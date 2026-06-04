<?php
/**
 * Action: Upload de Documento Entregável
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Upload.php';

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
$titulo = $_POST['titulo'] ?? $_POST['titulo_arquivo'] ?? '';

try {
    if (isset($_FILES['arquivo_entregavel'])) {
        $original_name = $_FILES['arquivo_entregavel']['name'];
        
        // Se título vazio, usa nome original sem extensão
        if (empty(trim($titulo))) {
            $titulo = pathinfo($original_name, PATHINFO_FILENAME);
        }

        $target_dir = __DIR__ . "/../../uploads/entregaveis/";
        
        $res = Upload::process($_FILES['arquivo_entregavel'], $target_dir, "entregavel_{$cid}");
        if ($res['success']) {
            $db_path = "uploads/entregaveis/" . basename($res['file_path']);
            
            $sql = "INSERT INTO processo_entregaveis (cliente_id, titulo, arquivo_path) VALUES (?, ?, ?)";
            $pdo->prepare($sql)->execute([$cid, $titulo, $db_path]);

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Documento entregável enviado com sucesso para o cliente!']);
                exit;
            }

            $_SESSION['flash_message'] = ['text' => 'Documento entregável enviado com sucesso para o cliente!', 'type' => 'success'];
            header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=documentos");
            exit;
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Erro no upload: ' . $res['message']]);
                exit;
            }
            $_SESSION['flash_message'] = ['text' => 'Erro no upload: ' . $res['message'], 'type' => 'error'];
            header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=documentos");
            exit;
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nenhum arquivo enviado.']);
            exit;
        }
        $_SESSION['flash_message'] = ['text' => 'Nenhum arquivo enviado.', 'type' => 'error'];
        header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=documentos");
        exit;
    }

} catch(Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao processar upload: ' . $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Erro ao processar upload: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=documentos");
    exit;
}
