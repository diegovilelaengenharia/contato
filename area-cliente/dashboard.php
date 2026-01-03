<?php
session_name('CLIENTE_SESSID');
session_start();

// Verifica se está logado
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente | Em Manutenção</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #2c3e50;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 400px;
            width: 90%;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        h1 {
            color: #146c43;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .btn-logout {
            display: inline-block;
            text-decoration: none;
            color: #dc3545;
            font-weight: bold;
            font-size: 14px;
            border: 1px solid #dc3545;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="../assets/logo.png" alt="Vilela Engenharia" class="logo">
        <h1>Em Manutenção</h1>
        <p>Estamos atualizando nossa Área do Cliente para trazer uma experiência ainda melhor para você.</p>
        <p>Por favor, retorne em breve.</p>
        <a href="logout.php" class="btn-logout">Sair</a>
    </div>
</body>
</html>
