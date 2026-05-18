<?php
/**
 * Action: Alternar Status de Pendência
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
        $curr = $pdo->query("SELECT status FROM processo_pendencias WHERE id=$pid")->fetchColumn();
        $new = ($curr == 'resolvido') ? 'pendente' : 'resolvido';
        
        $pdo->prepare("UPDATE processo_pendencias SET status = ? WHERE id = ? AND cliente_id = ?")
            ->execute([$new, $pid, $cid]);

        header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=pendencias");
        exit;
    } catch(PDOException $e) {
        die("Erro ao alternar status da pendência: " . $e->getMessage());
    }
}

header("Location: ../../gestao_admin_99.php");
exit;
