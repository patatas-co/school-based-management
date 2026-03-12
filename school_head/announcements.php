<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','teacher');
$db = getDB();

$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.target_role IN('all','school_head') AND a.is_published=1 ORDER BY a.created_at DESC")->fetchAll();
$catColors = ['general'=>'var(--g400)','policy'=>'var(--purple)','deadline'=>'var(--red)','advisory'=>'var(--gold)','emergency'=>'var(--red)'];

$pageTitle = 'Announcements'; $activePage = 'announcements.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Announcements</h2><p>Notices and advisories from the Division and Regional Office.</p></div>
</div>

<?php if(!$anns): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:48px;color:var(--n400);">No announcements at this time.</div></div>
<?php endif; ?>

<div style="display:flex;flex-direction:column;gap:12px;">
<?php foreach($anns as $a): ?>
<div class="card" style="border-left:4px solid <?= $catColors[$a['category']] ?? 'var(--g400)' ?>;">
  <div class="card-body" style="padding:16px 20px;">
    <div class="flex-c" style="gap:8px;margin-bottom:10px;">
      <span class="pill pill-<?= e($a['category']) ?>"><?= ucfirst($a['category']) ?></span>
      <?php if($a['category']==='emergency'): ?><span class="pill" style="background:#FEE2E2;color:#DC2626;border-color:#FECACA;animation:pulse 1s infinite;">🚨 Urgent</span><?php endif; ?>
    </div>
    <h3 style="font-size:15px;font-weight:700;color:var(--n900);margin-bottom:8px;"><?= e($a['title']) ?></h3>
    <p style="font-size:13.5px;color:var(--n600);line-height:1.7;"><?= nl2br(e($a['content'])) ?></p>
    <div style="font-size:11.5px;color:var(--n400);margin-top:12px;padding-top:10px;border-top:1px solid var(--n100);">
      Posted by <strong style="color:var(--n600);"><?= e($a['full_name']) ?></strong> &nbsp;·&nbsp; <?= timeAgo($a['created_at']) ?> &nbsp;·&nbsp; <?= date('M d, Y',strtotime($a['created_at'])) ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
