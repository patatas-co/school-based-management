<?php
// ============================================================
// school_head/dashboard.php — IMPROVED v2
// Key improvements:
// - Cleaner hero with assessment status prominence
// - Progress ring instead of a flat bar
// - Grouped dimension scores with maturity context
// - Teacher submission status as a visual checklist
// - Action CTAs tied to current workflow state
// - Better information hierarchy
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$uid = $_SESSION['user_id'];
$schoolId = SCHOOL_ID; // Always DIHS

$school = $db->prepare("SELECT * FROM schools WHERE school_id=?");
$school->execute([$schoolId]); $school = $school->fetch();

$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
$syLabel = $syId ? $db->query("SELECT label FROM school_years WHERE sy_id=$syId")->fetchColumn() : '—';

$cycle = null;
if ($schoolId && $syId) {
    $st = $db->prepare("SELECT c.*,sy.label sy_label FROM sbm_cycles c JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND c.sy_id=? LIMIT 1");
    $st->execute([$schoolId,$syId]); $cycle = $st->fetch();
}

$dimScores = [];
if ($cycle) {
    $st = $db->prepare("SELECT ds.*,d.dimension_no,d.dimension_name,d.color_hex,d.indicator_count FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
    $st->execute([$cycle['cycle_id']]); $dimScores = $st->fetchAll();
}

$totalResponded = 0;
if ($cycle) {
    $t = $db->prepare("SELECT COUNT(*) FROM sbm_responses WHERE cycle_id=?");
    $t->execute([$cycle['cycle_id']]); $totalResponded = $t->fetchColumn();
}
$totalIndicators = 42;
$progress = $totalIndicators > 0 ? round(($totalResponded/$totalIndicators)*100) : 0;

$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.target_role IN('all','school_head') ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

// Teacher submissions
$teacherList = [];
if ($cycle) {
    $tq = $db->prepare("
        SELECT u.user_id, u.full_name,
               ts.status sub_status, ts.submitted_at, ts.response_count,
               (SELECT COUNT(*) FROM teacher_responses tr WHERE tr.cycle_id=? AND tr.teacher_id=u.user_id) live_count
        FROM users u
        LEFT JOIN teacher_submissions ts ON ts.teacher_id=u.user_id AND ts.cycle_id=?
        WHERE u.school_id=? AND u.role='teacher' AND u.status='active'
        ORDER BY ts.status DESC, u.full_name ASC
    ");
    $tq->execute([$cycle['cycle_id'],$cycle['cycle_id'],$schoolId]);
    $teacherList = $tq->fetchAll();
}
$submittedTeachers = count(array_filter($teacherList, fn($t) => $t['sub_status'] === 'submitted'));
$totalTeachers = count($teacherList);

$pageTitle = 'Dashboard'; $activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>

<style>
/* ── Assessment Status Hero ── */
.sh-hero {
  background: var(--white);
  border: 1px solid var(--n-200);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 28px;
  flex-wrap: wrap;
  box-shadow: var(--shadow-xs);
}
.sh-hero-progress {
  flex-shrink: 0;
  position: relative;
  width: 100px; height: 100px;
}
.sh-hero-svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.sh-hero-svg circle { fill: none; stroke-width: 9; stroke-linecap: round; }
.sh-progress-track { stroke: var(--n-100); }
.sh-progress-fill { stroke: var(--brand-500); stroke-dasharray: 283; transition: stroke-dashoffset .8s cubic-bezier(.4,0,.2,1); }
.sh-hero-center {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%,-50%);
  text-align: center;
}
.sh-hero-pct {
  font-family: var(--font-display);
  font-size: 20px; font-weight: 800;
  color: var(--n-900); line-height: 1;
}
.sh-hero-pct-label { font-size: 10px; color: var(--n-400); font-weight: 600; }
.sh-hero-info { flex: 1; min-width: 200px; }
.sh-hero-school { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--n-900); margin-bottom: 4px; }
.sh-hero-sy { font-size: 13px; color: var(--n-500); margin-bottom: 14px; }
.sh-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Overall Score Badge ── */
.overall-score-badge {
  display: flex; flex-direction: column; align-items: center;
  padding: 20px 28px;
  border-radius: var(--radius-lg);
  border: 2px solid;
  text-align: center;
  flex-shrink: 0;
}
.overall-score-num {
  font-family: var(--font-display);
  font-size: 40px; font-weight: 800; line-height: 1; letter-spacing: -1px;
}
.overall-score-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; margin-top: 6px; }
.overall-maturity { font-size: 12px; font-weight: 600; margin-top: 4px; opacity: .75; }

/* ── Dimension Grid ── */
.dim-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.dim-tile {
  background: var(--white);
  border: 1px solid var(--n-200);
  border-radius: var(--radius);
  padding: 14px;
  border-top: 3px solid;
  box-shadow: var(--shadow-xs);
  transition: transform 150ms, box-shadow 150ms;
}
.dim-tile:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
.dim-tile-num { font-size: 10.5px; font-weight: 700; color: var(--n-400); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.dim-tile-name { font-size: 12.5px; font-weight: 700; color: var(--n-800); margin-bottom: 10px; line-height: 1.35; min-height: 32px; }
.dim-tile-score { font-family: var(--font-display); font-size: 22px; font-weight: 800; line-height: 1; margin-bottom: 8px; }
.dim-tile-prog { height: 5px; background: var(--n-100); border-radius: 999px; overflow: hidden; margin-bottom: 6px; }
.dim-tile-fill { height: 100%; border-radius: 999px; }
.dim-tile-mat { font-size: 11px; font-weight: 600; color: var(--n-500); }

/* ── Teacher Panel ── */
.teacher-panel { display: flex; flex-direction: column; gap: 6px; }
.teacher-row {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 8px;
  border: 1px solid var(--n-200); background: var(--n-50);
}
.teacher-row.submitted { background: #F0FDF4; border-color: #86EFAC; }
.teacher-avatar {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.teacher-name { flex: 1; font-size: 13px; font-weight: 600; color: var(--n-800); }
.teacher-status {
  font-size: 11px; font-weight: 700; padding: 3px 9px;
  border-radius: 999px; flex-shrink: 0;
}
.t-submitted { background: #DCFCE7; color: #16A34A; }
.t-progress  { background: #DBEAFE; color: #2563EB; }
.t-pending   { background: var(--n-100); color: var(--n-500); }

/* ── Announcement Card ── */
.ann-item {
  padding: 10px 0;
  border-bottom: 1px solid var(--n-100);
}
.ann-item:last-child { border-bottom: none; }
.ann-title { font-size: 13.5px; font-weight: 600; color: var(--n-900); margin: 4px 0 3px; }
.ann-meta { font-size: 11.5px; color: var(--n-400); }

/* ── Stat Cards (small) ── */
.sh-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}
.sh-stat {
  background: var(--white);
  border: 1px solid var(--n-200);
  border-radius: var(--radius-lg);
  padding: 16px 18px;
  box-shadow: var(--shadow-xs);
}
.sh-stat-val {
  font-family: var(--font-display);
  font-size: 26px; font-weight: 800;
  color: var(--n-900); line-height: 1; margin-bottom: 4px;
}
.sh-stat-lbl { font-size: 11.5px; color: var(--n-500); font-weight: 500; }

/* ── Returned Alert ── */
.returned-alert {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px; border-radius: 9px;
  background: #FEF3C7; border: 1px solid #FDE68A;
  margin-bottom: 16px;
}
.returned-alert svg { width: 16px; height: 16px; stroke: #D97706; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; margin-top: 1px; }
.returned-alert-text { font-size: 13.5px; color: #92400E; line-height: 1.5; }
.returned-alert-text strong { color: #78350F; }

@media (max-width: 768px) {
  .dim-grid { grid-template-columns: repeat(2, 1fr); }
  .sh-stats { grid-template-columns: repeat(2, 1fr); }
  .sh-hero { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 480px) {
  .dim-grid { grid-template-columns: 1fr; }
}
</style>

<?php
$isLocked = $cycle && in_array($cycle['status'], ['submitted','validated']);
$hasScore = $cycle && $cycle['overall_score'];
$mat = $hasScore ? sbmMaturityLevel(floatval($cycle['overall_score'])) : null;
?>

<?php if ($cycle && $cycle['status'] === 'returned'): ?>
<div class="returned-alert">
  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <div class="returned-alert-text">
    <strong>Assessment Returned for Revision</strong><br>
    <?php if ($cycle['validator_remarks']): ?>
    SDO Remarks: <?= e($cycle['validator_remarks']) ?>
    <?php endif; ?>
    <br><a href="self_assessment.php" style="color:#78350F;font-weight:700;">Revise your assessment →</a>
  </div>
</div>
<?php endif; ?>

<!-- ── HERO: School + Assessment Status ── -->
<div class="sh-hero">
  <!-- Progress Ring -->
  <div class="sh-hero-progress">
    <?php
    $circumference = 2 * 3.14159 * 45; // ~283
    $offset = $circumference - ($progress / 100) * $circumference;
    $strokeColor = $progress >= 100 ? '#16A34A' : ($progress >= 50 ? '#2563EB' : '#D97706');
    ?>
    <svg class="sh-hero-svg" viewBox="0 0 100 100">
      <circle class="sh-progress-track" cx="50" cy="50" r="45"/>
      <circle class="sh-progress-fill"
              cx="50" cy="50" r="45"
              stroke="<?= $strokeColor ?>"
              stroke-dashoffset="<?= $offset ?>"/>
    </svg>
    <div class="sh-hero-center">
      <div class="sh-hero-pct"><?= $progress ?>%</div>
      <div class="sh-hero-pct-label">done</div>
    </div>
  </div>

  <!-- School Info -->
  <div class="sh-hero-info">
    <div class="sh-hero-school">Dasmariñas Integrated High School</div>
    <div class="sh-hero-sy">
      School Year <?= e($syLabel) ?>
      &nbsp;·&nbsp;
      <?= $totalResponded ?>/<?= $totalIndicators ?> indicators rated
      &nbsp;·&nbsp;
      <?php if ($cycle): ?>
      <span class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst(str_replace('_',' ',$cycle['status'])) ?></span>
      <?php else: ?>
      <span class="pill pill-draft">Not Started</span>
      <?php endif; ?>
    </div>
    <div class="sh-hero-actions">
      <?php if (!$cycle || $cycle['status'] === 'draft' || $cycle['status'] === 'in_progress' || $cycle['status'] === 'returned'): ?>
      <a href="self_assessment.php" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= $cycle && $cycle['status'] === 'in_progress' ? 'Continue Assessment' : 'Start Assessment' ?>
      </a>
      <?php endif; ?>
      <a href="improvement.php" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        Improvement Plan
      </a>
      <a href="reports.php" class="btn btn-secondary">Reports</a>
    </div>
  </div>

  <!-- Overall Score -->
  <?php if ($hasScore): ?>
  <div class="overall-score-badge" style="border-color:<?= $mat['color'] ?>;background:<?= $mat['bg'] ?>;">
    <div class="overall-score-num" style="color:<?= $mat['color'] ?>;"><?= number_format($cycle['overall_score'],1) ?>%</div>
    <div class="overall-score-label" style="color:<?= $mat['color'] ?>;">Overall Score</div>
    <div class="overall-maturity" style="color:<?= $mat['color'] ?>;"><?= e($cycle['maturity_level']) ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- ── STAT CARDS ── -->
<div class="sh-stats">
  <div class="sh-stat">
    <div class="sh-stat-val"><?= $totalResponded ?></div>
    <div class="sh-stat-lbl">Indicators Rated</div>
  </div>
  <div class="sh-stat">
    <div class="sh-stat-val" style="color:<?= $hasScore ? $mat['color'] : 'var(--n-900)' ?>;"><?= $hasScore ? $cycle['overall_score'].'%' : '—' ?></div>
    <div class="sh-stat-lbl">SBM Score</div>
  </div>
  <div class="sh-stat">
    <div class="sh-stat-val" style="color:<?= $submittedTeachers === $totalTeachers && $totalTeachers > 0 ? 'var(--brand-700)' : 'var(--amber)' ?>;"><?= $submittedTeachers ?>/<?= $totalTeachers ?></div>
    <div class="sh-stat-lbl">Teachers Submitted</div>
  </div>
  <div class="sh-stat">
    <div class="sh-stat-val"><?= count($dimScores) ?></div>
    <div class="sh-stat-lbl">Dimensions Scored</div>
  </div>
</div>

<!-- ── MAIN GRID ── -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:18px;margin-bottom:20px;" class="db-layout-main">

  <div style="display:flex;flex-direction:column;gap:18px;">

    <!-- Dimension Grid -->
    <?php if ($dimScores): ?>
    <div>
      <div class="section-hd" style="margin-bottom:14px;">
        <span style="font-family:var(--font-display);font-size:15px;font-weight:700;color:var(--n-900);">Dimension Performance</span>
        <a href="dimensions.php" class="btn btn-ghost btn-sm">View details →</a>
      </div>
      <div class="dim-grid">
        <?php foreach($dimScores as $ds):
          $pct = floatval($ds['percentage']);
          $mat2 = sbmMaturityLevel($pct);
          $done = 0;
          if ($cycle) {
            $dd = $db->prepare("SELECT COUNT(*) FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id WHERE r.cycle_id=? AND i.dimension_id=?");
            $dd->execute([$cycle['cycle_id'],$ds['dimension_id']]); $done = $dd->fetchColumn();
          }
        ?>
        <a href="self_assessment.php#dim<?= $ds['dimension_no'] ?>" class="dim-tile" style="border-top-color:<?= e($ds['color_hex']) ?>;text-decoration:none;">
          <div class="dim-tile-num">Dimension <?= $ds['dimension_no'] ?></div>
          <div class="dim-tile-name" style="color:<?= e($ds['color_hex']) ?>;"><?= e($ds['dimension_name']) ?></div>
          <div class="dim-tile-score" style="color:<?= $mat2['color'] ?>;"><?= $pct > 0 ? $pct.'%' : '—' ?></div>
          <div class="dim-tile-prog"><div class="dim-tile-fill" style="width:<?= min(100,$pct) ?>%;background:<?= e($ds['color_hex']) ?>;"></div></div>
          <div class="dim-tile-mat"><?= $mat2['label'] ?> · <?= $done ?>/<?= $ds['indicator_count'] ?> rated</div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px;">
        <div style="font-size:36px;margin-bottom:12px;">📊</div>
        <h3 style="font-size:16px;font-weight:700;color:var(--n-700);margin-bottom:8px;">No dimension data yet</h3>
        <p style="font-size:13.5px;color:var(--n-400);margin-bottom:16px;">Start your self-assessment to see scores across all 6 SBM dimensions.</p>
        <a href="self_assessment.php" class="btn btn-primary">Start Assessment</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Teacher Submissions -->
    <?php if ($teacherList): ?>
    <div class="card">
      <div class="card-head">
        <span class="card-title">Teacher Submissions</span>
        <span style="font-size:13px;font-weight:700;color:<?= $submittedTeachers === $totalTeachers ? 'var(--brand-700)' : 'var(--amber)' ?>;">
          <?= $submittedTeachers ?>/<?= $totalTeachers ?> submitted
        </span>
      </div>
      <div class="card-body" style="padding:12px 16px;">
        <div class="teacher-panel">
          <?php foreach($teacherList as $t):
            $done = $t['sub_status'] === 'submitted';
            $inProg = !$done && $t['live_count'] > 0;
            $pctT = $t['live_count'] > 0 ? round(($t['live_count'] / count(TEACHER_INDICATOR_CODES)) * 100) : 0;
          ?>
          <div class="teacher-row <?= $done ? 'submitted' : '' ?>">
            <div class="teacher-avatar" style="background:<?= $done ? '#DCFCE7' : ($inProg ? '#DBEAFE' : 'var(--n-100)') ?>;color:<?= $done ? '#16A34A' : ($inProg ? '#2563EB' : 'var(--n-500)') ?>;">
              <?= strtoupper(substr($t['full_name'],0,1)) ?>
            </div>
            <div style="flex:1;min-width:0;">
              <div class="teacher-name"><?= e($t['full_name']) ?></div>
              <?php if (!$done && $inProg): ?>
              <div style="height:4px;background:var(--n-200);border-radius:999px;margin-top:4px;width:120px;overflow:hidden;">
                <div style="height:100%;width:<?= $pctT ?>%;background:#2563EB;border-radius:999px;"></div>
              </div>
              <?php endif; ?>
            </div>
            <span class="teacher-status <?= $done ? 't-submitted' : ($inProg ? 't-progress' : 't-pending') ?>">
              <?= $done ? '✓ Done' : ($inProg ? $t['live_count'].' rated' : 'Pending') ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- RIGHT: Announcements + Quick Actions -->
  <div style="display:flex;flex-direction:column;gap:18px;">

    <div class="card">
      <div class="card-head"><span class="card-title">Quick Actions</span></div>
      <div class="card-body" style="padding:12px 14px;display:flex;flex-direction:column;gap:8px;">
        <a href="self_assessment.php" class="btn btn-primary" style="justify-content:center;"><?= svgIcon('check-circle') ?> Self-Assessment</a>
        <a href="improvement.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('trending-up') ?> Improvement Plan</a>
        <a href="reports.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('file-text') ?> View Report</a>
        <a href="evidence.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('paperclip') ?> Evidence Files</a>
      </div>
    </div>

    <!-- Announcements -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Announcements</span>
        <a href="announcements.php" class="btn btn-ghost btn-sm">All →</a>
      </div>
      <div class="card-body" style="padding:10px 16px;">
        <?php if ($anns): ?>
        <?php foreach($anns as $a): ?>
        <div class="ann-item">
          <span class="pill pill-<?= e($a['category']) ?>" style="font-size:10.5px;"><?= ucfirst($a['category']) ?></span>
          <div class="ann-title"><?= e($a['title']) ?></div>
          <div class="ann-meta"><?= e($a['full_name']) ?> · <?= timeAgo($a['created_at']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p style="font-size:13px;color:var(--n-400);padding:12px 0;">No announcements.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>