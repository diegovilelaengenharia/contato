session_name('CLIENTE_SESSID');
session_start();
require 'db.php'; // Database Connection

// FORCE NO CACHE (Fix for immediate updates)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Verify Login
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch Client Info
try {
    $cliente_id = $_SESSION['cliente_id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();

    // 1. PROGRESS CALCULATION
    $fases_padrao = [
        "Levantamento de Dados",
        "Desenvolvimento de Projetos",
        "Aprovação na Prefeitura",
        "Pagamento de Taxas",
        "Emissão de Alvará",
        "Entrega de Projetos"
    ];
    $etapa_atual = $cliente['etapa'] ?? 'Levantamento de Dados';
    $total_fases = count($fases_padrao);
    $fase_index = array_search($etapa_atual, $fases_padrao);
    $porcentagem = ($fase_index !== false && $fase_index >= 0) ? round((($fase_index + 1) / $total_fases) * 100) : 0;

    // 2. FETCH PENDENCIES COUNT
    $stmt_pend = $pdo->prepare("SELECT COUNT(*) FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido'");
    $stmt_pend->execute([$cliente_id]);
    $pendencias_count = $stmt_pend->fetchColumn();

    // 3. FETCH NEXT PAYMENT
    $stmt_fin = $pdo->prepare("SELECT valor, data_vencimento FROM processo_financeiro WHERE cliente_id = ? AND status != 'pago' ORDER BY data_vencimento ASC LIMIT 1");
    $stmt_fin->execute([$cliente_id]);
    $next_bill = $stmt_fin->fetch(PDO::FETCH_ASSOC);

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
    <link href="style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER -->
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <div style="font-size:0.9rem; color:#666;">Olá,</div>
                <h1 style="color:#146c43; font-size:1.8rem;"><?= htmlspecialchars(explode(' ', $cliente['nome'])[0]) ?></h1>
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
                    <span class="app-btn-desc"><?= htmlspecialchars($etapa_atual) ?></span>
                </div>
                <div style="font-weight:800; color:#0d47a1;"><?= $porcentagem ?>%</div>
            </div>

            <!-- 2. PENDÊNCIAS -->
            <div class="app-button" onclick="document.getElementById('modalPendencias').showModal()">
                <div class="app-btn-icon" style="background:#fff3cd; color:#856404;">⚠️</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Pendências</span>
                    <?php if($pendencias_count > 0): ?>
                        <span class="app-btn-desc" style="color:#dc3545; font-weight:600;"><?= $pendencias_count ?> item(ns) pendente(s)</span>
                    <?php else: ?>
                        <span class="app-btn-desc">Tudo em dia!</span>
                    <?php endif; ?>
                </div>
                <?php if($pendencias_count > 0): ?>
                    <div style="background:#dc3545; color:white; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:bold;"><?= $pendencias_count ?></div>
                <?php else: ?>
                    <div style="color:#198754; font-size:1.2rem;">✅</div>
                <?php endif; ?>
            </div>

            <!-- 3. FINANCEIRO -->
            <div class="app-button" onclick="document.getElementById('modalFinanceiro').showModal()">
                <div class="app-btn-icon" style="background:#d1e7dd; color:#146c43;">💰</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Financeiro</span>
                    <?php if($next_bill): ?>
                        <span class="app-btn-desc">
                            Próx: <?= date('d/m', strtotime($next_bill['data_vencimento'])) ?> 
                            (R$ <?= number_format($next_bill['valor'], 2, ',', '.') ?>)
                        </span>
                    <?php else: ?>
                        <span class="app-btn-desc">Nenhum pagamento futuro</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. DOCUMENTOS -->
            <div class="app-button" onclick="document.getElementById('modalDocumentos').showModal()">
                <div class="app-btn-icon" style="background:#e0e0e0; color:#333;">📂</div>
                <div class="app-btn-content">
                    <span class="app-btn-title">Documentos</span>
                    <span class="app-btn-desc">Acessar Projetos</span>
                </div>
            </div>

        </div>

        <!-- DEVELOPER CREDIT -->
        <div style="text-align:center; margin-top:50px; opacity:0.6; font-size:0.8rem;">
            Desenvolvido por <strong>Diego T. N. Vilela</strong>
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
