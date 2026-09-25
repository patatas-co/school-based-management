<?php
ob_start();
// ============================================================
// system_admin/manage_roles.php — Role Management
// Roles: driven by `roles` table (system + custom)
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSystemAdmin();
$db = getDB();

$pageTitle = 'Manage Roles';
$activePage = 'manage_roles.php';
include __DIR__ . '/../includes/header.php';

$roles = $db->query("SELECT id,slug,label,color,description,is_system,status,(SELECT COUNT(*) FROM users u WHERE u.role = roles.slug COLLATE utf8mb4_unicode_ci) AS user_count FROM roles ORDER BY CASE slug WHEN 'system_admin' THEN 1 WHEN 'school_head' THEN 2 WHEN 'sbm_coordinator' THEN 3 WHEN 'teacher' THEN 4 WHEN 'external_stakeholder' THEN 5 ELSE 6 END ASC, label ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Add Role -->
<div class="card" style="box-shadow:none;border:1px solid var(--n-150,#e5e7eb);margin-bottom:16px;">
  <div style="display:flex;align-items:center;gap:8px;padding:16px 20px;border-bottom:1px solid var(--n-100,#f1f5f9);">
    <span style="font-size:14px;font-weight:700;color:var(--n-800,#1e293b);">Add Role</span>
  </div>
  <div style="padding:20px;">
    <div style="display:flex;align-items:flex-end;gap:10px;">
      <div class="fg" style="flex:1;margin-bottom:0;">
        <label>Role Name *</label>
        <input class="fc" id="ar_label" placeholder="e.g. Department Head">
      </div>
      <div style="width:160px;">
        <label style="visibility:hidden;display:block;">&nbsp;</label>
        <button class="btn btn-primary" style="width:100%;height:38px;" onclick="saveAddRole()"><?= svgIcon('check') ?> Save</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <!-- Search bar -->
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--n-100,#f1f5f9);">
    <div class="search" style="flex:1;min-width:200px;max-width:360px;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" id="roleSearch" placeholder="Search roles…" autocomplete="off" style="width:100%;">
    </div>
  </div>
  <?php if (!$roles): ?>
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('shield') ?></div>
      <div class="empty-title">No roles found</div>
      <div class="empty-sub">No roles have been configured yet.</div>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table id="tblRoles" class="tbl-enhanced">
        <thead>
          <tr>
            <th>Role</th>
            <th>Users</th>
            <th>Status</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($roles as $r): ?>
            <tr data-role-id="<?= (int)$r['id'] ?>" data-role-name="<?= e(strtolower($r['label'] . ' ' . $r['description'])) ?>">
              <td>
                <span style="font-weight:600;color:#0F172A;"><?= e($r['label']) ?></span>
              </td>
              <td><?= (int)$r['user_count'] ?> user<?= (int)$r['user_count'] === 1 ? '' : 's' ?></td>
              <td>
                <span style="font-size:11px;font-weight:700;color:<?= $r['status'] === 'enabled' ? '#166534' : '#991B1B' ?>;background:<?= $r['status'] === 'enabled' ? '#DCFCE7' : '#FEE2E2' ?>;padding:3px 10px;border-radius:999px;">
                  <?= ucfirst(e($r['status'])) ?>
                </span>
              </td>
              <td style="text-align:center;">
                <div style="display:flex;align-items:center;justify-content:center;gap:5px;">
                  <button class="btn btn-secondary" style="padding:5px 10px;font-size:12px;"
                    onclick="openEditRoleModal(<?= (int)$r['id'] ?>,'<?= e(addslashes($r['label'])) ?>','<?= e(addslashes($r['description'])) ?>')"
                    title="Edit role" aria-label="Edit role">
                    <?= svgIcon('edit', '', 'width:13px;height:13px;') ?>
                  </button>
                  <button class="btn btn-secondary" style="padding:5px 10px;font-size:12px;color:<?= $r['status'] === 'enabled' ? '#DC2626' : '#166534' ?>;"
                    onclick="toggleRoleStatus(<?= (int)$r['id'] ?>,'<?= e(addslashes($r['label'])) ?>','<?= e($r['status']) ?>')"
                    title="<?= $r['status'] === 'enabled' ? 'Disable role' : 'Enable role' ?>" aria-label="<?= $r['status'] === 'enabled' ? 'Disable role' : 'Enable role' ?>">
                    <?= $r['status'] === 'enabled' ? svgIcon('x', '', 'width:13px;height:13px;') : svgIcon('check', '', 'width:13px;height:13px;') ?>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Edit Role Modal -->
<div class="overlay" id="mEditRole">
  <div class="modal" style="max-width:440px;">
    <div class="modal-head">
      <span class="modal-title">Edit Role</span>
      <button class="modal-close" onclick="closeModal('mEditRole')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="er_id">
      <div class="fg">
        <label>Role Name *</label>
        <input class="fc" id="er_label" placeholder="e.g. Department Head">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mEditRole')">Cancel</button>
      <button class="btn btn-primary" onclick="saveEditRoleModal()">Save Changes</button>
    </div>
  </div>
</div>

<script>
  async function saveAddRole() {
    const label = document.getElementById('ar_label').value.trim();
    if (!label) { toast('Role name is required.', 'warning'); return; }
    const r = await apiPost('users.php', { action: 'save_role', id: 0, label, color: '#64748B', description: '' });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      document.getElementById('ar_label').value = '';
      setTimeout(() => location.reload(), 500);
    }
  }

  function openEditRoleModal(id, label, description) {
    document.getElementById('er_id').value = id;
    document.getElementById('er_label').value = label;
    openModal('mEditRole');
  }

  async function saveEditRoleModal() {
    const id = document.getElementById('er_id').value;
    const label = document.getElementById('er_label').value.trim();
    if (!label) { toast('Role name is required.', 'warning'); return; }
    const r = await apiPost('users.php', { action: 'save_role', id, label, color: '#64748B', description: '' });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      closeModal('mEditRole');
      setTimeout(() => location.reload(), 500);
    }
  }

  async function toggleRoleStatus(id, label, status) {
    const action = status === 'enabled' ? 'disable' : 'enable';
    if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} role "${label}"?`)) return;
    const r = await apiPost('users.php', { action: 'toggle_role_status', id });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 500);
  }

  (function () {
    const input = document.getElementById('roleSearch');
    if (!input) return;
    input.addEventListener('input', function () {
      const q = input.value.trim().toLowerCase();
      document.querySelectorAll('#tblRoles tbody tr').forEach(row => {
        row.style.display = row.dataset.roleName.includes(q) ? '' : 'none';
      });
    });
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>