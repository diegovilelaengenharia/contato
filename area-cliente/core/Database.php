<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Tenta vários caminhos em ordem de preferência.
        // db_config.ini (sem dotfile) é o caminho novo — Hostinger às vezes filtra dotfiles via FTP.
        $candidates = [
            __DIR__ . '/../db_config.ini',     // area-cliente/db_config.ini  (preferido)
            __DIR__ . '/../../db_config.ini',  // <root>/db_config.ini
            __DIR__ . '/../../.env',           // <root>/.env                 (legacy)
            __DIR__ . '/../.env',              // area-cliente/.env           (legacy fallback)
        ];

        $env_path = null;
        foreach ($candidates as $c) {
            if (file_exists($c)) {
                $env_path = $c;
                break;
            }
        }

        if ($env_path === null) {
            $msg = "ERRO CRITICO: Nenhum arquivo de credenciais encontrado. Caminhos tentados: " . implode(', ', $candidates);
            error_log($msg);
            header('HTTP/1.1 503 Service Unavailable');
            die($msg);
        }

        $env = parse_ini_file($env_path);
        if ($env === false) {
            $msg = "ERRO CRITICO: Arquivo de credenciais corrompido em: " . realpath($env_path);
            error_log($msg);
            header('HTTP/1.1 503 Service Unavailable');
            die($msg);
        }

        $host    = $env['DB_HOST'] ?? '';
        $db      = $env['DB_NAME'] ?? '';
        $user    = $env['DB_USER'] ?? '';
        $pass    = $env['DB_PASS'] ?? '';
        $charset = 'utf8mb4';

        if (empty($host) || empty($db) || empty($user)) {
            $msg = "ERRO CRITICO: Variaveis de ambiente do banco de dados estao incompletas no .env.";
            error_log($msg);
            header('HTTP/1.1 503 Service Unavailable');
            die($msg);
        }

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            $msg = "Erro de conexão com o banco de dados. Detalhe técnico: " . $e->getMessage();
            error_log("DB CONNECTION ERROR: " . $e->getMessage());
            header('HTTP/1.1 503 Service Unavailable');
            die($msg);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}
