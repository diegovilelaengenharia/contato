# STATE.md — Vilela Engenharia

## Project Reference

See: `.planning/PROJECT.md` (updated 2026-05-16)

**Core value:** Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.
**Current focus:** Phase 2 — Landing Page Mobile-First

## Current Phase

**Phase:** 2 — Landing Page Mobile-First
**Status:** not started
**Goal:** Substituir a landing page atual (linktree) por uma página de apresentação completa, focada em mobile, com hero, serviços, sobre e links de contato
**Plans:** a definir
**Last Activity:** 2026-05-16
**Resume:** `/gsd-discuss-phase 2`

## Phase History

| Phase | Status | Completed |
|-------|--------|-----------|
| 1 — Segurança e Base | done | 2026-05-16 |

## Blockers

None.

## Notes

- Phase 1 concluída: credenciais movidas para .env, .htaccess bloqueando db.php (403), deploy via GitHub Actions gerando .env automaticamente dos Secrets
- DB_PASS requer aspas duplas no .env por conter caracteres especiais (`;`, `#`)
- GitHub Secrets configurados: DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD, FTP_HOST, FTP_USER, FTP_PASSWORD
- Pasta `public_html/contato/` criada pelo Hostinger Git integration — apagar via Gerenciador de Arquivos
- Deploy correto via GitHub Actions FTP → public_html/
