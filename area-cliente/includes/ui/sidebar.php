<aside class="sidebar admin-nav-sidebar">

    <!-- ADMIN PROFILE (Sidebar Header - Clean & Simple) -->
    <!-- ADMIN PROFILE MOVED TO MAIN HEADER -->

    <!-- BRANDING HEADER (REMOVED) -->

    <!-- SEÇÃO CLIENTE SELECIONADO (Topo) -->
    <!-- SEÇÃO CLIENTE SELECIONADO (Removido daqui e movido para a aba Perfil) -->
    <?php if ($cliente_ativo): ?>
        <!-- Conteúdo movido para a aba Perfil -->
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
    <div style="margin-top:auto; padding:20px; border-top:1px solid #f0f0f0; background: linear-gradient(to bottom, #fff 0%, #f1f8f5 100%); border-radius: 0 0 16px 16px; text-align:center; position:relative; overflow:hidden;">
        <!-- Green Accent Line Bottom -->
        <div style="position:absolute; bottom:0; left:0; width:100%; height:4px; background:#198754;"></div>

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

        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #eaeaea;
        align-self: flex-start;
        position: sticky;
        top: 20px;
        height: calc(100vh - 40px);
        /* Full height minus padding */
        overflow: hidden;
        /* Hide outer scroll */
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

    .nav-divider {
        border: 0;
        border-top: 1px solid #f5f5f5;
        margin: 15px 0 20px 0;
    }

    /* Client Header */
    .nav-client-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #333;
        margin: 0 0 4px 0;
        line-height: 1.3;
    }

    /* Items */
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
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

    .nav-item .material-symbols-rounded {
        font-size: 1.25rem;
        color: #adb5bd;
        transition: 0.2s;
    }

    .nav-item:hover {
        background: #f8f9fa;
        color: #333;
        transform: translateX(3px);
    }

    .nav-item:hover .material-symbols-rounded {
        color: #333;
    }

    /* Active State (Modern Pill Style) */
    .nav-item.active {
        background: #e8f5e9;
        /* Light Green */
        color: #146c43;
        /* Dark Green */
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(25, 135, 84, 0.1);
        border-color: #c3e6cb;
    }

    .nav-item.active .material-symbols-rounded {
        color: #146c43;
    }
</style>