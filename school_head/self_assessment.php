<?php
ob_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
function buildInPlaceholders(array $arr): string
{
  return implode(',', array_fill(0, count($arr), '?'));
}
requireRole('school_head', 'sbm_coordinator');
$db = getDB();

// Rating constants
define('MIN_RATING', 1);
define('MAX_RATING', 4);

$schoolId = SCHOOL_ID; // Always DIHS
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$activeFormVersionId = (int) $db->query("SELECT version_id FROM form_versions WHERE is_active=1 LIMIT 1")->fetchColumn();

if (!$syId) {
  echo '<div class="alert alert-danger">No active school year configured. Contact the administrator.</div>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ── SELF-ASSESSMENT PHASE WINDOW (from sbm_workflow_phases, phase_no=1) ──
function getSelfAssessmentWindow(PDO $db, int $syId): array
{
  $ph = $db->prepare("SELECT date_start, date_end FROM sbm_workflow_phases WHERE sy_id=? AND phase_no=1 AND is_active=1");
  $ph->execute([$syId]);
  $row = $ph->fetch();
  return ['start' => $row['date_start'] ?? null, 'end' => $row['date_end'] ?? null];
}

function isWithinAssessmentWindow(array $window): bool
{
  $today = date('Y-m-d');
  if ($window['start'] && $today < $window['start']) return false;
  if ($window['end'] && $today > $window['end']) return false;
  return true;
}

function assessmentWindowMessage(array $window): string
{
  if (!$window['start'] && !$window['end']) {
    return 'The Self-Assessment schedule has not been configured yet.';
  }
  $today = date('Y-m-d');
  if ($window['start'] && $today < $window['start']) {
    return 'The Self-Assessment phase opens on ' . date('M d, Y', strtotime($window['start'])) . '.';
  }
  if ($window['end'] && $today > $window['end']) {
    return 'The Self-Assessment phase closed on ' . date('M d, Y', strtotime($window['end'])) . '.';
  }
  return 'The Self-Assessment phase is not currently open.';
}

$assessmentWindow = getSelfAssessmentWindow($db, $syId);

// ── AJAX HANDLERS ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  ob_start();
  ob_clean();
  header('Content-Type: application/json');
  verifyCsrf();
  try {

    if ($_POST['action'] === 'start_assessment') {
      // Check if user has permission to start assessment
      if (!hasAccess('start_assessment')) {
        echo json_encode(['ok' => false, 'msg' => 'Access denied. Only School Head can start assessments.']);
        exit;
      }

      if (!isWithinAssessmentWindow($assessmentWindow)) {
        echo json_encode(['ok' => false, 'msg' => assessmentWindowMessage($assessmentWindow)]);
        exit;
      }

      if (!$syId) {
        echo json_encode(['ok' => false, 'msg' => 'No active school year found.']);
        exit;
      }
      $check = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $check->execute([$schoolId, $syId]);
      if ($check->fetchColumn()) {
        echo json_encode(['ok' => false, 'msg' => 'Assessment cycle already exists.']);
        exit;
      }
      try {
        $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())")->execute([$syId, $schoolId]);
        $newCycleId = $db->lastInsertId();
        // Initialize dimension scores
        $dimIds = $db->query("SELECT dimension_id FROM sbm_dimensions")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($dimIds as $dId) {
          $db->prepare("INSERT IGNORE INTO sbm_dimension_scores (cycle_id, school_id, dimension_id, raw_score, max_score, percentage) VALUES (?, ?, ?, 0, 0, 0)")
            ->execute([$newCycleId, $schoolId, $dId]);
        }
        logActivity('start_assessment', 'self_assessment', "Started SBM assessment cycle for the current school year.");
        echo json_encode(['ok' => true, 'msg' => 'Assessment started successfully!']);
        exit;
      } catch (\PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'Failed to initialize assessment cycle. It may already exist.']);
        exit;
      }
    }

    if ($_POST['action'] === 'save_response') {
      // SBM Coordinator is view-only — cannot submit ratings
      if ($_SESSION['role'] === 'sbm_coordinator') {
        echo json_encode(['ok' => false, 'msg' => 'SBM Coordinators can view but not modify assessments.']);
        exit;
      }

      if (!isWithinAssessmentWindow($assessmentWindow)) {
        echo json_encode(['ok' => false, 'msg' => assessmentWindowMessage($assessmentWindow)]);
        exit;
      }

      $indicatorId = (int) $_POST['indicator_id'];
      $rating = (int) $_POST['rating'];
      $evidence = trim($_POST['evidence'] ?? '');

      if ($rating < 1 || $rating > 4) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid rating.']);
        exit;
      }

      $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
      $chk->execute([$indicatorId]);
      $indicatorCode = $chk->fetchColumn();

      // Block only pure teacher-only indicators (not shared SH+Teacher ones)
      if (!in_array($indicatorCode, SH_RATEABLE_CODES)) {
        echo json_encode(['ok' => false, 'msg' => 'This indicator is not rated by the School Head.']);
        exit;
      }

      $cycleStmt = $db->prepare("SELECT cycle_id, school_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycleStmt->execute([$schoolId, $syId]);
      $cycleRow = $cycleStmt->fetch();

      if (!$cycleRow) {
        try {
          $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())")->execute([$syId, $schoolId]);
          $cycleId = $db->lastInsertId();
          // Initialize dimension scores
          $dimIds = $db->query("SELECT dimension_id FROM sbm_dimensions")->fetchAll(PDO::FETCH_COLUMN);
          foreach ($dimIds as $dId) {
            $db->prepare("INSERT IGNORE INTO sbm_dimension_scores (cycle_id, school_id, dimension_id, raw_score, max_score, percentage) VALUES (?, ?, ?, 0, 0, 0)")
              ->execute([$cycleId, $schoolId, $dId]);
          }
        } catch (\PDOException $e) {
          if ($e->getCode() === '23000') {
            $retry = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?");
            $retry->execute([$schoolId, $syId]);
            $cycleId = $retry->fetchColumn();
            if (!$cycleId) {
              echo json_encode(['ok' => false, 'msg' => 'Failed to initialize assessment cycle. Please refresh and try again.']);
              exit;
            }
          } else {
            throw $e;
          }
        }
        if (empty($cycleId)) {
          echo json_encode(['ok' => false, 'msg' => 'Failed to initialize assessment cycle. Please refresh and try again.']);
          exit;
        }
      } else {
        $cycleId = $cycleRow['cycle_id'];
        if ((int) $cycleRow['school_id'] !== (int) $schoolId) {
          echo json_encode(['ok' => false, 'msg' => 'Access denied.']);
          exit;
        }
        if ($cycleRow['status'] === 'draft') {
          $db->prepare("UPDATE sbm_cycles SET status='in_progress',started_at=NOW() WHERE cycle_id=?")->execute([$cycleId]);
        }
      }

      $db->prepare("INSERT INTO sbm_responses (cycle_id,indicator_id,school_id,rating,evidence_text,rated_by)
                      VALUES (?,?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE
                        rating=VALUES(rating),
                        evidence_text=VALUES(evidence_text),
                        rated_by=VALUES(rated_by),
                        rated_at=NOW()")
        ->execute([$cycleId, $indicatorId, $schoolId, $rating, $evidence, $_SESSION['user_id']]);

      recomputeDimScoreWithOverrides($db, $cycleId, $indicatorId, $schoolId);
      echo json_encode(['ok' => true, 'msg' => 'Saved.']);
      exit;
    }

    if ($_POST['action'] === 'clear_response') {
      // SBM Coordinator is view-only
      if ($_SESSION['role'] === 'sbm_coordinator') {
        echo json_encode(['ok' => false, 'msg' => 'SBM Coordinators cannot modify assessments.']);
        exit;
      }

      if (!isWithinAssessmentWindow($assessmentWindow)) {
        echo json_encode(['ok' => false, 'msg' => assessmentWindowMessage($assessmentWindow)]);
        exit;
      }

      $indicatorId = (int) $_POST['indicator_id'];

      $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
      $chk->execute([$indicatorId]);
      $indicatorCode = $chk->fetchColumn();
      if (!in_array($indicatorCode, SH_RATEABLE_CODES)) {
        echo json_encode(['ok' => false, 'msg' => 'Cannot clear a non-SH indicator.']);
        exit;
      }

      $cycleRow = $db->prepare("SELECT cycle_id, school_id, sy_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycleRow->execute([$schoolId, $syId]);
      $cycleRow = $cycleRow->fetch();
      if (!$cycleRow) {
        echo json_encode(['ok' => false, 'msg' => 'No active cycle.']);
        exit;
      }
      if (in_array($cycleRow['status'], ['submitted', 'validated', 'finalized'])) {
        echo json_encode(['ok' => false, 'msg' => 'Assessment is locked. Cannot clear.']);
        exit;
      }

      $db->prepare("DELETE FROM sbm_responses WHERE cycle_id=? AND indicator_id=? AND school_id=?")
        ->execute([$cycleRow['cycle_id'], $indicatorId, $schoolId]);

      recomputeDimScoreWithOverrides($db, $cycleRow['cycle_id'], $indicatorId, $schoolId);
      echo json_encode(['ok' => true, 'msg' => 'Rating cleared.']);
      exit;
    }

    if ($_POST['action'] === 'clear_dimension') {
      if (!isWithinAssessmentWindow($assessmentWindow)) {
        echo json_encode(['ok' => false, 'msg' => assessmentWindowMessage($assessmentWindow)]);
        exit;
      }

      $dimId = (int) $_POST['dimension_id'];

      $cycleRow = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycleRow->execute([$schoolId, $syId]);
      $cycleRow = $cycleRow->fetch();
      if (!$cycleRow) {
        echo json_encode(['ok' => false, 'msg' => 'No active cycle.']);
        exit;
      }
      if (in_array($cycleRow['status'], ['submitted', 'validated', 'finalized'])) {
        echo json_encode(['ok' => false, 'msg' => 'Assessment is locked.']);
        exit;
      }

      $teacherOnlyCodes = array_merge(TEACHER_ONLY_CODES, TCH_EXT_CODES);
      if (empty($teacherOnlyCodes)) {
        $db->prepare("
    DELETE r FROM sbm_responses r
    JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
    WHERE r.cycle_id = ?
      AND i.dimension_id = ?
")->execute([$cycleRow['cycle_id'], $dimId]);
      } else {
        $ph = buildInPlaceholders($teacherOnlyCodes);
        $db->prepare("
    DELETE r FROM sbm_responses r
    JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
    WHERE r.cycle_id = ?
      AND i.dimension_id = ?
      AND i.indicator_code NOT IN ($ph)
")->execute(array_merge([$cycleRow['cycle_id'], $dimId], $teacherOnlyCodes));
      }

      $db->prepare("UPDATE sbm_dimension_scores SET raw_score=0,max_score=0,percentage=0,computed_at=NOW()
              WHERE cycle_id=? AND dimension_id=?")
        ->execute([$cycleRow['cycle_id'], $dimId]);

      // Recompute from scratch using any remaining teacher responses
      $anyInd = $db->prepare("SELECT indicator_id FROM sbm_indicators WHERE dimension_id=? AND is_active=1 LIMIT 1");
      $anyInd->execute([$dimId]);
      $anyIndId = $anyInd->fetchColumn();
      if ($anyIndId) {
        recomputeDimScoreWithOverrides($db, $cycleRow['cycle_id'], $anyIndId, $schoolId);
      }

      $indIds = $db->prepare("SELECT indicator_id FROM sbm_indicators WHERE dimension_id=? AND is_active=1");
      $indIds->execute([$dimId]);
      $indIds = $indIds->fetchAll(PDO::FETCH_COLUMN);

      echo json_encode(['ok' => true, 'msg' => 'All ratings cleared for this dimension.', 'indicator_ids' => $indIds]);
      exit;
    }

    if ($_POST['action'] === 'submit') {
      if (!isWithinAssessmentWindow($assessmentWindow)) {
        echo json_encode(['ok' => false, 'msg' => assessmentWindowMessage($assessmentWindow)]);
        exit;
      }

      $forceSubmit = !empty($_POST['force_submit']);
      $cyc = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cyc->execute([$schoolId, $syId]);
      $cyc = $cyc->fetch();
      if (!$cyc) {
        echo json_encode(['ok' => false, 'msg' => 'No assessment to submit.']);
        exit;
      }

      // Check all active teachers have submitted
      $totalTeachersQ = $db->prepare("SELECT COUNT(*) FROM users WHERE school_id=? AND role='teacher' AND status='active'");
      $totalTeachersQ->execute([$schoolId]);
      $totalTeachers = (int) $totalTeachersQ->fetchColumn();

      $submittedTeachersQ = $db->prepare("SELECT COUNT(*) FROM teacher_submissions WHERE cycle_id=? AND status='submitted'");
      $submittedTeachersQ->execute([$cyc['cycle_id']]);
      $submittedTeachers = (int) $submittedTeachersQ->fetchColumn();

      if ($submittedTeachers < $totalTeachers && !$forceSubmit) {
        // Return warning — let the SH decide to force-submit or wait
        echo json_encode([
          'ok' => false,
          'warn_teachers' => true,
          'submitted' => $submittedTeachers,
          'total' => $totalTeachers,
          'pending' => $totalTeachers - $submittedTeachers,
          'msg' => "Only $submittedTeachers of $totalTeachers teachers have submitted. You can wait for them or submit anyway (teacher averages will be based on responses received so far)."
        ]);
        exit;
      }

      // SH must answer: SH_ONLY indicators + shared (teacher codes not in SH_ONLY are teacher-only)
      $shAnswerableCodes = SH_ONLY_INDICATOR_CODES;
      $ph = buildInPlaceholders($shAnswerableCodes);
      $shOnlyStmt = $db->prepare("
    SELECT COUNT(*) FROM sbm_indicators
    WHERE is_active = 1
      AND form_version_id = ?
      AND indicator_code IN ($ph)
");
      $shOnlyStmt->execute(array_merge([$activeFormVersionId], $shAnswerableCodes));
      $expected = (int) $shOnlyStmt->fetchColumn();

      $shDoneStmt = $db->prepare("
    SELECT COUNT(*) FROM sbm_responses r
    JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
    WHERE r.cycle_id = ?
      AND i.is_active = 1
      AND i.indicator_code IN ($ph)
");
      $shDoneStmt->execute(array_merge([$cyc['cycle_id']], $shAnswerableCodes));
      $cnt = (int) $shDoneStmt->fetchColumn();

      if ($cnt < $expected) {
        echo json_encode([
          'ok' => false,
          'msg' => "Please rate all your indicators. ($cnt/$expected done)"
        ]);
        exit;
      }

      // Recompute all dimensions before finalizing score
      $allDims = $db->query("SELECT dimension_id FROM sbm_dimensions WHERE 1")->fetchAll(PDO::FETCH_COLUMN);
      foreach ($allDims as $dimId) {
        $anyInd = $db->prepare("SELECT indicator_id FROM sbm_indicators WHERE dimension_id=? AND is_active=1 LIMIT 1");
        $anyInd->execute([$dimId]);
        $anyIndId = $anyInd->fetchColumn();
        if ($anyIndId) {
          recomputeDimScoreWithOverrides($db, $cyc['cycle_id'], $anyIndId, $schoolId);
        }
      }

      // Single query AFTER all dimensions are updated
      $total = $db->prepare("SELECT SUM(raw_score), SUM(max_score) FROM sbm_dimension_scores WHERE cycle_id=?");
      $total->execute([$cyc['cycle_id']]);
      [$totalRaw, $totalMax] = array_values($total->fetch(PDO::FETCH_NUM));
      $overall = $totalMax > 0 ? round(($totalRaw / $totalMax) * 100, 2) : 0;
      $mat = sbmMaturityLevel($overall);


      $db->prepare("UPDATE sbm_cycles SET status='submitted',submitted_at=NOW(),overall_score=?,maturity_level=? WHERE cycle_id=?")
        ->execute([$overall, $mat['label'], $cyc['cycle_id']]);
      logActivity('submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle ' . $cyc['cycle_id']);

      // ── Trigger ML pipeline directly ──────────────────────────────
      $rl = checkRateLimit('ml_recommendation', 5, 60);
      if ($rl['allowed']) {
        require_once dirname(__DIR__) . '/includes/ml_service.php';
        try {
          runMLPipeline($db, $cyc['cycle_id']);
        } catch (Exception $e) {
          error_log("ML pipeline error: " . $e->getMessage());
          // Silent fail — submission still succeeds
        }
      } else {
        error_log("ML pipeline skipped: rate limit reached (retry_after={$rl['retry_after']}s)");
      }

      echo json_encode(['ok' => true, 'msg' => 'Assessment submitted successfully!']);
      exit;
    }

      } catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Server error: ' . $e->getMessage()]);
  }
  exit;
}

function isTeacherHandled(string $code): bool
{
  // Pure teacher-only: no SH input at all
  if (in_array($code, TEACHER_ONLY_CODES)) {
    return true;
  }
  // Teacher + External, no SH direct rating
  if (in_array($code, TCH_EXT_CODES)) {
    return true;
  }
  return false;
}

function recomputeDimScoreWithOverrides(PDO $db, int $cycleId, int $indicatorId, int $schoolId): void
{
  $dimId = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id=?");
  $dimId->execute([$indicatorId]);
  $dimId = $dimId->fetchColumn();

  $inds = $db->prepare("SELECT indicator_id, indicator_code FROM sbm_indicators WHERE dimension_id=? AND is_active=1");
  $inds->execute([$dimId]);
  $inds = $inds->fetchAll();

  $rawTotal = 0;
  $maxTotal = 0;

  // Each evaluator group has equal weight:
  // School Head rating, teacher-group average, external-group average.
  $teacherCodes = array_merge(
    TEACHER_ONLY_CODES,
    SH_TEACHER_CODES,
    SH_TCH_EXT_CODES,
    TCH_EXT_CODES
  );

  $externalCodes = array_merge(
    SH_EXT_CODES,
    SH_TCH_EXT_CODES,
    TCH_EXT_CODES
  );

  $shRatingStmt = $db->prepare(
    "SELECT rating
     FROM sbm_responses
     WHERE cycle_id=? AND indicator_id=? AND school_id=?"
  );

  $teacherAverageStmt = $db->prepare(
    "SELECT AVG(rating)
     FROM teacher_responses
     WHERE cycle_id=? AND indicator_id=?"
  );

  $externalAverageStmt = $db->prepare(
    "SELECT AVG(rating)
     FROM stakeholder_responses
     WHERE cycle_id=? AND indicator_id=?"
  );

  foreach ($inds as $ind) {
    $code = $ind['indicator_code'];
    $ratings = [];

    // Teacher-only and Teacher+External indicators have no School Head rating.
    $needsSchoolHead = !in_array($code, TEACHER_ONLY_CODES, true)
      && !in_array($code, TCH_EXT_CODES, true);

    $needsTeachers = in_array($code, $teacherCodes, true);
    $needsExternal = in_array($code, $externalCodes, true);

    if ($needsSchoolHead) {
      $shRatingStmt->execute([$cycleId, $ind['indicator_id'], $schoolId]);
      $shRating = $shRatingStmt->fetchColumn();

      if ($shRating !== false && $shRating !== null) {
        $ratings[] = (float) $shRating;
      }
    }

    if ($needsTeachers) {
      $teacherAverageStmt->execute([$cycleId, $ind['indicator_id']]);
      $teacherAverage = $teacherAverageStmt->fetchColumn();

      if ($teacherAverage !== false && $teacherAverage !== null) {
        $ratings[] = (float) $teacherAverage;
      }
    }

    if ($needsExternal) {
      $externalAverageStmt->execute([$cycleId, $ind['indicator_id']]);
      $externalAverage = $externalAverageStmt->fetchColumn();

      if ($externalAverage !== false && $externalAverage !== null) {
        $ratings[] = (float) $externalAverage;
      }
    }

    // Provisional: average only the evaluator groups that have submitted.
    // Final validation should require all required evaluator groups.
    if ($ratings) {
      $rawTotal += array_sum($ratings) / count($ratings);
      $maxTotal += 4;
    }
  }

  $rawTotal = round($rawTotal, 2);
  $pct = $maxTotal > 0 ? round(($rawTotal / $maxTotal) * 100, 2) : 0;

  $db->prepare("
        INSERT INTO sbm_dimension_scores (cycle_id, school_id, dimension_id, raw_score, max_score, percentage)
        VALUES (?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            raw_score=VALUES(raw_score),
            max_score=VALUES(max_score),
            percentage=VALUES(percentage),
            computed_at=NOW()
    ")->execute([$cycleId, $schoolId, $dimId, $rawTotal, $maxTotal, $pct]);

  // overall_score is only computed on submission, not during live rating
}

// ── LOAD DATA ────────────────────────────────────────────────
$indicatorsStmt = $db->prepare("
    SELECT i.*, d.dimension_no, d.dimension_name, d.color_hex
    FROM sbm_indicators i
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    WHERE i.is_active = 1
      AND i.form_version_id = ?
    ORDER BY d.dimension_no, i.sort_order
");
$indicatorsStmt->execute([$activeFormVersionId]);
$indicators = $indicatorsStmt->fetchAll();

$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

// ── AUTO-START: create the cycle the moment today enters the Self-Assessment window ──
if (!$cycle && isWithinAssessmentWindow($assessmentWindow) && $assessmentWindow['start']) {
  try {
    $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())")
      ->execute([$syId, $schoolId]);
    $newCycleId = $db->lastInsertId();
    $dimIds = $db->query("SELECT dimension_id FROM sbm_dimensions")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dimIds as $dId) {
      $db->prepare("INSERT IGNORE INTO sbm_dimension_scores (cycle_id, school_id, dimension_id, raw_score, max_score, percentage) VALUES (?, ?, ?, 0, 0, 0)")
        ->execute([$newCycleId, $schoolId, $dId]);
    }
    logActivity('start_assessment', 'self_assessment', "Auto-started SBM assessment cycle (Self-Assessment window opened).");

    $cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE cycle_id=?");
    $cycle->execute([$newCycleId]);
    $cycle = $cycle->fetch();
  } catch (\PDOException $e) {
    // Race condition guard: another request may have just created it
    if ($e->getCode() === '23000') {
      $cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycle->execute([$schoolId, $syId]);
      $cycle = $cycle->fetch();
    } else {
      throw $e;
    }
  }
}

$responses = [];
if ($cycle) {
  $r = $db->prepare("SELECT * FROM sbm_responses WHERE cycle_id=?");
  $r->execute([$cycle['cycle_id']]);
  foreach ($r->fetchAll() as $row)
    $responses[$row['indicator_id']] = $row;
}

$grouped = [];
foreach ($indicators as $ind)
  $grouped[$ind['dimension_no']][] = $ind;

$ratingLabels = [1 => 'Not yet Manifested', 2 => 'Rarely Manifested', 3 => 'Frequently Manifested', 4 => 'Always manifested'];
$ratingColors = [1 => '#DC2626', 2 => '#D97706', 3 => '#2563EB', 4 => '#16A34A'];

$isLocked = $cycle && in_array($cycle['status'], ['submitted', 'validated', 'finalized']);
$isCoordinator = ($_SESSION['role'] === 'sbm_coordinator');
// Coordinator is always effectively locked (view-only)
$canEdit = !$isLocked && !$isCoordinator;

// SH rates: SH_ONLY + SH_TEACHER + SH_EXT + SH_TCH_EXT (= SH_RATEABLE_CODES)
$shIndicators = array_filter($indicators, fn($i) => in_array($i['indicator_code'], SH_RATEABLE_CODES));
$shResponded = count(array_filter($shIndicators, fn($i) => isset($responses[$i['indicator_id']])));
$shTotal = count($shIndicators);


$totalDone = count($responses);
$totalCount = count($indicators);
$shCount = count($shIndicators);
$teacherCount = count(array_filter($indicators, fn($i) => in_array($i['indicator_code'], TEACHER_INDICATOR_CODES)));

// ── Teacher list: search + pagination ──────────────────────
$teacherSearch = trim($_GET['ts'] ?? '');          // search query
$teacherPage = max(1, (int) ($_GET['tp'] ?? 1));  // current page
$teacherPerPage = 10;                               // rows per page

$pendingTeachers = [];
$totalTeachers = 0;
$submittedTeachers = 0;
$pendingCount = 0;
$teacherTotalPages = 1;

if ($cycle) {
  // ── Global totals (always all teachers, ignoring search) ──
  $totStmt = $db->prepare("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN ts.status = 'submitted' THEN 1 ELSE 0 END) AS submitted
        FROM users u
        LEFT JOIN teacher_submissions ts
            ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
    ");
  $totStmt->execute([$cycle['cycle_id'], $schoolId]);
  $totRow = $totStmt->fetch();
  $totalTeachers = (int) $totRow['total'];
  $submittedTeachers = (int) $totRow['submitted'];
  $pendingCount = $totalTeachers - $submittedTeachers;

  // ── Filtered count (for pagination denominator) ────────────
  $searchParam = "%{$teacherSearch}%";
  $filtStmt = $db->prepare("
        SELECT COUNT(*) FROM users u
        LEFT JOIN teacher_submissions ts
            ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
          AND (? = '%%' OR u.full_name LIKE ? OR u.username LIKE ?)
    ");
  $filtStmt->execute([
    $cycle['cycle_id'],
    $schoolId,
    $searchParam,
    $searchParam,
    $searchParam
  ]);
  $filteredTotal = (int) $filtStmt->fetchColumn();
  $teacherTotalPages = max(1, (int) ceil($filteredTotal / $teacherPerPage));
  $teacherPage = min($teacherPage, $teacherTotalPages);
  $offset = ($teacherPage - 1) * $teacherPerPage;

  // ── Paginated page fetch ───────────────────────────────────
  $pageStmt = $db->prepare("
        SELECT u.user_id, u.full_name, u.email, u.username,
               ts.status AS sub_status,
               ts.submitted_at,
               ts.response_count
        FROM users u
        LEFT JOIN teacher_submissions ts
            ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
          AND (? = '%%' OR u.full_name LIKE ? OR u.username LIKE ?)
        ORDER BY
            CASE WHEN ts.status = 'submitted' THEN 1 ELSE 0 END ASC,
            u.full_name ASC
        LIMIT ? OFFSET ?
    ");
  $pageStmt->execute([
    $cycle['cycle_id'],
    $schoolId,
    $searchParam,
    $searchParam,
    $searchParam,
    $teacherPerPage,
    $offset
  ]);
  $pendingTeachers = $pageStmt->fetchAll();
}

// Teacher response data (for teacher indicator cards)
$teacherData = [];
if ($cycle) {
  try {
    $tr = $db->prepare("
            SELECT tr.indicator_id,
                   ROUND(AVG(tr.rating), 2) avg_rating,
                   COUNT(tr.tr_id)          teacher_count,
                   GROUP_CONCAT(u.full_name ORDER BY u.full_name SEPARATOR ', ') teachers
            FROM teacher_responses tr
            JOIN users u ON tr.teacher_id = u.user_id
            WHERE tr.cycle_id = ?
            GROUP BY tr.indicator_id
        ");
    $tr->execute([$cycle['cycle_id']]);
    foreach ($tr->fetchAll() as $row)
      $teacherData[$row['indicator_id']] = $row;
  } catch (Exception $e) {
    // teacher_responses table may not exist yet — safe to ignore
  }
}

$pageTitle = $isCoordinator ? 'Intervention Matrix' : 'SBM Self-Assessment';
$activePage = 'self_assessment.php';

// Shared indicator IDs that count as "done" via teacher ratings alone
// (SH_TEACHER, SH_TCH_EXT, TCH_EXT — no SH response needed)
$sharedCodes = array_unique(array_merge(TEACHER_ONLY_CODES, SH_TEACHER_CODES, SH_TCH_EXT_CODES, TCH_EXT_CODES));
$sharedDone = [];
foreach ($indicators as $ind) {
  if (
    in_array($ind['indicator_code'], $sharedCodes) &&
    isset($teacherData[$ind['indicator_id']]) &&
    (int) $teacherData[$ind['indicator_id']]['teacher_count'] > 0
  ) {
    $sharedDone[$ind['indicator_id']] = true;
  }
}
// Recalculate totalDone: SH responses + shared indicators with teacher ratings (not double-counted)
$totalDone = count($responses) + count(array_filter(
  array_keys($sharedDone),
  fn($id) => !isset($responses[$id])
));

include __DIR__ . '/../includes/header.php';
?>

<style>

  /* ══════════════════════════════════════════════════════
   DIMENSION ACCORDION
══════════════════════════════════════════════════════ */
  .dim-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    background: var(--white);
    border: 1px solid var(--n200);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    cursor: pointer;
    user-select: none;
    transition: background .15s;
  }

  .dim-header:hover {
    background: var(--n50);
  }

  .dim-chevron {
    font-size: 20px;
    color: var(--n300);
    transition: transform .25s ease;
    flex-shrink: 0;
    margin-left: 4px;
  }

  .dim-body {
    padding-top: 8px;
    margin-bottom: 20px;
    max-height: 6000px;
    opacity: 1;
    overflow: hidden;
    transition: max-height .35s ease, opacity .3s ease, margin-bottom .35s ease, padding-top .35s ease;
  }

  .dim-body.collapsed {
    max-height: 0;
    opacity: 0;
    margin-bottom: 0;
    padding-top: 0;
    overflow: hidden;
  }

  .dim-wrap {
    margin-bottom: 6px;
  }

  /* Dim hidden by filter */
  .dim-wrap.filter-hidden {
    display: none;
  }

  /* ══════════════════════════════════════════════════════
   INDICATOR CARDS
══════════════════════════════════════════════════════ */
  .indicator-row {
    background: var(--white);
    border: 1px solid var(--n200);
    border-radius: var(--radius);
    padding: 14px 16px;
    margin-bottom: 8px;
    transition: border-color .2s, background .2s, opacity .2s, transform .15s;
  }

  .indicator-row.rated {
    border-color: #86EFAC;
    background: #F0FDF4;
  }

  .indicator-row.teacher-only {
    border-color: #BFDBFE;
    background: #EFF6FF;
  }

  /* Hidden by filter — smooth fade out */
  .indicator-row.filter-hidden {
    display: none;
  }

  /* Role tag on each card */
  .role-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    white-space: nowrap;
  }

  .role-tag.role-sh {
    background: #DCFCE7;
    color: #166534;
    border: 1px solid #86EFAC;
  }

  .role-tag.role-teacher {
    background: var(--blueb);
    color: var(--blue);
    border: 1px solid #BFDBFE;
  }

  /* ══════════════════════════════════════════════════════
   RATING BUTTONS
══════════════════════════════════════════════════════ */
  .rating-group {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    margin-bottom: 10px;
  }

  .rating-btn {
    padding: 7px 14px;
    border-radius: 8px;
    border: 1.5px solid var(--n200);
    background: var(--white);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    color: var(--n600);
    white-space: nowrap;
  }

  .rating-btn:hover:not(:disabled) {
    border-color: var(--n400);
    background: var(--n50);
  }

  .rating-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
  }

  .rating-btn.selected-1 {
    background: #FEE2E2;
    border-color: #DC2626;
    color: #DC2626;
  }

  .rating-btn.selected-2 {
    background: #FEF3C7;
    border-color: #D97706;
    color: #D97706;
  }

  .rating-btn.selected-3 {
    background: #DBEAFE;
    border-color: #2563EB;
    color: #2563EB;
  }

  .rating-btn.selected-4 {
    background: #DCFCE7;
    border-color: #16A34A;
    color: #16A34A;
  }

  /* ══════════════════════════════════════════════════════
   TEACHER INFO BOX
══════════════════════════════════════════════════════ */
  .teacher-info-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: var(--blueb);
    border: 1px solid #BFDBFE;
    border-radius: 8px;
    margin-top: 4px;
  }

  .teacher-info-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 8px;
    background: var(--blue);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .teacher-info-icon svg {
    width: 18px !important;
    height: 18px !important;
    stroke: #fff;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    display: block;
    flex-shrink: 0;
  }

  .teacher-info-text {
    flex: 1;
    min-width: 0;
  }

  .teacher-info-title {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--blue);
    margin-bottom: 3px;
  }

  .teacher-info-body {
    font-size: 12.5px;
    color: var(--n600);
    line-height: 1.5;
  }

  .teacher-avg-rating {
    font-size: 15px;
    font-weight: 800;
    color: var(--blue);
  }

  /* ══════════════════════════════════════════════════════
   EMPTY DIM MESSAGE
══════════════════════════════════════════════════════ */
  .dim-empty-msg {
    display: none;
    padding: 14px 16px;
    font-size: 13px;
    color: var(--n400);
    text-align: center;
    border: 1.5px dashed var(--n200);
    border-radius: var(--radius);
    margin-bottom: 8px;
  }

  .dim-empty-msg.visible {
    display: block;
  }

  /* ── Clear button ─────────────────────────────────────── */
  .clear-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1.5px solid transparent;
    background: transparent;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--n400);
    cursor: pointer;
    transition: all .15s;
    opacity: 0;
    pointer-events: none;
  }

  .clear-btn svg {
    width: 12px;
    height: 12px;
    flex-shrink: 0;
    stroke: currentColor;
  }

  /* Only visible when the card is rated */
  .indicator-row.rated .clear-btn {
    opacity: 1;
    pointer-events: all;
  }

  .clear-btn:hover {
    background: var(--redb);
    color: var(--red);
    border-color: #FECACA;
  }

  /* Clear dim button in accordion header */
  .clear-dim-btn {
    display: none;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 6px;
    border: 1.5px solid var(--n200);
    background: var(--white);
    font-size: 11px;
    font-weight: 600;
    color: var(--n500);
    cursor: pointer;
    transition: all .15s;
    flex-shrink: 0;
  }

  .clear-dim-btn svg {
    width: 12px;
    height: 12px;
    stroke: currentColor;
    flex-shrink: 0;
  }

  .clear-dim-btn:hover {
    background: var(--redb);
    color: var(--red);
    border-color: #FECACA;
  }

  /* ── Progress bar animations ─────────────────────────── */
  .prog-fill {
    transition: width .4s cubic-bezier(.4, 0, .2, 1);
  }

  @keyframes prog-complete {
    0% {
      box-shadow: 0 0 0 0 rgba(22, 163, 74, .4);
    }

    70% {
      box-shadow: 0 0 0 6px rgba(22, 163, 74, 0);
    }

    100% {
      box-shadow: 0 0 0 0 rgba(22, 163, 74, 0);
    }
  }

  .prog-complete {
    animation: prog-complete .6s ease-out forwards;
  }
</style>

<?php if ($isCoordinator): ?>
<!-- ══════════════════════════════════════════════════════════
     COORDINATOR VIEW — Intervention Monitoring Table
══════════════════════════════════════════════════════════ -->
<style>
  .intervention-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 10px;
  }
  .intervention-header h2 {
    font-size: 17px;
    font-weight: 800;
    color: var(--n800);
    margin: 0;
  }
  .intervention-header p {
    font-size: 13px;
    color: var(--n500);
    margin: 2px 0 0;
  }
  .dim-intervention-block {
    margin-bottom: 20px;
  }
  .dim-intervention-title {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    border-radius: var(--radius) var(--radius) 0 0;
    background: #F8FAFC;
    border: 1px solid #E5E7EB;
    border-bottom: 1px solid #E5E7EB;
  }
  .dim-intervention-title .dim-no-badge {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
    opacity: .85;
  }
  .dim-intervention-title .dim-name {
    font-size: 12.5px;
    font-weight: 700;
    color: #1F2937;
    letter-spacing: .01em;
  }
  .dim-intervention-title .dim-label {
    font-size: 10.5px;
    font-weight: 500;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 1px;
  }
  .dim-intervention-title .dim-score-pill {
    margin-left: auto;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
  }
  .dim-intervention-title .dim-ind-count {
    font-size: 11.5px;
    color: var(--n400);
    margin-left: 6px;
    white-space: nowrap;
  }
  .intervention-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 0 0 var(--radius) var(--radius);
    overflow: hidden;
    font-size: 12.5px;
  }
  .intervention-table th {
    background: #fff;
    color: #9CA3AF;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: 6px 14px;
    text-align: left;
    border-bottom: 1px solid #E5E7EB;
  }
  .intervention-table td {
    padding: 7px 14px;
    border-bottom: 1px solid #E5E7EB;
    vertical-align: middle;
    color: #374151;
    line-height: 1.4;
  }
  .intervention-table tr:last-child td {
    border-bottom: none;
  }
  .intervention-table tr:hover td {
    background: #F9FAFB;
  }
  .ind-code-badge {
    display: inline-block;
    font-family: 'Roboto Mono', 'Courier New', monospace;
    font-size: 10.5px;
    font-weight: 500;
    color: #6B7280;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    padding: 1px 6px;
    border-radius: 4px;
    white-space: nowrap;
    letter-spacing: .02em;
  }
  .score-bar-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .score-val {
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
    min-width: 30px;
    text-align: right;
    color: #6B7280;
    font-variant-numeric: tabular-nums;
  }
  .score-bar-bg {
    width: 64px;
    flex-shrink: 0;
    height: 3px;
    background: #E5E7EB;
    border-radius: 999px;
    overflow: hidden;
  }
  .score-bar-fill {
    height: 100%;
    border-radius: 999px;
    opacity: 0.85;
  }
  .intervention-level-pill {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    padding: 3px 9px;
    border-radius: 5px;
    letter-spacing: .01em;
  }
  .level-critical { background: #FFF0F0; color: #C92A2A; }
  .level-low      { background: #FFFBEB; color: #B45309; }
  .level-good     { background: #F0FBF4; color: #1E824C; }
  .no-cycle-notice {
    text-align: center;
    padding: 60px 20px;
    color: var(--n400);
  }
  .no-cycle-notice svg {
    width: 40px; height: 40px;
    stroke: var(--n300);
    margin-bottom: 12px;
  }
  .all-good-row td {
    text-align: center;
    color: var(--g600);
    font-size: 13px;
    font-weight: 600;
    padding: 16px 14px;
    background: #F0FDF4;
  }
  .filter-bar {
    display: flex;
    align-items: center;
  }
  .seg-control {
    display: inline-flex;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 7px;
    padding: 3px;
    gap: 1px;
  }
  .filter-btn {
    padding: 4px 11px;
    border-radius: 5px;
    border: none;
    background: transparent;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748B;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
  }
  .filter-btn:hover {
    color: #1E293B;
    background: #E2E8F0;
  }
  .filter-btn.active {
    background: #fff;
    color: #1E293B;
    box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 0 0 1px #E2E8F0;
  }
</style>

<?php
// ── Build per-indicator score from responses + teacher data ──
// Score = SH rating if available, else teacher avg, converted to %
$indScores = [];
foreach ($indicators as $ind) {
  $iid = $ind['indicator_id'];
  $shRating   = $responses[$iid]['rating']             ?? null;
  $tchAvg     = $teacherData[$iid]['avg_rating']       ?? null;

  if ($shRating !== null) {
    $score = round(($shRating / 4) * 100, 1);
    $src   = 'SH';
  } elseif ($tchAvg !== null) {
    $score = round(($tchAvg / 4) * 100, 1);
    $src   = 'Teacher Avg';
  } else {
    $score = null;
    $src   = 'No data';
  }
  $indScores[$iid] = ['score' => $score, 'src' => $src, 'sh' => $shRating, 'tch' => $tchAvg];
}

// ── Dimension-level score (avg of its indicators that have scores) ──
$dimScores = [];
foreach ($grouped as $dimNo => $inds) {
  $scored = array_filter(array_map(fn($i) => $indScores[$i['indicator_id']]['score'], $inds), fn($s) => $s !== null);
  $dimScores[$dimNo] = count($scored) > 0 ? round(array_sum($scored) / count($scored), 1) : null;
}

function interventionLevel(float $pct): array {
  if ($pct <= 50.0) return ['label' => 'Critical Intervention', 'class' => 'level-critical',  'dot' => '🔴'];
  if ($pct <  62.5) return ['label' => 'Improvement Focus',     'class' => 'level-low',        'dot' => '🟠'];
  return                   ['label' => 'Acceptable/Sustained',  'class' => 'level-good',       'dot' => '🟢'];
}

function scoreBarColor(float $pct): string {
  if ($pct <= 50.0) return '#C92A2A';
  if ($pct <  62.5) return '#D97706';
  return '#1E824C';
}

// Tier thresholds (score %)
define('TIER_CRITICAL_MAX', 50.0);
define('TIER_IMPROVE_MAX',  62.5);
?>

<div class="intervention-header">
  <div>
    <?= $cycle ? '' : '<p><strong style="color:var(--red);">No active assessment cycle.</strong></p>' ?>
  </div>
  <div class="filter-bar">
    <div class="seg-control">
      <button class="filter-btn active" onclick="filterIntervention('all',this)">All</button>
      <?php foreach ($grouped as $dimNo => $inds): ?>
        <button class="filter-btn" onclick="filterIntervention(<?= $dimNo ?>,this)">D<?= $dimNo ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php foreach ($grouped as $dimNo => $inds):
  $dim         = $inds[0];
  $dimScore    = $dimScores[$dimNo];
  $dimLevel    = $dimScore !== null ? interventionLevel($dimScore) : null;
  $dimColor    = $dim['color_hex'];

  // Sort all indicators: Critical first, Improvement Focus second, Acceptable/Sustained last
  $allInds = $inds;
  usort($allInds, function($a, $b) use ($indScores) {
    $sa = $indScores[$a['indicator_id']]['score'] ?? -1;
    $sb = $indScores[$b['indicator_id']]['score'] ?? -1;
    $ta = $sa <= TIER_CRITICAL_MAX ? 0 : ($sa < TIER_IMPROVE_MAX ? 1 : 2);
    $tb = $sb <= TIER_CRITICAL_MAX ? 0 : ($sb < TIER_IMPROVE_MAX ? 1 : 2);
    return $ta <=> $tb;
  });

  // Count per tier for header summary
  $tierCounts = ['critical' => 0, 'improve' => 0, 'sustained' => 0];
  foreach ($inds as $i) {
    $s = $indScores[$i['indicator_id']]['score'] ?? null;
    if ($s === null || $s <= TIER_CRITICAL_MAX)  $tierCounts['critical']++;
    elseif ($s < TIER_IMPROVE_MAX)               $tierCounts['improve']++;
    else                                          $tierCounts['sustained']++;
  }
?>
<div class="dim-intervention-block" data-dim-filter="<?= $dimNo ?>">
  <div class="dim-intervention-title" style="border-left: 4px solid <?= e($dimColor) ?>;">
    <div class="dim-no-badge" style="background:<?= e($dimColor) ?>22; color:<?= e($dimColor) ?>;">
      <?= $dimNo ?>
    </div>
    <div>
      <div class="dim-label">Dimension <?= $dimNo ?></div>
      <div class="dim-name"><?= e($dim['dimension_name']) ?></div>
    </div>
    <?php if ($dimScore !== null):
      $trendIcon = $dimScore >= 62.5
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="#1E824C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="#C92A2A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>';
      $trendColor = $dimScore >= 62.5 ? '#1E824C' : '#C92A2A';
    ?>
      <span style="margin-left:auto;display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:<?= $trendColor ?>;">
        <?= $trendIcon ?> <?= $dimScore ?>%
      </span>
    <?php else: ?>
      <span style="margin-left:auto;font-size:12px;color:#9CA3AF;">No data</span>
    <?php endif; ?>
    <span class="dim-ind-count" style="display:flex;gap:12px;align-items:center;font-size:11.5px;color:var(--n500);">
      <?php if ($tierCounts['critical'] > 0): ?>
        <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#C92A2A;margin-right:4px;vertical-align:middle;opacity:.7;"></span><?= $tierCounts['critical'] ?> Critical</span>
      <?php endif; ?>
      <?php if ($tierCounts['improve'] > 0): ?>
        <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#D97706;margin-right:4px;vertical-align:middle;opacity:.7;"></span><?= $tierCounts['improve'] ?> Improvement</span>
      <?php endif; ?>
      <?php if ($tierCounts['sustained'] > 0): ?>
        <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#1E824C;margin-right:4px;vertical-align:middle;opacity:.7;"></span><?= $tierCounts['sustained'] ?> Sustained</span>
      <?php endif; ?>
    </span>
  </div>

  <table class="intervention-table">
    <thead>
      <tr>
        <th style="width:70px;">Code</th>
        <th>Indicator</th>
        <th style="width:160px;">Score</th>
        <th style="width:100px;">Level</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allInds)): ?>
        <tr class="all-good-row">
          <td colspan="5">No indicators found for this dimension.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($allInds as $ind):
          $iid   = $ind['indicator_id'];
          $data  = $indScores[$iid];
          $score = $data['score'];
          $src   = $data['src'];
          $level = $score !== null ? interventionLevel($score) : ['label'=>'No data','class'=>'level-low','dot'=>'⚪'];
          $color = $score !== null ? scoreBarColor($score) : '#CBD5E1';
          $pct   = $score ?? 0;
        ?>
        <tr>
          <td><span class="ind-code-badge"><?= e($ind['indicator_code']) ?></span></td>
          <td style="font-size:12px; line-height:1.5; color:#111827; font-weight:500;"><?= e($ind['indicator_text']) ?></td>
          <td>
            <?php if ($score !== null): ?>
            <div class="score-bar-wrap">
              <div class="score-bar-bg">
                <div class="score-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
              </div>
              <span class="score-val" style="color:<?= $color ?>;">
                <?= $score ?>%
                <span title="Source: <?= e($src) ?>. Note: Dashboard trend shows combined SH + Teacher average, which may differ from this single-rater score." style="cursor:help;display:inline-flex;align-items:center;margin-left:3px;vertical-align:middle;color:#9CA3AF;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
              </span>
            </div>
            <?php else: ?>
              <span style="font-size:12px;color:var(--n400);">— not rated</span>
            <?php endif; ?>
          </td>
          <td><span class="intervention-level-pill <?= $level['class'] ?>"><?= $level['label'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<script>
function filterIntervention(dim, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.dim-intervention-block').forEach(block => {
    if (dim === 'all' || parseInt(block.dataset.dimFilter) === dim) {
      block.style.display = '';
    } else {
      block.style.display = 'none';
    }
  });
}
</script>

<?php else: // ── non-coordinator (school head) full view ── ?>
<?php
// ── Build per-indicator / per-dimension scores for real-time % display ──
$indScores = [];
foreach ($indicators as $ind) {
  $iid = $ind['indicator_id'];
  $shRating = $responses[$iid]['rating'] ?? null;
  $tchAvg   = $teacherData[$iid]['avg_rating'] ?? null;
  if ($shRating !== null) {
    $score = round(($shRating / 4) * 100, 1);
  } elseif ($tchAvg !== null) {
    $score = round(($tchAvg / 4) * 100, 1);
  } else {
    $score = null;
  }
  $indScores[$iid] = ['score' => $score];
}
$dimScores = [];
foreach ($grouped as $dimNo => $inds) {
  $scored = array_filter(array_map(fn($i) => $indScores[$i['indicator_id']]['score'], $inds), fn($s) => $s !== null);
  $dimScores[$dimNo] = count($scored) > 0 ? round(array_sum($scored) / count($scored), 1) : null;
}
?>

<!-- ── PAGE HEAD ──────────────────────────────────────────── -->
<div class="page-head" style="justify-content:flex-end;margin-bottom:16px;">
  <div class="page-head-actions">
    <?php if (!$cycle): ?>
      <?php if (hasAccess('start_assessment')): ?>
        <button class="btn btn-primary" onclick="openModal('mStartAssessment')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" style="width:16px;height:16px;margin-right:6px;">
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
          Start Assessment
        </button>
      <?php endif; ?>
    <?php elseif (!$isLocked): ?>
      <!-- top Submit Assessment button removed; bottom button remains -->
    <?php else: ?>
      <span class="pill pill-<?= e($cycle['status']) ?>" style="font-size:13px;padding:6px 14px;">
        <?= ucfirst(str_replace('_', ' ', $cycle['status'])) ?>
      </span>
    <?php endif; ?>
  </div>
</div>

<?php if ($isLocked): ?>
  <div class="alert alert-info" style="margin-bottom:16px;">
    <?= svgIcon('info') ?> This assessment has been <strong><?= e($cycle['status']) ?></strong>. Responses are read-only.
  </div>
<?php endif; ?>

<?php if (!$cycle): ?>
  <div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 60px 20px; text-align: center;">
      <div
        style="width: 72px; height: 72px; border-radius: 50%; background: var(--blueb); color: var(--blue); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round" style="width: 32px; height: 32px; margin-left:4px;">
          <polygon points="5 3 19 12 5 21 5 3"></polygon>
        </svg>
      </div>
      <h3 style="font-size: 22px; font-weight: 800; color: var(--n800); margin-bottom: 12px;">Start New Assessment Cycle
      </h3>
      <p style="font-size: 15px; color: var(--n500); max-width: 480px; margin: 0 auto 30px; line-height: 1.6;">There is
        currently no active assessment cycle for this school year.<?php if (hasAccess('start_assessment')): ?> Click the
          button below to explicitly start the assessment. This will instantly make the indicators available for all active
          teachers to answer.<?php else: ?> Please wait for the School Head to start the assessment cycle.<?php endif; ?>
      </p>
      <?php if (hasAccess('start_assessment')): ?>
        <button class="btn btn-primary" onclick="openModal('mStartAssessment')"
          style="padding: 12px 28px; font-size: 15.5px;">
          Start Assessment Cycle
        </button>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>

  <!-- ── STICKY DIMENSION STEP PROGRESS ────────────────────── -->
  <div id="dimTabs" style="display:flex;gap:6px;margin-bottom:18px;
            position:sticky;top:60px;z-index:40;
            background:var(--n50);padding:8px 0;">
    <?php foreach ($grouped as $dimNo => $inds): ?>
      <?php
      $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']]) || (isTeacherHandled($i['indicator_code'] ?? '') && isset($sharedDone[$i['indicator_id']]))));
      $dimTotal = count($inds);
      $dimFull = $dimDone === $dimTotal;
      ?>
      <a href="#dim<?= $dimNo ?>" id="dimTab<?= $dimNo ?>" data-done="<?= $dimDone ?>" data-total="<?= $dimTotal ?>"
            style="flex:1;min-width:0;text-decoration:none;display:flex;flex-direction:column;gap:6px;">
        <div id="dimBar<?= $dimNo ?>" style="height:4px;border-radius:2px;
              background:<?= $dimFull ? 'var(--n900)' : 'var(--n200)' ?>;
              transition:background .3s;"></div>
        <div id="dimLabel<?= $dimNo ?>" style="font-size:12px;font-weight:700;
              color:<?= $dimFull ? 'var(--n900)' : 'var(--n400)' ?>;
              transition:color .3s;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          D<?= $dimNo ?>
          <span id="dimTabCount<?= $dimNo ?>" style="font-weight:500;opacity:.7;">(<?= $dimDone ?>/<?= $dimTotal ?>)</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ── INDICATORS BY DIMENSION ───────────────────────────── -->
  <?php foreach ($grouped as $dimNo => $inds): ?>
    <?php
    $dim = $inds[0];
    $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']]) || (isTeacherHandled($i['indicator_code'] ?? '') && isset($sharedDone[$i['indicator_id']]))));
    $allDone = $dimDone === count($inds);
    $dimShCount = count(array_filter($inds, fn($i) => !in_array($i['indicator_code'], TEACHER_INDICATOR_CODES)));
    $dimTchCount = count($inds) - $dimShCount;
    ?>
    <div class="dim-wrap" id="dim<?= $dimNo ?>" data-dim="<?= $dimNo ?>" data-dim-db-id="<?= $dim['dimension_id'] ?>"
      data-sh-count="<?= $dimShCount ?>" data-teacher-count="<?= $dimTchCount ?>">

      <div class="dim-header" onclick="toggleDim(<?= $dimNo ?>)" style="border-left:4px solid <?= e($dim['color_hex']) ?>;">

        <div style="width:38px;height:38px;border-radius:9px;
                background:<?= e($dim['color_hex']) ?>22;
                display:flex;align-items:center;justify-content:center;
                font-size:15px;font-weight:800;
                color:<?= e($dim['color_hex']) ?>;flex-shrink:0;">
          <?= $dimNo ?>
        </div>

        <div style="flex:1;min-width:0;">
          <div style="font-size:14.5px;font-weight:700;color:var(--n900);">
            Dimension <?= $dimNo ?>: <?= e($dim['dimension_name']) ?>
          </div>
          <div style="font-size:12px;color:var(--n400);margin-top:2px;" id="dimSubtitle<?= $dimNo ?>">
            <?= $dimDone ?>/<?= count($inds) ?> indicators rated
          </div>
        </div>

        <div style="font-size:13px;font-weight:700;color:<?= e($dim['color_hex']) ?>;margin-right:6px;">
          <?= $dimScores[$dimNo] !== null ? number_format($dimScores[$dimNo], 1) . '%' : '—' ?>
        </div>

        <?php if ($allDone): ?>
          <span style="font-size:11px;font-weight:700;color:#16A34A;
                 background:#DCFCE7;border:1px solid #86EFAC;
                 border-radius:999px;padding:3px 10px;flex-shrink:0;">
            Complete
          </span>
        <?php else: ?>
          <span style="font-size:11px;font-weight:600;color:var(--n500);
                 background:var(--n100);border-radius:999px;
                 padding:3px 10px;flex-shrink:0;" id="dimLeft<?= $dimNo ?>">
            <?= count($inds) - $dimDone ?> left
          </span>
        <?php endif; ?>

        <?php if (!$isLocked): ?>
          <button class="clear-dim-btn" id="clearDimBtn<?= $dimNo ?>"
            onclick="event.stopPropagation();confirmClearDim(<?= $dimNo ?>)" title="Clear all ratings in this dimension"
            style="<?= $dimDone > 0 ? 'display:inline-flex;' : 'display:none;' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" />
            </svg>
            Clear Dim
          </button>
        <?php endif; ?>
        <span class="dim-chevron" id="dimChevron<?= $dimNo ?>">▾</span>
      </div><!-- /.dim-header -->

      <div class="dim-body" id="dimBody<?= $dimNo ?>">

        <!-- Shown when filter hides all cards in this dimension -->
        <div class="dim-empty-msg" id="dimEmpty<?= $dimNo ?>">
          No indicators match the current filter in this dimension.
        </div>

        <?php foreach ($inds as $ind): ?>
          <?php
          $resp = $responses[$ind['indicator_id']] ?? null;
          $rated = $resp !== null;
          $isTeacherCard = isTeacherHandled($ind['indicator_code'] ?? '');
          $role = $isTeacherCard ? 'teacher' : 'sh';
          $showTeacherInfoAlso = in_array($ind['indicator_code'] ?? '', SH_SEES_TEACHER_CODES);
          $trData = $teacherData[$ind['indicator_id']] ?? null;
          ?>

          <?php
          $isSH = in_array($ind['indicator_code'], SH_RATEABLE_CODES);
          $isTeacher = in_array($ind['indicator_code'], TEACHER_INDICATOR_CODES);
          ?>
          <div class="indicator-row <?= $rated ? 'rated' : '' ?> <?= $isTeacherCard ? 'teacher-only' : '' ?>"
            id="row<?= $ind['indicator_id'] ?>" data-sh="<?= $isSH ? 1 : 0 ?>" data-teacher="<?= $isTeacher ? 1 : 0 ?>"
            data-code="<?= e($ind['indicator_code']) ?>">

            <!-- Top row: code + role tag + saved badge -->
            <div class="flex-cb" style="margin-bottom:6px;">
              <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">

                <span style="font-family:monospace;font-size:11px;font-weight:700;
                       color:var(--n500);letter-spacing:.5px;text-transform:uppercase;">
                  <?= e($ind['indicator_code']) ?>
                </span>

                <?php if ($isTeacherCard): ?>
                  <span class="role-tag role-teacher">
                    <span style="display:inline-flex;width:11px;height:11px;flex-shrink:0;">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="width:11px;height:11px;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                      </svg>
                    </span>
                    Teacher Indicator
                  </span>
                <?php else: ?>
                  <span class="role-tag role-sh">
                    <span style="display:inline-flex;width:11px;height:11px;flex-shrink:0;">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="width:11px;height:11px;">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M20 21a8 8 0 1 0-16 0" />
                      </svg>
                    </span>
                    School Head
                  </span>
                <?php endif; ?>

              </div>

              <?php if (!$isTeacherCard): ?>
                <div style="display:flex;align-items:center;gap:6px;">
                  <span id="savedBadge<?= $ind['indicator_id'] ?>" style="font-size:11px;color:var(--g600);font-weight:600;">
                    <?= $rated ? 'Saved' : '' ?>
                  </span>
                  <?php if ($canEdit): ?>
                    <button class="clear-btn" id="clearBtn<?= $ind['indicator_id'] ?>"
                      onclick="confirmClear(<?= $ind['indicator_id'] ?>)" title="Clear this rating">
                      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" />
                      </svg>
                      Clear
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div><!-- /.flex-cb -->

            <!-- Indicator text -->
            <div style="font-size:13.5px;font-weight:600;color:var(--n900);
                  margin-bottom:4px;line-height:1.5;">
              <?= e($ind['indicator_text']) ?>
            </div>

            <!-- MOV -->
            <div style="font-size:12px;color:var(--n400);margin-bottom:12px;line-height:1.5;">
              📎 MOV: <?= e($ind['mov_guide']) ?>
            </div>

            <?php if (!$isTeacherCard): ?>
              <!-- SCHOOL HEAD RATING -->
              <div class="rating-group" id="ratingGroup<?= $ind['indicator_id'] ?>">
                <?php foreach ([1, 2, 3, 4] as $r): ?>
                  <button <?= !$canEdit ? 'disabled' : '' ?> type="button"
                    class="rating-btn <?= $resp && $resp['rating'] == $r ? 'selected-' . $r : '' ?>"
                    data-ind="<?= $ind['indicator_id'] ?>" data-rating="<?= $r ?>"
                    onclick="selectRating(<?= $ind['indicator_id'] ?>,<?= $r ?>)">
                    <?= $r ?> — <?= $ratingLabels[$r] ?>
                  </button>
                <?php endforeach; ?>
              </div>

              <textarea class="fc" id="evidence<?= $ind['indicator_id'] ?>" rows="2"
                placeholder="Describe evidence or attach MOV reference…" <?= !$canEdit ? 'disabled' : '' ?>
                onblur="saveResponse(<?= $ind['indicator_id'] ?>)"><?= e($resp['evidence_text'] ?? '') ?></textarea>
              <div id="attachWidget_<?= $ind['indicator_id'] ?>"></div>

            <?php endif; ?>

            <?php
            if ($isTeacherCard || $showTeacherInfoAlso):
              ?>
              <!-- TEACHER INFO BOX -->
              <div class="teacher-info-box">
                <div class="teacher-info-icon">
                  <svg viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 
                     0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </div>
                <div class="teacher-info-text" style="flex:1;">
                  <?php if ($trData && (int) $trData['teacher_count'] > 0): ?>
                    <div class="teacher-info-title">
                      Teacher Average:
                      <div class="teacher-avg-rating">
                        <?= $trData['avg_rating'] ?>/4.00
                      </div>
                    </div>
                    <div class="teacher-info-body">
                      <?= (int) $trData['teacher_count'] ?> teacher response(s)
                    </div>
                  <?php else: ?>
                    <div class="teacher-info-title">
                      <?= 'Teacher Indicator' ?>
                    </div>
                    <div class="teacher-info-body">
                      <?= 'No teacher input yet. Teachers rate this in their portal.' ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

            <?php endif; ?>
          </div><!-- /.indicator-row -->

        <?php endforeach; ?>
      </div><!-- /.dim-body -->
    </div><!-- /.dim-wrap -->
  <?php endforeach; ?>

  <!-- ── SUBMIT BUTTON / VIEW-ONLY NOTICE ─────────────────── -->
  <div style="text-align:center;padding:20px 0;margin-top:8px;">
    <?php if ($isCoordinator): ?>
      <div
        style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:var(--brand-50);border:1.5px solid var(--brand-200);border-radius:10px;font-size:13.5px;font-weight:600;color:var(--brand-700);">
        <?= svgIcon('eye') ?> View-Only Mode — Coordinators can review but not modify the assessment.
      </div>
    <?php elseif (!$isLocked): ?>
      <button class="btn btn-primary" style="padding:12px 32px;font-size:15px;" onclick="submitAssessment()">
        <?= svgIcon('check') ?> Submit Self-Assessment
      </button>
    <?php endif; ?>
  </div>

<?php endif; // END of !$cycle check ?>

<script>
  // ── State ──────────────────────────────────────────────────
  let currentRatings = <?= json_encode(array_map(fn($r) => $r['rating'], $responses)) ?>;
  let currentFilter = 'all'; // 'all' | 'sh' | 'teacher' — no active toggle UI yet, defaults to showing everything

  const TEACHER_ONLY_CODES_JS = new Set(<?= json_encode(TEACHER_ONLY_CODES) ?>);
  const TCH_EXT_CODES_JS = new Set(<?= json_encode(TCH_EXT_CODES) ?>);
  const TEACHER_HANDLED_CODES = new Set([...TEACHER_ONLY_CODES_JS, ...TCH_EXT_CODES_JS]);

  // Progress tracking state (mutable as user rates)
  const progress = {
    shDone: <?= (int) ($shResponded ?? 0) ?>,
    shTotal: <?= (int) ($shTotal ?? 0) ?>,
    allDone: <?= $totalDone ?>,
    allTotal: <?= $totalCount ?>,
    ratings: { 1: <?= count(array_filter($responses, fn($x) => $x['rating'] == 1)) ?>, 2: <?= count(array_filter($responses, fn($x) => $x['rating'] == 2)) ?>, 3: <?= count(array_filter($responses, fn($x) => $x['rating'] == 3)) ?>, 4: <?= count(array_filter($responses, fn($x) => $x['rating'] == 4)) ?> },
    // Track previous rating per indicator so we can adjust breakdown on re-rate
    prevRatings: Object.assign({}, <?= json_encode(array_map(fn($r) => $r['rating'], $responses)) ?>)
  };

  // ── Live progress updater ──────────────────────────────────
  function updateProgress(indId, newRating, isTeacher, isNewResponse) {
    const prevRating = progress.prevRatings[indId] ?? null;

    // Adjust all-roles counter
    if (isNewResponse) {
      progress.allDone++;
    }

    // Adjust SH counter
    if (!isTeacher && isNewResponse) {
      progress.shDone++;
    }

    // Adjust rating breakdown
    if (prevRating && prevRating !== newRating) {
      progress.ratings[prevRating] = Math.max(0, progress.ratings[prevRating] - 1);
      progress.ratings[newRating] = (progress.ratings[newRating] || 0) + 1;
    } else if (!prevRating) {
      progress.ratings[newRating] = (progress.ratings[newRating] || 0) + 1;
    }
    progress.prevRatings[indId] = newRating;



    // ── Update dimension tab ──
    updateDimTab(indId);
  }

  function updateDimTab(indId) {
    // Find which dim-wrap contains this indicator row
    const row = document.getElementById('row' + indId);
    if (!row) return;
    const dimWrap = row.closest('.dim-wrap');
    if (!dimWrap) return;
    const dimNo = dimWrap.dataset.dim;

    // Count rated cards inside this dim
    const allCards = dimWrap.querySelectorAll('.indicator-row');
    const ratedCards = dimWrap.querySelectorAll('.indicator-row.rated');
    const done = ratedCards.length;
    const total = allCards.length;

    // Update tab text
    const tabCount = document.getElementById('dimTabCount' + dimNo);
    const tab = document.getElementById('dimTab' + dimNo);
    if (tabCount) tabCount.textContent = `(${done}/${total})`;

    refreshDimensionMetrics(dimNo);
  }

  function refreshDimensionMetrics(dimNo) {
    const dimWrap = document.getElementById('dim' + dimNo);
    if (!dimWrap) return;

    const mode = currentFilter;
    const allCards = dimWrap.querySelectorAll('.indicator-row');
    const visibleCards = Array.from(allCards).filter(c => !c.classList.contains('filter-hidden'));
    const ratedVisible = visibleCards.filter(c => c.classList.contains('rated')).length;
    const totalVisible = visibleCards.length;
    const leftVisible = totalVisible - ratedVisible;

    const emptyMsg = document.getElementById('dimEmpty' + dimNo);
    const tab = document.getElementById('dimTab' + dimNo);
    const tabCount = document.getElementById('dimTabCount' + dimNo);
    const subtitle = document.getElementById('dimSubtitle' + dimNo);
    const leftBadge = document.getElementById('dimLeft' + dimNo);

    if (totalVisible === 0) {
      if (emptyMsg) emptyMsg.classList.add('visible');
      if (tab) tab.style.display = 'none';
    } else {
      if (emptyMsg) emptyMsg.classList.remove('visible');
      if (tab) tab.style.display = '';

      if (subtitle) {
        const roleLabel = mode === 'all' ? '' : (mode === 'sh' ? 'school head ' : 'teacher ');
        subtitle.textContent = `${ratedVisible}/${totalVisible} ${roleLabel}indicator${totalVisible !== 1 ? 's' : ''} rated`;
      }

      if (leftBadge) {
        if (leftVisible === 0) {
          leftBadge.textContent = 'Complete';
          leftBadge.className = 'pill pill-success'; // Use standard pill classes for consistency
          leftBadge.style = 'font-size:11px;font-weight:700;padding:3px 10px;flex-shrink:0;';
        } else {
          leftBadge.textContent = `${leftVisible} left`;
          leftBadge.className = '';
          leftBadge.style = 'font-size:11px;font-weight:600;color:var(--n500);background:var(--n100);border-radius:999px;padding:3px 10px;flex-shrink:0;';
        }
      }

      if (tabCount) tabCount.textContent = `(${ratedVisible}/${totalVisible})`;
      const bar = document.getElementById('dimBar' + dimNo);
      const label = document.getElementById('dimLabel' + dimNo);
      if (leftVisible === 0) {
        if (bar) bar.style.background = 'var(--n900)';
        if (label) label.style.color = 'var(--n900)';
      } else {
        if (bar) bar.style.background = 'var(--n200)';
        if (label) label.style.color = 'var(--n400)';
      }
    }

    // Update dim header "Complete" badge (dimHeader section, not the top step bar)
    if (ratedVisible === totalVisible) {
      const leftBadge = document.getElementById('dimLeft' + dimNo);
      if (leftBadge) {
        leftBadge.textContent = 'Complete';
        leftBadge.style.color = '#16A34A';
        leftBadge.style.background = '#DCFCE7';
        leftBadge.style.borderColor = '#86EFAC';
      }

     // Auto-collapse removed per request — dimension stays open when complete.
    }
  }

  // ── Clear response ────────────────────────────────────────
  function confirmClear(indId) {
    const code = document.getElementById('row' + indId)?.dataset.code || indId;
    if (!confirm(`Clear the rating for indicator ${code}?\nThis will remove your saved answer.`)) return;
    clearResponse(indId);
  }

  async function clearResponse(indId) {
    const row = document.getElementById('row' + indId);
    if (!row || !row.classList.contains('rated')) return;

    const isTeacher = row.dataset.teacher === '1' && row.dataset.sh === '0';
    const prevRating = progress.prevRatings[indId] ?? null;

    const r = await apiPost('self_assessment.php', {
      action: 'clear_response',
      indicator_id: indId
    });

    if (!r.ok) { toast(r.msg, 'err'); return; }

    // Reset card UI
    row.classList.remove('rated');
    delete currentRatings[indId];

    // Reset all rating buttons to unselected
    document.querySelectorAll(`#ratingGroup${indId} .rating-btn`).forEach(btn => {
      btn.className = 'rating-btn';
    });

    // Clear evidence
    const ev = document.getElementById('evidence' + indId);
    if (ev) ev.value = '';

    // Reset saved badge
    const badge = document.getElementById('savedBadge' + indId);
    if (badge) { badge.textContent = ''; }

    // Update live progress (reverse)
    updateProgressOnClear(indId, prevRating, isTeacher);

    toast('Rating cleared.', 'ok');
  }

  function updateProgressOnClear(indId, prevRating, isTeacher) {
    // Decrease counters
    if (!isTeacher) {
      progress.shDone = Math.max(0, progress.shDone - 1);
    }
    progress.allDone = Math.max(0, progress.allDone - 1);

    // Decrease rating breakdown
    if (prevRating) {
      progress.ratings[prevRating] = Math.max(0, (progress.ratings[prevRating] || 1) - 1);
    }
    delete progress.prevRatings[indId];



    const row = document.getElementById('row' + indId);
    if (!row) return;
    const dimWrap = row.closest('.dim-wrap');
    if (!dimWrap) return;
    const dimNo = dimWrap.dataset.dim;
    const done = dimWrap.querySelectorAll('.indicator-row.rated').length;

    refreshDimensionMetrics(dimNo);

    // Show/hide clear dim button based on whether anything is still rated
    const clearDimBtn = document.getElementById('clearDimBtn' + dimNo);
    if (clearDimBtn) clearDimBtn.style.display = done > 0 ? 'inline-flex' : 'none';
  }

  // ── Clear entire dimension ─────────────────────────────────
  function confirmClearDim(dimNo) {
    const dimWrap = document.getElementById('dim' + dimNo);
    const ratedCount = dimWrap?.querySelectorAll('.indicator-row[data-sh="1"].rated').length ?? 0;
    if (ratedCount === 0) { toast('No ratings to clear in this dimension.', 'warning'); return; }

    const dimName = dimWrap?.querySelector('[style*="font-size:14.5px"]')?.textContent?.trim() ?? `Dimension ${dimNo}`;
    if (!confirm(`Clear all ${ratedCount} rating(s) in ${dimName}?\nThis cannot be undone.`)) return;

    clearDimension(dimNo);
  }

  async function clearDimension(dimNo) {
    const dimWrap = document.getElementById('dim' + dimNo);
    if (!dimWrap) return;

    const dimensionDbId = dimWrap.dataset.dimDbId || dimNo;
    const r = await apiPost('self_assessment.php', {
      action: 'clear_dimension',
      dimension_id: dimensionDbId
    });

    if (!r.ok) { toast(r.msg, 'err'); return; }

    // Reset each SH card's UI only — no progress calls yet
    dimWrap.querySelectorAll('.indicator-row[data-sh="1"]').forEach(row => {
      const indId = row.id.replace('row', '');
      if (!row.classList.contains('rated')) return;

      const prevRating = progress.prevRatings[indId] ?? null;
      row.classList.remove('rated');
      delete currentRatings[indId];

      document.querySelectorAll(`#ratingGroup${indId} .rating-btn`).forEach(btn => {
        btn.className = 'rating-btn';
      });
      const ev = document.getElementById('evidence' + indId);
      if (ev) ev.value = '';
      const badge = document.getElementById('savedBadge' + indId);
      if (badge) badge.textContent = '';

      // Accumulate progress changes (no DOM updates here)
      progress.shDone = Math.max(0, progress.shDone - 1);
      progress.allDone = Math.max(0, progress.allDone - 1);
      if (prevRating) {
        progress.ratings[prevRating] = Math.max(0, (progress.ratings[prevRating] || 1) - 1);
      }
      delete progress.prevRatings[indId];
    });



    toast(`All ratings cleared for Dimension ${dimNo}.`, 'ok');
    setTimeout(() => location.reload(), 700);
  }


  // ── Rating & save ──────────────────────────────────────────
  function selectRating(indId, rating) {
    currentRatings[indId] = rating;
    document.querySelectorAll(`#ratingGroup${indId} .rating-btn`).forEach(btn => {
      const r = parseInt(btn.dataset.rating);
      btn.className = 'rating-btn' + (r === rating ? ` selected-${r}` : '');
    });
    saveResponse(indId);
  }

  async function saveResponse(indId) {
    const rating = currentRatings[indId];
    if (!rating) return;

    const row = document.getElementById(`row${indId}`);
    const isTeacher = row.dataset.teacher === '1' && row.dataset.sh === '0';
    const wasRated = row?.classList.contains('rated') ?? false;

    const evidence = document.getElementById(`evidence${indId}`)?.value || '';
    const r = await apiPost('self_assessment.php', {
      action: 'save_response',
      indicator_id: indId,
      rating,
      evidence
    });

    if (r.ok) {
      const isNewResponse = !wasRated;
      if (row) row.classList.add('rated');
      const badge = document.getElementById(`savedBadge${indId}`);
      if (badge) {
        badge.textContent = 'Saved';
        badge.style.color = 'var(--g600)';
        badge.style.fontWeight = '600';
        badge.style.fontSize = '11px';
      }
      // ← Live progress update
      updateProgress(indId, rating, isTeacher, isNewResponse);
    } else {
      toast(r.msg, 'err');
    }
  }

  // ── Accordion ──────────────────────────────────────────────
  function toggleDim(n) {
    const body = document.getElementById('dimBody' + n);
    const chevron = document.getElementById('dimChevron' + n);
    const isOpen = !body.classList.contains('collapsed');
    body.classList.toggle('collapsed', isOpen);
    chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
  }

  // ── Submit ─────────────────────────────────────────────────
  async function submitAssessment(force = false) {
    if (!force) {
      openModal('mSubmitAssessmentSH');
      return;
    }
    closeModal('mSubmitAssessmentSH');

    const r = await apiPost('self_assessment.php', { action: 'submit', force_submit: force ? '1' : '' });

    // Fix A: soft teacher warning — offer to submit anyway
    if (!r.ok && r.warn_teachers) {
      document.getElementById('teacherWarnText').textContent =
        `${r.submitted} of ${r.total} teachers have submitted. ${r.pending} teacher(s) still pending.`;
      openModal('mTeacherWarning');
      return;
    }

    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 1200);
  }

  async function forceSubmitAssessment() {
    closeModal('mTeacherWarning');
    submitAssessment(true);
  }

  // ── Restore last filter on page load ──────────────────────
  (function () {
    const saved = sessionStorage.getItem('sbmFilter');
    if (saved && saved !== 'all') setFilter(saved);
  })();

  // ── Load attachments for all indicators (SH view) ────────────
  (async function loadSHAttachments() {
    if (!<?= $cycle ? $cycle['cycle_id'] : 0 ?>) return;
    const cycleId = <?= $cycle ? $cycle['cycle_id'] : 0 ?>;
    const isLocked = <?= !$canEdit ? 'true' : 'false' ?>;
    const indIds = <?= json_encode(array_column($indicators, 'indicator_id')) ?>;
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const fd = new FormData();
      fd.append('action', 'get_attachments');
      fd.append('csrf_token', csrf);
      fd.append('cycle_id', cycleId);
      fd.append('uploader_only', '1'); // SH ONLY sees their own attachments here so they don't accidentally remove teacher files
      const res = await fetch('../includes/upload_handler.php', { method: 'POST', body: fd });
      const data = await res.json();
      const byInd = {};
      (data.attachments || []).forEach(a => {
        if (!byInd[a.indicator_id]) byInd[a.indicator_id] = [];
        byInd[a.indicator_id].push(a);
      });
      indIds.forEach(id => {
        renderAttachWidget(id, cycleId, byInd[id] || [], isLocked);
      });
    } catch (e) {
      indIds.forEach(id => renderAttachWidget(id, cycleId, [], isLocked));
    }
  })();

  async function confirmStartAssessment() {
    const btn = document.getElementById('btnConfirmStart');
    if (btn) { btn.disabled = true; btn.textContent = 'Starting...'; }

    const r = await apiPost('self_assessment.php', { action: 'start_assessment' });
    if (r.ok) {
      toast(r.msg, 'ok');
      closeModal('mStartAssessment');
      setTimeout(() => location.reload(), 1000);
    } else {
      toast(r.msg || 'Something went wrong.', 'err');
      if (btn) { btn.disabled = false; btn.textContent = 'Yes, Start Assessment'; }
    }
  }

</script>

<!-- Start Assessment Modal -->
<div class="overlay" id="mSubmitAssessmentSH">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head">
      <span class="modal-title">
        Confirm Submission
      </span>
      <button class="modal-close" onclick="closeModal('mSubmitAssessmentSH')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">
      <p style="font-size:17px; font-weight:600; color:var(--n900); line-height:1.4; margin-bottom:16px;">
        Submit your SBM Self-Assessment to the SDO?
      </p>
      <div style="display:flex;align-items:center;gap:8px;color:var(--n500);font-size:13px;">
        <?= svgIcon('info') ?>
        <span>
          You will not be able to edit after submission.
        </span>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSubmitAssessmentSH')">
        Cancel
      </button>
      <button class="btn btn-primary" type="button" onclick="submitAssessment(true)">
        Yes, Submit
      </button>
    </div>
  </div>
</div>

<div class="overlay" id="mTeacherWarning">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head">
      <span class="modal-title">
        Teacher Submissions Incomplete
      </span>
      <button class="modal-close" onclick="closeModal('mTeacherWarning')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning" style="margin-bottom:16px;">
        <?= svgIcon('alert-circle') ?>
        <span id="teacherWarnText"></span>
      </div>
      <p style="font-size:14px; color:var(--n600); line-height:1.5;">
        Submit anyway? Teacher averages will be based on responses received so far.
      </p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mTeacherWarning')">
        Wait
      </button>
      <button class="btn btn-primary" type="button" onclick="forceSubmitAssessment()">
        Submit Anyway
      </button>
    </div>
  </div>
</div>

<div class="overlay" id="mStartAssessment">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head">
      <span class="modal-title">
        Start Assessment Cycle
      </span>
      <button class="modal-close" onclick="closeModal('mStartAssessment')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info" style="margin-bottom:16px;">
        <?= svgIcon('info') ?>
        <span>
          Are you sure you want to start the SBM Self-Assessment for this school year?
        </span>
      </div>
      <p style="font-size:14px; color:var(--n600); line-height:1.5;">
        This action will immediately initialize the assessment indicators and reflect them on the teachers' dashboard so
        they can begin providing their ratings.
      </p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mStartAssessment')">
        Cancel
      </button>
      <button class="btn btn-primary" id="btnConfirmStart" type="button" onclick="confirmStartAssessment()">
        Yes, Start Assessment
      </button>
    </div>
  </div>
</div>

<?php endif; // end non-coordinator (school head) view ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>