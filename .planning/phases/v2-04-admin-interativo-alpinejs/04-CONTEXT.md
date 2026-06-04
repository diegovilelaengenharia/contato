# Contexto de Decisão — Fase 4: Admin Interativo (Alpine.js) (v2.0)

Este documento registra as decisões técnicas sobre o uso de Alpine.js e APIs JSON assíncronas para tornar o painel de controle administrativo Vilela Engenharia reativo, ágil e livre de recarregamentos desnecessários (Fase 4).

---

## ⚡ Estratégia de Frontend e Reatividade (Alpine.js)

### 1. ADM-23 — Alpine.js via CDN e Hibridismo PHP-Server-Rendered
* **Decisão:** Carregar o Alpine.js via CDN no cabeçalho do admin e estruturar a reatividade sob a filosofia progressiva: o PHP renderiza o estado inicial no lado do servidor, e o Alpine.js assume o gerenciamento dinâmico de interações e submissões assíncronas de formulários.
* **Benefício:** Evita a complexidade e lentidão de reescrever as views em SPA puro, mantendo as abas e dados legíveis instantaneamente no carregamento inicial e atualizando a tela sem que o usuário perceba qualquer reload.

### 2. ADM-24 — API JSON de Escrita Híbrida (Reaproveitamento de Actions)
* **Decisão:** Em vez de criarmos endpoints paralelos duplicando código, iremos atualizar as **actions administrativas existentes** (`actions/admin/`) para suportarem comportamento híbrido.
* **Funcionamento:**
  * O script detectará se a requisição é um disparo AJAX (via parâmetro `ajax=1`, `json=1` ou header `X-Requested-With`).
  * Se for AJAX, em vez de efetuar o redirecionamento com `header("Location: ...")`, o script responderá com o header `Content-Type: application/json` e imprimirá o JSON de sucesso ou erro:
    * Sucesso: `echo json_encode(['success' => true, 'message' => 'Operação concluída']);`
    * Erro: `http_response_code(400); echo json_encode(['success' => false, 'error' => $e->getMessage()]);`

### 3. Endpoints a Atualizar:
* `actions/admin/etapa_update.php` (Timeline)
* `actions/admin/movimento_delete.php` (Timeline - Excluir)
* `actions/admin/movimento_clear_all.php` (Timeline - Limpar Tudo)
* `actions/admin/financeiro_create.php` (Financeiro)
* `actions/admin/financeiro_status_update.php` (Financeiro - Status)
* `actions/admin/financeiro_delete.php` (Financeiro - Excluir)
* `actions/admin/pendencia_create.php` (Pendências)
* `actions/admin/pendencia_status_toggle.php` (Pendências - Status/Toggle)
* `actions/admin/pendencia_delete.php` (Pendências - Excluir)
* `actions/admin/documentos_checklist_update.php` (Checklist - Alterar)
* `actions/admin/entregavel_upload.php` (Entregáveis)
* `actions/admin/entregavel_delete.php` (Entregáveis - Excluir)

### 4. ADM-25 — KPIs do Dashboard Reativos
* **Decisão:** Criar um endpoint de leitura admin em `api/admin/get_kpis.php` que calcula os indicadores de faturamento (honorários pagos, pendentes, taxas, etc.) e andamentos ativos para alimentar o dashboard principal, permitindo atualização reativa rápida e sem recarregamento total.
