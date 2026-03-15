<?php
// config/sbm_indicators.php
// All 42 SBM indicators per DepEd Order No. 007, s. 2024
// Run seed_sbm.php once to insert these into the database.

define('SBM_DIMENSIONS', [
    1 => ['name' => 'Curriculum and Teaching',              'color' => '#2563EB', 'icon' => 'book',        'indicator_count' => 8],
    2 => ['name' => 'Learning Environment',                 'color' => '#16A34A', 'icon' => 'home',        'indicator_count' => 10],
    3 => ['name' => 'Leadership and Governance',            'color' => '#7C3AED', 'icon' => 'star',        'indicator_count' => 4],
    4 => ['name' => 'Accountability and Continuous Improvement','color'=>'#D97706','icon'=>'check-circle', 'indicator_count' => 6],
    5 => ['name' => 'Human Resource Development',           'color' => '#DC2626', 'icon' => 'users',       'indicator_count' => 7],
    6 => ['name' => 'Finance and Resource Management',      'color' => '#0D9488', 'icon' => 'dollar-sign', 'indicator_count' => 7],
]);

define('SBM_INDICATORS', [
    // ── DIMENSION 1: Curriculum and Teaching (8 indicators) ──────────────────
    ['code'=>'1.1','dim'=>1,'text'=>'Learner proficiency rate in Grade 3 (Literacy and Numeracy) meets or exceeds the national target.',
     'mov'=>'MPS/proficiency data, class records, assessment results'],
    ['code'=>'1.2','dim'=>1,'text'=>'Learner proficiency rate in Grade 6 meets or exceeds the national target.',
     'mov'=>'MPS/proficiency data, NAT results, class records'],
    ['code'=>'1.3','dim'=>1,'text'=>'Learner proficiency rate in Grade 10 meets or exceeds the national target.',
     'mov'=>'NAT/quarterly assessment results, class records'],
    ['code'=>'1.4','dim'=>1,'text'=>'Learner proficiency rate in Grade 12 or ALS completion rate meets or exceeds the national target.',
     'mov'=>'NCAE results, ALS completion certificates, enrollment data'],
    ['code'=>'1.5','dim'=>1,'text'=>'Results of NAT/PEPT/ALS A&E are analyzed and used to improve instructional programs.',
     'mov'=>'Item analysis reports, LAC session minutes, action plans'],
    ['code'=>'1.6','dim'=>1,'text'=>'Contextualized and localized learning materials (LM) are developed and used by teachers.',
     'mov'=>'Developed LMs, LRMDS uploads, utilization records'],
    ['code'=>'1.7','dim'=>1,'text'=>'Remediation, enhancement, and intervention programs are implemented for at-risk learners.',
     'mov'=>'Program designs, attendance records, monitoring reports'],
    ['code'=>'1.8','dim'=>1,'text'=>'TLE/TVL programs have active industry partnerships and produce certified graduates.',
     'mov'=>'MOA with industry partners, NC/COC certificates, industry immersion records'],

    // ── DIMENSION 2: Learning Environment (10 indicators) ────────────────────
    ['code'=>'2.1','dim'=>2,'text'=>'The school has a zero-bullying policy that is implemented, monitored, and updated regularly.',
     'mov'=>'Anti-bullying policy, incident reports, monitoring logs'],
    ['code'=>'2.2','dim'=>2,'text'=>'Dropout rate is within the national target, with active early warning and intervention systems.',
     'mov'=>'Enrollment/completion data, BEIS reports, intervention records'],
    ['code'=>'2.3','dim'=>2,'text'=>'Out-of-School Youth (OSY) re-entry programs and ALS are actively implemented.',
     'mov'=>'OSY mapping, ALS enrollment records, completion reports'],
    ['code'=>'2.4','dim'=>2,'text'=>'School activities are culture-sensitive, inclusive, and respectful of learner diversity.',
     'mov'=>'Activity programs, photo documentation, feedback forms'],
    ['code'=>'2.5','dim'=>2,'text'=>'The Child Protection Committee (CPC) is organized, functional, and conducts regular activities.',
     'mov'=>'CPC composition order, meeting minutes, activity reports'],
    ['code'=>'2.6','dim'=>2,'text'=>'A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.',
     'mov'=>'DRRM plan, drill documentation, hazard maps'],
    ['code'=>'2.7','dim'=>2,'text'=>'Mental wellness programs for learners are implemented and monitored.',
     'mov'=>'Wellness program design, referral records, accomplishment reports'],
    ['code'=>'2.8','dim'=>2,'text'=>'School facilities are accessible for learners with disabilities (SPED/PWD compliance).',
     'mov'=>'Accessibility audit, ramp/facility photos, SPED program records'],
    ['code'=>'2.9','dim'=>2,'text'=>'Safe school environment audit is conducted and findings are addressed.',
     'mov'=>'Safety audit checklist, action plans, repair/improvement records'],
    ['code'=>'2.10','dim'=>2,'text'=>'Learners actively participate in school governance through SSG/SPG and other bodies.',
     'mov'=>'SSG/SPG election records, meeting minutes, program reports'],

    // ── DIMENSION 3: Leadership and Governance (4 indicators) ────────────────
    ['code'=>'3.1','dim'=>3,'text'=>'The School Improvement Plan (SIP) is developed collaboratively with all stakeholders and implemented.',
     'mov'=>'SIP document, stakeholder attendance, accomplishment reports'],
    ['code'=>'3.2','dim'=>3,'text'=>'A school-community planning team is established and functional.',
     'mov'=>'Planning team composition, meeting minutes, activity reports'],
    ['code'=>'3.3','dim'=>3,'text'=>'SSG/SPG is organized, trained, and actively implements programs.',
     'mov'=>'SSG/SPG constitution, election records, program accomplishments'],
    ['code'=>'3.4','dim'=>3,'text'=>'The school head implements innovations in frontline service delivery.',
     'mov'=>'Innovation documentation, feedback/evaluation, impact data'],

    // ── DIMENSION 4: Accountability and Continuous Improvement (6 indicators) ─
    ['code'=>'4.1','dim'=>4,'text'=>'School Governance Council (SGC) records are complete, updated, and actions are documented.',
     'mov'=>'SGC composition order, meeting minutes, resolutions'],
    ['code'=>'4.2','dim'=>4,'text'=>'PTA is organized and actively engaged in school planning and monitoring.',
     'mov'=>'PTA election records, meeting minutes, financial reports'],
    ['code'=>'4.3','dim'=>4,'text'=>'Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.',
     'mov'=>'MOA/MOU documents, partnership activity reports, resource contributions'],
    ['code'=>'4.4','dim'=>4,'text'=>'Monitoring and evaluation of school programs is conducted regularly with documented results.',
     'mov'=>'M&E plan, monitoring reports, action plans based on findings'],
    ['code'=>'4.5','dim'=>4,'text'=>'Stakeholder satisfaction survey is conducted and results are used for improvement.',
     'mov'=>'Survey instrument, tabulated results, action plans'],
    ['code'=>'4.6','dim'=>4,'text'=>'Transparency board and public financial disclosures are updated and accessible.',
     'mov'=>'Transparency board photos, disclosure documents, posting records'],

    // ── DIMENSION 5: Human Resource Development (7 indicators) ──────────────
    ['code'=>'5.1','dim'=>5,'text'=>'All teaching and non-teaching personnel accomplish IPCR/OPCR on time.',
     'mov'=>'Signed IPCR/OPCR forms, summary rating sheets, submission records'],
    ['code'=>'5.2','dim'=>5,'text'=>'Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.',
     'mov'=>'LAC session plan, attendance, minutes, action plans'],
    ['code'=>'5.3','dim'=>5,'text'=>'Teachers participate in professional development activities (trainings, seminars, scholarships).',
     'mov'=>'Training certificates, individual development plans, PDO records'],
    ['code'=>'5.4','dim'=>5,'text'=>'Employee recognition programs are implemented to motivate and reward outstanding performance.',
     'mov'=>'Recognition program design, awarding documentation, photos'],
    ['code'=>'5.5','dim'=>5,'text'=>'Teacher workload is within prescribed limits and fairly distributed.',
     'mov'=>'Teaching load summary, class schedule, assignment orders'],
    ['code'=>'5.6','dim'=>5,'text'=>'HR development programs for non-teaching staff are implemented.',
     'mov'=>'Capacity building plans, training records, accomplishment reports'],
    ['code'=>'5.7','dim'=>5,'text'=>'Succession planning and talent management practices are in place.',
     'mov'=>'Succession plan document, mentoring records, talent inventory'],

    // ── DIMENSION 6: Finance and Resource Management (7 indicators) ──────────
    ['code'=>'6.1','dim'=>6,'text'=>'School facilities inventory is updated and submitted on time.',
     'mov'=>'Facilities inventory form, submission acknowledgment, photos'],
    ['code'=>'6.2','dim'=>6,'text'=>'Infrastructure maintenance plan is implemented and documented.',
     'mov'=>'Maintenance plan, work orders, accomplishment reports, photos'],
    ['code'=>'6.3','dim'=>6,'text'=>'Water, electricity, and internet utilities are functional and adequate.',
     'mov'=>'Utility bills, repair records, functionality assessment'],
    ['code'=>'6.4','dim'=>6,'text'=>'Library resources are adequate, updated, and accessible to all learners.',
     'mov'=>'Library inventory, acquisition records, utilization logs'],
    ['code'=>'6.5','dim'=>6,'text'=>'Laboratory equipment is functional, adequate, and used for instruction.',
     'mov'=>'Lab inventory, equipment condition report, utilization records'],
    ['code'=>'6.6','dim'=>6,'text'=>'MOOE utilization rate reaches 100% with proper documentation.',
     'mov'=>'MOOE liquidation reports, utilization matrix, COB vs. actual'],
    ['code'=>'6.7','dim'=>6,'text'=>'Liquidation reports are submitted on time and complete.',
     'mov'=>'Liquidation reports, submission acknowledgments, COA records'],
]);

// Rating scale labels
define('SBM_RATINGS', [
    1 => ['label' => 'Not Yet Manifested',   'short' => 'NYM', 'color' => '#DC2626', 'bg' => '#FEE2E2'],
    2 => ['label' => 'Emerging',             'short' => 'EM',  'color' => '#D97706', 'bg' => '#FEF3C7'],
    3 => ['label' => 'Developing',           'short' => 'DEV', 'color' => '#2563EB', 'bg' => '#DBEAFE'],
    4 => ['label' => 'Always Manifested',    'short' => 'AM',  'color' => '#16A34A', 'bg' => '#DCFCE7'],
]);

// Maturity levels based on overall percentage score
define('SBM_MATURITY', [
    ['min'=>0,  'max'=>25,  'level'=>1, 'label'=>'Beginning',  'color'=>'#DC2626', 'bg'=>'#FEE2E2'],
    ['min'=>26, 'max'=>50,  'level'=>2, 'label'=>'Developing', 'color'=>'#D97706', 'bg'=>'#FEF3C7'],
    ['min'=>51, 'max'=>75,  'level'=>3, 'label'=>'Maturing',   'color'=>'#2563EB', 'bg'=>'#DBEAFE'],
    ['min'=>76, 'max'=>100, 'level'=>4, 'label'=>'Advanced',   'color'=>'#16A34A', 'bg'=>'#DCFCE7'],
]);

// ── Indicators that only Teachers can answer ──────────────────
define('TEACHER_INDICATOR_CODES', [
    '1.4','1.5','1.6','1.7','1.8',   // Dimension 1
    '2.1','2.2','2.3','2.4','2.7', '2.10',   // Dimension 2
    '3.1','3.2','3.3','3.4',         // Dimension 3
    '4.1',                            // Dimension 4
    '5.1','5.2','5.3','5.4',         // Dimension 5
    '5.5','5.6','5.7',
    '6.1','6.2','6.3','6.4','6.5',  // Dimension 6
]);

define('STAKEHOLDER_INDICATOR_CODES', [
    '1.8',                                          // Dimension 1
    '2.1','2.2','2.3','2.4','2.5','2.6','2.8',     // Dimension 2
    '3.1','3.2','3.4',                              // Dimension 3
    '4.1','4.2','4.3','4.4',                        // Dimension 4
    '6.1','6.2','6.3','6.4','6.5','6.6','6.7',     // Dimension 6
]);

function sbmRatingLabel(int $r): string {
    return SBM_RATINGS[$r]['label'] ?? '—';
}
?>
