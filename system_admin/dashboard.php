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
$recentUsers = $db->query("
  SELECT full_name, username, role, status, created_at
  FROM users
  WHERE DATE(created_at) = CURDATE()
  ORDER BY created_at DESC
  LIMIT 8
")->fetchAll();

$roleLabels = [
  'system_admin' => 'System Admin',
  'school_head' => 'School Head',
  'sbm_coordinator' => 'SBM Coordinator',
  'teacher' => 'Teacher',
  'external_stakeholder' => 'Stakeholder',
];

$roleColors = [
  'system_admin' => '#7C3AED',
  'school_head' => '#166534',
  'sbm_coordinator' => '#2563EB',
  'teacher' => '#0D9488',
  'external_stakeholder' => '#D97706',
];

$pageTitle = 'System Admin Dashboard';
$activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>

<style>
  .sa-hero {
    background: linear-gradient(135deg, #1f144d 0%, #312e81 48%, #0f172a 100%);
    border-radius: var(--radius-lg);
    padding: 28px 32px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 22px;
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

  .sa-layout {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 18px;
  }

  .sa-card-head {
    padding: 16px 20px;
    border-bottom: 1px solid var(--n-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
  }

  .sa-card-body {
    padding: 16px 20px 20px;
  }

  .sa-role-list {
    display: grid;
    gap: 10px;
  }

  .sa-role-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--n-50);
  }

  .sa-user-list {
    display: grid;
    gap: 10px;
  }

  .sa-user-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--n-100);
  }

  .sa-user-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
  }

  @media (max-width: 900px) {
    .sa-layout {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="sa-hero">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(196,181,253,.9);margin-bottom:8px;">Administration</div>
    <div style="font-family:var(--font-display);font-size:30px;font-weight:800;line-height:1.1;">System Admin Dashboard</div>
    <div style="margin-top:8px;font-size:13px;color:rgba(255,255,255,.72);">
      <?= date('l, F j, Y') ?><?php if ($currentSY): ?> · SY <?= e($currentSY) ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="users.php?action=create" class="btn btn-primary">Add Account</a>
    <a href="<?= baseUrl() ?>/school_head/settings.php" class="btn btn-secondary">School Years</a>
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

<div class="sa-layout">
  <div class="sa-card">
    <div class="sa-card-head">
      <strong>Accounts by Role</strong>
      <a href="users.php" class="btn btn-ghost btn-sm">Manage all</a>
    </div>
    <div class="sa-card-body">
      <div class="sa-role-list">
        <?php foreach ($roleLabels as $roleKey => $label): ?>
          <div class="sa-role-row">
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="width:10px;height:10px;border-radius:999px;background:<?= $roleColors[$roleKey] ?? '#64748B' ?>;"></span>
              <span style="font-weight:600;color:var(--n-800);"><?= e($label) ?></span>
            </div>
            <span style="font-family:var(--font-display);font-size:22px;font-weight:800;color:<?= $roleColors[$roleKey] ?? '#111827' ?>;"><?= number_format((int) ($counts[$roleKey] ?? 0)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="sa-card">
    <div class="sa-card-head">
      <strong>Recent Accounts</strong>
      <a href="users.php" class="btn btn-ghost btn-sm">Open accounts</a>
    </div>
    <div class="sa-card-body">
      <div class="sa-user-list">
        <?php if (empty($recentUsers)): ?>
          <div style="padding:40px 20px;text-align:center;color:var(--n-500);font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;margin-bottom:12px;opacity:.4;">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p>No new accounts created today.</p>
          </div>
        <?php else: ?>
          <?php foreach ($recentUsers as $user): ?>
            <div class="sa-user-row">
              <div>
                <div style="font-weight:700;color:var(--n-900);"><?= e($user['full_name']) ?></div>
                <div style="font-size:12px;color:var(--n-500);">@<?= e($user['username']) ?> · <?= e($roleLabels[$user['role']] ?? ucwords(str_replace('_', ' ', $user['role']))) ?></div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:12px;font-weight:700;color:<?= $user['status'] === 'active' ? '#16A34A' : '#6B7280' ?>;"><?= ucfirst(e($user['status'])) ?></div>
                <div style="font-size:11px;color:var(--n-400);"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
