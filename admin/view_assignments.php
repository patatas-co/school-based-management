<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/sbm_indicators.php';
require_once __DIR__.'/../includes/auth.php';

requireRole('admin');
$db = getDB();

$schoolId = SCHOOL_ID;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_start();
    ob_clean();
    header('Content-Type: application/json');
    verifyCsrf();
    
    try {
        if ($_POST['action'] === 'get_assignments') {
            $teacherId = (int)$_POST['teacher_id'];
            
            $stmt = $db->prepare("
                SELECT tia.indicator_code, tia.assigned_by, tia.created_at, 
                       u.full_name as assigned_by_name
                FROM teacher_indicator_assignments tia
                LEFT JOIN users u ON tia.assigned_by = u.user_id
                WHERE tia.teacher_id = ?
            ");
            $stmt->execute([$teacherId]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['ok' => true, 'assignments' => $assignments]);
            exit;
        }

        if ($_POST['action'] === 'override_assignments') {
            $teacherId = (int)$_POST['teacher_id'];
            $indicators = isset($_POST['indicators']) && is_array($_POST['indicators']) ? $_POST['indicators'] : [];
            $reason = trim($_POST['reason'] ?? '');
            
            if (empty($reason)) {
                throw new Exception("Reason for override is required.");
            }
            
            $verify = $db->prepare("SELECT user_id FROM users WHERE user_id=? AND school_id=? AND role='teacher'");
            $verify->execute([$teacherId, $schoolId]);
            if (!$verify->fetchColumn()) {
                throw new Exception("Invalid teacher selected.");
            }
            
            $db->beginTransaction();
            
            $prevStmt = $db->prepare("
                SELECT indicator_code, assigned_by 
                FROM teacher_indicator_assignments 
                WHERE teacher_id = ?
            ");
            $prevStmt->execute([$teacherId]);
            $previousAssignments = $prevStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $db->prepare("DELETE FROM teacher_indicator_assignments WHERE teacher_id = ?")->execute([$teacherId]);
            
            if (!empty($indicators)) {
                $insert = $db->prepare("INSERT INTO teacher_indicator_assignments (teacher_id, indicator_code, assigned_by) VALUES (?, ?, ?)");
                foreach ($indicators as $code) {
                    if (in_array($code, TEACHER_INDICATOR_CODES)) {
                        $insert->execute([$teacherId, $code, $_SESSION['user_id']]);
                    }
                }
            }
            
            $logDetails = sprintf(
                "Admin override for teacher ID %s. Previous: %s. New: %s. Reason: %s",
                $teacherId,
                json_encode(array_column($previousAssignments, 'indicator_code')),
                json_encode($indicators),
                $reason
            );
            logActivity('override_assignments', 'admin', $logDetails);
            
            $db->commit();
            
            echo json_encode(['ok' => true, 'msg' => 'Assignments overridden successfully!']);
            exit;
        }

    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

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
            WHERE t.teacher_id = u.user_id) as assigned_count,
           (SELECT GROUP_CONCAT(tia.indicator_code ORDER BY tia.indicator_code) 
            FROM teacher_indicator_assignments tia 
            WHERE tia.teacher_id = u.user_id) as assigned_indicators
    FROM users u
    WHERE u.school_id = ? AND u.role = 'teacher' AND u.status = 'active'
    $searchSQL
    ORDER BY u.full_name ASC
");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$pageTitle = 'Indicator Assignments';
$activePage = 'view_assignments.php';
include __DIR__.'/../includes/header.php';

$indicatorGroups = [];
foreach (TEACHER_INDICATOR_CODES as $code) {
    $dimNo = substr($code, 0, 1);
    $indicatorGroups[$dimNo][] = $code;
}
ksort($indicatorGroups);
?>

<style>
.assignment-header {
    background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
    color: white;
    padding: 20px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
}
.assignment-header h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 800;
}
.assignment-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.teacher-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.teacher-card {
    background: white;
    border: 1px solid var(--n-200);
    border-radius: 12px;
    padding: 16px;
    box-shadow: var(--shadow-xs);
    transition: all 0.2s ease;
}
.teacher-card:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.teacher-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}
.teacher-info h3 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--n-900);
}
.teacher-info p {
    margin: 0;
    font-size: 13px;
    color: var(--n-500);
}

.assignment-count {
    background: var(--purple-bg);
    color: var(--purple);
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.current-assignments {
    background: var(--n-50);
    border: 1px solid var(--n-200);
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 12px;
    min-height: 32px;
}
.current-assignments:empty::before {
    content: "No indicators assigned";
    color: var(--n-400);
    font-style: italic;
    font-size: 13px;
}

.assignment-indicator {
    display: inline-block;
    background: var(--purple);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin: 2px;
}

.btn-manage {
    background: var(--purple);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-manage:hover {
    background: #6D28D9;
    transform: translateY(-1px);
}

.indicator-group {
    margin-bottom: 16px;
}
.indicator-group-title {
    font-weight: 600;
    color: var(--n-700);
    margin-bottom: 8px;
    font-size: 13px;
}
.indicator-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 6px;
}
.indicator-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}
.indicator-checkbox input[type="checkbox"] {
    margin: 0;
}
</style>

<div class="assignment-header">
    <h2>📋 Indicator Assignments</h2>
    <p>View and manage teacher indicator assignments. Admin can override coordinator assignments when needed.</p>
</div>

<div class="teacher-grid">
    <?php foreach ($teachers as $teacher): ?>
    <div class="teacher-card">
        <div class="teacher-card-header">
            <div class="teacher-info">
                <h3><?= e($teacher['full_name']) ?></h3>
                <p><?= e($teacher['email']) ?></p>
            </div>
            <div class="assignment-count">
                <?= $teacher['assigned_count'] ?> indicators
            </div>
        </div>
        
        <div class="current-assignments">
            <?php 
            if ($teacher['assigned_indicators']):
                $indicators = explode(',', $teacher['assigned_indicators']);
                foreach ($indicators as $indicator):
                    echo '<span class="assignment-indicator">' . e($indicator) . '</span>';
                endforeach;
            endif;
            ?>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <button class="btn-manage" onclick="showManageModal(<?= $teacher['user_id'] ?>, '<?= e($teacher['full_name']) ?>')">
                ⚙️ Manage Assignments
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($teachers)): ?>
<div class="card">
    <div class="card-body" style="text-align: center; padding: 40px;">
        <h3 style="margin: 0 0 8px 0; color: var(--n-600);">No Teachers Found</h3>
        <p style="margin: 0; color: var(--n-400); font-size: 14px;">No active teachers found in the system.</p>
    </div>
</div>
<?php endif; ?>

<div class="overlay" id="mManage">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-head">
            <span class="modal-title">Manage Assignments</span>
            <button class="modal-close" onclick="closeModal('mManage')"><?= svgIcon('x') ?></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="manage_teacher_id">
            <div class="fg">
                <label>Teacher</label>
                <input type="text" id="manage_teacher_name" class="fc" readonly style="background: var(--n-50);">
            </div>
            
            <div class="fg">
                <label>Reason for Override <span style="color: var(--red);">*</span></label>
                <textarea id="manage_reason" class="fc" rows="3" placeholder="Please explain any changes..." required></textarea>
            </div>
            
            <div class="fg">
                <label>Select Indicators to Assign</label>
                <?php foreach ($indicatorGroups as $dimNo => $indicators): ?>
                <div class="indicator-group">
                    <div class="indicator-group-title">Dimension <?= $dimNo ?> Indicators</div>
                    <div class="indicator-checkboxes">
                        <?php foreach ($indicators as $indicator): ?>
                        <div class="indicator-checkbox">
                            <input type="checkbox" id="ind_<?= $indicator ?>" value="<?= $indicator ?>">
                            <label for="ind_<?= $indicator ?>"><?= $indicator ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-secondary" onclick="closeModal('mManage')">Cancel</button>
            <button class="btn btn-primary" onclick="saveAssignments()">Save Changes</button>
        </div>
    </div>
</div>

<script>
function showManageModal(teacherId, teacherName) {
    $v('manage_teacher_id', teacherId);
    $v('manage_teacher_name', teacherName);
    $v('manage_reason', '');
    
    document.querySelectorAll('#mManage input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    loadCurrentAssignments(teacherId);
    
    openModal('mManage');
}

function loadCurrentAssignments(teacherId) {
    apiPost('view_assignments.php', {action: 'get_assignments', teacher_id: teacherId}).then(r => {
        if (r.ok && r.assignments) {
            r.assignments.forEach(a => {
                const cb = document.getElementById('ind_' + a.indicator_code);
                if (cb) cb.checked = true;
            });
        }
    }).catch(err => {
        console.error('Failed to load assignments:', err);
    });
}

function saveAssignments() {
    const teacherId = $('manage_teacher_id');
    const reason = $('manage_reason');
    
    if (!reason.trim()) {
        toast('Please provide a reason for the changes.', 'warning');
        return;
    }
    
    const indicators = [];
    document.querySelectorAll('#mManage input[type="checkbox"]:checked').forEach(cb => {
        indicators.push(cb.value);
    });
    
    apiPost('view_assignments.php', {
        action: 'override_assignments',
        teacher_id: teacherId,
        indicators: indicators,
        reason: reason
    }).then(r => {
        toast(r.msg, r.ok ? 'ok' : 'err');
        if (r.ok) {
            closeModal('mManage');
            setTimeout(() => location.reload(), 1000);
        }
    });
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
