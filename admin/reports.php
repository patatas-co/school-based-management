<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin','sdo','ro');
$db = getDB();

$syId = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());
$schoolId = (int)($_GET['school'] ?? 0);

$schools = $db->query("SELECT school_id,school_name FROM schools ORDER BY school_name")->fetchAll();
$syears  = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();

// If school selected, get full report data
$reportData = null;
if ($schoolId) {
    $cycle = $db->prepare("SELECT c.*,s.school_name,s.school_id_deped,s.classification,s.school_head_name,s.address,s.total_enrollment,sy.label sy_label FROM sbm_cycles c JOIN schools s ON c.school_id=s.school_id JOIN school_years sy ON c.sy_id=sy.sy_id WHERE c.school_id=? AND c.sy_id=? LIMIT 1");
    $cycle->execute([$schoolId,$syId]); $reportData = $cycle->fetch();

    if ($reportData) {
        $dimScores = $db->prepare("SELECT ds.*,d.dimension_no,d.dimension_name,d.color_hex FROM sbm_dimension_scores ds JOIN sbm_dimensions d ON ds.dimension_id=d.dimension_id WHERE ds.cycle_id=? ORDER BY d.dimension_no");
        $dimScores->execute([$reportData['cycle_id']]); $dimScores = $dimScores->fetchAll();

        $responses = $db->prepare("SELECT r.*,i.indicator_code,i.indicator_text,i.mov_guide,d.dimension_no,d.dimension_name FROM sbm_responses r JOIN sbm_indicators i ON r.indicator_id=i.indicator_id JOIN sbm_dimensions d ON i.dimension_id=d.dimension_id WHERE r.cycle_id=? ORDER BY d.dimension_no,i.sort_order");
        $responses->execute([$reportData['cycle_id']]); $responses = $responses->fetchAll();
    }
}

$pageTitle = 'Reports'; $activePage = 'reports.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Reports & Documentation</h2><p>Generate official SBM Annex A and performance reports.</p></div>
  <div class="page-head-actions">
    <?php if($reportData): ?>
    <a href="/export_pdf.php?cycle_id=<?= $cycle['cycle_id'] ?>&type=dimension"
   target="_blank" class="btn btn-secondary">
  Download Dimension Report (PDF)
</a>
    <?php endif; ?>
  </div>
</div>

<div class="card mb5" style="margin-bottom:18px;">
  <div class="card-body" style="padding:14px 18px;">
    <form method="get" class="flex-c" style="gap:10px;flex-wrap:wrap;">
      <div class="fg" style="margin-bottom:0;min-width:200px;">
        <select name="school" class="fc">
          <option value="">— Select School —</option>
          <?php foreach($schools as $sc): ?>
          <option value="<?= $sc['school_id'] ?>" <?= $sc['school_id']==$schoolId?'selected':'' ?>><?= e($sc['school_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg" style="margin-bottom:0;">
        <select name="sy" class="fc">
          <?php foreach($syears as $sy): ?>
          <option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id']==$syId?'selected':'' ?>><?= e($sy['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Generate Report</button>
    </form>
  </div>
</div>

<?php if (!$schoolId): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:40px 40px;">
    <div style="width:52px;height:52px;background:var(--n100);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
      <?= svgIcon('file-text','','') ?>
    </div>
    <h3 style="font-size:16px;font-weight:600;color:var(--n600);margin-bottom:6px;">Select a School</h3>
    <p style="font-size:13px;color:var(--n400);">Choose a school and school year above to generate the SBM Self-Assessment Report (Annex A).</p>
  </div>
</div>
<?php elseif(!$reportData): ?>
<div class="alert alert-warning"><?= svgIcon('alert-circle') ?> No assessment data found for this school and school year.</div>
<?php else: ?>
<?php include __DIR__.'/../includes/report_annex_a.php'; ?>
<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>
