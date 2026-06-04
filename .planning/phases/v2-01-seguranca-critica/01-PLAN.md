---
phase: 1
plan: 01
milestone: v2.0
title: "Segurança Crítica & Correção de Bugs"
status: completed
created: 2026-06-04
requirements: [SEC-06, SEC-07, SEC-08, SEC-09, SEC-10, ADM-16, ADM-17, ADM-18]
---

# PLAN 01 — Fase 1: Segurança Crítica & Correção de Bugs

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Base:** [01-CONTEXT.md](01-CONTEXT.md) · [09-REVIEW.md](../09-redesign-admin/09-REVIEW.md)
**Bloqueante:** Sim — nenhuma outra fase avança até esta estar completa.

---

## Onda 1 — Hardening CSRF (SEC-06 + SEC-07) · *pode ser paralela*

### Tarefa 1.1 — Corrigir bypass CSRF nas 11 actions POST existentes (SEC-06)

**O quê:** Trocar `if (isset($_POST['csrf_token']) && !Csrf::validateToken(...))` por `if (!isset($_POST['csrf_token']) || !Csrf::validateToken(...))` + flash message em vez de `die()`.

**Arquivos (11):**
1. `actions/admin/cliente_create.php` (L14)
2. `actions/admin/cliente_update.php` (L13)
3. `actions/admin/cliente_approve_pre.php` (L13)
4. `actions/admin/documentos_checklist_update.php` (L13)
5. `actions/admin/entregavel_upload.php` (L12)
6. `actions/admin/etapa_update.php` (L14)
7. `actions/admin/financeiro_create.php` (L13)
8. `actions/admin/financeiro_status_update.php` (L16)
9. `actions/admin/pendencia_create.php` (L13)
10. `actions/admin/pendencia_update.php` (L13)
11. `actions/admin/processo_header_update.php` (L20)

**Padrão a aplicar em cada arquivo:**
```php
// Validar CSRF (obrigatório — sem token = rejeição imediata)
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página e tente novamente.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}
```

**Critério de aceite:** `grep -rn "isset(\$_POST\['csrf_token'\]) &&" actions/admin/` retorna zero resultados.

---

### Tarefa 1.2 — Migrar 7 exclusões destrutivas de GET → POST + CSRF (SEC-07)

**O quê:** Reescrever cada action para aceitar apenas POST e validar CSRF. Substituir `die()` por flash message + redirect.

#### 1.2a — `actions/admin/cliente_delete.php` (ADM-16)

**Antes:** Espera `GET ?delete_cliente=ID`
**Depois:**
```php
<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/index.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF).', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

$cid = (int)($_POST['cliente_id'] ?? 0);
if (!$cid) {
    $_SESSION['flash_message'] = ['text' => 'ID do cliente inválido.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}

$pdo = Database::getInstance();
try {
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$cid]);
    Logger::log('DELETE', 'cliente', $cid, []);
    $_SESSION['flash_message'] = ['text' => 'Cliente excluído permanentemente.', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
} catch (PDOException $e) {
    $_SESSION['flash_message'] = ['text' => 'Erro ao excluir cliente.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}
```

#### 1.2b — `actions/admin/financeiro_delete.php`
Mesmo padrão: POST + `$_POST['fin_id']` + `$_POST['cliente_id']` + CSRF. Redirect para `?route=cliente-detalhes&id=$cid&tab=financeiro`.

#### 1.2c — `actions/admin/pendencia_delete.php`
POST + `$_POST['pendencia_id']` + `$_POST['cliente_id']` + CSRF. Redirect para `?route=cliente-detalhes&id=$cid&tab=pendencias`.

#### 1.2d — `actions/admin/movimento_delete.php`
POST + `$_POST['movimento_id']` + `$_POST['cliente_id']` + CSRF. Redirect para `?route=cliente-detalhes&id=$cid&tab=timeline`.

#### 1.2e — `actions/admin/movimento_clear_all.php`
POST + `$_POST['cliente_id']` + CSRF. Redirect para `?route=cliente-detalhes&id=$cid&tab=timeline`.

#### 1.2f — `actions/admin/entregavel_delete.php` (ADM-17)
POST + `$_POST['id']` + `$_POST['cliente_id']` + CSRF. Manter lógica de `unlink()` do arquivo. Redirect para `?route=cliente-detalhes&id=$cid&tab=documentos`.

#### 1.2g — `actions/admin/pendencia_status_toggle.php`
POST + `$_POST['pendencia_id']` + `$_POST['cliente_id']` + CSRF. Redirect para `?route=cliente-detalhes&id=$cid&tab=pendencias`.

**Nota:** `financeiro_status_update.php` tem uma rota GET (toggle rápido, L35-51) que também deve ser migrada para POST.

**Critério de aceite:** `grep -rn "REQUEST_METHOD.*GET" actions/admin/*_delete.php actions/admin/*_clear_all.php actions/admin/pendencia_status_toggle.php` retorna zero.

---

### Tarefa 1.3 — Atualizar links na view `cliente_detalhes.php` (SEC-07 + ADM-16/17)

**O quê:** Substituir todos os links `<a href="...delete...">` por `<form method="POST">` inline com CSRF + confirmação SweetAlert2.

**Locais na view:**
- L93: link impersonate → corrigir param `cliente_id` → `id` (ADM-18)
- L97-103: form exclusão cliente → corrigir action URL para nova rota
- L154-158: link "Limpar Tudo" (movimentos) → form POST
- L209-212: links excluir movimento → form POST
- L353-357: links excluir financeiro → form POST
- L420-427: form pendência toggle → já é POST, mas verificar CSRF
- L433-436: links excluir pendência → form POST
- L634-637: links excluir entregável → form POST com param `id`

**Padrão de mini-form com SweetAlert2:**
```html
<form action="../actions/admin/movimento_delete.php" method="POST" class="inline-form"
      onsubmit="return confirmDelete(event, 'Deseja excluir esta movimentação?')">
    <?php echo Csrf::getHtmlField(); ?>
    <input type="hidden" name="movimento_id" value="<?php echo $m['id']; ?>">
    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
    <button type="submit" class="btn-icon danger" title="Excluir">
        <span class="material-symbols-rounded">delete</span>
    </button>
</form>
```

**JS helper (adicionar ao final do `admin/index.php`):**
```javascript
function confirmDelete(event, message) {
    event.preventDefault();
    Swal.fire({
        title: 'Confirmar exclusão',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) event.target.submit();
    });
    return false;
}
```

**Critério de aceite:** Nenhum `<a href=` que contenha `delete`, `del_fin`, `del_pen`, `del_hist`, `del_ent`, `del_all`, `toggle` na view.

---

### Tarefa 1.4 — Atualizar redirects no `admin.php` legado

**O quê:** As actions atuais redirecionam para `../../admin.php?cliente_id=X&tab=Y&msg=Z`. Após a migração, devem ir direto para `../../admin/index.php?route=...`. Porém o `admin.php` legado já faz esse mapeamento. As novas actions devem redirecionar diretamente para o novo roteador (`admin/index.php`) usando flash messages, eliminando dependência do wrapper `admin.php`.

**Critério de aceite:** Novas actions redirecionam para `../../admin/index.php?route=...` e setam `$_SESSION['flash_message']` diretamente.

---

## Onda 2 — Hash de Senha & Impersonate (SEC-08 + SEC-09) · *depende da Onda 1*

### Tarefa 2.1 — Implementar hash bcrypt para senha admin (SEC-08)

**Arquivo:** `area-cliente/index.php`

**Mudança na lógica de login (L88-98):**
```php
$senhaMestraAdmin = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : '';
$validAdminUsers = defined('ADMIN_USERNAMES') ? ADMIN_USERNAMES : ['admin', 'vilela', 'vilela adm'];
$isAdminUser = in_array(strtolower($usuario), $validAdminUsers);

if ($isAdminUser && $senhaMestraAdmin !== '') {
    $is_hashed = (strlen($senhaMestraAdmin) >= 60 && str_starts_with($senhaMestraAdmin, '$2'));
    $login_ok = false;

    if ($is_hashed) {
        // Senha já hasheada — verificação normal
        $login_ok = password_verify($senha, $senhaMestraAdmin);
    } else {
        // Senha em texto plano — verificação legacy + migração automática
        if (hash_equals($senhaMestraAdmin, $senha)) {
            $login_ok = true;
            // Migrar para bcrypt automaticamente
            $hashed = password_hash($senha, PASSWORD_DEFAULT);
            try {
                $pdo->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = 'admin_password'")
                    ->execute([$hashed]);
                error_log("SEC-08: Senha admin migrada para bcrypt automaticamente.");
            } catch (Exception $e) {
                error_log("SEC-08: Falha ao migrar senha: " . $e->getMessage());
            }
        }
    }

    if ($login_ok) {
        $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
        $_SESSION['admin_logado'] = true;
        header("Location: admin/index.php");
        exit;
    }
}
```

**Verificar:** `admin/views/configuracoes.php` — a troca de senha deve usar `password_hash()` ao gravar.

---

### Tarefa 2.2 — Reescrever `cliente_impersonate.php` (SEC-09 + ADM-18)

**Arquivo:** `actions/admin/cliente_impersonate.php`

**Nova implementação completa:**
```php
<?php
/**
 * Ação Admin: Personificar Cliente
 * Permite ao admin visualizar o portal como se fosse o cliente.
 * Refatorado em SEC-09 para usar Auth::initSession() e Database::getInstance().
 */
require_once __DIR__ . '/../../includes/init.php';

// init.php já garante: sessão segura, admin logado, $pdo disponível

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    $_SESSION['flash_message'] = ['text' => 'ID do cliente inválido para personificação.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

if ($cliente) {
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome'];
    $_SESSION['impersonating'] = true; // flag para o portal saber
    header("Location: ../../client-app/index.php");
    exit;
}

$_SESSION['flash_message'] = ['text' => 'Cliente não encontrado.', 'type' => 'error'];
header("Location: ../../admin/index.php");
exit;
```

---

### Tarefa 2.3 — Corrigir logout incompleto (SEC-09 / WRN-02)

**Arquivos:**
- `admin/init_admin.php` (L54-58)
- `includes/init.php` (L65-68)

**Em ambos, substituir:**
```php
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
```

**Por:**
```php
if (isset($_GET['sair'])) {
    Auth::logout(); // Limpa $_SESSION, invalida cookie, destrói sessão, redireciona
    // Auth::logout() já faz exit — nada abaixo executa
}
```

---

## Onda 3 — Checklist Operacional (SEC-10) · *paralela, dependência do Diego*

### Tarefa 3.1 — Gerar checklist de rotação de credenciais

**O quê:** Criar arquivo `v2-01-seguranca-critica/SEC-10-CHECKLIST.md` com instruções passo a passo para o Diego executar no painel da Hostinger.

**Conteúdo:**
- [ ] Alterar senha FTP no painel Hostinger
- [ ] Alterar senha SSH no painel Hostinger  
- [ ] Alterar senha MySQL no phpMyAdmin da Hostinger
- [ ] Atualizar GitHub Secrets (`DB_PASS`, `FTP_PASSWORD`) com os novos valores
- [ ] Rodar deploy (`git push` ou trigger manual) para regenerar `db_credentials.php`
- [ ] Testar acesso ao site após rotação

**Status:** Não bloqueia a conclusão técnica da fase.

---

## Verificação pós-execução

### Testes automatizáveis
```bash
# 1. Nenhum CSRF com padrão antigo (bypass)
grep -rn "isset(\$_POST\['csrf_token'\]) &&" area-cliente/actions/admin/

# 2. Nenhuma exclusão via GET
grep -rn "REQUEST_METHOD.*GET" area-cliente/actions/admin/*delete*.php area-cliente/actions/admin/movimento_clear_all.php area-cliente/actions/admin/pendencia_status_toggle.php

# 3. Nenhum die() cru nas actions
grep -rn "die(" area-cliente/actions/admin/*.php

# 4. Nenhum link <a> de exclusão na view
grep -n "del_fin\|del_pen\|del_hist\|del_ent\|del_all\|delete_cliente" area-cliente/admin/views/cliente_detalhes.php

# 5. PHP lint em todos os arquivos alterados
find area-cliente/actions/admin -name "*.php" -exec php -l {} \;
php -l area-cliente/index.php
php -l area-cliente/admin/init_admin.php
php -l area-cliente/includes/init.php
```

### Validação manual (UAT)
- [ ] Login admin com senha em texto plano → funciona E senha é hasheada no banco
- [ ] Login admin subsequente com mesma senha → funciona via `password_verify`
- [ ] Excluir cliente via painel → SweetAlert2 + funciona + toast de sucesso
- [ ] Excluir lançamento financeiro → funciona
- [ ] Excluir pendência → funciona
- [ ] Excluir movimento/limpar tudo → funciona
- [ ] Excluir entregável → funciona + arquivo removido do disco
- [ ] Toggle status pendência → funciona
- [ ] "Ver como Cliente" (impersonate) → abre portal do cliente corretamente
- [ ] Logout admin → sessão destruída, cookie invalidado

---

## Resumo de arquivos impactados

| Arquivo | Tipo de mudança |
|---|---|
| `actions/admin/cliente_create.php` | Fix CSRF |
| `actions/admin/cliente_update.php` | Fix CSRF |
| `actions/admin/cliente_approve_pre.php` | Fix CSRF |
| `actions/admin/cliente_delete.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/documentos_checklist_update.php` | Fix CSRF |
| `actions/admin/entregavel_delete.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/entregavel_upload.php` | Fix CSRF |
| `actions/admin/etapa_update.php` | Fix CSRF |
| `actions/admin/financeiro_create.php` | Fix CSRF |
| `actions/admin/financeiro_delete.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/financeiro_status_update.php` | Fix CSRF + remover rota GET |
| `actions/admin/movimento_clear_all.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/movimento_delete.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/pendencia_create.php` | Fix CSRF |
| `actions/admin/pendencia_delete.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/pendencia_status_toggle.php` | **Reescrita completa** (GET→POST+CSRF) |
| `actions/admin/pendencia_update.php` | Fix CSRF |
| `actions/admin/processo_header_update.php` | Fix CSRF |
| `actions/admin/cliente_impersonate.php` | **Reescrita completa** (SEC-09) |
| `admin/views/cliente_detalhes.php` | Links→forms, fix params |
| `admin/index.php` | Adicionar JS `confirmDelete()` |
| `area-cliente/index.php` | Login dual (bcrypt + legacy) |
| `admin/init_admin.php` | Logout via `Auth::logout()` |
| `includes/init.php` | Logout via `Auth::logout()` |

**Total:** 24 arquivos · 11 fix CSRF · 8 reescritas completas · 3 ajustes de lógica · 1 view · 1 JS
