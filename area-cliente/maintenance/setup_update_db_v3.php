<?php
require 'db.php';

try {
    $pdo->exec("
        ALTER TABLE processo_detalhes
        ADD COLUMN imovel_rua VARCHAR(255) AFTER endereco_imovel,
        ADD COLUMN imovel_numero VARCHAR(20) AFTER imovel_rua,
        ADD COLUMN imovel_bairro VARCHAR(100) AFTER imovel_numero,
        ADD COLUMN imovel_complemento VARCHAR(100) AFTER imovel_bairro,
        ADD COLUMN imovel_cidade VARCHAR(100) AFTER imovel_complemento,
        ADD COLUMN imovel_uf VARCHAR(2) AFTER imovel_cidade,
        ADD COLUMN imovel_area_lote DECIMAL(10,2) AFTER area_terreno;
    ");
    echo "Colunas adicionadas com sucesso na tabela processo_detalhes.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Colunas já existem. Nenhuma alteração feita.";
    } else {
        die("Erro ao atualizar tabela: " . $e->getMessage());
    }
}
?>
