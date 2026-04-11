<?php
ob_start();
// school_head/analytics.php — Analytics for School Head
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('school_head', 'sbm_coordinator');
$db = getDB();

$syears = $db->query("SELECT * FROM school_years ORDER BY sy_id DESC")->fetchAll();
$syId = (int) ($_GET['sy'] ?? $db->query("SELECT sy_id FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn());

// ── Comparison SY (second cycle for side-by-side) ─────────
$compareSyId = (int) ($_GET['compare_sy'] ?? 0);

// ── Current SY dimension averages ─────────────────────────
$dimAvgQ = $db->prepare("
    SELECT d.dimension_no, d.dimension_name, d.color_hex,
           ROUND(AVG(ds.percentage),1) AS avg_pct
    FROM sbm_dimensions d
    LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
    LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY d.dimension_id ORDER BY d.dimension_no
");
$dimAvgQ->execute([$syId, SCHOOL_ID]);
$dimAvgs = $dimAvgQ->fetchAll();

// ── Comparison SY dimension averages ──────────────────────
$dimAvgsCompare = [];
if ($compareSyId && $compareSyId !== $syId) {
  $cmpQ = $db->prepare("
        SELECT d.dimension_no, d.dimension_name, d.color_hex,
               ROUND(AVG(ds.percentage),1) AS avg_pct
        FROM sbm_dimensions d
        LEFT JOIN sbm_dimension_scores ds ON d.dimension_id = ds.dimension_id
        LEFT JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
        WHERE c.sy_id = ? AND c.school_id = ?
        GROUP BY d.dimension_id ORDER BY d.dimension_no
    ");
  $cmpQ->execute([$compareSyId, SCHOOL_ID]);
  $dimAvgsCompare = $cmpQ->fetchAll();
}

// ── Maturity distribution ──────────────────────────────────
$matDistQ = $db->prepare("
    SELECT maturity_level, COUNT(*) cnt
    FROM sbm_cycles
    WHERE sy_id = ? AND school_id = ? AND maturity_level IS NOT NULL
    GROUP BY maturity_level
");
$matDistQ->execute([$syId, SCHOOL_ID]);
$matDists = $matDistQ->fetchAll();

// ── Assessment history (all cycles, ordered by SY) ────────
$historyQ = $db->prepare("
    SELECT sy.label AS sy_label, sy.sy_id,
           c.cycle_id, c.overall_score, c.maturity_level,
           c.status, c.validated_at
    FROM sbm_cycles c
    JOIN school_years sy ON c.sy_id = sy.sy_id
    WHERE c.school_id = ? AND c.overall_score IS NOT NULL
    ORDER BY sy.date_start ASC
");
$historyQ->execute([SCHOOL_ID]);
$cycleHistory = $historyQ->fetchAll();

// ── Trend data: dimension scores across all cycles ─────────
$trendQ = $db->prepare("
    SELECT sy.label AS sy_label, sy.sy_id,
           d.dimension_no, d.dimension_name, d.color_hex,
           ROUND(AVG(ds.percentage),1) AS avg_pct
    FROM sbm_dimension_scores ds
    JOIN sbm_cycles c ON ds.cycle_id = c.cycle_id
    JOIN school_years sy ON c.sy_id = sy.sy_id
    JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
    WHERE c.school_id = ? AND c.overall_score IS NOT NULL
    GROUP BY sy.sy_id, d.dimension_id
    ORDER BY sy.date_start ASC, d.dimension_no ASC
");
$trendQ->execute([SCHOOL_ID]);
$trendRows = $trendQ->fetchAll();

// Pivot trend rows into [dim_no => [sy_label => pct, ...], ...]
$trendBySY = [];  // [sy_label => [dim_no => pct]]
$trendByDim = [];  // [dim_no => [sy_label => pct]]
$trendSYLabels = [];
foreach ($trendRows as $tr) {
  $trendSYLabels[$tr['sy_id']] = $tr['sy_label'];
  $trendBySY[$tr['sy_label']][$tr['dimension_no']] = floatval($tr['avg_pct']);
  $trendByDim[$tr['dimension_no']][$tr['sy_label']] = floatval($tr['avg_pct']);
}
$trendSYLabels = array_values($trendSYLabels);

// ── Weak indicators — current SY ──────────────────────────
$weakQ = $db->prepare("
    SELECT i.indicator_code, i.indicator_text,
           d.dimension_name, d.color_hex,
           ROUND(AVG(all_r.rating), 2) AS avg_rating,
           COUNT(all_r.rating) AS response_count
    FROM (
        SELECT cycle_id, indicator_id, rating FROM sbm_responses
        UNION ALL
        SELECT cycle_id, indicator_id, rating FROM teacher_responses
    ) AS all_r
    JOIN sbm_indicators i   ON all_r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d   ON i.dimension_id = d.dimension_id
    JOIN sbm_cycles c       ON all_r.cycle_id = c.cycle_id
    WHERE c.sy_id = ? AND c.school_id = ?
    GROUP BY i.indicator_id
    ORDER BY avg_rating ASC
    LIMIT 8
");
$weakQ->execute([$syId, SCHOOL_ID]);
$weakIndicatorRows = $weakQ->fetchAll();

// ── Consistently weak indicators (low across ALL cycles) ──
$consistentlyWeakQ = $db->prepare("
    SELECT i.indicator_code, i.indicator_text,
           d.dimension_name, d.color_hex,
           ROUND(AVG(all_r.rating), 2)  AS avg_rating,
           COUNT(DISTINCT c.sy_id)      AS cycle_count,
           MIN(ROUND(per_cy.avg_r, 2))  AS worst_cycle_avg,
           MAX(ROUND(per_cy.avg_r, 2))  AS best_cycle_avg
    FROM (
        SELECT cycle_id, indicator_id, rating FROM sbm_responses
        UNION ALL
        SELECT cycle_id, indicator_id, rating FROM teacher_responses
    ) AS all_r
    JOIN sbm_indicators i ON all_r.indicator_id = i.indicator_id
    JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
    JOIN sbm_cycles c     ON all_r.cycle_id = c.cycle_id AND c.school_id = ?
    JOIN (
        SELECT r2.indicator_id, r2.cycle_id, AVG(r2.rating) AS avg_r
        FROM (
            SELECT cycle_id, indicator_id, rating FROM sbm_responses
            UNION ALL
            SELECT cycle_id, indicator_id, rating FROM teacher_responses
        ) r2
        JOIN sbm_cycles c2 ON r2.cycle_id = c2.cycle_id AND c2.school_id = ?
        GROUP BY r2.indicator_id, r2.cycle_id
    ) per_cy ON per_cy.indicator_id = all_r.indicator_id
    GROUP BY i.indicator_id
    HAVING cycle_count >= 1 AND avg_rating <= 2.5
    ORDER BY avg_rating ASC
    LIMIT 6
");
$consistentlyWeakQ->execute([SCHOOL_ID, SCHOOL_ID]);
$consistentlyWeak = $consistentlyWeakQ->fetchAll();

// ── Summary insights ───────────────────────────────────────
$allPcts = array_filter(array_column($dimAvgs, 'avg_pct'), fn($v) => $v !== null);
$avgOverall = count($allPcts) > 0 ? round(array_sum($allPcts) / count($allPcts), 1) : null;
$topDim = !empty($allPcts) ? $dimAvgs[array_search(max($allPcts), array_column($dimAvgs, 'avg_pct'))] : null;
$weakDim = !empty($allPcts) ? $dimAvgs[array_search(min($allPcts), array_column($dimAvgs, 'avg_pct'))] : null;

// Trend direction vs previous cycle
$prevCycle = count($cycleHistory) >= 2 ? $cycleHistory[count($cycleHistory) - 2] : null;
$currCycle = count($cycleHistory) >= 1 ? $cycleHistory[count($cycleHistory) - 1] : null;
$scoreDelta = ($currCycle && $prevCycle)
  ? round(floatval($currCycle['overall_score']) - floatval($prevCycle['overall_score']), 2)
  : null;

// ── Auto-populate analytics_snapshots for validated cycles ─
// Only inserts if not already snapshotted (safe to run on every page load)
try {
  $unsnapped = $db->prepare("
        SELECT c.cycle_id, c.school_id, c.sy_id, c.overall_score, c.maturity_level,
               sy.label AS sy_label
        FROM sbm_cycles c
        JOIN school_years sy ON c.sy_id = sy.sy_id
        LEFT JOIN analytics_snapshots ans
            ON ans.cycle_id = c.cycle_id AND ans.dimension_id = 1
        WHERE c.school_id = ?
          AND c.status IN ('validated','finalized')
          AND ans.snap_id IS NULL
    ");
  $unsnapped->execute([SCHOOL_ID]);
  foreach ($unsnapped->fetchAll() as $uc) {
    $dscores = $db->prepare("
            SELECT ds.dimension_id, ds.percentage, ds.raw_score, ds.max_score,
                   d.dimension_no, d.dimension_name
            FROM sbm_dimension_scores ds
            JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
            WHERE ds.cycle_id = ?
        ");
    $dscores->execute([$uc['cycle_id']]);
    foreach ($dscores->fetchAll() as $ds) {
      $db->prepare("
                INSERT IGNORE INTO analytics_snapshots
                    (school_id, cycle_id, sy_id, sy_label, dimension_id, dimension_no,
                     dimension_name, percentage, raw_score, max_score, overall_score, maturity_level)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
            $uc['school_id'],
            $uc['cycle_id'],
            $uc['sy_id'],
            $uc['sy_label'],
            $ds['dimension_id'],
            $ds['dimension_no'],
            $ds['dimension_name'],
            $ds['percentage'],
            $ds['raw_score'],
            $ds['max_score'],
            $uc['overall_score'],
            $uc['maturity_level'],
          ]);
    }
  }
} catch (\Exception $e) {
  // analytics_snapshots table may not exist yet — silently skip
}

$pageTitle = 'Analytics';
$activePage = 'analytics.php';
include __DIR__ . '/../includes/header.php';
?>
<style>
  .chart-legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }

  .chart-legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--n-600);
  }

  .chart-legend-swatch {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    flex-shrink: 0;
  }

  .weak-prog {
    height: 6px;
    background: var(--n-100);
    border-radius: 999px;
    overflow: hidden;
    margin-top: 5px;
  }

  .weak-fill {
    height: 100%;
    border-radius: 999px;
  }

  /* ── Trend / comparison additions ── */
  .insight-strip {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
  }

  .insight-card {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    padding: 14px 16px;
    box-shadow: var(--shadow-xs);
  }

  .insight-val {
    font-family: var(--font-display);
    font-size: 26px;
    font-weight: 800;
    color: var(--n-900);
    line-height: 1;
    margin-bottom: 4px;
  }

  .insight-lbl {
    font-size: 11.5px;
    color: var(--n-500);
    font-weight: 500;
  }

  .insight-delta {
    font-size: 12px;
    font-weight: 700;
    margin-top: 5px;
  }

  .insight-delta.up {
    color: var(--brand-600);
  }

  .insight-delta.down {
    color: var(--red);
  }

  .insight-delta.flat {
    color: var(--n-400);
  }

  .filter-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    margin-bottom: 16px;
    flex-wrap: wrap;
    box-shadow: var(--shadow-xs);
  }

  .filter-bar label {
    font-size: 12px;
    font-weight: 600;
    color: var(--n-600);
    white-space: nowrap;
  }

  .cw-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid var(--n-100);
  }

  .cw-badge {
    min-width: 38px;
    height: 22px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .04em;
    flex-shrink: 0;
  }

  .cw-info {
    flex: 1;
    min-width: 0;
  }

  .cw-title {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-900);
    margin-bottom: 3px;
    line-height: 1.35;
  }

  .cw-meta {
    font-size: 11.5px;
    color: var(--n-400);
  }

  .cw-bar-track {
    height: 5px;
    background: var(--n-100);
    border-radius: 999px;
    margin-top: 5px;
    overflow: hidden;
  }

  .cw-bar-fill {
    height: 100%;
    border-radius: 999px;
  }

  .tab-btns {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
  }

  .tab-btn {
    padding: 6px 14px;
    border-radius: 7px;
    border: 1.5px solid var(--n-200);
    background: #fff;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--n-600);
    cursor: pointer;
    transition: all .14s;
  }

  .tab-btn:hover {
    background: var(--n-50);
    border-color: var(--n-300);
  }

  .tab-btn.active {
    background: var(--n-900);
    color: #fff;
    border-color: var(--n-900);
  }

  .tab-panel {
    display: none;
  }

  .tab-panel.active {
    display: block;
  }

  @media(max-width:768px) {
    .insight-strip {
      grid-template-columns: 1fr 1fr;
    }
  }
</style>

<div class="ph2">
  <div class="ph2-left">
    <div class="ph2-eyebrow">Reporting</div>
    <div class="ph2-title">SBM Analytics</div>
    <div class="ph2-sub">Performance insights, cycle trends, and dimension analysis — Dasmariñas Integrated High School
    </div>
  </div>
  <div class="ph2-right">
    <div class="p-select" id="sySelect" style="width:165px;">
      <input type="hidden" name="sy_id" value="<?= $syId ?>">
      <div class="p-select-trigger" onclick="togglePSelect(event, 'sySelect')">
        <span class="p-select-val">
          SY <?= e(array_column($syears, 'label', 'sy_id')[$syId] ?? 'Select SY') ?>
        </span>
      </div>
      <div class="p-select-menu">
        <?php foreach ($syears as $sy): ?>
          <div class="p-select-item <?= $sy['sy_id'] == $syId ? 'selected' : '' ?>" 
               onclick="location.href='analytics.php?sy=<?= $sy['sy_id'] ?>&compare_sy=<?= $compareSyId ?>'">
            SY <?= e($sy['label']) ?>
            <?php if ($sy['sy_id'] == $syId): ?>
              <span class="p-select-check"></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Filter bar with comparison selector -->
<div class="filter-bar">
  <label>Primary SY:</label>
  <span style="font-size:13px;font-weight:700;color:var(--n-900);">
    <?= e(array_column($syears, 'label', 'sy_id')[$syId] ?? '—') ?>
  </span>
  <div style="width:1px;height:18px;background:var(--n-200);margin:0 4px;"></div>
  <label>Compare with:</label>
  <div class="p-select" id="compareSelect" style="width:160px;">
    <input type="hidden" name="compare_sy_id" value="<?= $compareSyId ?>">
    <div class="p-select-trigger" onclick="togglePSelect(event, 'compareSelect')" style="padding: 5px 12px; font-size: 12.5px; min-height: 32px;">
      <span class="p-select-val">
        <?= $compareSyId ? 'SY ' . e(array_column($syears, 'label', 'sy_id')[$compareSyId]) : 'None' ?>
      </span>
    </div>
    <div class="p-select-menu">
      <div class="p-select-item <?= !$compareSyId ? 'selected' : '' ?>" 
           onclick="location.href='analytics.php?sy=<?= $syId ?>&compare_sy=0'">
        None
      </div>
      <?php foreach ($syears as $sy):
        if ($sy['sy_id'] == $syId) continue; ?>
        <div class="p-select-item <?= $sy['sy_id'] == $compareSyId ? 'selected' : '' ?>" 
             onclick="location.href='analytics.php?sy=<?= $syId ?>&compare_sy=<?= $sy['sy_id'] ?>'">
          SY <?= e($sy['label']) ?>
          <?php if ($sy['sy_id'] == $compareSyId): ?>
            <span class="p-select-check"></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if ($compareSyId): ?>
    <span
      style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--blue-bg);color:var(--blue);">
      Comparing 2 cycles
    </span>
    <a href="analytics.php?sy=<?= $syId ?>" class="btn btn-ghost btn-sm">✕ Clear</a>
  <?php endif; ?>
</div>

<!-- Enhanced insight strip -->
<div class="insight-strip">
  <!-- Overall score -->
  <div class="insight-card">
    <div class="insight-val"
      style="color:<?= $avgOverall !== null ? ($avgOverall >= 76 ? '#16A34A' : ($avgOverall >= 51 ? '#2563EB' : ($avgOverall >= 26 ? '#D97706' : '#DC2626'))) : 'var(--n-400)' ?>;">
      <?= $avgOverall !== null ? $avgOverall . '%' : '—' ?>
    </div>
    <div class="insight-lbl">Overall SBM Score</div>
    <?php if ($scoreDelta !== null): ?>
      <div class="insight-delta <?= $scoreDelta > 0 ? 'up' : ($scoreDelta < 0 ? 'down' : 'flat') ?>">
        <?= $scoreDelta > 0 ? '▲ +' : '▼ ' ?>   <?= abs($scoreDelta) ?>% vs prev cycle
      </div>
    <?php endif; ?>
  </div>

  <!-- Maturity level -->
  <div class="insight-card">
    <?php
    $curMaturity = $currCycle['maturity_level'] ?? null;
    $matColors = ['Beginning' => '#DC2626', 'Developing' => '#D97706', 'Maturing' => '#2563EB', 'Advanced' => '#16A34A'];
    ?>
    <div class="insight-val" style="font-size:18px;color:<?= $matColors[$curMaturity ?? ''] ?? 'var(--n-400)' ?>;">
      <?= $curMaturity ?? '—' ?>
    </div>
    <div class="insight-lbl">Maturity Level</div>
    <?php if ($prevCycle && $prevCycle['maturity_level'] && $curMaturity): ?>
      <div
        class="insight-delta <?= $curMaturity === $prevCycle['maturity_level'] ? 'flat' : ($matColors[$curMaturity] > '#9' ? 'up' : 'down') ?>">
        Was: <?= e($prevCycle['maturity_level']) ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Strongest dimension -->
  <div class="insight-card">
    <?php if ($topDim): ?>
      <div class="insight-val" style="font-size:20px;color:<?= e($topDim['color_hex']) ?>;">
        D<?= $topDim['dimension_no'] ?>
      </div>
      <div class="insight-lbl">Strongest — <?= e(substr($topDim['dimension_name'], 0, 28)) ?></div>
      <div class="insight-delta up"><?= $topDim['avg_pct'] ?>% average</div>
    <?php else: ?>
      <div class="insight-val">—</div>
      <div class="insight-lbl">Strongest Dimension</div>
    <?php endif; ?>
  </div>

  <!-- Weakest dimension -->
  <div class="insight-card">
    <?php if ($weakDim): ?>
      <div class="insight-val" style="font-size:20px;color:var(--red);">D<?= $weakDim['dimension_no'] ?></div>
      <div class="insight-lbl">Needs Work — <?= e(substr($weakDim['dimension_name'], 0, 28)) ?></div>
      <div class="insight-delta down"><?= $weakDim['avg_pct'] ?>% average</div>
    <?php else: ?>
      <div class="insight-val">—</div>
      <div class="insight-lbl">Weakest Dimension</div>
    <?php endif; ?>
  </div>

  <!-- Consistently weak count -->
  <div class="insight-card">
    <div class="insight-val" style="color:<?= count($consistentlyWeak) > 0 ? 'var(--red)' : 'var(--brand-600)' ?>;">
      <?= count($consistentlyWeak) ?>
    </div>
    <div class="insight-lbl">Indicators Below 2.5 Avg</div>
    <?php if (count($consistentlyWeak) > 0): ?>
      <div class="insight-delta down">Needs targeted intervention</div>
    <?php else: ?>
      <div class="insight-delta up">All indicators ≥ 2.5</div>
    <?php endif; ?>
  </div>

  <!-- Total cycles assessed -->
  <div class="insight-card">
    <div class="insight-val"><?= count($cycleHistory) ?></div>
    <div class="insight-lbl">Cycles Assessed</div>
    <?php if (count($cycleHistory) > 0): ?>
      <div class="insight-delta flat">Since SY <?= e($cycleHistory[0]['sy_label']) ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- Charts row -->
<div class="grid2" style="margin-bottom:18px;">
  <!-- Radar — with optional compare overlay -->
  <div class="chart-card">
    <div class="chart-card-head">
      <span class="chart-card-title">Dimension Performance Radar</span>
      <?php if ($compareSyId && !empty($dimAvgsCompare)): ?>
        <div style="display:flex;align-items:center;gap:10px;font-size:11.5px;">
          <span style="display:flex;align-items:center;gap:4px;"><span
              style="width:10px;height:10px;border-radius:50%;background:#16A34A;display:inline-block;"></span>SY
            <?= e(array_column($syears, 'label', 'sy_id')[$syId] ?? '') ?></span>
          <span style="display:flex;align-items:center;gap:4px;"><span
              style="width:10px;height:10px;border-radius:50%;background:#2563EB;display:inline-block;"></span>SY
            <?= e(array_column($syears, 'label', 'sy_id')[$compareSyId] ?? '') ?></span>
        </div>
      <?php endif; ?>
    </div>
    <div class="chart-card-body" style="display:flex;justify-content:center;align-items:center;min-height:300px;">
      <canvas id="radarChart" style="max-height:280px;"></canvas>
    </div>
  </div>

  <!-- Cycle-over-cycle overall score line chart -->
  <div class="chart-card">
    <div class="chart-card-head">
      <span class="chart-card-title">Overall Score Trend</span>
      <span style="font-size:12px;color:var(--n-400);"><?= count($cycleHistory) ?> cycle(s)</span>
    </div>
    <div class="chart-card-body" style="min-height:300px;display:flex;align-items:center;justify-content:center;">
      <?php if (count($cycleHistory) >= 1): ?>
        <canvas id="trendLineChart"></canvas>
      <?php else: ?>
        <p style="color:var(--n-400);font-size:13px;text-align:center;">Not enough cycles to show a trend.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Dimension trend over time (line per dimension) -->
<?php if (count($trendSYLabels) >= 2): ?>
  <div class="chart-card" style="margin-bottom:18px;">
    <div class="chart-card-head">
      <span class="chart-card-title">Dimension Trend — All Cycles</span>
      <span style="font-size:12px;color:var(--n-400);">Track how each dimension has moved over time</span>
    </div>
    <div class="chart-card-body"><canvas id="dimTrendChart" height="90"></canvas></div>
  </div>
<?php endif; ?>

<!-- Dimension Score Bar — with optional compare bars -->
<div class="chart-card" style="margin-bottom:18px;">
  <div class="chart-card-head">
    <span class="chart-card-title">Dimension Score Comparison</span>
    <div class="chart-legend" style="margin-bottom:0;">
      <?php foreach ($dimAvgs as $d):
        if (!$d['avg_pct'])
          continue; ?>
        <div class="chart-legend-item">
          <div class="chart-legend-swatch" style="background:<?= e($d['color_hex']) ?>;"></div>
          D<?= $d['dimension_no'] ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="chart-card-body"><canvas id="dimBarChart" height="80"></canvas></div>
</div>

<!-- Tabbed bottom section -->
<div class="tab-btns">
  <button class="tab-btn active" onclick="switchTab(this,'tabHistory')">Cycle History</button>
  <button class="tab-btn" onclick="switchTab(this,'tabWeak')">Weak This Cycle</button>
  <button class="tab-btn" onclick="switchTab(this,'tabConsistent')">Consistently Weak</button>
  <?php if ($compareSyId && !empty($dimAvgsCompare)): ?>
    <button class="tab-btn" onclick="switchTab(this,'tabCompare')">Side-by-Side</button>
  <?php endif; ?>
</div>

<!-- TAB: Cycle History -->
<div class="tab-panel active" id="tabHistory">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-head">
      <span class="card-title">Assessment History</span>
      <span style="font-size:12px;color:var(--n-400);"><?= count($cycleHistory) ?> cycle(s)</span>
    </div>
    <?php if ($cycleHistory): ?>
      <div class="tbl-wrap">
        <table class="tbl-enhanced">
          <thead>
            <tr>
              <th>#</th>
              <th>School Year</th>
              <th>Overall Score</th>
              <th>Maturity</th>
              <th>vs Prev</th>
              <th>Status</th>
              <th>Validated</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $prevScore = null;
            foreach ($cycleHistory as $i => $sc):
              $mat = sbmMaturityLevel(floatval($sc['overall_score']));
              $delta = $prevScore !== null ? round(floatval($sc['overall_score']) - $prevScore, 2) : null;
              $prevScore = floatval($sc['overall_score']);
              ?>
              <tr>
                <td style="width:32px;"><span
                    style="width:22px;height:22px;border-radius:6px;background:var(--n-100);color:var(--n-600);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;"><?= $i + 1 ?></span>
                </td>
                <td><strong style="font-size:13px;">SY <?= e($sc['sy_label']) ?></strong></td>
                <td>
                  <div class="score-bar-cell">
                    <div class="score-bar-track">
                      <div class="score-bar-fill"
                        style="width:<?= $sc['overall_score'] ?>%;background:<?= $mat['color'] ?>;"></div>
                    </div>
                    <span class="score-val" style="color:<?= $mat['color'] ?>;"><?= $sc['overall_score'] ?>%</span>
                  </div>
                </td>
                <td><span class="pill pill-<?= e($sc['maturity_level']) ?>"><?= e($sc['maturity_level']) ?></span></td>
                <td>
                  <?php if ($delta !== null): ?>
                    <span
                      style="font-size:12.5px;font-weight:700;color:<?= $delta > 0 ? '#16A34A' : ($delta < 0 ? '#DC2626' : '#9CA3AF') ?>;">
                      <?= $delta > 0 ? '▲ +' : '▼ ' ?>       <?= abs($delta) ?>%
                    </span>
                  <?php else: ?><span style="color:var(--n-400);font-size:12px;">First</span><?php endif; ?>
                </td>
                <td><span class="pill pill-<?= e($sc['status']) ?>"
                    style="font-size:10px;"><?= ucfirst($sc['status']) ?></span></td>
                <td style="font-size:12px;color:var(--n-400);">
                  <?= $sc['validated_at'] ? date('M d, Y', strtotime($sc['validated_at'])) : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><?= svgIcon('bar-chart-2') ?></div>
        <div class="empty-title">No cycle history yet</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- TAB: Weak This Cycle -->
<div class="tab-panel" id="tabWeak">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-head">
      <span class="card-title">Indicators Needing Attention — Current SY</span>
      <span style="font-size:12px;color:var(--n-400);">Lowest average ratings this cycle</span>
    </div>
    <?php if ($weakIndicatorRows): ?>
      <div class="card-body" style="padding:0;">
        <?php foreach ($weakIndicatorRows as $ind):
          $avgR = floatval($ind['avg_rating']);
          $pct = ($avgR / 4) * 100;
          $color = $avgR >= 3 ? 'var(--brand-600)' : ($avgR >= 2 ? 'var(--amber)' : 'var(--red)');
          ?>
          <div style="padding:12px 20px;border-bottom:1px solid var(--n-100);">
            <div class="flex-cb" style="margin-bottom:4px;">
              <div>
                <span
                  style="font-size:10.5px;font-weight:700;color:var(--n-400);text-transform:uppercase;letter-spacing:.05em;"><?= e($ind['indicator_code']) ?></span>
                <span
                  style="font-size:10.5px;color:var(--n-400);margin-left:6px;padding:1px 7px;background:var(--n-100);border-radius:4px;"><?= e($ind['dimension_name']) ?></span>
              </div>
              <span style="font-size:13px;font-weight:700;color:<?= $color ?>;"><?= number_format($avgR, 2) ?>/4.00</span>
            </div>
            <div style="font-size:12.5px;color:var(--n-700);margin-bottom:5px;line-height:1.45;">
              <?= e(substr($ind['indicator_text'], 0, 100)) . '…' ?>
            </div>
            <div class="weak-prog">
              <div class="weak-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
            </div>
            <div style="font-size:11px;color:var(--n-400);margin-top:4px;"><?= $ind['response_count'] ?> response(s)</div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><?= svgIcon('alert-circle') ?></div>
        <div class="empty-title">No indicator data yet</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- TAB: Consistently Weak -->
<div class="tab-panel" id="tabConsistent">
  <div class="card" style="margin-bottom:18px;">
    <div class="card-head">
      <span class="card-title">Consistently Weak Indicators</span>
      <span style="font-size:12px;color:var(--n-400);">Average ≤ 2.5 across all assessed cycles</span>
    </div>
    <?php if ($consistentlyWeak): ?>
      <div class="card-body" style="padding:0;">
        <?php foreach ($consistentlyWeak as $cw):
          $avgR = floatval($cw['avg_rating']);
          $color = $avgR >= 2 ? 'var(--amber)' : 'var(--red)';
          $pct = ($avgR / 4) * 100;
          ?>
          <div class="cw-row">
            <div class="cw-badge"
              style="background:<?= $avgR < 2 ? 'var(--red-bg)' : 'var(--amber-bg)' ?>;color:<?= $color ?>;">
              <?= e($cw['indicator_code']) ?>
            </div>
            <div class="cw-info">
              <div class="cw-title"><?= e(substr($cw['indicator_text'], 0, 95)) . '…' ?></div>
              <div class="cw-meta">
                <?= e($cw['dimension_name']) ?> ·
                Avg: <strong style="color:<?= $color ?>;"><?= number_format($avgR, 2) ?>/4.00</strong> ·
                Worst: <?= number_format($cw['worst_cycle_avg'], 2) ?> ·
                Best: <?= number_format($cw['best_cycle_avg'], 2) ?>
              </div>
              <div class="cw-bar-track">
                <div class="cw-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
              </div>
            </div>
            <div style="font-size:11px;font-weight:700;color:var(--red);text-align:center;min-width:48px;">
              Priority<br>Action
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><?= svgIcon('check-circle') ?></div>
        <div class="empty-title">No consistently weak indicators</div>
        <div class="empty-sub">All indicators are averaging above 2.5 across cycles.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- TAB: Side-by-Side Comparison (only shown when compare_sy is set) -->
<?php if ($compareSyId && !empty($dimAvgsCompare)): ?>
  <div class="tab-panel" id="tabCompare">
    <div class="card" style="margin-bottom:18px;">
      <div class="card-head">
        <span class="card-title">Side-by-Side Dimension Comparison</span>
        <span style="font-size:12px;color:var(--n-400);">
          SY <?= e(array_column($syears, 'label', 'sy_id')[$syId] ?? '') ?>
          vs
          SY <?= e(array_column($syears, 'label', 'sy_id')[$compareSyId] ?? '') ?>
        </span>
      </div>
      <div class="tbl-wrap">
        <table class="tbl-enhanced">
          <thead>
            <tr>
              <th>Dimension</th>
              <th>SY <?= e(array_column($syears, 'label', 'sy_id')[$syId] ?? '') ?></th>
              <th>SY <?= e(array_column($syears, 'label', 'sy_id')[$compareSyId] ?? '') ?></th>
              <th>Change</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $cmpByDim = array_column($dimAvgsCompare, null, 'dimension_no');
            foreach ($dimAvgs as $d):
              $curr = floatval($d['avg_pct'] ?? 0);
              $prev = floatval($cmpByDim[$d['dimension_no']]['avg_pct'] ?? 0);
              $chg = round($curr - $prev, 1);
              $chgC = $chg > 0 ? '#16A34A' : ($chg < 0 ? '#DC2626' : '#9CA3AF');
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:7px;">
                    <span
                      style="width:10px;height:10px;border-radius:2px;background:<?= e($d['color_hex']) ?>;flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:12.5px;font-weight:600;">D<?= $d['dimension_no'] ?>:
                      <?= e(substr($d['dimension_name'], 0, 30)) ?></span>
                  </div>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:60px;height:5px;background:var(--n-100);border-radius:999px;overflow:hidden;">
                      <div
                        style="width:<?= $curr ?>%;height:100%;background:<?= e($d['color_hex']) ?>;border-radius:999px;">
                      </div>
                    </div>
                    <strong style="font-size:13px;color:<?= e($d['color_hex']) ?>;"><?= $curr ?>%</strong>
                  </div>
                </td>
                <td>
                  <?php if ($prev > 0): ?>
                    <div style="display:flex;align-items:center;gap:6px;">
                      <div style="width:60px;height:5px;background:var(--n-100);border-radius:999px;overflow:hidden;">
                        <div style="width:<?= $prev ?>%;height:100%;background:#9CA3AF;border-radius:999px;"></div>
                      </div>
                      <span style="font-size:13px;color:var(--n-500);"><?= $prev ?>%</span>
                    </div>
                  <?php else: ?><span style="font-size:12px;color:var(--n-400);">No data</span><?php endif; ?>
                </td>
                <td>
                  <span style="font-size:13px;font-weight:700;color:<?= $chgC ?>;">
                    <?= $chg > 0 ? '▲ +' : ($chg < 0 ? '▼ ' : '') ?>     <?= abs($chg) ?>%
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
  // ── Data from PHP ─────────────────────────────────────────────
  const dimLabels = <?= json_encode(array_map(fn($d) => 'D' . $d['dimension_no'], $dimAvgs)) ?>;
  const dimColors = <?= json_encode(array_column($dimAvgs, 'color_hex')) ?>;
  const dimValues = <?= json_encode(array_map(fn($d) => $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $dimAvgs)) ?>;
  const dimValCmp = <?= json_encode(!empty($dimAvgsCompare) ? array_map(fn($d) => $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $dimAvgsCompare) : []) ?>;
  const radarNames = <?= json_encode(array_map(fn($d) => 'D' . $d['dimension_no'] . ': ' . $d['dimension_name'], $dimAvgs)) ?>;
  const cycleLabels = <?= json_encode(array_column($cycleHistory, 'sy_label')) ?>;
  const cycleScores = <?= json_encode(array_map(fn($c) => floatval($c['overall_score']), $cycleHistory)) ?>;
  const trendSYLabels = <?= json_encode($trendSYLabels) ?>;
  const trendByDim = <?= json_encode($trendByDim) ?>;
  const dimMeta = <?= json_encode(array_map(fn($d) => ['no' => $d['dimension_no'], 'name' => $d['dimension_name'], 'color' => $d['color_hex']], $dimAvgs)) ?>;
  const compareSyLabel = <?= json_encode(!empty($dimAvgsCompare) ? (array_column($syears, 'label', 'sy_id')[$compareSyId] ?? '') : '') ?>;
  const currSyLabel = <?= json_encode(array_column($syears, 'label', 'sy_id')[$syId] ?? '') ?>;

  // ── Tab switching ────────────────────────────────────────────
  function switchTab(btn, panelId) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId)?.classList.add('active');
  }

  // ── Radar chart ──────────────────────────────────────────────
  if (dimValues.some(v => v > 0)) {
    const radarDatasets = [{
      label: 'SY ' + currSyLabel,
      data: dimValues,
      backgroundColor: 'rgba(22,163,74,.13)',
      borderColor: '#16A34A',
      pointBackgroundColor: dimColors,
      pointRadius: 5, borderWidth: 2,
    }];
    if (dimValCmp.length && dimValCmp.some(v => v > 0)) {
      radarDatasets.push({
        label: 'SY ' + compareSyLabel,
        data: dimValCmp,
        backgroundColor: 'rgba(37,99,235,.10)',
        borderColor: '#2563EB',
        pointBackgroundColor: '#2563EB',
        pointRadius: 4, borderWidth: 2, borderDash: [4, 4],
      });
    }
    new Chart(document.getElementById('radarChart'), {
      type: 'radar',
      data: { labels: dimLabels, datasets: radarDatasets },
      options: {
        scales: { r: { min: 0, max: 100, ticks: { font: { size: 10 }, stepSize: 25, backdropColor: 'transparent' }, pointLabels: { font: { size: 13, weight: '700' }, color: '#374151' } } },
        plugins: { legend: { display: radarDatasets.length > 1, position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }, tooltip: { callbacks: { title: ctx => radarNames[ctx[0].dataIndex], label: ctx => ' ' + ctx.raw + '%' } } },
        maintainAspectRatio: true,
        aspectRatio: 1,
      }
    });
  } else {
    document.getElementById('radarChart').closest('.chart-card-body').innerHTML = '<p style="text-align:center;color:var(--n-400);padding:48px 0;font-size:13px;">No dimension data for this school year.</p>';
  }

  // ── Overall score trend line ─────────────────────────────────
  if (cycleScores.length >= 1 && document.getElementById('trendLineChart')) {
    new Chart(document.getElementById('trendLineChart'), {
      type: 'line',
      data: {
        labels: cycleLabels,
        datasets: [{
          label: 'Overall Score (%)',
          data: cycleScores,
          borderColor: '#16A34A',
          backgroundColor: 'rgba(22,163,74,.08)',
          pointBackgroundColor: cycleScores.map(s => s >= 76 ? '#16A34A' : (s >= 51 ? '#2563EB' : (s >= 26 ? '#D97706' : '#DC2626'))),
          pointRadius: 6, pointHoverRadius: 8,
          borderWidth: 2.5, tension: 0.3, fill: true,
        }]
      },
      options: {
        scales: {
          y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
          x: { ticks: { font: { size: 11, weight: '600' } }, grid: { display: false } }
        },
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ' Score: ' + ctx.raw + '%' } }
        },
        responsive: true, maintainAspectRatio: true,
        aspectRatio: 1.5,
      }
    });
  }

  // ── Dimension trend lines (one line per dimension) ───────────
  if (document.getElementById('dimTrendChart') && trendSYLabels.length >= 2) {
    const dimTrendDatasets = dimMeta.map(dm => {
      const data = trendSYLabels.map(lbl => trendByDim[dm.no]?.[lbl] ?? null);
      return {
        label: 'D' + dm.no + ': ' + dm.name,
        data,
        borderColor: dm.color,
        backgroundColor: dm.color + '22',
        pointBackgroundColor: dm.color,
        pointRadius: 5, borderWidth: 2, tension: 0.3,
      };
    });
    new Chart(document.getElementById('dimTrendChart'), {
      type: 'line',
      data: { labels: trendSYLabels, datasets: dimTrendDatasets },
      options: {
        scales: {
          y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
          x: { ticks: { font: { size: 11, weight: '600' } }, grid: { display: false } }
        },
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10, usePointStyle: true, pointStyleWidth: 8 } }
        },
        responsive: true, maintainAspectRatio: true,
      }
    });
  }

  // ── Dimension bar chart — with optional compare bars ─────────
  if (dimValues.some(v => v !== null && v > 0)) {
    const barDatasets = [{
      label: 'SY ' + currSyLabel,
      data: dimValues,
      backgroundColor: dimColors.map(c => c + '30'),
      borderColor: dimColors,
      borderWidth: 2, borderRadius: 8, borderSkipped: false,
    }];
    if (dimValCmp.length && dimValCmp.some(v => v > 0)) {
      barDatasets.push({
        label: 'SY ' + compareSyLabel,
        data: dimValCmp,
        backgroundColor: '#9CA3AF30',
        borderColor: '#9CA3AF',
        borderWidth: 2, borderRadius: 8, borderSkipped: false,
      });
    }
    new Chart(document.getElementById('dimBarChart'), {
      type: 'bar',
      data: { labels: dimLabels, datasets: barDatasets },
      options: {
        scales: {
          y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
          x: { ticks: { font: { size: 12, weight: '600' } }, grid: { display: false } }
        },
        plugins: {
          legend: { display: barDatasets.length > 1, position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }
        },
        responsive: true, maintainAspectRatio: true,
      }
    });
  } else {
    document.getElementById('dimBarChart').closest('.chart-card-body').innerHTML = '<p style="text-align:center;color:var(--n-400);padding:48px 0;font-size:13px;">No dimension score data for this school year.</p>';
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>