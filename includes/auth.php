<?php
// includes/auth.php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . baseUrl() . '/index.php'); exit;
    }
}
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . baseUrl() . '/index.php?err=access'); exit;
    }
}
function me(): array {
    return [
        'id'         => $_SESSION['user_id']    ?? null,
        'name'       => $_SESSION['full_name']   ?? '',
        'role'       => $_SESSION['role']        ?? '',
        'user'       => $_SESSION['username']    ?? '',
        'school_id'  => $_SESSION['school_id']   ?? null,
        'division_id'=> $_SESSION['division_id'] ?? null,
        'region_id'  => $_SESSION['region_id']   ?? null,
    ];
}
function baseUrl(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'];
    $dir   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    return $proto . '://' . $host . $dir;
}
function roleHome(string $role): string {
    return [
        'admin'                =>'admin/dashboard.php',
        'school_head'          =>'school_head/dashboard.php',
        'teacher'              =>'teacher/dashboard.php',
        'sdo'                  =>'sdo/dashboard.php',
        'ro'                   =>'ro/dashboard.php',
        'external_stakeholder' =>'stakeholder/dashboard.php'
    ][$role] ?? 'index.php';
}
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function timeAgo(string $dt): string {
    $tz   = new DateTimeZone('Asia/Manila');
    $now  = new DateTime('now', $tz);
    $past = new DateTime($dt, $tz);
    $diff = $now->getTimestamp() - $past->getTimestamp();
    if ($diff <  10)    return 'just now';
    if ($diff <  60)    return $diff . 's ago';
    if ($diff <  3600)  return floor($diff / 60) . 'm ago';
    if ($diff <  86400) return floor($diff / 3600) . 'h ago';
    if ($diff <  604800) return floor($diff / 86400) . 'd ago';
    return $past->format('M d, Y');
}
function sbmMaturityLevel(float $pct): array {
    if ($pct >= 76) return ['label'=>'Advanced',   'color'=>'#16A34A','bg'=>'#DCFCE7'];
    if ($pct >= 51) return ['label'=>'Maturing',   'color'=>'#2563EB','bg'=>'#DBEAFE'];
    if ($pct >= 26) return ['label'=>'Developing', 'color'=>'#D97706','bg'=>'#FEF3C7'];
    return                 ['label'=>'Beginning',  'color'=>'#DC2626','bg'=>'#FEE2E2'];
}
function sbmMaturityBadge(string $level): string {
    $map = [
        'Beginning'  => ['#FEE2E2','#DC2626','#FECACA'],
        'Developing' => ['#FEF3C7','#D97706','#FDE68A'],
        'Maturing'   => ['#DBEAFE','#2563EB','#BFDBFE'],
        'Advanced'   => ['#DCFCE7','#16A34A','#BBF7D0'],
    ];
    [$bg,$c,$br] = $map[$level] ?? ['#F3F4F6','#6B7280','#E5E7EB'];
    return "<span style=\"display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:$bg;color:$c;border:1px solid $br;\">$level</span>";
}
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Invalid CSRF token.']); exit;
    }
}
function sbmRatingBadge(int $r): string {
    $map = [1=>['Not Yet Manifested','#FEE2E2','#DC2626','#FECACA'],2=>['Emerging','#FEF3C7','#D97706','#FDE68A'],
            3=>['Developing','#DBEAFE','#2563EB','#BFDBFE'],4=>['Always Manifested','#DCFCE7','#16A34A','#BBF7D0']];
    [$l,$bg,$c,$br] = $map[$r] ?? ['—','#F3F4F6','#6B7280','#E5E7EB'];
    return "<span style=\"display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:$bg;color:$c;border:1px solid $br;\">$l</span>";
}
function computeMaturity(float $pct): string {
    if ($pct >= 76) return 'Advanced'; if ($pct >= 51) return 'Maturing';
    if ($pct >= 26) return 'Developing'; return 'Beginning';
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
               $_SERVER['REMOTE_ADDR'] ?? ''
           ]);
    } catch (Exception $e) {}
}