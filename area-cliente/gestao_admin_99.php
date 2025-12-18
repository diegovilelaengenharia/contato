<?php
// gestao_admin_99.php
session_start();
require 'db.php';

// Senha mestra simples para acesso ao painel (RECOMENDADO ALTERAR DEPOIS)
$minha_senha_mestra = "VilelaAdmin2025"; 

if (isset($_POST['login_admin'])) {
    if ($_POST['senha_mestra'] === $minha_senha_mestra) {
        $_SESSION['admin_logado'] = true;
    } else {
        echo "<script>alert('Senha errada!');</script>";
    }
}

if (!isset($_SESSION['admin_logado'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
        <link rel="stylesheet" href="../style.css">
        <style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--color-bg);}</style>
    </head>
    <body>
        <form method="POST" style="background:white; padding:40px; border-radius:24px; box-shadow:var(--shadow-soft); text-align:center;">
            <h2 style="margin-top:0; color:var(--color-primary-strong);">Admin Vilela</h2>
            <input type="password" name="senha_mestra" placeholder="Senha do Administrador" style="padding:10px; width:100%; margin-bottom:15px; border:1px solid #ccc; border-radius:8px;">
            <button type="submit" name="login_admin" style="background:var(--color-primary); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold;">Entrar</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// 1. Cadastrar Novo Cliente
if (isset($_POST['novo_cliente'])) {
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    // Cria um hash seguro da senha
    $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)");
    if($stmt->execute([$nome, $user, $pass])) {
        echo "<script>alert('Cliente cadastrado com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar. Verifique se o usuário já existe.');</script>";
    }
}

// 2. Adicionar Progresso
if (isset($_POST['novo_progresso'])) {
    $stmt = $pdo->prepare("INSERT INTO progresso (cliente_id, fase, data_fase, descricao) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['cliente_id'], $_POST['fase'], $_POST['data'], $_POST['desc']]);
    echo "<script>alert('Progresso atualizado com sucesso!');</script>";
}

// 3. Adicionar Documento
if (isset($_POST['novo_doc'])) {
    $stmt = $pdo->prepare("INSERT INTO documentos (cliente_id, titulo, link_drive) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['cliente_id'], $_POST['titulo'], $_POST['link']]);
    echo "<script>alert('Documento adicionado com sucesso!');</script>";
}

// Buscar clientes para o select
$clientes = $pdo->query("SELECT * FROM clientes")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin | Vilela Engenharia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: 'Outfit', sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; display: block; }
        h1, h3 { color: var(--color-primary-strong); }
        .admin-section { background: white; padding: 25px; border-radius: 16px; box-shadow: var(--shadow-soft); margin-bottom: 30px; }
        input, select, textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        button { background: var(--color-primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        button:hover { background: var(--color-primary-strong); }
        .logout-link { display: inline-block; margin-bottom: 20px; color: #d32f2f; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1>Painel de Gestão</h1>
        <a href="logout.php" class="logout-link">Sair</a>
    </div>
    
    <div class="admin-section">
        <h3>1. Cadastrar Novo Cliente</h3>
        <form method="POST">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <input type="text" name="nome" placeholder="Nome Completo" required>
                <input type="text" name="usuario" placeholder="Login (ex: CPF ou Nome)" required>
            </div>
            <input type="text" name="senha" placeholder="Senha Inicial" required>
            <button type="submit" name="novo_cliente">Cadastrar Cliente</button>
        </form>
    </div>

    <div class="admin-section" style="border-left: 5px solid var(--color-primary);">
        <h3>2. Atualizar Progresso</h3>
        <form method="POST">
            <select name="cliente_id" required>
                <option value="">Selecione o Cliente</option>
                <?php foreach($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:10px;">
                <input type="text" name="fase" placeholder="Fase Atual (ex: Protocolo na Prefeitura)" required>
                <input type="date" name="data" required>
            </div>
            <textarea name="desc" placeholder="Detalhes do andamento..." rows="3" required></textarea>
            <button type="submit" name="novo_progresso">Adicionar Progresso</button>
        </form>
    </div>

    <div class="admin-section" style="border-left: 5px solid var(--color-accent);">
        <h3>3. Anexar Documento</h3>
        <form method="POST">
            <select name="cliente_id" required>
                <option value="">Selecione o Cliente</option>
                <?php foreach($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="titulo" placeholder="Nome do Arquivo (ex: Planta Baixa.pdf)" required>
            <input type="url" name="link" placeholder="Link do Google Drive (Partilhar -> Copiar Link)" required>
            <button type="submit" name="novo_doc">Salvar Link</button>
        </form>
    </div>
</body>
</html>
