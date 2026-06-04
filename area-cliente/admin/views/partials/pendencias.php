<?php
/**
 * Parcial: Aba Pendências
 * Extraído de admin/views/cliente_detalhes.php
 */
?>
<div class="admin-header-row">
    <div>
        <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Gestão de Pendências</h3>
        <p class="admin-subtitle">Acompanhe solicitações de documentos e ações pendentes do cliente.</p>
    </div>
    <button type="button" class="btn-save" onclick="document.getElementById('modalPendenciaNew').showModal()">
        <span class="material-symbols-rounded">add_circle</span> Solicitar Documento / Pendência
    </button>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Descrição da Solicitação / Pendência</th>
                <th>Data de Abertura</th>
                <th>Anexo Cliente</th>
                <th style="text-align: right; padding-right: 20px;">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmtPen = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY data_criacao DESC");
            $stmtPen->execute([$cliente['id']]);
            $pendencias = $stmtPen->fetchAll();

            if (empty($pendencias)): ?>
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: var(--color-muted); font-style: italic;">
                        Nenhuma pendência cadastrada para este cliente.
                    </td>
                </tr>
            <?php else: foreach ($pendencias as $p): 
                $status_badge = $p['status'] === 'pendente' ? 'status-badge warning' : 'status-badge success';
                $status_label = $p['status'] === 'pendente' ? 'Aberto' : 'Resolvido';
                
                $anexo = '<span style="opacity: 0.5;">--</span>';
                if (!empty($p['arquivo_path'])) {
                    $anexo = '<a href="'.htmlspecialchars($p['arquivo_path']).'" target="_blank" style="color: var(--color-primary-dark); font-weight: 700; text-decoration: none;">Ver Anexo</a>';
                }
            ?>
                <tr>
                    <td>
                        <form action="../actions/admin/pendencia_status_toggle.php" method="POST" style="display: inline;" @submit.prevent="submitForm($event)">
                            <?php echo Csrf::getHtmlField(); ?>
                            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                            <input type="hidden" name="pendencia_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="<?php echo $status_badge; ?>" style="border: none; cursor: pointer; font-family: inherit;">
                                <?php echo $status_label; ?>
                            </button>
                        </form>
                    </td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($p['descricao']); ?></td>
                    <td style="color: var(--color-text-subtle);"><?php echo date('d/m/Y H:i', strtotime($p['data_criacao'])); ?></td>
                    <td><?php echo $anexo; ?></td>
                    <td style="text-align: right; padding-right: 20px;">
                        <form action="../actions/admin/pendencia_delete.php" method="POST" class="inline-form" style="display: inline;"
                              @submit.prevent="deleteItem($event, 'Deseja excluir esta pendência?')">
                            <?php echo Csrf::getHtmlField(); ?>
                            <input type="hidden" name="pendencia_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                            <button type="submit" class="btn-icon danger" title="Excluir" style="border: none; background: none; cursor: pointer; padding: 0; color: var(--color-danger);">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Nova Pendência para Cliente -->
<dialog id="modalPendenciaNew">
    <div style="background: var(--color-primary); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">⚠️ Solicitar Pendência do Cliente</h3>
        <button type="button" onclick="document.getElementById('modalPendenciaNew').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>
    <form action="../actions/admin/pendencia_create.php" method="POST" style="padding: 25px;" @submit.prevent="submitForm($event)">
        <?php echo Csrf::getHtmlField(); ?>
        <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Descrição do Documento ou Ação Exigida <span style="color: red;">*</span></label>
            <textarea name="descricao" required rows="4" placeholder="Ex: Cópia autenticada da certidão de casamento e espelho do IPTU de 2024..." class="admin-form-input" style="resize: vertical; font-family: inherit;"></textarea>
        </div>

        <button type="submit" name="btn_criar_pendencia" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
            Abrir Solicitação
        </button>
    </form>
</dialog>
