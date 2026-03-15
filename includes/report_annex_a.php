<?php
// includes/report_annex_a.php
// Shared Annex A report renderer.
// Requires: $reportData, $dimScores, $responses, $db
// All three must be set before including this file.

if (!$reportData): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:40px;">
    <h3 style="font-size:16px;font-weight:600;color:var(--n600);margin-bottom:6px;">Select a School</h3>
    <p style="font-size:13px;color:var(--n400);">Choose a school and school year above to generate the SBM Self-Assessment Report (Annex A).</p>
  </div>
</div>
<?php elseif(empty($reportData)): ?>
<div class="alert alert-warning"><?= svgIcon('alert-circle') ?> No assessment data found for this school and school year.</div>
<?php else: ?>

<div id="printReport">

<!-- Header -->
<div style="text-align:center;margin-bottom:24px;padding:20px;background:var(--white);border:1px solid var(--n200);border-radius:var(--radius);">
  <p style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--n500);margin-bottom:8px;">Republic of the Philippines · Department of Education</p>
  <h2 style="font-family:var(--font-serif);font-size:22px;color:var(--n900);margin-bottom:4px;">SBM Self-Assessment Report</h2>
  <h3 style="font-size:16px;font-weight:600;color:var(--g700);margin-bottom:12px;">Annex A — School-Based Management Checklist</h3>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;max-width:600px;margin:0 auto;font-size:13px;">
    <div><span style="color:var(--n400);">School:</span><br><strong><?= e($reportData['school_name']) ?></strong></div>
    <div><span style="color:var(--n400);">School ID:</span><br><strong><?= e($reportData['school_id_deped']??'—') ?></strong></div>
    <div><span style="color:var(--n400);">School Year:</span><br><strong><?= e($reportData['sy_label']) ?></strong></div>
    <div><span style="color:var(--n400);">Classification:</span><br><strong><?= e($reportData['classification']) ?></strong></div>
    <div><span style="color:var(--n400);">School Head:</span><br><strong><?= e($reportData['school_head_name']??'—') ?></strong></div>
    <div><span style="color:var(--n400);">Overall Score:</span><br><strong style="color:var(--g700);font-size:16px;"><?= $reportData['overall_score'] ? $reportData['overall_score'].'%' : '—' ?></strong></div>
  </div>
  <?php if($reportData['maturity_level']): $mat = sbmMaturityLevel(floatval($reportData['overall_score'])); ?>
  <div style="margin-top:12px;display:inline-flex;padding:6px 18px;border-radius:999px;background:<?= $mat['bg'] ?>;color:<?= $mat['color'] ?>;font-weight:700;font-size:14px;">
    Maturity Level: <?= e($reportData['maturity_level']) ?>
  </div>
  <?php endif; ?>
</div>

<!-- Dimension Summary -->
<div class="card" style="margin-bottom:18px;">
  <div class="card-head"><span class="card-title">Dimension Summary</span></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Dimension</th><th>Raw Score</th><th>Max Score</th><th>Percentage</th><th>Maturity</th></tr></thead>
      <tbody>
      <?php foreach(($dimScores??[]) as $ds): ?>
      <?php $mat = sbmMaturityLevel(floatval($ds['percentage'])); ?>
      <tr>
        <td style="font-weight:700;color:<?= e($ds['color_hex']) ?>;"><?= $ds['dimension_no'] ?></td>
        <td><strong><?= e($ds['dimension_name']) ?></strong></td>
        <td style="font-weight:600;"><?= number_format($ds['raw_score'],1) ?></td>
        <td style="color:var(--n400);"><?= number_format($ds['max_score'],1) ?></td>
        <td>
          <div class="flex-c" style="gap:8px;">
            <div class="prog" style="width:100px;"><div class="prog-fill" style="width:<?= $ds['percentage'] ?>%;background:<?= $mat['color'] ?>;"></div></div>
            <strong style="color:<?= $mat['color'] ?>;"><?= number_format($ds['percentage'],1) ?>%</strong>
          </div>
        </td>
        <td><span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:<?= $mat['bg'] ?>;color:<?= $mat['color'] ?>;"><?= $mat['label'] ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Full Indicator Checklist -->
<?php
$ratingMap    = [1=>'Not Yet Manifested',2=>'Emerging',3=>'Developing',4=>'Always Manifested'];
$ratingColors = [1=>'#DC2626',2=>'#D97706',3=>'#2563EB',4=>'#16A34A'];
$grouped = [];
foreach(($responses??[]) as $r) $grouped[$r['dimension_no']][] = $r;
?>
<?php foreach($grouped as $dimNo => $indicators): ?>
<?php $first = $indicators[0]; ?>
<div class="card" style="margin-bottom:14px;">
  <div class="card-head" style="background:<?= htmlspecialchars($first['color_hex']??'#16A34A') ?>1A;">
    <span class="card-title">Dimension <?= $dimNo ?>: <?= e($first['dimension_name']) ?></span>
    <span style="font-size:12px;color:var(--n500);"><?= count($indicators) ?> indicators</span>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th style="width:70px;">Code</th><th>Indicator</th><th style="width:180px;">Means of Verification</th><th style="width:150px;">Rating</th><th>Evidence</th></tr></thead>
      <tbody>
      <?php foreach($indicators as $ind): ?>
      <tr>
        <td><strong style="font-size:12px;color:var(--n600);"><?= e($ind['indicator_code']) ?></strong></td>
        <td style="font-size:12.5px;line-height:1.5;"><?= e($ind['indicator_text']) ?></td>
        <td style="font-size:11.5px;color:var(--n500);font-style:italic;"><?= e($ind['mov_guide']) ?></td>
        <td>
          <span style="display:inline-flex;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $ratingColors[$ind['rating']] ?>22;color:<?= $ratingColors[$ind['rating']] ?>;">
            <?= $ind['rating'] ?> — <?= e($ratingMap[$ind['rating']]??'—') ?>
          </span>
        </td>
        <td style="font-size:12px;color:var(--n600);"><?= e($ind['evidence_text']??'—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<?php if(empty($responses)): ?>
<div class="alert alert-info"><?= svgIcon('info') ?> No indicator responses recorded yet for this assessment cycle.</div>
<?php endif; ?>

</div><!-- #printReport -->
<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>