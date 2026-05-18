<?php
/**
 * init_client.php — Bootstrap centralizado para páginas do portal cliente (Q5).
 *
 * Substitui o boilerplate repetido (session + db + auth check) em cada arquivo.
 *
 * Uso padrão:
 *   require_once __DIR__ . '/init_client.php';
 *   // $pdo e $cliente_id ficam disponíveis
 *
 * Para páginas com modo simulação admin (ex: timeline.php):
 *   $SKIP_CLIENT_AUTH = true;
 *   require_once __DIR__ . '/init_client.php';
 *   // Faça a lógica de simulação e defina $cliente_id manualmente
 */

// 1. Session com cookies seguros (S5)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite'  => 'Lax',
]);
session_name('CLIENTE_SESSID');
session_start();

// 2. Produção: erros vão pro log, não pro HTML
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 3. Banco de dados (carrega $pdo via db.php → Database::getInstance())
require_once __DIR__ . '/../db.php';

// 4. Auth — só verifica se $SKIP_CLIENT_AUTH não foi definido pelo chamador
if (empty($SKIP_CLIENT_AUTH)) {
    if (!isset($_SESSION['cliente_id'])) {
        header("Location: ../index.php");
        exit;
    }
    $cliente_id = $_SESSION['cliente_id'];
}
