# Relatório de Code Review — Painel Admin & Segurança (Fase 09 / Geral)

Este relatório apresenta os resultados da auditoria de segurança, resiliência e qualidade de código realizada nos arquivos do painel administrativo (`area-cliente/admin/` e `area-cliente/actions/admin/`).

---

## 🚨 Classificação de Severidade dos Achados

Tabela resumo dos problemas identificados:

| ID | Achado | Severidade | Componente afetado | Descrição Curta |
| :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | Validação CSRF ignorada se token for omitido | **Crítico** | `actions/admin/*.php` | Validação usa `isset($_POST['csrf_token'])` permitindo que requisições sem o token passem sem proteção. |
| **SEC-02** | Exclusão destrutiva via GET sem proteção CSRF | **Crítico** | Várias actions de exclusão | Endpoints deletam clientes, finanças, pendências e arquivos via GET, permitindo ataques CSRF diretos por links. |
| **BUG-01** | Exclusão de cliente quebrada por incompatibilidade | **Crítico** | `cliente_detalhes.php` + `cliente_delete.php` | A view envia POST com `cliente_id`, mas a action espera GET com `delete_cliente`. |
| **BUG-02** | Exclusão de entregável quebrada por parâmetro incorreto | **Crítico** | `cliente_detalhes.php` + `entregavel_delete.php` | A view envia `del_ent`, mas a action espera `id`. |
| **BUG-03** | Personificação de cliente (Impersonate) quebrado | **Crítico** | `cliente_detalhes.php` + `cliente_impersonate.php` | A view envia `cliente_id`, mas a action espera `id`. |
| **WRN-01** | Gerenciamento de sessão manual duplicado e vulnerável | **Aviso** | `cliente_impersonate.php` | Usa require relativo frágil e inicialização de sessão manual duplicada em vez do `Auth::initSession()`. |
| **WRN-02** | Logout incompleto sem remoção do cookie no navegador | **Aviso** | `init_admin.php` | Destrói a sessão no servidor mas não remove o cookie de sessão no cliente. |

---

## 🔍 Detalhamento Técnico dos Achados

### SEC-01: Validação CSRF ignorada se token for omitido (Crítico)
* **Arquivos afetados:**
  - `area-cliente/actions/admin/cliente_create.php`
  - `area-cliente/actions/admin/cliente_update.php`
  - `area-cliente/actions/admin/cliente_approve_pre.php`
  - `area-cliente/actions/admin/documentos_checklist_update.php`
  - `area-cliente/actions/admin/entregavel_upload.php`
  - `area-cliente/actions/admin/etapa_update.php`
  - `area-cliente/actions/admin/financeiro_create.php`
  - `area-cliente/actions/admin/financeiro_status_update.php`
  - `area-cliente/actions/admin/pendencia_create.php`
  - `area-cliente/actions/admin/pendencia_update.php`
  - `area-cliente/actions/admin/processo_header_update.php`
* **Vulnerabilidade:** A validação é feita da seguinte forma:
  ```php
  if (isset($_POST['csrf_token']) && !Csrf::validateToken($_POST['csrf_token'])) {
      die("Erro de validação CSRF.");
  }
  ```
  Se um atacante omitir o campo `csrf_token` do payload POST, a função `isset()` retorna `false` e a verificação é simplesmente ignorada, permitindo a execução sem nenhum token CSRF.
* **Impacto:** Vulnerabilidade severa a CSRF em todas as ações de escrita e modificação de dados do admin.
* **Correção Recomendada:** Alterar a lógica para exigir o token:
  ```php
  if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
      die("Erro de validação CSRF.");
  }
  ```

---

### SEC-02: Exclusão destrutiva via GET sem proteção CSRF (Crítico)
* **Arquivos afetados:**
  - `area-cliente/actions/admin/cliente_delete.php`
  - `area-cliente/actions/admin/financeiro_delete.php`
  - `area-cliente/actions/admin/pendencia_delete.php`
  - `area-cliente/actions/admin/movimento_delete.php`
  - `area-cliente/actions/admin/movimento_clear_all.php`
  - `area-cliente/actions/admin/entregavel_delete.php`
  - `area-cliente/actions/admin/pendencia_status_toggle.php`
* **Vulnerabilidade:** Esses endpoints processam requisições destrutivas (exclusão no banco de dados e remoção de arquivos físicos no disco via `unlink()`) através do método `GET` sem qualquer validação de token CSRF.
* **Impacto:** Um invasor pode fazer o administrador logado executar exclusões silenciosas simplesmente fazendo-o abrir um link ou carregar uma imagem maliciosa que aponte para esses endpoints.
* **Correção Recomendada:** Migrar todas as requisições de alteração/exclusão de GET para POST, usando formulários ou requisições AJAX que incluam e validem o token CSRF de forma estrita.

---

### BUG-01: Exclusão de cliente quebrada por incompatibilidade (Crítico)
* **Arquivos afetados:**
  - `area-cliente/admin/views/cliente_detalhes.php` (linha 97)
  - `area-cliente/actions/admin/cliente_delete.php` (linhas 13-14)
* **Comportamento:** A view envia os dados via formulário `POST` passando `cliente_id` no corpo da requisição. Já a action `cliente_delete.php` espera uma requisição via `GET` contendo o parâmetro `delete_cliente`.
* **Impacto:** O botão de excluir cliente no painel simplesmente não funciona (redireciona sem excluir nada).
* **Correção Recomendada:** Ajustar a action para aceitar requisições POST com `cliente_id` e validar o CSRF.

---

### BUG-02: Exclusão de entregável quebrada por parâmetro incorreto (Crítico)
* **Arquivos afetados:**
  - `area-cliente/admin/views/cliente_detalhes.php` (linha 634)
  - `area-cliente/actions/admin/entregavel_delete.php` (linhas 12-16)
* **Comportamento:** A view envia o ID do documento entregável via link GET usando o parâmetro `del_ent`. Porém, a action `entregavel_delete.php` verifica e usa o parâmetro `id` (`$_GET['id']`).
* **Impacto:** Ao clicar para excluir um documento entregável, o painel falha exibindo "Parâmetros inválidos." e não deleta o arquivo.
* **Correção Recomendada:** Uniformizar o parâmetro. Idealmente migrando para POST com `id`, ou ajustando temporariamente o link na view para enviar `id` em vez de `del_ent`.

---

### BUG-03: Personificação de cliente (Impersonate) quebrado (Crítico)
* **Arquivos afetados:**
  - `area-cliente/admin/views/cliente_detalhes.php` (linha 93)
  - `area-cliente/actions/admin/cliente_impersonate.php` (linhas 16-17)
* **Comportamento:** O botão na view passa o parâmetro `cliente_id` via GET no link. Mas a action `cliente_impersonate.php` tenta ler o parâmetro `id` (`$_GET['id']`).
* **Impacto:** A funcionalidade de "Ver como Cliente" não inicia a sessão de personificação e apenas redireciona o admin de volta para a tela inicial do admin.
* **Correção Recomendada:** Ajustar o link na view para usar `id` em vez de `cliente_id`, ou atualizar a action para ler `cliente_id`.

---

### WRN-01: Gerenciamento de sessão manual duplicado e vulnerável (Aviso)
* **Arquivo afetado:**
  - `area-cliente/actions/admin/cliente_impersonate.php` (linhas 6-8)
* **Comportamento:** O arquivo configura cookies de sessão e inicia a sessão manualmente:
  ```php
  session_set_cookie_params(0, '/');
  session_name('CLIENTE_SESSID');
  session_start();
  ```
  Isso duplica a lógica de sessão e não aplica os parâmetros recomendados de segurança (como `secure => true`, `httponly => true` e `samesite => Lax`), que são aplicados em `Auth::initSession()`. Além disso, usa um require relativo frágil: `require '../../db.php';`.
* **Correção Recomendada:** Utilizar `Auth::initSession()` para inicializar a sessão de forma homogênea e carregar a conexão com o banco de dados através da arquitetura de classes `Database::getInstance()`.

---

### WRN-02: Logout incompleto sem remoção do cookie no navegador (Aviso)
* **Arquivo afetado:**
  - `area-cliente/admin/init_admin.php` (linhas 54-58)
* **Comportamento:** A lógica de logout no bootstrap do admin apenas destrói a sessão no servidor:
  ```php
  if (isset($_GET['sair'])) {
      session_destroy();
      header("Location: ../index.php");
      exit;
  }
  ```
  Diferente do método seguro `Auth::logout()`, ela não limpa a variável global `$_SESSION` nem invalida o cookie de sessão no navegador do usuário.
* **Correção Recomendada:** Substituir o bloco de logout por uma chamada direta ao método estático `Auth::logout()`.

---

## 📈 Plano de Ação Recomendado para Correção

1. **Ajuste Imediato de Bugs de Parâmetros (Bugs Críticos):**
   - Modificar a view `cliente_detalhes.php` para enviar os parâmetros corretos exigidos pelas actions.
2. **Hardening de Segurança de CSRF (Segurança Crítica):**
   - Corrigir a lógica de verificação `isset($_POST['csrf_token'])` em todos os 11 arquivos de ações POST.
3. **Migração de GET para POST nas Exclusões (Arquitetura & Segurança):**
   - Alterar os links de exclusão na view `cliente_detalhes.php` para formulários POST compactos com inputs hidden e token CSRF.
   - Refatorar as actions de exclusão para aceitarem apenas requisições POST com validação CSRF rígida.
