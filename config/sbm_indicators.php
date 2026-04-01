<?php
// config/sbm_indicators.php
// All 42 SBM indicators per DepEd Order No. 007, s. 2024
// Run seed_sbm.php once to insert these into the database.

define('SBM_DIMENSIONS', [
    1 => ['name' => 'Curriculum and Teaching', 'color' => '#2563EB', 'icon' => 'book', 'indicator_count' => 8],
    2 => ['name' => 'Learning Environment', 'color' => '#16A34A', 'icon' => 'home', 'indicator_count' => 10],
    3 => ['name' => 'Leadership', 'color' => '#7C3AED', 'icon' => 'star', 'indicator_count' => 3],
    4 => ['name' => 'Governance and Accountability', 'color' => '#D97706', 'icon' => 'check-circle', 'indicator_count' => 7],
    5 => ['name' => 'Human Resources and Team Development', 'color' => '#DC2626', 'icon' => 'users', 'indicator_count' => 7],
    6 => ['name' => 'Finance and Resource Management and Mobilization', 'color' => '#0D9488', 'icon' => 'dollar-sign', 'indicator_count' => 7],
]);

define('SBM_INDICATORS', [
    // ── DIMENSION 1: Curriculum and Teaching (8 indicators) ──────────────────
    [
        'code' => '1.1',
        'dim' => 1,
        'text' => 'Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.',
        'mov' => 'MPS/proficiency data, class records, early language and literacy assessment results'
    ],
    [
        'code' => '1.2',
        'dim' => 1,
        'text' => 'Grade 6, 10, and 12 learners achieve the proficiency level in all 21st century skills and core learning areas in the National Achievement Test (NAT).',
        'mov' => 'NAT results, MPS data, class records'
    ],
    [
        'code' => '1.3',
        'dim' => 1,
        'text' => 'School-based ALS learners attain certification as elementary and junior high school completers.',
        'mov' => 'ALS completion certificates, enrollment and completion records'
    ],
    [
        'code' => '1.4',
        'dim' => 1,
        'text' => 'Teachers prepare contextualized learning materials responsive to the needs of learners.',
        'mov' => 'Developed contextualized LMs, LRMDS uploads, utilization records'
    ],
    [
        'code' => '1.5',
        'dim' => 1,
        'text' => 'Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.',
        'mov' => 'Remediation program designs, attendance records, monitoring reports'
    ],
    [
        'code' => '1.6',
        'dim' => 1,
        'text' => 'Teachers integrate topics promoting peace and DepEd core values.',
        'mov' => 'Lesson plans, classroom observations, LAC session minutes'
    ],
    [
        'code' => '1.7',
        'dim' => 1,
        'text' => 'The school conducts test item analysis to inform its teaching and learning process.',
        'mov' => 'Item analysis reports, action plans based on findings, LAC minutes'
    ],
    [
        'code' => '1.8',
        'dim' => 1,
        'text' => 'The school engages local industries to strengthen its TLE-TVL course offerings.',
        'mov' => 'MOA with industry partners, NC/COC certificates, industry immersion records'
    ],

    // ── DIMENSION 2: Learning Environment (10 indicators) ────────────────────
    [
        'code' => '2.1',
        'dim' => 2,
        'text' => 'The school has zero bullying incidence.',
        'mov' => 'Anti-bullying policy, incident reports, monitoring logs'
    ],
    [
        'code' => '2.2',
        'dim' => 2,
        'text' => 'The school has zero child abuse incidence.',
        'mov' => 'CPC records, incident reports, referral documents'
    ],
    [
        'code' => '2.3',
        'dim' => 2,
        'text' => 'The school has reduced its drop-out incidence.',
        'mov' => 'Enrollment/completion data, BEIS reports, intervention records'
    ],
    [
        'code' => '2.4',
        'dim' => 2,
        'text' => 'The school conducts culture-sensitive activities.',
        'mov' => 'Activity programs, photo documentation, feedback forms'
    ],
    [
        'code' => '2.5',
        'dim' => 2,
        'text' => 'The school provides access to learning experiences for the disadvantaged, OSYs, and adult learners.',
        'mov' => 'OSY mapping, ALS enrollment records, inclusion program documents'
    ],
    [
        'code' => '2.6',
        'dim' => 2,
        'text' => 'The school has a functional school-based ALS program.',
        'mov' => 'ALS program design, learner enrollment, completion reports'
    ],
    [
        'code' => '2.7',
        'dim' => 2,
        'text' => 'The school has a functional child-protection committee.',
        'mov' => 'CPC composition order, meeting minutes, activity reports'
    ],
    [
        'code' => '2.8',
        'dim' => 2,
        'text' => 'The school has a functional DRRM plan.',
        'mov' => 'DRRM plan, drill documentation, hazard maps'
    ],
    [
        'code' => '2.9',
        'dim' => 2,
        'text' => 'The school has a functional support mechanism for mental wellness.',
        'mov' => 'Wellness program design, referral records, accomplishment reports'
    ],
    [
        'code' => '2.10',
        'dim' => 2,
        'text' => 'The school has special education- and PWD-friendly facilities.',
        'mov' => 'Accessibility audit, ramp/facility photos, SPED program records'
    ],

    // ── DIMENSION 3: Leadership (3 indicators) ────────────────────────────────
    [
        'code' => '3.1',
        'dim' => 3,
        'text' => 'The school develops a strategic plan.',
        'mov' => 'SIP/strategic plan document, stakeholder attendance, accomplishment reports'
    ],
    [
        'code' => '3.2',
        'dim' => 3,
        'text' => 'The school has a functional school-community planning team.',
        'mov' => 'Planning team composition, meeting minutes, activity reports'
    ],
    [
        'code' => '3.3',
        'dim' => 3,
        'text' => 'The school has a functional Supreme Student Government/Supreme Pupil Government.',
        'mov' => 'SSG/SPG constitution, election records, program accomplishments'
    ],

    // ── DIMENSION 4: Governance and Accountability (7 indicators) ─────────────
    [
        'code' => '4.1',
        'dim' => 4,
        'text' => 'The school innovates in its provision of frontline services to stakeholders.',
        'mov' => 'Innovation documentation, feedback/evaluation, impact data'
    ],
    [
        'code' => '4.2',
        'dim' => 4,
        'text' => 'The school\'s strategic plan is operationalized through an implementation plan.',
        'mov' => 'Implementation plan, accomplishment reports, M&E records'
    ],
    [
        'code' => '4.3',
        'dim' => 4,
        'text' => 'The school has a functional School Governance Council (SGC).',
        'mov' => 'SGC composition order, meeting minutes, resolutions'
    ],
    [
        'code' => '4.4',
        'dim' => 4,
        'text' => 'The school has a functional Parent-Teacher Association (PTA).',
        'mov' => 'PTA election records, meeting minutes, financial reports'
    ],
    [
        'code' => '4.5',
        'dim' => 4,
        'text' => 'The school collaborates with stakeholders and other schools in strengthening partnerships.',
        'mov' => 'MOA/MOU documents, partnership activity reports, resource contributions'
    ],
    [
        'code' => '4.6',
        'dim' => 4,
        'text' => 'The school monitors and evaluates its programs, projects, and activities.',
        'mov' => 'M&E plan, monitoring reports, action plans based on findings'
    ],
    [
        'code' => '4.7',
        'dim' => 4,
        'text' => 'The school maintains an average rating of satisfactory from its internal and external stakeholders.',
        'mov' => 'Stakeholder satisfaction survey results, tabulated data, action plans'
    ],

    // ── DIMENSION 5: Human Resources and Team Development (7 indicators) ──────
    [
        'code' => '5.1',
        'dim' => 5,
        'text' => 'School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.',
        'mov' => 'Signed IPCR forms, summary rating sheets, submission records'
    ],
    [
        'code' => '5.2',
        'dim' => 5,
        'text' => 'The school achieves an average rating of very satisfactory in the office performance commitment and review.',
        'mov' => 'OPCR rating sheets, division evaluation results'
    ],
    [
        'code' => '5.3',
        'dim' => 5,
        'text' => 'The school conducts needs-based Learning Action Cells and Learning & Development activities.',
        'mov' => 'LAC session plans, attendance, minutes, action plans, L&D records'
    ],
    [
        'code' => '5.4',
        'dim' => 5,
        'text' => 'The school facilitates the promotion and continuous professional development of its personnel.',
        'mov' => 'Training certificates, individual development plans, PDO records'
    ],
    [
        'code' => '5.5',
        'dim' => 5,
        'text' => 'The school recognizes and rewards milestone achievements of its personnel.',
        'mov' => 'Recognition program design, awarding documentation, photos'
    ],
    [
        'code' => '5.6',
        'dim' => 5,
        'text' => 'The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.',
        'mov' => 'Payroll records, DTR, allowance vouchers, personnel feedback'
    ],
    [
        'code' => '5.7',
        'dim' => 5,
        'text' => 'Teacher workload is distributed fairly and equitably.',
        'mov' => 'Teaching load summary, class schedule, assignment orders'
    ],

    // ── DIMENSION 6: Finance and Resource Management and Mobilization (7 indicators) ──
    [
        'code' => '6.1',
        'dim' => 6,
        'text' => 'The school inspects its infrastructure and facilities.',
        'mov' => 'Facilities inspection report, checklist, photos'
    ],
    [
        'code' => '6.2',
        'dim' => 6,
        'text' => 'The school initiates improvement of its infrastructure and facilities.',
        'mov' => 'Maintenance/improvement plan, work orders, accomplishment reports, photos'
    ],
    [
        'code' => '6.3',
        'dim' => 6,
        'text' => 'The school has a functional library.',
        'mov' => 'Library inventory, acquisition records, utilization logs'
    ],
    [
        'code' => '6.4',
        'dim' => 6,
        'text' => 'The school has functional water, electricity, and internet facilities.',
        'mov' => 'Utility bills, repair records, functionality assessment'
    ],
    [
        'code' => '6.5',
        'dim' => 6,
        'text' => 'The school has a functional computer laboratory/classroom.',
        'mov' => 'Lab inventory, equipment condition report, utilization records'
    ],
    [
        'code' => '6.6',
        'dim' => 6,
        'text' => 'The school achieves a 75–100% utilization rate of its Maintenance and Other Operating Expenses (MOOE).',
        'mov' => 'MOOE liquidation reports, utilization matrix, COB vs. actual'
    ],
    [
        'code' => '6.7',
        'dim' => 6,
        'text' => 'The school liquidates 100% of its utilized MOOE.',
        'mov' => 'Liquidation reports, submission acknowledgments, COA records'
    ],
]);

// Rating scale labels
define('SBM_RATINGS', [
    1 => ['label' => 'Not Yet Manifested', 'short' => 'NYM', 'color' => '#DC2626', 'bg' => '#FEE2E2'],
    2 => ['label' => 'Emerging', 'short' => 'EM', 'color' => '#D97706', 'bg' => '#FEF3C7'],
    3 => ['label' => 'Developing', 'short' => 'DEV', 'color' => '#2563EB', 'bg' => '#DBEAFE'],
    4 => ['label' => 'Always Manifested', 'short' => 'AM', 'color' => '#16A34A', 'bg' => '#DCFCE7'],
]);

// Maturity levels based on overall percentage score
define('SBM_MATURITY', [
    ['min' => 0, 'max' => 25, 'level' => 1, 'label' => 'Beginning', 'color' => '#DC2626', 'bg' => '#FEE2E2'],
    ['min' => 26, 'max' => 50, 'level' => 2, 'label' => 'Developing', 'color' => '#D97706', 'bg' => '#FEF3C7'],
    ['min' => 51, 'max' => 75, 'level' => 3, 'label' => 'Maturing', 'color' => '#2563EB', 'bg' => '#DBEAFE'],
    ['min' => 76, 'max' => 100, 'level' => 4, 'label' => 'Advanced', 'color' => '#16A34A', 'bg' => '#DCFCE7'],
]);

// ── Indicators that only Teachers can answer ──────────────────
// Indicators Teachers can answer (Teacher-only + Shared SH+Teacher)
define('TEACHER_INDICATOR_CODES', [
    // Dimension 1 — Teacher only
    '1.4',
    '1.5',
    '1.6',
    // Dimension 1 — Shared (SH + Teacher)
    '1.1',
    '1.2',
    '1.7',
    // Dimension 2 — Shared (SH + Teacher)
    '2.3',
    '2.4',
    '2.9',
    // Dimension 2 — Shared (SH + Teacher + External)
    '2.1',
    '2.2',
    // Dimension 3 — Shared (SH + Teacher)
    '3.3',
    // Dimension 4 — Shared (SH + Teacher)
    '4.2',
    // Dimension 5 — Shared (SH + Teacher)
    '5.1',
    '5.3',
    '5.4',
    '5.5',
    '5.6',
    '5.7',
    // Dimension 6 — Shared (SH + Teacher + External)
    '6.3',
    '6.4',
    '6.5',
    // Dimension 6 — Shared (SH + Teacher)
    '6.1',
    '6.2',
]);

// Indicators External Stakeholders can answer
define('STAKEHOLDER_INDICATOR_CODES', [
    // Dimension 1 — Shared (SH + External)
    '1.8',
    // Dimension 2 — Shared (SH + External)
    '2.4',
    '2.5',
    // Dimension 2 — Shared (SH + Teacher + External)
    '2.1',
    '2.2',
    // Dimension 3 — Shared (SH + External)
    '3.2',
    // Dimension 3 — Shared (SH + Teacher + External) — note: image 3 shows 3.4 as SH+External, mapped to code 4.1
    '4.1',
    // Dimension 4 — Shared (SH + External)
    '4.3',
    '4.4',
    '4.5',
    // Dimension 6 — Shared (SH + Teacher + External)
    '6.3',
    '6.4',
    '6.5',
]);

// Indicators that ONLY SH/SBM Coordinator can answer (no teacher, no stakeholder)
define('SH_ONLY_INDICATOR_CODES', [
    // Dimension 1
    '1.3',
    // Dimension 2
    '2.6',
    '2.7',
    '2.8',
    // Dimension 3
    '3.1',
    // Dimension 4
    '4.2',
    '4.6',
    '4.7',
    // Dimension 5
    '5.2',
    // Dimension 6
    '6.6',
    '6.7',
]);

function sbmRatingLabel(int $r): string
{
    return SBM_RATINGS[$r]['label'] ?? '—';
}
?>