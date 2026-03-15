<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('external_stakeholder');
$db  = getDB();
$uid = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'] ?? 0;

$school = null;
if ($schoolId) {
    $st = $db->prepare("SELECT * FROM schools WHERE school_id=?");
    $st->execute([$schoolId]); $school = $st->fetch();
}

$sy = $db->query(
    "SELECT * FROM school_years WHERE is_current=1 LIMIT 1"
)->fetch();

$cycle = null;
if ($schoolId && $sy) {
    $st = $db->prepare("
        SELECT * FROM sbm_cycles 
        WHERE school_id=? AND sy_id=? LIMIT 1
    ");
    $st->execute([$schoolId, $sy['sy_id']]); 
    $cycle = $st->fetch();
}

// My submission status
$mySubmission = null;
if ($cycle) {
    $st = $db->prepare("
        SELECT * FROM stakeholder_submissions 
        WHERE cycle_id=? AND stakeholder_id=?
    ");
    $st->execute([$cycle['cycle_id'], $uid]);
    $mySubmission = $st->fetch();
}

$anns = $db->query("
    SELECT a.*, u.full_name poster 
    FROM announcements a 
    JOIN users u ON a.posted_by=u.user_id 
    WHERE a.is_published=1 
      AND a.target_role IN('all','school_head') 
    ORDER BY a.created_at DESC LIMIT 5
")->fetchAll();

$pageTitle  = 'Stakeholder Dashboard';
$activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text">
    <h2>Welcome, <?= e(explode(' ', 
        trim($_SESSION['full_name']))[0]) ?></h2>
    <p>External Stakeholder Portal — 
       <?= e($school['school_name'] ?? 'No school assigned') ?>
    </p>
  </div>
  <div class="page-head-actions">
    <?php if (!$mySubmission || 
              $mySubmission['status'] !== 'submitted'): ?>
    <a href="self_assessment.php" class="btn btn-primary">
        <?= svgIcon('check-circle') ?> Fill Assessment
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="stats">
  <div class="stat">
    <div class="stat-ic green"><?= svgIcon('check-circle') ?></div>
    <div class="stat-data">
      <div class="stat-val">
          <?= $mySubmission 
              ? ucfirst($mySubmission['status']) 
              : 'Not Started' ?>
      </div>
      <div class="stat-lbl">My Submission</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic blue"><?= svgIcon('layers') ?></div>
    <div class="stat-data">
      <div class="stat-val">
          <?= $mySubmission ? $mySubmission['response_count'] : 0 ?>
      </div>
      <div class="stat-lbl">Indicators Rated</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic gold"><?= svgIcon('calendar') ?></div>
    <div class="stat-data">
      <div class="stat-val">
          <?= e($sy['label'] ?? '—') ?>
      </div>
      <div class="stat-lbl">School Year</div>
    </div>
  </div>
</div>

<?php if ($mySubmission && 
          $mySubmission['status'] === 'submitted'): ?>
<div class="alert alert-success" style="margin-bottom:16px;">
    <?= svgIcon('check-circle') ?>
    <span>
        Your assessment was submitted on 
        <strong>
            <?= date('F d, Y g:i A', 
                strtotime($mySubmission['submitted_at'])) ?>
        </strong>. 
        Thank you for your participation.
    </span>
</div>
<?php else: ?>
<div class="alert alert-info" style="margin-bottom:16px;">
    <?= svgIcon('info') ?>
    <span>
        You have 
        <strong>
            <?= count(STAKEHOLDER_INDICATOR_CODES ?? []) ?> 
            indicators
        </strong> 
        to rate as part of the SBM self-assessment.
        <a href="self_assessment.php" 
           style="color:var(--blue);font-weight:600;">
            Start now →
        </a>
    </span>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
      <span class="card-title">Announcements</span>
  </div>
  <div class="card-body">
  <?php if ($anns): foreach ($anns as $a): ?>
    <div style="border-bottom:1px solid var(--n100);padding:12px 0;">
      <div class="flex-cb" style="margin-bottom:4px;">
        <strong style="font-size:13.5px;color:var(--n900);">
            <?= e($a['title']) ?>
        </strong>
        <span class="pill pill-<?= e($a['category']) ?>" 
              style="font-size:10px;">
            <?= ucfirst($a['category']) ?>
        </span>
      </div>
      <p style="font-size:13px;color:var(--n600);line-height:1.6;">
          <?= nl2br(e(substr($a['content'],0,200))) ?>
          <?= strlen($a['content'])>200 ? '…' : '' ?>
      </p>
      <div style="font-size:11px;color:var(--n400);margin-top:6px;">
          <?= e($a['poster']) ?> &nbsp;·&nbsp; 
          <?= timeAgo($a['created_at']) ?>
      </div>
    </div>
  <?php endforeach; else: ?>
    <p style="color:var(--n400);font-size:13px;">
        No announcements yet.
    </p>
  <?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>