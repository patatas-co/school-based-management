<?php
// Background worker: called by exec() with cycle_id as argv[1]
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ml_service.php';

$cycleId = (int)($argv[1] ?? 0);
if (!$cycleId) exit(1);

$db = getDB();
$ok = runMLPipeline($db, $cycleId);
exit($ok ? 0 : 1);