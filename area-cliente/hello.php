<?php
// hello.php - Teste de Vida Básico
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Olá! O PHP está funcionando.</h1>";
echo "<p>Agora vamos testar a conexão com o banco...</p>";

try {
    require 'db.php';
    if(isset($pdo)) {
        echo "<p style='color:green; font-weight:bold;'>✅ Conexão DB Sucesso!</p>";
    } else {
        echo "<p style='color:red;'>❌ DB carregado mas variável $pdo não existe.</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red;'>❌ Falha no DB: " . $e->getMessage() . "</p>";
}
?>
