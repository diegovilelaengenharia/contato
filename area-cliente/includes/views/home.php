<?php
// Fallbacks
$primeiro_nome = $primeiro_nome ?? 'Cliente';
$etapa_atual = $etapa_atual ?? 'Início';
$fases_total = count($fases_padrao);
$fase_atual_idx = $fase_index; // Vem do dashboard.php

// Pega iniciais
$iniciais = strtoupper(substr($primeiro_nome, 0, 1));

// Data Formatada
$data_inicio = isset($data['data_cadastro']) ? date('d/m/Y', strtotime($data['data_cadastro'])) : '--/--/----';
?>

<!-- HOME VIEW -->
<div class="fade-in-up">
    <!-- RICH PROPERTY CARD (Substitui Olá Cliente) -->
    <div class="property-card fade-in-up">
        <!-- Background Image Area -->
        <div class="pc-image" style="background-image: url('<?= !empty($data['foto_capa_obra']) ? htmlspecialchars($data['foto_capa_obra']) : '../assets/obra-placeholder.jpg' ?>');">
            <div class="pc-overlay"></div>
            <div class="pc-content-top">
                <span class="pc-status-badge">
                    <span class="material-symbols-rounded">engineering</span>
                    <?= $etapa_atual ?>
                </span>
                <span class="pc-id">Processo: <?= htmlspecialchars($data['processo_numero'] ?? '---') ?></span>
            </div>
        </div>

        <!-- Info Content -->
        <div class="pc-info">
            <h1 class="pc-title"><?= htmlspecialchars($data['processo_objeto'] ?? 'Regularização de Edificação') ?></h1>
            <p class="pc-address">
                <span class="material-symbols-rounded">location_on</span>
                <?= htmlspecialchars($endereco) ?>
            </p>

            <!-- Technical Grid -->
            <div class="pc-grid">
                <div class="pc-grid-item">
                    <label>Área Construída</label>
                    <strong><?= htmlspecialchars($data['area_total_final'] ?? '--') ?> m²</strong>
                </div>
                <div class="pc-grid-item">
                    <label>Matrícula (CRIME)</label>
                    <strong><?= htmlspecialchars($data['num_matricula'] ?? '--') ?></strong>
                </div>
                <div class="pc-grid-item">
                    <label>Inscrição Imobiliária</label>
                    <strong><?= htmlspecialchars($data['inscricao_imob'] ?? '--') ?></strong>
                </div>
                <div class="pc-grid-item highlight">
                    <label>Valor Venal Avaliado</label>
                    <strong>R$ <?= htmlspecialchars($data['valor_venal'] ?? '--') ?></strong>
                </div>
            </div>
        </div>
    </div>


    <!-- ASSISTANT TIP -->
    <div class="assistant-tip fade-in-up">
        <div class="at-icon">ℹ️</div>
        <div class="at-content">
            <strong>Informativo Técnico</strong>
            <p>Este painel apresenta o status do licenciamento urbanístico em tempo real. Acompanhe abaixo o trâmite processual e a emissão das peças técnicas.</p>
        </div>
    </div>

    <!-- Quick Stats Grid (Modified to show Deliverables) -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:25px;">
        <!-- Status Card -->
        <div class="stat-card" onclick="window.location.href='?view=timeline'" style="cursor:pointer; background:var(--color-card-bg);">
            <div class="stat-icon" style="background:var(--color-primary-light); color:var(--color-primary);">🚀</div>
            <div class="stat-info">
                <span class="stat-label">Fase Atual</span>
                <span class="stat-value" style="font-size:1rem; color:var(--color-primary);"><?= $etapa_atual ?: 'Início' ?></span>
            </div>
        </div>

        <!-- Deliverables Card -->
        <?php 
            // Count official docs
            $stmtDocs = $pdo->prepare("SELECT COUNT(*) FROM processo_movimentos WHERE cliente_id = ? AND tipo_movimento = 'documento'");
            $stmtDocs->execute([$cliente_id]);
            $countDocs = $stmtDocs->fetchColumn();
        ?>
        <div class="stat-card" onclick="window.location.href='?view=timeline'" style="cursor:pointer; background:var(--color-card-bg);">
            <div class="stat-icon" style="background:#e8f5e9; color:#198754;">📜</div>
            <div class="stat-info">
                <span class="stat-label">Documentos</span>
                <span class="stat-value" style="color:#198754;"><?= $countDocs ?> emitidos</span>
            </div>
        </div>
    </div>


<!-- STEP PROCESS (MODERN) -->
<h3 style="margin-bottom:15px; padding-left:5px;">Status do Projeto</h3>

<div class="stepper-scroll-container fade-in-up" id="stepperContainer">
    <div class="stepper-track">
        <?php foreach($fases_padrao as $idx => $nome_fase): 
            $status_class = ''; // default future
            $icon_content = $idx + 1;
            
            if($idx < $fase_atual_idx) {
                $status_class = 'completed';
                $icon_content = '✓';
            } elseif($idx == $fase_atual_idx) {
                $status_class = 'active';
            }
        ?>
        <div class="step-item <?= $status_class ?>" id="step-<?= $idx ?>">
            <div class="step-line"></div>
            <div class="step-circle"><?= $icon_content ?></div>
            <div class="step-label"><?= $nome_fase ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- DETAILED PROCESS SUMMARY -->
<div class="process-summary fade-in-up" style="text-align: left;">
    <span class="summary-highlight" style="text-align:center; margin-bottom:15px;">Fase Atual: <?= $etapa_atual ?></span>
    
    <div class="data-grid-summary" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px;">
        <div class="dgs-item" style="background:var(--bg-app); padding:10px; border-radius:8px;">
            <label style="font-size:0.7rem; color:var(--text-muted); display:block;">Início do Processo</label>
            <strong style="font-size:0.95rem;"><?= $data_inicio ?></strong>
        </div>
        <div class="dgs-item" style="background:var(--bg-app); padding:10px; border-radius:8px;">
            <label style="font-size:0.7rem; color:var(--text-muted); display:block;">Documentos</label>
            <strong style="font-size:0.95rem;"><?= $total_docs ?? 0 ?> Anexados</strong>
        </div>
    </div>

    <p class="summary-desc" style="text-align:justify;">
        Nesta etapa, nossa equipe técnica está focada em <strong><?= strtolower($etapa_atual) ?></strong>. 
        Mantenha-se atento às notificações para qualquer necessidade de documento adicional. 
        O progresso é atualizado automaticamente conforme as aprovações ocorrem.
    </p>
</div>

<script>
// Auto-scroll to active step
document.addEventListener('DOMContentLoaded', () => {
    const activeStep = document.querySelector('.step-item.active');
    if(activeStep) {
        activeStep.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
});
</script>
