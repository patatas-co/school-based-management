<?php
// ============================================================
// system_admin/assign_indicators.php
// System Admin feature to assign specific SBM indicators
// to individual teachers.
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(ROLE_SYSTEM_ADMIN, ROLE_SCHOOL_HEAD, ROLE_COORDINATOR);
$db = getDB();

$schoolId = SCHOOL_ID;

// ── AJAX HANDLERS ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_start();
    ob_clean();
    header('Content-Type: application/json');
    verifyCsrf();

    try {
        if ($_POST['action'] === 'get_users_by_role') {
            $roleFilter = $_POST['role_filter'] ?? 'teacher';
            $allowedRoles = ['teacher', 'school_head', 'external_stakeholder'];
            if (!in_array($roleFilter, $allowedRoles, true)) {
                throw new Exception("Invalid role.");
            }
            $stmt = $db->prepare(
                "SELECT user_id, full_name, email FROM users
                 WHERE school_id=? AND role=? AND status='active'
                 ORDER BY full_name ASC"
            );
            $stmt->execute([$schoolId, $roleFilter]);
            echo json_encode(['ok' => true, 'users' => $stmt->fetchAll()]);
            exit;
        }

        if ($_POST['action'] === 'get_assignments') {
            $teacherId = (int) $_POST['teacher_id'];

            $stmt = $db->prepare("SELECT indicator_code FROM teacher_indicator_assignments WHERE teacher_id = ?");
            $stmt->execute([$teacherId]);
            $assigned = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['ok' => true, 'assigned' => $assigned]);
            exit;
        }

        if ($_POST['action'] === 'save_assignments') {
            $teacherId = (int) $_POST['teacher_id'];
            $indicators = isset($_POST['indicators']) && is_array($_POST['indicators']) ? $_POST['indicators'] : [];

            // Verify teacher belongs to this school
            $verify = $db->prepare("SELECT user_id FROM users WHERE user_id=? AND school_id=? AND role='teacher'");
            $verify->execute([$teacherId, $schoolId]);
            if (!$verify->fetchColumn()) {
                throw new Exception("Invalid teacher selected.");
            }

            $db->beginTransaction();

            // Clear existing
            $db->prepare("DELETE FROM teacher_indicator_assignments WHERE teacher_id = ?")->execute([$teacherId]);

            // Insert new
            if (!empty($indicators)) {
                $insert = $db->prepare("INSERT INTO teacher_indicator_assignments (teacher_id, indicator_code, assigned_by) VALUES (?, ?, ?)");
                foreach ($indicators as $code) {
                    // Make sure it's a valid teacher indicator
                    if (in_array($code, TEACHER_INDICATOR_CODES)) {
                        $insert->execute([$teacherId, $code, $_SESSION['user_id']]);
                    }
                }
            }

            $db->commit();
            logActivity('assign_indicators', 'coordinator', "Assigned " . count($indicators) . " indicators to teacher ID $teacherId");

            echo json_encode(['ok' => true, 'msg' => 'Assignments saved successfully!']);
            exit;
        }
    if ($_POST['action'] === 'bulk_save_assignments') {
            $userIds   = isset($_POST['user_ids'])   && is_array($_POST['user_ids'])   ? array_map('intval', $_POST['user_ids'])   : [];
            $indicators = isset($_POST['indicators']) && is_array($_POST['indicators']) ? $_POST['indicators'] : [];
            $roleFilter = $_POST['role_filter'] ?? 'teacher';

            $allowedRoles = ['teacher', 'school_head', 'external_stakeholder'];
            if (!in_array($roleFilter, $allowedRoles, true)) {
                throw new Exception("Invalid role filter.");
            }

            // Map role to valid indicator codes
            $roleCodeMap = [
                'teacher'              => TEACHER_INDICATOR_CODES,
                'external_stakeholder' => STAKEHOLDER_INDICATOR_CODES,
                'school_head'          => SH_RATEABLE_CODES,
            ];
            $validCodes = $roleCodeMap[$roleFilter] ?? [];

            if (empty($userIds)) {
                throw new Exception("No users selected.");
            }

            $db->beginTransaction();
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $verify = $db->prepare(
                "SELECT user_id FROM users WHERE user_id IN ($placeholders) AND school_id=? AND role=? AND status='active'"
            );
            $verify->execute([...$userIds, $schoolId, $roleFilter]);
            $validIds = $verify->fetchAll(PDO::FETCH_COLUMN);

            if (empty($validIds)) {
                throw new Exception("No valid users found for the selected role.");
            }

            $tableMap = [
                'teacher'              => ['table' => 'teacher_indicator_assignments',      'col' => 'teacher_id'],
                'external_stakeholder' => ['table' => 'stakeholder_indicator_assignments',  'col' => 'stakeholder_id'],
                'school_head'          => ['table' => 'school_head_indicator_assignments',  'col' => 'user_id'],
            ];
            $tbl = $tableMap[$roleFilter];

            foreach ($validIds as $uid) {
                $db->prepare("DELETE FROM {$tbl['table']} WHERE {$tbl['col']} = ?")->execute([$uid]);
                if (!empty($indicators)) {
                    $insert = $db->prepare(
                        "INSERT INTO {$tbl['table']} ({$tbl['col']}, indicator_code, assigned_by) VALUES (?, ?, ?)"
                    );
                    foreach ($indicators as $code) {
                        if (in_array($code, $validCodes)) {
                            $insert->execute([$uid, $code, $_SESSION['user_id']]);
                        }
                    }
                }
            }

            $db->commit();
            logActivity('bulk_assign_indicators', 'coordinator',
                "Bulk assigned " . count($indicators) . " indicators to " . count($validIds) . " {$roleFilter}(s)");

            echo json_encode(['ok' => true, 'msg' => 'Bulk assignments saved for ' . count($validIds) . ' user(s)!']);
            exit;
        }

    } catch (\Throwable $e) {
        if ($db->inTransaction())
            $db->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// ── FETCH TEACHERS ────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$searchSQL = $search !== '' ? "AND (u.full_name LIKE ? OR u.email LIKE ?)" : "";
$params = [$schoolId];
if ($search !== '') {
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT u.user_id, u.full_name, u.email,
           (SELECT COUNT(*) FROM teacher_indicator_assignments t 
            WHERE t.teacher_id = u.user_id) as assigned_count
    FROM users u
    WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
    $searchSQL
    ORDER BY u.full_name ASC
");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$pageTitle = 'Assign Indicators';
$activePage = 'assign_indicators.php';
include __DIR__ . '/../includes/header.php';

// Group TEACHER_INDICATOR_CODES by dimension
$groupedIndicators = [];
foreach (SBM_INDICATORS as $ind) {
    if (in_array($ind['code'], TEACHER_INDICATOR_CODES)) {
        $dimName = SBM_DIMENSIONS[$ind['dim']]['name'] ?? "Dimension {$ind['dim']}";
        $groupedIndicators[$ind['dim']]['name'] = $dimName;
        $groupedIndicators[$ind['dim']]['indicators'][] = $ind;
    }
}
?>

<style>
    .toolbar {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
        position: relative;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--n400);
        width: 16px;
        height: 16px;
    }

    .search-input {
        width: 100%;
        padding: 10px 14px 10px 36px;
        border: 1px solid var(--n200);
        border-radius: 8px;
        font-size: 13.5px;
    }

    .teacher-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--white);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .teacher-table th {
        background: var(--n50);
        font-size: 12px;
        font-weight: 700;
        color: var(--n500);
        text-align: left;
        padding: 12px 16px;
        border-bottom: 1px solid var(--n200);
        text-transform: uppercase;
    }

    .teacher-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--n100);
        font-size: 13.5px;
        color: var(--n800);
    }

    .teacher-table tr:hover {
        background: var(--n50);
    }

    .teacher-name {
        font-weight: 600;
    }

    .teacher-email {
        font-size: 12px;
        color: var(--n500);
        margin-top: 3px;
    }

    /* Modal specific overrides */
    .indicator-group {
        margin-bottom: 16px;
        border: 1px solid var(--n200);
        border-radius: 8px;
        overflow: hidden;
    }

    .indicator-group-head {
        background: var(--n50);
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 700;
        color: var(--n700);
        border-bottom: 1px solid var(--n200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .indicator-group-body {
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 250px;
        overflow-y: auto;
    }

    .check-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        cursor: pointer;
    }

    .check-item input {
        margin-top: 3px;
    }

    .check-item div {
        flex: 1;
        line-height: 1.4;
    }

    .check-badge {
        flex-shrink: 0;
        font-weight: 700;
        color: var(--blue);
    }

    /* Bulk modal */
    .bulk-user-list {
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid var(--n200);
        border-radius: 8px;
        background: #fff;
    }

    .bulk-user-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        border-bottom: 1px solid var(--n100);
        font-size: 13px;
        cursor: pointer;
    }

    .bulk-user-item:last-child { border-bottom: none; }
    .bulk-user-item:hover { background: var(--n50); }

    .bulk-user-item input[type=checkbox] { accent-color: var(--brand-600); }

    .bulk-search-input {
        width: 100%;
        padding: 9px 14px;
        border: 1px solid var(--n200);
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .bulk-role-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .bulk-role-tab {
        padding: 6px 14px;
        border-radius: 20px;
        border: 1px solid var(--n200);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        background: #fff;
        color: var(--n600);
        transition: all .15s;
    }

    .bulk-role-tab.active {
        background: var(--brand-600);
        color: #fff;
        border-color: var(--brand-600);
    }

    .selected-count-badge {
        display: inline-block;
        background: var(--brand-100);
        color: var(--brand-700);
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 6px;
    }
</style>

<div class="page-head">
    <div class="page-head-text">
        <h2>Assign Indicators</h2>
        <p>Assign specific SBM checklist indicators for teachers, school heads, or external stakeholders.</p>
    </div>
    <div class="page-head-actions">
        <button class="btn btn-primary" onclick="openBulkModal()">
            <?= svgIcon('users') ?> Bulk Assign Rules
        </button>
    </div>
</div>

<div class="card" style="padding:20px;">
    <div class="toolbar">
        <form class="search-box" method="GET">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" name="q" class="search-input" placeholder="Search teacher by name or email..."
                value="<?= e($search) ?>" onchange="this.form.submit()">
        </form>
        <?php if ($search !== ''): ?>
            <a href="assign_indicators.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </div>

    <table class="teacher-table">
        <thead>
            <tr>
                <th>Teacher Name</th>
                <th>Indicators Assigned</th>
                <th style="text-align:right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($teachers)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;padding:30px;color:var(--n500);">No active teachers found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($teachers as $t): ?>
                    <tr>
                        <td>
                            <div class="teacher-name"><?= e($t['full_name']) ?></div>
                            <div class="teacher-email"><?= e($t['email']) ?></div>
                        </td>
                        <td>
                            <?php if ($t['assigned_count'] > 0): ?>
                                <span class="pill pill-success" style="font-size:12px;"><?= $t['assigned_count'] ?> of
                                    <?= count(TEACHER_INDICATOR_CODES) ?></span>
                            <?php else: ?>
                                <span class="pill pill-draft" style="font-size:12px;">Not assigned (Defaults to all)</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <button class="btn btn-secondary btn-sm"
                                onclick="openAssignModal(<?= $t['user_id'] ?>, '<?= e(addslashes($t['full_name'])) ?>')">
                                <?= svgIcon('edit') ?> Assign Rules
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Assign Modal -->
<div id="assignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
           z-index:9999;align-items:center;justify-content:center;
           overflow:hidden;padding:20px;">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:700px;
                max-height:calc(100vh - 40px);display:flex;flex-direction:column;
                box-shadow:var(--shadow-lg);overflow:hidden;">
        <div
            style="padding:20px;border-bottom:1px solid var(--n200);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:18px;font-weight:700;">Assign Indicators: <span id="modalTeacherName"
                    style="color:var(--brand-600);"></span></h3>
            <button onclick="closeModal()"
                style="background:none;border:none;cursor:pointer;color:var(--n500);"><?= svgIcon('x') ?></button>
        </div>

        <div style="padding:20px;overflow-y:auto;flex:1;background:var(--n50);">
            <form id="assignForm" onsubmit="saveAssignments(event)">
                <input type="hidden" id="modalTeacherId" name="teacher_id">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="save_assignments">

                <div style="margin-bottom:16px;font-size:13px;color:var(--n600);">
                    Select the specific indicators you want this teacher to evaluate. If you leave all unchecked, the
                    teacher will default to evaluating ALL teacher-applicable indicators.
                </div>

                <div style="display:flex;gap:10px;margin-bottom:16px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAll(true)">Select All</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAll(false)">Deselect
                        All</button>
                </div>

                <?php foreach ($groupedIndicators as $dimId => $dim): ?>
                    <div class="indicator-group" id="dimGroup<?= $dimId ?>">
                        <div class="indicator-group-head">
                            <span>Dimension <?= $dimId ?>: <?= e($dim['name']) ?></span>
                            <span style="font-size:12px;font-weight:600;color:var(--brand-600);cursor:pointer;"
                                onclick="toggleDim(<?= $dimId ?>, true)">All</span>
                        </div>
                        <div class="indicator-group-body">
                            <?php foreach ($dim['indicators'] as $ind): ?>
                                <label class="check-item">
                                    <input type="checkbox" name="indicators[]" value="<?= $ind['code'] ?>"
                                        class="ind-chk dim-chk-<?= $dimId ?>">
                                    <div class="check-badge">[<?= $ind['code'] ?>]</div>
                                    <div style="flex:1;"><?= e($ind['text']) ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </form>
        </div>

        <div
            style="padding:16px 20px;border-top:1px solid var(--n200);display:flex;justify-content:flex-end;gap:10px;background:#fff;border-radius:0 0 12px 12px;">
            <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
            <button type="submit" form="assignForm" class="btn btn-primary" id="saveBtn">Save Assignments</button>
        </div>
    </div>
</div>

<!-- Bulk Assign Modal -->
<div id="bulkModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
           z-index:9999;align-items:center;justify-content:center;overflow:hidden;padding:20px;">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:820px;
                max-height:calc(100vh - 40px);display:flex;flex-direction:column;
                box-shadow:var(--shadow-lg);overflow:hidden;">

        <!-- Header -->
        <div style="padding:20px;border-bottom:1px solid var(--n200);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:18px;font-weight:700;">Bulk Assign Indicators</h3>
            <button onclick="closeBulkModal()" style="background:none;border:none;cursor:pointer;color:var(--n500);"><?= svgIcon('x') ?></button>
        </div>

        <div style="padding:20px;overflow-y:auto;flex:1;background:var(--n50);display:flex;flex-direction:column;gap:20px;">

            <!-- Step 1: Role Filter -->
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--n700);margin-bottom:8px;">
                    Step 1 — Select Role to Assign Indicators For
                </div>
                <div class="bulk-role-tabs">
                    <button class="bulk-role-tab active" data-role="teacher"               onclick="switchBulkRole('teacher', this)">Teachers</button>
                    <button class="bulk-role-tab"        data-role="school_head"            onclick="switchBulkRole('school_head', this)">School Heads</button>
                    <button class="bulk-role-tab"        data-role="external_stakeholder"   onclick="switchBulkRole('external_stakeholder', this)">External Stakeholders</button>
                </div>
            </div>

            <!-- Step 2: Select Users -->
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--n700);margin-bottom:8px;">
                    Step 2 — Select Accounts
                    <span class="selected-count-badge" id="bulkSelectedCount">0 selected</span>
                </div>
                <input type="text" class="bulk-search-input" id="bulkUserSearch"
                       placeholder="Search by name or email..." oninput="filterBulkUsers()">
                <div class="bulk-user-list" id="bulkUserList">
                    <div style="padding:16px;text-align:center;color:var(--n500);font-size:13px;">Loading users...</div>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllBulkUsers(true)">Select All</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllBulkUsers(false)">Deselect All</button>
                </div>
            </div>

            <!-- Step 3: Select Indicators -->
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--n700);margin-bottom:8px;">
                    Step 3 — Choose Indicators to Assign
                    <span style="font-size:12px;font-weight:400;color:var(--n500);">(Leave all unchecked to default to all applicable)</span>
                </div>
                <div style="display:flex;gap:8px;margin-bottom:10px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllBulkIndicators(true)">Select All</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllBulkIndicators(false)">Deselect All</button>
                </div>
                <div id="bulkIndicatorGroups">
                    <!-- dynamically rendered by JS based on role -->
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div style="padding:16px 20px;border-top:1px solid var(--n200);display:flex;justify-content:flex-end;
                    gap:10px;background:#fff;border-radius:0 0 12px 12px;">
            <button type="button" class="btn btn-ghost" onclick="closeBulkModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="bulkSaveBtn" onclick="saveBulkAssignments()">
                Save Bulk Assignments
            </button>
        </div>
    </div>
</div>

<script>
    // Auto close modal on escape or click outside
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    document.getElementById('assignModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

    function closeModal() {
        document.getElementById('assignModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function toggleAll(check) {
        document.querySelectorAll('.ind-chk').forEach(el => el.checked = check);
    }

    function toggleDim(dimId) {
        const boxes = document.querySelectorAll('.dim-chk-' + dimId);
        // If all are checked, uncheck them. Otherwise, check all.
        const allChecked = Array.from(boxes).every(el => el.checked);
        boxes.forEach(el => el.checked = !allChecked);
    }

    async function openAssignModal(teacherId, teacherName) {
        document.getElementById('modalTeacherId').value = teacherId;
        document.getElementById('modalTeacherName').textContent = teacherName;

        // reset checks
        toggleAll(false);

        // fetch current assignments
        try {
            const formData = new FormData();
            formData.append('action', 'get_assignments');
            formData.append('teacher_id', teacherId);
            formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

            const res = await fetch(location.href, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.ok && data.assigned) {
                data.assigned.forEach(code => {
                    const chk = document.querySelector(`input.ind-chk[value="${code}"]`);
                    if (chk) chk.checked = true;
                });
            }
        } catch (e) {
            console.error("Failed fetching assignments", e);
        }

        document.getElementById('assignModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    async function saveAssignments(e) {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const formData = new FormData(e.target);
            const res = await fetch(location.href, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.ok) {
                showToast('Success', data.msg, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Error', data.msg || 'Failed to save', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Assignments';
            }
        } catch (err) {
            showToast('Error', 'Network error', 'error');
            btn.disabled = false;
            btn.textContent = 'Save Assignments';
        }
    }
// ── BULK MODAL ────────────────────────────────────────────────────────────

    // All indicators grouped by dimension, per role — built from PHP data
    const ALL_GROUPED = <?= json_encode($groupedIndicators) ?>;

    // Role → valid indicator codes (from PHP constants)
    const ROLE_CODES = {
        teacher:              <?= json_encode(TEACHER_INDICATOR_CODES) ?>,
        school_head:          <?= json_encode(SH_RATEABLE_CODES) ?>,
        external_stakeholder: <?= json_encode(STAKEHOLDER_INDICATOR_CODES) ?>,
    };

    let bulkCurrentRole = 'teacher';
    let bulkAllUsers    = [];   // full list for current role

    function openBulkModal() {
        document.getElementById('bulkModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        switchBulkRole('teacher', document.querySelector('.bulk-role-tab[data-role="teacher"]'));
    }

    function closeBulkModal() {
        document.getElementById('bulkModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.getElementById('bulkModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeBulkModal();
    });

    async function switchBulkRole(role, tabEl) {
        // Update active tab
        document.querySelectorAll('.bulk-role-tab').forEach(t => t.classList.remove('active'));
        tabEl.classList.add('active');
        bulkCurrentRole = role;

        // Reset user search
        document.getElementById('bulkUserSearch').value = '';

        // Render indicator groups for this role
        renderBulkIndicators(role);

        // Fetch users for this role
        const listEl = document.getElementById('bulkUserList');
        listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--n500);font-size:13px;">Loading...</div>';

        try {
            const fd = new FormData();
            fd.append('action', 'get_users_by_role');
            fd.append('role_filter', role);
            fd.append('csrf_token', document.querySelector('[name=csrf_token]').value);
            const res  = await fetch(location.href, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                bulkAllUsers = data.users;
                renderBulkUserList(data.users);
            } else {
                listEl.innerHTML = `<div style="padding:16px;color:var(--red);font-size:13px;">${data.msg}</div>`;
            }
        } catch (err) {
            listEl.innerHTML = '<div style="padding:16px;color:var(--red);font-size:13px;">Failed to load users.</div>';
        }
        updateBulkSelectedCount();
    }

    function renderBulkUserList(users) {
        const listEl = document.getElementById('bulkUserList');
        if (!users.length) {
            listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--n500);font-size:13px;">No active users found.</div>';
            return;
        }
        listEl.innerHTML = users.map(u => `
            <label class="bulk-user-item">
                <input type="checkbox" class="bulk-user-chk" value="${u.user_id}" onchange="updateBulkSelectedCount()">
                <div>
                    <div style="font-weight:600;">${escHtml(u.full_name)}</div>
                    <div style="font-size:11.5px;color:var(--n500);">${escHtml(u.email)}</div>
                </div>
            </label>
        `).join('');
        updateBulkSelectedCount();
    }

    function filterBulkUsers() {
        const q = document.getElementById('bulkUserSearch').value.toLowerCase();
        const filtered = q
            ? bulkAllUsers.filter(u =>
                u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
            : bulkAllUsers;
        renderBulkUserList(filtered);
    }

    function toggleAllBulkUsers(check) {
        document.querySelectorAll('.bulk-user-chk').forEach(el => el.checked = check);
        updateBulkSelectedCount();
    }

    function updateBulkSelectedCount() {
        const n = document.querySelectorAll('.bulk-user-chk:checked').length;
        document.getElementById('bulkSelectedCount').textContent = n + ' selected';
    }

    function renderBulkIndicators(role) {
        const validCodes = ROLE_CODES[role] || [];
        const container  = document.getElementById('bulkIndicatorGroups');

        let html = '';
        for (const [dimId, dim] of Object.entries(ALL_GROUPED)) {
            // Only show indicators applicable to this role
            const applicable = dim.indicators.filter(ind => validCodes.includes(ind.code));
            if (!applicable.length) continue;

            html += `
                <div class="indicator-group" style="margin-bottom:12px;">
                    <div class="indicator-group-head">
                        <span>Dimension ${dimId}: ${escHtml(dim.name)}</span>
                        <span style="font-size:12px;font-weight:600;color:var(--brand-600);cursor:pointer;"
                              onclick="toggleBulkDim(${dimId})">Toggle All</span>
                    </div>
                    <div class="indicator-group-body">
                        ${applicable.map(ind => `
                            <label class="check-item">
                                <input type="checkbox" class="bulk-ind-chk bulk-dim-chk-${dimId}" value="${ind.code}">
                                <div class="check-badge">[${ind.code}]</div>
                                <div style="flex:1;">${escHtml(ind.text)}</div>
                            </label>
                        `).join('')}
                    </div>
                </div>`;
        }
        container.innerHTML = html || '<div style="color:var(--n500);font-size:13px;">No applicable indicators for this role.</div>';
    }

    function toggleAllBulkIndicators(check) {
        document.querySelectorAll('.bulk-ind-chk').forEach(el => el.checked = check);
    }

    function toggleBulkDim(dimId) {
        const boxes = document.querySelectorAll('.bulk-dim-chk-' + dimId);
        const allChecked = Array.from(boxes).every(el => el.checked);
        boxes.forEach(el => el.checked = !allChecked);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    async function saveBulkAssignments() {
        const selectedUsers = Array.from(document.querySelectorAll('.bulk-user-chk:checked')).map(el => el.value);
        if (!selectedUsers.length) {
            showToast('Warning', 'Please select at least one user.', 'warning');
            return;
        }

        const selectedIndicators = Array.from(document.querySelectorAll('.bulk-ind-chk:checked')).map(el => el.value);

        const btn = document.getElementById('bulkSaveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const fd = new FormData();
            fd.append('action', 'bulk_save_assignments');
            fd.append('role_filter', bulkCurrentRole);
            fd.append('csrf_token', document.querySelector('[name=csrf_token]').value);
            selectedUsers.forEach(id  => fd.append('user_ids[]', id));
            selectedIndicators.forEach(c => fd.append('indicators[]', c));

            const res  = await fetch(location.href, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.ok) {
                showToast('Success', data.msg, 'success');
                closeBulkModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Error', data.msg || 'Failed to save', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Bulk Assignments';
            }
        } catch (err) {
            showToast('Error', 'Network error', 'error');
            btn.disabled = false;
            btn.textContent = 'Save Bulk Assignments';
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>