<?php
ob_start();

// Segurança: Não exibir erros em produção. Logs devem ser consultados via servidor.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Session Configuration (S5: cookies seguros)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite'  => 'Lax',
]);
session_name('CLIENTE_SESSID');
session_start();

// Database Connection
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../core/Auth.php';
require __DIR__ . '/../core/Csrf.php';
require __DIR__ . '/../core/Processo.php';

try {
    require __DIR__ . '/../db.php';
} catch (Throwable $e) {
    // Erro crítico: Logar e mostrar mensagem amigável 503
    error_log("Erro Crítico Vilela: " . $e->getMessage());
    http_response_code(503);
    die("<h1>Serviço Temporariamente Indisponível</h1><p>Estamos realizando uma manutenção rápida. Por favor, tente novamente em instantes.</p>");
}

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        error_log("Erro Fatal Vilela: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        if (!headers_sent()) {
            http_response_code(500);
        }
        die("<h1>Ocorreu um erro interno</h1><p>Nossa equipe técnica foi notificada. Pedimos desculpas pelo transtorno.</p>");
    }
});

// --- SELF-HEALING DATABASE (Manter compatibilidade por enquanto) ---
try {
    $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN data_nascimento DATE DEFAULT NULL");
} catch (Exception $e) { }

// Verifica Sessão Admin
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Lógica do Popup de Boas Vindas (Apenas 1x por sessão)
$show_welcome_popup = false;
if (!isset($_SESSION['welcome_shown'])) {
    $show_welcome_popup = true;
    $_SESSION['welcome_shown'] = true;
}

// Logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
