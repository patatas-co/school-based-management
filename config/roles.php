<?php
// ============================================================
// config/roles.php — Centralized Role Permissions
// DIHS SBM Online Monitoring System
// ============================================================

define('ROLE_SCHOOL_HEAD', 'school_head');
define('ROLE_SYSTEM_ADMIN', 'system_admin');
define('ROLE_COORDINATOR', 'sbm_coordinator');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STAKEHOLDER', 'external_stakeholder');

define('SBM_MODULE_ACCESS', [

    // ── System Administration (School Head only) ────────────
    'system_admin_dashboard' => [ROLE_SYSTEM_ADMIN],
    'user_management' => [ROLE_SYSTEM_ADMIN],
    'system_settings' => [ROLE_SYSTEM_ADMIN],
    'school_years' => [ROLE_SYSTEM_ADMIN],
    'school_profile' => [ROLE_SYSTEM_ADMIN, ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Dashboards ──────────────────────────────────────────

    // ── Reports ─────────────────────────────────────────────
    // ── Reports ─────────────────────────────────────────────
    'reports_school' => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Monitoring ──────────────────────────────────────────
    'monitor_teachers' => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Workflow / Timeline ──────────────────────────────────
    'workflow_configure' => [ROLE_SCHOOL_HEAD],
    'workflow_view' => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],

    // ── Announcements ───────────────────────────────────────
    'announcement_post' => [ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
    'announcement_view' => [
        ROLE_SCHOOL_HEAD,
        ROLE_COORDINATOR,
        ROLE_TEACHER,
        ROLE_STAKEHOLDER
    ],
    'start_assessment' => [ROLE_SCHOOL_HEAD, ROLE_SYSTEM_ADMIN],
    'assign_indicators' => [ROLE_SYSTEM_ADMIN, ROLE_SCHOOL_HEAD, ROLE_COORDINATOR],
]);

define('SBM_NAV', [

    ROLE_SYSTEM_ADMIN => [
        [
            'Overview',
            'grid',
            [
                ['Dashboard', 'system_admin/dashboard.php', 'grid']

            ]
        ],
        [
            'Management',
            'users',
            [
                ['User Accounts', 'system_admin/users.php', 'users'],
                ['School Profile', 'school_head/school_profile.php', 'home'],
                ['School Years', 'school_head/settings.php', 'calendar'],
            ]
        ],
    ],

    ROLE_SCHOOL_HEAD => [
        [
            'Overview',
            'grid',
            [
                ['Dashboard', 'school_head/dashboard.php', 'grid'],
                ['Reports', 'school_head/reports.php', 'file-text'],
            ]
        ],
        [
            'Management',
            'users',
            [
                ['Evidence & MOV', 'school_head/evidence.php', 'paperclip'],
                ['School Profile', 'school_head/school_profile.php', 'home'],
            ]
        ],
        [
            'Evaluation',
            'check-circle',
            [
                ['Self-Assessment', 'school_head/self_assessment.php', 'check-circle'],
                ['SBM Assessments', 'school_head/assessment.php', 'clipboard'],
            ]
        ],
        [
            'Workflow',
            'trending-up',
            [
                ['Workflow Overview', 'school_head/workflow.php', 'trending-up'],
            ]
        ],
        [
            'Communication',
            'bell',
            [
                ['Announcements', 'school_head/announcements.php', 'bell'],
            ]
        ],
    ],

    ROLE_COORDINATOR => [
        [
            'Overview',
            'grid',
            [
                ['Dashboard', 'coordinator/dashboard.php', 'grid'],
                ['SBM Dimensions', 'coordinator/dimensions.php', 'layers'],
                ['Reports', 'coordinator/reports.php', 'file-text'],
            ]
        ],

        [
            'Management',
            'users',
            [
                ['Assign Indicators', 'coordinator/assign_indicators.php', 'check-square'],
                ['Teacher Status', 'coordinator/teacher_status.php', 'users']
            ]
        ],
        [
            'Evaluation',
            'check-circle',
            [
                ['Self-Assessment', 'coordinator/self_assessment.php', 'check-circle'],
            ]
        ],
        [
            'School',
            'home',
            [
                ['School Profile', 'coordinator/school_profile.php', 'home'],
            ]
        ],
        [
            'Communication',
            'bell',
            [
                ['Announcements', 'coordinator/announcements.php', 'bell'],
            ]
        ],
    ],

    ROLE_TEACHER => [
        [
            'Overview',
            'grid',
            [
                ['Dashboard', 'teacher/dashboard.php', 'grid'],
            ]
        ],
        [
            'Evaluation',
            'check-circle',
            [
                ['Self-Assessment', 'teacher/self_assessment.php', 'check-circle'],
            ]
        ],
        [
            'Communication',
            'bell',
            [
                ['Announcements', 'teacher/announcements.php', 'bell'],
            ]
        ],
    ],

    ROLE_STAKEHOLDER => [
        [
            'Participation',
            'users',
            [
                ['Self-Assessment', 'stakeholder/self_assessment.php', 'check-circle'],
            ]
        ],
    ],

]);

function hasAccess(string $module, ?string $role = null): bool
{
    if ($role === null) {
        $role = $_SESSION['role'] ?? '';
    }
    $allowed = SBM_MODULE_ACCESS[$module] ?? [];
    return in_array($role, $allowed, true);
}

function requireAccess(string $module): void
{
    if (!hasAccess($module)) {
        $role = $_SESSION['role'] ?? 'guest';
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'msg' => 'Access denied. Your role (' . $role . ') cannot perform this action.',
            ]);
            exit;
        }
        http_response_code(403);
        echo '<div style="font-family:sans-serif;padding:40px;text-align:center;">'
            . '<h2>Access Denied</h2>'
            . '<p>Your role does not have permission to view this page.</p>'
            . '<a href="javascript:history.back()">Go Back</a></div>';
        exit;
    }
}
