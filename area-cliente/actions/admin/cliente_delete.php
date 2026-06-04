<?php
/**
 * Action: Excluir Cliente
 * Reescrito em SEC-07/ADM-16: POST + CSRF obrigatório.
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/index.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF).', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

$cid = (int)($_POST['cliente_id'] ?? 0);
if (!$cid) {
    $_SESSION['flash_message'] = ['text' => 'ID do cliente inválido.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}

$pdo = Database::getInstance();
try {
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$cid]);
    Logger::log('DELETE', 'cliente', $cid, []);
    $_SESSION['flash_message'] = ['text' => 'Cliente excluído permanentemente.', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
} catch (PDOException $e) {
    $_SESSION['flash_message'] = ['text' => 'Erro ao excluir cliente.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}
