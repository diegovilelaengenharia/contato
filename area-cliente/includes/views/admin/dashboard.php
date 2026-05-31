<?php
/**
 * View Admin: Visão Geral (Dashboard)
 * KPIs do escritório + carteira de clientes.
 */

// --- KPIs ---
try {
    $kpi_total_clientes = count($clientes);

    $kpi_pre_pendentes = (int) ($pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'")->fetchColumn() ?: 0);
    $kpi_fin_atrasado  = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='atrasado'")->fetchColumn() ?: 0);
    $kpi_fin_pendente  = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='pendente'")->fetchColumn() ?: 0);
    $kpi_proc_ativos   = (int) ($pdo->query("SELECT COUNT(*) FROM processo_detalhes
        WHERE etapa_atual != 'Processo Finalizado (Documentos Prontos)'
          AND etapa_atual IS NOT NULL AND etapa_atual != ''")->fetchColumn() ?: 0);
} catch (Exception $e) {
    $kpi_total_clientes = count($clientes);
    $kpi_pre_pendentes = $kpi_proc_ativos = 0;
    $kpi_fin_pendente = $kpi_fin_atrasado = 0;
}

// --- Carteira de clientes (1 query com JOIN, evita N+1) ---
try {
    $carteira = $pdo->query("SELECT c.id, c.nome, pd.etapa_atual, pd.contato_tel
        FROM clientes c
        LEFT JOIN processo_detalhes pd ON pd.cliente_id = c.id
        ORDER BY c.nome ASC")->fetchAll();
} catch (Exception $e) {
    $carteira = $clientes;
}
?>

<div class="page-head">
    <h1>Visão Geral do Escritório</h1>
    <p>Resumo de atividades e indicadores de performance.</p>
</div>

<!-- KPIs -->
<div class="kpi-grid-compact">
    <div class="kpi-card-compact">
        <div class="kpi-icon-box blue"><span class="material-symbols-rounded">groups</span></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $kpi_total_clientes ?></div>
            <div class="kpi-label">Clientes Ativos</div>
        </div>
    </div>

    <div class="kpi-card-compact">
        <div class="kpi-icon-box amber"><span class="material-symbols-rounded">engineering</span></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $kpi_proc_ativos ?></div>
            <div class="kpi-label">Obras / Processos</div>
        </div>
    </div>

    <div class="kpi-card-compact" <?= $kpi_pre_pendentes > 0 ? 'style="cursor:pointer" onclick="window.location.href=\'?importar=true\'"' : '' ?>>
        <div class="kpi-icon-box red"><span class="material-symbols-rounded">move_to_inbox</span></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $kpi_pre_pendentes ?></div>
            <div class="kpi-label">Novos Pedidos</div>
        </div>
    </div>

    <div class="kpi-card-compact">
        <div class="kpi-icon-box green"><span class="material-symbols-rounded">savings</span></div>
        <div class="kpi-content">
            <div class="kpi-value">R$ <?= number_format($kpi_fin_pendente, 2, ',', '.') ?></div>
            <div class="kpi-label">A Receber (Futuro)</div>
        </div>
    </div>

    <?php if ($kpi_fin_atrasado > 0): ?>
    <div class="kpi-card-compact alert">
        <div class="kpi-icon-box red"><span class="material-symbols-rounded">warning</span></div>
        <div class="kpi-content">
            <div class="kpi-value" style="color:var(--text-danger)">R$ <?= number_format($kpi_fin_atrasado, 2, ',', '.') ?></div>
            <div class="kpi-label">Em Atraso</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Carteira de clientes -->
<div class="form-card">
    <h3>Situação da Carteira de Clientes</h3>
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Fase Atual</th>
                    <th>Contato</th>
                    <th style="text-align:right;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carteira as $c): ?>
                <tr>
                    <td style="font-weight:700; color:var(--color-primary-dark);"><?= htmlspecialchars($c['nome']) ?></td>
                    <td>
                        <?php if (!empty($c['etapa_atual'])): ?>
                            <?= htmlspecialchars($c['etapa_atual']) ?>
                        <?php else: ?>
                            <span style="color:var(--color-muted); font-style:italic;">Não iniciado</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['contato_tel'] ?? '--') ?></td>
                    <td style="text-align:right;">
                        <a href="?cliente_id=<?= $c['id'] ?>" class="btn-save" style="padding:7px 14px; font-size:.85rem;">
                            Gerenciar <span class="material-symbols-rounded">arrow_forward</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($carteira)): ?>
                <tr><td colspan="4" style="text-align:center; color:var(--color-muted); padding:30px;">Nenhum cliente cadastrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
