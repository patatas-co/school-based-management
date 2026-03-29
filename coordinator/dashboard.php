<?php
// ============================================================
// coordinator/dashboard.php
// SBM Coordinator role — manages assessment cycle,
// views analytics, improvement plans, reports.
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sbm_coordinator', 'admin');
$db = getDB();

$uid      = $_SESSION['user_id'];
$schoolId = SCHOOL_ID;

$school = $db->prepare("SELECT * FROM schools WHERE school_id=?");
$school->execute([$schoolId]); $school = $school->fetch();

$syId    = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();
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

$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.target_role IN('all','sbm_coordinator','admin') ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

// Teacher submissions
$teacherList = [];
if ($cycle) {
    $tq = $db->prepare("
        SELECT u.user_id, u.full_name,
               ts.status sub_status, ts.submitted_at, ts.response_count,
               (SELECT COUNT(*) FROM teacher_responses tr WHERE tr.cycle_id=? AND tr.teacher_id=u.user_id) live_count,
               (SELECT COUNT(*) FROM teacher_indicator_assignments tia WHERE tia.teacher_id=u.user_id) assigned_count
        FROM users u
        LEFT JOIN teacher_submissions ts ON ts.teacher_id=u.user_id AND ts.cycle_id=?
        WHERE u.school_id=? AND u.role='teacher' AND u.status='active'
        ORDER BY ts.status DESC, u.full_name ASC
    ");
    $tq->execute([$cycle['cycle_id'],$cycle['cycle_id'],$schoolId]);
    $teacherList = $tq->fetchAll();
}
$submittedTeachers = count(array_filter($teacherList, fn($t) => $t['sub_status'] === 'submitted'));
$totalTeachers     = count($teacherList);

$pageTitle  = 'Coordinator Dashboard';
$activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>

<style>
.sh-hero { background:var(--white); border:1px solid var(--n-200); border-radius:var(--radius-lg); padding:24px 28px; margin-bottom:20px; display:flex; align-items:center; gap:28px; flex-wrap:wrap; box-shadow:var(--shadow-xs); }
.sh-hero-progress { flex-shrink:0; position:relative; width:100px; height:100px; }
.sh-hero-svg { width:100%; height:100%; transform:rotate(-90deg); }
.sh-hero-svg circle { fill:none; stroke-width:9; stroke-linecap:round; }
.sh-progress-track { stroke:var(--n-100); }
.sh-hero-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; }
.sh-hero-pct { font-family:var(--font-display); font-size:20px; font-weight:800; color:var(--n-900); line-height:1; }
.sh-hero-pct-label { font-size:10px; color:var(--n-400); font-weight:600; }
.sh-hero-info { flex:1; min-width:200px; }
.sh-hero-school { font-family:var(--font-display); font-size:18px; font-weight:800; color:var(--n-900); margin-bottom:4px; }
.sh-hero-sy { font-size:13px; color:var(--n-500); margin-bottom:14px; }
.sh-hero-actions { display:flex; gap:8px; flex-wrap:wrap; }
.overall-score-badge { display:flex; flex-direction:column; align-items:center; padding:20px 28px; border-radius:var(--radius-lg); border:2px solid; text-align:center; flex-shrink:0; }
.overall-score-num { font-family:var(--font-display); font-size:40px; font-weight:800; line-height:1; letter-spacing:-1px; }

.dim-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.dim-tile { background:var(--white); border:1px solid var(--n-200); border-radius:var(--radius); padding:14px; border-top:3px solid; box-shadow:var(--shadow-xs); transition:transform 150ms,box-shadow 150ms; }
.dim-tile:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); }
.dim-tile-num { font-size:10.5px; font-weight:700; color:var(--n-400); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
.dim-tile-name { font-size:12.5px; font-weight:700; color:var(--n-800); margin-bottom:10px; line-height:1.35; min-height:32px; }
.dim-tile-score { font-family:var(--font-display); font-size:22px; font-weight:800; line-height:1; margin-bottom:8px; }
.dim-tile-prog { height:5px; background:var(--n-100); border-radius:999px; overflow:hidden; margin-bottom:6px; }
.dim-tile-fill { height:100%; border-radius:999px; }
.dim-tile-mat { font-size:11px; font-weight:600; color:var(--n-500); }

.teacher-row { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; border:1px solid var(--n-200); background:var(--n-50); }
.teacher-row.submitted { background:#F0FDF4; border-color:#86EFAC; }
.teacher-avatar { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.teacher-name { flex:1; font-size:13px; font-weight:600; color:var(--n-800); }
.teacher-status { font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; flex-shrink:0; }
.t-submitted { background:#DCFCE7; color:#16A34A; }
.t-progress  { background:#DBEAFE; color:#2563EB; }
.t-pending   { background:var(--n-100); color:var(--n-500); }

.ann-item { padding:10px 0; border-bottom:1px solid var(--n-100); }
.ann-item:last-child { border-bottom:none; }

.sh-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.sh-stat { background:var(--white); border:1px solid var(--n-200); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); }
.sh-stat-val { font-family:var(--font-display); font-size:26px; font-weight:800; color:var(--n-900); line-height:1; margin-bottom:4px; }
.sh-stat-lbl { font-size:11.5px; color:var(--n-500); font-weight:500; }

/* Coordinator-specific: read-only indicator for admin-only actions */
.admin-only-note {
  display:inline-flex; align-items:center; gap:5px; font-size:11px;
  color:var(--n-400); background:var(--n-100); padding:3px 8px;
  border-radius:4px; border:1px solid var(--n-200);
}

@media(max-width:768px){
  .dim-grid { grid-template-columns:repeat(2,1fr); }
  .sh-stats  { grid-template-columns:repeat(2,1fr); }
}
</style>

<?php
$isLocked = $cycle && in_array($cycle['status'], ['submitted','validated']);
$hasScore = $cycle && $cycle['overall_score'];
$mat      = $hasScore ? sbmMaturityLevel(floatval($cycle['overall_score'])) : null;

// Fetch AI Recommendations
$recommendations = [];
if ($cycle) {
    try {
        $recStmt = $db->prepare("SELECT * FROM ml_recommendations WHERE cycle_id=? ORDER BY priority_score DESC LIMIT 4");
        $recStmt->execute([$cycle['cycle_id']]);
        $recommendations = $recStmt->fetchAll();
    } catch(Exception $e) {}
}
?>

<?php if ($cycle && $cycle['status'] === 'returned'): ?>
<div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:9px;background:#FEF3C7;border:1px solid #FDE68A;margin-bottom:16px;">
  <?= svgIcon('alert-circle') ?>
  <div style="font-size:13.5px;color:#92400E;">
    <strong>Assessment Returned for Revision</strong><br>
    <?php if($cycle['validator_remarks']): ?>
    Admin Remarks: <?= e($cycle['validator_remarks']) ?>
    <?php endif; ?>
    <br><a href="javascript:void(0)" style="color:#78350F;font-weight:700;">Please instruct the School Head to revise it →</a>
  </div>
</div>
<?php endif; ?>

<!-- HERO -->
<div class="sh-hero">
  <div class="sh-hero-progress">
    <?php
    $circumference = 2 * 3.14159 * 45;
    $offset        = $circumference - ($progress / 100) * $circumference;
    $strokeColor   = $progress >= 100 ? '#16A34A' : ($progress >= 50 ? '#2563EB' : '#D97706');
    ?>
    <svg class="sh-hero-svg" viewBox="0 0 100 100">
      <circle class="sh-progress-track" cx="50" cy="50" r="45"/>
      <circle class="sh-progress-track" cx="50" cy="50" r="45" stroke="<?= $strokeColor ?>" stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $offset ?>"/>
    </svg>
    <div class="sh-hero-center">
      <div class="sh-hero-pct"><?= $progress ?>%</div>
      <div class="sh-hero-pct-label">done</div>
    </div>
  </div>

  <div class="sh-hero-info">
    <div class="sh-hero-school">Dasmariñas Integrated High School</div>
    <div class="sh-hero-sy">
      School Year <?= e($syLabel) ?> &nbsp;·&nbsp;
      <?= $totalResponded ?>/<?= $totalIndicators ?> indicators rated &nbsp;·&nbsp;
      <?php if($cycle): ?>
      <span class="pill pill-<?= e($cycle['status']) ?>"><?= ucfirst(str_replace('_',' ',$cycle['status'])) ?></span>
      <?php else: ?>
      <span class="pill pill-draft">Not Started</span>
      <?php endif; ?>
    </div>
    <div class="sh-hero-actions">
      <?php if($cycle && $cycle['status'] === 'in_progress'): ?>
      <a href="self_assessment.php" class="btn btn-primary">
        <?= svgIcon('check-circle') ?>
        Continue Assessment
      </a>
      <?php endif; ?>
      <a href="improvement.php" class="btn btn-secondary"><?= svgIcon('trending-up') ?> Improvement Plan</a>
      <a href="reports.php" class="btn btn-secondary"><?= svgIcon('file-text') ?> Reports</a>
    </div>
  </div>

  <?php if($hasScore): ?>
  <div class="overall-score-badge" style="border-color:<?= $mat['color'] ?>;background:<?= $mat['bg'] ?>;">
    <div class="overall-score-num" style="color:<?= $mat['color'] ?>;"><?= number_format($cycle['overall_score'],1) ?>%</div>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-top:6px;color:<?= $mat['color'] ?>;">Overall Score</div>
    <div style="font-size:12px;font-weight:600;margin-top:4px;opacity:.75;color:<?= $mat['color'] ?>;"><?= e($cycle['maturity_level']) ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- STAT CARDS -->
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
    <div class="sh-stat-val" style="color:<?= $submittedTeachers===$totalTeachers&&$totalTeachers>0?'var(--brand-700)':'var(--amber)' ?>;"><?= $submittedTeachers ?>/<?= $totalTeachers ?></div>
    <div class="sh-stat-lbl">Teachers Submitted</div>
  </div>
  <div class="sh-stat">
    <div class="sh-stat-val"><?= count($dimScores) ?></div>
    <div class="sh-stat-lbl">Dimensions Scored</div>
  </div>
</div>

<!-- MAIN GRID -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:18px;margin-bottom:20px;" class="db-layout-main">

  <div style="display:flex;flex-direction:column;gap:18px;">

    <!-- Dimension tiles & Visual Analytics -->
    <?php if($dimScores): ?>
    <div class="card">
      <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
        <span class="card-title">Dimension Performance & Analytics</span>
        <a href="dimensions.php" class="btn btn-ghost btn-sm">View details →</a>
      </div>
      <div class="card-body" style="padding:16px;">
        <div class="dim-grid" style="margin-bottom:20px;">
          <?php
          $dimCompletionData = [];
          $chartLabels = [];
          $chartData = [];
          $chartColors = [];
          
          if ($cycle) {
              $dcStmt = $db->prepare("SELECT i.dimension_id, COUNT(*) cnt FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id WHERE r.cycle_id=? GROUP BY i.dimension_id");
              $dcStmt->execute([$cycle['cycle_id']]);
              foreach ($dcStmt->fetchAll() as $dc) $dimCompletionData[$dc['dimension_id']] = $dc['cnt'];
          }
          ?>
          <?php foreach($dimScores as $ds):
            $pct  = floatval($ds['percentage']);
            $mat2 = sbmMaturityLevel($pct);
            $done = $dimCompletionData[$ds['dimension_id']] ?? 0;
            
            // For chart
            $chartLabels[] = "Dim " . $ds['dimension_no'];
            $chartData[] = $pct;
            $chartColors[] = $ds['color_hex'];
          ?>
          <a href="dimensions.php" class="dim-tile" style="border-top-color:<?= e($ds['color_hex']) ?>;text-decoration:none;">
            <div class="dim-tile-num">Dimension <?= $ds['dimension_no'] ?></div>
            <div class="dim-tile-name" style="color:<?= e($ds['color_hex']) ?>;"><?= e($ds['dimension_name']) ?></div>
            <div class="dim-tile-score" style="color:<?= $mat2['color'] ?>;"><?= $pct > 0 ? $pct.'%' : '—' ?></div>
            <div class="dim-tile-prog"><div class="dim-tile-fill" style="width:<?= min(100,$pct) ?>%;background:<?= e($ds['color_hex']) ?>;"></div></div>
            <div class="dim-tile-mat"><?= $mat2['label'] ?> · <?= $done ?>/<?= $ds['indicator_count'] ?> rated</div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
        <span class="card-title">Dimension Performance & Analytics</span>
      </div>
      <div class="card-body" style="text-align:center;padding:40px;">
        <h3 style="font-size:16px;font-weight:700;color:var(--n-700);margin-bottom:8px;">No dimension data yet</h3>
        <p style="font-size:13.5px;color:var(--n-400);margin-bottom:16px;">Wait for the School Head to start the self-assessment to see scores across all 6 SBM dimensions.</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- CHART CONTAINER ALONE -->
    <div class="card" style="background:var(--n-50);border-radius:12px;padding:16px;border:1px solid var(--n-200);">
        <div style="font-size:13px;font-weight:700;color:var(--n-800);margin-bottom:10px;">Visual Analytics: Dimension Comparison</div>
        <?php if($dimScores): ?>
        <div style="height:250px;width:100%;position:relative;">
            <canvas id="dimChart"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('dimChart');
                if(ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode($chartLabels) ?>,
                            datasets: [{
                                label: 'Score Percentage',
                                data: <?= json_encode($chartData) ?>,
                                backgroundColor: <?= json_encode($chartColors) ?>,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: { beginAtZero: true, max: 100 }
                            }
                        }
                    });
                }
            });
        </script>
        <?php else: ?>
        <div style="height:250px;width:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;border:2px dashed var(--n-200);border-radius:8px;background:var(--white);opacity:0.7;">
            <?= svgIcon('bar-chart-2', 32, 'var(--n-300)') ?>
            <div style="margin-top:10px;font-size:13px;font-weight:600;color:var(--n-500);">Chart data unavailable</div>
            <div style="font-size:12px;color:var(--n-400);">Scores will be visualized here once evaluations begin.</div>
        </div>
        <?php endif; ?>

    <!-- AI Recommendations block -->
    <div class="card" style="border:1.5px solid #E0E7FF;margin-top:18px;">
        <div class="card-head" style="background:#EEF2FF;border-bottom:1px solid #E0E7FF;display:flex;align-items:center;">
            <span class="card-title" style="color:#3730A3;">✨ AI Recommendations & ML Insights</span>
        </div>
        <div class="card-body" style="padding:16px;">
            <?php if(!empty($recommendations)): ?>
            <p style="font-size:12.5px;color:var(--n-500);margin-bottom:16px;">
                Based on machine learning analysis of SBM patterns and current cycle performance, the following priority areas are suggested for your Improvement Plan.
            </p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <?php foreach($recommendations as $rec): ?>
                <div style="padding:12px;border:1px solid var(--n-200);border-radius:8px;background:var(--white);box-shadow:var(--shadow-xs);">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <span class="pill pill-draft" style="background:#DBEAFE;color:#1E40AF;border-color:#BFDBFE;">Dim <?= e($rec['dimension_id']) ?></span>
                        <strong style="font-size:13.5px;color:var(--n-800);"><?= e($rec['indicator_code'] ?? 'Priority Focus') ?></strong>
                        <?php if($rec['priority_score'] > 0.7): ?>
                        <span style="margin-left:auto;font-size:11px;color:#DC2626;font-weight:700;display:flex;align-items:center;gap:3px;">
                            <?= svgIcon('trending-down', 13) ?> High Priority
                        </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:13px;color:var(--n-600);line-height:1.4;">
                        <?= e($rec['recommendation_text']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:16px;text-align:center;">
                <a href="improvement.php" class="btn btn-primary btn-sm">Add to Improvement Plan</a>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:30px 10px;opacity:0.8;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:40px;height:40px;margin:0 auto 12px;opacity:0.6;">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <div style="font-size:14px;font-weight:700;color:var(--n-700);">Gathering Data Model...</div>
                <div style="font-size:13px;color:var(--n-500);max-width:300px;margin:8px auto 0;">
                    Complete initial scoring data to unlock predictive Priority Areas and intelligent machine learning recommendations.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Teacher Submissions -->
    <?php if($teacherList): ?>
    <div class="card">
      <div class="card-head">
        <span class="card-title">Teacher Submissions</span>
        <span style="font-size:13px;font-weight:700;color:<?= $submittedTeachers===$totalTeachers?'var(--brand-700)':'var(--amber)' ?>;">
          <?= $submittedTeachers ?>/<?= $totalTeachers ?> submitted
        </span>
      </div>
      <div class="card-body" style="padding:12px 16px;">
        <div style="display:flex;flex-direction:column;gap:6px;">
          <?php foreach($teacherList as $t):
            $done   = $t['sub_status']==='submitted';
            $inProg = !$done && $t['live_count'] > 0;
          ?>
          <div class="teacher-row <?= $done?'submitted':'' ?>">
            <div class="teacher-avatar" style="background:<?= $done?'#DCFCE7':($inProg?'#DBEAFE':'var(--n-100)') ?>;color:<?= $done?'#16A34A':($inProg?'#2563EB':'var(--n-500)') ?>;">
              <?= strtoupper(substr($t['full_name'],0,1)) ?>
            </div>
            <div style="flex:1;min-width:0;">
              <div class="teacher-name"><?= e($t['full_name']) ?></div>
              <?php if(!$done&&$inProg): 
                  $assignCountCap = $t['assigned_count'] > 0 ? $t['assigned_count'] : count(TEACHER_INDICATOR_CODES);
              ?>
              <div style="height:4px;background:var(--n-200);border-radius:999px;margin-top:4px;width:120px;overflow:hidden;">
                <div style="height:100%;width:<?= min(100, round(($t['live_count']/$assignCountCap)*100)) ?>%;background:#2563EB;border-radius:999px;"></div>
              </div>
              <?php endif; ?>
            </div>
            <span class="teacher-status <?= $done?'t-submitted':($inProg?'t-progress':'t-pending') ?>">
              <?= $done?'✓ Done':($inProg?$t['live_count'].' rated':'Pending') ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- RIGHT: Quick Actions + Announcements -->
  <div style="display:flex;flex-direction:column;gap:18px;">

    <div class="card">
      <div class="card-head"><span class="card-title">Quick Actions</span></div>
      <div class="card-body" style="padding:12px 14px;display:flex;flex-direction:column;gap:8px;">
        <a href="self_assessment.php" class="btn btn-primary" style="justify-content:center;"><?= svgIcon('check-circle') ?> Self-Assessment</a>
        <a href="teacher_status.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('users') ?> Teacher Status</a>
        <a href="improvement.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('trending-up') ?> Improvement Plan</a>
        <a href="reports.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('file-text') ?> Reports</a>
        <a href="analytics.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('bar-chart-2') ?> Analytics</a>
        <a href="evidence.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('paperclip') ?> Evidence Files</a>
      </div>
    </div>

    <?php if($cycle && $cycle['status'] === 'validated'): ?>
    <div class="card" style="border:1.5px solid var(--brand-500);">
      <div class="card-body" style="padding:14px 16px;background:var(--brand-50);">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
          <?= svgIcon('check-circle') ?>
          <strong style="color:var(--brand-700);">Assessment Validated</strong>
        </div>
        <div style="font-size:12.5px;color:var(--brand-700);">This cycle has been validated by the Administrator.</div>
        <?php if($cycle['validator_remarks']): ?>
        <div style="font-size:12px;color:var(--n-600);margin-top:6px;font-style:italic;">"<?= e($cycle['validator_remarks']) ?>"</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head"><span class="card-title">Announcements</span><a href="announcements.php" class="btn btn-ghost btn-sm">All →</a></div>
      <div class="card-body" style="padding:10px 16px;">
        <?php if($anns): foreach($anns as $a): ?>
        <div class="ann-item">
          <span class="pill pill-<?= e($a['category']) ?>" style="font-size:10.5px;"><?= ucfirst($a['category']) ?></span>
          <div style="font-size:13px;font-weight:600;color:var(--n-900);margin:4px 0 2px;"><?= e($a['title']) ?></div>
          <div style="font-size:11.5px;color:var(--n-400);"><?= e($a['full_name']) ?> · <?= timeAgo($a['created_at']) ?></div>
        </div>
        <?php endforeach; else: ?>
        <p style="font-size:13px;color:var(--n-400);">No announcements.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>