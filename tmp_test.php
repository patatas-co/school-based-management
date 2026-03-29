<?php
require 'config/db.php';
$token = '91fef38ed43d8e93f0979fa05cf650a59f527583b1035b4bcbcc56d773f6f827648519b5...'; // This is from the image
$db = getDB();
$st = $db->query("SELECT * FROM password_setup_tokens ORDER BY created_at DESC LIMIT 5");
print_r($st->fetchAll());
