<?php
// Script para adicionar coluna de Link do Drive
// Rodar uma vez via browser: /area-cliente/setup_drive_link.php

require 'db.php';

try {
    // Tenta adicionar a coluna se não existir
    // Nota: MySQL não tem "ADD COLUMN IF NOT EXISTS" nativo em versões antigas, 
    // então vamos tentar rodar e ignorar erro de "duplicate column" ou checar schemas.
    // abordagem simples: try/catch
    
    $check = $pdo->query("SHOW COLUMNS FROM processo_detalhes LIKE 'link_drive_pasta'");
    
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN link_drive_pasta TEXT AFTER cliente_id");
        echo "Coluna 'link_drive_pasta' adicionada com sucesso!";
    } else {
        echo "Coluna 'link_drive_pasta' já existe.";
    }

} catch (PDOException $e) {
    die("Erro ao atualizar banco: " . $e->getMessage());
}
?>
