<div class="view-header-simple">
    <h2>Arquivos</h2>
    <p>Documentos oficiais e projetos.</p>
</div>

<!-- ASSISTANT TIP -->
<div class="assistant-tip fade-in-up">
    <div class="at-icon">☁️</div>
    <div class="at-content">
        <strong>Cofre Digital</strong>
        <p>Todos os documentos oficiais (Alvarás, Habite-se) e projetos aprovados ficam salvos aqui para sempre. Você pode baixar ou consultar quando quiser.</p>
    </div>
</div>

<div class="files-container fade-in-up">
    <!-- DRIVE EMBED -->
    <div class="drive-card">
        <div class="drive-header">
            <span class="material-symbols-rounded" style="font-size:40px; color:#34a853;">folder_data</span>
        </div>
        <h3 style="margin:10px 0 5px 0;">Nuvem do Projeto</h3>
        <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:20px;">
            Acesse todas as pastas (Projetos, Documentos, Taxas) diretamente.
        </p>
        
        <?php if($drive_id): ?>
            <div class="action-buttons-row">
                 <a href="<?= $detalhes['link_drive_pasta'] ?>" target="_blank" class="btn-primary-action">
                    <span class="material-symbols-rounded">open_in_new</span> Abrir Google Drive
                 </a>
            </div>

            <!-- Iframe otimizado -->
            <div class="iframe-wrapper" style="margin-top:20px; border:1px solid var(--border-color); border-radius:10px; overflow:hidden;">
                <iframe src="https://drive.google.com/embeddedfolderview?id=<?= $drive_id ?>#list" width="100%" height="400" frameborder="0"></iframe>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Pasta não vinculada.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
