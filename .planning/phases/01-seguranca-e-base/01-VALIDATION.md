---
phase: 1
slug: seguranca-e-base
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-05-16
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Nenhum — PHP puro, sem phpunit; verificação manual + smoke test via curl |
| **Config file** | N/A |
| **Quick run command** | `curl -s -o /dev/null -w "%{http_code}" https://vilela.eng.br/area-cliente/db.php` (deve retornar 403) |
| **Full suite command** | Ver checklist manual na tabela Per-Task abaixo |
| **Estimated runtime** | ~5 minutos (checklist manual após deploy) |

---

## Sampling Rate

- **After every task commit:** Verificar localmente que o arquivo modificado está correto (grep/leitura)
- **After every plan wave:** Rodar o deploy via git push e verificar no GitHub Actions
- **Before `/gsd-verify-work`:** Todos os smoke tests manuais devem estar verdes
- **Max feedback latency:** ~10 minutos por ciclo de deploy + verificação

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 1-db-01 | 01 | 1 | SEC-01 | Information Disclosure | db.php não contém strings de senha em plaintext | Code inspection | `grep -i "password\|senha\|pass" area-cliente/db.php` deve retornar só parse_ini_file | ✅ | ⬜ pending |
| 1-db-02 | 01 | 1 | SEC-02 | Information Disclosure | ADMIN_PASSWORD não hardcoded em db.php | Code inspection | `grep "ADMIN_PASSWORD" area-cliente/db.php` deve retornar só `$env['ADMIN_PASSWORD']` | ✅ | ⬜ pending |
| 1-env-01 | 01 | 1 | SEC-01 | Information Disclosure | area-cliente/.env.example commitado com placeholders | File check | `git show HEAD:area-cliente/.env.example` deve existir e conter só placeholders | ❌ W0 | ⬜ pending |
| 1-gitignore-01 | 01 | 1 | SEC-01 | Information Disclosure | area-cliente/db.php removido do .gitignore, area-cliente/.env adicionado | File check | `grep "area-cliente/db.php" .gitignore` deve retornar vazio; `grep "area-cliente/.env" .gitignore` deve existir | ✅ | ⬜ pending |
| 1-htaccess-01 | 02 | 1 | SEC-03 | Elevation of Privilege | db.php retorna 403 via HTTP direto | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/db.php` — HTTP/1.1 403 | N/A | ⬜ pending |
| 1-htaccess-02 | 02 | 1 | SEC-03 | Information Disclosure | .env retorna 403 via HTTP direto | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/.env` — HTTP/1.1 403 | N/A | ⬜ pending |
| 1-htaccess-03 | 02 | 1 | SEC-03 | Information Disclosure | config/ retorna 403 via HTTP direto | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/config/` — HTTP/1.1 403 | N/A | ⬜ pending |
| 1-deploy-01 | 03 | 2 | SEC-05 | — | deploy.yml gera .env antes do FTP upload | Code inspection | `grep -A5 "Gerar area-cliente/.env" .github/workflows/deploy.yml` deve mostrar step com secrets | ✅ | ⬜ pending |
| 1-deploy-02 | 03 | 2 | SEC-04 | Information Disclosure | debug_*.php não acessível em produção | Smoke (manual) | `curl -I https://vilela.eng.br/area-cliente/debug_admin.php` — deve dar 404 | N/A | ⬜ pending |
| 1-admin-01 | 04 | 2 | SEC-02 | Information Disclosure | admin_config.php escreve no .env, não no db.php | Code inspection | `grep "file_put_contents\|db.php" area-cliente/admin_config.php` deve retornar só .env | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Nenhum arquivo de teste automatizado para criar — fase usa verificação manual/smoke em produção

*Existing infrastructure covers all phase requirements (verificação via grep de código + curl smoke tests após deploy).*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Site conecta ao banco em produção após deploy | SEC-01 | Requer deploy real + banco de produção | Abrir `https://vilela.eng.br/area-cliente/` — deve exibir página de login sem erro PDO |
| Login admin funciona com senha do .env | SEC-02 | Requer credencial real do .env | Login em `/area-cliente/admin.php` com usuario `admin` e senha do GitHub Secret |
| db.php retorna 403 | SEC-03 | Requer servidor Apache em produção | `curl -I https://vilela.eng.br/area-cliente/db.php` — HTTP 403 |
| .env retorna 403 | SEC-03 | Requer servidor Apache em produção | `curl -I https://vilela.eng.br/area-cliente/.env` — HTTP 403 |
| debug_admin.php retorna 404 | SEC-04 | Requer deploy com exclude list ativo | `curl -I https://vilela.eng.br/area-cliente/debug_admin.php` — HTTP 404 |
| Push em main dispara deploy sem erro | SEC-05 | Requer GitHub Actions CI/CD | Ver aba Actions no GitHub após `git push origin main` — deve ser verde |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 600s (10 min — deploy + smoke test)
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
