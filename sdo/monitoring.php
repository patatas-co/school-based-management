<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo','admin');
$db = getDB();
$syId = (int)($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());
$status = $_GET['status'] ?? '';

$sql = "SELECT s.*,c.status cycle_status,c.overall_score,c.maturity_level,c.submitted_at,c.cycle_id FROM schools s LEFT JOIN sbm_cycles c ON s.school_id=c.school_id AND c.sy_id=? WHERE 1=1";
$p = [$syId];
if ($status) { $sql .= $status === 'not_started' ? " AND c.cycle_id IS NULL" : " AND c.status=?"; if($status!=='not_started') $p[] = $status; }
$sql .= " ORDER BY s.school_name";
$stmt = $db->prepare($sql); $stmt->execute($p); $schools = $stmt->fetchAll();
$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();

$pageTitle = 'School Monitoring'; $activePage = 'monitoring.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>School Monitoring</h2><p>Track SBM assessment status of all schools.</p></div>
  <div class="page-head-actions">
    <select class="fc" onchange="location.href='monitoring.php?sy='+this.value" style="width:150px;">
      <?php foreach($syears as $sy): ?><option value="<?= $sy['sy_id'] ?>" <?= $sy['sy_id']==$syId?'selected':'' ?>><?= e($sy['label']) ?></option><?php endforeach; ?>
    </select>
  </div>
</div>

<div class="flex-c" style="gap:8px;margin-bottom:18px;flex-wrap:wrap;">
  <?php $tabs = [''=> 'All','not_started'=>'Not Started','draft'=>'Draft','in_progress'=>'In Progress','submitted'=>'Submitted','validated'=>'Validated']; ?>
  <?php foreach($tabs as $sv => $sl): ?>
  <a href="monitoring.php?sy=<?= $syId ?>&status=<?= $sv ?>" class="btn btn-<?= $status===$sv?'primary':'secondary' ?> btn-sm"><?= $sl ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">Schools (<?= count($schools) ?>)</span></div>
  <div class="tbl-wrap">
    <table id="tblMon">
      <thead><tr><th>School</th><th>Classification</th><th>Assessment Status</th><th>Overall Score</th><th>Maturity Level</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
      <?php foreach($schools as $s): ?>
      <tr>
        <td><strong style="font-size:13px;"><?= e($s['school_name']) ?></strong><div style="font-size:11.5px;color:var(--n400);"><?= e($s['school_id_deped']??'') ?></div></td>
        <td><span class="pill pill-active"><?= e($s['classification']) ?></span></td>
        <td>
          <?php if($s['cycle_id']): ?>
            <span class="pill pill-<?= e($s['cycle_status']) ?>"><?= ucfirst(str_replace('_',' ',$s['cycle_status'])) ?></span>
          <?php else: ?>
            <span style="font-size:12px;color:var(--n400);">Not Started</span>
          <?php endif; ?>
        </td>
        <td style="font-weight:700;color:var(--g700);font-size:14px;"><?= $s['overall_score'] ? $s['overall_score'].'%' : '—' ?></td>
        <td><?php if($s['maturity_level']): ?><span class="pill pill-<?= e($s['maturity_level']) ?>"><?= e($s['maturity_level']) ?></span><?php else: ?>—<?php endif; ?></td>
        <td style="font-size:12px;color:var(--n500);"><?= $s['submitted_at'] ? date('M d, Y',strtotime($s['submitted_at'])) : '—' ?></td>
        <td>
          <?php if($s['cycle_id']): ?>
          <a href="../admin/view_assessment.php?id=<?= $s['cycle_id'] ?>" class="btn btn-secondary btn-sm"><?= svgIcon('eye') ?> View</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$schools): ?><tr><td colspan="7" style="text-align:center;color:var(--n400);padding:24px;">No schools found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
