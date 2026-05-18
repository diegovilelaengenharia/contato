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

// Definições globais úteis para o legado.
// Reusa a fonte de credenciais que Database.php já carregou em getInstance() acima,
// evitando duplicar a lógica de fallback e o warning de .env ausente.
if (!defined('ADMIN_PASSWORD')) {
    define('ADMIN_PASSWORD', Database::getConfig('ADMIN_PASSWORD') ?? '');
}
