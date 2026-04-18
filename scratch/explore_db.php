<?php
require_once 'config/db.php';
$db = getDB();
echo "--- Tables ---\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "- $t\n";
}

echo "\n--- Columns of potential tables ---\n";
$targets = ['improvement_plans', 'manual_improvement_plans', 'sbm_plans', 'school_improvement_plans'];
foreach ($targets as $t) {
    if (in_array($t, $tables)) {
        echo "\nTable: $t\n";
        $cols = $db->query("DESCRIBE $t")->fetchAll();
        foreach ($cols as $c) {
            echo "  {$c['Field']} ({$c['Type']})\n";
        }
    }
}
