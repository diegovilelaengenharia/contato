---
phase: 2
plan: 02
milestone: v2.0
title: "Consolidação da Arquitetura Admin"
status: ready
created: 2026-06-04
requirements: [ADM-19, ADM-20, ADM-21, ADM-22]
---

# PLAN 02 — Fase 2: Consolidação da Arquitetura Admin

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Base:** [02-CONTEXT.md](02-CONTEXT.md)
**Bloqueante:** Sim.

---

## Onda 1 — Modularização da View `cliente_detalhes.php` (ADM-21)

### Tarefa 1.1 — Criar parciais modulares para as abas
Mover o código correspondente a cada aba e seus respectivos modais para arquivos PHP isolados dentro de `admin/views/partials/`:

1. **`admin/views/partials/timeline.php`**
   * Contém a aba "Timeline & WhatsApp" (tabela de histórico, formulário de etapa de obra e botão de WhatsApp).
   * Contém o modal `modalAndamentoNew`.
   
2. **`admin/views/partials/financeiro.php`**
   * Contém a aba "Financeiro" (tabelas de faturamento e taxas).
   * Contém os modais `modalFinanceiroNew` e `modalStatusFinanceiroEdit` (se existirem ou forem declarados lá).
   
3. **`admin/views/partials/pendencias.php`**
   * Contém a aba "Pendências" (tabela de solicitações e botões de ação).
   * Contém o modal `modalPendenciaNew`.
   
4. **`admin/views/partials/documentos.php`**
   * Contém a aba "Documentação" (checklist de documentos por processo e lista de entregáveis concluídos).
   * Contém o modal `modalUploadEntregavel`.

### Tarefa 1.2 — Simplificar a view mestre `cliente_detalhes.php`
Substituir os blocos gigantescos de HTML e PHP por chamadas simples de include:
```php
<!-- ------------------ ABA 1: TIMELINE & WHATSAPP ------------------ -->
<div class="admin-tab-content tab-pane active" id="pane-timeline">
    <?php require_once __DIR__ . '/partials/timeline.php'; ?>
</div>

<!-- ------------------ ABA 2: FINANCEIRO ------------------ -->
<div class="admin-tab-content tab-pane" id="pane-financeiro">
    <?php require_once __DIR__ . '/partials/financeiro.php'; ?>
</div>

<!-- ------------------ ABA 3: PENDÊNCIAS ------------------ -->
<div class="admin-tab-content tab-pane" id="pane-pendencias">
    <?php require_once __DIR__ . '/partials/pendencias.php'; ?>
</div>

<!-- ------------------ ABA 4: DOCUMENTAÇÃO ------------------ -->
<div class="admin-tab-content tab-pane" id="pane-documentos">
    <?php require_once __DIR__ . '/partials/documentos.php'; ?>
</div>
```

---

## Onda 2 — Hardening de Uploads com MIME Real (ADM-22)

### Tarefa 2.1 — Expandir a classe `core/Upload.php`
Atualizar as listas estáticas `$allowed_extensions` e `$allowed_mimes` para abranger os tipos comerciais e técnicos de engenharia acordados:
* Extensões adicionais: `xls`, `xlsx`, `dwg`, `dxf`, `webp`, `gif`
* Tipos MIME a adicionar:
  * `application/vnd.ms-excel` (xls)
  * `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (xlsx)
  * `image/vnd.dwg`, `image/x-dwg`, `application/acad`, `application/x-acad`, `application/autocad_dwg`, `application/dwg` (dwg)
  * `image/vnd.dxf`, `image/x-dxf`, `application/dxf`, `text/plain`, `text/x-dxf` (dxf)
  * `image/webp` (webp)
  * `image/gif` (gif)

### Tarefa 2.2 — Refatorar uploads administrativos e de clientes
Substituir a lógica manual de upload por `Upload::process()` em todos os arquivos relevantes, tratando retornos de sucesso/erro de forma elegante:

1. **`actions/admin/cliente_create.php`:**
   * Utilizar `Upload::process()` para salvar o avatar do cliente.
2. **`actions/admin/cliente_update.php`:**
   * Utilizar `Upload::process()` para o avatar do cliente e para a foto de capa da obra.
3. **`actions/admin/entregavel_upload.php`:**
   * Utilizar `Upload::process()` para carregar projetos e certidões entregáveis finais.
4. **`actions/admin/etapa_update.php`:**
   * Utilizar `Upload::process()` para anexos técnicos da timeline.
5. **`actions/admin/pendencia_create.php`:**
   * Utilizar `Upload::process()` para anexos do administrador em pendências abertas.
6. **`upload_pendencia_cliente.php`:**
   * Utilizar `Upload::process()` para arquivos que o cliente anexa nas pendências.
7. **`upload_doc.php`:**
   * Utilizar `Upload::process()` para arquivos de checklist que o cliente submete.

---

## Onda 3 — Limpeza e Wrapper (ADM-19 + ADM-20)

### Tarefa 3.1 — Excluir o monolito legado
* Excluir fisicamente o arquivo `area-cliente/includes/processamento.php`.

### Tarefa 3.2 — Validar wrapper de compatibilidade
* Confirmar que o arquivo `area-cliente/admin.php` direciona as requisições legadas de URLs externas para o novo front-controller via `header()`.

---

## Verificação pós-execução

### Testes técnicos de integridade:
```bash
# 1. Confirmar que o arquivo processamento.php foi excluído
ls area-cliente/includes/processamento.php  # Deve retornar erro de inexistente

# 2. Confirmar que a view mestre está menor e modularizada
wc -l area-cliente/admin/views/cliente_detalhes.php  # Deve retornar < 200 linhas

# 3. PHP lint de todas as views e actions alteradas
find area-cliente/admin/views -name "*.php" -exec php -l {} \;
find area-cliente/actions/admin -name "*.php" -exec php -l {} \;
```

### Validação manual (UAT):
- [ ] Cadastrar novo cliente enviando avatar e foto da capa da obra -> funciona.
- [ ] Fazer upload de documento entregável (ex: PDF ou DWG) -> funciona e valida MIME.
- [ ] Enviar arquivo malicioso mascarado (ex: script PHP renomeado para `.pdf`) -> deve ser bloqueado na validação MIME.
- [ ] Alternar abas do cliente no admin e usar as ações normais (adicionar andamento, faturas, etc.) -> funciona sem quebras.
