---
phase: 2
plan: 02
milestone: v2.1
title: "Polimento do Portal do Cliente"
status: ready
created: 2026-06-04
requirements: [CLI-11, CLI-12, CLI-13, CLI-14]
---

# PLAN 02 — Fase 2: Polimento do Portal do Cliente (v2.1)

**Milestone:** v2.1 — Landing de Crescimento & Polimento
**Base:** [02-CONTEXT.md](02-CONTEXT.md)
**Status:** Pronto para Execução

---

## Onda 1 — Estilização Visual e Tokens (CLI-11 + CLI-13)

### Tarefa 1.1 — Aplicar Tokens CSS e Unificar Cores no Portal
* **Onde:** `area-cliente/client-app/css/style.css`.
* **Ação:** Substituir valores hexadecimais e regras hardcoded do Light Mode pelas variáveis de tokens importadas de `tokens.css`:
  - Fundo do container do app: usar `var(--color-bg)` (ou `#f8f9fa`).
  - Fundo dos cartões de atalhos e modais: usar `var(--color-surface)`.
  - Sombras dos elementos: usar `var(--shadow-sm)` e `var(--shadow-md)`.
  - Bordas dos cards: usar `var(--radius-md)` e `var(--radius-lg)`.

---

## Onda 2 — Refinamento de Tema (Dark Mode) e UX (CLI-12)

### Tarefa 2.1 — Otimizar Dark Mode no Portal
* **Onde:** `area-cliente/client-app/css/style.css` (regras do dark mode).
* **Ação:** Atualizar as variáveis de cores do Dark Mode para garantir contraste excelente:
  - Definir `--bg-app` e `--bg-card` de forma consistente usando paletas neutras escuras.
  - Certificar de que links e textos fiquem totalmente legíveis e confortáveis contra o fundo escuro.

---

## Onda 3 — Auditoria de Acoplamento do DB (CLI-14)

### Tarefa 3.1 — Validar Estrutura de Inicialização
* **Onde:** Views do portal (`index.php`, `timeline.php`, `financeiro.php`, `pendencias.php`, `documentos.php`, `documentos_iniciais.php`).
* **Ação:** Revisar que todos os scripts usem exclusivamente a inicialização de banco `init_client.php` ou a instância `Database::getInstance()` e que não haja credenciais hardcoded.
