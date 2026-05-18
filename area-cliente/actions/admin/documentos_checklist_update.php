<?php
/**
 * Action: Atualizar Checklist de Documentos (Aprovar/Rejeitar/Config)
 * Extratado de admin.php
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

    header("Location: ../../admin.php?cliente_id=$cliente_id&tab=docs_iniciais&msg=saved");
    exit;

} catch(PDOException $e) {
    die("Erro ao atualizar checklist de documentos: " . $e->getMessage());
}
