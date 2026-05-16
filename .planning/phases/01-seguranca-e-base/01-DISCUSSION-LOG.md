# Phase 1: Segurança e Base - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-05-16
**Phase:** 1-Segurança e Base
**Areas discussed:** Mecanismo de .env no Hostinger, Escopo do .htaccess, Limpeza do servidor em produção

---

## Mecanismo de .env no Hostinger

### Q1 — Como carregar credenciais no Hostinger?

| Option | Description | Selected |
|--------|-------------|----------|
| Parse PHP do arquivo .env | db.php lê e faz parse de area-cliente/.env. Funciona em qualquer shared hosting, sem dependências. | ✓ (Claude) |
| SetEnv no .htaccess | Adiciona SetEnv no .htaccess, PHP lê com getenv(). Credenciais ficam no .htaccess. | |
| Painel Hostinger | Não suportado em shared hosting. | |

**User's choice:** "faça o que achar melhor" — delegado ao Claude.
**Notes:** Claude escolheu parse PHP com parse_ini_file() por ser a opção mais portável e sem dependências externas.

### Q2 — Onde fica o arquivo .env?

| Option | Description | Selected |
|--------|-------------|----------|
| area-cliente/.env | Mesma pasta do db.php, protegido pelo .htaccess local. | ✓ |
| Raiz do projeto (.env) | Um único .env para todo o site. | |

**User's choice:** area-cliente/.env (Recomendado)

### Q3 — Formato do .env?

| Option | Description | Selected |
|--------|-------------|----------|
| INI simples: KEY=VALUE | Lido com parse_ini_file() nativo. Sem lib externa. | ✓ |
| Standard dotenv: KEY="VALUE" | Com aspas, requer parser próprio. | |

**User's choice:** INI simples: KEY=VALUE (Recomendado)

### Q4 — Commitar .env.example?

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, commitar .env.example | Template com chaves sem valores reais. Mesmo padrão do db.example.php. | ✓ |
| Não, documentar no README | Descreve as variáveis em texto. | |

**User's choice:** Sim, commitar .env.example (Recomendado)

---

## Escopo do .htaccess

### Q1 — O que bloquear?

| Option | Description | Selected |
|--------|-------------|----------|
| Mínimo: db.php, .env, config/ | Cobre exatamente o SEC-03. Rápido. | ✓ |
| Abrangente: + maintenance/, debug_*, probe.php | Defense in depth. | |

**User's choice:** Mínimo requerido (Recomendado)

### Q2 — Onde colocar as regras?

| Option | Description | Selected |
|--------|-------------|----------|
| Raiz (.htaccess na raiz) | Um só arquivo de configuração. | |
| area-cliente/.htaccess separado | Regras isoladas onde os arquivos sensíveis ficam. | ✓ |

**User's choice:** area-cliente/.htaccess separado (Recomendado)

### Q3 — Headers de segurança HTTP?

| Option | Description | Selected |
|--------|-------------|----------|
| Não, só bloqueio de arquivos | Escopo da Fase 1 é credenciais. | ✓ |
| Sim, adicionar headers básicos agora | X-Frame-Options, X-Content-Type-Options. | |

**User's choice:** Não, só bloqueio de arquivos (Recomendado para Fase 1)

---

## Limpeza do servidor em produção

### Q1 — Como tratar scripts perigosos no servidor?

| Option | Description | Selected |
|--------|-------------|----------|
| Checklist: Diego deleta via File Manager | Lista exata de arquivos a deletar. Ação única. | |
| Bloquear via .htaccess | Extende o escopo do .htaccess. | |
| Limpar e re-subir tudo | Fresh deploy substitui o servidor. | ✓ |

**User's choice:** "limpe o servidor e envie o projeto novamente, para evitar erros" (freeform).
**Notes:** Abordagem mais limpa — o deploy substitui todos os arquivos no servidor.

### Q2 — Como tratar db.php e uploads durante a limpeza?

| Option | Description | Selected |
|--------|-------------|----------|
| Backup + re-upload do db.php manualmente | Diego faz backup, limpa, depois re-sobe db.php. | |
| Limpar só PHP/config, preservar uploads/ | Mais preciso, mais manual. | |
| Tudo automatizado via GitHub Actions | Deploy gera .env dos Secrets, FTP sobe tudo. | ✓ |

**User's choice:** "quero que vc suba tudo, sem precisar mudar manualmente nada. deixe tudo conectado e automatizado" (freeform).

### Q3 — GitHub Secrets para automação total?

| Option | Description | Selected |
|--------|-------------|----------|
| Sim — adiciono os Secrets no GitHub | Diego adiciona 5 Secrets uma vez, resto é automático. | ✓ |
| Preferir .env manual via File Manager | Cria .env no servidor uma vez manualmente. | |

**User's choice:** Sim — adiciono os Secrets no GitHub e o resto é automático (Recomendado)

---

## Claude's Discretion

- **Mecanismo de parse do .env:** Claude escolheu `parse_ini_file()` nativo do PHP — usuário disse "faça o que achar melhor".
- **Sintaxe .htaccess:** Planner decidirá entre FilesMatch, Deny from all, ou Order Deny,Allow conforme compatibilidade Hostinger Apache.

## Deferred Ideas

- Headers de segurança HTTP (X-Frame-Options, CSP) — mais adequados para Fase 8 polimento.
- Bloqueio .htaccess de maintenance/ e tools/ — usuário optou pelo mínimo; pode ser revisitado.
- Rotação de credenciais do banco (Diego@159753) — ação recomendada após implementar .env, mas fora do escopo de código desta fase.
