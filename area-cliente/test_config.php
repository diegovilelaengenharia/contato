<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Testing docs_config.php...<br>";
try {
    $config = require 'config/docs_config.php';
    echo "✅ Loaded docs_config.php<br>";
    print_r($config);
} catch (Throwable $e) {
    echo "❌ Failed to load: " . $e->getMessage();
}
?>
