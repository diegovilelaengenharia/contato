<div class="view-header-simple">
    <h2>Documentos</h2>
    <p>Seus projetos e arquivos na nuvem.</p>
</div>

<div class="files-container fade-in-up">
    <!-- DRIVE EMBED -->
    <div class="drive-card">
        <div class="drive-header">
            <span class="material-symbols-rounded">folder_shared</span>
            <h3>Pasta do Projeto (Google Drive)</h3>
        </div>
        
        <?php if($drive_id): ?>
            <div class="iframe-wrapper">
                <iframe src="https://drive.google.com/embeddedfolderview?id=<?= $drive_id ?>#list" width="100%" height="500" frameborder="0"></iframe>
            </div>
            <a href="<?= $detalhes['link_drive_pasta'] ?>" target="_blank" class="btn-block btn-outline">
                Abrir no Google Drive ↗
            </a>
        <?php else: ?>
            <div class="empty-state">
                <p>Sua pasta ainda não foi vinculada.</p>
                <small>Aguarde a liberação pela engenharia.</small>
            </div>
        <?php endif; ?>
    </div>

    <!-- UPLOADS RECENTES -->
    <h3 class="section-title" style="margin-top:20px;">Uploads Recentes</h3>
    <div class="recent-uploads">
        <?php 
        // Busca arquivos de pendências como "uploads recentes"
        $stmt_arq = $pdo->prepare("SELECT * FROM processo_pendencias_arquivos WHERE pendencia_id IN (SELECT id FROM processo_pendencias WHERE cliente_id=?) ORDER BY data_upload DESC LIMIT 5");
        $stmt_arq->execute([$cliente_id]);
        $recentes = $stmt_arq->fetchAll();

        if(count($recentes) > 0): foreach($recentes as $arq): ?>
            <a href="<?= $arq['arquivo_path'] ?>" target="_blank" class="file-item">
                <div class="file-icon">📄</div>
                <div class="file-info">
                    <strong><?= htmlspecialchars($arq['arquivo_nome']) ?></strong>
                    <small>Enviado em <?= date('d/m/Y', strtotime($arq['data_upload'])) ?></small>
                </div>
                <div class="file-arrow">↓</div>
            </a>
        <?php endforeach; else: ?>
            <p style="color:var(--text-muted); padding:10px;">Nenhum arquivo enviado recentemente.</p>
        <?php endif; ?>
    </div>
</div>
