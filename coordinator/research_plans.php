<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();
$rows = $db->query("SELECT plan_id, priority_level, objective, target_date, person_responsible, created_by FROM improvement_plans LIMIT 15")->fetchAll();
print_r($rows);
?>
