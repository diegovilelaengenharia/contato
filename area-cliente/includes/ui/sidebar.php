<aside class="sidebar admin-nav-sidebar">
    
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

        <!-- Acesso Rápido -->
         <div class="nav-item-group">
            <div class="nav-item" onclick="this.parentElement.classList.toggle('open')" style="cursor:pointer; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-rounded">bolt</span>
                    Acesso Rápido
                </div>
                <span class="material-symbols-rounded arrow">expand_more</span>
            </div>
            <div class="nav-subitems">
                 <a href="?importar=1" class="nav-subitem">Solicitações Web</a>
                 <a href="https://oliveira.atende.net/atendenet?source=pwa" target="_blank" class="nav-subitem">Atende Oliveira</a>
                 <a href="https://ridigital.org.br/VisualizarMatricula/DefaultVM.aspx?from=menu" target="_blank" class="nav-subitem">Matrículas</a>
            </div>
        </div>
    </div>

    <!-- ADMIN PROFILE FOOTER REMOVED (Moved to Top Right) -->

    <!-- SEPARADOR -->
    <?php if($cliente_ativo): ?>
        <hr class="nav-divider">

        <!-- SEÇÃO CLIENTE SELECIONADO -->
        <div class="nav-section">
            <h6 class="nav-header" style="color:#198754;">CLIENTE SELECIONADO</h6>
            
            <div class="nav-client-info" style="display:flex; align-items:center; gap:12px; padding: 12px; background: #f8f9fa; border-radius: 12px; margin-bottom: 20px;">
                <!-- AVATAR (Compact) -->
                <div style="width:45px; height:45px; min-width:45px; position:relative;">
                    <?php if($avatar_url): ?>
                        <img src="<?= $avatar_url ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%; border:2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:#d1e7dd; color:#146c43; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem; font-weight:800; border:2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <?= strtoupper(substr($cliente_ativo['nome'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="flex:1; overflow:hidden;">
                    <h3 class="nav-client-name" style="font-size:0.9rem; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($cliente_ativo['nome']) ?>"><?= htmlspecialchars($cliente_ativo['nome']) ?></h3>
                    
                    <div style="font-size:0.75rem; color:#666; display:flex; flex-direction:column; gap:0;">
                        <span>📱 <?= $detalhes['contato_tel'] ?? '--' ?></span>
                        <span style="font-size:0.7rem; color:#999;">ID: #<?= str_pad($cliente_ativo['id'], 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                </div>
            </div>

            <!-- NAVEGAÇÃO DO CLIENTE -->
            <nav class="client-nav">
                <!-- Perfil (Novo) -->
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=perfil" class="nav-item <?= ($active_tab=='perfil') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">person</span>
                    Perfil
                </a>

                <!-- TL -> Histórico -->
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="nav-item <?= ($active_tab=='andamento'||$active_tab=='cadastro') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">history</span>
                    Histórico
                </a>

                <!-- Docs -> Checklist -->
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=docs_iniciais" class="nav-item <?= ($active_tab=='docs_iniciais') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">folder_open</span>
                    Documentos
                </a>

                <!-- Pendências -->
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="nav-item <?= ($active_tab=='pendencias') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">warning</span>
                    Pendências
                </a>

                <!-- Financeiro -->
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="nav-item <?= ($active_tab=='financeiro') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">paid</span>
                    Financeiro
                </a>

                 <!-- Arquivos Finais -->
                 <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=arquivos" class="nav-item <?= ($active_tab=='arquivos') ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">inventory_2</span>
                    Arquivos
                </a>
            </nav>

            <div style="margin-top:20px; padding:15px; background:#f0f7ff; border-radius:8px; border:1px solid #cce5ff; text-align:center;">
                 <a href="gerenciar_cliente.php?id=<?= $cliente_ativo['id'] ?>" style="display:block; margin-bottom:8px; font-size:0.85rem; color:#0d6efd; text-decoration:none; font-weight:600;">✏️ Editar Dados</a>
                 <a href="area_cliente.php" style="display:block; font-size:0.85rem; color:#666; text-decoration:none;">🔗 Ver como Cliente</a>
            </div>
        </div>
    <?php endif; ?>

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
