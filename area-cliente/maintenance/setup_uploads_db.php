<?php
require 'db.php';

try {
    // Adicionar colunas para upload de arquivos
    $sql = "ALTER TABLE processo_pendencias 
            ADD COLUMN arquivo_nome VARCHAR(255) DEFAULT NULL,
            ADD COLUMN arquivo_path VARCHAR(255) DEFAULT NULL,
            ADD COLUMN data_upload DATETIME DEFAULT NULL";
            
    $pdo->exec($sql);
    echo "Tabela processo_pendencias atualizada com colunas de upload com sucesso!<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Colunas já existem na tabela.<br>";
    } else {
        echo "Erro ao atualizar tabela: " . $e->getMessage();
    }
}

// Criar pasta de uploads se não existir
$uploadDir = __DIR__ . '/uploads/pendencias';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "Pasta de uploads criada: $uploadDir<br>";
    } else {
        echo "Erro ao criar pasta de uploads.<br>";
    }
} else {
    echo "Pasta de uploads já existe.<br>";
}
?>
