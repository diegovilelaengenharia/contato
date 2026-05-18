<?php
/**
 * Componente: Botões Flutuantes
 * Links rápidos para ferramentas externas
 */
?>
<!-- FLOATING ACTION BUTTONS (External Links - Circular & Discrete) -->
<div style="position:fixed; bottom:25px; right:25px; display:flex; flex-direction:column; gap:12px; z-index:9999; align-items:center;">
    
    <!-- Botão Matrícula (Cinza Escuro/Discreto) -->
    <a href="https://ridigital.org.br/VisualizarMatricula/DefaultVM.aspx?from=menu" target="_blank" 
       title="Acessar Matrícula"
       style="display:flex; align-items:center; justify-content:center; width:48px; height:48px; background:#495057; color:white; border-radius:50%; text-decoration:none; box-shadow:0 4px 10px rgba(0,0,0,0.2); transition:all 0.2s;"
       onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 15px rgba(0,0,0,0.25)';" 
       onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 10px rgba(0,0,0,0.2)';">
        <span class="material-symbols-rounded" style="font-size:1.4rem;">description</span>
    </a>

    <!-- Botão IPM Prefeitura (Azul/Discreto) -->
    <a href="https://oliveira.atende.net/atendenet?source=pwa" target="_blank" 
       title="Acessar IPM Prefeitura"
       style="display:flex; align-items:center; justify-content:center; width:48px; height:48px; background:#0d6efd; color:white; border-radius:50%; text-decoration:none; box-shadow:0 4px 10px rgba(13, 110, 253, 0.3); transition:all 0.2s;"
       onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 15px rgba(13, 110, 253, 0.4)';" 
       onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 10px rgba(13, 110, 253, 0.3)';">
        <span class="material-symbols-rounded" style="font-size:1.4rem;">account_balance</span>
    </a>

</div>
