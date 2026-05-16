<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require 'db.php';

// Segurança: Garante que o cliente está logado
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

$cid = $_SESSION['cliente_id'];

// Buscas de Dados (Recortado e adaptado do admin)
$c = $pdo->prepare("SELECT * FROM clientes WHERE id=?"); 
$c->execute([$cid]); 
$cliente_dados = $c->fetch();

$d = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id=?"); 
$d->execute([$cid]); 
$detalhes = $d->fetch();

$f = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? ORDER BY data_vencimento ASC"); 
$f->execute([$cid]); 
$financeiro = $f->fetchAll();

$h = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id=? ORDER BY data_movimento DESC"); 
$h->execute([$cid]); 
$historico = $h->fetchAll();

// Totais Financeiros
$total_hon = 0; $total_taxas = 0; $total_pago = 0; $total_pendente = 0;
foreach($f as $item) {
    if($item['categoria']=='honorarios') $total_hon += $item['valor'];
    else $total_taxas += $item['valor'];
    
    if($item['status']=='pago') $total_pago += $item['valor'];
    elseif($item['status']=='pendente' || $item['status']=='atrasado') $total_pendente += $item['valor'];
}

// Montar Endereço Completo
$end_parts = [];
if(!empty($detalhes['imovel_rua'])) $end_parts[] = $detalhes['imovel_rua'];
if(!empty($detalhes['imovel_numero'])) $end_parts[] = $detalhes['imovel_numero'];
if(!empty($detalhes['imovel_bairro'])) $end_parts[] = "Bairro " . $detalhes['imovel_bairro'];
if(!empty($detalhes['imovel_complemento'])) $end_parts[] = $detalhes['imovel_complemento'];
if(!empty($detalhes['imovel_cidade'])) $end_parts[] = $detalhes['imovel_cidade'] . "/" . $detalhes['imovel_uf'];

$endereco_final = !empty($end_parts) ? implode(', ', $end_parts) : ($detalhes['endereco_imovel'] ?? '--');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo do Processo - <?= htmlspecialchars($cliente_dados['nome']) ?></title>
    <style>
        @page { size: A4; margin: 20mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.4; font-size: 12px; }
        .header { border-bottom: 2px solid #146c43; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header img { height: 50px; }
        .header-info { text-align: right; color: #555; }
        h1 { font-size: 24px; color: #146c43; margin: 0; text-transform: uppercase; font-weight: 800; }
        h2 { font-size: 16px; color: #146c43; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
        
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .data-row { margin-bottom: 6px; border-bottom: 1px dotted #eee; padding-bottom: 2px; }
        .data-label { font-weight: bold; color: #666; width: 140px; display: inline-block; }
        .data-value { font-weight: 600; color: #000; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th { background: #f3f3f3; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; font-weight: bold; color: #444; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        
        .status-badge { padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .st-pago { background: #d1e7dd; color: #0f5132; }
        .st-pend { background: #fff3cd; color: #856404; }
        .st-atra { background: #f8d7da; color: #842029; }
        
        .box-summary { background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 6px; margin-top: 20px; display: flex; justify-content: space-around; }
        .sum-item { text-align: center; }
        .sum-res { font-size: 16px; font-weight: bold; color: #146c43; display: block; margin-top: 5px; }
        
        .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 15px; text-align: center; font-size: 10px; color: #888; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <div>
            <h1>Resumo do Processo</h1>
            <div style="font-size: 14px; margin-top: 5px;">Relatório Técnico Administrativo</div>
        </div>
        <div class="header-info">
            <strong>Vilela Engenharia</strong><br>
            Eng. Diego Vilela (CREA-MG 235474/D)<br>
            Gerado em: <?= date('d/m/Y \à\s H:i') ?>
        </div>
    </div>

    <!-- 1. IDENTIFICAÇÃO -->
    <div class="two-col">
        <div>
            <h2>1. Identificação do Cliente</h2>
            <div class="data-row"><span class="data-label">Nome Completo:</span> <span class="data-value"><?= htmlspecialchars($cliente_dados['nome']) ?></span></div>
            <div class="data-row"><span class="data-label">CPF / CNPJ:</span> <span class="data-value"><?= $detalhes['cpf_cnpj']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">RG / IE:</span> <span class="data-value"><?= $detalhes['rg_ie']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Estado Civil:</span> <span class="data-value"><?= $detalhes['estado_civil']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Profissão:</span> <span class="data-value"><?= $detalhes['profissao']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Endereço Real:</span> <span class="data-value"><?= $detalhes['endereco_residencial']??'--' ?></span></div>
        </div>
        <div>
            <h2>2. Contato e Acesso</h2>
            <div class="data-row"><span class="data-label">Email:</span> <span class="data-value"><?= $detalhes['contato_email']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Telefone:</span> <span class="data-value"><?= $detalhes['contato_tel']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">ID Sistema:</span> <span class="data-value">#<?= $cliente_dados['id'] ?></span></div>
        </div>
    </div>

    <!-- 2. DADOS TÉCNICOS -->
    <div class="two-col" style="margin-top: 20px;">
        <div>
            <h2>3. Dados do Imóvel/Obra</h2>
            <div class="data-row"><span class="data-label">Endereço Obra:</span> <span class="data-value"><?= htmlspecialchars($endereco_final) ?></span></div>
            <div class="data-row"><span class="data-label">Matrícula:</span> <span class="data-value"><?= $detalhes['num_matricula']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Insc. Imob.:</span> <span class="data-value"><?= $detalhes['inscricao_imob']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Área do Lote (m²):</span> <span class="data-value"><?= $detalhes['imovel_area_lote']??($detalhes['area_terreno']??'--') ?></span></div>
            <div class="data-row"><span class="data-label">Área Construída:</span> <span class="data-value"><?= $detalhes['area_construida']??'--' ?></span></div>
        </div>
        <div>
            <h2>4. Responsabilidade Técnica</h2>
            <div class="data-row"><span class="data-label">Resp. Técnico:</span> <span class="data-value"><?= $detalhes['resp_tecnico']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Registro (CAU/CREA):</span> <span class="data-value"><?= $detalhes['registro_prof']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">ART / RRT:</span> <span class="data-value"><?= $detalhes['num_art_rrt']??'--' ?></span></div>
            <div class="data-row"><span class="data-label">Tipo Resp.:</span> <span class="data-value"><?= $detalhes['tipo_responsavel']??'--' ?></span></div>
        </div>
    </div>

    <!-- 3. STATUS ATUAL -->
    <h2>5. Status do Processo</h2>
    <div style="background: #e9ecef; padding: 15px; border-radius: 6px; font-size: 14px;">
        <strong>Fase Atual:</strong> <?= htmlspecialchars($detalhes['etapa_atual']??'Não iniciado') ?>
    </div>
    <?php if(!empty($detalhes['texto_pendencias'])): ?>
        <div style="margin-top:10px; border:1px solid #ffc107; background:#fffbf2; padding:10px; border-radius:4px;">
            <strong>⚠️ Pendências Ativas:</strong><br>
            <?= nl2br(htmlspecialchars($detalhes['texto_pendencias'])) ?>
        </div>
    <?php endif; ?>

    <!-- 4. FINANCEIRO -->
    <h2>6. Relatório Financeiro Detalhado</h2>
    <table>
        <thead>
            <tr>
                <th>Data Venc.</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Valor (R$)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($financeiro)==0): ?><tr><td colspan="5">Nenhum registro financeiro.</td></tr><?php endif; ?>
            <?php foreach($financeiro as $fin): 
                $cls = '';
                if($fin['status']=='pago')$cls='st-pago';
                elseif($fin['status']=='atrasado')$cls='st-atra';
                else $cls='st-pend';
            ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($fin['data_vencimento'])) ?></td>
                <td><?= ucfirst($fin['categoria']) ?></td>
                <td><?= $fin['descricao'] ?></td>
                <td>R$ <?= number_format($fin['valor'], 2, ',', '.') ?></td>
                <td><span class="status-badge <?= $cls ?>"><?= strtoupper($fin['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="box-summary">
        <div class="sum-item">Total Honorários<span class="sum-res">R$ <?= number_format($total_hon, 2, ',', '.') ?></span></div>
        <div class="sum-item">Total Taxas<span class="sum-res">R$ <?= number_format($total_taxas, 2, ',', '.') ?></span></div>
        <div class="sum-item">Total Pago<span class="sum-res">R$ <?= number_format($total_pago, 2, ',', '.') ?></span></div>
        <div class="sum-item">Pendente<span class="sum-res" style="color:#d32f2f;">R$ <?= number_format($total_pendente, 2, ',', '.') ?></span></div>
    </div>

    <!-- 5. HISTÓRICO -->
    <h2>7. Histórico Completo de Movimentações</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 120px;">Data/Hora</th>
                <th>Movimento / Fase</th>
                <th>Detalhes / Observações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($historico)==0): ?><tr><td colspan="3">Nenhum histórico registrado.</td></tr><?php endif; ?>
            <?php foreach($historico as $hist): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($hist['data_movimento'])) ?></td>
                <td><strong><?= $hist['titulo_fase'] ?></strong></td>
                <td><?= nl2br(str_replace('||COMENTARIO_USER||', '<br><strong>Obs:</strong> ', htmlspecialchars($hist['descricao']))) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Vilela Engenharia & Consultoria - Documento gerado automaticamente pelo Sistema de Gestão.<br>
        Este relatório reflete a posição do banco de dados na data e hora da emissão.
    </div>

</body>
</html>
