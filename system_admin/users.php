<?php
ob_start();
// ============================================================
// system_admin/users.php — User Management
// Roles: driven by `roles` table (system + custom)
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/stakeholder_lifecycle.php';
requireSystemAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  while (ob_get_level())
    ob_end_clean();
  header('Content-Type: application/json; charset=UTF-8');
  verifyCsrf();
  $action = $_POST['action'];

  if ($action === 'create') {
    $role = $_POST['role'] ?? '';
    $validRoleSlugs = $db->query("SELECT slug FROM roles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($role, $validRoleSlugs, true)) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid role.']);
      exit;
    }
    try {
      $initialStatus = $_POST['status'] ?? 'inactive';
      $schoolId = (int) ($_POST['school_id'] ?: SCHOOL_ID);
      $empId = trim($_POST['employee_id'] ?? '');
      $dept  = trim($_POST['department'] ?? '');
      $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id,employee_id,department) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([trim($_POST['username']), null, trim($_POST['email']), trim($_POST['full_name']), $role, $initialStatus, $schoolId, $empId ?: null, $dept ?: null]);
      $newId = $db->lastInsertId();
      logActivity('create_user', 'users', 'Created: ' . trim($_POST['username']));

      $schoolStmt = $db->prepare("SELECT school_name FROM schools WHERE school_id=?");
      $schoolStmt->execute([$schoolId]);
      $schoolName = $schoolStmt->fetchColumn() ?: '—';

      $newUser = ['user_id' => $newId, 'full_name' => trim($_POST['full_name']), 'email' => trim($_POST['email'])];
      $responseJson = json_encode(['ok' => true, 'msg' => 'User created. A password setup link will be sent via email.', 'emailSent' => true, 'user' => ['id' => $newId, 'full_name' => trim($_POST['full_name']), 'username' => trim($_POST['username']), 'email' => trim($_POST['email']), 'role' => $role, 'status' => $initialStatus, 'school' => $schoolName]]);

      // Close output buffers and send response to browser immediately
      while (ob_get_level())
        ob_end_clean();
      header('Content-Type: application/json');
      header('Content-Length: ' . strlen($responseJson));
      header('Connection: close');
      echo $responseJson;
      flush();
      if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
      }

      // Send email after response delivered (or inline if fastcgi unavailable)
      ignore_user_abort(true);
      set_time_limit(60);
      require_once __DIR__ . '/../includes/email_service.php';
      sendAccountCreationEmail($db, $newUser);
      exit;
    } catch (Exception $e) {
      echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
      exit;
    }
  }

  if ($action === 'get') {
    $st = $db->prepare("SELECT user_id,username,email,full_name,role,status,school_id,department,employee_id FROM users WHERE user_id=?");
    $st->execute([(int) $_POST['id']]);
    echo json_encode($st->fetch());
    exit;
  }

  if ($action === 'update') {
    $id = (int) $_POST['id'];
    $newRole = $_POST['role'] ?? '';
    $validRoleSlugs2 = $db->query("SELECT slug FROM roles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($newRole, $validRoleSlugs2, true)) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid role.']);
      exit;
    }
    try {
      $dept = trim($_POST['department'] ?? '');
      $db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=?,department=? WHERE user_id=?")
        ->execute([trim($_POST['full_name']), trim($_POST['email']), $newRole, $_POST['status'], (int) ($_POST['school_id'] ?: null), $dept ?: null, $id]);
        
      if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['full_name'] = trim($_POST['full_name']);
        $_SESSION['email'] = trim($_POST['email']);
        $_SESSION['role'] = $newRole;
        $_SESSION['department'] = $dept ?: null;
      }

      logActivity('update_user', 'users', 'Updated user ID:' . $id);
      echo json_encode(['ok' => true, 'msg' => 'User updated.']);
      exit;
    } catch (PDOException $e) {
      echo json_encode(['ok' => false, 'msg' => $e->getCode() == 23000 ? 'Username or email already exists.' : 'Database error: ' . $e->getMessage()]);
      exit;
    }
  }

  if ($action === 'create_temp_evaluator') {
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);

    if (!$email || !$fullName || !$cycleId) {
      echo json_encode(['ok' => false, 'msg' => 'All fields are required.']);
      exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid email address.']);
      exit;
    }

    $db->beginTransaction();
    try {
      $existing = $db->prepare("SELECT user_id, status FROM users WHERE email=?");
      $existing->execute([$email]);
      $existingUser = $existing->fetch();

      if ($existingUser) {
        $userId = (int) $existingUser['user_id'];
        if ($existingUser['status'] !== 'active') {
          $db->prepare("UPDATE users SET status='active' WHERE user_id=?")->execute([$userId]);
        }
      } else {
        $username = 'eval_' . substr(md5($email . time()), 0, 8);
        $db->prepare("INSERT INTO users (username, email, full_name, role, status, school_id, force_password_change)
                      VALUES (?, ?, ?, 'external_stakeholder', 'inactive', ?, 1)")
          ->execute([$username, $email, $fullName, SCHOOL_ID]);
        $userId = (int) $db->lastInsertId();
      }

      $db->prepare("INSERT IGNORE INTO cycle_evaluators (cycle_id, user_id, school_id, added_by, is_active)
                    VALUES (?, ?, ?, ?, 1)")
        ->execute([$cycleId, $userId, SCHOOL_ID, $_SESSION['user_id']]);

      $db->commit();

      $userRow = $db->prepare("SELECT user_id, full_name, email FROM users WHERE user_id=?");
      $userRow->execute([$userId]);
      $userRow = $userRow->fetch();

      // Use the specialized stakeholder welcome email
      $sent = sendStakeholderWelcomeEmail($db, $userRow, $cycleId);

      logActivity('create_temp_evaluator', 'users', "Created temp evaluator for cycle $cycleId: $email");

      echo json_encode([
        'ok' => true,
        'msg' => $sent
          ? 'Evaluator added. Stakeholder welcome email sent to ' . $email . '.'
          : 'Evaluator added, but email failed to send. Check mail settings.',
        'user_id' => $userId,
      ]);
    } catch (\Throwable $e) {
      if ($db->inTransaction())
        $db->rollBack();
      echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
    }
    exit;
  }

  if ($action === 'set_cycle_dates') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $start = $_POST['start_date'] ?: null;
    $end = $_POST['end_date'] ?: null;

    if (!$cycleId || !$end) {
      echo json_encode(['ok' => false, 'msg' => 'Cycle ID and Access End Date are required.']);
      exit;
    }

    try {
      $db->prepare("UPDATE sbm_cycles SET stakeholder_access_start=?, stakeholder_access_end=?, auto_deactivated_at=NULL, auto_deactivated_by=NULL WHERE cycle_id=?")
        ->execute([$start, $end, $cycleId]);

      logActivity('set_cycle_dates', 'sbm_cycles', "Updated access window for cycle $cycleId: $start to $end");
      echo json_encode(['ok' => true, 'msg' => 'Access window updated successfully.']);
    } catch (Exception $e) {
      echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
    }
    exit;
  }

  if ($action === 'get_cycle_dates') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $st = $db->prepare("SELECT stakeholder_access_start, stakeholder_access_end, auto_deactivated_at FROM sbm_cycles WHERE cycle_id=?");
    $st->execute([$cycleId]);
    echo json_encode(['ok' => true, 'dates' => $st->fetch()]);
    exit;
  }

  if ($action === 'reactivate_evaluators') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $userIds = $_POST['user_ids'] ?? null; // array or null for ALL
    $newEnd = $_POST['new_end_date'] ?: null;

    if (!$cycleId) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid cycle.']);
      exit;
    }

    $res = reactivateEvaluators($db, $cycleId, $userIds, $newEnd, (int) $_SESSION['user_id']);

    if ($res['reactivated'] > 0) {
      logActivity('reactivate_evaluators', 'users', "Reactivated {$res['reactivated']} evaluators for cycle $cycleId");
      echo json_encode(['ok' => true, 'msg' => "Successfully reactivated {$res['reactivated']} account(s)."]);
    } else {
      echo json_encode(['ok' => false, 'msg' => 'No accounts were reactivated. ' . implode(' ', $res['errors'])]);
    }
    exit;
  }

  if ($action === 'deactivate_cycle_evaluators') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    if (!$cycleId) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid cycle ID.']);
      exit;
    }

    try {
      $db->beginTransaction();

      // 1. Deactivate main user accounts
      $stmtUsers = $db->prepare("
        UPDATE users u
        JOIN cycle_evaluators ce ON ce.user_id = u.user_id
        SET u.status = 'inactive'
        WHERE ce.cycle_id = ? AND u.user_id != ?
      ");
      $stmtUsers->execute([$cycleId, $_SESSION['user_id']]);
      $countUsers = $stmtUsers->rowCount();

      // 2. Mark in cycle_evaluators (Source of truth for the Evaluators Modal)
      $stmtCycle = $db->prepare("
        UPDATE cycle_evaluators
        SET is_active = 0, deactivated_at = NOW()
        WHERE cycle_id = ? AND is_active = 1
      ");
      $stmtCycle->execute([$cycleId]);
      $countCycle = $stmtCycle->rowCount();

      $db->commit();

      logActivity('deactivate_cycle_evaluators', 'users', "Deactivated $countCycle evaluators for cycle $cycleId");
      echo json_encode(['ok' => true, 'msg' => "Deactivated $countCycle evaluator account(s)."]);
    } catch (Exception $e) {
      $db->rollBack();
      echo json_encode(['ok' => false, 'msg' => 'Error deactivating accounts: ' . $e->getMessage()]);
    }
    exit;
  }

  if ($action === 'list_cycle_evaluators') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);

    // --- REAL-TIME ENFORCEMENT ---
    // Check if window is closed and auto-deactivate expired accounts on-the-fly
    $db->prepare("
        UPDATE cycle_evaluators ce
        JOIN sbm_cycles c ON ce.cycle_id = c.cycle_id
        JOIN users u ON ce.user_id = u.user_id
        SET ce.is_active = 0, 
            ce.deactivated_at = NOW(),
            u.status = 'inactive'
        WHERE ce.cycle_id = ? 
          AND ce.is_active = 1
          AND c.stakeholder_access_end IS NOT NULL
          AND c.stakeholder_access_end <= NOW()
    ")->execute([$cycleId]);
    // ----------------------------

    $stmt = $db->prepare("
      SELECT u.user_id, u.full_name, u.email, u.status,
             ce.is_active, ce.deactivated_at, ce.reactivated_at,
             ss.status AS submission_status, ss.submitted_at, ss.response_count
      FROM cycle_evaluators ce
      JOIN users u ON ce.user_id = u.user_id
      LEFT JOIN stakeholder_submissions ss
             ON ss.stakeholder_id = u.user_id AND ss.cycle_id = ce.cycle_id
      WHERE ce.cycle_id = ?
      ORDER BY u.full_name ASC
    ");
    $stmt->execute([$cycleId]);
    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
    exit;
  }

  if ($action === 'remove_cycle_evaluator') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (!$cycleId || !$userId) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid parameters.']);
      exit;
    }
    $stmt = $db->prepare("DELETE FROM cycle_evaluators WHERE cycle_id = ? AND user_id = ?");
    $stmt->execute([$cycleId, $userId]);
    logActivity('remove_cycle_evaluator', 'users', "Removed evaluator $userId from cycle $cycleId");
    echo json_encode(['ok' => true, 'msg' => 'Evaluator removed from cycle.']);
    exit;
  }

  if ($action === 'resend_evaluator_invite') {
    $cycleId = (int) ($_POST['cycle_id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (!$cycleId || !$userId) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid parameters.']);
      exit;
    }

    $check = $db->prepare("SELECT 1 FROM cycle_evaluators WHERE cycle_id = ? AND user_id = ?");
    $check->execute([$cycleId, $userId]);
    if (!$check->fetchColumn()) {
      echo json_encode(['ok' => false, 'msg' => 'This evaluator is not assigned to the selected cycle.']);
      exit;
    }

    $userRow = $db->prepare("SELECT user_id, full_name, email FROM users WHERE user_id=?");
    $userRow->execute([$userId]);
    $userRow = $userRow->fetch();
    if (!$userRow) {
      echo json_encode(['ok' => false, 'msg' => 'Evaluator account not found.']);
      exit;
    }

    $sent = sendStakeholderWelcomeEmail($db, $userRow, $cycleId);
    logActivity('resend_evaluator_invite', 'users', "Resent invitation to user $userId for cycle $cycleId");

    echo json_encode([
      'ok' => $sent,
      'msg' => $sent
        ? 'Invitation resent to ' . $userRow['email'] . '.'
        : 'Failed to resend invitation. Check mail settings.',
    ]);
    exit;
  }

  if ($action === 'toggle_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $targetStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['active', 'inactive', 'archived'];

    if ($id <= 0) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid user account.']);
      exit;
    }
    if (!in_array($targetStatus, $allowedStatuses, true)) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid account status.']);
      exit;
    }
    if ($id === (int) $_SESSION['user_id'] && $targetStatus !== 'active') {
      echo json_encode(['ok' => false, 'msg' => 'You cannot deactivate your own account.']);
      exit;
    }

    $userStmt = $db->prepare("SELECT user_id, full_name, role, status FROM users WHERE user_id=? LIMIT 1");
    $userStmt->execute([$id]);
    $user = $userStmt->fetch();

    if (!$user) {
      echo json_encode(['ok' => false, 'msg' => 'User not found.']);
      exit;
    }
    if ($user['status'] === $targetStatus) {
      echo json_encode([
        'ok' => true,
        'msg' => 'Account status already updated.',
        'status' => $user['status'],
        'nextAction' => $user['status'] === 'active' ? 'deactivate' : 'reactivate'
      ]);
      exit;
    }
    if ($user['role'] === 'system_admin' && $targetStatus !== 'active') {
      $activeAdmins = $db->query("SELECT COUNT(*) FROM users WHERE role='system_admin' AND status='active'")->fetchColumn();
      if ((int) $activeAdmins <= 1) {
        echo json_encode(['ok' => false, 'msg' => 'At least one active System Admin account must remain.']);
        exit;
      }
    }

    try {
      $db->prepare("UPDATE users SET status=? WHERE user_id=?")->execute([$targetStatus, $id]);
      logActivity('toggle_user_status', 'users', 'User ID ' . $id . ' status changed to ' . $targetStatus);
      echo json_encode([
        'ok' => true,
        'msg' => $targetStatus === 'active' ? 'Account reactivated.' : 'Account deactivated.',
        'status' => $targetStatus,
        'nextAction' => $targetStatus === 'active' ? 'deactivate' : 'reactivate'
      ]);
      exit;
    } catch (PDOException $e) {
      echo json_encode(['ok' => false, 'msg' => 'Failed to update account status.']);
      exit;
    }
  }

  if ($action === 'delete') {
    $id = (int) $_POST['id'];
    if ($id === (int) $_SESSION['user_id']) {
      echo json_encode(['ok' => false, 'msg' => 'Cannot delete your own account.']);
      exit;
    }
    try {
      $db->prepare("DELETE FROM users WHERE user_id=?")->execute([$id]);
      logActivity('delete_user', 'users', 'Deleted user ID:' . $id);
      echo json_encode(['ok' => true, 'msg' => 'User deleted.']);
      exit;
    } catch (PDOException $e) {
      // Provide specific feedback for foreign key constraint failures
      $msg = 'Cannot delete user: they have associated activity logs, assessment responses, or submissions. We recommend changing their status to "Suspended" instead to preserve historical data.';
      echo json_encode(['ok' => false, 'msg' => $msg]);
      exit;
    }
  }

  if ($action === 'resend_email') {
    $id = (int) $_POST['id'];
    $u = $db->prepare("SELECT user_id,full_name,email,status FROM users WHERE user_id=?");
    $u->execute([$id]);
    $u = $u->fetch();
    if (!$u) {
      echo json_encode(['ok' => false, 'msg' => 'User not found.']);
      exit;
    }
    if ($u['status'] === 'active') {
      echo json_encode(['ok' => false, 'msg' => 'Account already activated.']);
      exit;
    }
    require_once __DIR__ . '/../includes/email_service.php';
    $sent = sendAccountCreationEmail($db, $u);
    echo json_encode(['ok' => $sent, 'msg' => $sent ? 'Welcome email resent.' : 'Failed to resend email.']);
    exit;
  }

  if ($action === 'import') {
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
      echo json_encode(['ok' => false, 'msg' => 'No file uploaded.']);
      exit;
    }
    $file = $_FILES['csv']['tmp_name'];
    $handle = fopen($file, 'r');
    $headers = fgetcsv($handle);
    $success = 0;
    $failed = 0;
    $errors = [];
    $validRoles = $db->query("SELECT slug FROM roles")->fetchAll(PDO::FETCH_COLUMN);
    $db->beginTransaction();
    try {
      $importedIds = [];
      // Build header index map (case-insensitive, trimmed)
      $headerMap = [];
      foreach ($headers as $i => $h) {
        $headerMap[strtolower(trim($h))] = $i;
      }
      $col = function($name) use ($headerMap, &$row) {
        return isset($headerMap[$name]) && isset($row[$headerMap[$name]]) ? trim($row[$headerMap[$name]]) : '';
      };

      while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) < 3) { $failed++; continue; }

        $employeeId = $col('employee_id');
        $fullName   = $col('full_name');
        $email      = $col('email');
        $department = $col('department');
        $role       = strtolower($col('role'));
        $status     = strtolower($col('status')) ?: 'inactive';
        $username   = $col('username');

        if (!$fullName || !$email || !$username) { $failed++; $errors[] = "Missing required fields in row"; continue; }
        if (!in_array($role, $validRoles)) { $failed++; $errors[] = "Invalid role '$role' for $username"; continue; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $failed++; $errors[] = "Invalid email for $username"; continue; }
        if (!in_array($status, ['active', 'inactive'])) { $status = 'inactive'; }

        try {
          $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id,employee_id,department) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$username, null, $email, $fullName, $role, $status, SCHOOL_ID, $employeeId ?: null, $department ?: null]);
          $newId = $db->lastInsertId();
          $success++;
          $importedIds[] = (int) $newId; // track for post-commit email sending
        } catch (Exception $e) {
          $failed++;
          $errors[] = "Error creating $username: " . $e->getMessage();
        }
      }
      $db->commit();
    } catch (Exception $e) {
      $db->rollBack();
      fclose($handle);
      echo json_encode(['ok' => false, 'msg' => 'Import failed: ' . $e->getMessage()]);
      exit;
    }
    fclose($handle);
    // Send setup emails after transaction committed (outside the transaction to avoid timeout issues)
    if ($success > 0) {
      require_once __DIR__ . '/../includes/email_service.php';
      // Re-fetch newly created inactive users to send emails
      // Collect the IDs inserted during this import batch and email only those,
      // avoiding false positives from pre-existing inactive accounts created
      // within the same 5-minute window.
      if (!empty($importedIds)) {
        $placeholders = implode(',', array_fill(0, count($importedIds), '?'));
        $newUsers = $db->prepare(
          "SELECT user_id, full_name, email FROM users
          WHERE user_id IN ($placeholders) AND status = 'inactive'"
        );
        $newUsers->execute($importedIds);
        foreach ($newUsers->fetchAll() as $nu) {
          sendAccountCreationEmail($db, $nu);
        }
      }
    }
    echo json_encode(['ok' => true, 'msg' => "Import complete. $success success, $failed failed.", 'errors' => $errors]);
  exit;
}

// ── Roles CRUD ──────────────────────────────────────────────
if ($action === 'list_roles') {
  $hierarchyOrder = ['system_admin','school_head','sbm_coordinator','teacher','external_stakeholder'];
  $placeholders = implode(',', array_fill(0, count($hierarchyOrder), '?'));
  $rows = $db->prepare("SELECT id,slug,label,color,description,is_system FROM roles ORDER BY CASE slug " . implode(' ', array_map(fn($s,$i) => "WHEN '$s' THEN $i", $hierarchyOrder, array_keys($hierarchyOrder))) . " ELSE 99 END ASC")->execute([]);
  $rows = $db->query("SELECT id,slug,label,color,description,is_system,(SELECT COUNT(*) FROM users u WHERE u.role = roles.slug COLLATE utf8mb4_unicode_ci) AS user_count FROM roles ORDER BY CASE slug WHEN 'system_admin' THEN 1 WHEN 'school_head' THEN 2 WHEN 'sbm_coordinator' THEN 3 WHEN 'teacher' THEN 4 WHEN 'external_stakeholder' THEN 5 ELSE 6 END ASC, label ASC")->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok' => true, 'data' => $rows]); exit;
}

if ($action === 'save_role') {
  $id    = intval($_POST['id'] ?? 0);
  $label = trim($_POST['label'] ?? '');
  $color = trim($_POST['color'] ?? '#64748B');
  $desc  = trim($_POST['description'] ?? '');
  if (!$label) { echo json_encode(['ok' => false, 'msg' => 'Label is required.']); exit; }
  if ($id) {
    $db->prepare("UPDATE roles SET label=?,color=?,description=? WHERE id=? AND is_system=0")->execute([$label, $color, $desc, $id]);
    echo json_encode(['ok' => true, 'msg' => 'Role updated.']); exit;
  } else {
    $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
    try {
      $db->prepare("INSERT INTO roles (slug,label,color,is_system,description) VALUES (?,?,?,0,?)")->execute([$slug, $label, $color, $desc]);
      echo json_encode(['ok' => true, 'msg' => 'Role added.', 'slug' => $slug, 'description' => $desc]); exit;
    } catch (PDOException $e) {
      echo json_encode(['ok' => false, 'msg' => 'Role slug already exists. Try a different name.']); exit;
    }
  }
}

if ($action === 'delete_role') {
  $id = intval($_POST['id'] ?? 0);
  $role = $db->prepare("SELECT slug,is_system FROM roles WHERE id=?");
  $role->execute([$id]);
  $r = $role->fetch(PDO::FETCH_ASSOC);
  if (!$r) { echo json_encode(['ok' => false, 'msg' => 'Role not found.']); exit; }
  if ($r['is_system']) { echo json_encode(['ok' => false, 'msg' => 'System roles cannot be deleted.']); exit; }
  $inUse = $db->prepare("SELECT COUNT(*) FROM users WHERE role=?")->execute([$r['slug']]);
  $count = $db->prepare("SELECT COUNT(*) FROM users WHERE role=?");
  $count->execute([$r['slug']]);
  if ($count->fetchColumn() > 0) { echo json_encode(['ok' => false, 'msg' => 'Cannot delete — role is assigned to users.']); exit; }
  $db->prepare("DELETE FROM roles WHERE id=? AND is_system=0")->execute([$id]);
  echo json_encode(['ok' => true, 'msg' => 'Role deleted.']); exit;
}
  exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="stakeholder_template.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['full_name', 'email']);
  fclose($out);
  exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'download_user_template') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="user_template.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['employee_id', 'full_name', 'email', 'department', 'role', 'status', 'username']);
  fputcsv($out, ['100-456-789', 'Juan dela Cruz', 'juan@deped.gov.ph', 'Grade 10 - Science', 'teacher', 'active', 'juandelacruz']);
  fclose($out);
  exit;
}

$q = $_GET['q'] ?? '';
$rf = $_GET['role'] ?? '';
$sf = $_GET['status'] ?? '';

// User Accounts only shows processed accounts — pending/rejected registrations
// live exclusively on the Pending Requests page now.
$sql = "SELECT u.user_id,u.username,u.email,u.full_name,u.role,u.status,u.school_id,u.last_login,u.created_at,u.email_verified,u.force_password_change,u.profile_picture,u.department,s.school_name FROM users u LEFT JOIN schools s ON u.school_id=s.school_id WHERE u.status NOT IN ('pending','rejected')";
$p = [];
if ($q) {
  $qE = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($q)) . '%';
  $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
  $p = array_merge($p, [$qE, $qE, $qE]);
}
if ($rf) {
  $sql .= " AND u.role=?";
  $p[] = $rf;
}
if ($sf) {
  $sql .= " AND u.status=?";
  $p[] = $sf;
}
$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($p);
$users = $stmt->fetchAll();

$roleCounts = $db->query("SELECT role,COUNT(*) cnt FROM users WHERE status NOT IN ('pending','rejected') GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalUsers = array_sum($roleCounts);
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$inactiveUsersCount = $db->query("SELECT COUNT(*) FROM users WHERE status='inactive'")->fetchColumn();
$archivedUsersCount = $db->query("SELECT COUNT(*) FROM users WHERE status='archived'")->fetchColumn();

$statusLabels = ['active' => 'Active Accounts', 'inactive' => 'Inactive Accounts', 'archived' => 'Archived Accounts'];
$pageTitle = $statusLabels[$sf] ?? 'User Management';
$activePage = 'users.php';
include __DIR__ . '/../includes/header.php';

$_allRoles  = $db->query("SELECT slug,label,color,description FROM roles ORDER BY CASE slug WHEN 'system_admin' THEN 1 WHEN 'school_head' THEN 2 WHEN 'sbm_coordinator' THEN 3 WHEN 'teacher' THEN 4 WHEN 'external_stakeholder' THEN 5 ELSE 6 END ASC, label ASC")->fetchAll(PDO::FETCH_ASSOC);
$roleColors  = array_column($_allRoles, 'color', 'slug');
$roleLabels  = array_column($_allRoles, 'label', 'slug');
$_allDepts   = $db->prepare("SELECT name FROM departments WHERE school_id=? ORDER BY name ASC");
$_allDepts->execute([SCHOOL_ID]);
$_allDepts   = $_allDepts->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
  .status-tab{border:1px solid #E2E8F0;background:#fff;color:#64748B;font-size:12.5px;font-weight:600;padding:6px 12px;border-radius:7px;cursor:pointer;transition:all .15s;white-space:nowrap;}
  .status-tab:hover{background:#F8FAFC;}
  .status-tab.active{background:#16A34A;border-color:#16A34A;color:#fff;}
</style>

<!-- Page-level actions (no container) -->
<div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
  <button class="btn btn-secondary" onclick="openModal('mImport')"><?= svgIcon('upload') ?> Import CSV</button>
  <button class="btn btn-primary" onclick="openModal('mCreate')"><?= svgIcon('plus') ?> Add User</button>
</div>

<div class="card" style="box-shadow:none;border:1px solid var(--n-150,#e5e7eb);">
  <!-- Table toolbar: search + status filters -->
  <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;width:100%;padding:16px 20px;border-bottom:1px solid var(--n-100,#f1f5f9);">
    <div class="search" style="flex:0 1 320px;min-width:220px;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" id="liveSearch" placeholder="Search by name, username or email…"
        value="<?= e($q) ?>" autocomplete="off"
        style="width:100%;">
    </div>
    <div class="status-filter-tabs" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-left:auto;">
      <button type="button" class="status-tab <?= !$sf ? 'active' : '' ?>" data-status="" onclick="applyStatusFilter('')">All Users (<?= (int)$totalUsers ?>)</button>
      <button type="button" class="status-tab <?= $sf === 'active' ? 'active' : '' ?>" data-status="active" onclick="applyStatusFilter('active')">Active (<?= (int)$activeUsers ?>)</button>
      <button type="button" class="status-tab <?= $sf === 'inactive' ? 'active' : '' ?>" data-status="inactive" onclick="applyStatusFilter('inactive')">Inactive (<?= (int)$inactiveUsersCount ?>)</button>
      <button type="button" class="status-tab <?= $sf === 'archived' ? 'active' : '' ?>" data-status="archived" onclick="applyStatusFilter('archived')">Archived (<?= (int)$archivedUsersCount ?>)</button>
    </div>
  </div>
</div>

<div class="card" style="box-shadow:none;border:1px solid var(--n-150,#e5e7eb);margin-top:16px;">
  <?php if (!$users): ?>
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('users') ?></div>
      <div class="empty-title">No users found</div>
      <div class="empty-sub">
        <?= $q ? 'No users match "' . e($q) . '". Try a different search term.' : 'No users for this role yet.' ?>
      </div>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table id="tblUsers" class="tbl-enhanced">
        <thead>
          <tr>
            <th>User</th>
            <th>Username</th>
            <th>Department</th>
            <th>Role</th>
            <th>Status</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u):
            $rc = $roleColors[$u['role']] ?? '#16A34A';
            $rl = $roleLabels[$u['role']] ?? ucfirst($u['role']);
            ?>
            <tr data-user-id="<?= $u['user_id'] ?>" data-user-status="<?= e($u['status']) ?>">
              <td>
                <div class="cell-avatar">
                  <?php if (!empty($u['profile_picture']) && file_exists(__DIR__ . '/../' . $u['profile_picture'])): ?>
                    <img src="<?= baseUrl() . '/' . $u['profile_picture'] ?>?v=<?= time() ?>"
                         style="width:34px;height:34px;border-radius:9px;object-fit:cover;flex-shrink:0;"
                         alt="<?= e($u['full_name']) ?>">
                  <?php else: ?>
                    <div class="cell-av" style="background:<?= $rc ?>;"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                  <?php endif; ?>
                  <div class="cell-av-info">
                    <div class="cell-av-name"><?= e($u['full_name']) ?></div>
                    <div class="cell-av-sub"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td style="font-family:monospace;font-size:12px;color:var(--n-500);"><?= e($u['username']) ?></td>
              <td style="font-size:12px;color:var(--n-500);">
                <?php if (!empty($u['department'])): ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600;background:var(--n-100);color:var(--n-600);border:1px solid var(--n-200);">
                    <?= e($u['department']) ?>
                  </span>
                <?php else: ?>
                  <span style="color:var(--n-300);">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span
                  style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $rc ?>18;color:<?= $rc ?>;border:1px solid <?= $rc ?>30;">
                  <?= e($rl) ?>
                </span>
              </td>
              <td>
                <?php $statColors = ['active' => ['#DCFCE7', '#16A34A'], 'inactive' => ['var(--n-100)', 'var(--n-500)'], 'archived' => ['#FEF3C7', '#D97706'], 'suspended' => ['var(--red-bg)', 'var(--red)'], 'pending' => ['#FEF9C3', '#CA8A04'], 'rejected' => ['var(--red-bg)', 'var(--red)']];
                [$sb, $sc] = $statColors[$u['status']] ?? ['var(--n-100)', 'var(--n-500)']; ?>
                <span class="user-status-pill"
                  style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $sb ?>;color:<?= $sc ?>;"><?= ucfirst($u['status']) ?></span>
              </td>
              <td>
                <div class="user-row-actions">
                  <!-- Edit -->
                  <button class="btn btn-secondary btn-sm" onclick="editUser(<?= $u['user_id'] ?>)" title="Edit user"><?= svgIcon('edit') ?></button>

                  <!-- Resend email (inactive only) -->
                  <?php if ($u['status'] !== 'active'): ?>
                    <button class="btn btn-blue btn-sm" onclick="resendEmail(<?= $u['user_id'] ?>)" title="Resend welcome email"><?= svgIcon('send') ?></button>
                  <?php endif; ?>

                  <!-- Deactivate / Reactivate (not own account, not archived) -->
                  <?php if ($u['user_id'] != $_SESSION['user_id'] && $u['status'] !== 'archived'): ?>
                    <?php if ($u['status'] === 'active'): ?>
                      <button class="btn btn-warning btn-sm user-status-toggle"
                        data-user-id="<?= $u['user_id'] ?>" data-user-name="<?= e($u['full_name']) ?>"
                        data-current-status="active" onclick="toggleUserStatus(this)" title="Deactivate account">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                      </button>
                    <?php else: ?>
                      <button class="btn btn-success btn-sm user-status-toggle"
                        data-user-id="<?= $u['user_id'] ?>" data-user-name="<?= e($u['full_name']) ?>"
                        data-current-status="<?= e($u['status']) ?>" onclick="toggleUserStatus(this)" title="Reactivate account">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/>
                        </svg>
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>

                  <!-- Archive / Unarchive (not own account) -->
                  <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                    <?php if ($u['status'] === 'archived'): ?>
                      <button class="btn btn-success btn-sm" data-id="<?= $u['user_id'] ?>" data-name="<?= e($u['full_name']) ?>"
                        onclick="unarchiveUser(this.dataset.id, this.dataset.name, this)" title="Unarchive account">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><polyline points="9 12 12 9 15 12"/><line x1="12" y1="9" x2="12" y2="15"/>
                        </svg>
                      </button>
                    <?php else: ?>
                      <button class="btn btn-amber btn-sm" data-id="<?= $u['user_id'] ?>" data-name="<?= e($u['full_name']) ?>"
                        onclick="archiveUser(this.dataset.id, this.dataset.name, this)" title="Archive account">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>
                        </svg>
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<style>
  .user-row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
  }

  .btn-warning {
    background: #FEF3C7; color: #D97706;
    border: 1px solid #FDE68A;
  }
  .btn-warning:hover { background: #FDE68A; }

  .btn-success {
    background: #DCFCE7; color: #16A34A;
    border: 1px solid #BBF7D0;
  }
  .btn-success:hover { background: #BBF7D0; }

  .btn-amber {
    background: #FFF7ED; color: #C2410C;
    border: 1px solid #FDBA74;
  }
  .btn-amber:hover { background: #FDBA74; }

  .row-menu {
    position: relative;
  }

  .row-menu-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 1px solid var(--n-200);
    border-radius: 10px;
    background: #fff;
    color: var(--n-600);
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
  }

  .row-menu-btn:hover,
  .row-menu-btn[aria-expanded="true"] {
    background: var(--n-50);
    border-color: var(--n-300);
    color: var(--n-800);
    box-shadow: var(--shadow-xs);
  }

  .row-menu-list {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 170px;
    padding: 8px;
    border-radius: 14px;
    border: 1px solid var(--n-200);
    background: #fff;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .14);
    display: none;
    z-index: 20;
  }

  .row-menu.open .row-menu-list {
    display: block;
  }

  .row-menu-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: var(--n-700);
    font-size: 13px;
    font-weight: 600;
    padding: 9px 10px;
    cursor: pointer;
    text-align: left;
  }

  .row-menu-item:hover {
    background: var(--n-50);
  }

  .row-menu-item.is-danger {
    color: #B91C1C;
  }

  .row-menu-item.is-success {
    color: #166534;
  }

  .row-menu-item:disabled {
    opacity: .6;
    cursor: wait;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  @keyframes slideInToast {
    from {
      opacity: 0;
      transform: translateX(20px);
    }

    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* ── CUSTOM DATETIME PICKER ── */
  /* Hidden native inputs — still used for form values */
  .dt-premium {
    display: none !important;
  }

  .dt-split {
    display: flex;
    gap: 12px;
  }

  .dt-split>div {
    flex: 1;
  }

  /* Trigger button shown instead of native input */
  .dtp-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #fff;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #0F172A;
    width: 100%;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
    text-align: left;
    position: relative;
  }

  .dtp-trigger:hover {
    border-color: #10B981;
    background: #F0FDF4;
  }

  .dtp-trigger svg {
    flex-shrink: 0;
    stroke: #059669;
  }

  .dtp-trigger-text {
    flex: 1;
    font-weight: 600;
    color: #0F172A;
  }

  .dtp-trigger-text.placeholder {
    color: #94A3B8;
    font-weight: 400;
  }

  /* Popover shell */
  .dtp-popover {
    position: fixed;
    z-index: 9999;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(15, 23, 42, .18), 0 4px 16px rgba(15, 23, 42, .08);
    width: 520px;
    max-width: 96vw;
    overflow: hidden;
    display: none;
    flex-direction: column;
  }

  .dtp-popover.open {
    display: flex;
  }

  /* Popover body: calendar left + time right */
  .dtp-body {
    display: flex;
    height: 340px;
  }

  /* ── CALENDAR SIDE ── */
  .dtp-cal {
    flex: 1;
    padding: 18px 18px 0;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #F1F5F9;
  }

  .dtp-cal-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
  }

  .dtp-cal-nav button {
    width: 28px;
    height: 28px;
    border: none;
    background: none;
    cursor: pointer;
    border-radius: 6px;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .15s;
  }

  .dtp-cal-nav button:hover {
    background: #F1F5F9;
    color: #0F172A;
  }

  .dtp-cal-nav button svg {
    width: 16px;
    height: 16px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .dtp-cal-month {
    font-size: 14px;
    font-weight: 700;
    color: #0F172A;
  }

  .dtp-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    flex: 1;
  }

  .dtp-cal-dow {
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    color: #94A3B8;
    padding: 4px 0 6px;
  }

  .dtp-cal-day {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 34px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #0F172A;
    cursor: pointer;
    transition: background .12s, color .12s;
    border: none;
    background: none;
  }

  .dtp-cal-day:hover:not(.disabled):not(.selected) {
    background: #F1F5F9;
  }

  .dtp-cal-day.other-month {
    color: #CBD5E1;
    pointer-events: none;
  }

  .dtp-cal-day.today:not(.selected) {
    color: #10B981;
    font-weight: 800;
  }

  .dtp-cal-day.selected {
    background: #0F172A;
    color: #fff;
    font-weight: 700;
    border-radius: 8px;
  }

  .dtp-cal-day.disabled {
    color: #CBD5E1;
    pointer-events: none;
    cursor: default;
  }

  /* ── TIME SIDE ── */
  .dtp-time {
    width: 130px;
    overflow-y: auto;
    padding: 10px 8px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    scrollbar-width: thin;
    scrollbar-color: #E2E8F0 transparent;
  }

  .dtp-time::-webkit-scrollbar {
    width: 4px;
  }

  .dtp-time::-webkit-scrollbar-thumb {
    background: #E2E8F0;
    border-radius: 4px;
  }

  .dtp-time-slot {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 9px 0;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    border: 1.5px solid #F1F5F9;
    background: #fff;
    transition: all .12s;
    flex-shrink: 0;
  }

  .dtp-time-slot:hover:not(.selected) {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }

  .dtp-time-slot.disabled {
    color: #CBD5E1;
    pointer-events: none;
    border-color: transparent;
    cursor: not-allowed;
  }

  .dtp-time-slot.selected {
    background: #10B981;
    color: #fff;
    border-color: #10B981;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
  }

  /* ── CONFIRM FOOTER ── */
  .dtp-confirm {
    padding: 16px 20px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .dtp-confirm-text {
    font-size: 13.5px;
    color: #64748B;
  }

  .dtp-confirm-text strong {
    color: #0F172A;
    font-weight: 700;
  }

  .dtp-confirm-btn {
    padding: 9px 20px;
    background: #0F172A;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    box-shadow: 0 4px 6px rgba(15, 23, 42, 0.1);
  }

  .dtp-confirm-btn:hover {
    background: #1E293B;
    transform: translateY(-1px);
  }

  .dtp-confirm-btn:active {
    transform: translateY(0);
  }

  /* ── PREMIUM CSV IMPORT STYLES ── */
  .import-card {
    border: 1px solid var(--n-200);
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  }

  .import-card.drag-over {
    border-color: var(--brand-500);
    background: var(--brand-50);
    transform: scale(1.01);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  }

  .import-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--n-50);
    border-bottom: 1px solid var(--n-100);
    gap: 12px;
  }

  .import-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--brand-600), #059669);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
  }

  .import-schema {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .import-col {
    font-size: 11px;
    font-family: var(--font-mono);
    padding: 2px 8px;
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: 6px;
    color: var(--n-600);
    font-weight: 600;
  }

  .import-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .import-drop-zone {
    border: 2px dashed var(--n-200);
    border-radius: 14px;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--n-30);
    display: block;
    position: relative;
    overflow: hidden;
  }

  .import-drop-zone:hover {
    border-color: var(--brand-400);
    background: var(--brand-50);
  }

  .import-placeholder-state {
    display: block;
  }

  .has-file .import-placeholder-state {
    display: none;
  }

  .import-file-pill {
    display: none;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #fff;
    border: 1px solid var(--brand-200);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    animation: slideInUp 0.3s ease;
  }

  .has-file .import-file-pill {
    display: flex;
  }

  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .import-btn {
    width: 100%;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--brand-600), #059669);
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
  }

  .import-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
    filter: brightness(1.05);
  }

  .import-btn:active {
    transform: translateY(0);
  }

  .import-btn:disabled {
    background: var(--n-200);
    color: var(--n-400);
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
  }

  .import-download-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--brand-600);
    text-decoration: none;
    transition: all 0.2s ease;
    padding: 4px 8px;
    border-radius: 6px;
  }

  .import-download-link:hover {
    background: var(--brand-50);
    text-decoration: underline;
  }

  /* ── MANUAL ENTRY COLLAPSIBLY STYLES ── */
  .manual-entry-card {
    background: var(--n-50);
    border: 1px solid var(--n-200);
    border-radius: 14px;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .manual-entry-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
  }

  .manual-entry-header:hover {
    background: var(--n-100);
  }

  .manual-entry-card.is-expanded {
    background: #fff;
    border-color: var(--brand-200);
    box-shadow: var(--shadow-sm);
  }

  .manual-entry-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0, 1, 0, 1);
    padding: 0 20px;
  }

  .is-expanded .manual-entry-content {
    max-height: 500px;
    transition: max-height 0.4s cubic-bezier(1, 0, 1, 0);
    padding: 0 20px 20px;
  }

  .chevron-icon {
    transition: transform 0.3s ease;
    color: var(--n-400);
  }

  .is-expanded .chevron-icon {
    transform: rotate(180deg);
    color: var(--brand-600);
  }
</style>

<!-- Create Modal -->
<div class="overlay" id="mCreate">
  <div class="modal">
    <div class="modal-head"><span class="modal-title">Create New User</span><button class="modal-close"
        onclick="closeModal('mCreate')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="fg"><label>Employee ID</label><input class="fc" id="c_empid" placeholder="e.g. 100-456-789"></div>
        <div class="fg"><label>Full Name *</label><input class="fc" id="c_name" placeholder="Juan dela Cruz"></div>
      </div>
      <div class="fg"><label>Email *</label><input class="fc" type="email" id="c_email" placeholder="juan@deped.gov.ph"></div>
      <div class="fg">
        <label>Department</label>
        <div class="p-select p-select-fluid" id="pCDeptDropdown">
          <input type="hidden" id="c_dept">
          <div class="p-select-trigger" onclick="togglePSelect(event, 'pCDeptDropdown')">
            <span class="p-select-val" id="pCDeptLabel">Select Department</span>
            <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
          </div>
          <div class="p-select-menu">
            <div class="p-select-item" data-val="" onclick="setCDept('', '— None —')">
              <div class="p-item-content"><div class="p-item-title">— None —</div></div>
              <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
            </div>
            <?php foreach ($_allDepts as $dname): ?>
              <div class="p-select-item" data-val="<?= e($dname) ?>" onclick="setCDept('<?= e($dname) ?>', '<?= e($dname) ?>')">
                <div class="p-item-content"><div class="p-item-title"><?= e($dname) ?></div></div>
                <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="fg">
          <label>Role *</label>
          <div class="p-select p-select-fluid" id="pCRoleDropdown">
            <input type="hidden" id="c_role" value="teacher">
            <div class="p-select-trigger" onclick="togglePSelect(event, 'pCRoleDropdown')">
              <span class="p-select-val" id="pCRoleLabel">Teacher</span>
              <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
            </div>
            <div class="p-select-menu">
              <?php foreach ($_allRoles as $r): ?>
                <div class="p-select-item <?= $r['slug'] === 'teacher' ? 'active' : '' ?>" data-val="<?= e($r['slug']) ?>"
                  onclick="setCRole('<?= e($r['slug']) ?>', '<?= e($r['label']) ?>')">
                  <div class="p-item-content">
                    <div class="p-item-title"><?= e($r['label']) ?></div>
                    <div class="p-item-desc"><?= !empty($r['description']) ? e($r['description']) : ($r['slug'] === 'system_admin' ? 'Total system control' : 'Standard institutional access') ?></div>
                  </div>
                  <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="fg">
          <label>Status</label>
          <div class="p-select p-select-fluid" id="pCStatusDropdown">
            <input type="hidden" id="c_status" value="active">
            <div class="p-select-trigger" onclick="togglePSelect(event, 'pCStatusDropdown')">
              <span class="p-select-val" id="pCStatusLabel">Active</span>
              <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
            </div>
            <div class="p-select-menu">
              <?php foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $val => $lbl): ?>
                <div class="p-select-item <?= $val === 'active' ? 'active' : '' ?>" data-val="<?= $val ?>"
                  onclick="setCStatus('<?= $val ?>', '<?= $lbl ?>')">
                  <div class="p-item-content">
                    <div class="p-item-title"><?= $lbl ?></div>
                    <div class="p-item-desc"><?= $val === 'active' ? 'Account can log in' : 'Access is restricted' ?>
                    </div>
                  </div>
                  <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="fg"><label>Username *</label><input class="fc" id="c_user" placeholder="juandelacruz" autocomplete="off"></div>
      <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;font-size:12.5px;color:#166534;">
        <svg viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        A password setup link will be sent to the user's email after account creation.
      </div>
      <input type="hidden" id="c_school" value="<?= SCHOOL_ID ?>">
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mCreate')">Cancel</button>
      <button class="btn btn-primary" onclick="createUser()">Create User</button>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="overlay" id="mEdit">
  <div class="modal">
    <div class="modal-head"><span class="modal-title">Edit User</span><button class="modal-close"
        onclick="closeModal('mEdit')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="e_id">
      <div class="form-row">
        <div class="fg"><label>Full Name</label><input class="fc" id="e_name"></div>
        <div class="fg"><label>Email</label><input class="fc" type="email" id="e_email"></div>
      </div>
      <div class="fg">
        <label>Department</label>
        <div class="p-select p-select-fluid" id="pEDeptDropdown">
          <input type="hidden" id="e_dept">
          <div class="p-select-trigger" onclick="togglePSelect(event, 'pEDeptDropdown')">
            <span class="p-select-val" id="pEDeptLabel">Select Department</span>
            <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
          </div>
          <div class="p-select-menu">
            <div class="p-select-item" data-val="" onclick="setEDept('', '— None —')">
              <div class="p-item-content"><div class="p-item-title">— None —</div></div>
              <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
            </div>
            <?php foreach ($_allDepts as $dname): ?>
              <div class="p-select-item" data-val="<?= e($dname) ?>" onclick="setEDept('<?= e($dname) ?>', '<?= e($dname) ?>')">
                <div class="p-item-content"><div class="p-item-title"><?= e($dname) ?></div></div>
                <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="fg">
          <label>Role</label>
          <div class="p-select p-select-fluid" id="pERoleDropdown">
            <input type="hidden" id="e_role">
            <div class="p-select-trigger" onclick="togglePSelect(event, 'pERoleDropdown')">
              <span class="p-select-val" id="pERoleLabel">Select Role</span>
              <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
            </div>
            <div class="p-select-menu">
              <?php foreach ($_allRoles as $r): ?>
                <div class="p-select-item" data-val="<?= e($r['slug']) ?>" onclick="setERole('<?= e($r['slug']) ?>', '<?= e($r['label']) ?>')">
                  <div class="p-item-content">
                    <div class="p-item-title"><?= e($r['label']) ?></div>
                    <div class="p-item-desc"><?= !empty($r['description']) ? e($r['description']) : ($r['slug'] === 'system_admin' ? 'Total system control' : 'Standard institutional access') ?></div>
                  </div>
                  <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="fg">
          <label>Status</label>
          <div class="p-select p-select-fluid" id="pEStatusDropdown">
            <input type="hidden" id="e_status">
            <div class="p-select-trigger" onclick="togglePSelect(event, 'pEStatusDropdown')">
              <span class="p-select-val" id="pEStatusLabel">Select Status</span>
              <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
            </div>
            <div class="p-select-menu">
              <?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $val => $lbl): ?>
                <div class="p-select-item" data-val="<?= $val ?>" onclick="setEStatus('<?= $val ?>', '<?= $lbl ?>')">
                  <div class="p-item-content">
                    <div class="p-item-title"><?= $lbl ?></div>
                    <div class="p-item-desc">User account set to <?= strtolower($lbl) ?></div>
                  </div>
                  <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="fg">
        <label>School Assignment</label>
        <div
          style="padding:9px 12px;background:var(--brand-100);border-radius:8px;font-size:13px;font-weight:600;color:var(--brand-700);border:1.5px solid var(--brand-200);">
          Dasmariñas Integrated High School
        </div>
        <input type="hidden" id="e_school" value="<?= SCHOOL_ID ?>">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mEdit')">Cancel</button>
      <button class="btn btn-primary" onclick="updateUser()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div class="overlay" id="mImport">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head"><span class="modal-title">Bulk Import Users</span><button class="modal-close"
        onclick="closeModal('mImport')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div
        style="background:var(--n-50);border-radius:8px;border:1px solid var(--n-100);padding:14px 16px;margin-bottom:16px;">
        <div
          style="font-size:11px;font-weight:700;color:var(--n-400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
          Required CSV format</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <?php foreach (['employee_id', 'full_name', 'email', 'department', 'role', 'status', 'username'] as $col): ?>
            <span style="font-size:12px;font-family:monospace;background:#fff;border:1px solid var(--n-200);border-radius:4px;padding:3px 9px;color:var(--n-700);"><?= $col ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div style="margin-bottom:12px;">
        <div class="import-card" id="userImportCard">
          <div class="import-head">
            <div style="display:flex;align-items:center;gap:12px;">
              <div class="import-icon-wrap" style="background:linear-gradient(135deg, var(--blue), #1E40AF);">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle cx="8.5" cy="7" r="4" />
                  <polyline points="17 11 19 13 23 9" />
                </svg>
              </div>
              <div>
                <div style="font-size:14px;font-weight:800;color:var(--n-900);line-height:1.2;">Bulk User Upload
                </div>
                <div style="font-size:11px;color:var(--n-500);margin-top:2px;">Select CSV to import system users</div>
              </div>
            </div>
            <div class="import-schema">
              <span class="import-col">employee_id</span>
              <span class="import-col">full_name</span>
              <span class="import-col">email</span>
              <span class="import-col">department</span>
              <span class="import-col">role</span>
              <span class="import-col">status</span>
              <span class="import-col">username</span>
            </div>
          </div>

          <div class="import-body">
            <label for="csvFile" class="import-drop-zone" id="userImportDropZone">
              <input type="file" id="csvFile" accept=".csv" style="display:none;" onchange="handleUserCsvSelect(this)">

              <div class="import-placeholder-state" id="userImportPlaceholder">
                <div
                  style="width:44px;height:44px;border-radius:12px;background:var(--blue-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;border:1px solid #BFDBFE;">
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--blue)" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                </div>
                <div style="font-size:14px;font-weight:700;color:var(--n-800);margin-bottom:4px;">Drop CSV here
                </div>
                <div style="font-size:12px;color:var(--n-400);">or <span
                    style="color:var(--blue);font-weight:600;text-decoration:underline;">browse files</span></div>
              </div>

              <!-- Selected file pill -->
              <div class="import-file-pill" id="userImportFilePill">
                <div
                  style="width:32px;height:32px;border-radius:8px;background:var(--blue-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                  </svg>
                </div>
                <div style="flex:1;min-width:0;text-align:left;">
                  <div id="userImportFileName"
                    style="font-size:13px;font-weight:700;color:var(--n-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  </div>
                  <div id="userImportFileSize" style="font-size:11px;color:var(--n-500);"></div>
                </div>
                <button type="button" onclick="event.preventDefault(); clearUserCsv();"
                  style="background:var(--n-100);border:none;cursor:pointer;padding:6px;border-radius:6px;color:var(--n-500);line-height:0;transition:all .2s;"
                  title="Remove file">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                  </svg>
                </button>
              </div>
            </label>

            <button class="import-btn" id="userImportBtn"
              style="background:linear-gradient(135deg, var(--blue), #1E40AF);box-shadow: 0 4px 12px rgba(37, 99, 235, .2);"
              onclick="importUsers()">
              <?= svgIcon('upload') ?> Upload &amp; Import Users
            </button>

            <div style="text-align:center;">
              <a href="users.php?action=download_user_template" class="import-download-link" style="color:var(--blue);">
                <?= svgIcon('download') ?> Download User Template
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mImport')">Cancel</button>
      <button class="btn btn-primary" onclick="importUsers()"><?= svgIcon('upload') ?> Upload &amp;
        Import</button>
    </div>
  <script>
  function closeRowMenus() {
    document.querySelectorAll('.row-menu.open').forEach(menu => {
      menu.classList.remove('open');
      const btn = menu.querySelector('.row-menu-btn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  async function createUser() {
    const d = { action: 'create', full_name: $('c_name'), username: $('c_user'), email: $('c_email'), role: $('c_role'), status: $('c_status'), school_id: $('c_school'), employee_id: $('c_empid'), department: $('c_dept') };
    const r = await apiPost('users.php', d);
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mCreate');['c_name', 'c_user', 'c_email', 'c_empid', 'c_dept'].forEach(id => $v(id, '')); setTimeout(() => location.reload(), 800); }
  }
  async function editUser(id) {
    const r = await apiPost('users.php', { action: 'get', id });
    if (!r || !r.user_id) { toast('Failed to load user.', 'err'); return; }
    $v('e_id', r.user_id); $v('e_name', r.full_name); $v('e_email', r.email);
    const deptVal = r.department || '';
    const deptLabel = deptVal || '— None —';
    setEDept(deptVal, deptLabel);

    // Init custom dropdowns
    const roleMap = <?= json_encode(array_column($_allRoles, 'label', 'slug')) ?>;
    setERole(r.role || 'teacher', roleMap[r.role] || 'Teacher');

    const statusMap = { 'active': 'Active', 'inactive': 'Inactive', 'suspended': 'Suspended' };
    setEStatus(r.status || 'active', statusMap[r.status] || 'Active');

    $v('e_school', r.school_id || '');
    openModal('mEdit');
  }
  async function updateUser() {
    const r = await apiPost('users.php', { action: 'update', id: $('e_id'), full_name: $('e_name'), email: $('e_email'), role: $('e_role'), status: $('e_status'), school_id: $('e_school'), department: $('e_dept') });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mEdit'); setTimeout(() => location.reload(), 800); }
  }
  async function resendEmail(id) {
    if (!confirm('Resend the welcome email with a new password setup link?')) return;
    const r = await apiPost('users.php', { action: 'resend_email', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
  }
  async function toggleUserStatus(btn) {
    const currentStatus = btn.dataset.currentStatus || 'inactive';
    const nextStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const actionLabel = nextStatus === 'active' ? 'reactivate' : 'deactivate';
    const userName = btn.dataset.userName || 'this account';
    if (!confirm(`Are you sure you want to ${actionLabel} "${userName}"?`)) return;

    const row = btn.closest('tr');
    const menu = btn.closest('.row-menu');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin .7s linear infinite;"><path d="M12 2a10 10 0 1 0 10 10"/></svg>`;

    const r = await apiPost('users.php', { action: 'toggle_status', id: btn.dataset.userId, status: nextStatus });
    btn.disabled = false;
    btn.innerHTML = originalHTML;
    closeRowMenus();
    toast(r.msg, r.ok ? 'ok' : 'err');

    if (!r.ok) return;

    setTimeout(() => location.reload(), 500);
  }
  async function delUser(id, name, btn) {
    if (!confirm(`Delete "${name}"?\n\nThis cannot be undone.`)) return;
    const r = await apiPost('users.php', { action: 'delete', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) btn?.closest('tr')?.remove();
  }
  function showUploadToast() {
    const existing = document.getElementById('uploadToast');
    if (existing) existing.remove();
    const el = document.createElement('div');
    el.id = 'uploadToast';
    el.innerHTML = `
      <div style="display:flex;align-items:flex-start;gap:12px;">
        <div style="flex-shrink:0;margin-top:2px;">
          <svg id="uploadSpinner" width="18" height="18" viewBox="0 0 24 24"
               fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round">
            <path d="M12 2a10 10 0 1 0 10 10" style="opacity:.25"/>
            <path d="M12 2a10 10 0 0 1 10 10"/>
          </svg>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:13.5px;color:#0F172A;margin-bottom:2px;">Uploading…</div>
          <div style="font-size:12.5px;color:#64748B;" id="uploadToastSub">Your file is being uploaded.</div>
          <div style="margin-top:8px;height:4px;background:#E2E8F0;border-radius:999px;overflow:hidden;">
            <div id="uploadProgressBar"
                 style="height:100%;width:0%;background:#2563EB;border-radius:999px;transition:width .3s ease;">
            </div>
          </div>
        </div>
        <button onclick="document.getElementById('uploadToast').remove()"
                style="flex-shrink:0;background:none;border:none;cursor:pointer;
                       color:#94A3B8;padding:2px;line-height:0;margin-top:1px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2.5" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>`;
    Object.assign(el.style, {
      position: 'fixed', top: '20px', right: '20px', zIndex: '9999',
      background: '#FFFFFF', border: '1px solid #E2E8F0',
      borderLeft: '4px solid #2563EB', borderRadius: '12px',
      padding: '14px 16px', width: '300px',
      boxShadow: '0 8px 24px rgba(0,0,0,.12)',
      fontFamily: "'Inter',sans-serif",
      animation: 'slideInToast .2s ease',
    });
    document.body.appendChild(el);
    const spinner = el.querySelector('#uploadSpinner');
    let deg = 0;
    const spinInterval = setInterval(() => { deg += 8; spinner.style.transform = `rotate(${deg}deg)`; }, 16);
    el._spinInterval = spinInterval;
    const bar = el.querySelector('#uploadProgressBar');
    let pct = 0;
    const progInterval = setInterval(() => {
      if (pct < 85) { pct += Math.random() * 4; bar.style.width = Math.min(pct, 85) + '%'; }
    }, 120);
    el._progInterval = progInterval;
    return el;
  }

  function finishUploadToast(toastEl, success, message) {
    if (!toastEl) return;
    clearInterval(toastEl._spinInterval);
    clearInterval(toastEl._progInterval);
    const bar = toastEl.querySelector('#uploadProgressBar');
    const sub = toastEl.querySelector('#uploadToastSub');
    const spinWrap = toastEl.querySelector('[style*="flex-shrink:0;margin-top:2px"]');
    const title = toastEl.querySelector('[style*="font-weight:700"]');
    bar.style.width = '100%';
    bar.style.background = success ? '#16A34A' : '#DC2626';
    toastEl.style.borderLeftColor = success ? '#16A34A' : '#DC2626';
    if (spinWrap) {
      spinWrap.innerHTML = success
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#16A34A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
             <polyline points="20 6 9 17 4 12"/>
           </svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
             <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
           </svg>`;
    }
    if (title) title.textContent = success ? 'Upload complete!' : 'Upload failed';
    if (sub) sub.textContent = message || (success ? 'Import finished successfully.' : 'Something went wrong.');
    setTimeout(() => {
      if (toastEl.parentNode) {
        toastEl.style.opacity = '0'; toastEl.style.transition = 'opacity .3s ease';
        setTimeout(() => toastEl.remove(), 300);
      }
    }, 3000);
  }

  // ── Roles Modal ──────────────────────────────────────────

  async function importUsers() {
    const file = document.getElementById('csvFile').files[0];
    if (!file) { toast('Please select a CSV file.', 'err'); return; }
    const formData = new FormData();
    formData.append('action', 'import'); formData.append('csv', file);
    formData.append('csrf_token', '<?= csrfToken() ?>');
    closeModal('mImport');
    const uploadToastEl = showUploadToast();
    try {
      const response = await fetch('users.php', { method: 'POST', body: formData });
      const r = await response.json();
      finishUploadToast(uploadToastEl, r.ok, r.msg);
      if (r.ok) setTimeout(() => location.reload(), 2000);
    } catch (e) {
      finishUploadToast(uploadToastEl, false, 'Network error. Please try again.');
    }
  }

  function handleUserCsvSelect(input) {
    const file = input.files[0];
    const card = document.getElementById('userImportCard');
    const btn = document.getElementById('userImportBtn');
    if (!file) { clearUserCsv(); return; }
    card.classList.add('has-file');
    document.getElementById('userImportFileName').textContent = file.name;
    document.getElementById('userImportFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    btn.disabled = false;
  }

  function clearUserCsv() {
    const input = document.getElementById('csvFile');
    input.value = '';
    document.getElementById('userImportCard').classList.remove('has-file');
    document.getElementById('userImportFileName').textContent = '';
    document.getElementById('userImportFileSize').textContent = '';
    document.getElementById('userImportBtn').disabled = true;
  }

  // Drag-and-drop wiring
  document.addEventListener('DOMContentLoaded', () => {
    // Users Zone
    const userZone = document.getElementById('userImportDropZone');
    const userCard = document.getElementById('userImportCard');
    if (userZone) {
      userZone.addEventListener('dragover', e => { e.preventDefault(); userCard.classList.add('drag-over'); });
      userZone.addEventListener('dragleave', () => userCard.classList.remove('drag-over'));
      userZone.addEventListener('drop', e => {
        e.preventDefault(); userCard.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
          const input = document.getElementById('csvFile');
          const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
          handleUserCsvSelect(input);
        } else { toast('Please drop a .csv file.', 'warning'); }
      });
    }
  });

  async function unarchiveUser(id, name, btn) {
    if (!confirm(`Unarchive "${name}"? Their account will be set to inactive.`)) return;
    const r = await apiPost('users.php', { action: 'toggle_status', id, status: 'inactive' });
    if (r.ok) {
      toast('Account unarchived.', 'ok');
      setTimeout(() => location.reload(), 800);
    } else {
      toast(r.msg || 'Failed to unarchive.', 'err');
    }
  }

  async function archiveUser(id, name, btn) {
    if (!confirm(`Archive "${name}"? They will no longer be able to log in.`)) return;
    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    fd.append('id', id);
    fd.append('status', 'archived');
    const r = await apiPost('users.php', Object.fromEntries(fd));
    if (r.ok) {
      toast('Account archived.', 'ok');
      btn.closest('tr').querySelector('.user-status-pill').textContent = 'Archived';
      btn.closest('tr').querySelector('.user-status-pill').style.background = '#FEF3C7';
      btn.closest('tr').querySelector('.user-status-pill').style.color = '#D97706';
      setTimeout(() => location.reload(), 800);
    } else {
      toast(r.msg || 'Failed to archive.', 'err');
    }
  }

  function fetchAndSwap(url) {
    fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTable = doc.querySelector('#tblUsers')?.closest('.tbl-wrap') || doc.querySelector('.empty-state');
        const oldTable = document.querySelector('#tblUsers')?.closest('.tbl-wrap') || document.querySelector('.empty-state');
        const card = document.querySelector('.card');
        if (newTable && oldTable) {
          oldTable.replaceWith(newTable);
        } else if (newTable && card) {
          card.appendChild(newTable);
        }
        const newTitle = doc.querySelector('.card-title');
        const oldTitle = document.querySelector('.card-title');
        if (newTitle && oldTitle) oldTitle.innerHTML = newTitle.innerHTML;
        const newTabs = doc.querySelector('.status-filter-tabs');
        const oldTabs = document.querySelector('.status-filter-tabs');
        if (newTabs && oldTabs) oldTabs.outerHTML = newTabs.outerHTML;
        history.replaceState(null, '', url.toString());
      });
  }

  function applyStatusFilter(status) {
    const url = new URL(window.location.href);
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    fetchAndSwap(url);
  }

  (function () {
    const input = document.getElementById('liveSearch');
    if (!input) return;
    let debounce;
    input.addEventListener('input', function () {
      clearTimeout(debounce);
      debounce = setTimeout(() => {
        const q = input.value.trim();
        const url = new URL(window.location.href);
        if (q) url.searchParams.set('q', q);
        else url.searchParams.delete('q');
        fetchAndSwap(url);
      }, 300);
    });
  })();

  // mCreate helpers
  function setCRole(v, l) {
    $v('c_role', v);
    document.getElementById('pCRoleLabel').textContent = l;
    document.querySelectorAll('#pCRoleDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }
  function setCStatus(v, l) {
    $v('c_status', v);
    document.getElementById('pCStatusLabel').textContent = l;
    document.querySelectorAll('#pCStatusDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }

  function setCDept(v, l) {
    $v('c_dept', v);
    document.getElementById('pCDeptLabel').textContent = l;
    document.querySelectorAll('#pCDeptDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }

  // mEdit helpers
  function setEDept(v, l) {
    $v('e_dept', v);
    // Try to find a matching item in the dropdown
    const items = document.querySelectorAll('#pEDeptDropdown .p-select-item');
    let matched = false;
    items.forEach(i => {
      const match = i.dataset.val.trim().toLowerCase() === (v || '').trim().toLowerCase();
      i.classList.toggle('active', match);
      if (match) {
        document.getElementById('pEDeptLabel').textContent = i.dataset.val;
        $v('e_dept', i.dataset.val);
        matched = true;
      }
    });
    if (!matched) {
      document.getElementById('pEDeptLabel').textContent = v ? v + ' (not found)' : '— None —';
    }
    closeAllPSelects();
  }
  function setERole(v, l) {
    $v('e_role', v);
    document.getElementById('pERoleLabel').textContent = l;
    document.querySelectorAll('#pERoleDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }
  function setEStatus(v, l) {
    $v('e_status', v);
    document.getElementById('pEStatusLabel').textContent = l;
    document.querySelectorAll('#pEStatusDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }

  window.addEventListener('DOMContentLoaded', () => {
    if (new URLSearchParams(window.location.search).get('action') === 'create') openModal('mCreate');
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.row-menu')) closeRowMenus();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeRowMenus();
    }
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>