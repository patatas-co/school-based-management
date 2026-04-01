<?php
ob_start();
// school_head/announcements.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
// Reuse admin announcements logic with school_head role
$_SCHOOL_HEAD_VIEW = true;
// Pull in the full announcements page (same logic as admin/announcements.php)
// but with school_head role gate already passed above
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
  verifyCsrf();
  if ($_POST['action'] === 'post') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!$title || !$content) {
      echo json_encode(['ok' => false, 'msg' => 'Title and content required.']);
      exit;
    }
    $cat = in_array($_POST['category'], ['general', 'policy', 'deadline', 'advisory', 'emergency']) ? $_POST['category'] : 'general';
    $target = in_array($_POST['target'], ['all', 'school_head', 'sbm_coordinator', 'teacher', 'external_stakeholder']) ? $_POST['target'] : 'all';
    $db->prepare("INSERT INTO announcements (posted_by,title,content,category,target_role) VALUES (?,?,?,?,?)")
      ->execute([$_SESSION['user_id'], $title, $content, $cat, $target]);
    $newId = $db->lastInsertId();
    echo json_encode(['ok' => true, 'msg' => 'Announcement posted.', 'ann' => ['id' => $newId, 'title' => $title, 'content' => $content, 'category' => $cat, 'target' => $target, 'author' => $_SESSION['full_name'] ?? 'School Head']]);
    exit;
  }
  if ($_POST['action'] === 'delete') {
    $db->prepare("DELETE FROM announcements WHERE ann_id=?")->execute([(int) $_POST['id']]);
    echo json_encode(['ok' => true, 'msg' => 'Announcement deleted.']);
    exit;
  }
  exit;
}

$filterCat = $_GET['cat'] ?? '';
$sql = "SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id";
$params = [];
if ($filterCat) {
  $sql .= " WHERE a.category=?";
  $params[] = $filterCat;
}
$sql .= " ORDER BY a.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$anns = $stmt->fetchAll();
$catCounts = $db->query("SELECT category, COUNT(*) cnt FROM announcements GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Announcements';
$activePage = 'announcements.php';
include __DIR__ . '/../includes/header.php';

$catColors = ['general' => '#16A34A', 'policy' => '#7C3AED', 'deadline' => '#DC2626', 'advisory' => '#D97706', 'emergency' => '#DC2626'];
$catBgs = ['general' => '#DCFCE7', 'policy' => '#EDE9FE', 'deadline' => '#FEE2E2', 'advisory' => '#FEF3C7', 'emergency' => '#FEE2E2'];
?>
<style>
  .ann-v2 {
    background: var(--white);
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-xs);
    margin-bottom: 10px;
    transition: box-shadow 140ms, transform 140ms;
  }

  .ann-v2:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
  }

  .ann-v2-stripe {
    height: 4px;
  }

  .ann-v2-body {
    padding: 16px 20px;
  }

  .ann-v2-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
  }

  .ann-v2-pills {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }

  .ann-v2-title {
    font-size: 15.5px;
    font-weight: 700;
    color: var(--n-900);
    margin-bottom: 8px;
    line-height: 1.35;
  }

  .ann-v2-content {
    font-size: 13.5px;
    color: var(--n-600);
    line-height: 1.75;
  }

  .ann-v2-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid var(--n-100);
    font-size: 12px;
    color: var(--n-400);
  }
</style>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Communication</div>
    <div class="ph2-title">Announcements</div>
    <div class="ph2-sub">Post notices and advisories for portal users.</div>
  </div>
  <div class="ph2-right">
    <button class="btn btn-primary" onclick="openModal('mPost')"><?= svgIcon('plus') ?> Post Announcement</button>
  </div>
</div>

<div class="status-tabs" style="margin-bottom:20px;">
  <a href="announcements.php" class="status-tab <?= !$filterCat ? 'active' : '' ?>">All <span
      class="status-tab-count"><?= array_sum($catCounts) ?></span></a>
  <?php foreach (['general', 'policy', 'deadline', 'advisory', 'emergency'] as $c): ?>
    <a href="announcements.php?cat=<?= $c ?>" class="status-tab <?= $filterCat === $c ? 'active' : '' ?>">
      <span
        style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= $catColors[$c] ?>;margin-right:3px;"></span>
      <?= ucfirst($c) ?>
      <?php if ($catCounts[$c] ?? 0): ?><span class="status-tab-count"><?= $catCounts[$c] ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$anns): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('bell') ?></div>
      <div class="empty-title">No announcements yet</div><button class="btn btn-primary"
        onclick="openModal('mPost')"><?= svgIcon('plus') ?> Post Announcement</button>
    </div>
  </div>
<?php else: ?>
  <div id="annFeed">
    <?php foreach ($anns as $a):
      $col = $catColors[$a['category']] ?? '#16A34A';
      ?>
      <div class="ann-v2" id="ann<?= $a['ann_id'] ?>">
        <div class="ann-v2-stripe" style="background:<?= $col ?>;"></div>
        <div class="ann-v2-body">
          <div class="ann-v2-header">
            <div class="ann-v2-pills">
              <span class="pill pill-<?= e($a['category']) ?>"><?= ucfirst($a['category']) ?></span>
              <span class="pill"
                style="font-size:11px;background:var(--n-100);color:var(--n-600);"><?= ucfirst(str_replace('_', ' ', $a['target_role'])) ?></span>
              <?php if ($a['category'] === 'emergency'): ?><span class="pill"
                  style="background:var(--red-bg);color:var(--red);border:1px solid #FECACA;font-size:11px;animation:pulse 1.2s infinite;">Urgent</span><?php endif; ?>
            </div>
            <button class="btn btn-danger btn-sm"
              onclick="delAnn(<?= $a['ann_id'] ?>,this)"><?= svgIcon('trash') ?></button>
          </div>
          <div class="ann-v2-title"><?= e($a['title']) ?></div>
          <div class="ann-v2-content"><?= nl2br(e($a['content'])) ?></div>
          <div class="ann-v2-footer"><span>Posted by <strong style="color:var(--n-600);"><?= e($a['full_name']) ?></strong>
              · <?= timeAgo($a['created_at']) ?> · <?= date('M d, Y', strtotime($a['created_at'])) ?></span></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="overlay" id="mPost">
  <div class="modal" style="max-width:540px;">
    <div class="modal-head"><span class="modal-title">Post Announcement</span><button class="modal-close"
        onclick="closeModal('mPost')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="fg"><label>Category</label>
          <select class="fc" id="a_cat">
            <?php foreach (['general', 'policy', 'deadline', 'advisory', 'emergency'] as $c): ?>
              <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Target Audience</label>
          <select class="fc" id="a_target">
            <option value="all">All Users</option>
            <option value="school_head">School Heads</option>
            <option value="sbm_coordinator">SBM Coordinators</option>
            <option value="teacher">Teachers</option>
            <option value="external_stakeholder">External Stakeholders</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Title *</label><input class="fc" id="a_title" placeholder="Announcement title…"></div>
      <div class="fg"><label>Content *</label><textarea class="fc" id="a_content" rows="5"
          placeholder="Write your announcement here…"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mPost')">Cancel</button>
      <button class="btn btn-primary" onclick="postAnn()"><?= svgIcon('send') ?> Post</button>
    </div>
  </div>
</div>

<script>
  async function postAnn() {
    const r = await apiPost('announcements.php', { action: 'post', title: $('a_title'), content: $('a_content'), category: $('a_cat'), target: $('a_target') });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mPost'); setTimeout(() => location.reload(), 800); }
  }
  async function delAnn(id, btn) {
    if (!confirm('Delete this announcement?')) return;
    const r = await apiPost('announcements.php', { action: 'delete', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { const card = document.getElementById('ann' + id) || btn?.closest('.ann-v2'); card?.remove(); }
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>