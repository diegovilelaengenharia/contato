# CONTEXT 01 — Fase 1: Crescimento da Landing Page (v2.1)

## 📌 Escopo e Objetivos
O objetivo desta fase é expandir a Landing Page principal (`index.html`) da Vilela Engenharia para torná-la um canal de captação de leads mais robusto e profissional para o engenheiro autônomo Diego Vilela. 

A abordagem de marketing e confiabilidade será adaptada especificamente para valorizar um profissional recém-formado e atuante, focando em rigor técnico acadêmico, processos ágeis modernos e transparência.

---

## 🔒 Decisões de Conteúdo e UX

### 1. Sinais de Confiança e Diferenciais (LAND-13)
Em vez de grandes volumes acumulados de obras (por ser profissional em início de carreira), a confiabilidade será baseada em diferenciais qualitativos em destaque:
* **Rigor e Formação Acadêmica:** Destaque para a graduação pela **UFSJ (Universidade Federal de São João Del Rei)**.
* **Normas Atualizadas:** Conhecimento fresco e rigoroso de todas as normas técnicas da ABNT e códigos de obras municipais vigentes.
* **Transparência e Inovação Digital:** Evidenciar o **Portal do Cliente** como canal de acompanhamento online de processos para o cliente, promovendo tranquilidade e inovação.
* **Atendimento Dedicado e Próximo:** Foco na exclusividade e personalização do suporte a cada caso.

### 2. Cases de Sucesso / Portfólio (LAND-09)
Adicionar uma seção de portfólio visual com 3 cases ilustrativos dos serviços prestados pelo escritório:
1. **Regularização de Residências:** Habite-se, alvará de regularização e averbação em cartório.
2. **Projetos e Aprovações:** Elaboração de projetos arquitetônicos e complementares com ART.
3. **Laudos e Vistorias Técnicas:** Laudos de vistoria de vizinhança, reforma e patologias.

### 3. Depoimentos de Clientes (LAND-10)
Adicionar uma seção com 3 depoimentos modelo focados na experiência de atendimento ágil, transparência nos prazos e segurança jurídica proporcionada pela emissão de laudos e ART. Estes depoimentos servirão como modelo para posterior substituição por avaliações reais de clientes do Diego.

### 4. Seção de FAQ (Perguntas Frequentes) (LAND-11)
Implementar uma seção de acordeão (FAQ) contendo 4 a 5 dúvidas principais que clientes residenciais e comerciais costumam ter antes de contratar a regularização (Ex: "Por que regularizar meu imóvel?", "Quais documentos são necessários?", "O que é Habite-se?", "Qual o papel do engenheiro e do CREA?").

### 5. SEO Técnico & Schema.org (LAND-12)
* Configuração correta de tags de metadados de SEO para mídias sociais e buscas orgânicas.
* Expansão dos dados estruturados no formato JSON-LD do Schema.org para o tipo `EngineeringService` e `LocalBusiness`.
* Geração do arquivo `sitemap.xml` estruturado.

### 6. Blog (LAND-14)
* **Status:** FORA DE ESCOPO. O blog foi postergado para manter a página extremamente performática e focada na conversão direta.

---

## 🎨 Layout e Visual System
* A nova seção de portfólio e depoimentos deve utilizar o **Design System Unificado** criado na v2.0 (utilizando as variáveis declaradas em `css/tokens.css` e as cores institucionais do verde Vilela).
* O design deve ser mobile-first, com carregamento otimizado (imagens em WebP e lazy loading) e transições suaves de scroll (`scroll-reveal` existente).
