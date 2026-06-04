<?php
/**
 * Action: Adicionar Lançamento Financeiro
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin.php");
    exit;
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['format']) && $_POST['format'] === 'json')
    || (isset($_GET['format']) && $_GET['format'] === 'json');

// 1. Validar CSRF
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro de segurança (CSRF). Recarregue a página.']);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
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

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Novo lançamento financeiro adicionado com sucesso!']);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Novo lançamento financeiro adicionado com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=financeiro");
    exit;

} catch(PDOException $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar lançamento financeiro: ' . $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao adicionar lançamento financeiro: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=financeiro");
    exit;
}
