# STATE.md — Vilela Engenharia

## Project Reference

See: `.planning/PROJECT.md`

**Core value:** Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.

## Current Position

**Milestone:** v2.1 — Landing de Crescimento & Polimento
**Status:** 🟢 Em execução (Formalizado após Grill-me em 2026-06-05)
**Phase:** Fase 1 — Crescimento da Landing Page (Cartão de Visita) 📣
**Last activity:** 2026-06-05 — Correção de layouts mobile (padding de cards e hero, alinhamento de steps) e limpeza de CSS obsoleto de seções removidas (Cases/Depoimentos).

**Próximo passo:** Iniciar a Fase 2 (Polimento do Portal do Cliente) ou preparar o fechamento do milestone atual conforme solicitação do usuário.

## Milestone History

| Versão | Status | Período |
|--------|--------|---------|
| v1.0 — Reescrita completa | ✅ Arquivada | 2026-05-16 → 2026-05-18 (tag `v1.0`) |
| v2.0 — Hardening + Consolidação Admin | ✅ Arquivada | 2026-05-19 → 2026-06-04 (tag `v2.0` / auditado) |
| **v2.1 — Landing de Crescimento & Polimento** | 🟢 Em execução | iniciado 2026-06-04, formalizado 2026-06-05 |

## Decisões desta sessão (2026-06-05)

| Decisão | Escolha | Razão |
|---|---|---|
| Escopo Landing Page | **FAQ estático mantido, Cases/Depoimentos removidos** | Simplificação visual focada em conversão direta. Estático no código economiza processamento e banco. |
| Sub-app App Louvor | **Removido totalmente do escopo** | Está hospedado em outro subdomínio e é independente; não deve existir no repositório `contato`. |
| Desacoplamento DB no Portal | **Centralizado na classe Processo (core/Processo.php)** | Remove queries do HTML das views sem a complexidade de criar uma API JS inteira em fetch/AJAX. |
| Localização do .git residual | **Remover .git da pasta home (C:\Users\diego\)** | Corrige conflito que causava lentidão ao monitorar a home inteira do Windows; projeto ativo continua no Drive. |

## Dívidas técnicas (de v1.0) → mapeadas para fases

| Dívida | Fase | Requirement | Status |
|---|---|---|---|
| D1 — Validação CSRF mal escrita nos actions/admin/ | 1 | SEC-06 | ✅ Concluído (v2.0) |
| D2 — cliente_impersonate.php usa require relativo frágil | 1 | SEC-09 | ✅ Concluído (v2.0) |
| D3 — includes/processamento.php legado coexiste com actions/ | 2 | ADM-19 | ✅ Concluído (v2.0) |
| D4 — admin.php usa JS para redirect | 2 | ADM-20 | ✅ Concluído (v2.0) |
| D5 — Senhas comprometidas — rotacionar | 1 | SEC-10 | ✅ Checklist criado (v2.0) |
| D6 — .git em C:\Users\diego\ (HOME) — realocar | 3 | OPS-01 | A fazer (v2.1) |

## Blockers

None.

## Notes

- Backup local: `Desktop/BACKUP_Vilela-eng-Site_2026-05-19_0913.zip` (184 MB)
- Tag local: `local-pre-reset-2026-05-19`
- Tag milestone v2.0 criada e homologada em 2026-06-04.
