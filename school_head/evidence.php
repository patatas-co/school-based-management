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
// ── Load all attachments for this cycle ─────────────────────
$allAttachments = [];
$attachByIndicator = [];
if ($cycle) {
    try {
        $attStmt = $db->prepare("
            SELECT ra.*, u.full_name uploader_name, u.role uploader_role_label,
                   i.indicator_code, i.indicator_text,
                   d.dimension_no, d.dimension_name, d.color_hex
            FROM response_attachments ra
            JOIN users u         ON ra.uploaded_by    = u.user_id
            JOIN sbm_indicators i ON ra.indicator_id  = i.indicator_id
            JOIN sbm_dimensions d ON i.dimension_id   = d.dimension_id
            WHERE ra.cycle_id = ?
            ORDER BY d.dimension_no ASC, i.sort_order ASC,
                     ra.uploaded_at ASC
        ");
        $attStmt->execute([$cycle['cycle_id']]);
        $allAttachments = $attStmt->fetchAll();
        foreach ($allAttachments as $att) {
            $attachByIndicator[$att['indicator_id']][] = $att;
        }
    } catch (\Exception $e) { $allAttachments = []; }
}

// Group attachments by dimension for the subsection
$attachByDim = [];
foreach ($allAttachments as $att) {
    $key = $att['dimension_no'];
    if (!isset($attachByDim[$key])) {
        $attachByDim[$key] = [
            'dimension_name' => $att['dimension_name'],
            'color_hex'      => $att['color_hex'],
            'indicators'     => [],
        ];
    }
    $indKey = $att['indicator_id'];
    if (!isset($attachByDim[$key]['indicators'][$indKey])) {
        $attachByDim[$key]['indicators'][$indKey] = [
            'indicator_code' => $att['indicator_code'],
            'indicator_text' => $att['indicator_text'],
            'files'          => [],
        ];
    }
    $attachByDim[$key]['indicators'][$indKey]['files'][] = $att;
}

$pageTitle = 'Evidence Files'; $activePage = 'evidence.php';
include __DIR__.'/../includes/header.php';

function formatFileSize(int $bytes): string {
    if ($bytes < 1024)        return $bytes.' B';
    if ($bytes < 1024*1024)   return round($bytes/1024,1).' KB';
    return round($bytes/(1024*1024),1).' MB';
}
function fileIconHtml(string $mime): string {
    if (strncmp($mime, 'image/', 6) === 0)                        return '🖼️';
    if ($mime === 'application/pdf')                              return '📄';
    if (strpos($mime, 'word') !== false)                          return '📝';
    if (strpos($mime, 'sheet') !== false || strpos($mime, 'excel') !== false) return '📊';
    if (strpos($mime, 'presentation') !== false || strpos($mime, 'powerpoint') !== false) return '📊';
    return '📎';
}
function roleLabel(string $role): string {
    switch ($role) {
        case 'teacher':              return 'Teacher';
        case 'sbm_coordinator':      return 'Coordinator';
        case 'school_head':          return 'School Head';
        case 'external_stakeholder': return 'Stakeholder';
        default:                     return ucfirst($role);
    }
}
?>
<style>
/* ── Evidence Attachments section ── */
.ev-section-head {
  display:flex;align-items:center;gap:10px;padding:14px 18px;
  background:var(--n50);border-bottom:1px solid var(--n200);
}
.ev-dim-badge {
  width:28px;height:28px;border-radius:7px;display:flex;
  align-items:center;justify-content:center;font-size:12px;
  font-weight:800;color:#fff;flex-shrink:0;
}
.ev-ind-block {
  border:1px solid var(--n200);border-radius:9px;
  overflow:hidden;margin-bottom:10px;
}
.ev-ind-head {
  display:flex;align-items:center;gap:10px;padding:10px 14px;
  background:var(--n50);border-bottom:1px solid var(--n100);
  cursor:pointer;user-select:none;
}
.ev-ind-head:hover { background:var(--n100); }
.ev-ind-body { padding:10px 14px; }
.ev-file-row {
  display:flex;align-items:center;gap:10px;padding:8px 10px;
  border:1px solid var(--n200);border-radius:7px;margin-bottom:6px;
  background:var(--white);transition:background .12s;
}
.ev-file-row:hover { background:var(--n50); }
.ev-file-icon  { font-size:16px;flex-shrink:0; }
.ev-file-info  { flex:1;min-width:0; }
.ev-file-name  { font-size:13px;font-weight:600;color:var(--n900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.ev-file-meta  { font-size:11.5px;color:var(--n500);margin-top:2px; }
.ev-role-badge {
  display:inline-flex;padding:2px 8px;border-radius:999px;
  font-size:10.5px;font-weight:700;flex-shrink:0;
}
.role-teacher        { background:var(--teal-bg);color:var(--teal); }
.role-sbm_coordinator{ background:var(--brand-100);color:var(--brand-700); }
.role-school_head    { background:var(--purple-bg);color:var(--purple); }
.role-external_stakeholder { background:var(--blue-bg);color:var(--blue); }
.ev-download-btn {
  display:inline-flex;align-items:center;gap:5px;padding:5px 12px;
  border-radius:6px;border:1px solid var(--n200);background:var(--white);
  font-size:12px;font-weight:600;color:var(--n600);text-decoration:none;
  transition:all .12s;flex-shrink:0;
}
.ev-download-btn:hover { background:var(--brand-700);color:#fff;border-color:var(--brand-700); }
</style>

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

<!-- ══════════════════════════════════════════════════════════
     ATTACHMENT SUBSECTION — All uploaded evidence files
══════════════════════════════════════════════════════════ -->
<div style="margin-top:32px;">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <div style="width:3px;height:22px;background:var(--blue);border-radius:2px;"></div>
    <h3 style="font-size:17px;font-weight:700;color:var(--n900);">Uploaded Evidence Files</h3>
    <span style="font-size:12px;font-weight:600;color:var(--n400);background:var(--n100);border-radius:999px;padding:2px 10px;">
      <?= count($allAttachments) ?> file<?= count($allAttachments)!==1?'s':'' ?>
    </span>
  </div>

  <?php if(empty($allAttachments)): ?>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:40px;">
      <div style="font-size:32px;margin-bottom:12px;">📎</div>
      <div style="font-size:14px;font-weight:600;color:var(--n600);margin-bottom:6px;">No attachments yet</div>
      <div style="font-size:13px;color:var(--n400);">Teachers and evaluators can attach evidence files when filling out the self-assessment.</div>
    </div>
  </div>

  <?php else: ?>

  <!-- Summary strip -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <?php
    $byRole = [];
    foreach ($allAttachments as $att) {
        $r = $att['uploader_role'] ?? 'unknown';
        $byRole[$r] = ($byRole[$r] ?? 0) + 1;
    }
    $roleColors = [
        'teacher'              => ['var(--teal-bg)','var(--teal)'],
        'sbm_coordinator'      => ['var(--brand-100)','var(--brand-700)'],
        'school_head'          => ['var(--purple-bg)','var(--purple)'],
        'external_stakeholder' => ['var(--blue-bg)','var(--blue)'],
    ];
    foreach ($byRole as $r => $cnt):
        [$bg,$color] = $roleColors[$r] ?? ['var(--n100)','var(--n600)'];
    ?>
    <div style="background:<?= $bg ?>;border-radius:8px;padding:8px 14px;display:flex;align-items:center;gap:8px;">
      <span style="font-size:22px;font-weight:800;color:<?= $color ?>;"><?= $cnt ?></span>
      <span style="font-size:11.5px;font-weight:600;color:<?= $color ?>;"><?= roleLabel($r) ?> file<?= $cnt!==1?'s':'' ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Accordion by dimension -->
  <?php foreach ($attachByDim as $dimNo => $dimData): ?>
  <div class="card" style="margin-bottom:14px;overflow:hidden;">
    <div class="ev-section-head" style="border-left:4px solid <?= e($dimData['color_hex']) ?>;">
      <div class="ev-dim-badge" style="background:<?= e($dimData['color_hex']) ?>;"><?= $dimNo ?></div>
      <span style="font-size:14px;font-weight:700;color:var(--n900);flex:1;">
        Dimension <?= $dimNo ?>: <?= e($dimData['dimension_name']) ?>
      </span>
      <span style="font-size:12px;font-weight:600;color:var(--n400);">
        <?= array_sum(array_map(fn($i)=>count($i['files']),$dimData['indicators'])) ?> file<?= array_sum(array_map(fn($i)=>count($i['files']),$dimData['indicators']))!==1?'s':'' ?>
      </span>
    </div>

    <div style="padding:14px 16px;">
      <?php foreach ($dimData['indicators'] as $indId => $indData): ?>
      <div class="ev-ind-block">
        <div class="ev-ind-head" onclick="toggleEvInd(<?= $indId ?>)">
          <span style="font-family:monospace;font-size:11px;font-weight:700;
                       color:var(--n400);background:var(--n100);
                       border-radius:4px;padding:2px 7px;flex-shrink:0;">
            <?= e($indData['indicator_code']) ?>
          </span>
          <span style="font-size:13px;font-weight:600;color:var(--n800);flex:1;
                       overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= e(substr($indData['indicator_text'],0,80)) ?><?= strlen($indData['indicator_text'])>80?'…':'' ?>
          </span>
          <span style="font-size:12px;font-weight:600;color:var(--n500);
                       background:var(--n100);border-radius:999px;
                       padding:2px 10px;flex-shrink:0;">
            <?= count($indData['files']) ?> file<?= count($indData['files'])!==1?'s':'' ?>
          </span>
          <span id="evChevron<?= $indId ?>" style="color:var(--n300);font-size:16px;flex-shrink:0;">▾</span>
        </div>

        <div class="ev-ind-body" id="evIndBody<?= $indId ?>">
          <?php foreach ($indData['files'] as $file): ?>
          <div class="ev-file-row">
            <span class="ev-file-icon"><?= fileIconHtml($file['mime_type']) ?></span>
            <div class="ev-file-info">
              <div class="ev-file-name"><?= e($file['original_name']) ?></div>
              <div class="ev-file-meta">
                <?= formatFileSize($file['file_size']) ?>
                &nbsp;·&nbsp;
                <?= date('M d, Y g:i A', strtotime($file['uploaded_at'])) ?>
              </div>
            </div>
            <span class="ev-role-badge role-<?= e($file['uploader_role']) ?>">
              <?= e($file['uploader_name']) ?> · <?= roleLabel($file['uploader_role']) ?>
            </span>
            <a href="/includes/serve_attachment.php?id=<?= $file['attachment_id'] ?>"
               target="_blank"
               class="ev-download-btn"
               download="<?= e($file['original_name']) ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   style="width:13px;height:13px;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              Download
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>

<script>
function toggleEvInd(indId) {
  const body    = document.getElementById('evIndBody'   + indId);
  const chevron = document.getElementById('evChevron'   + indId);
  if (!body) return;
  const isOpen = body.style.display !== 'none';
  body.style.display    = isOpen ? 'none' : 'block';
  chevron.textContent   = isOpen ? '▸' : '▾';
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
