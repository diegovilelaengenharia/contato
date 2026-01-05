<?php
require 'includes/init.php';
require 'includes/schema.php';

// --- Logic to Determine Mode (Create vs Edit) ---
$cliente_id = $_GET['id'] ?? null;
$cliente = null;
$detalhes = null;
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
        } catch (Exception $e) { $campos_extras = []; }
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
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'success_update') $msg_alert = "<script>alert('✅ Dados atualizados com sucesso!');</script>";
    if($_GET['msg'] == 'welcome') $msg_alert = "<script>alert('✅ Cliente criado com sucesso! Complete os dados agora.');</script>";
    if($_GET['msg'] == 'error') $msg_alert = "<script>alert('❌ Erro: " . htmlspecialchars($_GET['details'] ?? 'Desconhecido') . "');</script>";
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
        <?php require 'includes/ui/sidebar.php'; ?>
        
        <main style="padding-bottom: 80px;">
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

</body>
</html>
