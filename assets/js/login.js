// Arquivo: assets/js/login.js (Versão Corrigida)
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const messageDiv = document.getElementById('formMessage');

    // *** NOVA LÓGICA: Captura o parâmetro de redirecionamento da URL ***
    const urlParams = new URLSearchParams(window.location.search);
    const redirectUrl = urlParams.get('redirect');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // *** Adiciona a URL de redirecionamento aos dados enviados ***
        if (redirectUrl) {
            data.redirect = redirectUrl;
        }

        messageDiv.textContent = 'Verificando...';
        messageDiv.className = 'form-message';

        try {
            const response = await fetch('auth_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data) // 'data' agora contém a URL de redirect
            });

            const result = await response.json();

            if (response.ok) {
                messageDiv.textContent = result.mensagem;
                messageDiv.classList.add('success');
                // Pega a URL de volta do PHP, ou usa 'index.html' como padrão
                const finalRedirectUrl = result.redirect || 'index.html';
                setTimeout(() => { window.location.href = finalRedirectUrl; }, 1500);
            } else {
                throw new Error(result.mensagem);
            }
        } catch (error) {
            messageDiv.textContent = error.message;
            messageDiv.classList.add('error');
        }
    });
});