-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 08:21 PM
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
(303, 52, 'login', 'auth', 'User logged in', '::1', '2026-04-03 14:28:20'),
(304, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-04-03 15:30:33'),
(305, 52, 'login', 'auth', 'User logged in', '::1', '2026-04-03 15:30:40'),
(306, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-03 15:30:48'),
(307, 37, 'login', 'auth', 'User logged in', '127.0.0.1', '2026-04-04 01:48:16'),
(308, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to inactive', '::1', '2026-04-04 01:57:46'),
(309, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to active', '::1', '2026-04-04 01:57:53'),
(310, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to inactive', '::1', '2026-04-04 02:04:56'),
(311, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:05:20'),
(312, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:05:54'),
(313, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to active', '::1', '2026-04-04 02:06:01'),
(314, NULL, 'password_reset', 'auth', 'User reset password via link', '::1', '2026-04-04 02:06:36'),
(315, 52, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:06:45'),
(316, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:06:53'),
(317, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to inactive', '::1', '2026-04-04 02:06:58'),
(318, 15, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:10:47'),
(319, 37, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:10:55'),
(320, 37, 'toggle_user_status', 'users', 'User ID 52 status changed to active', '::1', '2026-04-04 02:11:02'),
(321, 52, 'login', 'auth', 'User logged in', '::1', '2026-04-04 02:11:08'),
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
(353, 46, 'login', 'auth', 'User logged in', '::1', '2026-04-04 18:13:49');

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

--
-- Dumping data for table `analytics_snapshots`
--

INSERT INTO `analytics_snapshots` (`snap_id`, `school_id`, `cycle_id`, `sy_id`, `sy_label`, `dimension_id`, `dimension_no`, `dimension_name`, `percentage`, `raw_score`, `max_score`, `overall_score`, `maturity_level`, `snapshot_at`) VALUES
(1, 1, 5, 4, '2026-2027', 1, 1, 'Curriculum and Teaching', 62.50, 20.00, 32.00, 67.50, 'Maturing', '2026-04-04 18:06:47'),
(2, 1, 5, 4, '2026-2027', 2, 2, 'Learning Environment', 65.00, 26.00, 40.00, 67.50, 'Maturing', '2026-04-04 18:06:47'),
(3, 1, 5, 4, '2026-2027', 3, 3, 'Leadership', 68.75, 11.00, 16.00, 67.50, 'Maturing', '2026-04-04 18:06:47'),
(4, 1, 5, 4, '2026-2027', 4, 4, 'Governance and Accountability', 68.33, 16.40, 24.00, 67.50, 'Maturing', '2026-04-04 18:06:47'),
(5, 1, 5, 4, '2026-2027', 5, 5, 'Human Resources and Team Development', 67.86, 19.00, 28.00, 67.50, 'Maturing', '2026-04-04 18:06:47'),
(6, 1, 5, 4, '2026-2027', 6, 6, 'Finance and Resource Management and Mobilization', 75.00, 21.00, 28.00, 67.50, 'Maturing', '2026-04-04 18:06:47');

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
(1, 5, 'validated', 'finalized', 46, 'Cycle locked and archived.', '2026-04-05 01:43:58');

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
(38, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:02:53'),
(39, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:05:55'),
(40, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:10:50'),
(41, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:12:50'),
(42, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:13:15'),
(43, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:17:14'),
(44, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:19:34'),
(45, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:21:03'),
(46, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:22:29'),
(47, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:25:27'),
(48, 52, 'account_creation', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 14:27:08'),
(49, 52, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-03 15:29:48'),
(50, 52, 'password_reset', 'ariascharles00@gmail.com', 'sent', NULL, '2026-04-04 02:06:17');

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
(28, 1, 5, 1, 1, 'Medium', 'Improve performance on indicator 1.1: Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(29, 1, 5, 1, 2, 'Medium', 'Improve performance on indicator 1.2: Grade 6, 10, and 12 learners achieve the proficiency level in all 21st-century skills and core learning areas in the National Achievement Test (NAT).', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(30, 1, 5, 1, 4, 'Medium', 'Improve performance on indicator 1.4: Teachers prepare contextualized learning materials responsive to the needs of learners.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(31, 1, 5, 1, 5, 'Medium', 'Improve performance on indicator 1.5: Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(32, 1, 5, 4, 23, 'Medium', 'Improve performance on indicator 4.1: The school&#039;s strategic plan is operationalized through an implementation plan.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(33, 1, 5, 4, 28, 'Medium', 'Improve performance on indicator 4.6: The school maintains an average rating of satisfactory from its internal and external stakeholders.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(34, 1, 5, 5, 30, 'Medium', 'Improve performance on indicator 5.2: The school achieves an average rating of very satisfactory in the office performance commitment and review.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(35, 1, 5, 5, 34, 'Medium', 'Improve performance on indicator 5.6: The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(36, 1, 5, 3, 21, 'Medium', 'Improve performance on indicator 3.3: The school has a functional Supreme Student Government/Supreme Pupil Government.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(37, 1, 5, 2, 9, 'Medium', 'Improve performance on indicator 2.1: The school has zero bullying incidence.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(38, 1, 5, 2, 12, 'Medium', 'Improve performance on indicator 2.4: The school conducts culture-sensitive activities.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(39, 1, 5, 2, 14, 'Medium', 'Improve performance on indicator 2.6: The school has a functional school-based ALS program.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12'),
(40, 1, 5, 2, 17, 'Medium', 'Improve performance on indicator 2.9: The school has a functional support mechanism for mental wellness.', 'Develop targeted interventions to address areas rated \'Rarely Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 46, '2026-04-04 17:33:12');

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

--
-- Dumping data for table `ml_recommendations`
--

INSERT INTO `ml_recommendations` (`rec_id`, `cycle_id`, `recommendation_text`, `generated_by`, `top_topics`, `has_urgent`, `sentiment_summary`, `generated_at`) VALUES
(13, 5, '[Assessment Overview]\nThe Dasmariñas Integrated High School has achieved an overall SBM score of 67.52% with a maturity level of Maturing, as per DepEd Order No. 007, s. 2024, and this is the first assessment cycle with no prior data available for comparison.\n\n[Priority Recommendations]\n1. [1.1] The School Head shall convene a meeting with the Grade 3 teachers to discuss strategies for improving early language, literacy, and numeracy skills, and develop a plan to provide additional support to struggling learners, to be implemented by the end of the first semester, as outlined in DepEd Order No. 007, s. 2024.\n2. [2.1] The School Head shall collaborate with the School Guidance Counselor to develop and implement a bullying prevention program, which includes regular monitoring and reporting of incidents, and provide training to teachers and staff on bullying prevention and intervention, to be completed by the end of the second semester, in line with DepEd Order No. 007, s. 2024.\n3. [4.1] The School Head shall work with the School Governance Council (SGC) to review and refine the school\'s strategic plan, ensuring that it is aligned with the DepEd\'s priorities and includes specific, measurable, achievable, relevant, and time-bound (SMART) objectives, and develop an implementation plan with clear roles and responsibilities, to be finalized by the end of the first semester, as required by DepEd Order No. 007, s. 2024.\n4. [1.5] The School Head shall provide training to teachers on conducting remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics, and ensure that teachers are using contextualized learning materials and integrating topics promoting peace and DepEd core values, as outlined in DepEd Order No. 007, s. 2024, to be completed by the end of the second semester.\n5. [5.2] The School Head shall work with the school\'s administrative staff to review and refine the office performance commitment and review process, ensuring that it is aligned with the DepEd\'s priorities and includes clear expectations and evaluation criteria, and provide training to staff on the new process, to be completed by the end of the first semester, in line with DepEd Order No. 007, s. 2024.\n\n[Stakeholder Focus]\nNo specific stakeholder remarks or themes were identified that are not already covered by the indicator data, therefore, no additional recommendations are made.', 'groq', '[]', 0, '[]', '2026-04-04 18:15:07');

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
(11, 1, 5, '[{\"dimension_name\":\"Curriculum and Teaching\",\"dimension_no\":1,\"gap_from_avg\":5.02,\"maturity\":\"Maturing\",\"priority\":\"medium\",\"score\":62.5,\"weight\":1.2,\"weighted_gap\":6.03},{\"dimension_name\":\"Learning Environment\",\"dimension_no\":2,\"gap_from_avg\":2.52,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":65,\"weight\":1.2,\"weighted_gap\":3.03},{\"dimension_name\":\"Human Resource Development\",\"dimension_no\":5,\"gap_from_avg\":-0.34,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":67.86,\"weight\":0.9,\"weighted_gap\":-0.3},{\"dimension_name\":\"Accountability and Continuous Improvement\",\"dimension_no\":4,\"gap_from_avg\":-0.81,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":68.33,\"weight\":1,\"weighted_gap\":-0.81},{\"dimension_name\":\"Leadership and Governance\",\"dimension_no\":3,\"gap_from_avg\":-1.23,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":68.75,\"weight\":1,\"weighted_gap\":-1.23},{\"dimension_name\":\"Finance and Resource Management\",\"dimension_no\":6,\"gap_from_avg\":-7.48,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":75,\"weight\":0.9,\"weighted_gap\":-6.73}]', '[]', 67.50, 'Maturing', '2026-04-04 17:31:51');

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
(45, 52, '2566ea40057d192c1e7fbde19567db044e585c83cea52719307ac353ed1cbc26', 'setup', '2026-04-05 22:02:48', '2026-04-03 22:05:51', '2026-04-03 14:02:48'),
(46, 52, '1806c1815e2ec1510b21730b35df41e3cc7ac61b78ad2ae690f09b1817ae6ce5', 'setup', '2026-04-05 22:05:51', '2026-04-03 22:10:46', '2026-04-03 14:05:51'),
(47, 52, 'a9a0399197bff0bb8c37a2157a4ef0f4fafada2109ac07e5cdbe1c2c9f6e43d3', 'setup', '2026-04-05 22:10:46', '2026-04-03 22:12:46', '2026-04-03 14:10:46'),
(48, 52, '18ccc7b139e3d6f65c8aa10d4b772e74f25bac73631e1ff1b73f5ee57710eb1d', 'setup', '2026-04-05 22:12:46', '2026-04-03 22:13:11', '2026-04-03 14:12:46'),
(49, 52, '9fae1613efe18d4ebc748de52ceb19a2914202974cb22b1faa9fdbd3b708dd3b', 'setup', '2026-04-05 22:13:11', '2026-04-03 22:17:10', '2026-04-03 14:13:11'),
(50, 52, 'b17ef1b69834689adee3f35fe7e4d913620d264e564606a1a0dd0b739dd6f20c', 'setup', '2026-04-05 22:17:10', '2026-04-03 22:19:29', '2026-04-03 14:17:10'),
(51, 52, '414e44f501ce1257e4868444b7671ba8d0f9fcfd505487f5f943023bceeb27e2', 'setup', '2026-04-05 22:19:29', '2026-04-03 22:21:00', '2026-04-03 14:19:29'),
(52, 52, '434b612a107a4879d0c5f7eb795bcdca2b76f251ce467b5b9456337dea9975ba', 'setup', '2026-04-05 22:21:00', '2026-04-03 22:22:25', '2026-04-03 14:21:00'),
(53, 52, '2ed879a6e827ce5f0577f08d8ae6ccf77c53b8b6ded03709a66a1e5ad7724ad3', 'setup', '2026-04-05 22:22:25', '2026-04-03 22:25:22', '2026-04-03 14:22:25'),
(54, 52, 'a7172dcad43910002bfe9deafbf8c32ca3dbdafc796172aca83e926ed0b13bdc', 'setup', '2026-04-05 22:25:22', '2026-04-03 22:27:03', '2026-04-03 14:25:22'),
(55, 52, '318ee8408bfa29877b0abd243e40706cfba1d988791b759f59226a37e9d6b820', 'setup', '2026-04-05 22:27:03', '2026-04-03 22:28:01', '2026-04-03 14:27:03'),
(56, 52, '19c75085163f01601c53276bb84f9e5919181c9e42eb3c8b43d6ca2991689f10', 'reset', '2026-04-03 23:59:44', '2026-04-03 23:30:33', '2026-04-03 15:29:44'),
(57, 52, '8214ff4cc6dfe7eafe1d3827f169c4485f5d7fb60144b1af7963bdb7050ea1c1', 'reset', '2026-04-04 10:36:12', '2026-04-04 10:06:36', '2026-04-04 02:06:12');

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

--
-- Dumping data for table `response_attachments`
--

INSERT INTO `response_attachments` (`attachment_id`, `cycle_id`, `indicator_id`, `school_id`, `uploaded_by`, `uploader_role`, `original_name`, `stored_name`, `file_size`, `mime_type`, `uploaded_at`) VALUES
(4, 5, 9, 1, 15, 'teacher', 'High-school-boys-bullying-a-smaller-student-155140748_2122x1415-1.jpeg', '95a858f5fb4fcaa8a25f075157d0e769.jpeg', 727083, 'image/jpeg', '2026-04-05 01:23:55');

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

--
-- Dumping data for table `sbm_cycles`
--

INSERT INTO `sbm_cycles` (`cycle_id`, `sy_id`, `school_id`, `status`, `overall_score`, `maturity_level`, `started_at`, `submitted_at`, `validated_at`, `validated_by`, `validator_remarks`, `consolidation_confirmed`, `consolidation_confirmed_by`, `consolidation_confirmed_at`, `finalized_at`, `returned_at`, `returned_by`, `return_remarks`, `created_at`) VALUES
(5, 4, 1, 'finalized', 67.50, 'Maturing', '2026-04-01 22:31:55', '2026-04-05 01:31:41', '2026-04-05 01:32:16', 37, '', 0, NULL, NULL, '2026-04-05 01:43:58', NULL, NULL, NULL, '2026-04-01 14:31:55');

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
(63, 5, 1, 1, 20.00, 32.00, 62.50, '2026-04-04 17:31:41'),
(64, 5, 1, 2, 26.00, 40.00, 65.00, '2026-04-04 17:31:41'),
(65, 5, 1, 3, 11.00, 16.00, 68.75, '2026-04-04 17:31:41'),
(66, 5, 1, 4, 16.40, 24.00, 68.33, '2026-04-04 17:31:41'),
(67, 5, 1, 5, 19.00, 28.00, 67.86, '2026-04-04 17:31:41'),
(68, 5, 1, 6, 21.00, 28.00, 75.00, '2026-04-04 17:31:41');

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
(31, 5, 1, 1, 2, '', 37, '2026-04-04 17:28:52'),
(32, 5, 2, 1, 2, '', 37, '2026-04-04 17:28:54'),
(33, 5, 3, 1, 3, '', 37, '2026-04-04 17:28:59'),
(34, 5, 7, 1, 3, '', 37, '2026-04-04 17:29:12'),
(35, 5, 8, 1, 3, '', 37, '2026-04-04 17:29:14'),
(36, 5, 9, 1, 2, '', 37, '2026-04-04 17:29:17'),
(37, 5, 10, 1, 3, '', 37, '2026-04-04 17:29:19'),
(38, 5, 11, 1, 3, '', 37, '2026-04-04 17:29:30'),
(39, 5, 12, 1, 2, '', 37, '2026-04-04 17:29:32'),
(40, 5, 13, 1, 3, '', 37, '2026-04-04 17:29:34'),
(41, 5, 14, 1, 2, '', 37, '2026-04-04 17:29:37'),
(42, 5, 15, 1, 3, '', 37, '2026-04-04 17:29:40'),
(43, 5, 16, 1, 3, '', 37, '2026-04-04 17:29:42'),
(44, 5, 17, 1, 2, '', 37, '2026-04-04 17:29:44'),
(45, 5, 18, 1, 3, '', 37, '2026-04-04 17:29:46'),
(46, 5, 19, 1, 3, '', 37, '2026-04-04 17:29:50'),
(47, 5, 20, 1, 3, '', 37, '2026-04-04 17:29:54'),
(48, 5, 21, 1, 2, '', 37, '2026-04-04 17:29:56'),
(49, 5, 22, 1, 3, '', 37, '2026-04-04 17:29:58'),
(50, 5, 23, 1, 2, '', 37, '2026-04-04 17:29:59'),
(51, 5, 24, 1, 3, '', 37, '2026-04-04 17:30:00'),
(52, 5, 25, 1, 3, '', 37, '2026-04-04 17:30:02'),
(53, 5, 26, 1, 3, '', 37, '2026-04-04 17:30:03'),
(54, 5, 27, 1, 3, '', 37, '2026-04-04 17:30:04'),
(55, 5, 29, 1, 3, '', 37, '2026-04-04 17:30:07'),
(56, 5, 30, 1, 2, '', 37, '2026-04-04 17:30:09'),
(57, 5, 31, 1, 3, '', 37, '2026-04-04 17:30:12'),
(58, 5, 32, 1, 3, '', 37, '2026-04-04 17:30:14'),
(59, 5, 33, 1, 3, '', 37, '2026-04-04 17:30:16'),
(60, 5, 34, 1, 2, '', 37, '2026-04-04 17:30:18'),
(61, 5, 35, 1, 3, '', 37, '2026-04-04 17:30:22'),
(62, 5, 36, 1, 3, '', 37, '2026-04-04 17:30:24'),
(63, 5, 37, 1, 3, '', 37, '2026-04-04 17:30:25'),
(64, 5, 38, 1, 3, '', 37, '2026-04-04 17:30:27'),
(65, 5, 39, 1, 3, '', 37, '2026-04-04 17:30:30'),
(66, 5, 40, 1, 3, '', 37, '2026-04-04 17:30:31'),
(67, 5, 41, 1, 3, '', 37, '2026-04-04 17:30:33'),
(68, 5, 42, 1, 3, '', 37, '2026-04-04 17:30:34');

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

--
-- Dumping data for table `teacher_responses`
--

INSERT INTO `teacher_responses` (`tr_id`, `cycle_id`, `indicator_id`, `school_id`, `teacher_id`, `rating`, `remarks`, `status`, `submitted_at`, `created_at`, `updated_at`) VALUES
(477, 5, 1, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:23:29', '2026-04-04 17:24:20'),
(478, 5, 2, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:23:30', '2026-04-04 17:24:20'),
(479, 5, 4, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:23:32', '2026-04-04 17:24:20'),
(480, 5, 5, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:23:33', '2026-04-04 17:24:20'),
(481, 5, 6, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:23:36', '2026-04-04 17:24:20'),
(482, 5, 7, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:23:39', '2026-04-04 17:24:20'),
(483, 5, 9, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:23:41', '2026-04-04 17:24:20'),
(484, 5, 10, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:23:44', '2026-04-04 17:24:20'),
(485, 5, 11, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:23:46', '2026-04-04 17:24:20'),
(486, 5, 12, 1, 15, 4, '', 'submitted', NULL, '2026-04-04 17:23:48', '2026-04-04 17:24:20'),
(487, 5, 17, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:23:58', '2026-04-04 17:24:20'),
(488, 5, 21, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:24:00', '2026-04-04 17:24:20'),
(489, 5, 28, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:24:01', '2026-04-04 17:24:20'),
(490, 5, 29, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:24:04', '2026-04-04 17:24:20'),
(491, 5, 31, 1, 15, 4, '', 'submitted', NULL, '2026-04-04 17:24:05', '2026-04-04 17:24:20'),
(492, 5, 32, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:24:07', '2026-04-04 17:24:20'),
(493, 5, 33, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:24:09', '2026-04-04 17:24:20'),
(494, 5, 34, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:24:11', '2026-04-04 17:24:20'),
(495, 5, 35, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:24:12', '2026-04-04 17:24:20'),
(496, 5, 38, 1, 15, 2, '', 'submitted', NULL, '2026-04-04 17:24:13', '2026-04-04 17:24:20'),
(497, 5, 39, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:24:15', '2026-04-04 17:24:20'),
(498, 5, 40, 1, 15, 3, '', 'submitted', NULL, '2026-04-04 17:24:16', '2026-04-04 17:24:20'),
(499, 5, 1, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:01', '2026-04-04 17:25:45'),
(500, 5, 2, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:03', '2026-04-04 17:25:45'),
(501, 5, 4, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:06', '2026-04-04 17:25:45'),
(502, 5, 5, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:08', '2026-04-04 17:25:45'),
(503, 5, 6, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:09', '2026-04-04 17:25:45'),
(504, 5, 7, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:11', '2026-04-04 17:25:45'),
(505, 5, 9, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:13', '2026-04-04 17:25:45'),
(506, 5, 10, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:15', '2026-04-04 17:25:45'),
(507, 5, 11, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:16', '2026-04-04 17:25:45'),
(508, 5, 12, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:18', '2026-04-04 17:25:45'),
(509, 5, 17, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:19', '2026-04-04 17:25:45'),
(510, 5, 21, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:21', '2026-04-04 17:25:45'),
(511, 5, 28, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:23', '2026-04-04 17:25:45'),
(512, 5, 29, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:25', '2026-04-04 17:25:45'),
(513, 5, 31, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:26', '2026-04-04 17:25:45'),
(514, 5, 32, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:28', '2026-04-04 17:25:45'),
(515, 5, 33, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:29', '2026-04-04 17:25:45'),
(516, 5, 34, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:30', '2026-04-04 17:25:45'),
(517, 5, 35, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:32', '2026-04-04 17:25:45'),
(518, 5, 38, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:35', '2026-04-04 17:25:45'),
(519, 5, 39, 1, 2, 2, '', 'submitted', NULL, '2026-04-04 17:25:38', '2026-04-04 17:25:45'),
(520, 5, 40, 1, 2, 3, '', 'submitted', NULL, '2026-04-04 17:25:40', '2026-04-04 17:25:45'),
(521, 5, 1, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:25:58', '2026-04-04 17:26:33'),
(522, 5, 2, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:00', '2026-04-04 17:26:33'),
(523, 5, 4, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:01', '2026-04-04 17:26:33'),
(524, 5, 5, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:03', '2026-04-04 17:26:33'),
(525, 5, 6, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:04', '2026-04-04 17:26:33'),
(526, 5, 7, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:05', '2026-04-04 17:26:33'),
(527, 5, 9, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:07', '2026-04-04 17:26:33'),
(528, 5, 10, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:09', '2026-04-04 17:26:33'),
(529, 5, 11, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:10', '2026-04-04 17:26:33'),
(530, 5, 12, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:11', '2026-04-04 17:26:33'),
(531, 5, 17, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:12', '2026-04-04 17:26:33'),
(532, 5, 21, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:14', '2026-04-04 17:26:33'),
(533, 5, 28, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:16', '2026-04-04 17:26:33'),
(534, 5, 29, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:18', '2026-04-04 17:26:33'),
(535, 5, 31, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:19', '2026-04-04 17:26:33'),
(536, 5, 32, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:20', '2026-04-04 17:26:33'),
(537, 5, 33, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:22', '2026-04-04 17:26:33'),
(538, 5, 34, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:23', '2026-04-04 17:26:33'),
(539, 5, 35, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:25', '2026-04-04 17:26:33'),
(540, 5, 38, 1, 12, 2, '', 'submitted', NULL, '2026-04-04 17:26:26', '2026-04-04 17:26:33'),
(541, 5, 39, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:28', '2026-04-04 17:26:33'),
(542, 5, 40, 1, 12, 3, '', 'submitted', NULL, '2026-04-04 17:26:30', '2026-04-04 17:26:33'),
(543, 5, 1, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:26:49', '2026-04-04 17:27:29'),
(544, 5, 2, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:26:51', '2026-04-04 17:27:29'),
(545, 5, 4, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:26:53', '2026-04-04 17:27:29'),
(546, 5, 5, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:26:55', '2026-04-04 17:27:29'),
(547, 5, 6, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:26:57', '2026-04-04 17:27:29'),
(548, 5, 7, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:26:59', '2026-04-04 17:27:29'),
(549, 5, 9, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:01', '2026-04-04 17:27:29'),
(550, 5, 10, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:03', '2026-04-04 17:27:29'),
(551, 5, 11, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:04', '2026-04-04 17:27:29'),
(552, 5, 12, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:06', '2026-04-04 17:27:29'),
(553, 5, 17, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:07', '2026-04-04 17:27:29'),
(554, 5, 21, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:09', '2026-04-04 17:27:29'),
(555, 5, 28, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:10', '2026-04-04 17:27:29'),
(556, 5, 29, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:12', '2026-04-04 17:27:29'),
(557, 5, 31, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:13', '2026-04-04 17:27:29'),
(558, 5, 32, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:15', '2026-04-04 17:27:29'),
(559, 5, 33, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:16', '2026-04-04 17:27:29'),
(560, 5, 34, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:17', '2026-04-04 17:27:29'),
(561, 5, 35, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:19', '2026-04-04 17:27:29'),
(562, 5, 38, 1, 13, 2, '', 'submitted', NULL, '2026-04-04 17:27:20', '2026-04-04 17:27:29'),
(563, 5, 38, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:22', '2026-04-04 17:27:29'),
(564, 5, 39, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:24', '2026-04-04 17:27:29'),
(565, 5, 40, 1, 13, 3, '', 'submitted', NULL, '2026-04-04 17:27:25', '2026-04-04 17:27:29'),
(566, 5, 1, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:40', '2026-04-04 17:28:19'),
(567, 5, 2, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:27:42', '2026-04-04 17:28:19'),
(568, 5, 4, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:43', '2026-04-04 17:28:19'),
(569, 5, 5, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:44', '2026-04-04 17:28:19'),
(570, 5, 6, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:46', '2026-04-04 17:28:19'),
(571, 5, 7, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:27:47', '2026-04-04 17:28:19'),
(572, 5, 9, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:49', '2026-04-04 17:28:19'),
(573, 5, 10, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:27:50', '2026-04-04 17:28:19'),
(574, 5, 11, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:51', '2026-04-04 17:28:19'),
(575, 5, 12, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:27:53', '2026-04-04 17:28:19'),
(576, 5, 17, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:27:54', '2026-04-04 17:28:19'),
(577, 5, 21, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:27:56', '2026-04-04 17:28:19'),
(578, 5, 28, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:27:58', '2026-04-04 17:28:19'),
(579, 5, 29, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:28:01', '2026-04-04 17:28:19'),
(580, 5, 31, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:28:03', '2026-04-04 17:28:19'),
(581, 5, 32, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:28:06', '2026-04-04 17:28:19'),
(582, 5, 33, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:28:08', '2026-04-04 17:28:19'),
(583, 5, 34, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:28:10', '2026-04-04 17:28:19'),
(584, 5, 35, 1, 14, 2, '', 'submitted', NULL, '2026-04-04 17:28:10', '2026-04-04 17:28:19'),
(585, 5, 38, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:28:13', '2026-04-04 17:28:19'),
(586, 5, 39, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:28:15', '2026-04-04 17:28:19'),
(587, 5, 40, 1, 14, 3, '', 'submitted', NULL, '2026-04-04 17:28:16', '2026-04-04 17:28:19');

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
(17, 5, 1, 4, 15, 'submitted', '2026-04-05 01:24:20', 22),
(18, 5, 1, 4, 2, 'submitted', '2026-04-05 01:25:45', 22),
(19, 5, 1, 4, 12, 'submitted', '2026-04-05 01:26:33', 22),
(20, 5, 1, 4, 13, 'submitted', '2026-04-05 01:27:29', 23),
(21, 5, 1, 4, 14, 'submitted', '2026-04-05 01:28:19', 22);

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
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, '2026-04-05 01:24:57', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, '2026-04-05 01:25:53', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, '2026-04-05 01:26:45', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, '2026-04-05 01:27:37', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, '2026-04-05 01:23:23', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza Evangelio', 'school_head', 'active', 1, '2026-04-05 01:44:09', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0),
(46, 'Charles', '$2y$10$9QWVYCP/gNj9kS9vZ72OpeK8BsICHhNjMndKyzi4ZBxQ00A3Mw1WS', 'mendozacharles11011@gmail.com', 'Charles Patrick Arias', 'sbm_coordinator', 'active', 1, '2026-04-05 02:13:49', '2026-04-01 02:35:08', 0, NULL, NULL, '2026-04-01 10:35:53', 0),
(52, 'Rolito', '$2y$10$YYJHq2543mwFmg2SJ5.75.Vfchwz1xH0zIJY2B8qK2v8oBxqQjpim', 'ariascharles00@gmail.com', 'Jr Billones', 'external_stakeholder', 'active', 1, '2026-04-04 10:11:08', '2026-04-03 14:02:47', 0, NULL, NULL, '2026-04-03 22:27:08', 0);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=354;

--
-- AUTO_INCREMENT for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  MODIFY `snap_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `ann_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cycle_audit_log`
--
ALTER TABLE `cycle_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

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
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

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
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=588;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
