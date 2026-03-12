<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('teacher');
$db = getDB();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='post') {
        $db->prepare("INSERT INTO announcements (posted_by,title,content,target_role,category,is_published) VALUES (?,?,?,'student',?,1)")
           ->execute([$_SESSION['user_id'],trim($_POST['title']),trim($_POST['content']),$_POST['category']]);
        echo json_encode(['ok'=>true,'msg'=>'Announcement posted.']); exit;
    }
    exit;
}
$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE is_published=1 AND (target_role='all' OR target_role='teacher') ORDER BY created_at DESC")->fetchAll();
$pageTitle='Announcements'; $activePage='announcements.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Announcements</h2><p>School notices and communications.</p></div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mPost')"><?= svgIcon('plus') ?> Post Notice</button>
  </div>
</div>
<div style="display:flex;flex-direction:column;gap:14px;">
<?php foreach($anns as $a):
  $extra=$a['category']==='emergency'?' emergency':($a['category']==='event'?' event':($a['category']==='administrative'?' administrative':''));
?>
<div class="ann-card<?= $extra ?>">
  <div class="flex-c" style="gap:7px;flex-wrap:wrap;margin-bottom:6px;">
    <span class="pill pill-<?= e($a['category']) ?>"><?= ucfirst($a['category']) ?></span>
    <span class="pill pill-parent">→ <?= ucfirst($a['target_role']) ?></span>
    <span style="font-size:11px;color:var(--n400);"><?= timeAgo($a['created_at']) ?></span>
  </div>
  <div class="ann-title"><?= e($a['title']) ?></div>
  <div class="ann-body"><?= nl2br(e($a['content'])) ?></div>
  <div class="ann-meta">By <?= e($a['full_name']) ?> · <?= date('F d, Y',strtotime($a['created_at'])) ?></div>
</div>
<?php endforeach; if(!$anns): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--n400);">No announcements at this time.</div></div>
<?php endif; ?>
</div>
<div class="overlay" id="mPost">
  <div class="modal">
    <div class="modal-head"><span class="modal-title">Post Class Announcement</span><button class="modal-close" onclick="closeModal('mPost')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="fg"><label>Title *</label><input class="fc" id="a_title" placeholder="Announcement title"></div>
      <div class="fg"><label>Content *</label><textarea class="fc" id="a_body" rows="4" placeholder="Write your announcement…"></textarea></div>
      <div class="fg"><label>Category</label>
        <select class="fc" id="a_cat"><option value="academic">Academic</option><option value="event">Event</option><option value="administrative">Administrative</option></select>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mPost')">Cancel</button>
      <button class="btn btn-primary" onclick="postAnn()">Post</button>
    </div>
  </div>
</div>
<script>
async function postAnn(){
  const r = await apiPost('announcements.php', {
    action: 'post',
    title: document.getElementById('a_title').value,
    content: document.getElementById('a_body').value,
    category: document.getElementById('a_cat').value,
    audience: document.getElementById('a_aud').value   // ← add this
  });
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok){ closeModal('mPost'); setTimeout(()=>location.reload(),800); }
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
