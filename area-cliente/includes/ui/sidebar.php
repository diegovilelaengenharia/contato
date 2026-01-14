<!-- MOBILE OVERLAY (Click to close sidebar) -->
<div class="sidebar-overlay" onclick="toggleSidebar()" id="sidebarOverlay"></div>

<aside class="sidebar" id="mobileSidebar">
    
    <!-- ADMIN PROFILE HEADER -->
    <div class="sidebar__header p-4 position-relative overflow-hidden">
        <div style="position:absolute; top:0; left:0; width:100%; height:4px; background:var(--color-primary);"></div>

        <div class="flex-center" style="justify-content: flex-start; gap: 12px; margin-top: 5px;">
            <!-- Avatar -->
            <div style="width:48px; height:48px; border-radius:50%; background:#f0f2f5; overflow:hidden; border:2px solid #fff; box-shadow:var(--shadow-sm); flex-shrink:0;">
                <img src="../assets/foto-diego-new.jpg" onerror="this.src='https://ui-avatars.com/api/?name=Diego+Vilela&background=0D8ABC&color=fff'" style="width:100%; height:100%; object-fit:cover;">
            </div>
            
            <!-- Info -->
            <div style="flex:1; overflow:hidden;">
                <div style="font-weight:800; color:var(--color-text); font-size:0.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Diego Vilela</div>
                <div style="font-size:0.75rem; color:var(--color-text-subtle); font-weight:500;">Administrador</div>
            </div>

            <!-- Settings/Logout -->
            <div class="flex-center gap-10">
                <a href="admin_config.php" class="btn-icon" style="color:var(--color-text-subtle);" title="Configurações">
                    <span class="material-symbols-rounded">settings</span>
                </a>
                 <a href="logout.php" class="btn-icon" style="color:var(--color-danger);" title="Sair">
                    <span class="material-symbols-rounded">logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- SIDEBAR CONTENT -->
    <div class="sidebar__content">
        
        <!-- CLIENTE ATUAL SECTION -->
        <?php if($cliente_ativo): ?>
            <div class="mb-4">
                <h6 style="font-size:0.75rem; color:var(--color-text-subtle); margin: 0 0 10px 10px; text-transform:uppercase; letter-spacing:0.5px;">Cliente Atual</h6>
                
                <!-- Card Cliente Mini -->
                <div class="card" style="padding: 15px; margin-bottom: 20px; border-left: 4px solid var(--color-primary);">
                    <div class="flex-center" style="gap:10px; justify-content: flex-start; margin-bottom: 10px;">
                        <!-- Avatar Mini -->
                         <div style="width:36px; height:36px; min-width:36px; border-radius:50%; background:var(--color-primary-subtle); color:var(--color-primary); display:flex; align-items:center; justify-content:center; font-weight:bold;">
                            <?php if($avatar_url): ?>
                                <img src="<?= $avatar_url ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php else: ?>
                                <?= strtoupper(substr($cliente_ativo['nome'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div style="overflow:hidden;">
                            <div style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.9rem;"><?= htmlspecialchars($cliente_ativo['nome']) ?></div>
                            <div style="font-size:0.75rem; color:var(--color-text-subtle);"><?= $detalhes['contato_tel'] ?? '--' ?></div>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="flex-between" style="background:var(--color-bg); padding:5px; border-radius:8px;">
                        <a href="gerenciar_cliente.php?id=<?= $cliente_ativo['id'] ?>" class="btn-icon" style="flex:1;" title="Editar"><span class="material-symbols-rounded" style="font-size:1.1rem; color:var(--color-info);">edit</span></a>
                        <a href="relatorio_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="btn-icon" style="flex:1;" title="PDF"><span class="material-symbols-rounded" style="font-size:1.1rem; color:#6f42c1;">picture_as_pdf</span></a>
                        <a href="area_cliente.php" target="_blank" class="btn-icon" style="flex:1;" title="Ver"><span class="material-symbols-rounded" style="font-size:1.1rem; color:var(--color-success);">visibility</span></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- MENU GERAL -->
        <h6 style="font-size:0.75rem; color:var(--color-text-subtle); margin: 20px 0 10px 10px; text-transform:uppercase; letter-spacing:0.5px;">Navegação</h6>
        
        <a href="gestao_admin_99.php" class="nav-item <?= (!$cliente_ativo) ? 'active' : '' ?>">
            <span class="material-symbols-rounded icon">grid_view</span>
            Visão Geral
        </a>
        
        <a href="gerenciar_cliente.php" class="nav-item">
            <span class="material-symbols-rounded icon">person_add</span>
            Novo Cliente
        </a>
        
        <!-- Example Link for Mobile Test -->
        <!-- <a href="#" class="nav-item"><span class="material-symbols-rounded icon">calendar_month</span> Agenda</a> -->

    </div>

    <!-- FOOTER pinned to bottom -->
    <div class="sidebar__footer">
        <span style="display: block; font-size: 0.65rem; color: var(--color-text-subtle); text-transform: uppercase; font-weight: 700;">Engenheiro Responsável</span>
        <strong style="display: block; font-size: 0.85rem; color: var(--color-text);">Diego T. N. Vilela</strong>
        <span style="display: block; font-size: 0.75rem; color: var(--color-text-subtle);">CREA 235.474/D</span>
    </div>

</aside>

<script>
    // Simple toggle logic needed here or in main file
    function toggleSidebar() {
        const sb = document.getElementById('mobileSidebar');
        const ov = document.getElementById('sidebarOverlay');
        sb.classList.toggle('show');
        ov.classList.toggle('show');
    }
</script>
