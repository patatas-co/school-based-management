<?php
/**
 * ml_service.php
 * PHP ↔ Python ML microservice bridge.
 * Called after assessment finalization or from the analytics dashboard.
 */

define('ML_SERVICE_URL', getenv('ML_SERVICE_URL') ?: 'http://127.0.0.1:5000');
define('ML_SECRET',      getenv('ML_SECRET')      ?: 'sbm-ml-secret-change-in-production');

function ml_post(string $endpoint, array $payload): ?array
{
    $ch = curl_init(ML_SERVICE_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-ML-Secret: ' . ML_SECRET,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $body  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$body) {
        error_log("ML service error: $error");
        return null;
    }
    return json_decode($body, true);
}

/**
 * Triggered after a cycle is finalized/submitted.
 * Stores results in MySQL via ml_comment_analysis + ml_predictions
 * + ml_recommendations tables.
 */
function runMLPipeline(PDO $db, int $cycleId): bool
{
    // 1. Gather dim scores
    $dimQ = $db->prepare("
        SELECT d.dimension_no, ds.percentage
        FROM sbm_dimension_scores ds
        JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
        WHERE ds.cycle_id = ?
    ");
    $dimQ->execute([$cycleId]);
    $dimScores = [];
    foreach ($dimQ->fetchAll() as $row) {
        $dimScores[(int)$row['dimension_no']] = (float)$row['percentage'];
    }

    // 2. Gather indicator averages
    $indQ = $db->prepare("
        SELECT i.indicator_code, i.indicator_text, d.dimension_no,
               ROUND(AVG(r.rating), 2) avg_rating
        FROM sbm_responses r
        JOIN sbm_indicators i ON r.indicator_id = i.indicator_id
        JOIN sbm_dimensions d ON i.dimension_id = d.dimension_id
        WHERE r.cycle_id = ?
        GROUP BY i.indicator_id
    ");
    $indQ->execute([$cycleId]);
    $indicators = $indQ->fetchAll();

    // 3. Gather historical scores for trend
    $histQ = $db->prepare("
        SELECT YEAR(c.submitted_at) cycle_year, c.overall_score
        FROM sbm_cycles c
        JOIN sbm_dimension_scores ds ON c.cycle_id = ds.cycle_id
        WHERE c.school_id = (SELECT school_id FROM sbm_cycles WHERE cycle_id = ?)
          AND c.status = 'validated'
          AND c.cycle_id != ?
        GROUP BY c.cycle_id
        ORDER BY c.submitted_at ASC
        LIMIT 5
    ");
    $histQ->execute([$cycleId, $cycleId]);
    $history = $histQ->fetchAll();

    // 4. Gather text remarks (teacher + stakeholder)
    $commQ = $db->prepare("
        SELECT remarks AS text FROM teacher_responses
        WHERE cycle_id = ? AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT remarks AS text FROM stakeholder_responses
        WHERE cycle_id = ? AND remarks IS NOT NULL AND remarks != ''
    ");
    $commQ->execute([$cycleId, $cycleId]);
    $comments = $commQ->fetchAll();

    // 5. Get school info
    $metaQ = $db->prepare("
        SELECT s.school_name, sy.label sy_label
        FROM sbm_cycles c
        JOIN schools s ON c.school_id = s.school_id
        JOIN school_years sy ON c.sy_id = sy.sy_id
        WHERE c.cycle_id = ?
    ");
    $metaQ->execute([$cycleId]);
    $meta = $metaQ->fetch();

    // 6. Call Python
    $result = ml_post('/api/full_pipeline', [
        'dim_scores'  => $dimScores,
        'indicators'  => $indicators,
        'history'     => $history,
        'comments'    => $comments,
        'school_name' => $meta['school_name'] ?? 'School',
        'sy_label'    => $meta['sy_label']    ?? '',
    ]);

    if (!$result) return false;

    // 7. Store results
    saveMLResults($db, $cycleId, $result);
    return true;
}

function saveMLResults(PDO $db, int $cycleId, array $result): void
{
    $gap     = $result['score_analysis']['gap_analysis']       ?? [];
    $comms   = $result['comment_analysis']                     ?? [];
    $recs    = $result['recommendations']['recommendations']   ?? '';

    // ml_predictions (uses your existing table)
    if (!empty($gap['weakest_dimensions'])) {
        foreach ($gap['weakest_dimensions'] as $wd) {
            $db->prepare("
                INSERT INTO ml_predictions
                    (school_id, cycle_id, prediction_type,
                     predicted_value, risk_level, recommendation, confidence_score)
                SELECT school_id, ?, 'risk_flag', ?, ?, ?, 0.75
                FROM sbm_cycles WHERE cycle_id = ?
                ON DUPLICATE KEY UPDATE
                    risk_level = VALUES(risk_level),
                    recommendation = VALUES(recommendation)
            ")->execute([
                $cycleId,
                $wd['score'],
                $wd['priority'] === 'high' ? 'high' : 'medium',
                "Dimension {$wd['dimension_no']} ({$wd['dimension_name']}) " .
                "is at {$wd['score']}% ({$wd['maturity']} level). " .
                "Gap from average: {$wd['gap_from_avg']}%.",
                $cycleId,
            ]);
        }
    }

    // ml_recommendations (new table — see SQL below)
    if ($recs) {
        $db->prepare("
            INSERT INTO ml_recommendations
                (cycle_id, recommendation_text, generated_by,
                 top_topics, has_urgent, sentiment_summary)
            SELECT ?, ?, ?, ?, ?, ?
            FROM sbm_cycles WHERE cycle_id = ?
            ON DUPLICATE KEY UPDATE
                recommendation_text = VALUES(recommendation_text),
                generated_at        = NOW()
        ")->execute([
            $cycleId,
            $recs,
            $result['recommendations']['backend_used'] ?? 'rule_based',
            json_encode($comms['top_topics'] ?? []),
            ($comms['has_urgent'] ?? false) ? 1 : 0,
            json_encode($comms['sentiment_counts'] ?? []),
            $cycleId,
        ]);
    }
}