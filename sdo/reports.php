<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo','admin');
$db = getDB();
$divisionId = $_SESSION['division_id'] ?? null;
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();
$syId = $sy['sy_id'] ?? 0;

// All cycles with dim scores for this division
$baseQ = "SELECT c.*, s.school_name, s.classification, sy.label sy_label
  FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.sy_id=?";
$params = [$syId];
if ($divisionId) { $baseQ .= " AND s.division_id=?"; $params[] = $divisionId; }
$baseQ .= " AND c.status IN('submitted','validated') ORDER BY s.school_name";
$stmt = $db->prepare($baseQ); $stmt->execute($params); $cycles = $stmt->fetchAll();

$dimensions = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();

$pageTitle = 'SDO Reports'; $activePage = 'reports.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Division Reports</h2><p>SBM comparative reports for SDO monitoring — SY <?= e($sy['label']??'—') ?></p></div>
  <div class="page-head-actions">
    <button class="btn btn-secondary" onclick="window.print()"><?= svgIcon('download') ?> Print / Export</button>
  </div>
</div>

<!-- Summary Stats -->
<?php
$total = count($cycles); $validated = 0; $avgScore = 0;
foreach ($cycles as $c) { if($c['status']==='validated') $validated++; $avgScore += $c['overall_score']??0; }
$avgScore = $total ? round($avgScore/$total,1) : 0;
?>
<div class="stats">
  <div class="stat"><div class="stat-ic blue"><?= svgIcon('file-text') ?></div><div class="stat-data"><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Reports Submitted</div></div></div>
  <div class="stat"><div class="stat-ic green"><?= svgIcon('check-circle') ?></div><div class="stat-data"><div class="stat-val"><?= $validated ?></div><div class="stat-lbl">Validated</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('bar-chart-2') ?></div><div class="stat-data"><div class="stat-val"><?= $avgScore ?>%</div><div class="stat-lbl">Division Average</div></div></div>
</div>

<!-- Comparative Table -->
<div class="card">
  <div class="card-head">
    <span class="card-title">School SBM Comparative Report</span>
    <div class="search"><span class="si"><?= svgIcon('search') ?></span>
      <input type="text" placeholder="Search school…" oninput="filterTable(this.value,'repTbl')">
    </div>
  </div>
  <div class="tbl-wrap">
    <table id="repTbl">
      <thead>
        <tr>
          <th>School</th>
          <th>Type</th>
          <?php foreach($dimensions as $d): ?>
          <th style="color:<?= e($d['color_hex']) ?>;">D<?= $d['dimension_no'] ?></th>
          <?php endforeach; ?>
          <th>Overall</th>
          <th>Maturity</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($cycles as $c):
        // Get dimension scores
        $dScores = $db->prepare("SELECT dimension_id, percentage FROM sbm_dimension_scores WHERE cycle_id=?");
        $dScores->execute([$c['cycle_id']]); $dScores = $dScores->fetchAll(PDO::FETCH_KEY_PAIR);
      ?>
      <tr>
        <td><strong style="font-size:13px;"><?= e($c['school_name']) ?></strong></td>
        <td><span class="pill pill-teacher" style="font-size:10px;"><?= e($c['classification']) ?></span></td>
        <?php foreach($dimensions as $d):
          $pct = $dScores[$d['dimension_id']] ?? null;
          $color = $pct===null?'var(--n400)':($pct>=76?'var(--g600)':($pct>=51?'var(--blue)':($pct>=26?'var(--gold)':'var(--red)')));
        ?>
        <td style="font-weight:700;color:<?= $color ?>;font-size:12.5px;text-align:center;"><?= $pct!==null?number_format($pct,0).'%':'—' ?></td>
        <?php endforeach; ?>
        <td><strong style="color:var(--g600);font-size:14px;"><?= $c['overall_score']?number_format($c['overall_score'],1).'%':'—' ?></strong></td>
        <td><?= sbmMaturityBadge($c['maturity_level']??'Beginning') ?></td>
        <td><span class="pill pill-<?= e($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$cycles): ?><tr><td colspan="<?= 5+count($dimensions) ?>" style="text-align:center;padding:24px;color:var(--n400);">No reports submitted yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt5" style="margin-top:18px;">
  <div class="card-head"><span class="card-title">Dimension Performance Summary</span></div>
  <div class="card-body"><canvas id="dimChart" height="100"></canvas></div>
</div>

<script>
const dimLabels = <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'].': '.$d['dimension_name'],$dimensions)) ?>;
const dimColors = <?= json_encode(array_column($dimensions,'color_hex')) ?>;
<?php
$dimAvgs = [];
foreach ($dimensions as $d) {
    $st = $db->prepare("SELECT ROUND(AVG(ds.percentage),1) FROM sbm_dimension_scores ds JOIN sbm_cycles c ON ds.cycle_id=c.cycle_id JOIN schools s ON c.school_id=s.school_id WHERE ds.dimension_id=? AND c.sy_id=?".($divisionId?" AND s.division_id=$divisionId":""));
    $st->execute([$d['dimension_id'],$syId]); $dimAvgs[] = $st->fetchColumn()?:0;
}
?>
const dimAvgs = <?= json_encode($dimAvgs) ?>;
new Chart(document.getElementById('dimChart'),{
  type:'bar',
  data:{labels:dimLabels,datasets:[{label:'Average Score (%)',data:dimAvgs,backgroundColor:dimColors.map(c=>c+'33'),borderColor:dimColors,borderWidth:2,borderRadius:6}]},
  options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}}}}
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
