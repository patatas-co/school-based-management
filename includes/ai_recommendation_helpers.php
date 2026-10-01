<?php
declare(strict_types=1);

function buildLowRaterRecommendationCard(array $teacherSummary, string $displayName): ?array
{
    $assignedCount = (int) ($teacherSummary['assigned_indicator_count'] ?? 0);
    $ratedCount = (int) ($teacherSummary['rated_indicator_count'] ?? 0);
    if (
        empty($teacherSummary['low_rater'])
        || $assignedCount < 1
        || $ratedCount !== $assignedCount
    ) {
        return null;
    }

    $confidencePct = round(($ratedCount / $assignedCount) * 100, 1);
    if ($confidencePct >= 80) {
        $confidenceLevel = 'High Confidence';
    } elseif ($confidencePct >= 60) {
        $confidenceLevel = 'Moderate Confidence';
    } else {
        $confidenceLevel = 'Low Confidence';
    }

    $title = 'Hold a supportive conversation with ' . $displayName;
    $text = "**$title** Every one of this teacher's $assignedCount assigned indicators was rated at the lowest level (1). This pattern can reflect disengagement, misunderstanding of the rating scale, or real concerns. Treat the conversation as exploratory and supportive, not punitive.\n\n"
        . "- Arrange a private one-on-one and begin by listening without judgment.\n"
        . "- Ask open questions about what shaped the ratings and whether the teacher has concerns or needs support.\n"
        . "- Review the rating scale together and compare it with a few concrete examples.";

    return [
        'text' => $text,
        'block' => [
            'title' => $title,
            'source' => 'deterministic_low_rater',
            'insufficient_data' => false,
            'confidence_pct' => $confidencePct,
            'confidence_level' => $confidenceLevel,
            'factors' => [
                "$ratedCount of $assignedCount assigned indicators have recorded ratings.",
                'Every recorded rating for this teacher is 1.',
            ],
            'indicator_codes' => [],
        ],
    ];
}

function getTeacherRatingSummaryData(PDO $db, int $cycleId, int $schoolId): array
{
    $formVersionId = 0;
    if ($cycleId > 0) {
        $formVersionQ = $db->prepare("
            SELECT d.form_version_id
            FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
            WHERE ds.cycle_id = ? AND d.form_version_id IS NOT NULL
            ORDER BY d.dimension_no
            LIMIT 1
        ");
        $formVersionQ->execute([$cycleId]);
        $formVersionId = (int) ($formVersionQ->fetchColumn() ?: 0);
    }
    if ($formVersionId < 1) {
        $formVersionId = (int) ($db->query(
            "SELECT version_id FROM form_versions WHERE is_active = 1 LIMIT 1"
        )->fetchColumn() ?: 0);
    }

    $summaries = [];
    $displayNames = [];
    if ($cycleId < 1 || $formVersionId < 1) {
        return ['summaries' => $summaries, 'display_names' => $displayNames];
    }

    $summaryQ = $db->prepare("
        SELECT u.user_id, u.full_name,
               ROUND(AVG(tr.rating), 2) AS average_rating,
               COUNT(DISTINCT CASE WHEN tr.tr_id IS NOT NULL THEN a.indicator_id END) AS rated_indicator_count,
               COUNT(DISTINCT a.indicator_id) AS assigned_indicator_count,
               COUNT(DISTINCT CASE WHEN tr.tr_id IS NOT NULL AND tr.rating <> 1 THEN a.indicator_id END) AS non_one_indicator_count
        FROM (
            SELECT DISTINCT tia.teacher_id, i.indicator_id
            FROM teacher_indicator_assignments tia
            JOIN sbm_indicators i
              ON i.indicator_code = tia.indicator_code
             AND i.form_version_id = ?
            WHERE tia.cycle_id IS NULL OR tia.cycle_id = ?
        ) a
        JOIN users u ON u.user_id = a.teacher_id
        LEFT JOIN teacher_responses tr
          ON tr.teacher_id = u.user_id
         AND tr.indicator_id = a.indicator_id
         AND tr.cycle_id = ?
         AND tr.school_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
        GROUP BY u.user_id, u.full_name
        ORDER BY u.user_id
    ");
    $summaryQ->execute([$formVersionId, $cycleId, $cycleId, $schoolId, $schoolId]);
    foreach ($summaryQ->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $teacherId = (int) $row['user_id'];
        $label = 'Teacher ' . $teacherId;
        $assignedCount = (int) $row['assigned_indicator_count'];
        $ratedCount = (int) $row['rated_indicator_count'];
        $displayNames[$label] = htmlspecialchars(
            (string) $row['full_name'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $summaries[] = [
            'user_id' => $teacherId,
            'label' => $label,
            'average_rating' => $row['average_rating'] !== null ? (float) $row['average_rating'] : null,
            'rated_indicator_count' => $ratedCount,
            'assigned_indicator_count' => $assignedCount,
            'low_rater' => $assignedCount > 0
                && $ratedCount === $assignedCount
                && (int) $row['non_one_indicator_count'] === 0,
        ];
    }

    return ['summaries' => $summaries, 'display_names' => $displayNames];
}

function buildLowRaterCards(array $teacherSummaries, array $teacherDisplayNames): array
{
    $cards = [];
    foreach ($teacherSummaries as $teacherSummary) {
        if (empty($teacherSummary['low_rater'])) {
            continue;
        }

        $neutralLabel = 'Teacher ' . (int) $teacherSummary['user_id'];
        $displayName = $teacherDisplayNames[$neutralLabel] ?? $neutralLabel;
        $card = buildLowRaterRecommendationCard($teacherSummary, $displayName);
        if ($card !== null) {
            $card['block']['teacher_user_id'] = (int) $teacherSummary['user_id'];
            $card['block']['teacher_label'] = $neutralLabel;
            $cards[] = $card;
        }
    }
    return $cards;
}

function mergeLowRaterCardsIntoRecommendation(array $response, array $cards): array
{
    if (!$cards) {
        return $response;
    }

    $existingText = is_string($response['recommendations'] ?? null)
        ? trim($response['recommendations'])
        : '';
    $cardTitles = array_column(array_column($cards, 'block'), 'title');
    $filteredLines = [];
    $skipExistingSection = false;
    foreach (explode("\n", $existingText) as $line) {
        if (preg_match('/^\*\*(.+?)\*\*/', trim($line), $headingMatch)) {
            $skipExistingSection = in_array(trim($headingMatch[1]), $cardTitles, true);
        }
        if (!$skipExistingSection) {
            $filteredLines[] = $line;
        }
    }
    $existingText = trim(implode("\n", $filteredLines));
    $cardText = implode("\n\n", array_column($cards, 'text'));
    $response['recommendations'] = $existingText === ''
        ? $cardText
        : $cardText . "\n\n" . $existingText;

    $existingBlocks = is_array($response['blocks'] ?? null)
        ? $response['blocks']
        : [];
    $existingBlocks = array_values(array_filter(
        $existingBlocks,
        static fn($block): bool => !is_array($block)
            || !in_array($block['title'] ?? null, $cardTitles, true)
    ));
    $response['blocks'] = array_merge(array_column($cards, 'block'), $existingBlocks);
    return $response;
}
