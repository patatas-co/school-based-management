<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
require_once __DIR__ . '/../config/db.php';

/**
 * Read-only data bridge for teacher_outlier.py. It deliberately returns no
 * teacher names; evaluator IDs are neutral identifiers for model work.
 */
$includeGroundTruth = in_array('--ground-truth', $argv ?? [], true);
$db = getDB();

$rows = $db->query("
    SELECT tr.cycle_id, tr.teacher_id, tr.indicator_id, tr.rating,
           sy.label AS school_year_label
    FROM teacher_responses tr
    JOIN sbm_cycles c ON c.cycle_id = tr.cycle_id
    JOIN school_years sy ON sy.sy_id = c.sy_id
    WHERE tr.status = 'submitted'
    ORDER BY tr.cycle_id, tr.teacher_id, tr.indicator_id
")->fetchAll(PDO::FETCH_ASSOC);

$schoolHead = $db->query("
    SELECT sr.cycle_id, sr.indicator_id, sr.rating
    FROM sbm_responses sr
    ORDER BY sr.cycle_id, sr.indicator_id
")->fetchAll(PDO::FETCH_ASSOC);

$cycles = $db->query("
    SELECT cycle_id, overall_score
    FROM sbm_cycles
")->fetchAll(PDO::FETCH_ASSOC);

$result = [
    'teacher_responses' => $rows,
    'sbm_responses' => $schoolHead,
    'cycles' => $cycles,
];
if ($includeGroundTruth) {
    $result['seed_ground_truth'] = $db->query("
        SELECT cycle_id, evaluator_id, anomaly_type, school_year_label
        FROM seed_ground_truth
        ORDER BY cycle_id, evaluator_id
    ")->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
