<?php
// includes/auth.php
require_once __DIR__ . '/../config/db.php';
if (file_exists(__DIR__ . '/../config/roles.php')) {
    require_once __DIR__ . '/../config/roles.php';
}

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . baseUrl() . '/login.php'); exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . baseUrl() . '/login.php?err=access'); exit;
    }
}

function me(): array {
    return [
        'id'        => $_SESSION['user_id']  ?? null,
        'name'      => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'user'      => $_SESSION['username']  ?? '',
        'school_id' => $_SESSION['school_id'] ?? null,
    ];
}

function baseUrl(): string {
    if (!empty($_ENV['SBM_BASE_URL'])) {
        return rtrim($_ENV['SBM_BASE_URL'], '/');
    }
    $proto = ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' )
           ? 'https'
           : ( ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) === 'https' ? 'https' : 'http' );
    $host  = $_SERVER['HTTP_HOST'];
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $dir = preg_replace(
        '#/(school_head|coordinator|teacher|stakeholder|includes|config|ml|api|assets|vendor)(/.*)?$#i',
        '',
        dirname($script)
    );
    $dir = rtrim($dir, '/\\');
    return $proto . '://' . $host . (empty($dir) ? '' : $dir);
}

function roleHome(string $role): string {
    switch ($role) {
        case 'school_head':          return 'school_head/dashboard.php';
        case 'sbm_coordinator':      return 'coordinator/dashboard.php';
        case 'teacher':              return 'teacher/dashboard.php';
        case 'external_stakeholder': return 'stakeholder/dashboard.php';
        default:                     return 'login.php';
    }
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $dt): string {
    $tz   = new DateTimeZone('Asia/Manila');
    $now  = new DateTime('now', $tz);
    try {
        $past = new DateTime($dt, $tz);
    } catch (\Exception $e) {
        return '—';
    }
    $diff = $now->getTimestamp() - $past->getTimestamp();
    if ($diff < 0)      return 'just now';
    if ($diff < 10)     return 'just now';
    if ($diff < 60)     return $diff . 's ago';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return $past->format('M d, Y');
}

function sbmMaturityLevel(float $pct): array {
    if ($pct >= 76) return ['label' => 'Advanced',   'color' => '#16A34A', 'bg' => '#DCFCE7'];
    if ($pct >= 51) return ['label' => 'Maturing',   'color' => '#2563EB', 'bg' => '#DBEAFE'];
    if ($pct >= 26) return ['label' => 'Developing', 'color' => '#D97706', 'bg' => '#FEF3C7'];
    return                 ['label' => 'Beginning',  'color' => '#DC2626', 'bg' => '#FEE2E2'];
}

function sbmMaturityBadge(string $level): string {
    $map = [
        'Beginning'  => ['#FEE2E2', '#DC2626', '#FECACA'],
        'Developing' => ['#FEF3C7', '#D97706', '#FDE68A'],
        'Maturing'   => ['#DBEAFE', '#2563EB', '#BFDBFE'],
        'Advanced'   => ['#DCFCE7', '#16A34A', '#BBF7D0'],
    ];
    [$bg, $c, $br] = $map[$level] ?? ['#F3F4F6', '#6B7280', '#E5E7EB'];
    return "<span style=\"display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:$bg;color:$c;border:1px solid $br;\">$level</span>";
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrf(bool $lenient = false): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        ob_clean();
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Invalid CSRF token. Please refresh the page.']);
        exit;
    }
}

function sbmRatingBadge(int $r): string {
    $map = [
        1 => ['Not Yet Manifested', '#FEE2E2', '#DC2626', '#FECACA'],
        2 => ['Emerging',           '#FEF3C7', '#D97706', '#FDE68A'],
        3 => ['Developing',         '#DBEAFE', '#2563EB', '#BFDBFE'],
        4 => ['Always Manifested',  '#DCFCE7', '#16A34A', '#BBF7D0'],
    ];
    [$l, $bg, $c, $br] = $map[$r] ?? ['—', '#F3F4F6', '#6B7280', '#E5E7EB'];
    return "<span style=\"display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:$bg;color:$c;border:1px solid $br;\">$l</span>";
}

function computeMaturity(float $pct): string {
    if ($pct >= 76) return 'Advanced';
    if ($pct >= 51) return 'Maturing';
    if ($pct >= 26) return 'Developing';
    return 'Beginning';
}

function logActivity(string $action, string $module = '', string $details = ''): void {
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
    } catch (Exception $e) {}
}

/**
 * Renders inline CSS and JS for the password visibility toggle.
 * Automatically finds all password inputs and adds an eye icon.
 */
function renderPasswordToggle(): void {
    ?>
    <style>
    .pw-toggle-btn {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; padding: 4px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: #9CA3AF; transition: color 0.15s; z-index: 10;
        line-height: 0; outline: none;
    }
    .pw-toggle-btn:hover { color: #16A34A; }
    .pw-toggle-btn svg { width: 18px; height: 18px; pointer-events: none; stroke: currentColor; fill: none; }
    </style>
    <script>
    (function() {
        function initPasswordToggle() {
            const passwordFields = document.querySelectorAll('input[type="password"]');
            passwordFields.forEach(field => {
                if (field.dataset.pwToggleInit) return;
                field.dataset.pwToggleInit = 'true';

                const parent = field.parentElement;
                if (!parent) return;

                // Wrap the input in a relative div so the toggle centers against the input only,
                // not the whole form-group (which includes the label).
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'position:relative;display:block;';
                parent.insertBefore(wrapper, field);
                wrapper.appendChild(field);

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

                btn.addEventListener('click', function(e) {
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