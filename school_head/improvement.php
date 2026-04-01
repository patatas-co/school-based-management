<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();

// ── Helper functions (must be defined before any call site) ──
function saveInd(array &$s, int $r, string $dim, string $code, string $text, string $ev, string $act): void
{
  if ($r > 0 && $r <= 4 && !empty($code) && !empty($dim) && !empty($text)) {
    $key = "rating_{$r}";
    if (!isset($s[$key][$dim]))
      $s[$key][$dim] = [];
    foreach ($s[$key][$dim] as $existing) {
      if ($existing['code'] === $code)
        return;
    }
    $s[$key][$dim][] = [
      'code' => trim($code),
      'text' => trim($text),
      'evidence' => trim($ev),
      'action' => trim($act),
    ];
  }
}

function parseRecommendationSections(string $text): array
{
  $sections = [
    'overview' => '',
    'remarks_summary' => '',
    'rating_1' => [],
    'rating_2' => [],
    'rating_3' => [],
    'rating_4' => [],
    'topic_recs' => [],
    'counts' => [],
    'is_structured' => false,
  ];
  if (empty($text))
    return $sections;

  $lines = explode("\n", $text);

  foreach ($lines as $line) {
    if (preg_match('/Not Yet Manifested.*?:\s*(\d+)/i', $line, $m))
      $sections['counts']['not_yet'] = (int) $m[1];
    if (preg_match('/Emerging.*?:\s*(\d+)\s+indicator/i', $line, $m))
      $sections['counts']['emerging'] = (int) $m[1];
    if (preg_match('/Developing.*?:\s*(\d+)\s+indicator/i', $line, $m))
      $sections['counts']['developing'] = (int) $m[1];
    if (preg_match('/Always Manifested.*?:\s*(\d+)/i', $line, $m))
      $sections['counts']['always'] = (int) $m[1];
  }

  $inRemarks = false;
  $remarkLines = [];
  $inRating = 0;
  $currentDim = '';
  $currentCode = '';
  $currentText = '';
  $currentEvidence = '';
  $currentAction = '';
  $inTopics = false;
  $currentTopic = '';

  foreach ($lines as $line) {
    $trimmed = trim($line);
    $stripped = trim(preg_replace('/[\x{1F300}-\x{1FFFF}]/u', '', $trimmed));

    if (stripos($stripped, 'STAKEHOLDER REMARKS SUMMARY') !== false) {
      $inRemarks = true;
      $inRating = 0;
      $inTopics = false;
      continue;
    }
    if (preg_match('/PRIORITY\s*1.*NOT YET MANIFESTED/i', $stripped) || preg_match('/NOT YET MANIFESTED.*IMMEDIATE/i', $stripped)) {
      $inRemarks = false;
      $inRating = 1;
      $inTopics = false;
      continue;
    }
    if (preg_match('/PRIORITY\s*2.*EMERGING/i', $stripped) || preg_match('/EMERGING.*FOCUSED/i', $stripped)) {
      saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
      $currentCode = $currentText = $currentEvidence = $currentAction = '';
      $inRemarks = false;
      $inRating = 2;
      $inTopics = false;
      continue;
    }
    if (preg_match('/PRIORITY\s*3.*DEVELOPING/i', $stripped) || preg_match('/DEVELOPING.*CONTINUE/i', $stripped)) {
      saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
      $currentCode = $currentText = $currentEvidence = $currentAction = '';
      $inRemarks = false;
      $inRating = 3;
      $inTopics = false;
      continue;
    }
    if (preg_match('/SUSTAINED PRACTICES/i', $stripped) || preg_match('/ALWAYS MANIFESTED.*SUSTAIN/i', $stripped)) {
      saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
      $currentCode = $currentText = $currentEvidence = $currentAction = '';
      $inRemarks = false;
      $inRating = 4;
      $inTopics = false;
      continue;
    }
    if (stripos($stripped, 'FROM STAKEHOLDER REMARKS') !== false || stripos($stripped, 'RECOMMENDATIONS FROM STAKEHOLDER') !== false) {
      saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
      $currentCode = $currentText = $currentEvidence = $currentAction = '';
      $inRemarks = false;
      $inRating = 0;
      $inTopics = true;
      continue;
    }
    if (preg_match('/^[─\-]{10,}/', $stripped) || stripos($stripped, 'NOTE:') === 0 || stripos($stripped, 'DIMENSION-LEVEL') !== false) {
      saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
      $currentCode = $currentText = $currentEvidence = $currentAction = '';
      $inRemarks = false;
      $inRating = 0;
      $inTopics = false;
      continue;
    }

    if ($inRemarks && !empty($trimmed)) {
      if (
        !preg_match('/PRIORITY\s*[123]/i', $stripped) &&
        !preg_match('/NOT YET MANIFESTED/i', $stripped) &&
        !preg_match('/ALWAYS MANIFESTED/i', $stripped) &&
        !preg_match('/SUSTAINED PRACTICES/i', $stripped)
      ) {
        $remarkLines[] = $trimmed;
      }
      continue;
    }

    if ($inRating > 0) {
      if (preg_match('/(?:📌\s*|•\s*)(.+)/', $stripped, $m) && !preg_match('/^\[/', trim($m[1]))) {
        saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
        $currentDim = trim($m[1], ':');
        $currentCode = $currentText = $currentEvidence = $currentAction = '';
        continue;
      }
      if (preg_match('/\[([A-Za-z0-9.]+)\]\s*(.+)/', $trimmed, $m)) {
        saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
        $currentCode = $m[1];
        $currentText = $m[2];
        $currentEvidence = $currentAction = '';
        continue;
      }
      if (stripos($trimmed, 'Evidence noted:') !== false || stripos($trimmed, 'Evidence:') !== false) {
        preg_match('/"([^"]+)"/', $trimmed, $m);
        $currentEvidence = $m[1] ?? '';
        continue;
      }
      if (strpos($trimmed, '→') !== false && !empty($currentCode)) {
        $act = trim(ltrim($trimmed, '→ '));
        $currentAction = empty($currentAction) ? $act : $currentAction . ' ' . $act;
        continue;
      }
      if (!empty($currentCode) && !empty($currentAction) && strpos($line, '       ') === 0) {
        $currentAction .= ' ' . $trimmed;
        continue;
      }
    }

    if ($inTopics && !empty($trimmed)) {
      if (preg_match('/^\[(.+)\]$/', $trimmed, $m)) {
        $currentTopic = $m[1];
        continue;
      }
      if (!empty($currentTopic) && strpos($trimmed, '→') !== false) {
        $sections['topic_recs'][$currentTopic] = trim(ltrim($trimmed, '→ '));
        continue;
      }
    }
  }

  saveInd($sections, $inRating, $currentDim, $currentCode, $currentText, $currentEvidence, $currentAction);
  $sections['remarks_summary'] = implode("\n", $remarkLines);

  if (
    !empty($sections['rating_1']) || !empty($sections['rating_2']) ||
    !empty($sections['rating_3']) || !empty($sections['rating_4']) ||
    !empty($sections['topic_recs']) || !empty($sections['remarks_summary'])
  ) {
    $sections['is_structured'] = true;
  }

  return $sections;
}

$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
  verifyCsrf();

  // Load cycle early so all handlers can use it
  $syIdEarly = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
  $cycleEarly = null;
  if ($schoolId && $syIdEarly) {
    $ce = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
    $ce->execute([$schoolId, $syIdEarly]);
    $cycleEarly = $ce->fetch();
  }

  // ── Improvement Plan actions ──────────────────────────────
  if ($_POST['action'] === 'save') {
    if (!$cycle) {
      echo json_encode(['ok' => false, 'msg' => 'No active assessment cycle.']);
      exit;
    }
    $id = (int) ($_POST['plan_id'] ?? 0);
    // Validate max lengths before inserting
    $objective = trim($_POST['objective']);
    $strategy = trim($_POST['strategy']);
    $personResp = trim($_POST['person_responsible']);
    $resourcesNeeded = trim($_POST['resources_needed']);
    $expectedOutput = trim($_POST['expected_output']);
    if (strlen($objective) > 500) {
      echo json_encode(['ok' => false, 'msg' => 'Objective must be 500 characters or less.']);
      exit;
    }
    if (strlen($strategy) > 1000) {
      echo json_encode(['ok' => false, 'msg' => 'Strategy must be 1000 characters or less.']);
      exit;
    }
    if (strlen($personResp) > 200) {
      echo json_encode(['ok' => false, 'msg' => 'Person responsible must be 200 characters or less.']);
      exit;
    }
    if (strlen($resourcesNeeded) > 500) {
      echo json_encode(['ok' => false, 'msg' => 'Resources needed must be 500 characters or less.']);
      exit;
    }
    if (strlen($expectedOutput) > 500) {
      echo json_encode(['ok' => false, 'msg' => 'Expected output must be 500 characters or less.']);
      exit;
    }
    // Validate indicator_id and priority
    $indicatorId = $_POST['indicator_id'] ? (int) $_POST['indicator_id'] : null;
    $priority = in_array($_POST['priority'] ?? '', ['High', 'Medium', 'Low'], true) ? $_POST['priority'] : 'Medium';
    $data = [
      $schoolId,
      $cycle['cycle_id'],
      (int) $_POST['dimension_id'],
      $indicatorId,
      $priority,
      $objective,
      $strategy,
      $personResp,
      $_POST['target_date'] ?: null,
      $resourcesNeeded,
      $expectedOutput,
      $_SESSION['user_id']
    ];
    if ($id) {
      $data[] = $id;
      $db->prepare("UPDATE improvement_plans SET dimension_id=?,indicator_id=?,priority_level=?,objective=?,strategy=?,person_responsible=?,target_date=?,resources_needed=?,expected_output=? WHERE plan_id=? AND school_id=?")
        ->execute([(int) $_POST['dimension_id'], $indicatorId, $priority, trim($_POST['objective']), trim($_POST['strategy']), trim($_POST['person_responsible']), $_POST['target_date'] ?: null, trim($_POST['resources_needed']), trim($_POST['expected_output']), $id, $schoolId]);
    } else {
      $db->prepare("INSERT INTO improvement_plans (school_id,cycle_id,dimension_id,indicator_id,priority_level,objective,strategy,person_responsible,target_date,resources_needed,expected_output,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute($data);
    }
    echo json_encode(['ok' => true, 'msg' => 'Plan saved.']);
    exit;
  }
  if ($_POST['action'] === 'update_status') {
    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    $status = in_array($_POST['status'] ?? '', $validStatuses, true) ? $_POST['status'] : 'pending';
    $db->prepare("UPDATE improvement_plans SET status=?,remarks=? WHERE plan_id=? AND school_id=?")
      ->execute([$status, trim($_POST['remarks']), (int) $_POST['id'], $schoolId]);
    echo json_encode(['ok' => true, 'msg' => 'Status updated.']);
    exit;
  }
  if ($_POST['action'] === 'delete') {
    $db->prepare("DELETE FROM improvement_plans WHERE plan_id=? AND school_id=?")->execute([(int) $_POST['id'], $schoolId]);
    echo json_encode(['ok' => true, 'msg' => 'Plan deleted.']);
    exit;
  }
  if ($_POST['action'] === 'get') {
    $st = $db->prepare("SELECT * FROM improvement_plans WHERE plan_id=? AND school_id=?");
    $st->execute([(int) $_POST['id'], $schoolId]);
    echo json_encode($st->fetch());
    exit;
  }

  if ($_POST['action'] === 'regenerate_ml') {
    if (!$cycleEarly) {
      echo json_encode(['ok' => false, 'msg' => 'No active cycle.']);
      exit;
    }
    require_once dirname(__DIR__) . '/includes/ml_service.php';
    $db->prepare("DELETE FROM ml_recommendations WHERE cycle_id=?")->execute([$cycleEarly['cycle_id']]);
    $ok = runMLPipeline($db, $cycleEarly['cycle_id']);
    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'AI report regenerated successfully.' : 'Generation failed.']);
    exit;
  }

  // ── Auto-generate plans from weak indicators ──────────────
  if ($_POST['action'] === 'generate_from_weak') {
    if (!$cycle) {
      echo json_encode(['ok' => false, 'msg' => 'No active assessment cycle.']);
      exit;
    }
    // Get ALL weak indicators (SH + teacher avg) rated 1-2, without existing plans
    $weak = $db->prepare("
            SELECT
                combined.indicator_id, combined.rating,
                combined.indicator_text, combined.indicator_code,
                combined.dimension_id, combined.dimension_name
            FROM (
                -- School Head responses
                SELECT
                    i.indicator_id, r.rating,
                    i.indicator_text, i.indicator_code,
                    i.dimension_id, d.dimension_name
                FROM sbm_responses r
                JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
                JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
                WHERE r.cycle_id = ? AND r.rating <= 2

                UNION ALL

                -- Teacher average responses (teacher-only indicators)
                SELECT
                    i.indicator_id,
                    FLOOR(AVG(tr.rating)) AS rating,
                    i.indicator_text, i.indicator_code,
                    i.dimension_id, d.dimension_name
                FROM teacher_responses tr
                JOIN sbm_indicators i  ON tr.indicator_id = i.indicator_id
                JOIN sbm_dimensions d  ON i.dimension_id  = d.dimension_id
                LEFT JOIN sbm_responses sr
                    ON sr.indicator_id = i.indicator_id AND sr.cycle_id = tr.cycle_id
                WHERE tr.cycle_id = ? AND sr.response_id IS NULL
                GROUP BY i.indicator_id
                HAVING AVG(tr.rating) <= 2.5

            ) AS combined
            LEFT JOIN improvement_plans ip
                ON ip.indicator_id = combined.indicator_id
                AND ip.cycle_id = ?
            WHERE ip.plan_id IS NULL
            ORDER BY combined.rating ASC, combined.dimension_name ASC
        ");
    $weak->execute([$cycle['cycle_id'], $cycle['cycle_id'], $cycle['cycle_id']]);
    $weakRows = $weak->fetchAll();

    if (empty($weakRows)) {
      echo json_encode(['ok' => false, 'msg' => 'No weak indicators found without existing plans.']);
      exit;
    }

    $ratingLabels = [1 => 'Not Yet Manifested', 2 => 'Emerging'];
    $generated = 0;
    foreach ($weakRows as $w) {
      $r = (int) $w['rating'];
      $priority = $r === 1 ? 'High' : 'Medium';
      $objective = "Improve performance on indicator " . e($w['indicator_code']) . ": " . e($w['indicator_text']);
      $strategy = "Develop targeted interventions to address areas rated '" . ($ratingLabels[$r] ?? '') . "'. Identify root causes, allocate resources, and monitor progress.";
      $db->prepare("INSERT INTO improvement_plans (school_id,cycle_id,dimension_id,indicator_id,priority_level,objective,strategy,created_by) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$schoolId, $cycle['cycle_id'], $w['dimension_id'], $w['indicator_id'], $priority, $objective, $strategy, $_SESSION['user_id']]);
      $generated++;
    }
    echo json_encode(['ok' => true, 'msg' => "Generated {$generated} improvement plan(s) from weak indicators.", 'count' => $generated]);
    exit;
  }


  exit;
}

// ── LOAD DATA ─────────────────────────────────────────────────
$plans = $cycle ? $db->prepare("SELECT ip.*,d.dimension_name,d.color_hex,i.indicator_code,i.indicator_text FROM improvement_plans ip JOIN sbm_dimensions d ON ip.dimension_id=d.dimension_id LEFT JOIN sbm_indicators i ON ip.indicator_id=i.indicator_id WHERE ip.cycle_id=? ORDER BY FIELD(ip.priority_level,'High','Medium','Low'),ip.created_at DESC") : null;
if ($plans) {
  $plans->execute([$cycle['cycle_id']]);
  $plans = $plans->fetchAll();
} else
  $plans = [];

$dims = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
$inds = $db->query("SELECT i.*,d.dimension_no FROM sbm_indicators i JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id ORDER BY d.dimension_no,i.sort_order")->fetchAll();

// Weak indicators (rating ≤ 2) for the suggestion banner
$weakCount = 0;
$weakByDim = [];
if ($cycle) {
  // Combine SH responses AND teacher average responses (including teacher-only indicators)
  $wq = $db->prepare("
        SELECT
            combined.indicator_id,
            combined.indicator_code,
            combined.indicator_text,
            combined.dimension_id,
            combined.dimension_name,
            combined.color_hex,
            combined.rating,
            (SELECT COUNT(*) FROM improvement_plans ip
             WHERE ip.indicator_id = combined.indicator_id
               AND ip.cycle_id = ?) AS has_plan
        FROM (
            -- School Head direct responses
            SELECT
                i.indicator_id, i.indicator_code, i.indicator_text,
                i.dimension_id, d.dimension_name, d.color_hex,
                r.rating
            FROM sbm_responses r
            JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
            JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
            WHERE r.cycle_id = ?
              AND r.rating <= 2

            UNION ALL

            -- Teacher average responses (teacher-only indicators not covered by SH)
            SELECT
                i.indicator_id, i.indicator_code, i.indicator_text,
                i.dimension_id, d.dimension_name, d.color_hex,
                FLOOR(AVG(tr.rating)) AS rating
            FROM teacher_responses tr
            JOIN sbm_indicators i  ON tr.indicator_id = i.indicator_id
            JOIN sbm_dimensions d  ON i.dimension_id  = d.dimension_id
            LEFT JOIN sbm_responses sr
                ON sr.indicator_id = i.indicator_id AND sr.cycle_id = tr.cycle_id
            WHERE tr.cycle_id = ?
              AND sr.response_id IS NULL
            GROUP BY i.indicator_id
            HAVING AVG(tr.rating) <= 2.5

        ) AS combined
        ORDER BY combined.rating ASC, combined.dimension_name ASC
    ");
  $wq->execute([$cycle['cycle_id'], $cycle['cycle_id'], $cycle['cycle_id']]);
  foreach ($wq->fetchAll() as $w) {
    $weakByDim[$w['dimension_name']][] = $w;
    if (!$w['has_plan'])
      $weakCount++;
  }
}


$pageTitle = 'Improvement Plan';
$activePage = 'improvement.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ── Weak indicator chips ── */
  .weak-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
    border: 1px solid currentColor;
    margin: 2px;
  }

  .weak-chip.rating-1 {
    color: #DC2626;
    background: #FEE2E2;
  }

  .weak-chip.rating-2 {
    color: #D97706;
    background: #FEF3C7;
  }

  /* ── Weak banner header — fix icon size ── */
  .weak-banner-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px 11px;
    border-bottom: 1px solid #FECACA;
    background: var(--redb);
  }

  .weak-banner-head-left {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .weak-banner-head-left svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    stroke: #DC2626;
  }

  .weak-banner-title {
    font-size: 14px;
    font-weight: 700;
    color: #DC2626;
  }

  .weak-banner-count {
    font-size: 12px;
    color: #DC2626;
    opacity: .8;
  }



  /* ── Dim checkbox labels ── */
  .dim-check-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1.5px solid var(--n200);
    border-radius: 7px;
    cursor: pointer;
    font-size: 12.5px;
    transition: border-color .15s, background .15s;
    user-select: none;
  }

  .dim-check-label:hover {
    border-color: var(--n400);
  }
</style>

<div class="page-head">
  <div class="page-head-text">
    <h2>School Improvement Plan</h2>
    <p>Action plans derived from your SBM self-assessment.</p>
  </div>
  <div class="page-head-actions">
    <?php if ($weakCount > 0): ?>
      <button class="btn btn-secondary" onclick="generatePlans()" id="genBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;">
          <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
        </svg>
        Auto-Generate (<?= $weakCount ?> gaps)
      </button>
    <?php endif; ?>
    <?php if ($cycle): ?>
      <button class="btn btn-secondary" onclick="regenerateML()" id="regenBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;">
          <polyline points="23 4 23 10 17 10" />
          <polyline points="1 20 1 14 7 14" />
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
        </svg>
        Regenerate AI Report
      </button>
    <?php endif; ?>
    <button class="btn btn-primary" onclick="openModal('mPlan');resetPlan()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;">
        <line x1="12" y1="5" x2="12" y2="19" />
        <line x1="5" y1="12" x2="19" y2="12" />
      </svg>
      Add Plan
    </button>
  </div>
</div>

<?php if (!$cycle): ?>
  <div class="alert alert-warning"><?= svgIcon('alert-circle') ?> Complete your SBM self-assessment first before creating
    improvement plans.</div>
<?php endif; ?>

<!-- ── WEAK INDICATORS BANNER ──────────────────────────────── -->
<?php if (!empty($weakByDim)): ?>
  <div class="card" style="margin-bottom:18px;border-left:4px solid var(--red);">
    <div class="weak-banner-head">
      <div class="weak-banner-head-left">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        <span class="weak-banner-title">Indicators Needing Improvement</span>
      </div>
      <span class="weak-banner-count">
        <?= array_sum(array_map('count', $weakByDim)) ?> indicators rated 1–2
        <?= $weakCount > 0 ? "· {$weakCount} without plans yet" : ' · All covered ✓' ?>
      </span>
    </div>
    <div class="card-body" style="padding:16px 18px;">
      <?php foreach ($weakByDim as $dimName => $inds): ?>
        <div style="margin-bottom:14px;">
          <div
            style="font-size:12px;font-weight:700;color:var(--n600);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">
            <?= e($dimName) ?>
          </div>
          <div>
            <?php foreach ($inds as $ind): ?>
              <span class="weak-chip rating-<?= (int) $ind['rating'] ?>" title="<?= e($ind['indicator_text']) ?>">
                <?= e($ind['indicator_code']) ?> —
                <?= (int) $ind['rating'] === 1 ? 'Not Yet Manifested' : 'Emerging' ?>       <?= $ind['has_plan'] ? ' ✓' : '' ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if ($weakCount > 0): ?>
        <div
          style="margin-top:12px;padding-top:12px;border-top:1px solid #FECACA;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <button class="btn btn-primary btn-sm" onclick="generatePlans()" id="genBtn2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" style="width:13px;height:13px;">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
            </svg>
            Auto-generate <?= $weakCount ?> improvement plan(s) from these gaps
          </button>
          <span style="font-size:12px;color:var(--n500);">Plans will be pre-filled — you can edit them after.</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php
// Load ML-generated recommendations if available
$mlRec = null;
if ($cycle) {
  $rq = $db->prepare("SELECT * FROM ml_recommendations WHERE cycle_id=?");
  $rq->execute([$cycle['cycle_id']]);
  $mlRec = $rq->fetch();
}
?>

<?php if ($mlRec) { ?>
  <div class="card" style="margin-bottom:18px;border-left:4px solid var(--purple);">

    <div class="card-head" style="background:var(--purpb);">
      <span class="card-title" style="color:var(--purple);display:flex;align-items:center;gap:7px;">
        <?= svgIcon('cpu') ?>
        <?php if (($mlRec['generated_by'] ?? '') === 'rule_based_fallback'): ?>
          Rule-Based SIP Recommendations
        <?php else: ?>
          AI-Generated SIP Recommendations
        <?php endif; ?>
      </span>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <?php if (($mlRec['generated_by'] ?? '') === 'rule_based_fallback'): ?>
          <span style="font-size:11px;color:var(--gold);background:var(--goldb);
                   border:1px solid #FDE68A;border-radius:999px;
                   padding:2px 10px;font-weight:600;">
            ⚠ AI offline — using rule-based mode
          </span>
        <?php endif; ?>
        <?php if ($mlRec['has_urgent']): ?>
          <span class="pill"
            style="background:var(--redb);color:var(--red);border:1px solid #FECACA;animation:pulse 1.5s infinite;">
            🚨 Urgent Issues Flagged
          </span>
        <?php endif; ?>
        <span style="font-size:11px;color:var(--n400);">
          Generated by <?= e($mlRec['generated_by']) ?>
          · <?= timeAgo($mlRec['generated_at']) ?>
        </span>
        <button class="btn btn-secondary btn-sm" onclick="toggleRecFull()" id="recToggleBtn">
          Show Full Report
        </button>
      </div>
    </div>

    <?php
    $recText = $mlRec['recommendation_text'] ?? '';
    $sections = parseRecommendationSections($recText);
    ?>

    <?php if ($sections['is_structured']) { ?>
      <!-- Overview counts -->
      <?php if (!empty($sections['counts'])): ?>
        <div style="padding:14px 18px;border-bottom:1px solid var(--n100);">
          <div style="font-size:12px;font-weight:700;color:var(--n500);
                text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
            Assessment Overview
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;">
            <?php
            $overviewItems = [
              ['label' => 'Not Yet Manifested', 'key' => 'not_yet', 'color' => 'var(--red)', 'bg' => 'var(--redb)', 'icon' => '🔴'],
              ['label' => 'Emerging', 'key' => 'emerging', 'color' => 'var(--gold)', 'bg' => 'var(--goldb)', 'icon' => '🟡'],
              ['label' => 'Developing', 'key' => 'developing', 'color' => 'var(--blue)', 'bg' => 'var(--blueb)', 'icon' => '🔵'],
              ['label' => 'Always Manifested', 'key' => 'always', 'color' => 'var(--g600)', 'bg' => 'var(--g100)', 'icon' => '🟢'],
            ];
            foreach ($overviewItems as $item):
              $count = $sections['counts'][$item['key']] ?? null;
              if ($count === null)
                continue;
              ?>
              <div style="background:<?= $item['bg'] ?>;border-radius:9px;
                  padding:10px 14px;border:1px solid <?= $item['color'] ?>22;text-align:center;">
                <div style="font-size:22px;margin-bottom:4px;"><?= $item['icon'] ?></div>
                <div style="font-size:26px;font-weight:800;color:<?= $item['color'] ?>;">
                  <?= $count ?>
                </div>
                <div style="font-size:11px;font-weight:600;color:<?= $item['color'] ?>;line-height:1.3;">
                  <?= $item['label'] ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Remarks summary -->
      <?php if (!empty($sections['remarks_summary'])): ?>
        <div style="padding:14px 18px;border-bottom:1px solid var(--n100);background:var(--n50);">
          <div style="font-size:12px;font-weight:700;color:var(--n500);
                text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
            📝 Stakeholder Remarks Summary
          </div>
          <div style="font-size:13px;color:var(--n700);line-height:1.8;white-space:pre-line;">
            <?= nl2br(e($sections['remarks_summary'])) ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Rating 1 — Not Yet Manifested (always visible) -->
      <?php if (!empty($sections['rating_1'])): ?>
        <div style="padding:14px 18px;border-bottom:1px solid var(--n100);
              background:#FFF5F5;border-left:3px solid var(--red);">
          <div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:10px;">
            🔴 Not Yet Manifested — Immediate Action Required
          </div>
          <?php foreach ($sections['rating_1'] as $dimName => $indicators): ?>
            <div style="margin-bottom:10px;">
              <div style="font-size:11.5px;font-weight:700;color:var(--n600);
                  text-transform:uppercase;margin-bottom:5px;">📌 <?= e($dimName) ?></div>
              <?php foreach ($indicators as $ind): ?>
                <div style="background:var(--white);border:1px solid #FECACA;
                  border-radius:8px;padding:10px 12px;margin-bottom:6px;">
                  <div style="font-size:12.5px;font-weight:700;color:var(--n800);margin-bottom:4px;">
                    [<?= e($ind['code']) ?>] <?= e($ind['text']) ?>
                  </div>
                  <?php if (!empty($ind['evidence'])): ?>
                    <div style="font-size:12px;color:var(--n500);margin-bottom:5px;font-style:italic;">
                      Evidence: "<?= e($ind['evidence']) ?>"
                    </div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:var(--red);font-weight:600;line-height:1.6;">
                    → <?= e($ind['action']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Rating 2 — Emerging (always visible) -->
      <?php if (!empty($sections['rating_2'])): ?>
        <div style="padding:14px 18px;border-bottom:1px solid var(--n100);
              background:#FFFBEB;border-left:3px solid var(--gold);">
          <div style="font-size:13px;font-weight:700;color:var(--gold);margin-bottom:10px;">
            🟡 Emerging — Focused Intervention Needed
          </div>
          <?php foreach ($sections['rating_2'] as $dimName => $indicators): ?>
            <div style="margin-bottom:10px;">
              <div style="font-size:11.5px;font-weight:700;color:var(--n600);
                  text-transform:uppercase;margin-bottom:5px;">📌 <?= e($dimName) ?></div>
              <?php foreach ($indicators as $ind): ?>
                <div style="background:var(--white);border:1px solid #FDE68A;
                  border-radius:8px;padding:10px 12px;margin-bottom:6px;">
                  <div style="font-size:12.5px;font-weight:700;color:var(--n800);margin-bottom:4px;">
                    [<?= e($ind['code']) ?>] <?= e($ind['text']) ?>
                  </div>
                  <?php if (!empty($ind['evidence'])): ?>
                    <div style="font-size:12px;color:var(--n500);margin-bottom:5px;font-style:italic;">
                      Evidence: "<?= e($ind['evidence']) ?>"
                    </div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:var(--gold);font-weight:600;line-height:1.6;">
                    → <?= e($ind['action']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Rating 3 & 4 + Topics — hidden until "Show Full Report" clicked -->
      <div id="recFullContent" style="display:none;">

        <?php if (!empty($sections['rating_3'])): ?>
          <div style="padding:14px 18px;border-bottom:1px solid var(--n100);
                background:#EFF6FF;border-left:3px solid var(--blue);">
            <div style="font-size:13px;font-weight:700;color:var(--blue);margin-bottom:10px;">
              🔵 Developing — Continue &amp; Strengthen
            </div>
            <?php foreach ($sections['rating_3'] as $dimName => $indicators): ?>
              <div style="margin-bottom:10px;">
                <div style="font-size:11.5px;font-weight:700;color:var(--n600);
                    text-transform:uppercase;margin-bottom:5px;">📌 <?= e($dimName) ?></div>
                <?php foreach ($indicators as $ind): ?>
                  <div style="background:var(--white);border:1px solid #BFDBFE;
                    border-radius:8px;padding:10px 12px;margin-bottom:6px;">
                    <div style="font-size:12.5px;font-weight:600;color:var(--n800);margin-bottom:4px;">
                      [<?= e($ind['code']) ?>] <?= e($ind['text']) ?>
                    </div>
                    <div style="font-size:12px;color:var(--blue);line-height:1.6;">
                      → <?= e($ind['action']) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['rating_4'])): ?>
          <div style="padding:14px 18px;border-bottom:1px solid var(--n100);
                background:var(--g50);border-left:3px solid var(--g500);">
            <div style="font-size:13px;font-weight:700;color:var(--g600);margin-bottom:10px;">
              🟢 Always Manifested — Sustain &amp; Document
            </div>
            <?php foreach ($sections['rating_4'] as $dimName => $indicators): ?>
              <div style="margin-bottom:10px;">
                <div style="font-size:11.5px;font-weight:700;color:var(--n600);
                    text-transform:uppercase;margin-bottom:5px;">📌 <?= e($dimName) ?></div>
                <?php foreach ($indicators as $ind): ?>
                  <div style="background:var(--white);border:1px solid var(--g200);
                    border-radius:8px;padding:10px 12px;margin-bottom:6px;">
                    <div style="font-size:12.5px;font-weight:600;color:var(--n800);margin-bottom:4px;">
                      [<?= e($ind['code']) ?>] <?= e($ind['text']) ?>
                    </div>
                    <div style="font-size:12px;color:var(--g600);line-height:1.6;">
                      → <?= e($ind['action']) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['topic_recs'])): ?>
          <div style="padding:14px 18px;border-bottom:1px solid var(--n100);">
            <div style="font-size:12px;font-weight:700;color:var(--n500);
                  text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
              💬 From Stakeholder Remarks
            </div>
            <?php foreach ($sections['topic_recs'] as $topic => $rec): ?>
              <div style="background:var(--purpb);border:1px solid #DDD6FE;
                  border-radius:8px;padding:10px 14px;margin-bottom:8px;">
                <div style="font-size:12px;font-weight:700;color:var(--purple);margin-bottom:4px;">
                  <?= e($topic) ?>
                </div>
                <div style="font-size:12.5px;color:var(--n700);line-height:1.6;">
                  → <?= e($rec) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div><!-- #recFullContent -->

    <?php } else { ?>
      <!-- Raw text fallback for non-structured LLM output (Groq/OpenAI) -->
      <div style="padding:16px 18px; font-size:14px; color:var(--n800); line-height:1.7; white-space:pre-line;">
        <?php
        $cleaned = preg_replace('/^#+\s+/m', '', $recText);
        $cleaned = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $cleaned);
        $cleaned = preg_replace('/\*+(.*?)\*+/', '$1', $cleaned);
        echo nl2br($cleaned);
        ?>
      </div>
    <?php } ?>

    <!-- Topic pills -->
    <?php
    $topics = json_decode($mlRec['top_topics'] ?? '[]', true);
    if (!empty($topics)):
      ?>
      <div style="padding:12px 18px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
        <span style="font-size:11.5px;color:var(--n500);margin-right:4px;">Key themes:</span>
        <?php foreach ($topics as $t): ?>
          <span class="pill" style="background:var(--purpb);color:var(--purple);
          border:1px solid #DDD6FE;font-size:10.5px;">
            <?= e(str_replace('_', ' ', $t)) ?>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div><!-- .card -->

  <script>
    function toggleRecFull() {
      const content = document.getElementById('recFullContent');
      const btn = document.getElementById('recToggleBtn');
      if (!content || !btn) return;
      const isHidden = content.style.display === 'none';
      content.style.display = isHidden ? 'block' : 'none';
      btn.textContent = isHidden ? 'Show Less' : 'Show Full Report';
    }
  </script>

<?php } ?>

<!-- ── IMPROVEMENT PLANS SUMMARY ───────────────────────────── -->
<?php if ($plans): ?>
  <?php $byPriority = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'completed' => 0];
  foreach ($plans as $p) {
    $byPriority[$p['priority_level']]++;
    if ($p['status'] === 'completed')
      $byPriority['completed']++;
  } ?>
  <div class="stats" style="margin-bottom:18px;">
    <div class="stat">
      <div class="stat-ic red"><?= svgIcon('alert-circle') ?></div>
      <div class="stat-data">
        <div class="stat-val"><?= $byPriority['High'] ?></div>
        <div class="stat-lbl">High Priority</div>
      </div>
    </div>
    <div class="stat">
      <div class="stat-ic gold"><?= svgIcon('star') ?></div>
      <div class="stat-data">
        <div class="stat-val"><?= $byPriority['Medium'] ?></div>
        <div class="stat-lbl">Medium Priority</div>
      </div>
    </div>
    <div class="stat">
      <div class="stat-ic blue"><?= svgIcon('info') ?></div>
      <div class="stat-data">
        <div class="stat-val"><?= $byPriority['Low'] ?></div>
        <div class="stat-lbl">Low Priority</div>
      </div>
    </div>
    <div class="stat">
      <div class="stat-ic green"><?= svgIcon('check') ?></div>
      <div class="stat-data">
        <div class="stat-val"><?= $byPriority['completed'] ?></div>
        <div class="stat-lbl">Completed</div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="card" style="margin-bottom:22px;">
  <div class="card-head"><span class="card-title">Action Plans (<?= count($plans) ?>)</span></div>
  <?php if (!$plans): ?>
    <div class="card-body" style="text-align:center;padding:48px 40px;color:var(--n400);">
      No action plans yet.
      <?php if ($weakCount > 0): ?>
        <br>
        <button class="btn btn-primary btn-sm" style="margin-top:14px;" onclick="generatePlans()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" style="width:13px;height:13px;flex-shrink:0;">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
          </svg>
          Auto-generate from weak indicators
        </button>
      <?php else: ?>
        <br><span style="font-size:13px;">Add plans to address areas for improvement.</span>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table id="tblPlans">
        <thead>
          <tr>
            <th>Priority</th>
            <th>Dimension</th>
            <th>Indicator</th>
            <th>Objective</th>
            <th>Person</th>
            <th>Target</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans as $p):
            $pColors = ['High' => 'var(--red)', 'Medium' => 'var(--gold)', 'Low' => 'var(--blue)'];
            $sBgs = ['planned' => 'var(--n100)', 'ongoing' => 'var(--blueb)', 'completed' => 'var(--g100)', 'cancelled' => 'var(--redb)'];
            $sSubs = ['planned' => 'var(--n500)', 'ongoing' => 'var(--blue)', 'completed' => 'var(--g700)', 'cancelled' => 'var(--red)'];
            ?>
            <tr>
              <td><span
                  style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $pColors[$p['priority_level']] ?>22;color:<?= $pColors[$p['priority_level']] ?>;"><?= e($p['priority_level']) ?></span>
              </td>
              <td style="font-size:12.5px;font-weight:600;color:<?= e($p['color_hex']) ?>;"><?= e($p['dimension_name']) ?>
              </td>
              <td style="font-size:11.5px;color:var(--n500);"><?= e($p['indicator_code'] ?? '—') ?></td>
              <td style="font-size:13px;max-width:200px;">
                <?= e(substr($p['objective'], 0, 70)) ?>     <?= strlen($p['objective']) > 70 ? '…' : '' ?>
              </td>
              <td style="font-size:12.5px;"><?= e($p['person_responsible'] ?? '—') ?></td>
              <td style="font-size:12.5px;"><?= $p['target_date'] ? date('M d, Y', strtotime($p['target_date'])) : '—' ?>
              </td>
              <td><span
                  style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:<?= $sBgs[$p['status']] ?>;color:<?= $sSubs[$p['status']] ?>;"><?= ucfirst($p['status']) ?></span>
              </td>
              <td>
                <div class="flex-c" style="gap:5px;">
                  <button class="btn btn-secondary btn-sm"
                    onclick="editPlan(<?= $p['plan_id'] ?>)"><?= svgIcon('edit') ?></button>
                  <button class="btn btn-danger btn-sm"
                    onclick="delPlan(<?= $p['plan_id'] ?>)"><?= svgIcon('trash') ?></button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>


<!-- ═══════════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════ -->
<!-- Plan Modal -->
<div class="overlay" id="mPlan">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head"><span class="modal-title" id="mPlanTitle">Add Action Plan</span><button class="modal-close"
        onclick="closeModal('mPlan')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="p_id">
      <div class="form-row">
        <div class="fg"><label>Dimension *</label>
          <select class="fc" id="p_dim" onchange="filterIndicators()">
            <option value="">— Select —</option>
            <?php foreach ($dims as $d): ?>
              <option value="<?= $d['dimension_id'] ?>">D<?= $d['dimension_no'] ?>: <?= e($d['dimension_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Indicator (optional)</label>
          <select class="fc" id="p_ind">
            <option value="">— Dimension-wide —</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Priority Level</label>
          <select class="fc" id="p_priority">
            <option value="High">High</option>
            <option value="Medium" selected>Medium</option>
            <option value="Low">Low</option>
          </select>
        </div>
        <div class="fg"><label>Target Date</label><input class="fc" type="date" id="p_date"></div>
      </div>
      <div class="fg"><label>Objective *</label><textarea class="fc" id="p_objective" rows="2"
          placeholder="What do you want to achieve?"></textarea></div>
      <div class="fg"><label>Strategy / Action Steps *</label><textarea class="fc" id="p_strategy" rows="3"
          placeholder="Describe specific actions to be taken…"></textarea></div>
      <div class="form-row">
        <div class="fg"><label>Person Responsible</label><input class="fc" id="p_person" placeholder="Name / Position">
        </div>
        <div class="fg"><label>Resources Needed</label><input class="fc" id="p_resources"
            placeholder="Budget, materials, etc."></div>
      </div>
      <div class="fg"><label>Expected Output</label><input class="fc" id="p_output"
          placeholder="Measurable outcome or deliverable"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mPlan')">Cancel</button>
      <button class="btn btn-primary" onclick="savePlan()">Save Plan</button>
    </div>
  </div>
</div>

<script>
  const ALL_INDICATORS = <?= json_encode($inds) ?>;

  function filterIndicators() {
    const dimId = parseInt($('p_dim'));
    const sel = $el('p_ind');
    sel.innerHTML = '<option value="">— Dimension-wide —</option>';
    ALL_INDICATORS.filter(i => i.dimension_id == dimId).forEach(i => {
      const opt = document.createElement('option');
      opt.value = i.indicator_id;
      opt.textContent = i.indicator_code + ': ' + i.indicator_text.substring(0, 60) + '…';
      sel.appendChild(opt);
    });
  }

  function resetPlan() {
    $v('p_id', ''); $v('p_dim', ''); $v('p_ind', ''); $v('p_priority', 'Medium');
    $v('p_date', ''); $v('p_objective', ''); $v('p_strategy', '');
    $v('p_person', ''); $v('p_resources', ''); $v('p_output', '');
    $el('mPlanTitle').textContent = 'Add Action Plan';
  }

  async function savePlan() {
    if (!$('p_dim') || !$('p_objective') || !$('p_strategy')) { toast('Fill in required fields.', 'warning'); return; }
    const d = {
      action: 'save', plan_id: $('p_id'), dimension_id: $('p_dim'), indicator_id: $('p_ind'),
      priority: $('p_priority'), target_date: $('p_date'), objective: $('p_objective'),
      strategy: $('p_strategy'), person_responsible: $('p_person'),
      resources_needed: $('p_resources'), expected_output: $('p_output')
    };
    const r = await apiPost('improvement.php', d);
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mPlan'); setTimeout(() => location.reload(), 800); }
  }

  async function editPlan(id) {
    const r = await apiPost('improvement.php', { action: 'get', id });
    $v('p_id', r.plan_id); $v('p_dim', r.dimension_id); filterIndicators();
    setTimeout(() => { $v('p_ind', r.indicator_id || ''); }, 100);
    $v('p_priority', r.priority_level); $v('p_date', r.target_date || '');
    $v('p_objective', r.objective); $v('p_strategy', r.strategy);
    $v('p_person', r.person_responsible || ''); $v('p_resources', r.resources_needed || '');
    $v('p_output', r.expected_output || '');
    $el('mPlanTitle').textContent = 'Edit Action Plan';
    openModal('mPlan');
  }

  async function delPlan(id) {
    if (!confirm('Delete this action plan?')) return;
    const r = await apiPost('improvement.php', { action: 'delete', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }

  // ── Auto-generate plans ──────────────────────────────────────
  async function generatePlans() {
    const btns = document.querySelectorAll('#genBtn,#genBtn2');
    btns.forEach(b => { b.disabled = true; b.textContent = 'Generating…'; });
    const r = await apiPost('improvement.php', { action: 'generate_from_weak' });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 900);
    else btns.forEach(b => { b.disabled = false; b.textContent = '⚡ Auto-Generate'; });
  }


  async function regenerateML() {
    const btn = document.getElementById('regenBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Generating…'; }
    const r = await apiPost('improvement.php', { action: 'regenerate_ml' });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
    else if (btn) {
      btn.disabled = false;
      btn.innerHTML = `${svgI('refresh-cw')} Regenerate AI Report`;
    }
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>