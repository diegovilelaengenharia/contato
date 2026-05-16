<!-- Modal Novo Lançamento Financeiro -->
<dialog id="modalFinanceiro" style="border:none; border-radius:12px; padding:0; width:90%; max-width:550px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:#2196f3; color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.2rem;">💰 Novo Lançamento</h3>
        <button onclick="document.getElementById('modalFinanceiro').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <form method="POST" style="padding:25px;">
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        
        <div style="background:#f0f8ff; border:1px solid #cfe2ff; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
             <span style="color:#084298; font-size:0.9rem; font-weight:600;">Precisa de ajuda?</span>
             <button type="button" onclick="openTaxasModal()" class="btn-save btn-info" style="width:auto; padding:6px 15px; font-size:0.85rem; margin:0;">📋 Selecionar Padrão</button>
        </div>

        <div class="form-group" style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Descrição</label>
            <input type="text" name="descricao" id="fin_descricao" required placeholder="Ex: Taxa de Habite-se" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
        </div>

        <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
            <div class="form-group">
                <label style="display:block; margin-bottom:5px; font-weight:600;">Categoria</label>
                <select name="categoria" id="fin_categoria" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    <option value="honorarios">Honorários (Vilela Engenharia)</option>
                    <option value="taxas">Taxas e Multas (Governo)</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display:block; margin-bottom:5px; font-weight:600;">Valor (R$)</label>
                <input type="number" step="0.01" name="valor" id="fin_valor" required placeholder="0.00" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>
        </div>

        <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px;">
            <div class="form-group">
                <label style="display:block; margin-bottom:5px; font-weight:600;">Vencimento</label>
                <input type="date" name="data_vencimento" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>
            <div class="form-group">
                <label style="display:block; margin-bottom:5px; font-weight:600;">Status</label>
                <select name="status" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    <option value="pendente">⏳ Pendente</option>
                    <option value="pago">✅ Pago</option>
                    <option value="atrasado">❌ Atrasado</option>
                    <option value="isento">⚪ Isento</option>
                </select>
            </div>
        </div>

        <button type="submit" name="btn_salvar_financeiro" class="btn-save btn-success" style="width:100%; padding:12px; font-size:1rem;">Adicionar Lançamento</button>
    </form>
</dialog>

<!-- Modal Status Financeiro -->
<dialog id="modalStatusFin" style="border:none; border-radius:8px; padding:20px; box-shadow:0 5px 20px rgba(0,0,0,0.2);">
    <form method="POST">
        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
        <input type="hidden" name="fin_id" id="edit_fin_id">
        
        <h3 style="margin-top:0;">Alterar Status Financeiro</h3>
        
        <div class="form-group">
            <label>Novo Status</label>
            <select name="novo_status" id="edit_fin_status" style="width:100%; padding:8px;">
                <option value="pendente">⏳ Pendente</option>
                <option value="pago">✅ Pago</option>
                <option value="atrasado">❌ Atrasado</option>
                <option value="isento">⚪ Isento</option>
            </select>
        </div>
        
        <div style="margin-top:15px; text-align:right; display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="document.getElementById('modalStatusFin').close()" style="padding:8px 15px; border:1px solid #ccc; background:white; border-radius:4px; cursor:pointer;">Cancelar</button>
            <button type="submit" name="btn_update_status_fin" class="btn-save btn-primary" style="width:auto; padding:8px 15px; margin:0;">Salvar</button>
        </div>
    </form>
</dialog>

<script>
function openStatusFinModal(id, currentStatus) {
    document.getElementById('edit_fin_id').value = id;
    document.getElementById('edit_fin_status').value = currentStatus;
    document.getElementById('modalStatusFin').showModal();
}
</script>

<!-- MODAL DE SELEÇÃO DE TAXAS -->
<dialog id="modalTaxas" style="border:none; border-radius:12px; padding:0; width:90%; max-width:800px; max-height:90vh; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#f8f9fa;">
        <h3 style="margin:0; color:var(--color-primary);">📋 Selecionar Taxa ou Multa Padrão</h3>
        <button onclick="closeTaxasModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <div style="padding:20px; overflow-y:auto;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                
                <!-- Coluna Taxas -->
                <div>
                    <h4 style="color:#0f5132; border-bottom:2px solid #d1e7dd; padding-bottom:10px; margin-top:0;">🏛️ Taxas Administrativas</h4>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach($taxas_padrao['taxas'] as $t): ?>
                            <div onclick="selectTaxa('<?= $t['titulo'] ?>', '<?= $t['lei'] ?>', 'taxa', '<?= $t['valor'] ?? '' ?>')" 
                                 style="padding:15px; border:1px solid #e9ecef; border-radius:8px; cursor:pointer; transition:0.2s; background:#fff;">
                                <div style="display:flex; justify-content:space-between;">
                                    <div style="font-weight:bold; color:#146c43;"><?= $t['titulo'] ?></div>
                                    <div style="font-weight:bold; color:#146c43;">R$ <?= $t['valor'] ?? '0.00' ?></div>
                                </div>
                                <div style="font-size:0.85rem; color:#666; margin:4px 0;"><?= $t['desc'] ?></div>
                                <div style="font-size:0.8rem; background:#e9ecef; display:inline-block; padding:2px 6px; border-radius:4px; color:#555;">Eg: <?= $t['lei'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Coluna Multas -->
                <div>
                    <h4 style="color:#842029; border-bottom:2px solid #f8d7da; padding-bottom:10px; margin-top:0;">🚨 Infrações e Multas</h4>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach($taxas_padrao['multas'] as $t): ?>
                            <div onclick="selectTaxa('<?= $t['titulo'] ?>', '<?= $t['lei'] ?>', 'multa', '<?= $t['valor'] ?? '' ?>')" 
                                 style="padding:15px; border:1px solid #ffebe9; border-radius:8px; cursor:pointer; transition:0.2s; background:#fff;">
                                <div style="display:flex; justify-content:space-between;">
                                    <div style="font-weight:bold; color:#a50e0e;"><?= $t['titulo'] ?></div>
                                    <div style="font-weight:bold; color:#a50e0e;">R$ <?= $t['valor'] ?? '0.00' ?></div>
                                </div>
                                <div style="font-size:0.85rem; color:#666; margin:4px 0;"><?= $t['desc'] ?></div>
                                <div style="font-size:0.8rem; background:#fff3cd; display:inline-block; padding:2px 6px; border-radius:4px; color:#666;">Eg: <?= $t['lei'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            
            </div>
            
        <!-- Mobile Fix css -->
        <style>
            @media(max-width: 700px) {
                #modalTaxas > div > div:nth-child(2) > div { grid-template-columns: 1fr !important; }
            }
            #modalTaxas div[onclick]:hover { transform:translateY(-2px); box-shadow:0 4px 10px rgba(0,0,0,0.08); border-color:var(--color-primary); }
            /* Dialog backdrop */
            dialog::backdrop { background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px); }
        </style>
    </div>
</dialog>
