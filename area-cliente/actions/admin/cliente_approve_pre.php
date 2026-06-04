<?php
/**
 * Action: Aprovar Pré-Cadastro de Cliente
 * Extratado de includes/processamento.php
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';

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

try {
    $pid = $_POST['id_pre'];
    $nome_final = trim($_POST['nome_final']);
    $usuario_final = trim($_POST['usuario_final']);
    $senha_final = trim($_POST['senha_final']);
    $senha_hash = password_hash($senha_final, PASSWORD_DEFAULT);

    // 1. Pegar dados do pré-cadastro
    $pre = $pdo->prepare("SELECT * FROM pre_cadastros WHERE id = ?");
    $pre->execute([$pid]);
    $solicitacao = $pre->fetch();

    if ($solicitacao) {
        $pdo->beginTransaction();

        // 2. Criar Cliente na tabela oficial
        $sqlCliente = "INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)";
        $pdo->prepare($sqlCliente)->execute([$nome_final, $usuario_final, $senha_hash]);
        $novo_id = $pdo->lastInsertId();

        // 3. Criar Detalhes
        $sqlDet = "INSERT INTO processo_detalhes (cliente_id, cpf_cnpj, contato_tel, contato_email, tipo_servico) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sqlDet)->execute([
            $novo_id,
            $solicitacao['cpf_cnpj'],
            $solicitacao['telefone'],
            $solicitacao['email'],
            $solicitacao['tipo_servico']
        ]);

        // 4. Deletar Pré-Cadastro
        $pdo->prepare("DELETE FROM pre_cadastros WHERE id = ?")->execute([$pid]);

        $pdo->commit();

        // Redireciona para gerenciar o novo cliente
        $_SESSION['flash_message'] = ['text' => 'Pré-cadastro aprovado e cliente criado com sucesso!', 'type' => 'success'];
        header("Location: ../../admin/index.php?route=cliente-detalhes&id=$novo_id");
        exit;
    } else {
        throw new Exception("Solicitação de pré-cadastro não encontrada.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_message'] = ['text' => 'Erro ao aprovar cadastro: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=clientes");
    exit;
}
