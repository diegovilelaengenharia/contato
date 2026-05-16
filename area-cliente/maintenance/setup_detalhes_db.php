<?php
// Script para criar a tabela de detalhes do processo
// Rodar uma vez via browser: /area-cliente/setup_detalhes_db.php

require 'db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS processo_detalhes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            
            -- ABA 1: REQUERENTE
            tipo_pessoa ENUM('Fisica', 'Juridica') DEFAULT 'Fisica',
            cpf_cnpj VARCHAR(20),
            rg_ie VARCHAR(20),
            estado_civil VARCHAR(50),
            profissao VARCHAR(100),
            endereco_residencial TEXT,
            contato_email VARCHAR(100),
            contato_tel VARCHAR(20),
            
            -- ABA 2: LOTE E IMÓVEL
            inscricao_imob VARCHAR(50),
            num_matricula VARCHAR(50),
            endereco_imovel TEXT,
            area_terreno DECIMAL(10,2),
            area_construida DECIMAL(10,2),
            zoneamento VARCHAR(50),
            
            -- ABA 3: ENGENHARIA
            resp_tecnico VARCHAR(100),
            registro_prof VARCHAR(50),
            num_art_rrt VARCHAR(50),
            
            -- ABA 4: FINANCEIRO (Status simples)
            status_taxa_aprovacao TINYINT(1) DEFAULT 0, -- 0=Pendente, 1=Pago
            status_issqn TINYINT(1) DEFAULT 0,
            status_multas TINYINT(1) DEFAULT 0,
            
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Tabela 'processo_detalhes' criada/verificada com sucesso!";

} catch (PDOException $e) {
    die("Erro ao configurar banco: " . $e->getMessage());
}
?>
