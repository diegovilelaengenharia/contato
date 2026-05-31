<?php
/**
 * View Admin: Checklist de Pendências
 */

// --- Dados ---
$stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id=? ORDER BY status ASC, id DESC");
$stmt_pend->execute([$cliente_ativo['id']]);
$pendencias = $stmt_pend->fetchAll();

// Mensagem de cobrança via WhatsApp
$pend_abertas = array_filter($pendencias, fn($p) => $p['status'] == 'pendente' || $p['status'] == 'anexado');
$primeiro_nome = explode(' ', trim($cliente_ativo['nome']))[0];
$msg_wpp_pend = "Olá {$primeiro_nome}, tudo bem? Espero que sim! 🤝\n\nSou da *Vilela Engenharia*. Passando para lembrar das pendências necessárias para darmos andamento ao seu processo:\n\n";
if (count($pend_abertas) > 0) {
    foreach ($pend_abertas as $p) { $msg_wpp_pend .= "👉 " . strip_tags($p['descricao']) . "\n"; }
} else {
    $msg_wpp_pend .= "(Nenhuma pendência em aberto)\n";
}
$msg_wpp_pend .= "\nVocê pode anexar os documentos ou ver mais detalhes acessando sua Área do Cliente:\nhttps://vilela.eng.br/area-cliente/\n\nQualquer dúvida, fique à vontade para me chamar!";
?>

<div class="admin-tab-content">
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title">Checklist de Pendências</h3>
            <p class="admin-subtitle">Gerencie os itens pendentes e verifique os arquivos enviados.</p>
        </div>

        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button type="button" class="btn-save" onclick="document.getElementById('modalNovaPendencia').showModal()">
                <span class="material-symbols-rounded">add_circle</span> Nova Pendência
            </button>
            <a href="https://wa.me/55<?= preg_replace('/\D/','',$detalhes['contato_tel']??'') ?>?text=<?= urlencode($msg_wpp_pend) ?>"
               target="_blank" class="btn-save btn-warning">
                <span class="material-symbols-rounded">chat</span> Cobrar no WhatsApp
            </a>
            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&clear_all_files=true"
               class="btn-save btn-danger"
               onclick="return confirm('ATENÇÃO: Isso apagará TODOS os arquivos anexados nas pendências deste cliente.\n\nDeseja continuar?')">
                <span class="material-symbols-rounded">folder_delete</span> Limpar Arquivos
            </a>
        </div>
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60%;">Descrição</th>
                    <th style="text-align:center;">Data</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Arquivos (DB + sistema de arquivos)
                $stmtArq = $pdo->prepare("SELECT pendencia_id, id, arquivo_nome, arquivo_path, data_upload FROM processo_pendencias_arquivos WHERE pendencia_id IN (SELECT id FROM processo_pendencias WHERE cliente_id=?)");
                $stmtArq->execute([$cliente_ativo['id']]);
                $arquivos_por_pendencia = [];
                foreach ($stmtArq->fetchAll() as $arq) { $arquivos_por_pendencia[$arq['pendencia_id']][] = $arq; }

                $upload_dir_admin = __DIR__ . '/../../client-app/uploads/pendencias/';
                $web_path_admin = 'client-app/uploads/pendencias/';
                if (is_dir($upload_dir_admin)) {
                    foreach ($pendencias as $p_check) {
                        $files_fs = glob($upload_dir_admin . $p_check['id'] . "_*.*");
                        if ($files_fs) {
                            foreach ($files_fs as $f_fs) {
                                $fname = basename($f_fs);
                                $ja_existe = false;
                                if (isset($arquivos_por_pendencia[$p_check['id']])) {
                                    foreach ($arquivos_por_pendencia[$p_check['id']] as $ex) {
                                        if ($ex['arquivo_nome'] == $fname) $ja_existe = true;
                                    }
                                }
                                if (!$ja_existe) {
                                    $arquivos_por_pendencia[$p_check['id']][] = [
                                        'arquivo_nome' => $fname,
                                        'arquivo_path' => $web_path_admin . $fname,
                                        'data_upload' => date('Y-m-d H:i:s', filemtime($f_fs))
                                    ];
                                }
                            }
                        }
                    }
                }

                if (count($pendencias) == 0): ?>
                    <tr><td colspan="4" style="padding:30px; text-align:center; color:var(--color-muted); font-style:italic;">Nenhuma pendência registrada para este cliente.</td></tr>
                <?php else: foreach ($pendencias as $p):
                    $is_res = ($p['status'] == 'resolvido');
                    $is_anexo = ($p['status'] == 'anexado');
                    $arquivos = $arquivos_por_pendencia[$p['id']] ?? [];
                    if (!empty($p['arquivo_path']) && empty($arquivos)) {
                        $arquivos[] = ['arquivo_nome' => 'Anexo (Antigo)', 'arquivo_path' => $p['arquivo_path']];
                    }
                ?>
                    <tr style="<?= $is_res ? 'opacity:.6;' : '' ?>">
                        <td>
                            <div style="font-size:1rem; color:var(--color-text); <?= $is_res ? 'text-decoration:line-through;' : '' ?>">
                                <?= $p['descricao'] ?>
                            </div>
                            <?php if (!empty($arquivos)): ?>
                                <div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">
                                    <?php foreach ($arquivos as $arq): ?>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <a href="<?= htmlspecialchars($arq['arquivo_path']) ?>" target="_blank"
                                           style="display:inline-flex; align-items:center; gap:6px; font-size:.85rem; color:var(--text-info); text-decoration:none; background:var(--bg-info); padding:5px 10px; border-radius:6px; font-weight:600;">
                                            <span class="material-symbols-rounded" style="font-size:1rem;">attach_file</span>
                                            <?= htmlspecialchars(strlen($arq['arquivo_nome']) > 40 ? substr($arq['arquivo_nome'],0,40).'...' : $arq['arquivo_nome']) ?>
                                        </a>
                                        <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&delete_file_pendencia=true&file_name=<?= urlencode($arq['arquivo_nome']) ?>"
                                           class="btn-icon danger" style="width:30px; height:30px;"
                                           onclick="return confirm('ATENÇÃO: Deseja apagar este arquivo permanentemente?')" title="Apagar arquivo">
                                            <span class="material-symbols-rounded" style="font-size:1.05rem;">delete</span>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center; color:var(--color-text-subtle); font-size:.9rem;">
                            <?= date('d/m/Y', strtotime($p['data_criacao'])) ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($is_res): ?>
                                <span class="status-badge success">Resolvido</span>
                            <?php elseif ($is_anexo): ?>
                                <span class="status-badge info">Anexado</span>
                            <?php else: ?>
                                <span class="status-badge warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                           <?php if (!$is_res): ?>
                               <a href="actions/admin/pendencia_status_toggle.php?pid=<?= $p['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" class="btn-icon" title="Marcar como resolvido">
                                   <span class="material-symbols-rounded">check_circle</span>
                               </a>
                               <button type="button" onclick="openEditPendencia(<?= $p['id'] ?>, '<?= addslashes(str_replace(["\r", "\n"], '', $p['descricao'])) ?>')" class="btn-icon" title="Editar">
                                   <span class="material-symbols-rounded">edit</span>
                               </button>
                           <?php else: ?>
                               <a href="actions/admin/pendencia_status_toggle.php?pid=<?= $p['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" class="btn-icon" title="Reabrir pendência">
                                   <span class="material-symbols-rounded">undo</span>
                               </a>
                           <?php endif; ?>
                           <a href="actions/admin/pendencia_delete.php?pid=<?= $p['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" class="btn-icon danger" onclick="confirmAction(event, 'Excluir esta pendência definitivamente?')" title="Excluir">
                               <span class="material-symbols-rounded">delete</span>
                           </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modais Pendências -->
<?php require 'includes/modals/pendencias.php'; ?>
