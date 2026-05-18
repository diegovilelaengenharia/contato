<?php
/**
 * Ação Admin: Personificar Cliente
 * Permite ao admin visualizar o portal como se fosse o cliente.
 */
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require '../../db.php';

// Verificar se o admin está logado
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    die("Acesso negado. Apenas administradores podem personificar clientes.");
}

if (isset($_GET['id'])) {
    $cliente_id = (int)$_GET['id'];
    
    // Buscar cliente para confirmar existência
    $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        // Define o ID do cliente na sessão, mantendo admin_logado = true
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nome'] = $cliente['nome'];
        
        // Redireciona para o portal do cliente
        header("Location: ../../client-app/index.php");
        exit;
    }
}

// Fallback: volta para o admin
header("Location: ../../admin.php");
exit;
