<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

$cycleId = (int)($_GET['id'] ?? 0);
if (!$cycleId) { header('Location: assessment.php'); exit; }

// Load cycle + school + SY
$cycle = $db->prepare("SELECT c.*,s.school_name,s.classification,s.school_head_name,s.address,sy.label sy_label,u.full_name validator_name FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id JOIN school_years sy ON c.sy_id=sy.sy_id LEFT JOIN users u ON c.validated_by=u.user_id WHERE c.cycle_id=?");
$cycle->execute([$cycleId]); $cycle = $cycle->fetch();
if (!$cycle) { echo '<div class="alert alert-danger">Assessment not found.</div>'; include __DIR__.'/../includes/footer.php'; exit; }

// Dimension scores
$dimScores = $db->prepare("SELECT ds.*,d.dimension_no,d.dimension_name,d.color_hex FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
$dimScores->execute([$cycleId]); $dimScores = $dimScores->fetchAll();

// All responses grouped by dimension
$resp = $db->prepare("SELECT r.*,i.indicator_code,i.indicator_text,i.mov_guide,d.dimension_no,d.dimension_name,d.color_hex FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE r.cycle_id=? ORDER BY d.dimension_no,i.sort_order");
$resp->execute([$cycleId]); $responses = $resp->fetchAll();
$grouped = [];
foreach ($responses as $r) $grouped[$r['dimension_no']][] = $r;

$ratingLabels = [1=>'Not Yet Manifested',2=>'Emerging',3=>'Developing',4=>'Always Manifested'];
$ratingColors = [1=>'#DC2626',2=>'#D97706',3=>'#2563EB',4=>'#16A34A'];

// Handle validate/return POST
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='validate') {
    $targetCycleId = (int)($_POST['cycle_id'] ?? $cycleId);
    if ($targetCycleId !== $cycleId) { echo json_encode(['ok'=>false,'msg'=>'Cycle mismatch.']); exit; }
    $db->prepare("UPDATE sbm_cycles SET status='validated',validated_by=?,validated_at=NOW(),validator_remarks=? WHERE cycle_id=?")
       ->execute([$_SESSION['user_id'],trim($_POST['remarks']),$cycleId]);
        logActivity('validate_assessment','view_assessment','Validated cycle ID:'.$cycleId);
        echo json_encode(['ok'=>true,'msg'=>'Assessment validated successfully.']); exit;
    }
    if ($_POST['action']==='return') {
        if (!trim($_POST['remarks'])) { echo json_encode(['ok'=>false,'msg'=>'Remarks are required when returning.']); exit; }
        $db->prepare("UPDATE sbm_cycles SET status='returned',validator_remarks=? WHERE cycle_id=?")
           ->execute([trim($_POST['remarks']),$cycleId]);
        echo json_encode(['ok'=>true,'msg'=>'Assessment returned for revision.']); exit;
    }
    exit;
}

$pageTitle = 'View Assessment — '.$cycle['school_name'];
$activePage = 'assessment.php';
include __DIR__.'/../includes/header.php';
?>

<style>
.dim-header {
  display:flex;align-items:center;gap:10px;
  padding:13px 18px;
  background:var(--white);
  border:1px solid var(--n200);
  border-radius:var(--radius);
  cursor:pointer;user-select:none;
  transition:background .15s;
  box-shadow:var(--shadow);
}
.dim-header:hover { background:var(--n50); }
.dim-chevron { font-size:18px;color:var(--n300);transition:transform .25s;flex-shrink:0;margin-left:4px; }
.dim-body { padding-top:6px;margin-bottom:16px; }
.dim-body.collapsed { display:none; }
.dim-wrap { margin-bottom:6px; }
.rating-pill {
  display:inline-flex;padding:3px 10px;border-radius:999px;
  font-size:11px;font-weight:700;white-space:nowrap;
}
</style>

<div class="page-head">
  <div class="page-head-text">
    <h2><?= e($cycle['school_name']) ?></h2>
    <p><?= e($cycle['classification']) ?> &nbsp;·&nbsp; <?= e($cycle['sy_label']) ?> &nbsp;·&nbsp;
      <span class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst(str_replace('_',' ',$cycle['status'])) ?></span>
    </p>
  </div>
  <div class="page-head-actions" style="gap:8px;">
    <a href="assessment.php" class="btn btn-secondary"><?= svgIcon('arrow-left') ?> Back</a>
    <a href="<?= baseUrl() ?>/export_pdf.php?cycle_id=<?= $cycle['cycle_id'] ?>&type=improvement"
   target="_blank" class="btn btn-secondary">
  Download Improvement Plan (PDF)
</a>
    <?php if($cycle['status']==='submitted'): ?>
    <button class="btn btn-success btn-sm" onclick="openAction('validate')"><?= svgIcon('check') ?> Validate</button>
    <button class="btn btn-danger btn-sm" onclick="openAction('return')"><?= svgIcon('x') ?> Return</button>
    <?php endif; ?>
  </div>
</div>

<?php if($cycle['validator_remarks']): ?>
<div class="alert alert-<?= $cycle['status']==='returned'?'warning':'info' ?>" style="margin-bottom:16px;">
  <?= svgIcon('alert-circle') ?> <div><strong>Remarks:</strong> <?= e($cycle['validator_remarks']) ?></div>
</div>
<?php endif; ?>

<!-- School summary header -->
<div style="text-align:center;margin-bottom:20px;padding:20px;background:var(--white);border:1px solid var(--n200);border-radius:var(--radius);">
  <p style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--n500);margin-bottom:4px;">Republic of the Philippines · Department of Education</p>
  <h2 style="font-size:19px;font-weight:700;color:var(--n900);margin-bottom:2px;">SBM Self-Assessment Report — Annex A</h2>
  <p style="font-size:13px;color:var(--n500);margin-bottom:16px;"><?= e($cycle['sy_label']) ?></p>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-width:560px;margin:0 auto;text-align:left;">
    <div style="background:var(--n50);border-radius:8px;padding:10px 14px;">
      <div style="font-size:10.5px;color:var(--n400);text-transform:uppercase;letter-spacing:.06em;">School</div>
      <div style="font-size:13px;font-weight:700;color:var(--n900);margin-top:2px;"><?= e($cycle['school_name']) ?></div>
    </div>
    <div style="background:var(--n50);border-radius:8px;padding:10px 14px;">
      <div style="font-size:10.5px;color:var(--n400);text-transform:uppercase;letter-spacing:.06em;">School Head</div>
      <div style="font-size:13px;font-weight:700;color:var(--n900);margin-top:2px;"><?= e($cycle['school_head_name'] ?? '—') ?></div>
    </div>
    <?php $mat = $cycle['overall_score'] ? sbmMaturityLevel(floatval($cycle['overall_score'])) : null; ?>
    <div style="background:<?= $mat ? $mat['bg'] : 'var(--n50)' ?>;border-radius:8px;padding:10px 14px;">
      <div style="font-size:10.5px;color:var(--n400);text-transform:uppercase;letter-spacing:.06em;">Overall Score</div>
      <div style="font-size:20px;font-weight:800;color:<?= $mat ? $mat['color'] : 'var(--n900)' ?>;margin-top:2px;"><?= $cycle['overall_score'] ? $cycle['overall_score'].'%' : '—' ?></div>
      <?php if($mat): ?><div style="font-size:11px;font-weight:600;color:<?= $mat['color'] ?>;"><?= e($cycle['maturity_level']) ?></div><?php endif; ?>
    </div>
  </div>
</div>

<!-- Dimension scores + radar -->
<div class="grid2" style="margin-bottom:20px;">
  <div class="card">
    <div class="card-head"><span class="card-title">Dimension Summary</span></div>
    <div class="card-body"><canvas id="dimRadar" height="220"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><span class="card-title">Scores by Dimension</span></div>
    <div class="card-body" style="padding:0;">
      <?php foreach($dimScores as $ds): ?>
      <?php $m = sbmMaturityLevel(floatval($ds['percentage'])); ?>
      <div style="padding:11px 18px;border-bottom:1px solid var(--n100);">
        <div class="flex-cb" style="margin-bottom:4px;">
          <span style="font-size:13px;font-weight:600;color:<?= e($ds['color_hex']) ?>;">D<?= $ds['dimension_no'] ?> — <?= e($ds['dimension_name']) ?></span>
          <span style="font-size:13px;font-weight:700;color:<?= $m['color'] ?>;"><?= $ds['percentage'] ?>%</span>
        </div>
        <div class="prog"><div class="prog-fill" style="width:<?= $ds['percentage'] ?>%;background:<?= $ds['color_hex'] ?>;"></div></div>
        <div style="font-size:11px;color:var(--n400);margin-top:3px;"><?= $m['label'] ?> · <?= $ds['raw_score'] ?>/<?= $ds['max_score'] ?> pts</div>
      </div>
      <?php endforeach; ?>
      <?php if(!$dimScores): ?><p style="text-align:center;color:var(--n400);padding:20px;font-size:13px;">No scores computed yet.</p><?php endif; ?>
    </div>
  </div>
</div>

<!-- Collapsible dimension indicator tables -->
<?php foreach($grouped as $dimNo => $inds): ?>
<?php $first = $inds[0]; $dsDim = array_values(array_filter($dimScores, fn($d)=>$d['dimension_no']==$dimNo))[0] ?? null; ?>
<div class="dim-wrap" id="dim<?= $dimNo ?>">
  <div class="dim-header" onclick="toggleDim(<?= $dimNo ?>)" style="border-left:4px solid <?= e($first['color_hex']) ?>;">
    <div style="width:36px;height:36px;border-radius:8px;background:<?= e($first['color_hex']) ?>22;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:<?= e($first['color_hex']) ?>;flex-shrink:0;"><?= $dimNo ?></div>
    <div style="flex:1;">
      <div style="font-size:14px;font-weight:700;color:var(--n900);">Dimension <?= $dimNo ?>: <?= e($first['dimension_name']) ?></div>
      <div style="font-size:12px;color:var(--n400);margin-top:2px;"><?= count($inds) ?> indicators</div>
    </div>
    <?php if($dsDim): ?>
    <?php $dm = sbmMaturityLevel(floatval($dsDim['percentage'])); ?>
    <span style="font-size:13px;font-weight:700;color:<?= $dm['color'] ?>;margin-right:8px;"><?= $dsDim['percentage'] ?>%</span>
    <span style="font-size:11px;font-weight:700;background:<?= $dm['bg'] ?>;color:<?= $dm['color'] ?>;border-radius:999px;padding:3px 10px;flex-shrink:0;"><?= $dm['label'] ?></span>
    <?php endif; ?>
    <span class="dim-chevron" id="dimChevron<?= $dimNo ?>">▾</span>
  </div>
  <div class="dim-body collapsed" id="dimBody<?= $dimNo ?>">
    <div class="tbl-wrap" style="border:1px solid var(--n200);border-radius:var(--radius);overflow:hidden;">
      <table>
        <thead><tr><th style="width:80px;">Code</th><th>Indicator</th><th style="width:180px;">Rating</th><th>Evidence / MOV</th></tr></thead>
        <tbody>
        <?php foreach($inds as $ind): ?>
        <tr>
          <td style="font-weight:700;font-size:12px;color:var(--n600);"><?= e($ind['indicator_code']) ?></td>
          <td style="font-size:12.5px;line-height:1.5;"><?= e($ind['indicator_text']) ?></td>
          <td>
            <span class="rating-pill" style="background:<?= $ratingColors[$ind['rating']] ?>22;color:<?= $ratingColors[$ind['rating']] ?>;">
              <?= $ind['rating'] ?> — <?= $ratingLabels[$ind['rating']] ?>
            </span>
          </td>
          <td style="font-size:12px;color:var(--n600);"><?= e($ind['evidence_text'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if($cycle['status']==='submitted'): ?>
<div style="text-align:center;padding:24px 0;display:flex;gap:12px;justify-content:center;">
  <button class="btn btn-success" style="padding:10px 28px;" onclick="openAction('validate')"><?= svgIcon('check') ?> Validate Assessment</button>
  <button class="btn btn-danger"  style="padding:10px 28px;" onclick="openAction('return')"><?= svgIcon('x') ?> Return for Revision</button>
</div>
<?php endif; ?>

<!-- Validate/Return Modal -->
<div class="overlay" id="mAction">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head">
      <span class="modal-title" id="mActionTitle">Validate Assessment</span>
      <button class="modal-close" onclick="closeModal('mAction')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="a_action">
      <div class="fg">
        <label>Remarks <span style="font-weight:400;color:var(--n400);">(optional for validate, required for return)</span></label>
        <textarea class="fc" id="a_remarks" rows="4" placeholder="Enter your remarks or feedback…"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mAction')">Cancel</button>
      <button class="btn btn-primary" id="a_submit_btn" onclick="submitAction()">Confirm</button>
    </div>
  </div>
</div>

<script>
// Collapsible dimensions
function toggleDim(n){
  const body    = document.getElementById('dimBody'+n);
  const chevron = document.getElementById('dimChevron'+n);
  const isOpen  = !body.classList.contains('collapsed');
  body.classList.toggle('collapsed', isOpen);
  chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
}

// Set all sections to collapsed on load for cleaner initial view
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.dim-body').forEach(body => {
    body.classList.add('collapsed');
  });
  document.querySelectorAll('.dim-chevron').forEach(chevron => {
    chevron.style.transform = 'rotate(-90deg)';
  });
});

// Validate/Return modal
function openAction(action){
  document.getElementById('a_action').value = action;
  document.getElementById('a_remarks').value = '';
  const isVal = action === 'validate';
  document.getElementById('mActionTitle').textContent = isVal ? 'Validate Assessment' : 'Return for Revision';
  const btn = document.getElementById('a_submit_btn');
  btn.textContent = isVal ? 'Validate' : 'Return';
  btn.className = 'btn btn-' + (isVal ? 'success' : 'danger');
  openModal('mAction');
}

async function submitAction(){
  const action  = document.getElementById('a_action').value;
  const remarks = document.getElementById('a_remarks').value.trim();
  if(action==='return' && !remarks){ toast('Please provide remarks for returning.','warning'); return; }
  const r = await apiPost('view_assessment.php?id=<?= $cycleId ?>',{action,cycle_id:<?= $cycleId ?>,remarks});
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok){ closeModal('mAction'); setTimeout(()=>location.reload(),900); }
}

// Radar chart
const dimLabels = <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'],$dimScores)) ?>;
const dimValues = <?= json_encode(array_map(fn($d)=>floatval($d['percentage']),$dimScores)) ?>;
const dimColors = <?= json_encode(array_column($dimScores,'color_hex')) ?>;
if(dimValues.length){
  new Chart(document.getElementById('dimRadar'),{
    type:'radar',
    data:{labels:dimLabels,datasets:[{label:'Score %',data:dimValues,backgroundColor:'rgba(22,163,74,.15)',borderColor:'#16A34A',pointBackgroundColor:dimColors,pointRadius:4,borderWidth:2}]},
    options:{scales:{r:{min:0,max:100,ticks:{font:{size:10}}}},plugins:{legend:{display:false}}}
  });
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>