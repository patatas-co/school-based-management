-- Background-only AI suggestion provenance and feedback tracking.
CREATE TABLE IF NOT EXISTS ai_suggestion_generations (
  generation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  school_id INT NOT NULL,
  cycle_id INT NOT NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_synthetic TINYINT(1) DEFAULT 0,
  backend_used VARCHAR(60) DEFAULT NULL,
  input_payload_hash CHAR(64) NOT NULL,
  PRIMARY KEY (generation_id),
  KEY idx_ai_suggestion_generations_school_cycle (school_id, cycle_id, generated_at),
  KEY idx_ai_suggestion_generations_user (user_id),
  CONSTRAINT fk_ai_suggestion_generation_user
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_suggestion_generation_school
    FOREIGN KEY (school_id) REFERENCES schools (school_id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_suggestion_generation_cycle
    FOREIGN KEY (cycle_id) REFERENCES sbm_cycles (cycle_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sf_column_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ai_suggestion_generations'
    AND column_name = 'is_synthetic'
);
SET @sf_sql = IF(@sf_column_exists = 0,
  'ALTER TABLE ai_suggestion_generations ADD COLUMN is_synthetic TINYINT(1) DEFAULT 0 AFTER generated_at',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;

CREATE TABLE IF NOT EXISTS ai_suggestion_items (
  item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generation_id BIGINT UNSIGNED NOT NULL,
  item_index INT UNSIGNED NOT NULL,
  source ENUM('llm','rule_teacher_outlier','ml_teacher_outlier') NOT NULL DEFAULT 'llm',
  detector_type VARCHAR(30) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  body_text LONGTEXT NOT NULL,
  indicator_codes JSON DEFAULT NULL,
  confidence DECIMAL(5,2) DEFAULT NULL,
  teacher_user_id INT DEFAULT NULL,
  teacher_label VARCHAR(40) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (item_id),
  UNIQUE KEY uq_ai_suggestion_generation_item (generation_id, item_index),
  KEY idx_ai_suggestion_items_teacher (teacher_user_id),
  CONSTRAINT fk_ai_suggestion_item_generation
    FOREIGN KEY (generation_id) REFERENCES ai_suggestion_generations (generation_id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_suggestion_item_teacher
    FOREIGN KEY (teacher_user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sf_detector_column_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ai_suggestion_items'
    AND column_name = 'detector_type'
);
SET @sf_sql = IF(@sf_detector_column_exists = 0,
  'ALTER TABLE ai_suggestion_items ADD COLUMN detector_type VARCHAR(30) DEFAULT NULL AFTER source',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;

SET @sf_source_enum = (
  SELECT COLUMN_TYPE FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ai_suggestion_items'
    AND column_name = 'source'
);
SET @sf_sql = IF(@sf_source_enum IS NOT NULL
  AND @sf_source_enum NOT LIKE '%ml_teacher_outlier%',
  'ALTER TABLE ai_suggestion_items MODIFY source ENUM(''llm'',''rule_teacher_outlier'',''ml_teacher_outlier'') NOT NULL DEFAULT ''llm''',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;

SET @sf_column_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'improvement_plans'
    AND column_name = 'suggestion_item_id'
);
SET @sf_sql = IF(@sf_column_exists = 0,
  'ALTER TABLE improvement_plans ADD COLUMN suggestion_item_id BIGINT UNSIGNED DEFAULT NULL',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;

SET @sf_column_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'improvement_plans'
    AND column_name = 'suggestion_baseline_text'
);
SET @sf_sql = IF(@sf_column_exists = 0,
  'ALTER TABLE improvement_plans ADD COLUMN suggestion_baseline_text LONGTEXT DEFAULT NULL',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;

SET @sf_index_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'improvement_plans'
    AND index_name = 'idx_improvement_plans_suggestion_item'
);
SET @sf_sql = IF(@sf_index_exists = 0,
  'ALTER TABLE improvement_plans ADD KEY idx_improvement_plans_suggestion_item (suggestion_item_id)',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;

SET @sf_constraint_exists = (
  SELECT COUNT(*) FROM information_schema.referential_constraints
  WHERE constraint_schema = DATABASE()
    AND constraint_name = 'fk_improvement_plan_suggestion_item'
);
SET @sf_sql = IF(@sf_constraint_exists = 0,
  'ALTER TABLE improvement_plans ADD CONSTRAINT fk_improvement_plan_suggestion_item FOREIGN KEY (suggestion_item_id) REFERENCES ai_suggestion_items (item_id) ON DELETE SET NULL',
  'DO 0');
PREPARE sf_stmt FROM @sf_sql;
EXECUTE sf_stmt;
DEALLOCATE PREPARE sf_stmt;
