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
    <!-- Process Header: RESUMO DO PATRIMÔNIO -->
    <div style="background:var(--color-primary-dark); color:white; padding:25px 20px; border-radius:16px; margin-bottom:25px; box-shadow:var(--shadow-medium); position:relative; overflow:hidden;">
        <div style="position:absolute; top:-20px; right:-20px; background:rgba(255,255,255,0.1); width:150px; height:150px; border-radius:50%;"></div>
        
        <div style="position:relative; z-index:2;">
            <p style="opacity:0.9; margin:0 0 5px 0; font-weight:400; font-size:0.9rem;">Olá, <?= $primeiro_nome ?>.</p>
            <h1 style="font-size:1.6rem; margin:0 0 20px 0; font-weight:800; letter-spacing:-0.5px;">Resumo do Patrimônio</h1>
            
            <?php if(!empty($data['processo_numero'])): ?>
            <div style="background:rgba(0,0,0,0.25); border-radius:12px; overflow:hidden;">
                <!-- Table-like layout using CSS Grid -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; border-bottom:1px solid rgba(255,255,255,0.1);">
                    <div style="padding:15px; border-right:1px solid rgba(255,255,255,0.1);">
                        <label style="display:block; font-size:0.7rem; text-transform:uppercase; opacity:0.75; margin-bottom:3px;">Status</label>
                        <strong style="font-size:0.95rem; color:#4caf50;">✅ <?= $etapa_atual ?></strong>
                    </div>
                    <div style="padding:15px;">
                        <label style="display:block; font-size:0.7rem; text-transform:uppercase; opacity:0.75; margin-bottom:3px;">Processo</label>
                        <strong style="font-size:0.95rem;"><?= htmlspecialchars($data['processo_numero']) ?></strong>
                    </div>
                </div>

                <div style="padding:15px; border-bottom:1px solid rgba(255,255,255,0.1);">
                    <label style="display:block; font-size:0.7rem; text-transform:uppercase; opacity:0.75; margin-bottom:3px;">Imóvel</label>
                    <div style="font-size:0.95rem; font-weight:500; line-height:1.4;">
                        <?= htmlspecialchars($data['processo_objeto'] ?? 'Regularização de Imóvel') ?>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr;">
                    <div style="padding:15px; border-right:1px solid rgba(255,255,255,0.1);">
                        <label style="display:block; font-size:0.7rem; text-transform:uppercase; opacity:0.75; margin-bottom:3px;">Matrícula</label>
                        <strong style="font-size:0.9rem;"><?= htmlspecialchars($data['num_matricula'] ?? '--') ?></strong>
                    </div>
                    <div style="padding:15px; border-right:1px solid rgba(255,255,255,0.1);">
                        <label style="display:block; font-size:0.7rem; text-transform:uppercase; opacity:0.75; margin-bottom:3px;">Área Final</label>
                        <strong style="font-size:0.9rem;"><?= htmlspecialchars($data['area_total_final'] ?? '--') ?> m²</strong>
                    </div>
                    <div style="padding:15px;">
                        <label style="display:block; font-size:0.7rem; text-transform:uppercase; opacity:0.75; margin-bottom:3px;">Valor Venal</label>
                        <strong style="font-size:0.9rem;"><?= htmlspecialchars($data['valor_venal'] ?? '--') ?></strong>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <p style="opacity:0.8;">Os dados do seu processo estão sendo carregados.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- ASSISTANT TIP -->
    <div class="assistant-tip fade-in-up">
        <div class="at-icon">🤖</div>
        <div class="at-content">
            <strong>Assistente Virtual</strong>
            <p>Olá! Este é o painel principal. Aqui você vê o resumo do seu patrimônio e o status geral. Para detalhes do histórico, toque em <strong>Fase Atual</strong> ou <strong>Documentos</strong> abaixo.</p>
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
