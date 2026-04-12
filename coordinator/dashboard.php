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

$pageTitle = 'Coordinator Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ── HERO (mirrors school_head hero) ── */
  .coord-hero {
    background:
      radial-gradient(ellipse 55% 90% at 92% 50%, rgba(34, 197, 94, 0.13) 0%, transparent 65%),
      radial-gradient(ellipse 35% 55% at 8% 15%, rgba(22, 101, 52, 0.40) 0%, transparent 60%),
      linear-gradient(135deg, #081a08 0%, #0d260d 35%, #14532d 65%, #166534 100%);
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

  .coord-hero::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(74, 222, 128, 0.10) 0%, rgba(34, 197, 94, 0.05) 50%, transparent 75%);
    pointer-events: none;
    animation: heroOrbPulse 4s ease-in-out infinite;
  }

  @keyframes heroOrbPulse {

    0%,
    100% {
      opacity: .7;
      transform: scale(1);
    }

    50% {
      opacity: 1;
      transform: scale(1.07);
    }
  }

  .coord-hero::after {
    content: '';
    position: absolute;
    left: -40px;
    bottom: -60px;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(22, 163, 74, 0.08) 0%, transparent 70%);
    pointer-events: none;
  }

  .coord-hero-shimmer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(105deg, transparent 20%, rgba(255, 255, 255, 0.025) 50%, transparent 80%);
    z-index: 0;
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

  /* ── BREAKPOINTS ── */

  /* Tablet / medium zoom */
  @media (max-width: 1100px) {
    .main-grid {
      grid-template-columns: 1fr 300px;
    }

    .stats-v2 {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  /* Narrow / heavy zoom */
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
  }

  /* Mobile */
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
  <div class="coord-hero-shimmer"></div>
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

            // BUG-10: Compute actual active indicator count per dimension from DB
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
            <div style="font-size:14px;font-weight:700;color:var(--n-600);margin-bottom:4px;">No dimension data yet</div>
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
          <div style="font-size:12.5px;color:var(--brand-700);">This cycle has been validated by the Administrator.</div>
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
          <a href="improvement.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--blue-bg);color:var(--blue);">
              <svg viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                <polyline points="17 6 23 6 23 12" />
              </svg>
            </div>
            Improvement Plan
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
              <div style="font-size:11.5px;color:var(--n-400);"><?= e($a['full_name']) ?> · <?= timeAgo($a['created_at']) ?>
              </div>
            </div>
          <?php endforeach; else: ?>
          <p style="font-size:13px;color:var(--n-400);padding:12px 0;">No announcements.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

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