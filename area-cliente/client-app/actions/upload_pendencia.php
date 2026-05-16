<?php
session_name('CLIENTE_SESSID');
session_start();
require '../../db.php';

if (!isset($_SESSION['cliente_id']) || !isset($_FILES['arquivo']) || !isset($_POST['pendencia_id'])) {
    header("Location: ../index.php?msg=erro");
    exit;
}
// ...
// 2. Upload Logic
$upload_dir = '../../uploads/clientes/' . $cliente_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
// ...
    $stmt_up = $pdo->prepare("UPDATE processo_pendencias SET status = 'em_analise', data_resolucao = NOW() WHERE id = ?");
    $stmt_up->execute([$pendencia_id]);
    
    header("Location: ../index.php?msg=sucesso_upload");
} else {
    header("Location: ../index.php?msg=erro_upload");
}
?>
