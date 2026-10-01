<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

$_SERVER['REQUEST_METHOD'] = 'CLI';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/suggestion_payload.php';
require_once __DIR__ . '/../includes/ml_service.php';
require_once __DIR__ . '/../includes/suggestion_feedback.php';

const HISTORICAL_SUGGESTION_SCHOOL_ID = 1;
const HISTORICAL_SUGGESTION_MAX_RETRIES = 5;

function historicalSuggestionsOpenDatabase(): PDO
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

function historicalSuggestionsUsage(): never
{
    fwrite(STDERR, "Usage: php tools/generate_historical_suggestions.php [--execute] [--years=START-END] [--delay=SECONDS]\n");
    exit(2);
}

function historicalSuggestionsOptions(array $args): array
{
    $options = ['execute' => false, 'start_year' => 2001, 'end_year' => 2025, 'delay' => 1.0];
    foreach ($args as $arg) {
        if ($arg === '--execute') {
            $options['execute'] = true;
        } elseif (preg_match('/^--years=(\d{4})-(\d{4})$/', $arg, $matches)) {
            $options['start_year'] = (int) $matches[1];
            $options['end_year'] = (int) $matches[2];
        } elseif (preg_match('/^--delay=(\d+(?:\.\d+)?)$/', $arg, $matches)) {
            $options['delay'] = (float) $matches[1];
        } else {
            historicalSuggestionsUsage();
        }
    }

    if (
        $options['start_year'] < 1900
        || $options['end_year'] > 2200
        || $options['start_year'] > $options['end_year']
        || $options['delay'] < 0
        || $options['delay'] > 3600
    ) {
        historicalSuggestionsUsage();
    }
    return $options;
}

function historicalSuggestionsWait(float $seconds): void
{
    if ($seconds > 0) {
        usleep((int) min(PHP_INT_MAX, round($seconds * 1_000_000)));
    }
}

function historicalSuggestionsGeneratedAt(string $finalizedAt, int $year): string
{
    $date = new DateTimeImmutable($finalizedAt, new DateTimeZone('Asia/Manila'));
    $date = $date->setTime(0, 0);
    $weekdaysAdded = 0;
    while ($weekdaysAdded < 3) {
        $date = $date->modify('+1 day');
        if ((int) $date->format('N') <= 5) {
            $weekdaysAdded++;
        }
    }
    $timestamp = $date->setTime(10 + ($year % 4), 30)->format('Y-m-d H:i:s');
    if (new DateTimeImmutable($timestamp, new DateTimeZone('Asia/Manila')) > new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'))) {
        throw new RuntimeException('Refusing to backdate a suggestion to a future timestamp.');
    }
    return $timestamp;
}

function historicalSuggestionsErrorText(array $result): string
{
    $body = is_array($result['body'] ?? null) ? $result['body'] : [];
    $errorFields = [
        $body['error'] ?? '',
        $body['message'] ?? '',
        $body['detail'] ?? '',
        $body['code'] ?? '',
    ];
    $errorText = strtolower((string) json_encode($errorFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    if ((int) ($result['http_status'] ?? 0) >= 400) {
        $errorText .= ' ' . strtolower((string) ($result['raw_body'] ?? ''));
    }
    return $errorText;
}

function historicalSuggestionsQuotaExhausted(array $result): bool
{
    $errorText = historicalSuggestionsErrorText($result);
    return preg_match(
        '/insufficient[_ -]?quota|quota.{0,40}(exhausted|exceeded|depleted)|billing.{0,40}(limit|quota)/',
        $errorText
    ) === 1;
}

function historicalSuggestionsRateLimited(array $result): bool
{
    return (int) ($result['http_status'] ?? 0) === 429
        || preg_match(
            '/\b429\b|rate[_ -]?limit|too many requests|request limit exceeded/',
            historicalSuggestionsErrorText($result)
        ) === 1;
}

function historicalSuggestionsMigrationReady(PDO $db): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'ai_suggestion_generations'
          AND column_name = 'is_synthetic'
    ");
    $stmt->execute();
    return (int) $stmt->fetchColumn() === 1;
}

function historicalSuggestionsMain(array $args): int
{
    $options = historicalSuggestionsOptions($args);
    $db = historicalSuggestionsOpenDatabase();
    $schoolId = HISTORICAL_SUGGESTION_SCHOOL_ID;

    $yearsStmt = $db->prepare("
        SELECT c.cycle_id, c.sy_id, c.finalized_at, sy.label,
               CAST(LEFT(sy.label, 4) AS UNSIGNED) AS start_year
        FROM sbm_cycles c
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE c.school_id = ?
          AND c.status = 'finalized'
          AND CAST(LEFT(sy.label, 4) AS UNSIGNED) BETWEEN ? AND ?
        ORDER BY start_year, c.created_at, c.cycle_id
    ");
    $yearsStmt->execute([$schoolId, $options['start_year'], $options['end_year']]);
    $cycles = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$cycles) {
        throw new RuntimeException('No finalized synthetic cycles matched the requested school-year range.');
    }

    $headStmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE role = 'school_head' AND status = 'active' AND school_id = ?
        ORDER BY user_id
        LIMIT 1
    ");
    $headStmt->execute([$schoolId]);
    $schoolHeadId = (int) ($headStmt->fetchColumn() ?: 0);
    if ($schoolHeadId < 1) {
        throw new RuntimeException('No active School Head is available to own the synthetic generation records.');
    }

    if ($options['execute'] && !historicalSuggestionsMigrationReady($db)) {
        throw new RuntimeException('The is_synthetic migration is not applied; no provider calls were made.');
    }

    fwrite(STDOUT, $options['execute'] ? "EXECUTE mode\n" : "DRY RUN (no provider calls or database writes)\n");
    fwrite(STDOUT, sprintf("Range: %d-%d; cycles found: %d\n", $options['start_year'], $options['end_year'], count($cycles)));

    $generationCheck = $db->prepare('SELECT COUNT(*) FROM ai_suggestion_generations WHERE cycle_id = ?');
    $statuses = [];
    $callCount = 0;
    $stopProcessing = false;
    foreach ($cycles as $cycle) {
        $cycleId = (int) $cycle['cycle_id'];
        $syId = (int) $cycle['sy_id'];
        $startYear = (int) $cycle['start_year'];
        $label = (string) $cycle['label'];

        $generationCheck->execute([$cycleId]);
        if ((int) $generationCheck->fetchColumn() > 0) {
            $statuses[$label] = 'already generated (skipped)';
            fwrite(STDOUT, "$label cycle $cycleId: already generated (skipped)\n");
            continue;
        }
        if (empty($cycle['finalized_at'])) {
            $statuses[$label] = 'missing finalized timestamp';
            fwrite(STDOUT, "$label cycle $cycleId: missing finalized timestamp\n");
            continue;
        }

        $suggestionData = buildAiSuggestionPayload($db, $schoolId, $syId, $cycleId, $startYear);
        if ((int) $suggestionData['cycle_id'] !== $cycleId) {
            throw new RuntimeException("Payload builder returned the wrong cycle for $label.");
        }
        if (!$options['execute']) {
            $statuses[$label] = 'ready (dry run only)';
            fwrite(STDOUT, "$label cycle $cycleId: ready; payload hash " .
                hash('sha256', json_encode($suggestionData['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) .
                "\n");
            continue;
        }

        $generationAt = historicalSuggestionsGeneratedAt((string) $cycle['finalized_at'], $startYear);
        if ($callCount > 0) {
            historicalSuggestionsWait($options['delay']);
        }

        $result = null;
        for ($attempt = 1; $attempt <= HISTORICAL_SUGGESTION_MAX_RETRIES; $attempt++) {
            $callCount++;
            $result = ml_post_detailed('/api/recommend', $suggestionData['payload']);
            if ($result['transport_error'] !== null) {
                fwrite(STDERR, "$label cycle $cycleId: transport error: {$result['transport_error']}\n");
                $statuses[$label] = 'not generated (transport error)';
                $stopProcessing = true;
                break;
            }

            $body = $result['body'];
            $status = (int) $result['http_status'];
            if (historicalSuggestionsQuotaExhausted($result)) {
                fwrite(STDERR, "$label cycle $cycleId: provider quota is exhausted; stopping without saving fallback text\n");
                $statuses[$label] = 'not generated (provider quota exhausted)';
                $stopProcessing = true;
                break;
            }
            if (historicalSuggestionsRateLimited($result)) {
                if ($attempt < HISTORICAL_SUGGESTION_MAX_RETRIES) {
                    $backoff = $result['retry_after'] ?? (2 ** $attempt);
                    $backoff = min(300, max(1, (int) $backoff));
                    fwrite(STDERR, "$label cycle $cycleId: rate limited; retry $attempt/" .
                        (HISTORICAL_SUGGESTION_MAX_RETRIES - 1) . " in {$backoff}s\n");
                    historicalSuggestionsWait((float) $backoff);
                    continue;
                }
                fwrite(STDERR, "$label cycle $cycleId: rate limit persisted after retries; stopping\n");
                $statuses[$label] = 'not generated (rate limit persisted)';
                $stopProcessing = true;
                break;
            }
            if ($status >= 200 && $status < 300 && is_array($body)) {
                break;
            }
            fwrite(STDERR, "$label cycle $cycleId: HTTP $status; stopping without saving provider output\n");
            $statuses[$label] = "not generated (HTTP $status)";
            $stopProcessing = true;
            break;
        }

        if ($stopProcessing) {
            break;
        }
        if (!is_array($result['body'] ?? null)) {
            $statuses[$label] = 'not generated (invalid JSON response)';
            fwrite(STDERR, "$label cycle $cycleId: invalid JSON response; stopping\n");
            $stopProcessing = true;
            break;
        }

        $response = $result['body'];
        $backend = strtolower((string) ($response['backend_used'] ?? $response['backend'] ?? ''));
        if (
            !empty($response['error'])
            || strpos($backend, 'rule_based') === 0
            || !is_string($response['recommendations'] ?? null)
            || trim($response['recommendations']) === ''
        ) {
            $statuses[$label] = 'not generated (provider error or fallback response)';
            fwrite(STDERR, "$label cycle $cycleId: provider error or rule-based fallback; no generation saved\n");
            $stopProcessing = true;
            break;
        }

        $teacherDisplayNames = $suggestionData['teacher_display_names'];
        if ($teacherDisplayNames) {
            $response['recommendations'] = strtr($response['recommendations'], $teacherDisplayNames);
            if (is_array($response['blocks'] ?? null)) {
                foreach ($response['blocks'] as &$block) {
                    if (is_array($block) && is_string($block['title'] ?? null)) {
                        $block['title'] = strtr($block['title'], $teacherDisplayNames);
                    }
                }
                unset($block);
            }
        }

        $lowRaterCards = buildLowRaterCards(
            $suggestionData['teacher_summaries'],
            $teacherDisplayNames
        );
        foreach ($lowRaterCards as &$card) {
            $teacherId = (int) ($card['block']['teacher_user_id'] ?? 0);
            $card['block']['indicator_codes'] = array_values(array_unique(
                $suggestionData['low_rater_indicator_codes'][$teacherId] ?? []
            ));
        }
        unset($card);
        $response = mergeLowRaterCardsIntoRecommendation($response, $lowRaterCards);

        saveSuggestionGeneration(
            $db,
            $schoolHeadId,
            $schoolId,
            $cycleId,
            $suggestionData['payload'],
            $response,
            $teacherDisplayNames,
            false,
            ['is_synthetic' => true, 'generated_at' => $generationAt]
        );
        $statuses[$label] = 'generated';
        fwrite(STDOUT, "$label cycle $cycleId: generated at $generationAt\n");
    }

    if ($stopProcessing) {
        foreach ($cycles as $cycle) {
            $label = (string) $cycle['label'];
            if (!isset($statuses[$label])) {
                $statuses[$label] = 'not processed (stopped after provider failure)';
            }
        }
    }

    fwrite(STDOUT, "\nGeneration status by cycle:\n");
    foreach ($statuses as $label => $status) {
        fwrite(STDOUT, "  $label: $status\n");
    }
    $missing = array_keys(array_filter(
        $statuses,
        static fn(string $status): bool => strpos($status, 'generated') !== 0
            && strpos($status, 'already generated') !== 0
    ));
    if ($missing) {
        fwrite(STDOUT, 'Cycles without a generation (rerun to resume): ' . implode(', ', $missing) . "\n");
    } else {
        fwrite(STDOUT, "All matched cycles have a generation or were already complete.\n");
    }
    return $stopProcessing ? 1 : 0;
}

try {
    exit(historicalSuggestionsMain(array_slice($argv, 1)));
} catch (Throwable $error) {
    fwrite(STDERR, 'Historical suggestion command failed: ' . $error->getMessage() . "\n");
    exit(1);
}
