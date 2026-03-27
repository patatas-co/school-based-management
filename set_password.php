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
        $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

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
  <title><?= e($pageTitle) ?> — DIHS SBM Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-950: #052e16;
      --green-900: #0D5C28;
      --green-700: #15803D;
      --green-600: #16A34A;
      --green-100: #DCFCE7;
      --green-50:  #F0FDF4;
      --gray-900:  #111827;
      --gray-700:  #374151;
      --gray-500:  #6B7280;
      --gray-300:  #D1D5DB;
      --gray-100:  #F3F4F6;
      --red-600:   #DC2626;
      --red-50:    #FEF2F2;
      --red-200:   #FECACA;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      -webkit-font-smoothing: antialiased;
    }

    body::before {
      content: '';
      position: fixed; top:0; left:0; right:0; height:3px;
      background: linear-gradient(90deg,#166534 0%,#22C55E 40%,#FFD700 70%,#CE1126 100%);
      z-index: 999;
    }

    .page-bg {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
      background: linear-gradient(150deg, #052e16 0%, #0D5C28 45%, #15803D 100%);
      position: relative;
      overflow: hidden;
    }

    .page-bg::before, .page-bg::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: .07;
      pointer-events: none;
    }
    .page-bg::before { width:600px; height:600px; background:#22C55E; top:-180px; right:-180px; }
    .page-bg::after  { width:400px; height:400px; background:#22C55E; bottom:-120px; left:-120px; }

    /* ── Card ── */
    .card {
      background: #fff;
      border-radius: 18px;
      width: 100%; max-width: 460px;
      box-shadow: 0 0 0 1px rgba(0,0,0,.04), 0 8px 40px rgba(0,0,0,.22), 0 2px 8px rgba(0,0,0,.12);
      overflow: hidden;
      position: relative; z-index: 1;
    }

    .card-header {
      background: linear-gradient(150deg,#052e16 0%,#0D5C28 60%,#16A34A 100%);
      padding: 32px 36px 28px;
      text-align: center;
    }

    .logo-wrap {
      display: inline-flex;
      align-items: center; justify-content: center;
      width: 72px; height: 72px;
      background: rgba(255,255,255,.9);
      border-radius: 50%;
      margin-bottom: 16px;
      padding: 4px;
      box-shadow: 0 4px 12px rgba(0,0,0,.15);
      border: 2px solid rgba(255,255,255,.2);
    }

    .logo-wrap img { width:100%; height:100%; object-fit:contain; }

    .card-header h1 { color:#fff; font-size:17px; font-weight:700; margin-bottom:4px; }
    .card-header p  { color:rgba(255,255,255,.65); font-size:12.5px; }

    .card-body   { padding: 32px 36px 36px; }
    .card-footer {
      background: var(--gray-100);
      padding: 14px 36px;
      text-align: center;
      border-top: 1px solid #E5E7EB;
    }
    .card-footer p { font-size:11.5px; color:var(--gray-500); }
    .card-footer a { color:var(--green-700); text-decoration:none; font-weight:500; }
    .card-footer a:hover { text-decoration:underline; }

    /* ── Section head ── */
    .section-title    { font-size:18px; font-weight:700; color:var(--green-900); margin-bottom:4px; }
    .section-subtitle { font-size:13.5px; color:var(--gray-500); margin-bottom:24px; line-height:1.6; }

    /* ── Mode badge ── */
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

    .mode-badge.reset { background:#EFF6FF; border:1px solid #BFDBFE; color:#1D4ED8; }
    .mode-badge.setup { background:var(--green-50); border:1px solid #BBF7D0; color:var(--green-700); }

    /* ── User pill ── */
    .user-pill {
      display: flex;
      align-items: center;
      gap: 12px;
      background: var(--green-50);
      border: 1px solid #BBF7D0;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 24px;
    }

    .user-avatar {
      width:38px; height:38px;
      border-radius:10px;
      background: linear-gradient(135deg,#16A34A,#0D5C28);
      display:flex; align-items:center; justify-content:center;
      flex-shrink:0;
    }

    .user-info .name  { font-size:14px; font-weight:600; color:var(--green-900); line-height:1.3; }
    .user-info .label { font-size:11.5px; color:var(--gray-500); font-weight:500; }

    /* ── Alerts ── */
    .alert {
      border-radius:9px;
      padding:13px 16px;
      font-size:13.5px;
      margin-bottom:20px;
      display:flex;
      align-items:flex-start;
      gap:10px;
      line-height:1.55;
    }
    .alert svg { flex-shrink:0; margin-top:1px; }
    .alert-error   { background:var(--red-50);  border:1px solid var(--red-200);  color:var(--red-600); }
    .alert-expired { background:#FFF7ED; border:1px solid #FED7AA; color:#9A3412; }

    /* ── Form ── */
    .form-group { margin-bottom:18px; }

    label {
      display:block;
      font-size:13px;
      font-weight:600;
      color:var(--gray-700);
      margin-bottom:6px;
      letter-spacing:.1px;
    }

    .input-wrap { position:relative; }

    .input-icon {
      position:absolute;
      left:13px; top:50%;
      transform:translateY(-50%);
      color:var(--gray-300);
      pointer-events:none;
      display:flex; align-items:center;
    }

    input[type="password"] {
      width:100%;
      padding:11px 14px 11px 40px;
      border:1.5px solid var(--gray-300);
      border-radius:9px;
      font-size:14.5px;
      font-family:inherit;
      color:var(--gray-900);
      background:#fff;
      outline:none;
      transition:border-color .18s, box-shadow .18s;
    }

    input[type="password"]:focus {
      border-color:var(--green-600);
      box-shadow:0 0 0 3px rgba(22,163,74,.12);
    }

    .hint { font-size:11.5px; color:var(--gray-500); margin-top:5px; }

    /* Strength bar */
    .strength-wrap { height:4px; background:var(--gray-100); border-radius:4px; margin-top:8px; overflow:hidden; }
    .strength-bar  { height:100%; border-radius:4px; width:0%; transition:width .3s, background-color .3s; }

    /* Requirements checklist */
    .req-list {
      list-style:none;
      margin-top:10px;
      display:flex;
      flex-wrap:wrap;
      gap:6px;
    }

    .req-item {
      font-size:11.5px;
      color:var(--gray-500);
      display:flex;
      align-items:center;
      gap:4px;
      transition:color .2s;
    }

    .req-item.met { color:var(--green-600); }

    .req-dot {
      width:6px; height:6px;
      border-radius:50%;
      background:var(--gray-300);
      flex-shrink:0;
      transition:background .2s;
    }

    .req-item.met .req-dot { background:var(--green-600); }

    /* Submit button */
    .btn-primary {
      width:100%;
      padding:13px 20px;
      background:linear-gradient(135deg,var(--green-600) 0%,var(--green-700) 100%);
      color:#fff;
      border:none;
      border-radius:9px;
      font-size:15px;
      font-weight:600;
      font-family:inherit;
      cursor:pointer;
      margin-top:6px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      transition:opacity .15s, transform .15s, box-shadow .15s;
      box-shadow:0 4px 12px rgba(22,163,74,.30);
      letter-spacing:.1px;
    }

    .btn-primary:hover { opacity:.92; transform:translateY(-1px); box-shadow:0 6px 18px rgba(22,163,74,.38); }
    .btn-primary:active { transform:translateY(0); }
    .btn-primary:disabled { opacity:.55; pointer-events:none; cursor:not-allowed; }

    /* Success block */
    .success-block { text-align:center; padding:10px 0 8px; }

    .success-icon-wrap {
      width:68px; height:68px;
      border-radius:50%;
      background:var(--green-100);
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 20px;
      border:3px solid #BBF7D0;
    }

    .success-block h3 { font-size:19px; font-weight:700; color:var(--green-900); margin-bottom:8px; }
    .success-block p  { font-size:14px; color:var(--gray-500); line-height:1.65; margin-bottom:28px; }

    .btn-login {
      display:inline-flex;
      align-items:center;
      gap:8px;
      background:var(--green-900);
      color:#fff;
      text-decoration:none;
      font-size:14.5px;
      font-weight:600;
      padding:12px 32px;
      border-radius:9px;
      transition:background .15s, transform .15s;
      box-shadow:0 3px 10px rgba(13,92,40,.30);
    }

    .btn-login:hover { background:var(--green-700); transform:translateY(-1px); }

    /* Expired block */
    .expired-block { text-align:center; padding:8px 0; }

    .expired-icon-wrap {
      width:68px; height:68px;
      border-radius:50%;
      background:#FFF7ED;
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 20px;
      border:3px solid #FED7AA;
    }

    .expired-block h3 { font-size:18px; font-weight:700; color:#92400E; margin-bottom:8px; }
    .expired-block p  { font-size:13.5px; color:var(--gray-500); line-height:1.65; }

    .btn-outline {
      display:inline-flex;
      align-items:center;
      gap:8px;
      border:1.5px solid var(--green-700);
      color:var(--green-700);
      text-decoration:none;
      font-size:14px;
      font-weight:600;
      padding:10px 28px;
      border-radius:9px;
      margin-top:20px;
      transition:background .15s, color .15s;
    }

    .btn-outline:hover { background:var(--green-50); }

    @media (max-width:500px) {
      .card-body   { padding:26px 24px 28px; }
      .card-header { padding:26px 24px 22px; }
      .card-footer { padding:13px 24px; }
    }
  </style>
</head>
<body>

<div class="page-bg">
  <div class="card">

    <!-- Header -->
    <div class="card-header">
      <div class="logo-wrap">
        <img src="assets/seal.png" alt="DIHS School Seal">
      </div>
      <h1>DIHS SBM Portal</h1>
      <p>Dasmariñas Integrated High School &nbsp;·&nbsp; DepEd Cavite</p>
    </div>

    <!-- Body -->
    <div class="card-body">

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
          <a href="<?= baseUrl() ?>/forgot_password.php" class="btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="1 4 1 10 7 10"/>
              <path d="M3.51 15a9 9 0 1 0 .49-3.45"/>
            </svg>
            Request a New Reset Link
          </a>
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

    </div>

    <!-- Footer -->
    <div class="card-footer">
      <p>
        DIHS SBM Portal &mdash; Secure access
        &nbsp;·&nbsp;
        <a href="<?= baseUrl() ?>/login.php">Back to Login</a>
        <?php if ($mode === 'reset' && !$success && $tokenRow): ?>
          &nbsp;·&nbsp;
          <a href="<?= baseUrl() ?>/forgot_password.php">Request new link</a>
        <?php endif; ?>
      </p>
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