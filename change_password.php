<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Must be logged in
requireLogin();

$db    = getDB();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    // Re-fetch user to verify current password
    $st = $db->prepare("SELECT password FROM users WHERE user_id=?");
    $st->execute([$_SESSION['user_id']]);
    $user = $st->fetch();

    if (!$user || !password_verify($current, $user['password'] ?? '')) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password=?, force_password_change=0 WHERE user_id=?")
           ->execute([$hash, $_SESSION['user_id']]);
        unset($_SESSION['force_pw_change']);
        logActivity('password_change', 'auth', 'User changed password');
        $success = true;
        // Redirect to dashboard after short delay
        header('refresh:2;url=' . roleHome($_SESSION['role']));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Change Password — <?= e(SITE_NAME) ?></title>
<link rel="icon" type="image/x-icon" href="<?= baseUrl() ?>/favicon/favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:#F0FDF4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:16px;padding:44px 40px;max-width:440px;width:100%;box-shadow:0 4px 32px rgba(0,0,0,.09);}
.logo{text-align:center;margin-bottom:32px;}
.logo-icon{width:56px;height:56px;background:linear-gradient(135deg,#16A34A,#166534);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;}
.logo-icon svg{width:28px;height:28px;stroke:#fff;fill:none;}
.logo h2{font-size:22px;font-weight:700;color:#14532D;margin-bottom:4px;}
.logo p{font-size:13.5px;color:#6B7280;line-height:1.5;}
label{display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;}
input[type=password]{width:100%;padding:11px 14px;border:1.5px solid #D1D5DB;border-radius:9px;font-size:14px;font-family:inherit;outline:none;transition:.2s;background:#fff;color:#111827;}
input[type=password]:focus{border-color:#16A34A;box-shadow:0 0 0 3px rgba(22,163,74,.12);}
.fg{margin-bottom:18px;}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#15803D,#166534);color:#fff;border:none;border-radius:9px;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:4px;transition:.2s;}
.btn:hover{opacity:.92;transform:translateY(-1px);}
.err{background:#FEE2E2;color:#DC2626;border:1px solid #FECACA;border-radius:9px;padding:11px 14px;font-size:13.5px;margin-bottom:18px;}
.ok{background:#DCFCE7;color:#15803D;border:1px solid #86EFAC;border-radius:9px;padding:16px;text-align:center;font-size:14px;font-weight:500;}
.hint{font-size:11.5px;color:#9CA3AF;margin-top:5px;}
.notice{background:#FEF9C3;border:1px solid #FDE047;border-radius:9px;padding:11px 14px;font-size:13px;color:#713F12;margin-bottom:20px;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
    </div>
    <h2>Change Your Password</h2>
    <p>You need to set a new password before continuing.</p>
  </div>

  <?php if ($success): ?>
  <div class="ok">
    ✅ <strong>Password changed successfully!</strong><br>
    <span style="font-size:13px;color:#166534;">Redirecting to your dashboard…</span>
  </div>

  <?php else: ?>
  <div class="notice">
    🔐 For security, please create a strong password with at least 8 characters.
  </div>

  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="fg">
      <label>Current Password</label>
      <input type="password" name="current_password" required placeholder="Enter your current password">
    </div>
    <div class="fg">
      <label>New Password</label>
      <input type="password" name="new_password" required minlength="8" placeholder="Min. 8 characters">
      <p class="hint">Use a mix of letters, numbers, and symbols.</p>
    </div>
    <div class="fg">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required minlength="8" placeholder="Repeat new password">
    </div>
    <button class="btn" type="submit">Set New Password →</button>
  </form>
  <?php endif; ?>
</div>
<?php if (function_exists('renderPasswordToggle')) renderPasswordToggle(); ?>
</body>
</html>
