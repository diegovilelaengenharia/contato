<?php
/**
 * Componente: Footer Scripts
 * Scripts globais, modais de sucesso e fechamento do body
 */
?>
    <!-- Global Modals -->
    <?php require 'includes/modals/geral.php'; ?>

    <script>
        // Check URL for success messages
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            if(msg === 'pendencia_emitted') {
                showSuccessModal('Pendência Emitida!', 'A pendência foi publicada na lista e o quadro foi limpo com sucesso.');
            } else if (msg === 'pendencia_updated') {
                showSuccessModal('Pendência Atualizada!', 'As alterações foram salvas com sucesso.');
            } else if (msg === 'hist_deleted') {
                showSuccessModal('Histórico Apagado!', 'O item de histórico foi removido com sucesso.');
            } else if (msg === 'entregavel_added') {
                showSuccessModal('Documento Enviado!', 'O arquivo foi disponibilizado para o cliente com sucesso.');
            } else if (msg === 'entregavel_deleted') {
                showSuccessModal('Documento Excluído!', 'O arquivo foi removido permanentemente.');
            } else if (msg === 'mov_added') {
                showSuccessModal('Andamento Registrado!', 'A movimentação foi adicionada à timeline com sucesso.');
            } else if (msg === 'fin_added') {
                showSuccessModal('Lançamento Financeiro!', 'A cobrança/taxa foi registrada no fluxo financeiro.');
            } else if (msg === 'pend_added') {
                showSuccessModal('Pendência Criada!', 'O item foi adicionado à lista de pendências do cliente.');
            }
            
            // Clean URL
            if(msg) {
                const newUrl = window.location.pathname + window.location.search.replace(/&?msg=[^&]*/, '');
                window.history.replaceState({}, document.title, newUrl);
            }
        });

        function showSuccessModal(title, text) {
            document.getElementById('successModalTitle').innerText = title;
            document.getElementById('successModalText').innerText = text;
            document.getElementById('successModal').style.display = 'flex';
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }
        
        // Toggle Sidebar Logic
        function toggleSidebar() {
            document.getElementById('mobileSidebar').classList.toggle('show');
        }

        // --- MÁSCARAS E VALIDAÇÃO ---
        const phoneInputs = document.querySelectorAll('input[name="telefone"], input[name="contato_tel"]');
        const cpfCnpjInputs = document.querySelectorAll('input[name="cpf_cnpj"]');
        
        // Mask Phone: (XX) XXXXX-XXXX
        phoneInputs.forEach(input => {
            input.addEventListener('input', function (e) {
                let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            });
            
            input.addEventListener('blur', function(e) {
                const val = e.target.value.replace(/\D/g, '');
                if(val.length > 0 && val.length < 10) {
                    alert('⚠️ Número de telefone parece incompleto. Verifique se incluiu o DDD.');
                    e.target.style.borderColor = '#dc3545';
                } else {
                    e.target.style.borderColor = '';
                }
            });
        });

        // Mask CPF/CNPJ
        cpfCnpjInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '');
                if (v.length > 14) v = v.slice(0, 14); // Limit to CNPJ size

                if (v.length <= 11) { // CPF Mask
                    v = v.replace(/(\d{3})(\d)/, '$1.$2');
                    v = v.replace(/(\d{3})(\d)/, '$1.$2');
                    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    
                } else { // CNPJ Mask
                    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
                    v = v.replace(/(\d{4})(\d)/, '$1-$2');
                }
                e.target.value = v;
            });

            input.addEventListener('blur', function(e) {
                const val = e.target.value.replace(/\D/g, '');
                if(val.length > 0 && val.length !== 11 && val.length !== 14) {
                    alert('⚠️ CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos.');
                    e.target.style.borderColor = '#dc3545';
                } else {
                    e.target.style.borderColor = '';
                }
            });
        });
    </script>
