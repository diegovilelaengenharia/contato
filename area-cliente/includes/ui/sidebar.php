<?php
// Ensure metrics are available (or default to 0)
$kpi_pre_pendentes = $kpi_pre_pendentes ?? 0;
?>
<button class="mobile-menu-toggle" onclick="toggleSidebar()">
    ☰ Menu de Navegação
</button>
<aside class="sidebar" id="mobileSidebar">
    <nav class="sidebar-menu">
        <h4 style="font-size:0.75rem; text-transform:uppercase; color:#adb5bd; font-weight:700; margin:10px 0 5px 10px;">Principal</h4>
        <a href="gestao_admin_99.php" class="btn-menu <?= (!isset($_GET['cliente_id']) && !isset($_GET['novo']) && !isset($_GET['importar'])) ? 'active' : '' ?>">
            <span class="material-symbols-rounded">dashboard</span>
            Visão Geral
        </a>
        
        <?php 
            // Lógica de Cor: Amarelo se tiver pendências, Padrão (branco) se não.
            $alert_color_style = ($kpi_pre_pendentes > 0) ? 
                'background: linear-gradient(135deg, #fff3cd, #ffecb5); color: #856404; border: 1px solid #ffeeba;' : 
                'background: #fff; color: var(--color-text); border: 1px solid transparent;'; 
        ?>
        <button onclick="document.getElementById('modalNotificacoes').showModal()" class="btn-menu" style="cursor:pointer; text-align:left; width:100%; font-family:inherit; font-size:inherit; transition: 0.3s; <?= $alert_color_style ?>">
            <span class="material-symbols-rounded">notifications</span>
            Central de Avisos
            <?php if($kpi_pre_pendentes > 0): ?>
                <span style="background:#dc3545; color:white; padding:1px 8px; border-radius:12px; font-size:0.75rem; margin-left:auto; line-height:1.2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-weight:bold;"><?= $kpi_pre_pendentes ?></span>
            <?php endif; ?>
        </button>
        
        <h4 style="font-size:0.75rem; text-transform:uppercase; color:#adb5bd; font-weight:700; margin:15px 0 5px 10px;">Cadastro</h4>
        <!-- Botão Novo Cliente (Neutro) -->
        <a href="?novo=true" class="btn-menu <?= (isset($_GET['novo'])) ? 'active' : '' ?>">
            <span class="material-symbols-rounded">person_add</span>
            Novo Cliente
        </a>
        <a href="../cadastro.php" target="_blank" class="btn-menu">
            <span class="material-symbols-rounded">public</span>
            Pré-Cadastro ↗
        </a>
        <a href="?importar=true" class="btn-menu <?= (isset($_GET['importar'])) ? 'active' : '' ?>">
            <span class="material-symbols-rounded">move_to_inbox</span>
            Solicitações
            <?php if(isset($kpi_pre_pendentes) && $kpi_pre_pendentes > 0): ?>
                <span class="badge-count"><?= $kpi_pre_pendentes ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <h4 style="margin: 20px 0 10px 10px; color: var(--color-text-subtle); display:flex; align-items:center; gap:8px; font-size:0.8rem; text-transform:uppercase; font-weight:700;">📂 Meus Clientes</h4>
    <div class="client-list-fancy" style="padding:0 10px; max-height:500px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
        <?php foreach($clientes as $c): 
            $isActive = (isset($cliente_ativo) && $cliente_ativo['id'] == $c['id']);
            
            // Dados do DB já são o Primeiro Nome (devido à migração)
            // NEW: Show First 2 Names
            $parts = explode(' ', trim($c['nome']));
            $first_name = $parts[0] . (isset($parts[1]) ? ' ' . $parts[1] : '');
            $first_name = htmlspecialchars($first_name);
            
            $initial = strtoupper(substr($first_name, 0, 1));
            
            // Estilo
            $bg = $isActive ? 'var(--color-primary-light)' : '#fff';
            $border = $isActive ? '1px solid var(--color-primary)' : '1px solid transparent';
            $color = $isActive ? 'var(--color-primary)' : '#444';
        ?>
            <a href="?cliente_id=<?= $c['id'] ?>" style="display:flex; align-items:center; gap:12px; padding:10px; background:<?= $bg ?>; border-radius:8px; text-decoration:none; color:<?= $color ?>; border:<?= $border ?>; transition:0.2s;" onmouseover="this.style.background='#f0f8f5'" onmouseout="this.style.background='<?= $bg ?>'">
                <div style="width:32px; height:32px; background:<?= $isActive?'var(--color-primary)':'#eee' ?>; color:<?= $isActive?'#fff':'#777' ?>; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.9rem;">
                    <span class="material-symbols-rounded" style="font-size:1.1rem;">person</span>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $first_name ?></div>
                    <div style="font-size:0.75rem; opacity:0.7;">ID #<?= str_pad($c['id'], 3, '0', STR_PAD_LEFT) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</aside>
