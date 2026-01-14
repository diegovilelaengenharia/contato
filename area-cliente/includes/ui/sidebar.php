<aside class="sidebar admin-nav-sidebar">
    
    <!-- ADMIN PROFILE (Sidebar Header) -->
    <div style="padding: 15px 15px 15px 15px; border-bottom: 1px solid #f0f0f0; margin-bottom: 15px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <!-- Avatar -->
            <div style="width:38px; height:38px; border-radius:50%; background:#f0f2f5; overflow:hidden; border:2px solid #fff; box-shadow:0 2px 4px rgba(0,0,0,0.1); flex-shrink:0;">
                <img src="../assets/foto-diego-new.jpg" onerror="this.src='https://ui-avatars.com/api/?name=Diego+Vilela&background=0D8ABC&color=fff'" style="width:100%; height:100%; object-fit:cover;">
            </div>
            
            <!-- Info -->
            <div style="flex:1; overflow:hidden; text-align:left;">
                <div style="font-weight:700; color:#333; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Diego Vilela</div>
                <div style="font-size:0.7rem; color:#888;">Administrador</div>
            </div>

            <!-- Settings (Icon Only) -->
            <a href="admin_config.php" title="Configurações" style="color:#555; display:flex; padding:5px; border-radius:50%; transition:0.2s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='transparent'">
                <span class="material-symbols-rounded" style="font-size:1.1rem;">settings</span>
            </a>
            <!-- Logout (Icon Only) -->
             <a href="logout.php" title="Sair" style="color:#dc3545; display:flex; padding:5px; border-radius:50%; transition:0.2s;" onmouseover="this.style.background='#fff0f0'" onmouseout="this.style.background='transparent'">
                <span class="material-symbols-rounded" style="font-size:1.1rem;">logout</span>
            </a>
        </div>
    </div>

    <!-- BRANDING HEADER (REMOVED) -->
    
    <!-- SEÇÃO CLIENTE SELECIONADO (Topo) -->
    <?php if($cliente_ativo): ?>
        <div class="nav-section">
            <h6 class="nav-header" style="color:#198754; font-size:0.75rem; letter-spacing:1px; margin-bottom:12px;">CLIENTE ATUAL</h6>
            
            <!-- CLIENTE HEADER & AÇÕES -->
            <div class="nav-client-card" style="background: white; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border:1px solid #eee; overflow:hidden;">
                
                <!-- Info Principal -->
                <div style="padding: 15px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #f8f9fa;">
                    <!-- Avatar -->
                    <div style="width:42px; height:42px; min-width:42px; position:relative;">
                        <?php if($avatar_url): ?>
                            <img src="<?= $avatar_url ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%; border:2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background:#198754; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; font-weight:700; border:2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <?= strtoupper(substr($cliente_ativo['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Texto -->
                    <div style="flex:1; overflow:hidden;">
                        <h3 style="font-size:0.9rem; margin:0 0 2px 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#333; font-weight:700;" title="<?= htmlspecialchars($cliente_ativo['nome']) ?>">
                            <?= htmlspecialchars($cliente_ativo['nome']) ?>
                        </h3>
                        <div style="font-size:0.75rem; color:#777;">
                            📱 <?= $detalhes['contato_tel'] ?? '--' ?>
                        </div>
                    </div>
                </div>

                <!-- Barra de Ações -->
                <div style="display:flex; background:#fff;">
                    <a href="gerenciar_cliente.php?id=<?= $cliente_ativo['id'] ?>" class="client-action-btn" style="color:#0d6efd;" title="Editar Cadastro">
                        <span class="material-symbols-rounded">edit</span>
                    </a>
                    <a href="relatorio_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="client-action-btn" style="color:#6f42c1;" title="Resumo PDF">
                        <span class="material-symbols-rounded">picture_as_pdf</span>
                    </a>
                    <a href="area_cliente.php" target="_blank" class="client-action-btn" style="color:#198754;" title="Ver como Cliente">
                        <span class="material-symbols-rounded">visibility</span>
                    </a>
                    <!-- BOTAO EXCLUIR VERMELHO -->
                    <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" class="client-action-btn btn-danger-hover" onclick="return confirm('Deseja excluir este cliente?')" style="color:#dc3545;" title="Excluir Cliente">
                        <span class="material-symbols-rounded">delete</span>
                    </a>
                </div>
            </div>

            <style>
                .client-action-btn { flex: 1; display: flex; align-items: center; justify-content: center; padding: 10px 0; color: #6c757d; text-decoration: none; transition: all 0.2s; border-right: 1px solid #f9f9f9; }
                .client-action-btn:last-child { border-right: none; }
                .client-action-btn:hover { background: #f8f9fa; transform: translateY(-1px); }
                .client-action-btn .material-symbols-rounded { font-size: 1.1rem; }
                .btn-danger-hover:hover { background: #fee2e2 !important; color: #dc3545 !important; }
            </style>
        </div>
        
    <?php endif; ?>

    <!-- SEÇÃO GERAL -->
    <div class="nav-section" style="flex:1;">
        <h6 class="nav-header">GERAL</h6>
        
        <a href="gestao_admin_99.php" class="nav-item <?= (!$cliente_ativo) ? 'active' : '' ?>">
            <span class="material-symbols-rounded">grid_view</span>
            Visão Geral
        </a>
        
        <a href="gerenciar_cliente.php" class="nav-item">
            <span class="material-symbols-rounded">person_add</span>
            Novo Cliente
        </a>
    </div>

    <!-- TECHNICAL RESPONSIBLE FOOTER (Pinned to Bottom) -->
    <div style="margin-top:auto; padding:20px; border-top:1px solid #f0f0f0; background:#fff; border-radius: 0 0 16px 16px; text-align:center;">
        <div style="margin-bottom:8px; font-weight:800; color:#198754; text-transform:uppercase; letter-spacing:1px; font-size:0.9rem;">
            VILELA
        </div>
        <span style="display: block; font-size: 0.65rem; color: #adb5bd; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom:2px;">Engenheiro Responsável</span>
        <strong style="display: block; font-size: 0.85rem; color: #495057; line-height: 1.2;">Diego T. N. Vilela</strong>
        <span style="display: block; font-size: 0.75rem; color: #888;">CREA 235.474/D</span>
    </div>

</aside>

<!-- STYLE FOR SIDEBAR (Inline for component encapsulation) -->
<style>
    .admin-nav-sidebar {
        width: 270px;
        min-width: 270px;
        background: white;
        border-radius: 16px;
        /* Make it flex container to pin footer */
        display: flex;
        flex-direction: column;
        
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        border: 1px solid #eaeaea;
        align-self: flex-start;
        position: sticky;
        top: 20px;
        height: calc(100vh - 40px); /* Full height minus padding */
        overflow: hidden; /* Hide outer scroll */
    }

    .nav-section {
        padding: 0 20px;
        margin-bottom: 15px;
    }

    .nav-header {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #adb5bd;
        font-weight: 800;
        letter-spacing: 0.6px;
        margin: 0 0 12px 12px;
    }

    .nav-divider { border: 0; border-top: 1px solid #f5f5f5; margin: 15px 0 20px 0; }

    /* Client Header */
    .nav-client-name { font-size: 1.05rem; font-weight: 700; color: #333; margin: 0 0 4px 0; line-height: 1.3; }

    /* Items */
    .nav-item {
        display: flex; align-items: center; gap: 12px;
        padding: 11px 16px;
        color: #6c757d;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        border-radius: 10px;
        transition: all 0.2s ease;
        margin-bottom: 6px;
        border: 1px solid transparent;
    }

    .nav-item .material-symbols-rounded { font-size: 1.25rem; color: #adb5bd; transition: 0.2s; }
    
    .nav-item:hover {
        background: #f8f9fa;
        color: #333;
        transform: translateX(3px);
    }
    .nav-item:hover .material-symbols-rounded { color: #333; }

    /* Active State (Modern Pill Style) */
    .nav-item.active {
        background: #e8f5e9; /* Light Green */
        color: #146c43; /* Dark Green */
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(25, 135, 84, 0.1);
        border-color: #c3e6cb;
    }
    .nav-item.active .material-symbols-rounded { color: #146c43; }

</style>
