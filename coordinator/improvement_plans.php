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
    $planQ = $db->prepare("SELECT ip.*, d.dimension_no, d.dimension_name, i.indicator_code, i.indicator_text,
      vu.full_name AS validated_by_name
        FROM improvement_plans ip
        JOIN sbm_dimensions d ON d.dimension_id = ip.dimension_id
        LEFT JOIN sbm_indicators i ON i.indicator_id = ip.indicator_id
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

$pageTitle = 'SH Improvement Plans';
$activePage = 'improvement_plans.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <span class="card-title">Improvement Plan Review Queue</span>
  </div>
  <?php if (!$plans): ?>
    <div class="card-body" style="padding:28px;color:var(--n-500);font-size:13px;">No improvement plans are available for the current school year.</div>
  <?php else: ?>
    <div class="tbl-wrap" style="overflow-x:auto;">
      <table style="min-width:1320px;">
        <thead>
          <tr>
            <th>Review Stage</th>
            <th>Dimension</th>
            <th>Indicator</th>
            <th>Priority</th>
            <th style="min-width:90px;">Plan</th>
            <th>Target</th>
            <th>Responsible Person</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($plans as $plan):
            $status = $plan['workflow_status'];
            $canReview = in_array($status, [IP_STATUS_SUBMITTED, IP_STATUS_RESUBMITTED], true);
            $canValidate = $status === IP_STATUS_APPROVED;
            $isEditing = (int) ($_GET['edit'] ?? 0) === (int) $plan['plan_id'];
            $history = $historyByPlan[$plan['plan_id']] ?? [];
        ?>
          <?php if ($isEditing): ?>
          <tr style="background:var(--brand-50);">
            <form id="plan-edit-<?= (int) $plan['plan_id'] ?>" class="plan-edit-form" data-plan-id="<?= (int) $plan['plan_id'] ?>">
              <td><strong><?= e(ipStatusLabel($status)) ?></strong><br><small>Will return to School Head on save.</small></td>
              <td>D<?= (int) $plan['dimension_no'] ?><br><small><?= e($plan['dimension_name']) ?></small></td>
              <td><?= e($plan['indicator_code'] ?: 'General') ?></td>
              <td><textarea name="objective" required class="form-control" rows="5"><?= e($plan['objective']) ?></textarea></td>
              <td><textarea name="strategy" required class="form-control" rows="5"><?= e($plan['strategy']) ?></textarea></td>
              <td><input name="target_date" type="date" class="form-control ip-date-input" value="<?= e($plan['target_date']) ?>"></td>
              <td><input name="person_responsible" class="form-control" value="<?= e($plan['person_responsible']) ?>"></td>
              <td style="min-width:190px;">
                <input type="hidden" name="resources_needed" value="<?= e($plan['resources_needed']) ?>">
                <input type="hidden" name="expected_output" value="<?= e($plan['expected_output']) ?>">
                <select name="priority_level" class="form-control" style="margin-bottom:8px;"><option <?= $plan['priority_level']==='High'?'selected':'' ?>>High</option><option <?= $plan['priority_level']==='Medium'?'selected':'' ?>>Medium</option><option <?= $plan['priority_level']==='Low'?'selected':'' ?>>Low</option></select>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Revision remarks" required></textarea>
                <button class="btn btn-primary" type="submit" form="plan-edit-<?= (int) $plan['plan_id'] ?>" style="margin-top:8px;">Save &amp; Return</button>
                <a class="btn btn-secondary" href="improvement_plans.php" style="margin-top:8px;">Cancel</a>
              </td>
            </form>
          </tr>
          <?php else: ?>
          <tr class="ip-plan-row">
            <td>
              <span class="ip-stage-label"><?= e(ipStatusLabel($status)) ?></span><br>
              <small><?= $status === IP_STATUS_RETURNED ? 'School Head' : ($status === IP_STATUS_FINALIZED ? 'Completed' : 'SBM Coordinator') ?></small>
                <?php if ($status === IP_STATUS_FINALIZED && $plan['validated_by_name']): ?><br><small>Validated by <?= e($plan['validated_by_name']) ?><br><?= e($plan['validated_at']) ?></small><?php endif; ?>
            </td>
            <td><span class="ip-dimension-cell">D<?= (int) $plan['dimension_no'] ?></span><br><small><?= e($plan['dimension_name']) ?></small></td>
            <td><span class="ip-indicator-cell"><?= e($plan['indicator_code'] ?: 'General') ?></span><br><small><?= e(mb_strimwidth($plan['indicator_text'] ?: '', 0, 72, '…')) ?></small></td>
            <td><span class="priority-pill priority-<?= e(strtolower($plan['priority_level'])) ?>"><?= e($plan['priority_level']) ?></span></td>
            <td>
              <button type="button" class="full-plan-trigger" onclick='openFullPlanModal(<?= json_encode([
                "objective" => $plan["objective"],
                "strategy" => $plan["strategy"],
                "resources" => $plan["resources_needed"] ?: "—",
                "expectedOutput" => $plan["expected_output"] ?: "—",
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>View Plan</button>
            </td>
            <td><?= $plan['target_date'] ? e(date('M j, Y', strtotime($plan['target_date']))) : '—' ?></td>
            <td><?= e($plan['person_responsible'] ?: '—') ?></td>
            <td style="min-width:190px;">
              <?php if ($canReview): ?><a class="btn btn-secondary btn-sm" title="Edit the plan and return it with your revision remarks." href="?edit=<?= (int) $plan['plan_id'] ?>">Edit &amp; Return</a><small class="action-help">Changes fields, then sends it to School Head.</small><?php endif; ?>
              <?php if ($canReview): ?><details class="return-panel"><summary class="btn btn-secondary btn-sm" title="Return the plan without changing its content.">Return</summary><textarea class="return-remarks form-control" rows="2" placeholder="Required return remarks"></textarea><button class="btn btn-secondary btn-sm submit-return" data-id="<?= (int) $plan['plan_id'] ?>">Send Return</button><small class="action-help">Sends it back unchanged for review.</small></details><?php endif; ?>
              <?php if ($canReview): ?><button class="btn btn-success btn-sm approve-plan" title="Approve means the content is accepted and ready for the separate validation step." data-id="<?= (int) $plan['plan_id'] ?>">Approve</button><?php endif; ?>
              <?php if ($canValidate): ?><button class="btn btn-success btn-sm validate-plan" title="Validate records the final validation event and moves the plan to Finalized." data-id="<?= (int) $plan['plan_id'] ?>">Validate</button><?php endif; ?>
              <button type="button" class="history-trigger" aria-expanded="false" onclick='openHistoryPopover(this, <?= json_encode(array_map(static function ($item) {
                return [
                  "version" => (int) $item["version_no"],
                  "action" => ipHistoryActionLabel($item["action"]),
                  "actor" => $item["actor_name"],
                  "timestamp" => $item["created_at"],
                  "remarks" => $item["remarks"] ?: "",
                ];
              }, $history), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>History (<?= count($history) ?>)</button>
            </td>
          </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div id="historyPopover" class="history-popover" role="tooltip" aria-hidden="true">
  <div id="historyPopoverList" class="history-popover-list"></div>
</div>

<div id="fullPlanModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="fullPlanModalTitle">
  <div class="modal-content full-plan-modal-content">
    <div class="modal-form-side">
      <div class="modal-header">
        <div class="modal-title" id="fullPlanModalTitle">Full Improvement Plan</div>
        <button type="button" class="btn btn-ghost" style="padding:4px;" onclick="closeFullPlanModal()" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body full-plan-modal-body">
        <div class="full-plan-modal-section"><strong>Objective</strong><div id="fullPlanObjective"></div></div>
        <div class="full-plan-modal-section"><strong>Strategy</strong><div id="fullPlanStrategy"></div></div>
        <div class="full-plan-modal-section"><strong>Resources</strong><div id="fullPlanResources"></div></div>
        <div class="full-plan-modal-section"><strong>Expected Output</strong><div id="fullPlanExpectedOutput"></div></div>
      </div>
      <div class="modal-footer">
        <div style="flex:1"></div>
        <button type="button" class="btn-secondary" onclick="closeFullPlanModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
 .modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(0,0,0,.4);backdrop-filter:blur(2px);z-index:2000}.modal-content{width:600px;max-width:calc(100vw - 40px);background:#fff;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.2);overflow:hidden;display:flex;flex-direction:column;animation:modalSlideUp .3s ease-out}.modal-form-side{display:flex;flex-direction:column;max-height:85vh}.modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:#f8fafc;border-bottom:1px solid var(--n-200)}.modal-title{font-size:16px;font-weight:700;color:var(--n-900)}.modal-body{padding:20px;overflow-y:auto;max-height:calc(100vh - 200px)}.modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid var(--n-200)}@keyframes modalSlideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
 .history-trigger{display:block;margin-top:8px;padding:0;border:0;background:none;color:var(--n-700);font:inherit;font-size:11px;cursor:pointer;text-align:left}.history-trigger:hover{text-decoration:underline}.history-popover{position:fixed;display:none;width:300px;max-width:calc(100vw - 24px);max-height:300px;overflow-y:auto;padding:8px 10px;background:#fff;border:1px solid var(--n-200);border-radius:6px;box-shadow:0 8px 24px rgba(15,23,42,.14);z-index:2100}.history-popover-list{font-size:11px;line-height:1.4}.history-popover-entry{padding:7px 0;border-bottom:1px solid var(--n-200)}.history-popover-entry:last-child{border-bottom:0}.history-popover-entry.current{position:relative;padding-left:12px}.history-popover-entry.current::before{content:"";position:absolute;left:1px;top:12px;width:5px;height:5px;border-radius:50%;background:var(--n-700)}.history-popover-version{font-weight:700;color:var(--n-900)}.history-popover-meta{color:var(--n-500)}.history-popover-remarks{margin-top:2px;color:var(--n-500);white-space:pre-wrap}
 .full-plan-trigger{display:block;margin-top:6px;padding:0;border:0;background:none;color:var(--n-700);font:inherit;font-size:11px;cursor:pointer;text-align:left;white-space:nowrap}.full-plan-trigger:hover{text-decoration:underline}.full-plan-modal-content{max-width:620px}.full-plan-modal-body{font-size:13.5px;line-height:1.6;color:var(--n-800)}.full-plan-modal-section{margin-bottom:16px}.full-plan-modal-section:last-child{margin-bottom:0}.full-plan-modal-section strong{display:block;margin-bottom:4px;color:var(--n-900);font-weight:700}.full-plan-modal-section div{white-space:pre-wrap;font-weight:400}
thead th{text-transform:none;font-size:11px;font-weight:500;letter-spacing:0.01em}.ip-status-badge{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:700;background:#fef3c7;color:#b45309;white-space:nowrap}.ip-status-badge.finalized{background:#dcfce7;color:#15803d}.ip-status-badge.approved{background:#dbeafe;color:#1d4ed8}.ip-status-badge.returned{background:#ffedd5;color:#c2410c}.btn-sm{padding:5px 9px;font-size:11px;margin:2px 0}.action-help{display:block;color:var(--n-500);font-size:10px;line-height:1.3;margin:0 0 5px}.priority-pill{display:inline-flex;padding:3px 7px;border-radius:999px;font-size:11px;font-weight:600;box-shadow:none;line-height:1.3}.priority-high{background:#fef2f2;color:#b91c1c}.priority-medium{background:#fef3c7;color:#b45309}.priority-low{background:#e0f2fe;color:#0369a1}.plan-clamp{max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:4px 0 8px;color:var(--n-700);font-weight:400}.plan-detail{display:grid;gap:11px}.plan-detail-row{margin:0}.plan-detail-label{display:block;font-size:11px;font-weight:700;color:var(--n-700);text-transform:none;letter-spacing:0.01em}.plan-detail-copy{display:block;color:var(--n-700);font-weight:400;line-height:1.55}.full-plan summary{cursor:pointer;color:var(--n-700);font-size:11px;margin-top:6px}.full-plan div{max-width:340px;padding:10px;background:var(--n-50);border-radius:6px;margin-top:5px}.full-plan p{margin:3px 0 9px;line-height:1.45}.full-plan .plan-detail-block{margin:0 0 9px}.full-plan .plan-detail-copy{margin-top:4px}.history-summary{cursor:pointer;color:var(--n-700);font-size:11px}.history-panel{margin-top:8px}.plan-history-list{margin-top:7px;font-size:11px;line-height:1.45}.history-item{padding:6px 0;border-bottom:1px solid var(--n-200);color:var(--n-500)}.history-current{border-left:2px solid var(--n-500);padding-left:7px;color:var(--n-800);background:transparent}.history-muted{color:var(--n-500)}.history-person{color:var(--n-500)}.history-remark{color:var(--n-600)}.history-version{font-weight:700;color:var(--n-900)}.history-current .history-version{font-weight:700}.ip-plan-row{background:#fff}.ip-plan-row td{border-top:1px solid var(--n-200)}.ip-plan-row .ip-dimension-cell,.ip-plan-row .ip-indicator-cell{font-size:12px;font-weight:700;color:var(--n-800);text-decoration:none}.ip-plan-row .ip-indicator-cell{color:var(--n-800)}.ip-stage-label{font-size:11px;color:var(--n-800);font-weight:600;white-space:nowrap}.ip-date-input{min-width:142px;color-scheme:light}.plan-detail-label,.plan-detail-block .plan-detail-label{font-weight:700}.plan-detail-copy{font-weight:400}.history-current strong{font-weight:700}.history-muted strong{font-weight:600}.history-item .history-person,.history-item .history-remark{font-weight:400}
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

function openFullPlanModal(plan) {
  document.getElementById('fullPlanObjective').textContent = plan.objective || '—';
  document.getElementById('fullPlanStrategy').textContent = plan.strategy || '—';
  document.getElementById('fullPlanResources').textContent = plan.resources || '—';
  document.getElementById('fullPlanExpectedOutput').textContent = plan.expectedOutput || '—';
  document.getElementById('fullPlanModal').style.display = 'flex';
}

function closeFullPlanModal() {
  document.getElementById('fullPlanModal').style.display = 'none';
}

async function postPlanAction(action, planId, remarks = '') {
  const body = new URLSearchParams({action, plan_id: planId, remarks});
  const response = await fetch('dashboard.php', {method: 'POST', body});
  const data = await response.json();
  if (!data.ok) throw new Error(data.msg || 'Action failed.');
  return data;
}
document.querySelectorAll('.plan-edit-form').forEach(form => form.addEventListener('submit', async event => {
  event.preventDefault();
  const data = new FormData(form);
  data.append('action', 'coordinator_update_improvement_plan');
  data.append('plan_id', form.dataset.planId);
  try { const response = await fetch('dashboard.php', {method:'POST', body:data}); const result = await response.json(); if (!result.ok) throw new Error(result.msg); location.href = 'improvement_plans.php'; } catch (error) { alert(error.message); }
}));
document.querySelectorAll('.submit-return').forEach(button => button.addEventListener('click', async () => {
  const remarks = button.closest('.return-panel').querySelector('.return-remarks').value.trim();
  if (!remarks) { alert('Return remarks are required.'); return; }
  try { await postPlanAction('coordinator_return_improvement_plan', button.dataset.id, remarks); location.reload(); } catch (error) { alert(error.message); }
}));
document.querySelectorAll('.approve-plan').forEach(button => button.addEventListener('click', async () => {
  if (!confirm('Approve this Improvement Plan? It will move to the separate Validate step.')) return;
  try { await postPlanAction('coordinator_approve_improvement_plan', button.dataset.id); location.reload(); } catch (error) { alert(error.message); }
}));
document.querySelectorAll('.validate-plan').forEach(button => button.addEventListener('click', async () => {
  if (!confirm('Validate and finalize this Improvement Plan?')) return;
  try { await postPlanAction('coordinator_validate_improvement_plan', button.dataset.id); location.reload(); } catch (error) { alert(error.message); }
}));
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
