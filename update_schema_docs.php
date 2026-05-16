<?php
require 'area-cliente/db.php';

try {
    // Check if column exists
    $pdo->query("SELECT arquivo_path FROM processo_docs_entregues LIMIT 1");
    echo "Column arquivo_path already exists.\n";
} catch (Exception $e) {
    echo "Adding arquivo_path column...\n";
    $pdo->exec("ALTER TABLE processo_docs_entregues ADD COLUMN arquivo_path VARCHAR(255) DEFAULT NULL");
}

try {
    // Check if column exists
    $pdo->query("SELECT nome_original FROM processo_docs_entregues LIMIT 1");
    echo "Column nome_original already exists.\n";
} catch (Exception $e) {
    echo "Adding nome_original column...\n";
    $pdo->exec("ALTER TABLE processo_docs_entregues ADD COLUMN nome_original VARCHAR(255) DEFAULT NULL");
}

echo "Schema updated successfully.";
?>
