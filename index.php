<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
// Fetch stats
$total_indicators = $db->query("SELECT COUNT(*) FROM sbm_indicators WHERE is_active=1")->fetchColumn();
$total_dimensions = $db->query("SELECT COUNT(*) FROM sbm_dimensions")->fetchColumn();
$total_schools = 1; // DIHS only
$validated_cycles = $db->query("SELECT COUNT(*) FROM sbm_cycles WHERE status='validated' AND school_id=" . (int) SCHOOL_ID)->fetchColumn();


$is_logged_in = !empty($_SESSION['user_id']);
$dashboard_url = $is_logged_in ? roleHome($_SESSION['role']) : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
  <link rel="manifest" href="favicon/site.webmanifest">
  <link rel="shortcut icon" href="favicon/favicon.ico">
  <title><?= e(SITE_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap"
    rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #FFFFFF;
      --page-bg: #F9FAFB;
      --text-dark: #111827;
      --text-mid: #4B5563;
      --navy: #15803D;
      /* DepEd Green */
      --blue: #16A34A;
      /* Success Green */
      --radius-btn: 8px;
      /* Matching login buttons */
      --radius-img: 12px;
    }

    html {
      background: var(--page-bg);
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text-dark);
      max-width: 100%;
      margin: 0;
      padding: 0 96px;
      overflow-x: hidden;
    }

    /* ── Header ── */
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 100px;
      opacity: 0;
      transform: translateY(-12px);
      animation: fadeUp 0.6s ease forwards;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .logo-icon {
      width: 55px;
      height: 55px;
      flex-shrink: 0;
    }

    .logo-text {
      font-family: 'DM Sans', sans-serif;
      font-size: 25px;
      font-weight: 600;
      color: var(--text-dark);
      letter-spacing: -0.5px;
    }

    nav {
      display: flex;
      align-items: center;
      gap: 48px;
    }

    nav a {
      font-size: 18px;
      font-weight: 500;
      color: var(--text-dark);
      text-decoration: none;
      transition: opacity 0.2s ease;
    }

    nav a:hover {
      opacity: 0.55;
    }

    /* ── Hero ── */
    .hero {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 64px;
    }

    .hero-text {
      display: flex;
      flex-direction: column;
      align-items: center;
      max-width: 1100px;
      text-align: center;
      margin-bottom: 48px;
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--blue);
      margin-bottom: 24px;
      opacity: 0;
      animation: fadeUp 0.6s 0.15s ease forwards;
    }

    .hero-eyebrow::before,
    .hero-eyebrow::after {
      content: '';
      display: block;
      width: 24px;
      height: 1px;
      background: var(--blue);
      opacity: 0.5;
    }

    h1 {
      font-family: 'Instrument Serif', Georgia, serif;
      font-size: clamp(52px, 7.5vw, 96px);
      font-weight: 400;
      color: var(--text-dark);
      line-height: 1.05;
      letter-spacing: -2px;
      margin-bottom: 32px;
      opacity: 0;
      animation: fadeUp 0.7s 0.25s ease forwards;
    }

    h1 em {
      font-style: italic;
      color: var(--navy);
    }

    .hero-sub {
      font-size: 20px;
      font-weight: 300;
      color: var(--text-mid);
      line-height: 1.65;
      max-width: 620px;
      text-align: center;
      margin-bottom: 40px;
      opacity: 0;
      animation: fadeUp 0.7s 0.35s ease forwards;
    }

    /* ── Buttons ── */
    .btn-row {
      display: flex;
      flex-direction: row;
      gap: 16px;
      justify-content: center;
      margin-bottom: 64px;
      opacity: 0;
      animation: fadeUp 0.7s 0.45s ease forwards;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      height: 56px;
      padding: 0 32px;
      border-radius: var(--radius-btn);
      font-family: 'DM Sans', sans-serif;
      font-size: 17px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.22s ease;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn-primary {
      background: var(--navy);
      color: #fff;
      border: 2px solid var(--navy);
    }

    .btn-primary:hover {
      background: #162e4d;
      border-color: #162e4d;
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(30, 58, 95, 0.22);
    }

    .btn-primary .arrow {
      display: inline-flex;
      align-items: center;
      transition: transform 0.22s ease;
    }

    .btn-primary:hover .arrow {
      transform: translateX(3px);
    }

    .btn-secondary {
      background: transparent;
      color: var(--navy);
      border: 2px solid var(--navy);
    }

    .btn-secondary:hover {
      background: var(--navy);
      color: #fff;
      transform: translateY(-1px);
    }

    /* ── Hero Image ── */
    .hero-image-wrap {
      width: 100%;
      max-width: 100%;
      border-radius: var(--radius-img) var(--radius-img) 0 0;
      overflow: hidden;
      height: 380px;
      position: relative;
      opacity: 0;
      transform: translateY(20px);
      animation: fadeUp 0.9s 0.55s ease forwards;
      background: linear-gradient(135deg, #dbe4f0 0%, #c2d0e6 100%);
    }

    .hero-image-wrap.img-failed {
      display: none;
    }

    /* Subtle gradient overlay at the bottom to blend into white */
    .hero-image-wrap::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(to bottom, transparent 55%, rgba(255, 255, 255, 0.18) 100%);
      pointer-events: none;
    }

    .hero-image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center top;
      display: block;
    }

    /* Unsplash placeholder: modern office */
    .hero-img-placeholder {
      width: 100%;
      height: 100%;
      background:
        linear-gradient(160deg, #e8e4de 0%, #d6cfc4 40%, #c8bfb0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    /* Decorative office scene drawn with CSS */
    .office-scene {
      width: 100%;
      height: 100%;
      position: absolute;
      inset: 0;
    }

    /* ── Section Shared ── */
    .section {
      padding: 112px 0;
      border-top: 1px solid #EBEBEB;
    }

    .section-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--blue);
      margin-bottom: 20px;
    }

    .section-eyebrow::before {
      content: '';
      display: block;
      width: 20px;
      height: 1px;
      background: var(--blue);
      opacity: 0.5;
    }

    .section-heading {
      font-family: 'Instrument Serif', Georgia, serif;
      font-size: clamp(36px, 3.5vw, 52px);
      font-weight: 400;
      color: var(--text-dark);
      line-height: 1.1;
      letter-spacing: -1px;
      margin-bottom: 16px;
    }

    .section-heading em {
      font-style: italic;
      color: var(--navy);
    }

    .section-sub {
      font-size: 17px;
      font-weight: 300;
      color: var(--text-mid);
      line-height: 1.65;
      max-width: 520px;
    }

    /* Scroll reveal */
    .reveal {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.65s ease, transform 0.65s ease;
    }

    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .reveal-delay-1 {
      transition-delay: 0.1s;
    }

    .reveal-delay-2 {
      transition-delay: 0.2s;
    }

    .reveal-delay-3 {
      transition-delay: 0.3s;
    }

    .reveal-delay-4 {
      transition-delay: 0.4s;
    }

    /* ── Services Section ── */
    .services-header {
      margin-bottom: 64px;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2px;
      background: #EBEBEB;
      border: 1px solid #EBEBEB;
      border-radius: 16px;
      overflow: hidden;
    }

    .service-card {
      background: #fff;
      padding: 40px 36px 44px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      transition: background 0.2s ease;
    }

    .service-card:hover {
      background: #FAFBFF;
    }

    .service-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #EEF4FF;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 4px;
      flex-shrink: 0;
    }

    .service-icon svg {
      width: 22px;
      height: 22px;
      stroke: var(--blue);
    }

    .service-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-dark);
      letter-spacing: -0.3px;
    }

    .service-desc {
      font-size: 15px;
      font-weight: 300;
      color: var(--text-mid);
      line-height: 1.65;
    }

    .service-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 500;
      color: var(--navy);
      text-decoration: none;
      margin-top: auto;
      padding-top: 8px;
      transition: gap 0.2s ease;
    }

    .service-link:hover {
      gap: 10px;
    }

    /* ── Case Studies Section ── */
    .work-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 48px;
    }

    .work-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }

    .case-card {
      border-radius: 16px;
      overflow: hidden;
      background: #fff;
      border: 1px solid #EBEBEB;
      display: flex;
      flex-direction: column;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .case-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 40px rgba(10, 22, 40, 0.09);
    }

    .case-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      display: block;
      background: #E8EDF3;
    }

    .case-body {
      padding: 28px 28px 32px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      flex: 1;
    }

    .case-tag {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--blue);
    }

    .case-title {
      font-family: 'Instrument Serif', Georgia, serif;
      font-size: 22px;
      font-weight: 400;
      color: var(--text-dark);
      line-height: 1.3;
      letter-spacing: -0.3px;
    }

    .case-desc {
      font-size: 14px;
      font-weight: 300;
      color: var(--text-mid);
      line-height: 1.6;
      margin-top: 2px;
    }

    .case-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: auto;
      padding-top: 16px;
      border-top: 1px solid #F0F0F0;
    }

    .case-meta-label {
      font-size: 13px;
      color: var(--text-mid);
      font-weight: 300;
    }

    .case-result {
      font-size: 13px;
      font-weight: 600;
      color: var(--navy);
    }

    /* ── About Section ── */
    .about-inner {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .about-image-wrap {
      border-radius: 16px;
      overflow: hidden;
      aspect-ratio: 4/3;
      background: #E8EDF3;
    }

    .about-image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .about-content {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .about-body {
      font-size: 17px;
      font-weight: 300;
      color: var(--text-mid);
      line-height: 1.75;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      padding-top: 8px;
    }

    .stat {
      display: flex;
      flex-direction: column;
      gap: 4px;
      padding-top: 20px;
      border-top: 2px solid #EBEBEB;
    }

    .stat-number {
      font-family: 'Instrument Serif', Georgia, serif;
      font-size: 38px;
      font-weight: 400;
      color: var(--text-dark);
      letter-spacing: -1px;
      line-height: 1;
    }

    .stat-number span {
      color: var(--blue);
    }

    .stat-label {
      font-size: 13px;
      font-weight: 400;
      color: var(--text-mid);
    }

    /* ── Responsive additions ── */
    @media (max-width: 900px) {
      .services-grid {
        grid-template-columns: 1fr;
      }

      .work-grid {
        grid-template-columns: 1fr;
      }

      .about-inner {
        grid-template-columns: 1fr;
        gap: 40px;
      }

      .work-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }

      .section {
        padding: 80px 0;
      }
    }

    @media (max-width: 600px) {
      .services-grid {
        grid-template-columns: 1fr;
      }

      .work-grid {
        grid-template-columns: 1fr;
      }

      .stats-row {
        grid-template-columns: 1fr 1fr;
      }

      .section {
        padding: 64px 0;
      }
    }

    /* ── Animations ── */
    @keyframes fadeUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      body {
        padding: 0 32px;
      }

      h1 {
        font-size: 52px;
        letter-spacing: -1px;
      }

      .hero-image-wrap {
        width: 100%;
      }
    }

    @media (max-width: 600px) {
      body {
        padding: 0 16px;
      }

      header {
        height: 72px;
      }

      nav {
        gap: 24px;
      }

      nav a {
        font-size: 15px;
      }

      h1 {
        font-size: 38px;
        letter-spacing: -0.5px;
      }

      .hero-sub {
        font-size: 17px;
      }

      .btn-row {
        flex-direction: column;
        align-items: center;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>

<body>

  <!-- Header -->
  <header>
    <a class="logo" href="#">
      <img src="favicon/android-chrome-192x192.png" alt="Main Logo" class="logo-icon">
      <span class="logo-text">School Management</span>
    </a>

    <nav>
      <a href="#services">Features</a>
      <a href="#work">Impact</a>
      <a href="<?= $dashboard_url ?>" class="btn btn-primary" style="height: 40px; padding: 0 20px; font-size: 14px;">
        <?= $is_logged_in ? 'Go to Dashboard' : 'Sign In' ?>
      </a>
    </nav>
  </header>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-text">
      <span class="hero-eyebrow">School-Based Management Monitoring System</span>

      <h1>Governance for<br><em>Quality Education</em></h1>

      <p class="hero-sub">
        A digital platform for School-Based Management self-assessment, monitoring, and governance aligned with DepEd
        Order No. 007, s. 2024.
      </p>

      <div class="btn-row">
        <a href="<?= $dashboard_url ?>" class="btn btn-primary">
          <?= $is_logged_in ? 'Go to Dashboard' : 'Get Started' ?>
          <span class="arrow">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
          </span>
        </a>
        <a href="#services" class="btn btn-secondary">Learn More</a>
      </div>
    </div>

  </section>

  <!-- ══════════════════════════════════
       SERVICES SECTION
  ══════════════════════════════════ -->
  <section class="section" id="services">
    <div class="services-header reveal">
      <span class="section-eyebrow">Key Features</span>
      <h2 class="section-heading">Built for DepEd<br><em>SBM compliance</em></h2>
    </div>

    <div class="services-grid">

      <div class="service-card reveal reveal-delay-1">
        <div class="service-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="service-title">Self-Assessment</div>
        <p class="service-desc">Complete the 42-indicator SBM checklist aligned with DO 007, s. 2024. Track your
          school's maturity level in real-time.</p>
      </div>

      <div class="service-card reveal reveal-delay-2">
        <div class="service-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path
              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div class="service-title">Automated Reporting</div>
        <p class="service-desc">Generate SIP-ready reports and executive summaries instantly. No more manual
          consolidation of teacher feedback.</p>
      </div>

      <div class="service-card reveal reveal-delay-3">
        <div class="service-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <div class="service-title">ML Suggestions</div>
        <p class="service-desc">Receive AI-powered recommendations for school improvement based on your current
          assessment scores and stakeholder remarks.</p>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════
       CASE STUDIES SECTION
  ══════════════════════════════════ -->
  <section class="section" id="work">
    <div class="work-header">
      <div class="reveal">
        <span class="section-eyebrow">System Impact</span>
        <h2 class="section-heading">Transforming<br><em>school governance</em></h2>
      </div>
    </div>

    <div class="work-grid">

      <div class="case-card reveal reveal-delay-1">
        <div class="case-body">
          <span class="case-tag">Efficiency</span>
          <div class="case-title">Zero Paperwork Assessment</div>
          <p class="case-desc">Moving from manual consolidation to digital submission saves school heads an average of
            15 hours per assessment cycle.</p>
          <div class="case-meta">
            <span class="case-meta-label">Impact:</span>
            <span class="case-result">100% Digital Workflow</span>
          </div>
        </div>
      </div>

      <div class="case-card reveal reveal-delay-2">
        <div class="case-body">
          <span class="case-tag">Compliance</span>
          <div class="case-title">DO 007, s. 2024 Aligned</div>
          <p class="case-desc">Every indicator and dimension is mapped directly to the latest DepEd orders, ensuring
            your school is always audit-ready.</p>
          <div class="case-meta">
            <span class="case-meta-label">Status:</span>
            <span class="case-result">Fully Compliant</span>
          </div>
        </div>
      </div>

      <div class="case-card reveal reveal-delay-3">
        <div class="case-body">
          <span class="case-tag">Intelligence</span>
          <div class="case-title">Data-Driven SIP</div>
          <p class="case-desc">Automatically translate assessment gaps into actionable strategies for your School
            Improvement Plan using our ML engine.</p>
          <div class="case-meta">
            <span class="case-meta-label">Feature:</span>
            <span class="case-result">Smart Recommendations</span>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════
       ABOUT SECTION
  ══════════════════════════════════ -->
  <section class="section" id="about">
    <div class="about-inner">

      <div class="about-image-wrap reveal">
        <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=900&q=80&auto=format&fit=crop"
          alt="The Clearpath team collaborating" loading="lazy" />
      </div>

      <div class="about-content">
        <div class="reveal">
          <span class="section-eyebrow">Our Vision</span>
          <h2 class="section-heading">Empowering schools through<br><em>data-driven governance</em></h2>
        </div>
        <p class="about-body reveal reveal-delay-1">
          The SBM Monitoring System is designed to streamline the self-assessment process for Philippine schools. By
          digitizing DepEd Order No. 007, s. 2024, we enable school heads and stakeholders to focus on what truly
          matters: improving learner outcomes.
        </p>
        <p class="about-body reveal reveal-delay-2">
          Our platform provides a transparent, efficient, and evidence-based approach to school management, ensuring
          that every decision is backed by solid data and aligned with national standards.
        </p>
        <div class="stats-row reveal reveal-delay-3">
          <div class="stat">
            <div class="stat-number"><?= number_format($total_indicators) ?></div>
            <div class="stat-label">Critical Indicators</div>
          </div>
          <div class="stat">
            <div class="stat-number"><?= number_format($total_dimensions) ?></div>
            <div class="stat-label">SBM Dimensions</div>
          </div>
          <div class="stat">
            <div class="stat-number">2,500<span>+</span></div>
            <div class="stat-label">Learners Served</div>
          </div>
          <div class="stat">
            <div class="stat-number"><?= number_format($validated_cycles) ?></div>
            <div class="stat-label">Validated Cycles</div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <script>
    // Scroll-triggered reveal
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    reveals.forEach(el => observer.observe(el));
  </script>

</body>

</html>