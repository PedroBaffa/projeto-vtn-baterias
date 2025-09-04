/**
 * @file
 * assets/js/login.js
 * Controla a interatividade da página de login do usuário.
 * Lida com a submissão do formulário, chamada da API de autenticação
 * e redirecionamento do usuário após o login.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const messageDiv = document.getElementById('formMessage');

    // --- LÓGICA DE REDIRECIONAMENTO ---
    // Captura o parâmetro 'redirect' da URL.
    // Ex: Se a URL for "login.html?redirect=carrinho.php", redirectUrl será "carrinho.php".
    // Isso é crucial para enviar o usuário de volta para a página que ele tentou acessar antes do login.
    const urlParams = new URLSearchParams(window.location.search);
    const redirectUrl = urlParams.get('redirect');

    // --- SUBMISSÃO DO FORMULÁRIO ---
    form.addEventListener('submit', async function (e) {
        // Previne o comportamento padrão do formulário, que é recarregar a página.
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Adiciona a URL de redirecionamento aos dados que serão enviados ao back-end.
        // O back-end então retornará esta mesma URL para que o JavaScript saiba para onde ir.
        if (redirectUrl) {
            data.redirect = redirectUrl;
        }

        // Exibe um feedback visual para o usuário.
        messageDiv.textContent = 'Verificando...';
        messageDiv.className = 'form-message';

        try {
            // Envia os dados de login (email, senha e URL de redirect) para a API de autenticação.
            const response = await fetch('auth_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            // Se a resposta do servidor for bem-sucedida (status 200-299).
            if (response.ok) {
                messageDiv.textContent = result.mensagem;
                messageDiv.classList.add('success');

                // Determina a URL final para redirecionamento:
                // 1. Usa a URL retornada pelo PHP (que pegou do nosso 'redirectUrl').
                // 2. Se nenhuma for retornada, usa 'index.html' como padrão.
                const finalRedirectUrl = result.redirect || 'index.html';

                // Aguarda 1.5 segundos e então redireciona o usuário.
                setTimeout(() => { window.location.href = finalRedirectUrl; }, 1500);
            } else {
                // Se o servidor retornar um erro (ex: 401 - Não Autorizado), lança uma exceção.
                throw new Error(result.mensagem);
            }
        } catch (error) {
            // Captura qualquer erro (de rede ou do servidor) e o exibe para o usuário.
            messageDiv.textContent = error.message;
            messageDiv.classList.add('error');
        }
    });
});