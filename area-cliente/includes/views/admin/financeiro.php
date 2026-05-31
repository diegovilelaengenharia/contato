<?php
/**
 * View Admin: Financeiro do Processo
 */
?>
<div class="admin-tab-content">
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title">Fluxo Financeiro</h3>
            <p class="admin-subtitle">Gerencie honorários, taxas e despesas do processo.</p>
        </div>
        <button type="button" class="btn-save" onclick="document.getElementById('modalFinanceiro').showModal()">
            <span class="material-symbols-rounded">add_circle</span> Novo Lançamento
        </button>
    </div>

    <!-- Modais Financeiros -->
    <?php require 'includes/modals/financeiro.php'; ?>

    <!-- Tabelas -->
    <?php
    try {
        $fin_honorarios = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='honorarios' ORDER BY data_vencimento ASC");
        $fin_honorarios->execute([$cliente_ativo['id']]);

        $fin_taxas = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='taxas' ORDER BY data_vencimento ASC");
        $fin_taxas->execute([$cliente_ativo['id']]);

        renderFinTable($fin_honorarios, "Honorários e Serviços (Vilela Engenharia)", "#197e63", $cliente_ativo['id']);
        renderFinTable($fin_taxas, "Taxas e Multas Governamentais", "#c9871a", $cliente_ativo['id']);

    } catch (Exception $e) {
        echo "<p class='admin-subtitle' style='color:var(--text-danger);'>Não foi possível carregar os dados financeiros. Tente novamente em instantes.</p>";
        error_log("Financeiro admin: " . $e->getMessage());
    }
    ?>
</div>
