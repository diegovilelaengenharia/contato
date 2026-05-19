# STATE.md — Vilela Engenharia

## Project Reference

See: `.planning/PROJECT.md`

**Core value:** Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.

## Current Position

**Milestone:** v2.0 — Hardening, Auditoria e Features
**Status:** Planning (definindo requirements + roadmap)
**Phase:** Not started (defining requirements)
**Last activity:** 2026-05-19 — Milestone v2.0 started after archiving v1.0

## Milestone History

| Versão | Status | Período |
|--------|--------|---------|
| v1.0 — Reescrita completa | ✅ Arquivada | 2026-05-16 → 2026-05-18 (tag `v1.0`) |
| **v2.0 — Hardening + Features** | 🟡 Planning | iniciado 2026-05-19 |

## Dívidas técnicas trazidas para v2.0 (de v1.0)

D1: Validação CSRF mal escrita nos actions/admin/
D2: cliente_impersonate.php usa require relativo frágil
D3: includes/processamento.php legado coexiste com actions/
D4: admin.php:62 usa JS para redirect
D5: Senhas comprometidas — rotacionar todas (SSH/FTP/MySQL/ADMIN_PASSWORD)
D6: .git em C:\Users\diego\ (HOME) — realocar
D7: App Louvor pode ter outros paths absolutos quebrados (auditar JS)

## Features deferred de v1.0 (entram em v2.0)

- Portfólio de obras na landing
- Blog/artigos técnicos
- Automação WhatsApp ao atualizar etapa
- Log de auditoria do admin
- Exportação financeira em Excel
- 2FA para admin

## Bug history (sessão 2026-05-19)

| Bug | Status | Fix |
|---|---|---|
| Servidor com App Louvor aninhado errado | ✅ resolvido | SSH manual (18 pastas + 12 arquivos) |
| Secret DB_HOST errado | ✅ resolvido | commit `0c5e6419` |
| App Louvor: paths absolutos | ✅ resolvido | commit `b84fc2f` (repo applouvor) |
| .htaccess bloqueando actions/ | ✅ resolvido | commit `6694f8c` (repo contato) |

## Blockers

None.

## Notes

- Backup local: `Desktop/BACKUP_Vilela-eng-Site_2026-05-19_0913.zip` (184 MB)
- Tag local: `local-pre-reset-2026-05-19`
- Debug session resolved: `admin-modificar-cliente`
- Próximo passo automático: research (4 agentes paralelos) → requirements → roadmap
