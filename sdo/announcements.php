<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();
    if ($_POST['action']==='post') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if (!$title || !$content) { echo json_encode(['ok'=>false,'msg'=>'Title and content required.']); exit; }
        $cat = in_array($_POST['category'],['general','policy','deadline','advisory','emergency']) ? $_POST['category'] : 'general';
        $target = in_array($_POST['target'],['all','school_head','teacher','sdo','ro','external_stakeholder']) ? $_POST['target'] : 'all';
        $db->prepare("INSERT INTO announcements (posted_by,title,content,category,target_role) VALUES (?,?,?,?,?)")
           ->execute([$_SESSION['user_id'],$title,$content,$cat,$target]);
        $newId = $db->lastInsertId();
        echo json_encode(['ok'=>true,'msg'=>'Announcement posted.','ann'=>[
            'id'       => $newId,
            'title'    => $title,
            'content'  => $content,
            'category' => $cat,
            'target'   => $target,
            'author'   => $_SESSION['full_name'] ?? 'Admin',
        ]]); exit;
    }
    if ($_POST['action']==='delete') {
        $db->prepare("DELETE FROM announcements WHERE ann_id=?")->execute([(int)$_POST['id']]);
        echo json_encode(['ok'=>true,'msg'=>'Announcement deleted.']); exit;
    }
    exit;
}

$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id ORDER BY a.created_at DESC")->fetchAll();
$pageTitle = 'Announcements'; $activePage = 'announcements.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Announcements</h2><p>Post notices and advisories for portal users.</p></div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mPost')"><?= svgIcon('plus') ?> Post Announcement</button>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:12px;">
<?php foreach($anns as $a): ?>
<?php $colors = ['general'=>'var(--g400)','policy'=>'var(--purple)','deadline'=>'var(--red)','advisory'=>'var(--gold)','emergency'=>'var(--red)']; ?>
<div class="card" id="ann<?= $a['ann_id'] ?>" style="border-left:4px solid <?= $colors[$a['category']] ?? 'var(--g400)' ?>;">
  <div class="card-body" style="padding:16px 18px;">
    <div class="flex-cb">
      <div class="flex-c" style="gap:8px;">
        <span class="pill pill-<?= e($a['category']) ?>"><?= ucfirst($a['category']) ?></span>
        <span class="pill pill-<?= e($a['target_role']) ?>" style="font-size:10.5px;"><?= ucfirst(str_replace('_',' ',$a['target_role'])) ?></span>
      </div>
      <button class="btn btn-danger btn-sm" onclick="delAnn(<?= $a['ann_id'] ?>,this)"><?= svgIcon('trash') ?></button>
    </div>
    <h3 style="font-size:15px;font-weight:700;color:var(--n900);margin:10px 0 6px;"><?= e($a['title']) ?></h3>
    <p style="font-size:13.5px;color:var(--n600);line-height:1.65;"><?= nl2br(e($a['content'])) ?></p>
    <div style="font-size:11.5px;color:var(--n400);margin-top:10px;padding-top:9px;border-top:1px solid var(--n100);">
      Posted by <strong><?= e($a['full_name']) ?></strong> · <?= timeAgo($a['created_at']) ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php if(!$anns): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--n400);">No announcements yet.</div></div>
<?php endif; ?>
</div>

<div class="overlay" id="mPost">
  <div class="modal" style="max-width:560px;">
    <div class="modal-head"><span class="modal-title">Post Announcement</span><button class="modal-close" onclick="closeModal('mPost')"><?= svgIcon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="fg"><label>Category</label>
          <select class="fc" id="a_cat">
            <?php foreach(['general','policy','deadline','advisory','emergency'] as $c): ?>
            <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Target Audience</label>
          <select class="fc" id="a_target">
            <option value="all">All Users</option>
            <option value="school_head">School Heads</option>
            <option value="teacher">Teachers</option>
            <option value="sdo">SDO</option>
            <option value="ro">RO</option>
            <option value="external_stakeholder">External Stakeholders</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Title *</label><input class="fc" id="a_title" placeholder="Announcement title"></div>
      <div class="fg"><label>Content *</label><textarea class="fc" id="a_content" rows="5" placeholder="Write your announcement here…"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mPost')">Cancel</button>
      <button class="btn btn-primary" onclick="postAnn()">Post Announcement</button>
    </div>
  </div>
</div>

<script>
const ANN_COLORS = {general:'var(--g400)',policy:'var(--purple)',deadline:'var(--red)',advisory:'var(--gold)',emergency:'var(--red)'};

function annCard(a){
  const _esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  // Sanitize class-safe values (only allow alphanumeric, underscore, hyphen)
  const safecat = String(a.category||'general').replace(/[^a-z0-9_-]/g,'');
  const safetgt = String(a.target||'all').replace(/[^a-z0-9_-]/g,'');
  const safeId  = parseInt(a.id) || 0;
  const color   = ANN_COLORS[safecat] || 'var(--brand-600)';
  const cat     = safecat.charAt(0).toUpperCase() + safecat.slice(1);
  const tgt     = safetgt.replace(/_/g,' ');
  const tgtCap  = tgt.charAt(0).toUpperCase() + tgt.slice(1);
  const safeTitle   = _esc(a.title);
  const safeContent = _esc(a.content).replace(/\n/g,'<br>');
  const safeAuthor  = _esc(a.author);
  return `<div class="card" id="ann${safeId}" style="border-left:4px solid ${color};">
    <div class="card-body" style="padding:16px 18px;">
      <div class="flex-cb">
        <div class="flex-c" style="gap:8px;">
          <span class="pill pill-${safecat}">${cat}</span>
          <span class="pill pill-${safetgt}" style="font-size:10.5px;">${tgtCap}</span>
        </div>
        <button class="btn btn-danger btn-sm" onclick="delAnn(${safeId},this)">${svgI('trash')}</button>
      </div>
      <h3 style="font-size:15px;font-weight:700;color:var(--n900);margin:10px 0 6px;">${safeTitle}</h3>
      <p style="font-size:13.5px;color:var(--n600);line-height:1.65;">${safeContent}</p>
      <div style="font-size:11.5px;color:var(--n400);margin-top:10px;padding-top:9px;border-top:1px solid var(--n100);">
        Posted by <strong>${safeAuthor}</strong> · <span class="ann-time">just now</span>
      </div>
    </div>
  </div>`;
}
    <div class="card-body" style="padding:16px 18px;">
      <div class="flex-cb">
        <div class="flex-c" style="gap:8px;">
          <span class="pill pill-${a.category}">${cat}</span>
          <span class="pill pill-${a.target}" style="font-size:10.5px;">${tgtCap}</span>
        </div>
        <button class="btn btn-danger btn-sm" onclick="delAnn(${a.id},this)">${svgI('trash')}</button>
      </div>
      <h3 style="font-size:15px;font-weight:700;color:var(--n900);margin:10px 0 6px;">${safeTitle}</h3>
      <p style="font-size:13.5px;color:var(--n600);line-height:1.65;">${safeContent}</p>
      <div style="font-size:11.5px;color:var(--n400);margin-top:10px;padding-top:9px;border-top:1px solid var(--n100);">
        Posted by <strong>${safeAuthor}</strong> · <span class="ann-time">just now</span>
      </div>
    </div>
  </div>`;
}

async function postAnn(){
  const r = await apiPost('announcements.php',{
    action:'post', title:$('a_title'), content:$('a_content'),
    category:$('a_cat'), target:$('a_target')
  });
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok){
    closeModal('mPost');
    // Clear fields
    ['a_title','a_content'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });

    // Insert new card at top instantly
    const feed = document.querySelector('[style*="flex-direction:column"]');
    if(feed){
      // Remove "no announcements" placeholder if present
      const empty = feed.querySelector('.card-body[style*="text-align:center"]');
      if(empty) empty.closest('.card')?.remove();

      const tmp = document.createElement('div');
      tmp.innerHTML = annCard(r.ann);
      feed.insertBefore(tmp.firstElementChild, feed.firstChild);
    }
  }
}

async function delAnn(id, btn){
  if(!confirm('Delete this announcement?')) return;
  const r = await apiPost('announcements.php',{action:'delete',id});
  toast(r.msg, r.ok?'ok':'err');
  if(r.ok){
    const card = document.getElementById('ann'+id) || btn?.closest('.card');
    card?.remove();
  }
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>