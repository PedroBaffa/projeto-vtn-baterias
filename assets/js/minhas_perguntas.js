document.addEventListener("DOMContentLoaded", function () {
    const listaPerguntasContainer = document.getElementById('lista-perguntas');

    async function carregarPerguntas() {
        try {
            const response = await fetch('api.php?minhas_perguntas=true');
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            listaPerguntasContainer.innerHTML = ''; // Limpa o estado de "carregando"

            if (data.perguntas.length === 0) {
                listaPerguntasContainer.innerHTML = '<div class="nenhuma-pergunta"><i class="fas fa-comments"></i><p>Você ainda não fez nenhuma pergunta.</p></div>';
                return;
            }

            data.perguntas.forEach(p => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'pergunta-item';

                const dataPergunta = new Date(p.data_pergunta).toLocaleDateString('pt-BR');
                let respostaHtml = '<div class="resposta-body"><p class="italic text-gray-500">Aguardando resposta da nossa equipe.</p></div>';

                if (p.resposta) {
                    const classesResposta = p.resposta_lida == '0' ? 'resposta-body nao-lida' : 'resposta-body';
                    respostaHtml = `<div class="${classesResposta}" data-pergunta-id="${p.id}"><p>${p.resposta}</p></div>`;
                }

                itemDiv.innerHTML = `
                    <div class="pergunta-header">
                        <div class="pergunta-produto">
                            Referente a: <a href="produto_detalhes.html?sku=${p.produto_sku}">${p.produto_titulo}</a>
                        </div>
                        <div class="pergunta-data">${dataPergunta}</div>
                    </div>
                    <div class="pergunta-body">
                        <p><strong>Sua pergunta:</strong> ${p.pergunta}</p>
                    </div>
                    ${respostaHtml}
                `;
                listaPerguntasContainer.appendChild(itemDiv);
            });

            // Marca as respostas como lidas
            marcarRespostasComoLidas();

        } catch (error) {
            listaPerguntasContainer.innerHTML = `<div class="nenhuma-pergunta"><p class="text-red-500">Erro ao carregar suas perguntas. Tente novamente mais tarde.</p></div>`;
            console.error(error);
        }
    }

    async function marcarRespostasComoLidas() {
        const respostasNaoLidas = document.querySelectorAll('.nao-lida');
        for (const resposta of respostasNaoLidas) {
            const id = resposta.dataset.perguntaId;
            await fetch('acoes_faq_cliente.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'marcar_como_lida', pergunta_id: id })
            });
        }
    }

    carregarPerguntas();
});