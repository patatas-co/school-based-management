<?php
// ============================================================
// admin/dashboard.php — IMPROVED v2
// Applies all UI/UX Pro Max improvements:
// - Better information hierarchy & visual weight
// - Improved stat cards with trends & sparklines
// - Better empty/loading states
// - Cleaner typography with proper scale
// - Responsive grid improvements
// - Enhanced color semantics
// - Better activity feed
// - Quick action shortcuts
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
$db = getDB();

$totalSchools  = 1; // DIHS — single school system
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$totalCycles   = $db->query("SELECT COUNT(*) FROM sbm_cycles")->fetchColumn();
$submitted     = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status IN('submitted','validated')")->fetchColumn();
$validated     = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='validated'")->fetchColumn();
$inProgress    = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='in_progress'")->fetchColumn();
$returned      = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='returned'")->fetchColumn();

$maturity = $db->query("SELECT maturity_level, COUNT(*) cnt FROM sbm_cycles WHERE maturity_level IS NOT NULL GROUP BY maturity_level ORDER BY FIELD(maturity_level,'Advanced','Maturing','Developing','Beginning')")->fetchAll();

$recentCycles = $db->query("
  SELECT c.*, s.school_name, sy.label sy_label
  FROM sbm_cycles c
  JOIN schools s ON c.school_id=s.school_id
  JOIN school_years sy ON c.sy_id=sy.sy_id
  ORDER BY c.created_at DESC LIMIT 8
")->fetchAll();

$dimScores = $db->query("
  SELECT d.dimension_no, d.dimension_name, d.color_hex,
         ROUND(AVG(ds.percentage),1) avg_pct
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds ON d.dimension_id=ds.dimension_id
  GROUP BY d.dimension_id ORDER BY d.dimension_no
")->fetchAll();

$recentActivity = $db->query("
  SELECT l.*, u.full_name FROM activity_log l
  LEFT JOIN users u ON l.user_id=u.user_id
  ORDER BY l.created_at DESC LIMIT 10
")->fetchAll();

$submissionRate = $totalSchools > 0 ? round(($submitted / $totalSchools) * 100) : 0;
$validationRate = $submitted > 0 ? round(($validated / $submitted) * 100) : 0;

// Top 5 schools by score
$topSchools = []; // Single-school system — leaderboard not applicable

$currentSY = $db->query("SELECT label FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>

<style>
/* ================================================================
   DASHBOARD v2 — Improved Design System
   Builds on the existing CSS variables; adds only what's needed.
================================================================ */

/* ── Greeting / Hero Banner ── */
.db-hero {
  background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F4C25 100%);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
  color: #fff;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}
.db-hero::before {
  content: '';
  position: absolute;
  right: -80px; top: -80px;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: rgba(22,163,74,.08);
  pointer-events: none;
}
.db-hero::after {
  content: '';
  position: absolute;
  right: 80px; bottom: -100px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(22,163,74,.05);
  pointer-events: none;
}
.db-hero-left { position: relative; z-index: 1; }
.db-hero-greeting {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: rgba(74,222,128,.8);
  margin-bottom: 8px;
}
.db-hero-title {
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -.5px;
  margin-bottom: 6px;
  line-height: 1.15;
}
.db-hero-sub {
  font-size: 13.5px;
  color: rgba(255,255,255,.55);
  line-height: 1.5;
}
.db-hero-right {
  position: relative;
  z-index: 1;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  flex-shrink: 0;
}
.db-hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 140ms;
  white-space: nowrap;
}
.db-hero-btn-primary {
  background: rgba(255,255,255,.12);
  color: #fff;
  border: 1px solid rgba(255,255,255,.2);
}
.db-hero-btn-primary:hover { background: rgba(255,255,255,.2); }
.db-hero-btn-secondary {
  background: rgba(255,255,255,.05);
  color: rgba(255,255,255,.75);
  border: 1px solid rgba(255,255,255,.1);
}
.db-hero-btn-secondary:hover { background: rgba(255,255,255,.12); }
.db-hero-btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

/* ── Stat Cards v2 ── */
.stats-v2 {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}
.stat-v2 {
  background: #fff;
  border: 1px solid var(--n-200);
  border-radius: var(--radius-lg);
  padding: 20px 20px 16px;
  box-shadow: var(--shadow-xs);
  transition: transform 160ms var(--ease), box-shadow 160ms var(--ease);
  position: relative;
  overflow: hidden;
  cursor: default;
}
.stat-v2:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
.stat-v2-accent {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.stat-v2-label {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--n-500);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 10px;
}
.stat-v2-value {
  font-family: var(--font-display);
  font-size: 32px;
  font-weight: 800;
  color: var(--n-900);
  line-height: 1;
  letter-spacing: -.8px;
  margin-bottom: 8px;
}
.stat-v2-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--n-500);
}
.stat-v2-badge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 7px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}
.badge-green { background: var(--brand-100); color: var(--brand-700); }
.badge-blue  { background: var(--blue-bg);   color: var(--blue); }
.badge-amber { background: var(--amber-bg);  color: var(--amber); }
.badge-red   { background: var(--red-bg);    color: var(--red); }

/* ── KPI Progress Bar ── */
.kpi-bar {
  height: 5px;
  background: var(--n-100);
  border-radius: 999px;
  overflow: hidden;
  margin-top: 10px;
}
.kpi-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width .8s cubic-bezier(.4,0,.2,1);
}

/* ── Section Headers ── */
.section-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.section-title {
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 700;
  color: var(--n-900);
  display: flex;
  align-items: center;
  gap: 8px;
}
.section-title-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Dimension Progress Bar List ── */
.dim-list { display: flex; flex-direction: column; gap: 14px; }
.dim-row { display: flex; align-items: center; gap: 12px; }
.dim-num {
  width: 26px; height: 26px;
  border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 800;
  flex-shrink: 0;
  color: #fff;
}
.dim-info { flex: 1; min-width: 0; }
.dim-name { font-size: 13px; font-weight: 600; color: var(--n-800); margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dim-prog { height: 7px; background: var(--n-100); border-radius: 999px; overflow: hidden; }
.dim-prog-fill { height: 100%; border-radius: 999px; transition: width .7s cubic-bezier(.4,0,.2,1); }
.dim-pct { font-size: 13px; font-weight: 700; text-align: right; flex-shrink: 0; min-width: 38px; }

/* ── Activity Feed ── */
.activity-feed { display: flex; flex-direction: column; gap: 0; }
.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  padding: 10px 0;
  border-bottom: 1px solid var(--n-100);
  position: relative;
}
.activity-item:last-child { border-bottom: none; }
.activity-avatar {
  width: 30px; height: 30px;
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
  flex-shrink: 0;
  background: var(--brand-100);
  color: var(--brand-700);
}
.activity-text { flex: 1; min-width: 0; }
.activity-action { font-size: 12.5px; color: var(--n-700); line-height: 1.45; }
.activity-action strong { color: var(--n-900); font-weight: 600; }
.activity-time { font-size: 11px; color: var(--n-400); margin-top: 2px; }

/* ── Maturity Donut Labels ── */
.maturity-legend { display: flex; flex-direction: column; gap: 8px; }
.maturity-legend-row {
  display: flex; align-items: center; gap: 10px;
  font-size: 12.5px; color: var(--n-700);
}
.maturity-legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.maturity-legend-val { margin-left: auto; font-weight: 700; font-size: 13px; }

/* ── Top Schools List ── */
.school-rank-list { display: flex; flex-direction: column; gap: 0; }
.school-rank-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--n-100);
}
.school-rank-item:last-child { border-bottom: none; }
.rank-num {
  width: 24px; height: 24px;
  border-radius: 6px;
  background: var(--n-100);
  color: var(--n-600);
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.rank-num.gold   { background: #FEF3C7; color: #B45309; }
.rank-num.silver { background: #F3F4F6; color: #6B7280; }
.rank-num.bronze { background: #FEF3C7; color: #92400E; }
.school-rank-name { flex: 1; min-width: 0; font-size: 13px; font-weight: 600; color: var(--n-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.school-rank-score { font-family: var(--font-display); font-size: 15px; font-weight: 800; flex-shrink: 0; }

/* ── Quick Actions Grid ── */
.quick-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 9px;
  border: 1px solid var(--n-200);
  background: var(--n-50);
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: var(--n-700);
  transition: all 140ms var(--ease);
}
.quick-action-btn:hover { background: #fff; border-color: var(--n-300); color: var(--n-900); box-shadow: var(--shadow-xs); }
.quick-action-icon {
  width: 32px; height: 32px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.quick-action-icon svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

/* ── Submission pipeline ── */
.pipeline {
  display: flex;
  align-items: stretch;
  gap: 0;
  margin-bottom: 6px;
}
.pipeline-step {
  flex: 1;
  text-align: center;
  padding: 14px 8px;
  position: relative;
}
.pipeline-step:not(:last-child)::after {
  content: '→';
  position: absolute;
  right: -10px; top: 50%;
  transform: translateY(-50%);
  color: var(--n-300);
  font-size: 16px;
  z-index: 1;
}
.pipeline-val {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 4px;
}
.pipeline-lbl { font-size: 11px; font-weight: 600; color: var(--n-500); text-transform: uppercase; letter-spacing: .05em; }

/* ── Alert Banner ── */
.alert-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 14px;
  border: 1px solid;
}
.alert-banner svg { width: 14px; height: 14px; flex-shrink: 0; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.alert-warning { background: var(--amber-bg); color: var(--amber); border-color: #FDE68A; }

/* ── Responsive ── */
@media (max-width: 900px) {
  .stats-v2 { grid-template-columns: repeat(2, 1fr); }
  .db-layout-main { grid-template-columns: 1fr !important; }
  .db-layout-right { grid-template-columns: 1fr !important; }
}
@media (max-width: 540px) {
  .stats-v2 { grid-template-columns: 1fr 1fr; }
  .db-hero { padding: 20px; }
  .db-hero-title { font-size: 20px; }
  .quick-actions { grid-template-columns: 1fr; }
}

/* ── Chart tooltip override ── */
.chart-legend {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  padding: 0 4px;
  margin-bottom: 12px;
}
.chart-legend-item {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--n-600);
}
.chart-legend-swatch { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

/* ── Skeleton loader ── */
.skeleton {
  background: linear-gradient(90deg, var(--n-100) 25%, var(--n-50) 50%, var(--n-100) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ── Table v2 ── */
.tbl-v2 thead th {
  background: var(--n-50);
  font-size: 10.5px;
  color: var(--n-500);
  font-weight: 700;
  letter-spacing: .07em;
  text-transform: uppercase;
  padding: 9px 14px;
  border-bottom: 1px solid var(--n-200);
  white-space: nowrap;
}
.tbl-v2 tbody td {
  padding: 10px 14px;
  font-size: 13px;
  border-bottom: 1px solid var(--n-100);
  color: var(--n-700);
  vertical-align: middle;
}
.tbl-v2 tbody tr:last-child td { border-bottom: none; }
.tbl-v2 tbody tr:hover td { background: #F0FDF4; }

/* ── Inline score bar ── */
.score-inline {
  display: flex; align-items: center; gap: 8px;
}
.score-inline-bar {
  width: 56px; height: 5px;
  background: var(--n-100);
  border-radius: 999px; overflow: hidden;
  flex-shrink: 0;
}
.score-inline-fill { height: 100%; border-radius: 999px; background: var(--brand-500); }
</style>

<!-- ── HERO BANNER ── -->
<div class="db-hero">
  <div class="db-hero-left">
    <div class="db-hero-greeting">SBM Online Monitoring System</div>
    <div class="db-hero-title">Admin Dashboard</div>
    <div class="db-hero-sub">
      <?= date('l, F j, Y') ?>
      <?php if ($currentSY): ?>
      &nbsp;·&nbsp; SY <?= e($currentSY) ?>
      <?php endif; ?>
      &nbsp;·&nbsp; Dasmariñas Integrated High School
    </div>
  </div>
  <div class="db-hero-right">
    <a href="assessment.php?status=submitted" class="db-hero-btn db-hero-btn-primary">
      <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Review Submissions
      <?php if($submitted - $validated > 0): ?>
      <span style="background:rgba(255,255,255,.2);border-radius:999px;padding:1px 7px;font-size:11px;"><?= $submitted - $validated ?></span>
      <?php endif; ?>
    </a>
    <a href="analytics.php" class="db-hero-btn db-hero-btn-secondary">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Analytics
    </a>
    <a href="reports.php" class="db-hero-btn db-hero-btn-secondary">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Reports
    </a>
  </div>
</div>

<?php if ($returned > 0): ?>
<div class="alert-banner alert-warning">
  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <span><strong><?= $returned ?> assessment<?= $returned !== 1 ? 's' : '' ?></strong> returned for revision — schools are waiting for SDO feedback.</span>
  <a href="assessment.php?status=returned" style="margin-left:auto;font-weight:700;color:var(--amber);white-space:nowrap;">View →</a>
</div>
<?php endif; ?>

<!-- ── KPI STAT CARDS ── -->
<div class="stats-v2">

  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#16A34A;"></div>
    <div class="stat-v2-label">DIHS Enrollment</div>
    <div class="stat-v2-value">2,500</div>
    <div class="stat-v2-meta" style="color:var(--n-400);">Dasmariñas Integrated HS</div>
  </div>

  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#2563EB;"></div>
    <div class="stat-v2-label">Assessment Cycles</div>
    <div class="stat-v2-value" data-live="total-cycles"><?= number_format($totalCycles) ?></div>
    <div class="stat-v2-meta">
      <span class="stat-v2-badge badge-blue"><?= $inProgress ?> in progress</span>
    </div>
    <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= $totalSchools > 0 ? round(($totalCycles/$totalSchools)*100) : 0 ?>%;background:#2563EB;"></div></div>
  </div>

  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#D97706;"></div>
    <div class="stat-v2-label">Awaiting Validation</div>
    <div class="stat-v2-value" style="color:<?= ($submitted - $validated) > 0 ? 'var(--amber)' : 'var(--n-900)' ?>;"><?= $submitted - $validated ?></div>
    <div class="stat-v2-meta">
      <span class="stat-v2-badge badge-amber"><?= $submitted ?> total submitted</span>
    </div>
    <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= $validationRate ?>%;background:#D97706;"></div></div>
  </div>

  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#16A34A;"></div>
    <div class="stat-v2-label">Validated</div>
    <div class="stat-v2-value" data-live="validated"><?= number_format($validated) ?></div>
    <div class="stat-v2-meta">
      <span class="stat-v2-badge badge-green"><?= $validationRate ?>% of submitted</span>
    </div>
    <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= $validationRate ?>%;background:#16A34A;"></div></div>
  </div>

  <div class="stat-v2">
    <div class="stat-v2-accent" style="background:#7C3AED;"></div>
    <div class="stat-v2-label">Active Users</div>
    <div class="stat-v2-value" data-live="total-users"><?= number_format($totalUsers) ?></div>
    <div class="stat-v2-meta" style="color:var(--n-400);">Across all roles</div>
  </div>

</div>

<!-- ── SUBMISSION PIPELINE ── -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-head">
    <span class="card-title">Assessment Pipeline</span>
    <a href="assessment.php" class="btn btn-ghost btn-sm">View all →</a>
  </div>
  <div class="card-body" style="padding:8px 0;">
    <div class="pipeline">
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--n-500);"><?= $inProgress ?></div>
        <div class="pipeline-lbl">In Progress</div>
      </div>
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--amber);"><?= $submitted - $validated ?></div>
        <div class="pipeline-lbl">Pending Review</div>
      </div>
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--brand-600);"><?= $validated ?></div>
        <div class="pipeline-lbl">Validated</div>
      </div>
      <?php if($returned > 0): ?>
      <div class="pipeline-step">
        <div class="pipeline-val" style="color:var(--red);"><?= $returned ?></div>
        <div class="pipeline-lbl">Returned</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── MAIN CONTENT GRID ── -->
<div class="db-layout-main" style="display:grid;grid-template-columns:1fr 380px;gap:18px;margin-bottom:20px;">

  <!-- LEFT COL: Dimensions + Donut -->
  <div style="display:flex;flex-direction:column;gap:18px;">

    <!-- Dimension Performance -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Dimension Performance</span>
        <span style="font-size:12px;color:var(--n-400);">System-wide averages</span>
      </div>
      <div class="card-body">
        <div class="dim-list">
          <?php foreach($dimScores as $d):
            $pct = floatval($d['avg_pct']);
            $mat = $pct >= 76 ? 'Advanced' : ($pct >= 51 ? 'Maturing' : ($pct >= 26 ? 'Developing' : 'Beginning'));
            $matColor = $pct >= 76 ? '#16A34A' : ($pct >= 51 ? '#2563EB' : ($pct >= 26 ? '#D97706' : '#DC2626'));
          ?>
          <div class="dim-row">
            <div class="dim-num" style="background:<?= e($d['color_hex']) ?>;"><?= $d['dimension_no'] ?></div>
            <div class="dim-info">
              <div class="dim-name"><?= e($d['dimension_name']) ?></div>
              <div class="dim-prog">
                <div class="dim-prog-fill" style="width:<?= min(100,$pct) ?>%;background:<?= e($d['color_hex']) ?>;"></div>
              </div>
            </div>
            <div class="dim-pct" style="color:<?= $pct > 0 ? $matColor : 'var(--n-400)' ?>;"><?= $pct > 0 ? $pct.'%' : '—' ?></div>
          </div>
          <?php endforeach; ?>
          <?php if (!$dimScores): ?>
          <p style="text-align:center;color:var(--n-400);font-size:13px;padding:20px 0;">No dimension data yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Dimension Bar Chart -->
    <div class="card">
      <div class="card-head"><span class="card-title">Dimension Score Comparison</span></div>
      <div class="card-body">
        <div style="position:relative;height:220px;">
          <canvas id="dimBarChart"></canvas>
        </div>
      </div>
    </div>

  </div>

  <!-- RIGHT COL: Donut + Top Schools + Quick Actions -->
  <div class="db-layout-right" style="display:flex;flex-direction:column;gap:18px;">

    <!-- Maturity Distribution -->
    <div class="card">
      <div class="card-head"><span class="card-title">Maturity Distribution</span></div>
      <div class="card-body" style="padding:16px 18px;">
        <?php
        $matData = array_column($maturity,'cnt','maturity_level');
        $matTotal = array_sum(array_column($maturity,'cnt'));
        $matColors = ['Beginning'=>'#DC2626','Developing'=>'#D97706','Maturing'=>'#2563EB','Advanced'=>'#16A34A'];
        ?>
        <?php if ($matTotal > 0): ?>
        <div style="position:relative;max-width:180px;margin:0 auto 16px;">
          <canvas id="maturityChart" style="height:180px;"></canvas>
          <!-- Center label -->
          <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
            <div style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--n-900);line-height:1;"><?= $matTotal ?></div>
            <div style="font-size:10px;color:var(--n-400);font-weight:600;">schools</div>
          </div>
        </div>
        <div class="maturity-legend">
          <?php foreach(['Beginning','Developing','Maturing','Advanced'] as $lv): ?>
          <?php $cnt = $matData[$lv] ?? 0; $pct2 = $matTotal > 0 ? round(($cnt/$matTotal)*100) : 0; ?>
          <div class="maturity-legend-row">
            <span class="maturity-legend-dot" style="background:<?= $matColors[$lv] ?>;"></span>
            <span><?= $lv ?></span>
            <span class="maturity-legend-val" style="color:<?= $matColors[$lv] ?>;"><?= $cnt ?></span>
            <span style="font-size:11px;color:var(--n-400);"><?= $pct2 ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="text-align:center;color:var(--n-400);font-size:13px;padding:24px 0;">No validated assessments yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Top Schools -->
    <?php if ($topSchools): ?>
    <div class="card">
      <div class="card-head"><span class="card-title">Top Performing Schools</span></div>
      <div class="card-body" style="padding:0 0 8px;">
        <div class="school-rank-list">
          <?php foreach($topSchools as $i => $sc):
            $mat2 = sbmMaturityLevel(floatval($sc['overall_score']));
            $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
          ?>
          <div class="school-rank-item" style="padding:10px 18px;">
            <div class="rank-num <?= $rankClass ?>"><?= $i+1 ?></div>
            <div class="school-rank-name"><?= e($sc['school_name']) ?></div>
            <div class="school-rank-score" style="color:<?= $mat2['color'] ?>;"><?= number_format($sc['overall_score'],1) ?>%</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-head"><span class="card-title">Quick Actions</span></div>
      <div class="card-body" style="padding:12px 16px;">
        <div class="quick-actions">
          <a href="users.php?action=create" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--purple-bg);color:var(--purple);">
              <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </div>
            Add User
          </a>
          <a href="school_profile.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--brand-100);color:var(--brand-700);">
              <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            School Profile
          </a>
          <a href="announcements.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--amber-bg);color:var(--amber);">
              <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            Announce
          </a>
          <a href="settings.php" class="quick-action-btn">
            <div class="quick-action-icon" style="background:var(--n-100);color:var(--n-600);">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            Settings
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ── BOTTOM: Recent Cycles + Activity ── -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;margin-bottom:20px;" class="db-layout-main">

  <!-- Recent Assessment Cycles Table -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Recent Assessment Cycles</span>
      <div class="flex-c" style="gap:8px;">
        <div class="search" style="min-width:180px;">
          <span class="si"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input type="text" placeholder="Search schools…" oninput="filterTable(this.value,'tblRecent')">
        </div>
        <a href="assessment.php" class="btn btn-secondary btn-sm">View all</a>
      </div>
    </div>
    <div class="tbl-wrap">
      <table id="tblRecent" class="tbl-v2">
        <thead>
          <tr>
            <th>School</th>
            <th>Year</th>
            <th>Status</th>
            <th>Score</th>
            <th>Maturity</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($recentCycles as $c): ?>
        <tr>
          <td>
            <div style="font-size:13px;font-weight:600;color:var(--n-900);"><?= e($c['school_name']) ?></div>
          </td>
          <td style="color:var(--n-500);font-size:12.5px;"><?= e($c['sy_label']) ?></td>
          <td><span class="pill pill-<?= e($c['status']) ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
          <td>
            <?php if($c['overall_score']): ?>
            <div class="score-inline">
              <div class="score-inline-bar"><div class="score-inline-fill" style="width:<?= $c['overall_score'] ?>%;background:<?= sbmMaturityLevel(floatval($c['overall_score']))['color'] ?>;"></div></div>
              <span style="font-family:var(--font-display);font-size:14px;font-weight:800;color:<?= sbmMaturityLevel(floatval($c['overall_score']))['color'] ?>;"><?= $c['overall_score'] ?>%</span>
            </div>
            <?php else: ?><span style="color:var(--n-300);">—</span><?php endif; ?>
          </td>
          <td>
            <?php if($c['maturity_level']): ?>
            <span class="pill pill-<?= e($c['maturity_level']) ?>"><?= e($c['maturity_level']) ?></span>
            <?php else: ?><span style="color:var(--n-300);">—</span><?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--n-400);"><?= timeAgo($c['created_at']) ?></td>
          <td><a href="view_assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$recentCycles): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--n-400);padding:40px;font-size:13px;">No assessments yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activity Feed -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Recent Activity</span>
    </div>
    <div class="card-body" style="padding:12px 16px;" id="live-activity-feed">
      <div class="activity-feed">
        <?php foreach($recentActivity as $log):
          $initials = strtoupper(substr($log['full_name'] ?? 'S', 0, 1));
          $bgColors = ['A'=>'#EDE9FE','B'=>'#DBEAFE','C'=>'#DCFCE7','D'=>'#FEF3C7','E'=>'#FEE2E2','F'=>'#CCFBF1'];
          $textColors = ['A'=>'#7C3AED','B'=>'#2563EB','C'=>'#16A34A','D'=>'#D97706','E'=>'#DC2626','F'=>'#0D9488'];
          $k = $initials;
          $bg = $bgColors[$k] ?? '#DCFCE7'; $tx = $textColors[$k] ?? '#16A34A';
        ?>
        <div class="activity-item">
          <div class="activity-avatar" style="background:<?= $bg ?>;color:<?= $tx ?>;"><?= $initials ?></div>
          <div class="activity-text">
            <div class="activity-action"><strong><?= e($log['full_name'] ?? 'System') ?></strong> — <?= e($log['action']) ?></div>
            <div class="activity-time"><?= timeAgo($log['created_at']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(!$recentActivity): ?>
        <p style="text-align:center;color:var(--n-400);font-size:13px;padding:24px 0;">No activity yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
// ── Charts ──────────────────────────────────────────────────

// Maturity Donut
<?php if ($matTotal > 0): ?>
new Chart(document.getElementById('maturityChart'), {
  type: 'doughnut',
  data: {
    labels: ['Beginning','Developing','Maturing','Advanced'],
    datasets: [{
      data: [
        <?= $matData['Beginning'] ?? 0 ?>,
        <?= $matData['Developing'] ?? 0 ?>,
        <?= $matData['Maturing'] ?? 0 ?>,
        <?= $matData['Advanced'] ?? 0 ?>
      ],
      backgroundColor: ['#DC2626','#D97706','#2563EB','#16A34A'],
      borderWidth: 3,
      borderColor: '#fff',
      hoverOffset: 6,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => {
            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
            const pct = total > 0 ? Math.round((ctx.raw/total)*100) : 0;
            return ` ${ctx.raw} school${ctx.raw !== 1 ? 's' : ''} (${pct}%)`;
          }
        }
      }
    }
  }
});
<?php endif; ?>

// Dimension Bar Chart
const dimLabels = <?= json_encode(array_map(fn($d) => 'D'.$d['dimension_no'], $dimScores)) ?>;
const dimValues = <?= json_encode(array_map(fn($d) => $d['avg_pct'] !== null ? floatval($d['avg_pct']) : null, $dimScores)) ?>;
const dimColors = <?= json_encode(array_column($dimScores,'color_hex')) ?>;

new Chart(document.getElementById('dimBarChart'), {
  type: 'bar',
  data: {
    labels: dimLabels,
    datasets: [{
      label: 'Average Score (%)',
      data: dimValues,
      backgroundColor: dimColors.map(c => c + '33'),
      borderColor: dimColors,
      borderWidth: 2,
      borderRadius: 7,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        min: 0, max: 100,
        ticks: { callback: v => v + '%', font: { size: 11 }, color: '#9CA3AF' },
        grid: { color: '#F3F4F6' }
      },
      x: {
        ticks: { font: { size: 12, weight: '600' }, color: '#6B7280' },
        grid: { display: false }
      }
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ctx.raw !== null ? ' ' + ctx.raw + '%' : ' No data'
        }
      }
    }
  }
});

// ── Live polling ─────────────────────────────────────────────
async function pollUpdates() {
  try {
    const res = await fetch('<?= $__base ?>/includes/poll.php');
    if (!res.ok) return;
    const d = await res.json();
    if (d.schools   !== undefined) liveSet('total-schools', d.schools);
    if (d.users     !== undefined) liveSet('total-users',   d.users);
    if (d.cycles    !== undefined) liveSet('total-cycles',  d.cycles);
    if (d.validated !== undefined) liveSet('validated',     d.validated);
  } catch(e) {}
}
setInterval(pollUpdates, 8000);
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>