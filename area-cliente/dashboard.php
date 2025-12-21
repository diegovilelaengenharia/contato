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

// Fallback para tabela antiga se timeline vazia... (mantido do código anterior se necessário, mas simplificado aqui)
if(count($timeline) == 0) {
    $stmtOld = $pdo->prepare("SELECT * FROM progresso WHERE cliente_id = ? ORDER BY data_fase DESC");
    $stmtOld->execute([$cliente_id]);
    $progresso = $stmtOld->fetchAll();
    
    foreach($progresso as $p) {
        $timeline[] = [
            'data_movimento' => $p['data_fase'],
            'titulo_fase' => $p['fase'],
            'descricao' => $p['descricao'],
            'status_tipo' => 'tramite',
            'departamento_origem' => '',
            'departamento_destino' => '',
            'anexo_url' => ''
        ];
    }
}

// Buscar Documentos
$stmtDoc = $pdo->prepare("SELECT * FROM documentos WHERE cliente_id = ?");
$stmtDoc->execute([$cliente_id]);
$documentos = $stmtDoc->fetchAll();
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
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    <style>
        .container { width: min(1000px, 95%); margin: 40px auto; }
        .header-panel { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .card { background: var(--color-surface); padding: 32px; border-radius: var(--radius-large); box-shadow: var(--shadow-soft); margin-bottom: 30px; }
        
        /* Timeline style */
        .timeline-item { border-left: 3px solid var(--color-primary); padding-left: 24px; margin-bottom: 32px; position: relative; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-item::before { content: ''; width: 14px; height: 14px; background: var(--color-primary); border-radius: 50%; position: absolute; left: -8.5px; top: 0; }
        .timeline-date { font-size: 0.9rem; color: var(--color-text-subtle); margin-bottom: 4px; display: block; }
        .timeline-title { font-weight: 700; color: var(--color-primary-strong); margin: 0 0 8px; font-size: 1.2rem; }
        
        /* Doc Links */
        .links-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .doc-link { display: flex; align-items: center; gap: 16px; padding: 20px; border: 1px solid rgba(19, 111, 92, 0.1); border-radius: var(--radius-small); text-decoration: none; color: var(--color-text); transition: all 0.2s ease; background: #fff; }
        .doc-link:hover { transform: translateY(-3px); box-shadow: 0 12px 32px -10px rgba(0,0,0,0.1); border-color: var(--color-primary); }
        .doc-icon { display:flex; align-items:center; justify-content:center; background:var(--color-surface-soft); color: var(--color-primary); width:48px; height:48px; border-radius:12px; font-size: 24px; }
        
        h1 { margin: 0; font-size: clamp(1.5rem, 3vw, 2rem); color: var(--color-primary-strong); }
        .badge-panel { background: var(--color-accent); color: #1f2521; padding: 4px 12px; border-radius: 99px; font-size: 0.85rem; font-weight: 700; display: inline-block; margin-top: 5px; }
        .btn-drive { background-color: #1f2521; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-drive:hover { background-color: #444; transform: translateY(-2px); }
        .btn-logout { color: #d32f2f; text-decoration: none; font-weight: 600; padding: 8px 16px; border: 1px solid #d32f2f; border-radius: 12px; transition: 0.2s; }
        .btn-logout:hover { background: #fdecea; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header-panel">
            <div>
                <h1>Olá, <?= htmlspecialchars($_SESSION['cliente_nome']) ?></h1>
                <span class="badge-panel">Acompanhamento Online</span>
                <?php if(!empty($detalhes['link_drive_pasta'])): ?>
                    <div style="margin-top: 15px;">
                        <a href="<?= htmlspecialchars($detalhes['link_drive_pasta']) ?>" target="_blank" class="btn-drive">
                            📂 Acessar Pasta de Documentos
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="btn-logout">Sair</a>
        </header>

        <section class="timeline-section">
            <h2 class="section-heading" style="margin-top:0; margin-bottom: 30px; margin-left: 20px;">Linha do Tempo do Processo</h2>
            
            <?php 
            // Os dados já foram preparados no início do arquivo (variável $timeline)
            ?>

            <?php if(count($timeline) > 0): ?>
                <?php foreach($timeline as $mov): ?>
                    <div class="timeline-card">
                        <!-- Ícone Dinâmico conforme Status -->
                        <?php
                            $icon = "🔄"; // Default
                            $bgClass = "status-tramite";
                            switch($mov['status_tipo']) {
                                case 'inicio': $icon = "🚩"; $bgClass = "status-inicio"; break;
                                case 'pendencia': $icon = "⚠️"; $bgClass = "status-pendencia"; break;
                                case 'documento': $icon = "📄"; $bgClass = "status-documento"; break;
                                case 'conclusao': $icon = "✅"; $bgClass = "status-conclusao"; break;
                            }
                        ?>
                        <div class="timeline-icon <?= $bgClass ?>"><?= $icon ?></div>

                        <div class="timeline-header">
                            <span class="timeline-date"><?= date('d/m/Y \à\s H:i', strtotime($mov['data_movimento'])) ?></span>
                            <?php if(!empty($mov['prazo_previsto'])): ?>
                                <span style="font-size:0.8rem; color:#d97706; font-weight:600;">Previsão: <?= date('d/m/Y', strtotime($mov['prazo_previsto'])) ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 class="timeline-title"><?= htmlspecialchars($mov['titulo_fase']) ?></h3>
                        
                        <?php if(!empty($mov['departamento_origem']) || !empty($mov['departamento_destino'])): ?>
                            <div class="timeline-flow">
                                <span><?= htmlspecialchars($mov['departamento_origem'] ?: 'Início') ?></span>
                                <span class="flow-arrow">➜</span>
                                <strong><?= htmlspecialchars($mov['departamento_destino'] ?: 'Conclusão') ?></strong>
                            </div>
                        <?php endif; ?>

                        <p class="timeline-desc"><?= nl2br(htmlspecialchars($mov['descricao'])) ?></p>

                        <?php if(!empty($mov['anexo_url'])): ?>
                            <a href="<?= htmlspecialchars($mov['anexo_url']) ?>" target="_blank" class="timeline-attachment">
                                📎 <?= htmlspecialchars($mov['anexo_nome'] ?: 'Visualizar Anexo') ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if(!empty($mov['usuario_responsavel'])): ?>
                            <div style="margin-top:12px; font-size:0.8rem; color:#999;">
                                Resp: <?= htmlspecialchars($mov['usuario_responsavel']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--color-text-subtle); margin-left: 20px;">Nenhuma atualização recente encontrada para o seu processo.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="section-heading" style="margin-top:0;">Documentos e Arquivos</h2>
            <div class="links-grid">
                <?php if(count($documentos) > 0): ?>
                    <?php foreach($documentos as $doc): ?>
                        <a href="<?= htmlspecialchars($doc['link_drive']) ?>" target="_blank" class="doc-link">
                            <span class="doc-icon">📄</span>
                            <div>
                                <strong style="display:block; margin-bottom:4px;"><?= htmlspecialchars($doc['titulo']) ?></strong>
                                <small style="color: var(--color-text-subtle);">Clique para acessar</small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--color-text-subtle);">Nenhum documento disponível ainda.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
