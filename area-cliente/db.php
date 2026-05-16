<?php
// Configuração carregada de area-cliente/.env via parse_ini_file()
// Nunca edite credenciais diretamente aqui — edite o arquivo .env
$env = parse_ini_file(__DIR__ . '/.env');

if ($env === false) {
    http_response_code(503);
    die('Erro de configuração do servidor. Contate o suporte.');
}

$host    = $env['DB_HOST']    ?? '';
$db      = $env['DB_NAME']    ?? '';
$user    = $env['DB_USER']    ?? '';
$pass    = $env['DB_PASS']    ?? '';
$charset = 'utf8mb4';

// Configurações Gerais
define('ADMIN_PASSWORD', $env['ADMIN_PASSWORD'] ?? '');

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
