<?php
// profile.php — User Profile Page (accessible from any role subfolder or root)
// Auto-detects location and adjusts paths accordingly

// Try to find config/db.php relative to this file
$__dir = __DIR__;
$__configPath = null;
foreach (['/../config/db.php', '/config/db.php'] as $p) {
    if (file_exists($__dir . $p)) {
        $__configPath = $__dir . $p;
        break;
    }
}
if (!$__configPath) {
    // Walk up the directory tree
    $d = $__dir;
    for ($i = 0; $i < 4; $i++) {
        $d = dirname($d);
        if (file_exists($d . '/config/db.php')) {
            $__configPath = $d . '/config/db.php';
            break;
        }
    }
}
require_once $__configPath;
require_once dirname($__configPath) . '/../includes/auth.php';
requireLogin();

$db = getDB();
$uid = (int) $_SESSION['user_id'];

// Fetch current user data — safely add contact_number column if missing
try {
    $userStmt = $db->prepare("SELECT user_id, username, email, full_name, role, status, school_id, last_login, created_at, contact_number, profile_picture FROM users WHERE user_id = ?");
    $userStmt->execute([$uid]);
    $user = $userStmt->fetch();
} catch (\PDOException $e) {
    // contact_number or profile_picture column might not exist yet — run migration
    try {
        $db->exec("ALTER TABLE users ADD COLUMN contact_number VARCHAR(30) DEFAULT NULL");
    } catch (\Exception $ex) {
    }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    } catch (\Exception $ex) {
    }
    $userStmt = $db->prepare("SELECT user_id, username, email, full_name, role, status, school_id, last_login, created_at, contact_number, profile_picture FROM users WHERE user_id = ?");
    $userStmt->execute([$uid]);
    $user = $userStmt->fetch();
}

// Debug logging for avatar
if (!empty($user['profile_picture'])) {
    $fullAvatarPath = dirname($__configPath) . '/../' . $user['profile_picture'];
    $fileExists = file_exists($fullAvatarPath);
    error_log('Avatar check: stored=' . $user['profile_picture'] . ', path=' . $fullAvatarPath . ', exists=' . ($fileExists ? 'YES' : 'NO'));
}

$__roleLabel = [
    'system_admin' => 'System Admin',
    'school_head' => 'School Head',
    'sbm_coordinator' => 'SBM Coordinator',
    'teacher' => 'Teacher / Evaluator',
    'external_stakeholder' => 'External Stakeholder',
][$user['role']] ?? ucwords(str_replace('_', ' ', $user['role']));

$__roleColor = [
    'system_admin' => '#7C3AED',
    'school_head' => '#16A34A',
    'sbm_coordinator' => '#16A34A',
    'teacher' => '#0D9488',
    'external_stakeholder' => '#2563EB',
][$user['role']] ?? '#16A34A';

// Initials for fallback avatar
$__nameParts = array_filter(explode(' ', trim($user['full_name'])));
$__initials = strtoupper(substr($__nameParts[0] ?? 'U', 0, 1) . (isset($__nameParts[1]) ? substr($__nameParts[1], 0, 1) : ''));

$pageTitle = 'My Profile';
$activePage = 'profile.php';
include dirname($__configPath) . '/../includes/header.php';
?>

<style>
/* ── PROFILE PAGE ─────────────────────────────────────────── */
.profile-wrap {
    max-width: 860px;
    margin: 0 auto;
}

/* Hero card */
.profile-hero {
    background: 
        linear-gradient(to right, rgba(8, 26, 8, 0.8) 0%, rgba(8, 26, 8, 0.4) 50%, rgba(8, 26, 8, 0.1) 100%),
        url('<?= e(baseUrl()) ?>/assets/cover.png') center/cover no-repeat;
    background-color: #081a08;
    border-radius: var(--radius-lg);
    padding: 36px 36px 32px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    flex-wrap: wrap;
}


/* Avatar zone */
.avatar-zone {
    position: relative;
    flex-shrink: 0;
    cursor: pointer;
}
.avatar-ring {
    width: 90px; height: 90px;
    border-radius: 22px;
    background: rgba(255,255,255,.12);
    border: 2.5px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-display);
    font-size: 32px; font-weight: 800;
    color: #fff;
    overflow: hidden;
    position: relative;
    transition: border-color 200ms;
}
.avatar-ring img {
    width:100%; height:100%; object-fit:cover;
    border-radius:20px;
}
.avatar-zone:hover .avatar-ring {
    border-color: rgba(74,222,128,.65);
}
.avatar-edit-btn {
    position: absolute;
    bottom: -5px; right: -5px;
    width: 28px; height: 28px;
    border-radius: 8px;
    background: #16A34A;
    border: 2px solid #0d260d;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: background 150ms, transform 150ms;
    box-shadow: 0 2px 8px rgba(0,0,0,.4);
}
.avatar-edit-btn:hover { background:#15803D; transform:scale(1.1); }
.avatar-edit-btn svg { width:13px; height:13px; stroke:#fff; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

.hero-meta { position:relative; z-index:1; flex:1; min-width:0; }
.hero-name {
    font-family: var(--font-display);
    font-size: 24px; font-weight: 800; letter-spacing:-.4px;
    margin-bottom: 5px; line-height: 1.15;
}
.hero-role {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 999px;
    font-size: 11.5px; font-weight: 700;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.18);
    color: rgba(255,255,255,.85);
    margin-bottom: 10px;
}
.hero-role-dot { width:6px; height:6px; border-radius:50%; }
.hero-stats {
    display: flex; gap: 16px; flex-wrap: wrap;
    font-size: 12px; color: rgba(255,255,255,.5);
}
.hero-stat strong { color: rgba(255,255,255,.85); font-weight: 600; }

/* Section cards */
.profile-section {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    margin-bottom: 18px;
}
.ps-head {
    display: flex; align-items: center; gap: 14px;
    padding: 18px 24px 16px;
    border-bottom: 1px solid var(--n-100);
}
.ps-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ps-icon svg { width:17px; height:17px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.ps-head-text { flex:1; }
.ps-head-title { font-family:var(--font-display); font-size:14.5px; font-weight:700; color:var(--n-900); margin-bottom:2px; }
.ps-head-sub { font-size:12px; color:var(--n-400); }
.ps-body { padding: 24px; }

/* Form grid */
.pf-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:600px){ .pf-grid{ grid-template-columns:1fr; } }

.pf-group { display:flex; flex-direction:column; gap:5px; }
.pf-group label { font-size:12.5px; font-weight:600; color:var(--n-700); }
.pf-input {
    padding: 9px 13px;
    border: 1.5px solid var(--n-200);
    border-radius: 8px;
    background: #fff;
    font-family: var(--font-body); font-size:13.5px; color:var(--n-900);
    outline: none;
    transition: border-color 180ms, box-shadow 180ms;
}
.pf-input:focus { border-color:var(--brand-600); box-shadow: 0 0 0 3px rgba(22,163,74,.10); }
.pf-input:disabled {
    background: var(--n-50); color:var(--n-500);
    cursor:not-allowed; border-color:var(--n-150);
}
.pf-input::placeholder { color:var(--n-400); }

/* Read-only field display */
.pf-readonly {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 13px;
    border: 1.5px solid var(--n-150);
    border-radius: 8px;
    background: var(--n-50);
    font-size: 13.5px; color: var(--n-600);
}
.pf-readonly-icon { width:15px; height:15px; stroke:var(--n-400); fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }

/* Image preview */
.img-preview-wrap {
    display: flex; align-items: center; gap: 16px;
    padding: 14px;
    background: var(--n-50);
    border: 1.5px dashed var(--n-200);
    border-radius: 10px;
    transition: border-color 180ms, background 180ms;
    margin-bottom: 16px;
}
.img-preview-wrap.has-file { border-color:#86EFAC; background:#F0FDF4; }
.img-preview-thumb {
    width: 56px; height: 56px; border-radius: 12px;
    object-fit: cover; border: 2px solid var(--n-200); flex-shrink:0;
    display: none;
}
.img-preview-thumb.visible { display:block; }
.img-preview-placeholder {
    width: 56px; height: 56px; border-radius: 12px;
    background: var(--n-200);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.img-preview-placeholder svg { width:22px; height:22px; stroke:var(--n-400); fill:none; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
.img-preview-text { flex:1; }
.img-preview-name { font-size:13px; font-weight:600; color:var(--n-800); margin-bottom:2px; }
.img-preview-hint { font-size:11.5px; color:var(--n-400); }

/* Password strength */
.pw-strength { height:4px; background:var(--n-150); border-radius:999px; margin-top:6px; overflow:hidden; }
.pw-strength-fill { height:100%; border-radius:999px; width:0; transition: width 300ms, background 300ms; }

/* Footer actions */
.pf-footer {
    display: flex; align-items: center; justify-content: flex-end;
    gap: 10px; padding: 16px 24px;
    border-top: 1px solid var(--n-100);
    background: var(--n-50);
    flex-wrap: wrap;
}

/* Loading spinner inline */
.spinner {
    display: inline-block; width:14px; height:14px;
    border: 2px solid rgba(255,255,255,.4);
    border-top-color: #fff; border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
@keyframes spin { to{transform:rotate(360deg)} }
</style>

<?php
// Inline avatar style for hero
$avatarHtml = '';
if (!empty($user['profile_picture']) && file_exists(dirname($__configPath) . '/../' . $user['profile_picture'])) {
    $avatarHtml = '<img src="' . e(baseUrl() . '/' . $user['profile_picture']) . '?v=' . time() . '" id="heroAvatar" alt="Avatar">';
} else {
    $avatarHtml = '<span id="heroAvatarInitials">' . e($__initials) . '</span>';
}
?>

<div class="profile-wrap">

  <!-- BREADCRUMB -->
  <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--n-500);margin-bottom:18px;">
    <a href="javascript:history.back()" style="color:var(--n-500);text-decoration:none;display:flex;align-items:center;gap:5px;transition:color 150ms;" onmouseover="this.style.color='var(--n-900)'" onmouseout="this.style.color='var(--n-500)'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
    <span style="color:var(--n-300);">/</span>
    <span>My Profile</span>
  </div>

  <!-- HERO -->
  <div class="profile-hero">
    <div class="avatar-zone" onclick="document.getElementById('avatarFileInput').click();" title="Click to change photo">
      <div class="avatar-ring" id="avatarRingHero" style="background:<?= e($__roleColor) ?>22;border-color:<?= e($__roleColor) ?>44;">
        <?php if (!empty($user['profile_picture'])): ?>
              <img src="<?= e(baseUrl() . '/' . $user['profile_picture']) ?>?v=<?= time() ?>" id="heroAvatarImg" alt="Profile">
        <?php else: ?>
              <span id="heroAvatarInitials" style="color:<?= e($__roleColor) ?>;"><?= e($__initials) ?></span>
        <?php endif; ?>
      </div>
      <div class="avatar-edit-btn">
        <svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
      </div>
    </div>
    <div class="hero-meta">
      <div class="hero-name" id="heroName"><?= e($user['full_name']) ?></div>
      <div class="hero-role">
        <span class="hero-role-dot" style="background:<?= e($__roleColor) ?>;"></span>
        <?= e($__roleLabel) ?>
      </div>
      <div class="hero-stats">
        <span>@<?= e($user['username']) ?></span>
        <span>·</span>
        <span><strong><?= e($user['email']) ?></strong></span>
        <?php if ($user['last_login']): ?>
              <span>·</span>
              <span>Last login: <strong><?= timeAgo($user['last_login']) ?></strong></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ═══════ PERSONAL INFORMATION ═══════ -->
  <div class="profile-section" style="margin-bottom:32px;">
    <div class="ps-head">
      <div class="ps-icon" style="background:var(--brand-100);color:var(--brand-700);">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div class="ps-head-text">
        <div class="ps-head-title">Personal Information</div>
        <div class="ps-head-sub">Update your name and contact details</div>
      </div>
    </div>
    <div class="ps-body">

      <!-- Avatar Upload -->
      <div style="margin-bottom:20px;">
        <label style="font-size:12.5px;font-weight:600;color:var(--n-700);display:block;margin-bottom:8px;">Profile Photo</label>
        <div class="img-preview-wrap" id="previewWrap">
          <div class="img-preview-placeholder" id="previewPlaceholder"
            <?php if (!empty($user['profile_picture'])): ?>style="display:none;"<?php endif; ?>>
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <?php if (!empty($user['profile_picture'])): ?>
                <img class="img-preview-thumb visible" id="previewThumb"
                     src="<?= e(baseUrl() . '/' . $user['profile_picture']) ?>?v=<?= time() ?>" alt="Current photo">
          <?php else: ?>
                <img class="img-preview-thumb" id="previewThumb" src="" alt="Preview">
          <?php endif; ?>
          <div class="img-preview-text">
            <div class="img-preview-name" id="previewName">
              <?php echo !empty($user['profile_picture']) ? 'Current profile photo' : 'No photo selected'; ?>
            </div>
            <div class="img-preview-hint">JPG, PNG or WEBP · Max 5 MB</div>
          </div>
          <label for="avatarFileInput" class="btn btn-secondary btn-sm" style="cursor:pointer;flex-shrink:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload
          </label>
        </div>
        <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleAvatarPreview(this)">
      </div>

      <form id="profileForm" onsubmit="submitProfile(event)">
        <div class="pf-grid">
          <div class="pf-group" style="grid-column:1/-1;">
            <label for="fullName">Full Name <span style="color:var(--red);">*</span></label>
            <input class="pf-input" type="text" id="fullName" name="full_name"
                   value="<?= e($user['full_name']) ?>"
                   placeholder="Enter your full name" required>
          </div>
          <div class="pf-group">
            <label>Username</label>
            <div class="pf-readonly">
              <svg class="pf-readonly-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <?= e($user['username']) ?>
            </div>
          </div>
          <div class="pf-group">
            <label for="contactNumber">Contact Number</label>
            <input class="pf-input" type="tel" id="contactNumber" name="contact_number"
                   value="<?= e($user['contact_number'] ?? '') ?>"
                   placeholder="e.g. +63 912 345 6789">
          </div>
          <div class="pf-group" style="grid-column:1/-1;">
            <label>Email Address</label>
            <div class="pf-readonly">
              <svg class="pf-readonly-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <?= e($user['email']) ?>
            </div>
          </div>
          <div class="pf-group">
            <label>Role</label>
            <div class="pf-readonly">
              <span style="width:8px;height:8px;border-radius:50%;background:<?= e($__roleColor) ?>;flex-shrink:0;"></span>
              <?= e($__roleLabel) ?>
            </div>
          </div>
          <div class="pf-group">
            <label>Member Since</label>
            <div class="pf-readonly">
              <svg class="pf-readonly-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?= date('F j, Y', strtotime($user['created_at'])) ?>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="pf-footer">
      <button type="button" class="btn btn-secondary" onclick="resetProfileForm()">Reset</button>
      <button type="button" class="btn btn-primary" id="saveProfileBtn" onclick="submitProfile()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Changes
      </button>
    </div>
  </div>




</div><!-- /profile-wrap -->

<script>
const PROFILE_HANDLER = '<?= e(baseUrl()) ?>/includes/profile_handler.php';

// ── Original values for reset ──
const origName    = <?= json_encode($user['full_name']) ?>;
const origContact = <?= json_encode($user['contact_number'] ?? '') ?>;

function resetProfileForm() {
    document.getElementById('fullName').value    = origName;
    document.getElementById('contactNumber').value = origContact;
    const fileInput = document.getElementById('avatarFileInput');
    fileInput.value = '';
    // Reset preview to current saved state
    const thumb = document.getElementById('previewThumb');
    const placeholder = document.getElementById('previewPlaceholder');
    const wrap  = document.getElementById('previewWrap');
    const name  = document.getElementById('previewName');
    const hasPic = <?= !empty($user['profile_picture']) ? 'true' : 'false' ?>;
    if (hasPic) {
        thumb.classList.add('visible');
        if (placeholder) placeholder.style.display = 'none';
        name.textContent = 'Current profile photo';
    } else {
        thumb.classList.remove('visible');
        if (placeholder) placeholder.style.display = '';
        name.textContent = 'No photo selected';
    }
    wrap.classList.remove('has-file');
}

function handleAvatarPreview(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const thumb  = document.getElementById('previewThumb');
    const placeholder = document.getElementById('previewPlaceholder');
    const wrap   = document.getElementById('previewWrap');
    const nameEl = document.getElementById('previewName');

    const reader = new FileReader();
    reader.onload = function(e) {
        thumb.src = e.target.result;
        thumb.classList.add('visible');
        if (placeholder) placeholder.style.display = 'none';
        wrap.classList.add('has-file');
        nameEl.textContent = file.name + ' (' + (file.size/1024).toFixed(0) + ' KB)';

        // Live preview in hero
        updateHeroAvatar(e.target.result);
    };
    reader.readAsDataURL(file);
}

function updateHeroAvatar(src) {
    const ring = document.getElementById('avatarRingHero');
    const initialsEl = document.getElementById('heroAvatarInitials');
    let img = document.getElementById('heroAvatarImg');
    if (!img) {
        img = document.createElement('img');
        img.id = 'heroAvatarImg';
        img.alt = 'Profile';
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:20px;';
        ring.appendChild(img);
    }
    img.src = src;
    img.style.display = '';
    if (initialsEl) initialsEl.style.display = 'none';
    img.style.display = 'block';
}

async function submitProfile(e) {
    if (e) e.preventDefault();
    const fullName = document.getElementById('fullName').value.trim();
    const contact  = document.getElementById('contactNumber').value.trim();
    const fileInput = document.getElementById('avatarFileInput');
    const btn = document.getElementById('saveProfileBtn');

    // Validation
    if (!fullName) { toast('Full name is required.', 'err'); document.getElementById('fullName').focus(); return; }
    if (fullName.length < 2) { toast('Full name must be at least 2 characters.', 'err'); return; }
    if (contact && !/^[\d\s\+\-\(\)]{7,20}$/.test(contact)) { toast('Please enter a valid contact number.', 'err'); return; }

    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving…';

    const fd = new FormData();
    fd.append('action', 'save_profile');
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    fd.append('full_name', fullName);
    fd.append('contact_number', contact);
    if (fileInput.files[0]) fd.append('profile_picture', fileInput.files[0]);

    try {
        const res  = await fetch(PROFILE_HANDLER, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            toast(data.msg, 'ok');

            // Update hero name
            document.getElementById('heroName').textContent = data.full_name;

            // Update hero avatar if profile picture was updated
            if (data.profile_picture) {
                const avatarSrc = '<?= e(baseUrl()) ?>/' + data.profile_picture + '?v=' + Date.now();
                const heroRing = document.getElementById('avatarRingHero');
                const initialsEl = document.getElementById('heroAvatarInitials');
                let heroImg = document.getElementById('heroAvatarImg');
                
                if (!heroImg) {
                    heroImg = document.createElement('img');
                    heroImg.id = 'heroAvatarImg';
                    heroImg.alt = 'Profile';
                    heroImg.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:20px;';
                    heroRing.appendChild(heroImg);
                }
                heroImg.src = avatarSrc;
                heroImg.style.display = 'block';
                if (initialsEl) initialsEl.style.display = 'none';
            }

            // Update sidebar avatar/name (live DOM update)
            document.querySelectorAll('.sb-user-name, .sb-popup-name').forEach(el => el.textContent = data.full_name);
            if (data.profile_picture) {
                const avatarSrc = '<?= e(baseUrl()) ?>/' + data.profile_picture + '?v=' + Date.now();
                // Update sidebar avatars that are initials-based
                document.querySelectorAll('.sb-avatar').forEach(el => {
                    if (!el.querySelector('img')) {
                        el.innerHTML = `<img src="${avatarSrc}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" alt="Avatar">`;
                    } else {
                        el.querySelector('img').src = avatarSrc;
                    }
                });
            }

            // Reset file input
            fileInput.value = '';
            document.getElementById('previewWrap').classList.remove('has-file');
            document.getElementById('previewName').textContent = 'Current profile photo';

        } else {
            toast(data.msg, 'err');
        }
    } catch (err) {
        toast('Network error. Please try again.', 'err');
    }

    btn.disabled = false;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Changes`;
}


// Hook avatar file input to hero preview click too
document.getElementById('avatarFileInput').addEventListener('change', function() {
    if (!this.files[0]) return;
    handleAvatarPreview(this);
});
</script>

<?php include dirname($__configPath) . '/../includes/footer.php'; ?>