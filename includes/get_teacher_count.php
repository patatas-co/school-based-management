<?php
// includes/get_teacher_count.php
// Returns live teacher count for DIHS. Called via AJAX.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-store');

$db       = getDB();
$schoolId = SCHOOL_ID;

// Dynamic COUNT — never stale, always accurate
$stmt = $db->prepare("
    SELECT
        COUNT(*)                                                    AS total_teachers,
        SUM(CASE WHEN status = 'active'   THEN 1 ELSE 0 END)      AS active_teachers,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END)      AS inactive_teachers,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END)     AS suspended_teachers
    FROM users
    WHERE school_id = ?
      AND role = 'teacher'
");
$stmt->execute([$schoolId]);
$row = $stmt->fetch();

echo json_encode([
    'ok'                 => true,
    'total_teachers'     => (int) $row['total_teachers'],
    'active_teachers'    => (int) $row['active_teachers'],
    'inactive_teachers'  => (int) $row['inactive_teachers'],
    'suspended_teachers' => (int) $row['suspended_teachers'],
]);