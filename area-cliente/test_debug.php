<?php
// ATIVAR EXIBIÇÃO DE ERROS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Conexão - Vilela Engenharia</h1>";
echo "<hr>";

// 1. VERIFICAR ARQUIVo
echo "<h3>1. Verificando arquivos:</h3>";
if (file_exists('db.php')) {
    echo "<div style='color:green'>[OK] Arquivo 'db.php' encontrado.</div>";
    
    // Tentar incluir
    try {
        require 'db.php';
        echo "<div style='color:green'>[OK] Arquivo 'db.php' incluído com sucesso.</div>";
    } catch (Throwable $t) {
        echo "<div style='color:red'>[ERRO] Falha ao incluir db.php: " . $t->getMessage() . "</div>";
    }

} else {
    echo "<div style='color:red'>[ERRO] Arquivo 'db.php' NÃO encontrado no diretório atual (" . __DIR__ . ").</div>";
    echo "Verifique se você criou o arquivo no gerenciador de arquivos da Hostinger.";
    exit;
}

// 2. TESTAR VARIÁVEIS (Sem exibir senha)
echo "<h3>2. Configurações carregadas:</h3>";
if(isset($host) && isset($db) && isset($user)) {
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($host) . "</li>";
    echo "<li>Database: " . htmlspecialchars($db) . "</li>";
    echo "<li>User: " . htmlspecialchars($user) . "</li>";
    echo "<li>Senha definida? " . (isset($pass) && !empty($pass) ? "SIM" : "NÃO (ou vazia)") . "</li>";
    echo "</ul>";
} else {
    echo "<div style='color:red'>[ERRO] Variáveis de configuração (\$host, \$db, \$user) não foram definidas no db.php.</div>";
}

// 3. TESTAR CONEXÃO PDO
echo "<h3>3. Teste de Conexão com Banco de Dados:</h3>";
try {
    if (!isset($pdo)) {
        $dsn_check = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn_check, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "<div style='color:green'>[OK] Conexão criada manualmente no teste.</div>";
    } else {
        echo "<div style='color:green'>[OK] Variável \$pdo já existia (criada pelo db.php).</div>";
    }
    
    $attributes = [
        "SERVER_INFO" => PDO::ATTR_SERVER_INFO,
        "DRIVER_NAME" => PDO::ATTR_DRIVER_NAME,
        "CLIENT_VERSION" => PDO::ATTR_CLIENT_VERSION
    ];
    
    foreach ($attributes as $name => $attr) {
        try {
            echo "$name: " . $pdo->getAttribute($attr) . "<br>";
        } catch (Exception $e) {}
    }

} catch (PDOException $e) {
    echo "<div style='color:red'>[FALHA FATAL] Erro ao conectar: " . $e->getMessage() . "</div>";
    echo "<br>Dicas:";
    echo "<ul>";
    echo "<li>Verifique se o usuário '<strong>$user</strong>' tem permissão no banco '<strong>$db</strong>'.</li>";
    echo "<li>Verifique se a senha está correta (espaços extras?).</li>";
    echo "<li>Verifique se o Host é 'localhost' (padrão Hostinger).</li>";
    echo "</ul>";
    exit;
}

// 4. VERIFICAR TABELAS
echo "<h3>4. Verificando Tabelas:</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div style='color:orange'>[AVISO] Conexão feita, mas NENHUMA tabela encontrada no banco.</div>";
    } else {
        echo "<div style='color:green'>[OK] Tabelas encontradas:</div>";
        echo "<ul>";
        foreach ($tables as $t) {
            echo "<li>$t</li>";
        }
        echo "</ul>";
        
        // Verificar se tabela clientes existe
        if(in_array('clientes', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
            echo "<strong>Tabela 'clientes' tem $count registros.</strong>";
        } else {
            echo "<div style='color:red'>[ERRO CRÍTICO] Tabela 'clientes' NÃO existe. O login falhará.</div>";
        }
    }

} catch (Exception $e) {
    echo "<div style='color:red'>Erro ao listar tabelas: " . $e->getMessage() . "</div>";
}
?>
