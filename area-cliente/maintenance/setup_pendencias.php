<?php
require 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS processo_pendencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        descricao TEXT NOT NULL,
        status ENUM('pendente', 'resolvido') DEFAULT 'pendente',
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    )");
    echo "Tabela 'processo_pendencias' criada/verificada com sucesso! <br>";
    echo "Pode apagar este arquivo se desejar.";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
?>
