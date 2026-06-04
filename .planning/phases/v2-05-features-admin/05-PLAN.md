---
phase: 5
plan: 05
milestone: v2.0
title: "Features de Valor do Admin"
status: completed
created: 2026-06-04
requirements: [FEAT-01, FEAT-02, FEAT-03, FEAT-04, FEAT-05, FEAT-06]
---

# PLAN 05 — Fase 5: Features de Valor do Admin

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Base:** Planejamento de Features Úteis de Escritório
**Status:** Concluído

---

## Onda 1 — Atualização de Banco de Dados e Actions
* **Tarefa 1.1:** Adicionar colunas `prazo_prefeitura_data` (DATE), `prazo_prefeitura_descricao` (VARCHAR) e `notas_internas` (TEXT) à migração de `processo_detalhes` em [Migrations.php](file:///area-cliente/core/Migrations.php).
* **Tarefa 1.2:** Criar endpoint `exportar_financeiro.php` para download seguro de planilha CSV.
* **Tarefa 1.3:** Criar endpoint `prazo_prefeitura_update.php` com suporte a AJAX/JSON.
* **Tarefa 1.4:** Criar endpoint `processo_notas_update.php` com suporte a AJAX/JSON.

---

## Onda 2 — Interface e Autocomplete de Busca
* **Tarefa 2.1:** Implementar busca global de clientes com autocomplete reativo local na sidebar (`sidebar.php`) via Alpine.js.
* **Tarefa 2.2:** Inserir botão de exportação Planilha (CSV) na aba `financeiro.php`.
* **Tarefa 2.3:** Adicionar área de texto de notas internas privadas na view `cliente_detalhes.php` exclusiva para administradores.

---

## Onda 3 — Rastreamento de Prazos e Auditoria Avançada
* **Tarefa 3.1:** Adicionar painel de data limite de prazos da prefeitura na aba `timeline.php`.
* **Tarefa 3.2:** Integrar filtros dinâmicos reativos de dropdown (Operação, Entidade, Operador) na view `auditoria.php`.

---

## Onda 4 — Alertas no Dashboard
* **Tarefa 4.1:** Adicionar listagem e cards de alertas de faturas financeiras em atraso em `dashboard.php`.
* **Tarefa 4.2:** Adicionar listagem e cards de prazos de processos na prefeitura expirados ou vencendo nos próximos 7 dias em `dashboard.php`.
