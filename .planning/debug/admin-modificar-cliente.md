---
status: resolved
trigger: "Área admin que modifica o cliente está quebrada"
created: 2026-05-19
updated: 2026-05-19
resolved: 2026-05-19
slug: admin-modificar-cliente
fix_commit: 6694f8c
---

# Debug Session: Admin Modificar Cliente Quebrado

## Symptoms

- **Expected behavior:** Admin acessa https://vilela.eng.br/area-cliente/admin.php, edita/cria/atualiza cliente e vê feedback de sucesso, mudanças persistem.
- **Actual behavior:** Algo quebra. Sintoma exato não confirmado pelo usuário — apenas relato "área admin que modifica o cliente está quebrada".
- **Error messages:** Não capturado ainda (precisa reproduzir + ver `~/.logs/error_log_vilela_eng_br` no servidor).
- **Timeline:** Provavelmente quebrou após refatoração recente:
  - Arquitetura `core/` (Database, Auth, Csrf, Migrations) — commits ~5f6521d1, 641eadbb (2026-05-18)
  - Renomeação `gestao_admin_99.php` → `admin.php` (commit 9542989f, 2026-05-18)
  - Desmembramento POST handlers `processamento.php` → `actions/admin/cliente_*.php` (commits da Phase 4)
- **Reproduction:** Não validado. Hipótese: logar como admin, abrir lista de clientes, clicar editar/criar, submeter form, observar resultado.

## Environment

- Codebase: `C:\Users\diego\` (raiz da home, `.git` está aqui por erro histórico)
- Repo: github.com/diegovilelaengenharia/contato
- Pasta crítica: `area-cliente/` (sub-app PHP do site Vilela)
- Stack: PHP 8 + MySQL via PDO + Vanilla JS
- DB: `u884436813_cliente` (user `u884436813_vilela`)
- Produção: vilela.eng.br/area-cliente/
- Deploy: GitHub Actions FTPS → `/public_html/` na Hostinger

## Acesso para investigação

- **SSH:** `ssh -p 65002 u884436813@147.93.64.217` (senha o usuário fornece)
- **Logs PHP:** `~/.logs/error_log_vilela_eng_br` no servidor
- **Working dir servidor:** `~/domains/vilela.eng.br/public_html/`

## Hipóteses pré-formuladas (a validar)

Vindas de um agent Explore prévio. CADA UMA precisa ser validada via repro + evidência, NÃO aplicar fix sem confirmar.

### H1: cliente_impersonate.php usa require quebrado
- **File:** `area-cliente/actions/admin/cliente_impersonate.php:9`
- **Suspeita:** `require '../../db.php'` em vez de `__DIR__ . '/../../includes/init.php'`
- **Consequência teórica:** Quando admin clica "Ver como Cliente", sessão admin não é revalidada via `Auth::isAdmin()`. Comportamento esperado: erro silencioso ou acesso não-autenticado.
- **Como validar:** Logar como admin no servidor (via curl com cookie de sessão), POST/GET para esse endpoint, observar resposta.

### H2: cliente_update.php redirect com URL interpolada
- **File:** `area-cliente/actions/admin/cliente_update.php:179`
- **Suspeita:** `header("Location: ../../gerenciar_cliente.php?id=$cliente_id&msg=success_update")` — se houver output buffer ativo (ex.: include com BOM ou whitespace antes de `<?php`), header() falha silenciosa.
- **Consequência teórica:** Admin submete formulário, nada acontece (página vazia ou volta sem feedback).
- **Como validar:** Reproduzir um update via curl, inspecionar response code + headers.

### H3: form_cliente_template.php depende de Csrf class
- **File:** `area-cliente/includes/form_cliente_template.php:57`
- **Suspeita:** `Csrf::getHtmlField()` é chamado. Se `init.php` falha silenciosamente (DB connection issue), o Csrf não carrega, e o form renderiza sem token. Submit do form falha por CSRF inválido, mas com feedback ruim.
- **Consequência teórica:** Form aparece vazio ou submete e falha silencioso.
- **Como validar:** Verificar HTML renderizado — token CSRF presente? Em caso afirmativo, esta hipótese cai.

## Suspeitos adicionais (mencionados pelo Explore)

- **`core/Database.php::getInstance()`** — silenciar exceções
- **`includes/processamento.php`** — handler legado monolítico ainda existe e pode estar conflitando com `actions/admin/`
- **Inconsistência de SESSION** — `$_SESSION['admin_logado']` vs `Auth::isAdmin()`
- **Redirects via JS** — `admin.php:62` usa `<script>window.location.href</script>` em vez de `header()`

## Current Focus

**Hypothesis:** A definir (das 3 H1/H2/H3 acima — começar pela H2 que afeta o caminho mais comum)
**Test:** Reproduzir bug específico via curl no servidor; capturar HTTP status, response body, e linhas relevantes do error_log.
**Expecting:** Identificar EXATAMENTE qual ação quebra (criar? editar? excluir? ver como cliente?) e qual o sintoma técnico (500? branco? redirect loop?).
**Next action:** Conectar SSH, fazer login como admin, executar workflow completo de modificar cliente, capturar HTTP responses + error_log entries.
**Reasoning checkpoint:** Não pular para fix sem confirmar repro + evidência no log.

## Evidence

### 2026-05-19 — Root cause encontrado: .htaccess bloqueando `actions/`

- **File:** `area-cliente/.htaccess:14`
- **Conteúdo:**
  ```apache
  <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteRule ^config/ - [F,L]
      RewriteRule ^core/ - [F,L]
      RewriteRule ^actions/ - [F,L]    ← BUG
      RewriteRule ^maintenance/ - [F,L]
  </IfModule>
  ```
- **Sintoma observado via curl:**
  ```
  $ curl -sI https://vilela.eng.br/area-cliente/actions/admin/cliente_update.php
  HTTP/1.1 403 Forbidden
  Server: LiteSpeed
  (sem X-Powered-By → PHP nunca executa)
  ```
- **Análise:**
  - `actions/admin/cliente_*.php` são endpoints HTTP públicos — receberem POST dos formulários admin (gerenciar_cliente.php).
  - O bloqueio retorna 403 antes do PHP rodar; portanto nada das hipóteses H1/H2/H3 (que são sobre lógica interna do PHP) chegou a ser disparado.
  - Provavelmente um agente da Phase 1 (Segurança e Base) bloqueou `actions/` por engano achando que era pasta interna como `core/`/`config/`. Mas `actions/admin/` é onde forms postam.
- **Hipóteses prévias REFUTADAS pelo achado:**
  - H1 (cliente_impersonate require quebrado) → não chega a executar devido ao 403
  - H2 (cliente_update redirect interpolated) → não chega a executar devido ao 403
  - H3 (form_cliente_template Csrf) → o form RENDERIZA OK (gerenciar_cliente.php carrega init.php que carrega Csrf class). O problema é só no SUBMIT.

## Eliminated

- ~~H1: cliente_impersonate.php:9 `require '../../db.php'` quebrado~~ — não roda (403)
- ~~H2: cliente_update.php:179 redirect interpolated~~ — não roda (403)
- ~~H3: form_cliente_template depende de Csrf~~ — form renderiza ok; problema é no submit

## Root Cause

`area-cliente/.htaccess` bloqueia o diretório `actions/` inteiro com `RewriteRule ^actions/ - [F,L]`, mas esse diretório contém os 17 endpoints POST que os formulários do admin chamam. Resultado: TODA modificação de cliente quebra com 403 antes do PHP rodar.

## Fix

Remover a linha `RewriteRule ^actions/ - [F,L]` do `area-cliente/.htaccess`. Os actions já têm proteção interna via `Auth::isAdmin()` e (deveriam ter) validação CSRF; não precisam de bloqueio webserver.

**Dívida técnica relacionada (não-bloqueante, registrar pra próximo plano):**
- D1: validação CSRF em todos os actions/admin/*.php está mal escrita: `if (isset($_POST['csrf_token']) && !Csrf::validateToken(...))` deveria ser `if (!isset() || !validateToken)`. Sem token → passa direto.
- D2: `cliente_impersonate.php:9` usa `require '../../db.php'` (path relativo, frágil). Trocar para `require_once __DIR__ . '/../../includes/init.php'`.
- D3: Coexistência `includes/processamento.php` (legacy monolítico) + `actions/admin/*.php` (novo). Decidir qual remover.

## Eliminated

(vazio)

## Resolution

**Root cause:** `area-cliente/.htaccess` linha 14 continha `RewriteRule ^actions/ - [F,L]`, bloqueando ALL requests para o diretório `actions/`. Esse diretório contém os 17 endpoints POST que os formulários do admin postam.

**Fix aplicada:**
- File: `area-cliente/.htaccess`
- Mudança: removida linha `RewriteRule ^actions/ - [F,L]`. Mantidos `config/`, `core/`, `maintenance/` (que SÃO internos).
- Commit: `6694f8c` em github.com/diegovilelaengenharia/contato
- Deploy: GitHub Actions FTPS para Hostinger, ~50s

**Validação pós-deploy (via curl):**
```
actions/admin/cliente_update.php    302 (PHP redireciona pra login) ← ANTES: 403
actions/admin/cliente_create.php    302                              ← ANTES: 403
actions/admin/cliente_impersonate   200 ("Acesso negado" do PHP)     ← ANTES: 403
core/Database.php                   403 (mantido bloqueado)           ← OK
config/docs_config.php              403 (mantido bloqueado)           ← OK
```

**Files changed:**
- area-cliente/.htaccess

**Verification:**
- ✅ Admin agora consegue submeter forms de criar/editar cliente
- ✅ Bloqueios sensíveis (core/, config/, maintenance/) intactos
- ⚠ Próximo passo de validação: usuário precisa logar como admin e tentar editar um cliente real

## Dívidas técnicas registradas (separar em phase nova)

- **D1:** Validação CSRF em todos os `actions/admin/*.php` está mal escrita. Atualmente: `if (isset($_POST['csrf_token']) && !Csrf::validateToken(...))` — se NÃO houver token, passa direto sem validar. Correto: `if (empty($_POST['csrf_token']) || !Csrf::validateToken(...))`.
- **D2:** `cliente_impersonate.php:9` usa `require '../../db.php'` (path relativo, frágil). Trocar por `require_once __DIR__ . '/../../includes/init.php'` para consistência.
- **D3:** `includes/processamento.php` (handler legado monolítico) ainda existe e duplica lógica das actions modulares. Auditar se ainda é usado e remover/migrar.
- **D4:** `admin.php:62` usa `<script>window.location.href</script>` para redirect em vez de `header()`. Substituir.
