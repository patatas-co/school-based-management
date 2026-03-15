<?php
// ============================================================
// sdo/workflow.php — School Year Timeline & Workflow Module
// SBM 3-Step Cycle Enforcement (DepEd Order No. 007, s. 2024)
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo', 'admin');
include __DIR__.'/../includes/workflow_core.php';