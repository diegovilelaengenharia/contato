<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require_once '../db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        echo "<div style='background:red; color:white; padding:20px; font-weight:bold; z-index:99999; position:relative;'>FATAL ERROR CHECKLIST: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "</div>";
        die();
    }
});

// VERIFICAR LOGIN
if (!isset($_SESSION['cliente_id'])) {
    header("Location: ../index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// BUSCAR DADOS DO CLIENTE (Necessário para o Header/Avatar)
$stmt_cli = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt_cli->execute([$cliente_id]);
$cliente = $stmt_cli->fetch(PDO::FETCH_ASSOC);

// BUSCAR DETALHES DO PROCESSO
$stmt_det = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmt_det->execute([$cliente_id]);
$detalhes = $stmt_det->fetch(PDO::FETCH_ASSOC);

// --- LOGICA DE UPLOAD (Idêntica a pendencias.php) ---
if(isset($_FILES['arquivo_doc']) && isset($_POST['doc_chave'])) {
    $doc_chave = $_POST['doc_chave'];
    $file = $_FILES['arquivo_doc'];
    
    if($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Permitir tudo (exceto executáveis perigosos se quiser filtrar, mas user pediu tudo)
        // Directory: uploads/cliente_{id}/docs/ 
        // OBS: Caminho relativo a este arquivo (client-app) é ../uploads...
        $upload_dir_rel = "../uploads/cliente_{$cliente_id}/docs/";
        // Mas para DB queremos salvar relativo a root da area-cliente ou absoluto?
        // O padrão usado no projeto parece ser relativo a area-cliente.
        // O arquivo pendencias usa __DIR__ . '/uploads'... vamos seguir o padrão local.
        
        $upload_dir_abs = __DIR__ . "/../uploads/cliente_{$cliente_id}/docs/";
        
        if(!is_dir($upload_dir_abs)) mkdir($upload_dir_abs, 0755, true);
        
        // Name: CHAVE_TIMESTAMP.ext
        $new_name = "{$doc_chave}_" . time() . ".{$ext}";
        $target_path = $upload_dir_abs . $new_name;
        
        // Path para salvar no banco (Relativo a area-cliente)
        $db_path = "uploads/cliente_{$cliente_id}/docs/" . $new_name;

        if(move_uploaded_file($file['tmp_name'], $target_path)) {
            // Update DB
            // Check existing
            $stmt = $pdo->prepare("SELECT id FROM processo_docs_entregues WHERE cliente_id = ? AND doc_chave = ?");
            $stmt->execute([$cliente_id, $doc_chave]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Se já existe, atualiza arquivo e volta status para analise (caso fosse rejeitado)
                $update = $pdo->prepare("UPDATE processo_docs_entregues SET arquivo_path = ?, nome_original = ?, data_entrega = NOW(), status = 'em_analise' WHERE id = ?");
                $update->execute([$db_path, $file['name'], $existing['id']]);
            } else {
                $insert = $pdo->prepare("INSERT INTO processo_docs_entregues (cliente_id, doc_chave, arquivo_path, nome_original, data_entrega, status) VALUES (?, ?, ?, ?, NOW(), 'em_analise')");
                $insert->execute([$cliente_id, $doc_chave, $db_path, $file['name']]);
            }
            
            // Redirect to self to prevent resubmit
            header("Location: documentos_iniciais.php?msg=success");
            exit;
        } else {
            $error_msg = "Falha ao mover arquivo.";
        }
    } else {
        $error_msg = "Erro no upload: " . $file['error'];
    }
}
// --- FIM LOGICA UPLOAD ---

// FORCE SCHEMA UPDATE
require_once '../includes/schema.php';

// LOAD CONFIG
$docs_config = require '../config/docs_config.php';
$processos = $docs_config['processes'];
$todos_docs = $docs_config['document_registry'];

// Identificar Processo do Cliente
$tipo_chave = ($detalhes && isset($detalhes['tipo_processo_chave'])) ? $detalhes['tipo_processo_chave'] : '';
$proc_data = $processos[$tipo_chave] ?? null;

// Buscar Status de Entrega (Mapeado por chave)
$stmt_entregues = $pdo->prepare("SELECT doc_chave, arquivo_path, nome_original, data_entrega FROM processo_docs_entregues WHERE cliente_id = ?");
$stmt_entregues->execute([$cliente_id]);
$entregues_raw = $stmt_entregues->fetchAll(PDO::FETCH_ASSOC);

// Transform in Associative Array: [ 'chave' => {data} ]
$entregues = [];
foreach($entregues_raw as $row) {
    if(isset($row['doc_chave'])) $entregues[$row['doc_chave']] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Iniciais</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=4.2">
    <link rel="stylesheet" href="css/header-premium.css?v=<?= time() ?>">
    <!-- Toastify -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <style>
        /* FORCE SOCIAL UPDATE v2 */
        .floating-buttons { position: fixed; bottom: 25px; right: 25px; display: flex; flex-direction: column; gap: 16px; z-index: 99999 !important; }
        .floating-btn { width: 56px; height: 56px; border-radius: 50%; display: grid; place-items: center; background: var(--btn-bg); color: #ffffff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 8px 24px rgba(0, 0, 0, 0.1); transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease; text-decoration: none; position: relative; border: none !important; }
        .floating-btn svg { width: 28px; height: 28px; fill: currentColor; }
        .floating-btn--whatsapp { --btn-bg: #25d366; }
        .floating-btn--whatsapp:hover { background: #20bd5a; box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4); }
        .floating-btn--instagram { --btn-bg: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .floating-btn--instagram:hover { box-shadow: 0 6px 16px rgba(220, 39, 67, 0.4); }
        .floating-btn:hover { transform: scale(1.1) rotate(-4deg); }
        .floating-btn:active { transform: scale(0.95); }

        /* Override basic settings for full page view */
        body { background: #f4f6f8; }
        /* HEADER MODULE STYLE (TEAL - DOCS INICIAIS) */
        /* HEADER MODULE STYLE (VILELA PREMIUM) */
        .page-header {
            background: linear-gradient(135deg, #146C43 0%, #0d462b 100%); /* Vilela Dark Green Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(20, 108, 67, 0.25); 
            margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
            color: #ffffff; /* White Text */
            position: relative;
            overflow: hidden;
            border: none;
        }
        
        /* Decorative Circle (Subtle) */
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.4);
            border-radius: 50%; pointer-events: none;
        }

        .btn-back {
            text-decoration: none; color: #146C43; font-weight: 600; 
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; 
            background: white; 
            border-radius: 25px;
            transition: 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: none;
        }
        .btn-back:hover { background: #e6fffa; transform: translateX(-3px); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #ffffff; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.9; font-weight: 400; margin-top: 2px; color: #e9ecef; }

        .doc-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.2s;
        }
        .doc-card.entregue {
            border-left: 5px solid #198754;
            background: #f8fff9;
        }
        .doc-card.pendente {
            border-left: 5px solid #dc3545;
        }
        .doc-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 1.2rem;
        }
        .doc-info { flex: 1; }
        .doc-title { font-weight: 600; color: #333; font-size: 0.95rem; line-height: 1.3; }
        .doc-status { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-top: 3px; display: inline-block; padding: 2px 8px; border-radius: 8px; }
        
        .status-ok { color: #198754; background: #d1e7dd; }
        .status-pend { color: #dc3545; background: #f8d7da; }

        @keyframes docWiggle {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            50% { transform: rotate(10deg); }
            75% { transform: rotate(-5deg); }
            100% { transform: rotate(0deg); }
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER NOVA IDENTIDADE VISUAL -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Documentos Iniciais</span>
                    <span class="header-title-sub">Checklist do Processo</span>
                 </div>
                 
                 <!-- Animated Icon -->
                 <div style="background: rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); color: #ffffff; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; backdrop-filter: blur(5px); animation: docWiggle 4s ease-in-out infinite;">
                    📋
                 </div>
            </div>
        </div>

        <?php if($proc_data): ?>
            
            <!-- UNIFIED INFO HEADER -->
            <style>
                .unified-info-box {
                    background: linear-gradient(135deg, #fffcf5 0%, #ffffff 100%);
                    border: 1px solid #146C43; /* Full Green Border */
                    border-radius: 16px;
                    padding: 0;
                    margin-bottom: 30px;
                    box-shadow: 0 4px 12px rgba(20, 108, 67, 0.1);
                    overflow: hidden;
                }
                .uib-main {
                    padding: 20px;
                    display: flex; align-items: center; gap: 15px;
                }
                /* Typography */
                .uib-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; margin-bottom: 2px; }
                .uib-title { font-size: 1.3rem; font-weight: 700; color: #146C43; margin: 0; }
                
                .uib-icon { 
                    width: 45px; height: 45px; background: white; border-radius: 50%; 
                    display: flex; align-items: center; justify-content: center; 
                    font-size: 1.4rem; color: #146C43; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                    border: 1px solid #e7f1ff;
                }
            </style>

            <div class="unified-info-box">
                <!-- Top Section: Processo -->
                <div class="uib-main">
                    <div class="uib-icon">🏗️</div>
                    <div>
                        <div class="uib-label">PROCESSO IDENTIFICADO</div>
                        <h2 class="uib-title"><?= htmlspecialchars($proc_data['titulo']) ?></h2>
                    </div>
                </div>
            </div>

            <?php if($proc_data): 
                // Merge obligatory and exceptional for display or keep separate? 
                // Previous design kept them separate. Let's keep separate loops but use same internal logic.
            ?>

            <h3 style="font-size: 1rem; color: #555; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px;">Documentos Obrigatórios</h3>
            
            <?php foreach($proc_data['docs_obrigatorios'] as $d_key): 
                    $label = $todos_docs[$d_key] ?? $d_key;
                    $info = $entregues[$d_key] ?? null; 
                    
                    // Status Calculation
                    $status = $info['status'] ?? 'pendente'; 
                    // Fallback logic
                    if($status == 'pendente' && !empty($info['arquivo_path'])) $status = 'em_analise';
                    
                    // Visual Props
                    $icon = 'priority_high'; $status_text = 'Pendente'; $status_color = '#dc3545'; $bg_color = '#fff';
                    
                    if($status == 'em_analise') {
                        $icon = 'hourglass_top'; $status_text = 'Em Análise'; $status_color = '#ffc107'; $bg_color = '#fffbf0';
                    } elseif($status == 'aprovado') {
                        $icon = 'check_circle'; $status_text = 'Aprovado'; $status_color = '#198754'; $bg_color = '#f8fff9';
                    } elseif($status == 'rejeitado') {
                        $icon = 'error'; $status_text = 'Rejeitado / Corrigir'; $status_color = '#dc3545'; $bg_color = '#fff5f5';
                    }
                    
                    // Safe label for JS
                    $js_label = addslashes($label);
            ?>
                <div class="doc-card" style="border-left: 5px solid <?= $status_color ?>; background: <?= $bg_color ?>;">
                    <div class="doc-icon" style="background: <?= $status_color ?>; color: <?= ($status=='em_analise') ? '#555' : 'white' ?>;">
                        <span class="material-symbols-rounded"><?= $icon ?></span>
                    </div>
                    <div class="doc-info">
                        <div class="doc-title" style="margin-bottom: 4px;"><?= htmlspecialchars($label) ?></div>
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                            <span class="doc-status" style="background: <?= $status_color ?>20; color: <?= ($status=='em_analise') ? '#856404' : $status_color ?>;">
                                <?= $status_text ?>
                            </span>

                            <?php if(!empty($info['nome_original'])): ?>
                                <span style="font-size: 0.75rem; color: #666; display: flex; align-items: center; gap: 3px;">
                                    <span class="material-symbols-rounded" style="font-size: 0.9rem;">description</span>
                                    <?= htmlspecialchars($info['nome_original']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                    <?php if($status == 'pendente' || $status == 'rejeitado'): ?>
                            <!-- Upload Trigger -->
                            <button type="button" class="btn-anexar" onclick="openUploadModal('<?= $d_key ?>', '<?= $js_label ?>')" style="cursor:pointer; display:flex; align-items:center; gap:5px; padding:6px 14px; background:#0d6efd; color:white; border-radius:20px; font-size:0.8rem; font-weight:600; border:none; transition:0.2s; white-space: nowrap; box-shadow: 0 2px 5px rgba(13, 110, 253, 0.2);">
                            <span class="material-symbols-rounded" style="font-size:1.1rem;">cloud_upload</span> 
                            <span style="display:none; @media(min-width:400px){display:inline;}">Anexar</span>
                        </button>
                    <?php elseif($status == 'em_analise'): ?>
                            <!-- Re-Upload Trigger (EDIT) -->
                            <button type="button" class="btn-anexar" onclick="openUploadModal('<?= $d_key ?>', '<?= $js_label ?>')" style="cursor:pointer; display:flex; align-items:center; gap:5px; padding:6px 12px; background:#ffc107; color:#333; border-radius:20px; font-size:0.75rem; font-weight:600; border:none; transition:0.2s; white-space: nowrap;">
                            <span class="material-symbols-rounded" style="font-size:1rem;">edit</span> 
                            <span style="display:none; @media(min-width:400px){display:inline;}">Alterar</span>
                        </button>
                    <?php elseif($status == 'aprovado'): ?>
                        <div style="color: #198754; font-weight: bold; font-size: 1.2rem;">OK</div>
                    <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php // Handle docs_excepcionais (Optional loop handling or remove if empty check needed inside)
            if(!empty($proc_data['docs_excepcionais'])): ?>
                <h3 style="font-size: 1rem; color: #555; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px;">Documentos Excepcionais</h3>
                <?php foreach($proc_data['docs_excepcionais'] as $d_key): 
                        // Reuse same logic (simplified copy-paste for safety)
                        $label = $todos_docs[$d_key] ?? $d_key;
                        $info = $entregues[$d_key] ?? null;
                        $status = $info['status'] ?? 'pendente';
                        if($status == 'pendente' && !empty($info['arquivo_path'])) $status = 'em_analise';
                        
                        // Visuals
                        if($status == 'em_analise') { $icon='hourglass_top'; $status_color='#ffc107'; $bg_color='#fffbf0'; $status_text='Em Análise'; }
                        elseif($status == 'aprovado') { $icon='check_circle'; $status_color='#198754'; $bg_color='#f8fff9'; $status_text='Aprovado'; }
                        elseif($status == 'rejeitado') { $icon='error'; $status_color='#dc3545'; $bg_color='#fff5f5'; $status_text='Rejeitado'; }
                        else { $icon='priority_high'; $status_color='#dc3545'; $bg_color='#fff'; $status_text='Pendente'; } // Excepcionais might differ in default color? Keep consistent.
                        
                        $js_label = addslashes($label);
                ?>
                <div class="doc-card" style="border-left: 5px solid <?= $status_color ?>; background: <?= $bg_color ?>;">
                     <div class="doc-icon" style="background: <?= $status_color ?>; color: <?= ($status=='em_analise') ? '#555' : 'white' ?>;"><span class="material-symbols-rounded"><?= $icon ?></span></div>
                     <div class="doc-info">
                        <div class="doc-title"><?= htmlspecialchars($label) ?></div>
                        <span class="doc-status" style="background:<?= $status_color ?>20; color:<?= ($status=='em_analise')?'#856404':$status_color ?>;"><?= $status_text ?></span>
                        <?php if(!empty($info['nome_original'])): ?><div style="font-size:0.75rem; color:#666;">📎 <?= htmlspecialchars($info['nome_original']) ?></div><?php endif; ?>
                     </div>
                     <div>
                        <?php if($status != 'aprovado'): ?>
                        <button type="button" class="btn-anexar" onclick="openUploadModal('<?= $d_key ?>', '<?= $js_label ?>')" style="cursor:pointer; display:flex; align-items:center; gap:5px; padding:6px 12px; background:#0d6efd; color:white; border-radius:20px; font-size:0.75rem;"><span class="material-symbols-rounded">cloud_upload</span></button>
                        <?php endif; ?>
                     </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 50px 20px; color: #888;">
                <div style="font-size: 3rem; margin-bottom: 15px;">📋</div>
                <h3 style="color: #666;">Ainda não definido</h3>
                <p>O tipo do seu processo ainda está sendo analisado pela nossa equipe. Em breve a lista de documentos aparecerá aqui.</p>
            </div>
        <?php endif; ?>

        <!-- FLOATING SOCIAL BUTTONS -->
        <div class="floating-buttons" style="z-index: 99999;">
            <a href="https://wa.me/5535984529577?text=Ola%20Diego%20Vilela" class="floating-btn floating-btn--whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
                <svg viewBox="0 0 24 24" role="presentation"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
            </a>
            <a href="https://www.instagram.com/diegovilela.eng/" class="floating-btn floating-btn--instagram" target="_blank" rel="noopener" aria-label="Instagram">
                <svg viewBox="0 0 24 24" role="presentation"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 5A1.5 1.5 0 1 0 10.5 12 1.5 1.5 0 0 0 12 13.5zm4.25-6.75a1 1 0 1 1-1-1 1 1 0 0 1 1 1z"></path></svg>
            </a>
        </div>

        <!-- FOOTER -->
        <?php include 'includes/footer.php'; ?>

    </div>

    <!-- UPLOAD MODAL -->
    <div id="uploadModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(5px);">
        <div class="modal-content" style="background-color:#fff; margin:10% auto; padding:0; border:none; width:90%; max-width:500px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.2); animation:slideDown 0.3s ease-out;">
            
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); padding:20px; border-radius:15px 15px 0 0; display:flex; justify-content:space-between; align-items:center;">
                <h5 style="margin:0; color:white; font-size:1.2rem; display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-rounded">cloud_upload</span> Anexar Documento
                </h5>
                <span onclick="closeUploadModal()" style="color:white; font-size:28px; font-weight:bold; cursor:pointer; line-height:1;">&times;</span>
            </div>

            <div class="modal-body" style="padding:25px;">
                <p id="modalDocTitle" style="margin-top:0; color:#555; font-weight:600; font-size:1.1rem; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;"></p>
                
                <form id="uploadModalForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="doc_chave" id="modalDocChave">
                    
                    <div class="upload-area" style="border:2px dashed #ccc; border-radius:10px; padding:30px; text-align:center; cursor:pointer; transition:all 0.3s;" onclick="document.getElementById('modalFileInput').click()">
                        <span class="material-symbols-rounded" style="font-size:48px; color:#aaa; margin-bottom:10px; display:block;">folder_open</span>
                        <p style="margin:0; color:#666;">Clique para selecionar um arquivo ou arraste aqui</p>
                        <p style="font-size:0.8rem; color:#999; margin-top:5px;">(PDF, JPG, PNG)</p>
                    </div>
                    <input type="file" name="arquivo_doc" id="modalFileInput" style="display:none;" onchange="updateFileName(this)">
                    <p id="selectedFileName" style="text-align:center; margin-top:15px; font-weight:600; color:#0d6efd; display:none;"></p>

                    <div style="margin-top:25px; display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <button type="button" onclick="closeUploadModal()" style="padding:12px; border:1px solid #ddd; background:white; color:#555; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                        <button type="submit" style="padding:12px; border:none; background:#0d6efd; color:white; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <span class="material-symbols-rounded" style="font-size:20px;">send</span> Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideDown {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
    </style>

    <script>
    // Modal Logic
    function openUploadModal(docChave, docTitle) {
        document.getElementById('modalDocChave').value = docChave;
        document.getElementById('modalDocTitle').innerText = docTitle;
        document.getElementById('selectedFileName').style.display = 'none';
        document.getElementById('selectedFileName').innerText = '';
        document.getElementById('modalFileInput').value = '';
        document.getElementById('uploadModal').style.display = 'block';
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    function updateFileName(input) {
        if(input.files && input.files[0]) {
            const nameEl = document.getElementById('selectedFileName');
            nameEl.innerText = input.files[0].name;
            nameEl.style.display = 'block';
        }
    }

    // Close on click outside
    window.onclick = function(event) {
        const modal = document.getElementById('uploadModal');
        if (event.target == modal) {
            closeUploadModal();
        }
    }
    
    // Check URL for success/error
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'success') {
        Toastify({ text: "✅ Documento enviado com sucesso!", backgroundColor: "#198754", duration: 3000 }).showToast();
        // Clean URL
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path:newUrl},'',newUrl);
    }
    </script>
    
    <?php endif; ?>
    
</body>
</html>
