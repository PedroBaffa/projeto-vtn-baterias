// Ficheiro: assets/js/carrinho.js (Com endereço na mensagem do WhatsApp)

let selectedShipping = null;

document.addEventListener('DOMContentLoaded', () => {
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

    updateCartIcon();
    if (document.getElementById('cartItemsContainer')) {
        renderCartPage();
    }
});

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

async function getCartData() {
    const result = await apiCall('obter');
    return result.sucesso ? result : { carrinho: [], usuario: null };
}

async function addToCart(product, quantity) {
    const result = await apiCall('adicionar', { sku: product.sku, quantity: quantity });
    if (result.sucesso) {
        updateCartIcon(true);
        return true;
    }
    return false;
}

async function updateCartIcon(highlight = false) {
    const result = await apiCall('obter');
    const cart = result.sucesso ? result.carrinho : [];
    const itemCount = cart.reduce((sum, item) => sum + parseInt(item.quantidade, 10), 0);
    const cartLink = document.getElementById('headerCartLink');
    if (cartLink) {
        const cartTextSpan = cartLink.querySelector('span');
        if (cartTextSpan) {
            cartTextSpan.textContent = `Carrinho (${itemCount})`;
        }
        if (highlight) {
            cartLink.classList.add('just-added');
            setTimeout(() => {
                cartLink.classList.remove('just-added');
            }, 3000);
        }
    }
}

async function renderCartPage() {
    const cartData = await getCartData();
    const cart = cartData.carrinho;
    const itemsContainer = document.getElementById('cartItemsContainer');
    const summaryContainer = document.getElementById('cartSummary');
    const emptyCartMessage = document.getElementById('emptyCartMessage');

    if (!itemsContainer || !summaryContainer || !emptyCartMessage) return;

    itemsContainer.innerHTML = '';
    summaryContainer.innerHTML = '';

    if (cart.length === 0) {
        emptyCartMessage.style.display = 'block';
        summaryContainer.style.display = 'none';
        return;
    }

    emptyCartMessage.style.display = 'none';
    summaryContainer.style.display = 'block';

    let subtotalOriginal = 0;
    let totalDescontoAtacado = 0;

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
        if (quantidade >= 10) {
            const descontoAtacado = precoBase * 0.10;
            precoFinalUnitario = precoBase - descontoAtacado;
            totalDescontoAtacado += descontoAtacado * quantidade;
        }
        const itemTotal = precoFinalUnitario * quantidade;
        subtotalOriginal += precoBase * quantidade;
        const cartItemElement = document.createElement('div');
        cartItemElement.className = 'cart-item';
        cartItemElement.innerHTML = `
            <div class="cart-item-image"><img src="${item.image || 'assets/img/placeholder.svg'}" alt="${item.title}"></div>
            <div class="cart-item-details">
                <p class="cart-item-title">${item.title}</p>
                <p class="cart-item-sku">SKU: ${item.sku}</p>
                ${precoHTML}
            </div>
            <div class="cart-item-quantity">
                <button class="qty-btn" onclick="updateQuantity('${item.sku}', ${quantidade - 1})">-</button>
                <input type="number" value="${quantidade}" min="1" onchange="updateQuantity('${item.sku}', this.value)">
                <button class="qty-btn" onclick="updateQuantity('${item.sku}', ${quantidade + 1})">+</button>
            </div>
            <div class="cart-item-total"><p>R$ ${itemTotal.toFixed(2).replace('.', ',')}</p></div>
            <div class="cart-item-remove"><button class="remove-btn" onclick="removeFromCart('${item.sku}')"><i class="fas fa-trash-alt"></i></button></div>
        `;
        itemsContainer.appendChild(cartItemElement);
    });

    const subtotalFinal = subtotalOriginal - totalDescontoAtacado;
    const frete = selectedShipping ? parseFloat(selectedShipping.valor.replace(',', '.')) : 0;
    const totalGeral = subtotalFinal + frete;

    summaryContainer.innerHTML = `
        <div id="dynamicSummary">
            <div class="cart-subtotal"><span>Subtotal:</span> <span>R$ ${subtotalOriginal.toFixed(2).replace('.', ',')}</span></div>
            ${totalDescontoAtacado > 0 ? `<div class="cart-discount"><span>Desconto Atacado (10%):</span> <strong class="discount-value">- R$ ${totalDescontoAtacado.toFixed(2).replace('.', ',')}</strong></div>` : ''}
            ${selectedShipping ? `<div class="cart-subtotal"><span>Frete (${selectedShipping.tipo}):</span> <span>R$ ${selectedShipping.valor}</span></div>` : ''}
            <div class="cart-total"><span>Total do Orçamento:</span> <strong>R$ ${totalGeral.toFixed(2).replace('.', ',')}</strong></div>
            <button class="whatsapp-checkout-btn" onclick="generateWhatsAppMessage()">
                <i class="fab fa-whatsapp"></i> Finalizar Orçamento via WhatsApp
            </button>
        </div>
        <div class="cart-shipping">
            <h3><i class="fas fa-truck"></i> Calcular Frete e Prazo</h3>
            <div class="shipping-input-group">
                <input type="text" id="cepInput" placeholder="Digite seu CEP">
                <button id="calculateShippingBtn">Calcular</button>
            </div>
            <div id="shippingResult"></div>
        </div>
    `;
    document.getElementById('calculateShippingBtn').addEventListener('click', calculateShipping);
}

async function updateQuantity(sku, newQuantity) {
    const quantidade = Math.max(1, parseInt(newQuantity));
    await apiCall('atualizar_quantidade', { sku: sku, quantity: quantidade });
    renderCartPage();
    updateCartIcon();
}

function removeFromCart(sku) {
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteModal && confirmDeleteBtn) {
        confirmDeleteBtn.dataset.sku = sku;
        confirmDeleteModal.classList.add('active');
    }
}

async function executeDelete(sku) {
    await apiCall('remover', { sku: sku });
    renderCartPage();
    updateCartIcon();
}

async function calculateShipping() {
    const cepInput = document.getElementById('cepInput');
    const shippingResult = document.getElementById('shippingResult');
    const cep = cepInput.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        shippingResult.innerHTML = `<div class="error">CEP inválido.</div>`;
        return;
    }

    shippingResult.innerHTML = `<div class="loading"><i class="fas fa-spinner fa-spin"></i> Calculando...</div>`;

    try {
        const response = await fetch(`frete_api.php?cep=${cep}`);
        const data = await response.json();

        if (data.sucesso && data.opcoes.length > 0) {
            let optionsHTML = '';
            data.opcoes.forEach((op, i) => {
                optionsHTML += `<div class="shipping-option"><input type="radio" name="shipping" id="shipping${i}" value='${JSON.stringify(op)}'><label for="shipping${i}"><span>${op.tipo} (${op.prazo} dias)</span><span class="price">R$ ${op.valor}</span></label></div>`;
            });
            shippingResult.innerHTML = optionsHTML;
            document.querySelectorAll('input[name="shipping"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    selectedShipping = JSON.parse(this.value);
                    renderCartPage();
                });
            });
        } else {
            shippingResult.innerHTML = `<div class="error">${data.mensagem || 'Nenhuma opção encontrada.'}</div>`;
        }
    } catch (error) {
        shippingResult.innerHTML = `<div class="error">Erro ao calcular o frete.</div>`;
    }
}

async function generateWhatsAppMessage() {
    const cartData = await getCartData();
    const cart = cartData.carrinho;
    const usuario = cartData.usuario;

    if (cart.length === 0) {
        alert("Seu carrinho está vazio!");
        return;
    }

    let subtotalOriginal = 0;
    let totalDescontoAtacado = 0;
    const itensPedido = cart.map(item => {
        const precoOriginal = parseFloat(item.price);
        const precoPromocional = item.promotional_price ? parseFloat(item.promotional_price) : null;
        const quantidade = parseInt(item.quantidade, 10);
        const precoBase = (precoPromocional && precoPromocional < precoOriginal) ? precoPromocional : precoOriginal;
        let precoFinalUnitario = precoBase;

        if (quantidade >= 10) {
            const descontoAtacado = precoBase * 0.10;
            precoFinalUnitario = precoBase - descontoAtacado;
            totalDescontoAtacado += descontoAtacado * quantidade;
        }
        subtotalOriginal += precoBase * quantidade;

        return { sku: item.sku, title: item.title, quantity: quantidade, unit_price: precoFinalUnitario, promotional_price: (precoPromocional && precoPromocional < precoOriginal) ? precoPromocional : null };
    });

    const subtotalFinal = subtotalOriginal - totalDescontoAtacado;
    const frete = selectedShipping ? parseFloat(selectedShipping.valor.replace(',', '.')) : 0;
    const totalGeral = subtotalFinal + frete;

    const pedidoData = {
        itens: itensPedido,
        valor_total: totalGeral,
        frete_info: selectedShipping
    };

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

        let message = `Olá! Gostaria de finalizar meu orçamento (Pedido #${result.pedido_id}):\n\n`;

        cart.forEach(item => {
            message += `*Produto:* ${item.title}\n*SKU:* ${item.sku}\n*Quantidade:* ${item.quantidade}\n-------------------------\n`;
        });

        message += `\n*Total dos Produtos (com descontos): R$ ${subtotalFinal.toFixed(2).replace('.', ',')}*`;
        if (selectedShipping) {
            message += `\n*Frete (${selectedShipping.tipo}): R$ ${selectedShipping.valor}*`;
            message += `\n*Total Geral: R$ ${totalGeral.toFixed(2).replace('.', ',')}*`;
        }

        if (usuario && usuario.endereco) {
            message += `\n\n*Endereço para Entrega:*\n${usuario.endereco}`;
        }

        const phoneNumber = "5513997979637";
        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');

    } catch (error) {
        alert(`Erro: ${error.message}`);
    }
}