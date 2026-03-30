<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email_service.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . roleHome($_SESSION['role'])); exit;
}

$error   = '';
$success = false;

// ── Rate limiting: max 3 requests per email per 15 min ──────────────────────
function isRateLimited(PDO $db, string $email): bool {
    $st = $db->prepare(
        "SELECT COUNT(*) FROM password_setup_tokens
         WHERE user_id = (SELECT user_id FROM users WHERE email = ? LIMIT 1)
           AND type = 'reset'
           AND created_at > NOW() - INTERVAL 15 MINUTE"
    );
    $st->execute([$email]);
    return (int)$st->fetchColumn() >= 3;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT user_id, full_name, email, status
             FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show success to prevent email enumeration
        if ($user && $user['status'] === 'active') {
            if (isRateLimited($db, $email)) {
                // Still show success — don't reveal rate limiting to potential attackers
                $success = true;
            } else {
                try {
    $sent = sendPasswordResetEmail($db, $user);
} catch (\Exception $e) {
    error_log('SBM: Password reset token error: ' . $e->getMessage());
    $sent = false;
}
                // Log failure silently; don't expose to user
                if (!$sent) {
                    error_log('SBM: Password reset email failed for user_id=' . $user['user_id']);
                }
                $success = true;
            }
        } else {
            // Non-existent / inactive account — still show success (prevent enumeration)
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
<title>Forgot Password — <?= e(SITE_NAME) ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">

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
  font-family: var(--font);
  background: var(--white);
  color: var(--dark);
  -webkit-font-smoothing: antialiased;
}

/* ── Layout ── */
.layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
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
}

.form-wrap {
  width: 100%;
  max-width: 380px;
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
        Account <em>Recovery</em><br>Made Simple
      </h1>
      <p class="sub">
        Regain secure access to the DIHS SBM Portal in just a few steps. Your reset link expires in 30 minutes for security.
      </p>

      <div class="left-steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-text"><strong>Enter your email</strong><br>The one linked to your portal account.</div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-text"><strong>Check your inbox</strong><br>A secure reset link will be sent to you.</div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-text"><strong>Set a new password</strong><br>Use the link within 30 minutes to reset.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="panel-right">
    <div class="form-wrap">

      <a href="login.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Back to sign in
      </a>

      <?php if ($success): ?>

        <!-- ── Success state ── -->
        <div class="success-card">
          <div class="success-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <h3>Check Your Inbox</h3>
          <p>
            If an active account exists for that email address, we've sent a password reset link. It should arrive within a minute or two.
          </p>
          <div class="notice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            The reset link expires in <strong style="margin:0 3px;">30 minutes</strong> and can only be used once.
          </div>
          <a href="login.php" class="btn-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
            Return to sign in
          </a>
        </div>

      <?php else: ?>

        <!-- ── Form state ── -->
        <div class="form-eyebrow">
          <span class="form-dot"></span>
          <span class="form-eyebrow-text">Password Recovery</span>
        </div>

        <h2 class="form-title">Forgot your<br>password?</h2>
        <p class="form-sub">No worries — enter your account email and we'll send you a secure reset link.</p>

        <?php if ($error): ?>
        <div class="alert-err">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="post" id="forgotForm">
          <?php /* CSRF token reuses your existing helper */ ?>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="field">
            <label for="email">Email address</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
              </span>
              <input class="fc" type="email" id="email" name="email"
                     value="<?= e($_POST['email'] ?? '') ?>"
                     placeholder="your.email@dihs.edu.ph"
                     required autofocus autocomplete="email">
            </div>
          </div>

          <button class="btn-primary" type="submit" id="submitBtn">
            <span id="btnText">Send Reset Link</span>
            <span id="btnIcon">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
          </button>
        </form>

        <div style="text-align:center;margin-top:22px;font-size:12px;color:var(--light);">
          Remember your password?
          <a href="login.php" style="color:var(--green);font-weight:600;text-decoration:none;">Sign in</a>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<script>
document.getElementById('forgotForm')?.addEventListener('submit', function() {
  const btn  = document.getElementById('submitBtn');
  const text = document.getElementById('btnText');
  const icon = document.getElementById('btnIcon');
  btn.classList.add('loading');
  text.textContent = 'Sending…';
  icon.innerHTML   = '<div class="spinner"></div>';
});
</script>
</body>
</html>