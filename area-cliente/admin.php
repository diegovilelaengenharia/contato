<?php
/**
 * Wrapper de Compatibilidade Legada
 *
 * Mapeia e redireciona de forma inteligente as requisições antigas
 * para a nova estrutura unificada do painel em admin/index.php,
 * preservando mensagens flash de feedback.
 */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite'  => 'Lax',
]);
session_name('CLIENTE_SESSID');
session_start();

$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;
$tab = $_GET['tab'] ?? null;
$msg = $_GET['msg'] ?? null;
$importar = isset($_GET['importar']) ? $_GET['importar'] : null;

// Tradução de Mensagens Antigas para as novas Flash Messages elegantes
if ($msg) {
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Operação realizada com sucesso!'];
    
    switch ($msg) {
        case 'welcome':
            $_SESSION['flash_message']['text'] = 'Cadastro do cliente concluído com sucesso!';
            break;
        case 'mov_added':
            $_SESSION['flash_message']['text'] = 'Andamento registrado com sucesso na timeline!';
            break;
        case 'mov_deleted':
            $_SESSION['flash_message']['text'] = 'Movimentação excluída do histórico do processo.';
            break;
        case 'fin_added':
            $_SESSION['flash_message']['text'] = 'Novo lançamento financeiro adicionado com sucesso!';
            break;
        case 'fin_deleted':
            $_SESSION['flash_message']['text'] = 'Lançamento financeiro removido com sucesso.';
            break;
        case 'pen_added':
            $_SESSION['flash_message']['text'] = 'Pendência/Solicitação de documento aberta para o cliente!';
            break;
        case 'pen_deleted':
            $_SESSION['flash_message']['text'] = 'Pendência removida do sistema.';
            break;
        case 'ent_uploaded':
            $_SESSION['flash_message']['text'] = 'Documento entregável enviado com sucesso para o cliente!';
            break;
        case 'ent_deleted':
            $_SESSION['flash_message']['text'] = 'Documento entregável removido da Área do Cliente.';
            break;
        case 'doc_approved':
            $_SESSION['flash_message']['text'] = 'Documento do cliente aprovado com sucesso!';
            break;
        case 'doc_rejected':
            $_SESSION['flash_message']['text'] = 'Documento recusado e notificação enviada.';
            break;
    }
}

// Redireciona se for página de importação de pré-cadastros
if ($importar) {
    header("Location: admin/index.php?route=clientes&importar=1");
    exit;
}

// Redireciona se for detalhe de um cliente
if ($cliente_id) {
    // Mapeia abas antigas para a estrutura nova
    $new_tab = 'timeline';
    switch ($tab) {
        case 'cadastro':
        case 'dados':
            $new_tab = 'dados';
            break;
        case 'financeiro':
            $new_tab = 'financeiro';
            break;
        case 'pendencias':
            $new_tab = 'pendencias';
            break;
        case 'docs_iniciais':
        case 'arquivos':
        case 'docs':
            $new_tab = 'documentos';
            break;
    }
    
    header("Location: admin/index.php?route=cliente-detalhes&id={$cliente_id}&tab={$new_tab}");
    exit;
}

// Rota padrão final do admin
header("Location: admin/index.php?route=dashboard");
exit;
