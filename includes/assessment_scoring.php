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
        WHERE cycle_id = ? AND indicator_id = ? AND school_id = ? AND status = 'submitted'
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
            $teacherAverageStmt->execute([
                $cycleId,
                (int) $indicator['indicator_id'],
                $schoolId,
            ]);
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

function recomputeSbmCycleScores(PDO $db, int $cycleId, int $schoolId, int $formVersionId): array
{
    $dimensionsStmt = $db->prepare("
        SELECT dimension_id
        FROM sbm_dimensions
        WHERE form_version_id = ?
        ORDER BY dimension_no
    ");
    $dimensionsStmt->execute([$formVersionId]);
    $dimensionIds = array_map('intval', $dimensionsStmt->fetchAll(PDO::FETCH_COLUMN));
    if (!$dimensionIds) {
        throw new RuntimeException('No dimensions are configured for the assessment form.');
    }

    $upsertScore = $db->prepare("
        INSERT INTO sbm_dimension_scores
            (cycle_id, school_id, dimension_id, raw_score, max_score, percentage)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            raw_score = VALUES(raw_score),
            max_score = VALUES(max_score),
            percentage = VALUES(percentage),
            computed_at = NOW()
    ");
    foreach ($dimensionIds as $dimensionId) {
        $score = calculateSbmDimensionScore($db, $cycleId, $dimensionId, $schoolId);
        $upsertScore->execute([
            $cycleId,
            $schoolId,
            $score['dimension_id'],
            $score['raw_score'],
            $score['max_score'],
            $score['percentage'],
        ]);
    }

    $totalStmt = $db->prepare("
        SELECT SUM(raw_score), SUM(max_score)
        FROM sbm_dimension_scores
        WHERE cycle_id = ? AND school_id = ?
    ");
    $totalStmt->execute([$cycleId, $schoolId]);
    [$totalRaw, $totalMax] = array_values($totalStmt->fetch(PDO::FETCH_NUM));
    $overallScore = (float) $totalMax > 0
        ? round(((float) $totalRaw / (float) $totalMax) * 100, 2)
        : 0.0;
    $maturity = sbmMaturityLevel($overallScore);

    $db->prepare("
        UPDATE sbm_cycles
        SET overall_score = ?, maturity_level = ?
        WHERE cycle_id = ? AND school_id = ? AND status = 'submitted'
    ")->execute([$overallScore, $maturity['label'], $cycleId, $schoolId]);

    return ['overall_score' => $overallScore, 'maturity_level' => $maturity['label']];
}
