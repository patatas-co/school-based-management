<?php
// config/db.php
date_default_timezone_set('Asia/Manila');

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!$line || strncmp(ltrim($line), '#', 1) === 0 || strpos($line, '=') === false)
            continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = preg_replace('/\s+#.*$/', '', $v);
        $v = trim($v, '"\'');
        // Strip spaces from password fields (App Passwords are space-free)
        if (substr($k, -5) === '_PASS' || substr($k, -9) === '_PASSWORD') {
            $v = str_replace(' ', '', $v);
        }
        $_ENV[$k] = $v;
    }
}

if (session_status() === PHP_SESSION_NONE)
    session_start();

define('DB_HOST', $_ENV['SBM_DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['SBM_DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['SBM_DB_PASS'] ?? '');
define('DB_NAME', $_ENV['SBM_DB_NAME'] ?? 'sbm_db');
define('SITE_NAME', $_ENV['SBM_SITE_NAME'] ?? 'Dasmariñas Integrated High School SBM Online Monitoring System');
define('SITE_SHORT', $_ENV['SBM_SITE_SHORT'] ?? 'DIHS SBM Portal');

// Force UTF-8 content-type header for HTML responses only.
// Skip for POST/AJAX requests so JSON API handlers can set their own Content-Type.
$isApiRequest = (
    $_SERVER['REQUEST_METHOD'] === 'POST' ||
    (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') ||
    (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
);
if (!headers_sent() && !$isApiRequest) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (!defined('SCHOOL_ID')) {
    define('SCHOOL_ID', (int) ($_SESSION['school_id'] ?? 1));
}

define('SCHOOL_NAME', 'Dasmariñas Integrated High School');
define('SCHOOL_DEPED_ID', '301143');
define('SCHOOL_ADDRESS', 'Dasmariñas City, Cavite');
define('SCHOOL_HEAD', 'Maria Santos');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        try {
            // test connection is alive
            $pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            $pdo = null;
        }
    }
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}