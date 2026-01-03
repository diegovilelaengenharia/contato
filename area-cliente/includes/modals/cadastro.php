<!-- Modal Aprovar Cadastro -->
<dialog id="modalAprovarCadastro" style="border:none; border-radius:12px; padding:0; width:90%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:var(--color-primary); color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.2rem;">✅ Aprovar e Finalizar</h3>
        <button onclick="document.getElementById('modalAprovarCadastro').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <form method="POST" style="padding:25px;">
        <input type="hidden" name="id_pre" id="apr_id_pre">
        
        <div class="form-group">
            <label>Nome do Cliente</label>
            <input type="text" name="nome_final" id="apr_nome" required>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Usuário de Login (CPF)</label>
                <input type="text" name="usuario_final" id="apr_usuario" required>
            </div>
            <div class="form-group">
                <label>Senha Inicial</label>
                <input type="text" name="senha_final" value="mudar123" required>
            </div>
        </div>
        
        <hr style="margin:20px 0; border-top:1px solid #eee;">
        
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" name="btn_confirmar_aprovacao" class="btn-save" style="width:100%;">🚀 Confirmar e Criar Cliente</button>
        </div>
    </form>
</dialog>

<script>
    function openAprovarModal(id, nome, cpf) {
        document.getElementById('apr_id_pre').value = id;
        document.getElementById('apr_nome').value = nome;
        document.getElementById('apr_usuario').value = cpf.replace(/\D/g, ''); // Sugere CPF limpo como login
        document.getElementById('modalAprovarCadastro').showModal();
    }
</script>
