<?php
// includes/profile_handler.php — handles profile update AJAX requests
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');
verifyCsrf();

$db = getDB();
$uid = (int) $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

/* ── 1. SAVE PROFILE ─────────────────────────────────────── */
if ($action === 'save_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');

    // Validation
    if (!$fullName) {
        echo json_encode(['ok' => false, 'msg' => 'Full name is required.']);
        exit;
    }
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        echo json_encode(['ok' => false, 'msg' => 'Full name must be between 2 and 120 characters.']);
        exit;
    }
    if ($contact && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $contact)) {
        echo json_encode(['ok' => false, 'msg' => 'Please enter a valid contact number.']);
        exit;
    }

    // Handle profile picture upload
    $picturePath = null;
    if (!empty($_FILES['profile_picture']['tmp_name'])) {
        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'Only JPG, PNG, or WEBP images are allowed.']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'msg' => 'Image must be under 5 MB.']);
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
        $fileName = 'avatar_' . $uid . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $error = 'Failed to save image at: ' . $dest . '. Check permissions.';
            error_log($error);
            echo json_encode(['ok' => false, 'msg' => 'Failed to save image. Please try again.']);
            exit;
        }

        // Verify file was actually saved
        if (!file_exists($dest)) {
            error_log('Avatar file missing after upload: ' . $dest);
            echo json_encode(['ok' => false, 'msg' => 'Image save verification failed. Please try again.']);
            exit;
        }

        // Delete old avatar if exists
        $oldStmt = $db->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $oldStmt->execute([$uid]);
        $oldPic = $oldStmt->fetchColumn();
        if ($oldPic) {
            $oldPath = __DIR__ . '/../' . ltrim($oldPic, '/');
            if (file_exists($oldPath) && strpos($oldPath, 'uploads/avatars/') !== false) {
                @unlink($oldPath);
            }
        }

        $picturePath = 'uploads/avatars/' . $fileName;
        error_log('Saving avatar: user_id=' . $uid . ', path=' . $picturePath);
    }

    // Build update query
    if ($picturePath) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, contact_number = ?, profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$fullName, $contact ?: null, $picturePath, $uid]);
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, contact_number = ? WHERE user_id = ?");
        $stmt->execute([$fullName, $contact ?: null, $uid]);
    }

    // Refresh session name and avatar
    $_SESSION['full_name'] = $fullName;
    if ($picturePath) {
        $_SESSION['profile_picture'] = $picturePath;
    }

    logActivity('update_profile', 'profile', 'User updated their profile');

    // Return updated data
    $userStmt = $db->prepare("SELECT full_name, contact_number, profile_picture FROM users WHERE user_id = ?");
    $userStmt->execute([$uid]);
    $updated = $userStmt->fetch();

    $responseData = [
        'ok' => true,
        'msg' => 'Profile updated successfully.',
        'full_name' => $updated['full_name'],
        'contact_number' => $updated['contact_number'] ?? '',
        'profile_picture' => $updated['profile_picture'] ?? null,
    ];
    
    if ($picturePath) {
        error_log('Profile save response: picture_path=' . ($updated['profile_picture'] ?? 'NULL'));
    }

    echo json_encode($responseData);
    exit;
}

/* ── 2. CHANGE PASSWORD ──────────────────────────────────── */
if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        echo json_encode(['ok' => false, 'msg' => 'All password fields are required.']);
        exit;
    }
    if (strlen($new) < 8) {
        echo json_encode(['ok' => false, 'msg' => 'New password must be at least 8 characters.']);
        exit;
    }
    if ($new !== $confirm) {
        echo json_encode(['ok' => false, 'msg' => 'New passwords do not match.']);
        exit;
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$uid]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        echo json_encode(['ok' => false, 'msg' => 'Current password is incorrect.']);
        exit;
    }

    $db->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE user_id = ?")
        ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);

    logActivity('password_change', 'profile', 'User changed their password');
    echo json_encode(['ok' => true, 'msg' => 'Password changed successfully.']);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Invalid action.']);