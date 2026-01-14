<?php
// Ensure $clientes is available
if (!isset($clientes)) {
    try {
        $stmt_cli_menu = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
        $clientes_menu = $stmt_cli_menu->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $clientes_menu = [];
    }
} else {
    $clientes_menu = $clientes;
}
?>

<!-- FLOATING NAVIGATION CONTAINER -->
<div style="position:fixed; top:30px; left:30px; display:flex; flex-direction:column; gap:15px; z-index:9999;">

    <!-- Visão Geral -->
    <a href="gestao_admin_99.php" title="Visão Geral"
        class="float-btn"
        style="width:50px; height:50px; background:white; border-radius:50%; box-shadow:0 8px 20px rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; color:#555; text-decoration:none; transition:all 0.3s; border:1px solid #f0f0f0;">
        <span class="material-symbols-rounded" style="font-size:26px;">grid_view</span>
    </a>

    <!-- Clientes (Tree Toggle) -->
    <div style="position:relative;">
        <button onclick="toggleClientTree()" title="Meus Clientes"
            class="float-btn"
            style="width:50px; height:50px; background:white; border-radius:50%; box-shadow:0 8px 20px rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; color:#555; border:1px solid #f0f0f0; cursor:pointer; transition:all 0.3s;">
            <span class="material-symbols-rounded" style="font-size:26px;">groups</span>
        </button>

        <!-- CLIENT TREE / DROPDOwN -->
        <div id="clientTree" style="display:none; position:absolute; top:0; left:60px; background:white; width:260px; max-height:400px; overflow-y:auto; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.15); border:1px solid #eaeaea; padding:10px;">
            <h6 style="margin:5px 10px 10px 10px; font-size:0.75rem; text-transform:uppercase; color:#adb5bd; font-weight:700;">Carteira de Clientes</h6>

            <?php if (count($clientes_menu) > 0): ?>
                <?php foreach ($clientes_menu as $c): ?>
                    <a href="gestao_admin_99.php?cliente_id=<?= $c['id'] ?>&tab=perfil"
                        style="display:flex; align-items:center; gap:10px; padding:10px; border-radius:8px; text-decoration:none; color:#495057; font-size:0.9rem; transition:background 0.2s;">
                        <span class="material-symbols-rounded" style="font-size:1.2rem; color:#198754;">person</span>
                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($c['nome']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:10px; color:#999; font-size:0.9rem; font-style:italic;">Nenhum cliente.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Novo Cliente -->
    <a href="gerenciar_cliente.php" title="Novo Cliente"
        class="float-btn"
        style="width:50px; height:50px; background:#198754; border-radius:50%; box-shadow:0 8px 20px rgba(25,135,84,0.25); display:flex; align-items:center; justify-content:center; color:white; text-decoration:none; transition:all 0.3s;">
        <span class="material-symbols-rounded" style="font-size:26px;">person_add</span>
    </a>
</div>

<script>
    function toggleClientTree() {
        const tree = document.getElementById('clientTree');
        if (tree.style.display === 'none' || tree.style.display === '') {
            tree.style.display = 'block';
            // Animation (Simple Fade In)
            tree.style.opacity = '0';
            tree.style.transform = 'translateX(-10px)';
            setTimeout(() => {
                tree.style.opacity = '1';
                tree.style.transform = 'translateX(0)';
                tree.style.transition = 'all 0.2s ease';
            }, 10);
        } else {
            tree.style.display = 'none';
        }
    }

    // Close when clicking outside
    document.addEventListener('click', function(event) {
        const tree = document.getElementById('clientTree');
        const btn = event.target.closest('button[title="Meus Clientes"]');
        const insideTree = event.target.closest('#clientTree');

        if (!btn && !insideTree && tree.style.display === 'block') {
            tree.style.display = 'none';
        }
    });

    // Add hover effects via JS to generic float-btns if needed, or rely on inline CSS
    document.querySelectorAll('.float-btn').forEach(btn => {
        btn.addEventListener('mouseover', () => {
            btn.style.transform = 'scale(1.1)';
            if (btn.style.backgroundColor === ('white')) btn.style.color = '#198754';
        });
        btn.addEventListener('mouseout', () => {
            btn.style.transform = 'scale(1)';
            if (btn.style.backgroundColor === 'white') btn.style.color = '#555';
        });
    });
</script>