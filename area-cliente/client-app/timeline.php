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
    'Levantamento de Dados',
    'Desenvolvimento de Projetos',
    'Aprovação na Prefeitura',
    'Pagamento de Taxas',
    'Emissão de Alvará',
    'Entrega de Projetos'
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
    <title>Linha do Tempo | Vilela Engenharia</title>
    
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
            background:white; padding:15px; border-radius:16px; 
            box-shadow:0 2px 10px rgba(0,0,0,0.03); margin-bottom:20px;
            display:flex; align-items:center; gap:10px;
        }
        .btn-back {
            text-decoration:none; color:#666; font-weight:600; 
            display:flex; align-items:center; gap:5px;
            padding:8px 12px; background:#f8f9fa; border-radius:8px;
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
            <h1 style="font-size:1.2rem; margin:0; color:#146c43;">Linha do Tempo</h1>
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
            <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Etapas</h3>
            <div class="timeline-container-full" style="padding-left:15px; margin-bottom:30px;">
                <?php 
                    foreach($fases_padrao as $k => $fase): 
                        $is_past = $k < $fase_index;
                        $is_curr = $k === $fase_index;
                        
                        $dot_bg = $is_past ? '#198754' : ($is_curr ? 'white' : '#e9ecef');
                        $dot_border = $is_past ? '#198754' : ($is_curr ? 'var(--color-primary)' : '#ccc');
                        $dot_icon_color = $is_past ? 'white' : ($is_curr ? 'var(--color-primary)' : '#999');
                        $line_color = '#e9ecef';
                        if ($is_past) $line_color = '#198754';
                        
                        $text_style = $is_curr ? 'font-weight:700; color:var(--color-primary);' : ($is_past ? 'color:#198754;' : 'color:#999;');
                ?>
                <div style="display:flex; gap:15px; position:relative; padding-bottom:30px;">
                    <!-- Line -->
                    <?php if($k < count($fases_padrao)-1): ?>
                    <div style="position:absolute; left:12px; top:28px; bottom:0; width:3px; background:<?= $line_color ?>; z-index:0;"></div>
                    <?php endif; ?>
                    
                    <!-- Dot -->
                    <div style="width:28px; height:28px; border-radius:50%; background:<?= $dot_bg ?>; border:3px solid <?= $dot_border ?>; display:flex; align-items:center; justify-content:center; z-index:1; flex-shrink:0; font-size:0.8rem; font-weight:bold; color:<?= $dot_icon_color ?>; transition: all 0.3s ease;">
                        <?php if($is_past): ?>✓<?php elseif($is_curr): ?>•<?php else: ?> <?php endif; ?>
                    </div>
                    
                    <!-- Text -->
                    <div style="padding-top:4px;">
                        <span style="font-size:1rem; display:block; <?= $text_style ?>">
                            <?= $fase ?>
                        </span>
                        <?php if($is_curr): ?>
                            <span style="font-size:0.7rem; background:var(--color-primary); color:white; padding:3px 8px; border-radius:12px; font-weight:600; text-transform:uppercase; margin-top:4px; display:inline-block;">Em Andamento</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- HISTORY -->
            <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Movimentações Recentes</h3>
             <?php
             $stmt_hist = $pdo->prepare("SELECT * FROM processo_movimentacoes WHERE cliente_id = ? ORDER BY data_movimentacao DESC");
             $stmt_hist->execute([$cliente_id]);
             $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

             if(empty($historico)): ?>
                <div class="empty-state" style="text-align:center; padding:30px; color:#999; border:2px dashed #eee; border-radius:12px;">
                    Nenhuma movimentação registrada.
                </div>
             <?php else: 
                foreach($historico as $h): ?>
                <div class="history-item" style="border-left:4px solid var(--color-primary); padding:15px 20px; margin-bottom:15px; background:white; border-radius:8px; box-shadow:0 3px 6px rgba(0,0,0,0.04);">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span style="font-weight:700; color:#333; font-size:1rem;"><?= htmlspecialchars($h['titulo']) ?></span>
                        <span style="font-size:0.8rem; color:#666; font-weight:600; background:#f0f0f0; padding:2px 8px; border-radius:4px; height:fit-content;"><?= date('d/m/Y', strtotime($h['data_movimentacao'])) ?></span>
                    </div>
                    <?php if(!empty($h['descricao'])): ?>
                        <div style="font-size:0.9rem; color:#555; line-height:1.5; margin-top:5px;"><?= nl2br(htmlspecialchars($h['descricao'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; 
             endif; ?>

        </div>
        
    </div>

</body>
</html>
