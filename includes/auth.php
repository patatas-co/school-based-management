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
        'profile_picture' => $_SESSION['profile_picture'] ?? null,
    ];
}

function requireSystemAdmin(): void
{
    requireLogin();
    $role = $_SESSION['role'] ?? '';
    if ($role === 'system_admin') {
        return;
    }

    $systemAdminCount = null;
    try {
        $db = getDB();
        $systemAdminCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='system_admin'")->fetchColumn();
    } catch (Exception $e) {
        $systemAdminCount = null;
    }

    if ($role === 'school_head' && $systemAdminCount === 0) {
        return;
    }

    header('Location: ' . baseUrl() . '/login.php?err=access');
    exit;
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
        '#/(system_admin|school_head|coordinator|teacher|stakeholder|includes|config|ml|api|assets|vendor)(/.*)?$#i',
        '',
        dirname($script)
    );
    $dir = rtrim($dir, '/\\');
    return $proto . '://' . $host . (empty($dir) ? '' : $dir);
}

function roleHome(string $role): string
{
    switch ($role) {
        case 'system_admin':
            return 'system_admin/dashboard.php';
        case 'school_head':
            return 'school_head/dashboard.php';
        case 'sbm_coordinator':
            return 'coordinator/dashboard.php';
        case 'teacher':
            return 'teacher/dashboard.php';
        case 'external_stakeholder':
            return 'stakeholder/self_assessment.php';
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

/**
 * Simple session-based rate limiter.
 * Returns ['allowed' => bool, 'retry_after' => int (seconds remaining)]
 *
 * Usage:  $rl = checkRateLimit('ml_recommendation', 5, 60);
 *         if (!$rl['allowed']) { ... blocked ... }
 */
function checkRateLimit(string $key, int $maxAttempts, int $windowSeconds): array
{
    $now = time();
    $sessionKey = "rl_{$key}_attempts";
    $windowKey = "rl_{$key}_window_start";

    $windowStart = (int) ($_SESSION[$windowKey] ?? 0);
    $attempts = (int) ($_SESSION[$sessionKey] ?? 0);

    // Reset if the window has expired
    if ($now - $windowStart >= $windowSeconds) {
        $_SESSION[$windowKey] = $now;
        $_SESSION[$sessionKey] = 0;
        $attempts = 0;
        $windowStart = $now;
    }

    if ($attempts >= $maxAttempts) {
        $retryAfter = $windowSeconds - ($now - $windowStart);
        return ['allowed' => false, 'retry_after' => max(1, $retryAfter)];
    }

    $_SESSION[$sessionKey] = $attempts + 1;
    return ['allowed' => true, 'retry_after' => 0];
}


function getDeadlineInfo(PDO $db, int $syId): ?array
{
    if (!$syId)
        return null;

    // 1. Try to get the specific Self-Assessment (Phase 1) deadline from workflow configuration
    $st = $db->prepare("SELECT date_end FROM sbm_workflow_phases WHERE sy_id=? AND phase_no=1 LIMIT 1");
    $st->execute([$syId]);
    $dateEnd = $st->fetchColumn();

    // 2. Fallback to School Year boundary if workflow is not yet configured
    if (!$dateEnd) {
        $st = $db->prepare("SELECT date_end FROM school_years WHERE sy_id=? LIMIT 1");
        $st->execute([$syId]);
        $dateEnd = $st->fetchColumn();
    }

    if (!$dateEnd)
        return null;

    $tz = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $tz);
    $end = new DateTime($dateEnd . ' 23:59:59', $tz);
    $diff = $now->diff($end);
    $totalSec = $end->getTimestamp() - $now->getTimestamp();

    return [
        'date' => $dateEnd,
        'seconds' => $totalSec,
        'days' => (int) $diff->days,
        'hours' => (int) $diff->h,
        'minutes' => (int) $diff->i,
        'overdue' => $totalSec < 0,
        'today' => ($diff->days === 0 && $totalSec >= 0),
    ];
}

function renderDeadlineChip(?array $dl, string $context = 'dark'): string
{
    if (!$dl)
        return '';

    // Determine visual state
    if ($dl['overdue']) {
        $dot = '#EF4444';
        $color = $context === 'dark' ? '#FCA5A5' : '#DC2626';
        $bg = $context === 'dark' ? 'rgba(220,38,38,0.18)' : '#FEE2E2';
        $border = $context === 'dark' ? 'rgba(220,38,38,0.45)' : '#FECACA';
        $anim = 'deadlinePulseRed 2s ease-in-out infinite';
        $label = 'Overdue by ' . $dl['days'] . ' day' . ($dl['days'] !== 1 ? 's' : '');
        $sub = 'Assessment period has ended · ' . date('M d, Y', strtotime($dl['date']));
        $icon = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
    } elseif ($dl['today']) {
        $dot = '#EF4444';
        $color = $context === 'dark' ? '#FCA5A5' : '#DC2626';
        $bg = $context === 'dark' ? 'rgba(220,38,38,0.14)' : '#FEE2E2';
        $border = $context === 'dark' ? 'rgba(220,38,38,0.40)' : '#FECACA';
        $anim = 'deadlinePulseRed 1.4s ease-in-out infinite';
        $label = 'Due Today';
        $sub = 'Assessment ends tonight at 11:59 PM';
        $icon = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><polyline points="12 12 16 14"/>';
    } elseif ($dl['days'] < 3) {
        $dot = '#F59E0B';
        $color = $context === 'dark' ? '#FCD34D' : '#D97706';
        $bg = $context === 'dark' ? 'rgba(217,119,6,0.16)' : '#FEF3C7';
        $border = $context === 'dark' ? 'rgba(217,119,6,0.40)' : '#FDE68A';
        $anim = 'deadlinePulseAmber 2.5s ease-in-out infinite';
        $label = $dl['days'] . 'd ' . $dl['hours'] . 'h left';
        $sub = 'Deadline: ' . date('M d, Y', strtotime($dl['date']));
        $icon = '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>';
    } else {
        $dot = '#4ADE80';
        $color = $context === 'dark' ? '#86EFAC' : '#16A34A';
        $bg = $context === 'dark' ? 'rgba(74,222,128,0.10)' : '#DCFCE7';
        $border = $context === 'dark' ? 'rgba(74,222,128,0.28)' : '#86EFAC';
        $anim = 'none';
        $label = $dl['days'] . ' days left';
        $sub = 'Deadline: ' . date('M d, Y', strtotime($dl['date']));
        $icon = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
    }

    $subColor = $context === 'dark' ? 'rgba(255,255,255,.38)' : '#6B7280';

    return '
    <div class="deadline-chip" data-deadline="' . htmlspecialchars($dl['date'], ENT_QUOTES) . '" style="
        display:inline-flex;align-items:center;gap:9px;
        background:' . $bg . ';border:1px solid ' . $border . ';
        border-radius:10px;padding:8px 14px 8px 10px;
        animation:' . $anim . ';max-width:fit-content;
        backdrop-filter:blur(8px);">
      <span style="width:8px;height:8px;border-radius:50%;
          background:' . $dot . ';flex-shrink:0;
          box-shadow:0 0 6px ' . $dot . ';"></span>
      <div>
        <div style="display:flex;align-items:center;gap:7px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="' . $color . '"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
               style="width:13px;height:13px;flex-shrink:0;">
            ' . $icon . '
          </svg>
          <span class="dl-label" style="font-size:13px;font-weight:700;
              color:' . $color . ';letter-spacing:.01em;">' . htmlspecialchars($label, ENT_QUOTES) . '</span>
        </div>
        <div class="dl-sub" style="font-size:10.5px;color:' . $subColor . ';
            margin-top:1px;padding-left:20px;">' . htmlspecialchars($sub, ENT_QUOTES) . '</div>
      </div>
    </div>';
}

function deadlineChipCss(): string
{
    return '
    <style>
    @keyframes deadlinePulseRed {
        0%,100% { box-shadow:0 0 0 0 rgba(220,38,38,0); }
        50%      { box-shadow:0 0 0 5px rgba(220,38,38,0.20); }
    }
    @keyframes deadlinePulseAmber {
        0%,100% { box-shadow:0 0 0 0 rgba(217,119,6,0); }
        50%      { box-shadow:0 0 0 5px rgba(217,119,6,0.18); }
    }
    </style>';
}

function deadlineChipJs(): string
{
    return "
    <script>
    (function(){
        document.querySelectorAll('.deadline-chip').forEach(function(chip){
            var raw = chip.dataset.deadline;
            if(!raw) return;
            var end = new Date(raw + 'T23:59:59+08:00').getTime();
            var labelEl = chip.querySelector('.dl-label');
            var subEl   = chip.querySelector('.dl-sub');
            function fmt(sec){
                var abs  = Math.abs(sec);
                var d    = Math.floor(abs/86400);
                var h    = Math.floor((abs%86400)/3600);
                var m    = Math.floor((abs%3600)/60);
                var s    = abs%60;
                if(sec < 0){
                    if(d>0)  return 'Overdue by '+d+'d '+h+'h';
                    if(h>0)  return 'Overdue by '+h+'h '+m+'m';
                    return 'Overdue by '+m+'m '+s+'s';
                }
                if(d===0&&h===0) return m+'m '+s+'s left';
                if(d===0)        return h+'h '+m+'m left';
                if(d<3)          return d+'d '+h+'h '+m+'m left';
                return d+' days left';
            }
            setInterval(function(){
                var diff = Math.floor((end - Date.now())/1000);
                if(labelEl) labelEl.textContent = fmt(diff);
            }, 1000);
        });
    })();
    <\/script>";
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
