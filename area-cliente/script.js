document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const btn = document.querySelector('.btn-login');
    const alert = document.getElementById('loginAlert');
    
    // Simulação de carregamento
    btn.classList.add('loading');
    btn.textContent = 'Verificando...';
    alert.style.display = 'none';

    setTimeout(() => {
        // Lógica FAKE de validação apenas para demonstração
        if (email === 'admin' && password === 'admin') {
            // Sucesso (Futuramente redirecionar para dashboard)
            alert.className = 'alert'; // remove erro
            alert.style.background = '#dcfce7'; // verde
            alert.style.color = '#166534';
            alert.style.display = 'block';
            alert.textContent = 'Login realizado com sucesso! Redirecionando...';
        } else {
            // Erro
            btn.classList.remove('loading');
            btn.textContent = 'Entrar no Portal';
            
            alert.className = 'alert alert-error';
            alert.style.display = 'block';
            alert.textContent = 'Usuário ou senha incorretos.';
        }
    }, 1500);
});
