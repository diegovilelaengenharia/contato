<?php
session_name('CLIENTE_SESSID');
session_start();
require 'db.php';

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
$upload_dir = 'uploads/clientes/' . $cliente_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'pendencia_' . $pendencia_id . '_' . time() . '.' . $ext;
$target = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target)) {
    // 3. Update DB (Mark as pending review or resolved?)
    // For now, we update status to 'em_analise' and save file path if column exists, 
    // OR just create a log note. Assuming simple 'arquivo_retorno' column update or similar.
    // I'll update the description or a specific field. 
    // Safest is to update 'observacoes_cliente' or similar if exists.
    // Let's assume we update status to 'em_analise' so Admin sees it.
    
    $stmt_up = $pdo->prepare("UPDATE processo_pendencias SET status = 'em_analise', data_resolucao = NOW() WHERE id = ?");
    $stmt_up->execute([$pendencia_id]);
    
    // Redirect back to the new client app pendencies page
    header("Location: client-app/pendencias.php?msg=sucesso_upload");
} else {
    header("Location: client-app/pendencias.php?msg=erro_upload");
}
?>
