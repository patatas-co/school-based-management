<?php
ob_start();
// ============================================================
// system_admin/external_evaluators.php — External Evaluator Management
// Manages external stakeholder evaluators + access window per cycle.
// Reuses AJAX handlers already defined in users.php (list_cycle_evaluators,
// create_temp_evaluator, remove_cycle_evaluator, set_cycle_dates,
// get_cycle_dates, resend_evaluator_invite) — no duplication of backend logic.
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/stakeholder_lifecycle.php';
requireSystemAdmin();
$db = getDB();

$pageTitle = 'External Evaluator Management';
$activePage = 'external_evaluators.php';
include __DIR__ . '/../includes/header.php';

$cycles = $db->query("
  SELECT c.cycle_id, sy.label, c.status
  FROM sbm_cycles c
  JOIN school_years sy ON c.sy_id = sy.sy_id
  WHERE c.school_id = " . SCHOOL_ID . "
  ORDER BY c.cycle_id DESC
")->fetchAll();
?>

<style>
  /* ── CUSTOM DATETIME PICKER ── */
  .dt-premium { display: none !important; }
  .dtp-trigger {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    background: #fff; border: 1.5px solid #E2E8F0; border-radius: 12px;
    font-size: 14px; font-family: 'Inter', sans-serif; color: #0F172A;
    width: 100%; cursor: pointer; transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .05); text-align: left; position: relative;
  }
  .dtp-trigger:hover { border-color: #10B981; background: #F0FDF4; }
  .dtp-trigger svg { flex-shrink: 0; stroke: #059669; }
  .dtp-trigger-text { flex: 1; font-weight: 600; color: #0F172A; }
  .dtp-trigger-text.placeholder { color: #94A3B8; font-weight: 400; }
  .dtp-popover {
    position: fixed; z-index: 9999; background: #fff; border: 1px solid #E2E8F0;
    border-radius: 16px; box-shadow: 0 20px 60px rgba(15, 23, 42, .18), 0 4px 16px rgba(15, 23, 42, .08);
    width: 520px; max-width: 96vw; overflow: hidden; display: none; flex-direction: column;
  }
  .dtp-popover.open { display: flex; }
  .dtp-body { display: flex; height: 340px; }
  .dtp-cal { flex: 1; padding: 18px 18px 0; display: flex; flex-direction: column; border-right: 1px solid #F1F5F9; }
  .dtp-cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
  .dtp-cal-nav button { width: 28px; height: 28px; border: none; background: none; cursor: pointer; border-radius: 6px; color: #64748B; display: flex; align-items: center; justify-content: center; transition: background .15s; }
  .dtp-cal-nav button:hover { background: #F1F5F9; color: #0F172A; }
  .dtp-cal-nav button svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .dtp-cal-month { font-size: 14px; font-weight: 700; color: #0F172A; }
  .dtp-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; flex: 1; }
  .dtp-cal-dow { text-align: center; font-size: 11px; font-weight: 700; color: #94A3B8; padding: 4px 0 6px; }
  .dtp-cal-day { display: flex; align-items: center; justify-content: center; height: 34px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #0F172A; cursor: pointer; transition: background .12s, color .12s; border: none; background: none; }
  .dtp-cal-day:hover:not(.disabled):not(.selected) { background: #F1F5F9; }
  .dtp-cal-day.other-month { color: #CBD5E1; pointer-events: none; }
  .dtp-cal-day.today:not(.selected) { color: #10B981; font-weight: 800; }
  .dtp-cal-day.selected { background: #0F172A; color: #fff; font-weight: 700; border-radius: 8px; }
  .dtp-cal-day.disabled { color: #CBD5E1; pointer-events: none; cursor: default; }
  .dtp-time { width: 130px; overflow-y: auto; padding: 10px 8px; display: flex; flex-direction: column; gap: 3px; scrollbar-width: thin; scrollbar-color: #E2E8F0 transparent; }
  .dtp-time::-webkit-scrollbar { width: 4px; }
  .dtp-time::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 4px; }
  .dtp-time-slot { display: flex; align-items: center; justify-content: center; padding: 9px 0; border-radius: 10px; font-size: 14px; font-weight: 600; color: #374151; cursor: pointer; border: 1.5px solid #F1F5F9; background: #fff; transition: all .12s; flex-shrink: 0; }
  .dtp-time-slot:hover:not(.selected) { background: #F8FAFC; border-color: #E2E8F0; }
  .dtp-time-slot.disabled { color: #CBD5E1; pointer-events: none; border-color: transparent; cursor: not-allowed; }
  .dtp-time-slot.selected { background: #10B981; color: #fff; border-color: #10B981; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
  .dtp-confirm { padding: 16px 20px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; }
  .dtp-confirm-text { font-size: 13.5px; color: #64748B; }
  .dtp-confirm-text strong { color: #0F172A; font-weight: 700; }
  .dtp-confirm-btn { padding: 9px 20px; background: #0F172A; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.1s; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.1); }
  .dtp-confirm-btn:hover { background: #1E293B; transform: translateY(-1px); }
  .dtp-confirm-btn:active { transform: translateY(0); }

  /* ── CSV IMPORT ── */
  .import-card { border: 1px solid var(--n-200); border-radius: 16px; background: #fff; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
  .import-card.drag-over { border-color: var(--brand-500); background: var(--brand-50); transform: scale(1.01); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
  .import-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: var(--n-50); border-bottom: 1px solid var(--n-100); gap: 12px; }
  .import-icon-wrap { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, var(--brand-600), #059669); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25); }
  .import-schema { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
  .import-col { font-size: 11px; font-family: var(--font-mono); padding: 2px 8px; background: #fff; border: 1px solid var(--n-200); border-radius: 6px; color: var(--n-600); font-weight: 600; }
  .import-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
  .import-drop-zone { border: 2px dashed var(--n-200); border-radius: 14px; padding: 32px 20px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: var(--n-30); display: block; position: relative; overflow: hidden; }
  .import-drop-zone:hover { border-color: var(--brand-400); background: var(--brand-50); }
  .import-placeholder-state { display: block; }
  .has-file .import-placeholder-state { display: none; }
  .import-file-pill { display: none; align-items: center; gap: 12px; padding: 12px; background: #fff; border: 1px solid var(--brand-200); border-radius: 12px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1); animation: slideInUp 0.3s ease; }
  .has-file .import-file-pill { display: flex; }
  @keyframes slideInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .import-btn { width: 100%; height: 48px; display: flex; align-items: center; justify-content: center; gap: 10px; border: none; border-radius: 12px; background: linear-gradient(135deg, var(--brand-600), #059669); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
  .import-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3); filter: brightness(1.05); }
  .import-btn:active { transform: translateY(0); }
  .import-btn:disabled { background: var(--n-200); color: var(--n-400); cursor: not-allowed; box-shadow: none; transform: none; }
  .import-download-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--brand-600); text-decoration: none; transition: all 0.2s ease; padding: 4px 8px; border-radius: 6px; }
  .import-download-link:hover { background: var(--brand-50); text-decoration: underline; }

  /* ── MANUAL ENTRY ── */
  .manual-entry-card { background: var(--n-50); border: 1px solid var(--n-200); border-radius: 14px; overflow: hidden; transition: all 0.3s ease; }
  .manual-entry-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; cursor: pointer; user-select: none; transition: background 0.2s ease; }
  .manual-entry-header:hover { background: var(--n-100); }
  .manual-entry-card.is-expanded { background: #fff; border-color: var(--brand-200); box-shadow: var(--shadow-sm); }
  .manual-entry-content { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0, 1, 0, 1); padding: 0 20px; }
  .is-expanded .manual-entry-content { max-height: 500px; transition: max-height 0.4s cubic-bezier(1, 0, 1, 0); padding: 0 20px 20px; }
  .chevron-icon { transition: transform 0.3s ease; color: var(--n-400); }
  .is-expanded .chevron-icon { transform: rotate(180deg); color: var(--brand-600); }

  /* ── PAGE-SPECIFIC ── */
  .section-card { background: #fff; border: 1px solid var(--n-150, #e5e7eb); border-radius: 16px; padding: 22px; margin-bottom: 20px; box-shadow: var(--shadow-sm); }
  .section-eyebrow { font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--n-500); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
  .section-title-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
  .section-heading { font-size: 15px; font-weight: 800; color: var(--n-900); }
  .window-status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .ev-table th { text-align: left; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--n-500); padding: 12px 16px; border-bottom: 1px solid var(--n-100); }
  .ev-table td { padding: 12px 16px; border-bottom: 1px solid var(--n-100); font-size: 13.5px; color: var(--n-800); vertical-align: middle; }
  .ev-table tr:last-child td { border-bottom: none; }
  .ev-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: #fff; flex-shrink: 0; }
  .status-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
</style>

<!-- ── Section 1: Assessment Cycle & Access Window ── -->
<div class="section-card">
  <div class="section-title-row">
    <div>
      <div class="section-heading">Assessment Cycle &amp; Access Window</div>
    </div>
  </div>

  <div class="fg">
    <label>Assessment Cycle *</label>
    <div class="p-select p-select-fluid" id="pCycleDropdown">
      <input type="hidden" id="ev_cycle_id">
      <div class="p-select-trigger" onclick="togglePSelect(event, 'pCycleDropdown')">
        <span class="p-select-val" id="pCycleLabel">Select a cycle</span>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--n-400)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </div>
      <div class="p-select-menu">
        <?php foreach ($cycles as $cyc):
          $label = "SY " . e($cyc['label']) . " — " . ucfirst(str_replace('_', ' ', $cyc['status']));
          $desc = ($cyc['status'] === 'open') ? "Currently assessing school year" : "Historical assessment records";
          ?>
          <div class="p-select-item" onclick="setMCycle('<?= $cyc['cycle_id'] ?>', '<?= e($label) ?>')">
            <div class="p-item-content">
              <div class="p-item-title"><?= e($label) ?></div>
              <div class="p-item-desc"><?= $desc ?></div>
            </div>
            <div class="p-item-check"><?= svgIcon('check', '', 'width:16px;height:16px;') ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div id="cycleDatesCard" style="display:none;">
    <div style="background:var(--brand-50);border:1px solid var(--brand-200);border-radius:16px;padding:20px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
          <div class="section-eyebrow" style="color:var(--brand-700);"><div style="width:6px;height:6px;border-radius:50%;background:var(--brand-600);"></div>Stakeholder Access Window</div>
          <div id="cycleStatusBanner" style="font-size:14px;font-weight:700;color:var(--n-900);"></div>
        </div>
        <button class="btn btn-primary" onclick="saveCycleDates()" style="padding:7px 14px;font-size:12.5px;border-radius:9px;box-shadow:0 4px 12px rgba(22, 163, 74, .2);">Save Access Window</button>
      </div>

      <div class="form-row">
        <div>
          <label style="color:var(--n-600);font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            Opening Date &amp; Time
          </label>
          <input type="date" id="ev_start_d" class="dt-premium">
          <input type="time" id="ev_start_t" class="dt-premium">
          <button type="button" class="dtp-trigger" onclick="dtpOpen('start')">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <span class="dtp-trigger-text placeholder" id="dtp_start_label">Pick opening date &amp; time</span>
          </button>
        </div>
        <div>
          <label style="color:var(--n-600);font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            Closing Date &amp; Time
          </label>
          <input type="date" id="ev_end_d" class="dt-premium">
          <input type="time" id="ev_end_t" class="dt-premium">
          <button type="button" class="dtp-trigger" onclick="dtpOpen('end')">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <span class="dtp-trigger-text placeholder" id="dtp_end_label">Pick closing date &amp; time</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="cycleNotConfigured" style="text-align:center;padding:24px 16px;color:var(--n-400);font-size:13px;">
    Select an assessment cycle above to configure its stakeholder access window.
  </div>
</div>

<!-- ── Section 2: Add External Evaluators ── -->
<div class="section-card">
  <div class="section-title-row">
    <div>
      <div class="section-heading">Add External Evaluators</div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="import-card" id="evCsvDrop">
      <div class="import-head">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="import-icon-wrap">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><path d="M12 18v-6" /><path d="M9 15h6" />
            </svg>
          </div>
          <div>
            <div style="font-size:14px;font-weight:800;color:var(--n-900);line-height:1.2;">Bulk Import</div>
            <div style="font-size:11px;color:var(--n-500);margin-top:2px;">Upload CSV to add evaluators</div>
          </div>
        </div>
        <div class="import-schema">
          <span class="import-col">full_name</span>
          <span class="import-col">email</span>
        </div>
      </div>

      <div class="import-body">
        <label for="evCsvFile" class="import-drop-zone" id="evCsvDropZone">
          <input type="file" id="evCsvFile" accept=".csv" style="display:none;" onchange="handleEvCsvSelect(this)">
          <div class="import-placeholder-state">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--brand-50);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;border:1px solid var(--brand-100);">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--brand-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
              </svg>
            </div>
            <div style="font-size:14px;font-weight:700;color:var(--n-800);margin-bottom:4px;">Drop your CSV here</div>
            <div style="font-size:12px;color:var(--n-400);">or <span style="color:var(--brand-600);font-weight:600;text-decoration:underline;">click to browse</span></div>
          </div>
          <div class="import-file-pill" id="evCsvFilePill">
            <div style="width:32px;height:32px;border-radius:8px;background:var(--brand-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--brand-700)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" />
              </svg>
            </div>
            <div style="flex:1;min-width:0;text-align:left;">
              <div id="evCsvFileName" style="font-size:13px;font-weight:700;color:var(--n-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
              <div id="evCsvFileSize" style="font-size:11px;color:var(--n-500);"></div>
            </div>
            <button type="button" onclick="event.preventDefault(); clearEvCsv();" style="background:var(--n-100);border:none;cursor:pointer;padding:6px;border-radius:6px;color:var(--n-500);line-height:0;transition:all .2s;" title="Remove file">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
        </label>

        <button class="import-btn" id="evCsvImportBtn" onclick="importStakeholderCsv()" disabled>
          <?= svgIcon('send') ?> Import &amp; Send Invites
        </button>

        <div style="text-align:center;">
          <a href="users.php?action=download_template" class="import-download-link">
            <?= svgIcon('download') ?> Download CSV Template
          </a>
        </div>
      </div>
    </div>

    <div class="manual-entry-card" id="manualEntryCard">
      <div class="manual-entry-header" onclick="toggleManualEntry()">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:32px;height:32px;border-radius:8px;background:var(--n-100);display:flex;align-items:center;justify-content:center;color:var(--n-600);" id="manualIconWrap">
            <?= svgIcon('plus') ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:800;color:var(--n-800);line-height:1;">Add Evaluator Manually</div>
            <div style="font-size:11px;color:var(--n-400);margin-top:2px;">Add one external stakeholder using name and email</div>
          </div>
        </div>
        <div class="chevron-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </div>
      </div>
      <div class="manual-entry-content">
        <div style="padding-top:10px;">
          <div class="form-row">
            <div class="fg" style="margin-bottom:0;">
              <label>Full Name *</label>
              <input class="fc" id="ev_name" placeholder="e.g. Juan dela Cruz">
            </div>
            <div class="fg" style="margin-bottom:0;">
              <label>Email Address *</label>
              <input class="fc" type="email" id="ev_email" placeholder="evaluator@email.com">
            </div>
          </div>
          <div style="margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <button class="btn btn-primary btn-sm" onclick="addEvaluator()" style="padding:8px 16px;">
              <?= svgIcon('user-plus') ?> Add &amp; Send Invite
            </button>
            <div style="display:flex;align-items:center;gap:6px;color:var(--n-400);font-size:11px;">
              <?= svgIcon('info') ?>
              <span>System will email setup instructions.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Section 3: External Evaluator List ── -->
<div class="card" style="box-shadow:none;border:1px solid var(--n-150,#e5e7eb);">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--n-100,#f1f5f9);flex-wrap:wrap;">
    <div>
      <div class="section-heading">External Evaluator List</div>
    </div>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-danger btn-sm" onclick="deactivateAllEvaluators()" id="deactivateAllBtn" style="display:none;">
        <?= svgIcon('x') ?> Deactivate All
      </button>
      <button class="btn btn-primary btn-sm" onclick="openReactivationModal()" id="reactivateAllBtn" style="display:none;background:#2563EB;">
        <?= svgIcon('refresh') ?> Reactivate All
      </button>
    </div>
  </div>

  <div id="evaluatorListWrap" style="padding:20px;">
    <div style="text-align:center;padding:40px 20px;color:var(--n-400);font-size:13px;background:var(--n-50);border:2px dashed var(--n-200);border-radius:14px;display:flex;flex-direction:column;align-items:center;gap:12px;">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--n-100);display:flex;align-items:center;justify-content:center;color:var(--n-300);">
        <?= svgIcon('users', '', 'width:24px;height:24px;') ?>
      </div>
      <div>
        <div style="font-weight:700;color:var(--n-500);">No Evaluators Loaded</div>
        <div style="font-size:12px;margin-top:2px;">Select an assessment cycle above to view assigned stakeholders.</div>
      </div>
    </div>
  </div>
</div>

<!-- Reactivation Modal -->
<div class="overlay" id="mReactivate">
  <div class="modal" style="max-width:480px;">
    <div class="modal-head">
      <span class="modal-title">Reactivate Evaluators</span>
      <button class="modal-close" onclick="closeModal('mReactivate')"><?= svgIcon('x') ?></button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom:16px;font-size:14px;color:var(--n-600);line-height:1.6;">
        This will reactivate the selected evaluator accounts. They will be able to log in again immediately.
      </div>
      <div id="deactivatedEvalsList" style="max-height:200px;overflow-y:auto;border:1px solid var(--n-200);border-radius:10px;padding:4px;margin-bottom:18px;background:var(--n-50);"></div>
      <div class="fg">
        <label style="display:flex;align-items:center;gap:6px;color:#1E40AF;font-weight:600;">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#1E40AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          Optional: Extend Access End Date
        </label>
        <input type="date" id="reactivate_end_d" class="dt-premium">
        <input type="time" id="reactivate_end_t" class="dt-premium">
        <button type="button" class="dtp-trigger" onclick="dtpOpen('reactivate')" style="margin-top:8px;border-color:#BFDBFE;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#1E40AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          <span class="dtp-trigger-text placeholder" id="dtp_reactivate_label">Pick new end date &amp; time (optional)</span>
        </button>
        <div style="margin-top:8px;font-size:11.5px;color:var(--n-400);">Leave blank to keep existing end date.</div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" onclick="closeModal('mReactivate')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmReactivate()" style="background:#2563EB;">Confirm Reactivation</button>
    </div>
  </div>
</div>

<!-- Custom DateTime Picker Popover (shared, single instance) -->
<div class="dtp-popover" id="dtpPopover">
  <div class="dtp-body">
    <div class="dtp-cal">
      <div class="dtp-cal-nav">
        <button type="button" onclick="dtpPrevMonth()"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6" /></svg></button>
        <span class="dtp-cal-month" id="dtpMonthLabel"></span>
        <button type="button" onclick="dtpNextMonth()"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6" /></svg></button>
      </div>
      <div class="dtp-cal-grid" id="dtpCalGrid"></div>
    </div>
    <div class="dtp-time" id="dtpTimeList"></div>
  </div>
  <div class="dtp-confirm">
    <div class="dtp-confirm-text" id="dtpConfirmText">Select a date and time</div>
    <button type="button" class="dtp-confirm-btn" onclick="dtpConfirm()">Continue</button>
  </div>
</div>

<script>
  // ── Custom DateTime Picker Engine (identical to users.php instance) ──
  (function () {
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    let _target = null, _year = 0, _month = 0, _selDate = null, _selTime = null;
    const popover = document.getElementById('dtpPopover');
    const grid = document.getElementById('dtpCalGrid');
    const timeList = document.getElementById('dtpTimeList');
    const monthLbl = document.getElementById('dtpMonthLabel');
    const confText = document.getElementById('dtpConfirmText');
    const cfg = {
      start: { dateId: 'ev_start_d', timeId: 'ev_start_t', labelId: 'dtp_start_label' },
      end: { dateId: 'ev_end_d', timeId: 'ev_end_t', labelId: 'dtp_end_label' },
      reactivate: { dateId: 'reactivate_end_d', timeId: 'reactivate_end_t', labelId: 'dtp_reactivate_label' },
    };
    window.dtpOpen = function (target) {
      _target = target;
      const c = cfg[target];
      const now = new Date();
      const existingDate = document.getElementById(c.dateId).value;
      const existingTime = document.getElementById(c.timeId).value;
      if (existingDate) {
        const [y, m, d] = existingDate.split('-').map(Number);
        _selDate = new Date(y, m - 1, d);
        _year = y; _month = m - 1;
      } else {
        _selDate = null;
        _year = now.getFullYear(); _month = now.getMonth();
      }
      _selTime = existingTime || null;
      renderCal(); renderTimeSlots(); updateConfirmText();
      positionPopover(event.currentTarget || document.querySelector('.dtp-trigger'));
      popover.classList.add('open');
    };
    function positionPopover(trigger) {
      const rect = trigger.getBoundingClientRect();
      const pw = 520, ph = 390;
      let left = rect.left, top = rect.bottom + 8;
      if (left + pw > window.innerWidth - 12) left = window.innerWidth - pw - 12;
      if (left < 8) left = 8;
      if (top + ph > window.innerHeight - 12) top = rect.top - ph - 8;
      popover.style.left = left + 'px'; popover.style.top = top + 'px'; popover.style.width = pw + 'px';
    }
    function renderCal() {
      monthLbl.textContent = MONTHS[_month] + ' ' + _year;
      grid.innerHTML = '';
      DOW.forEach(d => { const el = document.createElement('div'); el.className = 'dtp-cal-dow'; el.textContent = d; grid.appendChild(el); });
      const firstDay = new Date(_year, _month, 1).getDay();
      const daysInMonth = new Date(_year, _month + 1, 0).getDate();
      const daysInPrev = new Date(_year, _month, 0).getDate();
      const today = new Date(); today.setHours(0, 0, 0, 0);
      for (let i = firstDay - 1; i >= 0; i--) {
        const el = document.createElement('button'); el.type = 'button'; el.className = 'dtp-cal-day other-month'; el.textContent = daysInPrev - i; grid.appendChild(el);
      }
      for (let d = 1; d <= daysInMonth; d++) {
        const dt = new Date(_year, _month, d);
        const el = document.createElement('button'); el.type = 'button'; el.textContent = d;
        let cls = 'dtp-cal-day';
        const isPastDate = dt.getTime() < today.getTime();
        if (dt.getTime() === today.getTime()) cls += ' today';
        if (_selDate && dt.toDateString() === _selDate.toDateString()) cls += ' selected';
        if (isPastDate) cls += ' disabled';
        el.className = cls;
        if (!isPastDate) el.onclick = () => { _selDate = dt; renderCal(); renderTimeSlots(); updateConfirmText(); };
        grid.appendChild(el);
      }
      const totalCells = firstDay + daysInMonth;
      const remainder = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
      for (let d = 1; d <= remainder; d++) {
        const el = document.createElement('button'); el.type = 'button'; el.className = 'dtp-cal-day other-month'; el.textContent = d; grid.appendChild(el);
      }
    }
    function renderTimeSlots() {
      timeList.innerHTML = '';
      const slots = [];
      for (let h = 0; h < 24; h++) for (let m = 0; m < 60; m += 15) slots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
      slots.forEach(slot => {
        const el = document.createElement('div');
        const [hh, mm] = slot.split(':').map(Number);
        let isDisabled = false;
        if (_selDate) {
          const now = new Date();
          if (_selDate.toDateString() === now.toDateString()) {
            if (hh < now.getHours() || (hh === now.getHours() && mm < now.getMinutes())) isDisabled = true;
          }
        }
        let cls = 'dtp-time-slot';
        if (slot === _selTime) cls += ' selected';
        if (isDisabled) cls += ' disabled';
        el.className = cls;
        const ampm = hh < 12 ? 'AM' : 'PM';
        el.textContent = ((hh % 12) || 12) + ':' + String(mm).padStart(2, '0') + ' ' + ampm;
        if (!isDisabled) el.onclick = () => { _selTime = slot; renderTimeSlots(); updateConfirmText(); };
        timeList.appendChild(el);
      });
      const selectedEl = timeList.querySelector('.selected');
      if (selectedEl) {
        setTimeout(() => selectedEl.scrollIntoView({ block: 'center', behavior: 'smooth' }), 30);
      } else {
        const now = new Date();
        const currentSlotIdx = now.getHours() * 4;
        const all = timeList.querySelectorAll('.dtp-time-slot');
        if (all[currentSlotIdx]) setTimeout(() => all[currentSlotIdx].scrollIntoView({ block: 'center' }), 30);
      }
    }
    function updateConfirmText() {
      if (!_selDate && !_selTime) { confText.innerHTML = 'Select a date and time'; return; }
      const datePart = _selDate ? _selDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) : '—';
      const timePart = _selTime ? fmtTime12(_selTime) : '—';
      confText.innerHTML = `Your selection: <strong>${datePart} at ${timePart}</strong>`;
    }
    function fmtTime12(t) {
      const [hh, mm] = t.split(':').map(Number);
      const ampm = hh < 12 ? 'AM' : 'PM';
      return ((hh % 12) || 12) + ':' + String(mm).padStart(2, '0') + ' ' + ampm;
    }
    window.dtpPrevMonth = function () { _month--; if (_month < 0) { _month = 11; _year--; } renderCal(); };
    window.dtpNextMonth = function () { _month++; if (_month > 11) { _month = 0; _year++; } renderCal(); };
    window.dtpConfirm = function () {
      if (!_selDate || !_selTime) {
        if (!_selDate) { alert('Please select a date.'); return; }
        if (!_selTime) { alert('Please select a time slot.'); return; }
      }
      const c = cfg[_target];
      const y = _selDate.getFullYear();
      const m = String(_selDate.getMonth() + 1).padStart(2, '0');
      const d = String(_selDate.getDate()).padStart(2, '0');
      document.getElementById(c.dateId).value = `${y}-${m}-${d}`;
      document.getElementById(c.timeId).value = _selTime;
      const label = document.getElementById(c.labelId);
      if (label) {
        const datePart = _selDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        label.textContent = `${datePart}  ${fmtTime12(_selTime)}`;
        label.classList.remove('placeholder');
      }
      popover.classList.remove('open');
    };
    document.addEventListener('mousedown', function (e) {
      if (!popover.contains(e.target) && !e.target.closest('.dtp-trigger')) popover.classList.remove('open');
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') popover.classList.remove('open'); });
  })();

  function setMCycle(id, label) {
    document.getElementById('ev_cycle_id').value = id;
    document.getElementById('pCycleLabel').textContent = label;
    document.querySelectorAll('#pCycleDropdown .p-select-item').forEach(item => {
      item.classList.toggle('active', item.getAttribute('onclick').includes(`'${id}'`));
    });
    closeAllPSelects();
    document.getElementById('cycleNotConfigured').style.display = 'none';
    loadEvaluators();
  }

  function toggleManualEntry() {
    const card = document.getElementById('manualEntryCard');
    const iconWrap = document.getElementById('manualIconWrap');
    const isExpanded = card.classList.toggle('is-expanded');
    if (isExpanded) { iconWrap.style.background = 'var(--brand-100)'; iconWrap.style.color = 'var(--brand-600)'; }
    else { iconWrap.style.background = 'var(--n-100)'; iconWrap.style.color = 'var(--n-600)'; }
  }

  async function addEvaluator() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const name = document.getElementById('ev_name').value.trim();
    const email = document.getElementById('ev_email').value.trim();
    if (!cycleId) { toast('Please select a cycle first.', 'warning'); return; }
    if (!name || !email) { toast('Name and email are required.', 'warning'); return; }
    const r = await apiPost('users.php', { action: 'create_temp_evaluator', cycle_id: cycleId, full_name: name, email: email });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) {
      document.getElementById('ev_name').value = '';
      document.getElementById('ev_email').value = '';
      loadEvaluators();
    }
  }

  const avatarColors = ['#7C3AED', '#2563EB', '#059669', '#D97706', '#DB2777', '#0891B2'];
  function colorFor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
    return avatarColors[Math.abs(hash) % avatarColors.length];
  }

  let lastEvalsList = [];
  async function loadEvaluators() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const wrap = document.getElementById('evaluatorListWrap');
    const deactBtn = document.getElementById('deactivateAllBtn');
    const reactBtn = document.getElementById('reactivateAllBtn');

    if (!cycleId) {
      wrap.innerHTML = '<div style="text-align:center;padding:20px;color:var(--n-400);font-size:13px;">Select a cycle above to see evaluators.</div>';
      deactBtn.style.display = 'none';
      reactBtn.style.display = 'none';
      document.getElementById('cycleDatesCard').style.display = 'none';
      document.getElementById('cycleNotConfigured').style.display = '';
      return;
    }

    refreshCycleDates(cycleId);
    wrap.innerHTML = '<div style="text-align:center;padding:20px;color:var(--n-400);">Loading…</div>';

    const r = await apiPost('users.php', { action: 'list_cycle_evaluators', cycle_id: cycleId });
    if (!r.ok || !r.data) { wrap.innerHTML = '<div style="color:var(--red);padding:12px;">Failed to load.</div>'; return; }

    lastEvalsList = r.data;

    if (r.data.length === 0) {
      wrap.innerHTML = '<div style="text-align:center;padding:40px 20px;color:var(--n-400);font-size:13px;background:var(--n-50);border:2px dashed var(--n-200);border-radius:14px;">No evaluators added to this cycle yet. Use the options above to add one.</div>';
      deactBtn.style.display = 'none';
      reactBtn.style.display = 'none';
      return;
    }

    let hasActive = false, hasDeactivated = false;

    let html = `<div style="overflow-x:auto;"><table class="ev-table" style="width:100%;border-collapse:collapse;">
      <thead><tr>
        <th style="width:44px;"></th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Invitation Status</th>
        <th>Assessment Status</th>
        <th style="text-align:right;">Actions</th>
      </tr></thead><tbody>`;

    r.data.forEach(ev => {
      const isAutoDeactivated = ev.is_active == 0;
      if (!isAutoDeactivated) hasActive = true;
      if (isAutoDeactivated) hasDeactivated = true;

      const invBadge = isAutoDeactivated
        ? `<span class="status-badge" style="background:#FEE2E2;color:#991B1B;">Deactivated</span>`
        : `<span class="status-badge" style="background:#DCFCE7;color:#16A34A;">Sent</span>`;

      let assessBadge;
      if (ev.submission_status === 'submitted') {
        assessBadge = `<span class="status-badge" style="background:#DCFCE7;color:#166534;">Completed</span>`;
      } else if (ev.response_count && ev.response_count > 0) {
        assessBadge = `<span class="status-badge" style="background:#FEF3C7;color:#92400E;">In Progress</span>`;
      } else {
        assessBadge = `<span class="status-badge" style="background:var(--n-100);color:var(--n-500);">Not Started</span>`;
      }

      const initial = (ev.full_name || '?').trim().charAt(0).toUpperCase();
      const avColor = colorFor(ev.full_name || ev.email || '');

      html += `<tr style="${isAutoDeactivated ? 'opacity:0.7;' : ''}">
        <td><div class="ev-avatar" style="background:${avColor};">${initial}</div></td>
        <td style="font-weight:600;color:var(--n-900);">${ev.full_name}</td>
        <td style="color:var(--n-600);">${ev.email}</td>
        <td>${invBadge}</td>
        <td>${assessBadge}</td>
        <td>
          <div style="display:flex;gap:6px;justify-content:flex-end;">
            <button class="btn btn-secondary btn-sm" onclick="resendInvite(${ev.user_id})" title="Resend Invitation" style="padding:5px 8px;">
              ${svgIconJs('send')}
            </button>
            <button class="btn btn-danger btn-sm" onclick="removeEvaluator(${ev.user_id})" title="Remove from cycle" style="padding:5px 8px;">
              ${svgIconJs('trash')}
            </button>
          </div>
        </td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    wrap.innerHTML = html;

    deactBtn.style.display = hasActive ? '' : 'none';
    reactBtn.style.display = hasDeactivated ? '' : 'none';
  }

  // Minimal inline icon set for dynamically-rendered rows (mirrors svgIcon() output)
  function svgIconJs(name) {
    const icons = {
      send: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
      trash: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',
    };
    return icons[name] || '';
  }

  async function refreshCycleDates(cycleId) {
    const card = document.getElementById('cycleDatesCard');
    const banner = document.getElementById('cycleStatusBanner');

    card.style.display = 'block';
    document.getElementById('cycleNotConfigured').style.display = 'none';
    const r = await apiPost('users.php', { action: 'get_cycle_dates', cycle_id: cycleId });
    if (r.ok && r.dates) {
      const s = r.dates.stakeholder_access_start || '';
      const e = r.dates.stakeholder_access_end || '';

      document.getElementById('ev_start_d').value = s ? s.substring(0, 10) : '';
      document.getElementById('ev_start_t').value = s ? s.substring(11, 16) : '';
      document.getElementById('ev_end_d').value = e ? e.substring(0, 10) : '';
      document.getElementById('ev_end_t').value = e ? e.substring(11, 16) : '';

      function fmtSavedLabel(dateStr, timeStr) {
        if (!dateStr || !timeStr) return null;
        const d = new Date(dateStr + 'T' + timeStr);
        const datePart = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const [hh, mm] = timeStr.split(':').map(Number);
        const ampm = hh < 12 ? 'AM' : 'PM';
        const timePart = ((hh % 12) || 12) + ':' + String(mm).padStart(2, '0') + ' ' + ampm;
        return datePart + '  ' + timePart;
      }
      const startLbl = document.getElementById('dtp_start_label');
      const endLbl = document.getElementById('dtp_end_label');
      const startFormatted = fmtSavedLabel(s ? s.substring(0, 10) : '', s ? s.substring(11, 16) : '');
      const endFormatted = fmtSavedLabel(e ? e.substring(0, 10) : '', e ? e.substring(11, 16) : '');
      if (startLbl) {
        if (startFormatted) { startLbl.textContent = startFormatted; startLbl.classList.remove('placeholder'); }
        else { startLbl.textContent = 'Pick opening date & time'; startLbl.classList.add('placeholder'); }
      }
      if (endLbl) {
        if (endFormatted) { endLbl.textContent = endFormatted; endLbl.classList.remove('placeholder'); }
        else { endLbl.textContent = 'Pick closing date & time'; endLbl.classList.add('placeholder'); }
      }

      const now = new Date();
      const end = e ? new Date(e.replace(' ', 'T')) : null;
      const start = s ? new Date(s.replace(' ', 'T')) : null;

      if (!end) {
        banner.innerHTML = '<span class="window-status-pill" style="background:var(--n-100);color:var(--n-500);">Not Configured</span>';
      } else if (now > end) {
        banner.innerHTML = '<span class="window-status-pill" style="background:#FEE2E2;color:#991B1B;">Closed</span>';
      } else if (start && now < start) {
        banner.innerHTML = '<span class="window-status-pill" style="background:#FEF3C7;color:#92400E;">Upcoming</span>';
      } else {
        banner.innerHTML = '<span class="window-status-pill" style="background:#DCFCE7;color:#166534;">Active</span>';
      }
    }
  }

  async function saveCycleDates() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const sd = document.getElementById('ev_start_d').value;
    const st = document.getElementById('ev_start_t').value;
    const ed = document.getElementById('ev_end_d').value;
    const et = document.getElementById('ev_end_t').value;

    if (!cycleId) { toast('Please select a cycle first.', 'warning'); return; }
    if (!ed || !et) { toast('Access end date and time are required.', 'warning'); return; }

    const start = sd && st ? (sd + ' ' + st + ':00') : '';
    const end = ed + ' ' + et + ':00';

    const r = await apiPost('users.php', { action: 'set_cycle_dates', cycle_id: cycleId, start_date: start, end_date: end });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) refreshCycleDates(cycleId);
  }

  function openReactivationModal() {
    const list = document.getElementById('deactivatedEvalsList');
    const deactivated = lastEvalsList.filter(u => u.is_active == 0);
    if (deactivated.length === 0) return;

    list.innerHTML = deactivated.map(u => `
      <div style="display:flex;align-items:center;padding:8px;border-bottom:1px solid var(--n-100);gap:10px;">
        <input type="checkbox" name="reactivate_uid" value="${u.user_id}" checked style="width:16px;height:16px;">
        <div style="flex:1;">
          <div style="font-weight:600;font-size:13px;">${u.full_name}</div>
          <div style="font-size:11px;color:var(--n-400);">${u.email}</div>
        </div>
      </div>
    `).join('');

    document.getElementById('reactivate_end_d').value = document.getElementById('ev_end_d').value;
    document.getElementById('reactivate_end_t').value = document.getElementById('ev_end_t').value;
    openModal('mReactivate');
  }

  async function confirmReactivate() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const checkboxes = document.querySelectorAll('input[name="reactivate_uid"]:checked');
    const userIds = Array.from(checkboxes).map(cb => cb.value);

    const rd = document.getElementById('reactivate_end_d').value;
    const rt = document.getElementById('reactivate_end_t').value;
    const newEnd = rd && rt ? (rd + ' ' + rt + ':00') : '';

    if (userIds.length === 0) { toast('Please select at least one account.', 'warning'); return; }

    const fd = new FormData();
    fd.append('action', 'reactivate_evaluators');
    fd.append('cycle_id', cycleId);
    fd.append('csrf_token', '<?= csrfToken() ?>');
    userIds.forEach(id => fd.append('user_ids[]', id));
    fd.append('new_end_date', newEnd);

    const r = await fetch('users.php', { method: 'POST', body: fd }).then(res => res.json());
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) { closeModal('mReactivate'); loadEvaluators(); }
  }

  async function deactivateAllEvaluators() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!cycleId) return;
    if (!confirm('Deactivate ALL evaluator accounts for this cycle?\n\nTheir accounts will become inactive and they will no longer be able to log in.')) return;
    const r = await apiPost('users.php', { action: 'deactivate_cycle_evaluators', cycle_id: cycleId });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) loadEvaluators();
  }

  async function removeEvaluator(userId) {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!confirm('Remove this evaluator from the cycle?')) return;
    const r = await apiPost('users.php', { action: 'remove_cycle_evaluator', cycle_id: cycleId, user_id: userId });
    toast(r.msg, r.ok ? 'ok' : 'err');
    if (r.ok) loadEvaluators();
  }

  async function resendInvite(userId) {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!cycleId) return;
    const r = await apiPost('users.php', { action: 'resend_evaluator_invite', cycle_id: cycleId, user_id: userId });
    toast(r.msg, r.ok ? 'ok' : 'err');
  }

  function handleEvCsvSelect(input) {
    const file = input.files[0];
    const drop = document.getElementById('evCsvDrop');
    const btn = document.getElementById('evCsvImportBtn');
    if (!file) { clearEvCsv(); return; }
    drop.classList.add('has-file');
    document.getElementById('evCsvFileName').textContent = file.name;
    document.getElementById('evCsvFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    btn.disabled = false;
  }

  function clearEvCsv() {
    const input = document.getElementById('evCsvFile');
    input.value = '';
    document.getElementById('evCsvDrop').classList.remove('has-file');
    document.getElementById('evCsvFileName').textContent = '';
    document.getElementById('evCsvFileSize').textContent = '';
    document.getElementById('evCsvImportBtn').disabled = true;
  }

  async function importStakeholderCsv() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    if (!cycleId) { toast('Please select a cycle first.', 'warning'); return; }
    const file = document.getElementById('evCsvFile').files[0];
    if (!file) { toast('Please choose a CSV file first.', 'warning'); return; }

    const btn = document.getElementById('evCsvImportBtn');
    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin .7s linear infinite;"><path d="M12 2a10 10 0 1 0 10 10" /></svg> Importing…`;

    const text = await file.text();
    const lines = text.trim().split('\n').filter(l => l.trim());
    if (lines.length < 2) {
      toast('CSV is empty or has no data rows.', 'err');
      btn.disabled = false;
      btn.innerHTML = `<?= svgIcon('send') ?> Import &amp; Send Invites`;
      return;
    }

    const rows = lines.slice(1);
    let success = 0, failed = 0, errors = [];

    for (const line of rows) {
      const cols = line.split(',').map(c => c.trim().replace(/^"|"$/g, ''));
      const [full_name, email] = cols;
      if (!full_name || !email) { failed++; errors.push('Skipped empty row'); continue; }
      if (!email.includes('@')) { failed++; errors.push(`Invalid email: ${email}`); continue; }
      const r = await apiPost('users.php', { action: 'create_temp_evaluator', cycle_id: cycleId, full_name, email });
      if (r.ok) { success++; } else { failed++; errors.push(`${email}: ${r.msg}`); }
    }

    if (errors.length) console.warn('Import errors:', errors);
    toast(`Import done — ${success} added${failed ? ', ' + failed + ' failed' : ''}.`, success > 0 ? 'ok' : 'err');
    clearEvCsv();
    if (success > 0) loadEvaluators();

    btn.disabled = true;
    btn.innerHTML = `<?= svgIcon('send') ?> Import &amp; Send Invites`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const evZone = document.getElementById('evCsvDropZone');
    const evCard = document.getElementById('evCsvDrop');
    if (evZone) {
      evZone.addEventListener('dragover', e => { e.preventDefault(); evCard.classList.add('drag-over'); });
      evZone.addEventListener('dragleave', () => evCard.classList.remove('drag-over'));
      evZone.addEventListener('drop', e => {
        e.preventDefault(); evCard.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
          const input = document.getElementById('evCsvFile');
          const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
          handleEvCsvSelect(input);
        } else { toast('Please drop a .csv file.', 'warning'); }
      });
    }
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>