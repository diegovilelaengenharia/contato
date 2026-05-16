# Phase 2: Landing Page Mobile-First - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-05-16
**Phase:** 2-Landing Page Mobile-First
**Areas discussed:** Abordagem da reescrita, Hero com foto, Melhorias de design, Seção Serviços, Seção Sobre

---

## Abordagem da Reescrita

| Option | Description | Selected |
|--------|-------------|----------|
| One-page com scroll | Nova estrutura: hero → serviços → sobre → contato | ✓ |
| Evoluir o index.html atual | Manter estrutura e adicionar seções inline | |
| Você decide | Delegar para o planner | |

**User's choice:** One-page com scroll

---

| Option | Description | Selected |
|--------|-------------|----------|
| Reaproveitar modal de orçamento + design system CSS | Reescrever HTML, manter modal e CSS vars | |
| Do zero absoluto | Novo HTML, novo CSS, novo JS | |
| Reaproveitar o máximo possível | Manter estrutura atual e adicionar seções | ✓ |

**User's choice:** Reaproveitar o máximo possível

---

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, indexar a página | Remover noindex para aparecer no Google | ✓ |
| Manter noindex por enquanto | Deixar como está | |
| Você decide | Delegar para o planner | |

**User's choice:** Sim, indexar a página

---

## Hero com foto

| Option | Description | Selected |
|--------|-------------|----------|
| Foto de Diego substituindo a logo | foto-diego-new.jpg como elemento principal | |
| Logo + foto lado a lado | Dois elementos visuais juntos | |
| Manter a logo, foto na seção Sobre | Hero continua com logo; foto de Diego aparece em Sobre | ✓ |

**User's choice:** Manter a logo e colocar foto na seção Sobre

---

| Option | Description | Selected |
|--------|-------------|----------|
| Manter marquee animado | Sem mudança | |
| Substituir por tagline estática e proeminente | Texto fixo, fonte maior, mais clean | ✓ |
| Remover a tagline do hero | Tagline aparece em outra seção | |

**User's choice:** Substituir por tagline estática e mais proeminente

---

| Option | Description | Selected |
|--------|-------------|----------|
| Proposta de valor: "Regularização e aprovação de obras..." | Direto e profissional | ✓ |
| Você sugere o texto | Digitar texto customizado | |
| Você decide | Planner cria a tagline | |

**User's choice:** "Regularização e aprovação de obras, do projeto à averbação"

---

## Melhorias de Design (proativas)

**Sugestões apresentadas pelo Claude:**

| Melhoria | Descrição | Aprovado |
|----------|-----------|----------|
| Hero com fundo degradê verde | Gradiente sutil (verde claro → branco) | ✓ |
| Cards de serviços com descrição | 2-3 linhas por categoria | ✓ |
| Seção "Como funciona" (3 passos) | WhatsApp → Análise → Execução | ✓ |
| Nenhuma / só o mínimo | Apenas LAND-01 a LAND-08 | |

**User's choice:** Todas as 3 melhorias aprovadas
**Notes:** Usuário pediu explicitamente sugestões para modernizar e tornar a página mais atrativa para clientes.

---

## Seção Serviços

| Option | Description | Selected |
|--------|-------------|----------|
| Manter cards interativos + adicionar descrição | Preservar modal de orçamento + WhatsApp | ✓ |
| Nova seção descritiva sem modal | Lista simples + CTA único | |
| Você decide | Delegar formato para o planner | |

**User's choice:** Manter os cards interativos + adicionar descrição por categoria

---

| Option | Description | Selected |
|--------|-------------|----------|
| Planner cria o texto | Baseado no servicesData existente | ✓ |
| Eu escreverei o texto | Diego fornece os textos | |

**User's choice:** Planner cria o texto

**Notes:** Usuário aceitou sugestões proativas do Claude para esta seção também.

---

## Seção Sobre

| Option | Description | Selected |
|--------|-------------|----------|
| Foto + texto lado a lado | Mobile: empilhado; Desktop: lado a lado | ✓ |
| Texto + badges, sem foto proeminente | Seção de texto com badges de credenciais | |
| Você decide | Planner escolhe o layout | |

**User's choice:** Foto + texto lado a lado (Recomendado)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Manter texto atual + badges de credenciais | Texto do bioModal + badges CREA/UFSJ | ✓ |
| Manter texto exatamente como está | Copiar bioModal sem mudanças | |
| Eu escreverei o texto novo | Diego fornece conteúdo atualizado | |

**User's choice:** Manter o texto atual + adicionar badges de credenciais

---

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, adicionar LinkedIn | Incluir link do LinkedIn | |
| Apenas Instagram por enquanto | Manter só Instagram | ✓ |
| Você decide quais redes | Planner inclui redes adequadas | |

**User's choice:** Apenas Instagram por enquanto

---

## Claude's Discretion

- Layout e espaçamento da seção "Como funciona" (visual mobile-first)
- Textos descritivos dos 5 cards de serviços (baseados no `servicesData`)
- Posicionamento dos botões flutuantes (WhatsApp e Instagram)
- Título `<title>` e meta description para SEO
- Estrutura exata do degradê no hero (tonalidade exata do verde claro)

## Deferred Ideas

- LinkedIn — adicionar quando perfil ativo disponível
- Portfólio de obras — Out of Scope v1
- Depoimentos de clientes — avaliar após v1
- Blog/artigos técnicos — Out of Scope v1
- Páginas individuais por serviço — avaliação pós-v1
