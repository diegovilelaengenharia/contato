<?php
require 'db.php';

try {
    // UPDATE PROCESSO_PENDENCIAS
    // Transformando a tabela simples em um sistema de tarefas
    
    // 1. Adicionar colunas
    $cols = [
        "titulo VARCHAR(255) AFTER cliente_id",
        "tipo ENUM('doc', 'pagamento', 'info', 'geral') DEFAULT 'geral'",
        "prioridade ENUM('alta', 'normal', 'baixa') DEFAULT 'normal'",
        "status ENUM('pendente', 'em_analise', 'resolvido') DEFAULT 'pendente'",
        "quem_resolveu ENUM('cliente', 'admin') DEFAULT NULL",
        "data_conclusao DATETIME DEFAULT NULL",
        "arquivo_anexo VARCHAR(255) DEFAULT NULL" // Para upload do cliente se precisar
    ];

    foreach ($cols as $col) {
        try {
            $parts = explode(" ", $col);
            $pdo->exec("ALTER TABLE processo_pendencias ADD COLUMN $col");
            echo "✅ Coluna adicionada: $parts[0]<br>";
        } catch (PDOException $e) {
            echo "⚠️ Coluna já existe ou erro: " . $e->getMessage() . "<br>";
        }
    }

    echo "<hr><h3>Atualização de Pendências Concluída!</h3>";

} catch (PDOException $e) {
    echo "<h1>Erro Fatal:</h1>" . $e->getMessage();
}
?>
