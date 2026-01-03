<?php
// Script para criar cliente de teste "Davidson Nunes Vilela" com dados completos
// Execute via terminal ou browser: php populate_test_client.php

require __DIR__ . '/db.php';

// FUNÇÃO AUXILIAR PARA CORRIGIR TABELA (SELF-HEALING)
function TryAddColumn($pdo, $table, $col, $type) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $col $type");
            echo "Coluna <strong>$col</strong> adicionada na tabela $table.<br>";
        }
    } catch (Exception $e) {
        // Ignora erro se já existir ou outro prob
    }
}

try {
    // --- 0. GARANTIR COLUNAS NOVAS (FIX EVERYTHING) ---
    echo "<h3>Verificando Estrutura do Banco de Dados...</h3>";
    
    // Tabela processo_detalhes
    TryAddColumn($pdo, 'processo_detalhes', 'observacoes_gerais', 'TEXT NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'valor_venal', 'DECIMAL(15,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'area_total_final', 'DECIMAL(10,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'area_existente', 'DECIMAL(10,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'area_acrescimo', 'DECIMAL(10,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'area_permeavel', 'DECIMAL(10,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'taxa_ocupacao', 'DECIMAL(10,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'fator_aproveitamento', 'DECIMAL(10,2) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'geo_coords', 'VARCHAR(100) NULL');
    TryAddColumn($pdo, 'processo_detalhes', 'foto_capa_obra', 'VARCHAR(255) NULL');

    echo "<hr>";
    
    $pdo->beginTransaction();

    echo "<h1>Recriando Cliente Teste...</h1>";

    // 1. Dados Básicos do Cliente
    $nome = "Davidson Nunes Vilela";
    $login = "33333333333"; // CPF Dummy
    $senha_plain = "mudar123";
    $senha_hash = password_hash($senha_plain, PASSWORD_DEFAULT);

    // Verifica se já existe
    $check = $pdo->prepare("SELECT id FROM clientes WHERE usuario = ?");
    $check->execute([$login]);
    $existing = $check->fetchColumn();

    if ($existing) {
        $cid = $existing;
        echo "Cliente já existe (ID: $cid). Atualizando dados...<br>";
        $pdo->prepare("UPDATE clientes SET nome=?, senha=? WHERE id=?")->execute([$nome, $senha_hash, $cid]);
        
        // Limpa dados antigos para re-popular
        $pdo->prepare("DELETE FROM processo_movimentos WHERE cliente_id=?")->execute([$cid]);
        $pdo->prepare("DELETE FROM processo_financeiro WHERE cliente_id=?")->execute([$cid]);
        $pdo->prepare("DELETE FROM processo_pendencias WHERE cliente_id=?")->execute([$cid]);
        $pdo->prepare("DELETE FROM processo_detalhes WHERE cliente_id=?")->execute([$cid]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $login, $senha_hash]);
        $cid = $pdo->lastInsertId();
        echo "Cliente Criado (ID: $cid)<br>";
    }

    // 2. Detalhes Completos (Processo + Imóvel + Contato)
    $sqlDetails = "INSERT INTO processo_detalhes (
        cliente_id, 
        etapa_atual,
        processo_numero,
        processo_objeto,
        data_nascimento,
        cpf_cnpj,
        rg_ie,
        nacionalidade,
        estado_civil,
        profissao,
        contato_email,
        contato_tel,
        res_rua, res_numero, res_bairro, res_cidade, res_uf,
        imovel_rua, imovel_numero, imovel_bairro, imovel_cidade, imovel_uf,
        inscricao_imob, num_matricula, imovel_area_lote, area_construida,
        valor_venal, area_total_final, area_existente, area_acrescimo, area_permeavel, taxa_ocupacao, fator_aproveitamento, geo_coords,
        observacoes_gerais
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";

    $pdo->prepare($sqlDetails)->execute([
        $cid,
        'Análise Técnica (Engenharia)', // Etapa Atual
        'PROC-2024/001', // Número do Processo
        'Alvará de Construção Residencial', // Objeto
        '1985-05-20', // Data Nasc
        '333.333.333-33', // CPF
        'MG-12.345.678', // RG
        'Brasileiro',
        'Casado',
        'Empresário',
        'davidson@teste.com', // Email
        '(35) 99999-8888', // Tel
        'Rua das Palmeiras', '100', 'Centro', 'Oliveira', 'MG', // Residencial
        'Av. Maracanã', '500', 'Jardim Panorâmico', 'Oliveira', 'MG', // Imóvel Obra
        '01.02.003.0045.001', // Inscrição
        '12345 (Lv. 2)', // Matrícula
        '360.00', // Área Lote
        '180.50', // Área Construída
        '450000.00', // Valor Venal
        '180.50', // Área Total Final
        '0.00', // Área Existente
        '180.50', // Área Acréscimo
        '25.00', // Área Permeável (%)
        '50.00', // Taxa Ocupação (%)
        '1.5', // Fator Aproveitamento
        '-20.697418, -44.827364', // GeoCoords
        '⚠️ Cliente solicitou urgência na aprovação do projeto hidrossanitário.' // Observação em Destaque
    ]);
    echo "Detalhes do Processo Inseridos.<br>";

    // 3. Histórico de Movimentações (Timeline)
    $movs = [
        ['Abertura de Processo', '2025-01-10', 'Processo protocolado na Prefeitura sob nº 2024/001.', 'conclusao', 'padrao'],
        ['Vistoria Inicial', '2025-01-15', 'Realizada vistoria no terreno. Topografia confere com projeto.', 'conclusao', 'padrao'],
        ['Análise Documental', '2025-01-20', 'Documentação pessoal e do terreno validada.', 'conclusao', 'padrao'],
        ['Pendência Emitida', '2025-01-25', 'Solicitado ajuste no recuo frontal do projeto arquitetônico.', 'pendencia', 'padrao']
    ];

    $stmtMov = $pdo->prepare("INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo, tipo_movimento) VALUES (?, ?, ?, ?, ?, ?)");
    foreach($movs as $m) {
        $stmtMov->execute([$cid, $m[0], $m[1], $m[2], $m[3], $m[4]]);
    }
    echo "Timeline (4 eventos) Inserida.<br>";

    // 4. Financeiro
    $financas = [
        ['Taxas', 'Taxa de Protocolo Prefeitura', '150.00', '2025-01-10', 'pago'],
        ['Honorários', 'Entrada Projeto Arquitetônico (30%)', '2500.00', '2025-01-10', 'pago'],
        ['Honorários', 'Segunda Parcela', '2500.00', '2025-02-10', 'pendente'],
        ['Taxas', 'Taxa de Alvará', '450.00', '2025-02-15', 'atrasado']
    ];

    $stmtFin = $pdo->prepare("INSERT INTO processo_financeiro (cliente_id, categoria, descricao, valor, data_vencimento, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach($financas as $f) {
        $stmtFin->execute([$cid, $f[0], $f[1], $f[2], $f[3], $f[4]]);
    }
    echo "Financeiro (4 lançamentos) Inserido.<br>";

    // 5. Pendências (Ação do Cliente)
    $pends = [
        ['Enviar comprovante da Taxa de Alvará', 'pendente'],
        ['Assinar planta baixa revisada', 'resolvido']
    ];

    $stmtPend = $pdo->prepare("INSERT INTO processo_pendencias (cliente_id, descricao, status, data_criacao) VALUES (?, ?, ?, NOW())");
    foreach($pends as $p) {
        $stmtPend->execute([$cid, $p[0], $p[1]]);
    }
    echo "Pendências (2 itens) Inseridas.<br>";

    $pdo->commit();
    echo "<h2 style='color:green'>SUCESSO! Cliente 'Davidson Nunes Vilela' criado/resetado.</h2>";
    echo "<p><strong>Login:</strong> 33333333333<br><strong>Senha:</strong> mudar123</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<h2 style='color:red;'>ERRO CRÍTICO: " . $e->getMessage() . "</h2>";
}
