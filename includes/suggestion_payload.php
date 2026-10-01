<?php
declare(strict_types=1);

require_once __DIR__ . '/ai_recommendation_helpers.php';

/**
 * Build the recommendation request used by the School Head page and historical CLI.
 *
 * Passing no cycle/cutoff preserves the page's current query behavior. Historical
 * callers pass both values to keep every cycle-derived input within that year.
 */
function buildAiSuggestionPayload(
    PDO $db,
    int $schoolId,
    int $syId,
    ?int $cycleId = null,
    ?int $historicalCutoffYear = null
): array {
    if (($cycleId === null) !== ($historicalCutoffYear === null)) {
        throw new InvalidArgumentException('Historical payloads require both a cycle ID and year cutoff.');
    }

    $schoolStmt = $db->prepare('SELECT school_name FROM schools WHERE school_id = ?');
    $schoolStmt->execute([$schoolId]);
    $schoolName = $schoolStmt->fetchColumn() ?: 'School';

    $schoolYearStmt = $db->prepare('SELECT label FROM school_years WHERE sy_id = ?');
    $schoolYearStmt->execute([$syId]);
    $schoolYearLabel = $schoolYearStmt->fetchColumn() ?: 'Unknown';

    if ($cycleId === null) {
        $cycleStmt = $db->prepare("
            SELECT cycle_id
            FROM sbm_cycles
            WHERE school_id = ? AND sy_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $cycleStmt->execute([$schoolId, $syId]);
        $cycleId = (int) ($cycleStmt->fetchColumn() ?: 0);
    } else {
        $cycleStmt = $db->prepare("
            SELECT cycle_id
            FROM sbm_cycles
            WHERE cycle_id = ? AND school_id = ? AND sy_id = ?
            LIMIT 1
        ");
        $cycleStmt->execute([$cycleId, $schoolId, $syId]);
        if (!$cycleStmt->fetchColumn()) {
            throw new RuntimeException("Cycle $cycleId does not belong to school $schoolId and SY $syId.");
        }
    }

    $teacherSummaryData = getTeacherRatingSummaryData($db, $cycleId, $schoolId);
    $teacherSummaries = $teacherSummaryData['summaries'];
    $teacherDisplayNames = $teacherSummaryData['display_names'];
    $lowRaterIndicatorCodes = [];
    $teacherFormVersionId = 0;
    if ($cycleId > 0 && $teacherSummaries) {
        $formVersionStmt = $db->prepare("
            SELECT d.form_version_id
            FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
            WHERE ds.cycle_id = ? AND d.form_version_id IS NOT NULL
            ORDER BY d.dimension_no
            LIMIT 1
        ");
        $formVersionStmt->execute([$cycleId]);
        $teacherFormVersionId = (int) ($formVersionStmt->fetchColumn() ?: 0);
        if ($teacherFormVersionId > 0) {
            $assignedCodesStmt = $db->prepare("
                SELECT DISTINCT tia.teacher_id, i.indicator_code
                FROM teacher_indicator_assignments tia
                JOIN sbm_indicators i
                  ON i.indicator_code = tia.indicator_code
                 AND i.form_version_id = ?
                WHERE tia.cycle_id IS NULL OR tia.cycle_id = ?
            ");
            $assignedCodesStmt->execute([$teacherFormVersionId, $cycleId]);
            foreach ($assignedCodesStmt->fetchAll(PDO::FETCH_ASSOC) as $assignedCode) {
                $lowRaterIndicatorCodes[(int) $assignedCode['teacher_id']][] = $assignedCode['indicator_code'];
            }
        }
    }

    if ($historicalCutoffYear === null) {
        $dimensionStmt = $db->prepare("
            SELECT d.dimension_no, d.dimension_name, ROUND(AVG(ds.percentage), 1) AS avg_pct
            FROM sbm_dimensions d
            LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
            LEFT JOIN sbm_cycles c
              ON ds.cycle_id = c.cycle_id AND c.sy_id = ? AND c.school_id = ?
            GROUP BY d.dimension_id
            ORDER BY d.dimension_no
        ");
        $dimensionStmt->execute([$syId, $schoolId]);
    } else {
        $dimensionStmt = $db->prepare("
            SELECT d.dimension_no, d.dimension_name, ROUND(ds.percentage, 1) AS avg_pct
            FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
            WHERE ds.cycle_id = ? AND ds.school_id = ?
            ORDER BY d.dimension_no
        ");
        $dimensionStmt->execute([$cycleId, $schoolId]);
    }
    $dimensionScores = [];
    foreach ($dimensionStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dimensionScores[] = [
            'dimension_name' => $row['dimension_name'],
            'score' => (float) $row['avg_pct'],
            'maturity' => sbmMaturityLevel((float) $row['avg_pct'])['label'],
        ];
    }

    if ($historicalCutoffYear === null) {
        $weakStmt = $db->prepare("
            SELECT i.indicator_code, i.indicator_text, ROUND(AVG(all_r.rating), 2) AS rating
            FROM (
                SELECT cycle_id, indicator_id, rating FROM sbm_responses
                UNION ALL
                SELECT cycle_id, indicator_id, rating FROM teacher_responses
            ) AS all_r
            JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
            JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
            WHERE c.sy_id = ? AND c.school_id = ?
            GROUP BY i.indicator_id
            HAVING rating < 2.5
            ORDER BY rating ASC
        ");
        $weakStmt->execute([$syId, $schoolId]);
    } else {
        $weakStmt = $db->prepare("
            SELECT i.indicator_code, i.indicator_text, ROUND(AVG(all_r.rating), 2) AS rating
            FROM (
                SELECT cycle_id, indicator_id, rating FROM sbm_responses
                UNION ALL
                SELECT cycle_id, indicator_id, rating FROM teacher_responses
            ) AS all_r
            JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
            JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
            WHERE all_r.cycle_id = ? AND c.school_id = ?
            GROUP BY i.indicator_id
            HAVING rating < 2.5
            ORDER BY rating ASC, i.indicator_code
        ");
        $weakStmt->execute([$cycleId, $schoolId]);
    }
    $byRating = ['1' => [], '2' => []];
    foreach ($weakStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ratingLevel = max(1, min(2, (int) floor((float) $row['rating'])));
        $byRating[(string) $ratingLevel][] = [
            'code' => $row['indicator_code'],
            'text' => $row['indicator_text'],
            'rating' => (float) $row['rating'],
        ];
    }

    if ($historicalCutoffYear === null) {
        $historyStmt = $db->prepare("
            SELECT overall_score
            FROM sbm_cycles
            WHERE school_id = ? AND status IN ('validated','finalized','completed') AND sy_id != ?
            ORDER BY created_at DESC
            LIMIT 3
        ");
        $historyStmt->execute([$schoolId, $syId]);
    } else {
        $historyStmt = $db->prepare("
            SELECT c.overall_score
            FROM sbm_cycles c
            JOIN school_years history_sy ON history_sy.sy_id = c.sy_id
            WHERE c.school_id = ?
              AND c.status IN ('validated','finalized','completed')
              AND c.sy_id <> ?
              AND CAST(LEFT(history_sy.label, 4) AS UNSIGNED) < ?
            ORDER BY c.created_at DESC, c.cycle_id DESC
            LIMIT 3
        ");
        $historyStmt->execute([$schoolId, $syId, $historicalCutoffYear]);
    }
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    $weakCodes = array_merge(
        array_column($byRating['1'], 'code'),
        array_column($byRating['2'], 'code')
    );
    $respondentConsistency = [];
    $indicatorHistory = [];
    if ($weakCodes) {
        $placeholders = implode(',', array_fill(0, count($weakCodes), '?'));
        if ($historicalCutoffYear === null) {
            $consistencyStmt = $db->prepare("
                SELECT i.indicator_code,
                       ROUND(AVG(sr.rating), 2) AS sh_avg,
                       (SELECT ROUND(AVG(tr.rating), 2)
                        FROM teacher_responses tr
                        JOIN sbm_cycles tc ON tr.cycle_id = tc.cycle_id
                        WHERE tr.indicator_id = i.indicator_id
                          AND tc.sy_id = ? AND tc.school_id = ?) AS teacher_avg
                FROM sbm_responses sr
                JOIN sbm_indicators i ON sr.indicator_id = i.indicator_id
                JOIN sbm_cycles c ON sr.cycle_id = c.cycle_id
                WHERE c.sy_id = ? AND c.school_id = ?
                  AND i.indicator_code IN ($placeholders)
                GROUP BY i.indicator_id
            ");
            $consistencyStmt->execute(array_merge([$syId, $schoolId, $syId, $schoolId], $weakCodes));
        } else {
            $consistencyStmt = $db->prepare("
                SELECT i.indicator_code,
                       ROUND(AVG(sr.rating), 2) AS sh_avg,
                       (SELECT ROUND(AVG(tr.rating), 2)
                        FROM teacher_responses tr
                        WHERE tr.indicator_id = i.indicator_id AND tr.cycle_id = ?) AS teacher_avg
                FROM sbm_responses sr
                JOIN sbm_indicators i ON sr.indicator_id = i.indicator_id
                WHERE sr.cycle_id = ? AND sr.school_id = ?
                  AND i.indicator_code IN ($placeholders)
                GROUP BY i.indicator_id
                ORDER BY i.indicator_code
            ");
            $consistencyStmt->execute(array_merge([$cycleId, $cycleId, $schoolId], $weakCodes));
        }
        foreach ($consistencyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['sh_avg'] !== null && $row['teacher_avg'] !== null) {
                $respondentConsistency[$row['indicator_code']] = [
                    'sh_avg' => (float) $row['sh_avg'],
                    'teacher_avg' => (float) $row['teacher_avg'],
                ];
            }
        }

        if ($historicalCutoffYear === null) {
            $indicatorHistoryStmt = $db->prepare("
                SELECT i.indicator_code, c.sy_id, ROUND(AVG(all_r.rating), 2) AS rating
                FROM (
                    SELECT cycle_id, indicator_id, rating FROM sbm_responses
                    UNION ALL
                    SELECT cycle_id, indicator_id, rating FROM teacher_responses
                ) AS all_r
                JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
                JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
                WHERE c.school_id = ? AND c.sy_id != ?
                  AND c.status IN ('validated','finalized','completed')
                  AND i.indicator_code IN ($placeholders)
                GROUP BY i.indicator_id, c.sy_id
                ORDER BY c.created_at DESC
            ");
            $indicatorHistoryStmt->execute(array_merge([$schoolId, $syId], $weakCodes));
        } else {
            $indicatorHistoryStmt = $db->prepare("
                SELECT i.indicator_code, c.sy_id, ROUND(AVG(all_r.rating), 2) AS rating
                FROM (
                    SELECT cycle_id, indicator_id, rating FROM sbm_responses
                    UNION ALL
                    SELECT cycle_id, indicator_id, rating FROM teacher_responses
                ) AS all_r
                JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
                JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
                JOIN school_years history_sy ON history_sy.sy_id = c.sy_id
                WHERE c.school_id = ? AND c.sy_id != ?
                  AND c.status IN ('validated','finalized','completed')
                  AND CAST(LEFT(history_sy.label, 4) AS UNSIGNED) < ?
                  AND i.indicator_code IN ($placeholders)
                GROUP BY i.indicator_id, c.sy_id
                ORDER BY i.indicator_code, c.created_at DESC, c.sy_id DESC
            ");
            $indicatorHistoryStmt->execute(array_merge([$schoolId, $syId, $historicalCutoffYear], $weakCodes));
        }
        foreach ($indicatorHistoryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $indicatorHistory[$row['indicator_code']][] = (float) $row['rating'];
        }
    }

    $formVersionId = $teacherFormVersionId;
    if ($formVersionId < 1 || $historicalCutoffYear === null) {
        $formVersionId = (int) $db->query(
            'SELECT version_id FROM form_versions WHERE is_active = 1 LIMIT 1'
        )->fetchColumn();
    }
    $totalIndicatorStmt = $db->prepare("
        SELECT COUNT(*) FROM sbm_indicators
        WHERE is_active = 1 AND form_version_id = ?
    ");
    $totalIndicatorStmt->execute([$formVersionId]);
    $totalIndicatorCount = (int) $totalIndicatorStmt->fetchColumn();

    if ($historicalCutoffYear === null) {
        $ratedIndicatorStmt = $db->prepare("
            SELECT COUNT(DISTINCT all_r.indicator_id)
            FROM (
                SELECT cycle_id, indicator_id FROM sbm_responses
                UNION ALL
                SELECT cycle_id, indicator_id FROM teacher_responses
            ) AS all_r
            JOIN sbm_cycles c ON all_r.cycle_id = c.cycle_id
            WHERE c.sy_id = ? AND c.school_id = ?
        ");
        $ratedIndicatorStmt->execute([$syId, $schoolId]);
    } else {
        $ratedIndicatorStmt = $db->prepare("
            SELECT COUNT(DISTINCT all_r.indicator_id)
            FROM (
                SELECT cycle_id, indicator_id FROM sbm_responses
                UNION ALL
                SELECT cycle_id, indicator_id FROM teacher_responses
            ) AS all_r
            WHERE all_r.cycle_id = ?
        ");
        $ratedIndicatorStmt->execute([$cycleId]);
    }
    $ratedIndicatorCount = (int) $ratedIndicatorStmt->fetchColumn();
    $dataCompleteness = $totalIndicatorCount > 0
        ? round(($ratedIndicatorCount / $totalIndicatorCount) * 100, 1)
        : 0;

    if ($historicalCutoffYear === null) {
        $scoreStmt = $db->prepare("
            SELECT overall_score, maturity_level
            FROM sbm_cycles
            WHERE school_id = ? AND sy_id = ? AND status IN ('validated','finalized','completed')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $scoreStmt->execute([$schoolId, $syId]);
    } else {
        $scoreStmt = $db->prepare("
            SELECT overall_score, maturity_level
            FROM sbm_cycles
            WHERE cycle_id = ? AND school_id = ? AND sy_id = ?
              AND status IN ('validated','finalized','completed')
        ");
        $scoreStmt->execute([$cycleId, $schoolId, $syId]);
    }
    $score = $scoreStmt->fetch(PDO::FETCH_ASSOC);

    return [
        'cycle_id' => $cycleId,
        'school_name' => $schoolName,
        'sy_label' => (string) $schoolYearLabel,
        'teacher_summaries' => $teacherSummaries,
        'teacher_display_names' => $teacherDisplayNames,
        'low_rater_indicator_codes' => $lowRaterIndicatorCodes,
        'payload' => [
            'school_name' => $schoolName,
            'sy_label' => (string) $schoolYearLabel,
            'analysis' => [
                'gap_analysis' => [
                    'average_score' => $score ? (float) $score['overall_score'] : 0,
                    'overall_maturity' => $score ? $score['maturity_level'] : 'N/A',
                    'weakest_dimensions' => array_slice($dimensionScores, 0, 3),
                ],
                'by_rating' => $byRating,
                'history' => $history,
                'comment_summary' => ['top_topics' => [], 'has_urgent' => false],
                'respondent_consistency' => $respondentConsistency,
                'indicator_history' => $indicatorHistory,
                'data_completeness' => $dataCompleteness,
                'teacher_summaries' => $teacherSummaries,
            ],
        ],
    ];
}
