<?php
// --- Taxas e Multas Padrão ---
return [
    'taxas' => [
        ['titulo' => 'Taxa de Aprovação de Projeto', 'lei' => 'Código de Posturas (2025)', 'desc' => 'R$ 4,28 por m²', 'valor' => '4.28'],
        ['titulo' => 'Taxa Edificações/Prédios/Galpões', 'lei' => 'Código de Posturas (2025)', 'desc' => 'R$ 4,28 por m²', 'valor' => '4.28'],
        ['titulo' => 'Alteração de Projeto Aprovado', 'lei' => 'Código de Posturas (2025)', 'desc' => 'R$ 4,28 por m²', 'valor' => '4.28'],
        ['titulo' => 'Taxa de Reformas/Demolições', 'lei' => 'Código de Posturas (2025)', 'desc' => 'R$ 2,14 por m²', 'valor' => '2.14'],
        ['titulo' => 'Taxa Unificação/Divisão de Áreas', 'lei' => 'Código de Posturas (2025)', 'desc' => 'R$ 2,14 por m²', 'valor' => '2.14'],
        ['titulo' => 'Taxa de Habite-se (Única)', 'lei' => 'Código de Posturas (2025)', 'desc' => 'Vistoria, valor fixo.', 'valor' => '81.20'],
        ['titulo' => 'Averbação em Cartório', 'lei' => 'Lei 6.015/73', 'desc' => 'Valor aproximado.', 'valor' => '800.00']
    ],
    'multas' => [
        ['titulo' => 'Multa: Obra s/ Alvará (até 40m²)', 'lei' => 'Lei 267/2019', 'desc' => '1x Valor Aprovação (R$ 4,28/m²)', 'valor' => '171.20'],
        ['titulo' => 'Multa: Obra s/ Alvará (40-80m²)', 'lei' => 'Lei 267/2019', 'desc' => '3x Valor Aprovação (R$ 12,85/m²)', 'valor' => '514.00'],
        ['titulo' => 'Multa: Obra s/ Alvará (80-100m²)', 'lei' => 'Lei 267/2019', 'desc' => '6x Valor Aprovação (R$ 26,75/m²)', 'valor' => '2140.00'],
        ['titulo' => 'Multa: Obra s/ Alvará (>100m²)', 'lei' => 'Lei 267/2019', 'desc' => '10x Valor Aprovação (R$ 42,85/m²)', 'valor' => '4285.00'],
        ['titulo' => 'Multa: Início sem Licença (até 60m²)', 'lei' => 'Art. 79 Cód. Obras', 'desc' => 'R$ 0,90 por m²', 'valor' => '54.00'],
        ['titulo' => 'Multa: Execução em Desacordo', 'lei' => 'Art. 79 Cód. Obras', 'desc' => 'Execução diferente do aprovado.', 'valor' => '90.60'],
        ['titulo' => 'Multa: Omissão Topografia/Águas', 'lei' => 'Art. 79 Cód. Obras', 'desc' => 'Omitir cursos d\'água ou topografia.', 'valor' => '45.31'],
        ['titulo' => 'Multa: Falta de Projeto na Obra', 'lei' => 'Art. 79 Cód. Obras', 'desc' => 'Não manter projeto/alvará no local.', 'valor' => '18.10'],
        ['titulo' => 'Multa: Materiais na Calçada', 'lei' => 'Art. 79 Cód. Obras', 'desc' => 'Obstrução de passeio além do tempo.', 'valor' => '18.10']
    ]
];
?>
