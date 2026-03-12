<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if (!$schoolId || !$syId) {
    echo '<div class="alert alert-danger">No school or school year configured. Contact the administrator.</div>';
    include __DIR__.'/../includes/footer.php'; exit;
}

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    if ($_POST['action'] === 'save_response') {
        $indicatorId = (int)$_POST['indicator_id'];
        $rating = (int)$_POST['rating'];
        $evidence = trim($_POST['evidence'] ?? '');
        if ($rating < 1 || $rating > 4) { echo json_encode(['ok'=>false,'msg'=>'Invalid rating.']); exit; }

        // Get or create cycle
        $cycle = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycle->execute([$schoolId,$syId]); $cycleRow = $cycle->fetch();
        if (!$cycleRow) {
            $db->prepare("INSERT INTO sbm_cycles (sy_id,school_id,status,started_at) VALUES (?,?,'in_progress',NOW())")->execute([$syId,$schoolId]);
            $cycleId = $db->lastInsertId();
        } else {
            $cycleId = $cycleRow['cycle_id'];
            if ($cycleRow['status'] === 'draft') $db->prepare("UPDATE sbm_cycles SET status='in_progress',started_at=NOW() WHERE cycle_id=?")->execute([$cycleId]);
        }

        // Upsert response
        $db->prepare("INSERT INTO sbm_responses (cycle_id,indicator_id,school_id,rating,evidence_text,rated_by) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),evidence_text=VALUES(evidence_text),rated_by=VALUES(rated_by),rated_at=NOW()")
           ->execute([$cycleId,$indicatorId,$schoolId,$rating,$evidence,$_SESSION['user_id']]);

        // Recompute dimension score
        recomputeDimScore($db, $cycleId, $indicatorId, $schoolId);

        echo json_encode(['ok'=>true,'msg'=>'Saved.']); exit;
    }

    if ($_POST['action'] === 'submit') {
        $cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
        $cycle->execute([$schoolId,$syId]); $cyc = $cycle->fetch();
        if (!$cyc) { echo json_encode(['ok'=>false,'msg'=>'No assessment to submit.']); exit; }
        $count = $db->prepare("SELECT COUNT(*) FROM sbm_responses WHERE cycle_id=?");
        $count->execute([$cyc['cycle_id']]); $cnt = $count->fetchColumn();
        if ($cnt < 42) { echo json_encode(['ok'=>false,'msg'=>"Please rate all 42 indicators. ($cnt/42 done)"]); exit; }

        // Compute overall score
        $total = $db->prepare("SELECT SUM(raw_score),SUM(max_score) FROM sbm_dimension_scores WHERE cycle_id=?");
        $total->execute([$cyc['cycle_id']]); [$totalRaw,$totalMax] = array_values($total->fetch(PDO::FETCH_NUM));
        $overall = $totalMax > 0 ? round(($totalRaw/$totalMax)*100,2) : 0;
        $mat = sbmMaturityLevel($overall);

        $db->prepare("UPDATE sbm_cycles SET status='submitted',submitted_at=NOW(),overall_score=?,maturity_level=? WHERE cycle_id=?")
           ->execute([$overall,$mat['label'],$cyc['cycle_id']]);
        logActivity('submit_assessment','self_assessment','Submitted SBM assessment cycle '.$cyc['cycle_id']);
        echo json_encode(['ok'=>true,'msg'=>'Assessment submitted successfully!']); exit;
    }
    exit;
}

function recomputeDimScore(PDO $db, int $cycleId, int $indicatorId, int $schoolId): void {
    $dimId = $db->prepare("SELECT dimension_id FROM sbm_indicators WHERE indicator_id=?");
    $dimId->execute([$indicatorId]); $dimId = $dimId->fetchColumn();
    $scores = $db->prepare("SELECT SUM(r.rating) raw,COUNT(r.response_id)*4 max_possible FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id WHERE r.cycle_id=? AND i.dimension_id=?");
    $scores->execute([$cycleId,$dimId]); [$raw,$maxP] = array_values($scores->fetch(PDO::FETCH_NUM));
    $pct = $maxP > 0 ? round(($raw/$maxP)*100,2) : 0;
    $db->prepare("INSERT INTO sbm_dimension_scores (cycle_id,school_id,dimension_id,raw_score,max_score,percentage) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE raw_score=VALUES(raw_score),max_score=VALUES(max_score),percentage=VALUES(percentage),computed_at=NOW()")
       ->execute([$cycleId,$schoolId,$dimId,$raw,$maxP,$pct]);
}

// Load all indicators with existing responses
$indicators = $db->query("SELECT i.*,d.dimension_no,d.dimension_name,d.color_hex FROM sbm_indicators i JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE i.is_active=1 ORDER BY d.dimension_no,i.sort_order")->fetchAll();

$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId,$syId]); $cycle = $cycle->fetch();

$responses = [];
if ($cycle) {
    $r = $db->prepare("SELECT * FROM sbm_responses WHERE cycle_id=?");
    $r->execute([$cycle['cycle_id']]); 
    foreach ($r->fetchAll() as $row) $responses[$row['indicator_id']] = $row;
}

$grouped = [];
foreach ($indicators as $ind) $grouped[$ind['dimension_no']][] = $ind;

$ratingLabels = [1=>'Not Yet Manifested',2=>'Emerging',3=>'Developing',4=>'Always Manifested'];
$ratingColors = [1=>'#DC2626',2=>'#D97706',3=>'#2563EB',4=>'#16A34A'];

$isLocked = $cycle && in_array($cycle['status'],['submitted','validated']);
$totalDone = count($responses);

$pageTitle = 'SBM Self-Assessment'; $activePage = 'self_assessment.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text">
    <h2>SBM Self-Assessment</h2>
    <p>Rate all 42 indicators across 6 dimensions using the 4 Degrees of Manifestation scale.</p>
  </div>
  <div class="page-head-actions">
    <?php if(!$isLocked): ?>
    <button class="btn btn-primary" onclick="submitAssessment()" <?= $totalDone < 42 ? 'title="Rate all 42 indicators first"' : '' ?>>
      <?= svgIcon('check') ?> Submit Assessment
    </button>
    <?php else: ?>
    <span class="pill pill-<?= e($cycle['status']) ?>" style="font-size:13px;padding:6px 14px;"><?= ucfirst(str_replace('_',' ',$cycle['status'])) ?></span>
    <?php endif; ?>
  </div>
</div>

<?php if($isLocked): ?>
<div class="alert alert-info mb5" style="margin-bottom:16px;"><?= svgIcon('info') ?> This assessment has been <?= e($cycle['status']) ?>. Responses are read-only.</div>
<?php endif; ?>

<!-- Progress summary -->
<div class="card mb5" style="margin-bottom:18px;">
  <div class="card-body" style="padding:14px 18px;">
    <div class="flex-cb" style="margin-bottom:8px;">
      <span style="font-size:14px;font-weight:700;color:var(--n800);">Progress: <?= $totalDone ?>/42 indicators rated</span>
      <span style="font-size:14px;font-weight:800;color:var(--g700);"><?= round(($totalDone/42)*100) ?>%</span>
    </div>
    <div class="prog" style="height:10px;"><div class="prog-fill green" style="width:<?= round(($totalDone/42)*100) ?>%;"></div></div>
    <div style="display:flex;gap:16px;margin-top:10px;flex-wrap:wrap;">
      <?php foreach([1=>'Not Yet',2=>'Emerging',3=>'Developing',4=>'Always Manifested'] as $r => $rl): ?>
      <?php $cnt = count(array_filter($responses, fn($x) => $x['rating']==$r)); ?>
      <div style="font-size:12px;"><span style="color:<?= $ratingColors[$r] ?>;font-weight:700;"><?= $cnt ?></span> <span style="color:var(--n500);"><?= $rl ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Sticky dimension tabs -->
<div style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;position:sticky;top:60px;z-index:40;background:var(--n50);padding:8px 0;">
  <?php foreach($grouped as $dimNo => $inds): ?>
  <?php $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']]))); ?>
  <a href="#dim<?= $dimNo ?>" style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;background:<?= $dimDone===count($inds)?'var(--g600)':'var(--white)' ?>;color:<?= $dimDone===count($inds)?'#fff':'var(--n600)' ?>;border:1px solid <?= $dimDone===count($inds)?'var(--g600)':'var(--n200)' ?>;text-decoration:none;">
    D<?= $dimNo ?> <span style="opacity:.7;">(<?= $dimDone ?>/<?= count($inds) ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<style>
/* ── Collapsible dimension header ── */
.dim-header {
  display:flex;align-items:center;gap:10px;
  padding:14px 18px;
  background:var(--white);
  border:1px solid var(--n200);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  cursor:pointer;
  user-select:none;
  transition:background .15s;
}
.dim-header:hover { background:var(--n50); }
.dim-chevron {
  margin-left:4px;
  font-size:20px;
  color:var(--n300);
  transition:transform .25s ease;
  flex-shrink:0;
  line-height:1;
}
.dim-body {
  padding-top:8px;
  margin-bottom:20px;
}
.dim-body.collapsed { display:none; }
.dim-wrap { margin-bottom:6px; }

/* ── Indicator rows ── */
.indicator-row {
  background:var(--white);
  border:1px solid var(--n200);
  border-radius:var(--radius);
  padding:14px 16px;
  margin-bottom:8px;
  transition:border-color .2s, background .2s;
}
.indicator-row.rated {
  border-color:#86EFAC;
  background:#F0FDF4;
}
.indicator-code { font-size:11px;font-weight:700;color:var(--n500);letter-spacing:.6px;text-transform:uppercase; }
.indicator-text { font-size:13.5px;font-weight:600;color:var(--n900);margin:5px 0 4px;line-height:1.5; }
.indicator-mov  { font-size:12px;color:var(--n400);margin-bottom:10px;line-height:1.5; }

/* ── Rating buttons ── */
.rating-group { display:flex;gap:7px;flex-wrap:wrap;margin-bottom:10px; }
.rating-btn {
  padding:7px 14px;
  border-radius:8px;
  border:1.5px solid var(--n200);
  background:var(--white);
  font-size:12px;font-weight:600;
  cursor:pointer;
  transition:all .15s;
  color:var(--n600);
  white-space:nowrap;
}
.rating-btn:hover:not(:disabled) { border-color:var(--n400);background:var(--n50); }
.rating-btn:disabled { opacity:.5;cursor:not-allowed; }
.rating-btn.selected-1 { background:#FEE2E2;border-color:#DC2626;color:#DC2626; }
.rating-btn.selected-2 { background:#FEF3C7;border-color:#D97706;color:#D97706; }
.rating-btn.selected-3 { background:#DBEAFE;border-color:#2563EB;color:#2563EB; }
.rating-btn.selected-4 { background:#DCFCE7;border-color:#16A34A;color:#16A34A; }
</style>

<!-- Indicators by dimension -->
<?php foreach($grouped as $dimNo => $inds): ?>
<?php $dim = $inds[0]; $dimDone = count(array_filter($inds, fn($i) => isset($responses[$i['indicator_id']]))); $allDone = $dimDone === count($inds); ?>
<div class="dim-wrap" id="dim<?= $dimNo ?>">

  <!-- Clickable header -->
  <div class="dim-header" onclick="toggleDim(<?= $dimNo ?>)" style="border-left:4px solid <?= e($dim['color_hex']) ?>;">
    <div style="width:38px;height:38px;border-radius:9px;background:<?= e($dim['color_hex']) ?>22;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:<?= e($dim['color_hex']) ?>;flex-shrink:0;"><?= $dimNo ?></div>
    <div style="flex:1;min-width:0;">
      <div style="font-size:14.5px;font-weight:700;color:var(--n900);">Dimension <?= $dimNo ?>: <?= e($dim['dimension_name']) ?></div>
      <div style="font-size:12px;color:var(--n400);margin-top:2px;" id="dim<?= $dimNo ?>Counter"><?= $dimDone ?>/<?= count($inds) ?> indicators rated</div>
    </div>
    <div style="font-size:13px;font-weight:700;color:<?= e($dim['color_hex']) ?>;margin-right:6px;" id="dim<?= $dimNo ?>Score">
      <?php
        $ds = $cycle ? $db->prepare("SELECT percentage FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? AND d.dimension_no=?") : null;
        if ($ds) { $ds->execute([$cycle['cycle_id'],$dimNo]); $pctRow = $ds->fetchColumn(); echo $pctRow ? number_format($pctRow,1).'%' : '—'; } else echo '—';
      ?>
    </div>
    <?php if($allDone): ?>
    <span style="font-size:11px;font-weight:700;color:#16A34A;background:#DCFCE7;border:1px solid #86EFAC;border-radius:999px;padding:3px 10px;flex-shrink:0;">✓ Complete</span>
    <?php else: ?>
    <span style="font-size:11px;font-weight:600;color:var(--n500);background:var(--n100);border-radius:999px;padding:3px 10px;flex-shrink:0;" id="dim<?= $dimNo ?>LeftBadge"><?= count($inds)-$dimDone ?> left</span>
    <?php endif; ?>
    <span class="dim-chevron" id="dimChevron<?= $dimNo ?>">▾</span>
  </div>

  <!-- Collapsible body -->
  <div class="dim-body" id="dimBody<?= $dimNo ?>">
    <?php foreach($inds as $ind): ?>
    <?php $resp = $responses[$ind['indicator_id']] ?? null; $rated = $resp !== null; ?>
    <div class="indicator-row <?= $rated?'rated':'' ?>" id="row<?= $ind['indicator_id'] ?>">
      <div class="flex-cb" style="margin-bottom:4px;">
        <div class="indicator-code"><?= e($ind['indicator_code']) ?></div>
        <?php if($rated): ?>
        <span id="savedBadge<?= $ind['indicator_id'] ?>" style="font-size:11px;color:var(--g600);font-weight:600;">✓ Saved</span>
        <?php else: ?>
        <span id="savedBadge<?= $ind['indicator_id'] ?>"></span>
        <?php endif; ?>
      </div>
      <div class="indicator-text"><?= e($ind['indicator_text']) ?></div>
      <div class="indicator-mov">📎 MOV: <?= e($ind['mov_guide']) ?></div>
      <div class="rating-group" id="ratingGroup<?= $ind['indicator_id'] ?>">
        <?php foreach([1,2,3,4] as $r): ?>
        <button <?= $isLocked?'disabled':'' ?> type="button"
          class="rating-btn <?= $resp&&$resp['rating']==$r?'selected-'.$r:'' ?>"
          data-ind="<?= $ind['indicator_id'] ?>" data-rating="<?= $r ?>"
          onclick="selectRating(<?= $ind['indicator_id'] ?>,<?= $r ?>)">
          <?= $r ?> — <?= $ratingLabels[$r] ?>
        </button>
        <?php endforeach; ?>
      </div>
      <textarea class="fc" id="evidence<?= $ind['indicator_id'] ?>" rows="2"
        placeholder="Describe evidence or attach MOV reference…"
        <?= $isLocked?'disabled':'' ?>
        onblur="saveResponse(<?= $ind['indicator_id'] ?>)"><?= e($resp['evidence_text'] ?? '') ?></textarea>
    </div>
    <?php endforeach; ?>
  </div><!-- /dimBody -->

</div><!-- /dim-wrap -->
<?php endforeach; ?>

<div style="text-align:center;padding:20px 0;margin-top:8px;">
  <?php if(!$isLocked): ?>
  <button class="btn btn-primary" style="padding:12px 32px;font-size:15px;" onclick="submitAssessment()">
    <?= svgIcon('check') ?> Submit Self-Assessment (<?= $totalDone ?>/42 rated)
  </button>
  <?php endif; ?>
</div>

<script>
let currentRatings = <?= json_encode(array_map(fn($r)=>$r['rating'],$responses)) ?>;

function selectRating(indId, rating){
  currentRatings[indId] = rating;
  // Update button styles
  document.querySelectorAll(`#ratingGroup${indId} .rating-btn`).forEach(btn=>{
    const r = parseInt(btn.dataset.rating);
    btn.className = 'rating-btn' + (r === rating ? ` selected-${r}` : '');
  });
  // Auto-save
  saveResponse(indId);
}

async function saveResponse(indId){
  const rating = currentRatings[indId];
  if(!rating) return;
  const evidence = document.getElementById(`evidence${indId}`)?.value || '';
  const r = await apiPost('self_assessment.php',{action:'save_response',indicator_id:indId,rating,evidence});
  if(r.ok){
    const row = document.getElementById(`row${indId}`);
    if(row) row.classList.add('rated');
    const badge = document.getElementById(`savedBadge${indId}`);
    if(badge){ badge.textContent='✓ Saved'; badge.style.color='var(--g600)'; badge.style.fontWeight='600'; badge.style.fontSize='11px'; }
  }
}

function toggleDim(n) {
  const body    = document.getElementById('dimBody' + n);
  const chevron = document.getElementById('dimChevron' + n);
  const isOpen  = !body.classList.contains('collapsed');
  body.classList.toggle('collapsed', isOpen);
  chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
}

async function submitAssessment(){
  const total = Object.keys(currentRatings).length;
  if(total < 42){ toast(`Please rate all 42 indicators. (${total}/42 done)`,'warning'); return; }
  if(!confirm('Submit your SBM Self-Assessment to the SDO? You will not be able to edit after submission.')) return;
  const r = await apiPost('self_assessment.php',{action:'submit'});
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok) setTimeout(()=>location.reload(),1200);
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>