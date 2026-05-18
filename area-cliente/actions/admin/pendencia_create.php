<?php
/**
 * Action: Adicionar Pendência
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar CSRF
if (isset($_POST['csrf_token']) && !Csrf::validateToken($_POST['csrf_token'])) {
    die("Erro de validação CSRF.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin.php");
    exit;
}

$pdo = Database::getInstance();

$cid = $_POST['cliente_id'];
$titulo = trim($_POST['titulo_pendencia'] ?? '');
$texto = trim($_POST['descricao_pendencia']);

try {
    // 1. Inserir Pendência
    $sql = "INSERT INTO processo_pendencias (cliente_id, titulo, descricao, status, data_criacao) VALUES (?, ?, ?, 'pendente', NOW())";
    $pdo->prepare($sql)->execute([$cid, $titulo, $texto]);
    $pendencia_id = $pdo->lastInsertId();

    // 2. Handle File Upload (Optional Admin Attachment)
    if(isset($_FILES['arquivo_pendencia_admin']) && $_FILES['arquivo_pendencia_admin']['error'] == 0) {
        $file = $_FILES['arquivo_pendencia_admin'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip'];
        
        if(in_array($ext, $allowed)) {
             $final_name = $pendencia_id . "_admin_" . time() . "." . $ext;
             $dir = __DIR__ . '/../../client-app/uploads/pendencias/';
             if(!is_dir($dir)) mkdir($dir, 0755, true);
             
             if(move_uploaded_file($file['tmp_name'], $dir . $final_name)) {
                  // Opcional: Registrar na tabela de arquivos se existir
                  // $web_path_db = 'uploads/pendencias/' . $final_name;
                  // $pdo->prepare("INSERT INTO processo_pendencias_arquivos ...")->execute(...);
             }
        }
    }

    header("Location: ../../admin.php?cliente_id=$cid&tab=pendencias&msg=pend_added");
    exit;

} catch(PDOException $e) {
    die("Erro ao adicionar pendência: " . $e->getMessage());
}
