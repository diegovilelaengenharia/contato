<?php
/**
 * Action: Atualizar Status Financeiro
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Csrf.php';

$pdo = Database::getInstance();

// 1. Caso POST (Vem do Modal de Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && !Csrf::validateToken($_POST['csrf_token'])) {
        die("Erro de validação CSRF.");
    }

    $fid = $_POST['financeiro_id'];
    $cid = $_POST['cliente_id'];
    $new_status = $_POST['novo_status'];

    try {
        $pdo->prepare("UPDATE processo_financeiro SET status = ? WHERE id = ? AND cliente_id = ?")
            ->execute([$new_status, $fid, $cid]);
        header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=financeiro&msg=status_updated");
        exit;
    } catch(PDOException $e) {
        die("Erro ao atualizar status financeiro: " . $e->getMessage());
    }
}

// 2. Caso GET (Toggle rápido na tabela)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fid']) && isset($_GET['cid'])) {
    $fid = $_GET['fid'];
    $cid = $_GET['cid'];

    try {
        $atual = $pdo->query("SELECT status FROM processo_financeiro WHERE id=$fid")->fetchColumn();
        $novo = ($atual == 'pago') ? 'pendente' : 'pago';
        
        $pdo->prepare("UPDATE processo_financeiro SET status=? WHERE id=? AND cliente_id=?")
            ->execute([$novo, $fid, $cid]);
            
        header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=financeiro");
        exit;
    } catch(PDOException $e) {
        die("Erro ao alternar status financeiro: " . $e->getMessage());
    }
}

header("Location: ../../gestao_admin_99.php");
exit;
