<?php
session_name('CLIENTE_SESSID');
session_start();
require '../db.php'; // Database Connection

// FORCE NO CACHE (Fix for immediate updates)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Verify Login
if (!isset($_SESSION['cliente_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch Client Info
try {
    $cliente_id = $_SESSION['cliente_id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();

    // 1. PROGRESS CALCULATION
    $fases_padrao = [
        "Levantamento de Dados",
        "Desenvolvimento de Projetos",
        "Aprovação na Prefeitura",
        "Pagamento de Taxas",
        "Emissão de Alvará",
        "Entrega de Projetos"
    ];
    $etapa_atual = $cliente['etapa'] ?? 'Levantamento de Dados';
    $total_fases = count($fases_padrao);
    $fase_index = array_search($etapa_atual, $fases_padrao);
    $porcentagem = ($fase_index !== false && $fase_index >= 0) ? round((($fase_index + 1) / $total_fases) * 100) : 0;

    // 2. FETCH PENDENCIES COUNT
    $stmt_pend = $pdo->prepare("SELECT COUNT(*) FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido'");
    $stmt_pend->execute([$cliente_id]);
    $pendencias_count = $stmt_pend->fetchColumn();

    // 3. FETCH NEXT PAYMENT
    $stmt_fin = $pdo->prepare("SELECT valor, data_vencimento FROM processo_financeiro WHERE cliente_id = ? AND status != 'pago' ORDER BY data_vencimento ASC LIMIT 1");
    $stmt_fin->execute([$cliente_id]);
    $next_bill = $stmt_fin->fetch();
    
    // 4. FETCH EXTRA DETAILS (Address, CPF, Phone)
    $stmt_det = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
    $stmt_det->execute([$cliente_id]);
    $detalhes = $stmt_det->fetch();

} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Área do Cliente | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        /* CRITICAL MODAL FIX - INLINED FOR RELIABILITY */
        .app-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(4px);
        }
        .app-modal.active {
            display: flex !important;
            opacity: 1 !important;
        }
        .app-modal-content {
            background: white;
            width: 100%; max-width: 500px;
            max-height: 90vh; overflow-y: auto;
            border-radius: 20px; padding: 25px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            display: flex; flex-direction: column;
        }
        .app-modal.active .app-modal-content {
            transform: translateY(0);
        }
        .app-modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;
        }
        .btn-close {
            background: #f1f1f1; border: none; width: 36px; height: 36px;
            border-radius: 50%; font-size: 1.2rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER PROFILE -->
        <header style="display:flex; align-items:center; gap:15px; margin-bottom:30px; background:white; padding:15px; border-radius:16px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
            <!-- Avatar -->
            <div style="flex-shrink:0;">
                <?php 
                    $avatarPath = $cliente['foto_perfil'] ?? '';
                    if($avatarPath && file_exists($avatarPath)): 
                ?>
                    <img src="<?= htmlspecialchars($avatarPath) ?>?v=<?= time() ?>" alt="Perfil" style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid var(--color-primary);">
                <?php else: ?>
                    <div style="width:60px; height:60px; border-radius:50%; background:#e9ecef; color:#aaa; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">👤</div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div style="flex:1; min-width:0;"> <!-- min-width 0 for text truncate -->
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <div style="font-size:0.85rem; color:#666;">Olá,</div>
                        <h1 style="color:#146c43; font-size:1.4rem; margin:0; line-height:1.2;"><?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?></h1>
                    </div>
                     <a href="logout.php" style="background:#fffcfc; border:1px solid #f8d7da; color:#dc3545; padding:5px 10px; border-radius:8px; font-weight:600; font-size:0.75rem; text-decoration:none;">
                        Sair
                    </a>
                </div>
                
                <div style="margin-top:8px; font-size:0.8rem; color:#555; display:flex; flex-direction:column; gap:2px;">
                    <?php if(!empty($detalhes['endereco_imovel'])): ?>
                        <div style="display:flex; align-items:center; gap:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <span>📍</span> <span style="font-weight:600;">Obra:</span> <?= htmlspecialchars($detalhes['endereco_imovel']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <?php if(!empty($detalhes['cpf_cnpj'])): ?>
                            <div style="display:flex; align-items:center; gap:4px;">
                                <span>🆔</span> <?= htmlspecialchars($detalhes['cpf_cnpj']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($detalhes['contato_tel'])): ?>
                            <div style="display:flex; align-items:center; gap:4px;">
                                <span>📞</span> <?= htmlspecialchars($detalhes['contato_tel']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN MENU GRID (Vertical "App" Style) -->
        <div class="app-action-grid">
            
            <!-- 1. TIMELINE -->
            <button class="app-button" onclick="openModal('modalTimeline')">
                <div class="app-btn-icon" style="background:#e3f2fd; color:#0d47a1;">⏳</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Linha do Tempo</span>
                    <span class="app-btn-desc"><?= htmlspecialchars($etapa_atual) ?></span>
                </div>
                <div style="font-weight:800; color:#0d47a1;"><?= $porcentagem ?>%</div>
            </button>

            <!-- 2. PENDÊNCIAS -->
            <?php 
                $has_pendency = $pendencias_count > 0;
                $p_style = $has_pendency ? "background:#fff5f5; border:2px solid #dc3545;" : "";
                $p_icon_bg = $has_pendency ? "#dc3545" : "#fff3cd";
                $p_icon_col = $has_pendency ? "white" : "#856404";
            ?>
            <button class="app-button" onclick="openModal('modalPendencias')" style="<?= $p_style ?>">
                <div class="app-btn-icon" style="background:<?= $p_icon_bg ?>; color:<?= $p_icon_col ?>;">⚠️</div>
                <div class="app-btn-content">
                    <span class="app-btn-title" style="<?= $has_pendency ? 'color:#dc3545; font-weight:700;' : '' ?>">Pendências</span>
                    <?php if($has_pendency): ?>
                        <span class="app-btn-desc" style="color:#dc3545; font-weight:600;"><?= $pendencias_count ?> Ação(ões) Necessária(s)</span>
                    <?php else: ?>
                        <span class="app-btn-desc">Tudo em dia!</span>
                    <?php endif; ?>
                </div>
                <?php if($has_pendency): ?>
                    <div style="background:#dc3545; color:white; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:bold; box-shadow:0 2px 5px rgba(220,53,69,0.4);"><?= $pendencias_count ?></div>
                <?php else: ?>
                    <div style="color:#198754; font-size:1.2rem;">✅</div>
                <?php endif; ?>
            </button>

            <!-- 3. FINANCEIRO -->
            <button class="app-button" onclick="openModal('modalFinanceiro')">
                <div class="app-btn-icon" style="background:#d1e7dd; color:#146c43;">💰</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Financeiro</span>
                    <?php if($next_bill): ?>
                        <span class="app-btn-desc">
                            Próx: <?= date('d/m', strtotime($next_bill['data_vencimento'])) ?> 
                            (R$ <?= number_format($next_bill['valor'], 2, ',', '.') ?>)
                        </span>
                    <?php else: ?>
                        <span class="app-btn-desc">Nenhum pagamento futuro</span>
                    <?php endif; ?>
                </div>
            </button>

            <!-- 4. DOCUMENTOS -->
            <button class="app-button" onclick="openModal('modalDocumentos')">
                <div class="app-btn-icon" style="background:#e0e0e0; color:#333;">📂</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Documentos</span>
                    <span class="app-btn-desc">Acessar Projetos</span>
                </div>
            </button> 

        </div>

        <!-- DEVELOPER CREDIT -->
        <div style="text-align:center; margin-top:50px; opacity:0.6; font-size:0.8rem;">
            Desenvolvido por <strong>Diego T. N. Vilela</strong>
        </div>

    </div>

    <!-- ================================================================= -->
    <!-- MODALS (LOGIC INLINED FOR STABILITY) -->
    <!-- ================================================================= -->

    <!-- 1. MODAL LINHA DO TEMPO -->
    <div id="modalTimeline" class="app-modal">
        <div class="app-modal-header">
            <h2>⏳ Linha do Tempo</h2>
            <button onclick="closeModal('modalTimeline')" class="btn-close">✕</button>
        </div>
        <div class="app-modal-content">
            
            <!-- DETALHES DO PROCESSO (NOVO) -->
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


    <!-- 2. MODAL PENDÊNCIAS -->
    <div id="modalPendencias" class="app-modal">
        <div class="app-modal-header">
            <h2>⚠️ Pendências</h2>
            <button onclick="closeModal('modalPendencias')" class="btn-close">✕</button>
        </div>
        <div class="app-modal-content">
            <?php
            $stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido' ORDER BY data_criacao DESC");
            $stmt_pend->execute([$cliente_id]);
            $pendencias = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);

            if(empty($pendencias)): ?>
                <div class="success-state" style="text-align:center; padding:40px;">
                    <span style="font-size:3rem;">✅</span>
                    <p style="margin-top:15px; font-weight:600;">Tudo certo! Nenhuma pendência.</p>
                </div>
            <?php else: 
                foreach($pendencias as $p): ?>
                <div class="pendency-card" style="border-left:5px solid #ffc107; background:white; padding:20px; border-radius:12px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <div style="font-weight:700; font-size:1.1rem; margin-bottom:5px;"><?= htmlspecialchars($p['titulo']) ?></div>
                    <div style="color:#666; margin-bottom:15px;"><?= htmlspecialchars($p['descricao']) ?></div>
                    
                    <form action="actions/upload_pendencia.php" method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="pendencia_id" value="<?= $p['id'] ?>">
                        <label class="btn-upload" style="display:block; width:100%; background:#f8f9fa; border:2px dashed #146c43; color:#146c43; padding:12px; text-align:center; border-radius:8px; cursor:pointer;">
                            <input type="file" name="arquivo" required onchange="this.form.submit()" style="display:none;">
                            📎 Anexar Resolução (Foto/PDF)
                        </label>
                    </form>
                </div>
                <?php endforeach; 
            endif; ?>
        </div>
    </div>


    <!-- 3. MODAL FINANCEIRO -->
    <div id="modalFinanceiro" class="app-modal">
        <div class="app-modal-header">
            <h2>💰 Financeiro</h2>
            <button onclick="closeModal('modalFinanceiro')" class="btn-close">✕</button>
        </div>
        <div class="app-modal-content">
            <?php
            $stmt_fin = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
            $stmt_fin->execute([$cliente_id]);
            $lancamentos = $stmt_fin->fetchAll(PDO::FETCH_ASSOC);

            $total_pago = 0; $total_pendente = 0;
            foreach($lancamentos as $l) { if($l['status'] == 'pago') $total_pago += $l['valor']; else $total_pendente += $l['valor']; }
            ?>
            <div class="finance-summary" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                <div style="background:#d1e7dd; padding:15px; border-radius:12px; text-align:center;">
                    <span style="font-size:0.8rem; color:#0f5132;">Pago</span>
                    <strong style="display:block; font-size:1.1rem; color:#0f5132;">R$ <?= number_format($total_pago, 2, ',', '.') ?></strong>
                </div>
                <div style="background:#fff3cd; padding:15px; border-radius:12px; text-align:center;">
                    <span style="font-size:0.8rem; color:#856404;">A Pagar</span>
                    <strong style="display:block; font-size:1.1rem; color:#856404;">R$ <?= number_format($total_pendente, 2, ',', '.') ?></strong>
                </div>
            </div>

            <h3 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px;">Lançamentos</h3>
            <?php if(empty($lancamentos)): ?>
                <div class="empty-state">Nenhum registro financeiro.</div>
            <?php else: 
                foreach($lancamentos as $l): 
                    $is_pago = $l['status'] == 'pago';
                    $status_color = $is_pago ? '#d1e7dd' : '#fff3cd';
                    $status_text = $is_pago ? '#0f5132' : '#856404';
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; background:white; padding:15px; border-radius:12px; margin-bottom:10px; box-shadow:0 2px 5px rgba(0,0,0,0.05); border-left:4px solid <?= $is_pago ? '#198754' : '#ffc107' ?>;">
                    <div>
                        <div style="font-weight:600;"><?= htmlspecialchars($l['descricao']) ?></div>
                        <div style="font-size:0.8rem; color:#666;">Vence: <?= date('d/m/Y', strtotime($l['data_vencimento'])) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700;">R$ <?= number_format($l['valor'], 2, ',', '.') ?></div>
                        <span style="font-size:0.7rem; background:<?= $status_color ?>; color:<?= $status_text ?>; padding:2px 8px; border-radius:10px;"><?= $is_pago ? 'PAGO' : 'ABERTO' ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>


    <!-- 4. MODAL DOCUMENTOS -->
    <div id="modalDocumentos" class="app-modal">
        <div class="app-modal-header">
            <h2>📂 Documentos</h2>
            <button onclick="closeModal('modalDocumentos')" class="btn-close">✕</button>
        </div>
        <div class="app-modal-content" style="padding:0; height:100%;">
            <?php
            $stmt_drive = $pdo->prepare("SELECT drive_link FROM processo_detalhes WHERE cliente_id = ?");
            $stmt_drive->execute([$cliente_id]);
            $drive = $stmt_drive->fetch();
            
            $embed = "";
            if (!empty($drive['drive_link'])) {
                if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $drive['drive_link'], $matches)) {
                    $embed = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
                } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $drive['drive_link'], $matches)) {
                     $embed = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
                }
            }
            ?>
            <?php if($embed): ?>
                <iframe src="<?= htmlspecialchars($embed) ?>" style="width:100%; height:90%; border:none;"></iframe>
            <?php else: ?>
                <div style="padding:40px; text-align:center; color:#666;">
                    Pasta de documentos não vinculada.<br>Entre em contato conosco.
                </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- JAVASCRIPT GLOBAL -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                // modal.showModal(); // REMOVED DIALOG API
                modal.classList.add('active'); // ADD CLASS
                document.body.style.overflow = 'hidden'; 
                
                // Trigger Progress bar if timeline
                if(id === 'modalTimeline') {
                     setTimeout(() => {
                        const fill = document.getElementById('progressFill');
                        const text = document.getElementById('progressText'); // If exists
                        if(fill) fill.style.width = '<?= $porcentagem ?>%';
                    }, 100);
                }
                
            } else {
                console.error('Modal não encontrado:', id);
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                // modal.close(); // REMOVED DIALOG API
                modal.classList.remove('active'); // REMOVE CLASS
                document.body.style.overflow = ''; 
            }
        }
    </script>

</body>
</html>
