-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2026 at 04:21 AM
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
(1, 37, 'login', 'auth', 'User logged in', '::1', '2026-10-10 09:49:02'),
(2, 74, 'login', 'auth', 'User logged in', '::1', '2026-10-10 09:51:16'),
(3, 37, 'login', 'auth', 'User logged in', '::1', '2026-10-10 09:53:17'),
(4, 46, 'login', 'auth', 'User logged in', '::1', '2026-10-10 09:53:37'),
(5, 46, 'import_form_document', 'manage_form', 'Imported form draft from uploaded document (docx)', '::1', '2026-10-10 10:02:21'),
(6, 46, 'publish_form_version', 'manage_form', 'Published form version 1 (ID: 1)', '::1', '2026-10-10 10:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `ai_suggestion_generations`
--

CREATE TABLE `ai_suggestion_generations` (
  `generation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_synthetic` tinyint(1) DEFAULT 0,
  `backend_used` varchar(60) DEFAULT NULL,
  `input_payload_hash` char(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_suggestion_history`
--

CREATE TABLE `ai_suggestion_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` int(11) NOT NULL,
  `school_year` varchar(30) NOT NULL,
  `dimension_id` int(11) NOT NULL,
  `suggestion_title` varchar(255) NOT NULL,
  `suggestion_text` longtext NOT NULL,
  `last_cycle_note` text DEFAULT NULL,
  `priority_label` varchar(20) NOT NULL DEFAULT 'Medium',
  `decline_risk` varchar(20) NOT NULL DEFAULT 'medium',
  `data_status` enum('sufficient','insufficient') NOT NULL DEFAULT 'insufficient',
  `score_at_time` decimal(5,2) DEFAULT NULL,
  `model_version` varchar(80) DEFAULT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `needs_review` tinyint(1) NOT NULL DEFAULT 0,
  `adopted_plan_id` int(11) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_sample` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_suggestion_items`
--

CREATE TABLE `ai_suggestion_items` (
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `generation_id` bigint(20) UNSIGNED NOT NULL,
  `item_index` int(10) UNSIGNED NOT NULL,
  `source` enum('llm','rule_teacher_outlier','ml_teacher_outlier') NOT NULL DEFAULT 'llm',
  `detector_type` varchar(30) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body_text` longtext NOT NULL,
  `indicator_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`indicator_codes`)),
  `confidence` decimal(5,2) DEFAULT NULL,
  `teacher_user_id` int(11) DEFAULT NULL,
  `teacher_label` varchar(40) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `school_id`, `name`, `description`, `status`, `created_at`) VALUES
(1, 1, 'English', NULL, 'active', '2026-06-07 19:43:18'),
(2, 1, 'Information Technology', NULL, 'active', '2026-06-07 19:51:01'),
(3, 1, 'Filipino', NULL, 'active', '2026-06-21 19:10:26'),
(4, 1, 'Guidance', NULL, 'active', '2026-06-21 19:16:55');

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
(46, 1, '2026-10-10');

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
(1, 1, 'Version 1', 1, 46, '2026-10-10 10:03:29', '2026-10-10 18:03:29');

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
  `current_owner_role` varchar(30) DEFAULT 'school_head',
  `current_owner_user_id` int(11) DEFAULT NULL,
  `last_action_by` int(11) DEFAULT NULL,
  `last_action_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `suggestion_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `suggestion_baseline_text` longtext DEFAULT NULL,
  `is_sample` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `improvement_plans_backup_20261009_214920`
--

CREATE TABLE `improvement_plans_backup_20261009_214920` (
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
  `current_owner_role` varchar(30) DEFAULT 'school_head',
  `current_owner_user_id` int(11) DEFAULT NULL,
  `last_action_by` int(11) DEFAULT NULL,
  `last_action_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `suggestion_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `suggestion_baseline_text` longtext DEFAULT NULL,
  `is_sample` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `improvement_plan_history`
--

CREATE TABLE `improvement_plan_history` (
  `history_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `action` varchar(40) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) DEFAULT NULL,
  `actor_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `snapshot_json` longtext NOT NULL,
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
-- Table structure for table `ip_field_usage`
--

CREATE TABLE `ip_field_usage` (
  `user_id` int(11) NOT NULL,
  `field_type` enum('objective','strategy') NOT NULL,
  `usage_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `reset_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `registration_requests`
--

CREATE TABLE `registration_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `username` varchar(60) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `assigned_role` enum('system_admin','school_head','sbm_coordinator','teacher','external_stakeholder') DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `slug`, `label`, `color`, `description`, `is_system`, `status`, `created_at`) VALUES
(1, 'system_admin', 'System Admin', '#64748B', '', 1, 'enabled', '2026-06-05 09:36:29'),
(2, 'school_head', 'School Head', '#166534', NULL, 1, 'enabled', '2026-06-05 09:36:29'),
(3, 'sbm_coordinator', 'SBM Coordinator', '#64748B', '', 1, 'enabled', '2026-06-05 09:36:29'),
(4, 'teacher', 'School Teacher', '#0D9488', NULL, 1, 'enabled', '2026-06-05 09:36:29'),
(5, 'external_stakeholder', 'External Stakeholder', '#D97706', NULL, 1, 'enabled', '2026-06-05 09:36:29');

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
  `maturity_level` varchar(20) DEFAULT NULL,
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
(1, 1, 1, 'draft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-10 09:52:58', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sbm_dimensions`
--

CREATE TABLE `sbm_dimensions` (
  `dimension_id` int(11) NOT NULL,
  `dimension_no` tinyint(4) NOT NULL,
  `dimension_name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT '#16A34A',
  `icon` varchar(40) DEFAULT NULL,
  `indicator_count` int(11) DEFAULT 0,
  `form_version_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_dimensions`
--

INSERT INTO `sbm_dimensions` (`dimension_id`, `dimension_no`, `dimension_name`, `description`, `color_hex`, `icon`, `indicator_count`, `form_version_id`) VALUES
(1, 1, 'Curriculum and Teaching', 'Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.', '#2563EB', 'layers', 8, 1),
(2, 2, 'Learning Environment', 'The school has zero bullying incidence.', '#16A34A', 'layers', 10, 1),
(3, 3, 'Leadership', 'The school develops a strategic plan.', '#7C3AED', 'layers', 4, 1),
(4, 4, 'Governance and Accountability', 'The school\'s strategic plan is operationalized through an implementation plan.', '#D97706', 'layers', 6, 1),
(5, 5, 'Human Resource and Team Development', 'School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.', '#DC2626', 'layers', 7, 1),
(6, 6, 'Finance and Resource Management and Mobilization', 'The school inspects its infrastructure and facilities.', '#0D9488', 'layers', 7, 1);

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
  `short_title` varchar(120) DEFAULT NULL,
  `mov_guide` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `rater_role` varchar(20) NOT NULL DEFAULT 'SH_ONLY',
  `is_active` tinyint(1) DEFAULT 1,
  `form_version_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `improvement_plan_indicators`
--

CREATE TABLE `improvement_plan_indicators` (
  `plan_id` int(11) NOT NULL,
  `indicator_id` int(11) NOT NULL,
  PRIMARY KEY (`plan_id`,`indicator_id`),
  KEY `idx_improvement_plan_indicators_indicator` (`indicator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbm_indicators`
--

INSERT INTO `sbm_indicators` (`indicator_id`, `dimension_id`, `indicator_code`, `indicator_text`, `short_title`, `mov_guide`, `sort_order`, `rater_role`, `is_active`, `form_version_id`) VALUES
(1, 1, '1.1', 'Grade 3 learners achieve the proficiency level for each cluster of early language, literacy, and numeracy skills.', NULL, 'Grade 3 assessment results, reading and numeracy assessment records, and learner progress reports.', 1, 'SH_TEACHER', 1, 1),
(2, 1, '1.2', 'Grade 6, 10, and 12 learners achieve the proficiency level in all 21st-century skills and core learning areas in the National Achievement Test (NAT).', NULL, 'NAT results, learner achievement summaries, and school performance analysis reports.', 2, 'SH_TEACHER', 1, 1),
(3, 1, '1.3', 'School-based ALS learners attain certification as elementary and junior high school completers.', NULL, 'ALS learner records, completion certificates, and ALS program accomplishment reports.', 3, 'SH_TEACHER', 1, 1),
(4, 1, '1.4', 'Teachers prepare contextualized learning materials responsive to the needs of learners.', NULL, 'Contextualized learning materials, lesson plans, and records of material review or validation.', 4, 'SH_TEACHER', 1, 1),
(5, 1, '1.5', 'Teachers conduct remediation activities to address learning gaps in reading and comprehension, science and technology, and mathematics.', NULL, 'Remediation plans, intervention records, learner progress data, and monitoring reports.', 5, 'SH_TEACHER', 1, 1),
(6, 1, '1.6', 'Teachers integrate topics promoting peace and DepEd core values.', NULL, 'Lesson plans, instructional materials, classroom observation records, and activity documentation.', 6, 'SH_TEACHER', 1, 1),
(7, 1, '1.7', 'The school conducts test item analysis to inform its teaching and learning process.', NULL, 'Test item analysis reports, assessment results, and records of instructional adjustments.', 7, 'SH_TEACHER', 1, 1),
(8, 1, '1.8', 'The school engages local industries to strengthen its TLE-TV L course offerings.', NULL, 'Partnership agreements, meeting minutes, industry engagement records, and TLE-TV L program reports.', 8, 'SH_TEACHER', 1, 1),
(9, 2, '2.1', 'The school has zero bullying incidence.', NULL, 'Bullying incident monitoring records, child protection committee reports, and intervention documentation.', 1, 'SH_TEACHER', 1, 1),
(10, 2, '2.2', 'The school has zero child abuse incidence.', NULL, 'Child protection records, incident monitoring reports, and prevention activity documentation.', 2, 'SH_TEACHER', 1, 1),
(11, 2, '2.3', 'The school has reduced its drop-out incidence.', NULL, 'Enrollment and dropout data, learner tracking records, and intervention reports.', 3, 'SH_TEACHER', 1, 1),
(12, 2, '2.4', 'The school conducts culture-sensitive activities.', NULL, 'Activity plans, photos or documentation, attendance sheets, and stakeholder feedback.', 4, 'SH_TEACHER', 1, 1),
(13, 2, '2.5', 'The school provides access to learning experiences for disadvantaged, OSYs, and adult learners.', NULL, 'Program records, learner profiles, enrollment records, and participation reports.', 5, 'SH_TEACHER', 1, 1),
(14, 2, '2.6', 'The school has a functional school-based ALS program.', NULL, 'ALS implementation plan, class/program records, learner attendance, and accomplishment reports.', 6, 'SH_TEACHER', 1, 1),
(15, 2, '2.7', 'The school has a functional child-protection committee.', NULL, 'Committee designation, meeting minutes, action plans, and accomplishment reports.', 7, 'SH_TEACHER', 1, 1),
(16, 2, '2.8', 'The school has a functional DRRM plan.', NULL, 'School DRRM plan, risk assessment, drill records, and monitoring reports.', 8, 'SH_TEACHER', 1, 1),
(17, 2, '2.9', 'The school has a functional support mechanism for mental wellness.', NULL, 'Mental wellness program plan, activity records, referral procedures, and monitoring reports.', 9, 'SH_TEACHER', 1, 1),
(18, 2, '2.10', 'The school has special education- and PWD-friendly facilities.', NULL, 'Facility inspection reports, accessibility assessment, photos, and improvement plans.', 10, 'SH_TEACHER', 1, 1),
(19, 3, '3.1', 'The school develops a strategic plan.', NULL, 'School strategic plan, planning workshop minutes, attendance sheets, and stakeholder consultation records.', 1, 'SH_TEACHER', 1, 1),
(20, 3, '3.2', 'The school has a functional school-community planning team.', NULL, 'Team designation, terms of reference, meeting minutes, and planning outputs.', 2, 'SH_TEACHER', 1, 1),
(21, 3, '3.3', 'The school has a functional Supreme Student Government/Supreme Pupil Government.', NULL, 'SSG/SPG designation and constitution, activity plans, meeting minutes, and accomplishment reports.', 3, 'SH_TEACHER', 1, 1),
(22, 3, '3.4', 'The school innovates in its provision of frontline services to stakeholders.', NULL, 'Innovation proposals, service records, stakeholder feedback, and implementation reports.', 4, 'SH_TEACHER', 1, 1),
(23, 4, '4.1', 'The school\'s strategic plan is operationalized through an implementation plan.', NULL, 'Implementation plan, work and financial plans, progress reports, and monitoring records.', 1, 'SH_TEACHER', 1, 1),
(24, 4, '4.2', 'The school has a functional School Governance Council (SGC).', NULL, 'SGC composition/designation, meeting minutes, action plans, and accomplishment reports.', 2, 'SH_TEACHER', 1, 1),
(25, 4, '4.3', 'The school has a functional Parent-Teacher Association (PTA).', NULL, 'PTA officers list, meeting minutes, approved plans, and accomplishment reports.', 3, 'SH_TEACHER', 1, 1),
(26, 4, '4.4', 'The school collaborates with stakeholders and other schools in strengthening partnerships.', NULL, 'Partnership agreements, coordination meeting minutes, joint activity reports, and stakeholder records.', 4, 'SH_TEACHER', 1, 1),
(27, 4, '4.5', 'The school monitors and evaluates its programs, projects, and activities.', NULL, 'Monitoring and evaluation tools, accomplishment reports, evaluation results, and follow-up actions.', 5, 'SH_TEACHER', 1, 1),
(28, 4, '4.6', 'The school maintains an average rating of satisfactory from its internal and external stakeholders.', NULL, 'Stakeholder satisfaction survey tools, consolidated results, and action plans based on feedback.', 6, 'SH_TEACHER', 1, 1),
(29, 5, '5.1', 'School personnel achieve an average rating of very satisfactory in the individual performance commitment and review.', NULL, 'IPCRF/individual performance review records, consolidated ratings, and performance improvement plans.', 1, 'SH_TEACHER', 1, 1),
(30, 5, '5.2', 'The school achieves an average rating of very satisfactory in the office performance commitment and review.', NULL, 'OPCRF/office performance review records, consolidated ratings, and office performance reports.', 2, 'SH_TEACHER', 1, 1),
(31, 5, '5.3', 'The school conducts needs-based Learning Action Cells and Learning & Development activities.', NULL, 'LAC plan, session designs, attendance sheets, minutes, and training evaluation reports.', 3, 'SH_TEACHER', 1, 1),
(32, 5, '5.4', 'The school facilitates the promotion and continuous professional development of its personnel.', NULL, 'Professional development plans, training certificates, promotion records, and personnel development reports.', 4, 'SH_TEACHER', 1, 1),
(33, 5, '5.5', 'The school recognizes and rewards milestone achievements of its personnel.', NULL, 'Recognition policy, award records, certificates, and documentation of recognition activities.', 5, 'SH_TEACHER', 1, 1),
(34, 5, '5.6', 'The school facilitates receipt of correct salaries, allowances, and other additional compensation in a timely manner.', NULL, 'Payroll and allowance records, disbursement schedules, and personnel acknowledgment records, subject to privacy safeguards.', 6, 'SH_TEACHER', 1, 1),
(35, 5, '5.7', 'Teacher workload is distributed fairly and equitably.', NULL, 'Teaching load assignments, class programs, workload distribution records, and approved adjustments.', 7, 'SH_TEACHER', 1, 1),
(36, 6, '6.1', 'The school inspects its infrastructure and facilities.', NULL, 'Inspection checklists, facility assessment reports, photos, and maintenance records.', 1, 'SH_TEACHER', 1, 1),
(37, 6, '6.2', 'The school initiates improvement of its infrastructure and facilities.', NULL, 'Improvement plans, work orders, project documentation, and completion/inspection reports.', 2, 'SH_TEACHER', 1, 1),
(38, 6, '6.3', 'The school has a functional library.', NULL, 'Library inventory, borrowing records, library schedules, and inspection reports.', 3, 'SH_TEACHER', 1, 1),
(39, 6, '6.4', 'The school has functional water, electric, and internet facilities.', NULL, 'Utility inspection records, service/maintenance reports, and facility functionality checklists.', 4, 'SH_TEACHER', 1, 1),
(40, 6, '6.5', 'The school has a functional computer laboratory/classroom.', NULL, 'Computer inventory, laboratory schedules, maintenance records, and usage logs.', 5, 'SH_TEACHER', 1, 1),
(41, 6, '6.6', 'The school achieves a 75–100% utilization rate of its Maintenance and Other Operating Expenses (MOOE).', NULL, 'MOOE utilization reports, financial statements, obligation/disbursement records, and supporting documents.', 6, 'SH_TEACHER', 1, 1),
(42, 6, '6.7', 'The school liquidates 100% of its utilized MOOE.', NULL, 'Liquidation reports, receipts and supporting documents, and financial accountability records.', 7, 'SH_TEACHER', 1, 1);

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
-- Table structure for table `school_head_indicator_assignments`
--

CREATE TABLE `school_head_indicator_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `indicator_code` varchar(20) NOT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `cycle_id` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `date_end` date DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`sy_id`, `label`, `is_current`, `date_start`, `date_end`, `is_archived`) VALUES
(1, '2001-2002', 1, '2001-06-04', '2002-03-27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `seed_ground_truth`
--

CREATE TABLE `seed_ground_truth` (
  `ground_truth_id` bigint(20) UNSIGNED NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `school_year_label` varchar(20) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `anomaly_type` varchar(30) NOT NULL,
  `is_preexisting` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stakeholder_indicator_assignments`
--

CREATE TABLE `stakeholder_indicator_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `stakeholder_id` int(10) UNSIGNED NOT NULL,
  `indicator_code` varchar(20) NOT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `cycle_id` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
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
('active_school_year_override', '1', '2026-10-10 09:52:54');

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
  `role` enum('system_admin','school_head','sbm_coordinator','teacher','external_stakeholder') DEFAULT NULL,
  `status` enum('pending','active','inactive','archived','suspended','rejected') NOT NULL DEFAULT 'inactive',
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
  `profile_picture` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `school_id`, `employee_id`, `department`, `last_login`, `created_at`, `email_verified`, `reset_token`, `token_expiry`, `email_sent_at`, `force_password_change`, `contact_number`, `profile_picture`, `rejection_reason`, `reviewed_by`, `reviewed_at`) VALUES
(2, 'Denise Alia', '$2y$10$ee68u47egveVq9Z4Yq/V9OBaCgjX0SdGlvVlt66Pv5EeVelt92A.a', 'daasernande@dihs.edu.ph', 'Denise Alia Sernande', 'teacher', 'active', 1, NULL, 'English', '2026-10-09 02:50:45', '2026-03-11 16:31:59', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(12, 'Julia', '$2y$10$X.wdMmmf0e4mYYVoRx9jquyA8cHJzj.y29Om9a04rWwRDap2mizOy', 'jcfornal@dihs.edu.com', 'Julia Chloe Fornal', 'teacher', 'active', 1, NULL, 'English', '2026-09-29 16:02:14', '2026-03-15 11:19:35', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(13, 'Juan', '$2y$10$wbZBIW1za0UZ7eD6GYUzAuncRsdG.TO1pB/66yuF30HhkpLxKQcSa', 'jdela@dihs.edu.com', 'Juan Dela', 'teacher', 'active', 1, NULL, 'English', '2026-09-19 11:30:44', '2026-03-15 11:20:09', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(14, 'Justine', '$2y$10$.9PKQlpP8KRtUGiAwrtiLOyxdvKjszyIXxZ.B.pjNSdDd7Vf3vjl.', 'jobien@dihs.edu.com', 'Justine Obien', 'teacher', 'active', 1, NULL, 'Filipino', '2026-10-09 02:47:14', '2026-03-15 11:20:53', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(15, 'Axl', '$2y$10$luvaOJeOb3AxCGfqCtSkN.GGLdKxZxhg/zOT6PZC.koJIKO00PkM.', 'amacabecha@dihs.edu.com', 'Axl Macabecha', 'teacher', 'active', 1, NULL, 'English', '2026-10-09 03:14:02', '2026-03-15 11:21:39', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(37, 'schoolhead', '$2y$10$gr5msAhfrcZobx/4yCcTPu9bBsl8WQCylqVSrxGjmBptxY8G9N.cO', 'schoolhead@gmail.com', 'Ryza Evangelio', 'school_head', 'active', 1, NULL, 'Guidance', '2026-10-10 17:53:17', '2026-03-29 09:06:55', 0, NULL, NULL, NULL, 0, '09412568901', 'uploads/avatars/avatar_37_1780853466.jpg', NULL, NULL, NULL),
(46, 'Charles', '$2y$10$9QWVYCP/gNj9kS9vZ72OpeK8BsICHhNjMndKyzi4ZBxQ00A3Mw1WS', 'mendozacharles11011@gmail.com', 'Charles Patrick Arias', 'sbm_coordinator', 'active', 1, NULL, 'Filipino', '2026-10-10 17:53:37', '2026-04-01 02:35:08', 0, NULL, NULL, '2026-04-01 10:35:53', 0, NULL, 'uploads/avatars/avatar_46_1790480979.jpg', NULL, NULL, NULL),
(74, 'charlesarias', '$2y$10$Zamu/arxPs7ldX8oJ9e27u95NJ4XqgJvWrOb9EggmsAOjOtyMIS3S', 'ariascharles00@gmail.com', 'Charles Arias', 'system_admin', 'active', 1, NULL, 'Information Technology', '2026-10-10 17:51:16', '2026-06-10 16:00:00', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(85, 'eval_9b3f24d0', '$2y$10$IO0eLX/c7WW0.9wxrunK4Og6Ar0PlxzroPBsSU.ggPUaGdFt4n.rm', 'dozenjames54@gmail.com', 'James Dozen', 'external_stakeholder', 'active', 1, NULL, NULL, '2026-10-09 03:08:07', '2026-10-08 18:52:59', 0, NULL, NULL, '2026-10-09 02:53:03', 0, NULL, NULL, NULL, NULL, NULL);

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
,`user_account_status` enum('pending','active','inactive','archived','suspended','rejected')
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
-- Indexes for table `ai_suggestion_generations`
--
ALTER TABLE `ai_suggestion_generations`
  ADD PRIMARY KEY (`generation_id`),
  ADD KEY `idx_ai_suggestion_generations_school_cycle` (`school_id`,`cycle_id`,`generated_at`),
  ADD KEY `idx_ai_suggestion_generations_user` (`user_id`),
  ADD KEY `fk_ai_suggestion_generation_cycle` (`cycle_id`);

--
-- Indexes for table `ai_suggestion_history`
--
ALTER TABLE `ai_suggestion_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_history_school_year` (`school_id`,`school_year`),
  ADD KEY `idx_ai_history_dimension` (`school_id`,`dimension_id`,`school_year`),
  ADD KEY `idx_ai_history_final` (`school_id`,`school_year`,`is_final`),
  ADD KEY `fk_ai_history_dimension` (`dimension_id`),
  ADD KEY `fk_ai_history_plan` (`adopted_plan_id`),
  ADD KEY `fk_ai_history_user` (`generated_by`);

--
-- Indexes for table `ai_suggestion_items`
--
ALTER TABLE `ai_suggestion_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uq_ai_suggestion_generation_item` (`generation_id`,`item_index`),
  ADD KEY `idx_ai_suggestion_items_teacher` (`teacher_user_id`);

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
  ADD KEY `improvement_plans_ibfk_6` (`submitted_by`),
  ADD KEY `idx_improvement_plans_suggestion_item` (`suggestion_item_id`);

--
-- Indexes for table `improvement_plans_backup_20261009_214920`
--
ALTER TABLE `improvement_plans_backup_20261009_214920`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `dimension_id` (`dimension_id`),
  ADD KEY `improvement_plans_ibfk_4` (`indicator_id`),
  ADD KEY `improvement_plans_ibfk_5` (`created_by`),
  ADD KEY `idx_workflow_status` (`workflow_status`),
  ADD KEY `improvement_plans_ibfk_6` (`submitted_by`),
  ADD KEY `idx_improvement_plans_suggestion_item` (`suggestion_item_id`);

--
-- Indexes for table `improvement_plan_history`
--
ALTER TABLE `improvement_plan_history`
  ADD PRIMARY KEY (`history_id`),
  ADD UNIQUE KEY `uq_plan_version` (`plan_id`,`version_no`),
  ADD KEY `idx_plan_history_plan` (`plan_id`),
  ADD KEY `idx_plan_history_actor` (`actor_id`);

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
-- Indexes for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `rejected_by` (`rejected_by`),
  ADD KEY `status_index` (`submitted_at`,`approved_at`,`rejected_at`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `ix_reg_req_user_id` (`user_id`),
  ADD KEY `ix_reg_req_submitted` (`submitted_at`),
  ADD KEY `ix_reg_req_approved` (`approved_at`),
  ADD KEY `ix_reg_req_rejected` (`rejected_at`);

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
-- Indexes for table `school_head_indicator_assignments`
--
ALTER TABLE `school_head_indicator_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sh_user_cycle` (`user_id`,`cycle_id`),
  ADD KEY `idx_sh_code` (`indicator_code`);

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
-- Indexes for table `seed_ground_truth`
--
ALTER TABLE `seed_ground_truth`
  ADD PRIMARY KEY (`ground_truth_id`),
  ADD UNIQUE KEY `uq_seed_ground_truth_cycle_evaluator` (`cycle_id`,`evaluator_id`),
  ADD KEY `idx_seed_ground_truth_sy` (`sy_id`,`school_year_label`);

--
-- Indexes for table `stakeholder_indicator_assignments`
--
ALTER TABLE `stakeholder_indicator_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sth_user_cycle` (`stakeholder_id`,`cycle_id`),
  ADD KEY `idx_sth_code` (`indicator_code`);

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
  ADD UNIQUE KEY `unique_teacher_cycle_indicator` (`teacher_id`,`indicator_code`,`cycle_id`),
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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ai_suggestion_generations`
--
ALTER TABLE `ai_suggestion_generations`
  MODIFY `generation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_suggestion_history`
--
ALTER TABLE `ai_suggestion_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_suggestion_items`
--
ALTER TABLE `ai_suggestion_items`
  MODIFY `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  MODIFY `snap_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycle_audit_log`
--
ALTER TABLE `cycle_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycle_evaluators`
--
ALTER TABLE `cycle_evaluators`
  MODIFY `evaluator_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycle_evaluator_status_log`
--
ALTER TABLE `cycle_evaluator_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evidence_audit_log`
--
ALTER TABLE `evidence_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form_versions`
--
ALTER TABLE `form_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grading_periods`
--
ALTER TABLE `grading_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans`
--
ALTER TABLE `improvement_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plans_backup_20261009_214920`
--
ALTER TABLE `improvement_plans_backup_20261009_214920`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `improvement_plan_history`
--
ALTER TABLE `improvement_plan_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `indicator_evidence_requirements`
--
ALTER TABLE `indicator_evidence_requirements`
  MODIFY `req_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_training_snapshots`
--
ALTER TABLE `ml_training_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration_requests`
--
ALTER TABLE `registration_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response_attachments`
--
ALTER TABLE `response_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbm_indicators`
--
ALTER TABLE `sbm_indicators`
  MODIFY `indicator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sbm_responses`
--
ALTER TABLE `sbm_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `school_head_indicator_assignments`
--
ALTER TABLE `school_head_indicator_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_workflow_status`
--
ALTER TABLE `school_workflow_status`
  MODIFY `wf_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seed_ground_truth`
--
ALTER TABLE `seed_ground_truth`
  MODIFY `ground_truth_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stakeholder_indicator_assignments`
--
ALTER TABLE `stakeholder_indicator_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `tr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `workflow_checkpoints`
--
ALTER TABLE `workflow_checkpoints`
  MODIFY `cp_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `ai_suggestion_generations`
--
ALTER TABLE `ai_suggestion_generations`
  ADD CONSTRAINT `fk_ai_suggestion_generation_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_suggestion_generation_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_suggestion_generation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_suggestion_history`
--
ALTER TABLE `ai_suggestion_history`
  ADD CONSTRAINT `fk_ai_history_dimension` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_history_plan` FOREIGN KEY (`adopted_plan_id`) REFERENCES `improvement_plans` (`plan_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ai_history_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_history_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `ai_suggestion_items`
--
ALTER TABLE `ai_suggestion_items`
  ADD CONSTRAINT `fk_ai_suggestion_item_generation` FOREIGN KEY (`generation_id`) REFERENCES `ai_suggestion_generations` (`generation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_suggestion_item_teacher` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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
ALTER TABLE `improvement_plan_indicators`
  ADD CONSTRAINT `fk_plan_indicators_plan` FOREIGN KEY (`plan_id`) REFERENCES `improvement_plans` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_plan_indicators_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE CASCADE;

ALTER TABLE `improvement_plans`
  ADD CONSTRAINT `fk_improvement_plan_suggestion_item` FOREIGN KEY (`suggestion_item_id`) REFERENCES `ai_suggestion_items` (`item_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `improvement_plans_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `improvement_plans_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `sbm_cycles` (`cycle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `improvement_plans_ibfk_3` FOREIGN KEY (`dimension_id`) REFERENCES `sbm_dimensions` (`dimension_id`),
  ADD CONSTRAINT `improvement_plans_ibfk_4` FOREIGN KEY (`indicator_id`) REFERENCES `sbm_indicators` (`indicator_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `improvement_plans_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `improvement_plans_ibfk_6` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `improvement_plan_history`
--
ALTER TABLE `improvement_plan_history`
  ADD CONSTRAINT `fk_plan_history_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_plan_history_plan` FOREIGN KEY (`plan_id`) REFERENCES `improvement_plans` (`plan_id`) ON DELETE CASCADE;

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
-- Constraints for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD CONSTRAINT `registration_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registration_requests_ibfk_3` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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
