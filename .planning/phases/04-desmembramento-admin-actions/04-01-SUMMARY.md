---
phase: 4
plan: 01
title: "Desmembramento do Backend Admin (Actions)"
status: completed
completed: 2026-05-18
created_post_hoc: 2026-05-19
---

# SUMMARY 04-01: Desmembramento do Backend Admin (Actions)

Executada conforme `04-01-PLAN.md`. Confirmação em [STATE.md](../../STATE.md).

## O que foi entregue

17 actions extraídas de `includes/processamento.php` para `area-cliente/actions/admin/`:

- `cliente_approve_pre.php`, `cliente_create.php`, `cliente_delete.php`, `cliente_impersonate.php`, `cliente_update.php`
- `documentos_checklist_update.php`
- `entregavel_delete.php`, `entregavel_upload.php`
- `etapa_update.php`
- `financeiro_create.php`, `financeiro_delete.php`, `financeiro_status_update.php`
- `movimento_clear_all.php`, `movimento_delete.php`
- `pendencia_create.php`, `pendencia_delete.php`, `pendencia_status_toggle.php`, `pendencia_update.php`
- `processo_header_update.php`

Padrão das actions: `require_once init.php` + `Auth::isAdmin()` + `Csrf::validateToken()` + `Database::getInstance()` + transação PDO + redirect.

## Issues remanescentes (descobertas em debug session 2026-05-19)

- Validação CSRF mal escrita (D1, ver debug/admin-modificar-cliente.md)
- `cliente_impersonate.php:9` ainda usa `require '../../db.php'` (D2)
- `.htaccess` bloqueava `actions/` (encontrado e corrigido em 2026-05-19, commit `6694f8c`)
