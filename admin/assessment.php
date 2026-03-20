<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

// Handle validate/return actions
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

$sql = "SELECT c.*,s.school_name,s.classification,sy.label sy_label,
               u.full_name validator_name
        FROM sbm_cycles c
        JOIN schools s ON c.school_id=s.school_id
        JOIN school_years sy ON c.sy_id=sy.sy_id
        LEFT JOIN users u ON c.validated_by=u.user_id
        WHERE c.sy_id=?";
$p = [$syId];
if ($status) { $sql .= " AND c.status=?"; $p[] = $status; }
$sql .= " ORDER BY c.submitted_at DESC, c.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($p); $cycles = $stmt->fetchAll();

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
$pageTitle = 'SBM Assessments'; $activePage = 'assessment.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>SBM Assessments</h2><p>Review and validate school self-assessment submissions.</p></div>
  <div class="page-head-actions">
    <select class="fc" id="syFilter" onchange="location.href='assessment.php?sy='+this.value" style="width:150px;">
      <?php foreach($syears as $sy): ?>
      <option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id']==$syId?'selected':'' ?>><?= e($sy['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- Status filter tabs -->
<div class="flex-c" style="gap:8px;margin-bottom:18px;flex-wrap:wrap;">
  <?php
  $statuses = [''=> 'All', 'draft'=>'Draft','in_progress'=>'In Progress','submitted'=>'Submitted','validated'=>'Validated','returned'=>'Returned'];
  foreach($statuses as $sv => $sl):
  ?>
  <a href="assessment.php?sy=<?= $syId ?>&status=<?= $sv ?>" class="btn btn-<?= $status===$sv?'primary':'secondary' ?> btn-sm"><?= $sl ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">Assessment Submissions (<?= count($cycles) ?>)</span></div>
  <div class="tbl-wrap">
    <table id="tblAssessments">
      <thead><tr><th>School</th><th>SY</th><th>Status</th><th>Overall Score</th><th>Maturity Level</th><th>Submitted</th><th>Validated By</th><th></th></tr></thead>
      <tbody>
      <?php foreach($cycles as $c): ?>
      <tr>
        <td>
          <strong style="font-size:13px;"><?= e($c['school_name']) ?></strong>
          <div style="font-size:11.5px;color:var(--n400);"><?= e($c['classification']) ?></div>
        </td>
        <td style="font-size:13px;"><?= e($c['sy_label']) ?></td>
        <td><span class="pill pill-<?= e($c['status']) ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
        <td style="font-weight:700;font-size:14px;color:var(--g700);"><?= $c['overall_score'] ? $c['overall_score'].'%' : '—' ?></td>
        <td><?php if($c['maturity_level']): ?><span class="pill pill-<?= e($c['maturity_level']) ?>"><?= e($c['maturity_level']) ?></span><?php else: ?>—<?php endif; ?></td>
        <td style="font-size:12px;color:var(--n500);"><?= $c['submitted_at'] ? date('M d, Y',strtotime($c['submitted_at'])) : '—' ?></td>
        <td style="font-size:12.5px;"><?= e($c['validator_name'] ?? '—') ?></td>
        <td>
          <div class="flex-c" style="gap:5px;">
            <a href="view_assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-secondary btn-sm"><?= svgIcon('eye') ?> View</a>
            <?php if(in_array($c['status'],['submitted'])): ?>
            <button class="btn btn-success btn-sm" onclick="validateCycle(<?= $c['cycle_id'] ?>,'validate')"><?= svgIcon('check') ?> Validate</button>
            <button class="btn btn-danger btn-sm" onclick="validateCycle(<?= $c['cycle_id'] ?>,'return')"><?= svgIcon('x') ?> Return</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$cycles): ?><tr><td colspan="8" style="text-align:center;color:var(--n400);padding:24px;">No assessments for this filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Validate/Return Modal -->
<div class="overlay" id="mValidate">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head"><span class="modal-title" id="mVTitle">Validate Assessment</span><button class="modal-close" onclick="closeModal('mValidate')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="v_cycle_id">
      <input type="hidden" id="v_action">
      <div class="fg">
        <label>Remarks <span style="font-weight:400;color:var(--n400);">(optional for validate, required for return)</span></label>
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
function validateCycle(cycleId, action){
  $v('v_cycle_id', cycleId);
  $v('v_action', action);
  $v('v_remarks', '');
  const isValidate = action === 'validate';
  const title = $el('mVTitle');
  const btn   = $el('v_submit_btn');
  if (title) title.textContent = isValidate ? 'Validate Assessment' : 'Return for Revision';
  if (btn) {
    btn.textContent = isValidate ? 'Validate' : 'Return';
    btn.className   = 'btn btn-' + (isValidate ? 'success' : 'danger');
  }
  openModal('mValidate');
}
async function submitValidation(){
  const action = $('v_action');
  const remarks = $('v_remarks');
  if(action === 'return' && !remarks){ toast('Please provide remarks for returning.','warning'); return; }
  const r = await apiPost('assessment.php', {action, cycle_id:$('v_cycle_id'), remarks});
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok){ closeModal('mValidate'); setTimeout(()=>location.reload(),800); }
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
