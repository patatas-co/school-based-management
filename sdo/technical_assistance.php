<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo','admin');
$db = getDB();
$divisionId = $_SESSION['division_id'] ?? null;
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='add_ta') {
        try {
            $db->prepare("INSERT INTO technical_assistance (school_id,cycle_id,dimension_id,sdo_user_id,ta_type,title,description,recommendation,scheduled_date,status)
              VALUES (?,?,?,?,?,?,?,?,?,?)")
              ->execute([(int)$_POST['school_id'],(int)$_POST['cycle_id'],$_POST['dim_id']?:null,$_SESSION['user_id'],
                         $_POST['ta_type'],$_POST['title'],$_POST['description'],$_POST['recommendation'],
                         $_POST['scheduled_date']?:null,'scheduled']);
            echo json_encode(['ok'=>true,'msg'=>'TA activity scheduled.']); exit;
        } catch(Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit; }
    }
    if ($_POST['action']==='mark_done') {
        $db->prepare("UPDATE technical_assistance SET status='conducted',conducted_date=NOW(),outcomes=? WHERE ta_id=?")
           ->execute([$_POST['outcomes'],(int)$_POST['ta_id']]);
        echo json_encode(['ok'=>true,'msg'=>'Marked as conducted.']); exit;
    }
}

$taListQ = "SELECT ta.*,s.school_name,u.full_name sdo_name,d.dimension_name
  FROM technical_assistance ta JOIN schools s ON ta.school_id=s.school_id
  JOIN users u ON ta.sdo_user_id=u.user_id
  LEFT JOIN sbm_dimensions d ON ta.dimension_id=d.dimension_id
  WHERE 1=1";
$params = [];
if ($divisionId) { $taListQ .= " AND s.division_id=?"; $params[] = $divisionId; }
$taListQ .= " ORDER BY ta.created_at DESC";
$stmt = $db->prepare($taListQ); $stmt->execute($params); $taList = $stmt->fetchAll();

// Schools for dropdown
$schoolsQ = "SELECT s.school_id,s.school_name FROM schools s WHERE 1=1";
$sp = [];
if ($divisionId) { $schoolsQ .= " AND s.division_id=?"; $sp[] = $divisionId; }
$schoolsQ .= " ORDER BY s.school_name";
$stmt2 = $db->prepare($schoolsQ); $stmt2->execute($sp); $schools = $stmt2->fetchAll();

// Cycles for schools
$cyclesQ = "SELECT c.cycle_id,c.school_id,sy.label FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.sy_id=?";
$cycles = $db->prepare($cyclesQ); $cycles->execute([$sy['sy_id']??0]); $cycles = $cycles->fetchAll();

$dimensions = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();

$pageTitle = 'Technical Assistance'; $activePage = 'technical_assistance.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Technical Assistance</h2><p>Plan and track TA activities for schools.</p></div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mAddTA')"><?= svgIcon('plus') ?> Schedule TA</button>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <span class="card-title">TA Activities <span style="font-weight:400;color:var(--n400);">(<?= count($taList) ?>)</span></span>
    <div class="search"><span class="si"><?= svgIcon('search') ?></span>
      <input type="text" placeholder="Search…" oninput="filterTable(this.value,'taTbl')">
    </div>
  </div>
  <div class="tbl-wrap">
    <table id="taTbl">
      <thead><tr><th>School</th><th>Type</th><th>Title</th><th>Dimension</th><th>Scheduled</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($taList as $ta): ?>
      <tr>
        <td><strong style="font-size:13px;"><?= e($ta['school_name']) ?></strong></td>
        <td><span class="pill pill-<?= $ta['ta_type']==='monitoring'?'in_progress':'validated' ?>"><?= ucfirst($ta['ta_type']) ?></span></td>
        <td style="font-size:13px;"><?= e($ta['title']) ?></td>
        <td style="font-size:12px;color:var(--n500);"><?= $ta['dimension_name'] ? 'Dim. '.e($ta['dimension_name']) : '—' ?></td>
        <td style="font-size:12.5px;"><?= $ta['scheduled_date'] ? date('M d, Y',strtotime($ta['scheduled_date'])) : '—' ?></td>
        <td>
          <?php if($ta['status']==='conducted'): ?>
            <span class="pill pill-validated">Conducted</span>
          <?php elseif($ta['status']==='cancelled'): ?>
            <span class="pill pill-returned">Cancelled</span>
          <?php else: ?>
            <span class="pill pill-in_progress">Scheduled</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if($ta['status']==='scheduled'): ?>
          <button class="btn btn-success btn-sm" onclick="markDone(<?= $ta['ta_id'] ?>)"><?= svgIcon('check') ?> Done</button>
          <?php else: ?>
          <span style="font-size:11.5px;color:var(--n400);"><?= $ta['conducted_date']?date('M d',strtotime($ta['conducted_date'])):'' ?></span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$taList): ?><tr><td colspan="7" style="text-align:center;padding:24px;color:var(--n400);">No TA activities yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add TA Modal -->
<div class="overlay" id="mAddTA">
  <div class="modal" style="max-width:580px;">
    <div class="modal-head"><span class="modal-title">Schedule Technical Assistance</span>
      <button class="modal-close" onclick="closeModal('mAddTA')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="fg"><label>School *</label>
          <select class="fc" id="ta_school" onchange="loadCycles()">
            <option value="">— Select School —</option>
            <?php foreach($schools as $s): ?>
            <option value="<?= $s['school_id'] ?>"><?= e($s['school_name']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="fg"><label>Cycle *</label>
          <select class="fc" id="ta_cycle"><option value="">— Select Cycle —</option></select></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>TA Type *</label>
          <select class="fc" id="ta_type">
            <option value="coaching">Coaching</option>
            <option value="mentoring">Mentoring</option>
            <option value="training">Training</option>
            <option value="monitoring" selected>Monitoring</option>
            <option value="evaluation">Evaluation</option>
          </select></div>
        <div class="fg"><label>Dimension (optional)</label>
          <select class="fc" id="ta_dim">
            <option value="">— All Dimensions —</option>
            <?php foreach($dimensions as $d): ?>
            <option value="<?= $d['dimension_id'] ?>">Dim <?= $d['dimension_no'] ?>: <?= e($d['dimension_name']) ?></option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <div class="fg"><label>Title *</label><input class="fc" id="ta_title" placeholder="e.g. Coaching on Dimension 1 Improvement"></div>
      <div class="fg"><label>Description</label><textarea class="fc" id="ta_desc" rows="2" placeholder="Describe the TA activity…"></textarea></div>
      <div class="fg"><label>Recommendation</label><textarea class="fc" id="ta_rec" rows="2" placeholder="Technical recommendations for the school…"></textarea></div>
      <div class="fg"><label>Scheduled Date</label><input class="fc" type="date" id="ta_date"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mAddTA')">Cancel</button>
      <button class="btn btn-primary" onclick="submitTA()">Schedule TA</button>
    </div>
  </div>
</div>

<!-- Mark Done Modal -->
<div class="overlay" id="mDone">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head"><span class="modal-title">Mark TA as Conducted</span>
      <button class="modal-close" onclick="closeModal('mDone')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="done_id">
      <div class="fg"><label>Outcomes / Findings</label><textarea class="fc" id="done_outcomes" rows="4" placeholder="Describe outcomes and findings from the TA activity…"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mDone')">Cancel</button>
      <button class="btn btn-primary" onclick="submitDone()">Save Outcomes</button>
    </div>
  </div>
</div>

<script>
const ALL_CYCLES = <?= json_encode($cycles) ?>;
function loadCycles() {
  const sid = document.getElementById('ta_school').value;
  const sel = document.getElementById('ta_cycle');
  sel.innerHTML = '<option value="">— Select Cycle —</option>';
  ALL_CYCLES.filter(c=>String(c.school_id)===String(sid)).forEach(c=>{
    sel.innerHTML += `<option value="${c.cycle_id}">SY ${c.label}</option>`;
  });
}
async function submitTA() {
  const d = {action:'add_ta',school_id:$('ta_school'),cycle_id:$('ta_cycle'),ta_type:$('ta_type'),
    dim_id:$('ta_dim'),title:$('ta_title'),description:document.getElementById('ta_desc').value,
    recommendation:document.getElementById('ta_rec').value,scheduled_date:$('ta_date')};
  if (!d.school_id||!d.cycle_id||!d.title) { toast('Please fill required fields.','warning'); return; }
  const r = await apiPost('technical_assistance.php', d);
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok){closeModal('mAddTA');setTimeout(()=>location.reload(),800);}
}
function markDone(id) { $v('done_id',id); $v('done_outcomes',''); openModal('mDone'); }
async function submitDone() {
  const r = await apiPost('technical_assistance.php',{action:'mark_done',ta_id:$('done_id'),outcomes:document.getElementById('done_outcomes').value});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mDone');setTimeout(()=>location.reload(),800);}
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
