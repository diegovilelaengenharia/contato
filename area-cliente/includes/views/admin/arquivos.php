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
            <h3 class="admin-title">📂 Central de Arquivos</h3>
            <p class="admin-subtitle">Gerencie documentos entregáveis e links do Google Drive.</p>
        </div>
    </div>

    <!-- SEÇÃO: DOCUMENTOS ENTREGÁVEIS (UPLOADS LOCAIS) -->
    <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h4 style="margin:0; color:#1e5d42; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-rounded">cloud_upload</span> Documentos para o Cliente
            </h4>
            <button type="button" onclick="document.getElementById('modalUploadEntregavel').showModal()" style="background:#198754; color:white; border:none; padding:8px 20px; border-radius:30px; font-weight:700; cursor:pointer; font-size:0.85rem; display:flex; align-items:center; gap:5px;">
                <span>➕</span> Novo Arquivo
            </button>
        </div>

        <?php if(count($entregaveis) == 0): ?>
            <div style="text-align:center; padding:30px; color:#999; border:2px dashed #eee; border-radius:8px;">
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
                                    <span class="material-symbols-rounded" style="color:#198754;">description</span>
                                    <span style="font-weight:600;"><?= htmlspecialchars($ent['titulo']) ?></span>
                                </div>
                            </td>
                            <td style="text-align:center; color:#666; font-size:0.85rem;">
                                <?= date('d/m/Y', strtotime($ent['data_upload'])) ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <a href="<?= htmlspecialchars($ent['arquivo_path']) ?>" target="_blank" class="btn-icon" style="background:#e8f5e9; color:#198754; border:1px solid #c3e6cb;" title="Visualizar">
                                        <span class="material-symbols-rounded" style="font-size:1.1rem;">visibility</span>
                                    </a>
                                    <a href="actions/admin/entregavel_delete.php?id=<?= $ent['id'] ?>&cliente_id=<?= $cliente_ativo['id'] ?>" 
                                       onclick="return confirm('Deseja realmente excluir este documento?')"
                                       class="btn-icon" style="background:#fff5f5; color:#dc3545; border:1px solid #f5c2c7;" title="Excluir">
                                        <span class="material-symbols-rounded" style="font-size:1.1rem;">delete</span>
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
    
    <!-- SEÇÃO: GOOGLE DRIVE -->
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 12px; padding: 25px;">
        <h4 style="margin:0 0 20px 0; color:#0d47a1; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-rounded">link</span> Integração Google Drive
        </h4>
        
        <form method="POST">
            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
            <div class="admin-form-group">
                <label class="admin-form-label">🔗 Link da Pasta (Backup/Drive)</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" class="admin-form-input" placeholder="https://drive.google.com/..." style="flex:1;">
                    <button type="submit" name="btn_salvar_arquivos" class="btn-save" style="background:#0d6efd; color:white; border:none; width:auto; padding:0 25px; margin:0;">Salvar Link</button>
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
            <div class="iframe-container visible" style="display:block; margin-top:20px; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                <div style="background:#e3f2fd; color:#0d47a1; padding:10px; font-size:0.85rem; text-align:center; border-bottom:1px solid #bbdefb;">
                    💡 Visualização da Pasta Drive
                </div>
                <iframe src="<?= htmlspecialchars($embed_url) ?>" width="100%" height="500" frameborder="0" style="border:0;"></iframe>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL UPLOAD ENTREGÁVEL -->
<dialog id="modalUploadEntregavel" style="border:none; border-radius:12px; padding:0; width:90%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:#198754; color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.2rem; display:flex; align-items:center; gap:10px;">
            <span class="material-symbols-rounded">upload_file</span> Enviar Documento
        </h3>
        <button onclick="document.getElementById('modalUploadEntregavel').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <form action="actions/admin/entregavel_upload.php" method="POST" enctype="multipart/form-data" style="padding:25px;">
        <?= Csrf::getHtmlField() ?>
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        
        <div class="admin-form-group">
            <label class="admin-form-label">Título do Arquivo</label>
            <input type="text" name="titulo_arquivo" placeholder="Ex: Planta Baixa Aprovada" class="admin-form-input">
            <small style="color:#888;">Se deixar vazio, usará o nome original do arquivo.</small>
        </div>

        <div style="margin:20px 0; border:2px dashed #ccc; border-radius:10px; padding:30px; text-align:center; cursor:pointer;" onclick="document.getElementById('file_entregavel').click()" id="dropzone_entregavel">
            <span class="material-symbols-rounded" style="font-size:3rem; color:#aaa; display:block; margin-bottom:10px;">add_a_photo</span>
            <span style="color:#666; font-weight:600;">Clique para selecionar o arquivo</span>
            <input type="file" name="arquivo_entregavel" id="file_entregavel" style="display:none;" required onchange="if(this.files.length>0) { document.getElementById('dropzone_entregavel').style.borderColor='#198754'; document.getElementById('dropzone_entregavel').querySelector('span:last-child').innerText = '✅ ' + this.files[0].name; }">
        </div>

        <button type="submit" class="btn-save" style="background:#198754; color:white; border:none; width:100%; padding:12px; font-size:1rem;">Fazer Upload</button>
    </form>
</dialog>

<style>
    dialog::backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
</style>
