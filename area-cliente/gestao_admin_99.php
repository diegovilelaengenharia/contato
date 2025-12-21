<?php
session_start();
require 'db.php';

// --- Configuração e Segurança ---
$minha_senha_mestra = "VilelaAdmin2025"; // Mantida para referência ou dupla checagem futura

// Verifica Sessão
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    // Se não estiver logado, manda para o login unificado
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

// --- Processamento de Formulários ---

// 0. Salvar Detalhes (Abas 1, 2, 3, 4)
if (isset($_POST['salvar_detalhes'])) {
    $cid = $_POST['cliente_id'];
    
    // Verifica se já existe registro na tabela detalhes
    $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $check->execute([$cid]);
    $exists = $check->fetch();

    if ($exists) {
        $sql = "UPDATE processo_detalhes SET 
            tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, estado_civil=?, profissao=?, endereco_residencial=?, contato_email=?, contato_tel=?,
            inscricao_imob=?, num_matricula=?, endereco_imovel=?, area_terreno=?, area_construida=?, zoneamento=?,
            resp_tecnico=?, registro_prof=?, num_art_rrt=?,
            status_taxa_aprovacao=?, status_issqn=?, status_multas=?
            WHERE cliente_id=?";
        $params = [
            $_POST['tipo_pessoa'], $_POST['cpf_cnpj'], $_POST['rg_ie'], $_POST['estado_civil'], $_POST['profissao'], $_POST['endereco_residencial'], $_POST['contato_email'], $_POST['contato_tel'],
            $_POST['inscricao_imob'], $_POST['num_matricula'], $_POST['endereco_imovel'], $_POST['area_terreno'], $_POST['area_construida'], $_POST['zoneamento'],
            $_POST['resp_tecnico'], $_POST['registro_prof'], $_POST['num_art_rrt'],
            isset($_POST['status_taxa_aprovacao']) ? 1 : 0, isset($_POST['status_issqn']) ? 1 : 0, isset($_POST['status_multas']) ? 1 : 0,
            $cid
        ];
    } else {
        $sql = "INSERT INTO processo_detalhes (
            tipo_pessoa, cpf_cnpj, rg_ie, estado_civil, profissao, endereco_residencial, contato_email, contato_tel,
            inscricao_imob, num_matricula, endereco_imovel, area_terreno, area_construida, zoneamento,
            resp_tecnico, registro_prof, num_art_rrt,
            status_taxa_aprovacao, status_issqn, status_multas,
            cliente_id
        ) VALUES (?,?,?,?,?,?,?,?, ?,?,?,?,?,?, ?,?,?, ?,?,?, ?)";
        $params = [
            $_POST['tipo_pessoa'], $_POST['cpf_cnpj'], $_POST['rg_ie'], $_POST['estado_civil'], $_POST['profissao'], $_POST['endereco_residencial'], $_POST['contato_email'], $_POST['contato_tel'],
            $_POST['inscricao_imob'], $_POST['num_matricula'], $_POST['endereco_imovel'], $_POST['area_terreno'], $_POST['area_construida'], $_POST['zoneamento'],
            $_POST['resp_tecnico'], $_POST['registro_prof'], $_POST['num_art_rrt'],
            isset($_POST['status_taxa_aprovacao']) ? 1 : 0, isset($_POST['status_issqn']) ? 1 : 0, isset($_POST['status_multas']) ? 1 : 0,
            $cid
        ];
    }
    
    try {
        $pdo->prepare($sql)->execute($params);
        $sucesso = "Dados do processo atualizados com sucesso!";
    } catch (PDOException $e) {
        $erro = "Erro ao salvar detalhes: " . $e->getMessage();
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
        
        // Cria registro vazio em detalhes
        $pdo->prepare("INSERT INTO processo_detalhes (cliente_id) VALUES (?)")->execute([$novo_id]);
        
        $sucesso = "Cliente $nome cadastrado!";
    } catch (PDOException $e) {
        $erro = "Erro: Usuário já existe ou dados inválidos.";
    }
}

// 2. Adicionar Progresso (Timeline)
if (isset($_POST['novo_movimento'])) {
    $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo, departamento_origem, departamento_destino, usuario_responsavel, anexo_url, anexo_nome) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $_POST['cliente_id'], $_POST['titulo'], $_POST['data'], $_POST['descricao'], $_POST['status_tipo'],
        $_POST['origem'], $_POST['destino'], $_POST['responsavel'], $_POST['anexo_url'], $_POST['anexo_nome']
    ]);
    $sucesso = "Movimento registrado na timeline!";
}

// 2.1 Exclusão Movimento
if (isset($_GET['del_mov'])) {
    $pdo->prepare("DELETE FROM processo_movimentos WHERE id=?")->execute([$_GET['del_mov']]);
    header("Location: ?cliente_id=".$_GET['cid']."&tab=timeline"); exit;
}

// 2.2 Atualizar Etapa Atual (Stepper)
if (isset($_POST['atualizar_etapa'])) {
    $nova_etapa = $_POST['nova_etapa'];
    $cid = $_POST['cliente_id'];
    
    try {
        $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
        
        $titulo = "Atualização de Fase: " . $nova_etapa;
        $desc = "Processo avançou para a fase de " . $nova_etapa;
        
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
    
    // Verifica existência
    $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $check->execute([$cid]);
    $exists = $check->fetch();

    // Campos a atualizar
    // Nota: Adicionamos os novos links aqui também
    $campos = [
        'tipo_pessoa', 'cpf_cnpj', 'rg_ie', 'estado_civil', 'profissao', 'endereco_residencial', 'contato_email', 'contato_tel',
        'inscricao_imob', 'num_matricula', 'endereco_imovel', 'area_terreno', 'area_construida', 'zoneamento',
        'resp_tecnico', 'registro_prof', 'num_art_rrt',
        // 'etapa_atual' não atualizamos aqui pra não sobrescrever o stepper
        'link_drive_pasta', 'link_doc_iniciais', 'link_doc_pendencias', 'link_doc_finais'
    ];

    if ($exists) {
        $sql = "UPDATE processo_detalhes SET 
            tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, estado_civil=?, profissao=?, endereco_residencial=?, contato_email=?, contato_tel=?,
            inscricao_imob=?, num_matricula=?, endereco_imovel=?, area_terreno=?, area_construida=?, zoneamento=?,
            resp_tecnico=?, registro_prof=?, num_art_rrt=?,
            link_drive_pasta=?, link_doc_iniciais=?, link_doc_pendencias=?, link_doc_finais=?
            WHERE cliente_id=?";
    } else {
        $sql = "INSERT INTO processo_detalhes (
            tipo_pessoa, cpf_cnpj, rg_ie, estado_civil, profissao, endereco_residencial, contato_email, contato_tel,
            inscricao_imob, num_matricula, endereco_imovel, area_terreno, area_construida, zoneamento,
            resp_tecnico, registro_prof, num_art_rrt,
            link_drive_pasta, link_doc_iniciais, link_doc_pendencias, link_doc_finais,
            cliente_id
        ) VALUES (?,?,?,?,?,?,?,?, ?,?,?,?,?,?, ?,?,?, ?,?,?,?, ?)";
    }
    
    // Preparar valores
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
$detalhes = null;
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

// Controle de Aba Ativa - Padrão 'cadastro'
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'cadastro';

// Fases Padrão
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
        /* CSS Admin Base */
        body { background-color: #f4f7f6; display: block; padding: 0; }
        .admin-header { background: var(--color-primary-strong); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index: 100; }
        .admin-container { display: grid; grid-template-columns: 260px 1fr; gap: 20px; max-width: 1600px; margin: 20px auto; padding: 0 20px; align-items: start; }
        
        .sidebar { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 15px; position: sticky; top: 80px; }
        .client-list { list-style: none; padding: 0; margin: 0; max-height: 70vh; overflow-y: auto; }
        .client-list li a { display: block; padding: 10px; border-radius: 6px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
        .client-list li a:hover { background: #e6f2ee; }
        .client-list li a.active { background: var(--color-primary); color: white; border-color: transparent; }

        /* Abas Modernas */
        .tabs-header { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: none; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; }
        .tab-btn { padding: 12px 24px; background: white; border: 1px solid #ddd; border-radius: 50px; cursor: pointer; font-weight: 600; color: #555; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tab-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .tab-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }

        .tab-content { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); min-height: 500px; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Cards dentro das Abas */
        .form-card { background: #f8f9fa; border: 1px solid #eee; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .form-card h3 { margin-top: 0; color: var(--color-primary-strong); font-size: 1.1rem; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: bold; color: #666; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; transition: 0.2s; }
        .form-group input:focus { border-color: var(--color-primary); outline: none; box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1); }

        .btn-save { background: var(--color-primary); color: white; padding: 14px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: bold; width: 100%; transition: 0.2s; }
        .btn-save:hover { background: var(--color-primary-strong); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        /* Stepper */
        .stepper-list { list-style: none; padding: 0; margin-bottom: 30px; border: 1px solid #eee; border-radius: 8px; overflow: hidden; }
        .stepper-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; border-bottom: 1px solid #eee; background: #fff; }
        .stepper-item.current { background: #e8f5e9; border-left: 4px solid var(--color-primary); }
        .stepper-btn { background: #ddd; color: #555; border: none; padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; }
        .stepper-btn.active { background: var(--color-primary); color: white; }

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
    <a href="?sair=true" style="color: white; text-decoration: underline;">Sair</a>
</header>

<div class="admin-container">
    <aside class="sidebar">
        <a href="?novo=true" style="display:block; text-align:center; background:#efb524; padding:12px; border-radius:6px; color:black; font-weight:bold; text-decoration:none; margin-bottom:20px; box-shadow:0 3px 6px rgba(0,0,0,0.1);">+ Novo Cliente</a>
        <h4 style="margin: 10px 0; font-size: 0.8rem; color: #999; text-transform: uppercase; letter-spacing: 1px;">Meus Clientes</h4>
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
            
            <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="margin: 0; color: #333; font-size: 1.8rem;"><?= htmlspecialchars($cliente_ativo['nome']) ?></h1>
                    <span style="color:#777; font-size:0.9rem;">Gerenciando dados do processo</span>
                </div>
                <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" onclick="return confirm('ATENÇÃO: Confirmar exclusão?')" style="color:#d32f2f; font-size:0.9rem; background: #fdecea; padding: 8px 16px; border-radius: 6px; text-decoration:none; font-weight:600;">Excluir Cliente</a>
            </div>

            <!-- Navegação de Abas (3 Abas Principais) -->
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
                                <div class="form-group"><label>CPF / CNPJ</label><input type="text" name="cpf_cnpj" value="<?= $detalhes['cpf_cnpj']??'' ?>"></div>
                                <div class="form-group"><label>RG / Inscrição Est.</label><input type="text" name="rg_ie" value="<?= $detalhes['rg_ie']??'' ?>"></div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group"><label>Estado Civil</label><input type="text" name="estado_civil" value="<?= $detalhes['estado_civil']??'' ?>"></div>
                                <div class="form-group"><label>Profissão</label><input type="text" name="profissao" value="<?= $detalhes['profissao']??'' ?>"></div>
                            </div>
                            <div class="form-group"><label>Endereço Residencial</label><input type="text" name="endereco_residencial" value="<?= $detalhes['endereco_residencial']??'' ?>"></div>
                            <div class="form-grid">
                                <div class="form-group"><label>Email</label><input type="text" name="contato_email" value="<?= $detalhes['contato_email']??'' ?>"></div>
                                <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="contato_tel" value="<?= $detalhes['contato_tel']??'' ?>"></div>
                            </div>
                        </div>

                        <!-- Section B: Imóvel -->
                        <div class="form-card">
                            <h3>🏠 Dados do Imóvel e Lote</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Inscrição Imobiliária (IPTU)</label><input type="text" name="inscricao_imob" value="<?= $detalhes['inscricao_imob']??'' ?>"></div>
                                <div class="form-group"><label>Matrícula</label><input type="text" name="num_matricula" value="<?= $detalhes['num_matricula']??'' ?>"></div>
                                <div class="form-group"><label>Zoneamento</label><input type="text" name="zoneamento" value="<?= $detalhes['zoneamento']??'' ?>"></div>
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
                            <div class="form-grid">
                                <div class="form-group"><label>Responsável Técnico</label><input type="text" name="resp_tecnico" value="<?= $detalhes['resp_tecnico']??'' ?>"></div>
                                <div class="form-group"><label>Registro Profissional</label><input type="text" name="registro_prof" value="<?= $detalhes['registro_prof']??'' ?>"></div>
                                <div class="form-group"><label>Número ART / RRT</label><input type="text" name="num_art_rrt" value="<?= $detalhes['num_art_rrt']??'' ?>"></div>
                            </div>
                        </div>

                        <button type="submit" name="salvar_detalhes" class="btn-save">Salvar Cadastro Unificado</button>
                    </form>

                <?php elseif($active_tab == 'status'): ?>
                    <!-- ABA 2: STATUS E PENDENCIAS -->
                    
                    <div class="form-card" style="border-left: 5px solid #ffc107;">
                        <h3>⚠️ Gestão de Pendências e Status</h3>
                        <p style="margin-bottom:20px; color:#555;">Controle visualmente em que fase o projeto está e forneça o link para o cliente resolver pendências.</p>

                        <!-- Link Pendências -->
                        <form method="POST" style="margin-bottom:30px;">
                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                            <div class="form-group">
                                <label style="font-size:1rem; color:#d97706;">Link da Pasta de Pendências (Drive)</label>
                                <input type="url" name="link_doc_pendencias" value="<?= $detalhes['link_doc_pendencias']??'' ?>" placeholder="Aquele link da pasta onde o cliente deve subir documentos..." style="border: 2px solid #ffc107; background:#fffbf2;">
                            </div>
                            <!-- Botão Salvar Discreto p/ esse campo -->
                            <div style="text-align:right; margin-top:5px;">
                                <button type="submit" name="salvar_detalhes" style="background:#d97706; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Salvar Link</button>
                            </div>
                        </form>
                        
                        <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

                        <!-- Timeline Stepper -->
                        <h4>Fase Atual (Stepper)</h4>
                        <ul class="stepper-list">
                            <?php 
                            $etapa_atual_db = $detalhes['etapa_atual'] ?? '';
                            foreach($fases_padrao as $fase): 
                                $is_current = ($etapa_atual_db === $fase);
                            ?>
                                <li class="stepper-item <?= $is_current ? 'current' : '' ?>">
                                    <span style="font-weight: 500; font-size: 1rem;"><?= $fase ?></span>
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

                    <!-- Historico -->
                    <h4>Histórico de Movimentos</h4>
                    <table style="width:100%; font-size:0.9rem; border-collapse:collapse;">
                        <thead style="background:#f0f0f0;"><tr><th>Data</th><th>Fase</th></tr></thead>
                        <tbody>
                            <?php 
                            $movs = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                            $movs->execute([$cliente_ativo['id']]);
                            foreach($movs->fetchAll() as $m): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:10px;"><?= date('d/m/y H:i', strtotime($m['data_movimento'])) ?></td>
                                <td style="padding:10px;"><strong><?= htmlspecialchars($m['titulo_fase']) ?></strong></td>
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
                            <p style="margin-bottom:20px;">Estes links aparecerão como botões grandes no topo do painel do cliente.</p>

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

                    <!-- Lista de Uploads Individuais (Legado/Extra) -->
                    <div class="card" style="margin-top:30px; border:1px dashed #ccc; padding:20px;">
                        <h4>Anexos Avulsos (Lista)</h4>
                        <form method="POST" style="display:flex; gap:10px; margin-bottom:15px;">
                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                            <input type="text" name="titulo" placeholder="Nome do Arquivo" required style="flex:1; padding:8px;">
                            <input type="url" name="link" placeholder="Link Drive" required style="flex:2; padding:8px;">
                            <button type="submit" name="novo_doc" style="background:#555; color:white; border:none; padding:0 20px; border-radius:4px; cursor:pointer;">+ Adicionar</button>
                        </form>
                        <?php foreach($docs_ativo as $d): ?>
                            <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
                                <span>📄 <?= htmlspecialchars($d['titulo']) ?></span>
                                <a href="<?= htmlspecialchars($d['link_drive']) ?>" target="_blank">Abrir</a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>

        <?php endif; ?>
    </main>
</div>
</body>
</html>
