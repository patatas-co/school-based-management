<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSystemAdmin();
$db = getDB();

$counts = $db->query("
  SELECT role, COUNT(*) AS cnt
  FROM users
  GROUP BY role
")->fetchAll(PDO::FETCH_KEY_PAIR);

$activeUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$inactiveUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE status='inactive'")->fetchColumn();
$currentSY = $db->query("SELECT label FROM school_years WHERE is_current=1 LIMIT 1")->fetchColumn();

$pageTitle = 'System Admin Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  .sa-hero {
    border-radius: var(--radius-lg);
    padding: 28px 32px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
  }

  .db-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 150ms ease;
    white-space: nowrap;
  }
  .db-hero-btn-secondary {
    background: rgba(255,255,255,.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,.25);
  }
  .db-hero-btn-secondary:hover {
    background: rgba(255,255,255,.22);
  }
  .db-hero-btn svg {
    width: 14px; height: 14px;
    stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
  }

  .sa-hero-bg {
    position: absolute;
    inset: 0;
    border-radius: var(--radius-lg);
    background: #081a08;
    overflow: hidden;
    z-index: 0;
  }



  .sa-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
  }

  .sa-stat,
  .sa-card {
    background: #fff;
    border: 1px solid var(--n-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xs);
  }

  .sa-stat {
    padding: 18px 20px;
  }

  .sa-stat-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--n-500);
    margin-bottom: 10px;
  }

  .sa-stat-value {
    font-family: var(--font-display);
    font-size: 32px;
    font-weight: 800;
    color: var(--n-900);
    line-height: 1;
  }
</style>

<div class="sa-hero">
  <div class="sa-hero-bg"></div>
  <div style="position:relative; z-index:1;">
    <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(74,222,128,.8);margin-bottom:8px;">Administration</div>
    <div style="font-family:var(--font-display);font-size:30px;font-weight:800;line-height:1.1;">System Admin Dashboard</div>
    <div style="margin-top:8px;font-size:13px;color:rgba(255,255,255,.72);">
      <?= date('l, F j, Y') ?><?php if ($currentSY): ?> · SY <?= e($currentSY) ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;position:relative; z-index:1;">
  </div>
</div>

<div class="sa-grid">
  <div class="sa-stat">
    <div class="sa-stat-label">Total Accounts</div>
    <div class="sa-stat-value"><?= number_format(array_sum($counts)) ?></div>
  </div>
  <div class="sa-stat">
    <div class="sa-stat-label">Active Accounts</div>
    <div class="sa-stat-value"><?= number_format($activeUsers) ?></div>
  </div>
  <div class="sa-stat">
    <div class="sa-stat-label">Inactive Accounts</div>
    <div class="sa-stat-value"><?= number_format($inactiveUsers) ?></div>
  </div>
  <div class="sa-stat">
    <div class="sa-stat-label">System Admins</div>
    <div class="sa-stat-value"><?= number_format((int) ($counts['system_admin'] ?? 0)) ?></div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
