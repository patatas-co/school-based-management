<?php
ob_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();
$schoolId = $_SESSION['school_id'] ?? 0;
// School year filter — default to current
$allSY = $db->query("SELECT sy_id, label FROM school_years ORDER BY sy_id DESC")->fetchAll();

// Only show school years that have at least one attachment
$sysWithEvidence = $db->query("
    SELECT DISTINCT sy.sy_id, sy.label
    FROM school_years sy
    JOIN sbm_cycles c ON c.sy_id = sy.sy_id
    JOIN response_attachments ra ON ra.cycle_id = c.cycle_id
    WHERE ra.deleted_at IS NULL AND ra.is_current_version = 1
    ORDER BY sy.sy_id DESC
")->fetchAll();

$selectedSyId = (int)($_GET['sy_id'] ?? 0);
if (!$selectedSyId) {
    $sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();
} else {
    $st = $db->prepare("SELECT * FROM school_years WHERE sy_id=? LIMIT 1");
    $st->execute([$selectedSyId]);
    $sy = $st->fetch();
}
$syId = $sy['sy_id'] ?? 0;

// Get current cycle
$cycle = null;
if ($schoolId) {
  $st = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
  $st->execute([$schoolId, $syId]);
  $cycle = $st->fetch();
}

// Responses with evidence
$responses = [];
if ($cycle) {
  $st = $db->prepare("SELECT r.*,i.indicator_code,i.indicator_text,d.dimension_name,d.dimension_no,d.color_hex FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE r.cycle_id=? AND r.evidence_text IS NOT NULL ORDER BY d.dimension_no,i.sort_order");
  $st->execute([$cycle['cycle_id']]);
  $responses = $st->fetchAll();
}

$dimensions = $db->query("SELECT * FROM sbm_dimensions ORDER BY dimension_no")->fetchAll();
// ── Load all attachments for this cycle ─────────────────────
$allAttachments = [];
$attachByIndicator = [];
if ($cycle) {
  try {
    $attStmt = $db->prepare("
            SELECT ra.*, u.full_name uploader_name, u.role uploader_role_label,
                   u.department,
                   i.indicator_code, i.indicator_text,
                   d.dimension_no, d.dimension_name, d.color_hex,
                   sy.label AS sy_label
            FROM response_attachments ra
            JOIN users u          ON ra.uploaded_by   = u.user_id
            JOIN sbm_indicators i ON ra.indicator_id  = i.indicator_id
            JOIN sbm_dimensions d ON i.dimension_id   = d.dimension_id
            JOIN sbm_cycles c2    ON ra.cycle_id      = c2.cycle_id
            JOIN school_years sy  ON c2.sy_id         = sy.sy_id
            WHERE ra.cycle_id = ?
              AND ra.deleted_at IS NULL
              AND ra.is_current_version = 1
            ORDER BY d.dimension_no ASC, i.sort_order ASC,
                     ra.uploaded_at ASC
        ");
    $attStmt->execute([$cycle['cycle_id']]);
    $allAttachments = $attStmt->fetchAll();
    foreach ($allAttachments as $att) {
      $attachByIndicator[$att['indicator_id']][] = $att;
    }
  } catch (\Exception $e) {
    $allAttachments = [];
  }
}

// Group attachments by dimension for the subsection
$attachByDim = [];
foreach ($allAttachments as $att) {
  $key = $att['dimension_no'];
  if (!isset($attachByDim[$key])) {
    $attachByDim[$key] = [
      'dimension_name' => $att['dimension_name'],
      'color_hex' => $att['color_hex'],
      'indicators' => [],
    ];
  }
  $indKey = $att['indicator_id'];
  if (!isset($attachByDim[$key]['indicators'][$indKey])) {
    $attachByDim[$key]['indicators'][$indKey] = [
      'indicator_code' => $att['indicator_code'],
      'indicator_text' => $att['indicator_text'],
      'files' => [],
    ];
  }
  $attachByDim[$key]['indicators'][$indKey]['files'][] = $att;
}

$pageTitle = 'Evidence Files';
$activePage = 'evidence.php';
include __DIR__ . '/../includes/header.php';

// ── Helper: build current URL with replaced sy_id ──
function syFilterUrl(int $syId): string {
    $params = $_GET;
    $params['sy_id'] = $syId;
    return '?' . http_build_query($params);
}

function formatFileSize(int $bytes): string
{
  if ($bytes < 1024)
    return $bytes . ' B';
  if ($bytes < 1024 * 1024)
    return round($bytes / 1024, 1) . ' KB';
  return round($bytes / (1024 * 1024), 1) . ' MB';
}
function fileIconHtml(string $mime): string
{
  if (strncmp($mime, 'image/', 6) === 0)
    return '🖼️';
  if ($mime === 'application/pdf')
    return '📄';
  if (strpos($mime, 'word') !== false)
    return '📝';
  if (strpos($mime, 'sheet') !== false || strpos($mime, 'excel') !== false)
    return '📊';
  if (strpos($mime, 'presentation') !== false || strpos($mime, 'powerpoint') !== false)
    return '📊';
  return '📎';
}

function categoryLabel(string $cat): string
{
  switch ($cat) {
    case 'photo':
      return '📷 Photo';
    case 'document':
      return '📄 Document';
    case 'report':
      return '📊 Report';
    case 'certificate':
      return '🏅 Certificate';
    case 'record':
      return '🗂️ Record';
    default:
      return '📎 Other';
  }
}

function categoryColor(string $cat): string
{
  switch ($cat) {
    case 'photo':
      return '#0D9488';
    case 'document':
      return '#2563EB';
    case 'report':
      return '#7C3AED';
    case 'certificate':
      return '#D97706';
    case 'record':
      return '#DC2626';
    default:
      return '#6B7280';
  }
}

function roleLabel(string $role): string
{
  switch ($role) {
    case 'teacher':
      return 'Teacher';
    case 'sbm_coordinator':
      return 'Coordinator';
    case 'school_head':
      return 'School Head';
    case 'external_stakeholder':
      return 'Stakeholder';
    default:
      return ucfirst($role);
  }
}
?>
<style>
  /* ── Evidence Attachments section ── */
  .ev-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    background: var(--n50);
    border-bottom: 1px solid var(--n200);
  }

  .ev-dim-badge {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
  }

  .ev-ind-block {
    border: 1px solid var(--n200);
    border-radius: 9px;
    overflow: hidden;
    margin-bottom: 10px;
  }

  .ev-ind-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--n50);
    border-bottom: 1px solid var(--n100);
    cursor: pointer;
    user-select: none;
  }

  .ev-ind-head:hover {
    background: var(--n100);
  }

  .ev-ind-body {
    padding: 10px 14px;
  }

  .ev-file-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid var(--n200);
    border-radius: 7px;
    margin-bottom: 6px;
    background: var(--white);
    transition: background .12s;
  }

  .ev-file-row:hover {
    background: var(--n50);
  }

  .ev-file-icon {
    font-size: 16px;
    flex-shrink: 0;
  }

  .ev-file-info {
    flex: 1;
    min-width: 0;
  }

  .ev-file-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--n900);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .ev-file-meta {
    font-size: 11.5px;
    color: var(--n500);
    margin-top: 2px;
  }

  .ev-role-badge {
    display: inline-flex;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .role-teacher {
    background: var(--teal-bg);
    color: var(--teal);
  }

  .role-sbm_coordinator {
    background: var(--brand-100);
    color: var(--brand-700);
  }

  .role-school_head {
    background: var(--purple-bg);
    color: var(--purple);
  }

  .role-external_stakeholder {
    background: var(--blue-bg);
    color: var(--blue);
  }

  .ev-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid var(--n200);
    background: var(--white);
    font-size: 12px;
    font-weight: 600;
    color: var(--n600);
    text-decoration: none;
    transition: all .12s;
    flex-shrink: 0;
  }

  .ev-download-btn:hover {
    background: var(--brand-700);
    color: #fff;
    border-color: var(--brand-700);
  }
</style>

<div class="page-head">
  <div class="page-head-actions">
  </div>
</div>

<?php if (!$cycle): ?>
  <div class="alert alert-warning"><?= svgIcon('alert-circle') ?><span>No assessment cycle found for this school year.</span></div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     ATTACHMENT SUBSECTION — All uploaded evidence files
══════════════════════════════════════════════════════════ -->
<div style="margin-top:0px;">

  <?php if (empty($allAttachments)): ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px;">
        <div style="font-size:14px;font-weight:600;color:var(--n600);margin-bottom:6px;">No attachments yet</div>
        <div style="font-size:13px;color:var(--n400);">Teachers and evaluators can attach evidence files when filling out the self-assessment.</div>
      </div>
    </div>

  <?php else: ?>

    <!-- Search + filter bar -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
      <div style="position:relative;flex:1;min-width:220px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          style="width:15px;height:15px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--n400);">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="evSearch" placeholder="Search by file name, uploader, or indicator…"
          oninput="filterEvTable()"
          style="width:100%;padding:8px 10px 8px 32px;border:1px solid var(--n200);border-radius:8px;font-size:13px;color:var(--n800);outline:none;box-sizing:border-box;">
      </div>
      <!-- Role Filter -->
      <div class="p-select" id="evRoleSelect" style="width:160px;">
        <div class="p-select-trigger" onclick="togglePSelect(event,'evRoleSelect')">
          <span class="p-select-val" id="evRoleLabel">All Roles</span>
        </div>
        <div class="p-select-menu">
          <div class="p-select-item selected" onclick="setEvFilter('role','','All Roles',this)">All Roles <span class="p-select-check"></span></div>
          <div class="p-select-item" onclick="setEvFilter('role','teacher','Teacher',this)">Teacher</div>
          <div class="p-select-item" onclick="setEvFilter('role','sbm_coordinator','Coordinator',this)">Coordinator</div>
          <div class="p-select-item" onclick="setEvFilter('role','school_head','School Head',this)">School Head</div>
          <div class="p-select-item" onclick="setEvFilter('role','external_stakeholder','Stakeholder',this)">Stakeholder</div>
        </div>
      </div>

      <!-- Category Filter -->
      <div class="p-select" id="evCatSelect" style="width:170px;">
        <div class="p-select-trigger" onclick="togglePSelect(event,'evCatSelect')">
          <span class="p-select-val" id="evCatLabel">All Categories</span>
        </div>
        <div class="p-select-menu">
          <div class="p-select-item selected" onclick="setEvFilter('cat','','All Categories',this)">All Categories <span class="p-select-check"></span></div>
          <div class="p-select-item" onclick="setEvFilter('cat','photo','Photo',this)">Photo</div>
          <div class="p-select-item" onclick="setEvFilter('cat','document','Document',this)">Document</div>
          <div class="p-select-item" onclick="setEvFilter('cat','report','Report',this)">Report</div>
          <div class="p-select-item" onclick="setEvFilter('cat','certificate','Certificate',this)">Certificate</div>
          <div class="p-select-item" onclick="setEvFilter('cat','record','Record',this)">Record</div>
          <div class="p-select-item" onclick="setEvFilter('cat','other','Other',this)">Other</div>
        </div>
      </div>
      <input type="hidden" id="evRoleFilter" value="">
      <input type="hidden" id="evCatFilter" value="">
    </div>

    <!-- Flat table -->
    <div class="card" style="overflow:hidden;">
      <table style="width:100%;border-collapse:collapse;" id="evTable">
        <thead>
          <tr style="background:var(--n50);border-bottom:2px solid var(--n200);">
            <th style="padding:11px 14px;text-align:left;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">File</th>
            <th style="padding:11px 14px;text-align:center;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;width:100px;">Indicator</th>
            <th style="padding:11px 14px 11px 28px;text-align:left;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Uploaded By</th>
            <th style="padding:11px 14px;text-align:left;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Department</th>
            <th style="padding:11px 14px;text-align:left;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Category</th>
            <th style="padding:11px 14px;text-align:left;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Date & Time</th>
            <th style="padding:11px 14px;text-align:left;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Size</th>
            <th style="padding:11px 14px;text-align:center;font-size:11.5px;font-weight:700;color:var(--n500);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Actions</th>
          </tr>
        </thead>
        <tbody id="evTableBody">
          <?php foreach ($allAttachments as $file): ?>
            <tr class="ev-table-row"
              data-name="<?= strtolower(e($file['original_name'])) ?>"
              data-uploader="<?= strtolower(e($file['uploader_name'])) ?>"
              data-department="<?= strtolower(e($file['department'] ?? '')) ?>"
              data-indicator="<?= strtolower(e($file['indicator_code'] . ' ' . $file['indicator_text'])) ?>"
              data-role="<?= e($file['uploader_role'] ?? '') ?>"
              data-category="<?= e($file['category'] ?? 'other') ?>"
              style="border-bottom:1px solid var(--n100);transition:background .12s;">
              <!-- File -->
              <td style="padding:12px 14px;max-width:220px;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:20px;flex-shrink:0;"><?= fileIconHtml($file['mime_type']) ?></span>
                  <div style="overflow:hidden;">
                    <div style="font-size:13px;font-weight:600;color:var(--n900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="<?= e($file['original_name']) ?>">
                      <?= e($file['original_name']) ?>
                    </div>
                    <?php if (!empty($file['version']) && (int)$file['version'] > 1): ?>
                      <span style="font-size:10px;font-weight:700;background:#FEF3C7;color:#D97706;border-radius:4px;padding:1px 5px;">v<?= (int)$file['version'] ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <!-- Indicator -->
              <td style="padding:12px 14px;text-align:center;">
                <span style="font-family:monospace;font-size:11px;font-weight:700;color:var(--n500);background:var(--n100);border-radius:4px;padding:3px 8px;display:inline-block;">
                  <?= e($file['indicator_code']) ?>
                </span>
              </td>
              <!-- Uploaded By -->
              <td style="padding:12px 14px 12px 28px;white-space:nowrap;">
                <div style="font-size:13px;font-weight:600;color:var(--n900);"><?= e($file['uploader_name']) ?></div>
                <div style="font-size:11px;color:var(--n400);margin-top:1px;"><?= roleLabel($file['uploader_role'] ?? '') ?></div>
              </td>
              <!-- Department -->
              <td style="padding:12px 14px;white-space:nowrap;">
                <?php if (!empty($file['department'])): ?>
                  <span style="display:inline-flex;align-items:center;padding:3px 9px;border-radius:6px;font-size:11.5px;font-weight:600;background:var(--n100);color:var(--n700);">
                    <?= e($file['department']) ?>
                  </span>
                <?php else: ?>
                  <span style="font-size:12px;color:var(--n300);">—</span>
                <?php endif; ?>
              </td>
              <!-- Category -->
              <td style="padding:12px 14px;white-space:nowrap;">
                <span style="display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;
                  background:<?= categoryColor($file['category'] ?? 'other') ?>18;
                  color:<?= categoryColor($file['category'] ?? 'other') ?>;
                  border:1px solid <?= categoryColor($file['category'] ?? 'other') ?>33;">
                  <?= categoryLabel($file['category'] ?? 'other') ?>
                </span>
              </td>
              <!-- Date & Time -->
              <td style="padding:12px 14px;white-space:nowrap;">
                <div style="font-size:13px;font-weight:600;color:var(--n800);"><?= date('M d, Y', strtotime($file['uploaded_at'])) ?></div>
                <div style="font-size:11px;color:var(--n400);margin-top:1px;"><?= date('g:i A', strtotime($file['uploaded_at'])) ?></div>
                <div style="font-size:10.5px;color:var(--n300);margin-top:2px;">SY <?= e($file['sy_label'] ?? '') ?></div>
              </td>
              <!-- Size -->
              <td style="padding:12px 14px;white-space:nowrap;font-size:13px;color:var(--n600);">
                <?= formatFileSize($file['file_size']) ?>
              </td>
              <!-- Actions -->
              <td style="padding:12px 14px;text-align:center;white-space:nowrap;">
                <div style="display:inline-flex;align-items:center;gap:6px;">
                  <!-- Preview -->
                  <button onclick="openEvPreview('../includes/serve_attachment.php?id=<?= $file['attachment_id'] ?>','<?= e(addslashes($file['original_name'])) ?>','<?= e($file['mime_type']) ?>')"
                    title="Preview"
                    style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue)22;transition:background .12s;cursor:pointer;"
                    onmouseover="this.style.background='var(--blue)';this.style.color='#fff';"
                    onmouseout="this.style.background='var(--blue-bg)';this.style.color='var(--blue)';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                  <!-- Download -->
                  <a href="../includes/serve_attachment.php?id=<?= $file['attachment_id'] ?>" download="<?= e($file['original_name']) ?>"
                    title="Download"
                    style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:var(--teal-bg);color:var(--teal);border:1px solid var(--teal)22;transition:background .12s;text-decoration:none;"
                    onmouseover="this.style.background='var(--teal)';this.style.color='#fff';"
                    onmouseout="this.style.background='var(--teal-bg)';this.style.color='var(--teal)';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div id="evNoResults" style="display:none;padding:36px;text-align:center;font-size:13px;color:var(--n400);">No files match your search.</div>
    </div>

  <?php endif; ?>
</div>

<script>
  function setEvFilter(type, value, label, el) {
    if (type === 'role') {
      document.getElementById('evRoleFilter').value = value;
      document.getElementById('evRoleLabel').textContent = label;
      el.closest('.p-select-menu').querySelectorAll('.p-select-item').forEach(i => {
        i.classList.remove('selected');
        i.querySelector('.p-select-check') && i.querySelector('.p-select-check').remove();
      });
      el.classList.add('selected');
      if (!el.querySelector('.p-select-check')) {
        const chk = document.createElement('span'); chk.className = 'p-select-check'; el.appendChild(chk);
      }
      document.getElementById('evRoleSelect').classList.remove('open');
    } else {
      document.getElementById('evCatFilter').value = value;
      document.getElementById('evCatLabel').textContent = label;
      el.closest('.p-select-menu').querySelectorAll('.p-select-item').forEach(i => {
        i.classList.remove('selected');
        i.querySelector('.p-select-check') && i.querySelector('.p-select-check').remove();
      });
      el.classList.add('selected');
      if (!el.querySelector('.p-select-check')) {
        const chk = document.createElement('span'); chk.className = 'p-select-check'; el.appendChild(chk);
      }
      document.getElementById('evCatSelect').classList.remove('open');
    }
    filterEvTable();
  }

  function filterEvTable() {
    const search = document.getElementById('evSearch').value.toLowerCase();
    const role   = document.getElementById('evRoleFilter').value;
    const cat    = document.getElementById('evCatFilter').value;
    const rows   = document.querySelectorAll('.ev-table-row');
    let visible  = 0;
    rows.forEach(row => {
      const matchSearch = !search ||
        row.dataset.name.includes(search) ||
        row.dataset.uploader.includes(search) ||
        row.dataset.indicator.includes(search);
      const matchRole = !role || row.dataset.role === role;
      const matchCat  = !cat  || row.dataset.category === cat;
      const show = matchSearch && matchRole && matchCat;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    document.getElementById('evNoResults').style.display = visible === 0 ? '' : 'none';
  }

  document.querySelectorAll('.ev-table-row').forEach(row => {
    row.addEventListener('mouseover', () => row.style.background = 'var(--n50)');
    row.addEventListener('mouseout',  () => row.style.background = '');
  });
</script>

<!-- Preview Modal -->
<div id="evPreviewModal" onclick="if(event.target===this)closeEvPreview()"
  style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);padding:20px;box-sizing:border-box;">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35);">
    <!-- Modal Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--n100);flex-shrink:0;">
      <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
        <span id="evPreviewIcon" style="font-size:20px;flex-shrink:0;"></span>
        <span id="evPreviewName" style="font-size:14px;font-weight:700;color:var(--n900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:600px;"></span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
        <button onclick="closeEvPreview()"
          style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--n200);background:var(--n50);cursor:pointer;color:var(--n600);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:14px;height:14px;">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </div>
    <!-- Modal Body -->
    <div id="evPreviewBody" style="flex:1;overflow:auto;background:var(--n50);display:flex;align-items:center;justify-content:center;min-height:400px;">
      <!-- content injected by JS -->
    </div>
  </div>
</div>

<script>
  function openEvPreview(url, name, mime) {
    document.getElementById('evPreviewName').textContent = name;
    document.getElementById('evPreviewIcon').textContent = mimeToIcon(mime);

    const body = document.getElementById('evPreviewBody');
    body.innerHTML = '';

    if (mime.startsWith('image/')) {
      const img = document.createElement('img');
      img.src = url;
      img.style.cssText = 'max-width:100%;max-height:75vh;object-fit:contain;border-radius:6px;';
      body.appendChild(img);
    } else if (mime === 'application/pdf') {
      const iframe = document.createElement('iframe');
      iframe.src = url;
      iframe.style.cssText = 'width:100%;height:75vh;border:none;';
      body.appendChild(iframe);
    } else {
      body.innerHTML = `
        <div style="text-align:center;padding:48px 24px;">
          <div style="font-size:48px;margin-bottom:12px;">${mimeToIcon(mime)}</div>
          <div style="font-size:15px;font-weight:700;color:var(--n800);margin-bottom:6px;">${name}</div>
          <div style="font-size:13px;color:var(--n400);margin-bottom:20px;">This file type cannot be previewed in the browser.</div>
          <a href="${url}" download="${name}"
            style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;background:var(--teal);color:#fff;font-size:13px;font-weight:600;text-decoration:none;">
            Download to view
          </a>
        </div>`;
    }

    const modal = document.getElementById('evPreviewModal');
    modal.style.display = 'block';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    // center the inner box manually
    const inner = modal.querySelector('div');
    inner.style.margin = 'auto';
    inner.style.position = 'relative';
    inner.style.top = '50%';
    inner.style.transform = 'translateY(-50%)';
    document.body.style.overflow = 'hidden';
  }

  function closeEvPreview() {
    document.getElementById('evPreviewModal').style.display = 'none';
    document.getElementById('evPreviewBody').innerHTML = '';
    document.body.style.overflow = '';
  }

  function mimeToIcon(mime) {
    if (mime.startsWith('image/')) return '🖼️';
    if (mime === 'application/pdf') return '📄';
    if (mime.includes('word')) return '📝';
    if (mime.includes('sheet') || mime.includes('excel')) return '📊';
    if (mime.includes('presentation') || mime.includes('powerpoint')) return '📊';
    return '📎';
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEvPreview(); });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>