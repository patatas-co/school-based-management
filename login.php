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
            $_SESSION['user_id']    = $row['user_id'];
            $_SESSION['username']   = $row['username'];
            $_SESSION['full_name']  = $row['full_name'];
            $_SESSION['role']       = $row['role'];
            $_SESSION['school_id']  = $row['school_id'];
            $_SESSION['division_id'] = $row['division_id'];
            $_SESSION['region_id']   = $row['region_id'];

            if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                try {
                    $newHash = password_hash($pass, PASSWORD_DEFAULT);
                    $db->prepare("UPDATE users SET password=? WHERE user_id=?")
                       ->execute([$newHash, $row['user_id']]);
                } catch (\Exception $e) {
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
<title>Sign In — <?= e(SITE_NAME) ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:    #15803D;
  --green:   #16A34A;
  --g50:     #F0FDF4;
  --g100:    #DCFCE7;
  --g200:    #BBF7D0;
  --g300:    #86EFAC;
  --g700:    #15803D;
  --dark:    #111827;
  --mid:     #4B5563;
  --light:   #9CA3AF;
  --n200:    #E5E7EB;
  --n100:    #F3F4F6;
  --red:     #DC2626;
  --redb:    #FEE2E2;
  --white:   #FFFFFF;
  --font:    'DM Sans', sans-serif;
  --serif:   'Instrument Serif', Georgia, serif;
}

html, body {
  height: 100%;
  font-family: var(--font);
  background: var(--white);
  color: var(--dark);
  -webkit-font-smoothing: antialiased;
}

/* ── DepEd flag stripe ── */
body::before {
  content: '';
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #166534 0%, #22C55E 40%, #FFD700 70%, #CE1126 100%);
  z-index: 999;
}

/* ══════════════════════════════════════
   LAYOUT — two-column
══════════════════════════════════════ */
.layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

/* ── LEFT — editorial panel ── */
.panel-left {
  position: relative;
  background: var(--dark);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: 56px 64px;
}

/* Noise texture overlay */
.panel-left::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(ellipse at 15% 20%, rgba(22,163,74,.18) 0%, transparent 55%),
    radial-gradient(ellipse at 85% 80%, rgba(22,163,74,.1) 0%, transparent 50%);
  pointer-events: none;
}

/* Decorative large circle */
.panel-left::after {
  content: '';
  position: absolute;
  width: 500px; height: 500px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,.05);
  top: -160px; right: -160px;
  pointer-events: none;
}

.left-nav {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: auto;
  position: relative;
  z-index: 1;

  opacity: 0;
  transform: translateY(-10px);
  animation: fadeUp .6s .1s ease forwards;
}

.left-seal {
  width: 44px; height: 44px;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.left-seal img {
  width: 100%; height: 100%;
  object-fit: cover; border-radius: 50%;
}

.left-seal-label {
  font-size: 11px;
  font-weight: 500;
  color: rgba(255,255,255,.45);
  line-height: 1.4;
  letter-spacing: .01em;
}

/* Editorial headline */
.left-body {
  position: relative;
  z-index: 1;
  margin-bottom: auto;
  padding-top: 72px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--g300);
  margin-bottom: 22px;

  opacity: 0;
  animation: fadeUp .6s .25s ease forwards;
}

.eyebrow::before, .eyebrow::after {
  content: '';
  display: block;
  width: 20px; height: 1px;
  background: var(--g300);
  opacity: .5;
}

.headline {
  font-family: var(--serif);
  font-size: clamp(42px, 4.5vw, 64px);
  font-weight: 400;
  color: #fff;
  line-height: 1.08;
  letter-spacing: -1.5px;
  margin-bottom: 20px;

  opacity: 0;
  animation: fadeUp .7s .35s ease forwards;
}

.headline em {
  font-style: italic;
  color: var(--g300);
}

.sub {
  font-size: 15px;
  font-weight: 300;
  color: rgba(255,255,255,.45);
  line-height: 1.75;
  max-width: 340px;

  opacity: 0;
  animation: fadeUp .7s .45s ease forwards;
}

/* Stats strip */
.left-stats {
  display: flex;
  gap: 0;
  border-top: 1px solid rgba(255,255,255,.08);
  padding-top: 28px;
  margin-top: 64px;
  position: relative;
  z-index: 1;

  opacity: 0;
  animation: fadeUp .6s .55s ease forwards;
}

.stat-item {
  flex: 1;
  padding-right: 24px;
}

.stat-item + .stat-item {
  padding-left: 24px;
  padding-right: 0;
  border-left: 1px solid rgba(255,255,255,.08);
}

.stat-num {
  font-family: var(--serif);
  font-size: 30px;
  font-weight: 400;
  color: #fff;
  line-height: 1;
  margin-bottom: 5px;
}

.stat-lbl {
  font-size: 11px;
  color: rgba(255,255,255,.3);
  text-transform: uppercase;
  letter-spacing: .07em;
  font-weight: 500;
}

/* ── RIGHT — form panel ── */
.panel-right {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 56px 64px;
  background: var(--white);
}

.form-wrap {
  width: 100%;
  max-width: 380px;

  opacity: 0;
  animation: fadeUp .7s .3s ease forwards;
}

.form-eyebrow {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 30px;
}

.form-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--green);
}

.form-eyebrow-text {
  font-size: 11px;
  font-weight: 700;
  color: var(--green);
  letter-spacing: .08em;
  text-transform: uppercase;
}

.form-title {
  font-family: var(--serif);
  font-size: 36px;
  font-weight: 400;
  color: var(--dark);
  letter-spacing: -.5px;
  line-height: 1.1;
  margin-bottom: 6px;
}

.form-sub {
  font-size: 14px;
  font-weight: 300;
  color: var(--mid);
  margin-bottom: 36px;
  line-height: 1.5;
}

/* Error */
.alert-err {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: var(--redb);
  border: 1px solid #FECACA;
  color: var(--red);
  border-radius: 10px;
  padding: 11px 13px;
  font-size: 13.5px;
  margin-bottom: 22px;
  line-height: 1.5;
}

.alert-err svg {
  width: 15px; height: 15px;
  flex-shrink: 0; margin-top: 2px;
}

/* Fields */
.field {
  margin-bottom: 16px;
}

.field label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 7px;
  letter-spacing: .01em;
}

.field-wrap {
  position: relative;
}

.field-icon {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  color: var(--light);
  display: flex; align-items: center;
  pointer-events: none;
}

.field-icon svg { width: 16px; height: 16px; }

.fc {
  width: 100%;
  padding: 11px 13px 11px 40px;
  border: 1.5px solid var(--n200);
  border-radius: 10px;
  background: var(--n100);
  font-family: var(--font);
  font-size: 14px;
  color: var(--dark);
  outline: none;
  transition: border-color .18s, background .18s, box-shadow .18s;
}

.fc::placeholder { color: var(--light); }

.fc:focus {
  border-color: var(--green);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(22,163,74,.1);
}

/* Submit button */
.btn-login {
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  border: none;
  background: var(--navy);
  color: #fff;
  font-family: var(--font);
  font-size: 14.5px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background .2s, transform .15s, box-shadow .2s;
  box-shadow: 0 2px 12px rgba(22,163,74,.3);
  letter-spacing: .01em;
}

.btn-login:hover {
  background: #0f6030;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(22,163,74,.28);
}

.btn-login:active { transform: translateY(0); }

.btn-login .arrow {
  transition: transform .2s;
  display: inline-flex; align-items: center;
}

.btn-login:hover .arrow { transform: translateX(3px); }

/* Role pills */
.roles {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid var(--n200);
}

.role-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 11px;
  border-radius: 999px;
  border: 1.5px solid var(--n200);
  font-size: 11.5px;
  font-weight: 600;
  color: var(--mid);
  background: var(--n100);
}

.role-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--green);
  opacity: .6;
}

.form-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 11.5px;
  color: var(--light);
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 500;
  color: var(--mid);
  text-decoration: none;
  margin-bottom: 36px;
  transition: color .15s;
}

.back-link:hover { color: var(--dark); }

.back-link svg { width: 14px; height: 14px; }

/* ── Animations ── */
@keyframes fadeUp {
  to { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ── */
@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; }
  .panel-left { display: none; }
  .panel-right { padding: 40px 28px; }
}
</style>
</head>
<body>

<div class="layout">

  <!-- ── LEFT PANEL ── -->
  <div class="panel-left">

    <div class="left-nav">
      <div class="left-seal">
        <img src="assets/seal.png" alt="DepEd"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <svg style="display:none;width:22px;height:22px;" viewBox="0 0 24 24" fill="none"
             stroke="rgba(255,255,255,.7)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M2 17l10 5 10-5"/>
          <path d="M2 12l10 5 10-5"/>
        </svg>
      </div>
      <div class="left-seal-label">
        Republic of the Philippines<br>Department of Education
      </div>
    </div>

    <div class="left-body">
      <span class="eyebrow"><?= e(SITE_NAME) ?></span>
      <h1 class="headline">
        Governance for<br><em>Quality Education</em>
      </h1>
      <p class="sub">
        Digital platform for SBM self-assessment, monitoring, and governance aligned with DepEd Order No. 007, s. 2024.
      </p>

      <div class="left-stats">
        <div class="stat-item">
          <div class="stat-num">42</div>
          <div class="stat-lbl">Indicators</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">6</div>
          <div class="stat-lbl">Dimensions</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">4</div>
          <div class="stat-lbl">Maturity Levels</div>
        </div>
      </div>
    </div>

  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="panel-right">
    <div class="form-wrap">

      <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Back to home
      </a>

      <div class="form-eyebrow">
        <span class="form-dot"></span>
        <span class="form-eyebrow-text">Secure Portal Access</span>
      </div>

      <h2 class="form-title">Welcome back</h2>
      <p class="form-sub">Sign in with your DepEd credentials to continue.</p>

      <?php if ($error): ?>
      <div class="alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="field">
          <label>Username or Email</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </span>
            <input class="fc" type="text" name="username"
                   value="<?= e($_POST['username'] ?? '') ?>"
                   placeholder="Enter username or email"
                   required autofocus>
          </div>
        </div>

        <div class="field">
          <label>Password</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input class="fc" type="password" name="password"
                   placeholder="Enter your password" required>
          </div>
        </div>

        <button class="btn-login" type="submit">
          Sign In
          <span class="arrow">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3 8H13M13 8L9 4M13 8L9 12"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </button>
      </form>

      <div class="roles">
        <span class="role-pill"><span class="role-dot"></span>Admin</span>
        <span class="role-pill"><span class="role-dot"></span>School Head</span>
        <span class="role-pill"><span class="role-dot"></span>Teacher</span>
        <span class="role-pill"><span class="role-dot"></span>SDO / RO</span>
      </div>

      <p class="form-footer">
        <?= e(SITE_NAME) ?> &nbsp;·&nbsp; DepEd Order No. 007, s. 2024 &nbsp;·&nbsp; <?= date('Y') ?>
      </p>

    </div>
  </div>

</div>

</body>
</html>