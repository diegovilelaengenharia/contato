<?php
require_once __DIR__ . '/init_client.php';

// 2. FETCH DATA
$stmt = $pdo->prepare("SELECT link_drive_pasta FROM processo_detalhes WHERE cliente_id = ?");
$stmt->execute([$cliente_id]);
$drive_link = $stmt->fetchColumn();

// Buscar arquivos entregáveis locais
$stmt_ent = $pdo->prepare("SELECT * FROM processo_entregaveis WHERE cliente_id = ? ORDER BY data_upload DESC");
$stmt_ent->execute([$cliente_id]);
$entregaveis = $stmt_ent->fetchAll();

// Helper for Embed URL
$embed_url = "";
if($drive_link) {
    if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $drive_link, $matches)) {
        $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
    } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $drive_link, $matches)) {
         $embed_url = "https://drive.google.com/embeddedfolderview?id=" . $matches[1] . "#list";
    } else {
        $embed_url = $drive_link; // Fallback
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Finais</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    
    <style>
        body { background: #f4f6f8; }
        
        .page-header {
            background: linear-gradient(135deg, #146C43 0%, #0d462b 100%);
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(20, 108, 67, 0.25); 
            margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            border: none;
        }
        
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.1);
            border-radius: 50%; pointer-events: none;
        }

        .btn-back {
            text-decoration: none; color: #146C43; font-weight: 600; 
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; 
            background: white; 
            border-radius: 25px;
            transition: 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn-back:hover { background: #f0fff4; transform: translateX(-3px); }
        
        .header-title-box { display: flex; flex-direction: column; align-items: flex-end; text-align: right; }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #ffffff; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.9; font-weight: 400; margin-top: 2px; color: #e9ecef; }

        /* ENTREGAVEIS CARDS */
        .entregavel-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: 0.2s;
        }
        .entregavel-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .ent-icon {
            width: 50px; height: 50px; border-radius: 12px;
            background: #e8f5e9; color: #198754;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; flex-shrink: 0;
        }
        .ent-info { flex: 1; }
        .ent-title { font-weight: 700; color: #333; font-size: 1.1rem; margin-bottom: 4px; }
        .ent-date { font-size: 0.8rem; color: #888; display: flex; align-items: center; gap: 4px; }
        .btn-download {
            background: #146C43; color: white; border: none; padding: 10px 20px;
            border-radius: 12px; font-weight: 700; text-decoration: none;
            display: flex; align-items: center; gap: 8px; font-size: 0.9rem;
            transition: 0.2s;
        }
        .btn-download:hover { background: #0d462b; box-shadow: 0 4px 10px rgba(20, 108, 67, 0.3); }

        .section-title {
            font-size: 1.1rem; font-weight: 800; color: #2c3e50;
            margin: 40px 0 20px 0; display: flex; align-items: center; gap: 10px;
            padding-bottom: 10px; border-bottom: 2px solid #e0e0e0;
        }

        /* DRIVE SECTION */
        .drive-box {
            background: #f8f9fa; border-radius: 20px; padding: 25px;
            border: 1px solid #e0e0e0; margin-bottom: 30px;
        }
        .iframe-wrapper {
            margin-top: 20px; border-radius: 12px; overflow: hidden;
            height: 500px; background: #fff; border: 1px solid #ddd;
        }

        @media (max-width: 600px) {
            .entregavel-card { flex-direction: column; text-align: center; padding: 25px; }
            .ent-icon { margin: 0 auto; }
            .btn-download { width: 100%; justify-content: center; }
            .header-title-sub { display: none; }
        }
    </style>
</head>
<body>

    <div class="app-container" style="padding: 0;">
        
        <div class="page-header">
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>
            <div class="header-title-box">
                <span class="header-title-main">Documentos Finais</span>
                <span class="header-title-sub">Acesso rápido aos seus arquivos</span>
            </div>
        </div>
        
        <div style="padding: 0 20px;">
            
            <!-- SECTION: ENTREGAVEIS -->
            <h3 class="section-title">
                <span class="material-symbols-rounded" style="color:#198754;">verified</span> 
                Documentos Entregues
            </h3>
            
            <?php if(count($entregaveis) == 0): ?>
                <div style="background:white; padding:40px 20px; border-radius:20px; text-align:center; color:#999; border:1px solid #eee;">
                    <span style="font-size:3rem; display:block; margin-bottom:10px;">⏳</span>
                    Ainda não há documentos finais disponíveis para download direto.<br>
                    Verifique a linha do tempo ou o Google Drive abaixo.
                </div>
            <?php else: foreach($entregaveis as $ent): ?>
                <div class="entregavel-card">
                    <div class="ent-icon">
                        <span class="material-symbols-rounded">description</span>
                    </div>
                    <div class="ent-info">
                        <div class="ent-title"><?= htmlspecialchars($ent['titulo']) ?></div>
                        <div class="ent-date">
                            <span class="material-symbols-rounded" style="font-size:1rem;">calendar_today</span>
                            Enviado em <?= date('d/m/Y', strtotime($ent['data_upload'])) ?>
                        </div>
                    </div>
                    <a href="../<?= htmlspecialchars($ent['arquivo_path']) ?>" target="_blank" class="btn-download">
                        <span class="material-symbols-rounded">download</span> Baixar
                    </a>
                </div>
            <?php endforeach; endif; ?>

            <!-- SECTION: DRIVE -->
            <h3 class="section-title">
                <span class="material-symbols-rounded" style="color:#4285F4;">folder_open</span> 
                Pasta Completa (Google Drive)
            </h3>
            
            <div class="drive-box">
                <?php if ($drive_link): ?>
                    <div style="text-align:center;">
                        <a href="<?= htmlspecialchars($drive_link) ?>" target="_blank" class="btn-download" style="background:#4285F4; display:inline-flex;">
                             Abrir no Google Drive ↗
                        </a>
                    </div>
                    <div class="iframe-wrapper">
                        <iframe src="<?= htmlspecialchars($embed_url) ?>" width="100%" height="100%" frameborder="0" style="border:0;"></iframe>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:20px; color:#666; font-style:italic;">
                        O link da pasta Drive ainda não foi vinculado ao seu processo.
                    </div>
                <?php endif; ?>
            </div>

            <!-- WHATSAPP CTA -->
            <div style="text-align: center; margin: 40px 0;">
                 <a href="https://wa.me/5535984529577?text=Ola,%20tenho%20duvidas%20sobre%20os%20documentos." style="display:inline-block; font-size: 0.85rem; color: #146c43; text-decoration: none; font-weight: 600; padding: 12px 25px; background: #d1e7dd; border-radius: 30px;">
                    Dúvidas sobre os documentos? Fale conosco.
                 </a>
            </div>
            
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </div>

</body>
</html>
