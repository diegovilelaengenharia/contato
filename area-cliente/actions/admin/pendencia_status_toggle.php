<?php
/**
 * Action: Alternar Status de Pendência
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

$pid = (int)($_POST['pendencia_id'] ?? 0);
$cid = (int)($_POST['cliente_id'] ?? 0);

if (!$pid || !$cid) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos para alteração.']);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Dados inválidos para alteração.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}

$pdo = Database::getInstance();
try {
    // Busca status atual usando prepared statement para evitar interpolação direta
    $stmt = $pdo->prepare("SELECT status FROM processo_pendencias WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$pid, $cid]);
    $curr = $stmt->fetchColumn();

    if ($curr !== false) {
        $new = ($curr == 'resolvido') ? 'pendente' : 'resolvido';
        $pdo->prepare("UPDATE processo_pendencias SET status = ? WHERE id = ? AND cliente_id = ?")
            ->execute([$new, $pid, $cid]);
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Status da pendência atualizado.', 'status' => $new]);
            exit;
        }
        $_SESSION['flash_message'] = ['text' => 'Status da pendência atualizado.', 'type' => 'success'];
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pendência não encontrada.']);
            exit;
        }
        $_SESSION['flash_message'] = ['text' => 'Pendência não encontrada.', 'type' => 'error'];
    }

    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=pendencias");
    exit;
} catch (PDOException $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao alternar status da pendência: ' . $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Erro ao alternar status da pendência.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=pendencias");
    exit;
}
