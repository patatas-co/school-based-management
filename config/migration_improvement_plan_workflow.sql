-- Improvement Plan review, ownership, approval, and immutable version history.
ALTER TABLE improvement_plans
  ADD COLUMN current_owner_role varchar(30) DEFAULT 'school_head' AFTER workflow_status,
  ADD COLUMN current_owner_user_id int(11) DEFAULT NULL AFTER current_owner_role,
  ADD COLUMN last_action_by int(11) DEFAULT NULL AFTER current_owner_user_id,
  ADD COLUMN last_action_at datetime DEFAULT NULL AFTER last_action_by,
  ADD COLUMN approved_by int(11) DEFAULT NULL AFTER submitted_at,
  ADD COLUMN approved_at datetime DEFAULT NULL AFTER approved_by,
  ADD COLUMN validated_by int(11) DEFAULT NULL AFTER approved_at,
  ADD COLUMN validated_at datetime DEFAULT NULL AFTER validated_by;

CREATE TABLE IF NOT EXISTS improvement_plan_history (
  history_id int(11) NOT NULL AUTO_INCREMENT,
  plan_id int(11) NOT NULL,
  version_no int(11) NOT NULL,
  action varchar(40) NOT NULL,
  from_status varchar(30) DEFAULT NULL,
  to_status varchar(30) DEFAULT NULL,
  actor_id int(11) NOT NULL,
  remarks text DEFAULT NULL,
  snapshot_json longtext NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (history_id),
  UNIQUE KEY uq_plan_version (plan_id, version_no),
  KEY idx_plan_history_plan (plan_id),
  KEY idx_plan_history_actor (actor_id),
  CONSTRAINT fk_plan_history_plan FOREIGN KEY (plan_id) REFERENCES improvement_plans (plan_id) ON DELETE CASCADE,
  CONSTRAINT fk_plan_history_actor FOREIGN KEY (actor_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE improvement_plans
SET current_owner_role = CASE
    WHEN workflow_status = 'submitted' THEN 'sbm_coordinator'
    WHEN workflow_status = 'finalized' THEN NULL
    ELSE 'school_head'
  END,
  last_action_by = COALESCE(submitted_by, created_by),
  last_action_at = COALESCE(submitted_at, updated_at)
WHERE current_owner_role IS NULL;

INSERT INTO improvement_plan_history
  (plan_id, version_no, action, from_status, to_status, actor_id, remarks, snapshot_json)
SELECT plan_id, 1, 'migration_snapshot', NULL, workflow_status,
       COALESCE(submitted_by, created_by), 'Initial workflow history snapshot',
       JSON_OBJECT(
         'plan_id', plan_id, 'school_id', school_id, 'cycle_id', cycle_id,
         'dimension_id', dimension_id, 'indicator_id', indicator_id,
         'priority_level', priority_level, 'objective', objective, 'strategy', strategy,
         'person_responsible', person_responsible, 'target_date', target_date,
         'resources_needed', resources_needed, 'expected_output', expected_output,
         'workflow_status', workflow_status
       )
FROM improvement_plans ip
WHERE NOT EXISTS (SELECT 1 FROM improvement_plan_history h WHERE h.plan_id = ip.plan_id);
