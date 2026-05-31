<?php
/**
 * View Admin: Arquivos e Google Drive
 */

// Buscar arquivos entregáveis locais
$stmt_entregaveis = $pdo->prepare("SELECT * FROM processo_entregaveis WHERE cliente_id = ? ORDER BY data_upload DESC");
$stmt_entregaveis->execute([$cliente_ativo['id']]);
$entregaveis = $stmt_entregaveis->fetchAll();
?>
<div class="admin-tab-content">
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title">Central de Arquivos</h3>
            <p class="admin-subtitle">Gerencie documentos entregáveis e links do Google Drive.</p>
        </div>
    </div>

    <!-- DOCUMENTOS ENTREGÁVEIS (uploads locais) -->
    <div class="form-card">
        <div class="admin-header-row" style="margin-bottom:18px; padding-bottom:14px;">
            <h4 class="section-title" style="margin:0;">
                <span class="material-symbols-rounded">cloud_upload</span> Documentos para o Cliente
            </h4>
            <button type="button" class="btn-save" onclick="document.getElementById('modalUploadEntregavel').showModal()">
                <span class="material-symbols-rounded">add_circle</span> Novo Arquivo
            </button>
        </div>

        <?php if(count($entregaveis) == 0): ?>
            <div class="docs-empty" style="border:2px dashed var(--color-border); border-radius:var(--radius-sm);">
                Nenhum documento enviado diretamente por aqui ainda.
            </div>
        <?php else: ?>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Título do Documento</th>
                            <th style="text-align:center;">Data</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($entregaveis as $ent): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="material-symbols-rounded" style="color:var(--color-primary);">description</span>
                                    <span style="font-weight:600;"><?= htmlspecialchars($ent['titulo']) ?></span>
                                </div>
                            </td>
                            <td style="text-align:center; color:var(--color-text-subtle); font-size:.85rem;">
                                <?= date('d/m/Y', strtotime($ent['data_upload'])) ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <a href="<?= htmlspecialchars($ent['arquivo_path']) ?>" target="_blank" class="btn-icon" title="Visualizar">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                    <a href="actions/admin/entregavel_delete.php?id=<?= $ent['id'] ?>&cliente_id=<?= $cliente_ativo['id'] ?>"
                                       onclick="return confirm('Deseja realmente excluir este documento?')"
                                       class="btn-icon danger" title="Excluir">
                                        <span class="material-symbols-rounded">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- GOOGLE DRIVE -->
    <div class="form-card">
        <h4 class="section-title"><span class="material-symbols-rounded">link</span> Integração Google Drive</h4>

        <form method="POST">
            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
            <div class="admin-form-group" style="margin-bottom:0;">
                <label class="admin-form-label">Link da Pasta (Backup/Drive)</label>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="text" name="link_drive_pasta" value="<?= htmlspecialchars($detalhes['link_drive_pasta'] ?? '') ?>" class="admin-form-input" placeholder="https://drive.google.com/..." style="flex:1; min-width:220px;">
                    <button type="submit" name="btn_salvar_arquivos" class="btn-save btn-info" style="width:auto;">Salvar Link</button>
                </div>
            </div>
        </form>

        <?php
        if(!empty($detalhes['link_drive_pasta'])):
            $drive_url = $detalhes['link_drive_pasta'];
            $embed_url = $drive_url;
            if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                 $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
            }
        ?>
            <div class="iframe-container visible">
                <iframe src="<?= htmlspecialchars($embed_url) ?>" width="100%" height="500"></iframe>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL UPLOAD ENTREGÁVEL -->
<dialog id="modalUploadEntregavel">
    <div style="background:var(--color-primary); color:#fff; padding:18px 22px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; color:#fff; font-size:1.15rem; display:flex; align-items:center; gap:10px;">
            <span class="material-symbols-rounded">upload_file</span> Enviar Documento
        </h3>
        <button type="button" onclick="document.getElementById('modalUploadEntregavel').close()" class="icon-btn" style="color:#fff;">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    <form action="actions/admin/entregavel_upload.php" method="POST" enctype="multipart/form-data" style="padding:24px;">
        <?= Csrf::getHtmlField() ?>
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">

        <div class="admin-form-group">
            <label class="admin-form-label">Título do Arquivo</label>
            <input type="text" name="titulo_arquivo" placeholder="Ex: Planta Baixa Aprovada" class="admin-form-input">
            <small style="color:var(--color-muted);">Se deixar vazio, usará o nome original do arquivo.</small>
        </div>

        <div id="dropzone_entregavel" onclick="document.getElementById('file_entregavel').click()"
             style="margin:18px 0; border:2px dashed var(--color-border); border-radius:var(--radius); padding:30px; text-align:center; cursor:pointer;">
            <span class="material-symbols-rounded" style="font-size:3rem; color:var(--color-muted); display:block; margin-bottom:10px;">cloud_upload</span>
            <span style="color:var(--color-text-subtle); font-weight:600;">Clique para selecionar o arquivo</span>
            <input type="file" name="arquivo_entregavel" id="file_entregavel" style="display:none;" required
                   onchange="if(this.files.length>0){ var dz=document.getElementById('dropzone_entregavel'); dz.style.borderColor='var(--color-primary)'; dz.querySelector('span:last-child').innerText = this.files[0].name; }">
        </div>

        <button type="submit" class="btn-save" style="width:100%; padding:12px;">Fazer Upload</button>
    </form>
</dialog>
