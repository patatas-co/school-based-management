-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2026 at 02:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(1, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-11 16:32:48'),
(2, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-11 16:34:11'),
(3, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-11 16:36:08'),
(4, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:00:43'),
(5, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:29:43'),
(6, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:30:12'),
(7, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:36:07'),
(8, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:47:57'),
(9, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:48:14'),
(10, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:50:06'),
(11, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:51:20'),
(12, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:52:52'),
(13, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-11 17:53:40'),
(14, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 11:53:58'),
(15, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 11:54:12'),
(16, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-12 12:01:24'),
(17, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-12 12:01:55'),
(18, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 12:02:02'),
(19, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:12:14'),
(20, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:12:24'),
(21, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:13:46'),
(22, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:13:58'),
(23, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:14:08'),
(24, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:14:16'),
(25, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-12 15:14:28'),
(26, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:33:50'),
(27, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:34:52'),
(28, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:35:34'),
(29, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:35:46'),
(30, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:35:57'),
(31, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:47:38'),
(32, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:48:51'),
(33, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 22:52:46'),
(34, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:07:56'),
(35, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:12:20'),
(36, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:12:59'),
(37, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:13:17'),
(38, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:15:28'),
(39, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:16:49'),
(40, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:16:57'),
(41, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:17:12'),
(42, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:17:36'),
(43, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:17:53'),
(44, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-12 23:18:02'),
(45, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 01:16:23'),
(46, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 01:38:05'),
(47, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 01:38:15'),
(48, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-14 01:38:31'),
(49, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:00:42'),
(50, 5, 'submit_ta_request', 'improvement', 'Submitted TA request for cycle 2', '::1', '2026-03-14 03:01:53'),
(51, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:02:02'),
(52, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:02:08'),
(53, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:09:41'),
(54, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:09:51'),
(55, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:10:28'),
(56, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:33:44'),
(57, 1, 'validate_assessment', 'assessment', 'Validated cycle ID:2', '::1', '2026-03-14 03:34:11'),
(58, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 03:34:38'),
(59, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 04:34:00'),
(60, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-14 04:34:17'),
(61, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 04:34:36'),
(62, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-14 04:34:52'),
(63, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 04:37:20'),
(64, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 04:45:16'),
(65, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 05:03:51'),
(66, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 05:06:01'),
(67, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 05:06:14'),
(68, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-14 06:23:02'),
(69, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-14 06:23:09'),
(70, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 06:23:39'),
(71, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 06:24:05'),
(72, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 06:28:42'),
(73, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 06:28:59'),
(74, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 07:04:09'),
(75, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 07:05:14'),
(76, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 07:05:24'),
(77, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 08:58:58'),
(78, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 10:50:59'),
(79, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 10:55:05'),
(80, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 10:55:28'),
(81, 5, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 3', '::1', '2026-03-14 11:20:47'),
(82, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 11:21:02'),
(83, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 11:36:49'),
(84, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:30:32'),
(85, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:30:38'),
(86, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:30:44'),
(87, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:32:17'),
(88, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:32:40'),
(89, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:33:33'),
(90, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-14 23:33:45'),
(91, 5, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 4', '::1', '2026-03-14 23:34:39'),
(92, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 00:01:41'),
(93, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 00:02:04'),
(94, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 00:35:24');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `ann_id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('all','school_head','teacher','sdo','ro') DEFAULT 'all',
  `category` enum('general','policy','deadline','advisory','emergency') DEFAULT 'general',
  `is_published` tinyint(4) DEFAULT 1,
  `region_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`ann_id`, `posted_by`, `title`, `content`, `target_role`, `category`, `is_published`, `region_id`, `created_at`) VALUES
(1, 1, 'In need assistance for the students of KLD', 'Ms. Jedie Mendoza, assist the students. they are in need of your help.', 'school_head', 'general', 1, NULL, '2026-03-11 17:29:08');

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `division_id` int(11) NOT NULL,
  `region_id` int(11) NOT NULL,
  `division_name` varchar(120) NOT NULL,
  `division_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`division_id`, `region_id`, `division_name`, `division_code`) VALUES
(1, 1, 'Schools Division of Cavite', 'SDO-CAVITE');

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

--
-- Dumping data for table `grading_periods`
--

INSERT INTO `grading_periods` (`period_id`, `sy_id`, `period_no`, `period_name`, `date_start`, `date_end`, `is_current`, `created_at`) VALUES
(1, 1, 1, 'First Grading', '2024-08-01', '2024-10-04', 0, '2026-03-12 11:53:47'),
(2, 1, 2, 'Second Grading', '2024-10-07', '2024-12-20', 0, '2026-03-12 11:53:47'),
(3, 1, 3, 'Third Grading', '2025-01-06', '2025-03-21', 1, '2026-03-12 11:53:47'),
(4, 1, 4, 'Fourth Grading', '2025-03-24', '2025-04-04', 0, '2026-03-12 11:53:47');

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

--
-- Dumping data for table `improvement_plans`
--

INSERT INTO `improvement_plans` (`plan_id`, `school_id`, `cycle_id`, `dimension_id`, `indicator_id`, `priority_level`, `objective`, `strategy`, `person_responsible`, `target_date`, `resources_needed`, `expected_output`, `status`, `remarks`, `created_by`, `created_at`) VALUES
(3, 1, 4, 1, 3, 'Medium', 'Improve performance on indicator 1.3: Learner proficiency rate in Grade 10 meets or exceeds the national target.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-14 23:35:02');

-- --------------------------------------------------------

--
-- Table structure for table `ml_predictions`
--

-- ⚠️  ml_predictions: Schema placeholder for future ML integration.
-- No application code currently reads or writes this table.

CREATE TABLE `ml_predictions` (
  `pred_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `dimension_id` int(11) DEFAULT NULL,
  `indicator_id` int(11) DEFAULT NULL,
  `prediction_type` enum('score_forecast','risk_flag','ta_recommendation','maturity_forecast') DEFAULT 'risk_flag',
  `predicted_value` decimal(5,2) DEFAULT NULL,
  `risk_level` enum('low','medium','high') DEFAULT 'low',
  `recommendation` text DEFAULT NULL,
  `confidence_score` decimal(4,3) DEFAULT 0.000,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `region_id` int(11) NOT NULL,
  `region_name` varchar(100) NOT NULL,
  `region_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`region_id`, `region_name`, `region_code`) VALUES
(1, 'Region IV-A (CALABARZON)', 'REGION-IVA');

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
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `validator_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_cycles`
--

INSERT INTO `sbm_cycles` (`cycle_id`, `sy_id`, `school_id`, `status`, `overall_score`, `maturity_level`, `started_at`, `submitted_at`, `validated_by`, `validated_at`, `validator_remarks`, `created_at`) VALUES
(4, 1, 1, 'submitted', 85.71, 'Advanced', '2026-03-14 19:23:12', '2026-03-15 07:34:39', NULL, NULL, NULL, '2026-03-14 11:23:12');

-- --------------------------------------------------------

--
-- Table structure for table `sbm_dimensions`
--

CREATE TABLE `sbm_dimensions` (
  `dimension_id` int(11) NOT NULL,
  `dimension_no` tinyint(4) NOT NULL,
  `dimension_name` varchar(120) NOT NULL,
  `color_hex` varchar(10) DEFAULT '#16A34A',
  `icon` varchar(40) DEFAULT 'star',
  `indicator_count` tinyint(4) DEFAULT 0,
  `sort_order` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_dimensions`
--

INSERT INTO `sbm_dimensions` (`dimension_id`, `dimension_no`, `dimension_name`, `color_hex`, `icon`, `indicator_count`, `sort_order`) VALUES
(1, 1, 'Curriculum and Teaching', '#2563EB', 'book', 8, 1),
(2, 2, 'Learning Environment', '#16A34A', 'home', 10, 2),
(3, 3, 'Leadership and Governance', '#7C3AED', 'star', 4, 3),
(4, 4, 'Accountability and Continuous Improvement', '#D97706', 'check-circle', 6, 4),
(5, 5, 'Human Resource Development', '#DC2626', 'users', 7, 5),
(6, 6, 'Finance and Resource Management', '#0D9488', 'dollar-sign', 7, 6);

-- --------------------------------------------------------

--
-- Table structure for table `sbm_dimension_scores`
--

CREATE TABLE `sbm_dimension_scores` (
  `score_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `dimension_id` int(11) NOT NULL,
  `raw_score` decimal(5,2) DEFAULT 0.00,
  `max_score` decimal(5,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_dimension_scores`
--

INSERT INTO `sbm_dimension_scores` (`score_id`, `cycle_id`, `school_id`, `dimension_id`, `raw_score`, `max_score`, `percentage`, `computed_at`) VALUES
(105, 4, 1, 1, 9.00, 12.00, 75.00, '2026-03-14 23:34:00'),
(133, 4, 1, 2, 14.00, 16.00, 87.50, '2026-03-14 23:34:17'),
(137, 4, 1, 4, 19.00, 20.00, 95.00, '2026-03-14 23:34:23'),
(142, 4, 1, 6, 6.00, 8.00, 75.00, '2026-03-14 23:34:27');

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
  `is_active` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_indicators`
--

INSERT INTO `sbm_indicators` (`indicator_id`, `dimension_id`, `indicator_code`, `indicator_text`, `mov_guide`, `is_active`, `sort_order`) VALUES
(1, 1, '1.1', 'Learner proficiency rate in Grade 3 (Literacy and Numeracy) meets or exceeds the national target.', 'MPS/proficiency data, class records, assessment results', 1, 1),
(2, 1, '1.2', 'Learner proficiency rate in Grade 6 meets or exceeds the national target.', 'MPS/proficiency data, NAT results, class records', 1, 2),
(3, 1, '1.3', 'Learner proficiency rate in Grade 10 meets or exceeds the national target.', 'NAT/quarterly assessment results, class records', 1, 3),
(4, 1, '1.4', 'Learner proficiency rate in Grade 12 or ALS completion rate meets or exceeds the national target.', 'NCAE results, ALS completion certificates, enrollment data', 1, 4),
(5, 1, '1.5', 'Results of NAT/PEPT/ALS A&E are analyzed and used to improve instructional programs.', 'Item analysis reports, LAC session minutes, action plans', 1, 5),
(6, 1, '1.6', 'Contextualized and localized learning materials are developed and used by teachers.', 'Developed LMs, LRMDS uploads, utilization records', 1, 6),
(7, 1, '1.7', 'Remediation, enhancement, and intervention programs are implemented for at-risk learners.', 'Program designs, attendance records, monitoring reports', 1, 7),
(8, 1, '1.8', 'TLE/TVL programs have active industry partnerships and produce certified graduates.', 'MOA with industry partners, NC/COC certificates, industry immersion records', 1, 8),
(9, 2, '2.1', 'The school has a zero-bullying policy that is implemented, monitored, and updated regularly.', 'Anti-bullying policy, incident reports, monitoring logs', 1, 1),
(10, 2, '2.2', 'Dropout rate is within the national target, with active early warning and intervention systems.', 'Enrollment/completion data, BEIS reports, intervention records', 1, 2),
(11, 2, '2.3', 'Out-of-School Youth (OSY) re-entry programs and ALS are actively implemented.', 'OSY mapping, ALS enrollment records, completion reports', 1, 3),
(12, 2, '2.4', 'School activities are culture-sensitive, inclusive, and respectful of learner diversity.', 'Activity programs, photo documentation, feedback forms', 1, 4),
(13, 2, '2.5', 'The Child Protection Committee (CPC) is organized, functional, and conducts regular activities.', 'CPC composition order, meeting minutes, activity reports', 1, 5),
(14, 2, '2.6', 'A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.', 'DRRM plan, drill documentation, hazard maps', 1, 6),
(15, 2, '2.7', 'Mental wellness programs for learners are implemented and monitored.', 'Wellness program design, referral records, accomplishment reports', 1, 7),
(16, 2, '2.8', 'School facilities are accessible for learners with disabilities (SPED/PWD compliance).', 'Accessibility audit, ramp/facility photos, SPED program records', 1, 8),
(17, 2, '2.9', 'Safe school environment audit is conducted and findings are addressed.', 'Safety audit checklist, action plans, repair/improvement records', 1, 9),
(18, 2, '2.10', 'Learners actively participate in school governance through SSG/SPG and other bodies.', 'SSG/SPG election records, meeting minutes, program reports', 1, 10),
(19, 3, '3.1', 'The School Improvement Plan (SIP) is developed collaboratively with all stakeholders and implemented.', 'SIP document, stakeholder attendance, accomplishment reports', 1, 1),
(20, 3, '3.2', 'A school-community planning team is established and functional.', 'Planning team composition, meeting minutes, activity reports', 1, 2),
(21, 3, '3.3', 'SSG/SPG is organized, trained, and actively implements programs.', 'SSG/SPG constitution, election records, program accomplishments', 1, 3),
(22, 3, '3.4', 'The school head implements innovations in frontline service delivery.', 'Innovation documentation, feedback/evaluation, impact data', 1, 4),
(23, 4, '4.1', 'School Governance Council (SGC) records are complete, updated, and actions are documented.', 'SGC composition order, meeting minutes, resolutions', 1, 1),
(24, 4, '4.2', 'PTA is organized and actively engaged in school planning and monitoring.', 'PTA election records, meeting minutes, financial reports', 1, 2),
(25, 4, '4.3', 'Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.', 'MOA/MOU documents, partnership activity reports, resource contributions', 1, 3),
(26, 4, '4.4', 'Monitoring and evaluation of school programs is conducted regularly with documented results.', 'M&E plan, monitoring reports, action plans based on findings', 1, 4),
(27, 4, '4.5', 'Stakeholder satisfaction survey is conducted and results are used for improvement.', 'Survey instrument, tabulated results, action plans', 1, 5),
(28, 4, '4.6', 'Transparency board and public financial disclosures are updated and accessible.', 'Transparency board photos, disclosure documents, posting records', 1, 6),
(29, 5, '5.1', 'All teaching and non-teaching personnel accomplish IPCR/OPCR on time.', 'Signed IPCR/OPCR forms, summary rating sheets, submission records', 1, 1),
(30, 5, '5.2', 'Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.', 'LAC session plan, attendance, minutes, action plans', 1, 2),
(31, 5, '5.3', 'Teachers participate in professional development activities (trainings, seminars, scholarships).', 'Training certificates, individual development plans, PDO records', 1, 3),
(32, 5, '5.4', 'Employee recognition programs are implemented to motivate and reward outstanding performance.', 'Recognition program design, awarding documentation, photos', 1, 4),
(33, 5, '5.5', 'Teacher workload is within prescribed limits and fairly distributed.', 'Teaching load summary, class schedule, assignment orders', 1, 5),
(34, 5, '5.6', 'HR development programs for non-teaching staff are implemented.', 'Capacity building plans, training records, accomplishment reports', 1, 6),
(35, 5, '5.7', 'Succession planning and talent management practices are in place.', 'Succession plan document, mentoring records, talent inventory', 1, 7),
(36, 6, '6.1', 'School facilities inventory is updated and submitted on time.', 'Facilities inventory form, submission acknowledgment, photos', 1, 1),
(37, 6, '6.2', 'Infrastructure maintenance plan is implemented and documented.', 'Maintenance plan, work orders, accomplishment reports, photos', 1, 2),
(38, 6, '6.3', 'Water, electricity, and internet utilities are functional and adequate.', 'Utility bills, repair records, functionality assessment', 1, 3),
(39, 6, '6.4', 'Library resources are adequate, updated, and accessible to all learners.', 'Library inventory, acquisition records, utilization logs', 1, 4),
(40, 6, '6.5', 'Laboratory equipment is functional, adequate, and used for instruction.', 'Lab inventory, equipment condition report, utilization records', 1, 5),
(41, 6, '6.6', 'MOOE utilization rate reaches 100% with proper documentation.', 'MOOE liquidation reports, utilization matrix, COB vs. actual', 1, 6),
(42, 6, '6.7', 'Liquidation reports are submitted on time and complete.', 'Liquidation reports, submission acknowledgments, COA records', 1, 7);

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
  `file_path` varchar(255) DEFAULT NULL,
  `rated_by` int(11) NOT NULL,
  `rated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_responses`
--

INSERT INTO `sbm_responses` (`response_id`, `cycle_id`, `indicator_id`, `school_id`, `rating`, `evidence_text`, `file_path`, `rated_by`, `rated_at`) VALUES
(121, 4, 1, 1, 3, '', NULL, 5, '2026-03-14 23:33:57'),
(122, 4, 2, 1, 4, '', NULL, 5, '2026-03-14 23:33:59'),
(123, 4, 3, 1, 2, '', NULL, 5, '2026-03-14 23:34:00'),
(124, 4, 13, 1, 4, '', NULL, 5, '2026-03-14 23:34:14'),
(125, 4, 14, 1, 4, '', NULL, 5, '2026-03-14 23:34:15'),
(126, 4, 17, 1, 3, '', NULL, 5, '2026-03-14 23:34:16'),
(127, 4, 16, 1, 3, '', NULL, 5, '2026-03-14 23:34:17'),
(128, 4, 24, 1, 4, '', NULL, 5, '2026-03-14 23:34:18'),
(129, 4, 25, 1, 4, '', NULL, 5, '2026-03-14 23:34:21'),
(130, 4, 26, 1, 3, '', NULL, 5, '2026-03-14 23:34:21'),
(131, 4, 28, 1, 4, '', NULL, 5, '2026-03-14 23:34:23'),
(132, 4, 27, 1, 4, '', NULL, 5, '2026-03-14 23:34:23'),
(133, 4, 41, 1, 3, '', NULL, 5, '2026-03-14 23:34:26'),
(134, 4, 42, 1, 3, '', NULL, 5, '2026-03-14 23:34:27');

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
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_workflow_phases`
--

INSERT INTO `sbm_workflow_phases` (`phase_id`, `sy_id`, `phase_no`, `phase_name`, `description`, `date_start`, `date_end`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'Self-Assessment', 'School Head and stakeholders accomplish the 42-indicator SBM checklist during the 4th Grading Period using the 4 Degrees of Manifestation.', '2025-03-24', '2025-04-04', 1, '2026-03-12 11:53:47'),
(2, 1, 2, 'Planning Integration', 'During summer vacation, the school integrates SBM results into the School Improvement Plan (SIP). Priority dimensions guide resource allocation.', '2025-04-07', '2025-05-30', 0, '2026-03-12 11:53:47'),
(3, 1, 3, 'Implementation & Monitoring', 'From 1st to 3rd Grading of the succeeding SY, the school implements planned interventions. SDO conducts quarterly monitoring and TA visits.', '2025-08-01', '2026-03-21', 0, '2026-03-12 11:53:47');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `school_name` varchar(200) NOT NULL,
  `school_id_deped` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `classification` enum('ES','JHS','SHS','IS','ALS') NOT NULL DEFAULT 'JHS',
  `school_head_name` varchar(120) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `total_enrollment` int(11) DEFAULT 0,
  `total_teachers` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `division_id`, `school_name`, `school_id_deped`, `address`, `classification`, `school_head_name`, `contact_no`, `email`, `total_enrollment`, `total_teachers`, `created_at`) VALUES
(1, 1, 'Dasmariñas Integrated High School', '301143', 'Dasmariñas City, Cavite', 'JHS', 'Maria Santos', NULL, NULL, 2500, 85, '2026-03-11 16:18:36');

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

--
-- Dumping data for table `school_workflow_status`
--

INSERT INTO `school_workflow_status` (`wf_id`, `school_id`, `sy_id`, `current_phase`, `phase1_started_at`, `phase1_done_at`, `phase2_started_at`, `phase2_done_at`, `phase3_started_at`, `q1_monitored_at`, `q2_monitored_at`, `q3_monitored_at`, `phase3_done_at`, `overall_status`, `remarks`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'not_started', NULL, '2026-03-12 11:57:51');

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
(1, '2024-2025', 1, '2024-06-03', '2025-04-04');

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
  `overridden_at` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `ta_requests`
--

CREATE TABLE `ta_requests` (
  `request_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `dimension_ids` varchar(100) NOT NULL,
  `concern` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `status` enum('pending','acknowledged','scheduled','completed','declined') DEFAULT 'pending',
  `sdo_user_id` int(11) DEFAULT NULL,
  `sdo_response` text DEFAULT NULL,
  `agreed_actions` text DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `outcome_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

--
-- Dumping data for table `teacher_responses`
--

INSERT INTO `teacher_responses` (`tr_id`, `cycle_id`, `indicator_id`, `school_id`, `teacher_id`, `rating`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(78, 4, 4, 1, 2, 4, '', 'draft', '2026-03-14 23:32:46', '2026-03-14 23:32:46'),
(79, 4, 5, 1, 2, 3, '', 'draft', '2026-03-14 23:32:49', '2026-03-14 23:32:49'),
(80, 4, 6, 1, 2, 4, '', 'draft', '2026-03-14 23:32:51', '2026-03-14 23:32:51'),
(81, 4, 7, 1, 2, 2, '', 'draft', '2026-03-14 23:32:52', '2026-03-14 23:32:52'),
(82, 4, 8, 1, 2, 4, '', 'draft', '2026-03-14 23:32:54', '2026-03-14 23:32:54'),
(83, 4, 9, 1, 2, 2, '', 'draft', '2026-03-14 23:32:56', '2026-03-14 23:32:56'),
(84, 4, 10, 1, 2, 4, '', 'draft', '2026-03-14 23:32:57', '2026-03-14 23:32:57'),
(85, 4, 11, 1, 2, 3, '', 'draft', '2026-03-14 23:32:58', '2026-03-14 23:32:58'),
(86, 4, 12, 1, 2, 4, '', 'draft', '2026-03-14 23:32:59', '2026-03-14 23:32:59'),
(87, 4, 15, 1, 2, 2, '', 'draft', '2026-03-14 23:33:00', '2026-03-14 23:33:00'),
(88, 4, 18, 1, 2, 3, '', 'draft', '2026-03-14 23:33:01', '2026-03-14 23:33:01'),
(89, 4, 19, 1, 2, 4, '', 'draft', '2026-03-14 23:33:02', '2026-03-14 23:33:02'),
(90, 4, 20, 1, 2, 4, '', 'draft', '2026-03-14 23:33:03', '2026-03-14 23:33:03'),
(91, 4, 21, 1, 2, 3, '', 'draft', '2026-03-14 23:33:05', '2026-03-14 23:33:05'),
(92, 4, 22, 1, 2, 3, '', 'draft', '2026-03-14 23:33:06', '2026-03-14 23:33:06'),
(93, 4, 23, 1, 2, 4, '', 'draft', '2026-03-14 23:33:07', '2026-03-14 23:33:07'),
(94, 4, 29, 1, 2, 3, '', 'draft', '2026-03-14 23:33:08', '2026-03-14 23:33:08'),
(95, 4, 30, 1, 2, 4, '', 'draft', '2026-03-14 23:33:09', '2026-03-14 23:33:09'),
(96, 4, 31, 1, 2, 3, '', 'draft', '2026-03-14 23:33:10', '2026-03-14 23:33:10'),
(97, 4, 32, 1, 2, 4, '', 'draft', '2026-03-14 23:33:12', '2026-03-14 23:33:12'),
(98, 4, 33, 1, 2, 3, '', 'draft', '2026-03-14 23:33:12', '2026-03-14 23:33:12'),
(99, 4, 34, 1, 2, 4, '', 'draft', '2026-03-14 23:33:14', '2026-03-14 23:33:14'),
(100, 4, 35, 1, 2, 3, '', 'draft', '2026-03-14 23:33:15', '2026-03-14 23:33:15'),
(101, 4, 36, 1, 2, 4, '', 'draft', '2026-03-14 23:33:16', '2026-03-14 23:33:16'),
(102, 4, 37, 1, 2, 3, '', 'draft', '2026-03-14 23:33:17', '2026-03-14 23:33:17'),
(103, 4, 38, 1, 2, 4, '', 'draft', '2026-03-14 23:33:19', '2026-03-14 23:33:19'),
(104, 4, 39, 1, 2, 3, '', 'draft', '2026-03-14 23:33:19', '2026-03-14 23:33:19'),
(105, 4, 40, 1, 2, 4, '', 'draft', '2026-03-14 23:33:21', '2026-03-14 23:33:21');

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
-- Table structure for table `technical_assistance`
--

CREATE TABLE `technical_assistance` (
  `ta_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `dimension_id` int(11) DEFAULT NULL,
  `sdo_user_id` int(11) NOT NULL,
  `ta_type` enum('coaching','mentoring','training','monitoring','evaluation') DEFAULT 'monitoring',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `conducted_date` date DEFAULT NULL,
  `status` enum('scheduled','conducted','cancelled') DEFAULT 'scheduled',
  `outcomes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(120) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` enum('admin','school_head','teacher','sdo','ro','external_stakeholder') NOT NULL DEFAULT 'teacher',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `school_id` int(11) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `division_id`, `region_id`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sbm.edu.ph', 'System Administrator', 'admin', 'active', NULL, NULL, NULL, '2026-03-15 07:33:33', '2026-03-11 16:18:35'),
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, NULL, NULL, '2026-03-15 08:35:24', '2026-03-11 16:31:59'),
(5, 'Ryza E.', '$2y$10$uNsxRtmZILkMBaV3EfXdtuIfTTvSp0ZCctNKLtjeoZ9N9MNEjvrV6', 'rmevangelio@dihs.edu.ph', 'Ryza Evangelio', 'school_head', 'active', 1, NULL, NULL, '2026-03-15 08:02:04', '2026-03-11 16:35:49'),
(8, 'Rolito Billones', '$2y$10$o.BOPmRTDvS9vK8jV2RVluCDlY6grCd969TseIoECHgsKTztLsxu.', 'rbillones@dihs.edu.ph', 'Rolito Villones', 'sdo', 'active', 1, NULL, NULL, '2026-03-14 14:23:09', '2026-03-11 17:49:46'),
(10, 'Charles', '$2y$10$i0qO24wRCGQIyiSFz.xR/Oz6dIXZJR1.rRK71zDoIGdAKU2Y3XfqS', 'cpmarias@dihs.edu.com', 'Charles Patrick Arias', 'ro', 'active', 1, NULL, NULL, '2026-03-15 07:32:17', '2026-03-11 17:52:19');

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
-- Dumping data for table `workflow_checkpoints`
--

INSERT INTO `workflow_checkpoints` (`cp_id`, `school_id`, `sy_id`, `phase_no`, `grading_period`, `cp_type`, `status`, `due_date`, `completed_at`, `completed_by`, `notes`, `created_at`) VALUES
(1, 1, 1, 1, NULL, 'self_assessment', 'overdue', '2025-04-04', NULL, NULL, NULL, '2026-03-12 11:57:51'),
(2, 1, 1, 2, NULL, 'planning', 'overdue', '2025-05-30', NULL, NULL, NULL, '2026-03-12 11:57:51'),
(3, 1, 1, 3, 1, 'q1_monitoring', 'overdue', '2025-10-17', NULL, NULL, NULL, '2026-03-12 11:57:51'),
(4, 1, 1, 3, 2, 'q2_monitoring', 'overdue', '2026-01-02', NULL, NULL, NULL, '2026-03-12 11:57:51'),
(5, 1, 1, 3, 3, 'q3_monitoring', 'pending', '2026-03-21', NULL, NULL, NULL, '2026-03-12 11:57:51'),
(6, 1, 1, 3, NULL, 'completion', 'pending', '2026-03-21', NULL, NULL, NULL, '2026-03-12 11:57:51');

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
-- Indexes for table `grading_periods`
--
ALTER TABLE `grading_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `uq_gp` (`sy_id`,`period_no`);

--
-- Indexes for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `dimension_id` (`dimension_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD PRIMARY KEY (`pred_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `dimension_id` (`dimension_id`),
  ADD KEY `indicator_id` (`indicator_id`);

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
  ADD KEY `school_id` (`school_id`),
  ADD KEY `validated_by` (`validated_by`);

--
-- Indexes for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  ADD PRIMARY KEY (`dimension_id`);

--
-- Indexes for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  ADD PRIMARY KEY (`score_id`),
  ADD UNIQUE KEY `unique_dim_score` (`cycle_id`,`dimension_id`),
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
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `rated_by` (`rated_by`);

--
-- Indexes for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  ADD PRIMARY KEY (`phase_id`),
  ADD UNIQUE KEY `uq_phase` (`sy_id`,`phase_no`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`),
  ADD UNIQUE KEY `school_id_deped` (`school_id_deped`),
  ADD KEY `division_id` (`division_id`);

--
-- Indexes for table `school_workflow_status`
--
ALTER TABLE `school_workflow_status`
  ADD PRIMARY KEY (`wf_id`),
  ADD UNIQUE KEY `uq_school_sy` (`school_id`,`sy_id`),
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
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `overridden_by` (`overridden_by`);

--
-- Indexes for table `stakeholder_responses`
--
ALTER TABLE `stakeholder_responses`
  ADD PRIMARY KEY (`sr_id`),
  ADD UNIQUE KEY `unique_stakeholder_response` (`cycle_id`,`indicator_id`,`stakeholder_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `stakeholder_id` (`stakeholder_id`);

--
-- Indexes for table `stakeholder_submissions`
--
ALTER TABLE `stakeholder_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_stakeholder_cycle` (`cycle_id`,`stakeholder_id`),
  ADD KEY `stakeholder_id` (`stakeholder_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `sy_id` (`sy_id`);

--
-- Indexes for table `ta_requests`
--
ALTER TABLE `ta_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `sdo_user_id` (`sdo_user_id`);

--
-- Indexes for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  ADD PRIMARY KEY (`tr_id`),
  ADD UNIQUE KEY `unique_teacher_response` (`cycle_id`,`indicator_id`,`teacher_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_teacher_cycle` (`cycle_id`,`teacher_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `ts_school_fk` (`school_id`),
  ADD KEY `ts_sy_fk` (`sy_id`);

--
-- Indexes for table `technical_assistance`
--
ALTER TABLE `technical_assistance`
  ADD PRIMARY KEY (`ta_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `dimension_id` (`dimension_id`),
  ADD KEY `sdo_user_id` (`sdo_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

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

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `ann_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `region_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  MODIFY `phase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_workflow_status`
--
ALTER TABLE `school_workflow_status`
  MODIFY `wf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sh_indicator_overrides`
--
ALTER TABLE `sh_indicator_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stakeholder_responses`
--
ALTER TABLE `stakeholder_responses`
  MODIFY `sr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stakeholder_submissions`
--
ALTER TABLE `stakeholder_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ta_requests`
--
ALTER TABLE `ta_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technical_assistance`
--
ALTER TABLE `technical_assistance`
  MODIFY `ta_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  MODIFY `cp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `divisions`
--
ALTER TABLE `divisions`
  ADD CONSTRAINT `divisions_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`region_id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_periods`
--
ALTER TABLE `grading_periods`
  ADD CONSTRAINT `grading_periods_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

--
-- Constraints for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  ADD CONSTRAINT `improvement_plans_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `improvement_plans_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `improvement_plans_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`),
  ADD CONSTRAINT `improvement_plans_ibfk_4` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `improvement_plans_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD CONSTRAINT `ml_predictions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_predictions_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_predictions_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ml_predictions_ibfk_4` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE SET NULL;

--
-- Constraints for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  ADD CONSTRAINT `sbm_cycles_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`),
  ADD CONSTRAINT `sbm_cycles_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_cycles_ibfk_3` FOREIGN KEY (`validated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  ADD CONSTRAINT `sbm_dimension_scores_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_dimension_scores_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_dimension_scores_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`);

--
-- Constraints for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  ADD CONSTRAINT `sbm_indicators_ibfk_1` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`);

--
-- Constraints for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  ADD CONSTRAINT `sbm_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `sbm_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_responses_ibfk_4` FOREIGN KEY (`rated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  ADD CONSTRAINT `sbm_workflow_phases_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

--
-- Constraints for table `schools`
--
ALTER TABLE `schools`
  ADD CONSTRAINT `schools_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`division_id`) ON DELETE CASCADE;

--
-- Constraints for table `school_workflow_status`
--
ALTER TABLE `school_workflow_status`
  ADD CONSTRAINT `school_workflow_status_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `school_workflow_status_ibfk_2` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

--
-- Constraints for table `sh_indicator_overrides`
--
ALTER TABLE `sh_indicator_overrides`
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sh_indicator_overrides_ibfk_4` FOREIGN KEY (`overridden_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `stakeholder_responses`
--
ALTER TABLE `stakeholder_responses`
  ADD CONSTRAINT `stakeholder_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `stakeholder_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_responses_ibfk_4` FOREIGN KEY (`stakeholder_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `stakeholder_submissions`
--
ALTER TABLE `stakeholder_submissions`
  ADD CONSTRAINT `stakeholder_submissions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_submissions_ibfk_2` FOREIGN KEY (`stakeholder_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_submissions_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stakeholder_submissions_ibfk_4` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

--
-- Constraints for table `ta_requests`
--
ALTER TABLE `ta_requests`
  ADD CONSTRAINT `ta_requests_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ta_requests_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ta_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `ta_requests_ibfk_4` FOREIGN KEY (`sdo_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  ADD CONSTRAINT `teacher_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `teacher_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_responses_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  ADD CONSTRAINT `teacher_submissions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_submissions_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ts_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ts_sy_fk` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

--
-- Constraints for table `technical_assistance`
--
ALTER TABLE `technical_assistance`
  ADD CONSTRAINT `technical_assistance_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `technical_assistance_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `technical_assistance_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `technical_assistance_ibfk_4` FOREIGN KEY (`sdo_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  ADD CONSTRAINT `workflow_checkpoints_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_checkpoints_ibfk_2` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_checkpoints_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
