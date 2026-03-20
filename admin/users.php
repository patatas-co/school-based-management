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
    $action=$_POST['action'];
    if($action==='create'){
        $pw=$_POST['password']??'';
        if(strlen($pw)<8){echo json_encode(['ok'=>false,'msg'=>'Password must be at least 8 characters.']);exit;}
        $role=$_POST['role']??'';
        if(!in_array($role,['admin','school_head','teacher','sdo','ro','external_stakeholder'])){echo json_encode(['ok'=>false,'msg'=>'Invalid role.']);exit;}
        try{
            $db->prepare("INSERT INTO users (username,password,email,full_name,role,status,school_id) VALUES (?,?,?,?,?,?,?)")
               ->execute([trim($_POST['username']),password_hash($pw,PASSWORD_DEFAULT),trim($_POST['email']),trim($_POST['full_name']),$role,$_POST['status'],$_POST['school_id']?:null]);
            logActivity('create_user','users','Created: '.trim($_POST['username']));
            $newId=$db->lastInsertId();
            $schoolStmt=$db->prepare("SELECT school_name FROM schools WHERE school_id=?");
            $schoolStmt->execute([$_POST['school_id']?:0]);
            $schoolName=$_POST['school_id']?($schoolStmt->fetchColumn()?:'—'):'—';
            echo json_encode(['ok'=>true,'msg'=>'User created.','user'=>['id'=>$newId,'full_name'=>trim($_POST['full_name']),'username'=>trim($_POST['username']),'email'=>trim($_POST['email']),'role'=>$_POST['role'],'status'=>$_POST['status'],'school'=>$schoolName]]);exit;
        }catch(Exception $e){echo json_encode(['ok'=>false,'msg'=>'Username or email already exists.']);exit;}
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
      <thead><tr><th>User</th><th>Username</th><th>Role</th><th>School</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
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
        <td style="font-size:12.5px;color:var(--n-600);"><?= e($u['school_name']??'—') ?></td>
        <td>
          <?php $statColors=['active'=>['#DCFCE7','#16A34A'],'inactive'=>['var(--n-100)','var(--n-500)'],'suspended'=>['var(--red-bg)','var(--red)']]; [$sb,$sc]=$statColors[$u['status']]??['var(--n-100)','var(--n-500)']; ?>
          <span style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $sb ?>;color:<?= $sc ?>;"><?= ucfirst($u['status']) ?></span>
        </td>
        <td style="font-size:12px;color:<?= $u['last_login']?'var(--n-400)':'var(--red)' ?>;"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
        <td>
          <div class="flex-c" style="gap:4px;">
            <button class="btn btn-secondary btn-sm" onclick="editUser(<?= $u['user_id'] ?>)"><?= svgIcon('edit') ?></button>
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
      <div class="fg"><label>School</label>
        <select class="fc" id="c_school">
          <option value="">— No School —</option>
          <?php foreach($schools as $sc): ?>
          <option value="<?= $sc['school_id'] ?>"><?= e($sc['school_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg">
        <label>Password * <span style="font-weight:400;color:var(--n-400);">(minimum 8 characters)</span></label>
        <input class="fc" type="password" id="c_pass" placeholder="Minimum 8 characters" autocomplete="new-password">
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
      <div class="fg"><label>School</label>
        <select class="fc" id="e_school">
          <option value="">— No School —</option>
          <?php foreach($schools as $sc): ?>
          <option value="<?= $sc['school_id'] ?>"><?= e($sc['school_name']) ?></option>
          <?php endforeach; ?>
        </select>
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
    tr.innerHTML=`
      <td><div class="cell-avatar"><div class="cell-av" style="background:${rc};">${initials}</div><div class="cell-av-info"><div class="cell-av-name">${escH(r.user.full_name)}</div><div class="cell-av-sub">${escH(r.user.email)}</div></div></div></td>
      <td style="font-family:monospace;font-size:12px;color:var(--n-500);">${escH(r.user.username)}</td>
      <td><span style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:${rc}18;color:${rc};">${escH(r.user.role.replace(/_/g,' ')).replace(/\b\w/g,c=>c.toUpperCase())}</span></td>
      <td style="font-size:12.5px;">${escH(r.user.school)}</td>
      <td><span style="display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#DCFCE7;color:#16A34A;">Active</span></td>
      <td style="font-size:12px;color:var(--red);">Never</td>
      <td><div class="flex-c" style="gap:4px;"><button class="btn btn-secondary btn-sm" onclick="editUser(${r.user.id})">${svgI('edit')}</button><button class="btn btn-danger btn-sm" onclick="delUser(${r.user.id},'${escH(r.user.full_name)}',this)">${svgI('trash')}</button></div></td>`;
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
async function delUser(id,name,btn){
  if(!confirm(`Delete "${name}"?\n\nThis cannot be undone.`))return;
  const r=await apiPost('users.php',{action:'delete',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok)btn?.closest('tr')?.remove();
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>