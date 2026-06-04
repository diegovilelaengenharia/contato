---
phase: 3
plan: 03
milestone: v2.1
title: "QA, Homologação & Deploy Final"
status: ready
created: 2026-06-04
requirements: [OPS-02, OPS-03, OPS-04]
---

# PLAN 03 — Fase 3: QA, Homologação & Deploy Final (v2.1)

**Milestone:** v2.1 — Landing de Crescimento & Polimento
**Base:** [03-CONTEXT.md](03-CONTEXT.md)
**Status:** Pronto para Execução

---

## Onda 1 — Correção de Caminhos no Sub-App Louvor (OPS-02)

### Tarefa 1.1 — Atualizar Registro do SW no Layout
* **Onde:** `c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/APP Louvor (PIB Oliveira)/src/layout/layout.php`.
* **Ação:** Mudar `navigator.serviceWorker.register('/sw.js')` para `navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')`.

### Tarefa 1.2 — Corrigir Caminho do Ícone no JavaScript de Notificações
* **Onde:** `c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/APP Louvor (PIB Oliveira)/assets/js/notifications.js`.
* **Ação:** Atualizar o caminho absoluto do ícone de notificação para usar `window.APP_URL` de forma dinâmica.

### Tarefa 1.3 — Relativizar Caminhos de Cache no Service Worker
* **Onde:** `c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/APP Louvor (PIB Oliveira)/sw.js`.
* **Ação:** Converter todas as rotas e assets absolutos em `urlsToCache` e os fallbacks em relativos (ex.: `'offline.html'` e `'assets/...'` em vez de `'/offline.html'` e `'/assets/...'`).

---

## Onda 2 — Validação e Homologação Fim a Fim (OPS-03)

### Tarefa 2.1 — Testes Integrados Localmente
* **Onde:** Landing Page, Portal do Cliente e Painel Admin.
* **Ação:**
  * Realizar testes cruzados de criação de processo, atualização financeira e upload de documentos.
  * Verificar a conformidade do simulador do portal com o Dark Mode e o design tokens do `tokens.css`.
  * Auditar logs e verificar se existem erros no console do navegador.

---

## Onda 3 — Deploy Automático no GitHub Actions (OPS-04)

### Tarefa 3.1 — Revisão de Exclusões no `deploy.yml`
* **Onde:** `.github/workflows/deploy.yml`.
* **Ação:** Garantir que todos os arquivos internos de controle, documentação (`.planning`, `.vscode`, `Meu Drive`, etc.) estejam na lista de exclusão do FTP para evitar uploads desnecessários.
