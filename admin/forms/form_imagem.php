<?php
// Arquivo: admin/forms/form_imagem.php (Caminhos Corrigidos)

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Corrigido
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once '../config.php'; // Corrigido

// Valida o ID do produto recebido via URL
$produto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($produto_id === 0) {
    header("Location: ../galeria.php?err=Produto inválido."); // Corrigido
    exit();
}

// Busca os dados do produto e suas imagens
try {
    $stmt_produto = $conn->prepare("SELECT id, title, sku FROM produtos WHERE id = :id");
    $stmt_produto->execute([':id' => $produto_id]);
    $produto = $stmt_produto->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        header("Location: ../galeria.php?err=Produto não encontrado."); // Corrigido
        exit();
    }

    $stmt_imagens = $conn->prepare("SELECT id, image_path FROM produto_imagens WHERE produto_id = :id ORDER BY ordem ASC");
    $stmt_imagens->execute([':id' => $produto_id]);
    $imagens = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar dados do produto: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Editar Imagens - <?php echo htmlspecialchars($produto['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .image-uploader {
            border: 2px dashed #cbd5e1;
            border-radius: 0.5rem;
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
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">

        <?php require_once '../templates/sidebar.php'; // CAMINHO ATUALIZADO 
        ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Gerenciar Imagens</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="../logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">
                    <div class="mb-6 border-b pb-4">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($produto['title']); ?></h2>
                        <p class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($produto['sku']); ?></p>
                    </div>

                    <form action="../actions/acoes_imagem.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="salvar_imagens_produto">
                        <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">

                        <div>
                            <label class="block text-gray-600 font-medium mb-2">Imagens do Produto (arraste para reordenar)</label>

                            <div id="image-preview-container" class="grid grid-cols-3 sm:grid-cols-5 gap-4 mb-4 min-h-[8rem] bg-gray-50 p-4 rounded-lg">
                                <?php foreach ($imagens as $img): ?>
                                    <div class="image-preview-item" draggable="true" data-id="<?php echo $img['id']; ?>">
                                        <img src="../../<?php echo htmlspecialchars($img['image_path']); ?>" class="h-24 w-24 object-cover rounded-lg">
                                        <div class="delete-btn font-bold" onclick="removeImage(this)">&times;</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div id="drop-area" class="image-uploader flex flex-col items-center justify-center p-6 text-center cursor-pointer">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                                <p class="text-gray-500 mt-2">Arraste e solte as imagens aqui, ou clique para selecionar</p>
                            </div>

                            <input type="file" id="image-input" name="novas_imagens[]" multiple accept="image/*" class="hidden">
                            <input type="hidden" name="imagens_removidas" id="imagens-removidas-input" value="">
                            <input type="hidden" name="ordem_imagens" id="ordem-imagens-input" value="">
                        </div>

                        <div class="mt-8 flex justify-end">
                            <a href="../galeria.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg mr-4">Cancelar</a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // O JavaScript continua igual, pois não depende de caminhos de ficheiros
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
                Array.from(files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            const newImage = createPreviewElement(e.target.result);
                            previewContainer.appendChild(newImage);
                            updateImageOrder();
                        };
                        reader.readAsDataURL(file);
                    }
                });
                const dataTransfer = new DataTransfer();
                Array.from(files).forEach(file => dataTransfer.items.add(file));
                imageInput.files = dataTransfer.files;
            }

            function createPreviewElement(src) {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.draggable = true;
                item.dataset.id = 'new';
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
                if (id !== 'new' && !imagensRemovidas.includes(id)) {
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
                const order = Array.from(items).map(item => item.dataset.id || 'new');
                ordemImagensInput.value = JSON.stringify(order);
            }
            document.querySelectorAll('.image-preview-item').forEach(addDragEvents);
            updateImageOrder();
        });
    </script>
</body>

</html>