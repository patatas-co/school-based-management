ALTER TABLE ai_suggestion_history
  ADD COLUMN needs_review TINYINT(1) NOT NULL DEFAULT 0 AFTER is_final;
