<?php
// Force session cookie to be available to entire domain
session_set_cookie_params(0, '/');
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
        "Protocolo e Autuação", 
        "Análise Documental", 
        "Vistoria Técnica In Loco",
        "Emissão de Laudos/Peças", 
        "Tramitação e Aprovação", 
        "Entrega Final/Habite-se"
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

    // Construct Response Object to match React App expectations
    $response = [
        'currentPhase' => $clientData['etapa_atual'] ?? 'Protocolo e Autuação',
        'processDetails' => [
            'number' => $clientData['numero_processo'] ?? 'N/A',
            'area' => $clientData['area_total'] ?? '0 m²',
            'object' => $clientData['objeto_processo'] ?? 'Consultoria',
            'address' => $clientData['endereco_imovel'] ?? 'Endereço não cadastrado'
        ],
        'timeline' => $timeline,
        'financeiro' => $financeiro,
        'pendencias' => $pendencias,
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
