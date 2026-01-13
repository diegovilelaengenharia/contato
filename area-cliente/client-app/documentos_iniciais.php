<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require_once '../db.php';

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

// LOAD CONFIG
$docs_config = require '../config/docs_config.php';
$processos = $docs_config['processes'];
$todos_docs = $docs_config['document_registry'];

// Identificar Processo do Cliente
$tipo_chave = ($detalhes && isset($detalhes['tipo_processo_chave'])) ? $detalhes['tipo_processo_chave'] : '';
$proc_data = $processos[$tipo_chave] ?? null;

// Buscar Status de Entrega
$stmt_entregues = $pdo->prepare("SELECT doc_chave FROM processo_docs_entregues WHERE cliente_id = ?");
$stmt_entregues->execute([$cliente_id]);
$entregues = $stmt_entregues->fetchAll(PDO::FETCH_COLUMN);

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
    <link rel="stylesheet" href="css/style.css?v=3.0">
    <link rel="stylesheet" href="css/header-premium.css?v=<?= time() ?>">
    
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
        .page-header {
            background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%); /* Light Teal Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(32, 201, 151, 0.15); 
            margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
            color: #0d5f4c; /* Dark Teal Text */
            position: relative;
            overflow: hidden;
            border: 1px solid #b2f5ea;
        }
        
        /* Decorative Circle (Subtle) */
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.4);
            border-radius: 50%; pointer-events: none;
        }

        .btn-back {
            text-decoration: none; color: #0d5f4c; font-weight: 600; 
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; 
            background: white; 
            border-radius: 25px;
            transition: 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #b2f5ea;
        }
        .btn-back:hover { background: #e6fffa; transform: translateX(-3px); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #083b30; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.8; font-weight: 500; margin-top: 2px; color: #0d5f4c; }

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
                    <span class="header-title-sub">Checklist do Processo (v3.1)</span>
                 </div>
                 
                 <!-- Animated Icon -->
                 <div style="background: white; border:1px solid #b2f5ea; color: #20c997; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05); animation: docWiggle 4s ease-in-out infinite;">
                    📋
                 </div>
            </div>
        </div>

        <?php if($proc_data): ?>
            
            <!-- INFO CARDS (PREMIUM) -->
            <style>
                .info-card-container {
                    display: grid; gap: 20px; margin-bottom: 30px;
                }
                .ic-processo {
                    background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
                    border: 1px solid #bbdefb;
                    border-left: 5px solid #0d6efd;
                    padding: 20px; border-radius: 12px;
                    display: flex; align-items: center; gap: 15px;
                    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.05);
                }
                .ic-icon {
                    width: 45px; height: 45px;
                    background: #fff; border-radius: 50%;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 1.5rem; color: #0d6efd;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                }
                .ic-content h4 { font-size: 0.8rem; text-transform: uppercase; color: #555; letter-spacing: 0.5px; margin: 0; font-weight: 600; }
                .ic-content p { font-size: 1.2rem; font-weight: 700; color: #084298; margin: 3px 0 0 0; }
                
                .ic-obs {
                    background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
                    border: 1px solid #ffecb5;
                    border-left: 5px solid #ffc107;
                    padding: 20px; border-radius: 12px;
                    display: flex; gap: 15px;
                    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.05);
                }
                .ic-obs .ic-icon { color: #856404; }
                .ic-obs h4 { color: #856404; }
                .ic-obs p { font-size: 0.95rem; font-weight: 400; color: #555; line-height: 1.6; font-style: italic; }
            </style>

            <div class="info-card-container">
                <!-- Processo Card -->
                <div class="ic-processo">
                    <div class="ic-icon">🏗️</div>
                    <div class="ic-content">
                        <h4>Processo Identificado</h4>
                        <p><?= htmlspecialchars($proc_data['titulo']) ?></p>
                    </div>
                </div>

                <!-- Obs Card -->
                <?php if(!empty($detalhes['observacoes_gerais'])): ?>
                    <div class="ic-obs">
                        <div class="ic-icon" style="align-self: flex-start;">👷‍♂️</div>
                        <div class="ic-content">
                            <h4>Observação do Engenheiro</h4>
                            <p>“<?= nl2br(htmlspecialchars($detalhes['observacoes_gerais'])) ?>”</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <h3 style="font-size: 1rem; color: #555; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px;">Documentos Obrigatórios</h3>
            
            <?php foreach($proc_data['docs_obrigatorios'] as $d_key): 
                $is_ok = in_array($d_key, $entregues);
            ?>
                <div class="doc-card <?= $is_ok ? 'entregue' : 'pendente' ?>">
                    <div class="doc-icon" style="background: <?= $is_ok ? '#d1e7dd' : '#f8d7da' ?>; color: <?= $is_ok ? '#198754' : '#dc3545' ?>;">
                        <?= $is_ok ? '✓' : '!' ?>
                    </div>
                    <div class="doc-info" style="display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%;">
                        <div>
                            <div class="doc-title"><?= htmlspecialchars($todos_docs[$d_key] ?? $d_key) ?></div>
                            <span class="doc-status <?= $is_ok ? 'status-ok' : 'status-pend' ?>">
                                <?= $is_ok ? 'Recebido' : 'Pendente' ?>
                            </span>
                        </div>
                        
                        <?php if(!$is_ok): ?>
                             <!-- Upload Trigger -->
                             <label class="btn-anexar" style="cursor:pointer; display:flex; align-items:center; gap:5px; padding:6px 12px; background:#0d6efd; color:white; border-radius:20px; font-size:0.75rem; font-weight:600; text-decoration:none; transition:0.2s; white-space: nowrap; box-shadow: 0 2px 5px rgba(13, 110, 253, 0.2);">
                                <span class="material-symbols-rounded" style="font-size:1rem;">attach_file</span> 
                                <span style="display:none; @media(min-width:400px){display:inline;}">Anexar</span>
                                <input type="file" style="display:none;" onchange="alert('O recurso de upload automático estará disponível em breve! Por favor, envie via WhatsApp ou email enquanto isso.')">
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(!empty($proc_data['docs_excepcionais'])): ?>
                <h3 style="font-size: 1rem; color: #555; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px;">Documentos Excepcionais (Se aplicável)</h3>
                
                <?php foreach($proc_data['docs_excepcionais'] as $d_key): 
                    $is_ok = in_array($d_key, $entregues);
                ?>
                    <div class="doc-card <?= $is_ok ? 'entregue' : 'pendente' ?>">
                        <div class="doc-icon" style="background: <?= $is_ok ? '#d1e7dd' : '#fff3cd' ?>; color: <?= $is_ok ? '#198754' : '#856404' ?>;">
                            <?= $is_ok ? '✓' : '?' ?>
                        </div>
                        <div class="doc-info">
                            <div class="doc-title"><?= htmlspecialchars($todos_docs[$d_key] ?? $d_key) ?></div>
                            <span class="doc-status <?= $is_ok ? 'status-ok' : '' ?>" style="<?= !$is_ok ? 'background:#fff3cd; color:#856404;' : '' ?>">
                                <?= $is_ok ? 'Recebido' : 'Aguardando Avaliação' ?>
                            </span>
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

        <!-- FOOTER -->
        <?php include 'includes/footer.php'; ?>

    </div>

</body>
</html>
