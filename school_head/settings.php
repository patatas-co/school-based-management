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
  if ($_POST['action'] === 'save_maturity') {
    $bands = $_POST['bands'] ?? [];
    if (!is_array($bands) || count($bands) !== 4) {
      echo json_encode(['ok' => false, 'msg' => 'Exactly 4 maturity bands are required.']);
      exit;
    }
    // Validate and sort by min ascending
    $parsed = [];
    foreach ($bands as $b) {
      $min   = (int)   ($b['min']   ?? 0);
      $max   = (int)   ($b['max']   ?? 0);
      $level = (int)   ($b['level'] ?? 0);
      $label = trim($b['label'] ?? '');
      $color = trim($b['color'] ?? '#000000');
      $bg    = trim($b['bg']    ?? '#FFFFFF');
      if ($min < 0 || $max > 100 || $min >= $max || !$label || $level < 1 || $level > 4) {
        echo json_encode(['ok' => false, 'msg' => "Invalid band data for level $level."]);
        exit;
      }
      $parsed[] = compact('min', 'max', 'level', 'label', 'color', 'bg');
    }
    usort($parsed, fn($a,$b) => $a['min'] <=> $b['min']);
    // Ensure bands are contiguous (max of prev == min of next - 1)
    for ($i = 1; $i < count($parsed); $i++) {
      if ($parsed[$i]['min'] !== $parsed[$i-1]['max'] + 1) {
        echo json_encode(['ok' => false, 'msg' => 'Bands must be contiguous with no gaps or overlaps (e.g. 0-25, 26-50).']);
        exit;
      }
    }
    if ($parsed[0]['min'] !== 0 || $parsed[count($parsed)-1]['max'] !== 100) {
      echo json_encode(['ok' => false, 'msg' => 'Bands must cover 0–100 exactly.']);
      exit;
    }
    $db->prepare("DELETE FROM system_settings WHERE setting_key = 'sbm_maturity_bands'")->execute();
    $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('sbm_maturity_bands', ?)")
      ->execute([json_encode($parsed)]);
    echo json_encode(['ok' => true, 'msg' => 'Maturity bands saved.']);
    exit;
  }
  if ($_POST['action'] === 'get_maturity') {
    $row = $db->query("SELECT setting_value FROM system_settings WHERE setting_key='sbm_maturity_bands' LIMIT 1")->fetchColumn();
    echo json_encode(['ok' => true, 'bands' => $row ? json_decode($row, true) : []]);
    exit;
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

// Load saved maturity bands (fall back to DepEd defaults if not yet configured)
$maturityRow = $db->query("SELECT setting_value FROM system_settings WHERE setting_key='sbm_maturity_bands' LIMIT 1")->fetchColumn();
$maturityBands = $maturityRow ? json_decode($maturityRow, true) : [
    ['min'=>0,  'max'=>25,  'level'=>1, 'label'=>'Beginning',  'color'=>'#DC2626', 'bg'=>'#FEE2E2'],
    ['min'=>26, 'max'=>50,  'level'=>2, 'label'=>'Developing', 'color'=>'#D97706', 'bg'=>'#FEF3C7'],
    ['min'=>51, 'max'=>75,  'level'=>3, 'label'=>'Maturing',   'color'=>'#2563EB', 'bg'=>'#DBEAFE'],
    ['min'=>76, 'max'=>100, 'level'=>4, 'label'=>'Advanced',   'color'=>'#16A34A', 'bg'=>'#DCFCE7'],
];

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

<div class="grid2" style="gap:20px;align-items:start;">

  <!-- Left Column: School Years + Maturity Bands -->
  <div style="display:flex;flex-direction:column;gap:20px;">

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

  <!-- Maturity Level Configuration Panel -->
  <div class="settings-section">
    <div class="settings-section-header">
      <div class="settings-section-icon" style="background:var(--brand-100);color:var(--brand-700);">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
      <div class="settings-section-info">
        <div class="settings-section-title">Maturity Level Bands</div>
        <div class="settings-section-desc">Configure score ranges for each SBM maturity level.</div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="openMaturityModal()" style="margin-left:auto;">
        <?= svgIcon('edit') ?> Edit
      </button>
    </div>
    <?php foreach ($maturityBands as $band): ?>
      <div class="info-row">
        <span class="info-label" style="display:flex;align-items:center;gap:8px;">
          <strong>Level <?= (int)$band['level'] ?></strong> — <?= e($band['label']) ?>
        </span>
        <span class="info-value" style="font-family:monospace;font-size:13px;">
          <?= (int)$band['min'] ?>% – <?= (int)$band['max'] ?>%
        </span>
      </div>
    <?php endforeach; ?>
  </div><!-- end maturity panel -->

  </div><!-- end left column -->

  <!-- Right Column: System Info + Quick Links -->
  <div style="display:flex;flex-direction:column;gap:20px;">
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
  </div>
</div>

<!-- Maturity Bands Modal -->
<div class="overlay" id="mMaturity">
  <div class="modal" style="max-width:600px;">
    <div class="modal-head">
      <span class="modal-title">Edit Maturity Level Bands</span>
      <button class="modal-close" onclick="closeModal('mMaturity')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--n-500);margin-bottom:16px;">
        Bands must be contiguous and cover <strong>0–100%</strong> with no gaps or overlaps.
      </p>
      <div id="maturityBandsForm"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mMaturity')">Cancel</button>
      <button class="btn btn-primary" onclick="saveMaturity()"><?= svgIcon('save') ?> Save</button>
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
  // ── Maturity Bands ────────────────────────────────────────────────
  const DEFAULT_BANDS = <?= json_encode(array_values($maturityBands)) ?>;

  function buildMaturityForm(bands) {
    let html = `
      <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
        <thead>
          <tr style="background:var(--n-50);border-bottom:2px solid var(--n-200);">
            <th style="padding:10px 12px;text-align:left;font-weight:600;color:var(--n-600);">Level</th>
            <th style="padding:10px 12px;text-align:left;font-weight:600;color:var(--n-600);">Band Name</th>
            <th style="padding:10px 12px;text-align:center;font-weight:600;color:var(--n-600);">Min Score (%)</th>
            <th style="padding:10px 12px;text-align:center;font-weight:600;color:var(--n-600);">Max Score (%)</th>
          </tr>
        </thead>
        <tbody>`;
    bands.forEach((b, i) => {
      html += `
          <tr style="border-bottom:1px solid var(--n-200);">
            <td style="padding:10px 12px;font-weight:600;color:var(--n-700);">Level ${b.level}</td>
            <td style="padding:10px 12px;color:var(--n-700);">${b.label}</td>
            <td style="padding:10px 12px;text-align:center;">
              <input class="fc" type="number" min="0" max="100" id="mat_min_${i}" value="${b.min}"
                style="width:80px;text-align:center;margin:0 auto;">
            </td>
            <td style="padding:10px 12px;text-align:center;">
              <input class="fc" type="number" min="0" max="100" id="mat_max_${i}" value="${b.max}"
                style="width:80px;text-align:center;margin:0 auto;">
            </td>
          </tr>
          <input type="hidden" id="mat_level_${i}" value="${b.level}">
          <input type="hidden" id="mat_label_${i}" value="${b.label}">
          <input type="hidden" id="mat_color_${i}" value="${b.color}">
          <input type="hidden" id="mat_bg_${i}" value="${b.bg}">`;
    });
    html += `
        </tbody>
      </table>`;
    document.getElementById('maturityBandsForm').innerHTML = html;
  }

  async function openMaturityModal() {
    const r = await apiPost('settings.php', { action: 'get_maturity' });
    const bands = (r && r.bands && r.bands.length === 4) ? r.bands : DEFAULT_BANDS;
    buildMaturityForm(bands);
    openModal('mMaturity');
  }

  async function saveMaturity() {
    const bands = [];
    for (let i = 0; i < 4; i++) {
      bands.push({
        level: parseInt(document.getElementById(`mat_level_${i}`).value),
        label: document.getElementById(`mat_label_${i}`).value,
        min:   parseInt(document.getElementById(`mat_min_${i}`).value),
        max:   parseInt(document.getElementById(`mat_max_${i}`).value),
        color: document.getElementById(`mat_color_${i}`).value,
        bg:    document.getElementById(`mat_bg_${i}`).value,
      });
    }
    const r = await apiPost('settings.php', { action: 'save_maturity', bands });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mMaturity'); setTimeout(() => location.reload(), 800); }
  }

  // ── School Years ──────────────────────────────────────────────────
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