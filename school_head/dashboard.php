<?php
ob_start();
// ============================================================
// school_head/dashboard.php — SY-FILTERED DASHBOARD
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head');
$db = getDB();

// ── All school years for the selector ────────────────────────
$allSYs = $db->query("SELECT * FROM school_years ORDER BY label DESC")->fetchAll();
$currentSYRow = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();

// ── Resolve selected SY from ?sy_id= (fall back to current) ──
$selectedSyId = isset($_GET['sy_id']) ? (int) $_GET['sy_id'] : ($currentSYRow['sy_id'] ?? 0);

// Validate the sy_id actually exists
$selSYStmt = $db->prepare("SELECT * FROM school_years WHERE sy_id = ?");
$selSYStmt->execute([$selectedSyId]);
$selectedSYRow = $selSYStmt->fetch();
if (!$selectedSYRow && $currentSYRow) {
  $selectedSyId = $currentSYRow['sy_id'];
  $selectedSYRow = $currentSYRow;
}
$selectedSYLabel = $selectedSYRow['label'] ?? 'All Years';
$isCurrentSY = ($selectedSYRow && $currentSYRow && $selectedSYRow['sy_id'] == $currentSYRow['sy_id']);

// ── SY-scoped stats ───────────────────────────────────────────
$mySchoolId = (int) ($_SESSION['school_id'] ?? 0);

$stTotalCycles = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id = ? AND school_id = ?");
$stTotalCycles->execute([$selectedSyId, $mySchoolId]);
$totalCycles = (int) $stTotalCycles->fetchColumn();

$stSubmitted = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id = ? AND school_id = ? AND status IN('submitted','validated')");
$stSubmitted->execute([$selectedSyId, $mySchoolId]);
$submitted = (int) $stSubmitted->fetchColumn();

$stValidated = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id = ? AND school_id = ? AND status='validated'");
$stValidated->execute([$selectedSyId, $mySchoolId]);
$validated = (int) $stValidated->fetchColumn();

$stInProgress = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id = ? AND school_id = ? AND status='in_progress'");
$stInProgress->execute([$selectedSyId, $mySchoolId]);
$inProgress = (int) $stInProgress->fetchColumn();

$stReturned = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id = ? AND school_id = ? AND status='returned'");
$stReturned->execute([$selectedSyId, $mySchoolId]);
$returned = (int) $stReturned->fetchColumn();

// Portal users & active users are global (not SY-scoped)
$enrollSt = $db->prepare("SELECT COUNT(*) FROM users WHERE school_id=? AND status='active'");
$enrollSt->execute([$mySchoolId]);
$portalUsers = (int) $enrollSt->fetchColumn();
$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();

// ── Maturity distribution (SY-scoped) ────────────────────────
$stMaturity = $db->prepare("
  SELECT maturity_level, COUNT(*) cnt FROM sbm_cycles
  WHERE sy_id = ? AND maturity_level IS NOT NULL
  GROUP BY maturity_level
  ORDER BY FIELD(maturity_level,'Advanced','Maturing','Developing','Beginning')
");
$stMaturity->execute([$selectedSyId]);
$maturity = $stMaturity->fetchAll();

// ── Recent cycles (SY-scoped) ─────────────────────────────────
$stRecent = $db->prepare("
  SELECT c.*, s.school_name, sy.label sy_label
  FROM sbm_cycles c
  JOIN schools s ON c.school_id=s.school_id
  JOIN school_years sy ON c.sy_id=sy.sy_id
  WHERE c.sy_id = ?
  ORDER BY c.created_at DESC LIMIT 8
");
$stRecent->execute([$selectedSyId]);
$recentCycles = $stRecent->fetchAll();

// ── Dimension scores (SY-scoped — subquery ensures only scores from selected SY cycles)
$stDimScores = $db->prepare("
  SELECT d.dimension_no, d.dimension_name, d.color_hex,
         ROUND(AVG(ds.percentage), 1) avg_pct
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds
    ON d.dimension_id = ds.dimension_id
    AND ds.cycle_id IN (SELECT cycle_id FROM sbm_cycles WHERE sy_id = ?)
  GROUP BY d.dimension_id
  ORDER BY d.dimension_no
");
$stDimScores->execute([$selectedSyId]);
$dimScores = $stDimScores->fetchAll();

// ── Recent activity (global — not SY-scoped) ──────────────────
$recentActivity = $db->query("
  SELECT l.*, u.full_name FROM activity_log l
  LEFT JOIN users u ON l.user_id=u.user_id
  ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

$validationRate = $submitted > 0 ? round(($validated / $submitted) * 100) : 0;
$hasData = ($totalCycles > 0);

// ── Deadline awareness ────────────────────────────────────────
$deadlineInfo = $selectedSyId ? getDeadlineInfo($db, $selectedSyId) : null;

$pageTitle = 'Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ── HERO ── */
  .db-hero {
    border-radius: var(--radius-lg);
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 24px;
    position: relative;
    overflow: visible;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
  }

  .db-hero-bg {
    position: absolute;
    inset: 0;
    border-radius: var(--radius-lg);
    background:
      linear-gradient(to right, rgba(8, 26, 8, 0.8) 0%, rgba(8, 26, 8, 0.4) 50%, rgba(8, 26, 8, 0.1) 100%),
      url('<?= e(baseUrl()) ?>/assets/cover.png') center/cover no-repeat;
    background-color: #081a08;
    overflow: hidden;
    z-index: 0;
  }



  .db-hero-left {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
  }

  .db-hero-greeting {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: rgba(74, 222, 128, .8);
    margin-bottom: 8px;
  }

  .db-hero-title {
    font-family: var(--font-display);
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -.5px;
    margin-bottom: 6px;
    line-height: 1.15;
  }

  .db-hero-sub {
    font-size: 13.5px;
    color: rgba(255, 255, 255, .55);
    line-height: 1.5;
  }

  .db-hero-right {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
  }

  .db-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 140ms;
    white-space: nowrap;
  }

  .db-hero-btn-primary {
    background: rgba(255, 255, 255, .12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, .2);
  }

  .db-hero-btn-primary:hover {
    background: rgba(255, 255, 255, .2);
  }

  .db-hero-btn-secondary {
    background: rgba(255, 255, 255, .05);
    color: rgba(255, 255, 255, .75);
    border: 1px solid rgba(255, 255, 255, .1);
  }

  .db-hero-btn-secondary:hover {
    background: rgba(255, 255, 255, .12);
  }

  .db-hero-btn svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* ── KPI STATS ── */
  .stats-v2 {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
  }

  .stat-v2 {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    padding: 20px 20px 16px;
    box-shadow: var(--shadow-xs);
    transition: transform 160ms, box-shadow 160ms;
    position: relative;
    overflow: hidden;
  }

  .stat-v2:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
  }

  .stat-v2-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  }

  .stat-v2-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--n-500);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 10px;
  }

  .stat-v2-value {
    font-family: var(--font-display);
    font-size: 32px;
    font-weight: 800;
    color: var(--n-900);
    line-height: 1;
    letter-spacing: -.8px;
    margin-bottom: 8px;
  }

  .stat-v2-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--n-500);
    flex-wrap: wrap;
  }

  .stat-v2-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
  }

  .badge-green {
    background: var(--brand-100);
    color: var(--brand-700);
  }

  .badge-amber {
    background: var(--amber-bg);
    color: var(--amber);
  }

  .badge-blue {
    background: var(--blue-bg);
    color: var(--blue);
  }

  .kpi-bar {
    height: 5px;
    background: var(--n-100);
    border-radius: 999px;
    overflow: hidden;
    margin-top: 10px;
  }

  .kpi-bar-fill {
    height: 100%;
    border-radius: 999px;
  }

  /* ── PIPELINE ── */
  .pipeline {
    display: flex;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 0;
    margin-bottom: 6px;
  }

  .pipeline-step {
    flex: 1;
    min-width: 80px;
    text-align: center;
    padding: 14px 8px;
    position: relative;
  }

  .pipeline-step:not(:last-child)::after {
    content: '→';
    position: absolute;
    right: -10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--n-300);
    font-size: 16px;
    z-index: 1;
  }

  .pipeline-val {
    font-family: var(--font-display);
    font-size: 24px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
  }

  .pipeline-lbl {
    font-size: 11px;
    font-weight: 600;
    color: var(--n-500);
    text-transform: uppercase;
    letter-spacing: .05em;
  }

  /* ── DIM LIST ── */
  .dim-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .dim-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .dim-num {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    flex-shrink: 0;
    color: #fff;
  }

  .dim-info {
    flex: 1;
    min-width: 0;
  }

  .dim-name {
    font-size: 12.5px;
    font-weight: 500;
    color: var(--n-600);
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .dim-prog {
    height: 6px;
    background: var(--n-150);
    border-radius: 999px;
    overflow: hidden;
  }

  .dim-prog-fill {
    height: 100%;
    border-radius: 999px;
  }

  .dim-pct {
    font-family: var(--font-display);
    font-size: 14px;
    font-weight: 800;
    text-align: right;
    flex-shrink: 0;
    min-width: 42px;
    letter-spacing: -0.3px;
  }

  /* ── ACTIVITY ── */
  .activity-feed {
    display: flex;
    flex-direction: column;
  }

  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 8px 0;
    border-bottom: 1px solid var(--n-100);
  }

  .activity-item:last-child {
    border-bottom: none;
  }

  .activity-avatar {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10.5px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .activity-action {
    font-size: 12px;
    color: var(--n-600);
    line-height: 1.4;
  }

  .activity-time {
    font-size: 10.5px;
    color: var(--n-400);
    margin-top: 1px;
  }

  /* ── QUICK ACTIONS ── */
  .quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .quick-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 11px;
    border-radius: 9px;
    border: 1px solid var(--n-200);
    background: var(--n-50);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    color: var(--n-700);
    transition: all 140ms, transform 0.15s ease;
  }

  .quick-action-btn:hover {
    background: #fff;
    border-color: var(--n-300);
    color: var(--n-900);
    box-shadow: var(--shadow-xs);
    transform: translateY(-1px);
  }

  .quick-action-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .quick-action-icon svg {
    width: 15px;
    height: 15px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* ── MATURITY LEGEND ── */
  .mat-legend {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .mat-legend-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11.5px;
    color: var(--n-700);
  }

  .mat-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
  }

  /* ── SCORE INLINE ── */
  .score-inline {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .score-inline-bar {
    width: 56px;
    height: 5px;
    background: var(--n-100);
    border-radius: 999px;
    overflow: hidden;
    flex-shrink: 0;
  }

  .score-inline-fill {
    height: 100%;
    border-radius: 999px;
  }

  /* ═══════════════════════════════════════════
   RESPONSIVE GRID CLASSES
   All grids use CSS classes — NO inline grid styles
   ═══════════════════════════════════════════ */

  /* Main layout: wide left + right sidebar (v2 standardized) */
  .db-layout-main {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 18px;
    margin-bottom: 20px;
    min-width: 0;
    align-items: start;
  }

  .db-layout-main>* {
    min-width: 0;
  }

  /* Column stacks for vertical card arrangement */
  .col-stack {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
  }

  /* ── BREAKPOINTS ── */

  /* Tablet / medium zoom */
  @media (max-width: 1100px) {
    .db-layout-main {
      grid-template-columns: 1fr 300px;
    }

    .stats-v2 {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  /* Narrow / heavy zoom */
  @media (max-width: 900px) {
    .db-layout-main {
      grid-template-columns: 1fr;
    }

    .stats-v2 {
      grid-template-columns: repeat(2, 1fr);
    }

    .db-hero {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
    }

    .db-hero-right {
      width: 100%;
    }
  }

  /* Mobile */
  @media (max-width: 600px) {
    .stats-v2 {
      grid-template-columns: repeat(2, 1fr);
    }

    .quick-actions {
      grid-template-columns: 1fr;
    }

    .pipeline-step:not(:last-child)::after {
      display: none;
    }

    .db-hero {
      padding: 20px 18px;
    }

    .db-hero-title {
      font-size: 20px;
    }
  }

  /* ── SY SELECTOR ── */
  .sy-selector-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, .10);
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 10px;
    padding: 7px 12px 7px 10px;
    cursor: pointer;
    transition: background 140ms;
    position: relative;
  }

  .sy-selector-wrap:hover {
    background: rgba(255, 255, 255, .18);
  }

  .sy-selector-label {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255, 255, 255, .55);
    text-transform: uppercase;
    letter-spacing: .07em;
    white-space: nowrap;
  }

  .sy-selector-value {
    font-size: 13.5px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
  }

  .sy-selector-wrap select {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    font-size: 14px;
  }

  /* ── CONTEXT BAR ── */
  .sy-context-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 18px;
    border: 1px solid;
  }

  .sy-context-bar.is-current {
    background: #F0FDF4;
    border-color: #86EFAC;
    color: #166534;
  }

  .sy-context-bar.is-historical {
    background: #EFF6FF;
    border-color: #93C5FD;
    color: #1E40AF;
  }

  .sy-context-bar svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* ── SY SIDEBAR ROWS ── */
  .sy-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    border-radius: 8px;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 120ms;
    gap: 8px;
  }

  .sy-row:hover {
    background: var(--n-50);
    border-color: var(--n-200);
  }

  .sy-row.sy-row-active {
    background: var(--brand-50, #F0FDF4);
    border-color: var(--brand-200, #86EFAC);
  }

  .sy-row-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--n-900);
  }

  .sy-row.sy-row-active .sy-row-label {
    color: var(--brand-700, #15803D);
  }

  .sy-row-dates {
    font-size: 11px;
    color: var(--n-400);
    margin-top: 1px;
  }

  .sy-row-caret {
    font-size: 12px;
    color: var(--n-300);
    flex-shrink: 0;
  }

  .sy-row.sy-row-active .sy-row-caret {
    color: var(--brand-600, #16A34A);
  }

  /* ── CUSTOM SY DROPDOWN ── */
  .sy-dd {
    position: relative;
    z-index: 200;
  }

  .sy-dd-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, .10);
    border: 1px solid rgba(255, 255, 255, .20);
    border-radius: 10px;
    padding: 8px 13px 8px 11px;
    cursor: pointer;
    transition: background 140ms, border-color 140ms;
    user-select: none;
    white-space: nowrap;
  }

  .sy-dd-trigger:hover,
  .sy-dd.open .sy-dd-trigger {
    background: rgba(255, 255, 255, .18);
    border-color: rgba(255, 255, 255, .35);
  }

  .sy-dd-icon {
    width: 15px;
    height: 15px;
    stroke: rgba(255, 255, 255, .65);
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
  }

  .sy-dd-text {
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  .sy-dd-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .50);
    line-height: 1;
  }

  .sy-dd-value {
    font-size: 13.5px;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
  }

  .sy-dd-chevron {
    width: 13px;
    height: 13px;
    stroke: rgba(255, 255, 255, .50);
    fill: none;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
    transition: transform 200ms;
  }

  .sy-dd.open .sy-dd-chevron {
    transform: rotate(180deg);
  }

  /* Panel */
  .sy-dd-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 210px;
    background: #fff;
    border: 1px solid var(--n-200, #E5E7EB);
    border-radius: 12px;
    box-shadow: 0 8px 28px rgba(0, 0, 0, .14), 0 2px 8px rgba(0, 0, 0, .06);
    padding: 6px;
    opacity: 0;
    transform: translateY(-6px);
    pointer-events: none;
    transition: opacity 180ms ease, transform 180ms ease;
  }

  .sy-dd.open .sy-dd-panel {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
  }

  .sy-dd-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    text-decoration: none;
    color: var(--n-700, #374151);
    font-size: 13.5px;
    font-weight: 500;
    transition: background 120ms;
  }

  .sy-dd-item:hover {
    background: var(--n-50, #F9FAFB);
    color: var(--n-900, #111827);
  }

  .sy-dd-item.active {
    background: #F0FDF4;
    color: #15803D;
    font-weight: 600;
  }

  .sy-dd-item.active:hover {
    background: #DCFCE7;
  }

  .sy-dd-item-text {
    flex: 1;
    min-width: 0;
  }

  .sy-dd-item-name {
    font-size: 13.5px;
    font-weight: inherit;
  }

  .sy-dd-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #16A34A;
    flex-shrink: 0;
  }

  .sy-dd-divider {
    height: 1px;
    background: var(--n-100, #F3F4F6);
    margin: 4px 6px;
  }
</style>

<!-- ═══════════ HERO ═══════════ -->
<div class="db-hero">
  <div class="db-hero-bg">
    <div class="db-hero-shimmer"></div>
  </div>
  <div class="db-hero-left">
    <div class="db-hero-greeting">SBM Online Monitoring System</div>
    <div class="db-hero-title">School Head Dashboard</div>
    <div class="db-hero-sub" style="margin-bottom:12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      <?= date('l, F j, Y') ?>
      &nbsp;·&nbsp; Dasmariñas Integrated High School
    </div>
    <?php if ($deadlineInfo): ?>
      <?= renderDeadlineChip($deadlineInfo, 'dark') ?>
    <?php endif; ?>
  </div>
  <div class="db-hero-right" style="align-items:center;">

    <?php if (count($allSYs) > 0): ?>
      <!-- Custom SY Dropdown -->
      <div class="sy-dd" id="syDropdown">
        <div class="sy-dd-trigger" id="syTrigger" onclick="toggleSyDropdown()" role="button" aria-haspopup="listbox"
          aria-expanded="false">
          <!-- Calendar icon -->
          <svg class="sy-dd-icon" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          <div class="sy-dd-text">
            <span class="sy-dd-label">School Year</span>
            <span class="sy-dd-value">SY <?= e($selectedSYLabel) ?></span>
          </div>
          <!-- Chevron -->
          <svg class="sy-dd-chevron" viewBox="0 0 24 24">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </div>
        <div class="sy-dd-panel" id="syPanel" role="listbox">
          <?php
          $hasCurrentProcessed = false;
          foreach ($allSYs as $i => $sy):
            $isActive = ($sy['sy_id'] == $selectedSyId);

            // Show a divider once we move from 'Current' year to 'Previous' years
            if ($i > 0 && $hasCurrentProcessed && !$sy['is_current']) {
              echo '<div class="sy-dd-divider"></div>';
              $hasCurrentProcessed = false; // Only show one divider
            }
            if ($sy['is_current']) {
              $hasCurrentProcessed = true;
            }
            ?>
            <a href="dashboard.php?sy_id=<?= $sy['sy_id'] ?>" class="sy-dd-item <?= $isActive ? 'active' : '' ?>"
              role="option" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
              <div class="sy-dd-item-text">
                <div class="sy-dd-item-name">SY <?= e($sy['label']) ?>
                  <?php if ($sy['is_current']): ?>
                    <span
                      style="font-size:10px;font-weight:700;color:#16A34A;margin-left:5px;background:#DCFCE7;padding:1px 6px;border-radius:999px;">Current</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ($isActive): ?><span class="sy-dd-dot"></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <a href="assessment.php?status=submitted&sy_id=<?= $selectedSyId ?>" class="db-hero-btn db-hero-btn-primary">
      <svg viewBox="0 0 24 24">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
        <polyline points="22 4 12 14.01 9 11.01" />
      </svg>
      Review Submissions
      <?php if ($submitted - $validated > 0): ?>
        <span
          style="background:rgba(255,255,255,.2);border-radius:999px;padding:1px 7px;font-size:11px;"><?= $submitted - $validated ?></span>
      <?php endif; ?>
    </a>
    <a href="analytics.php?sy_id=<?= $selectedSyId ?>" class="db-hero-btn db-hero-btn-secondary">
      <svg viewBox="0 0 24 24">
        <line x1="18" y1="20" x2="18" y2="10" />
        <line x1="12" y1="20" x2="12" y2="4" />
        <line x1="6" y1="20" x2="6" y2="14" />
      </svg>
      Analytics
    </a>
    <a href="reports.php?sy_id=<?= $selectedSyId ?>" class="db-hero-btn db-hero-btn-secondary">
      <svg viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
      </svg>
      Reports
    </a>
  </div><!-- /db-hero-right -->
</div><!-- /db-hero -->

<!-- ═══════════ SY CONTEXT BAR (Hidden for current year) ═══════════ -->
<?php if (!$isCurrentSY): ?>
  <div class="sy-context-bar is-historical">
    <svg viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
      <polyline points="12 6 12 12 16 14" />
    </svg>
    <?php if ($hasData): ?>
      Viewing data for <strong>SY <?= e($selectedSYLabel) ?></strong>
    <?php else: ?>
      <span>No assessment data found for <strong>SY <?= e($selectedSYLabel) ?></strong> — this year may not have any
        cycles
        yet.</span>
    <?php endif; ?>
    <a href="dashboard.php?sy_id=<?= $currentSYRow['sy_id'] ?? '' ?>"
      style="margin-left:auto;font-weight:700;white-space:nowrap;color:inherit;text-decoration:none;opacity:.8;">←
      Current
      SY</a>
  </div>
<?php endif; ?>

<?php if ($returned > 0): ?>
  <div
    style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;background:var(--amber-bg);border:1px solid #FDE68A;margin-bottom:14px;font-size:13px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
      style="width:14px;height:14px;flex-shrink:0;">
      <circle cx="12" cy="12" r="10" />
      <line x1="12" y1="8" x2="12" y2="12" />
      <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
    <span><strong><?= $returned ?> assessment<?= $returned !== 1 ? 's' : '' ?></strong> returned for revision — awaiting
      feedback.</span>
    <a href="assessment.php?status=returned"
      style="margin-left:auto;font-weight:700;color:var(--amber);white-space:nowrap;">View →</a>
  </div>
<?php endif; ?>

<!-- ═══════════ KPI STATS ═══════════ -->
<div class="stats-v2">
  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#16A34A;"></div>
    <div class="stat-v2-label">Portal Users</div>
    <div class="stat-v2-value"><?= number_format($portalUsers) ?></div>
    <div class="stat-v2-meta" style="color:var(--n-400);">Active portal accounts</div>
  </div>
  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#2563EB;"></div>
    <div class="stat-v2-label">Assessment Cycles</div>
    <div class="stat-v2-value" data-live="total-cycles"><?= number_format($totalCycles) ?></div>
    <div class="stat-v2-meta"><span class="stat-v2-badge badge-blue"><?= $inProgress ?> in progress</span></div>
    <div class="kpi-bar">
      <div class="kpi-bar-fill" style="width:<?= min(100, $totalCycles * 10) ?>%;background:#2563EB;"></div>
    </div>
  </div>
  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#D97706;"></div>
    <div class="stat-v2-label">Awaiting Validation</div>
    <div class="stat-v2-value" style="color:<?= ($submitted - $validated) > 0 ? 'var(--amber)' : 'var(--n-900)' ?>;">
      <?= $submitted - $validated ?>
    </div>
    <div class="stat-v2-meta"><span class="stat-v2-badge badge-amber"><?= $submitted ?> total submitted</span></div>
    <div class="kpi-bar">
      <div class="kpi-bar-fill" style="width:<?= $validationRate ?>%;background:#D97706;"></div>
    </div>
  </div>
  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#16A34A;"></div>
    <div class="stat-v2-label">Validated</div>
    <div class="stat-v2-value" data-live="validated"><?= number_format($validated) ?></div>
    <div class="stat-v2-meta"><span class="stat-v2-badge badge-green"><?= $validationRate ?>% of submitted</span>
    </div>
    <div class="kpi-bar">
      <div class="kpi-bar-fill" style="width:<?= $validationRate ?>%;background:#16A34A;"></div>
    </div>
  </div>
  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#7C3AED;"></div>
    <div class="stat-v2-label">Active Users</div>
    <div class="stat-v2-value" data-live="total-users"><?= number_format($totalUsers) ?></div>
    <div class="stat-v2-meta" style="color:var(--n-400);">Across all roles</div>
  </div>
</div>

<!-- ═══════════ PIPELINE ═══════════ -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-head">
    <span class="card-title">Assessment Pipeline</span>
    <a href="assessment.php" class="btn btn-ghost btn-sm">View all →</a>
  </div>
  <div class="card-body" style="padding:8px 0;">
    <div class="pipeline">
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--n-500);"><?= $inProgress ?></div>
        <div class="pipeline-lbl">In Progress</div>
      </div>
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--amber);"><?= $submitted - $validated ?></div>
        <div class="pipeline-lbl">Pending Review</div>
      </div>
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--brand-600);"><?= $validated ?></div>
        <div class="pipeline-lbl">Validated</div>
      </div>
      <?php if ($returned > 0): ?>
        <div class="pipeline-step">
          <div class="pipeline-val" style="color:var(--red);"><?= $returned ?></div>
          <div class="pipeline-lbl">Returned</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ═══════════ MAIN GRID ═══════════ -->
<div class="db-layout-main">

  <!-- LEFT: Dimension Performance + Chart -->
  <div class="col-stack">

    <div class="card">
      <div class="card-head">
        <span class="card-title">Dimension Performance</span>
        <span style="font-size:12px;color:var(--n-400);">SY <?= e($selectedSYLabel) ?> averages</span>
      </div>
      <div class="card-body">
        <div class="dim-list">
          <?php foreach ($dimScores as $d):
            $pct = floatval($d['avg_pct']);
            $matColor = $pct >= 76 ? '#16A34A' : ($pct >= 51 ? '#2563EB' : ($pct >= 26 ? '#D97706' : '#DC2626'));
            ?>
            <div class="dim-row">
              <div class="dim-num" style="background:<?= e($d['color_hex']) ?>;"><?= $d['dimension_no'] ?></div>
              <div class="dim-info">
                <div class="dim-name"><?= e($d['dimension_name']) ?></div>
                <div class="dim-prog">
                  <div class="dim-prog-fill" style="width:<?= min(100, $pct) ?>%;background:<?= e($d['color_hex']) ?>;">
                  </div>
                </div>
              </div>
              <div class="dim-pct" style="color:<?= $pct > 0 ? $matColor : 'var(--n-400)' ?>;">
                <?= $pct > 0 ? $pct . '%' : '—' ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><span class="card-title">Dimension Score Comparison</span></div>
      <div class="card-body" style="padding:16px 20px 18px;">
        <div style="position:relative;height:190px;">
          <canvas id="dimBarChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent Assessment Cycles -->
    <div class="card" style="min-width:0; margin-top: 10px;">
      <div class="card-head">
        <span class="card-title">Recent Assessment Cycles</span>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <div class="search" style="min-width:160px;">
            <span class="si"><svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
              </svg></span>
            <input type="text" placeholder="Search…" oninput="filterTable(this.value,'tblRecent')">
          </div>
          <a href="assessment.php" class="btn btn-secondary btn-sm">View all</a>
        </div>
      </div>
      <div class="tbl-wrap">
        <table id="tblRecent" class="tbl-enhanced">
          <thead>
            <tr>
              <th>School</th>
              <th>Year</th>
              <th>Status</th>
              <th>Score</th>
              <th>Maturity</th>
              <th>Updated</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentCycles as $c): ?>
              <tr>
                <td>
                  <div style="font-size:13px;font-weight:600;color:var(--n-900);"><?= e($c['school_name']) ?></div>
                </td>
                <td style="color:var(--n-500);font-size:12.5px;"><?= e($c['sy_label']) ?></td>
                <td><span
                    class="pill pill-<?= e($c['status']) ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></span>
                </td>
                <td>
                  <?php if ($c['overall_score']): ?>
                    <div class="score-inline">
                      <div class="score-inline-bar">
                        <div class="score-inline-fill"
                          style="width:<?= $c['overall_score'] ?>%;background:<?= sbmMaturityLevel(floatval($c['overall_score']))['color'] ?>;">
                        </div>
                      </div>
                      <span
                        style="font-family:var(--font-display);font-size:14px;font-weight:800;color:<?= sbmMaturityLevel(floatval($c['overall_score']))['color'] ?>;"><?= $c['overall_score'] ?>%</span>
                    </div>
                  <?php else: ?><span style="color:var(--n-300);">—</span><?php endif; ?>
                </td>
                <td><?php if ($c['maturity_level']): ?><span
                      class="pill pill-<?= e($c['maturity_level']) ?>"><?= e($c['maturity_level']) ?></span><?php else: ?><span
                      style="color:var(--n-300);">—</span><?php endif; ?></td>
                <td style="font-size:12px;color:var(--n-400);"><?= timeAgo($c['created_at']) ?></td>
                <td><a href="view_assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recentCycles): ?>
              <tr>
                <td colspan="7" style="text-align:center;color:var(--n-400);padding:40px;font-size:13px;">No assessment
                  cycles found for SY <?= e($selectedSYLabel) ?>.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- RIGHT: Maturity + Quick Actions -->
  <div class="col-stack">

    <!-- Maturity Distribution -->
    <div class="card">
      <div class="card-head"><span class="card-title">Maturity Distribution</span></div>
      <div class="card-body" style="padding:14px 16px;">
        <?php
        $matData = array_column($maturity, 'cnt', 'maturity_level');
        $matTotal = array_sum(array_column($maturity, 'cnt'));
        $matColors = ['Beginning' => '#DC2626', 'Developing' => '#D97706', 'Maturing' => '#2563EB', 'Advanced' => '#16A34A'];
        ?>
        <?php if ($matTotal > 0): ?>
          <div style="position:relative;max-width:130px;margin:0 auto 12px;">
            <canvas id="maturityChart" style="height:130px;"></canvas>
            <div
              style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
              <div
                style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--n-900);line-height:1;">
                <?= $matTotal ?>
              </div>
              <div style="font-size:10px;color:var(--n-400);font-weight:600;">cycles</div>
            </div>
          </div>
          <div class="mat-legend">
            <?php foreach (['Beginning', 'Developing', 'Maturing', 'Advanced'] as $lv):
              $cnt = $matData[$lv] ?? 0;
              $pct2 = $matTotal > 0 ? round(($cnt / $matTotal) * 100) : 0;
              ?>
              <div class="mat-legend-row">
                <span class="mat-dot" style="background:<?= $matColors[$lv] ?>;"></span>
                <span style="flex:1;"><?= $lv ?></span>
                <span style="font-weight:700;font-size:13px;color:<?= $matColors[$lv] ?>;"><?= $cnt ?></span>
                <span style="font-size:11px;color:var(--n-400);min-width:32px;text-align:right;"><?= $pct2 ?>%</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="text-align:center;color:var(--n-400);font-size:13px;padding:24px 0;">No validated assessments yet.
          </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- School Years -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">School Years</span>
        <a href="settings.php" class="btn btn-ghost btn-sm" style="margin-left:auto;">Manage</a>
      </div>
      <div class="card-body" style="padding:6px 8px;">
        <div style="display:flex;flex-direction:column;gap:4px;">
          <?php foreach ($allSYs as $sy):
            $isSelected = ($sy['sy_id'] == $selectedSyId);
            ?>
            <a href="dashboard.php?sy_id=<?= $sy['sy_id'] ?>" class="sy-row <?= $isSelected ? 'sy-row-active' : '' ?>">
              <div style="min-width:0;flex:1;">
                <div class="sy-row-label">
                  SY <?= e($sy['label']) ?>
                  <?php if ($sy['is_current']): ?>
                    <span class="pill pill-active" style="font-size:10px;margin-left:4px;">Current</span>
                  <?php endif; ?>
                </div>
                <div class="sy-row-dates">
                  <?= $sy['date_start'] ? date('M Y', strtotime($sy['date_start'])) : '—' ?> –
                  <?= $sy['date_end'] ? date('M Y', strtotime($sy['date_end'])) : 'Present' ?>
                </div>
              </div>
              <span class="sy-row-caret"><?= $isSelected ? '●' : '›' ?></span>
            </a>
          <?php endforeach; ?>
          <?php if (!$allSYs): ?>
            <p style="text-align:center;color:var(--n-400);font-size:12px;padding:12px 0;">No school years configured.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-head"><span class="card-title">Quick Actions</span></div>
      <div class="card-body" style="padding:10px 12px;">
        <div class="quick-actions">
          <a href="assessment.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--purple-bg);color:var(--purple);">
              <svg viewBox="0 0 24 24">
                <path d="M9 11l3 3L22 4" />
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
              </svg>
            </div>
            Assessments
          </a>
          <a href="school_profile.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--brand-100);color:var(--brand-700);">
              <svg viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
              </svg>
            </div>
            School Profile
          </a>
          <a href="announcements.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--amber-bg);color:var(--amber);">
              <svg viewBox="0 0 24 24">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
              </svg>
            </div>
            Announce
          </a>
          <a href="workflow.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--n-100);color:var(--n-600);">
              <svg viewBox="0 0 24 24">
                <polyline points="22 6 12 16 8 12" />
                <path d="M16 6h6v6" />
                <path d="M12 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-7" />
              </svg>
            </div>
            Workflow
          </a>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <div class="card-head"><span class="card-title">Recent Activity</span></div>
      <div class="card-body" style="padding:12px 16px;">
        <div class="activity-feed">
          <?php foreach ($recentActivity as $log):
            $initials = strtoupper(substr($log['full_name'] ?? 'S', 0, 1));
            $bgMap = ['A' => '#EDE9FE', 'B' => '#DBEAFE', 'C' => '#DCFCE7', 'D' => '#FEF3C7', 'E' => '#FEE2E2', 'F' => '#CCFBF1', 'G' => '#F0FDF4', 'H' => '#FEF9C3', 'I' => '#DBEAFE', 'J' => '#F3E8FF', 'K' => '#ECFDF5', 'L' => '#FFF7ED', 'M' => '#EFF6FF', 'N' => '#FDF4FF', 'O' => '#F0FDF4', 'P' => '#FFF1F2', 'Q' => '#F0FFFE', 'R' => '#FFF7ED', 'S' => '#F0FDF4', 'T' => '#EDE9FE', 'U' => '#DBEAFE', 'V' => '#DCFCE7', 'W' => '#FEF3C7', 'X' => '#FEE2E2', 'Y' => '#CCFBF1', 'Z' => '#EDE9FE'];
            $txMap = ['A' => '#7C3AED', 'B' => '#2563EB', 'C' => '#16A34A', 'D' => '#D97706', 'E' => '#DC2626', 'F' => '#0D9488', 'G' => '#15803D', 'H' => '#CA8A04', 'I' => '#1D4ED8', 'J' => '#7E22CE', 'K' => '#059669', 'L' => '#C2410C', 'M' => '#1E40AF', 'N' => '#A21CAF', 'O' => '#166534', 'P' => '#BE123C', 'Q' => '#0F766E', 'R' => '#C2410C', 'S' => '#166534', 'T' => '#6D28D9', 'U' => '#1D4ED8', 'V' => '#15803D', 'W' => '#B45309', 'X' => '#B91C1C', 'Y' => '#0F766E', 'Z' => '#6D28D9'];
            $bg = $bgMap[$initials] ?? '#DCFCE7';
            $tx = $txMap[$initials] ?? '#16A34A';
            ?>
            <div class="activity-item">
              <div class="activity-avatar" style="background:<?= $bg ?>;color:<?= $tx ?>;"> <?= $initials ?></div>
              <div style="flex:1;min-width:0;">
                <div class="activity-action"><strong><?= e($log['full_name'] ?? 'System') ?></strong> —
                  <?= e(formatActivityAction($log['action'])) ?>
                </div>
                <div class="activity-time"><?= timeAgo($log['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$recentActivity): ?>
            <p style="text-align:center;color:var(--n-400);font-size:13px;padding:24px 0;">No activity yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>


<script>
  const dimLabels = <?= json_encode(array_map(fn($d) => 'D' . $d['dimension_no'], $dimScores)) ?>;
  const dimValues = <?= json_encode(array_map(fn($d) => $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $dimScores)) ?>;
  const dimColors = <?= json_encode(array_column($dimScores, 'color_hex')) ?>;

  new Chart(document.getElementById('dimBarChart'), {
    type: 'bar',
    data: {
      labels: dimLabels,
      datasets: [{
        label: 'Average Score (%)',
        data: dimValues,
        backgroundColor: dimColors.map(c => c + '33'),
        borderColor: dimColors,
        borderWidth: 2,
        borderRadius: 7,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
        x: { ticks: { font: { size: 12, weight: '600' } }, grid: { display: false } }
      },
      plugins: { legend: { display: false } }
    }
  });

  <?php if ($matTotal > 0): ?>
    new Chart(document.getElementById('maturityChart'), {
      type: 'doughnut',
      data: {
        labels: ['Beginning', 'Developing', 'Maturing', 'Advanced'],
        datasets: [{
          data: [
            <?= $matData['Beginning'] ?? 0 ?>,
            <?= $matData['Developing'] ?? 0 ?>,
            <?= $matData['Maturing'] ?? 0 ?>,
            <?= $matData['Advanced'] ?? 0 ?>
          ],
          backgroundColor: ['#DC2626', '#D97706', '#2563EB', '#16A34A'],
          borderWidth: 3,
          borderColor: '#fff',
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: { legend: { display: false } }
      }
    });
  <?php endif; ?>
  // ── SY Dropdown toggle ──────────────────────────────────────
  function toggleSyDropdown() {
    const dd = document.getElementById('syDropdown');
    const trigger = document.getElementById('syTrigger');
    if (!dd || !trigger) return;
    const isOpen = dd.classList.toggle('open');
    trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }
  document.addEventListener('click', function (e) {
    const dd = document.getElementById('syDropdown');
    const trigger = document.getElementById('syTrigger');
    if (dd && !dd.contains(e.target)) {
      dd.classList.remove('open');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const dd = document.getElementById('syDropdown');
      const trigger = document.getElementById('syTrigger');
      if (dd) {
        dd.classList.remove('open');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      }
    }
  });
</script>

<?= deadlineChipCss() ?>
<?= deadlineChipJs() ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>