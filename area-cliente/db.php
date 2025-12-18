<?php
// db.php
$host = 'localhost';
// PREENCHA AQUI COM SEUS DADOS DA HOSTINGER
$db   = 'u123456789_NOME_DO_BANCO';
$user = 'u123456789_USUARIO';
$pass = 'SUA_SENHA_FORTE';
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
    // Em produção, evite exibir detalhes do erro para o usuário
    die("Erro na conexão com o banco de dados. Verifique o arquivo db.php.");
}
?>
