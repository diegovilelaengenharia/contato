<?php
session_start();
require 'db.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// --- DATA FETCHING ---
// 1. Client & Details
$stmt = $pdo->prepare("SELECT c.*, d.* FROM clientes c LEFT JOIN processo_detalhes d ON c.id = d.cliente_id WHERE c.id = ?");
$stmt->execute([$cliente_id]);
$data = $stmt->fetch();
$nome_parts = explode(' ', $data['nome']);
$primeiro_nome = $nome_parts[0];
$endereco = $data['imovel_rua'] ?? ($data['endereco_imovel'] ?? 'Endereço não cadastrado');

// 2. Timeline
$stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
$stmt->execute([$cliente_id]);
$timeline = $stmt->fetchAll();

// 3. Pendencies
$stmt = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY FIELD(status, 'pendente','vencido','analise','resolvido'), id DESC");
$stmt->execute([$cliente_id]);
$pendencias = $stmt->fetchAll();

// 4. Finance
$stmt = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
$stmt->execute([$cliente_id]);
$financeiro = $stmt->fetchAll();

// Financial Summary
$fin_stats = ['total'=>0, 'pago'=>0, 'pendente'=>0];
foreach($financeiro as $f) {
    if($f['categoria'] == 'honorarios') $fin_stats['total'] += $f['valor']; // Assuming stats focus on main fees or total? Adjust logic if needed. 
    // Or maybe just sum everything
    if($f['status']=='pago') $fin_stats['pago'] += $f['valor'];
    else $fin_stats['pendente'] += $f['valor'];
}

// Drive IDs
function getDriveId($url) {
    if (preg_match('/folders\/([a-zA-Z0-9-_]+)/', $url, $m)) return $m[1];
    if (preg_match('/id=([a-zA-Z0-9-_]+)/', $url, $m)) return $m[1];
    return null;
}
$drive_id = !empty($data['link_drive_pasta']) ? getDriveId($data['link_drive_pasta']) : null;


// --- HANDLE UPLOAD POST ---
$msg_toast = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_pendencia') {
    $p_id = $_POST['p_id'];
    $files = $_FILES['arquivos'];
    $total = count($files['name']);
    $success = 0;
    
    $dir = __DIR__ . '/uploads/pendencias/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    for($i=0; $i<$total; $i++) {
        if($files['error'][$i] === 0) {
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if(in_array($ext, ['pdf','jpg','jpeg','png','doc','docx'])) {
                $new_name = $p_id . '_' . time() . '_' . $i . '.' . $ext;
                if(move_uploaded_file($files['tmp_name'][$i], $dir . $new_name)) {
                    $pdo->prepare("INSERT INTO processo_pendencias_arquivos (pendencia_id, arquivo_nome, arquivo_path, data_upload) VALUES (?, ?, ?, NOW())")->execute([$p_id, $files['name'][$i], 'uploads/pendencias/'.$new_name]);
                    $success++;
                }
            }
        }
    }
    
    if($success > 0) {
        $pdo->prepare("UPDATE processo_pendencias SET status='anexado' WHERE id=?")->execute([$p_id]);
        $pdo->prepare("INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo) VALUES (?, '📎 Arquivos Enviados', NOW(), ?, 'upload')")->execute([$cliente_id, "$success arquivo(s) enviado(s) pelo cliente para a pendência #$p_id"]);
        $msg_toast = "Arquivos enviados com sucesso!";
        // Reduce redirect loop by just setting variable
    } else {
        $msg_toast = "Erro ao enviar arquivos. Verifique os formatos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Área do Cliente | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        // Check Dark Mode Preference
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark-mode');
    </script>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme']=='dark' ? 'dark-mode' : '' ?>">

<!-- FLOATING ACTIONS -->
<div class="header-actions">
    <button class="icon-btn" onclick="toggleTheme()" title="Alternar Tema">
        <span id="theme-icon">🌓</span>
    </button>
    <a href="logout.php" class="icon-btn" title="Sair" style="text-decoration:none;">
        <span>🛑</span>
    </a>
</div>

<div class="container">

    <!-- 1. CARD RESUME -->
    <div class="resume-card fade-in">
        <div class="resume-header">
            <div class="client-info">
                <div class="avatar-circle">
                    <?= strtoupper(substr($primeiro_nome, 0, 1)) ?>
                </div>
                <div class="resume-title">
                    <p style="text-transform:uppercase; font-size:0.75rem; color:rgba(255,255,255,0.7); font-weight:700;">Área do Cliente</p>
                    <h1>Olá, <?= htmlspecialchars($primeiro_nome) ?></h1>
                    <p><?= htmlspecialchars($endereco) ?></p>
                </div>
            </div>
        </div>
        
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <span class="status-pill" style="background:var(--bg-success); color:var(--text-success); border-color:transparent;">Status: <?= $data['status_geral'] ?? 'Ativo' ?></span>
            <span class="status-pill">Etapa: <?= $data['etapa_atual'] ?? 'Início' ?></span>
        </div>

        <!-- Mini Dashboard Stats inside Card -->
        <div class="resume-stats">
            <div class="stat-item" onclick="switchTab('financeiro')">
                <span>Pendências Fin.</span>
                <strong style="color:#ffda6a">R$ <?= number_format($fin_stats['pendente'], 2, ',', '.') ?></strong>
            </div>
            <div class="stat-item" onclick="switchTab('pendencias')">
                <span>Avisos</span>
                <strong><?= count(array_filter($pendencias, fn($p) => $p['status']!='resolvido')) ?> Ativos</strong>
            </div>
        </div>
    </div>

    <!-- 2. NAVIGATION -->
    <div class="nav-tabs">
        <button class="nav-item active" onclick="switchTab('timeline')">Linha do Tempo</button>
        <button class="nav-item" onclick="switchTab('pendencias')">Pendências</button>
        <button class="nav-item" onclick="switchTab('financeiro')">Financeiro</button>
        <button class="nav-item" onclick="switchTab('docs')">Documentos</button>
    </div>

    <!-- 3. VIEWS -->
    
    <!-- VIEW: TIMELINE -->
    <div id="view-timeline" class="view-section fade-in">
        <div class="section-card">
            <h3 class="section-title">Histórico de Movimentações</h3>
            <div class="timeline-stream">
                <?php if(count($timeline) > 0): foreach($timeline as $t): ?>
                    <div class="t-event">
                        <div class="t-dot"></div>
                        <span class="t-date"><?= date('d/m/Y H:i', strtotime($t['data_movimento'])) ?></span>
                        <div class="t-title"><?= htmlspecialchars($t['titulo_fase']) ?></div>
                        <div class="t-desc"><?= nl2br(strip_tags($t['descricao'])) ?></div>
                    </div>
                <?php endforeach; else: ?>
                    <p style="color:var(--text-muted); font-style:italic;">Nenhuma atividade registrada ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- VIEW: PENDENCIES -->
    <div id="view-pendencias" class="view-section hidden fade-in">
        <div class="section-card">
            <h3 class="section-title">Pendências e Solicitações</h3>
            <?php if(count($pendencias) > 0): foreach($pendencias as $p): 
                $is_resolved = $p['status'] === 'resolvido';
                $class = $is_resolved ? 'resolved' : '';
                $status_label = ucfirst($p['status']);
            ?>
                <div class="pendency-item <?= $class ?>">
                    <div class="p-header">
                        <span><?= date('d/m/Y', strtotime($p['data_criacao'])) ?></span>
                        <span><?= $status_label ?></span>
                    </div>
                    <div class="p-body">
                        <?= $p['descricao'] ?> <!-- HTML Allowed from Admin CKEditor -->
                    </div>
                    
                    <?php if(!$is_resolved): ?>
                    <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                        <button class="btn btn-primary" onclick="openUploadModal(<?= $p['id'] ?>)">
                             📤 Enviar Arquivos
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; else: ?>
                <div style="text-align:center; padding:30px; color:var(--text-muted);">
                    <h3>🎉 Tudo em dia!</h3>
                    <p>Você não tem pendências no momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- VIEW: FINANCEIRO -->
    <div id="view-financeiro" class="view-section hidden fade-in">
        <div class="section-card">
            <h3 class="section-title">Financeiro</h3>
            <div class="finance-list">
                <?php if(count($financeiro) > 0): foreach($financeiro as $f): 
                    $color = $f['status']=='pago' ? 'var(--text-success)' : ($f['status']=='atrasado' ? 'var(--text-danger)' : 'var(--text-warning)');
                ?>
                    <div class="finance-item">
                        <div class="f-info">
                            <h4><?= htmlspecialchars($f['descricao']) ?></h4>
                            <span>Venc: <?= date('d/m/Y', strtotime($f['data_vencimento'])) ?> • <?= ucfirst($f['categoria']) ?></span>
                            <?php if($f['link_comprovante']): ?>
                                <a href="<?= $f['link_comprovante'] ?>" target="_blank" style="font-size:0.8rem; color:var(--color-primary); text-decoration:underline;">Ver Comprovante</a>
                            <?php endif; ?>
                        </div>
                        <div class="f-amount">
                            <span class="f-val">R$ <?= number_format($f['valor'], 2, ',', '.') ?></span>
                            <span class="f-status" style="color:<?= $color ?>"><?= strtoupper($f['status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <p style="text-align:center; color:var(--text-muted);">Nenhum registro financeiro.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- VIEW: DOCS -->
    <div id="view-docs" class="view-section hidden fade-in">
        <div class="section-card" style="min-height:400px;">
             <h3 class="section-title">Documentos na Nuvem</h3>
             <?php if($drive_id): ?>
                <iframe src="https://drive.google.com/embeddedfolderview?id=<?= $drive_id ?>#list" style="width:100%; height:500px; border:none; border-radius:var(--radius-sm);"></iframe>
             <?php else: ?>
                <div style="text-align:center; padding:40px; color:var(--text-muted);">
                    <p>A pasta de documentos ainda não foi vinculada.</p>
                </div>
             <?php endif; ?>
        </div>
    </div>

</div>

<!-- FLOATING WHATSAPP -->
<a href="https://wa.me/5562999999999" target="_blank" class="fab-whatsapp" title="Fale Conosco">
    <!-- WhatsApp Icon SVG -->
    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
</a>

<!-- UPLOAD MODAL -->
<div id="uploadModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Anexar Arquivos</h3>
            <button class="modal-close" onclick="closeUploadModal()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_pendencia">
                <input type="hidden" name="p_id" id="modal_p_id">
                
                <p>Selecione um ou mais arquivos (PDF ou Imagens) para enviar.</p>
                <input type="file" name="arquivos[]" multiple accept=".pdf,image/*,.doc,.docx" style="margin-bottom:20px; width:100%;">
                
                <button type="submit" class="btn btn-primary" style="width:100%">Confirmar Envio</button>
            </form>
        </div>
    </div>
</div>

<!-- TOAST -->
<?php if(!empty($msg_toast)): ?>
<div style="position:fixed; bottom:80px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.8); color:white; padding:10px 20px; border-radius:30px; z-index:9999; animation:fadeIn 0.5s;">
    <?= $msg_toast ?>
</div>
<?php endif; ?>

<script>
    // Theme Logic
    function toggleTheme() {
        document.body.parentElement.classList.toggle('dark-mode'); // toggle html
        if(document.body.parentElement.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode'); // ensuring body has it too for old css variables if any
            localStorage.setItem('theme', 'dark');
            document.cookie = "theme=dark; path=/";
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.cookie = "theme=light; path=/";
        }
    }
    
    // Init Theme
    if(localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        document.documentElement.classList.add('dark-mode');
    }

    // Tabs Logic
    function switchTab(tabId) {
        // Buttons
        document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        
        // Views
        document.querySelectorAll('.view-section').forEach(v => v.classList.add('hidden'));
        document.getElementById('view-'+tabId).classList.remove('hidden');
    }

    // Modal Logic
    function openUploadModal(pId) {
        document.getElementById('modal_p_id').value = pId;
        document.getElementById('uploadModal').classList.add('active');
    }
    function closeUploadModal() {
        document.getElementById('uploadModal').classList.remove('active');
    }
</script>

</body>
</html>
