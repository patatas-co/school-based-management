<?php
ob_start();
// ============================================================
// coordinator/dashboard.php — REDESIGNED to match school_head proportions
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('sbm_coordinator');
$db = getDB();

$uid = $_SESSION['user_id'];
$schoolId = SCHOOL_ID;

$school = $db->prepare("SELECT * FROM schools WHERE school_id=?");
$school->execute([$schoolId]);
$school = $school->fetch();

$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$syId = $syId ? (int) $syId : null;
$syLabel = '—';
if ($syId) {
  $stSyLabel = $db->prepare("SELECT label FROM school_years WHERE sy_id=? LIMIT 1");
  $stSyLabel->execute([$syId]);
  $syLabel = $stSyLabel->fetchColumn() ?: '—';
}

// All school years for compare selector
$allSYs = $db->query("SELECT * FROM school_years ORDER BY label DESC")->fetchAll();

$cycle = null;
if ($schoolId && $syId) {
  $st = $db->prepare("SELECT c.*,sy.label sy_label FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND c.sy_id=? LIMIT 1");
  $st->execute([$schoolId, $syId]);
  $cycle = $st->fetch();
}

$dimScores = [];
if ($cycle) {
  $st = $db->prepare("SELECT ds.*,d.dimension_no,d.dimension_name,d.color_hex,d.indicator_count FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
  $st->execute([$cycle['cycle_id']]);
  $dimScores = $st->fetchAll();
}

$totalIndicators = $db->query("SELECT COUNT(*) FROM sbm_indicators WHERE is_active=1")->fetchColumn();
$totalResponded = 0;
if ($cycle) {
  $t = $db->prepare("
        SELECT COUNT(DISTINCT indicator_id)
        FROM (
            SELECT indicator_id FROM sbm_responses WHERE cycle_id=?
            UNION
            SELECT indicator_id FROM teacher_responses WHERE cycle_id=?
        ) AS all_responses
    ");
  $t->execute([$cycle['cycle_id'], $cycle['cycle_id']]);
  $totalResponded = $t->fetchColumn();
}
$progress = $totalIndicators > 0 ? round(($totalResponded / $totalIndicators) * 100) : 0;

$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.target_role IN('all','sbm_coordinator','school_head') ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

// Teacher submissions
$teacherList = [];
if ($cycle) {
  $tq = $db->prepare("
        SELECT u.user_id, u.full_name,
               ts.status sub_status, ts.submitted_at, ts.response_count,
               (SELECT COUNT(*) FROM teacher_responses tr WHERE tr.cycle_id=? AND tr.teacher_id=u.user_id) live_count,
               (SELECT COUNT(*) FROM teacher_indicator_assignments tia WHERE tia.teacher_id=u.user_id) assigned_count
        FROM users u
        LEFT JOIN teacher_submissions ts ON ts.teacher_id=u.user_id AND ts.cycle_id=?
        WHERE u.school_id=? AND u.role='teacher' AND u.status='active'
        ORDER BY ts.status DESC, u.full_name ASC
    ");
  $tq->execute([$cycle['cycle_id'], $cycle['cycle_id'], $schoolId]);
  $teacherList = $tq->fetchAll();
}
$submittedTeachers = count(array_filter($teacherList, fn($t) => $t['sub_status'] === 'submitted'));
$totalTeachers = count($teacherList);

// AI Recommendations
$recommendations = [];
if ($cycle) {
  try {
    $recStmt = $db->prepare("SELECT * FROM ml_recommendations WHERE cycle_id=? ORDER BY priority_score DESC LIMIT 4");
    $recStmt->execute([$cycle['cycle_id']]);
    $recommendations = $recStmt->fetchAll();
  } catch (Exception $e) {
  }
}

// ── Deadline awareness ────────────────────────────────────────
$deadlineInfo = $syId ? getDeadlineInfo($db, $syId) : null;

$isLocked = $cycle && in_array($cycle['status'], ['submitted', 'validated']);
$hasScore = $cycle && $cycle['overall_score'];
$mat = $hasScore ? sbmMaturityLevel(floatval($cycle['overall_score'])) : null;

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// ANALYTICS DATA
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// Comparison SY
$compareSyId = (int) ($_GET['compare_sy'] ?? 0);

// Analytics dimension averages
$anDimAvgQ = $db->prepare("
    SELECT d.dimension_id, d.dimension_no, d.dimension_name, d.color_hex,
           ROUND(AVG(ds.percentage),1) AS avg_pct
    FROM sbm_dimensions d
    LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
    LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY d.dimension_id ORDER BY d.dimension_no
");
$anDimAvgQ->execute([$syId, $schoolId]);
$anDimAvgs = $anDimAvgQ->fetchAll();

// Comparison SY dimension averages
$anDimAvgsCompare = [];
if ($compareSyId && $compareSyId !== $syId) {
  $cmpQ = $db->prepare("
    SELECT d.dimension_no, d.dimension_name, d.color_hex,
           ROUND(AVG(ds.percentage),1) AS avg_pct
    FROM sbm_dimensions d
    LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
    LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY d.dimension_id ORDER BY d.dimension_no
  ");
  $cmpQ->execute([$compareSyId, $schoolId]);
  $anDimAvgsCompare = $cmpQ->fetchAll();
}

// Assessment history
$historyQ = $db->prepare("
    SELECT sy.label AS sy_label, sy.sy_id,
           c.cycle_id, c.overall_score, c.maturity_level,
           c.status, c.validated_at
    FROM sbm_cycles c
    JOIN school_years sy ON c.sy_id = sy.sy_id
    WHERE c.school_id = ? AND c.overall_score IS NOT NULL
    ORDER BY sy.date_start ASC
");
$historyQ->execute([$schoolId]);
$cycleHistory = $historyQ->fetchAll();

// Trend data
$trendQ = $db->prepare("
    SELECT sy.label AS sy_label, sy.sy_id,
           d.dimension_no, d.dimension_name, d.color_hex,
           ROUND(AVG(ds.percentage),1) AS avg_pct
    FROM sbm_dimension_scores ds
    JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
    JOIN school_years sy ON c.sy_id = sy.sy_id
    JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
    WHERE c.school_id = ? AND c.overall_score IS NOT NULL
    GROUP BY sy.sy_id, d.dimension_id
    ORDER BY sy.date_start ASC, d.dimension_no ASC
");
$trendQ->execute([$schoolId]);
$trendRows = $trendQ->fetchAll();
$trendBySY = [];
$trendByDim = [];
$trendSYLabels = [];
foreach ($trendRows as $tr) {
  $trendSYLabels[$tr['sy_id']] = $tr['sy_label'];
  $trendBySY[$tr['sy_label']][$tr['dimension_no']] = floatval($tr['avg_pct']);
  $trendByDim[$tr['dimension_no']][$tr['sy_label']] = floatval($tr['avg_pct']);
}
$trendSYLabels = array_values($trendSYLabels);

// Weak indicators
$weakQ = $db->prepare("
    SELECT i.indicator_id, i.indicator_code, i.indicator_text,
           d.dimension_id, d.dimension_name, d.color_hex,
           ROUND(AVG(all_r.rating), 2) AS avg_rating,
           COUNT(all_r.rating) AS response_count
    FROM (
        SELECT cycle_id, indicator_id, rating FROM sbm_responses
        UNION ALL
        SELECT cycle_id, indicator_id, rating FROM teacher_responses
    ) AS all_r
    JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    JOIN sbm_cycles c     ON all_r.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY i.indicator_id
    HAVING avg_rating <= 2.5
    ORDER BY avg_rating ASC
");
$weakQ->execute([$syId, $schoolId]);
$weakIndicatorRows = $weakQ->fetchAll();

// Consistently weak indicators
$consistentlyWeakQ = $db->prepare("
    SELECT i.indicator_code, i.indicator_text,
           d.dimension_name, d.color_hex,
           ROUND(AVG(all_r.rating), 2)  AS avg_rating,
           COUNT(DISTINCT c.sy_id)      AS cycle_count,
           MIN(ROUND(per_cy.avg_r, 2))  AS worst_cycle_avg,
           MAX(ROUND(per_cy.avg_r, 2))  AS best_cycle_avg
    FROM (
        SELECT cycle_id, indicator_id, rating FROM sbm_responses
        UNION ALL
        SELECT cycle_id, indicator_id, rating FROM teacher_responses
    ) AS all_r
    JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    JOIN sbm_cycles c     ON all_r.cycle_id = c.cycle_id AND c.school_id = ?
    JOIN (
        SELECT r2.indicator_id, r2.cycle_id, AVG(r2.rating) AS avg_r
        FROM (
            SELECT cycle_id, indicator_id, rating FROM sbm_responses
            UNION ALL
            SELECT cycle_id, indicator_id, rating FROM teacher_responses
        ) r2
        JOIN sbm_cycles c2 ON r2.cycle_id = c2.cycle_id AND c2.school_id = ?
        GROUP BY r2.indicator_id, r2.cycle_id
    ) per_cy ON per_cy.indicator_id = all_r.indicator_id
    GROUP BY i.indicator_id
    HAVING cycle_count >= 1 AND avg_rating <= 2.5
    ORDER BY avg_rating ASC LIMIT 6
");
$consistentlyWeakQ->execute([$schoolId, $schoolId]);
$consistentlyWeak = $consistentlyWeakQ->fetchAll();

// Summary insights
$anAllPcts = array_filter(array_column($anDimAvgs, 'avg_pct'), fn($v) => $v !== null);
$anAvgOverall = count($anAllPcts) > 0 ? round(array_sum($anAllPcts) / count($anAllPcts), 1) : null;
$anTopDim = !empty($anAllPcts) ? $anDimAvgs[array_search(max($anAllPcts), array_column($anDimAvgs, 'avg_pct'))] : null;
$anWeakDim = !empty($anAllPcts) ? $anDimAvgs[array_search(min($anAllPcts), array_column($anDimAvgs, 'avg_pct'))] : null;
$prevCycle = count($cycleHistory) >= 2 ? $cycleHistory[count($cycleHistory) - 2] : null;
$currCycle = count($cycleHistory) >= 1 ? $cycleHistory[count($cycleHistory) - 1] : null;
$scoreDelta = ($currCycle && $prevCycle)
  ? round(floatval($currCycle['overall_score']) - floatval($prevCycle['overall_score']), 2) : null;

$pageTitle = 'Coordinator Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ── HERO (mirrors school_head hero) ── */
  .coord-hero {
    background:
      linear-gradient(to right, rgba(8, 26, 8, 0.8) 0%, rgba(8, 26, 8, 0.4) 50%, rgba(8, 26, 8, 0.1) 100%),
      url('<?= e(baseUrl()) ?>/assets/cover.png') center/cover no-repeat;
    background-color: #081a08;
    border-radius: var(--radius-lg);
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
  }



  .coord-hero-left {
    position: relative;
    z-index: 1;
  }

  .coord-hero-eyebrow {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: rgba(74, 222, 128, .65);
    margin-bottom: 10px;
  }

  .coord-hero-title {
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.8px;
    margin-bottom: 8px;
    line-height: 1.12;
  }

  .coord-hero-sub {
    font-size: 12px;
    color: rgba(255, 255, 255, .38);
    line-height: 1.5;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .coord-hero-right {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
    align-items: center;
  }

  .hero-btn {
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
    cursor: pointer;
    border: none;
  }

  .hero-btn-primary {
    background: rgba(74, 222, 128, 0.15);
    color: #fff;
    border: 1px solid rgba(74, 222, 128, 0.35);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }

  .hero-btn-primary:hover {
    background: rgba(74, 222, 128, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.20);
  }

  .hero-btn-secondary {
    background: rgba(255, 255, 255, .06);
    color: rgba(255, 255, 255, .80);
    border: 1px solid rgba(255, 255, 255, .14);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
  }

  .hero-btn-secondary:hover {
    background: rgba(255, 255, 255, .13);
    transform: translateY(-1px);
    color: #fff;
  }

  .hero-btn svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* ── PROGRESS RING in hero ── */
  .hero-ring-wrap {
    position: relative;
    width: 86px;
    height: 86px;
    flex-shrink: 0;
  }

  .hero-ring-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
  }

  .hero-ring-svg circle {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
  }

  .hero-ring-track {
    stroke: rgba(255, 255, 255, .1);
  }

  .hero-ring-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
  }

  .hero-ring-pct {
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    line-height: 1;
    letter-spacing: -0.5px;
  }

  .hero-ring-label {
    font-size: 8.5px;
    color: rgba(255, 255, 255, .30);
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    margin-top: 3px;
  }

  /* ── OVERALL SCORE CHIP in hero ── */
  .hero-score-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 26px;
    border-radius: 14px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, .18);
    background: rgba(255, 255, 255, 0.09);
    backdrop-filter: blur(16px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.12);
  }

  .hero-score-num {
    font-family: var(--font-display);
    font-size: 42px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -1.5px;
    color: #fff;
    text-shadow: 0 2px 12px rgba(74, 222, 128, 0.20);
  }

  .hero-score-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .10em;
    color: rgba(255, 255, 255, .45);
    margin-top: 6px;
  }

  .hero-score-mat {
    font-size: 13px;
    font-weight: 700;
    margin-top: 5px;
    color: #86efac;
    letter-spacing: .02em;
  }

  /* ── KPI STAT CARDS (mirrors school_head stat-v2) ── */
  .stats-v2 {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
  }

  .stats-v2 .stat-v2:nth-child(1) {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 6px 18px rgba(22, 163, 74, 0.06);
  }

  .stats-v2 .stat-v2:nth-child(2) {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 6px 18px rgba(37, 99, 235, 0.05);
  }

  .stats-v2 .stat-v2:nth-child(3) {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 6px 18px rgba(217, 119, 6, 0.05);
  }

  .stats-v2 .stat-v2:nth-child(4) {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 6px 18px rgba(37, 99, 235, 0.05);
  }

  .stats-v2 .stat-v2:nth-child(5) {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 6px 18px rgba(124, 58, 237, 0.06);
  }

  .stat-v2 {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 20px 20px 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: transform 180ms var(--ease), box-shadow 180ms var(--ease);
    position: relative;
    overflow: visible;
    border: 1px solid var(--n-200);
  }

  .stat-v2::after {
    display: none;
  }

  .stat-v2:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08), 0 12px 24px rgba(0, 0, 0, 0.06);
  }

  .stat-v2-accent {
    position: absolute;
    top: -1px;
    left: -1px;
    right: -1px;
    height: 3px;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    z-index: 1;
  }

  .stat-v2-label {
    font-size: 10.5px;
    font-weight: 700;
    color: var(--n-400);
    text-transform: uppercase;
    letter-spacing: .11em;
    margin-bottom: 12px;
  }

  .stat-v2-value {
    font-family: var(--font-display);
    font-size: 36px;
    font-weight: 800;
    color: var(--n-900);
    line-height: 1;
    letter-spacing: -1.2px;
    margin-bottom: 8px;
  }

  .stat-v2-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: var(--n-400);
  }

  .stat-v2-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
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

  .badge-red {
    background: var(--red-bg);
    color: var(--red);
  }

  .kpi-bar {
    height: 5px;
    background: var(--n-150);
    border-radius: 999px;
    overflow: visible;
    margin-top: 10px;
    position: relative;
  }

  .kpi-bar-fill {
    height: 100%;
    border-radius: 999px;
    position: relative;
    animation: barLoadIn 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
    transform-origin: left center;
  }

  .kpi-bar-fill::after {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: 999px;
    background: inherit;
    filter: blur(3px);
    opacity: 0.45;
    z-index: -1;
  }

  @keyframes barLoadIn {
    from {
      transform: scaleX(0);
      opacity: 0;
    }

    to {
      transform: scaleX(1);
      opacity: 1;
    }
  }

  /* ── PIPELINE (same as school_head) ── */
  .pipeline {
    display: flex;
    align-items: stretch;
    gap: 0;
    margin-bottom: 6px;
  }

  .pipeline-step {
    flex: 1;
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
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.8px;
    margin-bottom: 5px;
  }

  .pipeline-lbl {
    font-size: 10px;
    font-weight: 700;
    color: var(--n-400);
    text-transform: uppercase;
    letter-spacing: .10em;
  }

  /* ── DIMENSION LIST (mirrors school_head dim-list) ── */
  .dim-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .dim-row {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .dim-num {
    width: 26px;
    height: 26px;
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
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .dim-prog {
    height: 6px;
    background: var(--n-150);
    border-radius: 999px;
    overflow: visible;
    position: relative;
  }

  .dim-prog-fill {
    height: 100%;
    border-radius: 999px;
    position: relative;
    animation: barLoadIn 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
    transform-origin: left center;
  }

  .dim-prog-fill::after {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: 999px;
    background: inherit;
    filter: blur(3px);
    opacity: 0.40;
    z-index: -1;
  }

  .dim-pct {
    font-family: var(--font-display);
    font-size: 15px;
    font-weight: 800;
    text-align: right;
    flex-shrink: 0;
    min-width: 38px;
    letter-spacing: -0.4px;
  }

  /* ── TEACHER SUBMISSIONS ── */
  .teacher-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--n-200);
    background: var(--n-50);
    transition: background 140ms;
  }

  .teacher-row:hover {
    background: #fff;
    border-color: var(--n-300);
  }

  .teacher-row.submitted {
    background: #F0FDF4;
    border-color: #86EFAC;
  }

  .teacher-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .teacher-name {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: var(--n-800);
  }

  .teacher-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 999px;
    flex-shrink: 0;
  }

  .t-submitted {
    background: #DCFCE7;
    color: #16A34A;
  }

  .t-progress {
    background: #DBEAFE;
    color: #2563EB;
  }

  .t-pending {
    background: var(--n-100);
    color: var(--n-500);
  }

  /* ── QUICK ACTIONS GRID (mirrors school_head) ── */
  .quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .quick-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 12px;
    border-radius: 9px;
    border: 1px solid var(--n-200);
    background: var(--n-50);
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-700);
    transition: all 180ms var(--ease);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }

  .quick-action-btn:hover {
    background: #fff;
    border-color: var(--n-300);
    color: var(--n-900);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06), 0 6px 16px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
  }

  .quick-action-icon {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .quick-action-icon svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* ── AI CARD ── */
  .ai-card {
    border: 1.5px solid #E0E7FF;
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: #fff;
    box-shadow: var(--shadow-xs);
  }

  .ai-card-head {
    background: #EEF2FF;
    border-bottom: 1px solid #E0E7FF;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .ai-card-title {
    font-family: var(--font-display);
    font-size: 13.5px;
    font-weight: 700;
    color: #3730A3;
  }

  .ai-rec-item {
    padding: 12px;
    border: 1px solid var(--n-100);
    border-radius: 8px;
    background: var(--n-50);
  }

  .ai-rec-item:hover {
    background: #fff;
    border-color: var(--n-200);
  }

  /* ── ANN ── */
  .ann-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--n-100);
  }

  .ann-item:last-child {
    border-bottom: none;
  }

  /* ── RESPONSIVE LAYOUT CLASSES ── */
  .main-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 18px;
    margin-bottom: 20px;
    min-width: 0;
  }

  .main-grid>* {
    min-width: 0;
  }

  .hero-actions-wrap {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    flex-shrink: 0;
  }

  .coord-hero {
    flex-wrap: wrap;
  }

  .coord-hero-sub {
    flex-wrap: wrap;
    gap: 4px;
  }

  /* ── VIEW TOGGLE (same as school_head) ── */
  .view-toggle-wrap {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }

  .view-toggle {
    display: inline-flex;
    background: var(--n-100);
    border-radius: 10px;
    padding: 3px;
    gap: 2px;
  }

  .vt-btn {
    padding: 7px 20px;
    border-radius: 8px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    color: var(--n-500);
    cursor: pointer;
    transition: all 160ms;
  }

  .vt-btn.active {
    background: #fff;
    color: var(--n-900);
    box-shadow: 0 1px 4px rgba(0, 0, 0, .10);
  }

  /* ── ANALYTICS STYLES ── */
  .an-filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 12px 16px;
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--n-600);
    font-weight: 500;
  }

  .an-insight-strip {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
  }

  .an-insight-card {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    padding: 16px 18px;
    box-shadow: var(--shadow-xs);
  }

  .an-insight-val {
    font-family: var(--font-display);
    font-size: 26px;
    font-weight: 800;
    color: var(--n-900);
    line-height: 1;
    letter-spacing: -.5px;
    margin-bottom: 4px;
  }

  .an-insight-lbl {
    font-size: 11px;
    font-weight: 600;
    color: var(--n-400);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 4px;
  }

  .an-insight-delta {
    font-size: 11.5px;
    font-weight: 600;
  }

  .an-insight-delta.up {
    color: #16A34A;
  }

  .an-insight-delta.down {
    color: #DC2626;
  }

  .an-insight-delta.flat {
    color: var(--n-400);
  }

  .chart-card {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-xs);
  }

  .chart-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--n-100);
  }

  .chart-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--n-800);
  }

  .chart-card-body {
    padding: 16px 18px;
  }

  .grid2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .an-tab-btns {
    display: flex;
    gap: 4px;
    margin-bottom: 14px;
    flex-wrap: wrap;
  }

  .an-tab-btn {
    padding: 7px 16px;
    border-radius: 8px;
    border: 1px solid var(--n-200);
    background: #fff;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-500);
    cursor: pointer;
    transition: all 140ms;
  }

  .an-tab-btn:hover {
    color: var(--n-800);
    border-color: var(--n-300);
  }

  .an-tab-btn.active {
    background: var(--n-900);
    color: #fff;
    border-color: var(--n-900);
  }

  .an-tab-panel {
    display: none;
  }

  .an-tab-panel.active {
    display: block;
  }

  .an-weak-prog {
    height: 4px;
    background: var(--n-100);
    border-radius: 999px;
    overflow: hidden;
    margin-top: 4px;
  }

  .an-weak-fill {
    height: 100%;
    border-radius: 999px;
  }

  .an-cw-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--n-100);
  }

  .an-cw-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .an-cw-info {
    flex: 1;
    min-width: 0;
  }

  .an-cw-title {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-800);
    margin-bottom: 4px;
    line-height: 1.4;
  }

  .an-cw-meta {
    font-size: 11.5px;
    color: var(--n-400);
    margin-bottom: 5px;
  }

  .an-cw-bar-track {
    height: 4px;
    background: var(--n-100);
    border-radius: 999px;
    overflow: hidden;
  }

  .an-cw-bar-fill {
    height: 100%;
    border-radius: 999px;
  }

  .ai-assistant-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    border: 1px solid var(--n-200);
    background: #fff;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-700);
    cursor: pointer;
    transition: all 140ms;
  }

  .ai-assistant-btn:hover {
    background: var(--n-50);
    border-color: var(--n-300);
    color: var(--n-900);
  }

  /* -- CHART LEGENDS -- */
  .chart-legend {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 4px;
  }

  .chart-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: var(--n-600);
    line-height: 1;
  }

  .chart-legend-swatch {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    flex-shrink: 0;
  }

  /* ── BREAKPOINTS ── */
  @media (max-width: 1100px) {
    .main-grid {
      grid-template-columns: 1fr 300px;
    }

    .stats-v2 {
      grid-template-columns: repeat(3, 1fr);
    }

    .grid2 {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 900px) {
    .main-grid {
      grid-template-columns: 1fr;
    }

    .stats-v2 {
      grid-template-columns: repeat(2, 1fr);
    }

    .coord-hero {
      flex-direction: column;
      align-items: flex-start;
      gap: 18px;
    }

    .coord-hero-right {
      width: 100%;
      flex-wrap: wrap;
    }

    .hero-score-chip {
      flex-direction: row;
      gap: 12px;
      align-items: center;
      padding: 10px 16px;
    }

    .hero-score-num {
      font-size: 26px;
    }

    .an-insight-strip {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 600px) {
    .stats-v2 {
      grid-template-columns: repeat(2, 1fr);
    }

    .quick-actions {
      grid-template-columns: 1fr;
    }

    .pipeline {
      flex-wrap: wrap;
    }

    .pipeline-step {
      min-width: 80px;
    }

    .pipeline-step:not(:last-child)::after {
      display: none;
    }

    .coord-hero {
      padding: 20px 18px;
    }

    .coord-hero-title {
      font-size: 20px;
    }

    .grid2 {
      grid-template-columns: 1fr;
    }
  }
</style>

<?php if ($cycle && $cycle['status'] === 'returned'): ?>
  <div
    style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:9px;background:#FEF3C7;border:1px solid #FDE68A;margin-bottom:16px;font-size:13px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
      style="width:15px;height:15px;flex-shrink:0;margin-top:1px;">
      <circle cx="12" cy="12" r="10" />
      <line x1="12" y1="8" x2="12" y2="12" />
      <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
    <div style="color:#92400E;">
      <strong>Assessment Returned for Revision</strong>
      <?php if ($cycle['validator_remarks']): ?> — <?= e($cycle['validator_remarks']) ?><?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     HERO BANNER — matches school_head proportion
     ═══════════════════════════════════════════════════════ -->
<div class="coord-hero">
  <div class="coord-hero-left">
    <div class="coord-hero-eyebrow">SBM Online Monitoring System</div>
    <div class="coord-hero-title">Coordinator Dashboard</div>
    <div class="coord-hero-sub" style="align-items:center;">
      <?= date('l, F j, Y') ?>
      <?php if ($syLabel): ?>&nbsp;·&nbsp; SY <?= e($syLabel) ?><?php endif; ?>
      &nbsp;·&nbsp; Dasmariñas Integrated High School
      &nbsp;·&nbsp;
      <?php if ($cycle): ?>
        <span class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst(str_replace('_', ' ', $cycle['status'])) ?></span>
      <?php else: ?>
        <span class="pill pill-draft"
          style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.6);border-color:rgba(255,255,255,.15);">Not
          Started</span>
      <?php endif; ?>
    </div>

    <?php if ($deadlineInfo): ?>
      <?= renderDeadlineChip($deadlineInfo, 'dark') ?>
    <?php endif; ?>

  </div>

  <div class="coord-hero-right hero-actions-wrap">
    <!-- Progress ring -->
    <?php
    $circumference = 2 * 3.14159 * 37;
    $offset = $circumference - ($progress / 100) * $circumference;
    $strokeColor = $progress >= 100 ? '#4ADE80' : ($progress >= 50 ? '#60A5FA' : '#FCD34D');
    ?>
    <div class="hero-ring-wrap" style="filter:drop-shadow(0 0 10px rgba(74,222,128,0.40));">
      <svg class="hero-ring-svg" viewBox="0 0 86 86">
        <circle class="hero-ring-track" cx="43" cy="43" r="37" />
        <circle cx="43" cy="43" r="37" stroke="<?= $strokeColor ?>" stroke-width="8" fill="none" stroke-linecap="round"
          stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $offset ?>" />
      </svg>
      <div class="hero-ring-center">
        <div class="hero-ring-pct"><?= $progress ?>%</div>
        <div class="hero-ring-label">done</div>
      </div>
    </div>

    <?php if ($hasScore): ?>
      <div class="hero-score-chip">
        <div class="hero-score-num"><?= number_format($cycle['overall_score'], 1) ?></div>
        <div class="hero-score-label">Overall Score</div>
        <div class="hero-score-mat"><?= e($cycle['maturity_level']) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($cycle && $cycle['status'] === 'in_progress'): ?>
      <a href="self_assessment.php" class="hero-btn hero-btn-primary">
        <svg viewBox="0 0 24 24">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
        Continue Assessment
      </a>
    <?php endif; ?>
    <a href="improvement.php" class="hero-btn hero-btn-secondary">
      <svg viewBox="0 0 24 24">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
        <polyline points="17 6 23 6 23 12" />
      </svg>
      Improvement Plan
    </a>
    <a href="reports.php" class="hero-btn hero-btn-secondary">
      <svg viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
      </svg>
      Reports
    </a>
  </div>
</div>

<!-- ━━━━━━━━━━━ VIEW TOGGLE ━━━━━━━━━━━ -->
<div class="view-toggle-wrap">
  <div class="view-toggle">
    <button class="vt-btn active" onclick="switchView('progress', this)">Progress</button>
    <button class="vt-btn" onclick="switchView('analytics', this)">Analytics</button>
  </div>
</div>

<!-- ━━━━━━━━━━━ PROGRESS VIEW ━━━━━━━━━━━ -->
<div id="viewProgress">

  <!-- ═══════════════════════════════════════════════════════
     KPI STAT CARDS — matches school_head stats-v2
     ═══════════════════════════════════════════════════════ -->
  <div class="stats-v2">
    <div class="stat-v2">
      <div class="stat-v2-accent" style="background:#16A34A;"></div>
      <div class="stat-v2-label">Indicators Rated</div>
      <div class="stat-v2-value"><?= $totalResponded ?></div>
      <div class="stat-v2-meta"><span class="stat-v2-badge badge-green"><?= $progress ?>% complete</span></div>
      <div class="kpi-bar">
        <div class="kpi-bar-fill" style="width:<?= $progress ?>%;background:linear-gradient(90deg,#16A34A,#4ADE80);">
        </div>
      </div>
    </div>
    <div class="stat-v2">
      <div class="stat-v2-accent" style="background:<?= $hasScore ? $mat['color'] : '#6B7280' ?>;"></div>
      <div class="stat-v2-label">SBM Score</div>
      <div class="stat-v2-value" style="color:<?= $hasScore ? $mat['color'] : 'var(--n-300)' ?>;">
        <?= $hasScore ? $cycle['overall_score'] . '%' : '—' ?>
      </div>
      <div class="stat-v2-meta" style="color:var(--n-400);">
        <?= $hasScore ? e($cycle['maturity_level']) : 'Awaiting data' ?>
      </div>
      <?php if ($hasScore): ?>
        <div class="kpi-bar">
          <div class="kpi-bar-fill"
            style="width:<?= $cycle['overall_score'] ?>%;background:linear-gradient(90deg,<?= $mat['color'] ?>,<?= $mat['color'] ?>cc);filter:saturate(1.2);">
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div class="stat-v2">
      <div class="stat-v2-accent"
        style="background:<?= $submittedTeachers === $totalTeachers && $totalTeachers > 0 ? '#16A34A' : '#D97706' ?>;">
      </div>
      <div class="stat-v2-label">Teachers Submitted</div>
      <div class="stat-v2-value"
        style="color:<?= $submittedTeachers === $totalTeachers && $totalTeachers > 0 ? '#16A34A' : 'var(--n-900)' ?>;">
        <?= $submittedTeachers ?>/<?= $totalTeachers ?>
      </div>
      <div class="stat-v2-meta">
        <span
          class="stat-v2-badge <?= $submittedTeachers === $totalTeachers && $totalTeachers > 0 ? 'badge-green' : 'badge-amber' ?>">
          <?= $totalTeachers > 0 ? round(($submittedTeachers / $totalTeachers) * 100) : 0 ?>% submitted
        </span>
      </div>
      <div class="kpi-bar">
        <div class="kpi-bar-fill"
          style="width:<?= $totalTeachers > 0 ? round(($submittedTeachers / $totalTeachers) * 100) : 0 ?>%;background:<?= $submittedTeachers === $totalTeachers && $totalTeachers > 0 ? 'linear-gradient(90deg,#16A34A,#4ADE80)' : 'linear-gradient(90deg,#D97706,#FCD34D)' ?>;">
        </div>
      </div>
    </div>
    <div class="stat-v2">
      <div class="stat-v2-accent" style="background:#2563EB;"></div>
      <div class="stat-v2-label">Dimensions Scored</div>
      <div class="stat-v2-value"><?= count($dimScores) ?></div>
      <div class="stat-v2-meta"><span class="stat-v2-badge badge-blue">of 6 total</span></div>
      <div class="kpi-bar">
        <div class="kpi-bar-fill"
          style="width:<?= round((count($dimScores) / 6) * 100) ?>%;background:linear-gradient(90deg,#2563EB,#60A5FA);">
        </div>
      </div>
    </div>
    <?php if ($totalIndicators > 0): ?>
      <div class="stat-v2">
        <div class="stat-v2-accent" style="background:#7C3AED;"></div>
        <div class="stat-v2-label">Remaining</div>
        <div class="stat-v2-value" style="color:var(--n-700);"><?= $totalIndicators - $totalResponded ?></div>
        <div class="stat-v2-meta" style="color:var(--n-400);">indicators left</div>
        <div class="kpi-bar">
          <div class="kpi-bar-fill"
            style="width:<?= 100 - $progress ?>%;background:linear-gradient(90deg,#7C3AED,#A78BFA);"></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════════════════════════════════════════════════
     ASSESSMENT PIPELINE — mirrors school_head
     ═══════════════════════════════════════════════════════ -->
  <div class="card"
    style="margin-bottom:20px;box-shadow:0 2px 4px rgba(0,0,0,0.06),0 8px 20px rgba(0,0,0,0.05),inset 0 1px 0 rgba(255,255,255,0.90);">
    <div class="card-head">
      <span class="card-title">Assessment Progress Pipeline</span>
      <a href="self_assessment.php" class="btn btn-ghost btn-sm">View assessment →</a>
    </div>
    <div class="card-body" style="padding:8px 0;">
      <div class="pipeline">
        <div class="pipeline-step">
          <div class="pipeline-val" style="color:var(--n-500);"><?= $totalResponded ?></div>
          <div class="pipeline-lbl">Rated</div>
        </div>
        <div class="pipeline-step">
          <div class="pipeline-val" style="color:var(--amber);"><?= $totalIndicators - $totalResponded ?></div>
          <div class="pipeline-lbl">Remaining</div>
        </div>
        <div class="pipeline-step">
          <div class="pipeline-val" style="color:var(--blue);"><?= $submittedTeachers ?></div>
          <div class="pipeline-lbl">Teachers Done</div>
        </div>
        <div class="pipeline-step">
          <div class="pipeline-val" style="color:var(--brand-600);"><?= count($dimScores) ?></div>
          <div class="pipeline-lbl">Dims Scored</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════
     MAIN GRID: Left wide + Right sidebar
     ═══════════════════════════════════════════════════════ -->
  <div class="main-grid db-layout-main">

    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

      <!-- Dimension Performance List -->
      <div class="card"
        style="box-shadow:0 2px 6px rgba(0,0,0,0.07),0 12px 28px rgba(0,0,0,0.06),inset 0 1px 0 rgba(255,255,255,0.95);">
        <div class="card-head">
          <span class="card-title">Dimension Performance</span>
          <a href="dimensions.php" class="btn btn-ghost btn-sm">View details →</a>
        </div>
        <div class="card-body">
          <?php if ($dimScores):
            $dimCompletionData = [];
            $dimActiveCount = [];
            if ($cycle) {
              $dcStmt = $db->prepare("
                SELECT dimension_id, COUNT(DISTINCT indicator_id) cnt FROM (
                    SELECT i.dimension_id, r.indicator_id FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id WHERE r.cycle_id=?
                    UNION
                    SELECT i.dimension_id, r.indicator_id FROM teacher_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id WHERE r.cycle_id=?
                ) as combined
                GROUP BY dimension_id
            ");
              $dcStmt->execute([$cycle['cycle_id'], $cycle['cycle_id']]);
              foreach ($dcStmt->fetchAll() as $dc)
                $dimCompletionData[$dc['dimension_id']] = $dc['cnt'];

              $dacStmt = $db->prepare("SELECT dimension_id, COUNT(*) cnt FROM sbm_indicators WHERE is_active=1 GROUP BY dimension_id");
              $dacStmt->execute();
              foreach ($dacStmt->fetchAll() as $dac)
                $dimActiveCount[$dac['dimension_id']] = $dac['cnt'];
            }
            $chartLabels = $chartData = $chartColors = [];
            ?>
            <div class="dim-list">
              <?php foreach ($dimScores as $ds):
                $pct = floatval($ds['percentage']);
                $mat2 = sbmMaturityLevel($pct);
                $done = $dimCompletionData[$ds['dimension_id']] ?? 0;
                $chartLabels[] = 'D' . $ds['dimension_no'];
                $chartData[] = $pct;
                $chartColors[] = $ds['color_hex'];
                ?>
                <div class="dim-row">
                  <div class="dim-num" style="background:<?= e($ds['color_hex']) ?>;"><?= $ds['dimension_no'] ?></div>
                  <div class="dim-info">
                    <div class="dim-name"><?= e($ds['dimension_name']) ?></div>
                    <div class="dim-prog">
                      <div class="dim-prog-fill"
                        style="width:<?= min(100, $pct) ?>%;background:linear-gradient(90deg,<?= e($ds['color_hex']) ?>,<?= e($ds['color_hex']) ?>bb);">
                      </div>
                    </div>
                  </div>
                  <div style="text-align:right;flex-shrink:0;min-width:90px;">
                    <div class="dim-pct" style="color:<?= $mat2['color'] ?>;"><?= $pct > 0 ? $pct . '%' : '—' ?></div>
                    <div
                      style="font-size:10px;font-weight:600;letter-spacing:.04em;color:var(--n-300);text-transform:uppercase;">
                      <?= $done ?>/<?= $dimActiveCount[$ds['dimension_id']] ?? $ds['indicator_count'] ?> rated
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="text-align:center;padding:40px 20px;color:var(--n-400);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round" style="width:36px;height:36px;margin:0 auto 12px;opacity:.4;">
                <line x1="18" y1="20" x2="18" y2="10" />
                <line x1="12" y1="20" x2="12" y2="4" />
                <line x1="6" y1="20" x2="6" y2="14" />
              </svg>
              <div style="font-size:14px;font-weight:700;color:var(--n-600);margin-bottom:4px;">No dimension data yet
              </div>
              <div style="font-size:13px;">Start the self-assessment to see scores across all 6 SBM dimensions.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Dimension Bar Chart -->
      <div class="card">
        <div class="card-head"><span class="card-title">Dimension Score Comparison</span></div>
        <div class="card-body">
          <div style="position:relative;height:220px;">
            <?php if ($dimScores): ?>
              <canvas id="dimBarChart"></canvas>
            <?php else: ?>
              <div
                style="height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;border:2px dashed var(--n-200);border-radius:8px;background:var(--n-50);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round" style="width:32px;height:32px;color:var(--n-300);margin-bottom:8px;">
                  <line x1="18" y1="20" x2="18" y2="10" />
                  <line x1="12" y1="20" x2="12" y2="4" />
                  <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
                <div style="font-size:13px;font-weight:600;color:var(--n-400);">Chart data unavailable</div>
                <div style="font-size:12px;color:var(--n-400);">Scores will appear once evaluations begin.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /LEFT COLUMN -->

    <!-- ───────────────────────────────────────
       RIGHT SIDEBAR
       ─────────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

      <!-- Validated card -->
      <?php if ($cycle && $cycle['status'] === 'validated'): ?>
        <div class="card" style="border:1.5px solid var(--brand-500);">
          <div class="card-body" style="padding:14px 16px;background:var(--brand-50);">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" style="width:16px;height:16px;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
              <strong style="color:var(--brand-700);">Assessment Validated</strong>
            </div>
            <div style="font-size:12.5px;color:var(--brand-700);">This cycle has been validated by the Administrator.
            </div>
            <?php if ($cycle['validator_remarks']): ?>
              <div style="font-size:12px;color:var(--n-600);margin-top:6px;font-style:italic;">
                "<?= e($cycle['validator_remarks']) ?>"</div>
            <?php endif; ?>
            <div style="margin-top:12px;">
              <button class="btn btn-primary btn-sm" style="width:100%;justify-content:center;gap:6px;"
                onclick="doFinalizeCycle(<?= $cycle['cycle_id'] ?>)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round" style="width:14px;height:14px;">
                  <path d="M12 15l-2 5l10-10l-4-4l-10 10l5 2z" />
                  <path d="M2 22l5-2l-2-5l-3 7z" />
                </svg>
                Finalize & Lock Cycle
              </button>
            </div>
          </div>
        </div>

      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-head"><span class="card-title">Quick Actions</span></div>
        <div class="card-body" style="padding:12px 14px;">
          <div class="quick-actions">
            <a href="self_assessment.php" class="quick-action-btn">
              <div class="quick-action-icon" style="background:var(--brand-100);color:var(--brand-700);">
                <svg viewBox="0 0 24 24">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                  <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
              </div>
              Self-Assessment
            </a>
            <a href="teacher_status.php" class="quick-action-btn">
              <div class="quick-action-icon" style="background:var(--teal-bg);color:var(--teal);">
                <svg viewBox="0 0 24 24">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
              </div>
              Teacher Status
            </a>
            <a href="reports.php" class="quick-action-btn">
              <div class="quick-action-icon" style="background:var(--amber-bg);color:var(--amber);">
                <svg viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </svg>
              </div>
              Reports
            </a>
            <a href="analytics.php" class="quick-action-btn">
              <div class="quick-action-icon" style="background:var(--purple-bg);color:var(--purple);">
                <svg viewBox="0 0 24 24">
                  <line x1="18" y1="20" x2="18" y2="10" />
                  <line x1="12" y1="20" x2="12" y2="4" />
                  <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
              </div>
              Analytics
            </a>
            <a href="evidence.php" class="quick-action-btn">
              <div class="quick-action-icon" style="background:var(--n-100);color:var(--n-600);">
                <svg viewBox="0 0 24 24">
                  <path
                    d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                </svg>
              </div>
              Evidence Files
            </a>
          </div>
        </div>
      </div>

      <!-- Announcements -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Announcements</span>
          <a href="announcements.php" class="btn btn-ghost btn-sm">All →</a>
        </div>
        <div class="card-body" style="padding:10px 16px;">
          <?php if ($anns):
            foreach ($anns as $a): ?>
              <div class="ann-item">
                <span class="pill pill-<?= e($a['category']) ?>"
                  style="font-size:10.5px;"><?= ucfirst($a['category']) ?></span>
                <div style="font-size:13px;font-weight:600;color:var(--n-900);margin:4px 0 2px;"><?= e($a['title']) ?></div>
                <div style="font-size:11.5px;color:var(--n-400);"><?= e($a['full_name']) ?> ·
                  <?= timeAgo($a['created_at']) ?>
                </div>
              </div>
            <?php endforeach; else: ?>
            <p style="font-size:13px;color:var(--n-400);padding:12px 0;">No announcements.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

</div><!-- /viewProgress -->

<!-- ━━━━━━━━━━━ ANALYTICS VIEW ━━━━━━━━━━━ -->
<div id="viewAnalytics" style="display:none;">

  <!-- Filter bar -->
  <div class="an-filter-bar">
    <label>Primary SY:</label>
    <span style="font-size:13px;font-weight:700;color:var(--n-900);"><?= e($syLabel) ?></span>
    <div style="width:1px;height:18px;background:var(--n-200);margin:0 4px;"></div>
    <label>Compare with:</label>
    <div class="p-select" id="anCompareSelect" style="width:160px;">
      <input type="hidden" name="compare_sy_id" value="<?= $compareSyId ?>">
      <div class="p-select-trigger" onclick="togglePSelect(event, 'anCompareSelect')"
        style="padding:5px 12px;font-size:12.5px;min-height:32px;">
        <span
          class="p-select-val"><?= $compareSyId ? 'SY ' . e(array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') : 'None' ?></span>
      </div>
      <div class="p-select-menu">
        <div class="p-select-item <?= !$compareSyId ? 'selected' : '' ?>"
          onclick="location.href='dashboard.php?compare_sy=0&view=analytics'">None</div>
        <?php foreach ($allSYs as $sy):
          if ($sy['sy_id'] == $syId)
            continue; ?>
          <div class="p-select-item <?= $sy['sy_id'] == $compareSyId ? 'selected' : '' ?>"
            onclick="location.href='dashboard.php?compare_sy=<?= $sy['sy_id'] ?>&view=analytics'">
            SY <?= e($sy['label']) ?><?php if ($sy['sy_id'] == $compareSyId): ?><span
                class="p-select-check"></span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($compareSyId): ?>
      <span
        style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--blue-bg);color:var(--blue);">Comparing
        2 cycles</span>
      <a href="dashboard.php?view=analytics" class="btn btn-ghost btn-sm">✕ Clear</a>
    <?php endif; ?>
    <div style="margin-left:auto;display:flex;gap:8px;">
      <button class="ai-assistant-btn" onclick="openAIAssistant()">
        <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
        </svg>
        AI Suggestions
      </button>
    </div>
  </div>

  <!-- KPI insight strip -->
  <div class="an-insight-strip">
    <div class="an-insight-card">
      <div class="an-insight-val"
        style="color:<?= $anAvgOverall !== null ? ($anAvgOverall >= 76 ? '#16A34A' : ($anAvgOverall >= 51 ? '#2563EB' : ($anAvgOverall >= 26 ? '#D97706' : '#DC2626'))) : 'var(--n-400)' ?>;">
        <?= $anAvgOverall !== null ? $anAvgOverall . '%' : '—' ?>
      </div>
      <div class="an-insight-lbl">Overall SBM Score</div>
      <?php if ($scoreDelta !== null): ?>
        <div class="an-insight-delta <?= $scoreDelta > 0 ? 'up' : ($scoreDelta < 0 ? 'down' : 'flat') ?>">
          <?= $scoreDelta > 0 ? '▲ +' : '▼ ' ?>   <?= abs($scoreDelta) ?>% vs prev cycle
        </div>
      <?php endif; ?>
    </div>
    <div class="an-insight-card">
      <?php
      $curMaturity = $currCycle['maturity_level'] ?? null;
      $anMatColors = ['Beginning' => '#DC2626', 'Developing' => '#D97706', 'Maturing' => '#2563EB', 'Advanced' => '#16A34A'];
      ?>
      <div class="an-insight-val"
        style="font-size:18px;color:<?= $anMatColors[$curMaturity ?? ''] ?? 'var(--n-400)' ?>;">
        <?= $curMaturity ?? '—' ?>
      </div>
      <div class="an-insight-lbl">Maturity Level</div>
      <?php if ($prevCycle && $prevCycle['maturity_level'] && $curMaturity): ?>
        <div class="an-insight-delta flat">Was: <?= e($prevCycle['maturity_level']) ?></div>
      <?php endif; ?>
    </div>
    <div class="an-insight-card">
      <?php if ($anTopDim): ?>
        <div class="an-insight-val" style="font-size:20px;color:<?= e($anTopDim['color_hex']) ?>;">
          D<?= $anTopDim['dimension_no'] ?></div>
        <div class="an-insight-lbl">Strongest — <?= e($anTopDim['dimension_name']) ?></div>
        <div class="an-insight-delta up"><?= $anTopDim['avg_pct'] ?>% average</div>
      <?php else: ?>
        <div class="an-insight-val">—</div>
        <div class="an-insight-lbl">Strongest Dimension</div>
      <?php endif; ?>
    </div>
    <div class="an-insight-card">
      <?php if ($anWeakDim): ?>
        <div class="an-insight-val" style="font-size:20px;color:var(--red);">D<?= $anWeakDim['dimension_no'] ?></div>
        <div class="an-insight-lbl">Needs Work — <?= e($anWeakDim['dimension_name']) ?></div>
        <div class="an-insight-delta down"><?= $anWeakDim['avg_pct'] ?>% average</div>
      <?php else: ?>
        <div class="an-insight-val">—</div>
        <div class="an-insight-lbl">Weakest Dimension</div>
      <?php endif; ?>
    </div>
    <div class="an-insight-card">
      <div class="an-insight-val" style="color:<?= count($consistentlyWeak) > 0 ? 'var(--red)' : 'var(--n-800)' ?>;">
        <?= count($consistentlyWeak) ?>
      </div>
      <div class="an-insight-lbl">Indicators Below 2.5 Avg</div>
      <?php if (count($consistentlyWeak) > 0): ?>
        <div class="an-insight-delta down">Needs targeted intervention</div>
      <?php else: ?>
        <div class="an-insight-delta up">All indicators ≥ 2.5</div>
      <?php endif; ?>
    </div>
    <div class="an-insight-card">
      <div class="an-insight-val"><?= count($cycleHistory) ?></div>
      <div class="an-insight-lbl">Cycles Assessed</div>
      <?php if (count($cycleHistory) > 0): ?>
        <div class="an-insight-delta flat">Since SY <?= e($cycleHistory[0]['sy_label']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Charts row -->
  <div class="grid2" style="margin-bottom:18px;">
    <div class="chart-card">
      <div class="chart-card-head">
        <span class="chart-card-title">Dimension Performance Radar</span>
        <?php if ($compareSyId && !empty($anDimAvgsCompare)): ?>
          <div style="display:flex;align-items:center;gap:10px;font-size:11.5px;">
            <span style="display:flex;align-items:center;gap:4px;"><span
                style="width:10px;height:10px;border-radius:50%;background:#16A34A;display:inline-block;"></span>SY
              <?= e($syLabel) ?></span>
            <span style="display:flex;align-items:center;gap:4px;"><span
                style="width:10px;height:10px;border-radius:50%;background:#2563EB;display:inline-block;"></span>SY
              <?= e(array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="chart-card-body" style="display:flex;justify-content:center;align-items:center;min-height:300px;">
        <canvas id="anRadarChart" style="max-height:280px;"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <div class="chart-card-head">
        <span class="chart-card-title">Overall Score Trend</span>
        <span style="font-size:12px;color:var(--n-400);"><?= count($cycleHistory) ?> cycle(s)</span>
      </div>
      <div class="chart-card-body" style="min-height:300px;display:flex;align-items:center;justify-content:center;">
        <?php if (count($cycleHistory) >= 1): ?>
          <canvas id="anTrendLineChart"></canvas>
        <?php else: ?>
          <p style="text-align:center;color:var(--n-400);font-size:13px;">Not enough cycles for a trend.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Dimension Score Comparison -->
  <div class="chart-card" style="margin-bottom:18px;">
    <div class="chart-card-head">
      <span class="chart-card-title">Dimension Score Comparison</span>
      <div class="chart-legend" style="margin-bottom:0;">
        <?php foreach ($anDimAvgs as $d): ?>
          <div class="chart-legend-item">
            <div class="chart-legend-swatch" style="background:<?= e($d['color_hex']) ?>;"></div>
            D<?= $d['dimension_no'] ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="chart-card-body"><canvas id="anDimBarChart" height="80"></canvas></div>
  </div>

  <!-- Dimension trend over time -->
  <?php if (count($trendSYLabels) >= 2): ?>
    <div class="chart-card" style="margin-bottom:18px;">
      <div class="chart-card-head">
        <span class="chart-card-title">Dimension Trend — All Cycles</span>
        <span style="font-size:12px;color:var(--n-400);">Track how each dimension has moved over time</span>
      </div>
      <div class="chart-card-body"><canvas id="anDimTrendChart" height="90"></canvas></div>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="an-tab-btns">
    <button class="an-tab-btn active" onclick="anSwitchTab(this,'anTabHistory')">Cycle History</button>
    <button class="an-tab-btn" onclick="anSwitchTab(this,'anTabWeak')">Weak This Cycle</button>
    <button class="an-tab-btn" onclick="anSwitchTab(this,'anTabConsistent')">Consistently Weak</button>
    <?php if ($compareSyId && !empty($anDimAvgsCompare)): ?>
      <button class="an-tab-btn" onclick="anSwitchTab(this,'anTabCompare')">Side-by-Side</button>
    <?php endif; ?>
  </div>

  <!-- TAB: Cycle History -->
  <div class="an-tab-panel active" id="anTabHistory">
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head"><span class="card-title">Assessment Cycle History</span></div>
      <?php if ($cycleHistory): ?>
        <div class="tbl-wrap">
          <table class="tbl-enhanced">
            <thead>
              <tr>
                <th>School Year</th>
                <th>Overall Score</th>
                <th>Maturity</th>
                <th>Status</th>
                <th>Validated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($cycleHistory) as $ch): ?>
                <tr>
                  <td style="font-weight:600;">SY <?= e($ch['sy_label']) ?></td>
                  <td>
                    <?php if ($ch['overall_score']): ?>
                      <span
                        style="font-family:var(--font-display);font-size:15px;font-weight:800;color:<?= sbmMaturityLevel(floatval($ch['overall_score']))['color'] ?>;"><?= $ch['overall_score'] ?>%</span>
                    <?php else: ?><span style="color:var(--n-300);">—</span><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($ch['maturity_level']): ?>
                      <span class="pill pill-<?= e($ch['maturity_level']) ?>"><?= e($ch['maturity_level']) ?></span>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td><span
                      class="pill pill-<?= e($ch['status']) ?>"><?= ucfirst(str_replace('_', ' ', $ch['status'])) ?></span>
                  </td>
                  <td style="font-size:12px;color:var(--n-400);">
                    <?= $ch['validated_at'] ? date('M d, Y', strtotime($ch['validated_at'])) : '—' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-title">No cycle history yet.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: Weak This Cycle -->
  <div class="an-tab-panel" id="anTabWeak">
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head">
        <span class="card-title">Weak Indicators This Cycle</span>
        <span style="font-size:12px;color:var(--n-400);">Average rating ≤ 2.5</span>
      </div>
      <?php if ($weakIndicatorRows): ?>
        <div class="card-body" style="padding:0;">
          <?php foreach ($weakIndicatorRows as $ind):
            $avgR = floatval($ind['avg_rating']);
            $pct = ($avgR / 4) * 100;
            $color = $avgR >= 3 ? 'var(--n-800)' : ($avgR >= 2 ? 'var(--amber)' : 'var(--red)');
            ?>
            <div style="padding:12px 20px;border-bottom:1px solid var(--n-100);">
              <div class="flex-cb" style="margin-bottom:4px;">
                <div>
                  <span
                    style="font-size:10.5px;font-weight:700;color:var(--n-400);text-transform:uppercase;letter-spacing:.05em;"><?= e($ind['indicator_code']) ?></span>
                  <span
                    style="font-size:10.5px;color:var(--n-400);margin-left:6px;padding:1px 7px;background:var(--n-100);border-radius:4px;"><?= e($ind['dimension_name']) ?></span>
                </div>
                <span style="font-size:13px;font-weight:700;color:<?= $color ?>;"><?= number_format($avgR, 2) ?>/4.00</span>
              </div>
              <div style="font-size:12.5px;color:var(--n-700);margin-bottom:5px;line-height:1.45;">
                <?= e(substr($ind['indicator_text'], 0, 100)) ?>…
              </div>
              <div class="an-weak-prog">
                <div class="an-weak-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
              </div>
              <div style="font-size:11px;color:var(--n-400);margin-top:4px;"><?= $ind['response_count'] ?> response(s)</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-title">No weak indicators found for this cycle.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: Consistently Weak -->
  <div class="an-tab-panel" id="anTabConsistent">
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head">
        <span class="card-title">Consistently Weak Indicators</span>
        <span style="font-size:12px;color:var(--n-400);">Average ≤ 2.5 across all assessed cycles</span>
      </div>
      <?php if ($consistentlyWeak): ?>
        <div class="card-body" style="padding:0;">
          <?php foreach ($consistentlyWeak as $cw):
            $avgR = floatval($cw['avg_rating']);
            $color = $avgR >= 2 ? 'var(--amber)' : 'var(--red)';
            $pct = ($avgR / 4) * 100;
            ?>
            <div class="an-cw-row">
              <div class="an-cw-badge"
                style="background:<?= $avgR < 2 ? 'var(--red-bg)' : 'var(--amber-bg)' ?>;color:<?= $color ?>;">
                <?= e($cw['indicator_code']) ?>
              </div>
              <div class="an-cw-info">
                <div class="an-cw-title"><?= e(substr($cw['indicator_text'], 0, 95)) ?>...</div>
                <div class="an-cw-meta">
                  <?= e($cw['dimension_name']) ?> ·
                  Avg: <strong style="color:<?= $color ?>;"><?= number_format($avgR, 2) ?>/4.00</strong> ·
                  Worst: <?= number_format($cw['worst_cycle_avg'], 2) ?> ·
                  Best: <?= number_format($cw['best_cycle_avg'], 2) ?>
                </div>
                <div class="an-cw-bar-track">
                  <div class="an-cw-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                </div>
              </div>
              <div style="font-size:11px;font-weight:700;color:var(--red);text-align:center;min-width:48px;">
                Priority<br>Action</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-title">No consistently weak indicators</div>
          <div class="empty-sub">All indicators are averaging above 2.5 across cycles.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: Side-by-Side Comparison -->
  <?php if ($compareSyId && !empty($anDimAvgsCompare)): ?>
    <div class="an-tab-panel" id="anTabCompare">
      <div class="card" style="margin-bottom:18px;">
        <div class="card-head">
          <span class="card-title">Side-by-Side Dimension Comparison</span>
          <span style="font-size:12px;color:var(--n-400);">SY <?= e($syLabel) ?> vs SY
            <?= e(array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') ?></span>
        </div>
        <div class="tbl-wrap">
          <table class="tbl-enhanced">
            <thead>
              <tr>
                <th>Dimension</th>
                <th>SY <?= e($syLabel) ?></th>
                <th>SY <?= e(array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') ?></th>
                <th>Change</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $cmpByDim = array_column($anDimAvgsCompare, null, 'dimension_no');
              foreach ($anDimAvgs as $d):
                $curr = floatval($d['avg_pct'] ?? 0);
                $prev = floatval($cmpByDim[$d['dimension_no']]['avg_pct'] ?? 0);
                $chg = round($curr - $prev, 1);
                $chgC = $chg > 0 ? '#16A34A' : ($chg < 0 ? '#DC2626' : '#9CA3AF');
                ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:7px;">
                      <span
                        style="width:10px;height:10px;border-radius:2px;background:<?= e($d['color_hex']) ?>;flex-shrink:0;display:inline-block;"></span>
                      <span style="font-size:12.5px;font-weight:600;">D<?= $d['dimension_no'] ?>:
                        <?= e(substr($d['dimension_name'], 0, 30)) ?></span>
                    </div>
                  </td>
                  <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                      <div style="width:60px;height:5px;background:var(--n-100);border-radius:999px;overflow:hidden;">
                        <div
                          style="width:<?= $curr ?>%;height:100%;background:<?= e($d['color_hex']) ?>;border-radius:999px;">
                        </div>
                      </div>
                      <strong style="font-size:13px;color:<?= e($d['color_hex']) ?>;"><?= $curr ?>%</strong>
                    </div>
                  </td>
                  <td>
                    <?php if ($prev > 0): ?>
                      <div style="display:flex;align-items:center;gap:6px;">
                        <div style="width:60px;height:5px;background:var(--n-100);border-radius:999px;overflow:hidden;">
                          <div style="width:<?= $prev ?>%;height:100%;background:#9CA3AF;border-radius:999px;"></div>
                        </div>
                        <span style="font-size:13px;color:var(--n-500);"><?= $prev ?>%</span>
                      </div>
                    <?php else: ?><span style="font-size:12px;color:var(--n-400);">No data</span><?php endif; ?>
                  </td>
                  <td>
                    <span style="font-size:13px;font-weight:700;color:<?= $chgC ?>;">
                      <?= $chg > 0 ? '▲ +' : ($chg < 0 ? '▼ ' : '') ?>     <?= abs($chg) ?>%
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div><!-- /viewAnalytics -->

<?php if ($dimScores): ?>
  <script>
    const dimLabels = <?= json_encode($chartLabels) ?>;
    const dimValues = <?= json_encode($chartData) ?>;
    const dimColors = <?= json_encode($chartColors) ?>;

    new Chart(document.getElementById('dimBarChart'), {
      type: 'bar',
      data: {
        labels: dimLabels,
        datasets: [{
          label: 'Score (%)',
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
  </script>
<?php endif; ?>

<script>
  // -- View switcher
  function switchView(view, btn) {
    document.querySelectorAll('.vt-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('viewProgress').style.display = view === 'progress' ? '' : 'none';
    document.getElementById('viewAnalytics').style.display = view === 'analytics' ? '' : 'none';
    const url = new URL(window.location.href);
    if (view === 'analytics') {
      url.searchParams.set('view', 'analytics');
    } else {
      url.searchParams.delete('view');
    }
    window.history.replaceState({}, '', url.toString());
    if (view === 'analytics' && !window._anChartsInit) {
      window._anChartsInit = true;
      initAnalyticsCharts();
    }
  }

  // -- Analytics tab switching
  function anSwitchTab(btn, panelId) {
    document.querySelectorAll('.an-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.an-tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId)?.classList.add('active');
  }

  // -- Analytics chart data
  const anDimLabels = <?= json_encode(array_map(fn($d) => 'D' . $d['dimension_no'], $anDimAvgs)) ?>;
  const anDimColors = <?= json_encode(array_column($anDimAvgs, 'color_hex')) ?>;
  const anDimValues = <?= json_encode(array_map(fn($d) => $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $anDimAvgs)) ?>;
  const anDimValCmp = <?= json_encode(!empty($anDimAvgsCompare) ? array_map(fn($d) => $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $anDimAvgsCompare) : []) ?>;
  const anRadarNames = <?= json_encode(array_map(fn($d) => 'D' . $d['dimension_no'] . ': ' . $d['dimension_name'], $anDimAvgs)) ?>;
  const anCycleLabels = <?= json_encode(array_column($cycleHistory, 'sy_label')) ?>;
  const anCycleScores = <?= json_encode(array_map(fn($c) => floatval($c['overall_score']), $cycleHistory)) ?>;
  const anTrendSYLabels = <?= json_encode($trendSYLabels) ?>;
  const anTrendByDim = <?= json_encode($trendByDim) ?>;
  const anDimMeta = <?= json_encode(array_map(fn($d) => ['no' => $d['dimension_no'], 'name' => $d['dimension_name'], 'color' => $d['color_hex']], $anDimAvgs)) ?>;
  const anCompareSyLabel = <?= json_encode(!empty($anDimAvgsCompare) ? (array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') : '') ?>;
  const anCurrSyLabel = <?= json_encode($syLabel) ?>;

  function initAnalyticsCharts() {
    // Radar chart
    if (anDimValues.some(v => v > 0)) {
      const radarDatasets = [{
        label: 'SY ' + anCurrSyLabel,
        data: anDimValues,
        backgroundColor: 'rgba(22,163,74,.13)',
        borderColor: '#16A34A',
        pointBackgroundColor: anDimColors,
        pointRadius: 5,
        borderWidth: 2,
      }];
      if (anDimValCmp.length && anDimValCmp.some(v => v > 0)) {
        radarDatasets.push({
          label: 'SY ' + anCompareSyLabel,
          data: anDimValCmp,
          backgroundColor: 'rgba(37,99,235,.10)',
          borderColor: '#2563EB',
          pointBackgroundColor: '#2563EB',
          pointRadius: 4,
          borderWidth: 2,
          borderDash: [4, 4],
        });
      }
      new Chart(document.getElementById('anRadarChart'), {
        type: 'radar',
        data: { labels: anDimLabels, datasets: radarDatasets },
        options: {
          scales: { r: { min: 0, max: 100, ticks: { font: { size: 10 }, stepSize: 25, backdropColor: 'transparent' }, pointLabels: { font: { size: 13, weight: '700' }, color: '#374151' } } },
          plugins: { legend: { display: radarDatasets.length > 1, position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }, tooltip: { callbacks: { title: ctx => anRadarNames[ctx[0].dataIndex], label: ctx => ' ' + ctx.raw + '%' } } },
          maintainAspectRatio: true, aspectRatio: 1,
        }
      });
    } else {
      const rc = document.getElementById('anRadarChart');
      if (rc) rc.closest('.chart-card-body').innerHTML = '<p style="text-align:center;color:var(--n-400);padding:48px 0;font-size:13px;">No dimension data for this school year.</p>';
    }

    // Overall score trend line
    const trendEl = document.getElementById('anTrendLineChart');
    if (anCycleScores.length >= 1 && trendEl) {
      new Chart(trendEl, {
        type: 'line',
        data: {
          labels: anCycleLabels,
          datasets: [{
            label: 'Overall Score (%)',
            data: anCycleScores,
            borderColor: '#16A34A',
            backgroundColor: 'rgba(22,163,74,.08)',
            pointBackgroundColor: anCycleScores.map(s => s >= 76 ? '#16A34A' : (s >= 51 ? '#2563EB' : (s >= 26 ? '#D97706' : '#DC2626'))),
            pointRadius: 6, pointHoverRadius: 8,
            borderWidth: 2.5, tension: 0.3, fill: true,
          }]
        },
        options: {
          scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
            x: { ticks: { font: { size: 11, weight: '600' } }, grid: { display: false } }
          },
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' Score: ' + ctx.raw + '%' } } },
          responsive: true, maintainAspectRatio: true, aspectRatio: 1.5,
        }
      });
    }

    // Dimension trend lines
    const dimTrendEl = document.getElementById('anDimTrendChart');
    if (dimTrendEl && anTrendSYLabels.length >= 2) {
      const dimTrendDatasets = anDimMeta.map(dm => {
        const data = anTrendSYLabels.map(lbl => anTrendByDim[dm.no]?.[lbl] ?? null);
        return {
          label: 'D' + dm.no + ': ' + dm.name,
          data, borderColor: dm.color, backgroundColor: dm.color + '22',
          pointBackgroundColor: dm.color, pointRadius: 5, borderWidth: 2, tension: 0.3,
        };
      });
      new Chart(dimTrendEl, {
        type: 'line',
        data: { labels: anTrendSYLabels, datasets: dimTrendDatasets },
        options: {
          scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
            x: { ticks: { font: { size: 11, weight: '600' } }, grid: { display: false } }
          },
          plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10, usePointStyle: true, pointStyleWidth: 8 } } },
          responsive: true, maintainAspectRatio: true,
        }
      });
    }

    // Dimension bar chart
    if (anDimValues.some(v => v !== null && v > 0)) {
      const barDatasets = [{
        label: 'SY ' + anCurrSyLabel,
        data: anDimValues,
        backgroundColor: anDimColors.map(c => c + '30'),
        borderColor: anDimColors,
        borderWidth: 2, borderRadius: 8, borderSkipped: false,
      }];
      if (anDimValCmp.length && anDimValCmp.some(v => v > 0)) {
        barDatasets.push({
          label: 'SY ' + anCompareSyLabel,
          data: anDimValCmp,
          backgroundColor: '#9CA3AF30', borderColor: '#9CA3AF',
          borderWidth: 2, borderRadius: 8, borderSkipped: false,
        });
      }
      new Chart(document.getElementById('anDimBarChart'), {
        type: 'bar',
        data: { labels: anDimLabels, datasets: barDatasets },
        options: {
          scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
            x: { ticks: { font: { size: 12, weight: '600' } }, grid: { display: false } }
          },
          plugins: { legend: { display: barDatasets.length > 1, position: 'bottom', labels: { font: { size: 11 }, padding: 10 } } },
          responsive: true, maintainAspectRatio: true,
        }
      });
    } else {
      const bc = document.getElementById('anDimBarChart');
      if (bc) bc.closest('.chart-card-body').innerHTML = '<p style="text-align:center;color:var(--n-400);padding:48px 0;font-size:13px;">No dimension score data for this school year.</p>';
    }
  }

  // Auto-switch to analytics if ?view=analytics in URL
  (function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('view') === 'analytics') {
      const btn = document.querySelectorAll('.vt-btn')[1];
      if (btn) switchView('analytics', btn);
    }
  })();

  async function doFinalizeCycle(cycleId) {
    if (!confirm('Finalize and permanently lock this cycle? No further edits will be possible.')) return;
    const r = await apiPost('../school_head/workflow.php', {
      action: 'finalize_cycle',
      cycle_id: cycleId
    });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }
</script>

<?= deadlineChipCss() ?>
<?= deadlineChipJs() ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>