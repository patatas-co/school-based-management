<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

$syId = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());

// Dimension averages
$dimAvg = $db->prepare("
  SELECT d.dimension_no,d.dimension_name,d.color_hex,
         ROUND(AVG(ds.percentage),1) avg_pct,
         COUNT(DISTINCT ds.school_id) school_count
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds ON d.dimension_id=ds.dimension_id
  LEFT JOIN sbm_cycles c ON ds.cycle_id=c.cycle_id AND c.sy_id=?
  GROUP BY d.dimension_id ORDER BY d.dimension_no
");
$dimAvg->execute([$syId]); $dimAvgs = $dimAvg->fetchAll();

// Maturity distribution
$matDist = $db->prepare("SELECT maturity_level,COUNT(*) cnt FROM sbm_cycles WHERE sy_id=? AND maturity_level IS NOT NULL GROUP BY maturity_level");
$matDist->execute([$syId]); $matDists = $matDist->fetchAll();

// Top performing schools
$topSchools = $db->prepare("
  SELECT s.school_name,c.overall_score,c.maturity_level
  FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id
  WHERE c.sy_id=? AND c.overall_score IS NOT NULL
  ORDER BY c.overall_score DESC LIMIT 10
");
$topSchools->execute([$syId]); $topSchools = $topSchools->fetchAll();

// Weakest indicators
$weakIndicators = $db->prepare("
  SELECT i.indicator_code,i.indicator_text,d.dimension_name,
         ROUND(AVG(r.rating),2) avg_rating,
         COUNT(r.response_id) response_count
  FROM sbm_responses r
  JOIN sbm_indicators i ON r.indicator_id=i.indicator_id
  JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id
  JOIN sbm_cycles c ON r.cycle_id=c.cycle_id
  WHERE c.sy_id=?
  GROUP BY i.indicator_id
  ORDER BY avg_rating ASC LIMIT 8
");
$weakIndicators->execute([$syId]); $weakIndicators = $weakIndicators->fetchAll();

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
$pageTitle = 'Analytics'; $activePage = 'analytics.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>SBM Analytics</h2><p>System-wide performance insights and trend analysis.</p></div>
  <div class="page-head-actions">
    <select class="fc" onchange="location.href='analytics.php?sy='+this.value" style="width:150px;">
      <?php foreach($syears as $sy): ?>
      <option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id']==$syId?'selected':'' ?>><?= e($sy['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="grid2" style="margin-bottom:20px;">
  <!-- Radar chart -->
  <div class="card">
    <div class="card-head"><span class="card-title">Dimension Performance Radar</span></div>
    <div class="card-body" style="display:flex;justify-content:center;">
      <canvas id="radarChart" style="max-width:360px;"></canvas>
    </div>
  </div>
  <!-- Maturity donut -->
  <div class="card">
    <div class="card-head"><span class="card-title">Maturity Level Distribution</span></div>
    <div class="card-body" style="display:flex;justify-content:center;align-items:center;min-height:260px;">
      <canvas id="maturityChart" style="max-width:280px;"></canvas>
    </div>
  </div>
</div>

<div class="grid2" style="margin-bottom:20px;">
  <!-- Top Schools -->
  <div class="card">
    <div class="card-head"><span class="card-title">Top Performing Schools</span></div>
    <div class="card-body" style="padding:0;">
      <?php foreach($topSchools as $i => $sc): ?>
      <?php $mat = sbmMaturityLevel(floatval($sc['overall_score'])); ?>
      <div class="flex-cb" style="padding:10px 18px;border-bottom:1px solid var(--n100);">
        <div class="flex-c" style="gap:10px;">
          <span style="width:22px;height:22px;border-radius:50%;background:var(--g100);color:var(--g700);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $i+1 ?></span>
          <span style="font-size:13px;font-weight:600;"><?= e($sc['school_name']) ?></span>
        </div>
        <div class="flex-c" style="gap:8px;">
          <span style="font-size:14px;font-weight:800;color:var(--g700);"><?= $sc['overall_score'] ?>%</span>
          <span class="pill pill-<?= e($sc['maturity_level']) ?>"><?= e($sc['maturity_level']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(!$topSchools): ?><div style="padding:24px;text-align:center;color:var(--n400);">No data yet.</div><?php endif; ?>
    </div>
  </div>

  <!-- Weakest Indicators -->
  <div class="card">
    <div class="card-head"><span class="card-title">Indicators Needing Attention</span><span style="font-size:11.5px;color:var(--n400);">Lowest avg. rating</span></div>
    <div class="card-body" style="padding:0;">
      <?php foreach($weakIndicators as $ind): ?>
      <?php $avgR = floatval($ind['avg_rating']); $pct = ($avgR/4)*100; $color = $avgR >= 3 ? 'var(--g500)' : ($avgR >= 2 ? 'var(--gold)' : 'var(--red)'); ?>
      <div style="padding:10px 18px;border-bottom:1px solid var(--n100);">
        <div class="flex-cb" style="margin-bottom:5px;">
          <div>
            <span style="font-size:11px;font-weight:700;color:var(--n400);text-transform:uppercase;"><?= e($ind['indicator_code']) ?></span>
            <span style="font-size:11px;color:var(--n400);margin-left:6px;"><?= e($ind['dimension_name']) ?></span>
          </div>
          <span style="font-size:13px;font-weight:700;color:<?= $color ?>;"><?= number_format($avgR,2) ?>/4.00</span>
        </div>
        <div style="font-size:12.5px;color:var(--n700);margin-bottom:6px;line-height:1.4;"><?= e(substr($ind['indicator_text'],0,90)).'…' ?></div>
        <div class="prog"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
      </div>
      <?php endforeach; ?>
      <?php if(!$weakIndicators): ?><div style="padding:24px;text-align:center;color:var(--n400);">No response data yet.</div><?php endif; ?>
    </div>
  </div>
</div>

<!-- Dimension breakdown bar chart -->
<div class="card">
  <div class="card-head"><span class="card-title">Dimension Score Comparison</span></div>
  <div class="card-body">
    <canvas id="dimBarChart" height="80"></canvas>
  </div>
</div>

<script>
const dimLabels  = <?= json_encode(array_map(fn($d)=>'Dim '.$d['dimension_no'], $dimAvgs)) ?>;
const dimColors  = <?= json_encode(array_column($dimAvgs,'color_hex')) ?>;
const dimValues  = <?= json_encode(array_map(fn($d)=>floatval($d['avg_pct']), $dimAvgs)) ?>;

// Radar
const radarFullNames = <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'].': '.$d['dimension_name'], $dimAvgs)) ?>;
if (dimValues.length > 0 && dimValues.some(v => v > 0)) {
  new Chart(document.getElementById('radarChart'),{
    type:'radar',
    data:{
      labels: <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'], $dimAvgs)) ?>,
      datasets:[{
        label:'Avg Score (%)',
        data:dimValues,
        backgroundColor:'rgba(22,163,74,.15)',
        borderColor:'#16A34A',
        pointBackgroundColor:dimColors,
        pointRadius:5,
        borderWidth:2
      }]
    },
    options:{
      scales:{r:{
        min:0,max:100,
        ticks:{font:{size:10},stepSize:25,backdropColor:'transparent'},
        pointLabels:{font:{size:13,weight:'700',family:"'DM Sans',sans-serif"},color:'#374151'}
      }},
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{title:ctx=>radarFullNames[ctx[0].dataIndex],label:ctx=>' '+ctx.raw+'%'}}
      },
      maintainAspectRatio:true
    }
  });
} else {
  document.getElementById('radarChart').closest('.card-body').innerHTML =
    '<p style="text-align:center;color:var(--n400);padding:40px 0;font-size:13px;">No dimension data yet for this school year.</p>';
}

// Maturity donut
const matData = <?= json_encode(array_column($matDists,'cnt','maturity_level')) ?>;
const matTotal = ['Beginning','Developing','Maturing','Advanced'].reduce((s,l)=>(matData[l]||0)+s,0);
if (matTotal > 0) {
  new Chart(document.getElementById('maturityChart'),{
    type:'doughnut',
    data:{labels:['Beginning','Developing','Maturing','Advanced'],datasets:[{data:['Beginning','Developing','Maturing','Advanced'].map(l=>matData[l]||0),backgroundColor:['#DC2626','#D97706','#2563EB','#16A34A'],borderWidth:2,borderColor:'#fff'}]},
    options:{plugins:{legend:{position:'bottom',labels:{font:{family:"'DM Sans',sans-serif",size:12},padding:10}}},cutout:'60%',maintainAspectRatio:true}
  });
} else {
  document.getElementById('maturityChart').closest('.card-body').innerHTML =
    '<p style="text-align:center;color:var(--n400);padding:40px 0;font-size:13px;">No validated assessments yet.</p>';
}

// Bar chart
new Chart(document.getElementById('dimBarChart'),{
  type:'bar',
  data:{labels:dimLabels,datasets:[{label:'Average Score (%)',data:dimValues,backgroundColor:dimColors.map(c=>c+'33'),borderColor:dimColors,borderWidth:2,borderRadius:6}]},
  options:{scales:{y:{min:0,max:100,ticks:{callback:v=>v+'%'}}},plugins:{legend:{display:false}},responsive:true,maintainAspectRatio:true}
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>