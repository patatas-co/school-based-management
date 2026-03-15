<?php
// includes/workflow_actions.php
// POST handlers and helper functions for SBM workflow.
// Included by workflow_core.php only — not directly.

function fnAutoGenerateCheckpoints(PDO $db, int $syId, array $phases, bool $fromDB): void {
    $schools = $db->query("SELECT school_id FROM schools")->fetchAll(PDO::FETCH_COLUMN);
    $typeMap = [
        1 => [['self_assessment', null]],
        2 => [['planning', null]],
        3 => [['q1_monitoring',1],['q2_monitoring',2],['q3_monitoring',3],['completion',null]],
    ];
    foreach ($phases as $p) {
        $phNo  = $fromDB ? (int)$p['phase_no'] : (int)$p['no'];
        $pStart= $fromDB ? $p['date_start']    : $p['start'];
        $pEnd  = $fromDB ? $p['date_end']      : $p['end'];
        foreach ($schools as $sid) {
            foreach (($typeMap[$phNo] ?? []) as [$ctype, $qno]) {
                $due = $pEnd;
                if ($phNo === 3 && $qno) {
                    $span = (strtotime($pEnd) - strtotime($pStart)) / 3;
                    $due  = date('Y-m-d', strtotime($pStart) + $span * $qno);
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

function fnUpdateSchoolStatus(PDO $db, int $schoolId, int $syId): void {
    $cps = $db->prepare("SELECT cp_type,status FROM workflow_checkpoints WHERE school_id=? AND sy_id=?");
    $cps->execute([$schoolId, $syId]);
    $byType = array_column($cps->fetchAll(), 'status', 'cp_type');

    $p1done = ($byType['self_assessment'] ?? '') === 'done';
    $p2done = ($byType['planning']        ?? '') === 'done';
    $p3done = ($byType['completion']      ?? '') === 'done';

    $curPhase = 1;
    if ($p1done) $curPhase = 2;
    if ($p2done) $curPhase = 3;

    $overall = 'not_started';
    if ($p1done || in_array('done', array_values($byType))) $overall = 'in_progress';
    if ($p3done) $overall = 'completed';

    $db->prepare("INSERT INTO school_workflow_status (school_id,sy_id,current_phase,overall_status)
                  VALUES (?,?,?,?)
                  ON DUPLICATE KEY UPDATE current_phase=VALUES(current_phase),overall_status=VALUES(overall_status),updated_at=NOW()")
       ->execute([$schoolId, $syId, $curPhase, $overall]);
}

function handleWorkflowPost(PDO $db): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) return;

    header('Content-Type: application/json');
    if (function_exists('verifyCsrf')) verifyCsrf();

    $action = $_POST['action'];
    $syId   = (int)($_POST['sy_id'] ?? 0);

    if ($action === 'save_periods') {
        $periods = json_decode($_POST['periods_json'] ?? '[]', true);
        $db->prepare("UPDATE grading_periods SET is_current=0 WHERE sy_id=?")->execute([$syId]);
        foreach ($periods as $p) {
            $db->prepare("INSERT INTO grading_periods (sy_id,period_no,period_name,date_start,date_end,is_current)
                          VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE
                            period_name=VALUES(period_name),date_start=VALUES(date_start),
                            date_end=VALUES(date_end),is_current=VALUES(is_current)")
               ->execute([$syId, (int)$p['no'], trim($p['name']), $p['start'], $p['end'], (int)($p['current']??0)]);
        }
        echo json_encode(['ok' => true, 'msg' => 'Grading periods saved.']); exit;
    }

    if ($action === 'save_phases') {
        $phases = json_decode($_POST['phases_json'] ?? '[]', true);
        foreach ($phases as $p) {
            $db->prepare("INSERT INTO sbm_workflow_phases (sy_id,phase_no,phase_name,description,date_start,date_end,is_active)
                          VALUES (?,?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE
                            phase_name=VALUES(phase_name),description=VALUES(description),
                            date_start=VALUES(date_start),date_end=VALUES(date_end),is_active=VALUES(is_active)")
               ->execute([$syId,(int)$p['no'],trim($p['name']),trim($p['desc']),$p['start'],$p['end'],(int)($p['active']??0)]);
        }
        fnAutoGenerateCheckpoints($db, $syId, $phases, false);
        echo json_encode(['ok' => true, 'msg' => 'Workflow phases saved and checkpoints generated.']); exit;
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
        $phQ->execute([$syId]); $phArr = $phQ->fetchAll();
        fnAutoGenerateCheckpoints($db, $syId, $phArr, true);
        echo json_encode(['ok' => true, 'msg' => "Workflow initialized for {$inserted} new schools."]); exit;
    }

    if ($action === 'activate_phase') {
        $phaseNo = (int)$_POST['phase_no'];
        $db->prepare("UPDATE sbm_workflow_phases SET is_active=0 WHERE sy_id=?")->execute([$syId]);
        $db->prepare("UPDATE sbm_workflow_phases SET is_active=1 WHERE sy_id=? AND phase_no=?")->execute([$syId, $phaseNo]);
        $db->prepare("UPDATE workflow_checkpoints SET status='overdue' WHERE sy_id=? AND status='pending' AND due_date < CURDATE()")->execute([$syId]);
        echo json_encode(['ok' => true, 'msg' => "Phase {$phaseNo} is now active."]); exit;
    }

    if ($action === 'set_period') {
        $periodNo = (int)$_POST['period_no'];
        $db->prepare("UPDATE grading_periods SET is_current=0 WHERE sy_id=?")->execute([$syId]);
        $db->prepare("UPDATE grading_periods SET is_current=1 WHERE sy_id=? AND period_no=?")->execute([$syId, $periodNo]);
        echo json_encode(['ok' => true, 'msg' => 'Current grading period updated.']); exit;
    }

    if ($action === 'mark_checkpoint') {
        $cpId     = (int)$_POST['cp_id'];
        $schoolId = (int)$_POST['school_id'];
        $notes    = trim($_POST['notes'] ?? '');
        $db->prepare("UPDATE workflow_checkpoints SET status='done',completed_at=NOW(),completed_by=?,notes=? WHERE cp_id=?")
           ->execute([$_SESSION['user_id'], $notes, $cpId]);
        fnUpdateSchoolStatus($db, $schoolId, $syId);
        echo json_encode(['ok' => true, 'msg' => 'Checkpoint marked as done.']); exit;
    }

    exit;
}