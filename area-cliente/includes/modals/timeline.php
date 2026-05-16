<!-- Modal Timeline Completa -->
<dialog id="modalTimelineFull" style="border:none; border-radius:12px; padding:0; width:90%; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:#f8f9fa; padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1rem; color:#333;">Todas as Etapas</h3>
        <button onclick="document.getElementById('modalTimelineFull').close()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div style="padding:20px; max-height:60vh; overflow-y:auto;">
        <?php 
            // Define Groups
            $grupos = [
                '🚀 Fase Inicial' => array_slice($fases_padrao, 0, 4), // 0-3
                '🏗️ Análise Técnica' => array_slice($fases_padrao, 4, 2), // 4-5
                '📄 Emissão de Documentos' => array_slice($fases_padrao, 6, 3) // 6-8
            ];
            
            $global_index = 0;
            $found_idx = array_search(($detalhes['etapa_atual']??''), $fases_padrao);
            if($found_idx === false) $found_idx = -1;

            foreach($grupos as $nome_grupo => $fases_grupo):
        ?>
            <div style="margin-bottom:20px;">
                <h4 style="margin:0 0 10px 0; font-size:0.85rem; color:#999; text-transform:uppercase; font-weight:700; letter-spacing:1px; background:#fff; padding:5px 0; border-radius:4px; display:inline-block;">
                    <?= $nome_grupo ?>
                </h4>
                
                <div style="padding-left:15px; border-left: 2px solid #f0f0f0;">
                <?php
                    foreach($fases_grupo as $fase):
                        $is_past = $global_index < $found_idx;
                        $is_curr = $global_index === $found_idx;
                        
                        // Icons
                        $icon_display = '▫️'; 
                        if($is_past) $icon_display = '✅';
                        if($is_curr) $icon_display = '📍';
                        
                        $text_style = $is_curr ? 'font-weight:700; color:#333;' : ($is_past ? 'color:#198754;' : 'color:#aaa;');
                        $bg_item = $is_curr ? '#fff' : 'transparent';
                        $border_item = $is_curr ? '1px solid #198754' : '1px solid transparent';
                ?>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px; border-radius:6px; background:<?= $bg_item ?>; border:<?= $border_item ?>; margin-bottom:5px;">
                        <span style="font-size:1.2rem; min-width:25px; text-align:center;"><?= $icon_display ?></span>
                        <div style="display:flex; flex-direction:column;">
                            <span style="font-size:0.9rem; <?= $text_style ?>">
                                <?= $fase ?>
                            </span>
                            <?php if($is_curr): ?>
                                <span style="font-size:0.7rem; color:#198754; font-weight:700; text-transform:uppercase;">Em Andamento</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    $global_index++; 
                    endforeach; 
                ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</dialog>

<!-- MODAL VISUAL TIMELINE (IFRAME) -->
<dialog id="modalVisualTimeline" style="border:none; border-radius:12px; padding:0; width:90%; height:80vh; max-width:1100px; box-shadow:0 10px 60px rgba(0,0,0,0.3);">
    <div style="background:#fff; height:100%; display:flex; flex-direction:column;">
        <!-- Header Modal -->
        <div style="padding:15px 25px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#fff;">
            <div>
                <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:#2c3e50; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#0d6efd;">visibility</span> Timeline do Cliente
                </h3>
                <p style="margin:4px 0 0 0; font-size:0.85rem; color:#777;">Visualização exata que o cliente vê no painel dele.</p>
            </div>
            <button type="button" onclick="document.getElementById('modalVisualTimeline').close()" style="border:none; background:none; color:#999; font-size:2rem; cursor:pointer; line-height:1; transition:color 0.2s;">&times;</button>
        </div>

        <!-- iframe Content -->
        <div style="flex:1; background:#f4f6f8; position:relative; overflow:hidden;">
             <!-- Passamos o ID do cliente se necessário, aqui assume sessão ou parametro -->
             <!-- Importante: Ajustar URL para incluir cliente_id se a sessão admin não passar pra iframe auto -->
             <iframe src="area_cliente.php?simular_timeline=1" style="width:100%; height:100%; border:none; display:block;"></iframe>
        </div>
    </div>
</dialog>
<style>
    #modalVisualTimeline::backdrop {
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(2px);
    }
    /* Button Hover for Close */
    #modalVisualTimeline button:hover { color: #333; }
</style>

<!-- MODAL DE NOVO ANDAMENTO -->
<dialog id="modalAndamento" style="border:none; border-radius:12px; padding:0; width:90%; max-width:600px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
    <div style="background: linear-gradient(135deg, var(--color-primary) 0%, #2980b9 100%); padding:20px; display:flex; justify-content:space-between; align-items:center; color:white;">
        <h3 style="margin:0; font-size:1.2rem; display:flex; align-items:center; gap:10px;">✨ Novo Andamento</h3>
        <button type="button" onclick="document.getElementById('modalAndamento').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <div style="padding:25px;">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
            
            <!-- LINHA 1: Fase e Título -->
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.85rem; font-weight:bold; color:#555; margin-bottom:5px;">📌 Fase</label>
                <select name="nova_etapa" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:1rem; background:#f9f9f9;">
                    <option value="">Manter: <?= htmlspecialchars($detalhes['etapa_atual']??'-') ?></option>
                    <?php foreach($fases_padrao as $f): ?>
                        <option value="<?= $f ?>"><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.85rem; font-weight:bold; color:#555; margin-bottom:5px;">📝 Título do Evento</label>
                <input type="text" name="titulo_evento" required placeholder="Ex: Protocolo Realizado..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:1rem;">
            </div>
            
            <!-- LINHA 2: Descrição -->
            <div style="margin-bottom:15px;">
                <textarea name="observacao_etapa" rows="3" placeholder="Detalhes (Opcional)..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:1rem; resize:vertical; font-family:inherit;"></textarea>
            </div>

            <!-- Upload -->
            <div style="margin-bottom:20px;">
                    <div style="width:100%; border:2px dashed #ccc; border-radius:8px; padding:15px; text-align:center; cursor:pointer; background:#f8f9fa;" onclick="document.getElementById('file_input_modal').click();" id="dropzone_modal">
                        <span style="font-size:1.5rem; display:block; margin-bottom:5px;">📂</span>
                        <span style="font-weight:bold; color:#666;">Anexar Arquivo</span>
                        <input type="file" id="file_input_modal" name="arquivo_documento" style="display:none;" onchange="if(this.files.length > 0) { document.getElementById('dropzone_modal').style.borderColor='#198754'; document.getElementById('dropzone_modal').style.background='#e8f5e9'; document.getElementById('dropzone_modal').querySelector('span:last-child').innerText = '✅ Arquivo Selecionado!'; }">
                    </div>
            </div>

            <!-- BOTÃO -->
            <button type="submit" name="atualizar_etapa" class="btn-save" style="width:100%; padding:12px; background:var(--color-primary); border:none; border-radius:8px; font-size:1.1rem; font-weight:bold; color:white; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                ✅ Registrar Movimentação
            </button>
        </form>
    </div>
</dialog>
