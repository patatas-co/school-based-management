<?php
declare(strict_types=1);

require_once __DIR__ . '/ai_recommendation_helpers.php';
require_once __DIR__ . '/suggestion_feedback.php';

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
            SELECT d.dimension_id, d.dimension_no, d.dimension_name, ROUND(ds.percentage, 1) AS avg_pct
            FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
            WHERE ds.cycle_id = ? AND ds.school_id = ?
            ORDER BY d.dimension_no
        ");
        $dimensionStmt->execute([$cycleId, $schoolId]);
    } else {
        $dimensionStmt = $db->prepare("
            SELECT d.dimension_id, d.dimension_no, d.dimension_name, ROUND(ds.percentage, 1) AS avg_pct
            FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
            WHERE ds.cycle_id = ? AND ds.school_id = ?
            ORDER BY d.dimension_no
        ");
        $dimensionStmt->execute([$cycleId, $schoolId]);
    }
    $dimensionScores = [];
    $currentYearOrder = (int) substr((string) $schoolYearLabel, 0, 4);
    foreach ($dimensionStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dimensionScores[] = [
            'dimension_id' => (int) $row['dimension_id'],
            'dimension_no' => (int) $row['dimension_no'],
            'dimension_name' => $row['dimension_name'],
            'score' => (float) $row['avg_pct'],
            'maturity' => sbmMaturityLevel((float) $row['avg_pct'])['label'],
        ];
    }

    $dimensionRows = [];
    $dimensionCoverage = [];
    foreach ($dimensionScores as $dimension) {
        $dimensionCoverage[(string) $dimension['dimension_id']] = sfGetDimensionCoverage(
            $db,
            $schoolId,
            $cycleId,
            $dimension['dimension_id']
        );
        $dimensionRows[] = [
            'dimension_id' => $dimension['dimension_id'],
            'dimension_no' => $dimension['dimension_no'],
            'dimension_name' => $dimension['dimension_name'],
            'dimension_score' => $dimension['score'],
            'school_year_order' => $currentYearOrder,
        ];
    }

    $previousSuggestions = [];
    $historyTableStmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'ai_suggestion_history'
    ");
    $historyTableStmt->execute();
    $historyTableAvailable = (int) $historyTableStmt->fetchColumn() > 0;
    $previousYearStmt = $db->prepare("
        SELECT sy.label
        FROM school_years sy
        WHERE CAST(LEFT(sy.label, 4) AS UNSIGNED) = ?
        ORDER BY sy.sy_id DESC
        LIMIT 1
    ");
    $previousYearLabel = false;
    $previousYearStmt->execute([$currentYearOrder - 1]);
    $previousYearLabel = $previousYearStmt->fetchColumn();
    if ($previousYearLabel !== false) {
        $previousSuggestionsStmt = $db->prepare("
            SELECT h.dimension_id AS previous_dimension_id, hd.dimension_no,
                   h.suggestion_title, h.suggestion_text,
                   h.priority_label, h.decline_risk, h.data_status,
                   h.score_at_time, h.adopted_plan_id,
                   (SELECT ROUND(AVG(ds2.percentage), 1)
                    FROM sbm_dimension_scores ds2
                    JOIN sbm_cycles c2 ON c2.cycle_id = ds2.cycle_id
                    JOIN school_years sy2 ON sy2.sy_id = c2.sy_id
                    JOIN sbm_dimensions d2 ON d2.dimension_id = ds2.dimension_id
                    WHERE c2.school_id = h.school_id
                      AND d2.dimension_no = hd.dimension_no
                      AND sy2.label = ?
                      AND c2.status IN ('validated', 'finalized', 'completed')
                      AND c2.cycle_id = (
                        SELECT c3.cycle_id
                        FROM sbm_cycles c3
                        JOIN school_years sy3 ON sy3.sy_id = c3.sy_id
                        WHERE c3.school_id = h.school_id
                          AND sy3.label = ?
                          AND c3.status IN ('validated', 'finalized', 'completed')
                        ORDER BY COALESCE(c3.finalized_at, c3.created_at) DESC, c3.cycle_id DESC
                        LIMIT 1
                      )) AS current_score
            FROM ai_suggestion_history h
            JOIN sbm_dimensions hd ON hd.dimension_id = h.dimension_id
            WHERE h.school_id = ? AND h.school_year = ? AND h.is_final = 1
            ORDER BY hd.dimension_no, h.id
        ");
        $previousSuggestionsStmt->execute([$schoolYearLabel, $schoolYearLabel, $schoolId, $previousYearLabel]);
        foreach ($previousSuggestionsStmt->fetchAll(PDO::FETCH_ASSOC) as $previousSuggestion) {
            $previousSuggestion['has_previous_suggestion'] = true;
            $previousScore = $previousSuggestion['score_at_time'] !== null
                ? (float) $previousSuggestion['score_at_time'] : null;
            $currentScore = $previousSuggestion['current_score'] !== null
                ? (float) $previousSuggestion['current_score'] : null;
            $previousSuggestion['score_change'] = ($previousScore !== null && $currentScore !== null)
                ? round($currentScore - $previousScore, 1) : null;
            $previousSuggestion['plan_created'] = $previousSuggestion['adopted_plan_id'] !== null;
            $previousSuggestion['adopted_and_worked'] = $previousSuggestion['plan_created']
                && $previousSuggestion['score_change'] !== null
                && $previousSuggestion['score_change'] > 0;
            $previousSuggestions[] = $previousSuggestion;
        }
        $previousByDimension = [];
        foreach ($previousSuggestions as $previousSuggestion) {
            $previousByDimension[(int) $previousSuggestion['dimension_no']] = true;
        }
        $previousDimensionScoreStmt = $db->prepare("
            SELECT ROUND(AVG(ds.percentage), 1)
            FROM sbm_dimension_scores ds
            JOIN sbm_cycles c ON c.cycle_id = ds.cycle_id
            JOIN school_years sy ON sy.sy_id = c.sy_id
            JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
            WHERE c.school_id = ?
              AND sy.label = ?
              AND d.dimension_no = ?
              AND c.status IN ('validated', 'finalized', 'completed')
              AND c.cycle_id = (
                SELECT c_latest.cycle_id
                FROM sbm_cycles c_latest
                JOIN school_years sy_latest ON sy_latest.sy_id = c_latest.sy_id
                WHERE c_latest.school_id = c.school_id
                  AND sy_latest.label = ?
                  AND c_latest.status IN ('validated', 'finalized', 'completed')
                ORDER BY COALESCE(c_latest.finalized_at, c_latest.created_at) DESC,
                         c_latest.cycle_id DESC
                LIMIT 1
              )
        ");
        foreach ($dimensionScores as $dimension) {
            $dimensionNo = (int) $dimension['dimension_no'];
            if (isset($previousByDimension[$dimensionNo])) {
                continue;
            }
            $previousDimensionScoreStmt->execute([
                $schoolId,
                $previousYearLabel,
                $dimensionNo,
                $previousYearLabel,
            ]);
            $previousScoreValue = $previousDimensionScoreStmt->fetchColumn();
            if ($previousScoreValue === false || $previousScoreValue === null) {
                continue;
            }
            $previousScore = (float) $previousScoreValue;
            $currentScore = (float) $dimension['score'];
            $previousSuggestions[] = [
                'previous_dimension_id' => null,
                'dimension_no' => $dimensionNo,
                'suggestion_title' => null,
                'suggestion_text' => null,
                'priority_label' => null,
                'decline_risk' => null,
                'data_status' => null,
                'score_at_time' => $previousScore,
                'current_score' => $currentScore,
                'score_change' => round($currentScore - $previousScore, 1),
                'adopted_plan_id' => null,
                'plan_created' => false,
                'adopted_and_worked' => false,
                'has_previous_suggestion' => false,
            ];
        }
    }

    $earlierDimensionStmt = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, AVG(ds.percentage) AS dimension_score,
               CAST(LEFT(sy.label, 4) AS UNSIGNED) AS school_year_order
        FROM sbm_dimension_scores ds
        JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
        JOIN sbm_cycles c ON c.cycle_id = ds.cycle_id
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE c.school_id = ?
          AND c.status IN ('validated', 'finalized', 'completed')
          AND c.cycle_id = (
              SELECT MAX(c2.cycle_id)
              FROM sbm_cycles c2
              WHERE c2.school_id = c.school_id
                AND c2.sy_id = c.sy_id
                AND c2.status IN ('validated', 'finalized', 'completed')
          )
          AND CAST(LEFT(sy.label, 4) AS UNSIGNED) < ?
        GROUP BY d.dimension_no, d.dimension_name, sy.sy_id, sy.label
        ORDER BY school_year_order, d.dimension_no
    ");
    $earlierDimensionStmt->execute([$schoolId, $currentYearOrder]);
    foreach ($earlierDimensionStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dimensionRows[] = [
            'dimension_no' => (int) $row['dimension_no'],
            'dimension_name' => $row['dimension_name'],
            'dimension_score' => (float) $row['dimension_score'],
            'school_year_order' => (int) $row['school_year_order'],
        ];
    }

    $ratingsStmt = $db->prepare("
        SELECT d.dimension_id, i.indicator_code, i.indicator_text,
               ROUND(AVG(all_r.rating), 2) AS rating,
               COUNT(all_r.rating) AS response_count
        FROM (
            SELECT cycle_id, indicator_id, rating FROM sbm_responses
            UNION ALL
            SELECT cycle_id, indicator_id, rating
            FROM teacher_responses
            WHERE status = 'submitted'
        ) AS all_r
        JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
        JOIN sbm_dimensions d
          ON d.dimension_id = i.dimension_id
         AND d.form_version_id = i.form_version_id
        JOIN sbm_dimension_scores ds
          ON ds.cycle_id = all_r.cycle_id
         AND ds.dimension_id = d.dimension_id
         AND ds.school_id = ?
        WHERE all_r.cycle_id = ? AND i.is_active = 1 AND all_r.rating IS NOT NULL
        GROUP BY d.dimension_id, i.indicator_id, i.indicator_code, i.indicator_text
        ORDER BY i.indicator_code
    ");
    $ratingsStmt->execute([$schoolId, $cycleId]);
    $byRating = ['1' => [], '2' => []];
    $indicatorRatings = [];
    $indicatorResponseCounts = [];
    $dimensionEvidence = [];
    $dimensionIndicators = [];
    foreach ($ratingsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rating = (float) $row['rating'];
        $dimensionId = (int) $row['dimension_id'];
        $indicatorCode = (string) $row['indicator_code'];
        $indicatorRatings[$row['indicator_code']] = $rating;
        $indicatorResponseCounts[$row['indicator_code']] = (int) $row['response_count'];
        $dimensionIndicators[(string) $dimensionId][] = [
            'code' => $indicatorCode,
            'text' => (string) $row['indicator_text'],
            'rating' => $rating,
            'response_count' => (int) $row['response_count'],
        ];
        if (!isset($dimensionEvidence[(string) $dimensionId])) {
            $dimensionEvidence[(string) $dimensionId] = [
                'rated_indicator_count' => 0,
                'response_count' => 0,
            ];
        }
        $dimensionEvidence[(string) $dimensionId]['rated_indicator_count']++;
        $dimensionEvidence[(string) $dimensionId]['response_count'] += (int) $row['response_count'];
        if ($rating >= 2.5) {
            continue;
        }
        $ratingLevel = max(1, min(2, (int) floor($rating)));
        $byRating[(string) $ratingLevel][] = [
            'code' => $row['indicator_code'],
            'text' => $row['indicator_text'],
            'rating' => $rating,
        ];
    }
    $weakestIndicatorsByDimension = [];
    foreach ($dimensionIndicators as $dimensionId => $indicators) {
        usort($indicators, static function (array $left, array $right): int {
            $ratingOrder = $left['rating'] <=> $right['rating'];
            return $ratingOrder !== 0
                ? $ratingOrder
                : strcmp($left['code'], $right['code']);
        });
        $weakestIndicatorsByDimension[$dimensionId] = array_slice($indicators, 0, 3);
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
                          AND tr.status = 'submitted'
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
                        WHERE tr.indicator_id = i.indicator_id AND tr.cycle_id = ?
                          AND tr.status = 'submitted') AS teacher_avg
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
                    WHERE status = 'submitted'
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
                    WHERE status = 'submitted'
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
                WHERE status = 'submitted'
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
                WHERE status = 'submitted'
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
            WHERE cycle_id = ? AND school_id = ? AND sy_id = ?
            LIMIT 1
        ");
        $scoreStmt->execute([$cycleId, $schoolId, $syId]);
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

    $currentOverallScore = $score && is_numeric($score['overall_score'])
        ? round((float) $score['overall_score'], 1) : null;
    $previousOverallScore = null;
    $previousDimensionScores = [];
    if ($previousYearLabel !== false) {
        $previousCycleStmt = $db->prepare("
            SELECT c.cycle_id, c.overall_score
            FROM sbm_cycles c
            JOIN school_years sy ON sy.sy_id = c.sy_id
            WHERE c.school_id = ?
              AND sy.label = ?
              AND c.status IN ('validated', 'finalized', 'completed')
            ORDER BY COALESCE(c.finalized_at, c.created_at) DESC, c.cycle_id DESC
            LIMIT 1
        ");
        $previousCycleStmt->execute([$schoolId, $previousYearLabel]);
        $previousCycle = $previousCycleStmt->fetch(PDO::FETCH_ASSOC);
        if ($previousCycle) {
            $previousOverallScore = is_numeric($previousCycle['overall_score'])
                ? round((float) $previousCycle['overall_score'], 1) : null;
            $previousDimensionStmt = $db->prepare("
                SELECT d.dimension_no, ROUND(ds.percentage, 1) AS score
                FROM sbm_dimension_scores ds
                JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
                WHERE ds.cycle_id = ? AND ds.school_id = ?
            ");
            $previousDimensionStmt->execute([(int) $previousCycle['cycle_id'], $schoolId]);
            foreach ($previousDimensionStmt->fetchAll(PDO::FETCH_ASSOC) as $previousDimension) {
                if (is_numeric($previousDimension['score'])) {
                    $previousDimensionScores[(int) $previousDimension['dimension_no']] =
                        (float) $previousDimension['score'];
                }
            }
        }
    }

    $dimensionComparisons = [];
    foreach ($dimensionScores as &$dimension) {
        $dimensionNo = (int) $dimension['dimension_no'];
        $previousScore = $previousDimensionScores[$dimensionNo] ?? null;
        $currentDimensionScore = (float) $dimension['score'];
        $change = $previousScore !== null
            ? round($currentDimensionScore - $previousScore, 1) : null;
        if ($change === null) {
            $changeText = 'No prior-year data available.';
            $trendText = 'No prior-year data available.';
        } elseif ($change > 0) {
            $changeText = '+' . number_format($change, 1) . ' percentage points';
            $trendText = 'Improved by ' . number_format($change, 1) . ' percentage points from the previous school year.';
        } elseif ($change < 0) {
            $changeText = '−' . number_format(abs($change), 1) . ' percentage points';
            $trendText = 'Declined by ' . number_format(abs($change), 1) . ' percentage points from the previous school year.';
        } else {
            $changeText = '0.0 percentage points';
            $trendText = 'Unchanged from the previous school year.';
        }
        $dimension['previous_score'] = $previousScore;
        $dimension['score_change'] = $change;
        $dimensionComparisons[(string) $dimension['dimension_id']] = [
            'current_score' => $currentDimensionScore,
            'previous_score' => $previousScore,
            'change' => $change,
            'change_text' => $changeText,
            'trend_text' => $trendText,
        ];
    }
    unset($dimension);
    $weakestDimensionScores = $dimensionScores;
    usort($weakestDimensionScores, static function (array $left, array $right): int {
        $scoreOrder = (float) $left['score'] <=> (float) $right['score'];
        return $scoreOrder !== 0
            ? $scoreOrder
            : (int) $left['dimension_no'] <=> (int) $right['dimension_no'];
    });
    $weakestDimensionScores = array_slice($weakestDimensionScores, 0, 3);
    usort($dimensionScores, static function (array $left, array $right) use ($dimensionComparisons): int {
        $leftChange = $dimensionComparisons[(string) $left['dimension_id']]['change'];
        $rightChange = $dimensionComparisons[(string) $right['dimension_id']]['change'];
        if ($leftChange === null && $rightChange !== null) return 1;
        if ($rightChange === null && $leftChange !== null) return -1;
        if ($leftChange !== $rightChange) return ($leftChange ?? 0) <=> ($rightChange ?? 0);
        return (int) $left['dimension_no'] <=> (int) $right['dimension_no'];
    });

    $comparableDimensions = array_values(array_filter(
        $dimensionScores,
        static fn(array $dimension): bool =>
            $dimensionComparisons[(string) $dimension['dimension_id']]['change'] !== null
    ));
    $hasPreviousYear = $previousOverallScore !== null || $previousDimensionScores !== [];
    $overallChange = (
        $currentOverallScore !== null && $previousOverallScore !== null
    ) ? round($currentOverallScore - $previousOverallScore, 1) : null;
    $overviewText = 'No prior-year data available.';
    if ($hasPreviousYear) {
        $currentOverallLabel = $currentOverallScore !== null
            ? number_format($currentOverallScore, 1) . '%' : 'unavailable';
        $previousOverallLabel = $previousOverallScore !== null
            ? number_format($previousOverallScore, 1) . '%' : 'unavailable';
        $overallChangeLabel = $overallChange === null
            ? 'change unavailable'
            : (($overallChange > 0 ? '+' : ($overallChange < 0 ? '−' : ''))
                . number_format(abs($overallChange), 1) . ' percentage points');
        $dimensionSummary = 'Dimension comparisons are not available for the previous school year.';
        if ($comparableDimensions) {
            $declines = array_values(array_filter(
                $comparableDimensions,
                static fn(array $dimension): bool =>
                    $dimensionComparisons[(string) $dimension['dimension_id']]['change'] < 0
            ));
            $improvements = array_values(array_filter(
                $comparableDimensions,
                static fn(array $dimension): bool =>
                    $dimensionComparisons[(string) $dimension['dimension_id']]['change'] > 0
            ));
            $biggestDecline = $declines[0] ?? null;
            $biggestImprovement = $improvements ? end($improvements) : null;
            $declineSummary = $biggestDecline
                ? 'Biggest decline: D' . (int) $biggestDecline['dimension_no'] . ' '
                    . $biggestDecline['dimension_name'] . ' (' .
                    $dimensionComparisons[(string) $biggestDecline['dimension_id']]['change_text'] . ').'
                : 'No dimension declines were recorded.';
            $improvementSummary = $biggestImprovement
                ? 'Biggest improvement: D' . (int) $biggestImprovement['dimension_no'] . ' '
                    . $biggestImprovement['dimension_name'] . ' (' .
                    $dimensionComparisons[(string) $biggestImprovement['dimension_id']]['change_text'] . ').'
                : 'No dimension improvements were recorded.';
            $dimensionSummary = $declineSummary . ' ' . $improvementSummary;
        }
        $previousYearLabelText = $previousYearLabel !== false
            ? (string) $previousYearLabel : 'previous year';
        $overviewText = "The current school year (SY $schoolYearLabel) overall SBM score is "
            . "$currentOverallLabel, compared with $previousOverallLabel in SY $previousYearLabelText "
            . "($overallChangeLabel). $dimensionSummary";
    }
    $scoreComparison = [
        'has_previous_year' => $hasPreviousYear,
        'current_school_year' => (string) $schoolYearLabel,
        'previous_school_year' => $previousYearLabel !== false ? (string) $previousYearLabel : null,
        'current_overall_score' => $currentOverallScore,
        'previous_overall_score' => $previousOverallScore,
        'overall_change' => $overallChange,
        'overview_text' => $overviewText,
        'dimensions' => $dimensionComparisons,
        'weakest_indicators' => $weakestIndicatorsByDimension,
    ];

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
                    'average_score' => $score && is_numeric($score['overall_score'])
                        ? (float) $score['overall_score'] : 'N/A',
                    'overall_maturity' => $score ? $score['maturity_level'] : 'N/A',
                    'weakest_dimensions' => $weakestDimensionScores,
                    'all_dimensions' => $dimensionScores,
                ],
                'dimension_rows' => $dimensionRows,
                'by_rating' => $byRating,
                'indicator_ratings' => $indicatorRatings,
                'indicator_response_counts' => $indicatorResponseCounts,
                'dimension_evidence' => $dimensionEvidence,
                'dimension_weakest_indicators' => $weakestIndicatorsByDimension,
                'dimension_coverage' => $dimensionCoverage,
                'score_comparison' => $scoreComparison,
                'dimension_comparisons' => $dimensionComparisons,
                'history' => $history,
                'comment_summary' => ['top_topics' => [], 'has_urgent' => false],
                'respondent_consistency' => $respondentConsistency,
                'indicator_history' => $indicatorHistory,
                'data_completeness' => $dataCompleteness,
                'teacher_summaries' => $teacherSummaries,
                'previous_suggestions' => $previousSuggestions,
            ],
        ],
    ];
}
