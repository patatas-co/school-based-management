#!/usr/bin/env php
<?php
// ============================================================
// cron/stakeholder_lifecycle_cron.php
//
// Purpose:
//   1. Send expiry-warning emails N days before access ends.
//   2. Auto-deactivate evaluator accounts when cycle ends.
//
// Recommended schedule (crontab):
//   # Run at 07:00 Philippine time every day
//   0 7 * * *  php /var/www/html/sbm/cron/stakeholder_lifecycle_cron.php >> /var/log/sbm_cron.log 2>&1
//
// You can also invoke it via HTTP from a secure endpoint
// (see the web wrapper below) if shell access is unavailable.
// ============================================================
declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────
define('RUNNING_FROM_CRON', true);

$root = dirname(__DIR__);           // adjust if cron/ is not under project root
require_once $root . '/config/db.php';
require_once $root . '/includes/auth.php';          // for baseUrl()
require_once $root . '/includes/stakeholder_lifecycle.php';

$db = getDB();
$ts = date('Y-m-d H:i:s');

echo "[{$ts}] === Stakeholder Lifecycle Cron START ===\n";

// ── Step 1 – Send expiry-warning emails ──────────────────────
try {
    $warn = runStakeholderExpiryWarnings($db);
    echo "[{$ts}] Warning emails sent: {$warn['sent']}\n";
} catch (\Throwable $e) {
    echo "[{$ts}] ERROR (warnings): " . $e->getMessage() . "\n";
}

// ── Step 2 – Auto-deactivate expired evaluators ──────────────
try {
    $deact = runStakeholderAutoDeactivation($db, 'cron');

    if (isset($deact['skipped'])) {
        echo "[{$ts}] Auto-deactivation skipped ({$deact['skipped']})\n";
    } else {
        echo "[{$ts}] Accounts deactivated: {$deact['deactivated']}\n";
        foreach ($deact['cycles'] as $cyc) {
            echo "[{$ts}]   Cycle #{$cyc['cycle_id']} ({$cyc['sy_label']} | {$cyc['school']})"
                . " — {$cyc['count']} accounts, ended {$cyc['ended_at']}\n";
        }
    }
} catch (\Throwable $e) {
    echo "[{$ts}] ERROR (deactivation): " . $e->getMessage() . "\n";
}

echo "[{$ts}] === Stakeholder Lifecycle Cron END ===\n";