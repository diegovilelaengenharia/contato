<?php
class Auth {
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(0, '/');
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

    public static function logout() {
        self::initSession();
        session_destroy();
        header("Location: /area-cliente/index.php");
        exit;
    }
}
