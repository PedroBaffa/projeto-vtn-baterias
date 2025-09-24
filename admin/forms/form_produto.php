<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once '../config.php';

$modo_edicao = (isset($_GET['acao']) && $_GET['acao'] == 'editar' && isset($_GET['id']));
$produto_existente = [
    'id' => '',
    'title' => '',
    'sku' => '',
    'brand' => '',
    'price' => '0.00',
    'capacity' => '',
    'condicao' => 'novo',
    'descricao' => ''
];
$imagens_existentes = [];
$is_in_active_promo = false;
$active_promo_name = '';

if ($modo_edicao) {
    $id_produto = (int)$_GET['id'];
    try {
        $stmt_produto = $conn->prepare("SELECT * FROM produtos WHERE id = :id");
        $stmt_produto->execute([':id' => $id_produto]);
        $produto_existente = $stmt_produto->fetch(PDO::FETCH_ASSOC);

        if (!$produto_existente) {
            header("Location: ../dashboard.php?err=Produto não encontrado.");
            exit();
        }

        $stmt_promo = $conn->prepare("
            SELECT pr.nome FROM produto_promocao pp 
            JOIN promocoes pr ON pp.promocao_id = pr.id 
            WHERE pp.produto_id = :produto_id AND pr.is_active = 1 AND NOW() BETWEEN pr.data_inicio AND pr.data_fim
            LIMIT 1
        ");
        $stmt_promo->execute([':produto_id' => $id_produto]);
        $promo_name_result = $stmt_promo->fetchColumn();
        if ($promo_name_result) {
            $is_in_active_promo = true;
            $active_promo_name = $promo_name_result;
        }

        $stmt_imagens = $conn->prepare("SELECT id, image_path FROM produto_imagens WHERE produto_id = :produto_id ORDER BY ordem ASC");
        $stmt_imagens->execute([':produto_id' => $id_produto]);
        $imagens_existentes = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erro ao buscar dados do produto: " . $e->getMessage());
    }
}

// Busca todos os grupos de imagens para o modal
try {
    $stmt_grupos = $conn->query("
        SELECT g.id, g.nome, i.image_path 
        FROM galeria_grupos g 
        JOIN galeria_imagens i ON g.id = i.grupo_id 
        ORDER BY g.nome ASC, i.ordem ASC
    ");
    $imagens_dos_grupos_raw = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);
    $grupos_de_imagens = [];
    foreach ($imagens_dos_grupos_raw as $imagem) {
        $grupos_de_imagens[$imagem['nome']][] = $imagem['image_path'];
    }
} catch (PDOException $e) {
    $grupos_de_imagens = [];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo $modo_edicao ? 'Editar' : 'Adicionar'; ?> Produto - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .image-uploader,
        .gallery-uploader {
            border: 2px dashed #cbd5e1;
            border-radius: 0.5rem;
            transition: background-color 0.2s ease-in-out;
        }

        .image-uploader:hover,
        .gallery-uploader:hover {
            background-color: #f7fafc;
        }

        .image-preview-item {
            position: relative;
            cursor: grab;
        }

        .delete-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            z-index: 10;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.6);
        }

        .gallery-image-item:hover .image-overlay {
            opacity: 1;
        }

        .image-overlay {
            opacity: 0;
            transition: opacity 0.3s;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">

        <?php require_once '../templates/sidebar.php'; ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700"><?php echo $modo_edicao ? 'Editar' : 'Adicionar Novo'; ?> Produto</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="../logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8">
                    <form action="../actions/acoes_produto.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="<?php echo $modo_edicao ? 'editar' : 'adicionar'; ?>">
                        <?php if ($modo_edicao): ?>
                            <input type="hidden" name="id" value="<?php echo $produto_existente['id']; ?>">
                        <?php endif; ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div><label for="title" class="block text-gray-600 font-medium mb-2">Título do Produto</label><input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($produto_existente['title']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                                <div><label for="sku" class="block text-gray-600 font-medium mb-2">SKU</label><input type="text" id="sku" name="sku" required value="<?php echo htmlspecialchars($produto_existente['sku']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                                <div><label for="brand" class="block text-gray-600 font-medium mb-2">Marca</label><select id="brand" name="brand" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"><?php $marcas = ['samsung', 'apple', 'xiaomi', 'lg', 'motorola', 'huawei', 'asus', 'lenovo', 'nokia', 'positivo', 'multilaser', 'philco', 'infinix', 'realme'];
                                                                                                                                                                                                                        foreach ($marcas as $marca): ?><option value="<?php echo $marca; ?>" <?php if ($produto_existente['brand'] == $marca) echo 'selected'; ?>><?php echo ucfirst($marca); ?></option><?php endforeach; ?></select></div>
                                <div><label for="capacity" class="block text-gray-600 font-medium mb-2">Capacidade (mAh)</label><input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($produto_existente['capacity']); ?>" placeholder="Ex: 5000" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                                <div><label for="condicao" class="block text-gray-600 font-medium mb-2">Condição do Produto</label><select id="condicao" name="condicao" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="novo" <?php if (($produto_existente['condicao'] ?? 'novo') == 'novo') echo 'selected'; ?>>Novo</option>
                                        <option value="retirado" <?php if (($produto_existente['condicao'] ?? '') == 'retirado') echo 'selected'; ?>>Retirado</option>
                                    </select></div>
                                <div><label for="descricao" class="block text-gray-600 font-medium mb-2">Descrição do Produto</label><textarea id="descricao" name="descricao" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Digite os detalhes do produto aqui..."><?php echo htmlspecialchars($produto_existente['descricao'] ?? ''); ?></textarea></div>
                                <div>
                                    <label for="price" class="block text-gray-600 font-medium mb-2">Preço (ex: 124,90)</label>
                                    <input type="text" id="price" name="price" required value="<?php echo htmlspecialchars(number_format($produto_existente['price'], 2, ',', '.')); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg <?php echo $is_in_active_promo ? 'bg-gray-200 cursor-not-allowed' : ''; ?>" <?php echo $is_in_active_promo ? 'disabled' : ''; ?>>
                                    <?php if ($is_in_active_promo): ?>
                                        <div class="mt-2 p-3 bg-yellow-100 text-yellow-800 border-l-4 border-yellow-500 rounded-md text-sm">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            Este produto está na promoção "<?php echo htmlspecialchars($active_promo_name); ?>". Para editar, remova-o na <a href="../promocoes.php" class="font-bold underline">página de promoções</a>.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-600 font-medium mb-2">Imagens do Produto (arraste para reordenar)</label>
                                    <div id="image-preview-container" class="grid grid-cols-3 sm:grid-cols-4 gap-4 mb-4 min-h-[8rem] bg-gray-50 p-2 rounded-lg">
                                        <?php foreach ($imagens_existentes as $img): ?>
                                            <div class="image-preview-item" draggable="true" data-id="<?php echo $img['id']; ?>" data-path="<?php echo htmlspecialchars($img['image_path']); ?>">
                                                <img src="../../<?php echo htmlspecialchars($img['image_path']); ?>" class="h-24 w-24 object-cover rounded-lg">
                                                <div class="delete-btn font-bold" onclick="removeImage(this)">&times;</div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div id="drop-area" class="image-uploader flex flex-col items-center justify-center p-4 text-center cursor-pointer">
                                            <i class="fas fa-upload text-3xl text-gray-400"></i>
                                            <p class="text-gray-500 mt-2 text-sm">Carregar do Computador</p>
                                        </div>
                                        <div id="open-gallery-btn" class="gallery-uploader flex flex-col items-center justify-center p-4 text-center cursor-pointer">
                                            <i class="fas fa-images text-3xl text-gray-400"></i>
                                            <p class="text-gray-500 mt-2 text-sm">Adicionar da Galeria</p>
                                        </div>
                                    </div>
                                    <input type="file" id="image-input" name="novas_imagens[]" multiple accept="image/*" class="hidden">
                                    <input type="hidden" name="imagens_removidas" id="imagens-removidas-input" value="">
                                    <input type="hidden" name="ordem_imagens" id="ordem-imagens-input" value="">
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end">
                            <a href="../dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg mr-4">Cancelar</a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">Salvar Produto</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div id="gallery-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-5/6 flex flex-col">
            <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white">
                <h3 class="text-lg font-bold text-gray-800">Selecionar Imagens da Galeria</h3>
                <input type="text" id="gallery-search" placeholder="Pesquisar por nome do grupo..." class="px-3 py-1 border rounded-md w-1/3">
                <button id="close-gallery-btn" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="p-4 flex-grow overflow-y-auto">
                <?php if (empty($grupos_de_imagens)): ?>
                    <p class="text-center text-gray-500">Nenhum grupo de imagens encontrado na galeria.</p>
                <?php else: ?>
                    <?php foreach ($grupos_de_imagens as $nome_grupo => $imagens): ?>
                        <div class="gallery-group mb-6" data-group-name="<?php echo htmlspecialchars(strtolower($nome_grupo)); ?>">
                            <h4 class="font-bold text-gray-700 mb-2 border-b pb-1"><?php echo htmlspecialchars($nome_grupo); ?></h4>
                            <div class="grid grid-cols-5 sm:grid-cols-8 gap-2">
                                <?php foreach ($imagens as $img_path): ?>
                                    <div class="gallery-image-item relative cursor-pointer" onclick="toggleImageSelection(this)" data-path="<?php echo htmlspecialchars($img_path); ?>">
                                        <img src="../../<?php echo htmlspecialchars($img_path); ?>" class="w-full h-24 object-cover rounded-md">
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center image-overlay">
                                            <i class="fas fa-plus text-white text-2xl"></i>
                                        </div>
                                        <div class="absolute top-1 right-1 h-5 w-5 bg-white border-2 border-blue-500 rounded-sm flex items-center justify-center check-icon hidden">
                                            <i class="fas fa-check text-blue-500 text-xs"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-4 border-t flex justify-end sticky bottom-0 bg-white">
                <button id="add-selected-images-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Adicionar Selecionadas</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropArea = document.getElementById('drop-area');
            const imageInput = document.getElementById('image-input');
            const previewContainer = document.getElementById('image-preview-container');
            const imagensRemovidasInput = document.getElementById('imagens-removidas-input');
            const ordemImagensInput = document.getElementById('ordem-imagens-input');
            let imagensRemovidas = [];
            let draggedItem = null;

            dropArea.addEventListener('click', () => imageInput.click());
            imageInput.addEventListener('change', () => handleFiles(imageInput.files));
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            dropArea.addEventListener('dragover', () => dropArea.classList.add('bg-blue-50'));
            dropArea.addEventListener('dragleave', () => dropArea.classList.remove('bg-blue-50'));
            dropArea.addEventListener('drop', e => {
                dropArea.classList.remove('bg-blue-50');
                handleFiles(e.dataTransfer.files);
            });

            function handleFiles(files) {
                const dataTransfer = new DataTransfer();
                Array.from(imageInput.files).forEach(file => dataTransfer.items.add(file));
                Array.from(files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        dataTransfer.items.add(file);
                        const reader = new FileReader();
                        reader.onload = e => {
                            const newImage = createPreviewElement(e.target.result, 'new', e.target.result);
                            previewContainer.appendChild(newImage);
                            updateImageOrder();
                        };
                        reader.readAsDataURL(file);
                    }
                });
                imageInput.files = dataTransfer.files;
            }

            function createPreviewElement(src, id, path) {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.draggable = true;
                item.dataset.id = id;
                item.dataset.path = path;
                const img = document.createElement('img');
                img.src = src;
                img.className = 'h-24 w-24 object-cover rounded-lg';
                const deleteBtn = document.createElement('div');
                deleteBtn.className = 'delete-btn font-bold';
                deleteBtn.innerHTML = '&times;';
                deleteBtn.onclick = () => removeImage(deleteBtn);
                item.appendChild(img);
                item.appendChild(deleteBtn);
                addDragEvents(item);
                return item;
            }

            window.removeImage = function(buttonElement) {
                const imageItem = buttonElement.parentElement;
                const id = imageItem.dataset.id;
                if (id && id !== 'new' && !id.startsWith('url:') && !imagensRemovidas.includes(id)) {
                    imagensRemovidas.push(id);
                    imagensRemovidasInput.value = JSON.stringify(imagensRemovidas);
                }
                imageItem.remove();
                updateImageOrder();
            }

            function addDragEvents(item) {
                item.addEventListener('dragstart', (e) => {
                    draggedItem = item;
                    setTimeout(() => item.style.opacity = '0.5', 0);
                });
                item.addEventListener('dragend', () => {
                    setTimeout(() => {
                        if (draggedItem) draggedItem.style.opacity = '1';
                        draggedItem = null;
                        updateImageOrder();
                    }, 0);
                });
            }

            previewContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = getDragAfterElement(previewContainer, e.clientX);
                if (draggedItem) {
                    if (afterElement == null) {
                        previewContainer.appendChild(draggedItem);
                    } else {
                        previewContainer.insertBefore(draggedItem, afterElement);
                    }
                }
            });

            function getDragAfterElement(container, x) {
                const draggableElements = [...container.querySelectorAll('.image-preview-item:not([style*="opacity: 0.5"])')];
                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = x - box.left - box.width / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return {
                            offset: offset,
                            element: child
                        };
                    } else {
                        return closest;
                    }
                }, {
                    offset: Number.NEGATIVE_INFINITY
                }).element;
            }

            function updateImageOrder() {
                const items = previewContainer.querySelectorAll('.image-preview-item');
                const order = Array.from(items).map(item => item.dataset.id);
                ordemImagensInput.value = JSON.stringify(order);
            }

            document.querySelectorAll('.image-preview-item').forEach(addDragEvents);
            if (ordemImagensInput) updateImageOrder();

            const openGalleryBtn = document.getElementById('open-gallery-btn');
            const galleryModal = document.getElementById('gallery-modal');
            const closeGalleryBtn = document.getElementById('close-gallery-btn');
            const gallerySearch = document.getElementById('gallery-search');
            const addSelectedImagesBtn = document.getElementById('add-selected-images-btn');

            openGalleryBtn.addEventListener('click', () => galleryModal.classList.remove('hidden'));
            closeGalleryBtn.addEventListener('click', () => galleryModal.classList.add('hidden'));

            window.toggleImageSelection = function(element) {
                element.classList.toggle('selected');
                element.querySelector('.check-icon').classList.toggle('hidden');
            }

            gallerySearch.addEventListener('input', () => {
                const searchTerm = gallerySearch.value.toLowerCase();
                document.querySelectorAll('.gallery-group').forEach(group => {
                    const groupName = group.dataset.groupName;
                    group.style.display = groupName.includes(searchTerm) ? 'block' : 'none';
                });
            });

            addSelectedImagesBtn.addEventListener('click', () => {
                const selectedImages = document.querySelectorAll('.gallery-image-item.selected');
                selectedImages.forEach(selectedImg => {
                    const path = selectedImg.dataset.path;
                    if (document.querySelector(`.image-preview-item[data-path='${path}']`)) {
                        selectedImg.classList.remove('selected');
                        selectedImg.querySelector('.check-icon').classList.add('hidden');
                        return;
                    }
                    const item = createPreviewElement(`../../${path}`, `url:${path}`, path);
                    previewContainer.appendChild(item);
                    selectedImg.classList.remove('selected');
                    selectedImg.querySelector('.check-icon').classList.add('hidden');
                });
                updateImageOrder();
                galleryModal.classList.add('hidden');
            });
        });
    </script>
</body>

</html>