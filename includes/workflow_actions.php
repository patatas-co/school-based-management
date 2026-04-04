<?php
// includes/workflow_actions.php
// POST handlers and helper functions for SBM workflow.
// Included by workflow_core.php only — not directly.

// ── Stage order definition ────────────────────────────────────
const CYCLE_STAGES = [
    'draft',
    'setup',
    'assigning',
    'in_progress',
    'consolidating',
    'submitted',
    'validated',
    'finalized',
];

// Stages a cycle can be returned TO from validation
const RETURNABLE_TO = ['in_progress', 'assigning'];

// ── Write one row to cycle_audit_log ─────────────────────────
function logCycleStage(PDO $db, int $cycleId, ?string $from, string $to, ?int $actorId, string $notes = ''): void
{
    $db->prepare("
        INSERT INTO cycle_audit_log (cycle_id, stage_from, stage_to, actor_id, notes)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$cycleId, $from, $to, $actorId, $notes]);
}

// ── Write one row to cycle_stage_gates ───────────────────────
function logGateCheck(PDO $db, int $cycleId, string $from, string $to, int $passed, string $blockers, ?int $actorId): void
{
    $db->prepare("
        INSERT INTO cycle_stage_gates (cycle_id, from_stage, to_stage, passed, blocker_details, checked_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$cycleId, $from, $to, $passed, $blockers, $actorId]);
}

// ── Fetch current cycle status ────────────────────────────────
function getCycleStatus(PDO $db, int $cycleId): ?string
{
    $st = $db->prepare("SELECT status FROM sbm_cycles WHERE cycle_id = ? LIMIT 1");
    $st->execute([$cycleId]);
    $row = $st->fetch();
    return $row ? $row['status'] : null;
}

function fnAutoGenerateCheckpoints(PDO $db, int $syId, array $phases, bool $fromDB): void
{
    $schools = $db->query("SELECT school_id FROM schools")->fetchAll(PDO::FETCH_COLUMN);
    $typeMap = [
        1 => [['self_assessment', null]],
        2 => [['planning', null]],
        3 => [['q1_monitoring', 1], ['q2_monitoring', 2], ['q3_monitoring', 3], ['completion', null]],
    ];
    foreach ($phases as $p) {
        $phNo = $fromDB ? (int) $p['phase_no'] : (int) $p['no'];
        $pStart = $fromDB ? $p['date_start'] : $p['start'];
        $pEnd = $fromDB ? $p['date_end'] : $p['end'];
        foreach ($schools as $sid) {
            foreach (($typeMap[$phNo] ?? []) as [$ctype, $qno]) {
                $due = $pEnd;
                if ($phNo === 3 && $qno) {
                    $span = (strtotime($pEnd) - strtotime($pStart)) / 3;
                    $due = date('Y-m-d', strtotime($pStart) + $span * $qno);
                }
                $ex = $db->prepare("SELECT 1 FROM workflow_checkpoints WHERE school_id=? AND sy_id=? AND cp_type=?");
                $ex->execute([$sid, $syId, $ctype]);
                if (!$ex->fetchColumn()) {
                    $db->prepare("INSERT INTO workflow_checkpoints (school_id,sy_id,phase_no,grading_period,cp_type,status,due_date) VALUES (?,?,?,?,?,'pending',?)")
                        ->execute([$sid, $syId, $phNo, $qno, $ctype, $due]);
                }
            }
        }
    }
}

// ── Gate checks — return ['ok'=>bool,'msg'=>string,'blockers'=>array] ──

function gateSetupToAssigning(PDO $db, int $cycleId): array
{
    // Must have at least one workflow phase configured for this cycle's SY
    $st = $db->prepare("SELECT sy_id FROM sbm_cycles WHERE cycle_id = ?");
    $st->execute([$cycleId]);
    $syId = (int) $st->fetchColumn();

    $phases = (int) $db->prepare("SELECT COUNT(*) FROM sbm_workflow_phases WHERE sy_id = ?")
        ->execute([$syId]) ? $db->prepare("SELECT COUNT(*) FROM sbm_workflow_phases WHERE sy_id = ?") : null;
    $phQ = $db->prepare("SELECT COUNT(*) FROM sbm_workflow_phases WHERE sy_id = ?");
    $phQ->execute([$syId]);
    $phCount = (int) $phQ->fetchColumn();

    if ($phCount === 0) {
        return ['ok' => false, 'msg' => 'No workflow phases configured for this school year. Set dates first.', 'blockers' => ['No phases found']];
    }
    return ['ok' => true, 'msg' => '', 'blockers' => []];
}

function gateAssigningToInProgress(PDO $db, int $cycleId): array
{
    // Every indicator must have at least one assigned teacher or stakeholder
    $total = (int) $db->query("SELECT COUNT(*) FROM sbm_indicators")->fetchColumn();

    $assignedQ = $db->prepare("
        SELECT COUNT(DISTINCT indicator_code) 
        FROM teacher_indicator_assignments 
        WHERE cycle_id = ?
    ");
    $assignedQ->execute([$cycleId]);
    $assigned = (int) $assignedQ->fetchColumn();

    $blockers = [];
    if ($assigned < $total) {
        $missing = $total - $assigned;
        $blockers[] = "$missing indicator(s) have no assigned evaluator";
    }

    if (!empty($blockers)) {
        return ['ok' => false, 'msg' => implode('. ', $blockers) . '. Assign all indicators before opening assessment.', 'blockers' => $blockers];
    }
    return ['ok' => true, 'msg' => '', 'blockers' => []];
}

function gateInProgressToConsolidating(PDO $db, int $cycleId): array
{
    // At least 80% of assigned teachers must have submitted
    $assigneesQ = $db->prepare("
        SELECT COUNT(DISTINCT teacher_id) 
        FROM teacher_indicator_assignments 
        WHERE cycle_id = ?
    ");
    $assigneesQ->execute([$cycleId]);
    $assignees = (int) $assigneesQ->fetchColumn();

    if ($assignees === 0) {
        return ['ok' => false, 'msg' => 'No evaluators are assigned to this cycle.', 'blockers' => ['Zero assignees']];
    }

    $submittedQ = $db->prepare("
        SELECT COUNT(DISTINCT teacher_id) 
        FROM teacher_submissions 
        WHERE cycle_id = ? AND status = 'submitted'
    ");
    $submittedQ->execute([$cycleId]);
    $submitted = (int) $submittedQ->fetchColumn();

    // Also count submitted stakeholders
    $stakeQ = $db->prepare("
        SELECT COUNT(DISTINCT stakeholder_id) 
        FROM stakeholder_submissions 
        WHERE cycle_id = ? AND status = 'submitted'
    ");
    $stakeQ->execute([$cycleId]);
    $stakeSubmitted = (int) $stakeQ->fetchColumn();

    $totalSubmitted = $submitted + $stakeSubmitted;
    $pct = round(($totalSubmitted / $assignees) * 100);

    $blockers = [];
    if ($pct < 80) {
        $pending = $assignees - $totalSubmitted;
        $blockers[] = "Only $pct% of evaluators submitted ($pending pending). Minimum 80% required.";
    }

    if (!empty($blockers)) {
        return ['ok' => false, 'msg' => $blockers[0], 'blockers' => $blockers];
    }
    return ['ok' => true, 'msg' => '', 'blockers' => []];
}

function gateConsolidatingToSubmitted(PDO $db, int $cycleId): array
{
    $st = $db->prepare("SELECT consolidation_confirmed, overall_score FROM sbm_cycles WHERE cycle_id = ?");
    $st->execute([$cycleId]);
    $row = $st->fetch();

    $blockers = [];
    if (!$row || !(int) $row['consolidation_confirmed']) {
        $blockers[] = 'Coordinator has not confirmed consolidation yet';
    }
    if (!$row || !(float) $row['overall_score']) {
        $blockers[] = 'Overall score is 0 — assessment appears empty';
    }

    if (!empty($blockers)) {
        return ['ok' => false, 'msg' => implode('. ', $blockers) . '.', 'blockers' => $blockers];
    }
    return ['ok' => true, 'msg' => '', 'blockers' => []];
}

function gateValidation(string $decision, string $remarks): array
{
    if ($decision === 'return' && empty(trim($remarks))) {
        return ['ok' => false, 'msg' => 'Remarks are required when returning an assessment for revision.', 'blockers' => ['Missing return remarks']];
    }
    return ['ok' => true, 'msg' => '', 'blockers' => []];
}

// ── Central stage advance handler ────────────────────────────
function advanceCycleStage(PDO $db, int $cycleId, string $toStage, int $actorId, string $notes = ''): array
{
    $currentStatus = getCycleStatus($db, $cycleId);
    if (!$currentStatus) {
        return ['ok' => false, 'msg' => 'Cycle not found.'];
    }

    // Run the correct gate check for this transition
    $gate = ['ok' => true, 'msg' => '', 'blockers' => []];
    switch ("{$currentStatus}->{$toStage}") {
        case 'setup->assigning':
            $gate = gateSetupToAssigning($db, $cycleId);
            break;
        case 'assigning->in_progress':
            $gate = gateAssigningToInProgress($db, $cycleId);
            break;
        case 'in_progress->consolidating':
            $gate = gateInProgressToConsolidating($db, $cycleId);
            break;
        case 'consolidating->submitted':
            $gate = gateConsolidatingToSubmitted($db, $cycleId);
            break;
    }

    // Log the gate check result
    logGateCheck(
        $db,
        $cycleId,
        $currentStatus,
        $toStage,
        $gate['ok'] ? 1 : 0,
        $gate['ok'] ? '' : implode('; ', $gate['blockers']),
        $actorId
    );

    if (!$gate['ok']) {
        return ['ok' => false, 'msg' => $gate['msg']];
    }

    // Build extra column updates for specific transitions
    $extraSql = '';
    $extraParams = [];

    if ($toStage === 'submitted') {
        $extraSql = ', submitted_at = NOW()';
    } elseif ($toStage === 'validated') {
        $extraSql = ', validated_at = NOW(), validated_by = ?';
        $extraParams[] = $actorId;
    } elseif ($toStage === 'finalized') {
        $extraSql = ', finalized_at = NOW()';
    } elseif ($toStage === 'returned') {
        $extraSql = ', returned_at = NOW(), returned_by = ?, return_remarks = ?';
        $extraParams[] = $actorId;
        $extraParams[] = $notes;
    }

    $params = array_merge([$toStage], $extraParams, [$cycleId]);
    $db->prepare("UPDATE sbm_cycles SET status = ? $extraSql WHERE cycle_id = ?")
        ->execute($params);

    // Write audit log
    logCycleStage($db, $cycleId, $currentStatus, $toStage, $actorId, $notes);

    return ['ok' => true, 'msg' => 'Stage advanced to ' . ucfirst(str_replace('_', ' ', $toStage)) . '.'];
}

function fnUpdateSchoolStatus(PDO $db, int $schoolId, int $syId): void
{
    $cps = $db->prepare("SELECT cp_type,status FROM workflow_checkpoints WHERE school_id=? AND sy_id=?");
    $cps->execute([$schoolId, $syId]);
    $byType = array_column($cps->fetchAll(), 'status', 'cp_type');

    $p1done = ($byType['self_assessment'] ?? '') === 'done';
    $p2done = ($byType['planning'] ?? '') === 'done';
    $p3done = ($byType['completion'] ?? '') === 'done';

    $curPhase = 1;
    if ($p1done)
        $curPhase = 2;
    if ($p2done)
        $curPhase = 3;

    $overall = 'not_started';
    if ($p1done || in_array('done', array_values($byType)))
        $overall = 'in_progress';
    if ($p3done)
        $overall = 'completed';

    $db->prepare("INSERT INTO school_workflow_status (school_id,sy_id,current_phase,overall_status)
                  VALUES (?,?,?,?)
                  ON DUPLICATE KEY UPDATE current_phase=VALUES(current_phase),overall_status=VALUES(overall_status),updated_at=NOW()")
        ->execute([$schoolId, $syId, $curPhase, $overall]);
}

function handleWorkflowPost(PDO $db): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action']))
        return;

    header('Content-Type: application/json');
    if (function_exists('verifyCsrf'))
        verifyCsrf();

    $action = $_POST['action'];
    $syId = (int) ($_POST['sy_id'] ?? 0);

    if ($action === 'save_periods') {
        $periods = json_decode($_POST['periods_json'] ?? '[]', true);
        $db->prepare("UPDATE grading_periods SET is_current=0 WHERE sy_id=?")->execute([$syId]);
        foreach ($periods as $p) {
            $db->prepare("INSERT INTO grading_periods (sy_id,period_no,period_name,date_start,date_end,is_current)
                          VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE
                            period_name=VALUES(period_name),date_start=VALUES(date_start),
                            date_end=VALUES(date_end),is_current=VALUES(is_current)")
                ->execute([$syId, (int) $p['no'], trim($p['name']), $p['start'], $p['end'], (int) ($p['current'] ?? 0)]);
        }
        echo json_encode(['ok' => true, 'msg' => 'Grading periods saved.']);
        exit;
    }

    if ($action === 'save_phases') {
        $phases = json_decode($_POST['phases_json'] ?? '[]', true);
        foreach ($phases as $p) {
            $db->prepare("INSERT INTO sbm_workflow_phases (sy_id,phase_no,phase_name,description,date_start,date_end,is_active)
                          VALUES (?,?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE
                            phase_name=VALUES(phase_name),description=VALUES(description),
                            date_start=VALUES(date_start),date_end=VALUES(date_end),is_active=VALUES(is_active)")
                ->execute([$syId, (int) $p['no'], trim($p['name']), trim($p['desc']), $p['start'], $p['end'], (int) ($p['active'] ?? 0)]);
        }
        fnAutoGenerateCheckpoints($db, $syId, $phases, false);
        echo json_encode(['ok' => true, 'msg' => 'Workflow phases saved and checkpoints generated.']);
        exit;
    }

    if ($action === 'init_workflow') {
        $schools = $db->query("SELECT school_id FROM schools")->fetchAll(PDO::FETCH_COLUMN);
        $inserted = 0;
        foreach ($schools as $sid) {
            $chk = $db->prepare("SELECT 1 FROM school_workflow_status WHERE school_id=? AND sy_id=?");
            $chk->execute([$sid, $syId]);
            if (!$chk->fetchColumn()) {
                $db->prepare("INSERT INTO school_workflow_status (school_id,sy_id,current_phase,overall_status) VALUES (?,?,1,'not_started')")
                    ->execute([$sid, $syId]);
                $inserted++;
            }
        }
        $phQ = $db->prepare("SELECT * FROM sbm_workflow_phases WHERE sy_id=? ORDER BY phase_no");
        $phQ->execute([$syId]);
        $phArr = $phQ->fetchAll();
        fnAutoGenerateCheckpoints($db, $syId, $phArr, true);
        echo json_encode(['ok' => true, 'msg' => "Workflow initialized for {$inserted} new schools."]);
        exit;
    }

    if ($action === 'activate_phase') {
        $phaseNo = (int) $_POST['phase_no'];
        $db->prepare("UPDATE sbm_workflow_phases SET is_active=0 WHERE sy_id=?")->execute([$syId]);
        $db->prepare("UPDATE sbm_workflow_phases SET is_active=1 WHERE sy_id=? AND phase_no=?")->execute([$syId, $phaseNo]);
        $db->prepare("UPDATE workflow_checkpoints SET status='overdue' WHERE sy_id=? AND status='pending' AND due_date < CURDATE()")->execute([$syId]);
        echo json_encode(['ok' => true, 'msg' => "Phase {$phaseNo} is now active."]);
        exit;
    }

    if ($action === 'set_period') {
        $periodNo = (int) $_POST['period_no'];
        $db->prepare("UPDATE grading_periods SET is_current=0 WHERE sy_id=?")->execute([$syId]);
        $db->prepare("UPDATE grading_periods SET is_current=1 WHERE sy_id=? AND period_no=?")->execute([$syId, $periodNo]);
        echo json_encode(['ok' => true, 'msg' => 'Current grading period updated.']);
        exit;
    }

    if ($action === 'mark_checkpoint') {
        $cpId = (int) $_POST['cp_id'];
        $schoolId = (int) $_POST['school_id'];
        $notes = trim($_POST['notes'] ?? '');
        $db->prepare("UPDATE workflow_checkpoints SET status='done',completed_at=NOW(),completed_by=?,notes=? WHERE cp_id=?")
            ->execute([$_SESSION['user_id'], $notes, $cpId]);
        fnUpdateSchoolStatus($db, $schoolId, $syId);
        echo json_encode(['ok' => true, 'msg' => 'Checkpoint marked as done.']);
        exit;
    }

    // ── Advance cycle to next stage (with gate checks) ────────
    if ($action === 'advance_stage') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        $toStage = trim($_POST['to_stage'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$cycleId || !$toStage) {
            echo json_encode(['ok' => false, 'msg' => 'Missing cycle_id or to_stage.']);
            exit;
        }

        $result = advanceCycleStage($db, $cycleId, $toStage, $actorId, $notes);
        echo json_encode($result);
        exit;
    }

    // ── Confirm consolidation (coordinator only) ──────────────
    if ($action === 'confirm_consolidation') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$cycleId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing cycle_id.']);
            exit;
        }

        $db->prepare("
            UPDATE sbm_cycles 
            SET consolidation_confirmed = 1,
                consolidation_confirmed_by = ?,
                consolidation_confirmed_at = NOW()
            WHERE cycle_id = ?
        ")->execute([$actorId, $cycleId]);

        logCycleStage($db, $cycleId, 'consolidating', 'consolidating', $actorId, 'Consolidation confirmed by coordinator.');
        echo json_encode(['ok' => true, 'msg' => 'Consolidation confirmed. You may now submit to SDO.']);
        exit;
    }

    // ── Return cycle for revision (validator only) ────────────
    if ($action === 'return_cycle') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');
        $toStage = trim($_POST['to_stage'] ?? 'in_progress');
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        $gate = gateValidation('return', $remarks);
        if (!$gate['ok']) {
            echo json_encode($gate);
            exit;
        }

        if (!in_array($toStage, RETURNABLE_TO, true)) {
            $toStage = 'in_progress';
        }

        $db->prepare("
            UPDATE sbm_cycles
            SET status = ?,
                returned_at = NOW(),
                returned_by = ?,
                return_remarks = ?
            WHERE cycle_id = ?
        ")->execute([$toStage, $actorId, $remarks, $cycleId]);

        logCycleStage($db, $cycleId, 'submitted', $toStage, $actorId, "Returned: $remarks");
        echo json_encode(['ok' => true, 'msg' => 'Assessment returned for revision.']);
        exit;
    }

    // ── Validate and finalize cycle ───────────────────────────
    if ($action === 'validate_cycle') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$cycleId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing cycle_id.']);
            exit;
        }

        $db->prepare("
            UPDATE sbm_cycles
            SET status = 'validated',
                validated_at = NOW(),
                validated_by = ?,
                validator_remarks = ?
            WHERE cycle_id = ?
        ")->execute([$actorId, $remarks, $cycleId]);

        logCycleStage($db, $cycleId, 'submitted', 'validated', $actorId, $remarks ?: 'Validated by coordinator.');
        echo json_encode(['ok' => true, 'msg' => 'Assessment validated successfully.']);
        exit;
    }

    // ── Finalize cycle (lock it permanently) ──────────────────
    if ($action === 'finalize_cycle') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$cycleId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing cycle_id.']);
            exit;
        }

        // Only allow finalizing a validated cycle
        $status = getCycleStatus($db, $cycleId);
        if ($status !== 'validated') {
            echo json_encode(['ok' => false, 'msg' => 'Only a validated cycle can be finalized.']);
            exit;
        }

        $db->prepare("
            UPDATE sbm_cycles
            SET status = 'finalized',
                finalized_at = NOW()
            WHERE cycle_id = ?
        ")->execute([$cycleId]);

        logCycleStage($db, $cycleId, 'validated', 'finalized', $actorId, 'Cycle locked and archived.');
        echo json_encode(['ok' => true, 'msg' => 'Cycle has been finalized and locked.']);
        exit;
    }

    // ── Get cycle audit trail ─────────────────────────────────
    if ($action === 'get_audit_log') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        if (!$cycleId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing cycle_id.']);
            exit;
        }

        $st = $db->prepare("
            SELECT cal.stage_from, cal.stage_to, cal.notes, cal.created_at,
                   u.full_name AS actor_name, u.role AS actor_role
            FROM cycle_audit_log cal
            LEFT JOIN users u ON cal.actor_id = u.user_id
            WHERE cal.cycle_id = ?
            ORDER BY cal.created_at ASC
        ");
        $st->execute([$cycleId]);
        echo json_encode(['ok' => true, 'log' => $st->fetchAll()]);
        exit;
    }

    // ── Get submission progress for a cycle ───────────────────
    if ($action === 'get_submission_progress') {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        if (!$cycleId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing cycle_id.']);
            exit;
        }

        // Teachers
        $tQ = $db->prepare("
            SELECT u.user_id, u.full_name, u.role,
                   ts.status AS submission_status,
                   ts.submitted_at,
                   COUNT(DISTINCT tia.indicator_code) AS assigned_count
            FROM teacher_indicator_assignments tia
            JOIN users u ON tia.teacher_id = u.user_id
            LEFT JOIN teacher_submissions ts ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
            WHERE tia.cycle_id = ?
            GROUP BY u.user_id, u.full_name, u.role, ts.status, ts.submitted_at
        ");
        $tQ->execute([$cycleId, $cycleId]);
        $teachers = $tQ->fetchAll();

        // Stakeholders
        $sQ = $db->prepare("
            SELECT u.user_id, u.full_name, u.role,
                   ss.status AS submission_status,
                   ss.submitted_at
            FROM stakeholder_submissions ss
            JOIN users u ON ss.stakeholder_id = u.user_id
            WHERE ss.cycle_id = ?
        ");
        $sQ->execute([$cycleId]);
        $stakeholders = $sQ->fetchAll();

        echo json_encode([
            'ok' => true,
            'teachers' => $teachers,
            'stakeholders' => $stakeholders,
        ]);
        exit;
    }

    exit;
}