-- Migration 001: Criação da Tabela de Logs de Auditoria
-- Esta tabela registrará todas as ações críticas no painel admin

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL, -- Ex: CREATE, UPDATE, DELETE, LOGIN
    entity VARCHAR(50) NOT NULL, -- Ex: cliente, financeiro, pendencia
    entity_id INT NULL,
    payload_json TEXT NULL,      -- Dados enviados na requisição
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de controle de migrations se não existir
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
