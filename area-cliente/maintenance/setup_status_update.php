<?php
require 'db.php';

try {
    // Adicionar 'anexado' ao ENUM de status
    // Nota: Em MySQL, alterar ENUM requer reescrever a definição completa
    $sql = "ALTER TABLE processo_pendencias MODIFY COLUMN status ENUM('pendente', 'resolvido', 'anexado') DEFAULT 'pendente'";
            
    $pdo->exec($sql);
    echo "Tabela processo_pendencias atualizada com sucesso! Novo status 'anexado' permitido.<br>";

} catch (PDOException $e) {
    echo "Erro ao atualizar tabela: " . $e->getMessage();
}
?>
