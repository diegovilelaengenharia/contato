<?php
/**
 * Action: Exportar Histórico Financeiro em CSV
 * Retorna os lançamentos financeiros formatados para planilha (Excel)
 */

require_once __DIR__ . '/../../admin/init_admin.php';
require_once __DIR__ . '/../../core/Csrf.php';

// Validar CSRF
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/index.php");
    exit;
}

$cid = (int)($_POST['cliente_id'] ?? 0);
if (!$cid) {
    $_SESSION['flash_message'] = ['text' => 'Cliente inválido.', 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}

$pdo = Database::getInstance();

try {
    // 1. Obter nome do cliente
    $stmtC = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmtC->execute([$cid]);
    $cliente_nome = $stmtC->fetchColumn();
    
    if (!$cliente_nome) {
        throw new Exception("Cliente não encontrado.");
    }

    // 2. Obter lançamentos
    $stmtF = $pdo->prepare("SELECT categoria, descricao, valor, data_vencimento, status, referencia_legal FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
    $stmtF->execute([$cid]);
    $faturas = $stmtF->fetchAll(PDO::FETCH_ASSOC);

    // Gravar Log de Auditoria
    Logger::log('EXPORT', 'processo_financeiro', $cid, [
        'formato' => 'CSV',
        'total_registros' => count($faturas)
    ]);

    // 3. Configurar Cabeçalhos HTTP para download
    $filename = "financeiro_" . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($cliente_nome)) . "_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // 4. Criar buffer de saída
    $output = fopen('php://output', 'w');
    
    // Inserir UTF-8 BOM para o Excel ler acentos perfeitamente
    fwrite($output, "\xEF\xBB\xBF");

    // Escrever cabeçalho das colunas
    fputcsv($output, ['Categoria', 'Descrição', 'Valor (R$)', 'Data Vencimento', 'Status', 'Referência Legal'], ';');

    // Escrever linhas
    foreach ($faturas as $f) {
        $valor_formatado = number_format($f['valor'], 2, ',', '');
        $data_formatada = date('d/m/Y', strtotime($f['data_vencimento']));
        $categoria_label = ($f['categoria'] === 'honorarios') ? 'Honorários' : 'Taxas';
        $status_label = ucfirst($f['status']);

        fputcsv($output, [
            $categoria_label,
            $f['descricao'],
            $valor_formatado,
            $data_formatada,
            $status_label,
            $f['referencia_legal'] ?: '--'
        ], ';');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    $_SESSION['flash_message'] = ['text' => 'Erro ao exportar planilha financeira: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=financeiro");
    exit;
}
