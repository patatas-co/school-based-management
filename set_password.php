<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$db    = getDB();
$token = trim($_GET['token'] ?? '');
$mode  = ($_GET['mode'] ?? 'setup') === 'reset' ? 'reset' : 'setup';   // 'setup' or 'reset'

$error    = '';
$success  = false;
$tokenRow = null;

// ── Validate token ────────────────────────────────────────────────────────
if ($token) {
    $st = $db->prepare(
        "SELECT pst.*, u.email, u.full_name, u.user_id AS uid
         FROM password_setup_tokens pst
         JOIN users u ON pst.user_id = u.user_id
         WHERE pst.token    = ?
           AND pst.type     = ?
           AND pst.used_at  IS NULL
           AND pst.expires_at > NOW()
         LIMIT 1"
    );
    $st->execute([$token, $mode]);
    $tokenRow = $st->fetch();
}

if (!$token || !$tokenRow) {
    $error = ($mode === 'reset')
        ? 'This reset link is invalid or has expired. Links are valid for 30 minutes and can only be used once.'
        : 'This invitation link is invalid or has expired. Please contact your School Administrator.';
}

// ── Handle form submission ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow) {
    verifyCsrf();   // uses your existing CSRF helper

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Update password and activate account
        $db->prepare(
            "UPDATE users
             SET password = ?, status = 'active', force_password_change = 0
             WHERE user_id = ?"
        )->execute([$hash, $tokenRow['user_id']]);

        // Invalidate the used token
        $db->prepare(
            "UPDATE password_setup_tokens SET used_at = NOW() WHERE token = ?"
        )->execute([$token]);

        $actionLabel = ($mode === 'reset') ? 'password_reset' : 'password_set';
        logActivity($actionLabel, 'auth',
            ($mode === 'reset') ? 'User reset password via link' : 'User set password via invite link'
        );

        $success = true;
    }
}

// ── Handle "resend reset link" request ───────────────────────────────────
$resent      = isset($_GET['resent']) && $_GET['resent'] === '1';
$resentError = '';
if (isset($_GET['resend']) && $mode === 'reset' && $token) {
    require_once __DIR__ . '/includes/email_service.php';
    $st = $db->prepare(
        "SELECT u.user_id, u.email, u.full_name
         FROM password_setup_tokens pst
         JOIN users u ON pst.user_id = u.user_id
         WHERE pst.token = ? AND pst.type = 'reset'
         LIMIT 1"
    );
    $st->execute([$token]);
    $oldTokenRow = $st->fetch();

    if ($oldTokenRow) {
        $sent = sendPasswordResetEmail($db, $oldTokenRow);
        if ($sent) {
            // Redirect to a clean URL so the old token is no longer in the address bar
            header('Location: ' . baseUrl() . '/set_password.php?mode=reset&resent=1');
            exit;
        }
        $resentError = 'Failed to send email. Please try again later.';
    } else {
        $resentError = 'Could not identify the account for this link.';
    }
}

// ── Page copy based on mode ───────────────────────────────────────────────
$pageTitle    = $mode === 'reset' ? 'Reset Password'    : 'Set Your Password';
$cardHeading  = $mode === 'reset' ? 'Reset Password'    : 'Set Your Password';
$cardSubtitle = $mode === 'reset'
    ? 'Enter and confirm your new password below.'
    : 'Create a secure password to activate your account.';
$successTitle = $mode === 'reset' ? 'Password Reset!'             : 'Password Set Successfully';
$successMsg   = $mode === 'reset'
    ? 'Your password has been updated. You can now sign in with your new credentials.'
    : 'Your account is now active. You can sign in to the DIHS SBM Portal.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
  <title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
  <style>
    <style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:  #14532D;
  --green: #16A34A;
  --g50:   #F0FDF4;
  --g100:  #DCFCE7;
  --g200:  #BBF7D0;
  --g300:  #86EFAC;
  --g700:  #15803D;
  --dark:  #0D1117;
  --mid:   #4B5563;
  --light: #9CA3AF;
  --n200:  #E5E7EB;
  --n100:  #F3F4F6;
  --red:   #DC2626;
  --redb:  #FEE2E2;
  --white: #FFFFFF;
  --font:  'DM Sans', sans-serif;
  --serif: 'Instrument Serif', Georgia, serif;
}

html, body {
  height: 100%;
  margin: 0;
  overflow: hidden;
  font-family: var(--font);
  background: var(--white);
  color: var(--dark);
  -webkit-font-smoothing: antialiased;
}
body::before {
  content: '';
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg,#166534 0%,#22C55E 40%,#FFD700 70%,#CE1126 100%);
  z-index: 999;
}

/* ── Layout ── */
.layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  height: 100vh;
  overflow: hidden;
}

/* ── Left panel — mirrors login.php ── */
.panel-left {
  position: relative;
  background: #0A0F0A;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: 56px 64px;
}

.panel-left::before {
  content: '';
  position: absolute; inset: 0;
  background-image:
    radial-gradient(ellipse at 10% 15%, rgba(22,163,74,.22) 0%, transparent 50%),
    radial-gradient(ellipse at 90% 85%, rgba(22,163,74,.14) 0%, transparent 45%);
  pointer-events: none;
}

.panel-left::after {
  content: '';
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);
  background-size: 48px 48px;
  pointer-events: none;
}

.left-body {
  position: relative;
  z-index: 1;
  margin-top: auto;
  margin-bottom: auto;
  padding-top: 40px;
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
  font-size: clamp(38px,4vw,56px);
  font-weight: 400;
  color: #fff;
  line-height: 1.08;
  letter-spacing: -1.5px;
  margin-bottom: 18px;
  opacity: 0;
  animation: fadeUp .7s .35s ease forwards;
}

.headline em { font-style: italic; color: var(--g300); }

.sub {
  font-size: 15px;
  font-weight: 300;
  color: rgba(255,255,255,.45);
  line-height: 1.75;
  max-width: 340px;
  opacity: 0;
  animation: fadeUp .7s .45s ease forwards;
}

.left-steps {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 44px;
  border-top: 1px solid rgba(255,255,255,.08);
  padding-top: 28px;
  position: relative; z-index: 1;
  opacity: 0;
  animation: fadeUp .6s .55s ease forwards;
}

.step {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.step-num {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: rgba(22,163,74,.15);
  border: 1px solid rgba(22,163,74,.3);
  color: var(--g300);
  font-size: 11px;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.step-text {
  font-size: 13px;
  color: rgba(255,255,255,.5);
  line-height: 1.6;
}

.step-text strong { color: rgba(255,255,255,.75); font-weight: 500; }

/* ── Right panel ── */
.panel-right {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 56px 64px;
  background: #FAFDFB;
  position: relative;
  overflow-y: auto;
}

.form-wrap {
  width: 100%;
  max-width: 420px;
  opacity: 0;
  animation: fadeUp .7s .3s ease forwards;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 600;
  color: var(--mid);
  text-decoration: none;
  margin-bottom: 32px;
  padding: 6px 12px 6px 0;
  border-radius: 8px;
  transition: all .2s ease;
  border: 1px solid transparent;
}

.back-link:hover {
  color: var(--green);
  background: rgba(22,163,74,.05);
  border-color: rgba(22,163,74,.1);
  transform: translateX(-2px);
}

.back-link svg { width: 15px; height: 15px; transition: transform .2s; }
.back-link:hover svg { transform: translateX(-2px); }

.form-eyebrow {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 28px;
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
  background: rgba(22,163,74,.08);
  padding: 3px 8px;
  border-radius: 999px;
  border: 1px solid rgba(22,163,74,.2);
}

.form-title {
  font-family: var(--serif);
  font-size: 34px;
  font-weight: 400;
  color: var(--dark);
  letter-spacing: -.5px;
  line-height: 1.1;
  margin-bottom: 8px;
}

.form-sub {
  font-size: 14px;
  font-weight: 300;
  color: var(--mid);
  margin-bottom: 32px;
  line-height: 1.6;
}

/* Alert */
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
  margin-bottom: 20px;
  line-height: 1.5;
}

.alert-err svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 2px; }

/* Success state */
.success-card {
  background: var(--g50);
  border: 1px solid var(--g200);
  border-radius: 14px;
  padding: 30px 28px;
  text-align: center;
}

.success-icon {
  width: 60px; height: 60px;
  border-radius: 50%;
  background: var(--g100);
  border: 2px solid var(--g200);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 18px;
}

.success-card h3 {
  font-family: var(--serif);
  font-size: 24px;
  font-weight: 400;
  color: var(--navy);
  margin-bottom: 10px;
  letter-spacing: -.3px;
}

.success-card p {
  font-size: 13.5px;
  color: var(--mid);
  line-height: 1.7;
  margin-bottom: 22px;
}

.success-card .notice {
  background: rgba(22,163,74,.07);
  border: 1px solid rgba(22,163,74,.15);
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 12px;
  color: var(--navy);
  display: flex;
  align-items: center;
  gap: 8px;
  text-align: left;
  margin-bottom: 22px;
}

/* Field */
.field { margin-bottom: 18px; }

.field label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 7px;
  letter-spacing: .01em;
}

.field-wrap { position: relative; }

.field-icon {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  color: var(--light);
  display: flex; align-items: center;
  pointer-events: none;
}

.fc {
  width: 100%;
  padding: 12px 13px 12px 42px;
  border: 1.5px solid var(--n200);
  border-radius: 10px;
  background: #fff;
  font-family: var(--font);
  font-size: 14px;
  color: var(--dark);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}

.fc:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 3px rgba(22,163,74,.12),0 1px 2px rgba(0,0,0,.04);
}

.fc::placeholder { color: var(--light); }

/* Button */
.btn-primary {
  width: 100%;
  padding: 13px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg,#15803D 0%,#166534 100%);
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
  transition: all .2s ease;
  box-shadow: 0 2px 8px rgba(21,128,61,.35);
  letter-spacing: .01em;
  position: relative;
  overflow: hidden;
}

.btn-primary::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg,rgba(255,255,255,.1) 0%,transparent 60%);
  pointer-events: none;
}

.btn-primary:hover {
  background: linear-gradient(135deg,#166534 0%,#14532D 100%);
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(21,128,61,.3);
}

.btn-primary:active { transform: translateY(0); }

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: 100%;
  padding: 11px;
  border-radius: 10px;
  border: 1.5px solid var(--n200);
  background: #fff;
  color: var(--mid);
  font-family: var(--font);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all .2s;
  margin-top: 10px;
}

.btn-secondary:hover {
  border-color: var(--green);
  color: var(--green);
  background: var(--g50);
}

/* Loading state */
.btn-primary.loading { opacity: .7; pointer-events: none; }

.spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}

/* ── set_password extras ── */
.form-group { margin-bottom: 18px; }

label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 6px;
  letter-spacing: .01em;
}

.input-wrap { position: relative; }

.input-icon {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  color: var(--light);
  pointer-events: none;
  display: flex; align-items: center;
}

input[type="password"] {
  width: 100%;
  padding: 12px 13px 12px 42px;
  border: 1.5px solid var(--n200);
  border-radius: 10px;
  font-size: 14px;
  font-family: var(--font);
  color: var(--dark);
  background: #fff;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}

input[type="password"]:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 3px rgba(22,163,74,.12), 0 1px 2px rgba(0,0,0,.04);
}

.hint { font-size: 11.5px; color: var(--light); margin-top: 5px; }

.strength-wrap { height: 4px; background: var(--n100); border-radius: 4px; margin-top: 8px; overflow: hidden; }
.strength-bar  { height: 100%; border-radius: 4px; width: 0%; transition: width .3s, background-color .3s; }

.req-list {
  list-style: none;
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.req-item {
  font-size: 11.5px;
  color: var(--light);
  display: flex;
  align-items: center;
  gap: 4px;
  transition: color .2s;
}

.req-item.met { color: var(--green); }

.req-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--n200);
  flex-shrink: 0;
  transition: background .2s;
}

.req-item.met .req-dot { background: var(--green); }

.user-pill {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--g50);
  border: 1px solid var(--g200);
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 24px;
}

.user-avatar {
  width: 38px; height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #16A34A, #166534);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.user-info .name  { font-size: 14px; font-weight: 600; color: var(--dark); line-height: 1.3; }
.user-info .label { font-size: 11.5px; color: var(--light); font-weight: 500; }

.mode-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 999px;
  margin-bottom: 18px;
}

.mode-badge.reset { background: #EFF6FF; border: 1px solid #BFDBFE; color: #1D4ED8; }
.mode-badge.setup { background: var(--g50); border: 1px solid var(--g200); color: var(--g700); }

.alert {
  border-radius: 10px;
  padding: 11px 13px;
  font-size: 13.5px;
  margin-bottom: 20px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  line-height: 1.55;
}
.alert svg { flex-shrink: 0; margin-top: 1px; }
.alert-error   { background: var(--redb); border: 1px solid #FECACA; color: var(--red); }
.alert-expired { background: #FFF7ED; border: 1px solid #FED7AA; color: #9A3412; }

.success-block { text-align: center; padding: 10px 0 8px; width: 100%; }

.success-icon-wrap {
  width: 68px; height: 68px;
  border-radius: 50%;
  background: var(--g100);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
  border: 3px solid var(--g200);
}

.success-block h3 { font-family: var(--serif); font-size: 22px; font-weight: 400; color: var(--navy); margin-bottom: 8px; letter-spacing: -.3px; }
.success-block p  { font-size: 14px; color: var(--mid); line-height: 1.65; margin-bottom: 28px; }

.btn-login {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #15803D 0%, #166534 100%);
  color: #fff;
  text-decoration: none;
  font-size: 14.5px;
  font-weight: 600;
  padding: 12px 32px;
  border-radius: 10px;
  cursor: pointer;
  transition: background .15s, transform .15s, box-shadow .15s;
  box-shadow: 0 2px 8px rgba(21,128,61,.35);
}

.btn-login:hover {
  background: linear-gradient(135deg,#166534 0%,#14532D 100%);
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(21,128,61,.3);
}

.btn-login:active { transform: translateY(0); }

.expired-block { text-align: center; padding: 8px 0; width: 100%; }

.expired-icon-wrap {
  width: 68px; height: 68px;
  border-radius: 50%;
  background: #FFF7ED;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
  border: 3px solid #FED7AA;
}

.expired-block h3 { font-size: 18px; font-weight: 700; color: #92400E; margin-bottom: 8px; }
.expired-block p  { font-size: 13.5px; color: var(--mid); line-height: 1.65; }

.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1.5px solid var(--green);
  color: var(--green);
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  padding: 10px 28px;
  border-radius: 10px;
  margin-top: 20px;
  cursor: pointer;
  transition: background .15s, color .15s, transform .15s, box-shadow .15s;
}

.btn-outline:hover {
  background: var(--g50);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(22,163,74,.15);
}

.btn-outline:active { transform: translateY(0); }

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

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
    <div style="position:absolute;bottom:-60px;right:-60px;z-index:0;pointer-events:none;opacity:.04;filter:grayscale(1);">
      <img src="assets/seal.png" alt="" style="width:380px;height:380px;object-fit:contain;">
    </div>

    <div class="left-body">
      <span class="eyebrow"><?= e(SITE_NAME) ?></span>
      <h1 class="headline">
        <?php if ($mode === 'reset'): ?>
          Secure <em>Password</em><br>Reset
        <?php else: ?>
          Activate <em>Your</em><br>Account
        <?php endif; ?>
      </h1>
      <p class="sub">
        <?php if ($mode === 'reset'): ?>
          Create a new secure password for your DIHS SBM Portal account. Your link expires in 30 minutes.
        <?php else: ?>
          Set a password to complete your account setup and gain access to the DIHS SBM Portal.
        <?php endif; ?>
      </p>

      <div class="left-steps">
        <?php if ($mode === 'reset'): ?>
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-text"><strong>Enter a new password</strong><br>Choose something strong and memorable.</div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-text"><strong>Confirm your password</strong><br>Re-enter to make sure it's correct.</div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-text"><strong>Sign in</strong><br>Use your new credentials to access the portal.</div>
        </div>
        <?php else: ?>
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-text"><strong>Set your password</strong><br>Create a secure password for your account.</div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-text"><strong>Activate your account</strong><br>Your account goes live immediately.</div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-text"><strong>Sign in</strong><br>Access the DIHS SBM Portal with your credentials.</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="panel-right">
    <div class="form-wrap">

      <?php if ($success): ?>
      <!-- ── Success ── -->
      <div class="success-block">
        <div class="success-icon-wrap">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" fill="#16A34A" opacity=".15"/>
            <path d="M8 12L11 15L16 9" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3><?= e($successTitle) ?></h3>
        <p><?= e($successMsg) ?></p>
        <a href="<?= baseUrl() ?>/login.php" class="btn-login">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          Sign In Now
        </a>
      </div>

      <?php elseif ($error && !$tokenRow): ?>
      <!-- ── Invalid / Expired token ── -->
      <div class="expired-block">
        <div class="expired-icon-wrap">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" fill="#F97316" opacity=".15"/>
            <path d="M12 8V12" stroke="#F97316" stroke-width="2.2" stroke-linecap="round"/>
            <circle cx="12" cy="16" r="1.2" fill="#F97316"/>
          </svg>
        </div>
        <h3><?= $mode === 'reset' ? 'Link Expired or Invalid' : 'Invitation Expired' ?></h3>
        <p><?= e($error) ?></p>

        <?php if ($mode === 'reset'): ?>
          <?php if ($resent): ?>
            <div style="margin-top:20px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px 18px;text-align:center;">
              <p style="color:#15803D;font-size:13.5px;font-weight:600;margin-bottom:2px;">Reset link sent!</p>
              <p style="color:#6B7280;font-size:12.5px;">Check your email inbox for a new password reset link.</p>
            </div>
          <?php elseif ($resentError): ?>
            <p style="margin-top:14px;font-size:13px;color:#DC2626;"><?= e($resentError) ?></p>
            <a href="?token=<?= urlencode($token) ?>&mode=reset&resend=1" class="btn-outline" style="margin-top:12px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.45"/>
              </svg>
              Try Again
            </a>
          <?php else: ?>
            <a href="?token=<?= urlencode($token) ?>&mode=reset&resend=1" class="btn-outline">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.45"/>
              </svg>
              Request a New Reset Link
            </a>
          <?php endif; ?>
        <?php else: ?>
          <p style="margin-top:12px;font-size:13px;color:var(--gray-500);">Please contact your School Administrator for a new invitation.</p>
        <?php endif; ?>
      </div>

      <?php else: ?>
      <!-- ── Password form ── -->

      <!-- Mode badge -->
      <div class="mode-badge <?= $mode ?>">
        <?php if ($mode === 'reset'): ?>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 .49-3.45"/>
          </svg>
          Password Reset
        <?php else: ?>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12"/>
            <path d="M12 2a10 10 0 1 0 0 20"/>
          </svg>
          Account Setup
        <?php endif; ?>
      </div>

      <h2 class="section-title"><?= e($cardHeading) ?></h2>
      <p class="section-subtitle"><?= e($cardSubtitle) ?></p>

      <!-- User info -->
      <div class="user-pill">
        <div class="user-avatar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="user-info">
          <div class="name"><?= e($tokenRow['full_name']) ?></div>
          <div class="label"><?= e($tokenRow['email']) ?></div>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" id="pwForm">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label for="password">New Password</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </span>
            <input type="password" id="password" name="password"
                   required minlength="8"
                   autocomplete="new-password"
                   placeholder="Min. 8 characters"
                   oninput="checkPassword(this.value)">
          </div>
          <div class="strength-wrap"><div class="strength-bar" id="strengthBar"></div></div>
          <p class="hint" id="strengthLabel">Enter a password to see its strength.</p>

          <!-- Requirements -->
          <ul class="req-list" id="reqList">
            <li class="req-item" id="req-len">
              <span class="req-dot"></span>8+ characters
            </li>
            <li class="req-item" id="req-case">
              <span class="req-dot"></span>Upper &amp; lowercase
            </li>
            <li class="req-item" id="req-num">
              <span class="req-dot"></span>Number
            </li>
            <li class="req-item" id="req-sym">
              <span class="req-dot"></span>Symbol
            </li>
          </ul>
        </div>

        <div class="form-group">
          <label for="confirm">Confirm Password</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </span>
            <input type="password" id="confirm" name="confirm"
                   required minlength="8"
                   autocomplete="new-password"
                   placeholder="Re-enter your password"
                   oninput="checkMatch()">
          </div>
          <p class="hint" id="matchHint" style="display:none;"></p>
        </div>

        <button type="submit" class="btn-primary" id="submitBtn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L3 7V12C3 16.97 6.84 21.61 12 23C17.16 21.61 21 16.97 21 12V7L12 2Z"/>
            <path d="M9 12L11 14L15 10"/>
          </svg>
          <?= $mode === 'reset' ? 'Update Password' : 'Activate Account &amp; Set Password' ?>
        </button>
      </form>
      <?php endif; ?>

    <div style="text-align:center;margin-top:22px;font-size:12px;color:var(--light);">
        <?php if ($mode === 'reset' && !$success && $tokenRow): ?>
          Need a new link?
          <a href="<?= baseUrl() ?>/forgot_password.php" style="color:var(--green);font-weight:600;text-decoration:none;">Request one</a>
        <?php else: ?>
          <a href="<?= baseUrl() ?>/login.php" style="color:var(--green);font-weight:600;text-decoration:none;transition:opacity .2s;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">Back to sign in</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
function checkPassword(val) {
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');

  const reqs = {
    len:  val.length >= 8,
    case: /[A-Z]/.test(val) && /[a-z]/.test(val),
    num:  /\d/.test(val),
    sym:  /[^A-Za-z0-9]/.test(val),
  };

  // Update requirement dots
  Object.entries(reqs).forEach(([k, met]) => {
    document.getElementById('req-' + k)?.classList.toggle('met', met);
  });

  if (!val) {
    bar.style.width = '0%';
    label.textContent = 'Enter a password to see its strength.';
    label.style.color = '#6B7280';
    return;
  }

  const score = Object.values(reqs).filter(Boolean).length;

  const levels = [
    { w:'20%', color:'#EF4444', text:'Too weak' },
    { w:'40%', color:'#F97316', text:'Weak' },
    { w:'65%', color:'#EAB308', text:'Fair' },
    { w:'85%', color:'#22C55E', text:'Strong' },
    { w:'100%',color:'#16A34A', text:'Very strong' },
  ];

  const lvl = levels[score - 1] || levels[0];
  bar.style.width           = lvl.w;
  bar.style.backgroundColor = lvl.color;
  label.textContent         = lvl.text;
  label.style.color         = lvl.color;

  checkMatch();
}

function checkMatch() {
  const pw      = document.getElementById('password').value;
  const conf    = document.getElementById('confirm').value;
  const hint    = document.getElementById('matchHint');
  const btn     = document.getElementById('submitBtn');

  if (!conf) { hint.style.display = 'none'; btn.disabled = false; return; }

  const matches = pw === conf;
  hint.style.display  = 'block';
  hint.textContent    = matches ? '✓ Passwords match' : '✗ Passwords do not match';
  hint.style.color    = matches ? '#16A34A' : '#DC2626';
  btn.disabled        = !matches;
}
</script>

</body>
</html>