<?php
ob_start();
// school_head/settings.php — System Settings & School Years
// Moved from admin/settings.php — school_head is now top role
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('school_years');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  // Send JSON header before any output (including ob_start buffer) is flushed
  while (ob_get_level())
    ob_end_clean();
  header('Content-Type: application/json; charset=UTF-8');
  verifyCsrf();
  if ($_POST['action'] === 'save_sy') {
    $id = (int) ($_POST['sy_id'] ?? 0);
    // Validate date format if provided
    $dateStart = null;
    $dateEnd = null;
    if (!empty($_POST['date_start'])) {
      $dateStart = DateTime::createFromFormat('Y-m-d', $_POST['date_start']) ? $_POST['date_start'] : null;
    }
    if (!empty($_POST['date_end'])) {
      $dateEnd = DateTime::createFromFormat('Y-m-d', $_POST['date_end']) ? $_POST['date_end'] : null;
    }
    if ($id) {
      // If marking this year as current, unset all others first
      if ((int) $_POST['is_current'] === 1) {
        $db->prepare("UPDATE school_years SET is_current=0 WHERE sy_id != ?")
          ->execute([$id]);
      }
      $db->prepare("UPDATE school_years SET label=?,date_start=?,date_end=?,is_current=? WHERE sy_id=?")
        ->execute([trim($_POST['label']), $dateStart, $dateEnd, (int) $_POST['is_current'], $id]);
    } else {
      if ((int) $_POST['is_current'] === 1) {
        $db->query("UPDATE school_years SET is_current=0");
      }
      $db->prepare("INSERT INTO school_years (label,date_start,date_end,is_current) VALUES (?,?,?,?)")
        ->execute([trim($_POST['label']), $dateStart, $dateEnd, (int) $_POST['is_current']]);
    }
    echo json_encode(['ok' => true, 'msg' => 'School year saved.']);
    exit;
  }
  if ($_POST['action'] === 'set_current_sy') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid school year.']);
      exit;
    }

    $exists = $db->prepare("SELECT sy_id, label, is_current FROM school_years WHERE sy_id = ? LIMIT 1");
    $exists->execute([$id]);
    $syRow = $exists->fetch();
    if (!$syRow) {
      echo json_encode(['ok' => false, 'msg' => 'School year not found.']);
      exit;
    }

    if ((int) $syRow['is_current'] === 1) {
      echo json_encode(['ok' => true, 'msg' => 'That school year is already active.']);
      exit;
    }

    $db->beginTransaction();
    try {
      $db->exec("UPDATE school_years SET is_current = 0");
      $db->prepare("UPDATE school_years SET is_current = 1 WHERE sy_id = ?")->execute([$id]);
      $db->commit();
      echo json_encode(['ok' => true, 'msg' => 'Active school year updated to ' . $syRow['label'] . '.']);
    } catch (\Throwable $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      echo json_encode(['ok' => false, 'msg' => 'Failed to switch the active school year.']);
    }
    exit;
  }
  if ($_POST['action'] === 'delete_sy') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid ID.']);
      exit;
    }
    $isCurrent = $db->prepare("SELECT is_current FROM school_years WHERE sy_id = ?");
    $isCurrent->execute([$id]);
    $row = $isCurrent->fetch();
    if (!$row) {
      echo json_encode(['ok' => false, 'msg' => 'School year not found.']);
      exit;
    }
    if ((int) $row['is_current'] === 1) {
      echo json_encode(['ok' => false, 'msg' => 'Cannot delete the current active school year.']);
      exit;
    }
    try {
      $db->prepare("DELETE FROM school_years WHERE sy_id = ?")->execute([$id]);
      echo json_encode(['ok' => true, 'msg' => 'School year deleted.']);
      exit;
    } catch (\PDOException $e) {
      echo json_encode(['ok' => false, 'msg' => 'Cannot delete: this school year has linked assessment data.']);
      exit;
    }
  }
  if ($_POST['action'] === 'get_sy') {
    $st = $db->prepare("SELECT sy_id, label, date_start, date_end, is_current, created_at FROM school_years WHERE sy_id=?");
    $st->execute([(int) $_POST['id']]);
    $row = $st->fetch();
    if (!$row) {
      echo json_encode(['ok' => false, 'msg' => 'School year not found.']);
      exit;
    }
    echo json_encode($row);
    exit;
  }
  exit;
}

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();

$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$cycleCount = $db->query("SELECT COUNT(*) FROM sbm_cycles")->fetchColumn();
$validatedCount = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='validated'")->fetchColumn();
$responseCount = $db->query("SELECT COUNT(*) FROM sbm_responses")->fetchColumn();
$currentSY = $db->query("SELECT label FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$myCreatedAt = $db->prepare("SELECT created_at FROM users WHERE user_id=?");
$myCreatedAt->execute([$_SESSION['user_id']]);
$uCreated = $myCreatedAt->fetchColumn();
$daysActive = $uCreated ? floor((time() - strtotime($uCreated)) / 86400) : 0;

$pageTitle = 'Settings';
$activePage = 'settings.php';
include __DIR__ . '/../includes/header.php';
?>
<style>
  .sy-row {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 12px;
    padding: 13px 20px;
    border-bottom: 1px solid var(--n-100);
    transition: background 120ms;
  }

  .sy-row:last-child {
    border-bottom: none;
  }

  .sy-row:hover {
    background: var(--n-50);
  }

  .sy-label {
    font-size: 14px;
    font-weight: 700;
    color: var(--n-900);
  }

  .sy-dates {
    font-size: 12px;
    color: var(--n-400);
    margin-top: 2px;
  }

  .sy-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-bottom: 1px solid var(--n-100);
  }

  .info-row:last-child {
    border-bottom: none;
  }

  .info-row:hover {
    background: var(--n-50);
  }

  .info-label {
    font-size: 13.5px;
    color: var(--n-600);
  }

  .info-value {
    font-size: 14px;
    font-weight: 700;
    color: var(--n-900);
  }
</style>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Administration</div>
    <div class="ph2-title">System Settings</div>
    <div class="ph2-sub">Manage school years, system configuration, and application metadata.</div>
  </div>
</div>

<div class="grid2" style="gap:20px;align-items:start;">

  <!-- School Years Panel -->
  <div class="settings-section">
    <div class="settings-section-header">
      <div class="settings-section-icon" style="background:var(--brand-100);color:var(--brand-700);">
        <svg viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
          <line x1="16" y1="2" x2="16" y2="6" />
          <line x1="8" y1="2" x2="8" y2="6" />
          <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
      </div>
      <div class="settings-section-info">
        <div class="settings-section-title">School Years</div>
        <div class="settings-section-desc">Manage assessment periods and set the active year.</div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="openModal('mSY');resetSY()"
        style="margin-left:auto;"><?= svgIcon('plus') ?> Add</button>
    </div>
    <?php foreach ($syears as $sy): ?>
      <div class="sy-row">
        <div>
          <div class="sy-label">
            <?= e($sy['label']) ?>
            <?php if ($sy['is_current']): ?><span class="pill pill-active"
                style="margin-left:6px;font-size:10.5px;">Current</span><?php endif; ?>
          </div>
          <div class="sy-dates">
            <?= $sy['date_start'] ? date('M d, Y', strtotime($sy['date_start'])) : '—' ?> →
            <?= $sy['date_end'] ? date('M d, Y', strtotime($sy['date_end'])) : 'Ongoing' ?>
          </div>
        </div>
        <div class="sy-actions">
          <?php if (!(int) $sy['is_current']): ?>
            <button class="btn btn-primary btn-sm"
              onclick="setCurrentSY(<?= $sy['sy_id'] ?>,'<?= e(addslashes($sy['label'])) ?>')">
              <?= svgIcon('check') ?> Set Current
            </button>
          <?php endif; ?>
          <button class="btn btn-danger btn-sm"
            onclick="delSY(<?= $sy['sy_id'] ?>,'<?= e(addslashes($sy['label'])) ?>')"><?= svgIcon('trash') ?></button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$syears): ?>
      <div class="empty-state" style="padding:32px;">
        <div class="empty-title">No school years</div>
        <div class="empty-sub">Add a school year to enable the assessment cycle.</div>
      </div>
    <?php endif; ?>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <!-- System Stats -->
    <div class="settings-section">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:var(--blue-bg);color:var(--blue);">
          <svg viewBox="0 0 24 24">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
          </svg>
        </div>
        <div class="settings-section-info">
          <div class="settings-section-title">System Information</div>
          <div class="settings-section-desc">Current data counts and application metadata.</div>
        </div>
      </div>
      <div class="info-row"><span class="info-label">Current School Year</span><span class="info-value"
          style="color:var(--brand-700);"><?= e($currentSY ?: 'Not set') ?></span></div>
      <div class="info-row"><span class="info-label">School</span><span class="info-value"
          style="color:var(--brand-700);">Dasmariñas Integrated High School</span></div>
      <div class="info-row"><span class="info-label">Total Users</span><span
          class="info-value"><?= number_format($userCount) ?> <span
            style="font-size:12px;color:var(--n-400);font-weight:400;">(<?= $activeUsers ?> active)</span></span></div>
      <div class="info-row"><span class="info-label">Overall Assessment Cycles</span><span
          class="info-value"><?= number_format($cycleCount) ?></span></div>
      <div class="info-row"><span class="info-label">Validated Cycles</span><span class="info-value"
          style="color:var(--brand-700);"><?= number_format($validatedCount) ?></span></div>
      <div class="info-row"><span class="info-label">Account Age</span><span class="info-value"
          style="color:var(--brand-700);"><?= number_format($daysActive) ?> days active</span></div>
      <div class="info-row"><span class="info-label">PHP Version</span><span class="info-value"
          style="font-family:monospace;font-size:13px;"><?= phpversion() ?></span></div>
      <div class="info-row"><span class="info-label">DepEd Order Reference</span><span class="info-value"
          style="font-size:13px;">No. 007, s. 2024</span></div>
    </div>

    <!-- Quick Links -->
    <div class="settings-section">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:var(--n-100);color:var(--n-600);">
          <svg viewBox="0 0 24 24">
            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" />
          </svg>
        </div>
        <div class="settings-section-info">
          <div class="settings-section-title">Quick Navigation</div>
          <div class="settings-section-desc">Jump to related configuration pages.</div>
        </div>
      </div>
      <div class="info-row"><a href="<?= baseUrl() ?>/system_admin/users.php"
          style="color:var(--brand-600);font-size:13.5px;font-weight:600;text-decoration:none;">User Management</a><span
          style="font-size:12px;color:var(--n-400);"><?= $userCount ?> users</span></div>
      <div class="info-row"><a href="<?= baseUrl() ?>/system_admin/dashboard.php"
          style="color:var(--brand-600);font-size:13.5px;font-weight:600;text-decoration:none;">System Admin
          Dashboard</a><span style="font-size:12px;color:var(--n-400);">Admin overview</span></div>
      <div class="info-row"><span
          style="color:var(--brand-600);font-size:13.5px;font-weight:600;text-decoration:none;">School Years</span><span
          style="font-size:12px;color:var(--n-400);"><?= count($syears) ?> configured</span></div>
      <div class="info-row"><span
          style="color:var(--brand-600);font-size:13.5px;font-weight:600;text-decoration:none;">Current Cycle
          Data</span><span style="font-size:12px;color:var(--n-400);"><?= $cycleCount ?> assessment cycles</span></div>
    </div>
  </div>
</div>

<!-- School Year Modal -->
<div class="overlay" id="mSY">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head"><span class="modal-title" id="mSYTitle">Add School Year</span><button class="modal-close"
        onclick="closeModal('mSY')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="sy_id">
      <div class="fg"><label>Label *</label><input class="fc" id="sy_label" placeholder="e.g. 2025–2026"></div>
      <div class="form-row">
        <div class="fg"><label>Start Date</label><input class="fc" type="date" id="sy_start"></div>
        <div class="fg"><label>End Date</label><input class="fc" type="date" id="sy_end"></div>
      </div>
      <div class="fg">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
          <input type="checkbox" id="sy_current"> Set as current school year
        </label>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSY')">Cancel</button>
      <button class="btn btn-primary" onclick="saveSY()"><?= svgIcon('save') ?> Save</button>
    </div>
  </div>
</div>

<script>
  function resetSY() { $v('sy_id', ''); $v('sy_label', ''); $v('sy_start', ''); $v('sy_end', ''); $el('sy_current').checked = false; $el('mSYTitle').textContent = 'Add School Year'; }
  async function saveSY() {
    const d = { action: 'save_sy', sy_id: $('sy_id'), label: $('sy_label'), date_start: $('sy_start'), date_end: $('sy_end'), is_current: $el('sy_current').checked ? 1 : 0 };
    const r = await apiPost('settings.php', d);
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mSY'); setTimeout(() => location.reload(), 800); }
  }
  async function editSY(id) {
    const r = await apiPost('settings.php', { action: 'get_sy', id });
    if (!r || !r.sy_id) { toast('Failed to load school year data.', 'err'); return; }
    $v('sy_id', r.sy_id);
    $v('sy_label', r.label || '');
    // date inputs require YYYY-MM-DD format; slice to strip time if present
    $v('sy_start', r.date_start ? r.date_start.slice(0, 10) : '');
    $v('sy_end', r.date_end ? r.date_end.slice(0, 10) : '');
    $el('sy_current').checked = (parseInt(r.is_current) === 1);
    $el('mSYTitle').textContent = 'Edit School Year';
    openModal('mSY');
  }
  async function delSY(id, label) {
    if (!confirm(`Delete school year "${label}"?\n\nAll related assessment cycles will also be removed.`)) return;
    const r = await apiPost('settings.php', { action: 'delete_sy', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }
  async function setCurrentSY(id, label) {
    if (!confirm(`Set "${label}" as the current school year?`)) return;
    const r = await apiPost('settings.php', { action: 'set_current_sy', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>