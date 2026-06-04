<?php
/**
 * Ação Admin: Personificar Cliente
 * Permite ao admin visualizar o portal como se fosse o cliente.
 * Refatorado em SEC-09 para usar Auth::initSession() e Database::getInstance().
 */
require_once __DIR__ . '/../../includes/init.php';

// init.php já garante: sessão segura, admin logado, $pdo disponível

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    $_SESSION['flash_message'] = ['text' => 'ID do cliente inválido para personificação.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

if ($cliente) {
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome'];
    $_SESSION['impersonating'] = true; // flag para o portal saber
    header("Location: ../../client-app/index.php");
    exit;
}

$_SESSION['flash_message'] = ['text' => 'Cliente não encontrado.', 'type' => 'error'];
header("Location: ../../admin/index.php");
exit;
