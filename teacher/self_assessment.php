<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('teacher');
$db = getDB();

$uid      = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'] ?? 0;
$syId     = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if (!$schoolId || !$syId) {
    echo '<div class="alert alert-danger">No school or school year configured. Contact the administrator.</div>';
    include __DIR__.'/../includes/footer.php'; exit;
}

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    if ($_POST['action'] === 'clear_response') {
        $indicatorId = (int)$_POST['indicator_id'];

        $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
        $chk->execute([$indicatorId]);
        $code = $chk->fetchColumn();
        if (!in_array($code, TEACHER_INDICATOR_CODES)) {
            echo json_encode(['ok'=>false,'msg'=>'Not a teacher indicator.']); exit;
        }

        $cycleRow = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycleRow->execute([$schoolId,$syId]); $cycleRow = $cycleRow->fetch();
        if (!$cycleRow) { echo json_encode(['ok'=>false,'msg'=>'No active cycle.']); exit; }
        if (in_array($cycleRow['status'], ['submitted','validated'])) {
            echo json_encode(['ok'=>false,'msg'=>'Assessment is locked.']); exit;
        }

        $db->prepare("DELETE FROM teacher_responses WHERE cycle_id=? AND indicator_id=? AND teacher_id=?")
           ->execute([$cycleRow['cycle_id'], $indicatorId, $uid]);

        echo json_encode(['ok'=>true,'msg'=>'Rating cleared.']); exit;
    }

    if ($_POST['action'] === 'submit') {
    // Get or create cycle
    $cycleRow = $db->prepare(
        "SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?"
    );
    $cycleRow->execute([$schoolId, $syId]);
    $cycleId = $cycleRow->fetchColumn();

    if (!$cycleId) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'No active assessment cycle exists yet.'
        ]); exit;
    }

    // Count how many teacher indicators this teacher has answered
    $placeholders = implode(
        ',', array_fill(0, count(TEACHER_INDICATOR_CODES), '?')
    );
    $countStmt = $db->prepare("
        SELECT COUNT(*) FROM teacher_responses tr
        JOIN sbm_indicators i ON tr.indicator_id = i.indicator_id
        WHERE tr.cycle_id  = ?
          AND tr.teacher_id = ?
          AND i.indicator_code IN ($placeholders)
    ");
    $countStmt->execute(
        array_merge([$cycleId, $uid], TEACHER_INDICATOR_CODES)
    );
    $answered = (int) $countStmt->fetchColumn();
    $required = count(TEACHER_INDICATOR_CODES);

    if ($answered < $required) {
        echo json_encode([
            'ok'  => false,
            'msg' => "Please rate all your indicators before submitting. 
                      ($answered/$required done)"
        ]); exit;
    }

    // Upsert submission record
    $db->prepare("
        INSERT INTO teacher_submissions 
            (cycle_id, teacher_id, school_id, sy_id, status, 
             submitted_at, response_count)
        VALUES (?, ?, ?, ?, 'submitted', NOW(), ?)
        ON DUPLICATE KEY UPDATE
            status         = 'submitted',
            submitted_at   = NOW(),
            response_count = VALUES(response_count)
    ")->execute([$cycleId, $uid, $schoolId, $syId, $answered]);

    $db->prepare("UPDATE teacher_responses SET status='submitted' WHERE cycle_id=? AND teacher_id=?")
       ->execute([$cycleId, $uid]);

    logActivity(
        'teacher_submit_assessment',
        'teacher_self_assessment',
        "Teacher ID $uid submitted for cycle $cycleId"
    );

    echo json_encode([
        'ok'  => true,
        'msg' => 'Your assessment has been submitted to the School Head.'
    ]); exit;
}

    if ($_POST['action'] === 'save_response') {
        $indicatorId = (int)$_POST['indicator_id'];
        $rating      = (int)$_POST['rating'];
        $remarks     = trim($_POST['evidence'] ?? '');

        if ($rating < 1 || $rating > 4) {
            echo json_encode(['ok'=>false,'msg'=>'Invalid rating.']); exit;
        }

        $chk = $db->prepare("SELECT indicator_code FROM sbm_indicators WHERE indicator_id=?");
        $chk->execute([$indicatorId]);
        $code = $chk->fetchColumn();

        if (!in_array($code, TEACHER_INDICATOR_CODES)) {
            echo json_encode(['ok'=>false,'msg'=>'You are not allowed to answer this indicator.']); exit;
        }

        // Get or create cycle
        $cycleRow = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycleRow->execute([$schoolId, $syId]); $cycleRow = $cycleRow->fetch();

        if (!$cycleRow) {
            $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())")->execute([$syId,$schoolId]);
            $cycleId = $db->lastInsertId();
        } else {
            $cycleId = $cycleRow['cycle_id'];
            if (in_array($cycleRow['status'], ['submitted','validated'])) {
                echo json_encode(['ok'=>false,'msg'=>'Assessment is locked. Cannot edit.']); exit;
            }
        }

        $db->prepare("INSERT INTO teacher_responses (cycle_id,indicator_id,school_id,teacher_id,rating,remarks)
                      VALUES (?,?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE
                        rating=VALUES(rating),
                        remarks=VALUES(remarks),
                        updated_at=NOW()")
           ->execute([$cycleId, $indicatorId, $schoolId, $uid, $rating, $remarks]);

        echo json_encode(['ok'=>true,'msg'=>'Saved.']); exit;
    }
    exit;
}

// ── LOAD DATA ─────────────────────────────────────────────────
$placeholders = implode(',', array_fill(0, count(TEACHER_INDICATOR_CODES), '?'));
$indicators   = $db->prepare("
    SELECT i.*, d.dimension_no, d.dimension_name, d.color_hex
    FROM sbm_indicators i
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    WHERE i.is_active = 1
      AND i.indicator_code IN ($placeholders)
    ORDER BY d.dimension_no, i.sort_order
");
$indicators->execute(TEACHER_INDICATOR_CODES);
$indicators = $indicators->fetchAll();

$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]); $cycle = $cycle->fetch();

// This teacher's own responses
$responses = [];
if ($cycle) {
    $r = $db->prepare("SELECT * FROM teacher_responses WHERE cycle_id=? AND teacher_id=?");
    $r->execute([$cycle['cycle_id'], $uid]);
    foreach ($r->fetchAll() as $row) $responses[$row['indicator_id']] = $row;
}

$grouped = [];
foreach ($indicators as $ind) $grouped[$ind['dimension_no']][] = $ind;

$ratingLabels = [1=>'Not Yet Manifested', 2=>'Emerging', 3=>'Developing', 4=>'Always Manifested'];
$ratingColors = [1=>'#DC2626', 2=>'#D97706', 3=>'#2563EB', 4=>'#16A34A'];
$ratingBgs    = [1=>'#FEE2E2', 2=>'#FEF3C7', 3=>'#DBEAFE', 4=>'#DCFCE7'];

// Lock if cycle is submitted/validated OR if this teacher already submitted
$cycleIsLocked = $cycle && in_array(
    $cycle['status'], ['submitted', 'validated']
);

$mySubCheck = null;
if ($cycle) {
    $subQ = $db->prepare("
        SELECT status FROM teacher_submissions 
        WHERE cycle_id=? AND teacher_id=?
    ");
    $subQ->execute([$cycle['cycle_id'], $uid]);
    $mySubCheck = $subQ->fetchColumn();
}

$isLocked = $cycleIsLocked || ($mySubCheck === 'submitted');
$totalDone = count($responses);
$totalInds = count($indicators);
$progress  = $totalInds > 0 ? round(($totalDone/$totalInds)*100) : 0;

$school = $db->prepare("SELECT * FROM schools WHERE school_id=?");
$school->execute([$schoolId]); $school = $school->fetch();
$sy = $db->prepare("SELECT * FROM school_years WHERE sy_id=?");
$sy->execute([$syId]); $sy = $sy->fetch();

$pageTitle  = 'SBM Self-Assessment';
$activePage = 'self_assessment.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Dimension accordion ── */
.dim-header {
    display:flex; align-items:center; gap:10px;
    padding:14px 18px;
    background:var(--white); border:1px solid var(--n200);
    border-radius:var(--radius); box-shadow:var(--shadow);
    cursor:pointer; user-select:none; transition:background .15s;
}
.dim-header:hover { background:var(--n50); }
.dim-chevron {
    margin-left:auto; font-size:20px; color:var(--n300);
    transition:transform .25s ease; flex-shrink:0;
}
.dim-body { padding-top:8px; margin-bottom:20px; }
.dim-body.collapsed { display:none; }
.dim-wrap { margin-bottom:6px; }

/* ── Indicator cards ── */
.ind-card {
    background:var(--white); border:1px solid var(--n200);
    border-radius:var(--radius); padding:14px 16px;
    margin-bottom:8px;
    transition:border-color .2s, background .2s;
}
.ind-card.rated { border-color:#86EFAC; background:#F0FDF4; }

/* ── Rating buttons ── */
.rating-group { display:flex; gap:7px; flex-wrap:wrap; margin-bottom:10px; }
.rating-btn {
    padding:7px 14px; border-radius:8px;
    border:1.5px solid var(--n200); background:var(--white);
    font-size:12px; font-weight:600; cursor:pointer;
    transition:all .15s; color:var(--n600); white-space:nowrap;
}
.rating-btn:hover:not(:disabled) { border-color:var(--n400); background:var(--n50); }
.rating-btn:disabled              { opacity:.5; cursor:not-allowed; }
.rating-btn.selected-1 { background:#FEE2E2; border-color:#DC2626; color:#DC2626; }
.rating-btn.selected-2 { background:#FEF3C7; border-color:#D97706; color:#D97706; }
.rating-btn.selected-3 { background:#DBEAFE; border-color:#2563EB; color:#2563EB; }
.rating-btn.selected-4 { background:#DCFCE7; border-color:#16A34A; color:#16A34A; }

/* ── Clear button ── */
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
.clear-btn svg { width:12px; height:12px; flex-shrink:0; stroke:currentColor; }
.ind-card.rated .clear-btn { opacity:1; pointer-events:all; }
.clear-btn:hover { background:var(--redb); color:var(--red); border-color:#FECACA; }

/* ── Progress animations ── */
@keyframes prog-complete {
    0%   { box-shadow:0 0 0 0 rgba(22,163,74,.5); }
    70%  { box-shadow:0 0 0 8px rgba(22,163,74,0); }
    100% { box-shadow:0 0 0 0 rgba(22,163,74,0); }
}
.prog-complete { animation:prog-complete .7s ease-out forwards; }
</style>

<!-- ── PAGE HEAD ── -->
<div class="page-head">
    <div class="page-head-text">
        <h2>SBM Self-Assessment</h2>
        <p><?= e($school['school_name'] ?? '') ?> &nbsp;·&nbsp; SY <?= e($sy['label'] ?? '—') ?></p>
    </div>
</div>

<!-- ── NOTICE ── -->
<div class="alert alert-info" style="margin-bottom:16px;">
    <?= svgIcon('info') ?>
    <span>
        You are answering <strong><?= $totalInds ?> teacher-assigned indicators</strong>.
        Only the <strong>School Head</strong> can submit the final assessment.
        Your inputs are saved automatically.
    </span>
</div>

<?php if($isLocked): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
    <?= svgIcon('alert-circle') ?>
    This assessment has been <strong><?= e($cycle['status']) ?></strong>. Your responses are read-only.
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     LIVE PROGRESS CARD
══════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:18px;overflow:hidden;" id="progressCard">
    <div class="card-body" style="padding:16px 20px;">

        <!-- Main progress row -->
        <div class="flex-cb" style="margin-bottom:8px;">
            <span style="font-size:13.5px;font-weight:700;color:var(--n800);">Your Progress</span>
            <span style="font-size:14px;font-weight:800;color:var(--g700);" id="progCountLabel">
                <?= $totalDone ?>/<?= $totalInds ?> Indicators Rated
            </span>
        </div>

        <!-- Main progress bar -->
        <div style="position:relative;height:14px;background:var(--n100);border-radius:999px;overflow:hidden;margin-bottom:10px;">
            <div id="progBar"
                 style="height:100%;border-radius:999px;
                        background:<?= $progress >= 100 ? 'var(--g500)' : 'var(--g400)' ?>;
                        width:<?= $progress ?>%;
                        transition:width .4s cubic-bezier(.4,0,.2,1);"></div>
            <div id="progPct"
                 style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                        font-size:10px;font-weight:700;color:var(--n500);line-height:1;">
                <?= $progress ?>%
            </div>
        </div>

        <!-- Completion message -->
        <div id="completeMsg"
             style="display:<?= $progress >= 100 ? 'flex' : 'none' ?>;
                    align-items:center;gap:7px;
                    font-size:12.5px;font-weight:600;color:var(--g700);
                    background:var(--g50);border:1px solid var(--g200);
                    border-radius:7px;padding:8px 12px;margin-bottom:12px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round"
                 style="width:15px;height:15px;flex-shrink:0;">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            All your indicators are rated. Notify your School Head to review and submit.
        </div>

        <!-- Rating breakdown chips -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php foreach([1,2,3,4] as $rv):
                $cnt = count(array_filter($responses, fn($x) => $x['rating']==$rv));
            ?>
            <div id="ratingChip<?= $rv ?>"
                 style="display:inline-flex;align-items:center;gap:5px;
                        padding:4px 10px;border-radius:999px;
                        background:<?= $ratingBgs[$rv] ?>;
                        border:1px solid <?= $ratingColors[$rv] ?>33;">
                <span style="font-size:13px;font-weight:800;color:<?= $ratingColors[$rv] ?>;"
                      id="ratingCnt<?= $rv ?>"><?= $cnt ?></span>
                <span style="font-size:11px;font-weight:600;color:<?= $ratingColors[$rv] ?>;"><?= $ratingLabels[$rv] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- ── STICKY DIMENSION TABS ── -->
<div style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;
            position:sticky;top:60px;z-index:40;
            background:var(--n50);padding:8px 0;">
    <?php foreach($grouped as $dimNo => $inds):
        $dimDone  = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
        $dimTotal = count($inds);
        $dimFull  = $dimDone === $dimTotal;
    ?>
    <a href="#dim<?= $dimNo ?>" id="dimTab<?= $dimNo ?>"
       data-done="<?= $dimDone ?>" data-total="<?= $dimTotal ?>"
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

<!-- ── INDICATORS BY DIMENSION ── -->
<?php foreach($grouped as $dimNo => $inds):
    $dim      = $inds[0];
    $dimDone  = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
    $dimTotal = count($inds);
    $allDone  = $dimDone === $dimTotal;
?>
<div class="dim-wrap" id="dim<?= $dimNo ?>" data-dim="<?= $dimNo ?>">

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
            <div style="font-size:14px;font-weight:700;color:var(--n900);">
                Dimension <?= $dimNo ?>: <?= e($dim['dimension_name']) ?>
            </div>
            <div style="font-size:12px;color:var(--n400);margin-top:2px;"
                 id="dimSubtitle<?= $dimNo ?>">
                <?= $dimDone ?>/<?= $dimTotal ?> indicators rated
            </div>
        </div>

        <span style="font-size:11px;font-weight:600;border-radius:999px;
                     padding:3px 10px;flex-shrink:0;
                     transition:all .3s;"
              id="dimLeft<?= $dimNo ?>"
              <?php if($allDone): ?>
              style="color:#16A34A;background:#DCFCE7;border:1px solid #86EFAC;font-weight:700;"
              <?php else: ?>
              style="color:var(--n500);background:var(--n100);"
              <?php endif; ?>>
            <?= $allDone ? 'Complete' : ($dimTotal-$dimDone).' left' ?>
        </span>

        <span class="dim-chevron" id="dimChevron<?= $dimNo ?>">▾</span>
    </div>

    <div class="dim-body" id="dimBody<?= $dimNo ?>">
        <?php foreach($inds as $ind):
            $resp  = $responses[$ind['indicator_id']] ?? null;
            $rated = $resp !== null;
        ?>
        <div class="ind-card <?= $rated ? 'rated' : '' ?>" id="row<?= $ind['indicator_id'] ?>">

            <!-- Header row -->
            <div class="flex-cb" style="margin-bottom:4px;">
                <span style="font-family:monospace;font-size:11px;font-weight:700;
                             color:var(--n500);letter-spacing:.6px;text-transform:uppercase;">
                    <?= e($ind['indicator_code']) ?>
                </span>
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
            </div>

            <!-- Indicator text -->
            <div style="font-size:13.5px;font-weight:600;color:var(--n900);
                        margin:5px 0 4px;line-height:1.5;">
                <?= e($ind['indicator_text']) ?>
            </div>

            <!-- MOV -->
            <div style="font-size:12px;color:var(--n400);margin-bottom:10px;line-height:1.5;">
                📎 MOV: <?= e($ind['mov_guide']) ?>
            </div>

            <!-- Rating buttons -->
            <div class="rating-group" id="ratingGroup<?= $ind['indicator_id'] ?>">
                <?php foreach([1,2,3,4] as $r): ?>
                <button <?= $isLocked ? 'disabled' : '' ?>
                        type="button"
                        class="rating-btn <?= ($resp && $resp['rating']==$r) ? 'selected-'.$r : '' ?>"
                        data-ind="<?= $ind['indicator_id'] ?>"
                        data-rating="<?= $r ?>"
                        onclick="selectRating(<?= $ind['indicator_id'] ?>,<?= $r ?>)">
                    <?= $r ?> — <?= $ratingLabels[$r] ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Evidence / Remarks -->
            <textarea class="fc"
                      id="evidence<?= $ind['indicator_id'] ?>"
                      rows="2"
                      placeholder="Add remarks or describe your evidence…"
                      <?= $isLocked ? 'disabled' : '' ?>
                      onblur="saveResponse(<?= $ind['indicator_id'] ?>)"
                      style="margin-top:4px;"><?= e($resp['remarks'] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>
    </div>

</div>
<?php endforeach; ?>

<!-- ── BOTTOM SUBMIT ── -->
<div style="text-align:center;padding:24px 0 32px;">
    <?php
    // Check if already submitted
    $subCheck = $db->prepare("
        SELECT status, submitted_at 
        FROM teacher_submissions 
        WHERE cycle_id=? AND teacher_id=?
    ");
    $subCheck->execute([$cycle['cycle_id'] ?? 0, $uid]);
    $mySubmission = $subCheck->fetch();
    ?>

    <?php if ($mySubmission && $mySubmission['status'] === 'submitted'): ?>
    <div style="display:inline-flex;align-items:center;gap:10px;
                padding:14px 24px;border-radius:10px;
                background:var(--g50);border:1.5px solid var(--g200);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#16A34A" 
             stroke-width="2.5" stroke-linecap="round" 
             stroke-linejoin="round" 
             style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <div style="text-align:left;">
            <div style="font-size:14px;font-weight:700;color:var(--g700);">
                Assessment Submitted
            </div>
            <div style="font-size:12px;color:var(--n500);margin-top:2px;">
                Submitted on 
                <?= date('F d, Y g:i A', 
                    strtotime($mySubmission['submitted_at'])) ?>
            </div>
        </div>
    </div>

    <?php elseif (!$isLocked): ?>
    <button class="btn btn-primary"
            style="padding:12px 36px;font-size:15px;"
            id="submitBtn"
            onclick="submitMyAssessment()">
        <?= svgIcon('check') ?> Submit to School Head
        <span id="submitCount" 
              style="font-size:12px;opacity:.8;margin-left:4px;">
            (<?= $totalDone ?>/<?= $totalInds ?> rated)
        </span>
    </button>
    <p style="font-size:12px;color:var(--n400);margin-top:10px;">
        Once submitted, your responses will be locked.<br>
        Make sure all <?= $totalInds ?> indicators are rated first.
    </p>

    <?php else: ?>
    <p style="font-size:13px;color:var(--n500);">
        This assessment cycle is 
        <strong><?= e($cycle['status'] ?? '') ?></strong>.
        Your responses are read-only.
    </p>
    <?php endif; ?>
</div>

<script>
// ── State ──────────────────────────────────────────────────
let currentRatings = <?= json_encode(array_map(fn($r) => $r['rating'], $responses)) ?>;

const prog = {
    done:  <?= $totalDone ?>,
    total: <?= $totalInds ?>,
    ratings: {
        1: <?= count(array_filter($responses, fn($x)=>$x['rating']==1)) ?>,
        2: <?= count(array_filter($responses, fn($x)=>$x['rating']==2)) ?>,
        3: <?= count(array_filter($responses, fn($x)=>$x['rating']==3)) ?>,
        4: <?= count(array_filter($responses, fn($x)=>$x['rating']==4)) ?>
    },
    prevRatings: Object.assign({}, <?= json_encode(array_map(fn($r) => $r['rating'], $responses)) ?>)
};

// ── Live progress updater ──────────────────────────────────
function updateProgress(indId, newRating, isNewResponse) {
    const prevRating = prog.prevRatings[indId] ?? null;

    if (isNewResponse)  prog.done++;

    // Adjust rating breakdown
    if (prevRating && prevRating !== newRating) {
        prog.ratings[prevRating] = Math.max(0, prog.ratings[prevRating] - 1);
        prog.ratings[newRating]  = (prog.ratings[newRating] || 0) + 1;
    } else if (!prevRating) {
        prog.ratings[newRating] = (prog.ratings[newRating] || 0) + 1;
    }
    prog.prevRatings[indId] = newRating;

    const pct = prog.total > 0 ? Math.round((prog.done / prog.total) * 100) : 0;

    // Update bar
    const bar = document.getElementById('progBar');
    if (bar) {
        bar.style.width = pct + '%';
        if (pct === 100) {
            bar.style.background = 'var(--g500)';
            bar.classList.add('prog-complete');
        }
    }
    const pctEl = document.getElementById('progPct');
    if (pctEl) pctEl.textContent = pct + '%';

    const lbl = document.getElementById('progCountLabel');
    if (lbl) lbl.textContent = `${prog.done}/${prog.total} Indicators Rated`;

    // Completion message
    const msg = document.getElementById('completeMsg');
    if (msg) msg.style.display = pct === 100 ? 'flex' : 'none';

    // Rating breakdown chips
    [1,2,3,4].forEach(rv => {
        const el = document.getElementById('ratingCnt' + rv);
        if (el) el.textContent = prog.ratings[rv] || 0;
    });

    // Dimension tab
    updateDimTab(indId);
}

function updateDimTab(indId) {
    const row = document.getElementById('row' + indId);
    if (!row) return;
    const dimWrap = row.closest('.dim-wrap');
    if (!dimWrap) return;
    const dimNo = dimWrap.dataset.dim;

    const allCards   = dimWrap.querySelectorAll('.ind-card');
    const ratedCards = dimWrap.querySelectorAll('.ind-card.rated');
    const done  = ratedCards.length;
    const total = allCards.length;

    // Tab count
    const tabCount = document.getElementById('dimTabCount' + dimNo);
    if (tabCount) tabCount.textContent = `(${done}/${total})`;

    // Subtitle
    const subtitle = document.getElementById('dimSubtitle' + dimNo);
    if (subtitle) subtitle.textContent = `${done}/${total} indicators rated`;

    // Flash green on complete
    const tab      = document.getElementById('dimTab'  + dimNo);
    const leftBadge = document.getElementById('dimLeft' + dimNo);
    if (done === total) {
        if (tab) {
            tab.style.background  = 'var(--g600)';
            tab.style.color       = '#fff';
            tab.style.borderColor = 'var(--g600)';
        }
        if (leftBadge) {
            leftBadge.textContent   = 'Complete';
            leftBadge.style.color   = '#16A34A';
            leftBadge.style.background  = '#DCFCE7';
            leftBadge.style.border  = '1px solid #86EFAC';
            leftBadge.style.fontWeight  = '700';
        }
    }
}

// ── Clear response ────────────────────────────────────────
function confirmClear(indId) {
  const row = document.getElementById('row' + indId);
  const code = row?.querySelector('[style*="monospace"]')?.textContent?.trim() || indId;
  if (!confirm(`Clear the rating for indicator ${code}?\nThis will remove your saved answer.`)) return;
  clearResponse(indId);
}

async function clearResponse(indId) {
  const row = document.getElementById('row' + indId);
  if (!row || !row.classList.contains('rated')) return;

  const prevRating = prog.prevRatings[indId] ?? null;

  const r = await apiPost('self_assessment.php', {
    action: 'clear_response',
    indicator_id: indId
  });
  if (!r.ok) { toast(r.msg, 'err'); return; }

  // Reset card
  row.classList.remove('rated');
  delete currentRatings[indId];

  document.querySelectorAll(`#ratingGroup${indId} .rating-btn`).forEach(btn => {
    btn.className = 'rating-btn';
  });
  const ev = document.getElementById('evidence' + indId);
  if (ev) ev.value = '';
  const badge = document.getElementById('savedBadge' + indId);
  if (badge) badge.textContent = '';

  // Update progress
  prog.done = Math.max(0, prog.done - 1);
  if (prevRating) prog.ratings[prevRating] = Math.max(0, (prog.ratings[prevRating]||1) - 1);
  delete prog.prevRatings[indId];

  const pct = prog.total > 0 ? Math.round((prog.done / prog.total) * 100) : 0;
  const bar = document.getElementById('progBar');
  if (bar) { bar.style.width = pct+'%'; bar.classList.remove('prog-complete'); }
  const pctEl = document.getElementById('progPct');
  if (pctEl) pctEl.textContent = pct+'%';
  const lbl = document.getElementById('progCountLabel');
  if (lbl) lbl.textContent = `${prog.done}/${prog.total} Indicators Rated`;
  const msg = document.getElementById('completeMsg');
  if (msg) msg.style.display = 'none';
  [1,2,3,4].forEach(rv => {
    const el = document.getElementById('ratingCnt'+rv);
    if (el) el.textContent = prog.ratings[rv]||0;
  });

  // Update dim tab
  const dimWrap = row.closest('.dim-wrap');
  if (dimWrap) {
    const dimNo = dimWrap.dataset.dim;
    const done  = dimWrap.querySelectorAll('.ind-card.rated').length;
    const total = dimWrap.querySelectorAll('.ind-card').length;
    const tabCount = document.getElementById('dimTabCount'+dimNo);
    if (tabCount) tabCount.textContent = `(${done}/${total})`;
    const subtitle = document.getElementById('dimSubtitle'+dimNo);
    if (subtitle) subtitle.textContent = `${done}/${total} indicators rated`;
    const tab = document.getElementById('dimTab'+dimNo);
    if (tab && done < total) {
      tab.style.background='var(--white)'; tab.style.color='var(--n600)'; tab.style.borderColor='var(--n200)';
    }
    const leftBadge = document.getElementById('dimLeft'+dimNo);
    if (leftBadge && done < total) {
      leftBadge.textContent=`${total-done} left`; leftBadge.style.color='var(--n500)';
      leftBadge.style.background='var(--n100)'; leftBadge.style.border='';
    }
  }

  toast('Rating cleared.', 'ok');
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

    const row          = document.getElementById(`row${indId}`);
    const wasRated     = row?.classList.contains('rated') ?? false;
    const isNewResponse = !wasRated;

    const evidence = document.getElementById(`evidence${indId}`)?.value || '';
    const r = await apiPost('self_assessment.php', {
        action: 'save_response',
        indicator_id: indId,
        rating,
        evidence
    });

    if (r.ok) {
        if (row) row.classList.add('rated');
        const badge = document.getElementById(`savedBadge${indId}`);
        if (badge) {
            badge.textContent      = 'Saved';
            badge.style.color      = 'var(--g600)';
            badge.style.fontWeight = '600';
            badge.style.fontSize   = '11px';
        }
        updateProgress(indId, rating, isNewResponse);
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

async function submitMyAssessment() {
    if (!confirm(
        'Submit your assessment to the School Head?\n\n' +
        'Once submitted, you will not be able to edit your responses.'
    )) return;

    const btn = document.getElementById('submitBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Submitting…'; }

    const r = await apiPost('self_assessment.php', { action: 'submit' });
    toast(r.msg, r.ok ? 'ok' : 'err');

    if (r.ok) {
        setTimeout(() => location.reload(), 1000);
    } else {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `${svgI('check')} Submit to School Head`;
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>