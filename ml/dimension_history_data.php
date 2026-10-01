<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
require_once __DIR__ . '/../config/db.php';

/**
 * Read-only training-data bridge for the dimension improvement model.
 *
 * Each row describes one finalized school-cycle/dimension observation. All
 * feature fields are calculated from that cycle and earlier cycles only.
 * The next cycle's dimension score is exported separately as the target.
 */
$db = getDB();

$cycleRows = $db->query("
    SELECT
        c.cycle_id,
        c.school_id,
        c.sy_id,
        sy.label AS school_year_label,
        CAST(LEFT(sy.label, 4) AS UNSIGNED) AS school_year_start,
        CAST(SUBSTRING_INDEX(sy.label, '-', -1) AS UNSIGNED) AS school_year_end,
        COALESCE(c.finalized_at, c.validated_at, c.submitted_at, c.created_at) AS cycle_order
    FROM sbm_cycles c
    JOIN school_years sy ON sy.sy_id = c.sy_id
    WHERE c.status = 'finalized'
    ORDER BY
        c.school_id,
        school_year_start,
        school_year_end,
        c.cycle_id
")->fetchAll(PDO::FETCH_ASSOC);

$scoreRows = $db->query("
    SELECT
        ds.cycle_id,
        ds.school_id,
        d.dimension_id,
        d.dimension_no,
        d.dimension_name,
        ds.percentage
    FROM sbm_dimension_scores ds
    JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
    JOIN sbm_cycles c ON c.cycle_id = ds.cycle_id
    WHERE c.status = 'finalized'
    ORDER BY ds.school_id, ds.cycle_id, d.dimension_no
")->fetchAll(PDO::FETCH_ASSOC);

$indicatorRows = $db->query("
    SELECT
        r.cycle_id,
        r.school_id,
        i.dimension_id,
        i.indicator_id,
        i.indicator_code,
        AVG(r.rating) AS school_head_rating,
        COUNT(*) AS school_head_rating_count
    FROM sbm_responses r
    JOIN sbm_indicators i ON i.indicator_id = r.indicator_id
    JOIN sbm_cycles c ON c.cycle_id = r.cycle_id
    WHERE c.status = 'finalized'
    GROUP BY
        r.cycle_id, r.school_id, i.dimension_id,
        i.indicator_id, i.indicator_code
    ORDER BY r.cycle_id, i.dimension_id, i.indicator_id
")->fetchAll(PDO::FETCH_ASSOC);

$teacherRows = $db->query("
    SELECT
        tr.cycle_id,
        tr.school_id,
        i.dimension_id,
        i.indicator_id,
        i.indicator_code,
        AVG(tr.rating) AS teacher_rating,
        COUNT(DISTINCT tr.teacher_id) AS teacher_count
    FROM teacher_responses tr
    JOIN sbm_indicators i ON i.indicator_id = tr.indicator_id
    JOIN sbm_cycles c ON c.cycle_id = tr.cycle_id
    WHERE c.status = 'finalized'
      AND tr.status = 'submitted'
    GROUP BY
        tr.cycle_id, tr.school_id, i.dimension_id,
        i.indicator_id, i.indicator_code
    ORDER BY tr.cycle_id, i.dimension_id, i.indicator_id
")->fetchAll(PDO::FETCH_ASSOC);

$duplicateFinalizedCycles = [];
$cycleCandidatesBySchoolYear = [];
foreach ($cycleRows as $cycle) {
    $schoolYearKey = (int) $cycle['school_id'] . ':' . (int) $cycle['sy_id'];
    $cycleCandidatesBySchoolYear[$schoolYearKey][] = $cycle;
}

$deduplicatedCycleRows = [];
foreach ($cycleCandidatesBySchoolYear as $schoolYearKey => $candidates) {
    usort(
        $candidates,
        static fn(array $left, array $right): int =>
            (int) $left['cycle_id'] <=> (int) $right['cycle_id']
    );
    if (count($candidates) > 1) {
        $duplicateFinalizedCycles[] = [
            'school_id' => (int) $candidates[0]['school_id'],
            'school_year_id' => (int) $candidates[0]['sy_id'],
            'school_year_label' => (string) $candidates[0]['school_year_label'],
            'cycle_ids' => array_map(
                static fn(array $cycle): int => (int) $cycle['cycle_id'],
                $candidates
            ),
            'kept_cycle_id' => (int) $candidates[count($candidates) - 1]['cycle_id'],
        ];
    }
    $deduplicatedCycleRows[] = $candidates[count($candidates) - 1];
}

usort($deduplicatedCycleRows, static function (array $left, array $right): int {
    foreach (['school_id', 'school_year_start', 'school_year_end', 'cycle_id'] as $field) {
        $comparison = (int) $left[$field] <=> (int) $right[$field];
        if ($comparison !== 0) {
            return $comparison;
        }
    }
    return 0;
});

$cycleRows = $deduplicatedCycleRows;
$cycleById = [];
foreach ($cycleRows as $cycle) {
    $cycleById[(int) $cycle['cycle_id']] = [
        'cycle_id' => (int) $cycle['cycle_id'],
        'school_id' => (int) $cycle['school_id'],
        'sy_id' => (int) $cycle['sy_id'],
        'school_year_label' => (string) $cycle['school_year_label'],
        'school_year_start' => (int) $cycle['school_year_start'],
        'school_year_end' => (int) $cycle['school_year_end'],
        'cycle_order' => (string) $cycle['cycle_order'],
    ];
}

$scoreByCycleDimension = [];
foreach ($scoreRows as $score) {
    $cycleId = (int) $score['cycle_id'];
    $dimensionId = (int) $score['dimension_id'];
    $scoreByCycleDimension[$cycleId][$dimensionId] = [
        'dimension_id' => $dimensionId,
        'dimension_no' => (int) $score['dimension_no'],
        'dimension_name' => (string) $score['dimension_name'],
        'dimension_score' => (float) $score['percentage'],
    ];
}

$indicatorByCycleDimension = [];
foreach ($indicatorRows as $indicator) {
    $cycleId = (int) $indicator['cycle_id'];
    $dimensionId = (int) $indicator['dimension_id'];
    $indicatorId = (int) $indicator['indicator_id'];
    $indicatorByCycleDimension[$cycleId][$dimensionId][$indicatorId] = [
        'indicator_id' => $indicatorId,
        'indicator_code' => (string) $indicator['indicator_code'],
        'school_head_rating' => (float) $indicator['school_head_rating'],
        'school_head_rating_count' => (int) $indicator['school_head_rating_count'],
    ];
}

foreach ($teacherRows as $teacher) {
    $cycleId = (int) $teacher['cycle_id'];
    $dimensionId = (int) $teacher['dimension_id'];
    $indicatorId = (int) $teacher['indicator_id'];
    $indicator = $indicatorByCycleDimension[$cycleId][$dimensionId][$indicatorId] ?? [
        'indicator_id' => $indicatorId,
        'indicator_code' => (string) $teacher['indicator_code'],
        'school_head_rating' => null,
        'school_head_rating_count' => 0,
    ];
    $indicator['teacher_rating'] = (float) $teacher['teacher_rating'];
    $indicator['teacher_count'] = (int) $teacher['teacher_count'];
    $indicator['teacher_school_head_gap'] = $indicator['school_head_rating'] === null
        ? null
        : round($indicator['teacher_rating'] - $indicator['school_head_rating'], 4);
    $indicatorByCycleDimension[$cycleId][$dimensionId][$indicatorId] = $indicator;
}

$cyclesBySchool = [];
foreach ($cycleRows as $cycle) {
    $cyclesBySchool[(int) $cycle['school_id']][] = (int) $cycle['cycle_id'];
}

$rows = [];
foreach ($cyclesBySchool as $schoolId => $schoolCycleIds) {
    foreach ($schoolCycleIds as $cycleIndex => $cycleId) {
        $previousCycleId = $schoolCycleIds[$cycleIndex - 1] ?? null;
        $nextCycleId = $schoolCycleIds[$cycleIndex + 1] ?? null;
        foreach ($scoreByCycleDimension[$cycleId] ?? [] as $dimensionId => $current) {
            $previous = $previousCycleId !== null
                ? ($scoreByCycleDimension[$previousCycleId][$dimensionId] ?? null)
                : null;
            $next = $nextCycleId !== null
                ? ($scoreByCycleDimension[$nextCycleId][$dimensionId] ?? null)
                : null;

            $indicators = array_values(
                $indicatorByCycleDimension[$cycleId][$dimensionId] ?? []
            );
            $gaps = array_values(array_filter(
                array_column($indicators, 'teacher_school_head_gap'),
                static fn($gap): bool => $gap !== null
            ));
            $teacherHeadGap = $gaps
                ? round(array_sum($gaps) / count($gaps), 4)
                : null;

            $rows[] = [
                'school_id' => $schoolId,
                'cycle_id' => $cycleId,
                'school_year_id' => $cycleById[$cycleId]['sy_id'],
                'school_year_label' => $cycleById[$cycleId]['school_year_label'],
                'cycle_order' => $cycleById[$cycleId]['cycle_order'],
                'dimension_id' => $dimensionId,
                'dimension_no' => $current['dimension_no'],
                'dimension_name' => $current['dimension_name'],
                'dimension_score' => $current['dimension_score'],
                'previous_dimension_score' => $previous['dimension_score'] ?? null,
                'trend_vs_previous' => $previous
                    ? round($current['dimension_score'] - $previous['dimension_score'], 4)
                    : null,
                'teacher_school_head_gap' => $teacherHeadGap,
                'indicator_ratings' => $indicators,
                'next_cycle_id' => $nextCycleId,
                'next_school_year_label' => $next
                    ? $cycleById[$nextCycleId]['school_year_label']
                    : null,
                'next_dimension_score' => $next['dimension_score'] ?? null,
                'target_improved' => $next
                    ? (int) ($next['dimension_score'] > $current['dimension_score'])
                    : null,
            ];
        }
    }
}

echo json_encode([
    'source' => 'mysql',
    'target_definition' => 'next_cycle_dimension_score_greater_than_current',
    'cycle_order_definition' => 'school_id_then_school_year_label_start_then_school_year_label_end_then_cycle_id',
    'duplicate_finalized_cycles' => $duplicateFinalizedCycles,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
