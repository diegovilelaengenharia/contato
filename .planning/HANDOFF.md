# HANDOFF — Vilela Engenharia (Pós-v1.5)

> **Pra próxima IA:** este documento é auto-contido. Leia ele inteiro antes de qualquer ação. Não pressuponha contexto de sessões anteriores.

**Data do handoff:** 2026-05-18
**Sessão anterior:** segurança S1–S7 completa + dívida técnica Q1, Q2, Q3, Q5 concluídas.
**Próxima IA:** Iniciar milestone v2.0

---

## 1. Estado atual do projeto

- Sistema **v1.5 em produção** em https://vilela.eng.br
- **Todas as 8 fases do roadmap v1 concluídas** (ver [.planning/STATE.md](STATE.md))
- **Segurança S1–S7 totalmente fechada** ✅
- **Dívida técnica Q1, Q2, Q3, Q5 concluídas** ✅
- Stack: PHP 8 + PDO/MySQL, vanilla HTML/CSS/JS, Hostinger shared hosting
- Deploy: GitHub Actions → FTPS (push em `main` dispara)
- Login dual (admin + cliente) em `area-cliente/index.php`
- Credenciais: `core/db_credentials.php` (gerado pelo CI, gitignored) com fallback pra `db_config.ini`

**Não rode `/gsd-execute-phase N` sem antes ter uma fase nova definida em ROADMAP.md.**

---

## 2. O que foi feito nesta sessão (2026-05-18)

### ✅ Q1 — Renomeado gestao_admin_99.php
- `gestao_admin_99.php` renomeado para `admin.php` para um visual mais profissional.
- Mais de 40 referências em redirecionamentos, botões e links (`header("Location: ...")`) atualizadas no projeto inteiro.

### ✅ Q2 — Migrations e Schema Run-Once
- Lógica gigante de `CREATE TABLE` e `ALTER TABLE` movida de `includes/schema.php` para `core/Migrations.php`.
- Agora ele usa a tabela `migrations` (ou `admin_settings`) para garantir que o bloco rode **apenas 1 vez** e não em todas as requests (melhoria drástica de performance).

### ✅ Q3 — Segurança em Scripts de Manutenção Legados
- O diretório `area-cliente/maintenance/` (que continha mais de 15 scripts de manipulação direta de banco, uploads e senhas) foi bloqueado.
- Adicionado `if (php_sapi_name() !== 'cli') die('CLI ONLY');` no início de todos os arquivos. Eles não podem mais ser executados pelo navegador.

### ✅ Q5 — init_client.php centralizado (Sessão anterior/Atual)
- Criado `client-app/init_client.php` (session segura + db + auth)
- 6 arquivos refatorados: index, timeline, financeiro, pendencias, documentos, documentos_iniciais

---

## 3. Notas sobre Problemas na Produção (Erro no Banco)
Se houver o erro `SQLSTATE[HY000] [1045] Access denied for user 'u884436813_cliente'@'2a02:4780:13::52'`, isso **NÃO** é um bug de código. O código lê as credenciais corretamente. 
Isso indica que o Hostinger está bloqueando o acesso IPv6 (neste caso `2a02:4780...`) ao banco de dados, ou a senha no Hostinger mudou.
**Solução para o Diego:** Vá nas configurações (Secrets) do GitHub Actions e garanta que `DB_HOST` está configurado como `localhost` (ou `127.0.0.1`) em vez de um domínio/IP externo para não passar pelo firewall remoto do Hostinger.

---

## 4. Milestones v2 propostos (Próximos Passos)

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

## 5. Workflow esperado (importante!)

Este projeto usa **GSD (Get Shit Done)** — não pular fases:

1. **Antes de qualquer mudança não-trivial:** rodar `/gsd-discuss-phase N`
2. **Depois:** `/gsd-plan-phase N`
3. **Só então:** `/gsd-execute-phase N`
4. **Por fim:** `/gsd-verify-work`

Para criar uma fase nova:
- `/gsd-phase add` (adiciona fase em ROADMAP.md)
- Para um milestone novo (v2.0): `/gsd-new-milestone`
