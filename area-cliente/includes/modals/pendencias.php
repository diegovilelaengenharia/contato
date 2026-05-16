<!-- Modal Nova Pendência -->
<dialog id="modalNovaPendencia" style="border:none; border-radius:12px; padding:0; width:90%; max-width:600px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:#fff3e0; border-bottom:1px solid #ffe0b2; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; color:#ef6c00;">➕ Adicionar Nova Pendência</h3>
        <button onclick="document.getElementById('modalNovaPendencia').close()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#ef6c00;">&times;</button>
    </div>
    
    <form method="POST" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        
        <div style="display:flex; flex-direction:column; gap:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#555;">Título</label>
                <input type="text" name="titulo_pendencia" placeholder="Ex: RG, CPF, Planta Baixa..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" required>
            </div>
            
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#555;">Arquivo (Opcional)</label>
                <input type="file" name="arquivo_pendencia_admin" style="width:100%; padding:8px; background:#f9f9f9; border:1px solid #ddd; border-radius:8px;">
            </div>

            <div>
                 <label style="display:block; margin-bottom:5px; font-weight:bold; color:#555;">Descrição Detalhada</label>
                 <textarea name="descricao_pendencia" id="new_pendencia_editor" placeholder="Digite a descrição..." style="width:100%;"></textarea>
            </div>
            
            <div style="text-align:right; border-top:1px solid #eee; padding-top:15px; margin-top:10px;">
                <button type="button" onclick="document.getElementById('modalNovaPendencia').close()" style="padding:10px 20px; border:1px solid #ddd; background:white; border-radius:6px; margin-right:10px; cursor:pointer;">Cancelar</button>
                <button type="submit" name="btn_adicionar_pendencia" class="btn-save" style="width:auto; margin:0; padding:10px 25px; color:white; background: #fd7e14; border:none; border-radius:6px;">Salvar Pendência</button>
            </div>
        </div>
    </form>
</dialog>

<!-- Modal Editar Pendência -->
<dialog id="modalEditPendencia" style="border:none; border-radius:10px; padding:0; width:90%; max-width:600px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <form method="POST" style="display:flex; flex-direction:column;">
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        <input type="hidden" name="pendencia_id" id="edit_pendencia_id">
        
        <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">✏️ Editar Pendência</h3>
            <button type="button" onclick="document.getElementById('modalEditPendencia').close()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <div style="padding:20px;">
            <label style="display:block; margin-bottom:8px; font-weight:bold;">Descrição</label>
            <textarea name="descricao_pendencia" id="edit_pendencia_texto" rows="4" style="width:100%;"></textarea>
        </div>
        
        <div style="padding:20px; background:#f9f9f9; text-align:right;">
            <button type="button" onclick="document.getElementById('modalEditPendencia').close()" style="padding:10px 15px; border:1px solid #ddd; background:#fff; border-radius:5px; margin-right:10px; cursor:pointer;">Cancelar</button>
            <button type="submit" name="btn_editar_pendencia" class="btn-save btn-primary" style="width:auto; margin:0;">Salvar Alteração</button>
        </div>
    </form>
</dialog>

<!-- Modal Cobrar Cliente (Refeito) -->
<dialog id="modalChargeNew" style="border:none; border-radius:12px; padding:0; width:90%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:var(--color-primary); color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.2rem;">📱 Cobrar Pendências</h3>
        <button onclick="document.getElementById('modalChargeNew').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <div style="padding:25px;">
        <div style="background:#f0f8ff; border-left:4px solid #0056b3; padding:15px; margin-bottom:20px; border-radius:4px;">
            <strong style="color:#0056b3; display:block; margin-bottom:5px;">💡 Profissionalismo</strong>
            <span style="font-size:0.9rem; color:#444;">Modelo de mensagem pronto com a lista de pendências.</span>
        </div>

        <label style="display:block; margin-bottom:10px; font-weight:bold; color:#333;">Mensagem:</label>
        <textarea id="chargeTextNew" rows="12" style="width:100%; border:1px solid #ccc; border-radius:8px; padding:15px; font-family:monospace; background:#fafafa; font-size:0.9rem; resize:vertical;" readonly></textarea>
        
        <div style="margin-top:20px; display:flex; gap:10px;">
            <button type="button" onclick="copyChargeTextNew()" class="btn-save" style="flex:1; justify-content:center; background:var(--color-primary);">📋 Copiar</button>
            <a id="btnOpenWhatsNew" href="#" target="_blank" class="btn-save" style="flex:1; justify-content:center; background:#25D366; text-align:center; text-decoration:none;">Abrir WhatsApp</a>
        </div>
    </div>
</dialog>

<script>
    let editorEdicao;

    // Inicializa Editor de Edição (Se não foi inicializado ainda)
    document.addEventListener('DOMContentLoaded', () => {
        // Editor Nova Pendência
        if(document.querySelector('#new_pendencia_editor')) {
            ClassicEditor
            .create(document.querySelector('#new_pendencia_editor'), {
                toolbar: [ 'bold', 'italic', 'link', 'bulletedList', '|', 'undo', 'redo' ],
                language: 'pt-br',
                placeholder: 'Digite a descrição detalhada da pendência...'
            })
            .catch( error => { console.error( error ); } );
        }

        // Editor Edição Check
        if(document.querySelector( '#edit_pendencia_texto' )) {
             ClassicEditor
            .create( document.querySelector( '#edit_pendencia_texto' ), {
                toolbar: [ 'bold', 'italic', 'link', 'bulletedList', '|', 'undo', 'redo' ],
                language: 'pt-br'
            } )
            .then( newEditor => { editorEdicao = newEditor; } )
            .catch( error => { console.error( error ); } );
        }
    });

    function openEditPendencia(id, textoHtml) {
        document.getElementById('edit_pendencia_id').value = id;
        // Seta dados no CKEditor
        if(editorEdicao) {
            editorEdicao.setData(textoHtml);
        } else {
             document.getElementById('edit_pendencia_texto').value = textoHtml;
        }
        document.getElementById('modalEditPendencia').showModal();
    }
</script>
