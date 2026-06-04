<?php
/**
 * Action: Editar Pendência
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar CSRF
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin.php");
    exit;
}

$pdo = Database::getInstance();

$cid = $_POST['cliente_id'];
$pid = $_POST['pendencia_id'];
$texto = trim($_POST['descricao_pendencia']);

try {
    $pdo->prepare("UPDATE processo_pendencias SET descricao = ? WHERE id = ? AND cliente_id = ?")
        ->execute([$texto, $pid, $cid]);

    $_SESSION['flash_message'] = ['text' => 'Pendência atualizada com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=pendencias");
    exit;

} catch(PDOException $e) {
    $_SESSION['flash_message'] = ['text' => 'Erro ao atualizar pendência: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=pendencias");
    exit;
}
