<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

try {
    // 1. If 'tipo' (or 'processo') is provided, return requirements for that process
    // Supports both exact match "Alvará de Construção" or slug-like "alvara_construcao" (naive approach)
    if (isset($_GET['tipo']) || isset($_GET['processo'])) {
        $input = $_GET['tipo'] ?? $_GET['processo'];
        
        // Simple slug decoding if needed (e.g. alvara_de_construcao -> Alvará de Construção could be tricky without a map)
        // For now, let's assume the Frontend sends the EXACT name from the list we will provide.
        // Or we can try to find a match.
        
        $sql = "SELECT * FROM requisitos_processo WHERE processo_nome = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize into Mandatory and Conditional
        $response = [
            'processo' => $input,
            'obrigatorios' => [],
            'condicionais' => []
        ];
        
        foreach($rows as $r) {
            $item = [
                'id' => $r['id'],
                'nome' => $r['nome_documento'],
                'formato' => $r['formato'],
                'obs' => $r['obs']
            ];
            
            if ($r['tipo_exigencia'] === 'obrigatorio') {
                $response['obrigatorios'][] = $item;
            } else {
                $response['condicionais'][] = $item;
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    // 2. Otherwise, return the list of available Process Types (for the dropdown)
    $stmt = $pdo->query("SELECT DISTINCT processo_grupo, processo_nome FROM requisitos_processo ORDER BY processo_grupo, processo_nome");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by Group
    $grouped = [];
    foreach($all as $row) {
        $g = $row['processo_grupo'];
        if (!isset($grouped[$g])) $grouped[$g] = [];
        $grouped[$g][] = $row['processo_nome'];
    }
    
    echo json_encode(['tipos' => $grouped]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
