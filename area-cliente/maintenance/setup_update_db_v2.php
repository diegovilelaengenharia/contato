<?php
// setup_update_db_v2.php
// Adiciona coluna para Tipo de Responsável Técnico
require 'db.php';

try {
    // 1. Adicionar tipo_responsavel em processo_detalhes
    $col = 'tipo_responsavel';
    $check = $pdo->query("SHOW COLUMNS FROM processo_detalhes LIKE '$col'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN $col VARCHAR(50) AFTER zoneamento"); // Posição aproximada
        echo "Coluna '$col' adicionada.<br>";
    } else {
        echo "Coluna '$col' já existe.<br>";
    }

    // 2. Verificar Tabela de Movimentos (Debug para o erro relatado)
    // Se não existir, cria.
    $sqlMov = "CREATE TABLE IF NOT EXISTS processo_movimentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        titulo_fase VARCHAR(255) NOT NULL,
        descricao TEXT,
        data_movimento DATETIME DEFAULT CURRENT_TIMESTAMP,
        status_tipo VARCHAR(50) DEFAULT 'tramite',
        anexo_url VARCHAR(255),
        departamento_origem VARCHAR(100),
        departamento_destino VARCHAR(100)
    )";
    $pdo->exec($sqlMov);
    echo "Tabela 'processo_movimentos' verificada/criada.<br>";

    echo "Atualização V2 Concluída.";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
