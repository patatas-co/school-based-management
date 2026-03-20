-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2026 at 05:33 PM
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
(94, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 00:35:24'),
(95, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 02:07:11'),
(96, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 02:07:18'),
(97, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 02:47:09'),
(98, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 02:47:18'),
(99, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 02:47:39'),
(100, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 10:07:58'),
(101, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 10:08:16'),
(102, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 10:56:25'),
(103, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 5', '::1', '2026-03-15 10:57:09'),
(104, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 10:57:16'),
(105, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 10:58:34'),
(106, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 10:58:52'),
(107, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:11:59'),
(108, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:12:52'),
(109, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:18:37'),
(110, 1, 'create_user', 'users', 'Created user: Julia', '::1', '2026-03-15 11:19:35'),
(111, 1, 'create_user', 'users', 'Created user: Juan', '::1', '2026-03-15 11:20:09'),
(112, 1, 'create_user', 'users', 'Created user: Justine', '::1', '2026-03-15 11:20:53'),
(113, 1, 'create_user', 'users', 'Created user: Axl', '::1', '2026-03-15 11:21:39'),
(114, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:21:54'),
(115, 14, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:22:14'),
(116, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:22:43'),
(117, 1, 'update_user', 'users', 'Updated user ID: 13', '::1', '2026-03-15 11:22:58'),
(118, 13, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:23:06'),
(119, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:23:29'),
(120, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:23:45'),
(121, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 5', '::1', '2026-03-15 11:24:44'),
(122, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:24:53'),
(123, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 5', '::1', '2026-03-15 11:26:13'),
(124, 13, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:26:21'),
(125, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 5', '::1', '2026-03-15 11:27:06'),
(126, 14, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:27:12'),
(127, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 5', '::1', '2026-03-15 11:28:24'),
(128, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 11:28:30'),
(129, 5, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 5', '::1', '2026-03-15 11:51:52'),
(130, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:11:41'),
(131, 1, 'validate_assessment', 'assessment', 'Validated cycle ID:5', '::1', '2026-03-15 12:11:49'),
(132, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:12:04'),
(133, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:29:16'),
(134, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:29:30'),
(135, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 6', '::1', '2026-03-15 12:31:56'),
(136, 14, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:32:03'),
(137, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 6', '::1', '2026-03-15 12:32:49'),
(138, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:32:55'),
(139, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 6', '::1', '2026-03-15 12:33:34'),
(140, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:33:40'),
(141, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 6', '::1', '2026-03-15 12:34:30'),
(142, 13, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:34:37'),
(143, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 6', '::1', '2026-03-15 12:35:58'),
(144, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 12:36:05'),
(145, 5, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 6', '::1', '2026-03-15 12:37:33'),
(146, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:02:01'),
(147, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:02:27'),
(148, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:02:48'),
(149, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 7', '::1', '2026-03-15 13:04:31'),
(150, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:04:38'),
(151, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 7', '::1', '2026-03-15 13:05:29'),
(152, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:05:38'),
(153, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 7', '::1', '2026-03-15 13:06:24'),
(154, 13, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:06:36'),
(155, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 7', '::1', '2026-03-15 13:07:32'),
(156, 14, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:07:39'),
(157, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 7', '::1', '2026-03-15 13:08:16'),
(158, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 13:08:23'),
(159, 5, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 7', '::1', '2026-03-15 13:09:01'),
(160, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:16:01'),
(161, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:16:50'),
(162, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:17:26'),
(163, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:17:36'),
(164, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:41:16'),
(165, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:42:53'),
(166, 1, 'update_user', 'users', 'Updated user ID: 10', '::1', '2026-03-15 14:43:09'),
(167, 10, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:43:21'),
(168, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:43:39'),
(169, 1, 'validate_assessment', 'view_assessment', 'Validated cycle ID:7', '::1', '2026-03-15 14:44:12'),
(170, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:44:58'),
(171, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:45:44'),
(172, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:46:20'),
(173, 1, 'update_user', 'users', 'Updated user ID: 8', '::1', '2026-03-15 14:46:42'),
(174, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:46:51'),
(175, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-15 16:35:33'),
(176, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-16 12:21:46'),
(177, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-16 13:00:48'),
(178, 12, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 12 submitted for cycle 8', '::1', '2026-03-16 13:06:00'),
(179, 15, 'login', 'auth', 'User logged in', '::1', '2026-03-16 13:06:08'),
(180, 15, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 15 submitted for cycle 8', '::1', '2026-03-16 13:06:47'),
(181, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-16 13:06:53'),
(182, 2, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 2 submitted for cycle 8', '::1', '2026-03-16 13:08:21'),
(183, 14, 'login', 'auth', 'User logged in', '::1', '2026-03-16 13:08:26'),
(184, 14, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 14 submitted for cycle 8', '::1', '2026-03-16 13:09:57'),
(185, 13, 'login', 'auth', 'User logged in', '::1', '2026-03-16 13:10:06'),
(186, 13, 'teacher_submit_assessment', 'teacher_self_assessment', 'Teacher ID 13 submitted for cycle 8', '::1', '2026-03-16 13:10:44'),
(187, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-16 13:10:52'),
(188, 5, 'submit_assessment', 'self_assessment', 'Submitted SBM assessment cycle 8', '::1', '2026-03-16 13:11:34'),
(189, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-16 15:05:21'),
(190, 1, 'validate_assessment', 'view_assessment', 'Validated cycle ID:8', '::1', '2026-03-16 15:05:38'),
(191, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-16 15:05:52'),
(192, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-17 23:09:31'),
(193, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-18 14:34:35'),
(194, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-18 14:52:16'),
(195, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-18 14:52:44'),
(196, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-18 15:56:38'),
(197, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-18 16:36:55'),
(198, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-18 16:37:50'),
(199, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-18 16:38:58'),
(200, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 01:37:37'),
(201, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:19:41'),
(202, 12, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:20:07'),
(203, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:20:16'),
(204, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:30:46'),
(205, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:32:17'),
(206, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:32:48'),
(207, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:34:28'),
(208, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:34:41'),
(209, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 02:34:59'),
(210, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 03:10:15'),
(211, 2, 'login', 'auth', 'User logged in', '::1', '2026-03-20 03:20:02'),
(212, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 03:23:27'),
(213, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 04:42:47'),
(214, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 04:44:02'),
(215, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 04:51:42'),
(216, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 04:55:02'),
(217, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 04:56:20'),
(218, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 05:02:26'),
(219, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 05:03:01'),
(220, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 05:22:45'),
(221, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 05:22:57'),
(222, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 06:25:23'),
(223, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 12:06:48'),
(224, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 12:08:06'),
(225, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 12:34:34'),
(226, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 13:48:33'),
(227, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 14:25:51'),
(228, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 14:27:56'),
(229, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 14:30:13'),
(230, 5, 'login', 'auth', 'User logged in', '::1', '2026-03-20 14:30:47'),
(231, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 14:31:12'),
(232, 1, 'login', 'auth', 'User logged in', '::1', '2026-03-20 16:32:16');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `ann_id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('all','school_head','teacher','sdo','ro','external_stakeholder') DEFAULT 'all',
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
(29, 1, 8, 4, 24, 'Medium', 'Improve performance on indicator 4.2: PTA is organized and actively engaged in school planning and monitoring.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(30, 1, 8, 4, 27, 'Medium', 'Improve performance on indicator 4.5: Stakeholder satisfaction survey is conducted and results are used for improvement.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(31, 1, 8, 4, 23, 'Medium', 'Improve performance on indicator 4.1: School Governance Council (SGC) records are complete, updated, and actions are documented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(32, 1, 8, 1, 8, 'Medium', 'Improve performance on indicator 1.8: TLE/TVL programs have active industry partnerships and produce certified graduates.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(33, 1, 8, 6, 36, 'Medium', 'Improve performance on indicator 6.1: School facilities inventory is updated and submitted on time.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(34, 1, 8, 6, 39, 'Medium', 'Improve performance on indicator 6.4: Library resources are adequate, updated, and accessible to all learners.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(35, 1, 8, 5, 30, 'Medium', 'Improve performance on indicator 5.2: Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(36, 1, 8, 5, 33, 'Medium', 'Improve performance on indicator 5.5: Teacher workload is within prescribed limits and fairly distributed.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(37, 1, 8, 3, 20, 'Medium', 'Improve performance on indicator 3.2: A school-community planning team is established and functional.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(38, 1, 8, 3, 22, 'Medium', 'Improve performance on indicator 3.4: The school head implements innovations in frontline service delivery.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(39, 1, 8, 2, 9, 'Medium', 'Improve performance on indicator 2.1: The school has a zero-bullying policy that is implemented, monitored, and updated regularly.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(40, 1, 8, 2, 10, 'Medium', 'Improve performance on indicator 2.2: Dropout rate is within the national target, with active early warning and intervention systems.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(41, 1, 8, 2, 11, 'Medium', 'Improve performance on indicator 2.3: Out-of-School Youth (OSY) re-entry programs and ALS are actively implemented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(42, 1, 8, 2, 12, 'Medium', 'Improve performance on indicator 2.4: School activities are culture-sensitive, inclusive, and respectful of learner diversity.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49'),
(43, 1, 8, 2, 18, 'Medium', 'Improve performance on indicator 2.10: Learners actively participate in school governance through SSG/SPG and other bodies.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-16 13:11:49');

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
(7, 8, NULL, 2, 5, 0, 1, 4, '[]', 0, NULL, '2026-03-20 05:11:44'),
(8, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-16 13:11:36'),
(10, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-16 13:11:57'),
(11, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:31:52'),
(12, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:31:52'),
(13, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:32:03'),
(14, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:32:03'),
(15, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:32:06'),
(16, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:32:06'),
(18, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-16 13:33:17'),
(19, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:38:00'),
(20, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:38:00'),
(21, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:52:33'),
(22, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:52:33'),
(23, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:52:38'),
(24, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:52:38'),
(25, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:52:40'),
(26, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:52:40'),
(27, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 13:55:32'),
(28, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 13:55:32'),
(29, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:14:05'),
(30, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:14:05'),
(31, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:14:08'),
(32, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:14:08'),
(33, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:15:22'),
(34, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:15:22'),
(35, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:15:25'),
(36, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:15:25'),
(37, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:21:37'),
(38, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:21:37'),
(39, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:24:12'),
(40, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:24:12'),
(41, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:26:05'),
(42, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:26:05'),
(43, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:28:10'),
(44, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:28:10'),
(45, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:29:33'),
(46, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:29:33'),
(47, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:31:33'),
(48, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:31:33'),
(49, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:33:30'),
(50, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:33:30'),
(51, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:34:34'),
(52, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:34:34'),
(53, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:45:06'),
(54, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:45:06'),
(55, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:48:36'),
(56, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:48:36'),
(57, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:50:38'),
(58, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:50:38'),
(59, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:52:02'),
(60, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:52:02'),
(61, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 14:54:15'),
(62, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 14:54:15'),
(63, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-16 15:06:28'),
(64, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-16 15:06:28'),
(66, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-17 23:09:41'),
(67, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-17 23:13:11'),
(68, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-17 23:13:11'),
(70, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-18 14:34:42'),
(71, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-18 14:35:27'),
(72, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-18 14:35:27'),
(73, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-18 15:26:16'),
(74, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-18 15:26:16'),
(75, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-18 15:55:33'),
(76, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-18 15:55:33'),
(77, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 01:38:07'),
(78, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 01:38:07'),
(79, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 02:35:21'),
(80, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 02:35:21'),
(81, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 03:16:48'),
(82, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 03:16:48'),
(83, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 03:35:03'),
(84, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 03:35:03'),
(86, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:35:56'),
(88, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:36:29'),
(90, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:36:34'),
(92, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:36:38'),
(94, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:37:32'),
(96, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:37:58'),
(98, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:40:38'),
(100, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:41:48'),
(102, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:43:15'),
(104, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 03:47:42'),
(105, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 03:51:56'),
(106, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 03:51:56'),
(107, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 03:52:30'),
(108, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 03:52:30'),
(110, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 05:02:39'),
(112, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 05:03:15'),
(114, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 05:06:09'),
(116, 8, NULL, NULL, 5, 0, 1, 4, '[\"bullying\"]', 0, NULL, '2026-03-20 05:11:44'),
(117, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 05:18:01'),
(118, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 05:18:01'),
(119, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 05:20:22'),
(120, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 05:20:22'),
(121, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 05:26:44'),
(122, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 05:26:44'),
(123, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 06:25:37'),
(124, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 06:25:37'),
(125, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 12:35:46'),
(126, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 12:35:46'),
(127, 8, NULL, NULL, 5, 0, 0, 5, '[\"bullying\"]', 0, NULL, '2026-03-20 14:26:09'),
(128, 8, NULL, NULL, 5, 0, 3, 2, '[\"bullying\"]', 0, NULL, '2026-03-20 14:26:09');

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
  `prediction_type` enum('score_forecast','risk_flag','ta_recommendation','maturity_forecast') DEFAULT 'risk_flag',
  `predicted_value` decimal(5,2) DEFAULT NULL,
  `risk_level` enum('low','medium','high') DEFAULT 'low',
  `recommendation` text DEFAULT NULL,
  `confidence_score` decimal(4,3) DEFAULT 0.000,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_recommendations`
--

CREATE TABLE `ml_recommendations` (
  `rec_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `recommendation_text` text NOT NULL,
  `generated_by` varchar(60) DEFAULT 'rule_based',
  `top_topics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_topics`)),
  `has_urgent` tinyint(1) DEFAULT 0,
  `sentiment_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sentiment_summary`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ml_recommendations`
--

INSERT INTO `ml_recommendations` (`rec_id`, `cycle_id`, `recommendation_text`, `generated_by`, `top_topics`, `has_urgent`, `sentiment_summary`, `generated_at`) VALUES
(84, 8, '[Assessment Overview]\nThe Dasmariñas Integrated High School has achieved an overall SBM score of 76.58% with an Advanced maturity level in its first assessment cycle, providing a baseline for future improvements.\n\n[Priority Recommendations]\n1. [2.1] The School Head shall review and update the zero-bullying policy by the end of the first semester, ensuring it is implemented, monitored, and regularly updated, with a target of achieving a rating of 3 (Developing) by the next assessment cycle.\n2. [4.2] The School Head shall convene a meeting with the PTA by the end of the first quarter to discuss their role in school planning and monitoring, aiming to increase their active engagement and achieve a rating of 3 (Developing) by the next assessment cycle.\n3. [5.2] The School Head shall ensure that Learning Action Cells (LAC) sessions are conducted regularly, with at least one session per month, and that outcomes are documented, aiming to achieve a rating of 3 (Developing) by the next assessment cycle.\n4. [3.2] The School Head shall establish a school-community planning team by the end of the first semester, ensuring it is functional and contributes to school planning and development, with a target of achieving a rating of 3 (Developing) by the next assessment cycle.\n5. [6.1] The School Head shall update the school facilities inventory by the end of the first quarter and submit it on time, ensuring that all facilities are accounted for and maintained, aiming to achieve a rating of 3 (Developing) by the next assessment cycle.\n6. [4.5] The School Head shall conduct a stakeholder satisfaction survey by the end of the school year, using the results to inform school improvement plans and achieve a rating of 3 (Developing) by the next assessment cycle, as per DepEd Order No. 007, s. 2024.\n\n[Stakeholder Focus]\nConsidering the stakeholder remarks on bullying, the School Head should also ensure that the updated zero-bullying policy [2.1] is communicated to all stakeholders, including learners, teachers, and parents, to raise awareness and promote a culture of respect and inclusivity, as emphasized in DepEd Order No. 007, s. 2024.', 'groq', '[\"bullying\"]', 0, '{\"negative\":3,\"neutral\":2,\"positive\":0}', '2026-03-20 14:26:09');

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
(1, 1, 8, '[{\"dimension_name\":\"Human Resource Development\",\"dimension_no\":5,\"gap_from_avg\":3.01,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":73.57,\"weight\":0.9,\"weighted_gap\":2.71},{\"dimension_name\":\"Accountability and Continuous Improvement\",\"dimension_no\":4,\"gap_from_avg\":2.41,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":74.17,\"weight\":1,\"weighted_gap\":2.41},{\"dimension_name\":\"Learning Environment\",\"dimension_no\":2,\"gap_from_avg\":1.58,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":75,\"weight\":1.2,\"weighted_gap\":1.89},{\"dimension_name\":\"Leadership and Governance\",\"dimension_no\":3,\"gap_from_avg\":1.58,\"maturity\":\"Maturing\",\"priority\":\"low\",\"score\":75,\"weight\":1,\"weighted_gap\":1.58},{\"dimension_name\":\"Finance and Resource Management\",\"dimension_no\":6,\"gap_from_avg\":-4.13,\"maturity\":\"Advanced\",\"priority\":\"low\",\"score\":80.71,\"weight\":0.9,\"weighted_gap\":-3.72},{\"dimension_name\":\"Curriculum and Teaching\",\"dimension_no\":1,\"gap_from_avg\":-4.05,\"maturity\":\"Advanced\",\"priority\":\"low\",\"score\":80.63,\"weight\":1.2,\"weighted_gap\":-4.86}]', '[]', 76.67, 'Advanced', '2026-03-20 14:26:09');

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
(8, 1, 1, 'validated', 76.67, 'Advanced', '2026-03-16 21:00:53', '2026-03-16 21:11:34', 1, '2026-03-16 23:05:38', '', '2026-03-16 13:00:53');

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
(188, 8, 1, 1, 25.80, 32.00, 80.63, '2026-03-16 13:11:34'),
(191, 8, 1, 2, 30.00, 40.00, 75.00, '2026-03-16 13:11:34'),
(195, 8, 1, 4, 17.80, 24.00, 74.17, '2026-03-16 13:11:34'),
(200, 8, 1, 6, 22.60, 28.00, 80.71, '2026-03-16 13:11:34'),
(204, 8, 1, 3, 12.00, 16.00, 75.00, '2026-03-16 13:11:34'),
(206, 8, 1, 5, 20.60, 28.00, 73.57, '2026-03-16 13:11:34');

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
(179, 8, 1, 1, 4, '', NULL, 5, '2026-03-16 13:11:08'),
(180, 8, 2, 1, 3, '', NULL, 5, '2026-03-16 13:11:09'),
(181, 8, 3, 1, 3, '', NULL, 5, '2026-03-16 13:11:10'),
(182, 8, 13, 1, 4, '', NULL, 5, '2026-03-16 13:11:20'),
(183, 8, 14, 1, 3, '', NULL, 5, '2026-03-16 13:11:21'),
(184, 8, 16, 1, 4, '', NULL, 5, '2026-03-16 13:11:23'),
(185, 8, 17, 1, 3, '', NULL, 5, '2026-03-16 13:11:23'),
(186, 8, 24, 1, 2, '', NULL, 5, '2026-03-16 13:11:25'),
(187, 8, 25, 1, 4, '', NULL, 5, '2026-03-16 13:11:26'),
(188, 8, 26, 1, 3, '', NULL, 5, '2026-03-16 13:11:27'),
(189, 8, 27, 1, 2, '', NULL, 5, '2026-03-16 13:11:28'),
(190, 8, 28, 1, 4, '', NULL, 5, '2026-03-16 13:11:29'),
(191, 8, 41, 1, 4, '', NULL, 5, '2026-03-16 13:11:30'),
(192, 8, 42, 1, 3, '', NULL, 5, '2026-03-16 13:11:31');

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
(1, 1, 1, 'Self-Assessment', 'School Head and stakeholders accomplish the 42-indicator SBM checklist during the 4th Grading Period using the 4 Degrees of Manifestation.', '2025-03-24', '2025-04-04', 0, '2026-03-12 11:53:47'),
(2, 1, 2, 'Planning Integration', 'During summer vacation, the school integrates SBM results into the School Improvement Plan (SIP). Priority dimensions guide resource allocation.', '2025-04-07', '2025-05-30', 0, '2026-03-12 11:53:47'),
(3, 1, 3, 'Implementation', 'From 1st to 3rd Grading of the succeeding SY, the school implements planned interventions. SDO conducts quarterly monitoring and TA visits.', '2025-08-01', '2026-03-21', 1, '2026-03-12 11:53:47');

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
(1, '2024-2025', 1, '2024-06-03', '2025-04-04'),
(2, '2025-2026', 0, '2025-06-20', '2026-04-22');

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
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `config_key` varchar(80) NOT NULL,
  `config_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`config_key`, `config_value`, `updated_at`) VALUES
('deped_order', 'No. 007, s. 2024', '2026-03-20 16:29:13'),
('school_id', '1', '2026-03-20 16:29:13'),
('school_mode', 'single', '2026-03-20 16:29:13'),
('school_name', 'Dasmariñas Integrated High School', '2026-03-20 16:29:13');

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
(556, 8, 4, 1, 12, 3, '', 'submitted', '2026-03-16 13:00:53', '2026-03-16 13:06:00'),
(557, 8, 5, 1, 12, 4, '', 'submitted', '2026-03-16 13:00:55', '2026-03-16 13:06:00'),
(558, 8, 6, 1, 12, 3, '', 'submitted', '2026-03-16 13:00:56', '2026-03-16 13:06:00'),
(559, 8, 7, 1, 12, 4, '', 'submitted', '2026-03-16 13:00:57', '2026-03-16 13:06:00'),
(560, 8, 8, 1, 12, 2, '', 'submitted', '2026-03-16 13:00:59', '2026-03-16 13:06:00'),
(561, 8, 9, 1, 12, 2, 'still not implemented but we\'re currently working on it', 'submitted', '2026-03-16 13:01:23', '2026-03-16 13:06:00'),
(562, 8, 10, 1, 12, 3, 'there\'s many case of dropouts and we\'re trying to fix and avoid the problems when it comes to institution', 'submitted', '2026-03-16 13:05:31', '2026-03-16 13:06:00'),
(564, 8, 11, 1, 12, 1, '', 'submitted', '2026-03-16 13:05:33', '2026-03-16 13:06:00'),
(565, 8, 12, 1, 12, 3, '', 'submitted', '2026-03-16 13:05:34', '2026-03-16 13:06:00'),
(566, 8, 15, 1, 12, 4, '', 'submitted', '2026-03-16 13:05:35', '2026-03-16 13:06:00'),
(567, 8, 18, 1, 12, 2, '', 'submitted', '2026-03-16 13:05:36', '2026-03-16 13:06:00'),
(568, 8, 19, 1, 12, 3, '', 'submitted', '2026-03-16 13:05:38', '2026-03-16 13:06:00'),
(569, 8, 20, 1, 12, 1, '', 'submitted', '2026-03-16 13:05:39', '2026-03-16 13:06:00'),
(570, 8, 21, 1, 12, 4, '', 'submitted', '2026-03-16 13:05:40', '2026-03-16 13:06:00'),
(571, 8, 22, 1, 12, 3, '', 'submitted', '2026-03-16 13:05:41', '2026-03-16 13:06:00'),
(572, 8, 23, 1, 12, 2, '', 'submitted', '2026-03-16 13:05:43', '2026-03-16 13:06:00'),
(573, 8, 29, 1, 12, 4, '', 'submitted', '2026-03-16 13:05:44', '2026-03-16 13:06:00'),
(574, 8, 30, 1, 12, 2, '', 'submitted', '2026-03-16 13:05:45', '2026-03-16 13:06:00'),
(575, 8, 31, 1, 12, 3, '', 'submitted', '2026-03-16 13:05:47', '2026-03-16 13:06:00'),
(576, 8, 32, 1, 12, 2, '', 'submitted', '2026-03-16 13:05:48', '2026-03-16 13:06:00'),
(577, 8, 33, 1, 12, 4, '', 'submitted', '2026-03-16 13:05:49', '2026-03-16 13:06:00'),
(578, 8, 34, 1, 12, 2, '', 'submitted', '2026-03-16 13:05:50', '2026-03-16 13:06:00'),
(579, 8, 35, 1, 12, 4, '', 'submitted', '2026-03-16 13:05:51', '2026-03-16 13:06:00'),
(580, 8, 36, 1, 12, 2, '', 'submitted', '2026-03-16 13:05:52', '2026-03-16 13:06:00'),
(581, 8, 37, 1, 12, 1, '', 'submitted', '2026-03-16 13:05:53', '2026-03-16 13:06:00'),
(582, 8, 38, 1, 12, 4, '', 'submitted', '2026-03-16 13:05:55', '2026-03-16 13:06:00'),
(583, 8, 39, 1, 12, 3, '', 'submitted', '2026-03-16 13:05:57', '2026-03-16 13:06:00'),
(584, 8, 40, 1, 12, 3, '', 'submitted', '2026-03-16 13:05:57', '2026-03-16 13:06:00'),
(585, 8, 4, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:13', '2026-03-16 13:06:47'),
(586, 8, 5, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:14', '2026-03-16 13:06:47'),
(587, 8, 6, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:15', '2026-03-16 13:06:47'),
(588, 8, 7, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:17', '2026-03-16 13:06:47'),
(589, 8, 8, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:18', '2026-03-16 13:06:47'),
(590, 8, 9, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:19', '2026-03-16 13:06:47'),
(591, 8, 10, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:21', '2026-03-16 13:06:47'),
(592, 8, 11, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:22', '2026-03-16 13:06:47'),
(593, 8, 12, 1, 15, 1, '', 'submitted', '2026-03-16 13:06:23', '2026-03-16 13:06:47'),
(594, 8, 15, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:25', '2026-03-16 13:06:47'),
(595, 8, 18, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:25', '2026-03-16 13:06:47'),
(596, 8, 19, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:27', '2026-03-16 13:06:47'),
(597, 8, 20, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:28', '2026-03-16 13:06:47'),
(598, 8, 21, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:29', '2026-03-16 13:06:47'),
(599, 8, 22, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:30', '2026-03-16 13:06:47'),
(600, 8, 23, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:31', '2026-03-16 13:06:47'),
(601, 8, 29, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:32', '2026-03-16 13:06:47'),
(602, 8, 30, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:33', '2026-03-16 13:06:47'),
(603, 8, 31, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:34', '2026-03-16 13:06:47'),
(604, 8, 32, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:35', '2026-03-16 13:06:47'),
(605, 8, 33, 1, 15, 1, '', 'submitted', '2026-03-16 13:06:37', '2026-03-16 13:06:47'),
(606, 8, 34, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:38', '2026-03-16 13:06:47'),
(607, 8, 35, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:39', '2026-03-16 13:06:47'),
(608, 8, 36, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:40', '2026-03-16 13:06:47'),
(609, 8, 37, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:41', '2026-03-16 13:06:47'),
(610, 8, 38, 1, 15, 3, '', 'submitted', '2026-03-16 13:06:43', '2026-03-16 13:06:47'),
(611, 8, 39, 1, 15, 2, '', 'submitted', '2026-03-16 13:06:44', '2026-03-16 13:06:47'),
(612, 8, 40, 1, 15, 4, '', 'submitted', '2026-03-16 13:06:45', '2026-03-16 13:06:47'),
(613, 8, 4, 1, 2, 4, '', 'submitted', '2026-03-16 13:06:56', '2026-03-16 13:08:21'),
(614, 8, 5, 1, 2, 3, '', 'submitted', '2026-03-16 13:06:56', '2026-03-16 13:08:21'),
(615, 8, 6, 1, 2, 2, '', 'submitted', '2026-03-16 13:06:58', '2026-03-16 13:08:21'),
(616, 8, 7, 1, 2, 4, '', 'submitted', '2026-03-16 13:06:59', '2026-03-16 13:08:21'),
(617, 8, 8, 1, 2, 4, '', 'submitted', '2026-03-16 13:07:01', '2026-03-16 13:08:21'),
(618, 8, 9, 1, 2, 2, 'the school policy is under development and we\'re trying to avoid and maalis yung case of bullying', 'submitted', '2026-03-16 13:07:02', '2026-03-16 13:08:21'),
(620, 8, 10, 1, 2, 2, 'sinusubukan namin na mapababa ang case ng dropouts', 'submitted', '2026-03-16 13:07:37', '2026-03-16 13:08:21'),
(623, 8, 11, 1, 2, 2, '', 'submitted', '2026-03-16 13:07:57', '2026-03-16 13:08:21'),
(624, 8, 12, 1, 2, 4, '', 'submitted', '2026-03-16 13:07:58', '2026-03-16 13:08:21'),
(625, 8, 15, 1, 2, 3, '', 'submitted', '2026-03-16 13:07:59', '2026-03-16 13:08:21'),
(626, 8, 18, 1, 2, 2, '', 'submitted', '2026-03-16 13:08:00', '2026-03-16 13:08:21'),
(627, 8, 19, 1, 2, 4, '', 'submitted', '2026-03-16 13:08:02', '2026-03-16 13:08:21'),
(628, 8, 20, 1, 2, 2, '', 'submitted', '2026-03-16 13:08:02', '2026-03-16 13:08:21'),
(629, 8, 21, 1, 2, 2, '', 'submitted', '2026-03-16 13:08:04', '2026-03-16 13:08:21'),
(630, 8, 22, 1, 2, 3, '', 'submitted', '2026-03-16 13:08:04', '2026-03-16 13:08:21'),
(631, 8, 23, 1, 2, 2, '', 'submitted', '2026-03-16 13:08:06', '2026-03-16 13:08:21'),
(632, 8, 29, 1, 2, 4, '', 'submitted', '2026-03-16 13:08:07', '2026-03-16 13:08:21'),
(633, 8, 30, 1, 2, 1, '', 'submitted', '2026-03-16 13:08:08', '2026-03-16 13:08:21'),
(634, 8, 31, 1, 2, 3, '', 'submitted', '2026-03-16 13:08:10', '2026-03-16 13:08:21'),
(635, 8, 32, 1, 2, 3, '', 'submitted', '2026-03-16 13:08:10', '2026-03-16 13:08:21'),
(636, 8, 33, 1, 2, 1, '', 'submitted', '2026-03-16 13:08:12', '2026-03-16 13:08:21'),
(637, 8, 34, 1, 2, 4, '', 'submitted', '2026-03-16 13:08:13', '2026-03-16 13:08:21'),
(638, 8, 35, 1, 2, 3, '', 'submitted', '2026-03-16 13:08:13', '2026-03-16 13:08:21'),
(639, 8, 36, 1, 2, 4, '', 'submitted', '2026-03-16 13:08:15', '2026-03-16 13:08:21'),
(640, 8, 37, 1, 2, 3, '', 'submitted', '2026-03-16 13:08:15', '2026-03-16 13:08:21'),
(641, 8, 38, 1, 2, 4, '', 'submitted', '2026-03-16 13:08:17', '2026-03-16 13:08:21'),
(642, 8, 39, 1, 2, 2, '', 'submitted', '2026-03-16 13:08:17', '2026-03-16 13:08:21'),
(643, 8, 40, 1, 2, 4, '', 'submitted', '2026-03-16 13:08:19', '2026-03-16 13:08:21'),
(644, 8, 4, 1, 14, 4, '', 'submitted', '2026-03-16 13:08:48', '2026-03-16 13:09:57'),
(645, 8, 5, 1, 14, 2, '', 'submitted', '2026-03-16 13:08:49', '2026-03-16 13:09:57'),
(646, 8, 6, 1, 14, 4, '', 'submitted', '2026-03-16 13:08:50', '2026-03-16 13:09:57'),
(647, 8, 7, 1, 14, 3, '', 'submitted', '2026-03-16 13:08:52', '2026-03-16 13:09:57'),
(648, 8, 8, 1, 14, 3, '', 'submitted', '2026-03-16 13:08:53', '2026-03-16 13:09:57'),
(649, 8, 9, 1, 14, 2, 'we\'re working on it and base sa school year ngayon, napapababa na ang case of bully', 'submitted', '2026-03-16 13:08:54', '2026-03-16 13:09:57'),
(651, 8, 10, 1, 14, 2, '', 'submitted', '2026-03-16 13:09:28', '2026-03-16 13:09:57'),
(653, 8, 11, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:30', '2026-03-16 13:09:57'),
(654, 8, 12, 1, 14, 3, '', 'submitted', '2026-03-16 13:09:31', '2026-03-16 13:09:57'),
(655, 8, 15, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:32', '2026-03-16 13:09:57'),
(656, 8, 18, 1, 14, 2, '', 'submitted', '2026-03-16 13:09:33', '2026-03-16 13:09:57'),
(657, 8, 19, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:34', '2026-03-16 13:09:57'),
(658, 8, 20, 1, 14, 2, '', 'submitted', '2026-03-16 13:09:35', '2026-03-16 13:09:57'),
(659, 8, 21, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:36', '2026-03-16 13:09:57'),
(660, 8, 22, 1, 14, 3, '', 'submitted', '2026-03-16 13:09:37', '2026-03-16 13:09:57'),
(661, 8, 23, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:39', '2026-03-16 13:09:57'),
(662, 8, 29, 1, 14, 3, '', 'submitted', '2026-03-16 13:09:40', '2026-03-16 13:09:57'),
(663, 8, 30, 1, 14, 2, '', 'submitted', '2026-03-16 13:09:41', '2026-03-16 13:09:57'),
(664, 8, 31, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:43', '2026-03-16 13:09:57'),
(665, 8, 32, 1, 14, 2, '', 'submitted', '2026-03-16 13:09:44', '2026-03-16 13:09:57'),
(666, 8, 33, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:45', '2026-03-16 13:09:57'),
(667, 8, 34, 1, 14, 3, '', 'submitted', '2026-03-16 13:09:46', '2026-03-16 13:09:57'),
(668, 8, 35, 1, 14, 2, '', 'submitted', '2026-03-16 13:09:47', '2026-03-16 13:09:57'),
(669, 8, 36, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:48', '2026-03-16 13:09:57'),
(670, 8, 37, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:50', '2026-03-16 13:09:57'),
(672, 8, 38, 1, 14, 3, '', 'submitted', '2026-03-16 13:09:52', '2026-03-16 13:09:57'),
(674, 8, 39, 1, 14, 3, '', 'submitted', '2026-03-16 13:09:54', '2026-03-16 13:09:57'),
(675, 8, 40, 1, 14, 4, '', 'submitted', '2026-03-16 13:09:55', '2026-03-16 13:09:57'),
(676, 8, 4, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:09', '2026-03-16 13:10:44'),
(677, 8, 5, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:10', '2026-03-16 13:10:44'),
(678, 8, 6, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:11', '2026-03-16 13:10:44'),
(679, 8, 7, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:12', '2026-03-16 13:10:44'),
(680, 8, 8, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:14', '2026-03-16 13:10:44'),
(681, 8, 9, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:15', '2026-03-16 13:10:44'),
(682, 8, 10, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:16', '2026-03-16 13:10:44'),
(683, 8, 11, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:17', '2026-03-16 13:10:44'),
(684, 8, 12, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:20', '2026-03-16 13:10:44'),
(685, 8, 15, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:21', '2026-03-16 13:10:44'),
(686, 8, 18, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:22', '2026-03-16 13:10:44'),
(687, 8, 19, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:25', '2026-03-16 13:10:44'),
(688, 8, 20, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:26', '2026-03-16 13:10:44'),
(689, 8, 21, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:27', '2026-03-16 13:10:44'),
(690, 8, 22, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:28', '2026-03-16 13:10:44'),
(691, 8, 23, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:30', '2026-03-16 13:10:44'),
(692, 8, 29, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:31', '2026-03-16 13:10:44'),
(693, 8, 30, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:32', '2026-03-16 13:10:44'),
(694, 8, 31, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:33', '2026-03-16 13:10:44'),
(695, 8, 32, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:34', '2026-03-16 13:10:44'),
(696, 8, 33, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:35', '2026-03-16 13:10:44'),
(697, 8, 34, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:35', '2026-03-16 13:10:44'),
(698, 8, 35, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:37', '2026-03-16 13:10:44'),
(699, 8, 36, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:38', '2026-03-16 13:10:44'),
(700, 8, 37, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:39', '2026-03-16 13:10:44'),
(701, 8, 38, 1, 13, 3, '', 'submitted', '2026-03-16 13:10:40', '2026-03-16 13:10:44'),
(702, 8, 39, 1, 13, 2, '', 'submitted', '2026-03-16 13:10:41', '2026-03-16 13:10:44'),
(703, 8, 40, 1, 13, 4, '', 'submitted', '2026-03-16 13:10:42', '2026-03-16 13:10:44');

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
(16, 8, 1, 1, 12, 'submitted', '2026-03-16 21:06:00', 28),
(17, 8, 1, 1, 15, 'submitted', '2026-03-16 21:06:47', 28),
(18, 8, 1, 1, 2, 'submitted', '2026-03-16 21:08:21', 28),
(19, 8, 1, 1, 14, 'submitted', '2026-03-16 21:09:57', 28),
(20, 8, 1, 1, 13, 'submitted', '2026-03-16 21:10:44', 28);

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
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sbm.edu.ph', 'System Administrator', 'admin', 'active', NULL, '2026-03-21 00:32:16', '2026-03-11 16:18:35'),
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, '2026-03-20 11:20:02', '2026-03-11 16:31:59'),
(5, 'Ryza E.', '$2y$10$uNsxRtmZILkMBaV3EfXdtuIfTTvSp0ZCctNKLtjeoZ9N9MNEjvrV6', 'rmevangelio@dihs.edu.ph', 'Ryza Evangelio', 'school_head', 'active', 1, '2026-03-20 22:30:47', '2026-03-11 16:35:49'),
(8, 'Rolito Billones', '$2y$10$vE5eBX3jCDELcBxYFpzEyu2xZI7j4WmKBisnmALaEJoBlauG1wMby', 'rbillones@dihs.edu.ph', 'Rolito Villones', 'sdo', 'active', 1, '2026-03-15 22:46:51', '2026-03-11 17:49:46'),
(10, 'Charles', '$2y$10$QAAo3OtJ1AEEj3tltB3hteEmz6xYbZNL19jeADIS2dLHg26vTe/Je', 'cpmarias@dihs.edu.com', 'Charles Patrick Arias', 'ro', 'active', 1, '2026-03-15 22:43:21', '2026-03-11 17:52:19'),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, '2026-03-20 10:20:07', '2026-03-15 11:19:35'),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, '2026-03-16 21:10:06', '2026-03-15 11:20:09'),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, '2026-03-16 21:08:26', '2026-03-15 11:20:53'),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, '2026-03-16 21:06:08', '2026-03-15 11:21:39');

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
(7, 1, 1, 1, NULL, 'self_assessment', 'pending', '2025-04-04', NULL, NULL, NULL, '2026-03-18 16:38:40'),
(8, 1, 1, 2, NULL, 'planning', 'pending', '2025-05-30', NULL, NULL, NULL, '2026-03-18 16:38:40'),
(9, 1, 1, 3, 1, 'q1_monitoring', 'pending', '2025-10-17', NULL, NULL, NULL, '2026-03-18 16:38:40'),
(10, 1, 1, 3, 2, 'q2_monitoring', 'pending', '2026-01-02', NULL, NULL, NULL, '2026-03-18 16:38:40'),
(11, 1, 1, 3, 3, 'q3_monitoring', 'pending', '2026-03-21', NULL, NULL, NULL, '2026-03-18 16:38:40'),
(12, 1, 1, 3, NULL, 'completion', 'pending', '2026-03-21', NULL, NULL, NULL, '2026-03-18 16:38:40');

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
-- Indexes for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  ADD PRIMARY KEY (`analysis_id`),
  ADD UNIQUE KEY `uq_cycle_dim` (`cycle_id`,`dimension_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `indicator_id` (`indicator_id`);

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
  ADD UNIQUE KEY `uq_snapshot` (`school_id`,`cycle_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`);

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
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_key`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

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
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  MODIFY `analysis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `region_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `sbm_workflow_phases`
--
ALTER TABLE `sbm_workflow_phases`
  MODIFY `phase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=704;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `technical_assistance`
--
ALTER TABLE `technical_assistance`
  MODIFY `ta_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  MODIFY `cp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Constraints for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  ADD CONSTRAINT `mlca_cycle_fk` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD CONSTRAINT `ml_predictions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_predictions_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_predictions_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ml_predictions_ibfk_4` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE SET NULL;

--
-- Constraints for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  ADD CONSTRAINT `ml_recommendations_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  ADD CONSTRAINT `ml_snapshots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ml_snapshots_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE;

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
