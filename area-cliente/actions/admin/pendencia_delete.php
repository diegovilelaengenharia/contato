<?php
/**
 * Action: Excluir Pendência
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pid']) && isset($_GET['cid'])) {
    $pid = $_GET['pid'];
    $cid = $_GET['cid'];

    try {
        $pdo->prepare("DELETE FROM processo_pendencias WHERE id = ? AND cliente_id = ?")->execute([$pid, $cid]);
        header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=pendencias&msg=pend_deleted");
        exit;
    } catch(PDOException $e) {
        die("Erro ao excluir pendência: " . $e->getMessage());
    }
}

header("Location: ../../gestao_admin_99.php");
exit;
