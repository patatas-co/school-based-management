<?php
// includes/serve_attachment.php
// Streams an attachment file to the browser securely.
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireLogin();

$attId = (int)($_GET['id'] ?? 0);
if (!$attId) { http_response_code(400); exit('Invalid.'); }

$db  = getDB();
$uid = $_SESSION['user_id'];

$row = $db->prepare("
    SELECT ra.*, c.school_id cycle_school
    FROM response_attachments ra
    JOIN sbm_cycles c ON ra.cycle_id = c.cycle_id
    WHERE ra.attachment_id = ?
");
$row->execute([$attId]); $row = $row->fetch();

if (!$row) { http_response_code(404); exit('Not found.'); }

// Access: uploader, or same school's coordinator/school_head
$role = $_SESSION['role'];
$mySchool = SCHOOL_ID;
$canView = ($row['uploaded_by'] === $uid)
        || (in_array($role, ['sbm_coordinator','school_head']) && $mySchool == $row['cycle_school']);
if (!$canView) { http_response_code(403); exit('Forbidden.'); }

$path = __DIR__.'/../uploads/evidence/'.$row['stored_name'];
if (!file_exists($path)) { http_response_code(404); exit('File missing.'); }

$inline = in_array($row['mime_type'], ['image/jpeg','image/png','image/gif','image/webp','application/pdf']);
header('Content-Type: '.$row['mime_type']);
header('Content-Length: '.filesize($path));
header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.addslashes($row['original_name']).'"');
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;