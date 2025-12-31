<?php
session_start();
require 'db.php';

// Auth Check
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// --- FASES TÉCNICAS (Regularização Oliveira/MG) ---
// --- FASES TÉCNICAS (Processo Administrativo) ---
$fases_padrao = [
    "Protocolo e Autuação", 
    "Análise Documental", 
    "Vistoria Técnica In Loco",
    "Emissão de Laudos/Peças", 
    "Tramitação e Aprovação", 
    "Entrega Final/Habite-se"
];

// --- DATA FETCHING (COMMON) ---
// 1. Client & Details
$stmt = $pdo->prepare("SELECT c.*, d.* FROM clientes c LEFT JOIN processo_detalhes d ON c.id = d.cliente_id WHERE c.id = ?");
$stmt->execute([$cliente_id]);
$data = $stmt->fetch();
if(!$data) { header("Location: logout.php"); exit; }

$nome_parts = explode(' ', trim($data['nome']));
$primeiro_nome = $nome_parts[0];
$endereco = $data['imovel_rua'] ?? ($data['endereco_imovel'] ?? 'Endereço não cadastrado');

// AVATAR LOGIC (Matches Admin Panel behavior: File System Check, not DB)
$avatar_file = glob(__DIR__ . "/uploads/avatars/avatar_{$cliente_id}.*");
$foto_perfil = !empty($avatar_file) ? 'uploads/avatars/' . basename($avatar_file[0]) . '?v=' . time() : null; // Cache busting


// Calculate Progress (Now safely after data fetch)
$etapa_atual = $data['etapa_atual'] ?? '';
$fase_index = array_search($etapa_atual, $fases_padrao);
if($fase_index === false) $fase_index = 0; 
$progresso_porc = min(100, round((($fase_index + 1) / count($fases_padrao)) * 100));

// 2. Timeline
$stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
$stmt->execute([$cliente_id]);
$timeline = $stmt->fetchAll();

// 3. Pendencies
$stmt = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY FIELD(status, 'pendente','anexado','resolvido'), id DESC");
$stmt->execute([$cliente_id]);
$pendencias = $stmt->fetchAll();

// 4. Finance
$stmt = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
$stmt->execute([$cliente_id]);
$financeiro = $stmt->fetchAll();

// 5. Total Docs (Calculated from Pendencies to avoid missing table error)
$total_docs = 0;
foreach($pendencias as $p) {
    if($p['status'] == 'anexado' || $p['status'] == 'resolvido') {
        $total_docs++;
    }
} 

// Financial Stats
$fin_stats = ['total'=>0, 'pago'=>0, 'pendente'=>0];
foreach($financeiro as $f) {
    if($f['status']=='pago') $fin_stats['pago'] += $f['valor'];
    else $fin_stats['pendente'] += $f['valor'];
}

// Drive ID Helper
function getDriveId($url) {
    if (preg_match('/folders\/([a-zA-Z0-9-_]+)/', $url, $m)) return $m[1];
    if (preg_match('/id=([a-zA-Z0-9-_]+)/', $url, $m)) return $m[1];
    return null;
}
$drive_id = !empty($data['link_drive_pasta']) ? getDriveId($data['link_drive_pasta']) : null;

// --- CONTROLLER LOGIC ---
$view = $_GET['view'] ?? 'home';
$allowed_views = ['home', 'timeline', 'pendencias', 'financeiro', 'arquivos', 'perfil', 'conhecimento'];
if(!in_array($view, $allowed_views)) $view = 'home';

// --- HANDLE POST ACTIONS (If any) ---
// (Upload logic was in previous dashboard, kept here just in case, but focused on modal)
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
        // Redirect to same view to avoid resubmission
        header("Location: ?view=pendencias&success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Vilela Engenharia | Área do Cliente</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="icon" href="../assets/logo.png" type="image/png">
    
    <script>
        // Init Dark Mode
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark-mode');
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
    </script>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme']=='dark' ? 'dark-mode' : '' ?>">

<div class="app-container">
    
    <!-- VIEW CONTENT INCLUDED HERE -->
    <?php 
        $view_file = 'includes/views/' . $view . '.php';
        if(file_exists($view_file)) {
            include $view_file;
        } else {
            echo "<div class='empty-state'>Erro ao carregar visualização.</div>";
        }
    ?>

</div>

<!-- BOTTOM NAVIGATION -->
<nav class="bottom-nav">
    <button class="nav-btn <?= $view=='home'?'active':'' ?>" onclick="window.location.href='?view=home'">
        <span class="material-symbols-rounded nav-icon">home</span>
        <span class="nav-label">Início</span>
    </button>
    <button class="nav-btn <?= $view=='timeline'?'active':'' ?>" onclick="window.location.href='?view=timeline'">
        <span class="material-symbols-rounded nav-icon">history</span>
        <span class="nav-label">Timeline</span>
    </button>
    <button class="nav-btn <?= $view=='pendencias'?'active':'' ?>" onclick="window.location.href='?view=pendencias'">
        <span class="material-symbols-rounded nav-icon">assignment_late</span>
        <span class="nav-label">Pendências</span>
    </button>
    <button class="nav-btn <?= $view=='financeiro'?'active':'' ?>" onclick="window.location.href='?view=financeiro'">
        <span class="material-symbols-rounded nav-icon">payments</span>
        <span class="nav-label">Finanças</span>
    </button>
    <button class="nav-btn <?= $view=='arquivos'?'active':'' ?>" onclick="window.location.href='?view=arquivos'">
        <span class="material-symbols-rounded nav-icon">folder</span>
        <span class="nav-label">Acervo</span>
    </button>
    <button class="nav-btn <?= $view=='conhecimento'?'active':'' ?>" onclick="window.location.href='?view=conhecimento'">
        <span class="material-symbols-rounded nav-icon">menu_book</span>
        <span class="nav-label">Guia</span>
    </button>
</nav>

<!-- MODAL UPLOAD (Global) -->
<dialog id="uploadModal" style="border:none; border-radius:16px; padding:20px; width:90%; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
    <h3 style="margin-top:0;">📤 Enviar Arquivos</h3>
    <p>Selecione os documentos solicitados (PDF ou Foto).</p>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_pendencia">
        <input type="hidden" name="p_id" id="modal_p_id">
        <input type="file" name="arquivos[]" multiple required style="margin:20px 0; width:100%;">
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn-block btn-outline" onclick="document.getElementById('uploadModal').close()" style="flex:1;">Cancelar</button>
            <button type="submit" class="btn-block btn-primary" style="background:var(--color-primary); color:white; flex:1;">Enviar</button>
        </div>
    </form>
</dialog>

<script>
    function openUploadModal(pId) {
        document.getElementById('modal_p_id').value = pId;
        document.getElementById('uploadModal').showModal();
    }
</script>

</body>
</html>
