<?php
// Footer Padrão da Área do Cliente
// Deve ser incluído dentro da div .app-container ou logo após o conteúdo principal
?>
<div class="app-footer" style="text-align: center; margin-top: 40px; padding: 30px 20px; background: #f8f9fa; border-top: 1px solid #e9ecef; border-radius: 12px;">
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
        
        <!-- Logo e Info Block -->
        <div style="display: flex; align-items: center; gap: 20px; text-align: left;">
            <!-- Logo (Simbolico) -->
            <div style="border-right: 1px solid #ddd; padding-right: 20px;">
                 <img src="../../assets/logo.png" alt="Vilela Engenharia" style="height: 50px; width: auto; object-fit: contain;">
            </div>
            
            <!-- Info Engineer -->
            <div>
                <span style="display: block; font-size: 0.7rem; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Engenheiro Responsável</span>
                <strong style="display: block; font-size: 1.1rem; color: #333; line-height: 1.2;">Diego T. N. Vilela</strong>
                <span style="display: block; font-size: 0.85rem; color: #666;">CREA 235.474/D</span>
            </div>
        </div>

        <!-- Copyright -->
        <div style="margin-top: 10px; font-size: 0.8rem; color: #aaa;">
            © <?= date('Y') ?> Vilela Engenharia
        </div>
    </div>
</div>

<!-- Fallback Logo se não existir -->
<script>
    document.querySelector('.app-footer img').onerror = function() {
        this.style.display = 'none';
        // Mock Logo Visual
        var mock = document.createElement('div');
        mock.innerHTML = '<span style="font-size:1.5rem; font-weight:800; color:#444;">V<span style="color:#198754;">/</span>E</span><br><span style="font-size:0.5rem; letter-spacing:2px; text-transform:uppercase;">Vilela Engenharia</span>';
        mock.style.textAlign = 'center';
        this.parentElement.appendChild(mock);
    };
</script>
