<?php // deploy retry v2.3.1
/**
 * index.php — Front Controller e Roteador Central do Painel Admin.
 *
 * Renderiza o layout comum (Sidebar + Main Content) e inclui
 * a view apropriada baseada na rota informada.
 */
require_once __DIR__ . '/init_admin.php';

// Roteamento de Views
$route = $_GET['route'] ?? 'dashboard';
$allowed_routes = ['dashboard', 'clientes', 'cliente-detalhes', 'configuracoes', 'avisos', 'auditoria'];

if (!in_array($route, $allowed_routes)) {
    $route = 'dashboard';
}

// Lógica de Flash Messages (Toastify ou Alertas)
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Configurações Globais carregadas do banco para exibição/uso
$curr_settings = [];
try {
    $rows = $pdo->query("SELECT * FROM admin_settings")->fetchAll();
    foreach ($rows as $r) {
        $curr_settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) {
    // Tabela não existe ou banco fora
}

$company_crea = $curr_settings['company_crea'] ?? 'CREA MG 235.474/D';
$company_phone = $curr_settings['company_phone'] ?? '(35) 98452-9577';
$company_email = $curr_settings['company_email'] ?? 'vilela.eng.mg@gmail.com';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($route); ?> | Painel Administrativo Vilela Engenharia</title>
    
    <!-- Google Fonts Outfit & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../admin_style.css?v=<?php echo APP_VERSION; ?>">
    <link rel="icon" href="../../assets/logo.png" type="image/png">
    
    <!-- Toastify CSS & JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- SweetAlert2 (Para confirmações elegantes) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- CKEditor 5 (Para campos de texto ricos, se usados) -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
    
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>

    <div class="admin-container">
        <!-- SIDEBAR DE NAVEGAÇÃO -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- CONTEÚDO PRINCIPAL -->
        <main>
            <!-- Botão de Menu Mobile -->
            <button type="button" class="mobile-menu-toggle" onclick="toggleSidebar()">
                <span class="material-symbols-rounded">menu</span> Menu do Painel
            </button>
            
            <?php
            // Inclui dinamicamente a view correspondente
            // Converte hifens em underlines para corresponder ao nome físico do arquivo (ex: cliente-detalhes -> cliente_detalhes.php)
            $route_file = str_replace('-', '_', $route);
            $view_file = __DIR__ . "/views/{$route_file}.php";
            if (file_exists($view_file)) {
                require_once $view_file;
            } else {
                echo "<div class='form-card'><h2>Erro 404</h2><p>Página de visualização não encontrada.</p></div>";
            }
            ?>
        </main>
    </div>

    <!-- Script de Responsividade e Sidebar Mobile -->
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-nav-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        }
        
        // Exibe Flash Message se existir
        <?php if ($flash_message): ?>
        Toastify({
            text: "<?php echo addslashes($flash_message['text']); ?>",
            duration: 5000,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: {
                background: "<?php echo $flash_message['type'] === 'success' ? 'var(--color-primary)' : 'var(--color-danger)'; ?>",
                borderRadius: "10px",
                fontWeight: "600",
                boxShadow: "var(--shadow)"
            }
        }).showToast();
        <?php endif; ?>

        // Helper de Confirmação SweetAlert2
        function confirmDelete(event, message) {
            event.preventDefault();
            Swal.fire({
                title: 'Confirmar exclusão',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
            return false;
        }
    </script>
</body>
</html>
