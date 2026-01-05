<?php
session_name('CLIENTE_SESSID');
session_start();
require 'db.php'; // Database Connection

// FORCE NO CACHE (Fix for immediate updates)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Verify Login
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
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
    $next_bill = $stmt_fin->fetch(PDO::FETCH_ASSOC);

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
    <link href="style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER -->
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <div style="font-size:0.9rem; color:#666;">Olá,</div>
                <h1 style="color:#146c43; font-size:1.8rem;"><?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?></h1>
            </div>
            <a href="logout.php" style="background:#f8d7da; color:#dc3545; padding:8px 15px; border-radius:30px; font-weight:700; font-size:0.85rem;">
                Sair
            </a>
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
            <button class="app-button" onclick="openModal('modalPendencias')">
                <div class="app-btn-icon" style="background:#fff3cd; color:#856404;">⚠️</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Pendências</span>
                    <?php if($pendencias_count > 0): ?>
                        <span class="app-btn-desc" style="color:#dc3545; font-weight:600;"><?= $pendencias_count ?> item(ns) pendente(s)</span>
                    <?php else: ?>
                        <span class="app-btn-desc">Tudo em dia!</span>
                    <?php endif; ?>
                </div>
                <?php if($pendencias_count > 0): ?>
                    <div style="background:#dc3545; color:white; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:bold;"><?= $pendencias_count ?></div>
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
            </button> <!-- CHANGED DIV TO BUTTON for Accessibility/Touch -->

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
            <!-- CONTENT REMAIN SAME -->
            <!-- STATUS HEADER -->
            <div style="background:linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); color:white; padding:25px; border-radius:16px; margin-bottom:30px; text-align:center; box-shadow:0 10px 20px rgba(20, 108, 67, 0.2);">
                <div style="font-size:0.9rem; opacity:0.8; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Fase Atual</div>
                <div style="font-size:1.6rem; font-weight:800; line-height:1.2; margin-bottom:15px;">
                    <?= htmlspecialchars($etapa_atual) ?>
                </div>
                <!-- PROGRESS BAR -->
                <div style="background:rgba(255,255,255,0.2); height:8px; border-radius:4px; overflow:hidden;">
                     <div style="width:<?= $porcentagem ?>%; height:100%; background:#ffd700; border-radius:4px; transition:width 1s;"></div>
                </div>
            </div>

            <!-- TIMELINE STEPPER -->
            <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Etapas</h3>
            <div class="timeline-container-full" style="padding-left:15px; margin-bottom:40px;">
                <?php 
                    foreach($fases_padrao as $k => $fase): 
                        $is_past = $k < $fase_index;
                        $is_curr = $k === $fase_index;
                        $dot_bg = $is_past ? 'var(--color-primary)' : ($is_curr ? 'white' : '#e9ecef');
                        $dot_border = $is_past ? 'var(--color-primary)' : ($is_curr ? 'var(--color-primary)' : '#ccc');
                        $dot_icon_col = $is_past ? 'white' : 'var(--color-primary)';
                        $text_col = $is_past || $is_curr ? 'var(--text-main)' : 'var(--text-muted)';
                        $font_wt = $is_curr ? '800' : '500';
                ?>
                <div style="display:flex; gap:15px; position:relative; padding-bottom:25px;">
                    <!-- Line -->
                    <?php if($k < count($fases_padrao)-1): ?>
                    <div style="position:absolute; left:11px; top:25px; bottom:0; width:2px; background:<?= $is_past ? 'var(--color-primary)' : '#eee' ?>; z-index:0;"></div>
                    <?php endif; ?>
                    <!-- Dot -->
                    <div style="width:24px; height:24px; border-radius:50%; background:<?= $dot_bg ?>; border:2px solid <?= $dot_border ?>; display:flex; align-items:center; justify-content:center; z-index:1; flex-shrink:0; color:<?= $dot_icon_col ?>; font-size:0.75rem; font-weight:bold;">
                        <?php if($is_past): ?>✓<?php elseif($is_curr): ?>•<?php else: ?> <?php endif; ?>
                    </div>
                    <!-- Text -->
                    <div style="color:<?= $text_col ?>; font-weight:<?= $font_wt ?>; padding-top:2px;">
                        <?= $fase ?>
                        <?php if($is_curr): ?><span style="font-size:0.6rem; background:var(--color-primary); color:white; padding:2px 6px; border-radius:10px; margin-left:8px; vertical-align:middle;">AGORA</span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- HISTORY -->
            <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Histórico</h3>
             <?php
             $stmt_hist = $pdo->prepare("SELECT * FROM processo_movimentacoes WHERE cliente_id = ? ORDER BY data_movimentacao DESC");
             $stmt_hist->execute([$cliente_id]);
             $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

             if(empty($historico)): ?>
                <div class="empty-state">Nenhuma movimentação registrada no histórico.</div>
             <?php else: 
                foreach($historico as $h): ?>
                <div class="history-item" style="border-left:3px solid #ccc; padding:15px; margin-bottom:15px; background:white; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                    <div class="history-date" style="font-size:0.8rem; color:#666; font-weight:700;"><?= date('d/m/Y', strtotime($h['data_movimentacao'])) ?></div>
                    <div class="history-title" style="font-weight:700; color:#333; margin:4px 0;"><?= htmlspecialchars($h['titulo']) ?></div>
                    <div class="history-desc" style="font-size:0.9rem; color:#555;"><?= htmlspecialchars($h['descricao']) ?></div>
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
                    
                    <form action="upload_pendencia_cliente.php" method="POST" enctype="multipart/form-data" class="upload-form">
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
