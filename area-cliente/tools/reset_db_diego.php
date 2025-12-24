<?php
// tools/reset_db_diego.php
// Script para resetar clientes mantendo apenas Diego e redefinindo-o como ID 1

require '../db.php';

session_start();
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    die("Acesso negado. Faça login como admin.");
}

$nome_alvo = 'Diego'; // Nome ou parte do nome para identificar o cliente master

echo "<h1>Reset de Banco de Dados</h1>";
echo "<p>Iniciando processo...</p>";

try {
    $pdo->beginTransaction();

    // 1. Identificar o ID atual do Diego
    $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE nome LIKE ? LIMIT 1");
    $stmt->execute(['%' . $nome_alvo . '%']);
    $diego = $stmt->fetch();

    if (!$diego) {
        throw new Exception("Cliente 'Diego' não encontrado para preservação.");
    }

    $old_id = $diego['id'];
    echo "<p>Cliente encontrado: {$diego['nome']} (ID Atual: $old_id)</p>";

    // 2. Apagar TODOS os outros clientes
    $stmtDel = $pdo->prepare("DELETE FROM clientes WHERE id != ?");
    $stmtDel->execute([$old_id]);
    echo "<p>Outros clientes removidos.</p>";

    // 3. Atualizar ID do Diego para 1 (se já não for)
    if ($old_id != 1) {
        // Preciso desativar verificações de chave estrangeira temporariamente se o banco for rigoroso
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

        // Atualizar tabela clientes
        $stmtUp = $pdo->prepare("UPDATE clientes SET id = 1 WHERE id = ?");
        $stmtUp->execute([$old_id]);

        // Atualizar tabelas relacionadas
        $tables = [
            'processo_detalhes', 
            'processo_movimentos', 
            'processo_pendencias', 
            'processo_arquivos', 
            'processo_financeiro'
        ];

        foreach ($tables as $table) {
            // Verifica se tabela existe (por segurança, em base MySQL simples as vezes muda)
            try {
                $check = $pdo->query("SHOW TABLES LIKE '$table'");
                if($check->rowCount() > 0) {
                    $upTbl = $pdo->prepare("UPDATE $table SET cliente_id = 1 WHERE cliente_id = ?");
                    $upTbl->execute([$old_id]);
                    echo "<p>Tabela $table atualizada (ID $old_id -> 1).</p>";
                }
            } catch(Exception $eTbl) {
                echo "<p>Aviso na tabela $table: " . $eTbl->getMessage() . "</p>";
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        echo "<p>ID do Diego atualizado para 1 com sucesso.</p>";
    } else {
        echo "<p>Diego já é o ID 1. Nenhuma mudança de ID necessária.</p>";
    }

    // 4. Resetar AUTO_INCREMENT para 2 (para o próximo ser o 2)
    $pdo->exec("ALTER TABLE clientes AUTO_INCREMENT = 2");
    echo "<p>Contador Auto Increment resetado para 2.</p>";

    $pdo->commit();
    echo "<h2 style='color:green'>SUCESSO! Banco resetado. Diego agora é o Cliente #1.</h2>";
    echo "<a href='../gestao_admin_99.php'>Voltar para Admin</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color:red'>ERRO FATAL: " . $e->getMessage() . "</h2>";
}
?>
