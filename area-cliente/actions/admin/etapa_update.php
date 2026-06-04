<?php
/**
 * Action: Atualizar Etapa e Adicionar Histórico
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/Upload.php';

// 1. Validar CSRF
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin.php");
    exit;
}

$pdo = Database::getInstance();

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['format']) && $_POST['format'] === 'json')
    || (isset($_GET['format']) && $_GET['format'] === 'json');

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
    if (isset($_FILES['arquivo_documento']) && $_FILES['arquivo_documento']['error'] == UPLOAD_ERR_OK) {
        $tipo_mov = 'documento'; // Auto-set type
        
        $target_dir = __DIR__ . "/../../uploads/entregaveis/";
        
        $res = Upload::process($_FILES['arquivo_documento'], $target_dir, "doc_{$cid}");
        if ($res['success']) {
            $rel_path = "uploads/entregaveis/" . basename($res['file_path']);
            // Usamos a URL relativa ao root do projeto para o link
            $sys_desc .= "<br><br><a href='$rel_path' target='_blank' class='btn-download-doc'>📥 Baixar Documento</a>";
        } else {
            throw new Exception("Erro no upload do documento técnico: " . $res['message']);
        }
    }
    
    // 4. Insert Movement
    $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo, tipo_movimento) VALUES (?, ?, NOW(), ?, 'conclusao', ?)";
    $pdo->prepare($sql)->execute([$cid, $titulo_ev, $sys_desc, $tipo_mov]);

    // Gravar Log de Auditoria
    Logger::log('UPDATE', 'processo_etapa', $cid, [
        'etapa_atualizada' => $nova_etapa,
        'evento_titulo' => $titulo_ev,
        'observacao_etapa' => $obs_etapa
    ]);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Andamento registrado com sucesso na timeline!']);
        exit;
    }

    // Redirecionamento
    $_SESSION['flash_message'] = ['text' => 'Andamento registrado com sucesso na timeline!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;

} catch(Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao registrar movimentação: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;
}
