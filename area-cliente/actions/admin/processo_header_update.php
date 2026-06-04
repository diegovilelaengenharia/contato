<?php
/**
 * Action: Atualizar Header do Processo
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar Auth Admin
// init.php já faz o check, mas vamos garantir e usar o Core se possível no futuro.
// Por enquanto init.php redireciona se não estiver logado.

// 2. Validar CSRF
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin.php");
    exit;
}

$pdo = Database::getInstance();

$cid = $_POST['cliente_id'];
$proc_num = $_POST['processo_numero'];
$proc_obj = $_POST['processo_objeto'];
$proc_map = $_POST['processo_link_mapa'];

// Campos Técnicos
$valor_venal = $_POST['valor_venal'] ?? null;
$area_total = $_POST['area_total_final'] ?? null;
$area_existente = $_POST['area_existente'] ?? null;
$area_acrescimo = $_POST['area_acrescimo'] ?? null;
$area_permeavel = $_POST['area_permeavel'] ?? null;
$taxa_ocupacao = $_POST['taxa_ocupacao'] ?? null;
$fator_aproveitamento = $_POST['fator_aproveitamento'] ?? null;
$geo_coords = $_POST['geo_coords'] ?? null;

// Upload de Foto de Capa (Obra)
$foto_path = null;
if(isset($_FILES['foto_capa_obra']) && $_FILES['foto_capa_obra']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['foto_capa_obra']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if(in_array($ext, $allowed)) {
        $new_name = "capa_obra_{$cid}_" . time() . ".$ext";
        $target_dir = __DIR__ . '/../../uploads/obras/';
        if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        if(move_uploaded_file($_FILES['foto_capa_obra']['tmp_name'], $target_dir . $new_name)) {
            $foto_path = "uploads/obras/" . $new_name;
        }
    }
}

try {
    if($foto_path) {
         $sql = "UPDATE processo_detalhes SET processo_numero=?, processo_objeto=?, processo_link_mapa=?, valor_venal=?, area_total_final=?, foto_capa_obra=?, area_existente=?, area_acrescimo=?, area_permeavel=?, taxa_ocupacao=?, fator_aproveitamento=?, geo_coords=? WHERE cliente_id=?";
         $pdo->prepare($sql)->execute([$proc_num, $proc_obj, $proc_map, $valor_venal, $area_total, $foto_path, $area_existente, $area_acrescimo, $area_permeavel, $taxa_ocupacao, $fator_aproveitamento, $geo_coords, $cid]);
    } else {
         $sql = "UPDATE processo_detalhes SET processo_numero=?, processo_objeto=?, processo_link_mapa=?, valor_venal=?, area_total_final=?, area_existente=?, area_acrescimo=?, area_permeavel=?, taxa_ocupacao=?, fator_aproveitamento=?, geo_coords=? WHERE cliente_id=?";
         $pdo->prepare($sql)->execute([$proc_num, $proc_obj, $proc_map, $valor_venal, $area_total, $area_existente, $area_acrescimo, $area_permeavel, $taxa_ocupacao, $fator_aproveitamento, $geo_coords, $cid]);
    }
    
    $_SESSION['flash_message'] = ['text' => 'Dados do processo atualizados com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;
} catch(PDOException $e) {
    $_SESSION['flash_message'] = ['text' => 'Erro ao atualizar dados do processo: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;
}
