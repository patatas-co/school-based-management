<?php
// ============================================================
// admin/users.php — REDESIGNED v3
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    $action = $_POST['action'];
    if ($action === 'create') {
        $pw = $_POST['password'] ?? '';
        if ($pw && strlen($pw) < 8) { echo json_encode(['ok' => false, 'msg' => 'Password must be at least 8 characters.']); exit; }
        $role = $_POST['role'] ?? '';
        if (!in_array($role, ['admin', 'school_head', 'teacher', 'sdo', 'ro', 'external_stakeholder'])) { echo json_encode(['ok' => false, 'msg' => 'Invalid role.']); exit; }

        try {
            $hashedPw = $pw ? password_hash($pw, PASSWORD_DEFAULT) : null;
            $initialStatus = $pw ? ($_POST['status'] ?? 'active') : 'inactive';
            $schoolId = $_POST['school_id'] ?: SCHOOL_ID;

            $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id) VALUES (?,?,?,?,?,?,?)")
               ->execute([trim($_POST['username']), $hashedPw, trim($_POST['email']), trim($_POST['full_name']), $role, $initialStatus, $schoolId]);

            $newId = $db->lastInsertId();
            logActivity('create_user', 'users', 'Created: ' . trim($_POST['username']));

            $schoolStmt = $db->prepare("SELECT school_name FROM schools WHERE school_id=?");
            $schoolStmt->execute([$schoolId]);
            $schoolName = $schoolId ? ($schoolStmt->fetchColumn() ?: '—') : '—';

            // Only send setup-link email when no password was provided
            if (!$pw) {
                require_once __DIR__ . '/../includes/email_service.php';
                $newUser = [
                    'user_id'   => $newId,
                    'full_name' => trim($_POST['full_name']),
                    'email'     => trim($_POST['email']),
                ];
                $emailSent = sendAccountCreationEmail($db, $newUser);
                $emailMsg = $emailSent
                    ? 'User created. A password setup link was sent via email.'
                    : 'User created, but the welcome email could not be sent. Use Resend Email to retry.';
            } else {
                // Password was set manually — no email token needed
                $emailSent = false;
                $emailMsg  = 'User created with the provided password.';
            }

            echo json_encode([
                'ok'        => true,
                'msg'       => $emailMsg,
                'emailSent' => $emailSent,
                'user'      => [
                    'id'        => $newId,
                    'full_name' => trim($_POST['full_name']),
                    'username'  => trim($_POST['username']),
                    'email'     => trim($_POST['email']),
                    'role'      => $_POST['role'],
                    'status'    => $initialStatus,
                    'school'    => $schoolName
                ]
            ]);
            exit;
        } catch (Exception $e) {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) mkdir($logDir, 0777, true);
            $logMsg = date('[Y-m-d H:i:s] ') . "Action: create_user | Error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString() . "\n";
            file_put_contents($logDir . '/user_creation_error.log', $logMsg, FILE_APPEND);
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
    if($action==='get'){$st=$db->prepare("SELECT user_id,username,email,full_name,role,status,school_id FROM users WHERE user_id=?");$st->execute([(int)$_POST['id']]);echo json_encode($st->fetch());exit;}
    if($action==='update'){
        $id=(int)$_POST['id'];$pw=$_POST['password']??'';
        if($pw){$db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=?,password=? WHERE user_id=?")->execute([trim($_POST['full_name']),trim($_POST['email']),$_POST['role'],$_POST['status'],$_POST['school_id']?:null,password_hash($pw,PASSWORD_DEFAULT),$id]);}
        else{$db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=? WHERE user_id=?")->execute([trim($_POST['full_name']),trim($_POST['email']),$_POST['role'],$_POST['status'],$_POST['school_id']?:null,$id]);}
        logActivity('update_user','users','Updated user ID:'.$id);
        echo json_encode(['ok'=>true,'msg'=>'User updated.']);exit;
    }
    if($action==='delete'){
        $id=(int)$_POST['id'];
        if($id===(int)$_SESSION['user_id']){echo json_encode(['ok'=>false,'msg'=>'Cannot delete your own account.']);exit;}
        $db->prepare("DELETE FROM users WHERE user_id=?")->execute([$id]);
        echo json_encode(['ok'=>true,'msg'=>'User deleted.']);exit;
    }
    if($action==='resend_email'){
        $id=(int)$_POST['id'];
        $u=$db->prepare("SELECT user_id,full_name,email,status FROM users WHERE user_id=?");
        $u->execute([$id]); $u=$u->fetch();
        if(!$u){echo json_encode(['ok'=>false,'msg'=>'User not found.']);exit;}
        if($u['status']==='active'){
            echo json_encode(['ok'=>false,'msg'=>'Account already activated — password is already set.']);exit;
        }
        require_once __DIR__.'/../includes/email_service.php';
        $sent = sendAccountCreationEmail($db, $u);
        if($sent){
            try {
                $db->prepare("UPDATE users SET email_resent_count=email_resent_count+1 WHERE user_id=?")
                   ->execute([$id]);
            } catch (\Exception $e) {
                // Column may not exist — safe to ignore
            }
        }
        echo json_encode(['ok'=>$sent,'msg'=>$sent?'Welcome email resent.':'Failed to resend email. Check mail config.']);exit;
    }
    if ($action === 'import') {
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'No file uploaded or upload error.']); exit;
        }
        $file = $_FILES['csv']['tmp_name'];
        if (!is_uploaded_file($file)) { echo json_encode(['ok' => false, 'msg' => 'Invalid file.']); exit; }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle); // Assume first row is headers: full_name, username, email, role, [password]
        
        $success = 0; $failed = 0; $errors = []; $usersCreated = 0;
        $validRoles = ['admin', 'school_head', 'teacher', 'sdo', 'ro', 'external_stakeholder'];

        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) < 4) { $failed++; continue; }
            [$fullName, $username, $email, $role] = array_map('trim', array_slice($row, 0, 4));
            $password = isset($row[4]) ? trim($row[4]) : null;

            if (!in_array($role, $validRoles)) { $failed++; $errors[] = "Invalid role for $username"; continue; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $failed++; $errors[] = "Invalid email for $username"; continue; }

            try {
                $hashedPw = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
                $status = $password ? 'active' : 'inactive';
                
                $st = $db->prepare("INSERT INTO users (username, password, email, full_name, role, status, school_id) VALUES (?,?,?,?,?,?,?)");
                $st->execute([$username, $hashedPw, $email, $fullName, $role, $status, SCHOOL_ID]);
                
                $newId = $db->lastInsertId();
                $success++;

                if (!$password) {
                    require_once __DIR__ . '/../includes/email_service.php';
                    sendAccountCreationEmail($db, ['user_id' => $newId, 'full_name' => $fullName, 'email' => $email]);
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Error creating $username: " . $e->getMessage();
            }
        }
        fclose($handle);
        logActivity('import_users', 'users', "Imported $success users, $failed failed.");
        echo json_encode(['ok' => true, 'msg' => "Import complete. $success success, $failed failed.", 'errors' => $errors]);
        exit;
    }
    exit;
}

$q=trim($_GET['q']??'');$rf=$_GET['role']??'';
$sql="SELECT u.*,s.school_name FROM users u LEFT JOIN schools s ON u.school_id=s.school_id WHERE 1=1";
$p=[];
if($q){$qEsc='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$q).'%';$sql.=" AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";$p=array_merge($p,[$qEsc,$qEsc,$qEsc]);}
if($rf){$sql.=" AND u.role=?";$p[]=$rf;}
$sql.=" ORDER BY u.created_at DESC";
$stmt=$db->prepare($sql);$stmt->execute($p);$users=$stmt->fetchAll();

// Role counts
$roleCounts=$db->query("SELECT role,COUNT(*) cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalUsers=array_sum($roleCounts);
$activeUsers=$db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();

$schools=$db->query("SELECT school_id,school_name FROM schools ORDER BY school_name")->fetchAll();
$pageTitle='User Management';$activePage='users.php';
include __DIR__.'/../includes/header.php';

$roleColors=['admin'=>'#7C3AED','school_head'=>'#2563EB','teacher'=>'#0D9488','sdo'=>'#D97706','ro'=>'#DC2626','external_stakeholder'=>'#16A34A'];
$roleIcons=['admin'=>'shield','school_head'=>'home','teacher'=>'book-open','sdo'=>'briefcase','ro'=>'map-pin','external_stakeholder'=>'users'];
?>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Management</div>
    <div class="ph2-title">User Management</div>
    <div class="ph2-sub">Manage all portal accounts across roles — <?= $activeUsers ?> active users.</div>
  </div>
  <div class="ph2-right">
    <button class="btn btn-secondary" onclick="openModal('mImport')"><?= svgIcon('upload') ?> Import CSV</button>
    <button class="btn btn-primary" onclick="openModal('mCreate')"><?= svgIcon('plus') ?> Add User</button>
  </div>
</div>

<!-- Role filter tabs -->
<div class="status-tabs">
  <a href="users.php<?= $q?"?q=".urlencode($q):'' ?>" class="status-tab <?= !$rf?'active':'' ?>">
    All <span class="status-tab-count"><?= $totalUsers ?></span>
  </a>
  <?php foreach(['admin','school_head','teacher','sdo','ro','external_stakeholder'] as $r): ?>
  <?php $cnt=$roleCounts[$r]??0;if(!$cnt)continue; ?>
  <a href="users.php?role=<?= $r ?><?= $q?"&q=".urlencode($q):'' ?>" class="status-tab <?= $rf===$r?'active':'' ?>">
    <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= $roleColors[$r] ?>;margin-right:4px;"></span>
    <?= ucfirst(str_replace('_',' ',$r)) ?>
    <span class="status-tab-count"><?= $cnt ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Search + Table -->
<div class="filter-bar-v2">
  <form method="get" class="flex-c" style="gap:10px;flex:1;">
    <div class="search" style="flex:1;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" name="q" placeholder="Search by name, username or email…" value="<?= e($q) ?>">
      <?php if($rf): ?><input type="hidden" name="role" value="<?= e($rf) ?>"><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if($q||$rf): ?><a href="users.php" class="btn btn-secondary btn-sm">Reset</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="card-title">
      <?= $rf ? ucfirst(str_replace('_',' ',$rf)).'s' : 'All Users' ?>
      <span style="font-weight:400;color:var(--n-400);font-family:var(--font-body);font-size:13px;" id="userCountCap">(<?= count($users) ?>)</span>
    </span>
  </div>
  <?php if(!$users): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= svgIcon('users') ?></div>
    <div class="empty-title">No users found</div>
    <div class="empty-sub"><?= $q ? 'No users match "'.e($q).'". Try a different search term.' : 'No users for this role yet.' ?></div>
  </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table id="tblUsers" class="tbl-enhanced">
      <thead><tr><th>User</th><th>Username</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
      <tbody>
      <?php foreach($users as $u):
        $rc=$roleColors[$u['role']]??'#16A34A';
      ?>
      <tr>
        <td>
          <div class="cell-avatar">
            <div class="cell-av" style="background:<?= $rc ?>;"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
            <div class="cell-av-info">
              <div class="cell-av-name"><?= e($u['full_name']) ?></div>
              <div class="cell-av-sub"><?= e($u['email']) ?></div>
            </div>
          </div>
        </td>
        <td style="font-family:monospace;font-size:12px;color:var(--n-500);"><?= e($u['username']) ?></td>
        <td>
          <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $rc ?>18;color:<?= $rc ?>;border:1px solid <?= $rc ?>30;">
            <?= ucfirst(str_replace('_',' ',$u['role'])) ?>
          </span>
        </td>
        <td>
          <?php $statColors=['active'=>['#DCFCE7','#16A34A'],'inactive'=>['var(--n-100)','var(--n-500)'],'suspended'=>['var(--red-bg)','var(--red)']]; [$sb,$sc]=$statColors[$u['status']]??['var(--n-100)','var(--n-500)']; ?>
          <span style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $sb ?>;color:<?= $sc ?>;"><?= ucfirst($u['status']) ?></span>
        </td>
        <td style="font-size:12px;color:<?= $u['last_login']?'var(--n-400)':'var(--red)' ?>;"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
        <td>
          <div class="flex-c" style="gap:4px;">
    <button class="btn btn-secondary btn-sm" onclick="editUser(<?= $u['user_id'] ?>)"><?= svgIcon('edit') ?></button>
    <?php if($u['status'] !== 'active'): ?>
    <button class="btn btn-blue btn-sm" onclick="resendEmail(<?= $u['user_id'] ?>)" title="Resend welcome email"><?= svgIcon('send') ?></button>
    <?php endif; ?>
    <?php if($u['user_id']!=$_SESSION['user_id']): ?>
    <button class="btn btn-danger btn-sm" data-id="<?= $u['user_id'] ?>" data-name="<?= e($u['full_name']) ?>" onclick="delUser(this.dataset.id,this.dataset.name,this)"><?= svgIcon('trash') ?></button>
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

<!-- Create Modal -->
<div class="overlay" id="mCreate">
  <div class="modal">
    <div class="modal-head"><span class="modal-title">Create New User</span><button class="modal-close" onclick="closeModal('mCreate')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="fg"><label>Full Name *</label><input class="fc" id="c_name" placeholder="Juan dela Cruz"></div>
        <div class="fg"><label>Username *</label><input class="fc" id="c_user" placeholder="juandelacruz" autocomplete="off"></div>
      </div>
      <div class="fg"><label>Email *</label><input class="fc" type="email" id="c_email" placeholder="juan@deped.gov.ph"></div>
      <div class="form-row">
        <div class="fg"><label>Role *</label>
          <select class="fc" id="c_role">
            <?php foreach(['admin','school_head','teacher','sdo','ro','external_stakeholder'] as $r): ?>
            <option value="<?= $r ?>"><?= ucfirst(str_replace('_',' ',$r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="c_status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <!-- Single school system: all school users belong to DIHS automatically -->
      <div class="fg">
        <label>Password <span style="font-weight:400;color:var(--n-400);">(leave blank to send a setup link via email)</span></label>
        <input class="fc" type="password" id="c_pass" placeholder="Leave blank — user sets password via email link" autocomplete="new-password">
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
    <div class="modal-head"><span class="modal-title">Edit User</span><button class="modal-close" onclick="closeModal('mEdit')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="e_id">
      <div class="form-row">
        <div class="fg"><label>Full Name</label><input class="fc" id="e_name"></div>
        <div class="fg"><label>Email</label><input class="fc" type="email" id="e_email"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Role</label>
          <select class="fc" id="e_role">
            <?php foreach(['admin','school_head','teacher','sdo','ro','external_stakeholder'] as $r): ?>
            <option value="<?= $r ?>"><?= ucfirst(str_replace('_',' ',$r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="e_status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select>
        </div>
      </div>
      <div class="fg">
        <label>School Assignment</label>
        <div style="padding:9px 12px;background:var(--brand-100);border-radius:8px;
                    font-size:13px;font-weight:600;color:var(--brand-700);
                    border:1.5px solid var(--brand-200);">
          Dasmariñas Integrated High School
        </div>
        <input type="hidden" id="e_school" value="<?= SCHOOL_ID ?>">
      </div>
      <div class="fg">
        <label>New Password <span style="font-weight:400;color:var(--n-400);">(leave blank to keep current)</span></label>
        <input class="fc" type="password" id="e_pass" placeholder="Leave blank to keep current password" autocomplete="new-password">
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
  <div class="modal" style="max-width:520px;width:100%;border-radius:12px;overflow:hidden;">

    <!-- Header -->
    <div class="modal-head" style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--n-100);">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:34px;height:34px;border-radius:8px;background:var(--brand-50);border:1px solid var(--brand-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--brand-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
        </div>
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--n-900);line-height:1.2;">Bulk Import Users</div>
          <div style="font-size:12px;color:var(--n-400);margin-top:2px;">Upload a CSV file to create multiple accounts</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('mImport')" style="width:28px;height:28px;border-radius:6px;border:1px solid var(--n-200);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--n-500)" stroke-width="2.5" stroke-linecap="round">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <div class="modal-body" style="padding:20px 24px;">

      <!-- CSV Format Info -->
      <div style="background:var(--n-50);border-radius:8px;border:1px solid var(--n-100);padding:14px 16px;margin-bottom:18px;">
        <div style="font-size:11px;font-weight:600;color:var(--n-400);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Required CSV format</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;">
          <?php foreach(['full_name','username','email','role'] as $col): ?>
          <span style="font-size:12px;font-family:monospace;background:#fff;border:1px solid var(--n-200);border-radius:4px;padding:3px 9px;color:var(--n-700);"><?= $col ?></span>
          <?php endforeach; ?>
          <span style="font-size:12px;font-family:monospace;background:#fff;border:1px solid var(--n-100);border-radius:4px;padding:3px 9px;color:var(--n-400);font-style:italic;">password <span style="font-size:10px;">(optional)</span></span>
        </div>
        <div style="border-top:1px solid var(--n-100);padding-top:10px;">
          <div style="font-size:11px;color:var(--n-400);font-weight:600;margin-bottom:7px;">Valid roles</div>
          <div style="display:flex;gap:5px;flex-wrap:wrap;">
            <?php foreach(['admin','school_head','teacher','sdo','ro','external_stakeholder'] as $r): ?>
            <span style="font-size:11px;background:#fff;border:1px solid var(--n-200);border-radius:999px;padding:2px 9px;color:var(--n-500);"><?= $r ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Drop Zone -->
      <div id="importDropZone"
           style="border:1.5px dashed var(--n-200);border-radius:8px;padding:30px 20px;text-align:center;cursor:pointer;transition:border-color 0.15s,background 0.15s;position:relative;"
           onclick="document.getElementById('csvFile').click()"
           ondragover="event.preventDefault();this.style.borderColor='var(--brand-500)';this.style.background='var(--brand-50)';"
           ondragleave="this.style.borderColor='var(--n-200)';this.style.background='transparent';"
           ondrop="importHandleDrop(event)">

        <!-- Idle state -->
        <div id="importDropIdle">
          <div style="width:44px;height:44px;border-radius:8px;background:var(--n-50);border:1px solid var(--n-100);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--n-400)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="12" y1="18" x2="12" y2="12"/>
              <line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
          </div>
          <div style="font-size:13px;font-weight:600;color:var(--n-700);margin-bottom:5px;">Drop your CSV file here</div>
          <div style="font-size:12px;color:var(--n-400);">or <span style="color:var(--brand-600);text-decoration:underline;text-underline-offset:2px;">browse to upload</span></div>
          <div style="font-size:11px;color:var(--n-300);margin-top:6px;">.csv files only</div>
        </div>

        <!-- Selected state -->
        <div id="importDropSelected" style="display:none;">
          <div style="width:44px;height:44px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <polyline points="9 12 11 14 15 10"/>
            </svg>
          </div>
          <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:4px;">
            <span id="importFileName" style="font-size:13px;font-weight:600;color:var(--n-700);"></span>
            <button onclick="event.stopPropagation();importClearFile()"
                    style="width:18px;height:18px;border-radius:50%;border:1px solid var(--n-200);background:var(--n-100);cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">
              <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="var(--n-400)" stroke-width="3" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
          <div id="importFileSize" style="font-size:11px;color:var(--n-400);"></div>
        </div>

        <input type="file" id="csvFile" accept=".csv" style="position:absolute;opacity:0;width:0;height:0;" onchange="importHandleFile(this.files[0])">
      </div>

      <!-- Note -->
      <div style="display:flex;align-items:flex-start;gap:7px;margin-top:13px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--n-300)" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:2px;">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span style="font-size:12px;color:var(--n-400);line-height:1.5;">Users without a password will receive a setup link via email. Leave the password column blank or omit it entirely.</span>
      </div>
    </div>

    <div class="modal-foot" style="border-top:1px solid var(--n-100);">
      <button class="btn btn-secondary" onclick="closeModal('mImport')">Cancel</button>
      <button id="importSubmitBtn" class="btn btn-primary" onclick="importUsers()" disabled
              style="opacity:0.45;cursor:not-allowed;display:flex;align-items:center;gap:7px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        Upload &amp; Import
      </button>
    </div>

  </div>
</div>

<script>
const escH=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const roleColors={admin:'#7C3AED',school_head:'#2563EB',teacher:'#0D9488',sdo:'#D97706',ro:'#DC2626',external_stakeholder:'#16A34A'};

async function createUser(){
  const d={action:'create',full_name:$('c_name'),username:$('c_user'),email:$('c_email'),role:$('c_role'),status:$('c_status'),school_id:$('c_school'),password:$('c_pass')};
  const r=await apiPost('users.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){
    closeModal('mCreate');
    ['c_name','c_user','c_email','c_pass'].forEach(id=>$v(id,''));
    const tbody=document.querySelector('#tblUsers tbody');
    if(!tbody)return;
    const initials=escH(r.user.full_name).charAt(0).toUpperCase();
    const rc=roleColors[r.user.role]||'#16A34A';
    const safeRole=r.user.role.replace(/[^a-z0-9_]/g,'');
    const tr=document.createElement('tr');
    const isActive = r.user.status === 'active';
    const statusBg = isActive ? '#DCFCE7' : 'var(--n-100)';
    const statusClr = isActive ? '#16A34A' : 'var(--n-500)';
    const statusLabel = r.user.status.charAt(0).toUpperCase() + r.user.status.slice(1);
    tr.innerHTML=`
      <td><div class="cell-avatar"><div class="cell-av" style="background:${rc};">${initials}</div><div class="cell-av-info"><div class="cell-av-name">${escH(r.user.full_name)}</div><div class="cell-av-sub">${escH(r.user.email)}</div></div></div></td>
      <td style="font-family:monospace;font-size:12px;color:var(--n-500);">${escH(r.user.username)}</td>
      <td><span style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:${rc}18;color:${rc};">${escH(r.user.role.replace(/_/g,' ')).replace(/\b\w/g,c=>c.toUpperCase())}</span></td>
      <td><span style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:${statusBg};color:${statusClr};">${statusLabel}</span></td>
      <td style="font-size:12px;color:var(--red);">Never</td>
      <td><div class="flex-c" style="gap:4px;"><button class="btn btn-secondary btn-sm" onclick="editUser(${r.user.id})">${svgI('edit')}</button>${!isActive?`<button class="btn btn-blue btn-sm" onclick="resendEmail(${r.user.id})" title="Resend welcome email">${svgI('send')}</button>`:''}<button class="btn btn-danger btn-sm" onclick="delUser(${r.user.id},'${escH(r.user.full_name)}',this)">${svgI('trash')}</button></div></td>`;
    tbody.insertBefore(tr,tbody.firstChild);
    const cap=document.getElementById('userCountCap');
    if(cap) cap.textContent='('+tbody.querySelectorAll('tr').length+')';
  }
}
async function editUser(id){
  const r=await apiPost('users.php',{action:'get',id});
  $v('e_id',r.user_id);$v('e_name',r.full_name);$v('e_email',r.email);$v('e_role',r.role);$v('e_status',r.status);$v('e_school',r.school_id);$v('e_pass','');
  openModal('mEdit');
}
async function updateUser(){
  const d={action:'update',id:$('e_id'),full_name:$('e_name'),email:$('e_email'),role:$('e_role'),status:$('e_status'),school_id:$('e_school'),password:$('e_pass')};
  const r=await apiPost('users.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mEdit');setTimeout(()=>location.reload(),800);}
}
async function resendEmail(id) {
    if (!confirm('Resend the welcome email with a new password setup link?')) return;
    const r = await apiPost('users.php', { action: 'resend_email', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
}
async function delUser(id,name,btn){
  if(!confirm(`Delete "${name}"?\n\nThis cannot be undone.`))return;
  const r=await apiPost('users.php',{action:'delete',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok)btn?.closest('tr')?.remove();
}
async function importUsers() {
    const file = document.getElementById('csvFile').files[0];
    if (!file) { toast('Please select a CSV file.', 'err'); return; }
    
    const formData = new FormData();
    formData.append('action', 'import');
    formData.append('csv', file);
    formData.append('csrf_token', '<?= csrfToken() ?>');

    toast('Importing users... please wait.', 'info');
    
    try {
        const response = await fetch('users.php', { method: 'POST', body: formData });
        const r = await response.json();
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) {
            closeModal('mImport');
            if (r.errors && r.errors.length > 0) {
                console.error('Import errors:', r.errors);
                alert('Some rows had errors:\n' + r.errors.join('\n'));
            }
            setTimeout(() => location.reload(), 1500);
        }
    } catch (e) {
        toast('Upload failed.', 'err');
    }
}

// Auto-open create modal if action=create is in URL
window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('action') === 'create') {
    openModal('mCreate');
  }
});

function importHandleFile(file) {
  if (!file) return;
  document.getElementById('importDropIdle').style.display = 'none';
  document.getElementById('importDropSelected').style.display = 'block';
  document.getElementById('importFileName').textContent = file.name;
  document.getElementById('importFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
  const btn = document.getElementById('importSubmitBtn');
  btn.disabled = false;
  btn.style.opacity = '1';
  btn.style.cursor = 'pointer';
}
function importClearFile() {
  document.getElementById('importDropIdle').style.display = 'block';
  document.getElementById('importDropSelected').style.display = 'none';
  document.getElementById('csvFile').value = '';
  const btn = document.getElementById('importSubmitBtn');
  btn.disabled = true;
  btn.style.opacity = '0.45';
  btn.style.cursor = 'not-allowed';
}
function importHandleDrop(event) {
  event.preventDefault();
  const dz = document.getElementById('importDropZone');
  dz.style.borderColor = 'var(--n-200)';
  dz.style.background = 'transparent';
  const file = event.dataTransfer.files[0];
  if (file && file.name.endsWith('.csv')) {
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('csvFile').files = dt.files;
    importHandleFile(file);
  }
}

// Auto-open create modal if action=create is in URL
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>