<?php
ob_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('external_stakeholder');
$db = getDB();

$uid = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query(
    "SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1"
)->fetchColumn();

if (!$schoolId || !$syId) {
    echo '<div class="alert alert-danger">
              No school or school year configured. 
              Contact the administrator.
          </div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Check if cycle exists and is started ──────────────────────
$cycleCheck = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycleCheck->execute([$schoolId, $syId]);
$cycleCheck = $cycleCheck->fetch();

if ($cycleCheck && in_array($cycleCheck['status'], ['completed', 'finalized', 'validated'])) {
    $pageTitle = 'SBM Self-Assessment';
    $activePage = 'self_assessment.php';
    include __DIR__ . '/../includes/header.php';
    $stSyLbl = $db->prepare("SELECT label FROM school_years WHERE sy_id=? LIMIT 1");
    $stSyLbl->execute([$syId]);
    $syLabel = $stSyLbl->fetchColumn() ?: '—';
    ?>
    <div class="page-head">
        <div class="page-head-text">
            <h2>SBM Self-Assessment</h2>
            <p>Dasmariñas Integrated High School &nbsp;·&nbsp; SY <?= e($syLabel) ?></p>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:70px 20px;">
            <div style="width:72px;height:72px;border-radius:50%;
                    background:#FEE2E2;
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" style="width:32px;height:32px;">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                </svg>
            </div>
            <h3 style="font-size:20px;font-weight:800;color:var(--n800);margin-bottom:10px;">
                Assessment Already Finalized
            </h3>
            <p style="font-size:14px;color:var(--n500);max-width:420px;
                  margin:0 auto 8px;line-height:1.7;">
                The SBM Self-Assessment for this school year has already been
                finalized. No further responses can be submitted or modified.
            </p>
            <p style="font-size:12.5px;color:var(--n400);">
                Please contact your School Head if you have concerns.
            </p>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if (!$cycleCheck) {
    $pageTitle = 'SBM Self-Assessment';
    $activePage = 'self_assessment.php';
    include __DIR__ . '/../includes/header.php';
    $stSyLbl = $db->prepare("SELECT label FROM school_years WHERE sy_id=? LIMIT 1");
    $stSyLbl->execute([$syId]);
    $syLabel = $stSyLbl->fetchColumn() ?: '—';
    ?>
    <div class="page-head">
        <div class="page-head-text">
            <h2>SBM Self-Assessment</h2>
            <p>Dasmariñas Integrated High School &nbsp;·&nbsp; SY <?= e($syLabel) ?></p>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:70px 20px;">
            <div style="width:72px;height:72px;border-radius:50%;
                    background:var(--amber-bg);
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" style="width:32px;height:32px;">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
            </div>
            <h3 style="font-size:20px;font-weight:800;color:var(--n800);margin-bottom:10px;">
                Assessment Not Started Yet
            </h3>
            <p style="font-size:14px;color:var(--n500);max-width:420px;
                  margin:0 auto 8px;line-height:1.7;">
                The School Head has not started the SBM Self-Assessment cycle
                for this school year yet. You will be notified once it becomes available.
            </p>
            <p style="font-size:12.5px;color:var(--n400);">
                Please check back later or contact your School Head.
            </p>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    if ($_POST['action'] === 'save_response') {
        $indicatorId = (int) $_POST['indicator_id'];
        $rating = (int) $_POST['rating'];
        $remarks = trim($_POST['evidence'] ?? '');

        // Block if cycle is finalized/completed at the backend level
        $cycleStatusChk = $db->prepare(
            "SELECT status FROM sbm_cycles WHERE school_id=? AND sy_id=?"
        );
        $cycleStatusChk->execute([$schoolId, $syId]);
        $cycleStatus = $cycleStatusChk->fetchColumn();
        if (in_array($cycleStatus, ['completed', 'finalized', 'validated'])) {
            echo json_encode(['ok' => false, 'msg' => 'Assessment is already finalized. Responses cannot be saved.']);
            exit;
        }

        if ($rating < 1 || $rating > 4) {
            echo json_encode([
                'ok' => false,
                'msg' => 'Invalid rating.'
            ]);
            exit;
        }

        $chk = $db->prepare(
            "SELECT indicator_code FROM sbm_indicators 
             WHERE indicator_id=?"
        );
        $chk->execute([$indicatorId]);
        $code = $chk->fetchColumn();

        if (!in_array($code, STAKEHOLDER_INDICATOR_CODES)) {
            echo json_encode([
                'ok' => false,
                'msg' => 'You are not allowed to answer this indicator.'
            ]);
            exit;
        }

        // Get or create cycle
        $cycleRow = $db->prepare(
            "SELECT cycle_id, status 
             FROM sbm_cycles WHERE school_id=? AND sy_id=?"
        );
        $cycleRow->execute([$schoolId, $syId]);
        $cycleRow = $cycleRow->fetch();

        if (!$cycleRow) {
            try {
                $insertStmt = $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())");
                $insertStmt->execute([$syId, $schoolId]);
                $cycleId = (int) $db->lastInsertId();

                if (!$cycleId) {
                    echo json_encode(['ok' => false, 'msg' => 'Failed to create assessment cycle.']);
                    exit;
                }

                // Initialize dimension scores
                $dimIds = $db->query("SELECT dimension_id FROM sbm_dimensions")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($dimIds as $dId) {
                    $st = $db->prepare("INSERT IGNORE INTO sbm_dimension_scores (cycle_id, school_id, dimension_id, raw_score, max_score, percentage) VALUES (?, ?, ?, 0, 0, 0)");
                    $st->execute([$cycleId, $schoolId, $dId]);
                }
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') {
                    $retry = $db->prepare(
                        "SELECT cycle_id FROM sbm_cycles 
                 WHERE school_id=? AND sy_id=?"
                    );
                    $retry->execute([$schoolId, $syId]);
                    $cycleId = $retry->fetchColumn();
                    if (!$cycleId) {
                        echo json_encode(['ok' => false, 'msg' => 'Cycle creation failed. Please try again.']);
                        exit;
                    }
                } else {
                    throw $e;
                }
            }
        } else {
            $cycleId = $cycleRow['cycle_id'];
            if (
                in_array(
                    $cycleRow['status'],
                    ['submitted', 'validated']
                )
            ) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Assessment is locked.'
                ]);
                exit;
            }
        }

        $db->prepare("
            INSERT INTO stakeholder_responses 
                (cycle_id,indicator_id,school_id,
                 stakeholder_id,rating,remarks)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                rating     = VALUES(rating),
                remarks    = VALUES(remarks),
                updated_at = NOW()
        ")->execute([
                    $cycleId,
                    $indicatorId,
                    $schoolId,
                    $uid,
                    $rating,
                    $remarks
                ]);

        echo json_encode(['ok' => true, 'msg' => 'Saved.']);
        exit;
    }

    if ($_POST['action'] === 'submit') {
        $cycleRow = $db->prepare(
            "SELECT cycle_id, status 
             FROM sbm_cycles WHERE school_id=? AND sy_id=?"
        );
        $cycleRow->execute([$schoolId, $syId]);
        $cycleRow = $cycleRow->fetch();

        if (!$cycleRow) {
            echo json_encode(['ok' => false, 'msg' => 'No active cycle.']);
            exit;
        }

        // Block if cycle is finalized/completed at the backend level
        if (in_array($cycleRow['status'], ['completed', 'finalized', 'validated'])) {
            echo json_encode(['ok' => false, 'msg' => 'Assessment is already finalized. Submission is no longer allowed.']);
            exit;
        }

        if (!$cycleRow) {
            echo json_encode([
                'ok' => false,
                'msg' => 'No active cycle.'
            ]);
            exit;
        }

        if (
            in_array(
                $cycleRow['status'],
                ['submitted', 'validated']
            )
        ) {
            echo json_encode([
                'ok' => false,
                'msg' => 'Assessment is locked.'
            ]);
            exit;
        }

        $cycleId = $cycleRow['cycle_id'];
        $total = count(STAKEHOLDER_INDICATOR_CODES);

        $placeholders = implode(
            ',',
            array_fill(0, $total, '?')
        );
        $countStmt = $db->prepare("
            SELECT COUNT(*) FROM stakeholder_responses sr
            JOIN sbm_indicators i 
                ON sr.indicator_id = i.indicator_id
            WHERE sr.cycle_id      = ?
              AND sr.stakeholder_id = ?
              AND i.indicator_code IN ($placeholders)
        ");
        $countStmt->execute(
            array_merge([$cycleId, $uid], STAKEHOLDER_INDICATOR_CODES)
        );
        $answered = (int) $countStmt->fetchColumn();

        if ($answered < $total) {
            echo json_encode([
                'ok' => false,
                'msg' => "Please rate all indicators before 
                          submitting. ($answered/$total done)"
            ]);
            exit;
        }

        $cycleInfo = $db->prepare("SELECT school_id, sy_id FROM sbm_cycles WHERE cycle_id=?");
        $cycleInfo->execute([$cycleId]);
        $cycleInfo = $cycleInfo->fetch();
        $db->prepare("
            INSERT INTO stakeholder_submissions
                (cycle_id,stakeholder_id,school_id,sy_id,
                 status,submitted_at,response_count)
            VALUES (?,?,?,?,'submitted',NOW(),?)
            ON DUPLICATE KEY UPDATE
                status         = 'submitted',
                submitted_at   = NOW(),
                response_count = VALUES(response_count)
        ")->execute([$cycleId, $uid, $cycleInfo['school_id'], $cycleInfo['sy_id'], $answered]);

        echo json_encode([
            'ok' => true,
            'msg' => 'Your assessment has been submitted. Thank you!'
        ]);
        exit;
    }
    exit;
}

// ── LOAD DATA ──────────────────────────────────────────────────
$placeholders = implode(
    ',',
    array_fill(0, count(STAKEHOLDER_INDICATOR_CODES), '?')
);
$indicators = $db->prepare("
    SELECT i.*, d.dimension_no, d.dimension_name, d.color_hex
    FROM sbm_indicators i
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    WHERE i.is_active = 1
      AND i.indicator_code IN ($placeholders)
    ORDER BY d.dimension_no, i.sort_order
");
$indicators->execute(STAKEHOLDER_INDICATOR_CODES);
$indicators = $indicators->fetchAll();

$cycle = $db->prepare(
    "SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?"
);
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

$responses = [];
if ($cycle) {
    $r = $db->prepare("
        SELECT * FROM stakeholder_responses 
        WHERE cycle_id=? AND stakeholder_id=?
    ");
    $r->execute([$cycle['cycle_id'], $uid]);
    foreach ($r->fetchAll() as $row)
        $responses[$row['indicator_id']] = $row;
}

$mySubmission = null;
if ($cycle) {
    $st = $db->prepare("
        SELECT * FROM stakeholder_submissions 
        WHERE cycle_id=? AND stakeholder_id=?
    ");
    $st->execute([$cycle['cycle_id'], $uid]);
    $mySubmission = $st->fetch();
}

$grouped = [];
foreach ($indicators as $ind)
    $grouped[$ind['dimension_no']][] = $ind;

$ratingLabels = [
    1 => 'Not yet Manifested',
    2 => 'Rarely Manifested',
    3 => 'Frequently Manifested',
    4 => 'Always manifested'
];
$isLocked = ($cycle && in_array(
    $cycle['status'],
    ['submitted', 'validated']
))
    || ($mySubmission &&
        $mySubmission['status'] === 'submitted');

$totalDone = count($responses);
$totalInds = count($indicators);
$progress = $totalInds > 0
    ? round(($totalDone / $totalInds) * 100) : 0;

$sy = $db->prepare(
    "SELECT * FROM school_years WHERE sy_id=?"
);
$sy->execute([$syId]);
$sy = $sy->fetch();

$school = $db->prepare(
    "SELECT * FROM schools WHERE school_id=?"
);
$school->execute([$schoolId]);
$school = $school->fetch();

$deadlineInfo = $syId ? getDeadlineInfo($db, (int) $syId) : null;

$pageTitle = 'SBM Self-Assessment';
$activePage = 'self_assessment.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
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
        margin-left: auto;
        font-size: 20px;
        color: var(--n300);
        transition: transform .25s ease;
        flex-shrink: 0;
    }

    .dim-body {
        padding-top: 8px;
        margin-bottom: 20px;
    }

    .dim-body.collapsed {
        display: none;
    }

    .ind-card {
        background: var(--white);
        border: 1px solid var(--n200);
        border-radius: var(--radius);
        padding: 14px 16px;
        margin-bottom: 8px;
        transition: border-color .2s, background .2s;
    }

    .ind-card.rated {
        border-color: #86EFAC;
        background: #F0FDF4;
    }

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
</style>

<div class="page-head">
    <div class="page-head-text">
        <h2>SBM Self-Assessment</h2>
        <p><?= e($school['school_name'] ?? '') ?> &nbsp;·&nbsp;
            SY <?= e($sy['label'] ?? '—') ?></p>
    </div>
    <?php if ($deadlineInfo): ?>
        <div class="page-head-actions">
            <?= renderDeadlineChip($deadlineInfo, 'light') ?>
        </div>
    <?php endif; ?>
</div>

<div class="alert alert-info" style="margin-bottom:16px;">
    <?= svgIcon('info') ?>
    <span>
        You are an <strong>External Stakeholder</strong>.
        You have <strong><?= $totalInds ?> indicators</strong>
        to rate based on your observation of the school's programs
        and services.
    </span>
</div>

<?php if (
    $isLocked && $mySubmission &&
    $mySubmission['status'] === 'submitted'
): ?>
    <div class="alert alert-success" style="margin-bottom:16px;">
        <?= svgIcon('check-circle') ?>
        <span>
            You submitted on
            <strong>
                <?= date(
                    'F d, Y',
                    strtotime($mySubmission['submitted_at'])
                ) ?>
            </strong>.
            Your responses are now read-only. Thank you!
        </span>
    </div>
<?php endif; ?>



<!-- Indicators -->
<?php foreach ($grouped as $dimNo => $inds):
    $dim = $inds[0];
    $dimDone = count(array_filter(
        $inds,
        fn($i) => isset($responses[$i['indicator_id']])
    ));
    ?>
    <div class="dim-wrap" style="margin-bottom:6px;">
        <div class="dim-header" onclick="toggleDim(<?= $dimNo ?>)"
            style="border-left:4px solid <?= e($dim['color_hex']) ?>;">
            <div style="width:36px;height:36px;border-radius:9px;
                background:<?= e($dim['color_hex']) ?>22;
                display:flex;align-items:center;
                justify-content:center;font-size:14px;
                font-weight:800;color:<?= e($dim['color_hex']) ?>;
                flex-shrink:0;">
                <?= $dimNo ?>
            </div>
            <div style="flex:1;">
                <div style="font-size:14px;font-weight:700;color:var(--n900);">
                    Dimension <?= $dimNo ?>: <?= e($dim['dimension_name']) ?>
                </div>
                <div style="font-size:12px;color:var(--n400);margin-top:2px;">
                    <?= $dimDone ?>/<?= count($inds) ?> indicators rated
                </div>
            </div>
            <span class="dim-chevron" id="dimChevron<?= $dimNo ?>">▾</span>
        </div>

        <div class="dim-body" id="dimBody<?= $dimNo ?>">
            <?php foreach ($inds as $ind):
                $resp = $responses[$ind['indicator_id']] ?? null;
                $rated = $resp !== null;
                ?>
                <div class="ind-card <?= $rated ? 'rated' : '' ?>" id="row<?= $ind['indicator_id'] ?>">

                    <div class="flex-cb" style="margin-bottom:4px;">
                        <span style="font-family:monospace;font-size:11px;
                     font-weight:700;color:var(--n500);
                     text-transform:uppercase;">
                            <?= e($ind['indicator_code']) ?>
                        </span>
                        <span id="savedBadge<?= $ind['indicator_id'] ?>" style="font-size:11px;color:var(--g600);
                     font-weight:600;">
                            <?= $rated ? 'Saved' : '' ?>
                        </span>
                    </div>

                    <div style="font-size:13.5px;font-weight:600;
                  color:var(--n900);margin:5px 0 4px;
                  line-height:1.5;">
                        <?= e($ind['indicator_text']) ?>
                    </div>

                    <div style="font-size:12px;color:var(--n400);
                  margin-bottom:10px;">
                        📎 MOV: <?= e($ind['mov_guide']) ?>
                    </div>

                    <div class="rating-group" id="ratingGroup<?= $ind['indicator_id'] ?>">
                        <?php foreach ([1, 2, 3, 4] as $r): ?>
                            <button <?= $isLocked ? 'disabled' : '' ?> type="button" class="rating-btn 
                    <?= ($resp && $resp['rating'] == $r)
                        ? 'selected-' . $r : '' ?>" data-ind="<?= $ind['indicator_id'] ?>" data-rating="<?= $r ?>"
                                onclick="selectRating(
                    <?= $ind['indicator_id'] ?>,<?= $r ?>
                )">
                                <?= $r ?> — <?= $ratingLabels[$r] ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <textarea class="fc" id="evidence<?= $ind['indicator_id'] ?>" rows="2"
                        placeholder="Add remarks or observations…" <?= $isLocked ? 'disabled' : '' ?> onblur="saveResponse(
                    <?= $ind['indicator_id'] ?>
                )"><?= e($resp['remarks'] ?? '') ?></textarea>
                    <div id="attachWidget_<?= $ind['indicator_id'] ?>"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Submit -->
<div style="text-align:center;padding:24px 0 32px;">
    <?php if (!$isLocked): ?>
        <button class="btn btn-primary" style="padding:12px 36px;font-size:15px;" id="submitBtn"
            onclick="submitMyAssessment()">
            <?= svgIcon('check') ?> Submit Assessment
            <span style="font-size:12px;opacity:.8;margin-left:4px;">
                (<?= $totalDone ?>/<?= $totalInds ?> rated)
            </span>
        </button>
    <?php endif; ?>
</div>

<script>
    let currentRatings = <?= json_encode(
        array_map(fn($r) => $r['rating'], $responses)
    ) ?>;
    let doneSoFar = <?= $totalDone ?>;
    const totalInds = <?= $totalInds ?>;

    function selectRating(indId, rating) {
        currentRatings[indId] = rating;
        document.querySelectorAll(
            `#ratingGroup${indId} .rating-btn`
        ).forEach(btn => {
            const r = parseInt(btn.dataset.rating);
            btn.className = 'rating-btn' +
                (r === rating ? ` selected-${r}` : '');
        });
        saveResponse(indId);
    }

    async function saveResponse(indId) {
        const rating = currentRatings[indId];
        if (!rating) return;

        const row = document.getElementById(`row${indId}`);
        const wasRated = row?.classList.contains('rated') ?? false;
        const evidence =
            document.getElementById(`evidence${indId}`)?.value || '';

        const r = await apiPost('self_assessment.php', {
            action: 'save_response',
            indicator_id: indId,
            rating,
            evidence
        });

        if (r.ok) {
            if (row) row.classList.add('rated');
            const badge =
                document.getElementById(`savedBadge${indId}`);
            if (badge) {
                badge.textContent = 'Saved';
                badge.style.color = 'var(--g600)';
            }
            if (!wasRated) {
                doneSoFar++;
                updateProgressBar();
            }
        } else {
            toast(r.msg, 'err');
        }
    }



    async function submitMyAssessment() {
        if (!confirm(
            'Submit your assessment?\n\n' +
            'Once submitted you cannot edit your responses.'
        )) return;

        const btn = document.getElementById('submitBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Submitting…'; }

        const r = await apiPost('self_assessment.php',
            { action: 'submit' });
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) setTimeout(() => location.reload(), 1000);
        else if (btn) {
            btn.disabled = false;
            btn.textContent = 'Submit Assessment';
        }
    }

    // ── Load stakeholder attachments ─────────────────────────────
    (async function loadStakeholderAttachments() {
        if (!<?= $cycle ? $cycle['cycle_id'] : 0 ?>) return;
        const cycleId = <?= $cycle ? $cycle['cycle_id'] : 0 ?>;
        const isLocked = <?= $isLocked ? 'true' : 'false' ?>;
        const indIds = <?= json_encode(array_column($indicators, 'indicator_id')) ?>;
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const fd = new FormData();
            fd.append('action', 'get_attachments');
            fd.append('csrf_token', csrf);
            fd.append('cycle_id', cycleId);
            fd.append('uploader_only', '1');
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

    function toggleDim(n) {
        const body = document.getElementById('dimBody' + n);
        const chevron = document.getElementById('dimChevron' + n);
        const isOpen = !body.classList.contains('collapsed');
        body.classList.toggle('collapsed', isOpen);
        chevron.style.transform =
            isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
    }
</script>
<?= deadlineChipCss() ?>
<?= deadlineChipJs() ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>