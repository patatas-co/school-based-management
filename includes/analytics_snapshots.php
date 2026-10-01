<?php
declare(strict_types=1);

function saveAnalyticsSnapshotsForCycle(PDO $db, int $cycleId, ?string $snapshotAt = null): int
{
    $cycleStmt = $db->prepare("
        SELECT c.school_id, c.sy_id, c.overall_score, c.maturity_level, sy.label AS sy_label
        FROM sbm_cycles c
        JOIN school_years sy ON sy.sy_id = c.sy_id
        WHERE c.cycle_id = ?
        LIMIT 1
    ");
    $cycleStmt->execute([$cycleId]);
    $cycle = $cycleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$cycle) {
        throw new RuntimeException("Cannot snapshot analytics for missing cycle $cycleId.");
    }

    $dimensionStmt = $db->prepare("
        SELECT ds.dimension_id, ds.percentage, ds.raw_score, ds.max_score,
               d.dimension_no, d.dimension_name
        FROM sbm_dimension_scores ds
        JOIN sbm_dimensions d ON ds.dimension_id = d.dimension_id
        WHERE ds.cycle_id = ?
        ORDER BY d.dimension_no
    ");
    $dimensionStmt->execute([$cycleId]);
    $dimensions = $dimensionStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$dimensions) {
        throw new RuntimeException("Cannot snapshot analytics for cycle $cycleId without dimension scores.");
    }

    $insert = $db->prepare("
        INSERT IGNORE INTO analytics_snapshots
            (school_id, cycle_id, sy_id, sy_label, dimension_id, dimension_no,
             dimension_name, percentage, raw_score, max_score, overall_score,
             maturity_level, snapshot_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))
    ");
    foreach ($dimensions as $dimension) {
        $insert->execute([
            $cycle['school_id'],
            $cycleId,
            $cycle['sy_id'],
            $cycle['sy_label'],
            $dimension['dimension_id'],
            $dimension['dimension_no'],
            $dimension['dimension_name'],
            $dimension['percentage'],
            $dimension['raw_score'],
            $dimension['max_score'],
            $cycle['overall_score'],
            $cycle['maturity_level'],
            $snapshotAt,
        ]);
    }

    return count($dimensions);
}
