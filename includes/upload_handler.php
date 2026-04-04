<?php
// includes/upload_handler.php
// Handles evidence file uploads for self-assessment responses.
// Called via AJAX POST — returns JSON.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Auto-create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/evidence/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Evidence audit logger ────────────────────────────────────
function logEvidenceAudit(PDO $db, int $attachmentId, int $cycleId, int $indicatorId, int $schoolId, ?int $actorId, string $actorRole, string $action, ?string $details = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $db->prepare("
        INSERT INTO evidence_audit_log
            (attachment_id, cycle_id, indicator_id, school_id, actor_id, actor_role, action, details, ip_address)
        VALUES (?,?,?,?,?,?,?,?,?)
    ")->execute([$attachmentId, $cycleId, $indicatorId, $schoolId, $actorId, $actorRole, $action, $details, $ip]);
}

header('Content-Type: application/json');



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request.']);
    exit;
}

verifyCsrf();

$db = getDB();
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$schoolId = SCHOOL_ID;

// Allowed roles
$allowedRoles = ['teacher', 'sbm_coordinator', 'school_head', 'external_stakeholder'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['ok' => false, 'msg' => 'Access denied.']);
    exit;
}

$action = $_POST['action'] ?? '';

// ── DELETE ───────────────────────────────────────────────────
if ($action === 'delete_attachment') {
    $attId = (int) ($_POST['attachment_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    $row = $db->prepare("SELECT * FROM response_attachments WHERE attachment_id=?");
    $row->execute([$attId]);
    $row = $row->fetch();
    if (!$row) {
        echo json_encode(['ok' => false, 'msg' => 'Not found.']);
        exit;
    }
    if (!is_null($row['deleted_at'])) {
        echo json_encode(['ok' => false, 'msg' => 'Already deleted.']);
        exit;
    }

    $canDelete = ((int) $row['uploaded_by'] === (int) $uid)
        || in_array($role, ['sbm_coordinator', 'school_head']);
    if (!$canDelete) {
        echo json_encode(['ok' => false, 'msg' => 'Permission denied.']);
        exit;
    }

    // Soft-delete instead of hard-delete — preserves audit trail
    $db->prepare("
        UPDATE response_attachments
        SET deleted_at=NOW(), deleted_by=?, replace_reason=?, is_current_version=0
        WHERE attachment_id=?
    ")->execute([$uid, $reason ?: null, $attId]);

    logEvidenceAudit(
        $db,
        $attId,
        $row['cycle_id'],
        $row['indicator_id'],
        $row['school_id'],
        $uid,
        $role,
        'delete',
        $reason ?: 'No reason given'
    );

    echo json_encode(['ok' => true, 'msg' => 'Attachment removed.']);
    exit;
}

// ── UPLOAD ───────────────────────────────────────────────────
if ($action === 'upload_attachment') {
    $indicatorId = (int) ($_POST['indicator_id'] ?? 0);
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);

    if (!$indicatorId || !$cycleId) {
        echo json_encode(['ok' => false, 'msg' => 'Missing indicator or cycle.']);
        exit;
    }

    // Verify cycle belongs to this school
    $cyc = $db->prepare("SELECT cycle_id, status FROM sbm_cycles WHERE cycle_id=? AND school_id=?");
    $cyc->execute([$cycleId, $schoolId]);
    $cyc = $cyc->fetch();
    if (!$cyc) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid cycle.']);
        exit;
    }
    if (in_array($cyc['status'], ['submitted', 'validated', 'finalized'])) {
        echo json_encode(['ok' => false, 'msg' => 'Assessment is locked. Cannot upload.']);
        exit;
    }

    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        $errMap = [1 => 'File too large.', 2 => 'File too large.', 3 => 'Partial upload.', 4 => 'No file.', 6 => 'No temp dir.', 7 => 'Cannot write.', 8 => 'Extension blocked.'];
        $errCode = $_FILES['attachment']['error'] ?? 4;
        echo json_encode(['ok' => false, 'msg' => $errMap[$errCode] ?? 'Upload error.']);
        exit;
    }

    $file = $_FILES['attachment'];
    $originalName = basename($file['name']);
    $size = $file['size'];
    $tmpPath = $file['tmp_name'];

    // 10 MB limit
    if ($size > 10 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'msg' => 'File too large. Max 10 MB.']);
        exit;
    }

    // Allowed types
    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);
    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode(['ok' => false, 'msg' => 'File type not allowed: ' . $mimeType]);
        exit;
    }

    // Max 5 attachments per indicator per user
    $countStmt = $db->prepare("SELECT COUNT(*) FROM response_attachments WHERE cycle_id=? AND indicator_id=? AND uploaded_by=?");
    $countStmt->execute([$cycleId, $indicatorId, $uid]);
    if ((int) $countStmt->fetchColumn() >= 5) {
        echo json_encode(['ok' => false, 'msg' => 'Max 5 attachments per indicator reached.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/evidence/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['ok' => false, 'msg' => 'File extension not allowed.']);
        exit;
    }
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $uploadDir . $storedName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        echo json_encode(['ok' => false, 'msg' => 'Failed to save file.']);
        exit;
    }

    $category = $_POST['category'] ?? 'other';
    $replaceAttId = (int) ($_POST['replace_attachment_id'] ?? 0);
    $replaceReason = trim($_POST['replace_reason'] ?? '');

    $allowedCats = ['photo', 'document', 'report', 'certificate', 'record', 'other'];
    if (!in_array($category, $allowedCats))
        $category = 'other';

    // If replacing an existing file, mark old one as superseded
    $parentId = null;
    $newVersion = 1;
    if ($replaceAttId > 0) {
        $old = $db->prepare("SELECT * FROM response_attachments WHERE attachment_id=? AND cycle_id=? AND indicator_id=?");
        $old->execute([$replaceAttId, $cycleId, $indicatorId]);
        $oldRow = $old->fetch();
        if ($oldRow && is_null($oldRow['deleted_at'])) {
            $db->prepare("
                UPDATE response_attachments
                SET is_current_version=0, deleted_at=NOW(), deleted_by=?, replace_reason=?
                WHERE attachment_id=?
            ")->execute([$uid, $replaceReason ?: 'Replaced by new upload', $replaceAttId]);
            $parentId = $replaceAttId;
            $newVersion = (int) $oldRow['version'] + 1;
        }
    }

    $db->prepare("
        INSERT INTO response_attachments
            (cycle_id, indicator_id, school_id, uploaded_by, uploader_role,
             original_name, stored_name, file_size, mime_type,
             category, version, parent_attachment_id, is_current_version)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)
    ")->execute([
                $cycleId,
                $indicatorId,
                $schoolId,
                $uid,
                $role,
                $originalName,
                $storedName,
                $size,
                $mimeType,
                $category,
                $newVersion,
                $parentId
            ]);

    $attId = $db->lastInsertId();

    logActivity('upload_evidence', 'attachment', "Uploaded evidence for indicator $indicatorId cycle $cycleId");
    logEvidenceAudit(
        $db,
        $attId,
        $cycleId,
        $indicatorId,
        $schoolId,
        $uid,
        $role,
        'upload',
        "v{$newVersion}, category: {$category}" . ($parentId ? ", replaced: {$parentId}" : '')
    );

    echo json_encode([
        'ok' => true,
        'msg' => 'File uploaded.',
        'attachment_id' => $attId,
        'original_name' => $originalName,
        'file_size' => $size,
        'mime_type' => $mimeType,
        'category' => $category,
        'version' => $newVersion,
    ]);
    exit;
}

// ── GET ATTACHMENTS ──────────────────────────────────────────
if ($action === 'get_attachments') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $uploaderOnly = ($_POST['uploader_only'] ?? '0') === '1';

    if (!$cycleId) {
        echo json_encode(['ok' => false, 'msg' => 'Missing cycle.']);
        exit;
    }

    // Verify cycle belongs to this school
    $cyc = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE cycle_id=? AND school_id=?");
    $cyc->execute([$cycleId, $schoolId]);
    if (!$cyc->fetchColumn()) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid cycle.']);
        exit;
    }

    $params = [$cycleId];
    $extra = '';
    if ($uploaderOnly) {
        $extra = ' AND ra.uploaded_by=?';
        $params[] = $uid;
    }

    $stmt = $db->prepare("
        SELECT ra.attachment_id, ra.indicator_id, ra.uploaded_by,
               ra.uploader_role, ra.original_name, ra.file_size,
               ra.mime_type, ra.uploaded_at,
               ra.category, ra.version, ra.is_current_version,
               ra.parent_attachment_id,
               u.full_name uploader_name
        FROM response_attachments ra
        JOIN users u ON ra.uploaded_by = u.user_id
        WHERE ra.cycle_id = ?
          AND ra.is_current_version = 1
          AND ra.deleted_at IS NULL
          $extra
        ORDER BY ra.indicator_id ASC, ra.uploaded_at ASC
    ");
    $stmt->execute($params);
    echo json_encode(['ok' => true, 'attachments' => $stmt->fetchAll()]);
    exit;
}

// ── GET VERSION HISTORY ──────────────────────────────────────
if ($action === 'get_attachment_history') {
    $attId = (int) ($_POST['attachment_id'] ?? 0);
    if (!$attId) {
        echo json_encode(['ok' => false, 'msg' => 'Missing attachment ID.']);
        exit;
    }

    // Walk the version chain: find root then fetch all versions
    $stmt = $db->prepare("
        SELECT ra.*, u.full_name uploader_name,
               d.full_name deleted_by_name
        FROM response_attachments ra
        JOIN users u ON ra.uploaded_by = u.user_id
        LEFT JOIN users d ON ra.deleted_by = d.user_id
        WHERE ra.cycle_id = (SELECT cycle_id FROM response_attachments WHERE attachment_id=?)
          AND ra.indicator_id = (SELECT indicator_id FROM response_attachments WHERE attachment_id=?)
          AND ra.school_id = ?
        ORDER BY ra.version ASC, ra.uploaded_at ASC
    ");
    $stmt->execute([$attId, $attId, $schoolId]);
    echo json_encode(['ok' => true, 'history' => $stmt->fetchAll()]);
    exit;
}

// ── CHECK EVIDENCE REQUIREMENTS ──────────────────────────────
if ($action === 'check_requirements') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    if (!$cycleId) {
        echo json_encode(['ok' => false, 'msg' => 'Missing cycle.']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT i.indicator_id, i.indicator_code,
               COALESCE(r.required_count, 0) AS required_count,
               COUNT(CASE WHEN ra.deleted_at IS NULL AND ra.is_current_version=1 THEN 1 END) AS uploaded_count
        FROM sbm_indicators i
        LEFT JOIN indicator_evidence_requirements r ON r.indicator_id = i.indicator_id
        LEFT JOIN response_attachments ra ON ra.indicator_id = i.indicator_id
            AND ra.cycle_id = ? AND ra.school_id = ?
        WHERE i.is_active = 1
        GROUP BY i.indicator_id
        HAVING required_count > 0
    ");
    $stmt->execute([$cycleId, $schoolId]);
    $rows = $stmt->fetchAll();

    $missing = array_filter($rows, fn($r) => (int) $r['uploaded_count'] < (int) $r['required_count']);
    echo json_encode([
        'ok' => true,
        'summary' => $rows,
        'missing' => array_values($missing),
        'all_met' => empty($missing),
    ]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
exit;