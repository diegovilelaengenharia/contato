<?php
/**
 * Action: Atualizar Etapa e Adicionar Histórico
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
    header("Location: ../../gestao_admin_99.php");
    exit;
}

$pdo = Database::getInstance();

$cid = $_POST['cliente_id'];
$nova_etapa = $_POST['nova_etapa'] ?? null; 
$titulo_ev = $_POST['titulo_evento'];
$obs_etapa = $_POST['observacao_etapa'] ?? '';

$tipo_mov = 'padrao'; // Default

try {
    // 1. Update Current Phase if selected
    if (!empty($nova_etapa)) {
        $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
    }
    
    // 2. Prepare Description
    $sys_desc = $obs_etapa; 
    
    // 3. Handle File Upload if Document (Auto-detect)
    if (isset($_FILES['arquivo_documento']) && $_FILES['arquivo_documento']['error'] == 0) {
        $tipo_mov = 'documento'; // Auto-set type
        
        $ext = strtolower(pathinfo($_FILES['arquivo_documento']['name'], PATHINFO_EXTENSION));
        $new_name = "doc_{$cid}_" . time() . ".$ext";
        $target_dir = __DIR__ . "/../../uploads/entregaveis/";
        
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        if (move_uploaded_file($_FILES['arquivo_documento']['tmp_name'], $target_dir . $new_name)) {
            $rel_path = "uploads/entregaveis/$new_name";
            // Usamos a URL relativa ao root do projeto para o link
            $sys_desc .= "<br><br><a href='$rel_path' target='_blank' class='btn-download-doc'>📥 Baixar Documento</a>";
        }
    }
    
    // 4. Insert Movement
    $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo, tipo_movimento) VALUES (?, ?, NOW(), ?, 'conclusao', ?)";
    $pdo->prepare($sql)->execute([$cid, $titulo_ev, $sys_desc, $tipo_mov]);

    // Redirecionamento
    header("Location: ../../gestao_admin_99.php?cliente_id=$cid&tab=andamento&msg=mov_added");
    exit;

} catch(PDOException $e) {
    die("Erro ao registrar movimentação: " . $e->getMessage());
}
