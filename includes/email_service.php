<?php
// includes/email_service.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

function generateSetupToken(PDO $db, int $userId): string {
    $token     = bin2hex(random_bytes(48));
    $expiresAt = date('Y-m-d H:i:s',
        time() + ((int)($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48) * 3600)
    );
    $db->prepare("UPDATE password_setup_tokens SET used_at=NOW()
                  WHERE user_id=? AND used_at IS NULL")
       ->execute([$userId]);
    $db->prepare("INSERT INTO password_setup_tokens
                    (user_id, token, type, expires_at)
                  VALUES (?, ?, 'setup', ?)")
       ->execute([$userId, $token, $expiresAt]);
    return $token;
}

function sendAccountCreationEmail(PDO $db, array $user): bool {
    $token     = generateSetupToken($db, $user['user_id']);
    $setupLink = baseUrl() . '/set_password.php?token=' . $token;
    $expiry    = ($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48) . ' hours';

    $html = buildWelcomeEmailHtml($user['full_name'], $user['email'], $setupLink, $expiry);

    $mail = new PHPMailer(true);
    try {
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
        $mail->addAddress($user['email'], $user['full_name']);

        // Embed the school logo
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

        $db->prepare("UPDATE users SET email_sent_at=NOW() WHERE user_id=?")
           ->execute([$user['user_id']]);
        $db->prepare("INSERT INTO email_logs
                        (user_id, email_type, recipient_email, status)
                      VALUES (?, 'account_creation', ?, 'sent')")
           ->execute([$user['user_id'], $user['email']]);

        return true;
    } catch (Exception $e) {
        $errMsg = $mail->ErrorInfo ?: $e->getMessage();
        $db->prepare("INSERT INTO email_logs
                        (user_id, email_type, recipient_email, status, error_message)
                      VALUES (?, 'account_creation', ?, 'failed', ?)")
           ->execute([$user['user_id'], $user['email'], $errMsg]);
        error_log('SBM Email Error: ' . $errMsg);
        return false;
    }
}

function generateResetToken(PDO $db, int $userId): string {
    $token     = bin2hex(random_bytes(48));
    $expiresAt = date('Y-m-d H:i:s', time() + 30 * 60);
 
    // Invalidate all previous unused reset tokens for this user FIRST
    $db->prepare(
        "UPDATE password_setup_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND type = 'reset' AND used_at IS NULL"
    )->execute([$userId]);
 
    // Insert the new token AFTER invalidating old ones
    $db->prepare(
        "INSERT INTO password_setup_tokens (user_id, token, type, expires_at)
         VALUES (?, ?, 'reset', ?)"
    )->execute([$userId, $token, $expiresAt]);

    // Verify the token was actually saved correctly
    $check = $db->prepare(
        "SELECT token FROM password_setup_tokens 
         WHERE token = ? AND used_at IS NULL AND expires_at > NOW()"
    );
    $check->execute([$token]);
    if (!$check->fetch()) {
        throw new \RuntimeException('Token generation failed — token not found after insert.');
    }
 
    return $token;
}
 
/**
 * Send a password reset email to the user.
 * Returns true on success, false on failure.
 */
function sendPasswordResetEmail(PDO $db, array $user): bool {
    $token     = generateResetToken($db, $user['user_id']);
    $resetLink = baseUrl() . '/set_password.php?token=' . $token . '&mode=reset';
 
    $html = buildResetEmailHtml(
        $user['full_name'],
        $user['email'],
        $resetLink,
        '30 minutes'
    );
 
    $mail = new PHPMailer(true);
    try {
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
        $mail->addAddress($user['email'], $user['full_name']);
 
        $logoPath = dirname(__DIR__) . '/assets/seal.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'school_logo_cid', 'seal.png');
        }
 
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your DIHS SBM Portal Password';
        $mail->Body    = $html;
        $mail->AltBody = "Hi {$user['full_name']},\n\n"
                       . "We received a request to reset your DIHS SBM Portal password.\n\n"
                       . "Reset your password here: $resetLink\n\n"
                       . "This link expires in 30 minutes. If you did not request this, "
                       . "please ignore this email — your account remains secure.\n\n"
                       . "DIHS SBM Portal";
        $mail->send();
 
        // Log success
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
 
/**
 * Build the HTML body for the password reset email.
 */
function buildResetEmailHtml(string $name, string $email,
                              string $link, string $expiry): string {
    $safeName  = htmlspecialchars($name,  ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink  = htmlspecialchars($link,  ENT_QUOTES, 'UTF-8');
    $year      = date('Y');
 
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Your Password</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F7F1;font-family:'Segoe UI',Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;">
 
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#F0F7F1;padding:40px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">
 
        <!-- HEADER -->
        <tr>
          <td style="background:linear-gradient(150deg,#0D5C28 0%,#16A34A 60%,#22C55E 100%);border-radius:14px 14px 0 0;padding:36px 40px 32px;text-align:center;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td align="center" style="padding-bottom:16px;">
                  <table role="presentation" cellpadding="4" cellspacing="0" align="center" style="border-radius:50%;background:#FFFFFF;border:3px solid rgba(255,255,255,0.25);">
  <tr><td align="center" valign="middle" width="72" height="72">
    <img src="cid:school_logo_cid" width="64" height="64" alt="DIHS Logo" style="display:block;border-radius:50%;object-fit:contain;">
  </td></tr>
</table>
                </td>
              </tr>
              <tr>
                <td align="center">
                  <h1 style="margin:0;color:#FFFFFF;font-size:20px;font-weight:700;letter-spacing:-0.3px;">DIHS SBM Portal</h1>
                  <p style="margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">Password Reset Request</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
 
        <!-- BODY -->
        <tr>
          <td style="background:#FFFFFF;padding:40px 40px 32px;">
 
            <h2 style="margin:0 0 6px;color:#0D5C28;font-size:22px;font-weight:700;">Reset Your Password</h2>
            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.75;">
              Hi <strong style="color:#111827;">{$safeName}</strong>, we received a request to reset the password for your account associated with <strong style="color:#0D5C28;">{$safeEmail}</strong>.
            </p>
 
            <!-- Divider -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
              <tr><td style="border-top:1px solid #E5E7EB;"></td></tr>
            </table>
 
            <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.75;">
              Click the button below to set a new password. This link is single-use and will expire in <strong style="color:#0D5C28;">{$expiry}</strong>.
            </p>
 
            <!-- CTA Button -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
              <tr>
                <td align="center">
                  <a href="{$safeLink}"
                     style="display:inline-block;background:linear-gradient(135deg,#16A34A 0%,#15803D 100%);color:#FFFFFF;text-decoration:none;font-size:15px;font-weight:700;padding:15px 40px;border-radius:9px;letter-spacing:0.2px;box-shadow:0 4px 12px rgba(22,163,74,0.35);">
                    Reset My Password
                  </a>
                </td>
              </tr>
            </table>
 
            <!-- Divider -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:20px;">
              <tr><td style="border-top:1px solid #E5E7EB;"></td></tr>
            </table>
 
            <!-- Security notice card -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;margin-bottom:24px;">
              <tr>
                <td style="padding:16px 20px;">
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td width="24" valign="top" style="padding-right:10px;padding-top:1px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M12 2L2 20H22L12 2Z" stroke="#D97706" stroke-width="2" stroke-linejoin="round" fill="#FDE68A"/>
                          <path d="M12 9V13" stroke="#D97706" stroke-width="2" stroke-linecap="round"/>
                          <circle cx="12" cy="17" r="1" fill="#D97706"/>
                        </svg>
                      </td>
                      <td valign="top">
                        <p style="margin:0;color:#92400E;font-size:13.5px;line-height:1.6;">
                          <strong>Didn't request this?</strong> If you didn't request a password reset, ignore this email. Your account remains secure and your password has not been changed.
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
 
            <!-- Fallback link -->
            <p style="margin:0;color:#9CA3AF;font-size:12px;line-height:1.7;">
              If the button above doesn't work, copy and paste this link into your browser:
            </p>
            <p style="margin:6px 0 0;word-break:break-all;">
              <a href="{$safeLink}" style="color:#2563EB;font-size:12px;text-decoration:none;">{$safeLink}</a>
            </p>
 
          </td>
        </tr>
 
        <!-- FOOTER -->
        <tr>
          <td style="background:#1A2E1F;border-radius:0 0 14px 14px;padding:24px 40px;text-align:center;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td align="center" style="padding-bottom:12px;">
                  <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:0 10px;border-right:1px solid rgba(255,255,255,0.15);">
                        <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">DIHS SBM Portal</p>
                      </td>
                      <td style="padding:0 10px;border-right:1px solid rgba(255,255,255,0.15);">
                        <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">DepEd Cavite</p>
                      </td>
                      <td style="padding:0 10px;">
                        <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">DepEd Order No. 007, s. 2024</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td align="center">
                  <p style="margin:0;color:rgba(255,255,255,0.3);font-size:11px;">
                    This is an automated security message &mdash; please do not reply.<br>
                    &copy; {$year} Dasmariñas Integrated High School. All rights reserved.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
 
        <tr><td style="height:32px;"></td></tr>
 
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
}

function buildWelcomeEmailHtml(string $name, string $email,
                               string $link, string $expiry): string {
    $safeName  = htmlspecialchars($name,  ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink  = htmlspecialchars($link,  ENT_QUOTES, 'UTF-8');
    $year      = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your DIHS SBM Portal Account</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#F0F7F1;font-family:'Segoe UI',Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#F0F7F1;padding:40px 16px;">
  <tr>
    <td align="center">

      <!-- Outer container -->
      <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">

        <!-- ===== HEADER ===== -->
        <tr>
          <td style="background:linear-gradient(150deg,#0D5C28 0%,#16A34A 60%,#22C55E 100%);border-radius:14px 14px 0 0;padding:36px 40px 32px;text-align:center;">

            <!-- Shield / Crest icon (SVG, no emoji) -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td align="center" style="padding-bottom:16px;">
                  <table role="presentation" cellpadding="4" cellspacing="0" align="center" style="border-radius:50%;background:#FFFFFF;border:3px solid rgba(255,255,255,0.25);">
  <tr><td align="center" valign="middle" width="72" height="72">
    <img src="cid:school_logo_cid" width="64" height="64" alt="DIHS Logo" style="display:block;border-radius:50%;object-fit:contain;">
  </td></tr>
</table>
                </td>
              </tr>
              <tr>
                <td align="center">
                  <h1 style="margin:0;color:#FFFFFF;font-size:20px;font-weight:700;letter-spacing:-0.3px;line-height:1.3;">DIHS SBM Online Monitoring System</h1>
                  <p style="margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;letter-spacing:0.3px;">Dasmariñas Integrated High School &nbsp;·&nbsp; DepEd Cavite</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ===== BODY ===== -->
        <tr>
          <td style="background:#FFFFFF;padding:40px 40px 32px;">

            <!-- Greeting -->
            <h2 style="margin:0 0 6px;color:#0D5C28;font-size:22px;font-weight:700;">Welcome, {$safeName}!</h2>
            <p style="margin:0 0 24px;color:#6B7280;font-size:13px;font-weight:500;text-transform:uppercase;letter-spacing:0.8px;">Account Activation Notice</p>

            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.75;">
              Your account has been created in the <strong style="color:#0D5C28;">DIHS SBM Portal</strong>. You can now participate in the school's School-Based Management self-assessment process.
            </p>

            <!-- Divider -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
              <tr><td style="border-top:1px solid #E5E7EB;"></td></tr>
            </table>

            <!-- Login info card -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;margin-bottom:24px;">
              <tr>
                <td style="padding:18px 20px;">
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td width="32" valign="top" style="padding-right:12px;padding-top:2px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <rect x="2" y="4" width="20" height="16" rx="3" fill="#16A34A" opacity="0.15"/>
                          <path d="M2 8L12 13L22 8" stroke="#16A34A" stroke-width="2" stroke-linecap="round"/>
                          <rect x="2" y="4" width="20" height="16" rx="3" stroke="#16A34A" stroke-width="1.5"/>
                        </svg>
                      </td>
                      <td valign="top">
                        <p style="margin:0 0 3px;color:#6B7280;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.7px;">Your Login Email</p>
                        <p style="margin:0;color:#0D5C28;font-size:15px;font-weight:600;">{$safeEmail}</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.75;">
              To activate your account and gain access to the portal, please set your password by clicking the button below:
            </p>

            <!-- CTA Button -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
              <tr>
                <td align="center">
                  <a href="{$safeLink}"
                     style="display:inline-block;background:linear-gradient(135deg,#16A34A 0%,#15803D 100%);color:#FFFFFF;text-decoration:none;font-size:15px;font-weight:700;padding:15px 40px;border-radius:9px;letter-spacing:0.2px;box-shadow:0 4px 12px rgba(22,163,74,0.35);">
                    &#x1F512;&nbsp; Set My Password
                  </a>
                </td>
              </tr>
            </table>

            <!-- Divider -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:20px;">
              <tr><td style="border-top:1px solid #E5E7EB;"></td></tr>
            </table>

            <!-- Warning card -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;margin-bottom:24px;">
              <tr>
                <td style="padding:16px 20px;">
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td width="24" valign="top" style="padding-right:10px;padding-top:1px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M12 2L2 20H22L12 2Z" stroke="#D97706" stroke-width="2" stroke-linejoin="round" fill="#FDE68A"/>
                          <path d="M12 9V13" stroke="#D97706" stroke-width="2" stroke-linecap="round"/>
                          <circle cx="12" cy="17" r="1" fill="#D97706"/>
                        </svg>
                      </td>
                      <td valign="top">
                        <p style="margin:0;color:#92400E;font-size:13.5px;line-height:1.6;">
                          This link will expire in <strong>{$expiry}</strong>. If you did not expect this email, please contact your School Administrator immediately.
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Fallback link -->
            <p style="margin:0;color:#9CA3AF;font-size:12px;line-height:1.7;">
              If the button above doesn't work, copy and paste this link into your browser:
            </p>
            <p style="margin:6px 0 0;word-break:break-all;">
              <a href="{$safeLink}" style="color:#2563EB;font-size:12px;text-decoration:none;">{$safeLink}</a>
            </p>

          </td>
        </tr>

        <!-- ===== FOOTER ===== -->
        <tr>
          <td style="background:#1A2E1F;border-radius:0 0 14px 14px;padding:24px 40px;text-align:center;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td align="center" style="padding-bottom:12px;">
                  <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:0 10px;border-right:1px solid rgba(255,255,255,0.15);">
                        <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">DIHS SBM Portal</p>
                      </td>
                      <td style="padding:0 10px;border-right:1px solid rgba(255,255,255,0.15);">
                        <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">DepEd Cavite</p>
                      </td>
                      <td style="padding:0 10px;">
                        <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">DepEd Order No. 007, s. 2024</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td align="center">
                  <p style="margin:0;color:rgba(255,255,255,0.3);font-size:11px;">
                    This is an automated message &mdash; please do not reply directly to this email.<br>
                    &copy; {$year} Dasmariñas Integrated High School. All rights reserved.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Bottom spacer -->
        <tr><td style="height:32px;"></td></tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;
}