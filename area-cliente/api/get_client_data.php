<?php
// Force session cookie to be available to entire domain
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
header('Content-Type: application/json');
require '../db.php';

// Auth Check
if (!isset($_SESSION['cliente_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

try {
    // 1. Client Details
    $stmt = $pdo->prepare("SELECT c.*, d.* FROM clientes c LEFT JOIN processo_detalhes d ON c.id = d.cliente_id WHERE c.id = ?");
    $stmt->execute([$cliente_id]);
    $clientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clientData) {
        http_response_code(404);
        echo json_encode(['error' => 'Client not found']);
        exit;
    }

    // Avatar Logic
    $avatar_file = glob(__DIR__ . "/../uploads/avatars/avatar_{$cliente_id}.*");
    $foto_perfil = !empty($avatar_file) ? 'uploads/avatars/' . basename($avatar_file[0]) . '?v=' . time() : null;

    // Phase Logic
    $fases_padrao = [
        "Abertura de Processo (Guichê)", 
        "Fiscalização (Parecer Fiscal)", 
        "Triagem (Documentos Necessários)",
        "Comunicado de Pendências (Triagem)", 
        "Análise Técnica (Engenharia)", 
        "Comunicado (Pendências e Taxas)",
        "Confecção de Documentos", 
        "Avaliação (ITBI/Averbação)", 
        "Processo Finalizado (Documentos Prontos)"
    ];

    // 2. Timeline
    $stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
    $stmt->execute([$cliente_id]);
    $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Finance
    $stmt = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
    $stmt->execute([$cliente_id]);
    $financeiro = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Pendencies
    $stmt = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY FIELD(status, 'pendente','anexado','resolvido'), id DESC");
    $stmt->execute([$cliente_id]);
    $pendencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Calculate Financial KPIs (New)
    $kpi_total_pago = 0;
    $kpi_total_pendente = 0;
    $kpi_total_atrasado = 0;

    foreach ($financeiro as $lanc) {
        $val = floatval($lanc['valor']);
        if ($lanc['status'] == 'pago') $kpi_total_pago += $val;
        elseif ($lanc['status'] == 'pendente') $kpi_total_pendente += $val;
        elseif ($lanc['status'] == 'atrasado') $kpi_total_atrasado += $val;
    }
    
    $financeiro_kpis = [
        'total_pago' => $kpi_total_pago,
        'total_pendente' => $kpi_total_pendente,
        'total_atrasado' => $kpi_total_atrasado,
        'total_geral' => ($kpi_total_pago + $kpi_total_pendente + $kpi_total_atrasado)
    ];

    // Construct Response Object to match React App expectations
    $response = [
        'currentPhase' => $clientData['etapa_atual'] ?? 'Protocolo e Autuação',
        'processDetails' => [
            'number' => $clientData['numero_processo'] ?? 'N/A',
            'observation' => $clientData['observacoes_gerais'] ?? null,
            'area' => $clientData['area_total'] ?? '0 m²', // Legacy support
            'area_total' => $clientData['area_total_final'] ?? $clientData['area_total'] ?? '0',
            'object' => $clientData['objeto_processo'] ?? 'Consultoria',
            'address' => $clientData['endereco_imovel'] ?? 'Endereço não cadastrado',
            // New Technical Fields
            'valor_venal' => $clientData['valor_venal'],
            'area_existente' => $clientData['area_existente'],
            'area_acrescimo' => $clientData['area_acrescimo'],
            'area_permeavel' => $clientData['area_permeavel'],
            'taxa_ocupacao' => $clientData['taxa_ocupacao'],
            'fator_aproveitamento' => $clientData['fator_aproveitamento'],
            'geo_coords' => $clientData['geo_coords']
        ],
        'timeline' => $timeline,
        'financeiro' => $financeiro,
        'financeiro_kpis' => $financeiro_kpis, // New KPI Object
        'pendencias' => $pendencias,
        'client_info' => [
            'cpf' => $clientData['cpf'] ?? $clientData['cpf_cnpj'] ?? 'N/A',
            'email' => $clientData['email'] ?? 'N/A',
            'phone' => $clientData['telefone'] ?? 'N/A'
        ],
        'driveLink' => $clientData['link_drive_pasta'] ?? '',
        'engineer' => [
            'name' => 'Diego Vilela',
            'role' => 'Engenheiro Civil',
            'crea' => 'CREA-MG 235474/D',
            'email' => 'vilela.eng.mg@gmail.com',
            'phone' => '(35) 98452-9577'
        ],
        'user_photo' => $foto_perfil ? "../area-cliente/$foto_perfil" : null // Path relative to where React might access or absolute URL if needed
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}
?>
