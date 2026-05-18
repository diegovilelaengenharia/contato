<?php
/**
 * Action: Limpar Todo o Histórico do Cliente
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar Auth Admin

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit;
}

$pdo = Database::getInstance();
$cid = $_GET['cliente_id'] ?? null;

if ($cid && isset($_GET['del_all_hist'])) {
    try {
        $pdo->prepare("DELETE FROM processo_movimentos WHERE cliente_id=?")->execute([$cid]);
        header("Location: ../../admin.php?cliente_id=$cid&tab=andamento&msg=all_hist_deleted");
        exit;
    } catch(PDOException $e) {
        die("Erro ao apagar histórico: " . $e->getMessage());
    }
}

header("Location: ../../admin.php");
exit;
