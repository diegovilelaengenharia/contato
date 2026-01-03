<div class="admin-mobile-header" style="display:none; background:#146c43; color:white; padding:15px 20px; text-align:center; border-bottom:4px solid #0f5132;">
    <img src="../assets/logo.png" alt="Vilela Engenharia" style="height:45px; margin-bottom:10px; display:block; margin-left:auto; margin-right:auto;">
    <h3 style="margin:0 0 5px 0; font-size:1.1rem; text-transform:uppercase; letter-spacing:1px; font-weight:800;">Gestão Administrativa</h3>
    <div style="font-size:0.85rem; opacity:0.9; line-height:1.4;">
        Eng. Diego Vilela &nbsp;|&nbsp; CREA-MG: 235474/D<br>
        vilela.eng.mg@gmail.com &nbsp;|&nbsp; (35) 98452-9577
    </div>
</div>

<header class="admin-header" style="height: 45px; min-height: 45px; padding: 0 20px; background: #fff; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <nav>
        <ul style="display:flex; gap:20px; list-style:none; margin:0; padding:0; align-items:center;">
            <li><a href="#" style="text-decoration:none; color:#444; font-size:0.9rem; font-weight:500;">Arquivo</a></li>
            <li><a href="#" style="text-decoration:none; color:#444; font-size:0.9rem; font-weight:500;">Editar</a></li>
            <!-- Dropdown: Cadastro -->
            <li style="position:relative; height:100%; display:flex; align-items:center;" onmouseover="this.querySelector('.dropdown-menu').style.display='block'" onmouseout="this.querySelector('.dropdown-menu').style.display='none'">
                <a href="#" style="text-decoration:none; color:#444; font-size:0.9rem; font-weight:500; padding:10px 0; display:flex; align-items:center; gap:4px;">
                    Cadastro <span style="font-size:0.7rem;">▼</span>
                </a>
                <div class="dropdown-menu" style="display:none; position:absolute; top:100%; left:-10px; background:#fff; min-width:200px; box-shadow:0 4px 15px rgba(0,0,0,0.1); border:1px solid #eee; border-radius:8px; padding:8px 0; z-index:1000;">
                    <a href="?novo=true" style="display:flex; align-items:center; gap:8px; padding:10px 15px; text-decoration:none; color:#444; font-size:0.85rem; transition:0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                        <span class="material-symbols-rounded" style="font-size:1.1rem; color:#888;">person_add</span> Novo Cliente
                    </a>
                    <a href="../cadastro.php" target="_blank" style="display:flex; align-items:center; gap:8px; padding:10px 15px; text-decoration:none; color:#444; font-size:0.85rem; transition:0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                        <span class="material-symbols-rounded" style="font-size:1.1rem; color:#888;">public</span> Pré-Cadastro ↗
                    </a>
                    <div style="height:1px; background:#eee; margin:5px 0;"></div>
                    <a href="?importar=true" style="display:flex; align-items:center; gap:8px; padding:10px 15px; text-decoration:none; color:#444; font-size:0.85rem; transition:0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                        <span class="material-symbols-rounded" style="font-size:1.1rem; color:#888;">move_to_inbox</span> Solicitações
                        <?php if(isset($kpi_pre_pendentes) && $kpi_pre_pendentes > 0): ?>
                            <span style="background:#dc3545; color:white; font-size:0.7rem; padding:1px 6px; border-radius:10px; margin-left:auto;"><?= $kpi_pre_pendentes ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </li>
            <li><a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=configuracoes" style="text-decoration:none; color:#444; font-size:0.9rem; font-weight:500;">Configurações</a></li>
            <li><a href="#" style="text-decoration:none; color:#444; font-size:0.9rem; font-weight:500;">Ajuda</a></li>
        </ul>
    </nav>
    <div style="display:flex; align-items:center; gap:15px;">
        <span style="font-size:0.8rem; color:#888;">v1.2.0</span>
        <a href="?sair=true" style="text-decoration:none; color:#dc3545; font-size:0.9rem; font-weight:600; display:flex; align-items:center; gap:5px;">
            <span class="material-symbols-rounded" style="font-size:1.1rem;">logout</span> Sair
        </a>
    </div>
</header>
