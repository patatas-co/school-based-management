<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head','admin');
$db = getDB();

$uid = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'] ?? 0;

$school = $schoolId ? $db->prepare("SELECT * FROM schools WHERE school_id=?") : null;
if ($school) { $school->execute([$schoolId]); $school = $school->fetch(); }

$syId = $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

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

$totalResponded = $cycle ? $db->prepare("SELECT COUNT(*) FROM sbm_responses WHERE cycle_id=?")->execute([$cycle['cycle_id']]) : 0;
if ($cycle) {
    $t = $db->prepare("SELECT COUNT(*) FROM sbm_responses WHERE cycle_id=?");
    $t->execute([$cycle['cycle_id']]); $totalResponded = $t->fetchColumn();
}
$totalIndicators = 42;
$progress = $totalIndicators > 0 ? round(($totalResponded/$totalIndicators)*100) : 0;

$anns = $db->query("SELECT a.*,u.full_name FROM announcements a JOIN users u ON a.posted_by=u.user_id WHERE a.target_role IN('all','school_head') ORDER BY a.created_at DESC LIMIT 4")->fetchAll();

$plans = $cycle ? $db->prepare("SELECT ip.*,d.dimension_name FROM improvement_plans ip JOIN sbm_dimensions d ON ip.dimension_id=d.dimension_id WHERE ip.cycle_id=? ORDER BY ip.priority_level,ip.created_at DESC LIMIT 5") : null;
if ($plans) { $plans->execute([$cycle['cycle_id']]); $plans = $plans->fetchAll(); }

if (!function_exists('sbmMaturityLevel')) {
    function sbmMaturityLevel(float $pct): array {
        if ($pct >= 90) return ['label'=>'Advanced',   'color'=>'#16A34A','bg'=>'#DCFCE7'];
        if ($pct >= 75) return ['label'=>'Proficient', 'color'=>'#2563EB','bg'=>'#DBEAFE'];
        if ($pct >= 50) return ['label'=>'Developing', 'color'=>'#D97706','bg'=>'#FEF3C7'];
        return                 ['label'=>'Beginning',  'color'=>'#DC2626','bg'=>'#FEE2E2'];
    }
}

$pageTitle = 'Dashboard'; $activePage = 'dashboard.php';

$pageTitle = 'Dashboard'; $activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text">
    <h2>Welcome, <?= e(explode(' ', trim($school['school_head_name'] ?? $_SESSION['full_name']))[0]) ?></h2>
    <p><?= e($school['school_name'] ?? 'No school assigned') ?> &nbsp;·&nbsp; <?= $syId ? ($db->query("SELECT label FROM school_years WHERE sy_id=$syId")->fetchColumn()) : '' ?></p>
  </div>
  <div class="page-head-actions">
    <?php if(!$cycle || $cycle['status']==='draft'): ?>
    <a href="self_assessment.php" class="btn btn-primary"><?= svgIcon('check-circle') ?> Start/Continue Assessment</a>
    <?php elseif($cycle['status']==='in_progress'): ?>
    <a href="self_assessment.php" class="btn btn-primary"><?= svgIcon('check-circle') ?> Continue Assessment</a>
    <?php elseif($cycle['status']==='returned'): ?>
    <div class="alert alert-danger" style="margin-bottom:0;"><?= svgIcon('alert-circle') ?> Assessment returned for revision. <a href="self_assessment.php" style="font-weight:700;">Revise now</a></div>
    <?php else: ?>
    <span class="pill pill-<?= e($cycle['status']) ?>" style="font-size:13px;padding:6px 14px;"><?= ucfirst(str_replace('_',' ',$cycle['status'])) ?></span>
    <?php endif; ?>
  </div>
</div>

<?php if($cycle && $cycle['validator_remarks'] && $cycle['status']==='returned'): ?>
<div class="alert alert-warning mb5" style="margin-bottom:18px;"><?= svgIcon('alert-circle') ?> <div><strong>SDO Remarks:</strong> <?= e($cycle['validator_remarks']) ?></div></div>
<?php endif; ?>

<!-- Progress & Stats -->
<div class="stats">
  <div class="stat"><div class="stat-ic <?= $progress>=100?'green':'blue' ?>"><?= svgIcon('check-circle') ?></div><div class="stat-data"><div class="stat-val"><?= $progress ?>%</div><div class="stat-lbl">Assessment Progress</div><div class="stat-sub"><?= $totalResponded ?>/42 indicators rated</div></div></div>
  <div class="stat"><div class="stat-ic <?= !$cycle ? 'dark' : (['draft'=>'dark','in_progress'=>'blue','submitted'=>'gold','validated'=>'green','returned'=>'red'][$cycle['status']] ?: 'dark') ?>"><?= svgIcon('bar-chart-2') ?></div><div class="stat-data"><div class="stat-val"><?= $cycle ? ($cycle['overall_score'] ? $cycle['overall_score'].'%' : '—') : '—' ?></div><div class="stat-lbl">Overall SBM Score</div></div></div>
  <div class="stat"><div class="stat-ic green"><?= svgIcon('layers') ?></div><div class="stat-data"><div class="stat-val"><?= count($dimScores) ?></div><div class="stat-lbl">Dimensions Scored</div></div></div>
  <div class="stat"><div class="stat-ic gold"><?= svgIcon('trending-up') ?></div><div class="stat-data"><div class="stat-val" style="font-size:14px;line-height:1.3;"><?= $cycle&&$cycle['maturity_level'] ? sbmMaturityBadge($cycle['maturity_level']) : '—' ?></div><div class="stat-lbl">Maturity Level</div></div></div>
</div>

<!-- ── TEACHER SUBMISSION STATUS CARD ── -->
<?php
$teachers = $db->prepare("
    SELECT u.user_id, u.full_name,
        ts.status        sub_status,
        ts.submitted_at,
        ts.response_count,
        (SELECT COUNT(*) FROM teacher_responses tr 
         WHERE tr.cycle_id  = ? 
           AND tr.teacher_id = u.user_id) live_count
    FROM users u
    LEFT JOIN teacher_submissions ts 
        ON ts.teacher_id = u.user_id 
       AND ts.cycle_id   = ?
    WHERE u.school_id = ?
      AND u.role      = 'teacher'
      AND u.status    = 'active'
    ORDER BY ts.status DESC, u.full_name ASC
");
$cycleIdForTeachers = $cycle['cycle_id'] ?? 0;
$teachers->execute([
    $cycleIdForTeachers, 
    $cycleIdForTeachers, 
    $schoolId
]);
$teacherList = $teachers->fetchAll();

$submittedCount = count(array_filter(
    $teacherList, fn($t) => $t['sub_status'] === 'submitted'
));
$totalTeachers  = count($teacherList);
?>

<?php if ($teacherList): ?>
<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <span class="card-title">
            Teacher Submissions
        </span>
        <span style="font-size:13px;font-weight:700;
                     color:<?= $submittedCount===$totalTeachers
                               ?'var(--g600)':'var(--gold)' ?>;">
            <?= $submittedCount ?>/<?= $totalTeachers ?> Submitted
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php foreach ($teacherList as $t):
            $done    = $t['sub_status'] === 'submitted';
            $inProg  = !$done && $t['live_count'] > 0;
            $notYet  = !$done && $t['live_count'] === 0;
            $pct     = count(TEACHER_INDICATOR_CODES) > 0
                ? round(($t['live_count'] / 
                  count(TEACHER_INDICATOR_CODES)) * 100)
                : 0;
        ?>
        <div style="display:flex;align-items:center;gap:12px;
                    padding:11px 18px;
                    border-bottom:1px solid var(--n100);">

            <!-- Avatar -->
            <div style="width:34px;height:34px;border-radius:8px;
                        background:<?= $done?'var(--g100)':
                            ($inProg?'var(--blueb)':'var(--n100)') ?>;
                        color:<?= $done?'var(--g700)':
                            ($inProg?'var(--blue)':'var(--n400)') ?>;
                        font-size:12px;font-weight:700;
                        display:flex;align-items:center;
                        justify-content:center;flex-shrink:0;">
                <?= strtoupper(substr($t['full_name'], 0, 1)) ?>
            </div>

            <!-- Name + progress -->
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;
                            color:var(--n900);">
                    <?= e($t['full_name']) ?>
                </div>
                <?php if (!$done): ?>
                <div style="margin-top:4px;">
                    <div style="height:5px;background:var(--n100);
                                border-radius:999px;overflow:hidden;
                                width:180px;">
                        <div style="height:100%;
                                    background:var(--blue);
                                    border-radius:999px;
                                    width:<?= $pct ?>%;"></div>
                    </div>
                    <div style="font-size:11px;color:var(--n400);
                                margin-top:2px;">
                        <?= $t['live_count'] ?>/<?= count(TEACHER_INDICATOR_CODES) ?> 
                        indicators rated
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Status badge -->
            <div>
                <?php if ($done): ?>
                <span style="display:inline-flex;align-items:center;
                             gap:5px;padding:4px 12px;
                             border-radius:999px;font-size:12px;
                             font-weight:700;background:var(--g100);
                             color:var(--g700);
                             border:1px solid var(--g200);">
                    ✓ Submitted
                    <span style="font-size:11px;font-weight:400;
                                 opacity:.7;">
                        <?= date('M d', 
                            strtotime($t['submitted_at'])) ?>
                    </span>
                </span>
                <?php elseif ($inProg): ?>
                <span style="display:inline-flex;padding:4px 12px;
                             border-radius:999px;font-size:12px;
                             font-weight:700;background:var(--blueb);
                             color:var(--blue);
                             border:1px solid #BFDBFE;">
                    In Progress
                </span>
                <?php else: ?>
                <span style="display:inline-flex;padding:4px 12px;
                             border-radius:999px;font-size:12px;
                             font-weight:700;background:var(--n100);
                             color:var(--n500);">
                    Not Started
                </span>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Progress bar -->
<div class="card mb5" style="margin-bottom:18px;">
  <div class="card-body" style="padding:16px 20px;">
    <div class="flex-cb" style="margin-bottom:8px;">
      <span style="font-size:13.5px;font-weight:600;color:var(--n800);">Self-Assessment Progress</span>
      <span style="font-size:13px;font-weight:700;color:var(--g700);"><?= $totalResponded ?>/42 Indicators</span>
    </div>
    <div class="prog" style="height:12px;">
      <div class="prog-fill green" style="width:<?= $progress ?>%;"></div>
    </div>
    <?php if($progress < 100): ?>
    <p style="font-size:12px;color:var(--n400);margin-top:6px;"><?= 42-$totalResponded ?> indicators remaining. <a href="self_assessment.php" style="color:var(--g600);font-weight:600;">Continue assessment →</a></p>
    <?php else: ?>
    <p style="font-size:12px;color:var(--g700);font-weight:600;margin-top:6px;">✓ All indicators rated. <?= $cycle && $cycle['status']!=='submitted'&&$cycle['status']!=='validated' ? '<a href="self_assessment.php" style="color:var(--g600);">Review and submit →</a>' : '' ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="grid2-3" style="gap:18px;margin-bottom:20px;">
  <!-- Dimension scores -->
  <div class="card">
    <div class="card-head"><span class="card-title">Dimension Scores</span><a href="self_assessment.php" class="btn btn-secondary btn-sm">Update</a></div>
    <div class="card-body">
      <?php foreach($dimScores as $ds): ?>
      <?php $mat = sbmMaturityLevel(floatval($ds['percentage'])); ?>
      <div style="margin-bottom:14px;">
        <div class="flex-cb" style="margin-bottom:5px;">
          <span style="font-size:13px;font-weight:600;color:var(--n800);">Dim <?= $ds['dimension_no'] ?>: <?= e($ds['dimension_name']) ?></span>
          <span style="font-size:13px;font-weight:700;color:<?= $mat['color'] ?>;"><?= $ds['percentage'] ?>%</span>
        </div>
        <div class="prog"><div class="prog-fill" style="width:<?= $ds['percentage'] ?>%;background:<?= $ds['color_hex'] ?>;"></div></div>
        <div style="font-size:11px;color:var(--n400);margin-top:3px;"><?= $mat['label'] ?> · <?= $ds['raw_score'] ?>/<?= $ds['max_score'] ?> pts</div>
      </div>
      <?php endforeach; ?>
      <?php if(!$dimScores): ?><p style="color:var(--n400);font-size:13px;text-align:center;padding:20px 0;">Start your assessment to see scores here.</p><?php endif; ?>
    </div>
  </div>

  <!-- Announcements -->
  <div>
    <div class="card mb4" style="margin-bottom:14px;">
      <div class="card-head"><span class="card-title">Announcements</span></div>
      <div class="card-body" style="padding:10px 14px;">
        <?php foreach($anns as $a): ?>
        <div style="padding:8px 0;border-bottom:1px solid var(--n100);">
          <div class="flex-c" style="gap:6px;margin-bottom:3px;">
            <span class="pill pill-<?= e($a['category']) ?>" style="font-size:10px;"><?= ucfirst($a['category']) ?></span>
          </div>
          <div style="font-size:13px;font-weight:600;color:var(--n800);"><?= e($a['title']) ?></div>
          <div style="font-size:11.5px;color:var(--n400);"><?= timeAgo($a['created_at']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if(!$anns): ?><p style="font-size:13px;color:var(--n400);padding:12px 0;">No announcements.</p><?php endif; ?>
      </div>
    </div>

    <!-- Quick links -->
    <div class="card">
      <div class="card-head"><span class="card-title">Quick Actions</span></div>
      <div class="card-body" style="padding:12px 14px;display:flex;flex-direction:column;gap:8px;">
        <a href="self_assessment.php" class="btn btn-primary" style="justify-content:center;"><?= svgIcon('check-circle') ?> Self-Assessment</a>
        <a href="improvement.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('trending-up') ?> Improvement Plan</a>
        <a href="reports.php" class="btn btn-secondary" style="justify-content:center;"><?= svgIcon('file-text') ?> View Reports</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
