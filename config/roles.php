<?php
// ============================================================
// config/roles.php — Centralized Role Permissions
// DIHS SBM Online Monitoring System
// ============================================================

/**
 * ROLE DEFINITIONS
 * ─────────────────────────────────────────────────────────────
 * admin                — System Administrator: user management,
 *                        school years, system config, full access
 * school_head          — School Head: final submission authority,
 *                        school-level settings, full monitoring,
 *                        teacher/stakeholder oversight, improvement plan
 * teacher              — Teacher: checklist completion only
 * external_stakeholder — External Stakeholder: checklist completion only
 * sdo                  — SDO Officer: validation, TA, division monitoring,
 *                        analytics (read-only)
 * ro                   — Regional Office: regional analytics (read-only),
 *                        announcements
 */

define('ROLE_ADMIN',       'admin');
define('ROLE_SCHOOL_HEAD', 'school_head');
define('ROLE_TEACHER',     'teacher');
define('ROLE_STAKEHOLDER', 'external_stakeholder');
define('ROLE_SDO',         'sdo');
define('ROLE_RO',          'ro');

/**
 * Module-level access map.
 * Key   = module identifier (maps to file/section)
 * Value = array of roles allowed
 */
define('SBM_MODULE_ACCESS', [

    // ── System Administration (Admin only) ──────────────────
    'user_management'           => [ROLE_ADMIN],
    'system_settings'           => [ROLE_ADMIN],
    'school_years'              => [ROLE_ADMIN],

    // ── School-Level Configuration (Admin + School Head) ────
    'school_profile'            => [ROLE_ADMIN, ROLE_SCHOOL_HEAD],

    // ── Dashboards ──────────────────────────────────────────
    'admin_dashboard'           => [ROLE_ADMIN],
    'school_head_dashboard'     => [ROLE_SCHOOL_HEAD],
    'teacher_dashboard'         => [ROLE_TEACHER],
    'stakeholder_dashboard'     => [ROLE_STAKEHOLDER],
    'sdo_dashboard'             => [ROLE_SDO],
    'ro_dashboard'              => [ROLE_RO],

    // ── Analytics (Admin + SDO read-only + RO read-only) ────
    'analytics'                 => [ROLE_ADMIN, ROLE_SDO, ROLE_RO],

    // ── Assessment Lifecycle ─────────────────────────────────
    // Start/manage cycle: School Head only
    'start_assessment'          => [ROLE_SCHOOL_HEAD],
    // Fill SH indicators: School Head
    'sh_self_assessment'        => [ROLE_SCHOOL_HEAD],
    // Fill teacher indicators: Teachers
    'teacher_self_assessment'   => [ROLE_TEACHER],
    // Fill stakeholder indicators: Stakeholders
    'stakeholder_assessment'    => [ROLE_STAKEHOLDER],
    // Submit final assessment: School Head only
    'submit_assessment'         => [ROLE_SCHOOL_HEAD],
    // Override teacher ratings: School Head only
    'override_teacher_rating'   => [ROLE_SCHOOL_HEAD],

    // ── Assessment Validation ────────────────────────────────
    // View submitted assessments: Admin + SDO
    'view_assessments'          => [ROLE_ADMIN, ROLE_SDO, ROLE_RO],
    // Validate/Return: Admin + SDO only
    'validate_assessment'       => [ROLE_ADMIN, ROLE_SDO],

    // ── Improvement Plan ────────────────────────────────────
    // Create/manage: School Head only
    'improvement_plan'          => [ROLE_SCHOOL_HEAD],
    // View (read-only for SDO): School Head + Admin + SDO
    'improvement_plan_view'     => [ROLE_SCHOOL_HEAD, ROLE_ADMIN, ROLE_SDO],

    // ── Reports ─────────────────────────────────────────────
    'reports_school'            => [ROLE_SCHOOL_HEAD, ROLE_ADMIN],
    'reports_division'          => [ROLE_ADMIN, ROLE_SDO, ROLE_RO],

    // ── Monitoring ──────────────────────────────────────────
    'monitor_teachers'          => [ROLE_SCHOOL_HEAD],
    'monitor_schools'           => [ROLE_ADMIN, ROLE_SDO, ROLE_RO],

    // ── Workflow / Timeline ──────────────────────────────────
    // Configure workflow phases: Admin only
    'workflow_configure'        => [ROLE_ADMIN],
    // View workflow: Admin + SDO + School Head
    'workflow_view'             => [ROLE_ADMIN, ROLE_SDO, ROLE_SCHOOL_HEAD],

    // ── Technical Assistance ────────────────────────────────
    // SDO manages TA
    'ta_manage'                 => [ROLE_SDO, ROLE_ADMIN],
    // School Head requests TA
    'ta_request'                => [ROLE_SCHOOL_HEAD],

    // ── Announcements ───────────────────────────────────────
    // Post: Admin, SDO, RO
    'announcement_post'         => [ROLE_ADMIN, ROLE_SDO, ROLE_RO],
    // View: All roles
    'announcement_view'         => [ROLE_ADMIN, ROLE_SCHOOL_HEAD, ROLE_TEACHER,
                                     ROLE_STAKEHOLDER, ROLE_SDO, ROLE_RO],
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

    ROLE_SCHOOL_HEAD => [
        ['Overview', 'grid', [
            ['Dashboard',         'school_head/dashboard.php',             'grid'],
            ['SBM Dimensions',    'school_head/dimensions.php',            'layers'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',   'school_head/self_assessment.php',       'check-circle'],
            ['Teacher Status',    'school_head/teacher_status.php',        'users'],
            ['Evidence & MOV',    'school_head/evidence.php',              'paperclip'],
        ]],
        ['Planning', 'trending-up', [
            ['Improvement Plan',  'school_head/improvement.php',           'trending-up'],
            ['Reports',           'school_head/reports.php',               'file-text'],
        ]],
        // School Head can manage school profile (school-level config)
        ['School', 'home', [
            ['School Profile',    'school_head/school_profile.php',        'home'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'school_head/announcements.php',         'bell'],
        ]],
    ],

    ROLE_TEACHER => [
        ['Overview', 'grid', [
            ['Dashboard',         'teacher/dashboard.php',                 'grid'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',   'teacher/self_assessment.php',           'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'teacher/announcements.php',             'bell'],
        ]],
    ],

    ROLE_STAKEHOLDER => [
        ['Overview', 'grid', [
            ['Dashboard',         'stakeholder/dashboard.php',             'grid'],
        ]],
        ['Participation', 'users', [
            ['Self-Assessment',   'stakeholder/self_assessment.php',       'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'stakeholder/announcement.php',          'bell'],
        ]],
    ],

    ROLE_SDO => [
        ['Overview', 'grid', [
            ['Dashboard',         'sdo/dashboard.php',                     'grid'],
            // SDO now has read-only analytics access
            ['Analytics',         'sdo/analytics.php',                     'bar-chart-2'],
        ]],
        ['Monitoring', 'eye', [
            ['School Monitoring', 'sdo/monitoring.php',                    'home'],
            ['Assessments',       'sdo/assessments.php',                   'check-circle'],
            ['TA Requests',       'sdo/ta_requests.php',                   'briefcase'],
            ['Technical Assistance','sdo/technical_assistance.php',        'trending-up'],
        ]],
        ['Reports', 'file-text', [
            ['Division Reports',  'sdo/reports.php',                       'file-text'],
            ['Workflow',          'sdo/workflow.php',                      'trending-up'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'sdo/announcements.php',                 'bell'],
        ]],
    ],

    ROLE_RO => [
        ['Overview', 'grid', [
            ['Dashboard',         'ro/dashboard.php',                      'grid'],
            ['Analytics',         'ro/analytics.php',                      'bar-chart-2'],
        ]],
        ['Reports', 'file-text', [
            ['Division Reports',  'ro/reports.php',                        'file-text'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',     'ro/announcements.php',                  'bell'],
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
 * Terminates with 403 JSON or redirect if unauthorized.
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
