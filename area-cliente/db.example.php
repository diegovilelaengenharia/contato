<?php
// Exemplo de configuração - Renomeie para db.php e coloque seus dados reais

// Configurações do Banco de Dados
$host = 'localhost';
$db   = 'u123456789_nome_do_banco'; // Seu banco na Hostinger
$user = 'u123456789_usuario';       // Seu usuário do banco
$pass = 'SUA_SENHA_AQUI';           // Sua senha do banco
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em produção, evite mostrar o erro detalhado para o usuário
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
