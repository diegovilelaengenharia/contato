<?php
/**
 * dashboard.php — View Administrativa Geral (Dashboard).
 *
 * Exibe KPIs de faturamento anual, andamentos de processos,
 * alertas operacionais de documentos para aprovação e pendências críticas.
 */

// --- QUERIES DE KPIs ---
$kpi_total_clientes = 0;
$kpi_obras_ativas = 0;
$kpi_pre_cadastros = 0;
$kpi_fin_pago = 0.0;
$kpi_fin_pendente = 0.0;
$kpi_fin_atrasado = 0.0;

try {
    // Clientes totais
    $kpi_total_clientes = (int) ($pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn() ?: 0);
    
    // Obras / Processos em andamento (etapa não finalizada)
    $kpi_obras_ativas = (int) ($pdo->query("SELECT COUNT(*) FROM processo_detalhes WHERE etapa_atual != 'Processo Finalizado (Documentos Prontos)' AND etapa_atual IS NOT NULL AND etapa_atual != ''")->fetchColumn() ?: 0);
    
    // Pré-cadastros de novos clientes pendentes de aprovação
    $kpi_pre_cadastros = (int) ($pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'")->fetchColumn() ?: 0);
    
    // Financeiro: Faturamento Pago no ano corrente
    $kpi_fin_pago = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='pago' AND YEAR(vencimento) = YEAR(CURDATE())")->fetchColumn() ?: 0.0);
    
    // Financeiro: Total a receber (pendente)
    $kpi_fin_pendente = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='pendente'")->fetchColumn() ?: 0.0);
    
    // Financeiro: Total em atraso
    $kpi_fin_atrasado = (float) ($pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status='atrasado'")->fetchColumn() ?: 0.0);

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs do Dashboard: " . $e->getMessage());
}

// --- QUERIES DE ALERTAS RÁPIDOS ---
$alertas_docs = [];
$alertas_pendencias = [];
$pre_cadastros_lista = [];

try {
    // Documentos aguardando análise
    $alertas_docs = $pdo->query("
        SELECT doc.id, doc.cliente_id, doc.doc_chave, doc.data_entrega, c.nome as cliente_nome 
        FROM processo_docs_entregues doc
        INNER JOIN clientes c ON c.id = doc.cliente_id
        WHERE doc.status = 'em_analise'
        ORDER BY doc.data_entrega ASC 
        LIMIT 5
    ")->fetchAll();
    
    // Pendências abertas mais antigas
    $alertas_pendencias = $pdo->query("
        SELECT p.id, p.cliente_id, p.descricao, p.data_criacao, c.nome as cliente_nome 
        FROM processo_pendencias p
        INNER JOIN clientes c ON c.id = p.cliente_id
        WHERE p.status = 'pendente'
        ORDER BY p.data_criacao ASC 
        LIMIT 5
    ")->fetchAll();

    // Pré-cadastros pendentes de aprovação
    $pre_cadastros_lista = $pdo->query("
        SELECT id, nome, email, telefone, data_solicitacao 
        FROM pre_cadastros 
        WHERE status='pendente' 
        ORDER BY data_solicitacao ASC 
        LIMIT 5
    ")->fetchAll();

} catch (Exception $e) {
    error_log("Erro ao carregar Alertas do Dashboard: " . $e->getMessage());
}

// --- CARTEIRA DE CLIENTES COMPLETA ---
$carteira = [];
try {
    $carteira = $pdo->query("
        SELECT c.id, c.nome, pd.etapa_atual, pd.contato_tel 
        FROM clientes c
        LEFT JOIN processo_detalhes pd ON pd.cliente_id = c.id
        ORDER BY c.nome ASC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Erro ao carregar carteira de clientes: " . $e->getMessage());
}
?>

<div class="page-head">
    <h1>Painel de Controle</h1>
    <p>Bem-vindo ao centro de operações da Vilela Engenharia.</p>
</div>

<!-- GRID DE INDICADORES FINANCEIROS E OPERACIONAIS (KPIs) -->
<div class="kpi-grid-compact">
    <!-- Card 1: Clientes -->
    <div class="kpi-card-compact" style="cursor: pointer;" onclick="window.location.href='?route=clientes'">
        <div class="kpi-icon-box blue">
            <span class="material-symbols-rounded">groups</span>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?php echo $kpi_total_clientes; ?></div>
            <div class="kpi-label">Clientes Ativos</div>
        </div>
    </div>

    <!-- Card 2: Obras -->
    <div class="kpi-card-compact" style="cursor: pointer;" onclick="window.location.href='?route=clientes'">
        <div class="kpi-icon-box amber">
            <span class="material-symbols-rounded">engineering</span>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?php echo $kpi_obras_ativas; ?></div>
            <div class="kpi-label">Processos / Obras</div>
        </div>
    </div>

    <!-- Card 3: Recebido Pago -->
    <div class="kpi-card-compact">
        <div class="kpi-icon-box green">
            <span class="material-symbols-rounded">payments</span>
        </div>
        <div class="kpi-content">
            <div class="kpi-value">R$ <?php echo number_format($kpi_fin_pago, 2, ',', '.'); ?></div>
            <div class="kpi-label">Faturado (<?php echo date('Y'); ?>)</div>
        </div>
    </div>

    <!-- Card 4: A Receber -->
    <div class="kpi-card-compact">
        <div class="kpi-icon-box blue" style="background: var(--bg-info); color: var(--text-info);">
            <span class="material-symbols-rounded">savings</span>
        </div>
        <div class="kpi-content">
            <div class="kpi-value">R$ <?php echo number_format($kpi_fin_pendente, 2, ',', '.'); ?></div>
            <div class="kpi-label">A Receber Futuro</div>
        </div>
    </div>

    <!-- Card 5: Em Atraso (Opcional - Só exibe se houver saldo devedor) -->
    <?php if ($kpi_fin_atrasado > 0): ?>
    <div class="kpi-card-compact alert" style="background: rgba(220, 53, 69, 0.05); border-color: rgba(220, 53, 69, 0.3);">
        <div class="kpi-icon-box red">
            <span class="material-symbols-rounded">warning_amber</span>
        </div>
        <div class="kpi-content">
            <div class="kpi-value" style="color: var(--color-danger);">R$ <?php echo number_format($kpi_fin_atrasado, 2, ',', '.'); ?></div>
            <div class="kpi-label" style="font-weight: 700;">Valores em Atraso</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- SEÇÃO DE ALERTAS DE OPERAÇÃO RÁPIDA -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 26px;">
    
    <!-- Alerta A: Novos Pedidos de Pré-Cadastro -->
    <?php if ($kpi_pre_cadastros > 0 || !empty($pre_cadastros_lista)): ?>
    <div class="form-card" style="border-left: 5px solid var(--color-danger);">
        <div class="config-title" style="border: none; padding: 0; margin-bottom: 12px; color: var(--color-danger); font-size: 1.1rem;">
            <span class="material-symbols-rounded">person_add</span> Novos Pedidos de Acesso
        </div>
        <p style="font-size: 0.85rem; color: var(--color-text-subtle); margin-bottom: 12px;">Clientes se cadastraram pelo portal e aguardam aprovação de conta.</p>
        
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table" style="font-size: 0.85rem;">
                <tbody>
                    <?php foreach ($pre_cadastros_lista as $pre): ?>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 700;"><?php echo htmlspecialchars($pre['nome']); ?></td>
                        <td style="padding: 10px 0; color: var(--color-text-subtle);"><?php echo htmlspecialchars($pre['telefone']); ?></td>
                        <td style="padding: 10px 0; text-align: right;">
                            <form action="../actions/admin/cliente_approve_pre.php" method="POST" style="display: inline;">
                                <?php echo Csrf::getHtmlField(); ?>
                                <input type="hidden" name="pre_id" value="<?php echo $pre['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-icon" style="width: 28px; height: 28px; background: var(--color-primary-soft); color: var(--color-primary-dark); border: none;" title="Aprovar e Criar Conta">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">check</span>
                                </button>
                            </form>
                            <form action="../actions/admin/cliente_approve_pre.php" method="POST" style="display: inline; margin-left: 4px;">
                                <?php echo Csrf::getHtmlField(); ?>
                                <input type="hidden" name="pre_id" value="<?php echo $pre['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-icon danger" style="width: 28px; height: 28px; border: none;" title="Recusar Cadastro" onclick="return confirm('Deseja realmente excluir este pedido de pré-cadastro?')">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">close</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alerta B: Documentos em Análise (Cliente Enviou) -->
    <div class="form-card" style="border-left: 5px solid var(--color-primary);">
        <div class="config-title" style="border: none; padding: 0; margin-bottom: 12px; color: var(--color-primary-dark); font-size: 1.1rem;">
            <span class="material-symbols-rounded">cloud_upload</span> Documentos para Aprovar (<?php echo count($alertas_docs); ?>)
        </div>
        <p style="font-size: 0.85rem; color: var(--color-text-subtle); margin-bottom: 12px;">Clientes enviaram documentos que precisam de aprovação técnica.</p>
        
        <?php if (!empty($alertas_docs)): ?>
            <div class="admin-table-container" style="border: none; border-radius: 0;">
                <table class="admin-table" style="font-size: 0.85rem;">
                    <tbody>
                        <?php foreach ($alertas_docs as $doc): ?>
                        <tr>
                            <td style="padding: 10px 0; font-weight: 700; color: var(--color-primary-dark);"><?php echo htmlspecialchars($doc['cliente_nome']); ?></td>
                            <td style="padding: 10px 0; font-weight: 500;"><?php echo htmlspecialchars($doc['doc_chave']); ?></td>
                            <td style="padding: 10px 0; text-align: right;">
                                <a href="?route=cliente-detalhes&id=<?php echo $doc['cliente_id']; ?>&tab=docs" class="btn-act" style="width: 28px; height: 28px;" title="Verificar Documento">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">visibility</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="font-style: italic; color: var(--color-muted); font-size: 0.85rem; padding: 10px 0;">🎉 Nenhum documento pendente de aprovação!</p>
        <?php endif; ?>
    </div>

    <!-- Alerta C: Pendências Críticas -->
    <div class="form-card" style="border-left: 5px solid #dc7a0d;">
        <div class="config-title" style="border: none; padding: 0; margin-bottom: 12px; color: #dc7a0d; font-size: 1.1rem;">
            <span class="material-symbols-rounded">warning</span> Pendências Ativas (<?php echo count($alertas_pendencias); ?>)
        </div>
        <p style="font-size: 0.85rem; color: var(--color-text-subtle); margin-bottom: 12px;">Ações exigidas dos clientes que estão travando a obra.</p>
        
        <?php if (!empty($alertas_pendencias)): ?>
            <div class="admin-table-container" style="border: none; border-radius: 0;">
                <table class="admin-table" style="font-size: 0.85rem;">
                    <tbody>
                        <?php foreach ($alertas_pendencias as $p): ?>
                        <tr>
                            <td style="padding: 10px 0; font-weight: 700; color: var(--color-text);"><?php echo htmlspecialchars($p['cliente_nome']); ?></td>
                            <td style="padding: 10px 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; max-width: 150px;" title="<?php echo htmlspecialchars($p['descricao']); ?>">
                                <?php echo htmlspecialchars($p['descricao']); ?>
                            </td>
                            <td style="padding: 10px 0; text-align: right;">
                                <a href="?route=cliente-detalhes&id=<?php echo $p['cliente_id']; ?>&tab=pendencias" class="btn-act" style="width: 28px; height: 28px;" title="Ver Pendência">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">arrow_forward</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="font-style: italic; color: var(--color-muted); font-size: 0.85rem; padding: 10px 0;">✅ Nenhuma pendência em aberto cadastrada!</p>
        <?php endif; ?>
    </div>
</div>

<!-- CARTEIRA COMPLETA DE CLIENTES -->
<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="margin: 0; border: none; padding: 0;">Carteira de Clientes Ativos</h3>
            <p style="color: var(--color-text-subtle); font-size: 0.85rem; margin: 4px 0 0 0;">Situação e andamento atualizado de todos os clientes.</p>
        </div>
        <div style="position: relative; width: 100%; max-width: 300px;">
            <input type="text" id="searchClientInput" placeholder="🔍 Buscar cliente..." style="padding: 8px 12px 8px 34px; width: 100%; border: 1px solid var(--color-border); border-radius: 8px; font-size: 0.9rem;" onkeyup="filterClientTable()">
            <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; color: var(--color-muted);">search</span>
        </div>
    </div>

    <div class="admin-table-container">
        <table class="admin-table" id="clientsTable">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Etapa Atual</th>
                    <th>Telefone de Contato</th>
                    <th style="text-align: right;">Operação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carteira as $c): ?>
                <tr class="client-row">
                    <td class="client-name" style="font-weight: 700; color: var(--color-primary-dark);"><?php echo htmlspecialchars($c['nome']); ?></td>
                    <td>
                        <?php if (!empty($c['etapa_atual'])): ?>
                            <span class="status-badge info" style="font-size: 0.72rem; padding: 4px 10px;"><?php echo htmlspecialchars($c['etapa_atual']); ?></span>
                        <?php else: ?>
                            <span style="color: var(--color-muted); font-style: italic;">Não iniciado</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($c['contato_tel'] ?: 'Não informado'); ?></td>
                    <td style="text-align: right;">
                        <a href="?route=cliente-detalhes&id=<?php echo $c['id']; ?>" class="btn-save" style="padding: 7px 14px; font-size: 0.82rem; background: var(--color-primary); color: white; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; text-decoration: none;">
                            Gerenciar <span class="material-symbols-rounded" style="font-size: 1rem;">arrow_forward</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($carteira)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--color-muted); padding: 40px;">Nenhum cliente cadastrado no sistema ainda.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script de Filtro Local do Dashboard -->
<script>
function filterClientTable() {
    const input = document.getElementById("searchClientInput");
    const filter = input.value.toUpperCase();
    const rows = document.querySelectorAll(".client-row");
    
    rows.forEach(row => {
        const nameCell = row.querySelector(".client-name");
        if (nameCell) {
            const textValue = nameCell.textContent || nameCell.innerText;
            if (textValue.toUpperCase().indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
}
</script>
