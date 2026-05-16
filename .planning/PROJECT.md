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
- Painel admin (`gestao_admin_99.php`) acumulou bugs ao longo do desenvolvimento — difícil de manter
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
- **Frontend:** HTML/CSS/JS puro — sem React, Vue ou bundlers (mantém compatibilidade com Hostinger)
- **Design:** Verde `#197e63` como cor primária, logo atual como âncora visual
- **Hospedagem:** Hostinger shared — sem Docker, sem SSH root, sem Node.js em servidor
- **Domínio:** vilela.eng.br (HTTPS forçado via .htaccess)

## Key Decisions

| Decisão | Rationale | Outcome |
|---------|-----------|---------|
| Manter PHP + MySQL | Hostinger shared hosting, sem custo extra, stack já conhecida | — Pending |
| Git repo na pasta do projeto | Evitar caminhos longos com espaços, deploy.yml simplificado com `local-dir: ./` | ✓ Implementado |
| Redesign admin do zero | Código atual com bugs acumulados — mais rápido refazer do que corrigir patch a patch | — Pending |
| Credenciais para .env | Segurança básica — não expor dados de acesso no repositório público | — Pending |
| Conceito visual do portal do cliente mantido | Usuário validou a ideia dos botões e proposta — refatorar preservando o UX | — Pending |

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
*Last updated: 2026-05-16 após inicialização do projeto GSD*
