/**
 * @file
 * assets/js/index.js
 * Script principal para a página inicial (index.html).
 * Controla a interatividade de múltiplos componentes, como o hero,
 * carrosséis, menus, popups e a funcionalidade de busca.
 */

document.addEventListener("DOMContentLoaded", function () {

    // --- LÓGICA DO HERO CARD PROMOCIONAL ---
    const promoHeroCard = document.getElementById('promo-hero');
    const fallbackHero = document.getElementById('fallback-hero');

    /**
     * Busca produtos em promoção via API e, se existirem,
     * configura um card de destaque que alterna entre eles.
     * Se não houver promoções, exibe um hero padrão.
     */
    async function setupPromoHero() {
        if (!promoHeroCard || !fallbackHero) return;

        try {
            const response = await fetch('api.php?on_promo=true');
            const data = await response.json();

            if (data.promo_products && data.promo_products.length > 0) {
                fallbackHero.style.display = 'none';
                promoHeroCard.style.display = 'block';

                const products = data.promo_products;
                let currentIndex = 0;

                // Elementos do DOM que serão atualizados
                const cardLink = document.getElementById('hero-card-link');
                const cardImage = document.getElementById('hero-card-image');
                const cardTitle = document.getElementById('hero-card-product-title');
                const cardSku = document.getElementById('hero-card-product-sku');
                const cardPriceOld = document.getElementById('hero-card-price-old');
                const cardPriceNew = document.getElementById('hero-card-price-new');
                const cardButton = document.getElementById('hero-card-button');

                // Função para preencher o card com os dados de um produto.
                function updateHeroContent(product) {
                    const productUrl = `produto_detalhes.html?sku=${product.sku}`;
                    cardLink.href = productUrl;
                    cardImage.src = product.image || 'assets/img/placeholder.svg';
                    cardImage.alt = product.title;
                    cardTitle.textContent = product.title;
                    cardSku.textContent = `SKU: ${product.sku}`;
                    cardPriceOld.textContent = `R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')}`;
                    cardPriceNew.textContent = `R$ ${parseFloat(product.promotional_price).toFixed(2).replace('.', ',')}`;
                    cardButton.href = productUrl;
                }

                // Exibe o primeiro produto.
                updateHeroContent(products[currentIndex]);

                // Se houver mais de um produto em promoção, inicia o carrossel.
                if (products.length > 1) {
                    setInterval(() => {
                        currentIndex = (currentIndex + 1) % products.length;
                        // Animação de fade out
                        promoHeroCard.style.opacity = '0';
                        promoHeroCard.style.transform = 'scale(0.95)';

                        // Troca o conteúdo e faz o fade in
                        setTimeout(() => {
                            updateHeroContent(products[currentIndex]);
                            promoHeroCard.style.opacity = '1';
                            promoHeroCard.style.transform = 'scale(1)';
                        }, 500); // Tempo para a transição

                    }, 5000); // Troca a cada 5 segundos
                }
            } else {
                // Se não houver promoções, exibe o hero padrão.
                fallbackHero.style.display = 'flex';
                promoHeroCard.style.display = 'none';
            }
        } catch (error) {
            console.error('Erro ao configurar o hero:', error);
            fallbackHero.style.display = 'flex';
            promoHeroCard.style.display = 'none';
        }
    }
    setupPromoHero();

    // --- LÓGICA GERAL DO HEADER E MENU PRINCIPAL (ESQUERDO) ---
    const menuBtn = document.querySelector(".menu-btn");
    const closeBtn = document.querySelector(".sidebar .close-btn");
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.querySelector(".overlay");

    if (menuBtn && sidebar && overlay && closeBtn) {
        const openMenu = () => {
            sidebar.classList.add("active");
            overlay.classList.add("active");
            document.body.style.overflow = "hidden";
        };
        const closeMenu = () => {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
            document.body.style.overflow = "auto";
        };
        menuBtn.addEventListener("click", openMenu);
        closeBtn.addEventListener("click", closeMenu);
        overlay.addEventListener("click", closeMenu);
    }

    // --- LÓGICA DO CARROSSEL DE PRODUTOS EM DESTAQUE ---
    const productCarousel = document.getElementById("productCarousel");
    if (productCarousel) {
        const prevBtn = document.getElementById("carousel-prev");
        const nextBtn = document.getElementById("carousel-next");

        // Busca produtos aleatórios na API para preencher o carrossel.
        const fetchFeaturedProducts = async () => {
            try {
                const response = await fetch('api.php?limit=10&random=true');
                if (!response.ok) throw new Error('A resposta da rede não foi bem-sucedida.');

                const data = await response.json();
                if (!data.products || data.products.length === 0) {
                    productCarousel.innerHTML = '<p>Nenhum produto em destaque encontrado.</p>';
                    return;
                }

                productCarousel.innerHTML = ''; // Limpa o estado de "carregando".
                // Cria um card para cada produto retornado.
                data.products.forEach(product => {
                    const productCard = document.createElement("a");
                    productCard.href = `produto_detalhes.html?sku=${product.sku}`;
                    productCard.className = "product-card";
                    const price = parseFloat(product.price);
                    productCard.innerHTML = `
                        <div class="product-image"><img src="${product.image || 'https://placehold.co/150x150/f0f0f0/666666?text=Sem+Imagem'}" alt="${product.title}" onerror="this.src='https://placehold.co/150x150/f0f0f0/666666?text=Sem+Imagem';"></div>
                        <div class="product-info">
                            <div class="product-brand">${product.brand.toUpperCase()}</div>
                            <h3 class="product-title">${product.title}</h3>
                            <div class="product-sku">SKU: ${product.sku}</div>
                            <div class="product-price">R$ ${price.toFixed(2).replace(".", ",")}</div>
                        </div>`;
                    productCarousel.appendChild(productCard);
                });
            } catch (error) {
                console.error('Erro ao carregar produtos em destaque:', error);
                productCarousel.innerHTML = '<p>Erro ao carregar produtos.</p>';
            }
        };

        // Eventos para os botões de navegação do carrossel.
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                const card = productCarousel.querySelector('.product-card');
                if (card) {
                    const cardWidth = card.offsetWidth;
                    productCarousel.scrollBy({ left: -(cardWidth + 20), behavior: 'smooth' });
                }
            });

            nextBtn.addEventListener('click', () => {
                const card = productCarousel.querySelector('.product-card');
                if (card) {
                    const cardWidth = card.offsetWidth;
                    productCarousel.scrollBy({ left: cardWidth + 20, behavior: 'smooth' });
                }
            });
        }

        fetchFeaturedProducts();
    }

    // --- LÓGICA DO POPUP DE CONTATO ---
    const contactPopup = document.getElementById('contactPopup');
    const closePopupBtn = document.getElementById('closePopupBtn');
    const contactForm = document.getElementById('contactForm');
    const popupMessage = document.getElementById('popupMessage');

    const closeContactPopup = () => {
        if (contactPopup) {
            contactPopup.classList.remove('active');
        }
    };

    if (contactPopup && closePopupBtn) {
        closePopupBtn.addEventListener('click', closeContactPopup);
        contactPopup.addEventListener('click', (event) => {
            if (event.target === contactPopup) {
                closeContactPopup();
            }
        });
    }

    // Envia os dados do formulário de contato para o back-end.
    if (contactForm) {
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(contactForm);
            const data = Object.fromEntries(formData.entries());
            popupMessage.textContent = 'Enviando...';
            popupMessage.style.color = '#333';

            try {
                const response = await fetch('salvar_contato.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (response.ok) {
                    popupMessage.textContent = result.mensagem;
                    popupMessage.style.color = 'var(--verde-escuro)';
                    contactForm.reset();
                    // Grava na sessão do navegador que o popup já foi visto/preenchido.
                    sessionStorage.setItem('vtn_popup_seen', 'true');
                    setTimeout(closeContactPopup, 2000);
                } else {
                    throw new Error(result.mensagem);
                }
            } catch (error) {
                popupMessage.textContent = error.message;
                popupMessage.style.color = '#c0392b';
            }
        });
    }

    // --- LÓGICA DO BOTÃO VOLTAR AO TOPO ---
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        // Mostra o botão quando o usuário rola a página.
        window.onscroll = function () {
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        };
        // Rola a página suavemente para o topo ao clicar.
        backToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // --- SCRIPT DO WHATSAPP NA SEÇÃO DE CONTATO ---
    const whatsappMessageInput = document.getElementById('whatsappMessageInput');
    const whatsappSectionBtn = document.getElementById('whatsappSectionBtn');
    if (whatsappMessageInput && whatsappSectionBtn) {
        // Atualiza o link do botão de WhatsApp com a mensagem digitada pelo usuário.
        const updateWhatsAppLink = () => {
            const message = whatsappMessageInput.value;
            const baseUrl = "https://wa.me/5513997979637";
            whatsappSectionBtn.href = message ? `${baseUrl}?text=${encodeURIComponent(message)}` : baseUrl;
        };
        whatsappMessageInput.addEventListener('input', updateWhatsAppLink);
        updateWhatsAppLink(); // Atualiza uma vez ao carregar a página.
    }

    // --- LÓGICA DO MODAL DE PESQUISA (GLOBAL) ---
    const searchModal = document.getElementById("searchModal");
    const searchBtn = document.getElementById("searchBtn");
    const closeSearchBtn = document.getElementById("closeSearchBtn");
    const modalSearchInput = document.getElementById("modalSearchInput");
    const searchResultsContainer = document.getElementById("searchResults");
    let searchTimeout;

    if (searchModal && searchBtn && closeSearchBtn && modalSearchInput && searchResultsContainer) {
        const openSearchModal = () => {
            searchModal.classList.add("active");
            modalSearchInput.focus();
        };

        const closeSearchModal = () => {
            searchModal.classList.remove("active");
        };

        // Realiza a busca na API e exibe os resultados.
        const performSearch = async (query) => {
            if (query.length < 3) {
                searchResultsContainer.innerHTML = '<p class="search-result-info">Digite pelo menos 3 caracteres para buscar.</p>';
                return;
            }
            searchResultsContainer.innerHTML = '<div class="loading" style="padding: 20px; text-align: center;"><i class="fas fa-spinner fa-spin"></i></div>';

            try {
                const response = await fetch(`api.php?limit=5&search=${encodeURIComponent(query)}`);
                const data = await response.json();

                searchResultsContainer.innerHTML = "";
                if (data.products && data.products.length > 0) {
                    data.products.forEach(product => {
                        const item = document.createElement('a');
                        item.href = `produto_detalhes.html?sku=${product.sku}`;
                        item.className = 'search-result-item';
                        item.innerHTML = `
                            <img src="${product.image || 'assets/img/placeholder.svg'}" alt="${product.title}">
                            <div class="search-result-info">
                                <div class="title">${product.title}</div>
                                <div class="sku">SKU: ${product.sku}</div>
                            </div>`;
                        searchResultsContainer.appendChild(item);
                    });
                } else {
                    searchResultsContainer.innerHTML = '<p style="padding: 20px; text-align: center;">Nenhum produto encontrado.</p>';
                }
            } catch (error) {
                console.error("Erro na pesquisa:", error);
                searchResultsContainer.innerHTML = '<p style="padding: 20px; text-align: center;">Ocorreu um erro ao buscar.</p>';
            }
        };

        // Adiciona os eventos de abrir/fechar o modal.
        searchBtn.addEventListener("click", openSearchModal);
        closeSearchBtn.addEventListener("click", closeSearchModal);
        searchModal.addEventListener("click", (e) => {
            if (e.target === searchModal) {
                closeSearchModal();
            }
        });

        // Adiciona um "debounce" para a busca, evitando chamadas excessivas à API.
        modalSearchInput.addEventListener("input", () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(modalSearchInput.value);
            }, 300); // Espera 300ms após o usuário parar de digitar.
        });
    }
});