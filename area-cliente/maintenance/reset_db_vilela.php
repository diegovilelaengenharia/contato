<?php
/**
 * Script de Manutenção: Reset de Banco de Dados e Carga Inicial
 * Caminho: area-cliente/maintenance/reset_db_vilela.php
 * 
 * Este script limpa de forma segura todas as tabelas transacionais e do portal
 * do cliente, restabelecendo a base para um estado limpo, contendo apenas 
 * um cliente de teste padrão e fornecendo orientações de login.
 * 
 * Segurança: Exige chave de autenticação quando acessado via web (?token=vilela_reset_2026).
 */

require_once __DIR__ . '/../core/Database.php';

// Proteção de Acesso
$is_cli = (php_sapi_name() === 'cli');
$token_secreto = 'vilela_reset_2026';

if (!$is_cli) {
    // Acesso via Browser exige token
    $token_informado = $_GET['token'] ?? '';
    $admin_password_env = Database::getConfig('ADMIN_PASSWORD') ?? '';

    // Permite logar via token estático ou via senha mestra do admin
    $auth_ok = false;
    if (!empty($token_informado)) {
        if ($token_informado === $token_secreto || ($admin_password_env !== '' && $token_informado === $admin_password_env)) {
            $auth_ok = true;
        }
    }

    if (!$auth_ok) {
        header('HTTP/1.1 403 Forbidden');
        die("
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Acesso Negado - Vilela Engenharia</title>
            <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap' rel='stylesheet'>
            <style>
                body { font-family: 'Outfit', sans-serif; background-color: #f4f7f6; color: #333; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 450px; }
                h1 { color: #dc3545; font-size: 24px; margin-top: 0; }
                p { color: #666; font-size: 15px; line-height: 1.6; }
                .badge { background: #fee2e2; color: #dc3545; padding: 6px 12px; border-radius: 99px; font-weight: 600; font-size: 13px; display: inline-block; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <span class='badge'>🔒 Acesso Restrito</span>
                <h1>Acesso Não Autorizado</h1>
                <p>Este script executa ações destrutivas no banco de dados e exige autenticação.<br>Por favor, informe a chave correta via parâmetro na URL para prosseguir.</p>
            </div>
        </body>
        </html>
        ");
    }
}

$erro = null;
$sucesso = false;
$detalhes_log = [];

try {
    $pdo = Database::getInstance();

    // Inicia processo de limpeza
    $detalhes_log[] = "Conexão com o banco de dados estabelecida com sucesso.";

    // Desativa temporariamente as foreign keys para poder limpar sem restrições
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $detalhes_log[] = "Restrições de chave estrangeira desativadas temporariamente.";

    // Tabelas transacionais a serem limpas
    $tabelas = [
        'processo_pendencias',
        'processo_docs_entregues',
        'processo_financeiro',
        'processo_movimentos',
        'processo_campos_extras',
        'processo_entregaveis',
        'login_attempts',
        'audit_log',
        'pre_cadastros',
        'processo_detalhes',
        'clientes'
    ];

    foreach ($tabelas as $tabela) {
        $pdo->exec("TRUNCATE TABLE `$tabela`;");
        $detalhes_log[] = "Tabela `$tabela` limpa com sucesso (Truncated).";
    }

    // Reativa as foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    $detalhes_log[] = "Restrições de chave estrangeira reativadas.";

    // Criação do cliente padrão de teste
    $nome_cliente = "Cliente Teste";
    $usuario_cliente = "cliente";
    $senha_cliente_plana = "cliente123";
    $senha_hash = password_hash($senha_cliente_plana, PASSWORD_DEFAULT);

    // Insere cliente
    $stmt = $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)");
    $stmt->execute([$nome_cliente, $usuario_cliente, $senha_hash]);
    $novo_cliente_id = $pdo->lastInsertId();
    $detalhes_log[] = "Cliente de teste padrão cadastrado com ID: $novo_cliente_id.";

    // Insere detalhes do processo imobiliário padrão do cliente
    $sql_detalhes = "INSERT INTO processo_detalhes (
        cliente_id, tipo_pessoa, cpf_cnpj, contato_tel, contato_email, tipo_servico,
        imovel_rua, imovel_numero, imovel_bairro, imovel_cidade, imovel_uf,
        tipo_processo_chave, data_inicio
    ) VALUES (?, 'Fisica', '123.456.789-01', '(35) 99999-9999', 'cliente@vilela.eng.br', 'Aprovação de Projeto de Regularização',
        'Avenida Principal', '100', 'Centro', 'Oliveira', 'MG', 'regularizacao', NOW()
    )";
    $pdo->prepare($sql_detalhes)->execute([$novo_cliente_id]);
    $detalhes_log[] = "Detalhes do processo padrão inseridos para o cliente.";

    // Opcional: Adiciona um movimento inicial na timeline do processo do cliente para fins de teste visual
    $sql_movimento = "INSERT INTO processo_movimentos (
        cliente_id, titulo_fase, descricao, data_movimento, status_tipo, tipo_movimento
    ) VALUES (?, 'Abertura do Processo', 'Seu processo de regularização imobiliária foi iniciado com sucesso no sistema da Vilela Engenharia.', NOW(), 'tramite', 'padrao')";
    $pdo->prepare($sql_movimento)->execute([$novo_cliente_id]);
    $detalhes_log[] = "Movimento inicial de boas-vindas cadastrado na timeline.";

    $sucesso = true;

} catch (Exception $e) {
    $erro = $e->getMessage();
    $detalhes_log[] = "ERRO DURANTE O RESET: " . $erro;
}

if ($is_cli) {
    if ($sucesso) {
        echo "RESET CONCLUÍDO COM SUCESSO!\n";
        echo "---------------------------\n";
        echo "Acessos configurados:\n";
        echo "- ADMIN: Login com usuário 'admin' ou 'vilela' e a Senha Mestra definida no seu arquivo .env\n";
        echo "- CLIENTE: Login: 'cliente' | Senha: 'cliente123'\n";
    } else {
        echo "FALHA NO RESET: " . $erro . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset de Banco de Dados - Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #197e63;
            --color-success: #198754;
            --color-danger: #dc3545;
            --color-bg: #f4f7f6;
            --color-text: #2d3748;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            max-width: 650px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 10px 0 5px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header p {
            color: #718096;
            margin: 0;
            font-size: 15px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 99px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .status-badge.success {
            background-color: #d1e7dd;
            color: var(--color-success);
        }

        .status-badge.error {
            background-color: #f8d7da;
            color: var(--color-danger);
        }

        .credentials-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .credentials-box h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .credential-item:last-child {
            margin-bottom: 0;
        }

        .credential-label {
            font-weight: 600;
            color: #4a5568;
        }

        .credential-value {
            font-family: monospace;
            background: #edf2f7;
            padding: 2px 8px;
            border-radius: 4px;
            color: #2d3748;
            font-size: 14px;
        }

        .log-section {
            background: #1e293b;
            color: #38bdf8;
            font-family: monospace;
            padding: 20px;
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 25px;
            text-align: left;
        }

        .log-line {
            margin-bottom: 4px;
        }

        .btn-action {
            display: block;
            width: 100%;
            background-color: var(--color-primary);
            color: white;
            text-align: center;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s;
            box-sizing: border-box;
        }

        .btn-action:hover {
            background-color: #12634d;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($sucesso): ?>
                <div class="status-badge success">
                    <span>✓</span> Banco de Dados Resetado
                </div>
                <h1>Sucesso!</h1>
                <p>O banco de dados foi limpo e os acessos iniciais foram criados.</p>
            <?php else: ?>
                <div class="status-badge error">
                    <span>✕</span> Erro no Reset
                </div>
                <h1>Falha na Operação</h1>
                <p>Ocorreu um erro ao tentar reconfigurar o banco de dados.</p>
            <?php endif; ?>
        </div>

        <?php if ($sucesso): ?>
            <!-- Acessos Configurados -->
            <div class="credentials-box">
                <h3>🔑 Seus Acessos Disponíveis</h3>
                
                <div class="credential-item" style="border-bottom: 1px dashed #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
                    <div class="credential-label">👨‍💼 Administrador (Diego)</div>
                    <div>
                        <span class="credential-label">Login:</span> <span class="credential-value">admin</span> ou <span class="credential-value">vilela</span><br>
                        <span class="credential-label" style="font-size: 11px; color: #718096; display: block; text-align: right; margin-top: 4px;">Utilize a Senha Mestra cadastrada no arquivo .env</span>
                    </div>
                </div>
                
                <div class="credential-item">
                    <div class="credential-label">👤 Cliente de Teste</div>
                    <div>
                        <span class="credential-label">Login:</span> <span class="credential-value">cliente</span><br>
                        <span class="credential-label">Senha:</span> <span class="credential-value">cliente123</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Logs de Execução -->
        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #4a5568;">Log de Execução:</h4>
        <div class="log-section">
            <?php foreach ($detalhes_log as $log): ?>
                <div class="log-line">> <?= htmlspecialchars($log) ?></div>
            <?php endforeach; ?>
        </div>

        <?php if ($sucesso): ?>
            <a href="../index.php" class="btn-action">Ir para a Tela de Login</a>
        <?php else: ?>
            <a href="javascript:location.reload()" class="btn-action" style="background-color: var(--color-danger)">Tentar Novamente</a>
        <?php endif; ?>

        <div class="footer">
            Vilela Engenharia &copy; <?= date('Y') ?> · Ambiente de Manutenção Seguro
        </div>
    </div>
</body>
</html>
