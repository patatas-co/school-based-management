<?php
ob_start();
// school_head/ai_suggestion_planning.php — AI Suggestion Planning
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/ai_usage_limiter.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

// ── AI USAGE STATUS (read-only, used to restore button/timer state on page load) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_ai_usage_status') {
  header('Content-Type: application/json');
  echo json_encode(aiUsageGetStatus($db, $currentUserId));
  exit;
}

// ── AI ASSISTANT AJAX HANDLER (reused from school_head/dashboard.php) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_ai_suggestions') {
  header('Content-Type: application/json');
  require_once __DIR__ . '/../includes/ml_service.php';

  // ── Enforce daily limit + cooldown BEFORE calling the Groq API ──
  $usageCheck = aiUsageCheckAndConsume($db, $currentUserId);
  if (!$usageCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
      'error' => $usageCheck['reason'],
      'message' => $usageCheck['message'],
      'retry_after' => $usageCheck['retry_after'],
      'remaining' => $usageCheck['remaining'],
      'limit' => $usageCheck['limit'],
    ]);
    exit;
  }

  $schoolIdAjax = (int) ($_SESSION['school_id'] ?? 0);
  $syIdAjax = (int) ($_POST['sy_id'] ?? 0);

  $sQ = $db->prepare("SELECT school_name FROM schools WHERE school_id = ?");
  $sQ->execute([$schoolIdAjax]);
  $schoolName = $sQ->fetchColumn() ?: 'School';

  $syQ = $db->prepare("SELECT label FROM school_years WHERE sy_id = ?");
  $syQ->execute([$syIdAjax]);
  $syLabelAjax = $syQ->fetchColumn() ?: 'Unknown';

  $dimQ = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, ROUND(AVG(ds.percentage), 1) as avg_pct
        FROM sbm_dimensions d
        LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
        LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id AND c.sy_id = ? AND c.school_id = ?
        GROUP BY d.dimension_id ORDER BY d.dimension_no
    ");
  $dimQ->execute([$syIdAjax, $schoolIdAjax]);
  $dimScoresAjax = [];
  foreach ($dimQ->fetchAll() as $row) {
    $dimScoresAjax[] = [
      'dimension_name' => $row['dimension_name'],
      'score' => (float) $row['avg_pct'],
      'maturity' => sbmMaturityLevel(floatval($row['avg_pct']))['label']
    ];
  }

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
  $weakQ->execute([$syIdAjax, $schoolIdAjax]);
  $byRatingAjax = ['1' => [], '2' => []];
  foreach ($weakQ->fetchAll() as $row) {
    $r = (int) floor($row['rating']);
    if ($r < 1) $r = 1;
    if ($r > 2) $r = 2;
    $byRatingAjax[strval($r)][] = [
      'code' => $row['indicator_code'],
      'text' => $row['indicator_text'],
      'rating' => (float) $row['rating']
    ];
  }

  $histQ = $db->prepare("
        SELECT overall_score FROM sbm_cycles 
        WHERE school_id = ? AND status IN ('validated','finalized','completed') AND sy_id != ? 
        ORDER BY created_at DESC LIMIT 3
    ");
  $histQ->execute([$schoolIdAjax, $syIdAjax]);
  $historyAjax = $histQ->fetchAll();

  $scoreQ = $db->prepare("
        SELECT overall_score, maturity_level FROM sbm_cycles 
        WHERE school_id = ? AND sy_id = ? AND status IN ('validated','finalized','completed')
        ORDER BY created_at DESC LIMIT 1
    ");
  $scoreQ->execute([$schoolIdAjax, $syIdAjax]);
  $scoreDataAjax = $scoreQ->fetch();
  $overallScoreAjax = $scoreDataAjax ? (float) $scoreDataAjax['overall_score'] : 0;
  $overallMaturityAjax = $scoreDataAjax ? $scoreDataAjax['maturity_level'] : 'N/A';

  $payload = [
    'school_name' => $schoolName,
    'sy_label' => $syLabelAjax,
    'analysis' => [
      'gap_analysis' => [
        'average_score' => $overallScoreAjax,
        'overall_maturity' => $overallMaturityAjax,
        'weakest_dimensions' => array_slice($dimScoresAjax, 0, 3)
      ],
      'by_rating' => $byRatingAjax,
      'history' => $historyAjax,
      'comment_summary' => ['top_topics' => [], 'has_urgent' => false]
    ]
  ];

  $response = ml_post('/api/recommend', $payload);
  $response = $response ?: [
    'recommendations' => "I'm sorry, I'm having trouble connecting to my central intelligence. Please check if the ML service is running.",
    'error' => 'Service Unavailable'
  ];

  // Persist the successful result so it survives refresh/logout.
  if (!empty($response['recommendations']) && empty($response['error'])) {
    aiUsageSaveRecommendation($db, $currentUserId, $response['recommendations']);
  }

  $response['remaining'] = $usageCheck['remaining'];
  $response['limit'] = $usageCheck['limit'];
  echo json_encode($response);
  exit;
}

// ── IP FIELD USAGE STATUS (read-only, restores button/label state on load) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_ip_field_usage_status') {
  header('Content-Type: application/json');
  echo json_encode([
    'objective' => ipFieldUsageGetStatus($db, $currentUserId, 'objective'),
    'strategy'  => ipFieldUsageGetStatus($db, $currentUserId, 'strategy'),
  ]);
  exit;
}

// ── GENERATE OBJECTIVE/STRATEGY FROM A MATCHED AI RECOMMENDATION ──
// Only ever receives the single matched snippet (extracted client-side
// from the already-displayed AI Suggestions text) — never the full article.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_ip_field') {
  header('Content-Type: application/json');

  $fieldType     = $_POST['field_type'] ?? '';
  $indicatorCode = trim($_POST['indicator_code'] ?? '');
  $indicatorText = trim($_POST['indicator_text'] ?? '');
  $dimensionName = trim($_POST['dimension_name'] ?? '');
  $snippet       = trim($_POST['snippet'] ?? '');

  if (!in_array($fieldType, ['objective', 'strategy'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field type.']);
    exit;
  }
  if ($snippet === '' || $indicatorCode === '') {
    echo json_encode(['success' => false, 'message' => 'No matching AI suggestion found for this indicator.']);
    exit;
  }

  // ── Enforce the 3-per-field daily limit BEFORE calling the ML service ──
  $ipUsageCheck = ipFieldUsageCheckAndConsume($db, $currentUserId, $fieldType);
  if (!$ipUsageCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
      'success' => false,
      'message' => $ipUsageCheck['message'],
      'remaining' => $ipUsageCheck['remaining'],
      'limit' => $ipUsageCheck['limit'],
    ]);
    exit;
  }

  require_once __DIR__ . '/../includes/ml_service.php';

  $payload = [
    'field_type'     => $fieldType,
    'indicator_code' => $indicatorCode,
    'indicator_text' => $indicatorText,
    'dimension_name' => $dimensionName,
    'snippet'        => $snippet,
  ];

  $response = ml_post('/api/generate_ip_field', $payload);

  if (!$response || empty($response['text'])) {
    echo json_encode([
      'success' => false,
      'message' => 'Could not generate content. Please try again.',
      'remaining' => $ipUsageCheck['remaining'],
      'limit' => $ipUsageCheck['limit'],
    ]);
    exit;
  }

  echo json_encode([
    'success' => true,
    'text' => $response['text'],
    'remaining' => $ipUsageCheck['remaining'],
    'limit' => $ipUsageCheck['limit'],
  ]);
  exit;
}

// ── IMPROVEMENT PLAN WORKFLOW CONSTANTS ─────────────────────────
// Allowed workflow_status values. Kept as plain strings (not a DB ENUM)
// so future statuses (pending_review, returned, approved) are just new
// values here + in the Coordinator UI — no schema migration needed.
define('IP_STATUS_DRAFT', 'draft');
define('IP_STATUS_SUBMITTED', 'submitted');
// Future (not implemented yet): IP_STATUS_PENDING_REVIEW, IP_STATUS_RETURNED, IP_STATUS_APPROVED

/**
 * Resolves the active cycle_id for a given school + SY.
 * Centralized here so every handler below scopes to the same cycle.
 */
function ipResolveCycleId(PDO $db, int $schoolId, int $syId): ?int
{
  $cQ = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id = ? AND sy_id = ? ORDER BY created_at DESC LIMIT 1");
  $cQ->execute([$schoolId, $syId]);
  $cycleId = $cQ->fetchColumn();
  return $cycleId ? (int) $cycleId : null;
}

/**
 * True if this cycle already has a submitted batch — blocks new drafts
 * and edits/deletes per the "one-and-done per SY" rule (until a future
 * "returned for revision" status re-opens it).
 */
function ipCycleIsSubmitted(PDO $db, int $cycleId): bool
{
  $q = $db->prepare("SELECT COUNT(*) FROM improvement_plans WHERE cycle_id = ? AND workflow_status = ?");
  $q->execute([$cycleId, IP_STATUS_SUBMITTED]);
  return ((int) $q->fetchColumn()) > 0;
}

// ── SAVE IMPROVEMENT PLAN AJAX HANDLER (moved from school_head/dashboard.php) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_improvement_plan') {
  header('Content-Type: application/json');
  $schoolIdIp = (int) ($_SESSION['school_id'] ?? 1);
  $syIdIp = (int) ($_POST['sy_id'] ?? 0);
  $indIds = explode(',', $_POST['indicator_ids'] ?? '');
  $obj = trim($_POST['objective'] ?? '');
  $strat = trim($_POST['strategy'] ?? '');
  $person = trim($_POST['person_responsible'] ?? '');
  $target = $_POST['target_date'] ?? null;
  $res = trim($_POST['resources_needed'] ?? '');
  $output = trim($_POST['expected_output'] ?? '');
  $priority = $_POST['priority_level'] ?? 'Medium';

  if ($obj === '' || $strat === '') {
    echo json_encode(['success' => false, 'message' => 'Objective and Strategy are required.']);
    exit;
  }
  if (!in_array($priority, ['High', 'Medium', 'Low'], true)) {
    $priority = 'Medium';
  }

  $cycleIdIp = ipResolveCycleId($db, $schoolIdIp, $syIdIp);
  if (!$cycleIdIp) {
    echo json_encode(['success' => false, 'message' => 'No assessment cycle found for this year. Please create one first.']);
    exit;
  }

  // ── One-and-done guard: can't add drafts to an already-submitted batch ──
  if (ipCycleIsSubmitted($db, $cycleIdIp)) {
    echo json_encode(['success' => false, 'message' => 'Improvement plans for this school year have already been submitted and can no longer be edited.']);
    exit;
  }

  $db->beginTransaction();
  try {
    $ins = $db->prepare("INSERT INTO improvement_plans (school_id, cycle_id, dimension_id, indicator_id, priority_level, objective, strategy, person_responsible, target_date, resources_needed, expected_output, workflow_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $inserted = 0;
    foreach ($indIds as $indId) {
      if (!$indId)
        continue;
      $dimQ = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id = ?");
      $dimQ->execute([$indId]);
      $dId = $dimQ->fetchColumn();
      if (!$dId) continue;

      $ins->execute([$schoolIdIp, $cycleIdIp, $dId, $indId, $priority, $obj, $strat, $person ?: null, $target ?: null, $res ?: null, $output ?: null, IP_STATUS_DRAFT, $currentUserId]);
      $inserted++;
    }

    if ($inserted === 0) {
      $db->rollBack();
      echo json_encode(['success' => false, 'message' => 'Please select at least one indicator.']);
      exit;
    }

    $db->commit();
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ── UPDATE IMPROVEMENT PLAN (draft-only) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_improvement_plan') {
  header('Content-Type: application/json');
  $schoolIdIp = (int) ($_SESSION['school_id'] ?? 1);
  $planId = (int) ($_POST['plan_id'] ?? 0);
  $obj = trim($_POST['objective'] ?? '');
  $strat = trim($_POST['strategy'] ?? '');
  $person = trim($_POST['person_responsible'] ?? '');
  $target = $_POST['target_date'] ?? null;
  $res = trim($_POST['resources_needed'] ?? '');
  $output = trim($_POST['expected_output'] ?? '');
  $priority = $_POST['priority_level'] ?? 'Medium';

  if (!$planId) {
    echo json_encode(['success' => false, 'message' => 'Invalid plan.']);
    exit;
  }
  if ($obj === '' || $strat === '') {
    echo json_encode(['success' => false, 'message' => 'Objective and Strategy are required.']);
    exit;
  }
  if (!in_array($priority, ['High', 'Medium', 'Low'], true)) {
    $priority = 'Medium';
  }

  // Ownership + status guard: must belong to this school AND still be a draft.
  $chk = $db->prepare("SELECT plan_id FROM improvement_plans WHERE plan_id = ? AND school_id = ? AND workflow_status = ?");
  $chk->execute([$planId, $schoolIdIp, IP_STATUS_DRAFT]);
  if (!$chk->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'This plan can no longer be edited.']);
    exit;
  }

  try {
    $upd = $db->prepare("UPDATE improvement_plans SET priority_level = ?, objective = ?, strategy = ?, person_responsible = ?, target_date = ?, resources_needed = ?, expected_output = ? WHERE plan_id = ?");
    $upd->execute([$priority, $obj, $strat, $person ?: null, $target ?: null, $res ?: null, $output ?: null, $planId]);
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ── DELETE IMPROVEMENT PLAN (draft-only) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_improvement_plan') {
  header('Content-Type: application/json');
  $schoolIdIp = (int) ($_SESSION['school_id'] ?? 1);
  $planId = (int) ($_POST['plan_id'] ?? 0);

  if (!$planId) {
    echo json_encode(['success' => false, 'message' => 'Invalid plan.']);
    exit;
  }

  $chk = $db->prepare("SELECT plan_id FROM improvement_plans WHERE plan_id = ? AND school_id = ? AND workflow_status = ?");
  $chk->execute([$planId, $schoolIdIp, IP_STATUS_DRAFT]);
  if (!$chk->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'This plan can no longer be deleted.']);
    exit;
  }

  try {
    $del = $db->prepare("DELETE FROM improvement_plans WHERE plan_id = ?");
    $del->execute([$planId]);
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ── SUBMIT IMPROVEMENT PLANS (bulk: all drafts for this SY's cycle) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_improvement_plans') {
  header('Content-Type: application/json');
  $schoolIdIp = (int) ($_SESSION['school_id'] ?? 1);
  $syIdIp = (int) ($_POST['sy_id'] ?? 0);

  $cycleIdIp = ipResolveCycleId($db, $schoolIdIp, $syIdIp);
  if (!$cycleIdIp) {
    echo json_encode(['success' => false, 'message' => 'No assessment cycle found for this year.']);
    exit;
  }

  if (ipCycleIsSubmitted($db, $cycleIdIp)) {
    echo json_encode(['success' => false, 'message' => 'These improvement plans have already been submitted.']);
    exit;
  }

  $db->beginTransaction();
  try {
    // Lock the draft rows for this cycle to prevent a double-submit race.
    $lockQ = $db->prepare("SELECT plan_id FROM improvement_plans WHERE cycle_id = ? AND workflow_status = ? FOR UPDATE");
    $lockQ->execute([$cycleIdIp, IP_STATUS_DRAFT]);
    $draftIds = $lockQ->fetchAll(PDO::FETCH_COLUMN);

    if (empty($draftIds)) {
      $db->rollBack();
      echo json_encode(['success' => false, 'message' => 'There are no draft improvement plans to submit.']);
      exit;
    }

    $upd = $db->prepare("UPDATE improvement_plans SET workflow_status = ?, submitted_by = ?, submitted_at = UTC_TIMESTAMP() WHERE cycle_id = ? AND workflow_status = ?");
    $upd->execute([IP_STATUS_SUBMITTED, $currentUserId, $cycleIdIp, IP_STATUS_DRAFT]);

    $db->commit();
    echo json_encode(['success' => true, 'count' => count($draftIds)]);
  } catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

$syId     = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());
$schoolId = SCHOOL_ID;

// -- Dimension scores (for the improvement plan's dimension picker) --
$stDimScores = $db->prepare("
  SELECT d.dimension_id, d.dimension_no, d.dimension_name, d.color_hex,
         ROUND(AVG(ds.percentage), 1) avg_pct
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds
    ON d.dimension_id = ds.dimension_id
    AND ds.cycle_id IN (
      SELECT c.cycle_id FROM sbm_cycles c
      WHERE c.sy_id = ?
        AND c.status IN ('validated','finalized','completed')
    )
  GROUP BY d.dimension_id
  ORDER BY d.dimension_no
");
$stDimScores->execute([$syId]);
$dimScores = $stDimScores->fetchAll();

// -- Weak indicators (rating <= 2.5) for the improvement plan's indicator picker --
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
$weakQ->execute([$syId, $schoolId]);
$weakIndicatorRows = $weakQ->fetchAll();

$cycle = null;
if ($schoolId) {
    $stmt = $db->prepare("SELECT c.*, sy.label sy_label FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND c.sy_id=? LIMIT 1");
    $stmt->execute([$schoolId, $syId]);
    $cycle = $stmt->fetch();
}

$overallScore  = $cycle['overall_score'] ?? null;
$maturityLevel = $cycle['maturity_level'] ?? null;
$mat           = $overallScore !== null ? sbmMaturityLevel(floatval($overallScore)) : ['label' => '—', 'color' => 'var(--n-400)'];
$generatedAt   = $cycle['updated_at'] ?? $cycle['created_at'] ?? null;
$syLabel       = $cycle['sy_label'] ?? '—';
$aiUsageStatus = aiUsageGetStatus($db, $currentUserId);

// ── Improvement Plans for this SY's cycle ───────────────────────
$currentCycleId = $cycle['cycle_id'] ?? null;
$planList = [];
if ($currentCycleId) {
    $planQ = $db->prepare("
        SELECT ip.*, d.dimension_no, d.dimension_name, d.color_hex,
               i.indicator_code, i.indicator_text,
               u.full_name AS submitted_by_name
        FROM improvement_plans ip
        JOIN sbm_dimensions d ON ip.dimension_id = d.dimension_id
        LEFT JOIN sbm_indicators i ON ip.indicator_id = i.indicator_id
        LEFT JOIN users u ON ip.submitted_by = u.user_id
        WHERE ip.cycle_id = ?
        ORDER BY FIELD(ip.priority_level,'High','Medium','Low'), ip.created_at
    ");
    $planQ->execute([$currentCycleId]);
    $planList = $planQ->fetchAll();
}

$isSubmitted     = false;
$submittedByName = null;
$submittedAt     = null;
foreach ($planList as $p) {
    if ($p['workflow_status'] === IP_STATUS_SUBMITTED) {
        $isSubmitted     = true;
        $submittedByName = $p['submitted_by_name'] ?? 'School Head';
        $submittedAt     = $p['submitted_at'];
        break; // one-and-done: all rows in a submitted batch share the same submitted_by/at
    }
}
$draftCount = count(array_filter($planList, fn($p) => $p['workflow_status'] === IP_STATUS_DRAFT));

// ── History of past improvement plans (shown when the new SY has no cycle/data yet) ──
$historyPlans = [];
if (!$cycle && $schoolId) {
    $histQ = $db->prepare("
        SELECT ip.*, d.dimension_no, d.dimension_name, d.color_hex,
               i.indicator_code, i.indicator_text,
               u.full_name AS submitted_by_name,
               sy.label AS sy_label
        FROM improvement_plans ip
        JOIN sbm_cycles c        ON ip.cycle_id = c.cycle_id
        JOIN school_years sy     ON c.sy_id = sy.sy_id
        JOIN sbm_dimensions d    ON ip.dimension_id = d.dimension_id
        LEFT JOIN sbm_indicators i ON ip.indicator_id = i.indicator_id
        LEFT JOIN users u        ON ip.submitted_by = u.user_id
        WHERE ip.school_id = ?
        ORDER BY sy.sy_id DESC, FIELD(ip.priority_level,'High','Medium','Low'), ip.created_at DESC
    ");
    $histQ->execute([$schoolId]);
    $historyPlans = $histQ->fetchAll();
}

$pageTitle = 'AI Suggestion Planning'; $activePage = 'ai_suggestion_planning.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head"></div>

<?php if ($cycle): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-body" style="padding:20px 24px;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">Overall SBM Score</div>
        <div style="font-size:26px;font-weight:700;color:<?= $mat['color'] ?>;line-height:1.2;">
          <?= $overallScore !== null ? number_format($overallScore, 1) . '%' : '—' ?>
        </div>
        <div style="font-size:13px;font-weight:600;color:<?= $mat['color'] ?>;margin-top:2px;">
          <?= e($mat['label']) ?>
        </div>
      </div>

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">Maturity Level</div>
        <div style="font-size:16px;font-weight:700;color:<?= $mat['color'] ?>;line-height:1.2;">
          <?= e(!empty($maturityLevel) ? $maturityLevel : $mat['label']) ?>
        </div>
      </div>

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">Generated On</div>
        <div style="font-size:14px;font-weight:600;color:var(--n-900);line-height:1.4;" id="generatedOnVal">
          —
        </div>
      </div>

      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:6px;">School Year</div>
        <div style="font-size:14px;font-weight:700;color:var(--n-900);line-height:1.2;">
          SY <?= e($syLabel) ?>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <span class="card-title">AI Suggestions</span>
    <div style="display:flex;align-items:center;gap:12px;">
      <span id="aiUsageRemainingLbl" style="font-size:12.5px;color:var(--n-500);">
        <?= (int)$aiUsageStatus['remaining'] ?> of <?= (int)$aiUsageStatus['limit'] ?> generations remaining
      </span>
      <button type="button" class="btn btn-primary" id="genAiSuggestBtn" onclick="loadAISuggestionsPlan()">
        Generate AI Suggestions
      </button>
    </div>
  </div>
  <div class="card-body ai-suggest-content" id="aiSuggestBody" style="padding:24px 28px;">
    <p style="font-size:13px;color:var(--n-500);">Click "Generate AI Suggestions" to analyze this cycle's data and get recommendations.</p>
  </div>
  <div id="aiUsageMsg" style="display:none;padding:10px 28px 18px 28px;font-size:12.5px;color:var(--red);"></div>
</div>

<?php if ($isSubmitted): ?>
<div class="card" style="margin-bottom:18px;border-color:var(--green-200, #bbf7d0);background:var(--green-50, #f0fdf4);">
  <div class="card-body" style="padding:20px 24px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
      <span style="font-size:20px;line-height:1;">✅</span>
      <span style="font-size:15px;font-weight:700;color:var(--n-900);">Improvement Plans Successfully Submitted</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:4px;">Status</div>
        <div style="font-size:14px;font-weight:700;color:var(--green-700, #15803d);">Submitted</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:4px;">Submitted By</div>
        <div style="font-size:14px;font-weight:700;color:var(--n-900);"><?= e($submittedByName ?? 'School Head') ?></div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--n-500);margin-bottom:4px;">Submitted On</div>
        <div style="font-size:14px;font-weight:700;color:var(--n-900);">
          <?= $submittedAt ? date('F j, Y', strtotime($submittedAt)) . '<br>' . date('g:i A', strtotime($submittedAt)) : '—' ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <span class="card-title">Improvement Plans<?= $isSubmitted ? ' <span style="font-weight:600;font-size:11.5px;color:var(--n-500);">(read-only — submitted)</span>' : '' ?></span>
    <div style="display:flex;align-items:center;gap:10px;">
      <?php if (!$isSubmitted): ?>
        <?php if ($draftCount > 0): ?>
          <button class="btn btn-secondary" onclick="openSubmitConfirm()">Submit Improvement Plans</button>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="manuallyAddImprovementPlan()">
          <svg style="width:16px;height:16px;vertical-align:-3px;margin-right:4px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Add Improvement Plan
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($planList)): ?>
    <div class="card-body" style="padding:20px 24px;">
      <p style="font-size:13px;color:var(--n-500);">Use the AI suggestions above as a starting point, then log a formal improvement plan here — dimension, indicator, objective, strategy, and target date.</p>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:70px;">Dimension</th>
            <th style="width:90px;">Indicator</th>
            <th>Objective</th>
            <th style="width:90px;">Priority</th>
            <th style="width:110px;">Target Date</th>
            <th style="width:100px;">Status</th>
            <th style="width:150px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($planList as $p): ?>
            <tr>
              <td><strong style="font-size:12px;color:<?= e($p['color_hex'] ?? 'var(--n-600)') ?>;">D<?= (int)$p['dimension_no'] ?></strong></td>
              <td style="font-size:12px;color:var(--n-600);"><?= e($p['indicator_code'] ?? '—') ?></td>
              <td style="font-size:12.5px;line-height:1.5;"><?= e($p['objective']) ?></td>
              <td>
                <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;
                  background:<?= $p['priority_level']==='High' ? '#fee2e2' : ($p['priority_level']==='Low' ? '#f1f5f9' : '#fef3c7') ?>;
                  color:<?= $p['priority_level']==='High' ? '#dc2626' : ($p['priority_level']==='Low' ? '#64748b' : '#b45309') ?>;">
                  <?= e($p['priority_level']) ?>
                </span>
              </td>
              <td style="font-size:12.5px;color:var(--n-600);"><?= $p['target_date'] ? date('M j, Y', strtotime($p['target_date'])) : '—' ?></td>
              <td>
                <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;
                  background:<?= $p['workflow_status']===IP_STATUS_SUBMITTED ? '#dcfce7' : '#f1f5f9' ?>;
                  color:<?= $p['workflow_status']===IP_STATUS_SUBMITTED ? '#15803d' : '#64748b' ?>;">
                  <?= e(ucfirst($p['workflow_status'])) ?>
                </span>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:nowrap;white-space:nowrap;">
                  <button class="ip-icon-btn" title="Preview" onclick='openViewPlan(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <?php if (!$isSubmitted): ?>
                    <button class="ip-icon-btn" title="Edit" onclick='openEditPlan(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="ip-icon-btn ip-icon-btn-danger" title="Delete" onclick="deletePlan(<?= (int)$p['plan_id'] ?>)">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php else: ?>

<div class="card" style="margin-bottom:18px;">
  <div class="card-body" style="padding:20px 24px;">
    <p style="font-size:13px;color:var(--n-500);">
      A new school year has started and no self-assessment cycle has begun yet. Once the cycle is underway, AI suggestions and the improvement plan tools will appear here. In the meantime, you can review your school's past improvement plans below.
    </p>
  </div>
</div>

<div class="card" style="margin-bottom:18px;">
  <div class="card-head">
    <span class="card-title">Improvement Plan History</span>
  </div>
  <?php if (empty($historyPlans)): ?>
    <div class="card-body" style="padding:20px 24px;">
      <p style="font-size:13px;color:var(--n-500);">No past improvement plans found for this school.</p>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:80px;">School Year</th>
            <th style="width:70px;">Dimension</th>
            <th style="width:90px;">Indicator</th>
            <th>Objective</th>
            <th style="width:90px;">Priority</th>
            <th style="width:110px;">Target Date</th>
            <th style="width:100px;">Status</th>
            <th style="width:70px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($historyPlans as $p): ?>
            <tr>
              <td style="font-size:12px;color:var(--n-600);">SY <?= e($p['sy_label']) ?></td>
              <td><strong style="font-size:12px;color:<?= e($p['color_hex'] ?? 'var(--n-600)') ?>;">D<?= (int)$p['dimension_no'] ?></strong></td>
              <td style="font-size:12px;color:var(--n-600);"><?= e($p['indicator_code'] ?? '—') ?></td>
              <td style="font-size:12.5px;line-height:1.5;"><?= e($p['objective']) ?></td>
              <td>
                <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;
                  background:<?= $p['priority_level']==='High' ? '#fee2e2' : ($p['priority_level']==='Low' ? '#f1f5f9' : '#fef3c7') ?>;
                  color:<?= $p['priority_level']==='High' ? '#dc2626' : ($p['priority_level']==='Low' ? '#64748b' : '#b45309') ?>;">
                  <?= e($p['priority_level']) ?>
                </span>
              </td>
              <td style="font-size:12.5px;color:var(--n-600);"><?= $p['target_date'] ? date('M j, Y', strtotime($p['target_date'])) : '—' ?></td>
              <td>
                <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;
                  background:<?= $p['workflow_status']===IP_STATUS_SUBMITTED ? '#dcfce7' : '#f1f5f9' ?>;
                  color:<?= $p['workflow_status']===IP_STATUS_SUBMITTED ? '#15803d' : '#64748b' ?>;">
                  <?= e(ucfirst($p['workflow_status'])) ?>
                </span>
              </td>
              <td>
                <button class="ip-icon-btn" title="Preview" onclick='openViewPlan(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── SUBMIT CONFIRMATION MODAL ── -->
<div id="submitConfirmModal" class="modal-overlay">
  <div class="modal-content" style="width:440px;">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title">Submit Improvement Plans?</div>
        <button class="btn btn-ghost" style="padding:4px;" onclick="closeSubmitConfirm()">&times;</button>
      </div>
      <div class="modal-body">
        <p style="font-size:13.5px;color:var(--n-700);line-height:1.6;">
          After submission, your improvement plans will be finalized and cannot be edited unless they are returned for revision in the future.
        </p>
      </div>
      <div class="modal-footer">
        <div style="flex:1"></div>
        <button class="btn-secondary" onclick="closeSubmitConfirm()">Cancel</button>
        <button class="btn-primary" id="confirmSubmitBtn" onclick="confirmSubmitPlans()">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- ── EDIT IMPROVEMENT PLAN MODAL ── -->
<div id="editPlanModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title">Edit Improvement Plan</div>
        <button class="btn btn-ghost" style="padding:4px;" onclick="closeEditPlanModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editPlanForm">
          <input type="hidden" id="editPlanId">
          <div class="form-group">
            <label>Dimension / Indicator</label>
            <div id="editPlanBadge" style="font-size:12.5px;color:var(--n-600);padding:8px 12px;background:var(--n-50);border-radius:8px;"></div>
          </div>
          <div class="grid2">
            <div class="form-group">
              <label>Priority</label>
              <select id="editPriorityLevel" class="form-control">
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
              </select>
            </div>
            <div class="form-group">
              <label>Target Date</label>
              <input type="date" id="editTargetDate" class="form-control">
            </div>
          </div>
          <div class="form-group">
            <label>Objective</label>
            <textarea id="editObjective" class="form-control autosize" rows="2" required></textarea>
          </div>
          <div class="form-group">
            <label>Strategy</label>
            <textarea id="editStrategy" class="form-control autosize" rows="2" required></textarea>
          </div>
          <div class="form-group">
            <label>Person Responsible</label>
            <input type="text" id="editPersonResponsible" class="form-control">
          </div>
          <div class="grid2">
            <div class="form-group">
              <label>Resources Needed</label>
              <textarea id="editResourcesNeeded" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Expected Output</label>
              <textarea id="editExpectedOutput" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <div style="flex:1"></div>
        <button class="btn-secondary" onclick="closeEditPlanModal()">Cancel</button>
        <button class="btn-primary" onclick="saveEditPlan(event)">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- ── VIEW (PREVIEW) IMPROVEMENT PLAN MODAL — read-only ── -->
<div id="viewPlanModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title">Improvement Plan</div>
        <button class="btn btn-ghost" style="padding:4px;" onclick="closeViewPlanModal()">&times;</button>
      </div>
      <div class="modal-body" id="viewPlanBody" style="font-size:13.5px;line-height:1.7;color:var(--n-800);">
        <!-- Populated via JS -->
      </div>
      <div class="modal-footer">
        <div style="flex:1"></div>
        <button class="btn-secondary" onclick="closeViewPlanModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ── IMPROVEMENT PLAN MODAL ── -->
<div id="improvementPlanModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title">Add Improvement Plan</div>
        <button class="btn btn-ghost" style="padding:4px;" onclick="closeImprovementPlanModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="improvementPlanForm">
          <div class="form-group">
            <label>Dimension(s)</label>
            <div class="tag-select-container" id="dimTagContainer">
              <input type="text" class="tag-input-ghost" id="dimTagInput" placeholder="Select Dimensions...">
              <div class="tag-dropdown" id="dimTagDropdown">
                <?php foreach ($dimScores as $d): ?>
                  <div class="tag-option" data-id="<?= $d['dimension_id'] ?>" data-name="D<?= $d['dimension_no'] ?>">
                    D<?= $d['dimension_no'] ?> - <?= e($d['dimension_name']) ?> (<?= $d['avg_pct'] ?>%)
                    <?= ($d['avg_pct'] < 50) ? '⚠️' : '' ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <input type="hidden" name="dimension_ids" id="dimIdsInput" required>
          </div>

          <div class="form-group">
            <label>Indicator(s) <small style="color:var(--n-500);">(Only indicators with rating &le; 2.5
                available)</small></label>
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
            <div class="field-header">
              <label>Objective</label>
              <button type="button" class="ai-suggest-link" id="genObjectiveBtn" onclick="generateIpField('objective')" disabled>Suggest</button>
            </div>
            <textarea name="objective" class="form-control autosize" rows="2" placeholder="What do you want to achieve?"
              required></textarea>
            <div class="ai-field-status" id="objectiveAiStatus"></div>
          </div>
          <div class="form-group">
            <div class="field-header">
              <label>Strategy</label>
              <button type="button" class="ai-suggest-link" id="genStrategyBtn" onclick="generateIpField('strategy')" disabled>Suggest</button>
            </div>
            <textarea name="strategy" class="form-control autosize" rows="2" placeholder="How will you achieve it?"
              required></textarea>
            <div class="ai-field-status" id="strategyAiStatus"></div>
          </div>
          <div class="form-group">
            <label>Person Responsible</label>
            <input type="text" name="person_responsible" class="form-control"
              placeholder="E.g. Principal, Grade Level Head">
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
        <div class="ip-ai-usage-note" id="ipAiUsageNote" style="flex:1"></div>
        <button class="btn-secondary" onclick="closeImprovementPlanModal()">Cancel</button>
        <button class="btn-primary" onclick="saveImprovementPlan(event)">Save Improvement Plan</button>
      </div>
    </div>
  </div>
</div>

<style>
.ai-suggest-content {
  font-family: var(--font-sans, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
  font-size: 14px;
  line-height: 1.75;
  color: var(--n-800);
}
.ai-suggest-content p {
  margin: 0 0 14px 0;
}
.ai-suggest-content p:last-child {
  margin-bottom: 0;
}
.ai-suggest-content strong {
  color: var(--n-900);
  font-weight: 700;
}
.ai-suggest-content ul {
  margin: 0 0 16px 0;
  padding-left: 22px;
}
.ai-suggest-content li {
  margin-bottom: 8px;
  padding-left: 4px;
}
.ai-suggest-content li:last-child {
  margin-bottom: 0;
}
.ai-suggest-content hr {
  border: none;
  border-top: 1px solid var(--n-200);
  margin: 20px 0;
}
.ai-suggest-content .ai-section-head {
  margin: 26px 0 8px 0;
}
.ai-suggest-content .ai-section-head:first-child {
  margin-top: 0;
}
.ai-suggest-content .ai-section-head h4 {
  margin: 0;
  font-size: 15.5px;
  font-weight: 700;
  color: var(--n-900);
}
.ai-suggest-content ul {
  list-style: none;
  padding-left: 32px;
}
.ai-suggest-content ul li {
  position: relative;
}
.ai-suggest-content ul li::before {
  content: "";
  position: absolute;
  left: -16px;
  top: 9px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--n-400);
}
.ai-suggest-content .ai-closing-note {
  margin-top: 22px;
  padding-top: 16px;
  border-top: 1px solid var(--n-200);
  color: var(--n-700);
}

.ip-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: 1px solid var(--n-200);
  border-radius: 6px;
  background: #fff;
  color: var(--n-600);
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.ip-icon-btn:hover {
  background: var(--n-50);
  color: var(--n-900);
  border-color: var(--n-300);
}
.ip-icon-btn-danger:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}

.field-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}
.field-header label {
  margin-bottom: 0 !important;
}
.ai-suggest-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 12.5px;
  font-weight: 500;
  color: #6B7280;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.2s ease, text-decoration-color 0.2s ease;
}
.ai-suggest-link:hover:not(:disabled) {
  color: var(--green-700, #15803d);
}
.ai-suggest-link:disabled {
  color: var(--n-300, #cbd5e1);
  cursor: not-allowed;
  text-decoration: none;
}
.ai-field-status {
  font-size: 11px;
  color: #dc2626;
  margin-top: 4px;
  min-height: 0;
}
.ip-ai-usage-note {
  font-size: 11px;
  color: var(--n-400, #94a3b8);
}

/* ── Improvement Plan Modal (moved from dashboard.php) ── */
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
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-form-side {
  flex: 1;
  background: #fff;
  display: flex;
  flex-direction: column;
  max-height: 85vh;
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

.modal-content .form-group {
  margin-bottom: 16px;
  position: relative;
  overflow: visible !important;
}

.modal-content .form-group label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--n-600);
  margin-bottom: 6px;
}

.modal-content .form-control {
  width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid var(--n-300);
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
  font-family: inherit;
  resize: vertical;
}

.modal-content .form-control:focus {
  border-color: var(--n-600);
}

.modal-content textarea.form-control.autosize {
  resize: none;
  overflow: hidden;
}

.modal-content .grid2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.modal-content .btn-primary {
  background: var(--n-900);
  color: #fff;
  border: none;
  padding: 8px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.modal-content .btn-secondary {
  background: #fff;
  border: 1px solid var(--n-300);
  color: var(--n-700);
  padding: 8px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* -- TAG MULTI-SELECT STYLES -- */
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
</style>

<script>
/** Renders AI text as a formal, structured report (Claude-style formatting) */
function parseAILogicToHtml(text) {
  const lines = text.split('\n');
  let finalHtml = '';
  let inList = false;
  let sectionNo = 0;

  const inlineFormat = (s) => s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

  lines.forEach(line => {
    const trimmed = line.trim();

    if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
      if (!inList) { finalHtml += '<ul>'; inList = true; }
      finalHtml += '<li>' + inlineFormat(trimmed.substring(2)) + '</li>';
      return;
    }

    if (inList) { finalHtml += '</ul>'; inList = false; }

    if (trimmed === '---') {
      finalHtml += '<hr>';
      return;
    }

    if (!trimmed) return;

    // Paragraphs that open with a **Bold Heading** become a numbered section header
    const headingMatch = trimmed.match(/^\*\*(.+?)\*\*(.*)$/);
    if (headingMatch) {
      finalHtml += '<div class="ai-section-head"><h4>' + headingMatch[1] + '</h4></div>';
      const rest = headingMatch[2].trim();
      if (rest) finalHtml += '<p>' + inlineFormat(rest) + '</p>';
      return;
    }

    finalHtml += '<p>' + inlineFormat(trimmed) + '</p>';
  });

  if (inList) finalHtml += '</ul>';
  return finalHtml;
}

// ── AUTO-GROW TEXTAREAS (Objective / Strategy) ────────────────────
function autoGrowTextarea(el) {
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}

function wireAutoGrow(el) {
  if (!el || el.dataset.autoGrowWired) return;
  el.dataset.autoGrowWired = '1';
  el.addEventListener('input', () => autoGrowTextarea(el));
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-content textarea.autosize').forEach(el => {
    wireAutoGrow(el);
  });
});

function stripTrailingQuestion(text) {
  const lines = text.split('\n');
  while (lines.length && /\?\s*$/.test(lines[lines.length - 1].trim())) {
    lines.pop();
  }
  return lines.join('\n').trim();
}

// ── IMPROVEMENT PLAN: AI-ASSISTED OBJECTIVE/STRATEGY ─────────────
// Holds the raw AI Suggestions text (not the HTML) so we can extract
// the exact section that matches the selected indicator.
let currentAiRawText = '';

/** Splits the raw AI text into blocks at each bold header, then returns
 *  the ONE block that mentions the given indicator code — or null. */
function extractIndicatorSnippet(rawText, indicatorCode) {
  if (!rawText || !indicatorCode) return null;

  const lines = rawText.split('\n');
  const blocks = [];
  let current = [];

  lines.forEach(line => {
    const isHeader = /^\*\*(.+?)\*\*/.test(line.trim());
    if (isHeader && current.length) {
      blocks.push(current.join('\n'));
      current = [line];
    } else {
      current.push(line);
    }
  });
  if (current.length) blocks.push(current.join('\n'));

  const escaped = indicatorCode.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const codeRegex = new RegExp('\\b' + escaped + '\\b');

  const match = blocks.find(b => codeRegex.test(b));
  return match ? match.trim() : null;
}

/** Returns { indicator_id, indicator_code, indicator_text, dimension_name }
 *  only when exactly ONE indicator is currently selected. */
function getSelectedIndicatorMeta() {
  const ids = (document.getElementById('indIdsInput').value || '').split(',').filter(Boolean);
  if (ids.length !== 1) return null;
  return weakIndicatorsBase.find(wi => String(wi.indicator_id) === ids[0]) || null;
}

// ── IP FIELD (Objective/Strategy) usage state — 3 generations each per day ──
const ipFieldLimit = 3;
let ipFieldRemaining = { objective: ipFieldLimit, strategy: ipFieldLimit };

function ipFieldLimitReached(fieldType) {
  return ipFieldRemaining[fieldType] <= 0;
}

async function initIpFieldUsageState() {
  const formData = new FormData();
  formData.append('action', 'get_ip_field_usage_status');

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();
    ipFieldRemaining.objective = data.objective?.remaining ?? ipFieldLimit;
    ipFieldRemaining.strategy = data.strategy?.remaining ?? ipFieldLimit;
    updateAiFieldButtons();
  } catch (err) {
    console.error('Failed to load IP field usage status', err);
  }
}
document.addEventListener('DOMContentLoaded', initIpFieldUsageState);

/** Enables/disables the header "💡 Suggest" links based on selection
 *  AND remaining daily quota (3 objective / 3 strategy). Also refreshes
 *  the single usage note shown near the modal footer. */
function updateAiFieldButtons() {
  const dimSelected = !!(document.getElementById('dimIdsInput').value);
  const ids = (document.getElementById('indIdsInput').value || '').split(',').filter(Boolean);
  const meta = getSelectedIndicatorMeta();

  const objBtn = document.getElementById('genObjectiveBtn');
  const stratBtn = document.getElementById('genStrategyBtn');
  const selectionReady = dimSelected && ids.length === 1 && !!meta;

  let hint = '';
  if (!dimSelected || ids.length === 0) {
    hint = 'Select a Dimension and Indicator to enable AI suggestions.';
  } else if (ids.length > 1) {
    hint = 'Select a single Indicator to use AI suggestions.';
  }

  objBtn.disabled = !selectionReady || ipFieldLimitReached('objective');
  stratBtn.disabled = !selectionReady || ipFieldLimitReached('strategy');

  objBtn.title = !selectionReady ? hint : (ipFieldLimitReached('objective') ? "Today's objective-suggestion limit reached." : '');
  stratBtn.title = !selectionReady ? hint : (ipFieldLimitReached('strategy') ? "Today's strategy-suggestion limit reached." : '');

  updateIpAiUsageNote();
}

function updateIpAiUsageNote() {
  const note = document.getElementById('ipAiUsageNote');
  if (!note) return;
  note.textContent =
    'AI suggestions left today — Objective: ' + ipFieldRemaining.objective + '/' + ipFieldLimit +
    ' · Strategy: ' + ipFieldRemaining.strategy + '/' + ipFieldLimit;
}

async function generateIpField(fieldType) {
  const meta = getSelectedIndicatorMeta();
  if (!meta) return;

  const btn = document.getElementById(fieldType === 'objective' ? 'genObjectiveBtn' : 'genStrategyBtn');
  const statusEl = document.getElementById(fieldType + 'AiStatus');
  const textarea = document.querySelector(`#improvementPlanForm textarea[name="${fieldType}"]`);

  const snippet = extractIndicatorSnippet(currentAiRawText, meta.indicator_code);
  if (!snippet) {
    statusEl.textContent = 'No AI suggestion found for this indicator yet — generate AI Suggestions above first.';
    return;
  }

  const originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Generating…';
  statusEl.textContent = '';

  const formData = new FormData();
  formData.append('action', 'generate_ip_field');
  formData.append('field_type', fieldType);
  formData.append('indicator_code', meta.indicator_code);
  formData.append('indicator_text', meta.indicator_text);
  formData.append('dimension_name', meta.dimension_name);
  formData.append('snippet', snippet);

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();

    if (typeof data.remaining === 'number') {
      ipFieldRemaining[fieldType] = data.remaining;
    }

    if (data.success && data.text) {
      textarea.value = data.text.trim();
      autoGrowTextarea(textarea);
    } else {
      statusEl.textContent = data.message || 'Could not generate content. Please try again.';
    }
    updateAiFieldButtons();
  } catch (err) {
    console.error(err);
    statusEl.textContent = 'Network error. Please try again.';
  } finally {
    btn.textContent = originalLabel;
    updateAiFieldButtons();
  }
}

// ── IMPROVEMENT PLAN: TAG MULTI-SELECT COMPONENT (moved from dashboard.php) ──
const weakIndicatorsBase = <?= json_encode($weakIndicatorRows) ?>;
let dimTagControl, indTagControl;

function initTagSelect(prefix, options, onUpdate) {
  const container = document.getElementById(prefix + 'TagContainer');
  const input = document.getElementById(prefix + 'TagInput');
  const dropdown = document.getElementById(prefix + 'TagDropdown');
  const hidden = document.getElementById(prefix + 'IdsInput');

  let selected = [];

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
    container.querySelectorAll('.tag-pill').forEach(tp => tp.remove());
    selected.forEach(id => {
      const item = options.find(o => o.id == id);
      if (!item) return;

      const tag = document.createElement('div');
      tag.className = 'tag-pill';
      tag.innerHTML = `${item.name || item.label} <i onclick="window['removeTag${prefix}']('${id}', event)">&times;</i>`;
      container.insertBefore(tag, input);
    });
    hidden.value = selected.join(',');

    const opts = dropdown.querySelectorAll('.tag-option');
    let visibleCount = 0;
    opts.forEach(opt => {
      const id = opt.getAttribute('data-id');
      const isSel = selected.includes(id);
      opt.classList.toggle('selected', isSel);
      if (!isSel && !opt.classList.contains('hidden')) visibleCount++;
    });

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

  const toggleDropdown = (show) => {
    const isCurrentlyOpen = dropdown.style.display === 'block';
    const open = (show === undefined) ? !isCurrentlyOpen : show;
    dropdown.style.display = open ? 'block' : 'none';
    chevronBtn.style.transform = open ? 'rotate(180deg)' : 'rotate(0)';
  };

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

  const wireOptions = (div) => {
    div.onclick = (e) => {
      e.stopPropagation();
      const id = div.getAttribute('data-id');
      if (!selected.includes(id)) {
        selected.push(id);
        input.value = '';
        dropdown.querySelectorAll('.tag-option').forEach(o => o.classList.remove('hidden'));
        render();
      }
    };
  }

  dropdown.querySelectorAll('.tag-option').forEach(wireOptions);

  window['removeTag' + prefix] = (id, event) => {
    if (event) event.stopPropagation();
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
    if (indTagControl) indTagControl.setOptions(filteredIndicators);
    updateAiFieldButtons();
  });

  indTagControl = initTagSelect('ind', [], () => updateAiFieldButtons());
});

function manuallyAddImprovementPlan() {
  document.getElementById('improvementPlanModal').style.display = 'flex';
  autoGrowTextarea(document.querySelector('#improvementPlanForm textarea[name="objective"]'));
  autoGrowTextarea(document.querySelector('#improvementPlanForm textarea[name="strategy"]'));
}

function closeImprovementPlanModal() {
  document.getElementById('improvementPlanModal').style.display = 'none';
  if (dimTagControl) dimTagControl.reset();
  if (indTagControl) indTagControl.reset();
  document.getElementById('improvementPlanForm').reset();
  document.querySelectorAll('#improvementPlanForm textarea.autosize').forEach(el => { el.style.height = ''; });
  updateAiFieldButtons();
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
  formData.append('sy_id', '<?= $syId ?>');

  const btn = e.target;
  btn.disabled = true;
  btn.textContent = 'Saving...';

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
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

// ── SUBMIT CONFIRMATION ──────────────────────────────────────────
function openSubmitConfirm() {
  document.getElementById('submitConfirmModal').style.display = 'flex';
}

function closeSubmitConfirm() {
  document.getElementById('submitConfirmModal').style.display = 'none';
}

async function confirmSubmitPlans() {
  const btn = document.getElementById('confirmSubmitBtn');
  btn.disabled = true;
  btn.textContent = 'Submitting...';

  const formData = new FormData();
  formData.append('action', 'submit_improvement_plans');
  formData.append('sy_id', '<?= $syId ?>');

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      closeSubmitConfirm();
      location.reload();
    } else {
      alert('Error: ' + data.message);
      btn.disabled = false;
      btn.textContent = 'Submit';
    }
  } catch (err) {
    console.error(err);
    alert('Network error. Failed to submit.');
    btn.disabled = false;
    btn.textContent = 'Submit';
  }
}

// ── EDIT IMPROVEMENT PLAN ────────────────────────────────────────
function openEditPlan(plan) {
  document.getElementById('editPlanId').value = plan.plan_id;
  document.getElementById('editPlanBadge').textContent =
    'D' + plan.dimension_no + ' — ' + plan.dimension_name +
    (plan.indicator_code ? ' · ' + plan.indicator_code : '');
  document.getElementById('editPriorityLevel').value = plan.priority_level || 'Medium';
  document.getElementById('editTargetDate').value = plan.target_date || '';
  document.getElementById('editObjective').value = plan.objective || '';
  document.getElementById('editStrategy').value = plan.strategy || '';
  document.getElementById('editPersonResponsible').value = plan.person_responsible || '';
  document.getElementById('editResourcesNeeded').value = plan.resources_needed || '';
  document.getElementById('editExpectedOutput').value = plan.expected_output || '';
  document.getElementById('editPlanModal').style.display = 'flex';
  autoGrowTextarea(document.getElementById('editObjective'));
  autoGrowTextarea(document.getElementById('editStrategy'));
}

function closeEditPlanModal() {
  document.getElementById('editPlanModal').style.display = 'none';
  document.getElementById('editPlanForm').reset();
}

async function saveEditPlan(e) {
  if (e) e.preventDefault();
  const form = document.getElementById('editPlanForm');
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData();
  formData.append('action', 'update_improvement_plan');
  formData.append('plan_id', document.getElementById('editPlanId').value);
  formData.append('priority_level', document.getElementById('editPriorityLevel').value);
  formData.append('target_date', document.getElementById('editTargetDate').value);
  formData.append('objective', document.getElementById('editObjective').value);
  formData.append('strategy', document.getElementById('editStrategy').value);
  formData.append('person_responsible', document.getElementById('editPersonResponsible').value);
  formData.append('resources_needed', document.getElementById('editResourcesNeeded').value);
  formData.append('expected_output', document.getElementById('editExpectedOutput').value);

  const btn = e.target;
  btn.disabled = true;
  btn.textContent = 'Saving...';

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      closeEditPlanModal();
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  } catch (err) {
    console.error(err);
    alert('Network error. Failed to save.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Changes';
  }
}

// ── VIEW (PREVIEW) IMPROVEMENT PLAN — read-only ─────────────────
function ipEscape(str) {
  const div = document.createElement('div');
  div.textContent = str || '';
  return div.innerHTML;
}

function ipViewRow(label, value) {
  return `<div style="margin-bottom:14px;">
    <div style="font-size:11.5px;font-weight:600;color:var(--n-500);text-transform:uppercase;letter-spacing:.03em;margin-bottom:4px;">${label}</div>
    <div>${value || '<span style="color:var(--n-400);">—</span>'}</div>
  </div>`;
}

function openViewPlan(plan) {
  const dimLabel = 'D' + plan.dimension_no + ' — ' + plan.dimension_name + (plan.indicator_code ? ' · ' + plan.indicator_code : '');
  const targetDate = plan.target_date
    ? new Date(plan.target_date + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    : null;

  document.getElementById('viewPlanBody').innerHTML =
    ipViewRow('Dimension / Indicator', ipEscape(dimLabel)) +
    ipViewRow('Priority', ipEscape(plan.priority_level)) +
    ipViewRow('Target Date', targetDate ? ipEscape(targetDate) : null) +
    ipViewRow('Objective', ipEscape(plan.objective)) +
    ipViewRow('Strategy', ipEscape(plan.strategy)) +
    ipViewRow('Person Responsible', ipEscape(plan.person_responsible)) +
    ipViewRow('Resources Needed', ipEscape(plan.resources_needed)) +
    ipViewRow('Expected Output', ipEscape(plan.expected_output)) +
    ipViewRow('Status', ipEscape(plan.workflow_status ? plan.workflow_status.charAt(0).toUpperCase() + plan.workflow_status.slice(1) : null));

  document.getElementById('viewPlanModal').style.display = 'flex';
}

function closeViewPlanModal() {
  document.getElementById('viewPlanModal').style.display = 'none';
}

// ── DELETE IMPROVEMENT PLAN ──────────────────────────────────────
async function deletePlan(planId) {
  if (!confirm('Delete this improvement plan? This cannot be undone.')) return;

  const formData = new FormData();
  formData.append('action', 'delete_improvement_plan');
  formData.append('plan_id', planId);

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  } catch (err) {
    console.error(err);
    alert('Network error. Failed to delete.');
  }
}

// ── AI usage/cooldown UI state (server is the source of truth) ──
const AI_COOLDOWN_SECONDS = 20;
let aiRemaining = <?= (int)$aiUsageStatus['remaining'] ?>;
const aiLimit = <?= (int)$aiUsageStatus['limit'] ?>;
let aiCooldownTimer = null;

function updateAiUsageLabel() {
  document.getElementById('aiUsageRemainingLbl').textContent = `${aiRemaining} of ${aiLimit} generations remaining`;
}

function showAiUsageMsg(msg) {
  const el = document.getElementById('aiUsageMsg');
  if (!msg) { el.style.display = 'none'; el.textContent = ''; return; }
  el.style.display = 'block';
  el.textContent = msg;
}

function setGenerateBtnState(disabled, label) {
  const btn = document.getElementById('genAiSuggestBtn');
  btn.disabled = disabled;
  btn.textContent = label;
}

function startAiCooldown(seconds) {
  if (aiCooldownTimer) clearInterval(aiCooldownTimer);
  let remaining = seconds;

  const tick = () => {
    if (remaining <= 0) {
      clearInterval(aiCooldownTimer);
      aiCooldownTimer = null;
      if (aiRemaining > 0) {
        setGenerateBtnState(false, 'Generate AI Suggestions');
        showAiUsageMsg('');
      } else {
        setGenerateBtnState(true, 'Daily limit reached');
        showAiUsageMsg("You have reached today's generation limit. Please try again tomorrow.");
      }
      return;
    }
    setGenerateBtnState(true, `Generate again in ${remaining}s`);
    remaining--;
  };

  tick();
  aiCooldownTimer = setInterval(tick, 1000);
}

function renderStoredRecommendation(recs, lastGeneratedAtUtc) {
  const body = document.getElementById('aiSuggestBody');
  const cleaned = stripTrailingQuestion(recs);
  currentAiRawText = cleaned;
  body.innerHTML = cleaned
    ? parseAILogicToHtml(cleaned)
    : '<p style="font-size:13px;color:var(--n-500);">Your performance is currently optimal with no critical gaps detected.</p>';

  if (lastGeneratedAtUtc) {
    const d = new Date(lastGeneratedAtUtc.replace(' ', 'T') + 'Z');
    const dateStr = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    const timeStr = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    document.getElementById('generatedOnVal').innerHTML = dateStr + '<br>' + timeStr;
  }
}

async function initAiUsageState() {
  const formData = new FormData();
  formData.append('action', 'get_ai_usage_status');

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();

    aiRemaining = data.remaining;
    updateAiUsageLabel();

    if (data.last_recommendation) {
      renderStoredRecommendation(data.last_recommendation, data.last_generated_at);
    }

    if (data.remaining <= 0) {
      setGenerateBtnState(true, 'Daily limit reached');
      showAiUsageMsg("You have reached today's generation limit. Please try again tomorrow.");
    } else if (data.cooldown_remaining > 0) {
      startAiCooldown(data.cooldown_remaining);
    }
  } catch (err) {
    console.error('Failed to load AI usage status', err);
  }
}
document.addEventListener('DOMContentLoaded', initAiUsageState);

async function loadAISuggestionsPlan() {
  const body = document.getElementById('aiSuggestBody');
  const btn = document.getElementById('genAiSuggestBtn');

  if (btn.disabled) return;

  btn.disabled = true;
  btn.textContent = 'Generating...';
  showAiUsageMsg('');
  body.innerHTML = '<p style="font-size:13px;color:var(--n-500);">Analyzing your SBM data...</p>';

  const formData = new FormData();
  formData.append('action', 'get_ai_suggestions');
  formData.append('sy_id', '<?= $syId ?>');

  try {
    const res = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await res.json();

    if (res.status === 429) {
      aiRemaining = data.remaining ?? aiRemaining;
      updateAiUsageLabel();
      showAiUsageMsg(data.message || 'Please try again later.');
      body.innerHTML = '<p style="font-size:13px;color:var(--n-500);">Click "Generate AI Suggestions" to analyze this cycle\'s data and get recommendations.</p>';

      if (data.error === 'daily_limit') {
        setGenerateBtnState(true, 'Daily limit reached');
      } else {
        startAiCooldown(data.retry_after || AI_COOLDOWN_SECONDS);
      }
      return;
    }

    if (data.error) {
      body.innerHTML = '<p style="font-size:13px;color:var(--red);">Couldn\'t reach the analysis service. Please check if the ML service is running.</p>';
      setGenerateBtnState(false, 'Generate AI Suggestions');
      return;
    }

    const recs = stripTrailingQuestion(data.recommendations || '');
    currentAiRawText = recs;
    body.innerHTML = recs
      ? parseAILogicToHtml(recs)
      : '<p style="font-size:13px;color:var(--n-500);">Your performance is currently optimal with no critical gaps detected.</p>';

    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    document.getElementById('generatedOnVal').innerHTML = dateStr + '<br>' + timeStr;

    aiRemaining = typeof data.remaining === 'number' ? data.remaining : aiRemaining - 1;
    updateAiUsageLabel();
    startAiCooldown(AI_COOLDOWN_SECONDS);
  } catch (err) {
    console.error(err);
    body.innerHTML = '<p style="font-size:13px;color:var(--red);">Network error. Please try again.</p>';
    setGenerateBtnState(false, 'Generate AI Suggestions');
  }
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>