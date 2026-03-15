<?php
// debug_ml.php - DELETE THIS FILE AFTER DEBUGGING

echo "<h2>ML Pipeline Debug</h2>";

// 1. Check if exec() is available
echo "<h3>1. exec() function</h3>";
if (function_exists('exec')) {
    echo "✅ exec() is available<br>";
} else {
    echo "❌ exec() is DISABLED in php.ini<br>";
}

// 2. Check if shell_exec() is available
echo "<h3>2. shell_exec() function</h3>";
if (function_exists('shell_exec')) {
    $phpVersion = shell_exec("php --version 2>&1");
    if ($phpVersion) {
        echo "✅ shell_exec() works<br>";
        echo "PHP CLI output: <pre>" . htmlspecialchars($phpVersion) . "</pre>";
    } else {
        echo "❌ shell_exec() returned nothing — PHP not in PATH<br>";
    }
} else {
    echo "❌ shell_exec() is DISABLED<br>";
}

// 3. Check if ml_recommendations table exists
echo "<h3>3. ml_recommendations table</h3>";
require_once __DIR__ . '/config/db.php';
$db = getDB();
try {
    $db->query("SELECT 1 FROM ml_recommendations LIMIT 1");
    echo "✅ Table exists<br>";
} catch (Exception $e) {
    echo "❌ Table MISSING: " . $e->getMessage() . "<br>";
}

// 4. Check if ml_predictions table exists
echo "<h3>4. ml_predictions table</h3>";
try {
    $db->query("SELECT 1 FROM ml_predictions LIMIT 1");
    echo "✅ Table exists<br>";
} catch (Exception $e) {
    echo "❌ Table MISSING: " . $e->getMessage() . "<br>";
}

// 5. Check if run_pipeline.php exists
echo "<h3>5. run_pipeline.php script</h3>";
$script = __DIR__ . '/ml/run_pipeline.php';
if (file_exists($script)) {
    echo "✅ File exists at: " . $script . "<br>";
} else {
    echo "❌ File NOT found at: " . $script . "<br>";
}

// 6. Try running the script directly
echo "<h3>6. Try running ML script manually</h3>";
$cycleId = 4; // change this to a real cycle_id in your database
$mlScript = escapeshellarg($script);
$mlArg    = escapeshellarg((string)$cycleId);
$output   = shell_exec("php $mlScript $mlArg 2>&1");
if ($output) {
    echo "Output: <pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "No output (either succeeded silently or exec is broken)<br>";
}

// 7. Check latest cycle in database
echo "<h3>7. Latest cycle info</h3>";
$cycle = $db->query("SELECT cycle_id, school_id, status, overall_score FROM sbm_cycles ORDER BY cycle_id DESC LIMIT 1")->fetch();
if ($cycle) {
    echo "Latest cycle_id: <strong>" . $cycle['cycle_id'] . "</strong><br>";
    echo "Status: " . $cycle['status'] . "<br>";
    echo "Score: " . $cycle['overall_score'] . "<br>";
} else {
    echo "No cycles found<br>";
}

// 8. Check if ml_recommendations has any data
echo "<h3>8. Existing ML recommendations</h3>";
try {
    $recs = $db->query("SELECT rec_id, cycle_id, generated_by, generated_at FROM ml_recommendations")->fetchAll();
    if ($recs) {
        foreach ($recs as $r) {
            echo "rec_id: {$r['rec_id']} | cycle: {$r['cycle_id']} | by: {$r['generated_by']} | at: {$r['generated_at']}<br>";
        }
    } else {
        echo "Table exists but is empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr><p style='color:red;'><strong>DELETE debug_ml.php after you're done!</strong></p>";