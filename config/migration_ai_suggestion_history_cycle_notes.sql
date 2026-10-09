ALTER TABLE ai_suggestion_history
  ADD COLUMN last_cycle_note TEXT DEFAULT NULL AFTER suggestion_text,
  ADD COLUMN is_sample TINYINT(1) NOT NULL DEFAULT 0 AFTER generated_at;
