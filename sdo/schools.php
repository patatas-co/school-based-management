<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('sdo','admin');
$db = getDB();
$divisionId = $_SESSION['division_id'] ?? null;
$sy = $db->query("SELECT * FROM school_years WHERE is_current=1 LIMIT 1")->fetch();

$schoolsQ = "SELECT s.*, d.division_name,
  c.cycle_id, c.status cycle_status, c.overall_score, c.maturity_level, c.submitted_at
  FROM schools s JOIN divisions d ON s.division_id=d.division_id
  LEFT JOIN sbm_cycles c ON c.school_id=s.school_id AND c.sy_id=?
  WHERE 1=1";
$params = [$sy['sy_id']??0];
if ($divisionId) { $schoolsQ .= " AND s.division_id=?"; $params[] = $divisionId; }
$schoolsQ .= " ORDER BY s.school_name";
$stmt = $db->prepare($schoolsQ); $stmt->execute($params); $schools = $stmt->fetchAll();

$pageTitle = 'Schools'; $activePage = 'schools.php';
include __DIR__.'/../includes/header.php';
?>
<div class="page-head">
  <div class="page-head-text"><h2>Schools</h2><p>SBM assessment status per school — SY <?= e($sy['label']??'—') ?></p></div>
</div>

<div class="card mb5" style="margin-bottom:14px;">
  <div class="card-body" style="padding:10px 16px;">
    <div class="search"><span class="si"><?= svgIcon('search') ?></span>
      <input type="text" placeholder="Search schools…" oninput="filterTable(this.value,'schoolTbl')">
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><span class="card-title">Schools <span style="font-weight:400;color:var(--n400);">(<?= count($schools) ?>)</span></span></div>
  <div class="tbl-wrap">
    <table id="schoolTbl">
      <thead><tr><th>School</th><th>Type</th><th>Enrollment</th><th>Assessment</th><th>Score</th><th>Maturity</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($schools as $s): ?>
      <tr>
        <td>
          <div><strong style="font-size:13px;"><?= e($s['school_name']) ?></strong>
          <div style="font-size:11.5px;color:var(--n400);"><?= e($s['school_id_deped']??'') ?></div></div>
        </td>
        <td><span class="pill pill-teacher" style="font-size:10px;"><?= e($s['classification']) ?></span></td>
        <td style="font-size:13px;"><?= number_format($s['total_enrollment']) ?></td>
        <td>
          <?php if ($s['cycle_id']): ?>
            <span class="pill pill-<?= e($s['cycle_status']) ?>"><?= ucfirst(str_replace('_',' ',$s['cycle_status'])) ?></span>
          <?php else: ?>
            <span style="color:var(--n400);font-size:12px;">Not Started</span>
          <?php endif; ?>
        </td>
        <td><?= $s['overall_score'] ? '<strong style="color:var(--g600);">'.number_format($s['overall_score'],1).'%</strong>' : '—' ?></td>
        <td><?= $s['maturity_level'] ? sbmMaturityBadge($s['maturity_level']) : '—' ?></td>
        <td style="font-size:12px;color:var(--n400);"><?= $s['submitted_at'] ? date('M d, Y',strtotime($s['submitted_at'])) : '—' ?></td>
        <td>
          <?php if ($s['cycle_id']): ?>
          <a href="assessments.php?cycle=<?= $s['cycle_id'] ?>" class="btn btn-secondary btn-sm"><?= svgIcon('eye') ?> View</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
