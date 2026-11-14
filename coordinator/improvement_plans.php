<?php
ob_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sbm_indicators.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/improvement_plan_workflow.php';
requireRole('sbm_coordinator');
$db = getDB();

$syId = (int) $db->query("SELECT sy_id FROM school_years WHERE is_current = 1 LIMIT 1")->fetchColumn();
$syLabel = $db->query("SELECT label FROM school_years WHERE sy_id = {$syId} LIMIT 1")->fetchColumn() ?: '—';
$cycleQ = $db->prepare("SELECT cycle_id FROM sbm_cycles WHERE school_id = ? AND sy_id = ? ORDER BY created_at DESC LIMIT 1");
$cycleQ->execute([SCHOOL_ID, $syId]);
$cycleId = (int) $cycleQ->fetchColumn();

$plans = [];
if ($cycleId) {
    $planQ = $db->prepare("SELECT ip.*, d.dimension_no, d.dimension_name,
      COALESCE(plan_indicators.indicator_code, i.indicator_code) AS indicator_code,
      COALESCE(plan_indicators.indicator_text, i.indicator_text) AS indicator_text,
      vu.full_name AS validated_by_name
        FROM improvement_plans ip
        JOIN sbm_dimensions d ON d.dimension_id = ip.dimension_id
        LEFT JOIN sbm_indicators i ON i.indicator_id = ip.indicator_id
        LEFT JOIN (
          SELECT pii.plan_id,
                 GROUP_CONCAT(DISTINCT si.indicator_code ORDER BY si.indicator_code SEPARATOR ', ') AS indicator_code,
                 GROUP_CONCAT(DISTINCT si.indicator_text ORDER BY si.indicator_code SEPARATOR ' | ') AS indicator_text
          FROM improvement_plan_indicators pii
          JOIN sbm_indicators si ON si.indicator_id = pii.indicator_id
          GROUP BY pii.plan_id
        ) plan_indicators ON plan_indicators.plan_id = ip.plan_id
        LEFT JOIN users vu ON vu.user_id = ip.validated_by
        WHERE ip.cycle_id = ?
        ORDER BY FIELD(ip.workflow_status, 'submitted', 'resubmitted_to_coordinator', 'approved', 'returned_to_school_head', 'finalized', 'draft'), FIELD(ip.priority_level, 'High', 'Medium', 'Low'), ip.created_at");
    $planQ->execute([$cycleId]);
    $plans = $planQ->fetchAll();
}

$historyByPlan = [];
if ($plans) {
    $ids = array_column($plans, 'plan_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $historyQ = $db->prepare("SELECT h.*, u.full_name AS actor_name FROM improvement_plan_history h JOIN users u ON u.user_id = h.actor_id WHERE h.plan_id IN ($placeholders) ORDER BY h.plan_id, h.version_no DESC");
    $historyQ->execute($ids);
    foreach ($historyQ->fetchAll() as $history) $historyByPlan[$history['plan_id']][] = $history;
}

$planTabCounts = [
    'review' => 0,
    'approved' => 0,
    'finalized' => 0,
    'returned' => 0,
];
foreach ($plans as $plan) {
    $tab = match ($plan['workflow_status']) {
        IP_STATUS_SUBMITTED, IP_STATUS_RESUBMITTED => 'review',
        IP_STATUS_APPROVED => 'approved',
        IP_STATUS_FINALIZED => 'finalized',
        IP_STATUS_RETURNED => 'returned',
        default => null,
    };
    if ($tab !== null) {
        $planTabCounts[$tab]++;
    }
}
$activePlanTab = $planTabCounts['review'] > 0 ? 'review'
    : ($planTabCounts['approved'] > 0 ? 'approved'
        : ($planTabCounts['finalized'] > 0 ? 'finalized'
            : 'returned'));

$pageTitle = 'SH Improvement Plans';
$activePage = 'improvement_plans.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <span class="card-title">Improvement Plan Review Queue</span>
    <?php if ($planTabCounts['review'] > 0 || $planTabCounts['approved'] > 0): ?>
    <div style="display:flex;align-items:center;gap:8px;">
    <?php if ($planTabCounts['review'] > 0): ?>
      <button
        type="button"
        class="btn btn-secondary"
        id="approveAllPlansButton"
        data-count="<?= (int) $planTabCounts['review'] ?>"
        title="Approve all plans currently in the Review queue. They will still require separate validation."
      >Approve All</button>
    <?php endif; ?>
    <?php if ($planTabCounts['approved'] > 0): ?>
      <button
        type="button"
        class="btn btn-secondary"
        id="validateAllPlansButton"
        data-count="<?= (int) $planTabCounts['approved'] ?>"
        title="Validate all approved plans and move them to Finalized."
      >Validate All</button>
    <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php if (!$plans): ?>
    <div class="card-body" style="padding:28px;color:var(--n-500);font-size:13px;">No improvement plans are available for the current school year.</div>
  <?php else: ?>
    <div class="ip-review-tabs" role="tablist" aria-label="Filter improvement plans by workflow status">
      <?php foreach ([
        'review' => 'Review',
        'approved' => 'Approved',
        'finalized' => 'Finalized',
        'returned' => 'Returned',
      ] as $tabKey => $tabLabel): ?>
        <button
          type="button"
          class="ip-review-tab<?= $activePlanTab === $tabKey ? ' active' : '' ?>"
          id="ipReviewTab-<?= e($tabKey) ?>"
          role="tab"
          aria-selected="<?= $activePlanTab === $tabKey ? 'true' : 'false' ?>"
          aria-controls="ipReviewTableBody"
          tabindex="<?= $activePlanTab === $tabKey ? '0' : '-1' ?>"
          data-review-tab="<?= e($tabKey) ?>"
        ><?= e($tabLabel) ?><span class="ip-review-tab-count"><?= (int) $planTabCounts[$tabKey] ?></span></button>
      <?php endforeach; ?>
    </div>
    <div class="tbl-wrap ip-review-table-wrap">
      <table class="ip-review-summary-table">
        <thead>
          <tr>
            <th class="ip-review-stage-col">Review Stage</th>
            <th class="ip-review-dimension-col">Dimension</th>
            <th class="ip-review-indicator-col">Indicator</th>
            <th class="ip-review-objective-col">Objective</th>
            <th class="ip-review-priority-col">Priority</th>
            <th class="ip-review-date-col">Target Date</th>
            <th class="ip-review-actions-col">Actions</th>
          </tr>
        </thead>
        <tbody id="ipReviewTableBody">
        <?php foreach ($plans as $plan):
            $status = $plan['workflow_status'];
            $canReview = in_array($status, [IP_STATUS_SUBMITTED, IP_STATUS_RESUBMITTED], true);
            $canValidate = $status === IP_STATUS_APPROVED;
            $history = $historyByPlan[$plan['plan_id']] ?? [];
            $planTab = match ($status) {
                IP_STATUS_SUBMITTED, IP_STATUS_RESUBMITTED => 'review',
                IP_STATUS_APPROVED => 'approved',
                IP_STATUS_FINALIZED => 'finalized',
                IP_STATUS_RETURNED => 'returned',
                default => null,
            };
            $stageOwner = $status === IP_STATUS_RETURNED ? 'School Head'
                : ($status === IP_STATUS_FINALIZED ? 'Completed' : 'SBM Coordinator');
            $reviewDrawerPlan = [
              'plan_id' => (int) $plan['plan_id'],
              'dimension_no' => (int) $plan['dimension_no'],
              'dimension_name' => $plan['dimension_name'],
              'indicator_code' => $plan['indicator_code'] ?: 'General',
              'indicator_text' => $plan['indicator_text'] ?: '',
              'objective' => $plan['objective'],
              'strategy' => $plan['strategy'],
              'person_responsible' => $plan['person_responsible'],
              'resources_needed' => $plan['resources_needed'],
              'expected_output' => $plan['expected_output'],
              'priority_level' => $plan['priority_level'],
              'target_date' => $plan['target_date'],
              'workflow_status' => $status,
              'status_label' => ipStatusLabel($status),
              'stage_owner' => $stageOwner,
              'validated_by_name' => $plan['validated_by_name'],
              'validated_at' => $plan['validated_at'],
              'can_return' => $canReview,
              'can_approve' => $canReview,
              'can_validate' => $canValidate,
            ];
        ?>
          <tr
            class="ip-plan-row ip-review-summary-row"
            <?= $planTab !== null ? 'data-review-status="' . e($planTab) . '"' : '' ?>
            data-plan-detail="<?= e(json_encode($reviewDrawerPlan, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)) ?>"
            tabindex="0"
            aria-haspopup="dialog"
            aria-label="View details for dimension D<?= (int) $plan['dimension_no'] ?>, indicator <?= e($plan['indicator_code'] ?: 'General') ?>"
            <?= $planTab === null || $activePlanTab !== $planTab ? 'hidden' : '' ?>
          >
            <td>
              <span class="ip-stage-label"><?= e(ipStatusLabel($status)) ?></span><br>
              <small><?= e($stageOwner) ?></small>
            </td>
            <td><span class="ip-dimension-cell">D<?= (int) $plan['dimension_no'] ?></span><br><small><?= e($plan['dimension_name']) ?></small></td>
            <td>
              <span class="ip-indicator-cell"><?= e($plan['indicator_code'] ?: 'General') ?></span>
              <div class="ip-review-indicator-summary"><?= e($plan['indicator_text'] ?: '') ?></div>
            </td>
            <td class="ip-review-objective-cell"><div class="ip-review-objective-summary"><?= e($plan['objective']) ?></div></td>
            <td><span class="priority-pill priority-<?= e(strtolower($plan['priority_level'])) ?>"><?= e($plan['priority_level']) ?></span></td>
            <td><?= $plan['target_date'] ? e(date('M j, Y', strtotime($plan['target_date']))) : '—' ?></td>
            <td class="ip-review-actions-cell">
              <button type="button" class="full-plan-trigger ip-review-view-button" data-view-plan title="View plan details" aria-label="View details for dimension D<?= (int) $plan['dimension_no'] ?>, indicator <?= e($plan['indicator_code'] ?: 'General') ?>">
                View Plan
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p class="ip-review-tab-empty" hidden>No improvement plans in this status.</p>
    </div>
  <?php endif; ?>
</div>

<div id="historyPopover" class="history-popover" role="tooltip" aria-hidden="true">
  <div id="historyPopoverList" class="history-popover-list"></div>
</div>

<div id="planDetailsDrawer" class="ip-review-drawer-backdrop" role="dialog" aria-modal="true" aria-labelledby="planDetailsTitle" aria-hidden="true">
  <section class="ip-review-drawer" tabindex="-1">
    <div class="ip-review-drawer-header">
      <h2 class="modal-title" id="planDetailsTitle">Improvement Plan</h2>
      <button type="button" class="btn btn-ghost ip-review-drawer-close" onclick="closePlanDetailsDrawer()" aria-label="Close improvement plan details">&times;</button>
    </div>
    <div class="ip-review-drawer-body" id="planDetailsBody"></div>
    <footer class="ip-review-drawer-footer" id="planDetailsActions"></footer>
  </section>
</div>

<div id="returnRemarksModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="returnRemarksTitle" aria-describedby="returnRemarksHelp">
  <div class="modal-content return-remarks-modal-content">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title" id="returnRemarksTitle">Return Improvement Plan</div>
        <button type="button" class="btn btn-ghost" style="padding:4px;" onclick="closeReturnRemarksModal()" aria-label="Close">&times;</button>
      </div>
      <form id="returnRemarksForm">
        <div class="modal-body">
          <p id="returnPlanContext" class="return-plan-context"></p>
          <label class="return-remarks-label" for="returnRemarksInput">What should the School Head revise?</label>
          <p id="returnRemarksHelp" class="return-remarks-help">Your comments will be included in the plan history.</p>
          <textarea id="returnRemarksInput" class="form-control return-remarks-input" rows="5" placeholder="Describe the changes or clarifications needed…" required></textarea>
          <div id="returnRemarksError" class="return-remarks-error" role="alert" aria-live="polite"></div>
        </div>
        <div class="modal-footer">
          <div style="flex:1"></div>
          <button type="button" class="return-cancel-button" onclick="closeReturnRemarksModal()">Cancel</button>
          <button type="submit" id="sendReturnButton" class="return-submit-button">Send Return</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="planActionConfirmModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="planActionConfirmTitle" aria-describedby="planActionConfirmMessage">
  <div class="modal-content" style="width:440px;">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title" id="planActionConfirmTitle"></div>
        <button type="button" class="btn btn-ghost" style="padding:4px;" onclick="closePlanActionConfirm()" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <p id="planActionConfirmMessage" style="margin:0;font-size:13.5px;color:var(--n-700);line-height:1.6;"></p>
        <div id="planActionConfirmError" class="return-remarks-error" role="alert" aria-live="polite"></div>
      </div>
      <div class="modal-footer">
        <div style="flex:1"></div>
        <button type="button" class="btn btn-secondary" onclick="closePlanActionConfirm()">Cancel</button>
        <button type="button" id="confirmPlanActionButton" class="btn btn-success" onclick="confirmPlanAction()"></button>
      </div>
    </div>
  </div>
</div>

<style>
 .modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(0,0,0,.4);backdrop-filter:blur(2px);z-index:2000}.modal-content{width:600px;max-width:calc(100vw - 40px);background:#fff;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.2);overflow:hidden;display:flex;flex-direction:column;animation:modalSlideUp .3s ease-out}.modal-form-side{display:flex;flex-direction:column;max-height:85vh}.modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:#f8fafc;border-bottom:1px solid var(--n-200)}.modal-title{font-size:16px;font-weight:700;color:var(--n-900)}.modal-body{padding:20px;overflow-y:auto;max-height:calc(100vh - 200px)}.modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid var(--n-200)}@keyframes modalSlideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
 .ip-review-tabs{display:flex;align-items:center;gap:6px;padding:12px 20px 0;border-bottom:1px solid var(--n-200);overflow-x:auto}.ip-review-tab{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border:0;border-bottom:2px solid transparent;background:transparent;color:var(--n-600);font:inherit;font-size:12.5px;font-weight:600;white-space:nowrap;cursor:pointer}.ip-review-tab:hover,.ip-review-tab.active{color:var(--brand-700,#15803d)}.ip-review-tab.active{border-bottom-color:var(--brand-600,#16a34a)}.ip-review-tab:focus-visible{outline:2px solid var(--brand-600,#16a34a);outline-offset:-2px}.ip-review-tab-count{display:inline-flex;min-width:20px;height:20px;align-items:center;justify-content:center;padding:0 5px;border-radius:999px;background:var(--n-100);color:var(--n-600);font-size:10.5px}.ip-review-tab.active .ip-review-tab-count{background:var(--green-100,#dcfce7);color:var(--green-700,#15803d)}.ip-review-tab-empty{margin:0;padding:24px;color:var(--n-500);font-size:13px;text-align:center}
 .history-trigger{display:block;margin-top:8px;padding:0;border:0;background:none;color:var(--n-700);font:inherit;font-size:11px;cursor:pointer;text-align:left}.history-trigger:hover{text-decoration:underline}.history-popover{position:fixed;display:none;width:300px;max-width:calc(100vw - 24px);max-height:300px;overflow-y:auto;padding:8px 10px;background:#fff;border:1px solid var(--n-200);border-radius:6px;box-shadow:0 8px 24px rgba(15,23,42,.14);z-index:2100}.history-popover-list{font-size:11px;line-height:1.4}.history-popover-entry{padding:7px 0;border-bottom:1px solid var(--n-200)}.history-popover-entry:last-child{border-bottom:0}.history-popover-entry.current{position:relative;padding-left:12px}.history-popover-entry.current::before{content:"";position:absolute;left:1px;top:12px;width:5px;height:5px;border-radius:50%;background:var(--n-700)}.history-popover-version{font-weight:700;color:var(--n-900)}.history-popover-meta{color:var(--n-500)}.history-popover-remarks{margin-top:2px;color:var(--n-500);white-space:pre-wrap}
 .full-plan-trigger{display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:7px 12px;border:1px solid var(--n-300);border-radius:8px;background:#fff;color:var(--n-700);font:inherit;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;line-height:1.2;transition:background .15s,border-color .15s,box-shadow .15s,transform .15s}.full-plan-trigger:hover{background:var(--n-50);border-color:var(--n-400);box-shadow:0 2px 6px rgba(15,23,42,.06)}.full-plan-trigger:focus-visible{outline:3px solid rgba(22,163,74,.18);outline-offset:2px}.full-plan-modal-content{max-width:620px}.full-plan-modal-body{font-size:13.5px;line-height:1.6;color:var(--n-800)}.full-plan-modal-section{margin-bottom:16px}.full-plan-modal-section:last-child{margin-bottom:0}.full-plan-modal-section strong{display:block;margin-bottom:4px;color:var(--n-900);font-weight:700}.full-plan-modal-section div{white-space:pre-wrap;font-weight:400}
 .return-remarks-modal-content{max-width:520px}.return-remarks-modal-content form{display:flex;flex-direction:column;min-height:0}.return-remarks-modal-content .modal-body{max-height:none}.return-plan-context{margin:0 0 16px;padding:10px 12px;border:1px solid var(--n-200);border-radius:8px;background:var(--n-50);color:var(--n-700);font-size:12.5px;font-weight:600}.return-remarks-label{display:block;margin-bottom:5px;color:var(--n-800);font-size:13px;font-weight:700}.return-remarks-help{margin:0 0 9px;color:var(--n-500);font-size:12px;line-height:1.5}.return-remarks-input{display:block;width:100%;min-height:140px;padding:11px 13px;border:1px solid var(--n-300);border-radius:9px;background:#fff;color:var(--n-800);font:inherit;font-size:13px;line-height:1.55;box-sizing:border-box;resize:vertical;outline:none;transition:border-color .15s,box-shadow .15s}.return-remarks-input::placeholder{color:var(--n-400);opacity:1}.return-remarks-input:hover{border-color:var(--n-400)}.return-remarks-input:focus{border-color:var(--brand-600);box-shadow:0 0 0 3px rgba(22,163,74,.12)}.return-remarks-error{min-height:0;margin-top:6px;color:#dc2626;font-size:12px}.return-remarks-error:empty{display:none}.return-remarks-modal-content .return-cancel-button,.return-remarks-modal-content .return-submit-button{min-height:38px;padding:8px 16px;border-radius:8px;font:inherit;font-size:12.5px;font-weight:700;line-height:1.2;cursor:pointer;transition:background .15s,border-color .15s,color .15s,box-shadow .15s,transform .15s}.return-remarks-modal-content .return-cancel-button{border:1px solid var(--n-300);background:#fff;color:var(--n-700)}.return-remarks-modal-content .return-cancel-button:hover{border-color:var(--n-400);background:var(--n-50)}.return-remarks-modal-content .return-submit-button{border:1px solid var(--green-700,#15803d);background:var(--green-700,#15803d);color:#fff;box-shadow:0 1px 2px rgba(15,23,42,.12)}.return-remarks-modal-content .return-submit-button:hover:not(:disabled){border-color:var(--green-800,#166534);background:var(--green-800,#166534);box-shadow:0 3px 8px rgba(21,128,61,.22);transform:translateY(-1px)}.return-remarks-modal-content .return-submit-button:focus-visible,.return-remarks-modal-content .return-cancel-button:focus-visible{outline:3px solid rgba(22,163,74,.25);outline-offset:2px}.return-remarks-modal-content .return-submit-button:disabled{cursor:wait;opacity:.65;box-shadow:none;transform:none}
thead th{text-transform:none;font-size:11px;font-weight:500;letter-spacing:0.01em} thead th:last-child{text-align:center}.ip-status-badge{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:700;background:#fef3c7;color:#b45309;white-space:nowrap}.ip-status-badge.finalized{background:#dcfce7;color:#15803d}.ip-status-badge.approved{background:#dbeafe;color:#1d4ed8}.ip-status-badge.returned{background:#ffedd5;color:#c2410c}.btn-sm{padding:5px 9px;font-size:11px;margin:2px 0}.action-help{display:block;color:var(--n-500);font-size:10px;line-height:1.3;margin:0 0 5px}.priority-pill{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:11px;font-weight:600;box-shadow:none;line-height:1.3}.priority-high{background:#fef2f2;color:#b91c1c}.priority-medium{background:#fef3c7;color:#b45309}.priority-low{background:#e0f2fe;color:#0369a1}.plan-clamp{max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:4px 0 8px;color:var(--n-700);font-weight:400}.plan-detail{display:grid;gap:11px}.plan-detail-row{margin:0}.plan-detail-label{display:block;font-size:11px;font-weight:700;color:var(--n-700);text-transform:none;letter-spacing:0.01em}.plan-detail-copy{display:block;color:var(--n-700);font-weight:400;line-height:1.55}.full-plan summary{cursor:pointer;color:var(--n-700);font-size:11px;margin-top:6px}.full-plan div{max-width:340px;padding:10px;background:var(--n-50);border-radius:6px;margin-top:5px}.full-plan p{margin:3px 0 9px;line-height:1.45}.full-plan .plan-detail-block{margin:0 0 9px}.full-plan .plan-detail-copy{margin-top:4px}.history-summary{cursor:pointer;color:var(--n-700);font-size:11px}.history-panel{margin-top:8px}.plan-history-list{margin-top:7px;font-size:11px;line-height:1.45}.history-item{padding:6px 0;border-bottom:1px solid var(--n-200);color:var(--n-500)}.history-current{border-left:2px solid var(--n-500);padding-left:7px;color:var(--n-800);background:transparent}.history-muted{color:var(--n-500)}.history-person{color:var(--n-500)}.history-remark{color:var(--n-600)}.history-version{font-weight:700;color:var(--n-900)}.history-current .history-version{font-weight:700}.ip-plan-row{background:#fff}.ip-plan-row td{border-top:1px solid var(--n-200)}.ip-plan-row .ip-dimension-cell,.ip-plan-row .ip-indicator-cell{font-size:12px;font-weight:700;color:var(--n-800);text-decoration:none}.ip-plan-row .ip-indicator-cell{color:var(--n-800)}.ip-stage-label{font-size:11px;color:var(--n-800);font-weight:600;white-space:nowrap}.ip-date-input{min-width:142px;color-scheme:light}.plan-detail-label,.plan-detail-block .plan-detail-label{font-weight:700}.plan-detail-copy{font-weight:400}.history-current strong{font-weight:700}.history-muted strong{font-weight:600}.history-item .history-person,.history-item .history-remark{font-weight:400}
</style>
<style>
.ip-review-table-wrap{overflow-x:hidden}
.ip-review-summary-table{width:100%;table-layout:fixed}
.ip-review-summary-table th,.ip-review-summary-table td{padding-left:10px;padding-right:10px}
.ip-review-summary-table thead th{white-space:normal}
.ip-review-stage-col{width:17%}
.ip-review-dimension-col{width:11%}
.ip-review-indicator-col{width:14%}
.ip-review-objective-col{width:28%}
.ip-review-priority-col{width:10%}
.ip-review-date-col{width:11%}
.ip-review-actions-col{width:9%}
.ip-review-summary-row{cursor:pointer;transition:background-color .15s ease}
.ip-review-summary-row:hover>td{background:var(--n-50)}
.ip-review-summary-row:focus-visible>td{outline:2px solid var(--brand-600,#16a34a);outline-offset:-2px}
.ip-review-summary-row .ip-stage-label{white-space:normal;overflow-wrap:anywhere}
.ip-review-summary-row td:nth-child(2){overflow-wrap:anywhere}
.ip-review-indicator-summary,.ip-review-objective-summary{display:-webkit-box;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow-wrap:anywhere;line-height:1.45}
.ip-review-indicator-summary{margin-top:2px;color:var(--n-600);font-size:11px}
.ip-review-objective-cell{font-size:12px}
.ip-review-actions-cell{text-align:center;padding-left:6px!important;padding-right:6px!important}
.ip-review-view-button{min-width:84px;padding:7px 12px;white-space:nowrap}
.ip-review-drawer-backdrop{position:fixed;inset:0;z-index:2100;display:none;align-items:stretch;justify-content:flex-end;background:rgba(15,23,42,.38);backdrop-filter:blur(2px)}
.ip-review-drawer-backdrop.open{display:flex}
.ip-review-drawer{display:flex;flex-direction:column;width:min(560px,100vw);height:100%;background:#fff;box-shadow:-16px 0 42px rgba(15,23,42,.18);transform:translateX(100%);transition:transform .22s ease-out}
.ip-review-drawer-backdrop.open .ip-review-drawer{transform:translateX(0)}
.ip-review-drawer-header{display:flex;flex:0 0 auto;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;background:#f8fafc;border-bottom:1px solid var(--n-200)}
.ip-review-drawer-header .modal-title{margin:0}
.ip-review-drawer-body{flex:1 1 auto;overflow-y:auto;padding:20px;color:var(--n-800);font-size:13.5px;line-height:1.6}
.ip-review-detail-row{margin-bottom:16px}
.ip-review-detail-row:last-child{margin-bottom:0}
.ip-review-detail-label{margin-bottom:4px;color:var(--n-500);font-size:11.5px;font-weight:600;letter-spacing:.03em;text-transform:uppercase}
.ip-review-detail-value{color:var(--n-800);white-space:pre-wrap;overflow-wrap:anywhere}
.ip-review-drawer-footer{display:flex;flex:0 0 auto;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:8px;padding:14px 20px;border-top:1px solid var(--n-200);background:#fff}
.ip-review-drawer-footer:empty{display:none}
.ip-review-drawer .ip-stage-label{white-space:normal}
@media(max-width:760px){.ip-review-table-wrap{overflow-x:auto}.ip-review-summary-table{min-width:720px}.ip-review-drawer{width:100%;max-width:none;box-shadow:none}}
@media(prefers-reduced-motion:reduce){.ip-review-summary-row,.ip-review-drawer{transition:none}}
</style>
<script>
let openHistoryTrigger = null;

function closeHistoryPopover() {
  const popover = document.getElementById('historyPopover');
  if (!popover) return;
  popover.style.display = 'none';
  popover.setAttribute('aria-hidden', 'true');
  if (openHistoryTrigger) openHistoryTrigger.setAttribute('aria-expanded', 'false');
  openHistoryTrigger = null;
}

function initImprovementPlanReviewTabs() {
  const tabs = [...document.querySelectorAll('.ip-review-tab')];
  const rows = [...document.querySelectorAll('#ipReviewTableBody tr[data-review-status]')];
  const emptyState = document.querySelector('.ip-review-tab-empty');
  if (!tabs.length || !rows.length) return;

  const activateTab = (activeTab, moveFocus = false) => {
    const selectedStatus = activeTab.dataset.reviewTab;
    tabs.forEach(tab => {
      const selected = tab === activeTab;
      tab.classList.toggle('active', selected);
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
      tab.tabIndex = selected ? 0 : -1;
    });

    let visibleCount = 0;
    rows.forEach(row => {
      const visible = row.dataset.reviewStatus === selectedStatus;
      row.hidden = !visible;
      if (visible) visibleCount++;
    });
    if (emptyState) emptyState.hidden = visibleCount > 0;
    if (moveFocus) activeTab.focus();
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', event => {
      let nextIndex = null;
      if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = tabs.length - 1;
      if (nextIndex === null) return;
      event.preventDefault();
      activateTab(tabs[nextIndex], true);
    });
  });
}
document.addEventListener('DOMContentLoaded', initImprovementPlanReviewTabs);

function openHistoryPopover(trigger, history) {
  const popover = document.getElementById('historyPopover');
  const list = document.getElementById('historyPopoverList');
  if (!popover || !list) return;
  if (openHistoryTrigger === trigger) {
    closeHistoryPopover();
    return;
  }
  closeHistoryPopover();
  list.replaceChildren();
  history.forEach((entry, index) => {
    const item = document.createElement('div');
    item.className = 'history-popover-entry' + (index === 0 ? ' current' : '');

    const version = document.createElement('div');
    version.className = 'history-popover-version';
    version.textContent = `v${entry.version} · ${entry.action}`;
    item.appendChild(version);

    const meta = document.createElement('div');
    meta.className = 'history-popover-meta';
    meta.textContent = `${entry.actor} · ${entry.timestamp}`;
    item.appendChild(meta);

    if (entry.remarks) {
      const remarks = document.createElement('div');
      remarks.className = 'history-popover-remarks';
      remarks.textContent = entry.remarks;
      item.appendChild(remarks);
    }
    list.appendChild(item);
  });

  popover.style.display = 'block';
  popover.setAttribute('aria-hidden', 'false');
  trigger.setAttribute('aria-expanded', 'true');
  openHistoryTrigger = trigger;

  const triggerRect = trigger.getBoundingClientRect();
  const popoverRect = popover.getBoundingClientRect();
  const gap = 8;
  const viewportPadding = 12;
  let left = triggerRect.right - popoverRect.width;
  if (left < viewportPadding) left = triggerRect.left;
  if (left + popoverRect.width > window.innerWidth - viewportPadding) {
    left = triggerRect.left - popoverRect.width - gap;
  }
  left = Math.max(viewportPadding, Math.min(left, window.innerWidth - popoverRect.width - viewportPadding));

  let top = triggerRect.bottom + gap;
  if (top + popoverRect.height > window.innerHeight - viewportPadding) {
    top = triggerRect.top - popoverRect.height - gap;
  }
  top = Math.max(viewportPadding, Math.min(top, window.innerHeight - popoverRect.height - viewportPadding));
  popover.style.left = `${left}px`;
  popover.style.top = `${top}px`;
}

document.addEventListener('click', event => {
  if (!openHistoryTrigger) return;
  const popover = document.getElementById('historyPopover');
  if (!popover.contains(event.target) && event.target !== openHistoryTrigger) closeHistoryPopover();
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') closeHistoryPopover();
});

window.addEventListener('resize', closeHistoryPopover);

function ipReviewEscape(value) {
  const element = document.createElement('div');
  element.textContent = value == null ? '' : String(value);
  return element.innerHTML;
}

function ipReviewDetailRow(label, value) {
  return `<div class="ip-review-detail-row">
    <div class="ip-review-detail-label">${label}</div>
    <div class="ip-review-detail-value">${value || '<span style="color:var(--n-400);">—</span>'}</div>
  </div>`;
}

let currentReviewPlan = null;
let reviewDrawerReturnFocus = null;
let reviewDrawerPreviousBodyOverflow = '';

function addReviewDrawerAction(actions, label, action, className = '') {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = className || 'btn btn-secondary btn-sm';
  button.dataset.drawerAction = action;
  button.textContent = label;
  actions.appendChild(button);
  return button;
}

function renderReviewDrawerActions(plan) {
  const actions = document.getElementById('planDetailsActions');
  actions.innerHTML = '';
  if (plan.can_return) {
    const button = addReviewDrawerAction(actions, 'Return', 'return', 'btn btn-secondary btn-sm');
    button.title = 'Return the plan without changing its content.';
    button.dataset.id = plan.plan_id;
    button.dataset.dimension = `D${plan.dimension_no}`;
    button.dataset.indicator = plan.indicator_code || 'General';
  }
  if (plan.can_approve) {
    const button = addReviewDrawerAction(actions, 'Approve', 'approve', 'btn btn-success btn-sm');
    button.title = 'Approve the content and move it to the separate validation step.';
  }
  if (plan.can_validate) {
    const button = addReviewDrawerAction(actions, 'Validate', 'validate', 'btn btn-success btn-sm');
    button.title = 'Record final validation and move the plan to Finalized.';
  }
  addReviewDrawerAction(actions, 'Close', 'close', 'btn btn-secondary btn-sm');
}

function renderReviewPlanDetails(plan) {
  document.getElementById('planDetailsTitle').textContent = 'Improvement Plan Details';
  const indicator = [
    ipReviewEscape(plan.indicator_code || 'General'),
    plan.indicator_text ? `<div>${ipReviewEscape(plan.indicator_text)}</div>` : '',
  ].join('');
  const priorityClass = {
    High: 'priority-high',
    Medium: 'priority-medium',
    Low: 'priority-low',
  }[plan.priority_level] || '';
  const targetDate = plan.target_date
    ? new Date(plan.target_date + 'T00:00:00').toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'})
    : null;
  const status = [
    `<span class="ip-stage-label">${ipReviewEscape(plan.status_label)}</span>`,
    `<div>Current stage: ${ipReviewEscape(plan.stage_owner)}</div>`,
    plan.validated_by_name
      ? `<div>Validated by ${ipReviewEscape(plan.validated_by_name)}${plan.validated_at ? ` · ${ipReviewEscape(plan.validated_at)}` : ''}</div>`
      : '',
  ].join('');

  document.getElementById('planDetailsBody').innerHTML =
    ipReviewDetailRow('Dimension', `D${ipReviewEscape(plan.dimension_no)} — ${ipReviewEscape(plan.dimension_name)}`) +
    ipReviewDetailRow('Indicator', indicator) +
    ipReviewDetailRow('Objective', ipReviewEscape(plan.objective)) +
    ipReviewDetailRow('Strategy', ipReviewEscape(plan.strategy)) +
    ipReviewDetailRow('Person Responsible', ipReviewEscape(plan.person_responsible)) +
    ipReviewDetailRow('Resources Needed', ipReviewEscape(plan.resources_needed)) +
    ipReviewDetailRow('Expected Output', ipReviewEscape(plan.expected_output)) +
    ipReviewDetailRow('Priority', `<span class="priority-pill ${priorityClass}">${ipReviewEscape(plan.priority_level)}</span>`) +
    ipReviewDetailRow('Target Date', targetDate ? ipReviewEscape(targetDate) : null) +
    ipReviewDetailRow('Status', status);
  renderReviewDrawerActions(plan);
}

function openPlanDetailsDrawer(plan, triggerElement) {
  const drawer = document.getElementById('planDetailsDrawer');
  currentReviewPlan = plan;
  reviewDrawerReturnFocus = triggerElement || document.activeElement;
  renderReviewPlanDetails(plan);
  drawer.setAttribute('aria-hidden', 'false');
  drawer.classList.add('open');
  reviewDrawerPreviousBodyOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';
  requestAnimationFrame(() => {
    const closeButton = drawer.querySelector('.ip-review-drawer-close');
    if (closeButton) closeButton.focus();
  });
}

function closePlanDetailsDrawer() {
  const drawer = document.getElementById('planDetailsDrawer');
  drawer.classList.remove('open');
  drawer.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = reviewDrawerPreviousBodyOverflow;
  if (reviewDrawerReturnFocus && reviewDrawerReturnFocus.isConnected && !reviewDrawerReturnFocus.closest('[hidden]')) {
    reviewDrawerReturnFocus.focus();
  }
  reviewDrawerReturnFocus = null;
}

function initPlanDetailsDrawer() {
  const drawer = document.getElementById('planDetailsDrawer');
  const actions = document.getElementById('planDetailsActions');
  if (!drawer || !actions) return;

  document.querySelectorAll('.ip-review-summary-row').forEach(row => {
    row.addEventListener('click', event => {
      if (event.target.closest('button, a, input, select, textarea')) return;
      openPlanDetailsDrawer(JSON.parse(row.dataset.planDetail), row);
    });
    row.addEventListener('keydown', event => {
      if ((event.key !== 'Enter' && event.key !== ' ') || event.target !== row) return;
      event.preventDefault();
      openPlanDetailsDrawer(JSON.parse(row.dataset.planDetail), row);
    });
  });

  document.querySelectorAll('[data-view-plan]').forEach(button => {
    button.addEventListener('click', event => {
      event.stopPropagation();
      const row = button.closest('.ip-review-summary-row');
      if (row) openPlanDetailsDrawer(JSON.parse(row.dataset.planDetail), button);
    });
  });

  actions.addEventListener('click', event => {
    const button = event.target.closest('[data-drawer-action]');
    if (!button || !currentReviewPlan) return;

    switch (button.dataset.drawerAction) {
      case 'return': {
        const returnFocus = reviewDrawerReturnFocus;
        closePlanDetailsDrawer();
        openReturnRemarksModal(button, returnFocus);
        break;
      }
      case 'approve': {
        const planId = currentReviewPlan.plan_id;
        const returnFocus = reviewDrawerReturnFocus;
        closePlanDetailsDrawer();
        openPlanActionConfirm('coordinator_approve_improvement_plan', planId, returnFocus);
        break;
      }
      case 'validate': {
        const planId = currentReviewPlan.plan_id;
        const returnFocus = reviewDrawerReturnFocus;
        closePlanDetailsDrawer();
        openPlanActionConfirm('coordinator_validate_improvement_plan', planId, returnFocus);
        break;
      }
      case 'close':
        closePlanDetailsDrawer();
        break;
    }
  });

  drawer.addEventListener('click', event => {
    if (event.target === drawer) closePlanDetailsDrawer();
  });

  document.addEventListener('keydown', event => {
    if (!drawer.classList.contains('open')) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closePlanDetailsDrawer();
      return;
    }
    if (event.key !== 'Tab') return;

    const focusable = [...drawer.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
      .filter(element => element.offsetParent !== null);
    if (!focusable.length) {
      event.preventDefault();
      const panel = drawer.querySelector('.ip-review-drawer');
      if (panel) panel.focus();
      return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !drawer.contains(document.activeElement))) {
      event.preventDefault();
      first.focus();
    }
  });
}
document.addEventListener('DOMContentLoaded', initPlanDetailsDrawer);

async function postPlanAction(action, planId, remarks = '') {
  const body = new URLSearchParams({action, plan_id: planId, remarks});
  const response = await fetch('dashboard.php', {method: 'POST', body});
  const data = await response.json();
  if (!data.ok) throw new Error(data.msg || 'Action failed.');
  return data;
}
let returnPlanId = null;
let returnModalReturnFocus = null;

function openReturnRemarksModal(button, returnFocus = document.activeElement) {
  returnPlanId = button.dataset.id;
  returnModalReturnFocus = returnFocus;
  document.getElementById('returnPlanContext').textContent =
    `${button.dataset.dimension} · Indicator ${button.dataset.indicator}`;
  document.getElementById('returnRemarksForm').reset();
  document.getElementById('returnRemarksError').textContent = '';
  document.getElementById('returnRemarksModal').style.display = 'flex';
  document.getElementById('returnRemarksInput').focus();
}

function closeReturnRemarksModal() {
  const modal = document.getElementById('returnRemarksModal');
  if (modal.dataset.submitting === 'true') return;
  modal.style.display = 'none';
  returnPlanId = null;
  if (returnModalReturnFocus && returnModalReturnFocus.isConnected && !returnModalReturnFocus.closest('[hidden]')) {
    returnModalReturnFocus.focus();
  }
  returnModalReturnFocus = null;
}

document.getElementById('returnRemarksModal').addEventListener('click', event => {
  if (event.target === event.currentTarget) closeReturnRemarksModal();
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') {
    closeReturnRemarksModal();
    closePlanActionConfirm();
  }
});

document.getElementById('returnRemarksForm').addEventListener('submit', async event => {
  event.preventDefault();
  const form = event.currentTarget;
  const remarksInput = document.getElementById('returnRemarksInput');
  const error = document.getElementById('returnRemarksError');
  const sendButton = document.getElementById('sendReturnButton');
  const remarks = remarksInput.value.trim();

  if (!remarks) {
    remarksInput.setCustomValidity('Please enter return remarks.');
    form.reportValidity();
    remarksInput.addEventListener('input', () => remarksInput.setCustomValidity(''), { once: true });
    return;
  }
  remarksInput.setCustomValidity('');
  if (!returnPlanId) return;

  error.textContent = '';
  sendButton.disabled = true;
  document.getElementById('returnRemarksModal').dataset.submitting = 'true';
  try {
    await postPlanAction('coordinator_return_improvement_plan', returnPlanId, remarks);
    location.reload();
  } catch (requestError) {
    error.textContent = requestError.message || 'Unable to return this plan. Please try again.';
    sendButton.disabled = false;
    delete document.getElementById('returnRemarksModal').dataset.submitting;
  }
});
let pendingPlanAction = null;
let pendingPlanId = null;
let planActionReturnFocus = null;

function openPlanActionConfirm(action, planId, returnFocus = document.activeElement) {
  pendingPlanAction = action;
  pendingPlanId = planId;
  planActionReturnFocus = returnFocus;
  document.getElementById('planActionConfirmTitle').textContent =
    action === 'coordinator_approve_improvement_plan' ? 'Approve this Improvement Plan?' : 'Validate this Improvement Plan?';
  document.getElementById('planActionConfirmMessage').textContent =
    action === 'coordinator_approve_improvement_plan'
      ? 'It will move to the separate Validate step.'
      : 'This will finalize the Improvement Plan.';
  document.getElementById('planActionConfirmError').textContent = '';
  const confirmButton = document.getElementById('confirmPlanActionButton');
  confirmButton.textContent = action === 'coordinator_approve_improvement_plan' ? 'Approve' : 'Validate';
  confirmButton.disabled = false;
  document.getElementById('planActionConfirmModal').style.display = 'flex';
  confirmButton.focus();
}

function closePlanActionConfirm() {
  const modal = document.getElementById('planActionConfirmModal');
  if (modal.dataset.submitting === 'true') return;
  modal.style.display = 'none';
  pendingPlanAction = null;
  pendingPlanId = null;
  if (planActionReturnFocus && planActionReturnFocus.isConnected && !planActionReturnFocus.closest('[hidden]')) {
    planActionReturnFocus.focus();
  }
  planActionReturnFocus = null;
}

function openApproveAllConfirm(planCount, returnFocus = document.activeElement) {
  pendingPlanAction = 'coordinator_approve_all_improvement_plans';
  pendingPlanId = null;
  planActionReturnFocus = returnFocus;
  document.getElementById('planActionConfirmTitle').textContent = 'Approve all plans?';
  document.getElementById('planActionConfirmMessage').textContent =
    `Approve all ${planCount} plans currently in the Review queue? Each plan will still require separate validation.`;
  document.getElementById('planActionConfirmError').textContent = '';
  const confirmButton = document.getElementById('confirmPlanActionButton');
  confirmButton.textContent = 'Approve All';
  confirmButton.disabled = false;
  document.getElementById('planActionConfirmModal').style.display = 'flex';
  confirmButton.focus();
}

function openValidateAllConfirm(planCount, returnFocus = document.activeElement) {
  pendingPlanAction = 'coordinator_validate_all_improvement_plans';
  pendingPlanId = null;
  planActionReturnFocus = returnFocus;
  document.getElementById('planActionConfirmTitle').textContent = 'Validate all plans?';
  document.getElementById('planActionConfirmMessage').textContent =
    `Validate all ${planCount} approved plans? This will finalize them.`;
  document.getElementById('planActionConfirmError').textContent = '';
  const confirmButton = document.getElementById('confirmPlanActionButton');
  confirmButton.textContent = 'Validate All';
  confirmButton.disabled = false;
  document.getElementById('planActionConfirmModal').style.display = 'flex';
  confirmButton.focus();
}

document.getElementById('planActionConfirmModal').addEventListener('click', event => {
  if (event.target === event.currentTarget) closePlanActionConfirm();
});

async function confirmPlanAction() {
  if (!pendingPlanAction) return;
  const isBulkApproval = pendingPlanAction === 'coordinator_approve_all_improvement_plans';
  const isBulkValidation = pendingPlanAction === 'coordinator_validate_all_improvement_plans';
  if (!isBulkApproval && !isBulkValidation && !pendingPlanId) return;
  const modal = document.getElementById('planActionConfirmModal');
  const confirmButton = document.getElementById('confirmPlanActionButton');
  const error = document.getElementById('planActionConfirmError');
  confirmButton.disabled = true;
  modal.dataset.submitting = 'true';
  error.textContent = '';
  try {
    if (isBulkApproval || isBulkValidation) {
      const response = await fetch('dashboard.php', {
        method: 'POST',
        body: new URLSearchParams({action: pendingPlanAction}),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        throw new Error(data.msg || `Unable to ${isBulkApproval ? 'approve' : 'validate'} all plans.`);
      }
    } else {
      await postPlanAction(pendingPlanAction, pendingPlanId);
    }
    location.reload();
  } catch (requestError) {
    error.textContent = requestError.message || 'Unable to update this plan. Please try again.';
    confirmButton.disabled = false;
    delete modal.dataset.submitting;
    modal.dataset.submitting = 'false';
  }
}

const approveAllPlansButton = document.getElementById('approveAllPlansButton');
if (approveAllPlansButton) {
  approveAllPlansButton.addEventListener('click', async () => {
    const planCount = Number(approveAllPlansButton.dataset.count);
    if (!Number.isInteger(planCount) || planCount < 1) return;
    openApproveAllConfirm(planCount, approveAllPlansButton);
  });
}

const validateAllPlansButton = document.getElementById('validateAllPlansButton');
if (validateAllPlansButton) {
  validateAllPlansButton.addEventListener('click', () => {
    const planCount = Number(validateAllPlansButton.dataset.count);
    if (!Number.isInteger(planCount) || planCount < 1) return;
    openValidateAllConfirm(planCount, validateAllPlansButton);
  });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
