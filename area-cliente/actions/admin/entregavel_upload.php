<?php
/**
 * Action: Upload de Documento Entregável
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
    header("Location: ../../gestao_admin_99.php");
    exit;
}

$pdo = Database::getInstance();

$cid = $_POST['cliente_id'];
$titulo = $_POST['titulo_arquivo'];

try {
    if (isset($_FILES['arquivo_entregavel']) && $_FILES['arquivo_entregavel']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['arquivo_entregavel']['name'], PATHINFO_EXTENSION));
        $original_name = $_FILES['arquivo_entregavel']['name'];
        
        // Se título vazio, usa nome original sem extensão
        if (empty($titulo)) {
            $titulo = pathinfo($original_name, PATHINFO_FILENAME);
        }

        $new_name = "entregavel_{$cid}_" . time() . ".$ext";
        $target_dir = __DIR__ . "/../../uploads/entregaveis/";
        
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        if (move_uploaded_file($_FILES['arquivo_entregavel']['tmp_name'], $target_dir . $new_name)) {
            $db_path = "uploads/entregaveis/$new_name";
            
            $sql = "INSERT INTO processo_entregaveis (cliente_id, titulo, arquivo_path) VALUES (?, ?, ?)";
            $pdo->prepare($sql)->execute([$cid, $titulo, $db_path]);

            header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=arquivos&msg=entregavel_added");
            exit;
        } else {
            die("Erro ao mover arquivo.");
        }
    } else {
        die("Erro no upload do arquivo.");
    }

} catch(PDOException $e) {
    die("Erro ao processar upload: " . $e->getMessage());
}
