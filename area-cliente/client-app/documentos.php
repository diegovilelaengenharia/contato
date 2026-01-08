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

// 3. FETCH RECENT DOCUMENTS (FROM MOVEMENTS)
$stmt_docs = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? AND tipo_movimento = 'documento' ORDER BY data_movimento DESC");
$stmt_docs->execute([$cliente_id]);
$docs_recents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="css/style.css?v=3.2">
    
    <style>
        /* FORCE SOCIAL UPDATE v2 */
        .floating-social-container { position: fixed; bottom: 25px; right: 25px; display: flex; flex-direction: column; gap: 15px; z-index: 99999 !important; }
        .social-btn { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,0.25); position: relative; border: none !important; transition: transform 0.3s; }
        .social-btn:hover { transform: scale(1.1); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .social-btn svg { width: 32px; height: 32px; fill: white; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.1)); }
        .social-btn.whatsapp { background-color: #25D366 !important; }
        .social-btn.instagram { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%) !important; }

        body { background: #f4f6f8; }
        
        /* HEADER - BLUE THEME (Premium) */
        .page-header {
            background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%); /* Light Blue/Cyan Gradient */
            border-bottom: none;
            padding: 30px 25px; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(13, 202, 240, 0.15); 
            margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
            color: #006064; /* Dark Cyan Text */
            position: relative;
            overflow: hidden;
            border: 1px solid #bcecf5;
        }
        
        .page-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255,255,255,0.4);
            border-radius: 50%; pointer-events: none;
        }

        .btn-back {
            text-decoration: none; color: #006064; font-weight: 600; 
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; 
            background: white; 
            border-radius: 25px;
            transition: 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #bcecf5;
        }
        .btn-back:hover { background: #e6fcff; transform: translateX(-3px); }
        .btn-back:active { transform: scale(0.95); }
        
        .header-title-box {
            display: flex; flex-direction: column; align-items: flex-end; text-align: right;
        }
        .header-title-main { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #006064; }
        .header-title-sub { font-size: 0.8rem; opacity: 0.8; font-weight: 500; margin-top: 2px; color: #00838f; }

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
            background: #0d6efd; color: white;
            padding: 12px 25px; border-radius: 30px;
            text-decoration: none; font-weight: 600;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
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
            height: 400px;
            background: #fafafa;
        }
        
        .doc-list-item {
            background: white; padding: 15px; border-radius: 12px;
            margin-bottom: 10px; display: flex; align-items: center; gap: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03); border: 1px solid #eee;
        }
    </style>
</head>
<body>

    <div class="app-container" style="padding: 0;">
        
        <!-- HEADER -->
        <div class="page-header">
            <!-- Left: Back Button -->
            <a href="index.php" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span> Voltar
            </a>

            <!-- Right: Title & Icon -->
            <div style="display:flex; align-items:center; gap:15px; z-index:2;">
                 <div class="header-title-box">
                    <span class="header-title-main">Documentos</span>
                    <span class="header-title-sub">Arquivos do Projeto</span>
                 </div>
                 
                 <!-- Icon -->
                 <div style="background: white; border:1px solid #dee2e6; color: #0dcaf0; width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    📂
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

            <!-- RECENT ACTIVITY (IF ANY) -->
            <?php if (!empty($docs_recents)): ?>
                <h3 style="font-size: 1.1rem; color: #333; margin-bottom: 15px; padding-left: 5px;">Recentes</h3>
                <?php foreach($docs_recents as $doc): ?>
                    <div class="doc-list-item">
                        <div style="width: 40px; height: 40px; background: #e3f2fd; color: #0d6efd; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            📄
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #333; font-size: 0.95rem;"><?= htmlspecialchars($doc['titulo_fase']) ?></div>
                            <div style="font-size: 0.8rem; color: #888;"><?= date('d/m/Y', strtotime($doc['data_movimento'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- WHATSAPP CTA -->
            <div style="text-align: center; margin-top: 30px; padding-bottom: 20px;">
                 <a href="https://wa.me/5535984529577?text=Ola,%20tenho%20duvidas%20sobre%20os%20documentos." style="display:inline-block; font-size: 0.85rem; color: #146c43; text-decoration: none; font-weight: 600; padding: 10px 20px; background: #d1e7dd; border-radius: 20px;">
                    Dúvidas sobre os documentos? Fale conosco.
                 </a>
            </div>
            
        </div>
        
        <!-- FLOATING SOCIAL BUTTONS (OFFICIAL SVGS) -->
        <div class="floating-social-container">
            <!-- WhatsApp (Official Icon) -->
            <a href="https://wa.me/5535984529577?text=Ol%C3%A1%2C%20gostaria%20de%20falar%20sobre%20meu%20processo." target="_blank" class="social-btn whatsapp">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M18.403 5.633A8.919 8.919 0 0 0 12.053 3c-4.948 0-8.976 4.027-8.978 8.977 0 1.582.413 3.126 1.198 4.488L3 21.116l4.759-1.249a8.981 8.981 0 0 0 4.29 1.093h.004c4.947 0 8.975-4.027 8.977-8.977a8.926 8.926 0 0 0-2.627-6.35m-6.35 13.812h-.003a7.446 7.446 0 0 1-3.798-1.041l-.272-.162-2.824.741.753-2.753-.177-.282a7.448 7.448 0 0 1-1.141-3.971c.002-4.114 3.349-7.461 7.465-7.461a7.413 7.413 0 0 1 5.275 2.188 7.42 7.42 0 0 1 2.183 5.279c-.002 4.114-3.349 7.462-7.461 7.462m4.093-5.589c-.225-.113-1.327-.655-1.533-.73-.205-.075-.354-.112-.504.112-.149.224-.579.73-.709.88-.131.149-.261.169-.486.056-.224-.112-9.25-3.363-1.09-5.003-.1391-.124-.2681-.226-.387-.306-.419-.281-.722-.524-.712-.131-.187.056-.299.112-.411.224.112.224 2.224 1.12.374.374.149.524 1.421.749 2.091.16.476.16 1.842.16 2.385.16.543-.001 1.056-.239 1.341-.676.285-.436 1.056.126 1.706.766z" fill="white"/>
                </svg>
                <div class="social-tooltip">Fale conosco</div>
            </a>
            
            <!-- Instagram (Official Icon) -->
            <a href="https://instagram.com/vilela.engenharia" target="_blank" class="social-btn instagram">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" fill="white"/>
                </svg>
                <div class="social-tooltip">Siga no Instagram</div>
            </a>
        </div>

    </div>

</body>
</html>
