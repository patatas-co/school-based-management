<?php
ob_start();
// ============================================================
// system_admin/users.php — User Management
// Roles: system_admin | school_head | sbm_coordinator | teacher | external_stakeholder
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
    $pw = $_POST['password'] ?? '';
    if ($pw && strlen($pw) < 8) {
      echo json_encode(['ok' => false, 'msg' => 'Password must be at least 8 characters.']);
      exit;
    }
    $role = $_POST['role'] ?? '';
    if (!in_array($role, ['system_admin', 'school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'], true)) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid role.']);
      exit;
    }
    try {
      $hashedPw = $pw ? password_hash($pw, PASSWORD_DEFAULT) : null;
      $initialStatus = $pw ? ($_POST['status'] ?? 'active') : 'inactive';
      $schoolId = (int) ($_POST['school_id'] ?: SCHOOL_ID);
      $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id) VALUES (?,?,?,?,?,?,?)")
        ->execute([trim($_POST['username']), $hashedPw, trim($_POST['email']), trim($_POST['full_name']), $role, $initialStatus, $schoolId]);
      $newId = $db->lastInsertId();
      logActivity('create_user', 'users', 'Created: ' . trim($_POST['username']));

      $schoolStmt = $db->prepare("SELECT school_name FROM schools WHERE school_id=?");
      $schoolStmt->execute([$schoolId]);
      $schoolName = $schoolStmt->fetchColumn() ?: '—';

      if (!$pw) {
        $newUser = ['user_id' => $newId, 'full_name' => trim($_POST['full_name']), 'email' => trim($_POST['email'])];
        $emailMsg = 'User created. A password setup link will be sent via email.';
        $responseJson = json_encode(['ok' => true, 'msg' => $emailMsg, 'emailSent' => true, 'user' => ['id' => $newId, 'full_name' => trim($_POST['full_name']), 'username' => trim($_POST['username']), 'email' => trim($_POST['email']), 'role' => $role, 'status' => $initialStatus, 'school' => $schoolName]]);

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
      } else {
        $emailMsg = 'User created with the provided password.';
        echo json_encode(['ok' => true, 'msg' => $emailMsg, 'emailSent' => false, 'user' => ['id' => $newId, 'full_name' => trim($_POST['full_name']), 'username' => trim($_POST['username']), 'email' => trim($_POST['email']), 'role' => $role, 'status' => $initialStatus, 'school' => $schoolName]]);
      }
      exit;
    } catch (Exception $e) {
      echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
      exit;
    }
  }

  if ($action === 'get') {
    $st = $db->prepare("SELECT user_id,username,email,full_name,role,status,school_id FROM users WHERE user_id=?");
    $st->execute([(int) $_POST['id']]);
    echo json_encode($st->fetch());
    exit;
  }

  if ($action === 'update') {
    $id = (int) $_POST['id'];
    $pw = $_POST['password'] ?? '';
    $newRole = $_POST['role'] ?? '';
    if (!in_array($newRole, ['system_admin', 'school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'], true)) {
      echo json_encode(['ok' => false, 'msg' => 'Invalid role.']);
      exit;
    }
    try {
      if ($pw) {
        $db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=?,password=? WHERE user_id=?")
          ->execute([trim($_POST['full_name']), trim($_POST['email']), $newRole, $_POST['status'], (int) ($_POST['school_id'] ?: null), password_hash($pw, PASSWORD_DEFAULT), $id]);
      } else {
        $db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=? WHERE user_id=?")
          ->execute([trim($_POST['full_name']), trim($_POST['email']), $newRole, $_POST['status'], (int) ($_POST['school_id'] ?: null), $id]);
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

  if ($action === 'toggle_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $targetStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['active', 'inactive'];

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
    $validRoles = ['system_admin', 'school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'];
    $db->beginTransaction();
    try {
      $importedIds = [];
      while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) < 4) {
          $failed++;
          continue;
        }
        [$fullName, $username, $email, $role] = array_map('trim', array_slice($row, 0, 4));
        $password = isset($row[4]) ? trim($row[4]) : null;
        if (!in_array($role, $validRoles)) {
          $failed++;
          $errors[] = "Invalid role for $username";
          continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $failed++;
          $errors[] = "Invalid email for $username";
          continue;
        }
        try {
          $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id) VALUES (?,?,?,?,?,?,?)")
            ->execute([$username, $password ? password_hash($password, PASSWORD_DEFAULT) : null, $email, $fullName, $role, $password ? 'active' : 'inactive', SCHOOL_ID]);
          $newId = $db->lastInsertId();
          $success++;
          if (!$password) {
            $importedIds[] = (int) $newId; // track for post-commit email sending
          }
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
  fputcsv($out, ['full_name', 'username', 'email', 'role', 'password']);
  fclose($out);
  exit;
}

$q = $_GET['q'] ?? '';
$rf = $_GET['role'] ?? '';
$sf = $_GET['status'] ?? '';

$sql = "SELECT u.user_id,u.username,u.email,u.full_name,u.role,u.status,u.school_id,u.last_login,u.created_at,u.email_verified,u.force_password_change,s.school_name FROM users u LEFT JOIN schools s ON u.school_id=s.school_id WHERE 1=1";
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

$roleCounts = $db->query("SELECT role,COUNT(*) cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalUsers = array_sum($roleCounts);
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();

$pageTitle = 'User Management';
$activePage = 'users.php';
include __DIR__ . '/../includes/header.php';

$roleColors = [
  'system_admin' => '#7C3AED', // Purple
  'school_head' => '#166534', // Deeper green
  'sbm_coordinator' => '#2563EB', // Blue
  'teacher' => '#0D9488', // Teal
  'external_stakeholder' => '#D97706', // Amber
];
$roleLabels = [
  'system_admin' => 'System Admin',
  'school_head' => 'School Head',
  'sbm_coordinator' => 'SBM Coordinator',
  'teacher' => 'School Teacher',
  'external_stakeholder' => 'Stakeholder',
];
?>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Management</div>
    <div class="ph2-title">User Management</div>
    <div class="ph2-sub">Manage all portal accounts — <?= $activeUsers ?> active users.</div>
  </div>
  <div class="ph2-right">
    <button class="btn btn-secondary" onclick="openModal('mImport')"><?= svgIcon('upload') ?> Import CSV</button>
    <button class="btn btn-secondary" onclick="openModal('mEvaluators')"><?= svgIcon('users') ?> Manage
      Evaluators</button>
    <button class="btn btn-primary" onclick="openModal('mCreate')"><?= svgIcon('plus') ?> Add User</button>
  </div>
</div>

<!-- Role tabs -->
<div class="status-tabs">
  <a href="users.php<?= ($q || $sf) ? "?" . http_build_query(array_filter(['q' => $q, 'status' => $sf])) : '' ?>"
    class="status-tab <?= !$rf ? 'active' : '' ?>">
    All <span class="status-tab-count"><?= $totalUsers ?></span>
  </a>
  <?php foreach (['system_admin', 'school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'] as $r):
    $cnt = $roleCounts[$r] ?? 0;
    if (!$cnt)
      continue; ?>
    <a href="users.php?role=<?= $r ?><?= ($q || $sf) ? "&" . http_build_query(array_filter(['q' => $q, 'status' => $sf])) : '' ?>"
      class="status-tab <?= $rf === $r ? 'active' : '' ?>">
      <span
        style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= $roleColors[$r] ?>;margin-right:4px;"></span>
      <?= $roleLabels[$r] ?>
      <span class="status-tab-count"><?= $cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Search -->
<div class="filter-bar-v2">
  <form method="get" class="flex-c" style="gap:10px;flex:1;">
    <div class="search" style="flex:1;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" name="q" placeholder="Search by name, username or email…" value="<?= e($q) ?>">
      <?php if ($rf): ?><input type="hidden" name="role" value="<?= e($rf) ?>"><?php endif; ?>
    </div>
    <div class="p-select" id="pStatusDropdown">
      <input type="hidden" name="status" id="pStatusValue" value="<?= e($sf) ?>">
      <div class="p-select-trigger" onclick="togglePSelect(event)">
        <span class="p-select-val">
          <?= $sf === 'active' ? 'Active' : ($sf === 'inactive' ? 'Inactive' : 'All Status') ?>
        </span>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--n-400)" stroke-width="2.5"
          stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </div>
      <div class="p-select-menu">
        <!-- All Status -->
        <div class="p-select-item <?= !$sf ? 'active' : '' ?>" onclick="setPStatus('')">
          <div class="p-item-content">
            <div class="p-item-title">All Status</div>
            <div class="p-item-desc">View all accounts</div>
          </div>
          <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
        </div>
        <!-- Active -->
        <div class="p-select-item <?= $sf === 'active' ? 'active' : '' ?>" onclick="setPStatus('active')">
          <div class="p-item-content">
            <div class="p-item-title">Active</div>
            <div class="p-item-desc">Can access the portal</div>
          </div>
          <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
        </div>
        <!-- Inactive -->
        <div class="p-select-item <?= $sf === 'inactive' ? 'active' : '' ?>" onclick="setPStatus('inactive')">
          <div class="p-item-content">
            <div class="p-item-title">Inactive</div>
            <div class="p-item-desc">Account is locked</div>
          </div>
          <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
        </div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($q || $rf || $sf): ?><a href="users.php" class="btn btn-secondary btn-sm">Reset</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="card-title">
      <?= $rf ? ($roleLabels[$rf] ?? ucfirst($rf)) . 's' : 'All Users' ?>
      <span
        style="font-weight:400;color:var(--n-400);font-family:var(--font-body);font-size:13px;">(<?= count($users) ?>)</span>
    </span>
  </div>
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
            <th>Role</th>
            <th>Status</th>
            <th>Age</th>
            <th>Last Login</th>
            <th></th>
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
                  <div class="cell-av" style="background:<?= $rc ?>;"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                  <div class="cell-av-info">
                    <div class="cell-av-name"><?= e($u['full_name']) ?></div>
                    <div class="cell-av-sub"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td style="font-family:monospace;font-size:12px;color:var(--n-500);"><?= e($u['username']) ?></td>
              <td>
                <span
                  style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $rc ?>18;color:<?= $rc ?>;border:1px solid <?= $rc ?>30;">
                  <?= e($rl) ?>
                </span>
              </td>
              <td>
                <?php $statColors = ['active' => ['#DCFCE7', '#16A34A'], 'inactive' => ['var(--n-100)', 'var(--n-500)'], 'suspended' => ['var(--red-bg)', 'var(--red)']];
                [$sb, $sc] = $statColors[$u['status']] ?? ['var(--n-100)', 'var(--n-500)']; ?>
                <span class="user-status-pill"
                  style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $sb ?>;color:<?= $sc ?>;"><?= ucfirst($u['status']) ?></span>
              </td>
              <td style="font-size:12px;color:var(--n-500);font-weight:600;">
                <?php $days = floor((time() - strtotime($u['created_at'])) / 86400);
                echo ($days <= 0 ? 'Today' : number_format($days) . ' days'); ?>
              </td>
              <td style="font-size:12px;color:<?= $u['last_login'] ? 'var(--n-400)' : 'var(--red)' ?>;">
                <?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?>
              </td>
              <td>
                <div class="user-row-actions">
                  <button class="btn btn-secondary btn-sm"
                    onclick="editUser(<?= $u['user_id'] ?>)"><?= svgIcon('edit') ?></button>
                  <?php if ($u['status'] !== 'active'): ?>
                    <button class="btn btn-blue btn-sm" onclick="resendEmail(<?= $u['user_id'] ?>)"
                      title="Resend welcome email"><?= svgIcon('send') ?></button>
                  <?php endif; ?>
                  <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                    <button class="btn btn-danger btn-sm" data-id="<?= $u['user_id'] ?>" data-name="<?= e($u['full_name']) ?>"
                      onclick="delUser(this.dataset.id,this.dataset.name,this)"><?= svgIcon('trash') ?></button>
                  <?php endif; ?>
                  <?php if ($u['user_id'] != $_SESSION['user_id'] && in_array($u['status'], ['active', 'inactive'], true)): ?>
                    <div class="row-menu">
                      <button type="button" class="row-menu-btn" aria-label="Open account actions" aria-expanded="false"
                        onclick="toggleRowMenu(this)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
                          <circle cx="12" cy="5" r="1.8"></circle>
                          <circle cx="12" cy="12" r="1.8"></circle>
                          <circle cx="12" cy="19" r="1.8"></circle>
                        </svg>
                      </button>
                      <div class="row-menu-list" role="menu">
                        <button type="button"
                          class="row-menu-item user-status-toggle <?= $u['status'] === 'active' ? 'is-danger' : 'is-success' ?>"
                          data-user-id="<?= $u['user_id'] ?>" data-user-name="<?= e($u['full_name']) ?>"
                          data-current-status="<?= e($u['status']) ?>" onclick="toggleUserStatus(this)">
                          <?= $u['status'] === 'active' ? 'Deactivate account' : 'Reactivate account' ?>
                        </button>
                      </div>
                    </div>
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
    justify-content: flex-end;
    gap: 4px;
  }

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

  /* Premium Date Inputs */
  .dt-premium {
    appearance: none;
    -webkit-appearance: none;
    background: #ffffff;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #0F172A;
    width: 100%;
    transition: all 0.2s ease;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .dt-premium:hover {
    border-color: #10B981;
    background: #F0FDF4;
  }

  .dt-premium:focus {
    outline: none;
    border-color: #10B981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    background: #ffffff;
  }

  /* Split layout */
  .dt-split {
    display: flex;
    gap: 12px;
  }

  .dt-split>div {
    flex: 1;
  }

  /* Custom picker icons */
  .dt-premium::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.8;
  }

  /* Date specific icon */
  input[type="date"].dt-premium::-webkit-calendar-picker-indicator {
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") no-repeat center;
  }

  /* Time specific icon */
  input[type="time"].dt-premium::-webkit-calendar-picker-indicator {
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center;
  }

  .dt-premium::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
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
        <div class="fg"><label>Full Name *</label><input class="fc" id="c_name" placeholder="Juan dela Cruz"></div>
        <div class="fg"><label>Username *</label><input class="fc" id="c_user" placeholder="juandelacruz"
            autocomplete="off"></div>
      </div>
      <div class="fg"><label>Email *</label><input class="fc" type="email" id="c_email" placeholder="juan@deped.gov.ph">
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
              <?php foreach (['system_admin' => 'System Admin', 'school_head' => 'School Head', 'sbm_coordinator' => 'SBM Coordinator', 'teacher' => 'Teacher'] as $val => $lbl): ?>
                <div class="p-select-item <?= $val === 'teacher' ? 'active' : '' ?>" data-val="<?= $val ?>"
                  onclick="setCRole('<?= $val ?>', '<?= $lbl ?>')">
                  <div class="p-item-content">
                    <div class="p-item-title"><?= $lbl ?></div>
                    <div class="p-item-desc"><?= $val === 'system_admin' ? 'Total system control' : 'Standard institutional access' ?>
                    </div>
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
                    <div class="p-item-desc"><?= $val === 'active' ? 'Account can log in' : 'Access is restricted' ?></div>
                  </div>
                  <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="fg">
        <label>Password <span style="font-weight:400;color:var(--n-400);">(leave blank to send setup
            link)</span></label>
        <input class="fc" type="password" id="c_pass" placeholder="Leave blank — user sets password via email"
          autocomplete="new-password">
        <input type="hidden" id="c_school" value="<?= SCHOOL_ID ?>">
      </div>
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
              <?php foreach (['system_admin' => 'System Admin', 'school_head' => 'School Head', 'sbm_coordinator' => 'SBM Coordinator', 'teacher' => 'Teacher', 'external_stakeholder' => 'External Stakeholder'] as $val => $lbl): ?>
                <div class="p-select-item" data-val="<?= $val ?>" onclick="setERole('<?= $val ?>', '<?= $lbl ?>')">
                  <div class="p-item-content">
                    <div class="p-item-title"><?= $lbl ?></div>
                    <div class="p-item-desc"><?= e($lbl) ?> account access level</div>
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
      <div class="fg">
        <label>New Password <span style="font-weight:400;color:var(--n-400);">(leave blank to keep
            current)</span></label>
        <input class="fc" type="password" id="e_pass" placeholder="Leave blank to keep current"
          autocomplete="new-password">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mEdit')">Cancel</button>
      <button class="btn btn-primary" onclick="updateUser()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Evaluator Management Modal -->
<div class="overlay" id="mEvaluators">
  <div class="modal" style="max-width:680px;">
    <div class="modal-head">
      <span class="modal-title">Manage Cycle Evaluators</span>
      <button class="modal-close" onclick="closeModal('mEvaluators')">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="modal-body">

      <!-- Cycle selector -->
      <div class="fg">
        <label>Assessment Cycle *</label>
        <div class="p-select p-select-fluid" id="pCycleDropdown">
          <input type="hidden" id="ev_cycle_id">
          <div class="p-select-trigger" onclick="togglePSelect(event, 'pCycleDropdown')">
            <span class="p-select-val" id="pCycleLabel">Select a cycle</span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--n-400)" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </div>
          <div class="p-select-menu">
            <?php
            $cycles = $db->query("
              SELECT c.cycle_id, sy.label, c.status
              FROM sbm_cycles c
              JOIN school_years sy ON c.sy_id = sy.sy_id
              WHERE c.school_id = " . SCHOOL_ID . "
              ORDER BY c.cycle_id DESC
            ")->fetchAll();
            foreach ($cycles as $cyc):
              $label = "SY " . e($cyc['label']) . " — " . ucfirst(str_replace('_', ' ', $cyc['status']));
              $desc = ($cyc['status'] === 'open') ? "Currently assessing school year" : "Historical assessment records";
              ?>
              <div class="p-select-item" onclick="setMCycle('<?= $cyc['cycle_id'] ?>', '<?= e($label) ?>')">
                <div class="p-item-content">
                  <div class="p-item-title"><?= e($label) ?></div>
                  <div class="p-item-desc"><?= $desc ?></div>
                </div>
                <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Stakeholder Access Window Card -->
      <div id="cycleDatesCard" style="display:none;margin-bottom:20px;">
        <div
          style="background:var(--brand-50);border:1px solid var(--brand-200);border-radius:16px;padding:20px;box-shadow:var(--shadow-sm);">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
            <div>
              <div
                style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--brand-700);margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                <div style="width:6px;height:6px;border-radius:50%;background:var(--brand-600);"></div>
                Stakeholder Access Window
              </div>
              <div id="cycleStatusBanner" style="font-size:14px;font-weight:700;color:var(--n-900);"></div>
            </div>
            <button class="btn btn-primary" onclick="saveCycleDates()"
              style="padding:7px 14px;font-size:12.5px;border-radius:9px;box-shadow:0 4px 12px rgba(22, 163, 74, .2);">Save
              Window</button>
          </div>

          <div class="form-row">
            <!-- Start Group -->
            <div>
              <label
                style="color:var(--n-600);font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                <?= svgIcon('calendar') ?> Opening Date & Time
              </label>
              <div class="dt-split">
                <input type="date" id="ev_start_d" class="dt-premium" style="flex:1.4;">
                <input type="time" id="ev_start_t" class="dt-premium" style="flex:1;">
              </div>
            </div>

            <!-- End Group -->
            <div>
              <label
                style="color:var(--n-600);font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                <?= svgIcon('calendar') ?> Closing Date & Time
              </label>
              <div class="dt-split">
                <input type="date" id="ev_end_d" class="dt-premium" style="flex:1.4;">
                <input type="time" id="ev_end_t" class="dt-premium" style="flex:1;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk CSV Import for Stakeholders -->
      <div id="evCsvSection" style="margin-bottom:16px;">
        <div class="import-card" id="evCsvDrop">
          <!-- Header -->
          <div class="import-head">
            <div style="display:flex;align-items:center;gap:12px;">
              <div class="import-icon-wrap">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                  <path d="M12 18v-6" />
                  <path d="M9 15h6" />
                </svg>
              </div>
              <div>
                <div style="font-size:14px;font-weight:800;color:var(--n-900);line-height:1.2;">Bulk Import
                </div>
                <div style="font-size:11px;color:var(--n-500);margin-top:2px;">Upload CSV to add evaluators</div>
              </div>
            </div>
            <div class="import-schema">
              <span class="import-col">full_name</span>
              <span class="import-col">email</span>
            </div>
          </div>

          <!-- Body -->
          <div class="import-body">
            <label for="evCsvFile" class="import-drop-zone" id="evCsvDropZone">
              <input type="file" id="evCsvFile" accept=".csv" style="display:none;" onchange="handleEvCsvSelect(this)">

              <!-- Placeholder state -->
              <div class="import-placeholder-state">
                <div
                  style="width:44px;height:44px;border-radius:12px;background:var(--brand-50);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;border:1px solid var(--brand-100);">
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--brand-600)" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                </div>
                <div style="font-size:14px;font-weight:700;color:var(--n-800);margin-bottom:4px;">Drop your CSV here
                </div>
                <div style="font-size:12px;color:var(--n-400);">
                  or <span style="color:var(--brand-600);font-weight:600;text-decoration:underline;">click to
                    browse</span>
                </div>
              </div>

              <!-- Selected file pill -->
              <div class="import-file-pill" id="evCsvFilePill">
                <div
                  style="width:32px;height:32px;border-radius:8px;background:var(--brand-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--brand-700)"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                  </svg>
                </div>
                <div style="flex:1;min-width:0;text-align:left;">
                  <div id="evCsvFileName"
                    style="font-size:13px;font-weight:700;color:var(--n-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  </div>
                  <div id="evCsvFileSize" style="font-size:11px;color:var(--n-500);"></div>
                </div>
                <button type="button" onclick="event.preventDefault(); clearEvCsv();"
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

            <button class="import-btn" id="evCsvImportBtn" onclick="importStakeholderCsv()" disabled>
              <?= svgIcon('send') ?> Import &amp; Send Invites
            </button>

            <div style="text-align:center;">
              <a href="users.php?action=download_template" class="import-download-link">
                <?= svgIcon('download') ?> Download CSV Template
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Add evaluator form (Collapsible) -->
      <div class="manual-entry-card" id="manualEntryCard">
        <div class="manual-entry-header" onclick="toggleManualEntry()">
          <div style="display:flex;align-items:center;gap:12px;">
            <div
              style="width:32px;height:32px;border-radius:8px;background:var(--n-100);display:flex;align-items:center;justify-content:center;color:var(--n-600);"
              id="manualIconWrap">
              <?= svgIcon('plus') ?>
            </div>
            <div>
              <div style="font-size:14px;font-weight:800;color:var(--n-800);line-height:1;">Manual Entry</div>
              <div style="font-size:11px;color:var(--n-400);margin-top:2px;">Add a single evaluator manually</div>
            </div>
          </div>
          <div class="chevron-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </div>
        </div>

        <div class="manual-entry-content">
          <div style="padding-top:10px;">
            <div class="form-row">
              <div class="fg" style="margin-bottom:0;">
                <label>Full Name *</label>
                <input class="fc" id="ev_name" placeholder="e.g. Juan dela Cruz">
              </div>
              <div class="fg" style="margin-bottom:0;">
                <label>Email Address *</label>
                <input class="fc" type="email" id="ev_email" placeholder="evaluator@email.com">
              </div>
            </div>
            <div style="margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
              <button class="btn btn-primary btn-sm" onclick="addEvaluator()" style="padding:8px 16px;">
                <?= svgIcon('user-plus') ?> Add & Send Invite
              </button>
              <div style="display:flex;align-items:center;gap:6px;color:var(--n-400);font-size:11px;">
                <?= svgIcon('info') ?>
                <span>System will email setup instructions.</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Evaluator list -->
      <div id="evaluatorListWrap">
        <div
          style="text-align:center;padding:40px 20px;color:var(--n-400);font-size:13px;background:var(--n-50);border:2px dashed var(--n-200);border-radius:14px;display:flex;flex-direction:column;align-items:center;gap:12px;">
          <div
            style="width:48px;height:48px;border-radius:50%;background:var(--n-100);display:flex;align-items:center;justify-content:center;color:var(--n-300);">
            <?= svgIcon('users', '', 'width:24px;height:24px;') ?>
          </div>
          <div>
            <div style="font-weight:700;color:var(--n-500);">No Evaluators Loaded</div>
            <div style="font-size:12px;margin-top:2px;">Select an assessment cycle to view assigned stakeholders.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot" style="justify-content:space-between;">
      <div style="display:flex;gap:8px;">
        <button class="btn btn-danger btn-sm" onclick="deactivateAllEvaluators()" id="deactivateAllBtn"
          style="display:none;">
          <?= svgIcon('x') ?> Deactivate All
        </button>
        <button class="btn btn-primary btn-sm" onclick="openReactivationModal()" id="reactivateAllBtn"
          style="display:none;background:#2563EB;">
          <?= svgIcon('refresh') ?> Reactivate All
        </button>
      </div>
      <button class="btn btn-secondary" onclick="closeModal('mEvaluators')">Close</button>
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
          <?php foreach (['full_name', 'username', 'email', 'role'] as $col): ?>
            <span
              style="font-size:12px;font-family:monospace;background:#fff;border:1px solid var(--n-200);border-radius:4px;padding:3px 9px;color:var(--n-700);"><?= $col ?></span>
          <?php endforeach; ?>
          <span
            style="font-size:12px;font-family:monospace;background:#fff;border:1px solid var(--n-100);border-radius:4px;padding:3px 9px;color:var(--n-400);font-style:italic;">password
            (optional)</span>
        </div>
        <div style="margin-top:10px;font-size:11px;color:var(--n-400);">Valid roles: school_head ·
          sbm_coordinator ·
          teacher · external_stakeholder</div>
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
              <span class="import-col">full_name</span>
              <span class="import-col">username</span>
              <span class="import-col">email</span>
              <span class="import-col">role</span>
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
  </div>
</div>

<!-- Reactivation Modal -->
<div class="overlay" id="mReactivate">
  <div class="modal" style="max-width:480px;">
    <div class="modal-head">
      <span class="modal-title">Reactivate Evaluators</span>
      <button class="modal-close" onclick="closeModal('mReactivate')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom:16px;font-size:14px;color:var(--n-600);line-height:1.6;">
        This will reactivate the selected evaluator accounts. They will be able to log in again immediately.
      </div>

      <div id="deactivatedEvalsList"
        style="max-height:200px;overflow-y:auto;border:1px solid var(--n-200);border-radius:10px;padding:4px;margin-bottom:18px;background:var(--n-50);">
        <!-- List with checkboxes -->
      </div>

      <div class="fg">
        <label style="display:flex;align-items:center;gap:6px;color:#1E40AF;font-weight:600;">
          <?= svgIcon('calendar', '', 'width:15px;height:15px;') ?> Optional: Extend Access End Date
        </label>
        <div class="dt-split" style="margin-top:8px;">
          <input type="date" id="reactivate_end_d" class="dt-premium">
          <input type="time" id="reactivate_end_t" class="dt-premium">
        </div>
        <div style="margin-top:8px;font-size:11.5px;color:var(--n-400);">
          Leave blank to keep existing end date.
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mReactivate')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmReactivate()" style="background:#2563EB;">Confirm
        Reactivation</button>
    </div>
  </div>
</div>

<script>
  const userStatusStyles = {
    active: { label: 'Active', background: '#DCFCE7', color: '#16A34A' },
    inactive: { label: 'Inactive', background: 'var(--n-100)', color: 'var(--n-500)' },
    suspended: { label: 'Suspended', background: 'var(--red-bg)', color: 'var(--red)' }
  };

  function setUserRowStatus(row, status) {
    if (!row) return;
    row.dataset.userStatus = status;

    const pill = row.querySelector('.user-status-pill');
    const style = userStatusStyles[status] || userStatusStyles.inactive;
    if (pill) {
      pill.textContent = style.label;
      pill.style.background = style.background;
      pill.style.color = style.color;
    }

    const toggleBtn = row.querySelector('.user-status-toggle');
    if (toggleBtn) {
      toggleBtn.dataset.currentStatus = status;
      if (status === 'active') {
        toggleBtn.textContent = 'Deactivate account';
        toggleBtn.classList.remove('is-success');
        toggleBtn.classList.add('is-danger');
      } else {
        toggleBtn.textContent = 'Reactivate account';
        toggleBtn.classList.remove('is-danger');
        toggleBtn.classList.add('is-success');
      }
    }
  }

  function closeRowMenus() {
    document.querySelectorAll('.row-menu.open').forEach(menu => {
      menu.classList.remove('open');
      const btn = menu.querySelector('.row-menu-btn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  function toggleRowMenu(btn) {
    const menu = btn.closest('.row-menu');
    const shouldOpen = !menu.classList.contains('open');
    closeRowMenus();
    if (!shouldOpen) return;
    menu.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
  }

  async function createUser() {
    const d = { action: 'create', full_name: $('c_name'), username: $('c_user'), email: $('c_email'), role: $('c_role'), status: $('c_status'), school_id: $('c_school'), password: $('c_pass') };
    const r = await apiPost('users.php', d);
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mCreate');['c_name', 'c_user', 'c_email', 'c_pass'].forEach(id => $v(id, '')); setTimeout(() => location.reload(), 800); }
  }
  async function editUser(id) {
    const r = await apiPost('users.php', { action: 'get', id });
    if (!r || !r.user_id) { toast('Failed to load user.', 'err'); return; }
    $v('e_id', r.user_id); $v('e_name', r.full_name); $v('e_email', r.email);

    // Init custom dropdowns
    const roleMap = { 'system_admin': 'System Admin', 'school_head': 'School Head', 'sbm_coordinator': 'SBM Coordinator', 'teacher': 'Teacher', 'external_stakeholder': 'External Stakeholder' };
    setERole(r.role || 'teacher', roleMap[r.role] || 'Teacher');

    const statusMap = { 'active': 'Active', 'inactive': 'Inactive', 'suspended': 'Suspended' };
    setEStatus(r.status || 'active', statusMap[r.status] || 'Active');

    $v('e_school', r.school_id || ''); $v('e_pass', '');
    openModal('mEdit');
  }
  async function updateUser() {
    const r = await apiPost('users.php', { action: 'update', id: $('e_id'), full_name: $('e_name'), email: $('e_email'), role: $('e_role'), status: $('e_status'), school_id: $('e_school'), password: $('e_pass') });
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
    const originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = nextStatus === 'active' ? 'Reactivating...' : 'Deactivating...';

    const r = await apiPost('users.php', { action: 'toggle_status', id: btn.dataset.userId, status: nextStatus });
    btn.disabled = false;
    btn.textContent = originalLabel;
    closeRowMenus();
    toast(r.msg, r.ok ? 'ok' : 'err');

    if (!r.ok) return;

    setUserRowStatus(row, r.status || nextStatus);
    if (menu) {
      const menuBtn = menu.querySelector('.row-menu-btn');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
    }
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

  async function addEvaluator() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const name = document.getElementById('ev_name').value.trim();
    const email = document.getElementById('ev_email').value.trim();
    if (!cycleId) { toast('Please select a cycle first.', 'warning'); return; }
    if (!name || !email) { toast('Name and email are required.', 'warning'); return; }
    const r = await apiPost('users.php', {
      action: 'create_temp_evaluator',
      cycle_id: cycleId,
      full_name: name,
      email: email,
    });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      document.getElementById('ev_name').value = '';
      document.getElementById('ev_email').value = '';
      loadEvaluators();
    }
  }

  let lastEvalsList = [];
  async function loadEvaluators() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const wrap = document.getElementById('evaluatorListWrap');
    const deactBtn = document.getElementById('deactivateAllBtn');
    const reactBtn = document.getElementById('reactivateAllBtn');

    if (!cycleId) {
      wrap.innerHTML = '<div style="text-align:center;padding:20px;color:var(--n-400);font-size:13px;">Select a cycle above to see evaluators.</div>';
      deactBtn.style.display = 'none';
      reactBtn.style.display = 'none';
      document.getElementById('cycleDatesCard').style.display = 'none';
      return;
    }

    refreshCycleDates(cycleId);
    wrap.innerHTML = '<div style="text-align:center;padding:20px;color:var(--n-400);">Loading…</div>';

    const r = await apiPost('users.php', { action: 'list_cycle_evaluators', cycle_id: cycleId });
    if (!r.ok || !r.data) { wrap.innerHTML = '<div style="color:var(--red);padding:12px;">Failed to load.</div>'; return; }

    lastEvalsList = r.data;

    if (r.data.length === 0) {
      wrap.innerHTML = '<div style="text-align:center;padding:20px;color:var(--n-400);font-size:13px;">No evaluators added to this cycle yet.</div>';
      deactBtn.style.display = 'none';
      reactBtn.style.display = 'none';
      return;
    }

    let hasActive = false;
    let hasDeactivated = false;
    let html = `<div style="font-size:13px;font-weight:700;color:var(--n-800);margin-bottom:10px;">
      ${r.data.length} evaluator(s) for this cycle
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">`;

    r.data.forEach(ev => {
      const submitted = ev.submission_status === 'submitted';
      const isAutoDeactivated = ev.is_active == 0;
      if (!isAutoDeactivated) hasActive = true;
      if (isAutoDeactivated) hasDeactivated = true;

      const statusBadge = isAutoDeactivated
        ? `<span style="background:#FEE2E2;color:#991B1B;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;">Deactivated</span>`
        : `<span style="background:#DCFCE7;color:#16A34A;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;">Active</span>`;

      const subBadge = submitted
        ? `<span style="background:#DCFCE7;color:#166534;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;">Submitted</span>`
        : `<span style="background:var(--n-100);color:var(--n-500);padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;">Pending</span>`;

      html += `<div style="display:flex;align-items:center;gap:10px;padding:10px 12px;
                   border:1px solid var(--n-200);border-radius:9px;background:#fff;${isAutoDeactivated ? 'opacity:0.75;' : ''}">
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:600;color:var(--n-900);">${ev.full_name}</div>
          <div style="font-size:11.5px;color:var(--n-500);">${ev.email}</div>
        </div>
        <div style="display:flex;gap:4px;">${statusBadge}${subBadge}</div>
        <button class="btn btn-danger btn-sm" onclick="removeEvaluator(${ev.user_id})" title="Remove from cycle" style="padding:4px 8px;">
          ${svgIcon('trash')}
        </button>
      </div>`;
    });
    html += '</div>';
    wrap.innerHTML = html;

    deactBtn.style.display = hasActive ? '' : 'none';
    reactBtn.style.display = hasDeactivated ? '' : 'none';
  }

  async function refreshCycleDates(cycleId) {
    const card = document.getElementById('cycleDatesCard');
    const banner = document.getElementById('cycleStatusBanner');

    card.style.display = 'block';
    const r = await apiPost('users.php', { action: 'get_cycle_dates', cycle_id: cycleId });
    if (r.ok && r.dates) {
      const s = r.dates.stakeholder_access_start || '';
      const e = r.dates.stakeholder_access_end || '';

      document.getElementById('ev_start_d').value = s ? s.substring(0, 10) : '';
      document.getElementById('ev_start_t').value = s ? s.substring(11, 16) : '';
      document.getElementById('ev_end_d').value = e ? e.substring(0, 10) : '';
      document.getElementById('ev_end_t').value = e ? e.substring(11, 16) : '';

      const now = new Date();
      const end = e ? new Date(e.replace(' ', 'T')) : null;
      const start = s ? new Date(s.replace(' ', 'T')) : null;

      if (!end) {
        banner.innerHTML = '<span style="color:#991B1B;">No end date set</span>';
      } else if (now > end) {
        banner.innerHTML = '<span style="color:#991B1B;">Window Closed</span>';
      } else if (start && now < start) {
        banner.innerHTML = '<span style="color:#92400E;">Not Started Yet</span>';
      } else {
        banner.innerHTML = '<span style="color:#166534;">Window Open</span>';
      }
    }
  }

  async function saveCycleDates() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const sd = document.getElementById('ev_start_d').value;
    const st = document.getElementById('ev_start_t').value;
    const ed = document.getElementById('ev_end_d').value;
    const et = document.getElementById('ev_end_t').value;

    if (!ed || !et) { toast('Access end date and time are required.', 'warning'); return; }

    const start = sd && st ? (sd + ' ' + st + ':00') : '';
    const end = ed + ' ' + et + ':00';

    const r = await apiPost('users.php', { action: 'set_cycle_dates', cycle_id: cycleId, start_date: start, end_date: end });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) refreshCycleDates(cycleId);
  }

  function openReactivationModal() {
    const list = document.getElementById('deactivatedEvalsList');
    const deactivated = lastEvalsList.filter(u => u.is_active == 0);
    if (deactivated.length === 0) return;

    list.innerHTML = deactivated.map(u => `
      <div style="display:flex;align-items:center;padding:8px;border-bottom:1px solid var(--n-100);gap:10px;">
        <input type="checkbox" name="reactivate_uid" value="${u.user_id}" checked style="width:16px;height:16px;">
        <div style="flex:1;">
          <div style="font-weight:600;font-size:13px;">${u.full_name}</div>
          <div style="font-size:11px;color:var(--n-400);">${u.email}</div>
        </div>
      </div>
    `).join('');

    // Set default extension end date from current inputs
    document.getElementById('reactivate_end_d').value = document.getElementById('ev_end_d').value;
    document.getElementById('reactivate_end_t').value = document.getElementById('ev_end_t').value;
    openModal('mReactivate');
  }

  async function confirmReactivate() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const checkboxes = document.querySelectorAll('input[name="reactivate_uid"]:checked');
    const userIds = Array.from(checkboxes).map(cb => cb.value);

    const rd = document.getElementById('reactivate_end_d').value;
    const rt = document.getElementById('reactivate_end_t').value;
    const newEnd = rd && rt ? (rd + ' ' + rt + ':00') : '';

    if (userIds.length === 0) { toast('Please select at least one account.', 'warning'); return; }

    const fd = new FormData();
    fd.append('action', 'reactivate_evaluators');
    fd.append('cycle_id', cycleId);
    fd.append('csrf_token', '<?= csrfToken() ?>');
    userIds.forEach(id => fd.append('user_ids[]', id));
    fd.append('new_end_date', newEnd);

    const r = await fetch('users.php', { method: 'POST', body: fd }).then(res => res.json());
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      closeModal('mReactivate');
      loadEvaluators();
    }
  }

  async function deactivateAllEvaluators() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!cycleId) return;
    if (!confirm('Deactivate ALL evaluator accounts for this cycle?\n\nTheir accounts will become inactive and they will no longer be able to log in.')) return;
    const r = await apiPost('users.php', { action: 'deactivate_cycle_evaluators', cycle_id: cycleId });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) loadEvaluators();
  }

  async function removeEvaluator(userId) {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!confirm('Remove this evaluator from the cycle?')) return;
    const r = await apiPost('users.php', { action: 'remove_cycle_evaluator', cycle_id: cycleId, user_id: userId });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) loadEvaluators();
  }


  function handleEvCsvSelect(input) {
    const file = input.files[0];
    const drop = document.getElementById('evCsvDrop');
    const btn = document.getElementById('evCsvImportBtn');
    if (!file) { clearEvCsv(); return; }
    drop.classList.add('has-file');
    document.getElementById('evCsvFileName').textContent = file.name;
    document.getElementById('evCsvFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    btn.disabled = false;
  }

  function clearEvCsv() {
    const input = document.getElementById('evCsvFile');
    input.value = '';
    document.getElementById('evCsvDrop').classList.remove('has-file');
    document.getElementById('evCsvFileName').textContent = '';
    document.getElementById('evCsvFileSize').textContent = '';
    document.getElementById('evCsvImportBtn').disabled = true;
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
    // Evaluators Zone
    const evZone = document.getElementById('evCsvDropZone');
    const evCard = document.getElementById('evCsvDrop');
    if (evZone) {
      evZone.addEventListener('dragover', e => { e.preventDefault(); evCard.classList.add('drag-over'); });
      evZone.addEventListener('dragleave', () => evCard.classList.remove('drag-over'));
      evZone.addEventListener('drop', e => {
        e.preventDefault(); evCard.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
          const input = document.getElementById('evCsvFile');
          const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
          handleEvCsvSelect(input);
        } else { toast('Please drop a .csv file.', 'warning'); }
      });
    }

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

  function setPStatus(val) {
    document.getElementById('pStatusValue').value = val;
    document.getElementById('pStatusDropdown').closest('form').submit();
  }

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

  // mEdit helpers
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

  function setMCycle(id, label) {
    document.getElementById('ev_cycle_id').value = id;
    document.getElementById('pCycleLabel').textContent = label;
    // Update active state in UI
    document.querySelectorAll('#pCycleDropdown .p-select-item').forEach(item => {
      item.classList.toggle('active', item.getAttribute('onclick').includes(`'${id}'`));
    });
    closeAllPSelects();
    loadEvaluators();
  }

  function toggleManualEntry() {
    const card = document.getElementById('manualEntryCard');
    const iconWrap = document.getElementById('manualIconWrap');
    const isExpanded = card.classList.toggle('is-expanded');

    if (isExpanded) {
      iconWrap.style.background = 'var(--brand-100)';
      iconWrap.style.color = 'var(--brand-600)';
    } else {
      iconWrap.style.background = 'var(--n-100)';
      iconWrap.style.color = 'var(--n-600)';
    }
  }

  async function importStakeholderCsv() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!cycleId) { toast('Please select a cycle first.', 'warning'); return; }
    const file = document.getElementById('evCsvFile').files[0];
    if (!file) { toast('Please choose a CSV file first.', 'warning'); return; }

    const btn = document.getElementById('evCsvImportBtn');
    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin .7s linear infinite;"><path d="M12 2a10 10 0 1 0 10 10" /></svg> Importing…`;

    const text = await file.text();
    const lines = text.trim().split('\n').filter(l => l.trim());
    if (lines.length < 2) {
      toast('CSV is empty or has no data rows.', 'err');
      btn.disabled = false;
      btn.innerHTML = `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg> Import &amp; Send Invites`;
      return;
    }

    const rows = lines.slice(1);
    let success = 0, failed = 0, errors = [];

    for (const line of rows) {
      const cols = line.split(',').map(c => c.trim().replace(/^"|"$/g, ''));
      const [full_name, email] = cols;
      if (!full_name || !email) { failed++; errors.push('Skipped empty row'); continue; }
      if (!email.includes('@')) { failed++; errors.push(`Invalid email: ${email}`); continue; }
      const r = await apiPost('users.php', { action: 'create_temp_evaluator', cycle_id: cycleId, full_name, email });
      if (r.ok) { success++; } else { failed++; errors.push(`${email}: ${r.msg}`); }
    }

    if (errors.length) console.warn('Import errors:', errors);
    toast(`Import done — ${success} added${failed ? ', ' + failed + ' failed' : ''}.`, success > 0 ? 'ok' : 'err');
    clearEvCsv();
    if (success > 0) loadEvaluators();

    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg> Import &amp; Send Invites`;
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