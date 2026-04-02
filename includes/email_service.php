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
 *    PDO in ERRMODE_EXCEPTION will throw if INSERT fails — no need to verify.
 * 2. Use DATE_ADD(NOW(), INTERVAL N HOUR) in SQL so the DB computes its own
 *    expiry in its own timezone — zero PHP↔MySQL timezone conversion needed.
 * 3. The lookup in set_password.php uses expires_at > NOW() (same timezone).
 */
function generateSetupToken(PDO $db, int $userId): string {
    $token       = bin2hex(random_bytes(32));
    $expiryHours = (int)($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48);

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

function generateResetToken(PDO $db, int $userId): string {
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

function sendAccountCreationEmail(PDO $db, array $user): bool {
    try {
        $token = generateSetupToken($db, $user['user_id']);
    } catch (\Exception $e) {
        error_log('SBM: Token generation failed for user_id=' . $user['user_id'] . ': ' . $e->getMessage());
        return false;
    }

    $setupLink = baseUrl() . '/set_password.php?token=' . urlencode($token);
    $expiry    = ($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48) . ' hours';
    $html      = buildWelcomeEmailHtml($user['full_name'], $user['email'], $setupLink, $expiry);

    $mail = new PHPMailer(true);
    try {
        _configureMailer($mail);
        $mail->addAddress($user['email'], $user['full_name']);



        $mail->isHTML(true);
        $mail->Subject = 'Your DIHS SBM Portal Account is Ready';
        $mail->Body    = $html;
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

function sendPasswordResetEmail(PDO $db, array $user): bool {
    try {
        $token = generateResetToken($db, $user['user_id']);
    } catch (\Exception $e) {
        error_log('SBM: Reset token generation failed for user_id=' . $user['user_id'] . ': ' . $e->getMessage());
        return false;
    }

    $resetLink = baseUrl() . '/set_password.php?token=' . urlencode($token) . '&mode=reset';
    $html      = buildResetEmailHtml($user['full_name'], $user['email'], $resetLink, '30 minutes');

    $mail = new PHPMailer(true);
    try {
        _configureMailer($mail);
        $mail->addAddress($user['email'], $user['full_name']);



        $mail->isHTML(true);
        $mail->Subject = 'Reset Your DIHS SBM Portal Password';
        $mail->Body    = $html;
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

function _configureMailer(PHPMailer $mail): void {
    $mail->isSMTP();
    $mail->Host       = $_ENV['SBM_MAIL_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SBM_MAIL_USER'] ?? '';
    $mail->Password   = $_ENV['SBM_MAIL_PASS'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($_ENV['SBM_MAIL_PORT'] ?? 587);
    $mail->setFrom(
        $_ENV['SBM_MAIL_FROM']      ?? 'no-reply@dihs.edu.ph',
        $_ENV['SBM_MAIL_FROM_NAME'] ?? 'DIHS SBM Portal'
    );
}

// ── Email HTML builders ──────────────────────────────────────────────────────

/**
 * Shared inline styles — Google Fonts aren't reliable in email clients,
 * so we fall back to a clean serif + sans-serif stack that renders well
 * in Gmail, Outlook, and Apple Mail.
 */
function _emailBaseStyles(): string {
    return '
    body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
    table,td{mso-table-lspace:0;mso-table-rspace:0;}
    img{-ms-interpolation-mode:bicubic;border:0;outline:none;text-decoration:none;}
    ';
}

function buildWelcomeEmailHtml(string $name, string $email,
                               string $link, string $expiry): string {
    $safeName  = htmlspecialchars($name,  ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink  = htmlspecialchars($link,  ENT_QUOTES, 'UTF-8');
    $year      = date('Y');
    $base      = _emailBaseStyles();

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
<body style="margin:0;padding:0;background-color:#f0f7f1;font-family:Georgia,'Times New Roman',serif;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background-color:#f0f7f1;padding:40px 16px;">
<tr><td align="center">

  <!-- Shell -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600"
         style="max-width:600px;width:100%;">

    <!-- ── HEADER ── -->
    <tr>
      <td style="background:linear-gradient(160deg,#052e16 0%,#064e1e 38%,#0d5c28 68%,#15803d 100%);
                 border-radius:18px 18px 0 0;padding:44px 48px 40px;text-align:center;">

        <!-- Logo -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr><td align="center" style="padding-bottom:18px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr><td style="width:76px;height:76px;border-radius:50%;
               background:#ffffff;
               border:3px solid rgba(255,255,255,0.9);
               text-align:center;vertical-align:middle;
               box-shadow:0 2px 12px rgba(0,0,0,0.18);">
                <img src="https://www.learnatdihs.com/assets/images/logo.png" width="52" height="52" alt="DIHS"
                     style="display:block;margin:12px auto;border-radius:50%;object-fit:contain;">
              </td></tr>
            </table>
          </td></tr>
        </table>

        <!-- Badge -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr><td align="center" style="padding-bottom:12px;">
            <span style="display:inline-block;background:rgba(255,255,255,0.12);
                         border:1px solid rgba(255,255,255,0.22);
                         color:rgba(255,255,255,0.8);
                         font-family:Arial,sans-serif;font-size:10px;
                         font-weight:700;letter-spacing:1.8px;text-transform:uppercase;
                         padding:5px 16px;border-radius:100px;">Account Ready</span>
          </td></tr>
        </table>

        <h1 style="margin:0 0 4px;color:#ffffff;
                   font-family:Georgia,'Times New Roman',serif;
                   font-size:26px;font-weight:400;letter-spacing:-0.3px;line-height:1.2;">
          DIHS SBM Online<br>Monitoring System
        </h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.5);
                  font-family:Arial,sans-serif;font-size:12px;letter-spacing:0.3px;">
          Dasmariñas Integrated High School &middot; DepEd Cavite
        </p>
      </td>
    </tr>

    <!-- Accent strip -->
    <tr>
      <td style="height:3px;background:linear-gradient(90deg,#052e16,#16a34a 50%,#052e16);
                 font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    <!-- ── BODY ── -->
    <tr>
      <td style="background:#ffffff;padding:44px 48px 36px;">

        <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:10.5px;
                  font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#16a34a;">
          Welcome Aboard
        </p>
        <h2 style="margin:0 0 20px;font-family:Georgia,'Times New Roman',serif;
                   font-size:30px;font-weight:400;color:#0a1f0e;
                   letter-spacing:-0.5px;line-height:1.15;">
          Your Account<br>Is Ready
        </h2>

        <p style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:15px;
                  color:#4b5563;line-height:1.8;">
          Hi <strong style="color:#0d5c28;font-weight:600;">{$safeName}</strong>,
          your DIHS SBM Portal account has been created using
          <strong style="color:#0d5c28;font-weight:600;">{$safeEmail}</strong>.
          Click the button below to set your password and activate your account.
        </p>

        <!-- Login email card -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="background:#f0fdf4;border:1px solid #bbf7d0;
                      border-left:3px solid #16a34a;
                      border-radius:10px;margin-bottom:28px;">
          <tr>
            <td style="padding:16px 20px;">
              <p style="margin:0 0 3px;font-family:Arial,sans-serif;font-size:10.5px;
                        font-weight:700;text-transform:uppercase;
                        letter-spacing:1.2px;color:#6b7280;">Your Login Email</p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:15px;
                        color:#0d5c28;font-weight:700;">{$safeEmail}</p>
            </td>
          </tr>
        </table>

        <!-- Steps row -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="border:1px solid #e5e7eb;border-radius:12px;
                      overflow:hidden;margin-bottom:28px;">
          <tr>
            <td width="33%" style="padding:14px 12px;text-align:center;
                                   border-right:1px solid #e5e7eb;">
              <p style="margin:0 auto 6px;width:24px;height:24px;
                        background:#dcfce7;border-radius:50%;
                        font-family:Arial,sans-serif;font-size:11px;
                        font-weight:700;color:#16a34a;
                        line-height:24px;text-align:center;">1</p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:11.5px;
                        color:#6b7280;line-height:1.4;">Click the button below</p>
            </td>
            <td width="33%" style="padding:14px 12px;text-align:center;
                                   border-right:1px solid #e5e7eb;">
              <p style="margin:0 auto 6px;width:24px;height:24px;
                        background:#dcfce7;border-radius:50%;
                        font-family:Arial,sans-serif;font-size:11px;
                        font-weight:700;color:#16a34a;
                        line-height:24px;text-align:center;">2</p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:11.5px;
                        color:#6b7280;line-height:1.4;">Create your password</p>
            </td>
            <td width="33%" style="padding:14px 12px;text-align:center;">
              <p style="margin:0 auto 6px;width:24px;height:24px;
                        background:#dcfce7;border-radius:50%;
                        font-family:Arial,sans-serif;font-size:11px;
                        font-weight:700;color:#16a34a;
                        line-height:24px;text-align:center;">3</p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:11.5px;
                        color:#6b7280;line-height:1.4;">Log in &amp; get started</p>
            </td>
          </tr>
        </table>

        <!-- CTA Button -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="margin-bottom:8px;">
          <tr><td align="center">
            <a href="{$safeLink}"
               style="display:inline-block;
                      background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);
                      color:#ffffff;text-decoration:none;
                      font-family:Arial,sans-serif;font-size:15px;font-weight:700;
                      padding:16px 52px;border-radius:100px;
                      letter-spacing:0.3px;">
              Set My Password
            </a>
          </td></tr>
        </table>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="margin-bottom:28px;">
          <tr><td align="center">
            <span style="font-family:Arial,sans-serif;font-size:12px;color:#9ca3af;">
              Link expires in {$expiry}
            </span>
          </td></tr>
        </table>

        <!-- Warning -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="background:#fffbeb;border:1px solid #fde68a;
                      border-left:3px solid #f59e0b;
                      border-radius:10px;margin-bottom:28px;">
          <tr>
            <td style="padding:14px 18px;">
              <p style="margin:0;font-family:Arial,sans-serif;font-size:13.5px;
                        color:#78350f;line-height:1.65;">
                &#9888;&#65039; <strong style="color:#92400e;">Didn't expect this?</strong>
                Contact your school administrator immediately.
                Do not share this link with anyone.
              </p>
            </td>
          </tr>
        </table>

        <!-- Fallback -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="border-top:1px solid #f3f4f6;padding-top:20px;">
          <tr>
            <td style="padding-top:20px;">
              <p style="margin:0 0 6px;font-family:Arial,sans-serif;
                        font-size:12px;color:#9ca3af;">
                If the button doesn't work, paste this into your browser:
              </p>
              <p style="margin:0;word-break:break-all;">
                <a href="{$safeLink}"
                   style="color:#2563eb;font-family:Arial,sans-serif;
                          font-size:12px;text-decoration:none;">{$safeLink}</a>
              </p>
            </td>
          </tr>
        </table>

      </td>
    </tr>

    <!-- ── FOOTER ── -->
    <tr>
      <td style="background:#071a0c;border-radius:0 0 18px 18px;padding:24px 48px;text-align:center;">
        <p style="margin:0 0 12px;">
          <span style="display:inline-block;width:6px;height:6px;border-radius:50%;
                       background:#16a34a;margin:0 3px;"></span>
          <span style="display:inline-block;width:4px;height:4px;border-radius:50%;
                       background:rgba(255,255,255,0.2);margin:0 3px;"></span>
          <span style="display:inline-block;width:4px;height:4px;border-radius:50%;
                       background:rgba(255,255,255,0.2);margin:0 3px;"></span>
        </p>
        <p style="margin:0;font-family:Arial,sans-serif;font-size:11.5px;
                  color:rgba(255,255,255,0.25);line-height:1.7;">
          This is an automated message &mdash; please do not reply.<br>
          &copy; {$year} Dasma&ntilde;inas Integrated High School. All rights reserved.
        </p>
      </td>
    </tr>

  </table>
</td></tr>
</table>
</body>
</html>
HTML;
}

function buildResetEmailHtml(string $name, string $email,
                              string $link, string $expiry): string {
    $safeName  = htmlspecialchars($name,  ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink  = htmlspecialchars($link,  ENT_QUOTES, 'UTF-8');
    $year      = date('Y');
    $base      = _emailBaseStyles();

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
<body style="margin:0;padding:0;background-color:#f0f7f1;font-family:Georgia,'Times New Roman',serif;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background-color:#f0f7f1;padding:40px 16px;">
<tr><td align="center">

  <!-- Shell -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600"
         style="max-width:600px;width:100%;">

    <!-- ── HEADER ── -->
    <tr>
      <td style="background:linear-gradient(160deg,#052e16 0%,#064e1e 38%,#0d5c28 68%,#15803d 100%);
                 border-radius:18px 18px 0 0;padding:44px 48px 40px;text-align:center;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr><td align="center" style="padding-bottom:18px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr><td style="width:76px;height:76px;border-radius:50%;
               background:#ffffff;
               border:3px solid rgba(255,255,255,0.9);
               text-align:center;vertical-align:middle;
               box-shadow:0 2px 12px rgba(0,0,0,0.18);">
                <img src="https://www.learnatdihs.com/assets/images/logo.png" width="52" height="52" alt="DIHS"
                     style="display:block;margin:12px auto;border-radius:50%;object-fit:contain;">
              </td></tr>
            </table>
          </td></tr>
        </table>

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr><td align="center" style="padding-bottom:12px;">
            <span style="display:inline-block;background:rgba(255,255,255,0.12);
                         border:1px solid rgba(255,255,255,0.22);
                         color:rgba(255,255,255,0.8);
                         font-family:Arial,sans-serif;font-size:10px;
                         font-weight:700;letter-spacing:1.8px;text-transform:uppercase;
                         padding:5px 16px;border-radius:100px;">Security Notice</span>
          </td></tr>
        </table>

        <h1 style="margin:0 0 4px;color:#ffffff;
                   font-family:Georgia,'Times New Roman',serif;
                   font-size:26px;font-weight:400;letter-spacing:-0.3px;line-height:1.2;">
          DIHS SBM Portal
        </h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.5);
                  font-family:Arial,sans-serif;font-size:12px;letter-spacing:0.3px;">
          Dasmariñas Integrated High School &middot; DepEd Cavite
        </p>
      </td>
    </tr>

    <!-- Accent strip -->
    <tr>
      <td style="height:3px;background:linear-gradient(90deg,#052e16,#16a34a 50%,#052e16);
                 font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    <!-- ── BODY ── -->
    <tr>
      <td style="background:#ffffff;padding:44px 48px 36px;">

        <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:10.5px;
                  font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#16a34a;">
          Password Reset
        </p>
        <h2 style="margin:0 0 20px;font-family:Georgia,'Times New Roman',serif;
                   font-size:30px;font-weight:400;color:#0a1f0e;
                   letter-spacing:-0.5px;line-height:1.15;">
          Reset Your<br>Password
        </h2>

        <p style="margin:0 0 28px;font-family:Arial,sans-serif;font-size:15px;
                  color:#4b5563;line-height:1.8;">
          Hi <strong style="color:#0d5c28;font-weight:600;">{$safeName}</strong>,
          we received a request to reset the password associated with your DIHS SBM account at
          <strong style="color:#0d5c28;font-weight:600;">{$safeEmail}</strong>.
        </p>

        <!-- CTA Button -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="margin-bottom:8px;">
          <tr><td align="center">
            <a href="{$safeLink}"
               style="display:inline-block;
                      background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);
                      color:#ffffff;text-decoration:none;
                      font-family:Arial,sans-serif;font-size:15px;font-weight:700;
                      padding:16px 52px;border-radius:100px;
                      letter-spacing:0.3px;">
              &#128272; Reset My Password
            </a>
          </td></tr>
        </table>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="margin-bottom:28px;">
          <tr><td align="center">
            <span style="font-family:Arial,sans-serif;font-size:12px;color:#9ca3af;">
              Expires in {$expiry} &middot; Single use only
            </span>
          </td></tr>
        </table>

        <!-- Warning -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="background:#fffbeb;border:1px solid #fde68a;
                      border-left:3px solid #f59e0b;
                      border-radius:10px;margin-bottom:28px;">
          <tr>
            <td style="padding:14px 18px;">
              <p style="margin:0;font-family:Arial,sans-serif;font-size:13.5px;
                        color:#78350f;line-height:1.65;">
                &#9888;&#65039; <strong style="color:#92400e;">Didn't request this?</strong>
                Ignore this email &mdash; your account is safe and no changes have been made.
                If you keep receiving these emails, contact your school administrator.
              </p>
            </td>
          </tr>
        </table>

        <!-- Fallback -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="border-top:1px solid #f3f4f6;padding-top:20px;">
              <p style="margin:0 0 6px;font-family:Arial,sans-serif;
                        font-size:12px;color:#9ca3af;">
                If the button doesn't work, paste this into your browser:
              </p>
              <p style="margin:0;word-break:break-all;">
                <a href="{$safeLink}"
                   style="color:#2563eb;font-family:Arial,sans-serif;
                          font-size:12px;text-decoration:none;">{$safeLink}</a>
              </p>
            </td>
          </tr>
        </table>

      </td>
    </tr>

    <!-- ── FOOTER ── -->
    <tr>
      <td style="background:#071a0c;border-radius:0 0 18px 18px;padding:24px 48px;text-align:center;">
        <p style="margin:0 0 12px;">
          <span style="display:inline-block;width:6px;height:6px;border-radius:50%;
                       background:#16a34a;margin:0 3px;"></span>
          <span style="display:inline-block;width:4px;height:4px;border-radius:50%;
                       background:rgba(255,255,255,0.2);margin:0 3px;"></span>
          <span style="display:inline-block;width:4px;height:4px;border-radius:50%;
                       background:rgba(255,255,255,0.2);margin:0 3px;"></span>
        </p>
        <p style="margin:0;font-family:Arial,sans-serif;font-size:11.5px;
                  color:rgba(255,255,255,0.25);line-height:1.7;">
          This is an automated security message &mdash; please do not reply.<br>
          &copy; {$year} Dasma&ntilde;inas Integrated High School. All rights reserved.
        </p>
      </td>
    </tr>

  </table>
</td></tr>
</table>
</body>
</html>
HTML;
}