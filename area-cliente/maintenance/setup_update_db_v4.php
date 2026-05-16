<?php
require 'db.php';

try {
    $pdo->exec("
        ALTER TABLE processo_detalhes
        ADD COLUMN link_pasta_pagamentos TEXT AFTER link_drive_pasta;
    ");
    echo "Coluna 'link_pasta_pagamentos' adicionada com sucesso na tabela processo_detalhes.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Coluna 'link_pasta_pagamentos' já existe. Nenhuma alteração feita.";
    } else {
        die("Erro ao atualizar tabela: " . $e->getMessage());
    }
}
?>
