<?php
/**
 * Parcial: Aba Documentação
 * Extraído de admin/views/cliente_detalhes.php
 */
?>
<!-- Checklist de Documentos do Processo -->
<div style="border-bottom: 1px solid var(--color-border); padding-bottom: 24px; margin-bottom: 24px;">
    <form id="formDocsGlobalNew" action="../actions/admin/documentos_checklist_update.php" method="POST" x-ref="docsForm" @submit.prevent="submitForm($event)">
        <?php echo Csrf::getHtmlField(); ?>
        <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
        <input type="hidden" name="update_docs_settings" value="1">
        
        <div class="admin-header-row">
            <div>
                <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Checklist de Documentação Obrigatória</h3>
                <p class="admin-subtitle">Selecione o tipo de processo para habilitar a lista de exigências do cliente.</p>
            </div>
            <button type="submit" class="btn-save">
                <span class="material-symbols-rounded">save</span> Salvar Configuração
            </button>
        </div>
        
        <div class="docs-header" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Tipo de Processo</label>
                <select name="tipo_processo_chave" class="proc-select" @change="$refs.docsForm.requestSubmit()" style="background: white;">
                    <option value="">-- Selecione o Processo --</option>
                    <?php foreach ($processos_opts as $key => $proc): ?>
                        <option value="<?php echo $key; ?>" <?php echo $active_proc_key === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($proc['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Instruções / Observações de Documentação</label>
                <textarea name="observacoes_gerais" class="admin-form-input" rows="1" style="background: white;" placeholder="Instruções para o cliente sobre entrega..."><?php echo htmlspecialchars($detalhes['observacoes_gerais'] ?? ''); ?></textarea>
            </div>
        </div>
    </form>

    <!-- Renderizador de Documentos Iniciais do Cliente -->
    <?php if ($active_proc_key && isset($processos_opts[$active_proc_key])): 
        $proc_data = $processos_opts[$active_proc_key];
        
        // Mapeia docs entregues
        $stmtMap = $pdo->prepare("SELECT doc_chave, arquivo_path, nome_original, status FROM processo_docs_entregues WHERE cliente_id = ?");
        $stmtMap->execute([$cliente['id']]);
        $entregues_map = [];
        foreach ($stmtMap->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entregues_map[$row['doc_chave']] = $row;
        }
        
        // Função auxiliar local para renderizar cada card de documento
        if (!function_exists('renderDocCardNew')) {
            function renderDocCardNew($label, $key, $entregues_map, $active_proc_key, $obrigatoriedade, $cliente_id) {
                $doc_data = $entregues_map[$key] ?? null;
                $status = $doc_data['status'] ?? 'pendente';
                $has_file = !empty($doc_data['arquivo_path']);
                
                $color = '#9aa8a1'; $icon = 'check_box_outline_blank'; $status_bg = '#f1f4f2'; $status_txt = 'Pendente';
                
                if ($status === 'pendente') {
                    if ($obrigatoriedade === 'obrigatorio') {
                        $color = '#a32530'; $icon = 'priority_high'; $status_bg = '#fbe0e2'; $status_txt = 'Pendente (Obrigatório)';
                    } else {
                        $color = '#8a6400'; $icon = 'warning'; $status_bg = '#fdf2cf'; $status_txt = 'Pendente (Opcional)';
                    }
                } else {
                    if ($status === 'em_analise') {
                        $color = 'var(--color-primary)'; $icon = 'hourglass_top'; $status_bg = 'var(--color-primary-tint)'; $status_txt = 'Enviado / Em Análise';
                    } elseif ($status === 'aprovado') {
                        $color = '#14654f'; $icon = 'check_circle'; $status_bg = '#d8f0e2'; $status_txt = 'Aprovado';
                    } elseif ($status === 'rejeitado') {
                        $color = '#a32530'; $icon = 'error'; $status_bg = '#fbe0e2'; $status_txt = 'Rejeitado / Pendente';
                    }
                }
                
                echo '<div class="doc-card-admin" style="border-left: 4px solid '.$color.'; margin-bottom: 12px; padding: 15px;">';
                    echo '<div class="dca-info">';
                        echo '<div class="dca-icon" style="background: '.$status_bg.'; color: '.$color.'; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><span class="material-symbols-rounded">'.$icon.'</span></div>';
                        echo '<div class="dca-text" style="margin-left: 12px;">';
                            echo '<h4 style="margin: 0; font-size: 0.9rem; font-weight: 700;">'.htmlspecialchars($label).'</h4>';
                            echo '<span class="doc-status" style="background: '.$status_bg.'; color: '.$color.'; font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-top: 4px;">'.$status_txt.'</span>';
                        echo '</div>';
                    echo '</div>';
                    
                    if ($has_file) {
                        echo '<a href="'.htmlspecialchars($doc_data['arquivo_path']).'" target="_blank" class="dca-file" style="margin-left: auto; margin-right: 15px; font-size: 0.78rem;" title="'.htmlspecialchars($doc_data['nome_original']).'">';
                            echo '<span class="material-symbols-rounded" style="font-size: 1rem;">description</span> Baixar Arquivo';
                        echo '</a>';
                    }
                    
                    echo '<div class="dca-actions" style="display: flex; gap: 6px; margin-left: '.($has_file ? '0' : 'auto').';">';
                        $hidden = Csrf::getHtmlField() . '
                            <input type="hidden" name="cliente_id" value="'.$cliente_id.'">
                            <input type="hidden" name="update_docs_settings" value="1">
                            <input type="hidden" name="doc_chave" value="'.htmlspecialchars($key).'">
                            <input type="hidden" name="tipo_processo_chave" value="'.htmlspecialchars($active_proc_key).'">';
                        $action = "../actions/admin/documentos_checklist_update.php";
                        
                        if ($status === 'aprovado') {
                            echo '<form action="'.$action.'" method="POST" @submit.prevent="submitForm(\$event)">'.$hidden.'<input type="hidden" name="action_doc" value="reopen"><button type="submit" class="btn-act" title="Reabrir / Desaprovar"><span class="material-symbols-rounded">undo</span></button></form>';
                        } else {
                            echo '<form action="'.$action.'" method="POST" @submit.prevent="submitForm(\$event)">'.$hidden.'<input type="hidden" name="action_doc" value="approve"><button type="submit" class="btn-act" style="background: var(--color-primary-soft); color: var(--color-primary-dark); border: none;" title="Aprovar"><span class="material-symbols-rounded">check</span></button></form>';
                            if ($status !== 'pendente' || $has_file) {
                                echo '<form action="'.$action.'" method="POST" @submit.prevent="deleteItem(\$event, \'Deseja rejeitar este documento?\')">'.$hidden.'<input type="hidden" name="action_doc" value="reject"><button type="submit" class="btn-act danger" title="Rejeitar"><span class="material-symbols-rounded">close</span></button></form>';
                            }
                        }
                    echo '</div>';
                echo '</div>';
            }
        }
    ?>
        <div class="docs-grid" style="margin-top: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                
                <!-- Coluna Obrigatórios -->
                <div>
                    <div class="section-title" style="margin-bottom: 15px;"><span class="material-symbols-rounded" style="color: var(--color-danger);">assignment_late</span> Documentos Obrigatórios</div>
                    <?php foreach ($proc_data['docs_obrigatorios'] as $doc_key): 
                        $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                        renderDocCardNew($doc_label, $doc_key, $entregues_map, $active_proc_key, 'obrigatorio', $cliente['id']);
                    endforeach; ?>
                </div>
                
                <!-- Coluna Opcionais / Excepcionais -->
                <div>
                    <div class="section-title" style="margin-bottom: 15px;"><span class="material-symbols-rounded" style="color: #c9871a;">assignment</span> Documentos Adicionais</div>
                    <?php if (!empty($proc_data['docs_excepcionais'])): ?>
                        <?php foreach ($proc_data['docs_excepcionais'] as $doc_key): 
                            $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                            renderDocCardNew($doc_label, $doc_key, $entregues_map, $active_proc_key, 'excepcional', $cliente['id']);
                        endforeach; ?>
                    <?php else: ?>
                        <p style="font-style: italic; color: var(--color-muted); font-size: 0.88rem; padding: 15px 0;">Sem documentos adicionais exigidos para este processo.</p>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    <?php else: ?>
        <div class="docs-empty">
            <span class="material-symbols-rounded" style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.5;">assignment</span>
            Selecione um tipo de processo acima para carregar o checklist de documentos.
        </div>
    <?php endif; ?>
</div>

<!-- Documentos Finais Entregáveis (Sua Emissão -> Cliente) -->
<div>
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Projetos Concluídos & Entregáveis Finais</h3>
            <p class="admin-subtitle">Envie projetos aprovados, alvarás, habites-se e certidões finais para o cliente.</p>
        </div>
        <button type="button" class="btn-save" onclick="document.getElementById('modalUploadEntregavel').showModal()">
            <span class="material-symbols-rounded">cloud_upload</span> Disponibilizar Projeto / Certidão
        </button>
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Título do Documento Final</th>
                    <th>Data do Envio</th>
                    <th>Arquivo (.PDF / .DWG)</th>
                    <th style="text-align: right; padding-right: 20px;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmtEnt = $pdo->prepare("SELECT * FROM processo_entregaveis WHERE cliente_id = ? ORDER BY data_upload DESC");
                $stmtEnt->execute([$cliente['id']]);
                $entregaveis = $stmtEnt->fetchAll();

                if (empty($entregaveis)): ?>
                    <tr>
                        <td colspan="4" style="padding: 30px; text-align: center; color: var(--color-muted); font-style: italic;">
                            Nenhum documento final ou projeto disponibilizado ainda.
                        </td>
                    </tr>
                <?php else: foreach ($entregaveis as $ent): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--color-primary-dark);"><?php echo htmlspecialchars($ent['titulo']); ?></td>
                        <td style="color: var(--color-text-subtle);"><?php echo date('d/m/Y H:i', strtotime($ent['data_upload'])); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($ent['arquivo_path']); ?>" target="_blank" class="dca-file" style="font-size: 0.78rem;">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">description</span> Ver Documento
                            </a>
                        </td>
                        <td style="text-align: right; padding-right: 20px;">
                            <form action="../actions/admin/entregavel_delete.php" method="POST" class="inline-form" style="display: inline;"
                                  @submit.prevent="deleteItem($event, 'Deseja excluir este documento entregável?')">
                                <?php echo Csrf::getHtmlField(); ?>
                                <input type="hidden" name="id" value="<?php echo $ent['id']; ?>">
                                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                <button type="submit" class="btn-icon danger" title="Excluir" style="border: none; background: none; cursor: pointer; padding: 0; color: var(--color-danger);">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Upload de Entregável Final (Diego -> Cliente) -->
<dialog id="modalUploadEntregavel">
    <div style="background: var(--color-primary); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">📂 Disponibilizar Documento Final</h3>
        <button type="button" onclick="document.getElementById('modalUploadEntregavel').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>
    <form action="../actions/admin/entregavel_upload.php" method="POST" enctype="multipart/form-data" style="padding: 25px;" @submit.prevent="submitForm($event)">
        <?php echo Csrf::getHtmlField(); ?>
        <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Título do Documento Final <span style="color: red;">*</span></label>
            <input type="text" name="titulo" required placeholder="Ex: Planta Arquitetônica Aprovada - Habite-se" class="admin-form-input">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>Selecionar Arquivo Técnico (.PDF ou .ZIP) <span style="color: red;">*</span></label>
            <input type="file" name="arquivo_entregavel" required class="admin-form-input">
        </div>

        <button type="submit" name="btn_upload_entregavel" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
            Enviar para o Portal do Cliente
        </button>
    </form>
</dialog>
