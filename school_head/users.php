<?php
ob_start();
// ============================================================
// school_head/users.php — User Management
// Moved from admin/users.php — school_head is now the top role
// Roles: school_head | sbm_coordinator | teacher | external_stakeholder
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head');
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
    if (!in_array($role, ['school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'])) {
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
    if (!in_array($newRole, ['school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'])) {
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
    if ($user['role'] === 'school_head' && $targetStatus !== 'active') {
      $activeHeads = $db->query("SELECT COUNT(*) FROM users WHERE role='school_head' AND status='active'")->fetchColumn();
      if ((int) $activeHeads <= 1) {
        echo json_encode(['ok' => false, 'msg' => 'At least one active School Head account must remain.']);
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
    $validRoles = ['school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'];
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

$q = $_GET['q'] ?? '';
$rf = $_GET['role'] ?? '';
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
  'school_head' => '#166534', // Deeper green
  'sbm_coordinator' => '#7C3AED', // Purple
  'teacher' => '#0D9488', // Teal
  'external_stakeholder' => '#2563EB', // Blue
];
$roleLabels = [
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
    <button class="btn btn-primary" onclick="openModal('mCreate')"><?= svgIcon('plus') ?> Add User</button>
  </div>
</div>

<!-- Role tabs -->
<div class="status-tabs">
  <a href="users.php<?= $q ? "?q=" . urlencode($q) : '' ?>" class="status-tab <?= !$rf ? 'active' : '' ?>">
    All <span class="status-tab-count"><?= $totalUsers ?></span>
  </a>
  <?php foreach (['school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder'] as $r):
    $cnt = $roleCounts[$r] ?? 0;
    if (!$cnt)
      continue; ?>
    <a href="users.php?role=<?= $r ?><?= $q ? "&q=" . urlencode($q) : '' ?>"
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
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($q || $rf): ?><a href="users.php" class="btn btn-secondary btn-sm">Reset</a><?php endif; ?>
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
        <div class="fg"><label>Role *</label>
          <select class="fc" id="c_role">
            <option value="school_head">School Head</option>
            <option value="sbm_coordinator">SBM Coordinator</option>
            <option value="teacher">Teacher</option>
            <option value="external_stakeholder">External Stakeholder</option>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="c_status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
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
        <div class="fg"><label>Role</label>
          <select class="fc" id="e_role">
            <option value="school_head">School Head</option>
            <option value="sbm_coordinator">SBM Coordinator</option>
            <option value="teacher">Teacher</option>
            <option value="external_stakeholder">External Stakeholder</option>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="e_status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
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
        <div style="margin-top:10px;font-size:11px;color:var(--n-400);">Valid roles: school_head · sbm_coordinator ·
          teacher · external_stakeholder</div>
      </div>
      <div class="fg">
        <label>CSV File</label>
        <label for="csvFile" id="csvFileLabel"
          style="display:flex;align-items:center;gap:10px;padding:9px 14px;border:1.5px dashed var(--n-300);border-radius:8px;background:var(--n-50);cursor:pointer;transition:all .15s;">
          <span
            style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:#fff;border:1px solid var(--n-200);border-radius:6px;font-size:12.5px;font-weight:600;color:var(--n-700);white-space:nowrap;box-shadow:var(--shadow-xs);">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
              <polyline points="17 8 12 3 7 8" />
              <line x1="12" y1="3" x2="12" y2="15" />
            </svg>
            Choose File
          </span>
          <span id="csvFileName"
            style="font-size:13px;color:var(--n-400);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">No file
            chosen</span>
        </label>
        <input type="file" id="csvFile" accept=".csv" style="display:none;"
          onchange="document.getElementById('csvFileName').textContent = this.files[0]?.name || 'No file chosen'; document.getElementById('csvFileName').style.color = this.files[0] ? 'var(--n-800)' : 'var(--n-400)';">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mImport')">Cancel</button>
      <button class="btn btn-primary" onclick="importUsers()"><?= svgIcon('upload') ?> Upload &amp; Import</button>
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
    $el('e_role').value = r.role || 'teacher'; $el('e_status').value = r.status || 'active';
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
  async function importUsers() {
    const file = document.getElementById('csvFile').files[0];
    if (!file) { toast('Please select a CSV file.', 'err'); return; }
    const formData = new FormData();
    formData.append('action', 'import'); formData.append('csv', file);
    formData.append('csrf_token', '<?= csrfToken() ?>');
    toast('Importing...', 'info');
    try {
      const response = await fetch('users.php', { method: 'POST', body: formData });
      const r = await response.json();
      toast(r.msg, r.ok ? 'ok' : 'err');
      if (r.ok) { closeModal('mImport'); setTimeout(() => location.reload(), 1500); }
    } catch (e) { toast('Upload failed.', 'err'); }
  }
  window.addEventListener('DOMContentLoaded', () => {
    if (new URLSearchParams(window.location.search).get('action') === 'create') openModal('mCreate');
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.row-menu')) closeRowMenus();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeRowMenus();
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>