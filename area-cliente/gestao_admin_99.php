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

    // Busca Detalhes
    $stmt = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
    $stmt->execute([$id_selecionado]);
    $detalhes = $stmt->fetch();
    if(!$detalhes) $detalhes = []; // Evita erros se vazio

    // Documentos
    $stmt = $pdo->prepare("SELECT * FROM documentos WHERE cliente_id = ? ORDER BY id DESC");
    $stmt->execute([$id_selecionado]);
    $docs_ativo = $stmt->fetchAll();
}

// Controle de Aba Ativa
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'requerente';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão Completo | Vilela Engenharia</title>
    <!-- Fontes e CSS mantidos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <style>
        /* CSS Admin Base (Reciclado e Melhorado) */
        body { background-color: #f4f7f6; display: block; padding: 0; }
        .admin-header { background: var(--color-primary-strong); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .admin-container { display: grid; grid-template-columns: 260px 1fr; gap: 20px; max-width: 1600px; margin: 20px auto; padding: 0 20px; align-items: start; }
        
        .sidebar { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 15px; }
        .client-list { list-style: none; padding: 0; margin: 0; max-height: 70vh; overflow-y: auto; }
        .client-list li a { display: block; padding: 10px; border-radius: 6px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
        .client-list li a:hover { background: #e6f2ee; }
        .client-list li a.active { background: var(--color-primary); color: white; border-color: transparent; }

        /* Abas */
        .tabs-header { display: flex; gap: 5px; margin-bottom: 0; border-bottom: 1px solid #ddd; }
        .tab-btn {
            padding: 10px 20px;
            background: #e0e0e0;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 600;
            color: #555;
            text-decoration: none;
            display: inline-block;
        }
        .tab-btn.active { background: white; color: var(--color-primary-strong); border: 1px solid #ddd; border-bottom: 1px solid white; margin-bottom: -1px; }

        .tab-content { background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 8px 8px 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); min-height: 500px; }
        
        /* Formulários */
        .form-section-title { font-size: 1.1rem; color: var(--color-primary); border-bottom: 2px solid #eee; padding-bottom: 5px; margin: 20px 0 15px 0; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: bold; color: #666; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        
        .btn-save { background: var(--color-primary); color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; margin-top: 20px; }
        .btn-save:hover { background: var(--color-primary-strong); }

        /* Alertas e Badges */
        .alert-validacao { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 0.9rem; border: 1px solid #ffeeba; }
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
        <a href="?novo=true" style="display:block; text-align:center; background:#efb524; padding:10px; border-radius:6px; color:black; font-weight:bold; text-decoration:none; margin-bottom:15px;">+ Novo Cliente</a>
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
            <div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:6px;"><?= $sucesso ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['novo'])): ?>
            <!-- Formulário Novo Cliente (Simples) -->
            <div class="tab-content" style="border-radius: 8px;">
                <h2>Cadastrar Novo Cliente</h2>
                <form method="POST">
                    <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
                    <div class="form-group"><label>Usuário (CPF/Email)</label><input type="text" name="usuario" required></div>
                    <div class="form-group"><label>Senha Inicial</label><input type="text" name="senha" required></div>
                    <button type="submit" name="novo_cliente" class="btn-save">Cadastrar</button>
                </form>
            </div>

        <?php elseif($cliente_ativo): ?>
            
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between;">
                <h1 style="margin: 0; color: #333;"><?= htmlspecialchars($cliente_ativo['nome']) ?> <span style="font-size: 0.6em; color: #777;">(ID: <?= $cliente_ativo['id'] ?>)</span></h1>
                <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" onclick="return confirm('ATENÇÃO: Isso apaga TUDO deste cliente. Confirmar?')" style="color:red; font-size:0.8rem;">Excluir Cliente</a>
            </div>

            <!-- Navegação de Abas -->
            <div class="tabs-header">
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=requerente" class="tab-btn <?= $active_tab=='requerente'?'active':'' ?>">🧑 Requerente</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=imovel" class="tab-btn <?= $active_tab=='imovel'?'active':'' ?>">🏠 Lote e Imóvel</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=engenharia" class="tab-btn <?= $active_tab=='engenharia'?'active':'' ?>">📐 Engenharia</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="tab-btn <?= $active_tab=='financeiro'?'active':'' ?>">💰 Financeiro</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=timeline" class="tab-btn <?= $active_tab=='timeline'?'active':'' ?>">🚦 Status (Timeline)</a>
            </div>

            <div class="tab-content">
                
                <?php if($active_tab == 'timeline'): ?>
                    <!-- ABA 5: TIMELINE (Lógica antiga de Movimentos) -->
                    <h3>Linha do Tempo e Status</h3>
                    
                    <form method="POST" style="background:#fafafa; padding:20px; border:1px solid #eee; border-radius:6px; margin-bottom:20px;">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-grid">
                            <div class="form-group"><label>Título da Fase</label><input type="text" name="titulo" required></div>
                            <div class="form-group"><label>Data</label><input type="datetime-local" name="data" value="<?= date('Y-m-d\TH:i') ?>"></div>
                            <div class="form-group"><label>Status</label>
                                <select name="status_tipo">
                                    <option value="tramite">Trâmite</option>
                                    <option value="inicio">Início</option>
                                    <option value="pendencia">Pendência</option>
                                    <option value="conclusao">Conclusão</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid" style="margin-top:10px;">
                            <div class="form-group"><label>Origem</label><input type="text" name="origem"></div>
                            <div class="form-group"><label>Destino</label><input type="text" name="destino"></div>
                        </div>
                        <div class="form-group" style="margin-top:10px;"><label>Descrição</label><input type="text" name="descricao"></div>
                        <button type="submit" name="novo_movimento" class="btn-save" style="margin-top:10px; font-size:0.9rem;">Adicionar Fase</button>
                    </form>

                    <table style="width:100%; font-size:0.9rem; border-collapse:collapse;">
                        <thead style="background:#f0f0f0;"><tr><th>Data</th><th>Fase</th><th>Detalhes</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php 
                            $movs = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                            $movs->execute([$cliente_ativo['id']]);
                            foreach($movs->fetchAll() as $m): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:10px;"><?= date('d/m/y H:i', strtotime($m['data_movimento'])) ?></td>
                                <td style="padding:10px;"><strong><?= $m['titulo_fase'] ?></strong></td>
                                <td style="padding:10px;"><?= $m['descricao'] ?> (<?= $m['status_tipo'] ?>)</td>
                                <td style="padding:10px;"><a href="?cid=<?= $cliente_ativo['id'] ?>&del_mov=<?= $m['id'] ?>" style="color:red; font-size:0.8rem;">Excluir</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php else: ?>
                    <!-- FORMS UNIFICADOS PARA ABAS DE DADOS -->
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <input type="hidden" name="active_tab_source" value="<?= $active_tab ?>">

                        <?php if($active_tab == 'requerente'): ?>
                            <h3>📂 Dados do Requerente</h3>
                            
                            <div style="background: #e3f2fd; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #bbdefb;">
                                <label style="font-weight:bold; display:block; margin-bottom:5px; color:#0d47a1;">📂 Link da Pasta no Google Drive (Cliente)</label>
                                <input type="url" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" placeholder="https://drive.google.com/drive/folders/..." style="width:100%; padding:10px; border:1px solid #90caf9; border-radius:4px;">
                                <small style="color:#555;">Se preenchido, um botão "Acessar Pasta" aparecerá no painel do cliente.</small>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tipo de Pessoa</label>
                                    <select name="tipo_pessoa">
                                        <option value="Fisica" <?= ($detalhes['tipo_pessoa']??'')=='Fisica'?'selected':'' ?>>Pessoa Física</option>
                                        <option value="Juridica" <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'selected':'' ?>>Pessoa Jurídica</option>
                                    </select>
                                </div>
                                <div class="form-group"><label>CPF / CNPJ</label><input type="text" name="cpf_cnpj" value="<?= $detalhes['cpf_cnpj']??'' ?>"></div>
                                <div class="form-group"><label>RG / Inscrição Est.</label><input type="text" name="rg_ie" value="<?= $detalhes['rg_ie']??'' ?>"></div>
                                <div class="form-group"><label>Estado Civil</label><input type="text" name="estado_civil" value="<?= $detalhes['estado_civil']??'' ?>"></div>
                            </div>
                            <div class="form-group" style="margin-top:15px;"><label>Profissão</label><input type="text" name="profissao" value="<?= $detalhes['profissao']??'' ?>"></div>
                            <div class="form-group" style="margin-top:15px;"><label>Endereço Residencial Completo</label><textarea name="endereco_residencial" rows="2"><?= $detalhes['endereco_residencial']??'' ?></textarea></div>
                            <div class="form-grid" style="margin-top:15px;">
                                <div class="form-group"><label>E-mail</label><input type="text" name="contato_email" value="<?= $detalhes['contato_email']??'' ?>"></div>
                                <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="contato_tel" value="<?= $detalhes['contato_tel']??'' ?>"></div>
                            </div>

                        <?php elseif($active_tab == 'imovel'): ?>
                            <h3>🏠 Lote e Imóvel</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Inscrição Imobiliária (Capa IPTU)</label><input type="text" name="inscricao_imob" value="<?= $detalhes['inscricao_imob']??'' ?>"></div>
                                <div class="form-group"><label>Número da Matrícula</label><input type="text" name="num_matricula" value="<?= $detalhes['num_matricula']??'' ?>"></div>
                                <div class="form-group"><label>Zoneamento</label><input type="text" name="zoneamento" value="<?= $detalhes['zoneamento']??'' ?>"></div>
                            </div>
                            <div class="form-group" style="margin-top:15px;"><label>Endereço do Imóvel</label><textarea name="endereco_imovel" rows="2"><?= $detalhes['endereco_imovel']??'' ?></textarea></div>
                            
                            <h4 class="form-section-title">Quadro de Áreas</h4>
                            <div class="form-grid">
                                <div class="form-group"><label>Área do Terreno (m²)</label><input type="number" step="0.01" name="area_terreno" value="<?= $detalhes['area_terreno']??'' ?>"></div>
                                <div class="form-group"><label>Área Construída (m²)</label><input type="number" step="0.01" name="area_construida" value="<?= $detalhes['area_construida']??'' ?>"></div>
                            </div>

                            <!-- Validação Visual -->
                            <?php 
                                $at = floatval($detalhes['area_terreno']??0);
                                $ac = floatval($detalhes['area_construida']??0);
                                if($ac > $at && $at > 0) {
                                    echo "<div class='alert-validacao'>⚠️ <strong>Atenção:</strong> A Área Construída é maior que a Área do Terreno. Verifique se isso está correto (ex: sobrado) ou se há erro de digitação.</div>";
                                }
                            ?>

                        <?php elseif($active_tab == 'engenharia'): ?>
                            <h3>📐 Projeto de Engenharia</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Responsável Técnico</label><input type="text" name="resp_tecnico" value="<?= $detalhes['resp_tecnico']??'' ?>"></div>
                                <div class="form-group"><label>Registro Profissional (CREA/CAU)</label><input type="text" name="registro_prof" value="<?= $detalhes['registro_prof']??'' ?>"></div>
                                <div class="form-group"><label>Número ART / RRT</label><input type="text" name="num_art_rrt" value="<?= $detalhes['num_art_rrt']??'' ?>"></div>
                            </div>
                            
                            <h4 class="form-section-title">Uploads de Engenharia</h4>
                            <p style="color:#666; font-size:0.9rem;">Para anexar plantas (DWG/PDF), use a seção de "Documentos do Drive" abaixo.</p>

                        <?php elseif($active_tab == 'financeiro'): ?>
                            <h3>💰 Financeiro e Taxas</h3>
                            <p>Controle de pagamento de taxas municipais.</p>
                            
                            <div style="background:#f9f9f9; padding:20px; border-radius:8px;">
                                <div style="margin-bottom:15px;">
                                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                                        <input type="checkbox" name="status_taxa_aprovacao" value="1" <?= ($detalhes['status_taxa_aprovacao']??0)?'checked':'' ?> style="width:20px; height:20px;">
                                        <span><strong>Taxa de Aprovação de Projeto</strong> (Pago)</span>
                                    </label>
                                </div>
                                <div style="margin-bottom:15px;">
                                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                                        <input type="checkbox" name="status_issqn" value="1" <?= ($detalhes['status_issqn']??0)?'checked':'' ?> style="width:20px; height:20px;">
                                        <span><strong>Taxa de ISSQN</strong> (Imposto sobre Serviços)</span>
                                    </label>
                                </div>
                                <div style="margin-bottom:15px;">
                                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                                        <input type="checkbox" name="status_multas" value="1" <?= ($detalhes['status_multas']??0)?'checked':'' ?> style="width:20px; height:20px;">
                                        <span><strong>Multas de Regularização</strong> (Se houver)</span>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Botão de Salvar Global (Recarrega a página mantendo a aba) -->
                        <div style="border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px;">
                            <button type="submit" name="salvar_detalhes" class="btn-save">Salvar Alterações</button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>

            <!-- Seção Comum: Documentos -->
            <div class="card" style="margin-top: 30px;">
                <h3>Anexos e Documentos (Drive)</h3>
                <form method="POST" style="background:#f8f9fa; padding:15px; border-radius:6px; display:flex; gap:10px;">
                    <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                    <input type="text" name="titulo" placeholder="Nome do Arquivo" required style="flex:1; padding:8px;">
                    <input type="url" name="link" placeholder="Link do Google Drive" required style="flex:2; padding:8px;">
                    <button type="submit" name="novo_doc" style="background:var(--color-primary); color:white; border:none; padding:0 20px; border-radius:4px; cursor:pointer;">Anexar</button>
                </form>
                <div style="margin-top:15px;">
                    <?php foreach($docs_ativo as $d): ?>
                        <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
                            <span>📄 <?= htmlspecialchars($d['titulo']) ?></span>
                            <div>
                                <a href="<?= htmlspecialchars($d['link_drive']) ?>" target="_blank" style="margin-right:15px; color:blue;">Abrir</a>
                                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&delete_doc=<?= $d['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" style="color:red;">Excluir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endif; ?>
    </main>
</div>

</body>
</html>
