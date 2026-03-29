<?php
// ============================================================
// config/roles.php — Centralized Role Permissions
// DIHS SBM Online Monitoring System
// ============================================================

/**
 * ROLE DEFINITIONS
 * ─────────────────────────────────────────────────────────────
 * school_head          — School Head (Top-level):
 *                        full access, user management, school
 *                        years, system config, validation,
 *                        assessments, workflow, analytics
 * sbm_coordinator      — SBM Coordinator: manages assessment
 *                        cycle, analytics, improvement plans,
 *                        reporting; read-only system config
 * teacher              — Teacher / Evaluator: checklist
 *                        completion for assigned indicators
 * external_stakeholder — External Stakeholder: checklist
 *                        completion for stakeholder indicators
 */

define('ROLE_SCHOOL_HEAD',  'school_head');
define('ROLE_COORDINATOR',  'sbm_coordinator');
define('ROLE_TEACHER',      'teacher');
define('ROLE_STAKEHOLDER',  'external_stakeholder');

/**
 * Module-level access map.
 * Key   = module identifier
 * Value = array of roles allowed
 */
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

    // Assign indicators to teachers: School Head + Coordinator
    'assign_indicators'                => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

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

/**
 * Navigation menus per role.
 * Used by header.php to build the sidebar.
 */
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
            ['SBM Assessments',        'school_head/assessment.php',           'check-circle'],
            ['Assign Indicators',      'coordinator/assign_indicators.php',    'check-square'],
            ['Indicator Assignments',  'school_head/view_assignments.php',     'list'],
            ['Reports',                'school_head/reports.php',              'file-text'],
        ]],
        ['Workflow & SIP', 'trending-up', [
            ['Workflow Overview',      'school_head/workflow.php',             'trending-up'],
            ['Improvement Plan',       'school_head/improvement.php',          'trending-up'],
            ['TA Requests',            'school_head/improvement.php',          'briefcase'],
            ['Timeline',               'school_head/workflow.php',             'calendar'],
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

/**
 * hasAccess — check if a role can perform a module action.
 */
function hasAccess(string $module, ?string $role = null): bool {
    if ($role === null) {
        $role = $_SESSION['role'] ?? '';
    }
    $allowed = SBM_MODULE_ACCESS[$module] ?? [];
    return in_array($role, $allowed, true);
}

/**
 * requireAccess — gate a page to specific module access.
 */
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