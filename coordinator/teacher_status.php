<?php
// coordinator/teacher_status.php — Coordinator Wrapper
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sbm_coordinator', 'admin');
$_COORDINATOR_VIEW = true;
include __DIR__.'/../school_head/teacher_status.php';
