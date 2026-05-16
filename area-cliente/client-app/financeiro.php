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

        body { background: #f4f6f8; }
        
        /* HEADER - YELLOW THEME (Premium) */
        /* HEADER - VILELA PREMIUM (GREEN) */
        .page-header {
            background: linear-gradient(135deg, #146C43 0%, #0d462b 100%); /* Vilela Green Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(20, 108, 67, 0.25); 
            margin-bottom: 25px;
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
        
        /* Mobile overrides */
        @media (max-width: 480px) {
            .page-header { 
                padding: 15px 20px; 
                flex-direction: row; 
                align-items: center; 
                justify-content: space-between;
                border-radius: 0 0 25px 25px;
            }
            .header-title-box { text-align: right; margin-left: 10px; } 
            .header-title-main { font-size: 1.1rem; line-height: 1.1; }
            .header-title-sub { font-size: 0.75rem; display: none; }
            .btn-back { 
                width: auto; 
                padding: 8px 16px; 
                font-size: 0.85rem; 
                flex-shrink: 0;
            }
            .page-header > div:first-child { width: auto; }
        }

        .fin-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px; border: 1px solid #f0f0f0;
        }
    </style>
</head>
<body>

    <div class="app-container" style="padding: 0;">

        <!-- HEADER MODULE (GOLD) -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Financeiro</span>
                    <span class="header-title-sub">Extrato e Custos</span>
                 </div>
                 
                 <!-- Icon -->
                 <div style="background: rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); color: #ffffff; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; backdrop-filter: blur(5px);">
                    <span class="material-symbols-rounded" style="font-size: 2rem;">payments</span>
                 </div>
            </div>
        </div>

        <div style="padding: 0 20px">
            <!-- KPI SUMMARY -->
            <!-- KPI SUMMARY (ENHANCED) -->
             <style>
                .fin-summary-highlight {
                    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
                    margin-bottom: 35px;
                }
                .fsh-card {
                    background: white; border-radius: 20px; padding: 25px;
                    border: 1px solid #eee; position: relative; overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
                    display: flex; flex-direction: column; justify-content: center;
                }
                .fsh-card.paid { background: linear-gradient(135deg, #e8f5e9 0%, #ffffff 80%); border-color: #c3e6cb; }
                .fsh-card.pending { background: linear-gradient(135deg, #fff9e6 0%, #ffffff 80%); border-color: #ffecb5; }
                
                .fsh-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 5px; z-index: 2; }
                .fsh-value { font-size: 1.8rem; font-weight: 800; z-index: 2; line-height: 1.2; }
                
                .fsh-icon-bg {
                    position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.15; z-index: 1; transform: rotate(-10deg);
                }
                
                @media (max-width: 480px) {
                    .fin-summary-highlight { grid-template-columns: 1fr; gap: 15px; }
                    .fsh-card { padding: 20px; }
                    .fsh-value { font-size: 1.6rem; }
                }
            </style>
            
            <div class="fin-summary-highlight">
                <div class="fsh-card paid">
                    <span class="fsh-label" style="color: #146c43;">Total Pago</span>
                    <span class="fsh-value" style="color: #198754;"><?= formatMoney($total_pago) ?></span>
                    <div class="fsh-icon-bg">✅</div>
                </div>
                <div class="fsh-card pending">
                    <span class="fsh-label" style="color: #856404;">A Pagar</span>
                    <span class="fsh-value" style="color: #ffc107;"><?= formatMoney($total_pendente + $total_atrasado) ?></span>
                    <div class="fsh-icon-bg">⏳</div>
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
                        $status_color = '#666'; // Default
                        
                        if($l['status'] == 'pago'){ 
                            $status_class = 'status-pago'; 
                            $status_label = 'Pago'; 
                            $status_color = '#198754';
                        }
                        elseif($l['status'] == 'atrasado'){ 
                            $status_class = 'status-atrasado'; 
                            $status_label = 'Atrasado'; 
                            $status_color = '#dc3545';
                        }
                        elseif($l['status'] == 'isento'){ 
                            $status_class = ''; 
                            $status_label = 'Isento'; 
                            $status_color = '#999';
                        }
                        else { 
                            $status_class = 'status-pendente'; 
                            $status_label = 'Pendente'; 
                            $status_color = '#ffc107';
                        }
                    ?>
                    
                    <div style="background: white; border-radius: 16px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?= $status_color ?>;">
                        <div class="fp-left">
                            <h4 style="margin: 0 0 5px 0; color: #333; font-size: 0.95rem; font-weight: 700;"><?= htmlspecialchars($l['descricao']) ?></h4>
                            <span style="font-size: 0.8rem; color: #666; display: block;">Vencimento: <?= date('d/m/Y', strtotime($l['data_vencimento'])) ?></span>
                        </div>
                        <div class="fp-right" style="text-align: right;">
                            <span style="display: block; font-size: 1.1rem; font-weight: 700; color: #333;"><?= formatMoney($l['valor']) ?></span>
                            <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: <?= $status_color ?>; background: <?= $status_color ?>15; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-top: 4px;"><?= $status_label ?></span>
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
