<?php
// ENABLE DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Step 1: File Loaded</h1>";

session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
echo "Session Started.<br>";

require_once '../db.php';
echo "DB Included.<br>";

// 1. AUTHENTICATION
if (!isset($_SESSION['cliente_id'])) {
    // header("Location: ../index.php");
    echo "No Session ID (redirect disabled for debug).<br>";
    exit;
}
$cliente_id = $_SESSION['cliente_id'];
echo "Client ID: $cliente_id<br>";

// 2. LOGIC: COMMENTED OUT FOR DEBUGGING
/*
// LOGIC HERE...
*/

// 3. FETCH PENDENCIES
echo "Fetching Pendencies...<br>";
$stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY data_criacao DESC");
$stmt_pend->execute([$cliente_id]);
$all_pendencias = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);
echo "Pendencies Fetched: " . count($all_pendencias) . "<br>";

// SEPARATE LISTS
$resolvidas = [];
$abertas = [];

foreach($all_pendencias as $p) {
    if($p['status'] == 'resolvido') {
        $resolvidas[] = $p;
    } else {
        $abertas[] = $p;
    }
}

echo "Logic Complete. Rendering HTML...<br>";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug Mode</title>
</head>
<body>
    <h1>Debug Mode Active</h1>
    <p>If you see this, the PHP header logic is fine.</p>
</body>
</html>
