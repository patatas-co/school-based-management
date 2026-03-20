<?php
// ============================================================
// admin/schools.php — REDESIGNED v3
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='save') {
        $id=(int)($_POST['school_id']??0);
        $data=[trim($_POST['school_name']),trim($_POST['school_id_deped']),trim($_POST['address']),$_POST['classification'],trim($_POST['school_head_name']),trim($_POST['contact_no']),trim($_POST['email']),(int)$_POST['total_enrollment'],(int)$_POST['total_teachers'],(int)$_POST['division_id']];
        if($id){
            if($_SESSION['role']==='sdo'){
                if(empty($_SESSION['division_id'])){echo json_encode(['ok'=>false,'msg'=>'No division assigned.']);exit;}
                $check=$db->prepare("SELECT 1 FROM schools WHERE school_id=? AND division_id=?");$check->execute([$id,$_SESSION['division_id']]);
                if(!$check->fetchColumn()){echo json_encode(['ok'=>false,'msg'=>'Access denied.']);exit;}
            }
            $data[]=$id;
            $db->prepare("UPDATE schools SET school_name=?,school_id_deped=?,address=?,classification=?,school_head_name=?,contact_no=?,email=?,total_enrollment=?,total_teachers=?,division_id=? WHERE school_id=?")->execute($data);
            echo json_encode(['ok'=>true,'msg'=>'School updated.']);exit;
        } else {
            $db->prepare("INSERT INTO schools (school_name,school_id_deped,address,classification,school_head_name,contact_no,email,total_enrollment,total_teachers,division_id) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($data);
            echo json_encode(['ok'=>true,'msg'=>'School added.']);exit;
        }
    }
    if($_POST['action']==='get'){$st=$db->prepare("SELECT * FROM schools WHERE school_id=?");$st->execute([(int)$_POST['id']]);echo json_encode($st->fetch());exit;}
    if($_POST['action']==='delete'){
        $id=(int)$_POST['id'];
        if($_SESSION['role']==='sdo'){
            if(empty($_SESSION['division_id'])){echo json_encode(['ok'=>false,'msg'=>'No division assigned.']);exit;}
            $check=$db->prepare("SELECT 1 FROM schools WHERE school_id=? AND division_id=?");$check->execute([$id,$_SESSION['division_id']]);
            if(!$check->fetchColumn()){echo json_encode(['ok'=>false,'msg'=>'Access denied.']);exit;}
        }
        $db->prepare("DELETE FROM schools WHERE school_id=?")->execute([$id]);
        echo json_encode(['ok'=>true,'msg'=>'School removed.']);exit;
    }
    exit;
}

$q=trim($_GET['q']??'');
$classFilter=$_GET['class']??'';
$currentSyId=$db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

$sql="SELECT s.*,d.division_name,c.status AS cycle_status,c.overall_score AS cycle_score,c.maturity_level AS cycle_maturity
      FROM schools s
      LEFT JOIN divisions d ON s.division_id=d.division_id
      LEFT JOIN sbm_cycles c ON c.school_id=s.school_id AND c.sy_id=?";
$p=[$currentSyId];
$where=[];
if($q){$qEsc='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$q).'%';$where[]="(s.school_name LIKE ? OR s.school_id_deped LIKE ?)";$p[]=$qEsc;$p[]=$qEsc;}
if($classFilter){$where[]="s.classification=?";$p[]=$classFilter;}
if($where) $sql.=" WHERE ".implode(' AND ',$where);
$sql.=" ORDER BY s.school_name";
$stmt=$db->prepare($sql);$stmt->execute($p);$schools=$stmt->fetchAll();

$classCounts=$db->query("SELECT classification,COUNT(*) cnt FROM schools GROUP BY classification")->fetchAll(PDO::FETCH_KEY_PAIR);
$divisions=$db->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();

// Stat totals
$totalSchools=$db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$totalEnroll=$db->query("SELECT SUM(total_enrollment) FROM schools")->fetchColumn();
$withCycles=$db->query("SELECT COUNT(DISTINCT school_id) FROM sbm_cycles WHERE sy_id=".intval($currentSyId))->fetchColumn();

$pageTitle='Schools'; $activePage='schools.php';
include __DIR__.'/../includes/header.php';

$classColors=['ES'=>'#16A34A','JHS'=>'#2563EB','SHS'=>'#7C3AED','IS'=>'#D97706','ALS'=>'#0D9488'];
$classLabels=['ES'=>'Elementary','JHS'=>'Junior HS','SHS'=>'Senior HS','IS'=>'Integrated','ALS'=>'ALS'];
?>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Management</div>
    <div class="ph2-title">School Management</div>
    <div class="ph2-sub">Manage all registered schools and their SBM assessment status.</div>
  </div>
  <div class="ph2-right">
    <button class="btn btn-primary" onclick="openModal('mSchool');resetForm()"><?= svgIcon('plus') ?> Add School</button>
  </div>
</div>

<!-- KPI strip -->
<div class="kpi-row">
  <div class="kpi-mini">
    <div class="kpi-mini-val"><?= number_format($totalSchools) ?></div>
    <div class="kpi-mini-lbl">Total Schools</div>
  </div>
  <div class="kpi-mini">
    <div class="kpi-mini-val"><?= number_format($totalEnroll) ?></div>
    <div class="kpi-mini-lbl">Total Enrollment</div>
  </div>
  <div class="kpi-mini">
    <div class="kpi-mini-val"><?= $withCycles ?></div>
    <div class="kpi-mini-lbl">With Active Cycle</div>
  </div>
  <?php foreach(['ES','JHS','SHS'] as $cl): ?>
  <div class="kpi-mini">
    <div class="kpi-mini-val" style="color:<?= $classColors[$cl] ?>;"><?= $classCounts[$cl] ?? 0 ?></div>
    <div class="kpi-mini-lbl"><?= $classLabels[$cl] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="filter-bar-v2">
  <form method="get" class="flex-c" style="gap:10px;flex:1;flex-wrap:wrap;">
    <div class="search" style="flex:1;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" name="q" placeholder="Search by school name or DepEd ID…" value="<?= e($q) ?>">
    </div>
    <select name="class" class="fc" style="width:160px;" onchange="this.form.submit()">
      <option value="">All Classifications</option>
      <?php foreach(['ES','JHS','SHS','IS','ALS'] as $cl): ?>
      <option value="<?= $cl ?>" <?= $classFilter===$cl?'selected':'' ?>><?= $classLabels[$cl] ?> (<?= $classColors[$cl] ? '' : '' ?><?= $classCounts[$cl]??0 ?>)</option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if($q||$classFilter): ?><a href="schools.php" class="btn btn-secondary btn-sm">Reset</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="card-title">Schools <span style="font-weight:400;color:var(--n-400);font-size:13px;">(<?= count($schools) ?>)</span></span>
    <?php if($q||$classFilter): ?><span style="font-size:12px;color:var(--n-400);">Filtered view — <a href="schools.php" style="color:var(--brand-600);">clear filters</a></span><?php endif; ?>
  </div>
  <?php if(!$schools): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= svgIcon('home') ?></div>
    <div class="empty-title">No schools found</div>
    <div class="empty-sub"><?= $q ? 'No schools match "'.e($q).'". Try a different search.' : 'No schools registered yet. Add your first school to get started.' ?></div>
    <?php if(!$q): ?><button class="btn btn-primary" onclick="openModal('mSchool');resetForm()"><?= svgIcon('plus') ?> Add School</button><?php endif; ?>
  </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table id="tblSchools" class="tbl-enhanced">
      <thead>
        <tr>
          <th>School</th>
          <th>DepEd ID</th>
          <th>Division</th>
          <th>Class</th>
          <th>Head</th>
          <th>Enrollment</th>
          <th>SBM Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($schools as $s):
        $clColor = $classColors[$s['classification']] ?? 'var(--n-600)';
      ?>
      <tr>
        <td>
          <div class="cell-avatar">
            <div class="cell-av" style="background:<?= $clColor ?>;"><?= strtoupper(substr($s['school_name'],0,1)) ?></div>
            <div class="cell-av-info">
              <div class="cell-av-name"><?= e($s['school_name']) ?></div>
              <div class="cell-av-sub"><?= e($s['address'] ?? '') ?></div>
            </div>
          </div>
        </td>
        <td style="font-family:monospace;font-size:12.5px;color:var(--n-600);"><?= e($s['school_id_deped']??'—') ?></td>
        <td style="font-size:12.5px;"><?= e($s['division_name']??'—') ?></td>
        <td>
          <span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $clColor ?>18;color:<?= $clColor ?>;border:1px solid <?= $clColor ?>30;">
            <?= e($s['classification']) ?>
          </span>
        </td>
        <td style="font-size:12.5px;"><?= e($s['school_head_name']??'—') ?></td>
        <td style="font-size:13px;font-weight:600;"><?= number_format($s['total_enrollment']) ?></td>
        <td>
          <?php if($s['cycle_status']):
            $mat = $s['cycle_score'] ? sbmMaturityLevel(floatval($s['cycle_score'])) : null;
          ?>
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="pill pill-<?= e($s['cycle_status']) ?>"><?= ucfirst(str_replace('_',' ',$s['cycle_status'])) ?></span>
            <?php if($s['cycle_score']): ?>
            <span style="font-size:12px;font-weight:700;color:<?= $mat['color'] ?>;"><?= $s['cycle_score'] ?>%</span>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <span style="font-size:12px;color:var(--n-400);">Not started</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="flex-c" style="gap:4px;">
            <button class="btn btn-secondary btn-sm" onclick="editSchool(<?= $s['school_id'] ?>)"><?= svgIcon('edit') ?></button>
            <button class="btn btn-danger btn-sm" data-id="<?= $s['school_id'] ?>" data-name="<?= e($s['school_name']) ?>" onclick="delSchool(this.dataset.id,this.dataset.name)"><?= svgIcon('trash') ?></button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- School Modal -->
<div class="overlay" id="mSchool">
  <div class="modal" style="max-width:640px;">
    <div class="modal-head">
      <span class="modal-title" id="mSchoolTitle">Add School</span>
      <button class="modal-close" onclick="closeModal('mSchool')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="s_id">
      <div class="form-row">
        <div class="fg"><label>School Name *</label><input class="fc" id="s_name" placeholder="Full school name"></div>
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
            <option value="">— Select Division —</option>
            <?php foreach($divisions as $div): ?>
            <option value="<?= $div['division_id'] ?>"><?= e($div['division_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="fg"><label>School Head</label><input class="fc" id="s_head" placeholder="Name of School Head"></div>
        <div class="fg"><label>Contact Number</label><input class="fc" id="s_contact" placeholder="09XXXXXXXXX"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Email</label><input class="fc" type="email" id="s_email" placeholder="school@deped.gov.ph"></div>
        <div class="fg"><label>Total Enrollment</label><input class="fc" type="number" id="s_enroll" placeholder="0" min="0"></div>
      </div>
      <div style="max-width:50%;">
        <div class="fg"><label>Total Teachers</label><input class="fc" type="number" id="s_teachers" placeholder="0" min="0"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSchool')">Cancel</button>
      <button class="btn btn-primary" onclick="saveSchool()">Save School</button>
    </div>
  </div>
</div>

<script>
function resetForm(){$v('s_id','');$v('s_name','');$v('s_deped_id','');$v('s_address','');$v('s_class','ES');$v('s_division','');$v('s_head','');$v('s_contact','');$v('s_email','');$v('s_enroll','0');$v('s_teachers','0');$el('mSchoolTitle').textContent='Add School';}
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
  if(!confirm(`Delete "${name}"?\n\nAll SBM data for this school will be permanently removed.`))return;
  const r=await apiPost('schools.php',{action:'delete',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok)setTimeout(()=>location.reload(),800);
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>