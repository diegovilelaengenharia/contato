# STATE.md — Vilela Engenharia

## Project Reference

See: `.planning/PROJECT.md`

**Core value:** Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.

## Current Status

**Milestone:** v1.0 ✅ ARQUIVADO em 2026-05-19
**Tag git:** `v1.0`
**Próximo passo:** `/gsd-new-milestone` para iniciar v2.0
**Sistema:** v1.5 em produção em https://vilela.eng.br

## Milestone History

| Versão | Status | Completado |
|--------|--------|------------|
| v1.0 — Reescrita completa + portal cliente + admin novo | ✅ Completa | 2026-05-18 (arquivada 2026-05-19) |

## Phases (v1.0 — arquivado)

Ver [milestones/v1.0-ROADMAP.md](milestones/v1.0-ROADMAP.md) para detalhes completos.

| Phase | Status | Pasta |
|-------|--------|-------|
| 1 — Segurança e Base | done | `phases/01-seguranca-e-base/` |
| 2 — Landing Page Mobile-First | done | `phases/02-landing-page-mobile-first/` |
| 3 — Camada de Compatibilidade Core | done | `phases/03-camada-compatibilidade-core/` |
| 4 — Desmembramento Admin (Actions) | done | `phases/04-desmembramento-admin-actions/` |
| 5 — Desmembramento Admin (Views) | done | `phases/05-desmembramento-admin-views/` |
| 6 — Form Cliente + Validação | done | `phases/06-form-cliente-validacao-fluxos/` |
| 7 — Financeiro/Pendências/Documentos | done | `phases/07-financeiro-pendencias-documentos/` |
| 8 — Polimento e Responsividade | done | `phases/08-polimento-responsividade/` |

## Dívidas técnicas levadas para v2.0

Registradas em [debug/admin-modificar-cliente.md](debug/admin-modificar-cliente.md):

- D1: Validação CSRF mal escrita nos actions/admin/
- D2: cliente_impersonate.php usa require relativo frágil
- D3: includes/processamento.php legado coexiste com actions/
- D4: admin.php:62 usa JS para redirect
- D5: Senhas comprometidas (vazadas em chat + em GitHub Variables) — rotacionar
- D6: .git em C:\Users\diego\ (HOME) — realocar
- D7: App Louvor pode ter outros paths absolutos quebrados (auditar JS)

## Bug history (2026-05-19)

| Bug | Status | Fix |
|---|---|---|
| Servidor com App Louvor aninhado errado em /public_html/ | ✅ resolvido | manual via SSH (18 pastas + 12 arquivos removidos) |
| GitHub Secret DB_HOST = nome do usuário (deveria ser localhost) | ✅ resolvido | commit `0c5e6419` (redeploy after correction) |
| App Louvor: paths absolutos /sw.js + /manifest.json | ✅ resolvido | commit `b84fc2f` no repo applouvor |
| .htaccess bloqueando actions/ no contato | ✅ resolvido | commit `6694f8c` no repo contato |

## Notes

- Backup local da pasta de trabalho em `Desktop/BACKUP_Vilela-eng-Site_2026-05-19_0913.zip` (184 MB)
- Tag local de segurança `local-pre-reset-2026-05-19` antes do hard reset alinhando local com remote
- Sessão de debug `admin-modificar-cliente` marcada como `resolved`
