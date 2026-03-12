<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('ro','admin');
$db = getDB();

$regionId = $_SESSION['region_id'] ?? null;
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();

// Division stats
$divQuery = "SELECT d.*, r.region_name,
  COUNT(DISTINCT s.school_id) school_count,
  COUNT(DISTINCT c.cycle_id) cycle_count,
  COUNT(DISTINCT CASE WHEN c.status='validated' THEN c.cycle_id END) validated_count,
  ROUND(AVG(c.overall_score),1) avg_score
FROM divisions d
JOIN regions r ON d.region_id=r.region_id
LEFT JOIN schools s ON s.division_id=d.division_id
LEFT JOIN sbm_cycles c ON c.school_id=s.school_id AND c.sy_id=?
WHERE 1=1";
$params = [$sy['sy_id']??0];
if ($regionId) { $divQuery .= " AND d.region_id=?"; $params[] = $regionId; }
$divQuery .= " GROUP BY d.division_id ORDER BY avg_score DESC";
$divisions = $db->prepare($divQuery); $divisions->execute($params); $divisions = $divisions->fetchAll();

// Maturity distribution across region
$matDist = $db->query("SELECT maturity_level, COUNT(*) cnt FROM sbm_cycles WHERE status='validated' GROUP BY maturity_level ORDER BY FIELD(maturity_level,'Advanced','Maturing','Developing','Beginning')")->fetchAll();

// Top performing schools
$topSchools = $db->prepare("SELECT s.school_name, d.division_name, c.overall_score, c.maturity_level
  FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id JOIN divisions d ON s.division_id=d.division_id
  WHERE c.status='validated' AND c.sy_id=? ORDER BY c.overall_score DESC LIMIT 10");
$topSchools->execute([$sy['sy_id']??0]); $topSchools = $topSchools->fetchAll();

$totalSchools   = array_sum(array_column($divisions,'school_count'));
$totalValidated = array_sum(array_column($divisions,'validated_count'));
$overallAvg     = count($divisions) ? round(array_sum(array_column($divisions,'avg_score'))/count($divisions),1) : 0;

$pageTitle = 'Regional Dashboard'; $activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Regional SBM Overview</h2>
    <p>Region-wide SBM performance monitoring — SY <?= e($sy['label']??'—') ?></p></div>
</div>

<div class="stats">
  <div class="stat"><div class="stat-ic teal"><?= svgIcon('layers') ?></div><div class="stat-data"><div class="stat-val"><?= count($divisions) ?></div><div class="stat-lbl">Divisions</div></div></div>
  <div class="stat"><div class="stat-ic green"><?= svgIcon('home') ?></div><div class="stat-data"><div class="stat-val"><?= $totalSchools ?></div><div class="stat-lbl">Schools</div></div></div>
  <div class="stat"><div class="stat-ic blue"><?= svgIcon('check-circle') ?></div><div class="stat-data"><div class="stat-val"><?= $totalValidated ?></div><div class="stat-lbl">Validated</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('bar-chart-2') ?></div><div class="stat-data"><div class="stat-val"><?= $overallAvg ?>%</div><div class="stat-lbl">Avg Score</div></div></div>
</div>

<div class="grid2" style="margin-bottom:18px;">
  <div class="card">
    <div class="card-head"><span class="card-title">Division Performance</span></div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Division</th><th>Schools</th><th>Validated</th><th>Avg Score</th><th>Completion</th></tr></thead>
        <tbody>
        <?php foreach ($divisions as $div):
          $completion = $div['school_count'] ? round(($div['validated_count']/$div['school_count'])*100) : 0;
          $color = $div['avg_score']>=76?'var(--g500)':($div['avg_score']>=51?'var(--blue)':($div['avg_score']>=26?'var(--gold)':'var(--red)'));
        ?>
        <tr>
          <td><strong><?= e($div['division_name']) ?></strong></td>
          <td><?= $div['school_count'] ?></td>
          <td><?= $div['validated_count'] ?></td>
          <td><strong style="color:<?= $color ?>;"><?= $div['avg_score']??'—' ?>%</strong></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="prog" style="width:80px;"><div class="prog-fill" style="width:<?= $completion ?>%;background:<?= $color ?>;"></div></div>
              <span style="font-size:12px;"><?= $completion ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><span class="card-title">Regional Maturity Distribution</span></div>
    <div class="card-body">
      <canvas id="matChart" height="220"></canvas>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">Top Performing Schools</span></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>School</th><th>Division</th><th>Score</th><th>Maturity</th></tr></thead>
      <tbody>
      <?php foreach ($topSchools as $i => $s): ?>
      <tr>
        <td style="color:var(--n400);font-weight:700;"><?= $i+1 ?></td>
        <td><strong><?= e($s['school_name']) ?></strong></td>
        <td style="color:var(--n500);font-size:12.5px;"><?= e($s['division_name']) ?></td>
        <td><strong style="color:var(--g600);"><?= number_format($s['overall_score'],1) ?>%</strong></td>
        <td><?= sbmMaturityBadge($s['maturity_level']??'Beginning') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$topSchools): ?><tr><td colspan="5" style="text-align:center;color:var(--n400);padding:24px;">No validated assessments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const matData = <?= json_encode(array_column($matDist,'cnt')) ?>;
const matLabels = <?= json_encode(array_column($matDist,'maturity_level')) ?>;
const matColors = {'Advanced':'#16A34A','Maturing':'#2563EB','Developing':'#D97706','Beginning':'#DC2626'};
new Chart(document.getElementById('matChart'), {
  type: 'doughnut',
  data: { labels: matLabels, datasets: [{ data: matData, backgroundColor: matLabels.map(l=>matColors[l]||'#9CA3AF'), borderWidth: 0 }] },
  options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
