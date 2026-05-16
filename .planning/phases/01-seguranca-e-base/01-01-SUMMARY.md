# Plan 01-01 Summary — Credenciais para .env

**Status:** Concluído  
**Data:** 2026-05-16

## Arquivos Modificados

| Arquivo | O que mudou |
|---------|-------------|
| `area-cliente/db.php` | Reescrito: credenciais removidas, agora lê tudo de `.env` via `parse_ini_file()` |
| `area-cliente/.env.example` | Criado: template público com 5 chaves e valores placeholder |
| `.gitignore` | Removida a entrada `area-cliente/db.php`; adicionada `area-cliente/.env` |

## Interface $pdo e ADMIN_PASSWORD preservadas

- `$pdo` continua sendo criado via `new PDO($dsn, $user, $pass, $options)` com as mesmas opções (ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES false)
- `define('ADMIN_PASSWORD', ...)` ainda presente, agora lendo de `$env['ADMIN_PASSWORD']`
- Todos os arquivos que fazem `require_once 'db.php'` continuam funcionando sem alteração

## Acceptance Criteria

- [x] `db.php` não contém "srv1074.hstgr.io", "Diego@159753", "VilelaAdmin2025", "u884436813_vilela"
- [x] `db.php` contém `parse_ini_file(__DIR__ . '/.env')`
- [x] `db.php` contém `http_response_code(503)` (guarda contra .env ausente)
- [x] `area-cliente/.env.example` existe com os 5 campos documentados
- [x] `.gitignore` não lista `area-cliente/db.php`
- [x] `.gitignore` lista `area-cliente/.env`
