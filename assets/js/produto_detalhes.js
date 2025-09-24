document.addEventListener("DOMContentLoaded", function () {

  const loginModal = document.getElementById("loginRedirectModal");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const loginRedirectBtn = document.getElementById("loginRedirectBtn");

  // --- FUNÇÃO DO MODAL MELHORADA ---
  // Agora ela aceita uma mensagem para ser exibida
  function showLoginModal(redirectUrl, message) {
    if (!loginModal || !loginRedirectBtn) return;

    // Procura o elemento de texto dentro do modal e atualiza-o
    const modalText = loginModal.querySelector('.custom-modal-text');
    if (modalText && message) {
      modalText.textContent = message;
    }

    loginRedirectBtn.href = redirectUrl;
    loginModal.style.display = "flex";
    setTimeout(() => loginModal.classList.add("active"), 10);
  }

  function hideLoginModal() {
    if (!loginModal) return;
    loginModal.classList.remove("active");
    setTimeout(() => (loginModal.style.display = "none"), 300);
  }
  if (closeModalBtn) closeModalBtn.addEventListener("click", hideLoginModal);
  if (loginModal) loginModal.addEventListener("click", (e) => {
    if (e.target === loginModal) hideLoginModal();
  });

  function showNotFound() {
    document.getElementById("loadingState").style.display = "none";
    document.getElementById("product-content-wrapper").style.display = "none";
    document.getElementById("notFoundState").style.display = "block";
    document.title = "Produto não encontrado - VTN";
  }

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

  function setupFaq(faqData, product_id, isLoggedIn) {
    const faqList = document.getElementById('faq-list');
    const faqForm = document.getElementById('faq-form');
    const faqFormMessage = document.getElementById('faq-form-message');
    const faqSubmitBtn = document.getElementById('faq-submit-btn');
    const faqTextarea = document.getElementById('faq-question-input');

    if (!faqList || !faqForm) return;

    if (faqData && faqData.length > 0) {
      faqList.innerHTML = '';
      faqData.forEach(item => {
        const faqItem = document.createElement('div');
        faqItem.className = 'faq-item';
        const respostaHtml = item.resposta
          ? `<div class="faq-answer-content"><i class="fas fa-comment-dots"></i><p>${item.resposta}</p></div>`
          : `<div class="faq-answer-content italic text-gray-500"><i class="fas fa-clock"></i><p>Aguardando resposta.</p></div>`;
        const nomeClienteHtml = item.nome_cliente ? `<p class="faq-author"><i class="fas fa-user"></i> Perguntado por: <strong>${item.nome_cliente}</strong></p>` : '';
        faqItem.innerHTML = `
                <button class="faq-question">
                    <span><i class="fas fa-question-circle"></i><div>${nomeClienteHtml}<p class="faq-question-text">${item.pergunta}</p></div></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">${respostaHtml}</div>`;
        faqList.appendChild(faqItem);
      });
    } else {
      faqList.innerHTML = '<p class="text-gray-500 text-center py-4">Ainda não há perguntas para este produto. Seja o primeiro a perguntar!</p>';
    }

    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
      const question = item.querySelector('.faq-question');
      const answer = item.querySelector('.faq-answer');
      const icon = question.querySelector('i.fa-chevron-down');
      question.addEventListener('click', () => {
        const isOpen = answer.style.maxHeight && answer.style.maxHeight !== '0px';
        faqItems.forEach(otherItem => {
          if (otherItem !== item) {
            otherItem.querySelector('.faq-answer').style.maxHeight = '0px';
            const otherIcon = otherItem.querySelector('.faq-question i.fa-chevron-down');
            if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
          }
        });
        if (isOpen) {
          answer.style.maxHeight = '0px';
          if (icon) icon.style.transform = 'rotate(0deg)';
        } else {
          answer.style.maxHeight = answer.scrollHeight + 'px';
          if (icon) icon.style.transform = 'rotate(180deg)';
        }
      });
    });

    faqForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (!isLoggedIn) {
        // --- CHAMADA DO MODAL ATUALIZADA ---
        const redirectUrl = `login_usuario.html?redirect=${encodeURIComponent(window.location.href)}`;
        const message = 'Você precisa fazer login para enviar uma pergunta.';
        showLoginModal(redirectUrl, message);
        return;
      }
      const formData = new FormData(faqForm);
      const data = { acao: 'enviar_pergunta', pergunta: formData.get('pergunta'), produto_id: product_id };
      faqSubmitBtn.disabled = true;
      faqSubmitBtn.textContent = 'Enviando...';
      faqFormMessage.textContent = '';
      try {
        const response = await fetch('acoes_faq_cliente.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        const result = await response.json();
        if (response.ok) {
          faqFormMessage.className = 'faq-form-message success';
          faqFormMessage.textContent = result.mensagem;
          faqTextarea.value = '';
          setTimeout(() => location.reload(), 2000);
        } else {
          throw new Error(result.mensagem);
        }
      } catch (error) {
        faqFormMessage.className = 'faq-form-message error';
        faqFormMessage.textContent = error.message;
      } finally {
        faqSubmitBtn.disabled = false;
        faqSubmitBtn.textContent = 'Enviar Pergunta';
      }
    });
  }

  async function initializeProductDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const sku = urlParams.get("sku");
    if (!sku) { showNotFound(); return; }

    try {
      const response = await fetch(`api.php?sku=${sku}`);
      const product = await response.json();
      if (!product || !product.id) { showNotFound(); return; }

      document.getElementById("loadingState").style.display = "none";
      document.getElementById("product-content-wrapper").style.display = "block";
      document.title = `${product.title} | Bateria ${product.brand} | Grupo VTN`;
      document.getElementById("metaDescription").setAttribute("content", `Compre a ${product.title} com garantia. Bateria de ${product.capacity || 'alta'} mAh para celulares ${product.brand}. Orçamento rápido via WhatsApp.`);
      document.getElementById("breadcrumbCurrent").textContent = product.title;
      document.getElementById("productBrand").textContent = product.brand.toUpperCase();
      document.getElementById("productTitle").textContent = product.title;
      document.getElementById("productSku").textContent = `SKU: ${product.sku}`;

      const price = parseFloat(product.price);
      const promoPrice = product.promotional_price ? parseFloat(product.promotional_price) : null;
      const productPriceContainer = document.getElementById("productPrice");
      const bulkDiscountEl = document.getElementById("bulkDiscount");
      const basePriceForWholesale = (promoPrice && promoPrice < price) ? promoPrice : price;

      if (promoPrice && promoPrice < price) {
        productPriceContainer.classList.add('on-sale-details');
        productPriceContainer.innerHTML = `<span class="old-price-details">De: R$ ${price.toFixed(2).replace(".", ",")}</span><span class="promo-price-details">Por: R$ ${promoPrice.toFixed(2).replace(".", ",")}</span>`;
      } else {
        productPriceContainer.classList.remove('on-sale-details');
        productPriceContainer.innerHTML = `R$ ${price.toFixed(2).replace(".", ",")}`;
      }
      if (basePriceForWholesale > 0) {
        const wholesalePrice = (basePriceForWholesale * 0.9).toFixed(2).replace(".", ",");
        bulkDiscountEl.innerHTML = `<i class="fas fa-box-open"></i> Atacado (a partir de 10 peças): <strong>R$ ${wholesalePrice}</strong> cada`;
        bulkDiscountEl.style.display = "flex";
      } else {
        bulkDiscountEl.style.display = 'none';
      }

      const mainImage = document.getElementById("mainProductImage");
      const thumbnailsContainer = document.getElementById("productThumbnails");
      thumbnailsContainer.innerHTML = "";

      if (product.images && product.images.length > 0) {
        mainImage.src = product.images[0].image_path;
        mainImage.alt = product.title;
        product.images.forEach((img, index) => {
          const thumbItem = document.createElement("div");
          thumbItem.className = "thumbnail-item" + (index === 0 ? " active" : "");
          thumbItem.innerHTML = `<img src="${img.image_path}" alt="Miniatura ${index + 1}">`;
          thumbItem.addEventListener("click", () => {
            mainImage.src = img.image_path;
            document.querySelectorAll(".thumbnail-item").forEach(item => item.classList.remove("active"));
            thumbItem.classList.add("active");
          });
          thumbnailsContainer.appendChild(thumbItem);
        });
      } else {
        mainImage.src = "assets/img/placeholder.svg";
        mainImage.alt = "Sem Imagem";
      }

      document.getElementById("productDescriptionText").innerHTML = product.descricao ? product.descricao.trim().split("\n").map(line => `<p>${line}</p>`).join("") : '<p>Nenhuma descrição disponível.</p>';
      generateSpecifications(product);
      setupTabs();

      if (window.authReady) {
        const isLoggedIn = await window.authReady;
        setupFaq(product.faq, product.id, isLoggedIn);
        setupActionButtons(product, isLoggedIn);
      }

    } catch (error) {
      console.error("Erro ao inicializar detalhes do produto:", error);
      showNotFound();
    }
  }

  function generateSpecifications(product) {
    const specsGrid = document.getElementById("specsGrid");
    specsGrid.innerHTML = "";
    const specs = [
      { label: "Marca", value: product.brand.charAt(0).toUpperCase() + product.brand.slice(1) },
      { label: "SKU", value: product.sku },
      { label: "Condição", value: product.condicao ? product.condicao.charAt(0).toUpperCase() + product.condicao.slice(1) : "N/A" },
      { label: "Garantia", value: "3 meses" },
      { label: "Disponibilidade", value: product.in_stock == "1" ? "Em estoque" : "Indisponível" }
    ];
    if (product.capacity && product.capacity > 0) {
      specs.splice(3, 0, { label: "Capacidade", value: `${product.capacity} mAh` });
    }
    specs.forEach((spec) => {
      const specItem = document.createElement("div");
      specItem.className = "spec-item";
      specItem.innerHTML = `<div class="spec-label">${spec.label}</div><div class="spec-value">${spec.value}</div>`;
      specsGrid.appendChild(specItem);
    });
  }

  function setupActionButtons(product, isLoggedIn) {
    const purchaseSection = document.getElementById('productPurchaseSection');
    const outOfStockLabel = document.getElementById('outOfStockLabel');
    const actionButtonContainer = document.getElementById('actionButtonContainer');

    if (!purchaseSection || !outOfStockLabel || !actionButtonContainer) return;

    const generateWhatsappMessage = () => {
      let message = `Olá! Tenho interesse no produto ${product.title} (SKU: ${product.sku}).`;
      if (product.promotional_price && parseFloat(product.promotional_price) < parseFloat(product.price)) {
        const price = parseFloat(product.price).toFixed(2).replace('.', ',');
        const promoPrice = parseFloat(product.promotional_price).toFixed(2).replace('.', ',');
        message += `\n\nPromoção "${product.promotion_name || 'Especial'}":\nDe: R$ ${price}\nPor: R$ ${promoPrice}`;
      }
      return encodeURIComponent(message);
    };

    const whatsappUrl = `https://wa.me/5513997979637?text=${generateWhatsappMessage()}`;

    const showViewCartButton = () => {
      actionButtonContainer.innerHTML = `<a href="carrinho.php" class="add-to-cart-btn added-to-cart-state" style="text-decoration: none;"><i class="fas fa-shopping-cart"></i> Produto Adicionado!</a><a href="${whatsappUrl}" target="_blank" class="whatsapp-direct-btn" title="Chamar no WhatsApp"><i class="fab fa-whatsapp"></i></a>`;
    };

    const showAddToCartButton = () => {
      actionButtonContainer.innerHTML = `<div class="product-add-to-cart-section"><div class="quantity-selector"><button id="decreaseQtyBtn">-</button><input type="number" id="quantityInput" value="1" min="1"><button id="increaseQtyBtn">+</button></div><button class="add-to-cart-btn" id="addToCartBtn"><i class="fas fa-shopping-cart"></i> Adicionar ao Orçamento</button><a href="${whatsappUrl}" target="_blank" class="whatsapp-direct-btn" title="Chamar no WhatsApp"><i class="fab fa-whatsapp"></i></a></div>`;

      const addToCartBtn = document.getElementById('addToCartBtn');
      const qtyInput = document.getElementById('quantityInput');
      document.getElementById('decreaseQtyBtn').onclick = () => { qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1); };
      document.getElementById('increaseQtyBtn').onclick = () => { qtyInput.value = parseInt(qtyInput.value) + 1; };

      if (addToCartBtn) {
        addToCartBtn.addEventListener('click', async () => {
          if (isLoggedIn) {
            const success = await addToCart(product, parseInt(qtyInput.value));
            if (success) {
              showViewCartButton();
            }
          } else {
            // --- CHAMADA DO MODAL ATUALIZADA ---
            const redirectUrl = `login_usuario.html?redirect=${encodeURIComponent(window.location.href)}`;
            const message = 'Você precisa fazer login para adicionar produtos ao orçamento.';
            showLoginModal(redirectUrl, message);
          }
        });
      }
    };

    if (product.in_stock == '1') {
      purchaseSection.style.display = 'block';
      outOfStockLabel.style.display = 'none';
      showAddToCartButton();
    } else {
      purchaseSection.style.display = 'none';
      outOfStockLabel.style.display = 'block';
    }
  }

  const modal = document.getElementById("imageModal");
  const productImage = document.getElementById("mainProductImage");
  const modalImage = document.getElementById("modalImage");
  const closeModalZoomBtn = modal ? modal.querySelector(".close-modal-btn") : null;
  if (productImage && modal && modalImage && closeModalZoomBtn) {
    productImage.onclick = function () { if (this.src.includes("placeholder.svg")) return; modal.style.display = "block"; modalImage.src = this.src; };
    const closeModal = () => (modal.style.display = "none");
    closeModalZoomBtn.onclick = closeModal;
    modal.onclick = (event) => { if (event.target == modal) closeModal(); };
  }

  const calculateShippingBtn = document.getElementById("calculateShippingBtn");
  if (calculateShippingBtn) {
    calculateShippingBtn.addEventListener("click", async () => {
      const cepInput = document.getElementById("cepInput");
      const shippingResult = document.getElementById("shippingResult");
      const cep = cepInput.value.replace(/\D/g, "");
      if (cep.length !== 8) { shippingResult.innerHTML = `<div class="error">Por favor, digite um CEP válido.</div>`; return; }
      shippingResult.innerHTML = `<div class="loading"><i class="fas fa-spinner fa-spin"></i> Calculando...</div>`;
      try {
        const response = await fetch(`frete_api.php?cep=${cep}`);
        const data = await response.json();
        if (data.sucesso && data.opcoes.length > 0) {
          let html = "<ul>";
          data.opcoes.forEach((opcao) => { html += `<li><span><strong>${opcao.tipo}</strong></span><span>Prazo: ${opcao.prazo} dias</span><span>Valor: R$ ${opcao.valor}</span></li>`; });
          html += "</ul>";
          shippingResult.innerHTML = html;
        } else { shippingResult.innerHTML = `<div class="error">${data.mensagem || "Não foi possível calcular o frete."}</div>`; }
      } catch (error) { shippingResult.innerHTML = `<div class="error">Ocorreu um erro. Tente novamente.</div>`; }
    });
  }

  initializeProductDetails();
});