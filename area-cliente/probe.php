<?php
// probe.php - Diagnóstico de Erros

// 1. Tenta habilitar erros forçadamente
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Status do Sistema</h1>";
echo "<p>✅ PHP está rodando.</p>";

// 2. Tenta conectar ao banco isoladamente
echo "<h2>Teste de Banco de Dados</h2>";
try {
    require 'db.php';
    if(isset($pdo)) {
        echo "<p>✅ Conexão DB bem sucedida.</p>";
    } else {
        echo "<p>❌ Variável \$pdo não definida após include db.php</p>";
    }
} catch (Throwable $e) {
    echo "<p>❌ Erro ao conectar DB: " . $e->getMessage() . "</p>";
}

// 3. Tenta incluir init.php
echo "<h2>Teste de Init</h2>";
try {
    // Definir constantes que o init ou outros arquivos possam precisar
    if(!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', 'teste');
    
    // Mock de sessão para não falhar redirecionamentos
    if(session_status() == PHP_SESSION_NONE) {
        // session_start(); // init já faz start
    }

    require 'includes/init.php';
    echo "<p>✅ includes/init.php carregado.</p>";
} catch (Throwable $e) {
    echo "<p>❌ Erro ao carregar init.php: " . $e->getMessage() . "</p>";
}

// 4. Teste Schema (que parece ser o suspeito)
echo "<h2>Teste de Schema</h2>";
try {
    require 'includes/schema.php';
    echo "<p>✅ includes/schema.php carregado.</p>";
} catch (Throwable $e) {
    echo "<p>❌ Erro Critical no schema.php: " . $e->getMessage() . "</p>";
}

echo "<hr><p>Diagnóstico Finalizado.</p>";
?>
