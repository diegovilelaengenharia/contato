<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/init.php';
require 'includes/schema.php';

// --- Logic to Determine Mode (Create vs Edit) ---
$cliente_id = $_GET['id'] ?? null;
$cliente = [];
$detalhes = [];
$campos_extras = [];
$is_edit = false;

if ($cliente_id) {
    // EDIT MODE
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        $is_edit = true;
        // Fetch Details
        $stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
        $stmtDet->execute([$cliente_id]);
        $detalhes = $stmtDet->fetch();

        // Fetch Extra Fields
        try {
            $stmtEx = $pdo->prepare("SELECT * FROM processo_campos_extras WHERE cliente_id = ?");
            $stmtEx->execute([$cliente_id]);
            $campos_extras = $stmtEx->fetchAll();
        } catch (Exception $e) {
            $campos_extras = [];
        }
    } else {
        // ID provided but not found? standard generic error or redirect
        header("Location: gestao_admin_99.php?msg=error&details=Cliente nao encontrado");
        exit;
    }
} else {
    // CREATE MODE
    // predefined empty arrays not strictly needed as template handles nulls, but good practice if needed
}

// Msg Handling
$msg_alert = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'success_update') $msg_alert = "<script>alert('✅ Dados atualizados com sucesso!');</script>";
    if ($_GET['msg'] == 'welcome') $msg_alert = "<script>alert('✅ Cliente criado com sucesso! Complete os dados agora.');</script>";
    if ($_GET['msg'] == 'error') $msg_alert = "<script>alert('❌ Erro: " . htmlspecialchars($_GET['details'] ?? 'Desconhecido') . "');</script>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Editar Cliente' : 'Novo Cliente' ?> | Vilela Engenharia</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />

    <!-- SweetAlert2 + Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css?v=<?= time() ?>">
    <link rel="icon" href="../assets/logo.png" type="image/png">

    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
</head>

<body>

    <?php require 'includes/ui/header.php'; ?>

    <div class="admin-container">
        <!-- FLOATING NAVIGATION BUTTONS (Fixed Top Left) -->
        <div style="position:fixed; top:30px; left:30px; display:flex; flex-direction:column; gap:15px; z-index:9999;">
            <!-- Visão Geral -->
            <a href="gestao_admin_99.php" title="Visão Geral" style="width:50px; height:50px; background:white; border-radius:50%; box-shadow:0 8px 20px rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; color:#555; text-decoration:none; transition:all 0.3s; border:1px solid #f0f0f0;" onmouseover="this.style.transform='scale(1.1)'; this.style.color='#198754'" onmouseout="this.style.transform='scale(1)'; this.style.color='#555'">
                <span class="material-symbols-rounded" style="font-size:26px;">grid_view</span>
            </a>
            <!-- Clientes (List) -->
            <a href="gestao_admin_99.php#lista_clientes" title="Clientes" style="width:50px; height:50px; background:white; border-radius:50%; box-shadow:0 8px 20px rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; color:#555; text-decoration:none; transition:all 0.3s; border:1px solid #f0f0f0;" onmouseover="this.style.transform='scale(1.1)'; this.style.color='#198754'" onmouseout="this.style.transform='scale(1)'; this.style.color='#555'">
                <span class="material-symbols-rounded" style="font-size:26px;">groups</span>
            </a>
            <!-- Novo Cliente -->
            <a href="gerenciar_cliente.php" title="Novo Cliente" style="width:50px; height:50px; background:#198754; border-radius:50%; box-shadow:0 8px 20px rgba(25,135,84,0.25); display:flex; align-items:center; justify-content:center; color:white; text-decoration:none; transition:all 0.3s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 12px 25px rgba(25,135,84,0.35)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 8px 20px rgba(25,135,84,0.25)'">
                <span class="material-symbols-rounded" style="font-size:26px;">person_add</span>
            </a>
        </div>

        <main style="padding-bottom: 80px; width: 100%; max-width: 1200px; margin: 0 auto; padding: 40px; padding-left: 90px;">
            <?= $msg_alert ?>

            <div class="form-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div>
                        <h2><?= $is_edit ? 'Editar Cadastro' : 'Cadastrar Novo Cliente' ?></h2>
                        <p style="color:#666; font-size:0.9rem;"><?= $is_edit ? 'Atualize as informações do cliente abaixo.' : 'Preencha o formulário para adicionar um novo cliente.' ?></p>
                    </div>
                    <a href="gestao_admin_99.php" class="btn-cancel" style="text-decoration:none; padding:8px 16px; border-radius:5px; background:#f8f9fa; border:1px solid #ddd; color:#333;">&larr; Voltar</a>
                </div>

                <?php include 'includes/form_cliente_template.php'; ?>
            </div>

        </main>
    </div>

    <!-- GLOBAL DASHBOARD FOOTER (Premium Vilela Style) -->
    <footer style="margin-top: 80px; background: #fff; box-shadow: 0 -5px 20px rgba(0,0,0,0.02); border-top-left-radius: 40px; border-top-right-radius: 40px; overflow: hidden; position: relative;">

        <!-- White Content Section -->
        <div style="padding: 50px 20px 40px 20px; display: flex; align-items: center; justify-content: center; gap: 60px; flex-wrap: wrap;">

            <!-- Logo -->
            <img src="../assets/logo-vilela-mix.png" alt="Vilela Engenharia" style="height: 65px; object-fit: contain;">

            <!-- Vertical Divider -->
            <div class="footer-divider" style="width: 1px; height: 60px; background: #e0e0e0; display:block;"></div>

            <!-- Engineer Info -->
            <div style="text-align: left;">
                <span style="display: block; font-size: 0.75rem; color: #adb5bd; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; margin-bottom: 6px;">Engenheiro Responsável</span>
                <strong style="display: block; font-size: 1.4rem; color: #2c3e50; font-weight: 800; letter-spacing: -0.5px; line-height: 1.1;">Diego T. N. Vilela</strong>
                <span style="display: block; font-size: 0.95rem; color: #198754; font-weight: 600; margin-top: 4px;">CREA 235.474/D</span>
            </div>

        </div>

        <!-- Green Copyright Bar -->
        <div style="background: #198754; color: white; text-align: center; padding: 18px; font-size: 0.9rem; font-weight: 500; letter-spacing: 0.5px;">
            &copy; 2026 Vilela Engenharia
        </div>

    </footer>

    <style>
        @media (max-width: 768px) {
            main {
                padding-left: 20px !important;
                padding-top: 100px !important;
            }

            .footer-divider {
                display: none !important;
            }
        }
    </style>

</body>

</html>