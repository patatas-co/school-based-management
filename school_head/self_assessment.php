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

if (!$syId) {
  echo '<div class="alert alert-danger">No active school year configured. Contact the administrator.</div>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

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
      $ph = buildInPlaceholders($teacherOnlyCodes);
      $db->prepare("
    DELETE r FROM sbm_responses r
    JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
    WHERE r.cycle_id = ?
      AND i.dimension_id = ?
      AND i.indicator_code NOT IN ($ph)
")->execute(array_merge([$cycleRow['cycle_id'], $dimId], $teacherOnlyCodes));

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

      // Count SH-only indicators
      $ph = buildInPlaceholders(TEACHER_INDICATOR_CODES);
      // SH must answer: SH_ONLY indicators + shared (teacher codes not in SH_ONLY are teacher-only)
      $shAnswerableCodes = SH_ONLY_INDICATOR_CODES;
      $ph = buildInPlaceholders($shAnswerableCodes);
      $shOnlyStmt = $db->prepare("
    SELECT COUNT(*) FROM sbm_indicators
    WHERE is_active = 1
      AND indicator_code IN ($ph)
");
      $shOnlyStmt->execute($shAnswerableCodes);
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

    if ($_POST['action'] === 'override_teacher_indicator') {
      $indicatorId = (int) $_POST['indicator_id'];
      $overrideRating = (int) $_POST['rating'];
      $reason = trim($_POST['reason'] ?? '');

      if ($overrideRating < 1 || $overrideRating > 4) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid rating.']);
        exit;
      }
      if (empty($reason)) {
        echo json_encode(['ok' => false, 'msg' => 'Reason for override is required.']);
        exit;
      }

      $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
      $chk->execute([$indicatorId]);
      $code = $chk->fetchColumn();

      $isCoordinator = ($_SESSION['role'] === 'sbm_coordinator');

      // Only pure teacher-only indicators can be overridden by SH
      // Coordinator has global override authority (all 42)
      if (!$isCoordinator) {
        if (!in_array($code, TEACHER_INDICATOR_CODES) || in_array($code, SH_ONLY_INDICATOR_CODES)) {
          echo json_encode(['ok' => false, 'msg' => 'This indicator is not a teacher indicator.']);
          exit;
        }
      }

      $cycleRow = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycleRow->execute([$schoolId, $syId]);
      $cycleId = $cycleRow->fetchColumn();

      if (!$cycleId) {
        echo json_encode(['ok' => false, 'msg' => 'No active cycle.']);
        exit;
      }

      $avgStmt = $db->prepare("SELECT ROUND(AVG(rating), 2) FROM teacher_responses WHERE cycle_id=? AND indicator_id=?");
      $avgStmt->execute([$cycleId, $indicatorId]);
      $originalAvg = $avgStmt->fetchColumn();

      $prevStmt = $db->prepare("SELECT override_rating FROM sh_indicator_overrides WHERE cycle_id=? AND indicator_id=?");
      $prevStmt->execute([$cycleId, $indicatorId]);
      $prevOverride = $prevStmt->fetchColumn();

      $actionType = $prevOverride === false ? 'override' : 'update';
      $previousRating = $prevOverride === false ? ($originalAvg ?? 0) : $prevOverride;

      $db->prepare("
            INSERT INTO sh_indicator_overrides
                (cycle_id, indicator_id, school_id, original_avg,
                 override_rating, override_reason, overridden_by)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                original_avg     = VALUES(original_avg),
                override_rating  = VALUES(override_rating),
                override_reason  = VALUES(override_reason),
                overridden_by    = VALUES(overridden_by),
                overridden_at    = NOW()
        ")->execute([$cycleId, $indicatorId, $schoolId, $originalAvg, $overrideRating, $reason, $_SESSION['user_id']]);

      $db->prepare("
          INSERT INTO sh_indicator_override_history
          (cycle_id, indicator_id, school_id, action_type, previous_rating, new_rating, override_reason, changed_by)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ")->execute([$cycleId, $indicatorId, $schoolId, $actionType, $previousRating, $overrideRating, $reason, $_SESSION['user_id']]);

      recomputeDimScoreWithOverrides($db, $cycleId, $indicatorId, $schoolId);

      logActivity(
        'sh_override_indicator',
        'self_assessment',
        "SH overrode indicator $code from avg $originalAvg to $overrideRating in cycle $cycleId"
      );

      echo json_encode([
        'ok' => true,
        'msg' => 'Override saved. Dimension score updated.',
        'original_avg' => $originalAvg,
        'override_rating' => $overrideRating
      ]);
      exit;
    }

    if ($_POST['action'] === 'clear_override') {
      $indicatorId = (int) $_POST['indicator_id'];

      $cycleRow = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycleRow->execute([$schoolId, $syId]);
      $cycleId = $cycleRow->fetchColumn();

      if (!$cycleId) {
        echo json_encode(['ok' => false, 'msg' => 'No active cycle.']);
        exit;
      }

      $prevStmt = $db->prepare("SELECT override_rating FROM sh_indicator_overrides WHERE cycle_id=? AND indicator_id=?");
      $prevStmt->execute([$cycleId, $indicatorId]);
      $prevOverride = $prevStmt->fetchColumn();

      if ($prevOverride !== false) {
        $db->prepare("
              INSERT INTO sh_indicator_override_history
              (cycle_id, indicator_id, school_id, action_type, previous_rating, new_rating, override_reason, changed_by)
              VALUES (?, ?, ?, 'clear', ?, NULL, ?, ?)
          ")->execute([$cycleId, $indicatorId, $schoolId, $prevOverride, 'Override cleared', $_SESSION['user_id']]);
      }

      $db->prepare("DELETE FROM sh_indicator_overrides WHERE cycle_id=? AND indicator_id=?")
        ->execute([$cycleId, $indicatorId]);

      recomputeDimScoreWithOverrides($db, $cycleId, $indicatorId, $schoolId);

      echo json_encode(['ok' => true, 'msg' => 'Override cleared. Score reverted to teacher average.']);
      exit;
    }

    if ($_POST['action'] === 'get_override_history') {
      $indicatorId = (int) $_POST['indicator_id'];

      $cycleRow = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?");
      $cycleRow->execute([$schoolId, $syId]);
      $cycleId = $cycleRow->fetchColumn();

      if (!$cycleId) {
        echo json_encode(['ok' => true, 'data' => []]);
        exit;
      }

      $stmt = $db->prepare("
          SELECT h.*, u.full_name 
          FROM sh_indicator_override_history h
          LEFT JOIN users u ON h.changed_by = u.user_id
          WHERE h.cycle_id=? AND h.indicator_id=?
          ORDER BY h.changed_at DESC
      ");
      $stmt->execute([$cycleId, $indicatorId]);
      $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode(['ok' => true, 'data' => $history]);
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

  foreach ($inds as $ind) {
    // Priority 1: Check Override table (Global for all types now)
    $ov = $db->prepare("SELECT override_rating FROM sh_indicator_overrides WHERE cycle_id=? AND indicator_id=?");
    $ov->execute([$cycleId, $ind['indicator_id']]);
    $override = $ov->fetchColumn();

    if ($override !== false) {
      $rawTotal += (int) $override;
      $maxTotal += 4;
      continue;
    }

    if (isTeacherHandled($ind['indicator_code'])) {
      $avg = $db->prepare("SELECT AVG(rating) FROM teacher_responses WHERE cycle_id=? AND indicator_id=?");
      $avg->execute([$cycleId, $ind['indicator_id']]);
      $avgVal = $avg->fetchColumn();
      if ($avgVal !== null) {
        $rawTotal += floatval($avgVal);
        $maxTotal += 4;
      }
    } else {
      $shResp = $db->prepare("SELECT rating FROM sbm_responses WHERE cycle_id=? AND indicator_id=? AND school_id=?");
      $shResp->execute([$cycleId, $ind['indicator_id'], $schoolId]);
      $rating = $shResp->fetchColumn();
      if ($rating !== false) {
        $rawTotal += (int) $rating;
        $maxTotal += 4;
      }
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
}

// ── LOAD DATA ────────────────────────────────────────────────
$indicators = $db->query("
    SELECT i.*, d.dimension_no, d.dimension_name, d.color_hex
    FROM sbm_indicators i
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    WHERE i.is_active = 1
    ORDER BY d.dimension_no, i.sort_order
")->fetchAll();

$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

$responses = [];
if ($cycle) {
  $r = $db->prepare("SELECT * FROM sbm_responses WHERE cycle_id=?");
  $r->execute([$cycle['cycle_id']]);
  foreach ($r->fetchAll() as $row)
    $responses[$row['indicator_id']] = $row;
}

// Load SH overrides for teacher indicators
$overrides = [];
if ($cycle) {
  $ov = $db->prepare("
        SELECT * FROM sh_indicator_overrides WHERE cycle_id=?
    ");
  $ov->execute([$cycle['cycle_id']]);
  foreach ($ov->fetchAll() as $row)
    $overrides[$row['indicator_id']] = $row;
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

// Teacher indicators that the School Head has overridden
// Teacher-only = in TEACHER_INDICATOR_CODES but not SH_ONLY
$teacherIndicators = array_filter(
  $indicators,
  fn($i) =>
  in_array($i['indicator_code'], TEACHER_INDICATOR_CODES) &&
  !in_array($i['indicator_code'], SH_ONLY_INDICATOR_CODES)
);
$overridenCount = count(array_filter($teacherIndicators, fn($i) => isset($overrides[$i['indicator_id']])));

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

$pageTitle = 'SBM Self-Assessment';
$activePage = 'self_assessment.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ══════════════════════════════════════════════════════
   FILTER BAR
══════════════════════════════════════════════════════ */
  .filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: var(--white);
    border: 1px solid var(--n200);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 16px;
    flex-wrap: wrap;
  }

  .filter-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--n500);
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
  }

  .filter-chips {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    flex: 1;
  }

  .filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 600;
    border: 1.5px solid var(--n200);
    background: var(--white);
    color: var(--n600);
    cursor: pointer;
    transition: all .15s;
    user-select: none;
    white-space: nowrap;
  }

  .filter-chip:hover {
    border-color: var(--n400);
    background: var(--n50);
  }

  .filter-chip.active-all {
    background: var(--n800);
    color: #fff;
    border-color: var(--n800);
  }

  .filter-chip.active-sh {
    background: var(--g600);
    color: #fff;
    border-color: var(--g600);
  }

  .filter-chip.active-teacher {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
  }

  .filter-chip .chip-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
    opacity: .7;
    flex-shrink: 0;
  }

  .filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(255, 255, 255, .25);
    color: inherit;
  }

  .filter-chip:not([class*="active"]) .filter-count {
    background: var(--n100);
    color: var(--n500);
  }

  .filter-info {
    font-size: 12px;
    color: var(--n400);
    margin-left: auto;
    white-space: nowrap;
  }

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
  }

  .dim-body.collapsed {
    display: none;
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

<!-- ── PAGE HEAD ──────────────────────────────────────────── -->
<div class="page-head">
  <div class="page-head-text">
    <h2>SBM Self-Assessment</h2>
    <p>Rate all indicators across 6 dimensions using the 4 Degrees of Manifestation scale.</p>
  </div>
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
      <button class="btn btn-primary" onclick="submitAssessment()">
        <?= svgIcon('check') ?> Submit Assessment
      </button>
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




  <!-- ── FILTER BAR ────────────────────────────────────────── -->
  <?php
  $shCount = isset($shIndicators) ? count($shIndicators) : 0;
  $teacherCount = count(array_filter($indicators, fn($i) => in_array($i['indicator_code'], TEACHER_INDICATOR_CODES)));
  ?>
  <div class="filter-bar" id="filterBar">
    <span class="filter-label">View:</span>
    <div class="filter-chips">

      <button class="filter-chip active-all" id="chip-all" onclick="setFilter('all')">
        <span class="chip-dot"></span>
        All Indicators
        <span class="filter-count" id="count-all"><?= $totalCount ?></span>
      </button>

      <button class="filter-chip" id="chip-sh" onclick="setFilter('sh')">
        <span class="chip-dot"></span>
        School Head Only
        <span class="filter-count" id="count-sh"><?= $shCount ?></span>
      </button>

      <button class="filter-chip" id="chip-teacher" onclick="setFilter('teacher')">
        <span class="chip-dot"></span>
        Teacher Indicators
        <span class="filter-count" id="count-teacher"><?= $teacherCount ?></span>
      </button>

    </div>
    <span class="filter-info" id="filterInfo">Showing all <?= $totalCount ?> indicators</span>
  </div>

  <!-- ── STICKY DIMENSION TABS ─────────────────────────────── -->
  <div id="dimTabs" style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;
            position:sticky;top:60px;z-index:40;
            background:var(--n50);padding:8px 0;">
    <?php foreach ($grouped as $dimNo => $inds): ?>
      <?php
      $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
      $dimTotal = count($inds);
      $dimFull = $dimDone === $dimTotal;
      ?>
      <a href="#dim<?= $dimNo ?>" id="dimTab<?= $dimNo ?>" data-done="<?= $dimDone ?>" data-total="<?= $dimTotal ?>" style="display:inline-flex;align-items:center;gap:5px;
            padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;
            background:<?= $dimFull ? 'var(--g600)' : 'var(--white)' ?>;
            color:<?= $dimFull ? '#fff' : 'var(--n600)' ?>;
            border:1px solid <?= $dimFull ? 'var(--g600)' : 'var(--n200)' ?>;
            text-decoration:none;
            transition:background .3s,color .3s,border-color .3s;">
        D<?= $dimNo ?>
        <span id="dimTabCount<?= $dimNo ?>" style="opacity:.7;">(<?= $dimDone ?>/<?= $dimTotal ?>)</span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ── INDICATORS BY DIMENSION ───────────────────────────── -->
  <?php foreach ($grouped as $dimNo => $inds): ?>
    <?php
    $dim = $inds[0];
    $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
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
          <?php
          $ds = $cycle ? $db->prepare("
            SELECT percentage FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id
            WHERE ds.cycle_id=? AND d.dimension_no=?
        ") : null;
          if ($ds) {
            $ds->execute([$cycle['cycle_id'], $dimNo]);
            $pctRow = $ds->fetchColumn();
            echo $pctRow ? number_format($pctRow, 1) . '%' : '—';
          } else
            echo '—';
          ?>
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
            $isCoordinatorView = ($_COORDINATOR_VIEW ?? false);
            if ($isTeacherCard || $showTeacherInfoAlso || $isCoordinatorView):
              ?>
              <?php
              $hasOverride = isset($overrides[$ind['indicator_id']]);
              $ovData = $hasOverride
                ? $overrides[$ind['indicator_id']]
                : null;
              ?>

              <!-- TEACHER INFO BOX WITH OVERRIDE -->
              <div class="teacher-info-box" style="<?= $hasOverride
                ? 'background:var(--goldb);border-color:#FDE68A;'
                : '' ?>">
                <div class="teacher-info-icon" style="<?= $hasOverride
                  ? 'background:var(--gold);'
                  : '' ?>">
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
                      <?php if ($hasOverride): ?>
                        Coordinator Override:
                        <div style="font-size:18px;font-weight:800;color:var(--gold);line-height:1.2;margin-bottom:2px;">
                          <?= number_format($ovData['override_rating'], 2) ?>
                          <span style="font-size:12px;opacity:.7;font-weight:600;"> (Forced Score)</span>
                        </div>
                        <div style="font-size:12px;color:var(--n500);margin-bottom:4px;">
                          Teacher Average: <span style="text-decoration:line-through;"><?= $trData['avg_rating'] ?></span>
                        </div>
                      <?php else: ?>
                        Teacher Average:
                        <div class="teacher-avg-rating">
                          <?= $trData['avg_rating'] ?>/4.00
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="teacher-info-body">
                      <?= (int) $trData['teacher_count'] ?> teacher response(s)
                    </div>
                  <?php else: ?>
                    <div class="teacher-info-title">
                      <?php if ($hasOverride): ?>
                        Coordinator Override:
                        <div style="font-size:18px;font-weight:800;color:var(--gold);line-height:1.2;margin-bottom:2px;">
                          <?= number_format($ovData['override_rating'], 2) ?>
                          <span style="font-size:12px;opacity:.7;font-weight:600;"> (Forced Score)</span>
                        </div>
                      <?php else: ?>
                        <?= $isTeacherCard ? 'Teacher Indicator' : 'System Oversight' ?>
                      <?php endif; ?>
                    </div>
                    <div class="teacher-info-body">
                      <?php if (!$hasOverride): ?>
                        <?= $isTeacherCard ? 'No teacher input yet. Teachers rate this in their portal.' : 'School Head can override values if necessary.' ?>
                      <?php else: ?>
                        This score will be used instead of any manual ratings.
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <?php
                  $showOverrideBtn = $canEdit && (
                    ($trData && (int) $trData['teacher_count'] > 0 && $isTeacherCard) ||
                    $isCoordinatorView
                  );
                  if ($showOverrideBtn): ?>
                    <!-- Override controls -->
                    <div style="margin-top:10px;padding-top:10px;
                    border-top:1px solid rgba(0,0,0,.08);">
                      <?php
                      $currentAvg = $trData['avg_rating'] ?? 0;
                      if (!$hasOverride): ?>
                        <button class="btn btn-secondary btn-sm" onclick="openOverride(
                        <?= $ind['indicator_id'] ?>,
                        '<?= e($ind['indicator_code']) ?>',
                        <?= $currentAvg ?>
                    )">
                          Override Rating
                        </button>
                        <button class="btn btn-secondary btn-sm" style="margin-left:6px;"
                          onclick="viewOverrideHistory(<?= $ind['indicator_id'] ?>, '<?= e($ind['indicator_code']) ?>')">
                          <i class="feather-clock"></i> History
                        </button>
                        <span style="font-size:11px;color:var(--n400);
                         margin-left:8px;">
                          <?= $isCoordinatorView ? 'Coordinator override' : 'Use if teacher average needs correction' ?>
                        </span>
                      <?php else: ?>
                        <button class="btn btn-sm" style="background:var(--goldb);
                           color:var(--gold);
                           border:1px solid #FDE68A;" onclick="openOverride(
                        <?= $ind['indicator_id'] ?>,
                        '<?= e($ind['indicator_code']) ?>',
                        <?= $currentAvg ?>,
                        <?= $ovData['override_rating'] ?>,
                        `<?= e(addslashes($ovData['override_reason'] ?? '')) ?>`
                    )">
                          Edit Override
                        </button>
                        <button class="btn btn-danger btn-sm" style="margin-left:6px;"
                          onclick="clearOverride(<?= $ind['indicator_id'] ?>)">
                          Clear Override
                        </button>
                        <button class="btn btn-secondary btn-sm" style="margin-left:6px;"
                          onclick="viewOverrideHistory(<?= $ind['indicator_id'] ?>, '<?= e($ind['indicator_code']) ?>')">
                          <i class="feather-clock"></i> History
                        </button>
                        <?php if ($ovData['override_reason']): ?>
                          <div style="font-size:11.5px;color:var(--n600);
                        margin-top:5px;font-style:italic;">
                            Reason: <?= e($ovData['override_reason']) ?>
                          </div>
                        <?php endif; ?>
                      <?php endif; ?>
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
  let currentFilter = 'all';

  const TEACHER_ONLY_CODES_JS = new Set(<?= json_encode(TEACHER_ONLY_CODES) ?>);
  const TCH_EXT_CODES_JS = new Set(<?= json_encode(TCH_EXT_CODES) ?>);
  const TEACHER_HANDLED_CODES = new Set([...TEACHER_ONLY_CODES_JS, ...TCH_EXT_CODES_JS]);
  const COUNTS = {
    all: <?= (int) ($totalCount ?? 0) ?>,
    sh: <?= (int) ($shCount ?? 0) ?>,
    teacher: <?= (int) ($teacherCount ?? 0) ?>
  };

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
      if (tab) {
        if (leftVisible === 0) {
          tab.style.background = 'var(--g600)';
          tab.style.color = '#fff';
          tab.style.borderColor = 'var(--g600)';
        } else {
          tab.style.background = 'var(--white)';
          tab.style.color = 'var(--n600)';
          tab.style.borderColor = 'var(--n200)';
        }
      }
    }

    // Flash tab green when dimension completes
    if (tab && ratedVisible === totalVisible) {
      tab.style.background = 'var(--g600)';
      tab.style.color = '#fff';
      tab.style.borderColor = 'var(--g600)';

      // Update dim header badge
      const leftBadge = document.getElementById('dimLeft' + dimNo);
      if (leftBadge) {
        leftBadge.textContent = 'Complete';
        leftBadge.style.color = '#16A34A';
        leftBadge.style.background = '#DCFCE7';
        leftBadge.style.borderColor = '#86EFAC';
      }
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
    const ratedCount = dimWrap?.querySelectorAll('.indicator-row[data-role="sh"].rated').length ?? 0;
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
    dimWrap.querySelectorAll('.indicator-row[data-role="sh"]').forEach(row => {
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



    // Update dim tab ONCE
    const allCards = dimWrap.querySelectorAll('.indicator-row');
    const ratedCards = dimWrap.querySelectorAll('.indicator-row.rated');
    const done = ratedCards.length;
    const total = allCards.length;

    const tab = document.getElementById('dimTab' + dimNo);
    if (tab) { tab.style.background = 'var(--white)'; tab.style.color = 'var(--n600)'; tab.style.borderColor = 'var(--n200)'; }
    const tabCount = document.getElementById('dimTabCount' + dimNo);
    if (tabCount) tabCount.textContent = `(${done}/${total})`;
    const subtitle = document.getElementById('dimSubtitle' + dimNo);
    if (subtitle) subtitle.textContent = `${done}/${total} indicators rated`;
    const leftBadge = document.getElementById('dimLeft' + dimNo);
    if (leftBadge) {
      leftBadge.textContent = `${total - done} left`;
      leftBadge.style.color = 'var(--n500)';
      leftBadge.style.background = 'var(--n100)';
      leftBadge.style.border = '';
      leftBadge.style.fontWeight = '600';
    }
    const clearDimBtn = document.getElementById('clearDimBtn' + dimNo);
    if (clearDimBtn) clearDimBtn.style.display = 'none';

    toast(`All ratings cleared for Dimension ${dimNo}.`, 'ok');
  }

  // ── Filter system ──────────────────────────────────────────
  function setFilter(mode) {
    currentFilter = mode;

    // Update chip styles
    document.querySelectorAll('.filter-chip').forEach(c => {
      c.className = 'filter-chip';
    });
    document.getElementById('chip-' + mode).classList.add('active-' + mode);

    // Update info text
    const labels = {
      all: `Showing all ${COUNTS.all} indicators`,
      sh: `Showing ${COUNTS.sh} School Head indicator${COUNTS.sh !== 1 ? 's' : ''}`,
      teacher: `Showing ${COUNTS.teacher} Teacher indicator${COUNTS.teacher !== 1 ? 's' : ''}`
    };
    document.getElementById('filterInfo').textContent = labels[mode];

    // Apply visibility to each card
    document.querySelectorAll('.indicator-row').forEach(row => {
      const isSh = row.dataset.sh === '1';
      const isTeacher = row.dataset.teacher === '1';
      const show = mode === 'all'
        || (mode === 'sh' && isSh)
        || (mode === 'teacher' && isTeacher);
      row.classList.toggle('filter-hidden', !show);
    });

    document.querySelectorAll('.dim-wrap').forEach(dimWrap => {
      refreshDimensionMetrics(dimWrap.dataset.dim);
    });

    // Save filter preference in sessionStorage
    sessionStorage.setItem('sbmFilter', mode);
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
    if (!force && !confirm('Submit your SBM Self-Assessment to the SDO?\nYou will not be able to edit after submission.')) return;
    const r = await apiPost('self_assessment.php', { action: 'submit', force_submit: force ? '1' : '' });

    // Fix A: soft teacher warning — offer to submit anyway
    if (!r.ok && r.warn_teachers) {
      const go = confirm(
        `⚠️ Teacher Submissions Incomplete\n\n` +
        `${r.submitted} of ${r.total} teachers have submitted.\n` +
        `${r.pending} teacher(s) still pending.\n\n` +
        `Submit anyway? Teacher averages will be based on responses received so far.\n\n` +
        `Click OK to submit anyway, or Cancel to wait.`
      );
      if (go) submitAssessment(true); // force
      return;
    }

    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 1200);
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

  // ── Override functions ─────────────────────────────────────
  function openOverride(indId, code, avgRating,
    currentOverride, currentReason) {
    $v('ov_ind_id', indId);
    $v('ov_code', code);
    $v('ov_avg', avgRating);
    $v('ov_rating', currentOverride || '');
    $v('ov_reason', currentReason || '');

    document.getElementById('mOverrideTitle').textContent =
      `Override Indicator ${code}`;
    document.getElementById('ovAvgDisplay').textContent =
      `Teacher average: ${avgRating}/4.00`;

    // Pre-select the current override rating if editing
    document.querySelectorAll('.ov-rating-btn').forEach(btn => {
      const r = parseInt(btn.dataset.rating);
      btn.className = 'rating-btn' +
        (r === parseInt(currentOverride)
          ? ` selected-${r}`
          : '');
    });

    openModal('mOverride');
  }

  function selectOverrideRating(r) {
    $v('ov_rating', r);
    document.querySelectorAll('.ov-rating-btn').forEach(btn => {
      const bv = parseInt(btn.dataset.rating);
      btn.className = 'rating-btn' +
        (bv === r ? ` selected-${r}` : '');
    });
  }

  async function submitOverride() {
    const indId = $('ov_ind_id');
    const rating = parseInt($('ov_rating'));
    const reason = document.getElementById('ov_reason').value.trim();

    if (!rating || rating < 1 || rating > 4) {
      toast('Please select a rating.', 'warning');
      return;
    }
    if (!reason) {
      toast('Please provide a reason for the override.', 'warning');
      return;
    }

    const r = await apiPost('self_assessment.php', {
      action: 'override_teacher_indicator',
      indicator_id: indId,
      rating,
      reason
    });

    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      closeModal('mOverride');
      setTimeout(() => location.reload(), 800);
    }
  }

  async function clearOverride(indId) {
    if (!confirm(
      'Clear this override? The score will revert to ' +
      'the teacher average.'
    )) return;

    const r = await apiPost('self_assessment.php', {
      action: 'clear_override',
      indicator_id: indId
    });

    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
  }

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

  async function viewOverrideHistory(indId, code) {
    document.getElementById('mOverrideHistoryTitle').textContent = `Override History for Indicator ${code}`;
    document.getElementById('historyLoading').style.display = 'block';
    document.getElementById('historyContent').style.display = 'none';
    openModal('mOverrideHistory');

    const r = await apiPost('self_assessment.php', { action: 'get_override_history', indicator_id: indId });
    document.getElementById('historyLoading').style.display = 'none';
    const content = document.getElementById('historyContent');
    content.style.display = 'block';

    if (r.ok && r.data && r.data.length > 0) {
      let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
      r.data.forEach(item => {
        let actionBadge = '';
        if (item.action_type === 'override') actionBadge = '<span style="background:var(--blueb); color:var(--blue); padding: 2px 6px; border-radius:4px; font-size:11px; font-weight:bold;">First Override</span>';
        else if (item.action_type === 'update') actionBadge = '<span style="background:var(--goldb); color:var(--gold); padding: 2px 6px; border-radius:4px; font-size:11px; font-weight:bold;">Updated</span>';
        else actionBadge = '<span style="background:var(--redb); color:var(--red); padding: 2px 6px; border-radius:4px; font-size:11px; font-weight:bold;">Cleared</span>';

        let changes = '';
        if (item.action_type === 'clear') {
          changes = `Cleared override (Reverted to teacher average: ${item.previous_rating})`;
        } else {
          changes = `Changed from <strong>${item.previous_rating || 'N/A'}</strong> to <strong>${item.new_rating}</strong>`;
        }

        html += `
          <div style="border: 1px solid var(--n200); border-radius: var(--radius); padding: 12px; background: var(--n50);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
               <div>${actionBadge} <span style="font-size:12px; color:var(--n500); margin-left:6px;">${new Date(item.changed_at).toLocaleString()}</span></div>
               <div style="font-size:12px; font-weight:600; color:var(--n700);">${item.full_name}</div>
            </div>
            <div style="font-size: 13px; color: var(--n700); margin-bottom: 4px;">
                ${changes}
            </div>
            <div style="font-size: 12px; color: var(--n600); font-style: italic;">
                Reason: ${item.override_reason}
            </div>
          </div>
        `;
      });
      html += '</div>';
      content.innerHTML = html;
    } else {
      content.innerHTML = '<div style="text-align:center; padding:10px; color:var(--n500);">No override history found.</div>';
    }
  }

</script>

<!-- Start Assessment Modal -->
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

<!-- Override Modal -->
<div class="overlay" id="mOverride">
  <div class="modal" style="max-width:540px;max-height:none;overflow-y:visible;">
    <div class="modal-head">
      <span class="modal-title" id="mOverrideTitle">
        Override Indicator
      </span>
      <button class="modal-close" onclick="closeModal('mOverride')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ov_ind_id">
      <input type="hidden" id="ov_code">
      <input type="hidden" id="ov_avg">
      <input type="hidden" id="ov_rating">

      <div class="alert alert-warning" style="margin-bottom:16px;">
        <?= svgIcon('alert-circle') ?>
        <span>
          Overriding replaces the teacher average for
          score computation. Use only when necessary
          (e.g., data entry error, teacher on leave).
        </span>
      </div>

      <div style="font-size:13px;color:var(--n500);
                  margin-bottom:14px;" id="ovAvgDisplay">
      </div>

      <div class="fg">
        <label>Override Rating *</label>
        <div class="rating-group">
          <?php foreach ([1, 2, 3, 4] as $r): ?>
            <button type="button" class="rating-btn ov-rating-btn" data-rating="<?= $r ?>"
              onclick="selectOverrideRating(<?= $r ?>)">
              <?= $r ?> — <?= $ratingLabels[$r] ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="fg">
        <label>Reason for Override *</label>
        <textarea class="fc" id="ov_reason" rows="3" placeholder="Explain why you are overriding the 
                           teacher average (e.g., teacher was on 
                           leave, data entry error)…">
          </textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mOverride')">
        Cancel
      </button>
      <button class="btn btn-primary" onclick="submitOverride()">
        Save Override
      </button>
    </div>
  </div>
</div>

<!-- Override History Modal -->
<div class="overlay" id="mOverrideHistory">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head">
      <span class="modal-title" id="mOverrideHistoryTitle">
        Override History
      </span>
      <button class="modal-close" onclick="closeModal('mOverrideHistory')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">
      <div id="historyLoading" style="text-align:center; padding:20px; color:var(--n500);">Loading history...</div>
      <div id="historyContent" style="display:none; max-height: 400px; overflow-y: auto;">
        <!-- Filled by JS -->
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mOverrideHistory')">Close</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>