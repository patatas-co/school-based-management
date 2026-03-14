-- ============================================================
-- SBM Monitoring System — Complete Database Schema (Updated)
-- DepEd Order No. 007, s. 2024
-- Version 2.0 — Includes all missing tables, fixed maturity
-- levels, and performance indexes
-- Run this SQL in phpMyAdmin on your sbm_db database
-- ============================================================

CREATE DATABASE IF NOT EXISTS sbm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sbm_db;

-- ============================================================
-- CORE TABLES
-- ============================================================

-- ── REGIONS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS regions (
    region_id   INT PRIMARY KEY AUTO_INCREMENT,
    region_name VARCHAR(100) NOT NULL,
    region_code VARCHAR(20)
);

-- ── DIVISIONS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS divisions (
    division_id   INT PRIMARY KEY AUTO_INCREMENT,
    region_id     INT NOT NULL,
    division_name VARCHAR(120) NOT NULL,
    division_code VARCHAR(20),
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE
);

-- ── SCHOOLS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS schools (
    school_id        INT PRIMARY KEY AUTO_INCREMENT,
    division_id      INT NOT NULL,
    school_name      VARCHAR(200) NOT NULL,
    school_id_deped  VARCHAR(20) UNIQUE,
    address          TEXT,
    classification   ENUM('ES','JHS','SHS','IS','ALS') NOT NULL DEFAULT 'JHS',
    school_head_name VARCHAR(120),
    contact_no       VARCHAR(20),
    email            VARCHAR(120),
    total_enrollment INT DEFAULT 0,
    total_teachers   INT DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(division_id) ON DELETE CASCADE
);

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    user_id     INT PRIMARY KEY AUTO_INCREMENT,
    username    VARCHAR(60) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    email       VARCHAR(120) UNIQUE NOT NULL,
    full_name   VARCHAR(120) NOT NULL,
    role        ENUM('admin','school_head','teacher','sdo','ro') NOT NULL DEFAULT 'teacher',
    status      ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    school_id   INT NULL,
    division_id INT NULL,
    region_id   INT NULL,
    last_login  DATETIME NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(school_id) ON DELETE SET NULL
);

-- Default admin account (password: Admin@1234)
INSERT INTO users (username, password, email, full_name, role, status)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@sbm.edu.ph',
    'System Administrator',
    'admin',
    'active'
)
ON DUPLICATE KEY UPDATE username = username;

-- ── SCHOOL YEARS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS school_years (
    sy_id      INT PRIMARY KEY AUTO_INCREMENT,
    label      VARCHAR(20) NOT NULL,
    is_current TINYINT DEFAULT 0,
    date_start DATE,
    date_end   DATE
);

INSERT INTO school_years (label, is_current, date_start, date_end)
VALUES ('2024-2025', 1, '2024-06-03', '2025-04-04')
ON DUPLICATE KEY UPDATE label = label;

-- ============================================================
-- SBM DIMENSIONS & INDICATORS
-- ============================================================

-- ── SBM DIMENSIONS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sbm_dimensions (
    dimension_id    INT PRIMARY KEY AUTO_INCREMENT,
    dimension_no    TINYINT NOT NULL,
    dimension_name  VARCHAR(120) NOT NULL,
    color_hex       VARCHAR(10) DEFAULT '#16A34A',
    icon            VARCHAR(40) DEFAULT 'star',
    indicator_count TINYINT DEFAULT 0,
    sort_order      TINYINT DEFAULT 0
);

INSERT INTO sbm_dimensions (dimension_no, dimension_name, color_hex, icon, indicator_count, sort_order)
VALUES
    (1, 'Curriculum and Teaching',                    '#2563EB', 'book',        8,  1),
    (2, 'Learning Environment',                       '#16A34A', 'home',        10, 2),
    (3, 'Leadership and Governance',                  '#7C3AED', 'star',        4,  3),
    (4, 'Accountability and Continuous Improvement',  '#D97706', 'check-circle',6,  4),
    (5, 'Human Resource Development',                 '#DC2626', 'users',       7,  5),
    (6, 'Finance and Resource Management',            '#0D9488', 'dollar-sign', 7,  6)
ON DUPLICATE KEY UPDATE dimension_name = VALUES(dimension_name);

-- ── SBM INDICATORS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sbm_indicators (
    indicator_id   INT PRIMARY KEY AUTO_INCREMENT,
    dimension_id   INT NOT NULL,
    indicator_code VARCHAR(10) NOT NULL UNIQUE,
    indicator_text TEXT NOT NULL,
    mov_guide      TEXT,
    is_active      TINYINT DEFAULT 1,
    sort_order     INT DEFAULT 0,
    FOREIGN KEY (dimension_id) REFERENCES sbm_dimensions(dimension_id)
);

INSERT INTO sbm_indicators (dimension_id, indicator_code, indicator_text, mov_guide, sort_order)
VALUES
-- Dimension 1: Curriculum and Teaching
(1,'1.1','Learner proficiency rate in Grade 3 (Literacy and Numeracy) meets or exceeds the national target.','MPS/proficiency data, class records, assessment results',1),
(1,'1.2','Learner proficiency rate in Grade 6 meets or exceeds the national target.','MPS/proficiency data, NAT results, class records',2),
(1,'1.3','Learner proficiency rate in Grade 10 meets or exceeds the national target.','NAT/quarterly assessment results, class records',3),
(1,'1.4','Learner proficiency rate in Grade 12 or ALS completion rate meets or exceeds the national target.','NCAE results, ALS completion certificates, enrollment data',4),
(1,'1.5','Results of NAT/PEPT/ALS A&E are analyzed and used to improve instructional programs.','Item analysis reports, LAC session minutes, action plans',5),
(1,'1.6','Contextualized and localized learning materials are developed and used by teachers.','Developed LMs, LRMDS uploads, utilization records',6),
(1,'1.7','Remediation, enhancement, and intervention programs are implemented for at-risk learners.','Program designs, attendance records, monitoring reports',7),
(1,'1.8','TLE/TVL programs have active industry partnerships and produce certified graduates.','MOA with industry partners, NC/COC certificates, industry immersion records',8),
-- Dimension 2: Learning Environment
(2,'2.1','The school has a zero-bullying policy that is implemented, monitored, and updated regularly.','Anti-bullying policy, incident reports, monitoring logs',1),
(2,'2.2','Dropout rate is within the national target, with active early warning and intervention systems.','Enrollment/completion data, BEIS reports, intervention records',2),
(2,'2.3','Out-of-School Youth (OSY) re-entry programs and ALS are actively implemented.','OSY mapping, ALS enrollment records, completion reports',3),
(2,'2.4','School activities are culture-sensitive, inclusive, and respectful of learner diversity.','Activity programs, photo documentation, feedback forms',4),
(2,'2.5','The Child Protection Committee (CPC) is organized, functional, and conducts regular activities.','CPC composition order, meeting minutes, activity reports',5),
(2,'2.6','A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.','DRRM plan, drill documentation, hazard maps',6),
(2,'2.7','Mental wellness programs for learners are implemented and monitored.','Wellness program design, referral records, accomplishment reports',7),
(2,'2.8','School facilities are accessible for learners with disabilities (SPED/PWD compliance).','Accessibility audit, ramp/facility photos, SPED program records',8),
(2,'2.9','Safe school environment audit is conducted and findings are addressed.','Safety audit checklist, action plans, repair/improvement records',9),
(2,'2.10','Learners actively participate in school governance through SSG/SPG and other bodies.','SSG/SPG election records, meeting minutes, program reports',10),
-- Dimension 3: Leadership and Governance
(3,'3.1','The School Improvement Plan (SIP) is developed collaboratively with all stakeholders and implemented.','SIP document, stakeholder attendance, accomplishment reports',1),
(3,'3.2','A school-community planning team is established and functional.','Planning team composition, meeting minutes, activity reports',2),
(3,'3.3','SSG/SPG is organized, trained, and actively implements programs.','SSG/SPG constitution, election records, program accomplishments',3),
(3,'3.4','The school head implements innovations in frontline service delivery.','Innovation documentation, feedback/evaluation, impact data',4),
-- Dimension 4: Accountability and Continuous Improvement
(4,'4.1','School Governance Council (SGC) records are complete, updated, and actions are documented.','SGC composition order, meeting minutes, resolutions',1),
(4,'4.2','PTA is organized and actively engaged in school planning and monitoring.','PTA election records, meeting minutes, financial reports',2),
(4,'4.3','Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.','MOA/MOU documents, partnership activity reports, resource contributions',3),
(4,'4.4','Monitoring and evaluation of school programs is conducted regularly with documented results.','M&E plan, monitoring reports, action plans based on findings',4),
(4,'4.5','Stakeholder satisfaction survey is conducted and results are used for improvement.','Survey instrument, tabulated results, action plans',5),
(4,'4.6','Transparency board and public financial disclosures are updated and accessible.','Transparency board photos, disclosure documents, posting records',6),
-- Dimension 5: Human Resource Development
(5,'5.1','All teaching and non-teaching personnel accomplish IPCR/OPCR on time.','Signed IPCR/OPCR forms, summary rating sheets, submission records',1),
(5,'5.2','Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.','LAC session plan, attendance, minutes, action plans',2),
(5,'5.3','Teachers participate in professional development activities (trainings, seminars, scholarships).','Training certificates, individual development plans, PDO records',3),
(5,'5.4','Employee recognition programs are implemented to motivate and reward outstanding performance.','Recognition program design, awarding documentation, photos',4),
(5,'5.5','Teacher workload is within prescribed limits and fairly distributed.','Teaching load summary, class schedule, assignment orders',5),
(5,'5.6','HR development programs for non-teaching staff are implemented.','Capacity building plans, training records, accomplishment reports',6),
(5,'5.7','Succession planning and talent management practices are in place.','Succession plan document, mentoring records, talent inventory',7),
-- Dimension 6: Finance and Resource Management
(6,'6.1','School facilities inventory is updated and submitted on time.','Facilities inventory form, submission acknowledgment, photos',1),
(6,'6.2','Infrastructure maintenance plan is implemented and documented.','Maintenance plan, work orders, accomplishment reports, photos',2),
(6,'6.3','Water, electricity, and internet utilities are functional and adequate.','Utility bills, repair records, functionality assessment',3),
(6,'6.4','Library resources are adequate, updated, and accessible to all learners.','Library inventory, acquisition records, utilization logs',4),
(6,'6.5','Laboratory equipment is functional, adequate, and used for instruction.','Lab inventory, equipment condition report, utilization records',5),
(6,'6.6','MOOE utilization rate reaches 100% with proper documentation.','MOOE liquidation reports, utilization matrix, COB vs. actual',6),
(6,'6.7','Liquidation reports are submitted on time and complete.','Liquidation reports, submission acknowledgments, COA records',7)
ON DUPLICATE KEY UPDATE indicator_text = VALUES(indicator_text);

-- ============================================================
-- ASSESSMENT TABLES
-- ============================================================

-- ── SBM ASSESSMENT CYCLES ────────────────────────────────────
CREATE TABLE IF NOT EXISTS sbm_cycles (
    cycle_id       INT PRIMARY KEY AUTO_INCREMENT,
    sy_id          INT NOT NULL,
    school_id      INT NOT NULL,
    status         ENUM('draft','in_progress','submitted','validated','returned') DEFAULT 'draft',
    overall_score  DECIMAL(5,2) NULL,
    -- FIXED: maturity levels now match DepEd Order 007 (Beginning/Developing/Maturing/Advanced)
    maturity_level ENUM('Beginning','Developing','Maturing','Advanced') NULL,
    started_at     DATETIME NULL,
    submitted_at   DATETIME NULL,
    validated_by   INT NULL,
    validated_at   DATETIME NULL,
    validator_remarks TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cycle (sy_id, school_id),
    FOREIGN KEY (sy_id)        REFERENCES school_years(sy_id),
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)    ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(user_id)        ON DELETE SET NULL
);

-- ── SBM RESPONSES (School Head answers) ──────────────────────
CREATE TABLE IF NOT EXISTS sbm_responses (
    response_id  INT PRIMARY KEY AUTO_INCREMENT,
    cycle_id     INT NOT NULL,
    indicator_id INT NOT NULL,
    school_id    INT NOT NULL,
    rating       TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 4),
    evidence_text TEXT NULL,
    file_path    VARCHAR(255) NULL,
    rated_by     INT NOT NULL,
    rated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_response (cycle_id, indicator_id),
    FOREIGN KEY (cycle_id)     REFERENCES sbm_cycles(cycle_id)     ON DELETE CASCADE,
    FOREIGN KEY (indicator_id) REFERENCES sbm_indicators(indicator_id),
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)        ON DELETE CASCADE,
    FOREIGN KEY (rated_by)     REFERENCES users(user_id)
);

-- ── TEACHER RESPONSES (separate from school head responses) ──
-- Teachers answer their assigned indicators here, not in sbm_responses
CREATE TABLE IF NOT EXISTS teacher_responses (
    tr_id        INT PRIMARY KEY AUTO_INCREMENT,
    cycle_id     INT NOT NULL,
    indicator_id INT NOT NULL,
    school_id    INT NOT NULL,
    teacher_id   INT NOT NULL,
    rating       TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 4),
    remarks      TEXT NULL,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teacher_response (cycle_id, indicator_id, teacher_id),
    FOREIGN KEY (cycle_id)     REFERENCES sbm_cycles(cycle_id)     ON DELETE CASCADE,
    FOREIGN KEY (indicator_id) REFERENCES sbm_indicators(indicator_id),
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)        ON DELETE CASCADE,
    FOREIGN KEY (teacher_id)   REFERENCES users(user_id)            ON DELETE CASCADE
);

-- ── SBM DIMENSION SCORES ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS sbm_dimension_scores (
    score_id     INT PRIMARY KEY AUTO_INCREMENT,
    cycle_id     INT NOT NULL,
    school_id    INT NOT NULL,
    dimension_id INT NOT NULL,
    raw_score    DECIMAL(5,2) DEFAULT 0,
    max_score    DECIMAL(5,2) DEFAULT 0,
    percentage   DECIMAL(5,2) DEFAULT 0,
    computed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dim_score (cycle_id, dimension_id),
    FOREIGN KEY (cycle_id)    REFERENCES sbm_cycles(cycle_id)      ON DELETE CASCADE,
    FOREIGN KEY (school_id)   REFERENCES schools(school_id)         ON DELETE CASCADE,
    FOREIGN KEY (dimension_id) REFERENCES sbm_dimensions(dimension_id)
);

-- ── ML PREDICTIONS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ml_predictions (
    pred_id          INT PRIMARY KEY AUTO_INCREMENT,
    school_id        INT NOT NULL,
    cycle_id         INT NOT NULL,
    dimension_id     INT NULL,
    indicator_id     INT NULL,
    prediction_type  ENUM('score_forecast','risk_flag','ta_recommendation','maturity_forecast') DEFAULT 'risk_flag',
    predicted_value  DECIMAL(5,2) NULL,
    risk_level       ENUM('low','medium','high') DEFAULT 'low',
    recommendation   TEXT NULL,
    confidence_score DECIMAL(4,3) DEFAULT 0.000,
    generated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)        ON DELETE CASCADE,
    FOREIGN KEY (cycle_id)     REFERENCES sbm_cycles(cycle_id)      ON DELETE CASCADE,
    FOREIGN KEY (dimension_id) REFERENCES sbm_dimensions(dimension_id) ON DELETE SET NULL,
    FOREIGN KEY (indicator_id) REFERENCES sbm_indicators(indicator_id) ON DELETE SET NULL
);

-- ============================================================
-- IMPROVEMENT PLANNING
-- ============================================================

-- ── IMPROVEMENT PLANS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS improvement_plans (
    plan_id             INT PRIMARY KEY AUTO_INCREMENT,
    school_id           INT NOT NULL,
    cycle_id            INT NOT NULL,
    dimension_id        INT NOT NULL,
    indicator_id        INT NULL,
    priority_level      ENUM('High','Medium','Low') DEFAULT 'Medium',
    objective           TEXT NOT NULL,
    strategy            TEXT NOT NULL,
    person_responsible  VARCHAR(120),
    target_date         DATE NULL,
    resources_needed    TEXT NULL,
    expected_output     TEXT NULL,
    status              ENUM('planned','ongoing','completed','cancelled') DEFAULT 'planned',
    remarks             TEXT NULL,
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)       ON DELETE CASCADE,
    FOREIGN KEY (cycle_id)     REFERENCES sbm_cycles(cycle_id)     ON DELETE CASCADE,
    FOREIGN KEY (dimension_id) REFERENCES sbm_dimensions(dimension_id),
    FOREIGN KEY (indicator_id) REFERENCES sbm_indicators(indicator_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)   REFERENCES users(user_id)
);

-- ── TECHNICAL ASSISTANCE ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS technical_assistance (
    ta_id          INT PRIMARY KEY AUTO_INCREMENT,
    school_id      INT NOT NULL,
    cycle_id       INT NOT NULL,
    dimension_id   INT NULL,
    sdo_user_id    INT NOT NULL,
    ta_type        ENUM('coaching','mentoring','training','monitoring','evaluation') DEFAULT 'monitoring',
    title          VARCHAR(200) NOT NULL,
    description    TEXT NULL,
    recommendation TEXT NULL,
    scheduled_date DATE NULL,
    conducted_date DATE NULL,
    status         ENUM('scheduled','conducted','cancelled') DEFAULT 'scheduled',
    outcomes       TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)        ON DELETE CASCADE,
    FOREIGN KEY (cycle_id)     REFERENCES sbm_cycles(cycle_id)      ON DELETE CASCADE,
    FOREIGN KEY (dimension_id) REFERENCES sbm_dimensions(dimension_id) ON DELETE SET NULL,
    FOREIGN KEY (sdo_user_id)  REFERENCES users(user_id)
);

-- ── TA REQUESTS (School-initiated TA requests to SDO) ────────
CREATE TABLE IF NOT EXISTS ta_requests (
    request_id     INT PRIMARY KEY AUTO_INCREMENT,
    school_id      INT NOT NULL,
    cycle_id       INT NOT NULL,
    requested_by   INT NOT NULL,
    sdo_user_id    INT NULL,
    dimension_ids  VARCHAR(100) NOT NULL,
    concern        TEXT NOT NULL,
    preferred_date DATE NULL,
    sdo_response   TEXT NULL,
    agreed_actions TEXT NULL,
    scheduled_date DATE NULL,
    outcome_notes  TEXT NULL,
    completed_date DATE NULL,
    status         ENUM('pending','acknowledged','scheduled','completed','declined') DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)    ON DELETE CASCADE,
    FOREIGN KEY (cycle_id)     REFERENCES sbm_cycles(cycle_id)  ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(user_id),
    FOREIGN KEY (sdo_user_id)  REFERENCES users(user_id)        ON DELETE SET NULL
);

-- ============================================================
-- WORKFLOW & TIMELINE
-- ============================================================

-- ── GRADING PERIODS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grading_periods (
    period_id   INT PRIMARY KEY AUTO_INCREMENT,
    sy_id       INT NOT NULL,
    period_no   TINYINT NOT NULL,
    period_name VARCHAR(60) NOT NULL,
    date_start  DATE NULL,
    date_end    DATE NULL,
    is_current  TINYINT DEFAULT 0,
    UNIQUE KEY unique_period (sy_id, period_no),
    FOREIGN KEY (sy_id) REFERENCES school_years(sy_id) ON DELETE CASCADE
);

-- ── SBM WORKFLOW PHASES ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS sbm_workflow_phases (
    phase_id    INT PRIMARY KEY AUTO_INCREMENT,
    sy_id       INT NOT NULL,
    phase_no    TINYINT NOT NULL,
    phase_name  VARCHAR(120) NOT NULL,
    description TEXT NULL,
    date_start  DATE NULL,
    date_end    DATE NULL,
    is_active   TINYINT DEFAULT 0,
    UNIQUE KEY unique_phase (sy_id, phase_no),
    FOREIGN KEY (sy_id) REFERENCES school_years(sy_id) ON DELETE CASCADE
);

-- ── SCHOOL WORKFLOW STATUS ───────────────────────────────────
CREATE TABLE IF NOT EXISTS school_workflow_status (
    status_id      INT PRIMARY KEY AUTO_INCREMENT,
    school_id      INT NOT NULL,
    sy_id          INT NOT NULL,
    current_phase  TINYINT DEFAULT 1,
    overall_status ENUM('not_started','in_progress','completed') DEFAULT 'not_started',
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_school_sy (school_id, sy_id),
    FOREIGN KEY (school_id) REFERENCES schools(school_id) ON DELETE CASCADE,
    FOREIGN KEY (sy_id)     REFERENCES school_years(sy_id) ON DELETE CASCADE
);

-- ── WORKFLOW CHECKPOINTS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS workflow_checkpoints (
    cp_id          INT PRIMARY KEY AUTO_INCREMENT,
    school_id      INT NOT NULL,
    sy_id          INT NOT NULL,
    phase_no       TINYINT NOT NULL,
    grading_period TINYINT NULL,
    cp_type        VARCHAR(40) NOT NULL,
    status         ENUM('pending','done','overdue') DEFAULT 'pending',
    due_date       DATE NULL,
    completed_at   DATETIME NULL,
    completed_by   INT NULL,
    notes          TEXT NULL,
    UNIQUE KEY unique_cp (school_id, sy_id, cp_type),
    FOREIGN KEY (school_id)    REFERENCES schools(school_id)  ON DELETE CASCADE,
    FOREIGN KEY (sy_id)        REFERENCES school_years(sy_id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES users(user_id)      ON DELETE SET NULL
);

-- ============================================================
-- COMMUNICATION
-- ============================================================

-- ── ANNOUNCEMENTS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
    ann_id       INT PRIMARY KEY AUTO_INCREMENT,
    posted_by    INT NOT NULL,
    title        VARCHAR(200) NOT NULL,
    content      TEXT NOT NULL,
    target_role  ENUM('all','school_head','teacher','sdo','ro') DEFAULT 'all',
    category     ENUM('general','policy','deadline','advisory','emergency') DEFAULT 'general',
    is_published TINYINT DEFAULT 1,
    region_id    INT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id)
);

-- ── ACTIVITY LOG ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    log_id     INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NULL,
    action     VARCHAR(100) NOT NULL,
    module     VARCHAR(60) NULL,
    details    TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- PERFORMANCE INDEXES
-- ============================================================

-- Users
CREATE INDEX IF NOT EXISTS idx_users_school      ON users(school_id);
CREATE INDEX IF NOT EXISTS idx_users_role        ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status      ON users(status);

-- Cycles
CREATE INDEX IF NOT EXISTS idx_cycles_school     ON sbm_cycles(school_id);
CREATE INDEX IF NOT EXISTS idx_cycles_sy         ON sbm_cycles(sy_id);
CREATE INDEX IF NOT EXISTS idx_cycles_status     ON sbm_cycles(status);
CREATE INDEX IF NOT EXISTS idx_cycles_submitted  ON sbm_cycles(submitted_at);

-- Responses
CREATE INDEX IF NOT EXISTS idx_responses_cycle   ON sbm_responses(cycle_id);
CREATE INDEX IF NOT EXISTS idx_responses_ind     ON sbm_responses(indicator_id);

-- Teacher responses
CREATE INDEX IF NOT EXISTS idx_tr_cycle          ON teacher_responses(cycle_id);
CREATE INDEX IF NOT EXISTS idx_tr_teacher        ON teacher_responses(teacher_id);
CREATE INDEX IF NOT EXISTS idx_tr_indicator      ON teacher_responses(indicator_id);

-- Dimension scores
CREATE INDEX IF NOT EXISTS idx_dimscores_cycle   ON sbm_dimension_scores(cycle_id);
CREATE INDEX IF NOT EXISTS idx_dimscores_school  ON sbm_dimension_scores(school_id);

-- Activity log (for poll.php performance)
CREATE INDEX IF NOT EXISTS idx_log_created       ON activity_log(created_at);
CREATE INDEX IF NOT EXISTS idx_log_user          ON activity_log(user_id);

-- TA requests
CREATE INDEX IF NOT EXISTS idx_ta_req_school     ON ta_requests(school_id);
CREATE INDEX IF NOT EXISTS idx_ta_req_status     ON ta_requests(status);

-- Announcements
CREATE INDEX IF NOT EXISTS idx_ann_target        ON announcements(target_role);
CREATE INDEX IF NOT EXISTS idx_ann_published     ON announcements(is_published);

-- Workflow checkpoints
CREATE INDEX IF NOT EXISTS idx_wf_cp_school      ON workflow_checkpoints(school_id, sy_id);
CREATE INDEX IF NOT EXISTS idx_wf_cp_status      ON workflow_checkpoints(status);

-- ============================================================
-- SAMPLE SEED DATA
-- ============================================================

INSERT INTO regions (region_name, region_code)
VALUES ('Region IV-A (CALABARZON)', 'REGION-IVA')
ON DUPLICATE KEY UPDATE region_name = region_name;

INSERT INTO divisions (region_id, division_name, division_code)
VALUES (1, 'Schools Division of Cavite', 'SDO-CAVITE')
ON DUPLICATE KEY UPDATE division_name = division_name;

INSERT INTO schools (division_id, school_name, school_id_deped, address, classification, school_head_name, total_enrollment, total_teachers)
VALUES (1, 'Dasmariñas Integrated High School', '301143', 'Dasmariñas City, Cavite', 'JHS', 'Maria Santos', 2500, 85)
ON DUPLICATE KEY UPDATE school_name = school_name;

-- ============================================================
-- UPGRADE SCRIPT
-- Run this block if you already have an existing sbm_db and
-- just need to add the missing tables / fix the maturity enum.
-- Safe to run even if the DB is brand new.
-- ============================================================

-- Fix maturity level enum to match DepEd Order 007 four levels
-- (Beginning → Developing → Maturing → Advanced)
-- Old schema had 'Proficient' instead of 'Maturing' — this corrects it.
ALTER TABLE sbm_cycles
MODIFY COLUMN maturity_level
ENUM('Beginning','Developing','Maturing','Advanced') NULL;

-- Add region_id column to announcements if it doesn't exist
-- (needed by ro/announcements.php)
ALTER TABLE announcements
ADD COLUMN IF NOT EXISTS region_id INT NULL AFTER is_published;