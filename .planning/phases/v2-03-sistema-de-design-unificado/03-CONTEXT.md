# Contexto de Decisão — Fase 3: Sistema de Design Unificado (v2.0)

Este documento registra as decisões de design e a especificação dos tokens visuais unificados para guiar a Fase 3 do Milestone v2.0 do portal Vilela Engenharia.

---

## 🎨 Diretrizes e Decisões de Implementação

### 1. DES-01 — Centralização e Criação do Arquivo de Tokens
* **Decisão:** Criar um arquivo CSS único contendo a declaração de todas as variáveis customizadas CSS (CSS Variables) da marca Vilela Engenharia.
* **Caminho do Arquivo:** `area-cliente/css/tokens.css`.
* **Uso:** Todas as três folhas de estilo principais (`style.css` na raiz, `area-cliente/client-app/css/style.css` e `area-cliente/admin_style.css`) farão o `@import` desse arquivo de tokens no topo de seus respectivos códigos.

### 2. DES-02 — Accent Color Unificado
* **Decisão:** Unificar a cor de realce (Accent) de botões de chamada à ação (CTA), links destacados e badges no tom **Âmbar/Laranja Vilela (`#e8960f`)**, eliminando as discrepâncias históricas onde a Landing usava `#e8960f`, o portal do cliente usava Gold (`#d4ac36`) e o app usava Amarelo (`#ffba35`).
* **Hover:** O tom de hover do Accent será `#d4880c` (um tom ligeiramente mais escuro e encorpado).

### 3. DES-03 — Padronização de Componentes e Tokens de Forma/Sombra
* **Border Radius:**
  * Pequeno (`--radius-sm`): `8px` (usado em pequenos botões, inputs, pequenos elementos).
  * Médio (`--radius-md`): `12px` (usado em botões maiores, inputs de formulário, mini cards).
  * Grande (`--radius-lg`): `20px` (usado em cards de seção, modais, hero sections).
  * Pill (`--radius-pill`): `999px` (usado em badges de status e pílulas de navegação).
* **Sombras (Baseadas no tom corporativo verde para suavidade premium):**
  * `--shadow-xs`: `0 1px 2px rgba(25, 126, 99, 0.04)`
  * `--shadow-sm`: `0 4px 12px rgba(25, 126, 99, 0.06)`
  * `--shadow-md`: `0 8px 24px rgba(25, 126, 99, 0.08)`
  * `--shadow-lg`: `0 16px 40px rgba(25, 126, 99, 0.12)`

### 4. DES-04 — Dark Mode Coerente no Portal do Cliente
* **Decisão:** Unificar o Dark Mode no portal do cliente (`client-app/`) herdando a mesma paleta premium de tokens do arquivo central.
* **Cor de Fundo Escuro:** A cor de fundo padrão será `#121212` e a cor de superfície dos cards/containers será `#1e1e1e`.
* **Ajuste de Contrastes:** Ajustar todos os textos secundários e bordas para manter conformidade WCAG AA (contraste mínimo de 4.5:1), eliminando a opacidade crua que prejudicava a legibilidade de prazos e valores financeiros.
