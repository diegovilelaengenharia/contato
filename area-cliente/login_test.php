<?php
// login_test.php - Tracing Login Logic
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Lógica de Login</h1>";

echo "<p>1. Carregando db.php...</p>";
try {
    require 'db.php';
    echo "<p style='color:green'>✅ db.php carregado.</p>";
} catch (Throwable $e) {
    die("<p style='color:red'>❌ Erro db.php: " . $e->getMessage() . "</p>");
}

echo "<p>2. Verificando Tabela Admin Settings...</p>";
try {
    $stmt = $pdo->query("SELECT * FROM admin_settings LIMIT 1");
    echo "<p style='color:green'>✅ Tabela admin_settings existe.</p>";
} catch (Throwable $e) {
    echo "<p style='color:orange'>⚠️ Tabela admin_settings falhou: " . $e->getMessage() . " (Isso pode quebrar o check de manutenção)</p>";
}

echo "<p>3. Simulando busca de usuário (teste com 'admin')...</p>";
try {
    $user = 'admin';
    // Logic from index.php
    $senhaMestraAdmin = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'VilelaAdmin2025'; 
    echo "<p>Senha Mestra Definida: $senhaMestraAdmin</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Erro lógica admin: " . $e->getMessage() . "</p>";
}

echo "<p>4. Testando Consulta de Cliente...</p>";
try {
    $stmt = $pdo->prepare("SELECT * FROM clientes LIMIT 1");
    $stmt->execute();
    $cliente = $stmt->fetch();
    if($cliente) {
        echo "<p style='color:green'>✅ Tabela clientes lida com sucesso. Cliente ID: " . $cliente['id'] . "</p>";
    } else {
         echo "<p style='color:orange'>⚠️ Tabela clientes vazia ou erro.</p>";
    }
} catch (Throwable $e) {
     echo "<p style='color:red'>❌ Erro tabela clientes: " . $e->getMessage() . "</p>";
}

echo "<p>5. Teste de Redirecionamento (Simulado)</p>";
echo "<p>Se você vê isso, o PHP processou tudo sem Fatal Error.</p>";
?>
