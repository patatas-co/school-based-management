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

$defaultCycle = $db->query("
  SELECT c.cycle_id, sy.label, c.status
  FROM sbm_cycles c
  JOIN school_years sy ON c.sy_id = sy.sy_id
  WHERE c.school_id = " . SCHOOL_ID . "
  AND sy.is_current = 1
  ORDER BY c.cycle_id DESC
  LIMIT 1
")->fetch();

if (empty($defaultCycle)) {
  $defaultCycle = $db->query("
    SELECT c.cycle_id, sy.label, c.status
    FROM sbm_cycles c
    JOIN school_years sy ON c.sy_id = sy.sy_id
    WHERE c.school_id = " . SCHOOL_ID . "
    ORDER BY c.cycle_id DESC
    LIMIT 1
  ")->fetch();
}

$cycles = $db->query("
  SELECT c.cycle_id, sy.label, c.status
  FROM sbm_cycles c
  JOIN school_years sy ON c.sy_id = sy.sy_id
  WHERE c.school_id = " . SCHOOL_ID . "
  ORDER BY c.cycle_id DESC
")->fetchAll();

$defaultCycleId = (int) ($defaultCycle['cycle_id'] ?? 0);
$defaultCycleLabel = $defaultCycleId ? ("SY " . e($defaultCycle['label']) . " — " . ucfirst(str_replace('_', ' ', $defaultCycle['status']))) : '';
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
  .section-card { background: #fff; border: 1px solid var(--n-150, #e5e7eb); border-radius: 14px; margin-bottom: 20px; box-shadow: var(--shadow-sm); overflow: hidden; }
  .evaluator-create-header { display:flex; align-items:center; padding:16px 20px; border-bottom:1px solid var(--n-100,#f1f5f9); font-size:14px; font-weight:700; color:var(--n-800,#1e293b); }
  .evaluator-create-body { padding:20px; }
  .section-eyebrow { font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--n-500); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
  .section-title-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
  .section-heading { font-size: 15px; font-weight: 800; color: var(--n-900); }
  .window-status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .status-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
</style>

<!-- ── Add Evaluator Manually ── -->
<div class="section-card">
  <div class="evaluator-create-header">Add Evaluator</div>
  <div class="evaluator-create-body">
    <div class="form-row-3" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr) 160px;">
      <div class="fg" style="margin-bottom:0;">
        <label>Full Name *</label>
        <input class="fc" id="ev_name" placeholder="e.g. Juan dela Cruz">
      </div>
      <div class="fg" style="margin-bottom:0;">
        <label>Email Address *</label>
        <input class="fc" type="email" id="ev_email" placeholder="evaluator@email.com">
      </div>
      <div class="fg" style="display:flex;align-items:flex-end;margin-bottom:0;">
        <button class="btn btn-primary" style="width:100%;" onclick="addEvaluator()">
          <?= svgIcon('check') ?> Save
        </button>
      </div>
    </div>
    <input type="hidden" id="ev_cycle_id" value="<?= $defaultCycleId ?>">
  </div>
</div>

<!-- ── Section 3: External Evaluator List ── -->
<div class="card" style="box-shadow:none;border:1px solid var(--n-150,#e5e7eb);">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--n-100,#f1f5f9);flex-wrap:wrap;">
    <div class="search" style="flex:0 1 320px;min-width:220px;">
      <span class="si"><?= svgIcon('search') ?></span>
      <input type="text" id="evaluatorSearch" placeholder="Search by name, username or email…" autocomplete="off" style="width:100%;">
    </div>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-danger btn-sm" onclick="deactivateAllEvaluators()" id="deactivateAllBtn" style="display:none;">
        <?= svgIcon('x') ?> Deactivate All
      </button>
    </div>
  </div>

  <div id="evaluatorListWrap">
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

  function updateCycleEditability(status) {
    return status;
  }

  function setMCycle(id, label, status) {
    document.getElementById('ev_cycle_id').value = id;
    updateCycleEditability(status || '');
    loadEvaluators();
  }

  async function addEvaluator() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const name = document.getElementById('ev_name').value.trim();
    const email = document.getElementById('ev_email').value.trim();
    if (!cycleId) { toast('Please select a cycle first.', 'warning'); return; }
    if (!name || !email) { toast('Name and email are required.', 'warning'); return; }
    const r = await apiPost('users.php', { action: 'create_temp_evaluator', cycle_id: cycleId, full_name: name, email: email });
    if (!r.ok) { toast(r.msg, 'err'); return; }
    document.getElementById('ev_name').value = '';
    document.getElementById('ev_email').value = '';
    Toastify({
      text: 'The evaluation link is sent to the evaluator\'s email.',
      duration: 4000,
      gravity: 'top',
      position: 'right',
      stopOnFocus: true,
      style: { background: '#15803D', borderRadius: '10px', fontFamily: "'Inter',sans-serif", fontSize: '13.5px', fontWeight: '600', padding: '12px 18px', boxShadow: '0 8px 24px rgba(0,0,0,.18)', minWidth: '260px' }
    }).showToast();
    loadEvaluators();
  }

  const avatarColors = ['#7C3AED', '#2563EB', '#059669', '#D97706', '#DB2777', '#0891B2'];
  function colorFor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
    return avatarColors[Math.abs(hash) % avatarColors.length];
  }

  let lastEvalsList = [];
  function filterEvaluators() {
    const query = (document.getElementById('evaluatorSearch')?.value || '').trim().toLowerCase();
    document.querySelectorAll('#evaluatorListWrap tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
  }

  async function loadEvaluators() {
    const cycleId = document.getElementById('ev_cycle_id').value;
    const wrap = document.getElementById('evaluatorListWrap');
    const deactBtn = document.getElementById('deactivateAllBtn');

    if (!cycleId) {
      wrap.innerHTML = '<div style="text-align:center;padding:20px;color:var(--n-400);font-size:13px;">No assessment cycle is currently configured.</div>';
      deactBtn.style.display = 'none';
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
      return;
    }

    let hasActive = false;

    let html = `<div class="tbl-wrap"><table class="tbl-enhanced" style="width:100%;">
      <thead><tr>
        <th>User</th>
        <th>Email</th>
        <th>Invitation Status</th>
        <th>Assessment Status</th>
        <th style="text-align:center;"></th>
      </tr></thead><tbody>`;

    r.data.forEach(ev => {
      const isAutoDeactivated = ev.is_active == 0;
      if (!isAutoDeactivated) hasActive = true;

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
        <td>
          <div class="cell-avatar">
            <div class="cell-av" style="background:${avColor};">${initial}</div>
            <div class="cell-av-info">
              <div class="cell-av-name">${ev.full_name}</div>
              <div class="cell-av-sub">${ev.username || ''}</div>
            </div>
          </div>
        </td>
        <td style="font-size:12px;color:var(--n-500);">${ev.email}</td>
        <td>${invBadge}</td>
        <td>${assessBadge}</td>
        <td style="text-align:center;">
          <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
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
    filterEvaluators();

    deactBtn.style.display = hasActive ? '' : 'none';
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
    return;
  }

  async function openReactivationModal() {
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

    const r = await apiPost('users.php', { action: 'get_cycle_dates', cycle_id: document.getElementById('ev_cycle_id').value });
    if (r.ok && r.dates && r.dates.stakeholder_access_end) {
      const end = r.dates.stakeholder_access_end;
      document.getElementById('reactivate_end_d').value = end.substring(0, 10);
      document.getElementById('reactivate_end_t').value = end.substring(11, 16);
      const label = document.getElementById('dtp_reactivate_label');
      if (label) {
        label.textContent = new Date(end.replace(' ', 'T')).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
        label.classList.remove('placeholder');
      }
    }
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

  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('evaluatorSearch')?.addEventListener('input', filterEvaluators);
    const defaultCycleId = document.getElementById('ev_cycle_id')?.value;
    if (defaultCycleId) {
      loadEvaluators();
    }

  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>