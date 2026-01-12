<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../db.php';

// VERIFICAR LOGIN
if (!isset($_SESSION['cliente_id'])) {
    header("Location: ../index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// BUSCAR DADOS DO CLIENTE
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// BUSCAR DADOS DO CHECKLIST
$stmt_docs = $pdo->prepare("SELECT * FROM processo_docs_iniciais WHERE cliente_id = ?");
$stmt_docs->execute([$cliente_id]);
$dados_docs = $stmt_docs->fetch(PDO::FETCH_ASSOC);

// LOAD CONFIG
$docs_config = require '../area-cliente/config/docs_config.php'; // Path check: client-app/ is execution root relative? No, client-app/documentos_iniciais.php. Config is in area-cliente/config/
// Wait, file path structure:
// area-cliente/
//   client-app/
//     documentos_iniciais.php
//   config/
//     docs_config.php
// Relative path from client-app/documentos_iniciais.php to config/docs_config.php is ../config/docs_config.php
$docs_config = require '../config/docs_config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Iniciais</title>
    
    <!-- FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <link rel="stylesheet" href="css/style.css?v=3.0">
    
    <style>
        body { background: #f4f6f8; font-family: 'Outfit', sans-serif; }
        
        /* HEADER STYLE */
        .page-header {
            display:flex; align-items:center; justify-content:space-between; 
            margin-bottom: 30px; padding: 25px 30px; border-radius: 30px; 
            background: linear-gradient(135deg, #e3f2fd 0%, #f1f8ff 100%); 
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.1); 
            border: 1px solid #d1e7dd;
        }

        .doc-card {
            background: white; border-radius: 12px; padding: 15px 20px;
            margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;
            border: 1px solid #edf2f7; box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .doc-card.checked { border-left: 5px solid #198754; }
        .doc-card.missing { border-left: 5px solid #e9ecef; opacity: 0.8; }
        
        .status-icon {
            width: 30px; height: 30px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 30px; padding: 25px 30px; border-radius: 30px; background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%); box-shadow: 0 10px 30px rgba(59, 130, 246, 0.1); border: 1px solid #bfdbfe;">
            <!-- Left: Back Button -->
            <a href="index.php" style="text-decoration:none; color:#1e40af; font-weight:600; display:flex; align-items:center; gap:5px; padding:10px 20px; background:white; border-radius:25px; border:1px solid #dbeafe; box-shadow:0 2px 5px rgba(0,0,0,0.05); font-size: 0.95rem;">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="display:flex; flex-direction:column; align-items:flex-end; text-align:right;">
                    <h1 style="margin:0; font-size:1.4rem; color:#1e3a8a; font-weight:700; letter-spacing:-0.5px;">Documentos Iniciais</h1>
                    <span style="display:block; font-size:0.8rem; color:#2563eb; font-weight:500; margin-top:2px; opacity:0.9;">Checklist de Entrada</span>
                </div>
                
                <!-- Icon Box -->
                <div style="background: white; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15); border: 1px solid #dbeafe; color: #2563eb;">
                    📂
                </div>
            </div>
        </div>

        <?php if (!$dados_docs || empty($dados_docs['tipo_processo'])): ?>
            <!-- EMPTY STATE -->
            <div style="text-align:center; padding:50px 20px; background:white; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
                <span style="font-size:3rem;">🏗️</span>
                <h2 style="color:#555; margin-top:15px; font-size:1.2rem;">Aguardando Definição</h2>
                <p style="color:#888; max-width:400px; margin:10px auto; line-height:1.5;">
                    O engenheiro ainda não definiu o checklist de documentos para o seu processo.
                </p>
                <a href="index.php" style="display:inline-block; margin-top:10px; color:#0d6efd; font-weight:600; text-decoration:none;">Voltar ao Início</a>
            </div>
        <?php else: ?>
            
            <?php 
                $tipo = $dados_docs['tipo_processo'];
                $proc_def = $docs_config['processes'][$tipo] ?? null;
                $checked = json_decode($dados_docs['docs_entregues'], true) ?: [];
                $obs = $dados_docs['observacoes'];
                
                if($proc_def):
            ?>
                <!-- INFO PROCESSO -->
                <div style="margin-bottom:25px; padding:0 10px;">
                    <span style="text-transform:uppercase; font-size:0.75rem; color:#666; font-weight:700; letter-spacing:1px;">Tipo de Processo</span>
                    <h2 style="margin:5px 0 0 0; color:#333; font-size:1.4rem;"><?= htmlspecialchars($proc_def['titulo']) ?></h2>
                    
                    <?php if(!empty($obs)): ?>
                        <div style="margin-top:15px; background:#fff3cd; color:#856404; padding:15px; border-radius:12px; border:1px solid #ffeeba; font-size:0.95rem; line-height:1.5;">
                            <strong>📝 Observação do Engenheiro:</strong><br>
                            <?= nl2br(htmlspecialchars($obs)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- OBRIGATÓRIOS -->
                <h3 style="margin-left:10px; font-size:1rem; color:#444; margin-bottom:15px;">📌 Obrigatórios</h3>
                <?php foreach($proc_def['docs_obrigatorios'] as $docId): 
                    $is_checked = in_array($docId, $checked);
                ?>
                    <div class="doc-card <?= $is_checked ? 'checked' : 'missing' ?>">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <div class="status-icon" style="background: <?= $is_checked ? '#d1e7dd' : '#f8f9fa' ?>; color: <?= $is_checked ? '#198754' : '#ccc' ?>;">
                                <?= $is_checked ? '✅' : '⬜' ?>
                            </div>
                            <div>
                                <div style="font-weight:600; color:#333; font-size:0.95rem;"><?= $docs_config['document_registry'][$docId] ?></div>
                                <div style="font-size:0.8rem; color: <?= $is_checked ? '#198754' : '#999' ?>; margin-top:2px;">
                                    <?= $is_checked ? 'Recebido' : 'Pendente' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- EXCEPCIONAIS -->
                <?php if(!empty($proc_def['docs_excepcionais'])): ?>
                    <h3 style="margin-left:10px; font-size:1rem; color:#444; margin-top:30px; margin-bottom:15px;">⚠️ Complementares / Excepcionais</h3>
                    <?php foreach($proc_def['docs_excepcionais'] as $docId): 
                        $is_checked = in_array($docId, $checked);
                    ?>
                        <div class="doc-card <?= $is_checked ? 'checked' : 'missing' ?>">
                             <div style="display:flex; align-items:center; gap:15px;">
                                <div class="status-icon" style="background: <?= $is_checked ? '#d1e7dd' : '#f8f9fa' ?>; color: <?= $is_checked ? '#198754' : '#ccc' ?>;">
                                    <?= $is_checked ? '✅' : '⬜' ?>
                                </div>
                                <div>
                                    <div style="font-weight:600; color:#333; font-size:0.95rem;"><?= $docs_config['document_registry'][$docId] ?></div>
                                    <div style="font-size:0.8rem; color: <?= $is_checked ? '#198754' : '#999' ?>; margin-top:2px;">
                                        <?= $is_checked ? 'Recebido' : 'Pendente (Se aplicável)' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <p>Erro na configuração do processo.</p>
            <?php endif; ?>

        <?php endif; ?>
        
        <div style="height:50px;"></div>
    </div>

</body>
</html>
