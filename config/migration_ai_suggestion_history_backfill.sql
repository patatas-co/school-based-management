-- Backfill the per-year history repository from existing AI suggestion records.
-- Safe to run more than once: existing item/dimension pairs are not duplicated.

INSERT INTO ai_suggestion_history
  (school_id, school_year, dimension_id, suggestion_title, suggestion_text,
   priority_label, decline_risk, data_status, score_at_time, model_version,
   is_final, generated_by, generated_at)
SELECT
  g.school_id,
  sy.label,
  ind.dimension_id,
  i.title,
  i.body_text,
  'Medium',
  'medium',
  CASE WHEN i.confidence IS NULL OR i.confidence < 60 THEN 'insufficient' ELSE 'sufficient' END,
  (
    SELECT ROUND(AVG(ds.percentage), 2)
    FROM sbm_dimension_scores ds
    WHERE ds.cycle_id = g.cycle_id
      AND ds.dimension_id = ind.dimension_id
  ),
  COALESCE(g.backend_used, 'legacy'),
  0,
  g.user_id,
  g.generated_at
FROM ai_suggestion_items i
JOIN ai_suggestion_generations g ON g.generation_id = i.generation_id
JOIN sbm_cycles c ON c.cycle_id = g.cycle_id
JOIN school_years sy ON sy.sy_id = c.sy_id
JOIN sbm_indicators ind
  ON JSON_SEARCH(i.indicator_codes, 'one', ind.indicator_code) IS NOT NULL
JOIN (
  SELECT i2.generation_id, ind2.dimension_id, MIN(i2.item_id) AS item_id
  FROM ai_suggestion_items i2
  JOIN sbm_indicators ind2
    ON JSON_SEARCH(i2.indicator_codes, 'one', ind2.indicator_code) IS NOT NULL
  WHERE i2.indicator_codes IS NOT NULL
    AND JSON_VALID(i2.indicator_codes)
  GROUP BY i2.generation_id, ind2.dimension_id
) chosen
  ON chosen.generation_id = i.generation_id
 AND chosen.dimension_id = ind.dimension_id
 AND chosen.item_id = i.item_id
WHERE i.indicator_codes IS NOT NULL
  AND JSON_VALID(i.indicator_codes)
  AND NOT EXISTS (
    SELECT 1
    FROM ai_suggestion_history h
    WHERE h.school_id = g.school_id
      AND h.school_year = sy.label
      AND h.dimension_id = ind.dimension_id
      AND h.generated_at = g.generated_at
  );

DELETE duplicate_history
FROM ai_suggestion_history duplicate_history
JOIN ai_suggestion_history retained_history
  ON retained_history.school_id = duplicate_history.school_id
 AND retained_history.school_year = duplicate_history.school_year
 JOIN sbm_dimensions duplicate_dimension
   ON duplicate_dimension.dimension_id = duplicate_history.dimension_id
 JOIN sbm_dimensions retained_dimension
   ON retained_dimension.dimension_id = retained_history.dimension_id
  AND retained_dimension.dimension_no = duplicate_dimension.dimension_no
 AND (
   retained_history.generated_at > duplicate_history.generated_at
   OR (
     retained_history.generated_at = duplicate_history.generated_at
     AND retained_history.id > duplicate_history.id
   )
 )
WHERE duplicate_history.id <> retained_history.id;

UPDATE ai_suggestion_history h
JOIN (
  SELECT h1.school_id, h1.school_year, MAX(h1.generated_at) AS latest_generated_at
  FROM ai_suggestion_history h1
  JOIN sbm_dimensions d1 ON d1.dimension_id = h1.dimension_id
  GROUP BY h1.school_id, h1.school_year
) latest
  ON latest.school_id = h.school_id
 AND latest.school_year = h.school_year
SET h.is_final = (h.generated_at = latest.latest_generated_at);

UPDATE ai_suggestion_history h
JOIN sbm_dimensions history_dimension ON history_dimension.dimension_id = h.dimension_id
SET h.score_at_time = (
  SELECT ROUND(AVG(ds.percentage), 2)
  FROM sbm_dimension_scores ds
  JOIN sbm_cycles c ON c.cycle_id = ds.cycle_id
  JOIN school_years sy ON sy.sy_id = c.sy_id
  JOIN sbm_dimensions score_dimension ON score_dimension.dimension_id = ds.dimension_id
  WHERE c.school_id = h.school_id
    AND sy.label = h.school_year
    AND score_dimension.dimension_no = history_dimension.dimension_no
    AND c.status IN ('validated', 'finalized', 'completed')
    AND c.cycle_id = (
      SELECT c_latest.cycle_id
      FROM sbm_cycles c_latest
      JOIN school_years sy_latest ON sy_latest.sy_id = c_latest.sy_id
      WHERE c_latest.school_id = h.school_id
        AND sy_latest.label = h.school_year
        AND c_latest.status IN ('validated', 'finalized', 'completed')
      ORDER BY COALESCE(c_latest.finalized_at, c_latest.created_at) DESC, c_latest.cycle_id DESC
      LIMIT 1
    )
)
WHERE h.score_at_time IS NULL;
