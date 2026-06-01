<?php
/**
 * Wrapper de Compatibilidade Legada
 *
 * Mapeia e redireciona chamadas de edição ou cadastro do cliente
 * para a nova super view mestre em admin/index.php.
 */
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    header("Location: admin/index.php?route=cliente-detalhes&id={$id}&tab=dados");
} else {
    header("Location: admin/index.php?route=cliente-detalhes&action=new");
}
exit;
