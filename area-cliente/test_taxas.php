<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Testing taxas.php...<br>";
try {
    $taxas = require 'config/taxas.php';
    echo "✅ Loaded taxas.php<br>";
    print_r($taxas);
} catch (Throwable $e) {
    echo "❌ Failed to load: " . $e->getMessage();
}
?>
