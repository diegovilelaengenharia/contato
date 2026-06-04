# CONTEXT 02 — Fase 2: Polimento do Portal do Cliente (v2.1)

## 📌 Escopo e Objetivos
O objetivo desta fase é polir e alinhar a interface visual do Portal do Cliente (`area-cliente/client-app/`) com o **Design System Unificado** criado na milestone v2.0. Garantiremos que todas as seções e cartões herdem as variáveis semânticas de `tokens.css` de forma fiel, substituindo quaisquer valores hardcoded e otimizando a experiência do usuário.

---

## 🔒 Decisões de Conteúdo e UX

### 1. Aplicação Rígida de Tokens (CLI-11 + CLI-13)
* Mapear todos os seletores de layout de `client-app/css/style.css` para utilizar as variáveis centralizadas de `tokens.css`:
  - **Fundo da aplicação:** `--color-bg` (Light Gray / Cinza Gelo).
  - **Fundo dos cards e painéis:** `--color-surface` (Branco Puro).
  - **Botões e acentos primários:** `--color-primary` (Verde Vilela).
  - **Acessos de destaque e alertas:** `--color-accent` (Âmbar Vilela).
  - **Sombras:** Substituir sombras antigas por `--shadow-sm`, `--shadow-md` e `--shadow-strong`.
  - **Bordas:** Utilizar `--radius-md` e `--radius-lg` para arredondamento consistente de cantos.

### 2. Refinamento de Dark Mode e Acessibilidade (CLI-12)
* Otimizar as variáveis sob `@media (prefers-color-scheme: dark)` e a classe `body.dark-mode` no `client-app/css/style.css` para utilizarem fundos neutros e contrastes compatíveis com o restante da plataforma.
* Garantir que as tabelas financeiras, status de pendências e links tenham contraste WCAG AA no tema escuro.

### 3. Análise de Acoplamento do Banco de Dados (CLI-14)
* **Status:** Validado. As views em `client-app/` utilizam de forma segura o singleton `Database::getInstance()` carregado pelo bootstrap `init_client.php`. Não há credenciais expostas nas views. A arquitetura está preparada para posterior reuso ou migração de API JSON.
