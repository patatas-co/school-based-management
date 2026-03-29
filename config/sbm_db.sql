-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 11:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30
-- 
-- CLEANED: Removed 'admin' role. School Head is now the top-level role.
-- School Head credentials: username=schoolhead / password=(your existing password)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sbm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(60) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
(2, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-28 02:28:07'),
(6, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-28 03:31:41'),
(7, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 2', '::1', '2026-03-28 03:32:18'),
(18, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-28 14:15:53'),
(19, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 4', '::1', '2026-03-28 14:16:29'),
(63, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-29 07:25:41'),
(64, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 07:26:05'),
(65, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:00:43'),
(67, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:04:28'),
(70, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:21:44'),
(72, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:23:42'),
(74, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:50:11'),
(76, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 09:07:56'),
(77, 37, 'update_user', 'users', 'Updated user ID:37', '::1', '2026-03-29 09:08:40'),
(78, 37, 'update_user', 'users', 'Updated user ID:37', '::1', '2026-03-29 09:09:01'),
(81, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 09:56:46');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `ann_id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('all','school_head','sbm_coordinator','teacher','external_stakeholder') DEFAULT 'all',
  `category` enum('general','policy','deadline','advisory','emergency') DEFAULT 'general',
  `is_published` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
-- Note: announcement posted_by=1 removed since user 1 (admin) is deleted.
--

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `division_id` int(11) NOT NULL,
  `division_name` varchar(100) NOT NULL,
  `region_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`division_id`, `division_name`, `region_id`) VALUES
(1, 'Cavite Division', 1),
(2, 'Cavite Division', 1);

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_type` varchar(60) DEFAULT 'account_creation',
  `recipient_email` varchar(120) NOT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`log_id`, `user_id`, `email_type`, `recipient_email`, `status`, `error_message`, `sent_at`) VALUES
(12, 36, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-29 07:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `grading_periods`
--

CREATE TABLE `grading_periods` (
  `period_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `period_no` tinyint(4) NOT NULL COMMENT '1=First 2=Second 3=Third 4=Fourth',
  `period_name` varchar(60) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `improvement_plans`
--

CREATE TABLE `improvement_plans` (
  `plan_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `dimension_id` int(11) NOT NULL,
  `indicator_id` int(11) DEFAULT NULL,
  `priority_level` enum('High','Medium','Low') DEFAULT 'Medium',
  `objective` text NOT NULL,
  `strategy` text NOT NULL,
  `person_responsible` varchar(120) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `resources_needed` text DEFAULT NULL,
  `expected_output` text DEFAULT NULL,
  `status` enum('planned','ongoing','completed','cancelled') DEFAULT 'planned',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_comment_analysis`
--

CREATE TABLE `ml_comment_analysis` (
  `analysis_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) DEFAULT NULL,
  `dimension_id` int(11) DEFAULT NULL,
  `comment_count` int(11) DEFAULT 0,
  `sentiment_pos` int(11) DEFAULT 0,
  `sentiment_neg` int(11) DEFAULT 0,
  `sentiment_neu` int(11) DEFAULT 0,
  `top_topics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_topics`)),
  `has_urgent` tinyint(1) DEFAULT 0,
  `urgency_details` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_predictions`
--

CREATE TABLE `ml_predictions` (
  `pred_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `dimension_id` int(11) DEFAULT NULL,
  `indicator_id` int(11) DEFAULT NULL,
  `prediction_type` varchar(60) DEFAULT 'risk_flag',
  `predicted_value` decimal(5,2) DEFAULT NULL,
  `risk_level` enum('low','medium','high') DEFAULT 'low',
  `recommendation` text DEFAULT NULL,
  `confidence_score` decimal(4,3) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_recommendations`
--

CREATE TABLE `ml_recommendations` (
  `rec_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `recommendation_text` longtext NOT NULL,
  `generated_by` varchar(60) DEFAULT 'rule_based',
  `top_topics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_topics`)),
  `has_urgent` tinyint(1) DEFAULT 0,
  `sentiment_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sentiment_summary`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_training_snapshots`
--

CREATE TABLE `ml_training_snapshots` (
  `snapshot_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `dim_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dim_scores`)),
  `indicator_ratings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`indicator_ratings`)),
  `overall_score` decimal(5,2) DEFAULT NULL,
  `maturity_level` enum('Beginning','Developing','Maturing','Advanced') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_setup_tokens`
--

CREATE TABLE `password_setup_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'setup',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_setup_tokens`
--

INSERT INTO `password_setup_tokens` (`token_id`, `user_id`, `token`, `type`, `expires_at`, `used_at`, `created_at`) VALUES
(19, 36, '4db483f27c408e63b53fd6ca91f75d5a6db512e0b869c65c5126511a00032b9f', 'setup', '2026-03-31 15:25:09', '2026-03-29 15:25:41', '2026-03-29 07:25:09');

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `region_id` int(11) NOT NULL,
  `region_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`region_id`, `region_name`) VALUES
(1, 'Region IV-A CALABARZON'),
(2, 'Region IV-A CALABARZON');

-- --------------------------------------------------------

--
-- Table structure for table `sbm_cycles`
--

CREATE TABLE `sbm_cycles` (
  `cycle_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `status` enum('draft','in_progress','submitted','validated','returned') DEFAULT 'draft',
  `overall_score` decimal(5,2) DEFAULT NULL,
  `maturity_level` enum('Beginning','Developing','Maturing','Advanced') DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validator_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_cycles`
--

INSERT INTO `sbm_cycles` (`cycle_id`, `sy_id`, `school_id`, `status`, `overall_score`, `maturity_level`, `started_at`, `submitted_at`, `validated_at`, `validated_by`, `validator_remarks`, `created_at`) VALUES
(1, 4, 1, 'in_progress', NULL, NULL, '2026-03-29 16:13:02', NULL, NULL, NULL, NULL, '2026-03-29 08:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `sbm_dimensions`
--

CREATE TABLE `sbm_dimensions` (
  `dimension_id` int(11) NOT NULL,
  `dimension_no` tinyint(4) NOT NULL,
  `dimension_name` varchar(120) NOT NULL,
  `color_hex` varchar(7) DEFAULT '#16A34A',
  `icon` varchar(40) DEFAULT NULL,
  `indicator_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_dimensions`
--

INSERT INTO `sbm_dimensions` (`dimension_id`, `dimension_no`, `dimension_name`, `color_hex`, `icon`, `indicator_count`) VALUES
(1, 1, 'Curriculum and Teaching', '#2563EB', 'book', 8),
(2, 2, 'Learning Environment', '#16A34A', 'home', 10),
(3, 3, 'Leadership and Governance', '#7C3AED', 'star', 4),
(4, 4, 'Accountability and Continuous Improvement', '#D97706', 'check-circle', 6),
(5, 5, 'Human Resource Development', '#DC2626', 'users', 7),
(6, 6, 'Finance and Resource Management', '#0D9488', 'dollar-sign', 7);

-- --------------------------------------------------------

--
-- Table structure for table `sbm_dimension_scores`
--

CREATE TABLE `sbm_dimension_scores` (
  `score_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `dimension_id` int(11) NOT NULL,
  `raw_score` decimal(8,2) DEFAULT 0.00,
  `max_score` decimal(8,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sbm_indicators`
--

CREATE TABLE `sbm_indicators` (
  `indicator_id` int(11) NOT NULL,
  `dimension_id` int(11) NOT NULL,
  `indicator_code` varchar(10) NOT NULL,
  `indicator_text` text NOT NULL,
  `mov_guide` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_indicators`
--

INSERT INTO `sbm_indicators` (`indicator_id`, `dimension_id`, `indicator_code`, `indicator_text`, `mov_guide`, `sort_order`, `is_active`) VALUES
(1, 1, '1.1', 'Learner proficiency rate in Grade 3 (Literacy and Numeracy) meets or exceeds the national target.', 'MPS/proficiency data, class records, assessment results', 1, 1),
(2, 1, '1.2', 'Learner proficiency rate in Grade 6 meets or exceeds the national target.', 'MPS/proficiency data, NAT results, class records', 2, 1),
(3, 1, '1.3', 'Learner proficiency rate in Grade 10 meets or exceeds the national target.', 'NAT/quarterly assessment results, class records', 3, 1),
(4, 1, '1.4', 'Learner proficiency rate in Grade 12 or ALS completion rate meets or exceeds the national target.', 'NCAE results, ALS completion certificates, enrollment data', 4, 1),
(5, 1, '1.5', 'Results of NAT/PEPT/ALS A&E are analyzed and used to improve instructional programs.', 'Item analysis reports, LAC session minutes, action plans', 5, 1),
(6, 1, '1.6', 'Contextualized and localized learning materials (LM) are developed and used by teachers.', 'Developed LMs, LRMDS uploads, utilization records', 6, 1),
(7, 1, '1.7', 'Remediation, enhancement, and intervention programs are implemented for at-risk learners.', 'Program designs, attendance records, monitoring reports', 7, 1),
(8, 1, '1.8', 'TLE/TVL programs have active industry partnerships and produce certified graduates.', 'MOA with industry partners, NC/COC certificates, industry immersion records', 8, 1),
(9, 2, '2.1', 'The school has a zero-bullying policy that is implemented, monitored, and updated regularly.', 'Anti-bullying policy, incident reports, monitoring logs', 1, 1),
(10, 2, '2.2', 'Dropout rate is within the national target, with active early warning and intervention systems.', 'Enrollment/completion data, BEIS reports, intervention records', 2, 1),
(11, 2, '2.3', 'Out-of-School Youth (OSY) re-entry programs and ALS are actively implemented.', 'OSY mapping, ALS enrollment records, completion reports', 3, 1),
(12, 2, '2.4', 'School activities are culture-sensitive, inclusive, and respectful of learner diversity.', 'Activity programs, photo documentation, feedback forms', 4, 1),
(13, 2, '2.5', 'The Child Protection Committee (CPC) is organized, functional, and conducts regular activities.', 'CPC composition order, meeting minutes, activity reports', 5, 1),
(14, 2, '2.6', 'A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.', 'DRRM plan, drill documentation, hazard maps', 6, 1),
(15, 2, '2.7', 'Mental wellness programs for learners are implemented and monitored.', 'Wellness program design, referral records, accomplishment reports', 7, 1),
(16, 2, '2.8', 'School facilities are accessible for learners with disabilities (SPED/PWD compliance).', 'Accessibility audit, ramp/facility photos, SPED program records', 8, 1),
(17, 2, '2.9', 'Safe school environment audit is conducted and findings are addressed.', 'Safety audit checklist, action plans, repair/improvement records', 9, 1),
(18, 2, '2.10', 'Learners actively participate in school governance through SSG/SPG and other bodies.', 'SSG/SPG election records, meeting minutes, program reports', 10, 1),
(19, 3, '3.1', 'The School Improvement Plan (SIP) is developed collaboratively with all stakeholders and implemented.', 'SIP document, stakeholder attendance, accomplishment reports', 1, 1),
(20, 3, '3.2', 'A school-community planning team is established and functional.', 'Planning team composition, meeting minutes, activity reports', 2, 1),
(21, 3, '3.3', 'SSG/SPG is organized, trained, and actively implements programs.', 'SSG/SPG constitution, election records, program accomplishments', 3, 1),
(22, 3, '3.4', 'The school head implements innovations in frontline service delivery.', 'Innovation documentation, feedback/evaluation, impact data', 4, 1),
(23, 4, '4.1', 'School Governance Council (SGC) records are complete, updated, and actions are documented.', 'SGC composition order, meeting minutes, resolutions', 1, 1),
(24, 4, '4.2', 'PTA is organized and actively engaged in school planning and monitoring.', 'PTA election records, meeting minutes, financial reports', 2, 1),
(25, 4, '4.3', 'Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.', 'MOA/MOU documents, partnership activity reports, resource contributions', 3, 1),
(26, 4, '4.4', 'Monitoring and evaluation of school programs is conducted regularly with documented results.', 'M&E plan, monitoring reports, action plans based on findings', 4, 1),
(27, 4, '4.5', 'Stakeholder satisfaction survey is conducted and results are used for improvement.', 'Survey instrument, tabulated results, action plans', 5, 1),
(28, 4, '4.6', 'Transparency board and public financial disclosures are updated and accessible.', 'Transparency board photos, disclosure documents, posting records', 6, 1),
(29, 5, '5.1', 'All teaching and non-teaching personnel accomplish IPCR/OPCR on time.', 'Signed IPCR/OPCR forms, summary rating sheets, submission records', 1, 1),
(30, 5, '5.2', 'Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.', 'LAC session plan, attendance, minutes, action plans', 2, 1),
(31, 5, '5.3', 'Teachers participate in professional development activities (trainings, seminars, scholarships).', 'Training certificates, individual development plans, PDO records', 3, 1),
(32, 5, '5.4', 'Employee recognition programs are implemented to motivate and reward outstanding performance.', 'Recognition program design, awarding documentation, photos', 4, 1),
(33, 5, '5.5', 'Teacher workload is within prescribed limits and fairly distributed.', 'Teaching load summary, class schedule, assignment orders', 5, 1),
(34, 5, '5.6', 'HR development programs for non-teaching staff are implemented.', 'Capacity building plans, training records, accomplishment reports', 6, 1),
(35, 5, '5.7', 'Succession planning and talent management practices are in place.', 'Succession plan document, mentoring records, talent inventory', 7, 1),
(36, 6, '6.1', 'School facilities inventory is updated and submitted on time.', 'Facilities inventory form, submission acknowledgment, photos', 1, 1),
(37, 6, '6.2', 'Infrastructure maintenance plan is implemented and documented.', 'Maintenance plan, work orders, accomplishment reports, photos', 2, 1),
(38, 6, '6.3', 'Water, electricity, and internet utilities are functional and adequate.', 'Utility bills, repair records, functionality assessment', 3, 1),
(39, 6, '6.4', 'Library resources are adequate, updated, and accessible to all learners.', 'Library inventory, acquisition records, utilization logs', 4, 1),
(40, 6, '6.5', 'Laboratory equipment is functional, adequate, and used for instruction.', 'Lab inventory, equipment condition report, utilization records', 5, 1),
(41, 6, '6.6', 'MOOE utilization rate reaches 100% with proper documentation.', 'MOOE liquidation reports, utilization matrix, COB vs. actual', 6, 1),
(42, 6, '6.7', 'Liquidation reports are submitted on time and complete.', 'Liquidation reports, submission acknowledgments, COA records', 7, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sbm_responses`
--

CREATE TABLE `sbm_responses` (
  `response_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 4),
  `evidence_text` text DEFAULT NULL,
  `rated_by` int(11) DEFAULT NULL,
  `rated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sbm_workflow_phases`
--

CREATE TABLE `sbm_workflow_phases` (
  `phase_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `phase_no` tinyint(4) NOT NULL,
  `phase_name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(11) NOT NULL,
  `school_name` varchar(200) NOT NULL,
  `school_id_deped` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `classification` enum('ES','JHS','SHS','IS','ALS') NOT NULL DEFAULT 'JHS',
  `school_head_name` varchar(120) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `total_enrollment` int(11) DEFAULT 0,
  `total_teachers` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `division_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `school_name`, `school_id_deped`, `address`, `classification`, `school_head_name`, `contact_no`, `email`, `total_enrollment`, `total_teachers`, `created_at`, `division_id`) VALUES
(1, 'Dasmariñas Integrated High School', '301143', 'Dasmariñas City, Cavite', 'JHS', 'Ryza Evangelio', NULL, NULL, 2500, 85, '2026-03-11 16:18:36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `school_workflow_status`
--

CREATE TABLE `school_workflow_status` (
  `wf_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `current_phase` tinyint(4) DEFAULT 1,
  `phase1_started_at` datetime DEFAULT NULL,
  `phase1_done_at` datetime DEFAULT NULL,
  `phase2_started_at` datetime DEFAULT NULL,
  `phase2_done_at` datetime DEFAULT NULL,
  `phase3_started_at` datetime DEFAULT NULL,
  `q1_monitored_at` datetime DEFAULT NULL,
  `q2_monitored_at` datetime DEFAULT NULL,
  `q3_monitored_at` datetime DEFAULT NULL,
  `phase3_done_at` datetime DEFAULT NULL,
  `overall_status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `sy_id` int(11) NOT NULL,
  `label` varchar(20) NOT NULL,
  `is_current` tinyint(4) DEFAULT 0,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`sy_id`, `label`, `is_current`, `date_start`, `date_end`) VALUES
(4, '2026-2027', 1, '2026-07-13', '2027-04-26');

-- --------------------------------------------------------

--
-- Table structure for table `sh_indicator_overrides`
--

CREATE TABLE `sh_indicator_overrides` (
  `override_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `original_avg` decimal(4,2) DEFAULT NULL,
  `override_rating` tinyint(4) NOT NULL CHECK (`override_rating` between 1 and 4),
  `override_reason` text DEFAULT NULL,
  `overridden_by` int(11) NOT NULL,
  `overridden_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stakeholder_responses`
--

CREATE TABLE `stakeholder_responses` (
  `sr_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `stakeholder_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 4),
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stakeholder_submissions`
--

CREATE TABLE `stakeholder_submissions` (
  `submission_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `stakeholder_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `status` enum('draft','submitted') DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `response_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(80) NOT NULL,
  `config_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ta_requests`
--

CREATE TABLE `ta_requests` (
  `request_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `sdo_user_id` int(11) DEFAULT NULL,
  `dimension_ids` varchar(255) DEFAULT NULL,
  `concern` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','acknowledged','scheduled','completed','declined') DEFAULT 'pending',
  `sdo_response` text DEFAULT NULL,
  `agreed_actions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_indicator_assignments`
--

CREATE TABLE `teacher_indicator_assignments` (
  `assignment_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `indicator_code` varchar(10) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_responses`
--

CREATE TABLE `teacher_responses` (
  `tr_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 4),
  `remarks` text DEFAULT NULL,
  `status` enum('draft','submitted') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_submissions`
--

CREATE TABLE `teacher_submissions` (
  `submission_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `sy_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `status` enum('draft','submitted') DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `response_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- CHANGED: Removed 'admin' from role enum. 'school_head' is now the top role.
-- REMOVED: user_id=1 (old admin account with empty role)
-- KEPT:    user_id=37 (schoolhead / Ryza Evangelio) as the School Head
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` enum('school_head','sbm_coordinator','teacher','external_stakeholder') NOT NULL DEFAULT 'teacher',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `school_id` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--
-- School Head: username=schoolhead, password=password (hash below = 'password')
-- To reset password run:
--   UPDATE users SET password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username='schoolhead';
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `last_login`, `created_at`, `email_verified`, `reset_token`, `token_expiry`, `email_sent_at`, `force_password_change`) VALUES
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, '2026-03-28 11:31:41', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, '2026-03-27 18:09:31', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, '2026-03-27 18:10:33', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, '2026-03-27 18:11:31', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, '2026-03-29 16:04:28', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0),
(36, 'Pat', '$2y$10$DxRSlQjmuvZ3HgSdJFkBkena6d.BK3Eel.NDxS7UgARO4yLVuXu0y', 'ariascharles00@gmail.com', 'Charles Arias', 'sbm_coordinator', 'active', 1, '2026-03-29 16:50:11', '2026-03-29 07:25:09', 0, NULL, NULL, '2026-03-29 15:25:16', 0),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza Evangelio', 'school_head', 'active', 1, '2026-03-29 17:56:46', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `workflow_checkpoints`
--

CREATE TABLE `workflow_checkpoints` (
  `cp_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `phase_no` tinyint(4) NOT NULL,
  `grading_period` tinyint(4) DEFAULT NULL,
  `cp_type` enum('self_assessment','planning','q1_monitoring','q2_monitoring','q3_monitoring','completion') NOT NULL,
  `status` enum('pending','done','overdue') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`ann_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`division_id`),
  ADD KEY `region_id` (`region_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `grading_periods`
--
ALTER TABLE `grading_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `unique_period` (`sy_id`,`period_no`);

--
-- Indexes for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `dimension_id` (`dimension_id`),
  ADD KEY `improvement_plans_ibfk_4` (`indicator_id`),
  ADD KEY `improvement_plans_ibfk_5` (`created_by`);

--
-- Indexes for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  ADD PRIMARY KEY (`analysis_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Indexes for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD PRIMARY KEY (`pred_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Indexes for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  ADD PRIMARY KEY (`rec_id`),
  ADD UNIQUE KEY `cycle_id` (`cycle_id`);

--
-- Indexes for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  ADD PRIMARY KEY (`snapshot_id`),
  ADD UNIQUE KEY `cycle_id` (`cycle_id`),
  ADD KEY `ml_training_snapshots_ibfk_1` (`school_id`);

--
-- Indexes for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`region_id`);

--
-- Indexes for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  ADD PRIMARY KEY (`cycle_id`),
  ADD UNIQUE KEY `unique_cycle` (`sy_id`,`school_id`),
  ADD KEY `sy_id` (`sy_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `validated_by` (`validated_by`);

--
-- Indexes for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  ADD PRIMARY KEY (`dimension_id`),
  ADD UNIQUE KEY `dimension_no` (`dimension_no`);

--
-- Indexes for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  ADD PRIMARY KEY (`score_id`),
  ADD UNIQUE KEY `unique_dim_score` (`cycle_id`,`dimension_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `dimension_id` (`dimension_id`);

--
-- Indexes for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  ADD PRIMARY KEY (`indicator_id`),
  ADD UNIQUE KEY `indicator_code` (`indicator_code`),
  ADD KEY `dimension_id` (`dimension_id`);

--
-- Indexes for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD UNIQUE KEY `unique_response` (`cycle_id`,`indicator_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `rated_by` (`rated_by`);

--
-- Indexes for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  ADD PRIMARY KEY (`phase_id`),
  ADD UNIQUE KEY `unique_phase` (`sy_id`,`phase_no`),
  ADD KEY `sy_id` (`sy_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `school_workflow_status`
--
ALTER TABLE `school_workflow_status`
  ADD PRIMARY KEY (`wf_id`),
  ADD UNIQUE KEY `unique_school_sy` (`school_id`,`sy_id`),
  ADD KEY `sy_id` (`sy_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`sy_id`);

--
-- Indexes for table `sh_indicator_overrides`
--
ALTER TABLE `sh_indicator_overrides`
  ADD PRIMARY KEY (`override_id`),
  ADD UNIQUE KEY `unique_override` (`cycle_id`,`indicator_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `overridden_by` (`overridden_by`);

--
-- Indexes for table `stakeholder_responses`
--
ALTER TABLE `stakeholder_responses`
  ADD PRIMARY KEY (`sr_id`),
  ADD UNIQUE KEY `unique_stakeholder_response` (`cycle_id`,`indicator_id`,`stakeholder_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `stakeholder_id` (`stakeholder_id`);

--
-- Indexes for table `stakeholder_submissions`
--
ALTER TABLE `stakeholder_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_stakeholder_submission` (`cycle_id`,`stakeholder_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `stakeholder_id` (`stakeholder_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `sy_id` (`sy_id`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indexes for table `ta_requests`
--
ALTER TABLE `ta_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `teacher_indicator_assignments`
--
ALTER TABLE `teacher_indicator_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_teacher_indicator` (`teacher_id`,`indicator_code`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  ADD PRIMARY KEY (`tr_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `sy_id` (`sy_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  ADD PRIMARY KEY (`cp_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `sy_id` (`sy_id`),
  ADD KEY `completed_by` (`completed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

ALTER TABLE `announcements`
  MODIFY `ann_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `email_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ml_comment_analysis`
  MODIFY `analysis_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ml_predictions`
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ml_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `password_setup_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

ALTER TABLE `regions`
  MODIFY `region_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sbm_workflow_phases`
  MODIFY `phase_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `school_workflow_status`
  MODIFY `wf_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `school_years`
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `sh_indicator_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stakeholder_responses`
  MODIFY `sr_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stakeholder_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ta_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `teacher_indicator_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `teacher_responses`
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

ALTER TABLE `workflow_checkpoints`
  MODIFY `cp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `divisions`
  ADD CONSTRAINT `divisions_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`region_id`);

ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `grading_periods`
  ADD CONSTRAINT `grading_periods_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

ALTER TABLE `improvement_plans`
  ADD CONSTRAINT `improvement_plans_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `improvement_plans_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `improvement_plans_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`),
  ADD CONSTRAINT `improvement_plans_ibfk_4` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `improvement_plans_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `ml_comment_analysis`
  ADD CONSTRAINT `ml_comment_analysis_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

ALTER TABLE `ml_predictions`
  ADD CONSTRAINT `ml_predictions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_predictions_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

ALTER TABLE `ml_recommendations`
  ADD CONSTRAINT `ml_recommendations_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

ALTER TABLE `ml_training_snapshots`
  ADD CONSTRAINT `ml_training_snapshots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_training_snapshots_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

ALTER TABLE `password_setup_tokens`
  ADD CONSTRAINT `password_setup_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `sbm_cycles`
  ADD CONSTRAINT `sbm_cycles_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_cycles_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_cycles_ibfk_3` FOREIGN KEY (`validated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `sbm_dimension_scores`
  ADD CONSTRAINT `sbm_dimension_scores_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_dimension_scores_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_dimension_scores_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`);

ALTER TABLE `sbm_indicators`
  ADD CONSTRAINT `sbm_indicators_ibfk_1` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`) ON DELETE CASCADE;

ALTER TABLE `sbm_responses`
  ADD CONSTRAINT `sbm_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `sbm_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_responses_ibfk_4` FOREIGN KEY (`rated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `sbm_workflow_phases`
  ADD CONSTRAINT `sbm_workflow_phases_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

ALTER TABLE `school_workflow_status`
  ADD CONSTRAINT `school_workflow_status_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `school_workflow_status_ibfk_2` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

ALTER TABLE `sh_indicator_overrides`
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_4` FOREIGN KEY (`overridden_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `stakeholder_responses`
  ADD CONSTRAINT `stakeholder_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `stakeholder_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_responses_ibfk_4` FOREIGN KEY (`stakeholder_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `stakeholder_submissions`
  ADD CONSTRAINT `stakeholder_submissions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_submissions_ibfk_2` FOREIGN KEY (`stakeholder_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_submissions_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_submissions_ibfk_4` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

ALTER TABLE `ta_requests`
  ADD CONSTRAINT `ta_requests_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ta_requests_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ta_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `teacher_indicator_assignments`
  ADD CONSTRAINT `teacher_indicator_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `teacher_indicator_assignments_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `teacher_responses`
  ADD CONSTRAINT `teacher_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `teacher_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_responses_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `teacher_submissions`
  ADD CONSTRAINT `teacher_submissions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_submissions_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ts_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ts_sy_fk` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE SET NULL;

ALTER TABLE `workflow_checkpoints`
  ADD CONSTRAINT `workflow_checkpoints_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_checkpoints_ibfk_2` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_checkpoints_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
