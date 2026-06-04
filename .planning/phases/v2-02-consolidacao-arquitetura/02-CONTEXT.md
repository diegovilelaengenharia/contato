# Contexto de Decisão — Fase 2: Consolidação da Arquitetura Admin (v2.0)

Este documento registra as decisões de design e implementação tomadas com o usuário para guiar a Fase 2 do Milestone v2.0 do portal Vilela Engenharia.

---

## 🧹 Diretrizes e Decisões de Implementação

### 1. ADM-19 — Remoção do Legado `includes/processamento.php`
* **Decisão:** O arquivo `area-cliente/includes/processamento.php` (923 linhas) foi totalmente substituído pelas 19 actions individualizadas e protegidas criadas na Fase 1. Portanto, ele será **excluído permanentemente** da base de código do projeto.
* **Implicação:** Nenhuma action deve depender ou incluir o arquivo `processamento.php`. A pasta `area-cliente/includes/` ficará mais limpa e focada no bootstrapping seguro do sistema.

### 2. ADM-21 — Divisão Modular de `cliente_detalhes.php` em Parciais
* **Decisão:** A view principal [cliente_detalhes.php](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/area-cliente/admin/views/cliente_detalhes.php) (875 linhas) será decomposta em arquivos menores para facilitar a manutenção e a posterior reatividade com Alpine.js.
* **Estrutura:**
  * Nova pasta: `area-cliente/admin/views/partials/`
  * Parciais criadas:
    * `timeline.php`: Timeline, andamentos e disparos de WhatsApp. Inclui o modal `modalAndamentoNew`.
    * `financeiro.php`: Faturamento, honorários e taxas. Inclui os modais `modalFinanceiroNew` e `modalStatusFinanceiroEdit`.
    * `pendencias.php`: Gestão de solicitações abertas ao cliente. Inclui o modal `modalPendenciaNew`.
    * `documentos.php`: Checklist de documentação inicial por processo e entregáveis finais enviados ao cliente. Inclui o modal `modalUploadEntregavel`.
  * A aba 5 de dados cadastrais manterá o include de `__DIR__ . '/../../includes/form_cliente_template.php'`.
* **Redução de Complexidade:** O arquivo `cliente_detalhes.php` original passará a conter apenas o header da página, a navegação de abas (pílulas), o roteamento dos includes das parciais e o JavaScript de controle visual, reduzindo seu escopo de 875 linhas para cerca de 150 linhas.

### 3. ADM-22 — Hardening de Uploads com `core/Upload.php`
* **Decisão:** Substituir a validação superficial de upload (baseada puramente na extensão no nome do arquivo) por uma validação estrita de tipo MIME real baseada na classe modular [Upload.php](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/area-cliente/core/Upload.php) em todos os uploads do admin e do portal do cliente.
* **Refatoração dos Endpoints:** As seguintes actions de uploads serão alteradas para chamar o método estático `Upload::process()`:
  * `actions/admin/cliente_create.php` (Avatar)
  * `actions/admin/cliente_update.php` (Avatar e Foto da Capa de Obra)
  * `actions/admin/entregavel_upload.php` (Projetos e Documentos entregáveis)
  * `actions/admin/etapa_update.php` (Anexos técnicos de andamentos da timeline)
  * `actions/admin/pendencia_create.php` (Anexos administrativos de pendências)
  * `upload_pendencia_cliente.php` (Portal do cliente - Envio de arquivo solicitado)
  * `upload_doc.php` (Portal do cliente - Envio de documento do checklist)
* **Expansão de Mimes Permitidos no `Upload.php`:**
  * **Documentos e Laudos:** `pdf`, `doc`, `docx`
  * **Imagens de Vistorias e Capas:** `jpg`, `jpeg`, `png`, `webp`, `gif`
  * **Compactados:** `zip`
  * **Planilhas Financeiras e Custos:** `xls`, `xlsx` (Mimes: `application/vnd.ms-excel`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`)
  * **Projetos CAD:** `dwg`, `dxf` (Mimes: `image/vnd.dwg`, `image/x-dwg`, `application/acad`, `application/x-acad`, `application/autocad_dwg`, `application/dwg`, `application/dxf`, `image/vnd.dxf`, `image/x-dxf`, `text/plain`, `text/x-dxf`)

### 4. ADM-20 — Wrapper de Compatibilidade Legada `admin.php`
* **Decisão:** Manter o arquivo `area-cliente/admin.php` como um wrapper silencioso de compatibilidade em produção. Ele realiza redirecionamentos PHP limpos via `header()` (evitando delays de JS) e traduz mensagens legadas de URL em mensagens flash modernas na sessão antes de enviar o usuário para a rota equivalente em `admin/index.php`.
