# Phase 2: Landing Page Mobile-First - Context

**Gathered:** 2026-05-16
**Status:** Ready for planning

<domain>
## Phase Boundary

Transformar a landing page atual (linktree compacto) em uma página de apresentação completa, mobile-first, com scroll one-page. Estrutura: hero → como funciona → serviços → sobre → contato. Reaproveitar o máximo do index.html e style.css existentes. Nenhuma mudança em backend, area-cliente ou painel admin nesta fase.

</domain>

<decisions>
## Implementation Decisions

### Abordagem da Reescrita

- **D-01:** Estrutura one-page com scroll (hero → como funciona → serviços → sobre → contato). Não um linktree compacto.
- **D-02:** Reaproveitar o máximo possível do index.html e style.css existentes — não reescrever do zero. Adicionar seções ao redor da estrutura atual.
- **D-03:** Remover as meta tags `noindex, nofollow` e `no-cache`. A página deve ser indexável pelo Google (SEO habilitado).
- **D-04:** O modal de orçamento existente (checklist de serviços → WhatsApp) deve ser preservado — já implementa LAND-08 e funciona bem.

### Hero

- **D-05:** Manter a logo da Vilela Engenharia como elemento visual principal do hero (não substituir pela foto de Diego).
- **D-06:** Substituir a tagline marquee animada por tagline estática e mais proeminente.
- **D-07:** Texto da tagline: "Regularização e aprovação de obras, do projeto à averbação".
- **D-08:** Adicionar fundo em degradê verde suave no hero (verde claro → branco), mantendo o design system existente (`--color-primary: #197e63`).
- **D-09:** Manter os botões existentes no hero: WhatsApp (número) + Área do Cliente.

### Seção "Como Funciona" (nova)

- **D-10:** Adicionar seção "Como funciona" com 3 passos simples: 1. Me chama no WhatsApp → 2. Analiso seu caso → 3. Cuido de tudo. Posicionada após o hero, antes dos serviços.
- **D-11:** Planner define o visual e os textos exatos dos 3 passos.

### Seção Serviços

- **D-12:** Manter os cards interativos existentes (clique → modal de seleção → WhatsApp). Não substituir por seção descritiva estática.
- **D-13:** Adicionar 2-3 linhas de descrição por categoria de serviço em cada card. Planner cria os textos baseado no `servicesData` do código.
- **D-14:** As 5 categorias existentes são mantidas: Prefeitura, Receita Federal, Cartório de Imóveis, Projetos de Engenharia, Consultoria e Laudos.

### Seção Sobre (nova seção inline)

- **D-15:** Criar seção "Sobre" dedicada e inline (visível no scroll), substituindo o bioModal do footer.
- **D-16:** Layout: foto de Diego (`assets/foto-diego-new.jpg`) + texto lado a lado. Em mobile: foto circular no topo, texto abaixo. Em desktop: foto à esquerda, texto à direita.
- **D-17:** Texto base: usar o conteúdo existente do `bioModal` (UFSJ, Prefeitura de Oliveira, rigor técnico, compromisso com eficiência).
- **D-18:** Adicionar badges visuais de credenciais: CREA 235.474/D · Eng. Civil UFSJ · Analista de Projetos (ex-Prefeitura de Oliveira).

### Redes Sociais

- **D-19:** Manter apenas Instagram (`@diegovilela.eng`) por enquanto. Não adicionar LinkedIn nesta fase.
- **D-20:** Os botões flutuantes de WhatsApp e Instagram existentes podem ser mantidos ou reorganizados — planner decide o melhor posicionamento.

### Claude's Discretion

- Layout exato e espaçamento da seção "Como funciona" — planner decide o visual mais adequado para mobile-first.
- Posicionamento dos botões flutuantes (WhatsApp e Instagram) — manter como está ou integrar ao footer.
- Textos descritivos dos cards de serviços — planner cria baseado no `servicesData` existente.
- Título do `<title>` e meta description para SEO — planner cria texto adequado para engenheiro civil / regularização de imóveis em Minas Gerais.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requisitos da Fase
- `.planning/ROADMAP.md` — Meta e critérios de sucesso da Phase 2 (Landing Page Mobile-First), LAND-01 a LAND-08
- `.planning/REQUIREMENTS.md` — LAND-01 a LAND-08 com spec completa

### Arquivos a Modificar
- `index.html` — Arquivo principal a ser expandido. Estrutura one-page com scroll. Seções a adicionar: "Como funciona", "Sobre". Manter: header/hero, cards de serviços, modal de orçamento, footer.
- `style.css` — Adicionar estilos para as novas seções. Manter todas as variáveis CSS existentes (`--color-primary`, `--color-bg`, etc.) e o design system.

### Assets Disponíveis
- `assets/foto-diego-new.jpg` — Foto de Diego para a seção Sobre
- `assets/logo.png` — Logo da Vilela Engenharia (mantida no hero)
- `assets/logo-padded.png` — Versão com padding da logo

### Design System Estabelecido
- `style.css` (`:root`) — Variáveis de cor, tipografia, sombras. Cor primária `#197e63`, fonte Outfit, accent âmbar `#e8960f`.

### Contexto do Projeto
- `.planning/PROJECT.md` — Contexto geral, constraints de stack (PHP, Hostinger, sem bundlers), design system existente

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **Modal de orçamento** (`index.html` linhas 382-408): Sistema completo de modal com checklist de serviços + botão WhatsApp. Preservar sem modificações — já implementa LAND-08.
- **`servicesData` object** (`index.html` linha 443-476): Todas as categorias e listas de serviços já mapeadas. Usar como fonte para os textos descritivos dos cards.
- **`foto-diego-new.jpg`**: Foto profissional de Diego já disponível em `assets/`. Usar na seção Sobre.
- **Botões `.v-btn-base`**: Padrão de botão existente (border-radius 999px, sólido e ghost). Reutilizar nas novas seções.
- **Scroll reveal animation**: Sistema de IntersectionObserver já implementado (`.reveal` + `.reveal.active`). Pode ser estendido para as novas seções.

### Established Patterns
- **Design system via CSS vars**: Todo styling usa variáveis de `:root`. Novas seções DEVEM usar as mesmas variáveis, não valores hardcoded.
- **Fonte Outfit**: Já carregada via Google Fonts. Usar em todos os novos elementos.
- **Schema.org** (`index.html` linhas 166-201): SEO estruturado já configurado para EngineeringService. Atualizar com dados da seção Sobre se necessário.
- **Floating buttons**: WhatsApp e Instagram como botões flutuantes fixed no canto. Padrão estabelecido.

### Integration Points
- A seção Sobre inline substitui funcionalmente o `bioModal` — o botão "Sobre Mim" no footer pode virar um link âncora (`#sobre`) em vez de abrir modal.
- O `<footer class="footer-premium">` existente deve ser mantido ou adaptado para incluir as redes sociais de forma mais proeminente.
- O tagline marquee (`.tagline-container`) deve ser removido e substituído pela tagline estática.

</code_context>

<specifics>
## Specific Ideas

- Degradê do hero: verde muito claro (tipo `#e8f5f0` ou similar) → branco. Deve ser sutil, não chamar mais atenção que o conteúdo.
- A seção "Como funciona" deve ser simples e visual — ícones + números + texto curto. Pensar em mobile: elementos empilhados verticalmente.
- Badges da seção Sobre: pequenas pills/chips com fundo verde claro e texto verde escuro, inline abaixo do nome de Diego.
- A tagline estática substitui o marquee animado — deve ter fonte maior que o texto corrido mas menor que o nome de Diego. Cor: `--color-text-subtle` ou `--color-primary`.

</specifics>

<deferred>
## Deferred Ideas

- LinkedIn e outras redes sociais — adicionar quando Diego tiver perfil ativo para incluir.
- Portfólio de obras / galeria de projetos — Out of Scope v1 (PROJECT.md).
- Blog/artigos técnicos — Out of Scope v1 (PROJECT.md).
- Seção de depoimentos de clientes — avaliar após v1 se Diego tiver depoimentos disponíveis.
- Página de serviço individual (deep-dive por categoria) — avaliação pós-v1.

</deferred>

---

*Phase: 2-Landing Page Mobile-First*
*Context gathered: 2026-05-16*
