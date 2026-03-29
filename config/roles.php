<?php
// ============================================================
// config/roles.php — Centralized Role Permissions
// DIHS SBM Online Monitoring System
// ============================================================

define('ROLE_SCHOOL_HEAD',  'school_head');
define('ROLE_COORDINATOR',  'sbm_coordinator');
define('ROLE_TEACHER',      'teacher');
define('ROLE_STAKEHOLDER',  'external_stakeholder');

define('SBM_MODULE_ACCESS', [

    // ── System Administration (School Head only) ────────────
    'user_management'                  => [ROLE_SCHOOL_HEAD],
    'system_settings'                  => [ROLE_SCHOOL_HEAD],
    'school_years'                     => [ROLE_SCHOOL_HEAD],

    // ── School-Level Configuration ──────────────────────────
    'school_profile'                   => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Dashboards ──────────────────────────────────────────
    'school_head_dashboard'            => [ROLE_SCHOOL_HEAD],
    'coordinator_dashboard'            => [ROLE_COORDINATOR],
    'teacher_dashboard'                => [ROLE_TEACHER],
    'stakeholder_dashboard'            => [ROLE_STAKEHOLDER],

    // ── Analytics ───────────────────────────────────────────
    'analytics'                        => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'analytics_export'                 => [ROLE_SCHOOL_HEAD],

    // ── Assessment Lifecycle ─────────────────────────────────
    'start_assessment'                 => [ROLE_SCHOOL_HEAD],
    'close_assessment'                 => [ROLE_SCHOOL_HEAD],
    'reopen_assessment'                => [ROLE_SCHOOL_HEAD],
    'sh_self_assessment'               => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'teacher_self_assessment'          => [ROLE_TEACHER],
    'stakeholder_assessment'           => [ROLE_STAKEHOLDER],
    'submit_assessment'                => [ROLE_SCHOOL_HEAD],
    'override_teacher_rating'          => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'override_coordinator_assignments' => [ROLE_SCHOOL_HEAD],

    'assign_indicators'                => [ROLE_COORDINATOR],

    // ── Assessment Validation ────────────────────────────────
    'view_assessments'                 => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'validate_assessment'              => [ROLE_SCHOOL_HEAD],

    // ── Improvement Plan ────────────────────────────────────
    'improvement_plan'                 => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'improvement_plan_view'            => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Reports ─────────────────────────────────────────────
    'reports_school'                   => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Monitoring ──────────────────────────────────────────
    'monitor_teachers'                 => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Workflow / Timeline ──────────────────────────────────
    'workflow_configure'               => [ROLE_SCHOOL_HEAD],
    'workflow_view'                    => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Announcements ───────────────────────────────────────
    'announcement_post'                => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'announcement_view'                => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR,
                                          ROLE_TEACHER, ROLE_STAKEHOLDER],
]);

define('SBM_NAV', [

    ROLE_SCHOOL_HEAD => [
        ['Overview', 'grid', [
            ['Dashboard',             'school_head/dashboard.php',            'grid'],
            ['Analytics',             'school_head/analytics.php',            'bar-chart-2'],
        ]],
        ['Management', 'users', [
            ['User Accounts',         'school_head/users.php',                'users'],
            ['School Profile',        'school_head/school_profile.php',       'home'],
            ['School Years',          'school_head/settings.php',             'calendar'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',        'school_head/self_assessment.php',      'check-circle'],
            ['SBM Assessments',        'school_head/assessment.php',           'clipboard'],
            ['Reports',                'school_head/reports.php',              'file-text'],
        ]],
        ['Workflow & SIP', 'trending-up', [
            ['Workflow Overview',      'school_head/workflow.php',             'trending-up'],
            ['Improvement Plan',       'school_head/improvement.php',          'award'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',          'school_head/announcements.php',        'bell'],
        ]],
        ['System', 'settings', [
            ['Settings',               'school_head/settings.php',             'settings'],
        ]],
    ],

    ROLE_COORDINATOR => [
        ['Overview', 'grid', [
            ['Dashboard',             'coordinator/dashboard.php',             'grid'],
            ['Analytics',             'coordinator/analytics.php',             'bar-chart-2'],
            ['SBM Dimensions',        'coordinator/dimensions.php',            'layers'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',        'coordinator/self_assessment.php',       'check-circle'],
            ['Assign Indicators',      'coordinator/assign_indicators.php',     'check-square'],
            ['Teacher Status',         'coordinator/teacher_status.php',        'users'],
            ['Evidence & MOV',         'coordinator/evidence.php',              'paperclip'],
        ]],
        ['Planning', 'trending-up', [
            ['Improvement Plan',       'coordinator/improvement.php',           'trending-up'],
            ['Reports',                'coordinator/reports.php',               'file-text'],
        ]],
        ['School', 'home', [
            ['School Profile',         'coordinator/school_profile.php',        'home'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',          'coordinator/announcements.php',         'bell'],
        ]],
    ],

    ROLE_TEACHER => [
        ['Overview', 'grid', [
            ['Dashboard',             'teacher/dashboard.php',                  'grid'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',        'teacher/self_assessment.php',            'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',          'teacher/announcements.php',              'bell'],
        ]],
    ],

    ROLE_STAKEHOLDER => [
        ['Overview', 'grid', [
            ['Dashboard',             'stakeholder/dashboard.php',               'grid'],
        ]],
        ['Participation', 'users', [
            ['Self-Assessment',        'stakeholder/self_assessment.php',         'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',          'stakeholder/announcement.php',            'bell'],
        ]],
    ],

]);

function hasAccess(string $module, ?string $role = null): bool {
    if ($role === null) {
        $role = $_SESSION['role'] ?? '';
    }
    $allowed = SBM_MODULE_ACCESS[$module] ?? [];
    return in_array($role, $allowed, true);
}

function requireAccess(string $module): void {
    if (!hasAccess($module)) {
        $role = $_SESSION['role'] ?? 'guest';
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Access denied. Your role ('.$role.') cannot perform this action.',
            ]);
            exit;
        }
        http_response_code(403);
        echo '<div style="font-family:sans-serif;padding:40px;text-align:center;">'
            .'<h2>Access Denied</h2>'
            .'<p>Your role does not have permission to view this page.</p>'
            .'<a href="javascript:history.back()">Go Back</a></div>';
        exit;
    }
}