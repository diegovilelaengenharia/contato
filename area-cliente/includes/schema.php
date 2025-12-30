<?php
// Ensure Table Exists
$pdo->exec("CREATE TABLE IF NOT EXISTS processo_pendencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('pendente', 'resolvido') DEFAULT 'pendente',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
)");

// Update Schema: Add Separate Residential Address Columns
$cols_needed = ['res_rua', 'res_numero', 'res_bairro', 'res_complemento', 'res_cidade', 'res_uf'];
foreach($cols_needed as $col) {
    try {
        $pdo->query("SELECT $col FROM processo_detalhes LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN $col VARCHAR(255) DEFAULT NULL");
    }
}

// Create Dynamic Fields Table
$pdo->exec("CREATE TABLE IF NOT EXISTS processo_campos_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    valor TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
)");
?>
