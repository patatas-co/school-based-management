<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

$syId = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());

$dimAvg = $db->prepare("
  SELECT d.dimension_no,d.dimension_name,d.color_hex,
         ROUND(AVG(ds.percentage),1) avg_pct
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds ON d.dimension_id=ds.dimension_id
  LEFT JOIN sbm_cycles c ON ds.cycle_id=c.cycle_id
  WHERE c.sy_id=? AND c.school_id=?
  GROUP BY d.dimension_id ORDER BY d.dimension_no
");
$dimAvg->execute([$syId, SCHOOL_ID]); $dimAvgs = $dimAvg->fetchAll();

$matDist = $db->prepare("SELECT maturity_level,COUNT(*) cnt FROM sbm_cycles WHERE sy_id=? AND school_id=? AND maturity_level IS NOT NULL GROUP BY maturity_level");
$matDist->execute([$syId, SCHOOL_ID]); $matDists = $matDist->fetchAll();

$stmtHistory = $db->prepare("
  SELECT sy.label as school_name, 'JHS' as classification,
         c.overall_score, c.maturity_level
  FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id
  WHERE c.school_id=? AND c.overall_score IS NOT NULL
  ORDER BY c.overall_score DESC LIMIT 10
");
$stmtHistory->execute([SCHOOL_ID]); $topSchools = $stmtHistory->fetchAll();

$weakIndicators = $db->prepare("
  SELECT i.indicator_code,i.indicator_text,d.dimension_name,d.color_hex,
         ROUND(AVG(r.rating),2) avg_rating, COUNT(r.response_id) response_count
  FROM sbm_responses r
  JOIN sbm_indicators i ON r.indicator_id=i.indicator_id
  JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id
  JOIN sbm_cycles c ON r.cycle_id=c.cycle_id
  WHERE c.sy_id=?
  GROUP BY i.indicator_id ORDER BY avg_rating ASC LIMIT 8
");
$weakIndicators->execute([$syId]); $weakIndicators = $weakIndicators->fetchAll();

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();

// KPI calculations
$allPcts = array_filter(array_column($dimAvgs,'avg_pct'), fn($v) => $v !== null);
$avgOverall = count($allPcts) > 0 ? round(array_sum($allPcts)/count($allPcts),1) : null;
$topDim = !empty($allPcts) ? $dimAvgs[array_search(max($allPcts), array_column($dimAvgs,'avg_pct'))] : null;
$weakDim = !empty($allPcts) ? $dimAvgs[array_search(min($allPcts), array_column($dimAvgs,'avg_pct'))] : null;
$schoolsWithData = 1; // Single-school system (DIHS)

$pageTitle = 'Analytics'; $activePage = 'analytics.php';
include __DIR__.'/../includes/header.php';
?>
<style>
.chart-legend{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px;}
.chart-legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--n-600);}
.chart-legend-swatch{width:10px;height:10px;border-radius:2px;flex-shrink:0;}
.weak-prog{height:6px;background:var(--n-100);border-radius:999px;overflow:hidden;margin-top:5px;}
.weak-fill{height:100%;border-radius:999px;}
</style>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Reporting</div>
    <div class="ph2-title">SBM Analytics</div>
    <div class="ph2-sub">System-wide performance insights and dimension analysis.</div>
  </div>
  <div class="ph2-right">
    <select class="fc" onchange="location.href='analytics.php?sy='+this.value" style="width:160px;">
      <?php foreach($syears as $sy): ?>
      <option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id']==$syId?'selected':'' ?>><?= e($sy['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- KPI Strip -->
<div class="kpi-row">
  <div class="kpi-mini">
    <div class="kpi-mini-val"><?= $avgOverall !== null ? $avgOverall.'%' : '—' ?></div>
    <div class="kpi-mini-lbl">System Average</div>
  </div>
  <div class="kpi-mini">
    <div class="kpi-mini-val" style="font-size:15px;color:var(--brand-700);">DIHS</div>
    <div class="kpi-mini-lbl">Dasmariñas Integrated HS</div>
  </div>
  <div class="kpi-mini">
    <?php if($topDim): ?><div class="kpi-mini-val" style="font-size:18px;color:var(--brand-700);">D<?= $topDim['dimension_no'] ?></div>
    <div class="kpi-mini-lbl">Top Dimension (<?= $topDim['avg_pct'] ?>%)</div>
    <?php else: ?><div class="kpi-mini-val">—</div><div class="kpi-mini-lbl">Top Dimension</div><?php endif; ?>
  </div>
  <div class="kpi-mini">
    <?php if($weakDim): ?><div class="kpi-mini-val" style="font-size:18px;color:var(--red);">D<?= $weakDim['dimension_no'] ?></div>
    <div class="kpi-mini-lbl">Weakest Dimension (<?= $weakDim['avg_pct'] ?>%)</div>
    <?php else: ?><div class="kpi-mini-val">—</div><div class="kpi-mini-lbl">Weakest Dimension</div><?php endif; ?>
  </div>
</div>

<!-- Charts row -->
<div class="grid2" style="margin-bottom:18px;">
  <div class="chart-card">
    <div class="chart-card-head">
      <span class="chart-card-title">Dimension Performance Radar</span>
      <span style="font-size:12px;color:var(--n-400);">All 6 dimensions</span>
    </div>
    <div class="chart-card-body" style="display:flex;justify-content:center;">
      <canvas id="radarChart" style="max-width:360px;"></canvas>
    </div>
  </div>
  <div class="chart-card">
    <div class="chart-card-head">
      <span class="chart-card-title">Maturity Level Distribution</span>
    </div>
    <div class="chart-card-body" style="display:flex;justify-content:center;align-items:center;min-height:260px;">
      <canvas id="maturityChart" style="max-width:260px;"></canvas>
    </div>
  </div>
</div>

<!-- Dimension Score Bar -->
<div class="chart-card" style="margin-bottom:18px;">
  <div class="chart-card-head">
    <span class="chart-card-title">Dimension Score Comparison</span>
    <div class="chart-legend" style="margin-bottom:0;">
      <?php foreach($dimAvgs as $d): if(!$d['avg_pct']) continue; ?>
      <div class="chart-legend-item">
        <div class="chart-legend-swatch" style="background:<?= e($d['color_hex']) ?>;"></div>
        D<?= $d['dimension_no'] ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="chart-card-body">
    <canvas id="dimBarChart" height="80"></canvas>
  </div>
</div>

<!-- Bottom grid -->
<div class="grid2" style="margin-bottom:18px;">

  <!-- Top Schools -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Assessment History</span>
      <span style="font-size:12px;color:var(--n-400);"><?= count($topSchools) ?> cycle(s)</span>
    </div>
    <?php if($topSchools): ?>
    <div class="tbl-wrap">
      <table class="tbl-enhanced">
        <thead><tr><th>#</th><th>Year</th><th>Score</th><th>Maturity</th></tr></thead>
        <tbody>
        <?php foreach($topSchools as $i => $sc):
          $mat = sbmMaturityLevel(floatval($sc['overall_score']));
        ?>
        <tr>
          <td style="width:36px;">
            <span style="width:22px;height:22px;border-radius:6px;background:<?= $i===0?'#FEF3C7':($i===1?'#F3F4F6':'var(--n-100)') ?>;color:<?= $i===0?'#B45309':($i===1?'#6B7280':'var(--n-600)') ?>;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;"><?= $i+1 ?></span>
          </td>
          <td>
            <div style="font-size:13px;font-weight:600;color:var(--n-900);">SY <?= e($sc['school_name']) ?></div>
            <div style="font-size:11.5px;color:var(--n-400);">Dasmariñas Integrated HS</div>
          </td>
          <td>
            <div class="score-bar-cell">
              <div class="score-bar-track"><div class="score-bar-fill" style="width:<?= $sc['overall_score'] ?>%;background:<?= $mat['color'] ?>;"></div></div>
              <span class="score-val" style="color:<?= $mat['color'] ?>;"><?= $sc['overall_score'] ?>%</span>
            </div>
          </td>
          <td><span class="pill pill-<?= e($sc['maturity_level']) ?>"><?= e($sc['maturity_level']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('bar-chart-2') ?></div>
      <div class="empty-title">No school data yet</div>
      <div class="empty-sub">Validated assessments will appear here once schools complete their SBM cycle.</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Weak Indicators -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Indicators Needing Attention</span>
      <span style="font-size:12px;color:var(--n-400);">Lowest average ratings</span>
    </div>
    <?php if($weakIndicators): ?>
    <div class="card-body" style="padding:0;">
      <?php foreach($weakIndicators as $ind):
        $avgR = floatval($ind['avg_rating']);
        $pct = ($avgR/4)*100;
        $color = $avgR >= 3 ? 'var(--brand-600)' : ($avgR >= 2 ? 'var(--amber)' : 'var(--red)');
      ?>
      <div style="padding:12px 20px;border-bottom:1px solid var(--n-100);">
        <div class="flex-cb" style="margin-bottom:4px;">
          <div>
            <span style="font-size:10.5px;font-weight:700;color:var(--n-400);text-transform:uppercase;letter-spacing:.05em;"><?= e($ind['indicator_code']) ?></span>
            <span style="font-size:10.5px;color:var(--n-400);margin-left:6px;padding:1px 7px;background:var(--n-100);border-radius:4px;"><?= e($ind['dimension_name']) ?></span>
          </div>
          <span style="font-size:13px;font-weight:700;color:<?= $color ?>;"><?= number_format($avgR,2) ?>/4.00</span>
        </div>
        <div style="font-size:12.5px;color:var(--n-700);margin-bottom:5px;line-height:1.45;"><?= e(substr($ind['indicator_text'],0,88)).'…' ?></div>
        <div class="weak-prog"><div class="weak-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('alert-circle') ?></div>
      <div class="empty-title">No indicator data yet</div>
      <div class="empty-sub">Indicator ratings will appear once schools start submitting assessments.</div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const dimLabels = <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'], $dimAvgs)) ?>;
const dimColors = <?= json_encode(array_column($dimAvgs,'color_hex')) ?>;
const dimValues = <?= json_encode(array_map(fn($d)=> $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $dimAvgs)) ?>;
const radarFullNames = <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'].': '.$d['dimension_name'], $dimAvgs)) ?>;

if (dimValues.some(v => v > 0)) {
  new Chart(document.getElementById('radarChart'),{
    type:'radar',
    data:{
      labels: <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'], $dimAvgs)) ?>,
      datasets:[{
        label:'Avg Score (%)',
        data:dimValues,
        backgroundColor:'rgba(22,163,74,.12)',
        borderColor:'#16A34A',
        pointBackgroundColor:dimColors,
        pointRadius:5,borderWidth:2
      }]
    },
    options:{
      scales:{r:{min:0,max:100,ticks:{font:{size:10},stepSize:25,backdropColor:'transparent'},
        pointLabels:{font:{size:13,weight:'700',family:"'Manrope',sans-serif"},color:'#374151'}}},
      plugins:{legend:{display:false},
        tooltip:{callbacks:{title:ctx=>radarFullNames[ctx[0].dataIndex],label:ctx=>' '+ctx.raw+'%'}}},
      maintainAspectRatio:true
    }
  });
} else {
  document.getElementById('radarChart').closest('.chart-card-body').innerHTML =
    '<p style="text-align:center;color:var(--n-400);padding:48px 0;font-size:13px;">No dimension data for this school year.</p>';
}

const matData = <?= json_encode(array_column($matDists,'cnt','maturity_level')) ?>;
const matTotal = ['Beginning','Developing','Maturing','Advanced'].reduce((s,l)=>(matData[l]||0)+s,0);
if (matTotal > 0) {
  new Chart(document.getElementById('maturityChart'),{
    type:'doughnut',
    data:{labels:['Beginning','Developing','Maturing','Advanced'],datasets:[{
      data:['Beginning','Developing','Maturing','Advanced'].map(l=>matData[l]||0),
      backgroundColor:['#DC2626','#D97706','#2563EB','#16A34A'],
      borderWidth:3,borderColor:'#fff',hoverOffset:5
    }]},
    options:{
      plugins:{legend:{position:'bottom',labels:{font:{family:"'Inter',sans-serif",size:12},padding:12,usePointStyle:true,pointStyleWidth:8}}},
      cutout:'64%',maintainAspectRatio:true
    }
  });
} else {
  document.getElementById('maturityChart').closest('.chart-card-body').innerHTML =
    '<p style="text-align:center;color:var(--n-400);padding:48px 0;font-size:13px;">No validated assessments yet.</p>';
}

new Chart(document.getElementById('dimBarChart'),{
  type:'bar',
  data:{labels:dimLabels,datasets:[{
    label:'Average Score (%)',data:dimValues,
    backgroundColor:dimColors.map(c=>c+'28'),
    borderColor:dimColors,borderWidth:2,borderRadius:8,borderSkipped:false
  }]},
  options:{
    scales:{
      y:{min:0,max:100,ticks:{callback:v=>v+'%',font:{size:11}},grid:{color:'#F3F4F6'}},
      x:{ticks:{font:{size:12,weight:'600'}},grid:{display:false}}
    },
    plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>ctx.raw!==null?' '+ctx.raw+'%':' No data'}}},
    responsive:true,maintainAspectRatio:true
  }
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>