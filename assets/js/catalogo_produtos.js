document.addEventListener("DOMContentLoaded", () => {
    // Seleciona todos os elementos necessários da página
    const productsGrid = document.getElementById("productsGrid");
    const paginationContainer = document.getElementById("paginationContainer");
    const searchInput = document.getElementById("searchInput");
    const searchInputDesktop = document.getElementById("searchInputDesktop");
    const brandFilter = document.getElementById("brandFilter");
    const sortFilter = document.getElementById("sortFilter");
    const limitFilter = document.getElementById("limitFilter");
    const applyFiltersBtn = document.getElementById("applyFiltersBtn");
    const clearFiltersBtn = document.getElementById("clearFiltersBtn");
    const productCountSpan = document.getElementById("productCount");
    const toggleFiltersBtn = document.getElementById("toggleFiltersBtn");
    const advancedFilters = document.getElementById("advancedFilters");

    let currentPage = 1;
    let userData = null;

    async function fetchUserData() {
        try {
            const response = await fetch(`api.php?get_user_data=true`);
            const data = await response.json();
            if (data.logado) {
                userData = data.usuario;
            }
        } catch (error) {
            console.error('Não foi possível buscar dados do usuário.', error);
        }
    }

    async function renderProducts(page = 1) {
        currentPage = page;

        const searchTerm = window.innerWidth <= 768 ? searchInput.value : searchInputDesktop.value;
        const filterBrand = brandFilter.value;
        const sortOrder = sortFilter.value;
        const productsPerPage = limitFilter.value;

        productsGrid.innerHTML = '<div class="loading" id="loadingState"><i class="fas fa-spinner fa-spin"></i><h3>A carregar produtos...</h3></div>';
        if (paginationContainer) paginationContainer.innerHTML = '';
        if (productCountSpan) productCountSpan.textContent = 'Carregando...';

        try {
            const timestamp = new Date().getTime();
            let apiUrl = `api.php?limit=${productsPerPage}&page=${currentPage}&sort=${sortOrder}&t=${timestamp}`;
            if (filterBrand && filterBrand !== 'all') apiUrl += `&brand=${encodeURIComponent(filterBrand)}`;
            if (searchTerm.trim()) apiUrl += `&search=${encodeURIComponent(searchTerm)}`;

            const response = await fetch(apiUrl);
            const data = await response.json();
            productsGrid.innerHTML = "";

            if (data.products.length === 0) {
                productsGrid.innerHTML = `<div class="no-results"><i class="fas fa-search"></i><h3>Nenhum produto encontrado</h3><p>Tente ajustar a sua busca ou filtro.</p></div>`;
                productCountSpan.textContent = "0 produtos encontrados";
                return;
            }

            productCountSpan.textContent = `${data.total} produtos encontrados`;

            data.products.forEach(product => {
                const productCard = document.createElement("div");
                const isInStock = product.in_stock == '1';
                productCard.className = `product-card ${!isInStock ? 'out-of-stock' : ''}`;

                const price = parseFloat(product.price);
                const promoPrice = product.promotional_price ? parseFloat(product.promotional_price) : null;
                let priceHTML = '', saleBadge = '';

                if (promoPrice && promoPrice < price) {
                    priceHTML = `<div class="product-price on-sale"><span class="old-price">R$ ${price.toFixed(2).replace(".", ",")}</span><span class="promo-price">R$ ${promoPrice.toFixed(2).replace(".", ",")}</span></div>`;
                    saleBadge = '<span class="sale-badge">OFERTA</span>';
                } else {
                    priceHTML = `<div class="product-price">R$ ${price.toFixed(2).replace(".", ",")}</div>`;
                }

                let condicaoBadge = (product.condicao === 'retirado') ? '<span class="condicao-badge retirado">Retirado</span>' : '<span class="condicao-badge novo">Novo</span>';
                let whatsappMessage = `Olá! Tenho interesse no produto: ${product.title} (SKU: ${product.sku})`;
                if (userData && userData.endereco) {
                    whatsappMessage += `\n\nEndereço para entrega:\n${userData.endereco}`;
                }
                const whatsappUrl = `https://wa.me/5513997979637?text=${encodeURIComponent(whatsappMessage)}`;
                let actionsHTML = isInStock
                    ? `<a href="produto_detalhes.html?sku=${product.sku}" class="details-btn">Detalhes</a><a href="${whatsappUrl}" class="whatsapp-btn" target="_blank" title="Consultar no WhatsApp"><i class="fab fa-whatsapp"></i></a>`
                    : `<div class="out-of-stock-label">Sem Estoque</div>`;

                productCard.innerHTML = `
                    <a href="produto_detalhes.html?sku=${product.sku}" class="product-link-wrapper">
                        <div class="product-image">${saleBadge}<img src="${product.image || 'assets/img/placeholder.svg'}" alt="${product.title}" onerror="this.src='assets/img/placeholder.svg';"></div>
                        <div class="product-info">
                            <div class="product-brand-wrapper"><div class="product-brand">${product.brand.toUpperCase()}</div>${condicaoBadge}</div>
                            <h3 class="product-title">${product.title}</h3>
                            <div class="product-sku">SKU: ${product.sku}</div>
                            ${priceHTML}
                        </div>
                    </a>
                    <div class="product-actions-wrapper"><div class="product-actions">${actionsHTML}</div></div>`;
                productsGrid.appendChild(productCard);
            });

            setupPagination(data.total, parseInt(productsPerPage), currentPage);

        } catch (error) {
            console.error('Falha ao procurar produtos:', error);
            productsGrid.innerHTML = `<div class="no-results"><h3>Ocorreu um erro ao carregar os produtos.</h3></div>`;
            productCountSpan.textContent = "Erro ao carregar";
        }
    }

    function setupPagination(totalProducts, limit, currentPage) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = "";
        const totalPages = Math.ceil(totalProducts / limit);
        if (totalPages <= 1) return;
        const createPageButton = (page, text, isDisabled = false) => {
            const button = document.createElement('button');
            button.innerHTML = text;
            button.className = 'page-btn';
            button.disabled = isDisabled;
            if (page === currentPage) button.classList.add('active');
            button.addEventListener('click', () => {
                renderProducts(page);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            return button;
        };
        paginationContainer.appendChild(createPageButton(currentPage - 1, '&laquo;', currentPage === 1));
        for (let i = 1; i <= totalPages; i++) {
            paginationContainer.appendChild(createPageButton(i, i));
        }
        paginationContainer.appendChild(createPageButton(currentPage + 1, '&raquo;', currentPage === totalPages));
    }

    // --- EVENT LISTENERS ---

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener("click", () => {
            renderProducts(1);
            if (window.innerWidth <= 768 && advancedFilters) {
                advancedFilters.classList.remove('visible');
                if (toggleFiltersBtn) toggleFiltersBtn.classList.remove('active');
            }
        });
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            window.location.href = 'catalogo_produtos.php';
        });
    }

    const handleSearchOnEnter = (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            renderProducts(1);
        }
    };
    if (searchInput) searchInput.addEventListener('keypress', handleSearchOnEnter);
    if (searchInputDesktop) searchInputDesktop.addEventListener('keypress', handleSearchOnEnter);

    if (toggleFiltersBtn && advancedFilters) {
        toggleFiltersBtn.addEventListener('click', () => {
            advancedFilters.classList.toggle('visible');
            toggleFiltersBtn.classList.toggle('active');
        });
    }

    // --- FUNÇÃO DE INICIALIZAÇÃO ---
    async function initializePage() {
        // ** MUDANÇA PRINCIPAL AQUI **
        // 1. Pega os parâmetros da URL
        const urlParams = new URLSearchParams(window.location.search);
        const brandFromUrl = urlParams.get('brand');

        // 2. Se uma marca veio da URL, seleciona ela no filtro
        if (brandFromUrl && brandFilter) {
            brandFilter.value = brandFromUrl;
        }

        // 3. O resto continua igual
        await fetchUserData();
        renderProducts(1);
    }

    initializePage();
});