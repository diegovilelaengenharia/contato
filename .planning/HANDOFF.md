# HANDOFF — Vilela Engenharia (Pós-v1.5)

> **Pra próxima IA:** este documento é auto-contido. Leia ele inteiro antes de qualquer ação. Não pressuponha contexto de sessões anteriores.

**Data do handoff:** 2026-05-18
**Sessão anterior:** auditoria do código + ideação v2 + fixes de segurança S1 e S2
**Próxima IA:** continuar de onde paramos, na ordem que o Diego escolher

---

## 1. Estado atual do projeto

- Sistema **v1.5 em produção** em https://vilela.eng.br
- **Todas as 8 fases do roadmap v1 concluídas** em 2026-05-18 (ver [.planning/STATE.md](STATE.md))
- Stack: PHP 8 + PDO/MySQL, vanilla HTML/CSS/JS, Hostinger shared hosting
- Deploy: GitHub Actions → FTPS (push em `main` dispara)
- Login dual (admin + cliente) em `area-cliente/index.php`
- Credenciais: `core/db_credentials.php` (gerado pelo CI, gitignored) com fallback pra `db_config.ini`

**Não rode `/gsd-execute-phase N` sem antes ter uma fase nova definida em ROADMAP.md.**

---

## 2. O que foi feito nesta sessão (2026-05-18)

### ✅ Memória atualizada
- `~/.claude/projects/.../memory/project_vilela_state.md` reescrito (refletindo v1 done)
- `~/.claude/projects/.../memory/MEMORY.md` linha do estado atualizada

### ✅ S1 — Fallback de senha admin hardcoded removido
- **Arquivo:** [area-cliente/index.php:48-54](../area-cliente/index.php#L48-L54)
- **Mudança:** removido `'VilelaAdmin2025'` como fallback, adicionado guard pra senha vazia, trocado `===` por `hash_equals()` (timing-safe)
- **⚠️ Pendência operacional:** o histórico do git ainda contém `'VilelaAdmin2025'`. Se essa string foi a senha real em produção, **Diego precisa rotacionar `ADMIN_PASSWORD` no GitHub Secrets**. Verificar com ele antes de prosseguir.

### ✅ S2 — Script destrutivo deletado
- **Arquivo:** `area-cliente/tools/reset_db_diego.php` (removido)
- Era one-shot que já cumpriu propósito (Diego é cliente #1). Estava gitignored, nunca foi pra produção. Risco era menor que o plano original sugeriu.
- Pasta `tools/` ficou só com `desktop.ini` do Windows — ignorar.

### ⏳ Fixes ainda NÃO commitados
Diego não decidiu se commita agora ou continua mais coisas antes. Verificar com:
```bash
git status
git diff area-cliente/index.php
```
Mensagem sugerida quando commitar:
```
security(audit): remove hardcoded admin password fallback (S1)

- Remove fallback 'VilelaAdmin2025' do area-cliente/index.php
- Adiciona guard para senha vazia (env ausente desabilita admin)
- Troca === por hash_equals() pra comparação timing-safe
- Remove tools/reset_db_diego.php (one-shot já cumprido, gitignored)
```

---

## 3. Backlog completo (audit + v2)

### 3.1 Segurança — restante

| ID | Severidade | Onde | Problema | Esforço |
|----|------------|------|----------|---------|
| S3 | 🟡 MED | [area-cliente/index.php](../area-cliente/index.php) | Sem rate-limit no login — força bruta possível | 2h |
| S4 | 🟡 MED | `area-cliente/client-app/debug_*.php` | Confirmar que .gitignore cobre todos; remover do disco local também | 30min |
| S5 | 🟡 MED | Cookies de sessão | `secure`/`httponly`/`SameSite` em `session_set_cookie_params` ([index.php:3](../area-cliente/index.php#L3)) | 30min |
| S6 | 🟢 LOW | `area-cliente/includes/admin_helpers.php` | URLs/conteúdo nem sempre passam por `htmlspecialchars()` | 1h |
| S7 | 🟢 LOW | `area-cliente/core/Auth.php` (logout) | `session_unset()` em vez de `session_destroy()` em alguns paths | 30min |

### 3.2 Qualidade / dívida técnica

| ID | Onde | Observação | Ação |
|----|------|-----------|------|
| Q1 | `area-cliente/gestao_admin_99.php` | Sufixo `_99` legado | Renomear `admin.php` (atualizar todos os redirects) |
| Q2 | `area-cliente/includes/schema.php` | `ALTER TABLE` em toda request | Mover pra `core/Migrations.php` (já existe) |
| Q3 | `area-cliente/maintenance/` | Setup scripts sem gate (já bloqueados por `.htaccess`) | Adicionar token único ou mover pra CLI |
| Q4 | Mistura `db.php` global `$pdo` vs `core/Database.php` singleton | Padronizar | Refatorar pra `Database::pdo()` em todo lugar |
| Q5 | `client-app/` | Boilerplate `require 'db.php'; session_start(); Auth::checkClient();` repetido | Criar `init_client.php` único |

### 3.3 UX / Acessibilidade

- Sem **recuperar senha** no portal cliente
- Sem feedback visual de upload em andamento (spinner)
- Botões "Voltar" sempre vão pra dashboard em vez de usar history
- Sem indicação "atualizado há X" no dashboard

### 3.4 Milestones v2 propostos (não iniciados)

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

Plano completo (este arquivo é resumo): [C:\Users\diego\.claude\plans\leia-tudo-graceful-pond.md](file:///C:/Users/diego/.claude/plans/leia-tudo-graceful-pond.md)

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

---

## 6. Como retomar agora

**Diego precisa decidir** entre estas opções (em ordem de risco/valor decrescente):

1. **Commitar S1+S2 já feitos** — fechar o trabalho desta sessão
2. **Continuar segurança (S3-S7)** — fechar todo o vetor de risco antes de v2
3. **Atacar dívida técnica (Q1-Q5)** — codebase mais limpo antes de v2
4. **Iniciar Milestone v2.0** — features novas (notificações, recuperar senha, dashboard, audit log)
5. **Iniciar Milestone v2.1** — cliente mais autônomo (chat, Pix, feedback docs, calendário)
6. **Iniciar Milestone v2.2** — marketing (portfólio, depoimentos, blog, orçamento)

**Pergunta padrão pra começar:**
> "Diego, vi o HANDOFF.md. Os fixes S1+S2 ainda não foram commitados. Você quer commitar primeiro, ou avançar com algum outro item do backlog antes?"

---

## 7. Como rodar/testar localmente (referência rápida)

- Site live: https://vilela.eng.br/contato/
- Portal cliente: https://vilela.eng.br/contato/area-cliente/
- Admin: https://vilela.eng.br/contato/area-cliente/gestao_admin_99.php
- Para testar localmente PHP é preciso XAMPP/WAMP — Diego não usa local server frequentemente, prefere deploy direto e verifica live
- Banco: MySQL em `srv1074.hstgr.io`, banco `u884436813_cliente`
- Para depurar: olhar logs do Hostinger via cPanel

## 8. Arquivos críticos pra consultar

- [.planning/PROJECT.md](PROJECT.md) — contexto do projeto
- [.planning/ROADMAP.md](ROADMAP.md) — fases v1 (todas done)
- [.planning/REQUIREMENTS.md](REQUIREMENTS.md) — requisitos + v2 deferred (linhas 59-65)
- [.planning/STATE.md](STATE.md) — fonte da verdade do estado
- [CLAUDE.md](../CLAUDE.md) — convenções do projeto
- [area-cliente/index.php](../area-cliente/index.php) — login dual
- [area-cliente/core/Database.php](../area-cliente/core/Database.php) — PDO singleton + fallback
- [area-cliente/core/Auth.php](../area-cliente/core/Auth.php) — checkClient/checkAdmin
- [.github/workflows/deploy.yml](../.github/workflows/deploy.yml) — CI/CD

---

## 9. Perfil do Diego (importante pra colaboração)

- Engenheiro civil, escritório solo (Vilela Engenharia, CREA-MG 235.474/D)
- Cliente principal: ele mesmo + seu escritório
- Prefere **reaproveitar código existente** em vez de criar novo
- Usa GSD religiosamente — respeite o workflow
- Comunicação: **português brasileiro**, direto e curto
- Não tem ambiente de dev local sofisticado — testa direto em produção (cuidado com mudanças destrutivas)
