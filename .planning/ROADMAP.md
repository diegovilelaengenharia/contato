# Roadmap — Vilela Engenharia

## Milestones

| Versão | Status | Período | Archive |
|---|---|---|---|
| **v1.0** — Reescrita completa | ✅ Completa | 2026-05-16 → 2026-05-18 | [milestones/v1.0-ROADMAP.md](milestones/v1.0-ROADMAP.md) |
| **v2.0** — Hardening & Features Admin | ✅ Completa | 2026-06-04 | [milestones/v2.0-ROADMAP.md](milestones/v2.0-ROADMAP.md) |
| **v2.1** — Landing de Crescimento & Polimento | 🟢 Em execução | iniciado 2026-06-04, ativado 2026-06-05 | este arquivo |

---

# Milestone v2.1 — Detalhamento das Fases

**Goal:** Fortalecer a landing page mobile-first com FAQ estruturado de regularização de imóveis, polir a interface do portal do cliente com o novo design system unificado (`tokens.css`), desacoplar queries de banco das telas do portal e realizar ajustes operacionais de deploy (com limpeza do `.git` na home do usuário).

---

### Fase 1 — Crescimento da Landing Page (Cartão de Visita) 📣
* **FAQ estruturado** sobre regularização (LAND-11)
* **SEO & Schema.org** estruturado para LocalBusiness/Engineer, meta tags e sitemap (LAND-12)
* **Sinais de confiança** e credenciais do Diego expostos (LAND-13)

---

### Fase 2 — Polimento do Portal do Cliente 📱
* **Tokens unificados** aplicados para visual idêntico ao admin/landing (CLI-11)
* **Melhorias de UX** e acessibilidade no portal (CLI-12)
* **Accent unificado** com tokens (CLI-13)
* **Desacoplamento do DB** movendo queries de SQL das telas para a classe `Processo` (CLI-14)

---

### Fase 3 — QA, Homologação & Deploy Final ✅
* Remoção de resíduos de `.git` na home do usuário (C:\Users\diego\) (OPS-01)
* Testes cruzados admin ↔ portal (OPS-03)
* Homologação fim a fim com UAT do usuário (OPS-03)
* Ativação de CI/CD final com GitHub Actions em produção (OPS-04)
