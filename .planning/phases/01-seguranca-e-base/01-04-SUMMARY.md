# Plan 01-04 Summary — admin_config.php corrigido para usar .env

**Status:** Concluído  
**Data:** 2026-05-16

## Arquivos Modificados

| Arquivo | O que mudou |
|---------|-------------|
| `area-cliente/admin_config.php` | Bloco `update_password` reescrito: de `preg_replace` em `db.php` para `parse_ini_file` + `file_put_contents` em `.env` |

## Bloco substituído

**Antes (removido):** Lia `db.php` com `file_get_contents()`, localizava `define('ADMIN_PASSWORD', ...)` com `preg_match`, substituía com `preg_replace`, gravava de volta em `db.php`.

**Depois (novo):** Lê `.env` com `parse_ini_file(__DIR__ . '/.env')`, atualiza `$env['ADMIN_PASSWORD']`, reconstrói o arquivo linha a linha, grava com `file_put_contents`.

## Comportamento esperado em runtime

A constante PHP `ADMIN_PASSWORD` é definida via `define()` em `db.php` e não pode ser redefinida na mesma requisição. Após gravar o novo `.env`, a constante na requisição atual ainda tem o valor antigo. **Na próxima requisição (após logout/login), a nova senha estará ativa.** A mensagem de sucesso já orienta Diego sobre isso.

## Restante do arquivo preservado

- `require 'includes/init.php'` — linha 6, intacto
- Bloco `save_settings` — intacto
- Bloco `backup` (`action=backup`) — intacto
- Bloco `clean_logs` — intacto
- HTML completo — intacto

## Acceptance Criteria

- [x] Não contém `file_get_contents($db_file)` nem `preg_match` nem `preg_replace`
- [x] Não contém "Não foi possível localizar a definição de senha no arquivo db.php"
- [x] Contém `parse_ini_file($env_file)`
- [x] Contém `__DIR__ . '/.env'`
- [x] Contém `$env['ADMIN_PASSWORD'] = $new_pass`
- [x] Contém `file_put_contents($env_file`
- [x] Contém mensagem com "chmod 664"
- [x] `require 'includes/init.php'` preservado
- [x] `save_settings` preservado
- [x] `action=backup` preservado
