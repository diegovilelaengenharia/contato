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

    <!-- 2. Clientes (Dropdown) -->
    <div class="top-nav-dropdown" style="position:relative;">
        <button class="top-nav-btn" onclick="toggleTopNavDropdown(this)" style="cursor:pointer;">
            <span class="material-symbols-rounded" style="color:#0d6efd;">groups</span>
            Clientes
            <span class="material-symbols-rounded" style="font-size:1rem; margin-left:5px; color:#aaa;">expand_more</span>
        </button>
        <div class="top-nav-dropdown-menu">
            <?php 
            // Ensure $clientes is available. If not, fetch it lightly.
            if(!isset($clientes)) {
                $clientes_nav = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $clientes_nav = $clientes;
            }
            
            if(empty($clientes_nav)): ?>
                <div style="padding:10px; color:#666; font-size:0.9rem;">Nenhum cliente</div>
            <?php else: ?>
                <?php foreach($clientes_nav as $cnav): ?>
                    <a href="gestao_admin_99.php?cliente_id=<?= $cnav['id'] ?>" class="dropdown-item">
                        <?= htmlspecialchars($cnav['nome']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. Acesso Rápido (Dropdown) -->
    <div class="top-nav-dropdown" style="position:relative;">
        <button class="top-nav-btn" onclick="toggleTopNavDropdown(this)" style="cursor:pointer;">
            <span class="material-symbols-rounded" style="color:#6610f2;">link</span>
            Acesso Rápido
            <span class="material-symbols-rounded" style="font-size:1rem; margin-left:5px; color:#aaa;">expand_more</span>
        </button>
        <div class="top-nav-dropdown-menu" style="width:220px;">
            <!-- New Internal Shortcuts -->
            <a href="gerenciar_cliente.php" class="dropdown-item">
                <span class="material-symbols-rounded">person_add</span>
                Novo Cliente
            </a>
            <div style="border-top:1px solid #eee; margin:5px 0;"></div>
            
            <!-- External Links -->
            <a href="https://oliveira.atende.net/atendenet?source=pwa" target="_blank" class="dropdown-item">
                <span class="material-symbols-rounded" style="font-size:1.1rem; vertical-align:middle; margin-right:5px; color:#009688;">support_agent</span>
                Atende Oliveira
            </a>
            <a href="https://ridigital.org.br/VisualizarMatricula/DefaultVM.aspx?from=menu" target="_blank" class="dropdown-item">
                <span class="material-symbols-rounded" style="font-size:1.1rem; vertical-align:middle; margin-right:5px; color:#6f42c1;">assignment_ind</span>
                Matrículas
            </a>
        </div>
    </div>



    <!-- SPACER to push Avatar to right -->
    <div style="flex-grow:1;"></div>

    <!-- 5. Perfil (Avatar) -->
    <div style="display:flex; align-items:center; gap:10px;">
        
        <!-- NEW: Avisos (Circular) -->
        <button onclick="document.getElementById('modalNotificacoes').showModal()" style="position:relative; width:40px; height:40px; border-radius:50%; border:1px solid #ddd; background:white; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:all 0.2s;" title="Avisos">
            <span class="material-symbols-rounded" style="color:#fd7e14; font-size:1.4rem;">notifications</span>
            <?php if(isset($kpi_pre_pendentes) && $kpi_pre_pendentes > 0): ?>
                <span style="position:absolute; top:-2px; right:-2px; background:#dc3545; color:white; font-size:0.7rem; font-weight:bold; height:18px; min-width:18px; padding:0 4px; border-radius:10px; display:flex; align-items:center; justify-content:center; border:2px solid white;"><?= $kpi_pre_pendentes ?></span>
            <?php endif; ?>
        </button>

        <div class="top-nav-dropdown" style="position:relative;">
        <button class="nav-avatar-btn" onclick="toggleTopNavDropdown(this)" style="cursor:pointer; padding:0; overflow:hidden;" title="Meu Perfil">
            <img src="assets/avatar_admin.jpg" alt="DV" style="width:100%; height:100%; object-fit:cover; display:block;">
        </button>
        <!-- Dropdown Menu (Right Aligned) -->
        <div class="top-nav-dropdown-menu" style="right:0; left:auto; width:200px;">
            <div style="padding:10px 15px; border-bottom:1px solid #eee; margin-bottom:5px;">
                <div style="font-weight:700; color:#333;">Diego Vilela</div>
                <div style="font-size:0.75rem; color:#888;">Administrador</div>
            </div>

            <a href="admin_config.php" class="dropdown-item">
                <span class="material-symbols-rounded" style="font-size:1.1rem; vertical-align:middle; margin-right:8px; color:#555;">settings</span>
                Configurações
            </a>
            <div style="border-top:1px solid #eee; margin:5px 0;"></div>
            <a href="logout.php" class="dropdown-item" style="color:#dc3545;">
                <span class="material-symbols-rounded" style="font-size:1.1rem; vertical-align:middle; margin-right:8px;">logout</span>
                Sair
            </a>
        </div>
    </div>

    </div>

</div>
<div style="height:60px;"></div> <!-- Spacer to push content down -->

<script>
function toggleTopNavDropdown(btn) {
    // Prevent event bubbling to window
    event.stopPropagation();
    
    const dropdown = btn.closest('.top-nav-dropdown');
    // Toggle current
    dropdown.classList.toggle('active');
}

// Close when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('.top-nav-dropdown')) {
        document.querySelectorAll('.top-nav-dropdown.active').forEach(d => {
            d.classList.remove('active');
        });
    }
});
</script>
