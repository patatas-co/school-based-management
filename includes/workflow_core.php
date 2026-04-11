<?php
// ============================================================
// includes/workflow_core.php
// Orchestrator for the SBM Workflow & Timeline module.
// Included by admin/workflow.php and sdo/workflow.php.
// ============================================================

$workflowPostUrl = basename($_SERVER['PHP_SELF']);
$db = getDB();

// Pull in helpers and POST handler (no output, no side effects)
require_once __DIR__ . '/workflow_actions.php';
require_once __DIR__ . '/workflow_data.php';

// Handle any POST request and exit before any output
handleWorkflowPost($db);

// Load all view data into local variables
$_wd = loadWorkflowData($db);
$syId = $_wd['syId'];
$syRow = $_wd['syRow'];
$syears = $_wd['syears'];
$dbPhases = $_wd['dbPhases'];
$activePhaseNo = $_wd['activePhaseNo'];
$dbPeriods = $_wd['dbPeriods'];
$currentPeriodNo = $_wd['currentPeriodNo'];
$schools = $_wd['schools'];
$selId = $_wd['selId'];
$selSchool = $_wd['selSchool'];
$selCps = $_wd['selCps'];
$selCycle = $_wd['selCycle'];
$auditLog = $_wd['auditLog'];
$submissionProgress = $_wd['submissionProgress'];
$stageCounts = $_wd['stageCounts'];
unset($_wd);

// Compute summary stats for the stat cards
$totalSch = count($schools);
$notStarted = count(array_filter($schools, fn($s) => !$s['cycle_status'] || in_array($s['cycle_status'], ['draft', 'not_started'])));
$inProgress = count(array_filter($schools, fn($s) => in_array($s['cycle_status'] ?? '', ['setup', 'assigning', 'in_progress', 'consolidating'])));
$submitted = count(array_filter($schools, fn($s) => ($s['cycle_status'] ?? '') === 'submitted'));
$validated = count(array_filter($schools, fn($s) => in_array($s['cycle_status'] ?? '', ['validated', 'finalized'])));
$returned = count(array_filter($schools, fn($s) => ($s['cycle_status'] ?? '') === 'returned'));
$overdueSch = count(array_filter($schools, fn($s) => (int) ($s['cp_overdue'] ?? 0) > 0));

// Stage label map for display
$STAGE_LABEL = [
    'draft' => ['Not Started', '#6B7280', '#F3F4F6'],
    'setup' => ['Setting Up', '#7C3AED', '#EDE9FE'],
    'assigning' => ['Assigning', '#2563EB', '#DBEAFE'],
    'in_progress' => ['In Progress', '#D97706', '#FEF3C7'],
    'consolidating' => ['Consolidating', '#0D9488', '#CCFBF1'],
    'submitted' => ['Submitted', '#D97706', '#FEF3C7'],
    'returned' => ['Returned', '#DC2626', '#FEE2E2'],
    'validated' => ['Validated', '#16A34A', '#DCFCE7'],
    'finalized' => ['Finalized', '#166534', '#F0FDF4'],
];

// Phase display metadata (used only in the view)
$PHASES = [
    1 => [
        'label' => 'Self-Assessment',
        'desc' => 'School Head & stakeholders complete the 42-indicator SBM checklist (4th Grading Period).',
        'icon' => 'clipboard',
        'color' => '#2563EB',
        'bg' => '#EFF6FF',
        'border' => '#BFDBFE',
    ],
    2 => [
        'label' => 'Planning Integration',
        'desc' => 'SBM results integrated into the School Improvement Plan during summer vacation.',
        'icon' => 'trending-up',
        'color' => '#7C3AED',
        'bg' => '#F5F3FF',
        'border' => '#DDD6FE',
    ],
    3 => [
        'label' => 'Implementation & Monitoring',
        'desc' => 'Interventions implemented (1st–3rd Grading). SDO conducts quarterly TA visits.',
        'icon' => 'bar-chart-2',
        'color' => '#059669',
        'bg' => '#ECFDF5',
        'border' => '#A7F3D0',
    ],
];

$CP_META = [
    'self_assessment' => ['label' => 'Self-Assessment Submitted', 'phase' => 1, 'icon' => 'check-square'],
    'planning' => ['label' => 'SIP Integration Confirmed', 'phase' => 2, 'icon' => 'file-text'],
    'q1_monitoring' => ['label' => 'Q1 Monitoring Visit Done', 'phase' => 3, 'icon' => 'eye'],
    'q2_monitoring' => ['label' => 'Q2 Monitoring Visit Done', 'phase' => 3, 'icon' => 'eye'],
    'q3_monitoring' => ['label' => 'Q3 Monitoring Visit Done', 'phase' => 3, 'icon' => 'eye'],
    'completion' => ['label' => 'Cycle Completed', 'phase' => 3, 'icon' => 'award'],
];

$pageTitle = 'Workflow & Timeline';
$activePage = 'workflow.php';
include __DIR__ . '/../includes/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════ -->
<style>
    /* ── Phase timeline rail ───────────────────────────────── */
    .wf-rail {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto 1fr;
        align-items: start;
        gap: 0;
        margin-bottom: 28px;
    }

    .wf-rail-connector {
        display: flex;
        align-items: center;
        padding-top: 28px;
    }

    .wf-rail-connector-line {
        flex: 1;
        height: 3px;
        background: var(--n200);
        position: relative;
        overflow: hidden;
    }

    .wf-rail-connector-line.active-line::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, var(--g400), var(--g600));
        animation: lineFill .6s ease forwards;
    }

    @keyframes lineFill {
        from {
            transform: scaleX(0);
            transform-origin: left;
        }

        to {
            transform: scaleX(1);
        }
    }

    .phase-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 0 8px;
    }

    .phase-badge {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        border: 3px solid var(--n200);
        background: var(--white);
        margin-bottom: 10px;
        transition: all .25s;
        cursor: default;
    }

    .phase-badge.is-done {
        color: #fff !important;
        border-color: currentColor;
    }

    .phase-badge.is-active {
        border-color: currentColor;
        box-shadow: 0 0 0 6px color-mix(in srgb, currentColor 15%, transparent);
    }

    .phase-node-label {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--n900);
        margin-bottom: 3px;
    }

    .phase-node-dates {
        font-size: 11.5px;
        color: var(--n400);
    }

    .phase-node-pill {
        display: inline-block;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 999px;
        margin-top: 6px;
    }

    .phase-node-desc {
        font-size: 11.5px;
        color: var(--n500);
        line-height: 1.45;
        max-width: 190px;
        margin-top: 7px;
    }

    /* ── Grading period strip ──────────────────────────────── */
    .gp-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    .gp-cell {
        background: var(--white);
        border: 1.5px solid var(--n200);
        border-radius: 9px;
        padding: 12px 12px 10px;
        text-align: center;
        cursor: pointer;
        transition: all .15s;
        position: relative;
    }

    .gp-cell:hover {
        border-color: var(--g400);
        background: var(--g50);
    }

    .gp-cell.active {
        border-color: var(--g500);
        background: var(--g50);
        box-shadow: 0 0 0 3px rgba(5, 150, 105, .1);
    }

    .gp-q {
        font-size: 10px;
        font-weight: 700;
        color: var(--n400);
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .gp-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--n900);
        margin-top: 2px;
    }

    .gp-dates {
        font-size: 11.5px;
        color: var(--n400);
        margin-top: 3px;
    }

    .gp-cur-badge {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--g600);
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        padding: 1px 8px;
        border-radius: 999px;
        white-space: nowrap;
    }

    /* ── School progress dots ──────────────────────────────── */
    .wf-dots {
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .wf-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .wf-dot.done {
        background: var(--g500);
    }

    .wf-dot.pending {
        background: var(--n200);
    }

    .wf-dot.overdue {
        background: #EF4444;
    }

    /* ── Checkpoint timeline (school detail) ───────────────── */
    .cp-col {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .cp-row {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 11px 0;
        position: relative;
    }

    .cp-row:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 14px;
        top: 33px;
        bottom: -11px;
        width: 2px;
        background: var(--n150);
    }

    .cp-dot {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        border: 2px solid var(--n200);
        background: var(--white);
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .cp-dot.done {
        background: var(--g600);
        border-color: var(--g600);
        color: #fff;
    }

    .cp-dot.overdue {
        background: #EF4444;
        border-color: #EF4444;
        color: #fff;
    }

    .cp-dot.pending {
        color: var(--n400);
    }

    .cp-label {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--n900);
        margin-bottom: 2px;
    }

    .cp-meta {
        font-size: 12px;
        color: var(--n400);
    }

    .cp-notes {
        font-size: 12px;
        color: var(--n600);
        margin-top: 3px;
        font-style: italic;
    }

    /* ── Phase section header ──────────────────────────────── */
    .phase-section-hd {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 13px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    /* ── Phase card (description blocks) ──────────────────── */
    .phase-desc-card {
        border-radius: 9px;
        padding: 14px 16px;
        font-size: 12.5px;
        line-height: 1.55;
        color: var(--n700);
    }
</style>

<!-- ═══════════════════════════════════════════════════════════
     PAGE HEADER
═══════════════════════════════════════════════════════════ -->
<div class="page-head">
    <div class="page-head-text">
        <h2>Workflow &amp; Timeline</h2>
        <p>SBM 3-Step Cycle — SY <?= htmlspecialchars($syRow['label'] ?? '—') ?></p>
    </div>
    <div class="page-head-actions">
        <div class="p-select" id="wfCoreSySelect" style="width:160px;">
          <input type="hidden" name="sy_id" value="<?= $syId ?>">
          <div class="p-select-trigger" onclick="togglePSelect(event, 'wfCoreSySelect')">
            <span class="p-select-val">
              SY <?= htmlspecialchars(array_column($syears, 'label', 'sy_id')[$syId] ?? 'Select SY') ?>
            </span>
          </div>
          <div class="p-select-menu">
            <?php foreach ($syears as $sy): ?>
              <div class="p-select-item <?= $sy['sy_id'] == $syId ? 'selected' : '' ?>"
                   onclick="location.href='workflow.php?sy=<?= $sy['sy_id'] ?>'">
                SY <?= htmlspecialchars($sy['label']) ?>
                <?php if ($sy['sy_id'] == $syId): ?>
                  <span class="p-select-check"></span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="btn btn-secondary" onclick="openModal('mConfigure')"><?= svgIcon('settings') ?>
            Configure</button>
        <?php if (!$dbPhases): ?>
            <button class="btn btn-primary" onclick="doInitWorkflow()"><?= svgIcon('plus') ?> Initialize Workflow</button>
        <?php else: ?>
            <button class="btn btn-success" onclick="doInitWorkflow()"><?= svgIcon('refresh-cw') ?> Sync
                Checkpoints</button>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     STAT CARDS
═══════════════════════════════════════════════════════════ -->
<div class="stats" style="margin-bottom:20px;">
    <div class="stat">
        <div class="stat-ic blue"><?= svgIcon('home') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $totalSch ?></div>
            <div class="stat-lbl">Total Schools</div>
        </div>
    </div>
    <div class="stat">
        <div class="stat-ic dark"><?= svgIcon('minus-circle') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $notStarted ?></div>
            <div class="stat-lbl">Not Started</div>
        </div>
    </div>
    <div class="stat">
        <div class="stat-ic amber"><?= svgIcon('trending-up') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $inProgress ?></div>
            <div class="stat-lbl">In Progress</div>
        </div>
    </div>
    <div class="stat">
        <div class="stat-ic blue"><?= svgIcon('send') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $submitted ?></div>
            <div class="stat-lbl">Submitted</div>
        </div>
    </div>
    <div class="stat">
        <div class="stat-ic green"><?= svgIcon('check-circle') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $validated ?></div>
            <div class="stat-lbl">Validated</div>
        </div>
    </div>
    <div class="stat">
        <div class="stat-ic red"><?= svgIcon('alert-triangle') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $returned ?></div>
            <div class="stat-lbl">Returned</div>
        </div>
    </div>
    <div class="stat">
        <div class="stat-ic teal"><?= svgIcon('percent') ?></div>
        <div class="stat-data">
            <div class="stat-val"><?= $totalSch ? round(($validated / $totalSch) * 100) : 0 ?>%</div>
            <div class="stat-lbl">Validated Rate</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     PHASE TIMELINE RAIL
═══════════════════════════════════════════════════════════ -->
<?php if ($dbPhases): ?>
    <div class="card" style="margin-bottom:18px;">
        <div class="card-head">
            <span class="card-title">SBM 3-Step Cycle Timeline</span>
            <div class="flex-c" style="gap:6px;">
                <?php foreach ($dbPhases as $ph): ?>
                    <?php if (!$ph['is_active']): ?>
                        <button class="btn btn-secondary btn-sm" onclick="doActivatePhase(<?= $ph['phase_no'] ?>)">
                            Activate Phase <?= $ph['phase_no'] ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-body">

            <!-- Connector rail -->
            <div class="wf-rail">
                <?php foreach ($dbPhases as $i => $ph):
                    $pc = $PHASES[$ph['phase_no']] ?? $PHASES[1];
                    $isDone = $activePhaseNo > $ph['phase_no'];
                    $isAct = (bool) $ph['is_active'];
                    ?>
                    <?php if ($i > 0): ?>
                        <div class="wf-rail-connector">
                            <div class="wf-rail-connector-line <?= ($isDone || $isAct) ? 'active-line' : '' ?>"></div>
                        </div>
                    <?php endif; ?>
                    <div class="phase-node">
                        <div class="phase-badge <?= $isDone ? 'is-done' : ($isAct ? 'is-active' : '') ?>"
                            style="color:<?= $pc['color'] ?>;<?= $isDone ? "background:{$pc['color']};" : '' ?>">
                            <?= $isDone ? '✓' : $ph['phase_no'] ?>
                        </div>
                        <div class="phase-node-label"><?= htmlspecialchars($ph['phase_name']) ?></div>
                        <div class="phase-node-dates">
                            <?= date('M d', strtotime($ph['date_start'])) ?> – <?= date('M d, Y', strtotime($ph['date_end'])) ?>
                        </div>
                        <span class="phase-node-pill"
                            style="background:<?= $isDone ? $pc['bg'] : ($isAct ? $pc['bg'] : 'var(--n100)') ?>;color:<?= $isDone ? $pc['color'] : ($isAct ? $pc['color'] : 'var(--n500)') ?>;">
                            <?php if ($isDone): ?>Completed<?php elseif ($isAct): ?>●
                                Active<?php else: ?>Upcoming<?php endif; ?>
                        </span>
                        <div class="phase-node-desc"><?= htmlspecialchars(substr($ph['description'], 0, 100)) ?>…</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Grading period strip -->
            <?php if ($dbPeriods): ?>
                <div style="margin-bottom:6px;">
                    <div
                        style="font-size:11px;font-weight:700;color:var(--n400);text-transform:uppercase;letter-spacing:.07em;margin-bottom:9px;">
                        Grading Periods — click to set current</div>
                    <div class="gp-strip">
                        <?php foreach ($dbPeriods as $p): ?>
                            <div class="gp-cell <?= $p['is_current'] ? 'active' : '' ?>"
                                onclick="doSetPeriod(<?= $p['period_no'] ?>)">
                                <?php if ($p['is_current']): ?>
                                    <div class="gp-cur-badge">CURRENT</div><?php endif; ?>
                                <div class="gp-q">Q<?= $p['period_no'] ?></div>
                                <div class="gp-name"><?= htmlspecialchars($p['period_name']) ?></div>
                                <div class="gp-dates"><?= date('M d', strtotime($p['date_start'])) ?> –
                                    <?= date('M d', strtotime($p['date_end'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Phase description cards -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:6px;">
                <?php foreach ($dbPhases as $ph):
                    $pc = $PHASES[$ph['phase_no']];
                    $isAct = (bool) $ph['is_active'];
                    ?>
                    <div class="phase-desc-card" style="background:<?= $pc['bg'] ?>;border:1px solid <?= $pc['border'] ?>;">
                        <div
                            style="font-size:11px;font-weight:700;color:<?= $pc['color'] ?>;text-transform:uppercase;margin-bottom:5px;">
                            Phase <?= $ph['phase_no'] ?>
                        </div>
                        <div style="font-size:13.5px;font-weight:700;color:var(--n900);margin-bottom:5px;">
                            <?= htmlspecialchars($ph['phase_name']) ?>
                        </div>
                        <?= htmlspecialchars($ph['description']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning" style="margin-bottom:18px;">
        <?= svgIcon('alert-circle') ?>
        <div>
            <strong>No workflow configured for SY <?= htmlspecialchars($syRow['label'] ?? '—') ?>.</strong><br>
            Click <strong>Configure</strong> to set dates, then <strong>Initialize Workflow</strong> to generate school
            checkpoints.
        </div>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     SCHOOL PROGRESS TABLE
═══════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <span class="card-title">School Progress Tracker <span class="badge"><?= $totalSch ?></span></span>
        <div class="flex-c" style="gap:8px;">
            <div class="search">
                <?= svgIcon('search') ?>
                <input type="text" placeholder="Search school…" oninput="filterTable(this.value,'wfTable')">
            </div>
        </div>
    </div>
    <div class="tbl-wrap">
        <table id="wfTable">
            <thead>
                <tr>
                    <th>School</th>
                    <th>Type</th>
                    <th>SBM Score</th>
                    <th>Maturity</th>
                    <th>Cycle Stage</th>
                    <th>Checkpoints</th>
                    <th>Overdue</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$schools): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;color:var(--n400);padding:28px;">No schools found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($schools as $s):
                    $cpDone = (int) ($s['cp_done'] ?? 0);
                    $cpTotal = (int) ($s['cp_total'] ?? 0);
                    $cpOver = (int) ($s['cp_overdue'] ?? 0);
                    $cycleStatus = $s['cycle_status'] ?? 'draft';
                    [$ovLabel, $ovColor, $ovBg] = $STAGE_LABEL[$cycleStatus] ?? ['Not Started', '#6B7280', '#F3F4F6'];
                    ?>
                    <tr>
                        <td><strong style="font-size:13px;"><?= htmlspecialchars($s['school_name']) ?></strong></td>
                        <td><span class="pill pill-active"
                                style="font-size:10px;"><?= htmlspecialchars($s['classification'] ?? '—') ?></span></td>
                        <td style="font-weight:700;color:var(--g700);">
                            <?= $s['overall_score'] ? number_format($s['overall_score'], 1) . '%' : '—' ?>
                        </td>
                        <td style="font-size:12.5px;color:var(--n600);"><?= htmlspecialchars($s['maturity_level'] ?? '—') ?>
                        </td>
                        <td>
                            <span
                                style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $ovBg ?>;color:<?= $ovColor ?>;">
                                <?= $ovLabel ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($cpTotal > 0): ?>
                                <div class="flex-c" style="gap:6px;">
                                    <div class="wf-dots">
                                        <?php for ($i = 1; $i <= $cpTotal; $i++):
                                            $cls = $i <= $cpDone ? 'done' : ($cpOver > 0 && $i <= $cpDone + $cpOver ? 'overdue' : 'pending');
                                            ?>
                                            <div class="wf-dot <?= $cls ?>"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <span style="font-size:12px;color:var(--n500);"><?= $cpDone ?>/<?= $cpTotal ?></span>
                                </div>
                            <?php else: ?><span style="font-size:12px;color:var(--n400);">—</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($cpOver > 0): ?>
                                <span
                                    style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#EF4444;">
                                    <?= svgIcon('alert-triangle', '', 'width:13px;height:13px;') ?>         <?= $cpOver ?>
                                </span>
                            <?php else: ?><span style="color:var(--n400);font-size:12px;">—</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['submitted_at']): ?>
                                <span
                                    style="font-size:11.5px;color:var(--n500);"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></span>
                            <?php else: ?>
                                <span style="color:var(--n400);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="workflow.php?sy=<?= $syId ?>&school=<?= $s['school_id'] ?>"
                                class="btn btn-secondary btn-sm">
                                <?= svgIcon('eye') ?> Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SCHOOL CHECKPOINT DETAIL (when ?school= is set)
═══════════════════════════════════════════════════════════ -->
<?php if ($selId && $selSchool && $selCps): ?>
    <div class="card" style="margin-bottom:18px;">
        <div class="card-head" style="background:var(--g50);">
            <span class="card-title"><?= htmlspecialchars($selSchool['school_name']) ?> — Checkpoints</span>
            <a href="workflow.php?sy=<?= $syId ?>" class="btn btn-secondary btn-sm"><?= svgIcon('x') ?> Close</a>
        </div>
        <div class="card-body">
            <?php
            $cpByPhase = [];
            foreach ($selCps as $cp)
                $cpByPhase[$cp['phase_no']][] = $cp;
            ?>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
                <?php foreach ($cpByPhase as $phNo => $cps):
                    $pc = $PHASES[$phNo] ?? $PHASES[1];
                    ?>
                    <div>
                        <div class="phase-section-hd" style="background:<?= $pc['bg'] ?>;color:<?= $pc['color'] ?>;">
                            <?= svgIcon($pc['icon'], '', 'width:15px;height:15px;') ?>
                            Phase <?= $phNo ?>: <?= $pc['label'] ?>
                        </div>
                        <div class="cp-col">
                            <?php foreach ($cps as $cp):
                                $meta = $CP_META[$cp['cp_type']] ?? ['label' => $cp['cp_type'], 'icon' => 'circle'];
                                $isDone = $cp['status'] === 'done';
                                $isOver = $cp['status'] === 'overdue';
                                ?>
                                <div class="cp-row">
                                    <div class="cp-dot <?= $cp['status'] ?>">
                                        <?= $isDone ? '✓' : ($isOver ? '!' : '○') ?>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="cp-label"><?= htmlspecialchars($meta['label']) ?></div>
                                        <div class="cp-meta">
                                            <?php if ($cp['due_date']): ?>Due:
                                                <?= date('M d, Y', strtotime($cp['due_date'])) ?>             <?php endif; ?>
                                            <?php if ($isDone && $cp['completed_at']): ?> · Done:
                                                <?= date('M d, Y', strtotime($cp['completed_at'])) ?>                 <?php if ($cp['done_by_name']): ?>
                                                    by <?= htmlspecialchars($cp['done_by_name']) ?><?php endif; ?><?php endif; ?>
                                        </div>
                                        <?php if ($cp['notes']): ?>
                                            <div class="cp-notes"><?= htmlspecialchars($cp['notes']) ?></div><?php endif; ?>
                                        <?php if (!$isDone): ?>
                                            <button class="btn btn-success btn-sm" style="margin-top:6px;"
                                                onclick="openMarkModal(<?= $cp['cp_id'] ?>,<?= $selId ?>,<?= $syId ?>,'<?= htmlspecialchars(addslashes($meta['label'])) ?>')">
                                                <?= svgIcon('check') ?> Mark Done
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php elseif ($selId && $selSchool && empty($selCps)): ?>
    <div class="alert alert-warning" style="margin-bottom:18px;">
        <?= svgIcon('alert-circle') ?>
        <span>No checkpoints found for <strong><?= htmlspecialchars($selSchool['school_name']) ?></strong>. Run <strong>Sync
                Checkpoints</strong> first.</span>
    </div>
<?php endif; ?>

<?php if ($selId && $selCycle): ?>
    <!-- ── Submission Progress Panel ── -->
    <div class="card" style="margin-bottom:18px;">
        <div class="card-head">
            <span class="card-title">Evaluator Submission Progress</span>
            <?php
            $cycStatus = $selCycle['cycle_status'] ?? $selCycle['status'] ?? 'draft';
            [$stLabel, $stColor, $stBg] = $STAGE_LABEL[$cycStatus] ?? ['—', '#6B7280', '#F3F4F6'];
            ?>
            <span
                style="padding:3px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:<?= $stBg ?>;color:<?= $stColor ?>;">
                <?= $stLabel ?>
            </span>
        </div>
        <div class="card-body">
            <?php
            $allEvaluators = array_merge(
                $submissionProgress['teachers'],
                $submissionProgress['stakeholders']
            );
            $totalEval = count($allEvaluators);
            $submittedEval = count(array_filter($allEvaluators, fn($e) => ($e['submission_status'] ?? '') === 'submitted'));
            $pctEval = $totalEval > 0 ? round(($submittedEval / $totalEval) * 100) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="flex:1;">
                    <div class="prog">
                        <div class="prog-fill" style="width:<?= $pctEval ?>%;background:var(--brand-600);"></div>
                    </div>
                </div>
                <span style="font-size:13px;font-weight:700;color:var(--n700);white-space:nowrap;">
                    <?= $submittedEval ?>/<?= $totalEval ?> submitted (<?= $pctEval ?>%)
                </span>
                <?php if ($pctEval < 80): ?>
                    <span style="font-size:11.5px;color:var(--red);font-weight:600;">⚠ Below 80% threshold</span>
                <?php endif; ?>
            </div>
            <?php if (empty($allEvaluators)): ?>
                <p style="color:var(--n400);font-size:13px;text-align:center;padding:20px 0;">No evaluators assigned yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Evaluator</th>
                            <th>Role</th>
                            <th>Assigned Indicators</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allEvaluators as $ev):
                            $evStatus = $ev['submission_status'] ?? 'not_started';
                            $evColors = [
                                'submitted' => ['#DCFCE7', '#16A34A'],
                                'draft' => ['#DBEAFE', '#2563EB'],
                                'not_started' => ['#F3F4F6', '#6B7280'],
                            ];
                            [$evBg, $evC] = $evColors[$evStatus] ?? ['#F3F4F6', '#6B7280'];
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ev['full_name']) ?></strong></td>
                                <td><span
                                        class="pill pill-<?= htmlspecialchars($ev['role'] ?? 'teacher') ?>"><?= ucfirst(str_replace('_', ' ', $ev['role'] ?? 'teacher')) ?></span>
                                </td>
                                <td style="text-align:center;"><?= $ev['assigned_count'] ?? '—' ?></td>
                                <td><span
                                        style="padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $evBg ?>;color:<?= $evC ?>;"><?= ucfirst(str_replace('_', ' ', $evStatus)) ?></span>
                                </td>
                                <td style="font-size:12px;color:var(--n500);">
                                    <?= $ev['submitted_at'] ? date('M d, Y g:i A', strtotime($ev['submitted_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Audit Trail Panel ── -->
    <?php if (!empty($auditLog)): ?>
        <div class="card" style="margin-bottom:18px;">
            <div class="card-head">
                <span class="card-title">Stage History — Audit Trail</span>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:0;">
                    <?php foreach (array_reverse($auditLog) as $log):
                        [$fromL] = $STAGE_LABEL[$log['stage_from'] ?? 'draft'] ?? ['—'];
                        [$toL, $toC, $toBg] = $STAGE_LABEL[$log['stage_to']] ?? ['—', '#6B7280', '#F3F4F6'];
                        ?>
                        <div
                            style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--n100);">
                            <div
                                style="width:10px;height:10px;border-radius:50%;background:<?= $toC ?>;margin-top:4px;flex-shrink:0;">
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:600;color:var(--n900);">
                                    <?= htmlspecialchars($fromL) ?> →
                                    <span style="color:<?= $toC ?>;"><?= htmlspecialchars($toL) ?></span>
                                </div>
                                <?php if ($log['notes']): ?>
                                    <div style="font-size:12px;color:var(--n500);margin-top:2px;font-style:italic;">
                                        <?= htmlspecialchars($log['notes']) ?></div>
                                <?php endif; ?>
                                <div style="font-size:11.5px;color:var(--n400);margin-top:3px;">
                                    <?= htmlspecialchars($log['actor_name'] ?? 'System') ?> ·
                                    <?= date('M d, Y g:i A', strtotime($log['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     CONFIGURE MODAL — redesigned
═══════════════════════════════════════════════════════════ -->
<style>
    /* ── Configure modal tabs ───────────────────────────────── */
    .cfg-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid var(--n200);
        margin-bottom: 20px;
    }

    .cfg-tab {
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 600;
        color: var(--n500);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all .15s;
        display: flex;
        align-items: center;
        gap: 7px;
        user-select: none;
    }

    .cfg-tab:hover {
        color: var(--n800);
    }

    .cfg-tab.active {
        color: var(--g700);
        border-bottom-color: var(--g600);
    }

    .cfg-panel {
        display: none;
    }

    .cfg-panel.active {
        display: block;
    }

    /* ── Grading period rows ────────────────────────────────── */
    .gp-row {
        display: grid;
        grid-template-columns: 36px 1fr 1fr 1fr 100px;
        gap: 10px;
        align-items: center;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1.5px solid var(--n200);
        background: var(--white);
        margin-bottom: 8px;
        transition: border-color .15s;
    }

    .gp-row:hover {
        border-color: var(--n300);
    }

    .gp-row.is-current {
        border-color: var(--g400);
        background: var(--g50);
    }

    .gp-quarter {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: var(--n100);
        color: var(--n600);
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .gp-row.is-current .gp-quarter {
        background: var(--g600);
        color: #fff;
    }

    .gp-row-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--n800);
    }

    /* ── Phase config cards ─────────────────────────────────── */
    .ph-cfg-card {
        border-radius: 10px;
        border: 1.5px solid var(--n200);
        overflow: hidden;
        margin-bottom: 10px;
    }

    .ph-cfg-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 15px;
        font-size: 13px;
        font-weight: 700;
    }

    .ph-cfg-num {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
    }

    .ph-cfg-body {
        padding: 13px 15px;
        background: var(--white);
        border-top: 1px solid var(--n100);
    }

    .ph-cfg-dates {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 10px;
        align-items: end;
        margin-bottom: 10px;
    }

    .ph-cfg-field label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--n500);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 5px;
    }

    .ph-cfg-active-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 7px;
        border: 1.5px solid var(--n200);
        cursor: pointer;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--n600);
        background: var(--n50);
        transition: all .15s;
        white-space: nowrap;
        user-select: none;
    }

    .ph-cfg-active-toggle:has(input:checked) {
        border-color: var(--g400);
        background: var(--g50);
        color: var(--g700);
    }
</style>

<div class="overlay" id="mConfigure">
    <div class="modal" style="max-width:660px;">

        <!-- Header — no icon, just title + subtitle -->
        <div class="modal-head" style="padding:18px 22px 16px;">
            <div>
                <div class="modal-title" style="font-size:15px;font-weight:700;color:var(--n900);">
                    Edit Timeline — SY <?= htmlspecialchars($syRow['label'] ?? '') ?>
                </div>
                <div style="font-size:12px;color:var(--n400);margin-top:2px;font-weight:400;">
                    Set grading period dates and SBM cycle phase windows
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('mConfigure')"><?= svgIcon('x') ?></button>
        </div>

        <div class="modal-body" style="padding:0 22px 4px;">

            <!-- Tabs -->
            <div class="cfg-tabs">
                <div class="cfg-tab active" onclick="cfgTab(this,'tabPeriods')">
                    <?= svgIcon('calendar', '', 'width:14px;height:14px;') ?> Grading Periods
                </div>
                <div class="cfg-tab" onclick="cfgTab(this,'tabPhases')">
                    <?= svgIcon('trending-up', '', 'width:14px;height:14px;') ?> Workflow Phases
                </div>
            </div>

            <!-- ── TAB 1: Grading Periods ────────────────── -->
            <div class="cfg-panel active" id="tabPeriods">
                <p style="font-size:12px;color:var(--n500);margin-bottom:14px;">
                    Define the start and end dates for each grading period. Mark one as <strong>Current</strong> to
                    reflect the active quarter.
                </p>
                <?php
                $gpDefaults = [
                    ['no' => 1, 'name' => 'First Grading', 'start' => '', 'end' => '', 'current' => 0],
                    ['no' => 2, 'name' => 'Second Grading', 'start' => '', 'end' => '', 'current' => 0],
                    ['no' => 3, 'name' => 'Third Grading', 'start' => '', 'end' => '', 'current' => 0],
                    ['no' => 4, 'name' => 'Fourth Grading', 'start' => '', 'end' => '', 'current' => 0],
                ];
                foreach ($dbPeriods as $p) {
                    $gpDefaults[$p['period_no'] - 1] = ['no' => $p['period_no'], 'name' => $p['period_name'], 'start' => $p['date_start'], 'end' => $p['date_end'], 'current' => (int) $p['is_current']];
                }
                ?>
                <?php foreach ($gpDefaults as $gp): ?>
                    <div class="gp-row <?= $gp['current'] ? 'is-current' : '' ?>" id="gprow<?= $gp['no'] ?>">
                        <div class="gp-quarter">Q<?= $gp['no'] ?></div>
                        <div class="gp-row-label"><?= htmlspecialchars($gp['name']) ?></div>
                        <div>
                            <div
                                style="font-size:10.5px;font-weight:600;color:var(--n400);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">
                                Start</div>
                            <input class="fc" type="date" id="gps<?= $gp['no'] ?>" value="<?= $gp['start'] ?>"
                                style="font-size:12px;padding:6px 9px;">
                        </div>
                        <div>
                            <div
                                style="font-size:10.5px;font-weight:600;color:var(--n400);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">
                                End</div>
                            <input class="fc" type="date" id="gpe<?= $gp['no'] ?>" value="<?= $gp['end'] ?>"
                                style="font-size:12px;padding:6px 9px;">
                        </div>
                        <label
                            style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12.5px;font-weight:600;color:var(--n600);">
                            <input type="checkbox" id="gpc<?= $gp['no'] ?>" <?= $gp['current'] ? 'checked' : '' ?>
                                onchange="highlightGpRow(<?= $gp['no'] ?>)">
                            Current
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ── TAB 2: Workflow Phases ────────────────── -->
            <div class="cfg-panel" id="tabPhases">
                <p style="font-size:12px;color:var(--n500);margin-bottom:14px;">
                    Configure the 3-step SBM cycle dates. Only one phase can be <strong>Active</strong> at a time — it
                    determines which schools are currently being monitored.
                </p>
                <?php
                $phDefaults = [
                    ['no' => 1, 'name' => 'Self-Assessment', 'desc' => 'School Head and stakeholders accomplish the 42-indicator SBM checklist during the 4th Grading Period.', 'start' => '', 'end' => '', 'active' => 0],
                    ['no' => 2, 'name' => 'Planning Integration', 'desc' => 'School integrates SBM results into the SIP during summer vacation.', 'start' => '', 'end' => '', 'active' => 0],
                    ['no' => 3, 'name' => 'Implementation', 'desc' => 'School implements planned interventions; SDO conducts quarterly monitoring.', 'start' => '', 'end' => '', 'active' => 0],
                ];
                foreach ($dbPhases as $ph) {
                    $phDefaults[$ph['phase_no'] - 1] = ['no' => $ph['phase_no'], 'name' => $ph['phase_name'], 'desc' => $ph['description'], 'start' => $ph['date_start'], 'end' => $ph['date_end'], 'active' => (int) $ph['is_active']];
                }
                ?>
                <?php foreach ($phDefaults as $pd):
                    $pc = $PHASES[$pd['no']] ?? $PHASES[1];
                    ?>
                    <div class="ph-cfg-card">
                        <div class="ph-cfg-head" style="background:<?= $pc['bg'] ?>;color:<?= $pc['color'] ?>;">
                            <div class="ph-cfg-num" style="background:<?= $pc['color'] ?>;color:#fff;">
                                <?= $pd['no'] ?>
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($pd['name']) ?></div>
                            </div>
                        </div>
                        <div class="ph-cfg-body">
                            <div class="ph-cfg-dates">
                                <div class="ph-cfg-field">
                                    <label>Start Date</label>
                                    <input class="fc" type="date" id="phs<?= $pd['no'] ?>" value="<?= $pd['start'] ?>"
                                        style="font-size:12px;padding:6px 10px;">
                                </div>
                                <div class="ph-cfg-field">
                                    <label>End Date</label>
                                    <input class="fc" type="date" id="phe<?= $pd['no'] ?>" value="<?= $pd['end'] ?>"
                                        style="font-size:12px;padding:6px 10px;">
                                </div>
                                <label class="ph-cfg-active-toggle">
                                    <input type="checkbox" id="phact<?= $pd['no'] ?>" <?= $pd['active'] ? 'checked' : '' ?>>
                                    <?= svgIcon('zap', '', 'width:13px;height:13px;') ?> Set Active
                                </label>
                            </div>
                            <div class="ph-cfg-field">
                                <label>Description</label>
                                <textarea class="fc" id="phdesc<?= $pd['no'] ?>" rows="2"
                                    style="font-size:12px;padding:7px 10px;resize:none;"><?= htmlspecialchars($pd['desc']) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="modal-foot" style="padding:14px 22px;">
            <button class="btn btn-secondary" onclick="closeModal('mConfigure')">Cancel</button>
            <button class="btn btn-primary" onclick="doSaveConfig()">
                <?= svgIcon('save', '', 'width:14px;height:14px;') ?> Save Timeline
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MARK CHECKPOINT MODAL
═══════════════════════════════════════════════════════════ -->
<div class="overlay" id="mMark">
    <div class="modal" style="max-width:420px;">
        <div class="modal-head">
            <span class="modal-title" id="mMarkTitle">Mark Checkpoint Done</span>
            <button class="modal-close" onclick="closeModal('mMark')"><?= svgIcon('x') ?></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mk_cp_id">
            <input type="hidden" id="mk_school_id">
            <input type="hidden" id="mk_sy_id">
            <div class="alert alert-success"><?= svgIcon('check-circle') ?><span>This will advance the school's workflow
                    phase and update the progress tracker.</span></div>
            <div class="fg">
                <label>Remarks / Notes <span style="color:var(--n400);font-weight:400;">(optional)</span></label>
                <textarea class="fc" id="mk_notes" rows="3"
                    placeholder="Observations, issues, or follow-up actions…"></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-secondary" onclick="closeModal('mMark')">Cancel</button>
            <button class="btn btn-primary" onclick="doMarkDone()"><?= svgIcon('check') ?> Confirm Done</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     JS
═══════════════════════════════════════════════════════════ -->
<script>
    const WF_SY = <?= $syId ?>;

    // ── Save config ───────────────────────────────────────────────
    async function doSaveConfig() {
        const periods = [1, 2, 3, 4].map(n => ({
            no: n,
            name: ['First Grading', 'Second Grading', 'Third Grading', 'Fourth Grading'][n - 1],
            start: document.getElementById(`gps${n}`)?.value || '',
            end: document.getElementById(`gpe${n}`)?.value || '',
            current: document.getElementById(`gpc${n}`)?.checked ? 1 : 0,
        })).filter(p => p.start && p.end);

        const phases = [1, 2, 3].map(n => ({
            no: n,
            name: ['Self-Assessment', 'Planning Integration', 'Implementation'][n - 1],
            start: document.getElementById(`phs${n}`)?.value || '',
            end: document.getElementById(`phe${n}`)?.value || '',
            active: document.getElementById(`phact${n}`)?.checked ? 1 : 0,
            desc: document.getElementById(`phdesc${n}`)?.value || '',
        })).filter(p => p.start && p.end);

        if (!periods.length) { toast('Enter at least one grading period date range.', 'warning'); return; }
        if (!phases.length) { toast('Enter at least one workflow phase date range.', 'warning'); return; }

        const [r1, r2] = await Promise.all([
            apiPost('<?= $workflowPostUrl ?>', { action: 'save_periods', sy_id: WF_SY, periods_json: JSON.stringify(periods) }),
            apiPost('<?= $workflowPostUrl ?>', { action: 'save_phases', sy_id: WF_SY, phases_json: JSON.stringify(phases) }),
        ]);

        if (r1.ok && r2.ok) {
            toast('Workflow configured!', 'ok');
            closeModal('mConfigure');
            setTimeout(() => location.reload(), 800);
        } else {
            toast((r1.ok ? r2.msg : r1.msg) || 'Error saving config.', 'err');
        }
    }

    // ── Initialize / sync checkpoints for all schools ─────────────
    async function doInitWorkflow() {
        if (!confirm('Generate checkpoints for all schools in this SY?\n(Existing completed checkpoints will not be overwritten.)')) return;
        const r = await apiPost('<?= $workflowPostUrl ?>', { action: 'init_workflow', sy_id: WF_SY });
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) setTimeout(() => location.reload(), 900);
    }

    // ── Activate a phase ──────────────────────────────────────────
    async function doActivatePhase(phNo) {
        if (!confirm(`Set Phase ${phNo} as the active phase?\nOverdue checkpoints will be flagged automatically.`)) return;
        const r = await apiPost('<?= $workflowPostUrl ?>', { action: 'activate_phase', sy_id: WF_SY, phase_no: phNo });
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) setTimeout(() => location.reload(), 700);
    }

    // ── Set current grading period ────────────────────────────────
    async function doSetPeriod(n) {
        const r = await apiPost('<?= $workflowPostUrl ?>', { action: 'set_period', sy_id: WF_SY, period_no: n });
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) setTimeout(() => location.reload(), 600);
    }

    // ── Open mark-done modal ──────────────────────────────────────
    function openMarkModal(cpId, schoolId, syId, label) {
        document.getElementById('mk_cp_id').value = cpId;
        document.getElementById('mk_school_id').value = schoolId;
        document.getElementById('mk_sy_id').value = syId;
        document.getElementById('mk_notes').value = '';
        document.getElementById('mMarkTitle').textContent = 'Mark Done: ' + label;
        openModal('mMark');
    }

    async function doMarkDone() {
        const r = await apiPost('<?= $workflowPostUrl ?>', {
            action: 'mark_checkpoint',
            cp_id: document.getElementById('mk_cp_id').value,
            school_id: document.getElementById('mk_school_id').value,
            sy_id: document.getElementById('mk_sy_id').value,
            notes: document.getElementById('mk_notes').value,
        });
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) { closeModal('mMark'); setTimeout(() => location.reload(), 700); }
    }

    // ── Configure modal tab switching ────────────────────────────
    function cfgTab(el, panelId) {
        document.querySelectorAll('.cfg-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.cfg-panel').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        document.getElementById(panelId)?.classList.add('active');
    }

    // ── Highlight current grading period row ─────────────────────
    function highlightGpRow(n) {
        // Uncheck siblings, highlight only the checked one
        [1, 2, 3, 4].forEach(i => {
            const cb = document.getElementById(`gpc${i}`);
            const row = document.getElementById(`gprow${i}`);
            if (!cb || !row) return;
            if (i !== n) cb.checked = false;
            row.classList.toggle('is-current', i === n && document.getElementById(`gpc${n}`)?.checked);
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>