<?php
require 'area-cliente/db.php';

$sucesso = false;
$erro = false;

if (isset($_POST['btn_enviar'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO pre_cadastros (
            nome, cpf_cnpj, email, telefone, 
            rg, data_nascimento, profissao, estado_civil, nome_conjuge,
            endereco_obra, tipo_servico, mensagem, ip_origem,
            cep, imovel_rua, imovel_numero, imovel_bairro, imovel_cidade, imovel_area, imovel_matricula,
            inscricao_municipal, area_construida, procurador_legal, endereco_residencial
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Simple address for backward compatibility in listing
        $endereco_imovel_resumo = $_POST['imovel_rua'] . ", " . $_POST['imovel_numero'] . " - " . $_POST['imovel_bairro'];

        $stmt->execute([
            $_POST['nome'],
            $_POST['cpf_cnpj'],
            $_POST['email'],
            $_POST['telefone'],
            // New Fields
            $_POST['rg'] ?? null,
            $_POST['data_nascimento'] ?? null,
            $_POST['profissao'] ?? null,
            $_POST['estado_civil'] ?? null,
            $_POST['nome_conjuge'] ?? null,
            // Main Address
            $endereco_imovel_resumo, 
            $_POST['tipo_servico'],
            $_POST['mensagem'],
            $_SERVER['REMOTE_ADDR'],
            // Individual Address Parts
            $_POST['cep'] ?? null,
            $_POST['imovel_rua'] ?? null,
            $_POST['imovel_numero'] ?? null,
            $_POST['imovel_bairro'] ?? null,
            $_POST['imovel_cidade'] ?? null,
            $_POST['imovel_area'] ?? null,
            $_POST['imovel_matricula'] ?? null,
            // Extra User Requested Fields
            $_POST['inscricao_municipal'] ?? null,
            $_POST['area_construida'] ?? null,
            $_POST['procurador_legal'] ?? null,
            $_POST['endereco_residencial'] ?? null
        ]);
        $sucesso = true;
    } catch (PDOException $e) {
        $erro = "Erro ao enviar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cadastro de Projeto | Vilela Engenharia</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    
    <style>
        :root {
            --primary: #146c43;
            --primary-dark: #0f5132;
            --primary-light: #e8f5e9;
            --text: #2c3e50;
            --text-light: #6c757d;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e9ecef;
            --shadow: 0 10px 40px -10px rgba(0,0,0,0.08);
            --radius: 16px;
        }

        * { box-sizing: border-box; outline: none; -webkit-tap-highlight-color: transparent; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(#146c43 0.5px, transparent 0.5px), radial-gradient(#146c43 0.5px, var(--bg) 0.5px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            background-blend-mode: overlay;
            background-attachment: fixed;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text);
        }

        .container {
            background: var(--card-bg);
            width: 100%;
            max-width: 550px;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255,255,255,0.8);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .brand-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .brand-header img {
            height: 60px;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));
        }

        .brand-header h1 {
            font-size: 1.75rem;
            color: var(--primary);
            margin: 0 0 8px 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-header p {
            font-size: 0.95rem;
            color: var(--text-light);
            margin: 0;
            line-height: 1.5;
        }

        /* Modern Input Group */
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
            margin-left: 2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .material-symbols-rounded {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 20px;
            transition: 0.3s;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 14px 14px 14px 44px; /* Icon space */
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            color: var(--text);
            background: #fdfdfd;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-control:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-control:focus + .material-symbols-rounded {
            color: var(--primary);
        }

        textarea.form-control {
            padding-left: 14px; /* No Icon for TextArea usually */
            resize: vertical;
            min-height: 100px;
        }

        /* Select styling override */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
        }

        /* Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Button */
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(20, 108, 67, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transform: skewX(-20deg);
            left: -150%;
            transition: 0.5s;
        }

        .btn-submit:hover::after {
            left: 150%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            background: var(--primary-dark);
            box-shadow: 0 6px 15px rgba(20, 108, 67, 0.3);
        }

        /* Success State */
        .success-state {
            text-align: center;
            padding: 40px 10px;
        }
        
        .checkmark-circle {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--primary);
        }
        
        .checkmark-circle span {
            font-size: 40px;
        }

        .alert-error {
            background: #fbecec;
            color: #e53935;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #e53935;
            font-size: 0.9rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Mobile */
        @media (max-width: 600px) {
            body { padding: 15px; background-image: none; align-items: flex-start; } /* Allow scrolling better */
            .container { padding: 25px; margin-top: 10px; }
            .grid-2 { grid-template-columns: 1fr; gap: 0; }
            .brand-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <?php if($sucesso): ?>
        <div class="success-state">
            <div class="checkmark-circle">
                <span class="material-symbols-rounded">check</span>
            </div>
            <h2 style="color:var(--primary); margin:0 0 10px 0;">Recebemos seu Cadastro!</h2>
            <p style="color:var(--text-light); line-height:1.6; margin-bottom:30px;">
                Obrigado por confiar na Vilela Engenharia.<br>
                Nossa equipe irá analisar seus dados e entrar em contato em breve pelo telefone informado.
            </p>
            <a href="index.html" class="btn-submit" style="text-decoration:none; background: #fff; color: var(--text); border: 2px solid var(--border); box-shadow:none;">
                Voltar ao Site
            </a>
        </div>
    <?php else: ?>
        <div class="brand-header">
            <img src="assets/logo.png" alt="Vilela Engenharia">
            <h1>Iniciar Projeto</h1>
            <p>Preencha os dados abaixo para dar o primeiro passo na realização do seu sonho.</p>
        </div>

        <?php if($erro): ?>
            <div class="alert-error">
                <span class="material-symbols-rounded">error</span>
                <span><?= $erro ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- 1. Dados Pessoais -->
            <h3 style="color:var(--primary); margin:20px 0 15px; border-bottom:1px solid #eee; padding-bottom:5px;">1. Dados do Cliente (Requerente/Proprietário)</h3>
            
            <div class="input-group">
                <label>Nome Completo</label>
                <div class="input-wrapper">
                    <input type="text" name="nome" class="form-control" required placeholder="Nome completo do requerente">
                    <span class="material-symbols-rounded">person</span>
                </div>
            </div>
            
            <div class="grid-2">
                <div class="input-group">
                    <label>CPF ou CNPJ</label>
                    <div class="input-wrapper">
                        <input type="text" name="cpf_cnpj" class="form-control" required placeholder="000.000.000-00">
                        <span class="material-symbols-rounded">badge</span>
                    </div>
                </div>
                <div class="input-group">
                    <label>Documento de Identidade (RG)</label>
                    <div class="input-wrapper">
                        <input type="text" name="rg" class="form-control" placeholder="00.000.000">
                        <span class="material-symbols-rounded">id_card</span>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                 <div class="input-group">
                    <label>Profissão</label>
                    <div class="input-wrapper">
                        <input type="text" name="profissao" class="form-control" placeholder="Sua profissão">
                        <span class="material-symbols-rounded">work</span>
                    </div>
                </div>
                <div class="input-group">
                    <label>Estado Civil (Para conferência com a matrícula)</label>
                    <div class="input-wrapper">
                        <select name="estado_civil" class="form-control">
                            <option value="Solteiro(a)">Solteiro(a)</option>
                            <option value="Casado(a)">Casado(a)</option>
                            <option value="Divorciado(a)">Divorciado(a)</option>
                            <option value="Viúvo(a)">Viúvo(a)</option>
                            <option value="União Estável">União Estável</option>
                        </select>
                        <span class="material-symbols-rounded">diversity_3</span>
                    </div>
                </div>
            </div>
            
            <div class="input-group">
                <label>Endereço Completo de Residência (Rua, nº, Bairro, CEP, Cidade/UF)</label>
                <div class="input-wrapper">
                    <input type="text" name="endereco_residencial" class="form-control" placeholder="Ex: Rua X, 100, Centro, 37550-000, Pouso Alegre/MG">
                    <span class="material-symbols-rounded">home</span>
                </div>
            </div>

            <div class="grid-2">
                <div class="input-group">
                    <label>Informações de Contato (Telefone)</label>
                    <div class="input-wrapper">
                        <input type="tel" name="telefone" class="form-control" required placeholder="(35) 99999-9999">
                        <span class="material-symbols-rounded">call</span>
                    </div>
                </div>
                <div class="input-group">
                    <label>Informações de Contato (E-mail)</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-control" placeholder="seu@email.com">
                        <span class="material-symbols-rounded">mail</span>
                    </div>
                </div>
            </div>
            
            <div class="input-group">
                <label>Procurador Legal (Caso o requerente não seja o proprietário, anexar nome e CPF do representante)</label>
                <div class="input-wrapper">
                    <input type="text" name="procurador_legal" class="form-control" placeholder="Nome e CPF do Representante (Opcional)">
                    <span class="material-symbols-rounded">gavel</span>
                </div>
            </div>

            <!-- 2. Endereço e Imóvel -->
            <h3 style="color:var(--primary); margin:30px 0 15px; border-bottom:1px solid #eee; padding-bottom:5px;">2. Dados do Imóvel</h3>

            <div class="input-group">
                <label>Endereço do Imóvel (Local exato da regularização)</label>
                <div class="input-wrapper">
                    <input type="text" name="imovel_rua" class="form-control" required placeholder="Endereço completo do local da obra/imóvel">
                    <span class="material-symbols-rounded">signpost</span>
                </div>
            </div>
            
            <div class="input-group">
                <label>Tipo de Serviço Desejado</label>
                <div class="input-wrapper">
                    <select name="tipo_servico" class="form-control" required>
                        <option value="Regularização de Imóvel">Regularização de Imóvel</option>
                        <option value="Projeto Arquitetônico">Projeto Arquitetônico</option>
                        <option value="Projeto Estrutural">Projeto Estrutural</option>
                        <option value="Desmembramento">Desmembramento / Unificação</option>
                        <option value="Laudo Técnico">Laudo Técnico</option>
                        <option value="Outros">Outros</option>
                    </select>
                    <span class="material-symbols-rounded">category</span>
                </div>
            </div>

            <div class="grid-2">
                <div class="input-group">
                    <label>Inscrição Cadastral / Imobiliária (Código IPTU)</label>
                    <div class="input-wrapper">
                        <input type="text" name="inscricao_municipal" class="form-control" placeholder="Código de 15 dígitos">
                        <span class="material-symbols-rounded">pin</span>
                    </div>
                </div>
                <div class="input-group">
                    <label>Número da Matrícula (Cartório de Imóveis)</label>
                    <div class="input-wrapper">
                        <input type="text" name="imovel_matricula" class="form-control" placeholder="Registro no Cartório">
                        <span class="material-symbols-rounded">description</span>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="input-group">
                    <label>Área Total do Terreno (m²)</label>
                    <div class="input-wrapper">
                        <input type="text" name="imovel_area" class="form-control" placeholder="Ex: 300.00">
                        <span class="material-symbols-rounded">square_foot</span>
                    </div>
                </div>
                <div class="input-group">
                    <label>Área de Edificação / Construída (m²)</label>
                    <div class="input-wrapper">
                        <input type="text" name="area_construida" class="form-control" placeholder="Ex: 150.00">
                        <span class="material-symbols-rounded">domain</span>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label>Observações Adicionais</label>
                <textarea name="mensagem" class="form-control" placeholder="Descreva brevemente sua necessidade..."></textarea>
            </div>

            <button type="submit" name="btn_enviar" class="btn-submit">
                <span>Enviar Cadastro Completo</span>
                <span class="material-symbols-rounded">arrow_forward</span>
            </button>
        </form>

        <script>
            function buscaCep(cep) {
                if(cep.length < 8) return;
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(r => r.json())
                .then(d => {
                    if(!d.erro) {
                        document.getElementById('rua').value = d.logradouro;
                        document.getElementById('bairro').value = d.bairro;
                        document.getElementById('cidade').value = d.localidade;
                    }
                });
            }
        </script>
        
        <div style="text-align:center; margin-top:20px; font-size:0.8rem; color:#aaa;">
            🔒 Seus dados estão seguros.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
