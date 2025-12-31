<?php
// Dados Iniciais e Fallbacks
$primeiro_nome = $primeiro_nome ?? 'Cliente';
$etapa_atual = $detalhes['etapa_atual'] ?? 'Início';
$fase_index_atual = array_search($etapa_atual, $fases_padrao);
if($fase_index_atual === false) $fase_index_atual = 0;
$progresso_pct = round((($fase_index_atual + 1) / count($fases_padrao)) * 100);

// Cores e Icones por Status (Dinâmico)
$status_color = 'var(--color-primary)';
$status_icon = 'engineering';
if($progresso_pct >= 100) { $status_color = '#198754'; $status_icon = 'check_circle'; }
?>

<div class="fade-in-up" style="padding-bottom: 110px;">
    
    <!-- HEADER MINIMALISTA (Tecnico) -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding:0 5px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:40px; height:40px; background:var(--color-primary-light); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--color-primary); font-weight:bold;">
                <?= strtoupper(substr($primeiro_nome, 0, 1)) ?>
            </div>
            <div>
                 <span style="display:block; font-size:0.75rem; color:#666; text-transform:uppercase; letter-spacing:0.5px;">Requerente</span>
                 <strong style="font-size:1rem; color:#333;"><?= htmlspecialchars($primeiro_nome) ?></strong>
            </div>
        </div>
        <div style="text-align:right;">
             <span style="display:block; font-size:0.75rem; color:#999; text-transform:uppercase;">Processo Admin.</span>
             <strong style="color:var(--color-primary); font-size:1.1rem;"><?= htmlspecialchars($data['processo_numero'] ?? '---') ?></strong>
        </div>
    </div>

    <!-- MAIN STATUS WIDGET -->
    <div style="background: white; border-radius:16px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.06); margin-bottom:25px; border-left:5px solid <?= $status_color ?>; position:relative;">
        <h3 style="margin:0 0 15px 0; font-size:0.85rem; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Situação Atual</h3>
        
        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
             <div style="background:<?= $status_color ?>; color:white; width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                <span class="material-symbols-rounded" style="font-size:1.8rem;"><?= $status_icon ?></span>
             </div>
             <div>
                <strong style="display:block; font-size:1.2rem; color:#2c3e50; line-height:1.2;"><?= $etapa_atual ?></strong>
                <span style="font-size:0.8rem; color:#666;">Status: Em Andamento</span>
             </div>
        </div>

        <!-- Progress Bar Thin -->
        <div style="background:#f0f0f0; height:4px; border-radius:2px; overflow:hidden;">
            <div style="width:<?= $progresso_pct ?>%; height:100%; background:<?= $status_color ?>;"></div>
        </div>
        <div style="text-align:right; margin-top:5px; font-size:0.75rem; color:#aaa; font-weight:600;"><?= $progresso_pct ?>% CONCLUÍDO</div>
    </div>

    <!-- ACTIONS GRID (Clean Labels) -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:25px;">
        
        <button onclick="window.location.href='?view=timeline'" style="border:none; background:white; padding:15px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.03); text-align:left; display:flex; flex-direction:column; gap:5px; cursor:pointer; transition:0.2s;">
            <span class="material-symbols-rounded" style="font-size:1.8rem; color:#3498db;">history</span>
            <span style="font-size:0.9rem; font-weight:600; color:#333;">Trâmite Processual</span>
        </button>

        <button onclick="window.location.href='?view=documents'" style="border:none; background:white; padding:15px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.03); text-align:left; display:flex; flex-direction:column; gap:5px; cursor:pointer; transition:0.2s;">
            <span class="material-symbols-rounded" style="font-size:1.8rem; color:#f39c12;">folder_managed</span>
            <span style="font-size:0.9rem; font-weight:600; color:#333;">Autos / Peças</span>
        </button>

        <button onclick="window.location.href='?view=pendencias'" style="border:none; background:white; padding:15px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.03); text-align:left; display:flex; flex-direction:column; gap:5px; cursor:pointer; transition:0.2s; position:relative;">
            <span class="material-symbols-rounded" style="font-size:1.8rem; color:#e74c3c;">assignment_late</span>
            <span style="font-size:0.9rem; font-weight:600; color:#333;">Pendências</span>
            <?php if(count($pendencias) > 0): ?>
                <span style="position:absolute; top:10px; right:10px; width:8px; height:8px; background:#e74c3c; border-radius:50%;"></span>
            <?php endif; ?>
        </button>

        <button onclick="window.open('https://wa.me/5537998399321', '_blank')" style="border:none; background:white; padding:15px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.03); text-align:left; display:flex; flex-direction:column; gap:5px; cursor:pointer; transition:0.2s;">
            <span class="material-symbols-rounded" style="font-size:1.8rem; color:#2ecc71;">chat</span>
            <span style="font-size:0.9rem; font-weight:600; color:#333;">Falar com Técnico</span>
        </button>
    </div>

    <!-- DADOS TÉCNICOS (Compact List) -->
    <h3 style="margin:0 0 10px 5px; font-size:0.9rem; color:#666; text-transform:uppercase; letter-spacing:0.5px;">Metadados do Processo</h3>
    <div style="background:white; border-radius:16px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
        
        <div style="padding:15px; border-bottom:1px solid #f5f5f5; display:flex; justify-content:space-between; align-items:center;">
             <span style="font-size:0.9rem; color:#555;">Objeto</span>
             <strong style="font-size:0.9rem; color:#333; text-align:right; max-width:60%;"><?= htmlspecialchars($data['processo_objeto'] ?? '-') ?></strong>
        </div>

        <div style="padding:15px; border-bottom:1px solid #f5f5f5; display:flex; justify-content:space-between; align-items:center;">
             <span style="font-size:0.9rem; color:#555;">Logradouro</span>
             <strong style="font-size:0.9rem; color:#333; text-align:right; max-width:60%;"><?= htmlspecialchars($endereco) ?></strong>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; border-bottom:1px solid #f5f5f5;">
            <div style="padding:15px; flex:1; border-right:1px solid #f5f5f5;">
                 <span style="display:block; font-size:0.75rem; color:#999;">Área Processada</span>
                 <strong style="color:var(--color-primary); font-size:1.1rem;"><?= htmlspecialchars($data['area_total_final'] ?? '--') ?> m²</strong>
            </div>
            <div style="padding:15px; flex:1;">
                 <span style="display:block; font-size:0.75rem; color:#999;">Taxa Ocup. (%)</span>
                 <strong style="color:var(--color-primary); font-size:1.1rem;"><?= htmlspecialchars($data['taxa_ocupacao'] ?? '--') ?>%</strong>
            </div>
        </div>
    </div>

</div>
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
                <?php if(!empty($data['geo_coords'])): ?>
                    <small style="opacity:0.7; font-size:0.8rem;">(<?= htmlspecialchars($data['geo_coords']) ?>)</small>
                <?php endif; ?>
            </p>

            <!-- Technical Grid (Oliveira/MG Spec) -->
            <div class="pc-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                 <div class="pc-grid-item">
                    <label>Área Existente</label>
                    <strong><?= htmlspecialchars($data['area_existente'] ?? '--') ?> m²</strong>
                </div>
                <div class="pc-grid-item">
                    <label>Área Acréscimo</label>
                    <strong><?= htmlspecialchars($data['area_acrescimo'] ?? '--') ?> m²</strong>
                </div>
                 <div class="pc-grid-item">
                    <label>Área Permeável</label>
                    <strong><?= htmlspecialchars($data['area_permeavel'] ?? '--') ?> m²</strong>
                </div>

                <div class="pc-grid-item">
                    <label>Taxa Ocupação</label>
                    <strong><?= htmlspecialchars($data['taxa_ocupacao'] ?? '--') ?>%</strong>
                </div>
                <div class="pc-grid-item">
                    <label>Coef. Aprov. (CA)</label>
                    <strong><?= htmlspecialchars($data['fator_aproveitamento'] ?? '--') ?></strong>
                </div>
                 <div class="pc-grid-item highlight">
                    <label>Valor Venal Proposto</label>
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

    <!-- CENTRAL DE CONHECIMENTO LINK -->
    <div onclick="window.location.href='?view=conhecimento'" style="background:white; border:1px solid var(--border-color); padding:15px; border-radius:12px; display:flex; align-items:center; gap:15px; cursor:pointer; margin-bottom:20px; box-shadow:var(--shadow-card);">
        <div style="background:var(--color-primary-light); color:var(--color-primary); padding:10px; border-radius:50%;">
            <span class="material-symbols-rounded">menu_book</span>
        </div>
        <div style="flex:1;">
            <strong style="display:block; color:var(--text-main); font-size:1rem;">Central de Conhecimento</strong>
            <span style="font-size:0.85rem; color:var(--text-muted);">Glossário Técnico e Legislação Local</span>
        </div>
        <div style="color:var(--text-muted);">
            <span class="material-symbols-rounded">chevron_right</span>
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
