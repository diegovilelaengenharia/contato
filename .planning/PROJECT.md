# Vilela Engenharia — Reestruturação do Site

## What This Is

Sistema web em três camadas para a Vilela Engenharia (Diego T. N. Vilela, Eng. Civil · CREA 235.474/D): uma landing page mobile-first para captar clientes via WhatsApp e redes sociais, um portal do cliente para acompanhamento de processos de regularização e aprovação de obras, e um painel administrativo para Diego gerenciar clientes, etapas e financeiro. Tudo hospedado no Hostinger (vilela.eng.br) com deploy automático via GitHub Actions.

## Core Value

Clientes acompanham seu processo sem precisar ligar para Diego, e Diego consegue atualizar tudo pelo admin sem erros ou retrabalho.

## Requirements

### Validated

- ✓ Login separado para admin e cliente — existente (Fase 01)
- ✓ Timeline de 9 etapas do processo imobiliário — existente (Fase 02)
- ✓ Gestão financeira com status pago/pendente/atrasado/isento — existente (Fase 02)
- ✓ Upload de documentos e comprovantes — existente (Fase 03)
- ✓ Botão WhatsApp no hero com link direto — existente (Fase 01)
- ✓ Botão "Área do Cliente" com estilo ghost verde — existente (Fase 01)
- ✓ Deploy via GitHub Actions → FTP → Hostinger — corrigido e funcional (Fase 04)
- ✓ Segurança do Admin (CSRF, Senhas Bcrypt, Invalidação de Cookies) — consolidado (v2.0)
- ✓ Reatividade Admin com Alpine.js & KPIs dinâmicos — concluído (v2.0)
- ✓ Exportação Excel/CSV, Notas privadas e busca na sidebar — concluído (v2.0)

### Active (Foco v2.1)

- [ ] Landing page: seção de FAQ estruturado de regularização (estático no HTML)
- [ ] Landing page: SEO estruturado (Schema.org de LocalBusiness/Engineer, sitemap, meta tags)
- [ ] Landing page: Sinais de confiança (número de processos, exposição CREA 235.474/D)
- [ ] Portal do Cliente: polimento visual aplicando `tokens.css` (Outfit + cores unificadas)
- [ ] Portal do Cliente: unificar accent color e aplicar melhorias de UX/acessibilidade
- [ ] Portal do Cliente: desacoplar queries de banco das telas do portal, encapsulando no PHP (`Processo::methods()`)
- [ ] Operacional: limpeza do `.git` fantasma da pasta home (`C:\Users\diego\`) para acelerar Git local
- [ ] QA: Homologação fim a fim admin-portal com UAT do usuário
- [ ] Deploy: Ativação final do workflow automático de deploy

### Out of Scope

- Blog/artigos técnicos — decidido adiar no Grill-me v2.1
- Portfólio de obras e Depoimentos de clientes na Landing Page — removidos por simplificação no Grill-me v2.1
- Migração de stack (PHP → Laravel/Node) — custo-benefício não justifica no Hostinger
- App mobile nativo — site responsivo cobre o caso de uso

## Context

**Tecnologia atual:** PHP + MySQL (PDO), HTML/CSS vanilla, JavaScript puro, hospedagem compartilhada Hostinger. Sem frameworks JS ou PHP.

**Problemas atuais identificados:**
- Dívidas técnicas operacionais: presença de `.git` residual na pasta home do usuário
- Portal do cliente possui código acoplado diretamente com conexões/queries do banco nas views

**Design system existente:**
- Cor primária: `#197e63` (verde Vilela)
- Fonte: Outfit (Google Fonts), weights 400/500/600/700
- Botões: border-radius 999px, estilo ghost para cliente, sólido para ação primária
- Logo existente é a base da identidade visual

**Banco de dados:** MySQL em `srv1074.hstgr.io`, banco `u884436813_cliente`. Tabelas: clientes, processo_detalhes, processo_financeiro, processo_movimentos, processo_pendencias, processo_docs_entregues, admin_settings, processo_campos_extras.

**Git/Deploy:** Repositório agora em `github.com/diegovilelaengenharia/contato`, branch `main`. GitHub Actions (deploy.yml) faz upload via FTPS para `/domains/vilela.eng.br/public_html/` a cada push em main.

## Constraints

- **Stack:** PHP + MySQL (Hostinger) — sem mudança de tecnologia de backend
- **Frontend:** HTML/CSS/JS puro, sem bundlers e sem build no CI. Reatividade pontual permitida via Alpine.js (CDN) onde o estado é complexo.
- **Design:** Verde `#197e63` como cor primária, logo atual como âncora visual
- **Hospedagem:** Hostinger shared — sem Docker, sem SSH root, sem Node.js em servidor
- **Domínio:** vilela.eng.br (HTTPS forçado via .htaccess)

## Key Decisions

| Decisão | Rationale | Outcome |
|---|---|---|
| Manter PHP + MySQL | Hostinger shared hosting, sem custo extra, stack já conhecida | ✓ Mantido |
| Git repo na pasta do projeto | Evitar caminhos longos com espaços, deploy.yml simplificado com `local-dir: ./` | ✓ Implementado |
| Redesign admin do zero | Código atual com bugs acumulados — mais rápido refazer do que corrigir patch a patch | ✓ Base entregue na Fase 09 (design system + front-controller) |
| Credenciais para .env | Segurança básica — não expor dados de acesso no repositório público | ✓ Evoluído p/ `db_credentials.php` gerado pelo CI |
| Conceito visual do portal do cliente mantido | Usuário validou a ideia dos botões e proposta — refatorar preservando o UX | ✓ Mantido |
| Admin: Alpine.js, não React (v2.0) | Preserva design system da Fase 09, sem build no CI, manutenível por dev solo | ✓ Decidido 2026-06-04 |
| Escopo da LP v2.1 | FAQ estático no código, cases e depoimentos descartados | ✓ Decidido no Grill-me 2026-06-05 (velocidade e conversão direta) |
| App Louvor no v2.1 | Fora de escopo; está em outro subdomínio independente | ✓ Decidido no Grill-me 2026-06-05 |
| Desacoplamento DB v2.1 | Mover queries para a classe Processo | ✓ Decidido no Grill-me 2026-06-05 |

## Evolution

Este documento evolui a cada fase e marco do projeto.

---

## Current State (2026-06-05)

**Sistema:** v2.0 em produção em https://vilela.eng.br
**Milestone arquivado:** v2.0 (tag `v2.0` / auditado)
**Próximo:** Iniciar execução do v2.1

## Current Milestone: v2.1 — Landing de Crescimento & Polimento

**Status:** 🟢 Em execução (Formalizado após Grill-me em 2026-06-05)
**Goal:** Fortalecer a landing page com FAQ estruturado de regularização de imóveis, polir a interface do portal do cliente com o novo design system unificado (`tokens.css`), desacoplar queries de banco das telas do portal e realizar ajustes operacionais de deploy (com limpeza do `.git` na home do usuário).

**Target features (3 categorias):**

- 📣 **LAND** — FAQ estruturado, SEO estruturado (Schema.org), e sinais de confiança (exposição CREA 235.474/D).
- 📱 **CLI** — Integração com tokens de design system, melhorias de UX e desacoplamento do DB centralizando as consultas na classe Processo.
- ⚙️ **OPS** — Remoção de `.git` residual na home (~), homologação e deploy final do projeto principal no Drive.

<details>
<summary>Histórico de Milestones anteriores</summary>

* **v1.0** — Reescrita completa: Finalizada e arquivada em `milestones/v1.0-REQUIREMENTS.md`.
* **v2.0** — Hardening & Features Admin: Finalizada e arquivada em [milestones/v2.0-REQUIREMENTS.md](milestones/v2.0-REQUIREMENTS.md) em 2026-06-04.

</details>
