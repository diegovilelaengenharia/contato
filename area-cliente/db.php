<?php
/**
 * db.php - Wrapper de Compatibilidade
 * Este arquivo permite que o código legado que utiliza a variável global $pdo 
 * continue funcionando enquanto migramos para a arquitetura modular core/Database.php.
 */

require_once __DIR__ . '/core/Database.php';

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    error_log("FALHA NA COMPATIBILIDADE DB: " . $e->getMessage());
    http_response_code(503);
    die("Erro interno no servidor de banco de dados.");
}

// Definições globais úteis para o legado
if (!defined('ADMIN_PASSWORD')) {
    $env_path = __DIR__ . '/../.env';
    if (!file_exists($env_path)) $env_path = __DIR__ . '/.env';
    $env_legacy = parse_ini_file($env_path);
    define('ADMIN_PASSWORD', $env_legacy['ADMIN_PASSWORD'] ?? '');
}
