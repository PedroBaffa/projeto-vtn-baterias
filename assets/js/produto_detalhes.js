/**
 * @file
 * assets/js/produto_detalhes.js
 * Controla toda a lógica da página de detalhes de um produto.
 * Busca os dados do produto via API, renderiza a galeria de imagens,
 * os preços, as especificações e gerencia as ações de compra.
 */

document.addEventListener("DOMContentLoaded", function () {

  // --- ELEMENTOS E FUNÇÕES DO MODAL DE LOGIN ---
  const loginModal = document.getElementById("loginRedirectModal");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const loginRedirectBtn = document.getElementById("loginRedirectBtn");

  /**
   * Exibe um modal pedindo para o usuário fazer login antes de continuar.
   * @param {string} redirectUrl - A URL para a qual o usuário será redirecionado após o login.
   */
  function showLoginModal(redirectUrl) {
    if (!loginModal || !loginRedirectBtn) return;
    loginRedirectBtn.href = redirectUrl;
    loginModal.style.display = "flex";
    setTimeout(() => loginModal.classList.add("active"), 10);
  }

  /**
   * Esconde o modal de login.
   */
  function hideLoginModal() {
    if (!loginModal) return;
    loginModal.classList.remove("active");
    setTimeout(() => (loginModal.style.display = "none"), 300);
  }

  // Adiciona os eventos para fechar o modal.
  if (closeModalBtn) closeModalBtn.addEventListener("click", hideLoginModal);
  if (loginModal) loginModal.addEventListener("click", (e) => {
    if (e.target === loginModal) hideLoginModal();
  });

  // --- FUNÇÕES DE APOIO ---

  /**
   * Exibe a mensagem de "Produto não encontrado" e esconde o conteúdo principal.
   */
  function showNotFound() {
    document.getElementById("loadingState").style.display = "none";
    document.getElementById("product-content-wrapper").style.display = "none";
    document.getElementById("notFoundState").style.display = "block";
    document.title = "Produto não encontrado - VTN";
  }

  /**
   * Configura a funcionalidade das abas (Descrição/Especificações).
   */
  function setupTabs() {
    const tabButtons = document.querySelectorAll(".tab-button");
    const tabPanes = document.querySelectorAll(".tab-pane");

    tabButtons.forEach(button => {
      button.addEventListener("click", () => {
        const targetTab = button.dataset.tab;

        tabButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        tabPanes.forEach(pane => {
          pane.classList.toggle("active", pane.id === `tab-${targetTab}`);
        });
      });
    });
  }

  // --- FUNÇÃO PRINCIPAL ---

  /**
   * Inicializa a página: busca os dados do produto na API e renderiza todo o conteúdo.
   */
  async function initializeProductDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const sku = urlParams.get("sku");
    if (!sku) { showNotFound(); return; }

    try {
      // Busca os dados do produto específico na API.
      const response = await fetch(`api.php?sku=${sku}`);
      const product = await response.json();
      if (!product || !product.id) { showNotFound(); return; }

      // Esconde o "loading" e mostra o conteúdo do produto.
      document.getElementById("loadingState").style.display = "none";
      document.getElementById("product-content-wrapper").style.display = "block";

      // --- Preenche as informações básicas do produto ---
      document.title = `${product.title} - VTN`;
      document.getElementById("breadcrumbCurrent").textContent = product.title;
      document.getElementById("productBrand").textContent = product.brand.toUpperCase();
      document.getElementById("productTitle").textContent = product.title;
      document.getElementById("productSku").textContent = `SKU: ${product.sku}`;

      // --- Lógica de Preços ---
      const price = parseFloat(product.price);
      const promoPrice = product.promotional_price ? parseFloat(product.promotional_price) : null;
      const productPriceContainer = document.getElementById("productPrice");
      const bulkDiscountEl = document.getElementById("bulkDiscount");
      const basePriceForWholesale = (promoPrice && promoPrice < price) ? promoPrice : price;

      if (promoPrice && promoPrice < price) {
        productPriceContainer.classList.add('on-sale-details');
        productPriceContainer.innerHTML = `
          <span class="old-price-details">De: R$ ${price.toFixed(2).replace(".", ",")}</span>
          <span class="promo-price-details">Por: R$ ${promoPrice.toFixed(2).replace(".", ",")}</span>`;
      } else {
        productPriceContainer.classList.remove('on-sale-details');
        productPriceContainer.innerHTML = `R$ ${price.toFixed(2).replace(".", ",")}`;
      }

      // Mostra o preço de atacado se aplicável.
      if (basePriceForWholesale > 0) {
        const wholesalePrice = (basePriceForWholesale * 0.9).toFixed(2).replace(".", ",");
        bulkDiscountEl.innerHTML = `<i class="fas fa-box-open"></i> Atacado (a partir de 10 peças): <strong>R$ ${wholesalePrice}</strong> cada`;
        bulkDiscountEl.style.display = "flex";
      } else {
        bulkDiscountEl.style.display = 'none';
      }

      // --- Lógica da Galeria de Imagens ---
      const mainImage = document.getElementById("mainProductImage");
      const thumbnailsContainer = document.getElementById("productThumbnails");
      thumbnailsContainer.innerHTML = "";
      if (product.images && product.images.length > 0) {
        mainImage.src = product.images[0];
        mainImage.alt = product.title;
        product.images.forEach((imagePath, index) => {
          const thumbItem = document.createElement("div");
          thumbItem.className = "thumbnail-item" + (index === 0 ? " active" : "");
          thumbItem.innerHTML = `<img src="${imagePath}" alt="Miniatura ${index + 1}">`;
          thumbItem.addEventListener("click", () => {
            mainImage.src = imagePath;
            document.querySelectorAll(".thumbnail-item").forEach(item => item.classList.remove("active"));
            thumbItem.classList.add("active");
          });
          thumbnailsContainer.appendChild(thumbItem);
        });
      } else {
        mainImage.src = "assets/img/placeholder.svg";
        mainImage.alt = "Sem Imagem";
      }

      // --- Preenche as Abas ---
      document.getElementById("productDescriptionText").innerHTML = product.descricao ? product.descricao.trim().split("\n").map(line => `<p>${line}</p>`).join("") : '<p>Nenhuma descrição disponível.</p>';
      generateSpecifications(product);
      setupTabs();

      // Espera a verificação de login (vinda de auth.js) para montar os botões de ação.
      if (window.authReady) {
        const isLoggedIn = await window.authReady;
        setupActionButtons(product, isLoggedIn);
      }

    } catch (error) {
      console.error("Erro ao inicializar detalhes do produto:", error);
      showNotFound();
    }
  }

  /**
   * Gera e insere a tabela de especificações técnicas do produto.
   * @param {object} product - O objeto do produto.
   */
  function generateSpecifications(product) {
    const specsGrid = document.getElementById("specsGrid");
    if (!specsGrid) return;
    specsGrid.innerHTML = "";
    const specs = [
      { label: "Marca", value: product.brand.charAt(0).toUpperCase() + product.brand.slice(1) },
      { label: "SKU", value: product.sku },
      { label: "Condição", value: product.condicao ? product.condicao.charAt(0).toUpperCase() + product.condicao.slice(1) : "N/A" },
    ];
    if (product.capacity && product.capacity > 0) {
      specs.push({ label: "Capacidade", value: `${product.capacity} mAh` });
    }
    specs.push({ label: "Garantia", value: "3 meses" });
    specs.push({ label: "Disponibilidade", value: product.in_stock == "1" ? "Em estoque" : "Indisponível" });
    specs.forEach((spec) => {
      const specItem = document.createElement("div");
      specItem.className = "spec-item";
      specItem.innerHTML = `<div class="spec-label">${spec.label}</div><div class="spec-value">${spec.value}</div>`;
      specsGrid.appendChild(specItem);
    });
  }

  /**
   * Configura os botões de ação (adicionar ao carrinho, etc.) com base no status de login e estoque.
   * @param {object} product - O objeto do produto.
   * @param {boolean} isLoggedIn - True se o usuário estiver logado.
   */
  function setupActionButtons(product, isLoggedIn) {
    const purchaseSection = document.getElementById('productPurchaseSection');
    const outOfStockLabel = document.getElementById('outOfStockLabel');
    const actionButtonContainer = document.getElementById('actionButtonContainer');

    if (!purchaseSection || !outOfStockLabel || !actionButtonContainer) return;

    // Função para renderizar o botão de "Ver Carrinho" após adicionar um item.
    const showViewCartButton = () => {
      const whatsappMessage = encodeURIComponent(`Olá! Tenho interesse no produto ${product.title} (SKU: ${product.sku})`);
      actionButtonContainer.innerHTML = `
          <a href="carrinho.php" class="add-to-cart-btn added-to-cart-state" style="text-decoration: none;">
              <i class="fas fa-shopping-cart"></i> Produto Adicionado!
          </a>
          <a href="https://wa.me/5513997979637?text=${whatsappMessage}" target="_blank" class="whatsapp-direct-btn" title="Chamar no WhatsApp">
              <i class="fab fa-whatsapp"></i>
          </a>`;
    };

    // Função para renderizar o botão padrão de "Adicionar ao Orçamento".
    const showAddToCartButton = () => {
      const whatsappMessage = encodeURIComponent(`Olá! Tenho interesse no produto ${product.title} (SKU: ${product.sku})`);
      actionButtonContainer.innerHTML = `
          <div class="product-add-to-cart-section">
              <div class="quantity-selector">
                  <button id="decreaseQtyBtn" aria-label="Diminuir quantidade">-</button>
                  <input type="number" id="quantityInput" value="1" min="1" aria-label="Quantidade">
                  <button id="increaseQtyBtn" aria-label="Aumentar quantidade">+</button>
              </div>
              <button class="add-to-cart-btn" id="addToCartBtn">
                  <i class="fas fa-shopping-cart"></i> Adicionar ao Orçamento
              </button>
              <a href="https://wa.me/5513997979637?text=${whatsappMessage}" target="_blank" class="whatsapp-direct-btn" title="Chamar no WhatsApp">
                  <i class="fab fa-whatsapp"></i>
              </a>
          </div>`;

      // Adiciona eventos aos botões de quantidade.
      const qtyInput = document.getElementById('quantityInput');
      document.getElementById('decreaseQtyBtn').onclick = () => { qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1); };
      document.getElementById('increaseQtyBtn').onclick = () => { qtyInput.value = parseInt(qtyInput.value) + 1; };

      // Adiciona o evento principal ao botão de adicionar ao carrinho.
      document.getElementById('addToCartBtn').addEventListener('click', async () => {
        if (isLoggedIn) {
          const quantity = parseInt(qtyInput.value);
          const success = await addToCart(product, quantity); // Função global de carrinho.js
          if (success) {
            showViewCartButton();
          }
        } else {
          // Se não estiver logado, mostra o modal de login.
          const redirectUrl = `login_usuario.html?redirect=${encodeURIComponent(window.location.href)}`;
          showLoginModal(redirectUrl);
        }
      });
    };

    // Decide qual conjunto de botões/avisos exibir com base no estoque.
    if (product.in_stock == '1') {
      purchaseSection.style.display = 'block';
      outOfStockLabel.style.display = 'none';
      showAddToCartButton();
    } else {
      purchaseSection.style.display = 'none';
      outOfStockLabel.style.display = 'block';
    }
  }

  // --- LÓGICA DOS COMPONENTES ADICIONAIS ---

  // Lógica para o modal de zoom da imagem.
  const modal = document.getElementById("imageModal");
  const productImage = document.getElementById("mainProductImage");
  const modalImage = document.getElementById("modalImage");
  const closeModalZoomBtn = modal ? modal.querySelector(".close-modal-btn") : null;
  if (productImage && modal && modalImage && closeModalZoomBtn) {
    productImage.onclick = function () {
      if (this.src.includes("placeholder.svg")) return; // Não abre o zoom para a imagem padrão.
      modal.style.display = "block";
      modalImage.src = this.src;
    };
    const closeModal = () => (modal.style.display = "none");
    closeModalZoomBtn.onclick = closeModal;
    modal.onclick = (event) => { if (event.target == modal) closeModal(); };
  }

  // Lógica para a calculadora de frete.
  const cepInput = document.getElementById("cepInput");
  const calculateShippingBtn = document.getElementById("calculateShippingBtn");
  const shippingResult = document.getElementById("shippingResult");
  if (calculateShippingBtn) {
    calculateShippingBtn.addEventListener("click", async () => {
      const cep = cepInput.value.replace(/\D/g, "");
      if (cep.length !== 8) {
        shippingResult.innerHTML = `<div class="error">Por favor, digite um CEP válido.</div>`;
        return;
      }
      shippingResult.innerHTML = `<div class="loading"><i class="fas fa-spinner fa-spin"></i> Calculando...</div>`;
      try {
        const response = await fetch(`frete_api.php?cep=${cep}`);
        const data = await response.json();
        if (data.sucesso && data.opcoes.length > 0) {
          let html = "<ul>";
          data.opcoes.forEach((opcao) => {
            html += `<li><span><strong>${opcao.tipo}</strong></span><span>Prazo: ${opcao.prazo} dias</span><span>Valor: R$ ${opcao.valor}</span></li>`;
          });
          html += "</ul>";
          shippingResult.innerHTML = html;
        } else {
          shippingResult.innerHTML = `<div class="error">${data.mensagem || "Não foi possível calcular o frete."}</div>`;
        }
      } catch (error) {
        shippingResult.innerHTML = `<div class="error">Ocorreu um erro. Tente novamente.</div>`;
      }
    });
  }

  // --- CHAMADA INICIAL ---
  initializeProductDetails();
});