<?php
/**
 * Controlador Principal do Admin
 * Orquestra views e componentes.
 *
 * Tratamento de erros (display_errors off + páginas amigáveis 500/503)
 * é centralizado em includes/init.php.
 */
require 'includes/init.php';
require 'includes/schema.php';
require 'includes/admin_helpers.php';
require 'includes/processamento.php';
require 'includes/exportacao.php';

// --- Consultas Iniciais ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
$cliente_ativo = null;
$detalhes = [];

if (isset($_GET['cliente_id'])) {
    $id = $_GET['cliente_id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?"); 
    $stmt->execute([$id]);
    $cliente_ativo = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?"); 
    $stmt->execute([$id]);
    $detalhes = $stmt->fetch() ?: [];
}

$active_tab = $_GET['tab'] ?? 'cadastro';

// Get Avatar URL for Sidebar
$avatar_url = null;
if($cliente_ativo) {
    $avatar_file = glob("uploads/avatars/avatar_{$cliente_ativo['id']}.*");
    $avatar_url = !empty($avatar_file) ? $avatar_file[0] . '?v=' . time() : null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<?php require 'includes/ui/head.php'; ?>
<body>

    <div class="admin-container">
        <?php require 'includes/ui/sidebar.php'; ?>

        <main>
            <button type="button" class="mobile-menu-toggle" onclick="toggleSidebar()">
                <span class="material-symbols-rounded">menu</span> Menu
            </button>
            <?php
            if(isset($_GET['importar'])) {
                require 'includes/views/admin/importar.php';
            } elseif(isset($_GET['novo'])) {
                echo "<script>window.location.href='gerenciar_cliente.php';</script>";
            } elseif($cliente_ativo) {
                ?>
                <!-- ABAS DE NAVEGAÇÃO -->
                <div class="nav-pills">
                    <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=docs_iniciais" class="nav-pill <?= ($active_tab=='docs_iniciais')?'active':'' ?>">
                        <span class="material-symbols-rounded">folder_open</span> Documentos
                    </a>
                    <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="nav-pill <?= ($active_tab=='andamento'||$active_tab=='cadastro')?'active':'' ?>">
                        <span class="material-symbols-rounded">history</span> Timeline
                    </a>
                    <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="nav-pill <?= ($active_tab=='pendencias')?'active':'' ?>">
                        <span class="material-symbols-rounded">warning</span> Pendências
                    </a>
                    <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="nav-pill <?= ($active_tab=='financeiro')?'active':'' ?>">
                        <span class="material-symbols-rounded">paid</span> Financeiro
                    </a>
                    <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=arquivos" class="nav-pill <?= ($active_tab=='arquivos')?'active':'' ?>">
                        <span class="material-symbols-rounded">inventory_2</span> Arquivos
                    </a>
                </div>

                <?php
                // Cada view abre seu próprio container (.admin-tab-content)
                switch($active_tab) {
                    case 'docs_iniciais': require 'includes/views/admin/documentos.php'; break;
                    case 'pendencias':    require 'includes/views/admin/pendencias.php'; break;
                    case 'financeiro':    require 'includes/views/admin/financeiro.php'; break;
                    case 'arquivos':      require 'includes/views/admin/arquivos.php'; break;
                    case 'andamento':
                    case 'cadastro':
                    default:              require 'includes/views/admin/timeline.php'; break;
                }
                ?>
                <?php
            } else {
                require 'includes/views/admin/dashboard.php';
            }
            ?>
        </main>
    </div>

    <?php require 'includes/ui/floating_buttons.php'; ?>
    <?php require 'includes/ui/footer_scripts.php'; ?>

</body>
</html>
