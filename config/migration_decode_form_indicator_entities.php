<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/db.php';

$db = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]
);
$db->exec("SET time_zone = '+08:00'");

$migrationKey = 'form_indicator_entities_decoded_v1';
$lockName = 'migration_form_indicator_entities_decoded_v1';
$lockAcquired = false;
$exitCode = 0;

try {
    $lock = $db->prepare('SELECT GET_LOCK(?, 10)');
    $lock->execute([$lockName]);
    $lockAcquired = (int)$lock->fetchColumn() === 1;
    if (!$lockAcquired) {
        throw new RuntimeException('Could not acquire the migration lock.');
    }

    $check = $db->prepare('SELECT setting_value FROM system_settings WHERE setting_key=? LIMIT 1');
    $check->execute([$migrationKey]);
    if ($check->fetchColumn() !== false) {
        echo "Form indicator entity cleanup has already been applied.\n";
    } else {
        $db->beginTransaction();

        $rows = $db->query("
            SELECT indicator_id, indicator_text, mov_guide
            FROM sbm_indicators
            WHERE form_version_id IS NOT NULL
        ");
        $update = $db->prepare("
            UPDATE sbm_indicators
            SET indicator_text=?, mov_guide=?
            WHERE indicator_id=?
        ");
        $updated = 0;

        foreach ($rows as $row) {
            $indicatorText = html_entity_decode($row['indicator_text'], ENT_QUOTES, 'UTF-8');
            $movGuide = $row['mov_guide'] === null
                ? null
                : html_entity_decode($row['mov_guide'], ENT_QUOTES, 'UTF-8');

            if ($indicatorText !== $row['indicator_text'] || $movGuide !== $row['mov_guide']) {
                $update->execute([$indicatorText, $movGuide, $row['indicator_id']]);
                $updated++;
            }
        }

        $markApplied = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (?, '1')
        ");
        $markApplied->execute([$migrationKey]);
        $db->commit();

        echo "Decoded entity-escaped text in $updated form indicators.\n";
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Form indicator entity cleanup failed: ' . $e->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($lockAcquired) {
        $release = $db->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
}

exit($exitCode);
