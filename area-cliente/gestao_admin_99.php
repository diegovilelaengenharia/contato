<?php
session_start();
require 'db.php';

// --- Configuração e Segurança ---
$minha_senha_mestra = "VilelaAdmin2025"; 

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

// --- Fases Padrão ---
$fases_padrao = [
    "Abertura de Processo (Guichê)", "Fiscalização (Parecer Fiscal)", "Triagem (Documentos Necessários)",
    "Comunicado de Pendências (Triagem)", "Análise Técnica (Engenharia)", "Comunicado (Pendências e Taxas)",
    "Confecção de Documentos", "Avaliação (ITBI/Averbação)", "Processo Finalizado (Documentos Prontos)"
];

// --- Processamento de Formulários ---

// 2.2 Atualizar Etapa Atual (Disponível na aba Andamento)
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
    
    // Check if exists
    $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $check->execute([$cid]);
    $exists = $check->fetch();

    $campos = [
        'tipo_pessoa', 'cpf_cnpj', 'rg_ie', 'estado_civil', 'profissao', 'endereco_residencial', 'contato_email', 'contato_tel',
        'inscricao_imob', 'num_matricula', 'endereco_imovel', 'area_terreno', 'area_construida', 
        'tipo_responsavel', 'resp_tecnico', 'registro_prof', 'num_art_rrt', 
        'link_drive_pasta', 'link_doc_iniciais', 'link_doc_pendencias', 'link_doc_finais',
        'texto_pendencias'
    ];

    $set_clause = [];
    foreach($campos as $col) $set_clause[] = "$col=?";
    
    if ($exists) {
        $sql = "UPDATE processo_detalhes SET " . implode(', ', $set_clause) . " WHERE cliente_id=?";
    } else {
        $placeholders = array_fill(0, count($campos), '?');
        $sql = "INSERT INTO processo_detalhes (" . implode(', ', $campos) . ", cliente_id) VALUES (" . implode(', ', $placeholders) . ", ?)";
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

// 3. Adicionar Documento Avulso
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
        :root {
            /* Tema Verde Claro */
            --color-bg: #f0f8f5; 
            --color-surface: #ffffff;
            --color-text: #2f3e36;
            --color-text-subtle: #5f7a6c;
            --color-border: #dbece5;
            --color-primary: #146c43;
            --color-primary-light: #d1e7dd;
            --shadow: 0 4px 20px rgba(20, 108, 67, 0.08);
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
        .admin-container { display: grid; grid-template-columns: 260px 1fr; gap: 24px; max-width: 1600px; margin: 30px auto; padding: 0 20px; align-items: start; }
        
        .sidebar { background: var(--color-surface); border-radius: 12px; box-shadow: var(--shadow); padding: 20px; position: sticky; top: 90px; border: 1px solid var(--color-border); }
        .client-list { list-style: none; padding: 0; margin: 0; max-height: 70vh; overflow-y: auto; }
        .client-list li a { display: block; padding: 12px; border-radius: 8px; text-decoration: none; color: var(--color-text); border-bottom: 1px solid var(--color-border); font-size: 0.95rem; transition: 0.2s; font-weight: 500;}
        .client-list li a:hover { background: var(--color-primary-light); color: var(--color-primary); }
        .client-list li a.active { background: var(--color-primary); color: white; border-color: transparent; box-shadow: 0 4px 10px rgba(20,108,67,0.3); }

        .tabs-header { display: flex; gap: 15px; margin-bottom: 25px; border-bottom: none; overflow-x: auto; padding-bottom: 5px; }
        .tab-btn { padding: 10px 24px; background: var(--color-surface); border: 2px solid transparent; border-radius: 99px; cursor: pointer; font-weight: 600; color: var(--color-text-subtle); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: var(--shadow); }
        .tab-btn:hover { transform: translateY(-3px); color: var(--color-primary); }
        .tab-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); box-shadow: 0 5px 15px rgba(20,108,67,0.3); }

        .tab-content { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Modern Cards */
        .form-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: var(--shadow); position: relative; overflow: hidden; }
        .form-card::before { content: ''; position: absolute; top:0; left:0; width: 6px; height: 100%; background: var(--color-primary); opacity: 0.5; }
        .form-card h3 { margin-top: 0; color: var(--color-primary); font-size: 1.25rem; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--color-border); padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; letter-spacing: -0.5px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--color-text-subtle); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; border: 1px solid var(--color-border); background: var(--color-bg); color: var(--color-text); border-radius: 10px; box-sizing: border-box; transition: 0.2s; font-family: inherit; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--color-primary); outline: none; background: var(--color-surface); box-shadow: 0 0 0 4px rgba(20, 108, 67, 0.1); }

        .btn-save { background: var(--color-primary); color: white; padding: 16px 32px; border: none; border-radius: 12px; cursor: pointer; font-size: 1.1rem; font-weight: 700; width: 100%; transition: 0.2s; margin-top: 20px; box-shadow: 0 10px 20px rgba(20,108,67,0.2); }
        .btn-save:hover { background: #0f5132; transform: translateY(-2px); box-shadow: 0 15px 30px rgba(20,108,67,0.3); }

        /* HEADER TIMELINE (Simple) */
        .simple-timeline { display: flex; justify-content: space-between; gap: 5px; margin: 15px 0 30px; background: var(--color-surface); padding: 15px; border-radius: 12px; box-shadow: var(--shadow); border: 1px solid var(--color-border); flex-wrap: wrap; }
        .st-item { flex: 1; text-align: center; font-size: 0.8rem; color: var(--color-text-subtle); padding: 8px; border-radius: 8px; transition: 0.2s; min-width: 80px; display:flex; flex-direction:column; align-items:center; opacity: 0.6; }
        .st-item.active { background: var(--color-primary-light); color: var(--color-primary); font-weight: 700; opacity: 1; transform: scale(1.02); box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid var(--color-primary); }
        .st-item.past { color: var(--color-primary); opacity: 0.8; }
        .st-dot { width: 10px; height: 10px; background: currentColor; border-radius: 50%; margin-bottom: 5px; }

        .btn-toggle-theme { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 6px 12px; border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: inherit; font-size: 0.85rem; margin-right: 15px; transition:0.2s; }
        .btn-toggle-theme:hover { background: rgba(255,255,255,0.3); }

        @media (max-width: 768px) {
            .admin-container { grid-template-columns: 1fr; }
            .sidebar { position: static; margin-bottom: 20px; }
            .simple-timeline { overflow-x: auto; flex-wrap: nowrap; justify-content: flex-start; }
            .st-item { min-width: 120px; }
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
        <a href="?novo=true" style="display:block; text-align:center; background:#efb524; padding:12px; border-radius:6px; color:black; font-weight:bold; text-decoration:none; margin-bottom:20px; box-shadow: var(--shadow);">+ Novo Cliente</a>
        <h4 style="margin: 10px 0; font-size: 0.85rem; color: var(--color-text-subtle); text-transform: uppercase; letter-spacing: 1px;">Meus Clientes</h4>
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
            <div style="background:#d1e7dd; color:#0f5132; padding:20px; margin-bottom:30px; border-radius:12px; font-weight:600; text-align:center;"><?= $sucesso ?></div>
        <?php endif; ?>
        <?php if(isset($erro)): ?>
            <div style="background:#f8d7da; color:#842029; padding:20px; margin-bottom:30px; border-radius:12px; font-weight:600; text-align:center;"><?= $erro ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['novo'])): ?>
            <div class="tab-content">
                <h2>Cadastrar Novo Cliente</h2>
                <form method="POST" class="form-card">
                    <div class="form-group"><label>Nome do Cliente</label><input type="text" name="nome" required></div>
                    <div class="form-group"><label>Usuário de Acesso (Login)</label><input type="text" name="usuario" required></div>
                    <div class="form-group"><label>Senha Inicial</label><input type="text" name="senha" required></div>
                    <button type="submit" name="novo_cliente" class="btn-save">Cadastrar</button>
                </form>
            </div>

        <?php elseif($cliente_ativo): ?>
            
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="margin: 0; color: var(--color-text); font-size: 2rem; letter-spacing: -1px;"><?= htmlspecialchars($cliente_ativo['nome']) ?></h1>
                    <span style="color:var(--color-text-subtle); font-size:1rem;">Painel de Controle do Processo</span>
                </div>
                
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                     <?php if(!empty($detalhes['link_drive_pasta'])): ?>
                        <a href="<?= htmlspecialchars($detalhes['link_drive_pasta']) ?>" target="_blank" style="background: #2f3e36; color:white; padding: 12px 24px; border-radius: 8px; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:8px;">
                            📂 Drive
                        </a>
                    <?php endif; ?>
                    <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" onclick="return confirm('ATENÇÃO: Confirmar exclusão?')" style="color:#d32f2f; font-size:0.9rem; background: rgba(253, 236, 234, 0.5); border: 1px solid #d32f2f; padding: 12px 20px; border-radius: 8px; text-decoration:none; font-weight:600; display:flex; align-items:center;">Excluir</a>
                </div>
            </div>

            <!-- GLOBAL VISUAL TIMELINE (SIMPLE) -->
            <div class="simple-timeline">
                <?php 
                $etapa_atual = $detalhes['etapa_atual'] ?? '';
                $found_index = array_search($etapa_atual, $fases_padrao);
                if($found_index === false) $found_index = -1;
                
                foreach($fases_padrao as $i => $fase): 
                    $st_class = '';
                    if ($i < $found_index) $st_class = 'past';
                    elseif ($i === $found_index) $st_class = 'active';
                ?>
                    <div class="st-item <?= $st_class ?>">
                        <div class="st-dot"></div>
                        <span><?= $fase ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Navegação de Abas -->
            <div class="tabs-header">
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=cadastro" class="tab-btn <?= $active_tab=='cadastro'?'active':'' ?>">📝 Cadastro Unificado</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="tab-btn <?= $active_tab=='andamento'?'active':'' ?>">📊 Andamento</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="tab-btn <?= $active_tab=='pendencias'?'active':'' ?>">⚠️ Pendências</a>
            </div>

            <div class="tab-content">
                
                <?php if($active_tab == 'cadastro'): ?>
                    <!-- ABA 1: CADASTRO UNIFICADO -->
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
                            <!-- REQUERENTE -->
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
                                        <label>CPF / CNPJ</label>
                                        <input type="text" name="cpf_cnpj" value="<?= $detalhes['cpf_cnpj']??'' ?>" 
                                               style="border-left: 6px solid <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'#d32f2f':'#0288d1' ?>;">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-top:15px;"><label>Documento de ID</label><input type="text" name="rg_ie" value="<?= $detalhes['rg_ie']??'' ?>"></div>
                                <div class="form-group" style="margin-top:15px;"><label>Estado Civil</label>
                                    <select name="estado_civil">
                                        <option value="">-- Selecione --</option>
                                        <?php 
                                        foreach(["Solteiro(a)", "Casado(a)", "Divorciado(a)", "Viúvo(a)", "União Estável"] as $ec) {
                                            $sel = ($detalhes['estado_civil']??'') == $ec ? 'selected' : '';
                                            echo "<option value='$ec' $sel>$ec</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-top:15px;"><label>Profissão</label><input type="text" name="profissao" value="<?= $detalhes['profissao']??'' ?>"></div>
                                <div class="form-group" style="margin-top:15px;"><label>Endereço Residencial</label><input type="text" name="endereco_residencial" value="<?= $detalhes['endereco_residencial']??'' ?>"></div>
                                <div class="form-grid" style="margin-top:15px;">
                                    <div class="form-group"><label>Email</label><input type="text" name="contato_email" value="<?= $detalhes['contato_email']??'' ?>"></div>
                                    <div class="form-group"><label>Telefone</label><input type="text" name="contato_tel" value="<?= $detalhes['contato_tel']??'' ?>"></div>
                                </div>
                            </div>

                            <!-- IMÓVEL & TÉCNICO -->
                            <div>
                                <div class="form-card">
                                    <h3>🏠 Dados do Imóvel</h3>
                                    <div class="form-group"><label>Endereço do Imóvel</label><input type="text" name="endereco_imovel" value="<?= $detalhes['endereco_imovel']??'' ?>"></div>
                                    <div class="form-grid" style="margin-top:15px;">
                                        <div class="form-group"><label>Inscrição Imob.</label><input type="text" name="inscricao_imob" value="<?= $detalhes['inscricao_imob']??'' ?>"></div>
                                        <div class="form-group"><label>Matrícula</label><input type="text" name="num_matricula" value="<?= $detalhes['num_matricula']??'' ?>"></div>
                                    </div>
                                    <div class="form-grid" style="margin-top:15px;">
                                        <div class="form-group"><label>Área Terreno (m²)</label><input type="number" step="0.01" name="area_terreno" value="<?= $detalhes['area_terreno']??'' ?>"></div>
                                        <div class="form-group"><label>Área Construída (m²)</label><input type="number" step="0.01" name="area_construida" value="<?= $detalhes['area_construida']??'' ?>"></div>
                                    </div>
                                </div>

                                <div class="form-card">
                                    <h3>👷 Responsável Técnico</h3>
                                    <div class="form-group">
                                        <label>Tipo de Profissional</label>
                                        <select name="tipo_responsavel">
                                            <option value="">-- Selecione --</option>
                                            <option value="Engenheiro" <?= ($detalhes['tipo_responsavel']??'')=='Engenheiro'?'selected':'' ?>>Engenheiro(a)</option>
                                            <option value="Arquiteto" <?= ($detalhes['tipo_responsavel']??'')=='Arquiteto'?'selected':'' ?>>Arquiteto(a)</option>
                                            <option value="Tecnico" <?= ($detalhes['tipo_responsavel']??'')=='Tecnico'?'selected':'' ?>>Técnico(a)</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-top:15px;"><label>Nome do Responsável</label><input type="text" name="resp_tecnico" value="<?= $detalhes['resp_tecnico']??'' ?>"></div>
                                    <div class="form-grid" style="margin-top:15px;">
                                        <div class="form-group"><label>CAU/CREA</label><input type="text" name="registro_prof" value="<?= $detalhes['registro_prof']??'' ?>"></div>
                                        <div class="form-group"><label>ART/RRT</label><input type="text" name="num_art_rrt" value="<?= $detalhes['num_art_rrt']??'' ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION FULL WIDTH: LINKS -->
                        <div class="form-card">
                            <h3>📂 Links e Pastas (Drive)</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Docs Iniciais</label>
                                    <input type="url" name="link_doc_iniciais" value="<?= $detalhes['link_doc_iniciais']??'' ?>" placeholder="https://drive...">
                                </div>
                                <div class="form-group">
                                    <label>Docs Finais</label>
                                    <input type="url" name="link_doc_finais" value="<?= $detalhes['link_doc_finais']??'' ?>" placeholder="https://drive...">
                                </div>
                                <div class="form-group">
                                    <label>Pasta Geral</label>
                                    <input type="url" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" placeholder="https://drive...">
                                </div>
                            </div>
                        </div>

                        <!-- UPLOAD AVULSO -->
                        <div class="form-card" style="border-style: dashed; background: transparent;">
                            <h3>📎 Anexos Avulsos</h3>
                            <?php foreach($docs_ativo as $d): ?>
                                <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
                                    <span style="color: var(--color-text);">📄 <?= htmlspecialchars($d['titulo']) ?></span>
                                    <a href="<?= htmlspecialchars($d['link_drive']) ?>" target="_blank">Abrir</a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" name="salvar_detalhes" class="btn-save">Salvar Todo o Cadastro</button>
                    </form>
                    
                     <form method="POST" style="margin-top:20px; display:flex; gap:10px;">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <input type="text" name="titulo" placeholder="Nome do Arquivo" required style="flex:1; padding:10px; border-radius:8px; border:1px solid #ccc;">
                        <input type="url" name="link" placeholder="Link Drive" required style="flex:2; padding:10px; border-radius:8px; border:1px solid #ccc;">
                        <button type="submit" name="novo_doc" style="background:#555; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">+ Add</button>
                    </form>

                <?php elseif($active_tab == 'andamento'): ?>
                    <!-- ABA 2: ANDAMENTO (CONTROLE DE FASE + HISTÓRICO) -->
                    
                    <div class="form-card">
                        <h3>📊 Controle de Fase</h3>
                        <p style="color:var(--color-text-subtle);">Selecione a fase atual do processo para atualizar a linha do tempo e registrar no histórico.</p>
                        
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:20px;">
                            <?php foreach($fases_padrao as $fase): 
                                $is_current = (($detalhes['etapa_atual']??'') === $fase);
                            ?>
                                <?php if($is_current): ?>
                                    <button disabled style="background:var(--color-primary); color:white; border:none; padding:10px 15px; border-radius:8px; opacity:0.8;">✅ <?= $fase ?></button>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                                        <input type="hidden" name="nova_etapa" value="<?= $fase ?>">
                                        <button type="submit" name="atualizar_etapa" style="background:var(--color-surface); border:1px solid var(--color-border); padding:10px 15px; border-radius:8px; cursor:pointer; color:var(--color-text);">➡️ <?= $fase ?></button>
                                    </form>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-card">
                        <h3>📜 Histórico de Movimentações</h3>
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:rgba(0,0,0,0.03);">
                                    <th style="padding:12px; text-align:left; color:var(--color-text);">Data</th>
                                    <th style="padding:12px; text-align:left; color:var(--color-text);">Fase/Título</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $movs = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                                $movs->execute([$cliente_ativo['id']]);
                                foreach($movs->fetchAll() as $m): ?>
                                <tr style="border-bottom:1px solid var(--color-border);">
                                    <td style="padding:12px; color: var(--color-text);"><?= date('d/m/y H:i', strtotime($m['data_movimento'])) ?></td>
                                    <td style="padding:12px; color: var(--color-text);"><strong><?= htmlspecialchars($m['titulo_fase']) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif($active_tab == 'pendencias'): ?>
                    <!-- ABA 3: PENDÊNCIAS (TEXTO) -->
                    
                    <div class="form-card" style="border-left: 6px solid #ffc107;">
                        <h3>⚠️ Quadro de Pendências (Visível para o Cliente)</h3>
                        <form method="POST">
                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                            <div class="form-group">
                                <textarea name="texto_pendencias" rows="10" placeholder="Ex: - Falta comprovante de residencia..." style="font-size:1rem; line-height:1.5;"><?= htmlspecialchars($detalhes['texto_pendencias']??'') ?></textarea>
                            </div>
                            
                            <h4 style="margin-top:20px; color:var(--color-text);">Link da Pasta de Pendências (Opcional)</h4>
                            <div class="form-group">
                                <input type="url" name="link_doc_pendencias" value="<?= $detalhes['link_doc_pendencias']??'' ?>" placeholder="https://drive...">
                            </div>

                            <button type="submit" name="salvar_detalhes" class="btn-save" style="background:#d97706; margin-top:20px;">Salvar Pendências</button>
                        </form>
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
