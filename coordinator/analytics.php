<?php
// coordinator/analytics.php — Read-only analytics for SBM Coordinator
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('sbm_coordinator');
$_COORDINATOR_VIEW = true;
// compare_sy and sy GET params are forwarded automatically via $_GET
include __DIR__ . '/../school_head/analytics.php';