<?php
// includes/client_modals.php
?>

<!-- 1. MODAL LINHA DO TEMPO -->
<dialog id="modalTimeline" class="app-modal">
    <div class="app-modal-header">
        <h2>⏳ Linha do Tempo</h2>
        <button onclick="document.getElementById('modalTimeline').close()" class="btn-close">✕</button>
    </div>
    <div class="app-modal-content">
        
        <!-- HEADER DO STATUS ATUAL -->
        <div style="background:linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); color:white; padding:25px; border-radius:16px; margin-bottom:30px; text-align:center; box-shadow:0 10px 20px rgba(20, 108, 67, 0.2);">
            <div style="font-size:0.9rem; opacity:0.8; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Fase Atual</div>
            <div style="font-size:1.6rem; font-weight:800; line-height:1.2; margin-bottom:15px;">
                <?php
                // Get Phase from Session or DB Logic
                $fases_pd = [
                    "Levantamento de Dados",
                    "Desenvolvimento de Projetos",
                    "Aprovação na Prefeitura",
                    "Pagamento de Taxas",
                    "Emissão de Alvará",
                    "Entrega de Projetos"
                ];
                $db_cli_tl = $pdo->prepare("SELECT etapa FROM clientes WHERE id=?");
                $db_cli_tl->execute([$_SESSION['cliente_id'] ?? 0]);
                $res_cli_tl = $db_cli_tl->fetch();
                $etapa_raw = $res_cli_tl['etapa'] ?? $fases_pd[0];
                
                // Calculate percentage based on phase index
                $idx_atual = array_search($etapa_raw, $fases_pd);
                if($idx_atual === false) $idx_atual = 0;
                $perc_val = round((($idx_atual + 1) / count($fases_pd)) * 100);
                
                echo htmlspecialchars($etapa_raw);
                ?>
            </div>
            
            <!-- PROGRESS BAR -->
            <div style="background:rgba(255,255,255,0.2); height:8px; border-radius:4px; overflow:hidden;">
                 <div style="width:<?= $perc_val ?>%; height:100%; background:#ffd700; border-radius:4px; transition:width 1s;" id="modalProgressFill"></div>
            </div>
        </div>

        <!-- COMPLETE VERTICAL STANDARDIZED TIMELINE -->
        <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Etapas do Processo</h3>
        <div class="timeline-container-full" style="padding-left:15px; margin-bottom:40px;">
            <?php 
                foreach($fases_pd as $k => $fase): 
                    $is_past = $k < $idx_atual;
                    $is_curr = $k === $idx_atual;
                    
                    $dot_bg = $is_past ? 'var(--color-primary)' : ($is_curr ? 'white' : '#e9ecef');
                    $dot_border = $is_past ? 'var(--color-primary)' : ($is_curr ? 'var(--color-primary)' : '#ccc');
                    $dot_icon_col = $is_past ? 'white' : 'var(--color-primary)';
                    $text_col = $is_past || $is_curr ? 'var(--text-main)' : 'var(--text-muted)';
                    $font_wt = $is_curr ? '800' : '500';
            ?>
            <div style="display:flex; gap:15px; position:relative; padding-bottom:25px;">
                <!-- Connector Line -->
                <?php if($k < count($fases_pd)-1): ?>
                <div style="position:absolute; left:11px; top:25px; bottom:0; width:2px; background:<?= $is_past ? 'var(--color-primary)' : '#eee' ?>; z-index:0;"></div>
                <?php endif; ?>

                <!-- Dot -->
                <div style="width:24px; height:24px; border-radius:50%; background:<?= $dot_bg ?>; border:2px solid <?= $dot_border ?>; display:flex; align-items:center; justify-content:center; z-index:1; flex-shrink:0; color:<?= $dot_icon_col ?>; font-size:0.75rem; font-weight:bold;">
                    <?php if($is_past): ?>✓<?php elseif($is_curr): ?>•<?php else: ?> <?php endif; ?>
                </div>

                <!-- Text -->
                <div style="color:<?= $text_col ?>; font-weight:<?= $font_wt ?>; padding-top:2px;">
                    <?= $fase ?>
                    <?php if($is_curr): ?><span style="font-size:0.7rem; background:var(--color-primary); color:white; padding:2px 6px; border-radius:10px; margin-left:8px; vertical-align:middle;">EM ANDAMENTO</span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- REPORT: DETAILED HISTORY -->
        <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Relatório de Histórico</h3>
        <div class="timeline-container-history">
            <?php
             $stmt_hist = $pdo->prepare("SELECT * FROM processo_movimentacoes WHERE cliente_id = ? ORDER BY data_movimentacao DESC");
             $stmt_hist->execute([$_SESSION['cliente_id'] ?? 0]);
             $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

             if(empty($historico)): ?>
                <div class="empty-state">Nenhuma movimentação registrada.</div>
             <?php else: 
                foreach($historico as $h): ?>
                <div class="history-item" style="border-left:3px solid #ccc;">
                    <div class="history-date"><?= date('d/m/Y', strtotime($h['data_movimentacao'])) ?></div>
                    <div class="history-title"><?= htmlspecialchars($h['titulo']) ?></div>
                    <div class="history-desc"><?= htmlspecialchars($h['descricao']) ?></div>
                </div>
                <?php endforeach; 
             endif; ?>
        </div>
        
    </div>
</dialog>

<!-- 2. MODAL PENDÊNCIAS -->
<dialog id="modalPendencias" class="app-modal">
    <div class="app-modal-header">
        <h2>⚠️ Pendências</h2>
        <button onclick="document.getElementById('modalPendencias').close()" class="btn-close">✕</button>
    </div>
    <div class="app-modal-content">
        <p class="modal-intro">Resolva itens pendentes para seu processo avançar.</p>
        
        <?php
        $stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido' ORDER BY data_criacao DESC");
        $stmt_pend->execute([$_SESSION['cliente_id'] ?? 0]);
        $pendencias = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);

        if(empty($pendencias)): ?>
            <div class="success-state">
                <span style="font-size:3rem;">✅</span>
                <p>Tudo certo! Nenhuma pendência.</p>
            </div>
        <?php else: 
            foreach($pendencias as $p): ?>
            <div class="pendency-card">
                <div class="pendency-title"><?= htmlspecialchars($p['titulo']) ?></div>
                <div class="pendency-desc"><?= htmlspecialchars($p['descricao']) ?></div>
                
                <!-- Upload Form -->
                <form action="upload_pendencia_cliente.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="pendencia_id" value="<?= $p['id'] ?>">
                    <label class="btn-upload">
                        <input type="file" name="arquivo" required onchange="this.form.submit()">
                        📎 Anexar Resolução
                    </label>
                </form>
            </div>
            <?php endforeach; 
        endif; ?>
    </div>
</dialog>

<!-- 3. MODAL FINANCEIRO -->
<dialog id="modalFinanceiro" class="app-modal">
    <div class="app-modal-header">
        <h2>💰 Financeiro</h2>
        <button onclick="document.getElementById('modalFinanceiro').close()" class="btn-close">✕</button>
    </div>
    <div class="app-modal-content">
        <?php
        // Summations
        $stmt_fin = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
        $stmt_fin->execute([$_SESSION['cliente_id'] ?? 0]);
        $lancamentos = $stmt_fin->fetchAll(PDO::FETCH_ASSOC);

        $total_pago = 0;
        $total_pendente = 0;

        foreach($lancamentos as $l) {
            if($l['status'] == 'pago') $total_pago += $l['valor'];
            else $total_pendente += $l['valor'];
        }
        ?>

        <div class="finance-summary">
            <div class="fin-card pago">
                <span>Total Pago</span>
                <strong>R$ <?= number_format($total_pago, 2, ',', '.') ?></strong>
            </div>
            <div class="fin-card pendente">
                <span>A Pagar</span>
                <strong>R$ <?= number_format($total_pendente, 2, ',', '.') ?></strong>
            </div>
        </div>

        <h3 style="margin-top:20px; font-size:1.1rem; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Lançamentos</h3>
        <div class="finance-list" style="margin-top:15px;">
            <?php if(empty($lancamentos)): ?>
                <div class="empty-state">
                    <span style="font-size:2rem; display:block; margin-bottom:10px;">💸</span>
                    Nenhum registro financeiro encontrado.
                </div>
            <?php else: 
                foreach($lancamentos as $l): 
                    $is_pago = $l['status'] == 'pago';
                    $is_atrasado = !$is_pago && ($l['data_vencimento'] < date('Y-m-d'));
                    
                    $status_class = $is_pago ? 'st-pago' : ($is_atrasado ? 'st-atra' : 'st-pend');
                    $status_label = $is_pago ? 'Pago' : ($is_atrasado ? 'Atrasado' : 'Aberto');
                    $date_label = date('d/m/Y', strtotime($l['data_vencimento']));
            ?>
                <div class="fin-item <?= $status_class ?>" style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div class="fin-info">
                        <div class="fin-title" style="font-weight:600; color:#333;"><?= htmlspecialchars($l['descricao']) ?></div>
                        <div class="fin-date" style="font-size:0.85rem; color:#777; margin-top:2px;">
                            Vencimento: <strong><?= $date_label ?></strong>
                        </div>
                    </div>
                    <div class="fin-value" style="text-align:right;">
                        <span style="display:block; font-weight:700;">R$ <?= number_format($l['valor'], 2, ',', '.') ?></span>
                        <span class="fin-badge" style="font-size:0.7rem; padding:3px 8px; border-radius:12px; display:inline-block; margin-top:4px; font-weight:600; text-transform:uppercase;">
                            <?= $status_label ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</dialog>

<!-- 4. MODAL DOCUMENTOS (Drive) -->
<dialog id="modalDocumentos" class="app-modal">
    <div class="app-modal-header">
        <h2>📂 Documentos</h2>
        <button onclick="document.getElementById('modalDocumentos').close()" class="btn-close">✕</button>
    </div>
    <div class="app-modal-content" style="padding:0; height:80vh;">
        <?php
        // Fetch Drive Link
        $stmt_drive = $pdo->prepare("SELECT drive_link FROM processo_detalhes WHERE cliente_id = ?");
        $stmt_drive->execute([$_SESSION['cliente_id'] ?? 0]);
        $drive_data = $stmt_drive->fetch();
        $drive_url = $drive_data['drive_link'] ?? '';
        
        $embed_url = "";
        if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
            $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
        } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
             $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
        }
        ?>

        <?php if($embed_url): ?>
            <iframe src="<?= htmlspecialchars($embed_url) ?>" style="width:100%; height:100%; border:none;"></iframe>
        <?php else: ?>
            <div class="empty-state" style="padding:40px;">
                Pasta de documentos ainda não vinculada.
            </div>
        <?php endif; ?>
    </div>
</dialog>
