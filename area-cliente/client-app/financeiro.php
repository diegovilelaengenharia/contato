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

// 2. FETCH FINANCIAL DATA
$stmt = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
$stmt->execute([$cliente_id]);
$lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. CALCULATE TOTALS
$total_pago = 0;
$total_pendente = 0;
$total_atrasado = 0;

foreach($lancamentos as $l) {
    if($l['status'] == 'pago') $total_pago += $l['valor'];
    elseif($l['status'] == 'pendente') $total_pendente += $l['valor'];
    elseif($l['status'] == 'atrasado') $total_atrasado += $l['valor'];
}

// FORMATTER
function formatMoney($val) {
    return 'R$ ' . number_format($val, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=2.7.4">
    
    <style>
        body { background: #f4f6f8; }
        
        /* HEADER - YELLOW THEME (Premium) */
        .page-header {
            background: linear-gradient(135deg, #fff3cd 0%, #ffecb5 100%); /* Light Yellow Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.15); 
            margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
            color: #664d03; /* Dark Yellow/Brown Text */
            position: relative;
            overflow: hidden;
            border: 1px solid #ffe69c;
        }
        
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.4);
            border-radius: 50%; pointer-events: none;
        }

        .btn-back {
            text-decoration: none; color: #664d03; font-weight: 600; 
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; 
            background: white; 
            border-radius: 25px;
            transition: 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #ffe69c;
        }
        .btn-back:hover { background: #fffdf5; transform: translateX(-3px); }
        .btn-back:active { transform: scale(0.95); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #664d03; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.8; font-weight: 500; margin-top: 2px; color: #856404; }

        .fin-summary {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
            margin-bottom: 25px;
        }
        
        .fin-card-kpi {
            background: white; padding: 15px; border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            border: 1px solid #eee; text-align: center;
        }
        
        .fin-card-kpi small {
            display: block; font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .fin-card-kpi strong {
            display: block; font-size: 1.1rem; color: #333; margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="app-container" style="padding: 0;">
        
        <!-- HEADER -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Financeiro</span>
                    <span class="header-title-sub">Pagamentos e Previsões</span>
                 </div>
                 
                 <!-- Icon -->
                 <div style="background: white; border:1px solid #dee2e6; color: #ffc107; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    💰
                 </div>
            </div>
        </div>
            </h1>
        </div>

        <div style="padding: 0 20px;">
            <!-- KPI SUMMARY -->
            <div class="fin-summary">
                <div class="fin-card-kpi">
                    <small>Total Pago</small>
                    <strong style="color: #198754;"><?= formatMoney($total_pago) ?></strong>
                </div>
                <div class="fin-card-kpi">
                    <small>A Pagar</small>
                    <strong style="color: #ffc107;"><?= formatMoney($total_pendente + $total_atrasado) ?></strong>
                </div>
            </div>

            <!-- LISTA -->
            <?php if(empty($lancamentos)): ?>
                <div style="text-align:center; padding: 40px; color:#999;">
                    <div style="font-size:3rem; margin-bottom:10px; opacity:0.3;">💸</div>
                    <p>Nenhum lançamento financeiro encontrado.</p>
                </div>
            <?php else: ?>
                
                <div style="margin-bottom: 30px;">
                    <?php foreach($lancamentos as $l): 
                        $status_class = '';
                        $status_label = '';
                        
                        if($l['status'] == 'pago'){ $status_class = 'status-pago'; $status_label = 'Pago'; }
                        elseif($l['status'] == 'atrasado'){ $status_class = 'status-atrasado'; $status_label = 'Atrasado'; }
                        elseif($l['status'] == 'isento'){ $status_class = ''; $status_label = 'Isento'; } // Default gray
                        else { $status_class = 'status-pendente'; $status_label = 'Pendente'; }
                    ?>
                    
                    <div class="fin-premium-row <?= $status_class ?>">
                        <div class="fp-left">
                            <h4><?= htmlspecialchars($l['descricao']) ?></h4>
                            <span>Vencimento: <?= date('d/m/Y', strtotime($l['data_vencimento'])) ?></span>
                        </div>
                        <div class="fp-right">
                            <span class="fp-price"><?= formatMoney($l['valor']) ?></span>
                            <span class="fp-badge" style="color: inherit;"><?= $status_label ?></span>
                        </div>
                    </div>
                    
                    <?php endforeach; ?>
                </div>
                
            <?php endif; ?>

            <!-- WHATSAPP CTA -->
            <div style="text-align: center; margin-top: 20px;">
                 <a href="https://wa.me/5535984529577?text=Ola,%20gostaria%20de%20falar%20sobre%20o%20financeiro." style="display:inline-block; font-size: 0.85rem; color: #146c43; text-decoration: none; font-weight: 600; padding: 10px 20px; background: #d1e7dd; border-radius: 20px;">
                    Dúvidas sobre pagamentos? Fale conosco.
                 </a>
            </div>
        </div>

        <div class="floating-buttons">
            <a href="https://wa.me/5535984529577" class="floating-btn floating-btn--whatsapp" target="_blank" title="Falar com Engenheiro">
                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
            </a>
        </div>

    </div>

</body>
</html>
