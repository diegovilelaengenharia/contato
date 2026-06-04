---
phase: 1
plan: 01
milestone: v2.1
title: "Crescimento da Landing Page (Cartão de Visita)"
status: ready
created: 2026-06-04
requirements: [LAND-09, LAND-10, LAND-11, LAND-12, LAND-13]
---

# PLAN 01 — Fase 1: Crescimento da Landing Page (v2.1)

**Milestone:** v2.1 — Landing de Crescimento & Polimento
**Base:** [01-CONTEXT.md](01-CONTEXT.md)
**Status:** Pronto para Execução

---

## Onda 1 — Diferenciais e Confiabilidade Qualitativa (LAND-13)

### Tarefa 1.1 — Implementar Seção de Diferenciais
* **Onde:** `index.html`, logo após a seção "Como Funciona".
* **Visual:** Grid moderno de 4 colunas em desktop e flex-direction column em mobile.
* **Conteúdo:** 
  1. *Rigor e Formação Acadêmica* (Ícone: `school`) - Graduação UFSJ.
  2. *Normas e Leis Atualizadas* (Ícone: `gavel` ou `verified`) - Domínio completo de NBRs e códigos de obras.
  3. *Transparência Digital* (Ícone: `phone_iphone` ou `devices`) - Acompanhamento no Portal do Cliente.
  4. *Atendimento Dedicado* (Ícone: `handshake`) - Exclusividade e foco em soluções rápidas.

---

## Onda 2 — Portfólio de Cases e Depoimentos (LAND-09 + LAND-10)

### Tarefa 2.1 — Seção de Portfólio (Cases de Obras)
* **Onde:** `index.html` na tag `<main>`.
* **Visual:** Cartões modernos com hover dinâmico (zoom e sombras elegantes). Imagens ilustrativas otimizadas de projetos de engenharia.
* **Cases:**
  1. *Regularização de Residências* (Aprovações de habite-se e cartórios).
  2. *Projetos Técnicos e Aprovações* (Projeto arquitetônico e estrutural com ART).
  3. *Laudos e Vistorias Técnicas* (Vistorias de reforma, vizinhança e laudos cautelares).

### Tarefa 2.2 — Seção de Depoimentos Modelo
* **Onde:** `index.html` antes do footer.
* **Visual:** Slider de depoimentos ou grid de 3 colunas com visual premium (efeito glassmorphism leve, aspas e 5 estrelas em cor âmbar).
* **Conteúdo:** 3 depoimentos modelo enfatizando agilidade, transparência nas atualizações online do portal e profissionalismo técnico.

---

## Onda 3 — Acordeão de FAQ Interativo (LAND-11)

### Tarefa 3.1 — Seção de FAQ (Dúvidas Frequentes)
* **Onde:** `index.html` antes da seção "Sobre".
* **Visual:** Estrutura de acordeão expansível de forma suave.
* **Perguntas:**
  1. *Por que é necessário regularizar um imóvel?*
  2. *O que acontece se eu mantiver meu imóvel irregular?*
  3. *O que é o Habite-se e quando preciso dele?*
  4. *Como funciona o Portal do Cliente para acompanhar o meu processo?*

---

## Onda 4 — SEO Técnico e Schema.org (LAND-12)

### Tarefa 4.1 — Expandir Dados Estruturados e Metas
* **Ação 1:** Adicionar metadados Open Graph (Facebook/WhatsApp) e Twitter Cards na tag `<head>` para compartilhamento visual.
* **Ação 2:** Atualizar o script JSON-LD do Schema.org para o tipo `EngineeringService` detalhando os serviços, área de atuação (Oliveira/MG) e dados de contato.
* **Ação 3:** Criar arquivo `sitemap.xml` na raiz do projeto com as URLs principais e data de modificação.

### Tarefa 4.2 — Otimização Estética e Tema (Dark Mode)
* **Ação:** Escrever o CSS das seções em `style.css`.
* **Uso de Tokens:** Referenciar as variáveis de `area-cliente/css/tokens.css` (como `--color-primary`, `--border-radius-lg`, etc.).
* **Dark Mode:** Garantir que todas as novas seções tenham correspondentes contrastes elegantes no tema escuro.

---

## Verificação e Qualidade
* **Validação Semântica:** HTML estruturado com tags semânticas apropriadas (W3C friendly).
* **Responsividade:** Layout testado e 100% responsivo em dispositivos móveis.
* **Performance:** Imagens comprimidas e carregamento lazy para pontuação alta no Lighthouse.
