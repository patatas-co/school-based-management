<?php
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
        if (strlen($pw) < 8) { echo json_encode(['ok'=>false,'msg'=>'Password must be at least 8 characters.']); exit; }
        $role = $_POST['role'] ?? '';
        if (!in_array($role, ['admin','school_head','teacher','sdo','ro'])) { echo json_encode(['ok'=>false,'msg'=>'Invalid role.']); exit; }
        try {
            $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id) VALUES (?,?,?,?,?,?,?)")
               ->execute([trim($_POST['username']),password_hash($pw,PASSWORD_DEFAULT),trim($_POST['email']),trim($_POST['full_name']),$role,$_POST['status'],$_POST['school_id']?:null]);
            logActivity('create_user','users','Created user: '.trim($_POST['username']));
            $newId = $db->lastInsertId();
$schoolStmt = $db->prepare("SELECT school_name FROM schools WHERE school_id=?");
$schoolStmt->execute([$_POST['school_id'] ?: 0]);
$schoolName = $_POST['school_id'] ? ($schoolStmt->fetchColumn() ?: '—') : '—';
echo json_encode(['ok'=>true,'msg'=>'User created successfully.','user'=>[
    'id'        => $newId,
    'full_name' => trim($_POST['full_name']),
    'username'  => trim($_POST['username']),
    'email'     => trim($_POST['email']),
    'role'      => $_POST['role'],
    'status'    => $_POST['status'],
    'school'    => $schoolName ?: '—',
]]); exit;
        } catch(Exception $e) { echo json_encode(['ok'=>false,'msg'=>'Username or email already exists.']); exit; }
    }
    if ($action === 'get') {
        $st = $db->prepare("SELECT user_id,username,email,full_name,role,status,school_id FROM users WHERE user_id=?");
        $st->execute([(int)$_POST['id']]); echo json_encode($st->fetch()); exit;
    }
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $pw = $_POST['password'] ?? '';
        if ($pw) {
            $db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=?,password=? WHERE user_id=?")
               ->execute([trim($_POST['full_name']),trim($_POST['email']),$_POST['role'],$_POST['status'],$_POST['school_id']?:null,password_hash($pw,PASSWORD_DEFAULT),$id]);
        } else {
            $db->prepare("UPDATE users SET full_name=?,email=?,role=?,status=?,school_id=? WHERE user_id=?")
               ->execute([trim($_POST['full_name']),trim($_POST['email']),$_POST['role'],$_POST['status'],$_POST['school_id']?:null,$id]);
        }
        logActivity('update_user','users','Updated user ID: '.$id);
        echo json_encode(['ok'=>true,'msg'=>'User updated.']); exit;
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['user_id']) { echo json_encode(['ok'=>false,'msg'=>'Cannot delete your own account.']); exit; }
        $db->prepare("DELETE FROM users WHERE user_id=?")->execute([$id]);
        echo json_encode(['ok'=>true,'msg'=>'User deleted.']); exit;
    }
    exit;
}

$q  = trim($_GET['q'] ?? '');
$rf = $_GET['role'] ?? '';
$sql = "SELECT u.*,s.school_name FROM users u LEFT JOIN schools s ON u.school_id=s.school_id WHERE 1=1";
$p = [];
if ($q) {
    $qEsc = '%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$q).'%';
    $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $p = array_merge($p, [$qEsc,$qEsc,$qEsc]);
}
if ($rf) { $sql .= " AND u.role=?"; $p[] = $rf; }
$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($p); $users = $stmt->fetchAll();

$schools = $db->query("SELECT school_id,school_name FROM schools ORDER BY school_name")->fetchAll();
$pageTitle = 'User Management'; $activePage = 'users.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>User Management</h2><p>Manage all portal accounts across roles.</p></div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mCreate')"><?= svgIcon('plus') ?> Add User</button>
  </div>
</div>

<div class="card mb5" style="margin-bottom:16px;">
  <div class="card-body" style="padding:12px 16px;">
    <form method="get" class="flex-c" style="gap:10px;flex-wrap:wrap;">
      <div class="search"><span class="si"><?= svgIcon('search') ?></span><input type="text" name="q" placeholder="Search users…" value="<?= e($q) ?>"></div>
      <select name="role" class="fc" style="width:160px;">
        <option value="">All Roles</option>
        <?php foreach(['admin','school_head','teacher','sdo','ro'] as $r): ?>
        <option value="<?= $r ?>" <?= $rf===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="users.php" class="btn btn-secondary btn-sm">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">All Users <span style="font-weight:400;color:var(--n400);">(<?= count($users) ?>)</span></span></div>
  <div class="tbl-wrap">
    <table id="tblUsers">
      <thead><tr><th>User</th><th>Username</th><th>Role</th><th>School</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td>
          <div class="flex-c" style="gap:9px;">
            <div style="width:32px;height:32px;border-radius:8px;background:var(--g100);color:var(--g700);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
            <div><strong style="font-size:13px;"><?= e($u['full_name']) ?></strong><div style="font-size:11.5px;color:var(--n400);"><?= e($u['email']) ?></div></div>
          </div>
        </td>
        <td style="font-family:monospace;font-size:12.5px;color:var(--n500);"><?= e($u['username']) ?></td>
        <td><span class="pill pill-<?= e($u['role']) ?>"><?= ucfirst(str_replace('_',' ',$u['role'])) ?></span></td>
        <td style="font-size:12.5px;"><?= e($u['school_name'] ?? '—') ?></td>
        <td><span class="pill pill-<?= e($u['status']) ?>"><?= ucfirst($u['status']) ?></span></td>
        <td style="font-size:12px;color:var(--n400);"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
        <td>
          <div class="flex-c" style="gap:5px;">
            <button class="btn btn-secondary btn-sm" onclick="editUser(<?= $u['user_id'] ?>)"><?= svgIcon('edit') ?></button>
            <?php if($u['user_id'] != $_SESSION['user_id']): ?>
            <button class="btn btn-danger btn-sm" onclick="delUser(<?= $u['user_id'] ?>,'<?= e(addslashes($u['full_name'])) ?>',this)"><?= svgIcon('trash') ?></button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$users): ?><tr><td colspan="7" style="text-align:center;color:var(--n400);padding:24px;">No users found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create Modal -->
<div class="overlay" id="mCreate">
  <div class="modal">
    <div class="modal-head"><span class="modal-title">Create New User</span><button class="modal-close" onclick="closeModal('mCreate')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="fg"><label>Full Name *</label><input class="fc" id="c_name" placeholder="Juan dela Cruz"></div>
        <div class="fg"><label>Username *</label><input class="fc" id="c_user" placeholder="juandelacruz"></div>
      </div>
      <div class="fg"><label>Email *</label><input class="fc" type="email" id="c_email" placeholder="juan@deped.gov.ph"></div>
      <div class="form-row">
        <div class="fg"><label>Role *</label>
          <select class="fc" id="c_role">
            <?php foreach(['admin','school_head','teacher','sdo','ro'] as $r): ?>
            <option value="<?= $r ?>"><?= ucfirst(str_replace('_',' ',$r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="c_status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <div class="fg"><label>School</label>
        <select class="fc" id="c_school">
          <option value="">— No School —</option>
          <?php foreach($schools as $sc): ?>
          <option value="<?= $sc['school_id'] ?>"><?= e($sc['school_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg"><label>Password * <span style="font-weight:400;color:var(--n400);">(min 8 characters)</span></label><input class="fc" type="password" id="c_pass" placeholder="Minimum 8 characters"></div>
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
            <?php foreach(['admin','school_head','teacher','sdo','ro'] as $r): ?>
            <option value="<?= $r ?>"><?= ucfirst(str_replace('_',' ',$r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Status</label>
          <select class="fc" id="e_status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select>
        </div>
      </div>
      <div class="fg"><label>School</label>
        <select class="fc" id="e_school">
          <option value="">— No School —</option>
          <?php foreach($schools as $sc): ?>
          <option value="<?= $sc['school_id'] ?>"><?= e($sc['school_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg"><label>New Password <span style="font-weight:400;color:var(--n400);">(leave blank to keep)</span></label><input class="fc" type="password" id="e_pass"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mEdit')">Cancel</button>
      <button class="btn btn-primary" onclick="updateUser()">Save Changes</button>
    </div>
  </div>
</div>

<script>
async function createUser(){
  const d={action:'create',full_name:$('c_name'),username:$('c_user'),email:$('c_email'),role:$('c_role'),status:$('c_status'),school_id:$('c_school'),password:$('c_pass')};
  const r=await apiPost('users.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){
    closeModal('mCreate');
    // Clear form
    ['c_name','c_user','c_email','c_pass'].forEach(id=>$v(id,''));

    // Instantly add new row at top of table
    const tbody = document.querySelector('#tblUsers tbody');
    const initials = r.user.full_name.charAt(0).toUpperCase();
    const rolePill = r.user.role.replace(/_/g,' ');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div class="flex-c" style="gap:9px;">
          <div style="width:32px;height:32px;border-radius:8px;background:var(--g100);color:var(--g700);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${initials}</div>
          <div><strong style="font-size:13px;">${r.user.full_name}</strong><div style="font-size:11.5px;color:var(--n400);">${r.user.email}</div></div>
        </div>
      </td>
      <td style="font-family:monospace;font-size:12.5px;color:var(--n500);">${r.user.username}</td>
      <td><span class="pill pill-${r.user.role}">${rolePill.charAt(0).toUpperCase()+rolePill.slice(1)}</span></td>
      <td style="font-size:12.5px;">${r.user.school}</td>
      <td><span class="pill pill-${r.user.status}">${r.user.status.charAt(0).toUpperCase()+r.user.status.slice(1)}</span></td>
      <td style="font-size:12px;color:var(--n400);">just now</td>
      <td>
        <div class="flex-c" style="gap:5px;">
          <button class="btn btn-secondary btn-sm" onclick="editUser(${r.user.id})">${svgI('edit')}</button>
          <button class="btn btn-danger btn-sm" onclick="delUser(${r.user.id},'${r.user.full_name}')">${svgI('trash')}</button>
        </div>
      </td>`;
    tbody.insertBefore(tr, tbody.firstChild);

    // Update user count in header
    const countEl = document.querySelector('[data-live="total-users"]');
    if(countEl) countEl.textContent = parseInt(countEl.textContent||0)+1;

    // Update table caption count
    const cap = document.querySelector('.card-title');
    if(cap) cap.innerHTML = `All Users <span style="font-weight:400;color:var(--n400);">(${tbody.querySelectorAll('tr').length})</span>`;
  }
}
async function editUser(id){
  const r=await apiPost('users.php',{action:'get',id});
  $v('e_id',r.user_id);$v('e_name',r.full_name);$v('e_email',r.email);
  $v('e_role',r.role);$v('e_status',r.status);$v('e_school',r.school_id);$v('e_pass','');
  openModal('mEdit');
}
async function updateUser(){
  const d={action:'update',id:$('e_id'),full_name:$('e_name'),email:$('e_email'),role:$('e_role'),status:$('e_status'),school_id:$('e_school'),password:$('e_pass')};
  const r=await apiPost('users.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mEdit');setTimeout(()=>location.reload(),800);}
}
async function delUser(id, name, btn){
  if(!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  const r = await apiPost('users.php', {action:'delete', id});
  toast(r.msg, r.ok ? 'ok' : 'err');
  if(r.ok) btn?.closest('tr')?.remove();
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
