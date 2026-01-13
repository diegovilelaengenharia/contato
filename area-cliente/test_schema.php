<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "<h1>Testing schema.php...</h1>";

try {
    require_once 'db.php'; // Needed for $pdo
    require 'includes/schema.php';
    echo "<h2 style='color:green'>✅ Loaded schema.php successfully!</h2>";
} catch (Throwable $e) {
    echo "<h2 style='color:red'>❌ Failed to load schema.php</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
