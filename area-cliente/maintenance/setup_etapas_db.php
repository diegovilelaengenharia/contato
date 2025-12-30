<?php
// Script para adicionar coluna de Etapa Atual
// Rodar uma vez via browser: /area-cliente/setup_etapas_db.php

require 'db.php';

try {
    $check = $pdo->query("SHOW COLUMNS FROM processo_detalhes LIKE 'etapa_atual'");
    
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN etapa_atual VARCHAR(100) AFTER cliente_id");
        echo "Coluna 'etapa_atual' adicionada com sucesso!";
    } else {
        echo "Coluna 'etapa_atual' já existe.";
    }

} catch (PDOException $e) {
    die("Erro ao atualizar banco: " . $e->getMessage());
}
?>
