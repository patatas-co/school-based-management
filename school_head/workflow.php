<?php
ob_start();
// ============================================================
// school_head/workflow.php — SBM Workflow & Timeline
// DepEd Order No. 007, s. 2024 — 3-Step SBM Cycle
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();
require_once __DIR__ . '/../includes/workflow_actions.php';


$schoolId = SCHOOL_ID;
$syId = (int) ($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());
$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
$currentSY = $db->prepare("SELECT * FROM school_years WHERE sy_id=?");
$currentSY->execute([$syId]);
$currentSY = $currentSY->fetch();

// ── AJAX: save timeline entry ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
  verifyCsrf();
  handleWorkflowPost($db); // Core workflow actions


  if ($_POST['action'] === 'save_milestone') {
    $id = (int) ($_POST['milestone_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $start = $_POST['date_start'] ?: null;
    $end = $_POST['date_end'] ?: null;
    $step = (int) ($_POST['step'] ?? 1);
    $status = in_array($_POST['status'], ['upcoming', 'in_progress', 'completed', 'delayed'])
      ? $_POST['status'] : 'upcoming';

    if (!$title) {
      echo json_encode(['ok' => false, 'msg' => 'Title is required.']);
      exit;
    }

    if ($id) {
      $db->prepare("UPDATE workflow_milestones SET title=?,description=?,date_start=?,date_end=?,step_no=?,status=? WHERE milestone_id=? AND sy_id=?")
        ->execute([$title, $desc, $start, $end, $step, $status, $id, $syId]);
    } else {
      $db->prepare("INSERT INTO workflow_milestones (sy_id,school_id,title,description,date_start,date_end,step_no,status,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$syId, $schoolId, $title, $desc, $start, $end, $step, $status, $_SESSION['user_id']]);
    }
    logActivity('save_milestone', 'workflow', "Saved milestone: $title");
    echo json_encode(['ok' => true, 'msg' => 'Milestone saved.']);
    exit;
  }

  if ($_POST['action'] === 'delete_milestone') {
    $db->prepare("DELETE FROM workflow_milestones WHERE milestone_id=? AND sy_id=?")
      ->execute([(int) $_POST['id'], $syId]);
    echo json_encode(['ok' => true, 'msg' => 'Milestone deleted.']);
    exit;
  }

  if ($_POST['action'] === 'get_milestone') {
    $st = $db->prepare("SELECT * FROM workflow_milestones WHERE milestone_id=?");
    $st->execute([(int) $_POST['id']]);
    echo json_encode($st->fetch());
    exit;
  }

  if ($_POST['action'] === 'update_status') {
    $db->prepare("UPDATE workflow_milestones SET status=? WHERE milestone_id=? AND sy_id=?")
      ->execute([$_POST['status'], (int) $_POST['id'], $syId]);
    echo json_encode(['ok' => true, 'msg' => 'Status updated.']);
    exit;
  }

  if ($_POST['action'] === 'save_cycle_schedule') {
    $phases = [
      1 => ['name' => 'Self-Assessment', 'start' => $_POST['step1_start'] ?? '', 'end' => $_POST['step1_end'] ?? ''],
      2 => ['name' => 'Validation', 'start' => $_POST['step2_start'] ?? '', 'end' => $_POST['step2_end'] ?? ''],
      3 => ['name' => 'Improvement Planning', 'start' => $_POST['step3_start'] ?? '', 'end' => $_POST['step3_end'] ?? ''],
    ];
    $hasAny = false;
    foreach ($phases as $p) {
      if ($p['start'] || $p['end']) {
        $hasAny = true;
        break;
      }
    }
    if (!$hasAny) {
      echo json_encode(['ok' => false, 'msg' => 'Please set at least one phase date.']);
      exit;
    }

    foreach ($phases as $stepNo => $p) {
      $start = $p['start'] ?: null;
      $end = $p['end'] ?: null;
      if (!$start && !$end)
        continue;
      $exists = $db->prepare("SELECT phase_id FROM sbm_workflow_phases WHERE sy_id=? AND phase_no=?");
      $exists->execute([$syId, $stepNo]);
      $row = $exists->fetchColumn();
      if ($row) {
        $db->prepare("UPDATE sbm_workflow_phases SET phase_name=?,date_start=?,date_end=?,is_active=1 WHERE phase_id=?")
          ->execute([$p['name'], $start, $end, $row]);
      } else {
        $db->prepare("INSERT INTO sbm_workflow_phases (sy_id,phase_no,phase_name,description,date_start,date_end,is_active) VALUES (?,?,?,?,?,?,1)")
          ->execute([$syId, $stepNo, $p['name'], '', $start, $end]);
      }
    }
    logActivity('configure_cycle_schedule', 'workflow', "Set cycle schedule for SY $syId");
    echo json_encode(['ok' => true, 'msg' => 'Cycle schedule saved successfully.']);
    exit;
  }

  exit;
}

// ── Load existing cycle schedule (sbm_workflow_phases) ───
$phaseSchedule = [];
try {
  $ps = $db->prepare("SELECT * FROM sbm_workflow_phases WHERE sy_id=? ORDER BY phase_no ASC");
  $ps->execute([$syId]);
  foreach ($ps->fetchAll() as $ph)
    $phaseSchedule[$ph['phase_no']] = $ph;
} catch (\Exception $e) {
  $phaseSchedule = [];
}

// ── Load milestones ───────────────────────────────────────
$milestones = [];
try {
  $stmt = $db->prepare("SELECT * FROM workflow_milestones WHERE sy_id=? AND school_id=? ORDER BY step_no ASC, date_start ASC");
  $stmt->execute([$syId, $schoolId]);
  $milestones = $stmt->fetchAll();
} catch (\Exception $e) {
  // Table may not exist yet — handled below
  $milestones = [];
  $tableError = true;
}

// ── Cycle status ──────────────────────────────────────────
$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

// Group milestones by step
$byStep = [1 => [], 2 => [], 3 => []];
foreach ($milestones as $m) {
  $byStep[$m['step_no']][] = $m;
}

// Count status
$statusCount = ['upcoming' => 0, 'in_progress' => 0, 'completed' => 0, 'delayed' => 0];
foreach ($milestones as $m)
  $statusCount[$m['status']]++;
$totalMilestones = count($milestones);
$completedPct = $totalMilestones > 0 ? round(($statusCount['completed'] / $totalMilestones) * 100) : 0;

$pageTitle = 'Workflow & Timeline';
$activePage = 'workflow.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ── Step pipeline ──────────────────────────────────────── */
  .wf-steps {
    display: grid;
    grid-template-columns: 1fr 40px 1fr 40px 1fr;
    align-items: center;
    margin-bottom: 28px;
    gap: 0;
  }

  .wf-step {
    background: var(--white);
    border: 1.5px solid var(--n200);
    border-radius: var(--radius-lg);
    padding: 20px 20px 16px;
    text-align: center;
    box-shadow: var(--shadow-xs);
    position: relative;
    transition: box-shadow .15s;
  }

  .wf-step:hover {
    box-shadow: var(--shadow-sm);
  }

  .wf-step.active {
    border-color: var(--brand-600);
    background: linear-gradient(135deg, var(--brand-50), var(--white));
  }

  .wf-step-num {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 800;
    margin: 0 auto 10px;
    font-family: var(--font-display);
  }

  .wf-step-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--n900);
    margin-bottom: 4px;
  }

  .wf-step-sub {
    font-size: 12px;
    color: var(--n500);
    line-height: 1.5;
  }

  .wf-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--n300);
    font-size: 22px;
  }

  /* ── Milestone card ─────────────────────────────────────── */
  .milestone-card {
    background: var(--white);
    border: 1px solid var(--n200);
    border-radius: var(--radius);
    padding: 14px 16px;
    margin-bottom: 8px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: box-shadow .15s, transform .15s;
  }

  .milestone-card:hover {
    box-shadow: var(--shadow-sm);
    transform: translateX(2px);
  }

  .milestone-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 4px;
  }

  .milestone-info {
    flex: 1;
    min-width: 0;
  }

  .milestone-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--n900);
    margin-bottom: 3px;
  }

  .milestone-desc {
    font-size: 12.5px;
    color: var(--n500);
    line-height: 1.5;
    margin-bottom: 6px;
  }

  .milestone-dates {
    font-size: 11.5px;
    color: var(--n400);
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .milestone-actions {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
    align-items: flex-start;
  }

  /* ── Status colors ──────────────────────────────────────── */
  .dot-upcoming {
    background: var(--n300);
  }

  .dot-in_progress {
    background: var(--blue);
  }

  .dot-completed {
    background: var(--brand-600);
  }

  .dot-delayed {
    background: var(--red);
  }

  /* ── Step section ───────────────────────────────────────── */
  .step-section {
    margin-bottom: 24px;
  }

  .step-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px 10px;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    border: 1.5px solid var(--n200);
    border-bottom: none;
  }

  .step-section-body {
    border: 1.5px solid var(--n200);
    border-top: none;
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    padding: 14px 16px 8px;
  }

  .step-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    flex-shrink: 0;
    font-family: var(--font-display);
  }

  .wf-phase-dates {
    display: inline-block;
    margin-top: 10px;
    font-size: 11.5px;
    font-weight: 700;
    background: var(--brand-50);
    color: var(--brand-700);
    border: 1px solid var(--brand-200);
    border-radius: 6px;
    padding: 4px 10px;
  }

  /* ── Configure modal phase row ─────────────────────────── */
  .cfg-phase-row {
    background: var(--n50);
    border: 1.5px solid var(--n200);
    border-radius: var(--radius);
    padding: 14px 16px;
    margin-bottom: 12px;
  }

  .cfg-phase-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: var(--n800);
    margin-bottom: 10px;
  }

  .cfg-phase-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
  }

  /* ── Progress ring ──────────────────────────────────────── */
  .ring-wrap {
    position: relative;
    width: 90px;
    height: 90px;
    flex-shrink: 0;
  }

  .ring-wrap svg {
    transform: rotate(-90deg);
  }

  .ring-bg {
    fill: none;
    stroke: var(--n100);
    stroke-width: 8;
  }

  .ring-fill {
    fill: none;
    stroke: var(--brand-600);
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset .8s ease;
  }

  .ring-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    line-height: 1.2;
  }

  .ring-pct {
    font-family: var(--font-display);
    font-size: 18px;
    font-weight: 800;
    color: var(--n900);
  }

  .ring-lbl {
    font-size: 9.5px;
    font-weight: 600;
    color: var(--n400);
  }

  /* ── Empty state ────────────────────────────────────────── */
  .step-empty {
    text-align: center;
    padding: 28px 16px;
    color: var(--n400);
    font-size: 13px;
  }

  @media (max-width: 768px) {
    .wf-steps {
      grid-template-columns: 1fr;
    }

    .wf-arrow {
      transform: rotate(90deg);
    }
  }
</style>

<!-- PAGE HEADER -->
<div class="ph2" style="margin-bottom:20px;">
  <div class="ph2-left">
    <div class="ph2-eyebrow">SBM Process</div>
    <div class="ph2-title">Workflow & Timeline</div>
    <div class="ph2-sub">
      SBM 3-Step Cycle — DepEd Order No. 007, s. 2024
      <?php if ($currentSY): ?>&nbsp;·&nbsp; SY <?= e($currentSY['label']) ?><?php endif; ?>
    </div>
  </div>
  <div class="ph2-right">
    <select class="fc" onchange="location.href='workflow.php?sy='+this.value" style="width:155px;">
      <?php foreach ($syears as $sy): ?>
        <option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id'] == $syId ? 'selected' : '' ?>><?= e($sy['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($_SESSION['role'] === 'school_head'): ?>
      <button class="btn btn-secondary" onclick="openConfigModal()">
        <?= svgIcon('settings') ?> Configure Schedule
      </button>
      <button class="btn btn-primary" onclick="openModal('mMilestone');resetMilestone()">
        <?= svgIcon('plus') ?> Add Milestone
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($tableError)): ?>
  <div class="alert alert-warning" style="margin-bottom:18px;">
    <?= svgIcon('alert-circle') ?>
    <span>The <code>workflow_milestones</code> table doesn't exist yet.
      <a href="#" onclick="createTable()" style="font-weight:700;color:var(--amber);">Create it now →</a></span>
  </div>
<?php endif; ?>

<!-- CYCLE STATUS BANNER -->
<?php
// Stage definitions for the tracker
$SH_STAGES = [
  ['key' => 'setup', 'label' => 'Setup', 'role' => 'Coordinator'],
  ['key' => 'assigning', 'label' => 'Assigning', 'role' => 'Coordinator'],
  ['key' => 'in_progress', 'label' => 'Assessment', 'role' => 'Teachers / Stakeholders'],
  ['key' => 'consolidating', 'label' => 'Consolidation', 'role' => 'Coordinator'],
  ['key' => 'submitted', 'label' => 'Submitted', 'role' => 'School Head'],
  ['key' => 'validated', 'label' => 'Validated', 'role' => 'Coordinator'],
  ['key' => 'finalized', 'label' => 'Finalized', 'role' => 'System'],
];
$SH_STAGE_ORDER = array_column($SH_STAGES, 'key');
$currentCycleStatus = $cycle['status'] ?? 'draft';
$currentStageIdx = array_search($currentCycleStatus, $SH_STAGE_ORDER);
?>

<?php if ($cycle): ?>
  <!-- 7-Stage Tracker -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-head">
      <span class="card-title">Assessment Cycle Progress</span>
      <span style="font-size:12px;color:var(--n500);">Cycle ID #<?= $cycle['cycle_id'] ?> · SY
        <?= e($currentSY['label'] ?? '') ?></span>
    </div>
    <div class="card-body" style="padding:20px 16px 16px;">
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:16px;">
        <?php foreach ($SH_STAGES as $i => $st):
          $isDone = $currentStageIdx !== false && $i < $currentStageIdx;
          $isActive = $currentStageIdx !== false && $i === $currentStageIdx;
          $isReturn = $currentCycleStatus === 'returned' && $st['key'] === 'submitted';
          $dotBg = $isDone ? '#16A34A' : ($isReturn ? '#DC2626' : ($isActive ? '#2563EB' : '#E5E7EB'));
          $dotColor = ($isDone || $isActive || $isReturn) ? '#fff' : '#9CA3AF';
          $labelC = $isActive ? 'var(--n900)' : ($isDone ? 'var(--g700)' : 'var(--n400)');
          ?>
          <div style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:6px;">
            <div
              style="width:32px;height:32px;border-radius:50%;background:<?= $dotBg ?>;color:<?= $dotColor ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;border:2px solid <?= $isActive ? '#2563EB' : ($isDone ? '#16A34A' : '#E5E7EB') ?>;<?= $isActive ? 'box-shadow:0 0 0 4px rgba(37,99,235,.15);' : '' ?>">
              <?= $isDone ? '✓' : ($isReturn ? '!' : ($i + 1)) ?>
            </div>
            <div
              style="font-size:10.5px;font-weight:<?= $isActive ? '700' : '500' ?>;color:<?= $labelC ?>;line-height:1.3;">
              <?= $st['label'] ?>
            </div>
          </div>
          <?php if ($i < count($SH_STAGES) - 1): ?>
            <!-- connector line drawn via grid, use pseudo approach in next sibling -->
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Current stage info bar -->
      <div
        style="background:var(--n50);border:1px solid var(--n200);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <?php
        $activeStage = $SH_STAGES[$currentStageIdx] ?? null;
        ?>
        <?php if ($currentCycleStatus === 'returned'): ?>
          <span style="font-size:13px;font-weight:700;color:var(--red);">⚠ Assessment Returned for Revision</span>
          <?php if ($cycle['return_remarks'] ?? ''): ?>
            <span style="font-size:12.5px;color:var(--n600);">Remarks: <?= e($cycle['return_remarks']) ?></span>
          <?php endif; ?>
        <?php elseif ($activeStage): ?>
          <span style="font-size:13px;font-weight:700;color:var(--n800);">Current Stage: <?= $activeStage['label'] ?></span>
          <span style="font-size:12px;color:var(--n500);">Responsible: <?= $activeStage['role'] ?></span>
        <?php endif; ?>
        <?php if ($cycle['overall_score']): ?>
          <span style="margin-left:auto;font-size:13px;font-weight:700;color:var(--g700);">Score:
            <?= $cycle['overall_score'] ?>% — <?= e($cycle['maturity_level']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Stage action buttons — only show what this role can do right now -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
        <?php if ($currentCycleStatus === 'consolidating' && $_SESSION['role'] === 'sbm_coordinator'): ?>
          <button class="btn btn-primary"
            onclick="doAdvanceStage(<?= $cycle['cycle_id'] ?>,'submitted','Submit to SDO — this will lock school-side editing.')">
            <?= svgIcon('send') ?> Submit to SDO
          </button>
        <?php endif; ?>
        <?php if ($currentCycleStatus === 'submitted' && $_SESSION['role'] === 'sbm_coordinator'): ?>
          <button class="btn btn-success" onclick="openValidateModal(<?= $cycle['cycle_id'] ?>)">
            <?= svgIcon('check-circle') ?> Validate Assessment
          </button>
          <button class="btn btn-danger" onclick="openReturnModal(<?= $cycle['cycle_id'] ?>)">
            <?= svgIcon('arrow-left') ?> Return for Revision
          </button>
        <?php endif; ?>
        <?php if ($currentCycleStatus === 'validated' && $_SESSION['role'] === 'sbm_coordinator'): ?>
          <button class="btn btn-primary" onclick="doFinalizeCycle(<?= $cycle['cycle_id'] ?>)">
            <?= svgIcon('award') ?> Finalize & Lock Cycle
          </button>
        <?php endif; ?>
        <?php if ($currentCycleStatus === 'finalized'): ?>
          <a href="reports.php" class="btn btn-secondary"><?= svgIcon('file-text') ?> View Final Report</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- OVERALL PROGRESS + STATS -->
<div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
  <!-- Ring -->
  <div class="ring-wrap">
    <?php
    $circumference = 2 * M_PI * 37; // r=37
    $offset = $circumference * (1 - $completedPct / 100);
    ?>
    <svg viewBox="0 0 90 90" width="90" height="90">
      <circle class="ring-bg" cx="45" cy="45" r="37" />
      <circle class="ring-fill" cx="45" cy="45" r="37" stroke-dasharray="<?= $circumference ?>"
        stroke-dashoffset="<?= $offset ?>" />
    </svg>
    <div class="ring-text">
      <div class="ring-pct"><?= $completedPct ?>%</div>
      <div class="ring-lbl">Done</div>
    </div>
  </div>

  <!-- Mini stats -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;flex:1;">
    <?php
    $statDefs = [
      ['upcoming', 'Upcoming', '#9CA3AF', '#F3F4F6'],
      ['in_progress', 'In Progress', '#2563EB', '#DBEAFE'],
      ['completed', 'Completed', '#16A34A', '#DCFCE7'],
      ['delayed', 'Delayed', '#DC2626', '#FEE2E2'],
    ];
    foreach ($statDefs as [$key, $label, $color, $bg]):
      ?>
      <div style="background:<?= $bg ?>;border-radius:var(--radius);padding:12px 16px;min-width:110px;text-align:center;">
        <div style="font-family:var(--font-display);font-size:24px;font-weight:800;color:<?= $color ?>;">
          <?= $statusCount[$key] ?>
        </div>
        <div style="font-size:11.5px;font-weight:600;color:<?= $color ?>;margin-top:2px;"><?= $label ?></div>
      </div>
    <?php endforeach; ?>
    <div
      style="background:var(--n100);border-radius:var(--radius);padding:12px 16px;min-width:110px;text-align:center;">
      <div style="font-family:var(--font-display);font-size:24px;font-weight:800;color:var(--n700);">
        <?= $totalMilestones ?>
      </div>
      <div style="font-size:11.5px;font-weight:600;color:var(--n500);margin-top:2px;">Total</div>
    </div>
  </div>
</div>

<!-- SBM 3-STEP PIPELINE -->
<div class="wf-steps" style="margin-bottom:28px;">

  <div class="wf-step <?= (!$cycle || $cycle['status'] === 'in_progress') ? 'active' : '' ?>">
    <div class="wf-step-num" style="background:#DBEAFE;color:#2563EB;">1</div>
    <div class="wf-step-title">Self-Assessment</div>
    <div class="wf-step-sub">School conducts SBM self-assessment using the rating checklist across 6 dimensions.</div>
    <?php if (!empty($phaseSchedule[1])): $ph = $phaseSchedule[1]; ?>
      <div class="wf-phase-dates">
        <?= date('M d', strtotime($ph['date_start'])) ?> — <?= date('M d, Y', strtotime($ph['date_end'])) ?>
      </div>
    <?php elseif ($cycle && $cycle['started_at']): ?>
      <div
        style="margin-top:10px;font-size:11.5px;font-weight:600;color:var(--brand-600);background:var(--brand-50);border-radius:6px;padding:4px 10px;display:inline-block;">
        Started <?= date('M d, Y', strtotime($cycle['started_at'])) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="wf-arrow">›</div>

  <div class="wf-step <?= ($cycle && $cycle['status'] === 'submitted') ? 'active' : '' ?>">
    <div class="wf-step-num" style="background:#FEF3C7;color:#D97706;">2</div>
    <div class="wf-step-title">Validation</div>
    <div class="wf-step-sub">SDO validates the submitted assessment, reviews evidence, and provides official feedback.
    </div>
    <?php if (!empty($phaseSchedule[2])): $ph = $phaseSchedule[2]; ?>
      <div class="wf-phase-dates" style="background:#FEF3C7;color:#D97706;border-color:#FDE68A;">
        <?= date('M d', strtotime($ph['date_start'])) ?> — <?= date('M d, Y', strtotime($ph['date_end'])) ?>
      </div>
    <?php elseif ($cycle && $cycle['submitted_at']): ?>
      <div
        style="margin-top:10px;font-size:11.5px;font-weight:600;color:var(--amber);background:var(--amber-bg);border-radius:6px;padding:4px 10px;display:inline-block;">
        Submitted <?= date('M d, Y', strtotime($cycle['submitted_at'])) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="wf-arrow">›</div>

  <div class="wf-step <?= ($cycle && $cycle['status'] === 'validated') ? 'active' : '' ?>">
    <div class="wf-step-num" style="background:#DCFCE7;color:#16A34A;">3</div>
    <div class="wf-step-title">Improvement Planning</div>
    <div class="wf-step-sub">School develops action plans to address gaps identified in the validated assessment.</div>
    <?php if (!empty($phaseSchedule[3])): $ph = $phaseSchedule[3]; ?>
      <div class="wf-phase-dates" style="background:#DCFCE7;color:#16A34A;border-color:#86EFAC;">
        <?= date('M d', strtotime($ph['date_start'])) ?> — <?= date('M d, Y', strtotime($ph['date_end'])) ?>
      </div>
    <?php elseif ($cycle && $cycle['validated_at']): ?>
      <div
        style="margin-top:10px;font-size:11.5px;font-weight:600;color:var(--brand-700);background:var(--brand-100);border-radius:6px;padding:4px 10px;display:inline-block;">
        Validated <?= date('M d, Y', strtotime($cycle['validated_at'])) ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- QUICK ACTIONS (School Head only) -->
<?php if ($_SESSION['role'] === 'school_head'): ?>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
    <a href="self_assessment.php" class="btn btn-secondary" style="flex:1;justify-content:center;min-width:140px;">
      <?= svgIcon('check-circle') ?> Self-Assessment
    </a>
    <a href="assessment.php" class="btn btn-secondary" style="flex:1;justify-content:center;min-width:140px;">
      <?= svgIcon('check') ?> Assessments
    </a>
    <a href="improvement.php" class="btn btn-secondary" style="flex:1;justify-content:center;min-width:140px;">
      <?= svgIcon('trending-up') ?> Improvement Plan
    </a>
    <a href="reports.php" class="btn btn-secondary" style="flex:1;justify-content:center;min-width:140px;">
      <?= svgIcon('file-text') ?> Reports
    </a>
  </div>
<?php endif; ?>

<!-- MILESTONES BY STEP -->
<?php
$stepConfig = [
  1 => ['label' => 'Step 1: Self-Assessment Phase', 'color' => '#2563EB', 'bg' => '#DBEAFE'],
  2 => ['label' => 'Step 2: Validation Phase', 'color' => '#D97706', 'bg' => '#FEF3C7'],
  3 => ['label' => 'Step 3: Improvement Phase', 'color' => '#16A34A', 'bg' => '#DCFCE7'],
];

$dotClass = [
  'upcoming' => 'dot-upcoming',
  'in_progress' => 'dot-in_progress',
  'completed' => 'dot-completed',
  'delayed' => 'dot-delayed',
];
$pillMap = [
  'upcoming' => ['#F3F4F6', '#9CA3AF'],
  'in_progress' => ['#DBEAFE', '#2563EB'],
  'completed' => ['#DCFCE7', '#16A34A'],
  'delayed' => ['#FEE2E2', '#DC2626'],
];
?>

<?php if (empty($milestones) && !isset($tableError)): ?>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:56px 20px;">
      <div class="empty-icon" style="margin:0 auto 16px;"><?= svgIcon('calendar') ?></div>
      <div class="empty-title">No milestones yet</div>
      <div class="empty-sub">Add milestones to track the SBM workflow timeline for this school year.</div>
      <?php if ($_SESSION['role'] === 'school_head'): ?>
        <button class="btn btn-primary" style="margin-top:16px;" onclick="openModal('mMilestone');resetMilestone()">
          <?= svgIcon('plus') ?> Add First Milestone
        </button>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>

  <?php foreach ($stepConfig as $step => $cfg): ?>
    <div class="step-section">
      <div class="step-section-head" style="background:<?= $cfg['bg'] ?>;">
        <div class="step-badge" style="background:<?= $cfg['color'] ?>;color:#fff;"><?= $step ?></div>
        <span style="font-size:14px;font-weight:700;color:<?= $cfg['color'] ?>;flex:1;"><?= $cfg['label'] ?></span>
        <span style="font-size:12px;font-weight:600;color:<?= $cfg['color'] ?>;opacity:.7;">
          <?= count($byStep[$step]) ?> milestone<?= count($byStep[$step]) !== 1 ? 's' : '' ?>
        </span>
        <?php if ($_SESSION['role'] === 'school_head'): ?>
          <button class="btn btn-sm" style="margin-left:8px;background:<?= $cfg['color'] ?>;color:#fff;border:none;"
            onclick="openModal('mMilestone');resetMilestone();$el('m_step').value=<?= $step ?>">
            <?= svgIcon('plus') ?> Add
          </button>
        <?php endif; ?>
      </div>
      <div class="step-section-body">

        <?php if (empty($byStep[$step])): ?>
          <div class="step-empty">
            No milestones for this phase.
            <?php if ($_SESSION['role'] === 'school_head'): ?>
              <br><button class="btn btn-ghost btn-sm" style="margin-top:8px;"
                onclick="openModal('mMilestone');resetMilestone();$el('m_step').value=<?= $step ?>">
                + Add milestone
              </button>
            <?php endif; ?>
          </div>
        <?php else: ?>

          <?php foreach ($byStep[$step] as $m):
            [$pillBg, $pillColor] = $pillMap[$m['status']] ?? ['#F3F4F6', '#9CA3AF'];
            ?>
            <div class="milestone-card" id="milestone<?= $m['milestone_id'] ?>">
              <div class="milestone-dot <?= e($dotClass[$m['status']] ?? 'dot-upcoming') ?>"></div>
              <div class="milestone-info">
                <div class="milestone-title"><?= e($m['title']) ?></div>
                <?php if ($m['description']): ?>
                  <div class="milestone-desc"><?= nl2br(e($m['description'])) ?></div>
                <?php endif; ?>
                <div class="milestone-dates">
                  <?php if ($m['date_start']): ?>
                    <span>📅 Start: <?= date('M d, Y', strtotime($m['date_start'])) ?></span>
                  <?php endif; ?>
                  <?php if ($m['date_end']): ?>
                    <span>🏁 End: <?= date('M d, Y', strtotime($m['date_end'])) ?></span>
                  <?php endif; ?>
                  <?php
                  // Overdue check
                  if ($m['date_end'] && $m['status'] !== 'completed' && strtotime($m['date_end']) < time()):
                    ?>
                    <span style="color:var(--red);font-weight:600;">⚠ Overdue</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="milestone-actions">
                <!-- Status quick-change -->
                <select class="fc"
                  style="font-size:11.5px;padding:4px 8px;height:30px;width:auto;background:<?= $pillBg ?>;color:<?= $pillColor ?>;font-weight:600;border-color:<?= $pillColor ?>33;"
                  onchange="updateStatus(<?= $m['milestone_id'] ?>,this.value)">
                  <?php foreach (['upcoming', 'in_progress', 'completed', 'delayed'] as $sv): ?>
                    <option value="<?= $sv ?>" <?= $m['status'] === $sv ? 'selected' : '' ?>>
                      <?= ucfirst(str_replace('_', ' ', $sv)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($_SESSION['role'] === 'school_head'): ?>
                  <button class="btn btn-secondary btn-sm"
                    onclick="editMilestone(<?= $m['milestone_id'] ?>)"><?= svgIcon('edit') ?></button>
                  <button class="btn btn-danger btn-sm"
                    onclick="delMilestone(<?= $m['milestone_id'] ?>)"><?= svgIcon('trash') ?></button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<!-- DepEd Reference Box -->
<div
  style="margin-top:28px;padding:18px 20px;background:var(--n50);border:1px solid var(--n200);border-radius:var(--radius-lg);">
  <div
    style="font-size:12px;font-weight:700;color:var(--n400);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;">
    DepEd Order Reference
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
    <div>
      <div style="font-size:11.5px;font-weight:700;color:var(--n600);margin-bottom:4px;">Policy Basis</div>
      <div style="font-size:13px;color:var(--n800);">DepEd Order No. 007, s. 2024</div>
    </div>
    <div>
      <div style="font-size:11.5px;font-weight:700;color:var(--n600);margin-bottom:4px;">Framework</div>
      <div style="font-size:13px;color:var(--n800);">School-Based Management (SBM)</div>
    </div>
    <div>
      <div style="font-size:11.5px;font-weight:700;color:var(--n600);margin-bottom:4px;">Rating Scale</div>
      <div style="font-size:13px;color:var(--n800);">4 Degrees of Manifestation (1–4)</div>
    </div>
    <div>
      <div style="font-size:11.5px;font-weight:700;color:var(--n600);margin-bottom:4px;">Maturity Levels</div>
      <div style="font-size:13px;color:var(--n800);">Beginning · Developing · Maturing · Advanced</div>
    </div>
    <div>
      <div style="font-size:11.5px;font-weight:700;color:var(--n600);margin-bottom:4px;">Dimensions</div>
      <div style="font-size:13px;color:var(--n800);">6 SBM Dimensions assessed annually</div>
    </div>
    <div>
      <div style="font-size:11.5px;font-weight:700;color:var(--n600);margin-bottom:4px;">School</div>
      <div style="font-size:13px;color:var(--n800);">Dasmariñas Integrated High School</div>
    </div>
  </div>
</div>

<!-- ── CONFIGURE CYCLE SCHEDULE MODAL ───────────────────── -->
<div class="overlay" id="mConfigure">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head">
      <span class="modal-title">Configure Cycle Schedule</span>
      <button class="modal-close" onclick="closeModal('mConfigure')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--n500);margin-bottom:18px;line-height:1.6;">
        Set the official start and end dates for each SBM phase this school year.
        These dates will appear on the workflow pipeline cards.
      </p>

      <!-- Step 1 -->
      <div class="cfg-phase-row">
        <div class="cfg-phase-label">
          <span class="cfg-phase-num" style="background:#2563EB;">1</span>
          Self-Assessment Phase
        </div>
        <div class="form-row">
          <div class="fg" style="margin-bottom:0;">
            <label>Start Date</label>
            <input class="fc" type="date" id="cfg_step1_start">
          </div>
          <div class="fg" style="margin-bottom:0;">
            <label>End Date</label>
            <input class="fc" type="date" id="cfg_step1_end">
          </div>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="cfg-phase-row" style="border-color:#FDE68A;background:#FFFBEB;">
        <div class="cfg-phase-label">
          <span class="cfg-phase-num" style="background:#D97706;">2</span>
          Validation Phase
        </div>
        <div class="form-row">
          <div class="fg" style="margin-bottom:0;">
            <label>Start Date</label>
            <input class="fc" type="date" id="cfg_step2_start">
          </div>
          <div class="fg" style="margin-bottom:0;">
            <label>End Date</label>
            <input class="fc" type="date" id="cfg_step2_end">
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="cfg-phase-row" style="border-color:#86EFAC;background:#F0FDF4;">
        <div class="cfg-phase-label">
          <span class="cfg-phase-num" style="background:#16A34A;">3</span>
          Improvement Planning Phase
        </div>
        <div class="form-row">
          <div class="fg" style="margin-bottom:0;">
            <label>Start Date</label>
            <input class="fc" type="date" id="cfg_step3_start">
          </div>
          <div class="fg" style="margin-bottom:0;">
            <label>End Date</label>
            <input class="fc" type="date" id="cfg_step3_end">
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mConfigure')">Cancel</button>
      <button class="btn btn-primary" id="cfgSaveBtn" onclick="saveCycleSchedule()">
        <?= svgIcon('save') ?> Save Schedule
      </button>
    </div>
  </div>
</div>

<!-- ── MILESTONE MODAL ────────────────────────────────────── -->
<div class="overlay" id="mMilestone">
  <div class="modal" style="max-width:520px;">
    <div class="modal-head">
      <span class="modal-title" id="mMilestoneTitle">Add Milestone</span>
      <button class="modal-close" onclick="closeModal('mMilestone')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="m_id">
      <div class="fg"><label>Title *</label>
        <input class="fc" id="m_title" placeholder="e.g. Submit SBM Assessment">
      </div>
      <div class="fg"><label>Description</label>
        <textarea class="fc" id="m_desc" rows="2" placeholder="Brief description of this milestone…"></textarea>
      </div>
      <div class="form-row">
        <div class="fg"><label>SBM Step</label>
          <select class="fc" id="m_step">
            <option value="1">Step 1 — Self-Assessment</option>
            <option value="2">Step 2 — Validation</option>
            <option value="3">Step 3 — Improvement</option>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="m_status">
            <option value="upcoming">Upcoming</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="delayed">Delayed</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Start Date</label><input class="fc" type="date" id="m_start"></div>
        <div class="fg"><label>End Date</label><input class="fc" type="date" id="m_end"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mMilestone')">Cancel</button>
      <button class="btn btn-primary" onclick="saveMilestone()"><?= svgIcon('save') ?> Save</button>
    </div>
  </div>
</div>

<script>
  function resetMilestone() {
    $v('m_id', ''); $v('m_title', ''); $v('m_desc', '');
    $el('m_step').value = '1'; $el('m_status').value = 'upcoming';
    $v('m_start', ''); $v('m_end', '');
    $el('mMilestoneTitle').textContent = 'Add Milestone';
  }

  async function saveMilestone() {
    const title = $('m_title');
    if (!title) { toast('Title is required.', 'warning'); return; }
    const r = await apiPost('workflow.php', {
      action: 'save_milestone',
      milestone_id: $('m_id'),
      title,
      description: document.getElementById('m_desc').value,
      step: $('m_step'),
      status: $('m_status'),
      date_start: $('m_start'),
      date_end: $('m_end'),
    });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mMilestone'); setTimeout(() => location.reload(), 700); }
  }

  async function editMilestone(id) {
    const r = await apiPost('workflow.php', { action: 'get_milestone', id });
    if (!r || !r.milestone_id) { toast('Failed to load.', 'err'); return; }
    $v('m_id', r.milestone_id);
    $v('m_title', r.title);
    document.getElementById('m_desc').value = r.description || '';
    $el('m_step').value = r.step_no || '1';
    $el('m_status').value = r.status || 'upcoming';
    $v('m_start', r.date_start || '');
    $v('m_end', r.date_end || '');
    $el('mMilestoneTitle').textContent = 'Edit Milestone';
    openModal('mMilestone');
  }

  async function delMilestone(id) {
    if (!confirm('Delete this milestone?')) return;
    const r = await apiPost('workflow.php', { action: 'delete_milestone', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      const card = document.getElementById('milestone' + id);
      if (card) card.remove();
    }
  }

  async function updateStatus(id, status) {
    const r = await apiPost('workflow.php', { action: 'update_status', id, status });
    if (!r.ok) toast(r.msg, 'err');
    else toast('Status updated.', 'ok');
  }

  async function createTable() {
    toast('Please ask your database admin to create the workflow_milestones table.', 'warning');
  }

  // ── Configure Schedule ────────────────────────────────────
  const PHASE_SCHEDULE = <?= json_encode($phaseSchedule) ?>;

  function openConfigModal() {
    // Pre-fill existing schedule dates
    [1, 2, 3].forEach(s => {
      const ph = PHASE_SCHEDULE[s];
      $v('cfg_step' + s + '_start', ph ? ph.date_start : '');
      $v('cfg_step' + s + '_end', ph ? ph.date_end : '');
    });
    openModal('mConfigure');
  }

  async function saveCycleSchedule() {
    const btn = document.getElementById('cfgSaveBtn');
    btn.disabled = true; btn.textContent = 'Saving…';
    const r = await apiPost('workflow.php', {
      action: 'save_cycle_schedule',
      step1_start: $('cfg_step1_start'), step1_end: $('cfg_step1_end'),
      step2_start: $('cfg_step2_start'), step2_end: $('cfg_step2_end'),
      step3_start: $('cfg_step3_start'), step3_end: $('cfg_step3_end'),
    });
    toast(r.msg, r.ok ? 'ok' : 'err');
    btn.disabled = false; btn.textContent = 'Save Schedule';
    if (r.ok) { closeModal('mConfigure'); setTimeout(() => location.reload(), 700); }
  }
</script>

<!-- Validate Modal -->
<div class="overlay" id="mValidate">
  <div class="modal" style="max-width:440px;">
    <div class="modal-head">
      <span class="modal-title">Validate Assessment</span>
      <button class="modal-close" onclick="closeModal('mValidate')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="val_cycle_id">
      <div class="alert alert-success"><?= svgIcon('check-circle') ?><span>Validating will mark this cycle as officially
          accepted. This cannot be undone without a new cycle.</span></div>
      <div class="fg">
        <label>Validation Remarks <span style="color:var(--n400);font-weight:400;">(optional)</span></label>
        <textarea class="fc" id="val_remarks" rows="3" placeholder="Notes on validation outcome…"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mValidate')">Cancel</button>
      <button class="btn btn-success" onclick="submitValidate()"><?= svgIcon('check') ?> Confirm Validate</button>
    </div>
  </div>
</div>

<!-- Return Modal -->
<div class="overlay" id="mReturn">
  <div class="modal" style="max-width:440px;">
    <div class="modal-head">
      <span class="modal-title">Return for Revision</span>
      <button class="modal-close" onclick="closeModal('mReturn')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ret_cycle_id">
      <div class="alert alert-danger"><?= svgIcon('alert-circle') ?><span>The assessment will be sent back. Remarks are
          <strong>required</strong>.</span></div>
      <div class="fg">
        <label>Return To Stage</label>
        <select class="fc" id="ret_to_stage">
          <option value="in_progress">In Progress (teachers re-answer)</option>
          <option value="assigning">Assigning (re-assign indicators)</option>
        </select>
      </div>
      <div class="fg">
        <label>Reason for Return *</label>
        <textarea class="fc" id="ret_remarks" rows="3" placeholder="Explain what needs to be corrected…"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mReturn')">Cancel</button>
      <button class="btn btn-danger" onclick="submitReturn()"><?= svgIcon('arrow-left') ?> Confirm Return</button>
    </div>
  </div>
</div>

<script>
  // ── Workflow stage actions ─────────────────────────────────
  const WF_URL = 'workflow.php';

  async function doAdvanceStage(cycleId, toStage, confirmMsg) {
    if (!confirm(confirmMsg || 'Advance to next stage?')) return;
    const r = await apiPost(WF_URL, { action: 'advance_stage', cycle_id: cycleId, to_stage: toStage });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }

  function openValidateModal(cycleId) {
    document.getElementById('val_cycle_id').value = cycleId;
    document.getElementById('val_remarks').value = '';
    openModal('mValidate');
  }

  async function submitValidate() {
    const cycleId = document.getElementById('val_cycle_id').value;
    const remarks = document.getElementById('val_remarks').value;
    const r = await apiPost(WF_URL, { action: 'validate_cycle', cycle_id: cycleId, remarks });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mValidate'); setTimeout(() => location.reload(), 800); }
  }

  function openReturnModal(cycleId) {
    document.getElementById('ret_cycle_id').value = cycleId;
    document.getElementById('ret_remarks').value = '';
    openModal('mReturn');
  }

  async function submitReturn() {
    const cycleId = document.getElementById('ret_cycle_id').value;
    const remarks = document.getElementById('ret_remarks').value;
    const toStage = document.getElementById('ret_to_stage').value;
    if (!remarks.trim()) { toast('Remarks are required when returning.', 'warning'); return; }
    const r = await apiPost(WF_URL, { action: 'return_cycle', cycle_id: cycleId, remarks, to_stage: toStage });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mReturn'); setTimeout(() => location.reload(), 800); }
  }

  async function doFinalizeCycle(cycleId) {
    if (!confirm('Finalize and permanently lock this cycle? No further edits will be possible.')) return;
    const r = await apiPost(WF_URL, { action: 'finalize_cycle', cycle_id: cycleId });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>