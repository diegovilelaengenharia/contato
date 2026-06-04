<?php
/**
 * Action: Atualizar Notas Internas do Processo
 * Trata o salvamento de anotações privadas do administrador
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
$notas = trim($_POST['notas_internas'] ?? '');

if (empty($notas)) {
    $notas = null;
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
        $pdo->prepare("UPDATE processo_detalhes SET notas_internas = ? WHERE cliente_id = ?")
            ->execute([$notas, $cid]);
    } else {
        $pdo->prepare("INSERT INTO processo_detalhes (cliente_id, notas_internas) VALUES (?, ?)")
            ->execute([$cid, $notas]);
    }

    // Gravar Log de Auditoria
    Logger::log('UPDATE', 'processo_notas_internas', $cid, [
        'notas_tamanho' => $notas ? strlen($notas) : 0
    ]);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Notas internas salvas com sucesso!'
        ]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Notas internas salvas com sucesso!', 'type' => 'success'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid");
    exit;

} catch (Exception $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar notas internas: ' . $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_message'] = ['text' => 'Erro ao salvar notas internas: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: ../../admin/index.php?route=cliente-detalhes&id=$cid");
    exit;
}
