---
phase: 4
plan: 04
milestone: v2.0
title: "Admin Interativo (Alpine.js)"
status: ready
created: 2026-06-04
requirements: [ADM-23, ADM-24, ADM-25]
---

# PLAN 04 — Fase 4: Admin Interativo (Alpine.js)

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Base:** [04-CONTEXT.md](04-CONTEXT.md)
**Bloqueante:** Sim (para as features de valor e polimento final do painel Diego).

---

## Onda 1 — Adaptabilidade Híbrida das Actions POST (ADM-24)

### Tarefa 1.1 — Atualizar Actions de Escrita Administrativa
Refatorar as seguintes actions para detectar requisições assíncronas e retornar JSON em vez de redirecionar com `header("Location: ...")`:
1. `actions/admin/etapa_update.php`
2. `actions/admin/movimento_delete.php`
3. `actions/admin/movimento_clear_all.php`
4. `actions/admin/financeiro_create.php`
5. `actions/admin/financeiro_status_update.php`
6. `actions/admin/financeiro_delete.php`
7. `actions/admin/pendencia_create.php`
8. `actions/admin/pendencia_status_toggle.php`
9. `actions/admin/pendencia_delete.php`
10. `actions/admin/documentos_checklist_update.php`
11. `actions/admin/entregavel_upload.php`
12. `actions/admin/entregavel_delete.php`

* **Mecanismo de Detecção (AJAX):**
  ```php
  $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || (isset($_POST['format']) && $_POST['format'] === 'json')
      || (isset($_GET['format']) && $_GET['format'] === 'json');
  ```

---

## Onda 2 — Integração do Alpine.js e Estado do Cliente (ADM-23)

### Tarefa 2.1 — Importar Alpine.js via CDN no layout admin
* **Ação:** Adicionar `<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>` no layout unificado do admin (`admin/index.php` ou no cabeçalho do admin).

### Tarefa 2.2 — Adicionar controle de estado em `cliente_detalhes.php`
* **Ação:** Declarar um componente Alpine `x-data` mestre que gerencie o cliente logado, a aba ativa, e forneça helpers assíncronos de submissão (ex: submeter via `fetch` e remover/atualizar itens localmente nas listas de faturas, pendências e timeline).

---

## Onda 3 — Interatividade Assíncrona nas Parciais (ADM-23)

### Tarefa 3.1 — Refatorar as Parciais Modulares para usar diretivas Alpine.js
* **`views/partials/timeline.php`:** Excluir movimentações e gravar novos andamentos sem reload.
* **`views/partials/financeiro.php`:** Gravar faturas, mudar status da fatura e excluir registros em tempo real na tela.
* **`views/partials/pendencias.php`:** Alternar status Aberto/Resolvido e excluir pendências instantaneamente.
* **`views/partials/documentos.php`:** Submissão do checklist inicial de documentos, aprovação e exclusão de entregáveis finais.

---

## Onda 4 — Dashboard KPIs Reativos (ADM-25)

### Tarefa 4.1 — Criar o endpoint `api/admin/get_kpis.php`
* **Destino:** `area-cliente/api/admin/get_kpis.php`
* **Ação:** Responder JSON contendo a contagem de clientes ativos, processos finalizados, faturamento pendente/pago, e pendências abertas, protegido com verificação de sessão administrativa.

### Tarefa 4.2 — Integrar KPIs no Dashboard do Admin (`views/dashboard.php`)
* **Ação:** Usar Alpine.js para buscar e atualizar os dados do dashboard de forma assíncrona, eliminando a dependência de recarregamento para ver números financeiros atualizados.

---

## Verificação pós-execução

### Testes técnicos de integridade:
* **Segurança CSRF:** Garantir que todos os disparos `fetch()` efetuados pelo Alpine.js nos formulários enviem corretamente o token CSRF (`csrf_token`) e que requisições sem token válido sejam rejeitadas com erro 400.
* **PHP Lint:** Validar a conformidade sintática dos arquivos PHP alterados.

### Validação manual (UAT):
- [ ] Trocar de abas na tela de detalhes do cliente e recarregar -> a aba ativa deve persistir.
- [ ] Adicionar um novo andamento ou fatura financeira -> a nova linha deve aparecer na tabela imediatamente sem piscar ou recarregar a página inteira.
- [ ] Alterar o status de uma fatura financeira ou pendência -> o badge visual deve mudar de cor instantaneamente.
