<?php
require_once 'config/db.php';
$db = getDB();
$roles = $db->query("SELECT DISTINCT role FROM users")->fetchAll(PDO::FETCH_COLUMN);
echo "Roles found in DB: " . implode(', ', $roles) . "\n";
