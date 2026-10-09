-- Read-only audit: dimension references and identical suggestion text within a school year.
-- Rows are flagged for review, not automatically corrected.
SELECT
  h.id,
  h.school_id,
  h.school_year,
  h.dimension_id,
  d.dimension_no,
  d.dimension_name,
  h.suggestion_title,
  h.suggestion_text,
  (
    SELECT COUNT(*)
    FROM ai_suggestion_history duplicate_row
    WHERE duplicate_row.school_id = h.school_id
      AND duplicate_row.school_year = h.school_year
      AND duplicate_row.suggestion_text = h.suggestion_text
      AND duplicate_row.suggestion_text <> ''
      AND duplicate_row.id <> h.id
  ) AS identical_text_rows,
  CASE
    WHEN h.suggestion_title REGEXP 'Dimension[[:space:]]*[0-9]+'
      AND h.suggestion_title NOT REGEXP CONCAT('Dimension[[:space:]]*', d.dimension_no, '([^0-9]|$)')
      THEN 'title references a different dimension number'
    WHEN h.suggestion_text REGEXP 'Dimension[[:space:]]*[0-9]+'
      AND h.suggestion_text NOT REGEXP CONCAT('Dimension[[:space:]]*', d.dimension_no, '([^0-9]|$)')
      THEN 'text references a different dimension number'
    ELSE NULL
  END AS dimension_reference_issue
FROM ai_suggestion_history h
JOIN sbm_dimensions d ON d.dimension_id = h.dimension_id
WHERE h.school_year IN ('2023-2024', '2024-2025')
  AND (
    (
      h.suggestion_title REGEXP 'Dimension[[:space:]]*[0-9]+'
      AND h.suggestion_title NOT REGEXP CONCAT('Dimension[[:space:]]*', d.dimension_no, '([^0-9]|$)')
    )
    OR (
      h.suggestion_text REGEXP 'Dimension[[:space:]]*[0-9]+'
      AND h.suggestion_text NOT REGEXP CONCAT('Dimension[[:space:]]*', d.dimension_no, '([^0-9]|$)')
    )
    OR EXISTS (
      SELECT 1
      FROM ai_suggestion_history duplicate_row
      WHERE duplicate_row.school_id = h.school_id
        AND duplicate_row.school_year = h.school_year
        AND duplicate_row.suggestion_text = h.suggestion_text
        AND duplicate_row.suggestion_text <> ''
        AND duplicate_row.id <> h.id
    )
  )
ORDER BY h.school_id, h.school_year, d.dimension_no, h.id;
