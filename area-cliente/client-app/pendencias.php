<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostico Pendencias</h1>";

// 1. Session
echo "Starting Session...<br>";
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
echo "Session Started. Client ID: " . (isset($_SESSION['cliente_id']) ? $_SESSION['cliente_id'] : 'Not Set') . "<br>";

// 2. DB Inclusion
echo "Including DB...<br>";
$db_path = '../db.php';
if(file_exists($db_path)) {
    echo "DB File Found.<br>";
    require_once $db_path;
    echo "DB Included.<br>";
} else {
    echo "DB File NOT Found at $db_path<br>";
    exit;
}

// 3. DB Check
if(isset($pdo)) {
    echo "PDO Object Exists.<br>";
} else {
    echo "PDO Object Missing.<br>";
}

echo "Diagnostics Complete. If you see this, the server is executing PHP correctly.";
?>
