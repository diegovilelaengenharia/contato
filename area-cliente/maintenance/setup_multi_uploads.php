<?php
require 'db.php';

try {
    // Tabela para múltiplos arquivos por pendência
    $sql = "CREATE TABLE IF NOT EXISTS processo_pendencias_arquivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pendencia_id INT NOT NULL,
        arquivo_nome VARCHAR(255) NOT NULL,
        arquivo_path VARCHAR(255) NOT NULL,
        data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pendencia_id) REFERENCES processo_pendencias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
    $pdo->exec($sql);
    echo "Tabela processo_pendencias_arquivos criada/verificada com sucesso!<br>";

    // Criar pasta de uploads se não existir
    $uploadDir = __DIR__ . '/uploads/pendencias';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            echo "Pasta de uploads verificada: $uploadDir<br>";
        }
    } else {
        echo "Pasta de uploads já existe.<br>";
    }

} catch (PDOException $e) {
    echo "Erro ao atualizar tabela: " . $e->getMessage();
}
?>
