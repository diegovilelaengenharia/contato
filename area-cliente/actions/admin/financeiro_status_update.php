<?php
/**
 * Action: Atualizar Status Financeiro
 * Reescrito em SEC-07/ADM-16: Apenas POST + CSRF obrigatório.
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/index.php");
    exit;
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['format']) && $_POST['format'] === 'json')
    || (isset($_GET['format']) && $_GET['format'] === 'json');

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

$fid = (int)($_POST['financeiro_id'] ?? 0);
$cid = (int)($_POST['cliente_id'] ?? 0);
$new_status = $_POST['novo_status'] ?? '';

if (!$fid || !$cid || empty($new_status)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos para atualização.']);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Dados inválidos para atualização.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}

try {
    $pdo->prepare("UPDATE processo_financeiro SET status = ? WHERE id = ? AND cliente_id = ?")
        ->execute([$new_status, $fid, $cid]);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status financeiro atualizado.']);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Status financeiro atualizado.', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=financeiro");
    exit;
} catch (PDOException $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar status financeiro: ' . $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Erro ao atualizar status financeiro.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=financeiro");
    exit;
}
