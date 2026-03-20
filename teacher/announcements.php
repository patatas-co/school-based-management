<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('teacher');
$db = getDB();

$filterCat = $_GET['cat'] ?? '';
$sql = "SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.target_role IN('all','teacher')";
$params = [];
if ($filterCat) { $sql .= " AND a.category=?"; $params[] = $filterCat; }
$sql .= " ORDER BY a.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$anns = $stmt->fetchAll();

// Count by category for the teacher part
$catCounts = $db->query("SELECT category, COUNT(*) cnt FROM announcements WHERE target_role IN('all','teacher') GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Announcements'; $activePage = 'announcements.php';
include __DIR__.'/../includes/header.php';

$catColors = ['general'=>'#16A34A','policy'=>'#7C3AED','deadline'=>'#DC2626','advisory'=>'#D97706','emergency'=>'#DC2626'];
?>
<style>
.ann-v2 {
  background: var(--white);
  border: 1px solid var(--n-200);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-xs);
  margin-bottom: 12px;
  transition: box-shadow 140ms, transform 140ms;
}
.ann-v2:hover { box-shadow: var(--shadow-sm); transform: translateY(-1px); }
.ann-v2-stripe { height: 4px; }
.ann-v2-body { padding: 18px 24px; }
.ann-v2-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
.ann-v2-pills { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ann-v2-title { font-size: 16px; font-weight: 700; color: var(--n-900); margin-bottom: 8px; line-height: 1.4; }
.ann-v2-content { font-size: 14px; color: var(--n-600); line-height: 1.8; }
.ann-v2-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--n-100); font-size: 12.5px; color: var(--n-400); }
.cat-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Communication</div>
    <div class="ph2-title">Announcements</div>
    <div class="ph2-sub">Recent notices and advisories from the Division and Regional Office.</div>
  </div>
</div>

<!-- Category filter tabs -->
<div class="status-tabs" style="margin-bottom:24px;">
  <a href="announcements.php" class="status-tab <?= !$filterCat ? 'active' : '' ?>">
    All <span class="status-tab-count"><?= array_sum($catCounts) ?></span>
  </a>
  <?php foreach(['general','policy','deadline','advisory','emergency'] as $c): ?>
  <a href="announcements.php?cat=<?= $c ?>" class="status-tab <?= $filterCat===$c ? 'active' : '' ?>">
    <span class="cat-dot" style="background:<?= $catColors[$c] ?>;"></span>
    <?= ucfirst($c) ?>
    <?php if($catCounts[$c] ?? 0): ?><span class="status-tab-count"><?= $catCounts[$c] ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if(!$anns): ?>
<div class="card">
  <div class="empty-state">
    <div class="empty-icon"><?= svgIcon('bell') ?></div>
    <div class="empty-title">No announcements yet</div>
    <div class="empty-sub">Check back later for important notices and updates.</div>
  </div>
</div>
<?php else: ?>
<div id="annFeed">
<?php foreach($anns as $a):
  $col = $catColors[$a['category']] ?? '#16A34A';
?>
<div class="ann-v2" id="ann<?= $a['ann_id'] ?>">
  <div class="ann-v2-stripe" style="background:<?= $col ?>;"></div>
  <div class="ann-v2-body">
    <div class="ann-v2-header">
      <div class="ann-v2-pills">
        <span class="pill pill-<?= e($a['category']) ?>"><?= ucfirst($a['category']) ?></span>
        <?php if($a['category']==='emergency'): ?>
        <span class="pill" style="background:var(--red-bg);color:var(--red);border:1px solid #FECACA;font-size:11px;animation:pulse 1.2s infinite;">Urgent</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="ann-v2-title"><?= e($a['title']) ?></div>
    <div class="ann-v2-content"><?= nl2br(e($a['content'])) ?></div>
    <div class="ann-v2-footer">
      <span>Posted by <strong style="color:var(--n-600);"><?= e($a['full_name']) ?></strong> · <?= timeAgo($a['created_at']) ?> · <?= date('M d, Y',strtotime($a['created_at'])) ?></span>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>