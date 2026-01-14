<aside class="sidebar admin-nav-sidebar">
    
    <!-- BRANDING HEADER (Adjusted) -->
    <div style="text-align:center; padding: 30px 20px 15px 20px; margin-bottom: 20px;">
        <img src="../assets/logo.png" style="max-width: 90px; height: auto; margin-bottom: 15px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));">
        <h5 style="color: #6c757d; font-size: 0.85rem; font-weight: 800; letter-spacing: 1px; margin:0; text-transform: uppercase;">Painel Administrativo</h5>
    </div>

    <!-- SEÇÃO CLIENTE SELECIONADO (Topo) -->
    <?php if($cliente_ativo): ?>
        <div class="nav-section">
            <h6 class="nav-header" style="color:#198754; font-size:0.8rem; letter-spacing:0.5px;">CLIENTE ATUAL</h6>
            
            <!-- CLIENTE HEADER & AÇÕES -->
            <div class="nav-client-card" style="background: white; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border:1px solid #f1f1f1; overflow:hidden;">
                
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

                <!-- Barra de Ações (Icones Coloridos e VERMELHOS onde precisa) -->
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
                    <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" class="client-action-btn btn-danger-hover" onclick="return confirm('Deseja excluir este cliente?')" style="color:#dc3545; background:#fff5f5;" title="Excluir Cliente">
                        <span class="material-symbols-rounded">delete</span>
                    </a>
                </div>
            </div>

            <style>
                .client-action-btn {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 10px 0;
                    color: #555;
                    text-decoration: none;
                    transition: all 0.2s;
                    border-right: 1px solid #eee;
                }
                .client-action-btn:last-child { border-right: none; }
                .client-action-btn:hover { background: #e9ecef; color: #198754; }
                .client-action-btn .material-symbols-rounded { font-size: 1.1rem; }
                
                .btn-danger-hover:hover { background: #fff5f5 !important; color: #dc3545 !important; }
            </style>

            <!-- NAVEGAÇÃO DO CLIENTE REMOVIDA (Agora são Abas no Topo) -->


        </div>
        
        <hr class="nav-divider">
    <?php endif; ?>

    <!-- SEÇÃO GERAL -->
    <div class="nav-section">
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

</aside>

<!-- STYLE FOR SIDEBAR (Inline for component encapsulation) -->
<style>
    .admin-nav-sidebar {
        width: 270px;
        min-width: 270px;
        background: white;
        border-radius: 16px;
        padding: 25px 0; /* Vertical padding only, internal pads handled by items */
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        border: 1px solid #eaeaea;
        align-self: flex-start;
        position: sticky;
        top: 90px;
        max-height: calc(100vh - 110px);
        overflow-y: auto;
    }

    .nav-section {
        padding: 0 20px;
        margin-bottom: 15px;
    }

    .nav-header {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #999;
        font-weight: 700;
        letter-spacing: 0.8px;
        margin: 0 0 10px 10px; /* Indent slightly to align with text */
    }

    .nav-divider {
        border: 0;
        border-top: 1px solid #eee;
        margin: 15px 0 20px 0;
    }

    /* Client Header */
    .nav-client-info {
        padding: 0 10px 15px 10px;
        margin-bottom: 5px;
    }
    .nav-client-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
        margin: 0 0 4px 0;
        line-height: 1.3;
    }
    .nav-client-id {
        font-size: 0.8rem;
        color: #888;
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #eee;
    }

    /* Items */
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        color: #555;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        border-radius: 8px; /* Rounded right only? Image shows boxy or slight round. Let's do standard rounded */
        transition: all 0.2s;
        margin-bottom: 4px;
        border-left: 4px solid transparent; /* For active state strip */
    }

    .nav-item .material-symbols-rounded {
        font-size: 1.3rem;
        color: #888;
        transition: 0.2s;
    }
    
    .nav-item:hover {
        background: #fdfdfd;
        color: #000;
    }

    /* Active State (Idea from photo: Green BG/Strip) */
    .nav-item.active {
        background: #e8f5e9; /* Light Green */
        color: #146c43; /* Dark Green */
        border-left-color: #146c43;
        font-weight: 700;
    }
    .nav-item.active .material-symbols-rounded {
        color: #146c43;
    }

    /* Subitems */
    .nav-subitems {
        display: none;
        padding-left: 44px; /* Align with text */
        margin-top: 5px;
    }
    .nav-item-group.open .nav-subitems { display: block; }
    .nav-item-group.open .arrow { transform: rotate(180deg); }
    
    .nav-subitem {
        display: block;
        padding: 8px 0;
        font-size: 0.9rem;
        color: #666;
        text-decoration: none;
        transition: 0.2s;
    }
    .nav-subitem:hover { color: #146c43; }

</style>
