<?php
// ============================================================
// admin/dashboard.php — REDESIGNED
// Clean, professional admin overview dashboard
// ============================================================
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
$db = getDB();

// ── Data queries ─────────────────────────────────────────────
$totalSchools  = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$totalCycles   = $db->query("SELECT COUNT(*) FROM sbm_cycles")->fetchColumn();
$submitted     = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status IN('submitted','validated')")->fetchColumn();
$validated     = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='validated'")->fetchColumn();
$inProgress    = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='in_progress'")->fetchColumn();

// Maturity distribution
$maturity = $db->query("SELECT maturity_level, COUNT(*) cnt FROM sbm_cycles WHERE maturity_level IS NOT NULL GROUP BY maturity_level ORDER BY FIELD(maturity_level,'Advanced','Maturing','Developing','Beginning')")->fetchAll();

// Recent cycles
$recentCycles = $db->query("
  SELECT c.*, s.school_name, sy.label sy_label
  FROM sbm_cycles c
  JOIN schools s ON c.school_id=s.school_id
  JOIN school_years sy ON c.sy_id=sy.sy_id
  ORDER BY c.created_at DESC LIMIT 8
")->fetchAll();

// Dimension averages
$dimScores = $db->query("
  SELECT d.dimension_no, d.dimension_name, d.color_hex,
         ROUND(AVG(ds.percentage),1) avg_pct
  FROM sbm_dimensions d
  LEFT JOIN sbm_dimension_scores ds ON d.dimension_id=ds.dimension_id
  GROUP BY d.dimension_id ORDER BY d.dimension_no
")->fetchAll();

// Recent activity
$recentActivity = $db->query("
  SELECT l.*, u.full_name FROM activity_log l
  LEFT JOIN users u ON l.user_id=u.user_id
  ORDER BY l.created_at DESC LIMIT 8
")->fetchAll();

// Submission rate
$submissionRate = $totalSchools > 0 ? round(($submitted / $totalSchools) * 100) : 0;

$pageTitle  = 'Dashboard';
$activePage = 'dashboard.php';
include __DIR__.'/../includes/header.php';
?>

<style>
/* Dashboard-specific refinements */
.dim-bar-item { margin-bottom: 16px; }
.dim-bar-item:last-child { margin-bottom: 0; }
.dim-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}
.dim-label-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--n-800);
  display: flex;
  align-items: center;
  gap: 8px;
}
.dim-dot {
  width: 10px; height: 10px;
  border-radius: 3px;
  flex-shrink: 0;
}
.dim-label-score { font-size: 13px; font-weight: 700; }
.dim-prog { height: 8px; background: var(--n-100); border-radius: 999px; overflow: hidden; }
.dim-fill { height: 100%; border-radius: 999px; transition: width .6s cubic-bezier(.4,0,.2,1); }
.dim-mat  { font-size: 11px; color: var(--n-400); margin-top: 3px; }

/* Activity feed item */
.act-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 9px 0;
  border-bottom: 1px solid var(--n-100);
}
.act-item:last-child { border-bottom: none; }
.act-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--brand-400);
  flex-shrink: 0;
  margin-top: 5px;
}
.act-text  { font-size: 12.5px; color: var(--n-700); line-height: 1.4; }
.act-who   { font-weight: 600; color: var(--n-900); }
.act-action{ color: var(--n-500); }
.act-time  { font-size: 11px; color: var(--n-400); margin-top: 2px; }

/* Welcome banner */
.welcome-banner {
  background: linear-gradient(135deg, var(--brand-800) 0%, var(--brand-700) 60%, #1a7a4a 100%);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  color: #fff;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.welcome-banner::after {
  content: '';
  position: absolute;
  right: -40px; top: -40px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.welcome-banner::before {
  content: '';
  position: absolute;
  right: 60px; bottom: -60px;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
}
.welcome-title {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 4px;
  position: relative;
  z-index: 1;
}
.welcome-sub {
  font-size: 13.5px;
  opacity: .7;
  position: relative;
  z-index: 1;
}
.welcome-actions {
  display: flex;
  gap: 10px;
  margin-top: 18px;
  position: relative;
  z-index: 1;
  flex-wrap: wrap;
}
.welcome-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 140ms;
}
.welcome-btn-primary {
  background: rgba(255,255,255,.15);
  color: #fff;
  border: 1px solid rgba(255,255,255,.25);
}
.welcome-btn-primary:hover { background: rgba(255,255,255,.25); }
.welcome-btn-secondary {
  background: rgba(255,255,255,.08);
  color: rgba(255,255,255,.85);
  border: 1px solid rgba(255,255,255,.15);
}
.welcome-btn-secondary:hover { background: rgba(255,255,255,.15); }

/* Completion ring */
.ring-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
</style>

<!-- Welcome banner -->
<div class="welcome-banner">
  <div class="welcome-title">System Dashboard</div>
  <div class="welcome-sub">
    SBM Online Monitoring — <?= date('l, F j, Y') ?>
    &nbsp;·&nbsp; <?= e($db->query("SELECT label FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn() ?: '—') ?>
  </div>
  <div class="welcome-actions">
    <a href="assessment.php" class="welcome-btn welcome-btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      View Assessments
    </a>
    <a href="reports.php" class="welcome-btn welcome-btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
      </svg>
      Generate Reports
    </a>
  </div>
</div>

<!-- Stat Cards -->
<div class="stats">
  <div class="stat">
    <div class="stat-ic green">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </div>
    <div class="stat-data">
      <div class="stat-val" data-live="total-schools"><?= $totalSchools ?></div>
      <div class="stat-lbl">Total Schools</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic blue">
      <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div class="stat-data">
      <div class="stat-val" data-live="total-cycles"><?= $totalCycles ?></div>
      <div class="stat-lbl">Assessment Cycles</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic amber">
      <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
    <div class="stat-data">
      <div class="stat-val" data-live="submitted"><?= $submitted ?></div>
      <div class="stat-lbl">Submitted / Validated</div>
      <div class="stat-sub"><?= $submissionRate ?>% submission rate</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic green">
      <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="stat-data">
      <div class="stat-val" data-live="validated"><?= $validated ?></div>
      <div class="stat-lbl">Validated</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic blue">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </div>
    <div class="stat-data">
      <div class="stat-val" data-live="in-progress"><?= $inProgress ?></div>
      <div class="stat-lbl">In Progress</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic purple">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="stat-data">
      <div class="stat-val" data-live="total-users"><?= $totalUsers ?></div>
      <div class="stat-lbl">Active Users</div>
    </div>
  </div>
</div>

<!-- Main 2/3 + 1/3 layout -->
<div class="grid2-3" style="gap:18px;margin-bottom:20px;">

  <!-- Dimension performance -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Dimension Performance</span>
      <span style="font-size:12px;color:var(--n-400);">System-wide average</span>
    </div>
    <div class="card-body">
      <?php foreach($dimScores as $d):
        $pct = floatval($d['avg_pct']);
        $mat = sbmMaturityLevel($pct);
      ?>
      <div class="dim-bar-item">
        <div class="dim-label">
          <div class="dim-label-name">
            <span class="dim-dot" style="background:<?= e($d['color_hex']) ?>;"></span>
            D<?= $d['dimension_no'] ?>: <?= e($d['dimension_name']) ?>
          </div>
          <span class="dim-label-score" style="color:<?= e($d['color_hex']) ?>;">
            <?= $pct > 0 ? $pct.'%' : '—' ?>
          </span>
        </div>
        <div class="dim-prog">
          <div class="dim-fill" style="width:<?= min(100,$pct) ?>%;background:<?= e($d['color_hex']) ?>;"></div>
        </div>
        <?php if($pct > 0): ?>
        <div class="dim-mat"><?= $mat['label'] ?> level</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right column -->
  <div style="display:flex;flex-direction:column;gap:18px;">

    <!-- Maturity donut -->
    <div class="card">
      <div class="card-head"><span class="card-title">Maturity Distribution</span></div>
      <div class="card-body" style="padding:16px;">
        <div style="position:relative;max-width:220px;margin:0 auto;">
          <canvas id="maturityChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent activity -->
    <div class="card" style="flex:1;">
      <div class="card-head">
        <span class="card-title">Recent Activity</span>
      </div>
      <div class="card-body" id="live-activity-feed">
        <?php foreach($recentActivity as $log): ?>
        <div class="act-item">
          <div class="act-dot"></div>
          <div>
            <div class="act-text">
              <span class="act-who"><?= e($log['full_name'] ?? 'System') ?></span>
              <span class="act-action"> — <?= e($log['action']) ?></span>
            </div>
            <div class="act-time"><?= timeAgo($log['created_at']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(!$recentActivity): ?>
        <p style="font-size:13px;color:var(--n-400);text-align:center;padding:20px 0;">No activity yet.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- Recent Cycles Table -->
<div class="card">
  <div class="card-head">
    <span class="card-title">Recent Assessment Cycles</span>
    <div class="flex-c" style="gap:8px;">
      <div class="search">
        <span class="si"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input type="text" placeholder="Search schools…" oninput="filterTable(this.value,'tblRecent')">
      </div>
      <a href="assessment.php" class="btn btn-secondary btn-sm">View all</a>
    </div>
  </div>
  <div class="tbl-wrap">
    <table id="tblRecent">
      <thead>
        <tr>
          <th>School</th>
          <th>School Year</th>
          <th>Status</th>
          <th>Overall Score</th>
          <th>Maturity</th>
          <th>Last Updated</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($recentCycles as $c): ?>
      <tr>
        <td><strong style="font-size:13px;"><?= e($c['school_name']) ?></strong></td>
        <td style="font-size:13px;color:var(--n-500);"><?= e($c['sy_label']) ?></td>
        <td><span class="pill pill-<?= e($c['status']) ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
        <td>
          <?php if($c['overall_score']): ?>
          <span style="font-family:var(--font-display);font-size:15px;font-weight:800;color:var(--brand-700);"><?= $c['overall_score'] ?>%</span>
          <?php else: ?><span style="color:var(--n-400);">—</span><?php endif; ?>
        </td>
        <td>
          <?php if($c['maturity_level']): ?>
          <span class="pill pill-<?= e($c['maturity_level']) ?>"><?= e($c['maturity_level']) ?></span>
          <?php else: ?><span style="color:var(--n-400);">—</span><?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--n-400);"><?= timeAgo($c['created_at']) ?></td>
        <td><a href="assessment.php?id=<?= $c['cycle_id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$recentCycles): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--n-400);padding:32px;">No assessments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Maturity donut chart
const matData = <?= json_encode(array_column($maturity,'cnt','maturity_level')) ?>;
const labels  = ['Beginning','Developing','Maturing','Advanced'];
const colors  = ['#DC2626','#D97706','#2563EB','#16A34A'];
const values  = labels.map(l => matData[l] || 0);
const total   = values.reduce((a,b) => a+b, 0);

new Chart(document.getElementById('maturityChart'), {
  type: 'doughnut',
  data: {
    labels,
    datasets: [{
      data: values,
      backgroundColor: colors,
      borderWidth: 3,
      borderColor: '#fff',
      hoverBorderColor: '#fff',
      hoverOffset: 4,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    cutout: '68%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          font: { family: "'Inter',sans-serif", size: 12 },
          padding: 12,
          usePointStyle: true,
          pointStyleWidth: 8,
        }
      },
      tooltip: {
        callbacks: {
          label: ctx => {
            const pct = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
            return ` ${ctx.raw} schools (${pct}%)`;
          }
        }
      }
    }
  }
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>