---
phase: 5
milestone: v2.0
title: "Features de Valor do Admin"
status: passed
verified_at: 2026-06-04
---

# UAT 05 — Fase 5: Features de Valor do Admin

Este documento registra os testes de aceitação do usuário (UAT) para as funcionalidades implementadas na Fase 5.

## 🧪 Casos de Teste Executados

### CT-01: Exportação Financeira em CSV Excel-Friendly
* **Procedimento:** Acessar os detalhes de um cliente, navegar até a aba "Financeiro" e clicar no botão "Exportar Planilha (CSV)".
* **Resultado Esperado:** O navegador inicia o download do arquivo CSV. Ao abrir o arquivo no Microsoft Excel, todas as colunas de faturamento são exibidas de forma estruturada, com os acentos e caracteres especiais das descrições e nomes de clientes preservados devido ao UTF-8 BOM.
* **Resultado Obtido:** Passou.

### CT-02: Busca Global Autocomplete na Sidebar
* **Procedimento:** Na barra de busca da sidebar, começar a digitar o nome de um cliente da carteira de ativos.
* **Resultado Esperado:** Um dropdown reativo (sem recarregar a página e sem realizar novas requisições) é exibido imediatamente abaixo do input, listando os clientes correspondentes. Clicar no nome do cliente redireciona corretamente para os detalhes dele.
* **Resultado Obtido:** Passou.

### CT-03: Notas Internas Privadas
* **Procedimento:** Na aba principal de detalhes do cliente, preencher o campo "Notas Internas Privadas" e clicar no botão de salvar. 
* **Resultado Esperado:** O salvamento é efetuado via AJAX (sem recarregar a página). A caixa de texto é persistida e recarregada corretamente.
* **Resultado de Segurança:** Verificamos no banco de dados e nas APIs públicas do cliente que o conteúdo do campo `notas_internas` **nunca** é enviado para o front do cliente ou exposto fora da área restrita do Diego.
* **Resultado Obtido:** Passou.

### CT-04: Rastreamento de Prazos da Prefeitura
* **Procedimento:** Na aba "Timeline" do cliente, configurar uma data limite de prazo e uma descrição para o andamento do processo na prefeitura e clicar no botão de salvar.
* **Resultado Esperado:** O prazo limite é salvo via AJAX e exibido de forma proeminente no topo da timeline.
* **Resultado Obtido:** Passou.

### CT-05: Filtros Avançados de Auditoria
* **Procedimento:** Acessar a tela de Auditoria administrativa (`?route=auditoria`). Selecionar valores específicos nos dropdowns de Operação, Entidade e Operador.
* **Resultado Esperado:** A listagem de logs é filtrada instantaneamente via JS sem necessidade de requisições AJAX adicionais ao servidor, facilitando a navegação rápida.
* **Resultado Obtido:** Passou.

### CT-06: Alertas Críticos no Dashboard
* **Procedimento:** Acessar a página inicial do Dashboard administrativo (`?route=dashboard`).
* **Resultado Esperado:**
  - Se houver faturas com status `atrasado`, elas devem constar na lista "Pagamentos Atrasados" com o valor e atalho para gerenciá-las.
  - Se houver prazos de prefeitura vencidos ou a vencer nos próximos 7 dias, eles devem constar no painel "Prazos da Prefeitura" com badges de prioridade (vermelha para vencidos, amarela para próximos).
* **Resultado Obtido:** Passou.
