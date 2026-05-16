# Plan 01-03 Summary — deploy.yml com geração do .env

**Status:** Concluído  
**Data:** 2026-05-16

## Arquivos Modificados

| Arquivo | O que mudou |
|---------|-------------|
| `.github/workflows/deploy.yml` | Step "Gerar area-cliente/.env" inserido antes do FTP; exclude list expandida; DB_PASS gerado com aspas duplas |

## Checkpoint Humano — Concluído

- 5 GitHub Secrets cadastrados: DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD
- Secrets FTP pré-existentes preservados: FTP_HOST (147.93.64.217), FTP_USER, FTP_PASSWORD
- Deploy #22 completou com sucesso (1m 27s)

## Lições aprendidas

- DB_PASS contém `;` e `#` — caracteres especiais para `parse_ini_file()`. O `.env` gerado pelo Actions agora usa `DB_PASS="..."` com aspas duplas para garantir parse correto
- `FTP_HOST` deve ser o IP `147.93.64.217`, não o hostname do banco

## Smoke Tests

- `vilela.eng.br/area-cliente/` → 200 OK (tela de login visível)
- `vilela.eng.br/area-cliente/db.php` → 403 Forbidden (htaccess funcionando)

## Acceptance Criteria

- [x] deploy.yml contém step "Gerar area-cliente/.env a partir dos Secrets"
- [x] Step usa todos os 5 secrets de banco
- [x] Exclude list expandida com arquivos de debug
- [x] GitHub Actions workflow completa sem erros
- [x] Site área-cliente retorna login page
- [x] db.php retorna 403
