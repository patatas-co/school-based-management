<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This seed script can only run from the command line.\n");
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../config/db.php';

const DEMO_MARKER = 'CATBOOST_DEMO_V1';
const DEMO_BACKEND = 'catboost_demo_v1';
const DEMO_YEAR_LABELS = [
    'CBRANKDEMO-01',
    'CBRANKDEMO-02',
    'CBRANKDEMO-03',
    'CBRANKDEMO-04',
];

function demoPdo(): PDO
{
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET time_zone = '+08:00'");
    return $pdo;
}

function deleteDemoRows(PDO $db): void
{
    $seedYears = $db->prepare(
        'SELECT DISTINCT sy.sy_id
         FROM school_years sy
         JOIN sbm_cycles c ON c.sy_id = sy.sy_id
         WHERE sy.label IN (?, ?, ?, ?) AND c.validator_remarks LIKE ?'
    );
    $seedYears->execute(array_merge(DEMO_YEAR_LABELS, [DEMO_MARKER . ':%']));
    $seedYearIds = array_map('intval', $seedYears->fetchAll(PDO::FETCH_COLUMN));

    $plans = $db->prepare('DELETE FROM improvement_plans WHERE remarks LIKE ?');
    $plans->execute([DEMO_MARKER . ':%']);

    $items = $db->prepare(
        'DELETE FROM ai_suggestion_items
         WHERE generation_id IN (
             SELECT generation_id FROM ai_suggestion_generations
             WHERE is_synthetic = 1 AND backend_used = ?
         )'
    );
    $items->execute([DEMO_BACKEND]);

    $teacherResponses = $db->prepare(
        'DELETE FROM teacher_responses WHERE remarks LIKE ?'
    );
    $teacherResponses->execute([DEMO_MARKER . ':%']);

    $dimensionScores = $db->prepare(
        'DELETE FROM sbm_dimension_scores
         WHERE cycle_id IN (
             SELECT cycle_id FROM sbm_cycles WHERE validator_remarks LIKE ?
         )'
    );
    $dimensionScores->execute([DEMO_MARKER . ':%']);

    $teacherSubmissions = $db->prepare(
        'DELETE FROM teacher_submissions
         WHERE cycle_id IN (
             SELECT cycle_id FROM sbm_cycles WHERE validator_remarks LIKE ?
         )'
    );
    $teacherSubmissions->execute([DEMO_MARKER . ':%']);

    $generations = $db->prepare(
        'DELETE FROM ai_suggestion_generations
         WHERE is_synthetic = 1 AND backend_used = ?'
    );
    $generations->execute([DEMO_BACKEND]);

    $cycles = $db->prepare('DELETE FROM sbm_cycles WHERE validator_remarks LIKE ?');
    $cycles->execute([DEMO_MARKER . ':%']);

    $deleteYear = $db->prepare(
        'DELETE FROM school_years WHERE sy_id = ? AND label IN (?, ?, ?, ?) AND is_current = 0'
    );
    foreach ($seedYearIds as $seedYearId) {
        $deleteYear->execute(array_merge([$seedYearId], DEMO_YEAR_LABELS));
    }
}

function shuffledKeys(array $values): array
{
    $keys = array_keys($values);
    shuffle($keys);
    return $keys;
}

function buildCycleRatings(array $indicators, array $previousRatings): array
{
    $ratings = [];
    foreach ($indicators as $indicator) {
        $key = (int) $indicator['indicator_id'];
        $previous = $previousRatings[$key] ?? mt_rand(1, 4);
        $ratings[$key] = max(1, min(4, $previous + mt_rand(-1, 1)));
    }

    $forced = [];
    foreach ([1, 2, 3, 4] as $requiredRating) {
        if (!in_array($requiredRating, $ratings, true)) {
            foreach (shuffledKeys($ratings) as $key) {
                if (!isset($forced[$key])) {
                    $ratings[$key] = $requiredRating;
                    $forced[$key] = true;
                    break;
                }
            }
        }
    }

    $topRatingLimit = max(1, min(4, (int) round(count($ratings) * 0.08)));
    $topKeys = array_keys(array_filter(
        $ratings,
        static fn(int $rating): bool => $rating === 4
    ));
    if (count($topKeys) > $topRatingLimit) {
        shuffle($topKeys);
        foreach (array_slice($topKeys, $topRatingLimit) as $key) {
            $ratings[$key] = 3;
        }
    }

    return $ratings;
}

function demoSuggestionText(string $dimensionName, bool $actionable, int $variant): array
{
    $titles = [
        'Strengthen %s implementation',
        'Improve %s monitoring',
        'Build a stronger %s practice',
        'Advance %s planning',
        'Review and support %s',
        'Coordinate %s improvement work',
    ];
    $title = sprintf($titles[$variant % count($titles)], $dimensionName);

    if (!$actionable) {
        $vagueBodies = [
            "- Review current $dimensionName activities periodically.\n- Consider possible improvements when time permits.",
            "- Discuss $dimensionName during a future meeting.\n- Continue monitoring general progress.",
            "Explore ways to support $dimensionName. Revisit the approach when more information becomes available.",
            "- Encourage the team to keep $dimensionName in mind.\n- Make adjustments as needed.",
        ];
        return [$title, $vagueBodies[$variant % count($vagueBodies)]];
    }

    $target = 5 + (($variant * 7) % 36);
    $month = 1 + ($variant % 12);
    $reviewCount = 2 + ($variant % 5);
    $responsible = ['principal', 'teacher', 'coordinator', 'school team'][$variant % 4];
    $resource = ['materials', 'learning resources', 'partner support', 'budget'][$variant % 4];
    $bodies = [
        "- Assign a $responsible team to audit $dimensionName by month $month and document each finding.\n"
            . "- Coordinate a review of $target priority items with the assigned teachers.\n"
            . "- Set aside the required $resource and record who is responsible for distribution.\n"
            . "- Check progress in $reviewCount weeks and report completion to the principal.",
        "- The principal will coordinate a $dimensionName action team and assign owners for each task.\n"
            . "- Prepare a baseline, then reach at least $target% completion by month $month.\n"
            . "- Identify the needed $resource and record its source before activities begin.\n"
            . "- Teachers will review evidence every $reviewCount weeks and adjust the plan.",
        "- Assign teachers to complete a $dimensionName checklist covering $target priority items.\n"
            . "- The coordinator will schedule the first check by month $month and log the results.\n"
            . "- Confirm the $resource with the principal and name the person responsible for it.\n"
                . "- Review the team's progress after $reviewCount weeks.",
        "- Coordinate with the school team to deliver $dimensionName activities by month $month.\n"
            . "- Assign a responsible teacher to track $target% participation and submit a short update.\n"
            . "- List the required $resource, estimate its cost, and identify a funding source.\n"
            . "- The principal will review progress after $reviewCount weeks.",
    ];
    return [$title, $bodies[$variant % count($bodies)]];
}

try {
    mt_srand(20261011);
    $db = demoPdo();
    $schoolId = defined('SCHOOL_ID') && SCHOOL_ID > 0 ? SCHOOL_ID : 1;

    $dimensionRows = $db->query(
        'SELECT dimension_id, dimension_no, dimension_name
         FROM sbm_dimensions ORDER BY dimension_no, dimension_id'
    )->fetchAll();
    $indicatorRows = $db->query(
        'SELECT i.indicator_id, i.indicator_code, i.dimension_id,
                d.dimension_no, d.dimension_name
         FROM sbm_indicators i
         JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
         WHERE i.is_active = 1
         ORDER BY d.dimension_no, i.sort_order, i.indicator_id'
    )->fetchAll();
    if (!$dimensionRows || !$indicatorRows) {
        throw new RuntimeException(
            'sbm_dimensions or active sbm_indicators is empty. No rows were changed; '
            . 'the seed script will not invent dimensions or indicators.'
        );
    }

    $schoolCheck = $db->prepare('SELECT COUNT(*) FROM schools WHERE school_id = ?');
    $schoolCheck->execute([$schoolId]);
    if ((int) $schoolCheck->fetchColumn() !== 1) {
        throw new RuntimeException(
            "Configured school_id $schoolId does not exist. No rows were changed."
        );
    }
    $teacherStmt = $db->prepare(
        "SELECT user_id FROM users
         WHERE school_id = ? AND role = 'teacher' AND status = 'active'
         ORDER BY user_id LIMIT 1"
    );
    $teacherStmt->execute([$schoolId]);
    $teacherId = (int) ($teacherStmt->fetchColumn() ?: 0);
    $schoolHeadStmt = $db->prepare(
        "SELECT user_id FROM users
         WHERE school_id = ? AND role = 'school_head' AND status = 'active'
         ORDER BY user_id LIMIT 1"
    );
    $schoolHeadStmt->execute([$schoolId]);
    $schoolHeadId = (int) ($schoolHeadStmt->fetchColumn() ?: 0);
    if ($teacherId < 1 || $schoolHeadId < 1) {
        throw new RuntimeException(
            'An existing active teacher and School Head for the configured school are required. '
            . 'No users will be created or modified; no rows were changed.'
        );
    }

    $indicatorsByDimension = [];
    foreach ($indicatorRows as $indicator) {
        $indicatorsByDimension[(int) $indicator['dimension_id']][] = $indicator;
    }
    $dimensions = [];
    foreach ($dimensionRows as $dimension) {
        $dimensionId = (int) $dimension['dimension_id'];
        if (!empty($indicatorsByDimension[$dimensionId])) {
            $dimension['indicators'] = $indicatorsByDimension[$dimensionId];
            $dimensions[] = $dimension;
        }
    }
    if (count($dimensions) < 3 || count($indicatorRows) < 4) {
        throw new RuntimeException(
            'At least 3 existing dimensions and 4 active existing indicators are required '
            . 'for varied demo records. No rows were changed.'
        );
    }

    $db->beginTransaction();
    deleteDemoRows($db);

    $yearCheck = $db->prepare('SELECT COUNT(*) FROM school_years WHERE label = ?');
    foreach (DEMO_YEAR_LABELS as $label) {
        $yearCheck->execute([$label]);
        if ((int) $yearCheck->fetchColumn() !== 0) {
            throw new RuntimeException(
                "School year label $label is already in use outside this demo seed."
            );
        }
    }

    $insertYear = $db->prepare(
        'INSERT INTO school_years (label, is_current, date_start, date_end, is_archived)
         VALUES (?, 0, NULL, NULL, 1)'
    );
    $insertCycle = $db->prepare(
        "INSERT INTO sbm_cycles
         (sy_id, school_id, status, overall_score, started_at, submitted_at,
          validated_at, finalized_at, validator_remarks, consolidation_confirmed)
         VALUES (?, ?, 'finalized', ?, ?, ?, ?, ?, ?, 1)"
    );
    $insertSubmission = $db->prepare(
        "INSERT INTO teacher_submissions
         (cycle_id, school_id, sy_id, teacher_id, status, submitted_at, response_count)
         VALUES (?, ?, ?, ?, 'submitted', ?, ?)"
    );
    $insertTeacherResponse = $db->prepare(
        "INSERT INTO teacher_responses
         (cycle_id, indicator_id, school_id, teacher_id, rating, remarks, status, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?)"
    );
    $insertDimensionScore = $db->prepare(
        'INSERT INTO sbm_dimension_scores
         (cycle_id, school_id, dimension_id, raw_score, max_score, percentage)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertGeneration = $db->prepare(
        'INSERT INTO ai_suggestion_generations
         (user_id, school_id, cycle_id, generated_at, is_synthetic, backend_used, input_payload_hash)
         VALUES (?, ?, ?, ?, 1, ?, ?)'
    );
    $insertItem = $db->prepare(
        "INSERT INTO ai_suggestion_items
         (generation_id, item_index, source, title, body_text, indicator_codes, created_at)
         VALUES (?, 1, 'llm', ?, ?, ?, ?)"
    );
    $insertPlan = $db->prepare(
        "INSERT INTO improvement_plans
         (school_id, cycle_id, dimension_id, indicator_id, priority_level, objective,
          strategy, person_responsible, target_date, resources_needed, expected_output,
          status, workflow_status, current_owner_role, remarks, created_by, submitted_by,
          submitted_at, approved_by, approved_at, validated_by, validated_at,
          suggestion_item_id, suggestion_baseline_text, is_sample)
         VALUES
         (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'school_head',
          ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );

    $previousRatings = [];
    $indicatorCount = count($indicatorRows);
    for ($cycleIndex = 0; $cycleIndex < 4; $cycleIndex++) {
        $yearLabel = DEMO_YEAR_LABELS[$cycleIndex];
        $insertYear->execute([$yearLabel]);
        $syId = (int) $db->lastInsertId();
        $cycleTime = (new DateTimeImmutable('-' . (4 - $cycleIndex) . ' years'))
            ->setTime(9, 0, 0)
            ->format('Y-m-d H:i:s');
        $cycleRatingMap = buildCycleRatings($indicatorRows, $previousRatings);
        $previousRatings = $cycleRatingMap;
        $rawByDimension = [];
        $maxByDimension = [];
        foreach ($indicatorRows as $indicator) {
            $dimensionId = (int) $indicator['dimension_id'];
            $rating = $cycleRatingMap[(int) $indicator['indicator_id']];
            $rawByDimension[$dimensionId] = ($rawByDimension[$dimensionId] ?? 0) + $rating;
            $maxByDimension[$dimensionId] = ($maxByDimension[$dimensionId] ?? 0) + 4;
        }
        $cycleOverall = round(
            array_sum($rawByDimension) / max(1, array_sum($maxByDimension)) * 100,
            2
        );
        $insertCycle->execute([
            $syId,
            $schoolId,
            $cycleOverall,
            $cycleTime,
            $cycleTime,
            $cycleTime,
            $cycleTime,
            DEMO_MARKER . ':cycle-' . ($cycleIndex + 1),
        ]);
        $cycleId = (int) $db->lastInsertId();

        $insertSubmission->execute([
            $cycleId,
            $schoolId,
            $syId,
            $teacherId,
            $cycleTime,
            $indicatorCount,
        ]);
        foreach ($indicatorRows as $indicator) {
            $indicatorId = (int) $indicator['indicator_id'];
            $insertTeacherResponse->execute([
                $cycleId,
                $indicatorId,
                $schoolId,
                $teacherId,
                $cycleRatingMap[$indicatorId],
                DEMO_MARKER . ':teacher-response:' . $cycleIndex . ':' . $indicatorId,
                $cycleTime,
            ]);
        }

        foreach ($dimensions as $dimension) {
            $dimensionId = (int) $dimension['dimension_id'];
            $rawScore = (int) ($rawByDimension[$dimensionId] ?? 0);
            $maxScore = (int) ($maxByDimension[$dimensionId] ?? 0);
            if ($maxScore < 1) {
                continue;
            }
            $percentage = round(($rawScore / $maxScore) * 100, 2);
            $insertDimensionScore->execute([
                $cycleId,
                $schoolId,
                $dimensionId,
                $rawScore,
                $maxScore,
                $percentage,
            ]);
        }

        $labels = array_merge(array_fill(0, 20, 1), array_fill(0, 20, 0));
        shuffle($labels);
        $noiseFlags = array_merge(array_fill(0, 10, true), array_fill(0, 30, false));
        shuffle($noiseFlags);
        for ($rowIndex = 0; $rowIndex < 40; $rowIndex++) {
            $label = $labels[$rowIndex];
            $noisy = $noiseFlags[$rowIndex];
            $actionable = $label === 1 ? !$noisy : $noisy;
            $dimension = $dimensions[$rowIndex % count($dimensions)];
            $dimensionId = (int) $dimension['dimension_id'];
            $dimensionIndicators = $dimension['indicators'];
            $indicator = $dimensionIndicators[
                ($rowIndex + $cycleIndex) % count($dimensionIndicators)
            ];
            $indicatorId = (int) $indicator['indicator_id'];
            $variant = ($cycleIndex * 40) + $rowIndex;
            [$title, $body] = demoSuggestionText(
                (string) $dimension['dimension_name'],
                $actionable,
                $variant
            );
            $title = '[CATBOOST DEMO] ' . $title;
            $body = DEMO_MARKER . ' - synthetic demo text; do not use as a real school recommendation.' . "\n"
                . $body;
            $generatedAt = $cycleTime;
            $payloadHash = hash(
                'sha256',
                DEMO_MARKER . '|generation|' . $cycleId . '|' . $rowIndex
            );
            $insertGeneration->execute([
                $schoolHeadId,
                $schoolId,
                $cycleId,
                $generatedAt,
                DEMO_BACKEND,
                $payloadHash,
            ]);
            $generationId = (int) $db->lastInsertId();
            $insertItem->execute([
                $generationId,
                $title,
                $body,
                json_encode(
                    [(string) $indicator['indicator_code']],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
                $generatedAt,
            ]);
            $itemId = (int) $db->lastInsertId();

            $workflowStatus = $label === 1 ? 'finalized' : 'returned';
            $priority = ['High', 'Medium', 'Low'][$variant % 3];
            $responsible = ['School Head', 'Teacher', 'SBM Coordinator'][$variant % 3];
            $targetDate = (new DateTimeImmutable('+ ' . (30 + ($variant % 150)) . ' days'))
                ->format('Y-m-d');
            $resources = $actionable
                ? 'CATBOOST DEMO: ' . ['materials', 'learning resources', 'partner support', 'budget'][$variant % 4]
                : 'CATBOOST DEMO: to be determined';
            $output = $actionable
                ? "CATBOOST DEMO: track $variant documented tasks and review progress."
                : 'CATBOOST DEMO: discuss possible next steps.';
            $insertPlan->execute([
                $schoolId,
                $cycleId,
                $dimensionId,
                $indicatorId,
                $priority,
                $title,
                $body,
                $responsible,
                $targetDate,
                $resources,
                $output,
                $workflowStatus === 'finalized' ? 'completed' : 'planned',
                $workflowStatus,
                DEMO_MARKER . ':plan:' . $cycleId . ':' . $rowIndex,
                $schoolHeadId,
                $schoolHeadId,
                $cycleTime,
                $workflowStatus === 'finalized' ? $schoolHeadId : null,
                $workflowStatus === 'finalized' ? $cycleTime : null,
                $workflowStatus === 'finalized' ? $schoolHeadId : null,
                $workflowStatus === 'finalized' ? $cycleTime : null,
                $itemId,
                $body,
            ]);
        }
    }

    $db->commit();
    echo json_encode([
        'ok' => true,
        'demo_data' => true,
        'seed' => DEMO_MARKER,
        'school_id' => $schoolId,
        'cycles' => 4,
        'suggestion_count' => 160,
        'finalized_count' => 80,
        'returned_count' => 80,
        'noise_count' => 40,
        'dimensions_used' => count($dimensions),
        'active_indicators_rated_per_cycle' => $indicatorCount,
        'dimension_scores_consistent_with_teacher_responses' => true,
        'note' => 'Synthetic test data only; ranker needs --include-demo to see these rows.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
