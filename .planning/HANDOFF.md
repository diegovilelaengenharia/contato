# HANDOFF — Vilela Engenharia (Pós-v1.5)

> **Pra próxima IA:** este documento é auto-contido. Leia ele inteiro antes de qualquer ação. Não pressuponha contexto de sessões anteriores.

**Data do handoff:** 2026-05-18
**Sessão anterior:** segurança S1–S7 completa + dívida técnica Q5 concluída
**Próxima IA:** continuar Q1–Q4 ou iniciar milestone v2.0

---

## 1. Estado atual do projeto

- Sistema **v1.5 em produção** em https://vilela.eng.br
- **Todas as 8 fases do roadmap v1 concluídas** (ver [.planning/STATE.md](STATE.md))
- **Segurança S1–S7 totalmente fechada** ✅
- **Dívida técnica Q5 concluída** ✅
- Stack: PHP 8 + PDO/MySQL, vanilla HTML/CSS/JS, Hostinger shared hosting
- Deploy: GitHub Actions → FTPS (push em `main` dispara)
- Login dual (admin + cliente) em `area-cliente/index.php`
- Credenciais: `core/db_credentials.php` (gerado pelo CI, gitignored) com fallback pra `db_config.ini`

**Não rode `/gsd-execute-phase N` sem antes ter uma fase nova definida em ROADMAP.md.**

---

## 2. O que foi feito nesta sessão (2026-05-18)

### ✅ S1 — Fallback de senha admin hardcoded removido
- **Commit:** `65cbcc7`
- Removido `'VilelaAdmin2025'` como fallback, adicionado guard pra senha vazia, trocado `===` por `hash_equals()`

### ✅ S2 — Script destrutivo deletado
- **Commit:** `65cbcc7`
- `area-cliente/tools/reset_db_diego.php` removido

### ✅ S3 — Rate-limit no login
- **Commit:** `2c23c0c`
- Tabela `login_attempts` criada automaticamente (CREATE IF NOT EXISTS em index.php)
- 5 tentativas por IP em 15 minutos, depois bloqueia
- Limpeza automática de registros antigos (1% chance por request)

### ✅ S4 — Debug files removidos
- **Commit:** `2c23c0c`
- `debug_checklist.php` e `debug_syntax_checklist.php` deletados do disco
- `.gitignore` já cobria — nunca foram pra produção

### ✅ S5 — Cookies de sessão seguros
- **Commit:** `2c23c0c`
- `secure=true`, `httponly=true`, `SameSite=Lax` em:
  - `Auth.php` (central), `index.php`, `init.php`, `init_client.php`

### ✅ S6 — XSS em admin_helpers.php
- **Commit:** `2c23c0c`
- `htmlspecialchars()` em todos os valores do banco (descricao, status, link_comprovante)
- Validação de protocolo (só http/https) em URLs de comprovantes
- IDs cast pra `(int)` pra prevenir injeção

### ✅ S7 — Logout seguro centralizado
- **Commit:** `2c23c0c`
- `Auth::logout()` agora: limpa `$_SESSION = []`, invalida cookie, `session_destroy()`
- `logout.php` e `client-app/logout.php` delegam pra `Auth::logout()`

### ✅ Q5 — init_client.php centralizado
- **Commit:** `641eadb`
- Criado `client-app/init_client.php` (session segura + db + auth)
- 6 arquivos refatorados: index, timeline, financeiro, pendencias, documentos, documentos_iniciais
- ~60 linhas de boilerplate eliminadas
- Suporta `$SKIP_CLIENT_AUTH = true` pra modo simulação admin

### Bonus: display_errors=0 em produção
- `index.php` e `documentos_iniciais.php` não expõem mais erros PHP ao usuário
- Erros de login retornam mensagem genérica, detalhes vão pro `error_log()`

---

## 3. Backlog restante

### 3.1 Dívida técnica — restante

| ID | Onde | Observação | Ação |
|----|------|-----------|------|
| Q1 | `area-cliente/gestao_admin_99.php` | Sufixo `_99` legado | Renomear `admin.php` (atualizar ~50 redirects) |
| Q2 | `area-cliente/includes/schema.php` | `ALTER TABLE` em toda request | Mover pra `core/Migrations.php` (run-once) |
| Q3 | `area-cliente/maintenance/` | Setup scripts sem gate (já bloqueados por `.htaccess`) | Adicionar token único ou mover pra CLI |
| Q4 | Mistura `db.php` global `$pdo` vs `core/Database.php` singleton | Padronizar | Refatorar pra `Database::pdo()` em todo lugar |

### 3.2 UX / Acessibilidade

- Sem **recuperar senha** no portal cliente
- Sem feedback visual de upload em andamento (spinner)
- Botões "Voltar" sempre vão pra dashboard em vez de usar history
- Sem indicação "atualizado há X" no dashboard

### 3.3 Milestones v2 propostos (não iniciados)

**Milestone v2.0 — Operação sem fricção (foco no Diego)**
- Fase A: Notificações automáticas (email/WhatsApp Cloud API)
- Fase B: Recuperação de senha (token + expiração + email)
- Fase C: Dashboard admin com métricas
- Fase D: Log de auditoria admin (tabela `admin_audit_log`)

**Milestone v2.1 — Cliente mais autônomo**
- Fase E: Chat in-app cliente↔Diego
- Fase F: Pix copia-cola no financeiro.php
- Fase G: Aprovação/rejeição de docs com motivo escrito
- Fase H: Calendário de vencimentos (+ link .ics)

**Milestone v2.2 — Captação e marketing**
- Fase I: Portfólio de obras concluídas
- Fase J: Depoimentos de clientes
- Fase K: Blog técnico (SEO local)
- Fase L: Formulário de orçamento estruturado

---

## 4. Workflow esperado (importante!)

Este projeto usa **GSD (Get Shit Done)** — não pular fases:

1. **Antes de qualquer mudança não-trivial:** rodar `/gsd-discuss-phase N`
2. **Depois:** `/gsd-plan-phase N`
3. **Só então:** `/gsd-execute-phase N`
4. **Por fim:** `/gsd-verify-work`

Para mudanças triviais (typo, fix óbvio de 1 linha): `/gsd-fast` ou direto, mas avise o Diego.

Para criar uma fase nova:
- `/gsd-phase add` (adiciona fase em ROADMAP.md)
- Para um milestone novo (v2.0): `/gsd-new-milestone`

---

## 5. Convenções do projeto (de [CLAUDE.md](../CLAUDE.md))

- **Credenciais do banco NUNCA no código** — usar `core/db_credentials.php` (CI) ou fallback `.env`/`db_config.ini`
- Scripts de manutenção em `area-cliente/maintenance/` (bloqueado por `.htaccess`)
- CSS: variáveis em `:root`, cor primária `--color-primary: #197e63`
- Fonte: Outfit (Google Fonts)
- Nunca commitar `db.php` com credenciais reais
- Nunca commitar `debug_*.php`, `session_test_*.php`, `probe.php`
- Deploy automático via push em `main` — nunca FTP manual
- Idioma da comunicação com Diego: **português brasileiro**
- **Bootstrap do portal cliente:** sempre usar `require_once __DIR__ . '/init_client.php';` (Q5)

---

## 6. Como retomar agora

**Diego precisa decidir** entre estas opções:

1. **Continuar dívida técnica (Q1–Q4)** — codebase mais limpo antes de v2
2. **Iniciar Milestone v2.0** — features novas (notificações, recuperar senha, dashboard, audit log)
3. **Iniciar Milestone v2.1** — cliente mais autônomo (chat, Pix, feedback docs, calendário)
4. **Iniciar Milestone v2.2** — marketing (portfólio, depoimentos, blog, orçamento)

---

## 7. Como rodar/testar (referência rápida)

- Site live: https://vilela.eng.br/contato/
- Portal cliente: https://vilela.eng.br/contato/area-cliente/
- Admin: https://vilela.eng.br/contato/area-cliente/gestao_admin_99.php
- Para testar localmente PHP é preciso XAMPP/WAMP — Diego não usa local server, prefere deploy direto
- Banco: MySQL em `srv1074.hstgr.io`, banco `u884436813_cliente`
- Para depurar: olhar logs do Hostinger via cPanel

## 8. Arquivos críticos pra consultar

- [.planning/PROJECT.md](PROJECT.md) — contexto do projeto
- [.planning/ROADMAP.md](ROADMAP.md) — fases v1 (todas done)
- [.planning/REQUIREMENTS.md](REQUIREMENTS.md) — requisitos + v2 deferred
- [.planning/STATE.md](STATE.md) — fonte da verdade do estado
- [CLAUDE.md](../CLAUDE.md) — convenções do projeto
- [area-cliente/index.php](../area-cliente/index.php) — login dual + rate-limit
- [area-cliente/core/Auth.php](../area-cliente/core/Auth.php) — sessão segura + logout
- [area-cliente/client-app/init_client.php](../area-cliente/client-app/init_client.php) — bootstrap cliente (Q5)
- [area-cliente/core/Database.php](../area-cliente/core/Database.php) — PDO singleton + fallback
- [.github/workflows/deploy.yml](../.github/workflows/deploy.yml) — CI/CD

---

## 9. Perfil do Diego (importante pra colaboração)

- Engenheiro civil, escritório solo (Vilela Engenharia, CREA-MG 235.474/D)
- Cliente principal: ele mesmo + seu escritório
- Prefere **reaproveitar código existente** em vez de criar novo
- Usa GSD religiosamente — respeite o workflow
- Comunicação: **português brasileiro**, direto e curto
- Não tem ambiente de dev local sofisticado — testa direto em produção (cuidado com mudanças destrutivas)
