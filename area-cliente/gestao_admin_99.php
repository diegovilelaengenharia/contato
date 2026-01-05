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
    echo "<div class='form-card' style='border-left: 6px solid $color;'>
            <h3 style='color:$color;'>$title</h3>";
    
    if(count($rows) == 0) {
        echo "<p style='color:#666; font-style:italic;'>Nenhum lançamento encontrado nesta categoria.</p>";
    } else {
        echo "<div class='table-responsive'>
              <table style='width:100%; border-collapse:collapse; font-size:0.95rem; min-width:600px;'>
                <thead><tr style='background:#f8f9fa; border-bottom:2px solid #dee2e6;'>
                    <th style='padding:12px; text-align:left;'>Descrição</th>
                    <th style='padding:12px; text-align:left;'>Valor</th>
                    <th style='padding:12px; text-align:left;'>Vencimento</th>
                    <th style='padding:12px; text-align:center;'>Status</th>
                    <th style='padding:12px; text-align:center;'>Ação</th>
                    <th style='padding:12px;'></th>
                </tr></thead><tbody>";
        foreach($rows as $r) {
            $st_color = 'black';
            $st_icon = '';
            switch($r['status']){
                case 'pago': $st_color='#198754'; $st_icon='✅ Pago'; break;
                case 'pendente': $st_color='#ffc107'; $st_icon='⏳ Pendente'; break;
                case 'atrasado': $st_color='#dc3545'; $st_icon='❌ Atrasado'; break;
                case 'isento': $st_color='#6c757d'; $st_icon='⚪ Isento'; break;
                default: $st_icon=$r['status'];
            }
            $valor = number_format($r['valor'], 2, ',', '.');
            $data = date('d/m/Y', strtotime($r['data_vencimento']));
            $link = $r['link_comprovante'] ? "<a href='{$r['link_comprovante']}' target='_blank' style='color:white; background:#0d6efd; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;'>📄 Ver Doc</a>" : "<span style='opacity:0.5'>--</span>";
            
            echo "<tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:12px;'>{$r['descricao']}</td>
                    <td style='padding:12px; font-weight:bold;'>R$ {$valor}</td>
                    <td style='padding:12px;'>{$data}</td>
                    <td style='padding:12px; text-align:center;'>
                        <button onclick=\"openStatusFinModal({$r['id']}, '{$r['status']}')\" style=\"background:none; border:1px solid {$st_color}; color:{$st_color}; border-radius:12px; padding:2px 8px; font-weight:bold; cursor:pointer; font-size:0.85rem;\" title=\"Alterar Status\">
                            {$st_icon} ✏️
                        </button>
                    </td>
                    <td style='padding:12px; text-align:center;'>{$link}</td>
                    <td style='padding:12px; text-align:right;'>
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
            <?php if(!isset($_GET['cliente_id']) && !isset($_GET['novo']) && !isset($_GET['importar'])): ?>
                <!-- Widgets removed per user request -->
            <?php endif; ?>

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
            <div class="form-card">
                <h2>Cadastrar Novo Cliente</h2>
                <p style="color:#666; font-size:0.9rem; margin-bottom:20px;">Preencha os dados básicos. O restante será completado na tela de edição.</p>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group"><label>Nome Completo</label><input type="text" name="nome" required placeholder="Ex: João da Silva"></div>
                        <div class="form-group"><label>Senha de Acesso</label><input type="text" name="senha" required placeholder="Crie uma senha inicial"></div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom:20px;">
                        <label>📸 Foto de Perfil (Opcional)</label>
                        <input type="file" name="avatar_upload" accept="image/*" style="padding:10px; border:1px solid #ddd; width:100%; border-radius:8px;">
                    </div>
                    
                    <div style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #e9ecef; margin:20px 0;">
                        <label style="display:block; margin-bottom:10px; font-weight:bold; color:var(--color-primary);">Definir Login Automático por:</label>
                        <div style="display:flex; gap:20px; margin-bottom:15px;">
                            <label style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <input type="radio" name="tipo_login" value="cpf" checked onclick="document.getElementById('req_cpf').innerText='*'; document.getElementById('req_tel').innerText='';"> 
                                CPF (Recomendado)
                            </label>
                            <label style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <input type="radio" name="tipo_login" value="telefone" onclick="document.getElementById('req_cpf').innerText=''; document.getElementById('req_tel').innerText='*';"> 
                                Telefone (Celular)
                            </label>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>CPF / CNPJ <span id="req_cpf" style="color:red">*</span></label>
                                <input type="text" name="cpf_cnpj" placeholder="Apenas números">
                            </div>
                            <div class="form-group">
                                <label>Telefone <span id="req_tel" style="color:red"></span></label>
                                <input type="text" name="telefone" placeholder="(XX) XXXXX-XXXX">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label>Email (Opcional)</label>
                                <input type="email" name="email" placeholder="cliente@email.com">
                            </div>
                        </div>
                        
                        <script>
                        // Masks for Pre-Cadastro
                        document.querySelector('input[name="cpf_cnpj"]').addEventListener('input', e => {
                            let v = e.target.value.replace(/\D/g, "");
                            if (v.length <= 11) {
                                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                                v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                            } else {
                                v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                                v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                                v = v.replace(/(\d{4})(\d)/, "$1-$2");
                            }
                            e.target.value = v;
                        });
                        document.querySelector('input[name="telefone"]').addEventListener('input', e => {
                            let v = e.target.value.replace(/\D/g, "");
                            v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                            v = v.replace(/(\d)(\d{4})$/, "$1-$2");
                            e.target.value = v;
                        });
                        </script>
                    </div>

                    <button type="submit" name="novo_cliente" class="btn-save">Criar Cliente e Editar ➡️</button>
                </form>
            </div>

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
                                <a href="editar_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="btn-save" style="background:var(--color-primary-light); color:var(--color-primary); border:none; padding:5px 12px; font-size:0.8rem; box-shadow:none;">✏️ Editar Cadastro</a>
                                <a href="relatorio_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="btn-save" style="background:#e2e6ea; color:#444; border:none; padding:5px 12px; font-size:0.8rem; box-shadow:none;">⚠️ Resumo PDF</a>
                                <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" class="btn-delete-confirm" data-confirm-text="Excluir cliente?" style="color:#dc3545; text-decoration:none; font-weight:700; font-size:0.8rem; margin-left:10px;">🗑️ Excluir</a>
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
                
                /* 1. HISTÓRICO (Roxo Claro) */
                .tab-link.t-hist.active { background: #9d4edd; color: white !important; box-shadow: 0 4px 15px rgba(157, 78, 221, 0.4); }
                .tab-link.t-hist.active span { color: white; }
                
                /* 2. PENDÊNCIAS (Laranja Claro) */
                .tab-link.t-pend.active { background: #ffb74d; color: white !important; box-shadow: 0 4px 15px rgba(255, 183, 77, 0.4); }
                .tab-link.t-pend.active span { color: white; }

                /* 3. FINANCEIRO (Verde Claro) */
                .tab-link.t-fin.active { background: #66bb6a; color: white !important; box-shadow: 0 4px 15px rgba(102, 187, 106, 0.4); }
                .tab-link.t-fin.active span { color: white; }

                /* 4. ARQUIVOS (Azul Claro) */
                .tab-link.t-arq.active { background: #4dabf5; color: white !important; box-shadow: 0 4px 15px rgba(77, 171, 245, 0.4); }
                .tab-link.t-arq.active span { color: white; }
                
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
                // Define window color based on active tab (Matched Lighter Colors)
                $win_border_color = '#ccc';
                if($active_tab=='andamento'||$active_tab=='cadastro') $win_border_color = '#9d4edd'; 
                elseif($active_tab=='pendencias') $win_border_color = '#ffb74d'; 
                elseif($active_tab=='financeiro') $win_border_color = '#66bb6a'; 
                elseif($active_tab=='arquivos') $win_border_color = '#4dabf5'; 
            ?>

            <div style="background:#fff; border-top: 4px solid <?= $win_border_color ?>; border-radius: 0 0 12px 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px;">

            <!-- Script removed as logic is now backend-driven -->

            <?php if($active_tab == 'cadastro' || $active_tab == 'andamento'): ?>
                <div class=""> <!-- Removed inner card style since outer wrapper handles it -->
                    <!-- Unified Header with Actions -->
                    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:20px; gap:15px; border-bottom:1px solid #eee; padding-bottom:15px;">
                        <div>
                            <h3 style="margin:0; font-size:1.3rem; color: #6610f2;">📜 Histórico Completo do Processo</h3>
                            <p style="margin:5px 0 0 0; color:#888; font-size:0.9rem;">Registre aqui todos os passos e comunicações.</p>
                        </div>
                        
                        <div style="display:flex; gap:10px; align-items:center;">
                             <!-- Botão Novo Andamento (Integrado) -->
                            <button type="button" onclick="document.getElementById('modalAndamento').showModal()" style="padding:10px 20px; background:linear-gradient(135deg, #6610f2, #8540f5); border:none; border-radius:30px; font-size:0.9rem; font-weight:700; color:white; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; box-shadow:0 3px 6px rgba(102, 16, 242, 0.3);">
                                <span style="font-size:1.2rem;">✨</span> Novo Andamento
                            </button>
                            
 
 
                             <!-- Botão Apagar Histórico (Perigo) -->
                            <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento&del_all_hist=true" 
                               onclick="return confirm('ATENÇÃO EXTREMA: \n\nVocê está prestes a APAGAR TODO O HISTÓRICO deste processo.\n\nIsso limpará todas as movimentações, datas e logs.\n\nTem certeza absoluta que deseja fazer isso?');"
                               style="background:#fff0f3; color:#dc3545; padding:10px 15px; border:1px solid #ffdee6; border-radius:30px; font-size:0.8rem; text-decoration:none; font-weight:700; display:flex; align-items:center; gap:5px; margin-left:10px;" title="Limpar Tudo">
                               ⏱️ Limpar
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead><tr style="background:rgba(0,0,0,0.03);"><th style="padding:10px; text-align:left;">Data</th><th style="padding:10px; text-align:left;">Evento</th><th style="padding:10px; text-align:center;">Ação</th></tr></thead>
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
                                    <tr style="border-bottom:1px solid #eee; <?= $row_style ?>">
                                        <td style="padding:15px; font-size:0.9rem; color:#555; white-space:nowrap; vertical-align:top;">
                                            <?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?>
                                        </td>
                                        <td style="padding:15px;">
                                            <div style="font-weight:bold; margin-bottom:5px; color:#212529;"><?= htmlspecialchars($h['titulo_fase']) ?></div>
                                            <?php 
                                                // Lógica de exibição de comentários estilizados
                                                $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                                                // Permite HTML rico da primeira parte (descrição do sistema/admin)
                                                // Mas previne XSS grosseiro se quiser, porem aqui confiamos no admin.
                                                // removemos htmlspecialchars e nl2br pois o CKEditor já formata p/ html
                                                $sys_desc = $parts[0]; 
                                                echo "<div style='color:var(--color-text-subtle); line-height:1.5;'>{$sys_desc}</div>";
                                                
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

                                        <td style="padding:15px; text-align:center; vertical-align:top;">
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
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h3 style="color:#fd7e14; margin-bottom:5px;">📋 Checklist de Pendências</h3>
                            <p style="color:var(--color-text-subtle); margin-bottom:20px;">Adicione itens que o cliente precisa resolver. O cliente verá esta lista.</p>
                        </div>
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
                            $msg_wpp_pend = "Olá {$primeiro_nome}, tudo bem? 👋\n\nSou a *Vilela Engenharia*. Segue o relatório das pendências necessárias para o andamento do seu processo:\n\n";
                            
                            if(count($pend_abertas) > 0) {
                                foreach($pend_abertas as $p) {
                                    $msg_wpp_pend .= "🔸 " . strip_tags($p['descricao']) . "\n";
                                }
                            } else {
                                $msg_wpp_pend .= "(Nenhuma pendência em aberto)\n";
                            }
                            
                            $msg_wpp_pend .= "\n📂 *Acesse sua Área do Cliente* para anexar documentos ou ver detalhes:\nhttps://vilela.eng.br/area-cliente/\n\nQualquer dúvida, estou à disposição por aqui!";
                        ?>
                        
                    </div>

                    <!-- Novo Form de Inserção Rápida -->
                    <form method="POST" style="background:#fff3e0; padding:20px; border-radius:12px; border:1px solid #ffe0b2; margin-bottom:25px;">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <h4 style="margin-top:0; color:#ef6c00;">➕ Adicionar Nova Pendência</h4>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div style="flex-grow:1;">
                                <textarea name="descricao_pendencia" id="new_pendencia_editor" placeholder="Digite a descrição..." style="width:100%;"></textarea>
                            </div>
                            <div style="text-align:right;">
                                <button type="submit" name="btn_adicionar_pendencia" class="btn-save" style="width:auto; margin:0; padding:10px 25px; color:white; background: #fd7e14; border:none;">Adicionar Pendência</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Lista de Pendências -->
                    <div class="table-responsive">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid #eee; background:#fff8e1; color:#e65100;">
                                    <th style="padding:15px; text-align:left; width:60%;">Descrição</th>
                                    <th style="padding:15px; text-align:center;">Data</th>
                                    <th style="padding:15px; text-align:center;">Status</th>
                                    <th style="padding:15px; text-align:right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Query de pendências já feita acima para o botão WhatsApp
                                
                                // Buscar Arquivos (Novo Sistema)
                                $stmtArq = $pdo->prepare("SELECT pendencia_id, id, arquivo_nome, arquivo_path, data_upload FROM processo_pendencias_arquivos WHERE pendencia_id IN (SELECT id FROM processo_pendencias WHERE cliente_id=?)");
                                $stmtArq->execute([$cliente_ativo['id']]);
                                $arquivos_por_pendencia = [];
                                foreach($stmtArq->fetchAll() as $arq) {
                                    $arquivos_por_pendencia[$arq['pendencia_id']][] = $arq;
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
                                    <tr style="border-bottom:1px solid #eee; background:<?= $bg_row ?>; opacity:<?= $row_opac ?>;">
                                        <td style="padding:15px;">
                                            <div style="font-size:1.05rem; color:#333; text-decoration:<?= $txt_dec ?>;">
                                                <?= $p['descricao'] // Já permite HTML do editor ?>
                                            </div>
                                            <?php if(!empty($arquivos)): ?>
                                                <div style="margin-top:5px; display:flex; flex-wrap:wrap; gap:5px;">
                                                    <?php foreach($arquivos as $arq): ?>
                                                    <a href="<?= htmlspecialchars($arq['arquivo_path']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:5px; font-size:0.85rem; color:#0d6efd; text-decoration:none; background:#e9ecef; padding:2px 8px; border-radius:4px;">
                                                        📎 <?= (strlen($arq['arquivo_nome']) > 25 ? substr($arq['arquivo_nome'],0,25).'...' : $arq['arquivo_nome']) ?>
                                                    </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:15px; text-align:center; color:#777; font-size:0.9rem;">
                                            <?= date('d/m/Y', strtotime($p['data_criacao'])) ?>
                                        </td>
                                        <td style="padding:15px; text-align:center;">
                                            <?php if($is_res): ?>
                                                <span style="background:#198754; color:white; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:bold;">RESOLVIDO</span>
                                            <?php elseif($is_anexo): ?>
                                                <span style="background:#0d6efd; color:white; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:bold;">ANEXADO</span>
                                            <?php else: ?>
                                                <span style="background:#ffc107; color:#000; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:bold;">PENDENTE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:15px; text-align:right;">
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
                </div>

                <!-- Modais Pendências -->
                <?php require 'includes/modals/pendencias.php'; ?>


            <?php elseif($active_tab == 'arquivos'): ?>
                
                <!-- ARQUIVOS CONTENT (Azul) -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h3 style="color:#0d6efd;">📂 Arquivos do Cliente</h3>
                            <p style="margin-bottom:20px; color:var(--color-text-subtle);">Central de links e pastas do Google Drive.</p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-group">
                            <label>🔗 Link da Pasta Geral (Backup/Drive)</label>
                            <input type="text" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" placeholder="https://drive.google.com/...">
                        </div>

                        <button type="submit" name="btn_salvar_arquivos" class="btn-save" style="background:#0d6efd; color:white; border:none; box-shadow:0 4px 10px rgba(13, 110, 253, 0.3);">Salvar Links</button>
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
                <div>
                <!-- Header e Botão Novo Lançamento -->
                <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; padding-bottom:15px; border-bottom:1px solid #e0e0e0;">
                    <div>
                        <h3 style="margin:0; color:#198754;">💰 Fluxo Financeiro</h3>
                        <p style="margin:5px 0 0 0; font-size:0.9rem; color:#666;">Gerencie honorários, taxas e despesas do processo.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalFinanceiro').showModal()" style="background:linear-gradient(135deg, #198754, #20c997); color:white; border:none; padding:12px 25px; border-radius:30px; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 15px rgba(25, 135, 84, 0.3); transition:all 0.2s;">
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
