<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Clear session if redirected from password setup (so a different user can log in)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['session_cleared'])) {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    // Regenerate CSRF token for the fresh session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . roleHome($_SESSION['role'])); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['err'] ?? '') === 'deactivated')) {
    $error = 'This account has been deactivated. Please contact the School Head for support.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $loginAttempts = (int)($_SESSION['login_attempts'] ?? 0);
    $lastAttempt  = (int)($_SESSION['login_last_attempt'] ?? 0);
    $lockoutTime  = 15 * 60;
    if ($loginAttempts >= 5 && (time() - $lastAttempt) < $lockoutTime) {
        $remaining = ceil(($lockoutTime - (time() - $lastAttempt)) / 60);
        $error = "Too many failed attempts. Please try again in {$remaining} minute(s).";
    } else {
        if ((time() - $lastAttempt) >= $lockoutTime) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_last_attempt'] = 0;
            $loginAttempts = 0;
        }

        $uname = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if ($uname && $pass) {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE (username=? OR email=?) LIMIT 1");
            $stmt->execute([$uname, $uname]);
            $row  = $stmt->fetch();
            if ($row && $row['status'] === 'inactive') {
                $error = 'This account has been deactivated. Please contact the School Head for support.';
            } elseif ($row && $row['status'] === 'active' && $row['password'] && password_verify($pass, $row['password'])) {
                unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);

                $_SESSION['user_id']    = $row['user_id'];
                $_SESSION['username']   = $row['username'];
                $_SESSION['full_name']  = $row['full_name'];
                $_SESSION['role']       = $row['role'];
                $_SESSION['school_id']  = $row['school_id'];

                if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                    try {
                        $db->prepare("UPDATE users SET password=? WHERE user_id=?")
                           ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['user_id']]);
                    } catch (\Exception $e) {}
                }

                $db->prepare("UPDATE users SET last_login=NOW() WHERE user_id=?")->execute([$row['user_id']]);
                logActivity('login', 'auth', 'User logged in');

                if ($row['force_password_change']) {
                    $_SESSION['force_pw_change'] = true;
                    header('Location: ' . baseUrl() . '/change_password.php'); exit;
                }
                header('Location: ' . roleHome($row['role'])); exit;
            } elseif ($row && $row['status'] !== 'active') {
                $error = 'This account is not available for sign-in. Please contact the School Head for support.';
            } else {
                $error = 'Incorrect username or password.';
            }
        } else {
            $error = 'Please enter your username and password.';
        }

        $_SESSION['login_attempts']    = $loginAttempts + 1;
        $_SESSION['login_last_attempt'] = time();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
<title>Sign In — <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
:root {
  --navy:#14532D; --green:#16A34A; --g50:#F0FDF4; --g100:#DCFCE7;
  --g200:#BBF7D0; --g300:#86EFAC; --g600:#16A34A; --g700:#15803D;
  --dark:#0D1117; --mid:#4B5563; --light:#9CA3AF;
  --n200:#E5E7EB; --n100:#F3F4F6; --red:#DC2626; --redb:#FEE2E2;
  --white:#FFFFFF; --font:'DM Sans',sans-serif; --serif:'Instrument Serif',Georgia,serif;
}
html,body { height:100%; font-family:var(--font); background:var(--white); color:var(--dark); -webkit-font-smoothing:antialiased; }

.layout { display:grid; grid-template-columns:1fr 1fr; min-height:100vh; }

.panel-left {
  position:relative; background:#0A0F0A; overflow:hidden;
  display:flex; flex-direction:column; padding:56px 64px;
}
.panel-left::before {
  content:''; position:absolute; inset:0;
  background-image: radial-gradient(ellipse at 10% 15%, rgba(22,163,74,.22) 0%, transparent 50%), radial-gradient(ellipse at 90% 85%, rgba(22,163,74,.14) 0%, transparent 45%);
  pointer-events:none;
}
.panel-left::after {
  content:''; position:absolute; inset:0;
  background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
  background-size:48px 48px; pointer-events:none;
}

.left-body { position:relative; z-index:1; margin:auto 0; padding-top:72px; }
.eyebrow {
  display:inline-flex; align-items:center; gap:7px;
  font-size:11px; font-weight:500; letter-spacing:.12em; text-transform:uppercase;
  color:var(--g300); margin-bottom:22px;
  opacity:0; animation:fadeUp .6s .25s ease forwards;
}
.eyebrow::before, .eyebrow::after { content:''; display:block; width:20px; height:1px; background:var(--g300); opacity:.5; }
.headline {
  font-family:var(--serif); font-size:clamp(40px, 4.2vw, 60px); font-weight:400;
  color:#fff; line-height:1.08; letter-spacing:-1.5px; margin-bottom:20px;
  opacity:0; animation:fadeUp .7s .35s ease forwards;
}
.headline em { font-style:italic; color:var(--g300); }
.sub {
  font-size:15px; font-weight:300; color:rgba(255,255,255,.45); line-height:1.75; max-width:340px;
  opacity:0; animation:fadeUp .7s .45s ease forwards;
}
.left-stats {
  display:flex; gap:0; border-top:1px solid rgba(255,255,255,.08);
  padding-top:28px; margin-top:64px; position:relative; z-index:1;
  opacity:0; animation:fadeUp .6s .55s ease forwards;
}
.stat-item { flex:1; padding-right:24px; }
.stat-item + .stat-item { padding-left:24px; padding-right:0; border-left:1px solid rgba(255,255,255,.08); }
.stat-num { font-family:var(--serif); font-size:32px; font-weight:400; color:#fff; line-height:1; margin-bottom:5px; }
.stat-lbl { font-size:11px; color:rgba(255,255,255,.3); text-transform:uppercase; letter-spacing:.07em; font-weight:500; }

.panel-right {
  display:flex; flex-direction:column; justify-content:center; align-items:center;
  padding:56px 64px; background:#FAFDFB; position:relative;
}

.form-wrap { width:100%; max-width:380px; opacity:0; animation:fadeUp .7s .3s ease forwards; }

.form-eyebrow { display:flex; align-items:center; gap:8px; margin-bottom:30px; }
.form-dot { width:8px; height:8px; border-radius:50%; background:var(--green); }
.form-eyebrow-text {
  font-size:11px; font-weight:700; color:var(--green); letter-spacing:.08em; text-transform:uppercase;
  background:rgba(22,163,74,.08); padding:3px 8px; border-radius:999px; border:1px solid rgba(22,163,74,.2);
}
.form-title { font-family:var(--serif); font-size:36px; font-weight:400; color:var(--dark); letter-spacing:-.5px; line-height:1.1; margin-bottom:6px; }
.form-sub { font-size:14px; font-weight:300; color:var(--mid); margin-bottom:36px; line-height:1.5; }

.alert-err { display:flex; align-items:flex-start; gap:10px; background:var(--redb); border:1px solid #FECACA; color:var(--red); border-radius:10px; padding:11px 13px; font-size:13.5px; margin-bottom:22px; line-height:1.5; }
.alert-err svg { width:15px; height:15px; flex-shrink:0; margin-top:2px; }

.field { margin-bottom:16px; }
.field label { display:block; font-size:12.5px; font-weight:600; color:var(--dark); margin-bottom:7px; letter-spacing:.01em; }
.field-wrap { position:relative; }
.field-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--light); display:flex; align-items:center; pointer-events:none; }
.field-icon svg { width:16px; height:16px; }
.fc {
  width:100%; padding:12px 13px 12px 42px; border:1.5px solid var(--n200);
  border-radius:10px; background:#fff; font-family:var(--font); font-size:14px; color:var(--dark);
  outline:none; transition:border-color .2s, box-shadow .2s; box-shadow:0 1px 2px rgba(0,0,0,.04);
}
.fc:focus { border-color:var(--green); box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.fc::placeholder { color:var(--light); }

.btn-login {
  width:100%; padding:13px; border-radius:10px; border:none;
  background:linear-gradient(135deg,#15803D 0%,#166534 100%); color:#fff;
  font-family:var(--font); font-size:14.5px; font-weight:600; cursor:pointer;
  margin-top:10px; display:flex; align-items:center; justify-content:center; gap:8px;
  transition:all .2s ease; box-shadow:0 2px 8px rgba(21,128,61,.35);
}
.btn-login:hover { background:linear-gradient(135deg,#166534 0%,#14532D 100%); transform:translateY(-1px); box-shadow:0 8px 24px rgba(21,128,61,.3); }
.btn-login .arrow { transition:transform .2s; display:inline-flex; align-items:center; }
.btn-login:hover .arrow { transform:translateX(3px); }

.roles { display:flex; gap:8px; flex-wrap:wrap; margin-top:28px; padding-top:24px; border-top:1px solid var(--n200); }
.role-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:999px; border:1.5px solid var(--n200); font-size:11.5px; font-weight:600; color:var(--mid); background:var(--n100); }
.role-dot { width:6px; height:6px; border-radius:50%; background:var(--green); opacity:.6; }

.form-footer { text-align:center; margin-top:24px; font-size:11.5px; color:var(--light); }

.back-link { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:600; color:var(--mid); text-decoration:none; margin-bottom:32px; padding:6px 12px 6px 0; border-radius:8px; transition:all .2s ease; border:1px solid transparent; }
.back-link:hover { color:var(--green); background:rgba(22,163,74,.05); border-color:rgba(22,163,74,.1); transform:translateX(-2px); }
.back-link svg { width:15px; height:15px; transition:transform .2s ease; }
.back-link:hover svg { transform:translateX(-2px); }

.forgot-link { font-size:12px; font-weight:600; color:var(--green); text-decoration:none; }
.forgot-link:hover { opacity:.7; }

@keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

@media (max-width:900px) {
  .layout { grid-template-columns:1fr; }
  .panel-left { display:none; }
  .panel-right { padding:40px 28px; }
}
</style>
</head>
<body>
<div class="layout">

  <!-- LEFT PANEL -->
  <div class="panel-left">
    <div style="position:absolute;bottom:-60px;right:-60px;z-index:0;pointer-events:none;opacity:.04;filter:grayscale(1);">
      <img src="assets/seal.png" alt="" style="width:380px;height:380px;object-fit:contain;">
    </div>
    <div class="left-body">
      <span class="eyebrow"><?= e(SITE_NAME) ?></span>
      <h1 class="headline">DIHS School-Based<br><em>Management Portal</em></h1>
      <p class="sub">Dasmariñas Integrated High School's digital platform for SBM self-assessment, monitoring, and planning aligned with DepEd Order No. 007, s. 2024.</p>
      <div class="left-stats">
        <div class="stat-item"><div class="stat-num">42</div><div class="stat-lbl">Indicators</div></div>
        <div class="stat-item"><div class="stat-num">6</div><div class="stat-lbl">Dimensions</div></div>
        <div class="stat-item"><div class="stat-num">4</div><div class="stat-lbl">Maturity Levels</div></div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="panel-right">
    <div class="form-wrap">
      <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back to home
      </a>

      <div class="form-eyebrow"><span class="form-dot"></span><span class="form-eyebrow-text">Secure Portal Access</span></div>
      <h2 class="form-title">Welcome back</h2>
      <p class="form-sub">Sign in with your credentials to continue.</p>

      <?php if ($error): ?>
      <div class="alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" action="login.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="field">
          <label>Username or Email</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input class="fc" type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="Enter username or email" required autofocus>
          </div>
        </div>

        <div class="field">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;">
            <label for="password" style="margin-bottom:0;">Password</label>
            <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
          </div>
          <div class="field-wrap">
            <span class="field-icon">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="4" y="11" width="16" height="9" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <input class="fc" type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>
        </div>

        <button class="btn-login" type="submit">
          Sign In
          <span class="arrow">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </button>
      </form>

      <!-- Updated role pills: no SDO/RO -->
      <div class="roles">
        <span class="role-pill"><span class="role-dot"></span>School Head</span>
        <span class="role-pill"><span class="role-dot" style="background:#16A34A;"></span>SBM Coordinator</span>
        <span class="role-pill"><span class="role-dot" style="background:#0D9488;"></span>Teacher</span>
        <span class="role-pill"><span class="role-dot" style="background:#2563EB;"></span>Stakeholder</span>
      </div>

      <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid #F3F4F6;">
        <p class="form-footer" style="margin:0;"><?= e(SITE_NAME) ?> &nbsp;·&nbsp; DepEd Order No. 007, s. 2024 &nbsp;·&nbsp; <?= date('Y') ?></p>
      </div>
    </div>
  </div>

</div>
<?php if (function_exists('renderPasswordToggle')) renderPasswordToggle(); ?>
</body>
</html>
