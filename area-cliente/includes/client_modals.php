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
        <!-- Progress Bar (Dynamic) -->
        <div class="progress-container">
            <div class="progress-label">
                <span>Progresso Geral</span>
                <span id="progressText">0%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width: 0%;" id="progressFill"></div>
            </div>
        </div>

        <!-- History List -->
        <div class="history-list">
             <!-- PHP Loop will populate this -->
             <?php
             $stmt_hist = $pdo->prepare("SELECT * FROM processo_movimentacoes WHERE cliente_id = ? ORDER BY data_movimentacao DESC");
             $stmt_hist->execute([$_SESSION['cliente_id'] ?? 0]);
             $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

             if(empty($historico)): ?>
                <div class="empty-state">Nenhuma movimentação registrada.</div>
             <?php else: 
                foreach($historico as $h): ?>
                <div class="history-item">
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
                <span>Pago</span>
                <strong>R$ <?= number_format($total_pago, 2, ',', '.') ?></strong>
            </div>
            <div class="fin-card pendente">
                <span>A Pagar</span>
                <strong>R$ <?= number_format($total_pendente, 2, ',', '.') ?></strong>
            </div>
        </div>

        <h3 style="margin-top:20px; font-size:1rem; color:#666;">Próximos Vencimentos</h3>
        <div class="finance-list">
            <?php if(empty($lancamentos)): ?>
                <div class="empty-state">Nenhum lançamento financeiro.</div>
            <?php else: 
                foreach($lancamentos as $l): 
                    $status_class = $l['status'] == 'pago' ? 'st-pago' : ($l['data_vencimento'] < date('Y-m-d') ? 'st-atra' : 'st-pend');
                    $status_label = $l['status'] == 'pago' ? 'Pago' : ($l['data_vencimento'] < date('Y-m-d') ? 'Em Atraso' : 'Aberto');
            ?>
                <div class="fin-item <?= $status_class ?>">
                    <div class="fin-info">
                        <div class="fin-title"><?= htmlspecialchars($l['descricao']) ?></div>
                        <div class="fin-date">Vence: <?= date('d/m/Y', strtotime($l['data_vencimento'])) ?></div>
                    </div>
                    <div class="fin-value">
                        R$ <?= number_format($l['valor'], 2, ',', '.') ?>
                        <div class="fin-badge"><?= $status_label ?></div>
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
