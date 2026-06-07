<?php
ob_start();
// ============================================================
// system_admin/departments.php — Department Management
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSystemAdmin();
$db = getDB();

// ── AJAX / POST actions ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    verifyCsrf();
    $action = $_POST['action'];

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) { echo json_encode(['ok' => false, 'msg' => 'Department name is required.']); exit; }
        try {
            $db->prepare("INSERT INTO departments (name, description, school_id, created_at) VALUES (?, ?, ?, NOW())")
               ->execute([$name, $desc ?: null, SCHOOL_ID]);
            $newId = $db->lastInsertId();
            logActivity('create_department', 'departments', 'Created: ' . $name);
            echo json_encode(['ok' => true, 'msg' => 'Department created.', 'id' => $newId]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getCode() == 23000 ? 'Department name already exists.' : 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get') {
        $st = $db->prepare("SELECT department_id, name, description FROM departments WHERE department_id=? AND school_id=?");
        $st->execute([(int)$_POST['id'], SCHOOL_ID]);
        echo json_encode($st->fetch());
        exit;
    }

    if ($action === 'get_users') {
        try {
            $deptName = trim($_POST['dept_name'] ?? '');
            $st = $db->prepare("SELECT user_id, full_name, email, role FROM users WHERE department=? AND school_id=? ORDER BY full_name ASC");
        $st->execute([$deptName, SCHOOL_ID]);
            echo json_encode(['ok' => true, 'users' => $st->fetchAll()]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'users' => [], 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) { echo json_encode(['ok' => false, 'msg' => 'Department name is required.']); exit; }
        try {
            // Get old name before updating
            $old = $db->prepare("SELECT name FROM departments WHERE department_id=? AND school_id=?");
            $old->execute([$id, SCHOOL_ID]);
            $oldName = $old->fetchColumn();

            $db->prepare("UPDATE departments SET name=?, description=? WHERE department_id=? AND school_id=?")
               ->execute([$name, $desc ?: null, $id, SCHOOL_ID]);

            // Cascade rename to all users who had the old department name
            if ($oldName && $oldName !== $name) {
                $db->prepare("UPDATE users SET department=? WHERE CONVERT(department USING utf8mb4) = ? AND school_id=?")
                   ->execute([$name, $oldName, SCHOOL_ID]);
            }

            logActivity('update_department', 'departments', 'Updated department: ' . $oldName . ' → ' . $name);
            echo json_encode(['ok' => true, 'msg' => 'Department updated.']);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getCode() == 23000 ? 'Department name already exists.' : 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            // Check if any users belong to this department
            $count = $db->prepare("SELECT COUNT(*) FROM users WHERE department=(SELECT name FROM departments WHERE department_id=?) AND school_id=?");
            $count->execute([$id, SCHOOL_ID]);
            if ((int)$count->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'msg' => 'Cannot delete — users are assigned to this department.']);
                exit;
            }
            $db->prepare("DELETE FROM departments WHERE department_id=? AND school_id=?")->execute([$id, SCHOOL_ID]);
            logActivity('delete_department', 'departments', 'Deleted department ID: ' . $id);
            echo json_encode(['ok' => true, 'msg' => 'Department deleted.']);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'msg' => 'Cannot delete department: ' . $e->getMessage()]);
        }
        exit;
    }
    exit;
}

// ── Page data ───────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');
$sql = "SELECT d.department_id, d.name, d.description, d.created_at,
               (SELECT COUNT(*) FROM users u WHERE u.department = d.name AND u.school_id = d.school_id) AS user_count
        FROM departments d
        WHERE d.school_id = ?";
$p = [SCHOOL_ID];
if ($q) {
    $qE = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $q) . '%';
    $sql .= " AND d.name LIKE ?";
    $p[] = $qE;
}
$sql .= " ORDER BY d.name ASC";
$stmt = $db->prepare($sql);
$stmt->execute($p);
$departments = $stmt->fetchAll();

$totalDepts   = $db->prepare("SELECT COUNT(*) FROM departments WHERE school_id=?");
$totalDepts->execute([SCHOOL_ID]);
$totalDepts   = (int)$totalDepts->fetchColumn();

$pageTitle  = 'Departments';
$activePage = 'departments.php';
include __DIR__ . '/../includes/header.php';
?>

<!-- Search bar -->
<div class="filter-bar-v2" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
  <div class="search" style="flex:1;min-width:200px;max-width:360px;">
    <span class="si"><?= svgIcon('search') ?></span>
    <input type="text" id="liveSearch" placeholder="Search departments…"
           value="<?= e($q) ?>" autocomplete="off" style="width:100%;">
  </div>
</div>

<!-- Inline Add / Edit Form -->
<div id="deptFormCard" style="
  background:#fff;
  border:1px solid var(--n-150,#e5e7eb);
  border-radius:14px;
  margin-bottom:20px;
  box-shadow:0 1px 4px rgba(0,0,0,.06);
  overflow:hidden;
">
  <!-- Form header -->
  <div style="
    padding:14px 20px;
    background:var(--n-50,#f9fafb);
    border-bottom:1px solid var(--n-150,#e5e7eb);
    display:flex;align-items:center;gap:8px;
  ">
    <span style="
      width:30px;height:30px;border-radius:8px;
      background:var(--brand-100,#dcfce7);
      color:var(--brand-600,#16a34a);
      display:flex;align-items:center;justify-content:center;
      flex-shrink:0;
    "><?= svgIcon('briefcase', 15) ?></span>
    <span id="formCardTitle" style="font-weight:700;font-size:14px;color:var(--n-800,#1e293b);">Add Department</span>
  </div>

  <!-- Form body -->
  <div style="padding:20px 24px;">
    <input type="hidden" id="f_id" value="">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

      <!-- Department Name -->
      <div>
        <label style="display:block;font-size:12px;font-weight:600;color:var(--n-600,#475569);margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase;">
          Department Name <span style="color:var(--red,#ef4444);">*</span>
        </label>
        <input type="text" id="f_name" placeholder="e.g. Science Department" maxlength="120" autocomplete="off"
          style="
            width:100%;padding:9px 12px;border-radius:8px;
            border:1.5px solid var(--n-200,#e2e8f0);
            font-size:13px;color:var(--n-800);
            background:#fff;outline:none;box-sizing:border-box;
            transition:border-color .15s;
          "
          onfocus="this.style.borderColor='var(--brand-500,#22c55e)'"
          onblur="this.style.borderColor='var(--n-200,#e2e8f0)'">
      </div>

      <!-- Description -->
      <div>
        <label style="display:block;font-size:12px;font-weight:600;color:var(--n-600,#475569);margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase;">
          Description <span style="font-weight:400;color:var(--n-400,#94a3b8);text-transform:none;letter-spacing:0;">(optional)</span>
        </label>
        <input type="text" id="f_desc" placeholder="Brief description of this department…" maxlength="255" autocomplete="off"
          style="
            width:100%;padding:9px 12px;border-radius:8px;
            border:1.5px solid var(--n-200,#e2e8f0);
            font-size:13px;color:var(--n-800);
            background:#fff;outline:none;box-sizing:border-box;
            transition:border-color .15s;
          "
          onfocus="this.style.borderColor='var(--brand-500,#22c55e)'"
          onblur="this.style.borderColor='var(--n-200,#e2e8f0)'">
      </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--n-100,#f1f5f9);">
      <button id="btnCancelEdit" onclick="cancelEdit()" style="
        display:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;
        border:1.5px solid var(--n-200,#e2e8f0);background:#fff;color:var(--n-600);cursor:pointer;
      ">Cancel</button>
      <button id="btnSaveDept" onclick="saveDept()" style="
        display:flex;align-items:center;gap:6px;
        padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;
        background:var(--brand-600,#16a34a);color:#fff;border:none;cursor:pointer;
        box-shadow:0 1px 3px rgba(22,163,74,.25);transition:opacity .15s;
      " onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
        <?= svgIcon('check', 15) ?> <span id="btnSaveLabel">Save</span>
      </button>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--n-100);">
    <span style="font-weight:600;font-size:14px;color:var(--n-700);">
      <?= svgIcon('briefcase') ?>
      Departments
      <span style="margin-left:6px;padding:2px 8px;border-radius:999px;font-size:11px;background:var(--n-100);color:var(--n-500);"><?= $totalDepts ?></span>
    </span>
  </div>

  <?php if (!$departments): ?>
    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('briefcase') ?></div>
      <div class="empty-title">No departments found</div>
      <div class="empty-sub">
        <?= $q ? 'No departments match "' . e($q) . '". Try a different search term.' : 'Get started by adding your first department.' ?>
      </div>
    </div>
  <?php else: ?>
    <div class="tbl-wrap">
      <table id="tblDepts" class="tbl-enhanced">
        <thead>
          <tr>
            <th>#</th>
            <th>Department Name</th>
            <th>Description</th>
            <th>Users</th>
            <th>Created</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($departments as $i => $d): ?>
            <tr data-dept-id="<?= $d['department_id'] ?>">
              <td style="color:var(--n-400);font-size:12px;"><?= $i + 1 ?></td>
              <td>
                <span style="font-weight:600;font-size:13px;color:var(--n-800);"><?= e($d['name']) ?></span>
              </td>
              <td style="font-size:13px;color:var(--n-500);"><?= $d['description'] ? e($d['description']) : '<span style="color:var(--n-300);">—</span>' ?></td>
              <td>
                <span onclick="openUsersModal(<?= htmlspecialchars(json_encode($d['name'])) ?>)"
                  style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:var(--brand-50);color:var(--brand-600);border:1px solid var(--brand-100);cursor:pointer;transition:background .15s;"
                  onmouseover="this.style.background='var(--brand-100)'"
                  onmouseout="this.style.background='var(--brand-50)'">
                  <?= svgIcon('users', 12) ?> <?= (int)$d['user_count'] ?>
                </span>
              </td>
              <td style="font-size:12px;color:var(--n-400);"><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
              <td style="text-align:center;">
                <button onclick="openEdit(<?= $d['department_id'] ?>, <?= htmlspecialchars(json_encode($d['name'])) ?>, <?= htmlspecialchars(json_encode($d['description'] ?? '')) ?>)"
                  title="Edit"
                  style="
                    display:inline-flex;align-items:center;gap:5px;
                    padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;
                    background:var(--brand-50,#f0fdf4);color:var(--brand-600,#16a34a);
                    border:1.5px solid var(--brand-200,#bbf7d0);cursor:pointer;
                    transition:background .15s,border-color .15s;
                  "
                  onmouseover="this.style.background='var(--brand-100,#dcfce7)'"
                  onmouseout="this.style.background='var(--brand-50,#f0fdf4)'">
                  <?= svgIcon('edit', 13) ?> Edit
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Users Modal -->
<div id="usersModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:520px;margin:20px;box-shadow:0 8px 40px rgba(0,0,0,.18);overflow:hidden;display:flex;flex-direction:column;max-height:85vh;">
    <!-- Modal Header -->
    <div style="padding:16px 20px;background:var(--n-50,#f9fafb);border-bottom:1px solid var(--n-150,#e5e7eb);display:flex;align-items:center;justify-content:space-between;">
      <span style="font-weight:700;font-size:14px;color:var(--n-800);display:flex;align-items:center;gap:8px;">
        <?= svgIcon('users', 16) ?>
        <span id="usersModalTitle">Teachers</span>
      </span>
      <button onclick="closeUsersModal()" style="background:none;border:none;cursor:pointer;color:var(--n-400);font-size:20px;line-height:1;padding:2px 6px;">&times;</button>
    </div>
    <!-- Search -->
    <div style="padding:12px 16px;border-bottom:1px solid var(--n-100);">
      <div style="position:relative;">
        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--n-400);"><?= svgIcon('search', 14) ?></span>
        <input type="text" id="usersModalSearch" placeholder="Search teachers…" autocomplete="off"
          oninput="filterModalUsers()"
          style="width:100%;padding:8px 10px 8px 32px;border-radius:8px;border:1.5px solid var(--n-200,#e2e8f0);font-size:13px;outline:none;box-sizing:border-box;"
          onfocus="this.style.borderColor='var(--brand-500,#22c55e)'"
          onblur="this.style.borderColor='var(--n-200,#e2e8f0)'">
      </div>
    </div>
    <!-- List -->
    <div id="usersModalList" style="overflow-y:auto;flex:1;padding:8px 0;"></div>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let _modalUsers = [];

async function openUsersModal(deptName) {
  document.getElementById('usersModalTitle').textContent = deptName + ' — Teachers';
  document.getElementById('usersModalSearch').value = '';
  document.getElementById('usersModalList').innerHTML = '<div style="padding:24px;text-align:center;color:var(--n-400);font-size:13px;">Loading…</div>';
  document.getElementById('usersModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';

  const fd = new FormData();
  fd.append('action', 'get_users');
  fd.append('dept_name', deptName);
  fd.append('csrf_token', CSRF);
  try {
    const r = await fetch('departments.php', { method: 'POST', body: fd });
    const data = await r.json();
    _modalUsers = data.users || [];
    renderModalUsers(_modalUsers);
  } catch {
    document.getElementById('usersModalList').innerHTML = '<div style="padding:24px;text-align:center;color:var(--red,#ef4444);font-size:13px;">Failed to load teachers.</div>';
  }
}

function renderModalUsers(users) {
  const el = document.getElementById('usersModalList');
  if (!users.length) {
    el.innerHTML = '<div style="padding:24px;text-align:center;color:var(--n-400);font-size:13px;">No teachers found.</div>';
    return;
  }
  el.innerHTML = users.map((u, i) => `
    <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--n-100,#f1f5f9);">
      <div style="width:34px;height:34px;border-radius:50%;background:var(--brand-100,#dcfce7);color:var(--brand-700,#15803d);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">
        ${u.full_name ? u.full_name.charAt(0).toUpperCase() : '?'}
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:13px;color:var(--n-800);">${escHtml(u.full_name)}</div>
        <div style="font-size:11px;color:var(--n-400);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(u.email || '')}</div>
      </div>
      <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:999px;background:var(--n-100);color:var(--n-500);text-transform:capitalize;">${escHtml(u.role || '')}</span>
    </div>
  `).join('');
}

function filterModalUsers() {
  const q = document.getElementById('usersModalSearch').value.trim().toLowerCase();
  if (!q) { renderModalUsers(_modalUsers); return; }
  renderModalUsers(_modalUsers.filter(u =>
    (u.full_name || '').toLowerCase().includes(q) ||
    (u.email || '').toLowerCase().includes(q)
  ));
}

function closeUsersModal() {
  document.getElementById('usersModal').style.display = 'none';
  document.body.style.overflow = '';
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('usersModal').addEventListener('click', function(e) {
  if (e.target === this) closeUsersModal();
});

async function apiPost(data) {
  data.csrf_token = CSRF;
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const r = await fetch('departments.php', { method: 'POST', body: fd });
  return r.json();
}

function openEdit(id, name, desc) {
  document.getElementById('f_id').value   = id;
  document.getElementById('f_name').value = name;
  document.getElementById('f_desc').value = desc || '';
  document.getElementById('formCardTitle').textContent = 'Edit Department';
  document.getElementById('btnSaveLabel').textContent  = 'Save Changes';
  document.getElementById('btnCancelEdit').style.display = '';
  document.getElementById('f_name').focus();
  document.getElementById('deptFormCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelEdit() {
  document.getElementById('f_id').value   = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_desc').value = '';
  document.getElementById('formCardTitle').textContent = 'Add Department';
  document.getElementById('btnSaveLabel').textContent  = 'Save';
  document.getElementById('btnCancelEdit').style.display = 'none';
}

async function saveDept() {
  const id   = document.getElementById('f_id').value;
  const name = document.getElementById('f_name').value.trim();
  const desc = document.getElementById('f_desc').value.trim();
  if (!name) { toast('Department name is required.', 'warning'); return; }
  const btn = document.getElementById('btnSaveDept');
  btn.disabled = true;
  const action = id ? 'update' : 'create';
  const r = await apiPost({ action, id, name, description: desc });
  btn.disabled = false;
  if (r.ok) { toast(r.msg, 'ok'); setTimeout(() => location.reload(), 700); }
  else { toast(r.msg || 'Failed to save department.', 'err'); }
}

async function deleteDept(id, name) {
  if (!confirm(`Delete department "${name}"? This cannot be undone.`)) return;
  const r = await apiPost({ action: 'delete', id });
  if (r.ok) { toast(r.msg, 'ok'); document.querySelector(`tr[data-dept-id="${id}"]`)?.remove(); }
  else { toast(r.msg || 'Failed to delete.', 'err'); }
}

// Live search
(function () {
  const input = document.getElementById('liveSearch');
  if (!input) return;
  let debounce;
  input.addEventListener('input', function () {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      const q = input.value.trim();
      const url = new URL(window.location.href);
      if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
      fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newTable = doc.querySelector('#tblDepts')?.closest('.tbl-wrap') || doc.querySelector('.empty-state');
          const oldTable = document.querySelector('#tblDepts')?.closest('.tbl-wrap') || document.querySelector('.empty-state');
          const card = document.querySelector('.card');
          if (newTable && oldTable) { oldTable.replaceWith(newTable); }
          else if (newTable && card) { card.appendChild(newTable); }
          history.replaceState(null, '', url.toString());
        });
    }, 300);
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>