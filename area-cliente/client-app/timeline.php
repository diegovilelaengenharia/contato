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

$etapa_atual = $detalhes['etapa_atual'] ?? 'Levantamento de Dados';
$etapa_atual = trim($etapa_atual);
$fase_index = array_search($etapa_atual, $fases_padrao);
if($fase_index === false) $fase_index = 0; 
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
    <link rel="stylesheet" href="css/style.css?v=2.6"> 
    
    <style>
        /* Override basic settings for full page view */
        body { background: #f4f6f8; }
        .page-header {
            background:#fff; /* White */
            border-bottom: 3px solid #198754; /* Green Border */
            padding: 25px 20px; 
            border-bottom-left-radius: 0; 
            border-bottom-right-radius: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 25px;
            display: flex; align-items: center; gap: 10px;
            color: #333;
        }
        .btn-back {
            text-decoration: none; color: #666; font-weight: 600; 
            display: flex; align-items: center; gap: 5px;
            padding: 6px 12px; background: #f0f0f0; border-radius: 8px;
            transition: 0.2s;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="app-container">
        


        <!-- HEADER COM BOTÃO VOLTAR -->
        <div class="page-header">
            <a href="index.php" class="btn-back">
                <span>←</span> Voltar
            </a>
            <h1 style="font-size:1.2rem; margin:0; color:#198754;">Linha do Tempo</h1>
        </div>

        <!-- CONTEÚDO DA TIMELINE (Portado do Modal) -->
        <div style="background:white; border-radius:16px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            
            <!-- DETALHES DO PROCESSO -->
            <?php if ($detalhes): ?>
            <div style="background:#f8f9fa; border:1px solid #e9ecef; border-radius:12px; padding:20px; margin-bottom:30px;">
                <h3 style="margin:0 0 15px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #dee2e6; padding-bottom:8px;">
                    📋 Dados do Processo
                </h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; font-size:0.9rem;">
                    <?php if (!empty($detalhes['endereco_imovel'])): ?>
                        <div style="grid-column: span 2;">
                            <label style="display:block; font-size:0.75rem; color:#666; text-transform:uppercase; font-weight:700;">Local da Obra</label>
                            <span style="color:#000; font-weight:500;"><?= htmlspecialchars($detalhes['endereco_imovel']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($detalhes['tipo_servico'])): ?>
                        <div>
                            <label style="display:block; font-size:0.75rem; color:#666; text-transform:uppercase; font-weight:700;">Serviço</label>
                            <span style="color:#000; font-weight:500;"><?= htmlspecialchars($detalhes['tipo_servico']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($detalhes['numero_processo'])): ?>
                        <div>
                            <label style="display:block; font-size:0.75rem; color:#666; text-transform:uppercase; font-weight:700;">Nº Protocolo</label>
                            <span style="color:#000; font-weight:500;"><?= htmlspecialchars($detalhes['numero_processo']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- TIMELINE STEPPER -->
            <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Todas as Etapas</h3>
            <div class="timeline-container-full" style="padding-left:15px; margin-bottom:30px;">
                <?php 
                    foreach($fases_padrao as $k => $fase): 
                        $is_past = $k < $fase_index;
                        $is_curr = $k === $fase_index;
                        
                        // Icons based on status
                        $icon_display = '▫️'; // Default/Future
                        if($is_past) $icon_display = '✅';
                        if($is_curr) $icon_display = '📍';
                        
                        $text_style = $is_curr ? 'font-weight:700; color:#333;' : ($is_past ? 'color:#198754;' : 'color:#999;');
                        
                        // Line Line
                        $line_color = ($is_past) ? '#198754' : '#e9ecef';
                ?>
                <div style="display:flex; gap:15px; position:relative; padding-bottom:30px;">
                    <!-- Line -->
                    <?php if($k < count($fases_padrao)-1): ?>
                    <div style="position:absolute; left:11px; top:30px; bottom:0; width:2px; background:<?= $line_color ?>; z-index:0;"></div>
                    <?php endif; ?>
                    
                    <!-- Icon -->
                    <div style="width:24px; height:24px; display:flex; align-items:center; justify-content:center; z-index:1; flex-shrink:0; font-size:1.2rem; background:#fff;">
                        <?= $icon_display ?>
                    </div>
                    
                    <!-- Text -->
                    <div style="padding-top:4px;">
                        <span style="font-size:1rem; display:block; <?= $text_style ?>">
                            <?= $fase ?>
                        </span>
                        <?php if($is_curr): ?>
                            <span style="font-size:0.7rem; background:#ffc107; color:#333; padding:2px 8px; border-radius:12px; font-weight:700; text-transform:uppercase; margin-top:4px; display:inline-block;">Em Andamento</span>
                        <?php endif; ?>
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
