<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$db    = getDB();
$token = trim($_GET['token'] ?? '');
$error = '';
$success = false;

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

        $db->prepare("UPDATE users
                      SET password=?, status='active',
                          force_password_change=0
                      WHERE user_id=?")
           ->execute([$hash, $tokenRow['user_id']]);

        $db->prepare("UPDATE password_setup_tokens
                      SET used_at=NOW() WHERE token=?")
           ->execute([$token]);

        logActivity('password_set', 'auth', 'User set password via invite link');
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set Password — DIHS SBM Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-950: #052e16;
      --green-900: #0D5C28;
      --green-700: #15803D;
      --green-600: #16A34A;
      --green-500: #22C55E;
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
      background-color: #0D5C28;
      color: var(--gray-900);
      -webkit-font-smoothing: antialiased;
    }

    /* ─── Background ─── */
    .page-bg {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      position: relative;
      overflow: hidden;
      background:
        linear-gradient(150deg, #052e16 0%, #0D5C28 45%, #15803D 100%);
    }

    /* Subtle geometric decorations */
    .page-bg::before,
    .page-bg::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: 0.07;
      pointer-events: none;
    }
    .page-bg::before {
      width: 600px; height: 600px;
      background: #22C55E;
      top: -180px; right: -180px;
    }
    .page-bg::after {
      width: 400px; height: 400px;
      background: #22C55E;
      bottom: -120px; left: -120px;
    }

    /* ─── Card ─── */
    .card {
      background: #ffffff;
      border-radius: 18px;
      width: 100%;
      max-width: 460px;
      box-shadow:
        0 0 0 1px rgba(0,0,0,0.04),
        0 8px 40px rgba(0,0,0,0.22),
        0 2px 8px rgba(0,0,0,0.12);
      overflow: hidden;
      position: relative;
      z-index: 1;
    }

    /* ─── Card Header ─── */
    .card-header {
      background: linear-gradient(150deg, #052e16 0%, #0D5C28 60%, #16A34A 100%);
      padding: 32px 36px 28px;
      text-align: center;
      position: relative;
    }

    .logo-container {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 72px; height: 72px;
      /* Using a subtle white glow/background to make it pop against the dark gradient */
      background: rgba(255,255,255,0.9);
      border-radius: 50%;
      margin-bottom: 16px;
      padding: 4px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      border: 2px solid rgba(255,255,255,0.2);
    }

    .logo-container img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .card-header h1 {
      color: #FFFFFF;
      font-size: 17px;
      font-weight: 700;
      letter-spacing: -0.2px;
      margin-bottom: 4px;
    }

    .card-header p {
      color: rgba(255,255,255,0.65);
      font-size: 12.5px;
      letter-spacing: 0.2px;
    }

    /* ─── Card Body ─── */
    .card-body {
      padding: 32px 36px 36px;
    }

    /* Page title in body */
    .section-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--green-900);
      margin-bottom: 4px;
    }

    .section-subtitle {
      font-size: 13.5px;
      color: var(--gray-500);
      margin-bottom: 24px;
      line-height: 1.6;
    }

    /* Divider */
    .divider {
      border: none;
      border-top: 1px solid var(--gray-100);
      margin: 20px 0;
    }

    /* ─── User info pill ─── */
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
      width: 38px; height: 38px;
      border-radius: 10px;
      background: linear-gradient(135deg, #16A34A, #0D5C28);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .user-info-text .name {
      font-size: 14px;
      font-weight: 600;
      color: var(--green-900);
      line-height: 1.3;
    }

    .user-info-text .label {
      font-size: 11.5px;
      color: var(--gray-500);
      font-weight: 500;
    }

    /* ─── Form ─── */
    .form-group {
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--gray-700);
      margin-bottom: 6px;
      letter-spacing: 0.1px;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray-300);
      pointer-events: none;
      display: flex; align-items: center;
    }

    input[type="password"] {
      width: 100%;
      padding: 11px 14px 11px 40px;
      border: 1.5px solid var(--gray-300);
      border-radius: 9px;
      font-size: 14.5px;
      font-family: inherit;
      color: var(--gray-900);
      background: #fff;
      outline: none;
      transition: border-color 0.18s, box-shadow 0.18s;
      -webkit-appearance: none;
    }

    input[type="password"]:focus {
      border-color: var(--green-600);
      box-shadow: 0 0 0 3px rgba(22,163,74,0.12);
    }

    input[type="password"]:focus + .focus-ring {
      opacity: 1;
    }

    /* Password hint */
    .hint {
      font-size: 11.5px;
      color: var(--gray-500);
      margin-top: 5px;
    }

    /* ─── Password strength bar ─── */
    .strength-bar-wrap {
      height: 4px;
      background: var(--gray-100);
      border-radius: 4px;
      margin-top: 8px;
      overflow: hidden;
    }
    .strength-bar {
      height: 100%;
      border-radius: 4px;
      width: 0%;
      transition: width 0.3s ease, background-color 0.3s ease;
    }

    /* ─── Submit Button ─── */
    .btn-primary {
      width: 100%;
      padding: 13px 20px;
      background: linear-gradient(135deg, var(--green-600) 0%, var(--green-700) 100%);
      color: #fff;
      border: none;
      border-radius: 9px;
      font-size: 15px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      margin-top: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: opacity 0.15s, transform 0.15s, box-shadow 0.15s;
      box-shadow: 0 4px 12px rgba(22,163,74,0.30);
      letter-spacing: 0.1px;
    }

    .btn-primary:hover {
      opacity: 0.92;
      box-shadow: 0 6px 18px rgba(22,163,74,0.38);
      transform: translateY(-1px);
    }

    .btn-primary:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(22,163,74,0.25);
    }

    /* ─── Alerts ─── */
    .alert {
      border-radius: 9px;
      padding: 13px 16px;
      font-size: 13.5px;
      margin-bottom: 20px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      line-height: 1.55;
    }

    .alert-error {
      background: var(--red-50);
      border: 1px solid var(--red-200);
      color: var(--red-600);
    }

    .alert-expired {
      background: #FFF7ED;
      border: 1px solid #FED7AA;
      color: #9A3412;
    }

    .alert svg { flex-shrink: 0; margin-top: 1px; }

    /* ─── Success state ─── */
    .success-block {
      text-align: center;
      padding: 10px 0 8px;
    }

    .success-icon-wrap {
      width: 68px; height: 68px;
      border-radius: 50%;
      background: var(--green-100);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      border: 3px solid #BBF7D0;
    }

    .success-block h3 {
      font-size: 19px;
      font-weight: 700;
      color: var(--green-900);
      margin-bottom: 8px;
    }

    .success-block p {
      font-size: 14px;
      color: var(--gray-500);
      line-height: 1.65;
      margin-bottom: 28px;
    }

    .btn-login {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--green-900);
      color: #fff;
      text-decoration: none;
      font-size: 14.5px;
      font-weight: 600;
      padding: 12px 32px;
      border-radius: 9px;
      transition: background 0.15s, transform 0.15s;
      box-shadow: 0 3px 10px rgba(13,92,40,0.30);
    }

    .btn-login:hover {
      background: var(--green-700);
      transform: translateY(-1px);
    }

    /* ─── Expired / invalid state ─── */
    .expired-block {
      text-align: center;
      padding: 8px 0;
    }

    .expired-icon-wrap {
      width: 68px; height: 68px;
      border-radius: 50%;
      background: #FFF7ED;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      border: 3px solid #FED7AA;
    }

    .expired-block h3 {
      font-size: 18px;
      font-weight: 700;
      color: #92400E;
      margin-bottom: 8px;
    }

    .expired-block p {
      font-size: 13.5px;
      color: var(--gray-500);
      line-height: 1.65;
    }

    /* ─── Footer strip ─── */
    .card-footer {
      background: var(--gray-100);
      padding: 14px 36px;
      text-align: center;
      border-top: 1px solid #E5E7EB;
    }

    .card-footer p {
      font-size: 11.5px;
      color: var(--gray-500);
    }

    .card-footer a {
      color: var(--green-700);
      text-decoration: none;
      font-weight: 500;
    }

    .card-footer a:hover { text-decoration: underline; }

    /* ─── Responsive ─── */
    @media (max-width: 500px) {
      .card-body  { padding: 26px 24px 28px; }
      .card-header { padding: 26px 24px 22px; }
      .card-footer { padding: 13px 24px; }
    }
  </style>
</head>
<body>

<div class="page-bg">
  <div class="card">

    <!-- Header -->
    <div class="card-header">
      <div class="logo-container">
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
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" fill="#16A34A" opacity="0.15"/>
            <path d="M8 12L11 15L16 9" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3>Password Set Successfully</h3>
        <p>Your account is now active. You can sign in to the DIHS SBM Portal and start participating in the school's self-assessment process.</p>
        <a href="<?= baseUrl() ?>/login.php" class="btn-login">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          Sign In to Your Account
        </a>
      </div>

      <?php elseif ($error && !$tokenRow): ?>
      <!-- ── Expired / Invalid ── -->
      <div class="expired-block">
        <div class="expired-icon-wrap">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" fill="#F97316" opacity="0.15"/>
            <path d="M12 8V12" stroke="#F97316" stroke-width="2.2" stroke-linecap="round"/>
            <circle cx="12" cy="16" r="1.2" fill="#F97316"/>
          </svg>
        </div>
        <h3>Link Expired or Invalid</h3>
        <p style="margin-bottom:0;"><?= htmlspecialchars($error) ?><br><br>Please contact your School Administrator to resend the invitation.</p>
      </div>

      <?php else: ?>
      <!-- ── Set Password Form ── -->
      <h2 class="section-title">Set Your Password</h2>
      <p class="section-subtitle">Create a secure password to activate your account.</p>

      <!-- User info -->
      <div class="user-pill">
        <div class="user-avatar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="user-info-text">
          <div class="name"><?= htmlspecialchars($tokenRow['full_name']) ?></div>
          <div class="label"><?= htmlspecialchars($tokenRow['email']) ?></div>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" id="pwForm">
        <div class="form-group">
          <label for="password">New Password</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </span>
            <input type="password" id="password" name="password" required minlength="8"
                   autocomplete="new-password" placeholder="Min. 8 characters"
                   oninput="updateStrength(this.value)">
          </div>
          <div class="strength-bar-wrap">
            <div class="strength-bar" id="strengthBar"></div>
          </div>
          <p class="hint" id="strengthLabel">Enter a password to see its strength.</p>
        </div>

        <div class="form-group">
          <label for="confirm">Confirm Password</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </span>
            <input type="password" id="confirm" name="confirm" required minlength="8"
                   autocomplete="new-password" placeholder="Re-enter your password">
          </div>
        </div>

        <button type="submit" class="btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L3 7V12C3 16.97 6.84 21.61 12 23C17.16 21.61 21 16.97 21 12V7L12 2Z"/>
            <path d="M9 12L11 14L15 10"/>
          </svg>
          Activate Account &amp; Set Password
        </button>
      </form>
      <?php endif; ?>

    </div>

    <!-- Footer -->
    <div class="card-footer">
      <p>
        Secure portal &mdash; DIHS SBM Online Monitoring System
        &nbsp;·&nbsp;
        <a href="<?= baseUrl() ?>/login.php">Back to Login</a>
      </p>
    </div>

  </div>
</div>

<script>
  function updateStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    if (!val) {
      bar.style.width = '0%';
      label.textContent = 'Enter a password to see its strength.';
      label.style.color = '#6B7280';
      return;
    }
    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/\d/.test(val))   score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
      { w:'15%', color:'#EF4444', text:'Very weak' },
      { w:'30%', color:'#F97316', text:'Weak' },
      { w:'55%', color:'#EAB308', text:'Fair' },
      { w:'78%', color:'#22C55E', text:'Strong' },
      { w:'100%',color:'#16A34A', text:'Very strong' },
    ];
    const lvl = levels[Math.max(0, score - 1)];
    bar.style.width           = lvl.w;
    bar.style.backgroundColor = lvl.color;
    label.textContent = lvl.text;
    label.style.color = lvl.color;
  }
</script>

</body>
</html>