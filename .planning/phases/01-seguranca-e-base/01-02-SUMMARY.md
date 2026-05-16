# Plan 01-02 Summary — .htaccess de Bloqueio

**Status:** Concluído  
**Data:** 2026-05-16

## Arquivos Criados

| Arquivo | O que faz |
|---------|-----------|
| `area-cliente/.htaccess` | Bloqueia acesso HTTP a `db.php`, `.env` e `config/` com 403 Forbidden |

## Conteúdo do area-cliente/.htaccess

```apache
# Bloquear acesso HTTP direto a arquivos de configuração e credenciais

<FilesMatch "^(db\.php|\.env)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^config/ - [F,L]
</IfModule>
```

## .htaccess da raiz não modificado

Confirmado: raiz ainda contém `RewriteBase /` e o redirect HTTPS.

## Smoke Tests Pendentes

Os testes via `curl` dependem do deploy (Plan 01-03). Após o push:
- `curl https://vilela.eng.br/area-cliente/db.php` → deve retornar 403
- `curl https://vilela.eng.br/area-cliente/.env` → deve retornar 403

## Acceptance Criteria

- [x] `area-cliente/.htaccess` existe
- [x] Contém `FilesMatch` cobrindo `db.php` e `.env`
- [x] Contém `Deny from all`
- [x] Contém `RewriteRule ^config/` com `[F,L]`
- [x] Não contém `RewriteBase` nem `R=301`
- [x] `.htaccess` da raiz intocado
