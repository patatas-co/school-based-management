<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('teacher');
$db  = getDB();
$uid = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'] ?? 0;

$school = $schoolId ? $db->prepare("SELECT * FROM schools WHERE school_id=?") : null;
if ($school) { $school->execute([$schoolId]); $school = $school->fetch(); }

// Current SY
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();

// Latest cycle for this school
$cycle = null;
if ($schoolId && $sy) {
    $st = $db->prepare("SELECT c.*, s.school_name FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id WHERE c.school_id=? AND c.sy_id=? LIMIT 1");
    $st->execute([$schoolId, $sy['sy_id']]); $cycle = $st->fetch();
}

// Dimension scores
$dimScores = [];
if ($cycle) {
    $st = $db->prepare("SELECT ds.*, d.dimension_name, d.dimension_no, d.color_hex FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
    $st->execute([$cycle['cycle_id']]); $dimScores = $st->fetchAll();
}

// Announcements
$anns = $db->query("SELECT a.*, u.full_name poster FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.is_published=1 AND a.target_role IN('all','teacher') ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Teacher Dashboard'; $activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text">
    <h2>Welcome, <?= e(explode(' ', trim($_SESSION['full_name']))[0]) ?></h2>
    <p>View your school's SBM progress and announcements.</p>
  </div>
</div>

<?php if ($school): ?>
<div class="alert alert-info mb5" style="margin-bottom:16px;">
  <?= svgIcon('home') ?>
  <span><strong><?= e($school['school_name']) ?></strong> &nbsp;·&nbsp; SY <?= e($sy['label']??'—') ?> &nbsp;·&nbsp;
  <?php if ($cycle): ?>
    Assessment Status: <?= sbmMaturityBadge($cycle['maturity_level'] ?? 'Beginning') ?>
  <?php else: ?>
    No active assessment cycle yet.
  <?php endif; ?>
  </span>
</div>
<?php endif; ?>

<?php if ($dimScores): ?>
<div class="card mb5" style="margin-bottom:18px;">
  <div class="card-head"><span class="card-title">SBM Dimension Progress — SY <?= e($sy['label']??'') ?></span></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
    <?php foreach ($dimScores as $ds): ?>
    <div style="border:1px solid #E5E7EB;border-radius:9px;padding:14px;border-top:3px solid <?= e($ds['color_hex']) ?>;">
      <div style="font-size:12px;font-weight:700;color:var(--n500);margin-bottom:4px;">Dimension <?= $ds['dimension_no'] ?></div>
      <div style="font-size:13.5px;font-weight:600;color:var(--n800);margin-bottom:10px;"><?= e($ds['dimension_name']) ?></div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
        <span style="font-size:13px;color:var(--n600);">Score</span>
        <strong style="font-size:14px;color:<?= e($ds['color_hex']) ?>;"><?= number_format($ds['percentage'],1) ?>%</strong>
      </div>
      <div class="prog"><div class="prog-fill" style="width:<?= min(100,$ds['percentage']) ?>%;background:<?= e($ds['color_hex']) ?>;"></div></div>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>
<?php else: ?>
<div class="alert alert-warning mb5" style="margin-bottom:16px;">
  <?= svgIcon('alert-circle') ?>
  <span>No SBM assessment data available yet for the current school year.</span>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head"><span class="card-title">Announcements</span></div>
  <div class="card-body">
  <?php if ($anns): foreach ($anns as $a): ?>
    <div style="border-bottom:1px solid var(--n100);padding:12px 0;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
        <strong style="font-size:13.5px;color:var(--n900);"><?= e($a['title']) ?></strong>
        <span class="pill pill-<?= e($a['category']) ?>" style="font-size:10px;"><?= ucfirst($a['category']) ?></span>
      </div>
      <p style="font-size:13px;color:var(--n600);line-height:1.6;"><?= nl2br(e(substr($a['content'],0,200))) ?><?= strlen($a['content'])>200?'…':'' ?></p>
      <div style="font-size:11px;color:var(--n400);margin-top:6px;"><?= e($a['poster']) ?> &nbsp;·&nbsp; <?= timeAgo($a['created_at']) ?></div>
    </div>
  <?php endforeach; else: ?>
    <p style="color:var(--n400);font-size:13px;">No announcements yet.</p>
  <?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
