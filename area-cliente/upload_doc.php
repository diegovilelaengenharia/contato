<?php
session_name('CLIENTE_SESSID');
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/core/Upload.php';

header('Content-Type: application/json');

// DEBUG LOGGING
$logFile = __DIR__ . '/debug_upload.log';
function logStep($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

logStep("Script Start. POST Data: " . print_r($_POST, true));
logStep("FILES Data: " . print_r($_FILES, true));

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

if (!isset($_FILES['arquivo'])) {
    echo json_encode(['success' => false, 'error' => 'Erro no envio do arquivo']);
    exit;
}

$file = $_FILES['arquivo'];

// Directory: uploads/cliente_{id}/docs/ (Inside area-cliente)
$upload_dir_abs = __DIR__ . "/uploads/cliente_{$cliente_id}/docs/";
logStep("Upload Dir Abs: " . $upload_dir_abs);

$res = Upload::process($file, $upload_dir_abs, $doc_chave);
if ($res['success']) {
    $db_path = "uploads/cliente_{$cliente_id}/docs/" . basename($res['file_path']);
    logStep("File moved successfully via Upload::process. Updating DB...");
    try {
        // Check if exists update, else insert
        $stmt = $pdo->prepare("SELECT id FROM processo_docs_entregues WHERE cliente_id = ? AND doc_chave = ?");
        $stmt->execute([$cliente_id, $doc_chave]);
        $existing = $stmt->fetch();

        if ($existing) {
            $update = $pdo->prepare("UPDATE processo_docs_entregues SET arquivo_path = ?, nome_original = ?, data_entrega = NOW() WHERE id = ?");
            $update->execute([$db_path, $file['name'], $existing['id']]);
            logStep("DB Updated (ID: {$existing['id']})");
        } else {
            $insert = $pdo->prepare("INSERT INTO processo_docs_entregues (cliente_id, doc_chave, arquivo_path, nome_original, data_entrega) VALUES (?, ?, ?, ?, NOW())");
            $insert->execute([$cliente_id, $doc_chave, $db_path, $file['name']]);
            logStep("DB Inserted");
        }

        echo json_encode([
            'success' => true, 
            'filename' => $file['name'], 
            'path' => $db_path
        ]);
        logStep("Success Response Sent");

    } catch (PDOException $e) {
        logStep("DB Error: " . $e->getMessage());
        // Remove uploaded file if db fails
        unlink($res['file_path']);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
} else {
    logStep("Upload::process FAILED: " . $res['message']);
    echo json_encode(['success' => false, 'error' => $res['message']]);
}
?>
