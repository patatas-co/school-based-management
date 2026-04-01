<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();
$schoolId = $_SESSION['school_id'] ?? 0;
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();
$syId = $sy['sy_id'] ?? 0;

$cycle = null;
if ($schoolId) {
    $st = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
    $st->execute([$schoolId,$syId]); $cycle = $st->fetch();
}

$dimensions = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
$dimScores = [];
if ($cycle) {
    $st = $db->prepare("SELECT ds.*,d.dimension_name,d.dimension_no,d.color_hex,d.indicator_count FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
    $st->execute([$cycle['cycle_id']]); $dimScores = $st->fetchAll();
}
$dimScoreMap = array_column($dimScores, null, 'dimension_no');

// ── ADD THIS BLOCK BELOW ──────────────────────────────────────
// Cross-cycle trend data
$allCyclesStmt = $db->prepare("
    SELECT c.cycle_id, c.overall_score, c.status, c.created_at
    FROM sbm_cycles c
    WHERE c.school_id=?
    ORDER BY c.cycle_id ASC
");
$allCyclesStmt->execute([$schoolId]);
$allCycles = $allCyclesStmt->fetchAll();

$trendData = [];
foreach ($allCycles as $cyc) {
    $ds = $db->prepare("SELECT d.dimension_no, ds.percentage FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
    $ds->execute([$cyc['cycle_id']]);
    foreach ($ds->fetchAll() as $row) {
        $trendData[$cyc['cycle_id']][$row['dimension_no']] = $row['percentage'];
    }
}

// Indicator completion per dimension
$completed = [];
if ($cycle) {
    $st = $db->prepare("SELECT i.dimension_id, COUNT(*) cnt FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id WHERE r.cycle_id=? GROUP BY i.dimension_id");
    $st->execute([$cycle['cycle_id']]); foreach($st->fetchAll() as $row) $completed[$row['dimension_id']] = $row['cnt'];
}

$pageTitle = 'SBM Dimensions'; $activePage = 'dimensions.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>SBM Dimensions</h2>
    <p>Performance breakdown across 6 dimensions — SY <?= e($sy['label']??'—') ?></p></div>
  <div class="page-head-actions">
    <a href="self_assessment.php" class="btn btn-primary"><?= svgIcon('check-circle') ?> Fill Assessment</a>
  </div>
</div>

<?php if ($cycle): ?>
<div class="card mb5" style="margin-bottom:18px;">
  <div class="card-body" style="padding:14px 18px;">
    <div class="flex-cb" style="flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-size:11.5px;color:var(--n500);margin-bottom:2px;">Overall SBM Score</div>
        <div style="font-size:28px;font-weight:800;color:var(--g600);"><?= number_format($cycle['overall_score']??0,1) ?>%</div>
      </div>
      <div><?= sbmMaturityBadge($cycle['maturity_level']??'Beginning') ?></div>
      <div><span class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst(str_replace('_',' ',$cycle['status'])) ?></span></div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:18px;">
<?php foreach ($dimensions as $d):
  $score = $dimScoreMap[$d['dimension_no']] ?? null;
  $done  = $completed[$d['dimension_id']] ?? 0;
  $pct   = $score ? $score['percentage'] : 0;
  $maturity = computeMaturity($pct);
?>
<div style="background:var(--white);border:1px solid var(--n200);border-radius:10px;padding:18px;box-shadow:var(--shadow);border-top:4px solid <?= e($d['color_hex']) ?>;">
  <div class="flex-cb" style="margin-bottom:12px;">
    <div>
      <div style="font-size:11px;font-weight:700;color:var(--n500);text-transform:uppercase;margin-bottom:2px;">Dimension <?= $d['dimension_no'] ?></div>
      <div style="font-size:14px;font-weight:700;color:var(--n900);"><?= e($d['dimension_name']) ?></div>
    </div>
    <div style="font-size:24px;font-weight:800;color:<?= e($d['color_hex']) ?>;"><?= $pct ? number_format($pct,0).'%' : '—' ?></div>
  </div>
  <div class="prog" style="margin-bottom:10px;"><div class="prog-fill" style="width:<?= min(100,$pct) ?>%;background:<?= e($d['color_hex']) ?>;"></div></div>
  <div class="flex-cb">
    <span style="font-size:12px;color:var(--n500);"><?= $done ?>/<?= $d['indicator_count'] ?> indicators rated</span>
    <?php if($pct): ?><?= sbmMaturityBadge($maturity) ?><?php endif; ?>
  </div>
  <?php if ($score): ?>
  <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--n100);display:flex;gap:16px;">
    <div style="font-size:11.5px;color:var(--n500);">Raw: <strong><?= $score['raw_score'] ?>/<?= $score['max_score'] ?></strong></div>
  </div>
  <?php endif; ?>
  <a href="self_assessment.php#dim<?= $d['dimension_no'] ?>" class="btn btn-secondary btn-sm" style="margin-top:10px;width:100%;justify-content:center;"><?= svgIcon('edit') ?> View / Edit</a>
</div>
<?php endforeach; ?>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">Dimension Radar</span></div>
  <div class="card-body" style="max-width:500px;margin:0 auto;">
    <canvas id="radarChart"></canvas>
  </div>
</div>

<script>
new Chart(document.getElementById('radarChart'),{
  type:'radar',
  data:{
    labels:<?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'].': '.$d['dimension_name'],$dimensions)) ?>,
    datasets:[{label:'SBM Score (%)',data:<?= json_encode(array_map(fn($d)=>$dimScoreMap[$d['dimension_no']]['percentage']??0,$dimensions)) ?>,
      backgroundColor:'rgba(22,163,74,.15)',borderColor:'#16A34A',pointBackgroundColor:'#16A34A',pointRadius:5}]
  },
  options:{scales:{r:{beginAtZero:true,max:100,ticks:{stepSize:25,callback:v=>v+'%'}}},plugins:{legend:{display:false}}}
});
</script>

<?php else: ?>
<div class="alert alert-warning"><?= svgIcon('alert-circle') ?><span>No assessment data yet for SY <?= e($sy['label']??'—') ?>. <a href="self_assessment.php" style="color:var(--gold);font-weight:600;">Start your self-assessment →</a></span></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
<?php foreach($dimensions as $d): ?>
<div style="background:var(--white);border:1px solid var(--n200);border-radius:10px;padding:16px;border-top:4px solid <?= e($d['color_hex']) ?>;">
  <div style="font-size:11px;font-weight:700;color:var(--n400);text-transform:uppercase;">Dimension <?= $d['dimension_no'] ?></div>
  <div style="font-size:14px;font-weight:700;color:var(--n900);margin:4px 0 8px;"><?= e($d['dimension_name']) ?></div>
  <div style="font-size:12.5px;color:var(--n500);"><?= $d['indicator_count'] ?> indicators</div>
  <div class="prog" style="margin-top:8px;"><div class="prog-fill" style="width:0%;background:<?= e($d['color_hex']) ?>;"></div></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if(count($allCycles) >= 2 && !empty($trendData)): ?>
<div class="card" style="margin-top:20px;">
  <div class="card-head">
    <span class="card-title">Cross-Cycle Trend</span>
    <span style="font-size:12px;color:var(--n400);"><?= count($allCycles) ?> assessment cycle(s)</span>
  </div>
  <div class="card-body">
    <canvas id="trendChart" height="100"></canvas>
  </div>
</div>
<script>
const TREND_CYCLES = <?= json_encode(array_map(fn($c, $i) => 'Cycle '.($i+1).' ('.date('M Y', strtotime($c['created_at'])).')', $allCycles, array_keys($allCycles))) ?>;
const TREND_DIMS   = <?= json_encode(array_column($dimensions, 'dimension_name')) ?>;
const TREND_COLORS = <?= json_encode(array_column($dimensions, 'color_hex')) ?>;
const TREND_DATA   = <?= json_encode($trendData) ?>;
const CYCLE_IDS    = <?= json_encode(array_column($allCycles, 'cycle_id')) ?>;

const datasets = TREND_DIMS.map((name, idx) => ({
  label: 'D' + (idx+1) + ': ' + name,
  data: CYCLE_IDS.map(cid => TREND_DATA[cid]?.[idx+1] ?? null),
  borderColor: TREND_COLORS[idx],
  backgroundColor: TREND_COLORS[idx] + '22',
  tension: 0.35,
  pointRadius: 5,
  borderWidth: 2,
  spanGaps: true,
}));

new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: { labels: TREND_CYCLES, datasets },
  options: {
    scales: { y: { min:0, max:100, ticks:{ callback: v => v+'%' } } },
    plugins: {
      legend: { position:'bottom', labels:{ font:{ family:"'DM Sans',sans-serif", size:12 }, padding:10 } },
      tooltip: { callbacks:{ label: ctx => ' '+ctx.dataset.label+': '+ctx.raw+'%' } }
    },
    responsive: true
  }
});
</script>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>
