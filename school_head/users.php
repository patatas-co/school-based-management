<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

try {
    $systemAdminCount = (int) getDB()->query("SELECT COUNT(*) FROM users WHERE role='system_admin'")->fetchColumn();
} catch (Exception $e) {
    $systemAdminCount = null;
}

if (($_SESSION['role'] ?? '') === 'system_admin' || (($_SESSION['role'] ?? '') === 'school_head' && $systemAdminCount === 0)) {
    header('Location: ' . baseUrl() . '/system_admin/users.php');
    exit;
}

http_response_code(403);
echo '<div style="font-family:sans-serif;padding:40px;text-align:center;">'
    . '<h2>Access Denied</h2>'
    . '<p>User account management has been moved to the System Admin dashboard.</p>'
    . '<a href="' . htmlspecialchars(baseUrl() . '/' . roleHome($_SESSION['role'] ?? ''), ENT_QUOTES, 'UTF-8') . '">Go to dashboard</a>'
    . '</div>';
