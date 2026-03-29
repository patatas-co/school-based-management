<?php
// coordinator/analytics.php — Read-only analytics for SBM Coordinator
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sbm_coordinator','admin');

// Reuse the admin analytics page content
// The coordinator has read-only access (no admin controls shown)
$_COORDINATOR_VIEW = true;
include __DIR__.'/../admin/analytics.php';