<?php
session_start();
require 'db.php';

// --- Configuração e Segurança ---
$minha_senha_mestra = "VilelaAdmin2025"; // Troque por sua senha

// Login
if (isset($_POST['login_admin'])) {
    if ($_POST['senha_mestra'] === $minha_senha_mestra) {
        $_SESSION['admin_logado'] = true;
    } else {
        $erro_login = "Senha incorreta.";
    }
}

// Logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Se não estiver logado, exibe apenas o formulário de login
if (!isset($_SESSION['admin_logado'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin | Vilela</title>
        <link rel="stylesheet" href="../style.css">
        <style>
            body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: var(--color-bg); }
            .login-card { background: white; padding: 2rem; border-radius: 16px; box-shadow: var(--shadow-soft); text-align: center; width: 100%; max-width: 350px; }
            input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 8px; }
            button { width: 100%; padding: 12px; background: var(--color-primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2>Gestão Vilela</h2>
            <?php if(isset($erro_login)) echo "<p style='color:red'>$erro_login</p>"; ?>
            <form method="POST">
                <input type="password" name="senha_mestra" placeholder="Senha Mestra" required autofocus>
                <button type="submit" name="login_admin">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- Processamento de Formulários ---

// 1. Cadastrar Cliente
if (isset($_POST['novo_cliente'])) {
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $user, $pass]);
        $sucesso = "Cliente $nome cadastrado!";
    } catch (PDOException $e) {
        $erro = "Erro: Usuário já existe ou dados inválidos.";
    }
}

// 2. Adicionar Progresso
if (isset($_POST['novo_movimento'])) {
    $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo, departamento_origem, departamento_destino, usuario_responsavel, anexo_url, anexo_nome) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $_POST['cliente_id'], 
        $_POST['titulo'], 
        $_POST['data'], 
        $_POST['descricao'], 
        $_POST['status_tipo'],
        $_POST['origem'],
        $_POST['destino'],
        $_POST['responsavel'],
        $_POST['anexo_url'],
        $_POST['anexo_nome']
    ]);
    $sucesso = "Movimento registrado na timeline!";
}

// 2. Exclusões
if (isset($_GET['del_mov'])) {
    $pdo->prepare("DELETE FROM processo_movimentos WHERE id=?")->execute([$_GET['del_mov']]);
    header("Location: ?cid=".$_GET['cid']); exit;
}

// 3. Adicionar Documento
if (isset($_POST['novo_doc'])) {
    $stmt = $pdo->prepare("INSERT INTO documentos (cliente_id, titulo, link_drive) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['cliente_id'], $_POST['titulo'], $_POST['link']]);
    $sucesso = "Documento anexado!";
}

// 4. Excluir Itens (Progresso ou Documento)
if (isset($_GET['delete_progresso'])) {
    $pdo->prepare("DELETE FROM progresso WHERE id = ?")->execute([$_GET['delete_progresso']]);
    header("Location: ?cliente_id=" . $_GET['cid']); // Recarrega a página mantendo o cliente
    exit;
}
if (isset($_GET['delete_doc'])) {
    $pdo->prepare("DELETE FROM documentos WHERE id = ?")->execute([$_GET['delete_doc']]);
    header("Location: ?cliente_id=" . $_GET['cid']);
    exit;
}

// --- Consultas de Dados ---

// Listar todos os clientes para a barra lateral
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();

// Se um cliente estiver selecionado, busca os dados dele
$cliente_ativo = null;
$progresso_ativo = [];
$docs_ativo = [];

if (isset($_GET['cliente_id'])) {
    $id_selecionado = $_GET['cliente_id'];
    
    // Dados do Cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id_selecionado]);
    $cliente_ativo = $stmt->fetch();

    // Histórico
    $stmt = $pdo->prepare("SELECT * FROM progresso WHERE cliente_id = ? ORDER BY data_fase DESC, id DESC");
    $stmt->execute([$id_selecionado]);
    $progresso_ativo = $stmt->fetchAll();

    // Documentos
    $stmt = $pdo->prepare("SELECT * FROM documentos WHERE cliente_id = ? ORDER BY id DESC");
    $stmt->execute([$id_selecionado]);
    $docs_ativo = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão | Vilela Engenharia</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* CSS Específico para o Painel Admin */
        body { background-color: #f4f7f6; display: block; padding: 0; }
        
        .admin-header {
            background: var(--color-primary-strong);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            align-items: start; /* Impede que a sidebar estique */
        }

        /* Barra Lateral */
        .sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .sidebar h3 { font-size: 0.9rem; text-transform: uppercase; color: #888; margin-bottom: 10px; }
        
        .client-list { list-style: none; padding: 0; margin: 0; }
        .client-list li a {
            display: block;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--color-text);
            font-weight: 500;
            transition: 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        .client-list li a:hover { background: #e6f2ee; color: var(--color-primary); }
        .client-list li a.active { background: var(--color-primary); color: white; }
        
        .btn-new-client {
            display: block;
            width: 100%;
            padding: 10px;
            background: #efb524;
            color: #1f2521;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Área Principal */
        .workspace {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card h2 { margin-top: 0; color: var(--color-primary-strong); border-bottom: 1px solid #eee; padding-bottom: 10px; }

        /* Formulários */
        .form-row { display: flex; gap: 10px; align-items: flex-end; margin-top: 15px; }
        .form-group { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.85rem; font-weight: bold; color: #555; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }
        .btn-submit {
            padding: 10px 20px;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            height: 42px; /* Alinhar com inputs */
        }
        .btn-submit:hover { background: var(--color-primary-strong); }

        /* Tabelas de Dados */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.95rem; }
        .data-table th { text-align: left; padding: 10px; background: #f9f9f9; color: #666; font-size: 0.85rem; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .btn-delete { color: #dc3545; text-decoration: none; font-size: 0.8rem; font-weight: bold; }

        /* Mobile */
        @media (max-width: 768px) {
            .admin-container { grid-template-columns: 1fr; }
            .sidebar { max-height: 200px; }
            .form-row { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

    <header class="admin-header">
        <div style="font-weight: bold; font-size: 1.2rem;">Vilela Engenharia <span style="font-weight:400; font-size:1rem; opacity:0.8;">| Painel Admin</span></div>
        <a href="?sair=true" style="color: white; text-decoration: none; border: 1px solid white; padding: 5px 15px; border-radius: 20px;">Sair</a>
    </header>

    <div class="admin-container">
        
        <aside class="sidebar">
            <a href="?novo=true" class="btn-new-client">+ Novo Cliente</a>
            
            <h3>Meus Clientes</h3>
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

        <main class="workspace">
            
            <?php if(isset($sucesso)): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb;">
                    <?= $sucesso ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['novo'])): ?>
                <div class="card">
                    <h2>Cadastrar Novo Cliente</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" required placeholder="Ex: Maria Souza">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Usuário de Acesso</label>
                                <input type="text" name="usuario" required placeholder="Ex: maria.souza">
                            </div>
                            <div class="form-group">
                                <label>Senha Inicial</label>
                                <input type="text" name="senha" required placeholder="Ex: 123456">
                            </div>
                        </div>
                        <div style="margin-top: 15px; text-align: right;">
                            <button type="submit" name="novo_cliente" class="btn-submit">Criar Cliente</button>
                        </div>
                    </form>
                </div>

            <?php elseif($cliente_ativo): ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 style="margin: 0; color: var(--color-primary-strong);"><?= htmlspecialchars($cliente_ativo['nome']) ?></h1>
                    <span style="background: #e6f2ee; padding: 5px 10px; border-radius: 5px; color: #555;">Usuário: <strong><?= htmlspecialchars($cliente_ativo['usuario']) ?></strong></span>
                </div>

                <div class="card">
                    <h2>📅 Atualizar Timeline do Processo</h2>
                    
                    <!-- Formulário Expandido para Processos -->
                    <form method="POST" style="background:#fafafa; padding:20px; border-radius:8px; margin-bottom:25px; border: 1px solid #eee;">
                        <input type="hidden" name="cliente_id" value="<?= $ativo['id'] ?>">
                        
                        <!-- Linha 1: Título e Data -->
                        <div class="flex-row">
                            <div class="form-group" style="flex:2">
                                <label>Título da Fase</label>
                                <input type="text" name="titulo" placeholder="Ex: Protocolo na Prefeitura" required>
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Data/Hora</label>
                                <input type="datetime-local" name="data" required value="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Status</label>
                                <select name="status_tipo">
                                    <option value="tramite">🔄 Trâmite</option>
                                    <option value="inicio">🚩 Início</option>
                                    <option value="pendencia">⚠️ Pendência</option>
                                    <option value="documento">📄 Documento</option>
                                    <option value="conclusao">✅ Conclusão</option>
                                </select>
                            </div>
                        </div>

                        <!-- Linha 2: Fluxo de Departamentos -->
                        <div class="flex-row">
                            <div class="form-group" style="flex:1">
                                <label>De (Origem)</label>
                                <input type="text" name="origem" placeholder="Ex: Protocolo">
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Para (Destino)</label>
                                <input type="text" name="destino" placeholder="Ex: Engenharia">
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Responsável</label>
                                <input type="text" name="responsavel" placeholder="Ex: João">
                            </div>
                        </div>

                        <!-- Linha 3: Detalhes e Anexo -->
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>Descrição Detalhada</label>
                            <textarea name="descricao" rows="2" placeholder="Descreva o que aconteceu nesta etapa..."></textarea>
                        </div>

                        <div class="flex-row" style="align-items: flex-end;">
                            <div class="form-group" style="flex:2">
                                <label>Link de Anexo (Opcional)</label>
                                <input type="url" name="anexo_url" placeholder="https://drive.google.com/...">
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Nome do Anexo</label>
                                <input type="text" name="anexo_nome" placeholder="Ex: Comprovante.pdf">
                            </div>
                            <button type="submit" name="novo_movimento" class="save" style="height: 42px;">Adicionar à Timeline</button>
                        </div>
                    </form>

                    <!-- Tabela de Movimentos -->
                    <table style="font-size: 0.85rem;">
                        <thead><tr><th>Data</th><th>Fase / Detalhes</th><th>Fluxo</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php 
                            // Busca na tabela nova
                            $movs = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                            $movs->execute([$ativo['id']]);
                            $lista_movs = $movs->fetchAll();
                            
                            foreach($lista_movs as $m): ?>
                            <tr>
                                <td style="width:100px;"><?= date('d/m/y H:i', strtotime($m['data_movimento'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($m['titulo_fase']) ?></strong><br>
                                    <span style="color:#666; font-size:0.8rem;"><?= htmlspecialchars(substr($m['descricao'],0,50)) ?>...</span>
                                    <?php if($m['anexo_url']): ?><br><a href="<?= $m['anexo_url'] ?>" target="_blank" style="color:#136f5c;">📎 Ver Anexo</a><?php endif; ?>
                                </td>
                                <td><?= $m['departamento_origem'] ?> ➝ <?= $m['departamento_destino'] ?></td>
                                <td><span style="padding:2px 6px; border-radius:4px; font-size:10px; text-transform:uppercase; background:#eee;"><?= $m['status_tipo'] ?></span></td>
                                <td style="text-align:right;"><a href="?cid=<?= $ativo['id'] ?>&del_mov=<?= $m['id'] ?>" class="del" onclick="return confirm('Apagar este movimento?')">Apagar</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h2>Documentos do Drive</h2>
                    
                    <form method="POST" style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-row" style="margin-top: 0;">
                            <div class="form-group" style="flex: 1;">
                                <label>Nome do Arquivo</label>
                                <input type="text" name="titulo" placeholder="Ex: Alvará de Construção" required>
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label>Link do Google Drive</label>
                                <input type="url" name="link" placeholder="https://drive.google.com/..." required>
                            </div>
                            <button type="submit" name="novo_doc" class="btn-submit">Salvar</button>
                        </div>
                    </form>

                    <table class="data-table">
                        <thead><tr><th>Arquivo</th><th>Link</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php foreach($docs_ativo as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['titulo']) ?></td>
                                <td><a href="<?= htmlspecialchars($d['link_drive']) ?>" target="_blank" style="color: var(--color-primary);">Abrir Link ↗</a></td>
                                <td><a href="?cliente_id=<?= $cliente_ativo['id'] ?>&delete_doc=<?= $d['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" class="btn-delete" onclick="return confirm('Excluir?')">Excluir</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #888;">
                    <svg style="width: 64px; height: 64px; opacity: 0.3;" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
                    <h2>Bem-vindo à Gestão Vilela</h2>
                    <p>Selecione um cliente na esquerda ou cadastre um novo para começar.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>

</body>
</html>
