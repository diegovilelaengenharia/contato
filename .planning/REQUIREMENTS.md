# Requirements v2.1 — Vilela Engenharia

**Milestone:** v2.1 — Landing de Crescimento & Polimento
**Status:** 🟢 Em execução (Formalizado após Grill-me em 2026-06-05)
**Criado:** 2026-06-04
**Base:** [milestones/v2.0-REQUIREMENTS.md](milestones/v2.0-REQUIREMENTS.md)

---

## 📣 Landing Page (LAND)

- [ ] **LAND-11**: Seção FAQ integrada de forma estática no HTML (perguntas/respostas comuns sobre regularização de imóveis).
- [ ] **LAND-12**: SEO/Schema.org ampliado (LocalBusiness/Engineer estruturado, sitemap e tags de compartilhamento).
- [ ] **LAND-13**: Sinais de confiança (exposição do CREA 235.474/D do Diego e contadores/métricas de processos de forma estática).

## 📱 Portal do Cliente (CLI)

- [ ] **CLI-11**: Aplicar os tokens unificados do `tokens.css` (consistência visual com admin e landing page).
- [ ] **CLI-12**: Pequenas melhorias de UX e acessibilidade nas views do portal.
- [ ] **CLI-13**: Unificar accent do portal com a cor institucional do design system.
- [ ] **CLI-14**: Desacoplar queries SQL do HTML das views, centralizando-as na classe `Processo` (core/Processo.php) e chamando-as via PHP.

## ⚙️ Deploy & QA Operacional (OPS)

- [ ] **OPS-01**: Garantir a exclusão de qualquer pasta `.git` residual na raiz da home (`C:\Users\diego\`), mantendo apenas o repositório correto na pasta do projeto no Google Drive.
- [ ] **OPS-03**: Homologação fim a fim admin-portal com UAT do usuário.
- [ ] **OPS-04**: Ativação final do deploy automático via GitHub Actions para produção Hostinger.

---

## 🚫 Removidos do Escopo do v2.1 (Decisão do Grill-me de 2026-06-05)

- **LAND-09 (Cases/Portfólio)**: Removido para simplificar a Landing Page.
- **LAND-10 (Depoimentos)**: Removido para focar na conversão direta.
- **LAND-14 (Blog/Artigos)**: Descartado por complexidade de stack.
- **OPS-02 (App Louvor)**: Removido do escopo, pois está hospedado em outro subdomínio independente e não deve ser gerenciado por este repositório.
