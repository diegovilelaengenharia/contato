<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require_once '../db.php';

if (!isset($_SESSION['cliente_id'])) {
    die("No Session");
}
$cliente_id = $_SESSION['cliente_id'];

// 3. FETCH PENDENCIES
$stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY data_criacao DESC");
$stmt_pend->execute([$cliente_id]);
$all_pendencias = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);

$resolvidas = [];
$abertas = [];

foreach($all_pendencias as $p) {
    if($p['status'] == 'resolvido') {
        $resolvidas[] = $p;
    } else {
        $abertas[] = $p;
    }
}

function get_pendency_files($p_id) {
    return [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Skeleton Test</title>
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=3.0">
</head>
<body>
    <h1>Skeleton Test</h1>
    <p>CSS Linked. PHP Logic logic ran. If you see this, basic structure is fine.</p>
    <p>Resolved: <?= count($resolvidas) ?></p>
    <p>Open: <?= count($abertas) ?></p>
</body>
</html>
