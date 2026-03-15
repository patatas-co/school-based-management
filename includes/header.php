<?php
// includes/header.php — SBM System
if (session_status() === PHP_SESSION_NONE) session_start();
$__me   = me();
$__role = $__me['role'];
$__base = baseUrl();
$__nav = [];
if ($__role === 'admin') $__nav = [
    ['dashboard.php','grid','Dashboard'],['users.php','users','User Management'],
    ['schools.php','home','Schools'],['assessment.php','check-circle','SBM Assessments'],
    ['analytics.php','bar-chart-2','Analytics & ML'],['reports.php','file-text','Reports'],
    ['workflow.php','calendar','Workflow & Timeline'],
    ['announcements.php','bell','Announcements'],['settings.php','settings','Settings'],
];
elseif ($__role === 'school_head') $__nav = [
    ['dashboard.php','grid','Dashboard'],['self_assessment.php','check-circle','Self-Assessment'],
    ['dimensions.php','layers','Dimensions'],['improvement.php','trending-up','Improvement Plan'],
    ['evidence.php','paperclip','Evidence & MOV'],['announcements.php','bell','Announcements'],
    ['reports.php','file-text','Reports'],
];
elseif ($__role === 'teacher') $__nav = [
    ['dashboard.php','grid','Dashboard'],
    ['self_assessment.php','check-circle','Self-Assessment'],
    ['announcements.php','bell','Announcements'],
];
elseif ($__role === 'sdo') $__nav = [
    ['dashboard.php','grid','Dashboard'],['schools.php','home','Schools'],
    ['assessments.php','check-circle','Assessments'],['technical_assistance.php','briefcase','Technical Assistance'],
    ['ta_requests.php','send','TA Requests'],
    ['reports.php','file-text','Reports'],['workflow.php','calendar','Workflow & Timeline'],
    ['announcements.php','bell','Announcements'],
];
elseif ($__role === 'ro') $__nav = [
    ['dashboard.php','grid','Dashboard'],['reports.php','file-text','Reports'],
    ['announcements.php','bell','Announcements'],
];

elseif ($__role === 'external_stakeholder') $__nav = [
    ['dashboard.php','grid','Dashboard'],
    ['self_assessment.php','check-circle','Self-Assessment'],
    ['announcements.php','bell','Announcements'],
];
$__initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ', trim($__me['name'])), 0, 2))));
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="<?= $__base ?>/favicon/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $__base ?>/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $__base ?>/favicon/favicon-16x16.png">
<title><?= e($pageTitle??'Dashboard') ?> — <?= e(SITE_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Serif+Display&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<style>
:root{--g900:#0A2E1A;--g800:#0F4A2B;--g700:#166534;--g600:#15803D;--g500:#16A34A;--g400:#22C55E;--g300:#86EFAC;--g200:#BBF7D0;--g100:#DCFCE7;--g50:#F0FDF4;--n900:#111827;--n800:#1F2937;--n700:#374151;--n600:#4B5563;--n500:#6B7280;--n400:#9CA3AF;--n300:#D1D5DB;--n200:#E5E7EB;--n100:#F3F4F6;--n50:#F9FAFB;--white:#FFFFFF;--gold:#D97706;--goldb:#FEF3C7;--red:#DC2626;--redb:#FEE2E2;--blue:#2563EB;--blueb:#DBEAFE;--purple:#7C3AED;--purpb:#EDE9FE;--teal:#0D9488;--tealb:#CCFBF1;--sidebar:252px;--topbar:60px;--radius:10px;--radius-sm:6px;--font:'DM Sans',sans-serif;--font-serif:'DM Serif Display',serif;--shadow:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.05);--shadow-md:0 4px 6px -1px rgba(0,0,0,.08),0 2px 4px -1px rgba(0,0,0,.05);--shadow-lg:0 10px 15px -3px rgba(0,0,0,.08),0 4px 6px -2px rgba(0,0,0,.04);--trans:140ms cubic-bezier(.4,0,.2,1);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{font-size:15px;scroll-behavior:smooth;}
body{font-family:var(--font);background:var(--n50);color:var(--n800);display:flex;min-height:100vh;-webkit-font-smoothing:antialiased;}
.sidebar{position:fixed;top:0;left:0;bottom:0;width:var(--sidebar);background:var(--g800);display:flex;flex-direction:column;z-index:100;transition:width var(--trans);overflow:hidden;}
.sidebar.collapsed {
  width: 60px;
}
.sidebar.collapsed .sb-name,
.sidebar.collapsed .nav-link span,
.sidebar.collapsed .user-meta,
.sidebar.collapsed .logout-btn,
.sidebar.collapsed .sb-section {
  display: none;
}
.sidebar.collapsed .sb-brand {
  justify-content: center;
  padding: 18px 10px 14px;
}
.sidebar.collapsed .sb-logo {
  margin: 0;
}
.sidebar.collapsed .nav-link {
  justify-content: center;
  padding: 10px 0;
  position: relative;
}
.sidebar.collapsed .nav-link .ni {
  display: flex !important;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  flex-shrink: 0;
}
.sidebar.collapsed .nav-link .ni svg {
  width: 20px;
  height: 20px;
  display: block !important;
  stroke: rgba(255,255,255,.65);
}
.sidebar.collapsed .nav-link.active .ni svg {
  stroke: #fff;
}
.sidebar.collapsed .nav-link:hover .ni {
  background: rgba(255,255,255,.1);
}
/* Tooltip on hover */
.sidebar.collapsed .nav-link::after {
  content: attr(data-label);
  position: absolute;
  left: 62px;
  top: 50%;
  transform: translateY(-50%);
  background: var(--g900);
  color: #fff;
  padding: 5px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  z-index: 9999;
  box-shadow: 0 4px 12px rgba(0,0,0,.3);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s;
}
.sidebar.collapsed .nav-link:hover::after {
  opacity: 1;
}
.sidebar.collapsed .user-tile {
  justify-content: center;
  padding: 9px 6px;
}
.sidebar.collapsed .user-avatar {
  margin: 0;
}
/* Collapsed logout popup — opens to the right */
.sidebar.collapsed .user-popup {
  left: 65px;
  bottom: 10px;
  right: auto;
  width: 190px;
  z-index: 9999;
  position: fixed;
}
.sidebar::after{content:'';position:absolute;inset:0;background:repeating-linear-gradient(135deg,transparent 0,transparent 28px,rgba(255,255,255,.018) 28px,rgba(255,255,255,.018) 30px);pointer-events:none;}
.sb-brand{padding:18px 16px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:11px;}
.sb-logo{width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.1);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.sb-logo img{width:100%;height:100%;object-fit:cover;}
.sb-logo svg{width:22px;height:22px;flex-shrink:0;}
.sb-name strong{display:block;font-size:13px;font-weight:700;color:#fff;line-height:1.2;}
.sb-name span{font-size:10px;color:rgba(255,255,255,.4);}
.sb-nav{flex:1;padding:10px 8px;overflow-y:auto;}
.sb-nav::-webkit-scrollbar{width:3px;}
.sb-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:3px;}
.nav-link{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:6px;color:rgba(255,255,255,.62);font-size:13px;font-weight:500;text-decoration:none;margin-bottom:1px;transition:all var(--trans);white-space:nowrap;position:relative;}
.nav-link span{overflow:hidden;text-overflow:ellipsis;}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.08);}
.nav-link.active{color:#fff;background:var(--g600);box-shadow:inset 3px 0 0 var(--g300);}
.nav-link .ni{width:16px;height:16px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.nav-link .ni svg{width:100%;height:100%;}
.sb-footer{position:relative;padding:10px 8px 12px;border-top:1px solid rgba(255,255,255,.07);}
.user-tile{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:6px;background:rgba(255,255,255,.06);cursor:pointer;}
.user-avatar{width:32px;height:32px;border-radius:8px;background:var(--g500);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;}
.user-meta{flex:1;min-width:0;}
.user-meta strong{display:block;color:#fff;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.user-meta span{font-size:10.5px;color:rgba(255,255,255,.4);text-transform:capitalize;}
.user-popup{display:none;position:absolute;bottom:70px;left:10px;right:10px;background:var(--g900);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px;z-index:200;box-shadow:0 -4px 20px rgba(0,0,0,.4);}
.user-popup.open{display:block;}
.user-popup-info{display:flex;align-items:center;gap:10px;padding:6px 4px 10px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:8px;}
.user-popup-info strong{display:block;font-size:13px;color:#fff;font-weight:600;}
.user-popup-info span{font-size:11px;color:rgba(255,255,255,.45);}
.user-popup-logout{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;color:#FCA5A5;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s;}
.user-popup-logout:hover{background:rgba(220,38,38,.15);}
.user-popup-logout svg{width:15px;height:15px;stroke:#FCA5A5;}
.main-wrap{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin-left var(--trans);}
.main-wrap.expanded{margin-left:60px;}
.deped-stripe{height:4px;background:linear-gradient(90deg,var(--g600) 0%,var(--g400) 40%,#FFD700 70%,#CE1126 100%);position:fixed;top:0;left:var(--sidebar);right:0;z-index:200;transition:left var(--trans);}
.deped-stripe.expanded{left:60px;}
.topbar{height:var(--topbar);background:var(--white);border-bottom:1px solid var(--n200);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:50;gap:16px;}
.topbar-left{display:flex;align-items:center;gap:10px;}
.topbar-left h1{font-size:16px;font-weight:700;color:var(--n900);}
.menu-btn{display:flex;width:34px;height:34px;border-radius:6px;border:1px solid var(--n200);background:transparent;cursor:pointer;align-items:center;justify-content:center;color:var(--n700);}
.menu-btn svg{width:17px;height:17px;}
.topbar-right{display:flex;align-items:center;gap:8px;}
.role-chip{padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:var(--g100);color:var(--g700);border:1px solid var(--g200);}
.page{flex:1;padding:24px;}
.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap;}
.page-head-text h2{font-family:var(--font-serif);font-size:24px;font-weight:400;color:var(--n900);line-height:1.2;}
.page-head-text p{font-size:13px;color:var(--n500);margin-top:3px;}
.page-head-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--n200);box-shadow:var(--shadow);overflow:hidden;}
.card-head{padding:14px 18px 12px;border-bottom:1px solid var(--n100);display:flex;align-items:center;justify-content:space-between;gap:12px;}
.card-title{font-size:14px;font-weight:700;color:var(--n900);}
.card-body{padding:18px;}
.stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;margin-bottom:20px;}
.stat{background:var(--white);border:1px solid var(--n200);border-radius:var(--radius);padding:18px 16px;display:flex;align-items:center;gap:13px;box-shadow:var(--shadow);transition:box-shadow var(--trans);}
.stat:hover{box-shadow:var(--shadow-md);}
.stat-ic{width:42px;height:42px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-ic svg{width:19px;height:19px;}
.stat-ic.green{background:var(--g100);color:var(--g600);}.stat-ic.gold{background:var(--goldb);color:var(--gold);}.stat-ic.blue{background:var(--blueb);color:var(--blue);}.stat-ic.red{background:var(--redb);color:var(--red);}.stat-ic.purple{background:var(--purpb);color:var(--purple);}.stat-ic.teal{background:var(--tealb);color:var(--teal);}.stat-ic.dark{background:var(--g800);color:#fff;}
.stat-val{font-size:26px;font-weight:800;color:var(--n900);line-height:1;}
.stat-lbl{font-size:11.5px;color:var(--n500);margin-top:2px;font-weight:500;}
.stat-sub{font-size:11px;color:var(--g600);margin-top:3px;font-weight:600;}
.tbl-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13.5px;}
thead th{background:var(--g50);padding:9px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--g700);text-transform:uppercase;letter-spacing:.06em;border-bottom:2px solid var(--g200);white-space:nowrap;}
tbody td{padding:10px 14px;border-bottom:1px solid var(--n100);color:var(--n700);vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:var(--g50);}
.pill{display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap;border:1px solid transparent;}
.pill-active{background:var(--g100);color:var(--g700);border-color:var(--g200);}.pill-inactive{background:var(--n100);color:var(--n500);border-color:var(--n200);}.pill-draft{background:var(--n100);color:var(--n500);border-color:var(--n200);}.pill-in_progress{background:var(--blueb);color:var(--blue);border-color:#BFDBFE;}.pill-submitted{background:var(--goldb);color:var(--gold);border-color:#FDE68A;}.pill-validated{background:var(--g100);color:var(--g700);border-color:var(--g200);}.pill-returned{background:var(--redb);color:var(--red);border-color:#FECACA;}.pill-admin{background:var(--g100);color:var(--g700);border-color:var(--g200);}.pill-school_head{background:var(--purpb);color:var(--purple);border-color:#DDD6FE;}.pill-teacher{background:var(--blueb);color:var(--blue);border-color:#BFDBFE;}.pill-sdo{background:var(--goldb);color:var(--gold);border-color:#FDE68A;}.pill-ro{background:var(--tealb);color:var(--teal);border-color:#99F6E4;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 15px;border-radius:6px;border:none;cursor:pointer;font-family:var(--font);font-size:13.5px;font-weight:600;transition:all var(--trans);text-decoration:none;white-space:nowrap;line-height:1.4;}
.btn-primary{background:var(--g600);color:#fff;}.btn-primary:hover{background:var(--g700);}
.btn-secondary{background:var(--n100);color:var(--n700);border:1px solid var(--n200);}.btn-secondary:hover{background:var(--n200);}
.btn-danger{background:var(--redb);color:var(--red);}.btn-danger:hover{background:var(--red);color:#fff;}
.btn-success{background:var(--g100);color:var(--g700);border:1px solid var(--g200);}.btn-success:hover{background:var(--g600);color:#fff;}
.btn-blue{background:var(--blueb);color:var(--blue);border:1px solid #BFDBFE;}.btn-blue:hover{background:var(--blue);color:#fff;}
.btn-sm{padding:4px 10px;font-size:12px;border-radius:5px;gap:5px;}
.btn svg,.btn-sm svg{width:14px;height:14px;}
.fg{margin-bottom:14px;}
.fg label{display:block;font-size:12.5px;font-weight:600;color:var(--n700);margin-bottom:5px;}
.fc{width:100%;padding:8px 11px;border-radius:6px;border:1.5px solid var(--n200);background:var(--white);font-family:var(--font);font-size:13.5px;color:var(--n900);transition:border-color var(--trans),box-shadow var(--trans);outline:none;}
.fc:focus{border-color:var(--g500);box-shadow:0 0 0 3px rgba(22,163,74,.12);}
select.fc{cursor:pointer;}textarea.fc{resize:vertical;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}.form-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.42);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;z-index:200;padding:20px;opacity:0;pointer-events:none;transition:opacity var(--trans);}
.overlay.open{opacity:1;pointer-events:all;}
.modal{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow-lg);width:100%;max-width:520px;max-height:92vh;overflow-y:auto;transform:scale(.96) translateY(10px);transition:transform var(--trans);}
.overlay.open .modal{transform:scale(1) translateY(0);}
.modal-head{padding:16px 20px 12px;border-bottom:1px solid var(--n100);display:flex;align-items:center;justify-content:space-between;}
.modal-title{font-size:15px;font-weight:700;color:var(--n900);}
.modal-close{width:28px;height:28px;border-radius:5px;border:none;background:var(--n100);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--n500);transition:all var(--trans);}
.modal-close:hover{background:var(--n200);color:var(--n900);}
.modal-close svg{width:14px;height:14px;}
.modal-body{padding:18px 20px;}.modal-foot{padding:12px 20px 16px;border-top:1px solid var(--n100);display:flex;justify-content:flex-end;gap:8px;}
.alert{display:flex;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:6px;margin-bottom:14px;font-size:13.5px;}
.alert svg{width:15px;height:15px;flex-shrink:0;margin-top:1px;}
.alert-success{background:var(--g100);color:var(--g700);border:1px solid var(--g200);}.alert-danger{background:var(--redb);color:var(--red);border:1px solid #FECACA;}.alert-warning{background:var(--goldb);color:var(--gold);border:1px solid #FDE68A;}.alert-info{background:var(--blueb);color:var(--blue);border:1px solid #BFDBFE;}
.prog{height:7px;background:var(--n100);border-radius:999px;overflow:hidden;}
.prog-fill{height:100%;border-radius:999px;transition:width .5s ease;}
.search{position:relative;display:flex;align-items:center;}
.search input{padding:7px 11px 7px 33px;border-radius:6px;border:1.5px solid var(--n200);background:var(--n50);font-family:var(--font);font-size:13px;color:var(--n900);outline:none;min-width:200px;transition:border-color var(--trans);}
.search input:focus{border-color:var(--g500);background:var(--white);}
.search .si{position:absolute;left:9px;width:14px;height:14px;color:var(--n400);pointer-events:none;}
.search .si svg{width:100%;height:100%;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px;}.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;}.grid2-3{display:grid;grid-template-columns:2fr 1fr;gap:18px;}
.mt3{margin-top:12px;}.mt4{margin-top:16px;}.mt5{margin-top:20px;}.mb4{margin-bottom:16px;}.mb5{margin-bottom:20px;}
.flex{display:flex;}.flex-c{display:flex;align-items:center;}.flex-cb{display:flex;align-items:center;justify-content:space-between;}.gap{gap:18px;}
@media(max-width:768px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.main-wrap{margin-left:0!important;}.grid2,.grid3,.grid2-3{grid-template-columns:1fr;}.form-row,.form-row3{grid-template-columns:1fr;}.page-head{flex-direction:column;}.page{padding:14px;}.topbar{padding:0 14px;}.stats{grid-template-columns:1fr 1fr;}}
@media print{.sidebar,.topbar,.deped-stripe,.page-head-actions{display:none!important;}.main-wrap{margin-left:0!important;}.page{padding:0!important;}}
</style></head><body>
<div class="deped-stripe" id="depedStripe"></div>
<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <div class="sb-logo">
      <img src="<?= $__base ?>/assets/seal.png" alt="Seal"
           style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.85)" stroke-width="1.8"
           style="display:none;width:22px;height:22px;flex-shrink:0;">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
        <path d="M2 17l10 5 10-5"/>
        <path d="M2 12l10 5 10-5"/>
      </svg>
    </div>
    <div class="sb-name"><strong><?= e(SITE_NAME) ?></strong><span>DepEd SBM Portal</span></div>
  </div>
  <nav class="sb-nav" id="sbNav"></nav>
  <div class="sb-footer">
    <div class="user-tile" id="userTile" onclick="toggleUserMenu()">
      <div class="user-avatar"><?= e($__initials) ?></div>
      <div class="user-meta"><strong><?= e($__me['name']) ?></strong><span><?= str_replace('_',' ',e($__me['role'])) ?></span></div>
    </div>
    <div class="user-popup" id="userPopup">
      <div class="user-popup-info"><div class="user-avatar" style="width:36px;height:36px;font-size:13px;"><?= e($__initials) ?></div><div><strong><?= e($__me['name']) ?></strong><span><?= ucfirst(str_replace('_',' ',e($__me['role']))) ?></span></div></div>
      <a href="<?= $__base ?>/logout.php" class="user-popup-logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sign Out</a>
    </div>
  </div>
</aside>
<div class="main-wrap" id="mainWrap">
<header class="topbar">
  <div class="topbar-left">
    <button class="menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    <h1><?= e($pageTitle??'Dashboard') ?></h1>
  </div>
  <div class="topbar-right"><span class="role-chip"><?= ucfirst(str_replace('_',' ',$__role)) ?></span></div>
</header>
<main class="page">
<?php
$__SVG=['grid'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>','users'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>','home'=>'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>','check-circle'=>'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>','bar-chart-2'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>','file-text'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>','bell'=>'<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>','settings'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>','layers'=>'<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>','trending-up'=>'<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>','paperclip'=>'<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>','eye'=>'<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>','briefcase'=>'<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>','plus'=>'<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>','search'=>'<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>','trash'=>'<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>','edit'=>'<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>','x'=>'<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>','check'=>'<polyline points="20 6 9 17 4 12"/>','alert-circle'=>'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>','dollar-sign'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>','star'=>'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>','download'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>','calendar'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>','info'=>'<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>','cpu'=>'<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>','send'=>'<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>','save'=>'<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>','zap'=>'<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>','minus-circle'=>'<circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>','loader'=>'<line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>','alert-triangle'=>'<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>','percent'=>'<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>','refresh-cw'=>'<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>'];
function svgIcon(string $n,string $c='',string $s=''):string{global $__SVG;$d=$__SVG[$n]??'';return "<span class=\"ni $c\" style=\"$s\"><svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">$d</svg></span>";}
$navJson=json_encode($__nav);$activeJson=json_encode($activePage??'');
?>
<div id="toast-root"></div>
<script>
const NAV_ITEMS=<?= $navJson ?>;const ACTIVE=<?= $activeJson ?>;const SVG_PATHS=<?= json_encode($__SVG) ?>;
function svgI(n,c=''){const d=SVG_PATHS[n]||'';return `<span class="ni ${c}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg></span>`;}
(function(){
  const nav=document.getElementById('sbNav');
  NAV_ITEMS.forEach(([href,icon,label])=>{const a=document.createElement('a');a.className='nav-link'+(href===ACTIVE?' active':'');a.href=href;a.setAttribute('data-label',label);a.innerHTML=svgI(icon)+`<span>${label}</span>`;nav.appendChild(a);});
  const btn=document.getElementById('menuBtn'),sb=document.getElementById('sidebar'),mw=document.getElementById('mainWrap'),stripe=document.getElementById('depedStripe');
  if(localStorage.getItem('sbCollapsed')==='true'){sb.classList.add('collapsed');mw.classList.add('expanded');if(stripe)stripe.style.left='60px';}
  if(btn)btn.addEventListener('click',()=>{const c=sb.classList.toggle('collapsed');mw.classList.toggle('expanded',c);if(stripe)stripe.style.left=c?'60px':'var(--sidebar)';localStorage.setItem('sbCollapsed',c);});
})();
function openModal(id){document.getElementById(id)?.classList.add('open');}
function closeModal(id){document.getElementById(id)?.classList.remove('open');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.overlay.open').forEach(o=>o.classList.remove('open'));});
function toast(msg, type='ok') {
  const colors = {
    ok:      '#166534',
    err:     '#DC2626',
    warning: '#D97706',
    info:    '#2563EB'
  };
  Toastify({
    text:     msg,
    duration: 3500,
    gravity:  'top',
    position: 'right',
    stopOnFocus: true,
    style: {
      background:   colors[type] || colors.ok,
      borderRadius: '10px',
      fontFamily:   "'DM Sans',sans-serif",
      fontSize:     '13.5px',
      fontWeight:   '600',
      padding:      '12px 18px',
      boxShadow:    '0 8px 24px rgba(0,0,0,.18)',
      minWidth:     '260px'
    },
    close: false
  }).showToast();
}
function filterTable(q,id){document.querySelectorAll(`#${id} tbody tr`).forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q.toLowerCase())?'':'none');}
async function apiPost(url,data){data.csrf_token='<?= csrfToken() ?>';const res=await fetch(url,{method:'POST',body:new URLSearchParams(data)});return res.json();}
function toggleUserMenu(){
  const sb = document.getElementById('sidebar');
  const popup = document.getElementById('userPopup');
  if(!popup) return;
  popup.classList.toggle('open');
}
document.addEventListener('click',function(e){const t=document.getElementById('userTile'),p=document.getElementById('userPopup');if(p&&t&&!t.contains(e.target)&&!p.contains(e.target))p.classList.remove('open');});
function $(id){return document.getElementById(id)?.value||'';}
function $v(id,val){const el=document.getElementById(id);if(el)el.value=val||'';}
function $el(id){return document.getElementById(id);}
function liveSet(attr,val){document.querySelectorAll(`[data-live="${attr}"]`).forEach(el=>el.textContent=val);}

// ── Real-time polling (no page-loading spinner) ──
async function pollUpdates(){
  try {
    const res = await fetch('<?= baseUrl() ?>/includes/poll.php');
    if(!res.ok) return;
    const d = await res.json();
    if(d.schools    !== undefined) liveSet('total-schools', d.schools);
    if(d.users      !== undefined) liveSet('total-users',   d.users);
    if(d.cycles     !== undefined) liveSet('total-cycles',  d.cycles);
    if(d.submitted  !== undefined) liveSet('submitted',     d.submitted);
    if(d.validated  !== undefined) liveSet('validated',     d.validated);
    if(d.in_progress!== undefined) liveSet('in-progress',   d.in_progress);
    if(d.progress   !== undefined){
      liveSet('progress-pct',  d.progress+'%');
      liveSet('progress-text', d.responded+'/42 indicators rated');
      liveSet('overall-score', d.overall ? d.overall+'%' : '—');
      liveSet('maturity',      d.maturity || '—');
      document.querySelectorAll('.prog-fill.green').forEach(el=>el.style.width=d.progress+'%');
    }
    if(d.activity){
      const feed=document.getElementById('live-activity-feed');
      if(feed) feed.innerHTML=d.activity.map(item=>`
        <div class="flex-cb" style="padding:7px 0;border-bottom:1px solid var(--n100);">
          <div style="font-size:12.5px;color:var(--n700);">${item.name} — <span style="color:var(--n500);">${item.action}</span></div>
          <div style="font-size:11px;color:var(--n400);">${item.ago}</div>
        </div>`).join('');
    }
  } catch(e){}
}
setInterval(pollUpdates, 8000);
</script>