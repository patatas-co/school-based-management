<?php
// ============================================================
// school_head/school_profile.php
// School-level profile management for School Head role.
// (System-level config stays in admin/school_profile.php)
// Transfer: School Head now manages their own school's info.
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/roles.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('school_head', 'admin', 'sbm_coordinator');
$db = getDB();

// School Head can only edit THEIR school; admin can edit any
$schoolId = SCHOOL_ID;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    if ($_POST['action'] === 'save') {
        // School Head can update operational info — NOT school_id_deped or classification
        // (those remain admin-only structural fields)
        $db->prepare("
            UPDATE schools SET
                school_head_name  = ?,
                contact_no        = ?,
                email             = ?,
                total_enrollment  = ?,
                total_teachers    = ?,
                address           = ?
            WHERE school_id = ?
        ")->execute([
            trim($_POST['school_head_name']),
            trim($_POST['contact_no']),
            trim($_POST['email']),
            (int)$_POST['total_enrollment'],
            (int)$_POST['total_teachers'],
            trim($_POST['address']),
            $schoolId,
        ]);

        logActivity('sh_update_school_profile', 'school_profile',
            'School Head updated school profile for school_id: ' . $schoolId);

        echo json_encode(['ok' => true, 'msg' => 'School profile updated.']);
        exit;
    }
    exit;
}

$school = $db->prepare("
    SELECT s.*, d.division_name
    FROM schools s
    LEFT JOIN divisions d ON s.division_id = d.division_id
    WHERE s.school_id = ?
");
$school->execute([$schoolId]);
$school = $school->fetch();

// Stats
$totalTeachers = $db->query(
    "SELECT COUNT(*) FROM users WHERE role='teacher' AND status='active'"
)->fetchColumn();

$currentCycle = $db->query(
    "SELECT c.*, sy.label
     FROM sbm_cycles c
     JOIN school_years sy ON c.sy_id = sy.sy_id
     WHERE c.school_id = " . $schoolId . "
     ORDER BY c.created_at DESC LIMIT 1"
)->fetch();

$pageTitle  = 'School Profile';
$activePage = 'school_profile.php';
include __DIR__ . '/../includes/header.php';
?>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">School Configuration</div>
    <div class="ph2-title">School Profile</div>
    <div class="ph2-sub">
      Manage your school's operational information.
      <span style="font-size:12px;color:var(--n-400);display:block;margin-top:3px;">
        Note: School ID, classification, and division are managed by the System Administrator.
      </span>
    </div>
  </div>
  <div class="ph2-right">
    <button class="btn btn-primary" onclick="openModal('mEdit')"><?= svgIcon('edit') ?> Edit Profile</button>
  </div>
</div>

<!-- Profile Card -->
<div class="grid2" style="gap:20px;margin-bottom:20px;align-items:start;">
  <div class="card">
    <div class="card-head">
      <span class="card-title">Institutional Information</span>
    </div>
    <div style="padding:0;">
      <?php $fields = [
        ['School Name',       $school['school_name'],       false],
        ['DepEd School ID',   $school['school_id_deped'],   true],   // read-only for SH
        ['Address',           $school['address'],            false],
        ['Classification',    $school['classification'],     true],   // read-only for SH
        ['School Head',       $school['school_head_name'],  false],
        ['Contact Number',    $school['contact_no'] ?? '—', false],
        ['Email',             $school['email'] ?? '—',      false],
        ['Division',          $school['division_name'] ?? '—', true],
      ];
      foreach($fields as [$label, $val, $readonly]): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;
                  padding:12px 20px;border-bottom:1px solid var(--n-100);">
        <span style="font-size:13px;color:var(--n-500);font-weight:500;">
          <?= $label ?>
          <?php if($readonly): ?>
          <span style="font-size:10px;color:var(--n-400);font-weight:400;margin-left:4px;">(admin only)</span>
          <?php endif; ?>
        </span>
        <span style="font-size:13.5px;font-weight:600;color:var(--n-900);"><?= e($val ?? '—') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:14px;">
    <div class="kpi-row" style="grid-template-columns:1fr 1fr;">
      <div class="kpi-mini">
        <div class="kpi-mini-val"><?= number_format($school['total_enrollment']) ?></div>
        <div class="kpi-mini-lbl">Total Enrollment</div>
      </div>
      <div class="kpi-mini">
        <div class="kpi-mini-val"><?= $school['total_teachers'] ?></div>
        <div class="kpi-mini-lbl">Teaching Staff</div>
      </div>
      <div class="kpi-mini">
        <div class="kpi-mini-val"><?= $totalTeachers ?></div>
        <div class="kpi-mini-lbl">Active in Portal</div>
      </div>
      <div class="kpi-mini">
        <?php if($currentCycle): $mat = sbmMaturityLevel(floatval($currentCycle['overall_score'] ?? 0)); ?>
        <div class="kpi-mini-val" style="color:<?= $mat['color'] ?>;"><?= $currentCycle['overall_score'] ? $currentCycle['overall_score'].'%' : '—' ?></div>
        <div class="kpi-mini-lbl">Current SBM Score</div>
        <?php else: ?>
        <div class="kpi-mini-val">—</div><div class="kpi-mini-lbl">SBM Score</div>
        <?php endif; ?>
      </div>
    </div>

    <?php if($currentCycle): ?>
    <div class="card">
      <div class="card-head"><span class="card-title">Current Assessment</span></div>
      <div class="card-body" style="padding:14px 18px;">
        <div class="flex-cb"><span style="font-size:13px;color:var(--n-500);">School Year</span><strong><?= e($currentCycle['label']) ?></strong></div>
        <div class="flex-cb" style="margin-top:8px;"><span style="font-size:13px;color:var(--n-500);">Status</span><span class="pill pill-<?= e($currentCycle['status']) ?>"><?= ucfirst(str_replace('_',' ',$currentCycle['status'])) ?></span></div>
        <?php if($currentCycle['maturity_level']): ?>
        <div class="flex-cb" style="margin-top:8px;"><span style="font-size:13px;color:var(--n-500);">Maturity Level</span><?= sbmMaturityBadge($currentCycle['maturity_level']) ?></div>
        <?php endif; ?>
        <div style="margin-top:12px;">
          <a href="self_assessment.php" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;"><?= svgIcon('eye') ?> View Assessment</a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Edit Modal — School Head can only edit operational fields -->
<div class="overlay" id="mEdit">
  <div class="modal" style="max-width:520px;">
    <div class="modal-head">
      <span class="modal-title">Edit School Profile</span>
      <button class="modal-close" onclick="closeModal('mEdit')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">

      <!-- Read-only admin fields shown for reference -->
      <div style="background:var(--n-50);border:1px solid var(--n-200);border-radius:8px;
                  padding:12px 14px;margin-bottom:16px;">
        <div style="font-size:11px;font-weight:700;color:var(--n-400);text-transform:uppercase;
                    letter-spacing:.06em;margin-bottom:8px;">
          Fields managed by System Administrator
        </div>
        <div style="font-size:13px;color:var(--n-600);">
          <strong>School Name:</strong> <?= e($school['school_name']) ?><br>
          <strong>DepEd ID:</strong> <?= e($school['school_id_deped'] ?? '—') ?>  &nbsp;·&nbsp;
          <strong>Classification:</strong> <?= e($school['classification']) ?>
        </div>
      </div>

      <div class="fg"><label>School Head Name</label><input class="fc" id="s_head" value="<?= e($school['school_head_name'] ?? '') ?>"></div>
      <div class="fg"><label>Address</label><input class="fc" id="s_addr" value="<?= e($school['address'] ?? '') ?>"></div>
      <div class="form-row">
        <div class="fg"><label>Contact No.</label><input class="fc" id="s_contact" value="<?= e($school['contact_no'] ?? '') ?>"></div>
        <div class="fg"><label>Email</label><input class="fc" type="email" id="s_email" value="<?= e($school['email'] ?? '') ?>"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Total Enrollment</label><input class="fc" type="number" id="s_enroll" value="<?= $school['total_enrollment'] ?>"></div>
        <div class="fg"><label>Total Teachers</label><input class="fc" type="number" id="s_teachers" value="<?= $school['total_teachers'] ?>"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mEdit')">Cancel</button>
      <button class="btn btn-primary" onclick="saveProfile()"><?= svgIcon('save') ?> Save Changes</button>
    </div>
  </div>
</div>

<script>
async function saveProfile() {
  const r = await apiPost('school_profile.php', {
    action:           'save',
    school_head_name: $('s_head'),
    address:          $('s_addr'),
    contact_no:       $('s_contact'),
    email:            $('s_email'),
    total_enrollment: $('s_enroll'),
    total_teachers:   $('s_teachers'),
  });
  toast(r.msg, r.ok ? 'ok' : 'err');
  if (r.ok) { closeModal('mEdit'); setTimeout(() => location.reload(), 800); }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
