<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        echo "<div style='background:red; color:white; padding:20px; font-weight:bold; z-index:99999; position:relative;'>FATAL ERROR ADMIN: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "</div>";
        die();
    }
});
require 'includes/init.php';

// --- Atualização de Schema ---
require 'includes/schema.php';

// --- Fases Padrão ---
$fases_padrao = [
    "Abertura de Processo (Guichê)", 
    "Fiscalização (Parecer Fiscal)", 
    "Triagem (Documentos Necessários)",
    "Comunicado de Pendências (Triagem)", 
    "Análise Técnica (Engenharia)", 
    "Comunicado (Pendências e Taxas)",
    "Confecção de Documentos", 
    "Avaliação (ITBI/Averbação)", 
    "Processo Finalizado (Documentos Prontos)"
];

// --- Taxas e Multas Padrão ---
$taxas_padrao = require 'config/taxas.php';

// --- Processamento ---
// Helper Function for Finance Table
function renderFinTable($stmt, $title, $color, $cid) {
    if(!$stmt) return;
    $rows = $stmt->fetchAll();
    // Use admin-tab-content but with dynamic border color
    echo "<div class='admin-tab-content' style='border-top: 4px solid $color;'>
            <h3 class='admin-title' style='color:$color; margin-bottom:20px;'>$title</h3>";
    
    if(count($rows) == 0) {
        echo "<p class='admin-subtitle' style='font-style:italic;'>Nenhum lançamento encontrado nesta categoria.</p>";
    } else {
        echo "<div class='admin-table-container'>
              <table class='admin-table' style='min-width:600px;'>
                <thead><tr>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th style='text-align:center;'>Status</th>
                    <th style='text-align:center;'>Ação</th>
                    <th></th>
                </tr></thead><tbody>";
        foreach($rows as $r) {
            $st_color = 'black';
            $st_icon = '';
            // Badge classes for status
            $badge_class = 'status-badge'; 
            $status_label = ucfirst($r['status']);
            
            switch($r['status']){
                case 'pago': $badge_class.=' success'; $st_icon='✅'; break;
                case 'pendente': $badge_class.=' warning'; $st_icon='⏳'; break;
                case 'atrasado': $badge_class.=' danger'; $st_icon='❌'; break;
                case 'isento': $badge_class.=' info'; $st_icon='⚪'; break;
            }
            $valor = number_format($r['valor'], 2, ',', '.');
            $data = date('d/m/Y', strtotime($r['data_vencimento']));
            $link = $r['link_comprovante'] ? "<a href='{$r['link_comprovante']}' target='_blank' style='color:#0d6efd; font-weight:600; text-decoration:none;'>📄 Ver Doc</a>" : "<span style='opacity:0.5'>--</span>";
            
            echo "<tr>
                    <td style='font-weight:500;'>{$r['descricao']}</td>
                    <td style='font-weight:bold;'>R$ {$valor}</td>
                    <td>{$data}</td>
                    <td style='text-align:center;'>
                         <span class='$badge_class' onclick=\"openStatusFinModal({$r['id']}, '{$r['status']}')\" style='cursor:pointer;' title='Alterar Status'>
                            {$st_icon} {$status_label}
                         </span>
                    </td>
                    <td style='text-align:center;'>{$link}</td>
                    <td style='text-align:right;'>
                        <a href='?cliente_id={$cid}&tab=financeiro&del_fin={$r['id']}' onclick='confirmAction(event, \"Tem certeza que deseja EXCLUIR este lançamento financeiro?\")' style='color:#dc3545; text-decoration:none; font-size:1.1rem;'>🗑️</a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    }
    echo "</div>";
}

// --- Processamento (POST/GET) ---
require 'includes/processamento.php';

// Exportar Relatório (Exaustivo e Profissional)
require 'includes/exportacao.php';


// --- Consultas Iniciais e Dashboard Data ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
$cliente_ativo = null;
$detalhes = [];

// Dados para Dashboard
try {
    // 1. Total Clientes
    $kpi_total_clientes = count($clientes);

    // 2. Pré-Cadastros Pendentes
    $stmt_pre = $pdo->query("SELECT COUNT(*) FROM pre_cadastros WHERE status='pendente'");
    $kpi_pre_pendentes = $stmt_pre ? $stmt_pre->fetchColumn() : 0;

    // 3. Financeiro Pendente (Soma Global)
    // 3. Financeiro
    // Atrasados
    $stmt_fin_atrasados = $pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status = 'atrasado'");
    $kpi_fin_atrasado = $stmt_fin_atrasados ? $stmt_fin_atrasados->fetchColumn() : 0;
    
    // Futuros/Pendentes
    $stmt_fin_pendentes = $pdo->query("SELECT SUM(valor) FROM processo_financeiro WHERE status = 'pendente'");
    $kpi_fin_pendente = $stmt_fin_pendentes ? $stmt_fin_pendentes->fetchColumn() : 0;
    
    // 4. Processos Ativos (Não finalizados)
    $stmt_proc = $pdo->query("SELECT COUNT(*) FROM processo_detalhes WHERE etapa_atual != 'Processo Finalizado (Documentos Prontos)' AND etapa_atual IS NOT NULL AND etapa_atual != ''");
    $kpi_proc_ativos = $stmt_proc ? $stmt_proc->fetchColumn() : 0;

} catch (Exception $e) {
    // Silencia erro se tabelas não existirem ainda
    $kpi_total_clientes = 0; $kpi_pre_pendentes = 0; $kpi_fin_pendente = 0; $kpi_proc_ativos = 0;
}

if (isset($_GET['cliente_id'])) {
    $id = $_GET['cliente_id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?"); $stmt->execute([$id]);
    $cliente_ativo = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?"); $stmt->execute([$id]);
    $detalhes = $stmt->fetch();
    if(!$detalhes) $detalhes = [];
}
$active_tab = $_GET['tab'] ?? 'cadastro';

// --- LOGICA DE AVATAR (MOVIDO PARA O TOPO PARA DISPONIBILIZAR NA SIDEBAR) ---
$avatar_url = null;
if($cliente_ativo) {
    // Process Upload
    if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if(in_array($ext, $allowed)) {
            $new_name = 'avatar_' . $cliente_ativo['id'] . '.' . $ext;
            $upload_path = 'uploads/avatars/' . $new_name;
            if(!is_dir('uploads/avatars/')) mkdir('uploads/avatars/', 0755, true);
            
            if(move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $upload_path)) {
                // FORCE UPDATE IN DATABASE
                    try {
                    $stmt = $pdo->prepare("UPDATE clientes SET foto_perfil = ? WHERE id = ?");
                    $stmt->execute([$upload_path, $cliente_ativo['id']]);
                    
                    // Reload to show changes
                    $tab_redir = $_GET['tab'] ?? 'perfil';
                    echo "<script>window.location.href='?cliente_id={$cliente_ativo['id']}&tab={$tab_redir}&avatar_updated=1';</script>";
                    exit;
                    } catch(Exception $e) {}
            }
        }
    }
    
    // Get Avatar URL
    $avatar_file = glob("uploads/avatars/avatar_{$cliente_ativo['id']}.*");
    $avatar_url = !empty($avatar_file) ? $avatar_file[0] . '?v=' . time() : null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- SweetAlert2 + Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css?v=<?= time() ?>">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
    <style>
        .ck-editor__editable { min-height: 200px; }
    </style>
</head>
<body>

    <!-- UI Components -->
    <!-- UI Components -->
    <!-- UI Components -->
    <!-- DEPRECATED: Header removed by request on 2026-01-13 -->
    <?php /* require 'includes/ui/header.php'; */ ?>

    <!-- DATA FETCHING FOR SIDEBAR WIDGETS -->
    <?php
        // Aniversariantes
        $aniversariantes = $pdo->query("SELECT c.id, c.nome, pd.data_nascimento, DAY(pd.data_nascimento) as dia 
            FROM clientes c 
            JOIN processo_detalhes pd ON c.id = pd.cliente_id 
            WHERE MONTH(pd.data_nascimento) = MONTH(CURRENT_DATE()) 
            ORDER BY dia ASC")->fetchAll();

        // Processos Parados (> 15 dias)
        $parados = $pdo->query("SELECT c.id, c.nome, MAX(pm.data_movimento) as ultima_mov 
            FROM clientes c 
            JOIN processo_movimentos pm ON c.id = pm.cliente_id 
            GROUP BY c.id 
            HAVING DATEDIFF(NOW(), ultima_mov) > 15 
            ORDER BY ultima_mov ASC")->fetchAll();
            
        // Solicitações Web (Pendentes) - Available for sidebar counts if needed
        $solicitacoes = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC")->fetchAll();
        $kpi_pre_pendentes = count($solicitacoes); // Ensure variable matches what sidebar expects
    ?>

    <div class="admin-container">
        <?php require 'includes/ui/sidebar.php'; ?>
        
        <main style="padding-bottom: 80px;"> <!-- Padding for fixed footer area -->
            
            <!-- GLOBAL HEADLINE (Removed Admin Profile from here) -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div>
                    <!-- Optional Page Title or Breadcrumb could go here -->
                </div>
            </div>

            <!-- NEW: Dashboard Alert Widgets REMOVED -->


        <?php if(isset($_GET['importar'])): ?>
            <div class="form-card">
                <h2>Importar Cadastros do Site</h2>
                <p>Abaixo estão as solicitações de cadastro vindas da página pública.</p>
                <div class="table-responsive">
                    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                        <thead><tr style="background:#eee;"><th style="padding:10px;">Data</th><th style="padding:10px;">Nome</th><th style="padding:10px;">Contato</th><th style="padding:10px;">Serviço</th><th style="padding:10px;">Ação</th></tr></thead>
                        <tbody>
                            <?php 
                            try {
                                $pendentes = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC")->fetchAll();
                                if(count($pendentes) == 0) echo "<tr><td colspan='5' style='padding:20px; text-align:center;'>Nenhuma solicitação pendente.</td></tr>";
                                foreach($pendentes as $p): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="padding:10px;"><?= date('d/m/Y H:i', strtotime($p['data_solicitacao'])) ?></td>
                                    <td style="padding:10px;"><strong><?= htmlspecialchars($p['nome']) ?></strong><br><small><?= $p['cpf_cnpj'] ?></small></td>
                                    <td style="padding:10px;"><?= $p['telefone'] ?><br><small><?= $p['email'] ?></small></td>
                                    <td style="padding:10px;"><?= $p['tipo_servico'] ?></td>
                                    <td style="padding:10px; text-align:center;">
                                        <button type="button" onclick="openAprovarModal(<?= $p['id'] ?>, '<?= addslashes($p['nome']) ?>', '<?= addslashes($p['cpf_cnpj']) ?>')" class="btn-save btn-success" style="padding:5px 10px; font-size:0.8rem; cursor:pointer; width:auto;">✅ Aprovar</button>
                                    </td>
                                </tr>
                            <?php endforeach; 
                            } catch(Exception $e) { echo "<tr><td colspan='5'>Erro: Rode o setup_cadastro_db.php</td></tr>"; }
                            ?>
                        </tbody>
                    </table>

                    <?php require 'includes/modals/cadastro.php'; ?>
                </div>
            </div>

        <?php elseif(isset($_GET['novo'])): ?>
            <!-- Legacy block removed. Redirecting to gerenciar_cliente.php -->
            <script>window.location.href='gerenciar_cliente.php';</script>

        <?php elseif($cliente_ativo): ?>
            <!-- Lógica de Upload de Avatar movida para o início do arquivo (antes da sidebar) -->
            <?php /* Avatar Logic Moved Up */ ?>

            <!-- Old Client Summary Card Removed (Moved to Profile Tab) -->
            
            <!-- MODERN TIMELINE HEADER (Somente Stepper) -->
            <style>
                .timeline-header {
                    background: #fff;
                    border-radius: 20px;
                    padding: 30px 40px; /* Increased padding for standalone look */
                    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
                    margin-bottom: 30px;
                    border: 1px solid #f0f0f0;
                    position: relative;
                    overflow: visible; /* Prevent cutting off tooltips/shadows */
                }

                .th-container { display: flex; align-items: center; justify-content: center; width: 100%; }

                /* Timeline Stepper */
                .th-stepper { flex: 1; display: flex; align-items: center; justify-content: space-between; position: relative; max-width: 900px; margin: 0 auto; }
                
                .th-line-bg {
                    position: absolute; left: 0; right: 0; top: 15px; height: 4px; background: #e9ecef; z-index: 0; border-radius: 3px;
                }
                .th-line-fill {
                    position: absolute; left: 0; top: 15px; height: 4px; background: #198754; z-index: 0; border-radius: 3px;
                    transition: width 1s ease;
                }
                
            <!-- TAB NAVIGATION PILLS -->
            <style>
                .nav-pills {
                    display: flex;
                    gap: 12px;
                    margin-bottom: 25px;
                    flex-wrap: wrap;
                }
                .nav-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 10px 20px;
                    background: #fff;
                    border: 1px solid #e0e0e0;
                    border-radius: 50px; /* Pill shape */
                    text-decoration: none;
                    color: #555;
                    font-weight: 600;
                    font-size: 0.95rem;
                    transition: all 0.2s;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                }
                .nav-pill:hover {
                    background: #f8f9fa;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
                }
                .nav-pill.active {
                    background: #2f3e36; /* Dark Vilela Green/Gray */
                    color: white;
                    border-color: transparent;
                    box-shadow: 0 4px 12px rgba(47, 62, 54, 0.2);
                }
                .nav-pill .material-symbols-rounded {
                    font-size: 1.2rem;
                }
            </style>
            
            <!-- ABAS DE NAVEGAÇÃO (Pills) -->
            <div class="nav-pills">
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=docs_iniciais" class="nav-pill <?= ($active_tab=='docs_iniciais')?'active':'' ?>">
                    <span class="material-symbols-rounded">folder_open</span>
                    Documentos
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="nav-pill <?= ($active_tab=='andamento'||$active_tab=='cadastro')?'active':'' ?>">
                    <span class="material-symbols-rounded">history</span>
                    Timeline
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="nav-pill <?= ($active_tab=='pendencias')?'active':'' ?>">
                    <span class="material-symbols-rounded">warning</span>
                    Pendências
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="nav-pill <?= ($active_tab=='financeiro')?'active':'' ?>">
                    <span class="material-symbols-rounded">paid</span>
                    Financeiro
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=arquivos" class="nav-pill <?= ($active_tab=='arquivos')?'active':'' ?>">
                    <span class="material-symbols-rounded">inventory_2</span>
                    Arquivos
                </a>
            </div>

            <!-- Modal Timeline e Andamento -->
            <?php require 'includes/modals/timeline.php'; ?>
            
            <!-- CONTEÚDO DA ABA PERFIL (Removido, agora é Fixo acima) -->


            <!-- TAB NAVIGATION -->
            <!-- Styles for Tabs (Multi-Color) -->
            <!-- STYLE FOR FLEX LAYOUT & HIDDEN TABS -->
            <style>
                .admin-container {
                    display: block !important; /* Sidebar is fixed, so block is safer */
                    max-width: 100% !important;
                    margin: 0; padding: 0;
                }
                
                aside.sidebar {
                    display: block !important;
                    flex-shrink: 0; 
                    position: fixed;
                    top: 0; left: 0;
                    width: 280px;
                    height: 100vh;
                    overflow-y: auto;
                    z-index: 2000;
                    margin: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: 2px 0 15px rgba(0,0,0,0.06);
                    background: #fff;
                    padding-bottom: 120px; /* Keep bottom buffer */
                }
                
                /* Custom Scrollbar for Sidebar */
                aside.sidebar::-webkit-scrollbar { width: 6px; }
                aside.sidebar::-webkit-scrollbar-track { background: transparent; }
                aside.sidebar::-webkit-scrollbar-thumb { background: #dfe6e9; border-radius: 3px; }
                aside.sidebar::-webkit-scrollbar-thumb:hover { background: #b2bec3; }

                main {
                    display: block;
                    width: auto;
                    margin-left: 280px; /* Offset content */
                    padding: 30px;
                    position: relative; /* Context for absolute positioning */
                }

                /* Ocultar navegação antiga */
                .tabs-container { display: none !important; }

                /* Responsividade */
                @media (max-width: 991px) {
                    .admin-container {
                        flex-direction: column;
                    }
                    .admin-nav-sidebar {
                        width: 100%;
                        position: relative;
                        top: 0;
                        margin-bottom: 20px;
                        height: auto;
                        position: relative;
                    }
                    main { margin-left: 0; width: 100%; max-width: 100%; padding-top: 80px; /* Space for Profile */ }
                }

                /* Button styling for delete actions */
                .btn-danger-hover { /* Applied to delete buttons */
                    color: #dc3545 !important; /* Red text */
                    background: #fff5f5 !important; /* Light red background */
                    border: 1px solid #f5c2c7 !important;
                    font-weight: 700 !important;
                }
                .btn-danger-hover:hover {
                    background: #f8d7da !important; /* Darker red on hover */
                }
            </style>

            <!-- Modal Timeline e Andamento (Already included below) -->
            <!-- REMOVED DUPLICATE INCLUDE OF timeline.php -->
            
            <!-- WINDOW CONTENT CONTAINER -->
            <?php 
                // Define window color based on active tab (Unified Green)
                $win_border_color = '#146c43';
            ?>

            <div style="background:#fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); padding: 35px; margin-bottom: 30px; border: 1px solid #f0f0f0;">

            <!-- Script removed as logic is now backend-driven -->

            <?php if($active_tab == 'cadastro' || $active_tab == 'andamento'): ?>
                <div class="admin-tab-content">
                    <!-- Modern Header -->
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; gap:20px;">
                        <div>
                            <h3 style="margin:0 0 6px 0; font-size:1.6rem; font-weight:800; color:#2c3e50; letter-spacing:-0.5px; display:flex; align-items:center; gap:10px;">
                                Histórico do Processo
                            </h3>
                            <p style="margin:0; font-size:0.95rem; color:#6c757d; font-weight:400;">Linha do tempo completa e registros do cliente.</p>
                        </div>
                        
                        <div style="display:flex; gap:12px; align-items:center;">
                            <!-- Botão Novo Andamento (Integrado) -->
                            <button type="button" onclick="document.getElementById('modalAndamento').showModal()" style="padding:12px 24px; background:#198754; border:none; border-radius:12px; font-size:0.95rem; font-weight:600; color:white; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; box-shadow:0 4px 12px rgba(25, 135, 84, 0.25);">
                                <span class="material-symbols-rounded">add_circle</span> Novo Andamento
                            </button>

                            <!-- Botão Ver Timeline (Popup) -->
                            <button type="button" onclick="document.getElementById('modalVisualTimeline').showModal()" style="padding:12px 18px; background:#e9ecef; border:none; border-radius:12px; font-size:0.95rem; font-weight:600; color:#495057; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
                                <span class="material-symbols-rounded">visibility</span> Ver Timeline
                            </button>
                            
                             <!-- Botão Apagar Histórico (Perigo) -->
                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento&del_all_hist=true" 
                               onclick="return confirm('ATENÇÃO EXTREMA: \n\nVocê está prestes a APAGAR TODO O HISTÓRICO deste processo.\n\nIsso limpará todas as movimentações, datas e logs.\n\nTem certeza absoluta que deseja fazer isso?');"
                               style="background:#fff5f5; color:#dc3545; padding:11px 16px; border:1px solid #f5c2c7; border-radius:12px; font-size:0.9rem; text-decoration:none; font-weight:700; display:flex; align-items:center; gap:6px; transition:all 0.2s;" title="Limpar Tudo">
                                <span class="material-symbols-rounded">delete_sweep</span> Limpar
                            </a>
                        </div>
                    </div>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead><tr><th>Data</th><th>Evento</th><th style="text-align:center;">Ação</th></tr></thead>
                            <tbody>
                                <?php 
                                $hist = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id=? ORDER BY data_movimento DESC");
                                $hist->execute([$cliente_ativo['id']]);
                                foreach($hist->fetchAll() as $h): 
                                    // Style based on type
                                    $row_style = "";
                                    $icon_type = "📌";
                                    if(($h['tipo_movimento']??'padrao') == 'fase_inicio') {
                                        $row_style = "background:#f3e8ff; border-left:5px solid #6610f2;"; // Styled for History
                                        $icon_type = "🚀";
                                    } elseif(($h['tipo_movimento']??'padrao') == 'documento') {
                                        $row_style = "background:#f8f9fa; border-left:5px solid #198754;";
                                        $icon_type = "📄";
                                    }
                                ?>
                                    <tr style="<?= $row_style ?>">
                                        <td style="white-space:nowrap; vertical-align:top;">
                                            <?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:bold; margin-bottom:5px; color:#212529; font-size:1rem;"><?= htmlspecialchars($h['titulo_fase']) ?></div>
                                            <?php 
                                                // Lógica de exibição de comentários estilizados
                                                $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                                                // Permite HTML rico da primeira parte (descrição do sistema/admin)
                                                // But previne XSS grosseiro se quiser, porem aqui confiamos no admin.
                                                // removemos htmlspecialchars e nl2br pois o CKEditor já formata p/ html
                                                $sys_desc = $parts[0]; 
                                                echo "<div style='color:var(--color-text-subtle); line-height:1.5; font-size:0.95rem;'>{$sys_desc}</div>";
                                                
                                                // Se tiver comentário do usuário
                                                if (count($parts) > 1) {
                                                    $user_comment = nl2br(htmlspecialchars($parts[1]));
                                                    echo "<div style='margin-top:8px; border-left: 3px solid #d32f2f; padding-left:10px;'>
                                                            <span style='font-weight:800; color:black;'>Comentário Diego Vilela:</span>
                                                            <div style='color:#d32f2f; font-weight:bold; margin-top:2px;'>{$user_comment}</div>
                                                          </div>";
                                                }
                                            ?>
                                        </td>

                                        <td style="text-align:center; vertical-align:top;">
                                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento&del_hist=<?= $h['id'] ?>" onclick="confirmAction(event, 'ATENÇÃO: Deseja realmente apagar este histórico? Essa ação é irreversível.')" style="text-decoration:none; color:#dc3545; font-size:1.1rem; padding:8px; background:#fff5f5; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; transition:0.2s;" title="Excluir Histórico">
                                                <span class="material-symbols-rounded" style="font-size:1.1rem;">delete</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($active_tab == 'pendencias'): ?>

                <!-- PENDÊNCIAS CONTENT (Laranja) -->
                <div class="admin-tab-content">
                    <!-- LÓGICA DE EXCLUSÃO (Backend) -->
                    <?php
                    // 1. Exclusão Individual
                    if(isset($_GET['delete_file_pendencia']) && isset($_GET['file_name'])) {
                         $f_delete = basename($_GET['file_name']);
                         $p_delete = __DIR__ . '/client-app/uploads/pendencias/' . $f_delete;
                         if(file_exists($p_delete)) {
                             unlink($p_delete);
                             echo "<script>window.location.href='?cliente_id={$cliente_ativo['id']}&tab=pendencias&msg=file_deleted';</script>";
                         }
                    }
                    
                    // 2. Limpar Pasta Completa (Bulk)
                    if(isset($_GET['clear_all_files']) && $_GET['clear_all_files'] == 'true') {
                        $p_dir = __DIR__ . '/client-app/uploads/pendencias/';
                        $stmtIds = $pdo->prepare("SELECT id FROM processo_pendencias WHERE cliente_id=?");
                        $stmtIds->execute([$cliente_ativo['id']]);
                        $ids = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
                        
                        $count_del = 0;
                        if($ids) {
                            foreach($ids as $pid) {
                                $files = glob($p_dir . $pid . "_*.*");
                                if($files) {
                                    foreach($files as $f) {
                                        unlink($f);
                                        $count_del++;
                                    }
                                }
                            }
                        }
                        echo "<script>window.location.href='?cliente_id={$cliente_ativo['id']}&tab=pendencias&msg=all_files_cleared&count={$count_del}';</script>";
                    }
                    ?>

                    <div class="admin-header-row">
                        <div>
                            <h3 class="admin-title">📋 Checklist de Pendências</h3>
                            <p class="admin-subtitle">Gerencie os itens pendentes e verifique os arquivos enviados.</p>
                        </div>
                        
                        <div style="text-align:right;">
                             <button onclick="document.getElementById('modalNovaPendencia').showModal()" style="padding:8px 15px; background:linear-gradient(135deg, #198754, #146c43); border:none; border-radius:30px; font-size:0.8rem; font-weight:700; color:white; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 4px 10px rgba(25, 135, 84, 0.3); transition:all 0.2s; margin-left:10px;">
                                <span style="font-size:1rem;">➕</span> Nova Pendência
                            </button>
                            
                            <?php 
                            // Movido para cá para usar no botão WhatsApp
                            $stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id=? ORDER BY status ASC, id DESC");
                            $stmt_pend->execute([$cliente_ativo['id']]);
                            $pendencias = $stmt_pend->fetchAll(); // Mantendo nome da variavel igual

                            // Lógica WhatsApp - Cobrança Dinâmica
                            $pend_abertas = array_filter($pendencias, function($p) {
                                return $p['status'] == 'pendente' || $p['status'] == 'anexado';
                            });
                            
                            // GERA TEXTO SEMPRE (independente de ter telefone)
                            $primeiro_nome = explode(' ', trim($cliente_ativo['nome']))[0];
                            $msg_wpp_pend = "Olá {$primeiro_nome}, tudo bem? Espero que sim! 🤝\n\nSou da *Vilela Engenharia*. Passando para lembrar das pendências necessárias para darmos andamento ao seu processo:\n\n";
                            
                            if(count($pend_abertas) > 0) {
                                foreach($pend_abertas as $p) {
                                    $msg_wpp_pend .= "👉 " . strip_tags($p['descricao']) . "\n";
                                }
                            } else {
                                $msg_wpp_pend .= "(Nenhuma pendência em aberto)\n";
                            }
                            
                            $msg_wpp_pend .= "\nVocê pode anexar os documentos ou ver mais detalhes acessando sua Área do Cliente:\nhttps://vilela.eng.br/area-cliente/\n\nQualquer dúvida, fique à vontade para me chamar!";
                            ?>
                             <a href="https://wa.me/55<?= preg_replace('/\D/','',$detalhes['contato_tel']??'') ?>?text=<?= urlencode($msg_wpp_pend) ?>" target="_blank" class="btn-save" style="background:#ffc107; color:black; border:none; margin-left:10px; padding:8px 15px;">
                                📱 Cobrar no WhatsApp
                            </a>

                            <!-- Botão Limpar Pasta (MOVIDO PARA FINAL) -->
                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&clear_all_files=true" 
                               onclick="return confirm('ATENÇÃO: Isso apagará TODOS os arquivos anexados nas pendências deste cliente.\n\nDeseja continuar?')"
                               style="background:#f8d7da; color:#dc3545; padding:8px 15px; border-radius:30px; font-size:0.8rem; font-weight:700; text-decoration:none; border:1px solid #f5c6cb; display:inline-flex; align-items:center; gap:5px; margin-left:10px;">
                                🗑️ Limpar Pasta de Arquivos
                            </a>
                        </div>
                    </div>
                    
                    <!-- Lista de Pendências (MOVIDO PARA CIMA) -->
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width:60%;">Descrição</th>
                                    <th style="text-align:center;">Data</th>
                                    <th style="text-align:center;">Status</th>
                                    <th style="text-align:right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Query de pendências já feita acima para o botão WhatsApp
                                
                                // Buscar Arquivos (DB + FileSystem)
                                $stmtArq = $pdo->prepare("SELECT pendencia_id, id, arquivo_nome, arquivo_path, data_upload FROM processo_pendencias_arquivos WHERE pendencia_id IN (SELECT id FROM processo_pendencias WHERE cliente_id=?)");
                                $stmtArq->execute([$cliente_ativo['id']]);
                                $arquivos_por_pendencia = [];
                                
                                // 1. Do DB
                                foreach($stmtArq->fetchAll() as $arq) {
                                    $arquivos_por_pendencia[$arq['pendencia_id']][] = $arq;
                                }
                                
                                // 2. Do Sistema de Arquivos (Robustez)
                                $upload_dir_admin = __DIR__ . '/client-app/uploads/pendencias/';
                                $web_path_admin = 'client-app/uploads/pendencias/';
                                
                                if(is_dir($upload_dir_admin)) {
                                    foreach($pendencias as $p_check) {
                                        $files_fs = glob($upload_dir_admin . $p_check['id'] . "_*.*");
                                        if($files_fs) {
                                            foreach($files_fs as $f_fs) {
                                            // Loop continues correctly here

                                              $fname = basename($f_fs);
                                              // Evita duplicatas se já vieram do banco (por nome)
                                              $ja_existe = false;
                                              if(isset($arquivos_por_pendencia[$p_check['id']])) {
                                                  foreach($arquivos_por_pendencia[$p_check['id']] as $ex) {
                                                      if($ex['arquivo_nome'] == $fname) $ja_existe = true;
                                                  }
                                              }
                                              
                                              if(!$ja_existe) {
                                                  $arquivos_por_pendencia[$p_check['id']][] = [
                                                      'arquivo_nome' => $fname,
                                                      'arquivo_path' => $web_path_admin . $fname,
                                                      'data_upload' => date('Y-m-d H:i:s', filemtime($f_fs))
                                                  ];
                                              }
                                            }
                                        }
                                    }
                                }
                                
                                if(count($pendencias) == 0): ?>
                                    <tr><td colspan="4" style="padding:30px; text-align:center; color:#aaa; font-style:italic;">Nenhuma pendência registrada para este cliente.</td></tr>
                                <?php else: foreach($pendencias as $p): 
                                    $is_res = ($p['status'] == 'resolvido');
                                    $is_anexo = ($p['status'] == 'anexado');
                                    $row_opac = $is_res ? '0.6' : '1';
                                    $bg_row = $is_res ? '#f8fff9' : ($is_anexo ? '#f0f8ff' : '#fff');
                                    $txt_dec = $is_res ? 'line-through' : 'none';
                                    
                                    // Arquivos
                                    $arquivos = $arquivos_por_pendencia[$p['id']] ?? [];
                                    // Legado
                                    if (!empty($p['arquivo_path']) && empty($arquivos)) {
                                        $arquivos[] = ['arquivo_nome' => 'Anexo (Antigo)', 'arquivo_path' => $p['arquivo_path']];
                                    }
                                ?>
                                    <tr style="background:<?= $bg_row ?>; opacity:<?= $row_opac ?>;">
                                        <td>
                                            <div style="font-size:1.05rem; color:#333; text-decoration:<?= $txt_dec ?>;">
                                                <?= $p['descricao'] // Já permite HTML do editor ?>
                                            </div>
                                            <?php if(!empty($arquivos)): ?>
                                                <div style="margin-top:8px; display:flex; flex-direction:column; gap:5px;">
                                                    <?php foreach($arquivos as $arq): ?>
                                                    <div style="display:flex; align-items:center; gap:8px;">
                                                        <a href="<?= htmlspecialchars($arq['arquivo_path']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:5px; font-size:0.85rem; color:#0d6efd; text-decoration:none; background:#e9ecef; padding:4px 10px; border-radius:4px; font-weight:600;">
                                                            📎 <?= (strlen($arq['arquivo_nome']) > 40 ? substr($arq['arquivo_nome'],0,40).'...' : $arq['arquivo_nome']) ?>
                                                        </a>
                                                        <!-- Botão Excluir Arquivo -->
                                                        <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&delete_file_pendencia=true&file_name=<?= urlencode($arq['arquivo_nome']) ?>" 
                                                           onclick="return confirm('ATENÇÃO: Deseja apagar este arquivo permanentemente?')"
                                                           style="text-decoration:none; font-size:1.1rem; padding:2px;" title="Apagar Arquivo">🗑️</a>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; color:#777; font-size:0.9rem;">
                                            <?= date('d/m/Y', strtotime($p['data_criacao'])) ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if($is_res): ?>
                                                <span class="status-badge success">RESOLVIDO</span>
                                            <?php elseif($is_anexo): ?>
                                                <span class="status-badge info">ANEXADO</span>
                                            <?php else: ?>
                                                <span class="status-badge warning">PENDENTE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if(!$is_res): ?>
                                                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&toggle_pendencia=<?= $p['id'] ?>" class="btn-icon" style="background:#fff3e0; color:#ef6c00; border:1px solid #ffe0b2; margin-right:5px;" title="Marcar como Resolvido">✅</a>
                                                <button onclick="openEditPendencia(<?= $p['id'] ?>, '<?= addslashes(str_replace(["\r", "\n"], '', $p['descricao'])) // Encode seguro para JS inline ?>')" class="btn-icon" style="background:#e3f2fd; color:#0d6efd; border:1px solid #d1e7dd; margin-right:5px;" title="Editar">✏️</button>
                                            <?php else: ?>
                                                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&toggle_pendencia=<?= $p['id'] ?>" class="btn-icon" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; margin-right:5px;" title="Reabrir Pendência">↩️</a>
                                            <?php endif; ?>
                                            
                                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&delete_pendencia=<?= $p['id'] ?>" onclick="confirmAction(event, 'Excluir esta pendência definitivamente?')" class="btn-icon" style="background:#f8d7da; color:#dc3545; border:1px solid #f5c6cb;" title="Excluir">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Novo Form de Inserção Rápida (MOVIDO PARA BAIXO) -->

                    
                </div>

                <!-- Modais Pendências -->
                <?php require 'includes/modals/pendencias.php'; ?>



            <?php elseif($active_tab == 'docs_iniciais'): ?>
                <!-- DOCUMENTOS INICIAIS CONTENT (Azul Moderno) -->
                <?php 
                    $docs_config = require 'config/docs_config.php';
                    $processos = $docs_config['processes'];
                    $todos_docs = $docs_config['document_registry'];
                    
                    $active_proc_key = $detalhes['tipo_processo_chave'] ?? '';
                    
                    // --- LÓGICA DE ATUALIZAÇÃO (Backend) ---
                    if(isset($_POST['update_docs_settings'])) {
                        // 1. Salvar Tipo de Processo e Observações
                        $new_proc = $_POST['tipo_processo_chave'];
                        
                        // Check if record exists
                        $check = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
                        $check->execute([$cliente_ativo['id']]);
                        
                        if($check->rowCount() > 0) {
                            $pdo->prepare("UPDATE processo_detalhes SET tipo_processo_chave = ?, observacoes_gerais = ? WHERE cliente_id = ?")->execute([$new_proc, $_POST['observacoes_gerais']??'', $cliente_ativo['id']]);
                        } else {
                            $pdo->prepare("INSERT INTO processo_detalhes (cliente_id, tipo_processo_chave, observacoes_gerais) VALUES (?, ?, ?)")->execute([$cliente_ativo['id'], $new_proc, $_POST['observacoes_gerais']??'']);
                        }
                        
                        // 2. Processar Ações nos Documentos (Aprovar/Rejeitar)
                        if(isset($_POST['action_doc'])) {
                            $act = $_POST['action_doc'];
                            $d_key = $_POST['doc_chave'];
                            
                            // Check existence
                            $chk = $pdo->prepare("SELECT id FROM processo_docs_entregues WHERE cliente_id = ? AND doc_chave = ?");
                            $chk->execute([$cliente_ativo['id'], $d_key]);
                            $exists = $chk->fetch();

                            if($act == 'approve') {
                                if($exists) {
                                    $pdo->prepare("UPDATE processo_docs_entregues SET status = 'aprovado' WHERE id = ?")->execute([$exists['id']]);
                                } else {
                                    // Aprovação manual sem arquivo
                                    $pdo->prepare("INSERT INTO processo_docs_entregues (cliente_id, doc_chave, status, data_entrega) VALUES (?, ?, 'aprovado', NOW())")->execute([$cliente_ativo['id'], $d_key]);
                                }
                            }
                            elseif($act == 'reopen') {
                                if($exists) {
                                    // Reabrir: volta para em_analise se tiver arquivo, senão deleta a aprovação manual
                                    $check_file = $pdo->prepare("SELECT arquivo_path FROM processo_docs_entregues WHERE id = ?");
                                    $check_file->execute([$exists['id']]);
                                    $has_file = $check_file->fetchColumn();

                                    if($has_file) {
                                        $pdo->prepare("UPDATE processo_docs_entregues SET status = 'em_analise' WHERE id = ?")->execute([$exists['id']]);
                                    } else {
                                        $pdo->prepare("DELETE FROM processo_docs_entregues WHERE id = ?")->execute([$exists['id']]);
                                    }
                                }
                            }
                            elseif($act == 'reject') {
                                // Rejeitar: remove registro (reset status p/ pendente no front)
                                if($exists) {
                                    $pdo->prepare("DELETE FROM processo_docs_entregues WHERE id = ?")->execute([$exists['id']]);
                                }
                            }
                        }
                        
                        // Refresh
                        echo "<script>window.location.href='?cliente_id={$cliente_ativo['id']}&tab=docs_iniciais&msg=saved';</script>";
                    }
                ?>

                <!-- ESTILOS ESPECÍFICOS DA ABA (VERDE HARMONIZADO) -->
                <style>
                    /* Ajuste para Verde Vilela (#198754 / #1e5d42) - COMPACTO */
                    .docs-header { background: #f8fffb; padding: 20px; border-radius: 10px; border: 1px solid #d1e7dd; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.05); }
                    
                    .proc-select { padding: 10px; font-size: 0.95rem; border: 2px solid #198754; border-radius: 6px; color: #0f5132; font-weight: 600; width: 100%; max-width: 500px; outline: none; background: white; cursor: pointer; transition: 0.2s; }
                    .proc-select:focus { box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.2); border-color: #146c43; }
                    
                    .section-title { font-size: 1rem; font-weight: 700; color: #1e5d42; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e9f5ef; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; grid-column: 1 / -1; margin-top: 10px; }
                    
                    .docs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
                    @media(max-width: 900px) { .docs-grid { grid-template-columns: 1fr; gap: 10px; } }

                    .doc-card-admin { display: flex; align-items: center; justify-content: space-between; background: white; border: 1px solid #eaeaea; padding: 12px 15px; border-radius: 10px; transition: all 0.2s; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
                    .doc-card-admin:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); border-color: #c3e6cb; }
                    
                    .dca-info { display: flex; align-items: center; gap: 12px; flex: 1; overflow: hidden; }
                    .dca-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
                    .dca-text { overflow: hidden; }
                    .dca-text h4 { margin: 0 0 3px 0; font-size: 0.9rem; color: #2c3e50; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                    .dca-text span { font-size: 0.75rem; color: #7f8c8d; }
                    
                    /* Compact File Link */
                    .dca-file { display: inline-flex; align-items: center; gap: 4px; background: #e8f5e9; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; color: #1e5d42; text-decoration: none; font-weight: 600; transition: 0.2s; white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis; border: 1px solid #c3e6cb; }
                    .dca-file:hover { background: #d1e7dd; color: #0f5132; }
                    
                    .dca-actions { display: flex; gap: 6px; }
                    .btn-act { border: none; width: 30px; height: 30px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
                    .btn-act:hover { transform: scale(1.1); filter: brightness(0.95); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                    
                    /* Status Colors */
                    .st-pendente { background: #f8f9fa; color: #aaa; border: 1px solid #eee; }
                    .st-analise  { background: #fff8e1; color: #b7791f; border: 1px solid #ffeeba; }
                    .st-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
                    .st-rejeitado{ background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
                </style>

                <div class="admin-tab-content" style="border-top: 4px solid #198754;">
                    
                    <form id="formDocsGlobal" method="POST">
                        <input type="hidden" name="update_docs_settings" value="1">
                        
                        <!-- HEADER CONFIG DO PROCESSO -->
                        <div class="admin-header-row">
                            <div>
                                <h3 class="admin-title" style="color:#198754;">📑 Checklist de Documentos</h3>
                                <p class="admin-subtitle">Gerencie o recebimento e aprovação de documentos do cliente.</p>
                            </div>
                            <button type="submit" class="btn-save" style="background:#198754; color:white; border:none; padding:8px 20px; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.2);">
                                💾 Salvar
                            </button>
                        </div>

                        <div class="docs-header">
                            <div style="margin-bottom: 15px;">
                                <label style="display:block; margin-bottom:5px; font-weight:700; color:#1e5d42; font-size:0.85rem;">TIPO DE PROCESSO:</label>
                                <select name="tipo_processo_chave" class="proc-select" onchange="this.form.submit()">
                                    <option value="">-- Selecione --</option>
                                    <?php foreach($processos as $key => $proc): ?>
                                        <option value="<?= $key ?>" <?= $active_proc_key == $key ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($proc['titulo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display:block; margin-bottom:5px; font-weight:700; color:#1e5d42; font-size:0.85rem;">OBSERVAÇÕES (CLIENTE):</label>
                                <textarea name="observacoes_gerais" class="admin-form-input" rows="1" placeholder="Ex: Documentos recebidos..." style="border: 1px solid #ced4da; border-radius: 6px; padding: 10px; font-size:0.9rem;"><?= htmlspecialchars($detalhes['observacoes_gerais'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </form>

                    <!-- LIST LISTING -->
                    <?php if($active_proc_key && isset($processos[$active_proc_key])): 
                        $proc_data = $processos[$active_proc_key];
                        
                        // Busca dados mapeados
                        $stmt_map = $pdo->prepare("SELECT doc_chave, arquivo_path, nome_original, data_entrega, status FROM processo_docs_entregues WHERE cliente_id = ?");
                        $stmt_map->execute([$cliente_ativo['id']]);
                        $entregues_map = [];
                        foreach($stmt_map->fetchAll(PDO::FETCH_ASSOC) as $row) {
                            $entregues_map[$row['doc_chave']] = $row;
                        }

                        // Função Helper Render Doc Card Compacto
                        function renderDocCard($label, $key, $entregues_map, $active_proc_key, $tipo_obrigatoriedade) {
                            global $pdo; 
                            $doc_data = $entregues_map[$key] ?? null;
                            $status = $doc_data['status'] ?? 'pendente';
                            $has_file = !empty($doc_data['arquivo_path']);
                            
                            // Default Colors
                            $color = '#adb5bd'; $icon = 'check_box_outline_blank'; $status_bg = '#f8f9fa'; $status_txt = 'Pendente';
                            
                            // Lógica Personalizada Solicitada
                            if ($status == 'pendente') {
                                if ($tipo_obrigatoriedade == 'obrigatorio') {
                                    // Obrigatório não entregue -> VERMELHO
                                    $color = '#dc3545'; 
                                    $icon = 'priority_high'; 
                                    $status_bg = '#f8d7da'; 
                                    $status_txt = 'Pendente (Obrigatório)';
                                } else {
                                    // Excepcional não entregue -> AMARELO
                                    $color = '#ffc107'; 
                                    $icon = 'warning'; 
                                    $status_bg = '#fff3cd'; 
                                    $status_txt = 'Pendente (Opcional)';
                                }
                            } else {
                                // Se tem status (em anlise, aprovado, rejeitado)
                                if($status == 'em_analise') {
                                    // Anexado (Em Análise) -> VERDE (Solicitado: "sempre q for annexado... transformar em verde")
                                    // Vamos usar um verde um pouco diferente do aprovado para diferenciar sutilmente, ou o mesmo.
                                    $color = '#198754'; 
                                    $icon = 'hourglass_top'; 
                                    $status_bg = '#e8f5e9'; // Verde bem claro
                                    $status_txt = 'Anexado / Em Análise';
                                }
                                elseif($status == 'aprovado') {
                                    $color = '#198754'; 
                                    $icon = 'check_circle'; 
                                    $status_bg = '#d1e7dd'; 
                                    $status_txt = 'Aprovado';
                                }
                                elseif($status == 'rejeitado') {
                                    $color = '#dc3545'; 
                                    $icon = 'error'; 
                                    $status_bg = '#f8d7da'; 
                                    $status_txt = 'Rejeitado';
                                }
                            }
                            
                            echo '<div class="doc-card-admin" style="border-left: 4px solid '.$color.';">';
                                
                                // Info
                                echo '<div class="dca-info">';
                                    echo '<div class="dca-icon" style="background:'.$status_bg.'; color:'.$color.';"><span class="material-symbols-rounded">'.$icon.'</span></div>';
                                    echo '<div class="dca-text">';
                                        echo '<h4 title="'.htmlspecialchars($label).'">'.htmlspecialchars($label).'</h4>';
                                        
                                        // Status Chip
                                        echo '<span style="background:'.$status_bg.'; color:'.$color.'; padding:1px 6px; border-radius:4px; font-weight:700; font-size:0.65rem; text-transform:uppercase;">'.$status_txt.'</span>';
                                        
                                        // Metadata (Date)
                                        if($has_file) {
                                           // Compact link next to status if needed, or just let the file link handle it
                                        }
                                    echo '</div>';
                                echo '</div>';

                                // Center: File Link (Compacto)
                                if($has_file) {
                                    echo '<a href="'.htmlspecialchars($doc_data['arquivo_path']).'" target="_blank" class="dca-file" title="'.$doc_data['nome_original'].'">
                                            <span class="material-symbols-rounded" style="font-size:0.9rem;">description</span> 
                                            Arquivo
                                          </a>';
                                }

                                // Actions
                                echo '<div class="dca-actions">';
                                    $common_hidden = '<input type="hidden" name="update_docs_settings" value="1"><input type="hidden" name="doc_chave" value="'.$key.'"><input type="hidden" name="tipo_processo_chave" value="'.$active_proc_key.'">';
                                    
                                    if($status == 'aprovado') {
                                        echo '<form method="POST">'.$common_hidden.'<input type="hidden" name="action_doc" value="reopen"><button type="submit" class="btn-act" style="background:#fff3cd; color:#856404;" title="Reabrir">↩️</button></form>';
                                    } else {
                                        echo '<form method="POST">'.$common_hidden.'<input type="hidden" name="action_doc" value="approve"><button type="submit" class="btn-act" style="background:#d1e7dd; color:#198754;" title="Aprovar">✅</button></form>';
                                        if($status != 'pendente' || $has_file) {
                                            echo '<form method="POST" onsubmit="return confirm(\'Limpar?\')">'.$common_hidden.'<input type="hidden" name="action_doc" value="reject"><button type="submit" class="btn-act" style="background:#f8d7da; color:#dc3545;" title="Rejeitar">🗑️</button></form>';
                                        }
                                    }
                                echo '</div>';
                            echo '</div>';
                        }
                    ?>

                    <div class="docs-grid"> <!-- GRID CONTAINER -->
                        
                        <!-- OBRIGATÓRIOS (Full Width section title) -->
                        <div class="section-title">
                            <span style="background:#e8f5e9; color:#198754; padding:3px 6px; border-radius:4px; font-size:0.9rem;">📋</span> 
                            OBRIGATÓRIOS
                        </div>
                        <?php foreach($proc_data['docs_obrigatorios'] as $doc_key): 
                            $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                            renderDocCard($doc_label, $doc_key, $entregues_map, $active_proc_key, 'obrigatorio');
                        endforeach; ?>
                        
                        <!-- EXCEPCIONAIS (Se houver) -->
                        <?php if(!empty($proc_data['docs_excepcionais'])): ?>
                            <div class="section-title" style="margin-top:15px; border-bottom-color:#fff3cd;">
                                <span style="background:#fff3cd; color:#856404; padding:3px 6px; border-radius:4px; font-size:0.9rem;">⚠️</span> 
                                EXCEPCIONAIS
                            </div>
                            <!-- Spacer para grid alignment se precisar, ou apenas renderizar direto -->
                            <div style="grid-column: 1 / -1; display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                                <?php foreach($proc_data['docs_excepcionais'] as $doc_key): 
                                    $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                                    renderDocCard($doc_label, $doc_key, $entregues_map, $active_proc_key, 'excepcional');
                                endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <?php else: ?>
                        <div style="text-align:center; padding: 40px 20px; color:#999;">
                            <span style="font-size:3rem; display:block; margin-bottom:10px; opacity:0.5;">👆</span>
                            <h3 style="color:#666; font-size:1.1rem;">Selecione o Tipo de Processo</h3>
                        </div>
                    <?php endif; ?>

                </div>
            <?php elseif($active_tab == 'arquivos'): ?>
                
                <!-- ARQUIVOS CONTENT (Verde Vilela) -->
                <div class="admin-tab-content">
                    <div class="admin-header-row">
                        <div>
                            <h3 class="admin-title">📂 Arquivos do Cliente</h3>
                            <p class="admin-subtitle">Central de links e pastas do Google Drive.</p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="admin-form-group">
                            <label class="admin-form-label">🔗 Link da Pasta Geral (Backup/Drive)</label>
                            <input type="text" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" class="admin-form-input" placeholder="https://drive.google.com/...">
                        </div>

                        <button type="submit" name="btn_salvar_arquivos" class="btn-save" style="background:#198754; color:white; border:none; box-shadow:0 4px 10px rgba(25, 135, 84, 0.3);">Salvar Links</button>
                    </form>

                    <?php 
                    if(!empty($detalhes['link_drive_pasta'])): 
                        // Tenta extrair o ID da pasta para formato embed correto
                        $drive_url = $detalhes['link_drive_pasta'];
                        $embed_url = $drive_url; // fallback
                        
                        // Padrão: /folders/ID ou ?id=ID
                        if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                            $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
                        } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                             $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
                        }
                    ?>
                        <div class="iframe-container visible" style="display:block;">
                            <!-- Aviso sobre permissões -->
                            <div style="background:#e3f2fd; color:#0d47a1; padding:10px; font-size:0.85rem; text-align:center; border-bottom:1px solid #bbdefb;">
                                💡 Se aparecer erro 403/Recusado, verifique se a conta atual tem permissão na pasta.
                            </div>
                            <iframe src="<?= htmlspecialchars($embed_url) ?>" width="100%" height="100%" frameborder="0" style="border:0;"></iframe>
                        </div>
                    <?php endif; ?>
                </div>


            <?php elseif($active_tab == 'financeiro'): ?>

                <!-- FINANCEIRO CONTENT (Verde) -->
                <div class="admin-tab-content">
                <!-- Header e Botão Novo Lançamento -->
                <div class="admin-header-row">
                    <div>
                        <h3 class="admin-title">💰 Fluxo Financeiro</h3>
                        <p class="admin-subtitle">Gerencie honorários, taxas e despesas do processo.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalFinanceiro').showModal()" style="background:linear-gradient(135deg, #198754, #146c43); color:white; border:none; padding:12px 25px; border-radius:30px; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 15px rgba(25, 135, 84, 0.3); transition:all 0.2s;">
                        <span style="font-size:1.2rem;">➕</span> Novo Lançamento
                    </button>
                </div>

                <!-- Modais Financeiros -->
                <?php require 'includes/modals/financeiro.php'; ?>
                
                <!-- Modais Widgets Sidebar -->
                <?php require 'includes/modals/sidebar_widgets.php'; ?>

                <!-- Tabelas -->
                <?php 
                try {
                    // Verifica se tabela existe (silencioso) ou só roda
                    $fin_honorarios = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='honorarios' ORDER BY data_vencimento ASC");
                    $fin_honorarios->execute([$cliente_ativo['id']]);
                    
                    $fin_taxas = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='taxas' ORDER BY data_vencimento ASC");
                    $fin_taxas->execute([$cliente_ativo['id']]);

                    renderFinTable($fin_honorarios, "💰 Honorários e Serviços (Vilela Engenharia)", "#198754", $cliente_ativo['id']);
                    renderFinTable($fin_taxas, "🏛️ Taxas e Multas Governamentais", "#efb524", $cliente_ativo['id']);

                } catch (Exception $e) {
                    echo "<div style='color:red'>Erro ao carregar dados financeiros. Verifique se o Setup de Banco de Dados foi rodado. <br>". $e->getMessage() ."</div>";
                }
                ?>
                </div>

            <?php endif; ?>
            
            </div> <!-- End of Colored Window Wrapper -->
        
        <?php else: ?>
            
            <!-- DASHBOARD GERAL (Visão do Gestor) -->
            <div style="margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <h2 style="color:var(--color-primary); margin-bottom:5px;">Visão Geral do Escritório</h2>
                    <p style="color:var(--color-text-subtle);">Resumo de atividades e indicadores de performance.</p>
                </div>
            </div>

            <!-- KPI Cards Compactos -->
            <style>
                .kpi-grid-compact {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 15px;
                    margin-bottom: 30px;
                }
                .kpi-card-compact {
                    background: var(--color-surface); 
                    border: 1px solid var(--color-border);
                    border-radius: 12px;
                    padding: 15px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
                    transition: transform 0.2s;
                }
                .kpi-card-compact:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
                .kpi-icon-box {
                    width: 48px; height: 48px;
                    border-radius: 10px;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 1.5rem;
                    flex-shrink: 0;
                }
                .kpi-content div:first-child { font-size: 1.4rem; font-weight: 800; line-height: 1; margin-bottom: 2px; }
                .kpi-content div:last-child { font-size: 0.85rem; color: var(--color-text-subtle); font-weight: 600; line-height: 1.2; }
            </style>

            <div class="kpi-grid-compact">
                <!-- 1. Clientes -->
                <div class="kpi-card-compact">
                    <div class="kpi-icon-box" style="background:#e3f2fd; color:#2196f3;">👥</div>
                    <div class="kpi-content">
                        <div style="color:#2196f3;"><?= $kpi_total_clientes ?></div>
                        <div>Clientes Ativos</div>
                    </div>
                </div>

                <!-- 2. Obras -->
                <div class="kpi-card-compact">
                    <div class="kpi-icon-box" style="background:#fff3cd; color:#ffc107;">🏗️</div>
                    <div class="kpi-content">
                        <div style="color:#ffc107;"><?= $kpi_proc_ativos ?></div>
                        <div>Obras/Processos</div>
                    </div>
                </div>

                <!-- 3. Solicitações -->
                <div class="kpi-card-compact" style="cursor: pointer;" onclick="if(<?= $kpi_pre_pendentes ?> > 0) window.location.href='?importar=true'">
                    <div class="kpi-icon-box" style="background:#f8d7da; color:#dc3545;">📥</div>
                    <div class="kpi-content">
                        <div style="color:#dc3545;"><?= $kpi_pre_pendentes ?></div>
                        <div>Novos Pedidos</div>
                    </div>
                </div>

                <!-- 4. Recebíveis (Futuro) -->
                <div class="kpi-card-compact">
                    <div class="kpi-icon-box" style="background:#d1e7dd; color:#198754;">💰</div>
                    <div class="kpi-content">
                        <div style="color:#198754; font-size:1.1rem;"><?= number_format($kpi_fin_pendente ?? 0, 2, ',', '.') ?></div>
                        <div>A Receber (Futuro)</div>
                    </div>
                </div>
                
                <!-- 5. Atrasados (Alerta) - Só aparece se tiver -->
                <?php if(($kpi_fin_atrasado ?? 0) > 0): ?>
                <div class="kpi-card-compact" style="border-color:#dc3545;">
                    <div class="kpi-icon-box" style="background:#dc3545; color:white;">⚠️</div>
                    <div class="kpi-content">
                        <div style="color:#dc3545; font-size:1.1rem;"><?= number_format($kpi_fin_atrasado ?? 0, 2, ',', '.') ?></div>
                        <div>EM ATRASO</div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Tabela Geral de Clientes -->
            <div class="form-card">
                <h3>📋 Situação da Carteira de Clientes</h3>
                <div class="table-responsive">
                    <table style="width:100%; border-collapse:collapse; margin-top:15px;">
                        <thead>
                            <tr style="background:#f8f9fa; border-bottom:2px solid #ddd;">
                                <th style="padding:12px; text-align:left;">Cliente</th>
                                <th style="padding:12px; text-align:left;">Fase Atual</th>
                                <th style="padding:12px; text-align:left;">Contato</th>
                                <th style="padding:12px; text-align:center;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $c): 
                                // Busca detalhes rápidos (poderia ser otimizado com JOIN, mas mantendo simples)
                                $dt = $pdo->query("SELECT etapa_atual, contato_tel FROM processo_detalhes WHERE cliente_id={$c['id']}")->fetch();
                                $etapa = $dt['etapa_atual'] ?? '<span style="color:#ccc; font-style:italic;">Não iniciado</span>';
                                $tel = $dt['contato_tel'] ?? '--';
                            ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px; font-weight:bold; color:var(--color-primary);"><?= htmlspecialchars($c['nome']) ?></td>
                                <td style="padding:12px;"><?= $etapa ?></td>
                                <td style="padding:12px;"><?= $tel ?></td>
                                <td style="padding:12px; text-align:center;">
                                    <a href="?cliente_id=<?= $c['id'] ?>" class="btn-save btn-info" style="padding:5px 10px; font-size:0.85rem; text-decoration:none;">Gerenciar ➡️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php endif; ?>
    </main>
</div>
</body>
    <!-- Global Modals -->
    <?php require 'includes/modals/geral.php'; ?>
    <script>
        // Check URL for success messages
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            if(msg === 'pendencia_emitted') {
                showSuccessModal('Pendência Emitida!', 'A pendência foi publicada na lista e o quadro foi limpo com sucesso.');
            } else if (msg === 'pendencia_updated') {
                showSuccessModal('Pendência Atualizada!', 'As alterações foram salvas com sucesso.');
            } else if (msg === 'hist_deleted') {
                showSuccessModal('Histórico Apagado!', 'O item de histórico foi removido com sucesso.');
            }
            
            // Clean URL
            if(msg) {
                const newUrl = window.location.pathname + window.location.search.replace(/&?msg=[^&]*/, '');
                window.history.replaceState({}, document.title, newUrl);
            }
        });

        function showSuccessModal(title, text) {
            document.getElementById('successModalTitle').innerText = title;
            document.getElementById('successModalText').innerText = text;
            document.getElementById('successModal').style.display = 'flex';
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }
        
        // Toggle Sidebar Logic
        function toggleSidebar() {
            document.getElementById('mobileSidebar').classList.toggle('show');
        }
    </script>


<script>
// --- MÁSCARAS E VALIDAÇÃO ---
document.addEventListener('DOMContentLoaded', function() {
    const phoneInputs = document.querySelectorAll('input[name="telefone"], input[name="contato_tel"]');
    const cpfCnpjInputs = document.querySelectorAll('input[name="cpf_cnpj"]');
    
    // Mask Phone: (XX) XXXXX-XXXX
    phoneInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
        
        input.addEventListener('blur', function(e) {
            const val = e.target.value.replace(/\D/g, '');
            if(val.length > 0 && val.length < 10) {
                alert('⚠️ Número de telefone parece incompleto. Verifique se incluiu o DDD.');
                e.target.style.borderColor = '#dc3545';
            } else {
                e.target.style.borderColor = '';
            }
        });
    });

    // Mask CPF/CNPJ
    cpfCnpjInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 14) v = v.slice(0, 14); // Limit to CNPJ size

            if (v.length <= 11) { // CPF Mask
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                
            } else { // CNPJ Mask
                v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
                v = v.replace(/(\d{4})(\d)/, '$1-$2');
            }
            e.target.value = v;
        });

        input.addEventListener('blur', function(e) {
            const val = e.target.value.replace(/\D/g, '');
            if(val.length > 0 && val.length !== 11 && val.length !== 14) {
                alert('⚠️ CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos.');
                e.target.style.borderColor = '#dc3545';
            } else {
                e.target.style.borderColor = '';
            }
        });
    });
});
</script>

<!-- Modal Aprovar Removed (Included in cadastro.php) -->
    <!-- FLOATING ACTION BUTTONS (External Links - Circular & Discrete) -->
    <div style="position:fixed; bottom:25px; right:25px; display:flex; flex-direction:column; gap:12px; z-index:9999; align-items:center;">
        
        <!-- Botão Matrícula (Cinza Escuro/Discreto) -->
        <a href="https://ridigital.org.br/VisualizarMatricula/DefaultVM.aspx?from=menu" target="_blank" 
           title="Acessar Matrícula"
           style="display:flex; align-items:center; justify-content:center; width:48px; height:48px; background:#495057; color:white; border-radius:50%; text-decoration:none; box-shadow:0 4px 10px rgba(0,0,0,0.2); transition:all 0.2s;"
           onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 15px rgba(0,0,0,0.25)';" 
           onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 10px rgba(0,0,0,0.2)';">
            <span class="material-symbols-rounded" style="font-size:1.4rem;">description</span>
        </a>

        <!-- Botão IPM Prefeitura (Azul/Discreto) -->
        <a href="https://oliveira.atende.net/atendenet?source=pwa" target="_blank" 
           title="Acessar IPM Prefeitura"
           style="display:flex; align-items:center; justify-content:center; width:48px; height:48px; background:#0d6efd; color:white; border-radius:50%; text-decoration:none; box-shadow:0 4px 10px rgba(13, 110, 253, 0.3); transition:all 0.2s;"
           onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 15px rgba(13, 110, 253, 0.4)';" 
           onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 10px rgba(13, 110, 253, 0.3)';">
            <span class="material-symbols-rounded" style="font-size:1.4rem;">account_balance</span>
        </a>

    </div>

</body>
</html>
