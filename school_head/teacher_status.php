<?php
ob_start();
// school_head/teacher_status.php — Teacher Submission Status page
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';

requireRole('school_head', 'sbm_coordinator');
$db       = getDB();
$schoolId = SCHOOL_ID;
$syId     = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if (!$syId && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_teacher_reminder') {
    header('Content-Type: application/json; charset=utf-8');
    if (($_SESSION['role'] ?? '') !== 'sbm_coordinator') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Only the SBM Coordinator can send teacher reminders.']);
        exit;
    }
    verifyCsrf();
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'There is no current school year for this reminder.']);
    exit;
}

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

$currentSY = $db->query("SELECT label FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_teacher_reminder') {
    header('Content-Type: application/json; charset=utf-8');
    if (($_SESSION['role'] ?? '') !== 'sbm_coordinator') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Only the SBM Coordinator can send teacher reminders.']);
        exit;
    }

    verifyCsrf();
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    if (!$syId || !$cycle || (int) ($_POST['cycle_id'] ?? 0) !== (int) $cycle['cycle_id'] || $teacherId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'The current assessment cycle or teacher is invalid. Refresh the page and try again.']);
        exit;
    }

    try {
        $db->beginTransaction();
        $teacherStmt = $db->prepare("
            SELECT user_id, full_name, email
            FROM users
            WHERE user_id = ? AND school_id = ? AND role = 'teacher' AND status = 'active'
            FOR UPDATE
        ");
        $teacherStmt->execute([$teacherId, $schoolId]);
        $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
        if (!$teacher) {
            throw new DomainException('This teacher is not active in your school.');
        }
        if (trim((string) $teacher['email']) === '') {
            throw new DomainException('This teacher does not have an email address on file.');
        }

        $submissionStmt = $db->prepare("
            SELECT status
            FROM teacher_submissions
            WHERE cycle_id = ? AND school_id = ? AND teacher_id = ?
            ORDER BY submission_id DESC
            LIMIT 1
        ");
        $submissionStmt->execute([$cycle['cycle_id'], $schoolId, $teacherId]);
        $submissionStatus = $submissionStmt->fetchColumn();
        if ($submissionStatus === 'submitted') {
            throw new DomainException('This teacher has already submitted the current assessment.');
        }

        $cooldownStmt = $db->prepare("
            SELECT sent_at
            FROM email_logs
            WHERE user_id = ? AND email_type = 'assessment_reminder' AND status = 'sent'
              AND sent_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY sent_at DESC
            LIMIT 1
        ");
        $cooldownStmt->execute([$teacherId]);
        if ($cooldownStmt->fetchColumn()) {
            throw new DomainException('A reminder was sent to this teacher within the last 24 hours. Please try again later.');
        }

        require_once __DIR__ . '/../includes/email_service.php';
        $assessmentLink = baseUrl() . '/teacher/self_assessment.php';
        $isReturned = $submissionStatus === 'returned';
        $sent = sendAssessmentReminderEmail(
            $db,
            $teacher,
            (string) $currentSY,
            $assessmentLink,
            $isReturned
        );

        $details = sprintf(
            'Teacher ID %d; cycle ID %d; delivery %s',
            $teacherId,
            (int) $cycle['cycle_id'],
            $sent ? 'sent' : 'failed'
        );
        $db->prepare("
            INSERT INTO activity_log (user_id, action, module, details, ip_address)
            VALUES (?, 'send_teacher_assessment_reminder', 'teacher_status', ?, ?)
        ")->execute([
            (int) $_SESSION['user_id'],
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        $db->commit();

        if (!$sent) {
            http_response_code(502);
            echo json_encode(['ok' => false, 'msg' => 'The reminder email could not be sent. Please try again later.']);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'msg' => $isReturned
                ? 'Revision reminder sent to the teacher.'
                : 'Assessment reminder sent to the teacher.',
        ]);
    } catch (DomainException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Coordinator teacher reminder failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'The reminder could not be processed. Please try again later.']);
    }
    exit;
}

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

if ($cycle) {
  $latestTeacherSubmissionJoin = "
    LEFT JOIN (
      SELECT ts1.*
      FROM teacher_submissions ts1
      INNER JOIN (
        SELECT cycle_id, school_id, teacher_id, MAX(submission_id) AS submission_id
        FROM teacher_submissions
        GROUP BY cycle_id, school_id, teacher_id
      ) latest_ts ON latest_ts.submission_id = ts1.submission_id
    ) ts ON ts.teacher_id = u.user_id AND ts.cycle_id = ? AND ts.school_id = ?
  ";

    $totStmt = $db->prepare("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN ts.status = 'submitted' THEN 1 ELSE 0 END) AS submitted
        FROM users u
    $latestTeacherSubmissionJoin
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
    ");
    $totStmt->execute([$cycle['cycle_id'], $schoolId, $schoolId]);
    $totRow            = $totStmt->fetch();
    $totalTeachers     = (int)$totRow['total'];
    $submittedTeachers = (int)$totRow['submitted'];
    $pendingCount      = $totalTeachers - $submittedTeachers;

    // ── Filtered count ──────────────────────────────────────────
    $searchParam = "%{$teacherSearch}%";
    switch ($tsFilter) {
        case 'pending': $filterSQL = "AND (ts.status IS NULL OR ts.status != 'submitted')"; break;
        case 'done':    $filterSQL = "AND ts.status = 'submitted'"; break;
        default:        $filterSQL = '';
    }
    $searchSQL = $teacherSearch !== ''
        ? "AND (u.full_name LIKE ? OR u.username LIKE ?)"
        : '';

    $countParams = [$cycle['cycle_id'], $schoolId, $schoolId];
    if ($teacherSearch !== '') { $countParams[] = $searchParam; $countParams[] = $searchParam; }

    $filtStmt = $db->prepare("
        SELECT COUNT(*)
        FROM users u
        $latestTeacherSubmissionJoin
        WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
        $searchSQL $filterSQL
    ");
    $filtStmt->execute($countParams);
    $filteredTotal     = (int)$filtStmt->fetchColumn();
    $teacherTotalPages = max(1, (int)ceil($filteredTotal / $perPage));
    $teacherPage       = min($teacherPage, $teacherTotalPages);
    $offset            = ($teacherPage - 1) * $perPage;

    // ── Paginated fetch ─────────────────────────────────────────
    $pageParams = [$cycle['cycle_id'], $schoolId, $schoolId];
    if ($teacherSearch !== '') { $pageParams[] = $searchParam; $pageParams[] = $searchParam; }
    $pageParams[] = $perPage;
    $pageParams[] = $offset;

    $pageStmt = $db->prepare("
        SELECT u.user_id, u.full_name, u.email, u.username, u.department,
               ts.status      AS sub_status,
               ts.submitted_at,
               ts.response_count
        FROM users u
        $latestTeacherSubmissionJoin
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

$pageTitle    = 'Teacher Submission Status';
$activePage   = 'teacher_status.php';
include __DIR__.'/../includes/header.php';
?>

<style>
/* ── Page layout ─────────────────────────────────────── */
.ts-page-wrap    { width: 100%;padding:0 20px;box-sizing:border-box; }

/* ── Panel ───────────────────────────────────────────── */
.ts-panel        { margin-bottom:16px;border-color:var(--n-150);box-shadow:none;overflow:hidden; }
/* ── Filters and search ───────────────────────────────── */
.ts-toolbar      { display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;width:100%;padding:8px 20px;border-bottom:1px solid var(--n100); }
.ts-search-wrap  { flex:0 1 320px;min-width:220px; }
.ts-search-wrap input { width:100%; }
.ts-filter-tabs  { display:flex;align-items:center;gap:0;overflow-x:auto;white-space:nowrap;margin-left:auto; }
.ts-tab          { padding:7px 10px;font-size:12px;font-weight:500;border:0;border-bottom:1px solid transparent;background:transparent;color:var(--n500);cursor:pointer;text-decoration:none;white-space:nowrap;transition:color .15s,border-color .15s; }
.ts-tab:hover    { color:var(--n800); }
.ts-tab.active-all,
.ts-tab.active-pending,
.ts-tab.active-done { color:var(--n900);font-weight:600;border-bottom-color:var(--n700); }
.ts-tab-separator { color:var(--n300);font-size:12px; }
.ts-clear        { font-size:12px;color:var(--n500);text-decoration:none;white-space:nowrap;padding:4px 6px; }
.ts-clear:hover  { color:var(--n800); }

/* ── Warning banner ──────────────────────────────────── */
/* ── Teacher rows ────────────────────────────────────── */
.ts-list         { padding:10px 20px; display:flex;flex-direction:column;gap:6px; }
.ts-row          { display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:9px;border:1px solid transparent; }
.ts-row.pending  { background:#FFF7ED;border-color:#FDE68A; }
.ts-row.done     { background:#F0FDF4;border-color:#BBF7D0; }
.ts-avatar       { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;background:#F1F3F5;color:#667085; }
.ts-avatar.pending,
.ts-avatar.returned,
.ts-avatar.done    { background:#F1F3F5;color:#667085; }
.ts-info         { flex:1;min-width:0; }
.ts-name         { font-size:13px;font-weight:600;color:var(--n800);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.ts-meta         { font-size:11.5px;color:var(--n500);margin-top:2px; }
.ts-meta a       { color:var(--blue);text-decoration:none; }
.ts-meta a:hover { text-decoration:underline; }
.ts-empty        { text-align:center;padding:40px 20px;color:var(--n400);font-size:13px; }

/* ── Table layout ─────────────────────────────────────── */
.ts-table.w-full { width:100%;table-layout:fixed; }
.ts-table-wrap   { overflow-x:visible; }
.ts-table.tbl-enhanced thead th { padding:9px 12px; }
.ts-table.tbl-enhanced tbody td { padding:7px 12px; }
.ts-table.tbl-enhanced tbody tr:hover td { background:#F9FAFB; }
.ts-table.tbl-enhanced tbody tr.ts-row-pending td { background:#FFF9ED; }
.ts-table.tbl-enhanced tbody tr.ts-row-pending td:first-child { box-shadow:inset 3px 0 0 #D6A74F; }
.ts-table.tbl-enhanced tbody tr.ts-row-pending:hover td { background:#FFF5E3; }
.ts-table th.ts-th-teacher { width:38%; }
.ts-table th.ts-th-department { width:20%; }
.ts-table th.ts-th-submitted { width:24%; }
.ts-table th.ts-th-actions { width:18%;text-align:right; }
.ts-table td.ts-td-actions { text-align:right; }
.ts-action-stack { display:flex;flex-direction:column;align-items:flex-end;gap:4px; }
.ts-reminder-feedback { max-width:180px;font-size:11px;line-height:1.35;text-align:right;overflow-wrap:anywhere; }
.ts-td-sub      { font-size:12px;color:var(--n500)!important; }
.ts-neutral-avatar { width:34px;height:34px;border-radius:9px;background:var(--n-100);color:var(--n-600); }
.ts-department { color:var(--n500);font-size:12px;font-weight:400; }
.ts-remind-link { display:inline-flex;align-items:center;justify-content:center;min-height:26px;padding:3px 9px;border:1px solid var(--n-400);border-radius:5px;background:#fff;color:var(--n-700);font:inherit;font-size:11.5px;text-decoration:none;cursor:pointer; }
.ts-remind-link:hover { border-color:var(--n-500);color:var(--n-800);background:var(--n-50); }

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
  .ts-toolbar { padding:12px 16px; }
  .ts-search-wrap { flex-basis:100%;min-width:0; }
  .ts-filter-tabs { max-width:100%; }
  .ts-pbtn        { min-width:30px;height:30px;font-size:11.5px; }
  .ts-table.tbl-enhanced thead th,
  .ts-table.tbl-enhanced tbody td { padding-left:8px;padding-right:8px; }
}

@media(max-width:900px){
  .ts-table th.ts-th-teacher { width:36%; }
  .ts-table th.ts-th-department { width:19%; }
  .ts-table th.ts-th-submitted { width:27%; }
  .ts-table th.ts-th-actions { width:18%; }
}

@media(max-width:600px){
  .ts-table-wrap { overflow-x:auto; }
  .ts-table th.ts-th-teacher { width:38%; }
  .ts-table th.ts-th-department { width:18%; }
  .ts-table th.ts-th-submitted { width:26%; }
  .ts-table th.ts-th-actions { width:18%; }
  .ts-table.tbl-enhanced thead th { font-size:9px; }
  .ts-table.tbl-enhanced thead th,
  .ts-table.tbl-enhanced tbody td { padding-left:6px;padding-right:6px; }
  .ts-table .cell-avatar { gap:6px; }
  .ts-table .cell-av { width:28px;height:28px;flex:0 0 28px; }
  .ts-table .cell-av-name { font-size:11px; }
  .ts-table .cell-av-sub { font-size:10px; }
  .ts-table .ts-td-sub { font-size:10.5px; }
  .ts-table th.ts-th-actions,
  .ts-table td.ts-td-actions { text-align:right; }
  .ts-remind-link { min-height:24px;padding:2px 5px;font-size:10px; }
}
</style>

<!-- ── Page header ─────────────────────────────────────────── -->
<?php if (empty($_COORDINATOR_VIEW)): ?>
<div class="page-head">
  <div class="page-head-text">
    <h2>Teacher Submission Status</h2>
    <p>Monitor teacher assessment submissions for <?= e($currentSY ?: 'the current school year') ?>.</p>
  </div>
</div>
<?php endif; ?>

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

<!-- ── Main panel ─────────────────────────────────────────────── -->
<div class="card ts-panel">

  <div class="ts-toolbar">
    <form method="GET" action="" style="display:contents;" id="tsSearchForm">
      <div class="search ts-search-wrap">
        <span class="si">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input id="tsSearchInput"
               type="text" name="ts"
               value="<?= e($teacherSearch) ?>"
               placeholder="Search by name or username…"
               autocomplete="off"
               aria-label="Search teachers"
               oninput="tsDebounce(this.form)">
        <input type="hidden" name="tf" value="<?= e($tsFilter) ?>">
        <input type="hidden" name="tp" value="1">
      </div>
    </form>
    <div class="ts-filter-tabs">
      <?php foreach (['all' => ['All', $totalTeachers], 'pending' => ['Pending', $pendingCount], 'done' => ['Submitted', $submittedTeachers]] as $fk => [$fl, $filterCount]): ?>
      <?php if ($fk !== 'all'): ?><span class="ts-tab-separator" aria-hidden="true">·</span><?php endif; ?>
      <a href="<?= e(tsUrl(1, $teacherSearch, $fk)) ?>"
         class="ts-tab <?= $tsFilter === $fk ? 'active-'.$fk : '' ?>">
        <?= $fl ?> <?= $filterCount ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div id="tsResultsArea">
  <!-- Teacher list -->
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
  <div class="tbl-wrap ts-table-wrap">
    <table class="tbl-enhanced ts-table w-full">
      <thead>
        <tr>
          <th class="ts-th-teacher">Teacher</th>
          <th class="ts-th-department">Department</th>
          <th class="ts-th-submitted">Submitted On</th>
          <th class="ts-th-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingTeachers as $t):
          $submitted  = $t['sub_status'] === 'submitted';
          $returned = $t['sub_status'] === 'returned';
          // Initials avatar
          $parts    = array_filter(explode(' ', trim($t['full_name'])));
          $initials = strtoupper(substr($parts[0] ?? 'T', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        ?>
        <tr class="<?= !$submitted ? 'ts-row-pending' : '' ?>">
          <td>
            <div class="cell-avatar">
              <div class="cell-av ts-neutral-avatar"><?= e($initials) ?></div>
              <div class="cell-av-info">
                <div class="cell-av-name"><?= e($t['full_name']) ?></div>
                <div class="cell-av-sub"><?= e($t['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="ts-td-sub">
            <?php if (trim((string) ($t['department'] ?? '')) !== ''): ?>
              <span class="ts-department"><?= e($t['department']) ?></span>
            <?php else: ?>
              <span style="color:var(--n-400);font-style:italic;">Not set</span>
            <?php endif; ?>
          </td>
          <td class="ts-td-sub">
            <?php if ($submitted): ?>
              <?= $t['submitted_at'] ? date('M d, Y · g:i A', strtotime($t['submitted_at'])) : '—' ?>
            <?php elseif ($returned): ?>
              Returned for revision
            <?php else: ?>
              Not yet submitted
            <?php endif; ?>
          </td>
          <td class="ts-td-actions">
            <?php if (!$submitted && !empty($_COORDINATOR_VIEW) && !empty($t['email'])): ?>
              <div class="ts-action-stack">
                <button type="button" class="ts-remind-link"
                  data-reminder-button
                  data-teacher-id="<?= (int) $t['user_id'] ?>"
                  data-cycle-id="<?= (int) $cycle['cycle_id'] ?>"
                  data-csrf="<?= e(csrfToken()) ?>">Remind</button>
                <span id="reminder-feedback-<?= (int) $t['user_id'] ?>"
                  class="ts-reminder-feedback" role="status" aria-live="polite"></span>
              </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

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
  </div>

</div><!-- /.ts-panel -->
</div><!-- /.ts-page-wrap -->

<?php endif; ?>

<script>
(function () {
  const REFRESH_MS = 10000;
  let requestInFlight = false;
  let searchSubmitTimer = null;
  const requestWaiters = [];

  function acquireRequestSlot() {
    if (!requestInFlight) {
      requestInFlight = true;
      return Promise.resolve();
    }

    return new Promise(resolve => requestWaiters.push(resolve));
  }

  function releaseRequestSlot() {
    const nextRequest = requestWaiters.shift();
    if (nextRequest) {
      nextRequest();
    } else {
      requestInFlight = false;
    }
  }

  window.refreshTeacherStatus = async function() {
    const contentArea = document.getElementById('tsContentArea');
    const resultsArea = document.getElementById('tsResultsArea');
    const currentTabs = contentArea?.querySelector('.ts-filter-tabs');
    if (
      document.hidden ||
      !contentArea ||
      !resultsArea ||
      !currentTabs ||
      searchSubmitTimer !== null ||
      requestInFlight ||
      requestWaiters.length > 0
    ) return;

    requestInFlight = true;
    try {
      const url = new URL(window.location.href);
      const searchInput = document.getElementById('tsSearchInput');
      const filterInput = document.querySelector('#tsSearchForm [name="tf"]');
      const searchText = searchInput?.value ?? '';
      const activeFilter = filterInput?.value ?? 'all';
      const searchChanged = searchText !== (url.searchParams.get('ts') ?? '');

      if (searchText !== '') url.searchParams.set('ts', searchText);
      else url.searchParams.delete('ts');
      if (activeFilter !== 'all') url.searchParams.set('tf', activeFilter);
      else url.searchParams.delete('tf');
      if (searchChanged) url.searchParams.set('tp', '1');

      const response = await fetch(url.toString(), { headers: { Accept: 'text/html' } });
      if (!response.ok) return;
      const html = await response.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const newResults = doc.getElementById('tsResultsArea');
      const newTabs = doc.querySelector('#tsContentArea .ts-filter-tabs');
      if (!newResults || !newTabs) return;
      if (requestWaiters.length > 0) return;

      const scrollX = window.scrollX;
      const scrollY = window.scrollY;
      const tableWrap = resultsArea.querySelector('.tbl-wrap');
      const tableScrollLeft = tableWrap?.scrollLeft ?? 0;
      const previousActions = new Map();
      resultsArea.querySelectorAll('[data-reminder-button]').forEach(button => {
        const actionCell = button.closest('.ts-td-actions');
        if (actionCell) previousActions.set(button.dataset.teacherId, actionCell.innerHTML);
      });
      newResults.querySelectorAll('[data-reminder-button]').forEach(button => {
        const previousAction = previousActions.get(button.dataset.teacherId);
        const actionCell = button.closest('.ts-td-actions');
        if (previousAction !== undefined && actionCell) actionCell.innerHTML = previousAction;
      });

      resultsArea.replaceChildren(...Array.from(newResults.childNodes, node => node.cloneNode(true)));
      currentTabs.replaceChildren(...Array.from(newTabs.childNodes, node => node.cloneNode(true)));
      const refreshedTableWrap = resultsArea.querySelector('.tbl-wrap');
      if (refreshedTableWrap) refreshedTableWrap.scrollLeft = tableScrollLeft;
      window.scrollTo(scrollX, scrollY);
    } catch (error) {
      // Background refresh failures leave the current table unchanged.
    } finally {
      releaseRequestSlot();
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

    window.tsDebounce = function (form) {
      sessionStorage.setItem(INPUT_KEY, '1');
      sessionStorage.setItem(POS_KEY, inp?.selectionStart ?? inp?.value.length ?? 0);
      clearTimeout(searchSubmitTimer);
      searchSubmitTimer = window.setTimeout(() => {
        searchSubmitTimer = null;
        form.submit();
      }, 500);
    };

    if (inp) {
      inp.addEventListener('blur', () => {
        clearTimeout(searchSubmitTimer);
        searchSubmitTimer = null;
        sessionStorage.removeItem(INPUT_KEY);
        sessionStorage.removeItem(POS_KEY);
      });
    }
  }

  // Initialize on page load
  bindSearchLogic();

  let refreshTimer;
  if (document.getElementById('tsContentArea')) {
    refreshTimer = window.setInterval(() => {
      if (!document.hidden) window.refreshTeacherStatus();
    }, REFRESH_MS);
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) window.refreshTeacherStatus();
    });
    window.addEventListener('beforeunload', () => window.clearInterval(refreshTimer));
  }

  document.addEventListener('click', async function (event) {
    const button = event.target instanceof Element
      ? event.target.closest('[data-reminder-button]')
      : null;
    if (!button || button.disabled) return;

    const feedback = document.getElementById(`reminder-feedback-${button.dataset.teacherId}`);
    const formData = new FormData();
    formData.append('action', 'send_teacher_reminder');
    formData.append('teacher_id', button.dataset.teacherId);
    formData.append('cycle_id', button.dataset.cycleId);
    formData.append('csrf_token', button.dataset.csrf);
    button.disabled = true;
    button.textContent = 'Sending…';
    if (feedback) {
      feedback.textContent = '';
      feedback.style.color = '';
    }

    await acquireRequestSlot();
    try {
      const response = await fetch(window.location.href, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: formData
      });
      const result = await response.json();
      if (!response.ok || !result.ok) {
        if (feedback) {
          feedback.textContent = result.msg || 'The reminder could not be sent.';
          feedback.style.color = '#B91C1C';
        }
        return;
      }

      if (feedback) {
        feedback.textContent = result.msg;
        feedback.style.color = '#15803D';
      }
      button.textContent = 'Sent';
      return;
    } catch (error) {
      if (feedback) {
        feedback.textContent = 'The reminder could not be sent. Please try again.';
        feedback.style.color = '#B91C1C';
      }
    } finally {
      if (button.isConnected && button.textContent !== 'Sent') {
        button.disabled = false;
        button.textContent = 'Remind';
      }
      releaseRequestSlot();
    }
  });
})();
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>