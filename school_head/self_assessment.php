<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if (!$schoolId || !$syId) {
    echo '<div class="alert alert-danger">No school or school year configured. Contact the administrator.</div>';
    include __DIR__.'/../includes/footer.php'; exit;
}

// ── AJAX HANDLERS ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    if ($_POST['action'] === 'save_response') {
        $indicatorId = (int)$_POST['indicator_id'];
        $rating      = (int)$_POST['rating'];
        $evidence    = trim($_POST['evidence'] ?? '');

        if ($rating < 1 || $rating > 4) {
            echo json_encode(['ok'=>false,'msg'=>'Invalid rating.']); exit;
        }

        $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
        $chk->execute([$indicatorId]);
        $indicatorCode = $chk->fetchColumn();

        if (in_array($indicatorCode, TEACHER_INDICATOR_CODES)) {
            echo json_encode(['ok'=>false,'msg'=>'This indicator is answered by teachers.']); exit;
        }

        $cycleRow = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycleRow->execute([$schoolId,$syId]); $cycleRow = $cycleRow->fetch();

        if (!$cycleRow) {
            $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())")->execute([$syId,$schoolId]);
            $cycleId = $db->lastInsertId();
        } else {
            $cycleId = $cycleRow['cycle_id'];
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
           ->execute([$cycleId,$indicatorId,$schoolId,$rating,$evidence,$_SESSION['user_id']]);

        recomputeDimScore($db, $cycleId, $indicatorId, $schoolId);
        echo json_encode(['ok'=>true,'msg'=>'Saved.']); exit;
    }

    if ($_POST['action'] === 'clear_response') {
        $indicatorId = (int)$_POST['indicator_id'];

        // Block clearing teacher indicators
        $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
        $chk->execute([$indicatorId]);
        $indicatorCode = $chk->fetchColumn();
        if (in_array($indicatorCode, TEACHER_INDICATOR_CODES)) {
            echo json_encode(['ok'=>false,'msg'=>'Cannot clear a teacher indicator.']); exit;
        }

        // Block clearing if assessment is locked
        $cycleRow = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycleRow->execute([$schoolId,$syId]); $cycleRow = $cycleRow->fetch();
        if (!$cycleRow) { echo json_encode(['ok'=>false,'msg'=>'No active cycle.']); exit; }
        if (in_array($cycleRow['status'], ['submitted','validated'])) {
            echo json_encode(['ok'=>false,'msg'=>'Assessment is locked. Cannot clear.']); exit;
        }

        $db->prepare("DELETE FROM sbm_responses WHERE cycle_id=? AND indicator_id=? AND school_id=?")
           ->execute([$cycleRow['cycle_id'], $indicatorId, $schoolId]);

        recomputeDimScore($db, $cycleRow['cycle_id'], $indicatorId, $schoolId);
        echo json_encode(['ok'=>true,'msg'=>'Rating cleared.']); exit;
    }

    if ($_POST['action'] === 'clear_dimension') {
        $dimId = (int)$_POST['dimension_id'];

        $cycleRow = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycleRow->execute([$schoolId,$syId]); $cycleRow = $cycleRow->fetch();
        if (!$cycleRow) { echo json_encode(['ok'=>false,'msg'=>'No active cycle.']); exit; }
        if (in_array($cycleRow['status'], ['submitted','validated'])) {
            echo json_encode(['ok'=>false,'msg'=>'Assessment is locked.']); exit;
        }

        // Delete all SH responses in this dimension
        $db->prepare("
            DELETE r FROM sbm_responses r
            JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
            WHERE r.cycle_id = ?
              AND i.dimension_id = ?
              AND i.indicator_code NOT IN ('".implode("','", TEACHER_INDICATOR_CODES)."')
        ")->execute([$cycleRow['cycle_id'], $dimId]);

        // Recompute dim score — now zero
        $db->prepare("UPDATE sbm_dimension_scores SET raw_score=0,max_score=0,percentage=0,computed_at=NOW()
                      WHERE cycle_id=? AND dimension_id=?")
           ->execute([$cycleRow['cycle_id'], $dimId]);

        // Get indicator ids in this dim for response
        $indIds = $db->prepare("SELECT indicator_id FROM sbm_indicators WHERE dimension_id=? AND is_active=1");
        $indIds->execute([$dimId]);
        $indIds = $indIds->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['ok'=>true,'msg'=>'All ratings cleared for this dimension.','indicator_ids'=>$indIds]); exit;
    }

    if ($_POST['action'] === 'submit') {
        $cyc = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cyc->execute([$schoolId,$syId]); $cyc = $cyc->fetch();
        if (!$cyc) { echo json_encode(['ok'=>false,'msg'=>'No assessment to submit.']); exit; }

        // Count SH-only indicators (active, not in teacher list)
        $shOnlyStmt = $db->prepare("
            SELECT COUNT(*) FROM sbm_indicators
            WHERE is_active = 1
              AND indicator_code NOT IN ('".implode("','", TEACHER_INDICATOR_CODES)."')
        ");
        $shOnlyStmt->execute();
        $expected = (int)$shOnlyStmt->fetchColumn();

        // Count SH-only responses already saved (same filter, must match exactly)
        $shDoneStmt = $db->prepare("
            SELECT COUNT(*) FROM sbm_responses r
            JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
            WHERE r.cycle_id = ?
              AND i.is_active = 1
              AND i.indicator_code NOT IN ('".implode("','", TEACHER_INDICATOR_CODES)."')
        ");
        $shDoneStmt->execute([$cyc['cycle_id']]);
        $cnt = (int)$shDoneStmt->fetchColumn();

        if ($cnt < $expected) {
            echo json_encode([
                'ok'  => false,
                'msg' => "Please rate all your indicators. ($cnt/$expected done)"
            ]); exit;
        }

        $total = $db->prepare("SELECT SUM(raw_score), SUM(max_score) FROM sbm_dimension_scores WHERE cycle_id=?");
        $total->execute([$cyc['cycle_id']]);
        [$totalRaw, $totalMax] = array_values($total->fetch(PDO::FETCH_NUM));
        $overall = $totalMax > 0 ? round(($totalRaw/$totalMax)*100, 2) : 0;
        $mat = sbmMaturityLevel($overall);

        $db->prepare("UPDATE sbm_cycles SET status='submitted',submitted_at=NOW(),overall_score=?,maturity_level=? WHERE cycle_id=?")
           ->execute([$overall, $mat['label'], $cyc['cycle_id']]);
        logActivity('submit_assessment','self_assessment','Submitted SBM assessment cycle '.$cyc['cycle_id']);
        echo json_encode(['ok'=>true,'msg'=>'Assessment submitted successfully!']); exit;
    }
    exit;
}

// ── HELPERS ──────────────────────────────────────────────────
function recomputeDimScore(PDO $db, int $cycleId, int $indicatorId, int $schoolId): void {
    $dimId = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id=?");
    $dimId->execute([$indicatorId]); $dimId = $dimId->fetchColumn();

    $scores = $db->prepare("
        SELECT SUM(r.rating) raw, COUNT(r.response_id)*4 max_possible
        FROM sbm_responses r
        JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
        WHERE r.cycle_id = ? AND i.dimension_id = ?
    ");
    $scores->execute([$cycleId, $dimId]);
    [$raw, $maxP] = array_values($scores->fetch(PDO::FETCH_NUM));
    $pct = $maxP > 0 ? round(($raw/$maxP)*100, 2) : 0;

    $db->prepare("INSERT INTO sbm_dimension_scores (cycle_id,school_id,dimension_id,raw_score,max_score,percentage)
                  VALUES (?,?,?,?,?,?)
                  ON DUPLICATE KEY UPDATE
                    raw_score=VALUES(raw_score),
                    max_score=VALUES(max_score),
                    percentage=VALUES(percentage),
                    computed_at=NOW()")
       ->execute([$cycleId, $schoolId, $dimId, $raw, $maxP, $pct]);
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
$cycle->execute([$schoolId,$syId]); $cycle = $cycle->fetch();

$responses = [];
if ($cycle) {
    $r = $db->prepare("SELECT * FROM sbm_responses WHERE cycle_id=?");
    $r->execute([$cycle['cycle_id']]);
    foreach ($r->fetchAll() as $row) $responses[$row['indicator_id']] = $row;
}

$grouped = [];
foreach ($indicators as $ind) $grouped[$ind['dimension_no']][] = $ind;

$ratingLabels = [1=>'Not Yet Manifested', 2=>'Emerging', 3=>'Developing', 4=>'Always Manifested'];
$ratingColors = [1=>'#DC2626', 2=>'#D97706', 3=>'#2563EB', 4=>'#16A34A'];

$isLocked = $cycle && in_array($cycle['status'], ['submitted','validated']);

// SH-only indicator counts (for progress bar and submit button)
$shIndicators = array_filter($indicators, fn($i) => !in_array($i['indicator_code'], TEACHER_INDICATOR_CODES));
$shResponded  = count(array_filter($shIndicators, fn($i) => isset($responses[$i['indicator_id']])));
$shTotal      = count($shIndicators);
$totalDone    = count($responses);
$totalCount   = count($indicators);

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
        foreach ($tr->fetchAll() as $row) $teacherData[$row['indicator_id']] = $row;
    } catch (Exception $e) {
        // teacher_responses table may not exist yet — safe to ignore
    }
}

$pageTitle = 'SBM Self-Assessment'; $activePage = 'self_assessment.php';
include __DIR__.'/../includes/header.php';
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
.filter-chip:hover { border-color: var(--n400); background: var(--n50); }
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
  background: rgba(255,255,255,.25);
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
.dim-header:hover { background: var(--n50); }
.dim-chevron {
  font-size: 20px;
  color: var(--n300);
  transition: transform .25s ease;
  flex-shrink: 0;
  margin-left: 4px;
}
.dim-body { padding-top: 8px; margin-bottom: 20px; }
.dim-body.collapsed { display: none; }
.dim-wrap { margin-bottom: 6px; }

/* Dim hidden by filter */
.dim-wrap.filter-hidden { display: none; }

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
.indicator-row.rated  { border-color: #86EFAC; background: #F0FDF4; }
.indicator-row.teacher-only { border-color: #BFDBFE; background: #EFF6FF; }

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
.rating-group { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 10px; }
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
.rating-btn:hover:not(:disabled) { border-color: var(--n400); background: var(--n50); }
.rating-btn:disabled              { opacity: .5; cursor: not-allowed; }
.rating-btn.selected-1 { background: #FEE2E2; border-color: #DC2626; color: #DC2626; }
.rating-btn.selected-2 { background: #FEF3C7; border-color: #D97706; color: #D97706; }
.rating-btn.selected-3 { background: #DBEAFE; border-color: #2563EB; color: #2563EB; }
.rating-btn.selected-4 { background: #DCFCE7; border-color: #16A34A; color: #16A34A; }

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
.teacher-info-text   { flex: 1; min-width: 0; }
.teacher-info-title  { font-size: 12.5px; font-weight: 700; color: var(--blue); margin-bottom: 3px; }
.teacher-info-body   { font-size: 12.5px; color: var(--n600); line-height: 1.5; }
.teacher-avg-rating  { font-size: 15px; font-weight: 800; color: var(--blue); }

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
.dim-empty-msg.visible { display: block; }

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
.clear-dim-btn svg { width:12px; height:12px; stroke:currentColor; flex-shrink:0; }
.clear-dim-btn:hover { background:var(--redb); color:var(--red); border-color:#FECACA; }

/* ── Progress bar animations ─────────────────────────── */
.prog-fill { transition: width .4s cubic-bezier(.4,0,.2,1); }
@keyframes prog-complete {
  0%   { box-shadow: 0 0 0 0 rgba(22,163,74,.4); }
  70%  { box-shadow: 0 0 0 6px rgba(22,163,74,0); }
  100% { box-shadow: 0 0 0 0 rgba(22,163,74,0); }
}
.prog-complete { animation: prog-complete .6s ease-out forwards; }
</style>

<!-- ── PAGE HEAD ──────────────────────────────────────────── -->
<div class="page-head">
  <div class="page-head-text">
    <h2>SBM Self-Assessment</h2>
    <p>Rate all indicators across 6 dimensions using the 4 Degrees of Manifestation scale.</p>
  </div>
  <div class="page-head-actions">
    <?php if(!$isLocked): ?>
    <button class="btn btn-primary" onclick="submitAssessment()">
      <?= svgIcon('check') ?> Submit Assessment
    </button>
    <?php else: ?>
    <span class="pill pill-<?= e($cycle['status']) ?>" style="font-size:13px;padding:6px 14px;">
      <?= ucfirst(str_replace('_',' ',$cycle['status'])) ?>
    </span>
    <?php endif; ?>
  </div>
</div>

<?php if($isLocked): ?>
<div class="alert alert-info" style="margin-bottom:16px;">
  <?= svgIcon('info') ?> This assessment has been <strong><?= e($cycle['status']) ?></strong>. Responses are read-only.
</div>
<?php endif; ?>

<!-- ── PROGRESS CARD ─────────────────────────────────────── -->
<div class="card" style="margin-bottom:16px;overflow:hidden;" id="progressCard">
  <div class="card-body" style="padding:16px 20px;">

    <!-- SH progress row -->
    <div class="flex-cb" style="margin-bottom:8px;">
      <span style="font-size:13.5px;font-weight:700;color:var(--n800);">Your Progress</span>
      <span style="font-size:14px;font-weight:800;color:var(--g700);" id="shCountLabel">
        <?= $shResponded ?>/<?= $shTotal ?> Indicators Rated
      </span>
    </div>
    <div style="position:relative;height:14px;background:var(--n100);border-radius:999px;overflow:hidden;margin-bottom:8px;">
      <div id="shProgBar"
           style="height:100%;border-radius:999px;background:var(--g500);
                  width:<?= $shTotal > 0 ? round(($shResponded/$shTotal)*100) : 0 ?>%;
                  transition:width .4s cubic-bezier(.4,0,.2,1);"></div>
      <div id="shProgPct"
           style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                  font-size:10px;font-weight:700;color:var(--n500);line-height:1;">
        <?= $shTotal > 0 ? round(($shResponded/$shTotal)*100) : 0 ?>%
      </div>
    </div>

    <!-- Completion message — hidden until 100% -->
    <div id="shCompleteMsg"
         style="display:<?= $shResponded >= $shTotal && $shTotal > 0 ? 'flex' : 'none' ?>;
                align-items:center;gap:7px;
                font-size:12.5px;font-weight:600;color:var(--g700);
                background:var(--g50);border:1px solid var(--g200);
                border-radius:7px;padding:8px 12px;margin-bottom:12px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
           stroke-linecap="round" stroke-linejoin="round"
           style="width:15px;height:15px;flex-shrink:0;">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      All your indicators are rated. You can now submit the assessment.
    </div>

    <!-- All-roles row -->
    <div class="flex-cb" style="margin-bottom:5px;">
      <span style="font-size:12px;font-weight:600;color:var(--n500);">All Roles Combined</span>
      <span style="font-size:12px;font-weight:700;color:var(--n500);" id="allCountLabel">
        <?= $totalDone ?>/<?= $totalCount ?>
      </span>
    </div>
    <div style="height:7px;background:var(--n100);border-radius:999px;overflow:hidden;margin-bottom:14px;">
      <div id="allProgBar"
           style="height:100%;border-radius:999px;background:var(--blue);
                  width:<?= $totalCount > 0 ? round(($totalDone/$totalCount)*100) : 0 ?>%;
                  transition:width .4s cubic-bezier(.4,0,.2,1);"></div>
    </div>

    <!-- Rating breakdown chips -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;" id="ratingBreakdown">
      <?php
      $rColors = [1=>'#DC2626',2=>'#D97706',3=>'#2563EB',4=>'#16A34A'];
      $rBgs    = [1=>'#FEE2E2',2=>'#FEF3C7',3=>'#DBEAFE',4=>'#DCFCE7'];
      $rLabels = [1=>'Not Yet',2=>'Emerging',3=>'Developing',4=>'Always'];
      foreach([1,2,3,4] as $rv):
        $cnt = count(array_filter($responses, fn($x) => $x['rating']==$rv));
      ?>
      <div id="ratingChip<?= $rv ?>"
           style="display:inline-flex;align-items:center;gap:5px;
                  padding:4px 10px;border-radius:999px;
                  background:<?= $rBgs[$rv] ?>;
                  border:1px solid <?= $rColors[$rv] ?>33;">
        <span style="font-size:13px;font-weight:800;color:<?= $rColors[$rv] ?>;" id="ratingCnt<?= $rv ?>"><?= $cnt ?></span>
        <span style="font-size:11px;font-weight:600;color:<?= $rColors[$rv] ?>;"><?= $rLabels[$rv] ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<!-- ── FILTER BAR ────────────────────────────────────────── -->
<?php
$shCount      = count($shIndicators);
$teacherCount = $totalCount - $shCount;
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
  <?php foreach($grouped as $dimNo => $inds): ?>
  <?php
    $dimDone  = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
    $dimTotal = count($inds);
    $dimFull  = $dimDone === $dimTotal;
  ?>
  <a href="#dim<?= $dimNo ?>" id="dimTab<?= $dimNo ?>"
     data-done="<?= $dimDone ?>"
     data-total="<?= $dimTotal ?>"
     style="display:inline-flex;align-items:center;gap:5px;
            padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;
            background:<?= $dimFull?'var(--g600)':'var(--white)' ?>;
            color:<?= $dimFull?'#fff':'var(--n600)' ?>;
            border:1px solid <?= $dimFull?'var(--g600)':'var(--n200)' ?>;
            text-decoration:none;
            transition:background .3s,color .3s,border-color .3s;">
    D<?= $dimNo ?>
    <span id="dimTabCount<?= $dimNo ?>" style="opacity:.7;">(<?= $dimDone ?>/<?= $dimTotal ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- ── INDICATORS BY DIMENSION ───────────────────────────── -->
<?php foreach($grouped as $dimNo => $inds): ?>
<?php
$dim     = $inds[0];
$dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
$allDone = $dimDone === count($inds);
$dimShCount  = count(array_filter($inds, fn($i) => !in_array($i['indicator_code'], TEACHER_INDICATOR_CODES)));
$dimTchCount = count($inds) - $dimShCount;
?>
<div class="dim-wrap" id="dim<?= $dimNo ?>"
     data-dim="<?= $dimNo ?>"
     data-sh-count="<?= $dimShCount ?>"
     data-teacher-count="<?= $dimTchCount ?>">

  <div class="dim-header"
       onclick="toggleDim(<?= $dimNo ?>)"
       style="border-left:4px solid <?= e($dim['color_hex']) ?>;">

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
            $ds->execute([$cycle['cycle_id'],$dimNo]);
            $pctRow = $ds->fetchColumn();
            echo $pctRow ? number_format($pctRow,1).'%' : '—';
        } else echo '—';
      ?>
    </div>

    <?php if($allDone): ?>
    <span style="font-size:11px;font-weight:700;color:#16A34A;
                 background:#DCFCE7;border:1px solid #86EFAC;
                 border-radius:999px;padding:3px 10px;flex-shrink:0;">
      Complete
    </span>
    <?php else: ?>
    <span style="font-size:11px;font-weight:600;color:var(--n500);
                 background:var(--n100);border-radius:999px;
                 padding:3px 10px;flex-shrink:0;" id="dimLeft<?= $dimNo ?>">
      <?= count($inds)-$dimDone ?> left
    </span>
    <?php endif; ?>

    <?php if(!$isLocked): ?>
    <button class="clear-dim-btn" id="clearDimBtn<?= $dimNo ?>"
            onclick="event.stopPropagation();confirmClearDim(<?= $dimNo ?>)"
            title="Clear all ratings in this dimension"
            style="<?= $dimDone > 0 ? 'display:inline-flex;' : 'display:none;' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
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

    <?php foreach($inds as $ind): ?>
    <?php
    $resp      = $responses[$ind['indicator_id']] ?? null;
    $rated     = $resp !== null;
    $isTeacher = in_array($ind['indicator_code'], TEACHER_INDICATOR_CODES);
    $role      = $isTeacher ? 'teacher' : 'sh';
    $trData    = $teacherData[$ind['indicator_id']] ?? null;
    ?>

    <div class="indicator-row <?= $rated ? 'rated' : '' ?> <?= $isTeacher ? 'teacher-only' : '' ?>"
         id="row<?= $ind['indicator_id'] ?>"
         data-role="<?= $role ?>"
         data-code="<?= e($ind['indicator_code']) ?>">

      <!-- Top row: code + role tag + saved badge -->
      <div class="flex-cb" style="margin-bottom:6px;">
        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">

          <span style="font-family:monospace;font-size:11px;font-weight:700;
                       color:var(--n500);letter-spacing:.5px;text-transform:uppercase;">
            <?= e($ind['indicator_code']) ?>
          </span>

          <?php if($isTeacher): ?>
          <span class="role-tag role-teacher">
            <span style="display:inline-flex;width:11px;height:11px;flex-shrink:0;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </span>
            Teacher Indicator
          </span>
          <?php else: ?>
          <span class="role-tag role-sh">
            <span style="display:inline-flex;width:11px;height:11px;flex-shrink:0;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;">
                <circle cx="12" cy="8" r="4"/>
                <path d="M20 21a8 8 0 1 0-16 0"/>
              </svg>
            </span>
            School Head
          </span>
          <?php endif; ?>

        </div>

        <?php if(!$isTeacher): ?>
        <div style="display:flex;align-items:center;gap:6px;">
          <span id="savedBadge<?= $ind['indicator_id'] ?>"
                style="font-size:11px;color:var(--g600);font-weight:600;">
            <?= $rated ? 'Saved' : '' ?>
          </span>
          <?php if(!$isLocked): ?>
          <button class="clear-btn"
                  id="clearBtn<?= $ind['indicator_id'] ?>"
                  onclick="confirmClear(<?= $ind['indicator_id'] ?>)"
                  title="Clear this rating">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
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

      <?php if($isTeacher): ?>
      <!-- TEACHER INFO BOX -->
      <div class="teacher-info-box">
        <div class="teacher-info-icon">
          <svg viewBox="0 0 24 24">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="teacher-info-text">
          <?php if($trData && (int)$trData['teacher_count'] > 0): ?>
            <div class="teacher-info-title">
              Teacher Average:
              <span class="teacher-avg-rating"><?= $trData['avg_rating'] ?>/4.00</span>
            </div>
            <div class="teacher-info-body">
              <?= (int)$trData['teacher_count'] ?> response(s) &nbsp;·&nbsp; <?= e($trData['teachers']) ?>
            </div>
          <?php else: ?>
            <div class="teacher-info-title">Teacher Indicator</div>
            <div class="teacher-info-body">No teacher input yet. Teachers rate this in their own portal.</div>
          <?php endif; ?>
        </div>
      </div>

      <?php else: ?>
      <!-- SCHOOL HEAD RATING -->
      <div class="rating-group" id="ratingGroup<?= $ind['indicator_id'] ?>">
        <?php foreach([1,2,3,4] as $r): ?>
        <button <?= $isLocked ? 'disabled' : '' ?>
                type="button"
                class="rating-btn <?= $resp&&$resp['rating']==$r ? 'selected-'.$r : '' ?>"
                data-ind="<?= $ind['indicator_id'] ?>"
                data-rating="<?= $r ?>"
                onclick="selectRating(<?= $ind['indicator_id'] ?>,<?= $r ?>)">
          <?= $r ?> — <?= $ratingLabels[$r] ?>
        </button>
        <?php endforeach; ?>
      </div>

      <textarea class="fc"
                id="evidence<?= $ind['indicator_id'] ?>"
                rows="2"
                placeholder="Describe evidence or attach MOV reference…"
                <?= $isLocked ? 'disabled' : '' ?>
                onblur="saveResponse(<?= $ind['indicator_id'] ?>)"><?= e($resp['evidence_text'] ?? '') ?></textarea>

      <?php endif; ?>
    </div><!-- /.indicator-row -->

    <?php endforeach; ?>
  </div><!-- /.dim-body -->
</div><!-- /.dim-wrap -->
<?php endforeach; ?>

<!-- ── SUBMIT BUTTON ─────────────────────────────────────── -->
<div style="text-align:center;padding:20px 0;margin-top:8px;">
  <?php if(!$isLocked): ?>
  <button class="btn btn-primary"
          style="padding:12px 32px;font-size:15px;"
          onclick="submitAssessment()">
    <?= svgIcon('check') ?> Submit Self-Assessment
    <span id="submitCount">(<?= $shResponded ?>/<?= $shTotal ?> your indicators rated)</span>
  </button>
  <?php endif; ?>
</div>

<script>
// ── State ──────────────────────────────────────────────────
let currentRatings = <?= json_encode(array_map(fn($r) => $r['rating'], $responses)) ?>;
let currentFilter  = 'all';

const TEACHER_CODES = new Set(<?= json_encode(TEACHER_INDICATOR_CODES) ?>);
const COUNTS = {
  all:     <?= $totalCount ?>,
  sh:      <?= $shCount ?>,
  teacher: <?= $teacherCount ?>
};

// Progress tracking state (mutable as user rates)
const progress = {
  shDone:   <?= $shResponded ?>,
  shTotal:  <?= $shTotal ?>,
  allDone:  <?= $totalDone ?>,
  allTotal: <?= $totalCount ?>,
  ratings:  { 1:<?= count(array_filter($responses, fn($x)=>$x['rating']==1)) ?>, 2:<?= count(array_filter($responses, fn($x)=>$x['rating']==2)) ?>, 3:<?= count(array_filter($responses, fn($x)=>$x['rating']==3)) ?>, 4:<?= count(array_filter($responses, fn($x)=>$x['rating']==4)) ?> },
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
    progress.ratings[newRating]  = (progress.ratings[newRating] || 0) + 1;
  } else if (!prevRating) {
    progress.ratings[newRating] = (progress.ratings[newRating] || 0) + 1;
  }
  progress.prevRatings[indId] = newRating;

  // ── Update SH progress bar ──
  const shPct = progress.shTotal > 0 ? Math.round((progress.shDone / progress.shTotal) * 100) : 0;
  const shBar = document.getElementById('shProgBar');
  const shPctEl = document.getElementById('shProgPct');
  const shLbl = document.getElementById('shCountLabel');
  if (shBar) {
    shBar.style.width = shPct + '%';
    if (shPct === 100) {
      shBar.style.background = 'var(--g500)';
      shBar.classList.add('prog-complete');
    }
  }
  if (shPctEl) shPctEl.textContent = shPct + '%';
  if (shLbl)   shLbl.textContent   = `${progress.shDone}/${progress.shTotal} Indicators Rated`;

  // Show/hide completion message
  const completeMsg = document.getElementById('shCompleteMsg');
  if (completeMsg) completeMsg.style.display = shPct === 100 ? 'flex' : 'none';

  // ── Update all-roles bar ──
  const allPct = progress.allTotal > 0 ? Math.round((progress.allDone / progress.allTotal) * 100) : 0;
  const allBar = document.getElementById('allProgBar');
  const allLbl = document.getElementById('allCountLabel');
  if (allBar) allBar.style.width = allPct + '%';
  if (allLbl) allLbl.textContent = `${progress.allDone}/${progress.allTotal}`;

  // ── Update rating breakdown chips ──
  [1,2,3,4].forEach(rv => {
    const el = document.getElementById('ratingCnt' + rv);
    if (el) el.textContent = progress.ratings[rv] || 0;
  });

  // ── Update submit button count ──
  const submitCount = document.getElementById('submitCount');
  if (submitCount) submitCount.textContent = `(${progress.shDone}/${progress.shTotal} your indicators rated)`;

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
  const allCards   = dimWrap.querySelectorAll('.indicator-row');
  const ratedCards = dimWrap.querySelectorAll('.indicator-row.rated');
  const done  = ratedCards.length;
  const total = allCards.length;

  // Update tab text
  const tabCount = document.getElementById('dimTabCount' + dimNo);
  const tab      = document.getElementById('dimTab'      + dimNo);
  if (tabCount) tabCount.textContent = `(${done}/${total})`;

  // Update dim subtitle
  const subtitle = document.getElementById('dimSubtitle' + dimNo);
  if (subtitle && currentFilter === 'all') {
    subtitle.textContent = `${done}/${total} indicators rated`;
  }

  // Flash tab green when dimension completes
  if (tab && done === total) {
    tab.style.background   = 'var(--g600)';
    tab.style.color        = '#fff';
    tab.style.borderColor  = 'var(--g600)';

    // Update dim header badge
    const leftBadge = document.getElementById('dimLeft' + dimNo);
    if (leftBadge) {
      leftBadge.textContent       = 'Complete';
      leftBadge.style.color       = '#16A34A';
      leftBadge.style.background  = '#DCFCE7';
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

  const isTeacher = row.dataset.role === 'teacher';
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

  // Rerender progress bars
  const shPct = progress.shTotal > 0 ? Math.round((progress.shDone / progress.shTotal) * 100) : 0;
  const shBar = document.getElementById('shProgBar');
  if (shBar) {
    shBar.style.width = shPct + '%';
    shBar.style.background = shPct < 100 ? 'var(--g400)' : 'var(--g500)';
    shBar.classList.remove('prog-complete');
  }
  const shPctEl = document.getElementById('shProgPct');
  if (shPctEl) shPctEl.textContent = shPct + '%';
  const shLbl = document.getElementById('shCountLabel');
  if (shLbl) shLbl.textContent = `${progress.shDone}/${progress.shTotal} Indicators Rated`;

  const completeMsg = document.getElementById('shCompleteMsg');
  if (completeMsg) completeMsg.style.display = 'none';

  const allPct = progress.allTotal > 0 ? Math.round((progress.allDone / progress.allTotal) * 100) : 0;
  const allBar = document.getElementById('allProgBar');
  if (allBar) allBar.style.width = allPct + '%';
  const allLbl = document.getElementById('allCountLabel');
  if (allLbl) allLbl.textContent = `${progress.allDone}/${progress.allTotal}`;

  [1,2,3,4].forEach(rv => {
    const el = document.getElementById('ratingCnt' + rv);
    if (el) el.textContent = progress.ratings[rv] || 0;
  });

  const submitCount = document.getElementById('submitCount');
  if (submitCount) submitCount.textContent = `(${progress.shDone}/${progress.shTotal} your indicators rated)`;

  // Update dim tab — may need to un-green it
  const row = document.getElementById('row' + indId);
  if (!row) return;
  const dimWrap = row.closest('.dim-wrap');
  if (!dimWrap) return;
  const dimNo = dimWrap.dataset.dim;

  const allCards   = dimWrap.querySelectorAll('.indicator-row');
  const ratedCards = dimWrap.querySelectorAll('.indicator-row.rated');
  const done  = ratedCards.length;
  const total = allCards.length;

  const tabCount = document.getElementById('dimTabCount' + dimNo);
  if (tabCount) tabCount.textContent = `(${done}/${total})`;
  const subtitle = document.getElementById('dimSubtitle' + dimNo);
  if (subtitle) subtitle.textContent = `${done}/${total} indicators rated`;

  // Un-green the tab since it's no longer complete
  const tab = document.getElementById('dimTab' + dimNo);
  if (tab && done < total) {
    tab.style.background  = 'var(--white)';
    tab.style.color       = 'var(--n600)';
    tab.style.borderColor = 'var(--n200)';
  }

  // Update dim left badge
  const leftBadge = document.getElementById('dimLeft' + dimNo);
  if (leftBadge && done < total) {
    leftBadge.textContent          = `${total - done} left`;
    leftBadge.style.color          = 'var(--n500)';
    leftBadge.style.background     = 'var(--n100)';
    leftBadge.style.border         = '';
    leftBadge.style.fontWeight     = '600';
  }

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

  // Get dimension_id from first SH indicator in this dim
  const firstSHRow = dimWrap.querySelector('.indicator-row[data-role="sh"]');
  if (!firstSHRow) { toast('No school head indicators in this dimension.', 'warning'); return; }

  // Get indicator_id to look up dimension
  const firstIndId = firstSHRow.id.replace('row','');
  // We need dimension_id — pass dimNo and resolve server-side
  const r = await apiPost('self_assessment.php', {
    action: 'clear_dimension',
    dimension_id: dimNo  // server resolves via JOIN
  });

  if (!r.ok) { toast(r.msg, 'err'); return; }

  // Reset every SH card in this dimension
  dimWrap.querySelectorAll('.indicator-row[data-role="sh"]').forEach(row => {
    const indId = row.id.replace('row','');
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

    // Update progress state
    progress.shDone  = Math.max(0, progress.shDone - 1);
    progress.allDone = Math.max(0, progress.allDone - 1);
    if (prevRating) progress.ratings[prevRating] = Math.max(0, (progress.ratings[prevRating]||1) - 1);
    delete progress.prevRatings[indId];
  });

  // Re-render all progress UI at once
  const shPct = progress.shTotal > 0 ? Math.round((progress.shDone/progress.shTotal)*100) : 0;
  const shBar = document.getElementById('shProgBar');
  if (shBar) { shBar.style.width = shPct+'%'; shBar.classList.remove('prog-complete'); }
  const shPctEl = document.getElementById('shProgPct');
  if (shPctEl) shPctEl.textContent = shPct+'%';
  const shLbl = document.getElementById('shCountLabel');
  if (shLbl) shLbl.textContent = `${progress.shDone}/${progress.shTotal} Indicators Rated`;
  const completeMsg = document.getElementById('shCompleteMsg');
  if (completeMsg) completeMsg.style.display = 'none';

  const allPct = progress.allTotal > 0 ? Math.round((progress.allDone/progress.allTotal)*100) : 0;
  const allBar = document.getElementById('allProgBar');
  if (allBar) allBar.style.width = allPct+'%';
  const allLbl = document.getElementById('allCountLabel');
  if (allLbl) allLbl.textContent = `${progress.allDone}/${progress.allTotal}`;

  [1,2,3,4].forEach(rv => {
    const el = document.getElementById('ratingCnt'+rv);
    if (el) el.textContent = progress.ratings[rv]||0;
  });
  const submitCount = document.getElementById('submitCount');
  if (submitCount) submitCount.textContent = `(${progress.shDone}/${progress.shTotal} your indicators rated)`;

  // Un-green dim tab
  const tab = document.getElementById('dimTab'+dimNo);
  if (tab) { tab.style.background='var(--white)'; tab.style.color='var(--n600)'; tab.style.borderColor='var(--n200)'; }
  const tabCount = document.getElementById('dimTabCount'+dimNo);
  if (tabCount) tabCount.textContent = `(0/${dimWrap.querySelectorAll('.indicator-row').length})`;
  const subtitle = document.getElementById('dimSubtitle'+dimNo);
  const total = dimWrap.querySelectorAll('.indicator-row').length;
  if (subtitle) subtitle.textContent = `0/${total} indicators rated`;
  const leftBadge = document.getElementById('dimLeft'+dimNo);
  if (leftBadge) { leftBadge.textContent=`${total} left`; leftBadge.style.color='var(--n500)'; leftBadge.style.background='var(--n100)'; leftBadge.style.border=''; }
  const clearDimBtn = document.getElementById('clearDimBtn'+dimNo);
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
    all:     `Showing all ${COUNTS.all} indicators`,
    sh:      `Showing ${COUNTS.sh} School Head indicator${COUNTS.sh !== 1 ? 's' : ''}`,
    teacher: `Showing ${COUNTS.teacher} Teacher indicator${COUNTS.teacher !== 1 ? 's' : ''}`
  };
  document.getElementById('filterInfo').textContent = labels[mode];

  // Apply visibility to each card
  document.querySelectorAll('.indicator-row').forEach(row => {
    const role = row.dataset.role; // 'sh' or 'teacher'
    const show = mode === 'all'
               || (mode === 'sh'      && role === 'sh')
               || (mode === 'teacher' && role === 'teacher');
    row.classList.toggle('filter-hidden', !show);
  });

  // After hiding cards, check each dim — hide the whole dim wrap
  // if ALL its cards are hidden, and show the empty msg if none visible
  document.querySelectorAll('.dim-wrap').forEach(dimWrap => {
    const dimNo       = dimWrap.dataset.dim;
    const allCards    = dimWrap.querySelectorAll('.indicator-row');
    const visibleCards= [...allCards].filter(c => !c.classList.contains('filter-hidden'));
    const emptyMsg    = document.getElementById('dimEmpty' + dimNo);
    const dimBody     = document.getElementById('dimBody'  + dimNo);

    if (visibleCards.length === 0) {
      // All cards hidden — collapse and show empty msg, hide dim tab
      if (emptyMsg) emptyMsg.classList.add('visible');
      const tab = document.getElementById('dimTab' + dimNo);
      if (tab) tab.style.display = 'none';
      // Keep dim header visible but mark body as empty
    } else {
      if (emptyMsg) emptyMsg.classList.remove('visible');
      const tab = document.getElementById('dimTab' + dimNo);
      if (tab) tab.style.display = '';
    }

    // Update subtitle with filtered count
    const subtitle = document.getElementById('dimSubtitle' + dimNo);
    if (subtitle && mode !== 'all') {
      const roleLabel = mode === 'sh' ? 'school head' : 'teacher';
      subtitle.textContent = `${visibleCards.length} ${roleLabel} indicator${visibleCards.length !== 1 ? 's' : ''}`;
    } else if (subtitle) {
      // Restore original text
      const rated  = dimWrap.querySelectorAll('.indicator-row.rated').length;
      const total  = allCards.length;
      subtitle.textContent = `${rated}/${total} indicators rated`;
    }
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

  const row       = document.getElementById(`row${indId}`);
  const isTeacher = row?.dataset.role === 'teacher';
  const wasRated  = row?.classList.contains('rated') ?? false;

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
      badge.textContent      = 'Saved';
      badge.style.color      = 'var(--g600)';
      badge.style.fontWeight = '600';
      badge.style.fontSize   = '11px';
    }
    // ← Live progress update
    updateProgress(indId, rating, isTeacher, isNewResponse);
  } else {
    toast(r.msg, 'err');
  }
}

// ── Accordion ──────────────────────────────────────────────
function toggleDim(n) {
  const body    = document.getElementById('dimBody'    + n);
  const chevron = document.getElementById('dimChevron' + n);
  const isOpen  = !body.classList.contains('collapsed');
  body.classList.toggle('collapsed', isOpen);
  chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
}

// ── Submit ─────────────────────────────────────────────────
async function submitAssessment() {
  if (!confirm('Submit your SBM Self-Assessment to the SDO?\nYou will not be able to edit after submission.')) return;
  const r = await apiPost('self_assessment.php', { action: 'submit' });
  toast(r.msg, r.ok ? 'ok' : 'err');
  if (r.ok) setTimeout(() => location.reload(), 1200);
}

// ── Restore last filter on page load ──────────────────────
(function() {
  const saved = sessionStorage.getItem('sbmFilter');
  if (saved && saved !== 'all') setFilter(saved);
})();
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>