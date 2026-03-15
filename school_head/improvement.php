<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId,$syId]); $cycle = $cycle->fetch();

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    // ── Improvement Plan actions ──────────────────────────────
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

    // ── Auto-generate plans from weak indicators ──────────────
    if ($_POST['action']==='generate_from_weak') {
        if (!$cycle) { echo json_encode(['ok'=>false,'msg'=>'No active assessment cycle.']); exit; }
        // Get indicators rated 1 or 2 that don't already have a plan
        $weak = $db->prepare("
            SELECT r.indicator_id, r.rating, i.indicator_text, i.indicator_code,
                   i.dimension_id, d.dimension_name
            FROM sbm_responses r
            JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
            JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
            LEFT JOIN improvement_plans ip
                ON ip.indicator_id = r.indicator_id
                AND ip.cycle_id = r.cycle_id
            WHERE r.cycle_id = ?
              AND r.rating <= 2
              AND ip.plan_id IS NULL
            ORDER BY r.rating ASC, d.dimension_no ASC
        ");
        $weak->execute([$cycle['cycle_id']]); $weakRows = $weak->fetchAll();

        if (empty($weakRows)) {
            echo json_encode(['ok'=>false,'msg'=>'No weak indicators found without existing plans.']); exit;
        }

        $ratingLabels = [1=>'Not Yet Manifested',2=>'Emerging'];
        $generated = 0;
        foreach ($weakRows as $w) {
            $priority = $w['rating'] === 1 ? 'High' : 'Medium';
            $objective = "Improve performance on indicator {$w['indicator_code']}: {$w['indicator_text']}";
            $strategy  = "Develop targeted interventions to address areas rated '{$ratingLabels[$w['rating']]}'. Identify root causes, allocate resources, and monitor progress.";
            $db->prepare("INSERT INTO improvement_plans (school_id,cycle_id,dimension_id,indicator_id,priority_level,objective,strategy,created_by) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$schoolId,$cycle['cycle_id'],$w['dimension_id'],$w['indicator_id'],$priority,$objective,$strategy,$_SESSION['user_id']]);
            $generated++;
        }
        echo json_encode(['ok'=>true,'msg'=>"Generated {$generated} improvement plan(s) from weak indicators.",'count'=>$generated]); exit;
    }

    // ── TA Request actions ────────────────────────────────────
    if ($_POST['action']==='submit_ta_request') {
        if (!$cycle) { echo json_encode(['ok'=>false,'msg'=>'No active assessment cycle.']); exit; }
        $concern = trim($_POST['concern'] ?? '');
        $dimIds  = trim($_POST['dimension_ids'] ?? '');
        if (!$concern || !$dimIds) {
            echo json_encode(['ok'=>false,'msg'=>'Please describe your concern and select at least one dimension.']); exit;
        }
        // Check if there's already a pending request
        $existing = $db->prepare("SELECT request_id FROM ta_requests WHERE school_id=? AND cycle_id=? AND status IN('pending','acknowledged','scheduled')");
        $existing->execute([$schoolId,$cycle['cycle_id']]); 
        if ($existing->fetchColumn()) {
            echo json_encode(['ok'=>false,'msg'=>'You already have an active TA request. Please wait for the SDO to respond.']); exit;
        }
        $db->prepare("INSERT INTO ta_requests (school_id,cycle_id,requested_by,dimension_ids,concern,preferred_date) VALUES (?,?,?,?,?,?)")
           ->execute([$schoolId,$cycle['cycle_id'],$_SESSION['user_id'],$dimIds,$concern,$_POST['preferred_date']?:null]);
        logActivity('submit_ta_request','improvement','Submitted TA request for cycle '.$cycle['cycle_id']);
        echo json_encode(['ok'=>true,'msg'=>'TA request submitted successfully. The SDO will be notified.']); exit;
    }

    exit;
}

// ── LOAD DATA ─────────────────────────────────────────────────
$plans = $cycle ? $db->prepare("SELECT ip.*,d.dimension_name,d.color_hex,i.indicator_code,i.indicator_text FROM improvement_plans ip JOIN sbm_dimensions d ON ip.dimension_id=d.dimension_id LEFT JOIN sbm_indicators i ON ip.indicator_id=i.indicator_id WHERE ip.cycle_id=? ORDER BY FIELD(ip.priority_level,'High','Medium','Low'),ip.created_at DESC") : null;
if ($plans) { $plans->execute([$cycle['cycle_id']]); $plans = $plans->fetchAll(); } else $plans = [];

$dims = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
$inds = $db->query("SELECT i.*,d.dimension_no FROM sbm_indicators i JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id ORDER BY d.dimension_no,i.sort_order")->fetchAll();

// Weak indicators (rating ≤ 2) for the suggestion banner
$weakCount = 0;
$weakByDim = [];
if ($cycle) {
    $wq = $db->prepare("
        SELECT r.rating, i.indicator_code, i.indicator_text, i.dimension_id,
               d.dimension_name, d.color_hex,
               (SELECT COUNT(*) FROM improvement_plans ip WHERE ip.indicator_id=i.indicator_id AND ip.cycle_id=r.cycle_id) AS has_plan
        FROM sbm_responses r
        JOIN sbm_indicators i ON r.indicator_id=i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id
        WHERE r.cycle_id=? AND r.rating<=2
        ORDER BY r.rating ASC, d.dimension_no ASC
    ");
    $wq->execute([$cycle['cycle_id']]);
    foreach ($wq->fetchAll() as $w) {
        $weakByDim[$w['dimension_name']][] = $w;
        if (!$w['has_plan']) $weakCount++;
    }
}

// TA requests for this school/cycle
$taRequests = [];
if ($cycle) {
    $tq = $db->prepare("SELECT tr.*, u.full_name sdo_name FROM ta_requests tr LEFT JOIN users u ON tr.sdo_user_id=u.user_id WHERE tr.school_id=? AND tr.cycle_id=? ORDER BY tr.created_at DESC");
    $tq->execute([$schoolId,$cycle['cycle_id']]); $taRequests = $tq->fetchAll();
}
$activeTaRequest = array_filter($taRequests, fn($r) => in_array($r['status'],['pending','acknowledged','scheduled']));
$activeTaRequest = array_values($activeTaRequest)[0] ?? null;

$pageTitle = 'Improvement Plan'; $activePage = 'improvement.php';
include __DIR__.'/../includes/header.php';
?>

<style>
/* ── Weak indicator chips ── */
.weak-chip {
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:999px;
  font-size:11.5px;font-weight:600;
  border:1px solid currentColor;
  margin:2px;
}
.weak-chip.rating-1 { color:#DC2626;background:#FEE2E2; }
.weak-chip.rating-2 { color:#D97706;background:#FEF3C7; }

/* ── Weak banner header — fix icon size ── */
.weak-banner-head {
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 18px 11px;
  border-bottom:1px solid #FECACA;
  background:var(--redb);
}
.weak-banner-head-left {
  display:flex;align-items:center;gap:8px;
}
.weak-banner-head-left svg {
  width:16px;height:16px;flex-shrink:0;
  stroke:#DC2626;
}
.weak-banner-title {
  font-size:14px;font-weight:700;color:#DC2626;
}
.weak-banner-count {
  font-size:12px;color:#DC2626;opacity:.8;
}

/* ── TA Request status strip ── */
.ta-status-bar {
  display:flex;align-items:center;gap:8px;
  padding:14px 16px;
  border-radius:var(--radius);
  border:1.5px solid var(--n200);
  background:var(--white);
  margin-bottom:16px;
  flex-wrap:wrap;
}
.ta-step {
  display:flex;align-items:center;gap:6px;
  font-size:12px;font-weight:600;color:var(--n400);
  white-space:nowrap;
}
.ta-step.done   { color:var(--g600); }
.ta-step.active { color:var(--blue); }
.ta-step-dot {
  width:22px;height:22px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:800;
  background:var(--n100);color:var(--n400);
  flex-shrink:0;
}
.ta-step.done   .ta-step-dot { background:var(--g100);color:var(--g600); }
.ta-step.active .ta-step-dot { background:var(--blueb);color:var(--blue); }
.ta-connector      { flex:1;min-width:16px;height:2px;background:var(--n200);border-radius:1px; }
.ta-connector.done { background:var(--g300); }

/* ── TA card-head icon fix ── */
.card-head .ni svg { width:15px;height:15px; }

/* ── Dim checkbox labels ── */
.dim-check-label {
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;
  border:1.5px solid var(--n200);
  border-radius:7px;
  cursor:pointer;
  font-size:12.5px;
  transition:border-color .15s, background .15s;
  user-select:none;
}
.dim-check-label:hover { border-color:var(--n400); }
</style>

<div class="page-head">
  <div class="page-head-text"><h2>School Improvement Plan</h2><p>Action plans derived from your SBM self-assessment.</p></div>
  <div class="page-head-actions">
    <?php if($weakCount > 0): ?>
    <button class="btn btn-secondary" onclick="generatePlans()" id="genBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Auto-Generate (<?= $weakCount ?> gaps)
    </button>
    <?php endif; ?>
    <button class="btn btn-primary" onclick="openModal('mPlan');resetPlan()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Plan
    </button>
  </div>
</div>

<?php if(!$cycle): ?>
<div class="alert alert-warning"><?= svgIcon('alert-circle') ?> Complete your SBM self-assessment first before creating improvement plans.</div>
<?php endif; ?>

<!-- ── WEAK INDICATORS BANNER ──────────────────────────────── -->
<?php if(!empty($weakByDim)): ?>
<div class="card" style="margin-bottom:18px;border-left:4px solid var(--red);">
  <div class="weak-banner-head">
    <div class="weak-banner-head-left">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
      <span class="weak-banner-title">Indicators Needing Improvement</span>
    </div>
    <span class="weak-banner-count">
      <?= array_sum(array_map('count',$weakByDim)) ?> indicators rated 1–2
      <?= $weakCount > 0 ? "· {$weakCount} without plans yet" : ' · All covered ✓' ?>
    </span>
  </div>
  <div class="card-body" style="padding:16px 18px;">
    <?php foreach($weakByDim as $dimName => $inds): ?>
    <div style="margin-bottom:14px;">
      <div style="font-size:12px;font-weight:700;color:var(--n600);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;"><?= e($dimName) ?></div>
      <div>
        <?php foreach($inds as $ind): ?>
        <span class="weak-chip rating-<?= $ind['rating'] ?>" title="<?= e($ind['indicator_text']) ?>">
          <?= e($ind['indicator_code']) ?> — <?= $ind['rating']===1?'Not Yet':'Emerging' ?><?= $ind['has_plan'] ? ' ✓' : '' ?>
        </span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if($weakCount > 0): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid #FECACA;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <button class="btn btn-primary btn-sm" onclick="generatePlans()" id="genBtn2">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        Auto-generate <?= $weakCount ?> improvement plan(s) from these gaps
      </button>
      <span style="font-size:12px;color:var(--n500);">Plans will be pre-filled — you can edit them after.</span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php
// Load ML-generated recommendations if available
$mlRec = null;
if ($cycle) {
    $rq = $db->prepare("SELECT * FROM ml_recommendations WHERE cycle_id=?");
    $rq->execute([$cycle['cycle_id']]);
    $mlRec = $rq->fetch();
}
?>

<?php if ($mlRec): ?>
<div class="card" style="margin-bottom:18px;border-left:4px solid var(--purple);">
  <div class="card-head" style="background:var(--purpb);">
    <span class="card-title" style="color:var(--purple);display:flex;align-items:center;gap:7px;">
      <?= svgIcon('cpu') ?> AI-Generated SIP Recommendations
    </span>
    <div style="display:flex;align-items:center;gap:8px;">
      <?php if($mlRec['has_urgent']): ?>
      <span class="pill" style="background:var(--redb);color:var(--red);border:1px solid #FECACA;">
        Urgent issues flagged
      </span>
      <?php endif; ?>
      <span style="font-size:11px;color:var(--n400);">
        Generated by <?= e($mlRec['generated_by']) ?>
        · <?= timeAgo($mlRec['generated_at']) ?>
      </span>
    </div>
  </div>
  <div class="card-body" style="white-space:pre-line;font-size:13.5px;
       line-height:1.8;color:var(--n700);">
    <?= nl2br(e($mlRec['recommendation_text'])) ?>
  </div>
  <?php
    $topics = json_decode($mlRec['top_topics'] ?? '[]', true);
    if ($topics):
  ?>
  <div style="padding:12px 18px;border-top:1px solid var(--n100);
              display:flex;flex-wrap:wrap;gap:6px;">
    <span style="font-size:11.5px;color:var(--n500);margin-right:4px;">
      Key themes:
    </span>
    <?php foreach($topics as $t): ?>
    <span class="pill" style="background:var(--purpb);color:var(--purple);
          border:1px solid #DDD6FE;font-size:10.5px;">
      <?= e(str_replace('_', ' ', $t)) ?>
    </span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── IMPROVEMENT PLANS SUMMARY ───────────────────────────── -->
<?php if($plans): ?>
<?php $byPriority = ['High'=>0,'Medium'=>0,'Low'=>0,'completed'=>0];
foreach($plans as $p){ $byPriority[$p['priority_level']]++; if($p['status']==='completed') $byPriority['completed']++; } ?>
<div class="stats" style="margin-bottom:18px;">
  <div class="stat"><div class="stat-ic red"><?= svgIcon('alert-circle') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['High'] ?></div><div class="stat-lbl">High Priority</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('star') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['Medium'] ?></div><div class="stat-lbl">Medium Priority</div></div></div>
  <div class="stat"><div class="stat-ic blue"><?= svgIcon('info') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['Low'] ?></div><div class="stat-lbl">Low Priority</div></div></div>
  <div class="stat"><div class="stat-ic green"><?= svgIcon('check') ?></div><div class="stat-data"><div class="stat-val"><?= $byPriority['completed'] ?></div><div class="stat-lbl">Completed</div></div></div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:22px;">
  <div class="card-head"><span class="card-title">Action Plans (<?= count($plans) ?>)</span></div>
  <?php if(!$plans): ?>
  <div class="card-body" style="text-align:center;padding:48px 40px;color:var(--n400);">
    No action plans yet.
    <?php if($weakCount > 0): ?>
    <br>
    <button class="btn btn-primary btn-sm" style="margin-top:14px;" onclick="generatePlans()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;flex-shrink:0;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Auto-generate from weak indicators
    </button>
    <?php else: ?>
    <br><span style="font-size:13px;">Add plans to address areas for improvement.</span>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table id="tblPlans">
      <thead><tr><th>Priority</th><th>Dimension</th><th>Indicator</th><th>Objective</th><th>Person</th><th>Target</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($plans as $p):
        $pColors=['High'=>'var(--red)','Medium'=>'var(--gold)','Low'=>'var(--blue)'];
        $sBgs=['planned'=>'var(--n100)','ongoing'=>'var(--blueb)','completed'=>'var(--g100)','cancelled'=>'var(--redb)'];
        $sSubs=['planned'=>'var(--n500)','ongoing'=>'var(--blue)','completed'=>'var(--g700)','cancelled'=>'var(--red)'];
      ?>
      <tr>
        <td><span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $pColors[$p['priority_level']] ?>22;color:<?= $pColors[$p['priority_level']] ?>;"><?= e($p['priority_level']) ?></span></td>
        <td style="font-size:12.5px;font-weight:600;color:<?= e($p['color_hex']) ?>;"><?= e($p['dimension_name']) ?></td>
        <td style="font-size:11.5px;color:var(--n500);"><?= e($p['indicator_code'] ?? '—') ?></td>
        <td style="font-size:13px;max-width:200px;"><?= e(substr($p['objective'],0,70)) ?><?= strlen($p['objective'])>70?'…':'' ?></td>
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

<!-- ════════════════════════════════════════════════════════════
     TA REQUEST SECTION
════════════════════════════════════════════════════════════ -->
<div style="margin-top:28px;">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <div style="width:3px;height:22px;background:var(--blue);border-radius:2px;"></div>
    <h3 style="font-size:17px;font-weight:700;color:var(--n900);">Technical Assistance Request</h3>
  </div>
  <p style="font-size:13.5px;color:var(--n500);margin-bottom:18px;">
    Request coaching, mentoring, or monitoring support from the Schools Division Office. The SDO will review your SBM status and respond with recommended actions.
  </p>

  <?php if($activeTaRequest): ?>
  <!-- Active request tracker -->
  <?php
    $steps = ['pending'=>0,'acknowledged'=>1,'scheduled'=>2,'completed'=>3];
    $curStep = $steps[$activeTaRequest['status']] ?? 0;
    $stepLabels = ['Submitted','SDO Acknowledged','TA Scheduled','Completed'];
  ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-head" style="background:var(--blueb);">
      <span class="card-title" style="color:var(--blue);display:flex;align-items:center;gap:7px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Active TA Request
      </span>
      <span class="pill" style="background:var(--white);color:var(--blue);border:1px solid var(--blue);">
        <?= ucfirst($activeTaRequest['status']) ?>
      </span>
    </div>
    <div class="card-body">
      <!-- Step tracker -->
      <div class="ta-status-bar" style="margin-bottom:18px;">
        <?php foreach($stepLabels as $i => $label): ?>
        <?php $state = $i < $curStep ? 'done' : ($i === $curStep ? 'active' : ''); ?>
        <div class="ta-step <?= $state ?>">
          <div class="ta-step-dot"><?= $i < $curStep ? '✓' : ($i+1) ?></div>
          <span><?= $label ?></span>
        </div>
        <?php if($i < count($stepLabels)-1): ?>
        <div class="ta-connector <?= $i < $curStep ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Request details -->
      <div class="grid2" style="gap:14px;margin-bottom:14px;">
        <div>
          <div style="font-size:11px;font-weight:700;color:var(--n400);text-transform:uppercase;margin-bottom:4px;">Your Concern</div>
          <div style="font-size:13.5px;color:var(--n800);line-height:1.6;"><?= nl2br(e($activeTaRequest['concern'])) ?></div>
          <?php if($activeTaRequest['preferred_date']): ?>
          <div style="font-size:12px;color:var(--n400);margin-top:6px;">Preferred date: <?= date('M d, Y',strtotime($activeTaRequest['preferred_date'])) ?></div>
          <?php endif; ?>
        </div>
        <?php if($activeTaRequest['sdo_response']): ?>
        <div style="background:var(--g50);border-radius:8px;padding:12px 14px;border:1px solid var(--g200);">
          <div style="font-size:11px;font-weight:700;color:var(--g600);text-transform:uppercase;margin-bottom:4px;">SDO Response</div>
          <div style="font-size:13.5px;color:var(--n800);line-height:1.6;"><?= nl2br(e($activeTaRequest['sdo_response'])) ?></div>
          <?php if($activeTaRequest['sdo_name']): ?>
          <div style="font-size:11.5px;color:var(--n500);margin-top:6px;">— <?= e($activeTaRequest['sdo_name']) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if($activeTaRequest['agreed_actions']): ?>
      <div style="background:var(--goldb);border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;">
        <div style="font-size:11px;font-weight:700;color:var(--gold);text-transform:uppercase;margin-bottom:4px;">Agreed Priority Actions</div>
        <div style="font-size:13.5px;color:var(--n800);line-height:1.6;"><?= nl2br(e($activeTaRequest['agreed_actions'])) ?></div>
      </div>
      <?php endif; ?>

      <?php if($activeTaRequest['scheduled_date']): ?>
      <div style="margin-top:12px;font-size:13px;color:var(--n600);display:flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        TA scheduled for: <strong><?= date('F d, Y',strtotime($activeTaRequest['scheduled_date'])) ?></strong>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <!-- TA request form -->
  <div class="card">
  <div class="card-head"><span class="card-title" style="display:flex;align-items:center;gap:7px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    Submit a TA Request
  </span></div>
    <div class="card-body">
      <?php if(!$cycle): ?>
      <div class="alert alert-warning"><?= svgIcon('alert-circle') ?> Complete your self-assessment first before requesting TA.</div>
      <?php else: ?>
      <div class="form-row" style="margin-bottom:14px;">
        <div class="fg">
          <label>Dimensions Needing Support * <span style="font-weight:400;color:var(--n400);">(select all that apply)</span></label>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;" id="dimCheckboxes">
            <?php foreach($dims as $d): ?>
            <label class="dim-check-label">
              <input type="checkbox" class="dim-checkbox" value="<?= $d['dimension_id'] ?>"
                     onchange="updateDimIds()" style="accent-color:<?= e($d['color_hex']) ?>">
              <span style="color:<?= e($d['color_hex']) ?>;font-weight:700;">D<?= $d['dimension_no'] ?></span>
              <span style="color:var(--n700);"><?= e($d['dimension_name']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="ta_dim_ids">
        </div>
      </div>
      <div class="fg">
        <label>Describe Your Concern / Needs *</label>
        <textarea class="fc" id="ta_concern" rows="4"
          placeholder="Describe the specific challenges your school faces with SBM implementation. What kind of support do you need from the SDO?"></textarea>
      </div>
      <div class="fg" style="max-width:260px;">
        <label>Preferred TA Date <span style="font-weight:400;color:var(--n400);">(optional)</span></label>
        <input class="fc" type="date" id="ta_date" min="<?= date('Y-m-d') ?>">
      </div>
      <div style="margin-top:6px;">
        <button class="btn btn-primary" onclick="submitTaRequest()" id="taSubmitBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          Submit TA Request to SDO
        </button>
        <span style="font-size:12px;color:var(--n400);margin-left:12px;">The SDO will be notified and will respond to your request.</span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Past TA Requests -->
  <?php $pastRequests = array_filter($taRequests, fn($r) => in_array($r['status'],['completed','declined'])); ?>
  <?php if($pastRequests): ?>
  <div class="card" style="margin-top:14px;">
    <div class="card-head"><span class="card-title">Past TA Requests</span></div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Submitted</th><th>Concern</th><th>Status</th><th>SDO</th><th>Completed</th></tr></thead>
        <tbody>
        <?php foreach($pastRequests as $req): ?>
        <tr>
          <td style="font-size:12px;"><?= date('M d, Y',strtotime($req['created_at'])) ?></td>
          <td style="font-size:12.5px;max-width:260px;"><?= e(substr($req['concern'],0,80)) ?>…</td>
          <td><span class="pill pill-<?= $req['status']==='completed'?'validated':'returned' ?>"><?= ucfirst($req['status']) ?></span></td>
          <td style="font-size:12.5px;"><?= e($req['sdo_name']??'—') ?></td>
          <td style="font-size:12px;"><?= $req['completed_date'] ? date('M d, Y',strtotime($req['completed_date'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════ -->
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

function resetPlan(){
  $v('p_id','');$v('p_dim','');$v('p_ind','');$v('p_priority','Medium');
  $v('p_date','');$v('p_objective','');$v('p_strategy','');
  $v('p_person','');$v('p_resources','');$v('p_output','');
  $el('mPlanTitle').textContent='Add Action Plan';
}

async function savePlan(){
  if(!$('p_dim')||!$('p_objective')||!$('p_strategy')){toast('Fill in required fields.','warning');return;}
  const d={action:'save',plan_id:$('p_id'),dimension_id:$('p_dim'),indicator_id:$('p_ind'),
    priority:$('p_priority'),target_date:$('p_date'),objective:$('p_objective'),
    strategy:$('p_strategy'),person_responsible:$('p_person'),
    resources_needed:$('p_resources'),expected_output:$('p_output')};
  const r=await apiPost('improvement.php',d);
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok){closeModal('mPlan');setTimeout(()=>location.reload(),800);}
}

async function editPlan(id){
  const r=await apiPost('improvement.php',{action:'get',id});
  $v('p_id',r.plan_id);$v('p_dim',r.dimension_id);filterIndicators();
  setTimeout(()=>{$v('p_ind',r.indicator_id||'');},100);
  $v('p_priority',r.priority_level);$v('p_date',r.target_date||'');
  $v('p_objective',r.objective);$v('p_strategy',r.strategy);
  $v('p_person',r.person_responsible||'');$v('p_resources',r.resources_needed||'');
  $v('p_output',r.expected_output||'');
  $el('mPlanTitle').textContent='Edit Action Plan';
  openModal('mPlan');
}

async function delPlan(id){
  if(!confirm('Delete this action plan?')) return;
  const r=await apiPost('improvement.php',{action:'delete',id});
  toast(r.msg,r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),800);
}

// ── Auto-generate plans ──────────────────────────────────────
async function generatePlans(){
  const btns = document.querySelectorAll('#genBtn,#genBtn2');
  btns.forEach(b=>{ b.disabled=true; b.textContent='Generating…'; });
  const r = await apiPost('improvement.php',{action:'generate_from_weak'});
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),900);
  else btns.forEach(b=>{ b.disabled=false; b.textContent='⚡ Auto-Generate'; });
}

// ── TA Request ───────────────────────────────────────────────
function updateDimIds(){
  const checked = [...document.querySelectorAll('.dim-checkbox:checked')].map(c=>c.value);
  $v('ta_dim_ids', checked.join(','));
  // Highlight checked labels
  document.querySelectorAll('.dim-check-label').forEach(label => {
    const cb = label.querySelector('input');
    label.style.borderColor  = cb.checked ? 'var(--blue)'  : 'var(--n200)';
    label.style.background   = cb.checked ? 'var(--blueb)' : '';
  });
}

async function submitTaRequest(){
  const dimIds  = $('ta_dim_ids');
  const concern = document.getElementById('ta_concern').value.trim();
  if(!dimIds)  { toast('Please select at least one dimension.','warning'); return; }
  if(!concern) { toast('Please describe your concern.','warning'); return; }

  const btn = $el('taSubmitBtn');
  btn.disabled=true; btn.textContent='Submitting…';

  const r = await apiPost('improvement.php',{
    action:'submit_ta_request',
    dimension_ids: dimIds,
    concern: concern,
    preferred_date: $('ta_date'),
  });
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),900);
  else { btn.disabled=false; btn.textContent='⚡ Submit TA Request to SDO'; }
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>