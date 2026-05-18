<?php
/**
 * View Admin: Financeiro do Processo
 */
?>
<div class="admin-tab-content">
    <!-- Header e Botão Novo Lançamento -->
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title">💰 Fluxo Financeiro</h3>
            <p class="admin-subtitle">Gerencie honorários, taxas e despesas do processo.</p>
        </div>
        <button type="button" onclick="document.getElementById('modalFinanceiro').showModal()" style="background:linear-gradient(135deg, #198754, #146c43); color:white; border:none; padding:12px 25px; border-radius:30px; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 15px rgba(25, 135, 84, 0.3); transition:all 0.2s;">
            <span style="font-size:1.2rem;">➕</span> Novo Lançamento
        </button>
    </div>

    <!-- Modais Financeiros -->
    <?php require 'includes/modals/financeiro.php'; ?>
    
    <!-- Modais Widgets Sidebar -->
    <?php require 'includes/modals/sidebar_widgets.php'; ?>

    <!-- Tabelas -->
    <?php 
    try {
        $fin_honorarios = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='honorarios' ORDER BY data_vencimento ASC");
        $fin_honorarios->execute([$cliente_ativo['id']]);
        
        $fin_taxas = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='taxas' ORDER BY data_vencimento ASC");
        $fin_taxas->execute([$cliente_ativo['id']]);

        renderFinTable($fin_honorarios, "💰 Honorários e Serviços (Vilela Engenharia)", "#198754", $cliente_ativo['id']);
        renderFinTable($fin_taxas, "🏛️ Taxas e Multas Governamentais", "#efb524", $cliente_ativo['id']);

    } catch (Exception $e) {
        echo "<div style='color:red'>Erro ao carregar dados financeiros. Verifique se o Setup de Banco de Dados foi rodado. <br>". $e->getMessage() ."</div>";
    }
    ?>
</div>
