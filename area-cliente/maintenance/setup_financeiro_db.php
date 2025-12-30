<?php
require 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS processo_financeiro (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        categoria ENUM('honorarios', 'taxas') NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        data_vencimento DATE NOT NULL,
        status ENUM('pendente', 'pago', 'atrasado', 'isento') DEFAULT 'pendente',
        link_comprovante TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Tabela 'processo_financeiro' configurada.<br>";

    // Adicionar coluna de link de pasta de pagamentos na tabela de detalhes se não existir
    try {
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN link_pasta_pagamentos TEXT");
        echo "Coluna 'link_pasta_pagamentos' adicionada em processo_detalhes.<br>";
    } catch (PDOException $e) {
        // Ignora erro se coluna já existir
    }
    echo "Tabela 'processo_financeiro' criada ou já existente com sucesso!<br>";
    echo "Pode apagar este arquivo se desejar.";

} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
?>
