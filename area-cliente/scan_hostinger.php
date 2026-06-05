<?php
/**
 * scan_hostinger.php - Scanner Temporário de Arquivos do Servidor Hostinger
 * 
 * Sobe até a raiz do usuário e varre de forma recursiva mapeando o tamanho
 * e localização de todas as pastas e arquivos para identificar lixos e redundâncias.
 * 
 * Proteção: Exige token de segurança.
 */

header('Content-Type: application/json; charset=utf-8');

$token_secreto = 'vilela_scan_2026';
$token_informado = $_GET['token'] ?? '';

if ($token_informado !== $token_secreto) {
    http_response_code(403);
    echo json_encode(["erro" => "Acesso negado. Token inválido."]);
    exit;
}

// Tenta localizar a raiz do usuário u884436813
// Normalmente o script roda em /home/u884436813/domains/vilela.eng.br/public_html/area-cliente/
$currentPath = realpath(__DIR__);
$userRoot = dirname($currentPath, 4); // Sobe 4 níveis: de area-cliente -> public_html -> vilela.eng.br -> domains -> u884436813

if (!is_dir($userRoot) || !is_readable($userRoot)) {
    // Fallback: tenta subir de nível em nível até não conseguir mais
    $userRoot = $currentPath;
    while (dirname($userRoot) !== $userRoot && is_readable(dirname($userRoot))) {
        $userRoot = dirname($userRoot);
    }
}

// Limites para evitar estouro de memória e timeout
set_time_limit(120);
ini_set('memory_limit', '256M');

$result = [
    "user_root" => $userRoot,
    "scan_time" => date('c'),
    "tree" => []
];

function scanDirectory($dir, $depth = 0, $maxDepth = 5) {
    if ($depth > $maxDepth) return ["type" => "dir", "msg" => "Max depth reached", "size" => 0];
    
    $ignoredFolders = ['.git', '.github', '.pytest_cache', '__pycache__', '.composer', '.logs', '.ssh'];
    
    $size = 0;
    $items = [];
    
    $files = @scandir($dir);
    if ($files === false) {
        return ["type" => "dir", "msg" => "Permission denied", "size" => 0];
    }
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            if (in_array($file, $ignoredFolders)) {
                $items[$file] = ["type" => "dir", "status" => "ignored", "size" => 0];
                continue;
            }
            
            $subScan = scanDirectory($path, $depth + 1, $maxDepth);
            $size += $subScan["size"];
            $items[$file] = $subScan;
        } else {
            $fileSize = @filesize($path) ?: 0;
            $size += $fileSize;
            $items[$file] = [
                "type" => "file",
                "size" => $fileSize,
                "mtime" => date('c', @filemtime($path) ?: time())
            ];
        }
    }
    
    return [
        "type" => "dir",
        "size" => $size,
        "items" => $items
    ];
}

$result["tree"] = scanDirectory($userRoot);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
