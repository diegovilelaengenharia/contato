-- Migration 002: Senha Admin no Banco de Dados
-- Move a credencial ADMIN_PASSWORD do .env para a tabela admin_settings.
-- Isso torna a senha gerenciável direto pelo portal, sem editar arquivos no servidor.
-- A senha pode ser alterada a qualquer momento pela tela: Admin > Configurações > Credenciais.

-- Garante que a tabela existe (idempotente)
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insere o usuário e a senha admin padrão.
-- INSERT IGNORE: só executa se a chave ainda NÃO existir (não sobrescreve senha já alterada pelo admin).
INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('admin_username', 'Vilela Adm');
INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('admin_password', '08472320693');
