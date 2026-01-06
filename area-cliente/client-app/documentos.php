<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
// PAGE: DOCUMENTOS
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=2.6">
    <style>
        body { background: #f4f6f8; font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .dev-card {
            background: white; padding: 40px; border-radius: 24px; text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08); max-width: 400px; width: 100%;
        }
        .icon { font-size: 3rem; margin-bottom: 20px; }
        h1 { color: #333; margin: 0 0 10px 0; font-size: 1.5rem; }
        p { color: #666; margin-bottom: 30px; line-height: 1.5; }
        .btn-back {
            display: inline-block; text-decoration: none; background: #146c43; color: white;
            padding: 12px 24px; border-radius: 50px; font-weight: 600; transition: transform 0.2s;
        }
        .btn-back:hover { transform: translateY(-2px); background: #0f5132; }
    </style>
</head>
<body>
    <div class="dev-card">
        <div class="icon">📂</div>
        <h1>Em Desenvolvimento</h1>
        <p>Sua central de documentos está sendo organizada para facilitar o acesso aos seus projetos.</p>
        <a href="index.php" class="btn-back">Voltar para Início</a>
    </div>
</body>
</html>
