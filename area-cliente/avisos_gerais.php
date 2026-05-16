<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require 'db.php';

// Segurança
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- LÓGICA DO AVISO GLOBAL ---
// 1. Salvar Novo Aviso
if(isset($_POST['btn_salvar_aviso'])) {
    $msg = trim($_POST['mensagem_aviso']);
    
    // Desativa todos os anteriores
    $pdo->query("UPDATE sistema_avisos SET ativo=0");
    
    if(!empty($msg)) {
        $stmt = $pdo->prepare("INSERT INTO sistema_avisos (mensagem, ativo) VALUES (?, 1)");
        $stmt->execute([$msg]);
        $feedback = "Aviso publicado com sucesso!";
        $feedback_color = "success";
    } else {
        $feedback = "Aviso removido. O painel dos clientes está limpo.";
        $feedback_color = "warning";
    }
}

// 2. Reativar Aviso Antigo
if(isset($_GET['reativar'])) {
    $id_reativar = (int)$_GET['reativar'];
    // Busca mensagem
    $msg_antiga = $pdo->query("SELECT mensagem FROM sistema_avisos WHERE id=$id_reativar")->fetchColumn();
    
    if($msg_antiga) {
        $pdo->query("UPDATE sistema_avisos SET ativo=0");
        $stmt = $pdo->prepare("INSERT INTO sistema_avisos (mensagem, ativo) VALUES (?, 1)");
        $stmt->execute([$msg_antiga]);
        header("Location: avisos_gerais.php?msg=reativado");
        exit;
    }
}

// 3. Buscar Dados
$aviso_ativo = $pdo->query("SELECT * FROM sistema_avisos WHERE ativo=1 ORDER BY id DESC LIMIT 1")->fetch();
$historico = $pdo->query("SELECT * FROM sistema_avisos ORDER BY data_criacao DESC LIMIT 20")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso Global | Vilela Engenharia</title>
    <!-- CSS Reuse -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin_style.css">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,0,0" rel="stylesheet" />
    <!-- Toastify -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <style>
        /* Specific Styles for this page */
        .preview-box {
            background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%); 
            color: white; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(102, 16, 242, 0.2);
            display: flex; 
            align-items: center; 
            gap: 20px;
            margin-bottom: 30px;
        }
        .history-list { list-style: none; padding: 0; }
        .history-item { 
            background: white; 
            border: 1px solid #eee; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 10px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .tag-status { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #f1f1f1; color: #888; }
    </style>
</head>
<body>

<header>
    <div style="display:flex; align-items:center; gap:15px;">
        <img src="../assets/logo_vilela.png" alt="Logo Vilela" style="height:40px; background:white; padding:5px; border-radius:8px;">
        <div style="line-height:1.2;">
            <h1 style="font-size:1.2rem; margin:0; font-weight:700;">Gestão Administrativa</h1>
            <span style="font-size:0.8rem; opacity:0.9;">Eng. Diego Vilela | Central de Avisos</span>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="gestao_admin_99.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:6px;">
            <span class="material-symbols-rounded">arrow_back</span> Voltar ao Painel
        </a>
        <a href="?sair=true" style="color: white; opacity:0.8;">Sair</a>
    </div>
</header>

<div class="admin-container">
    <!-- Sidebar Simplified (Reusing same style) -->
    <aside class="sidebar">
        <nav class="sidebar-menu">
            <h4 style="font-size:0.75rem; text-transform:uppercase; color:#adb5bd; font-weight:700; margin:10px 0 5px 10px;">Navegação</h4>
            <a href="gestao_admin_99.php" class="btn-menu">
                <span class="material-symbols-rounded">dashboard</span>
                Visão Geral
            </a>
            <a href="avisos_gerais.php" class="btn-menu active">
                <span class="material-symbols-rounded">campaign</span>
                Aviso Global
            </a>
        </nav>
    </aside>

    <main>
        <div style="max-width: 900px; margin: 0 auto;">
            
            <h2 style="color: var(--color-primary); margin-bottom: 20px; display:flex; align-items:center; gap:10px;">
                <span class="material-symbols-rounded">campaign</span> Central de Aviso Global
            </h2>

            <!-- Feedback -->
            <?php if(isset($feedback)): ?>
                <div style="padding:15px; border-radius:8px; margin-bottom:20px; background: <?= $feedback_color=='success'?'#d1e7dd':'#fff3cd' ?>; color: <?= $feedback_color=='success'?'#0f5132':'#856404' ?>;">
                    <?= $feedback ?>
                </div>
            <?php endif; ?>

            <!-- VISUALIZAÇÃO ATUAL (PREVIEW) -->
            <h4 style="color:#666; text-transform:uppercase; font-size:0.8rem; letter-spacing:1px; margin-bottom:10px;">Como aparece para o cliente:</h4>
            
            <?php if($aviso_ativo): ?>
                <div class="preview-box">
                    <div style="background:rgba(255,255,255,0.2); width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                         <span class="material-symbols-rounded" style="font-size:1.8rem;">campaign</span>
                    </div>
                    <div>
                        <h4 style="margin:0 0 5px 0; font-size:1.1rem; font-weight:700;">Comunicado Importante</h4>
                        <p style="margin:0; font-size:1rem; line-height:1.4; opacity:0.95;"><?= nl2br(htmlspecialchars($aviso_ativo['mensagem'])) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding:30px; border:2px dashed #ddd; border-radius:12px; text-align:center; color:#999; margin-bottom:30px;">
                    <span class="material-symbols-rounded" style="font-size:2rem; display:block; margin-bottom:10px;">unpublished</span>
                    Nenhum aviso ativo no momento. O painel dos clientes está padrão.
                </div>
            <?php endif; ?>


            <!-- FORMULÁRIO -->
            <div class="form-card">
                <h3>📢 Publicar / Atualizar Aviso</h3>
                <p style="color:#666; font-size:0.9rem; margin-bottom:20px;">Use este espaço para comunicados gerais como "Recesso de Fim de Ano", "Mudança de Endereço" ou avisos sobre sistemas.</p>
                
                <form method="POST">
                    <textarea name="mensagem_aviso" rows="4" placeholder="Digite sua mensagem aqui..." style="width:100%; padding:15px; border:1px solid #ddd; border-radius:8px; font-family:inherit; font-size:1rem; margin-bottom:15px; resize:vertical;"><?= $aviso_ativo ? htmlspecialchars($aviso_ativo['mensagem']) : '' ?></textarea>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:0.85rem; color:#888;">* Ao publicar, o aviso anterior é automaticamente substituído.</span>
                        <div style="display:flex; gap:10px;">
                            <?php if($aviso_ativo): ?>
                                <button type="submit" name="btn_salvar_aviso" value="limpar" class="btn-save" style="background:#dc3545; border:none;">Remover Aviso</button>
                            <?php endif; ?>
                            <button type="submit" name="btn_salvar_aviso" class="btn-save">Publicar Aviso</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- HISTÓRICO -->
            <h3 style="margin-top:40px; color:#444;">📜 Histórico Recente</h3>
            <ul class="history-list">
                <?php foreach($historico as $h): 
                    $isActive = ($h['ativo'] == 1);
                ?>
                    <li class="history-item" style="<?= $isActive ? 'border-left:4px solid #198754;' : '' ?>">
                        <div style="flex:1;">
                            <div style="font-size:0.85rem; color:#999; margin-bottom:4px;">
                                <?= date('d/m/Y H:i', strtotime($h['data_criacao'])) ?>
                                <?php if($isActive): ?>
                                    <span class="tag-status status-active" style="margin-left:8px;">Ativo Agora</span>
                                <?php else: ?>
                                    <span class="tag-status status-inactive" style="margin-left:8px;">Inativo</span>
                                <?php endif; ?>
                            </div>
                            <div style="color:#555;"><?= htmlspecialchars($h['mensagem']) ?></div>
                        </div>
                        <?php if(!$isActive): ?>
                            <a href="?reativar=<?= $h['id'] ?>" style="margin-left:20px; text-decoration:none; color:var(--color-primary); font-size:0.85rem; font-weight:600; padding:6px 12px; border:1px solid var(--color-primary); border-radius:6px; transition:0.2s;" onmouseover="this.style.background='var(--color-primary)';this.style.color='white'" onmouseout="this.style.background='white';this.style.color='var(--color-primary)'">
                                Reutilizar
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div>
    </main>
</div>

</body>
</html>
