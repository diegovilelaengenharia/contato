<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Configuration
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();

// Database Connection
try {
    require __DIR__ . '/../db.php';
} catch (Throwable $e) {
    die("<h1>Erro Crítico (Sintaxe ou Banco)</h1><p><strong>Arquivo:</strong> " . $e->getFile() . " <br><strong>Linha:</strong> " . $e->getLine() . "<br><strong>Erro:</strong> " . $e->getMessage() . "</p>");
}

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        die("<h1>Erro Fatal PHP</h1><p><strong>Arquivo:</strong> " . $error['file'] . " <br><strong>Linha:</strong> " . $error['line'] . "<br><strong>Erro:</strong> " . $error['message'] . "</p>");
    }
});

// --- SELF-HEALING DATABASE (Correção de Colunas Faltantes) ---
try {
    $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN data_nascimento DATE DEFAULT NULL");
} catch (Exception $e) { 
    // Ignora erro se coluna já existe
}

// --- Configuração e Segurança ---
$minha_senha_mestra = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'VilelaAdmin2025'; 

// Verifica Sessão
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
