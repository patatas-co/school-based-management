<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='save_sy') {
        $id = (int)($_POST['sy_id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE school_years SET label=?,date_start=?,date_end=?,is_current=? WHERE sy_id=?")->execute([trim($_POST['label']),$_POST['date_start']?:null,$_POST['date_end']?:null,(int)$_POST['is_current'],$id]);
        } else {
            if ($_POST['is_current']) $db->query("UPDATE school_years SET is_current=0");
            $db->prepare("INSERT INTO school_years (label,date_start,date_end,is_current) VALUES (?,?,?,?)")->execute([trim($_POST['label']),$_POST['date_start']?:null,$_POST['date_end']?:null,(int)$_POST['is_current']]);
        }
        echo json_encode(['ok'=>true,'msg'=>'School year saved.']); exit;
    }
    if ($_POST['action']==='delete_sy') {
        $db->prepare("DELETE FROM school_years WHERE sy_id=?")->execute([(int)$_POST['id']]);
        echo json_encode(['ok'=>true,'msg'=>'School year deleted.']); exit;
    }
    if ($_POST['action']==='get_sy') {
        $st=$db->prepare("SELECT * FROM school_years WHERE sy_id=?");$st->execute([(int)$_POST['id']]);echo json_encode($st->fetch());exit;
    }
    exit;
}

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
$pageTitle = 'Settings'; $activePage = 'settings.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>System Settings</h2><p>Manage school years and system configuration.</p></div>
</div>

<div class="grid2">
  <!-- School Years -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">School Years</span>
      <button class="btn btn-primary btn-sm" onclick="openModal('mSY');resetSY()"><?= svgIcon('plus') ?> Add</button>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Label</th><th>Start</th><th>End</th><th>Current</th><th></th></tr></thead>
        <tbody>
        <?php foreach($syears as $sy): ?>
        <tr>
          <td><strong><?= e($sy['label']) ?></strong></td>
          <td style="font-size:12.5px;"><?= $sy['date_start'] ? date('M d, Y',strtotime($sy['date_start'])) : '—' ?></td>
          <td style="font-size:12.5px;"><?= $sy['date_end'] ? date('M d, Y',strtotime($sy['date_end'])) : '—' ?></td>
          <td><?php if($sy['is_current']): ?><span class="pill pill-active">Current</span><?php endif; ?></td>
          <td>
            <div class="flex-c" style="gap:5px;">
              <button class="btn btn-secondary btn-sm" onclick="editSY(<?= $sy['sy_id'] ?>)"><?= svgIcon('edit') ?></button>
              <button class="btn btn-danger btn-sm" onclick="delSY(<?= $sy['sy_id'] ?>,'<?= e(addslashes($sy['label'])) ?>')"><?= svgIcon('trash') ?></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- System Info -->
  <div class="card">
    <div class="card-head"><span class="card-title">System Information</span></div>
    <div class="card-body">
      <?php
      $schoolCount = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
      $userCount   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $cycleCount  = $db->query("SELECT COUNT(*) FROM sbm_cycles")->fetchColumn();
      $responseCount = $db->query("SELECT COUNT(*) FROM sbm_responses")->fetchColumn();
      $items = [['Schools Registered',$schoolCount],['Total Users',$userCount],['Assessment Cycles',$cycleCount],['Indicator Responses',$responseCount]];
      ?>
      <?php foreach($items as [$label,$val]): ?>
      <div class="flex-cb" style="padding:10px 0;border-bottom:1px solid var(--n100);">
        <span style="font-size:13.5px;color:var(--n600);"><?= $label ?></span>
        <strong style="font-size:15px;color:var(--n900);"><?= number_format($val) ?></strong>
      </div>
      <?php endforeach; ?>
      <div class="flex-cb" style="padding:10px 0;border-bottom:1px solid var(--n100);">
        <span style="font-size:13.5px;color:var(--n600);">PHP Version</span>
        <strong style="font-size:13px;"><?= phpversion() ?></strong>
      </div>
      <div class="flex-cb" style="padding:10px 0;">
        <span style="font-size:13.5px;color:var(--n600);">DepEd Order</span>
        <strong style="font-size:13px;">No. 007, s. 2024</strong>
      </div>
    </div>
  </div>
</div>

<!-- School Year Modal -->
<div class="overlay" id="mSY">
  <div class="modal" style="max-width:440px;">
    <div class="modal-head"><span class="modal-title" id="mSYTitle">Add School Year</span><button class="modal-close" onclick="closeModal('mSY')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="sy_id">
      <div class="fg"><label>Label *</label><input class="fc" id="sy_label" placeholder="e.g. 2024-2025"></div>
      <div class="form-row">
        <div class="fg"><label>Start Date</label><input class="fc" type="date" id="sy_start"></div>
        <div class="fg"><label>End Date</label><input class="fc" type="date" id="sy_end"></div>
      </div>
      <div class="fg">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" id="sy_current"> Set as current school year
        </label>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSY')">Cancel</button>
      <button class="btn btn-primary" onclick="saveSY()">Save</button>
    </div>
  </div>
</div>

<script>
function resetSY(){$v('sy_id','');$v('sy_label','');$v('sy_start','');$v('sy_end','');$el('sy_current').checked=false;$el('mSYTitle').textContent='Add School Year';}
async function saveSY(){
  const d={action:'save_sy',sy_id:$('sy_id'),label:$('sy_label'),date_start:$('sy_start'),date_end:$('sy_end'),is_current:$el('sy_current').checked?1:0};
  const r=await apiPost('settings.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mSY');setTimeout(()=>location.reload(),800);}
}
async function editSY(id){
  const r=await apiPost('settings.php',{action:'get_sy',id});
  $v('sy_id',r.sy_id);$v('sy_label',r.label);$v('sy_start',r.date_start||'');$v('sy_end',r.date_end||'');
  $el('sy_current').checked=!!parseInt(r.is_current);$el('mSYTitle').textContent='Edit School Year';
  openModal('mSY');
}
async function delSY(id,label){
  if(!confirm(`Delete school year "${label}"? This will remove all related assessment cycles.`)) return;
  const r=await apiPost('settings.php',{action:'delete_sy',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),800);
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
