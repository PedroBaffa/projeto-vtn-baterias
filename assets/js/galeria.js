/**
 * @file
 * assets/js/galeria.js
 * Controla toda a lógica da página da Galeria de Imagens no painel de administração.
 * Lida com o carregamento, upload, exclusão e seleção de imagens.
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- ELEMENTOS DO DOM ---
    const galleryGrid = document.getElementById('galleryGrid');
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadProgress = document.getElementById('uploadProgress');

    // --- LÓGICA PARA APAGAR EM MASSA (MODO DE SELEÇÃO) ---
    const selectModeBtn = document.getElementById('select-mode-btn');
    const deleteSelectedBtn = document.getElementById('delete-selected-btn');
    const cancelSelectionBtn = document.getElementById('cancel-selection-btn');
    const galleryActions = document.getElementById('gallery-actions');
    const selectionActions = document.getElementById('selection-actions');
    let isSelectMode = false;

    /**
     * Alterna a interface entre o modo de visualização normal e o modo de seleção.
     */
    function toggleSelectMode() {
        isSelectMode = !isSelectMode;
        // Mostra/esconde os botões de ação apropriados para cada modo.
        galleryActions.classList.toggle('hidden', isSelectMode);
        selectionActions.classList.toggle('hidden', !isSelectMode);

        // Percorre todos os cards de imagem para ajustar seu comportamento.
        document.querySelectorAll('.image-card').forEach(card => {
            const overlay = card.querySelector('.image-overlay');
            if (overlay) {
                // Desativa os botões do overlay (copiar/apagar individual) no modo de seleção.
                overlay.style.pointerEvents = isSelectMode ? 'none' : 'auto';
            }
            // Limpa a seleção visual ao sair do modo de seleção.
            if (!isSelectMode) {
                card.classList.remove('selected');
            }
        });
    }

    if (selectModeBtn) selectModeBtn.addEventListener('click', toggleSelectMode);
    if (cancelSelectionBtn) cancelSelectionBtn.addEventListener('click', toggleSelectMode);

    // Adiciona o evento de clique aos cards para permitir a seleção múltipla.
    if (galleryGrid) {
        galleryGrid.addEventListener('click', (e) => {
            if (isSelectMode) {
                const card = e.target.closest('.image-card');
                if (card) {
                    card.classList.toggle('selected');
                }
            }
        });
    }

    // Evento para o botão "Apagar Selecionadas".
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', async () => {
            const selectedCards = document.querySelectorAll('.image-card.selected');
            if (selectedCards.length === 0) {
                alert('Nenhuma imagem selecionada.');
                return;
            }

            const imagesToDelete = Array.from(selectedCards).map(card => card.dataset.path);

            if (confirm(`Tem a certeza de que deseja apagar ${imagesToDelete.length} imagem(ns)?`)) {
                try {
                    // Envia a lista de imagens para a API de exclusão em massa.
                    const response = await fetch('acoes_imagem.php?acao=apagar_massa', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ imagens: imagesToDelete })
                    });
                    const data = await response.json();
                    if (data.success) {
                        loadImages(); // Recarrega a galeria.
                        toggleSelectMode(); // Sai do modo de seleção.
                    } else {
                        alert('Erro: ' + data.message);
                    }
                } catch (error) {
                    alert('Ocorreu um erro de rede.');
                }
            }
        });
    }


    // --- FUNÇÕES PRINCIPAIS DA GALERIA ---

    /**
     * Cria o elemento HTML (card) para uma única imagem.
     * @param {object} imageData - Os dados da imagem (path, in_use).
     * @returns {HTMLElement} O elemento do card da imagem.
     */
    function createImageCard(imageData) {
        const imageUrl = imageData.path;
        const inUse = imageData.in_use;
        const card = document.createElement('div');
        card.className = 'image-card';
        card.dataset.path = imageUrl; // Armazena o caminho no elemento para fácil acesso.

        // Adiciona um "badge" de status (Em Uso / Não Usada).
        const statusBadge = inUse
            ? '<span class="absolute top-2 right-2 text-xs bg-green-500 text-white font-semibold py-1 px-2 rounded-full">Em Uso</span>'
            : '<span class="absolute top-2 right-2 text-xs bg-red-500 text-white font-semibold py-1 px-2 rounded-full">Não Usada</span>';

        card.innerHTML = `
            <img src="../${imageUrl}" class="w-full h-32 object-cover">
            ${statusBadge}
            <div class="image-overlay flex flex-col items-center justify-center p-2">
                <div class="flex gap-2">
                    <button class="copy-path-btn text-white bg-blue-500 h-8 w-8 rounded-full" title="Copiar Caminho"><i class="fas fa-copy"></i></button>
                    <button class="delete-img-btn text-white bg-red-500 h-8 w-8 rounded-full" title="Apagar Imagem"><i class="fas fa-trash-alt"></i></button>
                </div>
                <input type="text" value="${imageUrl}" class="absolute -top-96">
            </div>
        `;

        // Adiciona os eventos de clique aos botões de ação do card.
        card.querySelector('.delete-img-btn').addEventListener('click', (e) => { e.stopPropagation(); deleteImage(imageUrl, card) });
        card.querySelector('.copy-path-btn').addEventListener('click', (e) => { e.stopPropagation(); copyImagePath(card) });

        return card;
    }

    /**
     * Carrega todas as imagens da API e as renderiza na galeria.
     */
    async function loadImages() {
        if (!galleryGrid) return;
        galleryGrid.innerHTML = '<p class="text-gray-500 col-span-full">A carregar imagens...</p>';
        try {
            const response = await fetch('acoes_imagem.php?acao=listar');
            const data = await response.json();
            galleryGrid.innerHTML = ''; // Limpa a galeria.
            if (data.success && data.images.length > 0) {
                data.images.forEach(imageData => {
                    galleryGrid.appendChild(createImageCard(imageData));
                });
            } else {
                galleryGrid.innerHTML = '<p class="text-gray-500 col-span-full">Nenhuma imagem encontrada.</p>';
            }
        } catch (error) {
            galleryGrid.innerHTML = '<p class="text-red-500 col-span-full">Erro ao carregar imagens.</p>';
        }
    }

    /**
     * Apaga uma única imagem.
     * @param {string} imageUrl - O caminho da imagem a ser apagada.
     * @param {HTMLElement} cardElement - O elemento do card a ser removido da tela.
     */
    async function deleteImage(imageUrl, cardElement) {
        if (!confirm('Tem a certeza de que deseja apagar esta imagem? Esta ação não pode ser desfeita.')) return;

        try {
            const response = await fetch('acoes_imagem.php?acao=apagar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ imagem: imageUrl })
            });
            const data = await response.json();
            if (data.success) {
                cardElement.remove(); // Remove o card da tela se a exclusão for bem-sucedida.
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (error) {
            alert('Ocorreu um erro de rede.');
        }
    }

    /**
     * Copia o caminho da imagem para a área de transferência.
     * @param {HTMLElement} cardElement - O elemento do card que contém o caminho.
     */
    function copyImagePath(cardElement) {
        const pathInput = cardElement.querySelector('input[type="text"]');
        navigator.clipboard.writeText(pathInput.value).then(() => {
            alert('Caminho copiado: ' + pathInput.value);
        }).catch(err => {
            console.error('Falha ao copiar:', err);
        });
    }

    /**
     * Faz o upload de arquivos para o servidor.
     * @param {FileList} files - A lista de arquivos do input ou drag-and-drop.
     */
    async function uploadFiles(files) {
        const formData = new FormData();
        for (const file of files) {
            formData.append('images[]', file);
        }

        if (uploadProgress) uploadProgress.innerHTML = '<p class="text-blue-600">A enviar...</p>';
        try {
            const response = await fetch('acoes_imagem.php?acao=upload', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (uploadProgress) {
                if (data.success) {
                    uploadProgress.innerHTML = `<p class="text-green-600">${data.message}</p>`;
                    loadImages(); // Recarrega a galeria para mostrar as novas imagens.
                } else {
                    uploadProgress.innerHTML = `<p class="text-red-600">${data.message}</p>`;
                }
            }
        } catch (error) {
            if (uploadProgress) uploadProgress.innerHTML = '<p class="text-red-600">Ocorreu um erro no envio.</p>';
        }
    }

    // --- EVENT LISTENERS PARA UPLOAD ---
    if (uploadArea) {
        // Ativa o input de arquivo ao clicar na área de upload.
        uploadArea.addEventListener('click', () => fileInput.click());
        // Inicia o upload quando arquivos são selecionados.
        fileInput.addEventListener('change', () => uploadFiles(fileInput.files));
        // Previne o comportamento padrão do navegador para eventos de drag-and-drop.
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
        });
        // Adiciona um feedback visual quando um arquivo é arrastado sobre a área.
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.add('bg-blue-50'), false);
        });
        // Remove o feedback visual.
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('bg-blue-50'), false);
        });
        // Inicia o upload quando os arquivos são soltos na área.
        uploadArea.addEventListener('drop', e => uploadFiles(e.dataTransfer.files), false);
    }

    // --- CHAMADA INICIAL ---
    loadImages();
});