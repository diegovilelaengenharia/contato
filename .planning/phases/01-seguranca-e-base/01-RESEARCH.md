# Phase 1: Segurança e Base - Research

**Researched:** 2026-05-16
**Domain:** PHP security hardening, GitHub Actions deploy, .htaccess Apache, environment variables
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **D-01:** Parse PHP nativo do arquivo `area-cliente/.env` usando `parse_ini_file()` — sem dependências externas, sem Composer, funciona garantido em Hostinger shared hosting.
- **D-02:** Formato KEY=VALUE simples (INI). Ex: `DB_HOST=srv1074.hstgr.io`, `DB_PASS=...`, `ADMIN_PASSWORD=...`
- **D-03:** Localização do .env: `area-cliente/.env` (mesma pasta do db.php).
- **D-04:** Commitar `area-cliente/.env.example` como template público com todas as chaves e valores placeholder.
- **D-05:** Escopo mínimo: bloquear acesso HTTP a `db.php`, `.env`, e `config/` apenas.
- **D-06:** Regras de bloqueio em `area-cliente/.htaccess` separado — não misturar com o .htaccess da raiz.
- **D-07:** Sem headers de segurança HTTP nesta fase.
- **D-08:** Deploy 100% automatizado via GitHub Actions — nenhum arquivo criado ou editado manualmente no servidor.
- **D-09:** GitHub Secrets: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `ADMIN_PASSWORD`.
- **D-10:** `deploy.yml` ganha step que gera `area-cliente/.env` a partir dos GitHub Secrets ANTES do upload via FTP.
- **D-11:** O novo `db.php` (sem credenciais hardcoded) é commitado ao git. Remover `area-cliente/db.php` do `.gitignore`.
- **D-12:** Fresh deploy sobrescreve arquivos stale do servidor.

### Claude's Discretion

- Sintaxe exata das regras .htaccess (Deny from all vs. FilesMatch vs. Order Deny,Allow): planner decide o mais compatível com Hostinger Apache.
- Mecanismo exato de parse do .env: usar `parse_ini_file()` nativo (confirmado).

### Deferred Ideas (OUT OF SCOPE)

- Headers de segurança HTTP (X-Frame-Options, CSP, X-Content-Type-Options) — Fase 8.
- Bloqueio .htaccess de `maintenance/` e `tools/`.
- Rotação de credenciais do banco — ação manual fora do escopo de código.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| SEC-01 | Credenciais do banco de dados em variáveis de ambiente (.env), não no código | parse_ini_file() pattern, db.php rewrite, GitHub Actions step |
| SEC-02 | Senha do admin em .env, não hardcoded no PHP | Mesmo mecanismo do SEC-01; requer fix em admin_config.php (leitura/escrita do .env em vez de db.php) |
| SEC-03 | .htaccess bloqueia acesso direto a db.php, .env e config/ | area-cliente/.htaccess novo com FilesMatch + deny |
| SEC-04 | Scripts de debug/reset removidos do ambiente de produção | Arquivos identificados; solução: excluir via exclude list no deploy.yml |
| SEC-05 | Deploy automático funcional via GitHub Actions → FTPS → Hostinger a cada push em main | deploy.yml já funciona; adicionar step de geração do .env |
</phase_requirements>

---

## Summary

Esta fase é de hardening de segurança em um projeto PHP puro hospedado em Hostinger shared hosting. Não há frameworks, não há Composer — apenas PHP 8+ nativo, MySQL via PDO, e um workflow GitHub Actions que faz FTPS para o servidor. O risco principal é a exposição de credenciais de banco de dados e senha do admin diretamente no código-fonte commitado ao git.

A solução escolhida (parse_ini_file + GitHub Secret para gerar .env antes do deploy) é a abordagem correta e idiomatic para esse stack. O ponto de atenção mais importante desta fase é que `admin_config.php` atualmente reescreve `db.php` para alterar a senha do admin — esse mecanismo quebra completamente quando db.php deixar de conter a senha, e DEVE ser corrigido nesta fase (trocar a lógica de escrita de db.php para escrita do .env).

Os arquivos de debug/test (`debug_admin.php`, `test_*.php`, `session_test_*.php`, `probe.php`, `login_test.php`, `seed_test_user.php`) existem no disco local mas não estão no git — a solução não é deletá-los do git, mas garantir que o deploy.yml os exclua do upload, e adicioná-los ao .gitignore.

**Primary recommendation:** Executar as mudanças nesta ordem: (1) novo db.php, (2) .env.example, (3) step no deploy.yml que gera .env, (4) .htaccess bloqueios, (5) exclusão de debug files do deploy, (6) corrigir admin_config.php para escrever no .env em vez de db.php.

---

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Leitura de credenciais do banco | Backend (PHP) | — | db.php é incluído por require_once em todos os PHP do projeto |
| Proteção de arquivos sensíveis HTTP | Servidor Web (Apache/.htaccess) | — | .htaccess intercepta antes do PHP |
| Geração do .env em produção | CI/CD (GitHub Actions) | — | Secrets vivem no GitHub, não no disco |
| Autenticação de admin | Backend (PHP) | — | Comparação de senha em index.php e init.php |
| Exclusão de arquivos de debug do deploy | CI/CD (GitHub Actions) | .gitignore | exclude list no FTP Action impede upload |

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP nativo | 8+ | parse_ini_file(), PDO | Sem Composer, funciona em shared hosting |
| SamKirkland/FTP-Deploy-Action | v4.3.5 | Upload FTPS para Hostinger | Já em uso no projeto |
| actions/checkout | v4 | Checkout do repositório | Padrão GitHub Actions |

> Versão do FTP-Deploy-Action verificada no arquivo `deploy.yml` existente. [VERIFIED: codebase grep]

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| parse_ini_file() | vlucas/phpdotenv | phpdotenv requer Composer — incompatível com shared hosting sem acesso root |
| parse_ini_file() | getenv() puro | getenv() funciona para vars de ambiente do servidor, mas não lê arquivos .env sem parsing |
| GitHub Actions step para gerar .env | Painel da Hostinger (vars de ambiente) | Painel da Hostinger shared não suporta envvars de sistema; GitHub Actions é a única opção automatizável |

---

## Architecture Patterns

### System Architecture Diagram

```
GitHub push (main)
      |
      v
[GitHub Actions]
  checkout@v4
      |
      v
  [Step: Gerar .env]
  echo "DB_HOST=${{ secrets.DB_HOST }}" > area-cliente/.env
  echo "DB_NAME=..."  >> area-cliente/.env
  echo "DB_PASS=..."  >> area-cliente/.env
  echo "ADMIN_PASSWORD=..." >> area-cliente/.env
      |
      v
  [FTP-Deploy-Action@v4.3.5]
  FTPS → srv1074.hstgr.io
  destino: /domains/vilela.eng.br/public_html/
  local-dir: ./  (inclui area-cliente/.env gerado)
  exclui: debug/*.php, test_*.php, etc.
      |
      v
[Servidor Hostinger — Apache]
  .htaccess raiz: HTTPS, anti-listing (mantido)
  area-cliente/.htaccess: bloqueia db.php, .env, config/
      |
      v
[PHP — área-cliente/db.php]
  parse_ini_file(__DIR__ . '/.env')
  PDO conecta MySQL
  define('ADMIN_PASSWORD', $env['ADMIN_PASSWORD'])
      |
      v
[gestao_admin_99.php / index.php / includes/init.php]
  Lê ADMIN_PASSWORD via constante definida em db.php
```

### Recommended Project Structure (delta desta fase)

```
area-cliente/
├── .env                  # GERADO pelo deploy.yml (nunca no git)
├── .env.example          # Commitado — template público (NOVO)
├── .htaccess             # NOVO — bloqueia db.php, .env, config/
├── db.php                # REESCRITO — lê do .env via parse_ini_file()
│                         # Agora commitado (sem credenciais)
├── admin_config.php      # CORRIGIDO — escreve no .env, não no db.php
└── ...
```

### Pattern 1: parse_ini_file() para .env

**What:** Ler variáveis de um arquivo KEY=VALUE usando a função nativa do PHP.
**When to use:** Sempre que precisar de configuração sem Composer em shared hosting.

```php
// Source: PHP Manual — parse_ini_file()
// area-cliente/db.php (novo)
<?php
$env = parse_ini_file(__DIR__ . '/.env');

if ($env === false) {
    // .env não encontrado — erro crítico, não expor detalhes
    http_response_code(503);
    die('Erro de configuração do servidor.');
}

$host    = $env['DB_HOST']    ?? '';
$db      = $env['DB_NAME']    ?? '';
$user    = $env['DB_USER']    ?? '';
$pass    = $env['DB_PASS']    ?? '';
$charset = 'utf8mb4';

define('ADMIN_PASSWORD', $env['ADMIN_PASSWORD'] ?? '');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
```

> Interface pública preservada: `$pdo` e `ADMIN_PASSWORD` — compatível com todos os includes existentes. [VERIFIED: codebase grep]

### Pattern 2: Geração do .env no GitHub Actions

**What:** Step no workflow que escreve o arquivo .env a partir dos GitHub Secrets antes do upload FTP.
**When to use:** Qualquer arquivo que deve existir em produção mas nunca no git.

```yaml
# Source: GitHub Actions docs — secrets context
# Inserir ANTES do step "Deploy via FTP para Hostinger"
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

> O FTP Action sobe tudo incluindo o .env gerado. O servidor recebe o arquivo em cada deploy. [ASSUMED: comportamento do SamKirkland/FTP-Deploy-Action — upload de arquivos gerados no runner é padrão documentado]

### Pattern 3: .htaccess bloqueio de arquivos sensíveis

**What:** Regras Apache para negar acesso HTTP a arquivos específicos no diretório area-cliente.
**When to use:** Hostinger shared hosting usa Apache com mod_rewrite e suporta .htaccess.

```apache
# Source: Apache HTTP Server docs — FilesMatch directive
# area-cliente/.htaccess (NOVO)

# Bloquear acesso direto a arquivos de configuração e credenciais
<FilesMatch "^(db\.php|\.env)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Bloquear acesso direto ao diretório config/
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^config/ - [F,L]
</IfModule>
```

> Sintaxe `Order Allow,Deny` / `Deny from all` é compatível com Apache 2.2 e 2.4 (Hostinger usa Apache 2.4 mas suporta a sintaxe legada). [ASSUMED: compatibilidade do Hostinger com sintaxe legada — verificar se deploy.yml inclui area-cliente/.htaccess na exclusão] A alternativa moderna Apache 2.4 é `Require all denied` dentro de `<Files>` — mais segura se o módulo authz_core estiver ativo.

### Pattern 4: Correção do admin_config.php (mecanismo de troca de senha)

**PROBLEMA CRÍTICO IDENTIFICADO:** `admin_config.php` atualmente troca a senha do admin fazendo `file_get_contents('db.php')` + regex replace + `file_put_contents`. Com D-11 (db.php commitado sem senha), esse mecanismo quebra — a senha não estará mais em db.php.

**Solução:** Reescrever o bloco de alteração de senha em admin_config.php para:
1. Ler o .env atual com `parse_ini_file()`
2. Atualizar o valor de `ADMIN_PASSWORD`
3. Regravar o .env com os novos valores

```php
// Source: PHP Manual — file_put_contents, parse_ini_file
// admin_config.php — bloco de alteração de senha (substituir lógica existente)
if (isset($_POST['update_password'])) {
    $new_pass = trim($_POST['new_password']);
    if (strlen($new_pass) < 6) {
        $msg = "Erro: A senha deve ter pelo menos 6 caracteres.";
    } else {
        $env_file = __DIR__ . '/.env';
        $env = parse_ini_file($env_file);
        if ($env === false) {
            $msg = "Erro: Arquivo .env não encontrado.";
        } else {
            $env['ADMIN_PASSWORD'] = $new_pass;
            // Regravar KEY=VALUE
            $lines = [];
            foreach ($env as $key => $value) {
                $lines[] = $key . '=' . $value;
            }
            if (file_put_contents($env_file, implode("\n", $lines) . "\n") !== false) {
                // Recarregar constante na sessão atual
                // (ADMIN_PASSWORD já definida — PHP não permite redefine; usar variável de sessão ou redirect)
                $msg = "Senha alterada com sucesso! Recarregue o painel.";
            } else {
                $msg = "Erro ao escrever no arquivo .env. Verifique as permissões.";
            }
        }
    }
}
```

> ATENÇÃO: `define()` não pode ser redefinido em tempo de execução. Após alterar o .env, o `ADMIN_PASSWORD` na sessão atual ainda aponta para o valor antigo. A próxima requisição ao servidor lerá o novo .env. Isso é comportamento correto — o admin deve fazer logout/login após trocar a senha. [VERIFIED: PHP docs — define() é imutável em runtime]

### Anti-Patterns to Avoid

- **Usar `getenv()` sem parse_ini_file():** getenv() lê variáveis de ambiente do processo — em Hostinger shared hosting, o servidor Apache não injeta variáveis de ambiente de arquivos .env automaticamente. Sem parse_ini_file(), getenv() retorna false. [ASSUMED: comportamento do Hostinger shared hosting — verificar se suportam `SetEnv` no .htaccess]
- **Commitar o .env real:** O .env gerado pelo Actions não deve ser adicionado ao git — manter `area-cliente/.env` no .gitignore depois de remover `area-cliente/db.php`.
- **Usar `echo` multiline no Actions sem redirecionamento:** O step de geração do .env deve usar `>>` para append (exceto a primeira linha que usa `>`) ou usar bloco `{ ... } > arquivo` para evitar race conditions de escrita.
- **Não verificar se parse_ini_file() retornou false:** Em produção, se o .env não existir (ex: deploy falhou), db.php tentaria conectar com strings vazias, causando erro obscuro de PDO em vez de mensagem clara.
- **Misturar regras .htaccess da raiz com area-cliente:** A raiz já tem HTTPS redirect e anti-listing — misturar causaria conflitos de RewriteBase.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Parsing de arquivo KEY=VALUE | Parser manual com explode/preg_match | `parse_ini_file()` nativo | Lida com aspas, espaços, comentários, seções |
| Upload FTPS com exclusão de arquivos | Script FTP manual em PHP/Python | SamKirkland/FTP-Deploy-Action | Já testado no projeto, suporta exclude patterns, FTPS nativo |
| Escrita segura de múltiplas variáveis no .env | Regex replace linha por linha | Rebuild completo do arquivo via loop | Regex em INI tem edge cases com caracteres especiais nas senhas |

---

## Runtime State Inventory

> Esta fase envolve rename/refactor de credenciais e mecanismo de configuração.

| Category | Items Found | Action Required |
|----------|-------------|------------------|
| Stored data | Nenhum — credenciais são lidas em runtime, não armazenadas no banco | Nenhuma |
| Live service config | GitHub Secrets: Diego precisa ADICIONAR DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD no painel do repositório (ação manual única, fora do código) | Ação manual de Diego no GitHub — documentar como instrução |
| OS-registered state | Nenhum — sem cron, pm2, ou Task Scheduler envolvidos nesta fase | Nenhuma |
| Secrets/env vars | `.env` local do desenvolvedor: atualmente em `.env` na raiz (coberto pelo .gitignore). O novo .env vai para `area-cliente/.env` — nova localização | Verificar que `area-cliente/.env` está no .gitignore após mudança |
| Build artifacts | `area-cliente/db.php` estava no .gitignore — será REMOVIDO do .gitignore e adicionado ao git (novo conteúdo limpo) | git rm --cached area-cliente/db.php (se tracked), então remover linha do .gitignore |

**Nota sobre db.php no git:** `git ls-files` confirmou que `area-cliente/db.php` NÃO está atualmente no repositório (está no .gitignore). O novo db.php limpo será o primeiro commit desse arquivo. Não é necessário `git rm --cached`. [VERIFIED: git ls-files output]

**Nota sobre arquivos de debug:** `debug_admin.php`, `debug_syntax_admin.php`, `test_*.php`, `session_test_*.php`, `probe.php`, `login_test.php`, `seed_test_user.php` existem no disco local mas NÃO estão no git. A ação necessária é dupla: (1) garantir que estão no .gitignore, (2) garantir que o deploy.yml os exclui do upload FTP. [VERIFIED: git ls-files output]

---

## Common Pitfalls

### Pitfall 1: admin_config.php quebrado após remoção da senha de db.php

**What goes wrong:** O mecanismo de "Alterar Senha" em admin_config.php faz regex replace em db.php. Após D-11, db.php não tem mais a senha — o preg_match retorna false, a senha não muda, e o admin vê "Não foi possível localizar a definição de senha".
**Why it happens:** admin_config.php foi escrito assumindo que db.php é o armazenamento da senha.
**How to avoid:** Corrigir admin_config.php nesta fase para ler/escrever no .env.
**Warning signs:** Mensagem de erro "Não foi possível localizar a definição de senha no arquivo db.php." após o deploy.

### Pitfall 2: .env não gerado = site quebrado

**What goes wrong:** Se o step de geração do .env no GitHub Actions falhar silenciosamente, ou se o arquivo não for subido pelo FTP Action, db.php chama parse_ini_file() e recebe false — PDO tenta conectar com strings vazias e joga PDOException com mensagem de credenciais inválidas.
**Why it happens:** O step de geração do .env escreve num arquivo temporário no runner; se a sintaxe do YAML estiver errada, o arquivo pode ter conteúdo incorreto ou não ser criado.
**How to avoid:** Adicionar verificação de parse_ini_file() retornando false em db.php, com mensagem de erro clara. Testar o workflow manualmente após o primeiro push.
**Warning signs:** Site retorna erro PDO com "Access denied for user ''@'localhost'" (usuário vazio).

### Pitfall 3: db.php no .gitignore bloqueando o commit do arquivo limpo

**What goes wrong:** `area-cliente/db.php` está na linha 27 do .gitignore. Mesmo após reescrever o arquivo sem credenciais, `git add area-cliente/db.php` é silenciosamente ignorado.
**Why it happens:** .gitignore impede git add de arquivos listados.
**How to avoid:** Remover a linha `area-cliente/db.php` do .gitignore ANTES de tentar commitá-lo.
**Warning signs:** `git status` não mostra db.php como "Changes to be committed" mesmo após edição.

### Pitfall 4: FTP Action sobe o .env mas não sobrescreve versão antiga no servidor

**What goes wrong:** FTP-Deploy-Action por padrão usa estado (`.ftp-deploy-sync-state.json`) para subir apenas arquivos modificados. O .env gerado no runner é sempre "novo" (não existia antes no runner), mas o Action pode não detectar mudança se comparar hashes com um .env antigo no servidor.
**Why it happens:** O mecanismo de sync do SamKirkland Action depende de estado local no runner — que é zerado a cada run no ubuntu-latest.
**How to avoid:** Como o runner é stateless (ubuntu-latest fresh a cada job), o sync-state é sempre zerado — o Action SEMPRE sobe todos os arquivos. Este pitfall não se aplica. [ASSUMED: comportamento stateless do GitHub Actions runner — confirmar se o projeto usa cache do sync-state entre runs]
**Warning signs:** .env antigo com senha errada em produção após trocar senha via admin panel.

### Pitfall 5: parse_ini_file() com senhas contendo caracteres especiais

**What goes wrong:** Senhas com `=`, `#`, `;` ou aspas no valor podem ser mal interpretadas pelo parser INI.
**Why it happens:** parse_ini_file() interpreta `#` como início de comentário e `=` como separador de chave-valor.
**How to avoid:** Envolver o valor em aspas duplas no .env gerado: `ADMIN_PASSWORD="senha#com#hash"`. O step do GitHub Actions deve usar formato: `echo "ADMIN_PASSWORD=\"${{ secrets.ADMIN_PASSWORD }}\""`. Alternativamente, garantir que a senha do admin não contenha `#`, `=`, ou `;`.
**Warning signs:** Senha truncada (ex: `senha#especial` vira `senha`).

### Pitfall 6: area-cliente/.env no .gitignore após a mudança

**What goes wrong:** O .gitignore atual bloqueia `.env` na raiz (linha: `.env`) e `area-cliente/db.php`. Após a mudança, o novo arquivo sensível é `area-cliente/.env`. A regra `.env` na raiz do .gitignore cobre arquivos .env em qualquer subdiretório? Depende.
**Why it happens:** Gitignore sem path relativo (`.env` sem `/`) pode ou não cobrir subdiretórios dependendo da versão do git.
**How to avoid:** Adicionar `area-cliente/.env` explicitamente ao .gitignore (com path relativo) para garantia.
**Warning signs:** `git status` mostra `area-cliente/.env` como "Untracked file" — significa que NÃO está ignorado.

---

## Code Examples

### .env.example (a ser commitado)

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

### deploy.yml — step completo de geração do .env

```yaml
# Inserir entre "Checkout do repositório" e "Deploy via FTP para Hostinger"
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

### deploy.yml — exclude list atualizado (debug files)

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

### area-cliente/.htaccess (novo arquivo completo)

```apache
# Bloquear acesso HTTP direto a arquivos de configuração e credenciais

# Bloquear db.php e .env
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

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Credenciais hardcoded em db.php | parse_ini_file() lendo .env | Esta fase | db.php passa a ser seguro para commitar |
| Senha admin definida em db.php via define() | Senha admin no .env, carregada em db.php | Esta fase | admin_config.php precisa de fix |
| debug_*.php, test_*.php excluídos do deploy por padrão de glob | Exclusão explícita no deploy.yml | Esta fase | Garante SEC-04 idempotentemente |

**Deprecated/outdated:**
- Mecanismo de regex replace em db.php para alterar senha do admin (admin_config.php): substituído por rewrite do .env.

---

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | GitHub Actions runner é stateless (ubuntu-latest fresh por job) — sync-state do FTP Action é sempre zerado | Pitfall 4 | Se houver cache de sync-state, o .env novo pode não ser enviado em todo deploy |
| A2 | SamKirkland/FTP-Deploy-Action@v4.3.5 sobe arquivos gerados no runner (como o .env criado no step anterior) | Pattern 2 | Se o Action ignorar arquivos criados após checkout, o .env nunca chegaria ao servidor |
| A3 | Hostinger Apache 2.4 suporta sintaxe legada `Order Allow,Deny` no .htaccess | Pattern 3 | Se apenas `Require all denied` funcionar, o .htaccess precisaria de ajuste |
| A4 | Hostinger shared hosting NÃO injeta variáveis de ambiente via `SetEnv` no .htaccess de forma que getenv() as leia em PHP | Anti-patterns | Se Hostinger suportar SetEnv + getenv(), poderíamos evitar parse_ini_file() (mas D-01 já decidiu usar parse_ini_file()) |

---

## Open Questions

1. **Confirmação de que Diego adicionou os GitHub Secrets**
   - What we know: D-09 define os 5 Secrets necessários (DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD)
   - What's unclear: Se os Secrets já foram adicionados ao repositório no GitHub, ou se isso é uma ação manual que Diego precisa fazer antes do primeiro deploy desta fase
   - Recommendation: Documentar como instrução explícita no plano — o executor deve verificar os Secrets antes de fazer push

2. **Comportamento do FTP Action com arquivo .env gerado no runner**
   - What we know: O step de geração cria `area-cliente/.env` no filesystem do runner após o checkout
   - What's unclear: O SamKirkland Action usa `local-dir: ./` — isso inclui arquivos gerados após o checkout? (A1, A2 acima)
   - Recommendation: Testar no primeiro push e verificar nos logs do Actions se o .env aparece como "uploaded"

3. **Permissões de escrita do .env no servidor para admin_config.php**
   - What we know: O PHP precisa de permissão de escrita em `area-cliente/.env` para que admin_config.php possa alterar a senha
   - What's unclear: Hostinger shared hosting define permissões padrão de arquivo via FTP — arquivos subidos via FTPS são normalmente 644 (leitura pelo owner, sem escrita pelo PHP process)
   - Recommendation: Se file_put_contents() falhar por permissão, instruir Diego a fazer `chmod 664 area-cliente/.env` via File Manager do painel Hostinger

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| GitHub Actions (CI/CD) | SEC-05, D-10 | Confirmado (deploy.yml funcional) | — | Nenhum — D-08 decide deploy 100% automatizado |
| FTP/FTPS Hostinger | SEC-05 | Confirmado (deploy existente funcional) | SamKirkland v4.3.5 | — |
| Apache .htaccess | SEC-03 | Confirmado (raiz já tem .htaccess funcional) | Apache 2.4 (Hostinger padrão) | — |
| PHP parse_ini_file() | SEC-01, D-01 | Disponível em PHP 4+ — garantido em qualquer PHP 8+ | — | — |
| GitHub Secrets (5 vars) | D-09, D-10 | Requer ação manual de Diego no painel GitHub | — | Nenhum — sem Secrets o deploy.yml não gera .env |

**Missing dependencies with no fallback:**
- GitHub Secrets (DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD): Diego deve adicioná-los manualmente no painel do repositório GitHub antes do primeiro push desta fase. Sem eles, o step de geração do .env produz um arquivo com strings vazias e o site quebra.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Nenhum — PHP puro, sem phpunit instalado; testes manuais + verificação de deploy |
| Config file | N/A |
| Quick run command | Abrir `https://vilela.eng.br/area-cliente/` no browser após deploy |
| Full suite command | Ver checklist manual na seção Phase Requirements abaixo |

> nyquist_validation está habilitado no config.json, mas este projeto não tem framework de testes automatizados (PHP puro, sem Composer). Os "testes" desta fase são verificações de comportamento em produção após deploy.

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SEC-01 | db.php não contém strings de senha | Inspeção de código | `git show HEAD:area-cliente/db.php \| grep -v parse_ini` | ❌ Não aplicável — inspeção manual |
| SEC-01 | Site conecta ao banco em produção | Smoke (manual) | Abrir área de cliente no browser | N/A |
| SEC-02 | ADMIN_PASSWORD não está em db.php | Inspeção de código | `git show HEAD:area-cliente/db.php \| grep ADMIN_PASSWORD` | ❌ Inspeção manual |
| SEC-02 | Login admin funciona com senha do .env | Smoke (manual) | Login em `/area-cliente/` com usuario `admin` | N/A |
| SEC-03 | db.php retorna 403 quando acessado diretamente | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/db.php` | N/A |
| SEC-03 | .env retorna 403 quando acessado diretamente | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/.env` | N/A |
| SEC-03 | config/ retorna 403 | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/config/taxas.php` | N/A |
| SEC-04 | debug_admin.php não acessível em produção | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/debug_admin.php` — deve dar 404 | N/A |
| SEC-05 | Push em main dispara deploy sem erro | CI/CD | Ver aba Actions no GitHub após push | N/A |

### Wave 0 Gaps

- [ ] Nenhum arquivo de teste automatizado para criar — fase usa verificação manual/smoke em produção
- [ ] Comandos `curl` de smoke test documentados acima servem como checklist de verificação

*(Framework de testes automatizados PHP seria phpunit — fora de escopo para esta fase e stack)*

---

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | Sim (senha admin) | Senha em .env, comparação plaintext atual — melhoria de hash é escopo futuro |
| V3 Session Management | Não — sem mudanças de sessão nesta fase | — |
| V4 Access Control | Sim (.htaccess bloqueia arquivos) | Apache FilesMatch + Deny |
| V5 Input Validation | Não — sem novos formulários nesta fase | — |
| V6 Cryptography | Não — sem crypto nesta fase | — |
| V7 Error Handling | Sim (db.php deve não expor detalhes) | Mensagem genérica em caso de falha de parse_ini_file() |

### Known Threat Patterns

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Credenciais expostas no git | Information Disclosure | parse_ini_file() + .env no .gitignore |
| Acesso direto a db.php via HTTP | Elevation of Privilege | .htaccess FilesMatch + Deny |
| Acesso direto a .env via HTTP | Information Disclosure | .htaccess FilesMatch + Deny |
| Senha de admin hardcoded no código | Information Disclosure | ADMIN_PASSWORD movido para .env |
| Debug scripts expostos em produção | Information Disclosure | exclude list no deploy.yml + .gitignore |

---

## Sources

### Primary (HIGH confidence)
- PHP Manual — `parse_ini_file()`: https://www.php.net/manual/en/function.parse-ini-file.php
- PHP Manual — `define()`: https://www.php.net/manual/en/function.define.php (imutabilidade em runtime)
- Apache HTTP Server docs — `<FilesMatch>`, `Order/Deny`: https://httpd.apache.org/docs/2.4/mod/core.html#filesmatch
- Codebase grep — `area-cliente/db.php`, `area-cliente/admin_config.php`, `.github/workflows/deploy.yml`, `.gitignore` [VERIFIED: leitura direta dos arquivos]
- git ls-files — confirmação de quais arquivos estão/não estão no repositório [VERIFIED: git ls-files output]

### Secondary (MEDIUM confidence)
- SamKirkland/FTP-Deploy-Action README: https://github.com/SamKirkland/FTP-Deploy-Action — comportamento de exclusão de arquivos e upload de arquivos gerados no runner
- GitHub Actions docs — secrets context: https://docs.github.com/en/actions/security-guides/using-secrets-in-github-actions

### Tertiary (LOW confidence)
- Compatibilidade do Hostinger shared hosting com `Order Allow,Deny` vs `Require all denied` — [ASSUMED, não verificado via painel Hostinger]
- Comportamento de getenv() em Hostinger shared hosting — [ASSUMED, não testado]

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — arquivos do projeto lidos diretamente, sem suposições sobre libs externas
- Architecture: HIGH — todos os integration points identificados via leitura de código (init.php, index.php, admin_config.php, gestao_admin_99.php)
- Pitfalls: HIGH para pitfalls baseados em código (Pitfalls 1, 3, 5, 6); MEDIUM para pitfalls de infraestrutura (Pitfalls 2, 4)
- Security: HIGH — ameaças identificadas via leitura direta do código com credenciais expostas

**Research date:** 2026-05-16
**Valid until:** 2026-06-16 (stack estável — PHP + Apache + GitHub Actions mudam lentamente)
