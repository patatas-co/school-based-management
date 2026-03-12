<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo','admin');
$db = getDB();

$cycleId = (int)($_GET['cycle'] ?? 0);

// Handle validation actions
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='validate') {
        $db->prepare("UPDATE sbm_cycles SET status='validated',validated_by=?,validated_at=NOW(),validator_remarks=? WHERE cycle_id=?")
           ->execute([$_SESSION['user_id'],$_POST['remarks'],(int)$_POST['cycle_id']]);
        echo json_encode(['ok'=>true,'msg'=>'Assessment validated.']); exit;
    }
    if ($_POST['action']==='return') {
        $db->prepare("UPDATE sbm_cycles SET status='returned',validator_remarks=? WHERE cycle_id=?")
           ->execute([$_POST['remarks'],(int)$_POST['cycle_id']]);
        echo json_encode(['ok'=>true,'msg'=>'Assessment returned for revision.']); exit;
    }
}

// Get cycle details
$cycle = null;
if ($cycleId) {
    $st = $db->prepare("SELECT c.*,s.school_name,s.classification,sy.label sy_label,u.full_name validated_by_name FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id JOIN school_years sy ON c.sy_id=sy.sy_id LEFT JOIN users u ON c.validated_by=u.user_id WHERE c.cycle_id=?");
    $st->execute([$cycleId]); $cycle = $st->fetch();
}

// All submissions for review list
$divisionId = $_SESSION['division_id'] ?? null;
$listQ = "SELECT c.*,s.school_name,sy.label sy_label FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.status IN('submitted','validated','returned') AND sy.is_current=1";
$lp = [];
if ($divisionId) { $listQ .= " AND s.division_id=?"; $lp[] = $divisionId; }
$listQ .= " ORDER BY c.submitted_at DESC";
$stmt = $db->prepare($listQ); $stmt->execute($lp); $submissions = $stmt->fetchAll();

// If viewing a cycle, get all responses
$responses = [];
$dimScores = [];
if ($cycle) {
    $st = $db->prepare("SELECT r.*,i.indicator_code,i.indicator_text,d.dimension_name,d.dimension_no,d.color_hex FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE r.cycle_id=? ORDER BY d.dimension_no,i.sort_order");
    $st->execute([$cycleId]); $responses = $st->fetchAll();
    $st2 = $db->prepare("SELECT ds.*,d.dimension_name,d.dimension_no,d.color_hex FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
    $st2->execute([$cycleId]); $dimScores = $st2->fetchAll();
}

$pageTitle = 'Assessments Review'; $activePage = 'assessments.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Assessment Review</h2>
    <p>Review and validate submitted SBM self-assessments.</p></div>
</div>

<?php if (!$cycle): ?>
<!-- List view -->
<div class="card">
  <div class="card-head"><span class="card-title">Submitted Assessments <span style="font-weight:400;color:var(--n400);">(<?= count($submissions) ?>)</span></span></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>School</th><th>School Year</th><th>Submitted</th><th>Status</th><th>Score</th><th>Maturity</th><th></th></tr></thead>
      <tbody>
      <?php foreach($submissions as $s): ?>
      <tr>
        <td><strong><?= e($s['school_name']) ?></strong></td>
        <td><?= e($s['sy_label']) ?></td>
        <td style="font-size:12.5px;"><?= $s['submitted_at']?date('M d, Y',strtotime($s['submitted_at'])):'—' ?></td>
        <td><span class="pill pill-<?= e($s['status']) ?>"><?= ucfirst($s['status']) ?></span></td>
        <td><?= $s['overall_score']?'<strong style="color:var(--g600);">'.number_format($s['overall_score'],1).'%</strong>':'—' ?></td>
        <td><?= $s['maturity_level']?sbmMaturityBadge($s['maturity_level']):'—' ?></td>
        <td><a href="?cycle=<?= $s['cycle_id'] ?>" class="btn btn-secondary btn-sm"><?= svgIcon('eye') ?> Review</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$submissions): ?><tr><td colspan="7" style="text-align:center;padding:24px;color:var(--n400);">No submissions yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- Detail view -->
<div class="flex-cb mb5" style="margin-bottom:16px;">
  <a href="assessments.php" class="btn btn-secondary btn-sm"><?= svgIcon('x') ?> Back to List</a>
  <div class="flex-c" style="gap:8px;">
    <?php if($cycle['status']==='submitted'): ?>
    <button class="btn btn-danger btn-sm" onclick="openModal('mReturn')"><?= svgIcon('x') ?> Return</button>
    <button class="btn btn-primary btn-sm" onclick="openModal('mValidate')"><?= svgIcon('check') ?> Validate</button>
    <?php endif; ?>
  </div>
</div>

<div class="card mb5" style="margin-bottom:16px;">
  <div class="card-body">
    <div class="grid3">
      <div><div style="font-size:11px;color:var(--n400);font-weight:600;text-transform:uppercase;margin-bottom:3px;">School</div><strong><?= e($cycle['school_name']) ?></strong></div>
      <div><div style="font-size:11px;color:var(--n400);font-weight:600;text-transform:uppercase;margin-bottom:3px;">School Year</div><strong><?= e($cycle['sy_label']) ?></strong></div>
      <div><div style="font-size:11px;color:var(--n400);font-weight:600;text-transform:uppercase;margin-bottom:3px;">Status</div><?= sbmMaturityBadge($cycle['maturity_level']??'Beginning') ?></div>
      <div><div style="font-size:11px;color:var(--n400);font-weight:600;text-transform:uppercase;margin-bottom:3px;">Overall Score</div><strong style="font-size:18px;color:var(--g600);"><?= number_format($cycle['overall_score']??0,1) ?>%</strong></div>
      <div><div style="font-size:11px;color:var(--n400);font-weight:600;text-transform:uppercase;margin-bottom:3px;">Submitted</div><span style="font-size:13px;"><?= $cycle['submitted_at']?date('M d, Y',strtotime($cycle['submitted_at'])):'—' ?></span></div>
      <div><div style="font-size:11px;color:var(--n400);font-weight:600;text-transform:uppercase;margin-bottom:3px;">Review Status</div><span class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst($cycle['status']) ?></span></div>
    </div>
    <?php if($cycle['validator_remarks']): ?>
    <div class="alert alert-info mt4" style="margin-top:12px;"><?= svgIcon('info') ?><span><?= e($cycle['validator_remarks']) ?></span></div>
    <?php endif; ?>
  </div>
</div>

<!-- Dimension scores -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:18px;">
<?php foreach($dimScores as $ds): ?>
<div style="background:var(--white);border:1px solid var(--n200);border-radius:9px;padding:14px;border-top:3px solid <?= e($ds['color_hex']) ?>;">
  <div style="font-size:11px;font-weight:700;color:var(--n500);">Dimension <?= $ds['dimension_no'] ?></div>
  <div style="font-size:13px;font-weight:600;margin:3px 0 8px;"><?= e($ds['dimension_name']) ?></div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
    <span style="font-size:12px;color:var(--n500);"><?= $ds['raw_score'] ?>/<?= $ds['max_score'] ?></span>
    <strong style="color:<?= e($ds['color_hex']) ?>;"><?= number_format($ds['percentage'],1) ?>%</strong>
  </div>
  <div class="prog"><div class="prog-fill" style="width:<?= min(100,$ds['percentage']) ?>%;background:<?= e($ds['color_hex']) ?>;"></div></div>
</div>
<?php endforeach; ?>
</div>

<!-- Indicator responses -->
<div class="card">
  <div class="card-head"><span class="card-title">Indicator Responses (<?= count($responses) ?>)</span></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Code</th><th>Indicator</th><th>Rating</th><th>Evidence</th></tr></thead>
      <tbody>
      <?php $lastDim = ''; foreach($responses as $r):
        if ($r['dimension_name'] !== $lastDim): $lastDim = $r['dimension_name']; ?>
        <tr style="background:var(--g50);"><td colspan="4" style="font-weight:700;color:var(--g700);font-size:12px;padding:8px 14px;"><?= e($r['dimension_name']) ?></td></tr>
        <?php endif; ?>
      <tr>
        <td style="font-family:monospace;font-size:12px;color:var(--n500);white-space:nowrap;"><?= e($r['indicator_code']) ?></td>
        <td style="font-size:12.5px;max-width:400px;"><?= e($r['indicator_text']) ?></td>
        <td><?= sbmRatingBadge($r['rating']) ?></td>
        <td style="font-size:12px;color:var(--n500);max-width:250px;"><?= $r['evidence_text']?e(substr($r['evidence_text'],0,100)).'…':'—' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Validate modal -->
<div class="overlay" id="mValidate">
  <div class="modal" style="max-width:420px;">
    <div class="modal-head"><span class="modal-title">Validate Assessment</span><button class="modal-close" onclick="closeModal('mValidate')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="alert alert-success"><?= svgIcon('check-circle') ?><span>Validating this assessment confirms the school's SBM self-assessment is complete and accurate.</span></div>
      <div class="fg"><label>Remarks (optional)</label><textarea class="fc" id="val_remarks" rows="3" placeholder="Commendations, notes for the school…"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mValidate')">Cancel</button>
      <button class="btn btn-primary" onclick="doAction('validate')"><?= svgIcon('check') ?> Confirm Validate</button>
    </div>
  </div>
</div>

<!-- Return modal -->
<div class="overlay" id="mReturn">
  <div class="modal" style="max-width:420px;">
    <div class="modal-head"><span class="modal-title">Return for Revision</span><button class="modal-close" onclick="closeModal('mReturn')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="alert alert-warning"><?= svgIcon('alert-circle') ?><span>This will return the assessment to the school for revision.</span></div>
      <div class="fg"><label>Reason / Instructions *</label><textarea class="fc" id="ret_remarks" rows="3" placeholder="Explain what needs to be corrected…"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mReturn')">Cancel</button>
      <button class="btn btn-danger" onclick="doAction('return')"><?= svgIcon('x') ?> Return to School</button>
    </div>
  </div>
</div>

<script>
async function doAction(type) {
  const remarks = type==='validate' ? document.getElementById('val_remarks').value : document.getElementById('ret_remarks').value;
  if (type==='return' && !remarks.trim()) { toast('Please provide a reason.','warning'); return; }
  const r = await apiPost('assessments.php',{action:type,cycle_id:'<?= $cycleId ?>',remarks});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),800);
}
</script>
<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>
