document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registrationForm');
    const messageDiv = document.getElementById('formMessage');
    const cepInput = document.getElementById('cep');
    const enderecoInput = document.getElementById('endereco');
    const numeroInput = document.getElementById('numero');

    // Aplica as máscaras
    const cpfMask = IMask(document.getElementById('cpf'), { mask: '000.000.000-00' });
    const telMask = IMask(document.getElementById('telefone'), { mask: '(00) 00000-0000' });
    const cepMask = IMask(cepInput, { mask: '00000-000' });

    // Função para buscar o endereço pelo CEP
    async function fetchAddress(cep) {
        cepInput.classList.add('loading');
        enderecoInput.value = 'Buscando...';
        try {
            const response = await fetch(`cep_api.php?cep=${cep}`);
            const data = await response.json();

            if (data.erro) {
                throw new Error(data.mensagem || 'CEP não encontrado.');
            }

            // Preenche os campos com os dados retornados
            let addressString = '';
            if (data.logradouro) addressString += data.logradouro;
            if (data.bairro) addressString += `, ${data.bairro}`;
            if (data.localidade) addressString += `, ${data.localidade}`;
            if (data.uf) addressString += ` - ${data.uf}`;

            enderecoInput.value = addressString;
            numeroInput.focus(); // Move o foco para o campo de número

        } catch (error) {
            enderecoInput.value = '';
            messageDiv.textContent = error.message;
            messageDiv.className = 'form-message error';
        } finally {
            cepInput.classList.remove('loading');
        }
    }

    // Adiciona o "ouvinte" ao campo CEP
    cepInput.addEventListener('blur', function () {
        const cep = cepMask.unmaskedValue;
        if (cep.length === 8) {
            fetchAddress(cep);
        }
    });

    // Lida com o envio do formulário
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Validação de CPF
        if (cpfMask.unmaskedValue.length !== 11) {
            messageDiv.textContent = 'CPF inválido. Por favor, preencha corretamente.';
            messageDiv.className = 'form-message error';
            return;
        }

        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            sobrenome: formData.get('sobrenome'),
            cpf: cpfMask.unmaskedValue,
            email: formData.get('email'),
            telefone: telMask.unmaskedValue,
            cep: cepMask.unmaskedValue,
            endereco: formData.get('endereco'),
            numero: formData.get('numero'),
            complemento: formData.get('complemento'),
            senha: formData.get('senha'),
        };

        messageDiv.textContent = 'Enviando dados...';
        messageDiv.className = 'form-message';

        try {
            const response = await fetch('acoes_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (response.ok) {
                messageDiv.textContent = result.mensagem;
                messageDiv.className = 'form-message success';
                form.reset();
                setTimeout(() => { window.location.href = result.redirect || 'index.html'; }, 1500);
            } else {
                throw new Error(result.mensagem);
            }
        } catch (error) {
            messageDiv.textContent = error.message;
            messageDiv.className = 'form-message error';
        }
    });
});