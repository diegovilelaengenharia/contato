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

// Buscar Movimentos (Timeline)
$stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
$stmt->execute([$cliente_id]);
$timeline = $stmt->fetchAll();

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
        }

        body.dark-mode {
            --color-bg: #121212;
            --color-surface: #1e1e1e;
            --color-text: #e0e0e0;
            --color-text-subtle: #a0a0a0;
            --color-border: #333333;
            --shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        body { background-color: var(--color-bg); color: var(--color-text); font-family: 'Outfit', sans-serif; margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; }
        .container { width: min(1000px, 95%); margin: 40px auto; }
        
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
    </style>
</head>
<body>
    <div class="container">
        <header class="card" style="padding-bottom: 10px;">
            <div class="header-panel">
                <div>
                    <h1>Olá, <?= htmlspecialchars($_SESSION['cliente_nome']) ?></h1>
                    <span class="badge-panel">Acompanhamento Online</span>
                </div>
                <div style="display:flex; align-items:center;">
                    <button class="btn-toggle-theme" onclick="toggleTheme()">🌓 Tema</button>
                    <a href="logout.php" class="btn-logout">Sair</a>
                </div>
            </div>

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
        <div class="nav-grid">
            <button class="nav-btn active" onclick="switchView('timeline', this)">
                <span class="nav-icon">📊</span>
                Linha do Tempo & Arquivos
            </button>
            <button class="nav-btn" onclick="switchView('dados', this)">
                <span class="nav-icon">📋</span>
                Meus Dados Cadastrais
            </button>
            <button class="nav-btn" onclick="switchView('pendencias', this)">
                <span class="nav-icon">⚠️</span>
                Quadro de Pendências
            </button>
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
                                    <td><?= nl2br(htmlspecialchars($t['descricao'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--color-text-subtle);">Nenhuma movimentação registrada.</p>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2 style="margin-top:0;">Arquivos e Documentos</h2>
                <?php if ($drive_folder_id): ?>
                    <div class="drive-embed-container">
                        <iframe src="https://drive.google.com/embeddedfolderview?id=<?= htmlspecialchars($drive_folder_id) ?>#list" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <p style="color:var(--color-text-subtle);">A pasta de documentos ainda não foi vinculada.</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- VIEW 2: DADOS CADASTRAIS -->
        <div id="view-dados" class="view-section">
            <section class="card">
                <h2 style="margin-top:0; border-bottom:1px solid var(--color-border); padding-bottom:15px;">Dados do Processo</h2>
                
                <div class="info-grid">
                    <div class="section-header">👤 Requerente</div>
                    <div class="info-item"><label>Nome / Razão Social</label><div><?= htmlspecialchars($_SESSION['cliente_nome']) ?></div></div>
                    <div class="info-item"><label>CPF / CNPJ</label><div><?= htmlspecialchars($detalhes['cpf_cnpj']??'-') ?></div></div>
                    <div class="info-item"><label>Email</label><div><?= htmlspecialchars($detalhes['contato_email']??'-') ?></div></div>
                    <div class="info-item"><label>Telefone</label><div><?= htmlspecialchars($detalhes['contato_tel']??'-') ?></div></div>

                    <div class="section-header">🏠 Imóvel</div>
                    <div class="info-item"><label>Endereço</label><div><?= htmlspecialchars($detalhes['endereco_imovel']??'-') ?></div></div>
                    <div class="info-item"><label>Inscrição Imob.</label><div><?= htmlspecialchars($detalhes['inscricao_imob']??'-') ?></div></div>
                    
                    <div class="section-header">👷 Responsável Técnico</div>
                    <div class="info-item"><label>Profissional</label><div><?= htmlspecialchars($detalhes['resp_tecnico']??'-') ?></div></div>
                    <div class="info-item"><label>Registro</label><div><?= htmlspecialchars($detalhes['registro_prof']??'-') ?></div></div>
                </div>
            </section>
        </div>

        <!-- VIEW 3: PENDÊNCIAS -->
        <div id="view-pendencias" class="view-section">
            <section class="card">
                <h2 style="margin-top:0;">Quadro de Avisos e Pendências</h2>
                <?php if(!empty($detalhes['texto_pendencias'])): ?>
                    <div class="pendency-board">
                        <div class="pendency-text"><?= nl2br(htmlspecialchars($detalhes['texto_pendencias'])) ?></div>
                    </div>
                <?php else: ?>
                    <div style="padding:40px; text-align:center; color:var(--color-text-subtle); background:rgba(0,0,0,0.02); border-radius:12px;">
                        <div style="font-size:2rem;">✅</div>
                        <p>Não há pendências registradas no momento.</p>
                    </div>
                <?php endif; ?>

                <?php if(!empty($detalhes['link_doc_pendencias'])): ?>
                    <div style="margin-top:20px;">
                        <a href="<?= htmlspecialchars($detalhes['link_doc_pendencias']) ?>" target="_blank" class="nav-btn active" style="text-decoration:none; display:inline-flex; flex-direction:row; padding:15px 30px;">
                             📂 Acessar Pasta de Pendências no Drive
                        </a>
                    </div>
                <?php endif; ?>
            </section>
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
            btn.classList.add('active');
        }
    </script>
</body>
</html>
