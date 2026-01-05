<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estamos em Manutenção | Vilela Engenharia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0" />
    <style>
        :root {
            --primary: #146c43;
            --accent: #fd7e14;
            --text-main: #333;
            --text-light: #666;
            --bg: #f8f9fa;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .maint-card {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-bottom: 5px solid var(--primary);
        }
        .logo {
            max-height: 60px;
            margin-bottom: 30px;
        }
        .icon-area {
            font-size: 4rem;
            color: var(--accent);
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        h1 {
            color: var(--primary);
            margin: 0 0 15px 0;
            font-size: 1.8rem;
        }
        p {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 1.05rem;
        }
        .contact-box {
            background: #f0f8ff;
            border: 1px solid #cce5ff;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #004085;
            margin-top: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #888;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .back-link:hover { color: var(--primary); }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="maint-card">
        <img src="../assets/logo.png" alt="Vilela Engenharia" class="logo">
        
        <div class="icon-area">
            <span class="material-symbols-rounded" style="font-size: 4rem;">engineering</span>
        </div>

        <h1>Melhorando para Você</h1>
        <p>
            Estamos realizando uma atualização programada em nossa Área do Cliente para ofecerer uma experiência ainda mais rápida, segura e completa para o seu projeto.
        </p>
        <p>
            Voltaremos em instantes! Agradecemos a compreensão.
        </p>

        <div class="contact-box">
            <strong>Precisa falar com urgência?</strong><br>
            Nos chame no WhatsApp: (35) 98452-9577
        </div>

        <div style="margin-top: 20px; font-size: 0.8rem; color: #aaa;">
            Equipe Vilela Engenharia
        </div>
    </div>
</body>
</html>
