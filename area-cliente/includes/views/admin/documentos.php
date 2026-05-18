<?php
/**
 * View Admin: Checklist de Documentos
 */

$docs_config = require 'config/docs_config.php';
$processos = $docs_config['processes'];
$todos_docs = $docs_config['document_registry'];

$active_proc_key = $detalhes['tipo_processo_chave'] ?? '';
?>

<!-- ESTILOS ESPECÍFICOS DA ABA (VERDE HARMONIZADO) -->
<style>
    .docs-header { background: #f8fffb; padding: 20px; border-radius: 10px; border: 1px solid #d1e7dd; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.05); }
    .proc-select { padding: 10px; font-size: 0.95rem; border: 2px solid #198754; border-radius: 6px; color: #0f5132; font-weight: 600; width: 100%; max-width: 500px; outline: none; background: white; cursor: pointer; transition: 0.2s; }
    .proc-select:focus { box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.2); border-color: #146c43; }
    .section-title { font-size: 1rem; font-weight: 700; color: #1e5d42; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e9f5ef; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; grid-column: 1 / -1; margin-top: 10px; }
    .docs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    @media(max-width: 900px) { .docs-grid { grid-template-columns: 1fr; gap: 10px; } }
    .doc-card-admin { display: flex; align-items: center; justify-content: space-between; background: white; border: 1px solid #eaeaea; padding: 12px 15px; border-radius: 10px; transition: all 0.2s; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .doc-card-admin:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); border-color: #c3e6cb; }
    .dca-info { display: flex; align-items: center; gap: 12px; flex: 1; overflow: hidden; }
    .dca-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .dca-text { overflow: hidden; }
    .dca-text h4 { margin: 0 0 3px 0; font-size: 0.9rem; color: #2c3e50; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dca-text span { font-size: 0.75rem; color: #7f8c8d; }
    .dca-file { display: inline-flex; align-items: center; gap: 4px; background: #e8f5e9; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; color: #1e5d42; text-decoration: none; font-weight: 600; transition: 0.2s; white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis; border: 1px solid #c3e6cb; }
    .dca-file:hover { background: #d1e7dd; color: #0f5132; }
    .dca-actions { display: flex; gap: 6px; }
    .btn-act { border: none; width: 30px; height: 30px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
</style>

<div class="admin-tab-content" style="border-top: 4px solid #198754;">
    
    <form id="formDocsGlobal" action="actions/admin/documentos_checklist_update.php" method="POST">
        <?= Csrf::getHtmlField() ?>
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        <input type="hidden" name="update_docs_settings" value="1">
        
        <div class="admin-header-row">
            <div>
                <h3 class="admin-title" style="color:#198754;">📑 Checklist de Documentos</h3>
                <p class="admin-subtitle">Gerencie o recebimento e aprovação de documentos do cliente.</p>
            </div>
            <button type="submit" class="btn-save" style="background:#198754; color:white; border:none; padding:8px 20px; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.2);">
                💾 Salvar
            </button>
        </div>

        <div class="docs-header">
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:700; color:#1e5d42; font-size:0.85rem;">TIPO DE PROCESSO:</label>
                <select name="tipo_processo_chave" class="proc-select" onchange="this.form.submit()">
                    <option value="">-- Selecione --</option>
                    <?php foreach($processos as $key => $proc): ?>
                        <option value="<?= $key ?>" <?= $active_proc_key == $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proc['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:700; color:#1e5d42; font-size:0.85rem;">OBSERVAÇÕES (CLIENTE):</label>
                <textarea name="observacoes_gerais" class="admin-form-input" rows="1" placeholder="Ex: Documentos recebidos..." style="border: 1px solid #ced4da; border-radius: 6px; padding: 10px; font-size:0.9rem;"><?= htmlspecialchars($detalhes['observacoes_gerais'] ?? '') ?></textarea>
            </div>
        </div>
    </form>

    <?php if($active_proc_key && isset($processos[$active_proc_key])): 
        $proc_data = $processos[$active_proc_key];
        
        $stmt_map = $pdo->prepare("SELECT doc_chave, arquivo_path, nome_original, data_entrega, status FROM processo_docs_entregues WHERE cliente_id = ?");
        $stmt_map->execute([$cliente_ativo['id']]);
        $entregues_map = [];
        foreach($stmt_map->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entregues_map[$row['doc_chave']] = $row;
        }

        if(!function_exists('renderDocCard')) {
            function renderDocCard($label, $key, $entregues_map, $active_proc_key, $tipo_obrigatoriedade) {
                global $pdo; 
                $doc_data = $entregues_map[$key] ?? null;
                $status = $doc_data['status'] ?? 'pendente';
                $has_file = !empty($doc_data['arquivo_path']);
                
                $color = '#adb5bd'; $icon = 'check_box_outline_blank'; $status_bg = '#f8f9fa'; $status_txt = 'Pendente';
                
                if ($status == 'pendente') {
                    if ($tipo_obrigatoriedade == 'obrigatorio') {
                        $color = '#dc3545'; $icon = 'priority_high'; $status_bg = '#f8d7da'; $status_txt = 'Pendente (Obrigatório)';
                    } else {
                        $color = '#ffc107'; $icon = 'warning'; $status_bg = '#fff3cd'; $status_txt = 'Pendente (Opcional)';
                    }
                } else {
                    if($status == 'em_analise') {
                        $color = '#198754'; $icon = 'hourglass_top'; $status_bg = '#e8f5e9'; $status_txt = 'Anexado / Em Análise';
                    }
                    elseif($status == 'aprovado') {
                        $color = '#198754'; $icon = 'check_circle'; $status_bg = '#d1e7dd'; $status_txt = 'Aprovado';
                    }
                    elseif($status == 'rejeitado') {
                        $color = '#dc3545'; $icon = 'error'; $status_bg = '#f8d7da'; $status_txt = 'Rejeitado';
                    }
                }
                
                echo '<div class="doc-card-admin" style="border-left: 4px solid '.$color.';">';
                    echo '<div class="dca-info">';
                        echo '<div class="dca-icon" style="background:'.$status_bg.'; color:'.$color.';"><span class="material-symbols-rounded">'.$icon.'</span></div>';
                        echo '<div class="dca-text">';
                            echo '<h4 title="'.htmlspecialchars($label).'">'.htmlspecialchars($label).'</h4>';
                            echo '<span style="background:'.$status_bg.'; color:'.$color.'; padding:1px 6px; border-radius:4px; font-weight:700; font-size:0.65rem; text-transform:uppercase;">'.$status_txt.'</span>';
                        echo '</div>';
                    echo '</div>';

                    if($has_file) {
                        echo '<a href="'.htmlspecialchars($doc_data['arquivo_path']).'" target="_blank" class="dca-file" title="'.$doc_data['nome_original'].'">
                                <span class="material-symbols-rounded" style="font-size:0.9rem;">description</span> Arquivo
                              </a>';
                    }

                    echo '<div class="dca-actions">';
                        $common_hidden = Csrf::getHtmlField() . '<input type="hidden" name="cliente_id" value="'.$_GET['cliente_id'].'"><input type="hidden" name="update_docs_settings" value="1"><input type="hidden" name="doc_chave" value="'.$key.'"><input type="hidden" name="tipo_processo_chave" value="'.$active_proc_key.'">';
                        $action_url = "actions/admin/documentos_checklist_update.php";
                        
                        if($status == 'aprovado') {
                            echo '<form action="'.$action_url.'" method="POST">'.$common_hidden.'<input type="hidden" name="action_doc" value="reopen"><button type="submit" class="btn-act" style="background:#fff3cd; color:#856404;" title="Reabrir">↩️</button></form>';
                        } else {
                            echo '<form action="'.$action_url.'" method="POST">'.$common_hidden.'<input type="hidden" name="action_doc" value="approve"><button type="submit" class="btn-act" style="background:#d1e7dd; color:#198754;" title="Aprovar">✅</button></form>';
                            if($status != 'pendente' || $has_file) {
                                echo '<form action="'.$action_url.'" method="POST" onsubmit="return confirm(\'Limpar?\')">'.$common_hidden.'<input type="hidden" name="action_doc" value="reject"><button type="submit" class="btn-act" style="background:#f8d7da; color:#dc3545;" title="Rejeitar">🗑️</button></form>';
                            }
                        }
                    echo '</div>';
                echo '</div>';
            }
        }
    ?>

    <div class="docs-grid">
        <div class="section-title">
            <span style="background:#e8f5e9; color:#198754; padding:3px 6px; border-radius:4px; font-size:0.9rem;">📋</span> 
            OBRIGATÓRIOS
        </div>
        <?php foreach($proc_data['docs_obrigatorios'] as $doc_key): 
            $doc_label = $todos_docs[$doc_key] ?? $doc_key;
            renderDocCard($doc_label, $doc_key, $entregues_map, $active_proc_key, 'obrigatorio');
        endforeach; ?>
        
        <?php if(!empty($proc_data['docs_excepcionais'])): ?>
            <div class="section-title" style="margin-top:15px; border-bottom-color:#fff3cd;">
                <span style="background:#fff3cd; color:#856404; padding:3px 6px; border-radius:4px; font-size:0.9rem;">⚠️</span> 
                EXCEPCIONAIS
            </div>
            <div style="grid-column: 1 / -1; display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <?php foreach($proc_data['docs_excepcionais'] as $doc_key): 
                    $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                    renderDocCard($doc_label, $doc_key, $entregues_map, $active_proc_key, 'excepcional');
                endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <?php else: ?>
        <div style="text-align:center; padding: 40px 20px; color:#999;">
            <span style="font-size:3rem; display:block; margin-bottom:10px; opacity:0.5;">👆</span>
            <h3 style="color:#666; font-size:1.1rem;">Selecione o Tipo de Processo</h3>
        </div>
    <?php endif; ?>

</div>
