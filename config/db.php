<?php
// config/db.php
date_default_timezone_set('Asia/Manila');

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!$line || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = trim($v, '"\'');
    }
}

define('DB_HOST',    $_ENV['SBM_DB_HOST']    ?? 'localhost');
define('DB_USER',    $_ENV['SBM_DB_USER']    ?? 'root');
define('DB_PASS',    $_ENV['SBM_DB_PASS']    ?? '');
define('DB_NAME',    $_ENV['SBM_DB_NAME']    ?? 'sbm_db');
define('SITE_NAME',  $_ENV['SBM_SITE_NAME']  ?? 'Dasmarinas Integrated High School SBM Online Monitoring System');
define('SITE_SHORT', $_ENV['SBM_SITE_SHORT'] ?? 'DIHS SBM Online Monitoring System');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}