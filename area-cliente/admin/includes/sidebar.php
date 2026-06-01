<?php
/**
 * sidebar.php — Menu lateral de navegação administrativa.
 *
 * Contém o perfil do administrador, links dinâmicos para rotas,
 * contadores de notificações em tempo real e dados de rodapé.
 */

// Queries rápidas para carregar contadores de notificações na Sidebar
$count_pre_cadastros = 0;
$count_docs_analise = 0;
$count_pendencias = 0;

try {
    // Novos cadastros ou solicitações pendentes
    $count_pre_cadastros = (int) ($pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'")->fetchColumn() ?: 0);
    
    // Documentos enviados por clientes precisando de aprovação
    $count_docs_analise = (int) ($pdo->query("SELECT COUNT(*) FROM processo_docs_entregues WHERE status='em_analise'")->fetchColumn() ?: 0);
    
    // Total de pendências ativas em aberto de todos os clientes
    $count_pendencias = (int) ($pdo->query("SELECT COUNT(*) FROM processo_pendencias WHERE status='pendente'")->fetchColumn() ?: 0);
} catch (Exception $e) {
    // Ignora silenciosamente se tabelas não existirem
}
?>

<aside class="admin-nav-sidebar">
    <!-- Perfil do Administrador -->
    <div class="sidebar-profile">
        <!-- Avatar flutuante -->
        <div class="avatar" style="width: 46px; height: 46px; border-radius: 50%; background: var(--color-primary); color: #white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; border: 2px solid #fff; box-shadow: var(--shadow-xs); color: white;">
            DV
        </div>
        <div class="info">
            <span class="name">Diego Vilela</span>
            <span class="role">Engenheiro Civil</span>
        </div>
        <div class="actions">
            <a href="?route=configuracoes" class="icon-btn" title="Configurações">
                <span class="material-symbols-rounded">settings</span>
            </a>
        </div>
    </div>

    <!-- Navegação Scrollável -->
    <div class="nav-scroll">
        <!-- SEÇÃO: PRINCIPAL -->
        <div class="nav-section">
            <div class="nav-header">Gerenciamento</div>
            
            <a href="?route=dashboard" class="nav-item <?php echo $route === 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">dashboard</span>
                <span>Dashboard</span>
                <?php if ($count_docs_analise > 0): ?>
                    <span class="badge-count warn" title="Documentos aguardando análise"><?php echo $count_docs_analise; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="?route=clientes" class="nav-item <?php echo $route === 'clientes' || $route === 'cliente-detalhes' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">groups</span>
                <span>Clientes & Obras</span>
                <?php if ($count_pre_cadastros > 0): ?>
                    <span class="badge-count danger" title="Novas solicitações de cadastro"><?php echo $count_pre_cadastros; ?></span>
                <?php elseif ($count_pendencias > 0): ?>
                    <span class="badge-count" title="Pendências em aberto"><?php echo $count_pendencias; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- SEÇÃO: COMUNICAÇÃO & RELATÓRIOS -->
        <div class="nav-section">
            <div class="nav-header">Comunicação e Histórico</div>
            
            <a href="?route=avisos" class="nav-item <?php echo $route === 'avisos' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">campaign</span>
                <span>Avisos Gerais</span>
            </a>
            
            <a href="?route=auditoria" class="nav-item <?php echo $route === 'auditoria' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">history_toggle_off</span>
                <span>Logs de Auditoria</span>
            </a>
        </div>

        <!-- SEÇÃO: UTILITÁRIOS -->
        <div class="nav-section" style="margin-top: 15px;">
            <div class="nav-header">Sistema</div>
            
            <a href="?route=configuracoes" class="nav-item <?php echo $route === 'configuracoes' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">settings_applications</span>
                <span>Ajustes Gerais</span>
            </a>
            
            <a href="?sair=true" class="nav-item danger" style="color: var(--color-danger);" onclick="return confirm('Deseja realmente sair do painel administrativo?')">
                <span class="material-symbols-rounded" style="color: var(--color-danger);">logout</span>
                <span>Sair do Painel</span>
            </a>
        </div>
    </div>

    <!-- Rodapé Técnico -->
    <div class="sidebar-footer">
        <span class="label">Responsável Técnico</span>
        <span class="name">Diego T. N. Vilela</span>
        <span class="crea"><?php echo htmlspecialchars($company_crea); ?></span>
    </div>
</aside>
