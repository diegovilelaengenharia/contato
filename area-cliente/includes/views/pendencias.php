<div class="view-header-simple">
    <h2>Diligências e Pendências</h2>
    <p>Solicitações de documentos e ações do proprietário.</p>
</div>

<!-- ASSISTANT TIP -->
<div class="assistant-tip fade-in-up">
    <div class="at-icon">⚡</div>
    <div class="at-content">
        <strong>Ação Necessária</strong>
        <p>Abaixo estão listadas as pendências técnicas ou documentais necessárias para o prosseguimento do feito. A regularização depende do saneamento destes itens.</p>
    </div>
</div>

<div class="pendency-list fade-in-up">
    <?php if(count($pendencias) > 0): foreach($pendencias as $p): 
        $is_resolved = $p['status'] === 'resolvido';
        $is_anexo = $p['status'] === 'anexado';
    ?>
        <div class="card-pendency <?= $is_resolved ? 'resolved' : '' ?>">
            <div class="pendency-header">
                <span class="pendency-date"><?= date('d/m/Y', strtotime($p['data_criacao'])) ?></span>
                <?php if($is_resolved): ?>
                    <span class="badge badge-success">Resolvido</span>
                <?php elseif($is_anexo): ?>
                    <span class="badge badge-info">Em Análise</span>
                <?php else: ?>
                    <span class="badge badge-warning">Pendente</span>
                <?php endif; ?>
            </div>
            
            <div class="pendency-body">
                <?= $p['descricao'] ?>
            </div>
            
            <?php if(!$is_resolved): ?>
            <div class="pendency-actions">
                <button class="btn-action" onclick="openUploadModal(<?= $p['id'] ?>)">
                    <span class="material-symbols-rounded">cloud_upload</span>
                    Anexar Resposta
                </button>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; else: ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <h3>Tudo Certo!</h3>
            <p>Você não tem nenhuma pendência em aberto.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Floating Action Button for Support -->
<a href="https://wa.me/5535984529577?text=Tenho%20d%C3%BAvida%20sobre%20uma%20pend%C3%AAncia" target="_blank" class="fab-support">
    💬 Ajuda
</a>
