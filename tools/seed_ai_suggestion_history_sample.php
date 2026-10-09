<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$options = getopt('', ['school-id:', 'dry-run']);
$schoolId = filter_var($options['school-id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($schoolId === false || $schoolId === null) {
    fwrite(STDERR, "Usage: php tools/seed_ai_suggestion_history_sample.php --school-id=1 [--dry-run]\n");
    exit(2);
}
$dryRun = array_key_exists('dry-run', $options);
$schoolYears = ['2023-2024', '2024-2025'];
$templates = [
    1 => [
        ['Align lesson planning with curriculum standards', 'Review lesson plans against the current curriculum standards each term. Use learner evidence to select one instructional adjustment and track its effect at the next review.'],
        ['Strengthen classroom safety routines', 'Review safety procedures with learners and staff, record hazards during regular walk-throughs, and assign owners and completion dates for follow-up actions.'],
        ['Formalize leadership monitoring routines', 'Schedule short monthly leadership reviews of priority indicators. Record decisions, responsible people, and due dates, then revisit progress at the next meeting.'],
        ['Improve transparent school decision-making', 'Share key school priorities and resource decisions with stakeholders. Keep concise records of feedback, decisions, and the follow-up actions agreed by the school community.'],
        ['Expand teacher mentoring and development', 'Use classroom evidence and teacher input to identify development needs. Pair colleagues for focused peer-learning sessions and review one agreed practice after each cycle.'],
        ['Direct resources toward priority needs', 'Compare planned spending with documented learner and facility needs. Review utilization regularly and update priorities when evidence shows that needs have changed.'],
    ],
    2 => [
        ['Use learner evidence to refine instruction', 'Build on the previous curriculum review by checking learner progress at each term. Retain approaches that are working and target additional support where results remain below expectations.'],
        ['Reinforce consistent safety follow-through', 'Use a shared hazard log with an assigned owner and target date for every concern. Review unresolved items with staff and learners during scheduled safety checks.'],
        ['Build on leadership review follow-through', 'Continue the leadership review routine and add a brief evidence check for each action. Carry forward unresolved tasks with clear owners and confirm completed improvements with stakeholders.'],
        ['Close the loop on stakeholder decisions', 'Publish a short update after each consultation showing the issue raised, the decision made, and the action taken. Check whether the response addressed the original concern.'],
        ['Scale peer learning that improved practice', 'Continue teacher mentoring and identify practices that colleagues found useful. Share those practices in a short learning session and review their use in the next classroom cycle.'],
        ['Track resource use against outcomes', 'Compare resource utilization with the outcomes the spending was intended to support. Preserve effective allocations and revise or redirect funds where evidence indicates limited impact.'],
    ],
];
$priorities = ['High', 'High', 'Medium', 'Medium', 'Medium', 'Low'];
$risks = ['medium', 'low', 'low', 'medium', 'medium', 'low'];

try {
    $db = getDB();
    $ownerStmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE school_id = ? AND role = 'school_head' AND status = 'active'
        ORDER BY user_id
        LIMIT 1
    ");
    $ownerStmt->execute([$schoolId]);
    $generatedBy = (int) ($ownerStmt->fetchColumn() ?: 0);
    if ($generatedBy < 1) {
        throw new RuntimeException("No active School Head was found for school_id $schoolId.");
    }

    $cycleStmt = $db->prepare("
        SELECT c.cycle_id, sy.label, d.dimension_id, d.dimension_no, d.dimension_name,
               ROUND(AVG(ds.percentage), 1) AS score
        FROM sbm_cycles c
        JOIN school_years sy ON sy.sy_id = c.sy_id
        JOIN sbm_dimension_scores ds ON ds.cycle_id = c.cycle_id
        JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
        WHERE c.school_id = ?
          AND sy.label IN (?, ?)
          AND c.status IN ('validated', 'finalized', 'completed')
        GROUP BY c.cycle_id, sy.label, d.dimension_id, d.dimension_no, d.dimension_name
        ORDER BY sy.label, COALESCE(c.finalized_at, c.created_at) DESC, c.cycle_id DESC, d.dimension_no
    ");
    $cycleStmt->execute([$schoolId, $schoolYears[0], $schoolYears[1]]);
    $cyclesByYear = [];
    foreach ($cycleStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $year = (string) $row['label'];
        if (isset($cyclesByYear[$year]['cycle_id']) && $cyclesByYear[$year]['cycle_id'] !== (int) $row['cycle_id']) {
            continue;
        }
        $cyclesByYear[$year]['cycle_id'] = (int) $row['cycle_id'];
        $cyclesByYear[$year]['dimensions'][(int) $row['dimension_no']] = [
            'dimension_id' => (int) $row['dimension_id'],
            'dimension_no' => (int) $row['dimension_no'],
            'dimension_name' => (string) $row['dimension_name'],
            'score' => $row['score'] === null ? null : (float) $row['score'],
        ];
    }
    foreach ($schoolYears as $year) {
        if (count($cyclesByYear[$year]['dimensions'] ?? []) < 6) {
            throw new RuntimeException("School year $year needs a finalized assessment with scores for all six dimensions.");
        }
    }

    $generatedAt = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
    if ($dryRun) {
        foreach ($schoolYears as $year) {
            foreach ($cyclesByYear[$year]['dimensions'] as $dimensionNo => $dimension) {
                printf("%s D%d (%s): %s%%\n", $year, $dimensionNo, $dimension['dimension_name'], $dimension['score']);
            }
        }
        fwrite(STDOUT, "Dry run only; no sample rows were written.\n");
        exit(0);
    }

    $db->beginTransaction();
    $deleteSampleHistory = $db->prepare('DELETE FROM ai_suggestion_history WHERE school_id = ? AND is_sample = 1');
    $deleteSampleHistory->execute([$schoolId]);
    $deleteSamplePlans = $db->prepare('DELETE FROM improvement_plans WHERE school_id = ? AND is_sample = 1');
    $deleteSamplePlans->execute([$schoolId]);

    $clearFinal = $db->prepare("
        UPDATE ai_suggestion_history
        SET is_final = 0
        WHERE school_id = ? AND school_year IN (?, ?)
    ");
    $clearFinal->execute([$schoolId, $schoolYears[0], $schoolYears[1]]);

    $adoptedPlanIds = [];
    $planInsert = $db->prepare("
        INSERT INTO improvement_plans
          (school_id, cycle_id, dimension_id, priority_level, objective, strategy,
           person_responsible, target_date, expected_output, status, workflow_status,
           current_owner_role, created_by, is_sample)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', 'approved', 'school_head', ?, 1)
    ");
    foreach ([3, 5] as $dimensionNo) {
        $dimension = $cyclesByYear[$schoolYears[0]]['dimensions'][$dimensionNo];
        $planInsert->execute([
            $schoolId,
            $cyclesByYear[$schoolYears[0]]['cycle_id'],
            $dimension['dimension_id'],
            $priorities[$dimensionNo - 1],
            'Sample plan: ' . $templates[1][$dimensionNo - 1][0],
            'Sample plan: implement the related recommendation and review evidence at the end of the cycle.',
            'School Head and assigned SBM team',
            substr($schoolYears[0], 0, 4) . '-12-15',
            'Sample outcome for Suggestion History comparison.',
            $generatedBy,
        ]);
        $adoptedPlanIds[$dimensionNo] = (int) $db->lastInsertId();
    }

    $historyInsert = $db->prepare("
        INSERT INTO ai_suggestion_history
          (school_id, school_year, dimension_id, suggestion_title, suggestion_text,
           last_cycle_note, priority_label, decline_risk, data_status, score_at_time,
           model_version, is_final, adopted_plan_id, generated_by, generated_at, is_sample)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sufficient', ?, 'sample_data', 1, ?, ?, ?, 1)
    ");
    $scoresByYear = [];
    foreach ($schoolYears as $yearIndex => $year) {
        foreach ($cyclesByYear[$year]['dimensions'] as $dimensionNo => $dimension) {
            $scoresByYear[$year][$dimensionNo] = $dimension['score'];
            $priorScore = $yearIndex > 0 ? ($scoresByYear[$schoolYears[0]][$dimensionNo] ?? null) : null;
            $scoreChange = ($priorScore !== null && $dimension['score'] !== null)
                ? round($dimension['score'] - $priorScore, 1)
                : null;
            $priorAdopted = $yearIndex > 0 && isset($adoptedPlanIds[$dimensionNo]);
            $lastCycleNote = null;
            if ($yearIndex > 0) {
                if ($scoreChange === null) {
                    $lastCycleNote = 'The previous suggestion could not be evaluated because score data is unavailable.';
                } else {
                    $adoption = $priorAdopted ? 'was adopted through an improvement plan' : 'was not adopted into an improvement plan';
                    $direction = $scoreChange > 0 ? 'improved' : ($scoreChange < 0 ? 'declined' : 'was unchanged');
                    $lastCycleNote = sprintf(
                        'The prior suggestion %s; the score %s by %.1f points.',
                        $adoption,
                        $direction,
                        abs($scoreChange)
                    );
                }
            }
            $template = $templates[$yearIndex + 1][$dimensionNo - 1];
            $historyInsert->execute([
                $schoolId,
                $year,
                $dimension['dimension_id'],
                $template[0],
                $template[1],
                $lastCycleNote,
                $priorities[$dimensionNo - 1],
                $risks[$dimensionNo - 1],
                $dimension['score'],
                $yearIndex === 0 ? ($adoptedPlanIds[$dimensionNo] ?? null) : null,
                $generatedBy,
                $generatedAt,
            ]);
        }
    }
    $db->commit();
    fwrite(STDOUT, "Inserted 12 sample suggestions and 2 linked sample improvement plans for school_id $schoolId.\n");
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Sample seeding failed: ' . $e->getMessage() . "\n");
    exit(1);
}
