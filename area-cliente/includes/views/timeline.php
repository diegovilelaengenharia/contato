<div class="view-header-simple">
    <h2>Linha do Tempo</h2>
    <p>O histórico completo do seu sonho.</p>
</div>

<div class="timeline-container fade-in-up">
    <?php if(count($timeline) > 0): foreach($timeline as $t): 
        // Processar descrição e separar comentários do admin
        $parts = explode("||COMENTARIO_USER||", $t['descricao']);
        $sys_desc = $parts[0];
        $admin_note = count($parts) > 1 ? $parts[1] : null;

        // Ícone baseado no titulo
        $icon = '📅'; // default
        if(stripos($t['titulo_fase'], 'Início') !== false) $icon = '🚀';
        if(stripos($t['titulo_fase'], 'Conclusão') !== false || stripos($t['titulo_fase'], 'Pronto') !== false) $icon = '🎉';
        if(stripos($t['titulo_fase'], 'Pendência') !== false) $icon = '⚠️';
        if(stripos($t['titulo_fase'], 'Pagamento') !== false) $icon = '💲';
        if(stripos($t['status_tipo'], 'upload') !== false) $icon = '📎';
    ?>
    
    <div class="timeline-item">
        <div class="tl-icon"><?= $icon ?></div>
        <div class="tl-content">
            <span class="tl-date"><?= date('d/m/Y \à\s H:i', strtotime($t['data_movimento'])) ?></span>
            <h3 class="tl-title"><?= htmlspecialchars($t['titulo_fase']) ?></h3>
            <div class="tl-body">
                <?= $sys_desc ?>
            </div>
            
            <?php if($admin_note): ?>
            <div class="tl-admin-note">
                <strong>👷 Nota do Eng. Diego:</strong>
                <p><?= nl2br(htmlspecialchars($admin_note)) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php endforeach; else: ?>
        <div class="empty-state">
            <p>Nenhuma movimentação registrada ainda.</p>
        </div>
    <?php endif; ?>
</div>
