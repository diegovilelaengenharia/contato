<?php
/**
 * Action: Atualizar Prazo da Prefeitura do Processo
 * Trata o salvamento assíncrono do prazo limite de andamentos da prefeitura
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/index.php");
    exit;
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['format']) && $_POST['format'] === 'json')
    || (isset($_GET['format']) && $_GET['format'] === 'json');

// Validar CSRF
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro de segurança (CSRF). Recarregue a página.']);
        exit;
    }
    $_SESSION['flash_message'] = ['text' => 'Erro de segurança (CSRF). Recarregue a página.', 'type' => 'error'];
    header("Location: ../../admin/index.php");
    exit;
}

$pdo = Database::getInstance();

$cid = (int)($_POST['cliente_id'] ?? 0);
$prazo_data = $_POST['prazo_prefeitura_data'] ?? null;
$prazo_desc = trim($_POST['prazo_prefeitura_descricao'] ?? '');

// Normaliza campos vazios para NULL no banco
if (empty($prazo_data)) {
    $prazo_data = null;
}
if (empty($prazo_desc)) {
    $prazo_desc = null;
}

try {
    if (!$cid) {
        throw new Exception("ID do cliente inválido.");
    }

    // Verifica se já existe um registro na tabela processo_detalhes para o cliente
    $stmtCh = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $stmtCh->execute([$cid]);
    $exists = $stmtCh->fetch();

    if ($exists) {
        $pdo->prepare("UPDATE processo_detalhes SET prazo_prefeitura_data = ?, prazo_prefeitura_descricao = ? WHERE cliente_id = ?")
            ->execute([$prazo_data, $prazo_desc, $cid]);
    } else {
        $pdo->prepare("INSERT INTO processo_detalhes (cliente_id, prazo_prefeitura_data, prazo_prefeitura_descricao) VALUES (?, ?, ?)")
            ->execute([$cid, $prazo_data, $prazo_desc]);
    }

    // Gravar Log de Auditoria
    Logger::log('UPDATE', 'processo_prazo_prefeitura', $cid, [
        'prazo_prefeitura_data' => $prazo_data,
        'prazo_prefeitura_descricao' => $prazo_desc
    ]);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Prazo da prefeitura atualizado com sucesso!',
            'prazo_data' => $prazo_data ? date('d/m/Y', strtotime($prazo_data)) : null,
            'prazo_desc' => $prazo_desc
        ]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Prazo da prefeitura atualizado com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;

} catch (Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar prazo: ' . $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao atualizar prazo: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid&tab=timeline");
    exit;
}
