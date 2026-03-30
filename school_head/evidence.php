<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();
$schoolId = $_SESSION['school_id'] ?? 0;
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();
$syId = $sy['sy_id'] ?? 0;

// Get current cycle
$cycle = null;
if ($schoolId) {
    $st = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
    $st->execute([$schoolId,$syId]); $cycle = $st->fetch();
}

// Responses with evidence
$responses = [];
if ($cycle) {
    $st = $db->prepare("SELECT r.*,i.indicator_code,i.indicator_text,d.dimension_name,d.dimension_no,d.color_hex FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE r.cycle_id=? AND r.evidence_text IS NOT NULL ORDER BY d.dimension_no,i.sort_order");
    $st->execute([$cycle['cycle_id']]); $responses = $st->fetchAll();
}

$dimensions = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
$pageTitle = 'Evidence Files'; $activePage = 'evidence.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Evidence & MOV</h2>
    <p>Means of Verification submitted per indicator — SY <?= e($sy['label']??'—') ?></p></div>
  <div class="page-head-actions">
    <a href="self_assessment.php" class="btn btn-primary"><?= svgIcon('edit') ?> Edit Assessment</a>
  </div>
</div>

<?php if (!$cycle): ?>
<div class="alert alert-warning"><?= svgIcon('alert-circle') ?><span>No active assessment cycle. Please start your self-assessment first.</span></div>
<?php else: ?>

<div class="card mb5" style="margin-bottom:16px;">
  <div class="card-body" style="padding:12px 16px;">
    <div class="flex-c" style="gap:16px;flex-wrap:wrap;">
      <?php foreach($dimensions as $d):
        $cnt = 0; foreach($responses as $r) { if($r['dimension_no']==$d['dimension_no']) $cnt++; }
      ?>
      <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;">
        <span style="width:10px;height:10px;border-radius:50%;background:<?= e($d['color_hex']) ?>;flex-shrink:0;"></span>
        <span>D<?= $d['dimension_no'] ?></span>
        <strong style="color:<?= e($d['color_hex']) ?>;"><?= $cnt ?></strong>
      </div>
      <?php endforeach; ?>
      <span style="color:var(--n400);font-size:12px;margin-left:auto;"><?= count($responses) ?> total with evidence</span>
    </div>
  </div>
</div>

<?php if ($responses):
  $lastDim = '';
  foreach ($responses as $r):
    if ($r['dimension_name'] !== $lastDim): $lastDim = $r['dimension_name']; ?>
<div style="margin-top:18px;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
  <span style="width:4px;height:18px;border-radius:2px;background:<?= e($r['color_hex']) ?>;flex-shrink:0;"></span>
  <strong style="font-size:14px;color:var(--n900);">Dimension <?= $r['dimension_no'] ?>: <?= e($r['dimension_name']) ?></strong>
</div>
    <?php endif; ?>
<div style="background:var(--white);border:1px solid var(--n200);border-radius:9px;padding:14px 16px;margin-bottom:8px;border-left:3px solid <?= e($r['color_hex']) ?>;">
  <div class="flex-cb" style="margin-bottom:6px;">
    <span style="font-family:monospace;font-size:11.5px;color:var(--n500);background:var(--n100);padding:2px 7px;border-radius:4px;"><?= e($r['indicator_code']) ?></span>
    <?= sbmRatingBadge($r['rating']) ?>
  </div>
  <p style="font-size:13px;color:var(--n700);margin-bottom:8px;line-height:1.6;"><?= e($r['indicator_text']) ?></p>
  <?php if ($r['evidence_text']): ?>
  <div style="background:var(--n50);border:1px solid var(--n200);border-radius:6px;padding:10px 12px;font-size:12.5px;color:var(--n600);">
    <div style="font-size:10.5px;font-weight:700;color:var(--n400);text-transform:uppercase;margin-bottom:4px;">Evidence / MOV</div>
    <?= nl2br(e($r['evidence_text'])) ?>
  </div>
  <?php endif; ?>
</div>
  <?php endforeach;
else: ?>
<div class="alert alert-info"><?= svgIcon('info') ?><span>No evidence has been submitted yet. Add evidence when filling out the self-assessment.</span></div>
<?php endif; ?>

<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>
