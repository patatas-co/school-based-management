<?php
// ============================================================
// admin/assessment.php — REDESIGNED v3
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='validate') {
        $db->prepare("UPDATE sbm_cycles SET status='validated',validated_by=?,validated_at=NOW(),validator_remarks=? WHERE cycle_id=?")
           ->execute([$_SESSION['user_id'],trim($_POST['remarks']),(int)$_POST['cycle_id']]);
        logActivity('validate_assessment','assessment','Validated cycle ID:'.$_POST['cycle_id']);
        echo json_encode(['ok'=>true,'msg'=>'Assessment validated.']); exit;
    }
    if ($_POST['action']==='return') {
        $db->prepare("UPDATE sbm_cycles SET status='returned',validator_remarks=? WHERE cycle_id=?")
           ->execute([trim($_POST['remarks']),(int)$_POST['cycle_id']]);
        echo json_encode(['ok'=>true,'msg'=>'Assessment returned for revision.']); exit;
    }
    exit;
}

$syId  = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());
$status = $_GET['status'] ?? '';

$sql = "SELECT c.*,s.school_name,s.classification,sy.label sy_label,u.full_name validator_name
        FROM sbm_cycles c
        JOIN schools s ON c.school_id=s.school_id
        JOIN school_years sy ON c.sy_id=sy.sy_id
        LEFT JOIN users u ON c.validated_by=u.user_id
        WHERE c.sy_id=?";
$p = [$syId];
if ($status) { $sql .= " AND c.status=?"; $p[] = $status; }
$sql .= " ORDER BY c.submitted_at DESC, c.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($p); $cycles = $stmt->fetchAll();

// Status counts
$statusCounts = $db->prepare("SELECT status, COUNT(*) cnt FROM sbm_cycles WHERE sy_id=? GROUP BY status");
$statusCounts->execute([$syId]);
$counts = array_column($statusCounts->fetchAll(), 'cnt', 'status');
$totalCount = array_sum($counts);

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
$pageTitle = 'SBM Assessments'; $activePage = 'assessment.php';
include __DIR__.'/../includes/header.php';
?>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Evaluation</div>
    <div class="ph2-title">SBM Assessments</div>
    <div class="ph2-sub">Review, validate, and manage school self-assessment submissions.</div>
  </div>
  <div class="ph2-right">
    <select class="fc" onchange="location.href='assessment.php?sy='+this.value+'&status=<?= e($status) ?>'" style="width:155px;">
      <?php foreach($syears as $sy): ?>
      <option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id']==$syId?'selected':'' ?>><?= e($sy['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- Status filter tabs -->
<div class="status-tabs">
  <?php
  $statuses = [''=> 'All', 'draft'=>'Draft','in_progress'=>'In Progress','submitted'=>'Submitted','validated'=>'Validated','returned'=>'Returned'];
  $tabColors = ['submitted'=>'amber','validated'=>'active','returned'=>'returned','in_progress'=>'in_progress'];
  foreach($statuses as $sv => $sl):
    $cnt = $sv === '' ? $totalCount : ($counts[$sv] ?? 0);
  ?>
  <a href="assessment.php?sy=<?= $syId ?>&status=<?= $sv ?>"
     class="status-tab <?= $status===$sv?'active':'' ?>">
    <?= $sl ?>
    <?php if($cnt): ?><span class="status-tab-count"><?= $cnt ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Search + table -->
<div class="card">
  <div class="card-head">
    <span class="card-title">
      <?= $status ? ucfirst(str_replace('_',' ',$status)) : 'All Assessments' ?>
      <span style="font-weight:400;color:var(--n-400);font-family:var(--font-body);font-size:13px;">(<?= count($cycles) ?>)</span>
    </span>
    <div class="search">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" placeholder="Search schools…" oninput="filterTable(this.value,'tblAssessments')">
    </div>
  </div>
  <div class="tbl-wrap">
    <table id="tblAssessments" class="tbl-enhanced">
      <thead>
        <tr>
          <th>School</th>
          <th>School Year</th>
          <th>Status</th>
          <th>Score</th>
          <th>Maturity</th>
          <th>Submitted</th>
          <th>Validated By</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($cycles as $c): ?>
      <tr>
        <td>
          <div class="cell-avatar">
            <div class="cell-av" style="background:var(--brand-700);"><?= strtoupper(substr($c['school_name'],0,1)) ?></div>
            <div class="cell-av-info">
              <div class="cell-av-name"><?= e($c['school_name']) ?></div>
              <div class="cell-av-sub"><?= e($c['classification']) ?></div>
            </div>
          </div>
        </td>
        <td style="font-size:13px;"><?= e($c['sy_label']) ?></td>
        <td><span class="pill pill-<?= e($c['status']) ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
        <td>
          <?php if($c['overall_score']):
            $mat = sbmMaturityLevel(floatval($c['overall_score']));
          ?>
          <div class="score-bar-cell">
            <div class="score-bar-track"><div class="score-bar-fill" style="width:<?= $c['overall_score'] ?>%;background:<?= $mat['color'] ?>;"></div></div>
            <span class="score-val" style="font-size:14px;color:<?= $mat['color'] ?>;"><?= $c['overall_score'] ?>%</span>
          </div>
          <?php else: ?><span style="color:var(--n-300);">—</span><?php endif; ?>
        </td>
        <td>
          <?php if($c['maturity_level']): ?>
          <span class="pill pill-<?= e($c['maturity_level']) ?>"><?= e($c['maturity_level']) ?></span>
          <?php else: ?><span style="color:var(--n-300);">—</span><?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--n-500);"><?= $c['submitted_at'] ? date('M d, Y',strtotime($c['submitted_at'])) : '—' ?></td>
        <td style="font-size:12.5px;color:var(--n-600);"><?= e($c['validator_name'] ?? '—') ?></td>
        <td>
          <div class="flex-c" style="gap:5px;">
            <a href="view_assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-secondary btn-sm"><?= svgIcon('eye') ?> View</a>
            <?php if($c['status']==='submitted'): ?>
            <button class="btn btn-success btn-sm" onclick="validateCycle(<?= $c['cycle_id'] ?>,'validate')"><?= svgIcon('check') ?> Validate</button>
            <button class="btn btn-danger btn-sm"  onclick="validateCycle(<?= $c['cycle_id'] ?>,'return')"><?= svgIcon('x') ?></button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$cycles): ?>
      <tr>
        <td colspan="8">
          <div class="empty-state">
            <div class="empty-icon"><?= svgIcon('check-circle') ?></div>
            <div class="empty-title">No assessments found</div>
            <div class="empty-sub">No <?= $status ? e($status) : '' ?> assessments for this school year.</div>
          </div>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Validate/Return Modal -->
<div class="overlay" id="mValidate">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head">
      <span class="modal-title" id="mVTitle">Validate Assessment</span>
      <button class="modal-close" onclick="closeModal('mValidate')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="v_cycle_id">
      <input type="hidden" id="v_action">
      <div class="fg">
        <label>Remarks <span style="font-weight:400;color:var(--n-400);">(optional for validate, required for return)</span></label>
        <textarea class="fc" id="v_remarks" rows="4" placeholder="Enter your remarks or feedback…"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mValidate')">Cancel</button>
      <button class="btn btn-primary" id="v_submit_btn" onclick="submitValidation()">Confirm</button>
    </div>
  </div>
</div>

<script>
function validateCycle(cycleId,action){
  $v('v_cycle_id',cycleId);$v('v_action',action);$v('v_remarks','');
  const isVal=action==='validate';
  const title=$el('mVTitle');const btn=$el('v_submit_btn');
  if(title)title.textContent=isVal?'Validate Assessment':'Return for Revision';
  if(btn){btn.textContent=isVal?'Validate':'Return';btn.className='btn btn-'+(isVal?'success':'danger');}
  openModal('mValidate');
}
async function submitValidation(){
  const action=$('v_action');const remarks=$('v_remarks');
  if(action==='return'&&!remarks){toast('Please provide remarks for returning.','warning');return;}
  const r=await apiPost('assessment.php',{action,cycle_id:$('v_cycle_id'),remarks});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mValidate');setTimeout(()=>location.reload(),800);}
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>