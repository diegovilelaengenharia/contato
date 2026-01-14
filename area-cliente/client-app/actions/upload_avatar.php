<?php
// area-cliente/client-app/actions/upload_avatar.php
session_name('CLIENTE_SESSID');
session_start();
require_once '../../db.php';

header('Content-Type: application/json');

// 1. Verificar Login
if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// 2. Verificar Upload
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.']);
    exit;
}

$file = $_FILES['avatar'];
$maxSize = 5 * 1024 * 1024; // 5MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validações
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'O arquivo é muito grande (Máx: 5MB).']);
    exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Formato inválido. Apenas JPG, PNG, GIF ou WEBP.']);
    exit;
}

// 3. Preparar Diretório e Nomes
// Caminho absoluto para a pasta de uploads
$uploadDir = __DIR__ . '/../../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Extensão segura
$extension = 'jpg';
switch($mime) {
    case 'image/png': $extension = 'png'; break;
    case 'image/gif': $extension = 'gif'; break;
    case 'image/webp': $extension = 'webp'; break;
}

$newFileName = "avatar_{$cliente_id}.{$extension}";
$destination = $uploadDir . $newFileName;
$dbPath = "uploads/avatars/{$newFileName}"; // Caminho relativo para o banco (baseado na estrutura antiga)

// 4. LIMPEZA (Cruzar dados e evitar duplicidade)
// Remove quaisquer arquivos antigos deste cliente (avatar_123.jpg, avatar_123.png, etc)
$existingFiles = glob($uploadDir . "avatar_{$cliente_id}.*");
foreach ($existingFiles as $oldFile) {
    if (is_file($oldFile)) {
        unlink($oldFile);
    }
}

// 5. Mover Arquivo Novo
if (move_uploaded_file($file['tmp_name'], $destination)) {
    
    // 6. Atualizar Banco de Dados
    try {
        $stmt = $pdo->prepare("UPDATE clientes SET foto_perfil = ? WHERE id = ?");
        $stmt->execute([$dbPath, $cliente_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Foto atualizada com sucesso!',
            'newPath' => "../uploads/avatars/{$newFileName}" // Caminho relativo para o frontend (client-app/index.php)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar banco de dados: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar o arquivo no servidor.']);
}
?>
