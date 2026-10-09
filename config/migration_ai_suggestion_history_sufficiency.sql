-- Recalculate historical data_status from dimension score availability and
-- answer coverage in the latest completed cycle for each school year.
-- Sufficient means a non-NULL dimension score and at least half of that
-- form-version's active indicators answered by the School Head or submitted
-- by teachers.
UPDATE ai_suggestion_history h
JOIN sbm_dimensions history_dimension
  ON history_dimension.dimension_id = h.dimension_id
LEFT JOIN (
  SELECT
    c.school_id,
    sy.label AS school_year,
    d.dimension_no,
    MAX(ds.percentage) AS dimension_score,
    COUNT(DISTINCT i.indicator_id) AS total_indicators,
    COUNT(DISTINCT answered.indicator_id) AS answered_indicators
  FROM sbm_cycles c
  JOIN school_years sy ON sy.sy_id = c.sy_id
  JOIN sbm_dimension_scores ds
    ON ds.cycle_id = c.cycle_id
   AND ds.school_id = c.school_id
  JOIN sbm_dimensions d ON d.dimension_id = ds.dimension_id
  LEFT JOIN sbm_indicators i
    ON i.dimension_id = d.dimension_id
   AND i.form_version_id = d.form_version_id
   AND i.is_active = 1
  LEFT JOIN (
    SELECT cycle_id, school_id, indicator_id
    FROM sbm_responses
    WHERE rating IS NOT NULL
    UNION
    SELECT cycle_id, school_id, indicator_id
    FROM teacher_responses
    WHERE rating IS NOT NULL
      AND status = 'submitted'
  ) answered
    ON answered.cycle_id = c.cycle_id
   AND answered.school_id = c.school_id
   AND answered.indicator_id = i.indicator_id
  WHERE c.status IN ('validated', 'finalized', 'completed')
    AND c.cycle_id = (
      SELECT c_latest.cycle_id
      FROM sbm_cycles c_latest
      WHERE c_latest.school_id = c.school_id
        AND c_latest.sy_id = c.sy_id
        AND c_latest.status IN ('validated', 'finalized', 'completed')
      ORDER BY COALESCE(c_latest.finalized_at, c_latest.created_at) DESC,
               c_latest.cycle_id DESC
      LIMIT 1
    )
  GROUP BY c.school_id, sy.label, d.dimension_no
) coverage
  ON coverage.school_id = h.school_id
 AND coverage.school_year = h.school_year
 AND coverage.dimension_no = history_dimension.dimension_no
SET h.data_status = CASE
  WHEN coverage.dimension_score IS NOT NULL
   AND coverage.total_indicators > 0
   AND coverage.answered_indicators >= CEIL(coverage.total_indicators / 2)
  THEN 'sufficient'
  ELSE 'insufficient'
END;
