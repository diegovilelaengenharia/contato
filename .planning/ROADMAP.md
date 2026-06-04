# Roadmap — Vilela Engenharia

## Milestones

| Versão | Status | Período | Archive |
|---|---|---|---|
| **v1.0** — Reescrita completa | ✅ Completa | 2026-05-16 → 2026-05-18 | [milestones/v1.0-ROADMAP.md](milestones/v1.0-ROADMAP.md) |
| **v2.0** — Hardening & Features Admin | ✅ Completa | 2026-06-04 | [milestones/v2.0-ROADMAP.md](milestones/v2.0-ROADMAP.md) |
| **v2.1** — Landing de Crescimento & Polimento | 🟡 Em planejamento | iniciado 2026-06-04 | este arquivo |

---

# Milestone v2.1 — Detalhamento das Fases

**Goal:** Fortalecer a landing page mobile-first como captadora de leads de engenharia, polir a interface do portal do cliente com o novo design system unificado, e implantar deploy/QA definitivos (com realocação do repositório .git).

---

### Fase 1 — Crescimento da Landing Page (Cartão de Visita) 📣
* **Seção de portfólio/cases** de obras regularizadas (LAND-09)
* **Depoimentos** reais de clientes (LAND-10)
* **FAQ estruturado** sobre regularização (LAND-11)
* **SEO & Schema.org** expandido para LocalBusiness/Engineer, meta tags e sitemap (LAND-12)
* **Sinais de confiança** e credenciais do Diego expostos (LAND-13)
* *(opcional)* Estrutura leve de blog/artigos (LAND-14)

---

### Fase 2 — Polimento do Portal do Cliente 📱
* **Tokens unificados** aplicados para visual idêntico ao admin/landing (CLI-11)
* **Melhorias de UX** e acessibilidade (CLI-12)
* **Accent unificado** (CLI-13)
* **Revisão de queries e desacoplamento** do DB preparando reuso futuro da API (CLI-14)

---

### Fase 3 — QA, Homologação & Deploy Final ✅
* Testes cruzados admin ↔ portal
* Auditoria final de segurança pós-deploy
* UAT final com o Diego
* Realocação do repositório `.git` (retirar da home do usuário Windows)
* Ajustar caminhos de outros sub-apps (App Louvor)
* Configuração de CI com GitHub Actions em produção.
