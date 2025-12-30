<?php
// Configurações do Banco de Dados
$host = 'localhost';
$db   = 'u884436813_cliente';
$user = 'u884436813_vilela';
$pass = 'Diego@159753';
$charset = 'utf8mb4';

// Configurações Gerais
define('ADMIN_PASSWORD', 'VilelaAdmin2025');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em produção, a mensagem será capturada pelo index.php
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
