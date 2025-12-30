<div class="view-header-simple">
    <h2>Meu Perfil</h2>
    <p>Seus dados cadastrais.</p>
</div>

<div class="profile-container fade-in-up">
    <!-- AVATAR CARD -->
    <div class="profile-card center-text">
        <div class="big-avatar">
            <?php if(!empty($data['foto_perfil']) && file_exists(__DIR__ . '/../../' . $data['foto_perfil'])): ?>
                <img src="<?= htmlspecialchars($data['foto_perfil']) ?>" alt="Foto">
            <?php else: ?>
                <span><?= strtoupper(substr($primeiro_nome, 0, 1)) ?></span>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($data['nome']) ?></h2>
        <p class="role-badge">Cliente VIP</p>
    </div>

    <!-- DETAILS -->
    <div class="data-group">
        <label>CPF / CNPJ</label>
        <div class="data-val"><?= htmlspecialchars($data['cpf_cnpj'] ?? '--') ?></div>
    </div>
    
    <div class="data-group">
        <label>Contato</label>
        <div class="data-val"><?= htmlspecialchars($data['telefone'] ?? '--') ?></div>
        <div class="data-val"><?= htmlspecialchars($data['email'] ?? '--') ?></div>
    </div>

    <div class="data-group">
        <label>Endereço da Obra</label>
        <div class="data-val"><?= htmlspecialchars($endereco) ?></div>
    </div>

    <!-- ACTIONS -->
    <div class="profile-actions">
        <button class="btn-block" onclick="toggleTheme()">
            <span class="material-symbols-rounded">dark_mode</span>
            Alternar Tema (Claro/Escuro)
        </button>
        
        <a href="logout.php" class="btn-block btn-danger">
            <span class="material-symbols-rounded">logout</span>
            Sair da Conta
        </a>
    </div>
    
    <!-- FOOTER INFO -->
    <div class="app-footer-info">
        <p>Vilela Engenharia App v2.0</p>
        <p>Desenvolvido para você.</p>
    </div>
</div>
