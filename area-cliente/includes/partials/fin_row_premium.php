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
</div>
