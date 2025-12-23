<?php
require 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS pre_cadastros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cpf_cnpj VARCHAR(50),
        rg VARCHAR(30),
        estado_civil VARCHAR(50),
        profissao VARCHAR(100),
        email VARCHAR(255),
        telefone VARCHAR(50),
        endereco_residencial TEXT,
        endereco_obra VARCHAR(255),
        matricula_imovel VARCHAR(100),
        inscricao_municipal VARCHAR(100),
        area_terreno VARCHAR(50),
        area_construida VARCHAR(50),
        tipo_servico VARCHAR(100),
        mensagem TEXT,
        data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pendente', 'aprovado', 'arquivado') DEFAULT 'pendente',
        ip_origem VARCHAR(45)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // Auto-migration for existing tables
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS rg VARCHAR(30)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS estado_civil VARCHAR(50)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS profissao VARCHAR(100)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS endereco_residencial TEXT");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS matricula_imovel VARCHAR(100)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS inscricao_municipal VARCHAR(100)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS area_terreno VARCHAR(50)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS area_construida VARCHAR(50)");
    $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN IF NOT EXISTS procurador_legal VARCHAR(255)");

    $pdo->exec($sql);
    echo "Tabela 'pre_cadastros' criada ou verificada com sucesso!<br>";
    echo "Agora a página pública de cadastro funcionará.";

} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
?>
