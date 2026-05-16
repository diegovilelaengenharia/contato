<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
require_once '../db.php';

// 1. AUTHENTICATION
if (!isset($_SESSION['cliente_id'])) {
    header("Location: ../index.php");
    exit;
}
$cliente_id = $_SESSION['cliente_id'];

// 2. FETCH DRIVE LINK
$stmt = $pdo->prepare("SELECT link_drive_pasta FROM processo_detalhes WHERE cliente_id = ?");
$stmt->execute([$cliente_id]);
$drive_link = $stmt->fetchColumn();

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
    <title>Documentos</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=4.2">
    
    <style>
        /* FORCE SOCIAL UPDATE v2 */
        .floating-buttons { position: fixed; bottom: 25px; right: 25px; display: flex; flex-direction: column; gap: 16px; z-index: 99999 !important; }
        .floating-btn { width: 56px; height: 56px; border-radius: 50%; display: grid; place-items: center; background: var(--btn-bg); color: #ffffff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 8px 24px rgba(0, 0, 0, 0.1); transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease; text-decoration: none; position: relative; border: none !important; }
        .floating-btn svg { width: 28px; height: 28px; fill: currentColor; }
        .floating-btn--whatsapp { --btn-bg: #25d366; }
        .floating-btn--whatsapp:hover { background: #20bd5a; box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4); }
        .floating-btn--instagram { --btn-bg: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .floating-btn--instagram:hover { box-shadow: 0 6px 16px rgba(220, 39, 67, 0.4); }
        .floating-btn:hover { transform: scale(1.1) rotate(-4deg); }
        .floating-btn:active { transform: scale(0.95); }

        body { background: #f4f6f8; }
        
        /* HEADER - VILELA PREMIUM (GREEN) */
        .page-header {
            background: linear-gradient(135deg, #146C43 0%, #0d462b 100%); /* Vilela Green Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(20, 108, 67, 0.25); 
            margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
            color: #ffffff; /* White Text */
            position: relative;
            overflow: hidden;
            border: none;
        }
        
        /* Decorative Circle (Subtle) */
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.4);
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
            border: none;
        }
        .btn-back:hover { background: #f0fff4; transform: translateX(-3px); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #ffffff; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.9; font-weight: 400; margin-top: 2px; color: #e9ecef; }

        @keyframes docWiggle {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            50% { transform: rotate(10deg); }
            75% { transform: rotate(-5deg); }
            100% { transform: rotate(0deg); }
        }
        
        @media (max-width: 480px) {
            .page-header { flex-direction: column-reverse; gap: 20px; text-align: center; padding: 25px; align-items: center; }
            .header-title-box { text-align: center; align-items: center; } 
            .btn-back { width: 100%; justify-content: center; }
            .page-header img { height: 45px !important; margin-bottom: 10px; }
            .page-header > div:first-child { flex-direction: column-reverse; width: 100%; gap: 15px; }
        }

        .drive-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .drive-icon {
            font-size: 3rem; margin-bottom: 15px; display: block;
        }
        
        .btn-drive-primary {
            display: inline-flex; align-items: center; gap: 10px;
            background: #146C43; color: white;
            padding: 12px 25px; border-radius: 30px;
            text-decoration: none; font-weight: 600;
            box-shadow: 0 4px 12px rgba(20, 108, 67, 0.3);
            transition: transform 0.2s;
        }
        
        .btn-drive-primary:hover {
            transform: translateY(-2px);
        }
        
        .iframe-wrapper {
            margin-top: 20px;
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            height: 500px;
            background: #fafafa;
        }
        
        .doc-list-item {
            background: white; padding: 15px; border-radius: 12px;
            margin-bottom: 10px; display: flex; align-items: center; gap: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03); border: 1px solid #eee;
        }

        /* MOBILE OPTIMIZATION */
        @media (max-width: 480px) {
            .page-header { 
                padding: 15px 20px; 
                flex-direction: row; 
                align-items: center; 
                justify-content: space-between;
                border-radius: 0 0 25px 25px;
            }
            .header-title-box { text-align: right; margin-left: 10px; } 
            .header-title-main { font-size: 1.1rem; line-height: 1.1; }
            .header-title-sub { font-size: 0.75rem; display: none; }
            .btn-back { 
                width: auto; 
                padding: 8px 16px; 
                font-size: 0.85rem; 
                flex-shrink: 0;
            }
            
            /* Full Bleed Card on Mobile to maximize Iframe width */
            .drive-card {
                margin-left: -20px; margin-right: -20px;
                border-radius: 0; border-left: none; border-right: none;
                padding: 15px 10px;
                box-shadow: none; border-top: 1px solid #eee; border-bottom: 1px solid #eee;
            }
            .iframe-wrapper {
                height: 75vh; /* Taller on mobile */
                border: none;
            }
        }
    </style>
</head>
<body>

    <div class="app-container" style="padding: 0;">
        
        <!-- HEADER MODULE (BLUE) -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Arquivos do Processo</span>
                    <span class="header-title-sub">Drive Integrado</span>
                 </div>
                 
                 <!-- Animated Icon -->
                 <div style="color: #ffffff; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; animation: docWiggle 4s ease-in-out infinite;">
                    <span class="material-symbols-rounded" style="font-size: 2.2rem; text-shadow: 0 2px 10px rgba(0,0,0,0.1);">folder_open</span>
                 </div>
            </div>
        </div>
        
        <div style="padding: 0 20px;">
            <!-- MAIN DRIVE CARD -->
            <div class="drive-card">
                <span class="drive-icon">📁</span>
                <h2 style="font-size: 1.3rem; margin: 0 0 10px 0; color: #333;">Pasta do Projeto</h2>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">
                    Acesse todos os seus projetos, plantas e contratos diretamente no Google Drive.
                </p>
                
                <?php if ($drive_link): ?>
                    <a href="<?= htmlspecialchars($drive_link) ?>" target="_blank" class="btn-drive-primary">
                        Abrir no Google Drive ↗
                    </a>
                    
                    <div class="iframe-wrapper">
                        <iframe src="<?= htmlspecialchars($embed_url) ?>" width="100%" height="100%" frameborder="0" style="border:0;"></iframe>
                    </div>
                <?php else: ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; color: #666; font-style: italic;">
                        O link da pasta ainda não foi vinculado ao seu processo.
                    </div>
                <?php endif; ?>
            </div>

            <!-- WHATSAPP CTA -->
            <div style="text-align: center; margin-top: 30px; padding-bottom: 20px;">
                 <a href="https://wa.me/5535984529577?text=Ola,%20tenho%20duvidas%20sobre%20os%20documentos." style="display:inline-block; font-size: 0.85rem; color: #146c43; text-decoration: none; font-weight: 600; padding: 10px 20px; background: #d1e7dd; border-radius: 20px;">
                    Dúvidas sobre os documentos? Fale conosco.
                 </a>
            </div>
            
        </div>
        
        <!-- FLOATING SOCIAL BUTTONS (OFFICIAL LANDING PAGE STYLE) -->
        <div class="floating-buttons" style="z-index: 99999;">
            <a href="https://wa.me/5535984529577?text=Ola%20Diego%20Vilela" class="floating-btn floating-btn--whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
                <svg viewBox="0 0 24 24" role="presentation"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
            </a>
            <a href="https://www.instagram.com/diegovilela.eng/" class="floating-btn floating-btn--instagram" target="_blank" rel="noopener" aria-label="Instagram">
                <svg viewBox="0 0 24 24" role="presentation"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 5A1.5 1.5 0 1 0 10.5 12 1.5 1.5 0 0 0 12 13.5zm4.25-6.75a1 1 0 1 1-1-1 1 1 0 0 1 1 1z"></path></svg>
            </a>
        </div>

        <!-- FOOTER -->
        <?php include 'includes/footer.php'; ?>

    </div>

</body>
</html>
