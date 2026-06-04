<?php
/**
 * configuracoes.php — View Administrativa de Configurações do Sistema.
 *
 * Permite gerenciar o Modo Manutenção, editar preferências de rodapé (CREA,
 * email, telefone), atualizar a senha mestre e realizar backups SQL.
 */

// --- 1. LÓGICA DE BACKUP DE BANCO DE DADOS (DOWNLOAD SQL) ---
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    ob_clean(); // Limpa buffers de saída para evitar sujeira no SQL
    
    $tables = ['clientes', 'processo_detalhes', 'processo_movimentos', 'processo_financeiro', 'processo_pendencias', 'processo_campos_extras', 'admin_settings', 'processo_entregaveis', 'processo_docs_entregues'];
    $sql_dump = "-- Vilela Engenharia Database Backup\n-- Gerado em: " . date('d/m/Y H:i:s') . "\n\n";

    try {
        foreach ($tables as $table) {
            // Verifica se a tabela existe
            $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if (!$check) continue;

            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            $sql_dump .= "-- Estrutura de dados para a tabela: $table\n";
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $vals = array_map(function($v) use ($pdo) { 
                    return $v === null ? "NULL" : $pdo->quote($v); 
                }, array_values($row));
                $sql_dump .= "INSERT INTO $table (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
            }
            $sql_dump .= "\n";
        }
        
        // Registra a ação no log de auditoria
        Logger::log('BACKUP', 'banco_dados', null, ['tabelas_exportadas' => $tables]);

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="backup_vilela_' . date('Ymd_His') . '.sql"');
        echo $sql_dump;
        exit;
    } catch (Exception $e) {
        error_log("Erro ao gerar backup SQL: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erro ao gerar cópia de backup do banco de dados.'];
        header("Location: ?route=configuracoes");
        exit;
    }
}

// --- 2. SALVAR PREFERÊNCIAS E CONFIGURAÇÕES GERAIS ---
if (isset($_POST['save_settings_admin'])) {
    // Validação CSRF
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token de segurança CSRF inválido.'];
        header("Location: ?route=configuracoes");
        exit;
    }

    $settings = [
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'company_phone' => trim($_POST['company_phone']),
        'company_email' => trim($_POST['company_email']),
        'company_crea' => trim($_POST['company_crea']),
        'notify_email' => isset($_POST['notify_email']) ? 1 : 0
    ];

    try {
        foreach ($settings as $key => $val) {
            $chk = $pdo->prepare("SELECT id FROM admin_settings WHERE setting_key = ?");
            $chk->execute([$key]);
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $key]);
            } else {
                $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $val]);
            }
        }

        // Auditoria
        Logger::log('UPDATE', 'configuracoes', null, $settings);

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Configurações gerais salvas com sucesso!'];
        header("Location: ?route=configuracoes");
        exit;
    } catch (Exception $e) {
        error_log("Erro ao salvar configurações gerais: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Falha ao salvar as configurações no banco de dados.'];
    }
}

// --- 3. ALTERAR SENHA MESTRE ADMIN ---
if (isset($_POST['update_password_admin'])) {
    // Validação CSRF
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token de segurança CSRF inválido.'];
        header("Location: ?route=configuracoes");
        exit;
    }

    $new_pass = trim($_POST['new_password']);
    
    if (strlen($new_pass) < 6) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'A senha deve conter no mínimo 6 caracteres.'];
    } else {
        try {
            // Salva no banco de dados (admin_settings) — sem depender de permissão de arquivo no servidor
            // A senha é hasheada via bcrypt de forma segura
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $chk = $pdo->prepare("SELECT id FROM admin_settings WHERE setting_key = 'admin_password'");
            $chk->execute();
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = 'admin_password'")->execute([$hashed_pass]);
            } else {
                $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('admin_password', ?)")->execute([$hashed_pass]);
            }

            // Auditoria
            Logger::log('UPDATE_PASSWORD', 'admin_credential', null, null);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Senha master do admin alterada com sucesso!'];
        } catch (Exception $e) {
            error_log("Erro ao atualizar senha admin no banco: " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Falha ao salvar a nova senha no banco de dados.'];
        }
    }
    header("Location: ?route=configuracoes");
    exit;
}


// --- DEFINE VARIÁVEIS DO BANCO PARA EXIBIÇÃO ---
$maint_mode = $curr_settings['maintenance_mode'] ?? 0;
$c_phone = $curr_settings['company_phone'] ?? '(35) 98452-9577';
$c_email = $curr_settings['company_email'] ?? 'vilela.eng.mg@gmail.com';
$c_crea = $curr_settings['company_crea'] ?? 'MG 235474/D';
$notify = $curr_settings['notify_email'] ?? 0;
?>

<div class="page-head">
    <h1>Configurações Globais</h1>
    <p>Gerencie as preferências de rodapé de relatórios, modo manutenção e segurança master.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
    
    <!-- Lado Esquerdo: Formulários -->
    <div>
        
        <!-- Formulário A: Preferências Gerais e Manutenção -->
        <div class="config-card">
            <div class="config-title">
                <span class="material-symbols-rounded">tune</span> Preferências & Customização
            </div>
            
            <form method="POST">
                <?php echo Csrf::getHtmlField(); ?>
                
                <!-- Caixa de Alerta do Modo Manutenção -->
                <div style="background: rgba(220, 53, 69, 0.04); border: 1px solid rgba(220, 53, 69, 0.2); padding: 18px; border-radius: 10px; margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; font-weight: 700; color: var(--color-danger); font-size: 0.98rem; margin-bottom: 6px;">
                        <input type="checkbox" name="maintenance_mode" value="1" <?php echo $maint_mode ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                        <span>🚧 Ativar Modo Manutenção</span>
                    </label>
                    <p style="margin: 0 0 0 30px; font-size: 0.85rem; color: var(--color-text-subtle); line-height: 1.4;">
                        Quando ativo, **todos os clientes serão impedidos de logar** no portal. Apenas você (Administrador) terá acesso garantido para manutenções ou auditorias.
                    </p>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Telefone / WhatsApp (PDF & Rodapés)</label>
                        <input type="text" name="company_phone" value="<?php echo htmlspecialchars($c_phone); ?>" class="admin-form-input">
                    </div>
                    <div class="form-group">
                        <label>E-mail de Contato Comercial</label>
                        <input type="email" name="company_email" value="<?php echo htmlspecialchars($c_email); ?>" class="admin-form-input">
                    </div>
                </div>

                <div class="form-grid" style="margin-top: 10px;">
                    <div class="form-group">
                        <label>Registro Profissional Principal (CREA/CAU)</label>
                        <input type="text" name="company_crea" value="<?php echo htmlspecialchars($c_crea); ?>" class="admin-form-input">
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; padding-top: 20px;">
                        <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="notify_email" value="1" <?php echo $notify ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-weight: 600; font-size: 0.85rem;">Notificar novas uploads de cliente por e-mail</span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="save_settings_admin" class="btn-save btn-primary" style="margin-top: 15px; padding: 11px 24px; border-radius: 8px;">
                    <span class="material-symbols-rounded">save</span> Salvar Preferências
                </button>
            </form>
        </div>

        <!-- Formulário B: Alteração de Senha Master Admin -->
        <div class="config-card">
            <div class="config-title">
                <span class="material-symbols-rounded">lock</span> Credenciais de Segurança
            </div>
            
            <form method="POST">
                <?php echo Csrf::getHtmlField(); ?>
                <p style="font-size: 0.88rem; color: var(--color-text-subtle); margin-bottom: 16px; line-height: 1.4;">
                    Atualize a senha mestre de acesso do administrador. Esta senha é criptografada e salva no arquivo `.env` do servidor.
                </p>

                <div class="form-group" style="max-width: 400px; margin-bottom: 20px;">
                    <label>Nova Senha Master (Mínimo 6 caracteres)</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Digite uma nova senha master segura..." class="admin-form-input">
                </div>

                <button type="submit" name="update_password_admin" class="btn-save btn-primary" style="padding: 11px 24px; border-radius: 8px;">
                    <span class="material-symbols-rounded">vpn_key</span> Alterar Senha Master
                </button>
            </form>
        </div>

    </div>

    <!-- Lado Direito: Caixa de Ferramentas / Backup -->
    <div class="form-card" style="padding: 20px; background: #fafbfc; border: 1px solid var(--color-border); position: sticky; top: 10px;">
        <h4 style="margin: 0 0 14px 0; color: var(--color-primary-dark); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded">database</span> Ferramentas de Dados
        </h4>
        
        <div style="background: white; border: 1px solid var(--color-border); padding: 16px; border-radius: 10px; box-shadow: var(--shadow-xs);">
            <h5 style="margin: 0 0 6px 0; font-size: 0.88rem; font-weight: 700; color: var(--color-text);">Backup SQL Completo</h5>
            <p style="font-size: 0.78rem; color: var(--color-text-subtle); margin-bottom: 15px; line-height: 1.4;">
                Baixe uma cópia compacta em arquivo SQL contendo todas as faturas, timelines, logs de auditoria e informações dos clientes cadastrados.
            </p>
            
            <a href="?route=configuracoes&action=backup" class="btn-save" style="background: var(--color-primary); color: white; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; border: none; text-decoration: none; border-radius: 8px; font-size: 0.85rem; padding: 10px;">
                <span class="material-symbols-rounded">download</span> Baixar Cópia (.SQL)
            </a>
        </div>
        
        <p style="font-size: 0.75rem; color: var(--color-muted); text-align: center; margin-top: 15px; line-height: 1.3;">
            Recomenda-se realizar backups periódicos antes de grandes alterações financeiras ou de contratos.
        </p>
    </div>

</div>
