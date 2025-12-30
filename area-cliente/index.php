<?php
session_start();
// Conexão com banco de dados
try {
    if (!file_exists('db.php')) {
        throw new Exception("Arquivo 'db.php' não encontrado na pasta area-cliente.");
    }
    require 'db.php';

    
    // Verifica se a variável $pdo foi criada corretamente
    if (!isset($pdo)) {
         throw new Exception("O arquivo db.php foi carregado, mas a conexão (\$pdo) não foi estabelecida.");
    }
} catch (Exception $e) {
    // Exibe erro amigável (mas técnico o suficiente para debug)
    die("<div style='font-family:sans-serif; color:#721c24; background-color:#f8d7da; padding:20px; margin:20px; border:1px solid #f5c6cb; border-radius:5px;'>
            <h2 style='margin-top:0'>Erro Crítico no Sistema</h2>
            <p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr>
            <p>Dica: Verifique se o arquivo <code>db.php</code> existe no servidor e se as credenciais do banco (usuário, senha, nome do banco) estão corretas.</p>
         </div>");
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    // 1. Verifica se é ADMIN
    // Senha mestra definida em db.php
    $senhaMestraAdmin = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'VilelaAdmin2025'; 
    
    if (($usuario === 'admin' || $usuario === 'vilela') && $senha === $senhaMestraAdmin) {
        $_SESSION['admin_logado'] = true;
        header("Location: gestao_admin_99.php");
        exit;
    }

    // 2. Se não for Admin, busca Cliente no banco
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // Verifica a senha (usando hash seguro)
    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['cliente_id'] = $user['id'];
        $_SESSION['cliente_nome'] = $user['nome'];
        header("Location: dashboard.php");
        exit;
    } else {
        $erro = "Usuário ou senha inválidos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente | Vilela Engenharia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../assets/logo.png" type="image/png">

</head>

<body>
    <a href="../index.html" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5" />
            <path d="M12 19l-7-7 7-7" />
        </svg>
        Voltar ao site
    </a>

    <div class="login-container">
        <div class="logo-area">
            <img src="../assets/logo.png" alt="Vilela Engenharia">
            <h1>Área do Cliente</h1>
            <p>Acesse seus projetos e documentos</p>
        </div>

        <?php if($erro): ?>
            <div class="alert-error"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="usuario">CPF ou Número do Celular</label>
                <input type="text" id="usuario" name="usuario" class="form-input" placeholder="000.000.000-00 ou (00) 00000-0000"
                    required>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Entrar no Portal</button>

            <!-- <a href="#" class="forgot-password">Esqueceu sua senha?</a> -->
        </form>
    </div>
</body>

</html>
