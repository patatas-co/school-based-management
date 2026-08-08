-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2026 at 04:10 PM
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
(64, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 07:26:05'),
(65, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:00:43'),
(67, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:04:28'),
(70, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:21:44'),
(72, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:23:42'),
(74, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 08:50:11'),
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
(88, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:21:16'),
(89, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:23:53'),
(90, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:24:23'),
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
(101, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-29 11:30:22'),
(102, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 02:22:43'),
(103, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 03:01:09'),
(104, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-03-30 03:48:02'),
(105, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 03:48:21'),
(106, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 03:57:59'),
(107, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:43:05'),
(108, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:43:32'),
(109, 37, 'create_user', 'users', 'Created: Jr', '::1', '2026-03-30 05:44:36'),
(110, 37, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 05:45:27'),
(111, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:46:01'),
(112, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 05:52:45'),
(113, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 06:05:24'),
(114, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 06:05:29'),
(115, 37, 'create_user', 'users', 'Created: JuanJuan', '::1', '2026-03-30 06:06:38'),
(116, 37, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-03-30 06:07:20'),
(117, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 06:07:53'),
(118, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 08:58:53'),
(119, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 08:59:08'),
(120, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:21:41'),
(121, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:21:56'),
(122, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:22:03'),
(123, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:34:01'),
(124, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 09:53:56'),
(125, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:22:34'),
(126, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:23:10'),
(127, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:24:12'),
(128, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:25:11'),
(129, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:25:19'),
(130, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:26:26'),
(131, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:27:52'),
(132, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:28:33'),
(133, 37, 'update_user', 'users', 'Updated user ID:39', '::1', '2026-03-30 10:28:54'),
(134, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:29:07'),
(135, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:33:41'),
(136, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 10:33:50'),
(137, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:38:24'),
(138, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:43:33'),
(139, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:43:48'),
(140, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:1', '::1', '2026-03-30 11:43:57'),
(141, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 11:44:07'),
(142, NULL, 'sh_update_school_profile', 'school_profile', 'School Head updated school profile for school_id: 1', '::1', '2026-03-30 11:48:03'),
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
(158, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 13:47:43'),
(159, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:32:26'),
(160, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:33:19'),
(161, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:33:24'),
(162, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 14:36:42'),
(163, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-30 23:39:26'),
(164, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-31 02:40:12'),
(165, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 03:10:29'),
(166, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-31 03:23:38'),
(167, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 03:27:07'),
(168, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-03-31 17:29:59'),
(169, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-31 17:32:24'),
(170, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 17:34:22'),
(171, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-31 17:47:37'),
(172, NULL, 'login', 'auth', 'User logged in', '::1', '2026-03-31 18:02:33'),
(173, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 19:23:32'),
(174, 37, 'login', 'auth', 'User logged in', '::1', '2026-03-31 19:26:27'),
(175, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 00:17:47'),
(176, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 01:34:09'),
(177, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 01:55:30'),
(178, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 01:56:30'),
(179, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-04-01 02:32:33'),
(180, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:33:11'),
(181, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:33:20'),
(182, 37, 'delete_user', 'users', 'Deleted user ID:36', '::1', '2026-04-01 02:34:17'),
(183, 37, 'delete_user', 'users', 'Deleted user ID:45', '::1', '2026-04-01 02:34:19'),
(184, 37, 'create_user', 'users', 'Created: Charles', '::1', '2026-04-01 02:35:08'),
(185, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-01 02:40:39'),
(186, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:40:47'),
(187, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:41:07'),
(188, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:41:26'),
(189, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-01 02:41:50'),
(190, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:42:09'),
(191, 15, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 9 cycle 2', '::1', '2026-04-01 02:48:45'),
(192, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 2', '::1', '2026-04-01 02:49:40'),
(193, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:50:07'),
(194, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:51:13'),
(195, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 2', '::1', '2026-04-01 02:52:36'),
(196, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:52:42'),
(197, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:53:20'),
(198, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 2', '::1', '2026-04-01 02:54:49'),
(199, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:54:56'),
(200, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 2', '::1', '2026-04-01 02:55:42'),
(201, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:55:54'),
(202, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 2', '::1', '2026-04-01 02:56:38'),
(203, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:56:55'),
(204, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 02:59:54'),
(205, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:00:13'),
(206, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:02:29'),
(207, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:03:14'),
(208, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:03:29'),
(209, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-01 03:03:39'),
(210, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:03:53'),
(211, 15, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 9 cycle 3', '::1', '2026-04-01 03:05:06'),
(212, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 3', '::1', '2026-04-01 03:05:35'),
(213, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:05:45'),
(214, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:05:51'),
(215, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:06:13'),
(216, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:06:21'),
(217, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 3', '::1', '2026-04-01 03:07:41'),
(218, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:07:47'),
(219, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:07:56'),
(220, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 3', '::1', '2026-04-01 03:08:41'),
(221, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:08:50'),
(222, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 3', '::1', '2026-04-01 03:09:32'),
(223, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:09:40'),
(224, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 3', '::1', '2026-04-01 03:10:21'),
(225, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:10:28'),
(226, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 3', '::1', '2026-04-01 03:11:20'),
(227, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:11:39'),
(228, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:11:49'),
(229, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:12:02'),
(230, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 03:14:41'),
(231, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:3', '::1', '2026-04-01 03:15:52'),
(232, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 07:57:26'),
(233, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 09:53:28'),
(234, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-01 10:03:47'),
(235, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 11:06:38'),
(236, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 11:06:50'),
(237, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 11:17:18'),
(238, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 12:07:20'),
(239, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 12:08:05'),
(240, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 4', '::1', '2026-04-01 12:08:47'),
(241, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 12:09:01'),
(242, 37, 'sh_override_indicator', 'self_assessment', 'SH overrode indicator 1.4 from avg 2.00 to 4 in cycle 4', '::1', '2026-04-01 12:19:47'),
(243, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 13:00:34'),
(244, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 13:00:51'),
(245, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 13:06:25'),
(246, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 13:14:00'),
(247, 37, 'sh_override_indicator', 'self_assessment', 'SH overrode indicator 1.4 from avg 2.00 to 4 in cycle 4', '::1', '2026-04-01 13:18:13'),
(248, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 13:59:01'),
(249, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 14:31:49'),
(250, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-01 14:31:55'),
(251, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-01 14:33:21'),
(252, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:00:55'),
(253, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:01:02'),
(254, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:01:10'),
(255, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:02:01'),
(256, 37, 'create_user', 'users', 'Created: Rolito', '::1', '2026-04-01 16:02:29'),
(257, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:10:41'),
(258, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-01 16:12:28'),
(259, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:12:51'),
(260, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:33:00'),
(261, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:33:08'),
(262, 37, 'delete_user', 'users', 'Deleted user ID:47', '::1', '2026-04-01 16:33:16'),
(263, 37, 'delete_user', 'users', 'Deleted user ID:39', '::1', '2026-04-01 16:33:20'),
(264, 37, 'create_user', 'users', 'Created: Rolito', '::1', '2026-04-01 16:34:39'),
(265, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:40:51'),
(266, 37, 'delete_user', 'users', 'Deleted user ID:48', '::1', '2026-04-01 16:40:59'),
(267, 37, 'create_user', 'users', 'Created: Rolito', '::1', '2026-04-01 16:41:32'),
(268, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-01 16:42:50'),
(269, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-01 16:42:57'),
(270, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:03:32'),
(271, 37, 'delete_user', 'users', 'Deleted user ID:49', '::1', '2026-04-02 15:03:38'),
(272, 37, 'create_user', 'users', 'Created: Jr', '::1', '2026-04-02 15:04:02'),
(273, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-02 15:09:36'),
(274, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:09:52'),
(275, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-04-02 15:10:45'),
(276, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:11:07'),
(277, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:11:25'),
(278, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:13:02'),
(279, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:13:30'),
(280, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:13:57'),
(281, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:31:05'),
(282, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:33:16'),
(283, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:40:07'),
(284, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:40:26'),
(285, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-02 15:44:26'),
(286, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-03 03:14:35'),
(287, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-03 04:27:51'),
(288, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-03 10:05:14'),
(289, 37, 'delete_user', 'users', 'Deleted user ID:50', '::1', '2026-04-03 10:05:23'),
(290, 37, 'create_user', 'users', 'Created: Patpat', '::1', '2026-04-03 10:05:52'),
(291, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-03 10:09:50'),
(292, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-03 10:10:06'),
(293, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-03 10:33:33'),
(294, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-03 11:21:04'),
(295, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-03 11:21:12'),
(296, 15, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 1 cycle 5', '::1', '2026-04-03 11:21:25'),
(297, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-03 11:21:58'),
(298, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-03 13:28:53'),
(299, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-03 14:02:06'),
(300, 37, 'delete_user', 'users', 'Deleted user ID:51', '::1', '2026-04-03 14:02:14'),
(301, 37, 'create_user', 'users', 'Created: Rolito', '::1', '2026-04-03 14:02:47'),
(302, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-03 14:28:01'),
(303, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-03 14:28:20'),
(304, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-04-03 15:30:33'),
(305, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-03 15:30:40'),
(306, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-03 15:30:48'),
(307, 37, 'login', 'auth', 'User logged in', '127.0.0.1', '2026-04-04 01:48:16'),
(308, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to inactive', '::1', '2026-04-04 01:57:46'),
(309, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to active', '::1', '2026-04-04 01:57:53'),
(310, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to inactive', '::1', '2026-04-04 02:04:56'),
(311, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:05:20'),
(312, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:05:54'),
(313, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to active', '::1', '2026-04-04 02:06:01'),
(314, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-04-04 02:06:36'),
(315, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:06:45'),
(316, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:06:53'),
(317, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to inactive', '::1', '2026-04-04 02:06:58'),
(318, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:10:47'),
(319, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:10:55'),
(320, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to active', '::1', '2026-04-04 02:11:02'),
(321, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:11:08'),
(322, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:12:07'),
(323, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:13:17'),
(324, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:14:22'),
(325, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:14:39'),
(326, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:15:12'),
(327, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:15:25'),
(328, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:15:38'),
(329, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:16:02'),
(330, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:16:22'),
(331, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 03:23:18'),
(332, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 03:28:09'),
(333, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:23:23'),
(334, 15, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 9 cycle 5', '::1', '2026-04-04 17:23:55'),
(335, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 5', '::1', '2026-04-04 17:24:20'),
(336, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:24:30'),
(337, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:24:57'),
(338, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 5', '::1', '2026-04-04 17:25:45'),
(339, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:25:53'),
(340, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 5', '::1', '2026-04-04 17:26:33'),
(341, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:26:45'),
(342, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 5', '::1', '2026-04-04 17:27:29'),
(343, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:27:37'),
(344, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 5', '::1', '2026-04-04 17:28:19'),
(345, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:28:26'),
(346, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:28:39'),
(347, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 5', '::1', '2026-04-04 17:31:41'),
(348, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:5', '::1', '2026-04-04 17:32:16'),
(349, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:32:31'),
(350, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:35:05'),
(351, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:38:05'),
(352, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 17:44:09'),
(353, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 18:13:49'),
(354, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 19:19:45'),
(355, 46, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 5', '::1', '2026-04-04 19:25:12'),
(356, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 19:25:30'),
(357, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:5', '::1', '2026-04-04 19:27:59'),
(358, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 19:28:17'),
(359, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 19:28:57'),
(360, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 19:35:15'),
(361, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 10:25:40'),
(362, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-05 10:27:51'),
(363, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-05 10:27:59'),
(364, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 10:28:22'),
(365, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-05 10:40:41'),
(366, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-05 10:40:51'),
(367, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-05 11:05:23'),
(368, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 11:05:44'),
(369, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 11:13:29'),
(370, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 11:46:59'),
(371, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 12:28:11'),
(372, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-05 13:32:52'),
(373, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-05 13:33:18'),
(374, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-05 13:38:05'),
(375, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-05 14:28:31'),
(376, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-06 05:37:15'),
(377, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-06 05:37:29'),
(378, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 05:37:39'),
(379, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 05:40:08'),
(380, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 06:21:34'),
(381, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-06 07:49:19'),
(382, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-06 07:49:29'),
(383, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 08:07:43'),
(384, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-06 08:10:44'),
(385, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 08:58:16'),
(386, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-06 08:58:28'),
(387, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 08:58:35'),
(388, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-06 14:30:46'),
(389, 37, 'delete_user', 'users', 'Deleted user ID:52', '::1', '2026-04-06 14:35:01'),
(390, 37, 'toggle_user_status', 'users', 'User ID 46 status changed to inactive', '::1', '2026-04-06 14:35:19'),
(391, 37, 'toggle_user_status', 'users', 'User ID 46 status changed to active', '::1', '2026-04-06 14:35:26'),
(392, 37, 'delete_user', 'users', 'Deleted user ID:53', '::1', '2026-04-06 14:55:53'),
(393, 37, 'delete_user', 'users', 'Deleted user ID:54', '::1', '2026-04-06 14:55:55'),
(394, 37, 'delete_user', 'users', 'Deleted user ID:55', '::1', '2026-04-06 14:57:13'),
(395, 37, 'delete_user', 'users', 'Deleted user ID:56', '::1', '2026-04-06 14:57:15'),
(396, 37, 'delete_user', 'users', 'Deleted user ID:57', '::1', '2026-04-06 15:10:48'),
(397, 37, 'delete_user', 'users', 'Deleted user ID:58', '::1', '2026-04-06 15:10:50'),
(398, 37, 'delete_user', 'users', 'Deleted user ID:59', '::1', '2026-04-06 15:15:38'),
(399, 37, 'delete_user', 'users', 'Deleted user ID:60', '::1', '2026-04-06 15:15:40'),
(400, 37, 'delete_user', 'users', 'Deleted user ID:61', '::1', '2026-04-06 15:19:15'),
(401, 37, 'delete_user', 'users', 'Deleted user ID:62', '::1', '2026-04-06 15:19:17'),
(402, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-07 04:35:51'),
(403, 37, 'delete_user', 'users', 'Deleted user ID:63', '::1', '2026-04-07 04:47:36'),
(404, 37, 'delete_user', 'users', 'Deleted user ID:64', '::1', '2026-04-07 04:47:38'),
(405, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-07 04:59:34'),
(406, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-07 04:59:43'),
(407, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-07 04:59:51'),
(408, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:00:06'),
(409, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 6', '::1', '2026-04-07 05:00:42'),
(410, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:00:54'),
(411, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 6', '::1', '2026-04-07 05:01:42'),
(412, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:01:48'),
(413, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 6', '::1', '2026-04-07 05:02:27'),
(414, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:02:33'),
(415, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 6', '::1', '2026-04-07 05:03:06'),
(416, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:03:13'),
(417, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 6', '::1', '2026-04-07 05:03:45'),
(418, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:03:51'),
(419, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 6', '::1', '2026-04-07 05:05:44'),
(420, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:6', '::1', '2026-04-07 05:06:20'),
(421, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:06:40'),
(422, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-07 05:08:00'),
(423, 37, 'delete_user', 'users', 'Deleted user ID:65', '::1', '2026-04-07 07:31:56'),
(424, 37, 'delete_user', 'users', 'Deleted user ID:66', '::1', '2026-04-07 07:31:59'),
(425, 37, 'delete_user', 'users', 'Deleted user ID:69', '::1', '2026-04-07 07:33:31'),
(426, 37, 'delete_user', 'users', 'Deleted user ID:70', '::1', '2026-04-07 07:33:36'),
(427, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 02:39:36'),
(428, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 03:48:51'),
(429, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 04:10:19'),
(430, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 04:10:37'),
(431, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 09:50:39'),
(432, NULL, 'password_change', 'auth', 'User changed password', '::1', '2026-04-09 09:51:56'),
(433, NULL, 'create_user', 'users', 'Created: Patty', '::1', '2026-04-09 09:53:37'),
(434, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-09 09:55:08'),
(435, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 09:55:20'),
(436, NULL, 'delete_user', 'users', 'Deleted user ID:71', '::1', '2026-04-09 09:55:30'),
(437, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 09:57:35'),
(438, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:19'),
(439, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:25'),
(440, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:31'),
(441, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:39'),
(442, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:01:13'),
(443, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:39:26'),
(444, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:39:54'),
(445, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:43:15'),
(446, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:43:43'),
(447, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:06:04'),
(448, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:12:57'),
(449, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:18:36'),
(450, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:19:24'),
(451, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:19:39'),
(452, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:22:02'),
(453, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:22:17'),
(454, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:23:34'),
(455, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:24:29'),
(456, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:24:35'),
(457, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:24:40'),
(458, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 13:35:37'),
(459, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 13:40:14'),
(460, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 13:46:20'),
(461, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:13:40'),
(462, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:14:05'),
(463, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:14:34'),
(464, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:17:22'),
(465, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:35:04'),
(466, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:37:39'),
(467, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:55:08'),
(468, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:00:57'),
(469, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:02:35'),
(470, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:02:42'),
(471, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:02:57'),
(472, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:03:12'),
(473, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:03:20'),
(474, NULL, 'create_user', 'users', 'Created: Rol', '::1', '2026-04-10 03:05:14'),
(475, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-10 03:06:04'),
(476, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:06:24'),
(477, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-10 04:25:53'),
(478, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-10 14:50:09'),
(479, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:51:17'),
(480, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:52:15'),
(481, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:54:43'),
(482, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:56:05'),
(483, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:56:45'),
(484, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:58:38'),
(485, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:12:59'),
(486, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:17:35'),
(487, NULL, 'delete_user', 'users', 'Deleted user ID:73', '::1', '2026-04-11 03:17:57'),
(488, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:19:00'),
(489, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:29:26'),
(490, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:31:07'),
(491, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:34:42'),
(492, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:39:16'),
(493, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:41:17'),
(494, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:42:08'),
(495, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:30:55'),
(496, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:31:27'),
(497, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:32:08'),
(498, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:32:18'),
(499, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:32:29'),
(500, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:33:37'),
(501, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:35:16'),
(502, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-11 09:38:16'),
(503, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:38:25'),
(504, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:38:56'),
(505, NULL, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 7: dozenjames54@gmail.com', '::1', '2026-04-11 09:39:55'),
(506, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-11 09:41:55'),
(507, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:42:21'),
(508, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:48:24'),
(509, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:48:36'),
(510, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:48:45'),
(511, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 10:00:13'),
(512, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 10:07:51'),
(513, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:08:06'),
(514, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:08:09'),
(515, NULL, 'delete_user', 'users', 'Deleted user ID:74', '::1', '2026-04-11 10:08:17'),
(516, NULL, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 7: dozenjames54@gmail.com', '::1', '2026-04-11 10:40:04'),
(517, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-11 10:40:44'),
(518, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 10:40:55'),
(519, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 10:51:37'),
(520, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 10:54:35'),
(521, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:38'),
(522, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:43'),
(523, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:47'),
(524, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:49'),
(525, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:52'),
(526, NULL, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 7: 2026-04-11 19:01:00 to 2026-04-11 19:05:00', '::1', '2026-04-11 11:01:26'),
(527, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 11:02:30'),
(528, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 11:02:38'),
(529, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 11:03:58'),
(530, NULL, 'reactivate_evaluators', 'users', 'Reactivated 1 evaluators for cycle 7', '::1', '2026-04-11 11:04:09'),
(531, NULL, 'delete_user', 'users', 'Deleted user ID:75', '::1', '2026-04-11 11:13:34'),
(532, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:14:32'),
(533, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:35:58'),
(534, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:36:34'),
(535, NULL, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 7: dozenjames54@gmail.com', '::1', '2026-04-11 11:37:39'),
(536, NULL, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 7: 2026-04-11 19:01:00 to 2026-04-11 19:05:00', '::1', '2026-04-11 11:37:46'),
(537, NULL, 'reactivate_evaluators', 'users', 'Reactivated 1 evaluators for cycle 7', '::1', '2026-04-11 11:37:57'),
(538, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-11 11:39:09'),
(539, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:39:16'),
(540, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:02:26'),
(541, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:02:45'),
(542, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:14:13'),
(543, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:36:50'),
(544, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 14:09:46'),
(545, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 14:55:14'),
(546, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 15:29:23'),
(547, NULL, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 16:42:26'),
(548, NULL, 'reactivate_evaluators', 'users', 'Reactivated 1 evaluators for cycle 7', '::1', '2026-04-11 16:43:06'),
(549, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 16:57:19'),
(550, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 16:57:41'),
(551, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 16:58:22'),
(552, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:00:58'),
(553, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:02:19'),
(554, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:06:07'),
(555, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:08:16'),
(556, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:34:30'),
(557, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:36:24'),
(558, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:36:31'),
(559, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:00:59'),
(560, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:23:07'),
(561, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:23:14'),
(562, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:24:35'),
(563, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:26:40'),
(564, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:33:20'),
(565, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:35:50'),
(566, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:49:13'),
(567, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:49:28'),
(568, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:50:03'),
(569, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:50:13'),
(570, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-12 04:50:27'),
(571, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:50:37'),
(572, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:50:46'),
(573, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:51:08'),
(574, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:51:39'),
(575, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:52:30'),
(576, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:53:29'),
(577, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:57:25'),
(578, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:58:28'),
(579, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:18:42'),
(580, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 10', '::1', '2026-04-12 05:25:53'),
(581, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:40:39'),
(582, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:45:24'),
(583, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:48:02'),
(584, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:52:14'),
(585, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:52:34'),
(586, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:53:58'),
(587, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:54:10'),
(588, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 05:55:37'),
(589, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-12 05:55:53'),
(590, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:59:24'),
(591, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:59:39'),
(592, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:03:03'),
(593, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 06:03:21'),
(594, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:07:52'),
(595, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:09:16'),
(596, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:09:31'),
(597, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 06:10:22'),
(598, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 06:10:56'),
(599, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:14:14'),
(600, NULL, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 9: 2026-04-12 16:00:00 to 2026-04-21 06:00:00', '::1', '2026-04-12 06:15:38'),
(601, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:15:49'),
(602, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:16:01'),
(603, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:16:10'),
(604, NULL, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 9: 2026-04-12 16:00:00 to 2026-04-21 06:00:00', '::1', '2026-04-12 06:16:26'),
(605, NULL, 'delete_user', 'users', 'Deleted user ID:76', '::1', '2026-04-12 06:16:42'),
(606, NULL, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 9: dozenjames54@gmail.com', '::1', '2026-04-12 06:17:36'),
(607, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-12 06:19:12'),
(608, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:20:51'),
(609, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:25:01'),
(610, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:25:17'),
(611, NULL, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 9: 2026-04-12 16:00:00 to 2026-04-21 06:00:00', '::1', '2026-04-12 06:55:08'),
(612, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 09:23:36'),
(613, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 09:23:47'),
(614, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 09:12:15'),
(615, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 09:25:02'),
(616, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 09:25:43'),
(617, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 09:27:04'),
(618, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:06:42'),
(619, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:06:55'),
(620, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:06:56'),
(621, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:06:57'),
(622, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:06:58'),
(623, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:06:59'),
(624, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:07:00'),
(625, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:21:36'),
(626, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:23:28'),
(627, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:28:25'),
(628, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:38:07'),
(629, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:38:26'),
(630, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:41:14'),
(631, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:43:57'),
(632, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:44:11'),
(633, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:45:11'),
(634, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-04-14 10:48:12'),
(635, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 10:48:48'),
(636, NULL, 'update_user', 'users', 'Updated user ID:15', '::1', '2026-04-14 10:58:20'),
(637, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 10:58:44'),
(638, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:16:46'),
(639, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:20:37'),
(640, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:20:53'),
(641, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:21:36'),
(642, NULL, 'update_user', 'users', 'Updated user ID:15', '::1', '2026-04-14 11:21:44'),
(643, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:21:55'),
(644, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:23:10'),
(645, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:23:35'),
(646, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:24:25'),
(647, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:25:00'),
(648, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:25:33'),
(649, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:29:06');
INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
(650, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:29:16'),
(651, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:29:26'),
(652, 37, 'sh_update_school_profile', 'school_profile', 'School Head updated school profile for school_id: 1', '::1', '2026-04-14 11:30:22'),
(653, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:33:33'),
(654, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:33:55'),
(655, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:37:00'),
(656, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:50:21'),
(657, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:50:29'),
(658, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:50:46'),
(659, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-15 13:49:48'),
(660, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:49:34'),
(661, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:49:46'),
(662, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:49:59'),
(663, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:50:10'),
(664, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-16 12:43:11'),
(665, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-16 13:02:19'),
(666, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 13:17:51'),
(667, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 16:37:43'),
(668, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:32:37'),
(669, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 9', '::1', '2026-04-16 17:33:40'),
(670, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:33:48'),
(671, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 9', '::1', '2026-04-16 17:34:33'),
(672, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:34:40'),
(673, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 9', '::1', '2026-04-16 17:35:30'),
(674, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:35:45'),
(675, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 9', '::1', '2026-04-16 17:36:22'),
(676, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:36:29'),
(677, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 9', '::1', '2026-04-16 17:37:06'),
(678, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:37:14'),
(679, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:37:29'),
(680, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:37:43'),
(681, 37, 'sh_override_indicator', 'self_assessment', 'SH overrode indicator 1.4 from avg 3.00 to 2 in cycle 9', '::1', '2026-04-16 17:38:42'),
(682, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 9', '::1', '2026-04-16 17:40:05'),
(683, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:40:24'),
(684, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:40:57'),
(685, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:9', '::1', '2026-04-16 17:41:30'),
(686, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:42:04'),
(687, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:42:51'),
(688, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:52:48'),
(689, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:53:27'),
(690, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 18:31:21'),
(691, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-17 07:29:44'),
(692, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 02:11:43'),
(693, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 04:50:15'),
(694, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 04:57:26'),
(695, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 06:54:01'),
(696, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:13:42'),
(697, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:19:41'),
(698, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:33:25'),
(699, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:48:58'),
(700, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:49:30'),
(701, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:49:38'),
(702, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 07:55:56'),
(703, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 08:19:45'),
(704, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 08:29:11'),
(705, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 08:29:26'),
(706, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 08:30:07'),
(707, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 08:30:21'),
(708, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:05:17'),
(709, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:07:46'),
(710, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:08:30'),
(711, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:08:50'),
(712, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:09:33'),
(713, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:27:26'),
(714, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:41:51'),
(715, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:51:02'),
(716, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 11:08:52'),
(717, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:28:24'),
(718, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:28:33'),
(719, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:28:42'),
(720, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:29:32'),
(721, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:30:10'),
(722, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:32:32'),
(723, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:37:57'),
(724, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:41:06'),
(725, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:49:42'),
(726, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:57:56'),
(727, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:59:22'),
(728, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 17:04:59'),
(729, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 17:09:38'),
(730, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 01:43:34'),
(731, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 01:44:10'),
(732, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 01:54:59'),
(733, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 01:57:10'),
(734, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 02:57:33'),
(735, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 02:57:59'),
(736, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 02:59:13'),
(737, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 02:59:22'),
(738, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:02:08'),
(739, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:02:17'),
(740, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:05:23'),
(741, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:06:19'),
(742, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 14', '::1', '2026-04-19 03:08:39'),
(743, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-19 03:09:04'),
(744, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:09:27'),
(745, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:09:57'),
(746, 15, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 7 cycle 10', '::1', '2026-04-19 03:12:22'),
(747, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 10', '::1', '2026-04-19 03:13:57'),
(748, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:14:08'),
(749, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:22:05'),
(750, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 10', '::1', '2026-04-19 03:25:08'),
(751, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:25:47'),
(752, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:26:11'),
(753, NULL, 'delete_user', 'users', 'Deleted user ID:77', '::1', '2026-04-19 03:26:28'),
(754, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:26:38'),
(755, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 10', '::1', '2026-04-19 03:28:05'),
(756, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:28:14'),
(757, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:28:51'),
(758, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:30:15'),
(759, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:31:49'),
(760, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:33:52'),
(761, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:39:55'),
(762, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:44:08'),
(763, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 10', '::1', '2026-04-19 03:44:59'),
(764, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:45:07'),
(765, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:45:24'),
(766, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 10', '::1', '2026-04-19 03:46:04'),
(767, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:46:11'),
(768, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:46:57'),
(769, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 10', '::1', '2026-04-19 03:48:10'),
(770, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:48:43'),
(771, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:10', '::1', '2026-04-19 03:49:14'),
(772, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:49:24'),
(773, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:51:29'),
(774, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:55:01'),
(775, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:56:36'),
(776, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:02:59'),
(777, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:03:29'),
(778, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-19 04:04:48'),
(779, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:05:08'),
(780, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 11', '::1', '2026-04-19 04:08:20'),
(781, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:09:02'),
(782, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 11', '::1', '2026-04-19 04:12:28'),
(783, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:15:48'),
(784, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 11', '::1', '2026-04-19 04:19:45'),
(785, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:22:28'),
(786, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 11', '::1', '2026-04-19 04:25:26'),
(787, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:26:23'),
(788, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 11', '::1', '2026-04-19 04:30:11'),
(789, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:30:24'),
(790, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 11', '::1', '2026-04-19 04:33:19'),
(791, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:11', '::1', '2026-04-19 04:34:20'),
(792, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:34:29'),
(793, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:34:47'),
(794, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:35:41'),
(795, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:36:36'),
(796, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-19 04:36:50'),
(797, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 16', '::1', '2026-04-19 04:37:50'),
(798, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:38:05'),
(799, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 12', '::1', '2026-04-19 04:38:41'),
(800, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:38:49'),
(801, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 12', '::1', '2026-04-19 04:39:22'),
(802, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:39:31'),
(803, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 12', '::1', '2026-04-19 04:40:01'),
(804, 14, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:40:06'),
(805, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 12', '::1', '2026-04-19 04:40:36'),
(806, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:40:42'),
(807, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 12', '::1', '2026-04-19 04:41:14'),
(808, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:41:27'),
(809, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 12', '::1', '2026-04-19 04:42:37'),
(810, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:12', '::1', '2026-04-19 04:42:45'),
(811, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:42:51'),
(812, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:43:02'),
(813, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:57:12'),
(814, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:25:16'),
(815, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:27:15'),
(816, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:31:24'),
(817, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:37:08'),
(818, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:37:21'),
(819, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:38:30'),
(820, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:38:53'),
(821, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:39:03'),
(822, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:44:01'),
(823, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 10:46:59'),
(824, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 14', '::1', '2026-04-19 11:03:00'),
(825, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 12:23:25'),
(826, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:01:53'),
(827, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:02:08'),
(828, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:02:24'),
(829, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:15:17'),
(830, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:26:50'),
(831, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:27:05'),
(832, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:27:19'),
(833, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:28:30'),
(834, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:30:17'),
(835, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-21 02:31:55'),
(836, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-21 03:35:57'),
(837, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-24 04:33:00'),
(838, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-24 11:18:44'),
(839, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-24 11:22:15'),
(840, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-24 11:22:26'),
(841, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-24 11:22:42'),
(842, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-25 11:05:20'),
(843, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-25 11:11:27'),
(844, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-25 11:11:41'),
(845, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-25 11:13:30'),
(846, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-25 11:15:30'),
(847, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-26 02:38:55'),
(848, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-26 03:39:23'),
(849, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-26 03:40:56'),
(850, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-26 03:44:27'),
(851, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-26 03:46:48'),
(852, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-26 03:49:00'),
(853, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-26 12:16:25'),
(854, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-26 12:16:58'),
(855, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-26 12:18:37'),
(856, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-26 12:21:48'),
(857, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-26 12:24:21'),
(858, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-01 04:24:54'),
(859, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-01 04:34:14'),
(860, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-02 13:51:30'),
(861, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-02 14:41:43'),
(862, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-02 15:21:37'),
(863, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 08:57:00'),
(864, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 09:57:30'),
(865, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:26:19'),
(866, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:28:08'),
(867, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:39:43'),
(868, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:43:38'),
(869, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:44:44'),
(870, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:56:29'),
(871, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:57:30'),
(872, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:59:50'),
(873, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 11:59:59'),
(874, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:02:55'),
(875, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:03:25'),
(876, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:03:38'),
(877, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:07:54'),
(878, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:28:11'),
(879, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:28:22'),
(880, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:32:56'),
(881, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:33:07'),
(882, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:36:24'),
(883, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:37:39'),
(884, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:38:15'),
(885, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:39:08'),
(886, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:39:19'),
(887, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 12:39:59'),
(888, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 14:44:42'),
(889, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 14:52:33'),
(890, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-09 16:23:52'),
(891, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-09 16:26:00'),
(892, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-20 14:32:52'),
(893, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:22:15'),
(894, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:22:39'),
(895, NULL, 'create_user', 'users', 'Created: jamesdozen', '::1', '2026-05-22 10:24:04'),
(896, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-05-22 10:28:47'),
(897, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:29:06'),
(898, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:29:32'),
(899, NULL, 'delete_user', 'users', 'Deleted user ID:78', '::1', '2026-05-22 10:29:37'),
(900, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:29:51'),
(901, 2, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:33:50'),
(902, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:41:42'),
(903, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:41:53'),
(904, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:42:06'),
(905, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:42:23'),
(906, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 10:42:42'),
(907, 15, 'login', 'auth', 'User logged in', '::1', '2026-05-22 13:43:59'),
(908, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 13:44:58'),
(909, 15, 'login', 'auth', 'User logged in', '::1', '2026-05-22 14:00:24'),
(910, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 14:00:33'),
(911, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:31:56'),
(912, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:32:27'),
(913, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:32:47'),
(914, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:32:58'),
(915, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:33:08'),
(916, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:33:20'),
(917, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:51:04'),
(918, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-22 16:51:16'),
(919, 37, 'login', 'auth', 'User logged in', '127.0.0.1', '2026-05-23 13:57:14'),
(920, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-23 14:39:44'),
(921, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-23 14:51:21'),
(922, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-24 15:02:50'),
(923, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-26 09:35:38'),
(924, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-26 13:37:37'),
(925, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-26 13:37:47'),
(926, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-27 03:44:13'),
(927, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-27 03:47:32'),
(928, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-27 03:53:16'),
(929, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-27 03:58:38'),
(930, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-27 04:03:00'),
(931, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-27 04:03:07'),
(932, NULL, 'login', 'auth', 'User logged in', '::1', '2026-05-27 04:06:47'),
(933, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-27 04:09:44'),
(934, 37, 'login', 'auth', 'User logged in', '::1', '2026-05-31 12:12:46'),
(935, 46, 'login', 'auth', 'User logged in', '::1', '2026-05-31 13:51:03'),
(936, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-01 11:35:16'),
(937, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-02 20:43:17'),
(938, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:05:25'),
(939, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:11:42'),
(940, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:18:47'),
(941, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:18:59'),
(942, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:19:15'),
(943, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:22:09'),
(944, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:22:29'),
(945, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:23:07'),
(946, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-03 21:23:25'),
(947, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 02:34:16'),
(948, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 02:34:51'),
(949, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 04:13:06'),
(950, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 05:08:21'),
(951, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 05:14:00'),
(952, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 05:18:34'),
(953, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-04 05:19:51'),
(954, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 05:20:04'),
(955, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:03:41'),
(956, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:06:30'),
(957, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:06:41'),
(958, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:07:00'),
(959, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:14:54'),
(960, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:15:11'),
(961, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 06:15:20'),
(962, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-06-04 06:15:43'),
(963, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-06-04 06:16:34'),
(964, NULL, 'toggle_user_status', 'users', 'User ID 15 status changed to inactive', '::1', '2026-06-04 06:43:37'),
(965, NULL, 'toggle_user_status', 'users', 'User ID 15 status changed to active', '::1', '2026-06-04 06:43:51'),
(966, NULL, 'toggle_user_status', 'users', 'User ID 13 status changed to inactive', '::1', '2026-06-04 06:44:04'),
(967, NULL, 'toggle_user_status', 'users', 'User ID 13 status changed to active', '::1', '2026-06-04 06:46:17'),
(968, NULL, 'toggle_user_status', 'users', 'User ID 13 status changed to inactive', '::1', '2026-06-04 06:46:20'),
(969, NULL, 'toggle_user_status', 'users', 'User ID 13 status changed to active', '::1', '2026-06-04 06:46:25'),
(970, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-06-04 06:49:45'),
(971, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-06-04 06:49:50'),
(972, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-06-04 06:49:55'),
(973, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-06-04 06:51:12'),
(974, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-06-04 06:51:15'),
(975, NULL, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-06-04 06:51:19'),
(976, NULL, 'toggle_user_status', 'users', 'User ID 12 status changed to archived', '::1', '2026-06-04 07:00:16'),
(977, NULL, 'toggle_user_status', 'users', 'User ID 12 status changed to inactive', '::1', '2026-06-04 07:02:21'),
(978, NULL, 'toggle_user_status', 'users', 'User ID 12 status changed to active', '::1', '2026-06-04 07:02:31'),
(979, NULL, 'toggle_user_status', 'users', 'User ID 12 status changed to archived', '::1', '2026-06-04 07:02:37'),
(980, NULL, 'toggle_user_status', 'users', 'User ID 12 status changed to inactive', '::1', '2026-06-04 07:02:50'),
(981, NULL, 'toggle_user_status', 'users', 'User ID 12 status changed to active', '::1', '2026-06-04 07:02:55'),
(982, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-04 13:48:32'),
(983, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 16:08:37'),
(984, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-06-04 16:12:28'),
(985, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-04 16:40:06'),
(986, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 16:40:19'),
(987, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 16:44:47'),
(988, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-04 16:45:56'),
(989, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-05 00:34:56'),
(990, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-05 00:35:41'),
(991, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-05 01:05:01'),
(992, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 01:05:36'),
(993, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-05 02:27:30'),
(994, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 02:30:02'),
(995, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 03:57:38'),
(996, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-05 09:05:24'),
(997, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 09:08:46'),
(998, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-05 09:30:43'),
(999, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-05 09:30:53'),
(1000, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 09:31:11'),
(1001, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 10:36:04'),
(1002, NULL, 'update_user', 'users', 'Updated user ID:15', '::1', '2026-06-05 10:46:21'),
(1003, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-05 10:46:31'),
(1004, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 10:52:33'),
(1005, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-05 10:55:55'),
(1006, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-05 10:56:17'),
(1007, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-05 10:56:38'),
(1008, NULL, 'create_department', 'departments', 'Created: English', '::1', '2026-06-07 11:43:18'),
(1009, NULL, 'update_user', 'users', 'Updated user ID:15', '::1', '2026-06-07 11:43:29'),
(1010, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 11:50:07'),
(1011, NULL, 'update_user', 'users', 'Updated user ID:46', '::1', '2026-06-07 11:50:18'),
(1012, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 11:50:30'),
(1013, NULL, 'create_department', 'departments', 'Created: IT Department', '::1', '2026-06-07 11:51:01'),
(1014, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 11:51:10'),
(1015, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 11:54:51'),
(1016, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 11:58:35'),
(1017, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 12:10:17'),
(1018, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 12:10:23'),
(1019, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-07 12:40:55'),
(1020, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-07 12:41:16'),
(1021, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-07 16:15:46'),
(1022, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-07 16:15:53'),
(1023, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-07 16:31:27'),
(1024, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 16:47:00'),
(1025, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 16:47:08'),
(1026, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 16:48:28'),
(1027, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 16:48:35'),
(1028, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 16:50:07'),
(1029, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 16:50:13'),
(1030, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:07:24'),
(1031, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:07:41'),
(1032, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 17:24:17'),
(1033, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 17:26:32'),
(1034, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 17:26:38'),
(1035, NULL, 'toggle_user_status', 'users', 'User ID 15 status changed to inactive', '::1', '2026-06-07 17:26:55'),
(1036, NULL, 'toggle_user_status', 'users', 'User ID 15 status changed to active', '::1', '2026-06-07 17:27:04'),
(1037, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 17:27:47'),
(1038, NULL, 'update_user', 'users', 'Updated user ID:72', '::1', '2026-06-07 17:27:58'),
(1039, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:28:23'),
(1040, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:28:30'),
(1041, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:29:04'),
(1042, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-06-07 17:30:49'),
(1043, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-06-07 17:31:06'),
(1044, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:31:49'),
(1045, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:32:52'),
(1046, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:33:15'),
(1047, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:33:29'),
(1048, NULL, 'login', 'auth', 'User logged in', '::1', '2026-06-07 17:40:32'),
(1049, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 02:32:34'),
(1050, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 02:33:16'),
(1051, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 02:35:22'),
(1052, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 02:35:37'),
(1053, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 02:40:50'),
(1054, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 03:21:59'),
(1055, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 03:32:36'),
(1056, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 03:33:03'),
(1057, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 06:46:35'),
(1058, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 06:46:45'),
(1059, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 06:50:44'),
(1060, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 06:52:03'),
(1061, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 06:58:03'),
(1062, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 07:04:41'),
(1063, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 07:05:44'),
(1064, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:10:43'),
(1065, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:13:04'),
(1066, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:13:24'),
(1067, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:22:57'),
(1068, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:50:21'),
(1069, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:51:44'),
(1070, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 10:56:05'),
(1071, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:15:01'),
(1072, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:26:42'),
(1073, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:27:22'),
(1074, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:28:02'),
(1075, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:30:51'),
(1076, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 17', '::1', '2026-06-11 11:33:19'),
(1077, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 17', '::1', '2026-06-11 11:33:45'),
(1078, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-06-11 11:35:24'),
(1079, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:38:54'),
(1080, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:39:26'),
(1081, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:41:31'),
(1082, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:41:59'),
(1083, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:42:10'),
(1084, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:44:05'),
(1085, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-06-11 11:47:29'),
(1086, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:47:36'),
(1087, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:47:49'),
(1088, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:48:03'),
(1089, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:48:19'),
(1090, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:49:51'),
(1091, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:53:31'),
(1092, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-06-11 11:53:43'),
(1093, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:54:00'),
(1094, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:54:15'),
(1095, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:54:32'),
(1096, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:54:51'),
(1097, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:55:02'),
(1098, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:55:12'),
(1099, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:55:26'),
(1100, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:56:01'),
(1101, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:56:17'),
(1102, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:56:26'),
(1103, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:57:04'),
(1104, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 11:57:20'),
(1105, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:01:08'),
(1106, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:01:20'),
(1107, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:01:28'),
(1108, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 15', '::1', '2026-06-11 12:01:37'),
(1109, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:01:44'),
(1110, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:01:49'),
(1111, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:04:30'),
(1112, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:05:12'),
(1113, 37, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 1 cycle 15', '::1', '2026-06-11 12:13:50'),
(1114, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 12:49:51'),
(1115, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:15:24'),
(1116, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 15', '::1', '2026-06-11 15:16:01'),
(1117, 12, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:16:11'),
(1118, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 15', '::1', '2026-06-11 15:16:43'),
(1119, 14, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:16:49'),
(1120, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 15', '::1', '2026-06-11 15:17:22'),
(1121, 13, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:17:30'),
(1122, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 15', '::1', '2026-06-11 15:18:01'),
(1123, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:18:07'),
(1124, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:18:48'),
(1125, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 15', '::1', '2026-06-11 15:21:39'),
(1126, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:15', '::1', '2026-06-11 15:21:59'),
(1127, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:22:09'),
(1128, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:22:51'),
(1129, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:23:13'),
(1130, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:24:54'),
(1131, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-06-11 15:25:13'),
(1132, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:36:53'),
(1133, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 16', '::1', '2026-06-11 15:37:46'),
(1134, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:37:54'),
(1135, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 16', '::1', '2026-06-11 15:38:29'),
(1136, 12, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:38:37'),
(1137, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 16', '::1', '2026-06-11 15:39:09'),
(1138, 13, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:39:15'),
(1139, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 16', '::1', '2026-06-11 15:39:50'),
(1140, 14, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:39:56'),
(1141, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 16', '::1', '2026-06-11 15:40:28'),
(1142, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:40:34'),
(1143, 14, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:40:42'),
(1144, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:40:48'),
(1145, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 16', '::1', '2026-06-11 15:42:06'),
(1146, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:16', '::1', '2026-06-11 15:42:11'),
(1147, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:42:30'),
(1148, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:42:41'),
(1149, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:51:59'),
(1150, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:53:29'),
(1151, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-06-11 15:53:41'),
(1152, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:54:02'),
(1153, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 17', '::1', '2026-06-11 15:54:44'),
(1154, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:54:51'),
(1155, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 17', '::1', '2026-06-11 15:55:26'),
(1156, 12, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:55:36'),
(1157, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 17', '::1', '2026-06-11 15:56:09'),
(1158, 13, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:56:15'),
(1159, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 17', '::1', '2026-06-11 15:56:47'),
(1160, 14, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:56:53'),
(1161, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 17', '::1', '2026-06-11 15:57:25'),
(1162, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:57:34'),
(1163, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:57:43'),
(1164, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 17', '::1', '2026-06-11 15:58:55'),
(1165, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:17', '::1', '2026-06-11 15:59:04'),
(1166, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:59:11'),
(1167, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 15:59:20'),
(1168, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-11 16:12:19'),
(1169, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 17:12:14'),
(1170, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 17:41:56'),
(1171, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 17:58:13'),
(1172, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 17:59:01'),
(1173, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-11 18:00:19'),
(1174, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-11 18:12:06'),
(1175, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-12 03:22:17'),
(1176, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-12 03:23:30'),
(1177, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-12 04:06:16'),
(1178, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-12 04:20:20'),
(1179, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-12 05:01:33'),
(1180, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-12 05:01:46'),
(1181, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-12 05:09:31'),
(1182, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-12 11:54:55'),
(1183, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-12 11:55:05'),
(1184, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-12 16:54:06'),
(1185, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 14:41:51'),
(1186, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 14:58:02'),
(1187, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 15:06:18'),
(1188, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 15:45:38'),
(1189, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 15:49:26'),
(1190, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:11:43'),
(1191, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:12:01'),
(1192, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:15:05'),
(1193, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:15:25'),
(1194, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:28:05'),
(1195, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:36:19'),
(1196, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:45:02'),
(1197, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:46:06'),
(1198, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:48:02'),
(1199, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:52:26'),
(1200, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:56:19'),
(1201, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:57:00'),
(1202, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:57:52'),
(1203, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-06-13 16:57:58'),
(1204, 15, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:58:05'),
(1205, 15, 'upload_evidence', 'attachment', 'Uploaded evidence for indicator 1 cycle 18', '::1', '2026-06-13 16:59:01'),
(1206, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 18', '::1', '2026-06-13 16:59:13'),
(1207, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 16:59:20'),
(1208, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 17:27:10'),
(1209, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 17:27:26'),
(1210, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 17:29:11'),
(1211, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-13 17:33:21'),
(1212, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 17:34:37'),
(1213, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:03:06'),
(1214, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:03:18'),
(1215, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:04:38'),
(1216, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:05:56'),
(1217, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:13:02'),
(1218, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:15:09'),
(1219, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:16:33'),
(1220, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:18:33'),
(1221, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:21:11'),
(1222, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:25:15'),
(1223, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:27:27'),
(1224, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:27:43'),
(1225, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:32:31'),
(1226, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:32:51'),
(1227, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:36:51'),
(1228, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:38:38'),
(1229, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:48:40'),
(1230, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 18:49:32'),
(1231, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-13 19:02:22'),
(1232, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-13 19:02:34'),
(1233, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:13:17'),
(1234, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:13:57'),
(1235, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:14:15'),
(1236, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:14:30'),
(1237, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:27:21'),
(1238, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:27:35'),
(1239, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:32:03'),
(1240, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:43:08'),
(1241, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:43:40'),
(1242, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:44:27'),
(1243, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:45:41'),
(1244, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:47:14'),
(1245, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:53:41'),
(1246, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 05:55:54'),
(1247, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-14 06:09:11'),
(1248, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 06:09:28'),
(1249, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-14 06:15:39'),
(1250, 74, 'update_user', 'users', 'Updated user ID:14', '::1', '2026-06-14 06:15:54');
INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
(1251, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 06:16:05'),
(1252, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-14 22:52:10'),
(1253, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-16 01:20:04'),
(1254, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-16 02:44:19'),
(1255, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-19 16:48:48'),
(1256, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-19 16:50:38'),
(1257, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-19 17:12:44'),
(1258, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-20 02:05:12'),
(1259, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-20 14:10:36'),
(1260, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-20 14:18:55'),
(1261, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-20 14:19:05'),
(1262, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-20 14:25:20'),
(1263, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-20 14:47:07'),
(1264, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-21 09:07:57'),
(1265, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-21 11:10:08'),
(1266, 74, 'create_department', 'departments', 'Created: Filipino Department', '::1', '2026-06-21 11:10:26'),
(1267, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-21 11:11:05'),
(1268, 74, 'update_user', 'users', 'Updated user ID:46', '::1', '2026-06-21 11:11:27'),
(1269, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-21 11:11:39'),
(1270, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-21 11:12:01'),
(1271, 74, 'update_department', 'departments', 'Updated department: Filipino Department ΓåÆ Filipino', '::1', '2026-06-21 11:14:19'),
(1272, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-21 11:14:29'),
(1273, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-21 11:14:41'),
(1274, 74, 'update_department', 'departments', 'Updated department: IT Department ΓåÆ Information Technology', '::1', '2026-06-21 11:14:57'),
(1275, 74, 'update_user', 'users', 'Updated user ID:74', '::1', '2026-06-21 11:15:20'),
(1276, 74, 'update_user', 'users', 'Updated user ID:14', '::1', '2026-06-21 11:15:28'),
(1277, 74, 'update_user', 'users', 'Updated user ID:14', '::1', '2026-06-21 11:16:00'),
(1278, 74, 'create_department', 'departments', 'Created: Guidance', '::1', '2026-06-21 11:16:55'),
(1279, 74, 'update_user', 'users', 'Updated user ID:37', '::1', '2026-06-21 11:17:09'),
(1280, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-21 15:37:54'),
(1281, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-21 15:38:09'),
(1282, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-21 15:58:35'),
(1283, 37, 'update_profile', 'profile', 'User updated their profile', '::1', '2026-06-21 15:59:00'),
(1284, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-21 16:02:43'),
(1285, 2, 'login', 'auth', 'User logged in', '::1', '2026-06-21 16:06:05'),
(1286, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-21 16:07:37'),
(1287, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-22 15:50:28'),
(1288, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-22 16:25:35'),
(1289, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-25 11:31:27'),
(1290, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-25 11:31:45'),
(1291, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-25 11:33:11'),
(1292, 46, 'login', 'auth', 'User logged in', '::1', '2026-06-25 11:34:04'),
(1293, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-25 11:34:25'),
(1294, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-29 16:10:51'),
(1295, 74, 'login', 'auth', 'User logged in', '::1', '2026-06-29 16:11:19'),
(1296, 37, 'login', 'auth', 'User logged in', '::1', '2026-06-29 16:33:26'),
(1297, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-02 10:10:28'),
(1298, 2, 'login', 'auth', 'User logged in', '::1', '2026-07-02 10:19:56'),
(1299, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-02 10:22:48'),
(1300, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-02 10:30:24'),
(1301, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-02 10:35:51'),
(1302, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-16 05:10:05'),
(1303, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-23 11:08:06'),
(1304, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-23 11:08:31'),
(1305, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-23 11:09:28'),
(1306, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-24 15:52:32'),
(1307, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-24 15:52:53'),
(1308, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-24 16:01:34'),
(1309, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-24 16:12:58'),
(1310, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-24 16:40:18'),
(1311, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-24 16:44:15'),
(1312, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-24 16:47:27'),
(1313, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-24 16:47:38'),
(1314, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 04:18:29'),
(1315, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-25 04:50:56'),
(1316, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-25 04:57:29'),
(1317, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-25 04:57:40'),
(1318, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 04:57:47'),
(1319, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:03:44'),
(1320, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:03:54'),
(1321, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:05:15'),
(1322, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 22', '::1', '2026-07-25 05:06:01'),
(1323, 37, 'start_assessment', 'self_assessment', 'Auto-started SBM assessment cycle (Self-Assessment window opened).', '::1', '2026-07-25 05:07:57'),
(1324, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:26:02'),
(1325, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:30:08'),
(1326, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:30:16'),
(1327, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:35:16'),
(1328, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:37:30'),
(1329, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 19', '::1', '2026-07-25 05:38:03'),
(1330, 2, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:38:16'),
(1331, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 19', '::1', '2026-07-25 05:38:48'),
(1332, 12, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:39:01'),
(1333, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 19', '::1', '2026-07-25 05:39:31'),
(1334, 13, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:39:50'),
(1335, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 19', '::1', '2026-07-25 05:40:22'),
(1336, 12, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:40:29'),
(1337, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:40:36'),
(1338, 14, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:40:44'),
(1339, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 19', '::1', '2026-07-25 05:41:16'),
(1340, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:41:26'),
(1341, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:41:37'),
(1342, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:41:53'),
(1343, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 19', '::1', '2026-07-25 05:43:58'),
(1344, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:19', '::1', '2026-07-25 05:44:25'),
(1345, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:44:31'),
(1346, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:45:01'),
(1347, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:46:10'),
(1348, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:57:11'),
(1349, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:57:18'),
(1350, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:59:27'),
(1351, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 05:59:37'),
(1352, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 22', '::1', '2026-07-25 06:01:31'),
(1353, 37, 'start_assessment', 'self_assessment', 'Auto-started SBM assessment cycle (Self-Assessment window opened).', '::1', '2026-07-25 06:01:38'),
(1354, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:03:02'),
(1355, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:03:09'),
(1356, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 20', '::1', '2026-07-25 06:03:42'),
(1357, 2, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:03:49'),
(1358, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 20', '::1', '2026-07-25 06:04:20'),
(1359, 12, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:04:33'),
(1360, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 20', '::1', '2026-07-25 06:05:01'),
(1361, 13, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:05:08'),
(1362, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 20', '::1', '2026-07-25 06:05:40'),
(1363, 14, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:05:48'),
(1364, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 20', '::1', '2026-07-25 06:06:23'),
(1365, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:06:30'),
(1366, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 20', '::1', '2026-07-25 06:08:15'),
(1367, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:20', '::1', '2026-07-25 06:08:24'),
(1368, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:31:36'),
(1369, 37, 'start_assessment', 'self_assessment', 'Auto-started SBM assessment cycle (Self-Assessment window opened).', '::1', '2026-07-25 06:32:10'),
(1370, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 22', '::1', '2026-07-25 06:33:57'),
(1371, 37, 'start_assessment', 'self_assessment', 'Auto-started SBM assessment cycle (Self-Assessment window opened).', '::1', '2026-07-25 06:34:01'),
(1372, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:34:07'),
(1373, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 22', '::1', '2026-07-25 06:34:40'),
(1374, 2, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:34:49'),
(1375, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 22', '::1', '2026-07-25 06:35:16'),
(1376, 12, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:35:26'),
(1377, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 22', '::1', '2026-07-25 06:35:56'),
(1378, 13, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:36:02'),
(1379, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 22', '::1', '2026-07-25 06:36:38'),
(1380, 14, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:36:44'),
(1381, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 22', '::1', '2026-07-25 06:37:13'),
(1382, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:37:20'),
(1383, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:37:28'),
(1384, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 22', '::1', '2026-07-25 06:39:13'),
(1385, 37, 'validate_assessment', 'assessment', 'Validated cycle ID:22', '::1', '2026-07-25 06:39:20'),
(1386, 74, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:41:16'),
(1387, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 06:41:53'),
(1388, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 22', '::1', '2026-07-25 07:14:57'),
(1389, 37, 'start_assessment', 'self_assessment', 'Auto-started SBM assessment cycle (Self-Assessment window opened).', '::1', '2026-07-25 07:15:00'),
(1390, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:54:25'),
(1391, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:54:56'),
(1392, 15, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:55:13'),
(1393, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 23', '::1', '2026-07-25 12:55:43'),
(1394, 2, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:55:49'),
(1395, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 23', '::1', '2026-07-25 12:56:20'),
(1396, 12, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:56:31'),
(1397, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 23', '::1', '2026-07-25 12:57:04'),
(1398, 13, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:57:11'),
(1399, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 23', '::1', '2026-07-25 12:57:42'),
(1400, 14, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:57:53'),
(1401, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 23', '::1', '2026-07-25 12:58:24'),
(1402, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:58:29'),
(1403, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 12:58:39'),
(1404, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 23', '::1', '2026-07-25 13:00:03'),
(1405, 46, 'login', 'auth', 'User logged in', '::1', '2026-07-25 13:00:12'),
(1406, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 13:03:56'),
(1407, 37, 'login', 'auth', 'User logged in', '::1', '2026-07-25 13:10:20'),
(1408, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:42:25'),
(1409, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:43:41'),
(1410, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:44:18'),
(1411, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:48:15'),
(1412, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-08-01 11:48:31'),
(1413, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:48:46'),
(1414, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 24', '::1', '2026-08-01 11:49:26'),
(1415, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:49:38'),
(1416, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 24', '::1', '2026-08-01 11:50:09'),
(1417, 12, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:50:17'),
(1418, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 24', '::1', '2026-08-01 11:51:31'),
(1419, 13, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:51:39'),
(1420, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 24', '::1', '2026-08-01 11:52:08'),
(1421, 14, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:52:19'),
(1422, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 24', '::1', '2026-08-01 11:52:51'),
(1423, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 11:53:00'),
(1424, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 24', '::1', '2026-08-01 11:54:16'),
(1425, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:00:37'),
(1426, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:19:32'),
(1427, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:22:31'),
(1428, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:23:11'),
(1429, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:28:38'),
(1430, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:29:17'),
(1431, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:31:50'),
(1432, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-01 12:34:24'),
(1433, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 02:57:58'),
(1434, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 02:58:30'),
(1435, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:10:39'),
(1436, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-08-02 03:22:24'),
(1437, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:22:34'),
(1438, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 25', '::1', '2026-08-02 03:24:20'),
(1439, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:24:30'),
(1440, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 25', '::1', '2026-08-02 03:25:04'),
(1441, 12, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:25:12'),
(1442, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 25', '::1', '2026-08-02 03:25:45'),
(1443, 13, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:25:54'),
(1444, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 25', '::1', '2026-08-02 03:26:29'),
(1445, 14, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:26:38'),
(1446, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 25', '::1', '2026-08-02 03:27:12'),
(1447, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:27:20'),
(1448, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 25', '::1', '2026-08-02 03:28:43'),
(1449, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:28:54'),
(1450, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:32:40'),
(1451, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 03:35:50'),
(1452, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-08-02 03:55:12'),
(1453, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-02 04:01:17'),
(1454, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 04:01:45'),
(1455, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-02 04:51:51'),
(1456, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 04:56:31'),
(1457, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:01:33'),
(1458, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 26', '::1', '2026-08-02 05:20:43'),
(1459, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:20:52'),
(1460, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 26', '::1', '2026-08-02 05:24:59'),
(1461, 12, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:25:08'),
(1462, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 26', '::1', '2026-08-02 05:25:48'),
(1463, 13, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:27:29'),
(1464, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 26', '::1', '2026-08-02 05:28:01'),
(1465, 14, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:28:07'),
(1466, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 26', '::1', '2026-08-02 05:28:38'),
(1467, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:28:46'),
(1468, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:28:51'),
(1469, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:29:46'),
(1470, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 26', '::1', '2026-08-02 05:44:45'),
(1471, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:44:54'),
(1472, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:49:26'),
(1473, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:49:53'),
(1474, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 05:50:39'),
(1475, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:39:52'),
(1476, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:40:25'),
(1477, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:42:24'),
(1478, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:44:23'),
(1479, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:47:30'),
(1480, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:47:43'),
(1481, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:48:10'),
(1482, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:48:26'),
(1483, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:48:35'),
(1484, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-08-02 13:48:41'),
(1485, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-02 13:48:51'),
(1486, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 27', '::1', '2026-08-02 13:49:26'),
(1487, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:05:32'),
(1488, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 27', '::1', '2026-08-02 14:06:03'),
(1489, 12, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:06:17'),
(1490, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 27', '::1', '2026-08-02 14:06:49'),
(1491, 13, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:06:58'),
(1492, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 27', '::1', '2026-08-02 14:07:31'),
(1493, 14, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:07:37'),
(1494, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 27', '::1', '2026-08-02 14:08:10'),
(1495, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:08:17'),
(1496, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:08:30'),
(1497, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:08:41'),
(1498, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 27', '::1', '2026-08-02 14:10:04'),
(1499, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:10:32'),
(1500, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:10:53'),
(1501, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:11:08'),
(1502, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:19:57'),
(1503, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:22:49'),
(1504, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:23:27'),
(1505, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:23:43'),
(1506, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:25:02'),
(1507, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:25:33'),
(1508, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 14:36:48'),
(1509, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-02 16:23:11'),
(1510, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-02 16:23:21'),
(1511, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-02 16:25:18'),
(1512, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:21:46'),
(1513, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:22:09'),
(1514, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:34:22'),
(1515, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:34:38'),
(1516, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:34:59'),
(1517, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:35:25'),
(1518, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-08-03 14:35:33'),
(1519, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:35:40'),
(1520, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 28', '::1', '2026-08-03 14:36:13'),
(1521, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:36:21'),
(1522, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 28', '::1', '2026-08-03 14:36:51'),
(1523, 12, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:37:00'),
(1524, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 28', '::1', '2026-08-03 14:37:27'),
(1525, 13, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:37:43'),
(1526, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 28', '::1', '2026-08-03 14:38:21'),
(1527, 14, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:38:28'),
(1528, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 28', '::1', '2026-08-03 14:39:01'),
(1529, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:39:11'),
(1530, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:39:17'),
(1531, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:39:24'),
(1532, 37, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 28', '::1', '2026-08-03 14:40:34'),
(1533, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:40:51'),
(1534, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-03 14:41:34'),
(1535, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-04 11:04:22'),
(1536, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-05 00:44:16'),
(1537, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-07 12:50:40'),
(1538, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:01:13'),
(1539, 74, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-08-07 13:01:35'),
(1540, 74, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-08-07 13:01:49'),
(1541, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:04:44'),
(1542, 74, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-08-07 13:05:07'),
(1543, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:05:26'),
(1544, 74, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-08-07 13:05:31'),
(1545, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:07:41'),
(1546, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:14:15'),
(1547, 74, 'toggle_user_status', 'users', 'User ID 14 status changed to inactive', '::1', '2026-08-07 13:15:00'),
(1548, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:15:21'),
(1549, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:17:55'),
(1550, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:25:56'),
(1551, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:28:12'),
(1552, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:28:22'),
(1553, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:29:03'),
(1554, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:31:07'),
(1555, 74, 'toggle_user_status', 'users', 'User ID 14 status changed to active', '::1', '2026-08-07 13:31:13'),
(1556, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:31:21'),
(1557, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:33:27'),
(1558, 12, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:33:38'),
(1559, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-07 13:33:57'),
(1560, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-07 23:39:24'),
(1561, 46, 'publish_form_version', 'manage_form', 'Published form version 2 (ID: 5)', '::1', '2026-08-08 00:11:37'),
(1562, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 00:11:54'),
(1563, 74, 'login', 'auth', 'User logged in', '::1', '2026-08-08 00:12:05'),
(1564, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 00:12:18'),
(1565, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 00:21:24'),
(1566, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 00:28:06'),
(1567, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:06:09'),
(1568, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:06:42'),
(1569, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-08-08 01:06:49'),
(1570, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:06:59'),
(1571, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:07:28'),
(1572, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:08:45'),
(1573, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:09:37'),
(1574, 15, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:12:06'),
(1575, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:13:33'),
(1576, 46, 'publish_form_version', 'manage_form', 'Published form version 3 (ID: 6)', '::1', '2026-08-08 01:13:48'),
(1577, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:13:59'),
(1578, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:14:19'),
(1579, 2, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:14:25'),
(1580, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:14:37'),
(1581, 46, 'publish_form_version', 'manage_form', 'Published form version 4 (ID: 7)', '::1', '2026-08-08 01:14:55'),
(1582, 46, 'revert_form_version', 'manage_form', 'Reverted active form to version ID 1', '::1', '2026-08-08 01:15:50'),
(1583, 46, 'import_form_document', 'manage_form', 'Imported form draft from uploaded document (docx)', '::1', '2026-08-08 01:57:38'),
(1584, 46, 'publish_form_version', 'manage_form', 'Published form version 2 (ID: 8)', '::1', '2026-08-08 01:58:05'),
(1585, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:58:22'),
(1586, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 01:58:34'),
(1587, 46, 'revert_form_version', 'manage_form', 'Reverted active form to version ID 1', '::1', '2026-08-08 01:59:53'),
(1588, 46, 'import_form_document', 'manage_form', 'Imported form draft from uploaded document (docx)', '::1', '2026-08-08 02:01:08'),
(1589, 46, 'publish_form_version', 'manage_form', 'Published form version 2 (ID: 9)', '::1', '2026-08-08 02:01:13'),
(1590, 37, 'login', 'auth', 'User logged in', '::1', '2026-08-08 02:01:26'),
(1591, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 02:04:28'),
(1592, 46, 'revert_form_version', 'manage_form', 'Reverted active form to version ID 1', '::1', '2026-08-08 02:05:22'),
(1593, 46, 'login', 'auth', 'User logged in', '::1', '2026-08-08 09:45:57');

-- --------------------------------------------------------

--
-- Table structure for table `ai_suggestion_usage`
--

CREATE TABLE `ai_suggestion_usage` (
  `user_id` int(11) NOT NULL,
  `usage_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `last_generated_at` datetime DEFAULT NULL,
  `last_recommendation` mediumtext DEFAULT NULL,
  `reset_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_suggestion_usage`
--

INSERT INTO `ai_suggestion_usage` (`user_id`, `usage_count`, `last_generated_at`, `last_recommendation`, `reset_date`) VALUES
(37, 1, '2026-08-07 13:23:03', 'Here are some targeted suggestions based on your school\'s current SBM data:\n\n**Strengthen curriculum delivery** Your scores in Curriculum and Teaching suggest room to grow in instructional delivery, particularly in addressing the needs of diverse learners. For indicator 6.7, consider improving your school\'s liquidation process to achieve a higher rating.\n\n* Review and refine your school\'s MOOE utilization and liquidation procedures to ensure timely and accurate reporting.\n* Provide training for staff on proper liquidation and reporting procedures, as outlined in DepEd Order No. 007, s. 2024.\n\n**Improve stakeholder engagement** The school\'s average rating of satisfactory from internal and external stakeholders (indicator 4.6) indicates a need to enhance communication and collaboration. This is crucial in building a strong school community.\n\n* Establish regular stakeholder feedback mechanisms, such as surveys or focus groups, to gather insights and concerns.\n* Foster partnerships with local industries to strengthen TLE-TVL course offerings, as suggested by indicator 1.8, to provide students with relevant and industry-aligned skills.\n\n**Enhance leadership and governance** The school\'s Leadership dimension score of 61.7% highlights the need to strengthen leadership and governance structures. A functional School Governance Council (SGC) is essential in driving school improvement.\n\n* Reactivate and strengthen the School Governance Council (SGC) to ensure it is functional and effective in supporting school decision-making.\n* Develop a strategic plan with a clear implementation roadmap, as indicated by indicator 3.1, to guide school improvement efforts.\n\n**Develop innovative frontline services** The school\'s rating of 2 in innovating frontline services (indicator 3.4) suggests an opportunity to enhance service delivery and responsiveness to stakeholders. This can be achieved by leveraging technology and streamlining processes.\n\n* Explore innovative solutions, such as online platforms or mobile applications, to improve communication and service delivery to stakeholders.\n* Review and refine school processes to reduce bureaucracy and enhance responsiveness to stakeholder needs.\n\nThe single biggest factor in improving your school\'s SBM score is **focusing on strengthening leadership and governance**, which will have a ripple effect on other areas, including curriculum delivery and stakeholder engagement.', '2026-08-07');

-- --------------------------------------------------------

--
-- Table structure for table `analytics_snapshots`
--

CREATE TABLE `analytics_snapshots` (
  `snap_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `sy_label` varchar(20) NOT NULL COMMENT 'Cached SY label for display without extra joins',
  `dimension_id` int(11) NOT NULL,
  `dimension_no` tinyint(4) NOT NULL,
  `dimension_name` varchar(120) NOT NULL,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `raw_score` decimal(8,2) DEFAULT 0.00,
  `max_score` decimal(8,2) DEFAULT 0.00,
  `overall_score` decimal(5,2) DEFAULT NULL COMMENT 'Copied from sbm_cycles for convenience',
  `maturity_level` enum('Beginning','Developing','Maturing','Advanced') DEFAULT NULL,
  `snapshot_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cycle_audit_log`
--

CREATE TABLE `cycle_audit_log` (
  `log_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `stage_from` varchar(30) DEFAULT NULL,
  `stage_to` varchar(30) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cycle_audit_log`
--

INSERT INTO `cycle_audit_log` (`log_id`, `cycle_id`, `stage_from`, `stage_to`, `actor_id`, `notes`, `created_at`) VALUES
(16, 26, 'submitted', 'validated', 46, 'Validated by coordinator.', '2026-08-02 13:48:53'),
(17, 26, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-08-02 13:48:53'),
(18, 27, 'submitted', 'validated', 46, 'Validated by coordinator.', '2026-08-02 22:10:38'),
(19, 27, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-08-02 22:10:38'),
(20, 28, 'submitted', 'validated', 46, 'Validated by coordinator.', '2026-08-03 22:41:00'),
(21, 28, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-08-03 22:41:00');

-- --------------------------------------------------------

--
-- Table structure for table `cycle_evaluators`
--

CREATE TABLE `cycle_evaluators` (
  `evaluator_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = deactivated for this cycle',
  `deactivated_at` datetime DEFAULT NULL,
  `reactivated_at` datetime DEFAULT NULL,
  `custom_access_end` datetime DEFAULT NULL COMMENT 'Override the cycle-level end date for this specific evaluator'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cycle_evaluator_status_log`
--

CREATE TABLE `cycle_evaluator_status_log` (
  `log_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `action` enum('activated','deactivated','reactivated') NOT NULL,
  `triggered_by` enum('cron','manual','admin') NOT NULL DEFAULT 'manual',
  `actor_id` int(11) DEFAULT NULL COMMENT 'admin user_id; NULL for cron',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cycle_stage_gates`
--

CREATE TABLE `cycle_stage_gates` (
  `gate_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `from_stage` varchar(30) NOT NULL,
  `to_stage` varchar(30) NOT NULL,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `checked_by` int(11) DEFAULT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `blocker_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `school_id`, `name`, `description`, `created_at`) VALUES
(1, 1, 'English', NULL, '2026-06-07 19:43:18'),
(2, 1, 'Information Technology', NULL, '2026-06-07 19:51:01'),
(3, 1, 'Filipino', NULL, '2026-06-21 19:10:26'),
(4, 1, 'Guidance', NULL, '2026-06-21 19:16:55');

-- --------------------------------------------------------

--
-- Table structure for table `doc_import_usage`
--

CREATE TABLE `doc_import_usage` (
  `user_id` int(11) NOT NULL,
  `usage_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `reset_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doc_import_usage`
--

INSERT INTO `doc_import_usage` (`user_id`, `usage_count`, `reset_date`) VALUES
(46, 4, '2026-08-08');

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
(29, 46, 'account_creation', 'mendozacharles11011@gmail.com', 'sent', NULL, '2026-04-01 02:35:53');

-- --------------------------------------------------------

--
-- Table structure for table `evidence_audit_log`
--

CREATE TABLE `evidence_audit_log` (
  `audit_id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `actor_role` varchar(40) DEFAULT NULL,
  `action` enum('upload','delete','replace','view','download') NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form_versions`
--

CREATE TABLE `form_versions` (
  `version_id` int(11) NOT NULL,
  `version_number` int(11) NOT NULL,
  `label` varchar(60) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `form_versions`
--

INSERT INTO `form_versions` (`version_id`, `version_number`, `label`, `is_active`, `created_by`, `created_at`, `published_at`) VALUES
(1, 1, 'Initial Form (DO 007 s.2024)', 1, NULL, '2026-06-01 11:34:31', '2026-06-01 19:34:31');

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
  `workflow_status` varchar(30) NOT NULL DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `improvement_plans`
--

INSERT INTO `improvement_plans` (`plan_id`, `school_id`, `cycle_id`, `dimension_id`, `indicator_id`, `priority_level`, `objective`, `strategy`, `person_responsible`, `target_date`, `resources_needed`, `expected_output`, `status`, `workflow_status`, `remarks`, `created_by`, `submitted_by`, `submitted_at`, `created_at`, `updated_at`) VALUES
(60, 1, 28, 3, 19, 'Medium', 'The school aims to develop and implement a revised strategic plan that aligns with its vision and mission, and includes specific, measurable goals and objectives, by the end of the next academic year. This plan will be formulated through a collaborative process involving all stakeholders, ensuring ownership and commitment to its implementation and attainment of its objectives.', 'The school will revise its strategic plan to include specific, measurable goals and objectives that align with its vision and mission, as mandated by DepEd Order No. 007, s. 2024. This revision process will be inclusive, engaging all stakeholders to foster ownership and commitment, and will be led by the School Head in collaboration with the school\'s planning team. The revised strategic plan will be finalized and disseminated to all stakeholders within a specified timeframe to ensure a unified direction for the school.', 'SBM Coordinator', '2026-08-25', 'collaboration with other stakeholders', NULL, 'planned', 'draft', NULL, 37, NULL, NULL, '2026-08-05 03:37:15', '2026-08-05 03:37:15');

-- --------------------------------------------------------

--
-- Table structure for table `indicator_evidence_requirements`
--

CREATE TABLE `indicator_evidence_requirements` (
  `req_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `required_count` tinyint(4) DEFAULT 1,
  `allowed_categories` varchar(200) DEFAULT 'photo,document,report,certificate,record,other',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ip_field_usage`
--

CREATE TABLE `ip_field_usage` (
  `user_id` int(11) NOT NULL,
  `field_type` enum('objective','strategy') NOT NULL,
  `usage_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `reset_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ip_field_usage`
--

INSERT INTO `ip_field_usage` (`user_id`, `field_type`, `usage_count`, `reset_date`) VALUES
(37, 'objective', 1, '2026-08-07'),
(37, 'strategy', 1, '2026-08-07'),
(46, '', 0, '2026-08-08');

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
  `maturity_level` enum('Developing','Maturing','Advanced') DEFAULT NULL,
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
(36, 46, '25de0e85006db5c333e8d45d3733be2eff8b27e9fa2207f55e070ffac278a993', 'setup', '2026-04-03 10:35:08', '2026-04-01 10:40:39', '2026-04-01 02:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `response_attachments`
--

CREATE TABLE `response_attachments` (
  `attachment_id` int(11) NOT NULL,
  `version` tinyint(4) NOT NULL DEFAULT 1,
  `parent_attachment_id` int(11) DEFAULT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploader_role` varchar(40) NOT NULL,
  `category` enum('photo','document','report','certificate','record','other') DEFAULT 'other',
  `is_current_version` tinyint(1) DEFAULT 1,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `replace_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `label` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#16A34A',
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `slug`, `label`, `color`, `description`, `is_system`, `created_at`) VALUES
(1, 'system_admin', 'System Admin', '#7C3AED', NULL, 1, '2026-06-05 09:36:29'),
(2, 'school_head', 'School Head', '#166534', NULL, 1, '2026-06-05 09:36:29'),
(3, 'sbm_coordinator', 'SBM Coordinator', '#2563EB', NULL, 1, '2026-06-05 09:36:29'),
(4, 'teacher', 'School Teacher', '#0D9488', NULL, 1, '2026-06-05 09:36:29'),
(5, 'external_stakeholder', 'External Stakeholder', '#D97706', NULL, 1, '2026-06-05 09:36:29'),
(11, 'tambay', 'Tambay', '#64748B', 'Yelo', 0, '2026-06-07 11:42:07');

-- --------------------------------------------------------

--
-- Table structure for table `sbm_cycles`
--

CREATE TABLE `sbm_cycles` (
  `cycle_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `status` enum('draft','setup','assigning','in_progress','consolidating','submitted','returned','validated','finalized') DEFAULT 'draft',
  `overall_score` decimal(5,2) DEFAULT NULL,
  `maturity_level` enum('Developing','Maturing','Advanced') DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validator_remarks` text DEFAULT NULL,
  `consolidation_confirmed` tinyint(1) DEFAULT 0,
  `consolidation_confirmed_by` int(11) DEFAULT NULL,
  `consolidation_confirmed_at` datetime DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `return_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stakeholder_access_start` datetime DEFAULT NULL COMMENT 'When external stakeholder accounts become active for this cycle',
  `stakeholder_access_end` datetime DEFAULT NULL COMMENT 'When external stakeholder accounts are automatically deactivated',
  `auto_deactivated_at` datetime DEFAULT NULL COMMENT 'Timestamp of the last auto-deactivation run for this cycle',
  `auto_deactivated_by` varchar(40) DEFAULT NULL COMMENT 'How deactivation was triggered: cron | manual | api'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_cycles`
--

INSERT INTO `sbm_cycles` (`cycle_id`, `sy_id`, `school_id`, `status`, `overall_score`, `maturity_level`, `started_at`, `submitted_at`, `validated_at`, `validated_by`, `validator_remarks`, `consolidation_confirmed`, `consolidation_confirmed_by`, `consolidation_confirmed_at`, `finalized_at`, `returned_at`, `returned_by`, `return_remarks`, `created_at`, `stakeholder_access_start`, `stakeholder_access_end`, `auto_deactivated_at`, `auto_deactivated_by`) VALUES
(26, 25, 1, 'finalized', 61.37, 'Maturing', '2026-08-02 11:55:12', '2026-08-02 13:44:45', '2026-08-02 13:48:53', 46, '', 0, NULL, NULL, '2026-08-02 13:48:53', NULL, NULL, NULL, '2026-08-02 03:55:12', NULL, NULL, NULL, NULL),
(27, 26, 1, 'finalized', 70.60, 'Advanced', '2026-08-02 21:48:41', '2026-08-02 22:10:04', '2026-08-02 22:10:38', 46, '', 0, NULL, NULL, '2026-08-02 22:10:38', NULL, NULL, NULL, '2026-08-02 13:48:41', NULL, NULL, NULL, NULL),
(28, 27, 1, 'finalized', 70.00, '', '2026-08-03 22:35:33', '2026-08-03 22:40:34', '2026-08-03 22:41:00', 46, '', 0, NULL, NULL, '2026-08-03 22:41:00', NULL, NULL, NULL, '2026-08-03 14:35:33', NULL, NULL, NULL, NULL),
(29, 28, 1, 'in_progress', NULL, NULL, '2026-08-08 09:06:49', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-08 01:06:49', NULL, NULL, NULL, NULL);

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
  `indicator_count` int(11) DEFAULT 0,
  `form_version_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_dimensions`
--

INSERT INTO `sbm_dimensions` (`dimension_id`, `dimension_no`, `dimension_name`, `color_hex`, `icon`, `indicator_count`, `form_version_id`) VALUES
(1, 1, 'Curriculum and Teaching', '#2563EB', 'book', 8, 1),
(2, 2, 'Learning Environment', '#16A34A', 'home', 10, 1),
(3, 3, 'Leadership', '#7C3AED', 'star', 4, 1),
(4, 4, 'Governance and Accountability', '#D97706', 'check-circle', 6, 1),
(5, 5, 'Human Resources and Team Development', '#DC2626', 'users', 7, 1),
(6, 6, 'Finance and Resource Management and Mobilization', '#0D9488', 'dollar-sign', 7, 1);

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
(1113, 26, 1, 1, 19.70, 32.00, 61.56, '2026-08-02 05:44:45'),
(1114, 26, 1, 2, 21.90, 40.00, 54.75, '2026-08-02 05:44:45'),
(1115, 26, 1, 3, 9.00, 16.00, 56.25, '2026-08-02 05:44:45'),
(1116, 26, 1, 4, 16.00, 24.00, 66.67, '2026-08-02 05:44:45'),
(1117, 26, 1, 5, 17.50, 28.00, 62.50, '2026-08-02 05:44:45'),
(1118, 26, 1, 6, 19.00, 28.00, 67.86, '2026-08-02 05:44:45'),
(1226, 27, 1, 1, 22.70, 32.00, 70.94, '2026-08-02 14:10:04'),
(1227, 27, 1, 2, 29.10, 40.00, 72.75, '2026-08-02 14:10:04'),
(1228, 27, 1, 3, 10.10, 16.00, 63.13, '2026-08-02 14:10:04'),
(1229, 27, 1, 4, 18.00, 24.00, 75.00, '2026-08-02 14:10:04'),
(1230, 27, 1, 5, 18.30, 28.00, 65.36, '2026-08-02 14:10:04'),
(1231, 27, 1, 6, 20.40, 28.00, 72.86, '2026-08-02 14:10:04'),
(1279, 28, 1, 1, 22.20, 32.00, 69.38, '2026-08-03 14:40:34'),
(1280, 28, 1, 2, 30.10, 40.00, 75.25, '2026-08-03 14:40:34'),
(1281, 28, 1, 3, 10.50, 16.00, 65.63, '2026-08-03 14:40:34'),
(1282, 28, 1, 4, 15.00, 24.00, 62.50, '2026-08-03 14:40:34'),
(1283, 28, 1, 5, 18.80, 28.00, 67.14, '2026-08-03 14:40:34'),
(1284, 28, 1, 6, 21.00, 28.00, 75.00, '2026-08-03 14:40:34'),
(1333, 29, 1, 1, 0.00, 0.00, 0.00, '2026-08-08 01:06:49'),
(1335, 29, 1, 2, 0.00, 0.00, 0.00, '2026-08-08 01:06:49'),
(1337, 29, 1, 3, 0.00, 0.00, 0.00, '2026-08-08 01:06:49'),
(1339, 29, 1, 4, 0.00, 0.00, 0.00, '2026-08-08 01:06:49'),
(1341, 29, 1, 5, 0.00, 0.00, 0.00, '2026-08-08 01:06:49'),
(1343, 29, 1, 6, 0.00, 0.00, 0.00, '2026-08-08 01:06:49');

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
  `is_active` tinyint(1) DEFAULT 1,
  `form_version_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_indicators`
--

INSERT INTO `sbm_indicators` (`indicator_id`, `dimension_id`, `indicator_code`, `indicator_text`, `mov_guide`, `sort_order`, `is_active`, `form_version_id`) VALUES
(1, 1, '1.1', 'Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.', 'MPS/proficiency data, class records, early language and literacy assessment results', 1, 1, 1),
(2, 1, '1.2', 'Grade 6, 10, and 12 learners achieve the proficiency level in all 21st-century skills and core learning areas in the National Achievement Test (NAT).', 'NAT results, MPS data, class records', 2, 1, 1),
(3, 1, '1.3', 'School-based ALS learners attain certification as elementary and junior high school completers.', 'ALS completion certificates, enrollment and completion records', 3, 1, 1),
(4, 1, '1.4', 'Teachers prepare contextualized learning materials responsive to the needs of learners.', 'Developed contextualized LMs, LRMDS uploads, utilization records', 4, 1, 1),
(5, 1, '1.5', 'Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.', 'Remediation program designs, attendance records, monitoring reports', 5, 1, 1),
(6, 1, '1.6', 'Teachers integrate topics promoting peace and DepEd core values.', 'Lesson plans, classroom observations, LAC session minutes', 6, 1, 1),
(7, 1, '1.7', 'The school conducts test item analysis to inform its teaching and learning process.', 'Item analysis reports, action plans based on findings, LAC minutes', 7, 1, 1),
(8, 1, '1.8', 'The school engages local industries to strengthen its TLE-TVL course offerings.', 'MOA with industry partners, NC/COC certificates, industry immersion records', 8, 1, 1),
(9, 2, '2.1', 'The school has zero bullying incidence.', 'Anti-bullying policy, incident reports, monitoring logs', 9, 1, 1),
(10, 2, '2.2', 'The school has zero child abuse incidence.', 'CPC records, incident reports, referral documents', 10, 1, 1),
(11, 2, '2.3', 'The school has reduced its drop-out incidence.', 'Enrollment/completion data, BEIS reports, intervention records', 11, 1, 1),
(12, 2, '2.4', 'The school conducts culture-sensitive activities.', 'Activity programs, photo documentation, feedback forms', 12, 1, 1),
(13, 2, '2.5', 'The school provides access to learning experiences for the disadvantaged, OSYs, and adult learners.', 'OSY mapping, ALS enrollment records, inclusion program documents', 13, 1, 1),
(14, 2, '2.6', 'The school has a functional school-based ALS program.', 'ALS program design, learner enrollment, completion reports', 14, 1, 1),
(15, 2, '2.7', 'The school has a functional child-protection committee.', 'CPC composition order, meeting minutes, activity reports', 15, 1, 1),
(16, 2, '2.8', 'The school has a functional DRRM plan.', 'DRRM plan, drill documentation, hazard maps', 16, 1, 1),
(17, 2, '2.9', 'The school has a functional support mechanism for mental wellness.', 'Wellness program design, referral records, accomplishment reports', 17, 1, 1),
(18, 2, '2.10', 'The school has special education- and PWD-friendly facilities.', 'Accessibility audit, ramp/facility photos, SPED program records', 18, 1, 1),
(19, 3, '3.1', 'The school develops a strategic plan.', 'SIP/strategic plan document, stakeholder attendance, accomplishment reports', 19, 1, 1),
(20, 3, '3.2', 'The school has a functional school-community planning team.', 'Planning team composition, meeting minutes, activity reports', 20, 1, 1),
(21, 3, '3.3', 'The school has a functional Supreme Student Government/Supreme Pupil Government.', 'SSG/SPG constitution, election records, program accomplishments', 21, 1, 1),
(22, 3, '3.4', 'The school innovates in its provision of frontline services to stakeholders.', 'Innovation documentation, feedback/evaluation, impact data', 22, 1, 1),
(23, 4, '4.1', 'The school\'s strategic plan is operationalized through an implementation plan.', 'Implementation plan, accomplishment reports, M&E records', 23, 1, 1),
(24, 4, '4.2', 'The school has a functional School Governance Council (SGC).', 'SGC composition order, meeting minutes, resolutions', 24, 1, 1),
(25, 4, '4.3', 'The school has a functional Parent-Teacher Association (PTA).', 'PTA election records, meeting minutes, financial reports', 25, 1, 1),
(26, 4, '4.4', 'The school collaborates with stakeholders and other schools in strengthening partnerships.', 'MOA/MOU documents, partnership activity reports, resource contributions', 26, 1, 1),
(27, 4, '4.5', 'The school monitors and evaluates its programs, projects, and activities.', 'M&E plan, monitoring reports, action plans based on findings', 27, 1, 1),
(28, 4, '4.6', 'The school maintains an average rating of satisfactory from its internal and external stakeholders.', 'Stakeholder satisfaction survey results, tabulated data, action plans', 28, 1, 1),
(29, 5, '5.1', 'School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.', 'Signed IPCR forms, summary rating sheets, submission records', 29, 1, 1),
(30, 5, '5.2', 'The school achieves an average rating of very satisfactory in the office performance commitment and review.', 'OPCR rating sheets, division evaluation results', 30, 1, 1),
(31, 5, '5.3', 'The school conducts needs-based Learning Action Cells and Learning & Development activities.', 'LAC session plans, attendance, minutes, action plans, L&D records', 31, 1, 1),
(32, 5, '5.4', 'The school facilitates the promotion and continuous professional development of its personnel.', 'Training certificates, individual development plans, PDO records', 32, 1, 1),
(33, 5, '5.5', 'The school recognizes and rewards milestone achievements of its personnel.', 'Recognition program design, awarding documentation, photos', 33, 1, 1),
(34, 5, '5.6', 'The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.', 'Payroll records, DTR, allowance vouchers, personnel feedback', 34, 1, 1),
(35, 5, '5.7', 'Teacher workload is distributed fairly and equitably.', 'Teaching load summary, class schedule, assignment orders', 35, 1, 1),
(36, 6, '6.1', 'The school inspects its infrastructure and facilities.', 'Facilities inspection report, checklist, photos', 36, 1, 1),
(37, 6, '6.2', 'The school initiates improvement of its infrastructure and facilities.', 'Maintenance/improvement plan, work orders, accomplishment reports, photos', 37, 1, 1),
(38, 6, '6.3', 'The school has a functional library.', 'Library inventory, acquisition records, utilization logs', 38, 1, 1),
(39, 6, '6.4', 'The school has functional water, electricity, and internet facilities.', 'Utility bills, repair records, functionality assessment', 39, 1, 1),
(40, 6, '6.5', 'The school has a functional computer laboratory/classroom.', 'Lab inventory, equipment condition report, utilization records', 40, 1, 1),
(41, 6, '6.6', 'The school achieves a 75–100% utilization rate of its Maintenance and Other Operating Expenses (MOOE).', 'MOOE liquidation reports, utilization matrix, COB vs. actual', 41, 1, 1),
(42, 6, '6.7', 'The school liquidates 100% of its utilized MOOE.', 'Liquidation reports, submission acknowledgments, COA records', 42, 1, 1);

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
(863, 26, 1, 1, 2, '', 37, '2026-08-02 05:43:14'),
(864, 26, 2, 1, 2, '', 37, '2026-08-02 05:43:15'),
(865, 26, 3, 1, 3, '', 37, '2026-08-02 05:43:17'),
(866, 26, 4, 1, 2, '', 37, '2026-08-02 05:43:18'),
(867, 26, 5, 1, 2, '', 37, '2026-08-02 05:43:19'),
(868, 26, 6, 1, 3, '', 37, '2026-08-02 05:43:21'),
(869, 26, 7, 1, 2, '', 37, '2026-08-02 05:43:22'),
(870, 26, 8, 1, 3, '', 37, '2026-08-02 05:43:24'),
(871, 26, 9, 1, 2, '', 37, '2026-08-02 05:43:27'),
(872, 26, 10, 1, 2, '', 37, '2026-08-02 05:43:28'),
(873, 26, 11, 1, 2, '', 37, '2026-08-02 05:43:30'),
(874, 26, 12, 1, 2, '', 37, '2026-08-02 05:43:36'),
(875, 26, 13, 1, 2, '', 37, '2026-08-02 05:43:33'),
(876, 26, 14, 1, 3, '', 37, '2026-08-02 05:43:34'),
(878, 26, 15, 1, 2, '', 37, '2026-08-02 05:43:38'),
(879, 26, 16, 1, 3, '', 37, '2026-08-02 05:43:40'),
(880, 26, 17, 1, 2, '', 37, '2026-08-02 05:43:41'),
(881, 26, 18, 1, 2, '', 37, '2026-08-02 05:43:42'),
(882, 26, 19, 1, 2, '', 37, '2026-08-02 05:43:44'),
(883, 26, 20, 1, 2, '', 37, '2026-08-02 05:43:52'),
(884, 26, 21, 1, 3, '', 37, '2026-08-02 05:43:54'),
(885, 26, 22, 1, 2, '', 37, '2026-08-02 05:43:55'),
(886, 26, 23, 1, 3, '', 37, '2026-08-02 05:44:00'),
(887, 26, 24, 1, 2, '', 37, '2026-08-02 05:44:00'),
(888, 26, 25, 1, 2, '', 37, '2026-08-02 05:44:02'),
(889, 26, 26, 1, 3, '', 37, '2026-08-02 05:44:03'),
(890, 26, 27, 1, 3, '', 37, '2026-08-02 05:44:04'),
(891, 26, 28, 1, 3, '', 37, '2026-08-02 05:44:06'),
(892, 26, 29, 1, 2, '', 37, '2026-08-02 05:44:08'),
(893, 26, 30, 1, 3, '', 37, '2026-08-02 05:44:10'),
(894, 26, 31, 1, 2, '', 37, '2026-08-02 05:44:12'),
(895, 26, 32, 1, 3, '', 37, '2026-08-02 05:44:14'),
(896, 26, 33, 1, 3, '', 37, '2026-08-02 05:44:16'),
(897, 26, 34, 1, 3, '', 37, '2026-08-02 05:44:18'),
(898, 26, 35, 1, 2, '', 37, '2026-08-02 05:44:19'),
(899, 26, 36, 1, 3, '', 37, '2026-08-02 05:44:22'),
(900, 26, 37, 1, 2, '', 37, '2026-08-02 05:44:23'),
(901, 26, 38, 1, 3, '', 37, '2026-08-02 05:44:24'),
(902, 26, 39, 1, 3, '', 37, '2026-08-02 05:44:25'),
(903, 26, 40, 1, 3, '', 37, '2026-08-02 05:44:27'),
(904, 26, 41, 1, 2, '', 37, '2026-08-02 05:44:28'),
(905, 26, 42, 1, 3, '', 37, '2026-08-02 05:44:30'),
(906, 27, 1, 1, 3, '', 37, '2026-08-02 14:08:44'),
(907, 27, 2, 1, 3, '', 37, '2026-08-02 14:08:45'),
(908, 27, 3, 1, 2, '', 37, '2026-08-02 14:08:47'),
(909, 27, 4, 1, 3, '', 37, '2026-08-02 14:08:48'),
(910, 27, 5, 1, 4, '', 37, '2026-08-02 14:08:49'),
(911, 27, 6, 1, 3, '', 37, '2026-08-02 14:08:50'),
(912, 27, 7, 1, 2, '', 37, '2026-08-02 14:08:52'),
(913, 27, 8, 1, 3, '', 37, '2026-08-02 14:08:54'),
(914, 27, 9, 1, 3, '', 37, '2026-08-02 14:08:55'),
(915, 27, 10, 1, 2, '', 37, '2026-08-02 14:08:56'),
(916, 27, 11, 1, 3, '', 37, '2026-08-02 14:08:58'),
(917, 27, 12, 1, 3, '', 37, '2026-08-02 14:08:59'),
(918, 27, 13, 1, 2, '', 37, '2026-08-02 14:09:01'),
(919, 27, 14, 1, 4, '', 37, '2026-08-02 14:09:02'),
(920, 27, 15, 1, 3, '', 37, '2026-08-02 14:09:03'),
(921, 27, 16, 1, 3, '', 37, '2026-08-02 14:09:05'),
(922, 27, 17, 1, 2, '', 37, '2026-08-02 14:09:06'),
(923, 27, 18, 1, 3, '', 37, '2026-08-02 14:09:07'),
(924, 27, 19, 1, 3, '', 37, '2026-08-02 14:09:09'),
(925, 27, 20, 1, 2, '', 37, '2026-08-02 14:09:10'),
(926, 27, 21, 1, 3, '', 37, '2026-08-02 14:09:12'),
(927, 27, 22, 1, 2, '', 37, '2026-08-02 14:09:15'),
(928, 27, 23, 1, 3, '', 37, '2026-08-02 14:09:18'),
(929, 27, 24, 1, 4, '', 37, '2026-08-02 14:09:19'),
(930, 27, 25, 1, 3, '', 37, '2026-08-02 14:09:20'),
(931, 27, 26, 1, 2, '', 37, '2026-08-02 14:09:22'),
(932, 27, 27, 1, 3, '', 37, '2026-08-02 14:09:23'),
(933, 27, 28, 1, 3, '', 37, '2026-08-02 14:09:24'),
(934, 27, 29, 1, 2, '', 37, '2026-08-02 14:09:26'),
(935, 27, 30, 1, 2, '', 37, '2026-08-02 14:09:28'),
(936, 27, 31, 1, 3, '', 37, '2026-08-02 14:09:29'),
(937, 27, 32, 1, 3, '', 37, '2026-08-02 14:09:31'),
(938, 27, 34, 1, 2, '', 37, '2026-08-02 14:09:35'),
(939, 27, 35, 1, 2, '', 37, '2026-08-02 14:09:36'),
(940, 27, 36, 1, 3, '', 37, '2026-08-02 14:09:38'),
(941, 27, 37, 1, 4, '', 37, '2026-08-02 14:09:39'),
(942, 27, 38, 1, 3, '', 37, '2026-08-02 14:09:40'),
(943, 27, 39, 1, 2, '', 37, '2026-08-02 14:09:41'),
(944, 27, 40, 1, 3, '', 37, '2026-08-02 14:09:42'),
(945, 27, 41, 1, 3, '', 37, '2026-08-02 14:09:44'),
(946, 27, 42, 1, 2, '', 37, '2026-08-02 14:09:46'),
(947, 28, 1, 1, 3, '', 37, '2026-08-03 14:39:27'),
(948, 28, 2, 1, 2, '', 37, '2026-08-03 14:39:28'),
(949, 28, 3, 1, 3, '', 37, '2026-08-03 14:39:30'),
(950, 28, 4, 1, 3, '', 37, '2026-08-03 14:39:32'),
(951, 28, 5, 1, 4, '', 37, '2026-08-03 14:39:33'),
(952, 28, 6, 1, 3, '', 37, '2026-08-03 14:39:35'),
(953, 28, 7, 1, 2, '', 37, '2026-08-03 14:39:37'),
(954, 28, 8, 1, 2, '', 37, '2026-08-03 14:39:38'),
(955, 28, 9, 1, 3, '', 37, '2026-08-03 14:39:39'),
(956, 28, 10, 1, 3, '', 37, '2026-08-03 14:39:41'),
(957, 28, 11, 1, 2, '', 37, '2026-08-03 14:39:42'),
(958, 28, 12, 1, 3, '', 37, '2026-08-03 14:39:44'),
(959, 28, 13, 1, 4, '', 37, '2026-08-03 14:39:45'),
(960, 28, 14, 1, 3, '', 37, '2026-08-03 14:39:47'),
(961, 28, 15, 1, 3, '', 37, '2026-08-03 14:39:48'),
(962, 28, 16, 1, 2, '', 37, '2026-08-03 14:39:49'),
(963, 28, 17, 1, 3, '', 37, '2026-08-03 14:39:50'),
(964, 28, 18, 1, 4, '', 37, '2026-08-03 14:39:52'),
(965, 28, 19, 1, 2, '', 37, '2026-08-03 14:39:55'),
(966, 28, 20, 1, 3, '', 37, '2026-08-03 14:39:56'),
(967, 28, 21, 1, 3, '', 37, '2026-08-03 14:39:57'),
(968, 28, 22, 1, 2, '', 37, '2026-08-03 14:40:00'),
(969, 28, 23, 1, 2, '', 37, '2026-08-03 14:40:01'),
(970, 28, 24, 1, 2, '', 37, '2026-08-03 14:40:03'),
(971, 28, 25, 1, 3, '', 37, '2026-08-03 14:40:04'),
(972, 28, 26, 1, 3, '', 37, '2026-08-03 14:40:05'),
(973, 28, 27, 1, 3, '', 37, '2026-08-03 14:40:07'),
(974, 28, 28, 1, 2, '', 37, '2026-08-03 14:40:08'),
(975, 28, 29, 1, 4, '', 37, '2026-08-03 14:40:11'),
(976, 28, 30, 1, 2, '', 37, '2026-08-03 14:40:12'),
(977, 28, 31, 1, 2, '', 37, '2026-08-03 14:40:14'),
(978, 28, 32, 1, 3, '', 37, '2026-08-03 14:40:15'),
(979, 28, 33, 1, 3, '', 37, '2026-08-03 14:40:17'),
(980, 28, 34, 1, 2, '', 37, '2026-08-03 14:40:19'),
(981, 28, 35, 1, 2, '', 37, '2026-08-03 14:40:20'),
(982, 28, 36, 1, 4, '', 37, '2026-08-03 14:40:22'),
(983, 28, 37, 1, 4, '', 37, '2026-08-03 14:40:24'),
(984, 28, 38, 1, 3, '', 37, '2026-08-03 14:40:25'),
(985, 28, 39, 1, 2, '', 37, '2026-08-03 14:40:27'),
(986, 28, 40, 1, 3, '', 37, '2026-08-03 14:40:29'),
(987, 28, 41, 1, 3, '', 37, '2026-08-03 14:40:31'),
(988, 28, 42, 1, 2, '', 37, '2026-08-03 14:40:32');

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
  `division_name` varchar(100) DEFAULT NULL,
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

INSERT INTO `schools` (`school_id`, `school_name`, `division_name`, `school_id_deped`, `address`, `classification`, `school_head_name`, `contact_no`, `email`, `total_enrollment`, `total_teachers`, `created_at`) VALUES
(1, 'Dasmariñas Integrated High School', 'Cavite Division', '301143', 'Dasmariñas City, Cavite', 'JHS', 'Ryza Evangelio', '', 'dihs.edu.ph', 2500, 5, '2026-03-11 16:18:36');

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
  `overall_status` enum('not_started','setup','assigning','in_progress','consolidating','submitted','returned','validated','finalized') DEFAULT 'not_started',
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
(25, '2023-2024', 0, '2023-08-29', '2024-05-31'),
(26, '2024-2025', 0, '2024-07-29', '2025-04-15'),
(27, '2025-2026', 0, '2025-06-16', '2026-03-31'),
(28, '2026-2027', 1, '2026-06-08', '2027-04-08');

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
  `status` enum('draft','submitted') DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
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

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`config_id`, `config_key`, `config_value`, `updated_at`) VALUES
(1, 'stakeholder_email_notify_days_before', '3', '2026-04-11 10:31:52'),
(2, 'stakeholder_auto_deactivate_enabled', '1', '2026-04-11 10:31:52'),
(3, 'stakeholder_reactivation_default_days', '7', '2026-04-11 10:31:52');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('sbm_maturity_bands', '[{\"min\":0,\"max\":37.49,\"level\":1,\"label\":\"Developing\",\"color\":\"#D97706\",\"bg\":\"#FEF3C7\"},{\"min\":37.5,\"max\":62.49,\"level\":2,\"label\":\"Maturing\",\"color\":\"#2563EB\",\"bg\":\"#DBEAFE\"},{\"min\":62.5,\"max\":100,\"level\":3,\"label\":\"Advanced (Accredited)\",\"color\":\"#16A34A\",\"bg\":\"#DCFCE7\"}]', '2026-08-01 12:20:24');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_indicator_assignments`
--

CREATE TABLE `teacher_indicator_assignments` (
  `assignment_id` int(11) NOT NULL,
  `cycle_id` int(11) DEFAULT NULL COMMENT 'Links assignment to a specific SBM cycle',
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
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_responses`
--

INSERT INTO `teacher_responses` (`tr_id`, `cycle_id`, `indicator_id`, `school_id`, `teacher_id`, `rating`, `remarks`, `status`, `submitted_at`, `created_at`, `updated_at`) VALUES
(2177, 26, 1, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:37', '2026-08-02 05:20:43'),
(2178, 26, 2, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:01:39', '2026-08-02 05:20:43'),
(2179, 26, 4, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:41', '2026-08-02 05:20:43'),
(2180, 26, 5, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:43', '2026-08-02 05:20:43'),
(2181, 26, 6, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:44', '2026-08-02 05:20:43'),
(2182, 26, 7, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:01:45', '2026-08-02 05:20:43'),
(2183, 26, 9, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:47', '2026-08-02 05:20:43'),
(2184, 26, 10, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:49', '2026-08-02 05:20:43'),
(2185, 26, 11, 1, 15, 1, '', 'submitted', NULL, '2026-08-02 05:01:51', '2026-08-02 05:20:43'),
(2186, 26, 12, 1, 15, 1, '', 'submitted', NULL, '2026-08-02 05:01:52', '2026-08-02 05:20:43'),
(2188, 26, 17, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:55', '2026-08-02 05:20:43'),
(2189, 26, 21, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:01:56', '2026-08-02 05:20:43'),
(2190, 26, 29, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:01:59', '2026-08-02 05:20:43'),
(2191, 26, 31, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:02:00', '2026-08-02 05:20:43'),
(2192, 26, 32, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:02:03', '2026-08-02 05:20:43'),
(2193, 26, 33, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:02:05', '2026-08-02 05:20:43'),
(2194, 26, 34, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:02:07', '2026-08-02 05:20:43'),
(2195, 26, 35, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 05:02:09', '2026-08-02 05:20:43'),
(2196, 26, 38, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:02:10', '2026-08-02 05:20:43'),
(2197, 26, 39, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:02:11', '2026-08-02 05:20:43'),
(2198, 26, 40, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 05:02:13', '2026-08-02 05:20:43'),
(2200, 26, 1, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:21:01', '2026-08-02 05:24:59'),
(2201, 26, 2, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:21:02', '2026-08-02 05:24:59'),
(2202, 26, 4, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:21:03', '2026-08-02 05:24:59'),
(2203, 26, 5, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:21:04', '2026-08-02 05:24:59'),
(2204, 26, 6, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:21:05', '2026-08-02 05:24:59'),
(2205, 26, 7, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:21:07', '2026-08-02 05:24:59'),
(2206, 26, 9, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:31', '2026-08-02 05:24:59'),
(2208, 26, 10, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:34', '2026-08-02 05:24:59'),
(2210, 26, 11, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:24:35', '2026-08-02 05:24:59'),
(2211, 26, 12, 1, 2, 1, '', 'submitted', NULL, '2026-08-02 05:24:38', '2026-08-02 05:24:59'),
(2212, 26, 17, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:39', '2026-08-02 05:24:59'),
(2214, 26, 21, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:42', '2026-08-02 05:24:59'),
(2216, 26, 29, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:24:44', '2026-08-02 05:24:59'),
(2217, 26, 31, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:45', '2026-08-02 05:24:59'),
(2218, 26, 32, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:24:47', '2026-08-02 05:24:59'),
(2219, 26, 33, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:48', '2026-08-02 05:24:59'),
(2220, 26, 34, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 05:24:49', '2026-08-02 05:24:59'),
(2221, 26, 35, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:51', '2026-08-02 05:24:59'),
(2222, 26, 38, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:52', '2026-08-02 05:24:59'),
(2224, 26, 39, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:55', '2026-08-02 05:24:59'),
(2225, 26, 40, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 05:24:56', '2026-08-02 05:24:59'),
(2226, 26, 1, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:11', '2026-08-02 05:25:48'),
(2227, 26, 2, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:13', '2026-08-02 05:25:48'),
(2228, 26, 4, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:14', '2026-08-02 05:25:48'),
(2229, 26, 5, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:16', '2026-08-02 05:25:48'),
(2230, 26, 6, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:17', '2026-08-02 05:25:48'),
(2231, 26, 7, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:19', '2026-08-02 05:25:48'),
(2233, 26, 9, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:21', '2026-08-02 05:25:48'),
(2236, 26, 10, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:24', '2026-08-02 05:25:48'),
(2237, 26, 11, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:26', '2026-08-02 05:25:48'),
(2238, 26, 12, 1, 12, 1, '', 'submitted', NULL, '2026-08-02 05:25:28', '2026-08-02 05:25:48'),
(2239, 26, 17, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:30', '2026-08-02 05:25:48'),
(2240, 26, 21, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:31', '2026-08-02 05:25:48'),
(2241, 26, 29, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:33', '2026-08-02 05:25:48'),
(2242, 26, 31, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:35', '2026-08-02 05:25:48'),
(2243, 26, 32, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:36', '2026-08-02 05:25:48'),
(2244, 26, 33, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:37', '2026-08-02 05:25:48'),
(2245, 26, 34, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 05:25:38', '2026-08-02 05:25:48'),
(2246, 26, 35, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:40', '2026-08-02 05:25:48'),
(2247, 26, 38, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:41', '2026-08-02 05:25:48'),
(2248, 26, 39, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:42', '2026-08-02 05:25:48'),
(2249, 26, 40, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 05:25:44', '2026-08-02 05:25:48'),
(2250, 26, 1, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:32', '2026-08-02 05:28:01'),
(2251, 26, 2, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:33', '2026-08-02 05:28:01'),
(2252, 26, 4, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:34', '2026-08-02 05:28:01'),
(2253, 26, 5, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:35', '2026-08-02 05:28:01'),
(2254, 26, 6, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:36', '2026-08-02 05:28:01'),
(2255, 26, 7, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:37', '2026-08-02 05:28:01'),
(2256, 26, 9, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:39', '2026-08-02 05:28:01'),
(2257, 26, 10, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:40', '2026-08-02 05:28:01'),
(2258, 26, 11, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:43', '2026-08-02 05:28:01'),
(2259, 26, 12, 1, 13, 1, '', 'submitted', NULL, '2026-08-02 05:27:44', '2026-08-02 05:28:01'),
(2260, 26, 17, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:46', '2026-08-02 05:28:01'),
(2261, 26, 21, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:48', '2026-08-02 05:28:01'),
(2262, 26, 29, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:50', '2026-08-02 05:28:01'),
(2263, 26, 31, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:51', '2026-08-02 05:28:01'),
(2264, 26, 32, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:52', '2026-08-02 05:28:01'),
(2265, 26, 33, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:53', '2026-08-02 05:28:01'),
(2266, 26, 34, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:54', '2026-08-02 05:28:01'),
(2267, 26, 35, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 05:27:56', '2026-08-02 05:28:01'),
(2268, 26, 38, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:57', '2026-08-02 05:28:01'),
(2269, 26, 39, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:58', '2026-08-02 05:28:01'),
(2270, 26, 40, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 05:27:59', '2026-08-02 05:28:01'),
(2271, 26, 1, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:10', '2026-08-02 05:28:38'),
(2272, 26, 2, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:12', '2026-08-02 05:28:38'),
(2274, 26, 4, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:14', '2026-08-02 05:28:38'),
(2275, 26, 5, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:16', '2026-08-02 05:28:38'),
(2276, 26, 6, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:17', '2026-08-02 05:28:38'),
(2277, 26, 7, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:18', '2026-08-02 05:28:38'),
(2278, 26, 9, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:19', '2026-08-02 05:28:38'),
(2279, 26, 10, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:20', '2026-08-02 05:28:38'),
(2280, 26, 11, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:21', '2026-08-02 05:28:38'),
(2281, 26, 12, 1, 14, 1, '', 'submitted', NULL, '2026-08-02 05:28:23', '2026-08-02 05:28:38'),
(2282, 26, 17, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:24', '2026-08-02 05:28:38'),
(2283, 26, 21, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:26', '2026-08-02 05:28:38'),
(2284, 26, 29, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:27', '2026-08-02 05:28:38'),
(2285, 26, 31, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:28', '2026-08-02 05:28:38'),
(2286, 26, 32, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:29', '2026-08-02 05:28:38'),
(2287, 26, 33, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:30', '2026-08-02 05:28:38'),
(2288, 26, 34, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 05:28:32', '2026-08-02 05:28:38'),
(2289, 26, 35, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:33', '2026-08-02 05:28:38'),
(2290, 26, 38, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:34', '2026-08-02 05:28:38'),
(2291, 26, 39, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:35', '2026-08-02 05:28:38'),
(2292, 26, 40, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 05:28:36', '2026-08-02 05:28:38'),
(2293, 27, 1, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:48:53', '2026-08-02 13:49:26'),
(2294, 27, 2, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:48:54', '2026-08-02 13:49:26'),
(2295, 27, 4, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:48:56', '2026-08-02 13:49:26'),
(2296, 27, 5, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 13:48:57', '2026-08-02 13:49:26'),
(2297, 27, 6, 1, 15, 4, '', 'submitted', NULL, '2026-08-02 13:48:59', '2026-08-02 13:49:26'),
(2298, 27, 7, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:00', '2026-08-02 13:49:26'),
(2299, 27, 9, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 13:49:02', '2026-08-02 13:49:26'),
(2300, 27, 10, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:03', '2026-08-02 13:49:26'),
(2301, 27, 11, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:04', '2026-08-02 13:49:26'),
(2302, 27, 12, 1, 15, 4, '', 'submitted', NULL, '2026-08-02 13:49:06', '2026-08-02 13:49:26'),
(2303, 27, 17, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 13:49:08', '2026-08-02 13:49:26'),
(2304, 27, 21, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:10', '2026-08-02 13:49:26'),
(2305, 27, 29, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:11', '2026-08-02 13:49:26'),
(2306, 27, 31, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:12', '2026-08-02 13:49:26'),
(2307, 27, 32, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 13:49:13', '2026-08-02 13:49:26'),
(2308, 27, 33, 1, 15, 4, '', 'submitted', NULL, '2026-08-02 13:49:14', '2026-08-02 13:49:26'),
(2309, 27, 34, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:15', '2026-08-02 13:49:26'),
(2310, 27, 35, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:16', '2026-08-02 13:49:26'),
(2311, 27, 38, 1, 15, 2, '', 'submitted', NULL, '2026-08-02 13:49:17', '2026-08-02 13:49:26'),
(2312, 27, 39, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:19', '2026-08-02 13:49:26'),
(2313, 27, 40, 1, 15, 3, '', 'submitted', NULL, '2026-08-02 13:49:20', '2026-08-02 13:49:26'),
(2314, 27, 1, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:35', '2026-08-02 14:06:03'),
(2315, 27, 2, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:36', '2026-08-02 14:06:03'),
(2316, 27, 4, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 14:05:37', '2026-08-02 14:06:03'),
(2317, 27, 5, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:38', '2026-08-02 14:06:03'),
(2318, 27, 6, 1, 2, 4, '', 'submitted', NULL, '2026-08-02 14:05:40', '2026-08-02 14:06:03'),
(2319, 27, 7, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:41', '2026-08-02 14:06:03'),
(2320, 27, 9, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:42', '2026-08-02 14:06:03'),
(2321, 27, 10, 1, 2, 4, '', 'submitted', NULL, '2026-08-02 14:05:43', '2026-08-02 14:06:03'),
(2322, 27, 11, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 14:05:44', '2026-08-02 14:06:03'),
(2323, 27, 12, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:45', '2026-08-02 14:06:03'),
(2324, 27, 17, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:46', '2026-08-02 14:06:03'),
(2325, 27, 21, 1, 2, 4, '', 'submitted', NULL, '2026-08-02 14:05:47', '2026-08-02 14:06:03'),
(2326, 27, 29, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:49', '2026-08-02 14:06:03'),
(2327, 27, 31, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:50', '2026-08-02 14:06:03'),
(2328, 27, 32, 1, 2, 2, '', 'submitted', NULL, '2026-08-02 14:05:53', '2026-08-02 14:06:03'),
(2329, 27, 33, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:54', '2026-08-02 14:06:03'),
(2330, 27, 34, 1, 2, 4, '', 'submitted', NULL, '2026-08-02 14:05:55', '2026-08-02 14:06:03'),
(2331, 27, 35, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:56', '2026-08-02 14:06:03'),
(2332, 27, 38, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:57', '2026-08-02 14:06:03'),
(2333, 27, 39, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:58', '2026-08-02 14:06:03'),
(2334, 27, 40, 1, 2, 3, '', 'submitted', NULL, '2026-08-02 14:05:59', '2026-08-02 14:06:03'),
(2335, 27, 1, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:20', '2026-08-02 14:06:49'),
(2336, 27, 2, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:21', '2026-08-02 14:06:49'),
(2337, 27, 4, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 14:06:22', '2026-08-02 14:06:49'),
(2338, 27, 5, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:24', '2026-08-02 14:06:49'),
(2339, 27, 6, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:25', '2026-08-02 14:06:49'),
(2340, 27, 7, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:26', '2026-08-02 14:06:49'),
(2341, 27, 9, 1, 12, 4, '', 'submitted', NULL, '2026-08-02 14:06:28', '2026-08-02 14:06:49'),
(2342, 27, 10, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:29', '2026-08-02 14:06:49'),
(2343, 27, 11, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:30', '2026-08-02 14:06:49'),
(2344, 27, 12, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:32', '2026-08-02 14:06:49'),
(2345, 27, 17, 1, 12, 4, '', 'submitted', NULL, '2026-08-02 14:06:33', '2026-08-02 14:06:49'),
(2346, 27, 21, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:34', '2026-08-02 14:06:49'),
(2347, 27, 29, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:36', '2026-08-02 14:06:49'),
(2349, 27, 31, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:37', '2026-08-02 14:06:49'),
(2350, 27, 32, 1, 12, 2, '', 'submitted', NULL, '2026-08-02 14:06:38', '2026-08-02 14:06:49'),
(2351, 27, 33, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:40', '2026-08-02 14:06:49'),
(2352, 27, 34, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:41', '2026-08-02 14:06:49'),
(2353, 27, 35, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:42', '2026-08-02 14:06:49'),
(2354, 27, 38, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:45', '2026-08-02 14:06:49'),
(2355, 27, 39, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:46', '2026-08-02 14:06:49'),
(2356, 27, 40, 1, 12, 3, '', 'submitted', NULL, '2026-08-02 14:06:47', '2026-08-02 14:06:49'),
(2357, 27, 1, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:01', '2026-08-02 14:07:31'),
(2358, 27, 2, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:02', '2026-08-02 14:07:31'),
(2359, 27, 4, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 14:07:03', '2026-08-02 14:07:31'),
(2360, 27, 5, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:04', '2026-08-02 14:07:31'),
(2361, 27, 6, 1, 13, 4, '', 'submitted', NULL, '2026-08-02 14:07:05', '2026-08-02 14:07:31'),
(2363, 27, 7, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:08', '2026-08-02 14:07:31'),
(2364, 27, 9, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:10', '2026-08-02 14:07:31'),
(2365, 27, 10, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:11', '2026-08-02 14:07:31'),
(2366, 27, 11, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:13', '2026-08-02 14:07:31'),
(2367, 27, 12, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 14:07:14', '2026-08-02 14:07:31'),
(2368, 27, 17, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:16', '2026-08-02 14:07:31'),
(2369, 27, 21, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:17', '2026-08-02 14:07:31'),
(2370, 27, 29, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:18', '2026-08-02 14:07:31'),
(2371, 27, 31, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 14:07:19', '2026-08-02 14:07:31'),
(2372, 27, 32, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:20', '2026-08-02 14:07:31'),
(2373, 27, 33, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:22', '2026-08-02 14:07:31'),
(2374, 27, 34, 1, 13, 2, '', 'submitted', NULL, '2026-08-02 14:07:23', '2026-08-02 14:07:31'),
(2375, 27, 35, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:25', '2026-08-02 14:07:31'),
(2376, 27, 38, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:26', '2026-08-02 14:07:31'),
(2377, 27, 39, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:27', '2026-08-02 14:07:31'),
(2378, 27, 40, 1, 13, 3, '', 'submitted', NULL, '2026-08-02 14:07:28', '2026-08-02 14:07:31'),
(2379, 27, 1, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:40', '2026-08-02 14:08:10'),
(2380, 27, 2, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 14:07:41', '2026-08-02 14:08:10'),
(2381, 27, 4, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:42', '2026-08-02 14:08:10'),
(2382, 27, 5, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:43', '2026-08-02 14:08:10'),
(2383, 27, 6, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 14:07:44', '2026-08-02 14:08:10'),
(2384, 27, 7, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:46', '2026-08-02 14:08:10'),
(2385, 27, 9, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:47', '2026-08-02 14:08:10'),
(2386, 27, 10, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:48', '2026-08-02 14:08:10'),
(2387, 27, 11, 1, 14, 4, '', 'submitted', NULL, '2026-08-02 14:07:49', '2026-08-02 14:08:10'),
(2388, 27, 12, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:51', '2026-08-02 14:08:10'),
(2389, 27, 17, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:52', '2026-08-02 14:08:10'),
(2390, 27, 21, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:54', '2026-08-02 14:08:10'),
(2391, 27, 29, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:57', '2026-08-02 14:08:10'),
(2392, 27, 31, 1, 14, 2, '', 'submitted', NULL, '2026-08-02 14:07:58', '2026-08-02 14:08:10'),
(2393, 27, 32, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:07:59', '2026-08-02 14:08:10'),
(2394, 27, 33, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:08:00', '2026-08-02 14:08:10'),
(2395, 27, 34, 1, 14, 4, '', 'submitted', NULL, '2026-08-02 14:08:01', '2026-08-02 14:08:10'),
(2396, 27, 35, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:08:02', '2026-08-02 14:08:10'),
(2398, 27, 38, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:08:05', '2026-08-02 14:08:10'),
(2399, 27, 39, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:08:06', '2026-08-02 14:08:10'),
(2400, 27, 40, 1, 14, 3, '', 'submitted', NULL, '2026-08-02 14:08:07', '2026-08-02 14:08:10'),
(2401, 28, 1, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:43', '2026-08-03 14:36:13'),
(2402, 28, 2, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:44', '2026-08-03 14:36:13'),
(2403, 28, 4, 1, 15, 2, '', 'submitted', NULL, '2026-08-03 14:35:45', '2026-08-03 14:36:13'),
(2404, 28, 5, 1, 15, 2, '', 'submitted', NULL, '2026-08-03 14:35:47', '2026-08-03 14:36:13'),
(2405, 28, 6, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:48', '2026-08-03 14:36:13'),
(2406, 28, 7, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:50', '2026-08-03 14:36:13'),
(2407, 28, 9, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:51', '2026-08-03 14:36:13'),
(2408, 28, 10, 1, 15, 2, '', 'submitted', NULL, '2026-08-03 14:35:53', '2026-08-03 14:36:13'),
(2409, 28, 11, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:54', '2026-08-03 14:36:13'),
(2410, 28, 12, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:56', '2026-08-03 14:36:13'),
(2411, 28, 17, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:35:57', '2026-08-03 14:36:13'),
(2412, 28, 21, 1, 15, 4, '', 'submitted', NULL, '2026-08-03 14:35:58', '2026-08-03 14:36:13'),
(2413, 28, 29, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:36:00', '2026-08-03 14:36:13'),
(2414, 28, 31, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:36:01', '2026-08-03 14:36:13'),
(2415, 28, 32, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:36:02', '2026-08-03 14:36:13'),
(2416, 28, 33, 1, 15, 2, '', 'submitted', NULL, '2026-08-03 14:36:03', '2026-08-03 14:36:13'),
(2417, 28, 34, 1, 15, 4, '', 'submitted', NULL, '2026-08-03 14:36:05', '2026-08-03 14:36:13'),
(2418, 28, 35, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:36:06', '2026-08-03 14:36:13'),
(2419, 28, 38, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:36:07', '2026-08-03 14:36:13'),
(2420, 28, 39, 1, 15, 2, '', 'submitted', NULL, '2026-08-03 14:36:08', '2026-08-03 14:36:13'),
(2421, 28, 40, 1, 15, 3, '', 'submitted', NULL, '2026-08-03 14:36:09', '2026-08-03 14:36:13'),
(2422, 28, 1, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:24', '2026-08-03 14:36:51'),
(2423, 28, 2, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:25', '2026-08-03 14:36:51'),
(2424, 28, 4, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:26', '2026-08-03 14:36:51'),
(2425, 28, 5, 1, 2, 2, '', 'submitted', NULL, '2026-08-03 14:36:27', '2026-08-03 14:36:51'),
(2426, 28, 6, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:28', '2026-08-03 14:36:51'),
(2427, 28, 7, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:30', '2026-08-03 14:36:51'),
(2428, 28, 9, 1, 2, 2, '', 'submitted', NULL, '2026-08-03 14:36:31', '2026-08-03 14:36:51'),
(2429, 28, 10, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:32', '2026-08-03 14:36:51'),
(2430, 28, 11, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:33', '2026-08-03 14:36:51'),
(2431, 28, 12, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:34', '2026-08-03 14:36:51'),
(2432, 28, 17, 1, 2, 2, '', 'submitted', NULL, '2026-08-03 14:36:36', '2026-08-03 14:36:51'),
(2433, 28, 21, 1, 2, 4, '', 'submitted', NULL, '2026-08-03 14:36:37', '2026-08-03 14:36:51'),
(2434, 28, 29, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:39', '2026-08-03 14:36:51'),
(2435, 28, 31, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:40', '2026-08-03 14:36:51'),
(2436, 28, 32, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:41', '2026-08-03 14:36:51'),
(2437, 28, 33, 1, 2, 2, '', 'submitted', NULL, '2026-08-03 14:36:43', '2026-08-03 14:36:51'),
(2438, 28, 34, 1, 2, 4, '', 'submitted', NULL, '2026-08-03 14:36:44', '2026-08-03 14:36:51'),
(2439, 28, 35, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:45', '2026-08-03 14:36:51'),
(2440, 28, 38, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:46', '2026-08-03 14:36:51'),
(2441, 28, 39, 1, 2, 2, '', 'submitted', NULL, '2026-08-03 14:36:48', '2026-08-03 14:36:51'),
(2442, 28, 40, 1, 2, 3, '', 'submitted', NULL, '2026-08-03 14:36:49', '2026-08-03 14:36:51'),
(2443, 28, 1, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:02', '2026-08-03 14:37:27'),
(2444, 28, 2, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:03', '2026-08-03 14:37:27'),
(2445, 28, 4, 1, 12, 2, '', 'submitted', NULL, '2026-08-03 14:37:04', '2026-08-03 14:37:27'),
(2446, 28, 5, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:06', '2026-08-03 14:37:27'),
(2447, 28, 6, 1, 12, 4, '', 'submitted', NULL, '2026-08-03 14:37:07', '2026-08-03 14:37:27'),
(2448, 28, 7, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:08', '2026-08-03 14:37:27'),
(2449, 28, 9, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:09', '2026-08-03 14:37:27'),
(2450, 28, 10, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:10', '2026-08-03 14:37:27'),
(2451, 28, 11, 1, 12, 4, '', 'submitted', NULL, '2026-08-03 14:37:11', '2026-08-03 14:37:27'),
(2452, 28, 12, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:12', '2026-08-03 14:37:27'),
(2453, 28, 17, 1, 12, 2, '', 'submitted', NULL, '2026-08-03 14:37:13', '2026-08-03 14:37:27'),
(2454, 28, 21, 1, 12, 4, '', 'submitted', NULL, '2026-08-03 14:37:15', '2026-08-03 14:37:27'),
(2455, 28, 29, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:16', '2026-08-03 14:37:27'),
(2456, 28, 31, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:17', '2026-08-03 14:37:27'),
(2457, 28, 32, 1, 12, 2, '', 'submitted', NULL, '2026-08-03 14:37:18', '2026-08-03 14:37:27'),
(2458, 28, 33, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:19', '2026-08-03 14:37:27'),
(2459, 28, 34, 1, 12, 4, '', 'submitted', NULL, '2026-08-03 14:37:21', '2026-08-03 14:37:27'),
(2460, 28, 35, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:22', '2026-08-03 14:37:27'),
(2461, 28, 38, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:23', '2026-08-03 14:37:27'),
(2462, 28, 39, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:24', '2026-08-03 14:37:27'),
(2463, 28, 40, 1, 12, 3, '', 'submitted', NULL, '2026-08-03 14:37:25', '2026-08-03 14:37:27'),
(2464, 28, 1, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:37:46', '2026-08-03 14:38:21'),
(2465, 28, 2, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:37:55', '2026-08-03 14:38:21'),
(2466, 28, 4, 1, 13, 2, '', 'submitted', NULL, '2026-08-03 14:37:56', '2026-08-03 14:38:21'),
(2467, 28, 5, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:37:58', '2026-08-03 14:38:21'),
(2468, 28, 6, 1, 13, 4, '', 'submitted', NULL, '2026-08-03 14:37:59', '2026-08-03 14:38:21'),
(2469, 28, 7, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:00', '2026-08-03 14:38:21'),
(2470, 28, 9, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:01', '2026-08-03 14:38:21'),
(2471, 28, 10, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:02', '2026-08-03 14:38:21'),
(2472, 28, 11, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:04', '2026-08-03 14:38:21'),
(2473, 28, 12, 1, 13, 4, '', 'submitted', NULL, '2026-08-03 14:38:05', '2026-08-03 14:38:21'),
(2474, 28, 17, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:06', '2026-08-03 14:38:21'),
(2475, 28, 21, 1, 13, 4, '', 'submitted', NULL, '2026-08-03 14:38:07', '2026-08-03 14:38:21'),
(2476, 28, 29, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:08', '2026-08-03 14:38:21'),
(2477, 28, 31, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:10', '2026-08-03 14:38:21'),
(2478, 28, 32, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:11', '2026-08-03 14:38:21'),
(2479, 28, 33, 1, 13, 2, '', 'submitted', NULL, '2026-08-03 14:38:12', '2026-08-03 14:38:21'),
(2480, 28, 34, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:14', '2026-08-03 14:38:21'),
(2481, 28, 35, 1, 13, 2, '', 'submitted', NULL, '2026-08-03 14:38:15', '2026-08-03 14:38:21'),
(2482, 28, 38, 1, 13, 2, '', 'submitted', NULL, '2026-08-03 14:38:16', '2026-08-03 14:38:21'),
(2483, 28, 39, 1, 13, 2, '', 'submitted', NULL, '2026-08-03 14:38:18', '2026-08-03 14:38:21'),
(2484, 28, 40, 1, 13, 3, '', 'submitted', NULL, '2026-08-03 14:38:19', '2026-08-03 14:38:21'),
(2485, 28, 1, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:30', '2026-08-03 14:39:01'),
(2486, 28, 2, 1, 14, 2, '', 'submitted', NULL, '2026-08-03 14:38:32', '2026-08-03 14:39:01'),
(2487, 28, 4, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:33', '2026-08-03 14:39:01'),
(2488, 28, 5, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:34', '2026-08-03 14:39:01'),
(2489, 28, 6, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:35', '2026-08-03 14:39:01'),
(2490, 28, 7, 1, 14, 4, '', 'submitted', NULL, '2026-08-03 14:38:36', '2026-08-03 14:39:01'),
(2491, 28, 9, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:38', '2026-08-03 14:39:01'),
(2492, 28, 10, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:39', '2026-08-03 14:39:01'),
(2493, 28, 11, 1, 14, 2, '', 'submitted', NULL, '2026-08-03 14:38:40', '2026-08-03 14:39:01'),
(2494, 28, 12, 1, 14, 2, '', 'submitted', NULL, '2026-08-03 14:38:41', '2026-08-03 14:39:01'),
(2495, 28, 17, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:42', '2026-08-03 14:39:01'),
(2496, 28, 21, 1, 14, 4, '', 'submitted', NULL, '2026-08-03 14:38:43', '2026-08-03 14:39:01'),
(2498, 28, 29, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:45', '2026-08-03 14:39:01'),
(2499, 28, 31, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:49', '2026-08-03 14:39:01'),
(2500, 28, 32, 1, 14, 2, '', 'submitted', NULL, '2026-08-03 14:38:50', '2026-08-03 14:39:01'),
(2501, 28, 33, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:52', '2026-08-03 14:39:01'),
(2502, 28, 34, 1, 14, 4, '', 'submitted', NULL, '2026-08-03 14:38:53', '2026-08-03 14:39:01'),
(2503, 28, 35, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:54', '2026-08-03 14:39:01'),
(2504, 28, 38, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:56', '2026-08-03 14:39:01'),
(2505, 28, 39, 1, 14, 3, '', 'submitted', NULL, '2026-08-03 14:38:57', '2026-08-03 14:39:01'),
(2506, 28, 40, 1, 14, 2, '', 'submitted', NULL, '2026-08-03 14:38:58', '2026-08-03 14:39:01');

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
(93, 26, 1, 25, 15, 'submitted', '2026-08-02 13:20:43', 21),
(94, 26, 1, 25, 2, 'submitted', '2026-08-02 13:24:59', 21),
(95, 26, 1, 25, 12, 'submitted', '2026-08-02 13:25:48', 21),
(96, 26, 1, 25, 13, 'submitted', '2026-08-02 13:28:01', 21),
(97, 26, 1, 25, 14, 'submitted', '2026-08-02 13:28:38', 21),
(98, 27, 1, 26, 15, 'submitted', '2026-08-02 21:49:26', 21),
(99, 27, 1, 26, 2, 'submitted', '2026-08-02 22:06:03', 21),
(100, 27, 1, 26, 12, 'submitted', '2026-08-02 22:06:49', 21),
(101, 27, 1, 26, 13, 'submitted', '2026-08-02 22:07:31', 21),
(102, 27, 1, 26, 14, 'submitted', '2026-08-02 22:08:10', 21),
(103, 28, 1, 27, 15, 'submitted', '2026-08-03 22:36:13', 21),
(104, 28, 1, 27, 2, 'submitted', '2026-08-03 22:36:51', 21),
(105, 28, 1, 27, 12, 'submitted', '2026-08-03 22:37:27', 21),
(106, 28, 1, 27, 13, 'submitted', '2026-08-03 22:38:21', 21),
(107, 28, 1, 27, 14, 'submitted', '2026-08-03 22:39:01', 21);

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
  `role` enum('system_admin','school_head','sbm_coordinator','teacher','external_stakeholder') NOT NULL DEFAULT 'teacher',
  `status` enum('active','inactive','archived','suspended') NOT NULL DEFAULT 'inactive',
  `school_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `employee_id`, `department`, `last_login`, `created_at`, `email_verified`, `reset_token`, `token_expiry`, `email_sent_at`, `force_password_change`, `contact_number`, `profile_picture`) VALUES
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, NULL, 'English', '2026-08-08 09:14:25', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0, NULL, NULL),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, NULL, 'English', '2026-08-07 21:33:38', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0, NULL, NULL),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, NULL, 'English', '2026-08-03 22:37:43', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0, NULL, NULL),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, NULL, 'Filipino', '2026-08-03 22:38:28', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0, NULL, NULL),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, NULL, 'English', '2026-08-08 09:12:06', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0, NULL, NULL),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza Evangelio', 'school_head', 'active', 1, NULL, 'Guidance', '2026-08-08 10:01:26', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0, '09412568901', 'uploads/avatars/avatar_37_1780853466.jpg'),
(46, 'Charles', '$2y$10$9QWVYCP/gNj9kS9vZ72OpeK8BsICHhNjMndKyzi4ZBxQ00A3Mw1WS', 'mendozacharles11011@gmail.com', 'Charles Patrick Arias', 'sbm_coordinator', 'active', 1, NULL, 'Filipino', '2026-08-08 17:45:57', '2026-04-01 02:35:08', 0, NULL, NULL, '2026-04-01 10:35:53', 0, NULL, NULL),
(74, 'charlesarias', '$2y$10$Zamu/arxPs7ldX8oJ9e27u95NJ4XqgJvWrOb9EggmsAOjOtyMIS3S', 'ariascharles00@gmail.com', 'Charles Arias', 'system_admin', 'active', 1, NULL, 'Information Technology', '2026-08-08 08:12:05', '2026-06-10 16:00:00', 0, NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_cycle_evaluator_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_cycle_evaluator_summary` (
`evaluator_id` int(11)
,`cycle_id` int(11)
,`user_id` int(11)
,`school_id` int(11)
,`ce_is_active` tinyint(1)
,`deactivated_at` datetime
,`reactivated_at` datetime
,`custom_access_end` datetime
,`full_name` varchar(120)
,`email` varchar(120)
,`user_account_status` enum('active','inactive','archived','suspended')
,`submission_status` enum('draft','submitted')
,`submitted_at` datetime
,`response_count` int(11)
,`stakeholder_access_start` datetime
,`stakeholder_access_end` datetime
,`auto_deactivated_at` datetime
,`sy_label` varchar(20)
);

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

-- --------------------------------------------------------

--
-- Structure for view `v_cycle_evaluator_summary`
--
DROP TABLE IF EXISTS `v_cycle_evaluator_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cycle_evaluator_summary`  AS SELECT `ce`.`evaluator_id` AS `evaluator_id`, `ce`.`cycle_id` AS `cycle_id`, `ce`.`user_id` AS `user_id`, `ce`.`school_id` AS `school_id`, `ce`.`is_active` AS `ce_is_active`, `ce`.`deactivated_at` AS `deactivated_at`, `ce`.`reactivated_at` AS `reactivated_at`, `ce`.`custom_access_end` AS `custom_access_end`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`status` AS `user_account_status`, `ss`.`status` AS `submission_status`, `ss`.`submitted_at` AS `submitted_at`, `ss`.`response_count` AS `response_count`, `c`.`stakeholder_access_start` AS `stakeholder_access_start`, `c`.`stakeholder_access_end` AS `stakeholder_access_end`, `c`.`auto_deactivated_at` AS `auto_deactivated_at`, `sy`.`label` AS `sy_label` FROM ((((`cycle_evaluators` `ce` join `users` `u` on(`ce`.`user_id` = `u`.`user_id`)) join `sbm_cycles` `c` on(`ce`.`cycle_id` = `c`.`cycle_id`)) join `school_years` `sy` on(`c`.`sy_id` = `sy`.`sy_id`)) left join `stakeholder_submissions` `ss` on(`ss`.`stakeholder_id` = `ce`.`user_id` and `ss`.`cycle_id` = `ce`.`cycle_id`)) ;

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
-- Indexes for table `ai_suggestion_usage`
--
ALTER TABLE `ai_suggestion_usage`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  ADD PRIMARY KEY (`snap_id`),
  ADD UNIQUE KEY `unique_snap` (`cycle_id`,`dimension_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `sy_id` (`sy_id`),
  ADD KEY `dimension_id` (`dimension_id`);

--
-- Indexes for table `cycle_audit_log`
--
ALTER TABLE `cycle_audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `actor_id` (`actor_id`);

--
-- Indexes for table `cycle_evaluators`
--
ALTER TABLE `cycle_evaluators`
  ADD PRIMARY KEY (`evaluator_id`),
  ADD UNIQUE KEY `unique_cycle_user` (`cycle_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cycle_evaluator_status_log`
--
ALTER TABLE `cycle_evaluator_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_cycle` (`cycle_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `cycle_stage_gates`
--
ALTER TABLE `cycle_stage_gates`
  ADD PRIMARY KEY (`gate_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `checked_by` (`checked_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `uq_dept_school` (`school_id`,`name`);

--
-- Indexes for table `doc_import_usage`
--
ALTER TABLE `doc_import_usage`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `evidence_audit_log`
--
ALTER TABLE `evidence_audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `attachment_id` (`attachment_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `actor_id` (`actor_id`);

--
-- Indexes for table `form_versions`
--
ALTER TABLE `form_versions`
  ADD PRIMARY KEY (`version_id`),
  ADD KEY `created_by` (`created_by`);

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
  ADD KEY `improvement_plans_ibfk_5` (`created_by`),
  ADD KEY `idx_workflow_status` (`workflow_status`),
  ADD KEY `improvement_plans_ibfk_6` (`submitted_by`);

--
-- Indexes for table `indicator_evidence_requirements`
--
ALTER TABLE `indicator_evidence_requirements`
  ADD PRIMARY KEY (`req_id`),
  ADD UNIQUE KEY `indicator_id` (`indicator_id`);

--
-- Indexes for table `ip_field_usage`
--
ALTER TABLE `ip_field_usage`
  ADD PRIMARY KEY (`user_id`,`field_type`);

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
-- Indexes for table `response_attachments`
--
ALTER TABLE `response_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_cycle_indicator` (`cycle_id`,`indicator_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `idx_parent` (`parent_attachment_id`),
  ADD KEY `idx_current_version` (`is_current_version`,`cycle_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  ADD UNIQUE KEY `dimension_no` (`dimension_no`,`form_version_id`);

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
  ADD UNIQUE KEY `indicator_code` (`indicator_code`,`form_version_id`),
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
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

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
  ADD UNIQUE KEY `uniq_teacher_response` (`cycle_id`,`indicator_id`,`teacher_id`),
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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1594;

--
-- AUTO_INCREMENT for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  MODIFY `snap_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cycle_audit_log`
--
ALTER TABLE `cycle_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `cycle_evaluators`
--
ALTER TABLE `cycle_evaluators`
  MODIFY `evaluator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cycle_evaluator_status_log`
--
ALTER TABLE `cycle_evaluator_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cycle_stage_gates`
--
ALTER TABLE `cycle_stage_gates`
  MODIFY `gate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `evidence_audit_log`
--
ALTER TABLE `evidence_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `form_versions`
--
ALTER TABLE `form_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `indicator_evidence_requirements`
--
ALTER TABLE `indicator_evidence_requirements`
  MODIFY `req_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  MODIFY `analysis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `response_attachments`
--
ALTER TABLE `response_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1345;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=989;

--
-- AUTO_INCREMENT for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  MODIFY `phase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_indicator_assignments`
--
ALTER TABLE `teacher_indicator_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2507;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

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
-- Constraints for table `ai_suggestion_usage`
--
ALTER TABLE `ai_suggestion_usage`
  ADD CONSTRAINT `fk_ai_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  ADD CONSTRAINT `analytics_snapshots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_snapshots_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_snapshots_ibfk_3` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_snapshots_ibfk_4` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`);

--
-- Constraints for table `cycle_audit_log`
--
ALTER TABLE `cycle_audit_log`
  ADD CONSTRAINT `cal_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cal_ibfk_2` FOREIGN KEY (`actor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cycle_evaluators`
--
ALTER TABLE `cycle_evaluators`
  ADD CONSTRAINT `cycle_evaluators_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cycle_evaluators_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cycle_stage_gates`
--
ALTER TABLE `cycle_stage_gates`
  ADD CONSTRAINT `csg_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `csg_ibfk_2` FOREIGN KEY (`checked_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `doc_import_usage`
--
ALTER TABLE `doc_import_usage`
  ADD CONSTRAINT `fk_doc_import_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `improvement_plans_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `improvement_plans_ibfk_6` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `indicator_evidence_requirements`
--
ALTER TABLE `indicator_evidence_requirements`
  ADD CONSTRAINT `ier_ibfk_1` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE CASCADE;

--
-- Constraints for table `ip_field_usage`
--
ALTER TABLE `ip_field_usage`
  ADD CONSTRAINT `fk_ip_field_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
