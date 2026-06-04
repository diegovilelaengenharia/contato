---
phase: 3
plan: 03
milestone: v2.0
title: "Sistema de Design Unificado"
status: ready
created: 2026-06-04
requirements: [DES-01, DES-02, DES-03, DES-04]
---

# PLAN 03 — Fase 3: Sistema de Design Unificado

**Milestone:** v2.0 — Hardening, Consolidação Admin & Crescimento
**Base:** [03-CONTEXT.md](03-CONTEXT.md)
**Bloqueante:** Não (mas altamente recomendado para as Fases 4 e 7).

---

## Onda 1 — Centralização de Tokens (DES-01 + DES-03)

### Tarefa 1.1 — Criar o arquivo central `tokens.css`
* **Destino:** [area-cliente/css/tokens.css](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/area-cliente/css/tokens.css)
* **Conteúdo:** Declarar em `:root` os tokens unificados de cores, tipografia, bordas, sombras e espaçamento.
* **Tokens semânticos de status:** Integrar as cores de sucesso, perigo, aviso e info.

---

## Onda 2 — Aplicação na Landing Page e Área do Cliente (DES-01 + DES-02)

### Tarefa 2.1 — Importar tokens no `style.css` da raiz (Landing Page)
* **Ação:** Adicionar `@import url('area-cliente/css/tokens.css');` no topo de [style.css](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/style.css).
* **Ajuste:** Substituir valores hexadecimais de Accent e Primary antigos por referências às variáveis correspondentes (ex: `var(--color-primary)`, `var(--color-accent)`).

### Tarefa 2.2 — Importar tokens no `area-cliente/style.css` (Área do Cliente)
* **Ação:** Adicionar `@import url('css/tokens.css');` no topo de [area-cliente/style.css](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/area-cliente/style.css).
* **Ajuste:** Harmonizar os tokens locais `:root` com os novos tokens globais, em particular a substituição do tom Gold antigo pelo novo Accent Âmbar (`#e8960f`).

---

## Onda 3 — Aplicação no App do Cliente e Admin (DES-01 + DES-02)

### Tarefa 3.1 — Importar tokens no `client-app/css/style.css` (App do Cliente)
* **Ação:** Adicionar `@import url('tokens.css');` (ou o caminho relativo correspondente) no topo de [area-cliente/client-app/css/style.css](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/area-cliente/client-app/css/style.css).
* **Ajuste:** Substituir Amarelo Antigo (`#ffba35`) e Gold pelo Accent unificado (`#e8960f`).

### Tarefa 3.2 — Importar tokens no `admin_style.css` (Painel Admin)
* **Ação:** Adicionar `@import url('css/tokens.css');` no topo de [area-cliente/admin_style.css](file:///c:/Users/diego/Meu Drive/02. Trabalho/04. Projetos (Antigravity, Gemini e Claude Code)/Site Vilela (Landing Page e Area Cliente + Admin)/area-cliente/admin_style.css).
* **Ajuste:** Garantir o alinhamento com a paleta central e tokens de forma.

---

## Onda 4 — Refinamento do Dark Mode (DES-04)

### Tarefa 4.1 — Ajustar contrastes de Dark Mode no `client-app/css/style.css`
* **Ação:** Revisar as regras de `body.dark-mode` e sub-elementos para garantir legibilidade de textos secundários (cinzas escuros que devem ser mais claros no dark mode, ex: `--text-muted: #aaaaaa`), mantendo contraste acessível contra o fundo escuro (`#121212` e `#1e1e1e`).

---

## Verificação pós-execução

### Testes técnicos de integridade:
* **Verificação de Importações:** Garantir que todos os arquivos CSS importam `tokens.css` no topo e que o carregamento não causa falhas de layout no navegador.
* **Varredura de Hex Hardcoded:** Executar buscas globais por cores de marca antigas (`#ffba35`, `#d4ac36`) para assegurar que toda a interface migrou para o Accent correto.

### Validação manual (UAT):
- [ ] Visualizar a Landing Page e validar o layout de botões de conversão e hover.
- [ ] Entrar no Portal do Cliente, testar a alternância entre modo claro e escuro, e atestar a legibilidade de todas as abas.
- [ ] Abrir o Painel Administrativo do Diego e atestar que a barra lateral e os botões seguem a mesma identidade visual.
