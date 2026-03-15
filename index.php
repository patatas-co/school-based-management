<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . roleHome($_SESSION['role'])); exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($uname && $pass) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND status='active' LIMIT 1");
        $stmt->execute([$uname, $uname]);
        $row  = $stmt->fetch();
        if ($row && password_verify($pass, $row['password'])) {
    $_SESSION['user_id']   = $row['user_id'];
    $_SESSION['username']  = $row['username'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['role']      = $row['role'];
    $_SESSION['school_id'] = $row['school_id'];

    // Rehash password if PHP default algorithm has changed
    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
    try {
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password=? WHERE user_id=?")
           ->execute([$newHash, $row['user_id']]);
    } catch (\Exception $e) {
        // Non-fatal — user is authenticated, rehash can happen next login
        error_log('Password rehash failed for user '.$row['user_id'].': '.$e->getMessage());
    }
}

    $db->prepare("UPDATE users SET last_login=NOW() WHERE user_id=?")->execute([$row['user_id']]);
    logActivity('login', 'auth', 'User logged in');
    header('Location: ' . roleHome($row['role'])); exit;
}
        $error = 'Incorrect username or password.';
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
<title>Sign In — <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --g900:#0A2E1A;--g800:#0F4A2B;--g700:#166534;--g600:#15803D;--g500:#16A34A;--g400:#22C55E;--g300:#86EFAC;--g200:#BBF7D0;--g100:#DCFCE7;
  --n900:#111827;--n700:#374151;--n600:#4B5563;--n500:#6B7280;--n400:#9CA3AF;--n200:#E5E7EB;--n100:#F3F4F6;--n50:#F9FAFB;--white:#fff;
  --red:#DC2626;--redb:#FEE2E2;--redc:#FECACA;
  --font:'DM Sans',sans-serif;
}
body{font-family:var(--font);min-height:100vh;display:flex;align-items:stretch;background:var(--white);-webkit-font-smoothing:antialiased;}
body::before{content:'';position:fixed;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#166534 0%,#22C55E 40%,#FFD700 70%,#CE1126 100%);z-index:999;}
.left{flex:0 0 52%;background:var(--g800);background-image:radial-gradient(ellipse at 0% 0%,rgba(34,197,94,.2) 0%,transparent 55%),radial-gradient(ellipse at 100% 100%,rgba(15,74,43,.8) 0%,transparent 60%);display:flex;flex-direction:column;justify-content:space-between;padding:52px 56px;position:relative;overflow:hidden;}
.left::before{content:'';position:absolute;width:420px;height:420px;border-radius:50%;border:1px solid rgba(255,255,255,.06);top:-100px;right:-100px;}
.seal-wrap{display:flex;align-items:center;gap:14px;margin-bottom:52px;}
.seal-img{width:68px;height:68px;border-radius:50%;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;}
.seal-img img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.seal-label{color:rgba(255,255,255,.65);font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;line-height:1.4;}
.headline{font-size:38px;font-weight:700;color:#fff;line-height:1.15;letter-spacing:-.02em;margin-bottom:16px;}
.headline em{font-style:normal;color:var(--g300);}
.sub{font-size:14.5px;color:rgba(255,255,255,.48);line-height:1.7;max-width:340px;}
.left-stats{display:flex;gap:0;border-top:1px solid rgba(255,255,255,.08);padding-top:28px;margin-top:48px;}
.si{flex:1;padding-right:24px;}
.si+.si{padding-left:24px;padding-right:0;border-left:1px solid rgba(255,255,255,.08);}
.sn{font-size:26px;font-weight:700;color:#fff;line-height:1;margin-bottom:4px;}
.sl{font-size:11px;color:rgba(255,255,255,.38);text-transform:uppercase;letter-spacing:.06em;}
.foot{font-size:11px;color:rgba(255,255,255,.2);margin-top:28px;}
.right{flex:1;display:flex;align-items:center;justify-content:center;padding:52px 56px;background:var(--white);}
.form-box{width:100%;max-width:380px;}
.eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:28px;}
.edot{width:8px;height:8px;border-radius:50%;background:var(--g500);}
.etxt{font-size:11.5px;font-weight:700;color:var(--g600);letter-spacing:.07em;text-transform:uppercase;}
.ftitle{font-size:28px;font-weight:700;color:var(--n900);letter-spacing:-.02em;margin-bottom:6px;line-height:1.2;}
.fsub{font-size:14px;color:var(--n500);margin-bottom:36px;line-height:1.5;}
.alert-err{display:flex;align-items:flex-start;gap:10px;background:var(--redb);border:1px solid var(--redc);color:var(--red);border-radius:9px;padding:11px 13px;font-size:13.5px;margin-bottom:22px;line-height:1.5;}
.alert-err svg{width:15px;height:15px;flex-shrink:0;margin-top:2px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:13px;font-weight:600;color:var(--n700);margin-bottom:6px;}
.field-wrap{position:relative;}
.fi{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--n400);display:flex;align-items:center;}
.fi svg{width:16px;height:16px;}
.fc{width:100%;padding:10px 12px 10px 38px;border:1.5px solid var(--n200);border-radius:9px;background:var(--n50);font-family:var(--font);font-size:14px;color:var(--n900);outline:none;transition:border-color .15s,box-shadow .15s,background .15s;}
.fc::placeholder{color:var(--n400);}
.fc:focus{border-color:var(--g500);background:var(--white);box-shadow:0 0 0 3px rgba(22,163,74,.1);}
.btn-login{width:100%;padding:11px;border-radius:9px;border:none;background:var(--g600);color:#fff;font-family:var(--font);font-size:14.5px;font-weight:600;cursor:pointer;transition:background .15s,transform .1s;margin-top:6px;box-shadow:0 1px 2px rgba(0,0,0,.08),0 4px 12px rgba(22,163,74,.25);}
.btn-login:hover{background:var(--g700);transform:translateY(-1px);}
.roles{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:28px;}
.role-item{background:var(--n50);border:1px solid var(--n200);border-radius:8px;padding:10px;text-align:center;}
.role-item strong{display:block;font-size:11.5px;font-weight:700;color:var(--n700);}
.role-item span{font-size:10.5px;color:var(--n400);}
.ffooter{text-align:center;margin-top:22px;font-size:11.5px;color:var(--n400);}
@media(max-width:768px){body{flex-direction:column;}.left{flex:0 0 auto;padding:36px 28px;}.headline{font-size:26px;}.left-stats{display:none;}.right{padding:36px 28px;}}
</style>
</head>
<body>
<div class="left">
  <div>
    <div class="seal-wrap">
      <div class="seal-img">
        <?php if (file_exists(__DIR__.'/assets/seal.png')): ?>
          <img src="assets/seal.png" alt="Seal">
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.8)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        <?php endif; ?>
      </div>
      <span class="seal-label">Republic of the Philippines<br>Department of Education</span>
    </div>
    <h1 class="headline">School-Based<br>Management<br><em>Monitoring System</em></h1>
    <p class="sub">Digital platform for SBM self-assessment, monitoring, and governance aligned with DepEd Order No. 007, s. 2024.</p>
    <div class="left-stats">
      <div class="si"><div class="sn">42</div><div class="sl">Indicators</div></div>
      <div class="si"><div class="sn">6</div><div class="sl">Dimensions</div></div>
      <div class="si"><div class="sn">4</div><div class="sl">Maturity Levels</div></div>
    </div>
  </div>
  <p class="foot">DepEd Order No. 007, s. 2024 &nbsp;·&nbsp; <?= date('Y') ?></p>
</div>

<div class="right">
  <div class="form-box">
    <div class="eyebrow"><span class="edot"></span><span class="etxt">Secure Portal Access</span></div>
    <h2 class="ftitle">Welcome back</h2>
    <p class="fsub">Sign in with your DepEd credentials.</p>

    <?php if ($error): ?>
    <div class="alert-err">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="field">
        <label>Username or Email</label>
        <div class="field-wrap">
          <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input class="fc" type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="Enter username or email" required autofocus>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="field-wrap">
          <span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input class="fc" type="password" name="password" placeholder="Enter your password" required>
        </div>
      </div>
      <button class="btn-login" type="submit">Sign In</button>
    </form>

    <div class="roles">
      <div class="role-item"><strong>Admin</strong><span>System Admin</span></div>
      <div class="role-item"><strong>School Head</strong><span>SBM Coordinator</span></div>
      <div class="role-item"><strong>SDO / RO</strong><span>Monitoring</span></div>
    </div>
    <p class="ffooter"><?= e(SITE_NAME) ?> &nbsp;·&nbsp; <?= date('Y') ?></p>
  </div>
</div>
</body>
</html>
