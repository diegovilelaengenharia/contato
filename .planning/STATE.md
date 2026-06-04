# STATE.md — Vilela Engenharia

## Project Reference

See: `.planning/PROJECT.md`

**Core value:** Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.

## Current Position

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Status:** Em execução (milestone formalizado)
**Phase:** Fase 5 — Features de Valor do Admin ✨ (✅ Concluída)
**Last activity:** 2026-06-04 — Conclusão das Fases 4 e 5: Implementação de reatividade com Alpine.js no Admin e entrega de utilidades de escritório como exportação CSV Excel-friendly, busca autocomplete na sidebar, notas privadas, prazos da prefeitura na timeline e alertas de cobrança/processos no dashboard.

**Próximo passo:** Prosseguir com a auditoria e fechamento do milestone v2.0 (ou planejamento das Fases 6 a 8 de acordo com a prioridade).

## Milestone History

| Versão | Status | Período |
|--------|--------|---------|
| v1.0 — Reescrita completa | ✅ Arquivada | 2026-05-16 → 2026-05-18 (tag `v1.0`) |
| **v2.0 — Hardening + Consolidação + Crescimento** | 🟡 Em execução | iniciado 2026-05-19, formalizado 2026-06-04 |

## Decisões desta sessão (2026-06-04)

| Decisão | Escolha | Razão |
|---|---|---|
| Arquitetura do admin | **PHP + Alpine.js** (CDN, sem build) — React descartado | Preserva o design system da Fase 09; Hostinger não roda Node; manutenível por dev solo; só leituras tinham API, escrita inteira teria de ser construída |
| Escopo do v2.0 | Admin (núcleo) + Landing (crescimento) + Portal (polimento leve) | Admin é a dor principal; portal já é maduro; landing é cartão de visita a fortalecer |
| Identidade visual | Refinar o atual (#197e63 + Outfit), unificar tokens | Consistência sem custo de rebrand |
| Features confirmadas | Excel/CSV + log de auditoria + busca global + prazos + notas + alertas | Valor direto no dia a dia do escritório |
| Hash de senha admin | Incluído na Fase 1 (SEC-08) | Senha em texto plano no banco é risco crítico — não opcional |

> Rascunho original do Gemini (que propunha React) preservado em `milestones/v2.0-DRAFT-original.md`.

## Dívidas técnicas (de v1.0) → mapeadas para fases

| Dívida | Fase | Requirement |
|---|---|---|
| D1 — Validação CSRF mal escrita nos actions/admin/ | 1 | SEC-06 |
| D2 — cliente_impersonate.php usa require relativo frágil | 1 | SEC-09 |
| D3 — includes/processamento.php legado coexiste com actions/ | 2 | ADM-19 |
| D4 — admin.php usa JS para redirect | 2 | ADM-20 |
| D5 — Senhas comprometidas — rotacionar (SSH/FTP/MySQL/ADMIN_PASSWORD) | 1 | SEC-10 |
| D6 — .git em C:\Users\diego\ (HOME) — realocar | 8 | operacional |
| D7 — App Louvor pode ter paths absolutos quebrados (auditar JS) | 8 | operacional |

## Features deferred para v2.1

- Automação WhatsApp ao atualizar etapa (código existe comentado) — oferecida, não priorizada
- 2FA para admin — oferecida, não priorizada (SEC-08 remove o risco maior)

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
- Base do admin: Fase 09 já entregou design system (700 linhas CSS), front-controller `admin/index.php`, views modulares e API JSON de leitura (`area-cliente/api/`)
