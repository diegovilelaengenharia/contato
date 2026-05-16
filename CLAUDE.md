# CLAUDE.md — Vilela Engenharia

## Projeto

Site da Vilela Engenharia (vilela.eng.br) com três componentes:
1. **Landing page** (`index.html`) — mobile-first, serviços, WhatsApp, redes sociais
2. **Portal do cliente** (`area-cliente/client-app/`) — acompanhamento de processo
3. **Painel admin** (`area-cliente/gestao_admin_99.php` e includes/) — gestão de clientes

## GSD Workflow

Este projeto usa GSD (Get Shit Done). Sempre seguir o fluxo:
`/gsd-discuss-phase N` → `/gsd-plan-phase N` → `/gsd-execute-phase N` → `/gsd-verify-work`

**Fase atual:** Phase 1 — Segurança e Base (ver `.planning/STATE.md`)

**Próximo comando:** `/gsd-discuss-phase 1`

## Stack

- **Backend:** PHP 8+ com PDO/MySQL
- **Frontend:** HTML/CSS/JS puro (sem bundlers, sem frameworks)
- **Hospedagem:** Hostinger shared hosting, domínio vilela.eng.br
- **Deploy:** GitHub Actions → FTPS → Hostinger (`.github/workflows/deploy.yml`)
- **Banco:** MySQL em `srv1074.hstgr.io`, banco `u884436813_cliente`

## Convenções

- Credenciais do banco NUNCA no código — usar `getenv()` ou `$_ENV`
- `.env` na raiz do projeto (não commitado) contém as variáveis reais
- `db.example.php` é o template público seguro
- Scripts de manutenção ficam em `area-cliente/maintenance/` (não acessíveis publicamente)
- CSS: variáveis em `:root`, cor primária `--color-primary: #197e63`
- Fonte: Outfit (Google Fonts)

## Arquivos Importantes

- `.planning/PROJECT.md` — contexto do projeto
- `.planning/ROADMAP.md` — 8 fases de execução
- `.planning/REQUIREMENTS.md` — requirements com IDs (LAND-XX, CLI-XX, ADM-XX, SEC-XX)
- `.planning/STATE.md` — fase atual e histórico
- `area-cliente/db.php` — conexão MySQL (NUNCA commitar com credenciais reais)
- `area-cliente/includes/schema.php` — cria tabelas automaticamente se não existirem

## Regras

- Não commitar `db.php` com credenciais reais (está no .gitignore)
- Não commitar `debug_*.php`, `session_test_*.php`, `probe.php` (no .gitignore)
- Todo deploy automático via push para `main` — nunca fazer upload manual de arquivos
- Manter o `.gitignore` atualizado para não expor arquivos sensíveis
