CREATE TABLE IF NOT EXISTS improvement_plan_indicators (
  plan_id INT NOT NULL,
  indicator_id INT NOT NULL,
  PRIMARY KEY (plan_id, indicator_id),
  KEY idx_improvement_plan_indicators_indicator (indicator_id),
  CONSTRAINT fk_plan_indicators_plan
    FOREIGN KEY (plan_id) REFERENCES improvement_plans (plan_id) ON DELETE CASCADE,
  CONSTRAINT fk_plan_indicators_indicator
    FOREIGN KEY (indicator_id) REFERENCES sbm_indicators (indicator_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO improvement_plan_indicators (plan_id, indicator_id)
SELECT plan_id, indicator_id
FROM improvement_plans
WHERE indicator_id IS NOT NULL;
