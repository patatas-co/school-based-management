<?php
// ============================================================
// config/roles.php — Centralized Role Permissions
// DIHS SBM Online Monitoring System
// ============================================================

/**
 * ROLE DEFINITIONS
 * ─────────────────────────────────────────────────────────────
 * admin                — System Administrator (School Head):
 *                        full access, user management, school
 *                        years, system config, validation
 * sbm_coordinator      — SBM Coordinator: manages assessment
 *                        cycle, analytics, improvement plans,
 *                        reporting; read-only system config
 * teacher              — Teacher / Evaluator: checklist
 *                        completion for assigned indicators
 * external_stakeholder — External Stakeholder: checklist
 *                        completion for stakeholder indicators
 */

define('ROLE_ADMIN',       'admin');
define('ROLE_COORDINATOR', 'sbm_coordinator');
define('ROLE_TEACHER',     'teacher');
define('ROLE_STAKEHOLDER', 'external_stakeholder');

/**
 * Module-level access map.
 * Key   = module identifier
 * Value = array of roles allowed
 */
define('SBM_MODULE_ACCESS', [

    // ── System Administration (Admin only) ──────────────────
    'user_management'           => [ROLE_ADMIN],
    'system_settings'           => [ROLE_ADMIN],
    'school_years'              => [ROLE_ADMIN],

    // ── School-Level Configuration ──────────────────────────
    'school_profile'            => [ROLE_ADMIN, ROLE_COORDINATOR],

    // ── Dashboards ──────────────────────────────────────────
    'admin_dashboard'           => [ROLE_ADMIN],
    'coordinator_dashboard'     => [ROLE_COORDINATOR],
    'teacher_dashboard'         => [ROLE_TEACHER],
    'stakeholder_dashboard'     => [ROLE_STAKEHOLDER],

    // ── Analytics ───────────────────────────────────────────
    // Admin: full access; Coordinator: read-only
    'analytics'                 => [ROLE_ADMIN, ROLE_COORDINATOR],
    'analytics_export'          => [ROLE_ADMIN],

    // ── Assessment Lifecycle ─────────────────────────────────
    // Start/manage cycle: Admin + Coordinator
    'start_assessment'          => [ROLE_ADMIN, ROLE_COORDINATOR],
    // Fill SH/coordinator indicators
    'sh_self_assessment'        => [ROLE_ADMIN, ROLE_COORDINATOR],
    // Fill teacher indicators
    'teacher_self_assessment'   => [ROLE_TEACHER],
    // Fill stakeholder indicators
    'stakeholder_assessment'    => [ROLE_STAKEHOLDER],
    // Submit final assessment: Admin + Coordinator
    'submit_assessment'         => [ROLE_ADMIN, ROLE_COORDINATOR],
    // Override teacher ratings: Admin + Coordinator
    'override_teacher_rating'   => [ROLE_ADMIN, ROLE_COORDINATOR],

    // ── Assessment Validation ────────────────────────────────
    // View submitted assessments: Admin + Coordinator
    'view_assessments'          => [ROLE_ADMIN, ROLE_COORDINATOR],
    // Validate/Return: Admin only
    'validate_assessment'       => [ROLE_ADMIN],

    // ── Improvement Plan ────────────────────────────────────
    'improvement_plan'          => [ROLE_ADMIN, ROLE_COORDINATOR],
    'improvement_plan_view'     => [ROLE_ADMIN, ROLE_COORDINATOR],

    // ── Reports ─────────────────────────────────────────────
    'reports_school'            => [ROLE_ADMIN, ROLE_COORDINATOR],

    // ── Monitoring ──────────────────────────────────────────
    'monitor_teachers'          => [ROLE_ADMIN, ROLE_COORDINATOR],

    // ── Workflow / Timeline ──────────────────────────────────
    'workflow_configure'        => [ROLE_ADMIN],
    'workflow_view'             => [ROLE_ADMIN, ROLE_COORDINATOR],

    // ── Announcements ───────────────────────────────────────
    'announcement_post'         => [ROLE_ADMIN, ROLE_COORDINATOR],
    'announcement_view'         => [ROLE_ADMIN, ROLE_COORDINATOR,
                                    ROLE_TEACHER, ROLE_STAKEHOLDER],
]);

/**
 * Navigation menus per role.
 * Used by header.php to build the sidebar.
 */
define('SBM_NAV', [

    ROLE_ADMIN => [
        ['Overview', 'grid', [
            ['Dashboard',         'admin/dashboard.php',      'grid'],
            ['Analytics',         'admin/analytics.php',      'bar-chart-2'],
        ]],
        ['Management', 'users', [
            ['User Accounts',     'admin/users.php',          'users'],
            ['School Profile',    'admin/school_profile.php', 'home'],
            ['School Years',      'admin/settings.php',       'calendar'],
        ]],
        ['Evaluation', 'check-circle', [
            ['SBM Assessments',   'admin/assessment.php',     'check-circle'],
            ['Workflow & SIP',    'admin/workflow.php',       'trending-up'],
            ['Reports',           'admin/reports.php',        'file-text'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'admin/announcements.php',  'bell'],
        ]],
        ['System', 'settings', [
            ['Settings',          'admin/settings.php',       'settings'],
        ]],
    ],

    ROLE_COORDINATOR => [
        ['Overview', 'grid', [
            ['Dashboard',         'coordinator/dashboard.php',         'grid'],
            ['Analytics',         'coordinator/analytics.php',         'bar-chart-2'],
            ['SBM Dimensions',    'coordinator/dimensions.php',        'layers'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',   'coordinator/self_assessment.php',   'check-circle'],
            ['Teacher Status',    'coordinator/teacher_status.php',    'users'],
            ['Evidence & MOV',    'coordinator/evidence.php',          'paperclip'],
        ]],
        ['Planning', 'trending-up', [
            ['Improvement Plan',  'coordinator/improvement.php',       'trending-up'],
            ['Reports',           'coordinator/reports.php',           'file-text'],
        ]],
        ['School', 'home', [
            ['School Profile',    'coordinator/school_profile.php',    'home'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'coordinator/announcements.php',     'bell'],
        ]],
    ],

    ROLE_TEACHER => [
        ['Overview', 'grid', [
            ['Dashboard',         'teacher/dashboard.php',             'grid'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',   'teacher/self_assessment.php',       'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'teacher/announcements.php',         'bell'],
        ]],
    ],

    ROLE_STAKEHOLDER => [
        ['Overview', 'grid', [
            ['Dashboard',         'stakeholder/dashboard.php',         'grid'],
        ]],
        ['Participation', 'users', [
            ['Self-Assessment',   'stakeholder/self_assessment.php',   'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'stakeholder/announcement.php',      'bell'],
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