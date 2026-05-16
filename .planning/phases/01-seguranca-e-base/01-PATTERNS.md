# Phase 1: Segurança e Base - Pattern Map

**Mapped:** 2026-05-16
**Files analyzed:** 6
**Analogs found:** 5 / 6

---

## File Classification

| Novo/Modificado | Role | Data Flow | Analog mais próximo | Qualidade |
|-----------------|------|-----------|---------------------|-----------|
| `area-cliente/db.php` | config | request-response | `area-cliente/db.php` (atual) | exact — reescrita do mesmo arquivo |
| `area-cliente/.env.example` | config | — | `area-cliente/db.example.php` | exact — mesmo propósito de template público |
| `area-cliente/.htaccess` | middleware | request-response | `.htaccess` (raiz) | role-match — mesma diretiva Apache, escopo diferente |
| `.github/workflows/deploy.yml` | config (CI/CD) | file-I/O | `.github/workflows/deploy.yml` (atual) | exact — atualização do mesmo arquivo |
| `.gitignore` | config | — | `.gitignore` (atual) | exact — atualização do mesmo arquivo |
| `area-cliente/admin_config.php` | utility (admin) | file-I/O | `area-cliente/admin_config.php` (atual) | exact — correção do bloco de senha no mesmo arquivo |

---

## Pattern Assignments

### `area-cliente/db.php` (config, request-response) — REESCRITA

**Analog:** `area-cliente/db.php` (arquivo atual) + `area-cliente/db.example.php`

**Padrão atual a SUBSTITUIR** (linhas 1–24 de `area-cliente/db.php`):
```php
<?php
// Configurações do Banco de Dados
$host = 'srv1074.hstgr.io';
$db   = 'u884436813_cliente';
$user = 'u884436813_vilela';
$pass = 'Diego@159753';
$charset = 'utf8mb4';

// Configurações Gerais
define('ADMIN_PASSWORD', 'VilelaAdmin2025');
```

**Padrão PDO a PRESERVAR** (linhas 12–24 de `area-cliente/db.php`):
```php
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em produção, a mensagem será capturada pelo index.php
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
```

**Novo padrão de abertura com parse_ini_file()** (substitui linhas 1–11):
```php
<?php
$env = parse_ini_file(__DIR__ . '/.env');

if ($env === false) {
    http_response_code(503);
    die('Erro de configuração do servidor.');
}

$host    = $env['DB_HOST']    ?? '';
$db      = $env['DB_NAME']    ?? '';
$user    = $env['DB_USER']    ?? '';
$pass    = $env['DB_PASS']    ?? '';
$charset = 'utf8mb4';

define('ADMIN_PASSWORD', $env['ADMIN_PASSWORD'] ?? '');
```

**Interface pública que DEVE ser preservada** (consumida por todos os `require_once 'db.php'` do projeto):
- Variável `$pdo` (objeto PDO)
- Constante `ADMIN_PASSWORD`

---

### `area-cliente/.env.example` (config) — NOVO

**Analog:** `area-cliente/db.example.php` (linhas 1–24)

**Padrão do template existente a copiar** (db.example.php linhas 1–7):
```php
<?php
// Exemplo de configuração - Renomeie para db.php e coloque seus dados reais

// Configurações do Banco de Dados
$host = 'localhost';
$db   = 'u123456789_nome_do_banco'; // Seu banco na Hostinger
$user = 'u123456789_usuario';       // Seu usuário do banco
$pass = 'SUA_SENHA_AQUI';           // Sua senha do banco
```

**Formato do novo .env.example** (INI puro, sem PHP — cópia do padrão de comentários do db.example.php aplicado ao formato KEY=VALUE):
```ini
# area-cliente/.env.example
# Renomeie para .env e preencha com os valores reais
# NUNCA commite o arquivo .env com valores reais

DB_HOST=srv1074.hstgr.io
DB_NAME=u884436813_nome_do_banco
DB_USER=u884436813_usuario
DB_PASS=SUA_SENHA_DO_BANCO_AQUI
ADMIN_PASSWORD=SUA_SENHA_ADMIN_AQUI
```

---

### `area-cliente/.htaccess` (middleware, request-response) — NOVO

**Analog:** `.htaccess` da raiz (linhas 1–17)

**Padrão da raiz a copiar e adaptar** (`.htaccess` raiz linhas 1–17):
```apache
# Arquivo de índice padrão
DirectoryIndex index.html index.php

# Desabilitar listagem de diretórios
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # 1. Forçar HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Diferença de escopo:** O .htaccess da raiz usa `RewriteBase /` e redireciona para HTTPS. O novo `area-cliente/.htaccess` NÃO deve repetir essas regras (já herdadas). Deve conter apenas bloqueio de arquivos sensíveis:

```apache
# Bloquear acesso HTTP direto a arquivos de configuração e credenciais

<FilesMatch "^(db\.php|\.env)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Bloquear diretório config/ inteiro
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^config/ - [F,L]
</IfModule>
```

**Sintaxe:** `Order Allow,Deny` / `Deny from all` (compatível com Apache 2.2 e 2.4 — padrão Hostinger). Alternativa Apache 2.4 pura: `Require all denied` dentro de `<Files>`.

---

### `.github/workflows/deploy.yml` (config CI/CD, file-I/O) — ATUALIZAÇÃO

**Analog:** `.github/workflows/deploy.yml` atual (linhas 1–40)

**Estrutura atual a preservar** (linhas 1–26 de `deploy.yml`):
```yaml
name: Deploy para Hostinger via FTP

on:
  push:
    branches:
      - main

jobs:
  deploy:
    name: Upload via FTP
    runs-on: ubuntu-latest

    steps:
      - name: Checkout do repositório
        uses: actions/checkout@v4

      - name: Deploy via FTP para Hostinger
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.FTP_HOST }}
          username: ${{ secrets.FTP_USER }}
          password: ${{ secrets.FTP_PASSWORD }}
          protocol: ftps
          port: 21
          server-dir: /domains/vilela.eng.br/public_html/
          local-dir: ./
```

**Step a INSERIR entre `actions/checkout@v4` e `FTP-Deploy-Action`:**
```yaml
      - name: Gerar area-cliente/.env a partir dos Secrets
        run: |
          {
            echo "DB_HOST=${{ secrets.DB_HOST }}"
            echo "DB_NAME=${{ secrets.DB_NAME }}"
            echo "DB_USER=${{ secrets.DB_USER }}"
            echo "DB_PASS=${{ secrets.DB_PASS }}"
            echo "ADMIN_PASSWORD=${{ secrets.ADMIN_PASSWORD }}"
          } > area-cliente/.env
```

**`exclude` list atual** (linhas 27–40 de `deploy.yml`) — a substituir pela lista expandida:
```yaml
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/*.bkp
            **/desktop.ini
            **/propostas_sistema.md
            **/README.md
            **/.planning/**
            **/banco de dados/**
            **/debug_*.php
            **/login_test.php
            **/test_*.php
```

**Nova `exclude` list** (adiciona entradas faltantes identificadas no RESEARCH.md):
```yaml
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/*.bkp
            **/desktop.ini
            **/propostas_sistema.md
            **/README.md
            **/.planning/**
            **/banco de dados/**
            **/debug_*.php
            **/debug_*.html
            **/login_test.php
            **/test_*.php
            **/session_test_*.php
            **/probe.php
            **/seed_test_user.php
            **/hello.php
            area-cliente/tools/**
            area-cliente/client-app/debug_*.php
```

---

### `.gitignore` (config) — ATUALIZAÇÃO

**Analog:** `.gitignore` atual (linhas 1–42)

**Linha a REMOVER** (linha 27 do `.gitignore` atual):
```
# Credenciais reais — use db.example.php como template
area-cliente/db.php
```

**Linhas a ADICIONAR** (após a entrada `.env` existente na linha 17):
```
# .env gerado pelo deploy — credenciais reais
area-cliente/.env
```

**Entradas de debug existentes a verificar/manter** (linhas 19–35 do `.gitignore` atual — já cobre maioria dos arquivos de debug):
```
debug_live.html
debug_*.php
login_test.php
test_*.php
```

**Entradas de debug faltantes a ADICIONAR** (identificadas no RESEARCH.md como existentes no disco mas não cobertas):
```
area-cliente/session_test_*.php
area-cliente/probe.php
area-cliente/hello.php
```

---

### `area-cliente/admin_config.php` (utility, file-I/O) — FIX no bloco de senha

**Analog:** `area-cliente/admin_config.php` atual (arquivo completo lido)

**Bloco a SUBSTITUIR** (linhas 20–45 de `admin_config.php`):
```php
// 1. Alterar Senha Admin
if (isset($_POST['update_password'])) {
    $new_pass = trim($_POST['new_password']);
    if (strlen($new_pass) < 6) {
        $msg = "❌ A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Read db.php
        $db_file = 'db.php';
        $content = file_get_contents($db_file);
        
        // Replace constant define('ADMIN_PASSWORD', '... Old ...');
        // Pattern: define('ADMIN_PASSWORD',\s*'[^']*');
        $pattern = "/define\('ADMIN_PASSWORD',\s*'([^']*)'\);/";
        $replacement = "define('ADMIN_PASSWORD', '$new_pass');";
        
        if (preg_match($pattern, $content)) {
            $new_content = preg_replace($pattern, $replacement, $content);
            if (file_put_contents($db_file, $new_content)) {
                $msg = "✅ Senha alterada com sucesso! A nova senha já está valendo.";
            } else {
                $msg = "❌ Erro ao escrever no arquivo db.php. Verifique as permissões.";
            }
        } else {
            $msg = "❌ Não foi possível localizar a definição de senha no arquivo db.php.";
        }
    }
}
```

**Novo bloco** (mesma posição, lógica de escrita no .env em vez de db.php):
```php
// 1. Alterar Senha Admin
if (isset($_POST['update_password'])) {
    $new_pass = trim($_POST['new_password']);
    if (strlen($new_pass) < 6) {
        $msg = "❌ A senha deve ter pelo menos 6 caracteres.";
    } else {
        $env_file = __DIR__ . '/.env';
        $env = parse_ini_file($env_file);
        if ($env === false) {
            $msg = "❌ Erro: Arquivo .env não encontrado. Contate o suporte.";
        } else {
            $env['ADMIN_PASSWORD'] = $new_pass;
            $lines = [];
            foreach ($env as $key => $value) {
                $lines[] = $key . '=' . $value;
            }
            if (file_put_contents($env_file, implode("\n", $lines) . "\n") !== false) {
                $msg = "✅ Senha alterada com sucesso! Faça logout e login para ativar.";
            } else {
                $msg = "❌ Erro ao escrever no arquivo .env. Verifique as permissões (chmod 664).";
            }
        }
    }
}
```

**Resto do arquivo** (linhas 47–267) — preservar sem alteração. Apenas o bloco do `update_password` muda.

**Aviso de runtime:** `define('ADMIN_PASSWORD', ...)` não pode ser redefinido na mesma requisição PHP. Após alterar o .env, a constante na sessão atual ainda tem o valor antigo — o admin deve fazer logout/login. A mensagem de sucesso já orienta isso.

---

## Shared Patterns

### Padrão de inclusão de db.php
**Fonte:** Todos os arquivos PHP em `area-cliente/` que usam `require_once 'db.php'` ou `require 'db.php'`
**Aplica-se a:** `db.php` — a interface pública (`$pdo`, `ADMIN_PASSWORD`) deve ser idêntica antes e depois da reescrita.
```php
require_once 'db.php';
// Após o require: $pdo está disponível, ADMIN_PASSWORD está definida
```

### Padrão de template de configuração pública
**Fonte:** `area-cliente/db.example.php` (linhas 1–3)
```php
<?php
// Exemplo de configuração - Renomeie para db.php e coloque seus dados reais
```
**Aplica-se a:** `area-cliente/.env.example` — seguir o mesmo padrão de comentário "Renomeie para .env".

### Padrão PDO com ERRMODE_EXCEPTION
**Fonte:** `area-cliente/db.php` (linhas 13–16) e `area-cliente/db.example.php` (linhas 11–14)
```php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
```
**Aplica-se a:** `area-cliente/db.php` (reescrita) — manter exatamente estas opções.

### Padrão de Secrets no GitHub Actions
**Fonte:** `.github/workflows/deploy.yml` (linhas 20–22)
```yaml
server: ${{ secrets.FTP_HOST }}
username: ${{ secrets.FTP_USER }}
password: ${{ secrets.FTP_PASSWORD }}
```
**Aplica-se a:** Step de geração do .env no `deploy.yml` — seguir o mesmo padrão `${{ secrets.NOME }}`.

### Padrão Apache .htaccess — IfModule mod_rewrite.c
**Fonte:** `.htaccess` raiz (linhas 7–17)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    ...
</IfModule>
```
**Aplica-se a:** `area-cliente/.htaccess` — usar `<IfModule mod_rewrite.c>` ao redor de qualquer diretiva RewriteRule, sem `RewriteBase` (não é raiz do domínio).

---

## No Analog Found

Nenhum arquivo sem analog nesta fase. Todos os 6 arquivos têm analog direto no projeto:

| Arquivo | Motivo do analog disponível |
|---------|----------------------------|
| `area-cliente/db.php` | É reescrita do próprio arquivo |
| `area-cliente/.env.example` | `db.example.php` serve como modelo direto |
| `area-cliente/.htaccess` | `.htaccess` raiz é modelo Apache do mesmo projeto |
| `.github/workflows/deploy.yml` | É atualização do próprio arquivo |
| `.gitignore` | É atualização do próprio arquivo |
| `area-cliente/admin_config.php` | É fix cirúrgico no próprio arquivo |

---

## Metadata

**Escopo de busca de analogs:** `area-cliente/`, `.github/workflows/`, raiz do projeto
**Arquivos lidos:** 6 (db.php, db.example.php, deploy.yml, .gitignore, admin_config.php, .htaccess raiz)
**Data de extração:** 2026-05-16
