<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$schoolId = $_SESSION['school_id'] ?? 0;
$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

$school  = $schoolId ? $db->prepare("SELECT * FROM schools WHERE school_id=?") : null;
if ($school) { $school->execute([$schoolId]); $school = $school->fetch(); }

$cycle = $db->prepare("SELECT c.*,sy.label sy_label FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND c.sy_id=?");
$cycle->execute([$schoolId,$syId]); $cycle = $cycle->fetch();

$dimScores = [];
$responses = [];
if ($cycle) {
    $ds = $db->prepare("SELECT ds.*,d.dimension_no,d.dimension_name,d.color_hex FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
    $ds->execute([$cycle['cycle_id']]); $dimScores = $ds->fetchAll();
    $resp = $db->prepare("SELECT r.*,i.indicator_code,i.indicator_text,i.mov_guide,d.dimension_no,d.dimension_name,d.color_hex FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE r.cycle_id=? ORDER BY d.dimension_no,i.sort_order");
    $resp->execute([$cycle['cycle_id']]); $responses = $resp->fetchAll();
}
$grouped = [];
foreach($responses as $r) $grouped[$r['dimension_no']][] = $r;

$ratingLabels = [1=>'Not Yet Manifested',2=>'Emerging',3=>'Developing',4=>'Always Manifested'];
$ratingColors = [1=>'#DC2626',2=>'#D97706',3=>'#2563EB',4=>'#16A34A'];

$pageTitle = 'Reports'; $activePage = 'reports.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>My SBM Report</h2><p>Self-assessment results for <?= e($school['school_name'] ?? '') ?> — <?= $cycle ? e($cycle['sy_label']) : 'Current SY' ?></p></div>
  <div class="page-head-actions">
    <button class="btn btn-secondary" onclick="window.print()"><?= svgIcon('download') ?> Print / PDF</button>
  </div>
</div>

<?php if(!$cycle): ?>
<div class="alert alert-warning"><?= svgIcon('alert-circle') ?> No assessment data yet. <a href="self_assessment.php" style="font-weight:700;">Start your self-assessment →</a></div>
<?php else: ?>

<div id="printReport">
<!-- School header -->
<div style="text-align:center;margin-bottom:20px;padding:20px;background:var(--white);border:1px solid var(--n200);border-radius:var(--radius);">
  <p style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--n500);margin-bottom:6px;">Republic of the Philippines · Department of Education</p>
  <h2 style="font-size:20px;font-weight:700;color:var(--n900);margin-bottom:3px;">SBM Self-Assessment Report — Annex A</h2>
  <p style="font-size:13px;color:var(--n600);margin-bottom:16px;"><?= e($cycle['sy_label']) ?></p>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-width:560px;margin:0 auto;text-align:left;">
    <div style="background:var(--n50);border-radius:8px;padding:10px 14px;">
      <div style="font-size:10.5px;color:var(--n400);text-transform:uppercase;letter-spacing:.06em;">School</div>
      <div style="font-size:13px;font-weight:700;color:var(--n900);margin-top:2px;"><?= e($school['school_name']) ?></div>
    </div>
    <div style="background:var(--n50);border-radius:8px;padding:10px 14px;">
      <div style="font-size:10.5px;color:var(--n400);text-transform:uppercase;letter-spacing:.06em;">School Head</div>
      <div style="font-size:13px;font-weight:700;color:var(--n900);margin-top:2px;"><?= e($school['school_head_name'] ?? '—') ?></div>
    </div>
    <div style="background:<?= $cycle['maturity_level'] ? sbmMaturityLevel(floatval($cycle['overall_score']))['bg'] : 'var(--n50)' ?>;border-radius:8px;padding:10px 14px;">
      <div style="font-size:10.5px;color:var(--n400);text-transform:uppercase;letter-spacing:.06em;">Overall Score</div>
      <div style="font-size:18px;font-weight:800;color:<?= $cycle['maturity_level'] ? sbmMaturityLevel(floatval($cycle['overall_score']))['color'] : 'var(--n900)' ?>;margin-top:2px;"><?= $cycle['overall_score'] ? $cycle['overall_score'].'%' : '—' ?></div>
      <?php if($cycle['maturity_level']): ?><div style="font-size:11px;font-weight:600;color:<?= sbmMaturityLevel(floatval($cycle['overall_score']))['color'] ?>;"><?= e($cycle['maturity_level']) ?></div><?php endif; ?>
    </div>
  </div>
</div>

<!-- Dimension chart + table -->
<div class="grid2" style="margin-bottom:20px;">
  <div class="card">
    <div class="card-head"><span class="card-title">Dimension Summary</span></div>
    <div class="card-body"><canvas id="dimRadar" height="220"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><span class="card-title">Scores by Dimension</span></div>
    <div class="card-body" style="padding:0;">
      <?php foreach($dimScores as $ds): ?>
      <?php $mat = sbmMaturityLevel(floatval($ds['percentage'])); ?>
      <div style="padding:11px 18px;border-bottom:1px solid var(--n100);">
        <div class="flex-cb" style="margin-bottom:5px;">
          <span style="font-size:13px;font-weight:600;color:<?= e($ds['color_hex']) ?>;">D<?= $ds['dimension_no'] ?></span>
          <span style="font-size:13px;font-weight:700;color:<?= $mat['color'] ?>;"><?= $ds['percentage'] ?>%</span>
        </div>
        <div style="font-size:12px;color:var(--n600);margin-bottom:6px;"><?= e($ds['dimension_name']) ?></div>
        <div class="prog"><div class="prog-fill" style="width:<?= $ds['percentage'] ?>%;background:<?= $ds['color_hex'] ?>;"></div></div>
        <div style="font-size:11px;color:var(--n400);margin-top:3px;"><?= $mat['label'] ?> · <?= $ds['raw_score'] ?>/<?= $ds['max_score'] ?> pts</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Full checklist -->
<?php foreach($grouped as $dimNo => $inds): ?>
<?php $first = $inds[0]; ?>
<div class="card" style="margin-bottom:14px;">
  <div class="card-head" style="background:<?= htmlspecialchars($first['color_hex']) ?>15;">
    <span class="card-title">Dimension <?= $dimNo ?>: <?= e($first['dimension_name']) ?></span>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Code</th><th>Indicator</th><th>Rating</th><th>Evidence / MOV</th></tr></thead>
      <tbody>
      <?php foreach($inds as $ind): ?>
      <tr>
        <td style="font-weight:700;font-size:12px;"><?= e($ind['indicator_code']) ?></td>
        <td style="font-size:12.5px;line-height:1.5;"><?= e($ind['indicator_text']) ?></td>
        <td>
          <span style="display:inline-flex;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $ratingColors[$ind['rating']] ?>22;color:<?= $ratingColors[$ind['rating']] ?>;">
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
<?php endforeach; ?>
</div>

<script>
const dimLabels = <?= json_encode(array_map(fn($d)=>'D'.$d['dimension_no'],$dimScores)) ?>;
const dimValues = <?= json_encode(array_map(fn($d)=>floatval($d['percentage']),$dimScores)) ?>;
const dimColors = <?= json_encode(array_column($dimScores,'color_hex')) ?>;
new Chart(document.getElementById('dimRadar'),{
  type:'radar',
  data:{labels:dimLabels,datasets:[{label:'Score %',data:dimValues,backgroundColor:'rgba(22,163,74,.15)',borderColor:'#16A34A',pointBackgroundColor:dimColors,pointRadius:4,borderWidth:2}]},
  options:{scales:{r:{min:0,max:100,ticks:{font:{size:10}}}},plugins:{legend:{display:false}}}
});
</script>
<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>