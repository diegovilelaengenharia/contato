<?php
// Script para adicionar colunas de Links Específicos
// Rodar uma vez via browser: /area-cliente/setup_links_especificos.php

require 'db.php';

try {
    $cols = ['link_doc_iniciais', 'link_doc_pendencias', 'link_doc_finais'];
    
    foreach ($cols as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM processo_detalhes LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN $col TEXT AFTER link_drive_pasta");
            echo "Coluna '$col' adicionada.<br>";
        } else {
            echo "Coluna '$col' já existe.<br>";
        }
    }
    echo "Concluído.";

} catch (PDOException $e) {
    die("Erro ao atualizar banco: " . $e->getMessage());
}
?>
