---
phase: 3
plan: 01
title: "Camada de Compatibilidade Core"
status: completed-no-plan
completed: 2026-05-18
created_post_hoc: 2026-05-19
---

# SUMMARY 03-01: Camada de Compatibilidade Core

## Contexto

Esta phase foi **executada sem PLAN.md formal** durante o trabalho de migração para a nova arquitetura. Este SUMMARY foi criado post-hoc (2026-05-19) para preservar coerência da estrutura GSD ao fechar o Milestone v1.0.

A realidade documentada em [STATE.md](../../STATE.md) confirma que esta phase foi concluída em 2026-05-18 com o objetivo de "Camada de Compatibilidade Core".

## O que foi feito (reconstituído via leitura do código)

Foi criada a pasta `area-cliente/core/` com 7 classes:

- `Auth.php` — gestão de sessão admin/cliente
- `Csrf.php` — geração/validação de tokens CSRF
- `Database.php` — singleton PDO, carrega credenciais via `db_credentials.php` (gerado pelo CI) ou `.env` fallback
- `Logger.php` — logging básico
- `Migrations.php` — migrações de schema
- `Processo.php` — domain logic do processo de cliente
- `Upload.php` — handler de uploads

Wrapper de compatibilidade `area-cliente/db.php` mantido para código legado que usa `$pdo` global.

## Por que não tem PLAN

Provavelmente foi executada sem o flow `discuss → plan → execute` do GSD, ou o PLAN foi descartado durante uma reescrita histórica do repo (force-push detectado em 2026-05-19).

## Files entregues

- `area-cliente/core/Auth.php`
- `area-cliente/core/Csrf.php`
- `area-cliente/core/Database.php`
- `area-cliente/core/Logger.php`
- `area-cliente/core/Migrations.php`
- `area-cliente/core/Processo.php`
- `area-cliente/core/Upload.php`
- `area-cliente/db.php` (wrapper compat)
- `area-cliente/includes/init.php` (atualizado para usar core/)

## Dívidas técnicas remanescentes

Identificadas durante debug session [admin-modificar-cliente.md](../../debug/admin-modificar-cliente.md):

- D1: Validação CSRF mal escrita em `actions/admin/*.php` (condicional inverte semântica)
- D2: `cliente_impersonate.php` ainda usa `require '../../db.php'` (path relativo frágil)
- D3: `includes/processamento.php` legado coexiste com `actions/admin/*.php` (duplicação de lógica)
- D4: `admin.php:62` usa JS para redirect em vez de `header()`

Estas dívidas devem entrar no escopo do Milestone v2.0.
