<!-- Welcome Popup -->
<?php if($show_welcome_popup): ?>
<div id="welcomeRunning" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; justify-content:center; align-items:center; opacity:0; pointer-events:none; transition: opacity 0.5s ease;">
    <div style="background:white; padding:40px; border-radius:16px; width:90%; max-width:400px; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,0.2); transform: translateY(20px); transition: transform 0.5s ease;">
        <div style="font-size:3rem; margin-bottom:15px;">👷‍♂️</div>
        <h2 style="color:var(--color-primary); margin:0 0 10px 0;">Bem-vindo, Eng. Diego!</h2>
        <p style="color:var(--color-text-subtle); margin-bottom:25px; line-height:1.5;">O Painel Administrativo está pronto para uso.<br>Bom trabalho hoje!</p>
        <button onclick="closeWelcome()" class="btn-save" style="margin:0; width:100%;">Iniciar Gestão</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('welcomeRunning');
        const card = popup.querySelector('div');
        
        // Show
        setTimeout(() => {
            if(popup) {
                popup.style.opacity = '1';
                popup.style.pointerEvents = 'all';
                card.style.transform = 'translateY(0)';
            }
        }, 100);

        window.closeWelcome = function() {
            if(popup) {
                popup.style.opacity = '0';
                popup.style.pointerEvents = 'none';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => { popup.remove(); }, 500);
            }
        }
    });
</script>
<?php endif; ?>

<!-- MODAL NOTIFICAÇÕES (GLOBAL) -->
<dialog id="modalNotificacoes" style="border:none; border-radius:12px; width:90%; max-width:600px; padding:0; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
    <div style="background:var(--color-primary); color:white; padding:15px 20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.1rem;">🔔 Avisos e Atualizações</h3>
        <button onclick="document.getElementById('modalNotificacoes').close()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div style="padding:20px; max-height:60vh; overflow-y:auto;">
        
        <!-- 1. Novos Cadastros -->
        <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; color:#dc3545; margin-top:0;">📥 Solicitações Web (Pendentes)</h4>
        <?php 
        $notif_pre = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC LIMIT 5")->fetchAll();
        if(count($notif_pre) > 0): ?>
            <ul style="list-style:none; padding:0; margin-bottom:20px;">
                <?php foreach($notif_pre as $np): ?>
                    <li style="padding:10px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong><?= htmlspecialchars($np['nome']) ?></strong><br>
                            <small style="color:#888;"><?= date('d/m H:i', strtotime($np['data_solicitacao'])) ?> • <?= htmlspecialchars($np['tipo_servico']) ?></small>
                        </div>
                        <a href="?importar=true" style="font-size:0.8rem; background:#dc3545; color:white; padding:4px 8px; text-decoration:none; border-radius:4px;">Ver</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color:#aaa; font-style:italic; font-size:0.9rem;">Nenhuma solicitação pendente.</p>
        <?php endif; ?>

        <!-- 2. Últimas Movimentações -->
        <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; color:var(--color-primary); margin-top:20px;">🔄 Últimas Alterações de Processo</h4>
        <?php 
        // Busca últimas 10 movimentações de QUALQUER cliente, juntando com nome do cliente
        $sql_log = "SELECT m.*, c.nome as cliente_nome 
                    FROM processo_movimentos m 
                    JOIN clientes c ON m.cliente_id = c.id 
                    ORDER BY m.data_movimento DESC LIMIT 10";
        $notif_mov = $pdo->query($sql_log)->fetchAll();
        
        if(count($notif_mov) > 0): ?>
            <ul style="list-style:none; padding:0;">
                <?php foreach($notif_mov as $nm): ?>
                    <li style="padding:10px; border-bottom:1px solid #f0f0f0;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-weight:bold; color:#333; font-size:0.9rem;"><?= htmlspecialchars(explode(' ', $nm['cliente_nome'])[0]) ?>...</span>
                            <small style="color:#888;"><?= date('d/m H:i', strtotime($nm['data_movimento'])) ?></small>
                        </div>
                        <div style="font-size:0.85rem; color:#555;">
                            <?= htmlspecialchars($nm['titulo_fase']) ?>
                        </div>
                        <a href="?cliente_id=<?= $nm['cliente_id'] ?>" style="font-size:0.75rem; color:var(--color-primary); text-decoration:none; display:block; margin-top:4px;">Ir para Cliente →</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color:#aaa; font-style:italic; font-size:0.9rem;">Nenhuma atividade recente.</p>
        <?php endif; ?>

    </div>
</dialog>

<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.2); max-width:400px; width:90%;">
        <div style="font-size:3rem; margin-bottom:10px;">✅</div>
        <h3 id="successModalTitle" style="margin:0 0 10px 0; color:var(--color-primary);">Sucesso!</h3>
        <p id="successModalText" style="color:#666; margin-bottom:20px;">Operação realizada com sucesso.</p>
        <button onclick="closeSuccessModal()" class="btn-save" style="width:100%; margin:0;">OK</button>
    </div>
</div>
<script>
    function closeSuccessModal() {
        document.getElementById('successModal').style.display='none';
    }
</script>
