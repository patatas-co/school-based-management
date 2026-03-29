<?php
// admin/self_assessment.php — Admin (School Head) self-assessment access
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
// Delegate to school_head self_assessment — same functionality
include __DIR__.'/../school_head/self_assessment.php';
?>
