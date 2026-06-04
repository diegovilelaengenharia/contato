# Walkthrough — Fase 1: Segurança Crítica & Correção de Bugs (v2.0)

Este documento resume as melhorias e correções aplicadas durante a **Fase 1 (Segurança Crítica & Correção de Bugs)** na área administrativa e estrutural do portal **Vilela Engenharia**.

---

## 🔒 Melhorias de Segurança Implementadas

### 1. Correção de Bypass de CSRF nas 11 Actions POST (SEC-06)
Corrigimos a brecha que permitia requisições sem o token CSRF em todas as 11 ações administrativas do tipo POST. 
* **Antes:** O sistema validava o token apenas se ele estivesse presente na requisição (`isset($_POST['csrf_token']) && ...`), permitindo burlar a validação simplesmente omitindo o campo.
* **Depois:** Implementamos a verificação estrita (`!isset($_POST['csrf_token']) || ...`), bloqueando imediatamente qualquer requisição sem token válido e redirecionando de forma amigável com mensagens Toast.
* **Arquivos Corrigidos:**
  - `cliente_create.php`
  - `cliente_update.php`
  - `cliente_approve_pre.php`
  - `documentos_checklist_update.php`
  - `entregavel_upload.php`
  - `etapa_update.php`
  - `financeiro_create.php`
  - `financeiro_status_update.php`
  - `pendencia_create.php`
  - `pendencia_update.php`
  - `processo_header_update.php`

### 2. Migração de Actions Destrutivas de GET para POST + CSRF (SEC-07)
Ações críticas que removem dados foram alteradas de requisições simples via link (`GET`) para envios de formulário seguro (`POST` com verificação de token CSRF). Isso impede deleções acidentais ou ataques via links maliciosos.
* **Ações Modificadas:**
  - `cliente_delete.php` (Excluir Cliente)
  - `financeiro_delete.php` (Excluir Lançamento Financeiro)
  - `pendencia_delete.php` (Excluir Pendência)
  - `movimento_delete.php` (Excluir Histórico da Timeline)
  - `movimento_clear_all.php` (Limpar Todo o Histórico)
  - `entregavel_delete.php` (Remover Documento Entregável)
  - `pendencia_status_toggle.php` (Alternar status de pendência)
  - `financeiro_status_update.php` (Remoção da rota vulnerável de toggle rápido via GET)

### 3. Atualização da Interface (Views e Front-end) (SEC-07 + ADM-16/17)
* **Visualização Unificada (`cliente_detalhes.php`):**
  - Substituímos todos os links `<a>` de deleção por formulários inline `POST` contendo o campo de token CSRF (`Csrf::getHtmlField()`).
  - Corrigimos o link de personificação do cliente (`cliente_impersonate.php`) para passar o parâmetro seguro `id` em vez de `cliente_id`.
* **Confirmações Premium com SweetAlert2 (`admin/index.php`):**
  - Implementamos a função JS helper `confirmDelete` integrada nativamente com a biblioteca **SweetAlert2** já instalada no projeto.
  - Ao clicar para excluir qualquer registro (timelines, faturas, documentos ou clientes), um modal elegante de aviso em português do Brasil é exibido antes do envio do formulário.

### 4. Hardening de Autenticação (SEC-08 + SEC-09)
* **Senha Mestra em Bcrypt:**
  - Atualizamos o fluxo de login em `area-cliente/index.php` para aceitar senhas criptografadas nativamente via `password_verify()`.
  - Desenvolvemos uma rotina transparente que migra de forma automática e silenciosa a senha em texto plano (caso ainda esteja salva assim em `admin_settings`) para o hash seguro `bcrypt` no primeiro login de sucesso do Diego.
  - Atualizamos a página de alteração de credenciais do admin (`configuracoes.php`) para gerar o hash seguro `bcrypt` antes de salvar a nova senha no banco de dados.
* **Refatoração do Impersonate (`cliente_impersonate.php`):**
  - O script foi reescrito para utilizar a inicialização oficial de sessão e banco (`includes/init.php`), eliminando requires relativos frágeis e garantindo auditoria e controle de sessão corretos.
* **Destruição Completa de Sessão (Logout Seguro):**
  - Corrigimos as rotinas de logout em `admin/init_admin.php` e `includes/init.php` para usar o método centralizado `Auth::logout()`, garantindo a invalidação dos cookies e destruição completa dos dados em cache no servidor.

---

## 🛠️ Entregas Operacionais

* **Checklist de Rotação de Credenciais (`.planning/phases/v2-01-seguranca-critica/SEC-10-CHECKLIST.md`):**
  - Criamos o guia passo a passo em português do Brasil para auxiliar o Diego no processo de alteração de chaves e senhas críticas de FTP, SSH e Banco de Dados diretamente na Hostinger e no GitHub Secrets do repositório.

---

## 🔬 Validação e Verificação Técnica

Rodamos varreduras na base de código usando ferramentas de busca de padrões para validar que:
1. **Zero** ocorrências do padrão vulnerável de bypass de CSRF nos scripts PHP.
2. **Zero** rotas de exclusão aceitando requisições `GET` expostas.
3. **Zero** chamadas diretas a `die()` cru nas actions administrativas.
4. **Zero** links de deleção desprotegidos na view principal de gerenciamento de clientes.
