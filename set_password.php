<?php
ob_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, destroy session so the link can be used cleanly
// This MUST happen before any CSRF checks or tokens are generated.
// FIX: Only do this on GET to avoid destroying the CSRF token during a POST submission.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SESSION['user_id'])) {
  session_regenerate_id(true);
  session_unset();
  session_destroy();
  session_start();
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();
$token = trim($_GET['token'] ?? '');
$mode = ($_GET['mode'] ?? 'setup') === 'reset' ? 'reset' : 'setup';

$error = '';
$success = false;
$tokenRow = null;

function setPasswordCsrfToken(): string
{
  if (empty($_SESSION['set_password_csrf'])) {
    $_SESSION['set_password_csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['set_password_csrf'];
}

function verifySetPasswordCsrf(): void
{
  if (!hash_equals($_SESSION['set_password_csrf'] ?? '', $_POST['csrf_token'] ?? '')) {
    ob_clean();
    http_response_code(403);
    echo '<p style="font-family:sans-serif;padding:40px;text-align:center;">Invalid CSRF token. <a href="javascript:history.back()">Go back</a> and try again.</p>';
    exit;
  }
}

// ── Validate token ─────────────────────────────────────────────────────────
// FIX: Use NOW() (not UTC_TIMESTAMP()) because expires_at is now stored using
// MySQL's DATE_ADD(NOW(), ...) — so both sides are in the same DB timezone.
if ($token) {
  $st = $db->prepare(
    "SELECT pst.*, u.email, u.full_name, u.user_id AS uid
         FROM password_setup_tokens pst
         JOIN users u ON pst.user_id = u.user_id
         WHERE pst.token      = ?
           AND pst.used_at    IS NULL
           AND pst.expires_at > NOW()
         LIMIT 1"
  );
  $st->execute([$token]);
  $tokenRow = $st->fetch();
}

if (!$token || !$tokenRow) {
  $error = ($mode === 'reset')
    ? 'This reset link is invalid or has expired. Links are valid for 30 minutes and can only be used once.'
    : 'This invitation link is invalid or has expired. Please contact your School Administrator for a new one.';
}

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow) {
  verifySetPasswordCsrf();

  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Activate account, set password, clear force_password_change flag
    // Only activate the account if this is an invite/setup flow.
// A password-reset should NEVER re-activate a deactivated account.
    if ($mode === 'setup') {
      $db->prepare(
        "UPDATE users
                 SET password              = ?,
                     status                = 'active',
                     force_password_change = 0
                 WHERE user_id = ?"
      )->execute([$hash, $tokenRow['user_id']]);
    } else {
      // reset mode: only update the password; leave status and other flags untouched
      $db->prepare(
        "UPDATE users
                 SET password              = ?,
                     force_password_change = 0
                 WHERE user_id = ?"
      )->execute([$hash, $tokenRow['user_id']]);
    }

    // Mark token as used so it can't be reused
    $db->prepare(
      "UPDATE password_setup_tokens SET used_at = NOW() WHERE token = ?"
    )->execute([$token]);

    $actionLabel = ($mode === 'reset') ? 'password_reset' : 'password_set';
    logActivity(
      $actionLabel,
      'auth',
      ($mode === 'reset') ? 'User reset password via link' : 'User set password via invite link'
    );

    $success = true;
    unset($_SESSION['set_password_csrf']);
  }
}

// ── Handle resend request ──────────────────────────────────────────────────
$resent = isset($_GET['resent']) && $_GET['resent'] === '1';
$resentError = '';
if (isset($_GET['resend']) && $token) {
  // Verify CSRF token for resend action
  if (!hash_equals($_SESSION['set_password_csrf'] ?? '', $_GET['csrf_token'] ?? '')) {
    $resentError = 'Invalid request. Please refresh the page and try again.';
  } else {
    require_once __DIR__ . '/includes/email_service.php';
    // Look up user from the old token (even if expired)
    $st = $db->prepare(
      "SELECT u.user_id, u.email, u.full_name, u.status
         FROM password_setup_tokens pst
         JOIN users u ON pst.user_id = u.user_id
         WHERE pst.token = ?
         LIMIT 1"
    );
    $st->execute([$token]);
    $oldUser = $st->fetch();

    if ($oldUser) {
      // BUG-14: Rate-limit resends — allow max 1 per 2 minutes per user
      $rateSt = $db->prepare(
        "SELECT created_at FROM password_setup_tokens
          WHERE user_id = ? ORDER BY created_at DESC LIMIT 1"
      );
      $rateSt->execute([$oldUser['user_id']]);
      $lastTokenTime = $rateSt->fetchColumn();
      if ($lastTokenTime && (time() - strtotime($lastTokenTime)) < 120) {
        $resentError = 'Please wait a moment before requesting another email (max 1 every 2 minutes).';
      } else {
        $sent = false;
        if ($mode === 'reset') {
          $sent = sendPasswordResetEmail($db, $oldUser);
        } else {
          // Setup mode: only resend if they are actually inactive
          if ($oldUser['status'] === 'active') {
            $resentError = 'This account is already active. Please sign in or reset your password.';
          } else {
            $sent = sendAccountCreationEmail($db, $oldUser);
          }
        }

        if ($sent) {
          header('Location: ' . baseUrl() . '/set_password.php?mode=' . $mode . '&resent=1');
          exit;
        }

        if (!$resentError) {
          $resentError = 'Failed to send it. Please try again or contact your administrator.';
        }
      }
    } else {
      $resentError = 'Could not identify the account for this link.';
    }
  }
}

// ── Page copy ──────────────────────────────────────────────────────────────
$pageTitle = $mode === 'reset' ? 'Reset Password' : 'Set Your Password';
$cardHeading = $mode === 'reset' ? 'Reset Password' : 'Set Your Password';
$cardSubtitle = $mode === 'reset'
  ? 'Enter and confirm your new password below.'
  : 'Create a secure password to activate your account.';
$successTitle = $mode === 'reset' ? 'Password Reset!' : 'Password Set Successfully';
$successMsg = $mode === 'reset'
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
  <link
    href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap"
    rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --navy: #14532D;
      --green: #16A34A;
      --g50: #F0FDF4;
      --g100: #DCFCE7;
      --g200: #BBF7D0;
      --g300: #86EFAC;
      --g700: #15803D;
      --dark: #0D1117;
      --mid: #4B5563;
      --light: #9CA3AF;
      --n200: #E5E7EB;
      --n100: #F3F4F6;
      --red: #DC2626;
      --redb: #FEE2E2;
      --font: 'DM Sans', sans-serif;
      --serif: 'Instrument Serif', Georgia, serif;
    }

    html,
    body {
      height: 100%;
      font-family: var(--font);
      background: #FAFDFB;
      color: var(--dark);
      -webkit-font-smoothing: antialiased;
      overflow: hidden;
    }

    .layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      height: 100vh;
      overflow: hidden;
    }

    /* Left panel */
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
      position: absolute;
      inset: 0;
      background-image: radial-gradient(ellipse at 10% 15%, rgba(22, 163, 74, .22) 0%, transparent 50%), radial-gradient(ellipse at 90% 85%, rgba(22, 163, 74, .14) 0%, transparent 45%);
      pointer-events: none;
    }

    .panel-left::after {
      content: '';
      position: absolute;
      inset: 0;
      background-image: linear-gradient(rgba(255, 255, 255, .025) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .025) 1px, transparent 1px);
      background-size: 48px 48px;
      pointer-events: none;
    }

    .left-body {
      position: relative;
      z-index: 1;
      margin: auto 0;
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

    .eyebrow::before,
    .eyebrow::after {
      content: '';
      display: block;
      width: 20px;
      height: 1px;
      background: var(--g300);
      opacity: .5;
    }

    .headline {
      font-family: var(--serif);
      font-size: clamp(38px, 4vw, 52px);
      font-weight: 400;
      color: #fff;
      line-height: 1.08;
      letter-spacing: -1.5px;
      margin-bottom: 18px;
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
      color: rgba(255, 255, 255, .45);
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
      border-top: 1px solid rgba(255, 255, 255, .08);
      padding-top: 28px;
      position: relative;
      z-index: 1;
      opacity: 0;
      animation: fadeUp .6s .55s ease forwards;
    }

    .step {
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }

    .step-num {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: rgba(22, 163, 74, .15);
      border: 1px solid rgba(22, 163, 74, .3);
      color: var(--g300);
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .step-text {
      font-size: 13px;
      color: rgba(255, 255, 255, .5);
      line-height: 1.6;
    }

    .step-text strong {
      color: rgba(255, 255, 255, .75);
      font-weight: 500;
    }

    /* Right panel */
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
      transition: all .2s;
      border: 1px solid transparent;
    }

    .back-link:hover {
      color: var(--green);
      background: rgba(22, 163, 74, .05);
      border-color: rgba(22, 163, 74, .1);
      transform: translateX(-2px);
    }

    .back-link svg {
      width: 15px;
      height: 15px;
    }

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

    .mode-badge.reset {
      background: #EFF6FF;
      border: 1px solid #BFDBFE;
      color: #1D4ED8;
    }

    .mode-badge.setup {
      background: var(--g50);
      border: 1px solid var(--g200);
      color: var(--g700);
    }

    .section-title {
      font-family: var(--serif);
      font-size: 32px;
      font-weight: 400;
      color: var(--dark);
      letter-spacing: -.5px;
      line-height: 1.1;
      margin-bottom: 6px;
    }

    .section-subtitle {
      font-size: 14px;
      font-weight: 300;
      color: var(--mid);
      margin-bottom: 28px;
      line-height: 1.6;
    }

    .user-pill {
      display: flex;
      align-items: center;
      gap: 12px;
      background: var(--g50);
      border: 1px solid var(--g200);
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 22px;
    }

    .user-avatar {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: linear-gradient(135deg, #16A34A, #166534);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .user-info .name {
      font-size: 14px;
      font-weight: 600;
      color: var(--dark);
      line-height: 1.3;
    }

    .user-info .label {
      font-size: 11.5px;
      color: var(--light);
      font-weight: 500;
    }

    .alert {
      border-radius: 10px;
      padding: 11px 13px;
      font-size: 13.5px;
      margin-bottom: 18px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      line-height: 1.55;
    }

    .alert svg {
      flex-shrink: 0;
      margin-top: 1px;
    }

    .alert-error {
      background: var(--redb);
      border: 1px solid #FECACA;
      color: var(--red);
    }

    .form-group {
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 6px;
      letter-spacing: .01em;
    }

    .input-wrap {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--light);
      pointer-events: none;
      display: flex;
      align-items: center;
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
      box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    input[type="password"]:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
    }

    .hint {
      font-size: 11.5px;
      color: var(--light);
      margin-top: 5px;
    }

    .strength-wrap {
      height: 4px;
      background: var(--n100);
      border-radius: 4px;
      margin-top: 8px;
      overflow: hidden;
    }

    .strength-bar {
      height: 100%;
      border-radius: 4px;
      width: 0%;
      transition: width .3s, background-color .3s;
    }

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

    .req-item.met {
      color: var(--green);
    }

    .req-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--n200);
      flex-shrink: 0;
      transition: background .2s;
    }

    .req-item.met .req-dot {
      background: var(--green);
    }

    .btn-primary {
      width: 100%;
      padding: 13px;
      border-radius: 10px;
      border: none;
      background: linear-gradient(135deg, #15803D 0%, #166534 100%);
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
      box-shadow: 0 2px 8px rgba(21, 128, 61, .35);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #166534 0%, #14532D 100%);
      transform: translateY(-1px);
    }

    .btn-primary:disabled {
      opacity: .5;
      cursor: not-allowed;
      transform: none;
    }

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
      transition: all .2s;
      box-shadow: 0 2px 8px rgba(21, 128, 61, .35);
    }

    .btn-login:hover {
      background: linear-gradient(135deg, #166534 0%, #14532D 100%);
      transform: translateY(-1px);
    }

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
      transition: all .2s;
    }

    .btn-outline:hover {
      background: var(--g50);
      transform: translateY(-1px);
    }

    .success-block,
    .expired-block {
      text-align: center;
      padding: 10px 0 8px;
      width: 100%;
    }

    .success-icon-wrap {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      background: var(--g100);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      border: 3px solid var(--g200);
    }

    .expired-icon-wrap {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      background: #FFF7ED;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      border: 3px solid #FED7AA;
    }

    .success-block h3,
    .expired-block h3 {
      font-family: var(--serif);
      font-size: 22px;
      font-weight: 400;
      margin-bottom: 8px;
      letter-spacing: -.3px;
    }

    .success-block h3 {
      color: var(--navy);
    }

    .expired-block h3 {
      color: #92400E;
    }

    .success-block p,
    .expired-block p {
      font-size: 13.5px;
      color: var(--mid);
      line-height: 1.7;
      margin-bottom: 22px;
    }

    @keyframes fadeUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 900px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .panel-left {
        display: none;
      }

      .panel-right {
        padding: 40px 28px;
      }
    }
  </style>
</head>

<body>
  <div class="layout">

    <!-- Left Panel -->
    <div class="panel-left">
      <div
        style="position:absolute;bottom:-60px;right:-60px;z-index:0;pointer-events:none;opacity:.04;filter:grayscale(1);">
        <img src="assets/seal.png" alt="" style="width:380px;height:380px;object-fit:contain;">
      </div>
      <div class="left-body">
        <span class="eyebrow"><?= e(SITE_NAME) ?></span>
        <h1 class="headline">
          <?= $mode === 'reset' ? 'Secure <em>Password</em><br>Reset' : 'Activate <em>Your</em><br>Account' ?>
        </h1>
        <p class="sub">
          <?= $mode === 'reset'
            ? 'Create a new secure password for your DIHS SBM Portal account. Your link expires in 30 minutes.'
            : 'Set a password to complete your account setup and gain access to the DIHS SBM Portal.' ?>
        </p>
        <div class="left-steps">
          <div class="step">
            <div class="step-num">1</div>
            <div class="step-text"><strong>Enter a new password</strong><br>Choose something strong and unique.</div>
          </div>
          <div class="step">
            <div class="step-num">2</div>
            <div class="step-text"><strong>Confirm your password</strong><br>Re-enter to make sure it's correct.</div>
          </div>
          <div class="step">
            <div class="step-num">3</div>
            <div class="step-text"><strong>Sign in</strong><br>Use your credentials to access the portal.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel -->
    <div class="panel-right">
      <div class="form-wrap">

        <a href="<?= baseUrl() ?>/login.php" class="back-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6" />
          </svg>
          Back to sign in
        </a>

        <?php if ($success): ?>
          <!-- ── Success ── -->
          <div class="success-block">
            <div class="success-icon-wrap">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" fill="#16A34A" opacity=".15" />
                <path d="M8 12L11 15L16 9" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </div>
            <h3><?= e($successTitle) ?></h3>
            <p><?= e($successMsg) ?></p>
            <a href="<?= baseUrl() ?>/login.php?session_cleared=1" class="btn-login">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" />
                <polyline points="10 17 15 12 10 7" />
                <line x1="15" y1="12" x2="3" y2="12" />
              </svg>
              Sign In Now
            </a>
          </div>

        <?php elseif ($error && !$tokenRow): ?>
          <!-- ── Expired / Invalid ── -->
          <div class="expired-block">
            <div class="expired-icon-wrap">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" fill="#F97316" opacity=".15" />
                <path d="M12 8V12" stroke="#F97316" stroke-width="2.2" stroke-linecap="round" />
                <circle cx="12" cy="16" r="1.2" fill="#F97316" />
              </svg>
            </div>
            <h3><?= $mode === 'reset' ? 'Link Expired or Invalid' : 'Invitation Expired' ?></h3>

            <?php if ($resentError): ?>
              <div class="alert alert-error" style="text-align:left;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <line x1="12" y1="8" x2="12" y2="12" />
                  <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <?= e($resentError) ?>
              </div>
            <?php else: ?>
              <p><?= e($error) ?></p>
            <?php endif; ?>

            <?php if ($resent): ?>
              <div
                style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px 18px;text-align:center;">
                <p style="color:#15803D;font-size:13.5px;font-weight:600;margin-bottom:2px;">
                  <?= $mode === 'reset' ? 'New reset link sent!' : 'New invitation sent!' ?>
                </p>
                <p style="color:#6B7280;font-size:12.5px;">Check your email inbox for the new link.</p>
              </div>
            <?php else: ?>
              <?php if ($mode === 'reset'): ?>
                <a href="<?= baseUrl() ?>/forgot_password.php" class="btn-outline">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10" />
                    <path d="M3.51 15a9 9 0 1 0 .49-3.45" />
                  </svg>
                  Request a New Reset Link
                </a>
              <?php else: ?>
                <?php if ($token): ?>
                  <a href="<?= baseUrl() ?>/set_password.php?token=<?= urlencode($token) ?>&mode=setup&resend=1&csrf_token=<?= setPasswordCsrfToken() ?>"
                    class="btn-outline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="1 4 1 10 7 10" />
                      <path d="M3.51 15a9 9 0 1 0 .49-3.45" />
                    </svg>
                    Request a New Invitation Link
                  </a>
                <?php else: ?>
                  <p style="margin-top:12px;font-size:13px;color:var(--mid);">
                    Please contact your School Administrator for a new invitation link.
                  </p>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <!-- ── Password Form ── -->
          <div class="mode-badge <?= $mode ?>">
            <?php if ($mode === 'reset'): ?>
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10" />
                <path d="M3.51 15a9 9 0 1 0 .49-3.45" />
              </svg>
              Password Reset
            <?php else: ?>
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L3 7V12C3 16.97 6.84 21.61 12 23C17.16 21.61 21 16.97 21 12V7L12 2Z" />
              </svg>
              Account Setup
            <?php endif; ?>
          </div>

          <h2 class="section-title"><?= e($cardHeading) ?></h2>
          <p class="section-subtitle"><?= e($cardSubtitle) ?></p>

          <div class="user-pill">
            <div class="user-avatar">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>
            <div class="user-info">
              <div class="name"><?= e($tokenRow['full_name']) ?></div>
              <div class="label"><?= e($tokenRow['email']) ?></div>
            </div>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-error">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
              <?= e($error) ?>
            </div>
          <?php endif; ?>

          <form method="post" id="pwForm">
            <input type="hidden" name="csrf_token" value="<?= setPasswordCsrfToken() ?>">

            <div class="form-group">
              <label for="password">New Password</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0110 0v4" />
                  </svg>
                </span>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                  placeholder="Min. 8 characters" oninput="checkPassword(this.value)">
              </div>
              <div class="strength-wrap">
                <div class="strength-bar" id="strengthBar"></div>
              </div>
              <p class="hint" id="strengthLabel">Enter a password to see its strength.</p>
              <ul class="req-list">
                <li class="req-item" id="req-len"><span class="req-dot"></span>8+ characters</li>
                <li class="req-item" id="req-case"><span class="req-dot"></span>Upper &amp; lowercase</li>
                <li class="req-item" id="req-num"><span class="req-dot"></span>Number</li>
                <li class="req-item" id="req-sym"><span class="req-dot"></span>Symbol</li>
              </ul>
            </div>

            <div class="form-group">
              <label for="confirm">Confirm Password</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0110 0v4" />
                  </svg>
                </span>
                <input type="password" id="confirm" name="confirm" required minlength="8" autocomplete="new-password"
                  placeholder="Re-enter your password" oninput="checkMatch()">
              </div>
              <p class="hint" id="matchHint" style="display:none;"></p>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L3 7V12C3 16.97 6.84 21.61 12 23C17.16 21.61 21 16.97 21 12V7L12 2Z" />
                <path d="M9 12L11 14L15 10" />
              </svg>
              <?= $mode === 'reset' ? 'Update Password' : 'Activate Account &amp; Set Password' ?>
            </button>
          </form>
        <?php endif; ?>

        <div style="text-align:center;margin-top:22px;font-size:12px;color:var(--light);">
          <?php if ($mode === 'reset' && !$success && $tokenRow): ?>
            Need a new link? <a href="<?= baseUrl() ?>/forgot_password.php"
              style="color:var(--green);font-weight:600;text-decoration:none;">Request one</a>
          <?php else: ?>
            <a href="<?= baseUrl() ?>/login.php" style="color:var(--green);font-weight:600;text-decoration:none;">Back to
              sign in</a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <script>
    function checkPassword(val) {
      const bar = document.getElementById('strengthBar');
      const label = document.getElementById('strengthLabel');
      const reqs = {
        len: val.length >= 8,
        case: /[A-Z]/.test(val) && /[a-z]/.test(val),
        num: /\d/.test(val),
        sym: /[^A-Za-z0-9]/.test(val),
      };
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
        { w: '20%', color: '#EF4444', text: 'Too weak' },
        { w: '40%', color: '#F97316', text: 'Weak' },
        { w: '65%', color: '#EAB308', text: 'Fair' },
        { w: '85%', color: '#22C55E', text: 'Strong' },
        { w: '100%', color: '#16A34A', text: 'Very strong' },
      ];
      const lvl = levels[score - 1] || levels[0];
      bar.style.width = lvl.w;
      bar.style.backgroundColor = lvl.color;
      label.textContent = lvl.text;
      label.style.color = lvl.color;
      checkMatch();
    }

    function checkMatch() {
      const pw = document.getElementById('password').value;
      const conf = document.getElementById('confirm').value;
      const hint = document.getElementById('matchHint');
      const btn = document.getElementById('submitBtn');
      if (!conf) { hint.style.display = 'none'; btn.disabled = false; return; }
      const ok = pw === conf;
      hint.style.display = 'block';
      hint.textContent = ok ? '✓ Passwords match' : '✗ Passwords do not match';
      hint.style.color = ok ? '#16A34A' : '#DC2626';
      btn.disabled = !ok;
    }
  </script>
  <?php if (function_exists('renderPasswordToggle'))
    renderPasswordToggle(); ?>
</body>

</html>