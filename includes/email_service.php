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

function sendRejectionEmail(PDO $db, array $user, string $reason): bool
{
  $html = buildRejectionEmailHtml($user['full_name'], $reason);

  $mail = new PHPMailer(true);
  try {
    _configureMailer($mail);
    $mail->addAddress($user['email'], $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'Update on Your DIHS SBM Portal Account Request';
    $mail->Body = $html;
    $mail->AltBody = "Hi {$user['full_name']},\n\nYour DIHS SBM Portal account request was not approved.\n\n"
      . "Reason: $reason\n\nIf you believe this was a mistake, please contact your school administrator.";
    $mail->send();

    $db->prepare(
      "INSERT INTO email_logs (user_id, email_type, recipient_email, status)
             VALUES (?, 'account_rejection', ?, 'sent')"
    )->execute([$user['user_id'], $user['email']]);

    return true;

  } catch (Exception $e) {
    $errMsg = $mail->ErrorInfo ?: $e->getMessage();
    $db->prepare(
      "INSERT INTO email_logs (user_id, email_type, recipient_email, status, error_message)
             VALUES (?, 'account_rejection', ?, 'failed', ?)"
    )->execute([$user['user_id'], $user['email'], $errMsg]);
    error_log('SBM Rejection Email Error: ' . $errMsg);
    return false;
  }
}

function sendAssessmentReturnedEmail(PDO $db, array $user, string $schoolYear, string $remarks): bool
{
  $safeName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
  $safeYear = htmlspecialchars($schoolYear, ENT_QUOTES, 'UTF-8');
  $safeRemarks = nl2br(htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8'));
  $html = '<!doctype html><html><body style="margin:0;background:#f4f7f5;font-family:Arial,sans-serif;color:#23352a;">'
    . '<div style="max-width:620px;margin:24px auto;background:#fff;border-radius:14px;overflow:hidden;">'
    . '<div style="background:#103b1e;color:#fff;padding:24px 28px;"><strong>DIHS SBM Portal</strong><br>'
    . '<span style="font-size:12px;opacity:.8;">Assessment returned for revision</span></div>'
    . '<div style="padding:28px;"><p>Hello <strong>' . $safeName . '</strong>,</p>'
    . '<p>The SBM Coordinator returned the assessment for <strong>SY ' . $safeYear . '</strong> for revision.</p>'
    . '<div style="background:#fff8e8;border-left:4px solid #d97706;padding:14px 16px;margin:18px 0;">'
    . '<strong>Reason for return</strong><br>' . $safeRemarks . '</div>'
    . '<p>Please review the remarks and revise your assigned indicators or evidence as needed.</p>'
    . '<p><strong>If your assessment is already complete, simply click the Submit button again.</strong> '
    . 'We apologize for the notification.</p>'
    . '<p style="color:#6b7b70;font-size:12px;margin-top:28px;">This is an automated message from the DIHS SBM Portal.</p>'
    . '</div></div></body></html>';

  $mail = new PHPMailer(true);
  try {
    _configureMailer($mail);
    $mail->addAddress($user['email'], $user['full_name']);
    $mail->isHTML(true);
    $mail->Subject = 'SBM Assessment Returned for Revision — SY ' . $schoolYear;
    $mail->Body = $html;
    $mail->AltBody = "Hello {$user['full_name']},\n\n"
      . "The SBM Coordinator returned the assessment for SY {$schoolYear} for revision.\n\n"
      . "Reason: {$remarks}\n\n"
      . "If your assessment is already complete, simply click the Submit button again. "
      . "We apologize for the notification.";
    $mail->send();
    $db->prepare("INSERT INTO email_logs (user_id,email_type,recipient_email,status) VALUES (?,'assessment_returned',?,'sent')")
      ->execute([$user['user_id'], $user['email']]);
    return true;
  } catch (Exception $e) {
    $errMsg = $mail->ErrorInfo ?: $e->getMessage();
    $db->prepare("INSERT INTO email_logs (user_id,email_type,recipient_email,status,error_message) VALUES (?,'assessment_returned',?,'failed',?)")
      ->execute([$user['user_id'], $user['email'], $errMsg]);
    error_log('SBM Assessment Return Email Error: ' . $errMsg);
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
  return buildActionEmailHtml(
    'Your DIHS SBM Portal account is ready',
    "Hello {$name},",
    "Your account was created with {$email}. Set your password to activate access.",
    'Set My Password',
    $link,
    "This secure link expires in {$expiry}.",
    'If you were not expecting this account, contact your school administrator and do not use the link.'
  );
}

function buildResetEmailHtml(
  string $name,
  string $email,
  string $link,
  string $expiry
): string {
  return buildActionEmailHtml(
    'Reset your DIHS SBM Portal password',
    "Hello {$name},",
    "We received a request to reset the password for {$email}.",
    'Reset My Password',
    $link,
    "This secure link expires in {$expiry} and can only be used once.",
    'If you did not request this, ignore this email. No password was changed.'
  );
}

function buildActionEmailHtml(
  string $title,
  string $greeting,
  string $message,
  string $buttonLabel,
  string $link,
  string $expiryNote,
  string $securityNote
): string {
  $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  $safeGreeting = htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8');
  $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
  $safeButtonLabel = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');
  $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
  $safeExpiryNote = htmlspecialchars($expiryNote, ENT_QUOTES, 'UTF-8');
  $safeSecurityNote = htmlspecialchars($securityNote, ENT_QUOTES, 'UTF-8');

  return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeTitle}</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;color:#202124;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
  <tr>
    <td style="padding:32px 20px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;">
        <tr>
          <td>
            <p style="margin:0 0 24px;color:#202124;font-size:18px;font-weight:700;">DIHS SBM Portal</p>
            <h1 style="margin:0 0 20px;color:#202124;font-size:24px;font-weight:500;line-height:1.3;">{$safeTitle}</h1>
            <p style="margin:0 0 16px;">{$safeGreeting}</p>
            <p style="margin:0 0 24px;">{$safeMessage}</p>
            <p style="margin:0 0 24px;">
              <a href="{$safeLink}" style="display:inline-block;background:#1a73e8;border-radius:4px;color:#ffffff;font-size:14px;font-weight:700;padding:11px 20px;text-decoration:none;">{$safeButtonLabel}</a>
            </p>
            <p style="margin:0 0 20px;color:#5f6368;font-size:13px;">{$safeExpiryNote}</p>
            <p style="margin:0 0 20px;color:#5f6368;font-size:13px;">{$safeSecurityNote}</p>
            <p style="margin:0 0 8px;color:#5f6368;font-size:13px;">If the button does not work, copy and paste this link:</p>
            <p style="margin:0 0 24px;font-size:13px;word-break:break-all;"><a href="{$safeLink}" style="color:#1a73e8;">{$safeLink}</a></p>
            <p style="margin:0;color:#80868b;font-size:12px;">This is an automated message from the DIHS SBM Portal.</p>
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
function buildRejectionEmailHtml(
  string $name,
  string $reason
): string {
  $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
  $safeReason = nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'));
  $year = date('Y');
  $base = _emailBaseStyles();

  return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Update on Your DIHS SBM Portal Account Request</title>
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
                          <span style="display:inline-block;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:999px;color:#ffffff;font-family:Arial,sans-serif;font-size:10px;font-weight:700;letter-spacing:1.6px;padding:6px 12px;text-transform:uppercase;">Account Request Update</span>
                        </div>
                        <div style="padding-top:14px;color:#ffffff;font-family:Georgia,'Times New Roman',serif;font-size:34px;font-weight:500;letter-spacing:-1px;line-height:1.05;">
                          Request not approved.
                        </div>
                        <div style="padding-top:10px;color:rgba(255,255,255,0.78);font-family:Arial,sans-serif;font-size:14px;line-height:1.7;">
                          Your account request could not be approved at this time.
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:28px;">
                        <p style="margin:0 0 20px 0;color:#5d6f62;font-family:Arial,sans-serif;font-size:15px;line-height:1.8;">
                          Hello <strong style="color:#102316;">{$safeName}</strong>, after reviewing your registration
                          request for the DIHS SBM Portal, the System Administrator was unable to approve it.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:22px;background:#fdf2f2;border-radius:16px;">
                          <tr>
                            <td style="padding:16px 18px;">
                              <div style="margin:0 0 6px 0;color:#9d3a3a;font-family:Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:1.2px;line-height:1.4;text-transform:uppercase;">Reason</div>
                              <div style="margin:0;color:#7a2222;font-family:Arial,sans-serif;font-size:14px;font-weight:600;line-height:1.7;">{$safeReason}</div>
                            </td>
                          </tr>
                        </table>

                        <p style="margin:0;color:#5d6f62;font-family:Arial,sans-serif;font-size:14px;line-height:1.8;">
                          If you believe this was a mistake or would like to submit a new request, please contact your school administrator directly.
                        </p>
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
