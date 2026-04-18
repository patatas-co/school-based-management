<?php
require_once __DIR__ . '/../config/db.php';
$db = getDB();
$rows = $db->query("SELECT cycle_id, school_id, sy_id, status, created_at FROM sbm_cycles WHERE school_id=1 ORDER BY sy_id, created_at")->fetchAll();
echo "--- CYCLES ---\n";
foreach ($rows as $row) { 
    $c = $db->query("SELECT COUNT(*) FROM improvement_plans WHERE cycle_id={$row['cycle_id']}")->fetchColumn();
    $row['plan_count'] = $c;
    print_r($row); 
}
echo "--- SCHOOL YEARS ---\n";
$sy = $db->query("SELECT sy_id, label, is_current FROM school_years")->fetchAll();
print_r($sy);
?>
