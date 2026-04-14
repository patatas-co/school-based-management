-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2026 at 11:36 AM
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
(501, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-11 09:35:16');

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
(36, 46, '25de0e85006db5c333e8d45d3733be2eff8b27e9fa2207f55e070ffac278a993', 'setup', '2026-04-03 10:35:08', '2026-04-01 10:40:39', '2026-04-01 02:35:08'),
(74, 72, '39f0807dde06988876bc2e355da85edc901ff689d4a30fb6aa115cf3b853b691', 'setup', '2026-04-11 17:53:37', '2026-04-09 17:55:08', '2026-04-09 09:53:37');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(8, '2026-2027', 1, '2026-07-15', '2027-04-25');

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
  `profile_picture` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `last_login`, `created_at`, `email_verified`, `reset_token`, `token_expiry`, `email_sent_at`, `force_password_change`) VALUES
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, '2026-04-07 13:00:54', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, '2026-04-10 11:00:57', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, '2026-04-11 11:19:00', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, '2026-04-07 13:03:13', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, '2026-04-11 11:42:08', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza Evangelio', 'school_head', 'active', 1, '2026-04-11 17:35:16', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0),
(46, 'Charles', '$2y$10$9QWVYCP/gNj9kS9vZ72OpeK8BsICHhNjMndKyzi4ZBxQ00A3Mw1WS', 'mendozacharles11011@gmail.com', 'Charles Patrick Arias', 'sbm_coordinator', 'active', 1, '2026-04-11 10:58:37', '2026-04-01 02:35:08', 0, NULL, NULL, '2026-04-01 10:35:53', 0),
(72, 'Patty', '$2y$10$V5F8wLfNzHXU1XPrYScCBuTOd.le0o88IVEUGx52m4dDIg256otOC', 'ariascharles00@gmail.com', 'Charles Mendoza', 'system_admin', 'active', 1, '2026-04-11 17:33:37', '2026-04-09 09:53:37', 0, NULL, NULL, '2026-04-09 17:53:42', 0);

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
-- Indexes for table `cycle_stage_gates`
--
ALTER TABLE `cycle_stage_gates`
  ADD PRIMARY KEY (`gate_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `checked_by` (`checked_by`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=502;

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cycle_evaluators`
--
ALTER TABLE `cycle_evaluators`
  MODIFY `evaluator_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycle_stage_gates`
--
ALTER TABLE `cycle_stage_gates`
  MODIFY `gate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `evidence_audit_log`
--
ALTER TABLE `evidence_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

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
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `region_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `response_attachments`
--
ALTER TABLE `response_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

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
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sh_indicator_overrides`
--
ALTER TABLE `sh_indicator_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sh_indicator_override_history`
--
ALTER TABLE `sh_indicator_override_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `teacher_indicator_assignments`
--
ALTER TABLE `teacher_indicator_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_responses`
--
ALTER TABLE `teacher_responses`
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=698;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

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
