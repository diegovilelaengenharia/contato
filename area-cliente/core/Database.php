<?php
class Database {
    private static $instance = null;
    private static $env = [];
    private $pdo;

    private function __construct() {
        // 1) Preferido: arquivo PHP retornando array (gerado em CI; mais robusto via FTP)
        $php_creds = __DIR__ . '/db_credentials.php';
        $env = null;

        if (file_exists($php_creds)) {
            $env = require $php_creds;
            if (!is_array($env)) {
                $msg = "ERRO CRITICO: db_credentials.php nao retornou um array.";
                error_log($msg);
                header('HTTP/1.1 503 Service Unavailable');
                die($msg);
            }
        } else {
            // 2) Fallback: arquivos .ini / .env (legacy)
            $candidates = [
                __DIR__ . '/../db_config.ini',
                __DIR__ . '/../../db_config.ini',
                __DIR__ . '/../../.env',
                __DIR__ . '/../.env',
            ];
            $env_path = null;
            foreach ($candidates as $c) {
                if (file_exists($c)) { $env_path = $c; break; }
            }
            if ($env_path === null) {
                $msg = "ERRO CRITICO: Nenhum arquivo de credenciais encontrado. Tentados: db_credentials.php, " . implode(', ', $candidates);
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
        }

        self::$env = $env; // expõe pros consumidores legados (db.php)

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

    /**
     * Retorna um valor de configuração lido da fonte de credenciais
     * (db_credentials.php ou fallback .env/.ini). Garante que a inicialização
     * já ocorreu antes de consultar.
     */
    public static function getConfig($key) {
        if (self::$instance === null) {
            self::getInstance();
        }
        return self::$env[$key] ?? null;
    }
}
