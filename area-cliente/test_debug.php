<?php
// Tentar forçar exibição de erros
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
@error_reporting(E_ALL);

echo "<h1>Debug Step-by-Step</h1>";

// 1. PHP Version
echo "Status: PHP Running. Version: " . phpversion() . "<br><hr>";

// 2. File Check
$dbFile = 'db.php';
if (!file_exists($dbFile)) {
    die("<div style='color:red'>ERRO FATAL: Arquivo db.php NAO encontrado neste diretorio.</div>");
}
echo "Status: db.php encontrado.<br>";

// 3. Content Preview (Security: only showing first 50 chars to check for <?php)
$content = file_get_contents($dbFile);
echo "Status: Leitura do db.php OK (" . strlen($content) . " bytes).<br>";
echo "Inicio do arquivo: <code>" . htmlspecialchars(substr($content, 0, 50)) . "...</code><br>";

if (strpos(trim($content), '<?php') !== 0) {
    echo "<div style='color:red'>ALERTA: O arquivo nao parece comecar com &lt;?php. Verifique espacos em branco antes da tag.</div><br>";
}

// 4. Checking Syntax (Basic check via variable extraction logic simulation? No, just include safely)
echo "Status: Tentando incluir db.php...<br>";

// Isolando include para ver se o crash ocorre aqui
try {
    include $dbFile;
    echo "Status: Include executado sem excecao.<br>";
} catch (Exception $e) {
    echo "<div style='color:red'>Excecao ao incluir: " . $e->getMessage() . "</div>";
}

// 5. Test Variables
echo "Status: Verificando variaveis...<br>";
if (isset($host) && isset($db) && isset($user)) {
    echo "<div style='color:green'>Variaveis de conexao detectadas: Host=$host, User=$user, DB=$db</div>";
} else {
    echo "<div style='color:orange'>Variaveis \$host, \$db, \$user NAO detectadas apos include. O arquivo pode estar vazio ou com nomes diferentes.</div>";
}

// 6. Test PDO
echo "Status: Testando conexao PDO...<br>";
if (isset($pdo)) {
    echo "<div style='color:green'>Objeto \$pdo ja existe. Testando query simples...</div>";
    try {
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            echo "<div style='color:green'>Query 'SELECT 1' funcionou! Banco conectado.</div>";
        } else {
            echo "<div style='color:red'>Query falhou.</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color:red'>Erro na query: " . $e->getMessage() . "</div>";
    }
} else {
    echo "Objeto \$pdo nao existe. Tentando criar agora...<br>";
    if (isset($host, $db, $user, $pass)) {
        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            echo "<div style='color:green'>Conexao Manual: SUCESSO!</div>";
        } catch (Exception $e) {
            echo "<div style='color:red'>Conexao Manual: FALHA. " . $e->getMessage() . "</div>";
        }
    } else {
        echo "Nao tenho credenciais para testar conexao.<br>";
    }
}

echo "<hr>Fim do Diagnostico.";
?>
