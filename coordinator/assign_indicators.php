<?php
// ============================================================
// coordinator/assign_indicators.php
// SBM Coordinator feature to assign specific SBM indicators
// to individual teachers.
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('sbm_coordinator');
$db = getDB();

$schoolId = SCHOOL_ID;

// ── AJAX HANDLERS ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_start();
    ob_clean();
    header('Content-Type: application/json');
    verifyCsrf();

    try {
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
</style>

<div class="page-head">
    <div class="page-head-text">
        <h2>Assign Indicators</h2>
        <p>Assign specific SBM checklist indicators for each teacher to evaluate.</p>
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>