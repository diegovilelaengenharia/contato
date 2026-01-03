<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Force Redeploy
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
try {
    require 'db.php';
} catch (Throwable $e) {
    die("<h1>Erro Crítico (Sintaxe ou Banco)</h1><p><strong>Arquivo:</strong> " . $e->getFile() . " <br><strong>Linha:</strong> " . $e->getLine() . "<br><strong>Erro:</strong> " . $e->getMessage() . "</p>");
}

// --- SELF-HEALING DATABASE (Correção de Colunas Faltantes) ---
try {
    $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN data_nascimento DATE DEFAULT NULL");
} catch (Exception $e) { 
    // Ignora erro se coluna já existe
}

// --- Configuração e Segurança ---
$minha_senha_mestra = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'VilelaAdmin2025'; 

// Verifica Sessão
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Lógica do Popup de Boas Vindas (Apenas 1x por sessão)
$show_welcome_popup = false;
if (!isset($_SESSION['welcome_shown'])) {
    $show_welcome_popup = true;
    $_SESSION['welcome_shown'] = true;
}

// Logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- Atualização de Schema ---
require 'includes/schema.php';

// --- Fases Padrão ---
$fases_padrao = [
    "Abertura de Processo (Guichê)", "Fiscalização (Parecer Fiscal)", "Triagem (Documentos Necessários)",
    "Comunicado de Pendências (Triagem)", "Análise Técnica (Engenharia)", "Comunicado (Pendências e Taxas)",
    "Confecção de Documentos", "Avaliação (ITBI/Averbação)", "Processo Finalizado (Documentos Prontos)"
];

// --- Taxas e Multas Padrão ---
$taxas_padrao = require 'config/taxas.php';

// --- Processamento ---

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

    <!-- HEADER MOBILE (Exclusivo para telas pequenas) -->
    <div class="admin-mobile-header" style="display:none; background:#146c43; color:white; padding:15px 20px; text-align:center; border-bottom:4px solid #0f5132;">
        <img src="../assets/logo.png" alt="Vilela Engenharia" style="height:45px; margin-bottom:10px; display:block; margin-left:auto; margin-right:auto;">
        <h3 style="margin:0 0 5px 0; font-size:1.1rem; text-transform:uppercase; letter-spacing:1px; font-weight:800;">Gestão Administrativa</h3>
        <div style="font-size:0.85rem; opacity:0.9; line-height:1.4;">
            Eng. Diego Vilela &nbsp;|&nbsp; CREA-MG: 235474/D<br>
            vilela.eng.mg@gmail.com &nbsp;|&nbsp; (35) 98452-9577
        </div>
    </div>

<header class="admin-header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <img src="../assets/logo.png" alt="Logo" style="height: 50px;">
        <div style="display:flex; flex-direction:column; gap:4px;">
            <a href="gestao_admin_99.php" style="text-decoration:none; color:inherit;">
                <h1 style="margin:0; font-size:1.4rem; font-weight:700;">Gestão Administrativa</h1>
            </a>
            <div style="font-size:0.85rem; opacity: 1; line-height:1.4; font-weight: 500;">
                Eng. Diego Vilela &nbsp;|&nbsp; CREA-MG: 235474/D<br>
                vilela.eng.mg@gmail.com &nbsp;|&nbsp; (35) 98452-9577
            </div>
        </div>
    </div>
    <div style="display:flex; align-items:center;">
        <a href="?sair=true" style="color: white;">Sair</a>
    </div>
</header>

<div class="admin-container">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        ☰ Menu de Navegação
    </button>
    <aside class="sidebar" id="mobileSidebar">
        <nav class="sidebar-menu">
            <h4 style="font-size:0.75rem; text-transform:uppercase; color:#adb5bd; font-weight:700; margin:10px 0 5px 10px;">Principal</h4>
            <a href="gestao_admin_99.php" class="btn-menu <?= (!isset($_GET['cliente_id']) && !isset($_GET['novo']) && !isset($_GET['importar'])) ? 'active' : '' ?>">
                <span class="material-symbols-rounded">dashboard</span>
                Visão Geral
            </a>
            
            <?php 
                // Lógica de Cor: Amarelo se tiver pendências, Padrão (branco) se não.
                $alert_color_style = ($kpi_pre_pendentes > 0) ? 
                    'background: linear-gradient(135deg, #fff3cd, #ffecb5); color: #856404; border: 1px solid #ffeeba;' : 
                    'background: #fff; color: var(--color-text); border: 1px solid transparent;'; 
            ?>
            <button onclick="document.getElementById('modalNotificacoes').showModal()" class="btn-menu" style="cursor:pointer; text-align:left; width:100%; font-family:inherit; font-size:inherit; transition: 0.3s; <?= $alert_color_style ?>">
                <span class="material-symbols-rounded">notifications</span>
                Central de Avisos
                <?php if($kpi_pre_pendentes > 0): ?>
                    <span style="background:#dc3545; color:white; padding:1px 8px; border-radius:12px; font-size:0.75rem; margin-left:auto; line-height:1.2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-weight:bold;"><?= $kpi_pre_pendentes ?></span>
                <?php endif; ?>
            </button>
            
            <a href="avisos_gerais.php" class="btn-menu">
                <span class="material-symbols-rounded">campaign</span>
                Aviso Global
            </a>
            
            <h4 style="font-size:0.75rem; text-transform:uppercase; color:#adb5bd; font-weight:700; margin:15px 0 5px 10px;">Cadastro</h4>
            <!-- Botão Novo Cliente (Neutro) -->
            <a href="?novo=true" class="btn-menu <?= (isset($_GET['novo'])) ? 'active' : '' ?>">
                <span class="material-symbols-rounded">person_add</span>
                Novo Cliente
            </a>
            <a href="../cadastro.php" target="_blank" class="btn-menu">
                <span class="material-symbols-rounded">public</span>
                Pré-Cadastro ↗
            </a>
            <a href="?importar=true" class="btn-menu <?= (isset($_GET['importar'])) ? 'active' : '' ?>">
                <span class="material-symbols-rounded">move_to_inbox</span>
                Solicitações
                <?php if(isset($kpi_pre_pendentes) && $kpi_pre_pendentes > 0): ?>
                    <span class="badge-count"><?= $kpi_pre_pendentes ?></span>
                <?php endif; ?>
            </a>
        </nav>

        <h4 style="margin: 20px 0 10px 10px; color: var(--color-text-subtle); display:flex; align-items:center; gap:8px; font-size:0.8rem; text-transform:uppercase; font-weight:700;">📂 Meus Clientes</h4>
        <div class="client-list-fancy" style="padding:0 10px; max-height:500px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
            <?php foreach($clientes as $c): 
                $isActive = ($cliente_ativo && $cliente_ativo['id'] == $c['id']);
                
                // Dados do DB já são o Primeiro Nome (devido à migração)
                // NEW: Show First 2 Names
                $parts = explode(' ', trim($c['nome']));
                $first_name = $parts[0] . (isset($parts[1]) ? ' ' . $parts[1] : '');
                $first_name = htmlspecialchars($first_name);
                
                $initial = strtoupper(substr($first_name, 0, 1));
                
                // Estilo
                $bg = $isActive ? 'var(--color-primary-light)' : '#fff';
                $border = $isActive ? '1px solid var(--color-primary)' : '1px solid transparent';
                $color = $isActive ? 'var(--color-primary)' : '#444';
            ?>
                <a href="?cliente_id=<?= $c['id'] ?>" style="display:flex; align-items:center; gap:12px; padding:10px; background:<?= $bg ?>; border-radius:8px; text-decoration:none; color:<?= $color ?>; border:<?= $border ?>; transition:0.2s;" onmouseover="this.style.background='#f0f8f5'" onmouseout="this.style.background='<?= $bg ?>'">
                    <div style="width:32px; height:32px; background:<?= $isActive?'var(--color-primary)':'#eee' ?>; color:<?= $isActive?'#fff':'#777' ?>; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.9rem;">
                        <span class="material-symbols-rounded" style="font-size:1.1rem;">person</span>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $first_name ?></div>
                        <div style="font-size:0.75rem; opacity:0.7;">ID #<?= str_pad($c['id'], 3, '0', STR_PAD_LEFT) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main>
        <!-- 🔔 CENTRAL DE AVISOS -->
        <?php
        // 1. Tabela de Avisos (Broadcast)
        $pdo->exec("CREATE TABLE IF NOT EXISTS sistema_avisos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mensagem TEXT,
            ativo TINYINT(1) DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 2. Salvar Aviso Global
        if(isset($_POST['btn_salvar_aviso_geral'])) {
            $msg = trim($_POST['mensagem_aviso']);
            $pdo->query("UPDATE sistema_avisos SET ativo=0"); // Reset
            if(!empty($msg)) {
                $stmta = $pdo->prepare("INSERT INTO sistema_avisos (mensagem, ativo) VALUES (?, 1)");
                $stmta->execute([$msg]);
            }
            // Feedback visual via Toastify (injetado via JS no final ou aqui mesmo)
            echo "<script>document.addEventListener('DOMContentLoaded', () => Toastify({text: '📢 Aviso Global Atualizado!', duration: 3000, style:{background:'#198754'}}).showToast());</script>";
        }

        // 3. Dados
        $aviso_atual = $pdo->query("SELECT * FROM sistema_avisos WHERE ativo=1 ORDER BY id DESC LIMIT 1")->fetch();

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

        // Solicitações Web (Pendentes)
        $solicitacoes = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC")->fetchAll();
        ?>

        <!-- Smart Indicators Bar -->
        <style>
            .indicators-bar { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; align-items: flex-start; }
            
            .smart-btn { 
                position: relative; 
                background: white; 
                border: 1px solid #e0e0e0; 
                border-radius: 30px; 
                padding: 8px 16px; 
                display: flex; 
                align-items: center; 
                gap: 10px; 
                cursor: default; 
                transition: all 0.2s ease;
                font-size: 0.9rem;
                color: #555;
                font-weight: 600;
                box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            }
            
            .smart-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 10; }
            .smart-btn.active { border-color: currentColor; background: white; }
            
            /* Badge Styles */
            .s-badge { padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; color: white; }
            
            /* Theme Colors */
            .theme-green { color: #198754; }
            .theme-green .s-badge { background: #198754; }
            .theme-orange { color: #fd7e14; }
            .theme-orange .s-badge { background: #fd7e14; }
            .theme-red { color: #dc3545; }
            .theme-red .s-badge { background: #dc3545; }
            .theme-gray { color: #adb5bd; border-color: #f0f0f0; }
            .theme-gray .s-badge { background: #adb5bd; }

            /* Dropdown Logic */
            .smart-dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                margin-top: 10px;
                background: white;
                border: 1px solid #eee;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                width: 320px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(10px);
                transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
                z-index: 100;
                overflow: hidden;
            }
            .smart-btn:hover .smart-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
            
            /* Dropdown Content */
            .sd-header { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; background: #fafafa; font-size: 0.85rem; font-weight: 700; color: #444; }
            .sd-list { list-style: none; padding: 0; margin: 0; max-height: 300px; overflow-y: auto; }
            .sd-item { padding: 12px 15px; border-bottom: 1px solid #f9f9f9; display: flex; align-items: center; justify-content: space-between; gap: 10px; transition: background 0.1s; }
            .sd-item:last-child { border-bottom: none; }
            .sd-item:hover { background: #f8f9fa; }
            .sd-empty { padding: 20px; text-align: center; color: #999; font-size: 0.85rem; font-style: italic; }
            
            /* Global Broadcast Compact */
            .broadcast-compact { flex: 1; display: flex; gap: 10px; background: white; padding: 8px; border-radius: 30px; border: 1px solid #e0e0e0; align-items: center; min-width: 300px; }
            .broadcast-input { border: none; outline: none; flex: 1; padding: 0 15px; font-size: 0.9rem; color: #444; background: transparent; }
            .broadcast-btn { background: #6610f2; color: white; border: none; padding: 6px 15px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; cursor: pointer; white-space: nowrap; transition: background 0.2s; }
            .broadcast-btn:hover { background: #520dc2; }
        </style>

        <div class="indicators-bar">
            
            <!-- 1. Solicitações Web (Removido por solicitação) -->

            <!-- 2. Aniversariantes -->
            <?php 
                $count_ani = count($aniversariantes);
                $theme_ani = $count_ani > 0 ? 'theme-orange active' : 'theme-gray';
            ?>
            <div class="smart-btn <?= $theme_ani ?>">
                <span class="material-symbols-rounded">cake</span>
                Aniversariantes
                <span class="s-badge"><?= $count_ani ?></span>
                 <!-- Dropdown -->
                 <div class="smart-dropdown">
                    <div class="sd-header">Aniversariantes de <?= date('M') ?></div>
                    <?php if($count_ani == 0): ?>
                        <div class="sd-empty">Ninguém por enquanto.</div>
                    <?php else: ?>
                        <div class="sd-list">
                            <?php foreach($aniversariantes as $ani): ?>
                                <div class="sd-item">
                                    <span style="font-weight:600; color:#333;"><?= htmlspecialchars($ani['nome']) ?></span>
                                    <span style="background:#fff3cd; color:#856404; padding:2px 8px; border-radius:10px; font-weight:bold; font-size:0.75rem;">Dia <?= $ani['dia'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Processos Parados -->
            <?php 
                $count_par = count($parados);
                $theme_par = $count_par > 0 ? 'theme-red active' : 'theme-gray';
            ?>
            <div class="smart-btn <?= $theme_par ?>">
                <span class="material-symbols-rounded">timer_off</span>
                Parados (+15d)
                <span class="s-badge"><?= $count_par ?></span>
                 <!-- Dropdown -->
                 <div class="smart-dropdown">
                    <div class="sd-header">Sem movimentação há > 15 dias</div>
                    <?php if($count_par == 0): ?>
                        <div class="sd-empty">Tudo movimentando! 🚀</div>
                    <?php else: ?>
                        <div class="sd-list">
                            <?php foreach($parados as $p): ?>
                                <div class="sd-item">
                                    <div style="overflow:hidden; white-space:nowrap; text-overflow:ellipsis; max-width:180px; font-weight:600; color:#333; font-size:0.85rem;"><?= htmlspecialchars($p['nome']) ?></div>
                                    <a href="?cliente_id=<?= $p['id'] ?>&tab=andamento" style="text-decoration:none; background:#ffebee; color:#d32f2f; padding:2px 8px; border-radius:10px; font-weight:bold; font-size:0.75rem; white-space:nowrap;">
                                        <?= date('d/m', strtotime($p['ultima_mov'])) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. Global Broadcast (REMOVED - Moved to Dedicated Page) -->
        </div>

        <!-- Mensagens PHP serão capturadas pelo JS abaixo -->

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

                    <!-- Modal Aprovar Cadastro -->
                    <dialog id="modalAprovarCadastro" style="border:none; border-radius:12px; padding:0; width:90%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                        <div style="background:var(--color-primary); color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="margin:0; font-size:1.2rem;">✅ Aprovar e Finalizar</h3>
                            <button onclick="document.getElementById('modalAprovarCadastro').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
                        </div>
                        
                        <form method="POST" style="padding:25px;">
                            <input type="hidden" name="id_pre" id="apr_id_pre">
                            
                            <div class="form-group">
                                <label>Nome do Cliente</label>
                                <input type="text" name="nome_final" id="apr_nome" required>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Usuário de Login (CPF)</label>
                                    <input type="text" name="usuario_final" id="apr_usuario" required>
                                </div>
                                <div class="form-group">
                                    <label>Senha Inicial</label>
                                    <input type="text" name="senha_final" value="mudar123" required>
                                </div>
                            </div>
                            
                            <hr style="margin:20px 0; border-top:1px solid #eee;">
                            
                            <div style="display:flex; justify-content:flex-end;">
                                <button type="submit" name="btn_confirmar_aprovacao" class="btn-save" style="width:100%;">🚀 Confirmar e Criar Cliente</button>
                            </div>
                        </form>
                    </dialog>

                    <script>
                        function openAprovarModal(id, nome, cpf) {
                            document.getElementById('apr_id_pre').value = id;
                            document.getElementById('apr_nome').value = nome;
                            document.getElementById('apr_usuario').value = cpf.replace(/\D/g, ''); // Sugere CPF limpo como login
                            document.getElementById('modalAprovarCadastro').showModal();
                        }
                    </script>
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

            <!-- Card Resumo do Cliente (Compacto & Integrado) -->
            <div class="form-card" style="display:flex; align-items:center; gap:20px; padding:20px; flex-wrap:wrap; margin-bottom:20px; border-left:5px solid var(--color-primary); background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                
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
                </form>

                <div class="client-summary-card">
                    <div style="display:flex; gap:15px; align-items:center;">
                        <!-- Ícone Removido conforme solicitação -->
                        <div>
                            <h2 style="margin:0 0 5px 0; font-size:1.4rem;"><?= htmlspecialchars($cliente_ativo['nome']) ?></h2>
                            <div style="display:flex; gap:10px; font-size:0.9rem; color:#666; align-items:center; flex-wrap:wrap;">
                                <span>🆔 #<?= str_pad($cliente_ativo['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                <span>•</span>
                                <a href="editar_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="link-edit" style="color:var(--color-primary); text-decoration:none; font-weight:600;">✏️ Editar Cadastro</a>
                                <span>•</span>
                                <a href="relatorio_cliente.php?id=<?= $cliente_ativo['id'] ?>" target="_blank" class="link-edit" style="color:var(--color-secondary); text-decoration:none; font-weight:600;">⚠️ Resumo PDF</a>
                                <span>•</span>
                                <a href="?delete_cliente=<?= $cliente_ativo['id'] ?>" class="link-edit btn-delete-confirm" data-confirm-text="Tem certeza que deseja EXCLUIR este cliente permanentemente?" style="color:#dc3545; text-decoration:none; font-weight:600;">🗑️ Excluir</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- DADOS DO PROCESSO (Removido por solicitação - Editar no Cadastro Completo) -->
                    <div style="margin-top:15px; padding-top:15px; border-top:1px solid #eee; font-size:0.85rem; color:#888; font-style:italic;">
                        Para editar dados do processo, use o botão "Editar Cadastro".
                    </div>
                </div>

            </div>

            <div class="simple-timeline">
                <?php 
                $found_idx = array_search(($detalhes['etapa_atual']??''), $fases_padrao);
                if($found_idx === false) $found_idx = -1;
                foreach($fases_padrao as $i => $f): 
                    $cl = ($i < $found_idx) ? 'past' : ($i == $found_idx ? 'active' : '');
                ?>
                    <div class="st-item <?= $cl ?>">
                        <div class="st-dot"></div>
                        <span><?= $f ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tabs-header">
                <!-- Aba Cadastro Removida (Agora é o header fixo) -->
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento" class="tab-btn <?= ($active_tab=='andamento' || $active_tab=='cadastro')?'active':'' ?>">📊 Linha do Tempo</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias" class="tab-btn <?= $active_tab=='pendencias'?'active':'' ?>">⚠️ Pendências</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=financeiro" class="tab-btn <?= $active_tab=='financeiro'?'active':'' ?>">💰 Financeiro</a>
                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=arquivos" class="tab-btn <?= $active_tab=='arquivos'?'active':'' ?>">📂 Arquivos</a>
            </div>

            <?php if($active_tab == 'andamento' || $active_tab == 'cadastro'): ?>
                <style>
                    .mobile-only { display: none; }
                    @media(max-width:768px) { .mobile-only { display: flex; } }
                </style>

                <!-- Botão para Abrir Modal de Novo Andamento -->
                <div style="margin-bottom:20px; display:flex; gap:10px; justify-content:flex-start;">
                    <button type="button" onclick="document.getElementById('modalAndamento').showModal()" class="btn-save" style="padding:8px 20px; background:white; border:2px solid #ffc107; border-radius:30px; font-size:0.9rem; font-weight:700; color:#b38600; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                        <span style="font-size:1.2rem;">✨</span> Novo Andamento
                    </button>
                    <!-- Botão de Apoio (Ex: Apenas Documento) -->
                    <button type="button" onclick="document.getElementById('modalAndamento').showModal();" style="padding:8px 15px; background:white; border:1px solid #ddd; border-radius:30px; font-size:0.85rem; font-weight:600; color:#666; cursor:pointer; display:flex; align-items:center; gap:5px; transition:all 0.2s;" title="Atalho para documento">
                        📂 Anexar Arquivo
                    </button>
                    <script>
                        // Hover interactions
                        const btnNovo = document.querySelector('button[onclick*="modalAndamento"]');
                        btnNovo.addEventListener('mouseenter', () => { btnNovo.style.background = '#fff9db'; btnNovo.style.transform = 'translateY(-2px)'; });
                        btnNovo.addEventListener('mouseleave', () => { btnNovo.style.background = 'white'; btnNovo.style.transform = 'translateY(0)'; });
                    </script>
                </div>

                <!-- MODAL DE NOVO ANDAMENTO -->
                <dialog id="modalAndamento" style="border:none; border-radius:12px; padding:0; width:90%; max-width:600px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                    <div style="background: linear-gradient(135deg, var(--color-primary) 0%, #2980b9 100%); padding:20px; display:flex; justify-content:space-between; align-items:center; color:white;">
                        <h3 style="margin:0; font-size:1.2rem; display:flex; align-items:center; gap:10px;">✨ Novo Andamento</h3>
                        <button type="button" onclick="document.getElementById('modalAndamento').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
                    </div>
                    
                    <div style="padding:25px;">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                            
                            <!-- LINHA 1: Fase e Título -->
                            <div style="margin-bottom:15px;">
                                <label style="display:block; font-size:0.85rem; font-weight:bold; color:#555; margin-bottom:5px;">📌 Fase</label>
                                <select name="nova_etapa" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:1rem; background:#f9f9f9;">
                                    <option value="">Manter: <?= htmlspecialchars($detalhes['etapa_atual']??'-') ?></option>
                                    <?php foreach($fases_padrao as $f): ?>
                                        <option value="<?= $f ?>"><?= $f ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="margin-bottom:15px;">
                                <label style="display:block; font-size:0.85rem; font-weight:bold; color:#555; margin-bottom:5px;">📝 Título do Evento</label>
                                <input type="text" name="titulo_evento" required placeholder="Ex: Protocolo Realizado..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:1rem;">
                            </div>
                            
                            <!-- LINHA 2: Descrição -->
                            <div style="margin-bottom:15px;">
                                <textarea name="observacao_etapa" rows="3" placeholder="Detalhes (Opcional)..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:1rem; resize:vertical; font-family:inherit;"></textarea>
                            </div>

                            <!-- Upload -->
                            <div style="margin-bottom:20px;">
                                 <div style="width:100%; border:2px dashed #ccc; border-radius:8px; padding:15px; text-align:center; cursor:pointer; background:#f8f9fa;" onclick="document.getElementById('file_input_modal').click();" id="dropzone_modal">
                                     <span style="font-size:1.5rem; display:block; margin-bottom:5px;">📂</span>
                                     <span style="font-weight:bold; color:#666;">Anexar Arquivo</span>
                                     <input type="file" id="file_input_modal" name="arquivo_documento" style="display:none;" onchange="if(this.files.length > 0) { document.getElementById('dropzone_modal').style.borderColor='#198754'; document.getElementById('dropzone_modal').style.background='#e8f5e9'; document.getElementById('dropzone_modal').querySelector('span:last-child').innerText = '✅ Arquivo Selecionado!'; }">
                                 </div>
                            </div>

                            <!-- BOTÃO -->
                            <button type="submit" name="atualizar_etapa" class="btn-save" style="width:100%; padding:12px; background:var(--color-primary); border:none; border-radius:8px; font-size:1.1rem; font-weight:bold; color:white; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                                ✅ Registrar Movimentação
                            </button>
                        </form>
                    </div>
                </dialog>
                    
                    <!-- Script removed as logic is now backend-driven -->

                <div class="form-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h3 style="margin:0;">📜 Histórico Completo do Processo</h3>
                        <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=andamento&del_all_hist=true" 
                           onclick="return confirm('ATENÇÃO EXTREMA: \n\nVocê está prestes a APAGAR TODO O HISTÓRICO deste processo.\n\nIsso limpará todas as movimentações, datas e logs.\n\nTem certeza absoluta que deseja fazer isso?');"
                           style="background:#dc3545; color:white; padding:6px 12px; border-radius:6px; font-size:0.8rem; text-decoration:none; font-weight:bold; display:flex; align-items:center; gap:6px;">
                           🗑️ Apagar Histórico Completo
                        </a>
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
                                        $row_style = "background:#e3f2fd; border-left:5px solid #0d6efd;";
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

                <div class="form-card" style="border-left: 6px solid #ffc107;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h3 style="color:#b38600; margin-bottom:5px;">📋 Checklist de Pendências</h3>
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
                        

                        
                        <!-- Botão Cobrar Cliente removido conforme solicitação -->
                    </div>

                    <!-- Novo Form de Inserção Rápida -->
                    <form method="POST" style="background:#fff8e1; padding:20px; border-radius:8px; border:1px solid #ffeeba; margin-bottom:25px;">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <h4 style="margin-top:0; color:#b38600;">➕ Adicionar Nova Pendência</h4>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div style="flex-grow:1;">
                                <textarea name="descricao_pendencia" id="new_pendencia_editor" placeholder="Digite a descrição..." style="width:100%;"></textarea>
                            </div>
                            <div style="text-align:right;">
                                <button type="submit" name="btn_adicionar_pendencia" class="btn-save btn-warning" style="width:auto; margin:0; padding:10px 25px; color:#000;">Adicionar Pendência</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Lista de Pendências -->
                    <div class="table-responsive">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid #eee; background:#f9f9f9; color:#666;">
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
                                                <a href="?cliente_id=<?= $cliente_ativo['id'] ?>&tab=pendencias&toggle_pendencia=<?= $p['id'] ?>" class="btn-icon" style="background:#e8f5e9; color:#198754; border:1px solid #c3e6cb; margin-right:5px;" title="Marcar como Resolvido">✅</a>
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

                <!-- Modal Editar Pendência -->
                <dialog id="modalEditPendencia" style="border:none; border-radius:10px; padding:0; width:90%; max-width:600px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                    <form method="POST" style="display:flex; flex-direction:column;">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <input type="hidden" name="pendencia_id" id="edit_pendencia_id">
                        
                        <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="margin:0;">✏️ Editar Pendência</h3>
                            <button type="button" onclick="document.getElementById('modalEditPendencia').close()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                        </div>
                        
                        <div style="padding:20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:bold;">Descrição</label>
                            <textarea name="descricao_pendencia" id="edit_pendencia_texto" rows="4" style="width:100%;"></textarea>
                        </div>
                        
                        <div style="padding:20px; background:#f9f9f9; text-align:right;">
                            <button type="button" onclick="document.getElementById('modalEditPendencia').close()" style="padding:10px 15px; border:1px solid #ddd; background:#fff; border-radius:5px; margin-right:10px; cursor:pointer;">Cancelar</button>
                            <button type="submit" name="btn_editar_pendencia" class="btn-save btn-primary" style="width:auto; margin:0;">Salvar Alteração</button>
                        </div>
                    </form>
                </dialog>

                <style>
                    .btn-icon {
                        display: inline-flex; width: 32px; height: 32px; align-items: center; justify-content: center;
                        border-radius: 6px; text-decoration: none; font-size: 1rem; cursor: pointer; transition: 0.2s;
                    }
                    .btn-icon:hover { transform: scale(1.1); filter: brightness(0.95); }
                    /* Ajuste fino para o editor ficar mais compacto */
                    .ck-editor__editable_inline { min-height: 80px !important; }
                </style>

                <script>
                    let editorAdicao;
                    let editorEdicao;

                    // Inicializa Editor de Adição (Simples)
                    ClassicEditor
                        .create( document.querySelector( '#new_pendencia_editor' ), {
                            toolbar: [ 'bold', 'italic', 'link', 'bulletedList', '|', 'undo', 'redo' ],
                            placeholder: 'Digite a pendência aqui (Você pode usar negrito, listas...)',
                            language: 'pt-br'
                        } )
                        .then( newEditor => { editorAdicao = newEditor; } )
                        .catch( error => { console.error( error ); } );

                    // Inicializa Editor de Edição
                    ClassicEditor
                        .create( document.querySelector( '#edit_pendencia_texto' ), {
                            toolbar: [ 'bold', 'italic', 'link', 'bulletedList', '|', 'undo', 'redo' ],
                            language: 'pt-br'
                        } )
                        .then( newEditor => { editorEdicao = newEditor; } )
                        .catch( error => { console.error( error ); } );

                    function openEditPendencia(id, textoHtml) {
                        document.getElementById('edit_pendencia_id').value = id;
                        // Seta dados no CKEditor
                        if(editorEdicao) {
                            editorEdicao.setData(textoHtml);
                        }
                        document.getElementById('modalEditPendencia').showModal();
                    }
                </script>


            <?php elseif($active_tab == 'arquivos'): ?>
                <div class="form-card" style="border-left: 6px solid #2196f3;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h3 style="color:#1976d2;">📂 Arquivos do Cliente</h3>
                            <p style="margin-bottom:20px; color:var(--color-text-subtle);">Central de links e pastas do Google Drive.</p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-group">
                            <label>🔗 Link da Pasta Geral (Backup/Drive)</label>
                            <input type="text" name="link_drive_pasta" value="<?= $detalhes['link_drive_pasta']??'' ?>" placeholder="https://drive.google.com/...">
                        </div>

                        <button type="submit" name="btn_salvar_arquivos" class="btn-save btn-info">Salvar Links</button>
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



                <!-- Form de Adição -->
                <div class="form-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h3 style="margin:0;">➕ Novo Lançamento Financeiro</h3>
                        <button type="button" onclick="openTaxasModal()" class="btn-save btn-info" style="width:auto; padding:8px 15px; font-size:0.9rem;">📋 Selecionar Padrão</button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" name="descricao" required placeholder="Ex: Taxa de Habite-se">
                            </div>
                            <div class="form-group">
                                <label>Categoria</label>
                                <select name="categoria" required>
                                    <option value="honorarios">Honorários (Vilela Engenharia)</option>
                                    <option value="taxas">Taxas e Multas (Governo/Prefeitura)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Valor (R$)</label>
                                <input type="number" step="0.01" name="valor" required placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Vencimento</label>
                                <input type="date" name="data_vencimento" required>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="pendente">⏳ Pendente</option>
                                    <option value="pago">✅ Pago</option>
                                    <option value="atrasado">❌ Atrasado</option>
                                    <option value="isento">⚪ Isento</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" name="btn_salvar_financeiro" class="btn-save btn-success">Adicionar Lançamento</button>
                    </form>
                </div>

                <!-- Tabelas -->
                <?php 
                try {
                    // Verifica se tabela existe (silencioso) ou só roda
                    $fin_honorarios = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='honorarios' ORDER BY data_vencimento ASC");
                    $fin_honorarios->execute([$cliente_ativo['id']]);
                    
                    $fin_taxas = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id=? AND categoria='taxas' ORDER BY data_vencimento ASC");
                    $fin_taxas->execute([$cliente_ativo['id']]);

                    function renderFinTable($stmt, $title, $color, $cid) {
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

                    renderFinTable($fin_honorarios, "💰 Honorários e Serviços (Vilela Engenharia)", "#2196f3", $cliente_ativo['id']);
                    renderFinTable($fin_taxas, "🏛️ Taxas e Multas Governamentais", "#efb524", $cliente_ativo['id']);

                } catch (Exception $e) {
                    echo "<div style='color:red'>Erro ao carregar dados financeiros. Verifique se o Setup de Banco de Dados foi rodado. <br>". $e->getMessage() ."</div>";
                }
                ?>



            <?php endif; ?>

        <?php else: ?>
            
            <!-- DASHBOARD GERAL (Visão do Gestor) -->
            <div style="margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <h2 style="color:var(--color-primary); margin-bottom:5px;">Visão Geral do Escritório</h2>
                    <p style="color:var(--color-text-subtle);">Resumo de atividades e indicadores de performance.</p>
                </div>
            </div>

                <!-- Modal Notificações Movido para rodapé para ser global -->
                
                <!-- Modal Cobrar Cliente -->
                <!-- Modal Cobrar Cliente (Refeito) -->
                <dialog id="modalChargeNew" style="border:none; border-radius:12px; padding:0; width:90%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                    <div style="background:var(--color-primary); color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:1.2rem;">📱 Cobrar Pendências</h3>
                        <button onclick="document.getElementById('modalChargeNew').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
                    </div>
                    
                    <div style="padding:25px;">
                        <div style="background:#f0f8ff; border-left:4px solid #0056b3; padding:15px; margin-bottom:20px; border-radius:4px;">
                            <strong style="color:#0056b3; display:block; margin-bottom:5px;">💡 Profissionalismo</strong>
                            <span style="font-size:0.9rem; color:#444;">Modelo de mensagem pronto com a lista de pendências.</span>
                        </div>

                        <label style="display:block; margin-bottom:10px; font-weight:bold; color:#333;">Mensagem:</label>
                        <textarea id="chargeTextNew" rows="12" style="width:100%; border:1px solid #ccc; border-radius:8px; padding:15px; font-family:monospace; background:#fafafa; font-size:0.9rem; resize:vertical;" readonly></textarea>
                        
                        <div style="margin-top:20px; display:flex; gap:10px;">
                            <button type="button" onclick="copyChargeTextNew()" class="btn-save" style="flex:1; justify-content:center; background:var(--color-primary);">📋 Copiar</button>
                            <a id="btnOpenWhatsNew" href="#" target="_blank" class="btn-save" style="flex:1; justify-content:center; background:#25D366; text-align:center; text-decoration:none;">Abrir WhatsApp</a>
                        </div>
                    </div>
                </dialog>
                
                <!-- Modal Status Financeiro -->
                <dialog id="modalStatusFin" style="border:none; border-radius:8px; padding:20px; box-shadow:0 5px 20px rgba(0,0,0,0.2);">
                    <form method="POST">
                        <input type="hidden" name="cliente_id" value="<?= $cliente_ativo['id'] ?>">
                        <input type="hidden" name="fin_id" id="edit_fin_id">
                        
                        <h3 style="margin-top:0;">Alterar Status Financeiro</h3>
                        
                        <div class="form-group">
                            <label>Novo Status</label>
                            <select name="novo_status" id="edit_fin_status" style="width:100%; padding:8px;">
                                <option value="pendente">⏳ Pendente</option>
                                <option value="pago">✅ Pago</option>
                                <option value="atrasado">❌ Atrasado</option>
                                <option value="isento">⚪ Isento</option>
                            </select>
                        </div>
                        
                        <div style="margin-top:15px; text-align:right; display:flex; justify-content:flex-end; gap:10px;">
                            <button type="button" onclick="document.getElementById('modalStatusFin').close()" style="padding:8px 15px; border:1px solid #ccc; background:white; border-radius:4px; cursor:pointer;">Cancelar</button>
                            <button type="submit" name="btn_update_status_fin" class="btn-save btn-primary" style="width:auto; padding:8px 15px; margin:0;">Salvar</button>
                        </div>
                    </form>
                </dialog>

                <script>
                function openStatusFinModal(id, currentStatus) {
                    document.getElementById('edit_fin_id').value = id;
                    document.getElementById('edit_fin_status').value = currentStatus;
                    document.getElementById('modalStatusFin').showModal();
                }
                </script>


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
                        <div style="color:#198754; font-size:1.1rem;"><?= number_format($kpi_fin_pendente, 2, ',', '.') ?></div>
                        <div>A Receber (Futuro)</div>
                    </div>
                </div>
                
                <!-- 5. Atrasados (Alerta) - Só aparece se tiver -->
                <?php if($kpi_fin_atrasado > 0): ?>
                <div class="kpi-card-compact" style="border-color:#dc3545;">
                    <div class="kpi-icon-box" style="background:#dc3545; color:white;">⚠️</div>
                    <div class="kpi-content">
                        <div style="color:#dc3545; font-size:1.1rem;"><?= number_format($kpi_fin_atrasado, 2, ',', '.') ?></div>
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

<script>
    function toggleSidebar() {
        document.getElementById('mobileSidebar').classList.toggle('show');
    }

    // 1. Loading nos Botões
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if(btn) {
                const originalText = btn.innerText;
                btn.innerHTML = '⏳ Salvando...';
                btn.style.opacity = '0.7';
                btn.style.cursor = 'wait';
                // Prevents double click logic is handled effectively by the form submission navigation, but disabling helps visual feedback
                // btn.disabled = true; // Caution: disabling sometimes prevents value submission in some browsers if not careful, but usually ok.
            }
        });
    });

    // 2. SweetAlert nos deletes (Generalizado)
    function confirmAction(e, message) {
        e.preventDefault();
        const url = e.currentTarget.href;
        
        Swal.fire({
            title: 'Tem certeza?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, confirmar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const text = this.getAttribute('data-confirm-text') || 'Tem certeza?';
            confirmAction(e, text); // Reuses the same logic
        });
    });

    // 3. Toasts para Mensagens PHP
    <?php if(isset($sucesso)): ?>
        Toastify({
            text: "<?= addslashes($sucesso) ?>",
            duration: 4000,
            gravity: "top", 
            position: "right", 
            style: { background: "linear-gradient(to right, #00b09b, #96c93d)" }
        }).showToast();
    <?php endif; ?>

    <?php if(isset($trigger_wpp) && isset($wpp_link)): ?>
        Swal.fire({
            title: 'Notificar Cliente? 📱',
            text: "O processo mudou de fase. Deseja avisar no WhatsApp?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#25D366',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sim, Enviar Whats!',
            cancelButtonText: 'Não notificar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('<?= $wpp_link ?>', '_blank');
            }
        });
    <?php endif; ?>

    <?php if(isset($erro)): ?>
        Toastify({
            text: "<?= addslashes($erro) ?>",
            duration: 5000,
            gravity: "top", 
            position: "right", 
            style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" }
        }).showToast();
    <?php endif; ?>

    // --- Modal e Lógica de Taxas ---
    function openTaxasModal() {
        document.getElementById('modalTaxas').showModal();
    }
    function closeTaxasModal() {
        document.getElementById('modalTaxas').close();
    }
    function selectTaxa(titulo, lei, tipo, valor) {
        // Preenche campos
        const form = document.querySelector('form[action=""] div.form-grid') ? document.querySelector('form[action=""] div.form-grid').parentElement : document.forms[2]; // Busca o form de financeiro (hack simples baseada na ordem, melhor usar ID)
        
        // Melhor abordagem: usar IDs nos inputs do Financeiro
        const inpDesc = document.querySelector('input[name="descricao"]');
        const semCateg = document.querySelector('select[name="categoria"]');
        const inpValor = document.querySelector('input[name="valor"]');
        
        if(inpDesc) {
            let texto = titulo;
            if(lei) texto += " (Ref: " + lei + ")";
            inpDesc.value = texto;
        }
        
        if(inpValor && valor) {
            inpValor.value = valor;
        }
        
        if(semCateg) {
            semCateg.value = 'taxas'; // Força categoria taxas para ambos, ou muda se for honorarios
        }
        
        closeTaxasModal();
        
        Toastify({
             text: "Item selecionado! Complete o valor e salve.",
             duration: 3000,
             style: { background: "#4caf50" }
        }).showToast();
    }
</script>

<!-- MODAL DE SELEÇÃO DE TAXAS -->
<dialog id="modalTaxas" style="border:none; border-radius:12px; padding:0; width:90%; max-width:800px; max-height:90vh; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#f8f9fa;">
        <h3 style="margin:0; color:var(--color-primary);">📋 Selecionar Taxa ou Multa Padrão</h3>
        <button onclick="closeTaxasModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <div style="padding:20px; overflow-y:auto;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                
                <!-- Coluna Taxas -->
                <div>
                    <h4 style="color:#0f5132; border-bottom:2px solid #d1e7dd; padding-bottom:10px; margin-top:0;">🏛️ Taxas Administrativas</h4>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach($taxas_padrao['taxas'] as $t): ?>
                            <div onclick="selectTaxa('<?= $t['titulo'] ?>', '<?= $t['lei'] ?>', 'taxa', '<?= $t['valor'] ?? '' ?>')" 
                                 style="padding:15px; border:1px solid #e9ecef; border-radius:8px; cursor:pointer; transition:0.2s; background:#fff;">
                                <div style="display:flex; justify-content:space-between;">
                                    <div style="font-weight:bold; color:#146c43;"><?= $t['titulo'] ?></div>
                                    <div style="font-weight:bold; color:#146c43;">R$ <?= $t['valor'] ?? '0.00' ?></div>
                                </div>
                                <div style="font-size:0.85rem; color:#666; margin:4px 0;"><?= $t['desc'] ?></div>
                                <div style="font-size:0.8rem; background:#e9ecef; display:inline-block; padding:2px 6px; border-radius:4px; color:#555;">Eg: <?= $t['lei'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Coluna Multas -->
                <div>
                    <h4 style="color:#842029; border-bottom:2px solid #f8d7da; padding-bottom:10px; margin-top:0;">🚨 Infrações e Multas</h4>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach($taxas_padrao['multas'] as $t): ?>
                            <div onclick="selectTaxa('<?= $t['titulo'] ?>', '<?= $t['lei'] ?>', 'multa', '<?= $t['valor'] ?? '' ?>')" 
                                 style="padding:15px; border:1px solid #ffebe9; border-radius:8px; cursor:pointer; transition:0.2s; background:#fff;">
                                <div style="display:flex; justify-content:space-between;">
                                    <div style="font-weight:bold; color:#a50e0e;"><?= $t['titulo'] ?></div>
                                    <div style="font-weight:bold; color:#a50e0e;">R$ <?= $t['valor'] ?? '0.00' ?></div>
                                </div>
                                <div style="font-size:0.85rem; color:#666; margin:4px 0;"><?= $t['desc'] ?></div>
                                <div style="font-size:0.8rem; background:#fff3cd; display:inline-block; padding:2px 6px; border-radius:4px; color:#666;">Eg: <?= $t['lei'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            
            </div>
            
        <!-- Mobile Fix css -->
        <style>
            @media(max-width: 700px) {
                #modalTaxas > div > div:nth-child(2) > div { grid-template-columns: 1fr !important; }
            }
            #modalTaxas div[onclick]:hover { transform:translateY(-2px); box-shadow:0 4px 10px rgba(0,0,0,0.08); border-color:var(--color-primary); }
            /* Dialog backdrop */
            dialog::backdrop { background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px); }
        </style>
    </div>
</dialog>

<script>
function openPendenciaModal() {
    // Reset form for new entry
    document.getElementById('pendencia_id_input').value = '';
    
    if (typeof ClassicEditor !== 'undefined' && document.querySelector('#editor_pendencias').nextSibling) {
        const editorInstance = document.querySelector('#editor_pendencias').nextSibling.ckeditorInstance;
        if(editorInstance) editorInstance.setData('');
    } else {
        document.getElementById('editor_pendencias').value = '';
    }
    
    document.getElementById('btn_submit_pendencia').innerText = "Emitir Comunicado";
    document.getElementById('modalPendencia').showModal();
}

function closePendenciaModal() {
    document.getElementById('modalPendencia').close();
}

function editPendencia(id, texto) {
    // Populate the form ID
    document.getElementById('pendencia_id_input').value = id;
    
    // Populate the Text Editor
    if (typeof ClassicEditor !== 'undefined' && document.querySelector('#editor_pendencias').nextSibling) {
        // If CKEditor is active
        const editorInstance = document.querySelector('#editor_pendencias').nextSibling.ckeditorInstance;
        if(editorInstance) editorInstance.setData(texto);
    } else {
        // Fallback for textarea
        document.getElementById('editor_pendencias').value = texto;
    }
    
    // Change Button Text (Visual Feedback)
    document.getElementById('btn_submit_pendencia').innerText = "Salvar Alteração (Editar)";
    
    // Open Modal
    document.getElementById('modalPendencia').showModal();
}
</script>

</body>
<!-- Welcome Popup -->
<?php if($show_welcome_popup): ?>
<div id="welcomeRunning" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; justify-content:center; align-items:center; opacity:0; pointer-events:none; transition: opacity 0.5s ease;">
    <div style="background:white; padding:40px; border-radius:16px; width:90%; max-width:400px; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,0.2); transform: translateY(20px); transition: transform 0.5s ease;">
        <div style="font-size:3rem; margin-bottom:15px;">👷‍♂️</div>
        <h2 style="color:var(--color-primary); margin:0 0 10px 0;">Bem-vindo, Eng. Diego!</h2>
        <p style="color:var(--color-text-subtle); margin-bottom:25px; line-height:1.5;">O Painel Administrativo está pronto para uso.<br>Bom trabalho hoje!</p>
        <button onclick="closeWelcome()" class="btn-save" style="margin:0; width:100%;">Iniciar Gestão</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('welcomeRunning');
        const card = popup.querySelector('div');
        
        // Show
        setTimeout(() => {
            popup.style.opacity = '1';
            popup.style.pointerEvents = 'all';
            card.style.transform = 'translateY(0)';
        }, 100);

        window.closeWelcome = function() {
            popup.style.opacity = '0';
            popup.style.pointerEvents = 'none';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => { popup.remove(); }, 500);
        }
    });
</script>
<?php endif; ?>

<!-- MODAL NOTIFICAÇÕES (GLOBAL) -->
<dialog id="modalNotificacoes" style="border:none; border-radius:12px; width:90%; max-width:600px; padding:0; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
    <div style="background:var(--color-primary); color:white; padding:15px 20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.1rem;">🔔 Avisos e Atualizações</h3>
        <button onclick="document.getElementById('modalNotificacoes').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div style="padding:20px; max-height:60vh; overflow-y:auto;">
        
        <!-- 1. Novos Cadastros -->
        <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; color:#dc3545; margin-top:0;">📥 Solicitações Web (Pendentes)</h4>
        <?php 
        $notif_pre = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC LIMIT 5")->fetchAll();
        if(count($notif_pre) > 0): ?>
            <ul style="list-style:none; padding:0; margin-bottom:20px;">
                <?php foreach($notif_pre as $np): ?>
                    <li style="padding:10px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong><?= htmlspecialchars($np['nome']) ?></strong><br>
                            <small style="color:#888;"><?= date('d/m H:i', strtotime($np['data_solicitacao'])) ?> • <?= htmlspecialchars($np['tipo_servico']) ?></small>
                        </div>
                        <a href="?importar=true" style="font-size:0.8rem; background:#dc3545; color:white; padding:4px 8px; text-decoration:none; border-radius:4px;">Ver</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color:#aaa; font-style:italic; font-size:0.9rem;">Nenhuma solicitação pendente.</p>
        <?php endif; ?>

        <!-- 2. Últimas Movimentações -->
        <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; color:var(--color-primary); margin-top:20px;">🔄 Últimas Alterações de Processo</h4>
        <?php 
        // Busca últimas 10 movimentações de QUALQUER cliente, juntando com nome do cliente
        $sql_log = "SELECT m.*, c.nome as cliente_nome 
                    FROM processo_movimentos m 
                    JOIN clientes c ON m.cliente_id = c.id 
                    ORDER BY m.data_movimento DESC LIMIT 10";
        $notif_mov = $pdo->query($sql_log)->fetchAll();
        
        if(count($notif_mov) > 0): ?>
            <ul style="list-style:none; padding:0;">
                <?php foreach($notif_mov as $nm): ?>
                    <li style="padding:10px; border-bottom:1px solid #f0f0f0;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-weight:bold; color:#333; font-size:0.9rem;"><?= htmlspecialchars(explode(' ', $nm['cliente_nome'])[0]) ?>...</span>
                            <small style="color:#888;"><?= date('d/m H:i', strtotime($nm['data_movimento'])) ?></small>
                        </div>
                        <div style="font-size:0.85rem; color:#555;">
                            <?= htmlspecialchars($nm['titulo_fase']) ?>
                        </div>
                        <a href="?cliente_id=<?= $nm['cliente_id'] ?>" style="font-size:0.75rem; color:var(--color-primary); text-decoration:none; display:block; margin-top:4px;">Ir para Cliente →</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color:#aaa; font-style:italic; font-size:0.9rem;">Nenhuma atividade recente.</p>
        <?php endif; ?>

    </div>
</dialog>

<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.2); max-width:400px; width:90%;">
        <div style="font-size:3rem; margin-bottom:10px;">✅</div>
        <h3 id="successModalTitle" style="margin:0 0 10px 0; color:var(--color-primary);">Sucesso!</h3>
        <p id="successModalText" style="color:#666; margin-bottom:20px;">Operação realizada com sucesso.</p>
        <button onclick="closeSuccessModal()" class="btn-save" style="width:100%; margin:0;">OK</button>
    </div>
</div>

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

// FUNÇÃO PARA ABRIR MODAL DE APROVAÇÃO (CRÍTICO)
function openAprovarModal(id, nome, cpf) {
    document.getElementById('apr_id_pre').value = id;
    document.getElementById('apr_nome').value = nome;
    // Remove tudo que não é número do CPF para sugerir login
    const loginSugestao = cpf ? cpf.replace(/\D/g, '') : '';
    document.getElementById('apr_usuario').value = loginSugestao;
    document.getElementById('modalAprovarCadastro').showModal();
}

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

<!-- Modal Aprovar Cadastro (Movido para Footer para estar sempre disponível) -->
<dialog id="modalAprovarCadastro" style="border:none; border-radius:12px; padding:0; width:90%; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <div style="background:var(--color-primary); color:white; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.2rem;">✅ Aprovar e Finalizar</h3>
        <button onclick="document.getElementById('modalAprovarCadastro').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <form method="POST" style="padding:25px;">
        <input type="hidden" name="id_pre" id="apr_id_pre">
        
        <div class="form-group" style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Nome do Cliente</label>
            <input type="text" name="nome_final" id="apr_nome" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
        </div>
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Usuário (Login)</label>
                <input type="text" name="usuario_final" id="apr_usuario" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#f9f9f9;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Senha Inicial</label>
                <input type="text" name="senha_final" value="mudar123" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
        </div>
        
        <hr style="margin:20px 0; border-top:1px solid #eee;">
        
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" name="btn_confirmar_aprovacao" class="btn-save" style="width:100%; padding:12px; background:#198754; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">🚀 Confirmar e Criar Cliente</button>
        </div>
    </form>
</dialog>
</body>
</html>
