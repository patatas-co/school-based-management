<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='save') {
        $id = (int)($_POST['school_id'] ?? 0);
        $data = [
            trim($_POST['school_name']),trim($_POST['school_id_deped']),
            trim($_POST['address']),$_POST['classification'],
            trim($_POST['school_head_name']),trim($_POST['contact_no']),
            trim($_POST['email']),(int)$_POST['total_enrollment'],
            (int)$_POST['total_teachers'],(int)$_POST['division_id'],
        ];
        if ($id) {
            $data[] = $id;
            $db->prepare("UPDATE schools SET school_name=?,school_id_deped=?,address=?,classification=?,school_head_name=?,contact_no=?,email=?,total_enrollment=?,total_teachers=?,division_id=? WHERE school_id=?")->execute($data);
            echo json_encode(['ok'=>true,'msg'=>'School updated.']); exit;
        } else {
            $db->prepare("INSERT INTO schools (school_name,school_id_deped,address,classification,school_head_name,contact_no,email,total_enrollment,total_teachers,division_id) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($data);
            echo json_encode(['ok'=>true,'msg'=>'School added.']); exit;
        }
    }
    if ($_POST['action']==='get') {
        $st=$db->prepare("SELECT * FROM schools WHERE school_id=?");
        $st->execute([(int)$_POST['id']]); echo json_encode($st->fetch()); exit;
    }
    if ($_POST['action']==='delete') {
        $db->prepare("DELETE FROM schools WHERE school_id=?")->execute([(int)$_POST['id']]);
        echo json_encode(['ok'=>true,'msg'=>'School removed.']); exit;
    }
    exit;
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT s.*,d.division_name FROM schools s LEFT JOIN divisions d ON s.division_id=d.division_id";
$p = [];
if ($q) {
    $qEsc = '%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$q).'%';
    $sql .= " WHERE s.school_name LIKE ? OR s.school_id_deped LIKE ?";
    $p = [$qEsc,$qEsc];
}
$sql .= " ORDER BY s.school_name";
$stmt = $db->prepare($sql); $stmt->execute($p); $schools = $stmt->fetchAll();

$divisions = $db->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();
$pageTitle = 'Schools'; $activePage = 'schools.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>School Management</h2><p>Manage all registered schools.</p></div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mSchool');resetForm()"><?= svgIcon('plus') ?> Add School</button>
  </div>
</div>

<div class="card mb5" style="margin-bottom:14px;">
  <div class="card-body" style="padding:10px 16px;">
    <form method="get" class="flex-c" style="gap:10px;">
      <div class="search"><span class="si"><?= svgIcon('search') ?></span><input type="text" name="q" placeholder="Search schools…" value="<?= e($q) ?>"></div>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <a href="schools.php" class="btn btn-secondary btn-sm">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">Schools (<?= count($schools) ?>)</span></div>
  <div class="tbl-wrap">
    <table id="tblSchools">
      <thead><tr><th>School</th><th>School ID</th><th>Division</th><th>Classification</th><th>Head</th><th>Enrollment</th><th>SBM Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($schools as $s): ?>
      <?php
        $cycle = $db->prepare("SELECT status,overall_score,maturity_level FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND sy.is_current=1 LIMIT 1");
        $cycle->execute([$s['school_id']]); $cyc = $cycle->fetch();
      ?>
      <tr>
        <td>
          <div><strong style="font-size:13px;"><?= e($s['school_name']) ?></strong>
          <?php if($s['address']): ?><div style="font-size:11.5px;color:var(--n400);"><?= e($s['address']) ?></div><?php endif; ?>
          </div>
        </td>
        <td style="font-family:monospace;font-size:12.5px;"><?= e($s['school_id_deped']??'—') ?></td>
        <td style="font-size:12.5px;"><?= e($s['division_name']??'—') ?></td>
        <td><span class="pill pill-active"><?= e($s['classification']) ?></span></td>
        <td style="font-size:12.5px;"><?= e($s['school_head_name']??'—') ?></td>
        <td style="font-size:13px;font-weight:600;"><?= number_format($s['total_enrollment']) ?></td>
        <td>
          <?php if($cyc): ?>
            <span class="pill pill-<?= e($cyc['status']) ?>"><?= ucfirst(str_replace('_',' ',$cyc['status'])) ?></span>
            <?php if($cyc['overall_score']): ?><span style="margin-left:5px;font-size:12px;font-weight:700;color:var(--g700);"><?= $cyc['overall_score'] ?>%</span><?php endif; ?>
          <?php else: ?>
            <span style="font-size:12px;color:var(--n400);">Not Started</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="flex-c" style="gap:5px;">
            <button class="btn btn-secondary btn-sm" onclick="editSchool(<?= $s['school_id'] ?>)"><?= svgIcon('edit') ?></button>
            <button class="btn btn-danger btn-sm" onclick="delSchool(<?= $s['school_id'] ?>,'<?= e(addslashes($s['school_name'])) ?>')"><?= svgIcon('trash') ?></button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$schools): ?><tr><td colspan="8" style="text-align:center;color:var(--n400);padding:24px;">No schools found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- School Modal -->
<div class="overlay" id="mSchool">
  <div class="modal" style="max-width:620px;">
    <div class="modal-head"><span class="modal-title" id="mSchoolTitle">Add School</span><button class="modal-close" onclick="closeModal('mSchool')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="s_id">
      <div class="form-row">
        <div class="fg"><label>School Name *</label><input class="fc" id="s_name" placeholder="School name"></div>
        <div class="fg"><label>DepEd School ID</label><input class="fc" id="s_deped_id" placeholder="e.g. 301143"></div>
      </div>
      <div class="fg"><label>Address</label><input class="fc" id="s_address" placeholder="City/Municipality, Province"></div>
      <div class="form-row">
        <div class="fg"><label>Classification</label>
          <select class="fc" id="s_class">
            <option value="ES">Elementary (ES)</option>
            <option value="JHS">Junior High (JHS)</option>
            <option value="SHS">Senior High (SHS)</option>
            <option value="IS">Integrated (IS)</option>
            <option value="ALS">ALS</option>
          </select>
        </div>
        <div class="fg"><label>Division</label>
          <select class="fc" id="s_division">
            <option value="">— Select —</option>
            <?php foreach($divisions as $div): ?>
            <option value="<?= $div['division_id'] ?>"><?= e($div['division_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="fg"><label>School Head</label><input class="fc" id="s_head" placeholder="Name of School Head"></div>
        <div class="fg"><label>Contact No.</label><input class="fc" id="s_contact" placeholder="09XXXXXXXXX"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Email</label><input class="fc" type="email" id="s_email" placeholder="school@deped.gov.ph"></div>
        <div class="fg"><label>Total Enrollment</label><input class="fc" type="number" id="s_enroll" placeholder="0" min="0"></div>
      </div>
      <div class="fg" style="max-width:50%;"><label>Total Teachers</label><input class="fc" type="number" id="s_teachers" placeholder="0" min="0"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSchool')">Cancel</button>
      <button class="btn btn-primary" onclick="saveSchool()">Save School</button>
    </div>
  </div>
</div>

<script>
function resetForm(){$v('s_id','');$v('s_name','');$v('s_deped_id','');$v('s_address','');$v('s_class','JHS');$v('s_division','');$v('s_head','');$v('s_contact','');$v('s_email','');$v('s_enroll','0');$v('s_teachers','0');$el('mSchoolTitle').textContent='Add School';}
async function saveSchool(){
  const d={action:'save',school_id:$('s_id'),school_name:$('s_name'),school_id_deped:$('s_deped_id'),address:$('s_address'),classification:$('s_class'),division_id:$('s_division'),school_head_name:$('s_head'),contact_no:$('s_contact'),email:$('s_email'),total_enrollment:$('s_enroll'),total_teachers:$('s_teachers')};
  const r=await apiPost('schools.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mSchool');setTimeout(()=>location.reload(),800);}
}
async function editSchool(id){
  const r=await apiPost('schools.php',{action:'get',id});
  $v('s_id',r.school_id);$v('s_name',r.school_name);$v('s_deped_id',r.school_id_deped);$v('s_address',r.address);
  $v('s_class',r.classification);$v('s_division',r.division_id);$v('s_head',r.school_head_name);
  $v('s_contact',r.contact_no);$v('s_email',r.email);$v('s_enroll',r.total_enrollment);$v('s_teachers',r.total_teachers);
  $el('mSchoolTitle').textContent='Edit School';
  openModal('mSchool');
}
async function delSchool(id,name){
  if(!confirm(`Delete "${name}"? All SBM data for this school will be removed. This cannot be undone.`)) return;
  const r=await apiPost('schools.php',{action:'delete',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),800);
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
