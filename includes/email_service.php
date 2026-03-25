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

    // Invalidate any previous unused tokens
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

        $mail->isHTML(true);
        $mail->Subject = 'Your DIHS SBM Portal Account is Ready';
        $mail->Body    = $html;
        $mail->AltBody = "Welcome to the DIHS SBM Portal, {$user['full_name']}.\n\n"
                       . "Set your password here: $setupLink\n\n"
                       . "This link expires in $expiry.";
        $mail->send();

        // Log success + update user record
        $db->prepare("UPDATE users SET email_sent_at=NOW() WHERE user_id=?")
           ->execute([$user['user_id']]);
        $db->prepare("INSERT INTO email_logs 
                        (user_id, email_type, recipient_email, status) 
                      VALUES (?, 'account_creation', ?, 'sent')")
           ->execute([$user['user_id'], $user['email']]);

        return true;

    } catch (Exception $e) {
        // Log failure
        $db->prepare("INSERT INTO email_logs 
                        (user_id, email_type, recipient_email, status, error_message) 
                      VALUES (?, 'account_creation', ?, 'failed', ?)")
           ->execute([$user['user_id'], $user['email'], $mail->ErrorInfo]);
        error_log('SBM Email Error: ' . $mail->ErrorInfo);
        return false;
    }
}

function buildWelcomeEmailHtml(string $name, string $email, 
                               string $link, string $expiry): string {
    $safeName  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f8;margin:0;padding:0;}
  .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);}
  .hdr{background:linear-gradient(135deg,#0A4F1A 0%,#16A34A 100%);padding:28px 32px;text-align:center;}
  .hdr img{height:50px;margin-bottom:10px;}
  .hdr h1{color:#fff;font-size:18px;margin:0;font-weight:700;}
  .hdr p{color:rgba(255,255,255,.7);font-size:13px;margin:4px 0 0;}
  .body{padding:32px;}
  .body h2{color:#14532D;font-size:20px;margin:0 0 8px;}
  .body p{color:#374151;font-size:14px;line-height:1.7;margin:0 0 16px;}
  .info{background:#F0FDF4;border-left:4px solid #16A34A;padding:12px 16px;border-radius:0 8px 8px 0;margin:16px 0;}
  .info strong{color:#14532D;font-size:13px;}
  .btn{display:inline-block;background:#16A34A;color:#fff!important;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;margin:20px 0;}
  .warn{background:#FEF9C3;border:1px solid #FDE047;border-radius:8px;padding:12px 16px;font-size:13px;color:#713F12;margin:16px 0;}
  .ftr{background:#f4f6f8;padding:20px 32px;text-align:center;font-size:12px;color:#9CA3AF;}
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>DIHS SBM Online Monitoring System</h1>
    <p>Dasmariñas Integrated High School · DepEd Cavite</p>
  </div>
  <div class="body">
    <h2>Welcome, {$safeName}!</h2>
    <p>An account has been created for you in the <strong>DIHS SBM Portal</strong>. 
       You can now participate in the school's self-assessment process.</p>
    <div class="info">
      <strong>Your Login Email:</strong><br>
      📧 {$safeEmail}
    </div>
    <p>To activate your account, please set your password by clicking the button below:</p>
    <div style="text-align:center;">
      <a href="{$safeLink}" class="btn">🔐 Set My Password</a>
    </div>
    <div class="warn">
      ⚠️ This link will expire in <strong>{$expiry}</strong>. 
         If you did not expect this email, please contact your School Administrator.
    </div>
    <p style="font-size:12px;color:#6B7280;">If the button doesn't work, copy this link:<br>
      <span style="word-break:break-all;color:#2563EB;">{$safeLink}</span>
    </p>
  </div>
  <div class="ftr">
    This is an automated message · Do not reply<br>
    DIHS SBM Portal · DepEd Order No. 007, s. 2024
  </div>
</div>
</body>
</html>
HTML;
}