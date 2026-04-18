# SBM Coordinator Dashboard Update Guide
## Syncing functionality from School Head Dashboard

**Last Updated:** April 18, 2026  
**Scope:** Copy all shared functionality from `school_head/dashboard.php` Analytics tab to `coordinator/dashboard.php`

---

## ANALYSIS SUMMARY

### What School Head Dashboard Has (Analytics Tab):
✅ Overall SBM Score (77.7%) with Maturity Level badge  
✅ Key performance metrics cards:
   - Overall SBM Score
   - Advanced Maturity Level
   - Strongest dimension (D6 - Finance & Resource Management)
   - Weakest dimension (D1 - Curriculum & Teaching with needs work indicator)
   - Indicators Below 2.5 Average needing targeted intervention
   - Cycles Assessed counter
✅ Dimension Performance Radar chart (multi-dimension comparison)  
✅ Overall Score Trend line chart (shows SY comparison)  
✅ Primary SY selector with "Compare with" dropdown  
✅ "Manually Add Improvement Plan" button  
✅ "AI Suggestions" button with modal  
✅ Analytics tab switching (Progress/Analytics)  

### What Coordinator Dashboard Currently Has:
❌ Missing the Analytics tab completely  
❌ No KPI stat cards (Overall Score, Maturity, Best/Worst Dimension)  
❌ No Radar chart for Dimension Performance  
❌ No Trend chart for Overall Score  
❌ No SY comparison/dropdown  
❌ No AI Suggestions functionality  
❌ No "Manually Add Improvement Plan" feature  

---

## EDIT FORMAT REFERENCE

Format for all edits:
```
FIND:
[exact text to find]

REPLACE:
[new text to replace with]

---

ADD:
[new code to insert]

LOCATION: [where to add]

---

REMOVE:
[text to delete]

---
```

---

## SECTION-BY-SECTION EDIT GUIDE

---

### 1️⃣ ADD TAB SWITCHER (Progress/Analytics) in Hero Section
**Current Status:** Only "Progress" tab exists  
**Action:** Add "Analytics" tab alongside Progress tab  

LOCATION: Right after the closing `</div>` of the hero section (around line 320-350)

ADD:
```html
<!-- Tab Switcher: Progress / Analytics -->
<div class="tab-switcher" style="margin-top: 20px; margin-bottom: 24px; display: flex; gap: 6px; border-bottom: 1px solid var(--n-200);">
  <button id="tab-progress" class="tab-btn tab-active" onclick="switchTab('progress')" 
    style="padding: 12px 20px; border: none; background: none; color: var(--n-600); cursor: pointer; font-weight: 600; font-size: 14px; border-bottom: 2px solid var(--n-600); transition: all 140ms;">
    Progress
  </button>
  <button id="tab-analytics" class="tab-btn" onclick="switchTab('analytics')" 
    style="padding: 12px 20px; border: none; background: none; color: var(--n-400); cursor: pointer; font-weight: 600; font-size: 14px; border-bottom: 2px solid transparent; transition: all 140ms;">
    Analytics
  </button>
</div>

<script>
  function switchTab(tab) {
    const progressSec = document.getElementById('progress-section');
    const analyticsSec = document.getElementById('analytics-section');
    const progressBtn = document.getElementById('tab-progress');
    const analyticsBtn = document.getElementById('tab-analytics');
    
    if (tab === 'progress') {
      progressSec.style.display = 'grid';
      analyticsSec.style.display = 'none';
      progressBtn.classList.add('tab-active');
      analyticsBtn.classList.remove('tab-active');
      progressBtn.style.color = 'var(--n-600)';
      progressBtn.style.borderBottomColor = 'var(--n-600)';
      analyticsBtn.style.color = 'var(--n-400)';
      analyticsBtn.style.borderBottomColor = 'transparent';
    } else {
      progressSec.style.display = 'none';
      analyticsSec.style.display = 'grid';
      analyticsBtn.classList.add('tab-active');
      progressBtn.classList.remove('tab-active');
      analyticsBtn.style.color = 'var(--n-600)';
      analyticsBtn.style.borderBottomColor = 'var(--n-600)';
      progressBtn.style.color = 'var(--n-400)';
      progressBtn.style.borderBottomColor = 'transparent';
    }
  }
</script>
```

---

### 2️⃣ WRAP EXISTING PROGRESS CONTENT IN SECTION
**Current Status:** KPI cards and charts are unwrapped  
**Action:** Wrap all current KPI cards (INDICATORS RATED, SBM SCORE, etc.) in a div with ID

FIND:
```html
  <div class="stats-v2">
    <!-- INDICATORS RATED card -->
    <div class="stat-v2">
```

REPLACE:
```html
  <div id="progress-section" class="stats-v2">
    <!-- INDICATORS RATED card -->
    <div class="stat-v2">
```

FIND:
```html
    </div>
  </div>
</div><!-- /LEFT COLUMN -->
```

REPLACE:
```html
    </div>
  </div>
</div><!-- /progress-section -->
</div><!-- /LEFT COLUMN -->
```

---

### 3️⃣ CREATE ANALYTICS SECTION WITH KPI CARDS
**Action:** Add new Analytics tab content with stat cards

ADD:
```html
<!-- ANALYTICS SECTION (Hidden by default, shown when Analytics tab clicked) -->
<div id="analytics-section" style="display: none; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 24px;">

  <!-- Left Column: Analytics Content -->
  <div style="display: flex; flex-direction: column; gap: 18px;">

    <!-- SY Selector & Comparison Row -->
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <label style="font-size: 13px; font-weight: 600; color: var(--n-600);">Primary SY:</label>
        <select id="primary-sy-select" style="padding: 8px 12px; border-radius: 6px; border: 1px solid var(--n-300); font-size: 13px; font-weight: 600; background: #fff; cursor: pointer;">
          <option value="<?= $syId ?>"><?= e($syLabel) ?></option>
        </select>
      </div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <label style="font-size: 13px; font-weight: 600; color: var(--n-600);">Compare with:</label>
        <select id="compare-sy-select" style="padding: 8px 12px; border-radius: 6px; border: 1px solid var(--n-300); font-size: 13px; font-weight: 600; background: #fff; cursor: pointer;">
          <option value="">None</option>
        </select>
      </div>
    </div>

    <!-- KPI Stat Cards (Same as school_head analytics view) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
      
      <!-- Overall SBM Score -->
      <div class="stat-v2" style="border-left: 4px solid var(--brand-500);">
        <div class="stat-v2-label">Overall SBM Score</div>
        <div class="stat-v2-value" style="color: var(--brand-600);">
          <?= $hasScore ? round($cycle['overall_score'], 1) : '—' ?>%
        </div>
        <?php if ($mat): ?>
          <div style="font-size: 12px; font-weight: 600; color: var(--brand-700); margin-top: 6px;">
            <?= ucfirst($mat) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Maturity Level -->
      <div class="stat-v2" style="border-left: 4px solid var(--teal);">
        <div class="stat-v2-label">Maturity Level</div>
        <div class="stat-v2-value" style="color: var(--teal); font-size: 28px;">
          <?= $mat ? ucfirst($mat) : '—' ?>
        </div>
      </div>

      <!-- Strongest Dimension -->
      <div class="stat-v2" style="border-left: 4px solid var(--brand-500);">
        <div class="stat-v2-label">Strongest</div>
        <div class="stat-v2-value" style="font-size: 16px; color: var(--n-700);">
          <?php if ($dimScores): 
            $max = max(array_column($dimScores, 'score'));
            $strongest = array_values(array_filter($dimScores, fn($d) => $d['score'] == $max))[0] ?? null;
            echo $strongest ? 'D' . $strongest['dimension_no'] : '—';
          else: echo '—'; endif; ?>
        </div>
        <?php if (isset($strongest)): ?>
          <div style="font-size: 11px; color: var(--n-500); margin-top: 4px;">
            <?= e(substr($strongest['dimension_name'], 0, 35)) ?>
          </div>
          <div style="font-size: 11px; font-weight: 600; color: var(--brand-600); margin-top: 2px;">
            <?= $strongest['score'] ?>% average
          </div>
        <?php endif; ?>
      </div>

      <!-- Weakest Dimension (Needs Work) -->
      <div class="stat-v2" style="border-left: 4px solid var(--red-500);">
        <div class="stat-v2-label">Needs Work</div>
        <div class="stat-v2-value" style="font-size: 16px; color: var(--red-600);">
          <?php if ($dimScores): 
            $min = min(array_column($dimScores, 'score'));
            $weakest = array_values(array_filter($dimScores, fn($d) => $d['score'] == $min))[0] ?? null;
            echo $weakest ? 'D' . $weakest['dimension_no'] : '—';
          else: echo '—'; endif; ?>
        </div>
        <?php if (isset($weakest)): ?>
          <div style="font-size: 11px; color: var(--n-500); margin-top: 4px;">
            <?= e(substr($weakest['dimension_name'], 0, 30)) ?>
          </div>
          <div style="font-size: 11px; font-weight: 600; color: var(--red-600); margin-top: 2px;">
            <?= $weakest['score'] ?>% average
          </div>
        <?php endif; ?>
      </div>

      <!-- Indicators Below 2.5 Avg -->
      <div class="stat-v2" style="border-left: 4px solid var(--amber);">
        <div class="stat-v2-label">Indicators Below 2.5</div>
        <div class="stat-v2-value" style="color: var(--amber); font-size: 28px;">
          <?= $dimScores ? count(array_filter($dimScores, fn($d) => floatval($d['score']) < 2.5)) : 0 ?>
        </div>
        <div style="font-size: 11px; color: var(--amber); font-weight: 600; margin-top: 6px;">
          Needs targeted intervention
        </div>
      </div>

      <!-- Cycles Assessed -->
      <div class="stat-v2" style="border-left: 4px solid var(--purple);">
        <div class="stat-v2-label">Cycles Assessed</div>
        <div class="stat-v2-value" style="color: var(--purple); font-size: 28px;">
          1
        </div>
        <div style="font-size: 11px; color: var(--n-500); margin-top: 6px;">
          Since SY <?= $syLabel ?>
        </div>
      </div>

    </div>

    <!-- Dimension Performance Radar Chart -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Dimension Performance Radar</span>
      </div>
      <div class="card-body">
        <div style="position: relative; height: 280px;">
          <?php if ($dimScores): ?>
            <canvas id="radarChart"></canvas>
          <?php else: ?>
            <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; border: 2px dashed var(--n-200); border-radius: 8px; background: var(--n-50);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 32px; height: 32px; color: var(--n-300); margin-bottom: 8px;">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 8v8" />
                <path d="M8 12h8" />
              </svg>
              <div style="font-size: 13px; font-weight: 600; color: var(--n-400);">Chart data unavailable</div>
              <div style="font-size: 12px; color: var(--n-400);">Scores will appear once evaluations begin.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Right Column: Trend & Actions -->
  <div style="display: flex; flex-direction: column; gap: 18px;">

    <!-- Overall Score Trend Chart -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Overall Score Trend</span>
        <span style="font-size: 12px; color: var(--n-400);">1 cycle(s)</span>
      </div>
      <div class="card-body">
        <div style="position: relative; height: 240px;">
          <?php if ($hasScore): ?>
            <canvas id="trendChart"></canvas>
          <?php else: ?>
            <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; border: 2px dashed var(--n-200); border-radius: 8px; background: var(--n-50);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 32px; height: 32px; color: var(--n-300); margin-bottom: 8px;">
                <line x1="12" y1="5" x2="12" y2="19" />
                <polyline points="19 12 12 19 5 12" />
              </svg>
              <div style="font-size: 13px; font-weight: 600; color: var(--n-400);">Not enough data</div>
              <div style="font-size: 12px; color: var(--n-400);">Complete an assessment cycle to see trends.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- AI Suggestions & Improvement Actions -->
    <div class="card">
      <div class="card-head">
        <span class="card-title" style="font-size: 14px; font-weight: 700;">Improvement Actions</span>
      </div>
      <div class="card-body" style="padding: 14px;">
        <button id="ai-suggestions-btn" class="btn btn-primary" style="width: 100%; margin-bottom: 8px; justify-content: center; gap: 6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
            <path d="M12 2L15.09 8.26H21.77L17.04 12.33L19.09 18.59L12 14.41L4.91 18.59L6.96 12.33L2.23 8.26H8.91L12 2Z" />
          </svg>
          AI Suggestions
        </button>
        <button id="manual-plan-btn" class="btn btn-secondary" style="width: 100%; justify-content: center; gap: 6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
            <path d="M12 5v14M5 12h14" />
          </svg>
          Manually Add Improvement Plan
        </button>
      </div>
    </div>

  </div>

</div><!-- /analytics-section -->
```

LOCATION: Right before the `<!-- RIGHT SIDEBAR -->` comment (around line 1174)

---

### 4️⃣ ADD RADAR CHART CONFIGURATION
**Action:** Add Chart.js initialization for radar chart

FIND:
```javascript
<?php if ($dimScores): ?>
  <script>
    const dimLabels = <?= json_encode($chartLabels) ?>;
    const dimValues = <?= json_encode($chartData) ?>;
    const dimColors = <?= json_encode($chartColors) ?>;

    new Chart(document.getElementById('dimBarChart'), {
```

REPLACE:
```javascript
<?php if ($dimScores): ?>
  <script>
    const dimLabels = <?= json_encode($chartLabels) ?>;
    const dimValues = <?= json_encode($chartData) ?>;
    const dimColors = <?= json_encode($chartColors) ?>;

    // Radar Chart for Analytics Tab
    const radarLabels = [];
    const radarData = [];
    const radarColors = [];
    <?php foreach ($dimScores as $ds): ?>
      radarLabels.push('D<?= $ds["dimension_no"] ?>');
      radarData.push(<?= $ds['score'] ?>);
      radarColors.push('<?= $ds["color_hex"] ?>');
    <?php endforeach; ?>

    new Chart(document.getElementById('radarChart'), {
      type: 'radar',
      data: {
        labels: radarLabels,
        datasets: [{
          label: 'Score',
          data: radarData,
          borderColor: radarColors,
          backgroundColor: radarColors.map(c => c + '20'),
          pointBackgroundColor: radarColors,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
          tension: 0.2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            min: 0,
            max: 100,
            ticks: { callback: v => v + '%', font: { size: 11 } },
            grid: { color: '#F3F4F6' },
            pointLabels: { font: { size: 12, weight: '600' } }
          }
        },
        plugins: { legend: { display: false } }
      }
    });

    // Trend Chart for Analytics Tab (Simple line)
    const trendChart = new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: ['Current'],
        datasets: [{
          label: 'Overall Score',
          data: [<?= $hasScore ? round($cycle['overall_score'], 1) : 0 ?>],
          borderColor: '#10B981',
          backgroundColor: '#10B98120',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 5,
          pointBackgroundColor: '#10B981',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
          x: { grid: { display: false } }
        },
        plugins: { legend: { display: true, position: 'top' } }
      }
    });

    // Bar Chart (existing)
    new Chart(document.getElementById('dimBarChart'), {
```

---

### 5️⃣ ADD MODAL FOR AI SUGGESTIONS
**Action:** Add modal dialog for AI improvement suggestions

ADD:
```html
<!-- AI Suggestions Modal -->
<div id="ai-suggestions-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
  <div style="background: #fff; border-radius: var(--radius-lg); width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; border-bottom: 1px solid var(--n-200); position: sticky; top: 0; background: #fff;">
      <div>
        <h2 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--n-900);">AI Suggestion Improvement Plan</h2>
        <p style="margin: 4px 0 0; font-size: 12px; color: var(--n-500);">Actionable Suggestions</p>
      </div>
      <button onclick="closeAISuggestions()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--n-400);">×</button>
    </div>
    <div id="ai-suggestions-content" style="padding: 20px;">
      <!-- Suggestions will load here -->
    </div>
  </div>
</div>

<script>
  document.getElementById('ai-suggestions-btn').addEventListener('click', function() {
    const modal = document.getElementById('ai-suggestions-modal');
    modal.style.display = 'flex';
    // Load suggestions from API or database
    loadAISuggestions();
  });

  function closeAISuggestions() {
    document.getElementById('ai-suggestions-modal').style.display = 'none';
  }

  async function loadAISuggestions() {
    const content = document.getElementById('ai-suggestions-content');
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--n-400);">Loading suggestions...</div>';
    
    try {
      const response = await fetch('../coordinator/api.php?action=get_ai_suggestions&cycle_id=<?= $cycle['cycle_id'] ?? 0 ?>');
      const data = await response.json();
      
      if (data.ok && data.suggestions.length > 0) {
        content.innerHTML = data.suggestions.map(s => `
          <div style="padding: 14px; border-radius: 8px; background: var(--n-50); margin-bottom: 12px;">
            <p style="margin: 0 0 8px; font-size: 13px; color: var(--n-900); font-weight: 600;">${s.title}</p>
            <p style="margin: 0; font-size: 12px; color: var(--n-600); line-height: 1.5;">${s.description}</p>
          </div>
        `).join('');
      } else {
        content.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--n-400);">No suggestions available yet.</div>';
      }
    } catch (e) {
      content.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--red-600);">Error loading suggestions.</div>';
    }
  }

  window.addEventListener('click', function(e) {
    const modal = document.getElementById('ai-suggestions-modal');
    if (e.target === modal) modal.style.display = 'none';
  });
</script>
```

LOCATION: Right before `<?php include __DIR__ . '/../includes/footer.php'; ?>` (end of file)

---

### 6️⃣ ADD STYLES FOR ANALYTICS TAB
**Action:** Add CSS rules for new elements

ADD:
```css
  /* ── TAB SWITCHER ── */
  .tab-btn {
    transition: color 140ms, border-color 140ms;
  }

  .tab-btn:hover {
    color: var(--n-700);
  }

  .tab-active {
    color: var(--n-900) !important;
    border-bottom-color: var(--brand-500) !important;
  }

  /* ── STAT CARD BORDER ACCENT ── */
  .stat-v2[style*="border-left"] {
    position: relative;
  }

  /* ── IMPROVEMENT ACTIONS BUTTON ── */
  .btn-secondary {
    background: var(--n-50);
    border: 1px solid var(--n-200);
    color: var(--n-700);
  }

  .btn-secondary:hover {
    background: var(--n-100);
    border-color: var(--n-300);
  }
```

LOCATION: After the existing `.hero-btn svg` rule (around line 208-213), before the closing `</style>` tag

---

## SUMMARY OF CHANGES

| Component | Action | Lines |
|-----------|--------|-------|
| Tab Switcher | ADD | New ~50 lines |
| Progress Section Wrapper | FIND/REPLACE | 2 lines modified |
| Analytics Section | ADD | New ~250 lines |
| Radar Chart | ADD | New ~30 lines |
| Trend Chart | ADD | New ~25 lines |
| AI Modal | ADD | New ~80 lines |
| CSS Styles | ADD | New ~25 lines |
| **Total** | | **~460 new lines** |

---

## IMPLEMENTATION CHECKLIST

- [ ] Add tab switcher HTML/JS to hero section
- [ ] Wrap progress content in div#progress-section
- [ ] Add analytics section with KPI cards
- [ ] Configure radar chart data binding
- [ ] Add trend chart initialization
- [ ] Add AI suggestions modal & logic
- [ ] Add accompanying CSS styles
- [ ] Test tab switching functionality
- [ ] Verify chart rendering with sample data
- [ ] Test AI suggestions button (if API endpoint exists)
- [ ] Responsive design check on mobile
- [ ] Cross-browser compatibility check

---

## NOTES FOR DEVELOPER

1. **Chart.js**: Make sure Chart.js is loaded in footer.php
2. **Color Consistency**: Radar chart uses dimension colors from database
3. **Data Binding**: All statistics pull from `$dimScores` array
4. **API Endpoint**: AI suggestions need `../coordinator/api.php?action=get_ai_suggestions`
5. **Mobile**: Tab switcher and analytics section should be responsive
6. **Accessibility**: Ensure buttons have proper ARIA labels and keyboard support

---

## TESTING SCENARIOS

✓ Click "Analytics" tab → should hide Progress, show Analytics  
✓ Click "Progress" tab → should hide Analytics, show Progress  
✓ With data: Radar chart renders with proper colors  
✓ With data: KPI cards show correct values  
✓ Click "AI Suggestions" → Modal opens with suggestions  
✓ Resize window → Charts maintain responsive sizing  

---

**End of Guide**
