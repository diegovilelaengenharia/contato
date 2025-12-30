<div class="view-header">
    <div class="user-welcome">
        <small>Bem-vindo(a),</small>
        <h1><?= htmlspecialchars($primeiro_nome) ?></h1>
    </div>
    <div class="user-avatar" onclick="window.location.href='?view=perfil'">
        <?= strtoupper(substr($primeiro_nome, 0, 1)) ?>
    </div>
</div>

<!-- STORY CARD: FASE ATUAL -->
<div class="story-card fade-in-up">
    <div class="story-header">
        <span class="story-label">Fase Atual do Processo</span>
        <span class="story-date"><?= date('d/m', strtotime($detalhes['data_inicio'] ?? 'now')) ?></span>
    </div>
    <h2 class="story-title"><?= $detalhes['etapa_atual'] ?? 'Análise Inicial' ?></h2>
    <div class="progress-bar">
        <div class="progress-fill" style="width: 25%;"></div> 
        <!-- TODO: Calcular porcentagem real baseada na fase -->
    </div>
    <p class="story-desc">
        Estamos cuidando de tudo! Acompanhe o progresso detalhado na aba <strong>Timeline</strong>.
    </p>
</div>

<!-- ALERTS / PENDÊNCIAS URGENTES -->
<?php 
$pendencias_abertas = array_filter($pendencias, fn($p) => $p['status'] != 'resolvido');
$count_pend = count($pendencias_abertas);
if($count_pend > 0): 
?>
<div class="alert-box fade-in-up" onclick="window.location.href='?view=pendencias'">
    <div class="alert-icon">⚠️</div>
    <div class="alert-content">
        <strong>Atenção Necessária</strong>
        <p>Você tem <?= $count_pend ?> pendência(s) para resolver.</p>
    </div>
    <div class="alert-arrow">→</div>
</div>
<?php endif; ?>

<!-- QUICK ACTIONS -->
<h3 class="section-title">Acesso Rápido</h3>
<div class="grid-actions fade-in-up" style="animation-delay: 0.1s;">
    <div class="action-card" onclick="window.location.href='?view=financeiro'">
        <div class="action-icon">💸</div>
        <span>Financeiro</span>
    </div>
    <div class="action-card" onclick="window.location.href='?view=arquivos'">
        <div class="action-icon">📂</div>
        <span>Projetos</span>
    </div>
    <div class="action-card" onclick="window.location.href='?view=pendencias'">
        <div class="action-icon">📝</div>
        <span>Tarefas</span>
    </div>
    <a href="https://wa.me/5535984529577" target="_blank" class="action-card">
        <div class="action-icon">💬</div>
        <span>Suporte</span>
    </a>
</div>
