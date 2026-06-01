<?php
/**
 * init_admin.php — Bootstrap centralizado para páginas do portal administrativo.
 *
 * Garante segurança, cookies de sessão protegidos, inicialização de banco,
 * carregamento de classes de núcleo (core) e verificação rígida de sessão admin.
 */

// 1. Session com cookies seguros (S5)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_name('CLIENTE_SESSID');
    session_start();
}

// 2. Produção: erros vão para o log do servidor, não para a tela (vazamento de info)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 3. Banco de dados (carrega $pdo via db.php -> Database::getInstance())
try {
    require_once __DIR__ . '/../db.php';
} catch (Throwable $e) {
    error_log("Erro de Conexão no Admin: " . $e->getMessage());
    http_response_code(503);
    die("<h1>Serviço Temporariamente Indisponível</h1><p>Ocorreu um problema ao conectar ao banco de dados. Tente novamente mais tarde.</p>");
}

// 4. Carrega Classes de Core
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Processo.php';

// 5. Verificação Estrita de Sessão do Administrador
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: ../index.php");
    exit;
}

// 6. Configura constantes úteis
define('ADMIN_PATH', __DIR__);
define('APP_VERSION', '2.0.0');

// Lógica de logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
