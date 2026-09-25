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
    $label = trim($_POST['label'] ?? '');
    if ($label === '') {
      echo json_encode(['ok' => false, 'msg' => 'School year label is required.']);
      exit;
    }
    // Validate date format if provided
    $dateStart = null;
    $dateEnd = null;
    if (!empty($_POST['date_start'])) {
      $dateStart = DateTime::createFromFormat('Y-m-d', $_POST['date_start']) ? $_POST['date_start'] : null;
    }
    if (!empty($_POST['date_end'])) {
      $dateEnd = DateTime::createFromFormat('Y-m-d', $_POST['date_end']) ? $_POST['date_end'] : null;
    }
    if ($dateStart && $dateEnd && $dateStart > $dateEnd) {
      echo json_encode(['ok' => false, 'msg' => 'End date must be after the start date.']);
      exit;
    }
    $db->prepare("INSERT INTO school_years (label,date_start,date_end,is_current) VALUES (?,?,?,0)")
      ->execute([$label, $dateStart, $dateEnd]);
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
  if ($_POST['action'] === 'archive_sy') {
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
      echo json_encode(['ok' => false, 'msg' => 'Cannot archive the current active school year.']);
      exit;
    }
    $db->prepare("UPDATE school_years SET is_archived = 1 WHERE sy_id = ?")->execute([$id]);
    echo json_encode(['ok' => true, 'msg' => 'School year archived.']);
    exit;
  }
  if ($_POST['action'] === 'unarchive_sy') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid ID.']);
      exit;
    }
    $check = $db->prepare("SELECT sy_id FROM school_years WHERE sy_id = ? AND is_archived = 1 LIMIT 1");
    $check->execute([$id]);
    if (!$check->fetchColumn()) {
      echo json_encode(['ok' => false, 'msg' => 'Archived school year not found.']);
      exit;
    }
    $restore = $db->prepare("UPDATE school_years SET is_archived = 0 WHERE sy_id = ? AND is_archived = 1");
    $restore->execute([$id]);
    if ($restore->rowCount() !== 1) {
      echo json_encode(['ok' => false, 'msg' => 'The school year could not be restored.']);
      exit;
    }
    echo json_encode(['ok' => true, 'msg' => 'School year restored.']);
    exit;
  }
  if ($_POST['action'] === 'save_maturity') {
    $bands = $_POST['bands'] ?? [];
    if (!is_array($bands) || count($bands) !== 3) {
      echo json_encode(['ok' => false, 'msg' => 'Exactly 3 maturity bands are required.']);
      exit;
    }
    // Validate and sort by min ascending
    $parsed = [];
    foreach ($bands as $b) {
      $min   = (float) ($b['min']   ?? 0);
      $max   = (float) ($b['max']   ?? 0);
      $level = (int)   ($b['level'] ?? 0);
      $label = trim($b['label'] ?? '');
      $color = trim($b['color'] ?? '#000000');
      $bg    = trim($b['bg']    ?? '#FFFFFF');
      if ($min < 0 || $max > 100 || $min >= $max || !$label || $level < 1 || $level > 3) {
        echo json_encode(['ok' => false, 'msg' => "Invalid band data for level $level."]);
        exit;
      }
      $parsed[] = compact('min', 'max', 'level', 'label', 'color', 'bg');
    }
    usort($parsed, fn($a,$b) => $a['min'] <=> $b['min']);
    // Ensure bands are contiguous (min of next == max of prev)
    for ($i = 1; $i < count($parsed); $i++) {
      if (abs($parsed[$i]['min'] - $parsed[$i-1]['max']) > 0.02) {
        echo json_encode(['ok' => false, 'msg' => 'Bands must be contiguous with no gaps or overlaps (e.g. 0-62.5, 62.5-87.5).']);
        exit;
      }
    }
    if ($parsed[0]['min'] > 0.01 || $parsed[count($parsed)-1]['max'] < 99.99) {
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
  if ($_POST['action'] === 'get_school_years') {
    $active = $db->query("SELECT sy_id, label, date_start, date_end, is_current, is_archived FROM school_years WHERE is_archived = 0 ORDER BY sy_id DESC")->fetchAll();
    $archived = $db->query("SELECT sy_id, label, date_start, date_end, is_current, is_archived FROM school_years WHERE is_archived = 1 ORDER BY sy_id DESC")->fetchAll();
    echo json_encode(['ok' => true, 'active' => $active, 'archived' => $archived]);
    exit;
  }
  if ($_POST['action'] === 'get_cycle_dates') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $st = $db->prepare("SELECT stakeholder_access_start, stakeholder_access_end, status FROM sbm_cycles WHERE cycle_id=? AND school_id=?");
    $st->execute([$cycleId, SCHOOL_ID]);
    $row = $st->fetch();
    echo json_encode($row ? ['ok' => true, 'dates' => $row] : ['ok' => false, 'msg' => 'Assessment cycle not found.']);
    exit;
  }
  if ($_POST['action'] === 'set_cycle_dates') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $start = trim($_POST['start_date'] ?? '');
    $end = trim($_POST['end_date'] ?? '');
    if (!$cycleId || !$end) {
      echo json_encode(['ok' => false, 'msg' => 'Cycle ID and Access End Date are required.']);
      exit;
    }
    $cycleExists = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE cycle_id=? AND school_id=? LIMIT 1");
    $cycleExists->execute([$cycleId, SCHOOL_ID]);
    $cycleRow = $cycleExists->fetch();
    if (!$cycleRow) {
      echo json_encode(['ok' => false, 'msg' => 'Assessment cycle not found.']);
      exit;
    }
    if ($cycleRow['status'] === 'finalized') {
      echo json_encode(['ok' => false, 'msg' => 'Finalized assessments cannot change the access window.']);
      exit;
    }
    $startDate = $start ? DateTime::createFromFormat('Y-m-d H:i:s', $start) : null;
    $endDate = DateTime::createFromFormat('Y-m-d H:i:s', $end);
    if (!$endDate || ($start && !$startDate)) {
      echo json_encode(['ok' => false, 'msg' => 'Please provide valid opening and closing date-times.']);
      exit;
    }
    if ($startDate && $endDate <= $startDate) {
      echo json_encode(['ok' => false, 'msg' => 'Closing date and time must be after the opening date and time.']);
      exit;
    }
    $db->prepare("UPDATE sbm_cycles SET stakeholder_access_start=?, stakeholder_access_end=?, auto_deactivated_at=NULL, auto_deactivated_by=NULL WHERE cycle_id=? AND school_id=?")
      ->execute([$start ?: null, $end, $cycleId, SCHOOL_ID]);
    echo json_encode(['ok' => true, 'msg' => 'Access window updated successfully.']);
    exit;
  }
  exit;
}

$syears = $db->query("SELECT * FROM school_years WHERE is_archived = 0 ORDER BY sy_id DESC")->fetchAll();
$archivedSyears = $db->query("SELECT * FROM school_years WHERE is_archived = 1 ORDER BY sy_id DESC")->fetchAll();

// Load saved maturity bands (fall back to DepEd defaults if not yet configured)
$maturityRow = $db->query("SELECT setting_value FROM system_settings WHERE setting_key='sbm_maturity_bands' LIMIT 1")->fetchColumn();
$maturityBands = $maturityRow ? json_decode($maturityRow, true) : [
    ['min'=>0.0,  'max'=>37.49, 'level'=>1, 'label'=>'Developing',           'color'=>'#D97706', 'bg'=>'#FEF3C7'],
    ['min'=>37.5, 'max'=>62.49, 'level'=>2, 'label'=>'Maturing',             'color'=>'#2563EB', 'bg'=>'#DBEAFE'],
    ['min'=>62.5, 'max'=>100.0, 'level'=>3, 'label'=>'Advanced (Accredited)', 'color'=>'#16A34A', 'bg'=>'#DCFCE7'],
];

$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$cycleCount = $db->query("SELECT COUNT(*) FROM sbm_cycles")->fetchColumn();
$validatedCount = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='validated'")->fetchColumn();
$responseCount = $db->query("SELECT COUNT(*) FROM sbm_responses")->fetchColumn();
$currentSchoolYear = $db->query("SELECT sy_id, label FROM school_years WHERE is_current=1 LIMIT 1")->fetch();
$currentSY = $currentSchoolYear['label'] ?? null;
$currentSyId = (int) ($currentSchoolYear['sy_id'] ?? 0);
$activeCycleForYear = null;
if ($currentSyId) {
  $cycleForYearStmt = $db->prepare("SELECT cycle_id, status, stakeholder_access_start, stakeholder_access_end
    FROM sbm_cycles WHERE school_id = ? AND sy_id = ? ORDER BY cycle_id DESC LIMIT 1");
  $cycleForYearStmt->execute([SCHOOL_ID, $currentSyId]);
  $activeCycleForYear = $cycleForYearStmt->fetch() ?: null;
  if (!$activeCycleForYear) {
    $db->prepare("INSERT INTO sbm_cycles (sy_id, school_id, status) VALUES (?, ?, 'draft')")
      ->execute([$currentSyId, SCHOOL_ID]);
  }
}
$cycles = $db->query("SELECT c.cycle_id, sy.label, c.status, c.stakeholder_access_start, c.stakeholder_access_end
  FROM sbm_cycles c JOIN school_years sy ON c.sy_id = sy.sy_id
  WHERE c.school_id = " . SCHOOL_ID . " ORDER BY c.cycle_id DESC")->fetchAll();
$activeCycleStmt = $db->prepare("SELECT c.cycle_id, sy.label, c.status, c.stakeholder_access_start, c.stakeholder_access_end
  FROM sbm_cycles c JOIN school_years sy ON c.sy_id = sy.sy_id
  WHERE c.school_id = ? AND sy.is_current = 1
  ORDER BY c.cycle_id DESC LIMIT 1");
$activeCycleStmt->execute([SCHOOL_ID]);
$activeCycle = $activeCycleStmt->fetch() ?: null;
$defaultCycleId = (int) ($activeCycle['cycle_id'] ?? 0);
$activeCycleIsFinalized = ($activeCycle['status'] ?? '') === 'finalized';
$accessWindowHistory = [];
$historyRows = $db->query("
  SELECT sy.sy_id, sy.label, c.stakeholder_access_start, c.stakeholder_access_end, c.cycle_id
  FROM school_years sy
  LEFT JOIN sbm_cycles c
    ON c.sy_id = sy.sy_id AND c.school_id = " . SCHOOL_ID . "
  ORDER BY sy.sy_id DESC, c.cycle_id DESC
")->fetchAll();
$seenHistoryYears = [];
$now = new DateTime();
foreach ($historyRows as $historyRow) {
  $syId = (int) $historyRow['sy_id'];
  if (isset($seenHistoryYears[$syId])) {
    continue;
  }
  $seenHistoryYears[$syId] = true;
  $opening = $historyRow['stakeholder_access_start'];
  $closing = $historyRow['stakeholder_access_end'];
  $status = 'Not Set';
  if ($opening && $closing) {
    $openingDate = new DateTime($opening);
    $closingDate = new DateTime($closing);
    if ($now >= $openingDate && $now <= $closingDate) {
      $status = 'Active';
    } elseif ($now > $closingDate) {
      $status = 'Closed';
    }
  }
  $accessWindowHistory[] = [
    'label' => $historyRow['label'],
    'opening' => $opening,
    'closing' => $closing,
    'status' => $status,
  ];
}
$myCreatedAt = $db->prepare("SELECT created_at FROM users WHERE user_id=?");
$myCreatedAt->execute([$_SESSION['user_id']]);
$uCreated = $myCreatedAt->fetchColumn();
$daysActive = $uCreated ? floor((time() - strtotime($uCreated)) / 86400) : 0;

$settingsSection = $_GET['section'] ?? 'school_years';
$validSettingsSections = ['school_years', 'maturity_bands', 'assessment_cycle', 'system_information'];
if (!in_array($settingsSection, $validSettingsSections, true)) {
  $settingsSection = 'school_years';
}

$pageTitle = 'Settings';
$activePage = 'settings.php';
include __DIR__ . '/../includes/header.php';
?>
<?php
function renderSchoolYearTable(array $years, bool $archived): void
{
  if (!$years) {
    echo '<div class="empty-state" style="padding:32px;"><div class="empty-title">' .
      ($archived ? 'No archived school years' : 'No school years') .
      '</div><div class="empty-sub">' .
      ($archived ? '' : 'Add a school year to enable the assessment cycle.') .
      '</div></div>';
    return;
  }
  ?>
  <div class="tbl-wrap">
    <table class="tbl-enhanced sy-table" style="width:100%;">
      <thead><tr>
        <th>School Year</th><th>Start Date</th><th>End Date</th><th>Status</th><th style="text-align:center;">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($years as $sy): ?>
        <tr>
          <td><?= e($sy['label']) ?></td>
          <td class="sy-date"><?= $sy['date_start'] ? date('M d, Y', strtotime($sy['date_start'])) : '—' ?></td>
          <td class="sy-date"><?= $sy['date_end'] ? date('M d, Y', strtotime($sy['date_end'])) : 'Ongoing' ?></td>
          <td class="sy-status"><?php if ((int)$sy['is_current'] === 1): ?><span class="pill pill-active">Current</span><?php elseif (!$archived): ?>Available<?php endif; ?></td>
          <td class="sy-actions">
            <?php if ($archived): ?>
              <button class="btn btn-secondary btn-sm" title="Restore" aria-label="Restore school year"
                onclick="unarchiveSY(<?= (int)$sy['sy_id'] ?>,<?= htmlspecialchars(json_encode($sy['label']), ENT_QUOTES, 'UTF-8') ?>)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
              </button>
            <?php elseif ((int)$sy['is_current'] !== 1): ?>
              <button class="btn btn-primary btn-sm" title="Set Current" aria-label="Set current school year"
                onclick="setCurrentSY(<?= (int)$sy['sy_id'] ?>,<?= htmlspecialchars(json_encode($sy['label']), ENT_QUOTES, 'UTF-8') ?>)">
                <?= svgIcon('check') ?>
              </button>
              <button class="btn btn-danger btn-sm" title="Archive" aria-label="Archive school year"
                onclick="delSY(<?= (int)$sy['sy_id'] ?>,<?= htmlspecialchars(json_encode($sy['label']), ENT_QUOTES, 'UTF-8') ?>)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
}
?>
<style>
  .sy-create-card, .sy-list-card { background:#fff; border:1px solid var(--n-150,#e5e7eb); border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm); }
  .sy-create-header, .sy-list-toolbar { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid var(--n-100); }
  .sy-create-header { font-size:14px; font-weight:700; color:var(--n-900); }
  .sy-create-header svg { width:16px; height:16px; stroke:var(--n-700); }
  .sy-create-form { padding:18px 20px 20px; }
  .sy-create-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:end; }
  .sy-create-label, .sy-create-dates, .sy-save { grid-column:1 / -1; }
  .sy-create-dates { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .sy-table td:first-child { font-weight: 700; color: var(--n-900); }
  .sy-table .sy-date { color: var(--n-500); font-size: 12px; }
  .sy-table .sy-status { color: var(--n-400); font-size: 12px; }
  .sy-table .sy-actions { text-align: center; white-space: nowrap; }
  .sy-list-card { margin-top:20px; }
  @media (max-width: 760px) {
    .sy-create-grid, .sy-create-dates { grid-template-columns:1fr; }
    .sy-create-grid .sy-save { grid-column:1; justify-self:stretch; }
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

  .settings-dtp-wrap {
    position: relative;
  }

  .settings-dtp-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 42px;
    opacity: 0;
    color: transparent;
    font-size: 0;
    -webkit-text-fill-color: transparent;
    cursor: pointer;
    z-index: 2;
  }

  .settings-dtp-input::-webkit-datetime-edit,
  .settings-dtp-input::-webkit-datetime-edit-fields-wrapper,
  .settings-dtp-input::-webkit-datetime-edit-text,
  .settings-dtp-input::-webkit-datetime-edit-month-field,
  .settings-dtp-input::-webkit-datetime-edit-day-field,
  .settings-dtp-input::-webkit-datetime-edit-year-field,
  .settings-dtp-input::-webkit-datetime-edit-hour-field,
  .settings-dtp-input::-webkit-datetime-edit-minute-field,
  .settings-dtp-input::-webkit-datetime-edit-ampm-field {
    color: transparent;
    -webkit-text-fill-color: transparent;
  }

  .settings-dtp-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    height: 42px;
    padding: 10px 14px;
    border: 1.5px solid var(--n-200);
    border-radius: 12px;
    background: #fff;
    color: var(--n-900);
    text-align: left;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
  }

  .settings-dtp-trigger:hover {
    border-color: var(--brand-500);
    background: var(--brand-50);
  }

  .settings-dtp-trigger svg {
    flex-shrink: 0;
    stroke: var(--brand-600);
  }

  .settings-dtp-label {
    color: var(--n-400);
    font-weight: 400;
  }

  .settings-dtp-label.has-value {
    color: var(--n-900);
    font-weight: 600;
  }
</style>

<div class="grid2" style="gap:20px;align-items:start;grid-template-columns:1fr;">

  <!-- Left Column: School Years + Maturity Bands -->
  <div style="display:flex;flex-direction:column;gap:20px;">

  <!-- School Years Panel -->
  <?php if ($settingsSection === 'school_years'): ?>
  <div class="sy-page-section">
    <div class="sy-create-card">
      <div class="sy-create-header">
        <span>Add School Year</span>
      </div>
      <div class="sy-create-form">
        <div class="sy-create-grid">
          <div class="fg sy-create-label"><label>Label *</label><input class="fc" id="sy_label" placeholder="e.g. 2025-2026"></div>
          <div class="sy-create-dates">
            <div class="fg"><label>Start Date</label><input class="fc" type="date" id="sy_start" onclick="openSYDatePicker(this)"></div>
            <div class="fg"><label>End Date</label><input class="fc" type="date" id="sy_end" onclick="openSYDatePicker(this)"></div>
          </div>
          <div class="sy-save" style="display:flex;justify-content:flex-end;">
            <button class="btn btn-primary" type="button" onclick="saveSY()"><?= svgIcon('save') ?> Save</button>
          </div>
        </div>
      </div>
    </div>
    <div class="sy-list-card">
      <div class="sy-list-toolbar" id="syArchivedToolbar" style="<?= count($archivedSyears) ? '' : 'display:none;' ?>">
        <button class="btn btn-secondary btn-sm" id="btnToggleArchivedSY" onclick="toggleArchivedSY()" style="<?= count($archivedSyears) ? '' : 'display:none;' ?>">
          View Archived (<?= count($archivedSyears) ?>)
        </button>
      </div>
      <div id="syActiveList">
        <?php renderSchoolYearTable($syears, false); ?>
      </div>
      <div id="syArchivedList" style="display:none;">
        <?php renderSchoolYearTable($archivedSyears, true); ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Maturity Level Configuration Panel -->
  <?php if ($settingsSection === 'maturity_bands'): ?>
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
  <?php endif; ?>

  </div><!-- end left column -->

  <!-- Right Column: System Info + Quick Links -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <!-- Assessment Cycle & Access Window Panel -->
    <?php if ($settingsSection === 'assessment_cycle'): ?>
    <div class="settings-section">
      <div class="settings-section-header">
        <div class="settings-section-icon" style="background:var(--brand-100);color:var(--brand-700);">
          <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
        </div>
        <div class="settings-section-info">
          <div class="settings-section-title">Assessment Cycle &amp; Access Window</div>
          <div class="settings-section-desc">Configure when external stakeholders can access each cycle.</div>
        </div>
      </div>
      <div style="padding:16px 20px;">
        <div class="fg" style="margin-bottom:14px;">
          <label>Assessment Cycle</label>
          <div class="fc" style="display:flex;align-items:center;min-height:42px;background:var(--n-50);font-weight:700;">
            <?= $activeCycle ? 'SY ' . e($activeCycle['label']) : 'No active assessment cycle' ?>
          </div>
        </div>
        <div class="form-row" style="gap:12px;">
          <div class="fg" style="margin-bottom:0;">
            <label>Opening Date &amp; Time *</label>
            <div class="settings-dtp-wrap">
              <input class="settings-dtp-input" type="datetime-local" id="settings_access_start" onchange="updateSettingsDateLabel('settings_access_start', 'settings_access_start_label')" style="pointer-events:none;" <?= $activeCycleIsFinalized ? 'disabled' : '' ?>>
              <button class="settings-dtp-trigger" type="button" <?= $activeCycleIsFinalized ? 'disabled style="background:var(--n-100);border-color:var(--n-200);opacity:.65;cursor:not-allowed;"' : 'onclick="document.getElementById(\'settings_access_start\').showPicker ? document.getElementById(\'settings_access_start\').showPicker() : document.getElementById(\'settings_access_start\').click()"' ?>>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
                <span class="settings-dtp-label" id="settings_access_start_label">Pick opening date &amp; time</span>
              </button>
            </div>
          </div>
          <div class="fg" style="margin-bottom:0;">
            <label>Closing Date &amp; Time *</label>
            <div class="settings-dtp-wrap">
              <input class="settings-dtp-input" type="datetime-local" id="settings_access_end" onchange="updateSettingsDateLabel('settings_access_end', 'settings_access_end_label')" style="pointer-events:none;" <?= $activeCycleIsFinalized ? 'disabled' : '' ?>>
              <button class="settings-dtp-trigger" type="button" <?= $activeCycleIsFinalized ? 'disabled style="background:var(--n-100);border-color:var(--n-200);opacity:.65;cursor:not-allowed;"' : 'onclick="document.getElementById(\'settings_access_end\').showPicker ? document.getElementById(\'settings_access_end\').showPicker() : document.getElementById(\'settings_access_end\').click()"' ?>>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
                <span class="settings-dtp-label" id="settings_access_end_label">Pick closing date &amp; time</span>
              </button>
            </div>
          </div>
        </div>
        <div id="settings_datetime_error" style="display:none;margin-top:10px;color:#B91C1C;font-size:12.5px;"></div>
        <button class="btn btn-primary btn-sm" id="settings_save_window" onclick="saveSettingsCycleDates()" style="margin-top:14px;<?= $activeCycleIsFinalized ? 'background:var(--n-300);border-color:var(--n-300);color:var(--n-600);opacity:.65;cursor:not-allowed;' : '' ?>" <?= $activeCycleIsFinalized ? 'disabled' : '' ?>>
          <?= svgIcon('save') ?> Save Access Window
        </button>
      </div>
    </div>
    <div class="settings-section">
      <div class="settings-section-header">
        <div class="settings-section-info">
          <div class="settings-section-title">Access Window History</div>
          <div class="settings-section-desc">Read-only history of access windows for all school years.</div>
        </div>
      </div>
      <div class="tbl-wrap">
        <table class="tbl-enhanced" style="width:100%;">
          <thead>
            <tr>
              <th>School Year</th>
              <th>Opening Date &amp; Time</th>
              <th>Closing Date &amp; Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$accessWindowHistory): ?>
              <tr><td colspan="4" style="text-align:center;color:var(--n-400);padding:28px;">No school years available.</td></tr>
            <?php else: ?>
              <?php foreach ($accessWindowHistory as $history): ?>
                <?php
                  $statusStyle = $history['status'] === 'Active'
                    ? 'background:#DCFCE7;color:#166534;'
                    : ($history['status'] === 'Closed' ? 'background:#F1F5F9;color:#64748B;' : 'background:#F8FAFC;color:#94A3B8;');
                ?>
                <tr>
                  <td style="font-weight:700;color:var(--n-900);">SY <?= e($history['label']) ?></td>
                  <td style="font-size:12px;color:var(--n-500);">
                    <?= $history['opening'] ? e(date('M d, Y g:i A', strtotime($history['opening']))) : '—' ?>
                  </td>
                  <td style="font-size:12px;color:var(--n-500);">
                    <?= $history['closing'] ? e(date('M d, Y g:i A', strtotime($history['closing']))) : '—' ?>
                  </td>
                  <td><span class="status-badge" style="<?= $statusStyle ?>"><?= e($history['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
    <!-- System Stats -->
    <?php if ($settingsSection === 'system_information'): ?>
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
    <?php endif; ?>
  </div>
</div>

<!-- Archive SY Confirm Modal -->
<div class="overlay" id="mArchiveSY">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head">
      <span class="modal-title">Archive School Year</span>
      <button class="modal-close" onclick="closeModal('mArchiveSY')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <p id="archiveSYText" style="font-size:14.5px;color:var(--n700);line-height:1.5;"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mArchiveSY')">Cancel</button>
      <button class="btn btn-danger" type="button" onclick="confirmArchiveSY()">Yes, Archive</button>
    </div>
  </div>
</div>

<!-- Restore SY Confirm Modal -->
<div class="overlay" id="mRestoreSY">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head">
      <span class="modal-title">Restore School Year</span>
      <button class="modal-close" onclick="closeModal('mRestoreSY')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <p id="restoreSYText" style="font-size:14.5px;color:var(--n700);line-height:1.5;"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mRestoreSY')">Cancel</button>
      <button class="btn btn-primary" type="button" onclick="confirmRestoreSY()">Yes, Restore</button>
    </div>
  </div>
</div>

<!-- Maturity Bands Modal -->
<div class="overlay" id="mSetCurrentSY">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head">
      <span class="modal-title">
        Set Current School Year
      </span>
      <button class="modal-close" onclick="closeModal('mSetCurrentSY')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">
      <p id="setCurrentSYText" style="font-size:14.5px; color:var(--n700); line-height:1.5;"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSetCurrentSY')">
        Cancel
      </button>
      <button class="btn btn-primary" type="button" onclick="confirmSetCurrentSY()">
        Yes, Set Current
      </button>
    </div>
  </div>
</div>

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

<script>
  function openSYDatePicker(input) {
    if (typeof input.showPicker === 'function') {
      try { input.showPicker(); } catch (error) { input.focus(); }
    } else {
      input.focus();
    }
  }

  function updateSettingsCycleState(start, end) {
    const startInput = $el('settings_access_start');
    const endInput = $el('settings_access_end');
    if (startInput) startInput.value = start ? start.replace(' ', 'T').slice(0, 16) : '';
    if (endInput) endInput.value = end ? end.replace(' ', 'T').slice(0, 16) : '';
    updateSettingsDateLabel('settings_access_start', 'settings_access_start_label');
    updateSettingsDateLabel('settings_access_end', 'settings_access_end_label');
  }

  function validateSettingsDates(showError = true) {
    const start = $('settings_access_start');
    const end = $('settings_access_end');
    const error = $el('settings_datetime_error');
    const message = start && end && new Date(start) >= new Date(end)
      ? 'Closing date and time must be after the opening date and time.'
      : '';
    if (error) {
      error.textContent = message;
      error.style.display = message && showError ? 'block' : 'none';
    }
    return !message;
  }

  function updateSettingsDateLabel(inputId, labelId) {
    const input = $el(inputId);
    const label = $el(labelId);
    if (!input || !label) return;
    if (!input.value) {
      label.textContent = inputId.endsWith('start') ? 'Pick opening date & time' : 'Pick closing date & time';
      label.classList.remove('has-value');
      return;
    }
    const value = new Date(input.value);
    label.textContent = value.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    label.classList.add('has-value');
  }

  async function loadSettingsCycleDates() {
    const cycleId = <?= $defaultCycleId ?>;
    if (!cycleId) return;
    const r = await apiPost('settings.php', { action: 'get_cycle_dates', cycle_id: cycleId });
    if (!r || !r.ok) {
      toast(r?.msg || 'Failed to load assessment cycle dates.', 'err');
      return;
    }
    updateSettingsCycleState(r.dates.stakeholder_access_start, r.dates.stakeholder_access_end);
  }

  async function saveSettingsCycleDates() {
    const start = $('settings_access_start').replace('T', ' ');
    const end = $('settings_access_end').replace('T', ' ');
    if (!start) {
      toast('Access opening date and time are required.', 'warning');
      return;
    }
    if (!end) {
      toast('Access end date and time are required.', 'warning');
      return;
    }
    if (!validateSettingsDates()) {
      return;
    }
    const r = await apiPost('settings.php', {
      action: 'set_cycle_dates',
      cycle_id: <?= $defaultCycleId ?>,
      start_date: start ? start + ':00' : '',
      end_date: end + ':00'
    });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) loadSettingsCycleDates();
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (<?= $defaultCycleId ?>) loadSettingsCycleDates();
    ['settings_access_start', 'settings_access_end'].forEach(id => {
      $el(id)?.addEventListener('change', () => validateSettingsDates());
    });
  });

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
              <input class="fc" type="number" min="0" max="100" step="0.01" id="mat_min_${i}" value="${b.min}"
                style="width:80px;text-align:center;margin:0 auto;">
            </td>
            <td style="padding:10px 12px;text-align:center;">
              <input class="fc" type="number" min="0" max="100" step="0.01" id="mat_max_${i}" value="${b.max}"
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
    const bands = (r && r.bands && r.bands.length === 3) ? r.bands : DEFAULT_BANDS;
    buildMaturityForm(bands);
    openModal('mMaturity');
  }

  async function saveMaturity() {
    const bands = [];
    for (let i = 0; i < 3; i++) {
      bands.push({
        level: parseInt(document.getElementById(`mat_level_${i}`).value),
        label: document.getElementById(`mat_label_${i}`).value,
        min:   parseFloat(document.getElementById(`mat_min_${i}`).value),
        max:   parseFloat(document.getElementById(`mat_max_${i}`).value),
        color: document.getElementById(`mat_color_${i}`).value,
        bg:    document.getElementById(`mat_bg_${i}`).value,
      });
    }
    const r = await apiPost('settings.php', { action: 'save_maturity', bands });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mMaturity'); setTimeout(() => location.reload(), 800); }
  }

  // ── School Years ──────────────────────────────────────────────────
  function escapeSY(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
  }
  function formatSYDate(value, ongoing) {
    if (!value) return ongoing ? 'Ongoing' : '—';
    const date = new Date(`${value}T00:00:00`);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }
  function renderSYTable(years, archived) {
    const target = document.getElementById(archived ? 'syArchivedList' : 'syActiveList');
    if (!years.length) {
      target.innerHTML = `<div class="empty-state" style="padding:32px;"><div class="empty-title">${archived ? 'No archived school years' : 'No school years'}</div><div class="empty-sub">${archived ? '' : 'Add a school year to enable the assessment cycle.'}</div></div>`;
      return;
    }
    const rows = years.map(sy => {
      const label = escapeSY(sy.label);
      const jsLabel = JSON.stringify(String(sy.label)).replace(/</g, '\\u003c');
      let actions = '';
      if (archived) {
        actions = `<button class="btn btn-secondary btn-sm" title="Restore" aria-label="Restore school year" onclick="unarchiveSY(${Number(sy.sy_id)},${jsLabel})"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg></button>`;
      } else if (Number(sy.is_current) !== 1) {
        actions = `<button class="btn btn-primary btn-sm" title="Set Current" aria-label="Set current school year" onclick="setCurrentSY(${Number(sy.sy_id)},${jsLabel})">${svgI('check')}</button><button class="btn btn-danger btn-sm" title="Archive" aria-label="Archive school year" onclick="delSY(${Number(sy.sy_id)},${jsLabel})"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg></button>`;
      }
      return `<tr><td>${label}</td><td class="sy-date">${formatSYDate(sy.date_start, false)}</td><td class="sy-date">${formatSYDate(sy.date_end, true)}</td><td class="sy-status">${Number(sy.is_current) === 1 ? '<span class="pill pill-active">Current</span>' : (archived ? '' : 'Available')}</td><td class="sy-actions">${actions}</td></tr>`;
    }).join('');
    target.innerHTML = `<div class="tbl-wrap"><table class="tbl-enhanced sy-table" style="width:100%;"><thead><tr><th>School Year</th><th>Start Date</th><th>End Date</th><th>Status</th><th style="text-align:center;">Actions</th></tr></thead><tbody>${rows}</tbody></table></div>`;
  }
  async function refreshSchoolYears(forceActive = false) {
    const r = await apiPost('settings.php', { action: 'get_school_years' });
    if (!r || !r.ok) return;
    renderSYTable(r.active || [], false);
    renderSYTable(r.archived || [], true);
    const btn = document.getElementById('btnToggleArchivedSY');
    const toolbar = document.getElementById('syArchivedToolbar');
    if (btn) {
      const archivedCount = (r.archived || []).length;
      btn.dataset.viewLabel = `View Archived (${archivedCount})`;
      btn.style.display = archivedCount ? '' : 'none';
      if (toolbar) toolbar.style.display = archivedCount ? '' : 'none';
      const shouldShowArchived = !forceActive && sessionStorage.getItem('sy_view') === 'archived' && archivedCount > 0;
      document.getElementById('syActiveList').style.display = shouldShowArchived ? 'none' : '';
      document.getElementById('syArchivedList').style.display = shouldShowArchived ? '' : 'none';
      btn.textContent = shouldShowArchived ? 'Back to Active' : btn.dataset.viewLabel;
      sessionStorage.setItem('sy_view', shouldShowArchived ? 'archived' : 'active');
    }
  }
  async function saveSY() {
    const d = { action: 'save_sy', label: $('sy_label'), date_start: $('sy_start'), date_end: $('sy_end') };
    const r = await apiPost('settings.php', d);
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      $v('sy_label', ''); $v('sy_start', ''); $v('sy_end', '');
      await refreshSchoolYears();
    }
  }
  function delSY(id, label) {
    document.getElementById('mArchiveSY').dataset.id = id;
    document.getElementById('archiveSYText').textContent = `Are you sure you want to archive "${label}"? It will be hidden from the active list, but its data is kept.`;
    openModal('mArchiveSY');
  }
  async function confirmArchiveSY() {
    const id = document.getElementById('mArchiveSY').dataset.id;
    const r = await apiPost('settings.php', { action: 'archive_sy', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mArchiveSY'); await refreshSchoolYears(); }
  }
  function unarchiveSY(id, label) {
    document.getElementById('mRestoreSY').dataset.id = id;
    document.getElementById('restoreSYText').textContent = `Are you sure you want to restore "${label}" to the active list?`;
    openModal('mRestoreSY');
  }
  async function confirmRestoreSY() {
    const id = document.getElementById('mRestoreSY').dataset.id;
    if (!id) {
      toast('No archived school year was selected.', 'err');
      return;
    }
    const r = await apiPost('settings.php', { action: 'unarchive_sy', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mRestoreSY'); await refreshSchoolYears(true); }
  }
  function toggleArchivedSY(forceView) {
    const active = document.getElementById('syActiveList');
    const archived = document.getElementById('syArchivedList');
    const btn = document.getElementById('btnToggleArchivedSY');
    const showingArchived = forceView ? forceView !== 'archived' : archived.style.display !== 'none';
    active.style.display = showingArchived ? '' : 'none';
    archived.style.display = showingArchived ? 'none' : '';
    btn.textContent = showingArchived ? btn.dataset.viewLabel : 'Back to Active';
    sessionStorage.setItem('sy_view', showingArchived ? 'active' : 'archived');
  }
  (function () {
    const btn = document.getElementById('btnToggleArchivedSY');
    if (!btn) return;
    btn.dataset.viewLabel = btn.textContent.trim();
    if (sessionStorage.getItem('sy_view') === 'archived') toggleArchivedSY('active');
  })();
  function setCurrentSY(id, label) {
    document.getElementById('mSetCurrentSY').dataset.id = id;
    document.getElementById('setCurrentSYText').textContent = `Set "${label}" as the current school year?`;
    openModal('mSetCurrentSY');
  }
  async function confirmSetCurrentSY() {
    const id = document.getElementById('mSetCurrentSY').dataset.id;
    closeModal('mSetCurrentSY');
    const r = await apiPost('settings.php', { action: 'set_current_sy', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) await refreshSchoolYears();
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>