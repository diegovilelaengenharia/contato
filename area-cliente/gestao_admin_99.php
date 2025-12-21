<?php
session_start();
require 'db.php';

// --- Configuração e Segurança ---
$minha_senha_mestra = "VilelaAdmin2025"; // Mantida para referência ou dupla checagem futura

// Verifica Sessão
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- Processamento de Formulários ---

// 2.2 Atualizar Etapa Atual (Stepper)
if (isset($_POST['atualizar_etapa'])) {
    $nova_etapa = $_POST['nova_etapa'];
    $cid = $_POST['cliente_id'];
    
    try {
        // Atualiza Fase no Detalhes
        $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
        
        // Registra no Histórico
        $titulo = "Atualização de Fase: " . $nova_etapa;
        $desc = "Processo avançou para a fase de " . $nova_etapa;
        
        // FIX: Inserção compatível com colunas que existem
        // Verifica se a tabela já foi atualizada com colunas extras ou não. 
        // Por segurança, usamos apenas as colunas básicas garantidas pelo setup_update_db_v2
        $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo) VALUES (?, ?, NOW(), ?, 'conclusao')";
        $pdo->prepare($sql)->execute([$cid, $titulo, $desc]);

        $sucesso = "Fase atualizada para: $nova_etapa";
    } catch(PDOException $e) {
        $erro = "Erro ao atualizar etapa: " . $e->getMessage();
    }
}

// 0. Salvar Detalhes (Formulário Unificado)
if (isset($_POST['salvar_detalhes'])) {
    $cid = $_POST['cliente_id'];
    
    $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $check->execute([$cid]);
    $exists = $check->fetch();

    // Campos atualizados conforme pedido (sem zoneamento, com tipo_responsavel)
    $campos = [
        'tipo_pessoa', 'cpf_cnpj', 'rg_ie', 'estado_civil', 'profissao', 'endereco_residencial', 'contato_email', 'contato_tel',
        'inscricao_imob', 'num_matricula', 'endereco_imovel', 'area_terreno', 'area_construida', 
        'tipo_responsavel', 'resp_tecnico', 'registro_prof', 'num_art_rrt', 
        'link_drive_pasta', 'link_doc_iniciais', 'link_doc_pendencias', 'link_doc_finais'
    ];

    if ($exists) {
        $sql = "UPDATE processo_detalhes SET 
            tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, estado_civil=?, profissao=?, endereco_residencial=?, contato_email=?, contato_tel=?,
            inscricao_imob=?, num_matricula=?, endereco_imovel=?, area_terreno=?, area_construida=?, 
            tipo_responsavel=?, resp_tecnico=?, registro_prof=?, num_art_rrt=?,
            link_drive_pasta=?, link_doc_iniciais=?, link_doc_pendencias=?, link_doc_finais=?
            WHERE cliente_id=?";
    } else {
        $sql = "INSERT INTO processo_detalhes (
            tipo_pessoa, cpf_cnpj, rg_ie, estado_civil, profissao, endereco_residencial, contato_email, contato_tel,
            inscricao_imob, num_matricula, endereco_imovel, area_terreno, area_construida, 
            tipo_responsavel, resp_tecnico, registro_prof, num_art_rrt,
            link_drive_pasta, link_doc_iniciais, link_doc_pendencias, link_doc_finais,
            cliente_id
        ) VALUES (?,?,?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?)";
    }
    
    $params = [];
    foreach($campos as $c) {
        $params[] = $_POST[$c] ?? null;
    }
    $params[] = $cid;

    try {
        $pdo->prepare($sql)->execute($params);
        $sucesso = "Dados salvos com sucesso!";
    } catch (PDOException $e) {
        $erro = "Erro ao salvar: " . $e->getMessage();
    }
}

// 1. Cadastrar Cliente
if (isset($_POST['novo_cliente'])) {
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $user, $pass]);
        $novo_id = $pdo->lastInsertId();
        
        $pdo->prepare("INSERT INTO processo_detalhes (cliente_id) VALUES (?)")->execute([$novo_id]);
        
        $sucesso = "Cliente $nome cadastrado!";
    } catch (PDOException $e) {
        $erro = "Erro: Usuário já existe ou dados inválidos.";
    }
}

// 3. Adicionar Documento
if (isset($_POST['novo_doc'])) {
    $stmt = $pdo->prepare("INSERT INTO documentos (cliente_id, titulo, link_drive) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['cliente_id'], $_POST['titulo'], $_POST['link']]);
    $sucesso = "Documento anexado!";
}

if (isset($_GET['delete_cliente'])) {
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$_GET['delete_cliente']]);
    header("Location: ?"); exit;
}

// --- Consultas ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
$cliente_ativo = null;
$detalhes = [];
$docs_ativo = [];

if (isset($_GET['cliente_id'])) {
    $id_selecionado = $_GET['cliente_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id_selecionado]);
    $cliente_ativo = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
    $stmt->execute([$id_selecionado]);
    $detalhes = $stmt->fetch();
    if(!$detalhes) $detalhes = []; 

    $stmt = $pdo->prepare("SELECT * FROM documentos WHERE cliente_id = ? ORDER BY id DESC");
    $stmt->execute([$id_selecionado]);
    $docs_ativo = $stmt->fetchAll();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'cadastro';

$fases_padrao = [
    "Abertura de Processo (Guichê)", "Fiscalização (Parecer Fiscal)", "Triagem (Documentos Necessários)",
    "Comunicado de Pendências (Triagem)", "Análise Técnica (Engenharia)", "Comunicado (Pendências e Taxas)",
    "Confecção de Documentos", "Avaliação (ITBI/Averbação)", "Processo Finalizado (Documentos Prontos)"
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão | Vilela Engenharia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <style>
        /* CSS Variables & Theme */
        :root {
            --color-bg: #f4f7f6;
            --color-surface: #ffffff;
            --color-text: #333333;
            --color-text-subtle: #555555;
            --color-border: #e0e0e0;
            --color-primary: #198754;
            --color-primary-strong: #146c43;
            --shadow: 0 4px 20px rgba(0,0,0,0.05);
            --header-bg: #146c43;
        }

        body.dark-mode {
            --color-bg: #121212;
            --color-surface: #1e1e1e;
            --color-text: #e0e0e0;
            --color-text-subtle: #a0a0a0;
            --color-border: #333333;
            --shadow: 0 4px 20px rgba(0,0,0,0.3);
            --header-bg: #0b3d26;
        }

        body { background-color: var(--color-bg); color: var(--color-text); font-family: 'Outfit', sans-serif; display: block; padding: 0; transition: background-color 0.3s, color 0.3s; }
        
        .admin-header { background: var(--header-bg); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .admin-container { display: grid; grid-template-columns: 260px 1fr; gap: 20px; max-width: 1600px; margin: 20px auto; padding: 0 20px; align-items: start; }
        
        .sidebar { background: var(--color-surface); border-radius: 8px; box-shadow: var(--shadow); padding: 15px; position: sticky; top: 80px; border: 1px solid var(--color-border); }
        
        .client-list { list-style: none; padding: 0; margin: 0; max-height: 70vh; overflow-y: auto; }
        .client-list li a { display: block; padding: 10px; border-radius: 6px; text-decoration: none; color: var(--color-text); border-bottom: 1px solid var(--color-border); font-size: 0.9rem; transition: 0.2s; }
        .client-list li a:hover { background: rgba(0,0,0,0.05); }
        .client-list li a.active { background: var(--color-primary); color: white; border-color: transparent; }

        .tabs-header { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: none; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; }
        .tab-btn { padding: 12px 24px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 50px; cursor: pointer; font-weight: 600; color: var(--color-text-subtle); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tab-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); filter: brightness(0.95); }
        .tab-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }

        .tab-content { background: var(--color-surface); padding: 30px; border-radius: 12px; box-shadow: var(--shadow); min-height: 500px; border: 1px solid var(--color-border);animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-card { background: rgba(0,0,0,0.02); border: 1px solid var(--color-border); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .form-card h3 { margin-top: 0; color: var(--color-primary-strong); font-size: 1.1rem; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid var(--color-border); padding-bottom: 10px; margin-bottom: 15px; }
        body.dark-mode .form-card h3 { color: #4ade80; } /* Ajuste cor titulo no dark */
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: bold; color: var(--color-text-subtle); margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text); border-radius: 6px; box-sizing: border-box; transition: 0.2s; }
        .form-group input:focus { border-color: var(--color-primary); outline: none; box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1); }

        .btn-save { background: var(--color-primary); color: white; padding: 14px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: bold; width: 100%; transition: 0.2s; }
        .btn-save:hover { background: var(--color-primary-strong); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        .stepper-list { list-style: none; padding: 0; margin-bottom: 30px; border: 1px solid var(--color-border); border-radius: 8px; overflow: hidden; }
        .stepper-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; border-bottom: 1px solid var(--color-border); background: var(--color-surface); }
        .stepper-item.current { background: rgba(25, 135, 84, 0.1); border-left: 4px solid var(--color-primary); }
        .stepper-btn { background: #ddd; color: #555; border: none; padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; }
        .stepper-btn.active { background: var(--color-primary); color: white; }

        .btn-toggle-theme { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 6px 12px; border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: inherit; font-size: 0.85rem; margin-right: 15px; transition:0.2s; }
        .btn-toggle-theme:hover { background: rgba(255,255,255,0.3); }

        @media (max-width: 768px) {
            .admin-container { grid-template-columns: 1fr; display: block; }
            .sidebar { position: static; margin-bottom: 20px; }
            .tabs-header { padding-bottom: 15px; }
            .tab-btn { padding: 10px 15px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<header class="admin-header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <img src="../assets/logo.png" alt="Logo" style="height: 40px;">
        <div>
            <h1 style="margin:0; font-size:1.2rem;">Sistema de Regularização</h1>
            <span style="font-size:0.8rem; opacity: 0.8;">Gestão Administrativa</span>
        </div>
    </div>
    
    <div style="display:flex; align-items:center;">
        <button class="btn-toggle-theme" onclick="toggleTheme()">🌓 Tema</button>
        <a href="?sair=true" style="color: white; text-decoration: underline; font-size:0.9rem;">Sair</a>
    </div>
</header>

<div class="admin-container">
    <aside class="sidebar">
        <a href="?novo=true" style="display:block; text-align:center; background:#efb524; padding:12px; border-radius:6px; color:black; font-weight:bold; text-decoration:none; margin-bottom:20px; box-shadow:0 3px 6px rgba(0,0,0,0.1);">+ Novo Cliente</a>
        <h4 style="margin: 10px 0; font-size: 0.8rem; color: var(--color-text-subtle); text-transform: uppercase; letter-spacing: 1px;">Meus Clientes</h4>
        <ul class="client-list">
            <?php foreach($clientes as $c): ?>
                <li>
                    <a href="?cliente_id=<?= $c['id'] ?>" class="<?= ($cliente_ativo && $cliente_ativo['id'] == $c['id']) ? 'active' : '' ?>">
                        <?= htmlspecialchars($c['nome']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main>
        <?php if(isset($sucesso)): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:8px; border-left: 5px solid #28a745;"><?= $sucesso ?></div>
        <?php endif; ?>
        <?php if(isset($erro)): ?>
            <div style="background:#f8d7da; color:#721c24; padding:15px; margin-bottom:20px; border-radius:8px; border-left: 5px solid #d32f2f;"><?= $erro ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['novo'])): ?>
            <div class="tab-content">
                <h2>Cadastrar Novo Cliente</h2>
                <form method="POST">
                    <div class="form-group"><label>Nome do Cliente</label><input type="text" name="nome" required></div>
                    <div class="form-group"><label>Usuário de Acesso (Login)</label><input type="text" name="usuario" required></div>
                    <div class="form-group"><label>Senha Inicial</label><input type="text" name="senha" required></div>
                    <button type="submit" name="novo_cliente" class="btn-save" style="margin-top:20px;">Cadastrar</button>
                </form>
            </div>

        <?php elseif($cliente_ativo): ?>
            
            <div style="margin-bottom: 25px;"> 
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h1 style="margin: 0; color: var(--color-text); font-size: 1.8rem;"><?= htmlspecialchars($cliente_ativo['nome']) ?></h1>
                        <span style="color:var(--color-text-subtle); font-size:0.9rem;">Gerenciando dados do processo</span>
                    </div>
                    
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                         <!-- Botão Link Drive -->
                         <?php if(!empty($detalhes['link_drive_pasta'])): ?>
                            <a href="<?= htmlspecialchars($detalhes['link_drive_pasta']) ?>" target="_blank" style="background: #333; color:white; padding: 10px 20px; border-radius: 6px; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:8px;">
                                📂 Acessar Drive
                            </a>
                        <?php endif; ?>

                        <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" onclick="return confirm('ATENÇÃO: Confirmar exclusão?')" style="color:#d32f2f; font-size:0.9rem; background: rgba(253, 236, 234, 0.5); border: 1px solid #d32f2f; padding: 10px 16px; border-radius: 6px; text-decoration:none; font-weight:600; display:flex; align-items:center;">Excluir Cliente</a>
                    </div>
                </div>
            </div>

            <!-- Navegação de Abas -->
            <div class="tabs-header">
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=cadastro" class="tab-btn <?= $active_tab=='cadastro'?'active':'' ?>">📝 Cadastro Unificado</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=status" class="tab-btn <?= $active_tab=='status'?'active':'' ?>">⚠️ Status e Pendências</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=links" class="tab-btn <?= $active_tab=='links'?'active':'' ?>">📂 Links e Anexos</a>
            </div>

            <div class="tab-content">
                
                <?php if($active_tab == 'cadastro'): ?>
                    <!-- ABA 1: CADASTRO UNIFICADO -->
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">

                        <!-- Section A: Requerente -->
                        <div class="form-card">
                            <h3>👤 Dados do Requerente</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Tipo de Pessoa</label>
                                    <select name="tipo_pessoa">
                                        <option value="Fisica" <?= ($detalhes['tipo_pessoa']??'')=='Fisica'?'selected':'' ?>>Pessoa Física</option>
                                        <option value="Juridica" <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'selected':'' ?>>Pessoa Jurídica</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>CPF / CNPJ <span style="font-weight:normal;opacity:0.7;">(Vermelho=PJ, Azul=PF)</span></label>
                                    <input type="text" name="cpf_cnpj" value="<?= $detalhes['cpf_cnpj']??'' ?>" 
                                           style="border-left: 5px solid <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'#d32f2f':'#0288d1' ?>;">
                                </div>
                                <div class="form-group">
                                    <label>Documento de Identificação</label>
                                    <input type="text" name="rg_ie" value="<?= $detalhes['rg_ie']??'' ?>" placeholder="Ex: CNH, RG, Passaporte">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group"><label>Estado Civil</label>
                                    <select name="estado_civil">
                                        <option value="">-- Selecione --</option>
                                        <?php 
                                            // Lista de opções
                                            $ec_opts = ["Solteiro(a)", "Casado(a)", "Divorciado(a)", "Viúvo(a)", "União Estável"];
                                            foreach($ec_opts as $ec) {
                                                $sel = ($detalhes['estado_civil']??'') == $ec ? 'selected' : '';
                                                echo "<option value='$ec' $sel>$ec</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group"><label>Profissão</label><input type="text" name="profissao" value="<?= $detalhes['profissao']??'' ?>"></div>
                            </div>
                            <div class="form-group"><label>Endereço Residencial</label><input type="text" name="endereco_residencial" value="<?= $detalhes['endereco_residencial']??'' ?>"></div>
                            <div class="form-grid">
                                <div class="form-group"><label>Email</label><input type="text" name="contato_email" value="<?= $detalhes['contato_email']??'' ?>"></div>
                                <div class="form-group"><label>Telefone / WhatsApp</label>
                                    <input type="text" name="contato_tel" value="<?= $detalhes['contato_tel']??'' ?>" placeholder="(DDD) 90000-0000">
                                </div>
                            </div>
                        </div>

                        <!-- Section B: Imóvel -->
                        <div class="form-card">
                            <h3>🏠 Dados do Imóvel e Lote</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Inscrição Imobiliária (IPTU)</label><input type="text" name="inscricao_imob" value="<?= $detalhes['inscricao_imob']??'' ?>"></div>
                                <div class="form-group"><label>Matrícula</label><input type="text" name="num_matricula" value="<?= $detalhes['num_matricula']??'' ?>"></div>
                            </div>
                            <div class="form-group"><label>Endereço do Imóvel</label><input type="text" name="endereco_imovel" value="<?= $detalhes['endereco_imovel']??'' ?>"></div>
                            <div class="form-grid">
                                <div class="form-group"><label>Área Terreno (m²)</label><input type="number" step="0.01" name="area_terreno" value="<?= $detalhes['area_terreno']??'' ?>"></div>
                                <div class="form-group"><label>Área Construída (m²)</label><input type="number" step="0.01" name="area_construida" value="<?= $detalhes['area_construida']??'' ?>"></div>
                            </div>
                        </div>

                        <!-- Section C: Engenharia -->
                        <div class="form-card">
                            <h3>📐 Projeto de Engenharia</h3>
                            
                            <div class="form-group" style="margin-bottom:15px;">
                                <label>Tipo de Profissional</label>
                                <select name="tipo_responsavel">
                                    <option value="">-- Selecione --</option>
                                    <option value="Engenheiro" <?= ($detalhes['tipo_responsavel']??'')=='Engenheiro'?'selected':'' ?>>Engenheiro(a)</option>
                                    <option value="Arquiteto" <?= ($detalhes['tipo_responsavel']??'')=='Arquiteto'?'selected':'' ?>>Arquiteto(a)</option>
                                    <option value="Tecnico" <?= ($detalhes['tipo_responsavel']??'')=='Tecnico'?'selected':'' ?>>Técnico(a)</option>
                                </select>
                            </div>

                            <div class="form-grid">
                                <div class="form-group"><label>Nome do Responsável</label><input type="text" name="resp_tecnico" value="<?= $detalhes['resp_tecnico']??'' ?>"></div>
                                <div class="form-group"><label>Registro Profissional (CAU/CREA)</label><input type="text" name="registro_prof" value="<?= $detalhes['registro_prof']??'' ?>"></div>
                                <div class="form-group"><label>Número ART / RRT</label><input type="text" name="num_art_rrt" value="<?= $detalhes['num_art_rrt']??'' ?>"></div>
                            </div>
                        </div>

                        <button type="submit" name="salvar_detalhes" class="btn-save">Salvar Cadastro Unificado</button>
                    </form>

                <?php elseif($active_tab == 'status'): ?>
                    <!-- ABA 2: STATUS E PENDENCIAS -->
                    
                    <div class="form-card" style="border-left: 5px solid #ffc107;">
                        <h3>⚠️ Gestão de Pendências e Status</h3>
                        
                        <!-- Link Pendências -->
                        <form method="POST" style="margin-bottom:30px;">
                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                            <div class="form-group">
                                <label style="font-size:1rem; color:#d97706;">Link da Pasta de Pendências (Drive)</label>
                                <input type="url" name="link_doc_pendencias" value="<?= $detalhes['link_doc_pendencias']??'' ?>" placeholder="Link da pasta para o cliente..." style="border: 2px solid #ffc107; background:#fffbf2; color: #333;">
                            </div>
                            <div style="text-align:right; margin-top:5px;">
                                <button type="submit" name="salvar_detalhes" style="background:#d97706; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Salvar Link</button>
                            </div>
                        </form>
                        
                        <hr style="border:0; border-top:1px solid var(--color-border); margin:20px 0;">

                        <!-- Timeline Stepper -->
                        <h4>Fase Atual (Stepper)</h4>
                        <ul class="stepper-list">
                            <?php 
                            foreach($fases_padrao as $fase): 
                                $is_current = (($detalhes['etapa_atual']??'') === $fase);
                            ?>
                                <li class="stepper-item <?= $is_current ? 'current' : '' ?>">
                                    <span style="font-weight: 500; font-size: 1rem; color: var(--color-text);"><?= $fase ?></span>
                                    <?php if($is_current): ?>
                                        <button class="stepper-btn active">✅ Atual</button>
                                    <?php else: ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                                            <input type="hidden" name="nova_etapa" value="<?= $fase ?>">
                                            <button type="submit" name="atualizar_etapa" class="stepper-btn">Definir</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <h4>Histórico de Movimentos</h4>
                    <table style="width:100%; font-size:0.9rem; border-collapse:collapse;">
                        <thead style="background:rgba(0,0,0,0.05);">
                            <tr>
                                <th style="padding:10px; color:var(--color-text);">Data</th>
                                <th style="padding:10px; color:var(--color-text);">Fase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $movs = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                            $movs->execute([$cliente_ativo['id']]);
                            foreach($movs->fetchAll() as $m): ?>
                            <tr style="border-bottom:1px solid var(--color-border);">
                                <td style="padding:10px; color: var(--color-text);"><?= date('d/m/y H:i', strtotime($m['data_movimento'])) ?></td>
                                <td style="padding:10px; color: var(--color-text);"><strong><?= htmlspecialchars($m['titulo_fase']) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif($active_tab == 'links'): ?>
                    <!-- ABA 3: LINKS E ANEXOS -->
                    
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-card" style="border-left: 5px solid #2196f3;">
                            <h3>📂 Links Específicos do Drive</h3>
                            <div class="form-group" style="margin-bottom:15px;">
                                <label>📁 Link: Documentos Iniciais</label>
                                <input type="url" name="link_doc_iniciais" value="<?= $detalhes['link_doc_iniciais']??'' ?>" placeholder="https://drive...">
                            </div>
                            <div class="form-group" style="margin-bottom:15px;">
                                <label>📁 Link: Documentos Finais (Entregáveis)</label>
                                <input type="url" name="link_doc_finais" value="<?= $detalhes['link_doc_finais']??'' ?>" placeholder="https://drive...">
                            </div>
                            <div class="form-group">
                                <label>📁 Link: Pasta Geral do Processo (Backup)</label>
                                <input type="url" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" placeholder="https://drive...">
                            </div>
                        </div>
                        <button type="submit" name="salvar_detalhes" class="btn-save">Salvar Links</button>
                    </form>
                    
                    <!-- Lista de Uploads Individuais -->
                    <div class="card" style="margin-top:30px; border:1px dashed var(--color-border); padding:20px;">
                        <h4>Anexos Avulsos (Lista)</h4>
                        <form method="POST" style="display:flex; gap:10px; margin-bottom:15px;">
                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                            <input type="text" name="titulo" placeholder="Nome do Arquivo" required style="flex:1; padding:8px;">
                            <input type="url" name="link" placeholder="Link Drive" required style="flex:2; padding:8px;">
                            <button type="submit" name="novo_doc" style="background:#555; color:white; border:none; padding:0 20px; border-radius:4px; cursor:pointer;">+ Adicionar</button>
                        </form>
                        <?php foreach($docs_ativo as $d): ?>
                            <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
                                <span style="color: var(--color-text);">📄 <?= htmlspecialchars($d['titulo']) ?> (<?= htmlspecialchars($d['link_drive']) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>

        <?php endif; ?>
    </main>
</div>
<script>
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('admin_theme', isDark ? 'dark' : 'light');
    }
    const savedTheme = localStorage.getItem('admin_theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>
