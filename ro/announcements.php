<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('ro', 'admin');
$db       = getDB();
$uid      = $_SESSION['user_id'];
$regionId = $_SESSION['region_id'] ?? null;
$sy       = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();

/* ── POST HANDLERS ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    /* Create */
    if ($_POST['action'] === 'post') {
        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');
        if (!$title || !$content) {
            echo json_encode(['ok' => false, 'msg' => 'Title and content are required.']); exit;
        }
        $cat    = in_array($_POST['category'], ['general','policy','directive','advisory','emergency'])
                  ? $_POST['category'] : 'general';
        $target = in_array($_POST['target'], ['all','school_head','teacher','sdo','ro'])
                  ? $_POST['target'] : 'all';

        $stmt = $db->prepare(
            "INSERT INTO announcements (posted_by, title, content, category, target_role, region_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$uid, $title, $content, $cat, $target, $regionId]);
        $newId = $db->lastInsertId();

        echo json_encode(['ok' => true, 'msg' => 'Announcement posted successfully.', 'ann' => [
            'id'       => $newId,
            'title'    => $title,
            'content'  => $content,
            'category' => $cat,
            'target'   => $target,
            'author'   => $_SESSION['full_name'] ?? 'Regional Office',
            'time'     => 'just now',
        ]]); exit;
    }

    /* Delete */
    if ($_POST['action'] === 'delete') {
        $id  = (int) ($_POST['id'] ?? 0);
        $row = $db->prepare("SELECT posted_by FROM announcements WHERE ann_id = ?");
        $row->execute([$id]); $row = $row->fetch();
        if (!$row) { echo json_encode(['ok' => false, 'msg' => 'Announcement not found.']); exit; }
        if ($row['posted_by'] != $uid && $_SESSION['role'] !== 'admin') {
            echo json_encode(['ok' => false, 'msg' => 'You can only delete your own announcements.']); exit;
        }
        $db->prepare("DELETE FROM announcements WHERE ann_id = ?")->execute([$id]);
        echo json_encode(['ok' => true, 'msg' => 'Announcement deleted.']); exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action.']); exit;
}

/* ── LOAD ANNOUNCEMENTS ─────────────────────────────────────── */
try {
    $stmt = $db->prepare(
        "SELECT a.*, u.full_name
         FROM announcements a
         JOIN users u ON a.posted_by = u.user_id
         WHERE (a.region_id = ? OR a.region_id IS NULL)
         ORDER BY a.created_at DESC"
    );
    $stmt->execute([$regionId]);
    $anns = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback: no region_id column yet
    $anns = $db->query(
        "SELECT a.*, u.full_name FROM announcements a
         JOIN users u ON a.posted_by = u.user_id
         ORDER BY a.created_at DESC"
    )->fetchAll();
}

/* ── STATS ──────────────────────────────────────────────────── */
$total     = count($anns);
$today     = count(array_filter($anns, fn($a) => date('Y-m-d', strtotime($a['created_at'])) === date('Y-m-d')));
$emergency = count(array_filter($anns, fn($a) => ($a['category'] ?? '') === 'emergency'));
$thisWeek  = count(array_filter($anns, fn($a) => strtotime($a['created_at']) >= strtotime('-7 days')));

/* ── CATEGORY HELPERS ───────────────────────────────────────── */
$catMeta = [
    'general'   => ['label' => 'General',   'color' => 'var(--g500)',    'bg' => 'var(--g100)',   'pill' => 'pill-admin'],
    'policy'    => ['label' => 'Policy',    'color' => 'var(--purple)',  'bg' => 'var(--purpb)',  'pill' => 'pill-principal'],
    'directive' => ['label' => 'Directive', 'color' => 'var(--blue)',    'bg' => 'var(--blueb)',  'pill' => 'pill-teacher'],
    'advisory'  => ['label' => 'Advisory',  'color' => 'var(--gold)',    'bg' => 'var(--goldb)',  'pill' => 'pill-student'],
    'emergency' => ['label' => 'Emergency', 'color' => 'var(--red)',     'bg' => 'var(--redb)',   'pill' => 'pill-student'],
];

$pageTitle  = 'Announcements';
$activePage = 'announcements.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Announcement cards ── */
.ann-card {
  background: var(--white);
  border: 1px solid var(--n200);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 12px;
  overflow: hidden;
  transition: box-shadow var(--trans), transform var(--trans);
}
.ann-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }

.ann-stripe { height: 4px; }

.ann-body { padding: 14px 20px 12px; }

.ann-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }

.ann-title {
  font-size: 15px; font-weight: 700; color: var(--n900);
  margin: 9px 0 6px; line-height: 1.35;
}
.ann-content {
  font-size: 13.5px; color: var(--n600); line-height: 1.7;
  white-space: pre-line;
  word-break: break-word;
}
.ann-foot {
  display: flex; align-items: center; gap: 14px;
  font-size: 11.5px; color: var(--n400);
  margin-top: 10px; padding-top: 8px;
  border-top: 1px solid var(--n100);
  flex-wrap: wrap;
}
.ann-foot .ni { width: 13px; height: 13px; vertical-align: middle; }

/* Emergency pulse */
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
.emergency-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700; color: var(--red);
  animation: pulse 1.6s ease-in-out infinite;
}

/* Filter chips */
.chip-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
.chip {
  padding: 5px 15px; border-radius: 999px;
  font-size: 12px; font-weight: 600;
  border: 1.5px solid var(--n200);
  background: var(--white); color: var(--n600);
  cursor: pointer; transition: all var(--trans);
}
.chip:hover, .chip.active {
  background: var(--g700); color: #fff; border-color: var(--g700);
}

/* Empty state */
.empty-state {
  text-align: center; padding: 56px 24px;
  color: var(--n400);
}
.empty-state .icon { font-size: 38px; margin-bottom: 12px; }
.empty-state p { font-weight: 600; font-size: 14px; color: var(--n500); }
.empty-state span { font-size: 13px; }
</style>

<!-- Page header -->
<div class="page-head">
  <div class="page-head-text">
    <h2>Regional Announcements</h2>
    <p>Post notices, policies, directives, and alerts to divisions and schools — SY <?= e($sy['label'] ?? '—') ?></p>
  </div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal('mPost')">
      <?= svgIcon('plus') ?> New Announcement
    </button>
  </div>
</div>

<!-- Stats -->
<div class="stats" style="grid-template-columns:repeat(4,1fr); margin-bottom:20px;">
  <div class="stat">
    <div class="stat-ic green"><?= svgIcon('bell') ?></div>
    <div class="stat-data">
      <div class="stat-val" id="live-total"><?= $total ?></div>
      <div class="stat-lbl">Total Posts</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic blue"><?= svgIcon('calendar') ?></div>
    <div class="stat-data">
      <div class="stat-val"><?= $today ?></div>
      <div class="stat-lbl">Posted Today</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic gold"><?= svgIcon('trending-up') ?></div>
    <div class="stat-data">
      <div class="stat-val"><?= $thisWeek ?></div>
      <div class="stat-lbl">This Week</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic red"><?= svgIcon('alert-circle') ?></div>
    <div class="stat-data">
      <div class="stat-val" id="live-emergency"><?= $emergency ?></div>
      <div class="stat-lbl">Emergency Alerts</div>
    </div>
  </div>
</div>

<!-- Filter chips + search -->
<div class="chip-bar">
  <span style="font-size:12px;font-weight:600;color:var(--n400);">Filter:</span>
  <button class="chip active" onclick="filterBy('all',this)">All <span id="chip-count-all" style="opacity:.6;">(<?= $total ?>)</span></button>
  <?php foreach ($catMeta as $key => $cm):
    $cnt = count(array_filter($anns, fn($a) => ($a['category'] ?? '') === $key));
    if (!$cnt) continue;
  ?>
  <button class="chip" onclick="filterBy('<?= $key ?>',this)" data-cat="<?= $key ?>">
    <?= $cm['label'] ?>
    <span style="opacity:.6;">(<?= $cnt ?>)</span>
  </button>
  <?php endforeach; ?>
  <div style="margin-left:auto;">
    <input type="text" class="fc" placeholder="Search announcements…"
           oninput="searchAnns(this.value)"
           style="width:230px; padding:6px 12px; font-size:13px;">
  </div>
</div>

<!-- Announcement feed -->
<div id="annFeed">
<?php if (empty($anns)): ?>
  <div class="card">
    <div class="empty-state">
      <div class="icon">📢</div>
      <p>No announcements yet</p>
      <span>Click "New Announcement" to post the first one.</span>
    </div>
  </div>
<?php endif; ?>

<?php foreach ($anns as $a):
  $cat   = $a['category'] ?? 'general';
  $meta  = $catMeta[$cat] ?? $catMeta['general'];
  $own   = ($a['posted_by'] == $uid || ($_SESSION['role'] ?? '') === 'admin');
  $tgt   = ucfirst(str_replace('_', ' ', $a['target_role'] ?? 'all'));
?>
<div class="ann-card" id="ann<?= $a['ann_id'] ?>" data-category="<?= e($cat) ?>">
  <div class="ann-stripe" style="background:<?= $meta['color'] ?>;"></div>
  <div class="ann-body">

    <div class="ann-header">
      <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
        <span class="pill <?= $meta['pill'] ?>"><?= $meta['label'] ?></span>
        <span class="pill" style="background:var(--n100);color:var(--n600);border-color:var(--n200);font-size:10.5px;">
          <?= svgIcon('users','','width:11px;height:11px;vertical-align:middle;') ?>
          <?= e($tgt) ?>
        </span>
        <?php if ($cat === 'emergency'): ?>
        <span class="emergency-badge"><?= svgIcon('alert-circle') ?> URGENT</span>
        <?php endif; ?>
      </div>
      <?php if ($own): ?>
      <button class="btn btn-danger btn-sm" onclick="delAnn(<?= $a['ann_id'] ?>, this)" title="Delete announcement">
        <?= svgIcon('trash') ?>
      </button>
      <?php endif; ?>
    </div>

    <div class="ann-title"><?= e($a['title']) ?></div>
    <div class="ann-content"><?= e(preg_replace('/\n{3,}/', "\n\n", trim($a['content']))) ?></div>

    <div class="ann-foot">
      <span><?= svgIcon('user') ?> <strong><?= e($a['full_name']) ?></strong></span>
      <span><?= svgIcon('clock') ?> <?= timeAgo($a['created_at']) ?></span>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div><!-- #annFeed -->


<!-- ── POST MODAL ──────────────────────────────────────────── -->
<div class="overlay" id="mPost">
  <div class="modal" style="max-width:600px;">
    <div class="modal-head">
      <span class="modal-title"><?= svgIcon('bell') ?> New Announcement</span>
      <button class="modal-close" onclick="closeModal('mPost')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">

      <div class="form-row" style="margin-bottom:6px;">
        <div class="fg">
          <label>Category <span style="color:var(--red)">*</span></label>
          <select class="fc" id="fCat" onchange="onCatChange(this.value)">
            <option value="general">General</option>
            <option value="policy">Policy</option>
            <option value="directive">Directive</option>
            <option value="advisory">Advisory</option>
            <option value="emergency">🚨 Emergency</option>
          </select>
        </div>
        <div class="fg">
          <label>Target Audience <span style="color:var(--red)">*</span></label>
          <select class="fc" id="fTarget">
            <option value="all">All Users</option>
            <option value="school_head">School Heads Only</option>
            <option value="sdo">SDO Officers</option>
            <option value="teacher">Teachers</option>
            <option value="ro">Regional Office</option>
          </select>
        </div>
      </div>

      <!-- Color accent preview -->
      <div id="catAccent" style="height:3px;border-radius:3px;background:var(--g500);margin-bottom:16px;transition:background .2s;"></div>

      <!-- Emergency warning -->
      <div id="emergencyNote" style="display:none;margin-bottom:14px;"
           class="flex-c" style="gap:8px;padding:10px 14px;background:var(--redb);border:1px solid #FECACA;border-radius:var(--radius-sm);">
        <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--redb);border:1px solid #FECACA;border-radius:var(--radius-sm);">
          <?= svgIcon('alert-circle','','color:var(--red);width:16px;height:16px;') ?>
          <span style="font-size:13px;color:var(--red);font-weight:600;">This will be flagged as an <strong>Emergency Alert</strong> and displayed prominently.</span>
        </div>
      </div>

      <div class="fg">
        <label>Title <span style="color:var(--red)">*</span></label>
        <input class="fc" id="fTitle" placeholder="Enter announcement title…" maxlength="200" oninput="updateCounter()">
        <div style="text-align:right;font-size:11px;color:var(--n400);margin-top:3px;"><span id="fTitleCount">0</span>/200</div>
      </div>

      <div class="fg">
        <label>Content <span style="color:var(--red)">*</span></label>
        <textarea class="fc" id="fContent" rows="6"
                  placeholder="Write the full announcement here…"
                  style="resize:vertical;"></textarea>
      </div>

    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mPost')">Cancel</button>
      <button class="btn btn-primary" id="postBtn" onclick="submitPost()">
        <?= svgIcon('bell') ?> Post Announcement
      </button>
    </div>
  </div>
</div>


<script>
/* ── Category accent colours (JS mirror) ── */
const CAT_COLORS = {
  general:   '#16A34A',
  policy:    '#7C3AED',
  directive: '#2563EB',
  advisory:  '#D97706',
  emergency: '#DC2626',
};
const CAT_LABELS = {
  general:'General', policy:'Policy',
  directive:'Directive', advisory:'Advisory', emergency:'Emergency'
};

function onCatChange(val) {
  document.getElementById('catAccent').style.background = CAT_COLORS[val] || CAT_COLORS.general;
  document.getElementById('emergencyNote').style.display = val === 'emergency' ? 'block' : 'none';
  const btn = document.getElementById('postBtn');
  btn.className = val === 'emergency' ? 'btn btn-danger' : 'btn btn-primary';
}

function updateCounter() {
  document.getElementById('fTitleCount').textContent =
    document.getElementById('fTitle').value.length;
}

/* ── Submit new announcement ── */
async function submitPost() {
  const title   = document.getElementById('fTitle').value.trim();
  const content = document.getElementById('fContent').value.trim();
  const cat     = document.getElementById('fCat').value;
  const target  = document.getElementById('fTarget').value;

  if (!title)   { toast('Please enter a title.', 'warning'); return; }
  if (!content) { toast('Please enter the content.', 'warning'); return; }

  const btn = document.getElementById('postBtn');
  btn.disabled = true;
  btn.innerHTML = '<span style="opacity:.7">Posting…</span>';

  const r = await apiPost('announcements.php', { action:'post', title, content, category:cat, target });

  btn.disabled = false;
  onCatChange(cat); // restore label/color
  btn.innerHTML = svgI('bell') + ' Post Announcement';

  if (!r.ok) { toast(r.msg, 'err'); return; }
  toast(r.msg, 'ok');
  closeModal('mPost');

  // Reset form
  document.getElementById('fTitle').value   = '';
  document.getElementById('fContent').value = '';
  document.getElementById('fCat').value     = 'general';
  document.getElementById('fTarget').value  = 'all';
  document.getElementById('fTitleCount').textContent = '0';
  document.getElementById('catAccent').style.background = CAT_COLORS.general;
  document.getElementById('emergencyNote').style.display = 'none';
  document.getElementById('postBtn').className = 'btn btn-primary';

  // Inject card at top of feed
  injectCard(r.ann);

  // Update stat counters
  const liveTotal = document.getElementById('live-total');
  if (liveTotal) liveTotal.textContent = parseInt(liveTotal.textContent||0) + 1;
  if (cat === 'emergency') {
    const liveEmg = document.getElementById('live-emergency');
    if (liveEmg) liveEmg.textContent = parseInt(liveEmg.textContent||0) + 1;
  }
}

function injectCard(ann) {
  const color = CAT_COLORS[ann.category] || CAT_COLORS.general;
  const tgt   = ann.target.replace(/_/g, ' ');
  const label = CAT_LABELS[ann.category] || ann.category;
  const esc   = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const isEmg = ann.category === 'emergency';

  const html = `
    <div class="ann-card" id="ann${ann.id}" data-category="${ann.category}">
      <div class="ann-stripe" style="background:${color};"></div>
      <div class="ann-body">
        <div class="ann-header">
          <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
            <span class="pill" style="background:${color}18;color:${color};border-color:${color}44;">${label}</span>
            <span class="pill" style="background:var(--n100);color:var(--n600);border-color:var(--n200);font-size:10.5px;">
              ${tgt.charAt(0).toUpperCase()+tgt.slice(1)}
            </span>
            ${isEmg ? '<span class="emergency-badge">⚠ URGENT</span>' : ''}
          </div>
          <button class="btn btn-danger btn-sm" onclick="delAnn(${ann.id},this)" title="Delete">
            ${svgI('trash')}
          </button>
        </div>
        <div class="ann-title">${esc(ann.title)}</div>
        <div class="ann-content">${esc(ann.content.trim())}</div>
        <div class="ann-foot">
          <span>${svgI('user')} <strong>${esc(ann.author)}</strong></span>
          <span>${svgI('clock')} just now</span>
        </div>
      </div>
    </div>`;

  const feed = document.getElementById('annFeed');
  // Remove empty-state card if present
  const empty = feed.querySelector('.card');
  if (empty && empty.querySelector('.empty-state')) empty.remove();
  feed.insertAdjacentHTML('afterbegin', html);
}

/* ── Delete ── */
async function delAnn(id, btn) {
  if (!confirm('Delete this announcement? This cannot be undone.')) return;
  const r = await apiPost('announcements.php', { action:'delete', id });
  toast(r.msg, r.ok ? 'ok' : 'err');
  if (!r.ok) return;

  const card = document.getElementById('ann' + id) || btn?.closest('.ann-card');
  if (card) {
    // Update emergency counter if needed
    if (card.dataset.category === 'emergency') {
      const liveEmg = document.getElementById('live-emergency');
      if (liveEmg) liveEmg.textContent = Math.max(0, parseInt(liveEmg.textContent||0) - 1);
    }
    const liveTotal = document.getElementById('live-total');
    if (liveTotal) liveTotal.textContent = Math.max(0, parseInt(liveTotal.textContent||0) - 1);
    card.style.transition = 'opacity .25s, transform .25s';
    card.style.opacity = '0'; card.style.transform = 'translateX(16px)';
    setTimeout(() => card.remove(), 260);
  }
}

/* ── Filter by category ── */
function filterBy(cat, btnEl) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  if (btnEl) btnEl.classList.add('active');
  document.querySelectorAll('#annFeed .ann-card').forEach(card => {
    card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
  });
}

/* ── Search ── */
function searchAnns(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#annFeed .ann-card').forEach(card => {
    card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
  // Reset filter chips to show nothing active when searching
  if (q) document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  else   { filterBy('all', document.querySelector('.chip')); }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>