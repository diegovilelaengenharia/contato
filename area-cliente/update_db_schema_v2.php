<?php
require 'db.php';

try {
    // 1. ZERAR CONTADOR (TRUNCATE removes all data and resets ID to 1)
    $pdo->exec("TRUNCATE TABLE pre_cadastros");
    echo "✅ Tabela de pré-cadastros limpa e contador zerado.<br>";

    // 2. NOVAS COLUNAS (ADD IF NOT EXISTS logic via catch or silent fail)
    // Personal Info
    $cols = [
        "rg VARCHAR(30)",
        "data_nascimento DATE",
        "profissao VARCHAR(100)",
        "estado_civil VARCHAR(50)",
        "nome_conjuge VARCHAR(200)",
        // Address
        "cep VARCHAR(20)",
        "imovel_rua VARCHAR(200)",
        "imovel_numero VARCHAR(50)",
        "imovel_bairro VARCHAR(100)",
        "imovel_cidade VARCHAR(100)",
        "imovel_uf VARCHAR(2)",
        // Property Data
        "imovel_area VARCHAR(50)",
        "imovel_matricula VARCHAR(100)"
    ];

    foreach ($cols as $col) {
        try {
            // Extracts column name for checking
            $parts = explode(" ", $col); 
            $colName = $parts[0];
            
            // Try adding. If it exists, it might fail or we can check first.
            // Simple approach: Add and catch duplicate error.
            $pdo->exec("ALTER TABLE pre_cadastros ADD COLUMN $col");
            echo "✅ Coluna adicionada: $colName<br>";
        } catch (PDOException $e) {
            // Column likely exists
            echo "⚠️ Coluna já existe ou erro: $colName (" . $e->getMessage() . ")<br>";
        }
    }

    // 3. FIX Column Size for Service
    try {
        $pdo->exec("ALTER TABLE pre_cadastros MODIFY COLUMN tipo_servico VARCHAR(255)");
        echo "✅ Coluna 'tipo_servico' expandida para VARCHAR(255).<br>";
    } catch (PDOException $e) { /* ignore */ }

    // 4. UPDATE PROCESSO_DETALHES (Add missing personal fields)
    $cols_detalhes = [
        "data_nascimento DATE",
        "nome_conjuge VARCHAR(200)",
        "imovel_rua VARCHAR(200)",
        "imovel_numero VARCHAR(50)",
        "imovel_bairro VARCHAR(100)",
        "imovel_cidade VARCHAR(100)",
        "imovel_uf VARCHAR(2)"
    ];
    foreach ($cols_detalhes as $col) {
        try {
            $parts = explode(" ", $col);
            $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN $col");
            echo "✅ Detalhes: Coluna adicionada $parts[0]<br>";
        } catch (PDOException $e) { /* ignore */ }
    }

    echo "<hr><h3>Processo de Atualização Concluído!</h3>";

} catch (PDOException $e) {
    echo "<h1>Erro Fatal:</h1>" . $e->getMessage();
}
?>
