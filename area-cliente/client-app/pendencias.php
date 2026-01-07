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

// 2. FETCH CLIENT DATA (For context if needed)
$stmt = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente_nome = $stmt->fetchColumn(); 

// 3. FETCH PENDENCIES
// Statuses: 'pendente', 'resolvido', 'em_analise' (handled as 'pendente' or separate), 'anexado'
$stmt_pend = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END, data_criacao DESC");
$stmt_pend->execute([$cliente_id]);
$pendencias = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);

// Helper for WhatsApp Link
function getWhatsappLink($pendency_desc) {
    $text = "Olá, estou entrando em contato sobre a pendência: *" . strip_tags($pendency_desc) . "*.";
    return "https://wa.me/5535984529577?text=" . urlencode($text);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendências | Vilela Engenharia</title>
    
    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- STYLES -->
    <link rel="stylesheet" href="css/style.css?v=2.7.3">
    
    <style>
        /* PAGE SPECIFIC STYLES */
        body { background: #f4f6f8; }
        
        .page-header {
            background: white; padding: 15px; border-radius: 16px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        
        .btn-back {
            text-decoration: none; color: #666; font-weight: 600; 
            display: flex; align-items: center; gap: 5px;
            padding: 8px 12px; background: #f8f9fa; border-radius: 8px;
        }

        .card-pendency {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .card-pendency:active { transform: scale(0.99); }
        
        .card-pendency.resolvido {
            opacity: 0.7;
            background: #fdfdfd;
        }
        
        .status-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-resolvido { background: #d1e7dd; color: #0f5132; }
        .status-analise { background: #cff4fc; color: #055160; }
        
        .pendency-desc {
            font-size: 1rem; color: #333; line-height: 1.5; margin-bottom: 15px;
        }
        
        .btn-action-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%;
            padding: 12px;
            background: var(--color-primary); color: white;
            border-radius: 12px;
            text-decoration: none; font-weight: 600; font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(20, 108, 67, 0.2);
        }

        .empty-state {
            text-align: center; padding: 40px 20px; color: #888;
        }
        .empty-icon { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- HEADER -->
        <div class="page-header">
            <a href="index.php" class="btn-back">
                <span>←</span> Voltar
            </a>
            <h1 style="font-size: 1.2rem; margin: 0; color: #dc3545; display: flex; align-items: center; gap: 8px;">
                <span>⚠️</span> Pendências
            </h1>
        </div>

        <!-- CONTENT -->
        <?php if (empty($pendencias)): ?>
            <div class="empty-state">
                <div class="empty-icon">🎉</div>
                <h2 style="font-size: 1.2rem; margin-bottom: 10px; color: #333;">Tudo em dia!</h2>
                <p>Você não tem nenhuma pendência para resolver no momento.</p>
            </div>
        <?php else: ?>
            
            <div style="margin-bottom: 20px; font-size: 0.9rem; color: #666; padding: 0 5px;">
                Abaixo estão listadas as pendências que precisam da sua atenção para o andamento do processo.
            </div>

            <?php foreach($pendencias as $p): 
                $status = $p['status'];
                $is_resolved = ($status == 'resolvido');
                $status_label = $is_resolved ? 'Resolvido' : (($status == 'em_analise' || $status == 'anexado') ? 'Em Análise' : 'Pendente');
                $bg_class = $is_resolved ? 'status-resolvido' : (($status == 'em_analise' || $status == 'anexado') ? 'status-analise' : 'status-pendente');
                $icon = $is_resolved ? '✅' : '⏳';
            ?>
                <div class="card-pendency <?= $is_resolved ? 'resolvido' : '' ?>">
                    <div class="status-badge <?= $bg_class ?>">
                        <span><?= $icon ?></span> <?= $status_label ?>
                    </div>
                    
                    <div class="pendency-desc">
                        <?= $p['descricao'] ?> <!-- Descricao allows HTML from admin editor -->
                    </div>
                    
                    <?php if (!$is_resolved): ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            
                            <!-- WhatsApp Action -->
                            <a href="<?= getWhatsappLink($p['descricao']) ?>" target="_blank" class="btn-action-primary" style="background: #25d366;">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="white"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
                                Resolver via WhatsApp
                            </a>

                            <!-- Info Note -->
                            <div style="font-size: 0.75rem; text-align: center; color: #888; margin-top: 5px;">
                                Fale diretamente conosco para resolver este item.
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; font-size: 0.8rem; color: #198754; font-weight: 600; padding: 10px; background: #e8f5e9; border-radius: 8px;">
                            Item concluído em <?= date('d/m/Y', strtotime($p['data_criacao'])) // Approximate date ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
        
        <!-- FOOTER BRANDING -->
        <div style="text-align: center; margin-top: 30px; opacity: 0.4;">
            <img src="../../assets/logo.png" alt="" style="height: 30px; filter: grayscale(1);">
        </div>
        
        <div class="floating-buttons">
            <a href="https://wa.me/5535984529577?text=Ola%20Engenheiro,%20tenho%20uma%20divida%20sobre%20o%20processo" class="floating-btn floating-btn--whatsapp" target="_blank" title="Falar com Engenheiro">
                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.66 15.14L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.08-1.13l-.29-.18-3 .79.8-2.91-.19-.3A8 8 0 1 1 12 20zm4.37-5.73-.52-.26a1.32 1.32 0 0 0-1.15.04l-.4.21a.5.5 0 0 1-.49 0 8.14 8.14 0 0 1-2.95-2.58.5.5 0 0 1 0-.49l.21-.4a1.32 1.32 0 0 0 .04-1.15l-.26-.52a1.32 1.32 0 0 0-1.18-.73h-.37a1 1 0 0 0-1 .86 3.47 3.47 0 0 0 .18 1.52A10.2 10.2 0 0 0 13 15.58a3.47 3.47 0 0 0 1.52.18 1 1 0 0 0 .86-1v-.37a1.32 1.32 0 0 0-.73-1.18z"></path></svg>
            </a>
            <a href="https://www.instagram.com/diegovilela.eng/" class="floating-btn floating-btn--instagram" target="_blank" title="Instagram">
                <svg viewBox="0 0 24 24"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 5A1.5 1.5 0 1 0 10.5 12 1.5 1.5 0 0 0 12 13.5zm4.25-6.75a1 1 0 1 1-1-1 1 1 0 0 1 1 1z"></path></svg>
            </a>
        </div>

    </div>

</body>
</html>
