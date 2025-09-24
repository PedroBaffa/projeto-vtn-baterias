<?php
// Arquivo: admin/grupos_imagens.php (Com modal de confirmação personalizado)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';
try {
    $stmt = $conn->query("
        SELECT 
            g.id, g.nome,
            (SELECT GROUP_CONCAT(CONCAT(i.id, '::', i.image_path) SEPARATOR '||') 
             FROM galeria_imagens i 
             WHERE i.grupo_id = g.id 
             ORDER BY i.ordem ASC) as images_data
        FROM galeria_grupos g
        ORDER BY g.data_criacao DESC
    ");
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar os grupos de imagens: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Grupos de Imagens - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 50;
        }

        .image-preview-item {
            position: relative;
            cursor: grab;
        }

        .image-preview-item:active {
            cursor: grabbing;
        }

        .delete-img-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 10;
        }

        .image-preview-item:hover .delete-img-btn {
            opacity: 1;
        }

        .drop-area {
            border: 2px dashed #cbd5e1;
        }

        .drop-area.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .sortable-ghost {
            opacity: 0.4;
            background: #cce7ff;
        }

        .group-container.hidden {
            display: none;
        }

        #confirmation-modal {
            transition: opacity 0.3s ease;
        }

        #confirmation-modal-content {
            transition: transform 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">

        <?php require_once 'templates/sidebar.php'; ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Grupos de Imagens</h1>
                <div class="flex items-center"><span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span><a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a></div>
            </header>

            <main class="flex-1 p-6">
                <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                    <div class="relative flex-grow max-w-xs">
                        <input type="text" id="search-groups-input" placeholder="Pesquisar por nome do grupo..." class="w-full pl-10 pr-4 py-2 border rounded-lg">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                    </div>
                    <button onclick="openCreateGroupModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"><i class="fas fa-plus mr-2"></i>Criar grupo de fotos</button>
                </div>

                <div id="groups-container" class="space-y-6">
                    <?php if (empty($grupos)): ?>
                        <div class="text-center py-10 bg-white rounded-lg shadow-md">
                            <i class="fas fa-images text-4xl text-gray-300"></i>
                            <p class="mt-4 text-gray-500">Nenhum grupo de imagens criado ainda.</p>
                            <p class="text-sm text-gray-400">Clique em "Criar grupo de fotos" para começar.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($grupos as $grupo):
                            $images_data = !empty($grupo['images_data']) ? explode('||', $grupo['images_data']) : [];
                            $image_count = count($images_data);
                        ?>
                            <div class="bg-white rounded-lg shadow-md group-container" data-group-id="<?php echo $grupo['id']; ?>" data-group-name="<?php echo htmlspecialchars(strtolower($grupo['nome'])); ?>">
                                <div class="flex items-center p-4 border-b border-gray-200 flex-wrap gap-4">
                                    <div class="flex-grow flex items-center gap-4 min-w-max">
                                        <input type="text" value="<?php echo htmlspecialchars($grupo['nome']); ?>" class="text-lg font-semibold border-b-2 border-transparent focus:border-blue-500 outline-none p-1 group-name-input" onchange="renameGroup(<?php echo $grupo['id']; ?>, this.value)">
                                        <p class="text-sm text-gray-500" data-counter><span id="count-<?php echo $grupo['id']; ?>"><?php echo $image_count; ?></span> URLs prontas para copiar</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <button onclick="saveOrder(<?php echo $grupo['id']; ?>)" id="save-order-btn-<?php echo $grupo['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg hidden"><i class="fas fa-save mr-2"></i>Salvar Ordem</button>
                                        <button onclick="copyGroupUrls(<?php echo $grupo['id']; ?>)" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg">Copiar URLs</button>
                                        <button onclick="deleteGroup(<?php echo $grupo['id']; ?>)" class="text-red-500 hover:text-red-700 text-lg"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4 image-grid-sortable" id="image-grid-<?php echo $grupo['id']; ?>">
                                        <div class="drop-area w-full h-32 rounded-lg flex items-center justify-center text-center text-gray-500 cursor-pointer" id="drop-area-<?php echo $grupo['id']; ?>" onclick="document.getElementById('file-input-<?php echo $grupo['id']; ?>').click();">
                                            <div><i class="fas fa-upload text-2xl"></i>
                                                <p class="text-xs mt-1">Adicionar</p>
                                            </div>
                                        </div>
                                        <input type="file" id="file-input-<?php echo $grupo['id']; ?>" multiple class="hidden" onchange="handleFileUpload(<?php echo $grupo['id']; ?>, this.files)">
                                        <?php foreach ($images_data as $img_data): list($img_id, $img_path) = explode('::', $img_data); ?>
                                            <div class="image-preview-item h-32" data-image-id="<?php echo $img_id; ?>">
                                                <img src="../<?php echo htmlspecialchars($img_path); ?>" class="w-full h-full object-cover rounded-lg">
                                                <div class="delete-img-btn" onclick="deleteImage(<?php echo $img_id; ?>, this)">&times;</div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="createGroupModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Criar Novo Grupo de Fotos</h3>
            <div>
                <label for="groupNameInput" class="block text-sm font-medium text-gray-700">Nome do Grupo</label>
                <input type="text" id="groupNameInput" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div id="modal-error" class="text-red-500 text-sm mt-2 hidden"></div>
            <div class="flex justify-end gap-4 mt-6">
                <button onclick="closeCreateGroupModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancelar</button>
                <button onclick="submitNewGroup()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Criar Grupo</button>
            </div>
        </div>
    </div>

    <div id="confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0">
        <div id="confirmation-modal-content" class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm transform scale-95">
            <h3 id="modal-title" class="text-lg font-bold text-gray-800 text-center">Confirmar Ação</h3>
            <p id="modal-message" class="text-center text-gray-600 my-4">Tem a certeza de que deseja executar esta ação?</p>
            <div class="flex items-center justify-start mt-4">
                <input type="checkbox" id="modal-dont-show-again" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="modal-dont-show-again" class="ml-2 block text-sm text-gray-900">Não perguntar novamente nesta sessão</label>
            </div>
            <div class="flex justify-end gap-4 mt-6">
                <button id="modal-cancel-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancelar</button>
                <button id="modal-confirm-btn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        const createGroupModal = document.getElementById('createGroupModal');
        const groupNameInput = document.getElementById('groupNameInput');
        const modalError = document.getElementById('modal-error');

        const confirmationModal = document.getElementById('confirmation-modal');
        const confirmationModalContent = document.getElementById('confirmation-modal-content');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalConfirmBtn = document.getElementById('modal-confirm-btn');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        const modalDontShowAgain = document.getElementById('modal-dont-show-again');
        let confirmCallback = null;

        function showConfirmationModal(config) {
            const {
                title,
                message,
                confirmText = 'Confirmar',
                onConfirm,
                preferenceKey
            } = config;

            if (sessionStorage.getItem(preferenceKey)) {
                onConfirm();
                return;
            }

            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modalConfirmBtn.textContent = confirmText;
            confirmCallback = onConfirm;

            modalDontShowAgain.checked = false;
            modalDontShowAgain.dataset.key = preferenceKey;

            confirmationModal.classList.remove('hidden');
            setTimeout(() => {
                confirmationModal.classList.remove('opacity-0');
                confirmationModalContent.classList.remove('scale-95');
            }, 10);
        }

        function hideConfirmationModal() {
            confirmationModal.classList.add('opacity-0');
            confirmationModalContent.classList.add('scale-95');
            setTimeout(() => confirmationModal.classList.add('hidden'), 300);
        }

        modalConfirmBtn.addEventListener('click', () => {
            if (modalDontShowAgain.checked) {
                sessionStorage.setItem(modalDontShowAgain.dataset.key, 'true');
            }
            if (typeof confirmCallback === 'function') {
                confirmCallback();
            }
            hideConfirmationModal();
        });

        modalCancelBtn.addEventListener('click', hideConfirmationModal);

        function openCreateGroupModal() {
            groupNameInput.value = '';
            modalError.classList.add('hidden');
            createGroupModal.classList.remove('hidden');
            groupNameInput.focus();
        }

        function closeCreateGroupModal() {
            createGroupModal.classList.add('hidden');
        }

        async function apiCall(formData) {
            try {
                const response = await fetch('actions/acoes_grupos_imagens.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                alert('Ocorreu um erro de rede.');
                return {
                    success: false
                };
            }
        }

        async function submitNewGroup() {
            const nome = groupNameInput.value.trim();
            if (!nome) {
                modalError.textContent = 'O nome do grupo é obrigatório.';
                modalError.classList.remove('hidden');
                return;
            }
            modalError.classList.add('hidden');
            const formData = new FormData();
            formData.append('acao', 'criar_grupo');
            formData.append('nome', nome);
            const result = await apiCall(formData);
            if (result.success) {
                closeCreateGroupModal();
                location.reload();
            } else {
                modalError.textContent = 'Erro: ' + result.message;
                modalError.classList.remove('hidden');
            }
        }

        async function renameGroup(groupId, newName) {
            const formData = new FormData();
            formData.append('acao', 'renomear_grupo');
            formData.append('id', groupId);
            formData.append('nome', newName);
            await apiCall(formData);
            const groupContainer = document.querySelector(`[data-group-id='${groupId}']`);
            if (groupContainer) {
                groupContainer.dataset.groupName = newName.toLowerCase();
            }
        }

        async function _executeDeleteGroup(groupId) {
            const formData = new FormData();
            formData.append('acao', 'apagar_grupo');
            formData.append('id', groupId);
            const result = await apiCall(formData);
            if (result.success) {
                document.querySelector(`[data-group-id='${groupId}']`).remove();
            } else {
                alert('Erro: ' + result.message);
            }
        }

        function deleteGroup(groupId) {
            showConfirmationModal({
                title: 'Apagar Grupo',
                message: 'Tem a certeza de que deseja apagar este grupo e todas as suas imagens? Esta ação não pode ser desfeita.',
                confirmText: 'Sim, Apagar',
                onConfirm: () => _executeDeleteGroup(groupId),
                preferenceKey: 'confirmDeleteGroup'
            });
        }

        async function copyGroupUrls(groupId) {
            const formData = new FormData();
            formData.append('acao', 'obter_urls');
            formData.append('id', groupId);
            const result = await apiCall(formData);
            if (result.success && result.urls && result.urls.trim() !== '') {
                navigator.clipboard.writeText(result.urls).then(() => {
                    alert('URLs copiadas para a área de transferência!');
                });
            } else {
                alert('Este grupo não possui imagens para copiar.');
            }
        }

        function setupDragAndDrop(groupId) {
            const dropArea = document.getElementById(`drop-area-${groupId}`);
            dropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.classList.add('dragover');
            });
            dropArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.classList.remove('dragover');
            });
            dropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.classList.remove('dragover');
                handleFileUpload(groupId, e.dataTransfer.files);
            });
        }

        async function handleFileUpload(groupId, files) {
            const formData = new FormData();
            formData.append('acao', 'upload_imagens');
            formData.append('grupo_id', groupId);
            for (const file of files) {
                formData.append('novas_imagens[]', file);
            }

            const result = await apiCall(formData);
            if (result.success && result.images) {
                const imageGrid = document.getElementById(`image-grid-${groupId}`);
                result.images.forEach(img => {
                    const newImageElem = document.createElement('div');
                    newImageElem.className = 'image-preview-item h-32';
                    newImageElem.dataset.imageId = img.id;
                    newImageElem.innerHTML = `<img src="../${img.path}" class="w-full h-full object-cover rounded-lg"><div class="delete-img-btn" onclick="deleteImage(${img.id}, this)">&times;</div>`;
                    imageGrid.appendChild(newImageElem);
                });
                updateCounter(groupId);
            } else {
                alert(result.message || 'Falha no upload.');
            }
        }

        async function _executeDeleteImage(imageId, element) {
            const formData = new FormData();
            formData.append('acao', 'apagar_imagem_grupo');
            formData.append('image_id', imageId);
            const result = await apiCall(formData);
            if (result.success) {
                const groupId = element.closest('[data-group-id]').dataset.groupId;
                element.parentElement.remove();
                updateCounter(groupId);
            } else {
                alert(result.message || 'Não foi possível apagar a imagem.');
            }
        }

        function deleteImage(imageId, element) {
            showConfirmationModal({
                title: 'Apagar Imagem',
                message: 'Tem a certeza de que deseja apagar esta imagem?',
                confirmText: 'Sim, Apagar',
                onConfirm: () => _executeDeleteImage(imageId, element),
                preferenceKey: 'confirmDeleteImage'
            });
        }

        function updateCounter(groupId) {
            const imageGrid = document.getElementById(`image-grid-${groupId}`);
            const count = imageGrid.querySelectorAll('.image-preview-item').length;
            document.getElementById(`count-${groupId}`).textContent = count;
        }

        document.querySelectorAll('[id^=drop-area-]').forEach(area => {
            const groupId = area.id.split('-')[2];
            setupDragAndDrop(groupId);
        });

        document.querySelectorAll('.image-grid-sortable').forEach(grid => {
            new Sortable(grid, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                filter: '.drop-area',
                preventOnFilter: false,
                onStart: function(evt) {
                    const groupId = evt.target.closest('.group-container').dataset.groupId;
                    const saveBtn = document.getElementById(`save-order-btn-${groupId}`);
                    if (saveBtn) saveBtn.classList.remove('hidden');
                },
            });
        });

        async function saveOrder(groupId) {
            const grid = document.getElementById(`image-grid-${groupId}`);
            const items = grid.querySelectorAll('.image-preview-item');
            const order = Array.from(items).map(item => item.dataset.imageId);
            const formData = new FormData();
            formData.append('acao', 'salvar_ordem');
            formData.append('ordem', JSON.stringify(order));

            const saveBtn = document.getElementById(`save-order-btn-${groupId}`);
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            saveBtn.disabled = true;

            const result = await apiCall(formData);

            if (result.success) {
                saveBtn.innerHTML = '<i class="fas fa-check"></i> Salvo!';
                setTimeout(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.classList.add('hidden');
                    saveBtn.disabled = false;
                }, 2000);
            } else {
                alert('Erro ao salvar a ordem: ' + result.message);
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        }

        const searchInput = document.getElementById('search-groups-input');
        const groupsContainer = document.getElementById('groups-container');
        const allGroups = groupsContainer.querySelectorAll('.group-container');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            allGroups.forEach(group => {
                const groupName = group.dataset.groupName;
                const nameInput = group.querySelector('.group-name-input');
                const currentName = nameInput ? nameInput.value.toLowerCase() : groupName;
                if (currentName.includes(searchTerm)) {
                    group.classList.remove('hidden');
                } else {
                    group.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>