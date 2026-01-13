<?php
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
    <?php require 'includes/ui/header.php'; ?>

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
            <?php
            // Lógica de Upload de Avatar
            if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == 0) {
                $ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if(in_array($ext, $allowed)) {
                    $new_name = 'avatar_' . $cliente_ativo['id'] . '.' . $ext;
                    $upload_path = 'uploads/avatars/' . $new_name;
                    if(!is_dir('uploads/avatars/')) mkdir('uploads/avatars/', 0755, true);
                    
                    if(move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $upload_path)) {
                        // Salvar caminho no banco (assumindo coluna 'foto_perfil' ou criando lógica de arquivo)
                         try {
                            // Tenta atualizar se a coluna existir, senão o arquivo já vale pelo nome
                            // Mas para garantir cache busting, ideal seria salvar. 
                            // Como não tenho certeza da coluna, vou usar o arquivo físico como referência
                            // e forçar reload.
                            echo "<script>window.location.href='?cliente_id={$cliente_ativo['id']}&tab=andamento&avatar_updated=1';</script>";
                         } catch(Exception $e) {}
                    }
                }
            }
            
            // Verifica se existe avatar
            $avatar_file = glob("uploads/avatars/avatar_{$cliente_ativo['id']}.*");
            $avatar_url = !empty($avatar_file) ? $avatar_file[0] . '?v=' . time() : null;
            ?>

            <!-- Card Resumo do Cliente (Grid Layout: Info + Timelime) -->
            <div class="form-card" style="margin-bottom:20px; border-left:5px solid var(--color-primary); background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); padding:0;">
                
                <div style="display:grid; grid-template-columns: 1fr 300px; gap:0;">
                    
                    <!-- ESQUERDA: Info do Cliente -->
                    <div style="padding:25px; display:flex; align-items:flex-start; gap:20px;">
                        
                        <!-- Avatar / Iniciais (Com Upload) -->
                        <div style="position:relative; width:80px; height:80px; min-width:80px; cursor:pointer;" onclick="document.getElementById('avatar_input').click();" title="Clique para alterar a foto">
                            <?php if($avatar_url): ?>
                                <img src="<?= $avatar_url ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%; border:3px solid var(--color-primary-light);">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:var(--color-primary-light); color:var(--color-primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:800; border:2px solid white; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                                    <?= strtoupper(substr($cliente_ativo['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="position:absolute; bottom:0; right:0; background:var(--color-primary); color:white; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; border:2px solid white;">📷</div>
                        </div>
                        
                        <!-- Form invisível para upload -->
                        </form> <!-- Fechamento do form global (ajuste se necessário, mas mantendo estrutura original) -->

                        <div class="client-summary-card" style="flex:1;">
                            <h2 style="margin:0 0 5px 0; font-size:1.6rem; color:var(--color-text);"><?= htmlspecialchars($cliente_ativo['nome']) ?></h2>
                            
                            <div style="display:flex; flex-wrap:wrap; gap:15px; font-size:0.9rem; color:#666; margin-bottom:15px;">
                                <span style="background:#f8f9fa; padding:2px 8px; border-radius:6px; border:1px solid #e9ecef;">🆔 #<?= str_pad($cliente_ativo['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                <span style="background:#f8f9fa; padding:2px 8px; border-radius:6px; border:1px solid #e9ecef;">📱 <?= $detalhes['contato_tel'] ?? '--' ?></span>
                                <span style="background:#f8f9fa; padding:2px 8px; border-radius:6px; border:1px solid #e9ecef;">📄 <?= $detalhes['cpf_cnpj'] ?? '--' ?></span>
                            </div>

                            <!-- NEW: Process Info & Address -->
                            <div style="margin-top:12px; font-size:0.9rem; color:#444; display:flex; flex-direction:column; gap:6px;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="material-symbols-rounded" style="font-size:1.1rem; color:#6f42c1;">folder_open</span>
                                    <span style="font-weight:600;"><?= !empty($detalhes['tipo_servico']) ? htmlspecialchars($detalhes['tipo_servico']) : 'Tipo de Processo não informado' ?></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px; color:#666;">
                                    <span class="material-symbols-rounded" style="font-size:1.1rem; color:#dc3545;">location_on</span>
                                    <span><?= !empty($detalhes['endereco_imovel']) ? htmlspecialchars($detalhes['endereco_imovel']) : 'Endereço da obra não informado' ?></span>
                                </div>
                            </div>
                            
                            <div style="display:flex; gap:10px; font-size:0.9rem; align-items:center; margin-top:10px;">
                                <a href="gerenciar_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="btn-save" style="background:var(--color-primary-light); color:var(--color-primary); border:none; padding:5px 12px; font-size:0.8rem; box-shadow:none;">✏️ Editar Cadastro</a>
                                <a href="relatorio_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="btn-save" style="background:#e2e6ea; color:#444; border:none; padding:5px 12px; font-size:0.8rem; box-shadow:none;">⚠️ Resumo PDF</a>
                                <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" class="btn-delete-confirm btn-save" data-confirm-text="Excluir cliente?" style="background:#dc3545; color:white; border:none; padding:5px 12px; font-size:0.8rem; box-shadow:none;">🗑️ Excluir</a>
                            </div>
                        </div>
                    </div>

                    <!-- DIREITA: Timeline Compacta (Gráfico Pizza/Donut) -->
                    <div style="background:#fff; border-left:1px solid #eee; padding:20px; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; min-width:280px;">
                        
                        <?php 
                        $found_idx = array_search(($detalhes['etapa_atual']??''), $fases_padrao);
                        if($found_idx === false) $found_idx = -1;
                        
                        // Calc Percentage
                        $total_phases = count($fases_padrao);
                        $percent = ($found_idx >= 0) ? round((($found_idx + 1) / $total_phases) * 100) : 0;
                        ?>

                        <!-- Donut Chart -->
                        <div style="position:relative; width:100px; height:100px; border-radius:50%; background:conic-gradient(var(--color-primary) <?= $percent ?>%, #eee <?= $percent ?>% 100%); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            <div style="width:75px; height:75px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                                <span style="font-size:1.2rem; font-weight:800; color:var(--color-primary);"><?= $percent ?>%</span>
                            </div>
                        </div>

                        <div style="margin-top:10px; text-align:center;">
                            <h4 style="margin:0 0 8px 0; font-size:1rem; color:var(--color-primary); font-weight:700; max-width:220px; line-height:1.3;">
                                <?= ($found_idx >= 0) ? htmlspecialchars($fases_padrao[$found_idx]) : 'Não iniciado' ?>
                            </h4>
                            
                            <button onclick="document.getElementById('modalTimelineFull').showModal()" style="margin-top:10px; background:none; border:1px solid #ddd; color:#666; padding:5px 12px; border-radius:20px; font-size:0.75rem; cursor:pointer;">
                                Ver Etapas 📋
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB NAVIGATION -->
            <!-- Styles for Tabs (Multi-Color) -->
            <style>
                .tabs-container {
                    margin-bottom: 0; /* Remove margin to connect with window below */
                    border-bottom: none; 
                    display: flex;
                    gap: 10px;
                    overflow-x: auto;
                    padding: 10px 5px 0 5px; /* Bottom padding removed to sit on line */
                    align-items: flex-end; /* Align tabs to bottom */
                }
                .tab-link {
                    padding: 10px 20px;
                    text-decoration: none;
                    color: #666;
                    font-weight: 700;
                    font-size: 0.95rem;
                    border-radius: 12px 12px 0 0; /* Top rounded only */
                    transition: all 0.2s;
                    white-space: nowrap;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: #f8f9fa;
                    border: 1px solid #e0e0e0;
                    border-bottom: none;
                    opacity: 0.8;
                    margin-bottom: -1px; /* Overlap border */
                    position: relative;
                    z-index: 1;
                }
                .tab-link:hover {
                    opacity: 1;
                    transform: translateY(-2px);
                }
                .tab-link.active {
                    opacity: 1;
                    background: #fff;
                    color: #fff !important; /* Will be overridden by specific colors if needed, but lets use background */
                    border: none;
                    padding-top: 14px; /* Pop up effect */
                    box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
                    z-index: 10;
                }
                
                /* UNIFORM COLOR (Vilela Green) for all tabs */
                .tab-link.active {
                    background: #146c43 !important; /* Brand Green */
                    color: white !important;
                    box-shadow: 0 4px 15px rgba(20, 108, 67, 0.4);
                }
                .tab-link.active span { color: white; }
                
                /* Remove individual colors but keep classes for targeting if needed later */
                .tab-link.t-hist.active, 
                .tab-link.t-pend.active, 
                .tab-link.t-fin.active, 
                .tab-link.t-arq.active {
                    background: #146c43;
                }
            </style>
            
            <div class="tabs-container">
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="tab-link t-hist <?= ($active_tab=='andamento'||$active_tab=='cadastro')?'active':'' ?>">
                    <span>📜</span> Histórico
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="tab-link t-pend <?= ($active_tab=='pendencias')?'active':'' ?>">
                    <span>⚠️</span> Pendências
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="tab-link t-fin <?= ($active_tab=='financeiro')?'active':'' ?>">
                    <span>💰</span> Financeiro
                </a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=arquivos" class="tab-link t-arq <?= ($active_tab=='arquivos')?'active':'' ?>">
                    <span>📂</span> Arquivos
                </a>
            </div>

            <!-- Modal Timeline e Andamento -->
            <?php require 'includes/modals/timeline.php'; ?>
            
            <!-- WINDOW CONTENT CONTAINER -->
            <?php 
                // Define window color based on active tab (Unified Green)
                $win_border_color = '#146c43';
            ?>

            <div style="background:#fff; border-top: 4px solid <?= $win_border_color ?>; border-radius: 0 0 12px 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px;">

            <!-- Script removed as logic is now backend-driven -->

            <?php if($active_tab == 'cadastro' || $active_tab == 'andamento'): ?>
                <div class="admin-tab-content">
                    <!-- Unified Header with Actions -->
                    <div class="admin-header-row">
                        <div>
                            <h3 class="admin-title">📜 Histórico Completo do Processo</h3>
                            <p class="admin-subtitle">Registre aqui todos os passos e comunicações.</p>
                        </div>
                        
                        <div style="display:flex; gap:10px; align-items:center;">
                             <!-- Botão Novo Andamento (Integrado) -->
                            <button type="button" onclick="document.getElementById('modalAndamento').showModal()" style="padding:10px 20px; background:linear-gradient(135deg, #198754, #146c43); border:none; border-radius:30px; font-size:0.9rem; font-weight:700; color:white; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; box-shadow:0 3px 6px rgba(25, 135, 84, 0.3);">
                                <span style="font-size:1.2rem;">✨</span> Novo Andamento
                            </button>
                            
 
 
                             <!-- Botão Apagar Histórico (Perigo) -->
                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento&del_all_hist=true" 
                               onclick="return confirm('ATENÇÃO EXTREMA: \n\nVocê está prestes a APAGAR TODO O HISTÓRICO deste processo.\n\nIsso limpará todas as movimentações, datas e logs.\n\nTem certeza absoluta que deseja fazer isso?');"
                               style="background:#f8f9fa; color:#6c757d; padding:10px 15px; border:1px solid #dee2e6; border-radius:30px; font-size:0.8rem; text-decoration:none; font-weight:700; display:flex; align-items:center; gap:5px; margin-left:10px;" title="Limpar Tudo">
                               ⏱️ Limpar
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
                                                // Mas previne XSS grosseiro se quiser, porem aqui confiamos no admin.
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
                                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento&del_hist=<?= $h['id'] ?>" onclick="confirmAction(event, 'ATENÇÃO: Deseja realmente apagar este histórico? Essa ação é irreversível.')" style="text-decoration:none; color:#dc3545; font-size:1.1rem; padding:5px;" title="Excluir Histórico">🗑️</a>
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
</body>
</html>
