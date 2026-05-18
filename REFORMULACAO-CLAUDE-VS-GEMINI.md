# Reformulação Vilela Engenharia — Análise Claude + Refino do Plano Gemini

> Documento para revisão pelo Claude Opus. Compara o plano do Gemini CLI com o estado real do código e a roadmap GSD já existente, e propõe um plano refinado de 9 fases ordenado por risco.

---

## 0. Contexto verificado no repositório (fatos)

| Item | Estado verificado em 2026-05-18 |
|---|---|
| `area-cliente/gestao_admin_99.php` | **1.393 linhas / 85 KB** (monolito principal) |
| `area-cliente/includes/processamento.php` | **857 linhas / 42 KB** (todos os POST handlers do admin num só lugar) |
| `area-cliente/includes/form_cliente_template.php` | 323 linhas / 22 KB (form gigante reusado) |
| `area-cliente/client-app/index.php` | 683 linhas (queries SQL direto na view) |
| `area-cliente/client-app/documentos_iniciais.php` | 509 linhas |
| `area-cliente/client-app/pendencias.php` | 528 linhas |
| `area-cliente/client-app/timeline.php` | 373 linhas |
| `area-cliente/style.css` + `admin_style.css` | **~55 KB de CSS** sem bundling |
| `core/` (criado pelo Gemini) | 3 classes pequenas: `Database.php`, `Auth.php`, `Processo.php` |
| `actions/` (criado pelo Gemini) | **Vazio** |
| `admin/` (criado pelo Gemini) | Só tem `admin/includes/header.php` |
| Foreign Keys em `schema.php` | **Já existem** com `ON DELETE CASCADE` em `processo_pendencias`, `processo_campos_extras`, `processo_docs_entregues` |
| GSD Roadmap | **8 fases já planejadas**, Fase 1 concluída, Fase 2 em planejamento |
| Phase 2 (Landing) GSD | **3 planos já escritos** (hero, "Como Funciona", serviços+sobre) |

---

## 1. Avaliação do plano do Gemini

### 1.1. O que o Gemini acertou ✅

- **Diagnóstico do monolito:** `gestao_admin_99.php` com ~1.300 linhas está correto (real: 1.393).
- **Padrão MVC simplificado (Vanilla PHP):** apropriado para Hostinger compartilhado, sem dependências.
- **Criação proativa das pastas** `core/`, `actions/`, `admin/` + 3 classes base.
- **Restrições técnicas preservadas:** sem React/Vue, manter PHP/MySQL, credenciais via `.env`.
- **Priorizar o admin primeiro:** correto — é o ponto de maior débito técnico.
- **`.htaccess` já bloqueando** `core/` e `actions/` (verificado linha 11-14 de `area-cliente/.htaccess`).

### 1.2. O que o Gemini errou (contradições com o código real) ❌

| # | Afirmação do Gemini | Realidade no código |
|---|---|---|
| 1 | Task 3.2: "Refatorar BD para incluir FKs" | FKs **já existem** em `schema.php` linhas 9, 69, 91 — `ON DELETE CASCADE` ativo |
| 2 | Task 1.1: extrair lógica de `processamento.php` | Subestima o tamanho — são **857 linhas** com ~12 handlers POST distintos, não é "alguns arquivos" |
| 3 | Task 4.1: "Refatorar `index.html` com Design System Outfit + Verde" | **Já planejado e detalhado** no GSD Phase 2 (3 planos prontos em `.planning/phases/02-landing-page-mobile-first/`). Duplicação |
| 4 | "Pretende reconstruir o projeto" via Gemini CLI | A fundação já foi feita por GSD na Phase 1 (segurança, `.env`, deploy). Gemini ignorou esse trabalho |
| 5 | `Processo.php` como Model completo | Só tem 4 métodos estáticos. Faltam: `getMovimentos`, `getDocumentos`, `getDocsEntregues`, `getExtras`, `updateEtapa`, `getCliente` (JOIN com tabela `clientes`) |

### 1.3. O que o Gemini deixou de fora 🕳️

1. **CSRF tokens** — `processamento.php` aceita 12+ POSTs sem qualquer token. Risco real, não é teórico.
2. **Validação de upload por MIME** — só checa extensão (vulnerável a duplo-extensão e content-type spoofing). Existem 3 endpoints de upload (`foto_capa_obra`, comprovantes, documentos).
3. **Rate limiting no login** — admin e cliente. Sem isso, brute force é trivial.
4. **`schema.php` rodando migrations a cada request** — `ALTER TABLE` toda hora em produção é lento, frágil e mascara bugs. Falta sistema de migrations versionadas.
5. **Arquivos de debug em produção:** `debug_admin.php`, `debug_syntax_admin.php`, `session_test_1.php`, `session_test_2.php`, `hello.php`, `probe.php`, `login_test.php`, `seed_test_user.php` ainda presentes no working tree (mesmo no .gitignore, podem chegar em prod via FTP manual).
6. **Singleton vs `$pdo` global:** Gemini criou `Database::getInstance()` mas o código legado usa `$pdo` direto via `db.php`. **Sem plano de migração**, vai conviver os dois e gerar inconsistência.
7. **`init.php` tem `self-healing schema` no topo de cada request** (linha 28) + `display_errors=1` ligado em produção (linhas 3-5). Vazamento de informação.
8. **Hardcoded fallback `'VilelaAdmin2025'`** em `init.php` linha 34 — se `.env` falhar silenciosamente, senha previsível é aceita.
9. **Cache busting com `?v=<?php echo time(); ?>`** (header.php linha 16) — destrói cache HTTP. Trocar por versão fixa por release.
10. **CDNs externos sem SRI** (SweetAlert2, CKEditor, Toastify) — sem `integrity=` hash, supply-chain attack possível.
11. **9 fases do processo hardcoded** em `Processo::$fases_padrao`. Mudança exige redeploy. Considerar mover para `admin_settings` ou config.
12. **Sem plano de migração paralela** — Gemini quer "fatiar `gestao_admin_99.php`" direto. Sem rota paralela, qualquer bug derruba o admin de produção que você usa hoje.
13. **Logging e auditoria:** zero menção. Já está em REQUIREMENTS v2 ("log de auditoria do admin") — mas sem isso, debug em prod é cego.
14. **CSS bundling:** ~55 KB de CSS em 2 arquivos não-minificados servidos sem `gzip` explícito.

### 1.4. Veredito sobre o plano do Gemini

**Avaliação:** estrutura conceitual correta (MVC vanilla), mas executou superficial — não leu `processamento.php`, não viu que FKs já existem, não considerou a roadmap GSD existente. **Risco principal:** o roteiro "fatie o monolito e pronto" sem migração paralela vai quebrar o admin em produção.

**Recomendação:** aproveitar `core/Database.php` e `core/Auth.php` do Gemini (estão bem feitos), descartar suposições do plano sobre FKs e refatoração da landing (já feito/planejado), e seguir o plano refinado abaixo.

---

## 2. Plano refinado — 9 fases ordenadas por risco

> Ordem: **estabilizar → blindar → desmembrar admin → modernizar portal → polir**. Cada fase é atômica e deploy-safe.

### Fase A — Higiene e Segurança Crítica *(antes de qualquer refator)*
**Por que primeiro:** sem isso, qualquer refator pode quebrar prod ou expor dados.

- A1. Remover do servidor: `debug_admin.php`, `debug_syntax_admin.php`, `session_test_*.php`, `hello.php`, `probe.php`, `login_test.php`, `seed_test_user.php`, `client-app/debug_*.php`. Adicionar deletion ao workflow do deploy.
- A2. Desligar `display_errors` em produção (`init.php` linha 3-5). Substituir por `error_log()` em arquivo fora do `public_html`.
- A3. Remover fallback hardcoded `'VilelaAdmin2025'` (`init.php` linha 34) — se `.env` falhar, **abortar** com 503, não aceitar senha previsível.
- A4. Adicionar SRI hashes nos `<script src="cdn...">` (SweetAlert2, CKEditor, Toastify).
- A5. Trocar `?v=<?php echo time(); ?>` por `?v=<?php echo APP_VERSION; ?>` (constante em `.env` ou `config/app.php`).

### Fase B — Camada de Compatibilidade `core/`
**Por que:** evitar conviver com dois padrões (`$pdo` global + `Database::getInstance()`) sem plano.

- B1. Expandir `core/Database.php`: log de erro em arquivo, não `die()` com mensagem PDO crua.
- B2. Fazer `db.php` legado virar um **wrapper** que retorna `Database::getInstance()` em `$pdo` — mantém compatibilidade enquanto migra.
- B3. Expandir `core/Processo.php` com `getMovimentos`, `getDocsEntregues`, `getExtras`, `getClienteCompleto` (JOIN `clientes`+`processo_detalhes`).
- B4. Criar `core/Cliente.php` (CRUD), `core/Financeiro.php`, `core/Pendencia.php`, `core/Documento.php`, `core/Upload.php` (validação MIME, tamanho, sanitização nome).
- B5. Criar `core/Csrf.php` — `generate()`, `validate()`. Persistir em `$_SESSION['csrf_token']`.
- B6. Criar `core/Migrations.php` — substituir `schema.php` por sistema de migrations numeradas (`001_create_clientes.sql`...) executadas via CLI, não a cada request.

### Fase C — Desmembramento de `processamento.php` (admin actions)
**Estratégia paralela:** criar handlers novos em `actions/`, manter `processamento.php` funcionando. Migrar form por form via `<form action="actions/xxx.php">`.

Mapa de extração (12 actions, uma por handler POST):
- `actions/admin/processo_header_update.php` (update_processo_header)
- `actions/admin/etapa_update.php` (atualizar_etapa)
- `actions/admin/movimento_create.php` / `movimento_delete.php` / `movimento_update.php`
- `actions/admin/financeiro_create.php` / `financeiro_update_status.php` / `financeiro_delete.php`
- `actions/admin/pendencia_create.php` / `pendencia_resolve.php` / `pendencia_delete.php`
- `actions/admin/documento_upload.php` / `documento_delete.php`
- `actions/admin/cliente_create.php` / `cliente_update.php` / `cliente_delete.php`
- `actions/admin/campos_extras_save.php`

Cada action: valida CSRF → valida input → chama método do Model em `core/` → redireciona com flash message em `$_SESSION`.

### Fase D — Desmembramento de `gestao_admin_99.php` (admin views)
**Estratégia:** roteador em `admin/index.php?view=xxx`, cada `view` é um include curto.

- `admin/index.php` (router + auth check + layout)
- `admin/views/dashboard.php`
- `admin/views/clientes_lista.php`
- `admin/views/cliente_detalhe.php` (com tabs)
- `admin/views/cliente_processo.php`
- `admin/views/cliente_financeiro.php`
- `admin/views/cliente_pendencias.php`
- `admin/views/cliente_documentos.php`
- `admin/views/cliente_dados.php` (form pessoal/imóvel)
- `admin/partials/topbar.php`, `sidebar.php`, `flash.php`, `csrf_field.php`
- Manter `gestao_admin_99.php` como redirect 301 → `admin/index.php` por 30 dias, depois apagar.

### Fase E — Portal do Cliente (refator + UX)
**Goal:** remover SQL das views, garantir mobile-first impecável (iPhone SE 320px).

- E1. Mover queries de `client-app/index.php`, `pendencias.php`, `financeiro.php`, `documentos.php`, `timeline.php` para métodos em `core/`.
- E2. Header premium (branco/verde) unificado em `client-app/partials/header.php`.
- E3. Timeline interativa estilo "Pizza Tracker" (proposta original em `propostas_sistema.md` item 3): cada etapa clicável abre modal explicando "o que é, o que o cliente faz, prazo médio".
- E4. Notificações in-app (bell icon + badge) baseadas em `processo_movimentos.tipo_movimento` recente.
- E5. CSS específico do portal isolado em `client-app/css/portal.css`, importa variáveis de `:root` do `style.css` global.

### Fase F — Landing Page (já em curso via GSD Phase 2)
**Não duplicar.** A Phase 2 do GSD já tem 3 planos detalhados. Apenas adicionar:
- F1. Lazy loading de imagens (`loading="lazy"`).
- F2. Preconnect aos CDNs de fontes/CKEditor.
- F3. WhatsApp com mensagem pré-formatada por tipo de serviço (LAND-08).

### Fase G — Regras de Negócio (Engenharia)
- G1. **Validação de transição de etapa:** servidor rejeita pular etapa sem campos obrigatórios da fase atual preenchidos (ex.: sair de "Análise Técnica" exige `link_drive_pasta` e taxas com status decidido).
- G2. Auditoria mínima: tabela `audit_log` (admin_user, action, entity, entity_id, payload_json, created_at) — gravar em toda action.
- G3. Geração de documentos (DocGen do `propostas_sistema.md` item 2): templates `.docx` em `core/templates/`, biblioteca `PHPWord` via Composer (verificar suporte na Hostinger).

### Fase H — Performance e Polimento
- H1. Bundling CSS: minificar `style.css` + `admin_style.css` no deploy (workflow GitHub Actions).
- H2. Habilitar `gzip` via `.htaccess` (`<IfModule mod_deflate.c>`) e cache headers para `/assets/`, `/uploads/`.
- H3. Imagens em `<picture>` com WebP fallback para JPG.
- H4. Lighthouse > 90 mobile nas 3 interfaces (landing, portal, admin).

### Fase I — Observabilidade e Deploy Final
- I1. Endpoint `/health.php` simples (sem auth, sem dados) — verificar DB connectivity para monitoring.
- I2. UptimeRobot ou similar apontando para `health.php`.
- I3. Logs estruturados (JSON Lines) em arquivo fora do `public_html`, rotacionados.
- I4. Validar deploy end-to-end com checklist antes de marcar Phase 8 do GSD como done.

---

## 3. Diferenças vs. plano do Gemini

| Tema | Gemini | Meu refino |
|---|---|---|
| Ordem | Admin → Portal → Engenharia → Landing | Higiene/segurança → core → admin → portal → landing → engenharia → perf → deploy |
| Migração admin | "Fatiar" direto | Paralela (`actions/` novo + `processamento.php` legado convivendo, redirect 301 ao final) |
| FKs | "Adicionar" | Já existem, não tocar |
| Landing | Refatorar | Já em curso via GSD Phase 2, só adicionar lazy/preconnect |
| CSRF | Não menciona | `core/Csrf.php` obrigatório em todas actions |
| Uploads | Whitelist extensão | + MIME + tamanho + sanitização nome |
| Migrations | Não menciona | Sistema versionado, sai do `schema.php` autopiloto |
| Logging | Não menciona | `audit_log` + log estruturado fora do public_html |
| Debug files | Não menciona | Apagar **antes** de qualquer refator |
| `init.php` `display_errors=1` | Não menciona | Desligar em prod (fase A2) |
| Hardcoded senha fallback | Não menciona | Remover (fase A3) |

---

## 4. Integração com o GSD Roadmap existente

Não jogar fora o GSD. Mapeamento sugerido:

| Fase deste plano | GSD existente |
|---|---|
| A (Higiene + Segurança Crítica) | **Nova Phase 1.5** — inserir entre Phase 1 (done) e Phase 2 |
| B (Core wrappers) | **Nova Phase 1.6** ou prerequisito de Phase 5 |
| C (`processamento.php` → `actions/`) | Phase 6+7 do GSD |
| D (`gestao_admin_99.php` → `admin/views`) | Phase 5+6 do GSD |
| E (Portal) | Phase 3 + 4 do GSD |
| F (Landing) | Phase 2 do GSD (em curso) |
| G (Regras de negócio) | Nova Phase 9 (post-v1) |
| H (Performance) | Phase 8 do GSD |
| I (Observabilidade) | Nova Phase 10 |

**Sugestão de comando:** `/gsd-phase` para inserir as novas fases A/B antes da Phase 2 no `ROADMAP.md`.

---

## 5. Perguntas para o Claude Opus refinar

1. **Composer na Hostinger compartilhada:** validar se a hospedagem aceita Composer (PHPWord, league/csv). Se não, manter tudo vanilla — afeta Fase G.
2. **Migrations versionadas:** vale CLI via SSH (Hostinger oferece SSH em planos Premium+) ou um runner web protegido por token? Decidir antes da Fase B6.
3. **Notificações in-app vs WhatsApp:** começar por qual? `propostas_sistema.md` item 4 já apontava para WhatsApp wa.me links. Decidir antes da Fase E4.
4. **Auditoria minimum viable:** logar só admin actions ou também leituras sensíveis (download de doc do cliente)?
5. **Renomear `gestao_admin_99.php`:** o nome com `_99` é vestígio histórico. Pode quebrar bookmark/atalho seu — confirmar.

---

## 6. Próximos comandos sugeridos (GSD)

```
# 1. Inserir fase de higiene crítica antes da Phase 2:
/gsd-phase add "Higiene e Segurança Crítica" --before 2

# 2. Inserir fase de core wrappers:
/gsd-phase add "Core Wrappers e Migrations" --before 2

# 3. Continuar com Phase 2 (Landing) já planejada:
/gsd-execute-phase 2

# 4. Após Phase 2, planejar a nova Phase A (higiene):
/gsd-discuss-phase 3   # (que agora é Higiene)
```

---

**Resumo executivo para o Opus:** o plano do Gemini está estruturalmente correto mas omite segurança crítica (CSRF, uploads, debug files, hardcoded fallback), duplica trabalho da Phase 2 GSD já planejada, e propõe migração arriscada sem rota paralela. Este refino reorganiza em 9 fases ordenadas por risco, integra com o GSD existente, e preserva o código `core/` que o Gemini já criou.
