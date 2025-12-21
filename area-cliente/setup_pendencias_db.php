<?php
require 'db.php';

try {
    // Adiciona coluna texto_pendencias se não existir
    $check = $pdo->query("SHOW COLUMNS FROM processo_detalhes LIKE 'texto_pendencias'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN texto_pendencias TEXT");
        echo "Coluna 'texto_pendencias' criada com sucesso!<br>";
    } else {
        echo "Coluna 'texto_pendencias' já existe.<br>";
    }

    echo "Atualização de banco de dados concluída. Pode fechar esta aba.";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
