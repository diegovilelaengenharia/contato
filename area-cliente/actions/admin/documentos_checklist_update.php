<?php
/**
 * Action: Atualizar Checklist de Documentos (Aprovar/Rejeitar/Config)
 * Extratado de admin.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

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
$cliente_id = $_POST['cliente_id'];

try {
    // 1. Salvar Tipo de Processo e Observações
    $new_proc = $_POST['tipo_processo_chave'];
    $obs_gerais = $_POST['observacoes_gerais'] ?? '';

    // Check if record exists
    $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $check->execute([$cliente_id]);

    if($check->rowCount() > 0) {
        $pdo->prepare("UPDATE processo_detalhes SET tipo_processo_chave = ?, observacoes_gerais = ? WHERE cliente_id = ?")
            ->execute([$new_proc, $obs_gerais, $cliente_id]);
    } else {
        $pdo->prepare("INSERT INTO processo_detalhes (cliente_id, tipo_processo_chave, observacoes_gerais) VALUES (?, ?, ?)")
            ->execute([$cliente_id, $new_proc, $obs_gerais]);
    }

    // 2. Processar Ações nos Documentos (Aprovar/Rejeitar/Reabrir)
    if(isset($_POST['action_doc'])) {
        $act = $_POST['action_doc'];
        $d_key = $_POST['doc_chave'];
        
        // Check existence
        $chk = $pdo->prepare("SELECT id FROM processo_docs_entregues WHERE cliente_id = ? AND doc_chave = ?");
        $chk->execute([$cliente_id, $d_key]);
        $exists = $chk->fetch();

        if($act == 'approve') {
            if($exists) {
                $pdo->prepare("UPDATE processo_docs_entregues SET status = 'aprovado' WHERE id = ?")->execute([$exists['id']]);
            } else {
                // Aprovação manual sem arquivo
                $pdo->prepare("INSERT INTO processo_docs_entregues (cliente_id, doc_chave, status, data_entrega) VALUES (?, ?, 'aprovado', NOW())")
                    ->execute([$cliente_id, $d_key]);
            }
        }
        elseif($act == 'reopen') {
            if($exists) {
                // Reabrir: volta para em_analise se tiver arquivo, senão deleta a aprovação manual
                $check_file = $pdo->prepare("SELECT arquivo_path FROM processo_docs_entregues WHERE id = ?");
                $check_file->execute([$exists['id']]);
                $has_file = $check_file->fetchColumn();

                if($has_file) {
                    $pdo->prepare("UPDATE processo_docs_entregues SET status = 'em_analise' WHERE id = ?")->execute([$exists['id']]);
                } else {
                    $pdo->prepare("DELETE FROM processo_docs_entregues WHERE id = ?")->execute([$exists['id']]);
                }
            }
        }
        elseif($act == 'reject') {
            // Rejeitar: remove registro (reset status p/ pendente no front)
            if($exists) {
                $pdo->prepare("DELETE FROM processo_docs_entregues WHERE id = ?")->execute([$exists['id']]);
            }
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Checklist de documentos atualizado com sucesso!']);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Checklist de documentos atualizado com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cliente_id&tab=documentos");
    exit;

} catch(PDOException $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar checklist de documentos: ' . $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao atualizar checklist de documentos: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cliente_id&tab=documentos");
    exit;
}
