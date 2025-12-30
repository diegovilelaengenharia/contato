<?php
// Knowledge Base View (Oliveira/MG Spec)
?>
<div class="view-header-simple">
    <h2>Central de Conhecimento</h2>
    <p>Glossário Técnico e Legislação de Oliveira/MG.</p>
</div>

<!-- ASSISTANT TIP -->
<div class="assistant-tip fade-in-up">
    <div class="at-icon">📚</div>
    <div class="at-content">
        <strong>Educação Urbanística</strong>
        <p>Entenda os termos técnicos do seu processo e consulte a base legal que fundamenta nossa atuação técnica na regularização do seu imóvel.</p>
    </div>
</div>

<div class="fade-in-up">
    
    <!-- GLOSSARY SECTION -->
    <h3 style="color:var(--color-primary); margin-bottom:15px; display:flex; align-items:center; gap:10px;">
        <span class="material-symbols-rounded">menu_book</span> Glossário Técnico
    </h3>

    <div class="knowledge-grid" style="display:grid; gap:15px; margin-bottom:40px;">
        
        <div class="k-card">
            <h4>As Built ("Como Construído")</h4>
            <p>Levantamento técnico arquitetônico que reflete a situação real e atual da edificação. É obrigatório quando a obra executada diverge do projeto original aprovado.</p>
        </div>

        <div class="k-card">
            <h4>Decadência de Débitos (INSS)</h4>
            <p>Procedimento legal que reconhece que a construção ocorreu há mais de 5 anos (prazo decadencial), isentando o proprietário do pagamento de contribuições previdenciárias sobre a mão de obra aferida.</p>
        </div>

        <div class="k-card">
            <h4>Habite-se (Certidão de Baixa)</h4>
            <p>Documento administrativo expedido pela Prefeitura atestando que o imóvel foi construído conforme as posturas municipais e possui condições de segurança e habitabilidade.</p>
        </div>

        <div class="k-card">
            <h4>Averbação no SRI</h4>
            <p>Ato final do processo. Consiste em registrar na matrícula do imóvel (Cartório) a existência da construção ou suas alterações, garantindo a plena propriedade e valorização de mercado.</p>
        </div>

        <div class="k-card">
            <h4>Quebra de Parâmetros</h4>
            <p>Infração urbanística ocorrida quando a edificação desrespeita índices como Taxa de Ocupação (T.O) ou Afastamentos. Sua regularização pode exigir pagamento de contrapartida financeira (Outorga).</p>
        </div>

    </div>

    <!-- LEGISLATION SECTION -->
    <h3 style="color:var(--color-primary); margin-bottom:15px; display:flex; align-items:center; gap:10px;">
        <span class="material-symbols-rounded">gavel</span> Base Legal (Oliveira/MG)
    </h3>

    <div class="legislation-list" style="background:var(--bg-card); border-radius:12px; padding:20px; box-shadow:var(--shadow-soft);">
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="leg-item">
                <strong>Lei Municipal n.º 1.544/1986 (Código de Obras)</strong>
                <p>Art. 19 e 22: Define as responsabilidades técnicas e administrativas sobre a execução de obras no município.</p>
            </li>
            <li class="leg-item">
                <strong>Lei Municipal n.º 267/2019 (Plano Diretor)</strong>
                <p>Estabelece o zoneamento, uso e ocupação do solo, definindo índices como Taxa de Ocupação e Coeficiente de Aproveitamento.</p>
            </li>
            <li class="leg-item">
                <strong>Decreto Municipal n.º 4.149/2019</strong>
                <p>Regulamenta os critérios específicos e fluxo documental para aprovação de projetos arquitetônicos e regularizações.</p>
            </li>
        </ul>
    </div>

</div>

<style>
.k-card {
    background: var(--bg-card);
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid var(--color-primary);
    box-shadow: var(--shadow-card);
}
.k-card h4 { margin:0 0 8px 0; color:var(--text-main); font-size:1.1rem; }
.k-card p { margin:0; font-size:0.95rem; color:var(--text-muted); line-height:1.5; }

.leg-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}
.leg-item:last-child { margin-bottom:0; padding-bottom:0; border-bottom:none; }
.leg-item strong { display:block; color:var(--color-primary); font-size:1.05rem; margin-bottom:5px; }
.leg-item p { margin:0; font-size:0.9rem; color:var(--text-muted); }
</style>
