<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('teacher');
$db = getDB();

$uid      = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'] ?? 0;
$syId     = $db->query("SELECT sy_id FROM school_years 
                         WHERE is_current=1 LIMIT 1")->fetchColumn();

if (!$schoolId || !$syId) {
    echo '<div class="alert alert-danger">
            No school or school year configured. 
            Contact the administrator.
          </div>';
    include __DIR__.'/../includes/footer.php'; exit;
}

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    // Block submit — only School Head can submit
    if ($_POST['action'] === 'submit') {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Only the School Head can submit the assessment.'
        ]); exit;
    }

    if ($_POST['action'] === 'save_response') {
        $indicatorId = (int)$_POST['indicator_id'];
        $rating      = (int)$_POST['rating'];
        $remarks     = trim($_POST['evidence'] ?? '');

        // Validate rating
        if ($rating < 1 || $rating > 4) {
            echo json_encode(['ok'=>false,'msg'=>'Invalid rating.']); exit;
        }

        // Verify indicator is teacher-accessible
        $chk = $db->prepare("SELECT indicator_code 
                              FROM sbm_indicators 
                              WHERE indicator_id=?");
        $chk->execute([$indicatorId]);
        $code = $chk->fetchColumn();

        if (!in_array($code, TEACHER_INDICATOR_CODES)) {
            echo json_encode([
                'ok'  => false,
                'msg' => 'You are not allowed to answer this indicator.'
            ]); exit;
        }

        // Verify teacher belongs to this school
        if ((int)$_SESSION['school_id'] !== $schoolId) {
            echo json_encode(['ok'=>false,'msg'=>'Unauthorized.']); exit;
        }

        // Get or create cycle
        $cycleRow = $db->prepare("SELECT cycle_id, status 
                                  FROM sbm_cycles 
                                  WHERE school_id=? AND sy_id=?");
        $cycleRow->execute([$schoolId, $syId]);
        $cycleRow = $cycleRow->fetch();

        if (!$cycleRow) {
            $db->prepare("INSERT INTO sbm_cycles 
                            (sy_id, school_id, status, started_at) 
                          VALUES (?,?,'in_progress',NOW())")
               ->execute([$syId, $schoolId]);
            $cycleId = $db->lastInsertId();
        } else {
            $cycleId = $cycleRow['cycle_id'];
            // Block edits if already submitted/validated
            if (in_array($cycleRow['status'], ['submitted','validated'])) {
                echo json_encode([
                    'ok'  => false,
                    'msg' => 'Assessment is locked. Cannot edit.'
                ]); exit;
            }
        }

        // Save to teacher_responses table
        $db->prepare("
            INSERT INTO teacher_responses 
                (cycle_id, indicator_id, school_id, teacher_id, rating, remarks)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                rating=VALUES(rating),
                remarks=VALUES(remarks),
                updated_at=NOW()
        ")->execute([$cycleId, $indicatorId, $schoolId, $uid, $rating, $remarks]);

        echo json_encode(['ok'=>true,'msg'=>'Saved.']); exit;
    }

    exit;
}

// ── LOAD DATA ─────────────────────────────────────────────────

// Only load teacher-accessible indicators
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

// Current cycle
$cycle = $db->prepare("SELECT * FROM sbm_cycles 
                        WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

// Load THIS teacher's own responses
$responses = [];
if ($cycle) {
    $r = $db->prepare("SELECT * FROM teacher_responses 
                        WHERE cycle_id=? AND teacher_id=?");
    $r->execute([$cycle['cycle_id'], $uid]);
    foreach ($r->fetchAll() as $row) {
        $responses[$row['indicator_id']] = $row;
    }
}

// Group by dimension
$grouped = [];
foreach ($indicators as $ind) {
    $grouped[$ind['dimension_no']][] = $ind;
}

$ratingLabels = [
    1 => 'Not Yet Manifested',
    2 => 'Emerging',
    3 => 'Developing',
    4 => 'Always Manifested'
];
$ratingColors = [
    1 => '#DC2626',
    2 => '#D97706',
    3 => '#2563EB',
    4 => '#16A34A'
];

$isLocked  = $cycle && in_array($cycle['status'], ['submitted','validated']);
$totalDone = count($responses);
$totalInds = count($indicators); // 27 or however many are assigned
$progress  = $totalInds > 0 ? round(($totalDone / $totalInds) * 100) : 0;

$school = $db->prepare("SELECT * FROM schools WHERE school_id=?");
$school->execute([$schoolId]);
$school = $school->fetch();

$sy = $db->prepare("SELECT * FROM school_years WHERE sy_id=?");
$sy->execute([$syId]);
$sy = $sy->fetch();

$pageTitle  = 'SBM Self-Assessment';
$activePage = 'self_assessment.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
.dim-header {
    display:flex; align-items:center; gap:10px;
    padding:14px 18px;
    background:var(--white);
    border:1px solid var(--n200);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    cursor:pointer; user-select:none;
    transition:background .15s;
}
.dim-header:hover { background:var(--n50); }
.dim-chevron {
    margin-left:auto;
    font-size:20px; color:var(--n300);
    transition:transform .25s ease;
    flex-shrink:0;
}
.dim-body { padding-top:8px; margin-bottom:20px; }
.dim-body.collapsed { display:none; }
.dim-wrap { margin-bottom:6px; }

.ind-card {
    background:var(--white);
    border:1px solid var(--n200);
    border-radius:var(--radius);
    padding:14px 16px;
    margin-bottom:8px;
    transition:border-color .2s, background .2s;
}
.ind-card.rated {
    border-color:#86EFAC;
    background:#F0FDF4;
}

.rating-group { display:flex; gap:7px; flex-wrap:wrap; margin-bottom:10px; }
.rating-btn {
    padding:7px 14px;
    border-radius:8px;
    border:1.5px solid var(--n200);
    background:var(--white);
    font-size:12px; font-weight:600;
    cursor:pointer;
    transition:all .15s;
    color:var(--n600);
    white-space:nowrap;
}
.rating-btn:hover:not(:disabled) { border-color:var(--n400); background:var(--n50); }
.rating-btn:disabled { opacity:.5; cursor:not-allowed; }
.rating-btn.selected-1 { background:#FEE2E2; border-color:#DC2626; color:#DC2626; }
.rating-btn.selected-2 { background:#FEF3C7; border-color:#D97706; color:#D97706; }
.rating-btn.selected-3 { background:#DBEAFE; border-color:#2563EB; color:#2563EB; }
.rating-btn.selected-4 { background:#DCFCE7; border-color:#16A34A; color:#16A34A; }
</style>

<!-- PAGE HEAD -->
<div class="page-head">
    <div class="page-head-text">
        <h2>SBM Self-Assessment</h2>
        <p>
            <?= e($school['school_name'] ?? '') ?> &nbsp;·&nbsp;
            SY <?= e($sy['label'] ?? '—') ?>
        </p>
    </div>
</div>

<!-- NOTICE BANNER -->
<div class="alert alert-info" style="margin-bottom:16px;">
    <?= svgIcon('info') ?>
    <span>
        You are answering <strong><?= $totalInds ?> teacher-assigned indicators</strong>.
        Only the <strong>School Head</strong> can submit the final assessment.
        Your inputs are saved automatically.
    </span>
</div>

<?php if ($isLocked): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
    <?= svgIcon('alert-circle') ?>
    This assessment has been <strong><?= e($cycle['status']) ?></strong>.
    Your responses are now read-only.
</div>
<?php endif; ?>

<!-- PROGRESS BAR -->
<div class="card" style="margin-bottom:18px;">
    <div class="card-body" style="padding:16px 20px;">
        <div class="flex-cb" style="margin-bottom:8px;">
            <span style="font-size:13.5px;font-weight:600;color:var(--n800);">
                Your Progress
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--g700);">
                <?= $totalDone ?>/<?= $totalInds ?> Indicators Rated
            </span>
        </div>
        <div class="prog" style="height:10px;">
            <div class="prog-fill" 
                 style="width:<?= $progress ?>%;background:var(--g500);">
            </div>
        </div>
        <?php if ($progress < 100 && !$isLocked): ?>
        <p style="font-size:12px;color:var(--n400);margin-top:6px;">
            <?= $totalInds - $totalDone ?> indicators remaining.
            Your ratings are saved automatically.
        </p>
        <?php elseif($progress >= 100): ?>
        <p style="font-size:12px;color:var(--g700);font-weight:600;margin-top:6px;">
            ✓ All your indicators are rated. 
            Notify your School Head to review and submit.
        </p>
        <?php endif; ?>
    </div>
</div>

<!-- DIMENSION TABS (quick jump) -->
<div style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;
            position:sticky;top:60px;z-index:40;
            background:var(--n50);padding:8px 0;">
    <?php foreach ($grouped as $dimNo => $inds): ?>
    <?php $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']]))); ?>
    <a href="#dim<?= $dimNo ?>"
       style="display:inline-flex;align-items:center;gap:5px;
              padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;
              background:<?= $dimDone===count($inds)?'var(--g600)':'var(--white)' ?>;
              color:<?= $dimDone===count($inds)?'#fff':'var(--n600)' ?>;
              border:1px solid <?= $dimDone===count($inds)?'var(--g600)':'var(--n200)' ?>;
              text-decoration:none;">
        D<?= $dimNo ?>
        <span style="opacity:.7;">(<?= $dimDone ?>/<?= count($inds) ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- INDICATORS BY DIMENSION -->
<?php foreach ($grouped as $dimNo => $inds): ?>
<?php
$dim    = $inds[0];
$dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']])));
$allDone = $dimDone === count($inds);
?>
<div class="dim-wrap" id="dim<?= $dimNo ?>">

    <!-- Clickable header -->
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
            <div style="font-size:12px;color:var(--n400);margin-top:2px;">
                <?= $dimDone ?>/<?= count($inds) ?> indicators rated
            </div>
        </div>

        <?php if ($allDone): ?>
        <span style="font-size:11px;font-weight:700;color:#16A34A;
                     background:#DCFCE7;border:1px solid #86EFAC;
                     border-radius:999px;padding:3px 10px;flex-shrink:0;">
            ✓ Complete
        </span>
        <?php else: ?>
        <span style="font-size:11px;font-weight:600;color:var(--n500);
                     background:var(--n100);border-radius:999px;
                     padding:3px 10px;flex-shrink:0;">
            <?= count($inds) - $dimDone ?> left
        </span>
        <?php endif; ?>

        <span class="dim-chevron" id="dimChevron<?= $dimNo ?>">▾</span>
    </div>

    <!-- Collapsible body -->
    <div class="dim-body" id="dimBody<?= $dimNo ?>">
        <?php foreach ($inds as $ind): ?>
        <?php
        $resp  = $responses[$ind['indicator_id']] ?? null;
        $rated = $resp !== null;
        ?>
        <div class="ind-card <?= $rated ? 'rated' : '' ?>"
             id="row<?= $ind['indicator_id'] ?>">

            <!-- Top row: code + saved badge -->
            <div class="flex-cb" style="margin-bottom:4px;">
                <span style="font-size:11px;font-weight:700;
                             color:var(--n500);letter-spacing:.6px;
                             text-transform:uppercase;">
                    <?= e($ind['indicator_code']) ?>
                </span>
                <span id="savedBadge<?= $ind['indicator_id'] ?>"
                      style="font-size:11px;color:var(--g600);font-weight:600;">
                    <?= $rated ? '✓ Saved' : '' ?>
                </span>
            </div>

            <!-- Indicator text -->
            <div style="font-size:13.5px;font-weight:600;
                        color:var(--n900);margin:5px 0 4px;line-height:1.5;">
                <?= e($ind['indicator_text']) ?>
            </div>

            <!-- MOV guide -->
            <div style="font-size:12px;color:var(--n400);
                        margin-bottom:10px;line-height:1.5;">
                📎 MOV: <?= e($ind['mov_guide']) ?>
            </div>

            <!-- Rating buttons -->
            <div class="rating-group" id="ratingGroup<?= $ind['indicator_id'] ?>">
                <?php foreach ([1,2,3,4] as $r): ?>
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

            <!-- Remarks / Evidence -->
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

<!-- BOTTOM NOTICE -->
<div style="text-align:center;padding:24px 0;">
    <p style="font-size:13px;color:var(--n500);">
        ✓ Your responses are saved automatically when you select a rating.<br>
        Notify your <strong>School Head</strong> once you are done.
    </p>
</div>

<script>
let currentRatings = <?= json_encode(
    array_map(fn($r) => $r['rating'], $responses)
) ?>;

function selectRating(indId, rating) {
    currentRatings[indId] = rating;

    // Update button styles
    document.querySelectorAll(`#ratingGroup${indId} .rating-btn`)
        .forEach(btn => {
            const r = parseInt(btn.dataset.rating);
            btn.className = 'rating-btn' + (r === rating ? ` selected-${r}` : '');
        });

    // Auto-save
    saveResponse(indId);
}

async function saveResponse(indId) {
    const rating   = currentRatings[indId];
    if (!rating) return;

    const evidence = document.getElementById(`evidence${indId}`)?.value || '';
    const r = await apiPost('self_assessment.php', {
        action: 'save_response',
        indicator_id: indId,
        rating,
        evidence
    });

    if (r.ok) {
        const row = document.getElementById(`row${indId}`);
        if (row) row.classList.add('rated');

        const badge = document.getElementById(`savedBadge${indId}`);
        if (badge) {
            badge.textContent   = '✓ Saved';
            badge.style.color   = 'var(--g600)';
            badge.style.fontWeight = '600';
            badge.style.fontSize   = '11px';
        }
    } else {
        toast(r.msg, 'err');
    }
}

function toggleDim(n) {
    const body    = document.getElementById('dimBody' + n);
    const chevron = document.getElementById('dimChevron' + n);
    const isOpen  = !body.classList.contains('collapsed');
    body.classList.toggle('collapsed', isOpen);
    chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>