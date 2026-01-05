<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/init.php';
require 'includes/schema.php'; // Ensure table exists

// Security Check
if (!isset($_SESSION['admin_logado'])) {
    header("Location: index.php");
    exit;
}

$msg = '';

// --- ACTIONS ---

// 1. Alterar Senha Admin
if (isset($_POST['update_password'])) {
    $new_pass = trim($_POST['new_password']);
    if (strlen($new_pass) < 6) {
        $msg = "❌ A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Read db.php
        $db_file = 'db.php';
        $content = file_get_contents($db_file);
        
        // Replace constant define('ADMIN_PASSWORD', '... Old ...');
        // Pattern: define('ADMIN_PASSWORD',\s*'[^']*');
        $pattern = "/define\('ADMIN_PASSWORD',\s*'([^']*)'\);/";
        $replacement = "define('ADMIN_PASSWORD', '$new_pass');";
        
        if (preg_match($pattern, $content)) {
            $new_content = preg_replace($pattern, $replacement, $content);
            if (file_put_contents($db_file, $new_content)) {
                $msg = "✅ Senha alterada com sucesso! A nova senha já está valendo.";
            } else {
                $msg = "❌ Erro ao escrever no arquivo db.php. Verifique as permissões.";
            }
        } else {
            $msg = "❌ Não foi possível localizar a definição de senha no arquivo db.php.";
        }
    }
}

// 2. Salvar Configurações Gerais
if (isset($_POST['save_settings'])) {
    $settings = [
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'company_phone' => $_POST['company_phone'],
        'company_email' => $_POST['company_email'],
        'company_crea' => $_POST['company_crea'],
        'notify_email' => isset($_POST['notify_email']) ? 1 : 0
    ];

    foreach($settings as $key => $val) {
        // Check if exists
        $chk = $pdo->prepare("SELECT id FROM admin_settings WHERE setting_key = ?");
        $chk->execute([$key]);
        if($chk->fetch()) {
            $pdo->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $key]);
        } else {
            $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $val]);
        }
    }
    $msg = "✅ Configurações salvas!";
}

// 3. Backup Database (Download SQL)
if (isset($_GET['action']) && $_GET['action'] == 'backup') {
    // Basic SQL Dump Logic
    $tables = ['clientes', 'processo_detalhes', 'processo_movimentos', 'processo_financeiro', 'processo_pendencias', 'processo_campos_extras', 'admin_settings'];
    $sql_dump = "-- Vilela Engenharia Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    foreach($tables as $table) {
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        $sql_dump .= "-- Table: $table\n";
        foreach($rows as $row) {
            $cols = array_keys($row);
            $vals = array_map(function($v){ return $v === null ? "NULL" : "'".addslashes($v)."'"; }, array_values($row));
            $sql_dump .= "INSERT INTO $table (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
        }
        $sql_dump .= "\n";
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup_vilela_'.date('Ymd_His').'.sql"');
    echo $sql_dump;
    exit;
}

// 4. Limpeza de Logs (Simulação - Delete notificações antigas)
if (isset($_POST['clean_logs'])) {
    // Example: Delete old notifications if table exists, or clear old movements (dangerous, skipping for now)
    // For now, removing unresolved pre-registrations older than 30 days
    $pdo->query("DELETE FROM pre_cadastros WHERE status='pendente' AND data_solicitacao < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $msg = "✅ Limpeza realizada (Pré-cadastros antigos removidos).";
}


// --- FETCH CURRENT SETTINGS ---
$curr_settings = [];
$rows = $pdo->query("SELECT * FROM admin_settings")->fetchAll();
foreach($rows as $r) {
    echo "<!-- Loaded: {$r['setting_key']} -->"; 
    $curr_settings[$r['setting_key']] = $r['setting_value'];
}

// Defaults
$maint_mode = $curr_settings['maintenance_mode'] ?? 0;
$c_phone = $curr_settings['company_phone'] ?? '(35) 98452-9577';
$c_email = $curr_settings['company_email'] ?? 'vilela.eng.mg@gmail.com';
$c_crea = $curr_settings['company_crea'] ?? 'MG 235474/D';
$notify = $curr_settings['notify_email'] ?? 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações | Vilela Engenharia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css?v=<?= time() ?>">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <style>
        .config-card { background:white; padding:25px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:20px; }
        .config-title { font-size:1.2rem; font-weight:700; color:var(--color-primary); margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px; display:flex; align-items:center; gap:10px; }
        .form-row { display:flex; gap:20px; margin-bottom:15px; }
        .form-col { flex:1; }
        label { display:block; margin-bottom:5px; font-weight:600; color:#555; font-size:0.9rem; }
        input[type="text"], input[type="password"], select { width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; }
        .btn-action { padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:0.2s; }
        .btn-primary { background:var(--color-primary); color:white; }
        .btn-danger { background:#dc3545; color:white; }
        .btn-warning { background:#ffc107; color:#333; }
        .btn-primary:hover { opacity:0.9; }
    </style>
</head>
<body>
    <?php require 'includes/ui/header.php'; ?>

    <div class="admin-container" style="max-width:900px; margin:30px auto; padding:0 20px;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1 style="margin:0; color:#333;">⚙️ Configurações do Sistema</h1>
            <a href="gestao_admin_99.php" class="btn-action" style="background:#e9ecef; color:#444;">← Voltar ao Painel</a>
        </div>

        <?php if($msg): ?>
            <div style="padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold; text-align:center;
                <?= strpos($msg, '✅') !== false ? 'background:#d1e7dd; color:#0f5132;' : 'background:#f8d7da; color:#842029;' ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <!-- 1. ALTERAR SENHA -->
        <div class="config-card">
            <div class="config-title">
                <span class="material-symbols-rounded">lock</span> Segurança e Acesso
            </div>
            <form method="POST">
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">Atualize a senha mestre de acesso ao painel administrativo. Esta alteração será refletida imediatamente.</p>
                <div class="form-row">
                    <div class="form-col">
                        <label>Nova Senha de Administrador</label>
                        <input type="password" name="new_password" required minlength="6" placeholder="Digite a nova senha segura...">
                    </div>
                </div>
                <button type="submit" name="update_password" class="btn-action btn-primary">
                    <span class="material-symbols-rounded">save</span> Alterar Senha
                </button>
            </form>
        </div>

        <!-- 2. PERSONALIZAÇÃO -->
        <div class="config-card">
            <div class="config-title">
                <span class="material-symbols-rounded">tune</span> Preferências & Sistema
            </div>
            <form method="POST">
                
                <div style="background:#fff3cd; color:#856404; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ffecb5;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:bold; font-size:1rem;">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= $maint_mode?'checked':'' ?> style="width:20px; height:20px;">
                        <span>🚧 Ativar MODO MANUTENÇÃO</span>
                    </label>
                    <p style="margin:5px 0 0 30px; font-size:0.9rem;">
                        Quando ativo, <strong>os clientes não conseguirão acessar a plataforma</strong>. Apenas você (Administrador) terá acesso.
                    </p>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Telefone / WhatsApp (Rodapé PDF)</label>
                        <input type="text" name="company_phone" value="<?= htmlspecialchars($c_phone) ?>">
                    </div>
                    <div class="form-col">
                        <label>Email de Contato (Rodapé PDF)</label>
                        <input type="text" name="company_email" value="<?= htmlspecialchars($c_email) ?>">
                    </div>
                </div>
                <div class="form-row">
                <div class="form-col">
                        <label>Registro Profissional (CREA/CAU)</label>
                        <input type="text" name="company_crea" value="<?= htmlspecialchars($c_crea) ?>">
                    </div>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="notify_email" value="1" <?= $notify?'checked':'' ?> style="width:20px; height:20px;">
                        <span>Receber notificações por email (Novos clientes / Documentos enviados)</span>
                    </label>
                </div>

                <button type="submit" name="save_settings" class="btn-action btn-primary">
                    <span class="material-symbols-rounded">save</span> Salvar Configurações
                </button>
            </form>
        </div>

        <!-- 3. FERRAMENTAS -->
        <div class="config-card">
            <div class="config-title">
                <span class="material-symbols-rounded">database</span> Ferramentas de Banco de Dados
            </div>
            <div style="display:flex; gap:15px; flex-wrap:wrap;">
                
                <!-- Backup -->
                <div style="flex:1; min-width:250px; background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #e9ecef;">
                    <h4 style="margin:0 0 10px 0;">📦 Backup Completo</h4>
                    <p style="font-size:0.85rem; color:#666; margin-bottom:15px;">Baixe uma cópia de segurança de todos os clientes, processos e histórico financeiro.</p>
                    <a href="?action=backup" class="btn-action" style="background:#0d6efd; color:white;">
                        <span class="material-symbols-rounded">download</span> Baixar Backup (.sql)
                    </a>
                </div>



            </div>
        </div>

    </div>

    <!-- Script para Toastify se necessário -->
    <?php if(!empty($msg)): ?>
    <script>
        Toastify({
            text: "<?= strip_tags($msg) ?>",
            duration: 4000,
            gravity: "top", 
            position: "right", 
            style: { background: "<?= strpos($msg, '✅')!==false ? '#198754' : '#dc3545' ?>" }
        }).showToast();
    </script>
    <?php endif; ?>

</body>
</html>
