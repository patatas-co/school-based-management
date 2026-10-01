<?php
declare(strict_types=1);

function calculateSbmDimensionScore(PDO $db, int $cycleId, int $dimensionId, int $schoolId): array
{
    $indicatorsStmt = $db->prepare("
        SELECT indicator_id, indicator_code
        FROM sbm_indicators
        WHERE dimension_id = ? AND is_active = 1
    ");
    $indicatorsStmt->execute([$dimensionId]);
    $indicators = $indicatorsStmt->fetchAll(PDO::FETCH_ASSOC);

    $teacherCodes = array_merge(
        TEACHER_ONLY_CODES,
        SH_TEACHER_CODES,
        SH_TCH_EXT_CODES,
        TCH_EXT_CODES
    );
    $externalCodes = array_merge(
        SH_EXT_CODES,
        SH_TCH_EXT_CODES,
        TCH_EXT_CODES
    );

    $schoolHeadRatingStmt = $db->prepare("
        SELECT rating
        FROM sbm_responses
        WHERE cycle_id = ? AND indicator_id = ? AND school_id = ?
    ");
    $teacherAverageStmt = $db->prepare("
        SELECT AVG(rating)
        FROM teacher_responses
        WHERE cycle_id = ? AND indicator_id = ?
    ");
    $externalAverageStmt = $db->prepare("
        SELECT AVG(rating)
        FROM stakeholder_responses
        WHERE cycle_id = ? AND indicator_id = ?
    ");

    $rawTotal = 0.0;
    $maxTotal = 0.0;
    foreach ($indicators as $indicator) {
        $ratings = [];

        $schoolHeadRatingStmt->execute([
            $cycleId,
            (int) $indicator['indicator_id'],
            $schoolId,
        ]);
        $schoolHeadRating = $schoolHeadRatingStmt->fetchColumn();
        if ($schoolHeadRating !== false && $schoolHeadRating !== null) {
            $ratings[] = (float) $schoolHeadRating;
        }

        if (in_array($indicator['indicator_code'], $teacherCodes, true)) {
            $teacherAverageStmt->execute([$cycleId, (int) $indicator['indicator_id']]);
            $teacherAverage = $teacherAverageStmt->fetchColumn();
            if ($teacherAverage !== false && $teacherAverage !== null) {
                $ratings[] = (float) $teacherAverage;
            }
        }

        if (in_array($indicator['indicator_code'], $externalCodes, true)) {
            $externalAverageStmt->execute([$cycleId, (int) $indicator['indicator_id']]);
            $externalAverage = $externalAverageStmt->fetchColumn();
            if ($externalAverage !== false && $externalAverage !== null) {
                $ratings[] = (float) $externalAverage;
            }
        }

        if ($ratings) {
            $rawTotal += array_sum($ratings) / count($ratings);
            $maxTotal += 4;
        }
    }

    $rawTotal = round($rawTotal, 2);
    $percentage = $maxTotal > 0 ? round(($rawTotal / $maxTotal) * 100, 2) : 0.0;

    return [
        'dimension_id' => $dimensionId,
        'raw_score' => $rawTotal,
        'max_score' => $maxTotal,
        'percentage' => $percentage,
    ];
}
