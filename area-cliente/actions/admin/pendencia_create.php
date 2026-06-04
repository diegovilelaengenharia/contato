<?php
/**
 * Action: Adicionar Pendência
 * Extratado de includes/processamento.php
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
$titulo = trim($_POST['titulo_pendencia'] ?? '');
$texto = trim($_POST['descricao'] ?? $_POST['descricao_pendencia'] ?? '');

try {
    if (empty($texto)) {
        throw new Exception("A descrição da pendência é obrigatória.");
    }

    // 1. Inserir Pendência
    $sql = "INSERT INTO processo_pendencias (cliente_id, titulo, descricao, status, data_criacao) VALUES (?, ?, ?, 'pendente', NOW())";
    $pdo->prepare($sql)->execute([$cid, $titulo, $texto]);
    $pendencia_id = $pdo->lastInsertId();

    // 2. Handle File Upload (Optional Admin Attachment)
    if(isset($_FILES['arquivo_pendencia_admin']) && $_FILES['arquivo_pendencia_admin']['error'] == UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../../client-app/uploads/pendencias/';
        $res = Upload::process($_FILES['arquivo_pendencia_admin'], $dir, "{$pendencia_id}_admin");
        if ($res['success']) {
             // Opcional: Registrar na tabela de arquivos se existir
             // $web_path_db = 'uploads/pendencias/' . basename($res['file_path']);
        } else {
             throw new Exception("Erro no upload do anexo: " . $res['message']);
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Pendência/Solicitação de documento aberta para o cliente!']);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Pendência/Solicitação de documento aberta para o cliente!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=pendencias");
    exit;

} catch(Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar pendência: ' . $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao adicionar pendência: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=pendencias");
    exit;
}
