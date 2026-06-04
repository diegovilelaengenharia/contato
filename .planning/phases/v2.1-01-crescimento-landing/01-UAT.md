---
phase: 1
milestone: v2.1
title: "Crescimento da Landing Page (Cartão de Visita)"
status: passed
verified_at: 2026-06-04
---

# UAT 01 — Fase 1: Crescimento da Landing Page (v2.1)

Este documento registra a validação e verificação de aceitação das novas seções da Landing Page.

## 🧪 Casos de Teste Executados

### CT-01: Exibição da Seção de Diferenciais (Confiabilidade)
* **Procedimento:** Acessar a página inicial do site e rolar até logo abaixo da seção "Como Funciona".
* **Resultado Esperado:** 4 blocos de diferenciais qualitativos estruturados (Rigor Técnico, Normas Atualizadas, Transparência Digital, Suporte Personalizado) com ícones da Material Symbols centralizados e textos legíveis.
* **Resultado Obtido:** Passou.

### CT-02: Visualização de Cases de Sucesso (Portfólio)
* **Procedimento:** Rolar a Landing Page até a seção "Cases de Sucesso".
* **Resultado Esperado:** Grid responsivo contendo 3 cards de obras de engenharia com imagens correspondentes da pasta `assets/`. Efeito hover deve aplicar leve zoom na imagem de forma fluida.
* **Resultado Obtido:** Passou.

### CT-03: Exibição de Depoimentos
* **Procedimento:** Rolar a Landing Page até a seção "Depoimentos".
* **Resultado Esperado:** Apresentação de 3 depoimentos fictícios de modelo estruturados de forma minimalista com aspas, classificação de 5 estrelas em cor dourada/âmbar.
* **Resultado Obtido:** Passou.

### CT-04: Interatividade do Acordeão de FAQ
* **Procedimento:** Clicar sobre as perguntas da seção "Perguntas Frequentes".
* **Resultado Esperado:** A resposta correspondente deve expandir/colapsar de forma nativa e o chevron (seta) à direita da pergunta rotaciona 180 graus. Apenas a pergunta clicada é aberta.
* **Resultado Obtido:** Passou.

### CT-05: Dark Mode e Responsividade
* **Procedimento:** Testar em telas de smartphone/tablet e alternar as configurações do sistema operacional para o modo escuro.
* **Resultado Esperado:**
  - O layout do grid se adapta de forma vertical e fluida sem quebras de margem.
  - No modo escuro, o fundo dos cards das seções Diferenciais, Portfólio, FAQ e Depoimentos assume uma tonalidade cinza escura suave (`#18221f`), garantindo contraste excelente e leitura confortável.
* **Resultado Obtido:** Passou.

### CT-06: SEO e Sitemap
* **Procedimento:** Validar a presença das metatags Open Graph no head e a existência do arquivo `sitemap.xml` na raiz do projeto.
* **Resultado Esperado:**
  - Presença de tags `og:image`, `og:title` e `og:description` apontando para o site.
  - Acesso ao sitemap.xml retorna o XML legível estruturado mapeando a raiz e a área do cliente.
* **Resultado Obtido:** Passou.
