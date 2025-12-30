<?php
require 'db.php';
try {
    // Add foto_perfil column if it doesn't exist
    $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL");
    echo "Coluna 'foto_perfil' adicionada com sucesso ou já existente.";
} catch (Exception $e) {
    echo "Nota: " . $e->getMessage();
}
?>
