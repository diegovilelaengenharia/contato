<?php
/**
 * Parcial: Aba Financeiro
 * Extratado de admin/views/cliente_detalhes.php
 */
?>
<div class="admin-header-row">
    <div>
        <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Lançamentos Financeiros</h3>
        <p class="admin-subtitle">Honorários técnicos e taxas governamentais da obra.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
        <form action="../actions/admin/exportar_financeiro.php" method="POST" target="_blank" style="display: inline-block;">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            <button type="submit" class="btn-save btn-ghost" style="display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--color-border); background: white; color: var(--color-text); cursor: pointer; padding: 8px 14px; border-radius: 8px;">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">download</span> Exportar Planilha (CSV)
            </button>
        </form>
        <button type="button" class="btn-save" onclick="document.getElementById('modalFinanceiroNew').showModal()">
            <span class="material-symbols-rounded">add_circle</span> Novo Lançamento
        </button>
    </div>
</div>

<?php
try {
    // Honorários Vilela Engenharia
    $stmtHon = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? AND categoria = 'honorarios' ORDER BY data_vencimento ASC");
    $stmtHon->execute([$cliente['id']]);
    $honorarios = $stmtHon->fetchAll();

    // Taxas Governamentais
    $stmtTax = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? AND categoria = 'taxas' ORDER BY data_vencimento ASC");
    $stmtTax->execute([$cliente['id']]);
    $taxas = $stmtTax->fetchAll();
    
    // Função interna para desenhar tabelas financeiras elegantes no novo painel
    if (!function_exists('renderFinTableNew')) {
        function renderFinTableNew($rows, $title, $color, $cliente_id) {
            echo "<div style='margin-top: 24px; border-top: 3px solid {$color}; padding-top: 15px;'>";
            echo "<h4 style='color: {$color}; font-size: 1.05rem; margin: 0 0 14px 0;'>{$title}</h4>";
            
            if (empty($rows)) {
                echo "<p style='font-style: italic; color: var(--color-muted); font-size: 0.88rem;'>Nenhum lançamento nesta categoria.</p>";
            } else {
                echo "<div class='admin-table-container'>";
                echo "<table class='admin-table'>";
                echo "<thead><tr>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th style='text-align: center;'>Status</th>
                        <th style='text-align: center;'>Comprovante</th>
                        <th style='text-align: right; padding-right: 20px;'>Ação</th>
                      </tr></thead><tbody>";
                foreach ($rows as $r) {
                    $badge = 'status-badge';
                    $status_text = 'Pendente';
                    switch ($r['status']) {
                        case 'pago': $badge .= ' success'; $status_text = 'Pago'; break;
                        case 'pendente': $badge .= ' warning'; $status_text = 'Pendente'; break;
                        case 'atrasado': $badge .= ' danger'; $status_text = 'Atrasado'; break;
                        case 'isento': $badge .= ' info'; $status_text = 'Isento'; break;
                    }
                    
                    $valor = number_format($r['valor'], 2, ',', '.');
                    $data = date('d/m/Y', strtotime($r['data_vencimento']));
                    
                    // Link do comprovante
                    $link = '<span style="opacity: 0.5;">--</span>';
                    if (!empty($r['link_comprovante']) && preg_match('#^https?://#i', $r['link_comprovante'])) {
                        $link = '<a href="'.htmlspecialchars($r['link_comprovante']).'" target="_blank" style="color: var(--color-primary-dark); font-weight: 700; text-decoration: none;">Ver Doc</a>';
                    }
                    
                    echo "<tr>
                            <td style='font-weight: 600;'>".htmlspecialchars($r['descricao'])."</td>
                            <td style='font-weight: bold;'>R$ {$valor}</td>
                            <td>{$data}</td>
                            <td style='text-align: center;'>
                                <span class='{$badge}' onclick='openStatusFinanceiro({$r['id']}, \"{$r['status']}\")' style='cursor: pointer;' title='Alterar Status'>
                                    {$status_text}
                                </span>
                            </td>
                            <td style='text-align: center;'>{$link}</td>
                            <td style='text-align: right; padding-right: 20px;'>
                                <form action='../actions/admin/financeiro_delete.php' method='POST' class='inline-form' style='display: inline;'
                                      @submit.prevent='deleteItem(\$event, \"Deseja excluir este lançamento?\")'>
                                    " . Csrf::getHtmlField() . "
                                    <input type='hidden' name='fin_id' value='{$r['id']}'>
                                    <input type='hidden' name='cliente_id' value='{$cliente_id}'>
                                    <button type='submit' class='btn-icon danger' title='Excluir' style='border: none; background: none; cursor: pointer; padding: 0; color: var(--color-danger);'>
                                        <span class='material-symbols-rounded'>delete</span>
                                    </button>
                                </form>
                            </td>
                          </tr>";
                }
                echo "</tbody></table></div>";
            }
            echo "</div>";
        }
    }

    renderFinTableNew($honorarios, "Honorários e Serviços Técnicos (Vilela Engenharia)", "var(--color-primary)", $cliente['id']);
    renderFinTableNew($taxas, "Taxas Administrativas, Multas e Cartórios", "#c9871a", $cliente['id']);

} catch (Exception $e) {
    echo "<p style='color: var(--color-danger);'>Erro ao carregar dados financeiros.</p>";
}
?>

<!-- Modal: Novo Lançamento Financeiro -->
<dialog id="modalFinanceiroNew">
    <div style="background: var(--color-primary); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">💰 Novo Lançamento Financeiro</h3>
        <button type="button" onclick="document.getElementById('modalFinanceiroNew').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>
    
    <form action="../actions/admin/financeiro_create.php" method="POST" style="padding: 25px;" @submit.prevent="submitForm($event)">
        <?php echo Csrf::getHtmlField(); ?>
        <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Descrição do Lançamento</label>
            <input type="text" name="descricao" required placeholder="Ex: Honorários Técnicos - Regularização de Casa" class="admin-form-input">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div class="form-group">
                <label>Categoria</label>
                <select name="categoria" required class="proc-select" style="background: white;">
                    <option value="honorarios">Honorários (Vilela Engenharia)</option>
                    <option value="taxas">Taxas e Multas (Prefeitura/Cartório)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Valor (R$)</label>
                <input type="number" step="0.01" name="valor" required placeholder="0.00" class="admin-form-input">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div class="form-group">
                <label>Data de Vencimento</label>
                <input type="date" name="data_vencimento" required class="admin-form-input">
            </div>
            <div class="form-group">
                <label>Status de Pagamento</label>
                <select name="status" class="proc-select" style="background: white;">
                    <option value="pendente">⏳ Pendente</option>
                    <option value="pago">✅ Pago</option>
                    <option value="atrasado">❌ Atrasado</option>
                    <option value="isento">⚪ Isento</option>
                </select>
            </div>
        </div>

        <button type="submit" name="btn_salvar_financeiro" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
            Adicionar Fatura
        </button>
    </form>
</dialog>

<!-- Modal: Alterar Status de Lançamento Financeiro -->
<dialog id="modalStatusFinanceiroEdit" style="border: none; border-radius: var(--radius); max-width: 420px; width: 90%; box-shadow: var(--shadow-lg);">
    <div style="background: var(--color-primary); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.05rem;">Alterar Status da Fatura</h3>
        <button type="button" onclick="document.getElementById('modalStatusFinanceiroEdit').close()" style="background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer;">&times;</button>
    </div>
    <form action="../actions/admin/financeiro_status_update.php" method="POST" style="padding: 20px;" @submit.prevent="submitForm($event)">
        <?php echo Csrf::getHtmlField(); ?>
        <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
        <input type="hidden" name="financeiro_id" id="edit_fin_id">
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Novo Status</label>
            <select name="novo_status" id="edit_fin_status" class="proc-select" style="background: white;">
                <option value="pendente">⏳ Pendente</option>
                <option value="pago">✅ Pago</option>
                <option value="atrasado">❌ Atrasado</option>
                <option value="isento">⚪ Isento</option>
            </select>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn-std btn-ghost" style="padding: 8px 16px;" onclick="document.getElementById('modalStatusFinanceiroEdit').close()">Cancelar</button>
            <button type="submit" name="btn_update_status_fin" class="btn-std btn-primary" style="padding: 8px 16px;">Salvar Status</button>
        </div>
    </form>
</dialog>
