<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing Database Connection...\n";

try {
    require __DIR__ . '/area-cliente/db.php';
    if (isset($pdo)) {
        echo "Database connection successful!\n";
        $stmt = $pdo->query("SELECT VERSION()");
        echo "MySQL Version: " . $stmt->fetchColumn() . "\n";
    } else {
        echo "Error: \$pdo variable not set after including db.php\n";
    }
} catch (Throwable $e) {
    echo "Connection Failed: " . $e->getMessage() . "\n";
}
