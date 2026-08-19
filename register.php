<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
  header('Location: ' . roleHome($_SESSION['role']));
  exit;
}

$db = getDB();
$errors = [];
$success = false;

// Preserve submitted values on error (except password/file)
$old = [
  'employee_id' => '',
  'full_name'   => '',
  'email'       => '',
  'department'  => '',
  'username'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();

  $old['employee_id'] = trim($_POST['employee_id'] ?? '');
  $old['full_name']   = trim($_POST['full_name'] ?? '');
  $old['email']       = trim($_POST['email'] ?? '');
  $old['department']  = trim($_POST['department'] ?? '');
  $old['username']    = trim($_POST['username'] ?? '');

  // ── Field validation ──
  if ($old['full_name'] === '' || mb_strlen($old['full_name']) < 2) {
    $errors[] = 'Please enter your full name.';
  }
  if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
  }
  if ($old['username'] === '' || mb_strlen($old['username']) < 3) {
    $errors[] = 'Please choose a username (at least 3 characters).';
  }

  // ── Uniqueness checks ──
  if (!$errors) {
    if ($old['employee_id'] !== '') {
      $st = $db->prepare("SELECT 1 FROM users WHERE employee_id=? LIMIT 1");
      $st->execute([$old['employee_id']]);
      if ($st->fetchColumn()) $errors[] = 'This Employee ID is already registered.';
    }
    $st = $db->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1");
    $st->execute([$old['email']]);
    if ($st->fetchColumn()) $errors[] = 'An account with this email already exists.';

    $st = $db->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1");
    $st->execute([$old['username']]);
    if ($st->fetchColumn()) $errors[] = 'This username is already taken.';
  }

  // ── Profile photo validation ──
  $photoMime = null;
  $photoExt  = null;
  if (!$errors) {
    if (empty($_FILES['profile_photo']['tmp_name']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Please upload a profile photo.';
    } else {
      $file = $_FILES['profile_photo'];
      $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $photoMime = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);

      if (!isset($allowed[$photoMime])) {
        $errors[] = 'Only JPG, PNG, or WEBP images are allowed for the profile photo.';
      } elseif ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Profile photo must be under 5 MB.';
      } else {
        $photoExt = $allowed[$photoMime];
      }
    }
  }

  // ── Create pending account ──
  if (!$errors) {
    try {
      $db->prepare(
        "INSERT INTO users (username,password,email,full_name,role,status,school_id,employee_id,department,profile_picture)
         VALUES (?,NULL,?,?,NULL,'pending',?,?,?,NULL)"
      )->execute([
        $old['username'],
        $old['email'],
        $old['full_name'],
        SCHOOL_ID,
        $old['employee_id'] ?: null,
        $old['department'] ?: null,
      ]);
      $newId = (int) $db->lastInsertId();

      // Move uploaded photo now that we have a user_id (matches profile_handler.php naming convention)
      $uploadDir = __DIR__ . '/uploads/avatars/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
      $fileName = 'avatar_' . $newId . '_' . time() . '.' . $photoExt;
      $dest = $uploadDir . $fileName;

      if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest) || !file_exists($dest)) {
        // Roll back the user row if the photo couldn't be saved
        $db->prepare("DELETE FROM users WHERE user_id=?")->execute([$newId]);
        error_log('Registration photo save failed for pending user_id=' . $newId);
        $errors[] = 'Failed to save your profile photo. Please try again.';
      } else {
        $db->prepare("UPDATE users SET profile_picture=? WHERE user_id=?")
          ->execute(['uploads/avatars/' . $fileName, $newId]);
        if (function_exists('logActivity')) {
          logActivity('self_register', 'users', 'Registration submitted: ' . $old['username']);
        }
        $success = true;
      }
    } catch (PDOException $e) {
      $errors[] = 'Something went wrong while submitting your registration. Please try again.';
      error_log('Registration insert error: ' . $e->getMessage());
    }
  }
}

$_allDepts = $db->prepare("SELECT name FROM departments WHERE school_id=? AND status='active' ORDER BY name ASC");
$_allDepts->execute([SCHOOL_ID]);
$_allDepts = $_allDepts->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
  <title>Register — <?= e(SITE_NAME) ?></title>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap"
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
      --g600: #16A34A;
      --g700: #15803D;
      --dark: #0D1117;
      --mid: #4B5563;
      --light: #9CA3AF;
      --n200: #E5E7EB;
      --n100: #F3F4F6;
      --red: #DC2626;
      --redb: #FEE2E2;
      --white: #FFFFFF;
      --font: 'Inter', -apple-system, sans-serif;
      --display: 'Manrope', -apple-system, sans-serif;
      --mono: 'JetBrains Mono', 'Courier New', monospace;
    }

    html,
    body {
      height: 100%;
      font-family: var(--font);
      background: var(--white);
      color: var(--dark);
      -webkit-font-smoothing: antialiased;
    }

    .layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 100vh;
    }

    .panel-left {
      position: relative;
      background: #0A0F0A;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
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
      margin: auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .eyebrow {
      font-family: var(--mono);
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 11px;
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--g300);
      margin-bottom: 22px;
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
      font-family: var(--font);
      text-align: center;
      font-size: clamp(22px, 3vw, 36px);
      font-weight: 400;
      color: #fff;
      line-height: 1.25;
      letter-spacing: -1.5px;
      margin-top: 10px;
    }

    .panel-right {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: center;
      padding: 32px 64px 56px;
      background: #FAFDFB;
      position: relative;
      box-sizing: border-box;
    }

    .panel-right.is-success {
      justify-content: center;
    }

    .form-wrap {
      width: 100%;
      max-width: 420px;
      padding: 32px 0;
    }

    .form-title {
      font-family: var(--font);
      font-size: 30px;
      font-weight: 700;
      color: var(--dark);
      letter-spacing: -.5px;
      line-height: 1.1;
      margin-bottom: 6px;
    }

    .form-sub {
      font-size: 14px;
      font-weight: 300;
      color: var(--mid);
      margin-bottom: 24px;
      line-height: 1.5;
    }

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

    .alert-err ul {
      margin: 0;
      padding-left: 18px;
    }

    .alert-err svg {
      width: 15px;
      height: 15px;
      flex-shrink: 0;
      margin-top: 2px;
    }

    .notice-box {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: var(--g50);
      border: 1px solid var(--g200);
      color: var(--g700);
      border-radius: 10px;
      padding: 12px 13px;
      font-size: 13px;
      margin-bottom: 22px;
      line-height: 1.55;
    }

    .notice-box svg {
      width: 16px;
      height: 16px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .field {
      margin-bottom: 16px;
    }

    .field-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .field label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 7px;
      letter-spacing: .01em;
    }

    .field label .opt {
      font-weight: 400;
      color: var(--light);
    }

    .fc {
      width: 100%;
      padding: 12px 13px;
      border: 1.5px solid var(--n200);
      border-radius: 10px;
      background: #fff;
      font-family: var(--font);
      font-size: 14px;
      color: var(--dark);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .fc:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
    }

    .fc::placeholder {
      color: var(--light);
    }

    select.fc {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 16px;
      padding-right: 36px;
    }

    .photo-upload {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px;
      border: 1.5px dashed var(--n200);
      border-radius: 10px;
      background: #fff;
    }

    .photo-preview {
      width: 56px;
      height: 56px;
      border-radius: 10px;
      background: var(--n100);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      color: var(--light);
    }

    .photo-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .photo-upload-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 600;
      color: var(--g700);
      background: var(--g50);
      border: 1.5px solid var(--g200);
      border-radius: 8px;
      padding: 8px 14px;
      cursor: pointer;
      transition: background .15s;
    }

    .photo-upload-btn:hover {
      background: var(--g100);
    }

    .photo-file-name {
      font-size: 12px;
      color: var(--mid);
      margin-top: 4px;
    }

    .btn-login {
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
      margin-top: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all .2s ease;
      box-shadow: 0 2px 8px rgba(21, 128, 61, .35);
    }

    .btn-login:hover {
      background: linear-gradient(135deg, #166534 0%, #14532D 100%);
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(21, 128, 61, .3);
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 13px;
      font-weight: 600;
      color: var(--mid);
      text-decoration: none;
      margin-bottom: 24px;
      padding: 6px 12px 6px 0;
      border-radius: 8px;
      transition: all .2s ease;
    }

    .back-link:hover {
      color: var(--green);
      transform: translateX(-2px);
    }

    .back-link svg {
      width: 15px;
      height: 15px;
    }

    .form-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 11.5px;
      color: var(--light);
    }

    .success-panel {
      text-align: center;
      padding: 20px 0;
    }

    .success-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: var(--g100);
      color: var(--g700);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }

    .success-icon svg {
      width: 30px;
      height: 30px;
    }

    .success-title {
      font-size: 24px;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 10px;
    }

    .success-sub {
      font-size: 14px;
      color: var(--mid);
      line-height: 1.6;
      max-width: 340px;
      margin: 0 auto 28px;
    }

    @media (max-width:900px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .panel-left {
        display: none;
      }

      .panel-right {
        padding: 40px 28px;
      }

      .field-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <div class="layout">

    <!-- LEFT PANEL -->
    <div class="panel-left">
      <div class="left-body">
        <img src="assets/seal.png" alt="Dasmariñas Integrated High School"
          style="width:200px;height:200px;object-fit:contain;margin-bottom:28px;">
        <span class="eyebrow">School-Based Management Monitoring System</span>
        <h1 class="headline">Dasmariñas Integrated <br>High School</h1>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="panel-right<?= $success ? ' is-success' : '' ?>">
      <div class="form-wrap">

        <?php if ($success): ?>
          <div class="success-panel">
            <div class="success-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
            </div>
            <div class="success-title">Registration Submitted</div>
            <p class="success-sub">Your account request has been submitted and is awaiting review by the System Administrator. You'll receive an email with a link to set your password once your account is approved.</p>
            <a href="login.php" class="btn-login" style="text-decoration:none;">Back to Sign In</a>
          </div>
        <?php else: ?>

          <a href="login.php" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="19" y1="12" x2="5" y2="12" />
              <polyline points="12 19 5 12 12 5" />
            </svg>
            Back to Sign In
          </a>

          <div class="form-title">Register Account</div>
          <div class="form-sub">Request access to the SBM Monitoring Portal.</div>

          <?php if ($errors): ?>
            <div class="alert-err">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
              <ul>
                <?php foreach ($errors as $err): ?>
                  <li><?= e($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="notice-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="16" x2="12" y2="12" />
              <line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
            <span>Your account request will be reviewed by the System Administrator. Your role and account status will be assigned after approval.</span>
          </div>

          <form method="post" action="register.php" enctype="multipart/form-data" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="field-row">
              <div class="field">
                <label>Employee ID <span class="opt">(optional)</span></label>
                <input class="fc" type="text" name="employee_id" placeholder="e.g. 100-456-789"
                  value="<?= e($old['employee_id']) ?>">
              </div>
              <div class="field">
                <label>Full Name <span style="color:var(--red);">*</span></label>
                <input class="fc" type="text" name="full_name" placeholder="Juan dela Cruz" required
                  value="<?= e($old['full_name']) ?>">
              </div>
            </div>

            <div class="field">
              <label>Email Address <span style="color:var(--red);">*</span></label>
              <input class="fc" type="email" name="email" placeholder="juan@deped.gov.ph" required
                value="<?= e($old['email']) ?>">
            </div>

            <div class="field">
              <label>Department <span class="opt">(optional)</span></label>
              <select class="fc" name="department">
                <option value="">Select Department</option>
                <?php foreach ($_allDepts as $dname): ?>
                  <option value="<?= e($dname) ?>" <?= $old['department'] === $dname ? 'selected' : '' ?>><?= e($dname) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Username <span style="color:var(--red);">*</span></label>
              <input class="fc" type="text" name="username" placeholder="juandelacruz" required
                value="<?= e($old['username']) ?>">
            </div>

            <div class="field">
              <label>Profile Photo <span style="color:var(--red);">*</span></label>
              <div class="photo-upload">
                <div class="photo-preview" id="photoPreview">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                </div>
                <div>
                  <label for="profile_photo" class="photo-upload-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="17 8 12 3 7 8" />
                      <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    Choose Photo
                  </label>
                  <div class="photo-file-name" id="photoFileName">JPG, PNG, or WEBP · Max 5MB</div>
                </div>
              </div>
              <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp"
                required style="display:none;">
            </div>

            <button class="btn-login" type="submit">Submit Registration</button>
          </form>

        <?php endif; ?>

        <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #F3F4F6;">
          <p class="form-footer"><?= e(SITE_NAME) ?> &nbsp;·&nbsp; DepEd Order No. 007, s. 2024 &nbsp;·&nbsp; <?= date('Y') ?></p>
        </div>
      </div>
    </div>

  </div>

  <script>
    const photoInput = document.getElementById('profile_photo');
    if (photoInput) {
      photoInput.addEventListener('change', function () {
        const file = this.files[0];
        const preview = document.getElementById('photoPreview');
        const fileName = document.getElementById('photoFileName');
        if (!file) return;
        fileName.textContent = file.name;
        const reader = new FileReader();
        reader.onload = e => {
          preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
      });
    }
  </script>
</body>

</html>