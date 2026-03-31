<?php
/**
 * ml/ml_worker_check.php
 * Diagnostic script to check the health of the SBM ML microservice.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ml_service.php';

// Only school_head can run diagnostics
requireRole('school_head');

header('Content-Type: application/json');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks'    => [],
    'status'    => 'ok'
];

// 1. Check Python Service
$ch = curl_init(ML_SERVICE_URL . '/health');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_HTTPHEADER     => ['X-ML-Secret: ' . ML_SECRET]
]);
$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    $results['checks']['python_service'] = [
        'ok'    => false,
        'msg'   => "Service unreachable: $error",
        'url'   => ML_SERVICE_URL
    ];
    $results['status'] = 'warning';
} elseif ($httpCode !== 200) {
    $results['checks']['python_service'] = [
        'ok'    => false,
        'msg'   => "Service returned HTTP $httpCode",
        'url'   => ML_SERVICE_URL
    ];
    $results['status'] = 'warning';
} else {
    $health = json_decode($body, true);
    $results['checks']['python_service'] = [
        'ok'      => true,
        'msg'     => "Python service is healthy.",
        'details' => $health
    ];
}

// 2. Check Database Tables
$db = getDB();
$tables = ['ml_predictions', 'ml_recommendations', 'ml_comment_analysis', 'ml_training_snapshots'];
foreach ($tables as $t) {
    try {
        $db->query("SELECT 1 FROM $t LIMIT 1");
        $results['checks']["table_$t"] = ['ok' => true];
    } catch (Exception $e) {
        $results['checks']["table_$t"] = ['ok' => false, 'msg' => $e->getMessage()];
        $results['status'] = 'error';
    }
}

// 3. Check ML Directory Permissions
$mlDir = __DIR__;
$results['checks']['ml_directory'] = [
    'ok'       => is_writable($mlDir),
    'path'     => $mlDir,
    'readable' => is_readable($mlDir)
];

echo json_encode($results, JSON_PRETTY_PRINT);
exit;
