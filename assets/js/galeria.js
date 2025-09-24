document.addEventListener('DOMContentLoaded', function () {
    const galleryContainer = document.getElementById('product-gallery-container');
    const galleryModal = document.getElementById('gallery-modal');
    const closeGalleryBtn = document.getElementById('close-gallery-btn');
    const addSelectedImagesBtn = document.getElementById('add-selected-images-btn');
    let currentProductId = null;

    // Função para marcar uma galeria como "precisa de salvar"
    function markAsNeedsSaving(productId) {
        const saveBtn = document.querySelector(`#grid-${productId} + .mt-6 .save-btn`);
        if (saveBtn && !saveBtn.classList.contains('needs-saving')) {
            saveBtn.classList.add('needs-saving');
            saveBtn.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> Salvar Alterações`;
        }
    }

    async function loadProductGallery(productCard) {
        const contentDiv = productCard.querySelector('.gallery-content');
        const sku = productCard.dataset.sku;
        const productId = productCard.dataset.productId;

        try {
            const response = await fetch(`../api.php?sku=${sku}`);
            const product = await response.json();
            let imagesArray = (product && product.images) ? product.images : [];
            buildGalleryContent(contentDiv, productId, imagesArray);
        } catch (error) {
            contentDiv.innerHTML = `<p class="p-4 text-center text-red-500">Erro ao carregar a galeria.</p>`;
        }
    }

    function buildGalleryContent(contentElement, productId, imagesArray) {
        let imagesHtml = '';
        imagesArray.forEach(img => {
            imagesHtml += `<div class="image-preview-item" data-id="${img.id}" data-path="${img.image_path}"><img src="../${img.image_path}"><div class="delete-btn" onclick="removeImage(this, ${productId})">&times;</div></div>`;
        });
        contentElement.innerHTML = `
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4 image-grid-sortable" id="grid-${productId}">
                <div class="drop-area flex items-center justify-center text-center text-gray-500 cursor-pointer non-sortable"><div><i class="fas fa-upload text-2xl"></i><p class="text-xs mt-1">Carregar</p></div></div>
                <div class="gallery-uploader flex items-center justify-center text-center text-gray-500 cursor-pointer non-sortable" onclick="openGalleryModal(${productId})"><div><i class="fas fa-images text-2xl"></i><p class="text-xs mt-1">Da Galeria</p></div></div>
                ${imagesHtml}
            </div>
            <div class="mt-6 flex justify-end gap-4">
                <a href="forms/form_produto.php?acao=editar&id=${productId}" target="_blank" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg">Editar Detalhes</a>
                <button class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg save-btn" onclick="saveProductGallery(${productId})"><i class="fas fa-save mr-2"></i>Salvar Ordem</button>
            </div>`;
        setupSortableAndDrop(productId);
    }

    function setupSortableAndDrop(productId) {
        const grid = document.getElementById(`grid-${productId}`);
        const sortable = new Sortable(grid, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            filter: '.non-sortable',
            preventOnFilter: false,
            onEnd: () => markAsNeedsSaving(productId)
        });
        const dropArea = grid.querySelector('.drop-area');
        dropArea.addEventListener('click', () => {
            const fileInput = document.createElement('input');
            fileInput.type = 'file'; fileInput.multiple = true;
            fileInput.onchange = () => handleFileUpload(productId, fileInput.files);
            fileInput.click();
        });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eName => dropArea.addEventListener(eName, e => { e.preventDefault(); e.stopPropagation(); }));
        ['dragenter', 'dragover'].forEach(eName => dropArea.addEventListener(eName, () => dropArea.classList.add('dragover')));
        ['dragleave', 'drop'].forEach(eName => dropArea.addEventListener(eName, () => dropArea.classList.remove('dragover')));
        dropArea.addEventListener('drop', e => handleFileUpload(productId, e.dataTransfer.files));
    }

    function handleFileUpload(productId, files) {
        const grid = document.getElementById(`grid-${productId}`);
        for (const file of files) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    const newImageElem = createPreviewElement(e.target.result, 'new-upload', file);
                    grid.appendChild(newImageElem);
                };
                reader.readAsDataURL(file);
            }
        }
        markAsNeedsSaving(productId);
    }

    window.openGalleryModal = (productId) => {
        currentProductId = productId;
        galleryModal.classList.remove('hidden');
    };
    if (closeGalleryBtn) closeGalleryBtn.addEventListener('click', () => galleryModal.classList.add('hidden'));

    window.toggleImageSelection = (element) => {
        element.classList.toggle('selected');
        element.querySelector('.check-icon').classList.toggle('hidden');
    };

    if (addSelectedImagesBtn) {
        addSelectedImagesBtn.addEventListener('click', () => {
            const grid = document.getElementById(`grid-${currentProductId}`);
            document.querySelectorAll('.gallery-image-item.selected').forEach(selectedImg => {
                const path = selectedImg.dataset.path;
                if (!grid.querySelector(`[data-path='${path}']`)) {
                    const newImageElem = createPreviewElement(`../${path}`, `url:${path}`, null);
                    grid.appendChild(newImageElem);
                }
                selectedImg.classList.remove('selected');
                selectedImg.querySelector('.check-icon').classList.add('hidden');
            });
            galleryModal.classList.add('hidden');
            markAsNeedsSaving(currentProductId);
        });
    }

    function createPreviewElement(src, id, fileObject = null) {
        const item = document.createElement('div');
        item.className = 'image-preview-item';
        item.dataset.id = id;
        if (fileObject) item.fileObject = fileObject;
        if (id.startsWith('url:')) item.dataset.path = id.substring(4);
        item.innerHTML = `<img src="${src}"><div class="delete-btn" onclick="removeImage(this, currentProductId)">&times;</div>`;
        return item;
    }

    window.removeImage = (btn, productId) => {
        btn.parentElement.remove();
        markAsNeedsSaving(productId);
    };

    window.saveProductGallery = async (productId) => {
        const productCard = document.querySelector(`.product-gallery-card[data-product-id='${productId}']`);
        const saveBtn = productCard.querySelector('.save-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>A Guardar...`;

        const grid = document.getElementById(`grid-${productId}`);
        const items = grid.querySelectorAll('.image-preview-item');
        const formData = new FormData();
        formData.append('acao', 'salvar_galeria_produto');
        formData.append('produto_id', productId);

        const ordem = Array.from(items).map(item => item.dataset.id);
        const imagensRemovidas = [];
        const sku = productCard.dataset.sku;

        const responseOriginal = await fetch(`../api.php?sku=${sku}`);
        const productOriginal = await responseOriginal.json();
        const originalImageIds = new Set((productOriginal.images || []).map(img => img.id.toString()));

        const imagensAtuais = new Set(Array.from(items).map(item => item.dataset.id).filter(id => !id.startsWith('new') && !id.startsWith('url:')));
        originalImageIds.forEach(id => { if (!imagensAtuais.has(id)) imagensRemovidas.push(id); });

        items.forEach(item => { if (item.dataset.id === 'new-upload') formData.append('novas_imagens[]', item.fileObject); });

        formData.append('ordem_imagens', JSON.stringify(ordem));
        formData.append('imagens_removidas', JSON.stringify(imagensRemovidas));

        try {
            const response = await fetch('actions/acoes_produto.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                saveBtn.classList.remove('needs-saving');
                saveBtn.classList.add('bg-green-600');
                saveBtn.innerHTML = `<i class="fas fa-check mr-2"></i> Salvo!`;
                await loadProductGallery(productCard);
            } else {
                alert('Erro ao salvar: ' + result.message);
                saveBtn.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> Falha ao Salvar`;
            }
        } catch (e) {
            alert('Erro de comunicação ao salvar.');
        } finally {
            setTimeout(() => {
                saveBtn.disabled = false;
                if (!saveBtn.classList.contains('needs-saving')) {
                    saveBtn.innerHTML = `<i class="fas fa-save mr-2"></i>Salvar Ordem`;
                }
            }, 2000);
        }
    };

    // Inicializa as galerias para os produtos que já estão na página
    document.querySelectorAll('.product-gallery-card').forEach(card => {
        loadProductGallery(card);
    });
});