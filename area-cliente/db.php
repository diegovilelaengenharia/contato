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

// Executa migrations pendentes ANTES de ler as credenciais do banco.
// A classe Migrations é idempotente: só roda migrations que ainda não foram executadas.
// Isso garante que a migration 002 (admin_password_db) rode na primeira requisição após o deploy.
if (!defined('MIGRATIONS_RAN')) {
    define('MIGRATIONS_RAN', true);
    try {
        require_once __DIR__ . '/core/Migrations.php';
        Migrations::run();
    } catch (Exception $e) {
        error_log("AVISO: Falha ao executar migrations em db.php: " . $e->getMessage());
        // Não é fatal — continua com as credenciais disponíveis
    }
}

// Definições globais úteis para o legado.
// Prioridade: banco de dados (admin_settings) > .env / db_credentials.php
// Isso permite que a senha seja alterada pelo portal sem editar arquivos no servidor.
if (!defined('ADMIN_PASSWORD')) {
    $adminPassFromDb = null;
    try {
        $stmtPass = $pdo->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'admin_password' LIMIT 1");
        if ($stmtPass) {
            $adminPassFromDb = $stmtPass->fetchColumn() ?: null;
        }
    } catch (Exception $e) {
        // Tabela ainda não existe (primeira execução antes da migration) — usa fallback
    }
    define('ADMIN_PASSWORD', $adminPassFromDb ?? Database::getConfig('ADMIN_PASSWORD') ?? '');
}

// Usuário(s) admin válidos: lê do banco ou usa padrão.
if (!defined('ADMIN_USERNAMES')) {
    $adminUsernameFromDb = null;
    try {
        $stmtUser = $pdo->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'admin_username' LIMIT 1");
        if ($stmtUser) {
            $adminUsernameFromDb = $stmtUser->fetchColumn() ?: null;
        }
    } catch (Exception $e) {
        // Tabela ainda não existe — usa padrão
    }
    // Normaliza para minúsculas para comparação case-insensitive
    $defaultUsernames = ['admin', 'vilela', 'vilela adm'];
    if ($adminUsernameFromDb) {
        $fromDb = strtolower(trim($adminUsernameFromDb));
        if (!in_array($fromDb, $defaultUsernames)) {
            $defaultUsernames[] = $fromDb;
        }
    }
    define('ADMIN_USERNAMES', $defaultUsernames);
}

