<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$options = getopt('', ['school-id:']);
$schoolId = filter_var($options['school-id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($schoolId === false || $schoolId === null) {
    fwrite(STDERR, "Usage: php tools/cleanup_ai_suggestion_history_sample.php --school-id=1\n");
    exit(2);
}

try {
    $db = getDB();
    $db->beginTransaction();
    $deleteHistory = $db->prepare('DELETE FROM ai_suggestion_history WHERE school_id = ? AND is_sample = 1');
    $deleteHistory->execute([$schoolId]);
    $deletePlans = $db->prepare('DELETE FROM improvement_plans WHERE school_id = ? AND is_sample = 1');
    $deletePlans->execute([$schoolId]);

    $clearFinal = $db->prepare('UPDATE ai_suggestion_history SET is_final = 0 WHERE school_id = ?');
    $clearFinal->execute([$schoolId]);
    $latestRows = $db->prepare("
        SELECT h.id, h.school_year, d.dimension_no
        FROM ai_suggestion_history h
        JOIN sbm_dimensions d ON d.dimension_id = h.dimension_id
        WHERE h.school_id = ?
        ORDER BY h.school_year, d.dimension_no, h.generated_at DESC, h.id DESC
    ");
    $latestRows->execute([$schoolId]);
    $markFinal = $db->prepare('UPDATE ai_suggestion_history SET is_final = 1 WHERE id = ? AND school_id = ?');
    $seen = [];
    foreach ($latestRows->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = (string) $row['school_year'] . ':' . (int) $row['dimension_no'];
        if (isset($seen[$key])) {
            continue;
        }
        $markFinal->execute([(int) $row['id'], $schoolId]);
        $seen[$key] = true;
    }
    $db->commit();
    fwrite(STDOUT, "Removed sample suggestion and plan rows for school_id $schoolId and restored latest available history rows.\n");
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Sample cleanup failed: ' . $e->getMessage() . "\n");
    exit(1);
}
