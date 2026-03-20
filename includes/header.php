<?php
// ============================================================
// includes/header.php — SBM System  (REDESIGNED)
// Clean accordion sidebar + role-based navigation
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
$__me   = me();
$__role = $__me['role'];
$__base = baseUrl();

// ── Role-based navigation (accordion groups) ─────────────────
// Structure: [ 'group_label', 'icon', [ ['label', 'href', 'icon'], ... ], 'requires_exact_role' ]
// Set group to null for flat items (no accordion)

$__navGroups = [];

if ($__role === 'admin') {
    $__navGroups = [
        ['Overview', 'grid', [
            ['Dashboard',        'admin/dashboard.php',     'grid'],
            ['Analytics & ML',   'admin/analytics.php',     'bar-chart-2'],
        ]],
        ['Management', 'users', [
            ['User Accounts',    'admin/users.php',         'users'],
            ['Schools',          'admin/schools.php',       'home'],
            ['School Years',     'admin/settings.php',      'calendar'],
        ]],
        ['Evaluation', 'check-circle', [
            ['SBM Assessments',  'admin/assessment.php',    'check-circle'],
            ['Workflow & SIP',   'admin/workflow.php',      'trending-up'],
            ['Reports',          'admin/reports.php',       'file-text'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',    'admin/announcements.php', 'bell'],
        ]],
        ['System', 'settings', [
            ['Settings',         'admin/settings.php',      'settings'],
        ]],
    ];
}

elseif ($__role === 'school_head') {
    $__navGroups = [
        ['Overview', 'grid', [
            ['Dashboard',        'school_head/dashboard.php',         'grid'],
            ['SBM Dimensions',   'school_head/dimensions.php',        'layers'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',  'school_head/self_assessment.php',   'check-circle'],
            ['Evidence & MOV',   'school_head/evidence.php',          'paperclip'],
        ]],
        ['Planning', 'trending-up', [
            ['Improvement Plan', 'school_head/improvement.php',       'trending-up'],
            ['Reports',          'school_head/reports.php',           'file-text'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',    'school_head/announcements.php',     'bell'],
        ]],
    ];
}

elseif ($__role === 'teacher') {
    $__navGroups = [
        ['Overview', 'grid', [
            ['Dashboard',        'teacher/dashboard.php',            'grid'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Self-Assessment',  'teacher/self_assessment.php',      'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',    'teacher/announcements.php',        'bell'],
        ]],
    ];
}

elseif ($__role === 'sdo') {
    $__navGroups = [
        ['Overview', 'grid', [
            ['Dashboard',        'sdo/dashboard.php',                'grid'],
            ['School Monitor',   'sdo/monitoring.php',              'eye'],
        ]],
        ['Evaluation', 'check-circle', [
            ['Assessments',      'sdo/assessments.php',             'check-circle'],
            ['Workflow & SIP',   'sdo/workflow.php',                'trending-up'],
        ]],
        ['Support', 'briefcase', [
            ['Technical Assist', 'sdo/technical_assistance.php',    'briefcase'],
            ['TA Requests',      'sdo/ta_requests.php',             'send'],
        ]],
        ['Reports', 'file-text', [
            ['Division Reports', 'sdo/reports.php',                 'file-text'],
            ['Schools',          'sdo/schools.php',                 'home'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',    'sdo/announcements.php',           'bell'],
        ]],
    ];
}

elseif ($__role === 'ro') {
    $__navGroups = [
        ['Overview', 'grid', [
            ['Dashboard',        'ro/dashboard.php',                 'grid'],
        ]],
        ['Reports', 'file-text', [
            ['Regional Reports', 'ro/reports.php',                   'file-text'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',    'ro/announcements.php',             'bell'],
        ]],
    ];
}

elseif ($__role === 'external_stakeholder') {
    $__navGroups = [
        ['Overview', 'grid', [
            ['Dashboard',        'stakeholder/dashboard.php',        'grid'],
        ]],
        ['Participation', 'users', [
            ['Self-Assessment',  'stakeholder/self_assessment.php',  'check-circle'],
        ]],
        ['Communication', 'bell', [
            ['Announcements',    'stakeholder/announcement.php',     'bell'],
        ]],
    ];
}

// ── Active page detection ─────────────────────────────────────
$__currentFile = basename($_SERVER['PHP_SELF']);
$__currentPath = ltrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']), '/');

// ── Initials for avatar ───────────────────────────────────────
$__nameParts = array_filter(explode(' ', trim($__me['name'])));
$__initials  = strtoupper(
    substr($__nameParts[0] ?? 'U', 0, 1) .
    (isset($__nameParts[1]) ? substr($__nameParts[1], 0, 1) : '')
);

// ── Role display label ────────────────────────────────────────
$__roleLabel = [
    'admin'                => 'System Admin',
    'school_head'          => 'School Head',
    'teacher'              => 'Teacher',
    'sdo'                  => 'SDO Officer',
    'ro'                   => 'Regional Office',
    'external_stakeholder' => 'Ext. Stakeholder',
][$__role] ?? ucfirst($__role);

// ── Role accent color ─────────────────────────────────────────
$__roleColor = [
    'admin'                => '#7C3AED',
    'school_head'          => '#2563EB',
    'teacher'              => '#0D9488',
    'sdo'                  => '#D97706',
    'ro'                   => '#DC2626',
    'external_stakeholder' => '#16A34A',
][$__role] ?? '#16A34A';

$__sbCollapsed = ($_COOKIE['sb_collapsed'] ?? 'false') === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="<?= $__base ?>/favicon/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $__base ?>/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $__base ?>/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $__base ?>/favicon/favicon-16x16.png">
<link rel="manifest" href="<?= $__base ?>/favicon/site.webmanifest">
<link rel="shortcut icon" href="<?= $__base ?>/favicon/favicon.ico">
<title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e(SITE_NAME) ?></title>
<meta name="csrf-token" content="<?= csrfToken() ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<style>
/* ============================================================
   SBM DESIGN SYSTEM — v2.0 Redesign
   Font: Inter (body) + Manrope (headings/numbers)
============================================================ */
:root {
  /* Brand palette */
  --brand-50:  #F0FDF4;
  --brand-100: #DCFCE7;
  --brand-200: #BBF7D0;
  --brand-400: #4ADE80;
  --brand-500: #22C55E;
  --brand-600: #16A34A;
  --brand-700: #15803D;
  --brand-800: #166534;
  --brand-900: #14532D;

  /* Neutrals */
  --n-50:  #F9FAFB;
  --n-100: #F3F4F6;
  --n-150: #EAECF0;
  --n-200: #E5E7EB;
  --n-300: #D1D5DB;
  --n-400: #9CA3AF;
  --n-500: #6B7280;
  --n-600: #4B5563;
  --n-700: #374151;
  --n-800: #1F2937;
  --n-900: #111827;

  /* Semantic */
  --red:    #DC2626;  --red-bg:    #FEE2E2;
  --amber:  #D97706;  --amber-bg:  #FEF3C7;
  --blue:   #2563EB;  --blue-bg:   #DBEAFE;
  --purple: #7C3AED;  --purple-bg: #EDE9FE;
  --teal:   #0D9488;  --teal-bg:   #CCFBF1;

  /* Layout */
  --sidebar-w:    260px;
  --sidebar-mini: 64px;
  --topbar-h:     60px;
  --radius:       10px;
  --radius-sm:    6px;
  --radius-lg:    14px;

  /* Typography */
  --font-body:    'Inter', -apple-system, sans-serif;
  --font-display: 'Manrope', -apple-system, sans-serif;

  /* Shadows */
  --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / .05);
  --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / .10), 0 1px 2px -1px rgb(0 0 0 / .06);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / .08), 0 2px 4px -2px rgb(0 0 0 / .05);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / .08), 0 4px 6px -4px rgb(0 0 0 / .05);

  /* Sidebar-specific */
  --sb-bg:         #0F172A;
  --sb-surface:    #1E293B;
  --sb-border:     rgba(255,255,255,.07);
  --sb-text:       rgba(255,255,255,.55);
  --sb-text-hover: rgba(255,255,255,.9);
  --sb-active-bg:  rgba(255,255,255,.1);

  /* Transition */
  --ease: cubic-bezier(.4,0,.2,1);
  --dur:  150ms;

  /* ── Shorthand aliases (used by page files) ── */
  --n50:  var(--n-50);
  --n100: var(--n-100);
  --n200: var(--n-200);
  --n300: var(--n-300);
  --n400: var(--n-400);
  --n500: var(--n-500);
  --n600: var(--n-600);
  --n700: var(--n-700);
  --n800: var(--n-800);
  --n900: var(--n-900);

  /* Brand / green shorthands */
  --g50:  var(--brand-50);
  --g100: var(--brand-100);
  --g200: var(--brand-200);
  --g300: var(--brand-400);
  --g400: var(--brand-400);
  --g500: var(--brand-500);
  --g600: var(--brand-600);
  --g700: var(--brand-700);
  --g800: var(--brand-800);

  /* Semantic color shorthands */
  --gold:   var(--amber);
  --goldb:  var(--amber-bg);
  --blueb:  var(--blue-bg);
  --purpb:  var(--purple-bg);
  --redb:   var(--red-bg);

  /* Misc */
  --white:     #ffffff;
  --shadow:    var(--shadow-xs);
  --shadow-md: var(--shadow-md);
  --trans:     150ms cubic-bezier(.4,0,.2,1);
}

/* ── Reset ───────────────────────────────────────────────── */
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { font-size:15px; scroll-behavior:smooth; -webkit-text-size-adjust:100%; }
body {
  font-family: var(--font-body);
  background: var(--n-50);
  color: var(--n-800);
  display: flex;
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* ============================================================
   SIDEBAR
============================================================ */
.sb {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--sb-bg);
  display: flex;
  flex-direction: column;
  z-index: 100;
  transition: width 220ms var(--ease);
  overflow: hidden;
}
.sb.collapsed { width: var(--sidebar-mini); }

/* Brand strip at top */
.sb-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 16px 16px;
  border-bottom: 1px solid var(--sb-border);
  flex-shrink: 0;
}
.sb-logo {
  width: 36px; height: 36px;
  border-radius: 9px;
  background: var(--brand-700);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
}
.sb-logo img {
  width: 100%; height: 100%;
  object-fit: cover; border-radius: 9px;
}
.sb-logo-fallback {
  width: 18px; height: 18px;
  stroke: #fff; stroke-width: 1.8;
  fill: none; stroke-linecap: round; stroke-linejoin: round;
}
.sb-brand-text {
  flex: 1; min-width: 0;
  overflow: hidden;
}
.sb-brand-name {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}
.sb-brand-sub {
  font-size: 10.5px;
  color: var(--sb-text);
  white-space: nowrap;
  margin-top: 1px;
}
.sb.collapsed .sb-brand-text { display: none; }

/* Scroll nav area */
.sb-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 12px 8px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.1) transparent;
}
.sb-nav::-webkit-scrollbar { width: 3px; }
.sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

/* ── Section group ── */
.sb-section {
  margin-bottom: 4px;
}
.sb-section-label {
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: .09em;
  text-transform: uppercase;
  color: rgba(255,255,255,.28);
  padding: 12px 10px 4px;
  white-space: nowrap;
  overflow: hidden;
}
.sb.collapsed .sb-section-label { visibility: hidden; }

/* ── Nav item ── */
.sb-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 10px;
  border-radius: 7px;
  color: var(--sb-text);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: color var(--dur) var(--ease), background var(--dur) var(--ease);
  white-space: nowrap;
  overflow: hidden;
  position: relative;
  margin-bottom: 1px;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
  user-select: none;
}
.sb-item:hover {
  color: var(--sb-text-hover);
  background: var(--sb-active-bg);
}
.sb-item.active {
  color: #fff;
  background: var(--brand-700);
}
.sb-item.active .sb-icon svg { stroke: #fff; }
.sb-item .sb-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }

/* icon */
.sb-icon {
  width: 18px; height: 18px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.sb-icon svg {
  width: 16px; height: 16px;
  stroke: var(--sb-text);
  stroke-width: 1.8;
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
  transition: stroke var(--dur) var(--ease);
}
.sb-item:hover .sb-icon svg { stroke: var(--sb-text-hover); }

/* badge (notification count) */
.sb-badge {
  font-size: 10px;
  font-weight: 700;
  background: var(--red);
  color: #fff;
  border-radius: 999px;
  padding: 1px 6px;
  line-height: 1.6;
  flex-shrink: 0;
}

/* ── Accordion chevron ── */
.sb-chevron {
  width: 14px; height: 14px;
  stroke: var(--sb-text);
  stroke-width: 2;
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
  transition: transform 220ms var(--ease);
  flex-shrink: 0;
}
.sb-group.open .sb-chevron { transform: rotate(180deg); }

/* ── Accordion children ── */
.sb-children {
  max-height: 0;
  overflow: hidden;
  transition: max-height 240ms var(--ease), opacity 200ms var(--ease);
  opacity: 0;
  padding-left: 6px;
}
.sb-group.open .sb-children {
  max-height: 400px;
  opacity: 1;
}
.sb-child {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px 6px 14px;
  border-radius: 6px;
  color: var(--sb-text);
  font-size: 12.5px;
  font-weight: 400;
  text-decoration: none;
  transition: color var(--dur), background var(--dur);
  position: relative;
  margin-bottom: 1px;
}
.sb-child::before {
  content: '';
  position: absolute;
  left: 4px; top: 50%;
  transform: translateY(-50%);
  width: 1px; height: 14px;
  background: rgba(255,255,255,.18);
  border-radius: 1px;
}
.sb-child:hover { color: var(--sb-text-hover); background: rgba(255,255,255,.06); }
.sb-child.active {
  color: var(--brand-400);
  background: rgba(22,163,74,.12);
}
.sb-child.active::before { background: var(--brand-500); }

/* Collapsed tooltips */
/* ── Collapsed sidebar overrides ── */
.sb.collapsed {
  overflow: hidden;
}

.sb.collapsed .sb-nav {
  padding: 12px 0;
  overflow-x: hidden;
}

/* ── Popover for collapsed mode ── */
.sb.collapsed .sb-group {
  position: relative;
  margin-bottom: 2px;
}
.sb.collapsed .sb-children {
  position: fixed;
  left: var(--sidebar-mini);
  top: var(--popover-top, auto);
  width: 200px;
  background: var(--sb-surface);
  border: 1px solid var(--sb-border);
  border-radius: 0 10px 10px 0;
  display: none !important; /* Hide by default */
  max-height: none !important;
  opacity: 1 !important;
  padding: 8px;
  box-shadow: 10px 0 25px rgba(0,0,0,.3);
  z-index: 1000;
}
.sb.collapsed .sb-group:hover .sb-children {
  display: block !important;
}
.sb.collapsed .sb-child {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 8px 12px 8px 16px !important;
  border-bottom: none;
  position: relative;
}
.sb.collapsed .sb-child::before {
  left: 6px;
  height: 14px;
}
.sb.collapsed .sb-child span:not(.sb-icon) {
  display: inline !important;
}

.sb.collapsed .sb-section-label,
.sb.collapsed .sb-chevron,
.sb.collapsed .sb-label,
.sb.collapsed .sb-badge {
  display: none !important;
}

.sb.collapsed .sb-item {
  padding: 12px 0;
  justify-content: center;
  border-radius: 0;
  cursor: pointer;
}

.sb.collapsed .sb-item span:not(.sb-icon) {
  display: none !important;
}

.sb.collapsed .sb-icon {
  width: 24px;
  height: 24px;
  margin: 0 auto;
  display: flex !important;
  align-items: center;
  justify-content: center;
}

.sb.collapsed .sb-icon svg {
  width: 20px;
  height: 20px;
  stroke: var(--sb-text);
}


/* ── Footer / user tile ── */
.sb-footer {
  border-top: 1px solid var(--sb-border);
  padding: 10px 8px 12px;
  flex-shrink: 0;
  position: relative;
}
.sb-user-tile {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background var(--dur);
}
.sb-user-tile:hover { background: rgba(255,255,255,.07); }
.sb-avatar {
  width: 34px; height: 34px;
  border-radius: 8px;
  font-family: var(--font-display);
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #fff;
}
.sb-user-info { flex: 1; min-width: 0; }
.sb-user-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}
.sb-user-role {
  font-size: 10.5px;
  color: var(--sb-text);
  margin-top: 1px;
}
.sb-user-more {
  width: 14px; height: 14px;
  stroke: var(--sb-text);
  stroke-width: 2;
  fill: none; stroke-linecap: round; stroke-linejoin: round;
  flex-shrink: 0;
  transition: transform 200ms var(--ease);
}
.sb.collapsed .sb-user-info,
.sb.collapsed .sb-user-more { display: none; }
.sb.collapsed .sb-user-tile { justify-content: center; padding: 9px; }

/* User popup menu */
.sb-popup {
  display: none;
  position: absolute;
  bottom: 80px; left: 10px; right: 10px;
  background: #1E293B;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 10px;
  padding: 8px;
  box-shadow: 0 -8px 24px rgba(0,0,0,.35);
  z-index: 200;
}
.sb-popup.open { display: block; }
.sb.collapsed .sb-popup { 
  position: fixed;
  left: 70px; 
  right: auto; 
  width: 190px; 
  bottom: 14px; 
  z-index: 2000;
}
.sb-popup-user {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 6px 10px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  margin-bottom: 6px;
}
.sb-popup-name { font-size: 13px; font-weight: 600; color: #fff; }
.sb-popup-role { font-size: 11px; color: rgba(255,255,255,.4); margin-top: 1px; }
.sb-popup-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 10px;
  border-radius: 7px;
  color: rgba(255,255,255,.7);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: background var(--dur);
  cursor: pointer;
}
.sb-popup-item:hover { background: rgba(255,255,255,.07); color: #fff; }
.sb-popup-item.danger { color: #FCA5A5; }
.sb-popup-item.danger:hover { background: rgba(220,38,38,.15); }
.sb-popup-item svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

/* ============================================================
   MAIN LAYOUT
============================================================ */
.main-wrap {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-width: 0;
  transition: margin-left 220ms var(--ease);
}
.main-wrap.expanded { margin-left: var(--sidebar-mini); }

/* DepEd accent stripe */
.deped-stripe {
  height: 3px;
  background: linear-gradient(90deg, #166534 0%, #22C55E 40%, #FFD700 70%, #CE1126 100%);
  position: sticky;
  top: 0;
  z-index: 60;
  flex-shrink: 0;
}

/* ── Topbar ── */
.topbar {
  height: var(--topbar-h);
  background: #fff;
  border-bottom: 1px solid var(--n-200);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 3px;
  z-index: 50;
  gap: 16px;
}
.topbar-left { display: flex; align-items: center; gap: 10px; }
.topbar-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
  color: var(--n-600);
}
.topbar-breadcrumb span { color: var(--n-300); }
.topbar-breadcrumb .topbar-title {
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 700;
  color: var(--n-900);
}
.topbar-right { display: flex; align-items: center; gap: 8px; }
.menu-btn {
  width: 36px; height: 36px;
  border-radius: 7px;
  border: 1px solid var(--n-200);
  background: transparent;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: var(--n-700);
  transition: background var(--dur), border-color var(--dur);
  flex-shrink: 0;
}
.menu-btn:hover { background: var(--n-100); border-color: var(--n-300); }
.menu-btn svg { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

/* Role chip */
.role-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  background: var(--n-100);
  color: var(--n-600);
  border: 1px solid var(--n-200);
}
.role-chip .dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Page area ── */
.page {
  flex: 1;
  padding: 24px;
}

/* ============================================================
   DESIGN COMPONENTS
============================================================ */

/* ── Page header ── */
.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.page-head-text h2 {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 800;
  color: var(--n-900);
  letter-spacing: -.4px;
  line-height: 1.2;
}
.page-head-text p {
  font-size: 13.5px;
  color: var(--n-500);
  margin-top: 4px;
}
.page-head-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  flex-wrap: wrap;
}

/* ── Cards ── */
.card {
  background: #fff;
  border-radius: var(--radius-lg);
  border: 1px solid var(--n-200);
  box-shadow: var(--shadow-xs);
  overflow: hidden;
}
.card-head {
  padding: 14px 20px 12px;
  border-bottom: 1px solid var(--n-100);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.card-title {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 700;
  color: var(--n-900);
}
.card-body { padding: 20px; }
.card-body-flush { padding: 0; }

/* ── Stat cards ── */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}
.stat {
  background: #fff;
  border: 1px solid var(--n-200);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--shadow-xs);
  transition: box-shadow var(--dur);
}
.stat:hover { box-shadow: var(--shadow-sm); }
.stat-ic {
  width: 44px; height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-ic svg { width: 20px; height: 20px; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.stat-ic.green { background: var(--brand-100); }
.stat-ic.green svg { stroke: var(--brand-700); }
.stat-ic.blue { background: var(--blue-bg); }
.stat-ic.blue svg { stroke: var(--blue); }
.stat-ic.amber { background: var(--amber-bg); }
.stat-ic.amber svg { stroke: var(--amber); }
.stat-ic.red { background: var(--red-bg); }
.stat-ic.red svg { stroke: var(--red); }
.stat-ic.purple { background: var(--purple-bg); }
.stat-ic.purple svg { stroke: var(--purple); }
.stat-ic.teal { background: var(--teal-bg); }
.stat-ic.teal svg { stroke: var(--teal); }
.stat-ic.dark { background: var(--n-800); }
.stat-ic.dark svg { stroke: #fff; }
.stat-val {
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 800;
  color: var(--n-900);
  line-height: 1;
  letter-spacing: -.5px;
}
.stat-lbl {
  font-size: 12px;
  color: var(--n-500);
  margin-top: 3px;
  font-weight: 500;
}
.stat-sub {
  font-size: 11.5px;
  color: var(--brand-600);
  margin-top: 4px;
  font-weight: 600;
}

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 7px;
  border: none;
  cursor: pointer;
  font-family: var(--font-body);
  font-size: 13.5px;
  font-weight: 600;
  transition: all var(--dur) var(--ease);
  text-decoration: none;
  white-space: nowrap;
  line-height: 1.4;
}
.btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.btn .sb-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}
.btn .sb-icon svg {
  width: 14px;
  height: 14px;
}
.btn-sm .sb-icon {
  width: 13px;
  height: 13px;
}
.btn-sm .sb-icon svg {
  width: 13px;
  height: 13px;
}
.btn-primary { background: var(--brand-700); color: #fff; box-shadow: 0 1px 2px rgba(21,128,61,.25); }
.btn-primary:hover { background: var(--brand-800); }
.btn-secondary { background: var(--n-100); color: var(--n-700); border: 1px solid var(--n-200); }
.btn-secondary:hover { background: var(--n-200); }
.btn-danger { background: var(--red-bg); color: var(--red); border: 1px solid #FECACA; }
.btn-danger:hover { background: var(--red); color: #fff; border-color: var(--red); }
.btn-success { background: var(--brand-100); color: var(--brand-700); border: 1px solid var(--brand-200); }
.btn-success:hover { background: var(--brand-700); color: #fff; border-color: var(--brand-700); }
.btn-blue { background: var(--blue-bg); color: var(--blue); border: 1px solid #BFDBFE; }
.btn-blue:hover { background: var(--blue); color: #fff; }
.btn-sm { padding: 5px 11px; font-size: 12px; border-radius: 6px; gap: 5px; }
.btn-sm svg { width: 13px; height: 13px; }
.btn-ghost { background: transparent; color: var(--n-600); border: 1px solid transparent; }
.btn-ghost:hover { background: var(--n-100); border-color: var(--n-200); }
.btn-icon { padding: 7px; }

/* ── Form fields ── */
.fg { margin-bottom: 14px; }
.fg label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--n-700);
  margin-bottom: 5px;
}
.fc {
  width: 100%;
  padding: 8.5px 12px;
  border-radius: 7px;
  border: 1.5px solid var(--n-200);
  background: #fff;
  font-family: var(--font-body);
  font-size: 13.5px;
  color: var(--n-900);
  transition: border-color var(--dur), box-shadow var(--dur);
  outline: none;
}
.fc:focus { border-color: var(--brand-600); box-shadow: 0 0 0 3px rgba(22,163,74,.12); }
.fc::placeholder { color: var(--n-400); }
select.fc { cursor: pointer; }
textarea.fc { resize: vertical; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

/* ── Pills / badges ── */
.pill {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
  border: 1px solid transparent;
}
.pill-active     { background:var(--brand-100); color:var(--brand-700); border-color:var(--brand-200); }
.pill-inactive   { background:var(--n-100); color:var(--n-500); border-color:var(--n-200); }
.pill-draft      { background:var(--n-100); color:var(--n-500); border-color:var(--n-200); }
.pill-in_progress{ background:var(--blue-bg); color:var(--blue); border-color:#BFDBFE; }
.pill-submitted  { background:var(--amber-bg); color:var(--amber); border-color:#FDE68A; }
.pill-validated  { background:var(--brand-100); color:var(--brand-700); border-color:var(--brand-200); }
.pill-returned   { background:var(--red-bg); color:var(--red); border-color:#FECACA; }
.pill-admin      { background:var(--purple-bg); color:var(--purple); border-color:#DDD6FE; }
.pill-school_head{ background:var(--blue-bg); color:var(--blue); border-color:#BFDBFE; }
.pill-teacher    { background:var(--teal-bg); color:var(--teal); border-color:#99F6E4; }
.pill-sdo        { background:var(--amber-bg); color:var(--amber); border-color:#FDE68A; }
.pill-ro         { background:var(--red-bg); color:var(--red); border-color:#FECACA; }
.pill-Beginning  { background:var(--red-bg); color:var(--red); border-color:#FECACA; }
.pill-Developing { background:var(--amber-bg); color:var(--amber); border-color:#FDE68A; }
.pill-Maturing   { background:var(--blue-bg); color:var(--blue); border-color:#BFDBFE; }
.pill-Advanced   { background:var(--brand-100); color:var(--brand-700); border-color:var(--brand-200); }
.pill-general    { background:var(--brand-100); color:var(--brand-700); border-color:var(--brand-200); }
.pill-policy     { background:var(--purple-bg); color:var(--purple); border-color:#DDD6FE; }
.pill-deadline   { background:var(--red-bg); color:var(--red); border-color:#FECACA; }
.pill-advisory   { background:var(--amber-bg); color:var(--amber); border-color:#FDE68A; }
.pill-emergency  { background:var(--red-bg); color:var(--red); border-color:#FECACA; }

/* ── Table ── */
.tbl-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
thead th {
  background: var(--n-50);
  padding: 10px 16px;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  color: var(--n-600);
  text-transform: uppercase;
  letter-spacing: .06em;
  border-bottom: 1px solid var(--n-200);
  white-space: nowrap;
}
tbody td { padding: 11px 16px; border-bottom: 1px solid var(--n-100); color: var(--n-700); vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--brand-50); }

/* ── Progress bar ── */
.prog { height: 6px; background: var(--n-100); border-radius: 999px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 999px; transition: width .5s ease; }

/* ── Alert ── */
.alert {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  padding: 11px 14px;
  border-radius: 8px;
  margin-bottom: 14px;
  font-size: 13.5px;
}
.alert svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.alert-success { background: var(--brand-100); color: var(--brand-700); border: 1px solid var(--brand-200); }
.alert-danger   { background: var(--red-bg); color: var(--red); border: 1px solid #FECACA; }
.alert-warning  { background: var(--amber-bg); color: var(--amber); border: 1px solid #FDE68A; }
.alert-info     { background: var(--blue-bg); color: var(--blue); border: 1px solid #BFDBFE; }

/* ── Modal ── */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity var(--dur);
}
.overlay.open { opacity: 1; pointer-events: all; }
.modal {
  background: #fff;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-width: 520px;
  max-height: 92vh;
  overflow-y: auto;
  transform: scale(.97) translateY(8px);
  transition: transform 200ms var(--ease);
}
.overlay.open .modal { transform: scale(1) translateY(0); }
.modal-head {
  padding: 18px 22px 14px;
  border-bottom: 1px solid var(--n-100);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-title { font-family: var(--font-display); font-size: 15.5px; font-weight: 700; color: var(--n-900); }
.modal-close {
  width: 30px; height: 30px;
  border-radius: 6px;
  border: none;
  background: var(--n-100);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: var(--n-500);
  transition: all var(--dur);
}
.modal-close:hover { background: var(--n-200); color: var(--n-900); }
.modal-close svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.modal-body { padding: 20px 22px; }
.modal-foot { padding: 14px 22px 18px; border-top: 1px solid var(--n-100); display: flex; justify-content: flex-end; gap: 8px; }

/* ── Search ── */
.search { position: relative; display: flex; align-items: center; }
.search input {
  padding: 7px 11px 7px 34px;
  border-radius: 7px;
  border: 1.5px solid var(--n-200);
  background: var(--n-50);
  font-family: var(--font-body);
  font-size: 13px;
  color: var(--n-900);
  outline: none;
  min-width: 200px;
  transition: border-color var(--dur);
}
.search input:focus { border-color: var(--brand-600); background: #fff; }
.search .si {
  position: absolute;
  left: 10px;
  width: 15px; height: 15px;
  color: var(--n-400);
  pointer-events: none;
  display: flex; align-items: center;
}
.search .si svg { width: 100%; height: 100%; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

/* ── Grid layouts ── */
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
.grid2-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }

/* ── Utility ── */
.flex { display: flex; }
.flex-c { display: flex; align-items: center; }
.flex-cb { display: flex; align-items: center; justify-content: space-between; }
.mb4 { margin-bottom: 16px; }
.mb5 { margin-bottom: 20px; }
.mt4 { margin-top: 16px; }
.mt5 { margin-top: 20px; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .sb { transform: translateX(-100%); }
  .sb.mobile-open { transform: translateX(0); width: var(--sidebar-w); }
  .main-wrap { margin-left: 0 !important; }
  .grid2, .grid3, .grid2-3 { grid-template-columns: 1fr; }
  .form-row, .form-row-3 { grid-template-columns: 1fr; }
  .page-head { flex-direction: column; }
  .page { padding: 16px; }
  .topbar { padding: 0 16px; }
  .stats { grid-template-columns: 1fr 1fr; }
}

@media print {
  .sb, .topbar, .deped-stripe, .page-head-actions, .btn { display: none !important; }
  .main-wrap { margin-left: 0 !important; }
  .page { padding: 0 !important; }
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--n-300); border-radius: 3px; }

.sb.collapsed ~ .main-wrap,
.main-wrap.expanded {
  margin-left: var(--sidebar-mini) !important;
  transition: margin-left 220ms var(--ease);
}
.main-wrap {
  transition: margin-left 220ms var(--ease);
}
</style>
</head>
<body>

<!-- ── SIDEBAR ──────────────────────────────────────────────── -->
<aside class="sb <?= $__sbCollapsed ? 'collapsed' : '' ?>" id="sidebar">

  <!-- Brand -->
  <div class="sb-brand">
    <div class="sb-logo">
      <img src="<?= $__base ?>/assets/seal.png" alt="DepEd"
           onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <svg class="sb-logo-fallback" style="display:none" viewBox="0 0 24 24">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
      </svg>
    </div>
    <div class="sb-brand-text">
      <div class="sb-brand-name"><?= e(SITE_SHORT) ?></div>
      <div class="sb-brand-sub">DepEd SBM Portal</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sb-nav" id="sbNav" aria-label="Main navigation">
    <?php
    $__svgPaths = [
      'grid'          => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
      'bar-chart-2'   => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
      'users'         => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
      'home'          => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
      'calendar'      => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
      'check-circle'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
      'trending-up'   => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
      'file-text'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
      'bell'          => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
      'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
      'layers'        => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
      'paperclip'     => '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
      'eye'           => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
      'briefcase'     => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
      'send'          => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
      'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
'plus'          => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
'search'        => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
'trash'         => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
'edit'          => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
'x'             => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
'check'         => '<polyline points="20 6 9 17 4 12"/>',
'alert-circle'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
'info'          => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
'star'          => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
'dollar-sign'   => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
'cpu'           => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>',
'zap'           => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
'save'          => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
'refresh-cw'    => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
'alert-triangle'=> '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
'minus-circle'  => '<circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>',
'loader'        => '<line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/>',
'percent'       => '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
'award'         => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
'arrow-left'    => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
'clipboard'     => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
'clipboard-check'=> '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2"/>',
'filter'        => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
'link'          => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
'grid-2'        => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
'check-square'  => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
'bar-chart-2'   => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
'clock'         => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
'user'          => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    ];
    $__icon = function(string $n) use ($__svgPaths): string {
      $d = $__svgPaths[$n] ?? '';
      return "<svg viewBox=\"0 0 24 24\">$d</svg>";
    };

    foreach ($__navGroups as $group):
      [$groupLabel, $groupIcon, $groupItems] = $group;
      
      // Check if any item in this group is active
      $groupActive = false;
      foreach ($groupItems as $item) {
        if (basename($item[1]) === basename($_SERVER['PHP_SELF'])) $groupActive = true;
      }
    ?>

    <div class="sb-group <?= $groupActive ? 'open' : '' ?>">
      <div class="sb-item" onclick="toggleGroup(this.parentElement)" data-label="<?= e($groupLabel) ?>">
        <span class="sb-icon"><?= $__icon($groupIcon) ?></span>
        <span class="sb-label"><?= e($groupLabel) ?></span>
        <svg class="sb-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </div>

      <div class="sb-children">
        <?php foreach ($groupItems as $item):
          $isActive = basename($item[1]) === basename($_SERVER['PHP_SELF']);
        ?>
        <a href="<?= $__base ?>/<?= e($item[1]) ?>"
           class="sb-child <?= $isActive ? 'active' : '' ?>"
           data-label="<?= e($item[0]) ?>">
          <span class="sb-icon"><?= $__icon($item[2]) ?></span>
          <span><?= e($item[0]) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php endforeach; ?>
  </nav>

  <!-- Footer / User -->
  <div class="sb-footer">
    <div class="sb-user-tile" id="userTile" onclick="toggleUserMenu()" role="button" tabindex="0" aria-label="User menu">
      <div class="sb-avatar" style="background: <?= $__roleColor ?>;">
        <?= e($__initials) ?>
      </div>
      <div class="sb-user-info">
        <div class="sb-user-name"><?= e($__me['name']) ?></div>
        <div class="sb-user-role"><?= e($__roleLabel) ?></div>
      </div>
      <svg class="sb-user-more" viewBox="0 0 24 24">
        <circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>
      </svg>
    </div>

    <!-- Popup menu -->
    <div class="sb-popup" id="userPopup" role="menu">
      <div class="sb-popup-user">
        <div class="sb-avatar" style="background:<?= $__roleColor ?>;width:36px;height:36px;font-size:13px;">
          <?= e($__initials) ?>
        </div>
        <div>
          <div class="sb-popup-name"><?= e($__me['name']) ?></div>
          <div class="sb-popup-role"><?= e($__roleLabel) ?></div>
        </div>
      </div>
      <a href="<?= $__base ?>/logout.php" class="sb-popup-item danger" role="menuitem">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign out
      </a>
    </div>
  </div>
</aside>

<!-- ── MAIN WRAPPER ──────────────────────────────────────────── -->
<div class="main-wrap <?= $__sbCollapsed ? 'expanded' : '' ?>" id="mainWrap">

  <div class="deped-stripe"></div>

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-btn" id="menuBtn" aria-label="Toggle sidebar">
        <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar-breadcrumb">
        <span class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="role-chip">
        <span class="dot" style="background:<?= $__roleColor ?>;"></span>
        <?= e($__roleLabel) ?>
      </div>
    </div>
  </header>

  <!-- Page content area -->
  <main class="page" id="mainPage">

<?php
// ── JS SVG icons (for dynamic insertion) ─────────────────────
$__svgJs = json_encode($__svgPaths);
?>

<?php
// ── Backward-compatibility shim ──────────────────────────────
// Old views call svgIcon() as a PHP function. Keep it working.
$__svgPHP = $__svgPaths; // already defined above
function svgIcon(string $n, string $cls = '', string $style = ''): string {
    global $__svgPHP;
    $d = $__svgPHP[$n] ?? '';
    $styleAttr = $style ? " style=\"$style\"" : '';
    return "<span class=\"sb-icon $cls\"$styleAttr><svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\">$d</svg></span>";
}
?>

<script>
const SVG_PATHS = <?= $__svgJs ?>;
function svgI(n) {
  return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${SVG_PATHS[n]||''}</svg>`;
}
function svgIcon(n, cls='', style='') {
  const d = SVG_PATHS[n]||'';
  return `<span class="sb-icon ${cls}" style="${style}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${d}</svg></span>`;
}

// ── Sidebar collapse ──────────────────────────────────────────
(function() {
  // Fix tooltip and popover vertical position for collapsed sidebar items
  document.addEventListener('mouseover', function(e) {
    const sb = document.getElementById('sidebar');
    if (!sb.classList.contains('collapsed')) return;

    const group = e.target.closest('.sb-group');
    if (group) {
      const rect = group.getBoundingClientRect();
      const children = group.querySelector('.sb-children');
      if (children) {
        children.style.setProperty('--popover-top', rect.top + 'px');
      }
    }

    const item = e.target.closest('.sb-item, .sb-child');
    if (!item) return;
    const rect = item.getBoundingClientRect();
    item.style.setProperty('--tooltip-top', rect.top + (rect.height / 2) - 14 + 'px');
  });

  // Accordion toggle logic
  window.toggleGroup = function(groupEl) {
    const sb = document.getElementById('sidebar');
    if (sb.classList.contains('collapsed')) {
      // Expand sidebar on group click for better UX
      document.getElementById('menuBtn').click();
      return;
    }
    groupEl.classList.toggle('open');
  };
  const sb  = document.getElementById('sidebar');
  const mw  = document.getElementById('mainWrap');
  const btn = document.getElementById('menuBtn');
  const MOBILE = () => window.innerWidth <= 768;

  function applyState(collapsed) {
  if (MOBILE()) {
    sb.classList.toggle('mobile-open', !collapsed);
    sb.classList.remove('collapsed');
    mw.classList.remove('expanded');
  } else {
    sb.classList.toggle('collapsed', collapsed);
    mw.classList.toggle('expanded', collapsed);
  }
}

  // Restore saved state (handled by PHP cookies initially, but synced here)
  const saved = localStorage.getItem('sbCollapsed') === 'true';
  applyState(saved);

  btn.addEventListener('click', () => {
    const next = MOBILE()
      ? !sb.classList.contains('mobile-open')
      : !sb.classList.contains('collapsed');
    
    // Set cookie for PHP (30 days)
    document.cookie = `sb_collapsed=${next}; path=/; max-age=${30*24*60*60}; SameSite=Lax`;
    
    localStorage.setItem('sbCollapsed', MOBILE() ? 'false' : String(next));
    applyState(next);
  });

  // Close on mobile overlay click
  document.addEventListener('click', e => {
    if (MOBILE() && !sb.contains(e.target) && !btn.contains(e.target)) {
      applyState(true);
    }
  });
})();

// ── User popup ───────────────────────────────────────────────
function toggleUserMenu() {
  document.getElementById('userPopup')?.classList.toggle('open');
}
document.addEventListener('click', e => {
  const tile  = document.getElementById('userTile');
  const popup = document.getElementById('userPopup');
  if (popup && tile && !tile.contains(e.target) && !popup.contains(e.target)) {
    popup.classList.remove('open');
  }
});

// ── Modal helpers ─────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.overlay.open').forEach(o => o.classList.remove('open'));
});

// ── Toast ─────────────────────────────────────────────────────
function toast(msg, type = 'ok') {
  const colors = { ok:'#15803D', err:'#DC2626', warning:'#D97706', info:'#2563EB' };
  Toastify({
    text: msg,
    duration: 3500,
    gravity: 'top',
    position: 'right',
    stopOnFocus: true,
    style: {
      background: colors[type] || colors.ok,
      borderRadius: '10px',
      fontFamily: "'Inter',sans-serif",
      fontSize: '13.5px',
      fontWeight: '600',
      padding: '12px 18px',
      boxShadow: '0 8px 24px rgba(0,0,0,.18)',
      minWidth: '260px',
    }
  }).showToast();
}

// ── API helpers ───────────────────────────────────────────────
const _csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
async function apiPost(url, data) {
  data.csrf_token = _csrf;
  const res = await fetch(url, { method:'POST', body: new URLSearchParams(data) });
  if (res.status === 403) {
    toast('Your session has expired. Please refresh the page.', 'err');
    return { ok: false, msg: 'Session expired.' };
  }
  if (!res.ok) {
    return { ok: false, msg: 'Server error (' + res.status + ').' };
  }
  return res.json();
}
function filterTable(q, id) {
  const lower = q.toLowerCase();
  document.querySelectorAll(`#${id} tbody tr`).forEach(r => {
    if (r.dataset.filtered === 'true') return;
    const text = Array.from(r.querySelectorAll('td'))
      .map(td => td.textContent).join(' ').toLowerCase();
    r.style.display = text.includes(lower) ? '' : 'none';
  });
}
function $(id)    { return document.getElementById(id)?.value || ''; }
function $v(id,v) { const el = document.getElementById(id); if(el) el.value = v || ''; }
function $el(id)  { return document.getElementById(id); }
function liveSet(attr, val) {
  document.querySelectorAll(`[data-live="${attr}"]`).forEach(el => el.textContent = val);
}

// ── Live polling ──────────────────────────────────────────────
async function pollUpdates() {
  try {
    const res = await fetch('<?= $__base ?>/includes/poll.php');
    if (!res.ok) return;
    const d = await res.json();
    if (d.schools     !== undefined) liveSet('total-schools', d.schools);
    if (d.users       !== undefined) liveSet('total-users',   d.users);
    if (d.cycles      !== undefined) liveSet('total-cycles',  d.cycles);
    if (d.submitted   !== undefined) liveSet('submitted',     d.submitted);
    if (d.validated   !== undefined) liveSet('validated',     d.validated);
    if (d.in_progress !== undefined) liveSet('in-progress',   d.in_progress);
    if (d.progress    !== undefined) {
      liveSet('progress-pct',  d.progress + '%');
      liveSet('progress-text', d.responded + '/42 indicators rated');
      liveSet('overall-score', d.overall  ? d.overall + '%' : '—');
      liveSet('maturity',      d.maturity || '—');
      document.querySelectorAll('.prog-fill.live').forEach(el => el.style.width = d.progress + '%');
    }
    if (d.activity) {
      const feed = document.getElementById('live-activity-feed');
      if (feed) feed.innerHTML = d.activity.map(i => `
        <div class="flex-cb" style="padding:7px 0;border-bottom:1px solid var(--n-100);">
          <div style="font-size:12.5px;color:var(--n-700);">${i.name} — <span style="color:var(--n-500);">${i.action}</span></div>
          <div style="font-size:11px;color:var(--n-400);">${i.ago}</div>
        </div>`).join('');
    }
  } catch(e) {}
}
setInterval(pollUpdates, 8000);

// Backwards-compat shims for older PHP views that use the old class names
function sbmMaturityBadge(level) {
  const map = {
    'Beginning':  ['#FEE2E2','#DC2626','#FECACA'],
    'Developing': ['#FEF3C7','#D97706','#FDE68A'],
    'Maturing':   ['#DBEAFE','#2563EB','#BFDBFE'],
    'Advanced':   ['#DCFCE7','#16A34A','#BBF7D0'],
  };
  const [bg,c,br] = map[level] || ['#F3F4F6','#6B7280','#E5E7EB'];
  return `<span class="pill" style="background:${bg};color:${c};border-color:${br};">${level}</span>`;
}
</script>