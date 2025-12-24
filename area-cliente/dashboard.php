<?php
session_start();
require 'db.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// Buscar Detalhes
$stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmtDet->execute([$cliente_id]);
$detalhes = $stmtDet->fetch();

// Buscar Dados Login (Nome, Usuario)
$stmtCli = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmtCli->execute([$cliente_id]);
$cliente_ativo = $stmtCli->fetch();

// Montar Endereço Completo
$end_parts = [];
if(!empty($detalhes['imovel_rua'])) $end_parts[] = $detalhes['imovel_rua'];
if(!empty($detalhes['imovel_numero'])) $end_parts[] = $detalhes['imovel_numero'];
if(!empty($detalhes['imovel_bairro'])) $end_parts[] = "Bairro " . $detalhes['imovel_bairro'];
if(!empty($detalhes['imovel_complemento'])) $end_parts[] = $detalhes['imovel_complemento'];
if(!empty($detalhes['imovel_cidade'])) $end_parts[] = $detalhes['imovel_cidade'] . "/" . $detalhes['imovel_uf'];

$endereco_final = !empty($end_parts) ? implode(', ', $end_parts) : ($detalhes['endereco_imovel'] ?? 'Endereço não cadastrado');

// Buscar Movimentos (Timeline)
$stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
$stmt->execute([$cliente_id]);
$timeline = $stmt->fetchAll();

// Buscar Financeiro
$stmtFin = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
$stmtFin->execute([$cliente_id]);
$financeiro = $stmtFin->fetchAll();

$total_hon = 0; $total_taxas = 0; $total_pago = 0; $total_pendente = 0;
foreach($financeiro as $item) {
    if($item['categoria']=='honorarios') $total_hon += $item['valor'];
    else $total_taxas += $item['valor'];
    
    if($item['status']=='pago') $total_pago += $item['valor'];
    elseif($item['status']=='pendente' || $item['status']=='atrasado') $total_pendente += $item['valor'];
}

// Função ID Drive
function getDriveFolderId($url) {
    if (preg_match('/folders\/([a-zA-Z0-9-_]+)/', $url, $matches)) return $matches[1];
    if (preg_match('/id=([a-zA-Z0-9-_]+)/', $url, $matches)) return $matches[1];
    return null;
}

$drive_folder_id = null;
if (!empty($detalhes['link_drive_pasta'])) {
    $drive_folder_id = getDriveFolderId($detalhes['link_drive_pasta']);
}

$drive_pagamentos_id = null;
if (!empty($detalhes['link_pasta_pagamentos'])) {
    $drive_pagamentos_id = getDriveFolderId($detalhes['link_pasta_pagamentos']);
}

// --- LÓGICA DE UPLOAD DE PENDÊNCIA (MÚLTIPLOS ARQUIVOS) ---
$msg_upload = '';
if (isset($_POST['btn_upload_pendencia'])) {
    $pend_id_upload = $_POST['upload_pendencia_id'];
    
    // Verifica se arquivos foram enviados
    if (isset($_FILES['arquivo_pendencia'])) {
        $files = $_FILES['arquivo_pendencia'];
        $processed_count = 0;
        $error_count = 0;
        
        // Normalizar array de arquivos se for múltiplo ou único transformado
        $file_names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $file_tmps  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $file_errs  = is_array($files['error']) ? $files['error'] : [$files['error']];
        
        // Pasta de uploads
        $upload_dir = __DIR__ . '/uploads/pendencias/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        for ($i = 0; $i < count($file_names); $i++) {
            if ($file_errs[$i] == 0) {
                $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                $filename = $file_names[$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                     // Gera nome único
                    $new_name = $pend_id_upload . '_' . time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
                    $dest_path = $upload_dir . $new_name;
                    $rel_path = 'uploads/pendencias/' . $new_name;
                    
                    if (move_uploaded_file($file_tmps[$i], $dest_path)) {
                        try {
                            // Inserir na tabela de arquivos
                            $stmtUp = $pdo->prepare("INSERT INTO processo_pendencias_arquivos (pendencia_id, arquivo_nome, arquivo_path, data_upload) VALUES (?, ?, ?, NOW())");
                            $stmtUp->execute([$pend_id_upload, $filename, $rel_path]);
                            
                            // 2025-12 UPDATE: Mudar status para 'anexado' (azul)
                            $pdo->prepare("UPDATE processo_pendencias SET status='anexado' WHERE id=?")->execute([$pend_id_upload]);
                            
                            $processed_count++;
                        } catch(PDOException $e) { $error_count++; }
                    } else { $error_count++; }
                } else { $error_count++; }
            }
        }
        
        if ($processed_count > 0) {
            $msg_upload = "success|{$processed_count} arquivo(s) anexado(s) com sucesso!";
            
            try {
                // Registrar na timeline/avisos para o admin ver
                $desc_pend = $pdo->query("SELECT descricao FROM processo_pendencias WHERE id=$pend_id_upload")->fetchColumn();
                $titulo_aviso = "📎 Novo Anexo Enviado";
                $desc_aviso = "O cliente enviou {$processed_count} arquivo(s) para a pendência: " . strip_tags($desc_pend);
                
                $pdo->prepare("INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo) VALUES (?, ?, NOW(), ?, 'upload')")->execute([$cliente_id, $titulo_aviso, $desc_aviso]);
            } catch(Exception $ex) {}
            
            // PRG - Redirecionar para limpar POST
            header("Location: dashboard.php?msg=upload_success");
            exit;
            
        } else {
            $msg_upload = "error|Falha ao enviar arquivos. Verifique formatos permitidos.";
        }

    } else {
        $msg_upload = "error|Nenhum arquivo selecionado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel do Cliente | Vilela Engenharia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <style>
        :root {
            /* Tema Verde Claro */
            --color-bg: #f0f8f5; 
            --color-surface: #ffffff;
            --color-text: #2f3e36;
            --color-text-subtle: #5f7a6c;
            --color-border: #dbece5;
            --color-primary: #146c43;
            --color-primary-light: #d1e7dd;
            --shadow: 0 4px 20px rgba(20, 108, 67, 0.08);
            --header-bg: #146c43;
            
            /* Semantic Colors (Light) */
            --bg-warning: #fff3cd;
            --text-warning: #856404;
            --border-warning: #ffeeba;
            --bg-success: #d4edda;
            --text-success: #155724;
            --bg-danger: #f8d7da;
            --text-danger: #721c24;
        }

        body.dark-mode {
            --color-bg: #121212;
            --color-surface: #1e1e1e;
            --color-text: #e0e0e0;
            --color-text-subtle: #a0a0a0;
            --color-border: #333333;
            --shadow: 0 4px 20px rgba(0,0,0,0.3);
            
            /* Semantic Colors (Dark) */
            --bg-warning: #3e300a;
            --text-warning: #ffc107;
            --border-warning: #5e4b10;
            --bg-success: #0b3d26;
            --text-success: #d4edda;
            --bg-danger: #3e1015;
            --text-danger: #f8d7da;
        }

        body { background-color: var(--color-bg); color: var(--color-text); font-family: 'Outfit', sans-serif; margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; }
        .container { width: min(1000px, 95%); margin: 40px auto; }
        
        /* Badges */
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; display: inline-block; }
        .status-badge.st-pago { background: var(--bg-success); color: var(--text-success); }
        .status-badge.st-pend { background: var(--bg-warning); color: var(--text-warning); }
        .status-badge.st-atra { background: var(--bg-danger); color: var(--text-danger); }

        .header-panel { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: var(--color-surface); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); margin-bottom: 30px; border: 1px solid var(--color-border); }
        
        h1 { margin: 0; font-size: clamp(1.5rem, 3vw, 2rem); color: var(--color-text); letter-spacing: -1px; }
        .badge-panel { background: var(--color-primary-light); color: var(--color-primary); padding: 5px 15px; border-radius: 99px; font-size: 0.85rem; font-weight: 700; display: inline-block; margin-top: 5px; }
        
        .btn-logout { color: #d32f2f; text-decoration: none; font-weight: 600; padding: 8px 16px; border: 1px solid #d32f2f; border-radius: 12px; transition: 0.2s; }
        .btn-logout:hover { background: #fee; }

        .btn-toggle-theme { background: none; border: 1px solid var(--color-border); color: var(--color-text); padding: 8px 12px; border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: inherit; font-size: 0.9rem; margin-right: 10px; }
        .btn-toggle-theme:hover { background: var(--color-border); }

        /* Modern Stepper */
        .client-stepper { display: flex; justify-content: space-between; margin-top: 10px; padding-bottom: 20px; position: relative; overflow-x: auto; gap:20px; }
        .client-stepper::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 3px; background: var(--color-border); z-index: 0; }
        
        .s-item { position: relative; z-index: 1; text-align: center; min-width: 80px; display: flex; flex-direction: column; align-items: center; }
        .s-circle { width: 32px; height: 32px; background: var(--color-surface); border: 2px solid var(--color-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--color-text-subtle); font-size: 14px; transition: 0.3s; }
        .s-label { margin-top: 8px; font-size: 0.75rem; color: var(--color-text-subtle); max-width: 100px; line-height: 1.2; font-weight: 500; transition: 0.3s;}
        
        .s-item.active .s-circle { background: white; border-color: var(--color-primary); color: var(--color-primary); box-shadow: 0 0 0 4px rgba(20,108,67,0.2); transform: scale(1.1); }
        .s-item.active .s-label { color: var(--color-primary); font-weight: 700; transform: scale(1.05); }
        .s-item.completed .s-circle { background: var(--color-primary); border-color: var(--color-primary); color: white; }
        .s-item.completed .s-label { color: var(--color-primary); opacity: 0.8; }

        /* Nav Buttons */
        .nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 20px; border-radius: 12px; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--color-text); background: var(--color-surface); box-shadow: var(--shadow); border: 1px solid var(--color-border); }
        .nav-btn:hover { transform: translateY(-3px); border-color: var(--color-primary); }
        .nav-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .nav-icon { font-size: 1.5rem; }

        /* Views */
        .view-section { display: none; animation: fadeIn 0.3s ease; }
        .view-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Table & Grid */
        .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .history-table th, .history-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--color-border); }
        .history-table th { font-weight: 700; color: var(--color-text-subtle); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }
        .info-item label { font-size: 0.8rem; color: var(--color-text-subtle); font-weight: 700; display: block; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item div { font-size: 1.05rem; color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 8px; font-weight: 500; }
        
        .section-header { grid-column: 1 / -1; font-size: 1.2rem; color: var(--color-primary); margin: 20px 0 10px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        /* Pendency Board */
        .pendency-board { background: #fffbf2; border: 2px solid #ffc107; border-radius: 12px; padding: 30px; color: #5c4b1e; }
        .pendency-text { white-space: pre-wrap; line-height: 1.6; font-size: 1.05rem; }

        .drive-embed-container { width: 100%; height: 600px; background: var(--color-surface); border-radius: 12px; border: 1px solid var(--color-border); overflow: hidden; margin-top: 20px; }
        iframe { border: 0; width: 100%; height: 100%; }

        /* Financeiro Styles */
        .fin-summary { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
        .fin-card { flex: 1; min-width: 150px; background: #fff; border: 1px solid var(--color-border); padding: 15px; border-radius: 12px; text-align: center; }
        .fin-card strong { display: block; font-size: 0.9rem; color: var(--color-text-subtle); margin-bottom: 5px; }
        .fin-card span { font-size: 1.25rem; font-weight: 700; color: var(--color-primary); }
        .fin-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .fin-table th, .fin-table td { padding: 12px; border-bottom: 1px solid var(--color-border); text-align: left; }
        .fin-table th { background: rgba(0,0,0,0.02); color: var(--color-text-subtle); }
        .status-badge { padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .st-pago { background: #d1e7dd; color: #0f5132; }
        .st-pend { background: #fff3cd; color: #856404; }
        .st-atra { background: #f8d7da; color: #842029; }

        /* Modal Popup */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s; }
        .modal-content { background: var(--color-surface); width: 90%; max-width: 1000px; height: 85vh; border-radius: 16px; position: relative; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; background: var(--color-bg); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-text); }
        .modal-body { flex: 1; padding: 0; }
        .modal-iframe { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>
    <div class="container">
    <div class="container">
        
        <!-- HEADER / CARD RESUMO -->
        <div class="card" style="display:flex; align-items:center; gap:30px; padding:30px; flex-wrap:wrap; position:relative;">
            
            <!-- Botões Flutuantes (Mobile/Theme) -->
            <div style="position:absolute; top:20px; right:20px; display:flex; gap:10px;">
                <button class="btn-icon-mobile" onclick="toggleTheme()" title="Alternar Tema">🌓</button>
                <a href="logout.php" class="btn-logout btn-icon-mobile" title="Sair">🚪</a>
            </div>

            <!-- Avatar / Iniciais -->
            <div style="width:90px; height:90px; background:var(--color-primary-light); color:var(--color-primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2.2rem; font-weight:800; border:4px solid var(--color-surface); box-shadow:0 4px 15px rgba(0,0,0,0.1); min-width:90px;">
                <?= strtoupper(substr($cliente_ativo['nome'], 0, 1)) ?>
            </div>

            <div style="flex:1; min-width:250px;">
                <h2 style="margin:0 0 5px 0; color:var(--color-text); font-size:1.6rem;">Olá, <?= htmlspecialchars(explode(' ', $cliente_ativo['nome'])[0]) ?></h2>
                <span class="badge-panel" style="margin-bottom:15px; display:inline-block;"><?= htmlspecialchars($endereco_final) ?></span>
                
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:15px;">
                    <div>
                        <small style="display:block; color:var(--color-text-subtle); text-transform:uppercase; font-size:0.7rem; font-weight:bold;">Login de Acesso</small>
                        <span style="font-family:monospace; font-size:1rem; color:var(--color-primary);"><?= htmlspecialchars($cliente_ativo['usuario']) ?></span>
                    </div>
                    <div>
                        <small style="display:block; color:var(--color-text-subtle); text-transform:uppercase; font-size:0.7rem; font-weight:bold;">Status</small>
                        <span style="background:var(--bg-success); color:var(--text-success); padding:2px 8px; border-radius:4px; font-weight:bold; font-size:0.85rem;">Ativo</span>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div style="display:flex; flex-direction:column; gap:10px; align-self:flex-start; margin-top:10px;">
                <a href="exportar_resumo.php" target="_blank" class="btn-resumo-destaque">
                    📄 Resumo do Processo
                </a>
            </div>

            <style>
                .btn-resumo-destaque {
                    background: linear-gradient(135deg, #146c43 0%, #0d462b 100%);
                    color: white;
                    text-decoration: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    font-weight: 700;
                    box-shadow: 0 4px 10px rgba(20, 108, 67, 0.2);
                    text-align: center;
                    transition: transform 0.2s;
                    font-size: 0.9rem;
                    display: block;
                }
                .btn-resumo-destaque:hover { transform: translateY(-2px); }
            </style>
        </div>
                    transition: transform 0.2s;
                    display: flex; align-items: center; gap: 8px;
                    font-size: 0.95rem;
                    border: 1px solid rgba(255,255,255,0.2);
                }
                .btn-resumo-destaque:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(20, 108, 67, 0.3); }

                .mobile-only { display: none; }

                @media (max-width: 600px) {
                    .desktop-only { display: none; }
                    .mobile-only { display: inline; }
                    .btn-icon-mobile { padding: 8px 12px; font-size: 1.2rem; background: transparent; border: 1px solid var(--color-border); border-radius: 8px; text-decoration: none; color: var(--color-text); cursor: pointer; }
                    .header-panel { flex-direction: column; align-items: flex-start; gap: 15px; }
                    .header-actions { width: 100%; justify-content: space-between; gap: 5px; }
                    .btn-resumo-destaque { flex: 1; justify-content: center; }
                }
            </style>

            <!-- Stepper -->
            <?php 
                $etapa_atual = $detalhes['etapa_atual'] ?? '';
                $mapa_fases = [
                    "Abertura de Processo (Guichê)" => "Guichê", "Fiscalização (Parecer Fiscal)" => "Fiscalização",
                    "Triagem (Documentos Necessários)" => "Triagem", "Comunicado de Pendências (Triagem)" => "Pendências",
                    "Análise Técnica (Engenharia)" => "Engenharia", "Comunicado (Pendências e Taxas)" => "Taxas",
                    "Confecção de Documentos" => "Docs", "Avaliação (ITBI/Averbação)" => "Avaliação",
                    "Processo Finalizado (Documentos Prontos)" => "Finalizado"
                ];
                $keys = array_keys($mapa_fases);
                $found_index = array_search($etapa_atual, $keys);
                if($found_index === false) $found_index = -1;
                $i = 0;
            ?>
            <div class="client-stepper">
                <?php foreach($mapa_fases as $full => $label): 
                    $status_class = '';
                    if ($i < $found_index) $status_class = 'completed';
                    else if ($i === $found_index) $status_class = 'active';
                ?>
                    <div class="s-item <?= $status_class ?>">
                        <div class="s-circle"><?= ($i < $found_index) ? '✔' : ($i + 1) ?></div>
                        <span class="s-label"><?= $label ?></span>
                    </div>
                <?php $i++; endforeach; ?>
            </div>
        </header>

        <!-- Navegação Principal (3 Botões) -->
        <!-- Navegação Principal -->
        <div class="nav-grid">
            <button class="nav-btn active" onclick="switchView('timeline', this)">
                <span class="nav-icon">📊</span>
                Linha do Tempo
            </button>
            <button class="nav-btn" onclick="switchView('financeiro', this)">
                <span class="nav-icon">💰</span>
                Financeiro & Taxas
            </button>
            <button class="nav-btn" onclick="switchView('pendencias', this)">
                <span class="nav-icon">⚠️</span>
                Pendências
            </button>
            <button class="nav-btn" onclick="openDriveModal()">
                <span class="nav-icon">☁️</span>
                Documentos na Nuvem
            </button>
        </div>

        <!-- DRIVE MODAL POPUP -->
        <div id="drive-modal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 style="margin:0;">📂 Pasta de Arquivos do Processo</h3>
                    <button class="modal-close" onclick="closeDriveModal()">×</button>
                </div>
                <div class="modal-body">
                    <?php if ($drive_folder_id): ?>
                        <iframe class="modal-iframe" src="https://drive.google.com/embeddedfolderview?id=<?= htmlspecialchars($drive_folder_id) ?>#list"></iframe>
                    <?php else: ?>
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color:var(--color-text-subtle);">
                            <p>Pasta do Drive ainda não vinculada a este processo.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- VIEW 1: TIMELINE (Histórico + Drive) -->
        <div id="view-timeline" class="view-section active">
            <section class="card">
                <h2 style="margin-top:0;">Histórico do Processo</h2>
                <?php if(count($timeline) > 0): ?>
                    <div style="overflow-x:auto;">
                        <table class="history-table">
                            <thead>
                                <tr><th>Data</th><th>Fase</th><th>Descrição/Detalhes</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($timeline as $t): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($t['data_movimento'])) ?></td>
                                    <td><strong><?= htmlspecialchars($t['titulo_fase']) ?></strong></td>
                                    <td><?= $t['descricao'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--color-text-subtle);">Nenhuma movimentação registrada.</p>
                <?php endif; ?>
            </section>


        </div>



        <!-- VIEW 3: PENDÊNCIAS (Reformulada) -->
        <div id="view-pendencias" class="view-section">
            <section class="card">
                <h2 style="margin-top:0; margin-bottom:20px;">Avisos e Pendências do Projeto</h2>
                <?php 
                $stmtPend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id=? ORDER BY status ASC, id DESC");
                $stmtPend->execute([$cliente_id]);
                $pendencias = $stmtPend->fetchAll();
                
                // Buscar Arquivos (Novo Sistema)
                $stmtArq = $pdo->prepare("SELECT pendencia_id, id, arquivo_nome, arquivo_path, data_upload FROM processo_pendencias_arquivos WHERE pendencia_id IN (SELECT id FROM processo_pendencias WHERE cliente_id=?)");
                $stmtArq->execute([$cliente_id]);
                $arquivos_raw = $stmtArq->fetchAll();
                $arquivos_por_pendencia = [];
                foreach($arquivos_raw as $arq) {
                    $arquivos_por_pendencia[$arq['pendencia_id']][] = $arq;
                }

                if(count($pendencias) > 0): ?>
                    <div class="pendency-list">
                        <?php foreach($pendencias as $p): 
                             $is_resolved = ($p['status'] === 'resolvido');
                             $status_text = $is_resolved ? 'RESOLVIDO' : 'PENDENTE';
                             
                             $icon = $is_resolved ? '✅' : '⚠️';
                             $border_color = $is_resolved ? 'var(--color-primary)' : '#ffc107';
                             $bg_color = $is_resolved ? '#f8fff9' : '#fffbf2';
                             $opacity = $is_resolved ? '0.7' : '1';
                             
                             $descricao_html = $p['descricao']; 
                             
                             // Arquivos
                             $arquivos = $arquivos_por_pendencia[$p['id']] ?? [];
                             // Fallback Legado
                             if (!empty($p['arquivo_path']) && empty($arquivos)) {
                                 $arquivos[] = ['arquivo_nome' => $p['arquivo_nome'] ?? 'Anexo (Antigo)', 'arquivo_path' => $p['arquivo_path']];
                             }
                        ?>
                        <div class="pendency-card" style="border-left: 5px solid <?= $border_color ?>; background: <?= $bg_color ?>; opacity: <?= $opacity ?>;">
                            <div class="p-header" onclick="openPendencyModal('<?= addslashes(str_replace(["\r", "\n"], '', $descricao_html)) ?>')">
                                <span class="p-date"><?= date('d/m/Y', strtotime($p['data_criacao'])) ?></span>
                                <span class="status-badge <?= $is_resolved ? 'st-pago' : 'st-pend' ?>"><?= $status_text ?></span>
                            </div>
                            
                            <div class="p-body" onclick="openPendencyModal('<?= addslashes(str_replace(["\r", "\n"], '', $descricao_html)) ?>')">
                                <div class="p-content">
                                    <?= $descricao_html ?>
                                </div>
                            </div>
                            
                            <div class="p-footer">
                                <span style="font-size:0.85rem; color:var(--color-text-subtle); display:flex; align-items:center; gap:5px; flex-grow:1;" onclick="openPendencyModal('<?= addslashes(str_replace(["\r", "\n"], '', $descricao_html)) ?>')">
                                    Clique para ver detalhes
                                </span>
                                
                                <div class="p-actions" style="display:flex; flex-direction:column; align-items:flex-end; gap:5px;">
                                    <?php if(!empty($arquivos)): ?>
                                        <?php foreach($arquivos as $arq): ?>
                                            <a href="<?= htmlspecialchars($arq['arquivo_path']) ?>" target="_blank" class="btn-file-view" title="<?= $arq['arquivo_nome'] ?>">
                                                📎 <?= (strlen($arq['arquivo_nome']) > 20 ? substr($arq['arquivo_nome'],0,20).'...' : $arq['arquivo_nome']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if(!$is_resolved): ?>
                                        <button onclick="openUploadModal(<?= $p['id'] ?>)" class="btn-file-upload">
                                            📤 Anexar <?= !empty($arquivos)?'(Mais)':'' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding:40px; text-align:center; color:var(--color-text-subtle); background:rgba(0,0,0,0.02); border-radius:12px;">
                        <div style="font-size:3rem; margin-bottom:15px;">🎉</div>
                        <p style="font-size:1.1rem;">Tudo certo! Não há pendências no momento.</p>
                    </div>
                <?php endif; ?>

                <!-- PENDENCY MODAL -->
                <div id="pendency-modal" class="modal-overlay">
                    <div class="modal-content" style="height:auto; max-height:80vh; max-width:600px;">
                        <div class="modal-header">
                            <h3 style="margin:0;">Detalhes da Pendência</h3>
                            <button class="modal-close" onclick="closePendencyModal()">×</button>
                        </div>
                        <div class="modal-body" style="padding:30px; overflow-y:auto;">
                            <div id="pendency-modal-content" class="ck-content"></div>
                        </div>
                    </div>
                </div>

                <!-- UPLOAD MODAL -->
                <div id="upload-modal" class="modal-overlay">
                    <div class="modal-content" style="height:auto; max-width:400px; overflow:visible;">
                        <div class="modal-header">
                            <h3 style="margin:0;">📤 Anexar Arquivo</h3>
                            <button class="modal-close" onclick="closeUploadModal()">×</button>
                        </div>
                        <div class="modal-body" style="padding:25px;">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="upload_pendencia_id" id="upload_pendencia_id">
                                <p style="margin-top:0; color:#666; font-size:0.9rem;">Selecione o comprovante ou documento para anexar a esta pendência.</p>
                                
                                <div style="margin-bottom:20px;">
                                    <input type="file" name="arquivo_pendencia[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                                    <small style="color:#888;">Você pode selecionar múltiplos arquivos de uma vez.</small>
                                </div>
                                
                                <div style="text-align:right;">
                                    <button type="submit" name="btn_upload_pendencia" class="nav-btn active" style="padding:10px 20px; font-size:0.95rem; width:100%;">Confirmar Envio</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <style>
                    .pendency-list { display: grid; gap: 15px; }
                    .pendency-card { 
                        padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
                        transition: transform 0.2s, box-shadow 0.2s;
                        border: 1px solid rgba(0,0,0,0.05);
                        border-left-width: 5px; /* Importante para override */
                        display: flex; flex-direction: column;
                    }
                    .pendency-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
                    
                    .p-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; cursor: pointer; }
                    .p-date { font-size: 0.85rem; color: var(--color-text-subtle); font-weight: 600; }
                    
                    .p-body { margin-bottom: 15px; cursor: pointer; }
                    .p-content { 
                        font-size: 1rem; color: var(--color-text); line-height: 1.5; 
                        max-height: 60px; overflow: hidden; position: relative;
                        text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
                    }
                    
                    .p-footer { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); }
                    
                    .btn-file-upload { background: var(--color-primary); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; transition:0.2s; }
                    .btn-file-upload:hover { background: #0d462b; }
                    
                    .btn-file-view { background: #e2e6ea; color: #495057; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; transition:0.2s; }
                    .btn-file-view:hover { background: #dbe2e8; }

                    /* CKEditor Content Styles in Modal */
                    .ck-content ul { padding-left: 20px; }
                    .ck-content p { margin-bottom: 10px; }
                    .ck-content a { color: var(--color-primary); text-decoration: underline; }
                </style>
                
                <!-- Toast Logic for PHP Message -->
                <?php if(!empty($msg_upload)): 
                    list($type, $text) = explode('|', $msg_upload);
                    $bgColor = ($type == 'success') ? '#4caf50' : '#f44336';
                ?>
                <div id="toast-msg" style="position:fixed; bottom:20px; right:20px; background:<?= $bgColor ?>; color:white; padding:15px 25px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.2); z-index:9999; animation: slideIn 0.3s ease-out;">
                    <?= $text ?>
                </div>
                <script>
                    setTimeout(() => { document.getElementById('toast-msg').style.display = 'none'; }, 5000);
                </script>
                <?php endif; ?>

                <script>
                    function openPendencyModal(htmlContent) {
                        document.getElementById('pendency-modal-content').innerHTML = htmlContent;
                        document.getElementById('pendency-modal').classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                    function closePendencyModal() {
                        document.getElementById('pendency-modal').classList.remove('active');
                        document.body.style.overflow = '';
                    }
                    
                    function openUploadModal(id) {
                        document.getElementById('upload_pendencia_id').value = id;
                        document.getElementById('upload-modal').classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                    function closeUploadModal() {
                        document.getElementById('upload-modal').classList.remove('active');
                        document.body.style.overflow = '';
                    }
                    
                    // Close upload on outside click
                    document.getElementById('upload-modal').addEventListener('click', function(e) {
                         if (e.target === this) closeUploadModal();
                    });
                </script>



            </section>
        </div>
        
        <!-- VIEW 4: FINANCEIRO -->
        <div id="view-financeiro" class="view-section">
            <section class="card">
                <h2 style="margin-top:0;">Histórico Financeiro e Taxas</h2>
                
                <div class="fin-summary">
                    <div class="fin-card">
                        <strong>Total Honorários</strong>
                        <span>R$ <?= number_format($total_hon, 2, ',', '.') ?></span>
                    </div>
                    <div class="fin-card">
                        <strong>Total Taxas</strong>
                        <span>R$ <?= number_format($total_taxas, 2, ',', '.') ?></span>
                    </div>
                    <div class="fin-card" style="border-color: #d1e7dd; background: #f0fdf4;">
                        <strong>Pago</strong>
                        <span style="color: #198754;">R$ <?= number_format($total_pago, 2, ',', '.') ?></span>
                    </div>
                    <div class="fin-card" style="border-color: #f8d7da; background: #fdf2f2;">
                        <strong>Pendente</strong>
                        <span style="color: #dc3545;">R$ <?= number_format($total_pendente, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th>Vencimento</th>
                                <th>Categoria</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($financeiro) > 0): ?>
                                <?php foreach($financeiro as $fin): 
                                    $cls = '';
                                    if($fin['status']=='pago')$cls='st-pago';
                                    elseif($fin['status']=='atrasado')$cls='st-atra';
                                    else $cls='st-pend';
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($fin['data_vencimento'])) ?></td>
                                    <td><?= ucfirst($fin['categoria']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($fin['descricao']) ?>
                                        <?php if(!empty($fin['link_comprovante'])): ?>
                                            <a href="<?= htmlspecialchars($fin['link_comprovante']) ?>" target="_blank" style="margin-left:5px; font-size:0.8rem;">📎 Comprovante</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>R$ <?= number_format($fin['valor'], 2, ',', '.') ?></td>
                                    <td><span class="status-badge <?= $cls ?>"><?= strtoupper($fin['status']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding:20px;">Nenhum registro financeiro encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


        </div>
    </div>


    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        function switchView(viewName, btn) {
            // Hide all views
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.getElementById('view-' + viewName).classList.add('active');

            // Deactivate all buttons
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            // Activate clicked button
            if (btn) btn.classList.add('active');
        }

        function openDriveModal() {
            document.getElementById('drive-modal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Stop scrolling
        }
        function closeDriveModal() {
            document.getElementById('drive-modal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal on outside click
        document.getElementById('drive-modal').addEventListener('click', function(e) {
            if (e.target === this) closeDriveModal();
        });
    </script>
</body>
</html>
