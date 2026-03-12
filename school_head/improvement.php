<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId,$syId]); $cycle = $cycle->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='save') {
        if (!$cycle) { echo json_encode(['ok'=>false,'msg'=>'No active assessment cycle.']); exit; }
        $id = (int)($_POST['plan_id'] ?? 0);
        $data = [
            $schoolId,$cycle['cycle_id'],(int)$_POST['dimension_id'],
            $_POST['indicator_id']?:null,$_POST['priority'],
            trim($_POST['objective']),trim($_POST['strategy']),
            trim($_POST['person_responsible']),$_POST['target_date']?:null,
            trim($_POST['resources_needed']),trim($_POST['expected_output']),$_SESSION['user_id']
        ];
        if ($id) {
            $data[] = $id;
            $db->prepare("UPDATE improvement_plans SET dimension_id=?,indicator_id=?,priority_level=?,objective=?,strategy=?,person_responsible=?,target_date=?,resources_needed=?,expected_output=? WHERE plan_id=? AND school_id=?")
               ->execute([(int)$_POST['dimension_id'],$_POST['indicator_id']?:null,$_POST['priority'],trim($_POST['objective']),trim($_POST['strategy']),trim($_POST['person_responsible']),$_POST['target_date']?:null,trim($_POST['resources_needed']),trim($_POST['expected_output']),$id,$schoolId]);
        } else {
            $db->prepare("INSERT INTO improvement_plans (school_id,cycle_id,dimension_id,indicator_id,priority_level,objective,strategy,person_responsible,target_date,resources_needed,expected_output,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute($data);
        }
        echo json_encode(['ok'=>true,'msg'=>'Plan saved.']); exit;
    }
    if ($_POST['action']==='update_status') {
        $db->prepare("UPDATE improvement_plans SET status=?,remarks=? WHERE plan_id=? AND school_id=?")
           ->execute([$_POST['status'],trim($_POST['remarks']),(int)$_POST['id'],$schoolId]);
        echo json_encode(['ok'=>true,'msg'=>'Status updated.']); exit;
    }
    if ($_POST['action']==='delete') {
        $db->prepare("DELETE FROM improvement_plans WHERE plan_id=? AND school_id=?")->execute([(int)$_POST['id'],$schoolId]);
        echo json_encode(['ok'=>true,'msg'=>'Plan deleted.']); exit;
    }
    if ($_POST['action']==='get') {
        $st=$db->prepare("SELECT * FROM improvement_plans WHERE plan_id=? AND school_id=?");
        $st->execute([(int)$_POST['id'],$schoolId]); echo json_encode($st->fetch()); exit;
    }
    exit;
}

$plans = $cycle ? $db->prepare("SELECT ip.*,d.dimension_name,d.color_hex,i.indicator_code FROM improvement_plans ip JOIN sbm_dimensions d ON ip.dimension_id=d.dimension_id LEFT JOIN sbm_indicators i ON ip.indicator_id=i.indicator_id WHERE ip.cycle_id=? ORDER BY FIELD(ip.priority_level,'High','Medium','Low'),ip.created_at DESC") : null;
if ($plans) { $plans->execute([$cycle['cycle_id']]); $plans = $plans->fetchAll(); } else $plans = [];

$dims = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
$inds = $db->query("SELECT i.*,d.dimension_no FROM sbm_indicators i JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id ORDER BY d.dimension_no,i.sort_order")->fetchAll();

$pageTitle = 'Improvement Plan'; $activePage = 'improvement.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>School Improvement Plan</h2><p>Action plans derived from your SBM self-assessment.</p></div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mPlan');resetPlan()"><?= svgIcon('plus') ?> Add Action Plan</button>
  </div>
</div>

<?php if(!$cycle): ?><div class="alert alert-warning"><?= svgIcon('alert-circle') ?> Complete your SBM self-assessment first before creating improvement plans.</div><?php endif; ?>

<!-- Summary by priority -->
<?php if($plans): ?>
<?php $byPriority = ['High'=>0,'Medium'=>0,'Low'=>0,'completed'=>0]; foreach($plans as $p){ $byPriority[$p['priority_level']]++; if($p['status']==='completed') $byPriority['completed']++; } ?>
<div class="stats" style="margin-bottom:18px;">
  <div class="stat"><div class="stat-ic red"><?= svgIcon('alert-circle') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['High'] ?></div><div class="stat-lbl">High Priority</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('star') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['Medium'] ?></div><div class="stat-lbl">Medium Priority</div></div></div>
  <div class="stat"><div class="stat-ic blue"><?= svgIcon('info') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['Low'] ?></div><div class="stat-lbl">Low Priority</div></div></div>
  <div class="stat"><div class="stat-ic green"><?= svgIcon('check') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['completed'] ?></div><div class="stat-lbl">Completed</div></div></div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head"><span class="card-title">Action Plans (<?= count($plans) ?>)</span></div>
  <?php if(!$plans): ?>
  <div class="card-body" style="text-align:center;padding:40px;color:var(--n400);">No action plans yet. Add plans to address areas for improvement.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table id="tblPlans">
      <thead><tr><th>Priority</th><th>Dimension</th><th>Objective</th><th>Person Responsible</th><th>Target Date</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($plans as $p): ?>
      <?php $pColors = ['High'=>'var(--red)','Medium'=>'var(--gold)','Low'=>'var(--blue)'];
            $sBgs   = ['planned'=>'var(--n100)','ongoing'=>'var(--blueb)','completed'=>'var(--g100)','cancelled'=>'var(--redb)'];
            $sSubs  = ['planned'=>'var(--n500)','ongoing'=>'var(--blue)','completed'=>'var(--g700)','cancelled'=>'var(--red)']; ?>
      <tr>
        <td><span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $pColors[$p['priority_level']] ?>22;color:<?= $pColors[$p['priority_level']] ?>;"><?= e($p['priority_level']) ?></span></td>
        <td style="font-size:12.5px;">
          <div style="font-weight:600;color:<?= e($p['color_hex']) ?>;"><?= e($p['dimension_name']) ?></div>
          <?php if($p['indicator_code']): ?><div style="font-size:11px;color:var(--n400);"><?= e($p['indicator_code']) ?></div><?php endif; ?>
        </td>
        <td style="font-size:13px;max-width:220px;"><?= e(substr($p['objective'],0,80)) ?><?= strlen($p['objective'])>80?'…':'' ?></td>
        <td style="font-size:12.5px;"><?= e($p['person_responsible']??'—') ?></td>
        <td style="font-size:12.5px;"><?= $p['target_date'] ? date('M d, Y',strtotime($p['target_date'])) : '—' ?></td>
        <td><span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:<?= $sBgs[$p['status']] ?>;color:<?= $sSubs[$p['status']] ?>;"><?= ucfirst($p['status']) ?></span></td>
        <td>
          <div class="flex-c" style="gap:5px;">
            <button class="btn btn-secondary btn-sm" onclick="editPlan(<?= $p['plan_id'] ?>)"><?= svgIcon('edit') ?></button>
            <button class="btn btn-danger btn-sm" onclick="delPlan(<?= $p['plan_id'] ?>)"><?= svgIcon('trash') ?></button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Plan Modal -->
<div class="overlay" id="mPlan">
  <div class="modal" style="max-width:640px;">
    <div class="modal-head"><span class="modal-title" id="mPlanTitle">Add Action Plan</span><button class="modal-close" onclick="closeModal('mPlan')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="p_id">
      <div class="form-row">
        <div class="fg"><label>Dimension *</label>
          <select class="fc" id="p_dim" onchange="filterIndicators()">
            <option value="">— Select —</option>
            <?php foreach($dims as $d): ?>
            <option value="<?= $d['dimension_id'] ?>">D<?= $d['dimension_no'] ?>: <?= e($d['dimension_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Indicator (optional)</label>
          <select class="fc" id="p_ind"><option value="">— Dimension-wide —</option></select>
        </div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Priority Level</label>
          <select class="fc" id="p_priority"><option value="High">High</option><option value="Medium" selected>Medium</option><option value="Low">Low</option></select>
        </div>
        <div class="fg"><label>Target Date</label><input class="fc" type="date" id="p_date"></div>
      </div>
      <div class="fg"><label>Objective *</label><textarea class="fc" id="p_objective" rows="2" placeholder="What do you want to achieve?"></textarea></div>
      <div class="fg"><label>Strategy / Action Steps *</label><textarea class="fc" id="p_strategy" rows="3" placeholder="Describe specific actions to be taken…"></textarea></div>
      <div class="form-row">
        <div class="fg"><label>Person Responsible</label><input class="fc" id="p_person" placeholder="Name / Position"></div>
        <div class="fg"><label>Resources Needed</label><input class="fc" id="p_resources" placeholder="Budget, materials, etc."></div>
      </div>
      <div class="fg"><label>Expected Output</label><input class="fc" id="p_output" placeholder="Measurable outcome or deliverable"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mPlan')">Cancel</button>
      <button class="btn btn-primary" onclick="savePlan()">Save Plan</button>
    </div>
  </div>
</div>

<script>
const ALL_INDICATORS = <?= json_encode($inds) ?>;

function filterIndicators(){
  const dimId = parseInt($('p_dim'));
  const sel = $el('p_ind');
  sel.innerHTML = '<option value="">— Dimension-wide —</option>';
  ALL_INDICATORS.filter(i => i.dimension_id == dimId).forEach(i => {
    const opt = document.createElement('option');
    opt.value = i.indicator_id;
    opt.textContent = i.indicator_code + ': ' + i.indicator_text.substring(0,60) + '…';
    sel.appendChild(opt);
  });
}

function resetPlan(){$v('p_id','');$v('p_dim','');$v('p_ind','');$v('p_priority','Medium');$v('p_date','');$v('p_objective','');$v('p_strategy','');$v('p_person','');$v('p_resources','');$v('p_output','');$el('mPlanTitle').textContent='Add Action Plan';}

async function savePlan(){
  if(!$('p_dim')||!$('p_objective')||!$('p_strategy')){toast('Fill in required fields.','warning');return;}
  const d={action:'save',plan_id:$('p_id'),dimension_id:$('p_dim'),indicator_id:$('p_ind'),priority:$('p_priority'),target_date:$('p_date'),objective:$('p_objective'),strategy:$('p_strategy'),person_responsible:$('p_person'),resources_needed:$('p_resources'),expected_output:$('p_output')};
  const r=await apiPost('improvement.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mPlan');setTimeout(()=>location.reload(),800);}
}

async function editPlan(id){
  const r=await apiPost('improvement.php',{action:'get',id});
  $v('p_id',r.plan_id);$v('p_dim',r.dimension_id);filterIndicators();
  setTimeout(()=>{$v('p_ind',r.indicator_id||'');},100);
  $v('p_priority',r.priority_level);$v('p_date',r.target_date||'');$v('p_objective',r.objective);
  $v('p_strategy',r.strategy);$v('p_person',r.person_responsible||'');$v('p_resources',r.resources_needed||'');$v('p_output',r.expected_output||'');
  $el('mPlanTitle').textContent='Edit Action Plan';
  openModal('mPlan');
}

async function delPlan(id){
  if(!confirm('Delete this action plan?')) return;
  const r=await apiPost('improvement.php',{action:'delete',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),800);
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
