<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
$db = getDB();

$totalSchools   = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$totalUsers     = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$totalCycles    = $db->query("SELECT COUNT(*) FROM sbm_cycles")->fetchColumn();
$submitted      = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status IN('submitted','validated')")->fetchColumn();
$validated      = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='validated'")->fetchColumn();
$inProgress     = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='in_progress'")->fetchColumn();

// Maturity distribution
$maturity = $db->query("SELECT maturity_level, COUNT(*) cnt FROM sbm_cycles WHERE maturity_level IS NOT NULL GROUP BY maturity_level")->fetchAll();

// Recent cycles
$recentCycles = $db->query("
  SELECT c.*, s.school_name, sy.label sy_label
  FROM sbm_cycles c
  JOIN schools s ON c.school_id=s.school_id
  JOIN school_years sy ON c.sy_id=sy.sy_id
  ORDER BY c.created_at DESC LIMIT 8
")->fetchAll();

// Dimension avg scores
$dimScores = $db->query("
  SELECT d.dimension_no, d.dimension_name, d.color_hex,
         ROUND(AVG(ds.percentage),1) avg_pct,
         COUNT(ds.score_id) cnt
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds ON d.dimension_id=ds.dimension_id
  GROUP BY d.dimension_id ORDER BY d.dimension_no
")->fetchAll();

// Recent activity
$recentActivity = $db->query("
  SELECT l.*, u.full_name FROM activity_log l
  LEFT JOIN users u ON l.user_id=u.user_id
  ORDER BY l.created_at DESC LIMIT 6
")->fetchAll();

if (!function_exists('sbmMaturityLevel')) {
    function sbmMaturityLevel(float $pct): array {
        if ($pct >= 90) return ['label'=>'Advanced',   'color'=>'#16A34A','bg'=>'#DCFCE7'];
        if ($pct >= 75) return ['label'=>'Proficient', 'color'=>'#2563EB','bg'=>'#DBEAFE'];
        if ($pct >= 50) return ['label'=>'Developing', 'color'=>'#D97706','bg'=>'#FEF3C7'];
        return                 ['label'=>'Beginning',  'color'=>'#DC2626','bg'=>'#FEE2E2'];
    }
}

$pageTitle = 'Admin Dashboard'; $activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text">
    <h2>System Dashboard</h2>
    <p>Overview of SBM monitoring across all schools — <?= date('F Y') ?></p>
  </div>
  <div class="page-head-actions">
    <a href="reports.php" class="btn btn-secondary"><?= svgIcon('file-text') ?> Reports</a>
    <a href="schools.php" class="btn btn-primary"><?= svgIcon('home') ?> Manage Schools</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><div class="stat-ic green"><?= svgIcon('home') ?></div><div class="stat-data"><div class="stat-val" data-live="total-schools"><?= $totalSchools ?></div><div class="stat-lbl">Total Schools</div></div></div>
  <div class="stat"><div class="stat-ic blue"><?= svgIcon('check-circle') ?></div><div class="stat-data"><div class="stat-val" data-live="total-cycles"><?= $totalCycles ?></div><div class="stat-lbl">Assessment Cycles</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('trending-up') ?></div><div class="stat-data"><div class="stat-val" data-live="submitted"><?= $submitted ?></div><div class="stat-lbl">Submitted / Validated</div></div></div>
  <div class="stat"><div class="stat-ic purple"><?= svgIcon('users') ?></div><div class="stat-data"><div class="stat-val" data-live="total-users"><?= $totalUsers ?></div><div class="stat-lbl">Active Users</div></div></div>
  <div class="stat"><div class="stat-ic teal"><?= svgIcon('check') ?></div><div class="stat-data"><div class="stat-val" data-live="validated"><?= $validated ?></div><div class="stat-lbl">Validated</div></div></div>
  <div class="stat"><div class="stat-ic dark"><?= svgIcon('bar-chart-2') ?></div><div class="stat-data"><div class="stat-val" data-live="in-progress"><?= $inProgress ?></div><div class="stat-lbl">In Progress</div></div></div>
</div>

<div class="grid2-3" style="gap:18px;margin-bottom:20px;">
  <!-- Dimension Performance -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Dimension Performance (System Average)</span>
    </div>
    <div class="card-body">
      <?php foreach($dimScores as $d): ?>
      <?php $pct = floatval($d['avg_pct']); $mat = sbmMaturityLevel($pct); ?>
      <div style="margin-bottom:14px;">
        <div class="flex-cb" style="margin-bottom:5px;">
          <div style="font-size:13px;font-weight:600;color:var(--n800);">
            <span style="display:inline-block;width:20px;height:20px;border-radius:5px;background:<?= e($d['color_hex']) ?>;opacity:.15;vertical-align:middle;margin-right:6px;"></span>
            Dim <?= $d['dimension_no'] ?>: <?= e($d['dimension_name']) ?>
          </div>
          <span style="font-size:13px;font-weight:700;color:<?= e($d['color_hex']) ?>;"><?= $pct > 0 ? $pct.'%' : '—' ?></span>
        </div>
        <div class="prog">
          <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= e($d['color_hex']) ?>;"></div>
        </div>
        <?php if($pct>0): ?><div style="font-size:11px;color:var(--n400);margin-top:3px;"><?= $mat['label'] ?></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Maturity Distribution + Chart -->
  <div>
    <div class="card mb4">
      <div class="card-head"><span class="card-title">Maturity Distribution</span></div>
      <div class="card-body" style="padding:14px;">
        <canvas id="maturityChart" height="200"></canvas>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><span class="card-title">Recent Activity</span></div>
      <div class="card-body" style="padding:10px 14px;" id="live-activity-feed">
        <?php foreach($recentActivity as $log): ?>
        <div class="flex-cb" style="padding:7px 0;border-bottom:1px solid var(--n100);">
          <div style="font-size:12.5px;color:var(--n700);"><?= e($log['full_name']??'System') ?> — <span style="color:var(--n500);"><?= e($log['action']) ?></span></div>
          <div style="font-size:11px;color:var(--n400);"><?= timeAgo($log['created_at']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$recentActivity): ?>
        <p style="font-size:13px;color:var(--n400);text-align:center;padding:16px 0;">No activity yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recent Assessment Cycles -->
<div class="card">
  <div class="card-head">
    <span class="card-title">Recent Assessment Cycles</span>
    <a href="assessment.php" class="btn btn-secondary btn-sm">View All</a>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>School</th><th>School Year</th><th>Status</th><th>Overall Score</th><th>Maturity Level</th><th>Last Updated</th><th></th></tr></thead>
      <tbody>
      <?php foreach($recentCycles as $c): ?>
      <tr>
        <td><strong style="font-size:13px;"><?= e($c['school_name']) ?></strong></td>
        <td style="font-size:13px;"><?= e($c['sy_label']) ?></td>
        <td><span class="pill pill-<?= e($c['status']) ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
        <td style="font-size:13px;font-weight:700;"><?= $c['overall_score'] ? $c['overall_score'].'%' : '—' ?></td>
        <td><?php if($c['maturity_level']): ?><span class="pill pill-<?= e($c['maturity_level']) ?>"><?= e($c['maturity_level']) ?></span><?php else: ?>—<?php endif; ?></td>
        <td style="font-size:12px;color:var(--n400);"><?= timeAgo($c['created_at']) ?></td>
        <td><a href="assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$recentCycles): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--n400);padding:24px;">No assessments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const matData = <?= json_encode(array_column($maturity,'cnt','maturity_level')) ?>;
const labels  = ['Beginning','Developing','Maturing','Advanced'];
const colors  = ['#DC2626','#D97706','#2563EB','#16A34A'];
const values  = labels.map(l => matData[l] || 0);
new Chart(document.getElementById('maturityChart'), {
  type: 'doughnut',
  data: {labels, datasets:[{data:values, backgroundColor:colors, borderWidth:2, borderColor:'#fff'}]},
  options: {
    responsive:true, maintainAspectRatio:true,
    plugins:{legend:{position:'bottom',labels:{font:{family:"'DM Sans',sans-serif",size:12},padding:10}}},
    cutout:'65%'
  }
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>