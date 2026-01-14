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

$etapa_atual = ($detalhes && isset($detalhes['etapa_atual'])) ? $detalhes['etapa_atual'] : 'Levantamento de Dados';
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
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    
    <style>
        /* HEADER PORTAL STYLE (PREMIUM WHITE) */
        .portal-header {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .ph-top {
            background: #146C43 !important; /* Matches Footer Dark Green */
            padding: 30px 32px !important;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .ph-logo {
            padding-right: 0;
            margin-right: 0;
            border-right: none;
            display: flex;
            align-items: center;
            background: #ffffff; /* White badge for logo */
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .ph-logo img {
            height: 40px !important; /* Reduced to 40px */
            display: block;
            width: auto;
            object-fit: contain;
            /* Filter removed to show original logo colors */
        }
        .ph-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff !important; /* White Text */
            text-transform: none; /* No uppercase */
            letter-spacing: -0.5px;
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ph-subtitle {
            font-size: 0.7rem; /* Reduced size */
            color: rgba(255,255,255,0.85) !important;
            font-weight: 400;
            letter-spacing: 0.5px;
            font-style: italic; /* Italic */
            display: block;
            align-self: flex-end; /* Align to bottom right of container */
            margin-top: -5px; /* Pull closer to title if needed */
        }
        .ph-subtitle::before {
            content: "“";
            margin-right: 2px;
        }
        .ph-subtitle::after {
            content: "”";
            margin-left: 2px;
        }
        .ph-header-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
        }
        .ph-user-bar {
            background: #fff; /* White background */
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #333;
        }
        .ph-user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .ph-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f8f9fa;
            border: 2px solid var(--color-primary);
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--color-primary);
            position: relative; /* Para o overlay */
            cursor: pointer; /* Indicar clicável */
            overflow: hidden;
        }
        .ph-avatar:hover .ph-avatar-overlay {
            opacity: 1;
        }
        .ph-avatar-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            color: #fff;
            font-size: 1.2rem;
            border-radius: 50%;
        }
        .ph-avatar.loading::after {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: var(--color-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { from {transform: rotate(0deg);} to {transform: rotate(360deg);} }
        .ph-text-group {
            line-height: 1.3;
        }
        .ph-welcome {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
            display: block;
        }
        .ph-username {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            display: block;
        }
        .ph-logout-btn {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545; /* Red for logout */
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid #eee;
        }
        .ph-logout-btn:hover {
            background: #ffebe9;
            border-color: #ffcdd2;
            transform: translateY(-2px);
        }

        /* MOBILE ADAPT */
        @media(max-width: 600px) {
            .portal-header {
                border-radius: 16px;
            }
            .ph-top {
                padding: 16px;
                flex-direction: row; /* FORCE ROW even on mobile */
                align-items: center;
                gap: 16px;
                justify-content: flex-start;
            }
            .ph-logo img { height: 40px; }
            .ph-title { font-size: 0.8rem; }
            .ph-user-bar { padding: 16px; flex-direction: row; }
            .ph-username { font-size: 1rem; }
        }


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

    <div class="app-container" style="padding: 20px;">
        
        <!-- NOVO HARDER: PORTAL DE ACOMPANHAMENTO -->
        <div class="portal-header">
            <div class="ph-top">
                <div class="ph-logo">
                    <!-- Ajustar caminho do logo se necessário -->
                    <img src="../../assets/logo.png?v=<?= time() ?>" alt="Vilela Engenharia" style="height: 40px !important; width: auto !important; max-width: none !important;">
                </div>
                <div class="ph-header-text">
                    <div class="ph-title">Portal Cliente</div>
                </div>
            </div>
            
            <div class="ph-user-bar">
                <div class="ph-user-info">
                    <?php 
                        // Tenta achar avatar físico se não tiver no banco ou como fallback
                        $avatarPath = $cliente['foto_perfil'] ?? '';
                        
                        // Lógica de fallback físico (procura extensions comuns)
                        $possiveis_extensoes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $avatarFisico = '';
                        
                        foreach ($possiveis_extensoes as $ext) {
                            $caminhoTeste = "../uploads/avatars/avatar_{$cliente_id}.{$ext}";
                            if (file_exists($caminhoTeste)) {
                                $avatarFisico = $caminhoTeste;
                                break;
                            }
                        }

                        // Se achou arquivo físico, ele tem prioridade sobre o banco (se o banco for antigo ou nulo)
                        if ($avatarFisico) {
                            $avatarPath = $avatarFisico;
                        } elseif ($avatarPath && !str_starts_with($avatarPath, '../') && !str_starts_with($avatarPath, 'http')) {
                            // Se veio do banco sem caminho relativo, adiciona
                            $avatarPath = '../' . $avatarPath;
                        }
                    ?>
                    <?php if($avatarPath && file_exists($avatarPath) && !is_dir($avatarPath)): ?>
                        <div class="ph-avatar" onclick="document.getElementById('avatarUpload').click()" id="avatarContainer">
                            <img src="<?= htmlspecialchars($avatarPath) ?>?v=<?= time() ?>" id="currentAvatarImg" style="width:100%; height:100%; object-fit:cover;">
                            <div class="ph-avatar-overlay">
                                <span class="material-symbols-rounded">edit</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Fallback visual (Ícone Genérico) -->
                        <!-- Fallback visual (Ícone Genérico) -->
                        <div class="ph-avatar" onclick="document.getElementById('avatarUpload').click()" id="avatarContainer">
                            <span class="material-symbols-rounded">person</span>
                            <div class="ph-avatar-overlay">
                                <span class="material-symbols-rounded">edit</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Hidden Input for Upload -->
                    <input type="file" id="avatarUpload" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                    
                    <div class="ph-text-group">
                        <span class="ph-welcome">Bem-vindo(a),</span>
                        <span class="ph-username"><?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?></span>
                    </div>
                </div>

                <a href="logout.php" class="ph-logout-btn" title="Sair">
                    <span class="material-symbols-rounded">logout</span>
                </a>
            </div>
        </div>



        <!-- MAIN CONTENT -->
        <div style="">
            
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

                <!-- 1. DOCS INICIAIS (CHECKLIST) -->
                <a href="documentos_iniciais.php" class="app-button" style="border: 1px solid #083b30;">
                    <div class="app-btn-icon" style="background:#e6fffa; color:#083b30;">📋</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Abertura de Processo</span>
                        <span class="app-btn-desc">Documentos para Abertura do Processo</span>
                    </div>
                    <div class="app-btn-arrow" style="color:#083b30;">➔</div>
                </a>

                <!-- 2. TIMELINE -->
                <a href="timeline.php" class="app-button" style="border: 1px solid #0f5132;">
                    <div class="app-btn-icon" style="background:#e8f5e9; color:#0f5132;">🧭</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Linha do Tempo</span>
                        <div class="progress-mini" style="margin-top:5px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden; width:100px;">
                            <div class="bar" style="width: <?= $porcentagem ?>%; height:100%; background:#0f5132;"></div>
                        </div>
                        <span class="app-btn-desc" style="margin-top:5px;">
                            <?= htmlspecialchars($etapa_atual) ?> - <?= $porcentagem ?>%
                        </span>
                    </div>
                    <div class="app-btn-arrow" style="color:#0f5132;">➔</div>
                </a>

                <!-- 3. PENDÊNCIAS -->
                <?php 
                    // Lógica de Cores Solicitada:
                    // Se pendências > 0: Fundo Vermelho Claro
                    // Se pendências = 0: Fundo Verde
                    
                    if ($pend_qtd > 0) {
                        $p_border = '#dc3545'; // Vermelho
                        $p_bg_btn = '#ffebe9'; // Vermelho Claro (Fundo do Card Inteiro?) - User pediu "botao com cor de fundo", assumindo card
                        $p_icon_bg = '#f8d7da';
                        $p_icon_color = '#721c24';
                        $p_icon = '⚠️';
                        $p_arrow = '#dc3545';
                    } else {
                        $p_border = '#198754'; // Verde
                        $p_bg_btn = '#d1e7dd'; // Verde Claro / Sucesso
                        $p_icon_bg = '#a3cfbb';
                        $p_icon_color = '#0f5132';
                        $p_icon = '✅';
                        $p_arrow = '#198754';
                    }
                ?>
                <a href="pendencias.php" class="app-button" style="border: 1px solid <?= $p_border ?>; background-color: <?= $p_bg_btn ?>;">
                    <div class="app-btn-icon" style="background:<?= $p_icon_bg ?>; color:<?= $p_icon_color ?>;"><?= $p_icon ?></div>
                    <div class="app-btn-content">
                        <span class="app-btn-title" style="color: #333;">Pendências</span>
                        <span class="app-btn-desc" style="color:#444;">Pendência da Triagem ou dos Analistas</span>
                        <?php if($pend_qtd > 0): ?>
                             <div style="font-size: 0.75rem; color: #dc3545; font-weight: 600; margin-top: 4px;">
                                Atenção: <?= $pend_qtd ?> pendência(s)
                             </div>
                        <?php endif; ?>
                    </div>
                    <?php if($pend_qtd > 0): ?>
                        <span class="badge-count" style="background:#dc3545;"><?= $pend_qtd ?></span>
                    <?php else: ?>
                         <div class="app-btn-arrow" style="color:<?= $p_arrow ?>;">➔</div>
                    <?php endif; ?>
                </a>

                <!-- 4. FINANCEIRO -->
                <?php 
                    // Lógica de Cores Solicitada:
                    // Se pendências > 0: Fundo Amarelo Claro
                    // Se pendências = 0: Fundo Verde
                    
                    if ($fin_qtd > 0) {
                        $f_border = '#ffc107'; // Amarelo
                        $f_bg_btn = '#fff3cd'; // Amarelo Claro
                        $f_icon_bg = '#ffe69c';
                        $f_icon_color = '#856404';
                        $f_icon = '💰';
                        $f_arrow = '#856404';
                    } else {
                        $f_border = '#198754'; // Verde
                        $f_bg_btn = '#d1e7dd'; // Verde Claro
                        $f_icon_bg = '#a3cfbb';
                        $f_icon_color = '#0f5132';
                        $f_icon = '✅';
                        $f_arrow = '#198754';
                    }
                ?>
                <a href="financeiro.php" class="app-button" style="border: 1px solid <?= $f_border ?>; background-color: <?= $f_bg_btn ?>;">
                    <div class="app-btn-icon" style="background:<?= $f_icon_bg ?>; color:<?= $f_icon_color ?>;"><?= $f_icon ?></div>
                    <div class="app-btn-content">
                        <span class="app-btn-title" style="color: #333;">Financeiro</span>
                        <span class="app-btn-desc" style="color:#444;">Honorarios, Taxas, Multas, etc</span>
                        <?php if($fin_qtd > 0): ?>
                             <div style="font-size: 0.75rem; color: #856404; font-weight: 600; margin-top: 4px;">
                                <?= $fin_qtd ?> item(ns) pendente(s)
                             </div>
                        <?php endif; ?>
                    </div>
                    <?php if($fin_qtd > 0): ?>
                        <span class="badge-count" style="background:#ffc107; color:#856404;"><?= $fin_qtd ?></span>
                    <?php else: ?>
                        <div class="app-btn-arrow" style="color:<?= $f_arrow ?>;">➔</div>
                    <?php endif; ?>
                </a>
                
                <!-- 5. DOCUMENTOS -->
                <a href="documentos.php" class="app-button" style="border: 1px solid #052c65;">
                    <div class="app-btn-icon" style="background:#cfe2ff; color:#052c65;">📂</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Documentos Finais</span>
                        <span class="app-btn-desc">Acesso aos documentos digitais</span>
                    </div>
                    <div class="app-btn-arrow" style="color:#052c65;">➔</div>
                </a>

                <!-- 6. RESUMO (MOVED & RESTYLED) -->
                <!-- 6. RESUMO (SIMPLIFIED) -->
                <a href="../../area-cliente/relatorio_cliente.php?id=<?= $cliente['id'] ?>" target="_blank" class="download-card">
                    <div class="dc-info">
                        <div class="dc-icon">🖨️</div>
                        <div class="dc-text">
                            <h4>Visão Geral do Processo</h4>
                            <p>Clique para baixar o PDF</p>
                        </div>
                    </div>
                    <div class="dc-action">
                        <span class="material-symbols-rounded">download</span>
                    </div>
                </a>

            </div>
            
        </div>

        <!-- FOOTER PREMIUM -->
        <?php include 'includes/footer.php'; ?>

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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Reveal effect
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.app-button, .portal-header').forEach(el => {
                el.classList.add('reveal');
                observer.observe(el);
            });
            
            // Auto Update Year
            document.getElementById('year').textContent = new Date().getFullYear();
        });

        // AVATAR UPLOAD LOGIC
        function uploadAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const formData = new FormData();
                formData.append('avatar', file);

                const container = document.getElementById('avatarContainer');
                container.classList.add('loading'); // Show Spinner

                fetch('actions/upload_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    container.classList.remove('loading');
                    if (data.success) {
                        // Update Image
                        const timestamp = new Date().getTime();
                        let imgHtml = `<img src="${data.newPath}?v=${timestamp}" id="currentAvatarImg" style="width:100%; height:100%; object-fit:cover;">
                                       <div class="ph-avatar-overlay"><span class="material-symbols-rounded">edit</span></div>`;
                        container.innerHTML = imgHtml;
                        
                        // Optional: Show toast success
                        // alert('Avatar atualizado!'); 
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    container.classList.remove('loading');
                    console.error('Error:', error);
                    alert('Erro ao enviar a imagem.');
                });
            }
        }
    </script>
</body>
</html>
