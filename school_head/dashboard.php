<?php
ob_start();
// ============================================================
// school_head/dashboard.php — SY-FILTERED DASHBOARD
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head');
$db = getDB();

// -- All school years for the selector ------------------------
$allSYs = $db->query("SELECT * FROM school_years ORDER BY label DESC")->fetchAll();
$currentSYRow = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();
$myId = (int) ($_SESSION['user_id'] ?? 0);

// ── NEW: SAVE IMPROVEMENT PLAN AJAX HANDLER ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_improvement_plan') {
  header('Content-Type: application/json');
  $schoolId = (int) ($_SESSION['school_id'] ?? 1);
  $syId = (int) ($_POST['sy_id'] ?? 0);
  $dimIds = explode(',', $_POST['dimension_ids'] ?? '');
  $indIds = explode(',', $_POST['indicator_ids'] ?? '');
  $obj = $_POST['objective'] ?? '';
  $strat = $_POST['strategy'] ?? '';
  $person = $_POST['person_responsible'] ?? '';
  $target = $_POST['target_date'] ?? null;
  $res = $_POST['resources_needed'] ?? '';
  $output = $_POST['expected_output'] ?? '';
  $priority = $_POST['priority_level'] ?? 'Medium';

  // Get current cycle for this SY
  $cQ = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id = ? AND sy_id = ? ORDER BY created_at DESC LIMIT 1");
  $cQ->execute([$schoolId, $syId]);
  $cycleId = $cQ->fetchColumn();

  if (!$cycleId) {
    echo json_encode(['success' => false, 'message' => 'No assessment cycle found for this year. Please create one first.']);
    exit;
  }

  try {
    $ins = $db->prepare("INSERT INTO improvement_plans (school_id, cycle_id, dimension_id, indicator_id, priority_level, objective, strategy, person_responsible, target_date, resources_needed, expected_output, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($indIds as $indId) {
      if (!$indId) continue;
      // Get the dimension for this indicator
      $dimQ = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id = ?");
      $dimQ->execute([$indId]);
      $dId = $dimQ->fetchColumn();

      $ins->execute([$schoolId, $cycleId, $dId, $indId, $priority, $obj, $strat, $person, $target ?: null, $res, $output, $myId]);
    }
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ── NEW: AI ASSISTANT AJAX HANDLER (GROQ) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_ai_suggestions') {
  header('Content-Type: application/json');
  require_once __DIR__ . '/../includes/ml_service.php';

  $schoolId = (int) ($_SESSION['school_id'] ?? 0);
  $syId = (int) ($_POST['sy_id'] ?? 0);

  // 1. Get School Info
  $sQ = $db->prepare("SELECT school_name FROM schools WHERE school_id = ?");
  $sQ->execute([$schoolId]);
  $schoolName = $sQ->fetchColumn() ?: 'School';

  $syQ = $db->prepare("SELECT label FROM school_years WHERE sy_id = ?");
  $syQ->execute([$syId]);
  $syLabel = $syQ->fetchColumn() ?: 'Unknown';

  // 2. Gather Dim Scores
  $dimQ = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, ROUND(AVG(ds.percentage), 1) as avg_pct
        FROM sbm_dimensions d
        LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
        LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
        WHERE c.sy_id = ? AND c.school_id = ?
        GROUP BY d.dimension_id ORDER BY d.dimension_no
    ");
  $dimQ->execute([$syId, $schoolId]);
  $dimScores = [];
  foreach ($dimQ->fetchAll() as $row) {
    $dimScores[] = [
      'dimension_name' => $row['dimension_name'],
      'score' => (float) $row['avg_pct'],
      'maturity' => $row['avg_pct'] >= 66.6 ? 'Advanced' : ($row['avg_pct'] >= 33.3 ? 'Maturing' : 'Developing')
    ];
  }

  // 3. Gather Weak Indicators (Rating < 2.5)
  $weakQ = $db->prepare("
        SELECT i.indicator_code, i.indicator_text, ROUND(AVG(all_r.rating), 2) as rating
        FROM (
            SELECT cycle_id, indicator_id, rating FROM sbm_responses
            UNION ALL
            SELECT cycle_id, indicator_id, rating FROM teacher_responses
        ) AS all_r
        JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
        JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
        WHERE c.sy_id = ? AND c.school_id = ?
        GROUP BY i.indicator_id
        HAVING rating < 2.5
        ORDER BY rating ASC
    ");
  $weakQ->execute([$syId, $schoolId]);
  $byRating = ['1' => [], '2' => []];
  foreach ($weakQ->fetchAll() as $row) {
    $r = (int) floor($row['rating']);
    if ($r < 1)
      $r = 1;
    if ($r > 2)
      $r = 2;
    $byRating[strval($r)][] = [
      'code' => $row['indicator_code'],
      'text' => $row['indicator_text'],
      'rating' => (float) $row['rating']
    ];
  }

  // 4. History Trend
  $histQ = $db->prepare("
        SELECT overall_score FROM sbm_cycles 
        WHERE school_id = ? AND status='validated' AND sy_id != ? 
        ORDER BY created_at DESC LIMIT 3
    ");
  $histQ->execute([$schoolId, $syId]);
  $history = $histQ->fetchAll();

  // 5. Get Real Overall Score & Maturity
  $scoreQ = $db->prepare("
        SELECT overall_score, maturity_level FROM sbm_cycles 
        WHERE school_id = ? AND sy_id = ? AND status='validated'
        ORDER BY created_at DESC LIMIT 1
    ");
  $scoreQ->execute([$schoolId, $syId]);
  $scoreData = $scoreQ->fetch();
  $overallScore = $scoreData ? (float) $scoreData['overall_score'] : 0;
  $overallMaturity = $scoreData ? $scoreData['maturity_level'] : 'N/A';

  // 6. Call ML Service (Groq)
  $payload = [
    'school_name' => $schoolName,
    'sy_label' => $syLabel,
    'analysis' => [
      'gap_analysis' => [
        'average_score' => $overallScore,
        'overall_maturity' => $overallMaturity,
        'weakest_dimensions' => array_slice($dimScores, 0, 3)
      ],
      'by_rating' => $byRating,
      'history' => $history,
      'comment_summary' => ['top_topics' => [], 'has_urgent' => false]
    ]
  ];

  $response = ml_post('/api/recommend', $payload);
  echo json_encode($response ?: [
    'recommendations' => "I'm sorry, I'm having trouble connecting to my central intelligence. Please check if the ML service is running.",
    'error' => 'Service Unavailable'
  ]);
  exit;
}

// -- Resolve selected SY from ?sy_id= (fall back to current) --
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

// -- SY-scoped stats ------------------------------------------â”€
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

// Assessment cycles stats are SY-scoped

// -- Maturity distribution (SY-scoped) ------------------------
$stMaturity = $db->prepare("
  SELECT maturity_level, COUNT(*) cnt FROM sbm_cycles
  WHERE sy_id = ? AND maturity_level IS NOT NULL
  GROUP BY maturity_level
  ORDER BY FIELD(maturity_level,'Advanced','Maturing','Developing','Beginning')
");
$stMaturity->execute([$selectedSyId]);
$maturity = $stMaturity->fetchAll();

// -- Recent cycles (SY-scoped) --------------------------------â”€
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

// -- Dimension scores (SY-scoped — subquery ensures only scores from selected SY cycles)
$stDimScores = $db->prepare("
  SELECT d.dimension_id, d.dimension_no, d.dimension_name, d.color_hex,
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

// -- Recent activity (global — not SY-scoped) ------------------
$recentActivity = $db->query("
  SELECT l.*, u.full_name FROM activity_log l
  LEFT JOIN users u ON l.user_id=u.user_id
  ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

$validationRate = $submitted > 0 ? round(($validated / $submitted) * 100) : 0;
$hasData = ($totalCycles > 0);

// -- Deadline awareness ----------------------------------------
$deadlineInfo = $selectedSyId ? getDeadlineInfo($db, $selectedSyId) : null;

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// ANALYTICS DATA (loaded upfront for inline toggle)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// -- Comparison SY --------------------------------------------â”€
$compareSyId = (int) ($_GET['compare_sy'] ?? 0);

// -- Analytics dimension averages ------------------------------
$anDimAvgQ = $db->prepare("
    SELECT d.dimension_id, d.dimension_no, d.dimension_name, d.color_hex,
           ROUND(AVG(ds.percentage),1) AS avg_pct
    FROM sbm_dimensions d
    LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
    LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY d.dimension_id ORDER BY d.dimension_no
");
$anDimAvgQ->execute([$selectedSyId, $mySchoolId]);
$anDimAvgs = $anDimAvgQ->fetchAll();

// -- Identify the lowest dimension(s) --
$minDimVal = 1000;
foreach($anDimAvgs as $da) {
    if($da['avg_pct'] !== null && $da['avg_pct'] < $minDimVal) $minDimVal = $da['avg_pct'];
}
$lowestDimIds = [];
if($minDimVal < 1000) {
    foreach($anDimAvgs as $da) {
        if($da['avg_pct'] == $minDimVal) $lowestDimIds[] = (int)$da['dimension_id'];
    }
}

// -- Comparison SY dimension averages --------------------------
$anDimAvgsCompare = [];
if ($compareSyId && $compareSyId !== $selectedSyId) {
  $cmpQ = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, d.color_hex,
               ROUND(AVG(ds.percentage),1) AS avg_pct
        FROM sbm_dimensions d
        LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
        LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
        WHERE c.sy_id = ? AND c.school_id = ?
        GROUP BY d.dimension_id ORDER BY d.dimension_no
    ");
  $cmpQ->execute([$compareSyId, $mySchoolId]);
  $anDimAvgsCompare = $cmpQ->fetchAll();
}

// -- Assessment history (all cycles) --------------------------â”€
$historyQ = $db->prepare("
    SELECT sy.label AS sy_label, sy.sy_id,
           c.cycle_id, c.overall_score, c.maturity_level,
           c.status, c.validated_at
    FROM sbm_cycles c
    JOIN school_years sy ON c.sy_id = sy.sy_id
    WHERE c.school_id = ? AND c.overall_score IS NOT NULL
    ORDER BY sy.date_start ASC
");
$historyQ->execute([$mySchoolId]);
$cycleHistory = $historyQ->fetchAll();

// -- Trend data: dimension scores across all cycles ------------
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
$trendQ->execute([$mySchoolId]);
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

// -- Weak indicators — current SY ------------------------------
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
    JOIN sbm_indicators i   ON all_r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d   ON i.dimension_id = d.dimension_id
    JOIN sbm_cycles c       ON all_r.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY i.indicator_id
    HAVING avg_rating <= 2.5
    ORDER BY avg_rating ASC
");
$weakQ->execute([$selectedSyId, $mySchoolId]);
$weakIndicatorRows = $weakQ->fetchAll();

// -- Consistently weak indicators ------------------------------
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
    ORDER BY avg_rating ASC
    LIMIT 6
");
$consistentlyWeakQ->execute([$mySchoolId, $mySchoolId]);
$consistentlyWeak = $consistentlyWeakQ->fetchAll();

// -- Summary insights ------------------------------------------
$anAllPcts = array_filter(array_column($anDimAvgs, 'avg_pct'), fn($v) => $v !== null);
$anAvgOverall = count($anAllPcts) > 0 ? round(array_sum($anAllPcts) / count($anAllPcts), 1) : null;
$anTopDim = !empty($anAllPcts) ? $anDimAvgs[array_search(max($anAllPcts), array_column($anDimAvgs, 'avg_pct'))] : null;
$anWeakDim = !empty($anAllPcts) ? $anDimAvgs[array_search(min($anAllPcts), array_column($anDimAvgs, 'avg_pct'))] : null;

$prevCycle = count($cycleHistory) >= 2 ? $cycleHistory[count($cycleHistory) - 2] : null;
$currCycle = count($cycleHistory) >= 1 ? $cycleHistory[count($cycleHistory) - 1] : null;
$scoreDelta = ($currCycle && $prevCycle)
  ? round(floatval($currCycle['overall_score']) - floatval($prevCycle['overall_score']), 2)
  : null;

$pageTitle = 'Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* -- HERO -- */
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

  /* -- KPI STATS -- */
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
    color: var(--n-900);
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

  /* -- PIPELINE -- */
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

  /* -- ANALYTICS INSIGHTS -- */
  .an-insight-delta {
    font-size: 12px;
    font-weight: 700;
    margin-top: 8px;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
  }

  .an-insight-delta.up {
    color: var(--brand-700, #15803d);
    background: rgba(22, 163, 74, 0.1);
  }

  .an-insight-delta.down {
    color: var(--red);
    background: rgba(220, 38, 38, 0.1);
  }

  .an-insight-delta.flat {
    color: var(--n-600);
    background: var(--n-100);
  }

  /* -- DIM LIST -- */
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

  /* -- ACTIVITY -- */
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

  /* -- QUICK ACTIONS -- */
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

  /* -- MATURITY LEGEND -- */
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

  /* -- SCORE INLINE -- */
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

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   RESPONSIVE GRID CLASSES
   All grids use CSS classes — NO inline grid styles
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

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

  /* -- BREAKPOINTS -- */

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

  /* -- SY SELECTOR -- */
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

  /* -- CONTEXT BAR -- */
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

  /* -- SY SIDEBAR ROWS -- */
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

  /* -- CUSTOM SY DROPDOWN -- */
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

  /* -- VIEW TOGGLE -- */
  .view-toggle-wrap {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 24px;
  }

  .view-toggle {
    display: inline-flex;
    background: var(--n-100);
    border: 1px solid var(--n-200);
    border-radius: 12px;
    padding: 4px;
    gap: 4px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.06);
  }

  .vt-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--n-500);
    text-decoration: none;
    transition: all 140ms;
    border: none;
    background: transparent;
    cursor: pointer;
  }

  .vt-btn:hover {
    color: var(--n-700);
    background: rgba(0, 0, 0, 0.03);
  }

  .vt-btn.active {
    background: #fff;
    color: var(--n-900);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 1px 4px rgba(0, 0, 0, 0.05);
  }

  /* -- CHART LEGENDS -- */
  .chart-legend {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
  }

  .chart-legend-item {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--n-600);
    line-height: 1;
  }

  .chart-legend-swatch {
    width: 11px;
    height: 11px;
    border-radius: 3px;
    flex-shrink: 0;
  }

  /* -- ANALYTICS VIEW STYLES -- */
  .an-insight-strip {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
  }

  .an-insight-card {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    padding: 14px 16px;
    box-shadow: var(--shadow-xs);
  }

  .an-insight-val {
    font-size: 24px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 2px;
  }

  /* -- AI ASSISTANT PANEL -- */
  .ai-assistant-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #1e293b;
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .ai-assistant-btn:hover {
    background: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .ai-panel {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 420px;
    max-width: calc(100vw - 40px);
    height: 540px;
    max-height: calc(100vh - 100px);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
    transform: translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  .ai-panel.open {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
  }

  .ai-panel.minimized {
    height: 56px !important;
    width: 56px !important;
    border-radius: 28px !important;
    cursor: pointer;
  }

  .ai-panel.minimized .ai-panel-header,
  .ai-panel.minimized .ai-chat-body {
    display: none !important;
  }

  .ai-panel-fab {
    display: none;
    width: 100%;
    height: 100%;
    background: transparent;
    color: var(--n-800);
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 20px;
    transition: background 0.2s;
  }

  .ai-panel-fab:hover {
    background: #0f172a;
  }

  .ai-panel.minimized .ai-panel-fab {
    display: flex;
  }

  #aiAssistant .ai-panel-header {
    padding: 16px 20px !important;
    background: #C0C0C0 !important;
    color: var(--n-900) !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    flex-shrink: 0 !important;
    cursor: pointer !important;
    border-bottom: 1px solid var(--n-200) !important;
  }

  .ai-panel-actions {
    display: flex;
    gap: 8px;
  }

  .ai-panel-title {
    font-size: 15px;
    font-weight: 700;
  }

  #aiAssistant .ai-panel-btn {
    background: var(--n-200) !important;
    border: none !important;
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    color: var(--n-700) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: background .2s !important;
    font-size: 16px !important;
    outline: none !important;
  }

  #aiAssistant .ai-panel-btn:hover {
    background: var(--n-300) !important;
  }

  .ai-chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 22px 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #fff;
    scrollbar-width: thin;
    scrollbar-color: #e5e7eb transparent;
  }

  .ai-chat-body::-webkit-scrollbar {
    width: 4px;
  }

  .ai-chat-body::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 4px;
  }

  /* --- AI Message: clean prose block (not a chat bubble) --- */
  .chat-msg {
    width: 100%;
    max-width: 100%;
    padding: 0;
    font-size: 13.5px;
    line-height: 1.65;
    color: var(--n-800);
    font-family: var(--font-body);
  }

  .chat-msg.ai {
    background: #fff;
    align-self: flex-start;
    border: none;
    box-shadow: none;
  }

  .chat-msg.user {
    background: var(--n-100);
    color: var(--n-800);
    align-self: flex-end;
    padding: 10px 14px;
    border-radius: 12px;
    max-width: 85%;
  }

  /* Prose typography inside AI messages */
  .chat-msg.ai p {
    margin: 0 0 12px 0;
  }

  .chat-msg.ai p:last-child {
    margin-bottom: 0;
  }

  .chat-msg.ai strong {
    color: var(--n-900);
    font-weight: 700;
  }

  .chat-msg.ai ul {
    margin: 6px 0 14px 0;
    padding-left: 18px;
    list-style: disc;
  }

  .chat-msg.ai ul li {
    margin-bottom: 5px;
    padding-left: 2px;
    color: var(--n-700);
  }

  .chat-msg.ai ul li::marker {
    color: var(--n-400);
  }

  .chat-msg.ai hr {
    border: none;
    border-top: 1px solid var(--n-150, #eaecf0);
    margin: 14px 0;
  }

  /* Status message (initial greeting) */
  .chat-msg.status {
    background: var(--n-50);
    border: 1px solid var(--n-200);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 12.5px;
    color: var(--n-500);
    text-align: center;
    width: 100%;
  }

  /* Typing Animation */
  .typing {
    display: flex;
    gap: 5px;
    padding: 10px 14px;
    align-self: flex-start;
  }

  .dot {
    width: 6px;
    height: 6px;
    background: #cbd5e1;
    border-radius: 50%;
    animation: blink 1.4s infinite both;
  }

  .dot:nth-child(2) {
    animation-delay: 0.2s;
  }

  .dot:nth-child(3) {
    animation-delay: 0.4s;
  }

  @keyframes blink {

    0%,
    80%,
    100% {
      opacity: 0;
    }

    40% {
      opacity: 1;
    }
  }

  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 800;
  color: var(--n-900);
  line-height: 1;
  margin-bottom: 4px;
  }

  .an-insight-lbl {
    font-size: 11.5px;
    color: var(--n-500);
    font-weight: 500;
  }

  .an-insight-delta {
    font-size: 12px;
    font-weight: 700;
    margin-top: 5px;
  }

  .an-insight-delta.up {
    color: var(--n-800);
  }

  .an-insight-delta.down {
    color: var(--n-900);
    font-weight: 800;
  }

  .an-insight-delta.flat {
    color: var(--n-400);
  }

  .an-filter-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    margin-bottom: 16px;
    flex-wrap: wrap;
    box-shadow: var(--shadow-xs);
  }

  .an-filter-bar label {
    font-size: 12px;
    font-weight: 600;
    color: var(--n-600);
    white-space: nowrap;
  }

  .an-weak-prog {
    height: 6px;
    background: var(--n-100);
    border-radius: 999px;
    overflow: hidden;
    margin-top: 5px;
  }

  .an-weak-fill {
    height: 100%;
    border-radius: 999px;
  }

  .an-tab-btns {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
  }

  .an-tab-btn {
    padding: 6px 14px;
    border-radius: 7px;
    border: 1.5px solid var(--n-200);
    background: #fff;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-600);
    cursor: pointer;
    transition: all .14s;
  }

  .an-tab-btn:hover {
    background: var(--n-50);
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

  .an-cw-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid var(--n-100);
  }

  .an-cw-badge {
    min-width: 38px;
    height: 22px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .04em;
    flex-shrink: 0;
  }

  .an-cw-info {
    flex: 1;
    min-width: 0;
  }

  .an-cw-title {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-900);
    margin-bottom: 3px;
    line-height: 1.35;
  }

  .an-cw-meta {
    font-size: 11.5px;
    color: var(--n-400);
  }

  .an-cw-bar-track {
    height: 5px;
    background: var(--n-100);
    border-radius: 999px;
    margin-top: 5px;
    overflow: hidden;
  }

  .an-cw-bar-fill {
    height: 100%;
    border-radius: 999px;
  }

  @media(max-width:768px) {
    .an-insight-strip {
      grid-template-columns: 1fr 1fr;
    }
  }

  .ai-assistant-btn {
    background: #fff !important;
    color: var(--n-700) !important;
    border: 1px solid var(--n-300) !important;
    box-shadow: var(--shadow-xs) !important;
  }

  .ai-assistant-btn:hover {
    background: var(--n-50) !important;
    border-color: var(--n-400) !important;
  }

  .ai-panel-header {
    background: var(--n-50) !important;
    color: var(--n-900) !important;
    border-bottom: 1px solid var(--n-200) !important;
  }

  .ai-panel-close {
    background: transparent !important;
    color: var(--n-500) !important;
  }

  .ai-panel-close:hover {
    background: var(--n-200) !important;
    color: var(--n-900) !important;
  }

  .chat-msg.user {
    background: var(--n-800) !important;
  }

  .ai-suggestion-head {
    color: var(--n-600) !important;
    background: var(--n-50) !important;
  }

  .ai-priority-high {
    background: var(--n-100) !important;
    color: var(--n-900) !important;
  }

  /* -- MODAL STYLES -- */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    backdrop-filter: blur(2px);
  }

  .modal-content {
    background: #fff;
    width: 600px;
    max-width: calc(100vw - 40px);
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalSlideUp 0.3s ease-out;
  }

  @keyframes modalSlideUp {
    from {
      transform: translateY(30px);
      opacity: 0;
    }

    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .modal-header {
    background: #f8fafc;
    padding: 16px 20px;
    border-bottom: 1px solid var(--n-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .modal-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--n-900);
  }

  .modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(100vh - 200px);
  }

  .modal-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--n-200);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-600);
    margin-bottom: 6px;
  }

  .form-control {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--n-300);
    font-size: 13.5px;
    outline: none;
    transition: border-color 0.2s;
  }

  .form-control:focus {
    border-color: var(--n-600);
  }

  .btn-primary {
    background: var(--n-900);
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-secondary {
    background: #fff;
    border: 1px solid var(--n-300);
    color: var(--n-700);
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  /* -- DUAL PANEL MODAL -- */
  .modal-content.split-view {
    width: 1100px;
    flex-direction: row;
    align-items: stretch;
    transition: width 0.3s ease;
  }

  .modal-form-side {
    flex: 1;
    background: #fff;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--n-200);
    height: 700px;
    max-height: 85vh;
  }

  .modal-ai-side-panel {
    flex: 0;
    background: #f8fafc;
    display: none;
    flex-direction: column;
    overflow: hidden;
    height: 700px;
    max-height: 85vh;
  }

  .split-view .modal-ai-side-panel {
    display: flex;
    flex: 0.95;
    border-left: 1px solid var(--n-200);
    animation: fadeIn 0.4s ease;
  }

  .ai-side-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--n-200);
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    flex-shrink: 0;
  }

  .ai-side-header-title {
    font-weight: 700;
    color: var(--n-900);
    font-size: 14.5px;
  }

  .ai-side-body {
    padding: 24px;
    flex: 1;
    overflow-y: auto;
    font-size: 13.8px;
    line-height: 1.65;
    color: var(--n-800);
    background: #fff;
    scrollbar-width: thin;
    scrollbar-color: var(--n-200) transparent;
  }

  .ai-side-body::-webkit-scrollbar {
    width: 4px;
  }

  .ai-side-body::-webkit-scrollbar-thumb {
    background: var(--n-200);
    border-radius: 4px;
  }

  /* AI Prose styles replication */
  .ai-side-body p {
    margin: 0 0 12px 0;
  }

  .ai-side-body strong {
    color: var(--n-900);
    font-weight: 700;
  }

  .ai-side-body ul {
    margin: 6px 0 14px 0;
    padding-left: 18px;
    list-style: disc;
  }

  .ai-side-body ul li {
    margin-bottom: 6px;
    color: var(--n-700);
  }

  .ai-side-body ul li::marker {
    color: var(--n-400);
  }

  @media (max-width: 1100px) {
    .modal-content.split-view {
      flex-direction: column;
      width: 600px;
      max-height: 90vh;
    }

    .modal-form-side,
    .modal-ai-side-panel {
      height: auto;
      max-height: 45vh;
    }

    .modal-form-side {
      border-right: none;
      border-bottom: 1px solid var(--n-200);
    }
  }

  /* -- TAG MULTI-SELECT STYLES -- */
  /* -- TAG MULTI-SELECT STYLES (Refined) -- */
  .tag-select-container {
    border: 1px solid var(--n-300);
    border-radius: 8px;
    padding: 2px 4px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    min-height: 42px;
    background: #fff;
    cursor: text;
    position: relative;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .tag-select-container:focus-within {
    border-color: var(--n-800);
    box-shadow: 0 0 0 2px rgba(31, 41, 55, 0.05);
  }

  .tag-pill {
    background: #fff;
    border: 1px solid var(--n-200);
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12.5px;
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--n-700);
    font-weight: 500;
    box-shadow: var(--shadow-xs);
    user-select: none;
    animation: tagIn 0.2s ease;
  }

  @keyframes tagIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
  }

  .tag-pill i {
    cursor: pointer;
    font-size: 16px;
    font-weight: 700;
    color: var(--n-400);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 4px;
    margin-left: 2px;
    line-height: 1;
  }

  .tag-pill i:hover {
    color: #ef4444;
    background: #fee2e2;
  }

  .tag-input-ghost {
    border: none;
    outline: none;
    flex: 1;
    min-width: 80px;
    font-size: 13.5px;
    padding: 6px 4px;
    background: transparent;
  }

  .tag-select-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    padding-right: 8px;
    margin-left: auto;
    color: var(--n-400);
    font-size: 12px;
  }

  .tag-select-actions i {
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.1s;
  }

  .tag-select-actions i:hover {
    background: var(--n-100);
    color: var(--n-700);
  }

  .tag-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: 10px;
    box-shadow: var(--shadow-lg);
    z-index: 2000;
    max-height: 250px;
    overflow-y: auto;
    display: none;
    padding: 4px;
    min-height: 40px;
  }

  .tag-no-options {
    padding: 12px;
    font-size: 13px;
    color: var(--n-400);
    text-align: center;
    font-style: italic;
  }

  .tag-option {
    padding: 10px 12px;
    font-size: 13.5px;
    cursor: pointer;
    color: var(--n-700);
    border-radius: 6px;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .tag-option:hover {
    background: var(--n-50);
    color: var(--n-900);
    padding-left: 16px;
  }

  .tag-option.selected {
    display: none;
  }

  .tag-option.hidden {
    display: none;
  }

  .form-group {
    position: relative;
    overflow: visible !important;
  }
</style>

<!-- ━━━━━━━━━━━ HERO ━━━━━━━━━━━ -->
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
    <a href="reports.php?sy_id=<?= $selectedSyId ?>" class="db-hero-btn db-hero-btn-secondary">
      <svg viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
      </svg>
      Reports
    </a>
  </div><!-- /db-hero-right -->
</div><!-- /db-hero -->

<!-- ━━━━━━━━━━━ VIEW TOGGLE ━━━━━━━━━━━ -->
<div class="view-toggle-wrap">
  <div class="view-toggle">
    <button class="vt-btn active" onclick="switchView('progress', this)">Progress</button>
    <button class="vt-btn" onclick="switchView('analytics', this)">Analytics</button>
  </div>
</div>

<!-- ━━━━━━━━━━━ PROGRESS VIEW ━━━━━━━━━━━ -->
<div id="viewProgress">

  <!-- ━━━━━━━━━━━ SY CONTEXT BAR (Hidden for current year) ━━━━━━━━━━━ -->
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
        style="margin-left:auto;font-weight:700;white-space:nowrap;color:inherit;text-decoration:none;opacity:.8;">→
        Current
        SY</a>
    </div>
  <?php endif; ?>

  <?php if ($returned > 0): ?>
    <div
      style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;background:var(--amber-bg);border:1px solid #FDE68A;margin-bottom:14px;font-size:13px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;">
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

  <!-- ━━━━━━━━━━━ KPI STATS ━━━━━━━━━━━ -->
  <div class="stats-v2">
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
  </div>

  <!-- ━━━━━━━━━━━ PIPELINE ━━━━━━━━━━━ -->
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
          <div class="pipeline-val" style="color:var(--n-800);"><?= $validated ?></div>
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

  <!-- ━━━━━━━━━━━ MAIN GRID ━━━━━━━━━━━ -->
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
              <input type="text" placeholder="Search..." oninput="filterTable(this.value,'tblRecent')">
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
</div><!-- /viewProgress -->

<!-- ━━━━━━━━━━━ ANALYTICS VIEW ━━━━━━━━━━━ -->
<div id="viewAnalytics" style="display:none;">

  <!-- Filter bar -->
  <div class="an-filter-bar">
    <label>Primary SY:</label>
    <span style="font-size:13px;font-weight:700;color:var(--n-900);">
      <?= e($selectedSYLabel) ?>
    </span>
    <div style="width:1px;height:18px;background:var(--n-200);margin:0 4px;"></div>
    <label>Compare with:</label>
    <div class="p-select" id="anCompareSelect" style="width:160px;">
      <input type="hidden" name="compare_sy_id" value="<?= $compareSyId ?>">
      <div class="p-select-trigger" onclick="togglePSelect(event, 'anCompareSelect')"
        style="padding: 5px 12px; font-size: 12.5px; min-height: 32px;">
        <span class="p-select-val">
          <?= $compareSyId ? 'SY ' . e(array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') : 'None' ?>
        </span>
      </div>
      <div class="p-select-menu">
        <div class="p-select-item <?= !$compareSyId ? 'selected' : '' ?>"
          onclick="location.href='dashboard.php?sy_id=<?= $selectedSyId ?>&compare_sy=0&view=analytics'">
          None
        </div>
        <?php foreach ($allSYs as $sy):
          if ($sy['sy_id'] == $selectedSyId)
            continue; ?>
          <div class="p-select-item <?= $sy['sy_id'] == $compareSyId ? 'selected' : '' ?>"
            onclick="location.href='dashboard.php?sy_id=<?= $selectedSyId ?>&compare_sy=<?= $sy['sy_id'] ?>&view=analytics'">
            SY <?= e($sy['label']) ?>
            <?php if ($sy['sy_id'] == $compareSyId): ?>
              <span class="p-select-check"></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($compareSyId): ?>
      <span
        style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--blue-bg);color:var(--blue);">
        Comparing 2 cycles
      </span>
      <a href="dashboard.php?sy_id=<?= $selectedSyId ?>&view=analytics" class="btn btn-ghost btn-sm">✕ Clear</a>
    <?php endif; ?>

    <div style="margin-left:auto; display:flex; gap:8px;">
      <button class="ai-assistant-btn" onclick="manuallyAddImprovementPlan()">
        <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
        </svg>
        Manually Add Improvement Plan
      </button>
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
          D<?= $anTopDim['dimension_no'] ?>
        </div>
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
        <div class="an-insight-delta up">All indicators &ge; 2.5</div>
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
              <?= e($selectedSYLabel) ?></span>
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
          <p style="color:var(--n-400);font-size:13px;text-align:center;">Not enough cycles to show a trend.</p>
        <?php endif; ?>
      </div>
    </div>
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

  <!-- Dimension Score Bar -->
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

  <!-- Tabbed bottom section -->
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
      <div class="card-head">
        <span class="card-title">Assessment History</span>
        <span style="font-size:12px;color:var(--n-400);"><?= count($cycleHistory) ?> cycle(s)</span>
      </div>
      <?php if ($cycleHistory): ?>
        <div class="tbl-wrap">
          <table class="tbl-enhanced">
            <thead>
              <tr>
                <th>#</th>
                <th>School Year</th>
                <th>Overall Score</th>
                <th>Maturity</th>
                <th>vs Prev</th>
                <th>Status</th>
                <th>Validated</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $prevScore = null;
              foreach ($cycleHistory as $i => $sc):
                $mat = sbmMaturityLevel(floatval($sc['overall_score']));
                $delta = $prevScore !== null ? round(floatval($sc['overall_score']) - $prevScore, 2) : null;
                $prevScore = floatval($sc['overall_score']);
                ?>
                <tr>
                  <td style="width:32px;"><span
                      style="width:22px;height:22px;border-radius:6px;background:var(--n-100);color:var(--n-600);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;"><?= $i + 1 ?></span>
                  </td>
                  <td><strong style="font-size:13px;">SY <?= e($sc['sy_label']) ?></strong></td>
                  <td>
                    <div class="score-bar-cell">
                      <div class="score-bar-track">
                        <div class="score-bar-fill"
                          style="width:<?= $sc['overall_score'] ?>%;background:<?= $mat['color'] ?>;"></div>
                      </div>
                      <span class="score-val" style="color:<?= $mat['color'] ?>;"><?= $sc['overall_score'] ?>%</span>
                    </div>
                  </td>
                  <td><span class="pill pill-<?= e($sc['maturity_level']) ?>"><?= e($sc['maturity_level']) ?></span></td>
                  <td>
                    <?php if ($delta !== null): ?>
                      <span
                        style="font-size:12.5px;font-weight:700;color:<?= $delta > 0 ? '#16A34A' : ($delta < 0 ? '#DC2626' : '#9CA3AF') ?>;">
                        <?= $delta > 0 ? '▲ +' : '▼ ' ?>       <?= abs($delta) ?>%
                      </span>
                    <?php else: ?><span style="color:var(--n-400);font-size:12px;">First</span><?php endif; ?>
                  </td>
                  <td><span class="pill pill-<?= e($sc['status']) ?>"
                      style="font-size:10px;"><?= ucfirst($sc['status']) ?></span></td>
                  <td style="font-size:12px;color:var(--n-400);">
                    <?= $sc['validated_at'] ? date('M d, Y', strtotime($sc['validated_at'])) : '—' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-title">No cycle history yet</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: Weak This Cycle -->
  <div class="an-tab-panel" id="anTabWeak">
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head">
        <span class="card-title">Indicators Needing Attention — Current SY</span>
        <span style="font-size:12px;color:var(--n-400);">Lowest average ratings this cycle</span>
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
                <?= e(substr($ind['indicator_text'], 0, 100)) ?>”¦
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
          <div class="empty-title">No indicator data yet</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: Consistently Weak -->
  <div class="an-tab-panel" id="anTabConsistent">
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head">
        <span class="card-title">Consistently Weak Indicators</span>
        <span style="font-size:12px;color:var(--n-400);">Average &le; 2.5 across all assessed cycles</span>
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
          <span style="font-size:12px;color:var(--n-400);">SY <?= e($selectedSYLabel) ?> vs SY
            <?= e(array_column($allSYs, 'label', 'sy_id')[$compareSyId] ?? '') ?></span>
        </div>
        <div class="tbl-wrap">
          <table class="tbl-enhanced">
            <thead>
              <tr>
                <th>Dimension</th>
                <th>SY <?= e($selectedSYLabel) ?></th>
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
  // -- SY Dropdown toggle --------------------------------------
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
  function switchView(view, btn) {
    document.querySelectorAll('.vt-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('viewProgress').style.display = view === 'progress' ? '' : 'none';
    document.getElementById('viewAnalytics').style.display = view === 'analytics' ? '' : 'none';

    // Update URL to persist view on refresh
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

  // -- Analytics tab switching --------------------------------
  function anSwitchTab(btn, panelId) {
    document.querySelectorAll('.an-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.an-tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId)?.classList.add('active');
  }

  // -- Analytics charts (lazy init) --------------------------
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
  const anCurrSyLabel = <?= json_encode($selectedSYLabel) ?>;

  function initAnalyticsCharts() {
    // -- Radar chart ------------------------------------------
    if (anDimValues.some(v => v > 0)) {
      const radarDatasets = [{
        label: 'SY ' + anCurrSyLabel,
        data: anDimValues,
        backgroundColor: 'rgba(22,163,74,.13)',
        borderColor: '#16A34A',
        pointBackgroundColor: anDimColors,
        pointRadius: 5, borderWidth: 2,
      }];
      if (anDimValCmp.length && anDimValCmp.some(v => v > 0)) {
        radarDatasets.push({
          label: 'SY ' + anCompareSyLabel,
          data: anDimValCmp,
          backgroundColor: 'rgba(37,99,235,.10)',
          borderColor: '#2563EB',
          pointBackgroundColor: '#2563EB',
          pointRadius: 4, borderWidth: 2, borderDash: [4, 4],
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

    // -- Overall score trend line ----------------------------â”€
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

    // -- Dimension trend lines --------------------------------
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

    // -- Dimension bar chart ----------------------------------
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

  // -- Auto-switch to analytics if ?view=analytics is in URL --
  (function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('view') === 'analytics') {
      const btn = document.querySelectorAll('.vt-btn')[1];
      if (btn) switchView('analytics', btn);
    }
  })();
</script>


<!-- ── AI ASSISTANT PANEL ── -->
<div id="aiAssistant" class="ai-panel">
  <div class="ai-panel-fab" onclick="checkToggleMinimize(event)" title="Open AI Assistant">
    <img src="<?= e(baseUrl()) ?>/assets/seal.png" alt="School Seal"
      style="width:54px;height:54px;border-radius:50%;object-fit:cover;display:block;">
  </div>
  <div class="ai-panel-header" onclick="checkToggleMinimize(event)">
    <div>
      <div class="ai-panel-title">AI Suggestion Improvement Plan</div>
      <div style="font-size:11px;opacity:0.8;">Actionable Suggestions</div>
    </div>
    <div class="ai-panel-actions">
      <button class="ai-panel-btn" onclick="toggleMinimizeAIAssistant(event)" title="Minimize">&minus;</button>
      <button class="ai-panel-btn" onclick="closeAIAssistant(event)">&times;</button>
    </div>
  </div>
  <div id="aiChatBody" class="ai-chat-body"></div>
</div>

<!-- ── IMPROVEMENT PLAN MODAL ── -->
<div id="improvementPlanModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title">Manually Add Improvement Plan</div>
        <button class="btn btn-ghost" style="padding:4px;" onclick="closeImprovementPlanModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="improvementPlanForm">
          <div class="form-group">
            <label>Dimension(s)</label>
            <div class="tag-select-container" id="dimTagContainer">
              <input type="text" class="tag-input-ghost" id="dimTagInput" placeholder="Select Dimensions...">
              <div class="tag-dropdown" id="dimTagDropdown">
                <?php foreach ($dimScores as $d):
                ?>
                  <div class="tag-option" data-id="<?= $d['dimension_id'] ?>" data-name="D<?= $d['dimension_no'] ?>">
                    D<?= $d['dimension_no'] ?> - <?= e($d['dimension_name']) ?> (<?= $d['avg_pct'] ?>%) <?= ($d['avg_pct'] < 50) ? '⚠️' : '' ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <input type="hidden" name="dimension_ids" id="dimIdsInput" required>
          </div>

          <div class="form-group">
            <label>Indicator(s) <small style="color:var(--n-500);">(Only indicators with rating &le; 2.5 available)</small></label>
            <div class="tag-select-container" id="indTagContainer">
              <input type="text" class="tag-input-ghost" id="indTagInput" placeholder="Select Indicators...">
              <div class="tag-dropdown" id="indTagDropdown">
                <!-- Populated via JS -->
              </div>
            </div>
            <input type="hidden" name="indicator_ids" id="indIdsInput" required>
          </div>
          <div class="grid2">
            <div class="form-group">
              <label>Priority</label>
              <select name="priority_level" class="form-control">
                <option value="High">High</option>
                <option value="Medium" selected>Medium</option>
                <option value="Low">Low</option>
              </select>
            </div>
            <div class="form-group">
              <label>Target Date</label>
              <input type="date" name="target_date" class="form-control">
            </div>
          </div>
          <div class="form-group">
            <label>Objective</label>
            <textarea name="objective" class="form-control" rows="2" placeholder="What do you want to achieve?"
              required></textarea>
          </div>
          <div class="form-group">
            <label>Strategy</label>
            <textarea name="strategy" class="form-control" rows="2" placeholder="How will you achieve it?"
              required></textarea>
          </div>
          <div class="form-group">
            <label>Person Responsible</label>
            <input type="text" name="person_responsible" class="form-control" placeholder="E.g. Principal, Grade Level Head">
          </div>
          <div class="grid2">
            <div class="form-group">
              <label>Resources Needed</label>
              <textarea name="resources_needed" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Expected Output</label>
              <textarea name="expected_output" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="toggleAISuggestionSplit()" id="toggleAiModalBtn">View AI Suggestion</button>
        <div style="flex:1"></div>
        <button class="btn-secondary" onclick="closeImprovementPlanModal()">Cancel</button>
        <button class="btn-primary" onclick="saveImprovementPlan(event)">Save Improvement Plan</button>
      </div>
    </div>

    <!-- AI Side Panel -->
    <div class="modal-ai-side-panel" id="modalAiPanel">
      <div class="ai-side-header">
        <img src="assets/seal.png" style="width:24px;height:24px;object-fit:contain;">
        <div class="ai-side-header-title">AI Assessment & Recommendations</div>
      </div>
      <div class="ai-side-body" id="modalAiBody">
        <div class="typing" style="padding:24px;">
          <div class="dot"></div><div class="dot"></div><div class="dot"></div>
          <p style="margin-top:12px; font-size:12px; color:var(--n-500);">Generating recommendations...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Data for AI Assistant
  const weakIndicatorsBase = <?= json_encode($weakIndicatorRows) ?>;

  // -- AI Panel State Management --
  function setAIPanelState(state) {
    const panel = document.getElementById('aiAssistant');
    if (!panel) return;

    panel.classList.toggle('open', state === 'open' || state === 'minimized');
    panel.classList.toggle('minimized', state === 'minimized');

    localStorage.setItem('ai_panel_state', state);
  }

// ── TAG MULTI-SELECT COMPONENT (Refined) ────────────────────────
  function initTagSelect(prefix, options, onUpdate) {
    const container = document.getElementById(prefix + 'TagContainer');
    const input = document.getElementById(prefix + 'TagInput');
    const dropdown = document.getElementById(prefix + 'TagDropdown');
    const hidden = document.getElementById(prefix + 'IdsInput');

    let selected = [];
    
    // Add Clear/Dropdown actions visually
    const actions = document.createElement('div');
    actions.className = 'tag-select-actions';
    actions.innerHTML = `
      <i class="fas fa-times clear-btn" title="Clear All" style="display:none;"></i>
      <i class="fas fa-chevron-down chevron-btn"></i>
    `;
    container.appendChild(actions);

    const clearBtn = actions.querySelector('.clear-btn');
    const chevronBtn = actions.querySelector('.chevron-btn');

    const render = () => {
      // Clear existing tags
      container.querySelectorAll('.tag-pill').forEach(tp => tp.remove());
      // Add tags
      selected.forEach(id => {
        const item = options.find(o => o.id == id);
        if (!item) return;

        const tag = document.createElement('div');
        tag.className = 'tag-pill';
        tag.innerHTML = `${item.name || item.label} <i onclick="window['removeTag${prefix}']('${id}', event)">&times;</i>`;
        container.insertBefore(tag, input);
      });
      hidden.value = selected.join(',');
      
      // Update dropdown options visibility
      const opts = dropdown.querySelectorAll('.tag-option');
      let visibleCount = 0;
      opts.forEach(opt => {
        const id = opt.getAttribute('data-id');
        const isSel = selected.includes(id);
        opt.classList.toggle('selected', isSel);
        if(!isSel && !opt.classList.contains('hidden')) visibleCount++;
      });

      // Handle "No options" message
      let emptyMsg = dropdown.querySelector('.tag-no-options');
      if (visibleCount === 0) {
        if (!emptyMsg) {
          emptyMsg = document.createElement('div');
          emptyMsg.className = 'tag-no-options';
          emptyMsg.textContent = 'No available options';
          dropdown.appendChild(emptyMsg);
        }
      } else if (emptyMsg) {
        emptyMsg.remove();
      }

      clearBtn.style.display = selected.length > 0 ? 'inline-block' : 'none';
      if (onUpdate) onUpdate(selected);
    };

    // Toggle dropdown
    const toggleDropdown = (show) => {
      const isCurrentlyOpen = dropdown.style.display === 'block';
      const open = (show === undefined) ? !isCurrentlyOpen : show;
      dropdown.style.display = open ? 'block' : 'none';
      chevronBtn.style.transform = open ? 'rotate(180deg)' : 'rotate(0)';
    };

    // Close modal on backdrop click
    const modal = document.getElementById('improvementPlanModal');
    if (modal) {
      modal.onclick = (e) => {
        if (e.target === modal) closeImprovementPlanModal();
      };
    }


    container.onclick = (e) => {
      e.stopPropagation();
      input.focus();
      toggleDropdown(true);
    };

    input.oninput = (e) => {
      const q = e.target.value.toLowerCase();
      dropdown.querySelectorAll('.tag-option').forEach(opt => {
        const text = opt.textContent.toLowerCase();
        opt.classList.toggle('hidden', !text.includes(q));
      });
      toggleDropdown(true);
    };

    input.onclick = (e) => {
      e.stopPropagation();
      toggleDropdown(true);
    };

    clearBtn.onclick = (e) => {
      e.stopPropagation();
      selected = [];
      render();
      toggleDropdown(false);
    };

    chevronBtn.onclick = (e) => {
      e.stopPropagation();
      toggleDropdown();
    };

    document.addEventListener('click', () => toggleDropdown(false));

    // Initial item selection
    const wireOptions = (div) => {
      div.onclick = (e) => {
        e.stopPropagation();
        const id = div.getAttribute('data-id');
        if (!selected.includes(id)) {
          selected.push(id);
          input.value = ''; // clear search
          dropdown.querySelectorAll('.tag-option').forEach(o => o.classList.remove('hidden'));
          render();
        }
      };
    }

    dropdown.querySelectorAll('.tag-option').forEach(wireOptions);

    window['removeTag' + prefix] = (id, event) => {
      if(event) event.stopPropagation();
      selected = selected.filter(s => s != id);
      render();
    };

    return { 
      setOptions: (newOpts) => {
        options = newOpts;
        dropdown.innerHTML = '';
        selected = [];
        newOpts.forEach(o => {
          const div = document.createElement('div');
          div.className = 'tag-option';
          div.setAttribute('data-id', o.id);
          div.textContent = o.name || o.label;
          wireOptions(div);
          dropdown.appendChild(div);
        });
        render();
      },
      reset: () => {
        selected = [];
        input.value = '';
        render();
      }
    };
  }

  let dimTagControl, indTagControl;
  document.addEventListener('DOMContentLoaded', () => {
    const dimOptions = Array.from(document.querySelectorAll('#dimTagDropdown .tag-option')).map(opt => ({
      id: opt.getAttribute('data-id'),
      name: opt.getAttribute('data-name')
    }));

    dimTagControl = initTagSelect('dim', dimOptions, (selectedDimIds) => {
      const filteredIndicators = weakIndicatorsBase
        .filter(wi => selectedDimIds.includes(wi.dimension_id.toString()))
        .map(wi => ({
          id: wi.indicator_id,
          name: wi.indicator_code + ': ' + (wi.indicator_text.length > 60 ? wi.indicator_text.substring(0, 60) + "..." : wi.indicator_text),
          label: wi.indicator_code
        }));
      if(indTagControl) indTagControl.setOptions(filteredIndicators);
    });

    indTagControl = initTagSelect('ind', []);
  });

  function removeTag(prefix, id, e) {
    if(e) e.stopPropagation();
    window['removeTag' + prefix](id);
  }

  function manuallyAddImprovementPlan() {
    document.getElementById('improvementPlanModal').style.display = 'flex';
  }

  function toggleAISuggestionSplit() {
    const modal = document.querySelector('#improvementPlanModal .modal-content');
    const btn = document.getElementById('toggleAiModalBtn');
    modal.classList.toggle('split-view');

    if (modal.classList.contains('split-view')) {
      btn.textContent = 'Hide AI Suggestion';
      loadModalAISuggestions();
    } else {
      btn.textContent = 'View AI Suggestion';
    }
  }

  let cachedAIPlanResponse = "";

  async function loadModalAISuggestions() {
    const body = document.getElementById('modalAiBody');
    if (!body) return;

    if (cachedAIPlanResponse) {
      body.innerHTML = parseAILogicToHtml(cachedAIPlanResponse);
      return;
    }

    // Attempt to get data from current session if available
    const existingMsg = document.querySelector('#aiChatBody .chat-msg.ai:last-child');
    if (existingMsg) {
      cachedAIPlanResponse = existingMsg.innerHTML; // Note: this is already HTML, but parseAILogicToHtml expects text. 
      // Better to check for data-raw if we add it. 
      body.innerHTML = cachedAIPlanResponse;
      return;
    }

    // Otherwise, show a notice or trigger a fetch
    body.innerHTML = '<div style="padding:20px; text-align:center;"><p style="color:var(--n-500); font-size:13px;">Use the floating AI Assistant at the bottom right to generate a full school evaluation, then view it here alongside your plan.</p></div>';
  }

  function closeImprovementPlanModal() {
    document.getElementById('improvementPlanModal').style.display = 'none';
    document.querySelector('#improvementPlanModal .modal-content').classList.remove('split-view');
    document.getElementById('toggleAiModalBtn').textContent = 'View AI Suggestion';
    
    // Reset tag controls and form
    if(dimTagControl) dimTagControl.reset();
    if(indTagControl) indTagControl.reset();
    document.getElementById('improvementPlanForm').reset();
  }

  async function saveImprovementPlan(e) {
    if (e) e.preventDefault();
    const form = document.getElementById('improvementPlanForm');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    formData.append('action', 'save_improvement_plan');
    formData.append('sy_id', '<?= $selectedSyId ?>');

    const btn = e.target;
    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
      const res = await fetch(window.location.href, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        alert('Improvement Plan saved successfully!');
        closeImprovementPlanModal();
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch (err) {
      console.error(err);
      alert('Network error. Failed to save.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Save Improvement Plan';
    }
  }

  function openAIAssistant() {
    setAIPanelState('open');
    const body = document.getElementById('aiChatBody');
    if (body.children.length === 0) {
      startAISession();
    }
  }

  function closeAIAssistant(e) {
    if (e) e.stopPropagation();
    setAIPanelState('closed');
  }

  function toggleMinimizeAIAssistant(e) {
    if (e) e.stopPropagation();
    const panel = document.getElementById('aiAssistant');
    const newState = panel.classList.contains('minimized') ? 'open' : 'minimized';
    setAIPanelState(newState);
  }

  function checkToggleMinimize(e) {
    const panel = document.getElementById('aiAssistant');
    if (panel.classList.contains('minimized')) {
      setAIPanelState('open');
    }
  }

  // Restore state on load
  document.addEventListener('DOMContentLoaded', () => {
    const savedState = localStorage.getItem('ai_panel_state') || 'closed';

    if (savedState !== 'closed') {
      const panel = document.getElementById('aiAssistant');
      const body = document.getElementById('aiChatBody');

      // Explicitly set classes based on saved state
      panel.classList.add('open');
      if (savedState === 'minimized') {
        panel.classList.add('minimized');
      }

      // Trigger data fetching if panel was active
      if (body.children.length === 0) {
        startAISession();
      }
    }
  });

  function addMessage(text, type = 'ai', delay = 0) {
    const body = document.getElementById('aiChatBody');

    if (delay > 0) {
      const typing = document.createElement('div');
      typing.className = 'typing';
      typing.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
      body.appendChild(typing);
      body.scrollTop = body.scrollHeight;

      return new Promise(resolve => {
        setTimeout(() => {
          typing.remove();
          renderMessage(text, type);
          resolve();
        }, delay);
      });
    } else {
      renderMessage(text, type);
      return Promise.resolve();
    }
  }

  function renderMessage(content, type) {
    const body = document.getElementById('aiChatBody');
    const msg = document.createElement('div');
    msg.className = `chat-msg ${type}`;

    if (type === 'ai') {
      msg.innerHTML = parseAILogicToHtml(content);
    } else {
      msg.textContent = content;
    }

    body.appendChild(msg);
    body.scrollTop = body.scrollHeight;
  }

  /** Simple parser for AI markdown-like response */
  function parseAILogicToHtml(text) {
    // Bold: **text** -> <strong>text</strong>
    let html = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Bullets: - item -> <li>item</li>
    const lines = html.split('\n');
    let finalHtml = '';
    let inList = false;

    lines.forEach(line => {
      const trimmed = line.trim();
      if (trimmed.startsWith('- ')) {
        if (!inList) {
          finalHtml += '<ul>';
          inList = true;
        }
        finalHtml += '<li>' + trimmed.substring(2) + '</li>';
      } else if (trimmed === '---') {
        if (inList) { finalHtml += '</ul>'; inList = false; }
        finalHtml += '<hr>';
      } else {
        if (inList) {
          finalHtml += '</ul>';
          inList = false;
        }
        if (trimmed) {
          finalHtml += '<p>' + trimmed + '</p>';
        }
      }
    });

    if (inList) finalHtml += '</ul>';
    return finalHtml;
  }

  async function startAISession() {
    const body = document.getElementById('aiChatBody');

    // Show a subtle status message instead of a full chat bubble
    const status = document.createElement('div');
    status.className = 'chat-msg status';
    status.textContent = 'Analyzing your SBM data...';
    body.appendChild(status);

    // Show thinking dots
    const thinking = document.createElement('div');
    thinking.className = 'typing';
    thinking.id = 'groqThinking';
    thinking.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
    body.appendChild(thinking);
    body.scrollTop = body.scrollHeight;

    const formData = new FormData();
    formData.append('action', 'get_ai_suggestions');
    formData.append('sy_id', '<?= $selectedSyId ?>');

    try {
      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      const data = await res.json();

      status.remove();
      if (thinking) thinking.remove();

      if (data.error) {
        addMessage("I couldn't reach the analysis service right now. Please check if the ML service is running.", 'ai', 0);
        renderFallbackSuggestions();
        return;
      }

      const recs = data.recommendations || '';
      if (recs) {
        addMessage(recs, 'ai', 0);
      } else {
        addMessage("I've reviewed your data. Your performance is currently optimal with no critical gaps detected. Keep up the great work!", 'ai', 0);
      }
    } catch (err) {
      console.error(err);
      status.remove();
      if (thinking) thinking.remove();
      addMessage("Connection failed. Please ensure the ML microservice is active.", 'ai', 0);
      renderFallbackSuggestions();
    }
  }

  function renderFallbackSuggestions() {
    if (weakIndicatorsBase && weakIndicatorsBase.length > 0) {
      addMessage(`I identified ${weakIndicatorsBase.length} weak indicators. Focusing on ${weakIndicatorsBase[0].dimension_name}...`, 'ai', 1000);
    }
  }
</script>
<?= deadlineChipCss() ?>
<?= deadlineChipJs() ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>