<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

$_SERVER['REQUEST_METHOD'] = 'CLI';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/assessment_scoring.php';
require_once __DIR__ . '/../includes/analytics_snapshots.php';
require_once __DIR__ . '/../includes/ml_service.php';

const HISTORICAL_SCHOOL_ID = 1;
const HISTORICAL_FIRST_YEAR = 2001;
const HISTORICAL_LAST_YEAR = 2025;
const HISTORICAL_MARKER = 'DEV_SEEDER: historical assessment seed';
const HISTORICAL_LOCK_NAME = 'sbm_historical_assessment_seed_school_1';
const HISTORICAL_CURRENT_LABEL = '2026-2027';
const HISTORICAL_CURRENT_START = '2026-06-08';
const HISTORICAL_CURRENT_END = '2027-04-08';
const HISTORICAL_QUALITY_PATH = [
    2001 => 62, 2002 => 58, 2003 => 63, 2004 => 68, 2005 => 68,
    2006 => 61, 2007 => 53, 2008 => 45, 2009 => 30, 2010 => 39,
    2011 => 47, 2012 => 55, 2013 => 62, 2014 => 68, 2015 => 68,
    2016 => 74, 2017 => 80, 2018 => 73, 2019 => 66, 2020 => 59,
    2021 => 64, 2022 => 72, 2023 => 80, 2024 => 76, 2025 => 84,
];
const HISTORICAL_ANOMALIES = [
    2004 => ['teacher_index' => 0, 'type' => 'all_ones'],
    2008 => ['teacher_index' => 1, 'type' => 'all_fours'],
    2012 => ['teacher_index' => 2, 'type' => 'constant'],
    2016 => ['teacher_index' => 3, 'type' => 'random'],
    2020 => ['teacher_index' => 4, 'type' => 'all_ones'],
    2024 => ['teacher_index' => 0, 'type' => 'all_fours'],
];

function historicalFail(string $message): never
{
    throw new RuntimeException($message);
}

function historicalRequireCount(array $rows, int $expected, string $description): void
{
    if (count($rows) !== $expected) {
        historicalFail("$description: expected $expected, found " . count($rows) . '.');
    }
}

function historicalTimestamp(string $date, string $time): string
{
    return "$date $time:00";
}

function historicalWeekday(string $date): string
{
    $day = new DateTimeImmutable($date);
    while ((int) $day->format('N') > 5) {
        $day = $day->modify('+1 day');
    }
    return $day->format('Y-m-d');
}

function historicalAddWeekdays(string $date, int $count): string
{
    $day = new DateTimeImmutable($date);
    for ($added = 0; $added < $count;) {
        $day = $day->modify('+1 day');
        if ((int) $day->format('N') <= 5) {
            $added++;
        }
    }
    return $day->format('Y-m-d');
}

function historicalYearDates(int $startYear): array
{
    $startDay = 5 + (($startYear * 7) % 12);
    $start = historicalWeekday(sprintf('%04d-06-%02d', $startYear, $startDay));
    $endCandidate = (new DateTimeImmutable(sprintf('%04d-03-27', $startYear + 1)))
        ->modify('+' . (($startYear * 5) % 18) . ' days')
        ->format('Y-m-d');

    return [$start, historicalWeekday($endCandidate)];
}

function historicalHashNumber(int $seed, int $userId, string $salt): int
{
    return (int) sprintf('%u', crc32("$seed:$userId:$salt"));
}

function historicalEvaluatorProfile(int $seed, int $userId): array
{
    return [
        'bias' => -0.34 + (historicalHashNumber($seed, $userId, 'strictness') % 1001) / 1000 * 0.68,
        'noise' => 0.16 + (historicalHashNumber($seed, $userId, 'noise') % 1001) / 1000 * 0.24,
        'spread' => 0.22 + (historicalHashNumber($seed, $userId, 'spread') % 1001) / 1000 * 0.26,
        'phase' => (historicalHashNumber($seed, $userId, 'phase') % 6283) / 1000,
    ];
}

function historicalDimensionOffsets(int $yearIndex): array
{
    $base = [-7.0, 5.0, 10.0, -9.0, 6.0, -5.0];
    $offsets = [];
    foreach ($base as $index => $value) {
        $offsets[$index + 1] = $value + sin(($yearIndex * 0.61) + (($index + 1) * 1.13)) * 3.2;
    }
    return $offsets;
}

function historicalSampleRating(
    float $quality,
    int $dimensionNo,
    int $yearIndex,
    array $profile
): int {
    $rating = ($quality + historicalDimensionOffsets($yearIndex)[$dimensionNo]) / 25;
    $drift = sin(($yearIndex * 0.31) + $profile['phase']) * 0.09;
    $noise = ((mt_rand(0, 10000) / 5000) - 1) * $profile['noise'];
    if ((mt_rand(0, 1000) / 1000) < $profile['spread']) {
        $noise += mt_rand(0, 1) === 0 ? -0.62 : 0.62;
    }

    return max(1, min(4, (int) round($rating + $profile['bias'] + $drift + $noise)));
}

function historicalRatingVectorHash(array $ratings): string
{
    ksort($ratings, SORT_NATURAL);
    $vector = [];
    foreach ($ratings as $code => $rating) {
        $vector[] = $code . ':' . $rating;
    }
    return hash('sha256', implode('|', $vector));
}

function historicalMakeRatings(
    array $codes,
    array $indicatorByCode,
    float $quality,
    int $yearIndex,
    array $profile,
    array $usedHashes = []
): array {
    for ($attempt = 0; $attempt < 800; $attempt++) {
        $ratings = [];
        foreach ($codes as $code) {
            $ratings[$code] = historicalSampleRating(
                $quality,
                (int) $indicatorByCode[$code]['dimension_no'],
                $yearIndex,
                $profile
            );
        }

        $hash = historicalRatingVectorHash($ratings);
        if (isset($usedHashes[$hash])) {
            continue;
        }
        return $ratings;
    }

    historicalFail('Could not generate a unique evaluator answer vector for school year ' . (HISTORICAL_FIRST_YEAR + $yearIndex) . '.');
}

function historicalAnomalyRatings(array $codes, string $type, int $year): array
{
    $ratings = [];
    $constantRating = $year % 2 === 0 ? 2 : 3;
    foreach ($codes as $code) {
        $ratings[$code] = match ($type) {
            'all_ones' => 1,
            'all_fours' => 4,
            'constant' => $constantRating,
            'random' => mt_rand(1, 4),
            default => throw new RuntimeException("Unknown anomaly type '$type'."),
        };
    }
    return $ratings;
}

function historicalNarrative(
    array $indicator,
    int $rating,
    int $year,
    int $evaluatorIndex,
    string $role,
    int $seed,
    string $field
): string {
    $teacherOpenings = [
        'During my classroom review',
        'In checking the records available to me',
        'From the practice I observed this year',
        'While reviewing the supporting materials',
        'In my day-to-day work with this indicator',
    ];
    $openings = $role === 'school_head'
        ? ['In my school-level review,', 'When I checked the school records,']
        : [$teacherOpenings[$evaluatorIndex]];
    $observations = [
        1 => [
            'I could not verify this practice in the materials I reviewed.',
            'The available records did not yet show this being carried out.',
            'I found little evidence that the practice had become established.',
            'The activity was not visible consistently in my review.',
        ],
        2 => [
            'I found some evidence, although implementation was uneven.',
            'The practice appeared in a few records but needs steadier follow-through.',
            'There were signs of progress, with important gaps still present.',
            'Some classes showed the practice, but it was not consistent yet.',
        ],
        3 => [
            'Most of the evidence showed the practice being carried out regularly.',
            'The records indicate an established approach with room to improve.',
            'I saw regular implementation across most of the reviewed material.',
            'The practice was generally evident during the review period.',
        ],
        4 => [
            'The reviewed evidence consistently confirmed this practice.',
            'Records showed sustained implementation across the school.',
            'I found clear evidence that the practice was routinely maintained.',
            'The evidence reflected consistent implementation throughout the year.',
        ],
    ];
    $followUps = [
        1 => [
            'A dated follow-up record would help establish the next steps.',
            'The team could identify an owner and collect evidence in the next review.',
        ],
        2 => [
            'A short monitoring check could help close the implementation gaps.',
            'The next review should compare the available records across classes.',
        ],
        3 => [
            'Periodic monitoring can help make the approach more even.',
            'Keeping the records current will support the next improvement cycle.',
        ],
        4 => [
            'Maintaining the documentation will help sustain this result.',
            'The team can share this practice while continuing routine monitoring.',
        ],
    ];
    $artifacts = preg_split('/[,;]+/', (string) ($indicator['mov_guide'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
    $artifacts = array_values(array_filter(array_map('trim', $artifacts ?: [])));
    $artifact = $artifacts
        ? $artifacts[historicalHashNumber($seed, $year, $field . ':' . $indicator['indicator_code'] . ':' . $evaluatorIndex) % count($artifacts)]
        : 'the available cycle records';
    $lead = $openings[$evaluatorIndex % count($openings)];
    $observationOptions = $observations[$rating];
    $followUpOptions = $followUps[$rating];
    $observation = $observationOptions[
        historicalHashNumber($seed, $year, $field . ':observation:' . $indicator['indicator_code'] . ':' . $evaluatorIndex)
            % count($observationOptions)
    ];
    $followUp = $followUpOptions[
        historicalHashNumber($seed, $year, $field . ':followup:' . $indicator['indicator_code'] . ':' . $evaluatorIndex)
            % count($followUpOptions)
    ];
    $goal = rtrim((string) $indicator['indicator_text'], " \t\n\r\0\x0B.");

    return "$lead indicator {$indicator['indicator_code']} on $goal. $observation The supporting material included $artifact. $followUp";
}

function historicalYearTimes(string $startDate, string $endDate, int $yearIndex, array $teachers): array
{
    $cycleStart = historicalWeekday((new DateTimeImmutable($startDate))->modify('+1 day')->format('Y-m-d'));
    $lastTeacherDate = (new DateTimeImmutable($endDate))->modify('-48 days')->format('Y-m-d');
    $firstTeacherDate = historicalWeekday($lastTeacherDate);
    $teacherTimes = [];
    foreach ($teachers as $index => $teacher) {
        $date = historicalAddWeekdays($firstTeacherDate, $index * 2);
        $teacherTimes[(int) $teacher['user_id']] = [
            'created_at' => historicalTimestamp($date, sprintf('08:%02d', 10 + (($yearIndex + $index) % 40))),
            'submitted_at' => historicalTimestamp($date, sprintf('14:%02d', 15 + (($yearIndex * 3 + $index) % 35))),
        ];
    }
    $lastTeacherSubmission = end($teacherTimes)['submitted_at'];
    $schoolHeadDate = historicalAddWeekdays(substr($lastTeacherSubmission, 0, 10), 2);
    $validatedDate = historicalAddWeekdays($schoolHeadDate, 2);
    $finalizedDate = historicalAddWeekdays($validatedDate, 1);
    if ($finalizedDate > $endDate) {
        historicalFail("Generated workflow dates exceed the $endDate school-year end.");
    }

    $schoolHeadSubmittedAt = historicalTimestamp($schoolHeadDate, '15:30');
    $validatedAt = historicalTimestamp($validatedDate, '09:30');
    $consolidatedAt = historicalTimestamp($validatedDate, '10:30');
    $finalizedAt = historicalTimestamp($finalizedDate, '10:15');

    return [
        'cycle_created_at' => historicalTimestamp($cycleStart, '08:15'),
        'cycle_started_at' => historicalTimestamp($cycleStart, '08:30'),
        'teacher_times' => $teacherTimes,
        'school_head_submitted_at' => $schoolHeadSubmittedAt,
        'validated_at' => $validatedAt,
        'consolidated_at' => $consolidatedAt,
        'finalized_at' => $finalizedAt,
        'dimension_computed_at' => historicalTimestamp($validatedDate, '09:45'),
        'snapshot_at' => $finalizedAt,
        'checkpoint_created_at' => historicalTimestamp($cycleStart, '08:45'),
    ];
}

function historicalOpenDatabase(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]);
    $db->exec("SET time_zone = '+08:00'");
    return $db;
}

function historicalLoadEvaluators(PDO $db): array
{
    $teachersStmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE role = 'teacher' AND status = 'active' AND school_id = ?
        ORDER BY user_id
    ");
    $teachersStmt->execute([HISTORICAL_SCHOOL_ID]);
    $teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($teachers, 5, 'Active teachers');

    $schoolHeadsStmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE role = 'school_head' AND status = 'active' AND school_id = ?
        ORDER BY user_id
    ");
    $schoolHeadsStmt->execute([HISTORICAL_SCHOOL_ID]);
    $schoolHeads = $schoolHeadsStmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($schoolHeads, 1, 'Active School Heads');
    $schoolHead = $schoolHeads[0];

    $coordinatorStmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE role = 'sbm_coordinator' AND status = 'active' AND school_id = ?
        ORDER BY user_id
        LIMIT 1
    ");
    $coordinatorStmt->execute([HISTORICAL_SCHOOL_ID]);
    $coordinatorId = $coordinatorStmt->fetchColumn();
    if ($coordinatorId === false) {
        historicalFail('No active SBM Coordinator exists to record cycle validation.');
    }

    $versionId = (int) $db->query(
        'SELECT version_id FROM form_versions WHERE is_active = 1 ORDER BY version_id DESC LIMIT 1'
    )->fetchColumn();
    if ($versionId < 1) {
        historicalFail('No active form version is available.');
    }
    $indicatorStmt = $db->prepare("
        SELECT i.indicator_id, i.indicator_code, i.indicator_text, i.mov_guide,
               i.dimension_id, d.dimension_no, d.dimension_name
        FROM sbm_indicators i
        JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
        WHERE i.form_version_id = ? AND i.is_active = 1
        ORDER BY d.dimension_no, i.sort_order, i.indicator_id
    ");
    $indicatorStmt->execute([$versionId]);
    $indicators = $indicatorStmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($indicators, 42, 'Active indicators in the current form version');
    $indicatorByCode = [];
    foreach ($indicators as $indicator) {
        $indicatorByCode[$indicator['indicator_code']] = $indicator;
    }

    $schoolHeadCodesStmt = $db->prepare("
        SELECT DISTINCT indicator_code
        FROM school_head_indicator_assignments
        WHERE user_id = ? AND cycle_id IS NULL
        ORDER BY indicator_code
    ");
    $schoolHeadCodesStmt->execute([(int) $schoolHead['user_id']]);
    $schoolHeadCodes = $schoolHeadCodesStmt->fetchAll(PDO::FETCH_COLUMN);
    historicalRequireCount($schoolHeadCodes, 42, 'Global School Head assignments');

    $teacherCodesStmt = $db->prepare("
        SELECT DISTINCT indicator_code
        FROM teacher_indicator_assignments
        WHERE teacher_id = ? AND cycle_id IS NULL
        ORDER BY indicator_code
    ");
    $teacherAssignments = [];
    foreach ($teachers as $teacher) {
        $teacherId = (int) $teacher['user_id'];
        $teacherCodesStmt->execute([$teacherId]);
        $codes = $teacherCodesStmt->fetchAll(PDO::FETCH_COLUMN);
        historicalRequireCount($codes, 21, "Global assignments for teacher $teacherId");
        $teacherAssignments[$teacherId] = $codes;
    }

    foreach (array_merge($schoolHeadCodes, ...array_values($teacherAssignments)) as $code) {
        if (!isset($indicatorByCode[$code])) {
            historicalFail("Assigned indicator '$code' is not in the active indicator form.");
        }
    }

    return [
        'school_head' => $schoolHead,
        'teachers' => $teachers,
        'coordinator_id' => (int) $coordinatorId,
        'version_id' => $versionId,
        'indicators' => $indicators,
        'indicator_by_code' => $indicatorByCode,
        'school_head_codes' => $schoolHeadCodes,
        'teacher_assignments' => $teacherAssignments,
    ];
}

function historicalEnsureGroundTruthTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS seed_ground_truth (
            ground_truth_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cycle_id INT NOT NULL,
            sy_id INT NOT NULL,
            school_year_label VARCHAR(20) NOT NULL,
            evaluator_id INT NOT NULL,
            anomaly_type VARCHAR(30) NOT NULL,
            is_preexisting TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (ground_truth_id),
            UNIQUE KEY uq_seed_ground_truth_cycle_evaluator (cycle_id, evaluator_id),
            KEY idx_seed_ground_truth_sy (sy_id, school_year_label)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function historicalRecordGroundTruth(
    PDO $db,
    int $cycleId,
    int $syId,
    string $label,
    int $teacherId,
    string $type,
    bool $preexisting,
    string $createdAt
): void {
    $db->prepare("
        INSERT INTO seed_ground_truth
            (cycle_id, sy_id, school_year_label, evaluator_id, anomaly_type, is_preexisting, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            sy_id = VALUES(sy_id),
            school_year_label = VALUES(school_year_label),
            anomaly_type = VALUES(anomaly_type),
            is_preexisting = VALUES(is_preexisting),
            created_at = VALUES(created_at)
    ")->execute([$cycleId, $syId, $label, $teacherId, $type, $preexisting ? 1 : 0, $createdAt]);
}

function historicalExistingSeedCycle(PDO $db, int $syId): ?array
{
    $cycleStmt = $db->prepare("
        SELECT cycle_id, status
        FROM sbm_cycles
        WHERE school_id = ? AND sy_id = ?
        ORDER BY cycle_id
        FOR UPDATE
    ");
    $cycleStmt->execute([HISTORICAL_SCHOOL_ID, $syId]);
    $cycles = $cycleStmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($cycles) > 1) {
        historicalFail("School year $syId has multiple assessment cycles; refusing to select one.");
    }
    if (!$cycles) {
        return null;
    }

    $cycle = $cycles[0];
    $markerStmt = $db->prepare("
        SELECT COUNT(*) FROM cycle_audit_log
        WHERE cycle_id = ? AND notes LIKE ?
    ");
    $markerStmt->execute([(int) $cycle['cycle_id'], HISTORICAL_MARKER . '%']);
    if ((int) $markerStmt->fetchColumn() < 1) {
        historicalFail("Cycle {$cycle['cycle_id']} is not marked as historical-seeder data; refusing to replace it.");
    }
    return $cycle;
}

function historicalAssertNoProtectedCycleData(PDO $db, int $cycleId): void
{
    $stmt = $db->prepare("
        SELECT
            (SELECT COUNT(*) FROM stakeholder_responses WHERE cycle_id = ?) +
            (SELECT COUNT(*) FROM stakeholder_submissions WHERE cycle_id = ?) +
            (SELECT COUNT(*) FROM improvement_plans WHERE cycle_id = ?) +
            (SELECT COUNT(*) FROM response_attachments WHERE cycle_id = ?) +
            (SELECT COUNT(*) FROM evidence_audit_log WHERE cycle_id = ?)
    ");
    $stmt->execute([$cycleId, $cycleId, $cycleId, $cycleId, $cycleId]);
    if ((int) $stmt->fetchColumn() > 0) {
        historicalFail("Seed cycle $cycleId contains stakeholder responses or improvement plans.");
    }

    $unexpectedAuditStmt = $db->prepare("
        SELECT COUNT(*) FROM cycle_audit_log
        WHERE cycle_id = ? AND notes NOT LIKE ?
    ");
    $unexpectedAuditStmt->execute([$cycleId, HISTORICAL_MARKER . '%']);
    if ((int) $unexpectedAuditStmt->fetchColumn() > 0) {
        historicalFail("Seed cycle $cycleId contains unmarked audit history; refusing to clear it.");
    }
}

function historicalClearSeedCycle(PDO $db, int $cycleId, int $syId): void
{
    historicalAssertNoProtectedCycleData($db, $cycleId);
    $checkpointStmt = $db->prepare("
        SELECT COUNT(*) FROM workflow_checkpoints
        WHERE school_id = ? AND sy_id = ? AND notes NOT LIKE ?
    ");
    $checkpointStmt->execute([HISTORICAL_SCHOOL_ID, $syId, HISTORICAL_MARKER . '%']);
    if ((int) $checkpointStmt->fetchColumn() > 0) {
        historicalFail("Seed school year $syId contains unmarked workflow checkpoints; refusing to clear it.");
    }
    $assignmentStmt = $db->prepare("
        SELECT
            (SELECT COUNT(*) FROM school_head_indicator_assignments WHERE cycle_id = ?) +
            (SELECT COUNT(*) FROM teacher_indicator_assignments WHERE cycle_id = ?)
    ");
    $assignmentStmt->execute([$cycleId, $cycleId]);
    if ((int) $assignmentStmt->fetchColumn() > 0) {
        historicalFail("Seed cycle $cycleId contains cycle-specific evaluator assignments.");
    }
    foreach ([
        'analytics_snapshots',
        'ml_training_snapshots',
        'ml_predictions',
        'ml_recommendations',
        'ml_comment_analysis',
        'sbm_dimension_scores',
        'sbm_responses',
        'teacher_responses',
        'teacher_submissions',
        'cycle_stage_gates',
        'cycle_audit_log',
    ] as $table) {
        $db->prepare("DELETE FROM `$table` WHERE cycle_id = ?")->execute([$cycleId]);
    }
    $db->prepare("
        DELETE FROM workflow_checkpoints
        WHERE school_id = ? AND sy_id = ? AND notes LIKE ?
    ")->execute([HISTORICAL_SCHOOL_ID, $syId, HISTORICAL_MARKER . '%']);
    $tableExists = (int) $db->query("
        SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'seed_ground_truth'
    ")->fetchColumn() > 0;
    if ($tableExists) {
        $db->prepare("
            DELETE FROM seed_ground_truth
            WHERE cycle_id = ? AND is_preexisting = 0
        ")->execute([$cycleId]);
    }
}

function historicalBuildCycleAnswers(
    array $evaluators,
    int $year,
    int $seed
): array {
    $yearIndex = $year - HISTORICAL_FIRST_YEAR;
    $quality = HISTORICAL_QUALITY_PATH[$year];
    $anomaly = HISTORICAL_ANOMALIES[$year] ?? null;
    $teacherIds = array_map(
        static fn(array $teacher): int => (int) $teacher['user_id'],
        $evaluators['teachers']
    );
    $anomalyTeacherId = $anomaly !== null
        ? $teacherIds[$anomaly['teacher_index']]
        : null;

    $allVectors = [];
    $usedHashes = [];
    $schoolHeadId = (int) $evaluators['school_head']['user_id'];
    $headProfile = historicalEvaluatorProfile($seed, $schoolHeadId);
    $allVectors[$schoolHeadId] = historicalMakeRatings(
        $evaluators['school_head_codes'],
        $evaluators['indicator_by_code'],
        $quality,
        $yearIndex,
        $headProfile
    );
    $usedHashes[historicalRatingVectorHash($allVectors[$schoolHeadId])] = true;

    foreach ($evaluators['teachers'] as $teacherIndex => $teacher) {
        $teacherId = (int) $teacher['user_id'];
        $codes = $evaluators['teacher_assignments'][$teacherId];
        if ($teacherId === $anomalyTeacherId) {
            $allVectors[$teacherId] = historicalAnomalyRatings($codes, $anomaly['type'], $year);
            continue;
        }

        $profile = historicalEvaluatorProfile($seed, $teacherId);
        $allVectors[$teacherId] = historicalMakeRatings(
            $codes,
            $evaluators['indicator_by_code'],
            $quality,
            $yearIndex,
            $profile,
            $usedHashes
        );
        $usedHashes[historicalRatingVectorHash($allVectors[$teacherId])] = true;
    }

    $remarks = [];
    $evidence = [];
    foreach ($evaluators['teachers'] as $index => $teacher) {
        $teacherId = (int) $teacher['user_id'];
        foreach ($allVectors[$teacherId] as $code => $rating) {
            $text = historicalNarrative(
                $evaluators['indicator_by_code'][$code],
                $rating,
                $year,
                $index,
                'teacher',
                $seed,
                'remark'
            );
            if (isset($remarks[$text])) {
                historicalFail("Repeated teacher remark generated for $year ($code).");
            }
            $remarks[$text] = true;
        }
    }
    foreach ($allVectors[$schoolHeadId] as $code => $rating) {
        $text = historicalNarrative(
            $evaluators['indicator_by_code'][$code],
            $rating,
            $year,
            0,
            'school_head',
            $seed,
            'evidence'
        );
        if (isset($evidence[$text])) {
            historicalFail("Repeated School Head evidence generated for $year ($code).");
        }
        $evidence[$text] = true;
    }

    return [
        'vectors' => $allVectors,
        'anomaly_teacher_id' => $anomalyTeacherId,
        'anomaly_type' => $anomaly['type'] ?? null,
    ];
}

function historicalPrepareYear(
    PDO $db,
    int $year,
    int $seed,
    array $evaluators,
    bool $execute
): array {
    $label = $year . '-' . ($year + 1);
    [$startDate, $endDate] = historicalYearDates($year);
    $yearStmt = $db->prepare('SELECT sy_id, date_start, date_end FROM school_years WHERE label = ? ORDER BY sy_id FOR UPDATE');
    $yearStmt->execute([$label]);
    $schoolYears = $yearStmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($schoolYears) > 1) {
        historicalFail("Multiple school-year rows are labeled $label.");
    }
    $schoolYear = $schoolYears[0] ?? null;

    if ($schoolYear) {
        $syId = (int) $schoolYear['sy_id'];
        $existingCycle = historicalExistingSeedCycle($db, $syId);
        if (!$existingCycle) {
            historicalFail("School year $label already exists without a marked synthetic cycle.");
        }
        $cycleId = (int) $existingCycle['cycle_id'];
        historicalClearSeedCycle($db, $cycleId, $syId);
        $db->prepare("
            UPDATE school_years
            SET date_start = ?, date_end = ?, is_current = 0, is_archived = 1
            WHERE sy_id = ?
        ")->execute([$startDate, $endDate, $syId]);
        $db->prepare("
            UPDATE sbm_cycles
            SET status = 'draft', overall_score = NULL, maturity_level = NULL,
                started_at = NULL, submitted_at = NULL, validated_at = NULL,
                validated_by = NULL, validator_remarks = NULL,
                consolidation_confirmed = 0, consolidation_confirmed_by = NULL,
                consolidation_confirmed_at = NULL, finalized_at = NULL,
                returned_at = NULL, returned_by = NULL, return_remarks = NULL,
                created_at = ?
            WHERE cycle_id = ?
        ")->execute([historicalTimestamp($startDate, '08:15'), $cycleId]);
    } else {
        $db->prepare("
            INSERT INTO school_years (label, is_current, date_start, date_end, is_archived)
            VALUES (?, 0, ?, ?, 1)
        ")->execute([$label, $startDate, $endDate]);
        $syId = (int) $db->lastInsertId();
        $cycleId = 0;
    }

    $times = historicalYearTimes($startDate, $endDate, $year - HISTORICAL_FIRST_YEAR, $evaluators['teachers']);
    if ($cycleId < 1) {
        $db->prepare("
            INSERT INTO sbm_cycles (sy_id, school_id, status, started_at, created_at)
            VALUES (?, ?, 'draft', ?, ?)
        ")->execute([
            $syId,
            HISTORICAL_SCHOOL_ID,
            $times['cycle_started_at'],
            $times['cycle_created_at'],
        ]);
        $cycleId = (int) $db->lastInsertId();
    } else {
        $db->prepare("
            UPDATE sbm_cycles
            SET started_at = ?, created_at = ?
            WHERE cycle_id = ?
        ")->execute([$times['cycle_started_at'], $times['cycle_created_at'], $cycleId]);
    }

    $answers = historicalBuildCycleAnswers($evaluators, $year, $seed);
    $teacherInsert = $db->prepare("
        INSERT INTO teacher_responses
            (cycle_id, indicator_id, school_id, teacher_id, rating, remarks, status,
             submitted_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?, ?, ?)
    ");
    $submissionInsert = $db->prepare("
        INSERT INTO teacher_submissions
            (cycle_id, school_id, sy_id, teacher_id, status, submitted_at, response_count)
        VALUES (?, ?, ?, ?, 'submitted', ?, ?)
    ");
    foreach ($evaluators['teachers'] as $teacherIndex => $teacher) {
        $teacherId = (int) $teacher['user_id'];
        $timesForTeacher = $times['teacher_times'][$teacherId];
        $responseCount = 0;
        foreach ($evaluators['teacher_assignments'][$teacherId] as $code) {
            $indicator = $evaluators['indicator_by_code'][$code];
            $rating = $answers['vectors'][$teacherId][$code];
            $remark = historicalNarrative(
                $indicator,
                $rating,
                $year,
                $teacherIndex,
                'teacher',
                $seed,
                'remark'
            );
            $teacherInsert->execute([
                $cycleId,
                (int) $indicator['indicator_id'],
                HISTORICAL_SCHOOL_ID,
                $teacherId,
                $rating,
                $remark,
                $timesForTeacher['submitted_at'],
                $timesForTeacher['created_at'],
                $timesForTeacher['submitted_at'],
            ]);
            $responseCount++;
        }
        $submissionInsert->execute([
            $cycleId,
            HISTORICAL_SCHOOL_ID,
            $syId,
            $teacherId,
            $timesForTeacher['submitted_at'],
            $responseCount,
        ]);
    }

    $schoolHeadId = (int) $evaluators['school_head']['user_id'];
    $schoolHeadInsert = $db->prepare("
        INSERT INTO sbm_responses
            (cycle_id, indicator_id, school_id, rating, evidence_text, rated_by, rated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($evaluators['school_head_codes'] as $index => $code) {
        $indicator = $evaluators['indicator_by_code'][$code];
        $schoolHeadInsert->execute([
            $cycleId,
            (int) $indicator['indicator_id'],
            HISTORICAL_SCHOOL_ID,
            $answers['vectors'][$schoolHeadId][$code],
            historicalNarrative(
                $indicator,
                $answers['vectors'][$schoolHeadId][$code],
                $year,
                0,
                'school_head',
                $seed,
                'evidence'
            ),
            $schoolHeadId,
            historicalTimestamp(substr($times['school_head_submitted_at'], 0, 10), sprintf('09:%02d', 10 + ($index % 45))),
        ]);
    }

    $dimensionsStmt = $db->prepare("
        SELECT dimension_id, dimension_no, dimension_name
        FROM sbm_dimensions
        WHERE form_version_id = ?
        ORDER BY dimension_no
    ");
    $dimensionsStmt->execute([$evaluators['version_id']]);
    $dimensions = $dimensionsStmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($dimensions, 6, 'Active dimensions');

    $dimensionTotals = [];
    $overallRaw = 0.0;
    $overallMax = 0.0;
    $dimensionInsert = $db->prepare("
        INSERT INTO sbm_dimension_scores
            (cycle_id, school_id, dimension_id, raw_score, max_score, percentage, computed_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($dimensions as $dimension) {
        $dimensionId = (int) $dimension['dimension_id'];
        $score = calculateSbmDimensionScore($db, $cycleId, $dimensionId, HISTORICAL_SCHOOL_ID);
        $dimensionTotals[] = [
            'dimension_id' => $dimensionId,
            'dimension_no' => (int) $dimension['dimension_no'],
            'dimension_name' => (string) $dimension['dimension_name'],
            'raw_score' => $score['raw_score'],
            'max_score' => $score['max_score'],
            'percentage' => $score['percentage'],
        ];
        $overallRaw += $score['raw_score'];
        $overallMax += $score['max_score'];
        $dimensionInsert->execute([
            $cycleId,
            HISTORICAL_SCHOOL_ID,
            $dimensionId,
            $score['raw_score'],
            $score['max_score'],
            $score['percentage'],
            $times['dimension_computed_at'],
        ]);
    }
    if ($overallMax <= 0) {
        historicalFail("Scoring produced no rated indicators for $label.");
    }
    $overallScore = round(($overallRaw / $overallMax) * 100, 2);
    $maturity = computeMaturity($overallScore);

    $db->prepare("
        UPDATE sbm_cycles
        SET status = 'finalized', overall_score = ?, maturity_level = ?,
            submitted_at = ?, validated_at = ?, validated_by = ?,
            validator_remarks = ?, consolidation_confirmed = 1,
            consolidation_confirmed_by = ?, consolidation_confirmed_at = ?,
            finalized_at = ?
        WHERE cycle_id = ?
    ")->execute([
        $overallScore,
        $maturity,
        $times['school_head_submitted_at'],
        $times['validated_at'],
        $evaluators['coordinator_id'],
        'Historical cycle validated for development data.',
        $evaluators['coordinator_id'],
        $times['consolidated_at'],
        $times['finalized_at'],
        $cycleId,
    ]);

    $auditInsert = $db->prepare("
        INSERT INTO cycle_audit_log (cycle_id, stage_from, stage_to, actor_id, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $auditRows = [
        ['draft', 'in_progress', $schoolHeadId, "$label cycle started.", $times['cycle_started_at']],
        ['in_progress', 'submitted', $schoolHeadId, 'All teacher and School Head responses submitted.', $times['school_head_submitted_at']],
        ['submitted', 'validated', $evaluators['coordinator_id'], 'Coordinator validated the cycle.', $times['validated_at']],
        ['validated', 'finalized', $evaluators['coordinator_id'], 'Cycle finalized.', $times['finalized_at']],
    ];
    foreach ($auditRows as [$from, $to, $actorId, $note, $createdAt]) {
        $auditInsert->execute([
            $cycleId,
            $from,
            $to,
            $actorId,
            HISTORICAL_MARKER . "; $note",
            $createdAt,
        ]);
    }
    $gateInsert = $db->prepare("
        INSERT INTO cycle_stage_gates
            (cycle_id, from_stage, to_stage, checked_at, checked_by, passed, blocker_details)
        VALUES (?, ?, ?, ?, ?, 1, '')
    ");
    foreach ([
        ['in_progress', 'submitted', $times['school_head_submitted_at'], $schoolHeadId],
        ['submitted', 'validated', $times['validated_at'], $evaluators['coordinator_id']],
        ['validated', 'finalized', $times['finalized_at'], $evaluators['coordinator_id']],
    ] as [$from, $to, $checkedAt, $checkedBy]) {
        $gateInsert->execute([$cycleId, $from, $to, $checkedAt, $checkedBy]);
    }

    $checkpointNote = HISTORICAL_MARKER . '; completed assessment cycle.';
    $db->prepare("
        INSERT INTO workflow_checkpoints
            (school_id, sy_id, phase_no, grading_period, cp_type, status, due_date,
             completed_at, completed_by, notes, created_at)
        VALUES (?, ?, 3, NULL, 'completion', 'done', ?, ?, ?, ?, ?)
    ")->execute([
        HISTORICAL_SCHOOL_ID,
        $syId,
        $endDate,
        $times['school_head_submitted_at'],
        $schoolHeadId,
        $checkpointNote,
        $times['checkpoint_created_at'],
    ]);

    saveAnalyticsSnapshotsForCycle($db, $cycleId, $times['snapshot_at']);
    $weakIndicators = [];
    $indicatorRatingStmt = $db->prepare("
        SELECT i.indicator_code, i.indicator_text, d.dimension_no, d.dimension_name,
               COALESCE(sr.rating, AVG(tr.rating)) AS rating,
               sr.evidence_text
        FROM sbm_indicators i
        JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
        LEFT JOIN sbm_responses sr
          ON sr.indicator_id = i.indicator_id AND sr.cycle_id = ?
        LEFT JOIN teacher_responses tr
          ON tr.indicator_id = i.indicator_id AND tr.cycle_id = ?
        WHERE i.form_version_id = ? AND i.is_active = 1
        GROUP BY i.indicator_id, i.indicator_code, i.indicator_text,
                 d.dimension_no, d.dimension_name, sr.rating, sr.evidence_text
        ORDER BY d.dimension_no, i.indicator_id
    ");
    $indicatorRatingStmt->execute([$cycleId, $cycleId, $evaluators['version_id']]);
    foreach ($indicatorRatingStmt->fetchAll(PDO::FETCH_ASSOC) as $indicatorRating) {
        $average = $indicatorRating['rating'];
        if ($average === null) {
            continue;
        }
        $weakIndicators[$indicatorRating['dimension_name']][] = [
            'code' => $indicatorRating['indicator_code'],
            'text' => $indicatorRating['indicator_text'],
            'dimension_no' => (int) $indicatorRating['dimension_no'],
            'dimension_name' => $indicatorRating['dimension_name'],
            'rating' => (float) $average,
            'evidence' => (string) ($indicatorRating['evidence_text'] ?? ''),
        ];
    }
    $mlResult = [
        'score_analysis' => [
            'gap_analysis' => ['all_dimensions' => $dimensionTotals],
            'weak_indicators' => ['by_dimension' => $weakIndicators],
        ],
    ];
    saveMLResults($db, $cycleId, $mlResult);
    $db->prepare('UPDATE ml_training_snapshots SET created_at = ? WHERE cycle_id = ?')
        ->execute([$times['snapshot_at'], $cycleId]);
    $trainingCount = $db->prepare('SELECT COUNT(*) FROM ml_training_snapshots WHERE cycle_id = ?');
    $trainingCount->execute([$cycleId]);
    if ((int) $trainingCount->fetchColumn() !== 1) {
        historicalFail("Production ML training snapshot was not saved for $label.");
    }

    if ($answers['anomaly_teacher_id'] !== null && $execute) {
        historicalRecordGroundTruth(
            $db,
            $cycleId,
            $syId,
            $label,
            $answers['anomaly_teacher_id'],
            (string) $answers['anomaly_type'],
            false,
            $times['finalized_at']
        );
    }

    return historicalVerifyCycle(
        $db,
        $cycleId,
        $syId,
        $label,
        $startDate,
        $endDate,
        $times,
        $evaluators,
        $answers
    );
}

function historicalVerifyCycle(
    PDO $db,
    int $cycleId,
    int $syId,
    string $label,
    string $startDate,
    string $endDate,
    array $times,
    array $evaluators,
    array $answers
): array {
    $cycleStmt = $db->prepare("
        SELECT status, overall_score, maturity_level, started_at, submitted_at,
               validated_at, consolidation_confirmed_at, finalized_at, created_at
        FROM sbm_cycles WHERE cycle_id = ? AND sy_id = ? AND school_id = ?
    ");
    $cycleStmt->execute([$cycleId, $syId, HISTORICAL_SCHOOL_ID]);
    $cycle = $cycleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$cycle || $cycle['status'] !== 'finalized') {
        historicalFail("$label cycle did not finalize.");
    }

    $headId = (int) $evaluators['school_head']['user_id'];
    $vectors = [];
    $headStmt = $db->prepare("
        SELECT i.indicator_code, r.rating, r.evidence_text, r.rated_at
        FROM sbm_responses r
        JOIN sbm_indicators i ON i.indicator_id = r.indicator_id
        WHERE r.cycle_id = ? AND r.rated_by = ?
        ORDER BY i.indicator_code
    ");
    $headStmt->execute([$cycleId, $headId]);
    $headRows = $headStmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($headRows, count($evaluators['school_head_codes']), "$label School Head answers");
    $vectors[$headId] = [];
    $textValues = [];
    $ratingRows = [$headId => []];
    foreach ($headRows as $row) {
        $vectors[$headId][$row['indicator_code']] = (int) $row['rating'];
        $ratingRows[$headId][] = (int) $row['rating'];
        $textValues[] = (string) $row['evidence_text'];
        historicalAssertTimestamp($row['rated_at'], $startDate, $endDate, "$label School Head response");
    }

    $teacherStmt = $db->prepare("
        SELECT i.indicator_code, tr.rating, tr.remarks, tr.created_at, tr.updated_at, tr.submitted_at
        FROM teacher_responses tr
        JOIN sbm_indicators i ON i.indicator_id = tr.indicator_id
        WHERE tr.cycle_id = ? AND tr.teacher_id = ?
        ORDER BY i.indicator_code
    ");
    foreach ($evaluators['teachers'] as $teacher) {
        $teacherId = (int) $teacher['user_id'];
        $teacherStmt->execute([$cycleId, $teacherId]);
        $rows = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);
        historicalRequireCount($rows, count($evaluators['teacher_assignments'][$teacherId]), "$label teacher $teacherId answers");
        $vectors[$teacherId] = [];
        $ratingRows[$teacherId] = [];
        foreach ($rows as $row) {
            $vectors[$teacherId][$row['indicator_code']] = (int) $row['rating'];
            $ratingRows[$teacherId][] = (int) $row['rating'];
            $textValues[] = (string) $row['remarks'];
            historicalAssertTimestamp($row['created_at'], $startDate, $endDate, "$label teacher response");
            historicalAssertTimestamp($row['updated_at'], $startDate, $endDate, "$label teacher response update");
            historicalAssertTimestamp($row['submitted_at'], $startDate, $endDate, "$label teacher submission");
        }
    }
    if (count(array_unique($textValues)) !== count($textValues)) {
        historicalFail("$label contains repeated evaluator remarks or evidence.");
    }

    $anomalyId = $answers['anomaly_teacher_id'];
    $vectorHashes = [];
    $usedNormalHashes = [];
    foreach ($vectors as $evaluatorId => $vector) {
        $hash = historicalRatingVectorHash($vector);
        $vectorHashes[$evaluatorId] = $hash;
        if ($evaluatorId !== $anomalyId) {
            if (isset($usedNormalHashes[$hash])) {
                historicalFail("$label has duplicate non-anomalous evaluator answer vectors.");
            }
            $usedNormalHashes[$hash] = true;
        }
    }

    $overlapDifference = [];
    $teacherDifferenceCount = 0;
    $teacherOverlapCount = 0;
    $teacherVectors = array_filter(
        $vectors,
        static fn(array $vector, int $id): bool => $id !== $headId,
        ARRAY_FILTER_USE_BOTH
    );
    $teacherIds = array_keys($teacherVectors);
    for ($left = 0; $left < count($teacherIds); $left++) {
        for ($right = $left + 1; $right < count($teacherIds); $right++) {
            $one = $teacherVectors[$teacherIds[$left]];
            $two = $teacherVectors[$teacherIds[$right]];
            $overlap = array_intersect_key($one, $two);
            $different = 0;
            foreach ($overlap as $code => $rating) {
                if ($rating !== $two[$code]) {
                    $different++;
                }
            }
            $teacherDifferenceCount += $different;
            $teacherOverlapCount += count($overlap);
            $overlapDifference[] = count($overlap) > 0 ? round($different / count($overlap) * 100, 1) : 100.0;
        }
    }

    foreach ([
        'created_at' => $cycle['created_at'],
        'started_at' => $cycle['started_at'],
        'submitted_at' => $cycle['submitted_at'],
        'validated_at' => $cycle['validated_at'],
        'consolidation_confirmed_at' => $cycle['consolidation_confirmed_at'],
        'finalized_at' => $cycle['finalized_at'],
    ] as $name => $timestamp) {
        historicalAssertTimestamp((string) $timestamp, $startDate, $endDate, "$label cycle $name");
    }
    $ordered = [
        $cycle['created_at'],
        $cycle['started_at'],
        $cycle['submitted_at'],
        $cycle['validated_at'],
        $cycle['consolidation_confirmed_at'],
        $cycle['finalized_at'],
    ];
    for ($i = 1; $i < count($ordered); $i++) {
        if (strtotime($ordered[$i]) <= strtotime($ordered[$i - 1])) {
            historicalFail("$label cycle timestamps are not strictly chronological.");
        }

        $submissionStmt = $db->prepare("
            SELECT submitted_at FROM teacher_submissions
            WHERE cycle_id = ? AND school_id = ? AND sy_id = ?
            ORDER BY submitted_at, teacher_id
        ");
        $submissionStmt->execute([$cycleId, HISTORICAL_SCHOOL_ID, $syId]);
        $teacherSubmissionTimes = $submissionStmt->fetchAll(PDO::FETCH_COLUMN);
        historicalRequireCount($teacherSubmissionTimes, count($evaluators['teachers']), "$label teacher submission records");
        foreach ($teacherSubmissionTimes as $submissionTime) {
            historicalAssertTimestamp((string) $submissionTime, $startDate, $endDate, "$label teacher submission record");
            if (strtotime((string) $submissionTime) >= strtotime($cycle['submitted_at'])) {
                historicalFail("$label School Head submitted before all teacher submissions were complete.");
            }
        }

        foreach ([
            'sbm_dimension_scores' => ['computed_at', 'dimension scoring'],
            'analytics_snapshots' => ['snapshot_at', 'analytics snapshot'],
            'ml_training_snapshots' => ['created_at', 'ML training snapshot'],
            'cycle_audit_log' => ['created_at', 'cycle audit'],
            'cycle_stage_gates' => ['checked_at', 'stage gate'],
        ] as $table => [$column, $description]) {
            $timestampsStmt = $db->prepare("SELECT `$column` FROM `$table` WHERE cycle_id = ?");
            $timestampsStmt->execute([$cycleId]);
            foreach ($timestampsStmt->fetchAll(PDO::FETCH_COLUMN) as $timestamp) {
                historicalAssertTimestamp((string) $timestamp, $startDate, $endDate, "$label $description");
            }
        }
        $checkpointStmt = $db->prepare("
            SELECT created_at, completed_at
            FROM workflow_checkpoints
            WHERE school_id = ? AND sy_id = ? AND notes LIKE ?
        ");
        $checkpointStmt->execute([HISTORICAL_SCHOOL_ID, $syId, HISTORICAL_MARKER . '%']);
        $checkpointRows = $checkpointStmt->fetchAll(PDO::FETCH_ASSOC);
        historicalRequireCount($checkpointRows, 1, "$label completion checkpoint");
        foreach (['created_at', 'completed_at'] as $column) {
            historicalAssertTimestamp((string) $checkpointRows[0][$column], $startDate, $endDate, "$label checkpoint $column");
        }
    }

    $dimStmt = $db->prepare("
        SELECT d.dimension_no, ds.percentage
        FROM sbm_dimension_scores ds
        JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
        WHERE ds.cycle_id = ?
        ORDER BY d.dimension_no
    ");
    $dimStmt->execute([$cycleId]);
    $dimensionScores = [];
    foreach ($dimStmt->fetchAll(PDO::FETCH_ASSOC) as $dimension) {
        $dimensionScores[(int) $dimension['dimension_no']] = (float) $dimension['percentage'];
    }
    historicalRequireCount($dimensionScores, 6, "$label dimension scores");

    $evaluatorStats = [];
    foreach ($ratingRows as $evaluatorId => $ratings) {
        $counts = array_fill_keys([1, 2, 3, 4], 0);
        foreach ($ratings as $rating) {
            $counts[$rating]++;
        }
        $average = array_sum($ratings) / count($ratings);
        $variance = array_sum(array_map(
            static fn(int $rating): float => ($rating - $average) ** 2,
            $ratings
        )) / count($ratings);
        $evaluatorStats[$evaluatorId] = [
            'hash' => $vectorHashes[$evaluatorId],
            'ratings' => $counts,
            'average' => round($average, 3),
            'spread' => round(sqrt($variance), 3),
        ];
    }

    return [
        'label' => $label,
        'cycle_id' => $cycleId,
        'overall_score' => (float) $cycle['overall_score'],
        'maturity' => (string) $cycle['maturity_level'],
        'finalized_at' => (string) $cycle['finalized_at'],
        'anomaly_teacher_id' => $anomalyId,
        'anomaly_type' => $answers['anomaly_type'],
        'hashes_unique' => count($usedNormalHashes) === count($vectors) - ($anomalyId !== null ? 1 : 0),
        'dimension_scores' => $dimensionScores,
        'evaluator_stats' => $evaluatorStats,
        'minimum_teacher_overlap_difference_pct' => $overlapDifference ? min($overlapDifference) : 100.0,
        'teacher_differences' => $teacherDifferenceCount,
        'teacher_overlaps' => $teacherOverlapCount,
        'timestamps_valid' => true,
    ];
}

function historicalAssertTimestamp(string $timestamp, string $startDate, string $endDate, string $description): void
{
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp);
    if (!$parsed) {
        historicalFail("$description has an invalid timestamp: $timestamp.");
    }
    $date = $parsed->format('Y-m-d');
    $hour = (int) $parsed->format('G');
    if ($date < $startDate || $date > $endDate || (int) $parsed->format('N') > 5 || $hour < 8 || $hour >= 17) {
        historicalFail("$description timestamp is outside the school-year weekday working window: $timestamp.");
    }
}

function historicalSeedYear(PDO $db, int $year, int $seed, array $evaluators, bool $execute): array
{
    $db->beginTransaction();
    try {
        $result = historicalPrepareYear($db, $year, $seed, $evaluators, $execute);
        if ($execute) {
            $db->commit();
            $result['committed'] = true;
        } else {
            $db->rollBack();
            $result['committed'] = false;
        }
        return $result;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

function historicalTeacherDiversityPercentage(array $results): float
{
    $different = 0;
    $overlap = 0;
    foreach ($results as $result) {
        $different += (int) $result['teacher_differences'];
        $overlap += (int) $result['teacher_overlaps'];
    }
    return $overlap > 0 ? round($different / $overlap * 100, 2) : 0.0;
}

function historicalEnsureCurrentYear(PDO $db, bool $execute): void
{
    $db->beginTransaction();
    try {
        $currentStmt = $db->prepare("
            SELECT sy_id, date_start, date_end
            FROM school_years
            WHERE label = ?
            ORDER BY sy_id
            FOR UPDATE
        ");
        $currentStmt->execute([HISTORICAL_CURRENT_LABEL]);
        $currentYears = $currentStmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($currentYears) > 1) {
            historicalFail('Multiple 2026-2027 school-year rows exist.');
        }
        $currentYear = $currentYears[0] ?? null;
        if ($currentYear) {
            if (
                $currentYear['date_start'] !== HISTORICAL_CURRENT_START
                || $currentYear['date_end'] !== HISTORICAL_CURRENT_END
            ) {
                historicalFail('Existing 2026-2027 dates differ from the requested current-year dates.');
            }
            $cycleStmt = $db->prepare('SELECT COUNT(*) FROM sbm_cycles WHERE sy_id = ? AND school_id = ?');
            $cycleStmt->execute([(int) $currentYear['sy_id'], HISTORICAL_SCHOOL_ID]);
            if ((int) $cycleStmt->fetchColumn() > 0) {
                historicalFail('2026-2027 already has a cycle; refusing to alter its answers or status.');
            }
        } else {
            $db->prepare("
                INSERT INTO school_years (label, is_current, date_start, date_end, is_archived)
                VALUES (?, 0, ?, ?, 0)
            ")->execute([
                HISTORICAL_CURRENT_LABEL,
                HISTORICAL_CURRENT_START,
                HISTORICAL_CURRENT_END,
            ]);
            $currentYear = ['sy_id' => (int) $db->lastInsertId()];
        }

        $db->exec('UPDATE school_years SET is_current = 0');
        $db->prepare("
            UPDATE school_years
            SET is_current = 1, is_archived = 0
            WHERE sy_id = ?
        ")->execute([(int) $currentYear['sy_id']]);
        $currentCount = (int) $db->query('SELECT COUNT(*) FROM school_years WHERE is_current = 1')->fetchColumn();
        if ($currentCount !== 1) {
            historicalFail("Expected exactly one current school year, found $currentCount.");
        }

        if ($execute) {
            $db->commit();
        } else {
            $db->rollBack();
        }
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

function historicalLoadBaseline(PDO $db, array $evaluators): array
{
    $stmt = $db->prepare("
        SELECT c.cycle_id, c.sy_id, c.status, c.overall_score, c.maturity_level,
               c.started_at, c.submitted_at, c.validated_at,
               c.consolidation_confirmed_at, c.finalized_at, c.created_at,
               sy.label, sy.date_start, sy.date_end
        FROM sbm_cycles c
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE sy.label = '2000-2001' AND c.school_id = ?
        ORDER BY c.cycle_id
    ");
    $stmt->execute([HISTORICAL_SCHOOL_ID]);
    $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($cycles, 1, 'Protected 2000-2001 baseline cycle');
    $cycle = $cycles[0];
    if ($cycle['label'] !== '2000-2001' || $cycle['status'] !== 'finalized') {
        historicalFail('The protected 2000-2001 baseline cycle was not found in finalized state.');
    }
    $cycleId = (int) $cycle['cycle_id'];
    $headId = (int) $evaluators['school_head']['user_id'];
    $vectors = [];
    $ratingRows = [];
    $headStmt = $db->prepare("
        SELECT i.indicator_code, r.rating, r.rated_at
        FROM sbm_responses r
        JOIN sbm_indicators i ON i.indicator_id = r.indicator_id
        WHERE r.cycle_id = ? AND r.rated_by = ?
        ORDER BY i.indicator_code
    ");
    $headStmt->execute([$cycleId, $headId]);
    $headRows = $headStmt->fetchAll(PDO::FETCH_ASSOC);
    historicalRequireCount($headRows, count($evaluators['school_head_codes']), '2000-2001 School Head answers');
    $vectors[$headId] = [];
    $ratingRows[$headId] = [];
    foreach ($headRows as $row) {
        $vectors[$headId][$row['indicator_code']] = (int) $row['rating'];
        $ratingRows[$headId][] = (int) $row['rating'];
        historicalAssertTimestamp($row['rated_at'], (string) $cycle['date_start'], (string) $cycle['date_end'], '2000-2001 School Head response');
    }

    $anomalyTeacherId = null;
    $teacherStmt = $db->prepare("
        SELECT i.indicator_code, tr.rating, tr.created_at, tr.updated_at, tr.submitted_at
        FROM teacher_responses tr
        JOIN sbm_indicators i ON i.indicator_id = tr.indicator_id
        WHERE tr.cycle_id = ? AND tr.teacher_id = ?
        ORDER BY i.indicator_code
    ");
    foreach ($evaluators['teachers'] as $teacher) {
        $teacherId = (int) $teacher['user_id'];
        $teacherStmt->execute([$cycleId, $teacherId]);
        $rows = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);
        historicalRequireCount($rows, count($evaluators['teacher_assignments'][$teacherId]), "2000-2001 teacher $teacherId answers");
        $vectors[$teacherId] = [];
        $ratingRows[$teacherId] = [];
        foreach ($rows as $row) {
            $vectors[$teacherId][$row['indicator_code']] = (int) $row['rating'];
            $ratingRows[$teacherId][] = (int) $row['rating'];
            historicalAssertTimestamp($row['created_at'], (string) $cycle['date_start'], (string) $cycle['date_end'], '2000-2001 teacher response');
            historicalAssertTimestamp($row['updated_at'], (string) $cycle['date_start'], (string) $cycle['date_end'], '2000-2001 teacher response update');
            historicalAssertTimestamp($row['submitted_at'], (string) $cycle['date_start'], (string) $cycle['date_end'], '2000-2001 teacher submission');
        }
        if (count(array_unique($ratingRows[$teacherId])) === 1) {
            if ($anomalyTeacherId !== null) {
                historicalFail('2000-2001 has more than one uniform-rating teacher; baseline anomaly is ambiguous.');
            }
            $anomalyTeacherId = $teacherId;
        }
    }
    if ($anomalyTeacherId === null || min($ratingRows[$anomalyTeacherId]) !== 1) {
        historicalFail('2000-2001 legacy all-ones teacher anomaly was not found as expected.');
    }

    foreach ([
        'created_at' => $cycle['created_at'],
        'started_at' => $cycle['started_at'],
        'submitted_at' => $cycle['submitted_at'],
        'validated_at' => $cycle['validated_at'],
        'consolidation_confirmed_at' => $cycle['consolidation_confirmed_at'],
        'finalized_at' => $cycle['finalized_at'],
    ] as $name => $timestamp) {
        historicalAssertTimestamp((string) $timestamp, (string) $cycle['date_start'], (string) $cycle['date_end'], "2000-2001 cycle $name");
    }
    $ordered = [
        $cycle['created_at'],
        $cycle['started_at'],
        $cycle['submitted_at'],
        $cycle['validated_at'],
        $cycle['consolidation_confirmed_at'],
        $cycle['finalized_at'],
    ];
    for ($i = 1; $i < count($ordered); $i++) {
        if (strtotime($ordered[$i]) <= strtotime($ordered[$i - 1])) {
            historicalFail('2000-2001 baseline timestamps are not strictly chronological.');
        }
    }
    $baselineSubmissions = $db->prepare("
        SELECT submitted_at FROM teacher_submissions
        WHERE cycle_id = ? AND school_id = ? AND sy_id = ?
    ");
    $baselineSubmissions->execute([$cycleId, HISTORICAL_SCHOOL_ID, (int) $cycle['sy_id']]);
    $baselineSubmissionTimes = $baselineSubmissions->fetchAll(PDO::FETCH_COLUMN);
    historicalRequireCount($baselineSubmissionTimes, 5, '2000-2001 teacher submissions');
    foreach ($baselineSubmissionTimes as $submissionTime) {
        historicalAssertTimestamp(
            (string) $submissionTime,
            (string) $cycle['date_start'],
            (string) $cycle['date_end'],
            '2000-2001 teacher submission record'
        );
        if (strtotime((string) $submissionTime) >= strtotime($cycle['submitted_at'])) {
            historicalFail('2000-2001 School Head submitted before all teacher submissions were complete.');
        }
    }
    foreach ([
        'sbm_dimension_scores' => ['computed_at', 'dimension score'],
        'analytics_snapshots' => ['snapshot_at', 'analytics snapshot'],
        'ml_training_snapshots' => ['created_at', 'ML training snapshot'],
        'cycle_audit_log' => ['created_at', 'cycle audit'],
        'cycle_stage_gates' => ['checked_at', 'stage gate'],
    ] as $table => [$column, $description]) {
        $timestamps = $db->prepare("SELECT `$column` FROM `$table` WHERE cycle_id = ?");
        $timestamps->execute([$cycleId]);
        foreach ($timestamps->fetchAll(PDO::FETCH_COLUMN) as $timestamp) {
            historicalAssertTimestamp(
                (string) $timestamp,
                (string) $cycle['date_start'],
                (string) $cycle['date_end'],
                "2000-2001 $description"
            );
        }
    }
    $baselineCheckpoints = $db->prepare("
        SELECT created_at, completed_at
        FROM workflow_checkpoints
        WHERE school_id = ? AND sy_id = ?
    ");
    $baselineCheckpoints->execute([HISTORICAL_SCHOOL_ID, (int) $cycle['sy_id']]);
    foreach ($baselineCheckpoints->fetchAll(PDO::FETCH_ASSOC) as $checkpoint) {
        foreach (['created_at', 'completed_at'] as $column) {
            if ($checkpoint[$column] !== null) {
                historicalAssertTimestamp(
                    (string) $checkpoint[$column],
                    (string) $cycle['date_start'],
                    (string) $cycle['date_end'],
                    "2000-2001 checkpoint $column"
                );
            }
        }
    }

    $stats = [];
    $hashes = [];
    foreach ($ratingRows as $evaluatorId => $ratings) {
        $counts = array_fill_keys([1, 2, 3, 4], 0);
        foreach ($ratings as $rating) {
            $counts[$rating]++;
        }
        $average = array_sum($ratings) / count($ratings);
        $variance = array_sum(array_map(
            static fn(int $rating): float => ($rating - $average) ** 2,
            $ratings
        )) / count($ratings);
        $hashes[$evaluatorId] = historicalRatingVectorHash($vectors[$evaluatorId]);
        $stats[$evaluatorId] = [
            'hash' => $hashes[$evaluatorId],
            'ratings' => $counts,
            'average' => round($average, 3),
            'spread' => round(sqrt($variance), 3),
        ];
    }
    $nonAnomalousHashes = [];
    foreach ($hashes as $evaluatorId => $hash) {
        if ($evaluatorId !== $anomalyTeacherId) {
            $nonAnomalousHashes[$hash] = true;
        }
    }
    $baselineTeacherIds = array_values(array_filter(
        array_keys($vectors),
        static fn(int $id): bool => $id !== $headId
    ));
    $teacherDifferences = 0;
    $teacherOverlaps = 0;
    for ($left = 0; $left < count($baselineTeacherIds); $left++) {
        for ($right = $left + 1; $right < count($baselineTeacherIds); $right++) {
            $one = $vectors[$baselineTeacherIds[$left]];
            $two = $vectors[$baselineTeacherIds[$right]];
            $overlap = array_intersect_key($one, $two);
            foreach ($overlap as $code => $rating) {
                if ($rating !== $two[$code]) {
                    $teacherDifferences++;
                }
            }
            $teacherOverlaps += count($overlap);
        }
    }
    $dimensionStmt = $db->prepare("
        SELECT d.dimension_no, ds.percentage
        FROM sbm_dimension_scores ds
        JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
        WHERE ds.cycle_id = ?
        ORDER BY d.dimension_no
    ");
    $dimensionStmt->execute([$cycleId]);
    $dimensionScores = [];
    foreach ($dimensionStmt->fetchAll(PDO::FETCH_ASSOC) as $dimension) {
        $dimensionScores[(int) $dimension['dimension_no']] = (float) $dimension['percentage'];
    }
    historicalRequireCount($dimensionScores, 6, '2000-2001 dimension scores');

    return [
        'label' => '2000-2001',
        'cycle_id' => $cycleId,
        'sy_id' => (int) $cycle['sy_id'],
        'overall_score' => (float) $cycle['overall_score'],
        'maturity' => (string) $cycle['maturity_level'],
        'finalized_at' => (string) $cycle['finalized_at'],
        'anomaly_teacher_id' => $anomalyTeacherId,
        'anomaly_type' => 'all_ones (pre-existing)',
        'hashes_unique' => count($nonAnomalousHashes) === count($vectors) - 1,
        'dimension_scores' => $dimensionScores,
        'evaluator_stats' => $stats,
        'minimum_teacher_overlap_difference_pct' => null,
        'teacher_differences' => $teacherDifferences,
        'teacher_overlaps' => $teacherOverlaps,
        'timestamps_valid' => true,
    ];
}

function historicalWriteBaselineGroundTruth(PDO $db, array $baseline, array $evaluators, bool $execute): void
{
    if (!$execute) {
        return;
    }
    historicalEnsureGroundTruthTable($db);
    $db->beginTransaction();
    try {
        historicalRecordGroundTruth(
            $db,
            (int) $baseline['cycle_id'],
            (int) $baseline['sy_id'],
            '2000-2001',
            (int) $baseline['anomaly_teacher_id'],
            'all_ones',
            true,
            (string) $baseline['finalized_at']
        );
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

function historicalPrintReport(array $results, array $evaluators, int $seed, bool $execute): void
{
    echo ($execute ? "PHASE 1 COMMITTED DATA SUMMARY\n" : "PHASE 1 DRY-RUN REPORT (transactions rolled back)\n");
    echo "Seed: $seed; synthetic school: " . HISTORICAL_SCHOOL_ID . "; new years: "
        . HISTORICAL_FIRST_YEAR . '-' . HISTORICAL_LAST_YEAR . ".\n";
    echo "School Head: 1; teachers: 5; active indicators: 42; global assignments unchanged.\n";
    echo "Baseline 2000-2001 is reported from existing cycle 39; its answers, scores, and history were not rewritten.\n\n";

    echo "YEAR SUMMARY\n";
    echo "School year | Overall | Maturity | Finalized (Manila) | Anomaly | Unique non-anomaly vectors | Timestamps\n";
    foreach ($results as $result) {
        $anomaly = $result['anomaly_teacher_id'] === null
            ? 'none'
            : 'Teacher ' . $result['anomaly_teacher_id'] . ' (' . $result['anomaly_type'] . ')';
        printf(
            "%s | %.2f%% | %s | %s | %s | %s | %s\n",
            $result['label'],
            $result['overall_score'],
            $result['maturity'],
            $result['finalized_at'],
            $anomaly,
            $result['hashes_unique'] ? 'PASS' : 'FAIL',
            $result['timestamps_valid'] ? 'PASS' : 'FAIL'
        );
    }

    echo "\nDIMENSION PERCENTAGES (D1-D6)\n";
    foreach ($results as $result) {
        if (empty($result['dimension_scores'])) {
            continue;
        }
        $scores = [];
        foreach ($result['dimension_scores'] as $dimensionNo => $percentage) {
            $scores[] = "D$dimensionNo=" . number_format((float) $percentage, 2);
        }
        echo $result['label'] . ' | ' . implode(' | ', $scores) . "\n";
    }

    echo "\nEVALUATOR ANSWER HASHES, DISTRIBUTIONS, AVERAGE AND POPULATION SPREAD\n";
    echo "Year | Evaluator | SHA-256 vector | R1 | R2 | R3 | R4 | Average | Spread\n";
    $evaluatorLabels = [(int) $evaluators['school_head']['user_id'] => 'School Head'];
    foreach ($evaluators['teachers'] as $index => $teacher) {
        $evaluatorLabels[(int) $teacher['user_id']] = 'Teacher ' . ($index + 1);
    }
    foreach ($results as $result) {
        foreach ($result['evaluator_stats'] as $evaluatorId => $stats) {
            printf(
                "%s | %s | %s | %d | %d | %d | %d | %.3f | %.3f\n",
                $result['label'],
                $evaluatorLabels[(int) $evaluatorId] ?? ('Evaluator ' . $evaluatorId),
                $stats['hash'],
                $stats['ratings'][1],
                $stats['ratings'][2],
                $stats['ratings'][3],
                $stats['ratings'][4],
                $stats['average'],
                $stats['spread']
            );
        }
    }
    echo "\nPERSISTENT EVALUATOR PROFILES (bias/noise/spread tendency; drift is smooth across years)\n";
    echo "Evaluator | Strictness bias | Noise amplitude | Spread tendency\n";
    foreach ($evaluatorLabels as $evaluatorId => $label) {
        $profile = historicalEvaluatorProfile($seed, (int) $evaluatorId);
        printf(
            "%s | %+.3f | %.3f | %.3f\n",
            $label,
            $profile['bias'],
            $profile['noise'],
            $profile['spread']
        );
    }
    echo "\nEach non-anomalous vector hash is unique within its cycle. Affected anomaly teacher-years are excluded from that check.\n";
    echo "Generated-year remarks and evidence are unique per evaluator-cycle; legacy 2000-2001 text was preserved unchanged.\n";
    echo 'Teacher-to-teacher ratings differ on ' . number_format(historicalTeacherDiversityPercentage($results), 2)
        . "% of shared indicator comparisons across all 26 reported years.\n";
    echo "New anomalies: " . count(HISTORICAL_ANOMALIES) . ' of ' . (HISTORICAL_LAST_YEAR - HISTORICAL_FIRST_YEAR + 1)
        . " years (" . number_format(count(HISTORICAL_ANOMALIES) / (HISTORICAL_LAST_YEAR - HISTORICAL_FIRST_YEAR + 1) * 100, 1)
        . "%). Ground-truth metadata is written outside the AI payload.\n";
    echo "2026-2027: planned current, unarchived, no cycle or answers.\n";
    echo $execute
        ? "All listed historical years were committed in one transaction per year.\n"
        : "No seed rows or school-year changes were committed; 2026-2027 current-year changes were rolled back.\n";
}

$execute = false;
$seed = 2001;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--execute') {
        $execute = true;
    } elseif (preg_match('/^--seed=(\d+)$/', $argument, $matches)) {
        $seed = (int) $matches[1];
    } elseif ($argument === '--help') {
        echo "Usage: php tools/seed_historical_assessments.php [--seed=INTEGER] [--execute]\n";
        echo "Seeds synthetic cycles for 2001-2002 through 2025-2026. Dry run is the default.\n";
        exit(0);
    } else {
        fwrite(STDERR, "Unknown argument: $argument\n");
        exit(2);
    }
}
mt_srand($seed);

try {
    $db = historicalOpenDatabase();
    $lockStmt = $db->prepare('SELECT GET_LOCK(?, 0)');
    $lockStmt->execute([HISTORICAL_LOCK_NAME]);
    if ((int) $lockStmt->fetchColumn() !== 1) {
        historicalFail('Another historical assessment seed is already running.');
    }
    try {
        $evaluators = historicalLoadEvaluators($db);
        $baseline = historicalLoadBaseline($db, $evaluators);
        $results = [$baseline];

        if ($execute) {
            historicalEnsureGroundTruthTable($db);
        }
        for ($year = HISTORICAL_FIRST_YEAR; $year <= HISTORICAL_LAST_YEAR; $year++) {
            $results[] = historicalSeedYear($db, $year, $seed, $evaluators, $execute);
        }

        $teacherDiversity = historicalTeacherDiversityPercentage($results);
        if ($teacherDiversity <= 50.0) {
            historicalFail('Teacher ratings differ on only ' . number_format($teacherDiversity, 2) . '% of shared indicators; expected a majority.');
        }
        historicalEnsureCurrentYear($db, $execute);
        historicalWriteBaselineGroundTruth($db, $baseline, $evaluators, $execute);

        historicalPrintReport($results, $evaluators, $seed, $execute);
    } finally {
        $db->prepare('SELECT RELEASE_LOCK(?)')->execute([HISTORICAL_LOCK_NAME]);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Historical assessment seeder failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
