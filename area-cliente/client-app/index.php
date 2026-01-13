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
    <link rel="stylesheet" href="css/style.css?v=3.0">
    <link rel="stylesheet" href="css/header-premium.css?v=<?= time() ?>">
    
    <style>
        /* Mobile adjustment for Header - Handled mostly in header-premium.css now, but ensuring overrides */
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

        <!-- FLOATING SOCIAL BUTTONS (OFFICIAL LANDING PAGE STYLE) -->
        <div class="floating-buttons" style="z-index: 99999;">
            <a href="https://wa.me/5535984529577?text=Ola%20Diego%20Vilela" class="floating-btn floating-btn--whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
                <svg viewBox="0 0 24 24" role="presentation"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
            </a>
            <a href="https://www.instagram.com/diegovilela.eng/" class="floating-btn floating-btn--instagram" target="_blank" rel="noopener" aria-label="Instagram">
                <svg viewBox="0 0 24 24" role="presentation"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 5A1.5 1.5 0 1 0 10.5 12 1.5 1.5 0 0 0 12 13.5zm4.25-6.75a1 1 0 1 1-1-1 1 1 0 0 1 1 1z"></path></svg>
            </a>
        </div>
        
    </div>

</body>
</html>
