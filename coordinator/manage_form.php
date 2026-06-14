<?php
ob_start();
// coordinator/manage_form.php — SBM Form Version Manager
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('sbm_coordinator');
$db = getDB();

// ── AJAX HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ── Get full form for a version ──────────────────────────
    if ($action === 'get_version_form') {
        $vId = (int)($_POST['version_id'] ?? 0);
        $dims = $db->prepare("
            SELECT * FROM sbm_dimensions WHERE form_version_id=? ORDER BY dimension_no
        ");
        $dims->execute([$vId]);
        $dims = $dims->fetchAll();
        foreach ($dims as &$d) {
            $inds = $db->prepare("
                SELECT * FROM sbm_indicators WHERE dimension_id=? AND form_version_id=? ORDER BY sort_order
            ");
            $inds->execute([$d['dimension_id'], $vId]);
            $d['indicators'] = $inds->fetchAll();
        }
        echo json_encode(['ok' => true, 'dimensions' => $dims]);
        exit;
    }

    // ── Publish a new version ────────────────────────────────
    if ($action === 'publish_version') {
        $payload = json_decode($_POST['payload'] ?? '{}', true);
        $label   = trim($_POST['label'] ?? '');
        $uid     = $_SESSION['user_id'];

        if (empty($payload['dimensions'])) {
            echo json_encode(['ok' => false, 'msg' => 'Form cannot be empty.']);
            exit;
        }

        // Get next version number
        $maxV = $db->query("SELECT COALESCE(MAX(version_number),0) FROM form_versions")->fetchColumn();
        $newV = (int)$maxV + 1;

        $db->beginTransaction();
        try {
            // Deactivate current active version
            $db->exec("UPDATE form_versions SET is_active=0");

            // Insert new version
            $db->prepare("
                INSERT INTO form_versions (version_number, label, is_active, created_by, published_at)
                VALUES (?,?,1,?,NOW())
            ")->execute([$newV, $label ?: "Version $newV", $uid]);
            $versionId = $db->lastInsertId();

            foreach ($payload['dimensions'] as $dIdx => $dim) {
                // Insert dimension
                $db->prepare("
                    INSERT INTO sbm_dimensions
                        (dimension_no, dimension_name, color_hex, icon, indicator_count, form_version_id)
                    VALUES (?,?,?,?,?,?)
                ")->execute([
                    (int)$dim['dimension_no'],
                    trim($dim['dimension_name']),
                    $dim['color_hex'] ?? '#16A34A',
                    $dim['icon']      ?? 'layers',
                    count($dim['indicators'] ?? []),
                    $versionId
                ]);
                $dimId = $db->lastInsertId();

                foreach (($dim['indicators'] ?? []) as $iIdx => $ind) {
                    $code = trim($dim['dimension_no'] . '.' . ($iIdx + 1));
                    $db->prepare("
                        INSERT INTO sbm_indicators
                            (dimension_id, indicator_code, indicator_text, mov_guide, sort_order, is_active, form_version_id)
                        VALUES (?,?,?,?,?,1,?)
                    ")->execute([
                        $dimId,
                        $code,
                        trim($ind['indicator_text']),
                        trim($ind['mov_guide'] ?? ''),
                        $iIdx + 1,
                        $versionId
                    ]);
                }
            }

            $db->commit();
            logActivity('publish_form_version', 'manage_form', "Published form version $newV (ID: $versionId)");
            echo json_encode(['ok' => true, 'msg' => "Version $newV published successfully.", 'version_id' => $versionId]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── Revert active to a previous version ──────────────────
    if ($action === 'revert_version') {
        $vId = (int)($_POST['version_id'] ?? 0);
        $db->exec("UPDATE form_versions SET is_active=0");
        $db->prepare("UPDATE form_versions SET is_active=1 WHERE version_id=?")->execute([$vId]);
        logActivity('revert_form_version', 'manage_form', "Reverted active form to version ID $vId");
        echo json_encode(['ok' => true, 'msg' => 'Active version updated.']);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
    exit;
}

// ── PAGE DATA ─────────────────────────────────────────────────
// All versions
$versions = $db->query("
    SELECT fv.*, u.full_name creator_name
    FROM form_versions fv
    LEFT JOIN users u ON fv.created_by = u.user_id
    ORDER BY fv.version_number DESC
")->fetchAll();

// Active version details
$activeVersion = null;
$activeDimensions = [];
foreach ($versions as $v) {
    if ($v['is_active']) { $activeVersion = $v; break; }
}
if ($activeVersion) {
    $dims = $db->prepare("SELECT * FROM sbm_dimensions WHERE form_version_id=? ORDER BY dimension_no");
    $dims->execute([$activeVersion['version_id']]);
    $activeDimensions = $dims->fetchAll();
    foreach ($activeDimensions as &$d) {
        $inds = $db->prepare("SELECT * FROM sbm_indicators WHERE dimension_id=? AND form_version_id=? AND is_active=1 ORDER BY sort_order");
        $inds->execute([$d['dimension_id'], $activeVersion['version_id']]);
        $d['indicators'] = $inds->fetchAll();
    }
}

// Check if any active cycle exists — warn coordinator
$activeCycle = $db->query("SELECT cycle_id, status FROM sbm_cycles WHERE status IN('in_progress','submitted','consolidating') LIMIT 1")->fetch();

$pageTitle = 'Manage Form';
$activePage = 'manage_form.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── PAGE LAYOUT ── */
.mf-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
    align-items: start;
}

/* ── SIDEBAR ── */
.mf-sidebar {
    position: sticky;
    top: 84px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.mf-version-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 420px;
    overflow-y: auto;
    padding: 4px 2px;
}

.mf-version-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 13px;
    border-radius: 9px;
    border: 1.5px solid var(--n-200);
    background: #fff;
    cursor: pointer;
    transition: all 140ms;
}
.mf-version-item:hover { border-color: var(--n-300); background: var(--n-50); }
.mf-version-item.active-v { border-color: var(--brand-400); background: var(--brand-50); }
.mf-version-item.selected-v { border-color: #2563EB; background: #EFF6FF; box-shadow: 0 0 0 3px rgba(37,99,235,.08); }

.mf-v-badge {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--n-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-display);
    font-size: 11px;
    font-weight: 800;
    color: var(--n-600);
    flex-shrink: 0;
}
.mf-version-item.active-v .mf-v-badge { background: var(--brand-100); color: var(--brand-700); }
.mf-version-item.selected-v .mf-v-badge { background: #DBEAFE; color: #2563EB; }

.mf-v-info { flex: 1; min-width: 0; }
.mf-v-label { font-size: 12.5px; font-weight: 600; color: var(--n-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mf-v-meta  { font-size: 11px; color: var(--n-400); margin-top: 1px; }

/* ── EDITOR AREA ── */
.mf-editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 10px;
}

.mf-mode-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
}
.mf-mode-view  { background: var(--n-100); color: var(--n-600); border: 1px solid var(--n-200); }
.mf-mode-edit  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }

/* ── DIMENSION CARD ── */
.mf-dim-card {
    background: #fff;
    border: 1.5px solid var(--n-200);
    border-radius: 12px;
    margin-bottom: 16px;
    overflow: visible;
    transition: border-color 140ms, box-shadow 140ms;
}
.mf-dim-card:hover { border-color: var(--n-300); }
.mf-dim-card.editing { border-color: #D97706; box-shadow: 0 0 0 3px rgba(217,119,6,.08); }

.mf-dim-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    cursor: pointer;
    user-select: none;
    border-radius: 11px;
    transition: background 120ms;
}
.mf-dim-head:hover { background: var(--n-50); }

.mf-dim-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mf-dim-icon svg {
    width: 16px;
    height: 16px;
    stroke: #fff;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.mf-dim-number {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-display);
    font-size: 14px;
    font-weight: 800;
    flex-shrink: 0;
    color: var(--n-500);
    background: var(--n-100) !important;
}
.mf-dim-icon svg {
    stroke: var(--n-500) !important;
}

.mf-dim-title {
    flex: 1;
    font-size: 14px;
    font-weight: 700;
    color: var(--n-900);
}

.mf-dim-count {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--n-400);
    flex-shrink: 0;
}

.mf-dim-body {
    padding: 0 18px 14px;
    display: none;
}
.mf-dim-body.open { display: block; }

/* ── INDICATOR ROW ── */
.mf-ind-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--n-100);
}
.mf-ind-row:last-child { border-bottom: none; }

.mf-ind-code {
    flex-shrink: 0;
    width: 36px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--n-400);
    padding-top: 2px;
}

.mf-ind-content { flex: 1; min-width: 0; }
.mf-ind-text { font-size: 13px; color: var(--n-800); line-height: 1.5; margin-bottom: 3px; }
.mf-ind-mov  { font-size: 11.5px; color: var(--n-400); line-height: 1.4; }

.mf-ind-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 120ms;
}
.mf-ind-row:hover .mf-ind-actions { opacity: 1; }

/* ── EDIT FIELDS ── */
.mf-edit-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--n-100);
}
.mf-edit-row:last-child { border-bottom: none; }

.mf-edit-num {
    width: 28px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--n-400);
    padding-top: 9px;
    flex-shrink: 0;
}

.mf-edit-fields { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }

.mf-edit-drag {
    cursor: grab;
    color: var(--n-300);
    padding-top: 9px;
    flex-shrink: 0;
}
.mf-edit-drag:active { cursor: grabbing; }
.mf-edit-drag svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; }

/* ── ADD BUTTONS ── */
.mf-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1.5px dashed var(--n-300);
    border-radius: 7px;
    background: transparent;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-500);
    cursor: pointer;
    transition: all 140ms;
}
.mf-add-btn:hover { border-color: var(--brand-500); color: var(--brand-600); background: var(--brand-50); }
.mf-add-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; }

/* ── PUBLISH DRAWER ── */
.mf-publish-panel {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: #fff;
    border-top: 2px solid #D97706;
    padding: 16px 28px;
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    z-index: 300;
    box-shadow: 0 -8px 24px rgba(0,0,0,.10);
    flex-wrap: wrap;
}
.mf-publish-panel.open { display: flex; }

.mf-publish-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mf-publish-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #D97706;
    animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── VERSION DIFF ── */
.mf-diff-strip {
    display: flex;
    gap: 8px;
    padding: 8px 12px;
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 8px;
    font-size: 12px;
    color: #92400E;
    align-items: center;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

/* ── WARNING BANNER ── */
.mf-warning {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    background: #FEF3C7;
    border: 1px solid #FDE68A;
    border-radius: 9px;
    font-size: 13px;
    color: #92400E;
    margin-bottom: 18px;
}
.mf-warning svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; margin-top: 1px; }

@media (max-width: 900px) {
    .mf-layout { grid-template-columns: 1fr; }
    .mf-sidebar { position: static; }
}
</style>

<?php if ($activeCycle): ?>
<div class="mf-warning">
    <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <div>
        <strong>Assessment in Progress</strong> — A cycle is currently <strong><?= ucfirst(str_replace('_',' ',$activeCycle['status'])) ?></strong>.
        Publishing a new form version will only apply to <em>future</em> cycles. All submitted and in-progress data remain linked to their original version.
    </div>
</div>
<?php endif; ?>

<div class="mf-layout">

    <!-- ── LEFT: Version History ─────────────────────────── -->
    <div class="mf-sidebar">
        <div class="card">
            <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                <span class="card-title">Version History</span>
                <span style="font-size:11.5px;color:var(--n-400);"><?= count($versions) ?> version(s)</span>
            </div>
            <div class="card-body" style="padding:12px 12px 0;display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-secondary btn-sm" id="btnPreviewActive" onclick="previewVersion(<?= $activeVersion ? $activeVersion['version_id'] : 0 ?>)" style="flex:1;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Preview Active
                </button>
                <button class="btn btn-primary btn-sm" onclick="startEdit()" id="btnStartEdit" style="flex:1;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit Form
                </button>
            </div>
            <div class="card-body" style="padding:12px;">
                <div class="mf-version-list">
                    <?php foreach ($versions as $v):
                        $isCurrent = (bool)$v['is_active'];
                        $dimCount = $db->prepare("SELECT COUNT(*) FROM sbm_dimensions WHERE form_version_id=?");
                        $dimCount->execute([$v['version_id']]);
                        $dimCount = $dimCount->fetchColumn();
                        $indCount = $db->prepare("SELECT COUNT(*) FROM sbm_indicators WHERE form_version_id=?");
                        $indCount->execute([$v['version_id']]);
                        $indCount = $indCount->fetchColumn();
                    ?>
                    <div class="mf-version-item <?= $isCurrent ? 'active-v' : '' ?>"
                         id="vItem_<?= $v['version_id'] ?>"
                         onclick="selectVersion(<?= $v['version_id'] ?>, <?= $isCurrent ? 'true' : 'false' ?>)">
                        <div class="mf-v-badge">v<?= $v['version_number'] ?></div>
                        <div class="mf-v-info">
                            <div class="mf-v-label"><?= e($v['label'] ?? 'Version ' . $v['version_number']) ?></div>
                            <div class="mf-v-meta">
                                <?= $dimCount ?>D · <?= $indCount ?>I ·
                                <?= $v['published_at'] ? date('M d, Y', strtotime($v['published_at'])) : 'Draft' ?>
                                <?php if ($isCurrent): ?>
                                    <span style="color:var(--brand-600);font-weight:700;"> · Active</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$versions): ?>
                    <p style="font-size:12.5px;color:var(--n-400);padding:8px 4px;">No versions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Version actions -->
        <div class="card" id="versionActionsCard" style="display:none;">
            <div class="card-body" style="padding:14px;">
                <div style="font-size:12px;font-weight:600;color:var(--n-500);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Selected Version</div>
                <div id="vActionInfo" style="font-size:13px;color:var(--n-700);margin-bottom:12px;"></div>
                <div style="display:flex;flex-direction:column;gap:7px;">
                    <button class="btn btn-secondary btn-sm" style="justify-content:center;" onclick="previewSelectedVersion()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Preview This Version
                    </button>
                    <button class="btn btn-blue btn-sm" style="justify-content:center;" id="btnRevertToSelected" onclick="revertToSelected()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
                        Set As Active
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: Editor / Viewer ────────────────────────── -->
    <div>

        <!-- VIEW MODE -->
        <div id="viewMode">
            <div class="mf-editor-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="mf-mode-badge mf-mode-view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View Mode
                    </span>
                    <?php if ($activeVersion): ?>
                    <span style="font-size:13px;color:var(--n-500);">
                        Active: <strong style="color:var(--n-800);"><?= e($activeVersion['label'] ?? 'Version ' . $activeVersion['version_number']) ?></strong>
                        — <?= count($activeDimensions) ?> dimensions,
                        <?= array_sum(array_column($activeDimensions, 'indicator_count')) ?> indicators
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($activeDimensions):
                $dimColors = ['#2563EB','#16A34A','#7C3AED','#D97706','#DC2626','#0D9488'];
                $dimIconPaths = [
                    '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
                    '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
                    '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
                    '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                    '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                    '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
                ];
                foreach ($activeDimensions as $dIdx => $dim):
                    $bgColor  = '';
                    $iconPath = $dimIconPaths[$dIdx % count($dimIconPaths)];
                ?>
                <div class="mf-dim-card">
                    <div class="mf-dim-head" onclick="toggleDimBody(this)">
                        <div class="mf-dim-number">
                            <div class="mf-dim-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $iconPath ?></svg>
                            </div>
                        </div>
                        <div class="mf-dim-title">Dimension <?= $dim['dimension_no'] ?>: <?= e($dim['dimension_name']) ?></div>
                        <div class="mf-dim-count"><?= count($dim['indicators']) ?> indicators</div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--n-400);flex-shrink:0;transition:transform 200ms;" class="mf-chevron">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="mf-dim-body">
                        <?php foreach ($dim['indicators'] as $ind): ?>
                        <div class="mf-ind-row">
                            <div class="mf-ind-code"><?= e($ind['indicator_code']) ?></div>
                            <div class="mf-ind-content">
                                <div class="mf-ind-text"><?= e($ind['indicator_text']) ?></div>
                                <?php if ($ind['mov_guide']): ?>
                                <div class="mf-ind-mov">
                                    <span style="font-weight:600;color:var(--n-500);">MOV:</span>
                                    <?= e($ind['mov_guide']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    </div>
                    <div class="empty-title">No form version yet</div>
                    <div class="empty-sub">Click "Edit Form" to create the first version of the SBM assessment form.</div>
                    <button class="btn btn-primary" onclick="startEdit()">Create First Version</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- EDIT MODE (hidden initially) -->
        <div id="editMode" style="display:none;">
            <div class="mf-editor-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="mf-mode-badge mf-mode-edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Mode
                    </span>
                    <span style="font-size:13px;color:#92400E;">Unsaved changes — publish to save as a new version</span>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="cancelEdit()">Cancel</button>
            </div>

            <div id="editDimList"></div>

            <button class="mf-add-btn" style="width:100%;justify-content:center;margin-top:4px;" onclick="addDimension()">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Dimension
            </button>
        </div>

    </div>
</div>

<!-- ── PUBLISH BOTTOM PANEL ── -->
<div class="mf-publish-panel" id="publishPanel">
    <div class="mf-publish-info">
        <div class="mf-publish-dot"></div>
        <div>
            <div style="font-size:13.5px;font-weight:700;color:var(--n-900);">Ready to publish</div>
            <div style="font-size:12px;color:var(--n-500);">This will create a new form version. Old submissions are unaffected.</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <input type="text" class="fc" id="newVersionLabel" placeholder="Version label (e.g. DO 007 s.2025 Update)"
               style="width:280px;padding:7px 12px;font-size:13px;">
        <button class="btn btn-primary" onclick="publishNewVersion()" id="btnPublish">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            Publish New Version
        </button>
        <button class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
    </div>
</div>

<!-- ── PREVIEW MODAL ── -->
<div class="overlay" id="mPreview" onclick="if(event.target===this)closeModal('mPreview')">
    <div class="modal" style="max-width:720px;max-height:90vh;">
        <div class="modal-head">
            <span class="modal-title" id="previewTitle">Form Preview</span>
            <button class="modal-close" onclick="closeModal('mPreview')">
                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body" id="previewBody" style="padding:16px 20px;"></div>
    </div>
</div>

<script>
// ── STATE ─────────────────────────────────────────────────────
let _editData   = [];  // [{dimension_no, dimension_name, color_hex, indicators:[{indicator_text,mov_guide}]}]
let _selectedVersionId = null;
let _isEditing  = false;

const DIM_COLORS = ['#2563EB','#16A34A','#7C3AED','#D97706','#DC2626','#0D9488'];
const DIM_ICONS  = [
    // D1 Curriculum & Teaching — book-open
    '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    // D2 Learning Environment — home
    '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    // D3 Leadership — star
    '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
    // D4 Governance & Accountability — shield
    '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    // D5 Human Resources — users
    '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    // D6 Finance — bar-chart-2
    '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
];

// ── VERSION SELECT ────────────────────────────────────────────
function selectVersion(vId, isActive) {
    document.querySelectorAll('.mf-version-item').forEach(el => el.classList.remove('selected-v'));
    const el = document.getElementById('vItem_' + vId);
    if (el) el.classList.add('selected-v');
    _selectedVersionId = vId;

    const card = document.getElementById('versionActionsCard');
    const info = document.getElementById('vActionInfo');
    const btnRevert = document.getElementById('btnRevertToSelected');
    if (card) card.style.display = '';
    if (info) info.textContent = isActive ? 'This is the currently active version.' : 'This version is archived.';
    if (btnRevert) btnRevert.style.display = isActive ? 'none' : '';
}

function previewSelectedVersion() {
    if (_selectedVersionId) previewVersion(_selectedVersionId);
}

async function revertToSelected() {
    if (!_selectedVersionId) return;
    if (!confirm('Set this version as the active form? All new assessment cycles will use it.')) return;
    const r = await apiPost('manage_form.php', { action: 'revert_version', version_id: _selectedVersionId });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) setTimeout(() => location.reload(), 800);
}

// ── PREVIEW ───────────────────────────────────────────────────
async function previewVersion(vId) {
    if (!vId) { toast('No active version found.','warning'); return; }
    document.getElementById('previewTitle').textContent = 'Form Preview — Version ' + vId;
    document.getElementById('previewBody').innerHTML = '<p style="color:var(--n-400);text-align:center;padding:40px;">Loading…</p>';
    openModal('mPreview');

    const fd = new FormData();
    fd.append('action','get_version_form');
    fd.append('version_id', vId);
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    const res = await fetch('manage_form.php', { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { document.getElementById('previewBody').innerHTML = '<p style="color:var(--red);">Failed to load.</p>'; return; }

    let html = '';
    data.dimensions.forEach((d, i) => {
        const color = DIM_COLORS[i % DIM_COLORS.length];
        const previewIcon = DIM_ICONS[i % DIM_ICONS.length];
    html += `<div style="margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span style="width:32px;height:32px;border-radius:8px;background:var(--n-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--n-500)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">${previewIcon}</svg>
                </span>
                <span style="font-size:14px;font-weight:700;color:var(--n-900);">Dimension ${d.dimension_no}: ${_esc(d.dimension_name)}</span>
                <span style="font-size:11.5px;color:var(--n-400);">${d.indicators.length} indicators</span>
            </div>`;
        d.indicators.forEach(ind => {
            html += `<div style="padding:8px 0 8px 42px;border-bottom:1px solid var(--n-100);">
                <span style="font-size:11.5px;font-weight:700;color:var(--n-400);">${_esc(ind.indicator_code)}</span>
                <span style="font-size:13px;color:var(--n-800);margin-left:8px;">${_esc(ind.indicator_text)}</span>
                ${ind.mov_guide ? `<div style="font-size:11px;color:var(--n-400);margin-top:3px;padding-left:4px;">MOV: ${_esc(ind.mov_guide)}</div>` : ''}
            </div>`;
        });
        html += '</div>';
    });
    document.getElementById('previewBody').innerHTML = html || '<p style="color:var(--n-400);">Empty form.</p>';
}

function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── TOGGLE DIM BODY ───────────────────────────────────────────
function toggleDimBody(headEl) {
    const body = headEl.nextElementSibling;
    const chev = headEl.querySelector('.mf-chevron');
    const isOpen = body.classList.toggle('open');
    if (chev) chev.style.transform = isOpen ? 'rotate(180deg)' : '';
}

// ── START EDIT ────────────────────────────────────────────────
function startEdit() {
    _isEditing = true;
    // Deep-copy active form data into _editData
    _editData = <?= json_encode(array_map(function($d) {
        return [
            'dimension_no'   => (int)$d['dimension_no'],
            'dimension_name' => $d['dimension_name'],
            'color_hex'      => $d['color_hex'],
            'icon'           => $d['icon'] ?? 'layers',
            'indicators'     => array_map(fn($i) => [
                'indicator_text' => $i['indicator_text'],
                'mov_guide'      => $i['mov_guide'] ?? ''
            ], $d['indicators'])
        ];
    }, $activeDimensions), JSON_HEX_TAG) ?>;

    if (!_editData.length) {
        // Default scaffold
        _editData = [{ dimension_no:1, dimension_name:'New Dimension', color_hex:'#16A34A', icon:'layers', indicators:[{indicator_text:'',mov_guide:''}] }];
    }

    document.getElementById('viewMode').style.display = 'none';
    document.getElementById('editMode').style.display  = '';
    document.getElementById('publishPanel').classList.add('open');
    document.getElementById('btnStartEdit').style.display = 'none';
    renderEditForm();
}

function cancelEdit() {
    _isEditing = false;
    document.getElementById('viewMode').style.display = '';
    document.getElementById('editMode').style.display  = 'none';
    document.getElementById('publishPanel').classList.remove('open');
    document.getElementById('btnStartEdit').style.display = '';
}

// ── RENDER EDIT FORM ──────────────────────────────────────────
function renderEditForm() {
    const container = document.getElementById('editDimList');
    container.innerHTML = '';
    _editData.forEach((dim, dIdx) => renderDimCard(container, dim, dIdx));
}

function renderDimCard(container, dim, dIdx) {
    const color = DIM_COLORS[dIdx % DIM_COLORS.length];
    const card = document.createElement('div');
    card.className = 'mf-dim-card editing';
    card.dataset.dIdx = dIdx;

    const iconPath = DIM_ICONS[dIdx % DIM_ICONS.length];
    let indHtml = dim.indicators.map((ind, iIdx) => `
        <div class="mf-edit-row" data-iidx="${iIdx}">
            <div class="mf-edit-drag" title="Drag to reorder">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>
            <div class="mf-edit-num">${dIdx+1}.${iIdx+1}</div>
            <div class="mf-edit-fields">
                <textarea class="fc ind-text-input" rows="2" placeholder="Indicator text…" style="font-size:13px;resize:vertical;"
                    onchange="updateIndicator(${dIdx},${iIdx},'indicator_text',this.value)">${_esc(ind.indicator_text)}</textarea>
                <input type="text" class="fc ind-mov-input" placeholder="MOV guide (optional)…" style="font-size:12.5px;"
                    value="${_esc(ind.mov_guide||'')}"
                    onchange="updateIndicator(${dIdx},${iIdx},'mov_guide',this.value)">
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;padding-top:6px;flex-shrink:0;">
                <button class="btn btn-secondary btn-sm btn-icon" title="Remove indicator" onclick="removeIndicator(${dIdx},${iIdx})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
            </div>
        </div>`).join('');

    card.innerHTML = `
        <div class="mf-dim-head" onclick="toggleDimBody(this)">
            <div class="mf-dim-number">
                <div class="mf-dim-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${iconPath}</svg>
                </div>
            </div>
            <div style="flex:1;display:flex;align-items:center;gap:8px;">
                <input type="text" class="fc" value="${_esc(dim.dimension_name)}"
                    placeholder="Dimension name…"
                    style="flex:1;font-size:13.5px;font-weight:700;border-color:transparent;background:transparent;padding:4px 8px;"
                    onclick="event.stopPropagation()"
                    onchange="updateDimension(${dIdx},'dimension_name',this.value)"
                    oninput="updateDimension(${dIdx},'dimension_name',this.value)">
            </div>
            <div class="mf-dim-count">${dim.indicators.length} ind.</div>
            <button class="btn btn-danger btn-sm btn-icon" title="Remove dimension"
                style="margin-left:6px;"
                onclick="event.stopPropagation();removeDimension(${dIdx})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--n-400);flex-shrink:0;transition:transform 200ms;" class="mf-chevron">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="mf-dim-body open">
            <div class="ind-list">${indHtml}</div>
            <div style="padding:10px 0 4px;">
                <button class="mf-add-btn" onclick="addIndicator(${dIdx})">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Indicator
                </button>
            </div>
        </div>`;

    document.getElementById('editDimList').appendChild(card);
}

// ── EDIT HELPERS ──────────────────────────────────────────────
function updateDimension(dIdx, field, value) {
    if (_editData[dIdx]) _editData[dIdx][field] = value;
}
function updateIndicator(dIdx, iIdx, field, value) {
    if (_editData[dIdx]?.indicators?.[iIdx]) _editData[dIdx].indicators[iIdx][field] = value;
}

function addDimension() {
    const newNo = _editData.length + 1;
    _editData.push({ dimension_no: newNo, dimension_name: 'New Dimension ' + newNo, color_hex: DIM_COLORS[(newNo-1) % DIM_COLORS.length], icon: 'layers', indicators: [{ indicator_text: '', mov_guide: '' }] });
    renderEditForm();
    // Scroll to bottom
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}

function removeDimension(dIdx) {
    if (_editData.length <= 1) { toast('A form must have at least one dimension.','warning'); return; }
    if (!confirm('Remove this entire dimension and all its indicators?')) return;
    _editData.splice(dIdx, 1);
    _editData.forEach((d, i) => d.dimension_no = i + 1);
    renderEditForm();
}

function addIndicator(dIdx) {
    _editData[dIdx].indicators.push({ indicator_text: '', mov_guide: '' });
    renderEditForm();
}

function removeIndicator(dIdx, iIdx) {
    if (_editData[dIdx].indicators.length <= 1) { toast('A dimension must have at least one indicator.','warning'); return; }
    _editData[dIdx].indicators.splice(iIdx, 1);
    renderEditForm();
}

// Sync textarea/input values back into _editData before publishing
function syncEditDataFromDOM() {
    const cards = document.querySelectorAll('#editDimList .mf-dim-card');
    cards.forEach((card, dIdx) => {
        if (!_editData[dIdx]) return;
        const nameInput = card.querySelector('.mf-dim-head input');
        if (nameInput) _editData[dIdx].dimension_name = nameInput.value;
        const rows = card.querySelectorAll('.mf-edit-row');
        rows.forEach((row, iIdx) => {
            if (!_editData[dIdx].indicators[iIdx]) return;
            const ta  = row.querySelector('.ind-text-input');
            const mov = row.querySelector('.ind-mov-input');
            if (ta)  _editData[dIdx].indicators[iIdx].indicator_text = ta.value;
            if (mov) _editData[dIdx].indicators[iIdx].mov_guide       = mov.value;
        });
    });
}

// ── PUBLISH ───────────────────────────────────────────────────
async function publishNewVersion() {
    syncEditDataFromDOM();

    // Validate: no empty indicator text
    for (const d of _editData) {
        if (!d.dimension_name.trim()) { toast('All dimensions must have a name.','warning'); return; }
        for (const ind of d.indicators) {
            if (!ind.indicator_text.trim()) { toast('All indicators must have text.','warning'); return; }
        }
    }

    const label = document.getElementById('newVersionLabel').value.trim();
    const btn = document.getElementById('btnPublish');
    btn.disabled = true;
    btn.textContent = 'Publishing…';

    const fd = new FormData();
    fd.append('action','publish_version');
    fd.append('payload', JSON.stringify({ dimensions: _editData }));
    fd.append('label', label);
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    const res = await fetch('manage_form.php', { method:'POST', body: fd });
    const data = await res.json();
    btn.disabled = false;
    btn.textContent = 'Publish New Version';
    toast(data.msg, data.ok ? 'ok' : 'err');
    if (data.ok) setTimeout(() => location.reload(), 900);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>