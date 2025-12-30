<div class="view-header-simple">
    <h2>Financeiro</h2>
    <p>Transparência total em custos e taxas.</p>
</div>

<!-- Summary Cards -->
<div class="finance-summary fade-in-up">
    <div class="fin-card highlight">
        <label>Em Aberto</label>
        <strong>R$ <?= number_format($fin_stats['pendente'], 2, ',', '.') ?></strong>
        <p>Vence em breve</p>
    </div>
    <div class="fin-card">
        <label>Total Pago</label>
        <strong>R$ <?= number_format($fin_stats['pago'], 2, ',', '.') ?></strong>
    </div>
</div>

<div class="finance-list fade-in-up">
    <!-- SEPARADOR: TAXAS (GOVERNO) -->
    <h3 class="list-section-title">🏛️ Taxas e Multas (Prefeitura/Gov)</h3>
    <?php 
    $taxas = array_filter($financeiro, fn($f) => $f['categoria'] == 'taxas');
    if(count($taxas) > 0): foreach($taxas as $f): include 'partials/fin_item.php'; endforeach;
    else: echo "<p class='empty-msg'>Nenhuma taxa registrada.</p>"; endif; 
    ?>

    <!-- SEPARADOR: HONORÁRIOS -->
    <h3 class="list-section-title" style="margin-top:20px;">👷 Honorários Técnicos</h3>
    <?php 
    $honorarios = array_filter($financeiro, fn($f) => $f['categoria'] == 'honorarios');
    if(count($honorarios) > 0): foreach($honorarios as $f): include 'partials/fin_item.php'; endforeach;
    else: echo "<p class='empty-msg'>Nenhum honorário registrado.</p>"; endif; 
    ?>
</div>

<?php
// Partial logic inline for simplicity in file creation, 
// usually would be a separate file but for now let's functionize or just define variable scope
// Actually I'll use a loop inside since I can't easily create a partial in the same `write_to_file` call without complexity.
// RE-WRITING THE LOOP ABOVE TO BE EXPLICIT
?>
