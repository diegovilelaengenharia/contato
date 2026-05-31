# PATTERNS 09 — Admin Design System + Lições para o futuro

Guia para que as próximas implementações no admin sejam consistentes e sem retrabalho.

## Design System do Admin (`area-cliente/admin_style.css`)

**Tokens (use sempre as variáveis, nunca cores soltas):**
- Marca: `--color-primary: #197e63`, `--color-primary-dark`, `--color-primary-tint` (fundo claro).
- Texto: `--color-text`, `--color-text-subtle`, `--color-muted`.
- Semânticos: `--bg-success/warning/danger/info` + `--text-*` correspondentes.
- Forma: `--radius-sm/--radius/--radius-lg/--radius-pill`; sombra `--shadow-xs/sm/(lg)`.

**Componentes prontos (reutilize, não recrie):**
- Cabeçalho de tela: `.page-head` (Visão Geral) ou `.admin-header-row` + `.admin-title` + `.admin-subtitle` (telas com ação no topo).
- Card/contêiner: `.admin-tab-content` (cada view interna abre o seu — NÃO embrulhar de novo no `admin.php`) ou `.form-card`.
- Tabela: `.admin-table-container` > `table.admin-table`.
- Formulário: `.form-group`/`.admin-form-group` + `.admin-form-input`.
- Botões: `.btn-save` (+ `.btn-info/.btn-warning/.btn-danger/.btn-ghost`); ícone-só: `.btn-icon`/`.btn-act` (+ `.danger`).
- Status: `.status-badge.success|warning|danger|info`.
- KPIs: `.kpi-grid-compact` > `.kpi-card-compact` > `.kpi-icon-box.(blue|amber|red|green)` + `.kpi-content .kpi-value/.kpi-label`.

**Regras de ouro:**
- **Ícones:** Material Symbols Rounded, nunca emoji.
- **Sem `<style>` inline nas views** e sem `style="..."` exceto o estritamente data-driven (ex.: cor por status).
- **Não tocar** em Auth/CSRF/PDO nem nos endpoints `actions/` ao mexer em UI.
- `admin_style.css` é carregado depois de `../style.css` (landing); por isso o `body { display:block }` neutraliza o flex-center da landing. Mantém-se assim.

## Lições para implementações futuras

1. **D6 — Repositório na HOME (crítico):** a árvore de trabalho do repo de deploy
   (`github.com/diegovilelaengenharia/contato`) fica em `C:\Users\diego\` (raiz), com
   `area-cliente/`, `.planning/`, `.github/workflows/deploy.yml`. A pasta aberta no IDE
   (`...\04. Site Vilela Engenharia\Pagina Vilela Engenharia (COM STICH)\`) é uma **cópia NÃO
   rastreada**. **Editar/portar sempre para a cópia rastreada** antes de commitar.
   Ao commitar na home, **nunca `git add -A`** (há NTUSER.DAT, AppData, etc.) — adicionar por caminho.
   *Ação recomendada:* realocar o repo para a pasta do projeto (resolver D6 de vez).
2. **Higiene de produção:** scripts de debug/teste não devem existir no repo (probe.php,
   session_test_*, test_*, debug_*, setup_credentials.php). Ver SEC-04.
3. **Performance:** evitar query dentro de loop (N+1) — usar JOIN. Padrão aplicado no dashboard.
4. **Modais:** os widgets da sidebar (`modalAniversariantes`, `modalParados`) já vêm da
   `sidebar.php`. Não dar `require` de novo nas views (gera IDs duplicados).
