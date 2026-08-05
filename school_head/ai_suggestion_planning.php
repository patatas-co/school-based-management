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

// ── SAVE IMPROVEMENT PLAN AJAX HANDLER (moved from school_head/dashboard.php) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_improvement_plan') {
  header('Content-Type: application/json');
  $schoolIdIp = (int) ($_SESSION['school_id'] ?? 1);
  $syIdIp = (int) ($_POST['sy_id'] ?? 0);
  $indIds = explode(',', $_POST['indicator_ids'] ?? '');
  $obj = $_POST['objective'] ?? '';
  $strat = $_POST['strategy'] ?? '';
  $person = $_POST['person_responsible'] ?? '';
  $target = $_POST['target_date'] ?? null;
  $res = $_POST['resources_needed'] ?? '';
  $output = $_POST['expected_output'] ?? '';
  $priority = $_POST['priority_level'] ?? 'Medium';

  $cQ = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id = ? AND sy_id = ? ORDER BY created_at DESC LIMIT 1");
  $cQ->execute([$schoolIdIp, $syIdIp]);
  $cycleIdIp = $cQ->fetchColumn();

  if (!$cycleIdIp) {
    echo json_encode(['success' => false, 'message' => 'No assessment cycle found for this year. Please create one first.']);
    exit;
  }

  try {
    $ins = $db->prepare("INSERT INTO improvement_plans (school_id, cycle_id, dimension_id, indicator_id, priority_level, objective, strategy, person_responsible, target_date, resources_needed, expected_output, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($indIds as $indId) {
      if (!$indId)
        continue;
      $dimQ = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id = ?");
      $dimQ->execute([$indId]);
      $dId = $dimQ->fetchColumn();

      $ins->execute([$schoolIdIp, $cycleIdIp, $dId, $indId, $priority, $obj, $strat, $person, $target ?: null, $res, $output, $currentUserId]);
    }
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
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

$pageTitle = 'AI Suggestion Planning'; $activePage = 'ai_suggestion_planning.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head"></div>

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

<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
    <span class="card-title">Improvement Plans</span>
    <button class="btn btn-primary" onclick="manuallyAddImprovementPlan()">
      <svg style="width:16px;height:16px;vertical-align:-3px;margin-right:4px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="16" />
        <line x1="8" y1="12" x2="16" y2="12" />
      </svg>
      Add Improvement Plan
    </button>
  </div>
  <div class="card-body" style="padding:20px 24px;">
    <p style="font-size:13px;color:var(--n-500);">Use the AI suggestions above as a starting point, then log a formal improvement plan here — dimension, indicator, objective, strategy, and target date.</p>
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
        <div style="flex:1"></div>
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

#improvementPlanModal .form-group {
  margin-bottom: 16px;
  position: relative;
  overflow: visible !important;
}

#improvementPlanModal .form-group label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--n-600);
  margin-bottom: 6px;
}

#improvementPlanModal .form-control {
  width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid var(--n-300);
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.2s;
}

#improvementPlanModal .form-control:focus {
  border-color: var(--n-600);
}

#improvementPlanModal .grid2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

#improvementPlanModal .btn-primary {
  background: var(--n-900);
  color: #fff;
  border: none;
  padding: 8px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

#improvementPlanModal .btn-secondary {
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

function stripTrailingQuestion(text) {
  const lines = text.split('\n');
  while (lines.length && /\?\s*$/.test(lines[lines.length - 1].trim())) {
    lines.pop();
  }
  return lines.join('\n').trim();
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
  });

  indTagControl = initTagSelect('ind', []);
});

function manuallyAddImprovementPlan() {
  document.getElementById('improvementPlanModal').style.display = 'flex';
}

function closeImprovementPlanModal() {
  document.getElementById('improvementPlanModal').style.display = 'none';
  if (dimTagControl) dimTagControl.reset();
  if (indTagControl) indTagControl.reset();
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