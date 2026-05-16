<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção - Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00e5ff;
            --secondary: #2979ff;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --bg-glass: rgba(255, 255, 255, 0.05);
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            animation: float 10s infinite alternate;
        }

        body::before {
            background: var(--primary);
            top: -100px;
            left: -100px;
            opacity: 0.2;
        }

        body::after {
            background: var(--secondary);
            bottom: -100px;
            right: -100px;
            opacity: 0.2;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            line-height: 1.2;
            font-weight: 700;
        }

        p {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 3px solid var(--border-glass);
            border-radius: 50%;
            display: inline-block;
            position: relative;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }
        .loader::after {
            content: '';  
            box-sizing: border-box;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid transparent;
            border-bottom-color: var(--primary);
        }
        
        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.1);
            color: #34d399;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .pulse {
            width: 8px;
            height: 8px;
            background: #34d399;
            border-radius: 50%;
            box-shadow: 0 0 0 rgba(52, 211, 153, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(52, 211, 153, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="logo">VILELA ENGENHARIA</div>
        
        <div class="loader"></div>
        
        <h1>Área do Cliente<br>em Atualização</h1>
        
        <p>Estamos implementando melhorias significativas na sua experiência. Em breve, uma nova plataforma mais rápida e moderna estará disponível.</p>
        
        <!-- Optional: Link de contato via WhatsApp se o cliente precisar de algo urgente -->
        <!-- <a href="#" class="btn">Entrar em contato</a> -->

        <div class="status-badge">
            <div class="pulse"></div>
            Sistema em manutenção programada
        </div>
    </div>
</body>
</html>
