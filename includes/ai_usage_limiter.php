<?php
// includes/ai_usage_limiter.php
// Server-side enforcement of AI Suggestion generation limits.
// - AI_USAGE_DAILY_LIMIT generations per user per UTC day.
// - AI_USAGE_COOLDOWN_SECONDS between consecutive generations.
// Race-condition safe via row-level locking (SELECT ... FOR UPDATE).

define('AI_USAGE_DAILY_LIMIT', 6);
define('AI_USAGE_COOLDOWN_SECONDS', 20);

// Separate, smaller limit for the per-field "Generate Objective" /
// "Generate Strategy" buttons in the Improvement Plan modal — each
// field type gets its own daily quota (3 + 3, not a shared pool of 3).
define('AI_IP_FIELD_DAILY_LIMIT', 3);

// Separate daily quota for the "Import from Document" feature in Manage Form —
// each import costs one LLM call over a potentially large document.
define('DOC_IMPORT_DAILY_LIMIT', 10);

/**
 * Read-only status check (no locking, no mutation). Used to restore
 * button/timer state on page load without consuming a generation.
 */
function aiUsageGetStatus(PDO $db, int $userId): array
{
    $todayUtc = gmdate('Y-m-d');

    $stmt = $db->prepare("SELECT usage_count, last_generated_at, reset_date, last_recommendation FROM ai_suggestion_usage WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    // last_recommendation persists across days/refresh/logout — it's only
    // ever replaced by a new successful generation, never cleared by a
    // daily reset. So it's read independently of the reset_date check below.
    $lastRecommendation = $row['last_recommendation'] ?? null;
    $lastGeneratedAtRaw = $row['last_generated_at'] ?? null;

    if (!$row || $row['reset_date'] !== $todayUtc) {
        return [
            'used' => 0,
            'remaining' => AI_USAGE_DAILY_LIMIT,
            'limit' => AI_USAGE_DAILY_LIMIT,
            'cooldown_remaining' => 0,
            'last_recommendation' => $lastRecommendation,
            'last_generated_at' => $lastGeneratedAtRaw,
        ];
    }

    $used = (int) $row['usage_count'];
    $cooldownRemaining = 0;
    if ($row['last_generated_at']) {
        $elapsed = time() - strtotime($row['last_generated_at'] . ' UTC');
        $cooldownRemaining = max(0, AI_USAGE_COOLDOWN_SECONDS - $elapsed);
    }

    return [
        'used' => $used,
        'remaining' => max(0, AI_USAGE_DAILY_LIMIT - $used),
        'limit' => AI_USAGE_DAILY_LIMIT,
        'cooldown_remaining' => $cooldownRemaining,
        'last_recommendation' => $lastRecommendation,
        'last_generated_at' => $lastGeneratedAtRaw,
    ];
}

/**
 * Persists the latest successful recommendation text so it survives
 * page refresh / logout. Does NOT touch usage_count or cooldown.
 */
function aiUsageSaveRecommendation(PDO $db, int $userId, string $text): void
{
    $stmt = $db->prepare("UPDATE ai_suggestion_usage SET last_recommendation = ? WHERE user_id = ?");
    $stmt->execute([$text, $userId]);
}

/**
 * Read-only status check for the Objective/Strategy generate buttons.
 * No locking, no mutation — used to restore button/label state on load.
 */
function ipFieldUsageGetStatus(PDO $db, int $userId, string $fieldType): array
{
    $todayUtc = gmdate('Y-m-d');

    $stmt = $db->prepare("SELECT usage_count, reset_date FROM ip_field_usage WHERE user_id = ? AND field_type = ? LIMIT 1");
    $stmt->execute([$userId, $fieldType]);
    $row = $stmt->fetch();

    if (!$row || $row['reset_date'] !== $todayUtc) {
        return [
            'used' => 0,
            'remaining' => AI_IP_FIELD_DAILY_LIMIT,
            'limit' => AI_IP_FIELD_DAILY_LIMIT,
        ];
    }

    $used = (int) $row['usage_count'];
    return [
        'used' => $used,
        'remaining' => max(0, AI_IP_FIELD_DAILY_LIMIT - $used),
        'limit' => AI_IP_FIELD_DAILY_LIMIT,
    ];
}

/**
 * Atomically validates + consumes one document import for this user
 * (shared with no other quota — its own row keyed by user_id only,
 * reusing the ip_field_usage table with a fixed field_type value so no
 * new table is needed).
 */
function docImportCheckAndConsume(PDO $db, int $userId): array
{
    $todayUtc = gmdate('Y-m-d');

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT usage_count, reset_date FROM doc_import_usage WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            $ins = $db->prepare("INSERT INTO doc_import_usage (user_id, usage_count, reset_date) VALUES (?, 0, ?)");
            $ins->execute([$userId, $todayUtc]);
            $usageCount = 0;
        } elseif ($row['reset_date'] !== $todayUtc) {
            $usageCount = 0;
        } else {
            $usageCount = (int) $row['usage_count'];
        }

        if ($usageCount >= DOC_IMPORT_DAILY_LIMIT) {
            $db->rollBack();
            return [
                'allowed' => false,
                'reason' => 'daily_limit',
                'message' => "You've reached today's limit for document imports ({$usageCount}/" . DOC_IMPORT_DAILY_LIMIT . ").",
                'remaining' => 0,
                'limit' => DOC_IMPORT_DAILY_LIMIT,
            ];
        }

        $newCount = $usageCount + 1;
        $upd = $db->prepare("UPDATE doc_import_usage SET usage_count = ?, reset_date = ? WHERE user_id = ?");
        $upd->execute([$newCount, $todayUtc, $userId]);

        $db->commit();

        return [
            'allowed' => true,
            'remaining' => max(0, DOC_IMPORT_DAILY_LIMIT - $newCount),
            'limit' => DOC_IMPORT_DAILY_LIMIT,
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}

/**
 * Atomically validates + consumes one Objective/Strategy generation for
 * this user + field type. Locks the row for the duration of the check
 * to prevent double-submit / race conditions, same pattern as
 * aiUsageCheckAndConsume() below.
 */
function ipFieldUsageCheckAndConsume(PDO $db, int $userId, string $fieldType): array
{
    $todayUtc = gmdate('Y-m-d');

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT usage_count, reset_date FROM ip_field_usage WHERE user_id = ? AND field_type = ? FOR UPDATE");
        $stmt->execute([$userId, $fieldType]);
        $row = $stmt->fetch();

        if (!$row) {
            $ins = $db->prepare("INSERT INTO ip_field_usage (user_id, field_type, usage_count, reset_date) VALUES (?, ?, 0, ?)");
            $ins->execute([$userId, $fieldType, $todayUtc]);
            $usageCount = 0;
        } elseif ($row['reset_date'] !== $todayUtc) {
            $usageCount = 0;
        } else {
            $usageCount = (int) $row['usage_count'];
        }

        if ($usageCount >= AI_IP_FIELD_DAILY_LIMIT) {
            $db->rollBack();
            $label = $fieldType === 'objective' ? 'objectives' : 'strategies';
            return [
                'allowed' => false,
                'reason' => 'daily_limit',
                'message' => "You've reached today's limit for AI-generated {$label} ({$fieldType}).",
                'remaining' => 0,
                'limit' => AI_IP_FIELD_DAILY_LIMIT,
            ];
        }

        $newCount = $usageCount + 1;
        $upd = $db->prepare("UPDATE ip_field_usage SET usage_count = ?, reset_date = ? WHERE user_id = ? AND field_type = ?");
        $upd->execute([$newCount, $todayUtc, $userId, $fieldType]);

        $db->commit();

        return [
            'allowed' => true,
            'remaining' => max(0, AI_IP_FIELD_DAILY_LIMIT - $newCount),
            'limit' => AI_IP_FIELD_DAILY_LIMIT,
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}

/**
 * Atomically validates the daily limit + cooldown, and if allowed,
 * consumes one generation. Locks the user's row for the duration of
 * the check to prevent double-submit / race conditions.
 */
function aiUsageCheckAndConsume(PDO $db, int $userId): array
{
    $todayUtc = gmdate('Y-m-d');

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT usage_count, last_generated_at, reset_date FROM ai_suggestion_usage WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            $ins = $db->prepare("INSERT INTO ai_suggestion_usage (user_id, usage_count, last_generated_at, reset_date) VALUES (?, 0, NULL, ?)");
            $ins->execute([$userId, $todayUtc]);
            $usageCount = 0;
            $lastGeneratedAt = null;
        } elseif ($row['reset_date'] !== $todayUtc) {
            $usageCount = 0;
            $lastGeneratedAt = null;
        } else {
            $usageCount = (int) $row['usage_count'];
            $lastGeneratedAt = $row['last_generated_at'];
        }

        // ── Cooldown ──
        if ($lastGeneratedAt) {
            $elapsed = time() - strtotime($lastGeneratedAt . ' UTC');
            if ($elapsed < AI_USAGE_COOLDOWN_SECONDS) {
                $db->rollBack();
                $retryAfter = AI_USAGE_COOLDOWN_SECONDS - $elapsed;
                return [
                    'allowed' => false,
                    'reason' => 'cooldown',
                    'retry_after' => $retryAfter,
                    'message' => "Please wait {$retryAfter} seconds before generating again.",
                    'remaining' => max(0, AI_USAGE_DAILY_LIMIT - $usageCount),
                    'limit' => AI_USAGE_DAILY_LIMIT,
                ];
            }
        }

        // ── Daily limit ──
        if ($usageCount >= AI_USAGE_DAILY_LIMIT) {
            $db->rollBack();
            return [
                'allowed' => false,
                'reason' => 'daily_limit',
                'retry_after' => 0,
                'message' => "You have reached today's generation limit. Please try again tomorrow.",
                'remaining' => 0,
                'limit' => AI_USAGE_DAILY_LIMIT,
            ];
        }

        // ── Allowed: consume ──
        $newCount = $usageCount + 1;
        $upd = $db->prepare("UPDATE ai_suggestion_usage SET usage_count = ?, last_generated_at = UTC_TIMESTAMP(), reset_date = ? WHERE user_id = ?");
        $upd->execute([$newCount, $todayUtc, $userId]);

        $db->commit();

        return [
            'allowed' => true,
            'remaining' => max(0, AI_USAGE_DAILY_LIMIT - $newCount),
            'limit' => AI_USAGE_DAILY_LIMIT,
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}