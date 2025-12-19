<?php
// gestao_admin_99.php - Painel Administrativo Dinâmico
session_start();
require 'db.php';

// Senha mestra (Recomendado alterar)
$minha_senha_mestra = "VilelaAdmin2025"; 

// --- AUTENTICAÇÃO ---
if (isset($_POST['login_admin'])) {
    if ($_POST['senha_mestra'] === $minha_senha_mestra) {
        $_SESSION['admin_logado'] = true;
    } else {
        $erro_login = "Senha incorreta!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: gestao_admin_99.php");
    exit;
}

if (!isset($_SESSION['admin_logado'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login | Vilela Engenharia</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../style.css">
        <style>
            body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--color-bg); padding: 20px; }
            .login-card { background: white; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-soft); width: min(400px, 100%); text-align: center; }
            input { width: 100%; padding: 12px; margin-bottom: 16px; border: 1px solid #ddd; border-radius: 8px; }
            button { width: 100%; background: var(--color-primary); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; }
            .error { color: #d32f2f; margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <img src="../assets/logo.png" alt="Logo" style="height: 80px; margin-bottom: 24px;">
            <h2 style="margin: 0 0 24px; color: var(--color-primary-strong);">Admin Vilela</h2>
            <?php if(isset($erro_login)): ?><div class="error"><?= $erro_login ?></div><?php endif; ?>
            <form method="POST">
                <input type="password" name="senha_mestra" placeholder="Senha do Administrador" required>
                <button type="submit" name="login_admin">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- LÓGICA DE AÇÕES (CRUD) ---

$msg = "";
$msg_type = "";

// 1. Excluir Cliente
if (isset($_GET['delete_client'])) {
    $id = $_GET['delete_client'];
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: gestao_admin_99.php?msg=Cliente excluido com sucesso&type=success");
    exit;
}

// 2. Excluir Progresso
if (isset($_GET['delete_progresso'])) {
    $id = $_GET['delete_progresso'];
    $client_id = $_GET['client_id']; // Para voltar pra página certa
    $stmt = $pdo->prepare("DELETE FROM progresso WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: gestao_admin_99.php?view=details&id=$client_id&msg=Fase removida&type=success");
    exit;
}

// 3. Excluir Documento
if (isset($_GET['delete_doc'])) {
    $id = $_GET['delete_doc'];
    $client_id = $_GET['client_id'];
    $stmt = $pdo->prepare("DELETE FROM documentos WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: gestao_admin_99.php?view=details&id=$client_id&msg=Documento removido&type=success");
    exit;
}

// 4. Cadastrar Cliente
if (isset($_POST['novo_cliente'])) {
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$nome, $user, $pass]);
        $msg = "Cliente cadastrado!";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Erro: Usuário já existe.";
        $msg_type = "error";
    }
}

// 5. Adicionar Progresso (Direto)
if (isset($_POST['add_progresso_direct'])) {
    $stmt = $pdo->prepare("INSERT INTO progresso (cliente_id, fase, data_fase, descricao) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['cliente_id'], $_POST['fase'], $_POST['data'], $_POST['desc']]);
    header("Location: gestao_admin_99.php?view=details&id=".$_POST['cliente_id']."&msg=Progresso adicionado&type=success");
    exit;
}

// 6. Adicionar Documento (Direto)
if (isset($_POST['add_doc_direct'])) {
    $stmt = $pdo->prepare("INSERT INTO documentos (cliente_id, titulo, link_drive) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['cliente_id'], $_POST['titulo'], $_POST['link']]);
    header("Location: gestao_admin_99.php?view=details&id=".$_POST['cliente_id']."&msg=Documento adicionado&type=success");
    exit;
}

// Recuperar mensagens via URL
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msg_type = $_GET['type'];
}

// --- PREPARAÇÃO DA VIEW ---
$view = $_GET['view'] ?? 'list'; // 'list' ou 'details'
$search = $_GET['search'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin Dinâmico</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <style>
        /* Estilos Base Admin */
        body { background: #f4f6f5; padding: 20px; max-width: 1200px; margin: 0 auto; color: var(--color-text); }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .logo-box { display: flex; align-items: center; gap: 12px; }
        .logo-box img { height: 40px; }
        .logo-box h1 { margin: 0; font-size: 1.2rem; color: var(--color-primary-strong); }
        
        .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-primary:hover { background: var(--color-primary-strong); }
        .btn-danger { background: #feebeb; color: #d32f2f; }
        .btn-danger:hover { background: #fdd; }
        .btn-outline { border: 1px solid #ddd; background: white; color: #555; }
        .btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }

        /* Tabela */
        .table-container { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 16px; border-bottom: 1px solid #eee; }
        th { color: var(--color-text-subtle); font-weight: 600; font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #fafafa; }
        
        /* Search Bar */
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }

        /* Notification */
        .notif { position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: bold; animation: slideIn 0.3s; z-index: 999; }
        .bg-success { background: var(--color-primary); }
        .bg-error { background: #d32f2f; }
        @keyframes slideIn { from{transform:translateX(100%)} to{transform:translateX(0)} }

        /* Detalhes do Cliente */
        .details-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media(max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
        
        .panel { background: white; padding: 24px; border-radius: 16px; border: 1px solid #eee; height: 100%; }
        .panel h3 { margin-top: 0; color: var(--color-primary-strong); border-bottom: 1px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        
        .mini-form { background: #f9f9f9; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px dashed #ccc; }
        .mini-form input, .mini-form textarea, .mini-form select { width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 6px; }
        
        .list-item { display: flex; justify-content: space-between; align-items: start; padding: 12px 0; border-bottom: 1px solid #eee; }
        .list-item:last-child { border-bottom: none; }
        .list-info h4 { margin: 0 0 4px; font-size: 1rem; }
        .list-info p { margin: 0; color: #666; font-size: 0.85rem; }
        .date-badge { background: #e0f2f1; color: var(--color-primary); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; margin-right: 6px; }
    </style>
</head>
<body>
    
    <?php if($msg): ?>
        <div class="notif bg-<?= $msg_type ?>" onclick="this.remove()"><?= $msg ?></div>
    <?php endif; ?>

    <div class="header">
        <div class="logo-box">
            <img src="../assets/logo.png" alt="Logo">
            <h1>Gestão Vilela</h1>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if($view === 'details'): ?>
                <a href="gestao_admin_99.php" class="btn btn-outline">&larr; Voltar</a>
            <?php endif; ?>
            <a href="?logout=true" class="btn btn-danger">Sair</a>
        </div>
    </div>

    <!-- VIEW: LISTA DE CLIENTES -->
    <?php if ($view === 'list'): ?>
        <?php
            // Filtro de busca
            $sql = "SELECT * FROM clientes";
            if($search) { $sql .= " WHERE nome LIKE :s OR usuario LIKE :s"; }
            $sql .= " ORDER BY id DESC";
            $stmt = $pdo->prepare($sql);
            if($search) { $stmt->execute(['s' => "%$search%"]); } else { $stmt->execute(); }
            $lista_clientes = $stmt->fetchAll();
        ?>
        
        <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: end;">
            <div>
                <h2 style="margin:0;">Meus Clientes</h2>
                <p style="margin:0; color: #666;">Gerencie o progresso e documentos de cada um.</p>
            </div>
            <button onclick="document.getElementById('modalNewClient').style.display='flex'" class="btn btn-primary">
                + Novo Cliente
            </button>
        </div>

        <form class="search-box">
            <input type="text" name="search" class="search-input" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nome ou CPF...">
            <button type="submit" class="btn btn-outline">Buscar</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Usuário/Login</th>
                        <th>Cadastro</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($lista_clientes) == 0): ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px;">Nenhum cliente encontrado.</td></tr>
                    <?php endif; ?>

                    <?php foreach($lista_clientes as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($c['usuario']) ?></td>
                            <td><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
                            <td style="text-align:right; display:flex; gap:10px; justify-content:flex-end;">
                                <a href="?view=details&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Gerenciar</a>
                                <a href="?delete_client=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza? Isso apaga todo o histórico e documentos deste cliente!')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MODAL NOVO CLIENTE -->
        <div id="modalNewClient" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:white; padding:30px; border-radius:16px; width:400px; max-width:90%; position:relative;">
                <h3 style="margin-top:0;">Cadastrar Cliente</h3>
                <form method="POST">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-bottom:10px;">
                    <label>Login (CPF/Celular)</label>
                    <input type="text" name="usuario" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-bottom:10px;">
                    <label>Senha Inicial</label>
                    <input type="text" name="senha" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-bottom:20px;">
                    <div style="display:flex; gap:10px;">
                        <button type="button" onclick="document.getElementById('modalNewClient').style.display='none'" class="btn btn-outline" style="flex:1;">Cancelar</button>
                        <button type="submit" name="novo_cliente" class="btn btn-primary" style="flex:1;">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

    <!-- VIEW: DETALHES DO CLIENTE -->
    <?php elseif ($view === 'details'): ?>
        <?php
            $id = $_GET['id'];
            // Cliente Info
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch();
            if(!$cliente) die("Cliente não encontrado.");

            // Progresso
            $stmtP = $pdo->prepare("SELECT * FROM progresso WHERE cliente_id = ? ORDER BY data_fase DESC");
            $stmtP->execute([$id]);
            $progresso = $stmtP->fetchAll();

            // Docs
            $stmtD = $pdo->prepare("SELECT * FROM documentos WHERE cliente_id = ? ORDER BY id DESC");
            $stmtD->execute([$id]);
            $docs = $stmtD->fetchAll();
        ?>

        <div class="details-header">
            <div>
                <h2 style="margin:0;"><?= htmlspecialchars($cliente['nome']) ?></h2>
                <p style="margin:0; color:#666;">Login: <?= htmlspecialchars($cliente['usuario']) ?></p>
            </div>
        </div>

        <div class="grid-2">
            <!-- COLUNA 1: PROGRESSO -->
            <div class="panel">
                <h3>
                    <span>📈 Linha do Tempo</span>
                    <button onclick="document.getElementById('formProgresso').style.display = document.getElementById('formProgresso').style.display === 'none' ? 'block' : 'none'" class="btn btn-primary btn-sm">+ Add</button>
                </h3>
                
                <form method="POST" id="formProgresso" class="mini-form" style="display:none;">
                    <input type="hidden" name="cliente_id" value="<?= $id ?>">
                    <input type="hidden" name="add_progresso_direct" value="1">
                    <input type="text" name="fase" placeholder="Título da Fase (Ex: Projeto Aprovado)" required>
                    <input type="date" name="data" value="<?= date('Y-m-d') ?>" required>
                    <textarea name="desc" placeholder="Detalhes..." rows="2"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">Adicionar à Timeline</button>
                </form>

                <div class="list-container">
                    <?php foreach($progresso as $p): ?>
                        <div class="list-item">
                            <div class="list-info">
                                <div><span class="date-badge"><?= date('d/m/y', strtotime($p['data_fase'])) ?></span></div>
                                <h4><?= htmlspecialchars($p['fase']) ?></h4>
                                <p><?= nl2br(htmlspecialchars($p['descricao'])) ?></p>
                            </div>
                            <a href="?delete_progresso=<?= $p['id'] ?>&client_id=<?= $id ?>" class="btn btn-danger btn-sm" style="padding:4px 8px;" onclick="return confirm('Apagar?')">X</a>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($progresso)==0) echo "<p style='color:#999; text-align:center;'>Nenhum histórico ainda.</p>"; ?>
                </div>
            </div>

            <!-- COLUNA 2: DOCUMENTOS -->
            <div class="panel">
                <h3>
                    <span>📂 Documentos</span>
                    <button onclick="document.getElementById('formDoc').style.display = document.getElementById('formDoc').style.display === 'none' ? 'block' : 'none'" class="btn btn-primary btn-sm">+ Add</button>
                </h3>

                <form method="POST" id="formDoc" class="mini-form" style="display:none;">
                    <input type="hidden" name="cliente_id" value="<?= $id ?>">
                    <input type="hidden" name="add_doc_direct" value="1">
                    <input type="text" name="titulo" placeholder="Nome do Arquivo (Ex: Planta.pdf)" required>
                    <input type="url" name="link" placeholder="Link do Drive..." required>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">Salvar Documento</button>
                </form>

                <div class="list-container">
                    <?php foreach($docs as $d): ?>
                        <div class="list-item">
                            <div class="list-info">
                                <h4><?= htmlspecialchars($d['titulo']) ?></h4>
                                <a href="<?= htmlspecialchars($d['link_drive']) ?>" target="_blank" style="font-size:0.85rem; color:var(--color-primary);">Abrir Link &nearr;</a>
                            </div>
                            <a href="?delete_doc=<?= $d['id'] ?>&client_id=<?= $id ?>" class="btn btn-danger btn-sm" style="padding:4px 8px;" onclick="return confirm('Apagar?')">X</a>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($docs)==0) echo "<p style='color:#999; text-align:center;'>Nenhum documento.</p>"; ?>
                </div>
            </div>
        </div>

    <?php endif; ?>

</body>
</html>
