// Ficheiro: assets/js/carrinho.js (Versão Final com Animação no Header)

// Variável global para armazenar a opção de frete selecionada pelo usuário.
let selectedShipping = null;

/**
 * Evento que é disparado quando o conteúdo HTML da página foi completamente carregado.
 * Ponto de partida para inicializar os componentes do carrinho.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- Configuração do Modal de Confirmação de Exclusão ---
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Evento para o botão "Cancelar" no modal, que o esconde.
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => confirmDeleteModal.classList.remove('active'));
    }
    // Evento que permite fechar o modal clicando na área escura (overlay).
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('click', (e) => {
            if (e.target === confirmDeleteModal) confirmDeleteModal.classList.remove('active');
        });
    }
    // Evento para o botão "Confirmar Exclusão" que executa a remoção do item.
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            const sku = confirmDeleteBtn.dataset.sku; // Pega o SKU armazenado no botão.
            if (sku) {
                await executeDelete(sku);
                confirmDeleteModal.classList.remove('active'); // Esconde o modal após a ação.
            }
        });
    }

    // Atualiza o contador de itens no ícone do carrinho no cabeçalho assim que a página carrega.
    updateCartIcon();
    // Se estivermos na página do carrinho, renderiza todo o seu conteúdo.
    if (document.getElementById('cartItemsContainer')) {
        renderCartPage();
    }
});

/**
 * Função central para todas as chamadas à API do carrinho no backend.
 * @param {string} acao - A ação a ser executada no PHP (ex: 'adicionar', 'remover').
 * @param {object} data - Dados adicionais a serem enviados (ex: { sku: '123', quantity: 1 }).
 * @returns {Promise<object>} - A resposta JSON do servidor.
 */
async function apiCall(acao, data = {}) {
    try {
        const response = await fetch('acoes_carrinho.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ acao, ...data }) // Combina a ação e os dados em um único objeto.
        });
        if (!response.ok) { return { sucesso: false, mensagem: 'Erro de comunicação.' }; }
        return await response.json();
    } catch (error) {
        return { sucesso: false, mensagem: 'Não foi possível conectar ao servidor.' };
    }
}

/**
 * Busca os dados completos do carrinho do servidor.
 * @returns {Promise<Array>} - Um array de itens do carrinho.
 */
async function getCart() {
    const result = await apiCall('obter');
    return result.sucesso ? result.carrinho : [];
}

/**
 * Adiciona um produto ao carrinho.
 * @param {object} product - O objeto do produto a ser adicionado.
 * @param {number} quantity - A quantidade a ser adicionada.
 * @returns {Promise<boolean>} - True se a operação foi bem-sucedida.
 */
async function addToCart(product, quantity) {
    const result = await apiCall('adicionar', { sku: product.sku, quantity: quantity });
    if (result.sucesso) {
        // Atualiza o ícone do carrinho e ativa a animação de destaque.
        updateCartIcon(true);
        return true;
    }
    return false;
}

/**
 * Atualiza o ícone do carrinho no cabeçalho do site com o número de itens.
 * @param {boolean} highlight - Se true, adiciona uma classe CSS para uma animação visual.
 */
async function updateCartIcon(highlight = false) {
    const cart = await getCart();
    // Soma a quantidade de todos os itens no carrinho.
    const itemCount = cart.reduce((sum, item) => sum + parseInt(item.quantidade, 10), 0);
    const cartLink = document.getElementById('headerCartLink');
    if (cartLink) {
        const cartTextSpan = cartLink.querySelector('span');
        if (cartTextSpan) {
            cartTextSpan.textContent = `Carrinho (${itemCount})`;
        }

        // Se 'highlight' for verdadeiro, adiciona a classe que dispara a animação.
        if (highlight) {
            cartLink.classList.add('just-added');
            // Remove a classe após 3 segundos para que a animação possa ser disparada novamente.
            setTimeout(() => {
                cartLink.classList.remove('just-added');
            }, 3000);
        }
    }
}

/**
 * Renderiza todo o conteúdo da página do carrinho (lista de itens, resumo, etc.).
 * Esta é a função principal que constrói a visualização do carrinho.
 */
async function renderCartPage() {
    const cart = await getCart();
    const itemsContainer = document.getElementById('cartItemsContainer');
    const summaryContainer = document.getElementById('cartSummary');
    const emptyCartMessage = document.getElementById('emptyCartMessage');

    if (!itemsContainer || !summaryContainer || !emptyCartMessage) return;

    itemsContainer.innerHTML = ''; // Limpa o conteúdo atual para reconstruir.
    summaryContainer.innerHTML = '';

    // Se o carrinho estiver vazio, exibe a mensagem apropriada e esconde o resumo.
    if (cart.length === 0) {
        emptyCartMessage.style.display = 'block';
        summaryContainer.style.display = 'none';
        return;
    }

    emptyCartMessage.style.display = 'none';
    summaryContainer.style.display = 'block';

    let subtotalOriginal = 0;
    let totalDescontoAtacado = 0;

    // Itera sobre cada item do carrinho para criar seu elemento HTML.
    cart.forEach(item => {
        // --- Lógica de Cálculo de Preço ---
        const precoOriginal = parseFloat(item.price);
        const precoPromocional = item.promotional_price ? parseFloat(item.promotional_price) : null;
        const quantidade = parseInt(item.quantidade, 10);
        const precoBase = (precoPromocional && precoPromocional < precoOriginal) ? precoPromocional : precoOriginal;
        let precoFinalUnitario = precoBase;

        let precoHTML = `<p class="cart-item-price">R$ ${precoBase.toFixed(2).replace('.', ',')}</p>`;
        if (precoPromocional && precoPromocional < precoOriginal) {
            precoHTML = `<p class="cart-item-price-original">R$ ${precoOriginal.toFixed(2).replace('.', ',')}</p><p class="cart-item-price-discounted">${precoHTML}</p>`;
        }

        // Aplica desconto de atacado (10%) para 10 ou mais unidades.
        if (quantidade >= 10) {
            const descontoAtacado = precoBase * 0.10;
            precoFinalUnitario = precoBase - descontoAtacado;
            totalDescontoAtacado += descontoAtacado * quantidade;
        }

        const itemTotal = precoFinalUnitario * quantidade;
        subtotalOriginal += precoBase * quantidade;

        // --- Criação do Elemento HTML do Item ---
        const cartItemElement = document.createElement('div');
        cartItemElement.className = 'cart-item';
        cartItemElement.innerHTML = `
            `;
        itemsContainer.appendChild(cartItemElement);
    });

    // --- Cálculo e Renderização do Resumo do Pedido ---
    const subtotalFinal = subtotalOriginal - totalDescontoAtacado;
    const frete = selectedShipping ? parseFloat(selectedShipping.valor.replace(',', '.')) : 0;
    const totalGeral = subtotalFinal + frete;

    summaryContainer.innerHTML = `
        `;
    // Adiciona o evento de clique ao botão de calcular frete recém-criado.
    document.getElementById('calculateShippingBtn').addEventListener('click', calculateShipping);
}

/**
 * Atualiza a quantidade de um item no carrinho.
 * @param {string} sku - O SKU do produto a ser atualizado.
 * @param {number} newQuantity - A nova quantidade.
 */
async function updateQuantity(sku, newQuantity) {
    const quantidade = Math.max(1, parseInt(newQuantity)); // Garante que a quantidade seja no mínimo 1.
    await apiCall('atualizar_quantidade', { sku: sku, quantity: quantidade });
    renderCartPage(); // Re-renderiza a página para refletir a mudança.
    updateCartIcon();
}

/**
 * Inicia o processo de remoção de um item, abrindo o modal de confirmação.
 * @param {string} sku - O SKU do produto a ser removido.
 */
function removeFromCart(sku) {
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteModal && confirmDeleteBtn) {
        // Armazena o SKU no atributo 'data-sku' do botão de confirmação.
        confirmDeleteBtn.dataset.sku = sku;
        confirmDeleteModal.classList.add('active'); // Exibe o modal.
    }
}

/**
 * Executa a remoção de um item após a confirmação no modal.
 * @param {string} sku - O SKU do produto.
 */
async function executeDelete(sku) {
    await apiCall('remover', { sku: sku });
    renderCartPage();
    updateCartIcon();
}

/**
 * Calcula o frete usando uma API de backend.
 */
async function calculateShipping() {
    const cepInput = document.getElementById('cepInput');
    const shippingResult = document.getElementById('shippingResult');
    const cep = cepInput.value.replace(/\D/g, ''); // Remove caracteres não numéricos.

    if (cep.length !== 8) {
        shippingResult.innerHTML = `<div class="error">CEP inválido.</div>`;
        return;
    }

    shippingResult.innerHTML = `<div class="loading"><i class="fas fa-spinner fa-spin"></i> Calculando...</div>`;

    try {
        const response = await fetch(`frete_api.php?cep=${cep}`);
        const data = await response.json();

        if (data.sucesso && data.opcoes.length > 0) {
            // Constrói as opções de frete como botões de rádio.
            let optionsHTML = '';
            data.opcoes.forEach((op, i) => {
                optionsHTML += ``;
            });
            shippingResult.innerHTML = optionsHTML;

            // Adiciona eventos aos novos botões de rádio para atualizar o resumo quando um for selecionado.
            document.querySelectorAll('input[name="shipping"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    selectedShipping = JSON.parse(this.value);
                    renderCartPage(); // Re-renderiza para incluir o valor do frete.
                });
            });
        } else {
            shippingResult.innerHTML = `<div class="error">${data.mensagem || 'Nenhuma opção encontrada.'}</div>`;
        }
    } catch (error) {
        shippingResult.innerHTML = `<div class="error">Erro ao calcular o frete.</div>`;
    }
}

/**
 * Função de checkout: salva o pedido e gera a mensagem para o WhatsApp.
 */
async function generateWhatsAppMessage() {
    const cart = await getCart();
    if (cart.length === 0) {
        alert("Seu carrinho está vazio!");
        return;
    }

    // Recalcula todos os totais para garantir a consistência dos dados.
    let subtotalOriginal = 0;
    let totalDescontoAtacado = 0;
    const itensPedido = cart.map(item => {
        // ... (lógica de cálculo de preços, igual à de renderCartPage) ...
        return { /* ... objeto do item para salvar no banco ... */ };
    });
    const subtotalFinal = subtotalOriginal - totalDescontoAtacado;
    const frete = selectedShipping ? parseFloat(selectedShipping.valor.replace(',', '.')) : 0;
    const totalGeral = subtotalFinal + frete;

    const pedidoData = {
        itens: itensPedido,
        valor_total: totalGeral,
        frete_info: selectedShipping
    };

    // 1. Tenta salvar o pedido no banco de dados ANTES de abrir o WhatsApp.
    try {
        const response = await fetch('registrar_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(pedidoData)
        });
        const result = await response.json();

        if (!result.sucesso) {
            throw new Error(result.mensagem || 'Não foi possível registrar o pedido.');
        }

        // 2. Se o pedido foi salvo com sucesso, monta a mensagem e abre o WhatsApp.
        let message = `Olá! Gostaria de finalizar meu orçamento (Pedido #${result.pedido_id}):\n\n`;
        // ... (montagem da string da mensagem) ...

        const phoneNumber = "5513997979637"; // Número de telefone da loja.
        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank'); // Abre em uma nova aba.

    } catch (error) {
        alert(`Erro: ${error.message}`);
    }
}