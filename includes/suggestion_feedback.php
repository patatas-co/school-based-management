<?php
declare(strict_types=1);

require_once __DIR__ . '/ai_usage_limiter.php';
require_once __DIR__ . '/../config/sbm_indicators.php';

function sfNormalizeText(string $text): string
{
    $text = strip_tags($text);
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
    return trim($normalized ?? $text);
}

function sfTextSimilarity(string $baseline, string $current): float
{
    $baseline = sfNormalizeText($baseline);
    $current = sfNormalizeText($current);
    if ($baseline === '' || $current === '') {
        return $baseline === $current ? 1.0 : 0.0;
    }

    similar_text($baseline, $current, $percentage);
    return max(0.0, min(1.0, $percentage / 100));
}

function sfHistorySuggestionBody(array $item): string
{
    $title = trim(str_replace('**', '', (string) ($item['suggestion_title'] ?? '')));
    $body = trim(str_replace('**', '', (string) ($item['suggestion_text'] ?? '')));
    if ($title !== '') {
        $body = preg_replace(
            '/^\s*' . preg_quote($title, '/') . '(?![\p{L}\p{N}])\s*/iu',
            '',
            $body,
            1
        ) ?? $body;
    }
    return trim($body);
}

function sfSuggestionActionSimilarity(array $baseline, array $current): float
{
    $stopWords = array_fill_keys([
        'about', 'after', 'again', 'also', 'and', 'are', 'because', 'been', 'being',
        'can', 'could', 'each', 'from', 'have', 'into', 'more', 'most', 'other',
        'should', 'some', 'such', 'than', 'that', 'their', 'them', 'then', 'there',
        'these', 'they', 'this', 'those', 'through', 'with', 'would', 'your',
        'ensure', 'consider', 'develop', 'establish', 'implement', 'improve',
        'provide', 'review', 'support', 'using',
    ], true);
    $extractActions = static function (array $item) use ($stopWords): array {
        $body = sfHistorySuggestionBody($item);
        preg_match_all('/(?:^|\s)-\s+(.+?)(?=(?:\s+-\s+)|$)/us', $body, $matches);
        $actions = $matches[1] ?? [];
        if (!$actions) {
            $actions = preg_split('/(?<=[.!?])\s+/u', $body) ?: [];
        }

        $tokenSets = [];
        foreach ($actions as $action) {
            $normalized = sfNormalizeText((string) $action);
            $tokens = array_values(array_unique(array_filter(
                explode(' ', $normalized),
                static fn(string $token): bool => strlen($token) >= 4
                    && !isset($stopWords[$token])
            )));
            if ($tokens) {
                $tokenSets[] = array_fill_keys($tokens, true);
            }
        }
        return $tokenSets;
    };

    $oldActions = $extractActions($baseline);
    $newActions = $extractActions($current);
    if (!$oldActions || !$newActions) {
        return 0.0;
    }

    $matches = 0;
    $usedNewActions = [];
    foreach ($oldActions as $oldTokens) {
        $bestIndex = null;
        $bestOverlap = 0.0;
        foreach ($newActions as $index => $newTokens) {
            if (isset($usedNewActions[$index])) {
                continue;
            }
            $intersection = count(array_intersect_key($oldTokens, $newTokens));
            $overlap = $intersection / min(count($oldTokens), count($newTokens));
            if ($overlap > $bestOverlap) {
                $bestOverlap = $overlap;
                $bestIndex = $index;
            }
        }
        if ($bestIndex !== null && $bestOverlap >= 0.55) {
            $matches++;
            $usedNewActions[$bestIndex] = true;
        }
    }

    return $matches / max(count($oldActions), count($newActions));
}

function sfGetDimensionCoverage(PDO $db, int $schoolId, int $cycleId, int $dimensionId): array
{
    $stmt = $db->prepare("
        SELECT
            (SELECT ds.percentage
             FROM sbm_dimension_scores ds
             WHERE ds.cycle_id = ? AND ds.school_id = ? AND ds.dimension_id = d.dimension_id
             LIMIT 1) AS dimension_score,
            (SELECT COUNT(*)
             FROM sbm_indicators i
             WHERE i.dimension_id = d.dimension_id
               AND i.form_version_id = d.form_version_id
               AND i.is_active = 1) AS total_indicators,
            (SELECT COUNT(DISTINCT answered.indicator_id)
             FROM (
                 SELECT cycle_id, school_id, indicator_id
                 FROM sbm_responses
                 WHERE cycle_id = ? AND school_id = ? AND rating IS NOT NULL
                 UNION
                 SELECT cycle_id, school_id, indicator_id
                 FROM teacher_responses
                 WHERE cycle_id = ? AND school_id = ? AND rating IS NOT NULL AND status = 'submitted'
             ) answered
             JOIN sbm_indicators i ON i.indicator_id = answered.indicator_id
             WHERE i.dimension_id = d.dimension_id
               AND i.form_version_id = d.form_version_id
               AND i.is_active = 1) AS answered_indicators
        FROM sbm_dimensions d
        WHERE d.dimension_id = ?
        LIMIT 1
    ");
    $stmt->execute([
        $cycleId,
        $schoolId,
        $cycleId,
        $schoolId,
        $cycleId,
        $schoolId,
        $dimensionId,
    ]);
    $coverage = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalIndicators = (int) ($coverage['total_indicators'] ?? 0);
    $answeredIndicators = (int) ($coverage['answered_indicators'] ?? 0);
    $minimumAnswered = (int) ceil($totalIndicators / 2);
    return [
        'dimension_score_available' => (bool) ($coverage && $coverage['dimension_score'] !== null),
        'total_indicators' => $totalIndicators,
        'answered_indicators' => $answeredIndicators,
        'sufficient' => (bool) ($coverage
            && $coverage['dimension_score'] !== null
            && $totalIndicators > 0
            && $answeredIndicators >= $minimumAnswered),
    ];
}

function sfSuggestionDataStatus(PDO $db, int $schoolId, int $cycleId, int $dimensionId): string
{
    return sfGetDimensionCoverage($db, $schoolId, $cycleId, $dimensionId)['sufficient']
        ? 'sufficient'
        : 'insufficient';
}

function sfBuildLastCycleNote(int $dimensionNo, ?array $previous): ?string
{
    if ($previous === null) {
        return null;
    }

    $hasPreviousSuggestion = (bool) ($previous['has_previous_suggestion'] ?? !empty($previous['suggestion_title']));
    $title = trim((string) ($previous['suggestion_title'] ?? ''));
    $title = trim(preg_replace('/\s+/u', ' ', str_replace(['**', '"'], '', $title)) ?? $title);
    $summary = $hasPreviousSuggestion && $title !== ''
        ? '“' . $title . '”'
        : "no saved AI suggestion was recorded for D{$dimensionNo}";

    $previousScore = is_numeric($previous['score_at_time'] ?? null)
        ? (float) $previous['score_at_time'] : null;
    $currentScore = is_numeric($previous['current_score'] ?? null)
        ? (float) $previous['current_score'] : null;
    $change = is_numeric($previous['score_change'] ?? null)
        ? round((float) $previous['score_change'], 1) : null;
    if ($change === null) {
        $result = 'score change unavailable';
    } elseif ($change > 0) {
        $result = 'score improved by ' . number_format($change, 1);
    } elseif ($change < 0) {
        $result = 'score declined by ' . number_format(abs($change), 1);
    } else {
        $result = 'score was unchanged';
    }
    if ($previousScore !== null && $currentScore !== null) {
        $result .= ' from ' . number_format($previousScore, 1) . '% to ' . number_format($currentScore, 1) . '%';
    }

    if (!$hasPreviousSuggestion) {
        $planOutcome = 'there was no prior suggestion to adopt into an improvement plan';
    } else {
        $planOutcome = !empty($previous['plan_created'])
            ? 'an improvement plan was created'
            : 'no improvement plan was created';
    }
    return "Last cycle: {$summary}; Result: {$result}, and {$planOutcome}.";
}

function sfRecommendationSections(string $text): array
{
    $sections = [];
    $currentTitle = '';
    $currentLines = [];

    foreach (preg_split('/\R/', trim($text)) ?: [] as $line) {
        if (preg_match('/^\s*\*\*(.+?)\*\*(.*)$/', $line, $matches)) {
            if ($currentLines) {
                $sections[] = [
                    'title' => $currentTitle ?: 'AI suggestion',
                    'body_text' => trim(implode("\n", $currentLines)),
                ];
            }
            $currentTitle = trim($matches[1]);
            $currentLines = trim($matches[2]) !== '' ? [trim($matches[2])] : [];
            continue;
        }
        $currentLines[] = $line;
    }

    if ($currentLines) {
        $sections[] = [
            'title' => $currentTitle ?: 'AI suggestion',
            'body_text' => trim(implode("\n", $currentLines)),
        ];
    }

    return array_values(array_filter(
        $sections,
        static fn(array $section): bool => $section['body_text'] !== ''
    ));
}

function sfSanitizeTeacherLabels(string $text, array $teacherDisplayNames): string
{
    $replacements = [];
    foreach ($teacherDisplayNames as $neutralLabel => $displayName) {
        $replacements[(string) $displayName] = (string) $neutralLabel;
    }

    return $replacements ? strtr($text, $replacements) : $text;
}

function saveSuggestionGeneration(
    PDO $db,
    int $userId,
    int $schoolId,
    int $cycleId,
    array $payload,
    array $response,
    array $teacherDisplayNames,
    bool $saveLatestRecommendation = true,
    array $generationOptions = []
): array {
    if ($cycleId < 1) {
        throw new RuntimeException('Cannot save AI suggestions without an assessment cycle.');
    }

    $isSynthetic = !empty($generationOptions['is_synthetic']);
    $generatedAt = $generationOptions['generated_at'] ?? null;
    if ($isSynthetic) {
        if ($saveLatestRecommendation) {
            throw new InvalidArgumentException('Synthetic suggestions cannot update the latest user recommendation.');
        }
        if (!is_string($generatedAt)) {
            throw new InvalidArgumentException('Synthetic suggestions require a generated_at timestamp.');
        }
        $parsedGeneratedAt = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $generatedAt,
            new DateTimeZone('Asia/Manila')
        );
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            !$parsedGeneratedAt
            || $parsedGeneratedAt->format('Y-m-d H:i:s') !== $generatedAt
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException('Synthetic generated_at must use the YYYY-MM-DD HH:MM:SS format.');
        }
    } elseif ($generatedAt !== null) {
        throw new InvalidArgumentException('Only synthetic suggestions may override generated_at.');
    }

    $recommendationText = is_string($response['recommendations'] ?? null)
        ? $response['recommendations']
        : '';
    $blocks = is_array($response['blocks'] ?? null) ? $response['blocks'] : [];
    $sections = sfRecommendationSections($recommendationText);
    if (!$sections && $blocks) {
        foreach ($blocks as $block) {
            if (is_array($block) && !empty($block['title'])) {
                $sections[] = [
                    'title' => (string) $block['title'],
                    'body_text' => (string) ($block['body_text'] ?? $block['text'] ?? $block['title']),
                ];
            }
        }
    }

    $blocksByTitle = [];
    foreach ($blocks as $blockIndex => $block) {
        if (is_array($block) && isset($block['title'])) {
            $blocksByTitle[(string) $block['title']][] = $blockIndex;
        }
    }

    $ownsTransaction = !$db->inTransaction();
    if ($ownsTransaction) {
        $db->beginTransaction();
    } else {
        $db->exec('SAVEPOINT sf_suggestion_generation');
    }
    try {
        if ($isSynthetic) {
            $generationStmt = $db->prepare("
                INSERT INTO ai_suggestion_generations
                    (user_id, school_id, cycle_id, generated_at, is_synthetic, backend_used, input_payload_hash)
                VALUES (?, ?, ?, ?, 1, ?, ?)
            ");
            $generationStmt->execute([
                $userId,
                $schoolId,
                $cycleId,
                $generatedAt,
                substr((string) ($response['backend_used'] ?? $response['backend'] ?? 'unknown'), 0, 60),
                hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            ]);
        } else {
            $generationStmt = $db->prepare("
                INSERT INTO ai_suggestion_generations
                    (user_id, school_id, cycle_id, generated_at, backend_used, input_payload_hash)
                VALUES (?, ?, ?, NOW(), ?, ?)
            ");
            $generationStmt->execute([
                $userId,
                $schoolId,
                $cycleId,
                substr((string) ($response['backend_used'] ?? $response['backend'] ?? 'unknown'), 0, 60),
                hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            ]);
        }
        $generationId = (int) $db->lastInsertId();

        $insertItem = $isSynthetic
            ? $db->prepare("
                INSERT INTO ai_suggestion_items
                    (generation_id, item_index, source, detector_type, title, body_text, indicator_codes,
                     confidence, teacher_user_id, teacher_label, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")
            : $db->prepare("
                INSERT INTO ai_suggestion_items
                    (generation_id, item_index, source, detector_type, title, body_text, indicator_codes,
                     confidence, teacher_user_id, teacher_label, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
        foreach ($sections as $itemIndex => $section) {
            $title = (string) $section['title'];
            $matchingBlockIndex = null;
            if (!empty($blocksByTitle[$title])) {
                $matchingBlockIndex = array_shift($blocksByTitle[$title]);
            }
            $block = $matchingBlockIndex !== null && is_array($blocks[$matchingBlockIndex] ?? null)
                ? $blocks[$matchingBlockIndex]
                : [];
            $isTeacherOutlier = in_array(
                $block['source'] ?? '',
                ['deterministic_low_rater', 'ml_teacher_outlier'],
                true
            );
            $source = ($block['source'] ?? '') === 'ml_teacher_outlier'
                ? 'ml_teacher_outlier'
                : ($isTeacherOutlier ? 'rule_teacher_outlier' : 'llm');
            $teacherUserId = $isTeacherOutlier ? (int) ($block['teacher_user_id'] ?? 0) : 0;
            $teacherLabel = $isTeacherOutlier
                ? 'Teacher ' . $teacherUserId
                : null;
            if ($isTeacherOutlier && $teacherUserId < 1) {
                throw new RuntimeException('A teacher-outlier suggestion is missing its neutral teacher identifier.');
            }

            $storedTitle = sfSanitizeTeacherLabels($title, $teacherDisplayNames);
            $storedBody = sfSanitizeTeacherLabels((string) $section['body_text'], $teacherDisplayNames);
            $indicatorCodes = array_values(array_unique(array_filter(
                is_array($block['indicator_codes'] ?? null) ? $block['indicator_codes'] : [],
                static fn($code): bool => is_string($code) && trim($code) !== ''
            )));
            $confidence = isset($block['confidence_pct']) && is_numeric($block['confidence_pct'])
                ? max(0.0, min(100.0, (float) $block['confidence_pct']))
                : null;

            $itemParams = [
                $generationId,
                $itemIndex,
                $source,
                $isTeacherOutlier ? ($block['detector_type'] ?? null) : null,
                $storedTitle,
                $storedBody,
                json_encode($indicatorCodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                $confidence,
                $isTeacherOutlier ? $teacherUserId : null,
                $teacherLabel,
            ];
            if ($isSynthetic) {
                $itemParams[] = $generatedAt;
            }
            $insertItem->execute($itemParams);
            $itemId = (int) $db->lastInsertId();
            if ($matchingBlockIndex !== null) {
                $blocks[$matchingBlockIndex]['suggestion_item_id'] = $itemId;
            }
        }

        $historyTableStmt = $db->prepare("
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = 'ai_suggestion_history'
            ");
        $historyTableStmt->execute();
        if ((int) $historyTableStmt->fetchColumn() < 1) {
            throw new RuntimeException(
                'Suggestion history is not installed. Run config/migration_ai_suggestion_history.sql before generating suggestions.'
            );
        }
        $reviewColumnStmt = $db->prepare("
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'ai_suggestion_history'
                  AND column_name = 'needs_review'
            ");
        $reviewColumnStmt->execute();
        if ((int) $reviewColumnStmt->fetchColumn() < 1) {
            throw new RuntimeException(
                'Suggestion review tracking is not installed. Run config/migration_ai_suggestion_history_review.sql before generating suggestions.'
            );
        }
        $schoolYear = trim((string) ($payload['sy_label'] ?? ''));
        $dimensionRows = $payload['analysis']['gap_analysis']['all_dimensions'] ?? [];
        $dimensionOutlook = $response['dimension_outlook'] ?? [];
        $outlookByDimension = [];
        foreach ($dimensionOutlook as $outlook) {
            $outlookByDimension[(int) ($outlook['dimension_id'] ?? 0)] = $outlook;
        }
        $suggestions = $response['suggestions'] ?? [];
        if ($schoolYear !== '' && $dimensionRows && $suggestions) {
            $dimensionsById = [];
            foreach ($dimensionRows as $dimension) {
                $dimensionId = (int) ($dimension['dimension_id'] ?? 0);
                if ($dimensionId > 0) {
                    $dimensionsById[$dimensionId] = $dimension;
                }
            }
            $suggestionsByDimension = [];
            foreach ($suggestions as $suggestion) {
                $dimensionId = (int) ($suggestion['dimension_id'] ?? 0);
                if (!isset($dimensionsById[$dimensionId])) {
                    throw new RuntimeException('An AI suggestion returned an unknown dimension_id.');
                }
                if (isset($suggestionsByDimension[$dimensionId])) {
                    throw new RuntimeException('The AI returned more than one suggestion for a dimension.');
                }
                if (trim((string) ($suggestion['title'] ?? '')) === ''
                    || trim((string) ($suggestion['text'] ?? '')) === '') {
                    throw new RuntimeException('An AI suggestion is missing its title or text.');
                }
                $suggestionsByDimension[$dimensionId] = $suggestion;
            }
            $previousByDimension = [];
            foreach (($payload['analysis']['previous_suggestions'] ?? []) as $previous) {
                if (is_array($previous) && (int) ($previous['dimension_no'] ?? 0) > 0) {
                    $previousByDimension[(int) $previous['dimension_no']] = $previous;
                }
            }
            $clearFinalStmt = $db->prepare(
                'UPDATE ai_suggestion_history SET is_final = 0 WHERE school_id = ? AND school_year = ?'
            );
            $clearFinalStmt->execute([$schoolId, $schoolYear]);
            $historyStmt = $db->prepare("
                INSERT INTO ai_suggestion_history
                  (school_id, school_year, dimension_id, suggestion_title, suggestion_text, last_cycle_note,
                   priority_label, decline_risk, data_status, score_at_time, model_version,
                   is_final, needs_review, generated_by, generated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, COALESCE(?, NOW()))
            ");
            foreach ($suggestionsByDimension as $dimensionId => $suggestion) {
                $dimension = $dimensionsById[$dimensionId];
                $outlook = $outlookByDimension[$dimensionId] ?? [];
                $score = is_numeric($dimension['score'] ?? null)
                    ? (float) $dimension['score']
                    : (is_numeric($dimension['dimension_score'] ?? null) ? (float) $dimension['dimension_score'] : null);
                $priority = ucfirst((string) ($outlook['priority'] ?? 'Medium'));
                if (!in_array($priority, ['High', 'Medium', 'Low'], true)) {
                    $priority = 'Medium';
                }
                $risk = strtolower((string) ($outlook['decline_risk'] ?? 'medium'));
                if (!in_array($risk, ['high', 'medium', 'low'], true)) {
                    $risk = 'medium';
                }
                $matchingBlock = [];
                foreach ($blocks as $block) {
                    if (is_array($block) && (int) ($block['dimension_id'] ?? 0) === $dimensionId) {
                        $matchingBlock = $block;
                        break;
                    }
                }
                $historyStmt->execute([
                    $schoolId,
                    $schoolYear,
                    $dimensionId,
                    mb_substr(trim((string) $suggestion['title']), 0, 255),
                    trim((string) $suggestion['text']),
                    sfBuildLastCycleNote(
                        (int) ($dimension['dimension_no'] ?? 0),
                        $previousByDimension[(int) ($dimension['dimension_no'] ?? 0)] ?? null
                    ),
                    $priority,
                    $risk,
                    sfSuggestionDataStatus($db, $schoolId, $cycleId, $dimensionId),
                    $score,
                    mb_substr((string) ($suggestion['model_version'] ?? $response['backend_used'] ?? $response['model_source'] ?? 'unknown'), 0, 80),
                    !empty($suggestion['needs_review']) ? 1 : 0,
                    $userId,
                    $generatedAt,
                ]);
            }
        }

        if ($saveLatestRecommendation) {
            $latestRecommendation = [
                'text' => $recommendationText,
                'blocks' => $blocks,
            ];
            if (is_array($response['suggestion_ranking'] ?? null)) {
                $latestRecommendation['suggestion_ranking'] = $response['suggestion_ranking'];
            }
            aiUsageSaveRecommendation($db, $userId, json_encode(
                $latestRecommendation,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ));
        }
        if ($ownsTransaction) {
            $db->commit();
        } else {
            $db->exec('RELEASE SAVEPOINT sf_suggestion_generation');
        }
        $response['blocks'] = $blocks;
        return $response;
    } catch (Throwable $e) {
        if ($ownsTransaction && $db->inTransaction()) {
            $db->rollBack();
        } elseif ($db->inTransaction()) {
            $db->exec('ROLLBACK TO SAVEPOINT sf_suggestion_generation');
            $db->exec('RELEASE SAVEPOINT sf_suggestion_generation');
        }
        throw $e;
    }
}

function sfAttachLatestGenerationItems(
    PDO $db,
    int $userId,
    int $schoolId,
    int $cycleId,
    array $blocks
): array {
    $generationStmt = $db->prepare("
        SELECT generation_id
        FROM ai_suggestion_generations
        WHERE user_id = ? AND school_id = ? AND cycle_id = ?
        ORDER BY generated_at DESC, generation_id DESC
        LIMIT 1
    ");
    $generationStmt->execute([$userId, $schoolId, $cycleId]);
    $generationId = (int) ($generationStmt->fetchColumn() ?: 0);
    if ($generationId < 1) {
        return $blocks;
    }

    $itemsStmt = $db->prepare("
        SELECT item_id, source, title, indicator_codes, teacher_user_id, teacher_label
        FROM ai_suggestion_items
        WHERE generation_id = ?
        ORDER BY item_index
    ");
    $itemsStmt->execute([$generationId]);
    $itemsByTitle = [];
    $outlierItems = [];
    foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $item['indicator_codes'] = json_decode((string) $item['indicator_codes'], true) ?: [];
        if (in_array($item['source'], ['rule_teacher_outlier', 'ml_teacher_outlier'], true)) {
            $outlierItems[(int) $item['teacher_user_id']] = $item;
        } else {
            $itemsByTitle[(string) $item['title']][] = $item;
        }
    }

    foreach ($blocks as &$block) {
        if (!is_array($block)) {
            continue;
        }
        $item = null;
        if (in_array($block['source'] ?? '', ['deterministic_low_rater', 'ml_teacher_outlier'], true)) {
            $item = $outlierItems[(int) ($block['teacher_user_id'] ?? 0)] ?? null;
        } elseif (!empty($itemsByTitle[$block['title'] ?? ''])) {
            $item = array_shift($itemsByTitle[$block['title']]);
        }
        if ($item !== null) {
            $block['suggestion_item_id'] = (int) $item['item_id'];
            $block['indicator_codes'] = $item['indicator_codes'];
            if (in_array($item['source'], ['rule_teacher_outlier', 'ml_teacher_outlier'], true)) {
                $block['teacher_user_id'] = (int) $item['teacher_user_id'];
                $block['teacher_label'] = $item['teacher_label'];
            }
        }
    }
    unset($block);
    return $blocks;
}

function sfAssertSchoolAccess(PDO $db, int $schoolId): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $sessionSchoolId = (int) ($_SESSION['school_id'] ?? 0);
    $role = (string) ($_SESSION['role'] ?? '');
    if (
        $userId < 1
        || $schoolId < 1
        || $schoolId !== $sessionSchoolId
        || !in_array($role, ['school_head', 'sbm_coordinator'], true)
    ) {
        throw new RuntimeException('Access denied for suggestion feedback data.');
    }

    $userStmt = $db->prepare("
        SELECT 1 FROM users
        WHERE user_id = ? AND school_id = ? AND role = ? AND status = 'active'
        LIMIT 1
    ");
    $userStmt->execute([$userId, $schoolId, $role]);
    if (!$userStmt->fetchColumn()) {
        throw new RuntimeException('Access denied for suggestion feedback data.');
    }
}

function getSuggestionGenerationFeedback(PDO $db, int $schoolId, ?int $cycleId = null): array
{
    sfAssertSchoolAccess($db, $schoolId);
    $sql = "
        SELECT g.generation_id, g.user_id, g.school_id, g.cycle_id, g.generated_at,
               g.backend_used, i.item_id, i.item_index, i.source, i.title,
               i.body_text, i.indicator_codes, i.confidence,
               i.teacher_user_id, i.teacher_label,
               COUNT(DISTINCT ip.plan_id) AS linked_plan_count,
               GROUP_CONCAT(DISTINCT ip.plan_id ORDER BY ip.plan_id) AS linked_plan_ids
        FROM ai_suggestion_generations g
        LEFT JOIN ai_suggestion_items i ON i.generation_id = g.generation_id
        LEFT JOIN improvement_plans ip
          ON ip.suggestion_item_id = i.item_id AND ip.school_id = g.school_id
        WHERE g.school_id = ?
    ";
    $params = [$schoolId];
    if ($cycleId !== null) {
        $sql .= ' AND g.cycle_id = ?';
        $params[] = $cycleId;
    }
    $sql .= ' GROUP BY g.generation_id, i.item_id ORDER BY g.generated_at DESC, i.item_index';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $generations = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $generationId = (int) $row['generation_id'];
        if (!isset($generations[$generationId])) {
            $generations[$generationId] = [
                'generation_id' => $generationId,
                'user_id' => (int) $row['user_id'],
                'school_id' => (int) $row['school_id'],
                'cycle_id' => (int) $row['cycle_id'],
                'generated_at' => $row['generated_at'],
                'backend_used' => $row['backend_used'],
                'items' => [],
            ];
        }
        if ($row['item_id'] === null) {
            continue;
        }
        $linkedPlanCount = (int) $row['linked_plan_count'];
        $generations[$generationId]['items'][] = [
            'item_id' => (int) $row['item_id'],
            'item_index' => (int) $row['item_index'],
            'source' => $row['source'],
            'title' => $row['title'],
            'body_text' => $row['body_text'],
            'indicator_codes' => json_decode((string) $row['indicator_codes'], true) ?: [],
            'confidence' => $row['confidence'] !== null ? (float) $row['confidence'] : null,
            'teacher_user_id' => $row['teacher_user_id'] !== null ? (int) $row['teacher_user_id'] : null,
            'teacher_label' => $row['teacher_label'],
            'linked_plan_count' => $linkedPlanCount,
            'linked_plan_ids' => $row['linked_plan_ids'] === null
                ? []
                : array_map('intval', explode(',', $row['linked_plan_ids'])),
            'usage_status' => $linkedPlanCount > 0 ? 'linked_to_plan' : 'not_recorded_as_used',
        ];
    }
    return array_values($generations);
}

function getSuggestionPlanFeedback(PDO $db, int $planId, int $schoolId): ?array
{
    sfAssertSchoolAccess($db, $schoolId);
    $planStmt = $db->prepare("
        SELECT ip.plan_id, ip.school_id, ip.cycle_id, ip.suggestion_item_id,
               ip.suggestion_baseline_text, ip.objective, ip.strategy,
               ip.submitted_at, ip.approved_at, ip.validated_at, ip.workflow_status
        FROM improvement_plans ip
        WHERE ip.plan_id = ? AND ip.school_id = ?
        LIMIT 1
    ");
    $planStmt->execute([$planId, $schoolId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan || $plan['suggestion_item_id'] === null) {
        return null;
    }

    $historyStmt = $db->prepare("
        SELECT
            SUM(CASE WHEN action IN ('coordinator_returned', 'coordinator_revision') THEN 1 ELSE 0 END) AS return_count,
            MAX(CASE WHEN action = 'coordinator_approved' THEN created_at ELSE NULL END) AS approval_at,
            MIN(CASE WHEN action IN ('school_head_submitted', 'school_head_resubmitted') THEN created_at ELSE NULL END) AS first_submitted_at
        FROM improvement_plan_history
        WHERE plan_id = ?
    ");
    $historyStmt->execute([$planId]);
    $history = $historyStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $approvalAt = $history['approval_at'] ?: $plan['approved_at'];
    $submittedAt = $history['first_submitted_at'] ?: $plan['submitted_at'];
    $timeToApprovalSeconds = null;
    if ($approvalAt && $submittedAt && strtotime($approvalAt) >= strtotime($submittedAt)) {
        $timeToApprovalSeconds = strtotime($approvalAt) - strtotime($submittedAt);
    }

    return [
        'plan_id' => (int) $plan['plan_id'],
        'suggestion_item_id' => (int) $plan['suggestion_item_id'],
        'edit_similarity' => sfTextSimilarity(
            (string) ($plan['suggestion_baseline_text'] ?? ''),
            trim((string) $plan['objective'] . "\n" . (string) $plan['strategy'])
        ),
        'return_count' => (int) ($history['return_count'] ?? 0),
        'approved' => $approvalAt !== null
            || in_array($plan['workflow_status'], ['approved', 'finalized'], true),
        'validated_at' => $plan['validated_at'],
        'time_to_approval_seconds' => $timeToApprovalSeconds,
    ];
}

function sfIndicatorRating(PDO $db, int $cycleId, int $schoolId, int $indicatorId): ?float
{
    $indicatorStmt = $db->prepare('SELECT indicator_code FROM sbm_indicators WHERE indicator_id = ? LIMIT 1');
    $indicatorStmt->execute([$indicatorId]);
    $indicatorCode = $indicatorStmt->fetchColumn();
    if ($indicatorCode === false) {
        return null;
    }

    $teacherCodes = array_merge(
        TEACHER_ONLY_CODES,
        SH_TEACHER_CODES,
        SH_TCH_EXT_CODES,
        TCH_EXT_CODES
    );
    $externalCodes = array_merge(SH_EXT_CODES, SH_TCH_EXT_CODES, TCH_EXT_CODES);
    $ratings = [];

    $schoolHeadStmt = $db->prepare("
        SELECT rating FROM sbm_responses
        WHERE cycle_id = ? AND indicator_id = ? AND school_id = ?
    ");
    $schoolHeadStmt->execute([$cycleId, $indicatorId, $schoolId]);
    $schoolHeadRating = $schoolHeadStmt->fetchColumn();
    if ($schoolHeadRating !== false && $schoolHeadRating !== null) {
        $ratings[] = (float) $schoolHeadRating;
    }

    if (in_array($indicatorCode, $teacherCodes, true)) {
        $teacherStmt = $db->prepare("
            SELECT AVG(rating) FROM teacher_responses
            WHERE cycle_id = ? AND indicator_id = ? AND status = 'submitted'
        ");
        $teacherStmt->execute([$cycleId, $indicatorId]);
        $teacherRating = $teacherStmt->fetchColumn();
        if ($teacherRating !== false && $teacherRating !== null) {
            $ratings[] = (float) $teacherRating;
        }
    }

    if (in_array($indicatorCode, $externalCodes, true)) {
        $externalStmt = $db->prepare("
            SELECT AVG(rating) FROM stakeholder_responses
            WHERE cycle_id = ? AND indicator_id = ?
        ");
        $externalStmt->execute([$cycleId, $indicatorId]);
        $externalRating = $externalStmt->fetchColumn();
        if ($externalRating !== false && $externalRating !== null) {
            $ratings[] = (float) $externalRating;
        }
    }

    return $ratings ? array_sum($ratings) / count($ratings) : null;
}

function getSuggestionPlanNextCycleOutcome(PDO $db, int $planId, int $schoolId): ?array
{
    sfAssertSchoolAccess($db, $schoolId);
    $planStmt = $db->prepare("
        SELECT ip.plan_id, ip.cycle_id, ip.school_id, ip.dimension_id,
               ip.indicator_id, c.sy_id, sy.date_start AS school_year_start
        FROM improvement_plans ip
        JOIN sbm_cycles c ON c.cycle_id = ip.cycle_id
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE ip.plan_id = ? AND ip.school_id = ?
        LIMIT 1
    ");
    $planStmt->execute([$planId, $schoolId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan || $plan['indicator_id'] === null) {
        return null;
    }

    $nextCycleStmt = $db->prepare("
        SELECT c.cycle_id, c.sy_id, sy.label AS school_year_label, sy.date_start
        FROM sbm_cycles c
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE c.school_id = ? AND c.status = 'finalized'
          AND sy.date_start > ?
        ORDER BY sy.date_start, c.created_at
        LIMIT 1
    ");
    $nextCycleStmt->execute([$schoolId, $plan['school_year_start']]);
    $nextCycle = $nextCycleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$nextCycle) {
        return null;
    }

    $indicatorStmt = $db->prepare("
        SELECT i.indicator_code, d.dimension_name
        FROM sbm_indicators i
        JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
        WHERE i.indicator_id = ? AND d.dimension_id = ?
        LIMIT 1
    ");
    $indicatorStmt->execute([(int) $plan['indicator_id'], (int) $plan['dimension_id']]);
    $indicator = $indicatorStmt->fetch(PDO::FETCH_ASSOC);
    if (!$indicator) {
        return null;
    }

    $matchStmt = $db->prepare("
        SELECT i.indicator_id
        FROM sbm_indicators i
        JOIN sbm_dimensions d ON d.dimension_id = i.dimension_id
        WHERE i.form_version_id = (
            SELECT d2.form_version_id
            FROM sbm_cycles c2
            JOIN sbm_dimension_scores ds2 ON ds2.cycle_id = c2.cycle_id
            JOIN sbm_dimensions d2 ON d2.dimension_id = ds2.dimension_id
            WHERE c2.cycle_id = ?
            ORDER BY d2.dimension_no
            LIMIT 1
        )
          AND i.indicator_code = ?
          AND d.dimension_name = ?
        LIMIT 1
    ");
    $matchStmt->execute([
        (int) $nextCycle['cycle_id'],
        $indicator['indicator_code'],
        $indicator['dimension_name'],
    ]);
    $nextIndicatorId = $matchStmt->fetchColumn();
    if (!$nextIndicatorId) {
        return null;
    }

    $baselineScore = sfIndicatorRating(
        $db,
        (int) $plan['cycle_id'],
        $schoolId,
        (int) $plan['indicator_id']
    );
    $nextScore = sfIndicatorRating(
        $db,
        (int) $nextCycle['cycle_id'],
        $schoolId,
        (int) $nextIndicatorId
    );
    if ($baselineScore === null || $nextScore === null) {
        return null;
    }

    return [
        'plan_id' => (int) $plan['plan_id'],
        'indicator_code' => $indicator['indicator_code'],
        'dimension_name' => $indicator['dimension_name'],
        'baseline_cycle_id' => (int) $plan['cycle_id'],
        'baseline_score' => $baselineScore,
        'next_cycle_id' => (int) $nextCycle['cycle_id'],
        'next_school_year' => $nextCycle['school_year_label'],
        'next_score' => $nextScore,
        'change' => round($nextScore - $baselineScore, 4),
    ];
}
