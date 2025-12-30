<div class="view-header-simple">
    <h2>Gestão Financeira</h2>
    <p>Acompanhe seus investimentos na obra.</p>
</div>

<!-- ASSISTANT TIP -->
<div class="assistant-tip fade-in-up">
    <div class="at-icon">💰</div>
    <div class="at-content">
        <strong>Transparência Total</strong>
        <p>Aqui você confere exatamente para onde vai cada centavo. As taxas governamentais tem a referência da Lei para sua conferência.</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="finance-summary fade-in-up">
    <div class="fin-card highlight">
        <label>Pendentes</label>
        <strong style="color: #856404;">R$ <?= number_format($fin_stats['pendente'], 2, ',', '.') ?></strong>
    </div>
    <div class="fin-card">
        <label>Total Pago</label>
        <strong style="color: var(--color-success);">R$ <?= number_format($fin_stats['pago'], 2, ',', '.') ?></strong>
    </div>
</div>

<div class="fade-in-up">

    <!-- HONORÁRIOS -->
    <div class="fin-premium-section">
        <div class="fin-section-title">
            <span class="material-symbols-rounded">engineering</span>
            Honorários Vilela Engenharia
        </div>
        
        <?php 
        $honorarios = array_filter($financeiro, fn($f) => $f['categoria'] == 'honorarios');
        if(count($honorarios) > 0): foreach($honorarios as $f): 
            include __DIR__ . '/../partials/fin_row_premium.php'; 
        endforeach;
        else: echo "<p style='text-align:center; color:var(--text-muted);'>Nenhum registro.</p>"; endif; 
        ?>
    </div>

    <!-- TAXAS -->
    <div class="fin-premium-section">
        <div class="fin-section-title">
            <span class="material-symbols-rounded">account_balance</span>
            Taxas e Emolumentos Oficiais
        </div>
        
        <?php 
        $taxas = array_filter($financeiro, fn($f) => $f['categoria'] == 'taxas');
        if(count($taxas) > 0): foreach($taxas as $f): 
            include __DIR__ . '/../partials/fin_row_premium.php';
        endforeach;
        else: echo "<p style='text-align:center; color:var(--text-muted);'>Nenhuma taxa registrada.</p>"; endif; 
        ?>
    </div>

</div>

<?php
// Creating the Premium Row Partial
if(!file_exists(__DIR__ . '/../partials/fin_row_premium.php')) {
    if(!is_dir(__DIR__ . '/../partials')) mkdir(__DIR__ . '/../partials', 0755, true);
    $partial_content = '
    <div class="fin-premium-row status-<?= $f["status"] ?>">
        <div class="fp-left">
            <h4><?= htmlspecialchars($f["descricao"]) ?></h4>
            <span>Vencimento: <?= date("d/m/Y", strtotime($f["data_vencimento"])) ?></span>
            <?php if(!empty($f["referencia_legal"])): ?>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">
                    <span style="font-weight:600;">Lei/Ref:</span> <?= htmlspecialchars($f["referencia_legal"]) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="fp-right">
            <span class="fp-price">R$ <?= number_format($f["valor"], 2, ",", ".") ?></span>
            <?php if($f["status"]=="pendente"): ?>
                <span class="fp-badge" style="color:var(--color-warning);">Aberto</span>
            <?php elseif($f["status"]=="pago"): ?>
                <span class="fp-badge" style="color:var(--color-success);">Pago</span>
            <?php else: ?>
                <span class="fp-badge" style="color:var(--color-danger);">Vencido</span>
            <?php endif; ?>
        </div>
    </div>';
    file_put_contents(__DIR__ . '/../partials/fin_row_premium.php', $partial_content);
}
?>
