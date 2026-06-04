<?php
/**
 * Endpoint de API: Obter KPIs do Dashboard
 * Retorna dados em JSON para atualização reativa no painel Admin
 */

require_once __DIR__ . '/../../admin/init_admin.php';

header('Content-Type: application/json');

try {
    $pdo = Database::getInstance();
    
    // Clientes totais
    $kpi_total_clientes = (int) ($pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn() ?: 0);
    
    // Obras / Processos em andamento (etapa não finalizada)
    $kpi_obras_ativas = (int) ($pdo->query("SELECT COUNT(*) FROM processo_detalhes WHERE etapa_atual != 'Processo Finalizado (Documentos Prontos)' AND etapa_atual IS NOT NULL AND etapa_atual != ''")->fetchColumn() ?: 0);
    
    // Pré-cadastros de novos clientes pendentes de aprovação
    $kpi_pre_cadastros = (int) ($pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'")->fetchColumn() ?: 0);
    
    // Financeiro: Faturamento Pago no ano corrente (usando data_vencimento correta)
    $kpi_fin_pago = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='pago' AND YEAR(data_vencimento) = YEAR(CURDATE())")->fetchColumn() ?: 0.0);
    
    // Financeiro: Total a receber (pendente)
    $kpi_fin_pendente = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='pendente'")->fetchColumn() ?: 0.0);
    
    // Financeiro: Total em atraso
    $kpi_fin_atrasado = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='atrasado'")->fetchColumn() ?: 0.0);

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total_clientes' => $kpi_total_clientes,
            'obras_ativas' => $kpi_obras_ativas,
            'pre_cadastros' => $kpi_pre_cadastros,
            'fin_pago' => $kpi_fin_pago,
            'fin_pago_formatted' => 'R$ ' . number_format($kpi_fin_pago, 2, ',', '.'),
            'fin_pendente' => $kpi_fin_pendente,
            'fin_pendente_formatted' => 'R$ ' . number_format($kpi_fin_pendente, 2, ',', '.'),
            'fin_atrasado' => $kpi_fin_atrasado,
            'fin_atrasado_formatted' => 'R$ ' . number_format($kpi_fin_atrasado, 2, ',', '.')
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar KPIs: ' . $e->getMessage()
    ]);
}
