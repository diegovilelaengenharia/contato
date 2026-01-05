<?php
// editar_cliente.php

// Iniciando sessão com o nome correto
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
// Debug para erro 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header('Location: index.php');
    exit;
}

// Output Buffering para evitar erro de Header sent
ob_start();

require 'db.php';
// Ensure schema is up to date (Self-Healing)
require_once 'includes/schema.php';

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) {
    die("ID do cliente não fornecido.");
}

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    die("Cliente não encontrado.");
}

// Buscar detalhes
// Buscar detalhes
$stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmtDet->execute([$cliente_id]);
$detalhes = $stmtDet->fetch();

// Buscar campos extras
try {
    $stmtEx = $pdo->prepare("SELECT * FROM processo_campos_extras WHERE cliente_id = ?");
    $stmtEx->execute([$cliente_id]);
    $campos_extras = $stmtEx->fetchAll();
} catch (Exception $e) {
    // Tabela não existe? Criar agora.
    $pdo->exec("CREATE TABLE IF NOT EXISTS processo_campos_extras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        valor TEXT,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    )");
    $campos_extras = [];
}

// Arrays para dropdowns
$tipos_pessoa = ['Fisica', 'Juridica'];
$estados_civil = ['Solteiro', 'Casado', 'Divorciado', 'Viuvo', 'Uniao Estavel'];

// Msg Feedback
$msg_alert = "";
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'success_update') $msg_alert = "<script>alert('✅ Dados atualizados com sucesso!');</script>";
    if($_GET['msg'] == 'welcome') $msg_alert = "<script>alert('✅ Cliente criado com sucesso! Continue editando abaixo.');</script>";
    if($_GET['msg'] == 'error') $msg_alert = "<script>alert('❌ Erro: " . htmlspecialchars($_GET['details'] ?? 'Desconhecido') . "');</script>";
}
if(isset($_GET['new']) && $_GET['new']==1) $msg_alert = "<script>alert('✅ Cadastro aprovado! Complete os dados agora.');</script>";

echo $msg_alert;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cliente | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Sincronizando com admin_style.css */
        :root {
            --primary: var(--color-primary, #146c43);
            --primary-hover: #0f5132;
            --bg-page: var(--color-bg, #f8f9fa);
            --bg-card: var(--color-surface, #ffffff);
            --text-main: var(--color-text, #2c3e50);
            --text-sub: var(--color-text-subtle, #7f8c8d);
            --border-color: var(--color-border, #e2e8f0);
        }
        
        body { 
            background: var(--bg-page); 
            font-family: 'Outfit', sans-serif; 
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .main-wrapper {
            max-width: 1600px; 
            margin: 0 auto; 
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* Header */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .page-title h1 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            display: flex; 
            align-items: center; 
            gap: 10px;
        }

        .page-title span {
            font-size: 0.9rem;
            color: var(--text-sub);
            font-weight: 400;
            background: #eee;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .btn-close {
            background: #eef2f5;
            color: #555;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-close:hover { background: #dfe4ea; color: #333; }

        /* Form Structure */
        .form-container {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .section-header {
            background: #fdfdfd;
            padding: 12px 25px;
            border-bottom: 1px solid var(--border-color);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header:first-child { border-top: none; }

        .section-header h2 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section-icon {
            width: 32px; height: 32px;
            background: #e6f4ea;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .section-body {
            padding: 20px 25px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-main);
            background: #fbfbfb;
            transition: all 0.2s;
            font-family: 'Outfit', sans-serif;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(20, 108, 67, 0.1);
        }

        .form-group input[readonly] {
            background: #eee;
            color: #888;
            cursor: not-allowed;
        }

        /* Sticky Footer */
        .sticky-footer {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px 30px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.03);
            gap: 15px;
        }
        
        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(20, 108, 67, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-save:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Responsive */
        @media(max-width: 768px) {
            body { padding: 10px; }
            .section-body { padding: 20px; }
            .sticky-footer { flex-direction: column; }
            .btn-save { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <form action="includes/processamento.php" method="POST" enctype="multipart/form-data" class="main-wrapper">
        <input type="hidden" name="acao" value="editar_cliente_completo">
        <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
        
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>
                    Configurações do Cliente
                    <span>ID #<?= str_pad($cliente['id'], 3, '0', STR_PAD_LEFT) ?></span>
                </h1>
            </div>
            <!-- Botão Voltar Robusto -->
            <a href="gestao_admin_99.php?cliente_id=<?= $cliente_id ?>&tab=andamento" class="btn-close">
                ⬅️ Voltar ao Painel
            </a>
        </div>

        <div class="form-container">
            
            <!-- 1. ACESSO -->
            <h3 style="margin:0 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">1. Acesso & Fotos</h3>
            <div style="display:flex; gap:20px; margin-bottom:20px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">📸 Foto de Perfil</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="file" name="avatar_upload" accept="image/*" style="padding:10px; border:1px solid #ddd; border-radius:8px; width:100%;">
                        <?php 
                            $avatar = glob("uploads/avatars/avatar_{$cliente['id']}.*");
                            if(!empty($avatar)) echo "<img src='{$avatar[0]}?".time()."' style='width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid #ddd;'>";
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group"><label>Nome Completo (Titular)</label><input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required placeholder="Ex: João da Silva"></div>
                <div class="form-group">
                    <label>Login de Acesso (Usuário)</label>
                    <input type="text" name="usuario" value="<?= htmlspecialchars($cliente['usuario']) ?>" required style="font-family:monospace; color:#2980b9;">
                </div>
                <div class="form-group"><label>Nova Senha (Opcional)</label><input type="text" name="nova_senha" placeholder="Preencha apenas se for trocar"></div>
            </div>

            <!-- 2. DADOS PESSOAIS -->
            <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">2. Dados Pessoais</h3>
            <div class="form-grid">
                <div class="form-group"><label>CPF / CNPJ <span style="color:red">*</span></label><input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($detalhes['cpf_cnpj']??'') ?>" required></div>
                <div class="form-group"><label>RG / Inscrição Estadual</label><input type="text" name="rg_ie" value="<?= htmlspecialchars($detalhes['rg_ie']??'') ?>"></div>
                <div class="form-group"><label>Nacionalidade</label><input type="text" name="nacionalidade" value="<?= htmlspecialchars($detalhes['nacionalidade']??'') ?>"></div>
                <div class="form-group"><label>Data Nascimento</label><input type="date" name="data_nascimento" value="<?= htmlspecialchars($detalhes['data_nascimento']??'') ?>"></div>
                <div class="form-group"><label>Profissão</label><input type="text" name="profissao" value="<?= htmlspecialchars($detalhes['profissao']??'') ?>"></div>
                <div class="form-group">
                    <label>Estado Civil</label>
                    <select name="estado_civil" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                        <?php 
                        $opts = ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União Estável'];
                        foreach($opts as $o) {
                            $sel = ($detalhes['estado_civil']??'') == $o ? 'selected' : '';
                            echo "<option value='$o' $sel>$o</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group"><label>Nome Cônjuge</label><input type="text" name="nome_conjuge" value="<?= htmlspecialchars($detalhes['nome_conjuge']??'') ?>"></div>
                <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="contato_tel" value="<?= htmlspecialchars($detalhes['contato_tel']??'') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="contato_email" value="<?= htmlspecialchars($detalhes['contato_email']??'') ?>"></div>
            </div>

            <!-- 3. ENDEREÇO RESIDENCIAL -->
            <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">3. Endereço Residencial</h3>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;"><label>Rua / Logradouro</label><input type="text" name="res_rua" value="<?= htmlspecialchars($detalhes['res_rua']??'') ?>"></div>
                <div class="form-group"><label>Número</label><input type="text" name="res_numero" value="<?= htmlspecialchars($detalhes['res_numero']??'') ?>"></div>
                <div class="form-group"><label>Bairro</label><input type="text" name="res_bairro" value="<?= htmlspecialchars($detalhes['res_bairro']??'') ?>"></div>
                <div class="form-group"><label>Complemento</label><input type="text" name="res_complemento" value="<?= htmlspecialchars($detalhes['res_complemento']??'') ?>"></div>
                <div class="form-group"><label>Cidade</label><input type="text" name="res_cidade" value="<?= htmlspecialchars($detalhes['res_cidade']??'') ?>"></div>
                <div class="form-group"><label>UF</label><input type="text" name="res_uf" value="<?= htmlspecialchars($detalhes['res_uf']??'') ?>" maxlength="2" style="text-transform:uppercase;"></div>
            </div>

            <!-- 4. DADOS DO IMÓVEL -->
            <h3 style="margin:20px 0 15px 0; color:var(--color-primary); border-bottom:1px solid #eee; padding-bottom:5px;">4. Dados do Imóvel / Obra</h3>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 3;">
                    <label>Tipo de Serviço</label>
                    <select name="tipo_servico" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                        <?php 
                        $servicos = ['Regularização de Imóvel', 'Projeto Arquitetônico', 'Projeto Estrutural', 'Desmembramento', 'Laudo Técnico', 'Outros'];
                        foreach($servicos as $s) {
                            $sel = ($detalhes['tipo_servico']??'') == $s ? 'selected' : '';
                            echo "<option value='$s' $sel>$s</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;"><label>Rua / Logradouro (Obra)</label><input type="text" name="imovel_rua" value="<?= htmlspecialchars($detalhes['imovel_rua']??'') ?>"></div>
                <div class="form-group"><label>Número</label><input type="text" name="imovel_numero" value="<?= htmlspecialchars($detalhes['imovel_numero']??'') ?>"></div>
                <div class="form-group"><label>Bairro</label><input type="text" name="imovel_bairro" value="<?= htmlspecialchars($detalhes['imovel_bairro']??'') ?>"></div>
                <div class="form-group"><label>Complemento</label><input type="text" name="imovel_complemento" value="<?= htmlspecialchars($detalhes['imovel_complemento']??'') ?>"></div>
                <div class="form-group"><label>Cidade</label><input type="text" name="imovel_cidade" value="<?= htmlspecialchars($detalhes['imovel_cidade']??'') ?>"></div>
                <div class="form-group"><label>UF</label><input type="text" name="imovel_uf" value="<?= htmlspecialchars($detalhes['imovel_uf']??'') ?>" maxlength="2" style="text-transform:uppercase;"></div>
            </div>
            
            <div class="form-grid" style="margin-top:15px; background:#f8f9fa; padding:15px; border-radius:8px;">
                <div class="form-group"><label>Inscrição Imobiliária (IPTU)</label><input type="text" name="inscricao_imob" value="<?= htmlspecialchars($detalhes['inscricao_imob']??'') ?>"></div>
                <div class="form-group"><label>Matrícula Cartório</label><input type="text" name="num_matricula" value="<?= htmlspecialchars($detalhes['num_matricula']??'') ?>"></div>
                <div class="form-group"><label>Área do Lote (m²)</label><input type="text" name="imovel_area_lote" value="<?= htmlspecialchars($detalhes['imovel_area_lote']??($detalhes['area_terreno']??'')) ?>"></div>
                <div class="form-group"><label>Área Construída (m²)</label><input type="text" name="area_construida" value="<?= htmlspecialchars($detalhes['area_construida']??'') ?>"></div>
            </div>

            <!-- Seção Técnica Removida conforme solicitação -->

            <!-- SECTION 5: CUSTOM FIELDS (DINÂMICOS) -->
            <div class="section-header">
                <div class="section-icon">📝</div>
                <h2>Outras Informações</h2>
            </div>
            <div class="section-body">
                <p style="font-size:0.9rem; color:#666; margin-bottom:20px;">Use esta seção para adicionar dados personalizados (Ex: CNH, Nome do Cônjuge, etc).</p>
                
                <div id="container-campos-extras" style="display:flex; flex-direction:column; gap:15px;">
                    <?php foreach($campos_extras as $ex): ?>
                        <div class="extra-field-row" style="background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee; display:flex; gap:15px; align-items:flex-end;">
                            <div style="flex:1;">
                                <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Título do Campo</label>
                                <input type="text" name="extra_titulos[]" value="<?= htmlspecialchars($ex['titulo']) ?>" placeholder="Ex: CNH" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                            <div style="flex:2;">
                                <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Informação / Valor</label>
                                <input type="text" name="extra_valores[]" value="<?= htmlspecialchars($ex['valor']) ?>" placeholder="Digite a informação..." style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:6px; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#e74c3c'; this.style.color='white';" onmouseout="this.style.background='#fff'; this.style.color='#e74c3c';">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" onclick="addExtraField()" style="margin-top:20px; display:flex; align-items:center; gap:8px; background:#f0f8f5; color:#146c43; border:1px dashed #146c43; padding:12px 20px; border-radius:8px; cursor:pointer; font-weight:600; width:100%; justify-content:center; transition:0.2s;" onmouseover="this.style.background='#e6f4ea'">
                    <span class="material-symbols-rounded">add_circle</span> Adicionar Novo Campo
                </button>

                <script>
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
                            <button type="button" onclick="this.parentElement.remove()" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:6px; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#e74c3c'; this.style.color='white';" onmouseout="this.style.background='#fff'; this.style.color='#e74c3c';">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        `;
                        document.getElementById('container-campos-extras').appendChild(div);
                    }
                </script>
            </div>
            </div>

            <!-- Sticky Save -->
            <div class="sticky-footer">
                <span style="font-size:0.9rem; color:var(--text-sub); margin-right:auto;">
                    ⚠️ Todas as alterações são salvas imediatamente no banco.
                </span>
                <a href="gestao_admin_99.php?cliente_id=<?= $cliente_id ?>&tab=andamento" class="btn-close" style="background:none; text-decoration:none;">Cancelar</a>
                <button type="submit" name="btn_salvar_tudo" class="btn-save">
                    💾 Salvar Alterações
                </button>
            </div>

        </div>
    </form>

    <script>
        // Helper para copiar CPF ou Telefone para o Login
        function copiarParaLogin(origem) {
            let valor = '';
            if(origem === 'cpf') {
                const cpfInput = document.querySelector('input[name="cpf_cnpj"]');
                if(cpfInput) valor = cpfInput.value.replace(/\D/g, ''); // Apenas numeros
            } else if(origem === 'tel') {
                const telInput = document.querySelector('input[name="contato_tel"]');
                if(telInput) valor = telInput.value.replace(/\D/g, '');
            }
            
            if(valor) {
                document.getElementById('campo_login').value = valor;
                // alert('Login atualizado para: ' + valor);
            } else {
                alert('Campo de origem (' + origem + ') está vazio!');
            }
        }

        // --- MÁSCARAS E VALIDAÇÃO ---
        // --- MÁSCARAS E VALIDAÇÃO ---
        document.addEventListener('DOMContentLoaded', function() {
            
            // Helpers
            const maskPhone = (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                v = v.replace(/(\d)(\d{4})$/, "$1-$2");
                return v;
            }

            const maskCpfCnpj = (v) => {
                v = v.replace(/\D/g, "");
                if (v.length <= 11) {
                    v = v.replace(/(\d{3})(\d)/, "$1.$2");
                    v = v.replace(/(\d{3})(\d)/, "$1.$2");
                    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                } else {
                    v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                    v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                    v = v.replace(/(\d{4})(\d)/, "$1-$2");
                }
                return v;
            }

            const maskCep = (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{5})(\d)/, "$1-$2");
                return v;
            }

            const maskArea = (v) => {
                // Allows 1234.56 or 1234,56
                return v.replace(/[^0-9.,]/g, ''); 
            }

            // Apply Masks
            const inputs = {
                'contato_tel': { mask: maskPhone, limit: 15 },
                'telefone': { mask: maskPhone, limit: 15 },
                'cpf_cnpj': { mask: maskCpfCnpj, limit: 18 },
                // 'cep': { mask: maskCep, limit: 9 }, // Se houver campo CEP
                'imovel_area_lote': { mask: maskArea, limit: 10 },
                'area_construida': { mask: maskArea, limit: 10 }
            };

            for (const [name, config] of Object.entries(inputs)) {
                document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
                    input.addEventListener('input', (e) => {
                        e.target.value = config.mask(e.target.value);
                    });
                });
            }
        });
    </script>
</body>
</html>
