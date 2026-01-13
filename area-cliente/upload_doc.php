<?php
session_name('CLIENTE_SESSID');
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cliente_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$doc_chave = $_POST['doc_chave'] ?? '';

if (empty($doc_chave)) {
    echo json_encode(['success' => false, 'error' => 'Documento não identificado']);
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Erro no envio do arquivo']);
    exit;
}

$file = $_FILES['arquivo'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Formato não permitido (apenas PDF, Imagens ou Word)']);
    exit;
}

// Directory: uploads/cliente_{id}/docs/ (Inside area-cliente)
$upload_dir_rel = "uploads/cliente_{$cliente_id}/docs/";
$upload_dir_abs = __DIR__ . "/" . $upload_dir_rel; // Absolute system path

if (!is_dir($upload_dir_abs)) {
    mkdir($upload_dir_abs, 0755, true);
}

// Filename: {doc_chave}_{timestamp}.{ext}
$new_name = "{$doc_chave}_" . time() . ".{$ext}";
$target_path = $upload_dir_abs . $new_name;
$db_path = "uploads/cliente_{$cliente_id}/docs/" . $new_name; // Path stored in DB relative to area-cliente root

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    try {
        // Check if exists update, else insert
        $stmt = $pdo->prepare("SELECT id FROM processo_docs_entregues WHERE cliente_id = ? AND doc_chave = ?");
        $stmt->execute([$cliente_id, $doc_chave]);
        $existing = $stmt->fetch();

        if ($existing) {
            $update = $pdo->prepare("UPDATE processo_docs_entregues SET arquivo_path = ?, nome_original = ?, data_entrega = NOW() WHERE id = ?");
            $update->execute([$db_path, $file['name'], $existing['id']]);
        } else {
            $insert = $pdo->prepare("INSERT INTO processo_docs_entregues (cliente_id, doc_chave, arquivo_path, nome_original, data_entrega) VALUES (?, ?, ?, ?, NOW())");
            $insert->execute([$cliente_id, $doc_chave, $db_path, $file['name']]);
        }

        echo json_encode([
            'success' => true, 
            'filename' => $file['name'], 
            'path' => $db_path
        ]);

    } catch (PDOException $e) {
        // Remove uploaded file if db fails
        unlink($target_path);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao mover arquivo']);
}
?>
