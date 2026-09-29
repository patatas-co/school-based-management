ALTER TABLE teacher_indicator_assignments
  DROP INDEX unique_teacher_indicator,
  ADD UNIQUE KEY unique_teacher_cycle_indicator (teacher_id, indicator_code, cycle_id);