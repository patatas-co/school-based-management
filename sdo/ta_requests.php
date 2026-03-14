<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo','admin');
$db = getDB();

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    $reqId = (int)($_POST['request_id'] ?? 0);

    if ($_POST['action']==='acknowledge') {
        $db->prepare("UPDATE ta_requests SET status='acknowledged',sdo_user_id=?,sdo_response=?,updated_at=NOW() WHERE request_id=?")
           ->execute([$_SESSION['user_id'],trim($_POST['sdo_response']),$reqId]);
        echo json_encode(['ok'=>true,'msg'=>'Request acknowledged and response sent to school.']); exit;
    }

    if ($_POST['action']==='schedule') {
        $db->prepare("UPDATE ta_requests SET status='scheduled',scheduled_date=?,agreed_actions=?,updated_at=NOW() WHERE request_id=?")
           ->execute([$_POST['scheduled_date'],trim($_POST['agreed_actions']),$reqId]);
        echo json_encode(['ok'=>true,'msg'=>'TA visit scheduled and priority actions recorded.']); exit;
    }

    if ($_POST['action']==='complete') {
        $db->prepare("UPDATE ta_requests SET status='completed',completed_date=NOW(),outcome_notes=?,updated_at=NOW() WHERE request_id=?")
           ->execute([trim($_POST['outcome_notes']),$reqId]);
        logActivity('complete_ta_request','ta_requests','Completed TA request ID:'.$reqId);
        echo json_encode(['ok'=>true,'msg'=>'TA request marked as completed.']); exit;
    }

    if ($_POST['action']==='decline') {
        $db->prepare("UPDATE ta_requests SET status='declined',sdo_user_id=?,sdo_response=?,updated_at=NOW() WHERE request_id=?")
           ->execute([$_SESSION['user_id'],trim($_POST['sdo_response']),$reqId]);
        echo json_encode(['ok'=>true,'msg'=>'Request declined. School will be notified.']); exit;
    }

    exit;
}

// ── LOAD REQUESTS ─────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT tr.*, s.school_name, s.classification,
               u_req.full_name requested_by_name,
               u_sdo.full_name sdo_name,
               cy.overall_score, cy.maturity_level,
               sy.label sy_label
        FROM ta_requests tr
        JOIN schools s      ON tr.school_id    = s.school_id
        JOIN users u_req    ON tr.requested_by = u_req.user_id
        LEFT JOIN users u_sdo ON tr.sdo_user_id = u_sdo.user_id
        LEFT JOIN sbm_cycles cy ON tr.cycle_id  = cy.cycle_id
        LEFT JOIN school_years sy ON cy.sy_id   = sy.sy_id
        WHERE 1=1";
$params = [];
if ($statusFilter) { $sql .= " AND tr.status=?"; $params[] = $statusFilter; }
$sql .= " ORDER BY FIELD(tr.status,'pending','acknowledged','scheduled','completed','declined'), tr.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $requests = $stmt->fetchAll();

// Count by status for tabs
$counts = [];
foreach (['pending','acknowledged','scheduled','completed','declined'] as $s) {
    $c = $db->prepare("SELECT COUNT(*) FROM ta_requests WHERE status=?"); $c->execute([$s]);
    $counts[$s] = (int)$c->fetchColumn();
}
$counts['all'] = array_sum($counts);

$dimensions = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
$dimMap = array_column($dimensions, null, 'dimension_id');

$pageTitle = 'TA Requests'; $activePage = 'ta_requests.php';
include __DIR__.'/../includes/header.php';
?>

<style>
.req-card {
  background:var(--white);
  border:1px solid var(--n200);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  margin-bottom:12px;
  overflow:hidden;
  transition:box-shadow var(--trans);
}
.req-card:hover { box-shadow:var(--shadow-md); }
.req-stripe { height:4px; }
.req-body { padding:16px 20px; }
.req-school { font-size:15px;font-weight:700;color:var(--n900);margin-bottom:2px; }
.req-meta   { font-size:12px;color:var(--n400); }
.req-concern { font-size:13.5px;color:var(--n700);line-height:1.65;margin:10px 0; }
.dim-tag {
  display:inline-flex;align-items:center;gap:4px;
  padding:2px 9px;border-radius:999px;
  font-size:11px;font-weight:700;
  margin:2px;
}
</style>

<div class="page-head">
  <div class="page-head-text">
    <h2>TA Requests</h2>
    <p>Schools requesting technical assistance from the SDO.</p>
  </div>
  <?php if($counts['pending'] > 0): ?>
  <div class="page-head-actions">
    <span class="pill" style="background:var(--redb);color:var(--red);border:1px solid #FECACA;font-size:13px;padding:5px 14px;">
      <?= svgIcon('alert-circle') ?> <?= $counts['pending'] ?> pending
    </span>
  </div>
  <?php endif; ?>
</div>

<!-- Status filter tabs -->
<div class="flex-c" style="gap:8px;margin-bottom:18px;flex-wrap:wrap;">
  <?php
  $tabLabels = ['all'=>'All','pending'=>'Pending','acknowledged'=>'Acknowledged','scheduled'=>'Scheduled','completed'=>'Completed','declined'=>'Declined'];
  foreach($tabLabels as $sv => $sl):
    $cnt = $counts[$sv] ?? 0;
  ?>
  <a href="ta_requests.php<?= $sv!=='all'?"?status=$sv":'' ?>"
     class="btn btn-<?= ($statusFilter===$sv || ($sv==='all'&&!$statusFilter))?'primary':'secondary' ?> btn-sm">
    <?= $sl ?> <?= $cnt>0?"($cnt)":'' ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Request cards -->
<?php if(!$requests): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:48px;color:var(--n400);">No TA requests found.</div></div>
<?php endif; ?>

<?php foreach($requests as $req):
  $statusColors = [
    'pending'      => ['#D97706','#FEF3C7'],
    'acknowledged' => ['#2563EB','#DBEAFE'],
    'scheduled'    => ['#7C3AED','#EDE9FE'],
    'completed'    => ['#16A34A','#DCFCE7'],
    'declined'     => ['#DC2626','#FEE2E2'],
  ];
  [$sc,$sb] = $statusColors[$req['status']];
  $reqDimIds = array_filter(array_map('intval', explode(',', $req['dimension_ids'])));
?>
<div class="req-card" id="req<?= $req['request_id'] ?>">
  <div class="req-stripe" style="background:<?= $sc ?>;"></div>
  <div class="req-body">
    <div class="flex-cb" style="flex-wrap:wrap;gap:10px;">
      <div>
        <div class="req-school"><?= e($req['school_name']) ?></div>
        <div class="req-meta">
          <?= e($req['classification']) ?> &nbsp;·&nbsp;
          SY <?= e($req['sy_label']??'—') ?> &nbsp;·&nbsp;
          Submitted by <?= e($req['requested_by_name']) ?> &nbsp;·&nbsp;
          <?= timeAgo($req['created_at']) ?>
          <?php if($req['overall_score']): ?> &nbsp;·&nbsp; SBM Score: <strong style="color:var(--g600);"><?= $req['overall_score'] ?>%</strong><?php endif; ?>
        </div>
      </div>
      <div class="flex-c" style="gap:8px;">
        <span style="display:inline-flex;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700;background:<?= $sb ?>;color:<?= $sc ?>;">
          <?= ucfirst($req['status']) ?>
        </span>
        <?php if($req['preferred_date']): ?>
        <span style="font-size:12px;color:var(--n500);"><?= svgIcon('calendar') ?> Preferred: <?= date('M d',strtotime($req['preferred_date'])) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dimensions requested -->
    <div style="margin-top:10px;">
      <?php foreach($reqDimIds as $did):
        $d = $dimMap[$did] ?? null;
        if(!$d) continue;
      ?>
      <span class="dim-tag" style="background:<?= e($d['color_hex']) ?>22;color:<?= e($d['color_hex']) ?>;border:1px solid <?= e($d['color_hex']) ?>44;">
        D<?= $d['dimension_no'] ?>: <?= e($d['dimension_name']) ?>
      </span>
      <?php endforeach; ?>
    </div>

    <div class="req-concern"><?= nl2br(e($req['concern'])) ?></div>

    <!-- SDO response / agreed actions -->
    <?php if($req['sdo_response']): ?>
    <div style="background:var(--g50);border:1px solid var(--g200);border-radius:8px;padding:10px 14px;margin-bottom:10px;">
      <div style="font-size:10.5px;font-weight:700;color:var(--g600);text-transform:uppercase;margin-bottom:3px;">SDO Response</div>
      <div style="font-size:13px;color:var(--n700);"><?= nl2br(e($req['sdo_response'])) ?></div>
      <?php if($req['sdo_name']): ?><div style="font-size:11.5px;color:var(--n400);margin-top:4px;">— <?= e($req['sdo_name']) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if($req['agreed_actions']): ?>
    <div style="background:var(--goldb);border:1px solid #FDE68A;border-radius:8px;padding:10px 14px;margin-bottom:10px;">
      <div style="font-size:10.5px;font-weight:700;color:var(--gold);text-transform:uppercase;margin-bottom:3px;">Agreed Priority Actions</div>
      <div style="font-size:13px;color:var(--n700);"><?= nl2br(e($req['agreed_actions'])) ?></div>
    </div>
    <?php endif; ?>

    <?php if($req['scheduled_date']): ?>
    <div style="font-size:13px;color:var(--n600);margin-bottom:8px;">
      <?= svgIcon('calendar') ?> Scheduled: <strong><?= date('F d, Y',strtotime($req['scheduled_date'])) ?></strong>
    </div>
    <?php endif; ?>

    <?php if($req['outcome_notes']): ?>
    <div style="font-size:13px;color:var(--n600);"><?= svgIcon('check-circle') ?> Outcome: <?= e($req['outcome_notes']) ?></div>
    <?php endif; ?>

    <!-- Action buttons -->
    <?php if($req['status']==='pending'): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--n100);display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn btn-primary btn-sm" onclick="openAcknowledge(<?= $req['request_id'] ?>)">
        <?= svgIcon('check') ?> Acknowledge & Respond
      </button>
      <button class="btn btn-danger btn-sm" onclick="openDecline(<?= $req['request_id'] ?>)">
        <?= svgIcon('x') ?> Decline
      </button>
    </div>
    <?php elseif($req['status']==='acknowledged'): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--n100);display:flex;gap:8px;">
      <button class="btn btn-primary btn-sm" onclick="openSchedule(<?= $req['request_id'] ?>)">
        <?= svgIcon('calendar') ?> Schedule TA Visit
      </button>
    </div>
    <?php elseif($req['status']==='scheduled'): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--n100);display:flex;gap:8px;">
      <button class="btn btn-success btn-sm" onclick="openComplete(<?= $req['request_id'] ?>)">
        <?= svgIcon('check-circle') ?> Mark Completed
      </button>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- ── MODALS ────────────────────────────────────────────────── -->

<!-- Acknowledge Modal -->
<div class="overlay" id="mAck">
  <div class="modal" style="max-width:500px;">
    <div class="modal-head"><span class="modal-title">Acknowledge & Respond</span><button class="modal-close" onclick="closeModal('mAck')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="ack_id">
      <div class="alert alert-info"><?= svgIcon('info') ?> Your response will be visible to the school head immediately.</div>
      <div class="fg">
        <label>SDO Response / Recommended Actions *</label>
        <textarea class="fc" id="ack_response" rows="5" placeholder="Describe your assessment of the school's needs and the courses of action you recommend. This will be shown to the school."></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mAck')">Cancel</button>
      <button class="btn btn-primary" onclick="submitAck()"><?= svgIcon('check') ?> Send Response</button>
    </div>
  </div>
</div>

<!-- Schedule Modal -->
<div class="overlay" id="mSchedule">
  <div class="modal" style="max-width:500px;">
    <div class="modal-head"><span class="modal-title">Schedule TA Visit</span><button class="modal-close" onclick="closeModal('mSchedule')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="sched_id">
      <div class="fg">
        <label>Scheduled Date *</label>
        <input class="fc" type="date" id="sched_date" min="<?= date('Y-m-d') ?>">
      </div>
      <div class="fg">
        <label>Agreed Priority Actions *</label>
        <textarea class="fc" id="sched_actions" rows="5"
          placeholder="Record the agreed priority improvement areas here. This becomes the official agreed actions between the school and SDO, aligned with the SBM policy requirement."></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mSchedule')">Cancel</button>
      <button class="btn btn-primary" onclick="submitSchedule()"><?= svgIcon('calendar') ?> Confirm Schedule</button>
    </div>
  </div>
</div>

<!-- Complete Modal -->
<div class="overlay" id="mComplete">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head"><span class="modal-title">Mark TA as Completed</span><button class="modal-close" onclick="closeModal('mComplete')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="comp_id">
      <div class="fg">
        <label>Outcome Notes</label>
        <textarea class="fc" id="comp_notes" rows="4" placeholder="Summarize what was accomplished during the TA visit, findings, and any follow-up actions needed."></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mComplete')">Cancel</button>
      <button class="btn btn-success" onclick="submitComplete()"><?= svgIcon('check-circle') ?> Mark Completed</button>
    </div>
  </div>
</div>

<!-- Decline Modal -->
<div class="overlay" id="mDecline">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head"><span class="modal-title">Decline Request</span><button class="modal-close" onclick="closeModal('mDecline')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="dec_id">
      <div class="alert alert-warning"><?= svgIcon('alert-circle') ?> The school will see this reason. Please be constructive.</div>
      <div class="fg">
        <label>Reason for Declining *</label>
        <textarea class="fc" id="dec_response" rows="4" placeholder="Explain why the request cannot be accommodated and suggest alternatives if possible."></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mDecline')">Cancel</button>
      <button class="btn btn-danger" onclick="submitDecline()"><?= svgIcon('x') ?> Decline Request</button>
    </div>
  </div>
</div>

<script>
function openAcknowledge(id) { $v('ack_id',id); $v('ack_response',''); openModal('mAck'); }
function openSchedule(id)    { $v('sched_id',id); $v('sched_date',''); $v('sched_actions',''); openModal('mSchedule'); }
function openComplete(id)    { $v('comp_id',id); $v('comp_notes',''); openModal('mComplete'); }
function openDecline(id)     { $v('dec_id',id); $v('dec_response',''); openModal('mDecline'); }

async function submitAck(){
  const r = await apiPost('ta_requests.php',{action:'acknowledge',request_id:$('ack_id'),sdo_response:document.getElementById('ack_response').value});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mAck');setTimeout(()=>location.reload(),800);}
}
async function submitSchedule(){
  const date = $('sched_date'), actions = document.getElementById('sched_actions').value.trim();
  if(!date||!actions){toast('Please fill in both fields.','warning');return;}
  const r = await apiPost('ta_requests.php',{action:'schedule',request_id:$('sched_id'),scheduled_date:date,agreed_actions:actions});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mSchedule');setTimeout(()=>location.reload(),800);}
}
async function submitComplete(){
  const r = await apiPost('ta_requests.php',{action:'complete',request_id:$('comp_id'),outcome_notes:document.getElementById('comp_notes').value});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mComplete');setTimeout(()=>location.reload(),800);}
}
async function submitDecline(){
  const reason = document.getElementById('dec_response').value.trim();
  if(!reason){toast('Please provide a reason.','warning');return;}
  const r = await apiPost('ta_requests.php',{action:'decline',request_id:$('dec_id'),sdo_response:reason});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mDecline');setTimeout(()=>location.reload(),800);}
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>