<?php
/**
 * clientes.php — View Administrativa de Listagem de Clientes.
 *
 * Exibe a carteira completa com busca instantânea local, filtros avançados de
 * andamento, pendências e situação financeira, e atalhos de gerenciamento.
 */

$clientes_lista = [];

try {
    // Query de alta performance com subconsultas agregadas para contadores (evita N+1 queries)
    $clientes_lista = $pdo->query("
        SELECT c.id, c.nome, pd.etapa_atual, pd.contato_tel, pd.tipo_servico,
               (SELECT COUNT(*) FROM processo_pendencias WHERE cliente_id = c.id AND status = 'pendente') as total_pendencias,
               (SELECT COUNT(*) FROM processo_docs_entregues WHERE cliente_id = c.id AND status = 'em_analise') as total_docs_analise,
               (SELECT COUNT(*) FROM processo_financeiro WHERE cliente_id = c.id AND status = 'atrasado') as total_atrasados,
               (SELECT COUNT(*) FROM processo_financeiro WHERE cliente_id = c.id AND status = 'pendente') as total_pendentes
        FROM clientes c
        LEFT JOIN processo_detalhes pd ON pd.cliente_id = c.id
        ORDER BY c.nome ASC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Erro ao carregar listagem de clientes: " . $e->getMessage());
}
?>

<div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
    <div>
        <h1>Clientes e Obras</h1>
        <p>Gerencie sua carteira de clientes, andamento de processos e cobranças.</p>
    </div>
    <!-- Botão de Criação de Novo Cliente -->
    <a href="?route=cliente-detalhes&action=new" class="btn-std btn-primary" style="padding: 12px 20px; font-weight: 700; border-radius: var(--radius-sm); border: none; box-shadow: var(--shadow-sm); font-size: 0.9rem;">
        <span class="material-symbols-rounded">person_add</span> Cadastrar Novo Cliente
    </a>
</div>

<!-- BARRA DE FILTROS E BUSCA -->
<div class="form-card" style="padding: 18px 24px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        
        <!-- Pílulas de Filtros Rápidos -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap;" id="filterContainer">
            <button class="nav-pill active" onclick="applyQuickFilter('todos', this)" style="padding: 8px 16px; font-size: 0.85rem;">
                Todos <span style="font-weight: 400; opacity: 0.8;">(<?php echo count($clientes_lista); ?>)</span>
            </button>
            <button class="nav-pill" onclick="applyQuickFilter('ativos', this)" style="padding: 8px 16px; font-size: 0.85rem;">
                Processo Ativo
            </button>
            <button class="nav-pill" onclick="applyQuickFilter('pendentes', this)" style="padding: 8px 16px; font-size: 0.85rem;">
                Com Pendências
            </button>
            <button class="nav-pill" onclick="applyQuickFilter('atrasados', this)" style="padding: 8px 16px; font-size: 0.85rem;">
                Financeiro Atrasado
            </button>
        </div>
        
        <!-- Campo de Pesquisa em Tempo Real -->
        <div style="position: relative; width: 100%; max-width: 320px;">
            <input type="text" id="searchClientPage" placeholder="Pesquise por nome, etapa ou telefone..." 
                   style="padding: 10px 14px 10px 38px; width: 100%; border: 1px solid var(--color-border); border-radius: 10px; font-size: 0.9rem;" 
                   onkeyup="searchClients()">
            <span class="material-symbols-rounded" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: var(--color-muted);">search</span>
        </div>
        
    </div>
</div>

<!-- LISTAGEM DE CLIENTES -->
<div class="form-card" style="padding: 0; overflow: hidden;">
    <div class="admin-table-container" style="border: none; border-radius: 0;">
        <table class="admin-table" id="clientsMasterTable">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Serviço / Etapa da Obra</th>
                    <th>Telefone</th>
                    <th>Pendências</th>
                    <th>Financeiro</th>
                    <th style="text-align: right; padding-right: 24px;">Operações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes_lista as $cli): 
                    // Determinar classes CSS de filtros baseadas nos dados
                    $is_ativo = ($cli['etapa_atual'] !== 'Processo Finalizado (Documentos Prontos)' && !empty($cli['etapa_atual'])) ? '1' : '0';
                    $has_pendencias = ($cli['total_pendencias'] > 0 || $cli['total_docs_analise'] > 0) ? '1' : '0';
                    $has_atrasos = ($cli['total_atrasados'] > 0) ? '1' : '0';
                    
                    // Status financeiro do cliente
                    $fin_status = 'isento';
                    $fin_label = 'Isento';
                    $fin_class = 'info';
                    
                    if ($cli['total_atrasados'] > 0) {
                        $fin_status = 'atrasado';
                        $fin_label = 'Atrasado';
                        $fin_class = 'danger';
                    } elseif ($cli['total_pendentes'] > 0) {
                        $fin_status = 'pendente';
                        $fin_label = 'Pendente';
                        $fin_class = 'warning';
                    } else {
                        // Sem atrasados e sem pendentes, se houver financeiro cadastrado, indica pago
                        $fin_status = 'pago';
                        $fin_label = 'Em dia';
                        $fin_class = 'success';
                    }
                ?>
                <tr class="client-master-row" 
                    data-ativo="<?php echo $is_ativo; ?>" 
                    data-pendente="<?php echo $has_pendencias; ?>" 
                    data-atrasado="<?php echo $has_atrasos; ?>">
                    
                    <td class="cli-name" style="padding: 16px; font-weight: 700; color: var(--color-primary-dark); font-size: 0.95rem;">
                        <?php echo htmlspecialchars($cli['nome']); ?>
                    </td>
                    
                    <td class="cli-stage" style="padding: 16px;">
                        <?php if (!empty($cli['etapa_atual'])): ?>
                            <span class="status-badge info" style="font-size: 0.72rem; padding: 4px 10px;" title="Serviço: <?php echo htmlspecialchars($cli['tipo_servico'] ?? 'Regularização'); ?>">
                                <?php echo htmlspecialchars($cli['etapa_atual']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--color-muted); font-style: italic; font-size: 0.85rem;">Não iniciado</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="cli-tel" style="padding: 16px; font-size: 0.88rem; color: var(--color-text-subtle);">
                        <?php echo htmlspecialchars($cli['contato_tel'] ?: '--'); ?>
                    </td>
                    
                    <td style="padding: 16px;">
                        <?php if ($cli['total_pendencias'] > 0): ?>
                            <span class="status-badge warning" style="font-size: 0.72rem; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="material-symbols-rounded" style="font-size: 0.85rem;">warning</span>
                                <?php echo $cli['total_pendencias']; ?> abertas
                            </span>
                        <?php endif; ?>
                        <?php if ($cli['total_docs_analise'] > 0): ?>
                            <span class="status-badge info" style="font-size: 0.72rem; padding: 3px 8px; margin-left: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="material-symbols-rounded" style="font-size: 0.85rem;">cloud_upload</span>
                                <?php echo $cli['total_docs_analise']; ?> para rever
                            </span>
                        <?php endif; ?>
                        <?php if ($cli['total_pendencias'] == 0 && $cli['total_docs_analise'] == 0): ?>
                            <span style="color: var(--color-primary); font-size: 0.85rem; font-weight: 600;">Sem pendências</span>
                        <?php endif; ?>
                    </td>
                    
                    <td style="padding: 16px;">
                        <span class="status-badge <?php echo $fin_class; ?>" style="font-size: 0.72rem; padding: 4px 10px;">
                            <?php echo $fin_label; ?>
                        </span>
                    </td>
                    
                    <td style="padding: 16px; text-align: right; padding-right: 24px;">
                        <a href="?route=cliente-detalhes&id=<?php echo $cli['id']; ?>" class="btn-save" 
                           style="padding: 8px 16px; font-size: 0.82rem; background: var(--color-primary); color: white; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            Gerenciar <span class="material-symbols-rounded" style="font-size: 1rem;">arrow_forward</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clientes_lista)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--color-muted); padding: 50px;">
                        <span class="material-symbols-rounded" style="font-size: 3rem; display: block; margin-bottom: 10px; color: var(--color-muted);">search_off</span>
                        Nenhum cliente cadastrado no momento.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts Interativos de Pesquisa e Filtros -->
<script>
let currentActiveFilter = 'todos';

function searchClients() {
    const input = document.getElementById("searchClientPage");
    const filter = input.value.toUpperCase();
    const rows = document.querySelectorAll(".client-master-row");
    
    rows.forEach(row => {
        // Verifica se a linha passa pelo filtro de pesquisa
        const nameText = row.querySelector(".cli-name").textContent || row.querySelector(".cli-name").innerText;
        const stageText = row.querySelector(".cli-stage").textContent || row.querySelector(".cli-stage").innerText;
        const telText = row.querySelector(".cli-tel").textContent || row.querySelector(".cli-tel").innerText;
        
        const matchesSearch = nameText.toUpperCase().indexOf(filter) > -1 || 
                             stageText.toUpperCase().indexOf(filter) > -1 || 
                             telText.toUpperCase().indexOf(filter) > -1;
                             
        // Verifica se a linha também satisfaz o filtro de pílula atual
        let matchesPill = true;
        if (currentActiveFilter === 'ativos') {
            matchesPill = row.getAttribute('data-ativo') === '1';
        } else if (currentActiveFilter === 'pendentes') {
            matchesPill = row.getAttribute('data-pendente') === '1';
        } else if (currentActiveFilter === 'atrasados') {
            matchesPill = row.getAttribute('data-atrasado') === '1';
        }
        
        if (matchesSearch && matchesPill) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

function applyQuickFilter(filterType, element) {
    // Altera a classe visual da pílula ativa
    const pills = document.querySelectorAll("#filterContainer .nav-pill");
    pills.forEach(p => p.classList.remove('active'));
    element.classList.add('active');
    
    currentActiveFilter = filterType;
    
    // Executa a pesquisa (que leva em conta o filtro atual)
    searchClients();
}
</script>
