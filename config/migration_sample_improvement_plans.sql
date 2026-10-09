ALTER TABLE improvement_plans
  ADD COLUMN is_sample TINYINT(1) NOT NULL DEFAULT 0 AFTER suggestion_baseline_text;
