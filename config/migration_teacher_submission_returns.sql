-- Add a per-teacher returned state without changing existing submission rows.
ALTER TABLE `teacher_submissions`
  MODIFY COLUMN `status` ENUM('draft', 'submitted', 'returned') NULL DEFAULT 'draft';

-- Append-only event history for coordinator returns and teacher resubmissions.
CREATE TABLE IF NOT EXISTS `teacher_submission_history` (
  `event_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cycle_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `event_type` enum('returned', 'resubmitted') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `teacher_submission_history_cycle_teacher` (`cycle_id`, `school_id`, `teacher_id`, `event_id`),
  KEY `teacher_submission_history_submission` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
