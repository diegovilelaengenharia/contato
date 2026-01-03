<?php
// HARDCORE DEBUG PROBE
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Vilela System Probe</h1>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";

// 1. Session Test
echo "<h2>1. Session State</h2>";
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();

echo "Session Name: " . session_name() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . " (2=Active)<br>";

if(empty($_SESSION)) {
    echo "<strong style='color:red'>⚠️ $_SESSION is EMPTY. Login has not persisted.</strong><br>";
} else {
    echo "<strong style='color:green'>✅ Session Data Found:</strong><pre>" . print_r($_SESSION, true) . "</pre>";
}

echo "<h3>Cookies Received:</h3><pre>" . print_r($_COOKIE, true) . "</pre>";

// 2. Database Test
echo "<h2>2. Database Connection</h2>";
try {
    if(file_exists('db.php')) {
        require 'db.php';
        echo "<strong style='color:green'>✅ db.php found and loaded.</strong><br>";
        
        if(isset($pdo)) {
            echo "<strong style='color:green'>✅ PDO Object exists.</strong><br>";
            
            if(isset($_SESSION['cliente_id'])) {
                $id = $_SESSION['cliente_id'];
                echo "Testing User Look-up for ID: $id ...<br>";
                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user) {
                    echo "<strong style='color:green'>✅ User Found in DB:</strong> " . htmlspecialchars($user['nome']) . " (CPF: " . $user['cpf_cnpj'] . ")<br>";
                } else {
                    echo "<strong style='color:red'>❌ User ID $id NOT FOUND in Database!</strong><br>";
                }
            }
        } else {
             echo "<strong style='color:red'>❌ \$pdo variable not set!</strong><br>";
        }
    } else {
        echo "<strong style='color:red'>❌ db.php NOT FOUND!</strong><br>";
    }
} catch(Exception $e) {
    echo "<strong style='color:red'>❌ Exception: " . $e->getMessage() . "</strong><br>";
}

echo "<hr><a href='index.php'>Voltar para Login</a> | <a href='dashboard.php'>Tentar acessar Dashboard</a>";
?>
