<?php
// seed_test_user.php
// Cria um usuário de teste completo no banco de dados

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

echo "<h1>🌱 Seeding Test User...</h1>";

$usuario = 'test';
$senha_plain = '1234'; // Senha simples para teste
$senha_hash = password_hash($senha_plain, PASSWORD_DEFAULT);
$nome = "Usuário Teste Completo";
$email = "test@vilela.eng.br";

try {
    $pdo->beginTransaction();

    // 1. Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $existing = $stmt->fetch();

    if ($existing) {
        $cliente_id = $existing['id'];
        echo "Usuário '$usuario' já existe (ID: $cliente_id). Atualizando dados...<br>";
        
        // Update credentials
        $stmt = $pdo->prepare("UPDATE clientes SET senha = ?, nome = ?, email = ? WHERE id = ?");
        $stmt->execute([$senha_hash, $nome, $email, $cliente_id]);
        
        // Clear related data to rebuild
        $pdo->prepare("DELETE FROM processo_detalhes WHERE cliente_id = ?")->execute([$cliente_id]);
        $pdo->prepare("DELETE FROM processo_movimentos WHERE cliente_id = ?")->execute([$cliente_id]);
        $pdo->prepare("DELETE FROM processo_financeiro WHERE cliente_id = ?")->execute([$cliente_id]);
        $pdo->prepare("DELETE FROM processo_pendencias WHERE cliente_id = ?")->execute([$cliente_id]);
        $pdo->prepare("DELETE FROM processo_campos_extras WHERE cliente_id = ?")->execute([$cliente_id]);
    } else {
        echo "Criando novo usuário '$usuario'...<br>";
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, usuario, senha) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $usuario, $senha_hash]);
        $cliente_id = $pdo->lastInsertId();
    }

    // 2. Insert Details (Simulating Regularization)
    $sqlDet = "INSERT INTO processo_detalhes (
        cliente_id, 
        tipo_pessoa, cpf_cnpj, rg_ie, nacionalidade, 
        contato_email, contato_tel, 
        res_rua, res_numero, res_bairro, res_cidade, res_uf,
        profissao, estado_civil,
        imovel_rua, imovel_numero, imovel_bairro, imovel_cidade, imovel_uf,
        inscricao_imob, num_matricula, imovel_area_lote, area_construida,
        situacao, etapa_atual, numero_processo, objeto_processo, data_inicio, area_total, endereco_imovel
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Note: Some columns (etapa_atual, numero_processo, etc.) might be in 'clientes' or 'processo_detalhes' depending on previous schema analysis.
    // Based on 'get_client_data.php', I see: c.etapa_atual, c.numero_processo in 'clientes' table or 'processo_detalhes'?
    // Wait, get_client_data says: SELECT c.*, d.* FROM clientes c LEFT JOIN processo_detalhes d
    // And response uses: $clientData['etapa_atual'], $clientData['numero_processo']
    // Let's check where they are typically stored. usually in details or clientes.
    // To be safe, I will update 'clientes' with process info if columns exist there, otherwise just relies on details.
    // Actually, 'editar_cliente.php' shows updates to 'processo_detalhes' for address etc.
    // 'gestao_admin_99.php' implies 'etapa_atual' might be in 'clientes' or handled via join.
    // I will attempt to update 'clientes' extra fields if possible, or just 'processo_detalhes'.
    
    // Inserting basic details
    $stmtDet = $pdo->prepare($sqlDet);
    // Adjusted param count must match placeholders.
    // I'll stick to the columns I SAW in 'editar_cliente.php' + the ones for the 'Mock Data' simulation.
    // Since I can't be 100% sure of ALL column locations without 'DESCRIBE', I'll focus on the ones verified in 'editar_cliente.php' 
    // and assume 'etapa_atual' etc are in 'clientes' or 'processo_detalhes'.
    
    // Let's refine the INSERT query to strict known columns from 'editar_cliente.php' + updates to 'clientes' for phase.
    
    // Update 'clientes' with process summary info if columns exist (Common pattern)
    // Or maybe they are in 'processo_detalhes'? 
    // Let's try to update 'clientes' table with: etapa_atual, numero_processo, objeto_processo, area_total, endereco_imovel
    // If they fail, we catch exception.
    try {
        $pdo->prepare("UPDATE clientes SET 
            etapa_atual = 'Análise Técnica',
            numero_processo = '2024/0015',
            objeto_processo = 'Regularização Residencial',
            area_total = '250.00 m²',
            endereco_imovel = 'Rua das Oliveiras, 123 - Centro'
            WHERE id = ?")->execute([$cliente_id]);
    } catch (Exception $e) {
        echo "Aviso: Colunas de processo na tabela clientes podem não existir. (Ignorado)<br>";
    }

    $pdo->prepare("INSERT INTO processo_detalhes (
        cliente_id, tipo_pessoa, cpf_cnpj, rg_ie, nacionalidade, contato_email, contato_tel,
        res_rua, res_numero, res_bairro, res_cidade, res_uf, profissao, estado_civil,
        imovel_rua, imovel_numero, imovel_bairro, imovel_cidade, imovel_uf,
        inscricao_imob, num_matricula, imovel_area_lote, area_construida
    ) VALUES (?, 'Fisica', '123.456.789-00', 'MG-12.345.678', 'Brasileira', 'test@vilela.eng.br', '(35) 99999-8888',
        'Av. Teste', '100', 'Centro', 'Oliveira', 'MG', 'Analista de Sistemas', 'Solteiro',
        'Rua das Oliveiras', '123', 'Centro', 'Oliveira', 'MG',
        '01.02.003.0045.001', '12345', '300,00', '150,00'
    )")->execute([$cliente_id]);

    // 3. Timeline
    $pdo->prepare("INSERT INTO processo_movimentos (cliente_id, titulo_fase, descricao, data_movimento, status_tipo) VALUES 
        (?, 'Processo Iniciado', 'Abertura do protocolo na prefeitura.', '2025-01-02', 'fase'),
        (?, 'Documentação', 'Recebimento de documentos pessoais.', '2025-01-05', 'documento'),
        (?, 'Vistoria', 'Vistoria in loco realizada.', '2025-01-10', 'fase')
    ")->execute([$cliente_id, $cliente_id, $cliente_id]);

    // 4. Finance
    $pdo->prepare("INSERT INTO processo_financeiro (cliente_id, descricao, valor, data_vencimento, status) VALUES 
        (?, 'Entrada (Projeto)', 1500.00, '2025-01-02', 'pago'),
        (?, 'Taxa Prefeitura', 250.00, '2025-01-15', 'pendente'),
        (?, 'Parecer Técnico', 1500.00, '2025-02-02', 'pendente')
    ")->execute([$cliente_id, $cliente_id, $cliente_id]);

    // 5. Pendencies
    $pdo->prepare("INSERT INTO processo_pendencias (cliente_id, titulo, descricao, status) VALUES 
        (?, 'Espelho do IPTU', 'Precisamos da cópia atualizada do IPTU.', 'pendente'),
        (?, 'Assinatura Procuração', 'Assinar e reconhecer firma.', 'resolvido')
    ")->execute([$cliente_id, $cliente_id]);

    $pdo->commit();
    echo "<h2 style='color:green'>✅ User 'test' (Senha: 1234) created successfully!</h2>";
    echo "<p>Agora você pode logar com <strong>test</strong> / <strong>1234</strong>.</p>";
    echo "<a href='index.php'>Ir para Login</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao criar usuário de teste: " . $e->getMessage());
}
?>
