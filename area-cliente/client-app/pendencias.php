<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require_once '../db.php';

// 1. AUTHENTICATION
if (!isset($_SESSION['cliente_id'])) {
    header("Location: ../index.php");
    exit;
}
$cliente_id = $_SESSION['cliente_id'];

// 2. LOGIC: HANDLE UPLOAD & DELETION

// A) EXCLUSÃO DE ARQUIVO (CLIENTE)
if(isset($_POST['delete_file']) && isset($_POST['file_name']) && isset($_POST['pendencia_id'])) {
    $f_del = basename($_POST['file_name']); // Security: basename
    $p_id_del = $_POST['pendencia_id'];
    
    // Check if filename starts with ID (Security)
    if(strpos($f_del, $p_id_del . '_') === 0) {
        $path_del = __DIR__ . '/uploads/pendencias/' . $f_del;
        if(file_exists($path_del)) {
            unlink($path_del);
            $msg_success = "Arquivo removido com sucesso.";
        } else {
             $msg_error = "Arquivo não encontrado.";
        }
    } else {
        $msg_error = "Permissão negada para excluir este arquivo.";
    }
}

// B) UPLOAD
if(isset($_FILES['arquivo_pendencia']) && isset($_POST['pendencia_id'])) {
    $pid = $_POST['pendencia_id'];
    $file = $_FILES['arquivo_pendencia'];
    
    if($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip'];
        
        if(in_array($ext, $allowed)) {
             // Create Dir
             $dir = __DIR__ . '/uploads/pendencias/';
             if(!is_dir($dir)) mkdir($dir, 0755, true);
             
             // Name: ID_TIMESTAMP.ext
             $new_name = $pid . '_' . time() . '.' . $ext;
             
             if(move_uploaded_file($file['tmp_name'], $dir . $new_name)) {
                 // Tenta atualizar status para 'em_analise' apenas visualmente ou DB se possível
                 try {
                    $sql = "UPDATE processo_pendencias SET status='em_analise' WHERE id=? AND cliente_id=? AND status != 'resolvido'";
                    $stmtUpdate = $pdo->prepare($sql);
                    $stmtUpdate->execute([$pid, $cliente_id]);
                    $msg_success = "Arquivo enviado! Pendência em análise.";
                 } catch(PDOException $e) {
                     $msg_success = "Arquivo enviado com sucesso!";
                 }
             } else {
                 $msg_error = "Erro ao mover arquivo para pasta de uploads.";
             }
        } else {
            $msg_error = "Formato inválido.";
        }
    }
}

// 3. FETCH PENDENCIES
$stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY data_criacao DESC");
$stmt_pend->execute([$cliente_id]);
$all_pendencias = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);

// SEPARATE LISTS
$resolvidas = [];
$abertas = [];

foreach($all_pendencias as $p) {
    if($p['status'] == 'resolvido') {
        $resolvidas[] = $p;
    } else {
        $abertas[] = $p;
    }
}

function getWhatsappLink($pendency_title) {
    $text = "Olá, estou entrando em contato sobre a pendência: *" . strip_tags($pendency_title) . "*.";
    return "https://wa.me/5535984529577?text=" . urlencode($text);
}

// Helper para buscar arquivos
function get_pendency_files($p_id) {
    $upload_dir = __DIR__ . '/uploads/pendencias/';
    $web_path = 'uploads/pendencias/';
    $anexos = [];
    if(is_dir($upload_dir)) {
        $files = glob($upload_dir . $p_id . "_*.*");
        if($files) {
            foreach($files as $f) {
                $filename = basename($f);
                $anexos[] = [
                    'name' => $filename,
                    'path' => $web_path . $filename,
                    'date' => filemtime($f)
                ];
            }
        }
    }
    return $anexos;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendências</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    
    <style>
        body { background: #f4f6f8; }
        
        /* HEADER - RED THEME (Premium) */
        .page-header {
            background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%); /* Light Red Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.15); 
            margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
            color: #842029; /* Dark Red Text */
            position: relative;
            overflow: hidden;
            border: 1px solid #f5c2c7;
        }
        
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.4);
            border-radius: 50%; pointer-events: none;
        }

        .btn-back {
            text-decoration: none; color: #842029; font-weight: 600; 
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; 
            background: white; 
            border-radius: 25px;
            transition: 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #f5c2c7;
        }
        .btn-back:hover { background: #fff5f5; transform: translateX(-3px); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #58151c; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.8; font-weight: 500; margin-top: 2px; color: #842029; }

        .status-badge {
            padding: 4px 10px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase;
        }

        .btn-action-text {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px;
            border-radius: 12px;
            font-weight: 600; font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .btn-action-text:active { transform: scale(0.98); }

        .empty-state {
            text-align: center; padding: 40px; color: #999;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #555;
            margin: 30px 0 15px 0;
            display: flex; align-items: center; gap: 8px;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Pendências</span>
                    <span class="header-title-sub">Ações Necessárias</span>
                 </div>
                 
                 <!-- Icon -->
                 <div style="background: white; border:1px solid #f5c2c7; color: #dc3545; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.1);">
                    ⚠️
                 </div>
            </div>
        </div>

        <?php if(isset($msg_success)): ?>
            <div style="background:#d1e7dd; color:#0f5132; padding:15px; border-radius:12px; margin-bottom:20px; font-size:0.9rem; border: 1px solid #badbcc;">
                ✅ <?= $msg_success ?>
            </div>
        <?php endif; ?>

        <?php if(isset($msg_error)): ?>
            <div style="background:#f8d7da; color:#842029; padding:15px; border-radius:12px; margin-bottom:20px; font-size:0.9rem; border: 1px solid #f5c2c7;">
                ❌ <?= $msg_error ?>
            </div>
        <?php endif; ?>

        <!-- CONTENT -->
        <?php if(empty($all_pendencias)): ?>
            <div class="empty-state">
                <span style="font-size:2rem; display:block; margin-bottom:10px;">🎉</span>
                <h3 style="color:#333; margin:0;">Tudo Certo!</h3>
                <div style="font-size:0.9rem; margin-top:5px;">Nenhuma pendência encontrada.</div>
            </div>
        <?php else: ?>
            
            <div style="display: flex; flex-direction: column; gap: 10px; padding-bottom: 20px;">

                <!-- 1. HISTÓRICO DE RESOLUÇÕES (TOPO) -->
                <?php if(count($resolvidas) > 0): ?>
                    <h3 class="section-title">
                        <span class="material-symbols-rounded" style="color:#198754;">history</span> Histórico de Resoluções
                    </h3>
                    
                    <?php foreach($resolvidas as $p): 
                         $data_criacao = date('d/m/Y', strtotime($p['data_criacao']));
                         $anexos = get_pendency_files($p['id']);
                         // Resolvidos tem estilo mais compacto/suave
                    ?>
                    <div style="background: #e8f5e9; border: 1px solid #c3e6cb; border-radius: 12px; padding: 15px; opacity: 0.9;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #198754;">Concluído em: <?= $data_criacao ?></span>
                            <span class="material-symbols-rounded" style="color:#198754; font-size:1.2rem;">check_circle</span>
                        </div>
                        
                        <h4 style="margin: 0 0 5px 0; font-size: 1rem; color: #155724; font-weight: 700;">
                            <?= htmlspecialchars($p['titulo']) ?>
                        </h4>
                        
                        <?php if(!empty($p['descricao'])): ?>
                            <div style="font-size: 0.9rem; color: #155724; line-height: 1.4; opacity: 0.8;">
                                <?= nl2br(htmlspecialchars($p['descricao'])) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Arquivos do Histórico -->
                        <?php if(!empty($anexos)): ?>
                            <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #c3e6cb;">
                                <strong style="font-size:0.75rem; color:#198754;">Arquivos do Processo:</strong>
                                <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:5px;">
                                <?php foreach($anexos as $arq): ?>
                                    <a href="<?= $arq['path'] ?>" target="_blank" style="text-decoration:none; background:white; color:#198754; padding:3px 8px; border-radius:10px; font-size:0.75rem; border:1px solid #c3e6cb;">
                                        📎 Anexo
                                    </a>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>


                <!-- 2. PENDÊNCIAS EM ABERTO (EMBAIXO) -->
                <?php if(count($abertas) > 0): ?>
                    <h3 class="section-title">
                        <span class="material-symbols-rounded" style="color:#e65100;">warning</span> Pendências em Aberto
                    </h3>

                    <?php foreach($abertas as $p): 
                        $anexos = get_pendency_files($p['id']);
                        $has_attachment = !empty($anexos);
                        $data_criacao = date('d/m/Y', strtotime($p['data_criacao']));
                        
                        // Cores
                        if($has_attachment) {
                             $status_label = "Arquivo Enviado / Em Análise";
                             $bg_badge = "#0d6efd"; $bg_card = "#f0f8ff"; $border_card = "#cce5ff"; $text_title = "#084298";
                        } else {
                             $status_label = "Pendente";
                             $bg_badge = "#ffc107"; $bg_card = "#fff9d6"; $border_card = "#ffeeba"; $text_title = "#856404";
                        }
                    ?>
                    <div style="background: <?= $bg_card ?>; border: 1px solid <?= $border_card ?>; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        
                        <!-- Header do Card -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <span style="display: block; font-size: 0.8rem; font-weight: 700; color: #555; margin-bottom: 4px; opacity: 0.7;">
                                    📅 <?= $data_criacao ?>
                                </span>
                                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: <?= $text_title ?>; line-height: 1.3;">
                                    <?= htmlspecialchars($p['titulo']) ?>
                                </h3>
                            </div>
                            <span style="background: <?= $bg_badge ?>; color: <?= ($status_label=='Pendente')?'#333':'white' ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                <?= $status_label ?>
                            </span>
                        </div>
    
                        <!-- Descrição -->
                        <?php if(!empty($p['descricao'])): ?>
                            <div style="font-size: 0.95rem; color: #444; margin-bottom: 15px; line-height: 1.5; font-weight: 500;">
                                <?= nl2br(htmlspecialchars($p['descricao'])) ?>
                            </div>
                        <?php endif; ?>
    
                        <!-- Arquivos Enviados (Com Botão Delete) -->
                        <?php if($has_attachment): ?>
                            <div style="margin-bottom: 15px; background: rgba(255,255,255,0.7); padding: 10px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.05);">
                                <strong style="display:block; font-size:0.8rem; margin-bottom:5px; color:#555;">Arquivos Enviados:</strong>
                                <?php foreach($anexos as $arq): ?>
                                    <div style="display:inline-flex; align-items:center; gap:5px; background:white; padding:5px 10px; border-radius:15px; border:1px solid #ddd; margin-right:5px; margin-bottom:5px;">
                                        <a href="<?= $arq['path'] ?>" target="_blank" style="display:flex; align-items:center; gap:5px; color:#0d6efd; text-decoration:none; font-size:0.85rem;">
                                            📎 <?= $arq['name'] ?>
                                        </a>
                                        <!-- Delete Button (Form Inline) -->
                                        <form method="POST" onsubmit="return confirm('Deseja realmente apagar este arquivo?');" style="margin:0; display:flex;">
                                            <input type="hidden" name="delete_file" value="true">
                                            <input type="hidden" name="file_name" value="<?= htmlspecialchars($arq['name']) ?>">
                                            <input type="hidden" name="pendencia_id" value="<?= $p['id'] ?>">
                                            <button type="submit" style="background:none; border:none; cursor:pointer; padding:0; display:flex; color:#dc3545;" title="Apagar Arquivo">
                                                <span class="material-symbols-rounded" style="font-size:1rem;">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                                <div style="font-size:0.75rem; color:#888; margin-top:5px;">*Aguardando análise. Você pode excluir se enviou errado.</div>
                            </div>
                        <?php endif; ?>
    
                        <!-- Ações -->
                        <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                            <!-- Form Upload -->
                            <form action="pendencias.php" method="POST" enctype="multipart/form-data" style="background: rgba(255,255,255,0.6); padding: 15px; border-radius: 12px; border: 1px dashed <?= $text_title ?>; margin-bottom:0;">
                                <input type="hidden" name="pendencia_id" value="<?= $p['id'] ?>">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.85rem; color: #333;">
                                    <?= $has_attachment ? 'Enviar novo arquivo:' : 'Anexar Comprovante/Arquivo:' ?>
                                </label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="file" name="arquivo_pendencia" required style="font-size: 0.85rem; width: 100%; border-radius: 6px; border: 1px solid #ccc; background: #fff; padding:5px;">
                                </div>
                                <button type="submit" name="upload_arquivo" style="margin-top: 10px; width: 100%; padding: 10px; background: #0d6efd; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <span class="material-symbols-rounded">cloud_upload</span> Enviar Arquivo
                                </button>
                            </form>
    
                            <!-- Botão Whatsapp -->
                            <a href="<?= getWhatsappLink($p['titulo']) ?>" target="_blank" class="btn-action-text" style="background: #25D366; color: white; border: 1px solid #badbcc; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <span class="material-symbols-rounded">chat</span>
                                Fale com o Engenheiro
                            </a>
                        </div>
    
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

             <!-- WHATSAPP CTA -->
            <div style="text-align: center; margin-top: 20px; padding-bottom: 20px;">
                 <a href="https://wa.me/5535984529577?text=Ola,%20tenho%20duvidas%20sobre%20as%20pendencias." style="display:inline-block; font-size: 0.85rem; color: #146c43; text-decoration: none; font-weight: 600; padding: 10px 20px; background: #d1e7dd; border-radius: 20px;">
                    Dúvidas sobre as pendências? Fale conosco.
                 </a>
            </div>
            
        <?php endif; ?>

    </div>

</body>
</html>
