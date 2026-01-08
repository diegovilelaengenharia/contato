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
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=2.7.4"> 
    
    <style>
        /* Override basic settings for full page view */
        body { background: #f4f6f8; }
        .page-header {
            background: #e8f5e9; /* Light Green */
            border-bottom: none;
            padding: 25px 20px; 
            border-bottom-left-radius: 20px; 
            border-bottom-right-radius: 20px;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.1); 
            margin-bottom: 25px;
            display: flex; align-items: center; gap: 10px;
            color: #146c43;
        }
        .btn-back {
            text-decoration: none; color: #146c43; font-weight: 600; 
            display: flex; align-items: center; gap: 5px;
            padding: 8px 16px; background: #fff; border-radius: 20px;
            transition: 0.2s;
            font-size: 0.9rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="app-container">
        


        <!-- HEADER COM BOTÃO VOLTAR + ANITMATED COMPASS -->
        <div class="page-header" style="justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="index.php" class="btn-back">
                    <span>←</span> Voltar
                </a>
                <h1 style="font-size:1.2rem; margin:0; color:#198754;">Acompanhamento do Processo</h1>
            </div>
            
            <!-- Animated Compass Icon -->
            <div class="app-btn-icon" style="background:#f0f4f8; color:#5c7c93; width:50px; height:50px; font-size:1.5rem; animation: compassWiggle 3s ease-in-out infinite;">
                🧭
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
            <div style="border-radius:12px; overflow:hidden; border:1px solid #e0e0e0; margin-bottom:30px; box-shadow:0 10px 30px rgba(0,0,0,0.03);">
                <!-- Card Header -->
                <div style="background: linear-gradient(135deg, #198754 0%, #146c43 100%); padding:20px; color:white;">
                    <span style="display:block; font-size:0.75rem; text-transform:uppercase; opacity:0.8; letter-spacing:1px; font-weight:600; margin-bottom:5px;">Fase Atual</span>
                    <h2 style="margin:0; font-size:1.3rem; font-weight:700; display:flex; align-items:center; gap:10px;">
                        📍 <?= htmlspecialchars($etapa_atual) ?>
                    </h2>
                </div>

                <!-- Card Body -->
                <div style="padding:20px; background:#fff;">
                    
                    <!-- Engineer Observation -->
                    <div style="background:#fff8e1; border-left:4px solid #ffc107; padding:15px; border-radius:4px; margin-bottom:20px;">
                        <div style="display:flex; gap:10px; margin-bottom:8px;">
                            <span style="font-size:1.2rem;">👷‍♂️</span>
                            <strong style="color:#856404; font-size:0.9rem;">Observação do Engenheiro:</strong>
                        </div>
                        <div style="color:#555; line-height:1.5; font-size:0.95rem; font-style:italic;">
                            "<?= !empty($obs_atual) ? strip_tags($obs_atual) : 'O processo segue em análise conforme o cronograma previsto. Nenhuma pendência urgente no momento.' ?>"
                        </div>
                    </div>

                    <!-- Process Details Grid -->
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; border-top:1px solid #eee; padding-top:20px;">
                        <?php if (!empty($detalhes['numero_processo'])): ?>
                            <div>
                                <label style="display:block; font-size:0.7rem; color:#999; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Nº Protocolo</label>
                                <span style="color:#333; font-weight:600; font-size:1rem;"><?= htmlspecialchars($detalhes['numero_processo']) ?></span>
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
                
                <!-- Card Footer (Progress Bar Visual) -->
                <div style="background:#f8f9fa; padding:10px 20px; border-top:1px solid #eee; display:flex; align-items:center; justify-content:space-between;">
                   <span style="font-size:0.75rem; color:#666; font-weight:600;">Progresso Geral</span>
                   <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:100px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden;">
                            <div style="width:<?= $porcentagem ?>%; height:100%; background:#198754;"></div>
                        </div>
                        <span style="font-size:0.8rem; font-weight:700; color:#198754;"><?= $porcentagem ?>%</span>
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
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="background:#f8f9fa; text-align:left; color:#666;">
                                <th style="padding:12px; border-bottom:2px solid #e9ecef;">Data</th>
                                <th style="padding:12px; border-bottom:2px solid #e9ecef;">Evento</th>
                                <th style="padding:12px; border-bottom:2px solid #e9ecef;">Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historico as $h): 
                                $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                                $desc_principal = $parts[0];
                            ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px; color:#555; font-weight:600; vertical-align:top; white-space:nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?>
                                </td>
                                <td style="padding:12px; vertical-align:top;">
                                    <span style="display:inline-block; background:#e9ecef; color:#333; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                        <?= htmlspecialchars($h['titulo_fase']) ?>
                                    </span>
                                </td>
                                <td style="padding:12px; color:#666; vertical-align:top; line-height:1.5;">
                                    <?= $desc_principal ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
             <?php endif; ?>

        </div>
        </div>
        
        <div class="floating-buttons">
            <a href="https://wa.me/5535984529577?text=Ola%20Engenheiro,%20tenho%20uma%20divida%20sobre%20o%20processo" class="floating-btn floating-btn--whatsapp" target="_blank" title="Falar com Engenheiro">
                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
            </a>
            <a href="https://www.instagram.com/diegovilela.eng/" class="floating-btn floating-btn--instagram" target="_blank" title="Instagram">
                <svg viewBox="0 0 24 24"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 5A1.5 1.5 0 1 0 10.5 12 1.5 1.5 0 0 0 12 13.5zm4.25-6.75a1 1 0 1 1-1-1 1 1 0 0 1 1 1z"></path></svg>
            </a>
        </div>

    </div>

</body>
</html>
