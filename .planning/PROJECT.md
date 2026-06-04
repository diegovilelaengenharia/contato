# Vilela Engenharia — Reestruturação do Site

## What This Is

Sistema web em três camadas para a Vilela Engenharia (Diego T. N. Vilela, Eng. Civil · CREA 235.474/D): uma landing page mobile-first para captar clientes via WhatsApp e redes sociais, um portal do cliente para acompanhamento de processos de regularização e aprovação de obras, e um painel administrativo para Diego gerenciar clientes, etapas e financeiro. Tudo hospedado no Hostinger (vilela.eng.br) com deploy automático via GitHub Actions.

## Core Value

Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.

## Requirements

### Validated

- ✓ Login separado para admin e cliente — existente
- ✓ Timeline de 9 etapas do processo imobiliário — existente
- ✓ Gestão financeira com status pago/pendente/atrasado/isento — existente
- ✓ Upload de documentos e comprovantes — existente
- ✓ Botão WhatsApp no hero com link direto — existente
- ✓ Botão "Área do Cliente" com estilo ghost verde — existente (conceito validado pelo usuário)
- ✓ Deploy via GitHub Actions → FTP → Hostinger — corrigido e funcional

### Active

- [ ] Landing page mobile-first com seções completas (hero, serviços, sobre, contato)
- [ ] Links para redes sociais e WhatsApp na landing page
- [ ] Redesign profissional do portal do cliente (mantendo conceito de botões/proposta atual)
- [ ] Dashboard do cliente com status, pendências, financeiro e documentos numa tela só
- [ ] Painel admin completamente refeito do zero — UI moderna e intuitiva
- [ ] Admin: gestão de clientes, etapas, financeiro e documentos sem bugs
- [ ] Segurança: credenciais do banco em variáveis de ambiente (.env)
- [ ] Remoção de scripts de debug/reset que não devem estar em produção
- [ ] .htaccess protegendo arquivos sensíveis (db.php, config/)

### Out of Scope

- Blog/artigos técnicos — decidir após reestruturação básica
- Portfólio de obras — decidir após reestruturação básica
- Migração de stack (PHP → Laravel/Node) — custo-benefício não justifica no Hostinger
- App mobile nativo — site responsivo cobre o caso de uso

## Context

**Tecnologia atual:** PHP + MySQL (PDO), HTML/CSS vanilla, JavaScript puro, hospedagem compartilhada Hostinger. Sem frameworks JS ou PHP.

**Problemas atuais identificados:**
- Painel admin (`admin.php`) acumulou bugs ao longo do desenvolvimento — difícil de manter
- Portal do cliente foi construído de forma não profissional — código funciona mas com muitos patches
- Credenciais do banco expostas no `db.php` (Diego@159753, host srv1074.hstgr.io)
- Scripts de debug e reset em produção (probe.php, session_test*, reset_db_diego.php)
- Senha admin hardcoded em texto plano no index.php ("VilelaAdmin2025")

**Design system existente:**
- Cor primária: `#197e63` (verde Vilela)
- Fonte: Outfit (Google Fonts), weights 400/500/600/700
- Botões: border-radius 999px, estilo ghost para cliente, sólido para ação primária
- Logo existente é a base da identidade visual — redesign trabalha em cima dele

**Banco de dados:** MySQL em `srv1074.hstgr.io`, banco `u884436813_cliente`. Tabelas: clientes, processo_detalhes, processo_financeiro, processo_movimentos, processo_pendencias, processo_docs_entregues, admin_settings, processo_campos_extras.

**Git/Deploy:** Repositório agora em `github.com/diegovilelaengenharia/contato`, branch `main`. GitHub Actions (deploy.yml) faz upload via FTPS para `/domains/vilela.eng.br/public_html/` a cada push em main.

## Constraints

- **Stack:** PHP + MySQL (Hostinger) — sem mudança de tecnologia de backend
- **Frontend:** HTML/CSS/JS puro, sem bundlers e sem build no CI (mantém compatibilidade com Hostinger, que não roda Node). **Reatividade pontual permitida via Alpine.js (CDN)** onde o estado é complexo (decisão v2.0). React/Vue SPA permanece fora de escopo.
- **Design:** Verde `#197e63` como cor primária, logo atual como âncora visual
- **Hospedagem:** Hostinger shared — sem Docker, sem SSH root, sem Node.js em servidor
- **Domínio:** vilela.eng.br (HTTPS forçado via .htaccess)

## Key Decisions

| Decisão | Rationale | Outcome |
|---------|-----------|---------|
| Manter PHP + MySQL | Hostinger shared hosting, sem custo extra, stack já conhecida | ✓ Mantido |
| Git repo na pasta do projeto | Evitar caminhos longos com espaços, deploy.yml simplificado com `local-dir: ./` | ✓ Implementado |
| Redesign admin do zero | Código atual com bugs acumulados — mais rápido refazer do que corrigir patch a patch | ✓ Base entregue na Fase 09 (design system + front-controller) |
| Credenciais para .env | Segurança básica — não expor dados de acesso no repositório público | ✓ Evoluído p/ `db_credentials.php` gerado pelo CI |
| Conceito visual do portal do cliente mantido | Usuário validou a ideia dos botões e proposta — refatorar preservando o UX | ✓ Mantido |
| Admin: Alpine.js, não React (v2.0) | Preserva design system da Fase 09, sem build no CI, manutenível por dev solo | ✓ Decidido 2026-06-04 |

## Evolution

Este documento evolui a cada fase e marco do projeto.

**Após cada fase:**
1. Requirements entregues? → Mover para Validated com referência da fase
2. Novos requirements? → Adicionar em Active
3. Decisions a registrar? → Adicionar em Key Decisions

**Após cada milestone:**
1. Revisar todas as seções
2. Core Value ainda correto?
3. Out of Scope ainda válido?

---

## Current State (2026-05-19)

**Sistema:** v1.5 em produção em https://vilela.eng.br
**Milestone arquivado:** v1.0 (tag `v1.0`)
**Próximo:** `/gsd-new-milestone` → planejar v2.0

### O que está ao vivo

- Landing page mobile-first (vilela.eng.br/)
- Cartão de visitas (vilela.eng.br/contato/)
- Portal do cliente (vilela.eng.br/area-cliente/)
- Admin (vilela.eng.br/area-cliente/admin.php)
- App Louvor PIB Oliveira (vilela.eng.br/applouvor/) — projeto separado mas hospedado mesmo domínio

### Arquitetura modular `core/`

`area-cliente/core/` com classes: Auth, Csrf, Database (singleton), Logger, Migrations, Processo, Upload. `actions/admin/*.php` com 17 endpoints POST modulares. Compatibilidade legacy mantida via `area-cliente/db.php` wrapper.

### Deploy CI/CD

GitHub Actions FTPS → Hostinger. Secrets injetados em `area-cliente/core/db_credentials.php` a cada deploy. App Louvor usa webhook Hostinger (`git pull` no servidor).

## Current Milestone: v2.1 — Landing de Crescimento & Polimento

**Status:** 🟡 Em planejamento (iniciado 2026-06-04)
**Goal:** Expandir a landing page como cartão de visitas para captação de leads, realizar polimento e alinhamento visual do portal do cliente, e sanar pendências operacionais de deploy e organização de repositório git.

**Target features (3 categorias):**

- 📣 **LAND** — Portfólio de obras, Depoimentos, FAQ, Schema de SEO e sinais de confiança.
- 📱 **CLI** — Integração com tokens de design system, melhorias de UX e desacoplamento do DB.
- ⚙️ **OPS** — Mover repositório .git, auditoria caminhos App Louvor, homologação e deploy final.

## Next Milestone Goals (v2.1 — esboço inicial, refinado no REQUIREMENTS.md)

- **Landing Page**: Implementar seções de engajamento de leads para Diego Vilela (cases, depoimentos e SEO estruturado).
- **Portal do Cliente**: Atualizar estilos para Outfit/verde Vilela e tokens compartilhados, unificando a identidade visual nas três camadas.
- **QA/Deploy**: Organização de ambiente local (.git e App Louvor) e automatização de entregas via FTPS na Hostinger.

<details>
<summary>Histórico de Milestones anteriores</summary>

* **v1.0** — Reescrita completa: Finalizada e arquivada em `milestones/v1.0-REQUIREMENTS.md`.
* **v2.0** — Hardening & Features Admin: Finalizada e arquivada em [milestones/v2.0-REQUIREMENTS.md](milestones/v2.0-REQUIREMENTS.md) em 2026-06-04.

</details>

