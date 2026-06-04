<?php
session_name('CLIENTE_SESSID');
session_start();
require 'db.php';
require_once __DIR__ . '/core/Upload.php';

if (!isset($_SESSION['cliente_id']) || !isset($_FILES['arquivo']) || !isset($_POST['pendencia_id'])) {
    header("Location: dashboard.php?msg=erro");
    exit;
}

$pendencia_id = (int)$_POST['pendencia_id'];
$cliente_id = $_SESSION['cliente_id'];
$file = $_FILES['arquivo'];

// 1. Verify Ownership
$stmt = $pdo->prepare("SELECT id FROM processo_pendencias WHERE id = ? AND cliente_id = ?");
$stmt->execute([$pendencia_id, $cliente_id]);
if(!$stmt->fetch()) {
    die("Acesso negado.");
}

// 2. Upload Logic
$upload_dir = __DIR__ . '/uploads/clientes/' . $cliente_id . '/';

$res = Upload::process($file, $upload_dir, 'pendencia_' . $pendencia_id);
if ($res['success']) {
    $db_path = 'uploads/clientes/' . $cliente_id . '/' . basename($res['file_path']);
    
    // 3. Update DB status and file path
    $stmt_up = $pdo->prepare("UPDATE processo_pendencias SET status = 'em_analise', arquivo_path = ?, data_resolucao = NOW() WHERE id = ?");
    $stmt_up->execute([$db_path, $pendencia_id]);
    
    // Redirect back to the new client app pendencies page
    header("Location: client-app/pendencias.php?msg=sucesso_upload");
} else {
    header("Location: client-app/pendencias.php?msg=erro_upload");
}
?>
