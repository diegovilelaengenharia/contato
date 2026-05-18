<?php
/**
 * Action: Excluir Documento Entregável
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

// Apenas Admin (Verificado no init/auth se necessário, mas aqui confirmamos)
if (!isset($_GET['id']) || !isset($_GET['cliente_id'])) {
    die("Parâmetros inválidos.");
}

$id = $_GET['id'];
$cid = $_GET['cliente_id'];

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
    }

    header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=arquivos&msg=entregavel_deleted");
    exit;

} catch(PDOException $e) {
    die("Erro ao excluir arquivo: " . $e->getMessage());
}
