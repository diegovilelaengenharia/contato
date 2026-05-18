<?php
/**
 * Action: Adicionar Lançamento Financeiro
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar CSRF
if (isset($_POST['csrf_token']) && !Csrf::validateToken($_POST['csrf_token'])) {
    die("Erro de validação CSRF.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../gestao_admin_99.php");
    exit;
}

$pdo = Database::getInstance();

$cid = $_POST['cliente_id'];
$categoria = $_POST['categoria'];
$descricao = $_POST['descricao'];
$valor = str_replace(',', '.', $_POST['valor']);
$data_vencimento = $_POST['data_vencimento'];
$status = $_POST['status'];
$link_comprovante = $_POST['link_comprovante'] ?? null;
$referencia_legal = $_POST['referencia_legal'] ?? null;

try {
    $sql = "INSERT INTO processo_financeiro (cliente_id, categoria, descricao, valor, data_vencimento, status, link_comprovante, referencia_legal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cid,
        $categoria,
        $descricao,
        $valor,
        $data_vencimento,
        $status,
        $link_comprovante,
        $referencia_legal
    ]);

    header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=financeiro&msg=fin_added");
    exit;

} catch(PDOException $e) {
    die("Erro ao adicionar lançamento financeiro: " . $e->getMessage());
}
