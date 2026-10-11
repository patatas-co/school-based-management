<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This cleanup script can only run from the command line.\n");
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../config/db.php';

const DEMO_MARKER = 'CATBOOST_DEMO_V1';
const DEMO_BACKEND = 'catboost_demo_v1';
const DEMO_YEAR_LABELS = [
    'CBRANKDEMO-01',
    'CBRANKDEMO-02',
    'CBRANKDEMO-03',
    'CBRANKDEMO-04',
];

function demoPdo(): PDO
{
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET time_zone = '+08:00'");
    return $pdo;
}

try {
    $db = demoPdo();
    $db->beginTransaction();

    $seedYears = $db->prepare(
        'SELECT DISTINCT sy.sy_id
         FROM school_years sy
         JOIN sbm_cycles c ON c.sy_id = sy.sy_id
         WHERE sy.label IN (?, ?, ?, ?) AND c.validator_remarks LIKE ?'
    );
    $seedYears->execute(array_merge(DEMO_YEAR_LABELS, [DEMO_MARKER . ':%']));
    $seedYearIds = array_map('intval', $seedYears->fetchAll(PDO::FETCH_COLUMN));

    $plans = $db->prepare('DELETE FROM improvement_plans WHERE remarks LIKE ?');
    $plans->execute([DEMO_MARKER . ':%']);

    $items = $db->prepare(
        'DELETE FROM ai_suggestion_items
         WHERE generation_id IN (
             SELECT generation_id FROM ai_suggestion_generations
             WHERE is_synthetic = 1 AND backend_used = ?
         )'
    );
    $items->execute([DEMO_BACKEND]);

    $teacherResponses = $db->prepare(
        'DELETE FROM teacher_responses WHERE remarks LIKE ?'
    );
    $teacherResponses->execute([DEMO_MARKER . ':%']);

    $dimensionScores = $db->prepare(
        'DELETE FROM sbm_dimension_scores
         WHERE cycle_id IN (
             SELECT cycle_id FROM sbm_cycles WHERE validator_remarks LIKE ?
         )'
    );
    $dimensionScores->execute([DEMO_MARKER . ':%']);

    $teacherSubmissions = $db->prepare(
        'DELETE FROM teacher_submissions
         WHERE cycle_id IN (
             SELECT cycle_id FROM sbm_cycles WHERE validator_remarks LIKE ?
         )'
    );
    $teacherSubmissions->execute([DEMO_MARKER . ':%']);

    $generations = $db->prepare(
        'DELETE FROM ai_suggestion_generations
         WHERE is_synthetic = 1 AND backend_used = ?'
    );
    $generations->execute([DEMO_BACKEND]);

    $cycles = $db->prepare('DELETE FROM sbm_cycles WHERE validator_remarks LIKE ?');
    $cycles->execute([DEMO_MARKER . ':%']);

    $deleteYear = $db->prepare(
        'DELETE FROM school_years WHERE sy_id = ? AND label IN (?, ?, ?, ?) AND is_current = 0'
    );
    foreach ($seedYearIds as $seedYearId) {
        $deleteYear->execute(array_merge([$seedYearId], DEMO_YEAR_LABELS));
    }
    $db->commit();

    $python = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR
        . 'venv64' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
    $ranker = __DIR__ . DIRECTORY_SEPARATOR . 'suggestion_ranker.py';
    if (!is_file($python)) {
        throw new RuntimeException(
            'Demo DB rows were removed, but the expected Python executable was not found: '
            . $python . '. Remove the demo model with ml\\venv64\\Scripts\\python.exe '
            . 'ml\\suggestion_ranker.py --remove-demo-model.'
        );
    }

    $pipes = [];
    $process = proc_open(
        [$python, $ranker, '--remove-demo-model'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        dirname(__DIR__)
    );
    if (!is_resource($process)) {
        throw new RuntimeException(
            'Demo DB rows were removed, but the demo model cleanup command could not start.'
        );
    }
    fclose($pipes[0]);
    $modelOutput = stream_get_contents($pipes[1]);
    $modelError = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $modelExitCode = proc_close($process);
    if ($modelExitCode !== 0) {
        throw new RuntimeException(
            "Demo DB rows were removed, but saved-model cleanup failed: "
            . trim((string) $modelError . "\n" . (string) $modelOutput)
        );
    }

    echo json_encode([
        'ok' => true,
        'seed' => DEMO_MARKER,
        'database_rows_removed' => true,
        'model_cleanup' => json_decode((string) $modelOutput, true),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Cleanup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
