<?php
// relatorio_cliente.php
session_start();
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    die("Acesso Negado");
}
require 'db.php';

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) die("ID não fornecido");

// Buscar Dados Completo
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

$stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmtDet->execute([$cliente_id]);
$detalhes = $stmtDet->fetch();

$stmtMov = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
$stmtMov->execute([$cliente_id]);
$movimentos = $stmtMov->fetchAll();

$stmtEx = $pdo->prepare("SELECT * FROM processo_campos_extras WHERE cliente_id = ?");
$stmtEx->execute([$cliente_id]);
$extras = $stmtEx->fetchAll();

// Buscar Financeiro (Para paridade com relatório antigo)
$stmtFin = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
$stmtFin->execute([$cliente_id]);
$financeiro = $stmtFin->fetchAll();

// Totais Financeiros
$total_hon = 0; $total_taxas = 0; $total_pago = 0; $total_pendente = 0;
foreach($financeiro as $item) {
    if($item['categoria']=='honorarios') $total_hon += $item['valor'];
    else $total_taxas += $item['valor'];
    
    if($item['status']=='pago') $total_pago += $item['valor'];
    elseif($item['status']=='pendente' || $item['status']=='atrasado') $total_pendente += $item['valor'];
}

// Configurações Visuais
$primary_color = '#005f73'; // Vilela Oficial (aprox)
$text_color = '#333';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório - <?= htmlspecialchars($cliente['nome']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?= $primary_color ?>;
            --text: <?= $text_color ?>;
        }
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text);
            margin: 0;
            padding: 40px;
            background: #fff; /* Fundo branco para impressão */
            font-size: 12pt;
        }
        
        /* A4 Page Setup for Screen Preview */
        @media screen {
            body {
                background: #f0f0f0;
                display: flex;
                justify-content: center;
                padding-top: 20px;
            }
            .page {
                background: white;
                width: 210mm;
                min-height: 297mm;
                padding: 20mm;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .no-print {
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--primary);
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                cursor: pointer;
                transition: 0.2s;
            }
            .no-print:hover { transform: translateY(-2px); }
        }

        @media print {
            @page { margin: 0; size: A4; }
            body { margin: 0; padding: 0; background: white; }
            .page { width: 100%; border: none; padding: 20mm; box-sizing: border-box; }
            .no-print { display: none; }
            h1, h2, h3 { page-break-after: avoid; }
            table, tr, td, .item { page-break-inside: avoid; }
        }

        /* Layout Components */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo h1 {
            margin: 0;
            color: var(--primary);
            font-size: 24pt;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .logo span {
            font-size: 10pt;
            color: #666;
            letter-spacing: 4px;
            text-transform: uppercase;
        }
        .meta-header {
            text-align: right;
            font-size: 9pt;
            color: #777;
            line-height: 1.4;
        }

        .section-title {
            background: #f8f9fa;
            border-left: 5px solid var(--primary);
            padding: 8px 15px;
            font-size: 14pt;
            font-weight: 700;
            color: var(--primary);
            margin: 30px 0 15px 0;
            text-transform: uppercase;
        }

        .grid-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item label {
            display: block;
            font-size: 8pt;
            text-transform: uppercase;
            color: #888;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-item span {
            font-size: 11pt;
            font-weight: 500;
            color: #222;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        th {
            font-size: 9pt;
            text-transform: uppercase;
            color: #666;
            font-weight: 700;
            border-bottom: 2px solid #ddd;
        }
        td {
            font-size: 10pt;
        }

        /* Timeline for History */
        .timeline {
            border-left: 2px solid #ddd;
            margin-left: 10px;
            padding-left: 20px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 1px #ddd;
        }
        .timeline-date {
            font-size: 9pt;
            color: #888;
            font-weight: 600;
        }
        .timeline-title {
            font-size: 11pt;
            font-weight: 700;
            color: #333;
        }
        .timeline-desc {
            font-size: 10pt;
            color: #555;
            margin-top: 3px;
        }

        .footer {
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 9pt;
            color: #aaa;
        }
    </style>
</head>
<body>

    <a href="javascript:window.print()" class="no-print">🖨️ Imprimir / Salvar PDF</a>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <!-- Tenta carregar logo se existir, senão texto -->
                <img src="../logo.png" alt="Vilela Engenharia" style="max-height: 80px; display: block;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <h1 style="display:none;">Vilela</h1>
                <span style="display:none;">Engenharia</span>
            </div>
            <div class="meta-header">
                <strong>Relatório de Processo</strong><br>
                <?php if(!empty($detalhes['data_inicio'])): ?>
                    <span style="color:var(--primary);">Início: <?= date('d/m/Y', strtotime($detalhes['data_inicio'])) ?></span><br>
                <?php endif; ?>
                Gerado em: <?= date('d/m/Y H:i') ?><br>
                ID Cliente: #<?= str_pad($cliente['id'], 3, '0', STR_PAD_LEFT) ?>
            </div>
        </div>

        <!-- Section: Perfil -->
        <div class="section-title">Dados do Cliente</div>
        <div class="grid-info">
            <div class="info-item">
                <label>Nome Completo</label>
                <span><?= htmlspecialchars($cliente['nome']) ?></span>
            </div>
            <div class="info-item">
                <label>CPF / CNPJ</label>
                <span><?= htmlspecialchars($detalhes['cpf_cnpj'] ?? 'Não informado') ?></span>
            </div>
            <div class="info-item">
                <label>Telefone</label>
                <span><?= htmlspecialchars($detalhes['contato_tel'] ?? 'Não informado') ?></span>
            </div>
            <div class="info-item">
                <label>Email</label>
                <span><?= htmlspecialchars($detalhes['contato_email'] ?? 'Não informado') ?></span>
            </div>
        </div>
        
        <div class="info-item" style="margin-top:10px; border-top:1px dashed #eee; padding-top:10px;">
            <label>Endereço Residencial</label>
            <span>
                <?= htmlspecialchars($detalhes['res_rua'] ?? '') ?>, <?= htmlspecialchars($detalhes['res_numero'] ?? '') ?> - 
                <?= htmlspecialchars($detalhes['res_bairro'] ?? '') ?>. <?= htmlspecialchars($detalhes['res_cidade'] ?? '') ?>/<?= htmlspecialchars($detalhes['res_uf'] ?? '') ?>
            </span>
        </div>

        <!-- Section: Imóvel -->
        <div class="section-title">Dados do Imóvel / Obra</div>
        <div class="grid-info">
             <div class="info-item">
                <label>Logradouro</label>
                <span><?= htmlspecialchars($detalhes['imovel_rua'] ?? '-') ?>, <?= htmlspecialchars($detalhes['imovel_numero'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <label>Bairro / Cidade</label>
                <span><?= htmlspecialchars($detalhes['imovel_bairro'] ?? '-') ?> - <?= htmlspecialchars($detalhes['imovel_cidade'] ?? '-') ?>/<?= htmlspecialchars($detalhes['imovel_uf'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <label>Inscrição Imobiliária (IPTU)</label>
                <span><?= htmlspecialchars($detalhes['inscricao_imob'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Matrícula</label>
                <span><?= htmlspecialchars($detalhes['num_matricula'] ?? 'N/A') ?></span>
            </div>
             <div class="info-item">
                <label>Área do Lote</label>
                <span><?= htmlspecialchars($detalhes['imovel_area_lote'] ?? '-') ?> m²</span>
            </div>
             <div class="info-item">
                <label>Área Construída</label>
                <span><?= htmlspecialchars($detalhes['area_construida'] ?? '-') ?> m²</span>
            </div>
        </div>

        <!-- Section: Resp Técnica (Paridade Old Report) -->
        <div class="section-title">Responsabilidade Técnica & Status</div>
        <div class="grid-info">
             <div class="info-item">
                <label>Responsável Técnico</label>
                <span><?= htmlspecialchars($detalhes['resp_tecnico'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <label>Registro (CREA/CAU)</label>
                <span><?= htmlspecialchars($detalhes['registro_prof'] ?? '-') ?></span>
            </div>
             <div class="info-item">
                <label>Fase Atual do Processo</label>
                <span style="background:var(--primary); color:white; padding:2px 8px; border-radius:4px; font-size:10pt;"><?= htmlspecialchars($detalhes['etapa_atual'] ?? 'Não iniciado') ?></span>
            </div>
        </div>

        <?php if(!empty($detalhes['texto_pendencias'])): ?>
            <div style="margin-top:10px; border:1px solid #ffc107; background:#fffbf2; padding:10px; border-radius:4px; color:#856404; font-size:10pt;">
                <strong>⚠️ Pendências Ativas:</strong><br>
                <?= nl2br(htmlspecialchars($detalhes['texto_pendencias'])) ?>
            </div>
        <?php endif; ?>

        <!-- Section: Financeiro -->
        <div class="section-title">Relatório Financeiro</div>
        <?php if(count($financeiro) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:15%">Vencimento</th>
                        <th style="width:15%">Categoria</th>
                        <th style="width:40%">Descrição</th>
                        <th style="width:15%">Valor</th>
                        <th style="width:15%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($financeiro as $fin): 
                        $stStyle = "background:#eee; color:#555;";
                        if($fin['status']=='pago') $stStyle = "background:#d1e7dd; color:#0f5132;";
                        elseif($fin['status']=='atrasado') $stStyle = "background:#f8d7da; color:#842029;";
                        elseif($fin['status']=='pendente') $stStyle = "background:#fff3cd; color:#856404;";
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($fin['data_vencimento'])) ?></td>
                        <td><?= ucfirst($fin['categoria']) ?></td>
                        <td><?= htmlspecialchars($fin['descricao']) ?></td>
                        <td>R$ <?= number_format($fin['valor'], 2, ',', '.') ?></td>
                        <td><span style="padding:2px 6px; border-radius:4px; font-weight:bold; font-size:9pt; text-transform:uppercase; <?= $stStyle ?>"><?= $fin['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="display:flex; gap:20px; background:#f9f9f9; padding:15px; margin-top:10px; border:1px solid #eee; justify-content:flex-end;">
                 <div style="text-align:right;">
                    <small>Total Pago</small><br>
                    <strong style="color:#0f5132; font-size:12pt;">R$ <?= number_format($total_pago, 2, ',', '.') ?></strong>
                </div>
                 <div style="text-align:right;">
                    <small>Total Pendente</small><br>
                    <strong style="color:#b71c1c; font-size:12pt;">R$ <?= number_format($total_pendente, 2, ',', '.') ?></strong>
                </div>
            </div>
        <?php else: ?>
            <p style="color:#888; font-style:italic;">Nenhum registro financeiro encontrado.</p>
        <?php endif; ?>

        <?php if(count($extras) > 0): ?>
            <div class="section-title">Outras Informações</div>
            <table style="width:100%; font-size:10pt;">
                <?php foreach($extras as $ex): ?>
                    <tr>
                        <td style="width:40%; font-weight:bold; color:#555;"><?= htmlspecialchars($ex['titulo']) ?></td>
                        <td><?= htmlspecialchars($ex['valor']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <!-- Section: Histórico -->
        <div class="section-title" style="page-break-before: always;">Andamento do Processo</div>
        
        <?php if(count($movimentos) > 0): ?>
            <div class="timeline">
                <?php foreach($movimentos as $mov): ?>
                    <div class="timeline-item">
                        <div class="timeline-date"><?= date('d/m/Y \à\s H:i', strtotime($mov['data_movimento'])) ?></div>
                        <div class="timeline-title"><?= htmlspecialchars($mov['titulo_fase']) ?></div>
                        <?php if(!empty($mov['descricao'])): ?>
                            <div class="timeline-desc"><?= nl2br(htmlspecialchars($mov['descricao'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#888; font-style:italic;">Nenhuma atividade registrada até o momento.</p>
        <?php endif; ?>

        
        <div class="footer">
            Vilela Engenharia - Documento Confidencial<br>
            www.vilelaengenharia.com.br
        </div>
    </div>

    <script>
        // Auto-print option on load
        // window.onload = function() { setTimeout(function(){ window.print(); }, 1000); }
    </script>
</body>
</html>
