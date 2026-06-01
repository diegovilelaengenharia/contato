<?php
/**
 * avisos.php — View Administrativa de Central de Aviso Global.
 *
 * Permite publicar comunicados importantes que aparecem em destaque
 * na Área do Cliente e gerenciar o histórico recente de publicações.
 */

// --- 1. SALVAR / DESATIVAR AVISO ---
if (isset($_POST['btn_salvar_aviso_admin'])) {
    // Validação CSRF
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token de segurança CSRF inválido.'];
        header("Location: ?route=avisos");
        exit;
    }

    $msg = trim($_POST['mensagem_aviso']);
    $action = $_POST['btn_salvar_aviso_admin'];

    try {
        // Desativa todos os anteriores
        $pdo->query("UPDATE sistema_avisos SET ativo = 0");

        if ($action === 'publicar' && !empty($msg)) {
            $stmt = $pdo->prepare("INSERT INTO sistema_avisos (mensagem, ativo) VALUES (?, 1)");
            $stmt->execute([$msg]);
            
            // Auditoria
            Logger::log('CREATE', 'sistema_aviso', null, ['mensagem' => $msg]);

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Aviso global publicado com sucesso!'];
        } else {
            // Auditoria
            Logger::log('DELETE', 'sistema_aviso', null, ['detalhes' => 'Aviso global removido']);

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Aviso global removido do portal dos clientes.'];
        }
        header("Location: ?route=avisos");
        exit;
    } catch (Exception $e) {
        error_log("Erro ao salvar aviso global: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Falha ao processar operação no banco de dados.'];
    }
}

// --- 2. REATIVAR AVISO ANTIGO ---
if (isset($_GET['reativar_aviso'])) {
    $id_reativar = (int)$_GET['reativar_aviso'];
    try {
        $msg_antiga = $pdo->query("SELECT mensagem FROM sistema_avisos WHERE id = $id_reativar")->fetchColumn();
        if ($msg_antiga) {
            $pdo->query("UPDATE sistema_avisos SET ativo = 0");
            $stmt = $pdo->prepare("INSERT INTO sistema_avisos (mensagem, ativo) VALUES (?, 1)");
            $stmt->execute([$msg_antiga]);

            // Auditoria
            Logger::log('CREATE', 'sistema_aviso', $id_reativar, ['mensagem' => $msg_antiga, 'acao' => 'reativar']);

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Aviso antigo reativado com sucesso!'];
        }
        header("Location: ?route=avisos");
        exit;
    } catch (Exception $e) {
        error_log("Erro ao reativar aviso antigo: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Falha ao reativar o aviso selecionado.'];
    }
}

// --- 3. CARREGA DADOS PARA RENDERIZAR ---
$aviso_ativo = null;
$historico = [];

try {
    $aviso_ativo = $pdo->query("SELECT * FROM sistema_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1")->fetch();
    $historico = $pdo->query("SELECT * FROM sistema_avisos ORDER BY data_criacao DESC LIMIT 10")->fetchAll();
} catch (Exception $e) {
    error_log("Erro ao carregar avisos: " . $e->getMessage());
}
?>

<div class="page-head">
    <h1>Aviso Global para Clientes</h1>
    <p>Publique mensagens em destaque (como férias, recessos ou comunicados urgentes) na página inicial do portal dos clientes.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
    
    <!-- Lado Esquerdo: Publicar e Histórico -->
    <div>
        
        <!-- Formulário de Publicação -->
        <div class="config-card">
            <div class="config-title">
                <span class="material-symbols-rounded">campaign</span> Publicar / Atualizar Comunicado
            </div>
            
            <form method="POST">
                <?php echo Csrf::getHtmlField(); ?>
                
                <div class="form-group" style="margin-bottom: 18px;">
                    <label>Mensagem do Comunicado</label>
                    <textarea name="mensagem_aviso" rows="5" required placeholder="Digite sua mensagem de aviso de forma clara..." class="admin-form-input" style="font-family: inherit; resize: vertical;"><?php echo $aviso_ativo ? htmlspecialchars($aviso_ativo['mensagem']) : ''; ?></textarea>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <span style="font-size: 0.78rem; color: var(--color-text-subtle);">
                        * Ao publicar, o aviso ativo anterior é inativado automaticamente.
                    </span>
                    <div style="display: flex; gap: 8px;">
                        <?php if ($aviso_ativo): ?>
                            <button type="submit" name="btn_salvar_aviso_admin" value="remover" class="btn-save btn-danger" style="border: none; padding: 10px 20px; border-radius: 8px;">
                                Remover Aviso
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="btn_salvar_aviso_admin" value="publicar" class="btn-save btn-primary" style="padding: 10px 24px; border-radius: 8px;">
                            Publicar Comunicado
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Histórico de Avisos Recentes -->
        <div class="config-card">
            <div class="config-title" style="margin-bottom: 18px;">
                <span class="material-symbols-rounded">history</span> Histórico de Publicações Recentes
            </div>
            
            <?php if (empty($historico)): ?>
                <p style="font-style: italic; color: var(--color-muted); font-size: 0.88rem;">Nenhum aviso publicado anteriormente.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($historico as $h): 
                        $isActive = ($h['ativo'] == 1);
                        $border = $isActive ? 'border-left: 4px solid var(--color-primary); background: var(--color-primary-tint);' : 'border-left: 4px solid var(--color-border);';
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--color-border); border-radius: 10px; <?php echo $border; ?>">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                    <span style="font-size: 0.78rem; color: var(--color-text-subtle); font-weight: 600;">
                                        <?php echo date('d/m/Y H:i', strtotime($h['data_criacao'])); ?>
                                    </span>
                                    <?php if ($isActive): ?>
                                        <span class="status-badge success" style="font-size: 0.65rem; padding: 2px 8px;">Ativo</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="font-size: 0.65rem; padding: 2px 8px; background: #eee; color: #777;">Inativo</span>
                                    <?php endif; ?>
                                </div>
                                <p style="margin: 0; font-size: 0.88rem; color: var(--color-text); line-height: 1.4; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?php echo htmlspecialchars($h['mensagem']); ?>">
                                    <?php echo htmlspecialchars($h['mensagem']); ?>
                                </p>
                            </div>
                            
                            <?php if (!$isActive): ?>
                                <a href="?route=avisos&reativar_aviso=<?php echo $h['id']; ?>" class="btn-save btn-ghost" 
                                   style="padding: 6px 12px; font-size: 0.78rem; font-weight: 700; border-radius: 6px; margin-left: 15px; text-decoration: none;">
                                    Reutilizar
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Lado Direito: Preview no Celular do Cliente -->
    <div class="form-card" style="padding: 20px; background: #fafbfc; border: 1px solid var(--color-border); position: sticky; top: 10px;">
        <h4 style="margin: 0 0 14px 0; color: var(--color-primary-dark); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded">phone_iphone</span> Visualização no Portal
        </h4>
        
        <p style="font-size: 0.78rem; color: var(--color-text-subtle); margin-bottom: 15px; line-height: 1.4;">
            Veja abaixo como o aviso ativo aparece na página inicial do cliente:
        </p>

        <?php if ($aviso_ativo): ?>
            <div style="background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%); color: white; padding: 16px; border-radius: 12px; box-shadow: 0 8px 24px rgba(102, 16, 242, 0.25); display: flex; gap: 12px; align-items: start; animation: fadeIn 0.3s;">
                <div style="background: rgba(255, 255, 255, 0.2); width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: white;">
                    <span class="material-symbols-rounded" style="font-size: 1.25rem;">campaign</span>
                </div>
                <div style="min-width: 0;">
                    <h5 style="margin: 0 0 4px 0; font-size: 0.85rem; font-weight: 700; color: white; text-transform: uppercase; letter-spacing: 0.5px;">Comunicado Importante</h5>
                    <p style="margin: 0; font-size: 0.82rem; line-height: 1.3; opacity: 0.95; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($aviso_ativo['mensagem'])); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div style="padding: 24px 16px; border: 2px dashed var(--color-border); border-radius: 12px; text-align: center; color: var(--color-muted); font-size: 0.82rem;">
                <span class="material-symbols-rounded" style="font-size: 1.8rem; display: block; margin-bottom: 6px; opacity: 0.6;">unpublished</span>
                Nenhum comunicado ativo no momento.
            </div>
        <?php endif; ?>
    </div>

</div>
