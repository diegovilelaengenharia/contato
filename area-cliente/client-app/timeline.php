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

// BUSCAR DADOS DO CLIENTE
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// BUSCAR DETALHES DO PROCESSO
$stmt_det = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmt_det->execute([$cliente_id]);
$detalhes = $stmt_det->fetch(PDO::FETCH_ASSOC);

// DEFINIÇÃO DAS FASES (Mantendo consistência com index.php)
$fases_padrao = [
    'Abertura de Processo (Guichê)',
    'Fiscalização (Parecer Fiscal)',
    'Triagem (Documentos Necessários)',
    'Comunicado de Pendências (Triagem)',
    'Análise Técnica (Engenharia)',
    'Comunicado (Pendências e Taxas)',
    'Confecção de Documentos',
    'Avaliação (ITBI/Averbação)',
    'Processo Finalizado (Documentos Prontos)'
];

$etapa_atual = trim($detalhes['etapa_atual'] ?? 'Abertura de Processo (Guichê)');
$fase_index = array_search($etapa_atual, $fases_padrao);
if($fase_index === false) $fase_index = 0; 
$porcentagem = round((($fase_index + 1) / count($fases_padrao)) * 100);

// BUSCAR OBSERVAÇÃO DA ETAPA ATUAL
$stmt_obs = $pdo->prepare("SELECT descricao FROM processo_movimentos WHERE cliente_id = ? AND titulo_fase = ? ORDER BY data_movimento DESC LIMIT 1");
$stmt_obs->execute([$cliente_id, $etapa_atual]);
$obs_atual = $stmt_obs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linha do Tempo</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=4.2"> 
    
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
        /* HEADER MODULE STYLE (VILELA PREMIUM) - TIMELINE */
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
        .btn-back:hover { background: #f0fff4; transform: translateX(-3px); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #ffffff; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.9; font-weight: 400; margin-top: 2px; color: #e9ecef; }

        @media (max-width: 480px) {
            .page-header { 
                padding: 15px 20px; 
                flex-direction: row; /* Keep row */
                align-items: center; 
                justify-content: space-between;
                border-radius: 0 0 25px 25px;
            }
            .header-title-box { text-align: right; margin-left: 10px; } 
            .header-title-main { font-size: 1.1rem; line-height: 1.1; }
            .header-title-sub { font-size: 0.75rem; display: none; } /* Simplify mobile */
            .btn-back { 
                width: auto; 
                padding: 8px 16px; 
                font-size: 0.85rem; 
                flex-shrink: 0;
            }
            .page-header > div:first-child { width: auto; } /* Reset */
        }

        @keyframes compassWiggle {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(-15deg); }
            50% { transform: rotate(10deg); }
            75% { transform: rotate(-5deg); }
            100% { transform: rotate(0deg); }
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER NOVA IDENTIDADE VISUAL (TIMELINE) -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Linha do Tempo</span>
                    <span class="header-title-sub">Acompanhamento do Processo</span>
                 </div>
                 
                 <!-- Animated Compass Icon -->
                 <div style="color: #ffffff; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; animation: compassWiggle 4s ease-in-out infinite;">
                    <span class="material-symbols-rounded" style="font-size: 2.2rem; text-shadow: 0 2px 10px rgba(0,0,0,0.1);">compass_calibration</span>
                 </div>
            </div>
        </div>

        <style>
            @keyframes compassWiggle {
                0% { transform: rotate(0deg); }
                25% { transform: rotate(-15deg); }
                50% { transform: rotate(10deg); }
                75% { transform: rotate(-5deg); }
                100% { transform: rotate(0deg); }
            }
        </style>

        <!-- CONTEÚDO DA TIMELINE -->
        <div style="background:white; border-radius:16px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            
            <!-- STATUS CARD (Resumo Fase Atual) -->
            <?php if ($detalhes): ?>
            <div style="border-radius:16px; overflow:hidden; border:1px solid #e0e0e0; margin-bottom:30px; background:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
                
                <div style="padding:25px;">
                     <!-- Discrete Phase Section -->
                     <div style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:15px;">
                        <span style="display:block; font-size:0.7rem; text-transform:uppercase; color:#999; letter-spacing:1px; font-weight:700; margin-bottom:5px;">Fase Atual</span>
                        <h2 style="margin:0; font-size:1.1rem; font-weight:600; color:#333; display:flex; align-items:center; gap:8px;">
                            <span style="font-size:1.2rem;">📍</span> <?= htmlspecialchars($etapa_atual) ?>
                        </h2>
                     </div>

                    <!-- Emphasized Observation -->
                    <div style="margin-bottom:20px;">
                        <div style="color:#dc3545; font-weight:800; font-size:0.95rem; margin-bottom:8px;">
                            Observação do Engenheiro:
                        </div>
                        <div style="color:#333; line-height:1.6; font-size:1rem; padding-left:2px;">
                            "<?= !empty($obs_atual) ? strip_tags($obs_atual) : 'O processo segue em análise conforme o cronograma previsto. Nenhuma pendência urgente no momento.' ?>"
                        </div>
                    </div>

                    <!-- Process Details Grid -->
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; border-top:1px solid #eee; padding-top:20px; opacity:0.9;">
                        <?php if (!empty($detalhes['numero_processo'])): ?>
                            <div>
                                <label style="display:block; font-size:0.7rem; color:#999; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Nº Protocolo</label>
                                <span style="font-family:monospace; color:#555; font-weight:600; font-size:0.95rem;"><?= htmlspecialchars($detalhes['numero_processo']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($detalhes['endereco_imovel'])): ?>
                            <div>
                                <label style="display:block; font-size:0.7rem; color:#999; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Local da Obra</label>
                                <span style="color:#333; font-weight:600; font-size:0.95rem; display:block; line-height:1.3;"><?= htmlspecialchars($detalhes['endereco_imovel']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <?php endif; ?>

            <!-- TIMELINE STEPPER (GROUPED) -->
            <div class="timeline-container-full" style="padding-left:0; margin-bottom:30px;">
                <?php 
                    // Define Groups
                    $grupos = [
                        '🚀 Fase Inicial' => array_slice($fases_padrao, 0, 4), // 0-3
                        '🏗️ Análise Técnica' => array_slice($fases_padrao, 4, 2), // 4-5
                        '📄 Emissão de Documentos' => array_slice($fases_padrao, 6, 3) // 6-8
                    ];
                    
                    $global_index = 0;

                    foreach($grupos as $nome_grupo => $fases_grupo):
                ?>
                    <div style="margin-bottom:20px;">
                        <h4 style="margin:0 0 15px 0; font-size:0.85rem; color:#999; text-transform:uppercase; font-weight:700; letter-spacing:1px; background:#f8f9fa; padding:5px 10px; border-radius:4px; display:inline-block;">
                            <?= $nome_grupo ?>
                        </h4>
                        
                        <div style="padding-left:15px;">
                        <?php
                            foreach($fases_grupo as $fase):
                                $is_past = $global_index < $fase_index;
                                $is_curr = $global_index === $fase_index;
                                
                                // Icons
                                $icon_display = '▫️'; 
                                if($is_past) $icon_display = '✅';
                                if($is_curr) $icon_display = '📍';
                                
                                $text_style = $is_curr ? 'font-weight:700; color:#333;' : ($is_past ? 'color:#198754;' : 'color:#aaa;');
                                $line_color = ($is_past) ? '#198754' : '#e9ecef';
                        ?>
                            <div style="display:flex; gap:15px; position:relative; padding-bottom:25px;">
                                <!-- Connect Line (Logic: if not last in group) -->
                                <div style="position:absolute; left:11px; top:25px; bottom:0; width:2px; background:<?= $line_color ?>; z-index:0;"></div>
                                
                                <!-- Icon -->
                                <div style="width:24px; height:24px; display:flex; align-items:center; justify-content:center; z-index:1; flex-shrink:0; font-size:1.2rem; background:#fff;">
                                    <?= $icon_display ?>
                                </div>
                                
                                <!-- Text -->
                                <div style="padding-top:4px;">
                                    <span style="font-size:0.95rem; display:block; <?= $text_style ?>">
                                        <?= $fase ?>
                                    </span>
                                    <?php if($is_curr): ?>
                                        <div style="margin-top:5px;">
                                            <span style="font-size:0.65rem; background:#ffc107; color:#333; padding:2px 8px; border-radius:12px; font-weight:700; text-transform:uppercase; display:inline-block;">Em Andamento</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php 
                            $global_index++; 
                            endforeach; 
                        ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- HISTÓRICO COMPLETO (REAL) -->
            <h3 style="margin:30px 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">📜 Histórico do Processo</h3>
             <?php
             // Usando a tabela REAL do Admin (processo_movimentos)
             $stmt_hist = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
             $stmt_hist->execute([$cliente_id]);
             $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

             if(empty($historico)): ?>
                <div class="empty-state" style="text-align:center; padding:30px; color:#999; border:2px dashed #eee; border-radius:12px;">
                    Nenhum registro no histórico.
                </div>
             <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <?php foreach($historico as $h): 
                        $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                        $desc_principal = $parts[0];
                        $data_mov = strtotime($h['data_movimento']);
                    ?>
                    <div style="display:flex; gap:15px; align-items:flex-start;">
                        <!-- Date Column -->
                        <div style="display:flex; flex-direction:column; align-items:center; min-width:50px; padding-top: 5px;">
                            <span style="font-size:1.1rem; font-weight:700; color:#333; line-height:1;"><?= date('d', $data_mov) ?></span>
                            <span style="font-size:0.75rem; text-transform:uppercase; color:#999; font-weight:600;"><?= date('M', $data_mov) ?></span>
                            <span style="font-size:0.65rem; color:#ccc;"><?= date('Y', $data_mov) ?></span>
                            <!-- Vertical Line (Visual Connector) -->
                            <div style="width:2px; background:#e0e0e0; flex:1; margin-top:8px; border-radius:1px;"></div>
                        </div>

                        <!-- Content Card -->
                        <div style="background:white; border:1px solid #efefef; border-radius:12px; padding:15px; flex:1; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                            <h4 style="margin:0 0 8px 0; color:#146c43; font-size:0.95rem; font-weight:700;">
                                <?= htmlspecialchars($h['titulo_fase']) ?>
                            </h4>
                            <?php if(!empty($desc_principal)): ?>
                                <div style="font-size:0.9rem; color:#555; line-height:1.5;">
                                    <?= nl2br(htmlspecialchars($desc_principal)) ?>
                                </div>
                            <?php else: ?>
                                <span style="font-size:0.8rem; color:#ccc; font-style:italic;">Sem observações adicionais.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
             <?php endif; ?>

             <!-- WHATSAPP CTA -->
             <div style="text-align: center; margin-top: 30px; margin-bottom: 20px;">
                 <a href="https://wa.me/5535984529577?text=Ola,%20tenho%20duvidas%20sobre%20o%20andamento%20do%20processo." style="display:inline-block; font-size: 0.85rem; color: #146c43; text-decoration: none; font-weight: 600; padding: 10px 20px; background: #d1e7dd; border-radius: 20px;">
                    Dúvidas sobre o andamento? Fale conosco.
                 </a>
             </div>

        </div>

        
        <!-- FLOATING SOCIAL BUTTONS (OFFICIAL LANDING PAGE STYLE) -->
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

</body>
</html>
