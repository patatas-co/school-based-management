<?php
ob_start();
// ============================================================
// system_admin/pending_requests.php — Pending Registration Requests
// Split out of users.php so User Accounts stays focused on
// existing/processed accounts only.
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSystemAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  while (ob_get_level())
    ob_end_clean();
  header('Content-Type: application/json; charset=UTF-8');
  verifyCsrf();
  $action = $_POST['action'];

  if ($action === 'get_pending') {
    $st = $db->prepare("SELECT user_id,username,email,full_name,employee_id,department,profile_picture,created_at,status FROM users WHERE user_id=? AND status='pending'");
    $st->execute([(int) $_POST['id']]);
    $row = $st->fetch();
    if (!$row) {
      echo json_encode(['ok' => false, 'msg' => 'Request not found or already reviewed.']);
      exit;
    }
    $row['photo_url'] = (!empty($row['profile_picture']) && file_exists(__DIR__ . '/../' . $row['profile_picture']))
      ? baseUrl() . '/' . $row['profile_picture'] . '?v=' . time() : null;
    $row['submitted_label'] = date('M j, Y g:i A', strtotime($row['created_at']));
    echo json_encode(array_merge(['ok' => true], $row));
    exit;
  }

  if ($action === 'approve_user') {
    $id = (int) ($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $validRoleSlugs = $db->query("SELECT slug FROM roles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($role, $validRoleSlugs, true)) {
      echo json_encode(['ok' => false, 'msg' => 'Please select a valid role before approving.']);
      exit;
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
      echo json_encode(['ok' => false, 'msg' => 'Please select a valid account status.']);
      exit;
    }
    $u = $db->prepare("SELECT user_id,full_name,email,status FROM users WHERE user_id=?");
    $u->execute([$id]);
    $u = $u->fetch();
    if (!$u || $u['status'] !== 'pending') {
      echo json_encode(['ok' => false, 'msg' => 'This request is no longer pending.']);
      exit;
    }
    try {
      $db->prepare("UPDATE users SET role=?, status=?, reviewed_by=?, reviewed_at=NOW() WHERE user_id=?")
        ->execute([$role, $status, (int) $_SESSION['user_id'], $id]);
      logActivity('approve_user', 'users', 'Approved registration for user ID:' . $id . ' (status=' . $status . ')');

      require_once __DIR__ . '/../includes/email_service.php';
      $sent = sendAccountCreationEmail($db, $u);

      echo json_encode(['ok' => true, 'msg' => 'Account approved. ' . ($sent ? 'A password setup link has been emailed to the user.' : 'Approved, but the setup email failed to send — use "Resend Email" from the account list.')]);
      exit;
    } catch (PDOException $e) {
      echo json_encode(['ok' => false, 'msg' => 'Failed to approve account.']);
      exit;
    }
  }

  if ($action === 'reject_user') {
    $id = (int) ($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
      echo json_encode(['ok' => false, 'msg' => 'A reason for rejection is required.']);
      exit;
    }
    $u = $db->prepare("SELECT user_id,full_name,email,status FROM users WHERE user_id=?");
    $u->execute([$id]);
    $u = $u->fetch();
    if (!$u || $u['status'] !== 'pending') {
      echo json_encode(['ok' => false, 'msg' => 'This request is no longer pending.']);
      exit;
    }
    try {
      $db->prepare("UPDATE users SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE user_id=?")
        ->execute([$reason, (int) $_SESSION['user_id'], $id]);
      logActivity('reject_user', 'users', 'Rejected registration for user ID:' . $id);

      require_once __DIR__ . '/../includes/email_service.php';
      $sent = sendRejectionEmail($db, $u, $reason);

      echo json_encode(['ok' => true, 'msg' => 'Request rejected.' . ($sent ? '' : ' (Notification email failed to send.)')]);
      exit;
    } catch (PDOException $e) {
      echo json_encode(['ok' => false, 'msg' => 'Failed to reject account.']);
      exit;
    }
  }

  echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
  exit;
}

$q = $_GET['q'] ?? '';
$sql = "SELECT user_id,username,email,full_name,employee_id,department,profile_picture,created_at FROM users WHERE status='pending'";
$p = [];
if ($q) {
  $qE = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($q)) . '%';
  $sql .= " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
  $p = array_merge($p, [$qE, $qE, $qE]);
}
$sql .= " ORDER BY created_at ASC";
$stmt = $db->prepare($sql);
$stmt->execute($p);
$pendingRows = $stmt->fetchAll();
$pendingUsersCount = count($pendingRows);

$pageTitle = 'Pending Requests';
$activePage = 'pending_requests.php';
include __DIR__ . '/../includes/header.php';

$_allRoles = $db->query("SELECT slug,label,color,description FROM roles ORDER BY CASE slug WHEN 'system_admin' THEN 1 WHEN 'school_head' THEN 2 WHEN 'sbm_coordinator' THEN 3 WHEN 'teacher' THEN 4 WHEN 'external_stakeholder' THEN 5 ELSE 6 END ASC, label ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page-level actions (no container) -->

<div class="card" style="box-shadow:none;border:1px solid var(--n-150,#e5e7eb);">
  <!-- Table toolbar: search + count -->
  <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;width:100%;padding:16px 20px;border-bottom:1px solid var(--n-100,#f1f5f9);">
    <div class="search" style="flex:0 1 320px;min-width:220px;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" id="liveSearch" placeholder="Search by name, username or email…"
        value="<?= e($q) ?>" autocomplete="off"
        style="width:100%;">
    </div>
    <span style="padding:4px 11px;border-radius:999px;font-size:12px;font-weight:700;background:#FEF3C7;color:#D97706;margin-left:auto;">
      <?= (int) $pendingUsersCount ?> Pending
    </span>
  </div>

  <?php if (!$pendingRows): ?>
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('user-check') ?></div>
      <div class="empty-title">No pending requests</div>
      <div class="empty-sub">
        <?= $q ? 'No pending requests match "' . e($q) . '".' : 'All registration requests have been reviewed.' ?>
      </div>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table id="tblPending" class="tbl-enhanced">
        <thead>
          <tr>
            <th>User</th>
            <th>Employee ID</th>
            <th>Email</th>
            <th>Department</th>
            <th>Username</th>
            <th>Requested</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingRows as $u): ?>
            <tr data-user-id="<?= $u['user_id'] ?>" data-user-status="pending">
              <td>
                <div class="cell-avatar">
                  <?php if (!empty($u['profile_picture']) && file_exists(__DIR__ . '/../' . $u['profile_picture'])): ?>
                    <img src="<?= baseUrl() . '/' . $u['profile_picture'] ?>?v=<?= time() ?>"
                         style="width:34px;height:34px;border-radius:9px;object-fit:cover;flex-shrink:0;"
                         alt="<?= e($u['full_name']) ?>">
                  <?php else: ?>
                    <div class="cell-av" style="background:#D97706;"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                  <?php endif; ?>
                  <div class="cell-av-info">
                    <div class="cell-av-name"><?= e($u['full_name']) ?></div>
                  </div>
                </div>
              </td>
              <td style="font-size:12px;color:var(--n-500);"><?= $u['employee_id'] !== null && $u['employee_id'] !== '' ? e($u['employee_id']) : '<span style="color:var(--n-300);">—</span>' ?></td>
              <td style="font-size:12px;color:var(--n-500);"><?= e($u['email']) ?></td>
              <td style="font-size:12px;color:var(--n-500);">
                <?php if (!empty($u['department'])): ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600;background:var(--n-100);color:var(--n-600);border:1px solid var(--n-200);">
                    <?= e($u['department']) ?>
                  </span>
                <?php else: ?>
                  <span style="color:var(--n-300);">—</span>
                <?php endif; ?>
              </td>
              <td style="font-family:monospace;font-size:12px;color:var(--n-500);"><?= e($u['username']) ?></td>
              <td style="font-size:12px;color:var(--n-500);"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div class="user-row-actions">
                  <button class="btn btn-secondary btn-sm" onclick="viewPendingUser(<?= $u['user_id'] ?>)" title="Review submission"><?= svgIcon('eye') ?></button>
                  <button class="btn btn-success btn-sm" onclick="openApproveModal(<?= $u['user_id'] ?>)" title="Approve request"><?= svgIcon('check') ?></button>
                  <button class="btn btn-warning btn-sm" onclick="openRejectModal(<?= $u['user_id'] ?>)" title="Reject request"><?= svgIcon('x') ?></button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- View Pending Request Modal -->
<div class="overlay" id="mViewPending">
  <div class="modal" style="max-width:440px;">
    <div class="modal-head">
      <span class="modal-title">Registration Request</span>
      <button class="modal-close" onclick="closeModal('mViewPending')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <div style="display:flex;flex-direction:column;align-items:center;margin-bottom:20px;">
        <div id="vp_photo_wrap" style="width:84px;height:84px;border-radius:16px;overflow:hidden;background:var(--n-100);display:flex;align-items:center;justify-content:center;margin-bottom:10px;">
          <span id="vp_photo_fallback" style="font-size:28px;font-weight:700;color:var(--n-400);"></span>
          <img id="vp_photo_img" style="display:none;width:100%;height:100%;object-fit:cover;">
        </div>
        <div id="vp_name" style="font-weight:700;font-size:16px;color:var(--n-800);"></div>
        <span style="display:inline-flex;margin-top:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#FEF9C3;color:#CA8A04;">Pending</span>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 16px;">
        <div>
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--n-400);margin-bottom:4px;">Employee ID</div>
          <div id="vp_empid" style="font-size:13.5px;color:var(--n-700);"></div>
        </div>
        <div>
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--n-400);margin-bottom:4px;">Username</div>
          <div id="vp_username" style="font-size:13.5px;color:var(--n-700);font-family:monospace;"></div>
        </div>
        <div style="grid-column:1 / -1;">
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--n-400);margin-bottom:4px;">Email</div>
          <div id="vp_email" style="font-size:13.5px;color:var(--n-700);"></div>
        </div>
        <div>
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--n-400);margin-bottom:4px;">Department</div>
          <div id="vp_dept" style="font-size:13.5px;color:var(--n-700);"></div>
        </div>
        <div>
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--n-400);margin-bottom:4px;">Registered</div>
          <div id="vp_submitted" style="font-size:13.5px;color:var(--n-700);"></div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mViewPending')">Close</button>
      <button class="btn btn-warning" onclick="closeModal('mViewPending'); openRejectModal(vpCurrentId);">Reject</button>
      <button class="btn btn-primary" onclick="closeModal('mViewPending'); openApproveModal(vpCurrentId);">Approve</button>
    </div>
  </div>
</div>

<!-- Approve Request Modal -->
<div class="overlay" id="mApprove">
  <div class="modal" style="max-width:420px;">
    <div class="modal-head">
      <span class="modal-title">Approve Account</span>
      <button class="modal-close" onclick="closeModal('mApprove')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom:16px;font-size:13.5px;color:var(--n-600);line-height:1.6;">
        Approving <strong id="ap_name" style="color:var(--n-800);"></strong>'s request will send a password setup
        link to their email.
      </div>
      <div class="fg">
        <label>Assign Role <span style="color:var(--red,#ef4444);">*</span></label>
        <div class="p-select p-select-fluid" id="pApRoleDropdown">
          <input type="hidden" id="ap_role" value="">
          <div class="p-select-trigger" onclick="togglePSelect(event, 'pApRoleDropdown')">
            <span class="p-select-val" id="pApRoleLabel">Select Role</span>
            <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
          </div>
          <div class="p-select-menu">
            <?php foreach ($_allRoles as $r): ?>
              <div class="p-select-item" data-val="<?= e($r['slug']) ?>"
                onclick="setApRole('<?= e($r['slug']) ?>', '<?= e($r['label']) ?>')">
                <div class="p-item-content">
                  <div class="p-item-title"><?= e($r['label']) ?></div>
                  <div class="p-item-desc"><?= !empty($r['description']) ? e($r['description']) : ($r['slug'] === 'system_admin' ? 'Total system control' : 'Standard institutional access') ?></div>
                </div>
                <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="fg">
        <label>Account Status <span style="color:var(--red,#ef4444);">*</span></label>
        <div class="p-select p-select-fluid" id="pApStatusDropdown">
          <input type="hidden" id="ap_status" value="active">
          <div class="p-select-trigger" onclick="togglePSelect(event, 'pApStatusDropdown')">
            <span class="p-select-val" id="pApStatusLabel">Active</span>
            <?= svgIcon('chevron-down', '', 'width:16px;height:16px;stroke:var(--n-400);') ?>
          </div>
          <div class="p-select-menu">
            <?php foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $val => $lbl): ?>
              <div class="p-select-item <?= $val === 'active' ? 'active' : '' ?>" data-val="<?= $val ?>"
                onclick="setApStatus('<?= $val ?>', '<?= $lbl ?>')">
                <div class="p-item-content">
                  <div class="p-item-title"><?= $lbl ?></div>
                  <div class="p-item-desc"><?= $val === 'active' ? 'Can log in once password is set' : 'Access is restricted' ?></div>
                </div>
                <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mApprove')">Cancel</button>
      <button class="btn btn-primary" id="btnConfirmApprove" onclick="confirmApprove()"><?= svgIcon('check', 15) ?> Approve Account</button>
    </div>
  </div>
</div>

<!-- Reject Request Modal -->
<div class="overlay" id="mReject">
  <div class="modal" style="max-width:420px;">
    <div class="modal-head">
      <span class="modal-title">Reject Request</span>
      <button class="modal-close" onclick="closeModal('mReject')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom:16px;font-size:13.5px;color:var(--n-600);line-height:1.6;">
        Rejecting <strong id="rj_name" style="color:var(--n-800);"></strong>'s request will notify them by email,
        including the reason below.
      </div>
      <div class="fg">
        <label>Reason for Rejection <span style="color:var(--red,#ef4444);">*</span></label>
        <textarea class="fc" id="rj_reason" rows="4" placeholder="e.g. Employee ID could not be verified against school records."></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mReject')">Cancel</button>
      <button class="btn btn-warning" id="btnConfirmReject" onclick="confirmReject()"><?= svgIcon('x', 15) ?> Reject Request</button>
    </div>
  </div>
</div>

<script>
  // ── Live search (reuses same query-string convention as users.php) ──
  let _pendingSearchDebounce;
  document.getElementById('liveSearch').addEventListener('input', function () {
    clearTimeout(_pendingSearchDebounce);
    const val = this.value;
    _pendingSearchDebounce = setTimeout(() => {
      const url = new URL(window.location.href);
      if (val) url.searchParams.set('q', val); else url.searchParams.delete('q');
      window.location.href = url.toString();
    }, 450);
  });

  // ── Pending Requests: View / Approve / Reject ──
  let vpCurrentId = null;

  async function viewPendingUser(id) {
    const r = await apiPost('pending_requests.php', { action: 'get_pending', id });
    if (!r || !r.ok) { toast(r?.msg || 'Failed to load request.', 'err'); return; }
    vpCurrentId = id;

    document.getElementById('vp_name').textContent = r.full_name;
    document.getElementById('vp_empid').textContent = r.employee_id || '—';
    document.getElementById('vp_username').textContent = r.username;
    document.getElementById('vp_email').textContent = r.email;
    document.getElementById('vp_dept').textContent = r.department || '—';
    document.getElementById('vp_submitted').textContent = r.submitted_label;

    const img = document.getElementById('vp_photo_img');
    const fallback = document.getElementById('vp_photo_fallback');
    if (r.photo_url) {
      img.src = r.photo_url;
      img.style.display = 'block';
      fallback.style.display = 'none';
    } else {
      img.style.display = 'none';
      fallback.style.display = 'block';
      fallback.textContent = (r.full_name || '?').charAt(0).toUpperCase();
    }

    openModal('mViewPending');
  }

  function setApRole(v, l) {
    $v('ap_role', v);
    document.getElementById('pApRoleLabel').textContent = l;
    document.querySelectorAll('#pApRoleDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }
  function setApStatus(v, l) {
    $v('ap_status', v);
    document.getElementById('pApStatusLabel').textContent = l;
    document.querySelectorAll('#pApStatusDropdown .p-select-item').forEach(i => i.classList.toggle('active', i.dataset.val === v));
    closeAllPSelects();
  }

  let apCurrentId = null;
  async function openApproveModal(id) {
    const r = await apiPost('pending_requests.php', { action: 'get_pending', id });
    if (!r || !r.ok) { toast(r?.msg || 'Failed to load request.', 'err'); return; }
    apCurrentId = id;
    document.getElementById('ap_name').textContent = r.full_name;
    $v('ap_role', '');
    document.getElementById('pApRoleLabel').textContent = 'Select Role';
    document.querySelectorAll('#pApRoleDropdown .p-select-item').forEach(i => i.classList.remove('active'));
    setApStatus('active', 'Active');
    openModal('mApprove');
  }

  async function confirmApprove() {
    const role = $('ap_role');
    const status = $('ap_status') || 'active';
    if (!role) { toast('Please select a role before approving.', 'warning'); return; }
    const btn = document.getElementById('btnConfirmApprove');
    btn.disabled = true;
    const r = await apiPost('pending_requests.php', { action: 'approve_user', id: apCurrentId, role, status });
    btn.disabled = false;
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mApprove'); setTimeout(() => location.reload(), 900); }
  }

  let rjCurrentId = null;
  async function openRejectModal(id) {
    const r = await apiPost('pending_requests.php', { action: 'get_pending', id });
    if (!r || !r.ok) { toast(r?.msg || 'Failed to load request.', 'err'); return; }
    rjCurrentId = id;
    document.getElementById('rj_name').textContent = r.full_name;
    document.getElementById('rj_reason').value = '';
    openModal('mReject');
  }

  async function confirmReject() {
    const reason = document.getElementById('rj_reason').value.trim();
    if (!reason) { toast('Please provide a reason for rejection.', 'warning'); return; }
    const btn = document.getElementById('btnConfirmReject');
    btn.disabled = true;
    const r = await apiPost('pending_requests.php', { action: 'reject_user', id: rjCurrentId, reason });
    btn.disabled = false;
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mReject'); setTimeout(() => location.reload(), 900); }
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>