<?php
// config/sbm_indicators.php
// All 42 SBM indicators per DepEd Order No. 007, s. 2024
// Role assignments verified against the official indicator matrix (images 1–6).

// ── ROLE KEY ────────────────────────────────────────────────────────────────
// SH_ONLY    → SH / SBM Coordinator only        (no teacher, no external)
// TEACHER    → Teacher only                     (pure teacher-answered)
// SH_TEACHER → SH/SBM Coord + Teacher           (shared)
// SH_EXT     → SH/SBM Coord + External          (no teacher)
// SH_TCH_EXT → SH/SBM Coord + Teacher + External
// TCH_EXT    → Teacher + External               (no SH primary — indicator 4.6)

define('SBM_DIMENSIONS', [
    1 => ['name' => 'Curriculum and Teaching', 'color' => '#2563EB', 'icon' => 'book', 'indicator_count' => 8],
    2 => ['name' => 'Learning Environment', 'color' => '#16A34A', 'icon' => 'home', 'indicator_count' => 10],
    3 => ['name' => 'Leadership', 'color' => '#7C3AED', 'icon' => 'star', 'indicator_count' => 4],
    4 => ['name' => 'Governance and Accountability', 'color' => '#D97706', 'icon' => 'check-circle', 'indicator_count' => 6],
    5 => ['name' => 'Human Resources and Team Development', 'color' => '#DC2626', 'icon' => 'users', 'indicator_count' => 7],
    6 => ['name' => 'Finance and Resource Management and Mobilization', 'color' => '#0D9488', 'icon' => 'dollar-sign', 'indicator_count' => 7],
]);

define('SBM_INDICATORS', [
    // ── DIMENSION 1: Curriculum and Teaching (8 indicators) ──────────────────
    // #1  SH_TEACHER
    [
        'code' => '1.1',
        'dim' => 1,
        'text' => 'Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.',
        'mov' => 'MPS/proficiency data, class records, early language and literacy assessment results',
    ],
    // #2  SH_TEACHER
    [
        'code' => '1.2',
        'dim' => 1,
        'text' => 'Grade 6, 10, and 12 learners achieve the proficiency level in all 21st-century skills and core learning areas in the National Achievement Test (NAT).',
        'mov' => 'NAT results, MPS data, class records',
    ],
    // #3  SH_ONLY
    [
        'code' => '1.3',
        'dim' => 1,
        'text' => 'School-based ALS learners attain certification as elementary and junior high school completers.',
        'mov' => 'ALS completion certificates, enrollment and completion records',
    ],
    // #4  TEACHER only
    [
        'code' => '1.4',
        'dim' => 1,
        'text' => 'Teachers prepare contextualized learning materials responsive to the needs of learners.',
        'mov' => 'Developed contextualized LMs, LRMDS uploads, utilization records',
    ],
    // #5  TEACHER only
    [
        'code' => '1.5',
        'dim' => 1,
        'text' => 'Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.',
        'mov' => 'Remediation program designs, attendance records, monitoring reports',
    ],
    // #6  TEACHER only
    [
        'code' => '1.6',
        'dim' => 1,
        'text' => 'Teachers integrate topics promoting peace and DepEd core values.',
        'mov' => 'Lesson plans, classroom observations, LAC session minutes',
    ],
    // #7  SH_TEACHER
    [
        'code' => '1.7',
        'dim' => 1,
        'text' => 'The school conducts test item analysis to inform its teaching and learning process.',
        'mov' => 'Item analysis reports, action plans based on findings, LAC minutes',
    ],
    // #8  SH_EXT
    [
        'code' => '1.8',
        'dim' => 1,
        'text' => 'The school engages local industries to strengthen its TLE-TVL course offerings.',
        'mov' => 'MOA with industry partners, NC/COC certificates, industry immersion records',
    ],

    // ── DIMENSION 2: Learning Environment (10 indicators) ────────────────────
    // #9  SH_TCH_EXT
    [
        'code' => '2.1',
        'dim' => 2,
        'text' => 'The school has zero bullying incidence.',
        'mov' => 'Anti-bullying policy, incident reports, monitoring logs',
    ],
    // #10 SH_TCH_EXT
    [
        'code' => '2.2',
        'dim' => 2,
        'text' => 'The school has zero child abuse incidence.',
        'mov' => 'CPC records, incident reports, referral documents',
    ],
    // #11 SH_TEACHER
    [
        'code' => '2.3',
        'dim' => 2,
        'text' => 'The school has reduced its drop-out incidence.',
        'mov' => 'Enrollment/completion data, BEIS reports, intervention records',
    ],
    // #12 SH_TCH_EXT
    [
        'code' => '2.4',
        'dim' => 2,
        'text' => 'The school conducts culture-sensitive activities.',
        'mov' => 'Activity programs, photo documentation, feedback forms',
    ],
    // #13 SH_EXT  (NO teacher)
    [
        'code' => '2.5',
        'dim' => 2,
        'text' => 'The school provides access to learning experiences for the disadvantaged, OSYs, and adult learners.',
        'mov' => 'OSY mapping, ALS enrollment records, inclusion program documents',
    ],
    // #14 SH_ONLY
    [
        'code' => '2.6',
        'dim' => 2,
        'text' => 'The school has a functional school-based ALS program.',
        'mov' => 'ALS program design, learner enrollment, completion reports',
    ],
    // #15 SH_ONLY
    [
        'code' => '2.7',
        'dim' => 2,
        'text' => 'The school has a functional child-protection committee.',
        'mov' => 'CPC composition order, meeting minutes, activity reports',
    ],
    // #16 SH_ONLY
    [
        'code' => '2.8',
        'dim' => 2,
        'text' => 'The school has a functional DRRM plan.',
        'mov' => 'DRRM plan, drill documentation, hazard maps',
    ],
    // #17 SH_TEACHER
    [
        'code' => '2.9',
        'dim' => 2,
        'text' => 'The school has a functional support mechanism for mental wellness.',
        'mov' => 'Wellness program design, referral records, accomplishment reports',
    ],
    // #18 SH_EXT  (NO teacher)
    [
        'code' => '2.10',
        'dim' => 2,
        'text' => 'The school has special education- and PWD-friendly facilities.',
        'mov' => 'Accessibility audit, ramp/facility photos, SPED program records',
    ],

    // ── DIMENSION 3: Leadership (4 indicators) ────────────────────────────────
    // #19 SH_ONLY
    [
        'code' => '3.1',
        'dim' => 3,
        'text' => 'The school develops a strategic plan.',
        'mov' => 'SIP/strategic plan document, stakeholder attendance, accomplishment reports',
    ],
    // #20 SH_EXT
    [
        'code' => '3.2',
        'dim' => 3,
        'text' => 'The school has a functional school-community planning team.',
        'mov' => 'Planning team composition, meeting minutes, activity reports',
    ],
    // #21 SH_TEACHER
    [
        'code' => '3.3',
        'dim' => 3,
        'text' => 'The school has a functional Supreme Student Government / Supreme Pupil Government.',
        'mov' => 'SSG/SPG constitution, election records, program accomplishments',
    ],
    // #22 SH_EXT
    [
        'code' => '3.4',
        'dim' => 3,
        'text' => 'The school innovates in its provision of frontline services to stakeholders.',
        'mov' => 'Innovation documentation, feedback/evaluation, impact data',
    ],

    // ── DIMENSION 4: Governance and Accountability (6 indicators) ─────────────
    // #23 SH_ONLY
    [
        'code' => '4.1',
        'dim' => 4,
        'text' => 'The school\'s strategic plan is operationalized through an implementation plan.',
        'mov' => 'Implementation plan, accomplishment reports, M&E records',
    ],
    // #24 SH_EXT
    [
        'code' => '4.2',
        'dim' => 4,
        'text' => 'The school has a functional School Governance Council (SGC).',
        'mov' => 'SGC composition order, meeting minutes, resolutions',
    ],
    // #25 SH_EXT
    [
        'code' => '4.3',
        'dim' => 4,
        'text' => 'The school has a functional Parent-Teacher Association (PTA).',
        'mov' => 'PTA election records, meeting minutes, financial reports',
    ],
    // #26 SH_EXT
    [
        'code' => '4.4',
        'dim' => 4,
        'text' => 'The school collaborates with stakeholders and other schools in strengthening partnerships.',
        'mov' => 'MOA/MOU documents, partnership activity reports, resource contributions',
    ],
    // #27 SH_ONLY
    [
        'code' => '4.5',
        'dim' => 4,
        'text' => 'The school monitors and evaluates its programs, projects, and activities.',
        'mov' => 'M&E plan, monitoring reports, action plans based on findings',
    ],
    // #28 TCH_EXT  (Teacher + External — SH is NOT the primary rater here)
    [
        'code' => '4.6',
        'dim' => 4,
        'text' => 'The school maintains an average satisfactory rating from its internal and external stakeholders.',
        'mov' => 'Stakeholder satisfaction survey results, tabulated data, action plans',
    ],

    // ── DIMENSION 5: Human Resources and Team Development (7 indicators) ──────
    // #29 SH_TEACHER
    [
        'code' => '5.1',
        'dim' => 5,
        'text' => 'School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.',
        'mov' => 'Signed IPCR forms, summary rating sheets, submission records',
    ],
    // #30 SH_ONLY
    [
        'code' => '5.2',
        'dim' => 5,
        'text' => 'The school achieves an average rating of very satisfactory in the office performance commitment and review.',
        'mov' => 'OPCR rating sheets, division evaluation results',
    ],
    // #31 SH_TEACHER
    [
        'code' => '5.3',
        'dim' => 5,
        'text' => 'The school conducts needs-based Learning Action Cells and Learning & Development activities.',
        'mov' => 'LAC session plans, attendance, minutes, action plans, L&D records',
    ],
    // #32 SH_TEACHER
    [
        'code' => '5.4',
        'dim' => 5,
        'text' => 'The school facilitates the promotion and continuous professional development of its personnel.',
        'mov' => 'Training certificates, individual development plans, PDO records',
    ],
    // #33 SH_TEACHER
    [
        'code' => '5.5',
        'dim' => 5,
        'text' => 'The school recognizes and rewards milestone achievements of its personnel.',
        'mov' => 'Recognition program design, awarding documentation, photos',
    ],
    // #34 SH_TEACHER
    [
        'code' => '5.6',
        'dim' => 5,
        'text' => 'The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.',
        'mov' => 'Payroll records, DTR, allowance vouchers, personnel feedback',
    ],
    // #35 SH_TEACHER
    [
        'code' => '5.7',
        'dim' => 5,
        'text' => 'Teacher workload is distributed fairly and equitably.',
        'mov' => 'Teaching load summary, class schedule, assignment orders',
    ],

    // ── DIMENSION 6: Finance and Resource Management and Mobilization (7 indicators) ──
    // #36 SH_ONLY
    [
        'code' => '6.1',
        'dim' => 6,
        'text' => 'The school inspects its infrastructure and facilities.',
        'mov' => 'Facilities inspection report, checklist, photos',
    ],
    // #37 SH_EXT
    [
        'code' => '6.2',
        'dim' => 6,
        'text' => 'The school initiates improvement of its infrastructure and facilities.',
        'mov' => 'Maintenance/improvement plan, work orders, accomplishment reports, photos',
    ],
    // #38 SH_TCH_EXT
    [
        'code' => '6.3',
        'dim' => 6,
        'text' => 'The school has a functional library.',
        'mov' => 'Library inventory, acquisition records, utilization logs',
    ],
    // #39 SH_TCH_EXT
    [
        'code' => '6.4',
        'dim' => 6,
        'text' => 'The school has functional water, electricity, and internet facilities.',
        'mov' => 'Utility bills, repair records, functionality assessment',
    ],
    // #40 SH_TCH_EXT
    [
        'code' => '6.5',
        'dim' => 6,
        'text' => 'The school has a functional computer laboratory/classroom.',
        'mov' => 'Lab inventory, equipment condition report, utilization records',
    ],
    // #41 SH_ONLY
    [
        'code' => '6.6',
        'dim' => 6,
        'text' => 'The school achieves a 75–100% utilization rate of its Maintenance and Other Operating Expenses (MOOE).',
        'mov' => 'MOOE liquidation reports, utilization matrix, COB vs. actual',
    ],
    // #42 SH_ONLY
    [
        'code' => '6.7',
        'dim' => 6,
        'text' => 'The school liquidates 100% of its utilized MOOE.',
        'mov' => 'Liquidation reports, submission acknowledgments, COA records',
    ],
]);

// ── Rating scale ─────────────────────────────────────────────────────────────
define('SBM_RATINGS', [
    1 => ['label' => 'Not yet Manifested', 'short' => 'NYM', 'color' => '#DC2626', 'bg' => '#FEE2E2'],
    2 => ['label' => 'Rarely Manifested', 'short' => 'RM', 'color' => '#D97706', 'bg' => '#FEF3C7'],
    3 => ['label' => 'Frequently Manifested', 'short' => 'FM', 'color' => '#2563EB', 'bg' => '#DBEAFE'],
    4 => ['label' => 'Always manifested', 'short' => 'AM', 'color' => '#16A34A', 'bg' => '#DCFCE7'],
]);

// ── Maturity levels ───────────────────────────────────────────────────────────
define('SBM_MATURITY', [
    ['min' => 0, 'max' => 25, 'level' => 1, 'label' => 'Beginning', 'color' => '#DC2626', 'bg' => '#FEE2E2'],
    ['min' => 26, 'max' => 50, 'level' => 2, 'label' => 'Developing', 'color' => '#D97706', 'bg' => '#FEF3C7'],
    ['min' => 51, 'max' => 75, 'level' => 3, 'label' => 'Maturing', 'color' => '#2563EB', 'bg' => '#DBEAFE'],
    ['min' => 76, 'max' => 100, 'level' => 4, 'label' => 'Advanced', 'color' => '#16A34A', 'bg' => '#DCFCE7'],
]);

// ════════════════════════════════════════════════════════════════════════════
// ROLE ARRAYS  — derived strictly from the indicator matrix images
// Each code appears in AT MOST ONE of the arrays below (no duplicates).
// ════════════════════════════════════════════════════════════════════════════

// ── Indicators answered by TEACHER ONLY (not SH, not External) ──────────────
define('TEACHER_ONLY_CODES', [
    '1.4',   // Contextualized LMs
    '1.5',   // Remediation activities
    '1.6',   // Peace / DepEd values
]);

// ── Indicators shared by SH/SBM Coord AND Teacher (SH+Teacher ONLY — no External) ──
// SH rates these in the SH portal; teachers rate the same in the teacher portal.
// The final score is the average of both pools.
// Note: 2.1, 2.2, 2.4, 6.3, 6.4, 6.5 also involve External — see SH_TCH_EXT_CODES.
define('SH_TEACHER_CODES', [
    '1.1',   // Grade 3 proficiency
    '1.2',   // Grade 6/10/12 NAT
    '1.7',   // Test item analysis
    '2.3',   // Reduced dropout
    '2.9',   // Mental wellness
    '3.3',   // SSG/SPG
    '5.1',   // IPCR very satisfactory
    '5.3',   // LAC / L&D
    '5.4',   // CPD promotion
    '5.5',   // Recognition / rewards
    '5.6',   // Correct salaries
    '5.7',   // Fair workload
]);

// ── Indicators answered by SH/SBM Coord AND External Stakeholder (no Teacher) ─
define('SH_EXT_CODES', [
    '1.8',   // TLE-TVL industry engagement
    '2.5',   // Disadvantaged / OSYs / adult learners
    '2.10',  // PWD-friendly facilities
    '3.2',   // School-community planning team
    '3.4',   // Frontline services innovation
    '4.2',   // SGC
    '4.3',   // PTA
    '4.4',   // Stakeholder partnerships
    '6.2',   // Infrastructure improvement
]);

// ── Indicators answered by SH/SBM Coord, Teacher, AND External Stakeholder ───
define('SH_TCH_EXT_CODES', [
    '2.1',   // Zero bullying
    '2.2',   // Zero child abuse
    '2.4',   // Culture-sensitive activities
    '6.3',   // Functional library
    '6.4',   // Water/electric/internet
    '6.5',   // Computer lab
]);

// ── Indicators answered by Teacher AND External (SH is NOT primary rater) ────
define('TCH_EXT_CODES', [
    '4.6',   // Stakeholder satisfaction rating
]);

// ── Indicators answered by SH/SBM Coord ONLY ─────────────────────────────────
define('SH_ONLY_INDICATOR_CODES', [
    '1.3',   // ALS certification
    '2.6',   // ALS program
    '2.7',   // Child-protection committee
    '2.8',   // DRRM plan
    '3.1',   // Strategic plan
    '4.1',   // Implementation plan
    '4.5',   // Monitor and evaluate
    '5.2',   // OPCR very satisfactory
    '6.1',   // Infrastructure inspection
    '6.6',   // 75–100% MOOE utilization
    '6.7',   // 100% MOOE liquidation
]);

// ── Consolidated: ALL codes where Teachers are involved ──────────────────────
// Used for access-control checks (teacher portal, recompute logic).
// = TEACHER_ONLY + SH_TEACHER + SH_TCH_EXT + TCH_EXT
define('TEACHER_INDICATOR_CODES', array_unique(array_merge(
    TEACHER_ONLY_CODES,
    SH_TEACHER_CODES,
    SH_TCH_EXT_CODES,
    TCH_EXT_CODES,
)));

// ── Consolidated: ALL codes where External Stakeholders are involved ──────────
define('STAKEHOLDER_INDICATOR_CODES', array_unique(array_merge(
    SH_EXT_CODES,
    SH_TCH_EXT_CODES,
    TCH_EXT_CODES,
)));

// ── Helper: which codes the SH portal renders as "teacher boxes" (read-only) ──
// These are codes where teachers are involved but SH also has a stake.
// Pure TEACHER_ONLY codes are invisible in the SH portal entirely.
define('SH_SEES_TEACHER_CODES', array_unique(array_merge(
    SH_TEACHER_CODES,
    SH_TCH_EXT_CODES,
)));

// ── Helper: codes the SH portal rates directly (SH has a rating input) ────────
// = SH_ONLY + SH_TEACHER + SH_EXT + SH_TCH_EXT
// Notably excludes TEACHER_ONLY_CODES and TCH_EXT_CODES (4.6).
define('SH_RATEABLE_CODES', array_unique(array_merge(
    SH_ONLY_INDICATOR_CODES,
    SH_TEACHER_CODES,
    SH_EXT_CODES,
    SH_TCH_EXT_CODES,
)));

// ── Helpers ───────────────────────────────────────────────────────────────────
function sbmRatingLabel(int $r): string
{
    return SBM_RATINGS[$r]['label'] ?? '—';
}

function sbmMaturityLevel(float $pct): array
{
    foreach (SBM_MATURITY as $m) {
        if ($pct >= $m['min'] && $pct <= $m['max']) {
            return $m;
        }
    }
    return SBM_MATURITY[array_key_last(SBM_MATURITY)];
}