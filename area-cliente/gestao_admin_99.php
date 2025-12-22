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

// Lógica do Popup de Boas Vindas (Apenas 1x por sessão)
$show_welcome_popup = false;
if (!isset($_SESSION['welcome_shown'])) {
    $show_welcome_popup = true;
    $_SESSION['welcome_shown'] = true;
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

// 8. Importar Pré-Cadastro (Aprovar)
if (isset($_GET['aprovar_cadastro'])) {
    $pid = $_GET['aprovar_cadastro'];
    try {
        $pre = $pdo->query("SELECT * FROM pre_cadastros WHERE id=$pid")->fetch();
        if ($pre) {
            // Cria Cliente
            $nome = $pre['nome'];
            $user_sugerido = strtolower(explode(' ', trim($nome))[0]) . rand(100,999);
            $pass_padrao = password_hash("Mudar123", PASSWORD_DEFAULT); // Senha temporaria
            
            $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)")->execute([$nome, $user_sugerido, $pass_padrao]);
            $nid = $pdo->lastInsertId();
            
            // Renomeia
            $nome_final = sprintf("Cliente %03d - %s", $nid, $nome);
            $pdo->prepare("UPDATE clientes SET nome = ? WHERE id = ?")->execute([$nome_final, $nid]);
            
            // Cria Detalhes e popula com o que veio
            $sql_det = "INSERT INTO processo_detalhes (cliente_id, cpf_cnpj, contato_email, contato_tel, endereco_imovel, profissao) VALUES (?, ?, ?, ?, ?, ?)";
            // Usamos 'profissao' para guardar tipo servico temp, ou observacao
            $pdo->prepare($sql_det)->execute([$nid, $pre['cpf_cnpj'], $pre['email'], $pre['telefone'], $pre['endereco_obra'], $pre['tipo_servico']]);

            // Atualiza status do pré
            $pdo->query("UPDATE pre_cadastros SET status='aprovado' WHERE id=$pid");
            
            $sucesso = "Cadastro importado com sucesso! Cliente criado: $nome_final (Login: $user_sugerido / Senha: Mudar123)";
        }
    } catch (Exception $e) { $erro = "Erro ao importar: " . $e->getMessage(); }
}

// 9. Exportar Relatório (Exaustivo e Profissional)
if (isset($_GET['exportar_cliente'])) {
    $cid = $_GET['exportar_cliente'];
    $c = $pdo->query("SELECT * FROM clientes WHERE id=$cid")->fetch();
    $d = $pdo->query("SELECT * FROM processo_detalhes WHERE cliente_id=$cid")->fetch();
    $f = $pdo->query("SELECT * FROM processo_financeiro WHERE cliente_id=$cid ORDER BY data_vencimento ASC")->fetchAll();
    $h = $pdo->query("SELECT * FROM processo_movimentos WHERE cliente_id=$cid ORDER BY data_movimento DESC")->fetchAll();
    
    // Totais Financeiros
    $total_hon = 0; $total_taxas = 0; $total_pago = 0; $total_pendente = 0;
    foreach($f as $item) {
        if($item['categoria']=='honorarios') $total_hon += $item['valor'];
        else $total_taxas += $item['valor'];
        
        if($item['status']=='pago') $total_pago += $item['valor'];
        elseif($item['status']=='pendente' || $item['status']=='atrasado') $total_pendente += $item['valor'];
    }

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Dossuiê Técnico - <?= htmlspecialchars($c['nome']) ?></title>
        <style>
            @page { size: A4; margin: 20mm; }
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.4; font-size: 12px; }
            .header { border-bottom: 2px solid #146c43; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
            .header img { height: 50px; }
            .header-info { text-align: right; color: #555; }
            h1 { font-size: 24px; color: #146c43; margin: 0; text-transform: uppercase; font-weight: 800; }
            h2 { font-size: 16px; color: #146c43; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
            
            .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
            .data-row { margin-bottom: 6px; border-bottom: 1px dotted #eee; padding-bottom: 2px; }
            .data-label { font-weight: bold; color: #666; width: 140px; display: inline-block; }
            .data-value { font-weight: 600; color: #000; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
            th { background: #f3f3f3; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; font-weight: bold; color: #444; }
            td { padding: 8px; border-bottom: 1px solid #eee; }
            
            .status-badge { padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; text-transform: uppercase; }
            .st-pago { background: #d1e7dd; color: #0f5132; }
            .st-pend { background: #fff3cd; color: #856404; }
            .st-atra { background: #f8d7da; color: #842029; }
            
            .box-summary { background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 6px; margin-top: 20px; display: flex; justify-content: space-around; }
            .sum-item { text-align: center; }
            .sum-res { font-size: 16px; font-weight: bold; color: #146c43; display: block; margin-top: 5px; }
            
            .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 15px; text-align: center; font-size: 10px; color: #888; }
        </style>
    </head>
    <body onload="window.print()">

        <div class="header">
            <div>
                <h1>Resumo do Processo</h1>
                <div style="font-size: 14px; margin-top: 5px;">Relatório Técnico Administrativo</div>
            </div>
            <div class="header-info">
                <strong>Vilela Engenharia</strong><br>
                Eng. Diego Vilela (CREA-MG 235474/D)<br>
                Gerado em: <?= date('d/m/Y \à\s H:i') ?>
            </div>
        </div>

        <!-- 1. IDENTIFICAÇÃO -->
        <div class="two-col">
            <div>
                <h2>1. Identificação do Cliente</h2>
                <div class="data-row"><span class="data-label">Nome Completo:</span> <span class="data-value"><?= htmlspecialchars($c['nome']) ?></span></div>
                <div class="data-row"><span class="data-label">CPF / CNPJ:</span> <span class="data-value"><?= $d['cpf_cnpj']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">RG / IE:</span> <span class="data-value"><?= $d['rg_ie']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Estado Civil:</span> <span class="data-value"><?= $d['estado_civil']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Profissão:</span> <span class="data-value"><?= $d['profissao']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Endereço Real:</span> <span class="data-value"><?= $d['endereco_residencial']??'--' ?></span></div>
            </div>
            <div>
                <h2>2. Contato e Acesso</h2>
                <div class="data-row"><span class="data-label">Email:</span> <span class="data-value"><?= $d['contato_email']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Telefone:</span> <span class="data-value"><?= $d['contato_tel']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Usuário Sistema:</span> <span class="data-value"><?= htmlspecialchars($c['usuario']) ?></span></div>
                <div class="data-row"><span class="data-label">ID Sistema:</span> <span class="data-value">#<?= $c['id'] ?></span></div>
            </div>
        </div>

        <!-- 2. DADOS TÉCNICOS -->
        <div class="two-col" style="margin-top: 20px;">
            <div>
                <h2>3. Dados do Imóvel/Obra</h2>
                <div class="data-row"><span class="data-label">Endereço Obra:</span> <span class="data-value"><?= $d['endereco_imovel']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Matrícula:</span> <span class="data-value"><?= $d['num_matricula']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Insc. Imob.:</span> <span class="data-value"><?= $d['inscricao_imob']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Área Terreno:</span> <span class="data-value"><?= $d['area_terreno']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Área Construída:</span> <span class="data-value"><?= $d['area_construida']??'--' ?></span></div>
            </div>
            <div>
                <h2>4. Responsabilidade Técnica</h2>
                <div class="data-row"><span class="data-label">Resp. Técnico:</span> <span class="data-value"><?= $d['resp_tecnico']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Registro (CAU/CREA):</span> <span class="data-value"><?= $d['registro_prof']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">ART / RRT:</span> <span class="data-value"><?= $d['num_art_rrt']??'--' ?></span></div>
                <div class="data-row"><span class="data-label">Tipo Resp.:</span> <span class="data-value"><?= $d['tipo_responsavel']??'--' ?></span></div>
            </div>
        </div>

        <!-- 3. STATUS ATUAL -->
        <h2>5. Status do Processo</h2>
        <div style="background: #e9ecef; padding: 15px; border-radius: 6px; font-size: 14px;">
            <strong>Fase Atual:</strong> <?= htmlspecialchars($d['etapa_atual']??'Não iniciado') ?>
        </div>
        <?php if(!empty($d['texto_pendencias'])): ?>
            <div style="margin-top:10px; border:1px solid #ffc107; background:#fffbf2; padding:10px; border-radius:4px;">
                <strong>⚠️ Pendências Ativas:</strong><br>
                <?= nl2br(htmlspecialchars($d['texto_pendencias'])) ?>
            </div>
        <?php endif; ?>

        <!-- 4. FINANCEIRO -->
        <h2>6. Relatório Financeiro Detalhado</h2>
        <table>
            <thead>
                <tr>
                    <th>Data Venc.</th>
                    <th>Categoria</th>
                    <th>Descrição</th>
                    <th>Valor (R$)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($f)==0): ?><tr><td colspan="5">Nenhum registro financeiro.</td></tr><?php endif; ?>
                <?php foreach($f as $fin): 
                    $cls = '';
                    if($fin['status']=='pago')$cls='st-pago';
                    elseif($fin['status']=='atrasado')$cls='st-atra';
                    else $cls='st-pend';
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($fin['data_vencimento'])) ?></td>
                    <td><?= ucfirst($fin['categoria']) ?></td>
                    <td><?= $fin['descricao'] ?></td>
                    <td>R$ <?= number_format($fin['valor'], 2, ',', '.') ?></td>
                    <td><span class="status-badge <?= $cls ?>"><?= strtoupper($fin['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="box-summary">
            <div class="sum-item">Total Honorários<span class="sum-res">R$ <?= number_format($total_hon, 2, ',', '.') ?></span></div>
            <div class="sum-item">Total Taxas<span class="sum-res">R$ <?= number_format($total_taxas, 2, ',', '.') ?></span></div>
            <div class="sum-item">Total Pago<span class="sum-res">R$ <?= number_format($total_pago, 2, ',', '.') ?></span></div>
            <div class="sum-item">Pendente<span class="sum-res" style="color:#d32f2f;">R$ <?= number_format($total_pendente, 2, ',', '.') ?></span></div>
        </div>

        <!-- 5. HISTÓRICO -->
        <h2>7. Histórico Completo de Movimentações</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 120px;">Data/Hora</th>
                    <th>Movimento / Fase</th>
                    <th>Detalhes / Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($h)==0): ?><tr><td colspan="3">Nenhum histórico registrado.</td></tr><?php endif; ?>
                <?php foreach($h as $hist): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($hist['data_movimento'])) ?></td>
                    <td><strong><?= $hist['titulo_fase'] ?></strong></td>
                    <td><?= nl2br(str_replace('||COMENTARIO_USER||', '<br><strong>Obs:</strong> ', htmlspecialchars($hist['descricao']))) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            Vilela Engenharia & Consultoria - Documento gerado automaticamente pelo Sistema de Gestão.<br>
            Este relatório reflete a posição do banco de dados na data e hora da emissão.
        </div>

    </body>
    </html>
    <?php
    exit;
}

// --- Consultas Iniciais e Dashboard Data ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
$cliente_ativo = null;
$detalhes = [];

// Dados para Dashboard
try {
    // 1. Total Clientes
    $kpi_total_clientes = count($clientes);

    // 2. Pré-Cadastros Pendentes
    $stmt_pre = $pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'");
    $kpi_pre_pendentes = $stmt_pre ? $stmt_pre->fetchColumn() : 0;

    // 3. Financeiro Pendente (Soma Global)
    $stmt_fin = $pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status IN ('pendente', 'atrasado')");
    $kpi_fin_pendente = $stmt_fin ? $stmt_fin->fetchColumn() : 0;
    
    // 4. Processos Ativos (Não finalizados)
    $stmt_proc = $pdo->query("SELECT COUNT(*) FROM processo_detalhes WHERE etapa_atual != 'Processo Finalizado (Documentos Prontos)' AND etapa_atual IS NOT NULL AND etapa_atual != ''");
    $kpi_proc_ativos = $stmt_proc ? $stmt_proc->fetchColumn() : 0;

} catch (Exception $e) {
    // Silencia erro se tabelas não existirem ainda
    $kpi_total_clientes = 0; $kpi_pre_pendentes = 0; $kpi_fin_pendente = 0; $kpi_proc_ativos = 0;
}

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

        .form-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--shadow); position: relative; overflow: hidden; }
        .form-card::before { content: ''; position: absolute; top:0; left:0; width: 4px; height: 100%; background: var(--color-primary); opacity: 0.5; }
        .form-card h3 { margin-top: 0; color: var(--color-primary); font-size: 1.15rem; font-weight: 700; border-bottom: 1px solid var(--color-border); padding-bottom: 10px; margin-bottom: 20px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; }
        .form-group { margin-bottom:15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--color-text-subtle); margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; border: 1px solid var(--color-border); background: var(--color-bg); color: var(--color-text); border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem; }
        .form-group input:focus { border-color: var(--color-primary); outline: none; background: var(--color-surface); }

        .btn-save { background: var(--color-primary); color: white; padding: 10px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; width: auto; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: inline-flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-save:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-save.full-mobile { width: auto; }
        
        /* Cores de Botões */
        .btn-primary { background: var(--color-primary); }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-info { background: #0dcaf0; color: #fff; }
        .btn-success { background: #198754; }
        .btn-secondary { background: #6c757d; color: white; }

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
            .admin-container { grid-template-columns: 1fr; margin: 15px auto; padding: 0 15px; gap: 15px; }
            .sidebar { position: static; margin-bottom: 20px; padding: 15px; }
            .admin-header { padding: 0.8rem 1rem; flex-direction: column; gap: 10px; align-items: flex-start; }
            .admin-header > div { width: 100%; justify-content: space-between; }
            .form-card { padding: 15px; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            .btn-save.full-mobile { width: 100%; }
        }
        
        .iframe-container.visible { display:block; }
        
        .sidebar-menu { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--color-border); padding-bottom: 20px; }
        .btn-menu { display: block; padding: 12px 15px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.95rem; color: var(--color-text); transition: 0.2s; border: 1px solid transparent; display:flex; align-items:center; gap:10px; }
        .btn-menu:hover { background: var(--color-surface); border-color: var(--color-border); transform: translateX(5px); }
        .btn-menu.active { background: var(--color-primary-light); color: var(--color-primary); }
        .btn-menu-primary { background: var(--color-primary); color: white !important; }
        .btn-menu-primary:hover { filter: brightness(1.1); }
    </style>
</head>
<body>

<header class="admin-header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <img src="../assets/logo.png" alt="Logo" style="height: 50px;">
        <div style="display:flex; flex-direction:column; gap:4px;">
            <a href="gestao_admin_99.php" style="text-decoration:none; color:inherit;">
                <h1 style="margin:0; font-size:1.3rem; font-weight:700;">Gestão Administrativa (Vilela Engenharia)</h1>
            </a>
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
        <nav class="sidebar-menu">
            <a href="gestao_admin_99.php" class="btn-menu <?= (!isset($_GET['cliente_id']) && !isset($_GET['novo']) && !isset($_GET['importar'])) ? 'active' : '' ?>">
                🏠 Página Inicial
            </a>
            
            <div style="margin: 10px 0; border-top: 1px solid #eee; padding-top: 10px;">
                <label style="font-size: 0.75rem; text-transform: uppercase; color: #999; font-weight: bold; padding-left: 10px; margin-bottom: 5px; display: block;">Gestão</label>
                <a href="?novo=true" class="btn-menu <?= (isset($_GET['novo'])) ? 'active' : '' ?>" style="color: var(--color-primary); background: rgba(20, 108, 67, 0.05); border: 1px solid rgba(20, 108, 67, 0.1);">
                    ➕ Novo Cliente
                </a>
                <a href="?importar=true" class="btn-menu <?= (isset($_GET['importar'])) ? 'active' : '' ?>">
                    📥 Importar Cadastro
                </a>
            </div>
        </nav>

        <h4 style="margin: 10px 0; color: var(--color-text-subtle); display:flex; align-items:center; gap:8px;">📂 Clientes</h4>
        <ul class="client-list" style="list-style:none; padding:0; max-height:500px; overflow-y:auto;">
            <?php foreach($clientes as $c): ?>
                <li><a href="?cliente_id=<?= $c['id'] ?>" class="<?= ($cliente_ativo && $cliente_ativo['id'] == $c['id']) ? 'active' : '' ?>"><?= htmlspecialchars($c['nome']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main>
        <?php if(isset($sucesso)): ?><div style="background:#d1e7dd; color:#0f5132; padding:15px; margin-bottom:20px; border-radius:8px;"><?= $sucesso ?></div><?php endif; ?>
        <?php if(isset($erro)): ?><div style="background:#f8d7da; color:#842029; padding:15px; margin-bottom:20px; border-radius:8px;"><?= $erro ?></div><?php endif; ?>

        <?php if(isset($_GET['importar'])): ?>
            <div class="form-card">
                <h2>Importar Cadastros do Site</h2>
                <p>Abaixo estão as solicitações de cadastro vindas da página pública.</p>
                <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                    <thead><tr style="background:#eee;"><th style="padding:10px;">Data</th><th style="padding:10px;">Nome</th><th style="padding:10px;">Contato</th><th style="padding:10px;">Serviço</th><th style="padding:10px;">Ação</th></tr></thead>
                    <tbody>
                        <?php 
                        try {
                            $pendentes = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC")->fetchAll();
                            if(count($pendentes) == 0) echo "<tr><td colspan='5' style='padding:20px; text-align:center;'>Nenhuma solicitação pendente.</td></tr>";
                            foreach($pendentes as $p): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:10px;"><?= date('d/m/Y H:i', strtotime($p['data_solicitacao'])) ?></td>
                                <td style="padding:10px;"><strong><?= htmlspecialchars($p['nome']) ?></strong><br><small><?= $p['cpf_cnpj'] ?></small></td>
                                <td style="padding:10px;"><?= $p['telefone'] ?><br><small><?= $p['email'] ?></small></td>
                                <td style="padding:10px;"><?= $p['tipo_servico'] ?></td>
                                <td style="padding:10px; text-align:center;">
                                    <a href="?aprovar_cadastro=<?= $p['id'] ?>" class="btn-save btn-success" style="padding:5px 10px; font-size:0.8rem; text-decoration:none;">✅ Aprovar</a>
                                </td>
                            </tr>
                        <?php endforeach; 
                        } catch(Exception $e) { echo "<tr><td colspan='5'>Erro: Rode o setup_cadastro_db.php</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php elseif(isset($_GET['novo'])): ?>
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
                    <a href="?exportar_cliente=<?= $cliente_ativo['id'] ?>" target="_blank" class="btn-save btn-secondary" style="text-decoration:none; margin-top:10px; margin-right:10px;">
                       📄 Resumo do Processo
                    </a>
                    <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" onclick="return confirm('ATENÇÃO EXTREMA!\n\nVocê tem certeza absoluta que deseja EXCLUIR este cliente?\n\nEssa ação apagará todo o histórico e dados permanentemente.')" 
                       class="btn-save btn-danger" style="text-decoration:none; margin-top:10px;">
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

                                    <button type="submit" name="btn_salvar_acesso" class="btn-save btn-warning" style="color:black; margin:0; padding: 10px 20px; white-space:nowrap; width:auto; height:auto;">Salvar Acesso</button>
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
                     <button type="button" onclick="toggleEditMode()" class="btn-save btn-secondary" style="width:auto;">🔓 Liberar Edição</button>
                     <button type="submit" form="form_dados_detalhados" name="btn_salvar_cadastro" id="btn_salvar_dados" class="btn-save btn-success" style="display:none;">Salvar Detalhes Cadastrais</button>
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

                        <button type="submit" name="btn_salvar_pendencias" class="btn-save btn-warning" style="color:#000;">Salvar Pendências</button>
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

                        <button type="submit" name="btn_salvar_arquivos" class="btn-save btn-info">Salvar Links</button>
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
                                <button type="submit" name="btn_salvar_dados_financeiros" class="btn-save btn-success" style="margin:0;">Salvar Link</button>
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
                            <div class="form-group">
                                <label>Link Comprovante/Boleto (Opcional)</label>
                                <input type="text" name="link_comprovante" placeholder="https://...">
                            </div>
                        </div>
                        <button type="submit" name="btn_salvar_financeiro" class="btn-save btn-success">Adicionar Lançamento</button>
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

        <?php else: ?>
            
            <!-- DASHBOARD GERAL (Visão do Gestor) -->
            <div style="margin-bottom:30px;">
                <h2 style="color:var(--color-primary); margin-bottom:10px;">Visão Geral do Escritório</h2>
                <p style="color:var(--color-text-subtle);">Resumo de atividades e indicadores de performance.</p>
            </div>

            <!-- KPI Cards -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:40px;">
                
                <div class="form-card" style="padding:20px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; border-left:5px solid #2196f3;">
                    <div style="font-size:2.5rem; margin-bottom:10px;">👥</div>
                    <div style="font-size:2rem; font-weight:800; color:#2196f3;"><?= $kpi_total_clientes ?></div>
                    <div style="color:#666; font-weight:600;">Clientes Cadastrados</div>
                </div>

                <div class="form-card" style="padding:20px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; border-left:5px solid #efb524;">
                    <div style="font-size:2.5rem; margin-bottom:10px;">🏗️</div>
                    <div style="font-size:2rem; font-weight:800; color:#efb524;"><?= $kpi_proc_ativos ?></div>
                    <div style="color:#666; font-weight:600;">Obras em Andamento</div>
                </div>

                <div class="form-card" style="padding:20px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; border-left:5px solid #dc3545;">
                    <div style="font-size:2.5rem; margin-bottom:10px;">📥</div>
                    <div style="font-size:2rem; font-weight:800; color:#dc3545;"><?= $kpi_pre_pendentes ?></div>
                    <div style="color:#666; font-weight:600;">Solicitações Web</div>
                    <?php if($kpi_pre_pendentes > 0): ?>
                        <a href="?importar=true" class="btn-save btn-danger" style="margin-top:10px; padding:5px 15px; font-size:0.8rem; width:auto;">Ver Pendentes</a>
                    <?php endif; ?>
                </div>

                <div class="form-card" style="padding:20px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; border-left:5px solid #198754;">
                    <div style="font-size:2.5rem; margin-bottom:10px;">💰</div>
                    <div style="font-size:2rem; font-weight:800; color:#198754;">R$ <?= number_format($kpi_fin_pendente, 2, ',', '.') ?></div>
                    <div style="color:#666; font-weight:600;">Recebíveis Pendentes</div>
                </div>

            </div>

            <!-- Tabela Geral de Clientes -->
            <div class="form-card">
                <h3>📋 Situação da Carteira de Clientes</h3>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; margin-top:15px;">
                        <thead>
                            <tr style="background:#f8f9fa; border-bottom:2px solid #ddd;">
                                <th style="padding:12px; text-align:left;">Cliente</th>
                                <th style="padding:12px; text-align:left;">Fase Atual</th>
                                <th style="padding:12px; text-align:left;">Contato</th>
                                <th style="padding:12px; text-align:center;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $c): 
                                // Busca detalhes rápidos (poderia ser otimizado com JOIN, mas mantendo simples)
                                $dt = $pdo->query("SELECT etapa_atual, contato_tel FROM processo_detalhes WHERE cliente_id={$c['id']}")->fetch();
                                $etapa = $dt['etapa_atual'] ?? '<span style="color:#ccc; font-style:italic;">Não iniciado</span>';
                                $tel = $dt['contato_tel'] ?? '--';
                            ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px; font-weight:bold; color:var(--color-primary);"><?= htmlspecialchars($c['nome']) ?></td>
                                <td style="padding:12px;"><?= $etapa ?></td>
                                <td style="padding:12px;"><?= $tel ?></td>
                                <td style="padding:12px; text-align:center;">
                                    <a href="?cliente_id=<?= $c['id'] ?>" class="btn-save btn-info" style="padding:5px 10px; font-size:0.85rem; text-decoration:none;">Gerenciar ➡️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </main>
</div>
</body>
<!-- Welcome Popup -->
<?php if($show_welcome_popup): ?>
<div id="welcomeRunning" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; justify-content:center; align-items:center; opacity:0; pointer-events:none; transition: opacity 0.5s ease;">
    <div style="background:white; padding:40px; border-radius:16px; width:90%; max-width:400px; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,0.2); transform: translateY(20px); transition: transform 0.5s ease;">
        <div style="font-size:3rem; margin-bottom:15px;">👷‍♂️</div>
        <h2 style="color:var(--color-primary); margin:0 0 10px 0;">Bem-vindo, Eng. Diego!</h2>
        <p style="color:var(--color-text-subtle); margin-bottom:25px; line-height:1.5;">O Painel Administrativo está pronto para uso.<br>Bom trabalho hoje!</p>
        <button onclick="closeWelcome()" class="btn-save" style="margin:0; width:100%;">Iniciar Gestão</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('welcomeRunning');
        const card = popup.querySelector('div');
        
        // Show
        setTimeout(() => {
            popup.style.opacity = '1';
            popup.style.pointerEvents = 'all';
            card.style.transform = 'translateY(0)';
        }, 100);

        window.closeWelcome = function() {
            popup.style.opacity = '0';
            popup.style.pointerEvents = 'none';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => { popup.remove(); }, 500);
        }
    });
</script>
<?php endif; ?>
</html>
