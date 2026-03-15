-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2026 at 04:57 PM
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
(174, 8, 'login', 'auth', 'User logged in', '::1', '2026-03-15 14:46:51');

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
(8, 1, 7, 1, 3, 'Medium', 'Improve performance on indicator 1.3: Learner proficiency rate in Grade 10 meets or exceeds the national target.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 13:09:13'),
(9, 1, 7, 2, 14, 'Medium', 'Improve performance on indicator 2.6: A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 13:09:13'),
(10, 1, 7, 2, 17, 'Medium', 'Improve performance on indicator 2.9: Safe school environment audit is conducted and findings are addressed.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 13:09:13'),
(11, 1, 7, 4, 25, 'Medium', 'Improve performance on indicator 4.3: Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 13:09:13'),
(12, 1, 7, 4, 27, 'Medium', 'Improve performance on indicator 4.5: Stakeholder satisfaction survey is conducted and results are used for improvement.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 13:09:13'),
(13, 1, 7, 6, 41, 'Medium', 'Improve performance on indicator 6.6: MOOE utilization rate reaches 100% with proper documentation.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 13:09:13'),
(14, 1, 7, 2, 9, 'High', 'Improve performance on indicator 2.1: The school has a zero-bullying policy that is implemented, monitored, and updated regularly.', 'Develop targeted interventions to address areas rated \'Not Yet Manifested\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(15, 1, 7, 4, 23, 'Medium', 'Improve performance on indicator 4.1: School Governance Council (SGC) records are complete, updated, and actions are documented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(16, 1, 7, 1, 5, 'Medium', 'Improve performance on indicator 1.5: Results of NAT/PEPT/ALS A&E are analyzed and used to improve instructional programs.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(17, 1, 7, 6, 37, 'Medium', 'Improve performance on indicator 6.2: Infrastructure maintenance plan is implemented and documented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(18, 1, 7, 6, 40, 'Medium', 'Improve performance on indicator 6.5: Laboratory equipment is functional, adequate, and used for instruction.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(19, 1, 7, 5, 29, 'Medium', 'Improve performance on indicator 5.1: All teaching and non-teaching personnel accomplish IPCR/OPCR on time.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(20, 1, 7, 5, 31, 'Medium', 'Improve performance on indicator 5.3: Teachers participate in professional development activities (trainings, seminars, scholarships).', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(21, 1, 7, 5, 32, 'Medium', 'Improve performance on indicator 5.4: Employee recognition programs are implemented to motivate and reward outstanding performance.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(22, 1, 7, 5, 34, 'Medium', 'Improve performance on indicator 5.6: HR development programs for non-teaching staff are implemented.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(23, 1, 7, 5, 35, 'Medium', 'Improve performance on indicator 5.7: Succession planning and talent management practices are in place.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(24, 1, 7, 3, 20, 'Medium', 'Improve performance on indicator 3.2: A school-community planning team is established and functional.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(25, 1, 7, 3, 22, 'Medium', 'Improve performance on indicator 3.4: The school head implements innovations in frontline service delivery.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(26, 1, 7, 2, 10, 'Medium', 'Improve performance on indicator 2.2: Dropout rate is within the national target, with active early warning and intervention systems.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(27, 1, 7, 2, 15, 'Medium', 'Improve performance on indicator 2.7: Mental wellness programs for learners are implemented and monitored.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16'),
(28, 1, 7, 2, 18, 'Medium', 'Improve performance on indicator 2.10: Learners actively participate in school governance through SSG/SPG and other bodies.', 'Develop targeted interventions to address areas rated \'Emerging\'. Identify root causes, allocate resources, and monitor progress.', NULL, NULL, NULL, NULL, 'planned', NULL, 5, '2026-03-15 14:29:16');

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
(20, 7, 'SCHOOL IMPROVEMENT PLAN RECOMMENDATIONS\nSchool: Dasmariñas Integrated High School | SY: 2024-2025\nOverall SBM Score: 71.29% | Maturity Level: Maturing\n────────────────────────────────────────────────────────────\n\n📊 ASSESSMENT OVERVIEW\nTotal Indicators Rated: 42\n  ▪ Not Yet Manifested (1): 1 indicator(s) — Requires immediate action\n  ▪ Emerging (2):           20 indicator(s) — Needs focused intervention\n  ▪ Developing (3):         16 indicator(s) — Continue and strengthen\n  ▪ Always Manifested (4):  5 indicator(s) — Sustain and document\n\n📝 STAKEHOLDER REMARKS SUMMARY\nA total of 4 remarks were collected for this assessment cycle, coming from 4 teachers.\nThe overall tone of the feedback was mostly neutral or observational in nature. Of the 4 remarks, 0 were positive, 1 raised concerns, and 3 were neutral or descriptive.\nThe most frequently mentioned topic in the feedback was Bullying.\n\nConcerns raised:\n  • (Teacher [Learning Environment]): \"none, but according to the last meeting it will be conduct later this year\"\n\n🔴 PRIORITY 1 — NOT YET MANIFESTED (Immediate Action Required)\nThese 1 indicator(s) have not been demonstrated and need urgent attention:\n\n  📌 Learning Environment:\n     [2.1] The school has a zero-bullying policy that is implemented, monitored, and updated regularly.\n     → RECOMMENDED ACTION: Establish a baseline program immediately. Assign a point person,\n       set a 30-day implementation target, and document all initial steps taken.\n\n🟡 PRIORITY 2 — EMERGING (Focused Intervention Needed)\nThese 20 indicator(s) show early signs but need structured support:\n\n  📌 Curriculum and Teaching:\n     [1.3] Learner proficiency rate in Grade 10 meets or exceeds the national target.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [1.5] Results of NAT/PEPT/ALS A&E are analyzed and used to improve instructional programs.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Learning Environment:\n     [2.6] A Disaster Risk Reduction and Management (DRRM) plan is formulated, practiced, and updated.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [2.9] Safe school environment audit is conducted and findings are addressed.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [2.2] Dropout rate is within the national target, with active early warning and intervention systems.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [2.10] Learners actively participate in school governance through SSG/SPG and other bodies.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [2.7] Mental wellness programs for learners are implemented and monitored.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Accountability and Continuous Improvement:\n     [4.3] Stakeholder partnerships (LGU, NGO, alumni, private sector) are documented and active.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [4.5] Stakeholder satisfaction survey is conducted and results are used for improvement.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [4.1] School Governance Council (SGC) records are complete, updated, and actions are documented.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Finance and Resource Management:\n     [6.6] MOOE utilization rate reaches 100% with proper documentation.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [6.2] Infrastructure maintenance plan is implemented and documented.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [6.5] Laboratory equipment is functional, adequate, and used for instruction.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Leadership and Governance:\n     [3.4] The school head implements innovations in frontline service delivery.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [3.2] A school-community planning team is established and functional.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n  📌 Human Resource Development:\n     [5.1] All teaching and non-teaching personnel accomplish IPCR/OPCR on time.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [5.3] Teachers participate in professional development activities (trainings, seminars, scholarships).\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [5.4] Employee recognition programs are implemented to motivate and reward outstanding performance.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [5.7] Succession planning and talent management practices are in place.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n     [5.6] HR development programs for non-teaching staff are implemented.\n     → RECOMMENDED ACTION: Develop a structured action plan with clear milestones.\n       Conduct LAC sessions, identify resource gaps, and monitor progress quarterly.\n\n🔵 PRIORITY 3 — DEVELOPING (Continue & Strengthen)\nThese 16 indicator(s) show good progress and should be maintained:\n\n  📌 Curriculum and Teaching:\n     [1.2] Learner proficiency rate in Grade 6 meets or exceeds the national target.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.4] Learner proficiency rate in Grade 12 or ALS completion rate meets or exceeds the national target.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.8] TLE/TVL programs have active industry partnerships and produce certified graduates.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.7] Remediation, enhancement, and intervention programs are implemented for at-risk learners.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [1.6] Contextualized and localized learning materials are developed and used by teachers.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Accountability and Continuous Improvement:\n     [4.4] Monitoring and evaluation of school programs is conducted regularly with documented results.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Finance and Resource Management:\n     [6.7] Liquidation reports are submitted on time and complete.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.4] Library resources are adequate, updated, and accessible to all learners.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.1] School facilities inventory is updated and submitted on time.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [6.3] Water, electricity, and internet utilities are functional and adequate.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Learning Environment:\n     [2.3] Out-of-School Youth (OSY) re-entry programs and ALS are actively implemented.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [2.4] School activities are culture-sensitive, inclusive, and respectful of learner diversity.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Leadership and Governance:\n     [3.3] SSG/SPG is organized, trained, and actively implements programs.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [3.1] The School Improvement Plan (SIP) is developed collaboratively with all stakeholders and implemented.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n  📌 Human Resource Development:\n     [5.2] Learning Action Cells (LAC) sessions are conducted regularly with documented outcomes.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n     [5.5] Teacher workload is within prescribed limits and fairly distributed.\n     → RECOMMENDED ACTION: Scale current practices. Document best practices,\n       share with other schools, and target transition to \'Always Manifested\' next cycle.\n\n🟢 SUSTAINED PRACTICES — ALWAYS MANIFESTED\nThese 5 indicator(s) are consistently implemented — keep it up:\n\n  📌 Curriculum and Teaching:\n     [1.1] Learner proficiency rate in Grade 3 (Literacy and Numeracy) meets or exceeds the national target.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n  📌 Learning Environment:\n     [2.5] The Child Protection Committee (CPC) is organized, functional, and conducts regular activities.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n     [2.8] School facilities are accessible for learners with disabilities (SPED/PWD compliance).\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n  📌 Accountability and Continuous Improvement:\n     [4.2] PTA is organized and actively engaged in school planning and monitoring.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n     [4.6] Transparency board and public financial disclosures are updated and accessible.\n     → Continue current practices. Document these as best practices in the SIP.\n       Consider sharing these with neighboring schools as models.\n\n📐 DIMENSION-LEVEL PRIORITY ACTIONS\n\n  Finance and Resource Management (67.86% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: 3.7%.\n\n  Learning Environment (68% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: 3.6%.\n\n  Accountability and Continuous Improvement (74.17% — Maturing):\n  → Good progress noted. Focus on the remaining weak indicators to reach the\n    Advanced level. Current gap from average: -2.6%.\n\n💬 RECOMMENDATIONS FROM STAKEHOLDER REMARKS\n\n  [Bullying]\n  → Strengthen the anti-bullying program. Ensure the Child Protection Committee (CPC) is active, conducts quarterly sessions, and all incidents are documented and resolved.\n\n────────────────────────────────────────────────────────────\nNOTE: These recommendations are generated based on the SBM self-assessment data\nsubmitted by Dasmariñas Integrated High School for SY 2024-2025. All action plans should be\nintegrated into the School Improvement Plan (SIP) and monitored quarterly by the SDO.\nFor dimensions rated \'Beginning\' or \'Developing\', SDO technical assistance is strongly advised.', 'rule_based', '[\"bullying\"]', 0, '{\"positive\":0,\"negative\":1,\"neutral\":3}', '2026-03-15 14:45:23');

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
(7, 1, 1, 'validated', 71.29, 'Maturing', '2026-03-15 21:02:54', '2026-03-15 21:09:01', 1, '2026-03-15 22:44:12', 'need improvement', '2026-03-15 13:02:54');

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
(174, 7, 1, 1, 24.40, 32.00, 76.25, '2026-03-15 13:08:47'),
(177, 7, 1, 2, 27.20, 40.00, 68.00, '2026-03-15 13:08:51'),
(181, 7, 1, 4, 17.80, 24.00, 74.17, '2026-03-15 13:08:58'),
(186, 7, 1, 6, 19.00, 28.00, 67.86, '2026-03-15 13:09:00');

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
(165, 7, 1, 1, 4, '', NULL, 5, '2026-03-15 13:08:44'),
(166, 7, 2, 1, 3, '', NULL, 5, '2026-03-15 13:08:46'),
(167, 7, 3, 1, 2, '', NULL, 5, '2026-03-15 13:08:47'),
(168, 7, 13, 1, 4, '', NULL, 5, '2026-03-15 13:08:48'),
(169, 7, 14, 1, 2, '', NULL, 5, '2026-03-15 13:08:49'),
(170, 7, 16, 1, 4, '', NULL, 5, '2026-03-15 13:08:50'),
(171, 7, 17, 1, 2, '', NULL, 5, '2026-03-15 13:08:51'),
(172, 7, 24, 1, 4, '', NULL, 5, '2026-03-15 13:08:52'),
(173, 7, 25, 1, 2, '', NULL, 5, '2026-03-15 13:08:54'),
(174, 7, 26, 1, 3, '', NULL, 5, '2026-03-15 13:08:55'),
(175, 7, 27, 1, 2, '', NULL, 5, '2026-03-15 13:08:56'),
(176, 7, 28, 1, 4, '', NULL, 5, '2026-03-15 13:08:58'),
(177, 7, 41, 1, 2, '', NULL, 5, '2026-03-15 13:08:59'),
(178, 7, 42, 1, 3, '', NULL, 5, '2026-03-15 13:09:00');

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
(406, 7, 4, 1, 2, 3, '', 'submitted', '2026-03-15 13:02:54', '2026-03-15 13:04:31'),
(407, 7, 5, 1, 2, 4, '', 'submitted', '2026-03-15 13:02:55', '2026-03-15 13:04:31'),
(408, 7, 6, 1, 2, 3, '', 'submitted', '2026-03-15 13:02:56', '2026-03-15 13:04:31'),
(409, 7, 7, 1, 2, 4, '', 'submitted', '2026-03-15 13:02:57', '2026-03-15 13:04:31'),
(410, 7, 8, 1, 2, 3, '', 'submitted', '2026-03-15 13:02:58', '2026-03-15 13:04:31'),
(411, 7, 9, 1, 2, 1, 'none, but according to the last meeting it will be conduct later this year', 'submitted', '2026-03-15 13:03:11', '2026-03-15 13:04:31'),
(413, 7, 10, 1, 2, 1, 'marami pa rin cases of dropouts within the institution', 'submitted', '2026-03-15 13:03:37', '2026-03-15 13:04:31'),
(417, 7, 11, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:03', '2026-03-15 13:04:31'),
(418, 7, 12, 1, 2, 4, '', 'submitted', '2026-03-15 13:04:04', '2026-03-15 13:04:31'),
(420, 7, 15, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:07', '2026-03-15 13:04:31'),
(421, 7, 18, 1, 2, 1, '', 'submitted', '2026-03-15 13:04:09', '2026-03-15 13:04:31'),
(422, 7, 19, 1, 2, 4, '', 'submitted', '2026-03-15 13:04:10', '2026-03-15 13:04:31'),
(423, 7, 20, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:11', '2026-03-15 13:04:31'),
(424, 7, 21, 1, 2, 3, '', 'submitted', '2026-03-15 13:04:12', '2026-03-15 13:04:31'),
(425, 7, 22, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:14', '2026-03-15 13:04:31'),
(426, 7, 23, 1, 2, 4, '', 'submitted', '2026-03-15 13:04:15', '2026-03-15 13:04:31'),
(427, 7, 29, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:16', '2026-03-15 13:04:31'),
(428, 7, 30, 1, 2, 3, '', 'submitted', '2026-03-15 13:04:17', '2026-03-15 13:04:31'),
(429, 7, 31, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:18', '2026-03-15 13:04:31'),
(430, 7, 32, 1, 2, 3, '', 'submitted', '2026-03-15 13:04:19', '2026-03-15 13:04:31'),
(431, 7, 33, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:20', '2026-03-15 13:04:31'),
(432, 7, 34, 1, 2, 3, '', 'submitted', '2026-03-15 13:04:21', '2026-03-15 13:04:31'),
(433, 7, 35, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:22', '2026-03-15 13:04:31'),
(434, 7, 36, 1, 2, 3, '', 'submitted', '2026-03-15 13:04:24', '2026-03-15 13:04:31'),
(435, 7, 37, 1, 2, 1, '', 'submitted', '2026-03-15 13:04:25', '2026-03-15 13:04:31'),
(436, 7, 38, 1, 2, 4, '', 'submitted', '2026-03-15 13:04:26', '2026-03-15 13:04:31'),
(437, 7, 39, 1, 2, 2, '', 'submitted', '2026-03-15 13:04:28', '2026-03-15 13:04:31'),
(438, 7, 40, 1, 2, 3, '', 'submitted', '2026-03-15 13:04:29', '2026-03-15 13:04:31'),
(439, 7, 4, 1, 15, 2, '', 'submitted', '2026-03-15 13:04:41', '2026-03-15 13:05:29'),
(440, 7, 5, 1, 15, 3, '', 'submitted', '2026-03-15 13:04:42', '2026-03-15 13:05:29'),
(441, 7, 6, 1, 15, 3, '', 'submitted', '2026-03-15 13:04:43', '2026-03-15 13:05:29'),
(442, 7, 7, 1, 15, 2, '', 'submitted', '2026-03-15 13:04:45', '2026-03-15 13:05:29'),
(443, 7, 8, 1, 15, 4, '', 'submitted', '2026-03-15 13:04:47', '2026-03-15 13:05:29'),
(444, 7, 9, 1, 15, 1, 'marami pa rin cases of bullying', 'submitted', '2026-03-15 13:05:00', '2026-03-15 13:05:29'),
(445, 7, 10, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:02', '2026-03-15 13:05:29'),
(446, 7, 11, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:02', '2026-03-15 13:05:29'),
(447, 7, 12, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:04', '2026-03-15 13:05:29'),
(448, 7, 15, 1, 15, 4, '', 'submitted', '2026-03-15 13:05:05', '2026-03-15 13:05:29'),
(449, 7, 18, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:06', '2026-03-15 13:05:29'),
(450, 7, 19, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:08', '2026-03-15 13:05:29'),
(451, 7, 20, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:09', '2026-03-15 13:05:29'),
(452, 7, 21, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:10', '2026-03-15 13:05:29'),
(453, 7, 22, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:11', '2026-03-15 13:05:29'),
(454, 7, 23, 1, 15, 4, '', 'submitted', '2026-03-15 13:05:12', '2026-03-15 13:05:29'),
(455, 7, 29, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:13', '2026-03-15 13:05:29'),
(457, 7, 30, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:16', '2026-03-15 13:05:29'),
(458, 7, 31, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:17', '2026-03-15 13:05:29'),
(459, 7, 32, 1, 15, 1, '', 'submitted', '2026-03-15 13:05:18', '2026-03-15 13:05:29'),
(460, 7, 33, 1, 15, 4, '', 'submitted', '2026-03-15 13:05:19', '2026-03-15 13:05:29'),
(461, 7, 34, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:20', '2026-03-15 13:05:29'),
(462, 7, 35, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:21', '2026-03-15 13:05:29'),
(463, 7, 36, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:23', '2026-03-15 13:05:29'),
(464, 7, 37, 1, 15, 4, '', 'submitted', '2026-03-15 13:05:24', '2026-03-15 13:05:29'),
(465, 7, 38, 1, 15, 2, '', 'submitted', '2026-03-15 13:05:25', '2026-03-15 13:05:29'),
(466, 7, 39, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:26', '2026-03-15 13:05:29'),
(467, 7, 40, 1, 15, 3, '', 'submitted', '2026-03-15 13:05:27', '2026-03-15 13:05:29'),
(468, 7, 4, 1, 12, 3, '', 'submitted', '2026-03-15 13:05:41', '2026-03-15 13:06:24'),
(469, 7, 5, 1, 12, 2, '', 'submitted', '2026-03-15 13:05:42', '2026-03-15 13:06:24'),
(470, 7, 6, 1, 12, 3, '', 'submitted', '2026-03-15 13:05:43', '2026-03-15 13:06:24'),
(471, 7, 7, 1, 12, 4, '', 'submitted', '2026-03-15 13:05:45', '2026-03-15 13:06:24'),
(472, 7, 8, 1, 12, 2, '', 'submitted', '2026-03-15 13:05:46', '2026-03-15 13:06:24'),
(473, 7, 9, 1, 12, 3, '', 'submitted', '2026-03-15 13:05:49', '2026-03-15 13:06:24'),
(474, 7, 10, 1, 12, 2, '', 'submitted', '2026-03-15 13:05:51', '2026-03-15 13:06:24'),
(475, 7, 11, 1, 12, 3, '', 'submitted', '2026-03-15 13:05:52', '2026-03-15 13:06:24'),
(476, 7, 12, 1, 12, 4, '', 'submitted', '2026-03-15 13:05:53', '2026-03-15 13:06:24'),
(477, 7, 15, 1, 12, 2, '', 'submitted', '2026-03-15 13:05:54', '2026-03-15 13:06:24'),
(478, 7, 18, 1, 12, 1, '', 'submitted', '2026-03-15 13:05:55', '2026-03-15 13:06:24'),
(479, 7, 19, 1, 12, 4, '', 'submitted', '2026-03-15 13:05:57', '2026-03-15 13:06:24'),
(480, 7, 20, 1, 12, 2, '', 'submitted', '2026-03-15 13:05:59', '2026-03-15 13:06:24'),
(481, 7, 21, 1, 12, 3, '', 'submitted', '2026-03-15 13:06:00', '2026-03-15 13:06:24'),
(482, 7, 22, 1, 12, 2, '', 'submitted', '2026-03-15 13:06:01', '2026-03-15 13:06:24'),
(483, 7, 23, 1, 12, 1, '', 'submitted', '2026-03-15 13:06:02', '2026-03-15 13:06:24'),
(484, 7, 29, 1, 12, 4, '', 'submitted', '2026-03-15 13:06:03', '2026-03-15 13:06:24'),
(485, 7, 30, 1, 12, 3, '', 'submitted', '2026-03-15 13:06:04', '2026-03-15 13:06:24'),
(486, 7, 31, 1, 12, 2, '', 'submitted', '2026-03-15 13:06:06', '2026-03-15 13:06:24'),
(487, 7, 32, 1, 12, 3, '', 'submitted', '2026-03-15 13:06:06', '2026-03-15 13:06:24'),
(488, 7, 33, 1, 12, 3, '', 'submitted', '2026-03-15 13:06:07', '2026-03-15 13:06:24'),
(489, 7, 34, 1, 12, 2, '', 'submitted', '2026-03-15 13:06:08', '2026-03-15 13:06:24'),
(490, 7, 35, 1, 12, 4, '', 'submitted', '2026-03-15 13:06:09', '2026-03-15 13:06:24'),
(491, 7, 36, 1, 12, 4, '', 'submitted', '2026-03-15 13:06:10', '2026-03-15 13:06:24'),
(493, 7, 37, 1, 12, 2, '', 'submitted', '2026-03-15 13:06:13', '2026-03-15 13:06:24'),
(494, 7, 38, 1, 12, 4, '', 'submitted', '2026-03-15 13:06:14', '2026-03-15 13:06:24'),
(495, 7, 39, 1, 12, 3, '', 'submitted', '2026-03-15 13:06:15', '2026-03-15 13:06:24'),
(496, 7, 40, 1, 12, 2, '', 'submitted', '2026-03-15 13:06:16', '2026-03-15 13:06:24'),
(497, 7, 4, 1, 13, 4, '', 'submitted', '2026-03-15 13:06:39', '2026-03-15 13:07:32'),
(498, 7, 5, 1, 13, 3, '', 'submitted', '2026-03-15 13:06:40', '2026-03-15 13:07:32'),
(499, 7, 6, 1, 13, 4, '', 'submitted', '2026-03-15 13:06:41', '2026-03-15 13:07:32'),
(500, 7, 7, 1, 13, 2, '', 'submitted', '2026-03-15 13:06:43', '2026-03-15 13:07:32'),
(501, 7, 8, 1, 13, 3, '', 'submitted', '2026-03-15 13:06:44', '2026-03-15 13:07:32'),
(502, 7, 9, 1, 13, 3, '', 'submitted', '2026-03-15 13:06:45', '2026-03-15 13:07:32'),
(503, 7, 10, 1, 13, 1, 'marami pa rin dropout students here', 'submitted', '2026-03-15 13:06:46', '2026-03-15 13:07:32'),
(504, 7, 11, 1, 13, 4, '', 'submitted', '2026-03-15 13:06:47', '2026-03-15 13:07:32'),
(507, 7, 12, 1, 13, 3, '', 'submitted', '2026-03-15 13:07:08', '2026-03-15 13:07:32'),
(508, 7, 15, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:09', '2026-03-15 13:07:32'),
(509, 7, 18, 1, 13, 4, '', 'submitted', '2026-03-15 13:07:10', '2026-03-15 13:07:32'),
(510, 7, 19, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:12', '2026-03-15 13:07:32'),
(511, 7, 20, 1, 13, 3, '', 'submitted', '2026-03-15 13:07:13', '2026-03-15 13:07:32'),
(512, 7, 21, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:13', '2026-03-15 13:07:32'),
(513, 7, 22, 1, 13, 4, '', 'submitted', '2026-03-15 13:07:15', '2026-03-15 13:07:32'),
(514, 7, 23, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:16', '2026-03-15 13:07:32'),
(515, 7, 29, 1, 13, 1, '', 'submitted', '2026-03-15 13:07:17', '2026-03-15 13:07:32'),
(516, 7, 30, 1, 13, 3, '', 'submitted', '2026-03-15 13:07:18', '2026-03-15 13:07:32'),
(517, 7, 31, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:19', '2026-03-15 13:07:32'),
(518, 7, 32, 1, 13, 3, '', 'submitted', '2026-03-15 13:07:20', '2026-03-15 13:07:32'),
(519, 7, 33, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:21', '2026-03-15 13:07:32'),
(520, 7, 34, 1, 13, 4, '', 'submitted', '2026-03-15 13:07:22', '2026-03-15 13:07:32'),
(521, 7, 35, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:23', '2026-03-15 13:07:32'),
(522, 7, 36, 1, 13, 3, '', 'submitted', '2026-03-15 13:07:25', '2026-03-15 13:07:32'),
(523, 7, 37, 1, 13, 1, '', 'submitted', '2026-03-15 13:07:27', '2026-03-15 13:07:32'),
(524, 7, 38, 1, 13, 4, '', 'submitted', '2026-03-15 13:07:28', '2026-03-15 13:07:32'),
(525, 7, 39, 1, 13, 3, '', 'submitted', '2026-03-15 13:07:29', '2026-03-15 13:07:32'),
(526, 7, 40, 1, 13, 2, '', 'submitted', '2026-03-15 13:07:30', '2026-03-15 13:07:32'),
(527, 7, 4, 1, 14, 3, '', 'submitted', '2026-03-15 13:07:41', '2026-03-15 13:08:16'),
(528, 7, 5, 1, 14, 2, '', 'submitted', '2026-03-15 13:07:42', '2026-03-15 13:08:16'),
(529, 7, 6, 1, 14, 4, '', 'submitted', '2026-03-15 13:07:43', '2026-03-15 13:08:16'),
(530, 7, 7, 1, 14, 4, '', 'submitted', '2026-03-15 13:07:44', '2026-03-15 13:08:16'),
(531, 7, 8, 1, 14, 3, '', 'submitted', '2026-03-15 13:07:45', '2026-03-15 13:08:16'),
(532, 7, 9, 1, 14, 1, '', 'submitted', '2026-03-15 13:07:46', '2026-03-15 13:08:16'),
(534, 7, 10, 1, 14, 4, '', 'submitted', '2026-03-15 13:07:49', '2026-03-15 13:08:16'),
(535, 7, 11, 1, 14, 3, '', 'submitted', '2026-03-15 13:07:50', '2026-03-15 13:08:16'),
(536, 7, 12, 1, 14, 3, '', 'submitted', '2026-03-15 13:07:51', '2026-03-15 13:08:16'),
(537, 7, 15, 1, 14, 4, '', 'submitted', '2026-03-15 13:07:52', '2026-03-15 13:08:16'),
(538, 7, 18, 1, 14, 2, '', 'submitted', '2026-03-15 13:07:53', '2026-03-15 13:08:16'),
(539, 7, 19, 1, 14, 4, '', 'submitted', '2026-03-15 13:07:54', '2026-03-15 13:08:16'),
(540, 7, 20, 1, 14, 3, '', 'submitted', '2026-03-15 13:07:55', '2026-03-15 13:08:16'),
(541, 7, 21, 1, 14, 4, '', 'submitted', '2026-03-15 13:07:56', '2026-03-15 13:08:16'),
(542, 7, 22, 1, 14, 2, '', 'submitted', '2026-03-15 13:07:57', '2026-03-15 13:08:16'),
(543, 7, 23, 1, 14, 3, '', 'submitted', '2026-03-15 13:07:59', '2026-03-15 13:08:16'),
(544, 7, 29, 1, 14, 2, '', 'submitted', '2026-03-15 13:08:01', '2026-03-15 13:08:16'),
(545, 7, 30, 1, 14, 3, '', 'submitted', '2026-03-15 13:08:01', '2026-03-15 13:08:16'),
(546, 7, 31, 1, 14, 3, '', 'submitted', '2026-03-15 13:08:02', '2026-03-15 13:08:16'),
(547, 7, 32, 1, 14, 2, '', 'submitted', '2026-03-15 13:08:04', '2026-03-15 13:08:16'),
(548, 7, 33, 1, 14, 4, '', 'submitted', '2026-03-15 13:08:05', '2026-03-15 13:08:16'),
(549, 7, 34, 1, 14, 2, '', 'submitted', '2026-03-15 13:08:07', '2026-03-15 13:08:16'),
(550, 7, 35, 1, 14, 1, '', 'submitted', '2026-03-15 13:08:08', '2026-03-15 13:08:16'),
(551, 7, 36, 1, 14, 4, '', 'submitted', '2026-03-15 13:08:10', '2026-03-15 13:08:16'),
(552, 7, 37, 1, 14, 3, '', 'submitted', '2026-03-15 13:08:11', '2026-03-15 13:08:16'),
(553, 7, 38, 1, 14, 2, '', 'submitted', '2026-03-15 13:08:11', '2026-03-15 13:08:16'),
(554, 7, 39, 1, 14, 4, '', 'submitted', '2026-03-15 13:08:13', '2026-03-15 13:08:16'),
(555, 7, 40, 1, 14, 2, '', 'submitted', '2026-03-15 13:08:13', '2026-03-15 13:08:16');

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
(11, 7, 1, 1, 2, 'submitted', '2026-03-15 21:04:31', 28),
(12, 7, 1, 1, 15, 'submitted', '2026-03-15 21:05:29', 28),
(13, 7, 1, 1, 12, 'submitted', '2026-03-15 21:06:24', 28),
(14, 7, 1, 1, 13, 'submitted', '2026-03-15 21:07:32', 28),
(15, 7, 1, 1, 14, 'submitted', '2026-03-15 21:08:16', 28);

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
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sbm.edu.ph', 'System Administrator', 'admin', 'active', NULL, NULL, NULL, '2026-03-15 22:46:20', '2026-03-11 16:18:35'),
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, NULL, NULL, '2026-03-15 22:45:44', '2026-03-11 16:31:59'),
(5, 'Ryza E.', '$2y$10$uNsxRtmZILkMBaV3EfXdtuIfTTvSp0ZCctNKLtjeoZ9N9MNEjvrV6', 'rmevangelio@dihs.edu.ph', 'Ryza Evangelio', 'school_head', 'active', 1, NULL, NULL, '2026-03-15 22:44:58', '2026-03-11 16:35:49'),
(8, 'Rolito Billones', '$2y$10$vE5eBX3jCDELcBxYFpzEyu2xZI7j4WmKBisnmALaEJoBlauG1wMby', 'rbillones@dihs.edu.ph', 'Rolito Villones', 'sdo', 'active', 1, NULL, NULL, '2026-03-15 22:46:51', '2026-03-11 17:49:46'),
(10, 'Charles', '$2y$10$QAAo3OtJ1AEEj3tltB3hteEmz6xYbZNL19jeADIS2dLHg26vTe/Je', 'cpmarias@dihs.edu.com', 'Charles Patrick Arias', 'ro', 'active', 1, NULL, NULL, '2026-03-15 22:43:21', '2026-03-11 17:52:19'),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, NULL, NULL, '2026-03-15 21:05:38', '2026-03-15 11:19:35'),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, NULL, NULL, '2026-03-15 21:06:36', '2026-03-15 11:20:09'),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, NULL, NULL, '2026-03-15 21:07:39', '2026-03-15 11:20:53'),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, NULL, NULL, '2026-03-15 22:16:50', '2026-03-15 11:21:39');

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

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
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `ml_comment_analysis`
--
ALTER TABLE `ml_comment_analysis`
  MODIFY `analysis_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `pred_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_recommendations`
--
ALTER TABLE `ml_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `region_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sbm_cycles`
--
ALTER TABLE `sbm_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sbm_dimensions`
--
ALTER TABLE `sbm_dimensions`
  MODIFY `dimension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbm_dimension_scores`
--
ALTER TABLE `sbm_dimension_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

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
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=556;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
