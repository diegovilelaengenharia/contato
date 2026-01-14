<?php
// includes/helpers.php

/**
 * Sanitiza output HTML para prevenir XSS.
 * Use sempre que for exibir dados vindos do banco ou input de usuário.
 */
function safe_html($value) {
    if ($value === null) return '';
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata valor monetário para BRL
 */
function format_money($value) {
    if ($value === null) return 'R$ 0,00';
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata data para o padrão Brasileiro (d/m/Y)
 */
function format_date($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

/**
 * Formata data e hora para o padrão Brasileiro (d/m/Y H:i)
 */
function format_datetime($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}
?>
