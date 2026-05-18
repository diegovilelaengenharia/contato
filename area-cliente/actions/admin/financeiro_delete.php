<?php
/**
 * Action: Excluir Lançamento Financeiro
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['del_fin']) && isset($_GET['cliente_id'])) {
    $fid = $_GET['del_fin'];
    $cid = $_GET['cliente_id'];

    try {
        $pdo->prepare("DELETE FROM processo_financeiro WHERE id=? AND cliente_id=?")->execute([$fid, $cid]);
        header("Location: ../../admin.php?cliente_id=$cid&tab=financeiro&msg=fin_deleted");
        exit;
    } catch(PDOException $e) {
        die("Erro ao excluir lançamento financeiro: " . $e->getMessage());
    }
}

header("Location: ../../admin.php");
exit;
