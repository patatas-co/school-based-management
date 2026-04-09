<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Explicit auth check — poll.php must never return data to unauthenticated users
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Only poll once every 8s per session — if client sends If-Modified-Since, check it
$lastPoll = $_SESSION['last_poll'] ?? 0;
$now = time();
if ($now - $lastPoll < 5) {
    http_response_code(204); // No Content — client uses its cached data
    exit;
}
$_SESSION['last_poll'] = $now;

$db = getDB();
$role = $_SESSION['role'] ?? '';
$schoolId = SCHOOL_ID; // Single-school system
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$out = [];

function ago(string $dt): string
{
    $d = time() - strtotime($dt);
    if ($d < 60)
        return 'just now';
    if ($d < 3600)
        return floor($d / 60) . 'm ago';
    if ($d < 86400)
        return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}

if ($role === 'school_head') {
    $out['schools'] = (int) $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
    $usersStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE school_id=? AND status='active'");
    $usersStmt->execute([$schoolId]);
    $out['users'] = (int) $usersStmt->fetchColumn();
    $cycleBase = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE school_id=? AND sy_id=?");
    $cycleBase->execute([$schoolId, $syId]);
    $out['cycles'] = (int) $cycleBase->fetchColumn();

    $cycleSubmitted = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE school_id=? AND sy_id=? AND status IN('submitted','validated')");
    $cycleSubmitted->execute([$schoolId, $syId]);
    $out['submitted'] = (int) $cycleSubmitted->fetchColumn();

    $cycleValidated = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE school_id=? AND sy_id=? AND status='validated'");
    $cycleValidated->execute([$schoolId, $syId]);
    $out['validated'] = (int) $cycleValidated->fetchColumn();

    $cycleInProgress = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE school_id=? AND sy_id=? AND status='in_progress'");
    $cycleInProgress->execute([$schoolId, $syId]);
    $out['in_progress'] = (int) $cycleInProgress->fetchColumn();

    $logs = $db->query("SELECT l.action,l.created_at,u.full_name FROM activity_log l LEFT JOIN users u ON l.user_id=u.user_id ORDER BY l.created_at DESC LIMIT 6")->fetchAll();
    $out['activity'] = array_map(fn($r) => ['name' => $r['full_name'] ?? 'System', 'action' => formatActivityAction($r['action']), 'ago' => ago($r['created_at'])], $logs);
}

if ($role === 'school_head' && $schoolId && $syId) {
    $cyc = $db->prepare("SELECT c.*,(SELECT COUNT(*) FROM sbm_responses WHERE cycle_id=c.cycle_id) responded FROM sbm_cycles c WHERE c.school_id=? AND c.sy_id=?");
    $cyc->execute([$schoolId, $syId]);
    $cyc = $cyc->fetch();
    if ($cyc) {
        $out['responded'] = (int) $cyc['responded'];
        $out['progress'] = $cyc['responded'] > 0 ? round(($cyc['responded'] / 42) * 100) : 0;
        $out['overall'] = $cyc['overall_score'];
        $out['maturity'] = $cyc['maturity_level'];
        $out['status'] = $cyc['status'];
    }
}

echo json_encode($out);