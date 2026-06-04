---
phase: 2
milestone: v2.1
title: "Polimento do Portal do Cliente"
status: passed
verified_at: 2026-06-04
---

# UAT 02 — Fase 2: Polimento do Portal do Cliente (v2.1)

Este documento registra a validação e verificação de aceitação das melhorias estéticas e técnicas do Portal do Cliente.

## 🧪 Casos de Teste Executados

### CT-01: Adaptação de Tokens de Layout
* **Procedimento:** Acessar a tela inicial do Portal do Cliente (`area-cliente/client-app/index.php`).
* **Resultado Esperado:** O simulador do aplicativo centralizado (`.app-container`) deve herdar o arredondamento de borda e sombra suave do design system. Os botões de atalhos e menus devem ter cantos perfeitamente harmonizados com os tokens.
* **Resultado Obtido:** Passou.

### CT-02: Consistência do Dark Mode no Simulador
* **Procedimento:** Acessar o Portal do Cliente e alternar o tema do sistema para o modo escuro.
* **Resultado Esperado:** O fundo do simulador (`.app-container`) e dos cartões de menu muda automaticamente para cinza escuro/preto neutro, as bordas assumem coloração sutil escura e os textos de boas-vindas mudam para branco de alto contraste, garantindo leitura perfeita.
* **Resultado Obtido:** Passou.

### CT-03: Estilização do Header do Portal
* **Procedimento:** Observar a seção superior do cabeçalho do portal (onde consta o logotipo e avatar do cliente).
* **Resultado Esperado:** Fundo superior usando o verde Vilela forte e barra inferior com avatar usando o fundo e texto semânticos sem cores duras de contraste. No modo escuro, a barra do usuário fica escura automaticamente.
* **Resultado Obtido:** Passou.

### CT-04: Teste de Acessibilidade Financeira
* **Procedimento:** Acessar a página Financeiro no portal (`financeiro.php`) em modo claro e escuro.
* **Resultado Esperado:** O cabeçalho verde e o botão "Voltar" mantêm legibilidade WCAG AA. Os cards de lançamentos e badges coloridas de status (Pago, Pendente, Atrasado) possuem contrastes adequados.
* **Resultado Obtido:** Passou.

### CT-05: Integridade de Inicialização de Banco
* **Procedimento:** Navegar por todas as abas (Timeline, Financeiro, Pendências, Documentos).
* **Resultado Esperado:** Carregamento rápido de dados sem erros de banco de dados, confirmando que a inicialização de banco de dados e sessão via `init_client.php` opera com integridade.
* **Resultado Obtido:** Passou.
