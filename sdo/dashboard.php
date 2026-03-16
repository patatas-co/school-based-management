<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo');
$db = getDB();

$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$totalSchools = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$submitted    = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id=? AND status IN('submitted','validated')")->execute([$syId]) ? $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id=? AND status IN('submitted','validated')")->execute([$syId]) : 0;
$st = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id=? AND status IN('submitted','validated')"); $st->execute([$syId]); $submitted = $st->fetchColumn();
$st2 = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id=? AND status='validated'"); $st2->execute([$syId]); $validated = $st2->fetchColumn();
$st3 = $db->prepare("SELECT COUNT(*) FROM sbm_cycles WHERE sy_id=? AND status='in_progress'"); $st3->execute([$syId]); $inProg = $st3->fetchColumn();
$st4 = $db->prepare("SELECT ROUND(AVG(overall_score),1) FROM sbm_cycles WHERE sy_id=? AND overall_score IS NOT NULL"); $st4->execute([$syId]); $avgScore = $st4->fetchColumn();

$pendingValidation = $db->prepare("SELECT c.*,s.school_name,s.classification FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id WHERE c.sy_id=? AND c.status='submitted' ORDER BY c.submitted_at DESC");
$pendingValidation->execute([$syId]); $pending = $pendingValidation->fetchAll();

$schoolPerf = $db->prepare("SELECT s.school_name,c.overall_score,c.maturity_level,c.status FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id WHERE c.sy_id=? AND c.overall_score IS NOT NULL ORDER BY c.overall_score DESC LIMIT 10");
$schoolPerf->execute([$syId]); $schoolPerf = $schoolPerf->fetchAll();

$pageTitle = 'SDO Dashboard'; $activePage = 'dashboard.php';

include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>SDO Monitoring Dashboard</h2><p>Schools Division Office — SBM monitoring overview</p></div>
  <div class="page-head-actions">
    <a href="monitoring.php" class="btn btn-secondary"><?= svgIcon('eye') ?> All Schools</a>
    <a href="ta.php" class="btn btn-primary"><?= svgIcon('briefcase') ?> Technical Assistance</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><div class="stat-ic green"><?= svgIcon('home') ?></div><div class="stat-data"><div class="stat-val"><?= $totalSchools ?></div><div class="stat-lbl">Total Schools</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('check-circle') ?></div><div class="stat-data"><div class="stat-val"><?= $submitted ?></div><div class="stat-lbl">Submitted</div></div></div>
  <div class="stat"><div class="stat-ic blue"><?= svgIcon('bar-chart-2') ?></div><div class="stat-data"><div class="stat-val"><?= $inProg ?></div><div class="stat-lbl">In Progress</div></div></div>
  <div class="stat"><div class="stat-ic teal"><?= svgIcon('check') ?></div><div class="stat-data"><div class="stat-val"><?= $validated ?></div><div class="stat-lbl">Validated</div></div></div>
  <div class="stat"><div class="stat-ic purple"><?= svgIcon('trending-up') ?></div><div class="stat-data"><div class="stat-val"><?= $avgScore ? $avgScore.'%' : '—' ?></div><div class="stat-lbl">Division Avg Score</div></div></div>
</div>

<div class="grid2-3" style="gap:18px;">
  <!-- Pending validation -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Pending Validation <span style="font-weight:400;color:var(--n400);">(<?= count($pending) ?>)</span></span>
      <a href="../admin/assessment.php?status=submitted" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <?php if(!$pending): ?>
    <div class="card-body" style="text-align:center;padding:32px;color:var(--n400);">No pending submissions.</div>
    <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>School</th><th>Submitted</th><th>Score</th><th></th></tr></thead>
        <tbody>
        <?php foreach($pending as $c): ?>
        <tr>
          <td><strong style="font-size:13px;"><?= e($c['school_name']) ?></strong><div style="font-size:11.5px;color:var(--n400);"><?= e($c['classification']) ?></div></td>
          <td style="font-size:12.5px;color:var(--n500);"><?= $c['submitted_at'] ? date('M d',strtotime($c['submitted_at'])) : '—' ?></td>
          <td style="font-weight:700;color:var(--g700);"><?= $c['overall_score'] ? $c['overall_score'].'%' : '—' ?></td>
          <td><a href="../admin/view_assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-success btn-sm"><?= svgIcon('check') ?> Review</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Top schools -->
  <div class="card">
    <div class="card-head"><span class="card-title">School Rankings</span></div>
    <div class="card-body" style="padding:0;">
      <?php foreach($schoolPerf as $i => $sc): ?>
      <?php $mat = sbmMaturityLevel(floatval($sc['overall_score'])); ?>
      <div class="flex-cb" style="padding:10px 18px;border-bottom:1px solid var(--n100);">
        <div class="flex-c" style="gap:9px;">
          <span style="width:22px;height:22px;border-radius:50%;background:var(--g100);color:var(--g700);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $i+1 ?></span>
          <span style="font-size:12.5px;font-weight:600;"><?= e(substr($sc['school_name'],0,28)) ?><?= strlen($sc['school_name'])>28?'…':'' ?></span>
        </div>
        <div class="flex-c" style="gap:6px;">
          <strong style="font-size:14px;color:<?= $mat['color'] ?>;"><?= $sc['overall_score'] ?>%</strong>
          <span class="pill pill-<?= e($sc['maturity_level']) ?>"><?= e($sc['maturity_level']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(!$schoolPerf): ?><div style="padding:24px;text-align:center;color:var(--n400);">No data yet.</div><?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
