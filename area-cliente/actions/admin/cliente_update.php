<?php
/**
 * Action: Atualizar Cadastro Completo do Cliente
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Upload.php';

// 1. Validar CSRF
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
$cliente_id = $_POST['cliente_id'];

try {
    $pdo->beginTransaction();

    // 1. Atualizar Clientes (Login + Nome + Foto)
    $sqlCli = "UPDATE clientes SET nome=?, usuario=? WHERE id=?";
    $paramsCli = [trim($_POST['nome']), $_POST['usuario'], $cliente_id];
    
    if (!empty($_POST['nova_senha'])) {
        $sqlCli = "UPDATE clientes SET nome=?, usuario=?, senha=? WHERE id=?";
        $paramsCli = [trim($_POST['nome']), $_POST['usuario'], password_hash($_POST['nova_senha'], PASSWORD_DEFAULT), $cliente_id];
    }
    $pdo->prepare($sqlCli)->execute($paramsCli);

    // Upload Avatar
    if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../../uploads/avatars/';
        
        // Remove antigos
        array_map('unlink', glob($dir . "avatar_{$cliente_id}*.*"));
        
        $res = Upload::process($_FILES['avatar_upload'], $dir, "avatar_{$cliente_id}");
        if ($res['success']) {
            $rel_path = "uploads/avatars/" . basename($res['file_path']);
            $pdo->prepare("UPDATE clientes SET foto_perfil=? WHERE id=?")->execute([$rel_path, $cliente_id]);
        } else {
            throw new Exception("Erro no upload do avatar: " . $res['message']);
        }
    }

    // Upload Capa Obra
    if(isset($_FILES['foto_capa_obra']) && $_FILES['foto_capa_obra']['error'] == UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../../uploads/obras/';
        
        // Remove antigos
        array_map('unlink', glob($dir . "capa_obra_{$cliente_id}_*.*"));
        
        $res = Upload::process($_FILES['foto_capa_obra'], $dir, "capa_obra_{$cliente_id}");
        if ($res['success']) {
            $rel_path = "uploads/obras/" . basename($res['file_path']);
            $pdo->prepare("UPDATE processo_detalhes SET foto_capa_obra=? WHERE cliente_id=?")->execute([$rel_path, $cliente_id]);
        } else {
            throw new Exception("Erro no upload da capa da obra: " . $res['message']);
        }
    }

    // 2. Atualizar Detalhes
    $paramsDet = [
        $_POST['tipo_pessoa'] ?? 'fisica', 
        $_POST['cpf_cnpj'], 
        $_POST['rg_ie'] ?? '', 
        $_POST['nacionalidade'] ?? '', 
        $_POST['data_nascimento'] ?: null, 
        $_POST['contato_email'] ?? '', 
        $_POST['contato_tel'] ?? '',
        $_POST['res_rua'] ?? '', 
        $_POST['res_numero'] ?? '', 
        $_POST['res_bairro'] ?? '', 
        $_POST['res_complemento'] ?? '', 
        $_POST['res_cidade'] ?? '', 
        $_POST['res_uf'] ?? '',
        $_POST['profissao'] ?? '', 
        $_POST['estado_civil'] ?? '', 
        $_POST['nome_conjuge'] ?? null, 
        $_POST['tipo_servico'] ?? null, 
        $_POST['tipo_processo_chave'] ?? null, 
        $_POST['imovel_rua'] ?? '', 
        $_POST['imovel_numero'] ?? '',
        $_POST['imovel_bairro'] ?? '', 
        $_POST['imovel_complemento'] ?? '', 
        $_POST['imovel_cidade'] ?? '', 
        $_POST['imovel_uf'] ?? '', 
        $_POST['inscricao_imob'] ?? '',
        $_POST['num_matricula'] ?? '', 
        $_POST['imovel_area_lote'] ?? '', 
        $_POST['area_construida'] ?? '',
        
        $_POST['processo_objeto'] ?? null, 
        $_POST['processo_numero'] ?? null, 
        $_POST['area_total_final'] ?? null,
        $_POST['valor_venal'] ?? null, 
        $_POST['area_existente'] ?? null, 
        $_POST['area_acrescimo'] ?? null, 
        $_POST['area_permeavel'] ?? null,
        $_POST['taxa_ocupacao'] ?? null, 
        $_POST['fator_aproveitamento'] ?? null, 
        $_POST['geo_coords'] ?? null,
        $_POST['observacoes_gerais'] ?? null,

        $_POST['cpf_conjuge'] ?? null,
        $_POST['eh_procurador'] ?? 0,
        $_POST['data_inicio'] ?? null,

        ($_POST['imovel_rua'] ?? '') . ', ' . ($_POST['imovel_numero'] ?? '') . ' - ' . ($_POST['imovel_bairro'] ?? '') . ' - ' . ($_POST['imovel_cidade'] ?? '') . '/' . ($_POST['imovel_uf'] ?? ''),
        ($_POST['res_rua'] ?? '') . ', ' . ($_POST['res_numero'] ?? '') . ' - ' . ($_POST['res_bairro'] ?? '') . ' - ' . ($_POST['res_cidade'] ?? '') . '/' . ($_POST['res_uf'] ?? ''),

        $cliente_id
    ];

    $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id=?");
    $check->execute([$cliente_id]);
    
    if($check->rowCount() > 0) {
        $sqlDet = "UPDATE processo_detalhes SET 
            tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, nacionalidade=?, data_nascimento=?, contato_email=?, contato_tel=?, 
            res_rua=?, res_numero=?, res_bairro=?, res_complemento=?, res_cidade=?, res_uf=?,
            profissao=?, estado_civil=?, nome_conjuge=?, tipo_servico=?, 
            tipo_processo_chave=?, 
            imovel_rua=?, imovel_numero=?,
            imovel_bairro=?, imovel_complemento=?, imovel_cidade=?, imovel_uf=?, inscricao_imob=?,
            num_matricula=?, imovel_area_lote=?, area_construida=?,
            
            processo_objeto=?, processo_numero=?, area_total_final=?,
            valor_venal=?, area_existente=?, area_acrescimo=?, area_permeavel=?, taxa_ocupacao=?, fator_aproveitamento=?, geo_coords=?,
            observacoes_gerais=?,
            cpf_conjuge=?, eh_procurador=?, data_inicio=?, 
            endereco_imovel=?, endereco_residencial=?
            WHERE cliente_id=?";
    } else {
         $sqlDet = "INSERT INTO processo_detalhes (
            tipo_pessoa, cpf_cnpj, rg_ie, nacionalidade, data_nascimento, contato_email, contato_tel, 
            res_rua, res_numero, res_bairro, res_complemento, res_cidade, res_uf,
            profissao, estado_civil, nome_conjuge, tipo_servico,
            tipo_processo_chave,
            imovel_rua, imovel_numero,
            imovel_bairro, imovel_complemento, imovel_cidade, imovel_uf, inscricao_imob,
            num_matricula, imovel_area_lote, area_construida, 
            processo_objeto, processo_numero, area_total_final,
            valor_venal, area_existente, area_acrescimo, area_permeavel, taxa_ocupacao, fator_aproveitamento, geo_coords,
            observacoes_gerais,
            cpf_conjuge, eh_procurador, data_inicio,
            endereco_imovel, endereco_residencial,
            cliente_id
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    }
    
    $pdo->prepare($sqlDet)->execute($paramsDet);

    // 3. Atualizar Campos Extras
    $pdo->prepare("DELETE FROM processo_campos_extras WHERE cliente_id = ?")->execute([$cliente_id]);
    
    if (isset($_POST['extra_titulos']) && is_array($_POST['extra_titulos'])) {
        $titulos = $_POST['extra_titulos'];
        $valores = $_POST['extra_valores'] ?? [];
        $stmtInsEx = $pdo->prepare("INSERT INTO processo_campos_extras (cliente_id, titulo, valor) VALUES (?, ?, ?)");
        
        foreach ($titulos as $key => $titulo) {
            $titulo_limpo = trim($titulo);
            $valor_limpo = trim($valores[$key] ?? '');
            if (!empty($titulo_limpo)) {
                $stmtInsEx->execute([$cliente_id, $titulo_limpo, $valor_limpo]);
            }
        }
    }

    $pdo->commit();
    $_SESSION['flash_message'] = ['text' => 'Cadastro do cliente atualizado com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cliente_id&tab=dados");
    exit;

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_message'] = ['text' => 'Erro ao atualizar cliente: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cliente_id&tab=dados");
    exit;
}
