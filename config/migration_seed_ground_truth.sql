CREATE TABLE IF NOT EXISTS `seed_ground_truth` (
  `ground_truth_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cycle_id` int(11) NOT NULL,
  `sy_id` int(11) NOT NULL,
  `school_year_label` varchar(20) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `anomaly_type` varchar(30) NOT NULL,
  `is_preexisting` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`ground_truth_id`),
  UNIQUE KEY `uq_seed_ground_truth_cycle_evaluator` (`cycle_id`,`evaluator_id`),
  KEY `idx_seed_ground_truth_sy` (`sy_id`,`school_year_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
