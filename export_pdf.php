<?php
// ============================================================
// export_pdf.php — SBM Annex A PDF Export using mPDF
// Place this file in your project ROOT (same level as login.php)
// ============================================================
require_once __DIR__ . '/vendor/autoload.php';  // mPDF via Composer
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Allow school heads, admins, SDO, and RO to export
requireLogin();

$db = getDB();

// ── Get parameters ────────────────────────────────────────────
$cycleId  = (int)($_GET['cycle_id']  ?? 0);
$schoolId = (int)($_GET['school_id'] ?? 0);
$syId     = (int)($_GET['sy_id']     ?? 0);
$type     = $_GET['type'] ?? 'annex_a'; // annex_a | dimension | improvement

// ── Role-based access guard ───────────────────────────────────
$role = $_SESSION['role'];
if ($role === 'school_head' || $role === 'teacher') {
    // School head/teacher can only export their own school
    $mySchoolId = $_SESSION['school_id'] ?? 0;
    if ($schoolId && $schoolId !== $mySchoolId) {
        http_response_code(403);
        die('Access denied.');
    }
    if (!$schoolId) $schoolId = $mySchoolId;
}

// ── Resolve cycle ─────────────────────────────────────────────
if ($cycleId) {
    $cycleStmt = $db->prepare("
        SELECT c.*, s.school_name, s.school_id_deped, s.classification,
               s.school_head_name, s.address, s.total_enrollment, s.total_teachers,
               sy.label sy_label, sy.sy_id,
               d.division_name,
               u.full_name validator_name
        FROM sbm_cycles c
        JOIN schools s   ON c.school_id = s.school_id
        JOIN school_years sy ON c.sy_id = sy.sy_id
        LEFT JOIN divisions d  ON s.division_id = d.division_id
        LEFT JOIN users u ON c.validated_by = u.user_id
        WHERE c.cycle_id = ?
    ");
    $cycleStmt->execute([$cycleId]);
    $cycle = $cycleStmt->fetch();
} elseif ($schoolId && $syId) {
    $cycleStmt = $db->prepare("
        SELECT c.*, s.school_name, s.school_id_deped, s.classification,
               s.school_head_name, s.address, s.total_enrollment, s.total_teachers,
               sy.label sy_label, sy.sy_id,
               d.division_name,
               u.full_name validator_name
        FROM sbm_cycles c
        JOIN schools s   ON c.school_id = s.school_id
        JOIN school_years sy ON c.sy_id = sy.sy_id
        LEFT JOIN divisions d  ON s.division_id = d.division_id
        LEFT JOIN users u ON c.validated_by = u.user_id
        WHERE c.school_id = ? AND c.sy_id = ?
        LIMIT 1
    ");
    $cycleStmt->execute([$schoolId, $syId]);
    $cycle = $cycleStmt->fetch();
} else {
    http_response_code(400);
    die('Missing cycle_id or school_id/sy_id parameters.');
}

if (!$cycle) {
    http_response_code(404);
    die('Assessment cycle not found.');
}

$cycleId  = $cycle['cycle_id'];
$schoolId = $cycle['school_id'];

// ── Load dimension scores ─────────────────────────────────────
$dimScoresStmt = $db->prepare("
    SELECT ds.*, d.dimension_no, d.dimension_name, d.color_hex, d.indicator_count
    FROM sbm_dimension_scores ds
    JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
    WHERE ds.cycle_id = ?
    ORDER BY d.dimension_no
");
$dimScoresStmt->execute([$cycleId]);
$dimScores = $dimScoresStmt->fetchAll();

// ── Load all responses ────────────────────────────────────────
$respStmt = $db->prepare("
    SELECT r.*, i.indicator_code, i.indicator_text, i.mov_guide, i.sort_order,
           d.dimension_no, d.dimension_name, d.color_hex
    FROM sbm_responses r
    JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    WHERE r.cycle_id = ?
    ORDER BY d.dimension_no, i.sort_order
");
$respStmt->execute([$cycleId]);
$responses = $respStmt->fetchAll();

// Group by dimension
$grouped = [];
foreach ($responses as $r) {
    $grouped[$r['dimension_no']][] = $r;
}

// ── Load improvement plans (for improvement type) ─────────────
$plans = [];
if ($type === 'improvement') {
    $planStmt = $db->prepare("
        SELECT ip.*, d.dimension_name, d.color_hex,
               i.indicator_code, i.indicator_text
        FROM improvement_plans ip
        JOIN sbm_dimensions d ON ip.dimension_id = d.dimension_id
        LEFT JOIN sbm_indicators i ON ip.indicator_id = i.indicator_id
        WHERE ip.cycle_id = ?
        ORDER BY FIELD(ip.priority_level,'High','Medium','Low'), ip.created_at
    ");
    $planStmt->execute([$cycleId]);
    $plans = $planStmt->fetchAll();
}

// ── Rating helpers ────────────────────────────────────────────
$ratingLabels = [
    1 => 'Not yet Manifested',
    2 => 'Rarely Manifested',
    3 => 'Frequently Manifested',
    4 => 'Always manifested',
];
$ratingColors = [
    1 => '#DC2626',
    2 => '#D97706',
    3 => '#2563EB',
    4 => '#16A34A',
];
$ratingBgs = [
    1 => '#FEE2E2',
    2 => '#FEF3C7',
    3 => '#DBEAFE',
    4 => '#DCFCE7',
];

function getMaturityLabel(float $pct): array {
    if ($pct >= 76) return ['label' => 'Advanced',   'color' => '#16A34A', 'bg' => '#DCFCE7'];
    if ($pct >= 51) return ['label' => 'Maturing',   'color' => '#2563EB', 'bg' => '#DBEAFE'];
    if ($pct >= 26) return ['label' => 'Developing', 'color' => '#D97706', 'bg' => '#FEF3C7'];
    return              ['label' => 'Beginning',  'color' => '#DC2626', 'bg' => '#FEE2E2'];
}

$overallMat = getMaturityLabel((float)($cycle['overall_score'] ?? 0));

// ── Build HTML content ────────────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  /* ── Base ── */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9pt;
    color: #1F2937;
    line-height: 1.4;
  }

  /* ── Page header ── */
  .page-header {
    text-align: center;
    border-bottom: 2px solid #166534;
    padding-bottom: 10px;
    margin-bottom: 14px;
  }
  .deped-stripe {
    height: 4px;
    background: linear-gradient(90deg, #166534 0%, #22C55E 40%, #FFD700 70%, #CE1126 100%);
    margin-bottom: 10px;
  }
  .page-header h1 {
    font-size: 12pt;
    font-weight: bold;
    color: #166534;
    margin-bottom: 2px;
  }
  .page-header h2 {
    font-size: 10pt;
    font-weight: bold;
    color: #1F2937;
    margin-bottom: 2px;
  }
  .page-header p { font-size: 8.5pt; color: #6B7280; }

  /* ── School info table ── */
  .school-info {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
  }
  .school-info td {
    padding: 6px 10px;
    font-size: 8.5pt;
    border: 1px solid #E5E7EB;
    vertical-align: top;
  }
  .school-info .lbl {
    font-weight: bold;
    color: #6B7280;
    font-size: 7.5pt;
    text-transform: uppercase;
    width: 110px;
  }

  /* ── Score summary ── */
  .score-summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
  }
  .score-summary td {
    border: 1px solid #D1D5DB;
    padding: 7px 10px;
    font-size: 8.5pt;
    text-align: center;
  }
  .score-summary .score-val {
    font-size: 16pt;
    font-weight: bold;
    color: #166534;
  }
  .score-summary .score-lbl {
    font-size: 7.5pt;
    color: #6B7280;
    text-transform: uppercase;
  }
  .maturity-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 9pt;
  }

  /* ── Section heading ── */
  .section-heading {
    font-size: 10pt;
    font-weight: bold;
    color: #166534;
    padding: 6px 0;
    border-bottom: 1px solid #D1FAE5;
    margin-bottom: 8px;
    margin-top: 14px;
  }

  /* ── Dimension header ── */
  .dim-header {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
  }
  .dim-header td {
    padding: 6px 10px;
    font-weight: bold;
    font-size: 9pt;
    color: #fff;
  }
  .dim-score-row {
    background: #F9FAFB;
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
  }
  .dim-score-row td {
    padding: 5px 10px;
    font-size: 8pt;
    border-bottom: 1px solid #E5E7EB;
  }

  /* ── Indicator table ── */
  .ind-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 8pt;
  }
  .ind-table th {
    background: #F3F4F6;
    padding: 5px 8px;
    text-align: left;
    font-size: 7.5pt;
    font-weight: bold;
    color: #4B5563;
    border: 1px solid #D1D5DB;
    text-transform: uppercase;
  }
  .ind-table td {
    padding: 5px 8px;
    border: 1px solid #E5E7EB;
    vertical-align: top;
  }
  .ind-table tr:nth-child(even) td { background: #F9FAFB; }
  .ind-code {
    font-family: monospace;
    font-size: 7.5pt;
    font-weight: bold;
    color: #6B7280;
  }
  .rating-pill {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 7.5pt;
    font-weight: bold;
    white-space: nowrap;
  }

  /* ── Progress bar ── */
  .prog-outer {
    background: #E5E7EB;
    border-radius: 4px;
    height: 8px;
    width: 100%;
  }
  .prog-inner {
    height: 8px;
    border-radius: 4px;
  }

  /* ── Dimension summary table ── */
  .dim-summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
  }
  .dim-summary th {
    background: #166534;
    color: #fff;
    padding: 6px 10px;
    font-size: 8pt;
    text-align: left;
    border: 1px solid #15803D;
  }
  .dim-summary td {
    padding: 6px 10px;
    font-size: 8.5pt;
    border: 1px solid #E5E7EB;
    vertical-align: middle;
  }
  .dim-summary tr:nth-child(even) td { background: #F9FAFB; }

  /* ── Improvement plan ── */
  .plan-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 8pt;
  }
  .plan-table th {
    background: #1E293B;
    color: #fff;
    padding: 5px 8px;
    font-size: 7.5pt;
    text-align: left;
    border: 1px solid #374151;
  }
  .plan-table td {
    padding: 5px 8px;
    border: 1px solid #E5E7EB;
    vertical-align: top;
  }
  .plan-table tr:nth-child(even) td { background: #F9FAFB; }
  .priority-high   { color: #DC2626; font-weight: bold; }
  .priority-medium { color: #D97706; font-weight: bold; }
  .priority-low    { color: #2563EB; font-weight: bold; }

  /* ── Footer ── */
  .report-footer {
    margin-top: 20px;
    padding-top: 8px;
    border-top: 1px solid #D1D5DB;
    font-size: 7pt;
    color: #9CA3AF;
    text-align: center;
  }
  .signature-block {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
  }
  .signature-block td {
    width: 33%;
    text-align: center;
    padding: 0 10px;
    vertical-align: bottom;
  }
  .signature-line {
    border-top: 1px solid #374151;
    margin-bottom: 3px;
    margin-top: 30px;
  }
  .signature-name  { font-weight: bold; font-size: 8.5pt; }
  .signature-title { font-size: 7.5pt; color: #6B7280; }

  /* ── Page break ── */
  .pagebreak { page-break-after: always; }
</style>
</head>
<body>

<!-- DepEd stripe -->
<div class="deped-stripe"></div>

<!-- Page header -->
<div class="page-header">
  <p style="font-size:8pt;color:#6B7280;margin-bottom:4px;">
    Republic of the Philippines · Department of Education · Region IV-A (CALABARZON)
  </p>
  <?php if($type === 'improvement'): ?>
  <h1>School Improvement Plan (SIP)</h1>
  <h2>Based on SBM Self-Assessment Results</h2>
  <?php elseif($type === 'dimension'): ?>
  <h1>SBM Dimension Performance Summary</h1>
  <h2>School-Based Management Evaluation Report</h2>
  <?php else: ?>
  <h1>SBM Self-Assessment Checklist</h1>
  <h2>Annex A — DepEd Order No. 007, s. 2024</h2>
  <?php endif; ?>
  <p>School Year <?= htmlspecialchars($cycle['sy_label']) ?></p>
</div>

<!-- School information block -->
<table class="school-info">
  <tr>
    <td class="lbl">School Name</td>
    <td colspan="3"><strong><?= htmlspecialchars($cycle['school_name']) ?></strong></td>
  </tr>
  <tr>
    <td class="lbl">School ID (DepEd)</td>
    <td><?= htmlspecialchars($cycle['school_id_deped'] ?? '—') ?></td>
    <td class="lbl">Classification</td>
    <td><?= htmlspecialchars($cycle['classification'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="lbl">School Head</td>
    <td><?= htmlspecialchars($cycle['school_head_name'] ?? '—') ?></td>
    <td class="lbl">Division</td>
    <td><?= htmlspecialchars($cycle['division_name'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="lbl">Address</td>
    <td><?= htmlspecialchars($cycle['address'] ?? '—') ?></td>
    <td class="lbl">School Year</td>
    <td><?= htmlspecialchars($cycle['sy_label']) ?></td>
  </tr>
  <tr>
    <td class="lbl">Total Enrollment</td>
    <td><?= number_format($cycle['total_enrollment'] ?? 0) ?></td>
    <td class="lbl">Total Teachers</td>
    <td><?= number_format($cycle['total_teachers'] ?? 0) ?></td>
  </tr>
  <tr>
    <td class="lbl">Assessment Status</td>
    <td><?= ucfirst(str_replace('_', ' ', $cycle['status'])) ?></td>
    <td class="lbl">Validated By</td>
    <td><?= htmlspecialchars($cycle['validator_name'] ?? '—') ?></td>
  </tr>
</table>

<!-- Overall score summary -->
<?php if($cycle['overall_score']): ?>
<table class="score-summary">
  <tr>
    <td style="width:25%;background:#F0FDF4;border:2px solid #86EFAC;">
      <div class="score-val"><?= number_format($cycle['overall_score'], 2) ?>%</div>
      <div class="score-lbl">Overall SBM Score</div>
    </td>
    <td style="width:20%;background:<?= $overallMat['bg'] ?>;border:1px solid <?= $overallMat['color'] ?>30;">
      <span class="maturity-badge" style="background:<?= $overallMat['bg'] ?>;color:<?= $overallMat['color'] ?>;">
        <?= htmlspecialchars($cycle['maturity_level'] ?? $overallMat['label']) ?>
      </span>
      <div class="score-lbl" style="margin-top:4px;">Maturity Level</div>
    </td>
    <td style="width:55%;">
      <table style="width:100%;border-collapse:collapse;">
        <?php foreach($dimScores as $ds):
          $mat = getMaturityLabel((float)$ds['percentage']);
        ?>
        <tr>
          <td style="font-size:7.5pt;padding:2px 0;width:45%;">
            D<?= $ds['dimension_no'] ?>: <?= htmlspecialchars($ds['dimension_name']) ?>
          </td>
          <td style="width:45%;padding:2px 4px;">
            <div class="prog-outer">
              <div class="prog-inner"
                   style="width:<?= min(100, $ds['percentage']) ?>%;
                          background:<?= htmlspecialchars($ds['color_hex']) ?>;"></div>
            </div>
          </td>
          <td style="font-size:7.5pt;font-weight:bold;color:<?= $mat['color'] ?>;
                     text-align:right;white-space:nowrap;width:10%;padding:2px 0 2px 4px;">
            <?= number_format($ds['percentage'], 1) ?>%
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </td>
  </tr>
</table>
<?php endif; ?>

<?php if($type === 'dimension'): ?>
<!-- ═══════════════════════════════════════════════════════════
     DIMENSION SUMMARY REPORT
═══════════════════════════════════════════════════════════ -->
<div class="section-heading">Dimension Performance Summary</div>

<table class="dim-summary">
  <thead>
    <tr>
      <th style="width:5%;">#</th>
      <th style="width:30%;">Dimension</th>
      <th style="width:12%;text-align:center;">Raw Score</th>
      <th style="width:12%;text-align:center;">Max Score</th>
      <th style="width:12%;text-align:center;">Percentage</th>
      <th style="width:15%;text-align:center;">Maturity Level</th>
      <th style="width:14%;">Progress</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($dimScores as $ds):
    $mat = getMaturityLabel((float)$ds['percentage']);
  ?>
  <tr>
    <td style="text-align:center;font-weight:bold;color:<?= htmlspecialchars($ds['color_hex']) ?>;">
        <?= $ds['dimension_no'] ?>
    </td>
    <td><?= htmlspecialchars($ds['dimension_name']) ?></td>
    <td style="text-align:center;font-weight:bold;">
        <?= number_format($ds['raw_score'], 1) ?>
    </td>
    <td style="text-align:center;color:#6B7280;">
        <?= number_format($ds['max_score'], 1) ?>
    </td>
    <td style="text-align:center;font-weight:bold;color:<?= $mat['color'] ?>;">
        <?= number_format($ds['percentage'], 2) ?>%
    </td>
    <td style="text-align:center;">
      <span class="rating-pill"
            style="background:<?= $mat['bg'] ?>;color:<?= $mat['color'] ?>;">
        <?= $mat['label'] ?>
      </span>
    </td>
    <td>
      <div class="prog-outer">
        <div class="prog-inner"
             style="width:<?= min(100,$ds['percentage']) ?>%;
                    background:<?= htmlspecialchars($ds['color_hex']) ?>;"></div>
      </div>
      <div style="font-size:7pt;color:#9CA3AF;margin-top:2px;">
        <?= number_format($ds['raw_score'],1) ?>/<?= number_format($ds['max_score'],1) ?> pts
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(!$dimScores): ?>
  <tr><td colspan="7" style="text-align:center;color:#9CA3AF;padding:14px;">
      No dimension scores computed yet.
  </td></tr>
  <?php endif; ?>
  </tbody>
</table>

<!-- Indicator breakdown per dimension -->
<div class="section-heading">Indicator Ratings by Dimension</div>
<?php foreach($grouped as $dimNo => $inds):
  $first = $inds[0];
  $rated1 = count(array_filter($inds, fn($i) => $i['rating'] == 1));
  $rated2 = count(array_filter($inds, fn($i) => $i['rating'] == 2));
  $rated3 = count(array_filter($inds, fn($i) => $i['rating'] == 3));
  $rated4 = count(array_filter($inds, fn($i) => $i['rating'] == 4));
?>
<table class="ind-table">
  <thead>
    <tr>
      <td colspan="3"
          style="background:<?= htmlspecialchars($first['color_hex']) ?>;
                 color:#fff;font-weight:bold;padding:5px 8px;font-size:8.5pt;">
        Dimension <?= $dimNo ?>: <?= htmlspecialchars($first['dimension_name']) ?>
        &nbsp;·&nbsp;
        <span style="font-size:7.5pt;opacity:.9;">
          NYM: <?= $rated1 ?> &nbsp; Rarely: <?= $rated2 ?> &nbsp;
          Frequently: <?= $rated3 ?> &nbsp; Always: <?= $rated4 ?>
        </span>
      </td>
    </tr>
    <tr>
      <th style="width:8%;">Code</th>
      <th style="width:55%;">Indicator</th>
      <th style="width:37%;">Rating</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($inds as $ind): ?>
  <tr>
    <td><span class="ind-code"><?= htmlspecialchars($ind['indicator_code']) ?></span></td>
    <td style="font-size:7.5pt;line-height:1.4;">
        <?= htmlspecialchars($ind['indicator_text']) ?>
    </td>
    <td>
      <span class="rating-pill"
            style="background:<?= $ratingBgs[$ind['rating']] ?>;
                   color:<?= $ratingColors[$ind['rating']] ?>;">
        <?= $ind['rating'] ?> — <?= $ratingLabels[$ind['rating']] ?>
      </span>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endforeach; ?>

<?php elseif($type === 'improvement'): ?>
<!-- ═══════════════════════════════════════════════════════════
     SCHOOL IMPROVEMENT PLAN
═══════════════════════════════════════════════════════════ -->
<div class="section-heading">School Improvement Action Plans</div>

<?php if($plans): ?>
<table class="plan-table">
  <thead>
    <tr>
      <th style="width:8%;">Priority</th>
      <th style="width:18%;">Dimension</th>
      <th style="width:8%;">Indicator</th>
      <th style="width:25%;">Objective</th>
      <th style="width:22%;">Strategy / Actions</th>
      <th style="width:10%;">Person Responsible</th>
      <th style="width:9%;">Target Date</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($plans as $p): ?>
  <tr>
    <td class="priority-<?= strtolower($p['priority_level']) ?>">
        <?= htmlspecialchars($p['priority_level']) ?>
    </td>
    <td style="font-size:7.5pt;"><?= htmlspecialchars($p['dimension_name']) ?></td>
    <td style="font-family:monospace;font-size:7.5pt;">
        <?= htmlspecialchars($p['indicator_code'] ?? '—') ?>
    </td>
    <td style="font-size:7.5pt;line-height:1.4;">
        <?= htmlspecialchars($p['objective']) ?>
    </td>
    <td style="font-size:7.5pt;line-height:1.4;">
        <?= htmlspecialchars($p['strategy']) ?>
    </td>
    <td style="font-size:7.5pt;">
        <?= htmlspecialchars($p['person_responsible'] ?? '—') ?>
    </td>
    <td style="font-size:7.5pt;text-align:center;">
        <?= $p['target_date'] ? date('M d, Y', strtotime($p['target_date'])) : '—' ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p style="text-align:center;color:#9CA3AF;padding:20px;">
    No improvement plans added yet.
</p>
<?php endif; ?>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════
     ANNEX A — FULL CHECKLIST (default)
═══════════════════════════════════════════════════════════ -->
<div class="section-heading">Self-Assessment Checklist (Annex A)</div>

<?php foreach($grouped as $dimNo => $inds):
  $first = $inds[0];
  $dsDim = null;
  foreach($dimScores as $ds) {
    if ($ds['dimension_no'] == $dimNo) { $dsDim = $ds; break; }
  }
  $mat = $dsDim ? getMaturityLabel((float)$dsDim['percentage']) : null;
?>
<table class="ind-table">
  <thead>
    <tr>
      <td colspan="5"
          style="background:<?= htmlspecialchars($first['color_hex']) ?>;
                 color:#fff;font-weight:bold;padding:6px 8px;font-size:8.5pt;">
        Dimension <?= $dimNo ?>: <?= htmlspecialchars($first['dimension_name']) ?>
        <?php if($dsDim): ?>
          &nbsp;·&nbsp;
          <span style="font-size:8pt;">
            <?= number_format($dsDim['percentage'], 1) ?>%
            (<?= $mat['label'] ?>)
            &nbsp;—&nbsp;
            Raw: <?= number_format($dsDim['raw_score'], 1) ?>/<?= number_format($dsDim['max_score'], 1) ?> pts
          </span>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <th style="width:7%;">Code</th>
      <th style="width:38%;">Indicator / Key Action</th>
      <th style="width:22%;">Means of Verification</th>
      <th style="width:17%;">Rating</th>
      <th style="width:16%;">Evidence / MOV Notes</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($inds as $ind): ?>
  <tr>
    <td style="text-align:center;">
      <span class="ind-code"><?= htmlspecialchars($ind['indicator_code']) ?></span>
    </td>
    <td style="font-size:7.5pt;line-height:1.4;">
        <?= htmlspecialchars($ind['indicator_text']) ?>
    </td>
    <td style="font-size:7pt;color:#6B7280;font-style:italic;line-height:1.4;">
        <?= htmlspecialchars($ind['mov_guide'] ?? '—') ?>
    </td>
    <td style="text-align:center;">
      <span class="rating-pill"
            style="background:<?= $ratingBgs[$ind['rating']] ?>;
                   color:<?= $ratingColors[$ind['rating']] ?>;">
        <?= $ind['rating'] ?> — <?= $ratingLabels[$ind['rating']] ?>
      </span>
    </td>
    <td style="font-size:7.5pt;color:#374151;line-height:1.4;">
        <?= htmlspecialchars($ind['evidence_text'] ?? '—') ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endforeach; ?>

<?php if(empty($grouped)): ?>
<p style="text-align:center;color:#9CA3AF;padding:20px;">
    No indicator responses recorded yet.
</p>
<?php endif; ?>

<?php endif; // end type switch ?>

<!-- ── Signature Block ── -->
<table class="signature-block">
  <tr>
    <td>
      <div class="signature-line"></div>
      <div class="signature-name">
          <?= htmlspecialchars($cycle['school_head_name'] ?? 'School Head') ?>
      </div>
      <div class="signature-title">School Head / SBM Coordinator</div>
    </td>
    <td>
      <div class="signature-line"></div>
      <div class="signature-name">District Supervisor</div>
      <div class="signature-title">PSDS / District SBM Validator</div>
    </td>
    <td>
      <div class="signature-line"></div>
      <div class="signature-name">SDO Representative</div>
      <div class="signature-title">Division SBM Focal Person</div>
    </td>
  </tr>
</table>

<!-- ── Footer ── -->
<div class="report-footer">
  Generated by <?= htmlspecialchars(SITE_NAME) ?> &nbsp;·&nbsp;
  <?= date('F d, Y \a\t g:i A') ?> &nbsp;·&nbsp;
  DepEd Order No. 007, s. 2024 &nbsp;·&nbsp;
  <?= htmlspecialchars($cycle['school_name']) ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// ── Generate PDF with mPDF ────────────────────────────────────
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'orientation'   => 'L',            // Landscape for wide tables
        'margin_top'    => 12,
        'margin_bottom' => 14,
        'margin_left'   => 12,
        'margin_right'  => 12,
        'default_font'  => 'Arial',
        'default_font_size' => 9,
    ]);

    // Landscape is better for Annex A tables.
    // Switch dimension-only to portrait since it's narrower.
    if ($type === 'dimension') {
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 12,
            'margin_bottom' => 14,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'default_font'  => 'Arial',
            'default_font_size' => 9,
        ]);
    }

    // Header on every page
    $mpdf->SetHTMLHeader('
        <div style="font-size:7pt;color:#9CA3AF;text-align:right;
                    border-bottom:1px solid #E5E7EB;padding-bottom:3px;">
            ' . htmlspecialchars($cycle['school_name']) . '
            &nbsp;·&nbsp; SY ' . htmlspecialchars($cycle['sy_label']) . '
            &nbsp;·&nbsp; SBM Self-Assessment Report (Annex A)
        </div>
    ');

    // Footer on every page
    $mpdf->SetHTMLFooter('
        <div style="font-size:7pt;color:#9CA3AF;text-align:center;
                    border-top:1px solid #E5E7EB;padding-top:3px;">
            Page {PAGENO} of {nbpg}
            &nbsp;·&nbsp; ' . htmlspecialchars(SITE_NAME) . '
            &nbsp;·&nbsp; DepEd Order No. 007, s. 2024
        </div>
    ');

    $mpdf->WriteHTML($html);

    // Build filename
    $schoolSlug = preg_replace('/[^a-zA-Z0-9]+/', '_',
                               $cycle['school_name'] ?? 'school');
    $typeLabel  = ['annex_a' => 'AnnexA', 'dimension' => 'DimensionReport',
                   'improvement' => 'ImprovementPlan'][$type] ?? 'Report';
    $filename   = "SBM_{$typeLabel}_{$schoolSlug}_{$cycle['sy_label']}.pdf";
    $filename   = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $filename);

    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (\Mpdf\MpdfException $e) {
    http_response_code(500);
    die('PDF generation failed: ' . htmlspecialchars($e->getMessage()));
}