<?php
// Ensure metrics are available (or default to 0)
$kpi_pre_pendentes = $kpi_pre_pendentes ?? 0;
$count_ani = count($aniversariantes ?? []);
$count_par = count($parados ?? []);
?>

<!-- Floating Action Menu Container -->
<div class="fab-container" id="fabContainer">
    
    <!-- Collapsible Menu Items -->
    <div class="fab-menu">
        
        <!-- 4. Parados -->
        <a href="#" onclick="document.getElementById('modalParados').showModal(); return false;" class="fab-item">
            <span class="fab-label">Processos Parados</span>
            <button class="fab-btn" style="color:#dc3545;">
                <span class="material-symbols-rounded">timer_off</span>
                <?php if($count_par > 0): ?>
                    <span class="fab-badge"><?= $count_par ?></span>
                <?php endif; ?>
            </button>
        </a>

        <!-- 3. Aniversários -->
        <a href="#" onclick="document.getElementById('modalAniversariantes').showModal(); return false;" class="fab-item">
            <span class="fab-label">Aniversariantes</span>
            <button class="fab-btn" style="color:#fd7e14;">
                <span class="material-symbols-rounded">cake</span>
                <?php if($count_ani > 0): ?>
                    <span class="fab-badge"><?= $count_ani ?></span>
                <?php endif; ?>
            </button>
        </a>

        <!-- 2. Avisos -->
        <a href="#" onclick="document.getElementById('modalNotificacoes').showModal(); return false;" class="fab-item">
            <span class="fab-label">Avisos</span>
            <button class="fab-btn" style="color:#ffc107;">
                <span class="material-symbols-rounded">notifications</span>
                <?php if($kpi_pre_pendentes > 0): ?>
                    <span class="fab-badge"><?= $kpi_pre_pendentes ?></span>
                <?php endif; ?>
            </button>
        </a>
        
        <!-- 1. Dashboard (Home) -->
        <a href="gestao_admin_99.php" class="fab-item">
            <span class="fab-label">Visão Geral</span>
            <button class="fab-btn" style="color:var(--color-primary);">
                <span class="material-symbols-rounded">dashboard</span>
            </button>
        </a>
    </div>

    <!-- Main Toggle Button -->
    <button class="fab-main" onclick="toggleFab()">
        <span class="material-symbols-rounded">add</span>
    </button>
</div>

<!-- Mobile Branding Footer (Optional: Fixed at bottom left or removed?) 
     User requested discreet buttons. Let's keep it clean and remove branding from screen, 
     maybe just keep it in the header if it exists or footer if we add one.
     For now, removed from sidebar as requested.
-->

<script>
    function toggleFab() {
        document.querySelector('.fab-container').classList.toggle('active');
        const icon = document.querySelector('.fab-main .material-symbols-rounded');
        // Icon rotation handled by CSS
    }
    
    // Auto-close on click outside
    document.addEventListener('click', function(event) {
        const fab = document.querySelector('.fab-container');
        const isClickInside = fab.contains(event.target);
        if (!isClickInside && fab.classList.contains('active')) {
            fab.classList.remove('active');
        }
    });
</script>
