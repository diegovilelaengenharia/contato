<?php
// includes/form_cliente_template.php
// Este arquivo é usado tanto para Cadastrar Novo Cliente quanto para Editar Cliente.
// Ele espera que variáveis como $cliente, $detalhes, $campos_extras estejam definidas se for edição.

$is_edit = isset($cliente) && !empty($cliente);
$action_url = $is_edit ? "actions/admin/cliente_update.php" : "actions/admin/cliente_create.php";
$btn_text = $is_edit ? "💾 Salvar Alterações" : "✅ Criar Cadastro Completo";
$btn_name = $is_edit ? "btn_salvar_tudo" : "novo_cliente";
$hidden_acao = $is_edit ? "editar_cliente_completo" : "novo_cliente";

// Dados para preenchimento (Null Coalescing para evitar Warnings no cadastro)
$d_nome = $cliente['nome'] ?? '';
$d_usuario = $cliente['usuario'] ?? '';
$d_cpf = $detalhes['cpf_cnpj'] ?? '';
$d_rg = $detalhes['rg_ie'] ?? '';
$d_nacionalidade = $detalhes['nacionalidade'] ?? '';
$d_nasc = $detalhes['data_nascimento'] ?? '';
$d_profissao = $detalhes['profissao'] ?? '';
$d_civil = $detalhes['estado_civil'] ?? '';
$d_conjuge = $detalhes['nome_conjuge'] ?? '';
$d_cpf_conjuge = $detalhes['cpf_conjuge'] ?? '';
$d_procurador = $detalhes['eh_procurador'] ?? 0;
$d_tel = $detalhes['contato_tel'] ?? '';
$d_email = $detalhes['contato_email'] ?? '';

// Endereços
$r_rua = $detalhes['res_rua'] ?? '';
$r_num = $detalhes['res_numero'] ?? '';
$r_bairro = $detalhes['res_bairro'] ?? '';
$r_comp = $detalhes['res_complemento'] ?? '';
$r_cid = $detalhes['res_cidade'] ?? '';
$r_uf = $detalhes['res_uf'] ?? '';

$i_serv = $detalhes['tipo_servico'] ?? '';
$i_rua = $detalhes['imovel_rua'] ?? '';
$i_num = $detalhes['imovel_numero'] ?? '';
$i_bairro = $detalhes['imovel_bairro'] ?? '';
$i_comp = $detalhes['imovel_complemento'] ?? '';
$i_cid = $detalhes['imovel_cidade'] ?? '';
$i_uf = $detalhes['imovel_uf'] ?? '';

$i_iptu = $detalhes['inscricao_imob'] ?? '';
$i_mat = $detalhes['num_matricula'] ?? '';
$i_lote = $detalhes['imovel_area_lote'] ?? ($detalhes['area_terreno'] ?? '');
$i_area = $detalhes['area_construida'] ?? '';

// Dados do Processo
$p_num = $detalhes['processo_numero'] ?? ($detalhes['numero_processo'] ?? '');
$p_data = $detalhes['data_inicio'] ?? '';
$p_obj = $detalhes['processo_objeto'] ?? ($detalhes['objeto_processo'] ?? '');
$p_tipo_chave = $detalhes['tipo_processo_chave'] ?? '';

?>

<form action="<?= $action_url ?>" method="POST" enctype="multipart/form-data" class="main-wrapper" style="<?= $is_edit ? '' : 'box-shadow:none; padding:0;' ?>">
    <?= Csrf::getHtmlField() ?>
    <input type="hidden" name="acao" value="<?= $hidden_acao ?>">
    <?php if($is_edit): ?>
        <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
    <?php endif; ?>

    <!-- 1. ACESSO & FOTOS -->
    <h3 style="margin:0 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">1. Acesso & Fotos</h3>
    <div style="display:flex; gap:20px; margin-bottom:20px;">
        <div style="flex:1;">
            <label style="display:block; margin-bottom:5px; font-weight:bold;">📸 Foto de Perfil</label>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="file" name="avatar_upload" accept="image/*" style="padding:10px; border:1px solid #ddd; border-radius:8px; width:100%;">
                <?php 
                if($is_edit) {
                    $avatar = glob("uploads/avatars/avatar_{$cliente['id']}.*");
                    if(!empty($avatar)) echo "<img src='{$avatar[0]}?".time()."' style='width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid #ddd;'>";
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="form-grid">
        <div class="form-group"><label>Nome Completo (Titular)</label><input type="text" name="nome" value="<?= htmlspecialchars($d_nome) ?>" required placeholder="Ex: João da Silva"></div>
        <div class="form-group">
            <label>Login de Acesso (Usuário) <span style="font-size:0.75rem; color:#888;">(Pode usar CPF ou Tel)</span></label>
            <input type="text" name="usuario" id="campo_usuario" value="<?= htmlspecialchars($d_usuario) ?>" required style="font-family:monospace; color:#2980b9; font-weight:bold; letter-spacing:1px;" placeholder="Digite ou gere automático...">
            
            <?php if(!$is_edit): ?>
                <!-- Helper de Geração Automática -->
                <div style="display:flex; gap:15px; align-items:center; margin-top:5px;">
                     <label style="display:flex; align-items:center; gap:5px; font-size:0.8rem; cursor:pointer; color:#666;">
                        <input type="radio" name="auto_login_source" value="cpf" checked onchange="atualizarLoginAuto()"> Usar CPF
                     </label>
                     <label style="display:flex; align-items:center; gap:5px; font-size:0.8rem; cursor:pointer; color:#666;">
                        <input type="radio" name="auto_login_source" value="tel" onchange="atualizarLoginAuto()"> Usar Telefone
                     </label>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group"><label><?= $is_edit ? 'Nova Senha (Opcional)' : 'Senha Inicial' ?></label><input type="text" name="<?= $is_edit ? 'nova_senha' : 'senha' ?>" placeholder="<?= $is_edit ? 'Preencha se for trocar' : 'Ex: 123456' ?>" <?= $is_edit ? '' : 'required' ?>></div>
    </div>

    <?php if($is_edit): ?>
        <!-- Caixa de Compartilhamento de Credenciais por WhatsApp -->
        <div style="background: #eef6f2; border: 1px solid var(--color-primary-soft); border-radius: 12px; padding: 18px; margin-top: 15px; margin-bottom: 25px; box-shadow: var(--shadow-sm);">
            <h4 style="margin: 0 0 8px 0; color: var(--color-primary-dark); display: flex; align-items: center; gap: 8px; font-size: 0.95rem;">
                <span class="material-symbols-rounded" style="color: var(--color-primary);">share</span>
                Enviar Acesso ao Portal (WhatsApp)
            </h4>
            <p style="font-size: 0.82rem; color: var(--color-text-subtle); margin: 0 0 12px 0;">
                Use os botões abaixo para copiar ou enviar os dados de login formatados diretamente ao cliente pelo WhatsApp.
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" onclick="copiarCredenciais()" class="btn-std" style="background: var(--color-surface); border: 1px solid var(--color-primary); color: var(--color-primary); padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">content_copy</span> Copiar Credenciais
                </button>
                <button type="button" onclick="enviarCredenciaisWhatsApp()" class="btn-std" style="background: #25d366; border: none; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.85rem; box-shadow: 0 2px 6px rgba(37,211,102,0.25);">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.003 5.324 5.328 0 11.94 0c3.205.002 6.216 1.252 8.48 3.518 2.262 2.268 3.509 5.282 3.507 8.49-.005 6.618-5.33 11.942-11.94 11.942-2.01-.001-3.987-.507-5.744-1.468L0 24zm6.58-15.602c-.172-.383-.344-.39-.505-.397-.13-.005-.28-.005-.43-.005-.15 0-.396.056-.604.283-.207.227-.79.772-.79 1.885 0 1.112.809 2.187.922 2.338.113.15 1.593 2.43 3.86 3.414.54.234.96.374 1.288.478.543.173 1.037.149 1.429.09.436-.066 1.344-.55 1.533-1.08.188-.528.188-.98.132-1.075-.056-.095-.207-.15-.434-.264-.227-.113-1.344-.664-1.552-.739-.208-.076-.36-.113-.508.113-.15.227-.58.73-.711.88-.13.15-.26.168-.487.055-.227-.113-.96-.353-1.83-1.127-.676-.602-1.133-1.347-1.266-1.573-.13-.227-.014-.35.1-.462.103-.1.227-.264.34-.396.113-.132.15-.226.226-.377.076-.15.038-.283-.019-.396-.057-.113-.505-1.22-.693-1.67z"/>
                    </svg> Enviar por WhatsApp
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. DADOS PESSOAIS -->
    <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">2. Dados Pessoais</h3>
    <div class="form-grid">
        <div class="form-group" style="grid-column: span 2; display:flex; align-items:center; gap:10px; background:#f0f8ff; padding:10px; border-radius:8px; border:1px solid #cce5ff;">
            <label style="margin:0; font-weight:bold; color:#0056b3;">O cliente é Procurador?</label>
            <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="radio" name="eh_procurador" value="1" <?= $d_procurador == 1 ? 'checked' : '' ?>> Sim</label>
            <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="radio" name="eh_procurador" value="0" <?= $d_procurador == 0 ? 'checked' : '' ?>> Não</label>
        </div>
        <div class="form-group"><label>CPF / CNPJ <span style="color:red">*</span></label><input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($d_cpf) ?>" required oninput="atualizarLoginAuto()"></div>
        <div class="form-group"><label>RG / Inscrição Estadual</label><input type="text" name="rg_ie" value="<?= htmlspecialchars($d_rg) ?>"></div>
        <div class="form-group"><label>Nacionalidade</label><input type="text" name="nacionalidade" value="<?= htmlspecialchars($d_nacionalidade) ?>"></div>
        <div class="form-group"><label>Data Nascimento</label><input type="date" name="data_nascimento" value="<?= htmlspecialchars($d_nasc) ?>"></div>
        <div class="form-group"><label>Profissão</label><input type="text" name="profissao" value="<?= htmlspecialchars($d_profissao) ?>"></div>
        <div class="form-group">
            <label>Estado Civil</label>
            <select name="estado_civil" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                <?php 
                $opts = ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União Estável'];
                foreach($opts as $o) {
                    $sel = ($d_civil == $o) ? 'selected' : '';
                    echo "<option value='$o' $sel>$o</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group"><label>Nome Cônjuge</label><input type="text" name="nome_conjuge" value="<?= htmlspecialchars($d_conjuge) ?>"></div>
        <div class="form-group"><label>CPF Cônjuge</label><input type="text" name="cpf_conjuge" value="<?= htmlspecialchars($d_cpf_conjuge) ?>"></div>
        <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="contato_tel" value="<?= htmlspecialchars($d_tel) ?>" oninput="atualizarLoginAuto()"></div>
        <div class="form-group"><label>Email</label><input type="email" name="contato_email" value="<?= htmlspecialchars($d_email) ?>"></div>
    </div>

    <!-- 3. ENDEREÇO RESIDENCIAL -->
    <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">3. Endereço Residencial</h3>
    <div class="form-grid">
        <div class="form-group" style="grid-column: span 2;">
            <label>CEP Residencial</label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="res_cep" name="res_cep_temp" placeholder="00000-000" style="flex:1; padding:10px; border:1px solid #ddd; border-radius:8px;">
                <button type="button" onclick="buscarCepResidencial()" class="btn-std" style="padding:10px 15px; border-radius:8px; border:1px solid var(--color-primary); background:transparent; color:var(--color-primary); font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px;">
                    <span class="material-symbols-rounded" style="font-size:1.1rem;">search</span> Buscar
                </button>
            </div>
        </div>
        <div class="form-group" style="grid-column: span 2;"></div> <!-- Espaçador no grid -->
        <div class="form-group" style="grid-column: span 2;"><label>Rua / Logradouro</label><input type="text" name="res_rua" value="<?= htmlspecialchars($r_rua) ?>"></div>
        <div class="form-group"><label>Número</label><input type="text" name="res_numero" value="<?= htmlspecialchars($r_num) ?>"></div>
        <div class="form-group"><label>Bairro</label><input type="text" name="res_bairro" value="<?= htmlspecialchars($r_bairro) ?>"></div>
        <div class="form-group"><label>Complemento</label><input type="text" name="res_complemento" value="<?= htmlspecialchars($r_comp) ?>"></div>
        <div class="form-group"><label>Cidade</label><input type="text" name="res_cidade" value="<?= htmlspecialchars($r_cid) ?>"></div>
        <div class="form-group"><label>UF</label><input type="text" name="res_uf" value="<?= htmlspecialchars($r_uf) ?>" maxlength="2" style="text-transform:uppercase;"></div>
    </div>

    <!-- 4. DADOS DO IMÓVEL / OBRA -->
    <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">4. Dados do Imóvel / Obra</h3>
    <div class="form-grid">
        <div class="form-group" style="grid-column: span 3;">
            <label>Tipo de Serviço</label>
            <select name="tipo_servico" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                <?php 
                $servicos = ['Regularização de Imóvel', 'Projeto Arquitetônico', 'Projeto Estrutural', 'Desmembramento', 'Laudo Técnico', 'Outros'];
                foreach($servicos as $s) {
                    $sel = ($i_serv == $s) ? 'selected' : '';
                    echo "<option value='$s' $sel>$s</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group" style="grid-column: span 2;">
            <label>CEP do Imóvel / Obra</label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="imovel_cep" name="imovel_cep_temp" placeholder="00000-000" style="flex:1; padding:10px; border:1px solid #ddd; border-radius:8px;">
                <button type="button" onclick="buscarCepImovel()" class="btn-std" style="padding:10px 15px; border-radius:8px; border:1px solid var(--color-primary); background:transparent; color:var(--color-primary); font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px;">
                    <span class="material-symbols-rounded" style="font-size:1.1rem;">search</span> Buscar
                </button>
            </div>
        </div>
        <div class="form-group"></div> <!-- Espaçador no grid -->
        <div class="form-group" style="grid-column: span 2;"><label>Rua / Logradouro (Obra)</label><input type="text" name="imovel_rua" value="<?= htmlspecialchars($i_rua) ?>"></div>
        <div class="form-group"><label>Número</label><input type="text" name="imovel_numero" value="<?= htmlspecialchars($i_num) ?>"></div>
        <div class="form-group"><label>Bairro</label><input type="text" name="imovel_bairro" value="<?= htmlspecialchars($i_bairro) ?>"></div>
        <div class="form-group"><label>Complemento</label><input type="text" name="imovel_complemento" value="<?= htmlspecialchars($i_comp) ?>"></div>
        <div class="form-group"><label>Cidade</label><input type="text" name="imovel_cidade" value="<?= htmlspecialchars($i_cid) ?>"></div>
        <div class="form-group"><label>UF</label><input type="text" name="imovel_uf" value="<?= htmlspecialchars($i_uf) ?>" maxlength="2" style="text-transform:uppercase;"></div>
    </div>
    
    <div class="form-grid" style="margin-top:15px; background:#f8f9fa; padding:15px; border-radius:8px;">
        <div class="form-group"><label>Inscrição Imobiliária (IPTU)</label><input type="text" name="inscricao_imob" value="<?= htmlspecialchars($i_iptu) ?>"></div>
        <div class="form-group"><label>Matrícula Cartório</label><input type="text" name="num_matricula" value="<?= htmlspecialchars($i_mat) ?>"></div>
        <div class="form-group"><label>Área do Lote (m²)</label><input type="text" name="imovel_area_lote" value="<?= htmlspecialchars($i_lote) ?>"></div>
        <div class="form-group"><label>Área Construída (m²)</label><input type="text" name="area_construida" value="<?= htmlspecialchars($i_area) ?>"></div>
    </div>

    <!-- 5. DADOS DO PROCESSO -->
    <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">5. Dados do Processo</h3>
    <div class="form-grid">
        <div class="form-group"><label>Número do Processo</label><input type="text" name="processo_numero" value="<?= htmlspecialchars($p_num) ?>" placeholder="Ex: 2024/0058"></div>
        <div class="form-group"><label>Data de Início</label><input type="date" name="data_inicio" value="<?= htmlspecialchars($p_data) ?>"></div>
        
        <div class="form-group" style="grid-column: span 2;">
            <label>Tipo de Processo (Lista de Documentos)</label>
            <?php 
                // Load Docs Config safely
                $docs_config_path = __DIR__ . '/../config/docs_config.php';
                if(file_exists($docs_config_path)) {
                    $docs_data_conf = require $docs_config_path;
                    $processos_opts = $docs_data_conf['processes'] ?? [];
                } else {
                    $processos_opts = [];
                }
            ?>
            <select name="tipo_processo_chave" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; background-color:#fff;">
                <option value="">-- Selecione o Tipo (Define o Checklist) --</option>
                <?php foreach($processos_opts as $chave => $proc): ?>
                    <option value="<?= $chave ?>" <?= ($p_tipo_chave == $chave) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proc['titulo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color:#666; font-size:0.8rem;">Selecionar o tipo correto ativa a lista de documentos personalizada.</small>
        </div>
    </div>

    <!-- SECTION 6: OUTRAS INFORMAÇÕES (DINÂMICOS) -->
    <div class="section-header" style="margin-top:20px;">
        <div class="section-icon">📝</div>
        <h3 style="margin:0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">6. Outras Informações</h3>
    </div>
    <div class="section-body">
        <p style="font-size:0.9rem; color:#666; margin-bottom:20px;">Use esta seção para adicionar dados personalizados (Ex: CNH, Nome do Cônjuge, etc).</p>
        
        <div id="container-campos-extras" style="display:flex; flex-direction:column; gap:15px;">
            <?php if(!empty($campos_extras)) foreach($campos_extras as $ex): ?>
                <div class="extra-field-row" style="background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee; display:flex; gap:15px; align-items:flex-end;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Título do Campo</label>
                        <input type="text" name="extra_titulos[]" value="<?= htmlspecialchars($ex['titulo']) ?>" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                    </div>
                    <div style="flex:2;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Informação / Valor</label>
                        <input type="text" name="extra_valores[]" value="<?= htmlspecialchars($ex['valor']) ?>" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:6px; cursor:pointer;">
                        <span class="material-symbols-rounded">delete</span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addExtraField()" style="margin-top:20px; display:flex; align-items:center; gap:8px; background:#f0f8f5; color:#146c43; border:1px dashed #146c43; padding:12px 20px; border-radius:8px; cursor:pointer; width:100%; justify-content:center;">
            <span class="material-symbols-rounded">add_circle</span> Adicionar Novo Campo
        </button>
    </div>


    <!-- Sticky Footer -->
    <div class="<?= $is_edit ? 'sticky-footer' : '' ?>" style="<?= $is_edit ? 'display:flex; justify-content:flex-end; gap:15px; padding:15px; background:white; border-top:1px solid #ddd; position:sticky; bottom:0; box-shadow:0 -2px 10px rgba(0,0,0,0.05); z-index:100;' : 'margin-top:20px;' ?>">
        <?php if($is_edit): ?>
            <a href="admin.php?cliente_id=<?= $cliente['id'] ?>&tab=andamento" class="btn-close" style="background:#f8d7da; color:#721c24; text-decoration:none; padding:10px 20px; border-radius:8px; border:1px solid #f5c6cb; font-weight:bold;">Cancelar</a>
        <?php endif; ?>
        
        <button type="submit" name="<?= $btn_name ?>" class="btn-save" style="<?= $is_edit ? 'background:#0d6efd; color:white; border:none; padding:10px 30px; border-radius:8px; font-weight:bold; cursor:pointer;' : 'width:100%; justify-content:center;' ?>">
            <?= $btn_text ?>
        </button>
    </div>

    <!-- Scripts de Máscara e CEP (JS) -->
    <script>
        // LOGIN AUTOMÁTICO (UI)
        function atualizarLoginAuto() {
            const radioCpf = document.querySelector('input[name="auto_login_source"][value="cpf"]');
            const radioTel = document.querySelector('input[name="auto_login_source"][value="tel"]');
            
            if(!radioCpf) return; // Não estamos no modo create

            const inputCpf = document.querySelector('input[name="cpf_cnpj"]');
            const inputTel = document.querySelector('input[name="contato_tel"]');
            const inputUser = document.querySelector('input[name="usuario"]');
            
            if(radioCpf.checked && inputCpf) {
                inputUser.value = inputCpf.value.replace(/\D/g, '');
            } else if(radioTel && radioTel.checked && inputTel) {
                inputUser.value = inputTel.value.replace(/\D/g, '');
            }
        }

        // BUSCA DE CEP RESIDENCIAL (API ViaCEP)
        async function buscarCepResidencial() {
            const cepInput = document.getElementById('res_cep');
            const cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('Por favor, digite um CEP válido de 8 dígitos.');
                return;
            }
            
            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();
                if (data.erro) {
                    alert('CEP Residencial não encontrado.');
                    return;
                }
                
                document.querySelector('input[name="res_rua"]').value = data.logradouro || '';
                document.querySelector('input[name="res_bairro"]').value = data.bairro || '';
                document.querySelector('input[name="res_cidade"]').value = data.localidade || '';
                document.querySelector('input[name="res_uf"]').value = data.uf || '';
                
                document.querySelector('input[name="res_numero"]').focus();
            } catch (err) {
                alert('Erro ao buscar o CEP. Verifique a conexão.');
            }
        }

        // BUSCA DE CEP IMÓVEL / OBRA (API ViaCEP)
        async function buscarCepImovel() {
            const cepInput = document.getElementById('imovel_cep');
            const cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('Por favor, digite um CEP válido de 8 dígitos.');
                return;
            }
            
            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();
                if (data.erro) {
                    alert('CEP do Imóvel não encontrado.');
                    return;
                }
                
                document.querySelector('input[name="imovel_rua"]').value = data.logradouro || '';
                document.querySelector('input[name="imovel_bairro"]').value = data.bairro || '';
                document.querySelector('input[name="imovel_cidade"]').value = data.localidade || '';
                document.querySelector('input[name="imovel_uf"]').value = data.uf || '';
                
                document.querySelector('input[name="imovel_numero"]').focus();
            } catch (err) {
                alert('Erro ao buscar o CEP. Verifique a conexão.');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const maskPhone = (v) => v.replace(/\D/g, "").replace(/^(\d{2})(\d)/g, "($1) $2").replace(/(\d)(\d{4})$/, "$1-$2").substring(0, 15);
            const maskCpfCnpj = (v) => {
                v = v.replace(/\D/g, "");
                if (v.length <= 11) return v.replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                return v.replace(/^(\d{2})(\d)/, "$1.$2").replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3").replace(/\.(\d{3})(\d)/, ".$1/$2").replace(/(\d{4})(\d)/, "$1-$2");
            };
            const maskCep = (v) => v.replace(/\D/g, "").replace(/^(\d{5})(\d)/, "$1-$2").substring(0, 9);

            const inputs = {
                'contato_tel': maskPhone,
                'cpf_cnpj': maskCpfCnpj,
                'cpf_conjuge': maskCpfCnpj,
                'res_cep_temp': maskCep,
                'imovel_cep_temp': maskCep
            };

            for (const [name, fn] of Object.entries(inputs)) {
                document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
                    input.addEventListener('input', (e) => {
                        e.target.value = fn(e.target.value);
                        atualizarLoginAuto();
                        
                        // Executa busca automática ao completar os 8 dígitos limpos do CEP
                        if (name === 'res_cep_temp' && e.target.value.replace(/\D/g, '').length === 8) {
                            buscarCepResidencial();
                        }
                        if (name === 'imovel_cep_temp' && e.target.value.replace(/\D/g, '').length === 8) {
                            buscarCepImovel();
                        }
                    });
                });
            }
        });
        
        function addExtraField() {
            const div = document.createElement('div');
            div.className = 'extra-field-row';
            div.style.cssText = 'background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee; display:flex; gap:15px; align-items:flex-end; animation:fadeIn 0.3s; margin-top:10px;';
            div.innerHTML = `
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Título do Campo</label>
                    <input type="text" name="extra_titulos[]" placeholder="Ex: CNH" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                </div>
                <div style="flex:2;">
                    <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Informação / Valor</label>
                    <input type="text" name="extra_valores[]" placeholder="Digite a informação..." style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:6px; cursor:pointer;">
                    <span class="material-symbols-rounded">delete</span>
                </button>
            `;
            document.getElementById('container-campos-extras').appendChild(div);
        }

        // GERADOR E COMPARTILHADOR DE CREDENCIAIS DE ACESSO
        function obterTextoCredenciais() {
            const nomeInput = document.querySelector('input[name="nome"]').value;
            const nome = nomeInput.trim().split(' ')[0];
            const usuario = document.querySelector('input[name="usuario"]').value;
            const senhaNova = document.querySelector('input[name="nova_senha"]') ? document.querySelector('input[name="nova_senha"]').value : '';
            
            let msgSenha = "a mesma cadastrada anteriormente";
            if (senhaNova.trim() !== '') {
                msgSenha = senhaNova.trim();
            }
            
            return `Olá ${nome}! Seu acesso exclusivo ao Portal do Cliente da Vilela Engenharia está disponível. Por lá você pode acompanhar o andamento dos seus projetos e baixar todos os documentos da sua obra em tempo real.\n\n` +
                   `🔗 Acesse: https://vilela.eng.br/area-cliente/\n` +
                   `👤 Usuário: *${usuario}*\n` +
                   `🔑 Senha: *${msgSenha}*\n\n` +
                   `Qualquer dúvida com o acesso, estou à disposição!`;
        }

        function copiarCredenciais() {
            const texto = obterTextoCredenciais();
            navigator.clipboard.writeText(texto).then(() => {
                Toastify({
                    text: "Mensagem de acesso copiada para a área de transferência!",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: {
                        background: "var(--color-primary)",
                        borderRadius: "10px",
                        fontWeight: "600",
                        boxShadow: "var(--shadow)"
                    }
                }).showToast();
            }).catch(err => {
                alert("Erro ao copiar credenciais: " + err);
            });
        }

        function enviarCredenciaisWhatsApp() {
            const texto = obterTextoCredenciais();
            const telInput = document.querySelector('input[name="contato_tel"]').value;
            const telLimpo = telInput.replace(/\D/g, '');
            
            let dddTel = telLimpo;
            if (telLimpo !== '' && telLimpo.length <= 11) {
                dddTel = '55' + telLimpo;
            }
            
            const url = `https://wa.me/${dddTel}?text=${encodeURIComponent(texto)}`;
            window.open(url, '_blank');
        }
    </script>
</form>

