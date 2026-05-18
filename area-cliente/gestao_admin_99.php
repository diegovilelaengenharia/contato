<?php
/**
 * Controlador Principal do Admin
 * Orquestra views e componentes.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Error Handling Global
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        echo "<div style='background:red; color:white; padding:20px; font-weight:bold; z-index:99999; position:relative;'>FATAL ERROR ADMIN: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "</div>";
        die();
    }
});

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
        
        <main style="padding-bottom: 80px;">
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

                <div style="background:#fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); padding: 35px; margin-bottom: 30px; border: 1px solid #f0f0f0;">
                    <?php 
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
                </div>
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
