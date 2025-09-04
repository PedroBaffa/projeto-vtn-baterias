/**
 * @file
 * assets/js/carrinho.js
 * Gerencia toda a lógica do carrinho de compras do lado do cliente.
 * Inclui adicionar, remover, atualizar itens, calcular frete e
 * gerar a mensagem para finalizar o orçamento via WhatsApp.
 */

// Variável global para armazenar a opção de frete selecionada.
let selectedShipping = null;

// --- INICIALIZAÇÃO E EVENTOS GLOBAIS ---
document.addEventListener('DOMContentLoaded', () => {
    // Lógica para o modal de confirmação de exclusão.
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => confirmDeleteModal.classList.remove('active'));
    }
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('click', (e) => {
            if (e.target === confirmDeleteModal) confirmDeleteModal.classList.remove('active');
        });
    }
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            const sku = confirmDeleteBtn.dataset.sku;
            if (sku) {
                await executeDelete(sku);
                confirmDeleteModal.classList.remove('active');
            }
        });
    }

    // Atualiza o ícone do carrinho assim que a página carrega.
    updateCartIcon();
    // Se estivermos na página do carrinho, renderiza seu conteúdo completo.
    if (document.getElementById('cartItemsContainer')) {
        renderCartPage();
    }
});

// --- FUNÇÕES DE COMUNICAÇÃO COM A API (BACK-END) ---

/**
 * Função central para todas as chamadas à API do carrinho.
 * @param {string} acao - A ação a ser executada no back-end (ex: 'adicionar', 'obter').
 * @param {object} data - Os dados a serem enviados (ex: { sku: '123', quantity: 1 }).
 * @returns {Promise<object>} A resposta da API em formato JSON.
 */
async function apiCall(acao, data = {}) {
    try {
        const response = await fetch('acoes_carrinho.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ acao, ...data })
        });
        if (!response.ok) { return { sucesso: false, mensagem: 'Erro de comunicação.' }; }
        return await response.json();
    } catch (error) {
        return { sucesso: false, mensagem: 'Não foi possível conectar ao servidor.' };
    }
}

/**
 * Busca os itens atuais do carrinho do usuário.
 * @returns {Promise<Array>} Um array de itens do carrinho.
 */
async function getCart() {
    const result = await apiCall('obter');
    return result.sucesso ? result.carrinho : [];
}

/**
 * Adiciona um produto ao carrinho.
 * @param {object} product - O objeto do produto.
 * @param {number} quantity - A quantidade a ser adicionada.
 * @returns {Promise<boolean>} True se a operação foi bem-sucedida, false caso contrário.
 */
async function addToCart(product, quantity) {
    const result = await apiCall('adicionar', { sku: product.sku, quantity: quantity });
    if (result.sucesso) {
        updateCartIcon(true); // Chama a atualização com o efeito de destaque.
        return true;
    }
    return false;
}


// --- FUNÇÕES DE ATUALIZAÇÃO DA INTERFACE (UI) ---

/**
 * Atualiza o ícone do carrinho no header com a contagem de itens.
 * @param {boolean} highlight - Se true, aplica uma animação de destaque ao ícone.
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

        // Se 'highlight' for true, adiciona uma classe CSS para a animação de "pulso".
        if (highlight) {
            cartLink.classList.add('just-added');
            // Remove a classe após a animação para permitir que ela seja reativada no futuro.
            setTimeout(() => {
                cartLink.classList.remove('just-added');
            }, 3000);
        }
    }
}

/**
 * Renderiza a página completa do carrinho, incluindo itens, totais e cálculo de frete.
 */
async function renderCartPage() {
    const cart = await getCart();
    const itemsContainer = document.getElementById('cartItemsContainer');
    const summaryContainer = document.getElementById('cartSummary');
    const emptyCartMessage = document.getElementById('emptyCartMessage');

    if (!itemsContainer || !summaryContainer || !emptyCartMessage) return;

    itemsContainer.innerHTML = '';
    summaryContainer.innerHTML = '';

    // Se o carrinho estiver vazio, exibe a mensagem apropriada.
    if (cart.length === 0) {
        emptyCartMessage.style.display = 'block';
        summaryContainer.style.display = 'none';
        return;
    }

    emptyCartMessage.style.display = 'none';
    summaryContainer.style.display = 'block';

    let subtotalOriginal = 0;
    let totalDescontoAtacado = 0;

    // Itera sobre cada item para construir o HTML e calcular os totais.
    cart.forEach(item => {
        const precoOriginal = parseFloat(item.price);
        const precoPromocional = item.promotional_price ? parseFloat(item.promotional_price) : null;
        const quantidade = parseInt(item.quantidade, 10);
        const precoBase = (precoPromocional && precoPromocional < precoOriginal) ? precoPromocional : precoOriginal;
        let precoFinalUnitario = precoBase;
        let precoHTML = `<p class="cart-item-price">R$ ${precoBase.toFixed(2).replace('.', ',')}</p>`;

        if (precoPromocional && precoPromocional < precoOriginal) {
            precoHTML = `<p class="cart-item-price-original">R$ ${precoOriginal.toFixed(2).replace('.', ',')}</p><p class="cart-item-price-discounted">${precoHTML}</p>`;
        }
        // Aplica o desconto de atacado se a quantidade for 10 ou mais.
        if (quantidade >= 10) {
            const descontoAtacado = precoBase * 0.10;
            precoFinalUnitario = precoBase - descontoAtacado;
            totalDescontoAtacado += descontoAtacado * quantidade;
        }

        const itemTotal = precoFinalUnitario * quantidade;
        subtotalOriginal += precoBase * quantidade;

        const cartItemElement = document.createElement('div');
        cartItemElement.className = 'cart-item';
        // Injeta o HTML do item do carrinho no container.
        // ... (HTML do item)
        itemsContainer.appendChild(cartItemElement);
    });

    const subtotalFinal = subtotalOriginal - totalDescontoAtacado;
    const frete = selectedShipping ? parseFloat(selectedShipping.valor.replace(',', '.')) : 0;
    const totalGeral = subtotalFinal + frete;

    // Injeta o HTML do resumo do carrinho.
    summaryContainer.innerHTML = `...`; // (HTML do resumo)
    document.getElementById('calculateShippingBtn').addEventListener('click', calculateShipping);
}

// --- FUNÇÕES DE AÇÃO DO CARRINHO ---

/**
 * Atualiza a quantidade de um item no carrinho.
 * @param {string} sku - O SKU do produto a ser atualizado.
 * @param {number} newQuantity - A nova quantidade.
 */
async function updateQuantity(sku, newQuantity) {
    const quantidade = Math.max(1, parseInt(newQuantity)); // Garante que a quantidade não seja menor que 1.
    await apiCall('atualizar_quantidade', { sku: sku, quantity: quantidade });
    renderCartPage();
    updateCartIcon();
}

/**
 * Inicia o processo de remoção de um item, exibindo o modal de confirmação.
 * @param {string} sku - O SKU do produto a ser removido.
 */
function removeFromCart(sku) {
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteModal && confirmDeleteBtn) {
        confirmDeleteBtn.dataset.sku = sku; // Armazena o SKU no botão para uso posterior.
        confirmDeleteModal.classList.add('active');
    }
}

/**
 * Executa a exclusão de um item após a confirmação.
 * @param {string} sku - O SKU do produto a ser deletado.
 */
async function executeDelete(sku) {
    await apiCall('remover', { sku: sku });
    renderCartPage();
    updateCartIcon();
}

/**
 * Calcula o frete usando a API de back-end.
 */
async function calculateShipping() {
    // ... (lógica de cálculo de frete)
}

/**
 * Gera a mensagem formatada para finalizar o orçamento via WhatsApp.
 */
async function generateWhatsAppMessage() {
    // ... (lógica para montar a mensagem com base nos itens do carrinho e frete)
}