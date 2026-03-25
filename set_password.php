<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$db    = getDB();
$token = trim($_GET['token'] ?? '');
$error = '';
$success = false;

// Validate token
$tokenRow = null;
if ($token) {
    $st = $db->prepare("SELECT pst.*, u.email, u.full_name 
                        FROM password_setup_tokens pst
                        JOIN users u ON pst.user_id = u.user_id
                        WHERE pst.token = ? 
                          AND pst.used_at IS NULL 
                          AND pst.expires_at > NOW()
                        LIMIT 1");
    $st->execute([$token]);
    $tokenRow = $st->fetch();
}

if (!$token || !$tokenRow) {
    $error = 'This link is invalid or has expired. Please contact your School Administrator for a new invitation.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

        // Activate user account
        $db->prepare("UPDATE users 
                      SET password=?, status='active', 
                          force_password_change=0 
                      WHERE user_id=?")
           ->execute([$hash, $tokenRow['user_id']]);

        // Mark token used
        $db->prepare("UPDATE password_setup_tokens 
                      SET used_at=NOW() WHERE token=?")
           ->execute([$token]);

        logActivity('password_set', 'auth', 
            'User set password via invite link');
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Set Password — DIHS SBM Portal</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:#F0FDF4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:14px;padding:40px;max-width:420px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.1);}
.logo{text-align:center;margin-bottom:28px;}
.logo h2{font-size:20px;color:#14532D;margin-top:10px;}
.logo p{font-size:13px;color:#6B7280;margin-top:4px;}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;}
input{width:100%;padding:11px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:14px;font-family:inherit;outline:none;transition:.2s;}
input:focus{border-color:#16A34A;box-shadow:0 0 0 3px rgba(22,163,74,.12);}
.fg{margin-bottom:16px;}
.btn{width:100%;padding:13px;background:#16A34A;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:6px;}
.btn:hover{background:#15803D;}
.err{background:#FEE2E2;color:#DC2626;border:1px solid #FECACA;border-radius:8px;padding:11px 14px;font-size:13.5px;margin-bottom:16px;}
.ok{background:#DCFCE7;color:#16A34A;border:1px solid #86EFAC;border-radius:8px;padding:16px;text-align:center;font-size:14px;}
.ok a{color:#16A34A;font-weight:700;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div style="font-size:40px;">🏫</div>
    <h2>DIHS SBM Portal</h2>
    <p>Set your account password</p>
  </div>

  <?php if ($success): ?>
  <div class="ok">
    ✅ <strong>Password set successfully!</strong><br>
    <div style="margin-top:10px;">
      <a href="<?= baseUrl() ?>/login.php">Sign in to your account →</a>
    </div>
  </div>

  <?php elseif ($error && !$tokenRow): ?>
  <div class="err"><?= htmlspecialchars($error) ?></div>
  <p style="text-align:center;font-size:13px;color:#6B7280;margin-top:16px;">
    Contact your administrator to resend the invitation.
  </p>

  <?php else: ?>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <p style="font-size:13.5px;color:#374151;margin-bottom:20px;">
    Hello, <strong><?= htmlspecialchars($tokenRow['full_name']) ?></strong>! 
    Create a secure password for your account.
  </p>
  <form method="post">
    <div class="fg">
      <label>New Password (min. 8 characters)</label>
      <input type="password" name="password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="fg">
      <label>Confirm Password</label>
      <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
    </div>
    <button class="btn" type="submit">Set Password & Activate Account</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>