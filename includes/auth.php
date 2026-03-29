<?php
// includes/auth.php — Updated with roles.php integration
require_once __DIR__ . '/../config/db.php';
// Load centralized role permissions
if (file_exists(__DIR__ . '/../config/roles.php')) {
    require_once __DIR__ . '/../config/roles.php';
}

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . baseUrl() . '/login.php'); exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        if (!empty($_SESSION['user_id'])) {
            echo '<div class="alert alert-danger">Access denied. You do not have permission to view this page.</div>';
            include __DIR__ . '/footer.php';
            exit;
        }
        header('Location: ' . baseUrl() . '/login.php?err=access'); exit;
    }
}

function me(): array {
    return [
        'id'          => $_SESSION['user_id']    ?? null,
        'name'        => $_SESSION['full_name']   ?? '',
        'role'        => $_SESSION['role']        ?? '',
        'user'        => $_SESSION['username']    ?? '',
        'school_id'   => $_SESSION['school_id']   ?? null,
        'division_id' => $_SESSION['division_id'] ?? null,
        'region_id'   => $_SESSION['region_id']   ?? null,
    ];
}

function baseUrl(): string {
    $proto = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $proto = 'https';
    }
    $host    = $_SERVER['HTTP_HOST'];
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $base    = str_ireplace($docRoot, '', $appRoot);
    return $proto . '://' . $host . $base;
}

function roleHome(string $role): string {
    $base = baseUrl();
    return match($role) {
        'admin'                => $base . '/admin/dashboard.php',
        'school_head'          => $base . '/school_head/dashboard.php',
        'teacher'              => $base . '/teacher/dashboard.php',
        'external_stakeholder' => $base . '/stakeholder/dashboard.php',
        'sdo'                  => $base . '/sdo/dashboard.php',
        'ro'                   => $base . '/ro/dashboard.php',
        default                => $base . '/login.php',
    };
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
    if ($diff < 0)     return 'just now';
    if ($diff < 10)    return 'just now';
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
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

function verifyCsrf(bool $ajaxOnly = false): void {
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
    } catch (Exception $e) {
        error_log('logActivity failed: ' . $e->getMessage());
    }
}

// ── hasAccess / requireAccess ──────────────────────────────────
// These are defined in config/roles.php if that file is loaded.
// Fallback stubs so pages don't break if roles.php isn't present.
if (!function_exists('hasAccess')) {
    function hasAccess(string $module, ?string $role = null): bool {
        // Fallback: allow if role is admin
        return ($_SESSION['role'] ?? '') === 'admin';
    }
}
if (!function_exists('requireAccess')) {
    function requireAccess(string $module): void {
        if (!hasAccess($module)) {
            http_response_code(403);
            echo '<p>Access denied.</p>'; exit;
        }
    }
}
