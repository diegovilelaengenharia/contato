<?php
/**
 * Action: Excluir Cliente
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_cliente'])) {
    $cid = $_GET['delete_cliente'];

    try {
        $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$cid]);
        header("Location: ../../gestao_admin_99.php?msg=client_deleted");
        exit;
    } catch(PDOException $e) {
        die("Erro ao excluir cliente: " . $e->getMessage());
    }
}

header("Location: ../../gestao_admin_99.php");
exit;
