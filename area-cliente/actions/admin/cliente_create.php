<?php
/**
 * Action: Criar Novo Cliente
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

// 1. Validar CSRF
if (isset($_POST['csrf_token']) && !Csrf::validateToken($_POST['csrf_token'])) {
    die("Erro de validação CSRF.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin.php");
    exit;
}

$pdo = Database::getInstance();

try {
    $nome_original = $_POST['nome'];
    $cpf = $_POST['cpf_cnpj'];
    $tel = $_POST['contato_tel']; 
    $senha_plain = $_POST['senha'] ?? $_POST['nova_senha']; 

    // Lógica de Login
    $usuario_final = '';
    if(!empty($_POST['usuario'])) {
        $usuario_final = trim($_POST['usuario']);
    } else {
        $tipo_login = $_POST['tipo_login'] ?? 'cpf';
        $usuario_final = preg_replace('/[^0-9]/', '', ($tipo_login == 'cpf' ? $cpf : $tel));
    }
    
    if(empty($usuario_final)) throw new Exception("Login (Usuário) não pode ser vazio.");
    if(empty($nome_original) || empty($senha_plain)) throw new Exception("Nome, Usuário e Senha são obrigatórios.");

    $pass = password_hash($senha_plain, PASSWORD_DEFAULT);
    
    // Inicia Transação
    $pdo->beginTransaction();

    // 1. Verifica duplicidade
    $check = $pdo->prepare("SELECT id FROM clientes WHERE usuario = ?");
    $check->execute([$usuario_final]);
    if($check->rowCount() > 0) throw new Exception("Este login ($usuario_final) já está em uso.");

    // 2. Inserir Cliente Base
    $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)")->execute([trim($nome_original), $usuario_final, $pass]);
    $novo_id = $pdo->lastInsertId();

    // 3. Inserir Detalhes
    $sql_det = "INSERT INTO processo_detalhes (cliente_id, cpf_cnpj, contato_tel, contato_email, tipo_servico, imovel_rua, imovel_numero, imovel_bairro, imovel_cidade, imovel_uf, tipo_processo_chave, data_inicio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $pdo->prepare($sql_det)->execute([
        $novo_id, 
        $cpf, 
        $tel, 
        $_POST['contato_email'] ?? '', 
        $_POST['tipo_servico'] ?? '',
        $_POST['imovel_rua'] ?? '',
        $_POST['imovel_numero'] ?? '',
        $_POST['imovel_bairro'] ?? '',
        $_POST['imovel_cidade'] ?? '',
        $_POST['imovel_uf'] ?? '',
        $_POST['tipo_processo_chave'] ?? ''
    ]);

    // 4. Salvar Campos Extras
    if (isset($_POST['extra_titulos']) && is_array($_POST['extra_titulos'])) {
        $titulos = $_POST['extra_titulos'];
        $valores = $_POST['extra_valores'] ?? [];
        $stmt_ex = $pdo->prepare("INSERT INTO processo_campos_extras (cliente_id, titulo, valor) VALUES (?, ?, ?)");
        foreach ($titulos as $idx => $tit) {
            if (!empty($tit)) {
                $stmt_ex->execute([$novo_id, $tit, $valores[$idx] ?? '']);
            }
        }
    }

    $pdo->commit();
    header("Location: ../../admin.php?cliente_id=$novo_id&msg=client_created");
    exit;

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Erro ao criar cliente: " . $e->getMessage());
}
