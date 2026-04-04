<?php
// includes/workflow_data.php
// Loads all data needed for the workflow view.
// Call loadWorkflowData($db) and it returns an array.

function loadWorkflowData(PDO $db): array
{
    $syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
    $syId = (int) ($_GET['sy'] ?? (
        $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn()
        ?: ($syears[0]['sy_id'] ?? 0)
    ));

    $syRow = $db->prepare("SELECT * FROM school_years WHERE sy_id=?");
    $syRow->execute([$syId]);
    $syRow = $syRow->fetch();

    $dbPhasesQ = $db->prepare("SELECT * FROM sbm_workflow_phases WHERE sy_id=? ORDER BY phase_no");
    $dbPhasesQ->execute([$syId]);
    $dbPhases = $dbPhasesQ->fetchAll();

    $activePhaseNo = 0;
    foreach ($dbPhases as $ph) {
        if ($ph['is_active'])
            $activePhaseNo = (int) $ph['phase_no'];
    }

    $dbPeriodsQ = $db->prepare("SELECT * FROM grading_periods WHERE sy_id=? ORDER BY period_no");
    $dbPeriodsQ->execute([$syId]);
    $dbPeriods = $dbPeriodsQ->fetchAll();

    $currentPeriodNo = 0;
    foreach ($dbPeriods as $p) {
        if ($p['is_current'])
            $currentPeriodNo = (int) $p['period_no'];
    }

    // ── Schools with cycle status and submission progress ─────
    $schoolSQL = "
        SELECT s.school_id, s.school_name, s.classification,
            ws.current_phase, ws.overall_status,
            (SELECT COUNT(*) FROM workflow_checkpoints wc
             WHERE wc.school_id=s.school_id AND wc.sy_id=? AND wc.status='done')    AS cp_done,
            (SELECT COUNT(*) FROM workflow_checkpoints wc
             WHERE wc.school_id=s.school_id AND wc.sy_id=?)                          AS cp_total,
            (SELECT COUNT(*) FROM workflow_checkpoints wc
             WHERE wc.school_id=s.school_id AND wc.sy_id=? AND wc.status='overdue') AS cp_overdue,
            sc.cycle_id,
            sc.overall_score,
            sc.maturity_level,
            sc.status        AS cycle_status,
            sc.submitted_at,
            sc.validated_at,
            sc.finalized_at,
            sc.consolidation_confirmed
        FROM schools s
        LEFT JOIN school_workflow_status ws ON ws.school_id=s.school_id AND ws.sy_id=?
        LEFT JOIN sbm_cycles sc ON sc.school_id=s.school_id AND sc.sy_id=?
        ORDER BY s.school_name";
    $sStmt = $db->prepare($schoolSQL);
    $sStmt->execute([$syId, $syId, $syId, $syId, $syId]);
    $schools = $sStmt->fetchAll();

    // ── Selected school detail ────────────────────────────────
    $selId = (int) ($_GET['school'] ?? 0);
    $selSchool = null;
    $selCps = [];
    $selCycle = null;
    $auditLog = [];
    $submissionProgress = ['teachers' => [], 'stakeholders' => []];

    if ($selId) {
        // School info
        $q = $db->prepare("SELECT * FROM schools WHERE school_id=?");
        $q->execute([$selId]);
        $selSchool = $q->fetch();

        // Checkpoints
        $q2 = $db->prepare("
            SELECT wc.*, u.full_name AS done_by_name
            FROM workflow_checkpoints wc
            LEFT JOIN users u ON wc.completed_by = u.user_id
            WHERE wc.school_id=? AND wc.sy_id=?
            ORDER BY wc.phase_no, wc.grading_period
        ");
        $q2->execute([$selId, $syId]);
        $selCps = $q2->fetchAll();

        // Active cycle for this school
        $cq = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=? LIMIT 1");
        $cq->execute([$selId, $syId]);
        $selCycle = $cq->fetch();

        if ($selCycle) {
            $cycleId = (int) $selCycle['cycle_id'];

            // Audit log
            $aq = $db->prepare("
                SELECT cal.stage_from, cal.stage_to, cal.notes, cal.created_at,
                       u.full_name AS actor_name, u.role AS actor_role
                FROM cycle_audit_log cal
                LEFT JOIN users u ON cal.actor_id = u.user_id
                WHERE cal.cycle_id = ?
                ORDER BY cal.created_at ASC
            ");
            $aq->execute([$cycleId]);
            $auditLog = $aq->fetchAll();

            // Teacher submission progress
            $tq = $db->prepare("
                SELECT u.user_id, u.full_name,
                       COALESCE(ts.status, 'not_started') AS submission_status,
                       ts.submitted_at,
                       COUNT(DISTINCT tia.indicator_code) AS assigned_count
                FROM teacher_indicator_assignments tia
                JOIN users u ON tia.teacher_id = u.user_id
                LEFT JOIN teacher_submissions ts
                    ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
                WHERE tia.cycle_id = ?
                GROUP BY u.user_id, u.full_name, ts.status, ts.submitted_at
            ");
            $tq->execute([$cycleId, $cycleId]);
            $submissionProgress['teachers'] = $tq->fetchAll();

            // Stakeholder submission progress
            $sq = $db->prepare("
                SELECT u.user_id, u.full_name,
                       COALESCE(ss.status, 'not_started') AS submission_status,
                       ss.submitted_at
                FROM stakeholder_submissions ss
                JOIN users u ON ss.stakeholder_id = u.user_id
                WHERE ss.cycle_id = ?
            ");
            $sq->execute([$cycleId]);
            $submissionProgress['stakeholders'] = $sq->fetchAll();
        }
    }

    // ── Submission threshold summary (for stat cards) ─────────
    $stageCountsQ = $db->prepare("
        SELECT status, COUNT(*) AS cnt
        FROM sbm_cycles
        WHERE sy_id = ?
        GROUP BY status
    ");
    $stageCountsQ->execute([$syId]);
    $stageCounts = [];
    foreach ($stageCountsQ->fetchAll() as $row) {
        $stageCounts[$row['status']] = (int) $row['cnt'];
    }

    return compact(
        'syId',
        'syRow',
        'syears',
        'dbPhases',
        'activePhaseNo',
        'dbPeriods',
        'currentPeriodNo',
        'schools',
        'selId',
        'selSchool',
        'selCps',
        'selCycle',
        'auditLog',
        'submissionProgress',
        'stageCounts'
    );
}