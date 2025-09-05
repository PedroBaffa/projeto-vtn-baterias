/**
 * @file
 * assets/js/catalogo_produtos.js
 * Controla toda a lógica da página de catálogo de produtos.
 * Lida com a busca de produtos na API, aplicação de filtros,
 * renderização da grade de produtos e criação da paginação.
 */

document.addEventListener("DOMContentLoaded", () => {
    // --- ELEMENTOS DO DOM ---
    // Seleciona todos os elementos da página que serão manipulados pelo script.
    const productsGrid = document.getElementById("productsGrid");
    const paginationContainer = document.getElementById("paginationContainer");
    const searchInput = document.getElementById("searchInput");
    const brandFilter = document.getElementById("brandFilter");
    const sortFilter = document.getElementById("sortFilter");
    const limitFilter = document.getElementById("limitFilter");
    const applyFiltersBtn = document.getElementById("applyFiltersBtn");
    const productCountSpan = document.getElementById("productCount");
    const filterSidebar = document.getElementById("filterSidebar");
    const openFiltersBtn = document.getElementById("openFiltersBtn");
    const closeFiltersBtn = document.getElementById("closeFiltersBtn");

    // Variável para manter o controle da página atual.
    let currentPage = 1;

    // --- FUNÇÃO PRINCIPAL DE RENDERIZAÇÃO ---

    /**
     * Busca e renderiza os produtos na página com base nos filtros atuais.
     * @param {number} page - O número da página a ser exibida.
     */
    async function renderProducts(page = 1) {
        currentPage = page;
        // Captura os valores atuais de todos os campos de filtro.
        const filterBrand = brandFilter.value;
        const sortOrder = sortFilter.value;
        const searchTerm = searchInput.value;
        const productsPerPage = limitFilter.value;

        // Exibe um estado de "carregando" para o usuário enquanto os dados são buscados.
        productsGrid.innerHTML = '<div class="loading" id="loadingState"><i class="fas fa-spinner fa-spin"></i><h3>A carregar produtos...</h3></div>';
        if (paginationContainer) paginationContainer.innerHTML = '';
        if (productCountSpan) productCountSpan.textContent = 'Carregando...';

        try {
            // Adiciona um timestamp para evitar cache da API em algumas configurações de servidor.
            const timestamp = new Date().getTime();
            // Constrói a URL da API dinamicamente com os parâmetros de filtro e paginação.
            let apiUrl = `api.php?limit=${productsPerPage}&page=${currentPage}&sort=${sortOrder}&t=${timestamp}`;

            // Adiciona o filtro de marca à URL, se um for selecionado.
            if (filterBrand && filterBrand !== 'all') {
                apiUrl += `&brand=${encodeURIComponent(filterBrand)}`;
            }
            // Adiciona o termo de busca à URL, se houver algum.
            if (searchTerm.trim()) {
                apiUrl += `&search=${encodeURIComponent(searchTerm)}`;
            }

            // Faz a chamada assíncrona (fetch) para a API.
            const response = await fetch(apiUrl);
            const data = await response.json();

            productsGrid.innerHTML = ""; // Limpa a grade antes de adicionar os novos produtos.

            // Se a API não retornar produtos, exibe uma mensagem amigável.
            if (data.products.length === 0) {
                productsGrid.innerHTML = `<div class="no-results"><i class="fas fa-search"></i><h3>Nenhum produto encontrado</h3><p>Tente ajustar a sua busca ou filtro.</p></div>`;
                productCountSpan.textContent = "0 produtos encontrados";
                return;
            }

            // Atualiza a contagem total de produtos encontrados.
            productCountSpan.textContent = `${data.total} produtos encontrados`;

            // Itera sobre cada produto retornado e cria o card HTML correspondente.
            data.products.forEach(product => {
                const productCard = document.createElement("a");
                productCard.href = `produto_detalhes.html?sku=${product.sku}`;
                const isInStock = product.in_stock == '1';
                productCard.className = `product-card ${!isInStock ? 'out-of-stock' : ''}`;

                // --- Lógica de Preço ---
                // Determina se o produto tem um preço promocional e monta o HTML do preço de acordo.
                const price = parseFloat(product.price);
                const promoPrice = product.promotional_price ? parseFloat(product.promotional_price) : null;
                let priceHTML = '';
                let saleBadge = '';

                if (promoPrice && promoPrice < price) {
                    // Se houver preço promocional, mostra o preço antigo riscado e o novo em destaque.
                    priceHTML = `
                        <div class="product-price on-sale">
                            <span class="old-price">R$ ${price.toFixed(2).replace(".", ",")}</span>
                            <span class="promo-price">R$ ${promoPrice.toFixed(2).replace(".", ",")}</span>
                        </div>
                    `;
                    saleBadge = '<span class="sale-badge">OFERTA</span>'; // Adiciona um selo de oferta.
                } else {
                    // Caso contrário, mostra apenas o preço normal.
                    priceHTML = `<div class="product-price">R$ ${price.toFixed(2).replace(".", ",")}</div>`;
                }
                // --- Fim da Lógica de Preço ---

                // Define o selo de condição do produto ("Novo" ou "Retirado").
                let condicaoBadge = (product.condicao === 'retirado')
                    ? '<span class="condicao-badge retirado">Retirado</span>'
                    : '<span class="condicao-badge novo">Novo</span>';

                // Define os botões de ação com base na disponibilidade do estoque.
                let actionsHTML = isInStock
                    ? `<span class="details-btn">Detalhes</span><span class="whatsapp-btn"><i class="fab fa-whatsapp"></i></span>`
                    : `<div class="out-of-stock-label">Sem Estoque</div>`;

                // Insere o HTML completo do card de produto.
                productCard.innerHTML = `
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
                    <div class="product-actions">${actionsHTML}</div>
                </div>`;

                productsGrid.appendChild(productCard);
            });

            // Após renderizar os produtos, cria os botões de paginação.
            setupPagination(data.total, parseInt(productsPerPage), currentPage);

        } catch (error) {
            console.error('Falha ao procurar produtos:', error);
            productsGrid.innerHTML = `<div class="no-results"><h3>Ocorreu um erro ao carregar os produtos.</h3></div>`;
            productCountSpan.textContent = "Erro ao carregar";
        }
    }

    /**
     * Cria e gerencia os botões da paginação.
     * @param {number} totalProducts - O número total de produtos encontrados.
     * @param {number} limit - O número de produtos por página.
     * @param {number} currentPage - A página atual.
     */
    function setupPagination(totalProducts, limit, currentPage) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = "";
        const totalPages = Math.ceil(totalProducts / limit);
        if (totalPages <= 1) return; // Não mostra a paginação se houver apenas uma página.

        // Função auxiliar para criar cada botão da paginação.
        const createPageButton = (page, text, isDisabled = false) => {
            const button = document.createElement('button');
            button.innerHTML = text;
            button.className = 'page-btn';
            button.disabled = isDisabled;
            if (page === currentPage) button.classList.add('active');
            button.addEventListener('click', () => {
                renderProducts(page);
                window.scrollTo({ top: 0, behavior: 'smooth' }); // Rola para o topo ao trocar de página.
            });
            return button;
        };

        // Adiciona botões "Anterior", numéricos e "Próximo".
        paginationContainer.appendChild(createPageButton(currentPage - 1, '&laquo;', currentPage === 1));
        for (let i = 1; i <= totalPages; i++) {
            paginationContainer.appendChild(createPageButton(i, i));
        }
        paginationContainer.appendChild(createPageButton(currentPage + 1, '&raquo;', currentPage === totalPages));
    }

    // --- EVENT LISTENERS ---
    
    // Adiciona um "ouvinte" para a tecla pressionada no campo de busca.
    searchInput.addEventListener('keypress', function (event) {
        // Verifica se a tecla pressionada foi a "Enter".
        if (event.key === 'Enter') {
            // Impede a ação padrão do formulário (que seria recarregar a página).
            event.preventDefault();
            // Clica programaticamente no botão de aplicar filtros para iniciar a busca.
            applyFiltersBtn.click();
        }
    });

    // Aplica os filtros ao clicar no botão.
    applyFiltersBtn.addEventListener("click", () => {
        renderProducts(1); // Volta para a primeira página ao aplicar novos filtros.
        // Se a sidebar de filtros estiver aberta (em mobile), fecha ela.
        if (filterSidebar.classList.contains('open')) {
            filterSidebar.classList.remove('open');
            document.body.classList.remove('sidebar-open');
        }
    });

    // Lógica para abrir e fechar a barra de filtros em dispositivos móveis.
    openFiltersBtn.addEventListener('click', () => {
        filterSidebar.classList.add('open');
        document.body.classList.add('sidebar-open');
    });
    closeFiltersBtn.addEventListener('click', () => {
        filterSidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    });

    // --- CHAMADA INICIAL ---
    // Renderiza os produtos pela primeira vez ao carregar a página.
    renderProducts(1);
});
