<?php
/**
 * Action: Excluir Documento Entregável
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

$id = (int)($_POST['id'] ?? 0);
$cid = (int)($_POST['cliente_id'] ?? 0);

if (!$id || !$cid) {
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
    // 1. Buscar path do arquivo
    $stmt = $pdo->prepare("SELECT arquivo_path FROM processo_entregaveis WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cid]);
    $arquivo = $stmt->fetch();

    if ($arquivo) {
        $full_path = __DIR__ . "/../../" . $arquivo['arquivo_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // 2. Excluir do Banco
        $pdo->prepare("DELETE FROM processo_entregaveis WHERE id = ?")->execute([$id]);
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Documento entregável removido.']);
            exit;
        }
        $_SESSION['flash_message'] = ['text' => 'Documento entregável removido.', 'type' => 'success'];
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Arquivo não encontrado.']);
            exit;
        }
        $_SESSION['flash_message'] = ['text' => 'Arquivo não encontrado.', 'type' => 'error'];
    }

    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=documentos");
    exit;

} catch (PDOException $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir arquivo: ' . $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Erro ao excluir arquivo.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=documentos");
    exit;
}
