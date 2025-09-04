/**
 * @file
 * assets/js/cadastro.js
 * Controla a interatividade da página de cadastro de novos usuários.
 * Inclui máscaras de input e a lógica de submissão do formulário via AJAX.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registrationForm');
    const messageDiv = document.getElementById('formMessage');

    // --- MÁSCARAS DE INPUT ---
    // Aplica máscaras de input para os campos de CPF e Telefone usando a biblioteca IMask.
    // Isso melhora a experiência do usuário, formatando os dados enquanto ele digita.
    const cpfMask = IMask(document.getElementById('cpf'), { mask: '000.000.000-00' });
    const telMask = IMask(document.getElementById('telefone'), { mask: '(00) 00000-0000' });

    // --- SUBMISSÃO DO FORMULÁRIO ---
    form.addEventListener('submit', async function (e) {
        // Previne o comportamento padrão do formulário (que seria recarregar a página).
        e.preventDefault();

        const formData = new FormData(form);
        // Cria um objeto com os dados do formulário.
        // É importante usar os valores "unmasked" (sem a máscara) para enviar ao back-end,
        // garantindo que apenas os números sejam salvos no banco de dados.
        const data = {
            nome: formData.get('nome'),
            sobrenome: formData.get('sobrenome'),
            cpf: cpfMask.unmaskedValue,
            email: formData.get('email'),
            telefone: telMask.unmaskedValue,
            endereco: formData.get('endereco'),
            senha: formData.get('senha'),
        };

        // Exibe uma mensagem de feedback para o usuário.
        messageDiv.textContent = 'Enviando dados...';
        messageDiv.className = 'form-message'; // Reseta as classes de cor.

        try {
            // Envia os dados do formulário para o script PHP de back-end de forma assíncrona.
            const response = await fetch('acoes_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            // Verifica se a resposta do servidor foi bem-sucedida.
            if (response.ok) {
                messageDiv.textContent = result.mensagem;
                messageDiv.classList.add('success'); // Adiciona a classe de estilo para sucesso.
                form.reset(); // Limpa o formulário.
                // Redireciona o usuário para a página inicial após um breve intervalo.
                setTimeout(() => { window.location.href = 'index.html'; }, 1500);
            } else {
                // Se o servidor retornar um erro, lança uma exceção com a mensagem.
                throw new Error(result.mensagem || 'Ocorreu um erro no cadastro.');
            }

        } catch (error) {
            // Captura qualquer erro (de rede ou do servidor) e exibe para o usuário.
            messageDiv.textContent = error.message;
            messageDiv.classList.add('error'); // Adiciona a classe de estilo para erro.
        }
    });
});