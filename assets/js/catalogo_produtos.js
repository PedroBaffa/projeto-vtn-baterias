// Aguarda o carregamento completo do HTML da página antes de executar o script.
// Isso evita erros ao tentar manipular elementos que ainda não existem.
document.addEventListener("DOMContentLoaded", () => {

    // --- MAPEAMENTO DOS ELEMENTOS DO HTML (DOM) ---
    // Guardamos referências aos elementos principais da página em variáveis para acesso rápido.
    const productsGrid = document.getElementById("productsGrid"); // A grelha onde os produtos aparecem.
    const paginationContainer = document.getElementById("paginationContainer"); // Onde os botões de página ficam.
    const searchInput = document.getElementById("searchInput"); // O campo de busca por texto.
    const brandFilter = document.getElementById("brandFilter"); // O seletor de marcas.
    const sortFilter = document.getElementById("sortFilter"); // O seletor de ordenação.
    const limitFilter = document.getElementById("limitFilter"); // O seletor de quantos produtos por página.
    const applyFiltersBtn = document.getElementById("applyFiltersBtn"); // O botão para aplicar os filtros.
    const productCountSpan = document.getElementById("productCount"); // Onde o total de produtos é exibido.
    const toggleFiltersBtn = document.getElementById("toggleFiltersBtn"); // O botão para mostrar/esconder filtros no telemóvel.
    const advancedFilters = document.getElementById("advancedFilters"); // O contentor dos filtros avançados.

    // --- ESTADO DA PÁGINA ---
    // Variável que guarda a página atual em que o utilizador está. Começa na página 1.
    let currentPage = 1;

    /**
     * Função principal que busca os produtos na API e os renderiza na página.
     * @param {number} page - O número da página a ser carregada. O padrão é 1.
     */
    async function renderProducts(page = 1) {
        currentPage = page; // Atualiza a página atual.

        // Recolhe os valores atuais de todos os filtros.
        const filterBrand = brandFilter.value;
        const sortOrder = sortFilter.value;
        const searchTerm = searchInput.value;
        const productsPerPage = limitFilter.value;

        // Mostra um estado de "a carregar" para o utilizador enquanto os dados não chegam.
        productsGrid.innerHTML = '<div class="loading" id="loadingState"><i class="fas fa-spinner fa-spin"></i><h3>A carregar produtos...</h3></div>';
        if (paginationContainer) paginationContainer.innerHTML = '';
        if (productCountSpan) productCountSpan.textContent = 'Carregando...';

        try {
            // Constrói a URL da API com base nos filtros selecionados.
            const timestamp = new Date().getTime(); // Adiciona um timestamp para evitar cache.
            let apiUrl = `api.php?limit=${productsPerPage}&page=${currentPage}&sort=${sortOrder}&t=${timestamp}`;
            if (filterBrand && filterBrand !== 'all') apiUrl += `&brand=${encodeURIComponent(filterBrand)}`;
            if (searchTerm.trim()) apiUrl += `&search=${encodeURIComponent(searchTerm)}`;

            // Faz a chamada à API para buscar os dados dos produtos.
            const response = await fetch(apiUrl);
            const data = await response.json();

            // Limpa a grelha para inserir os novos produtos.
            productsGrid.innerHTML = "";

            // Se a API não retornar produtos, exibe uma mensagem de "nenhum produto encontrado".
            if (data.products.length === 0) {
                productsGrid.innerHTML = `<div class="no-results"><i class="fas fa-search"></i><h3>Nenhum produto encontrado</h3><p>Tente ajustar a sua busca ou filtro.</p></div>`;
                productCountSpan.textContent = "0 produtos encontrados";
                return; // Encerra a função aqui.
            }

            // Atualiza a contagem de produtos.
            productCountSpan.textContent = `${data.total} produtos encontrados`;

            // Itera sobre cada produto recebido da API para criar o seu "card" visual.
            data.products.forEach(product => {
                const productCard = document.createElement("div"); // O contentor principal do card.
                const isInStock = product.in_stock == '1';
                productCard.className = `product-card ${!isInStock ? 'out-of-stock' : ''}`;

                // Formatação de preços e emblemas (badges).
                const price = parseFloat(product.price);
                const promoPrice = product.promotional_price ? parseFloat(product.promotional_price) : null;
                let priceHTML = '', saleBadge = '';

                // Verifica se há um preço promocional válido e cria o HTML correspondente.
                if (promoPrice && promoPrice < price) {
                    priceHTML = `<div class="product-price on-sale"><span class="old-price">R$ ${price.toFixed(2).replace(".", ",")}</span><span class="promo-price">R$ ${promoPrice.toFixed(2).replace(".", ",")}</span></div>`;
                    saleBadge = '<span class="sale-badge">OFERTA</span>';
                } else {
                    priceHTML = `<div class="product-price">R$ ${price.toFixed(2).replace(".", ",")}</div>`;
                }

                // Cria o emblema de condição ("Novo" ou "Retirado").
                let condicaoBadge = (product.condicao === 'retirado') ? '<span class="condicao-badge retirado">Retirado</span>' : '<span class="condicao-badge novo">Novo</span>';

                // Prepara a mensagem para o botão do WhatsApp.
                const whatsappMessage = encodeURIComponent(`Olá! Tenho interesse no produto: ${product.title} (SKU: ${product.sku})`);
                const whatsappUrl = `https://wa.me/5513997979637?text=${whatsappMessage}`;

                // Define os botões de ação ("Detalhes" e WhatsApp) ou a mensagem "Sem Estoque".
                let actionsHTML = isInStock
                    ? `<a href="produto_detalhes.html?sku=${product.sku}" class="details-btn">Detalhes</a>
                       <a href="${whatsappUrl}" class="whatsapp-btn" target="_blank" title="Consultar no WhatsApp"><i class="fab fa-whatsapp"></i></a>`
                    : `<div class="out-of-stock-label">Sem Estoque</div>`;

                // Monta a estrutura HTML completa do card do produto.
                productCard.innerHTML = `
                    <a href="produto_detalhes.html?sku=${product.sku}" class="product-link-wrapper">
                        <div class="product-image">
                            ${saleBadge}
                            <img src="${product.image || 'assets/img/placeholder.svg'}" alt="${product.title}" onerror="this.src='assets/img/placeholder.svg';">
                        </div>
                        <div class="product-info">
                            <div class="product-brand-wrapper">
                                <div class="product-brand">${product.brand.toUpperCase()}</div>
                                ${condicaoBadge}
                            </div>
                            <h3 class="product-title">${product.title}</h3>
                            <div class="product-sku">SKU: ${product.sku}</div>
                            ${priceHTML}
                        </div>
                    </a>
                    <div class="product-actions-wrapper">
                        <div class="product-actions">${actionsHTML}</div>
                    </div>
                `;

                // Adiciona o card recém-criado à grelha de produtos na página.
                productsGrid.appendChild(productCard);
            });

            // Chama a função para criar os botões de paginação.
            setupPagination(data.total, parseInt(productsPerPage), currentPage);

        } catch (error) {
            // Em caso de erro na chamada da API, exibe uma mensagem de erro na grelha.
            console.error('Falha ao procurar produtos:', error);
            productsGrid.innerHTML = `<div class="no-results"><h3>Ocorreu um erro ao carregar os produtos.</h3></div>`;
            productCountSpan.textContent = "Erro ao carregar";
        }
    }

    /**
     * Cria e gere os botões de navegação entre páginas.
     * @param {number} totalProducts - O número total de produtos encontrados.
     * @param {number} limit - O número de produtos por página.
     * @param {number} currentPage - A página atual.
     */
    function setupPagination(totalProducts, limit, currentPage) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = "";
        const totalPages = Math.ceil(totalProducts / limit);

        // Se houver apenas uma página (ou menos), não mostra a paginação.
        if (totalPages <= 1) return;

        // Função auxiliar para criar cada botão.
        const createPageButton = (page, text, isDisabled = false) => {
            const button = document.createElement('button');
            button.innerHTML = text;
            button.className = 'page-btn';
            button.disabled = isDisabled;
            if (page === currentPage) button.classList.add('active'); // Destaca a página atual.
            button.addEventListener('click', () => {
                renderProducts(page); // Ao clicar, carrega a página correspondente.
                window.scrollTo({ top: 0, behavior: 'smooth' }); // Rola a janela para o topo.
            });
            return button;
        };

        // Cria os botões "Anterior", os números das páginas e o "Próximo".
        paginationContainer.appendChild(createPageButton(currentPage - 1, '&laquo;', currentPage === 1));
        for (let i = 1; i <= totalPages; i++) {
            paginationContainer.appendChild(createPageButton(i, i));
        }
        paginationContainer.appendChild(createPageButton(currentPage + 1, '&raquo;', currentPage === totalPages));
    }

    // --- EVENT LISTENERS (OUVINTES DE EVENTOS) ---

    // Define a ação do botão "Aplicar Filtros".
    applyFiltersBtn.addEventListener("click", () => {
        renderProducts(1); // Volta para a primeira página com os novos filtros.
        // Se estiver no telemóvel, esconde a barra de filtros após aplicar.
        if (window.innerWidth <= 768) {
            advancedFilters.classList.remove('visible');
            toggleFiltersBtn.classList.remove('active');
        }
    });

    // Permite que a busca seja feita ao pressionar "Enter" no campo de busca.
    searchInput.addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Impede o comportamento padrão do formulário.
            renderProducts(1);
        }
    });

    // Controla o botão que mostra/esconde os filtros no telemóvel.
    toggleFiltersBtn.addEventListener('click', () => {
        advancedFilters.classList.toggle('visible');
        toggleFiltersBtn.classList.toggle('active');
    });

    // --- CHAMADA INICIAL ---
    // Carrega os produtos da primeira página assim que o script é executado.
    renderProducts(1);
});