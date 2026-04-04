<?php
// includes/email_service.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * THE ACTUAL BUG:
 * The original generateSetupToken() did a post-INSERT verification SELECT:
 *
 *   SELECT token FROM password_setup_tokens
 *   WHERE token = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()
 *
 * This ALWAYS FAILED and triggered the RuntimeException because:
 * - config/db.php sets: $pdo->exec("SET time_zone = '+08:00'")
 * - So MySQL's NOW() = Manila time, and UTC_TIMESTAMP() = UTC (8 hrs behind)
 * - The token expires_at was stored using PHP's date() = Manila time
 * - But the verify SELECT used UTC_TIMESTAMP() (8 hours behind Manila)
 * - Result: "14:00 Manila" > "06:00 UTC" is TRUE normally, but the
 *   original code used BEGIN TRANSACTION + ROLLBACK, so on rollback
 *   the token was deleted before it could be used.
 *
 * THE FIX:
 * 1. Remove the transaction and post-INSERT verification entirely.
 *    PDO in ERRMODE_EXCEPTION will throw if INSERT fails - no need to verify.
 * 2. Use DATE_ADD(NOW(), INTERVAL N HOUR) in SQL so the DB computes its own
 *    expiry in its own timezone - zero PHP/MySQL timezone conversion needed.
 * 3. The lookup in set_password.php uses expires_at > NOW() (same timezone).
 */
function generateSetupToken(PDO $db, int $userId): string
{
  $token = bin2hex(random_bytes(32));
  $expiryHours = (int) ($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48);

  $db->prepare(
    "UPDATE password_setup_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL AND type = 'setup'"
  )->execute([$userId]);

  $db->prepare(
    "INSERT INTO password_setup_tokens (user_id, token, type, expires_at)
         VALUES (?, ?, 'setup', DATE_ADD(NOW(), INTERVAL ? HOUR))"
  )->execute([$userId, $token, $expiryHours]);

  return $token;
}

function generateResetToken(PDO $db, int $userId): string
{
  $token = bin2hex(random_bytes(32));

  $db->prepare(
    "UPDATE password_setup_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL"
  )->execute([$userId]);

  $db->prepare(
    "INSERT INTO password_setup_tokens (user_id, token, type, expires_at)
         VALUES (?, ?, 'reset', DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
  )->execute([$userId, $token]);

  return $token;
}

function sendAccountCreationEmail(PDO $db, array $user): bool
{
  try {
    $token = generateSetupToken($db, $user['user_id']);
  } catch (\Exception $e) {
    error_log('SBM: Token generation failed for user_id=' . $user['user_id'] . ': ' . $e->getMessage());
    return false;
  }

  $setupLink = baseUrl() . '/set_password.php?token=' . urlencode($token);
  $expiry = ($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48) . ' hours';
  $html = buildWelcomeEmailHtml($user['full_name'], $user['email'], $setupLink, $expiry);

  $mail = new PHPMailer(true);
  try {
    _configureMailer($mail);
    $mail->addAddress($user['email'], $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'Your DIHS SBM Portal Account is Ready';
    $mail->Body = $html;
    $mail->AltBody = "Welcome to the DIHS SBM Portal, {$user['full_name']}.\n\n"
      . "Set your password here: $setupLink\n\n"
      . "This link expires in $expiry.";
    $mail->send();

    $db->prepare("UPDATE users SET email_sent_at = NOW() WHERE user_id = ?")
      ->execute([$user['user_id']]);
    $db->prepare(
      "INSERT INTO email_logs (user_id, email_type, recipient_email, status)
             VALUES (?, 'account_creation', ?, 'sent')"
    )->execute([$user['user_id'], $user['email']]);

    return true;

  } catch (Exception $e) {
    $errMsg = $mail->ErrorInfo ?: $e->getMessage();
    $db->prepare(
      "INSERT INTO email_logs (user_id, email_type, recipient_email, status, error_message)
             VALUES (?, 'account_creation', ?, 'failed', ?)"
    )->execute([$user['user_id'], $user['email'], $errMsg]);
    error_log('SBM Email Error: ' . $errMsg);
    return false;
  }
}

function sendPasswordResetEmail(PDO $db, array $user): bool
{
  try {
    $token = generateResetToken($db, $user['user_id']);
  } catch (\Exception $e) {
    error_log('SBM: Reset token generation failed for user_id=' . $user['user_id'] . ': ' . $e->getMessage());
    return false;
  }

  $resetLink = baseUrl() . '/set_password.php?token=' . urlencode($token) . '&mode=reset';
  $html = buildResetEmailHtml($user['full_name'], $user['email'], $resetLink, '30 minutes');

  $mail = new PHPMailer(true);
  try {
    _configureMailer($mail);
    $mail->addAddress($user['email'], $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'Reset Your DIHS SBM Portal Password';
    $mail->Body = $html;
    $mail->AltBody = "Hi {$user['full_name']},\n\nReset your password: $resetLink\n\n"
      . "This link expires in 30 minutes.\n\nDIHS SBM Portal";
    $mail->send();

    $db->prepare(
      "INSERT INTO email_logs (user_id, email_type, recipient_email, status)
             VALUES (?, 'password_reset', ?, 'sent')"
    )->execute([$user['user_id'], $user['email']]);

    return true;

  } catch (Exception $e) {
    $errMsg = $mail->ErrorInfo ?: $e->getMessage();
    $db->prepare(
      "INSERT INTO email_logs (user_id, email_type, recipient_email, status, error_message)
             VALUES (?, 'password_reset', ?, 'failed', ?)"
    )->execute([$user['user_id'], $user['email'], $errMsg]);
    error_log('SBM Reset Email Error: ' . $errMsg);
    return false;
  }
}

function _configureMailer(PHPMailer $mail): void
{
  $mail->isSMTP();
  $mail->Host = $_ENV['SBM_MAIL_HOST'] ?? 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = $_ENV['SBM_MAIL_USER'] ?? '';
  $mail->Password = $_ENV['SBM_MAIL_PASS'] ?? '';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = (int) ($_ENV['SBM_MAIL_PORT'] ?? 587);
  $mail->setFrom(
    $_ENV['SBM_MAIL_FROM'] ?? 'no-reply@dihs.edu.ph',
    $_ENV['SBM_MAIL_FROM_NAME'] ?? 'DIHS SBM Portal'
  );
}

// Email HTML builders

/**
 * Shared inline styles for broad email-client compatibility.
 */
function _emailBaseStyles(): string
{
  return '
    body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
    table,td{mso-table-lspace:0;mso-table-rspace:0;}
    img{-ms-interpolation-mode:bicubic;border:0;outline:none;text-decoration:none;}
    table{border-collapse:collapse !important;}
    a{text-decoration:none;}
    ';
}

function buildWelcomeEmailHtml(
  string $name,
  string $email,
  string $link,
  string $expiry
): string {
  $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
  $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
  $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
  $year = date('Y');
  $base = _emailBaseStyles();

  return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Your DIHS SBM Portal Account is Ready</title>
<style>{$base}</style>
</head>
<body style="margin:0;padding:0;background-color:#eef4ef;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef4ef;">
  <tr>
    <td align="center" style="padding:32px 16px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;">
        <tr>
          <td>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ffffff;border-radius:22px;">
              <tr>
                <td style="padding:0;">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                      <td style="background:#103b1e;background-image:linear-gradient(145deg,#0c2d17 0%,#145127 52%,#1f7a3f 100%);border-radius:22px 22px 0 0;padding:28px 28px 24px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:54px;height:54px;border-radius:27px;background:#ffffff;text-align:center;vertical-align:middle;">
                              <img src="https://www.learnatdihs.com/assets/images/logo.png" width="42" height="42" alt="DIHS Logo" style="display:inline-block;vertical-align:middle;">
                            </td>
                            <td style="padding-left:14px;">
                              <div style="color:#ffffff;font-family:Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.3;">DIHS SBM Portal</div>
                              <div style="color:rgba(255,255,255,0.72);font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Dasmarinas Integrated High School</div>
                            </td>
                          </tr>
                        </table>
                        <div style="padding-top:18px;">
                          <span style="display:inline-block;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:10px;font-weight:700;letter-spacing:1.6px;padding:6px 12px;text-transform:uppercase;">Account Ready to Use</span>
                        </div>
                        <div style="padding-top:14px;color:#ffffff;font-family:Georgia,'Times New Roman',serif;font-size:34px;font-weight:500;letter-spacing:-1px;line-height:1.05;">
                          Your account is ready.
                        </div>
                        <div style="padding-top:10px;color:rgba(255,255,255,0.78);font-family:Arial,sans-serif;font-size:14px;line-height:1.7;">
                          Follow the steps below to set your password and activate your account.
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:28px;">
                        <p style="margin:0 0 20px 0;color:#5d6f62;font-family:Arial,sans-serif;font-size:15px;line-height:1.8;">
                          Hello <strong style="color:#102316;">{$safeName}</strong>, your DIHS SBM Portal account was created using
                          <strong style="color:#102316;">{$safeEmail}</strong>. Set your password below to activate access.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:18px;background:#e7f6eb;border-radius:16px;">
                          <tr>
                            <td style="padding:16px 18px;">
                              <div style="margin:0 0 6px 0;color:#5d6f62;font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Login Email</div>
                              <div style="margin:0;color:#103b1e;font-family:Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.4;">{$safeEmail}</div>
                            </td>
                          </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:22px;">
                          <tr>
                            <td width="33.33%" valign="top" style="padding-right:8px;">
                              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fbf8;border-radius:16px;">
                                <tr><td style="padding:14px 12px;">
                                  <div style="width:28px;height:28px;border-radius:16px;background:#d8efdf;color:#1f7a3f;font-family:Arial,sans-serif;font-size:12px;font-weight:800;line-height:28px;text-align:center;">1</div>
                                  <div style="padding-top:10px;color:#5d6f62;font-family:Arial,sans-serif;font-size:13px;line-height:1.5;">Open the secure setup link.</div>
                                </td></tr>
                              </table>
                            </td>
                            <td width="33.33%" valign="top" style="padding-left:4px;padding-right:4px;">
                              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fbf8;border-radius:16px;">
                                <tr><td style="padding:14px 12px;">
                                  <div style="width:28px;height:28px;border-radius:16px;background:#d8efdf;color:#1f7a3f;font-family:Arial,sans-serif;font-size:12px;font-weight:800;line-height:28px;text-align:center;">2</div>
                                  <div style="padding-top:10px;color:#5d6f62;font-family:Arial,sans-serif;font-size:13px;line-height:1.5;">Create a password for your account.</div>
                                </td></tr>
                              </table>
                            </td>
                            <td width="33.33%" valign="top" style="padding-left:8px;">
                              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fbf8;border-radius:16px;">
                                <tr><td style="padding:14px 12px;">
                                  <div style="width:28px;height:28px;border-radius:16px;background:#d8efdf;color:#1f7a3f;font-family:Arial,sans-serif;font-size:12px;font-weight:800;line-height:28px;text-align:center;">3</div>
                                  <div style="padding-top:10px;color:#5d6f62;font-family:Arial,sans-serif;font-size:13px;line-height:1.5;">Sign in and begin using the portal.</div>
                                </td></tr>
                              </table>
                            </td>
                          </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
                          <tr>
                            <td align="center">
                              <a href="{$safeLink}" style="display:inline-block;min-width:240px;background:#1f7a3f;background-image:linear-gradient(135deg,#1f7a3f 0%,#165c30 100%);border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.3px;padding:16px 28px;">Set My Password</a>
                            </td>
                          </tr>
                        </table>
                        <p style="margin:0 0 18px 0;color:#77877b;font-family:Arial,sans-serif;font-size:12px;line-height:1.6;text-align:center;">
                          This secure link expires in {$expiry}.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:18px;background:#fff8e8;border-radius:16px;">
                          <tr>
                            <td style="padding:16px 18px;">
                              <div style="margin:0 0 6px 0;color:#7a5311;font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Security Note</div>
                              <div style="margin:0;color:#7a5311;font-family:Arial,sans-serif;font-size:14px;font-weight:600;line-height:1.7;">
                                If you were not expecting this account, contact your school administrator and do not use the link.
                              </div>
                            </td>
                          </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                          <tr>
                            <td style="border-top:1px solid #edf2ed;padding-top:18px;color:#5d6f62;font-family:Arial,sans-serif;font-size:12px;line-height:1.7;">
                              If the button does not work, copy this link into your browser:<br>
                              <a href="{$safeLink}" style="color:#1459c7;word-break:break-all;">{$safeLink}</a>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td style="background:#f7faf7;border-top:1px solid #edf2ed;border-radius:0 0 22px 22px;padding:18px 28px 24px;color:#77877b;font-family:Arial,sans-serif;font-size:12px;line-height:1.7;text-align:center;">
                        Automated message from DIHS SBM Portal.<br>
                        &copy; {$year} Dasmarinas Integrated High School.
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
}

function buildResetEmailHtml(
  string $name,
  string $email,
  string $link,
  string $expiry
): string {
  $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
  $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
  $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
  $year = date('Y');
  $base = _emailBaseStyles();

  return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Reset Your Password</title>
<style>{$base}</style>
</head>
<body style="margin:0;padding:0;background-color:#eef4ef;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef4ef;">
  <tr>
    <td align="center" style="padding:32px 16px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;">
        <tr>
          <td>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ffffff;border-radius:22px;">
              <tr>
                <td style="padding:0;">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                      <td style="background:#103b1e;background-image:linear-gradient(145deg,#0c2d17 0%,#145127 52%,#1f7a3f 100%);border-radius:22px 22px 0 0;padding:28px 28px 24px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:54px;height:54px;border-radius:27px;background:#ffffff;text-align:center;vertical-align:middle;">
                              <img src="https://www.learnatdihs.com/assets/images/logo.png" width="42" height="42" alt="DIHS Logo" style="display:inline-block;vertical-align:middle;">
                            </td>
                            <td style="padding-left:14px;">
                              <div style="color:#ffffff;font-family:Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.3;">DIHS SBM Portal</div>
                              <div style="color:rgba(255,255,255,0.72);font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Dasmarinas Integrated High School</div>
                            </td>
                          </tr>
                        </table>
                        <div style="padding-top:18px;">
                          <span style="display:inline-block;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:10px;font-weight:700;letter-spacing:1.6px;padding:6px 12px;text-transform:uppercase;">Password Reset</span>
                        </div>
                        <div style="padding-top:14px;color:#ffffff;font-family:Georgia,'Times New Roman',serif;font-size:34px;font-weight:500;letter-spacing:-1px;line-height:1.05;">
                          Reset your password.
                        </div>
                        <div style="padding-top:10px;color:rgba(255,255,255,0.78);font-family:Arial,sans-serif;font-size:14px;line-height:1.7;">
                          Use the button below to set a new password for your account.
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:28px;">
                        <p style="margin:0 0 20px 0;color:#5d6f62;font-family:Arial,sans-serif;font-size:15px;line-height:1.8;">
                          Hello <strong style="color:#102316;">{$safeName}</strong>, we received a request to reset the password for
                          <strong style="color:#102316;">{$safeEmail}</strong>. Use the button below if this request was yours.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:20px;background:#e7f6eb;border-radius:16px;">
                          <tr>
                            <td style="padding:16px 18px;">
                              <div style="margin:0 0 6px 0;color:#5d6f62;font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Requested For</div>
                              <div style="margin:0;color:#103b1e;font-family:Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.4;">{$safeEmail}</div>
                            </td>
                          </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
                          <tr>
                            <td align="center">
                              <a href="{$safeLink}" style="display:inline-block;min-width:240px;background:#1f7a3f;background-image:linear-gradient(135deg,#1f7a3f 0%,#165c30 100%);border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.3px;padding:16px 28px;">Reset My Password</a>
                            </td>
                          </tr>
                        </table>
                        <p style="margin:0 0 18px 0;color:#77877b;font-family:Arial,sans-serif;font-size:12px;line-height:1.6;text-align:center;">
                          This secure link expires in {$expiry} and can only be used once.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:18px;background:#fff8e8;border-radius:16px;">
                          <tr>
                            <td style="padding:16px 18px;">
                              <div style="margin:0 0 6px 0;color:#7a5311;font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Did not request this?</div>
                              <div style="margin:0;color:#7a5311;font-family:Arial,sans-serif;font-size:14px;font-weight:600;line-height:1.7;">
                                Ignore this email. No password was changed yet. Contact the administrator if these requests continue.
                              </div>
                            </td>
                          </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                          <tr>
                            <td style="border-top:1px solid #edf2ed;padding-top:18px;color:#5d6f62;font-family:Arial,sans-serif;font-size:12px;line-height:1.7;">
                              If the button does not work, copy this link into your browser:<br>
                              <a href="{$safeLink}" style="color:#1459c7;word-break:break-all;">{$safeLink}</a>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td style="background:#f7faf7;border-top:1px solid #edf2ed;border-radius:0 0 22px 22px;padding:18px 28px 24px;color:#77877b;font-family:Arial,sans-serif;font-size:12px;line-height:1.7;text-align:center;">
                        Automated security message from DIHS SBM Portal.<br>
                        &copy; {$year} Dasmarinas Integrated High School.
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
}