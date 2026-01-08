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

// --- NOTIFICATIONS LOGIC ---
$notificacoes = [];

// 1. Pendências em Aberto
$stmt_pend = $pdo->prepare("SELECT count(*) as qtd FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido'");
$stmt_pend->execute([$cliente_id]);
$pend_qtd = $stmt_pend->fetchColumn();
if($pend_qtd > 0) {
    $notificacoes[] = [
        'tipo' => 'alerta',
        'msg' => "Você tem $pend_qtd pendência(s) para resolver.",
        'link' => 'pendencias.php'
    ];
}

// 2. Pagamentos Pendentes/Atrasados
$stmt_fin = $pdo->prepare("SELECT count(*) as qtd FROM processo_financeiro WHERE cliente_id = ? AND (status = 'pendente' OR status = 'atrasado')");
$stmt_fin->execute([$cliente_id]);
$fin_qtd = $stmt_fin->fetchColumn();
if($fin_qtd > 0) {
    $notificacoes[] = [
        'tipo' => 'financeiro',
        'msg' => "Existem $fin_qtd pagamentos pendentes.",
        'link' => 'financeiro.php'
    ];
}

// 3. Movimentações Recentes (Últimos 15 dias)
$stmt_mov = $pdo->prepare("SELECT titulo_fase, data_movimento FROM processo_movimentos WHERE cliente_id = ? AND data_movimento >= DATE_SUB(NOW(), INTERVAL 15 DAY) ORDER BY data_movimento DESC LIMIT 3");
$stmt_mov->execute([$cliente_id]);
$movs = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);
foreach($movs as $m) {
    $notificacoes[] = [
        'tipo' => 'info',
        'msg' => "Nova movimentação: " . $m['titulo_fase'],
        'link' => 'timeline.php'
    ];
}

$total_notif = count($notificacoes);


// DEFINIÇÃO DAS FASES (Para Timeline Card)
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
$porcentagem = round((($fase_index + 1) / count($fases_padrao)) * 100);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=3.2">
    <link rel="stylesheet" href="css/header-premium.css?v=<?= time() ?>">
    
    <style>
        /* Mobile adjustment for Header - Handled mostly in header-premium.css now, but ensuring overrides */
        /* FORCE SOCIAL UPDATE v2 */
        .floating-social-container { position: fixed; bottom: 25px; right: 25px; display: flex; flex-direction: column; gap: 15px; z-index: 99999 !important; }
        .social-btn { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,0.25); position: relative; border: none !important; transition: transform 0.3s; }
        .social-btn:hover { transform: scale(1.1); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .social-btn svg { width: 32px; height: 32px; fill: white; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.1)); }
        .social-btn.whatsapp { background-color: #25D366 !important; }
        .social-btn.instagram { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%) !important; }
        
        @media (max-width: 420px) {
            .premium-header {
                padding: 15px !important;
            }
        }
    </style>
    <style>
        /* MODAL DE NOTIFICAÇÕES */
        #modalNotificacoes {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 10000;
            align-items: center; justify-content: center;
        }
        #modalNotificacoes.open { display: flex; }
        
        .notification-box {
            background: white; width: 90%; max-width: 400px;
            border-radius: 20px; padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .notif-item {
            padding: 15px; border-bottom: 1px solid #eee;
            display: flex; align-items: start; gap: 10px;
            text-decoration: none; color: #333;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { font-size: 1.2rem; }

        /* FOOTER BRANDING */
        .premium-footer {
            margin-top: 50px; padding: 40px 20px;
            background: white; border-top-left-radius: 30px; border-top-right-radius: 30px;
            text-align: center; box-shadow: 0 -4px 20px rgba(0,0,0,0.02);
        }
        .pf-logo { height: 50px; margin-bottom: 10px; filter: grayscale(1); opacity: 0.7; }
        .pf-text { font-size: 0.9rem; color: #999; line-height: 1.6; } /* Font size increased */
        .pf-strong { color: #555; font-weight: 700; display: block; margin-top: 5px; font-size: 1rem;} 
    </style>
</head>
<body>

    <div class="app-container" style="padding: 0;"> <!-- Remove padding here, controlled by inner elements -->
        
        <!-- HEADER STYLE PREMIUM WOW (Glass + Gradient) -->
        <!-- HEADER STYLE PREMIUM WOW (Glass + Gradient) -->
        <div style="display: flex; justify-content: center; margin-bottom: 20px; margin-top: 20px;">
            <div style="background: #222; padding: 12px 35px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 215, 0, 0.2); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                <!-- Shine Effect Background -->
                <div style="position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent); animation: shine 3s infinite;"></div>
                
                <span style="font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 3px; background: linear-gradient(45deg, #B8860B, #FFD700, #F0E68C, #DAA520); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-size: 200% auto; animation: textShine 4s linear infinite;">
                    Área do Cliente
                </span>
            </div>
        </div>
        <style>
            @keyframes shine { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
            @keyframes textShine { to { background-position: 200% center; } }
        </style>
        <header class="premium-header" style="flex-direction: column; gap: 10px; align-items: stretch;">
            
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="ph-content" style="flex: 1;">
                    <!-- AVATAR (Re-added, small) -->
                    <?php 
                        $avatarPath = $cliente['foto_perfil'] ?? '';
                        if($avatarPath && !str_starts_with($avatarPath, '../') && !str_starts_with($avatarPath, 'http')) $avatarPath = '../' . $avatarPath;
                    ?>
                    <div class="ph-avatar-box" style="width: 45px; height: 45px; margin-right: 12px;">
                        <?php if($avatarPath && file_exists($avatarPath) && !is_dir($avatarPath)): ?>
                            <img src="<?= htmlspecialchars($avatarPath) ?>?v=<?= time() ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:1.2rem; color:white;">👤</span>
                        <?php endif; ?>
                    </div>

                    <div class="ph-info">
                        <h1 style="margin:0; font-size:1.4rem;">Olá, <?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?>!</h1>
                    </div>
                </div>

                <div class="ph-actions">
                    <a href="logout.php" class="ph-logout" style="background: rgba(220, 53, 69, 0.2); color: #ffcccc; border: 1px solid rgba(220, 53, 69, 0.3); padding: 6px 12px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size:1.1rem;">logout</span>
                        <span style="font-size: 0.85rem; font-weight: 600;">Sair</span>
                    </a>
                </div>

        </header>



        <!-- MAIN CONTENT (With Padding) -->
        <div style="padding: 0 20px;">
            
            <div class="app-action-grid">
                
                <?php
                    // --- LOGIC: Fetch Titles Safe ---
                    // 1. Latest Pendency (Safe Fetch - Title + Desc)
                    $last_pend_name = '';
                    try {
                        $stmt_lp = $pdo->prepare("SELECT titulo, descricao FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido' ORDER BY data_criacao DESC LIMIT 1");
                        $stmt_lp->execute([$cliente_id]);
                        $row_lp = $stmt_lp->fetch(PDO::FETCH_ASSOC);
                        if($row_lp) {
                            $last_pend_name = $row_lp['titulo'];
                            if(!empty($row_lp['descricao'])) {
                                $last_pend_name .= ' - ' . strip_tags($row_lp['descricao']);
                            }
                        }
                    } catch(Exception $e) { $last_pend_name = ''; }

                    // 2. Latest Finance (Safe Fetch - Desc + Val)
                    $last_fin_name = '';
                    try {
                        $stmt_lf = $pdo->prepare("SELECT descricao, valor FROM processo_financeiro WHERE cliente_id = ? AND (status = 'pendente' OR status = 'atrasado') ORDER BY data_vencimento ASC LIMIT 1");
                        $stmt_lf->execute([$cliente_id]);
                        $row_lf = $stmt_lf->fetch(PDO::FETCH_ASSOC);
                        if($row_lf) {
                            $last_fin_name = $row_lf['descricao'];
                            if(!empty($row_lf['valor'])) {
                                $last_fin_name .= ' (R$ ' . number_format($row_lf['valor'], 2, ',', '.') . ')';
                            }
                        }
                    } catch(Exception $e) { $last_fin_name = ''; }
                ?>

                <!-- 2. TIMELINE -->
                <a href="timeline.php" class="app-button" style="border-left-color: #198754;">
                    <div class="app-btn-icon" style="background:#e8f5e9; color:#198754;">🧭</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Linha do Tempo</span>
                        <div class="progress-mini" style="margin-top:5px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden; width:100px;">
                            <div class="bar" style="width: <?= $porcentagem ?>%; height:100%; background:#198754;"></div>
                        </div>
                        <span class="app-btn-desc" style="margin-top:5px;">
                            <?= htmlspecialchars($etapa_atual) ?> - <?= $porcentagem ?>%
                        </span>
                    </div>
                    <div class="app-btn-arrow" style="color:#198754;">➔</div>
                </a>

                <!-- 3. PENDÊNCIAS -->
                <?php 
                    // Se houver pendências: Vermelho (#dc3545)
                    // Se NÃO houver: Cinza Bacana (#6c757d)
                    $p_color = ($pend_qtd > 0) ? '#dc3545' : '#6c757d';
                    $p_bg    = ($pend_qtd > 0) ? '#fce8e6' : '#e9ecef';
                    $p_icon  = ($pend_qtd > 0) ? '⚠️' : '✅'; // Warning se tiver, Check se não
                ?>
                <a href="pendencias.php" class="app-button" style="border-left-color: <?= $p_color ?>;">
                    <div class="app-btn-icon" style="background:<?= $p_bg ?>; color:<?= $p_color ?>;"><?= $p_icon ?></div>
                    <div class="app-btn-content">
                        <span class="app-btn-title" style="color: #333;">Pendências</span>
                        <?php if($pend_qtd > 0): ?>
                            <span class="app-btn-desc" style="color:#dc3545; font-weight:600;">
                                <?= htmlspecialchars(mb_strimwidth($last_pend_name, 0, 35, "...")) ?>
                            </span>
                        <?php else: ?>
                            <span class="app-btn-desc" style="color:#888;">Nenhuma pendência recente</span>
                        <?php endif; ?>
                    </div>
                    <?php if($pend_qtd > 0): ?>
                        <span class="badge-count" style="background:#dc3545;"><?= $pend_qtd ?></span>
                    <?php else: ?>
                         <div class="app-btn-arrow" style="color:<?= $p_color ?>;">➔</div>
                    <?php endif; ?>
                </a>

                <!-- 4. FINANCEIRO -->
                <a href="financeiro.php" class="app-button" style="border-left-color: #ffc107;">
                    <div class="app-btn-icon" style="background:#fff3cd; color:#ffc107;">💰</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Financeiro</span>
                         <?php if($fin_qtd > 0): ?>
                            <span class="app-btn-desc" style="color:#d9a406; font-weight:600;">
                                <?= htmlspecialchars(mb_strimwidth($last_fin_name, 0, 40, "...")) ?>
                            </span>
                        <?php else: ?>
                            <span class="app-btn-desc">Nenhum pagamento pendente</span>
                        <?php endif; ?>
                    </div>
                    <?php if($fin_qtd > 0): ?>
                        <span class="badge-count" style="background:#ffc107; color:#856404;"><?= $fin_qtd ?></span>
                    <?php else: ?>
                        <div class="app-btn-arrow" style="color:#ffc107;">➔</div>
                    <?php endif; ?>
                </a>
                
                <!-- 5. DOCUMENTOS -->
                <a href="documentos.php" class="app-button" style="border-left-color: #0dcaf0;">
                    <div class="app-btn-icon" style="background:#d1ecf1; color:#0dcaf0;">📂</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Documentos Finais</span>
                        <span class="app-btn-desc">Acesso aos documentos digitais</span>
                    </div>
                    <div class="app-btn-arrow" style="color:#0dcaf0;">➔</div>
                </a>

                <!-- 6. RESUMO (MOVED & RESTYLED) -->
                <a href="../../area-cliente/relatorio_cliente.php?id=<?= $cliente['id'] ?>" target="_blank" class="app-button" style="background: #fff3cd; border: 1px solid #ffecb5; padding: 12px 15px; min-height: auto;">
                    <div class="app-btn-icon" style="background: rgba(255, 193, 7, 0.2); color: #856404; width: 32px; height: 32px; font-size: 1.1rem; flex-shrink: 0;">🖨️</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title" style="color: #856404; font-size: 0.95rem; font-weight: 700; display: block; margin-bottom: 2px;">Download: Visão Geral do Processo</span>
                        <span class="app-btn-desc" style="color: #856404; font-size: 0.75rem; opacity: 0.9;">Baixar Resumo Completo do processo</span>
                    </div>
                </a>

            </div>
            
        </div>

        <!-- FOOTER PREMIUM -->
        <footer class="premium-footer" style="padding: 20px 20px; background: #fff; border-top: 1px solid #eee; margin-top: 0; margin-bottom: 25px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                <!-- Logo -->
                <div style="flex-shrink: 0;">
                    <img src="../../assets/logo.png" alt="Vilela Engenharia" style="max-height: 45px; opacity: 1;">
                </div>
                
                <!-- Vertical Divider (Optional, subtle) -->
                <div style="width:1px; height:35px; background:#eee;"></div>

                <!-- Info -->
                <div style="text-align: left;">
                    <span style="display:block; font-size: 0.65rem; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; line-height:1;">Engenheiro Responsável</span>
                    <span style="display:block; font-size: 0.95rem; font-weight: 700; color: #333; margin: 2px 0; line-height:1.2;">Diego T. N. Vilela</span>
                    <span style="display:block; font-size: 0.75rem; color: #666; line-height:1;">CREA 235.474/D</span>
                </div>
            </div>
            <div style="margin-top: 15px; font-size: 0.7rem; color: #ccc; text-align: center;">
                &copy; <?= date('Y') ?> Vilela Engenharia
            </div>
        </footer>

        <!-- FLOATING ACTION BUTTONS -->
        <!-- FLOATING SOCIAL BUTTONS (OFFICIAL SVGS) -->
        <div class="floating-social-container">
            <!-- WhatsApp (Official Icon) -->
            <a href="https://wa.me/5535984529577?text=Ol%C3%A1%2C%20gostaria%20de%20falar%20sobre%20meu%20processo." target="_blank" class="social-btn whatsapp">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M18.403 5.633A8.919 8.919 0 0 0 12.053 3c-4.948 0-8.976 4.027-8.978 8.977 0 1.582.413 3.126 1.198 4.488L3 21.116l4.759-1.249a8.981 8.981 0 0 0 4.29 1.093h.004c4.947 0 8.975-4.027 8.977-8.977a8.926 8.926 0 0 0-2.627-6.35m-6.35 13.812h-.003a7.446 7.446 0 0 1-3.798-1.041l-.272-.162-2.824.741.753-2.753-.177-.282a7.448 7.448 0 0 1-1.141-3.971c.002-4.114 3.349-7.461 7.465-7.461a7.413 7.413 0 0 1 5.275 2.188 7.42 7.42 0 0 1 2.183 5.279c-.002 4.114-3.349 7.462-7.461 7.462m4.093-5.589c-.225-.113-1.327-.655-1.533-.73-.205-.075-.354-.112-.504.112-.149.224-.579.73-.709.88-.131.149-.261.169-.486.056-.224-.112-9.25-3.363-1.09-5.003-.1391-.124-.2681-.226-.387-.306-.419-.281-.722-.524-.712-.131-.187.056-.299.112-.411.224.112.224 2.224 1.12.374.374.149.524 1.421.749 2.091.16.476.16 1.842.16 2.385.16.543-.001 1.056-.239 1.341-.676.285-.436 1.056.126 1.706.766z" fill="white"/>
                </svg>
                <div class="social-tooltip">Fale conosco</div>
            </a>
            
            <!-- Instagram (Official Icon) -->
            <a href="https://instagram.com/vilela.engenharia" target="_blank" class="social-btn instagram">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" fill="white"/>
                </svg>
                <div class="social-tooltip">Siga no Instagram</div>
            </a>
        </div>
        
    </div>

</body>
</html>
