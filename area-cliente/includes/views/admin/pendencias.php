<?php
/**
 * View Admin: Checklist de Pendências
 */
?>

<div class="admin-tab-content">
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title">📋 Checklist de Pendências</h3>
            <p class="admin-subtitle">Gerencie os itens pendentes e verifique os arquivos enviados.</p>
        </div>
        
        <div style="text-align:right;">
             <button onclick="document.getElementById('modalNovaPendencia').showModal()" style="padding:8px 15px; background:linear-gradient(135deg, #198754, #146c43); border:none; border-radius:30px; font-size:0.8rem; font-weight:700; color:white; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 4px 10px rgba(25, 135, 84, 0.3); transition:all 0.2s; margin-left:10px;">
                <span style="font-size:1rem;">➕</span> Nova Pendência
            </button>
            
            <?php 
            $stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id=? ORDER BY status ASC, id DESC");
            $stmt_pend->execute([$cliente_ativo['id']]);
            $pendencias = $stmt_pend->fetchAll();

            // Lógica WhatsApp - Cobrança Dinâmica
            $pend_abertas = array_filter($pendencias, function($p) {
                return $p['status'] == 'pendente' || $p['status'] == 'anexado';
            });
            
            $primeiro_nome = explode(' ', trim($cliente_ativo['nome']))[0];
            $msg_wpp_pend = "Olá {$primeiro_nome}, tudo bem? Espero que sim! 🤝\n\nSou da *Vilela Engenharia*. Passando para lembrar das pendências necessárias para darmos andamento ao seu processo:\n\n";
            
            if(count($pend_abertas) > 0) {
                foreach($pend_abertas as $p) {
                    $msg_wpp_pend .= "👉 " . strip_tags($p['descricao']) . "\n";
                }
            } else {
                $msg_wpp_pend .= "(Nenhuma pendência em aberto)\n";
            }
            
            $msg_wpp_pend .= "\nVocê pode anexar os documentos ou ver mais detalhes acessando sua Área do Cliente:\nhttps://vilela.eng.br/area-cliente/\n\nQualquer dúvida, fique à vontade para me chamar!";
            ?>
             <a href="https://wa.me/55<?= preg_replace('/\D/','',$detalhes['contato_tel']??'') ?>?text=<?= urlencode($msg_wpp_pend) ?>" target="_blank" class="btn-save" style="background:#ffc107; color:black; border:none; margin-left:10px; padding:8px 15px;">
                📱 Cobrar no WhatsApp
            </a>

            <!-- Botão Limpar Pasta -->
            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&clear_all_files=true" 
               onclick="return confirm('ATENÇÃO: Isso apagará TODOS os arquivos anexados nas pendências deste cliente.\n\nDeseja continuar?')"
               style="background:#f8d7da; color:#dc3545; padding:8px 15px; border-radius:30px; font-size:0.8rem; font-weight:700; text-decoration:none; border:1px solid #f5c6cb; display:inline-flex; align-items:center; gap:5px; margin-left:10px;">
                🗑️ Limpar Pasta de Arquivos
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
                // Buscar Arquivos (DB + FileSystem)
                $stmtArq = $pdo->prepare("SELECT pendencia_id, id, arquivo_nome, arquivo_path, data_upload FROM processo_pendencias_arquivos WHERE pendencia_id IN (SELECT id FROM processo_pendencias WHERE cliente_id=?)");
                $stmtArq->execute([$cliente_ativo['id']]);
                $arquivos_por_pendencia = [];
                
                foreach($stmtArq->fetchAll() as $arq) {
                    $arquivos_por_pendencia[$arq['pendencia_id']][] = $arq;
                }
                
                $upload_dir_admin = __DIR__ . '/../../client-app/uploads/pendencias/';
                $web_path_admin = 'client-app/uploads/pendencias/';
                
                if(is_dir($upload_dir_admin)) {
                    foreach($pendencias as $p_check) {
                        $files_fs = glob($upload_dir_admin . $p_check['id'] . "_*.*");
                        if($files_fs) {
                            foreach($files_fs as $f_fs) {
                              $fname = basename($f_fs);
                              $ja_existe = false;
                              if(isset($arquivos_por_pendencia[$p_check['id']])) {
                                  foreach($arquivos_por_pendencia[$p_check['id']] as $ex) {
                                      if($ex['arquivo_nome'] == $fname) $ja_existe = true;
                                  }
                              }
                              
                              if(!$ja_existe) {
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
                
                if(count($pendencias) == 0): ?>
                    <tr><td colspan="4" style="padding:30px; text-align:center; color:#aaa; font-style:italic;">Nenhuma pendência registrada para este cliente.</td></tr>
                <?php else: foreach($pendencias as $p): 
                    $is_res = ($p['status'] == 'resolvido');
                    $is_anexo = ($p['status'] == 'anexado');
                    $row_opac = $is_res ? '0.6' : '1';
                    $bg_row = $is_res ? '#f8fff9' : ($is_anexo ? '#f0f8ff' : '#fff');
                    $txt_dec = $is_res ? 'line-through' : 'none';
                    
                    $arquivos = $arquivos_por_pendencia[$p['id']] ?? [];
                    if (!empty($p['arquivo_path']) && empty($arquivos)) {
                        $arquivos[] = ['arquivo_nome' => 'Anexo (Antigo)', 'arquivo_path' => $p['arquivo_path']];
                    }
                ?>
                    <tr style="background:<?= $bg_row ?>; opacity:<?= $row_opac ?>;">
                        <td>
                            <div style="font-size:1.05rem; color:#333; text-decoration:<?= $txt_dec ?>;">
                                <?= $p['descricao'] ?>
                            </div>
                            <?php if(!empty($arquivos)): ?>
                                <div style="margin-top:8px; display:flex; flex-direction:column; gap:5px;">
                                    <?php foreach($arquivos as $arq): ?>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <a href="<?= htmlspecialchars($arq['arquivo_path']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:5px; font-size:0.85rem; color:#0d6efd; text-decoration:none; background:#e9ecef; padding:4px 10px; border-radius:4px; font-weight:600;">
                                            📎 <?= (strlen($arq['arquivo_nome']) > 40 ? substr($arq['arquivo_nome'],0,40).'...' : $arq['arquivo_nome']) ?>
                                        </a>
                                        <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&delete_file_pendencia=true&file_name=<?= urlencode($arq['arquivo_nome']) ?>" 
                                           onclick="return confirm('ATENÇÃO: Deseja apagar este arquivo permanentemente?')"
                                           style="text-decoration:none; font-size:1.1rem; padding:2px;" title="Apagar Arquivo">🗑️</a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center; color:#777; font-size:0.9rem;">
                            <?= date('d/m/Y', strtotime($p['data_criacao'])) ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if($is_res): ?>
                                <span class="status-badge success">RESOLVIDO</span>
                            <?php elseif($is_anexo): ?>
                                <span class="status-badge info">ANEXADO</span>
                            <?php else: ?>
                                <span class="status-badge warning">PENDENTE</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                           <?php if(!$is_res): ?>
                               <a href="actions/admin/pendencia_status_toggle.php?pid=<?= $p['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" class="btn-icon" style="background:#fff3e0; color:#ef6c00; border:1px solid #ffe0b2; margin-right:5px;" title="Marcar como Resolvido">✅</a>
                               <button onclick="openEditPendencia(<?= $p['id'] ?>, '<?= addslashes(str_replace(["\r", "\n"], '', $p['descricao'])) ?>')" class="btn-icon" style="background:#e3f2fd; color:#0d6efd; border:1px solid #d1e7dd; margin-right:5px;" title="Editar">✏️</button>
                           <?php else: ?>
                               <a href="actions/admin/pendencia_status_toggle.php?pid=<?= $p['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" class="btn-icon" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; margin-right:5px;" title="Reabrir Pendência">↩️</a>
                           <?php endif; ?>

                           <a href="actions/admin/pendencia_delete.php?pid=<?= $p['id'] ?>&cid=<?= $cliente_ativo['id'] ?>" onclick="confirmAction(event, 'Excluir esta pendência definitivamente?')" class="btn-icon" style="background:#f8d7da; color:#dc3545; border:1px solid #f5c6cb;" title="Excluir">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modais Pendências -->
<?php require 'includes/modals/pendencias.php'; ?>
