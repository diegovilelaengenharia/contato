<?php
/**
 * Action: Excluir Movimentação (Histórico)
 * Reescrito em SEC-07/ADM-16: POST + CSRF obrigatório.
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

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
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF).', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

$hid = (int)($_POST['movimento_id'] ?? 0);
$cid = (int)($_POST['cliente_id'] ?? 0);

if (!$hid || !$cid) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos para exclusão.']);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Dados inválidos para exclusão.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}

$pdo = Database::getInstance();
try {
    $pdo->prepare("DELETE FROM processo_movimentos WHERE id=? AND cliente_id=?")->execute([$hid, $cid]);
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Movimentação excluída do histórico.']);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Movimentação excluída do histórico.', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;
} catch (Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir histórico: ' . $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao excluir histórico.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;
}
