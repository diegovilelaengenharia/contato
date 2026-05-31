<?php
/**
 * View Admin: Checklist de Documentos
 */

$docs_config = require 'config/docs_config.php';
$processos = $docs_config['processes'];
$todos_docs = $docs_config['document_registry'];

$active_proc_key = $detalhes['tipo_processo_chave'] ?? '';
?>

<div class="admin-tab-content">

    <form id="formDocsGlobal" action="actions/admin/documentos_checklist_update.php" method="POST">
        <?= Csrf::getHtmlField() ?>
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        <input type="hidden" name="update_docs_settings" value="1">

        <div class="admin-header-row">
            <div>
                <h3 class="admin-title">Checklist de Documentos</h3>
                <p class="admin-subtitle">Gerencie o recebimento e aprovação de documentos do cliente.</p>
            </div>
            <button type="submit" class="btn-save">
                <span class="material-symbols-rounded">save</span> Salvar
            </button>
        </div>

        <div class="docs-header">
            <div class="form-group" style="margin-bottom:14px;">
                <label>Tipo de Processo</label>
                <select name="tipo_processo_chave" class="proc-select" onchange="this.form.submit()">
                    <option value="">-- Selecione --</option>
                    <?php foreach($processos as $key => $proc): ?>
                        <option value="<?= $key ?>" <?= $active_proc_key == $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proc['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label>Observações (cliente)</label>
                <textarea name="observacoes_gerais" class="admin-form-input" rows="1" placeholder="Ex: Documentos recebidos..."><?= htmlspecialchars($detalhes['observacoes_gerais'] ?? '') ?></textarea>
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
                $doc_data = $entregues_map[$key] ?? null;
                $status = $doc_data['status'] ?? 'pendente';
                $has_file = !empty($doc_data['arquivo_path']);

                $color = '#9aa8a1'; $icon = 'check_box_outline_blank'; $status_bg = '#f1f4f2'; $status_txt = 'Pendente';

                if ($status == 'pendente') {
                    if ($tipo_obrigatoriedade == 'obrigatorio') {
                        $color = '#a32530'; $icon = 'priority_high'; $status_bg = '#fbe0e2'; $status_txt = 'Pendente (Obrigatório)';
                    } else {
                        $color = '#8a6400'; $icon = 'warning'; $status_bg = '#fdf2cf'; $status_txt = 'Pendente (Opcional)';
                    }
                } else {
                    if($status == 'em_analise')      { $color = '#197e63'; $icon = 'hourglass_top'; $status_bg = '#eef6f2'; $status_txt = 'Anexado / Em Análise'; }
                    elseif($status == 'aprovado')    { $color = '#14654f'; $icon = 'check_circle';  $status_bg = '#d8f0e2'; $status_txt = 'Aprovado'; }
                    elseif($status == 'rejeitado')   { $color = '#a32530'; $icon = 'error';         $status_bg = '#fbe0e2'; $status_txt = 'Rejeitado'; }
                }

                echo '<div class="doc-card-admin" style="border-left:4px solid '.$color.';">';
                    echo '<div class="dca-info">';
                        echo '<div class="dca-icon" style="background:'.$status_bg.'; color:'.$color.';"><span class="material-symbols-rounded">'.$icon.'</span></div>';
                        echo '<div class="dca-text">';
                            echo '<h4 title="'.htmlspecialchars($label).'">'.htmlspecialchars($label).'</h4>';
                            echo '<span class="doc-status" style="background:'.$status_bg.'; color:'.$color.';">'.$status_txt.'</span>';
                        echo '</div>';
                    echo '</div>';

                    if($has_file) {
                        echo '<a href="'.htmlspecialchars($doc_data['arquivo_path']).'" target="_blank" class="dca-file" title="'.htmlspecialchars($doc_data['nome_original'] ?? '').'">
                                <span class="material-symbols-rounded" style="font-size:.9rem;">description</span> Arquivo
                              </a>';
                    }

                    echo '<div class="dca-actions">';
                        $common_hidden = Csrf::getHtmlField() . '<input type="hidden" name="cliente_id" value="'.((int)($_GET['cliente_id'] ?? 0)).'"><input type="hidden" name="update_docs_settings" value="1"><input type="hidden" name="doc_chave" value="'.htmlspecialchars($key).'"><input type="hidden" name="tipo_processo_chave" value="'.htmlspecialchars($active_proc_key).'">';
                        $action_url = "actions/admin/documentos_checklist_update.php";

                        if($status == 'aprovado') {
                            echo '<form action="'.$action_url.'" method="POST">'.$common_hidden.'<input type="hidden" name="action_doc" value="reopen"><button type="submit" class="btn-act" title="Reabrir"><span class="material-symbols-rounded">undo</span></button></form>';
                        } else {
                            echo '<form action="'.$action_url.'" method="POST">'.$common_hidden.'<input type="hidden" name="action_doc" value="approve"><button type="submit" class="btn-act" title="Aprovar"><span class="material-symbols-rounded">check_circle</span></button></form>';
                            if($status != 'pendente' || $has_file) {
                                echo '<form action="'.$action_url.'" method="POST" onsubmit="return confirm(\'Limpar?\')">'.$common_hidden.'<input type="hidden" name="action_doc" value="reject"><button type="submit" class="btn-act danger" title="Rejeitar / Limpar"><span class="material-symbols-rounded">delete</span></button></form>';
                            }
                        }
                    echo '</div>';
                echo '</div>';
            }
        }
    ?>

    <div class="docs-grid">
        <div class="section-title">
            <span class="material-symbols-rounded">checklist</span> Obrigatórios
        </div>
        <?php foreach($proc_data['docs_obrigatorios'] as $doc_key):
            $doc_label = $todos_docs[$doc_key] ?? $doc_key;
            renderDocCard($doc_label, $doc_key, $entregues_map, $active_proc_key, 'obrigatorio');
        endforeach; ?>

        <?php if(!empty($proc_data['docs_excepcionais'])): ?>
            <div class="section-title" style="margin-top:8px;">
                <span class="material-symbols-rounded">priority_high</span> Excepcionais
            </div>
            <div style="grid-column: 1 / -1; display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                <?php foreach($proc_data['docs_excepcionais'] as $doc_key):
                    $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                    renderDocCard($doc_label, $doc_key, $entregues_map, $active_proc_key, 'excepcional');
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
        <div class="docs-empty">
            <span class="material-symbols-rounded" style="font-size:3rem; display:block; margin-bottom:10px; opacity:.5;">touch_app</span>
            <h3 style="color:var(--color-text-subtle); font-size:1.1rem;">Selecione o Tipo de Processo</h3>
        </div>
    <?php endif; ?>

</div>
