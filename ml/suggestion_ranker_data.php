<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
require_once __DIR__ . '/../config/db.php';

/**
 * Export linked suggestion-plan decisions for offline training.
 * Synthetic demo rows are excluded unless the CLI explicitly passes
 * --include-demo; draft/submitted plans never become labels.
 */
$db = getDB();
$includeDemo = PHP_SAPI === 'cli'
    && isset($argv)
    && in_array('--include-demo', $argv, true);
$syntheticFilter = $includeDemo
    ? "(COALESCE(g.is_synthetic, 0) = 0
       OR (g.is_synthetic = 1
           AND g.backend_used = 'catboost_demo_v1'
           AND i.title LIKE '[CATBOOST DEMO]%'
           AND i.body_text LIKE 'CATBOOST_DEMO_V1%'))"
    : 'COALESCE(g.is_synthetic, 0) = 0';
$stmt = $db->query("
    SELECT
        i.item_id,
        g.school_id,
        g.cycle_id,
        i.title,
        i.body_text,
        d.dimension_id,
        d.dimension_no,
        d.dimension_name,
        ds.percentage AS dimension_score,
        ip.workflow_status,
        ip.updated_at AS outcome_updated_at
    FROM ai_suggestion_items i
    JOIN ai_suggestion_generations g ON g.generation_id = i.generation_id
    JOIN improvement_plans ip
      ON ip.suggestion_item_id = i.item_id
     AND ip.school_id = g.school_id
    JOIN sbm_dimensions d ON d.dimension_id = ip.dimension_id
    LEFT JOIN sbm_dimension_scores ds
      ON ds.cycle_id = g.cycle_id
     AND ds.school_id = g.school_id
     AND ds.dimension_id = d.dimension_id
    WHERE $syntheticFilter
      AND i.source = 'llm'
      AND g.backend_used IS NOT NULL
      AND g.backend_used NOT IN ('rule_based', 'rule_based_local_fallback')
      AND ip.workflow_status IN ('finalized', 'returned')
    ORDER BY i.item_id, ip.updated_at DESC, ip.plan_id DESC
");

$rows = [];
$seenItems = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $itemId = (int) $row['item_id'];
    if (isset($seenItems[$itemId])) {
        continue;
    }
    $seenItems[$itemId] = true;
    $rows[] = [
        'item_id' => $itemId,
        'school_id' => (int) $row['school_id'],
        'cycle_id' => (int) $row['cycle_id'],
        'title' => (string) $row['title'],
        'body_text' => (string) $row['body_text'],
        'dimension_no' => (int) $row['dimension_no'],
        'dimension_name' => (string) $row['dimension_name'],
        'dimension_score' => $row['dimension_score'] === null
            ? null
            : (float) $row['dimension_score'],
        'workflow_status' => (string) $row['workflow_status'],
        'outcome_updated_at' => (string) $row['outcome_updated_at'],
        'label' => $row['workflow_status'] === 'finalized' ? 1 : 0,
    ];
}

echo json_encode(
    ['rows' => $rows],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
