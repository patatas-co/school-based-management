<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/ai_recommendation_helpers.php';

$db = getDB();
$confidenceCases = [
    [
        'name' => 'negative_score',
        'outlier' => [
            'anomaly_type' => 'erratic',
            'teacher_id' => 1,
            'anomaly_score' => -0.1,
            'supporting_numbers' => ['peer_mean_abs_difference' => 1.0],
        ],
        'expected' => 70.0,
    ],
    [
        'name' => 'missing_score',
        'outlier' => [
            'anomaly_type' => 'erratic',
            'teacher_id' => 1,
            'supporting_numbers' => ['peer_mean_abs_difference' => 1.0],
        ],
        'expected' => 70.0,
    ],
];
$confidenceResults = [];
foreach ($confidenceCases as $case) {
    $card = buildTeacherOutlierCard($case['outlier'], 'Teacher 1');
    $actual = (float) $card['block']['confidence_pct'];
    if ($actual !== $case['expected']) {
        throw new RuntimeException(
            $case['name'] . ' confidence expected ' . $case['expected'] . ', got ' . $actual
        );
    }
    $confidenceResults[] = [
        'name' => $case['name'],
        'confidence_pct' => $actual,
        'expected' => $case['expected'],
    ];
}
$truth = $db->query("
    SELECT cycle_id, evaluator_id, anomaly_type
    FROM seed_ground_truth
    ORDER BY cycle_id, evaluator_id
")->fetchAll(PDO::FETCH_ASSOC);
$truthByCycle = [];
foreach ($truth as $row) {
    $truthByCycle[(int) $row['cycle_id']][] = (int) $row['evaluator_id'];
}
$results = [];
$nameLeak = false;
foreach (array_keys($truthByCycle) as $cycleId) {
    $cycleQ = $db->prepare("SELECT school_id FROM sbm_cycles WHERE cycle_id = ?");
    $cycleQ->execute([$cycleId]);
    $schoolId = (int) $cycleQ->fetchColumn();
    $summary = getTeacherRatingSummaryData($db, $cycleId, $schoolId);
    $detected = detectTeacherOutliers($db, $cycleId, $schoolId);
    $cards = buildLowRaterCards(
        $summary['summaries'],
        $summary['display_names'],
        $db,
        $cycleId,
        $schoolId
    );
    $neutralPayload = json_encode($detected['outliers'], JSON_THROW_ON_ERROR);
    foreach ($summary['display_names'] as $name) {
        if ($name !== '' && strpos($neutralPayload, html_entity_decode($name, ENT_QUOTES, 'UTF-8')) !== false) {
            $nameLeak = true;
        }
    }
    $results[] = [
        'cycle_id' => $cycleId,
        'seed_teacher_ids' => $truthByCycle[$cycleId],
        'card_teacher_ids' => array_values(array_map(
            static fn(array $card): int => (int) ($card['block']['teacher_user_id'] ?? 0),
            $cards
        )),
        'card_sources' => array_values(array_map(
            static fn(array $card): string => (string) ($card['block']['source'] ?? ''),
            $cards
        )),
        'card_confidence' => array_values(array_map(
            static fn(array $card): array => [
                'teacher_id' => (int) ($card['block']['teacher_user_id'] ?? 0),
                'confidence_pct' => $card['block']['confidence_pct'] ?? null,
                'confidence_level' => $card['block']['confidence_level'] ?? null,
                'factors' => $card['block']['factors'] ?? [],
            ],
            $cards
        )),
    ];
}
echo json_encode([
    'llm_called' => false,
    'cycles_checked' => count($results),
    'cycles' => $results,
    'real_name_in_neutral_payload_or_logs' => $nameLeak,
    'test_rows_written' => false,
    'confidence_formula_cases' => $confidenceResults,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
