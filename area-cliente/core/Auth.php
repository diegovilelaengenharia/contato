<?php
class Auth {
    /**
     * Parâmetros seguros para o cookie de sessão.
     * secure=true: só envia via HTTPS
     * httponly=true: inacessível via JavaScript (mitiga XSS)
     * samesite=Lax: proteção contra CSRF básica
     */
    private static function cookieParams(): array {
        return [
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ];
    }

    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(self::cookieParams());
            session_name('CLIENTE_SESSID');
            session_start();
        }
    }

    public static function checkAdmin() {
        self::initSession();
        if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
            header("Location: /area-cliente/index.php");
            exit;
        }
    }

    public static function checkClient() {
        self::initSession();
        if (!isset($_SESSION['cliente_id'])) {
            header("Location: /index.html");
            exit;
        }
    }

    /**
     * Logout seguro: limpa variáveis, invalida cookie, destrói sessão.
     */
    public static function logout() {
        self::initSession();

        // 1. Limpa todas as variáveis da sessão
        $_SESSION = [];

        // 2. Invalida o cookie de sessão no navegador
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }

        // 3. Destrói o storage da sessão no servidor
        session_destroy();

        header("Location: /area-cliente/index.php");
        exit;
    }
}
