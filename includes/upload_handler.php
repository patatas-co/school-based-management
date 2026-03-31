<?php
// includes/upload_handler.php
// Handles evidence file uploads for self-assessment responses.
// Called via AJAX POST — returns JSON.

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';

// Auto-create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/evidence/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Invalid request.']); exit;
}

verifyCsrf();

$db       = getDB();
$uid      = $_SESSION['user_id'];
$role     = $_SESSION['role'];
$schoolId = SCHOOL_ID;

// Allowed roles
$allowedRoles = ['teacher','sbm_coordinator','school_head','external_stakeholder'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['ok'=>false,'msg'=>'Access denied.']); exit;
}

$action = $_POST['action'] ?? '';

// ── DELETE ───────────────────────────────────────────────────
if ($action === 'delete_attachment') {
    $attId = (int)($_POST['attachment_id'] ?? 0);
    $row = $db->prepare("SELECT * FROM response_attachments WHERE attachment_id=?");
    $row->execute([$attId]); $row = $row->fetch();
    if (!$row) { echo json_encode(['ok'=>false,'msg'=>'Not found.']); exit; }

    // Only uploader or coordinator/school_head can delete
    $canDelete = ($row['uploaded_by'] === $uid)
              || in_array($role, ['sbm_coordinator','school_head']);
    if (!$canDelete) { echo json_encode(['ok'=>false,'msg'=>'Permission denied.']); exit; }

    $path = __DIR__.'/../uploads/evidence/'.$row['stored_name'];
    if (file_exists($path)) @unlink($path);
    $db->prepare("DELETE FROM response_attachments WHERE attachment_id=?")->execute([$attId]);
    echo json_encode(['ok'=>true,'msg'=>'Attachment deleted.']); exit;
}

// ── UPLOAD ───────────────────────────────────────────────────
if ($action === 'upload_attachment') {
    $indicatorId = (int)($_POST['indicator_id'] ?? 0);
    $cycleId     = (int)($_POST['cycle_id']     ?? 0);

    if (!$indicatorId || !$cycleId) {
        echo json_encode(['ok'=>false,'msg'=>'Missing indicator or cycle.']); exit;
    }

    // Verify cycle belongs to this school
    $cyc = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE cycle_id=? AND school_id=?");
    $cyc->execute([$cycleId, $schoolId]); $cyc = $cyc->fetch();
    if (!$cyc) { echo json_encode(['ok'=>false,'msg'=>'Invalid cycle.']); exit; }
    if (in_array($cyc['status'], ['submitted','validated'])) {
        echo json_encode(['ok'=>false,'msg'=>'Assessment is locked. Cannot upload.']); exit;
    }

    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        $errMap = [1=>'File too large.',2=>'File too large.',3=>'Partial upload.',4=>'No file.',6=>'No temp dir.',7=>'Cannot write.',8=>'Extension blocked.'];
        $errCode = $_FILES['attachment']['error'] ?? 4;
        echo json_encode(['ok'=>false,'msg'=>$errMap[$errCode]??'Upload error.']); exit;
    }

    $file         = $_FILES['attachment'];
    $originalName = basename($file['name']);
    $size         = $file['size'];
    $tmpPath      = $file['tmp_name'];

    // 10 MB limit
    if ($size > 10 * 1024 * 1024) {
        echo json_encode(['ok'=>false,'msg'=>'File too large. Max 10 MB.']); exit;
    }

    // Allowed types
    $allowedMimes = [
        'image/jpeg','image/png','image/gif','image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
    ];

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);
    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode(['ok'=>false,'msg'=>'File type not allowed: '.$mimeType]); exit;
    }

    // Max 5 attachments per indicator per user
    $countStmt = $db->prepare("SELECT COUNT(*) FROM response_attachments WHERE cycle_id=? AND indicator_id=? AND uploaded_by=?");
    $countStmt->execute([$cycleId,$indicatorId,$uid]);
    if ((int)$countStmt->fetchColumn() >= 5) {
        echo json_encode(['ok'=>false,'msg'=>'Max 5 attachments per indicator reached.']); exit;
    }

    $uploadDir  = __DIR__.'/../uploads/evidence/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','txt'];
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['ok'=>false,'msg'=>'File extension not allowed.']); exit;
    }
    $storedName = bin2hex(random_bytes(16)).'.'.$ext;
    $destPath   = $uploadDir.$storedName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        echo json_encode(['ok'=>false,'msg'=>'Failed to save file.']); exit;
    }

    $db->prepare("
        INSERT INTO response_attachments
            (cycle_id, indicator_id, school_id, uploaded_by, uploader_role,
             original_name, stored_name, file_size, mime_type)
        VALUES (?,?,?,?,?,?,?,?,?)
    ")->execute([$cycleId,$indicatorId,$schoolId,$uid,$role,$originalName,$storedName,$size,$mimeType]);

    $attId = $db->lastInsertId();
    logActivity('upload_evidence','attachment',"Uploaded evidence for indicator $indicatorId cycle $cycleId");

    echo json_encode([
        'ok'             => true,
        'msg'            => 'File uploaded.',
        'attachment_id'  => $attId,
        'original_name'  => $originalName,
        'file_size'      => $size,
        'mime_type'      => $mimeType,
    ]); exit;
}

// ── GET ATTACHMENTS ──────────────────────────────────────────
if ($action === 'get_attachments') {
    $cycleId      = (int)($_POST['cycle_id'] ?? 0);
    $uploaderOnly = ($_POST['uploader_only'] ?? '0') === '1';

    if (!$cycleId) { echo json_encode(['ok'=>false,'msg'=>'Missing cycle.']); exit; }

    // Verify cycle belongs to this school
    $cyc = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE cycle_id=? AND school_id=?");
    $cyc->execute([$cycleId, $schoolId]);
    if (!$cyc->fetchColumn()) { echo json_encode(['ok'=>false,'msg'=>'Invalid cycle.']); exit; }

    $params = [$cycleId];
    $extra  = '';
    if ($uploaderOnly) { $extra = ' AND ra.uploaded_by=?'; $params[] = $uid; }

    $stmt = $db->prepare("
        SELECT ra.attachment_id, ra.indicator_id, ra.uploaded_by,
               ra.uploader_role, ra.original_name, ra.file_size,
               ra.mime_type, ra.uploaded_at,
               u.full_name uploader_name
        FROM response_attachments ra
        JOIN users u ON ra.uploaded_by = u.user_id
        WHERE ra.cycle_id = ? $extra
        ORDER BY ra.indicator_id ASC, ra.uploaded_at ASC
    ");
    $stmt->execute($params);
    echo json_encode(['ok'=>true,'attachments'=>$stmt->fetchAll()]); exit;
}

echo json_encode(['ok'=>false,'msg'=>'Unknown action.']); exit;