<?php
// ============================================================
// cron/run_cron_via_http.php
//
// A secure HTTP endpoint that runs the stakeholder cron logic
// for environments where direct shell/crontab access is not
// available (e.g. shared hosting).
//
// Usage:
//   curl -s "https://yourdomain.com/sbm/cron/run_cron_via_http.php?key=YOUR_SECRET_KEY"
//
// Or schedule via cPanel / Plesk "Cron Jobs" pointing to:
//   /usr/bin/php /path/to/run_cron_via_http.php
//
// Protect this endpoint with a secret key stored in .env:
//   SBM_CRON_SECRET=change_me_to_something_random
// ============================================================
declare(strict_types=1);

define('RUNNING_FROM_CRON', true);

$root = dirname(__DIR__);
require_once $root . '/config/db.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/stakeholder_lifecycle.php';

// ── Auth ─────────────────────────────────────────────────────
$secret = $_ENV['SBM_CRON_SECRET'] ?? '';
$key = $_GET['key'] ?? ($_SERVER['HTTP_X_CRON_KEY'] ?? '');

if (!$secret || !hash_equals($secret, $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized.']);
    exit;
}

// ── Run ──────────────────────────────────────────────────────
$db = getDB();
$log = [];

try {
    $warn = runStakeholderExpiryWarnings($db);
    $log['warnings_sent'] = $warn['sent'];
} catch (\Throwable $e) {
    $log['warnings_error'] = $e->getMessage();
}

try {
    $deact = runStakeholderAutoDeactivation($db, 'cron');
    $log['deactivation'] = $deact;
} catch (\Throwable $e) {
    $log['deactivation_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'ts' => date('Y-m-d H:i:s'), 'log' => $log], JSON_PRETTY_PRINT);