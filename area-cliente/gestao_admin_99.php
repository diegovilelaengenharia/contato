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

// --- Processamento ---

// 1. Atualizar Etapa (Aba Andamento)
if (isset($_POST['atualizar_etapa'])) {
    $nova_etapa = $_POST['nova_etapa'];
    $obs_etapa = $_POST['observacao_etapa'] ?? '';
    $cid = $_POST['cliente_id'];
    
    try {
        // Atualiza a fase atual
        $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
        
        // Registra histórico
        $titulo = "Mudança de Fase: " . $nova_etapa;
        
        // Formatação do comentário: Título padrão + Delimitador + Obs do usuário (se houver)
        $desc = "Fase atualizada pelo administrador.";
        if (trim($obs_etapa) !== '') {
            $desc .= "\n||COMENTARIO_USER||" . $obs_etapa;
        }
        
        $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo) VALUES (?, ?, NOW(), ?, 'conclusao')";
        $pdo->prepare($sql)->execute([$cid, $titulo, $desc]);

        $sucesso = "Fase atualizada e histórico registrado!";
    } catch(PDOException $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}

// 2. Salvar Dados Cadastrais (Aba Cadastro)
if (isset($_POST['btn_salvar_cadastro'])) {
    $cid = $_POST['cliente_id'];
    $campos = [
        'tipo_pessoa', 'cpf_cnpj', 'rg_ie', 'estado_civil', 'profissao', 'endereco_residencial', 'contato_email', 'contato_tel',
        'inscricao_imob', 'num_matricula', 'endereco_imovel', 'area_terreno', 'area_construida', 
        'tipo_responsavel', 'resp_tecnico', 'registro_prof', 'num_art_rrt'
    ];
    
    // Verifica se existe registro
    $exists = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $exists->execute([$cid]);
    
    if ($exists->fetch()) {
        $set = implode('=?, ', $campos) . '=?'; // ultimo ?
        $sql = "UPDATE processo_detalhes SET " . implode('=?, ', $campos) . "=? WHERE cliente_id=?";
    } else {
        $sql = "INSERT INTO processo_detalhes (" . implode(', ', $campos) . ", cliente_id) VALUES (" . str_repeat('?,', count($campos)) . "?)";
    }
    
    $params = [];
    foreach($campos as $c) $params[] = $_POST[$c] ?? null;
    $params[] = $cid;

    try { $pdo->prepare($sql)->execute($params); $sucesso = "Cadastro salvo!"; } 
    catch (PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 3. Salvar Pendências (Aba Pendências)
if (isset($_POST['btn_salvar_pendencias'])) {
    $cid = $_POST['cliente_id'];
    try {
        $sql = "UPDATE processo_detalhes SET texto_pendencias = ?, link_doc_pendencias = ? WHERE cliente_id = ?";
        $pdo->prepare($sql)->execute([$_POST['texto_pendencias'], $_POST['link_doc_pendencias'], $cid]);
        $sucesso = "Pendências atualizadas!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 4. Salvar Arquivos/Links (Aba Arquivos)
if (isset($_POST['btn_salvar_arquivos'])) {
    $cid = $_POST['cliente_id'];
    try {
        $sql = "UPDATE processo_detalhes SET link_drive_pasta = ? WHERE cliente_id = ?";
        $pdo->prepare($sql)->execute([$_POST['link_drive_pasta'], $cid]);
        $sucesso = "Links de arquivos atualizados!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 5. Novo Cliente
if (isset($_POST['novo_cliente'])) {
    $nome_original = $_POST['nome'];
    $user = $_POST['usuario'];
    $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    try {
        $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)")->execute([$nome_original, $user, $pass]);
        $nid = $pdo->lastInsertId();
        
        // ROTINA DE AUTO-RENOMEAÇÃO (Cliente 00X - Nome)
        $nome_final = sprintf("Cliente %03d - %s", $nid, $nome_original);
        $pdo->prepare("UPDATE clientes SET nome = ? WHERE id = ?")->execute([$nome_final, $nid]);
        
        $pdo->prepare("INSERT INTO processo_detalhes (cliente_id) VALUES (?)")->execute([$nid]);
        $sucesso = "Cliente criado com sucesso: $nome_final";
    } catch (PDOException $e) { $erro = "Erro ao criar cliente."; }
}

// 5.5 Atualizar Acesso (Nome/Login/Senha)
if (isset($_POST['btn_salvar_acesso'])) {
    $cid = $_POST['cliente_id'];
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    $nova_senha = $_POST['nova_senha'];

    try {
        if (!empty($nova_senha)) {
            $pass = password_hash($nova_senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE clientes SET nome=?, usuario=?, senha=? WHERE id=?")->execute([$nome, $user, $pass, $cid]);
            $sucesso = "Dados de acesso e Senha atualizados!";
        } else {
            $pdo->prepare("UPDATE clientes SET nome=?, usuario=? WHERE id=?")->execute([$nome, $user, $cid]);
            $sucesso = "Dados de acesso atualizados (Senha mantida)!";
        }
        // Atualiza var local p/ refletir na hora
        $refresh = $pdo->prepare("SELECT * FROM clientes WHERE id=?"); $refresh->execute([$cid]);
        $cliente_ativo = $refresh->fetch();

    } catch (PDOException $e) { $erro = "Erro ao atualizar acesso: " . $e->getMessage(); }
}

// 6. Financeiro - Adicionar
if (isset($_POST['btn_salvar_financeiro'])) {
    $cid = $_POST['cliente_id'];
    try {
        $stmt = $pdo->prepare("INSERT INTO processo_financeiro (cliente_id, categoria, descricao, valor, data_vencimento, status, link_comprovante) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cid, 
            $_POST['categoria'], 
            $_POST['descricao'], 
            str_replace(',', '.', $_POST['valor']), 
            $_POST['data_vencimento'], 
            $_POST['status'], 
            $_POST['link_comprovante']
        ]);
        $sucesso = "Lançamento financeiro adicionado!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }

}

// 6.5 Salvar Dados Gerais Financeiro (Link da Pasta)
if (isset($_POST['btn_salvar_dados_financeiros'])) {
    $cid = $_POST['cliente_id'];
    try {
        $pdo->prepare("UPDATE processo_detalhes SET link_pasta_pagamentos = ? WHERE cliente_id = ?")->execute([$_POST['link_pasta_pagamentos'], $cid]);
        $sucesso = "Link da pasta de pagamentos salvo!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 7. Financeiro - Excluir
if (isset($_GET['del_fin'])) {
    $fid = $_GET['del_fin'];
    $cid = $_GET['cliente_id']; // para manter na pag
    $pdo->prepare("DELETE FROM processo_financeiro WHERE id=? AND cliente_id=?")->execute([$fid, $cid]);
    header("Location: ?cliente_id=$cid&tab=financeiro");
    exit;
}

// Delete
if (isset($_GET['delete_cliente'])) {
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$_GET['delete_cliente']]);
    header("Location: ?"); exit;
}

// --- Consultas Iniciais ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
$cliente_ativo = null;
$detalhes = [];

if (isset($_GET['cliente_id'])) {
    $id = $_GET['cliente_id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?"); $stmt->execute([$id]);
    $cliente_ativo = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?"); $stmt->execute([$id]);
    $detalhes = $stmt->fetch();
    if(!$detalhes) $detalhes = [];
}
$active_tab = $_GET['tab'] ?? 'cadastro';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <style>
        :root {
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
            --color-bg: #121212; --color-surface: #1e1e1e; --color-text: #e0e0e0; --color-text-subtle: #a0a0a0; --color-border: #333333;
            --shadow: 0 4px 20px rgba(0,0,0,0.3); --header-bg: #0b3d26;
        }

        /* FIX CRÍTICO DE LAYOUT */
        body { 
            background: var(--color-bg) !important; 
            color: var(--color-text); 
            font-family: 'Outfit', sans-serif; 
            display: block !important; 
            padding: 0 !important; 
            margin: 0 !important;
            height: auto !important;
            min-height: 100vh;
        }

        .admin-header { background: var(--header-bg); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 999; width: 100%; box-sizing: border-box; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .admin-container { display: grid; grid-template-columns: 260px 1fr; gap: 24px; max-width: 1600px; margin: 30px auto; padding: 0 20px; align-items: start; }
        .sidebar { background: var(--color-surface); border-radius: 12px; box-shadow: var(--shadow); padding: 20px; position: sticky; top: 90px; border: 1px solid var(--color-border); }
        .client-list li a { display: block; padding: 12px; border-radius: 8px; text-decoration: none; color: var(--color-text); border-bottom: 1px solid var(--color-border); font-size: 0.95rem; margin-bottom:5px; transition:0.2s; }
        .client-list li a:hover { background: var(--color-primary-light); color: var(--color-primary); }
        .client-list li a.active { background: var(--color-primary); color: white; }

        .tabs-header { display: flex; gap: 10px; margin-bottom: 25px; overflow-x: auto; padding-bottom: 5px; flex-wrap:wrap; border-bottom: 1px solid var(--color-border); }
        .tab-btn { padding: 12px 20px; background: transparent; border: none; border-bottom: 3px solid transparent; border-radius: 0; cursor: pointer; font-weight: 600; color: var(--color-text-subtle); text-decoration: none; transition: 0.2s; display: flex; align-items: center; gap: 8px; white-space: nowrap; font-size: 0.95rem; opacity: 0.7; }
        .tab-btn:hover { background: rgba(0,0,0,0.02); color: var(--color-primary); opacity: 1; }
        .tab-btn.active { border-bottom-color: var(--color-primary); color: var(--color-primary); opacity: 1; font-weight: 700; background: transparent; box-shadow: none; }
        .tab-btn.blue.active { border-bottom-color: #2196f3; color: #1976d2; }

        .form-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: var(--shadow); position: relative; overflow: hidden; }
        .form-card::before { content: ''; position: absolute; top:0; left:0; width: 6px; height: 100%; background: var(--color-primary); opacity: 0.5; }
        .form-card h3 { margin-top: 0; color: var(--color-primary); font-size: 1.25rem; font-weight: 700; border-bottom: 1px solid var(--color-border); padding-bottom: 15px; margin-bottom: 25px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; }
        .form-group { margin-bottom:15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--color-text-subtle); margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; border: 1px solid var(--color-border); background: var(--color-bg); color: var(--color-text); border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem; }
        .form-group input:focus { border-color: var(--color-primary); outline: none; background: var(--color-surface); }

        .btn-save { background: var(--color-primary); color: white; padding: 16px 32px; border: none; border-radius: 12px; cursor: pointer; font-size: 1.1rem; font-weight: 700; width: 100%; transition: 0.2s; margin-top: 20px; }
        .btn-save:hover { background: #0f5132; transform: translateY(-2px); }

        /* Timeline */
        .simple-timeline { display: flex; gap: 10px; margin: 15px 0 30px; background: var(--color-surface); padding: 25px 20px; border-radius: 12px; box-shadow: var(--shadow); border: 1px solid var(--color-border); overflow-x:auto; align-items: flex-start; }
        .st-item { flex: 1; text-align: center; min-width: 100px; display:flex; flex-direction:column; align-items:center; opacity: 0.6; position: relative; }
        .st-item::after { content: ''; position: absolute; top: 9px; left: 50%; width: 100%; height: 2px; background: #ddd; z-index: 0; }
        .st-item:last-child::after { display: none; }
        .st-dot { width: 20px; height: 20px; background: #ddd; border-radius: 50%; margin-bottom: 10px; position: relative; z-index: 1; border: 3px solid var(--color-surface); transition: 0.3s; }
        .st-item span { font-size: 0.9rem; color: var(--color-text-subtle); line-height: 1.3; font-weight: 500; }
        .st-item.past .st-dot { background: var(--color-primary); }
        .st-item.past span { color: var(--color-primary); }
        .st-item.active { opacity: 1; }
        .st-item.active .st-dot { background: white; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(20,108,67,0.2); transform: scale(1.2); }
        .st-item.active span { font-weight: 800; color: var(--color-primary); font-size: 0.95rem; }

        @media (max-width: 768px) {
            .admin-container { grid-template-columns: 1fr; }
            .sidebar { position: static; margin-bottom: 20px; }
        }
        
        .iframe-container { width:100%; height:600px; border:1px solid var(--color-border); border-radius:8px; display:none; margin-top:15px; }
        .iframe-container.visible { display:block; }
    </style>
</head>
<body>

<header class="admin-header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <img src="../assets/logo.png" alt="Logo" style="height: 50px;">
        <div style="display:flex; flex-direction:column; gap:4px;">
            <h1 style="margin:0; font-size:1.3rem; font-weight:700;">Gestão Administrativa (Vilela Engenharia)</h1>
            <div style="font-size:0.8rem; opacity: 0.9; line-height:1.4;">
                <strong>Eng. Diego Vilela</strong><br>
                CREA-MG: 235474/D &nbsp;|&nbsp; Email: vilela.eng.mg@gmail.com &nbsp;|&nbsp; Tel: (35) 98452-9577
            </div>
        </div>
    </div>
    <div style="display:flex; align-items:center;">
        <button onclick="document.body.classList.toggle('dark-mode')" style="background:transparent; border:1px solid white; color:white; padding:5px 10px; border-radius:20px; cursor:pointer; margin-right:15px;">🌓 Tema</button>
        <a href="?sair=true" style="color: white;">Sair</a>
    </div>
</header>

<div class="admin-container">
    <aside class="sidebar">
        <a href="?novo=true" style="display:block; text-align:center; background:#efb524; padding:12px; border-radius:6px; color:black; font-weight:bold; text-decoration:none; margin-bottom:20px;">+ Novo Cliente</a>
        <h4 style="margin: 10px 0; color: var(--color-text-subtle);">Meus Clientes</h4>
        <ul class="client-list" style="list-style:none; padding:0;">
            <?php foreach($clientes as $c): ?>
                <li><a href="?cliente_id=<?= $c['id'] ?>" class="<?= ($cliente_ativo && $cliente_ativo['id'] == $c['id']) ? 'active' : '' ?>"><?= htmlspecialchars($c['nome']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main>
        <?php if(isset($sucesso)): ?><div style="background:#d1e7dd; color:#0f5132; padding:15px; margin-bottom:20px; border-radius:8px;"><?= $sucesso ?></div><?php endif; ?>
        <?php if(isset($erro)): ?><div style="background:#f8d7da; color:#842029; padding:15px; margin-bottom:20px; border-radius:8px;"><?= $erro ?></div><?php endif; ?>

        <?php if(isset($_GET['novo'])): ?>
            <div class="form-card">
                <h2>Cadastrar Novo Cliente</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
                        <div class="form-group"><label>Login</label><input type="text" name="usuario" required></div>
                        <div class="form-group"><label>Senha</label><input type="text" name="senha" required></div>
                    </div>
                    <button type="submit" name="novo_cliente" class="btn-save">Cadastrar</button>
                </form>
            </div>

        <?php elseif($cliente_ativo): ?>
            
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
                <div>
                    <h1 style="margin: 0; color: var(--color-text); font-size: 1.8rem;"><?= htmlspecialchars($cliente_ativo['nome']) ?></h1>
                    <div style="display:flex; gap:15px; color: var(--color-text-subtle); margin-top:8px; font-size:0.95rem;">
                        <span>🆔 CPF/CNPJ: <strong><?= htmlspecialchars($detalhes['cpf_cnpj']??'--') ?></strong></span>
                        <span>📍 Obra: <strong><?= htmlspecialchars($detalhes['endereco_imovel']??'--') ?></strong></span>
                    </div>
                </div>
                <div>
                    <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" onclick="return confirm('ATENÇÃO EXTREMA!\n\nVocê tem certeza absoluta que deseja EXCLUIR este cliente?\n\nEssa ação apagará todo o histórico e dados permanentemente.')" 
                       style="background: #d32f2f; color:white; padding: 10px 20px; border-radius: 8px; text-decoration:none; font-weight:bold; display:inline-block; margin-top:10px;">
                       🗑️ Excluir Cliente
                    </a>
                </div>
            </div>

            <div class="simple-timeline">
                <?php 
                $found_idx = array_search(($detalhes['etapa_atual']??''), $fases_padrao);
                if($found_idx === false) $found_idx = -1;
                foreach($fases_padrao as $i => $f): 
                    $cl = ($i < $found_idx) ? 'past' : ($i == $found_idx ? 'active' : '');
                ?>
                    <div class="st-item <?= $cl ?>">
                        <div class="st-dot"></div>
                        <span><?= $f ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tabs-header">
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=cadastro" class="tab-btn <?= $active_tab=='cadastro'?'active':'' ?>">📝 Cadastro</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="tab-btn <?= $active_tab=='andamento'?'active':'' ?>">📊 Andamento</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="tab-btn <?= $active_tab=='pendencias'?'active':'' ?>">⚠️ Pendências</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="tab-btn <?= $active_tab=='financeiro'?'active':'' ?>">💰 Financeiro</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=arquivos" class="tab-btn <?= $active_tab=='arquivos'?'active':'' ?>">📂 Arquivos</a>
            </div>

            <?php if($active_tab == 'cadastro'): ?>
                <!-- Form separado para dados detalhados para nao conflitar com o de acesso se quiser submit separado, ou tudo junto.
                     Neste caso, o primeiro form ali em cima fecha. Vamos ajustar. -->
                
                <form method="POST" id="form_dados_acesso">
                    <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                    <!-- Card Acesso Inserido no Grid Abaixo via ReplacementChunk 2 -->
                </form>

                <form method="POST" id="form_dados_detalhados">
                    <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                    <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
                        <!-- Coluna 1: Acesso + Pessoais -->
                        <div>
                             <!-- Card Acesso -->
                            <div class="form-card" style="border-left: 6px solid #efb524;">
                            <div class="form-card" style="border-left: 6px solid #efb524;">
                                <h3>🔐 Dados de Acesso (Login)</h3>
                                <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr auto; gap: 15px; align-items: end;">
                                    <div class="form-group" style="margin-bottom:0;"><label>Nome (Sistema)</label><input type="text" name="nome" value="<?= htmlspecialchars($cliente_ativo['nome']) ?>" required></div>
                                    <div class="form-group" style="margin-bottom:0;"><label>Usuário</label><input type="text" name="usuario" value="<?= htmlspecialchars($cliente_ativo['usuario']) ?>" required></div>
                                    <div class="form-group" style="margin-bottom:0;"><label>Nova Senha</label><input type="text" name="nova_senha" placeholder="Opcional"></div>
                                    <button type="submit" name="btn_salvar_acesso" class="btn-save" style="background:#efb524; color:black; margin:0; padding: 14px 20px; white-space:nowrap; width:auto; height:52px;">Salvar Acesso</button>
                                </div>
                            </div>

                            <div class="form-card">
                                <h3>👤 Detalhes do Requerente</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Tipo</label><select name="tipo_pessoa" disabled style="background:var(--color-bg);"><option value="Fisica">Física</option><option value="Juridica">Jurídica</option></select></div>
                                <div class="form-group"><label>CPF/CNPJ</label><input type="text" name="cpf_cnpj" value="<?= $detalhes['cpf_cnpj']??'' ?>" readonly style="background:var(--color-bg);"></div>
                            </div>
                            <div class="form-group"><label>Identidade (RG)</label><input type="text" name="rg_ie" value="<?= $detalhes['rg_ie']??'' ?>" readonly style="background:var(--color-bg);"></div>
                            <div class="form-group"><label>Email</label><input type="text" name="contato_email" value="<?= $detalhes['contato_email']??'' ?>" readonly style="background:var(--color-bg);"></div>
                            <div class="form-group"><label>Telefone</label><input type="text" name="contato_tel" value="<?= $detalhes['contato_tel']??'' ?>" readonly style="background:var(--color-bg);"></div>

                            <div class="form-group"><label>Endereço</label><input type="text" name="endereco_residencial" value="<?= $detalhes['endereco_residencial']??'' ?>" readonly style="background:var(--color-bg);"></div>
                        </div>
                        </div> <!-- Fim Coluna 1 -->
                        
                        <!-- Coluna 2 -->
                        <div>
                            <div class="form-card">
                                <h3>🏠 Imóvel</h3>
                                <div class="form-group"><label>Endereço da Obra</label><input type="text" name="endereco_imovel" value="<?= $detalhes['endereco_imovel']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                <div class="form-grid">
                                    <div class="form-group"><label>Inscrição</label><input type="text" name="inscricao_imob" value="<?= $detalhes['inscricao_imob']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                    <div class="form-group"><label>Matrícula</label><input type="text" name="num_matricula" value="<?= $detalhes['num_matricula']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                    <div class="form-group"><label>Área Terreno</label><input type="text" name="area_terreno" value="<?= $detalhes['area_terreno']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                    <div class="form-group"><label>Área Constr.</label><input type="text" name="area_construida" value="<?= $detalhes['area_construida']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                </div>
                            </div>
                            <div class="form-card">
                                <h3>👷 Técnico</h3>
                                <div class="form-group"><label>Nome Responsável</label><input type="text" name="resp_tecnico" value="<?= $detalhes['resp_tecnico']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                <div class="form-grid">
                                    <div class="form-group"><label>CAU/CREA</label><input type="text" name="registro_prof" value="<?= $detalhes['registro_prof']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                    <div class="form-group"><label>ART/RRT</label><input type="text" name="num_art_rrt" value="<?= $detalhes['num_art_rrt']??'' ?>" readonly style="background:var(--color-bg);"></div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Botão Salvar Geral (Cadastrais) -->
                <div style="margin-top: -20px; margin-bottom: 40px; display:flex; gap:15px; align-items:center;">
                     <button type="button" onclick="toggleEditMode()" class="btn-save" style="background:#6c757d; width:auto;">🔓 Liberar Edição</button>
                     <button type="submit" form="form_dados_detalhados" name="btn_salvar_cadastro" id="btn_salvar_dados" class="btn-save" style="display:none;">Salvar Detalhes Cadastrais</button>
                </div>

                <script>
                    function toggleEditMode() {
                        const form = document.getElementById('form_dados_detalhados');
                        const inputs = form.querySelectorAll('input, select');
                        const btnSalvar = document.getElementById('btn_salvar_dados');
                        const btnUnlock = document.querySelector('button[onclick="toggleEditMode()"]');
                        
                        inputs.forEach(input => {
                            if (input.hasAttribute('readonly') || input.hasAttribute('disabled')) {
                                input.removeAttribute('readonly');
                                input.removeAttribute('disabled');
                                input.style.background = '#ffffff';
                                input.style.borderColor = 'var(--color-primary)';
                            } else {
                                input.setAttribute('readonly', 'true');
                                // Selects need disabled instead of readonly
                                if(input.tagName === 'SELECT') input.setAttribute('disabled', 'true');
                                input.style.background = 'var(--color-bg)';
                                input.style.borderColor = 'var(--color-border)';
                            }
                        });

                        if (btnSalvar.style.display === 'none') {
                            btnSalvar.style.display = 'block';
                            btnUnlock.innerText = '🔒 Bloquear Edição';
                            btnUnlock.style.background = '#dc3545';
                        } else {
                            btnSalvar.style.display = 'none';
                            btnUnlock.innerText = '🔓 Liberar Edição';
                            btnUnlock.style.background = '#6c757d';
                        }
                    }
                </script>
            
            <?php elseif($active_tab == 'andamento'): ?>
                <div class="form-card">
                    <h3>🔄 Atualizar Fase do Processo</h3>
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-group">
                            <label>Selecione a Nova Fase</label>
                            <select name="nova_etapa" style="font-size:1.1rem; padding:15px; border:2px solid var(--color-primary); color:var(--color-primary); font-weight:bold;">
                                <option value="">-- Selecione --</option>
                                <?php foreach($fases_padrao as $f): ?>
                                    <option value="<?= $f ?>" <?= ($detalhes['etapa_atual']??'')==$f?'selected':'' ?>><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Observação sobre a mudança (opcional)</label>
                            <textarea name="observacao_etapa" rows="3" placeholder="Ex: Protocolado na prefeitura sob nº 123..."></textarea>
                        </div>
                        <button type="submit" name="atualizar_etapa" class="btn-save">Atualizar Status</button>
                    </form>
                </div>

                <div class="form-card">
                    <h3>📜 Histórico de Movimentações</h3>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr style="background:rgba(0,0,0,0.03);"><th style="padding:10px; text-align:left;">Data</th><th style="padding:10px; text-align:left;">Descrição</th></tr></thead>
                        <tbody>
                            <?php 
                            $hist = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id=? ORDER BY data_movimento DESC");
                            $hist->execute([$cliente_ativo['id']]);
                            foreach($hist->fetchAll() as $h): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="padding:15px; color:var(--color-primary); font-weight:bold; white-space:nowrap; vertical-align:top;">
                                        <?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?>
                                    </td>
                                    <td style="padding:15px;">
                                        <div style="font-weight:bold; margin-bottom:5px; color:#212529;"><?= htmlspecialchars($h['titulo_fase']) ?></div>
                                        <?php 
                                            // Lógica de exibição de comentários estilizados
                                            $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                                            $sys_desc = nl2br(htmlspecialchars($parts[0]));
                                            echo "<div style='color:var(--color-text-subtle);'>{$sys_desc}</div>";
                                            
                                            // Se tiver comentário do usuário
                                            if (count($parts) > 1) {
                                                $user_comment = nl2br(htmlspecialchars($parts[1]));
                                                echo "<div style='margin-top:8px; border-left: 3px solid #d32f2f; padding-left:10px;'>
                                                        <span style='font-weight:800; color:black;'>Comentário Diego Vilela:</span>
                                                        <div style='color:#d32f2f; font-weight:bold; margin-top:2px;'>{$user_comment}</div>
                                                      </div>";
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif($active_tab == 'pendencias'): ?>
                <div class="form-card" style="border-left: 6px solid #ffc107;">
                    <h3>⚠️ Quadro de Pendências</h3>
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-group">
                            <label>Texto descritivo das pendências (Cliente visualizará isso)</label>
                            <textarea name="texto_pendencias" rows="12" style="background:#fffbf2; border:1px solid #ffeeba;"><?= htmlspecialchars($detalhes['texto_pendencias']??'') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Link Pasta Pendências (Drive)</label>
                            <input type="text" name="link_doc_pendencias" value="<?= $detalhes['link_doc_pendencias']??'' ?>">
                        </div>
                        <button type="submit" name="btn_salvar_pendencias" class="btn-save" style="background:#d97706;">Salvar Pendências</button>
                    </form>
                </div>

            <?php elseif($active_tab == 'arquivos'): ?>
                <div class="form-card" style="border-left: 6px solid #2196f3;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h3 style="color:#1976d2;">📂 Arquivos do Cliente</h3>
                            <p style="margin-bottom:20px; color:var(--color-text-subtle);">Central de links e pastas do Google Drive.</p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-group">
                            <label>🔗 Link da Pasta Geral (Backup/Drive)</label>
                            <input type="text" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" placeholder="https://drive.google.com/...">
                        </div>
                        <button type="submit" name="btn_salvar_arquivos" class="btn-save" style="background:#1976d2;">Salvar Links</button>
                    </form>

                    <?php 
                    if(!empty($detalhes['link_drive_pasta'])): 
                        // Tenta extrair o ID da pasta para formato embed correto
                        $drive_url = $detalhes['link_drive_pasta'];
                        $embed_url = $drive_url; // fallback
                        
                        // Padrão: /folders/ID ou ?id=ID
                        if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                            $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
                        } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                             $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
                        }
                    ?>
                        <div class="iframe-container visible" style="display:block;">
                            <!-- Aviso sobre permissões -->
                            <div style="background:#e3f2fd; color:#0d47a1; padding:10px; font-size:0.85rem; text-align:center; border-bottom:1px solid #bbdefb;">
                                💡 Se aparecer erro 403/Recusado, verifique se a conta atual tem permissão na pasta.
                            </div>
                            <iframe src="<?= htmlspecialchars($embed_url) ?>" width="100%" height="100%" frameborder="0" style="border:0;"></iframe>
                        </div>
                    <?php endif; ?>
                </div>


            <?php elseif($active_tab == 'financeiro'): ?>
                <!-- ConfigFinanceiro -->
                <div class="form-card" style="border-left: 6px solid #28a745; background:#f0fff4;">
                    <h3 style="color:#28a745;">📂 Pasta de Comprovantes/Pagamentos</h3>
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-group">
                            <label>Link da Pasta (Google Drive) para Cliente ver Boletos/Comprovantes</label>
                            <div style="display:flex; gap:10px;">
                                <input type="text" name="link_pasta_pagamentos" value="<?= $detalhes['link_pasta_pagamentos']??'' ?>" placeholder="https://drive.google.com/..." style="flex:1;">
                                <button type="submit" name="btn_salvar_dados_financeiros" class="btn-save" style="margin:0; width:auto; padding:10px 20px; background:#198754;">Salvar Link</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Form de Adição -->
                <div class="form-card">
                    <h3>➕ Novo Lançamento Financeiro</h3>
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" name="descricao" required placeholder="Ex: Taxa de Habite-se">
                            </div>
                            <div class="form-group">
                                <label>Categoria</label>
                                <select name="categoria" required>
                                    <option value="honorarios">Honorários (Vilela Engenharia)</option>
                                    <option value="taxas">Taxas e Multas (Governo/Prefeitura)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Valor (R$)</label>
                                <input type="number" step="0.01" name="valor" required placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Vencimento</label>
                                <input type="date" name="data_vencimento" required>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="pendente">⏳ Pendente</option>
                                    <option value="pago">✅ Pago</option>
                                    <option value="atrasado">❌ Atrasado</option>
                                    <option value="isento">⚪ Isento</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Link Comprovante/Boleto (Opcional)</label>
                                <input type="text" name="link_comprovante" placeholder="https://...">
                            </div>
                        </div>
                        <button type="submit" name="btn_salvar_financeiro" class="btn-save" style="background:#28a745;">Adicionar Lançamento</button>
                    </form>
                </div>

                <!-- Tabelas -->
                <?php 
                try {
                    // Verifica se tabela existe (silencioso) ou só roda
                    $fin_honorarios = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='honorarios' ORDER BY data_vencimento ASC");
                    $fin_honorarios->execute([$cliente_ativo['id']]);
                    
                    $fin_taxas = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='taxas' ORDER BY data_vencimento ASC");
                    $fin_taxas->execute([$cliente_ativo['id']]);

                    function renderFinTable($stmt, $title, $color, $cid) {
                        $rows = $stmt->fetchAll();
                        echo "<div class='form-card' style='border-left: 6px solid $color;'>
                                <h3 style='color:$color;'>$title</h3>";
                        
                        if(count($rows) == 0) {
                            echo "<p style='color:#666; font-style:italic;'>Nenhum lançamento encontrado nesta categoria.</p>";
                        } else {
                            echo "<div style='overflow-x:auto;'>
                                  <table style='width:100%; border-collapse:collapse; font-size:0.95rem; min-width:600px;'>
                                    <thead><tr style='background:#f8f9fa; border-bottom:2px solid #dee2e6;'>
                                        <th style='padding:12px; text-align:left;'>Descrição</th>
                                        <th style='padding:12px; text-align:left;'>Valor</th>
                                        <th style='padding:12px; text-align:left;'>Vencimento</th>
                                        <th style='padding:12px; text-align:center;'>Status</th>
                                        <th style='padding:12px; text-align:center;'>Ação</th>
                                        <th style='padding:12px;'></th>
                                    </tr></thead><tbody>";
                            foreach($rows as $r) {
                                $st_color = 'black';
                                $st_icon = '';
                                switch($r['status']){
                                    case 'pago': $st_color='#198754'; $st_icon='✅ Pago'; break;
                                    case 'pendente': $st_color='#ffc107'; $st_icon='⏳ Pendente'; break;
                                    case 'atrasado': $st_color='#dc3545'; $st_icon='❌ Atrasado'; break;
                                    default: $st_icon=$r['status'];
                                }
                                $valor = number_format($r['valor'], 2, ',', '.');
                                $data = date('d/m/Y', strtotime($r['data_vencimento']));
                                $link = $r['link_comprovante'] ? "<a href='{$r['link_comprovante']}' target='_blank' style='color:white; background:#0d6efd; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;'>📄 Ver Doc</a>" : "<span style='opacity:0.5'>--</span>";
                                
                                echo "<tr style='border-bottom:1px solid #eee;'>
                                        <td style='padding:12px;'>{$r['descricao']}</td>
                                        <td style='padding:12px; font-weight:bold;'>R$ {$valor}</td>
                                        <td style='padding:12px;'>{$data}</td>
                                        <td style='padding:12px; text-align:center; color:{$st_color}; font-weight:bold;'>{$st_icon}</td>
                                        <td style='padding:12px; text-align:center;'>{$link}</td>
                                        <td style='padding:12px; text-align:right;'>
                                            <a href='?cliente_id={$cid}&tab=financeiro&del_fin={$r['id']}' onclick='return confirm(\"Tem certeza que deseja EXCLUIR este lançamento financeiro?\")' style='color:#dc3545; text-decoration:none; font-size:1.1rem;'>🗑️</a>
                                        </td>
                                      </tr>";
                            }
                            echo "</tbody></table></div>";
                        }
                        echo "</div>";
                    }

                    renderFinTable($fin_honorarios, "💰 Honorários e Serviços (Vilela Engenharia)", "#2196f3", $cliente_ativo['id']);
                    renderFinTable($fin_taxas, "🏛️ Taxas e Multas Governamentais", "#efb524", $cliente_ativo['id']);

                } catch (Exception $e) {
                    echo "<div style='color:red'>Erro ao carregar dados financeiros. Verifique se o Setup de Banco de Dados foi rodado. <br>". $e->getMessage() ."</div>";
                }
                ?>
            <?php endif; ?>

        <?php endif; ?>
    </main>
</div>
</body>
</html>
