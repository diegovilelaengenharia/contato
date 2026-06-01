<?php
/**
 * Wrapper de Compatibilidade Legada
 *
 * Redireciona de forma transparente para a nova página de configurações
 * no painel administrativo unificado.
 */
header("Location: admin/index.php?route=configuracoes");
exit;
