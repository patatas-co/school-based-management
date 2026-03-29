<?php
// coordinator/self_assessment.php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sbm_coordinator');
$_COORDINATOR_VIEW = true;
include __DIR__.'/../school_head/self_assessment.php';