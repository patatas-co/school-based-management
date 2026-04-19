-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2026 at 06:44 AM
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
(435, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 09:55:20'),
(436, 72, 'delete_user', 'users', 'Deleted user ID:71', '::1', '2026-04-09 09:55:30'),
(437, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 09:57:35'),
(438, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:19'),
(439, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:25'),
(440, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:31'),
(441, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:00:39'),
(442, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:01:13'),
(443, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:39:26'),
(444, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:39:54'),
(445, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:43:15'),
(446, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 10:43:43'),
(447, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:06:04'),
(448, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:12:57'),
(449, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:18:36'),
(450, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:19:24'),
(451, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:19:39'),
(452, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:22:02'),
(453, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:22:17'),
(454, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:23:34'),
(455, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:24:29'),
(456, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:24:35'),
(457, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 11:24:40'),
(458, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 13:35:37'),
(459, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-09 13:40:14'),
(460, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 13:46:20'),
(461, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:13:40'),
(462, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:14:05'),
(463, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:14:34'),
(464, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:17:22'),
(465, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:35:04'),
(466, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:37:39'),
(467, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-09 17:55:08'),
(468, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:00:57'),
(469, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:02:35'),
(470, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:02:42'),
(471, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:02:57'),
(472, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:03:12'),
(473, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:03:20'),
(474, 72, 'create_user', 'users', 'Created: Rol', '::1', '2026-04-10 03:05:14'),
(475, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-10 03:06:04'),
(476, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-10 03:06:24'),
(477, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-10 04:25:53'),
(478, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-10 14:50:09'),
(479, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:51:17'),
(480, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:52:15'),
(481, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:54:43'),
(482, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:56:05'),
(483, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:56:45'),
(484, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 02:58:38'),
(485, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:12:59'),
(486, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:17:35'),
(487, 72, 'delete_user', 'users', 'Deleted user ID:73', '::1', '2026-04-11 03:17:57'),
(488, 13, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:19:00'),
(489, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:29:26'),
(490, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:31:07'),
(491, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:34:42'),
(492, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:39:16'),
(493, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:41:17'),
(494, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-11 03:42:08'),
(495, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:30:55'),
(496, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:31:27'),
(497, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:32:08'),
(498, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:32:18'),
(499, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:32:29'),
(500, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:33:37'),
(501, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:35:16'),
(502, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-11 09:38:16'),
(503, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:38:25'),
(504, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:38:56'),
(505, 72, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 7: dozenjames54@gmail.com', '::1', '2026-04-11 09:39:55'),
(506, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-11 09:41:55'),
(507, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:42:21'),
(508, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:48:24'),
(509, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:48:36'),
(510, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:48:45'),
(511, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 10:00:13'),
(512, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 10:07:51'),
(513, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:08:06'),
(514, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:08:09'),
(515, 72, 'delete_user', 'users', 'Deleted user ID:74', '::1', '2026-04-11 10:08:17'),
(516, 72, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 7: dozenjames54@gmail.com', '::1', '2026-04-11 10:40:04'),
(517, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-11 10:40:44'),
(518, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 10:40:55'),
(519, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 10:51:37'),
(520, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 10:54:35'),
(521, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:38'),
(522, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:43'),
(523, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:47'),
(524, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:49'),
(525, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 10:54:52'),
(526, 72, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 7: 2026-04-11 19:01:00 to 2026-04-11 19:05:00', '::1', '2026-04-11 11:01:26'),
(527, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 11:02:30'),
(528, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 0 evaluators for cycle 7', '::1', '2026-04-11 11:02:38'),
(529, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 11:03:58'),
(530, 72, 'reactivate_evaluators', 'users', 'Reactivated 1 evaluators for cycle 7', '::1', '2026-04-11 11:04:09'),
(531, 72, 'delete_user', 'users', 'Deleted user ID:75', '::1', '2026-04-11 11:13:34'),
(532, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:14:32'),
(533, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:35:58'),
(534, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:36:34'),
(535, 72, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 7: dozenjames54@gmail.com', '::1', '2026-04-11 11:37:39'),
(536, 72, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 7: 2026-04-11 19:01:00 to 2026-04-11 19:05:00', '::1', '2026-04-11 11:37:46'),
(537, 72, 'reactivate_evaluators', 'users', 'Reactivated 1 evaluators for cycle 7', '::1', '2026-04-11 11:37:57'),
(538, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-11 11:39:09'),
(539, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 11:39:16'),
(540, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:02:26'),
(541, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:02:45'),
(542, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:14:13'),
(543, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 12:36:50'),
(544, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 14:09:46'),
(545, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 14:55:14'),
(546, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 15:29:23'),
(547, 72, 'deactivate_cycle_evaluators', 'users', 'Deactivated 1 evaluators for cycle 7', '::1', '2026-04-11 16:42:26'),
(548, 72, 'reactivate_evaluators', 'users', 'Reactivated 1 evaluators for cycle 7', '::1', '2026-04-11 16:43:06'),
(549, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 16:57:19'),
(550, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 16:57:41'),
(551, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 16:58:22'),
(552, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:00:58'),
(553, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:02:19'),
(554, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:06:07'),
(555, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:08:16'),
(556, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:34:30'),
(557, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:36:24'),
(558, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-11 17:36:31'),
(559, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:00:59'),
(560, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:23:07'),
(561, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:23:14'),
(562, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:24:35'),
(563, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:26:40'),
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
(577, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:57:25'),
(578, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 04:58:28'),
(579, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:18:42'),
(580, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 10', '::1', '2026-04-12 05:25:53'),
(581, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:40:39'),
(582, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:45:24'),
(583, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:48:02'),
(584, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:52:14'),
(585, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:52:34'),
(586, 12, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:53:58'),
(587, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:54:10'),
(588, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 05:55:37'),
(589, 37, 'start_assessment', 'self_assessment', 'Started SBM assessment cycle for the current school year.', '::1', '2026-04-12 05:55:53'),
(590, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:59:24'),
(591, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-12 05:59:39'),
(592, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:03:03'),
(593, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 06:03:21'),
(594, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:07:52'),
(595, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:09:16'),
(596, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:09:31'),
(597, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 06:10:22'),
(598, 37, 'configure_cycle_schedule', 'workflow', 'Set cycle schedule for SY 12', '::1', '2026-04-12 06:10:56'),
(599, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:14:14'),
(600, 72, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 9: 2026-04-12 16:00:00 to 2026-04-21 06:00:00', '::1', '2026-04-12 06:15:38'),
(601, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:15:49'),
(602, NULL, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:16:01'),
(603, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:16:10'),
(604, 72, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 9: 2026-04-12 16:00:00 to 2026-04-21 06:00:00', '::1', '2026-04-12 06:16:26'),
(605, 72, 'delete_user', 'users', 'Deleted user ID:76', '::1', '2026-04-12 06:16:42'),
(606, 72, 'create_temp_evaluator', 'users', 'Created temp evaluator for cycle 9: dozenjames54@gmail.com', '::1', '2026-04-12 06:17:36'),
(607, NULL, 'password_set', 'auth', 'User set password via invite link', '::1', '2026-04-12 06:19:12'),
(608, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:20:51'),
(609, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:25:01'),
(610, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-12 06:25:17'),
(611, 72, 'set_cycle_dates', 'sbm_cycles', 'Updated access window for cycle 9: 2026-04-12 16:00:00 to 2026-04-21 06:00:00', '::1', '2026-04-12 06:55:08'),
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
(635, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-14 10:48:48'),
(636, 72, 'update_user', 'users', 'Updated user ID:15', '::1', '2026-04-14 10:58:20'),
(637, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-14 10:58:44'),
(638, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:16:46'),
(639, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:20:37'),
(640, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:20:53'),
(641, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:21:36'),
(642, 72, 'update_user', 'users', 'Updated user ID:15', '::1', '2026-04-14 11:21:44'),
(643, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:21:55'),
(644, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:23:10'),
(645, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:23:35'),
(646, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:24:25'),
(647, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:25:00'),
(648, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:25:33'),
(649, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:29:06'),
(650, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:29:16'),
(651, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:29:26');
INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
(652, 37, 'sh_update_school_profile', 'school_profile', 'School Head updated school profile for school_id: 1', '::1', '2026-04-14 11:30:22'),
(653, 2, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:33:33'),
(654, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-14 11:33:55'),
(655, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:37:00'),
(656, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:50:21'),
(657, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:50:29'),
(658, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-15 10:50:46'),
(659, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-15 13:49:48'),
(660, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:49:34'),
(661, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:49:46'),
(662, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:49:59'),
(663, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-16 11:50:10'),
(664, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-16 12:43:11'),
(665, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-16 13:02:19'),
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
(678, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-16 17:37:14'),
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
(708, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:05:17'),
(709, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:07:46'),
(710, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:08:30'),
(711, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:08:50'),
(712, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:09:33'),
(713, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:27:26'),
(714, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:41:51'),
(715, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-18 10:51:02'),
(716, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-18 11:08:52'),
(717, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:28:24'),
(718, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-18 16:28:33'),
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
(737, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-19 02:59:22'),
(738, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:02:08'),
(739, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:02:17'),
(740, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:05:23'),
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
(752, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:26:11'),
(753, 72, 'delete_user', 'users', 'Deleted user ID:77', '::1', '2026-04-19 03:26:28'),
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
(774, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-19 03:55:01'),
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
(794, 72, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:35:41'),
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
(812, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-19 04:43:02');

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
(5, 10, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-04-19 11:51:18'),
(6, 11, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-04-19 12:34:34'),
(7, 12, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-04-19 12:42:55');

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

--
-- Dumping data for table `cycle_evaluator_status_log`
--

INSERT INTO `cycle_evaluator_status_log` (`log_id`, `cycle_id`, `user_id`, `school_id`, `action`, `triggered_by`, `actor_id`, `notes`, `created_at`) VALUES
(1, 7, 75, 1, 'reactivated', 'admin', 72, NULL, '2026-04-11 11:04:09'),
(2, 7, 75, 1, 'deactivated', 'cron', NULL, NULL, '2026-04-11 11:09:00'),
(3, 7, 76, 1, 'reactivated', 'admin', 72, NULL, '2026-04-11 11:37:57'),
(4, 7, 76, 1, 'reactivated', 'admin', 72, NULL, '2026-04-11 16:43:06');

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
(29, 46, 'account_creation', 'mendozacharles11011@gmail.com', 'sent', NULL, '2026-04-01 02:35:53'),
(67, 72, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-09 09:53:42');

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

--
-- Dumping data for table `evidence_audit_log`
--

INSERT INTO `evidence_audit_log` (`audit_id`, `attachment_id`, `cycle_id`, `indicator_id`, `school_id`, `actor_id`, `actor_role`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 5, 10, 7, 1, 15, 'teacher', 'upload', 'v1, category: other', '::1', '2026-04-19 03:12:22');

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

--
-- Dumping data for table `ml_predictions`
--

INSERT INTO `ml_predictions` (`pred_id`, `school_id`, `cycle_id`, `dimension_id`, `indicator_id`, `prediction_type`, `predicted_value`, `risk_level`, `recommendation`, `confidence_score`, `created_at`) VALUES
(3, 1, 10, NULL, NULL, 'risk_flag', 69.04, 'medium', 'Dimension 4 (Accountability and Continuous Improvement) is at 69.04% (Maturing level). Gap from average: 6.5%.', 0.750, '2026-04-19 03:48:13');

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
(25, 10, 'SCHOOL IMPROVEMENT PLAN RECOMMENDATIONS\nSchool: Dasmariñas Integrated High School | SY: 2025-2026\nOverall SBM Score: 75.78% | Maturity Level: Advanced\n────────────────────────────────────────────────────────────\n\n📊 ASSESSMENT OVERVIEW\nTotal Indicators Rated: 42\n  ▪ Not yet Manifested (1): 0 indicator(s) — Requires immediate action\n  ▪ Rarely Manifested (2):  7 indicator(s) — Needs focused intervention\n  ▪ Frequently Manifested (3): 29 indicator(s) — Continue and strengthen\n  ▪ Always manifested (4):  6 indicator(s) — Sustain and document\n\n📝 STAKEHOLDER REMARKS SUMMARY\nNo remarks were submitted.\n\n🟡 PRIORITY 2 — RARELY MANIFESTED (Focused Intervention Needed)\nThese 7 indicator(s) show early signs but need structured support:\n\n  📌 Learning Environment:\n     [2.5] The school provides access to learning experiences for the disadvantaged, OSYs, and adult learners.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Leadership:\n     [3.3] The school has a functional Supreme Student Government/Supreme Pupil Government.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Governance and Accountability:\n     [4.3] The school has a functional Parent-Teacher Association (PTA).\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [4.6] The school maintains an average rating of satisfactory from its internal and external stakeholders.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Human Resources and Team Development:\n     [5.4] The school facilitates the promotion and continuous professional development of its personnel.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Curriculum and Teaching:\n     [1.5] Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [1.4] Teachers prepare contextualized learning materials responsive to the needs of learners.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n🔵 PRIORITY 3 — FREQUENTLY MANIFESTED (Continue & Strengthen)\nThese 29 indicator(s) show good progress and should be maintained:\n\n  📌 Curriculum and Teaching:\n     [1.2] Grade 6, 10, and 12 learners achieve the proficiency level in all 21st-century skills and core learning areas in the National Achievement Test (NAT).\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.3] School-based ALS learners attain certification as elementary and junior high school completers.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.7] The school conducts test item analysis to inform its teaching and learning process.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.6] Teachers integrate topics promoting peace and DepEd core values.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Learning Environment:\n     [2.1] The school has zero bullying incidence.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.2] The school has zero child abuse incidence.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.3] The school has reduced its drop-out incidence.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.6] The school has a functional school-based ALS program.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.7] The school has a functional child-protection committee.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.9] The school has a functional support mechanism for mental wellness.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.10] The school has special education- and PWD-friendly facilities.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.4] The school conducts culture-sensitive activities.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Leadership:\n     [3.1] The school develops a strategic plan.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [3.2] The school has a functional school-community planning team.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Governance and Accountability:\n     [4.1] The school\'s strategic plan is operationalized through an implementation plan.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [4.2] The school has a functional School Governance Council (SGC).\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [4.4] The school collaborates with stakeholders and other schools in strengthening partnerships.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [4.5] The school monitors and evaluates its programs, projects, and activities.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Human Resources and Team Development:\n     [5.1] School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [5.2] The school achieves an average rating of very satisfactory in the office performance commitment and review.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [5.5] The school recognizes and rewards milestone achievements of its personnel.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [5.6] The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Finance and Resource Management and Mobilization:\n     [6.1] The school inspects its infrastructure and facilities.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.2] The school initiates improvement of its infrastructure and facilities.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.3] The school has a functional library.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.4] The school has functional water, electricity, and internet facilities.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.5] The school has a functional computer laboratory/classroom.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.6] The school achieves a 75–100% utilization rate of its Maintenance and Other Operating Expenses (MOOE).\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.7] The school liquidates 100% of its utilized MOOE.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n🟢 SUSTAINED PRACTICES — ALWAYS MANIFESTED\nThese 6 indicator(s) are consistently implemented — keep it up:\n\n  📌 Curriculum and Teaching:\n     [1.1] Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n     [1.8] The school engages local industries to strengthen its TLE-TVL course offerings.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n  📌 Learning Environment:\n     [2.8] The school has a functional DRRM plan.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n  📌 Leadership:\n     [3.4] The school innovates in its provision of frontline services to stakeholders.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n  📌 Human Resources and Team Development:\n     [5.3] The school conducts needs-based Learning Action Cells and Learning & Development activities.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n     [5.7] Teacher workload is distributed fairly and equitably.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n📐 DIMENSION-LEVEL PRIORITY ACTIONS\n\n  Accountability and Continuous Improvement (69.04% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: 6.5%.\n\n  Learning Environment (75% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: 0.5%.\n\n  Leadership and Governance (75% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: 0.5%.\n\n  Finance and Resource Management (75% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: 0.5%.\n\n────────────────────────────────────────────────────────────\nNOTE: These recommendations are generated based on the SBM self-assessment data\nsubmitted by Dasmariñas Integrated High School for SY 2025-2026. All action plans should be\nintegrated into the School Improvement Plan (SIP) and monitored quarterly by the SDO.\nFor dimensions rated \'Beginning\' or \'Developing\', SDO technical assistance is strongly advised.', 'rule_based_fallback', '[]', 0, '{\"positive\":0,\"negative\":0,\"neutral\":0}', '2026-04-19 03:48:13'),
(26, 11, 'Given Dasmariñas Integrated High School\'s overall SBM score of 78.76% and Advanced maturity level, it\'s clear that the school has a solid foundation to build upon. **Improving drop-out incidence** is an area where the school can make significant strides, particularly for indicator 2.3, which is currently rated as Emerging.\n\n* Consider conducting a root cause analysis to identify factors contributing to drop-outs, and develop targeted interventions to address these issues.\n* Engage with local stakeholders and community leaders to provide support and resources for at-risk students, as outlined in DepEd Order No. 007, s. 2024.\n\n**Enhancing access to learning experiences** for disadvantaged groups, OSYs, and adult learners is another key area for growth, as indicated by the Emerging rating for indicator 2.5. \n* Develop partnerships with local organizations to provide additional learning opportunities and resources for these groups.\n* Ensure that the school\'s programs and services are inclusive and responsive to the needs of all learners.\n\n**Strengthening school-community planning** is essential for effective governance and community engagement, particularly given the Emerging rating for indicator 3.2. \n* Establish a functional school-community planning team that meets regularly to discuss school priorities and initiatives.\n* Foster collaborative relationships with local stakeholders, including parents, community leaders, and local businesses.\n\n**Monitoring and evaluation** of school programs and activities is critical for continuous improvement, as highlighted by the Emerging rating for indicator 4.5. \n* Develop a comprehensive monitoring and evaluation framework to track progress and identify areas for improvement.\n* Use data and feedback from stakeholders to inform decision-making and drive school improvement initiatives.\n\n**Recognizing and rewarding personnel achievements** is important for motivating and retaining high-performing staff, given the Emerging rating for indicator 5.5. \n* Establish a recognition and rewards system that acknowledges and celebrates the achievements of school personnel.\n* Consider providing opportunities for professional development and growth to support the ongoing improvement of teaching and learning.\n\nThe single biggest factor in sustaining SBM improvement is consistent focus on a few priorities — everything else becomes easier from there. Would you like more specific guidance on any of these areas?', 'groq', '[]', 0, '[]', '2026-04-19 04:33:21'),
(27, 12, 'Given your school\'s overall SBM score of 73.62% and maturity level of Maturing, it\'s clear that Dasmariñas Integrated High School is on the right track. **Strengthening industry partnerships** is an area where you can make significant gains, particularly for indicator 1.8, which is currently rated as Emerging. \n\n* Consider reaching out to local industries to explore potential partnerships that can enhance your TLE-TVL course offerings, aligning with DepEd Order No. 007, s. 2024.\n* Develop a plan to engage these industries in curriculum development and student training, ensuring that your programs are relevant and responsive to industry needs.\n\n**Enhancing teacher capacity** is another critical area, especially for indicators 1.4, 1.5, and 1.6, which are all rated as Emerging. \n* Provide training and support for teachers to develop contextualized learning materials that address the diverse needs of your learners.\n* Encourage teachers to integrate topics promoting peace and DepEd core values into their lessons, fostering a more holistic learning environment.\n\n**Fostering a culture of inclusivity** is also important, as indicated by the Emerging rating for indicator 2.4. \n* Organize culture-sensitive activities that celebrate the diversity of your school community, promoting a sense of belonging and respect among students, teachers, and staff.\n* Ensure that these activities are planned and implemented in collaboration with various stakeholders, including students, parents, and community members.\n\n**Building effective partnerships** is vital for the growth and development of your school, as seen in the Emerging ratings for indicators 4.4 and 4.6. \n* Develop a strategic plan to collaborate with stakeholders and other schools, focusing on areas such as curriculum development, teacher training, and resource sharing.\n* Establish a system to monitor and evaluate the effectiveness of these partnerships, using feedback from internal and external stakeholders to inform your decisions.\n\n**Supporting student leadership** is another area where you can make a positive impact, given the Emerging rating for indicator 3.3. \n* Reactivate or strengthen your Supreme Student Government/Supreme Pupil Government, providing opportunities for students to develop their leadership skills and participate in decision-making processes.\n* Ensure that student leaders receive training and support to fulfill their roles effectively, aligning with the principles outlined in DepEd Order No. 007, s. 2024.\n\n**Improving infrastructure and facilities** is essential for creating a conducive learning environment, as indicated by the Emerging rating for indicator 6.4. \n* Conduct a thorough assessment of your school\'s water, electricity, and internet facilities, identifying areas that require improvement or upgrade.\n* Develop a plan to address these infrastructure needs, exploring potential partnerships with local government units, private organizations, or community groups.\n\n**Facilitating teacher development** is crucial for enhancing the quality of education in your school, as seen in the Emerging rating for indicator 5.4. \n* Provide opportunities for teachers to engage in continuous professional development, focusing on areas such as curriculum design, instructional strategies, and assessment techniques.\n* Encourage teachers to share their expertise and experiences with colleagues, fostering a culture of collaboration and peer support.\n\nWould you like more specific guidance on any of these areas to further support the growth and development of Dasmariñas Integrated High School?', 'groq', '[]', 0, '[]', '2026-04-19 04:42:39');

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
(25, 1, 10, '[{\"dimension_no\":4,\"dimension_name\":\"Accountability and Continuous Improvement\",\"score\":69.04,\"gap_from_avg\":6.5,\"maturity\":\"Maturing\",\"priority\":\"medium\"},{\"dimension_no\":2,\"dimension_name\":\"Learning Environment\",\"score\":75,\"gap_from_avg\":0.5,\"maturity\":\"Maturing\",\"priority\":\"low\"},{\"dimension_no\":3,\"dimension_name\":\"Leadership and Governance\",\"score\":75,\"gap_from_avg\":0.5,\"maturity\":\"Maturing\",\"priority\":\"low\"},{\"dimension_no\":6,\"dimension_name\":\"Finance and Resource Management\",\"score\":75,\"gap_from_avg\":0.5,\"maturity\":\"Maturing\",\"priority\":\"low\"},{\"dimension_no\":5,\"dimension_name\":\"Human Resource Development\",\"score\":78.57,\"gap_from_avg\":-3.1,\"maturity\":\"Advanced\",\"priority\":\"low\"},{\"dimension_no\":1,\"dimension_name\":\"Curriculum and Teaching\",\"score\":80.34,\"gap_from_avg\":-4.8,\"maturity\":\"Advanced\",\"priority\":\"low\"}]', '[]', 75.78, 'Advanced', '2026-04-19 03:48:13'),
(26, 1, 11, '[{\"dimension_name\":\"Leadership and Governance\",\"dimension_no\":3,\"gap_from_avg\":10.01,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"medium\",\"score\":68.75,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1,\"weighted_gap\":10.01},{\"dimension_name\":\"Learning Environment\",\"dimension_no\":2,\"gap_from_avg\":6.26,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"medium\",\"score\":72.5,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1.2,\"weighted_gap\":7.51},{\"dimension_name\":\"Human Resource Development\",\"dimension_no\":5,\"gap_from_avg\":7.33,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"medium\",\"score\":71.43,\"weak_count\":0,\"weak_ratio\":0,\"weight\":0.9,\"weighted_gap\":6.59},{\"dimension_name\":\"Curriculum and Teaching\",\"dimension_no\":1,\"gap_from_avg\":-0.93,\"maturity\":\"Advanced\",\"maturity_confidence\":1,\"priority\":\"medium\",\"score\":79.69,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1.2,\"weighted_gap\":-1.12},{\"dimension_name\":\"Accountability and Continuous Improvement\",\"dimension_no\":4,\"gap_from_avg\":-7.07,\"maturity\":\"Advanced\",\"maturity_confidence\":1,\"priority\":\"low\",\"score\":85.83,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1,\"weighted_gap\":-7.07},{\"dimension_name\":\"Finance and Resource Management\",\"dimension_no\":6,\"gap_from_avg\":-17.67,\"maturity\":\"Advanced\",\"maturity_confidence\":1,\"priority\":\"low\",\"score\":96.43,\"weak_count\":0,\"weak_ratio\":0,\"weight\":0.9,\"weighted_gap\":-15.91}]', '[]', 79.23, 'Advanced', '2026-04-19 04:33:21'),
(27, 1, 12, '[{\"dimension_name\":\"Curriculum and Teaching\",\"dimension_no\":1,\"gap_from_avg\":4.24,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"medium\",\"score\":69.38,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1.2,\"weighted_gap\":5.09},{\"dimension_name\":\"Leadership and Governance\",\"dimension_no\":3,\"gap_from_avg\":4.87,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"low\",\"score\":68.75,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1,\"weighted_gap\":4.87},{\"dimension_name\":\"Accountability and Continuous Improvement\",\"dimension_no\":4,\"gap_from_avg\":3.62,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"low\",\"score\":70,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1,\"weighted_gap\":3.62},{\"dimension_name\":\"Human Resource Development\",\"dimension_no\":5,\"gap_from_avg\":-1.38,\"maturity\":\"Maturing\",\"maturity_confidence\":1,\"priority\":\"low\",\"score\":75,\"weak_count\":0,\"weak_ratio\":0,\"weight\":0.9,\"weighted_gap\":-1.24},{\"dimension_name\":\"Learning Environment\",\"dimension_no\":2,\"gap_from_avg\":-3.88,\"maturity\":\"Advanced\",\"maturity_confidence\":1,\"priority\":\"medium\",\"score\":77.5,\"weak_count\":0,\"weak_ratio\":0,\"weight\":1.2,\"weighted_gap\":-4.66},{\"dimension_name\":\"Finance and Resource Management\",\"dimension_no\":6,\"gap_from_avg\":-8.52,\"maturity\":\"Advanced\",\"maturity_confidence\":1,\"priority\":\"low\",\"score\":82.14,\"weak_count\":0,\"weak_ratio\":0,\"weight\":0.9,\"weighted_gap\":-7.67}]', '[]', 74.40, 'Maturing', '2026-04-19 04:42:39');

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
(36, 46, '25de0e85006db5c333e8d45d3733be2eff8b27e9fa2207f55e070ffac278a993', 'setup', '2026-04-03 10:35:08', '2026-04-01 10:40:39', '2026-04-01 02:35:08'),
(74, 72, '39f0807dde06988876bc2e355da85edc901ff689d4a30fb6aa115cf3b853b691', 'setup', '2026-04-11 17:53:37', '2026-04-09 17:55:08', '2026-04-09 09:53:37');

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

--
-- Dumping data for table `response_attachments`
--

INSERT INTO `response_attachments` (`attachment_id`, `version`, `parent_attachment_id`, `cycle_id`, `indicator_id`, `school_id`, `uploaded_by`, `uploader_role`, `category`, `is_current_version`, `original_name`, `stored_name`, `file_size`, `mime_type`, `uploaded_at`, `deleted_at`, `deleted_by`, `replace_reason`) VALUES
(5, 1, NULL, 10, 7, 1, 15, 'teacher', 'other', 1, 'SAMPLE MOVs.pdf', '45c1c9fd1649deb768a5048cab8394c6.pdf', 469513, 'application/pdf', '2026-04-19 11:12:22', NULL, NULL, NULL);

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
  `maturity_level` enum('Beginning','Developing','Maturing','Advanced') DEFAULT NULL,
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
(10, 14, 1, 'finalized', 75.78, 'Advanced', '2026-04-19 11:09:04', '2026-04-19 11:48:10', '2026-04-19 11:49:14', 37, '', 0, NULL, NULL, '2026-04-19 11:51:18', NULL, NULL, NULL, '2026-04-19 03:09:04', NULL, NULL, NULL, NULL),
(11, 15, 1, 'finalized', 79.23, 'Advanced', '2026-04-19 12:04:48', '2026-04-19 12:33:19', '2026-04-19 12:34:20', 37, '', 0, NULL, NULL, '2026-04-19 12:34:34', NULL, NULL, NULL, '2026-04-19 04:04:48', NULL, NULL, NULL, NULL),
(12, 16, 1, 'finalized', 74.40, 'Maturing', '2026-04-19 12:36:50', '2026-04-19 12:42:37', '2026-04-19 12:42:45', 37, '', 0, NULL, NULL, '2026-04-19 12:42:55', NULL, NULL, NULL, '2026-04-19 04:36:50', NULL, NULL, NULL, NULL);

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
(3, 3, 'Leadership', '#7C3AED', 'star', 4),
(4, 4, 'Governance and Accountability', '#D97706', 'check-circle', 6),
(5, 5, 'Human Resources and Team Development', '#DC2626', 'users', 7),
(6, 6, 'Finance and Resource Management and Mobilization', '#0D9488', 'dollar-sign', 7);

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
(236, 10, 1, 1, 25.71, 32.00, 80.34, '2026-04-19 03:48:10'),
(237, 10, 1, 2, 27.00, 36.00, 75.00, '2026-04-19 03:48:10'),
(238, 10, 1, 3, 12.00, 16.00, 75.00, '2026-04-19 03:48:10'),
(239, 10, 1, 4, 16.57, 24.00, 69.04, '2026-04-19 03:48:10'),
(240, 10, 1, 5, 22.00, 28.00, 78.57, '2026-04-19 03:48:10'),
(241, 10, 1, 6, 21.00, 28.00, 75.00, '2026-04-19 03:48:10'),
(291, 11, 1, 1, 25.50, 32.00, 79.69, '2026-04-19 04:33:19'),
(292, 11, 1, 2, 29.00, 40.00, 72.50, '2026-04-19 04:33:19'),
(293, 11, 1, 3, 11.00, 16.00, 68.75, '2026-04-19 04:33:19'),
(294, 11, 1, 4, 20.60, 24.00, 85.83, '2026-04-19 04:33:19'),
(295, 11, 1, 5, 20.00, 28.00, 71.43, '2026-04-19 04:33:19'),
(296, 11, 1, 6, 27.00, 28.00, 96.43, '2026-04-19 04:33:19'),
(346, 12, 1, 1, 22.20, 32.00, 69.38, '2026-04-19 04:42:37'),
(347, 12, 1, 2, 31.00, 40.00, 77.50, '2026-04-19 04:42:37'),
(348, 12, 1, 3, 11.00, 16.00, 68.75, '2026-04-19 04:42:37'),
(349, 12, 1, 4, 16.80, 24.00, 70.00, '2026-04-19 04:42:37'),
(350, 12, 1, 5, 21.00, 28.00, 75.00, '2026-04-19 04:42:37'),
(351, 12, 1, 6, 23.00, 28.00, 82.14, '2026-04-19 04:42:37');

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
(1, 1, '1.1', 'Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.', 'MPS/proficiency data, class records, early language and literacy assessment results', 1, 1),
(2, 1, '1.2', 'Grade 6, 10, and 12 learners achieve the proficiency level in all 21st-century skills and core learning areas in the National Achievement Test (NAT).', 'NAT results, MPS data, class records', 2, 1),
(3, 1, '1.3', 'School-based ALS learners attain certification as elementary and junior high school completers.', 'ALS completion certificates, enrollment and completion records', 3, 1),
(4, 1, '1.4', 'Teachers prepare contextualized learning materials responsive to the needs of learners.', 'Developed contextualized LMs, LRMDS uploads, utilization records', 4, 1),
(5, 1, '1.5', 'Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.', 'Remediation program designs, attendance records, monitoring reports', 5, 1),
(6, 1, '1.6', 'Teachers integrate topics promoting peace and DepEd core values.', 'Lesson plans, classroom observations, LAC session minutes', 6, 1),
(7, 1, '1.7', 'The school conducts test item analysis to inform its teaching and learning process.', 'Item analysis reports, action plans based on findings, LAC minutes', 7, 1),
(8, 1, '1.8', 'The school engages local industries to strengthen its TLE-TVL course offerings.', 'MOA with industry partners, NC/COC certificates, industry immersion records', 8, 1),
(9, 2, '2.1', 'The school has zero bullying incidence.', 'Anti-bullying policy, incident reports, monitoring logs', 9, 1),
(10, 2, '2.2', 'The school has zero child abuse incidence.', 'CPC records, incident reports, referral documents', 10, 1),
(11, 2, '2.3', 'The school has reduced its drop-out incidence.', 'Enrollment/completion data, BEIS reports, intervention records', 11, 1),
(12, 2, '2.4', 'The school conducts culture-sensitive activities.', 'Activity programs, photo documentation, feedback forms', 12, 1),
(13, 2, '2.5', 'The school provides access to learning experiences for the disadvantaged, OSYs, and adult learners.', 'OSY mapping, ALS enrollment records, inclusion program documents', 13, 1),
(14, 2, '2.6', 'The school has a functional school-based ALS program.', 'ALS program design, learner enrollment, completion reports', 14, 1),
(15, 2, '2.7', 'The school has a functional child-protection committee.', 'CPC composition order, meeting minutes, activity reports', 15, 1),
(16, 2, '2.8', 'The school has a functional DRRM plan.', 'DRRM plan, drill documentation, hazard maps', 16, 1),
(17, 2, '2.9', 'The school has a functional support mechanism for mental wellness.', 'Wellness program design, referral records, accomplishment reports', 17, 1),
(18, 2, '2.10', 'The school has special education- and PWD-friendly facilities.', 'Accessibility audit, ramp/facility photos, SPED program records', 18, 1),
(19, 3, '3.1', 'The school develops a strategic plan.', 'SIP/strategic plan document, stakeholder attendance, accomplishment reports', 19, 1),
(20, 3, '3.2', 'The school has a functional school-community planning team.', 'Planning team composition, meeting minutes, activity reports', 20, 1),
(21, 3, '3.3', 'The school has a functional Supreme Student Government/Supreme Pupil Government.', 'SSG/SPG constitution, election records, program accomplishments', 21, 1),
(22, 3, '3.4', 'The school innovates in its provision of frontline services to stakeholders.', 'Innovation documentation, feedback/evaluation, impact data', 22, 1),
(23, 4, '4.1', 'The school\'s strategic plan is operationalized through an implementation plan.', 'Implementation plan, accomplishment reports, M&E records', 23, 1),
(24, 4, '4.2', 'The school has a functional School Governance Council (SGC).', 'SGC composition order, meeting minutes, resolutions', 24, 1),
(25, 4, '4.3', 'The school has a functional Parent-Teacher Association (PTA).', 'PTA election records, meeting minutes, financial reports', 25, 1),
(26, 4, '4.4', 'The school collaborates with stakeholders and other schools in strengthening partnerships.', 'MOA/MOU documents, partnership activity reports, resource contributions', 26, 1),
(27, 4, '4.5', 'The school monitors and evaluates its programs, projects, and activities.', 'M&E plan, monitoring reports, action plans based on findings', 27, 1),
(28, 4, '4.6', 'The school maintains an average rating of satisfactory from its internal and external stakeholders.', 'Stakeholder satisfaction survey results, tabulated data, action plans', 28, 1),
(29, 5, '5.1', 'School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.', 'Signed IPCR forms, summary rating sheets, submission records', 29, 1),
(30, 5, '5.2', 'The school achieves an average rating of very satisfactory in the office performance commitment and review.', 'OPCR rating sheets, division evaluation results', 30, 1),
(31, 5, '5.3', 'The school conducts needs-based Learning Action Cells and Learning & Development activities.', 'LAC session plans, attendance, minutes, action plans, L&D records', 31, 1),
(32, 5, '5.4', 'The school facilitates the promotion and continuous professional development of its personnel.', 'Training certificates, individual development plans, PDO records', 32, 1),
(33, 5, '5.5', 'The school recognizes and rewards milestone achievements of its personnel.', 'Recognition program design, awarding documentation, photos', 33, 1),
(34, 5, '5.6', 'The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.', 'Payroll records, DTR, allowance vouchers, personnel feedback', 34, 1),
(35, 5, '5.7', 'Teacher workload is distributed fairly and equitably.', 'Teaching load summary, class schedule, assignment orders', 35, 1),
(36, 6, '6.1', 'The school inspects its infrastructure and facilities.', 'Facilities inspection report, checklist, photos', 36, 1),
(37, 6, '6.2', 'The school initiates improvement of its infrastructure and facilities.', 'Maintenance/improvement plan, work orders, accomplishment reports, photos', 37, 1),
(38, 6, '6.3', 'The school has a functional library.', 'Library inventory, acquisition records, utilization logs', 38, 1),
(39, 6, '6.4', 'The school has functional water, electricity, and internet facilities.', 'Utility bills, repair records, functionality assessment', 39, 1),
(40, 6, '6.5', 'The school has a functional computer laboratory/classroom.', 'Lab inventory, equipment condition report, utilization records', 40, 1),
(41, 6, '6.6', 'The school achieves a 75–100% utilization rate of its Maintenance and Other Operating Expenses (MOOE).', 'MOOE liquidation reports, utilization matrix, COB vs. actual', 41, 1),
(42, 6, '6.7', 'The school liquidates 100% of its utilized MOOE.', 'Liquidation reports, submission acknowledgments, COA records', 42, 1);

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
(153, 10, 1, 1, 4, '', 37, '2026-04-19 03:47:13'),
(154, 10, 2, 1, 3, '', 37, '2026-04-19 03:47:15'),
(155, 10, 3, 1, 3, '', 37, '2026-04-19 03:47:16'),
(156, 10, 7, 1, 3, '', 37, '2026-04-19 03:47:21'),
(157, 10, 8, 1, 4, '', 37, '2026-04-19 03:47:22'),
(158, 10, 9, 1, 3, '', 37, '2026-04-19 03:47:23'),
(159, 10, 10, 1, 3, '', 37, '2026-04-19 03:47:24'),
(160, 10, 11, 1, 3, '', 37, '2026-04-19 03:47:26'),
(161, 10, 13, 1, 2, '', 37, '2026-04-19 03:47:29'),
(162, 10, 14, 1, 3, '', 37, '2026-04-19 03:47:30'),
(163, 10, 15, 1, 3, '', 37, '2026-04-19 03:47:31'),
(164, 10, 16, 1, 4, '', 37, '2026-04-19 03:47:32'),
(165, 10, 17, 1, 3, '', 37, '2026-04-19 03:47:33'),
(166, 10, 18, 1, 3, '', 37, '2026-04-19 03:47:35'),
(167, 10, 19, 1, 3, '', 37, '2026-04-19 03:47:36'),
(168, 10, 20, 1, 3, '', 37, '2026-04-19 03:47:37'),
(169, 10, 21, 1, 2, '', 37, '2026-04-19 03:47:39'),
(170, 10, 22, 1, 4, '', 37, '2026-04-19 03:47:40'),
(171, 10, 23, 1, 3, '', 37, '2026-04-19 03:47:41'),
(172, 10, 24, 1, 3, '', 37, '2026-04-19 03:47:44'),
(173, 10, 25, 1, 2, '', 37, '2026-04-19 03:47:45'),
(174, 10, 26, 1, 3, '', 37, '2026-04-19 03:47:46'),
(175, 10, 27, 1, 3, '', 37, '2026-04-19 03:47:48'),
(176, 10, 29, 1, 3, '', 37, '2026-04-19 03:47:50'),
(177, 10, 30, 1, 3, '', 37, '2026-04-19 03:47:51'),
(178, 10, 31, 1, 4, '', 37, '2026-04-19 03:47:53'),
(179, 10, 32, 1, 2, '', 37, '2026-04-19 03:47:54'),
(180, 10, 33, 1, 3, '', 37, '2026-04-19 03:47:55'),
(181, 10, 34, 1, 3, '', 37, '2026-04-19 03:47:57'),
(182, 10, 35, 1, 4, '', 37, '2026-04-19 03:47:59'),
(183, 10, 36, 1, 3, '', 37, '2026-04-19 03:48:00'),
(184, 10, 37, 1, 3, '', 37, '2026-04-19 03:48:01'),
(185, 10, 38, 1, 3, '', 37, '2026-04-19 03:48:02'),
(186, 10, 39, 1, 3, '', 37, '2026-04-19 03:48:04'),
(187, 10, 40, 1, 3, '', 37, '2026-04-19 03:48:05'),
(188, 10, 41, 1, 3, '', 37, '2026-04-19 03:48:06'),
(189, 10, 42, 1, 3, '', 37, '2026-04-19 03:48:07'),
(190, 11, 1, 1, 3, '', 37, '2026-04-19 04:31:27'),
(191, 11, 2, 1, 3, '', 37, '2026-04-19 04:31:28'),
(192, 11, 3, 1, 3, '', 37, '2026-04-19 04:31:29'),
(193, 11, 7, 1, 3, '', 37, '2026-04-19 04:31:41'),
(194, 11, 8, 1, 3, '', 37, '2026-04-19 04:31:34'),
(195, 11, 9, 1, 3, '', 37, '2026-04-19 04:31:35'),
(197, 11, 10, 1, 3, '', 37, '2026-04-19 04:31:44'),
(198, 11, 11, 1, 2, '', 37, '2026-04-19 04:31:48'),
(200, 11, 12, 1, 3, '', 37, '2026-04-19 04:31:50'),
(201, 11, 13, 1, 2, '', 37, '2026-04-19 04:32:31'),
(202, 11, 14, 1, 3, '', 37, '2026-04-19 04:32:32'),
(203, 11, 15, 1, 3, '', 37, '2026-04-19 04:32:33'),
(204, 11, 16, 1, 3, '', 37, '2026-04-19 04:32:34'),
(205, 11, 17, 1, 4, '', 37, '2026-04-19 04:32:37'),
(208, 11, 18, 1, 3, '', 37, '2026-04-19 04:32:39'),
(209, 11, 19, 1, 3, '', 37, '2026-04-19 04:32:41'),
(210, 11, 20, 1, 2, '', 37, '2026-04-19 04:32:42'),
(211, 11, 21, 1, 3, '', 37, '2026-04-19 04:32:43'),
(212, 11, 22, 1, 3, '', 37, '2026-04-19 04:32:44'),
(213, 11, 23, 1, 4, '', 37, '2026-04-19 04:32:46'),
(214, 11, 24, 1, 4, '', 37, '2026-04-19 04:32:47'),
(215, 11, 25, 1, 4, '', 37, '2026-04-19 04:32:51'),
(217, 11, 26, 1, 3, '', 37, '2026-04-19 04:32:52'),
(218, 11, 27, 1, 2, '', 37, '2026-04-19 04:32:54'),
(219, 11, 29, 1, 3, '', 37, '2026-04-19 04:32:56'),
(220, 11, 30, 1, 3, '', 37, '2026-04-19 04:32:58'),
(221, 11, 31, 1, 3, '', 37, '2026-04-19 04:33:00'),
(222, 11, 32, 1, 3, '', 37, '2026-04-19 04:33:02'),
(223, 11, 33, 1, 2, '', 37, '2026-04-19 04:33:03'),
(224, 11, 34, 1, 3, '', 37, '2026-04-19 04:33:04'),
(225, 11, 35, 1, 3, '', 37, '2026-04-19 04:33:05'),
(226, 11, 36, 1, 4, '', 37, '2026-04-19 04:33:07'),
(227, 11, 37, 1, 4, '', 37, '2026-04-19 04:33:08'),
(228, 11, 38, 1, 4, '', 37, '2026-04-19 04:33:09'),
(229, 11, 39, 1, 4, '', 37, '2026-04-19 04:33:11'),
(230, 11, 40, 1, 4, '', 37, '2026-04-19 04:33:13'),
(231, 11, 41, 1, 4, '', 37, '2026-04-19 04:33:14'),
(232, 11, 42, 1, 3, '', 37, '2026-04-19 04:33:16'),
(233, 12, 1, 1, 3, '', 37, '2026-04-19 04:41:31'),
(234, 12, 2, 1, 3, '', 37, '2026-04-19 04:41:32'),
(235, 12, 3, 1, 3, '', 37, '2026-04-19 04:41:34'),
(236, 12, 7, 1, 3, '', 37, '2026-04-19 04:41:37'),
(237, 12, 8, 1, 2, '', 37, '2026-04-19 04:41:39'),
(238, 12, 9, 1, 3, '', 37, '2026-04-19 04:41:40'),
(239, 12, 10, 1, 3, '', 37, '2026-04-19 04:41:41'),
(240, 12, 11, 1, 3, '', 37, '2026-04-19 04:41:43'),
(241, 12, 12, 1, 2, '', 37, '2026-04-19 04:41:45'),
(242, 12, 13, 1, 3, '', 37, '2026-04-19 04:41:47'),
(243, 12, 14, 1, 3, '', 37, '2026-04-19 04:41:48'),
(244, 12, 15, 1, 4, '', 37, '2026-04-19 04:41:49'),
(245, 12, 16, 1, 3, '', 37, '2026-04-19 04:41:50'),
(246, 12, 17, 1, 3, '', 37, '2026-04-19 04:41:52'),
(248, 12, 18, 1, 4, '', 37, '2026-04-19 04:41:53'),
(249, 12, 19, 1, 3, '', 37, '2026-04-19 04:41:55'),
(250, 12, 20, 1, 3, '', 37, '2026-04-19 04:41:56'),
(251, 12, 21, 1, 2, '', 37, '2026-04-19 04:42:01'),
(252, 12, 22, 1, 3, '', 37, '2026-04-19 04:42:02'),
(253, 12, 23, 1, 3, '', 37, '2026-04-19 04:42:03'),
(254, 12, 24, 1, 3, '', 37, '2026-04-19 04:42:06'),
(255, 12, 25, 1, 3, '', 37, '2026-04-19 04:42:07'),
(256, 12, 26, 1, 2, '', 37, '2026-04-19 04:42:08'),
(257, 12, 27, 1, 3, '', 37, '2026-04-19 04:42:09'),
(258, 12, 29, 1, 3, '', 37, '2026-04-19 04:42:11'),
(259, 12, 30, 1, 3, '', 37, '2026-04-19 04:42:12'),
(260, 12, 31, 1, 3, '', 37, '2026-04-19 04:42:13'),
(261, 12, 32, 1, 2, '', 37, '2026-04-19 04:42:14'),
(262, 12, 33, 1, 3, '', 37, '2026-04-19 04:42:16'),
(263, 12, 34, 1, 4, '', 37, '2026-04-19 04:42:23'),
(264, 12, 35, 1, 3, '', 37, '2026-04-19 04:42:21'),
(266, 12, 36, 1, 4, '', 37, '2026-04-19 04:42:25'),
(267, 12, 37, 1, 4, '', 37, '2026-04-19 04:42:27'),
(268, 12, 38, 1, 4, '', 37, '2026-04-19 04:42:28'),
(269, 12, 39, 1, 2, '', 37, '2026-04-19 04:42:31'),
(271, 12, 40, 1, 3, '', 37, '2026-04-19 04:42:33'),
(273, 12, 41, 1, 3, '', 37, '2026-04-19 04:42:34'),
(274, 12, 42, 1, 3, '', 37, '2026-04-19 04:42:35');

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

--
-- Dumping data for table `sbm_workflow_phases`
--

INSERT INTO `sbm_workflow_phases` (`phase_id`, `sy_id`, `phase_no`, `phase_name`, `description`, `date_start`, `date_end`, `is_active`) VALUES
(7, 14, 1, 'Self-Assessment', '', '2026-04-15', '2026-04-20', 1),
(8, 14, 2, 'Validation', '', '2026-04-20', '2026-04-20', 1),
(9, 14, 3, 'Improvement Planning', '', '2026-04-21', '2026-04-21', 1),
(10, 16, 1, 'Self-Assessment', '', '2028-06-25', '2028-06-28', 1);

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
(14, '2025-2026', 0, '2025-06-16', '2026-03-31'),
(15, '2026-2027', 0, '2026-06-08', '2027-09-15'),
(16, '2027-2028', 1, '2027-06-25', NULL);

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
-- Table structure for table `sh_indicator_override_history`
--

CREATE TABLE `sh_indicator_override_history` (
  `history_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `action_type` enum('override','update','clear') NOT NULL,
  `previous_rating` decimal(5,2) DEFAULT NULL,
  `new_rating` decimal(5,2) DEFAULT NULL,
  `override_reason` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(821, 10, 1, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:11:37', '2026-04-19 03:13:57'),
(822, 10, 2, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:11:48', '2026-04-19 03:13:57'),
(823, 10, 2, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 03:11:50', '2026-04-19 03:13:57'),
(824, 10, 4, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:11:54', '2026-04-19 03:13:57'),
(825, 10, 5, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 03:12:01', '2026-04-19 03:13:57'),
(826, 10, 5, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:12:05', '2026-04-19 03:13:57'),
(827, 10, 6, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:12:08', '2026-04-19 03:13:57'),
(828, 10, 7, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:12:14', '2026-04-19 03:13:57'),
(829, 10, 9, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:12:30', '2026-04-19 03:13:57'),
(830, 10, 10, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:12:34', '2026-04-19 03:13:57'),
(831, 10, 11, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:12:37', '2026-04-19 03:13:57'),
(832, 10, 11, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:12:40', '2026-04-19 03:13:57'),
(833, 10, 12, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:12:46', '2026-04-19 03:13:57'),
(834, 10, 17, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:12:50', '2026-04-19 03:13:57'),
(835, 10, 21, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 03:12:56', '2026-04-19 03:13:57'),
(836, 10, 28, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:13:02', '2026-04-19 03:13:57'),
(837, 10, 28, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:13:03', '2026-04-19 03:13:57'),
(838, 10, 28, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:13:03', '2026-04-19 03:13:57'),
(839, 10, 29, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:13:15', '2026-04-19 03:13:57'),
(840, 10, 31, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 03:13:21', '2026-04-19 03:13:57'),
(841, 10, 32, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 03:13:29', '2026-04-19 03:13:57'),
(842, 10, 33, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:13:33', '2026-04-19 03:13:57'),
(843, 10, 34, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:13:39', '2026-04-19 03:13:57'),
(844, 10, 35, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 03:13:45', '2026-04-19 03:13:57'),
(845, 10, 38, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:13:47', '2026-04-19 03:13:57'),
(846, 10, 39, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:13:50', '2026-04-19 03:13:57'),
(847, 10, 40, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 03:13:53', '2026-04-19 03:13:57'),
(848, 10, 1, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:22:10', '2026-04-19 03:25:08'),
(849, 10, 2, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:22:12', '2026-04-19 03:25:08'),
(850, 10, 2, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:22:13', '2026-04-19 03:25:08'),
(851, 10, 1, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:22:14', '2026-04-19 03:25:08'),
(852, 10, 2, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:22:41', '2026-04-19 03:25:08'),
(853, 10, 4, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:22:47', '2026-04-19 03:25:08'),
(854, 10, 4, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:22:48', '2026-04-19 03:25:08'),
(855, 10, 5, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:23:05', '2026-04-19 03:25:08'),
(856, 10, 6, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:23:14', '2026-04-19 03:25:08'),
(857, 10, 7, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:23:20', '2026-04-19 03:25:08'),
(858, 10, 9, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:23:25', '2026-04-19 03:25:08'),
(859, 10, 10, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:23:26', '2026-04-19 03:25:08'),
(860, 10, 10, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:23:27', '2026-04-19 03:25:08'),
(861, 10, 11, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:23:30', '2026-04-19 03:25:08'),
(862, 10, 12, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:23:33', '2026-04-19 03:25:08'),
(863, 10, 17, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:23:44', '2026-04-19 03:25:08'),
(864, 10, 21, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:23:48', '2026-04-19 03:25:08'),
(865, 10, 28, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:23:52', '2026-04-19 03:25:08'),
(866, 10, 29, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:23:59', '2026-04-19 03:25:08'),
(867, 10, 31, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:24:23', '2026-04-19 03:25:08'),
(868, 10, 31, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 03:24:26', '2026-04-19 03:25:08'),
(869, 10, 32, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:24:37', '2026-04-19 03:25:08'),
(870, 10, 33, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 03:24:44', '2026-04-19 03:25:08'),
(871, 10, 34, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:24:50', '2026-04-19 03:25:08'),
(872, 10, 35, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:24:52', '2026-04-19 03:25:08'),
(873, 10, 38, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:25:03', '2026-04-19 03:25:08'),
(874, 10, 39, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:25:04', '2026-04-19 03:25:08'),
(875, 10, 40, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 03:25:05', '2026-04-19 03:25:08'),
(876, 10, 1, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 03:26:51', '2026-04-19 03:28:05'),
(877, 10, 2, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:26:54', '2026-04-19 03:28:05'),
(878, 10, 4, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:06', '2026-04-19 03:28:05'),
(879, 10, 5, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:09', '2026-04-19 03:28:05'),
(880, 10, 6, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 03:27:12', '2026-04-19 03:28:05'),
(881, 10, 7, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:18', '2026-04-19 03:28:05'),
(882, 10, 9, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:21', '2026-04-19 03:28:05'),
(883, 10, 10, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 03:27:23', '2026-04-19 03:28:05'),
(884, 10, 11, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:26', '2026-04-19 03:28:05'),
(885, 10, 12, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 03:27:31', '2026-04-19 03:28:05'),
(886, 10, 17, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:34', '2026-04-19 03:28:05'),
(887, 10, 17, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:36', '2026-04-19 03:28:05'),
(888, 10, 21, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:39', '2026-04-19 03:28:05'),
(889, 10, 28, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:44', '2026-04-19 03:28:05'),
(890, 10, 29, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:48', '2026-04-19 03:28:05'),
(891, 10, 31, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:50', '2026-04-19 03:28:05'),
(892, 10, 32, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 03:27:52', '2026-04-19 03:28:05'),
(893, 10, 33, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:55', '2026-04-19 03:28:05'),
(894, 10, 34, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:27:57', '2026-04-19 03:28:05'),
(895, 10, 35, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:28:00', '2026-04-19 03:28:05'),
(896, 10, 38, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:28:01', '2026-04-19 03:28:05'),
(897, 10, 39, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:28:02', '2026-04-19 03:28:05'),
(898, 10, 40, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 03:28:04', '2026-04-19 03:28:05'),
(899, 10, 1, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 03:44:16', '2026-04-19 03:44:59'),
(900, 10, 1, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:18', '2026-04-19 03:44:59'),
(901, 10, 2, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:19', '2026-04-19 03:44:59'),
(902, 10, 4, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:20', '2026-04-19 03:44:59'),
(903, 10, 5, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:22', '2026-04-19 03:44:59'),
(904, 10, 6, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:24', '2026-04-19 03:44:59'),
(905, 10, 7, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:26', '2026-04-19 03:44:59'),
(906, 10, 9, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 03:44:27', '2026-04-19 03:44:59'),
(907, 10, 9, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:29', '2026-04-19 03:44:59'),
(908, 10, 10, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:30', '2026-04-19 03:44:59'),
(909, 10, 11, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:31', '2026-04-19 03:44:59'),
(910, 10, 12, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:38', '2026-04-19 03:44:59'),
(911, 10, 17, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:40', '2026-04-19 03:44:59'),
(912, 10, 21, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:41', '2026-04-19 03:44:59'),
(913, 10, 28, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:43', '2026-04-19 03:44:59'),
(914, 10, 29, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:44', '2026-04-19 03:44:59'),
(915, 10, 31, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 03:44:46', '2026-04-19 03:44:59'),
(916, 10, 31, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:48', '2026-04-19 03:44:59'),
(917, 10, 32, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:48', '2026-04-19 03:44:59'),
(918, 10, 33, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:50', '2026-04-19 03:44:59'),
(919, 10, 34, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 03:44:51', '2026-04-19 03:44:59'),
(920, 10, 35, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:52', '2026-04-19 03:44:59'),
(921, 10, 38, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:54', '2026-04-19 03:44:59'),
(922, 10, 39, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:55', '2026-04-19 03:44:59'),
(923, 10, 40, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 03:44:56', '2026-04-19 03:44:59'),
(924, 10, 1, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:28', '2026-04-19 03:46:04'),
(925, 10, 1, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 03:45:29', '2026-04-19 03:46:04'),
(926, 10, 2, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:30', '2026-04-19 03:46:04'),
(927, 10, 4, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:31', '2026-04-19 03:46:04'),
(928, 10, 5, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:33', '2026-04-19 03:46:04'),
(929, 10, 5, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:34', '2026-04-19 03:46:04'),
(930, 10, 6, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:35', '2026-04-19 03:46:04'),
(931, 10, 6, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 03:45:36', '2026-04-19 03:46:04'),
(932, 10, 7, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:37', '2026-04-19 03:46:04'),
(933, 10, 9, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:38', '2026-04-19 03:46:04'),
(934, 10, 9, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:39', '2026-04-19 03:46:04'),
(935, 10, 10, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:40', '2026-04-19 03:46:04'),
(936, 10, 9, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:41', '2026-04-19 03:46:04'),
(937, 10, 11, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:43', '2026-04-19 03:46:04'),
(938, 10, 12, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:44', '2026-04-19 03:46:04'),
(939, 10, 17, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:45', '2026-04-19 03:46:04'),
(940, 10, 12, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 03:45:46', '2026-04-19 03:46:04'),
(941, 10, 21, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:48', '2026-04-19 03:46:04'),
(942, 10, 28, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:50', '2026-04-19 03:46:04'),
(943, 10, 29, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:51', '2026-04-19 03:46:04'),
(944, 10, 31, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 03:45:53', '2026-04-19 03:46:04'),
(945, 10, 32, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:54', '2026-04-19 03:46:04'),
(946, 10, 33, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:55', '2026-04-19 03:46:04'),
(947, 10, 34, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 03:45:57', '2026-04-19 03:46:04'),
(948, 10, 35, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 03:45:58', '2026-04-19 03:46:04'),
(949, 10, 38, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:45:59', '2026-04-19 03:46:04'),
(950, 10, 39, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:46:01', '2026-04-19 03:46:04'),
(951, 10, 40, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 03:46:02', '2026-04-19 03:46:04'),
(952, 11, 1, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:05:25', '2026-04-19 04:08:20'),
(953, 11, 2, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:05:31', '2026-04-19 04:08:20'),
(954, 11, 4, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:05:37', '2026-04-19 04:08:20'),
(955, 11, 5, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:05:45', '2026-04-19 04:08:20'),
(956, 11, 6, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:05:50', '2026-04-19 04:08:20'),
(957, 11, 7, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:06:19', '2026-04-19 04:08:20'),
(958, 11, 9, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:06:24', '2026-04-19 04:08:20'),
(959, 11, 10, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:06:28', '2026-04-19 04:08:20'),
(960, 11, 9, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:06:31', '2026-04-19 04:08:20'),
(961, 11, 9, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:06:32', '2026-04-19 04:08:20'),
(962, 11, 11, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:06:41', '2026-04-19 04:08:20'),
(963, 11, 11, 1, 15, 1, '', 'submitted', NULL, '2026-04-19 04:06:44', '2026-04-19 04:08:20'),
(964, 11, 11, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:06:45', '2026-04-19 04:08:20'),
(965, 11, 12, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:06:52', '2026-04-19 04:08:20'),
(966, 11, 17, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:06:58', '2026-04-19 04:08:20'),
(967, 11, 21, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:07:04', '2026-04-19 04:08:20'),
(968, 11, 28, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:07:11', '2026-04-19 04:08:20'),
(969, 11, 29, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:07:16', '2026-04-19 04:08:20'),
(970, 11, 31, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:07:21', '2026-04-19 04:08:20'),
(971, 11, 32, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:07:26', '2026-04-19 04:08:20'),
(972, 11, 33, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:07:31', '2026-04-19 04:08:20'),
(973, 11, 34, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:07:40', '2026-04-19 04:08:20'),
(974, 11, 35, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:07:49', '2026-04-19 04:08:20'),
(975, 11, 38, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:07:54', '2026-04-19 04:08:20'),
(976, 11, 39, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:08:00', '2026-04-19 04:08:20'),
(977, 11, 40, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:08:04', '2026-04-19 04:08:20'),
(978, 11, 1, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:10:04', '2026-04-19 04:12:28'),
(979, 11, 2, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:10:07', '2026-04-19 04:12:28'),
(980, 11, 4, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:10:13', '2026-04-19 04:12:28'),
(981, 11, 5, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:10:21', '2026-04-19 04:12:28'),
(982, 11, 6, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:10:27', '2026-04-19 04:12:28'),
(983, 11, 7, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:10:36', '2026-04-19 04:12:28'),
(984, 11, 9, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:10:43', '2026-04-19 04:12:28'),
(985, 11, 10, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:10:49', '2026-04-19 04:12:28'),
(986, 11, 11, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:10:56', '2026-04-19 04:12:28'),
(987, 11, 12, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:01', '2026-04-19 04:12:28'),
(988, 11, 17, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:09', '2026-04-19 04:12:28'),
(989, 11, 21, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:12', '2026-04-19 04:12:28'),
(990, 11, 28, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:19', '2026-04-19 04:12:28'),
(991, 11, 29, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:11:28', '2026-04-19 04:12:28'),
(992, 11, 31, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:35', '2026-04-19 04:12:28'),
(993, 11, 32, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:45', '2026-04-19 04:12:28'),
(994, 11, 33, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:11:53', '2026-04-19 04:12:28'),
(995, 11, 33, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:11:56', '2026-04-19 04:12:28'),
(996, 11, 34, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:12:01', '2026-04-19 04:12:28'),
(997, 11, 35, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:12:08', '2026-04-19 04:12:28'),
(998, 11, 38, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:12:12', '2026-04-19 04:12:28'),
(999, 11, 39, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:12:18', '2026-04-19 04:12:28'),
(1000, 11, 40, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:12:22', '2026-04-19 04:12:28'),
(1001, 11, 1, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:17:48', '2026-04-19 04:19:45'),
(1002, 11, 2, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:17:58', '2026-04-19 04:19:45'),
(1003, 11, 4, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:01', '2026-04-19 04:19:45'),
(1004, 11, 5, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:06', '2026-04-19 04:19:45'),
(1005, 11, 6, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:18:10', '2026-04-19 04:19:45'),
(1006, 11, 7, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:15', '2026-04-19 04:19:45'),
(1007, 11, 9, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:20', '2026-04-19 04:19:45'),
(1008, 11, 10, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:22', '2026-04-19 04:19:45'),
(1009, 11, 11, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:26', '2026-04-19 04:19:45'),
(1010, 11, 12, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:18:29', '2026-04-19 04:19:45'),
(1011, 11, 17, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:34', '2026-04-19 04:19:45'),
(1012, 11, 21, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:18:47', '2026-04-19 04:19:45'),
(1013, 11, 28, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:52', '2026-04-19 04:19:45'),
(1014, 11, 29, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:18:59', '2026-04-19 04:19:45'),
(1015, 11, 31, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:19:03', '2026-04-19 04:19:45'),
(1016, 11, 32, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:19:11', '2026-04-19 04:19:45'),
(1017, 11, 33, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:19:17', '2026-04-19 04:19:45'),
(1018, 11, 34, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:19:23', '2026-04-19 04:19:45'),
(1019, 11, 35, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:19:28', '2026-04-19 04:19:45'),
(1020, 11, 38, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:19:32', '2026-04-19 04:19:45'),
(1021, 11, 39, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:19:37', '2026-04-19 04:19:45'),
(1022, 11, 40, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:19:40', '2026-04-19 04:19:45'),
(1023, 11, 1, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:23:44', '2026-04-19 04:25:26'),
(1024, 11, 2, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:23:49', '2026-04-19 04:25:26'),
(1025, 11, 4, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:23:53', '2026-04-19 04:25:26'),
(1026, 11, 5, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:23:56', '2026-04-19 04:25:26'),
(1027, 11, 6, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:23:59', '2026-04-19 04:25:26'),
(1028, 11, 7, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:04', '2026-04-19 04:25:26'),
(1029, 11, 9, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:09', '2026-04-19 04:25:26'),
(1030, 11, 10, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:11', '2026-04-19 04:25:26'),
(1031, 11, 11, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:15', '2026-04-19 04:25:26'),
(1032, 11, 12, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:19', '2026-04-19 04:25:26'),
(1033, 11, 17, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:25', '2026-04-19 04:25:26'),
(1034, 11, 21, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:24:29', '2026-04-19 04:25:26'),
(1035, 11, 28, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:36', '2026-04-19 04:25:26'),
(1036, 11, 29, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:24:44', '2026-04-19 04:25:26'),
(1037, 11, 31, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:24:48', '2026-04-19 04:25:26'),
(1038, 11, 32, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:24:57', '2026-04-19 04:25:26'),
(1039, 11, 33, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:25:00', '2026-04-19 04:25:26'),
(1040, 11, 34, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:25:05', '2026-04-19 04:25:26'),
(1041, 11, 35, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:25:09', '2026-04-19 04:25:26'),
(1042, 11, 38, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:25:13', '2026-04-19 04:25:26'),
(1043, 11, 39, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:25:17', '2026-04-19 04:25:26'),
(1044, 11, 39, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:25:18', '2026-04-19 04:25:26'),
(1045, 11, 40, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:25:21', '2026-04-19 04:25:26'),
(1046, 11, 1, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:08', '2026-04-19 04:30:11'),
(1047, 11, 2, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:12', '2026-04-19 04:30:11'),
(1048, 11, 4, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:27:17', '2026-04-19 04:30:11'),
(1049, 11, 5, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:23', '2026-04-19 04:30:11'),
(1050, 11, 5, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:27:24', '2026-04-19 04:30:11'),
(1051, 11, 6, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:28', '2026-04-19 04:30:11'),
(1052, 11, 7, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:27:32', '2026-04-19 04:30:11'),
(1053, 11, 9, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:36', '2026-04-19 04:30:11'),
(1054, 11, 10, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:38', '2026-04-19 04:30:11'),
(1055, 11, 11, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:41', '2026-04-19 04:30:11'),
(1056, 11, 12, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:44', '2026-04-19 04:30:11'),
(1057, 11, 17, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:47', '2026-04-19 04:30:11'),
(1058, 11, 21, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:50', '2026-04-19 04:30:11'),
(1059, 11, 28, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:53', '2026-04-19 04:30:11'),
(1060, 11, 29, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:27:57', '2026-04-19 04:30:11'),
(1061, 11, 31, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:28:02', '2026-04-19 04:30:11'),
(1062, 11, 32, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:28:04', '2026-04-19 04:30:11'),
(1063, 11, 33, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:28:07', '2026-04-19 04:30:11'),
(1064, 11, 34, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:28:11', '2026-04-19 04:30:11'),
(1065, 11, 35, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:28:14', '2026-04-19 04:30:11'),
(1066, 11, 38, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:29:56', '2026-04-19 04:30:11'),
(1067, 11, 40, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:30:01', '2026-04-19 04:30:11'),
(1068, 11, 39, 1, 13, 4, '', 'submitted', NULL, '2026-04-19 04:30:03', '2026-04-19 04:30:11'),
(1069, 12, 1, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:09', '2026-04-19 04:38:41'),
(1070, 12, 2, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:10', '2026-04-19 04:38:41'),
(1071, 12, 4, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:11', '2026-04-19 04:38:41'),
(1072, 12, 5, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:13', '2026-04-19 04:38:41'),
(1073, 12, 6, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:14', '2026-04-19 04:38:41'),
(1074, 12, 7, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:16', '2026-04-19 04:38:41'),
(1075, 12, 9, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:17', '2026-04-19 04:38:41'),
(1076, 12, 10, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:18', '2026-04-19 04:38:41'),
(1077, 12, 11, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:19', '2026-04-19 04:38:41'),
(1078, 12, 12, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:20', '2026-04-19 04:38:41'),
(1079, 12, 17, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:21', '2026-04-19 04:38:41'),
(1080, 12, 21, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:38:22', '2026-04-19 04:38:41'),
(1081, 12, 28, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:23', '2026-04-19 04:38:41'),
(1082, 12, 29, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:26', '2026-04-19 04:38:41'),
(1083, 12, 31, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:27', '2026-04-19 04:38:41'),
(1084, 12, 32, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:28', '2026-04-19 04:38:41'),
(1085, 12, 33, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:29', '2026-04-19 04:38:41'),
(1086, 12, 34, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:30', '2026-04-19 04:38:41'),
(1087, 12, 35, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:31', '2026-04-19 04:38:41'),
(1088, 12, 38, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:38:33', '2026-04-19 04:38:41'),
(1089, 12, 39, 1, 15, 3, '', 'submitted', NULL, '2026-04-19 04:38:34', '2026-04-19 04:38:41'),
(1090, 12, 40, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:36', '2026-04-19 04:38:41'),
(1091, 12, 40, 1, 15, 4, '', 'submitted', NULL, '2026-04-19 04:38:38', '2026-04-19 04:38:41'),
(1092, 12, 39, 1, 15, 2, '', 'submitted', NULL, '2026-04-19 04:38:39', '2026-04-19 04:38:41'),
(1093, 12, 1, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:38:53', '2026-04-19 04:39:22'),
(1094, 12, 2, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:38:54', '2026-04-19 04:39:22'),
(1095, 12, 4, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:38:55', '2026-04-19 04:39:22'),
(1096, 12, 5, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:38:56', '2026-04-19 04:39:22'),
(1097, 12, 6, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:38:57', '2026-04-19 04:39:22'),
(1098, 12, 7, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:38:58', '2026-04-19 04:39:22'),
(1099, 12, 9, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:38:59', '2026-04-19 04:39:22'),
(1100, 12, 10, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:00', '2026-04-19 04:39:22'),
(1101, 12, 11, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:39:01', '2026-04-19 04:39:22'),
(1102, 12, 12, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:03', '2026-04-19 04:39:22'),
(1103, 12, 17, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:39:04', '2026-04-19 04:39:22'),
(1104, 12, 21, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:05', '2026-04-19 04:39:22'),
(1105, 12, 28, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:06', '2026-04-19 04:39:22'),
(1106, 12, 29, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:39:08', '2026-04-19 04:39:22'),
(1107, 12, 31, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:09', '2026-04-19 04:39:22'),
(1108, 12, 32, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:11', '2026-04-19 04:39:22'),
(1109, 12, 33, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:39:12', '2026-04-19 04:39:22'),
(1110, 12, 34, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:13', '2026-04-19 04:39:22'),
(1111, 12, 35, 1, 2, 3, '', 'submitted', NULL, '2026-04-19 04:39:14', '2026-04-19 04:39:22'),
(1112, 12, 38, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:39:16', '2026-04-19 04:39:22'),
(1113, 12, 38, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:39:18', '2026-04-19 04:39:22'),
(1114, 12, 39, 1, 2, 2, '', 'submitted', NULL, '2026-04-19 04:39:19', '2026-04-19 04:39:22'),
(1115, 12, 40, 1, 2, 4, '', 'submitted', NULL, '2026-04-19 04:39:20', '2026-04-19 04:39:22'),
(1116, 12, 1, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:33', '2026-04-19 04:40:01'),
(1117, 12, 2, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:39:35', '2026-04-19 04:40:01'),
(1118, 12, 4, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:36', '2026-04-19 04:40:01'),
(1119, 12, 5, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:37', '2026-04-19 04:40:01'),
(1120, 12, 6, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:38', '2026-04-19 04:40:01'),
(1121, 12, 7, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:39:39', '2026-04-19 04:40:01'),
(1122, 12, 9, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:40', '2026-04-19 04:40:01'),
(1123, 12, 10, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:41', '2026-04-19 04:40:01'),
(1124, 12, 11, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:43', '2026-04-19 04:40:01'),
(1125, 12, 12, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:39:44', '2026-04-19 04:40:01'),
(1126, 12, 12, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:45', '2026-04-19 04:40:01'),
(1127, 12, 17, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:39:47', '2026-04-19 04:40:01'),
(1128, 12, 21, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:48', '2026-04-19 04:40:01'),
(1129, 12, 28, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:49', '2026-04-19 04:40:01'),
(1130, 12, 29, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:39:50', '2026-04-19 04:40:01'),
(1131, 12, 31, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:51', '2026-04-19 04:40:01'),
(1132, 12, 32, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:52', '2026-04-19 04:40:01'),
(1133, 12, 33, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:54', '2026-04-19 04:40:01'),
(1134, 12, 34, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:39:55', '2026-04-19 04:40:01'),
(1135, 12, 35, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:56', '2026-04-19 04:40:01'),
(1136, 12, 38, 1, 12, 4, '', 'submitted', NULL, '2026-04-19 04:39:57', '2026-04-19 04:40:01'),
(1137, 12, 39, 1, 12, 2, '', 'submitted', NULL, '2026-04-19 04:39:58', '2026-04-19 04:40:01'),
(1138, 12, 40, 1, 12, 3, '', 'submitted', NULL, '2026-04-19 04:39:59', '2026-04-19 04:40:01'),
(1139, 12, 1, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:08', '2026-04-19 04:40:36'),
(1140, 12, 2, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:09', '2026-04-19 04:40:36'),
(1141, 12, 4, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:11', '2026-04-19 04:40:36'),
(1142, 12, 5, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:12', '2026-04-19 04:40:36'),
(1143, 12, 6, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:13', '2026-04-19 04:40:36'),
(1144, 12, 7, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:14', '2026-04-19 04:40:36'),
(1145, 12, 9, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:15', '2026-04-19 04:40:36'),
(1146, 12, 10, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:16', '2026-04-19 04:40:36'),
(1147, 12, 11, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:18', '2026-04-19 04:40:36'),
(1148, 12, 12, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:19', '2026-04-19 04:40:36'),
(1149, 12, 17, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:20', '2026-04-19 04:40:36'),
(1150, 12, 21, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:21', '2026-04-19 04:40:36'),
(1151, 12, 28, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:23', '2026-04-19 04:40:36'),
(1152, 12, 29, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:24', '2026-04-19 04:40:36'),
(1153, 12, 31, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:25', '2026-04-19 04:40:36'),
(1154, 12, 32, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:26', '2026-04-19 04:40:36'),
(1155, 12, 33, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:28', '2026-04-19 04:40:36'),
(1156, 12, 34, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:29', '2026-04-19 04:40:36'),
(1157, 12, 35, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:30', '2026-04-19 04:40:36'),
(1158, 12, 38, 1, 14, 4, '', 'submitted', NULL, '2026-04-19 04:40:32', '2026-04-19 04:40:36'),
(1159, 12, 39, 1, 14, 2, '', 'submitted', NULL, '2026-04-19 04:40:33', '2026-04-19 04:40:36'),
(1160, 12, 40, 1, 14, 3, '', 'submitted', NULL, '2026-04-19 04:40:35', '2026-04-19 04:40:36'),
(1161, 12, 1, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:45', '2026-04-19 04:41:14'),
(1162, 12, 2, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:40:47', '2026-04-19 04:41:14'),
(1163, 12, 4, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:48', '2026-04-19 04:41:14'),
(1164, 12, 5, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:49', '2026-04-19 04:41:14'),
(1165, 12, 6, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:40:50', '2026-04-19 04:41:14'),
(1166, 12, 7, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:51', '2026-04-19 04:41:14'),
(1167, 12, 9, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:52', '2026-04-19 04:41:14'),
(1168, 12, 10, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:40:54', '2026-04-19 04:41:14'),
(1169, 12, 11, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:55', '2026-04-19 04:41:14'),
(1170, 12, 12, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:56', '2026-04-19 04:41:14'),
(1171, 12, 17, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:40:57', '2026-04-19 04:41:14'),
(1172, 12, 21, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:40:58', '2026-04-19 04:41:14'),
(1173, 12, 28, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:00', '2026-04-19 04:41:14'),
(1174, 12, 29, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:41:02', '2026-04-19 04:41:14'),
(1175, 12, 31, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:03', '2026-04-19 04:41:14'),
(1176, 12, 32, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:04', '2026-04-19 04:41:14'),
(1177, 12, 33, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:41:05', '2026-04-19 04:41:14'),
(1178, 12, 34, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:06', '2026-04-19 04:41:14'),
(1179, 12, 35, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:08', '2026-04-19 04:41:14'),
(1180, 12, 38, 1, 13, 2, '', 'submitted', NULL, '2026-04-19 04:41:09', '2026-04-19 04:41:14'),
(1181, 12, 39, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:10', '2026-04-19 04:41:14'),
(1182, 12, 40, 1, 13, 3, '', 'submitted', NULL, '2026-04-19 04:41:12', '2026-04-19 04:41:14');

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
(32, 10, 1, 14, 15, 'submitted', '2026-04-19 11:13:57', 27),
(33, 10, 1, 14, 2, 'submitted', '2026-04-19 11:25:08', 28),
(34, 10, 1, 14, 12, 'submitted', '2026-04-19 11:28:05', 23),
(35, 10, 1, 14, 14, 'submitted', '2026-04-19 11:44:59', 25),
(36, 10, 1, 14, 13, 'submitted', '2026-04-19 11:46:04', 28),
(37, 11, 1, 15, 15, 'submitted', '2026-04-19 12:08:20', 26),
(38, 11, 1, 15, 2, 'submitted', '2026-04-19 12:12:28', 23),
(39, 11, 1, 15, 12, 'submitted', '2026-04-19 12:19:45', 22),
(40, 11, 1, 15, 14, 'submitted', '2026-04-19 12:25:26', 23),
(41, 11, 1, 15, 13, 'submitted', '2026-04-19 12:30:11', 23),
(42, 12, 1, 16, 15, 'submitted', '2026-04-19 12:38:41', 24),
(43, 12, 1, 16, 2, 'submitted', '2026-04-19 12:39:22', 23),
(44, 12, 1, 16, 12, 'submitted', '2026-04-19 12:40:01', 23),
(45, 12, 1, 16, 14, 'submitted', '2026-04-19 12:40:36', 22),
(46, 12, 1, 16, 13, 'submitted', '2026-04-19 12:41:14', 22);

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
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `school_id` int(11) DEFAULT NULL,
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

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `last_login`, `created_at`, `email_verified`, `reset_token`, `token_expiry`, `email_sent_at`, `force_password_change`, `contact_number`, `profile_picture`) VALUES
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, '2026-04-19 12:38:49', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0, NULL, NULL),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, '2026-04-19 12:39:31', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0, NULL, NULL),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, '2026-04-19 12:40:42', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0, NULL, NULL),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, '2026-04-19 12:40:06', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0, NULL, NULL),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, '2026-04-19 12:38:05', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0, NULL, NULL),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza M. Evangelio', 'school_head', 'active', 1, '2026-04-19 12:43:02', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0, '09412568901', 'uploads/avatars/avatar_37_1776163511.jpg'),
(46, 'Charles', '$2y$10$9QWVYCP/gNj9kS9vZ72OpeK8BsICHhNjMndKyzi4ZBxQ00A3Mw1WS', 'mendozacharles11011@gmail.com', 'Charles Patrick Arias', 'sbm_coordinator', 'active', 1, '2026-04-19 12:42:51', '2026-04-01 02:35:08', 0, NULL, NULL, '2026-04-01 10:35:53', 0, NULL, NULL),
(72, 'Patty', '$2y$10$V5F8wLfNzHXU1XPrYScCBuTOd.le0o88IVEUGx52m4dDIg256otOC', 'ariascharles00@gmail.com', 'Charles Mendoza', 'system_admin', 'active', 1, '2026-04-19 12:35:41', '2026-04-09 09:53:37', 0, NULL, NULL, '2026-04-09 17:53:42', 0, NULL, NULL);

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
,`user_account_status` enum('active','inactive','suspended')
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
-- Indexes for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  ADD PRIMARY KEY (`snap_id`),
  ADD UNIQUE KEY `unique_snap` (`cycle_id`,`dimension_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `sy_id` (`sy_id`),
  ADD KEY `dimension_id` (`dimension_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`ann_id`),
  ADD KEY `posted_by` (`posted_by`);

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
-- Indexes for table `indicator_evidence_requirements`
--
ALTER TABLE `indicator_evidence_requirements`
  ADD PRIMARY KEY (`req_id`),
  ADD UNIQUE KEY `indicator_id` (`indicator_id`);

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
-- Indexes for table `sh_indicator_override_history`
--
ALTER TABLE `sh_indicator_override_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `changed_by` (`changed_by`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=813;

--
-- AUTO_INCREMENT for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  MODIFY `snap_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `ann_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cycle_audit_log`
--
ALTER TABLE `cycle_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `evidence_audit_log`
--
ALTER TABLE `evidence_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

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
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `response_attachments`
--
ALTER TABLE `response_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=400;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=275;

--
-- AUTO_INCREMENT for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  MODIFY `phase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sh_indicator_overrides`
--
ALTER TABLE `sh_indicator_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sh_indicator_override_history`
--
ALTER TABLE `sh_indicator_override_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1183;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

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
-- Constraints for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  ADD CONSTRAINT `analytics_snapshots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_snapshots_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_snapshots_ibfk_3` FOREIGN KEY (`sy_id`) REFERENCES `school_years` (`sy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_snapshots_ibfk_4` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
-- Constraints for table `indicator_evidence_requirements`
--
ALTER TABLE `indicator_evidence_requirements`
  ADD CONSTRAINT `ier_ibfk_1` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE CASCADE;

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
-- Constraints for table `sh_indicator_override_history`
--
ALTER TABLE `sh_indicator_override_history`
  ADD CONSTRAINT `sh_indicator_override_history_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sh_indicator_override_history_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`),
  ADD CONSTRAINT `sh_indicator_override_history_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sh_indicator_override_history_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`);

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
