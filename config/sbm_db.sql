-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 07:43 PM
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
(81, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 09:56:46'),
(82, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 10:05:29'),
(83, 37, 'init_workflow', 'workflow', 'Initialized workflow for SY 4', '::1', '2026-03-29 10:38:33'),
(84, 37, 'override_assignments', 'school_head', 'SH override for teacher ID 2. Prev: []. New: []. Reason: Test', '::1', '2026-03-29 11:06:19'),
(85, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:17:46'),
(86, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 1', '::1', '2026-03-29 11:20:30'),
(87, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:20:41'),
(88, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:21:16'),
(89, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:23:53'),
(90, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:24:23'),
(91, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:24:33'),
(92, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 1', '::1', '2026-03-29 11:26:51'),
(93, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:27:02'),
(94, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 1', '::1', '2026-03-29 11:27:40'),
(95, 13, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:27:46'),
(96, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 1', '::1', '2026-03-29 11:28:25'),
(97, 14, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:28:30'),
(98, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 1', '::1', '2026-03-29 11:29:10'),
(99, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:29:23'),
(100, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 1', '::1', '2026-03-29 11:30:03'),
(101, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:30:22'),
(102, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 02:22:43'),
(103, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 03:01:09'),
(104, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-03-30 03:48:02'),
(105, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 03:48:21'),
(106, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 03:57:59'),
(107, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:43:05'),
(108, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:43:32'),
(109, 37, 'create_user', 'users', 'Created: Jr', '::1', '2026-03-30 05:44:36'),
(110, 37, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 05:45:27'),
(111, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:46:01'),
(112, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:52:45'),
(113, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 06:05:24'),
(114, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 06:05:29'),
(115, 37, 'create_user', 'users', 'Created: JuanJuan', '::1', '2026-03-30 06:06:38'),
(116, 37, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 06:07:20'),
(117, 39, 'login', 'auth', 'User logged in', '::1', '2026-03-30 06:07:53'),
(118, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 08:58:53'),
(119, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 08:59:08'),
(120, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:21:41'),
(121, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:21:56'),
(122, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:22:03'),
(123, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:34:01'),
(124, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:53:56'),
(125, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:22:34'),
(126, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:23:10'),
(127, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:24:12'),
(128, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:25:11'),
(129, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:25:19'),
(130, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:26:26'),
(131, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:27:52'),
(132, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:28:33'),
(133, 37, 'update_user', 'users', 'Updated user ID:39', '::1', '2026-03-30 10:28:54'),
(134, 39, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:29:07'),
(135, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:33:41'),
(136, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:33:50'),
(137, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:38:24'),
(138, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:43:33'),
(139, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:43:48'),
(140, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:1', '::1', '2026-03-30 11:43:57'),
(141, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:44:07'),
(142, 36, 'sh_update_school_profile', 'school_profile', 'School Head updated school profile for school_id: 1', '::1', '2026-03-30 11:48:03'),
(143, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:48:40'),
(144, 37, 'delete_user', 'users', 'Deleted user ID:38', '::1', '2026-03-30 11:49:12'),
(145, 37, 'create_user', 'users', 'Created: Rol', '::1', '2026-03-30 12:16:11'),
(146, 37, 'delete_user', 'users', 'Deleted user ID:40', '::1', '2026-03-30 12:17:02'),
(147, 37, 'create_user', 'users', 'Created: Jr', '::1', '2026-03-30 12:18:06'),
(148, 37, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 12:23:31'),
(149, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 12:23:49'),
(150, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 12:24:16'),
(151, 37, 'delete_user', 'users', 'Deleted user ID:43', '::1', '2026-03-30 13:28:40'),
(152, 37, 'create_user', 'users', 'Created: Jr', '::1', '2026-03-30 13:29:17'),
(153, 37, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 13:31:48'),
(154, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 13:39:22'),
(155, 37, 'delete_user', 'users', 'Deleted user ID:44', '::1', '2026-03-30 13:39:35'),
(156, 37, 'create_user', 'users', 'Created: Jr', '::1', '2026-03-30 13:40:53'),
(157, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 13:42:08'),
(158, 45, 'login', 'auth', 'User logged in', '::1', '2026-03-30 13:47:43'),
(159, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:32:26'),
(160, 45, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:33:19'),
(161, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:33:24'),
(162, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:36:42'),
(163, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 23:39:26'),
(164, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-31 02:40:12'),
(165, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 03:10:29'),
(166, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-31 03:23:38'),
(167, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 03:27:07'),
(168, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-03-31 17:29:59'),
(169, 36, 'login', 'auth', 'User logged in', '::1', '2026-03-31 17:32:24'),
(170, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 17:34:22');

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
(12, 36, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-29 07:25:16'),
(13, 36, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-30 03:46:22'),
(14, 36, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-30 03:58:21'),
(15, 36, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-30 05:32:58'),
(16, 36, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-30 05:37:00'),
(17, 36, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-30 05:38:06'),
(22, 39, 'account_creation', 'dozenjames54@gmail.com', 'sent', NULL, '2026-03-30 06:06:43'),
(26, 45, 'account_creation', 'mendozacharles11011@gmail.com', 'sent', NULL, '2026-03-30 13:41:33'),
(27, 36, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-03-31 17:27:52');

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

--
-- Dumping data for table `improvement_plans`
--

INSERT INTO `improvement_plans` (`plan_id`, `school_id`, `cycle_id`, `dimension_id`, `indicator_id`, `priority_level`, `objective`, `strategy`, `person_responsible`, `target_date`, `resources_needed`, `expected_output`, `status`, `remarks`, `created_by`, `created_at`) VALUES
(1, 1, 1, 4, 28, 'High', 'Improve performance on indicator 4.6: Transparency board and public financial disclosures are updated and accessible.', 'Develop targeted interventions to address areas rated \'Not Yet Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(2, 1, 1, 6, 36, 'High', 'Improve performance on indicator 6.1: School facilities inventory is updated and submitted on time.', 'Develop targeted interventions to address areas rated \'Not Yet Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(3, 1, 1, 2, NULL, 'High', 'Improve performance on indicator 2.1: The school has a zero-bullying policy that is implemented, monitored, and updated regularly.', 'Develop targeted interventions to address areas rated \'Not Yet Manifested\'. Identify root causes, allocate resources, and monitor progress.', '', NULL, '', '', 'planned', NULL, 36, '2026-03-29 11:30:42'),
(4, 1, 1, 4, 25, 'Medium', 'Improve performance on indicator 4.3: Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(5, 1, 1, 4, 27, 'Medium', 'Improve performance on indicator 4.5: Stakeholder satisfaction survey is conducted and results are used for improvement.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(6, 1, 1, 4, 23, 'Medium', 'Improve performance on indicator 4.1: School Governance Council (SGC) records are complete, updated, and actions are documented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(7, 1, 1, 1, 2, 'Medium', 'Improve performance on indicator 1.2: Learner proficiency rate in Grade 6 meets or exceeds the national target.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(8, 1, 1, 1, 4, 'Medium', 'Improve performance on indicator 1.4: Learner proficiency rate in Grade 12 or ALS completion rate meets or exceeds the national target.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(9, 1, 1, 1, 6, 'Medium', 'Improve performance on indicator 1.6: Contextualized and localized learning materials (LM) are developed and used by teachers.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(10, 1, 1, 1, 7, 'Medium', 'Improve performance on indicator 1.7: Remediation, enhancement, and intervention programs are implemented for at-risk learners.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(11, 1, 1, 1, 8, 'Medium', 'Improve performance on indicator 1.8: TLE/TVL programs have active industry partnerships and produce certified graduates.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(12, 1, 1, 6, 38, 'Medium', 'Improve performance on indicator 6.3: Water, electricity, and internet utilities are functional and adequate.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(13, 1, 1, 5, 30, 'Medium', 'Improve performance on indicator 5.2: Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(14, 1, 1, 5, 31, 'Medium', 'Improve performance on indicator 5.3: Teachers participate in professional development activities (trainings, seminars, scholarships).', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(15, 1, 1, 5, 34, 'Medium', 'Improve performance on indicator 5.6: HR development programs for non-teaching staff are implemented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(16, 1, 1, 5, 35, 'Medium', 'Improve performance on indicator 5.7: Succession planning and talent management practices are in place.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(17, 1, 1, 3, 20, 'Medium', 'Improve performance on indicator 3.2: A school-community planning team is established and functional.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(18, 1, 1, 3, 21, 'Medium', 'Improve performance on indicator 3.3: SSG/SPG is organized, trained, and actively implements programs.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(19, 1, 1, 3, 22, 'Medium', 'Improve performance on indicator 3.4: The school head implements innovations in frontline service delivery.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(20, 1, 1, 2, 14, 'Medium', 'Improve performance on indicator 2.6: A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(21, 1, 1, 2, 17, 'Medium', 'Improve performance on indicator 2.9: Safe school environment audit is conducted and findings are addressed.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(22, 1, 1, 2, 12, 'Medium', 'Improve performance on indicator 2.4: School activities are culture-sensitive, inclusive, and respectful of learner diversity.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 36, '2026-03-29 11:30:42'),
(23, 1, 1, 2, 9, 'High', 'Improve performance on indicator 2.1: The school has a zero-bullying policy that is implemented, monitored, and updated regularly.', 'Develop targeted interventions to address areas rated \'Not Yet Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 37, '2026-03-30 16:04:44');

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

--
-- Dumping data for table `ml_comment_analysis`
--

INSERT INTO `ml_comment_analysis` (`analysis_id`, `cycle_id`, `indicator_id`, `dimension_id`, `comment_count`, `sentiment_pos`, `sentiment_neg`, `sentiment_neu`, `top_topics`, `has_urgent`, `urgency_details`, `generated_at`) VALUES
(1, 1, NULL, NULL, 6, 0, 0, 6, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-29 11:30:06'),
(2, 1, NULL, NULL, 6, 1, 3, 2, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-29 11:30:06'),
(3, 1, NULL, NULL, 6, 0, 0, 6, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-29 11:30:48'),
(4, 1, NULL, NULL, 6, 1, 3, 2, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-29 11:30:48'),
(5, 1, NULL, NULL, 6, 0, 0, 6, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-30 16:04:17'),
(6, 1, NULL, NULL, 6, 1, 3, 2, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-30 16:04:17'),
(7, 1, NULL, 2, 6, 0, 0, 6, '[]', 0, NULL, '2026-03-31 17:32:58'),
(8, 1, NULL, NULL, 6, 0, 0, 6, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-31 17:32:58'),
(9, 1, NULL, NULL, 6, 0, 0, 6, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-31 17:34:01'),
(10, 1, NULL, NULL, 6, 1, 3, 2, '[\"teacher_quality\",\"bullying\"]', 0, NULL, '2026-03-31 17:34:01');

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

--
-- Dumping data for table `ml_predictions`
--

INSERT INTO `ml_predictions` (`pred_id`, `school_id`, `cycle_id`, `dimension_id`, `indicator_id`, `prediction_type`, `predicted_value`, `risk_level`, `recommendation`, `confidence_score`, `created_at`) VALUES
(1, 1, 1, NULL, NULL, 'risk_flag', 55.83, 'medium', 'Dimension 4 (Accountability and Continuous Improvement) is at 55.83% (Maturing level). Gap from average: 5.1%.', 0.750, '2026-03-31 17:32:58');

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

--
-- Dumping data for table `ml_recommendations`
--

INSERT INTO `ml_recommendations` (`rec_id`, `cycle_id`, `recommendation_text`, `generated_by`, `top_topics`, `has_urgent`, `sentiment_summary`, `generated_at`) VALUES
(6, 1, '[Assessment Overview]\nDasmariñas Integrated High School achieved an overall SBM score of 60.81% with a maturity level of Maturing, marking its first assessment cycle with no prior data available for comparison, as per DepEd Order No. 007, s. 2024.\n\n[Priority Recommendations]\n1. [4.6] The School Head shall ensure the transparency board and public financial disclosures are updated and accessible by conducting a monthly review and update of the transparency board, starting from the first week of August 2026, to achieve a rating of at least 3 (Developing) by the end of the school year.\n2. [2.1] The School Head, in coordination with the School Guidance Counselor, shall develop and implement a zero-bullying policy by September 2026, which will be monitored and updated regularly, with a target of reducing bullying incidents by 50% by the end of the school year, as mandated by DepEd Order No. 007, s. 2024.\n3. [6.1] The School Administrator shall lead the updating and submission of the school facilities inventory on time, starting with a thorough inventory count by October 2026, to ensure compliance with DepEd regulations and achieve a rating of at least 3 (Developing) by the end of the school year.\n4. [2.6] The School Disaster Risk Reduction and Management (DRRM) Team shall formulate, practice, and update the DRRM plan by November 2026, with a target of conducting at least two DRRM drills per semester, to enhance the school\'s preparedness and response to disasters, in accordance with DepEd Order No. 007, s. 2024.\n5. [1.2] The School Head, in coordination with the Curriculum Team, shall develop and implement remediation programs for at-risk learners in Grade 6 by January 2027, with a target of improving the learner proficiency rate to meet or exceed the national target, as outlined in DepEd Order No. 007, s. 2024.\n\n[Stakeholder Focus]\nGiven the stakeholder remarks on teacher quality and bullying, the School Head shall conduct a Teacher Quality Assessment and Development Program by February 2027, which will include training on bullying prevention and response, to address the concerns and improve teacher quality, as mandated by DepEd Order No. 007, s. 2024.', 'groq', '[\"teacher_quality\",\"bullying\"]', 0, '{\"negative\":3,\"neutral\":2,\"positive\":1}', '2026-03-31 17:34:01');

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

--
-- Dumping data for table `ml_training_snapshots`
--

INSERT INTO `ml_training_snapshots` (`snapshot_id`, `school_id`, `cycle_id`, `dim_scores`, `indicator_ratings`, `overall_score`, `maturity_level`, `created_at`) VALUES
(1, 1, 1, '[{\"dimension_name\":\"Accountability and Continuous Improvement\",\"dimension_no\":4,\"gap_from_avg\":4.98,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":55.83,\"weight\":1,\"weighted_gap\":4.98},{\"dimension_name\":\"Leadership and Governance\",\"dimension_no\":3,\"gap_from_avg\":1.02,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":59.79,\"weight\":1,\"weighted_gap\":1.02},{\"dimension_name\":\"Curriculum and Teaching\",\"dimension_no\":1,\"gap_from_avg\":0.81,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":60,\"weight\":1.2,\"weighted_gap\":0.97},{\"dimension_name\":\"Human Resource Development\",\"dimension_no\":5,\"gap_from_avg\":-0.62,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":61.43,\"weight\":0.9,\"weighted_gap\":-0.56},{\"dimension_name\":\"Learning Environment\",\"dimension_no\":2,\"gap_from_avg\":-1.73,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":62.54,\"weight\":1.2,\"weighted_gap\":-2.08},{\"dimension_name\":\"Finance and Resource Management\",\"dimension_no\":6,\"gap_from_avg\":-4.79,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":65.6,\"weight\":0.9,\"weighted_gap\":-4.31}]', '[]', 61.17, 'Maturing', '2026-03-29 10:41:28');

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
(19, 36, '4db483f27c408e63b53fd6ca91f75d5a6db512e0b869c65c5126511a00032b9f', 'setup', '2026-03-31 15:25:09', '2026-03-29 15:25:41', '2026-03-29 07:25:09'),
(20, 36, 'e558d888b4b1aa3aa43208e5c7177a5c5f3180314ecba73199e6a3b92723a655', 'reset', '2026-03-30 12:16:14', '2026-03-30 11:48:02', '2026-03-30 03:46:14'),
(21, 36, 'a0995de2f8a02c51b1f5eaf371549e5b2f183f8ee6e0a674d41e41ecb8f272b4', 'reset', '2026-03-30 12:28:15', '2026-03-30 13:32:52', '2026-03-30 03:58:15'),
(22, 36, '936950b6d3e8a3b98507c046204b2c1939b9273e8e6ded10e070e2f9ad6856ea', 'reset', '2026-03-30 14:02:52', '2026-03-30 13:36:53', '2026-03-30 05:32:52'),
(23, 36, '2ea78cec87cf2c683a7b857f837ffdb7fb8519e3c58ea2693b47581c6235ab89', 'reset', '2026-03-30 14:06:53', '2026-03-30 13:38:00', '2026-03-30 05:36:53'),
(24, 36, 'f785c11bab2e970bd80826909d8ae335b30223529ad027e5b3c5eeccd4df07de', 'reset', '2026-03-30 14:08:00', '2026-04-01 01:27:05', '2026-03-30 05:38:00'),
(29, 39, '42f3b92965811f4df5f33f7240873dd31f9580fc60087e5fdfc7ec57345ebdac', 'setup', '2026-04-01 14:06:38', '2026-03-30 14:07:20', '2026-03-30 06:06:38'),
(33, 45, 'e828482e1abc6ad51480eb2824c3f9908a96ca2a9b684f5801c9b576e652c417', 'setup', '2026-04-01 21:40:53', '2026-03-30 21:42:08', '2026-03-30 13:40:53'),
(34, 36, '7a5e01d4fa927ae6982dbfd90ad7a1ae7bdefa23feae889eda791a31a059f028', 'reset', '2026-04-01 01:57:05', '2026-04-01 01:29:59', '2026-03-31 17:27:05');

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
-- Table structure for table `response_attachments`
--

CREATE TABLE `response_attachments` (
  `attachment_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploader_role` varchar(40) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 4, 1, 'validated', 61.17, 'Maturing', '2026-03-29 16:13:02', '2026-03-29 19:30:03', '2026-03-30 19:43:57', 37, '', '2026-03-29 08:13:02');

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

--
-- Dumping data for table `sbm_dimension_scores`
--

INSERT INTO `sbm_dimension_scores` (`score_id`, `cycle_id`, `school_id`, `dimension_id`, `raw_score`, `max_score`, `percentage`, `computed_at`) VALUES
(1, 1, 1, 1, 19.20, 32.00, 60.00, '2026-03-29 11:30:03'),
(4, 1, 1, 2, 25.02, 40.00, 62.54, '2026-03-29 11:30:03'),
(8, 1, 1, 4, 13.40, 24.00, 55.83, '2026-03-29 11:30:03'),
(13, 1, 1, 6, 18.37, 28.00, 65.60, '2026-03-29 11:30:03'),
(17, 1, 1, 3, 9.57, 16.00, 59.79, '2026-03-29 11:30:03'),
(19, 1, 1, 5, 17.20, 28.00, 61.43, '2026-03-29 11:30:03');

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

--
-- Dumping data for table `sbm_responses`
--

INSERT INTO `sbm_responses` (`response_id`, `cycle_id`, `indicator_id`, `school_id`, `rating`, `evidence_text`, `rated_by`, `rated_at`) VALUES
(1, 1, 1, 1, 3, '', 37, '2026-03-29 11:29:32'),
(2, 1, 2, 1, 2, '', 37, '2026-03-29 11:29:32'),
(3, 1, 3, 1, 3, '', 37, '2026-03-29 11:29:34'),
(4, 1, 13, 1, 3, '', 37, '2026-03-29 11:29:43'),
(5, 1, 14, 1, 2, '', 37, '2026-03-29 11:29:45'),
(6, 1, 16, 1, 3, '', 37, '2026-03-29 11:29:47'),
(7, 1, 17, 1, 2, '', 37, '2026-03-29 11:29:47'),
(8, 1, 24, 1, 3, '', 37, '2026-03-29 11:29:52'),
(9, 1, 25, 1, 2, '', 37, '2026-03-29 11:29:52'),
(10, 1, 26, 1, 3, '', 37, '2026-03-29 11:29:53'),
(11, 1, 27, 1, 2, '', 37, '2026-03-29 11:29:54'),
(12, 1, 28, 1, 1, '', 37, '2026-03-29 11:29:55'),
(13, 1, 41, 1, 3, '', 37, '2026-03-29 11:30:01'),
(14, 1, 42, 1, 3, '', 37, '2026-03-29 11:30:02');

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
(1, 'Dasmariñas Integrated High School', '301143', 'Dasmariñas City, Cavite', 'JHS', 'Ryza Evangelio', '', '', 2500, 5, '2026-03-11 16:18:36', 1);

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
(1, 1, 4, 1, '2026-03-29 18:38:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'in_progress', NULL, '2026-03-29 10:38:33');

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
(4, '2026-2027', 1, '2026-07-13', '2027-04-26'),
(5, '2024-2025', 0, '2024-07-24', '2025-09-24');

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

--
-- Dumping data for table `teacher_responses`
--

INSERT INTO `teacher_responses` (`tr_id`, `cycle_id`, `indicator_id`, `school_id`, `teacher_id`, `rating`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 1, 15, 2, '', 'submitted', '2026-03-29 11:17:53', '2026-03-29 11:20:30'),
(2, 1, 5, 1, 15, 3, '', 'submitted', '2026-03-29 11:17:55', '2026-03-29 11:20:30'),
(3, 1, 6, 1, 15, 2, '', 'submitted', '2026-03-29 11:17:56', '2026-03-29 11:20:30'),
(4, 1, 7, 1, 15, 3, '', 'submitted', '2026-03-29 11:17:58', '2026-03-29 11:20:30'),
(5, 1, 8, 1, 15, 2, '', 'submitted', '2026-03-29 11:17:59', '2026-03-29 11:20:30'),
(6, 1, 9, 1, 15, 1, '', 'submitted', '2026-03-29 11:18:01', '2026-03-29 11:20:30'),
(7, 1, 9, 1, 15, 1, '', 'submitted', '2026-03-29 11:18:22', '2026-03-29 11:20:30'),
(8, 1, 9, 1, 15, 2, '', 'submitted', '2026-03-29 11:18:22', '2026-03-29 11:20:30'),
(9, 1, 9, 1, 15, 2, 'the teachers are trying to monitor each cases and trying to eridicate it', 'submitted', '2026-03-29 11:18:49', '2026-03-29 11:20:30'),
(10, 1, 10, 1, 15, 3, '', 'submitted', '2026-03-29 11:18:49', '2026-03-29 11:20:30'),
(11, 1, 11, 1, 15, 3, '', 'submitted', '2026-03-29 11:18:50', '2026-03-29 11:20:30'),
(12, 1, 10, 1, 15, 3, '', 'submitted', '2026-03-29 11:18:52', '2026-03-29 11:20:30'),
(13, 1, 10, 1, 15, 2, '', 'submitted', '2026-03-29 11:18:52', '2026-03-29 11:20:30'),
(14, 1, 10, 1, 15, 2, '', 'submitted', '2026-03-29 11:19:03', '2026-03-29 11:20:30'),
(15, 1, 10, 1, 15, 4, '', 'submitted', '2026-03-29 11:19:04', '2026-03-29 11:20:30'),
(16, 1, 10, 1, 15, 4, 'by doing a seminars and also activities, we, the teachers and faculties help to lower the drop rate', 'submitted', '2026-03-29 11:19:43', '2026-03-29 11:20:30'),
(17, 1, 12, 1, 15, 3, '', 'submitted', '2026-03-29 11:19:43', '2026-03-29 11:20:30'),
(18, 1, 15, 1, 15, 2, '', 'submitted', '2026-03-29 11:19:45', '2026-03-29 11:20:30'),
(19, 1, 12, 1, 15, 3, '', 'submitted', '2026-03-29 11:19:47', '2026-03-29 11:20:30'),
(20, 1, 12, 1, 15, 2, '', 'submitted', '2026-03-29 11:19:47', '2026-03-29 11:20:30'),
(21, 1, 12, 1, 15, 2, 'many case of bullies because of differences', 'submitted', '2026-03-29 11:20:08', '2026-03-29 11:20:30'),
(22, 1, 18, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:08', '2026-03-29 11:20:30'),
(23, 1, 19, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:09', '2026-03-29 11:20:30'),
(24, 1, 20, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:10', '2026-03-29 11:20:30'),
(25, 1, 21, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:11', '2026-03-29 11:20:30'),
(26, 1, 22, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:12', '2026-03-29 11:20:30'),
(27, 1, 23, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:14', '2026-03-29 11:20:30'),
(28, 1, 29, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:16', '2026-03-29 11:20:30'),
(29, 1, 30, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:17', '2026-03-29 11:20:30'),
(30, 1, 31, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:18', '2026-03-29 11:20:30'),
(31, 1, 32, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:19', '2026-03-29 11:20:30'),
(32, 1, 33, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:20', '2026-03-29 11:20:30'),
(33, 1, 34, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:22', '2026-03-29 11:20:30'),
(34, 1, 35, 1, 15, 1, '', 'submitted', '2026-03-29 11:20:23', '2026-03-29 11:20:30'),
(35, 1, 36, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:24', '2026-03-29 11:20:30'),
(36, 1, 37, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:25', '2026-03-29 11:20:30'),
(37, 1, 38, 1, 15, 2, '', 'submitted', '2026-03-29 11:20:27', '2026-03-29 11:20:30'),
(38, 1, 39, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:28', '2026-03-29 11:20:30'),
(39, 1, 40, 1, 15, 3, '', 'submitted', '2026-03-29 11:20:28', '2026-03-29 11:20:30'),
(40, 1, 4, 1, 2, 2, '', 'submitted', '2026-03-29 11:24:37', '2026-03-29 11:26:51'),
(41, 1, 5, 1, 2, 3, '', 'submitted', '2026-03-29 11:24:39', '2026-03-29 11:26:51'),
(42, 1, 6, 1, 2, 2, '', 'submitted', '2026-03-29 11:24:40', '2026-03-29 11:26:51'),
(43, 1, 7, 1, 2, 2, '', 'submitted', '2026-03-29 11:24:41', '2026-03-29 11:26:51'),
(44, 1, 8, 1, 2, 2, '', 'submitted', '2026-03-29 11:24:42', '2026-03-29 11:26:51'),
(45, 1, 9, 1, 2, 2, '', 'submitted', '2026-03-29 11:24:44', '2026-03-29 11:26:51'),
(46, 1, 9, 1, 2, 2, 'many cases of bullying is still ocurring but with the hwlp of other teachers, we\'re trying to make the cases lower.', 'submitted', '2026-03-29 11:25:22', '2026-03-29 11:26:51'),
(47, 1, 10, 1, 2, 2, '', 'submitted', '2026-03-29 11:25:22', '2026-03-29 11:26:51'),
(48, 1, 10, 1, 2, 2, 'still has many cases of dropouts, but with the help of school activities we\'re trying to lower it.', 'submitted', '2026-03-29 11:26:04', '2026-03-29 11:26:51'),
(49, 1, 11, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:04', '2026-03-29 11:26:51'),
(50, 1, 12, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:06', '2026-03-29 11:26:51'),
(51, 1, 12, 1, 2, 2, 'many cases of bullying because of indifferences', 'submitted', '2026-03-29 11:26:27', '2026-03-29 11:26:51'),
(52, 1, 15, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:27', '2026-03-29 11:26:51'),
(53, 1, 18, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:28', '2026-03-29 11:26:51'),
(54, 1, 19, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:29', '2026-03-29 11:26:51'),
(55, 1, 20, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:31', '2026-03-29 11:26:51'),
(56, 1, 21, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:32', '2026-03-29 11:26:51'),
(57, 1, 22, 1, 2, 1, '', 'submitted', '2026-03-29 11:26:33', '2026-03-29 11:26:51'),
(58, 1, 23, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:34', '2026-03-29 11:26:51'),
(59, 1, 29, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:37', '2026-03-29 11:26:51'),
(60, 1, 30, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:38', '2026-03-29 11:26:51'),
(61, 1, 31, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:38', '2026-03-29 11:26:51'),
(62, 1, 32, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:40', '2026-03-29 11:26:51'),
(63, 1, 33, 1, 2, 4, '', 'submitted', '2026-03-29 11:26:41', '2026-03-29 11:26:51'),
(64, 1, 34, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:42', '2026-03-29 11:26:51'),
(65, 1, 35, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:43', '2026-03-29 11:26:51'),
(66, 1, 36, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:44', '2026-03-29 11:26:51'),
(67, 1, 37, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:46', '2026-03-29 11:26:51'),
(68, 1, 38, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:47', '2026-03-29 11:26:51'),
(69, 1, 39, 1, 2, 3, '', 'submitted', '2026-03-29 11:26:48', '2026-03-29 11:26:51'),
(70, 1, 40, 1, 2, 2, '', 'submitted', '2026-03-29 11:26:49', '2026-03-29 11:26:51'),
(71, 1, 4, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:06', '2026-03-29 11:27:40'),
(72, 1, 5, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:07', '2026-03-29 11:27:40'),
(73, 1, 6, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:08', '2026-03-29 11:27:40'),
(74, 1, 7, 1, 12, 1, '', 'submitted', '2026-03-29 11:27:09', '2026-03-29 11:27:40'),
(75, 1, 8, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:10', '2026-03-29 11:27:40'),
(76, 1, 9, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:11', '2026-03-29 11:27:40'),
(77, 1, 10, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:13', '2026-03-29 11:27:40'),
(78, 1, 10, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:14', '2026-03-29 11:27:40'),
(79, 1, 11, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:15', '2026-03-29 11:27:40'),
(80, 1, 12, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:16', '2026-03-29 11:27:40'),
(81, 1, 15, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:17', '2026-03-29 11:27:40'),
(82, 1, 18, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:19', '2026-03-29 11:27:40'),
(83, 1, 19, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:20', '2026-03-29 11:27:40'),
(84, 1, 20, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:21', '2026-03-29 11:27:40'),
(85, 1, 21, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:22', '2026-03-29 11:27:40'),
(86, 1, 22, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:23', '2026-03-29 11:27:40'),
(87, 1, 23, 1, 12, 1, '', 'submitted', '2026-03-29 11:27:24', '2026-03-29 11:27:40'),
(88, 1, 29, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:25', '2026-03-29 11:27:40'),
(89, 1, 30, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:27', '2026-03-29 11:27:40'),
(90, 1, 31, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:27', '2026-03-29 11:27:40'),
(91, 1, 32, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:29', '2026-03-29 11:27:40'),
(92, 1, 33, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:30', '2026-03-29 11:27:40'),
(93, 1, 34, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:31', '2026-03-29 11:27:40'),
(94, 1, 35, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:32', '2026-03-29 11:27:40'),
(95, 1, 36, 1, 12, 1, '', 'submitted', '2026-03-29 11:27:33', '2026-03-29 11:27:40'),
(96, 1, 37, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:34', '2026-03-29 11:27:40'),
(97, 1, 38, 1, 12, 3, '', 'submitted', '2026-03-29 11:27:36', '2026-03-29 11:27:40'),
(98, 1, 39, 1, 12, 2, '', 'submitted', '2026-03-29 11:27:37', '2026-03-29 11:27:40'),
(99, 1, 40, 1, 12, 4, '', 'submitted', '2026-03-29 11:27:38', '2026-03-29 11:27:40'),
(100, 1, 4, 1, 13, 3, '', 'submitted', '2026-03-29 11:27:50', '2026-03-29 11:28:25'),
(101, 1, 5, 1, 13, 2, '', 'submitted', '2026-03-29 11:27:51', '2026-03-29 11:28:25'),
(102, 1, 6, 1, 13, 2, '', 'submitted', '2026-03-29 11:27:53', '2026-03-29 11:28:25'),
(103, 1, 7, 1, 13, 2, '', 'submitted', '2026-03-29 11:27:53', '2026-03-29 11:28:25'),
(104, 1, 8, 1, 13, 2, '', 'submitted', '2026-03-29 11:27:55', '2026-03-29 11:28:25'),
(105, 1, 9, 1, 13, 2, '', 'submitted', '2026-03-29 11:27:57', '2026-03-29 11:28:25'),
(106, 1, 10, 1, 13, 3, '', 'submitted', '2026-03-29 11:27:58', '2026-03-29 11:28:25'),
(107, 1, 10, 1, 13, 2, '', 'submitted', '2026-03-29 11:27:59', '2026-03-29 11:28:25'),
(108, 1, 11, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:00', '2026-03-29 11:28:25'),
(109, 1, 12, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:01', '2026-03-29 11:28:25'),
(110, 1, 15, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:02', '2026-03-29 11:28:25'),
(111, 1, 18, 1, 13, 1, '', 'submitted', '2026-03-29 11:28:03', '2026-03-29 11:28:25'),
(112, 1, 19, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:05', '2026-03-29 11:28:25'),
(113, 1, 20, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:06', '2026-03-29 11:28:25'),
(114, 1, 21, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:07', '2026-03-29 11:28:25'),
(115, 1, 22, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:08', '2026-03-29 11:28:25'),
(116, 1, 23, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:10', '2026-03-29 11:28:25'),
(117, 1, 29, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:11', '2026-03-29 11:28:25'),
(118, 1, 30, 1, 13, 1, '', 'submitted', '2026-03-29 11:28:12', '2026-03-29 11:28:25'),
(119, 1, 31, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:13', '2026-03-29 11:28:25'),
(120, 1, 32, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:14', '2026-03-29 11:28:25'),
(121, 1, 33, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:15', '2026-03-29 11:28:25'),
(122, 1, 34, 1, 13, 1, '', 'submitted', '2026-03-29 11:28:16', '2026-03-29 11:28:25'),
(123, 1, 35, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:18', '2026-03-29 11:28:25'),
(124, 1, 36, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:19', '2026-03-29 11:28:25'),
(125, 1, 37, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:20', '2026-03-29 11:28:25'),
(126, 1, 38, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:21', '2026-03-29 11:28:25'),
(127, 1, 39, 1, 13, 3, '', 'submitted', '2026-03-29 11:28:22', '2026-03-29 11:28:25'),
(128, 1, 40, 1, 13, 2, '', 'submitted', '2026-03-29 11:28:24', '2026-03-29 11:28:25'),
(129, 1, 4, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:34', '2026-03-29 11:29:10'),
(130, 1, 5, 1, 14, 3, '', 'submitted', '2026-03-29 11:28:35', '2026-03-29 11:29:10'),
(131, 1, 6, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:36', '2026-03-29 11:29:10'),
(132, 1, 7, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:38', '2026-03-29 11:29:10'),
(133, 1, 8, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:40', '2026-03-29 11:29:10'),
(134, 1, 9, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:41', '2026-03-29 11:29:10'),
(135, 1, 10, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:42', '2026-03-29 11:29:10'),
(136, 1, 11, 1, 14, 3, '', 'submitted', '2026-03-29 11:28:43', '2026-03-29 11:29:10'),
(137, 1, 12, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:44', '2026-03-29 11:29:10'),
(138, 1, 15, 1, 14, 3, '', 'submitted', '2026-03-29 11:28:46', '2026-03-29 11:29:10'),
(139, 1, 18, 1, 14, 3, '', 'submitted', '2026-03-29 11:28:46', '2026-03-29 11:29:10'),
(140, 1, 19, 1, 14, 4, '', 'submitted', '2026-03-29 11:28:49', '2026-03-29 11:29:10'),
(141, 1, 20, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:50', '2026-03-29 11:29:10'),
(142, 1, 21, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:51', '2026-03-29 11:29:10'),
(143, 1, 21, 1, 14, 1, '', 'submitted', '2026-03-29 11:28:51', '2026-03-29 11:29:10'),
(144, 1, 22, 1, 14, 3, '', 'submitted', '2026-03-29 11:28:52', '2026-03-29 11:29:10'),
(145, 1, 23, 1, 14, 4, '', 'submitted', '2026-03-29 11:28:54', '2026-03-29 11:29:10'),
(146, 1, 29, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:55', '2026-03-29 11:29:10'),
(147, 1, 30, 1, 14, 3, '', 'submitted', '2026-03-29 11:28:57', '2026-03-29 11:29:10'),
(148, 1, 31, 1, 14, 2, '', 'submitted', '2026-03-29 11:28:58', '2026-03-29 11:29:10'),
(149, 1, 32, 1, 14, 4, '', 'submitted', '2026-03-29 11:28:59', '2026-03-29 11:29:10'),
(150, 1, 33, 1, 14, 2, '', 'submitted', '2026-03-29 11:29:00', '2026-03-29 11:29:10'),
(151, 1, 34, 1, 14, 3, '', 'submitted', '2026-03-29 11:29:01', '2026-03-29 11:29:10'),
(152, 1, 35, 1, 14, 2, '', 'submitted', '2026-03-29 11:29:02', '2026-03-29 11:29:10'),
(153, 1, 36, 1, 14, 1, '', 'submitted', '2026-03-29 11:29:03', '2026-03-29 11:29:10'),
(154, 1, 37, 1, 14, 3, '', 'submitted', '2026-03-29 11:29:04', '2026-03-29 11:29:10'),
(155, 1, 38, 1, 14, 2, '', 'submitted', '2026-03-29 11:29:05', '2026-03-29 11:29:10'),
(156, 1, 39, 1, 14, 4, '', 'submitted', '2026-03-29 11:29:06', '2026-03-29 11:29:10'),
(157, 1, 39, 1, 14, 4, '', 'submitted', '2026-03-29 11:29:06', '2026-03-29 11:29:10'),
(158, 1, 40, 1, 14, 2, '', 'submitted', '2026-03-29 11:29:09', '2026-03-29 11:29:10');

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

--
-- Dumping data for table `teacher_submissions`
--

INSERT INTO `teacher_submissions` (`submission_id`, `cycle_id`, `school_id`, `sy_id`, `teacher_id`, `status`, `submitted_at`, `response_count`) VALUES
(1, 1, 1, 4, 15, 'submitted', '2026-03-29 19:20:30', 39),
(2, 1, 1, 4, 2, 'submitted', '2026-03-29 19:26:51', 31),
(3, 1, 1, 4, 12, 'submitted', '2026-03-29 19:27:40', 29),
(4, 1, 1, 4, 13, 'submitted', '2026-03-29 19:28:25', 29),
(5, 1, 1, 4, 14, 'submitted', '2026-03-29 19:29:10', 30);

-- --------------------------------------------------------

--
-- Table structure for table `users`
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

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `last_login`, `created_at`, `email_verified`, `reset_token`, `token_expiry`, `email_sent_at`, `force_password_change`) VALUES
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, '2026-03-29 19:24:33', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, '2026-03-29 19:27:02', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, '2026-03-29 19:27:46', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, '2026-03-29 19:28:30', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, '2026-03-30 18:33:41', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0),
(36, 'Pat', '$2y$10$Bz.RVHsaEZGhu6t2ui2/TeFvZAQN2Zs8PZei0z87CxFj/vVlBgzTK', 'ariascharles00@gmail.com', 'Charles Arias', 'sbm_coordinator', 'active', 1, '2026-04-01 01:32:24', '2026-03-29 07:25:09', 0, NULL, NULL, '2026-03-29 15:25:16', 0),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza Evangelio', 'school_head', 'active', 1, '2026-04-01 01:34:22', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0),
(39, 'JuanJuan', '$2y$10$DTAxyo7xI3N41L.H5RDzpemE64WFYAQv0gCh1w2dZEIs5kc9ix6Vu', 'dozenjames54@gmail.com', 'Juan Linaw', 'external_stakeholder', 'active', 1, '2026-03-30 18:29:07', '2026-03-30 06:06:38', 0, NULL, NULL, '2026-03-30 14:06:43', 0),
(45, 'Jr', '$2y$10$cC9.O/AxVHZV8BC29eO14.E68QIL6vLWR8UY7plPgOTrxeqbdOkvS', 'mendozacharles11011@gmail.com', 'Jr Rolito Billones', 'teacher', 'active', 1, '2026-03-30 22:33:19', '2026-03-30 13:40:53', 0, NULL, NULL, '2026-03-30 21:41:33', 0);

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
(1, 1, 4, 1, NULL, 'self_assessment', 'pending', '2026-04-28', NULL, NULL, NULL, '2026-03-29 10:38:33'),
(2, 1, 4, 1, NULL, 'planning', 'pending', '2026-05-13', NULL, NULL, NULL, '2026-03-29 10:38:33'),
(3, 1, 4, 2, 1, 'q1_monitoring', 'pending', '2026-06-27', NULL, NULL, NULL, '2026-03-29 10:38:33'),
(4, 1, 4, 2, 2, 'q2_monitoring', 'pending', '2026-08-26', NULL, NULL, NULL, '2026-03-29 10:38:33'),
(5, 1, 4, 2, 3, 'q3_monitoring', 'pending', '2026-10-25', NULL, NULL, NULL, '2026-03-29 10:38:33'),
(6, 1, 4, 3, NULL, 'completion', 'pending', '2026-12-24', NULL, NULL, NULL, '2026-03-29 10:38:33');

-- --------------------------------------------------------

--
-- Table structure for table `workflow_milestones`
--

CREATE TABLE `workflow_milestones` (
  `milestone_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `step_no` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Self-Assessment, 2=Validation, 3=Improvement',
  `status` enum('upcoming','in_progress','completed','delayed') NOT NULL DEFAULT 'upcoming',
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Indexes for table `response_attachments`
--
ALTER TABLE `response_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_cycle_indicator` (`cycle_id`,`indicator_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `indicator_id` (`indicator_id`);

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
-- Indexes for table `workflow_milestones`
--
ALTER TABLE `workflow_milestones`
  ADD PRIMARY KEY (`milestone_id`),
  ADD KEY `idx_sy_school` (`sy_id`,`school_id`),
  ADD KEY `idx_step` (`step_no`),
  ADD KEY `fk_wm_school` (`school_id`),
  ADD KEY `fk_wm_user` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `ann_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  MODIFY `analysis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `region_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `response_attachments`
--
ALTER TABLE `response_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  MODIFY `phase_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ta_requests`
--
ALTER TABLE `ta_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_indicator_assignments`
--
ALTER TABLE `teacher_indicator_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  MODIFY `cp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `workflow_milestones`
--
ALTER TABLE `workflow_milestones`
  MODIFY `milestone_id` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `divisions`
--
ALTER TABLE `divisions`
  ADD CONSTRAINT `divisions_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`region_id`);

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
-- Constraints for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  ADD CONSTRAINT `ml_comment_analysis_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD CONSTRAINT `ml_predictions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_predictions_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  ADD CONSTRAINT `ml_recommendations_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  ADD CONSTRAINT `ml_training_snapshots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_training_snapshots_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  ADD CONSTRAINT `password_setup_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `response_attachments`
--
ALTER TABLE `response_attachments`
  ADD CONSTRAINT `response_attachments_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `response_attachments_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `response_attachments_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  ADD CONSTRAINT `sbm_cycles_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
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
  ADD CONSTRAINT `sbm_indicators_ibfk_1` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`) ON DELETE CASCADE;

--
-- Constraints for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  ADD CONSTRAINT `sbm_responses_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_responses_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `sbm_responses_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sbm_responses_ibfk_4` FOREIGN KEY (`rated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  ADD CONSTRAINT `sbm_workflow_phases_ibfk_1` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `ta_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `teacher_indicator_assignments`
--
ALTER TABLE `teacher_indicator_assignments`
  ADD CONSTRAINT `teacher_indicator_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `teacher_indicator_assignments_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

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
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE SET NULL;

--
-- Constraints for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  ADD CONSTRAINT `workflow_checkpoints_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_checkpoints_ibfk_2` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_checkpoints_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `workflow_milestones`
--
ALTER TABLE `workflow_milestones`
  ADD CONSTRAINT `fk_wm_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wm_sy` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
