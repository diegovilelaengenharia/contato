# Vilela Engenharia - Portal & Landing Page

Sistema completo de acompanhamento de processos para a Vilela Engenharia, incluindo Landing Page institucional, Portal do Cliente e Painel Administrativo.

## Conteúdo do Projeto

- **Landing Page (Raiz):** Interface institucional mobile-first com PWA (Manifest + Service Worker).
- **Portal do Cliente (`/area-cliente/client-app/`):** Dashboard para clientes acompanharem timeline, pendências, financeiro e baixarem documentos finais.
- **Painel Administrativo (`/area-cliente/admin.php`):** Gestão completa de clientes, processos, lançamentos financeiros e uploads.

## Tecnologias e Arquitetura

- **Frontend:** HTML5, CSS3 (Vanilla), JavaScript (ES6).
- **Backend:** PHP 8.x (Arquitetura modular em `/area-cliente/core/`).
- **Banco de Dados:** MySQL/MariaDB (PDO).
- **Segurança:** Proteção CSRF, hashing de senhas (bcrypt), bloqueio de diretórios via `.htaccess` e gestão de segredos via `.env`.
- **Deploy:** Workflow automatizado via GitHub Actions para Hostinger (FTP).

## Como Instalar (Desenvolvimento)

1. Clone o repositório.
2. Configure o banco de dados usando os schemas em `.planning/intel/database_schema.sql` (ou rode os scripts em `area-cliente/maintenance/` em ambiente seguro).
3. Crie o arquivo `area-cliente/db.php` a partir do `db.example.php`.
4. Crie o arquivo `area-cliente/.env` com as credenciais (DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD).

## Deploy para Produção

O deploy é automático ao realizar push para a branch `main`. 
Os segredos devem ser configurados nos **GitHub Secrets** do repositório:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `ADMIN_PASSWORD`
- `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`

## Manutenção

- O sistema possui um "Modo Manutenção" que pode ser ativado no banco de dados para bloquear acesso de clientes.
- Logs de erro são gerados automaticamente pelo PHP se configurado no servidor.
- Os uploads são armazenados em `area-cliente/uploads/`.

---
*Vilela Engenharia - Regularização e Aprovação de Imóveis*
