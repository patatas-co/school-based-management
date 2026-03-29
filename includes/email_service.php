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

    // Invalidate previous unused setup tokens for this user
    $db->prepare(
        "UPDATE password_setup_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL AND type = 'setup'"
    )->execute([$userId]);

    // Let the DB compute expires_at in its own timezone — no PHP date() needed
    $db->prepare(
        "INSERT INTO password_setup_tokens (user_id, token, type, expires_at)
         VALUES (?, ?, 'setup', DATE_ADD(NOW(), INTERVAL ? HOUR))"
    )->execute([$userId, $token, $expiryHours]);

    return $token;
}

/**
 * Generate a 30-minute password reset token.
 * Same fix: DB-native expiry, no post-INSERT verification.
 */
function generateResetToken(PDO $db, int $userId): string {
    $token = bin2hex(random_bytes(32));

    // Invalidate previous unused tokens for this user
    $db->prepare(
        "UPDATE password_setup_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL"
    )->execute([$userId]);

    // DB-native 30-minute expiry
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

        $logoPath = dirname(__DIR__) . '/assets/seal.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'school_logo_cid', 'seal.png');
        }

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

        $logoPath = dirname(__DIR__) . '/assets/seal.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'school_logo_cid', 'seal.png');
        }

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

/** Shared SMTP configuration. */
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

// ── Email HTML builders ───────────────────────────────────────────────────

function buildWelcomeEmailHtml(string $name, string $email,
                               string $link, string $expiry): string {
    $safeName  = htmlspecialchars($name,  ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink  = htmlspecialchars($link,  ENT_QUOTES, 'UTF-8');
    $year      = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Your DIHS SBM Portal Account</title></head>
<body style="margin:0;padding:0;background:#F0F7F1;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F7F1;padding:40px 16px;">
  <tr><td align="center">
    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">
      <tr>
        <td style="background:linear-gradient(150deg,#0D5C28 0%,#16A34A 60%,#22C55E 100%);border-radius:14px 14px 0 0;padding:36px 40px 32px;text-align:center;">
          <img src="cid:school_logo_cid" width="64" height="64" alt="DIHS"
               style="display:block;margin:0 auto 16px;border-radius:50%;object-fit:contain;">
          <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;">DIHS SBM Online Monitoring System</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:13px;">Dasmariñas Integrated High School · DepEd Cavite</p>
        </td>
      </tr>
      <tr>
        <td style="background:#fff;padding:40px 40px 32px;">
          <h2 style="margin:0 0 6px;color:#0D5C28;font-size:22px;font-weight:700;">Welcome, {$safeName}!</h2>
          <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.75;">
            Your account has been created in the <strong style="color:#0D5C28;">DIHS SBM Portal</strong>
            using <strong style="color:#0D5C28;">{$safeEmail}</strong>.
            Click the button below to set your password and activate your account.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                 style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;margin-bottom:24px;">
            <tr><td style="padding:14px 18px;">
              <p style="margin:0 0 3px;color:#6B7280;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.7px;">Your Login Email</p>
              <p style="margin:0;color:#0D5C28;font-size:15px;font-weight:600;">{$safeEmail}</p>
            </td></tr>
          </table>
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
            <tr><td align="center">
              <a href="{$safeLink}"
                 style="display:inline-block;background:linear-gradient(135deg,#16A34A 0%,#15803D 100%);
                        color:#fff;text-decoration:none;font-size:15px;font-weight:700;
                        padding:15px 40px;border-radius:9px;box-shadow:0 4px 12px rgba(22,163,74,.35);">
                🔒 Set My Password
              </a>
            </td></tr>
          </table>
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                 style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;margin-bottom:24px;">
            <tr><td style="padding:14px 18px;">
              <p style="margin:0;color:#92400E;font-size:13.5px;line-height:1.6;">
                ⚠️ This link will expire in <strong>{$expiry}</strong>.
                If you did not expect this email, contact your School Administrator immediately.
              </p>
            </td></tr>
          </table>
          <p style="margin:0;color:#9CA3AF;font-size:12px;">If the button doesn't work, paste this into your browser:</p>
          <p style="margin:6px 0 0;word-break:break-all;">
            <a href="{$safeLink}" style="color:#2563EB;font-size:12px;">{$safeLink}</a>
          </p>
        </td>
      </tr>
      <tr>
        <td style="background:#1A2E1F;border-radius:0 0 14px 14px;padding:20px 40px;text-align:center;">
          <p style="margin:0;color:rgba(255,255,255,.3);font-size:11px;">
            This is an automated message — please do not reply.<br>
            © {$year} Dasmariñas Integrated High School. All rights reserved.
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

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Reset Your Password</title></head>
<body style="margin:0;padding:0;background:#F0F7F1;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F7F1;padding:40px 16px;">
  <tr><td align="center">
    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">
      <tr>
        <td style="background:linear-gradient(150deg,#0D5C28 0%,#16A34A 60%,#22C55E 100%);border-radius:14px 14px 0 0;padding:36px 40px 32px;text-align:center;">
          <img src="cid:school_logo_cid" width="64" height="64" alt="DIHS"
               style="display:block;margin:0 auto 16px;border-radius:50%;object-fit:contain;">
          <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;">DIHS SBM Portal</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:13px;">Password Reset Request</p>
        </td>
      </tr>
      <tr>
        <td style="background:#fff;padding:40px 40px 32px;">
          <h2 style="margin:0 0 6px;color:#0D5C28;font-size:22px;font-weight:700;">Reset Your Password</h2>
          <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.75;">
            Hi <strong>{$safeName}</strong>, we received a request to reset the password for
            <strong style="color:#0D5C28;">{$safeEmail}</strong>.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
            <tr><td align="center">
              <a href="{$safeLink}"
                 style="display:inline-block;background:linear-gradient(135deg,#16A34A 0%,#15803D 100%);
                        color:#fff;text-decoration:none;font-size:15px;font-weight:700;
                        padding:15px 40px;border-radius:9px;box-shadow:0 4px 12px rgba(22,163,74,.35);">
                Reset My Password
              </a>
            </td></tr>
          </table>
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                 style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;margin-bottom:24px;">
            <tr><td style="padding:14px 18px;">
              <p style="margin:0;color:#92400E;font-size:13.5px;line-height:1.6;">
                ⚠️ <strong>Didn't request this?</strong> Ignore this email — your account is secure.
                This link expires in <strong>{$expiry}</strong> and can only be used once.
              </p>
            </td></tr>
          </table>
          <p style="margin:0;color:#9CA3AF;font-size:12px;">If the button doesn't work, paste this into your browser:</p>
          <p style="margin:6px 0 0;word-break:break-all;">
            <a href="{$safeLink}" style="color:#2563EB;font-size:12px;">{$safeLink}</a>
          </p>
        </td>
      </tr>
      <tr>
        <td style="background:#1A2E1F;border-radius:0 0 14px 14px;padding:20px 40px;text-align:center;">
          <p style="margin:0;color:rgba(255,255,255,.3);font-size:11px;">
            This is an automated security message — please do not reply.<br>
            © {$year} Dasmariñas Integrated High School. All rights reserved.
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