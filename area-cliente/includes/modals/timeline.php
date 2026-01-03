<!-- Modal Timeline Completa -->
<dialog id="modalTimelineFull" style="border:none; border-radius:12px; padding:0; width:90%; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:#f8f9fa; padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1rem; color:#333;">Todas as Etapas</h3>
        <button onclick="document.getElementById('modalTimelineFull').close()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div style="padding:20px; max-height:60vh; overflow-y:auto;">
        <div style="display:flex; flex-direction:column; gap:4px;">
            <?php foreach($fases_padrao as $i => $f): 
                    $found_idx = array_search(($detalhes['etapa_atual']??''), $fases_padrao);
                    if($found_idx === false) $found_idx = -1;
                    
                    $is_past = $i < $found_idx;
                    $is_active = $i == $found_idx;
                    $color = $is_active ? 'var(--color-primary)' : ($is_past ? '#198754' : '#ccc');
                    $icon = $is_past ? '✅' : ($is_active ? '📍' : '▫️');
                    $weight = $is_active ? '700' : '400';
                    $bg_item = $is_active ? '#e8f5e9' : 'transparent';
            ?>
                <div style="display:flex; align-items:center; gap:10px; padding:8px; border-radius:6px; background:<?= $bg_item ?>">
                    <span style="font-size:1rem;"><?= $icon ?></span>
                    <span style="color:<?= $color ?>; font-weight:<?= $weight ?>; font-size:0.9rem;"><?= $f ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</dialog>

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
