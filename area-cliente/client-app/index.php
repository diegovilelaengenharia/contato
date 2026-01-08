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
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=2.7.4">
    <link rel="stylesheet" href="css/header-premium.css?v=4.0">
    
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
        
        <!-- HEADER PREMIUM v4.0 -->
        <header class="premium-header">
            
            <!-- CLIENT AREA TITLE -->
            <div style="margin-bottom: 20px; border-left: 4px solid #ffc107; padding-left: 10px;">
                <h6 style="font-size: 0.75rem; color: #198754; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin: 0;">Área do Cliente</h6>
                <div style="font-size: 0.7rem; color: #888; margin-top: 2px;">Vilela Engenharia</div>
            </div>

            <div class="ph-profile">
                <?php 
                    $avatarPath = $cliente['foto_perfil'] ?? '';
                    // Admin returns path like "uploads/clientes/ID/foto.jpg"
                    // We are in "area-cliente/client-app/index.php"
                    // We need to go up to "area-cliente/" to find "uploads/"
                    if($avatarPath && !str_starts_with($avatarPath, '../') && !str_starts_with($avatarPath, 'http')) {
                        $avatarPath = '../' . $avatarPath;
                    }
                ?>
                <?php if($avatarPath && file_exists($avatarPath) && !is_dir($avatarPath)): ?>
                    <img src="<?= htmlspecialchars($avatarPath) ?>?v=<?= time() ?>" alt="Perfil" class="ph-avatar">
                <?php else: ?>
                    <div class="ph-avatar">👤</div>
                <?php endif; ?>
                
                <div class="ph-info">
                    <p>Bem-vindo(a),</p>
                    <h1><?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?></h1>
                    <a href="logout.php" class="btn-logout">Sair da conta</a>
                </div>
            </div>

            <div class="ph-details-grid">
                <?php if(!empty($detalhes['endereco_imovel'])): ?>
                    <div class="ph-row" style="margin-bottom:10px;">
                        <div class="ph-icon-box">📍</div>
                        <div style="line-height:1.2;">
                            <div style="font-size:0.7rem; color:#999; text-transform:uppercase; font-weight:700;">Local da Obra</div>
                            <span style="font-weight:600; color:#333; display:block;"><?= htmlspecialchars($detalhes['endereco_imovel']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                     <?php if(!empty($detalhes['contato_tel'])): ?>
                        <div class="ph-row">
                            <div class="ph-icon-box">📞</div>
                            <div style="line-height:1.2;">
                                <div style="font-size:0.7rem; color:#999; text-transform:uppercase; font-weight:700;">Telefone</div>
                                <span style="font-weight:600; color:#333;"><?= htmlspecialchars($detalhes['contato_tel']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($detalhes['cpf_cnpj'])): ?>
                        <div class="ph-row">
                            <div class="ph-icon-box">🆔</div>
                            <div style="line-height:1.2;">
                                <div style="font-size:0.7rem; color:#999; text-transform:uppercase; font-weight:700;">CPF</div>
                                <span style="font-size:0.85rem; color:#333; font-weight:600;"><?= htmlspecialchars($detalhes['cpf_cnpj']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT (With Padding) -->
        <div style="padding: 0 20px;">
            
            <div class="app-action-grid">
                
                <!-- TIMELINE -->
                <a href="timeline.php" class="app-button">
                    <div class="app-btn-icon" style="background:#f0f4f8; color:#5c7c93;">🧭</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Linha do Tempo</span>
                        <span class="app-btn-desc"><?= htmlspecialchars($etapa_atual) ?> (<?= $porcentagem ?>%)</span>
                    </div>
                    <div style="font-weight:800; color:#5c7c93; font-size:1.4rem;">➔</div>
                </a>

                <!-- PENDÊNCIAS -->
                <?php 
                    $has_pendency = $pend_qtd > 0;
                    $p_style = $has_pendency ? "border-left: 6px solid #dba7a7;" : ""; 
                ?>
                <a href="pendencias.php" class="app-button" style="<?= $p_style ?>">
                    <div class="app-btn-icon" style="background:<?= $has_pendency ? '#fdf2f2' : '#fcf8e8' ?>; color:<?= $has_pendency ? '#c25e5e' : '#9e8538' ?>;">⚠️</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title" style="<?= $has_pendency ? 'color:#c25e5e; font-weight:800;' : '' ?>">Pendências</span>
                        <?php if($has_pendency): ?>
                            <span class="app-btn-desc" style="color:#d97575; font-weight:600;"><?= $pend_qtd ?> Ação(ões) Necessária(s)</span>
                        <?php else: ?>
                            <span class="app-btn-desc">Tudo em dia!</span>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- FINANCEIRO -->
                <?php 
                    $has_fin = $fin_qtd > 0;
                ?>
                <a href="financeiro.php" class="app-button">
                    <div class="app-btn-icon" style="background:#eaf4ed; color:#4a8b5c;">💰</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Financeiro</span>
                        <span class="app-btn-desc"><?= $has_fin ? "$fin_qtd Pagamento(s) Pendente(s)" : "Faturas e Recibos" ?></span>
                    </div>
                    <div style="color:#4a8b5c; font-size:1.4rem;">➔</div>
                </a>

                <!-- DOCUMENTOS -->
                <a href="documentos.php" class="app-button">
                    <div class="app-btn-icon" style="background:#fff8e6; color:#a1832d;">📂</div>
                    <div class="app-btn-content">
                        <span class="app-btn-title">Documentos</span>
                        <span class="app-btn-desc">Projetos e Contratos</span>
                    </div>
                    <div style="color:#a1832d; font-size:1.4rem;">➔</div>
                </a> 

            </div>
            
        </div>

        <!-- FOOTER PREMIUM -->
        <footer class="premium-footer" style="padding: 30px 20px; background: #fff; border-top: 1px solid #eee;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                <!-- Logo -->
                <div style="flex-shrink: 0;">
                    <img src="../../assets/logo.png" alt="Vilela Engenharia" style="max-height: 50px; opacity: 1;">
                </div>
                
                <!-- Vertical Divider (Optional, subtle) -->
                <div style="width:1px; height:40px; background:#eee;"></div>

                <!-- Info -->
                <div style="text-align: left;">
                    <span style="display:block; font-size: 0.65rem; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; line-height:1;">Engenheiro Responsável</span>
                    <span style="display:block; font-size: 0.95rem; font-weight: 700; color: #333; margin: 2px 0; line-height:1.2;">Diego T. N. Vilela</span>
                    <span style="display:block; font-size: 0.75rem; color: #666; line-height:1;">CREA 235.474/D</span>
                </div>
            </div>
            <div style="margin-top: 20px; font-size: 0.7rem; color: #ccc; text-align: center;">
                &copy; <?= date('Y') ?> Vilela Engenharia
            </div>
        </footer>

        <!-- FLOATING ACTION BUTTONS -->
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
