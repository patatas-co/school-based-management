<?php
ob_start();
// school_head/view_assessment.php — View a single SBM assessment cycle
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();

$cycleId = (int) ($_GET['id'] ?? 0);
if (!$cycleId) {
    header('Location: assessment.php');
    exit;
}

// Load cycle
$cycleStmt = $db->prepare("
    SELECT c.*, s.school_name, s.school_id_deped, s.classification,
           s.school_head_name, s.address, s.total_enrollment, s.total_teachers,
           sy.label sy_label,
           u.full_name validator_name
    FROM sbm_cycles c
    JOIN schools s   ON c.school_id = s.school_id
    JOIN school_years sy ON c.sy_id = sy.sy_id
    LEFT JOIN users u ON c.validated_by = u.user_id
    WHERE c.cycle_id = ? AND c.school_id = ?
");
$cycleStmt->execute([$cycleId, SCHOOL_ID]);
$cycle = $cycleStmt->fetch();

if (!$cycle) {
    header('Location: assessment.php');
    exit;
}

// Load dimension scores
$dimScores = $db->prepare("
    SELECT ds.*, d.dimension_no, d.dimension_name, d.color_hex, d.indicator_count
    FROM sbm_dimension_scores ds
    JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
    WHERE ds.cycle_id = ?
    ORDER BY d.dimension_no
");
$dimScores->execute([$cycleId]);
$dimScores = $dimScores->fetchAll();

// Load all responses
$responses = $db->prepare("
    SELECT r.*, i.indicator_code, i.indicator_text, i.mov_guide, i.sort_order,
           d.dimension_no, d.dimension_name, d.color_hex
    FROM sbm_responses r
    JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    WHERE r.cycle_id = ?
    ORDER BY d.dimension_no, i.sort_order
");
$responses->execute([$cycleId]);
$responses = $responses->fetchAll();

// Group responses by dimension
$grouped = [];
foreach ($responses as $r) {
    $grouped[$r['dimension_no']][] = $r;
}

$ratingLabels = [1 => 'Not Yet Manifested', 2 => 'Emerging', 3 => 'Developing', 4 => 'Always Manifested'];
$ratingColors = [1 => '#DC2626', 2 => '#D97706', 3 => '#2563EB', 4 => '#16A34A'];
$ratingBgs = [1 => '#FEE2E2', 2 => '#FEF3C7', 3 => '#DBEAFE', 4 => '#DCFCE7'];

$pageTitle = 'View Assessment';
$activePage = 'assessment.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .va-header {
        background: linear-gradient(135deg, #0A1F0A 0%, #0F2D0F 60%, #0F3D1F 100%);
        border-radius: var(--radius-lg);
        padding: 24px 28px;
        color: #fff;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .va-header::before {
        content: '';
        position: absolute;
        right: -60px;
        top: -60px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(74, 222, 128, .06);
        pointer-events: none;
    }

    .va-header-title {
        font-family: var(--font-display);
        font-size: 22px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .va-header-sub {
        font-size: 13px;
        color: rgba(255, 255, 255, .5);
    }

    .dim-score-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid var(--n200);
        background: var(--white);
        margin-bottom: 8px;
    }

    .dim-num-badge {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .ind-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .ind-table th {
        background: var(--n50);
        padding: 8px 12px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        color: var(--n500);
        border-bottom: 1px solid var(--n200);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .ind-table td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--n100);
        vertical-align: top;
    }

    .ind-table tr:last-child td {
        border-bottom: none;
    }

    .ind-table tr:hover td {
        background: var(--n50);
    }

    .rating-pill {
        display: inline-flex;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
</style>

<!-- Back button -->
<div style="margin-bottom: 16px;">
    <a href="assessment.php" class="btn btn-ghost btn-sm" style="color: var(--n600);">
        <?= svgIcon('arrow-left') ?> Back to Assessments
    </a>
</div>

<!-- Header card -->
<div class="va-header">
    <div style="position: relative; z-index: 1;">
        <div
            style="font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(74,222,128,.8); margin-bottom: 8px;">
            SBM Self-Assessment Report
        </div>
        <div class="va-header-title"><?= e($cycle['school_name']) ?></div>
        <div class="va-header-sub">School Year <?= e($cycle['sy_label']) ?> &nbsp;·&nbsp;
            <?= e($cycle['classification']) ?></div>

        <div style="display: flex; gap: 20px; margin-top: 16px; flex-wrap: wrap;">
            <div>
                <div
                    style="font-size: 10.5px; color: rgba(255,255,255,.4); margin-bottom: 3px; text-transform: uppercase; letter-spacing: .06em;">
                    Status</div>
                <span
                    class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst(str_replace('_', ' ', $cycle['status'])) ?></span>
            </div>
            <?php if ($cycle['overall_score']):
                $mat = sbmMaturityLevel(floatval($cycle['overall_score'])); ?>
                <div>
                    <div
                        style="font-size: 10.5px; color: rgba(255,255,255,.4); margin-bottom: 3px; text-transform: uppercase; letter-spacing: .06em;">
                        Overall Score</div>
                    <div
                        style="font-family: var(--font-display); font-size: 24px; font-weight: 800; color: #fff; line-height: 1;">
                        <?= $cycle['overall_score'] ?>%</div>
                </div>
                <div>
                    <div
                        style="font-size: 10.5px; color: rgba(255,255,255,.4); margin-bottom: 3px; text-transform: uppercase; letter-spacing: .06em;">
                        Maturity Level</div>
                    <span
                        style="display: inline-flex; padding: 3px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: <?= $mat['bg'] ?>; color: <?= $mat['color'] ?>;"><?= e($cycle['maturity_level']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($cycle['school_head_name']): ?>
                <div>
                    <div
                        style="font-size: 10.5px; color: rgba(255,255,255,.4); margin-bottom: 3px; text-transform: uppercase; letter-spacing: .06em;">
                        School Head</div>
                    <div style="font-size: 13px; font-weight: 600; color: #fff;"><?= e($cycle['school_head_name']) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($cycle['submitted_at']): ?>
                <div>
                    <div
                        style="font-size: 10.5px; color: rgba(255,255,255,.4); margin-bottom: 3px; text-transform: uppercase; letter-spacing: .06em;">
                        Submitted</div>
                    <div style="font-size: 13px; color: rgba(255,255,255,.7);">
                        <?= date('M d, Y', strtotime($cycle['submitted_at'])) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($cycle['validator_name']): ?>
                <div>
                    <div
                        style="font-size: 10.5px; color: rgba(255,255,255,.4); margin-bottom: 3px; text-transform: uppercase; letter-spacing: .06em;">
                        Validated By</div>
                    <div style="font-size: 13px; color: rgba(255,255,255,.7);"><?= e($cycle['validator_name']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions top-right -->
    <div style="position: absolute; top: 20px; right: 24px; display: flex; gap: 8px; z-index: 1;">
        <a href="<?= baseUrl() ?>/export_pdf.php?cycle_id=<?= $cycleId ?>&type=annex_a" target="_blank"
            class="btn btn-sm"
            style="background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2);">
            <?= svgIcon('download') ?> Annex A PDF
        </a>
        <a href="<?= baseUrl() ?>/export_pdf.php?cycle_id=<?= $cycleId ?>&type=dimension" target="_blank"
            class="btn btn-sm"
            style="background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2);">
            <?= svgIcon('download') ?> Dimension PDF
        </a>
        <?php if ($cycle['status'] === 'submitted'): ?>
            <button class="btn btn-sm" style="background: #16A34A; color: #fff; border: none;"
                onclick="validateCycle(<?= $cycleId ?>, 'validate')">
                <?= svgIcon('check') ?> Validate
            </button>
            <button class="btn btn-sm" style="background: rgba(220,38,38,.8); color: #fff; border: none;"
                onclick="validateCycle(<?= $cycleId ?>, 'return')">
                <?= svgIcon('x') ?> Return
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($cycle['validator_remarks']): ?>
    <div class="alert <?= $cycle['status'] === 'validated' ? 'alert-success' : 'alert-warning' ?>"
        style="margin-bottom: 18px;">
        <?= svgIcon($cycle['status'] === 'validated' ? 'check-circle' : 'alert-circle') ?>
        <div>
            <strong><?= $cycle['status'] === 'validated' ? 'Validator Remarks' : 'Returned for Revision' ?>:</strong>
            <?= e($cycle['validator_remarks']) ?>
        </div>
    </div>
<?php endif; ?>

<!-- Dimension Summary -->
<?php if ($dimScores): ?>
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-head">
            <span class="card-title">Dimension Summary</span>
            <span style="font-size: 12px; color: var(--n400);"><?= count($dimScores) ?> dimensions scored</span>
        </div>
        <div class="card-body" style="padding: 16px 20px;">
            <?php foreach ($dimScores as $ds):
                $mat = sbmMaturityLevel(floatval($ds['percentage']));
                ?>
                <div class="dim-score-row">
                    <div class="dim-num-badge" style="background: <?= e($ds['color_hex']) ?>;"><?= $ds['dimension_no'] ?></div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--n800); margin-bottom: 4px;">
                            <?= e($ds['dimension_name']) ?></div>
                        <div style="height: 6px; background: var(--n100); border-radius: 999px; overflow: hidden;">
                            <div
                                style="height: 100%; width: <?= min(100, $ds['percentage']) ?>%; background: <?= e($ds['color_hex']) ?>; border-radius: 999px;">
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right; flex-shrink: 0; min-width: 110px;">
                        <div style="font-size: 15px; font-weight: 800; color: <?= $mat['color'] ?>;">
                            <?= number_format($ds['percentage'], 1) ?>%</div>
                        <div style="font-size: 11px; color: var(--n400);"><?= $ds['raw_score'] ?>/<?= $ds['max_score'] ?> pts
                        </div>
                    </div>
                    <span
                        style="display: inline-flex; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: <?= $mat['bg'] ?>; color: <?= $mat['color'] ?>; flex-shrink: 0;"><?= $mat['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Indicator Responses by Dimension -->
<?php if ($grouped): ?>
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--n900); margin-bottom: 14px;">Indicator Ratings</h3>

        <?php foreach ($grouped as $dimNo => $inds):
            $first = $inds[0];
            $dimRated1 = count(array_filter($inds, fn($i) => $i['rating'] == 1));
            $dimRated2 = count(array_filter($inds, fn($i) => $i['rating'] == 2));
            $dimRated3 = count(array_filter($inds, fn($i) => $i['rating'] == 3));
            $dimRated4 = count(array_filter($inds, fn($i) => $i['rating'] == 4));
            ?>
            <div class="card" style="margin-bottom: 14px;">
                <div class="card-head"
                    style="background: <?= e($first['color_hex']) ?>18; border-bottom: 2px solid <?= e($first['color_hex']) ?>40;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span
                            style="width: 26px; height: 26px; border-radius: 6px; background: <?= e($first['color_hex']) ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; flex-shrink: 0;"><?= $dimNo ?></span>
                        <span class="card-title" style="color: <?= e($first['color_hex']) ?>;">Dimension <?= $dimNo ?>:
                            <?= e($first['dimension_name']) ?></span>
                    </div>
                    <div style="display: flex; gap: 8px; font-size: 11px; flex-wrap: wrap;">
                        <?php if ($dimRated1): ?><span
                                style="background: #FEE2E2; color: #DC2626; padding: 2px 7px; border-radius: 999px; font-weight: 700;">NYM:
                                <?= $dimRated1 ?></span><?php endif; ?>
                        <?php if ($dimRated2): ?><span
                                style="background: #FEF3C7; color: #D97706; padding: 2px 7px; border-radius: 999px; font-weight: 700;">EM:
                                <?= $dimRated2 ?></span><?php endif; ?>
                        <?php if ($dimRated3): ?><span
                                style="background: #DBEAFE; color: #2563EB; padding: 2px 7px; border-radius: 999px; font-weight: 700;">DEV:
                                <?= $dimRated3 ?></span><?php endif; ?>
                        <?php if ($dimRated4): ?><span
                                style="background: #DCFCE7; color: #16A34A; padding: 2px 7px; border-radius: 999px; font-weight: 700;">AM:
                                <?= $dimRated4 ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="tbl-wrap">
                    <table class="ind-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Code</th>
                                <th>Indicator</th>
                                <th style="width: 160px;">MOV Guide</th>
                                <th style="width: 170px;">Rating</th>
                                <th>Evidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inds as $ind): ?>
                                <tr>
                                    <td><span
                                            style="font-family: monospace; font-size: 11.5px; font-weight: 700; color: var(--n500);"><?= e($ind['indicator_code']) ?></span>
                                    </td>
                                    <td style="font-size: 12.5px; line-height: 1.55; color: var(--n800);">
                                        <?= e($ind['indicator_text']) ?></td>
                                    <td style="font-size: 11.5px; color: var(--n400); font-style: italic; line-height: 1.4;">
                                        <?= e($ind['mov_guide'] ?? '—') ?></td>
                                    <td>
                                        <span class="rating-pill"
                                            style="background: <?= $ratingBgs[$ind['rating']] ?>; color: <?= $ratingColors[$ind['rating']] ?>;">
                                            <?= $ind['rating'] ?> — <?= $ratingLabels[$ind['rating']] ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12px; color: var(--n600);"><?= e($ind['evidence_text'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <?= svgIcon('info') ?> No indicator responses recorded for this assessment cycle.
    </div>
<?php endif; ?>

<!-- Validate/Return Modal -->
<div class="overlay" id="mValidate">
    <div class="modal" style="max-width:540px;max-height:none;overflow-y:visible;">
        <div class="modal-head">
            <span class="modal-title" id="mVTitle">Validate Assessment</span>
            <button class="modal-close" onclick="closeModal('mValidate')"><?= svgIcon('x') ?></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="v_cycle_id">
            <input type="hidden" id="v_action">
            <div class="fg">
                <label>Remarks <span style="font-weight: 400; color: var(--n400);">(optional for validate, required for
                        return)</span></label>
                <textarea class="fc" id="v_remarks" rows="4" placeholder="Enter your remarks or feedback…"></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-secondary" onclick="closeModal('mValidate')">Cancel</button>
            <button class="btn btn-primary" id="v_submit_btn" onclick="submitValidation()">Confirm</button>
        </div>
    </div>
</div>

<script>
    function validateCycle(cycleId, action) {
        $v('v_cycle_id', cycleId);
        $v('v_action', action);
        $v('v_remarks', '');
        const isVal = action === 'validate';
        const title = $el('mVTitle');
        const btn = $el('v_submit_btn');
        if (title) title.textContent = isVal ? 'Validate Assessment' : 'Return for Revision';
        if (btn) {
            btn.textContent = isVal ? 'Validate' : 'Return';
            btn.className = 'btn btn-' + (isVal ? 'success' : 'danger');
        }
        openModal('mValidate');
    }

    async function submitValidation() {
        const action = $('v_action');
        const remarks = $('v_remarks');
        if (action === 'return' && !remarks) {
            toast('Please provide remarks for returning.', 'warning');
            return;
        }
        const r = await apiPost('assessment.php', {
            action,
            cycle_id: $('v_cycle_id'),
            remarks
        });
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) {
            closeModal('mValidate');
            setTimeout(() => location.reload(), 900);
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>