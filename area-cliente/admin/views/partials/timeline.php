<?php
/**
 * Parcial: Aba Timeline & WhatsApp
 * Extratado de admin/views/cliente_detalhes.php
 */
?>
<div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start;">
    
    <!-- Linha do Tempo de Movimentos -->
    <div>
        <div class="admin-header-row">
            <div>
                <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Histórico do Processo</h3>
                <p class="admin-subtitle">Eventos, despachos técnicos e datas registradas.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-save" onclick="document.getElementById('modalAndamentoNew').showModal()">
                    <span class="material-symbols-rounded">add_circle</span> Novo Andamento
                </button>
                <form action="../actions/admin/movimento_clear_all.php" method="POST" class="inline-form" style="display: inline-block;"
                      @submit.prevent="deleteItem($event, 'ATENÇÃO: Deseja apagar TODO o histórico deste cliente? Essa ação é irreversível.')">
                    <?php echo Csrf::getHtmlField(); ?>
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                    <button type="submit" class="btn-save btn-danger" style="border: none; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded">delete_sweep</span> Limpar Tudo
                    </button>
                </form>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Movimentação / Evento</th>
                        <th style="text-align: right; padding-right: 20px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmtMov = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                    $stmtMov->execute([$cliente['id']]);
                    $movimentos = $stmtMov->fetchAll();

                    if (empty($movimentos)): ?>
                        <tr>
                            <td colspan="3" style="padding: 40px; text-align: center; color: var(--color-muted); font-style: italic;">
                                Nenhuma movimentação registrada para este processo ainda.
                            </td>
                        </tr>
                    <?php else: foreach ($movimentos as $m): 
                        $accent = '';
                        $tipo = $m['tipo_movimento'] ?? 'padrao';
                        if ($tipo === 'fase_inicio') {
                            $accent = 'border-left: 4px solid #6610f2; background: rgba(102, 16, 242, 0.02);';
                        } elseif ($tipo === 'documento') {
                            $accent = 'border-left: 4px solid var(--color-primary); background: var(--color-primary-tint);';
                        }
                    ?>
                        <tr style="<?php echo $accent; ?>">
                            <td style="white-space: nowrap; vertical-align: top; color: var(--color-text-subtle); padding: 14px;">
                                <?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?>
                            </td>
                            <td style="padding: 14px; vertical-align: top;">
                                <div style="font-weight: 700; color: var(--color-text); margin-bottom: 4px;"><?php echo htmlspecialchars($m['titulo_fase']); ?></div>
                                <?php 
                                $parts = explode("||COMENTARIO_USER||", $m['descricao']);
                                echo "<div style='color: var(--color-text-subtle); line-height: 1.5; font-size: 0.9rem;'>{$parts[0]}</div>";
                                if (count($parts) > 1 && !empty(trim($parts[1]))) {
                                    echo "<div style='margin-top: 8px; border-left: 3px solid var(--color-danger); padding-left: 10px; color: var(--color-danger); font-weight: 600; font-size: 0.88rem;'>
                                            <strong>Diego Vilela:</strong> " . nl2br(htmlspecialchars($parts[1])) . "
                                          </div>";
                                }
                                ?>
                            </td>
                            <td style="text-align: right; vertical-align: top; padding: 14px 20px 14px 14px;">
                                <form action="../actions/admin/movimento_delete.php" method="POST" class="inline-form" style="display: inline;"
                                      @submit.prevent="deleteItem($event, 'Deseja excluir esta movimentação?')">
                                    <?php echo Csrf::getHtmlField(); ?>
                                    <input type="hidden" name="movimento_id" value="<?php echo $m['id']; ?>">
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

    <!-- Painel Lateral: Atualizar Etapa e WhatsApp -->
    <div class="form-card" style="padding: 20px; border: 1px solid var(--color-border); background: #fafbfc; position: sticky; top: 10px;">
        <h4 style="margin: 0 0 14px 0; color: var(--color-primary-dark); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded">update</span> Atualizar Etapa da Obra
        </h4>
        
        <form action="../actions/admin/etapa_update.php" method="POST" @submit.prevent="submitForm($event)">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            <input type="hidden" name="titulo_evento" value="Atualização de Etapa">
            <input type="hidden" name="observacao_etapa" value="Etapa alterada pelo painel de controle.">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--color-text-subtle);">Etapa Atual do Processo</label>
                <select name="nova_etapa" class="proc-select" required style="background: white; border: 1px solid var(--color-border);">
                    <?php foreach (Processo::$fases_padrao as $fase): ?>
                        <option value="<?php echo $fase; ?>" <?php echo ($detalhes['etapa_atual'] ?? '') === $fase ? 'selected' : ''; ?>>
                            <?php echo $fase; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="atualizar_etapa" class="btn-save btn-primary" style="width: 100%; padding: 11px; font-size: 0.88rem; border-radius: 8px;">
                Salvar Nova Etapa
            </button>
        </form>
        
        <!-- PAINEL DE PRAZOS DA PREFEITURA (FEAT-04) -->
        <div style="margin-top: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #dc7a0d; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded">calendar_month</span> Prazo da Prefeitura
            </h4>
            <p style="font-size: 0.8rem; color: var(--color-text-subtle); margin-bottom: 14px;">Defina a data limite para exigências ou validade de licenças.</p>
            
            <form action="../actions/admin/prazo_prefeitura_update.php" method="POST" @submit.prevent="submitForm($event)">
                <?php echo Csrf::getHtmlField(); ?>
                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--color-text-subtle);">Data Limite</label>
                    <input type="date" name="prazo_prefeitura_data" class="admin-form-input" value="<?php echo $detalhes['prazo_prefeitura_data'] ?? ''; ?>" style="background: white; border: 1px solid var(--color-border); padding: 6px 10px; font-size: 0.85rem; border-radius: 6px; width: 100%; box-sizing: border-box;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--color-text-subtle);">Descrição do Prazo</label>
                    <input type="text" name="prazo_prefeitura_descricao" class="admin-form-input" value="<?php echo htmlspecialchars($detalhes['prazo_prefeitura_descricao'] ?? ''); ?>" placeholder="Ex: Protocolo de Habite-se" style="background: white; border: 1px solid var(--color-border); padding: 6px 10px; font-size: 0.85rem; border-radius: 6px; width: 100%; box-sizing: border-box;">
                </div>
                
                <button type="submit" class="btn-save btn-primary" style="width: 100%; padding: 9px; font-size: 0.85rem; border-radius: 6px; background: #dc7a0d; border-color: #dc7a0d; border: none; cursor: pointer; color: white;">
                    Salvar Prazo
                </button>
            </form>
        </div>
        
        <!-- DISPARADOR INTEGRADO DE WHATSAPP -->
        <div style="margin-top: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
            <h4 style="margin: 0 0 8px 0; color: #128c7e; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded">chat</span> Disparar WhatsApp
            </h4>
            <p style="font-size: 0.8rem; color: var(--color-text-subtle); margin-bottom: 14px;">Envie uma mensagem formatada ao cliente sobre o andamento atualizado da obra com 1 clique.</p>
            
            <?php 
            $cli_tel_clean = preg_replace('/\D/', '', $detalhes['contato_tel'] ?? '');
            if (substr($cli_tel_clean, 0, 2) !== '55' && strlen($cli_tel_clean) >= 10) {
                $cli_tel_clean = '55' . $cli_tel_clean;
            }
            $whats_msg = "*Vilela Engenharia — Atualização do Processo* \n\nOlá, " . $cliente['nome'] . "! Atualizamos o andamento do seu processo na Vilela Engenharia para a etapa:\n\n📍 *_" . ($detalhes['etapa_atual'] ?? 'Não iniciada') . "_*\n\nVocê pode conferir o andamento completo, pendências e baixar documentos na sua Área do Cliente em:\n🌐 *vilela.eng.br/area-cliente*";
            $whats_url = "https://wa.me/" . $cli_tel_clean . "?text=" . urlencode($whats_msg);
            ?>
            
            <?php if (!empty($cli_tel_clean)): ?>
                <a href="<?php echo $whats_url; ?>" target="_blank" class="btn-save" style="background: #25d366; color: white; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; border: none; text-decoration: none; border-radius: 8px; font-size: 0.88rem; padding: 11px;">
                    <span class="material-symbols-rounded">send</span> Enviar pelo WhatsApp
                </a>
            <?php else: ?>
                <button type="button" class="btn-save btn-ghost" style="width: 100%; opacity: 0.5; font-size: 0.85rem;" disabled>
                    Telefone não cadastrado
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Novo Andamento / Movimentação -->
<dialog id="modalAndamentoNew">
    <div style="background: var(--color-primary); padding: 20px; display: flex; justify-content: space-between; align-items: center; color: white;">
        <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded">history</span> Novo Andamento do Processo
        </h3>
        <button type="button" onclick="document.getElementById('modalAndamentoNew').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>
    <div style="padding: 25px;">
        <form action="../actions/admin/etapa_update.php" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm($event)">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Fase / Etapa Correspondente</label>
                <select name="nova_etapa" class="proc-select" style="background: #fafbfc; border: 1px solid var(--color-border);">
                    <option value="">Manter atual: <?php echo htmlspecialchars($detalhes['etapa_atual'] ?? 'Não iniciada'); ?></option>
                    <?php foreach (Processo::$fases_padrao as $f): ?>
                        <option value="<?php echo $f; ?>"><?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Título da Atualização <span style="color: red;">*</span></label>
                <input type="text" name="titulo_evento" required placeholder="Ex: Entrada do Projeto Realizada na Prefeitura" class="admin-form-input">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Comentários e Detalhes</label>
                <textarea name="observacao_etapa" rows="3" placeholder="Insira informações de prazos, taxas ou andamento da análise técnica..." class="admin-form-input" style="resize: vertical; font-family: inherit;"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Anexar Documento Técnico (Opcional)</label>
                <input type="file" name="arquivo_documento" class="admin-form-input">
                <small style="color: var(--color-text-subtle); display: block; margin-top: 4px;">Ex: PDF de taxas ou comprovante de protocolo.</small>
            </div>

            <button type="submit" name="atualizar_etapa" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
                Gravar Andamento
            </button>
        </form>
    </div>
</dialog>
