<div class="admin-mobile-header" style="display:none; background:#146c43; color:white; padding:15px 20px; text-align:center; border-bottom:4px solid #0f5132;">
    <img src="../assets/logo.png" alt="Vilela Engenharia" style="height:45px; margin-bottom:10px; display:block; margin-left:auto; margin-right:auto;">
    <h3 style="margin:0 0 5px 0; font-size:1.1rem; text-transform:uppercase; letter-spacing:1px; font-weight:800;">Gestão Administrativa</h3>
    <div style="font-size:0.85rem; opacity:0.9; line-height:1.4;">
        Eng. Diego Vilela &nbsp;|&nbsp; CREA-MG: 235474/D<br>
        vilela.eng.mg@gmail.com &nbsp;|&nbsp; (35) 98452-9577
    </div>
</div>

<!-- Top Fixed Navigation Bar -->
<div class="top-nav-container">
    
    <!-- 1. Visão Geral -->
    <a href="gestao_admin_99.php" class="top-nav-btn" style="border-color:var(--color-primary);">
        <span class="material-symbols-rounded" style="color:var(--color-primary);">dashboard</span>
        Visão Geral
    </a>

    <!-- 2. Atende Oliveira -->
    <a href="https://oliveira.atende.net/atendenet?source=pwa" target="_blank" class="top-nav-btn">
        <span class="material-symbols-rounded">support_agent</span>
        Atende Oliveira
    </a>

    <!-- 3. Matrícula do Imóvel -->
    <a href="https://ridigital.org.br/VisualizarMatricula/DefaultVM.aspx?from=menu" target="_blank" class="top-nav-btn">
        <span class="material-symbols-rounded">assignment_ind</span>
        Matrículas
    </a>

    <!-- 4. Avisos -->
    <button onclick="document.getElementById('modalNotificacoes').showModal()" class="top-nav-btn" style="cursor:pointer;">
        <span class="material-symbols-rounded">notifications</span>
        Avisos
        <?php if(isset($kpi_pre_pendentes) && $kpi_pre_pendentes > 0): ?>
            <span class="fab-badge-top"><?= $kpi_pre_pendentes ?></span>
        <?php endif; ?>
    </button>

</div>
<div style="height:60px;"></div> <!-- Spacer to push content down -->
