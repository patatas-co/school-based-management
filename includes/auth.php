<?php
// includes/auth.php
require_once __DIR__ . '/../config/db.php';
if (file_exists(__DIR__ . '/../config/roles.php')) {
    require_once __DIR__ . '/../config/roles.php';
}
if (file_exists(__DIR__ . '/../config/sbm_indicators.php')) {
    require_once __DIR__ . '/../config/sbm_indicators.php';
}

if (session_status() === PHP_SESSION_NONE)
    session_start();

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . baseUrl() . '/login.php');
        exit;
    }

    static $validatedSession = false;
    if ($validatedSession) {
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT status, role FROM users WHERE user_id=? LIMIT 1");
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $user = false;
    }

    if (!$user || ($user['status'] ?? '') !== 'active') {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: ' . baseUrl() . '/login.php?err=deactivated');
        exit;
    }

    if (!empty($user['role']) && $user['role'] !== ($_SESSION['role'] ?? '')) {
        $_SESSION['role'] = $user['role'];
    }

    $validatedSession = true;
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . baseUrl() . '/login.php?err=access');
        exit;
    }
}

function me(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'user' => $_SESSION['username'] ?? '',
        'school_id' => $_SESSION['school_id'] ?? null,
    ];
}

function baseUrl(): string
{
    if (!empty($_ENV['SBM_BASE_URL'])) {
        return rtrim($_ENV['SBM_BASE_URL'], '/');
    }
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'];
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $dir = preg_replace(
        '#/(school_head|coordinator|teacher|stakeholder|includes|config|ml|api|assets|vendor)(/.*)?$#i',
        '',
        dirname($script)
    );
    $dir = rtrim($dir, '/\\');
    return $proto . '://' . $host . (empty($dir) ? '' : $dir);
}

function roleHome(string $role): string
{
    switch ($role) {
        case 'school_head':
            return 'school_head/dashboard.php';
        case 'sbm_coordinator':
            return 'coordinator/dashboard.php';
        case 'teacher':
            return 'teacher/dashboard.php';
        case 'external_stakeholder':
            return 'stakeholder/dashboard.php';
        default:
            return 'login.php';
    }
}

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $dt): string
{
    $tz = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $tz);
    try {
        $past = new DateTime($dt, $tz);
    } catch (\Exception $e) {
        return '—';
    }
    $diff = $now->getTimestamp() - $past->getTimestamp();
    if ($diff < 0)
        return 'just now';
    if ($diff < 10)
        return 'just now';
    if ($diff < 60)
        return $diff . 's ago';
    if ($diff < 3600)
        return floor($diff / 60) . 'm ago';
    if ($diff < 86400)
        return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)
        return floor($diff / 86400) . 'd ago';
    return $past->format('M d, Y');
}

/**
 * Formats a coded activity action (snake_case) into a human-readable label.
 */
function formatActivityAction(string $action): string
{
    $map = [
        'login' => 'Logged in',
        'logout' => 'Logged out',
        'password_set' => 'Set password',
        'password_change' => 'Changed password',
        'reset_request' => 'Requested password reset',
        'reset_success' => 'Reset password successfully',
        'create_user' => 'Created a new user',
        'update_user' => 'Updated user profile',
        'delete_user' => 'Deleted a user',
        'export_report' => 'Exported a report',
        'view_report' => 'Viewed a report',
        'start_assessment' => 'Started assessment cycle',
        'submit_assessment' => 'Submitted assessment',
        'validate_assessment' => 'Validated assessment',
        'return_assessment' => 'Returned assessment',
        'upload_evidence' => 'Uploaded evidence',
        'assign_indicators' => 'Assigned indicators',
        'save_milestone' => 'Saved milestone',
        'configure_cycle_schedule' => 'Configured schedule',
        'override_assignments' => 'Overrode assignments',
    ];
    if (isset($map[$action])) {
        return $map[$action];
    }
    return ucwords(str_replace(['_', '-'], ' ', $action));
}



function sbmMaturityBadge(string $level): string
{
    $map = [
        'Beginning' => ['#FEE2E2', '#DC2626', '#FECACA'],
        'Developing' => ['#FEF3C7', '#D97706', '#FDE68A'],
        'Maturing' => ['#DBEAFE', '#2563EB', '#BFDBFE'],
        'Advanced' => ['#DCFCE7', '#16A34A', '#BBF7D0'],
    ];
    [$bg, $c, $br] = $map[$level] ?? ['#F3F4F6', '#6B7280', '#E5E7EB'];
    return "<span style=\"display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:$bg;color:$c;border:1px solid $br;\">$level</span>";
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        ob_clean();
        http_response_code(403);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (($_SERVER['HTTP_ACCEPT'] ?? '') && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Invalid CSRF token. Please refresh the page.']);
        } else {
            echo '<p style="font-family:sans-serif;padding:40px;text-align:center;">Invalid CSRF token. <a href="javascript:history.back()">Go back</a> and try again.</p>';
        }
        exit;
    }
}

function sbmRatingBadge(int $r): string
{
    $map = [
        1 => ['Not yet Manifested', '#FEE2E2', '#DC2626', '#FECACA'],
        2 => ['Rarely Manifested', '#FEF3C7', '#D97706', '#FDE68A'],
        3 => ['Frequently Manifested', '#DBEAFE', '#2563EB', '#BFDBFE'],
        4 => ['Always manifested', '#DCFCE7', '#16A34A', '#BBF7D0'],
    ];
    [$l, $bg, $c, $br] = $map[$r] ?? ['—', '#F3F4F6', '#6B7280', '#E5E7EB'];
    return "<span style=\"display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:$bg;color:$c;border:1px solid $br;\">$l</span>";
}

function computeMaturity(float $pct): string
{
    if ($pct >= 76)
        return 'Advanced';
    if ($pct >= 51)
        return 'Maturing';
    if ($pct >= 26)
        return 'Developing';
    return 'Beginning';
}

function logActivity(string $action, string $module = '', string $details = ''): void
{
    try {
        $db = getDB();
        $db->prepare("INSERT INTO activity_log (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)")
            ->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                $module,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
    } catch (Exception $e) {
    }
}

/**
 * Renders inline CSS and JS for the password visibility toggle.
 * Automatically finds all password inputs and adds an eye icon.
 */
function renderPasswordToggle(): void
{
    ?>
        <style>
            .pw-toggle-btn {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                padding: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #9CA3AF;
                transition: color 0.15s;
                z-index: 10;
                line-height: 0;
                outline: none;
            }

            .pw-toggle-btn:hover {
                color: #16A34A;
            }

            .pw-toggle-btn svg {
                width: 18px;
                height: 18px;
                pointer-events: none;
                stroke: currentColor;
                fill: none;
            }
        </style>
        <script>
            (function () {
                function initPasswordToggle() {
                    const passwordFields = document.querySelectorAll('input[type="password"]');
                    passwordFields.forEach(field => {
                        if (field.dataset.pwToggleInit) return;
                        field.dataset.pwToggleInit = 'true';

                        const parent = field.parentElement;
                        if (!parent) return;

                        // Reuse an existing input wrapper when available so we do not disturb
                        // sibling icons or layout that already depends on the current DOM shape.
                        const wrapper = parent.classList.contains('field-wrap') || parent.classList.contains('input-wrap')
                            ? parent
                            : (() => {
                                const el = document.createElement('div');
                                el.style.cssText = 'position:relative;display:block;';
                                parent.insertBefore(el, field);
                                el.appendChild(field);
                                return el;
                            })();

                        if (window.getComputedStyle(wrapper).position === 'static') {
                            wrapper.style.position = 'relative';
                        }

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'pw-toggle-btn';
                        btn.setAttribute('aria-label', 'Toggle password visibility');
                        btn.innerHTML = `
                    <svg class="eye-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-off-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;

                        wrapper.appendChild(btn);

                        const style = window.getComputedStyle(field);
                        if (parseInt(style.paddingRight) < 42) {
                            field.style.paddingRight = '42px';
                        }

                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const isPassword = field.type === 'password';
                            field.type = isPassword ? 'text' : 'password';
                            btn.querySelector('.eye-icon').style.display = isPassword ? 'none' : 'block';
                            btn.querySelector('.eye-off-icon').style.display = isPassword ? 'block' : 'none';
                            field.focus();
                        });
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initPasswordToggle);
                } else {
                    initPasswordToggle();
                }

                const observer = new MutationObserver(initPasswordToggle);
                observer.observe(document.body, { childList: true, subtree: true });
            })();
        </script>
        <?php
}
