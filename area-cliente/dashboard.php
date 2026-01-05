<?php
session_name('CLIENTE_SESSID');
session_start();
require 'db.php'; // Database Connection

// Verify Login
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch Client Info
try {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_SESSION['cliente_id']]);
    $cliente = $stmt->fetch();

    // Verify Phases Logic (Same as Admin)
    $fases_padrao = [
        "Levantamento de Dados",
        "Desenvolvimento de Projetos",
        "Aprovação na Prefeitura",
        "Pagamento de Taxas",
        "Emissão de Alvará",
        "Entrega de Projetos"
    ];
    
    // Get Current Progress from 'processo_detalhes' or similar logic
    // Assuming simple logic for now -> 'etapa' field in clientes or fetch from details
    // For demo purposes, we will treat 'etapa' column if exists, or default to 0
    $etapa_atual = $cliente['etapa'] ?? 'Levantamento de Dados';
    
    // Calculate %
    $total_fases = count($fases_padrao);
    $fase_index = array_search($etapa_atual, $fases_padrao);
    $porcentagem = ($fase_index !== false && $fase_index >= 0) ? round((($fase_index + 1) / $total_fases) * 100) : 0;

} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Área do Cliente | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER -->
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <div style="font-size:0.9rem; color:#666;">Olá,</div>
                <h1 style="color:#146c43; font-size:1.8rem;"><?= explode(' ', $cliente['nome'])[0] ?></h1>
            </div>
            <a href="logout.php" style="background:#f8d7da; color:#dc3545; padding:8px 15px; border-radius:30px; font-weight:700; font-size:0.85rem;">
                Sair
            </a>
        </header>

        <!-- MAIN MENU GRID (Vertical "App" Style) -->
        <div class="app-action-grid">
            
            <!-- 1. TIMELINE -->
            <div class="app-button" onclick="document.getElementById('modalTimeline').showModal()">
                <div class="app-btn-icon" style="background:#e3f2fd; color:#0d47a1;">⏳</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Linha do Tempo</span>
                    <span class="app-btn-desc">Acompanhe o progresso da obra</span>
                </div>
                <div style="font-weight:800; color:#0d47a1;"><?= $porcentagem ?>%</div>
            </div>

            <!-- 2. PENDÊNCIAS -->
            <div class="app-button" onclick="document.getElementById('modalPendencias').showModal()">
                <div class="app-btn-icon" style="background:#fff3cd; color:#856404;">⚠️</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Pendências</span>
                    <span class="app-btn-desc">Itens que precisam da sua atenção</span>
                </div>
                <div style="background:#dc3545; width:10px; height:10px; border-radius:50%;"></div>
            </div>

            <!-- 3. FINANCEIRO -->
            <div class="app-button" onclick="document.getElementById('modalFinanceiro').showModal()">
                <div class="app-btn-icon" style="background:#d1e7dd; color:#146c43;">💰</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Financeiro</span>
                    <span class="app-btn-desc">Pagamentos e boletos</span>
                </div>
            </div>

            <!-- 4. DOCUMENTOS -->
            <div class="app-button" onclick="document.getElementById('modalDocumentos').showModal()">
                <div class="app-btn-icon" style="background:#e0e0e0; color:#333;">📂</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Documentos</span>
                    <span class="app-btn-desc">Projetos e arquivos no Drive</span>
                </div>
            </div>

        </div>

        <!-- DEVELOPER CREDIT -->
        <div style="text-align:center; margin-top:50px; opacity:0.6; font-size:0.8rem;">
            Desenvolvido por <strong>Vilela Engenharia</strong>
        </div>

    </div>

    <!-- INCLUDE MODALS -->
    <?php require 'includes/client_modals.php'; ?>

    <!-- JAVASCRIPT FOR PROGRESS BAR ANIMATION -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Set Progress Bar width dynamically
            setTimeout(() => {
                const fill = document.getElementById('progressFill');
                const text = document.getElementById('progressText');
                if(fill && text) {
                    fill.style.width = '<?= $porcentagem ?>%';
                    text.innerText = '<?= $porcentagem ?>%';
                }
            }, 500);
        });
    </script>

</body>
</html>
