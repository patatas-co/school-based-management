<?php
// includes/workflow_data.php
// Loads all data needed for the workflow view.
// Call loadWorkflowData($db) and it returns an array.

function loadWorkflowData(PDO $db): array {
    $syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
    $syId   = (int)($_GET['sy'] ?? (
        $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn()
        ?: ($syears[0]['sy_id'] ?? 0)
    ));

    $syRow = $db->prepare("SELECT * FROM school_years WHERE sy_id=?");
    $syRow->execute([$syId]); $syRow = $syRow->fetch();

    $dbPhasesQ = $db->prepare("SELECT * FROM sbm_workflow_phases WHERE sy_id=? ORDER BY phase_no");
    $dbPhasesQ->execute([$syId]); $dbPhases = $dbPhasesQ->fetchAll();

    $activePhaseNo = 0;
    foreach ($dbPhases as $ph) { if ($ph['is_active']) $activePhaseNo = (int)$ph['phase_no']; }

    $dbPeriodsQ = $db->prepare("SELECT * FROM grading_periods WHERE sy_id=? ORDER BY period_no");
    $dbPeriodsQ->execute([$syId]); $dbPeriods = $dbPeriodsQ->fetchAll();

    $currentPeriodNo = 0;
    foreach ($dbPeriods as $p) { if ($p['is_current']) $currentPeriodNo = (int)$p['period_no']; }

    $schoolSQL = "
        SELECT s.school_id, s.school_name, s.classification,
            ws.current_phase, ws.overall_status,
            (SELECT COUNT(*) FROM workflow_checkpoints wc WHERE wc.school_id=s.school_id AND wc.sy_id=? AND wc.status='done')    AS cp_done,
            (SELECT COUNT(*) FROM workflow_checkpoints wc WHERE wc.school_id=s.school_id AND wc.sy_id=?)                          AS cp_total,
            (SELECT COUNT(*) FROM workflow_checkpoints wc WHERE wc.school_id=s.school_id AND wc.sy_id=? AND wc.status='overdue') AS cp_overdue,
            sc.overall_score, sc.maturity_level
        FROM schools s
        LEFT JOIN school_workflow_status ws ON ws.school_id=s.school_id AND ws.sy_id=?
        LEFT JOIN sbm_cycles sc ON sc.school_id=s.school_id AND sc.sy_id=?
        ORDER BY s.school_name";
    $sStmt = $db->prepare($schoolSQL);
    $sStmt->execute([$syId,$syId,$syId,$syId,$syId]);
    $schools = $sStmt->fetchAll();

    $selId = (int)($_GET['school'] ?? 0);
    $selSchool = null; $selCps = [];
    if ($selId) {
        $q = $db->prepare("SELECT * FROM schools WHERE school_id=?");
        $q->execute([$selId]); $selSchool = $q->fetch();
        $q2 = $db->prepare("SELECT wc.*, u.full_name AS done_by_name FROM workflow_checkpoints wc LEFT JOIN users u ON wc.completed_by=u.user_id WHERE wc.school_id=? AND wc.sy_id=? ORDER BY wc.phase_no, wc.grading_period");
        $q2->execute([$selId, $syId]); $selCps = $q2->fetchAll();
    }

    return compact(
        'syId','syRow','syears',
        'dbPhases','activePhaseNo',
        'dbPeriods','currentPeriodNo',
        'schools','selId','selSchool','selCps'
    );
}