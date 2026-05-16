<?php
// Force session cookie to be available to entire domain, preventing subdirectory issues
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// 0. QUICK CHECK: ADMIN ALREADY LOGGED IN?
// Bypass everything and go to dashboard
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header("Location: gestao_admin_99.php");
    exit;
}

// MAINTENANCE CHECK REMOVED FROM HERE TO ALLOW LOGIN FORM VISIBILITY
// Logic moved to inside POST handling below

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $senha = trim($_POST['senha']);

    // 1. Verifica se é ADMIN
    // Senha mestra definida em db.php
    $senhaMestraAdmin = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'VilelaAdmin2025'; 
    
    if ((strtolower($usuario) === 'admin' || strtolower($usuario) === 'vilela') && $senha === $senhaMestraAdmin) {
        $_SESSION['admin_logado'] = true;
        header("Location: gestao_admin_99.php");
        exit;
    }



    // CHECK MAINTENANCE BLOCK FOR CLIENTS
    // If we are here, it's not admin. Check maintenance again to block client login.
    try {
        $stmtMaint = $pdo->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'maintenance_mode'");
        if ($stmtMaint && $stmtMaint->fetchColumn() == 1) {
            
            // SE FOR ADMIN TENTANDO LOGAR (E ERROU A SENHA), NÃO MOSTRA MANUTENÇÃO, MOSTRA ERRO
            if (strtolower($usuario) !== 'admin' && strtolower($usuario) !== 'vilela') {
                 // MOSTRAR AVISO DE MANUTENÇÃO (PÁGINA COMPLETA) PARA CLIENTES
                require 'maintenance.php';
                exit;
            }
        }
        
        // Se não caiu no exit acima, continua tentando logar (vai dar erro de senha se não for admin)
            // 2. Se não for Admin, busca Cliente no banco
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            // Verifica a senha (usando hash seguro)
            if ($user && password_verify($senha, $user['senha'])) {
                session_regenerate_id(true);
                // CLEAR PREVIOUS SESSION DATA (Prevent Admin/Client Mix)
                session_unset();
                
                $_SESSION['cliente_id'] = $user['id'];
                $_SESSION['cliente_nome'] = $user['nome'];
                session_write_close();
                header("Location: client-app/index.php");
                exit;
            } else {
                $erro = "Usuário ou senha inválidos!";
            }

    } catch(Exception $e) {
        $erro = "Erro Login: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">

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

    <div class="login-container reveal">
        <div class="logo-area">
            <img src="../assets/logo.png" alt="Vilela Engenharia" onerror="this.style.display='none'">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginCard = document.querySelector('.login-container');
            if (loginCard) {
                // Pequeno delay para garantir que a transição seja perceptível no carregamento
                setTimeout(() => {
                    loginCard.classList.add('active');
                }, 100);
            }
        });
    </script>
</body>

</html>
