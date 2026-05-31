<?php
/**
 * Componente: Sidebar Admin (redesenhada 2026 — classes do design system)
 */

// --- DADOS DOS WIDGETS DA SIDEBAR ---
try {
    // Aniversariantes do mês
    $aniversariantes = $pdo->query("SELECT c.id, c.nome, pd.data_nascimento, DAY(pd.data_nascimento) as dia
        FROM clientes c
        JOIN processo_detalhes pd ON c.id = pd.cliente_id
        WHERE MONTH(pd.data_nascimento) = MONTH(CURRENT_DATE())
        ORDER BY dia ASC")->fetchAll();

    // Processos parados (> 15 dias sem movimento)
    $parados = $pdo->query("SELECT c.id, c.nome, MAX(pm.data_movimento) as ultima_mov
        FROM clientes c
        JOIN processo_movimentos pm ON c.id = pm.cliente_id
        GROUP BY c.id
        HAVING DATEDIFF(NOW(), ultima_mov) > 15
        ORDER BY ultima_mov ASC")->fetchAll();
} catch (Exception $e) {
    $aniversariantes = [];
    $parados = [];
}
?>
<aside id="mobileSidebar" class="admin-nav-sidebar">

    <!-- Perfil do administrador -->
    <div class="sidebar-profile">
        <img class="avatar" src="../assets/foto-diego-new.jpg"
             alt="Diego Vilela"
             onerror="this.src='https://ui-avatars.com/api/?name=Diego+Vilela&background=197e63&color=fff'">
        <div class="info">
            <div class="name">Diego Vilela</div>
            <div class="role">Administrador</div>
        </div>
        <div class="actions">
            <a href="admin_config.php" class="icon-btn" title="Configurações">
                <span class="material-symbols-rounded">settings</span>
            </a>
            <a href="logout.php" class="icon-btn danger" title="Sair">
                <span class="material-symbols-rounded">logout</span>
            </a>
        </div>
    </div>

    <div class="nav-scroll">

        <!-- Cliente selecionado -->
        <?php if ($cliente_ativo): ?>
        <div class="nav-section">
            <h6 class="nav-header">Cliente atual</h6>
            <div class="nav-client-card">
                <div class="client-head">
                    <?php if ($avatar_url): ?>
                        <img class="client-avatar" src="<?= htmlspecialchars($avatar_url) ?>" alt="">
                    <?php else: ?>
                        <div class="client-avatar"><?= strtoupper(substr($cliente_ativo['nome'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div style="flex:1; min-width:0;">
                        <h3 class="client-name" title="<?= htmlspecialchars($cliente_ativo['nome']) ?>">
                            <?= htmlspecialchars($cliente_ativo['nome']) ?>
                        </h3>
                        <div class="client-phone"><?= htmlspecialchars($detalhes['contato_tel'] ?? '--') ?></div>
                    </div>
                </div>
                <div class="client-actions">
                    <a href="gerenciar_cliente.php?id=<?= $cliente_ativo['id'] ?>" class="client-action-btn" title="Editar cadastro">
                        <span class="material-symbols-rounded">edit</span>
                    </a>
                    <a href="relatorio_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="client-action-btn" title="Resumo PDF">
                        <span class="material-symbols-rounded">picture_as_pdf</span>
                    </a>
                    <a href="actions/admin/cliente_impersonate.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="client-action-btn" title="Ver como cliente">
                        <span class="material-symbols-rounded">visibility</span>
                    </a>
                    <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" class="client-action-btn danger"
                       onclick="return confirm('Deseja excluir este cliente?')" title="Excluir cliente">
                        <span class="material-symbols-rounded">delete</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navegação geral -->
        <div class="nav-section">
            <h6 class="nav-header">Geral</h6>
            <a href="admin.php" class="nav-item <?= !$cliente_ativo ? 'active' : '' ?>">
                <span class="material-symbols-rounded">grid_view</span>
                Visão Geral
            </a>
            <a href="gerenciar_cliente.php" class="nav-item">
                <span class="material-symbols-rounded">person_add</span>
                Novo Cliente
            </a>
        </div>

        <!-- Alertas -->
        <div class="nav-section">
            <h6 class="nav-header">Alertas</h6>
            <button type="button" class="nav-item" onclick="document.getElementById('modalAniversariantes').showModal()">
                <span class="material-symbols-rounded">cake</span>
                Aniversários
                <?php if (count($aniversariantes) > 0): ?>
                    <span class="badge-count warn"><?= count($aniversariantes) ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="nav-item" onclick="document.getElementById('modalParados').showModal()">
                <span class="material-symbols-rounded">timer_off</span>
                Processos Parados
                <?php if (count($parados) > 0): ?>
                    <span class="badge-count danger"><?= count($parados) ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Modais dos widgets da sidebar -->
        <?php require 'includes/modals/sidebar_widgets.php'; ?>

    </div><!-- /nav-scroll -->

    <!-- Rodapé: responsável técnico -->
    <div class="sidebar-footer">
        <span class="label">Engenheiro Responsável</span>
        <strong class="name">Diego T. N. Vilela</strong>
        <span class="crea">CREA 235.474/D</span>
    </div>

</aside>
