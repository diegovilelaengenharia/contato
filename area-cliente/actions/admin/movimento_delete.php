<?php
/**
 * Action: Excluir Movimentação (Histórico)
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar Auth Admin (init.php já faz)

// Para deleções via link (GET), não usamos CSRF no momento para manter compatibilidade,
// mas o ideal seria migrar para POST.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit;
}

$pdo = Database::getInstance();

$hid = $_GET['del_hist'] ?? null;
$cid = $_GET['cliente_id'] ?? null;

if ($hid && $cid) {
    try {
        $pdo->prepare("DELETE FROM processo_movimentos WHERE id=? AND cliente_id=?")->execute([$hid, $cid]);
        header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=andamento&msg=hist_deleted");
        exit;
    } catch(PDOException $e) {
        die("Erro ao excluir histórico: " . $e->getMessage());
    }
}

header("Location: ../../gestao_admin_99.php");
exit;
