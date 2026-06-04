---
phase: 1
milestone: v2.0
title: "Segurança Crítica & Correção de Bugs"
status: discussed
created: 2026-06-04
---

# CONTEXT — Fase 1: Segurança Crítica & Correção de Bugs

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Requirements:** SEC-06, SEC-07, SEC-08, SEC-09, SEC-10, ADM-16, ADM-17, ADM-18
**Fase bloqueante:** Sim — nenhuma outra fase avança até esta estar completa.

---

## Decisões Técnicas

### D1: Estratégia CSRF nos endpoints de exclusão (SEC-06 + SEC-07)

**Decisão:** Mini-formulários POST inline com progressive enhancement SweetAlert2.

**Como funciona:**
- Cada botão de "Excluir" na view `cliente_detalhes.php` (e dashboard) será um `<form method="POST">` com token CSRF oculto + `<input type="hidden">` para IDs.
- A confirmação visual antes de enviar é feita via SweetAlert2 (já carregado no `admin/index.php`): o JS intercepta o submit, exibe modal de confirmação e só submete se o usuário confirmar.
- Se JS falhar, o form submete normalmente (sem confirmação visual, mas com CSRF válido).

**Arquivos impactados (views):**
- `admin/views/cliente_detalhes.php` — links de exclusão de movimentos (L209), financeiro (L353), pendências (L433), entregáveis (L634), botão "Limpar Tudo" (L154)
- `admin/views/dashboard.php` — nenhum link de exclusão direto (OK)

**Arquivos impactados (actions — migrar GET→POST):**
- `actions/admin/cliente_delete.php` — aceitar POST + `$_POST['cliente_id']`
- `actions/admin/financeiro_delete.php` — aceitar POST + `$_POST['fin_id']` + `$_POST['cliente_id']`
- `actions/admin/pendencia_delete.php` — aceitar POST + `$_POST['pendencia_id']` + `$_POST['cliente_id']`
- `actions/admin/movimento_delete.php` — aceitar POST + `$_POST['movimento_id']` + `$_POST['cliente_id']`
- `actions/admin/movimento_clear_all.php` — aceitar POST + `$_POST['cliente_id']`
- `actions/admin/entregavel_delete.php` — aceitar POST + `$_POST['id']` + `$_POST['cliente_id']`
- `actions/admin/pendencia_status_toggle.php` — aceitar POST + `$_POST['pendencia_id']` + `$_POST['cliente_id']`

**Padrão CSRF corrigido em TODOS os endpoints POST (11 existentes + 7 migrados):**
```php
// ANTES (bypass possível):
if (isset($_POST['csrf_token']) && !Csrf::validateToken($_POST['csrf_token'])) { die(...); }

// DEPOIS (obrigatório):
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de validação CSRF. Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}
```

---

### D2: Hash de senha admin (SEC-08)

**Decisão:** Migration automática transparente — ao logar com senha antiga válida, o sistema hasheia e grava automaticamente.

**Fluxo:**
1. O `index.php` (login) tenta `password_verify($senha, $senhaMestraAdmin)` primeiro.
2. Se falha e `hash_equals($senhaMestraAdmin, $senha)` funciona (senha em texto plano), então:
   - `password_hash($senha, PASSWORD_DEFAULT)` → grava no `admin_settings`
   - Prossegue login normalmente
3. Na próxima vez, `password_verify` funciona diretamente.

**Arquivos impactados:**
- `area-cliente/index.php` (L92) — lógica dual de verificação
- `area-cliente/db.php` (L38-45) — leitura do `admin_settings` permanece inalterada
- Nova migration em `area-cliente/migrations/` (opcional, para forçar hash se admin não logar em X dias)

**UI de troca de senha:** A tela `admin/views/configuracoes.php` que permite alterar a senha deve usar `password_hash()` ao gravar. Verificar implementação atual.

---

### D3: Refatoração do impersonate (SEC-09)

**Decisão:** Reescrever `cliente_impersonate.php` completamente.

**Mudanças:**
- Substituir `session_set_cookie_params(0, '/')` / `session_start()` por `Auth::initSession()`
- Substituir `require '../../db.php'` por `require_once __DIR__ . '/../../includes/init.php'` (que já carrega DB, Auth, CSRF)
- Ler parâmetro `$_GET['id']` (manter compatibilidade com nome do param que a action espera)
- Corrigir na view `cliente_detalhes.php` (L93): trocar `cliente_id` por `id` no link

**Logout completo (WRN-02):**
- Em `init_admin.php` (L54-58) e `includes/init.php` (L65-68): substituir o bloco manual por `Auth::logout()`.
- `Auth::logout()` já limpa `$_SESSION`, invalida cookie e destrói sessão.

---

### D4: Rotação de credenciais (SEC-10)

**Decisão:** Híbrido — automatizar o que for possível via script + checklist operacional para SSH/FTP.

**Automatizável (via migration PHP ou script):**
- Alterar senha MySQL do usuário do banco (se o user tiver permissão `ALTER USER`, o que na Hostinger shared pode não funcionar — verificar)
- Forçar reset de `ADMIN_PASSWORD` no `admin_settings` (já coberto por SEC-08)

**Checklist operacional para o Diego:**
- [ ] Alterar senha FTP no painel Hostinger
- [ ] Alterar senha SSH no painel Hostinger
- [ ] Alterar senha MySQL no phpMyAdmin da Hostinger
- [ ] Atualizar GitHub Secrets com os novos valores
- [ ] Trigger de deploy para regenerar `db_credentials.php`

**Status no plano:** Dependência do Diego — não bloqueia as demais tarefas da fase.

---

### D5: Tratamento de erro nas actions (padrão de resposta)

**Decisão:** Flash messages via `$_SESSION` com redirect + Toastify na UI.

**Padrão:**
```php
// Sucesso:
$_SESSION['flash_message'] = ['text' => 'Cliente excluído com sucesso.', 'type' => 'success'];
header("Location: ../../admin/index.php?route=clientes");
exit;

// Erro:
$_SESSION['flash_message'] = ['text' => 'Erro ao excluir cliente: ' . $e->getMessage(), 'type' => 'error'];
header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid");
exit;
```

**Exibição:** O `admin/index.php` (L103-117) já tem a lógica de Toastify que lê `$_SESSION['flash_message']` — nenhuma mudança necessária na UI, apenas nas actions.

**Eliminar todos os `die(...)` das actions** — substituir por flash message + redirect.

---

### D6: Correção dos 3 bugs de incompatibilidade (ADM-16, ADM-17, ADM-18)

**ADM-16 (excluir cliente):**
- `cliente_delete.php`: reescrever para aceitar POST com `$_POST['cliente_id']` + CSRF
- View `cliente_detalhes.php` (L97-103): já é form POST — só ajustar a action URL se necessário

**ADM-17 (excluir entregável):**
- View `cliente_detalhes.php` (L634): trocar link GET com `del_ent` por form POST com `id`
- `entregavel_delete.php`: reescrever para POST com `$_POST['id']` + `$_POST['cliente_id']` + CSRF

**ADM-18 (impersonate):**
- View `cliente_detalhes.php` (L93): trocar `cliente_id=` por `id=` no href
- `cliente_impersonate.php`: já lê `$_GET['id']` — match correto após correção da view

---

## Ativos reutilizáveis no codebase

| Ativo | Localização | Uso na Fase 1 |
|---|---|---|
| `Csrf::validateToken()` | `core/Csrf.php` | Usado em todas as actions — lógica OK, só o padrão de chamada precisa mudar |
| `Auth::initSession()` | `core/Auth.php` | Substituirá as inicializações manuais de sessão |
| `Auth::logout()` | `core/Auth.php` | Substituirá blocos de logout incompletos |
| Flash messages / Toastify | `admin/index.php` L103-117 | Já funciona — as actions só precisam setar `$_SESSION['flash_message']` |
| SweetAlert2 | `admin/index.php` L63 | Já carregado — usar para confirmação visual de exclusão |
| `Database::getInstance()` | `core/Database.php` | Substituirá `require '../../db.php'` no impersonate |

---

## Escopo explícito — O QUE NÃO ENTRA na Fase 1

- **Não** refatorar `processamento.php` (ADM-19 → Fase 2)
- **Não** quebrar `cliente_detalhes.php` em parciais (ADM-21 → Fase 2)
- **Não** adicionar Alpine.js (Fase 4)
- **Não** tocar no CSS/design system (Fase 3)
- **Não** alterar lógica de login do cliente (já usa `password_verify`)
- **Não** tocar nas views que não tenham links de exclusão afetados

---

## Próximo passo

```
/gsd-plan-phase 1
```
