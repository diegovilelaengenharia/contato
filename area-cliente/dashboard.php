<?php
session_start();
require 'db.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// Buscar Detalhes e Link do Drive
$stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmtDet->execute([$cliente_id]);
$detalhes = $stmtDet->fetch();

// Buscar Movimentos (Timeline)
$stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
$stmt->execute([$cliente_id]);
$timeline = $stmt->fetchAll();

// Função Auxiliar para Extrair ID do Drive
function getDriveFolderId($url) {
    if (preg_match('/folders\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return $matches[1];
    }
    if (preg_match('/id=([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return $matches[1];
    }
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
            --color-bg: #f4f7f6;
            --color-surface: #ffffff;
            --color-text: #333333;
            --color-text-subtle: #666666;
            --color-border: #e0e0e0;
            --color-primary: #198754;
            --color-primary-strong: #146c43;
            --shadow: 0 4px 20px rgba(0,0,0,0.05);
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
        
        .header-panel { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        
        .card { background: var(--color-surface); padding: 32px; border-radius: 12px; box-shadow: var(--shadow); margin-bottom: 30px; border: 1px solid var(--color-border); }
        
        h1 { margin: 0; font-size: clamp(1.5rem, 3vw, 2rem); color: var(--color-text); }
        .badge-panel { background: var(--color-primary); color: white; padding: 4px 12px; border-radius: 99px; font-size: 0.85rem; font-weight: 700; display: inline-block; margin-top: 5px; }
        
        .btn-drive { color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-drive:hover { transform: translateY(-2px); filter: brightness(1.1); }
        
        .btn-logout { color: #d32f2f; text-decoration: none; font-weight: 600; padding: 8px 16px; border: 1px solid #d32f2f; border-radius: 12px; transition: 0.2s; }
        .btn-logout:hover { background: #fee; }

        .btn-toggle-theme { background: none; border: 1px solid var(--color-border); color: var(--color-text); padding: 8px 12px; border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: inherit; font-size: 0.9rem; margin-right: 10px; }
        .btn-toggle-theme:hover { background: var(--color-border); }

        /* Stepper Client */
        .client-stepper { display: flex; align-items: center; justify-content: space-between; margin-top: 30px; position: relative; overflow-x: auto; padding-bottom: 10px; }
        .client-stepper::-webkit-scrollbar { height: 6px; }
        .client-stepper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
        .client-stepper::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 3px; background: var(--color-border); z-index: 0; }
        .s-item { position: relative; z-index: 1; text-align: center; min-width: 80px; display: flex; flex-direction: column; align-items: center; }
        .s-circle { width: 32px; height: 32px; background: var(--color-surface); border: 3px solid var(--color-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--color-text-subtle); font-size: 14px; transition: 0.3s; }
        .s-label { margin-top: 8px; font-size: 0.75rem; color: var(--color-text-subtle); max-width: 100px; line-height: 1.2; font-weight: 500; transition: 0.3s;}
        
        .s-item.active .s-circle { border-color: var(--color-primary); background: var(--color-primary); color: white; }
        .s-item.active .s-label { color: var(--color-primary); font-weight: 700; }
        .s-item.completed .s-circle { border-color: var(--color-primary); background: var(--color-primary); color: white; opacity: 0.7; }
        .s-item.completed .s-label { color: var(--color-primary); opacity: 0.8; }

        /* Tabela Histórico */
        .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .history-table th, .history-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--color-border); }
        .history-table th { font-weight: 600; color: var(--color-text-subtle); font-size: 0.85rem; text-transform: uppercase; }
        .history-table td { color: var(--color-text); font-size: 0.95rem; }
        .history-table tr:last-child td { border-bottom: none; }
        
        /* Iframe Drive */
        .drive-embed-container { width: 100%; height: 600px; background: var(--color-surface); border-radius: 8px; border: 1px solid var(--color-border); overflow: hidden; margin-top: 20px; }
        iframe { border: 0; width: 100%; height: 100%; }

    </style>
</head>
<body>
    <div class="container">
        <header class="card">
            <div style="width:100%;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div>
                        <h1>Olá, <?= htmlspecialchars($_SESSION['cliente_nome']) ?></h1>
                        <span class="badge-panel">Acompanhamento Online</span>
                    </div>
                    <div style="display:flex; align-items:center;">
                        <button class="btn-toggle-theme" onclick="toggleTheme()">🌓 Tema</button>
                        <a href="logout.php" class="btn-logout">Sair</a>
                    </div>
                </div>

                <!-- Botões Customizados -->
                <div style="display:flex; gap:15px; flex-wrap:wrap; margin-top:20px;">
                    <?php 
                        $link1 = !empty($detalhes['link_doc_iniciais']) ? $detalhes['link_doc_iniciais'] : ($detalhes['link_drive_pasta'] ?? '');
                        if(!empty($link1)): 
                    ?>
                        <a href="<?= htmlspecialchars($link1) ?>" target="_blank" class="btn-drive" style="background-color:#6c757d;">
                             📄 Cadastro Inicial
                        </a>
                    <?php endif; ?>

                    <?php if(!empty($detalhes['link_doc_pendencias'])): ?>
                        <a href="<?= htmlspecialchars($detalhes['link_doc_pendencias']) ?>" target="_blank" class="btn-drive" style="background-color:#ffc107; color: #333;">
                            ⚠️ Status e Pendências
                        </a>
                    <?php endif; ?>

                    <?php if(!empty($detalhes['link_doc_finais'])): ?>
                        <a href="<?= htmlspecialchars($detalhes['link_doc_finais']) ?>" target="_blank" class="btn-drive" style="background-color:#198754;">
                            ✅ Links e Documentos Finais
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Stepper -->
                <?php 
                $etapa_atual = $detalhes['etapa_atual'] ?? '';
                $mapa_fases = [
                    "Abertura de Processo (Guichê)" => "Guichê",
                    "Fiscalização (Parecer Fiscal)" => "Fiscalização",
                    "Triagem (Documentos Necessários)" => "Triagem",
                    "Comunicado de Pendências (Triagem)" => "Pendências",
                    "Análise Técnica (Engenharia)" => "Engenharia",
                    "Comunicado (Pendências e Taxas)" => "Taxas",
                    "Confecção de Documentos" => "Docs",
                    "Avaliação (ITBI/Averbação)" => "Avaliação",
                    "Processo Finalizado (Documentos Prontos)" => "Finalizado"
                ];
                $keys = array_keys($mapa_fases);
                $found_index = array_search($etapa_atual, $keys);
                if($found_index === false) $found_index = -1;
                ?>
                <div class="client-stepper">
                    <?php 
                    $i = 0;
                    foreach($mapa_fases as $full => $label): 
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
            </div>
        </header>

        <!-- Histórico (Tabela Simples) -->
        <section class="card">
            <h2 style="margin-top:0; color:var(--color-text);">Histórico do Processo</h2>
            <?php if(count($timeline) > 0): ?>
                <div style="overflow-x:auto;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Fase</th>
                                <th>Descrição/Detalhes</th>
                            </tr>
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

        <!-- Área de Documentos (Google Drive Embed) -->
        <section class="card">
            <h2 style="margin-top:0; color:var(--color-text);">Arquivos e Documentos</h2>
            <p style="color:var(--color-text-subtle); margin-bottom:15px;">Abaixo você visualiza diretamente sua pasta de documentos no sistema.</p>
            
            <?php if ($drive_folder_id): ?>
                <div class="drive-embed-container">
                    <iframe src="https://drive.google.com/embeddedfolderview?id=<?= htmlspecialchars($drive_folder_id) ?>#list" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div style="padding: 30px; text-align:Center; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <p>A pasta de documentos ainda não foi vinculada a este processo.</p>
                    <p style="font-size:0.9rem; color:#666;">Entre em contato com a administração.</p>
                </div>
            <?php endif; ?>
        </section>

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
    </script>
</body>
</html>
