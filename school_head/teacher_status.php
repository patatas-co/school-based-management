<?php
// school_head/teacher_status.php — Teacher Submission Status page
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';

requireRole('school_head', 'admin');
$db       = getDB();
$schoolId = SCHOOL_ID;
$syId     = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if (!$syId) {
    $pageTitle  = 'Teacher Submission Status';
    $activePage = 'teacher_status.php';
    include __DIR__.'/../includes/header.php';
    echo '<div class="alert alert-warning">No active school year configured.</div>';
    include __DIR__.'/../includes/footer.php';
    exit;
}

// ── Active cycle ──────────────────────────────────────────────
$cycle = $db->prepare("SELECT * FROM sbm_cycles WHERE school_id=? AND sy_id=?");
$cycle->execute([$schoolId, $syId]);
$cycle = $cycle->fetch();

// ── Search / pagination / filter params ──────────────────────
$teacherSearch  = trim($_GET['ts'] ?? '');
$teacherPage    = max(1, (int)($_GET['tp'] ?? 1));
$tsFilter       = in_array($_GET['tf'] ?? '', ['all','pending','done'])
                  ? ($_GET['tf'] ?? 'all') : 'all';
$perPage        = 15;

// ── Global totals (always all teachers, ignoring search+filter) ──
$totalTeachers     = 0;
$submittedTeachers = 0;
$pendingCount      = 0;
$teacherTotalPages = 1;
$pendingTeachers   = [];
$filteredTotal     = 0;
$currentSY         = $db->query("SELECT label FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if ($cycle) {
    $totStmt = $db->prepare("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN ts.status = 'submitted' THEN 1 ELSE 0 END) AS submitted
        FROM users u
        LEFT JOIN teacher_submissions ts
            ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
    ");
    $totStmt->execute([$cycle['cycle_id'], $schoolId]);
    $totRow            = $totStmt->fetch();
    $totalTeachers     = (int)$totRow['total'];
    $submittedTeachers = (int)$totRow['submitted'];
    $pendingCount      = $totalTeachers - $submittedTeachers;

    // ── Filtered count ──────────────────────────────────────────
    $searchParam = "%{$teacherSearch}%";
    $filterSQL   = match($tsFilter) {
        'pending' => "AND (ts.status IS NULL OR ts.status != 'submitted')",
        'done'    => "AND ts.status = 'submitted'",
        default   => ''
    };
    $searchSQL = $teacherSearch !== ''
        ? "AND (u.full_name LIKE ? OR u.username LIKE ?)"
        : '';

    $countParams = [$cycle['cycle_id'], $schoolId];
    if ($teacherSearch !== '') { $countParams[] = $searchParam; $countParams[] = $searchParam; }

    $filtStmt = $db->prepare("
        SELECT COUNT(*)
        FROM users u
        LEFT JOIN teacher_submissions ts
            ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
        $searchSQL $filterSQL
    ");
    $filtStmt->execute($countParams);
    $filteredTotal     = (int)$filtStmt->fetchColumn();
    $teacherTotalPages = max(1, (int)ceil($filteredTotal / $perPage));
    $teacherPage       = min($teacherPage, $teacherTotalPages);
    $offset            = ($teacherPage - 1) * $perPage;

    // ── Paginated fetch ─────────────────────────────────────────
    $pageParams = [$cycle['cycle_id'], $schoolId];
    if ($teacherSearch !== '') { $pageParams[] = $searchParam; $pageParams[] = $searchParam; }
    $pageParams[] = $perPage;
    $pageParams[] = $offset;

    $pageStmt = $db->prepare("
        SELECT u.user_id, u.full_name, u.email, u.username,
               ts.status      AS sub_status,
               ts.submitted_at,
               ts.response_count
        FROM users u
        LEFT JOIN teacher_submissions ts
            ON ts.teacher_id = u.user_id AND ts.cycle_id = ?
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
        $searchSQL $filterSQL
        ORDER BY
            CASE WHEN ts.status = 'submitted' THEN 1 ELSE 0 END ASC,
            u.full_name ASC
        LIMIT ? OFFSET ?
    ");
    $pageStmt->execute($pageParams);
    $pendingTeachers = $pageStmt->fetchAll();
}

// ── Pagination URL builder ────────────────────────────────────
function tsUrl(int $page, string $search = '', string $filter = 'all'): string {
    $q = [];
    if ($page   > 1)    $q['tp'] = $page;
    if ($search !== '') $q['ts'] = $search;
    if ($filter !== 'all') $q['tf'] = $filter;
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    return $base . ($q ? '?' . http_build_query($q) : '');
}

$submittedPct = $totalTeachers > 0 ? round(($submittedTeachers / $totalTeachers) * 100) : 0;
$pageTitle    = 'Teacher Submission Status';
$activePage   = 'teacher_status.php';
include __DIR__.'/../includes/header.php';
?>

<style>
/* ── Spinner Animation ───────────────────────────────── */
@keyframes spin-anim {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.spin { animation: spin-anim 1s linear infinite; }

/* ── Page layout ─────────────────────────────────────── */
.ts-page-wrap    { width: 100%; padding: 0 20px; }

/* ── Summary cards ───────────────────────────────────── */
.ts-summary      { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px; }
.ts-card         { background:var(--white);border:1px solid var(--n200);border-radius:var(--radius);padding:16px 20px;display:flex;flex-direction:column;gap:4px; }
.ts-card-val     { font-size:28px;font-weight:800;font-family:var(--font-display); }
.ts-card-lbl     { font-size:12px;font-weight:600;color:var(--n500);text-transform:uppercase;letter-spacing:.04em; }
.ts-card-bar     { height:4px;border-radius:999px;background:var(--n100);margin-top:8px;overflow:hidden; }
.ts-card-fill    { height:100%;border-radius:999px;transition:width .4s; }

/* ── Panel ───────────────────────────────────────────── */
.ts-panel        { background:var(--white);border:1px solid var(--n200);border-radius:var(--radius);overflow:hidden; }
.ts-panel-head   { padding:16px 20px 0;border-bottom:1px solid var(--n100); }
.ts-panel-title  { font-size:14px;font-weight:700;color:var(--n800);margin-bottom:12px; }

/* ── Toolbar ─────────────────────────────────────────── */
.ts-toolbar      { display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 20px;border-bottom:1px solid var(--n100); }
.ts-search-wrap  { position:relative;flex:1;min-width:200px; }
.ts-search-icon  { position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;stroke:var(--n400);pointer-events:none; }
.ts-search-input { width:100%;padding:8px 10px 8px 34px;font-size:13px;border:1px solid var(--n200);border-radius:8px;outline:none;color:var(--n800);background:var(--n50);transition:border-color .15s; }
.ts-search-input:focus { border-color:var(--blue);background:var(--white); }
.ts-filter-tabs  { display:flex;gap:4px; }
.ts-tab          { padding:7px 14px;font-size:12.5px;font-weight:600;border:1px solid var(--n200);border-radius:8px;background:var(--white);color:var(--n500);cursor:pointer;text-decoration:none;white-space:nowrap;transition:all .15s; }
.ts-tab:hover    { background:var(--n100); }
.ts-tab.active-all     { background:var(--n800);color:#fff;border-color:var(--n800); }
.ts-tab.active-pending { background:#FEF3C7;color:#92400E;border-color:#FDE68A; }
.ts-tab.active-done    { background:#DCFCE7;color:#166534;border-color:#86EFAC; }
.ts-clear        { font-size:12px;color:var(--n400);text-decoration:none;white-space:nowrap;padding:4px 6px;border-radius:6px; }
.ts-clear:hover  { background:var(--n100); }

/* ── Warning banner ──────────────────────────────────── */
.ts-warning      { display:flex;align-items:center;gap:8px;margin:0;padding:9px 14px;background:#FFF7ED;border:1px solid #FDE68A;border-radius:8px;font-size:12.5px;color:#92400E; }

/* ── Teacher rows ────────────────────────────────────── */
.ts-list         { padding:10px 20px; display:flex;flex-direction:column;gap:6px; }
.ts-row          { display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:9px;border:1px solid transparent; }
.ts-row.pending  { background:#FFF7ED;border-color:#FDE68A; }
.ts-row.done     { background:#F0FDF4;border-color:#BBF7D0; }
.ts-avatar       { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0; }
.ts-avatar.pending { background:#FEF3C7;color:#92400E; }
.ts-avatar.done    { background:#DCFCE7;color:#166534; }
.ts-info         { flex:1;min-width:0; }
.ts-name         { font-size:13px;font-weight:600;color:var(--n800);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.ts-meta         { font-size:11.5px;color:var(--n500);margin-top:2px; }
.ts-meta a       { color:var(--blue);text-decoration:none; }
.ts-meta a:hover { text-decoration:underline; }
.ts-badge        { font-size:11px;font-weight:700;padding:4px 11px;border-radius:999px;flex-shrink:0; }
.ts-badge.pending { background:#FEF3C7;color:#D97706; }
.ts-badge.done    { background:#DCFCE7;color:#16A34A; }
.ts-empty        { text-align:center;padding:40px 20px;color:var(--n400);font-size:13px; }

/* ── Pagination ──────────────────────────────────────── */
.ts-pager        { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:12px 20px;border-top:1px solid var(--n100); }
.ts-pager-info   { font-size:12px;color:var(--n500); }
.ts-pager-btns   { display:flex;gap:4px;flex-wrap:wrap; }
.ts-pbtn         { min-width:34px;height:34px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:600;border:1px solid var(--n200);border-radius:8px;background:var(--white);color:var(--n600);text-decoration:none;transition:all .15s; }
.ts-pbtn:hover   { background:var(--n100); }
.ts-pbtn.active  { background:var(--n800);color:#fff;border-color:var(--n800);pointer-events:none; }
.ts-pbtn.disabled{ opacity:.35;pointer-events:none; }

/* ── No cycle state ──────────────────────────────────── */
.ts-nocycle      { text-align:center;padding:60px 20px; }
.ts-nocycle-icon { width:64px;height:64px;border-radius:50%;background:var(--n100);display:flex;align-items:center;justify-content:center;margin:0 auto 16px; }

@media(max-width:600px){
  .ts-summary     { grid-template-columns:1fr 1fr; }
  .ts-filter-tabs { display:none; }
  .ts-pbtn        { min-width:30px;height:30px;font-size:11.5px; }
}
</style>

<!-- ── Page header ─────────────────────────────────────────── -->
<div class="page-head">
  <div class="page-head-text">
    <h2>Teacher Submission Status</h2>
    <p>Monitor teacher assessment submissions for <?= e($currentSY ?: 'the current school year') ?>.</p>
  </div>
  <div class="page-head-actions">
    <button id="refreshDataBtn" class="btn btn-secondary" onclick="refreshTeacherStatus()" style="display:inline-flex;align-items:center;gap:6px;">
      <svg id="refreshIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <polyline points="23 4 23 10 17 10"></polyline>
        <polyline points="1 20 1 14 7 14"></polyline>
        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
      </svg>
      Refresh Data
    </button>
  </div>
</div>

<?php if (!$cycle): ?>
<!-- No active cycle ──────────────────────────────────────────── -->
<div class="card">
  <div class="card-body ts-nocycle">
    <div class="ts-nocycle-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
           stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px;stroke:var(--n400);">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
    </div>
    <h3 style="font-size:17px;font-weight:700;color:var(--n700);margin-bottom:8px;">No Assessment Cycle Started</h3>
    <p style="font-size:13.5px;color:var(--n500);max-width:400px;margin:0 auto 20px;line-height:1.6;">
      Teacher submission status will appear here once the SBM Self-Assessment cycle has been started.
    </p>
    <a href="<?= baseUrl() ?>/school_head/self_assessment.php" class="btn btn-primary">
      Go to Self-Assessment
    </a>
  </div>
</div>

<?php else: ?>

<div class="ts-page-wrap" id="tsContentArea">

<!-- ── Summary cards ─────────────────────────────────────────── -->
<div class="ts-summary">
  <div class="ts-card">
    <span class="ts-card-val" style="color:#16A34A;"><?= $submittedTeachers ?></span>
    <span class="ts-card-lbl">Submitted</span>
    <div class="ts-card-bar">
      <div class="ts-card-fill" style="width:<?= $submittedPct ?>%;background:#16A34A;"></div>
    </div>
  </div>
  <div class="ts-card">
    <span class="ts-card-val" style="color:#D97706;"><?= $pendingCount ?></span>
    <span class="ts-card-lbl">Pending</span>
    <div class="ts-card-bar">
      <div class="ts-card-fill"
           style="width:<?= $totalTeachers > 0 ? round(($pendingCount/$totalTeachers)*100) : 0 ?>%;
                  background:#D97706;"></div>
    </div>
  </div>
  <div class="ts-card">
    <span class="ts-card-val" style="color:var(--n700);"><?= $totalTeachers ?></span>
    <span class="ts-card-lbl">Total Teachers</span>
    <div class="ts-card-bar">
      <div class="ts-card-fill" style="width:100%;background:var(--n300);"></div>
    </div>
  </div>
</div>

<!-- ── Main panel ─────────────────────────────────────────────── -->
<div class="ts-panel">

  <div class="ts-panel-head">
    <div class="ts-panel-title">
      All Teachers
      <span style="font-size:11px;font-weight:600;color:var(--n400);margin-left:6px;">
        <?= $submittedPct ?>% submitted
      </span>
    </div>
  </div>

  <!-- Toolbar: search + filter tabs -->
  <div class="ts-toolbar">
    <form method="GET" action="" style="display:contents;" id="tsSearchForm">
      <div class="ts-search-wrap">
        <svg class="ts-search-icon" viewBox="0 0 24 24" fill="none" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input id="tsSearchInput"
               class="ts-search-input"
               type="text" name="ts"
               value="<?= e($teacherSearch) ?>"
               placeholder="Search by name or username…"
               autocomplete="off"
               oninput="tsDebounce(this.form)">
        <input type="hidden" name="tf" value="<?= e($tsFilter) ?>">
        <input type="hidden" name="tp" value="1">
      </div>
    </form>

    <div class="ts-filter-tabs">
      <?php foreach (['all' => 'All', 'pending' => 'Pending', 'done' => 'Submitted'] as $fk => $fl): ?>
      <a href="<?= e(tsUrl(1, $teacherSearch, $fk)) ?>"
         class="ts-tab <?= $tsFilter === $fk ? 'active-'.$fk : '' ?>">
        <?= $fl ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($teacherSearch !== ''): ?>
    <a href="<?= e(tsUrl(1, '', $tsFilter)) ?>" class="ts-clear">✕ Clear</a>
    <?php endif; ?>
  </div>

  <!-- Warning if teachers still pending -->
  <?php if ($pendingCount > 0): ?>
  <div style="padding:10px 20px 0;">
    <div class="ts-warning">
      <svg viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span>
        <strong><?= $pendingCount ?> teacher<?= $pendingCount > 1 ? 's' : '' ?></strong>
        <?= $pendingCount > 1 ? 'have' : 'has' ?> not yet submitted.
        Teacher averages will be based on responses received so far.
      </span>
    </div>
  </div>
  <?php endif; ?>

  <!-- Teacher list -->
  <div class="ts-list">
    <?php if (empty($pendingTeachers)): ?>
    <div class="ts-empty">
      <?php if ($teacherSearch !== ''): ?>
        No teachers match "<strong><?= e($teacherSearch) ?></strong>".
        <br><a href="<?= e(tsUrl(1, '', $tsFilter)) ?>" style="color:var(--blue);">Clear search</a>
      <?php else: ?>
        No teachers found<?= $tsFilter !== 'all' ? ' in this filter' : '' ?>.
      <?php endif; ?>
    </div>
    <?php else: ?>
    <?php foreach ($pendingTeachers as $t):
      $submitted  = $t['sub_status'] === 'submitted';
      $statusClass = $submitted ? 'done' : 'pending';
      // Initials avatar
      $parts    = array_filter(explode(' ', trim($t['full_name'])));
      $initials = strtoupper(substr($parts[0] ?? 'T', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    ?>
    <div class="ts-row <?= $statusClass ?>">
      <div class="ts-avatar <?= $statusClass ?>">
        <?= e($initials) ?>
      </div>
      <div class="ts-info">
        <div class="ts-name"><?= e($t['full_name']) ?></div>
        <div class="ts-meta">
          <?php if ($submitted): ?>
            Submitted <?= $t['submitted_at'] ? date('M d, Y · g:i A', strtotime($t['submitted_at'])) : '' ?>
            · <?= (int)$t['response_count'] ?> responses
          <?php else: ?>
            Not yet submitted
            <?php if ($t['email']): ?>
              · <a href="mailto:<?= e($t['email']) ?>?subject=<?= urlencode('SBM Self-Assessment Reminder') ?>&body=<?= urlencode("Dear {$t['full_name']},\n\nThis is a reminder to please submit your SBM Self-Assessment at your earliest convenience.\n\nThank you.") ?>">Send reminder</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <span class="ts-badge <?= $statusClass ?>">
        <?= $submitted ? 'Done' : 'Pending' ?>
      </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php
    $showFrom = ($teacherPage - 1) * $perPage + 1;
    $showTo   = min($teacherPage * $perPage, $filteredTotal);
  ?>
  <?php if ($teacherTotalPages > 1 || $teacherSearch !== ''): ?>
  <div class="ts-pager">
    <span class="ts-pager-info">
      Showing <?= $filteredTotal > 0 ? $showFrom : 0 ?>–<?= $showTo ?>
      of <?= $filteredTotal ?><?= $teacherSearch !== '' ? ' (filtered)' : '' ?> teachers
    </span>
    <div class="ts-pager-btns">
      <a href="<?= e(tsUrl($teacherPage - 1, $teacherSearch, $tsFilter)) ?>"
         class="ts-pbtn <?= $teacherPage <= 1 ? 'disabled' : '' ?>">‹</a>

      <?php
        $prev = null;
        for ($p = 1; $p <= $teacherTotalPages; $p++):
          $show = ($p === 1 || $p === $teacherTotalPages || abs($p - $teacherPage) <= 2);
          if (!$show) { if ($prev !== null && $prev !== -1) { echo '<span class="ts-pbtn disabled" style="border:none;background:none;">…</span>'; $prev = -1; } continue; }
      ?>
      <a href="<?= e(tsUrl($p, $teacherSearch, $tsFilter)) ?>"
         class="ts-pbtn <?= $p === $teacherPage ? 'active' : '' ?>"><?= $p ?></a>
      <?php $prev = $p; endfor; ?>

      <a href="<?= e(tsUrl($teacherPage + 1, $teacherSearch, $tsFilter)) ?>"
         class="ts-pbtn <?= $teacherPage >= $teacherTotalPages ? 'disabled' : '' ?>">›</a>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /.ts-panel -->
</div><!-- /.ts-page-wrap -->

<?php endif; ?>

<script>
(function () {
  // ── Refresh logic (AJAX DOM Swap) ─────────────────────────
  window.refreshTeacherStatus = async function() {
    const btn = document.getElementById('refreshDataBtn');
    const icon = document.getElementById('refreshIcon');
    const contentArea = document.getElementById('tsContentArea');
    
    if (!btn || !contentArea) return;
    
    // Start loading state
    btn.disabled = true;
    btn.style.opacity = '0.7';
    icon.classList.add('spin');
    
    try {
      const res = await fetch(window.location.href);
      if (!res.ok) throw new Error('Fetch failed');
      const html = await res.text();
      
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const newContent = doc.getElementById('tsContentArea');
      
      if (newContent) {
        contentArea.innerHTML = newContent.innerHTML;
        bindSearchLogic(); // Rebind events on new elements
      }
    } catch (err) {
      console.error('Refresh Failed', err);
    } finally {
      // Stop loading state
      btn.disabled = false;
      btn.style.opacity = '1';
      icon.classList.remove('spin');
    }
  };

  // ── Search Debounce logic ───────────────────────────────────
  function bindSearchLogic() {
    const INPUT_KEY = 'tsStatusFocused';
    const POS_KEY   = 'tsStatusCursorPos';
    const inp       = document.getElementById('tsSearchInput');

    if (inp && sessionStorage.getItem(INPUT_KEY) === '1') {
      inp.focus();
      const pos = parseInt(sessionStorage.getItem(POS_KEY) ?? inp.value.length, 10);
      inp.setSelectionRange(pos, pos);
    }

    let _timer;
    window.tsDebounce = function (form) {
      sessionStorage.setItem(INPUT_KEY, '1');
      sessionStorage.setItem(POS_KEY, inp?.selectionStart ?? inp?.value.length ?? 0);
      clearTimeout(_timer);
      _timer = setTimeout(() => form.submit(), 500);
    };

    if (inp) {
      inp.addEventListener('blur', () => {
        clearTimeout(_timer);
        sessionStorage.removeItem(INPUT_KEY);
        sessionStorage.removeItem(POS_KEY);
      });
    }
  }

  // Initialize on page load
  bindSearchLogic();
})();
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>