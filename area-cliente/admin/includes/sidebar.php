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

    <?php
    // Carregar clientes de forma leve para a busca global autocomplete
    $todos_clientes_busca = [];
    try {
        $todos_clientes_busca = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Silencia erro
    }
    ?>

    <!-- BUSCA GLOBAL DE CLIENTES (FEAT-03) -->
    <div class="sidebar-search" x-data="globalSearch(<?php echo htmlspecialchars(json_encode($todos_clientes_busca), ENT_QUOTES, 'UTF-8'); ?>)" style="padding: 10px 15px; position: relative;">
        <div style="position: relative;">
            <input type="text" x-model="query" @input="search()" @focus="open = true" @click.away="open = false" placeholder="🔍 Buscar cliente..." style="padding: 8px 12px 8px 32px; width: 100%; border: 1px solid var(--color-border); border-radius: 8px; font-size: 0.85rem; background: white; color: var(--color-text); outline: none;">
            <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 1rem; color: var(--color-muted); pointer-events: none;">search</span>
        </div>
        
        <!-- Dropdown Autocomplete -->
        <div class="search-autocomplete-dropdown" x-show="open && results.length > 0" style="position: absolute; left: 15px; right: 15px; top: 100%; background: white; border: 1px solid var(--color-border); border-radius: 8px; box-shadow: var(--shadow-lg); z-index: 999; max-height: 200px; overflow-y: auto; margin-top: 5px; border-top: none;">
            <template x-for="c in results" :key="c.id">
                <a :href="'?route=cliente-detalhes&id=' + c.id" style="display: block; padding: 8px 12px; text-decoration: none; color: var(--color-text); font-size: 0.82rem; font-weight: 600; border-bottom: 1px solid #f1f3f5; transition: background 0.15s;" @mouseenter="$el.style.background = '#f8fafc'" @mouseleave="$el.style.background = 'none'">
                    <span x-text="c.nome"></span>
                </a>
            </template>
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
    
    <script>
    function globalSearch(clientes) {
        return {
            clientes: clientes || [],
            query: '',
            results: [],
            open: false,
            
            search() {
                if (this.query.trim() === '') {
                    this.results = [];
                    return;
                }
                const q = this.query.toLowerCase();
                this.results = this.clientes.filter(c => c.nome.toLowerCase().includes(q)).slice(0, 5);
            }
        }
    }
    </script>
</aside>
