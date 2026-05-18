<?php
/**
 * View Admin: Dashboard Geral
 * Exibe KPIs e Lista de Clientes
 */

// Dados para Dashboard
try {
    // 1. Total Clientes
    $kpi_total_clientes = count($clientes);

    // 2. Pré-Cadastros Pendentes
    $stmt_pre = $pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'");
    $kpi_pre_pendentes = $stmt_pre ? $stmt_pre->fetchColumn() : 0;

    // 3. Financeiro
    // Atrasados
    $stmt_fin_atrasados = $pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status = 'atrasado'");
    $kpi_fin_atrasado = $stmt_fin_atrasados ? $stmt_fin_atrasados->fetchColumn() : 0;
    
    // Futuros/Pendentes
    $stmt_fin_pendentes = $pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status = 'pendente'");
    $kpi_fin_pendente = $stmt_fin_pendentes ? $stmt_fin_pendentes->fetchColumn() : 0;
    
    // 4. Processos Ativos (Não finalizados)
    $stmt_proc = $pdo->query("SELECT COUNT(*) FROM processo_detalhes WHERE etapa_atual != 'Processo Finalizado (Documentos Prontos)' AND etapa_atual IS NOT NULL AND etapa_atual != ''");
    $kpi_proc_ativos = $stmt_proc ? $stmt_proc->fetchColumn() : 0;

} catch (Exception $e) {
    // Silencia erro se tabelas não existirem ainda
    $kpi_total_clientes = 0; $kpi_pre_pendentes = 0; $kpi_fin_pendente = 0; $kpi_proc_ativos = 0; $kpi_fin_atrasado = 0;
}
?>
<!-- DASHBOARD GERAL (Visão do Gestor) -->
<div style="margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <h2 style="color:var(--color-primary); margin-bottom:5px;">Visão Geral do Escritório</h2>
        <p style="color:var(--color-text-subtle);">Resumo de atividades e indicadores de performance.</p>
    </div>
</div>

<!-- KPI Cards Compactos -->
<style>
    .kpi-grid-compact {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .kpi-card-compact {
        background: var(--color-surface); 
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        transition: transform 0.2s;
    }
    .kpi-card-compact:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .kpi-icon-box {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .kpi-content div:first-child { font-size: 1.4rem; font-weight: 800; line-height: 1; margin-bottom: 2px; }
    .kpi-content div:last-child { font-size: 0.85rem; color: var(--color-text-subtle); font-weight: 600; line-height: 1.2; }
</style>

<div class="kpi-grid-compact">
    <!-- 1. Clientes -->
    <div class="kpi-card-compact">
        <div class="kpi-icon-box" style="background:#e3f2fd; color:#2196f3;">👥</div>
        <div class="kpi-content">
            <div style="color:#2196f3;"><?= $kpi_total_clientes ?></div>
            <div>Clientes Ativos</div>
        </div>
    </div>

    <!-- 2. Obras -->
    <div class="kpi-card-compact">
        <div class="kpi-icon-box" style="background:#fff3cd; color:#ffc107;">🏗️</div>
        <div class="kpi-content">
            <div style="color:#ffc107;"><?= $kpi_proc_ativos ?></div>
            <div>Obras/Processos</div>
        </div>
    </div>

    <!-- 3. Solicitações -->
    <div class="kpi-card-compact" style="cursor: pointer;" onclick="if(<?= $kpi_pre_pendentes ?> > 0) window.location.href='?importar=true'">
        <div class="kpi-icon-box" style="background:#f8d7da; color:#dc3545;">📥</div>
        <div class="kpi-content">
            <div style="color:#dc3545;"><?= $kpi_pre_pendentes ?></div>
            <div>Novos Pedidos</div>
        </div>
    </div>

    <!-- 4. Recebíveis (Futuro) -->
    <div class="kpi-card-compact">
        <div class="kpi-icon-box" style="background:#d1e7dd; color:#198754;">💰</div>
        <div class="kpi-content">
            <div style="color:#198754; font-size:1.1rem;"><?= number_format($kpi_fin_pendente ?? 0, 2, ',', '.') ?></div>
            <div>A Receber (Futuro)</div>
        </div>
    </div>
    
    <!-- 5. Atrasados (Alerta) - Só aparece se tiver -->
    <?php if(($kpi_fin_atrasado ?? 0) > 0): ?>
    <div class="kpi-card-compact" style="border-color:#dc3545;">
        <div class="kpi-icon-box" style="background:#dc3545; color:white;">⚠️</div>
        <div class="kpi-content">
            <div style="color:#dc3545; font-size:1.1rem;"><?= number_format($kpi_fin_atrasado ?? 0, 2, ',', '.') ?></div>
            <div>EM ATRASO</div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Tabela Geral de Clientes -->
<div class="form-card">
    <h3>📋 Situação da Carteira de Clientes</h3>
    <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse; margin-top:15px;">
            <thead>
                <tr style="background:#f8f9fa; border-bottom:2px solid #ddd;">
                    <th style="padding:12px; text-align:left;">Cliente</th>
                    <th style="padding:12px; text-align:left;">Fase Atual</th>
                    <th style="padding:12px; text-align:left;">Contato</th>
                    <th style="padding:12px; text-align:center;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($clientes as $c): 
                    // Busca detalhes rápidos (poderia ser otimizado com JOIN, mas mantendo simples)
                    $dt = $pdo->query("SELECT etapa_atual, contato_tel FROM processo_detalhes WHERE cliente_id={$c['id']}")->fetch();
                    $etapa = $dt['etapa_atual'] ?? '<span style="color:#ccc; font-style:italic;">Não iniciado</span>';
                    $tel = $dt['contato_tel'] ?? '--';
                ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:12px; font-weight:bold; color:var(--color-primary);"><?= htmlspecialchars($c['nome']) ?></td>
                    <td style="padding:12px;"><?= $etapa ?></td>
                    <td style="padding:12px;"><?= $tel ?></td>
                    <td style="padding:12px; text-align:center;">
                        <a href="?cliente_id=<?= $c['id'] ?>" class="btn-save btn-info" style="padding:5px 10px; font-size:0.85rem; text-decoration:none;">Gerenciar ➡️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
