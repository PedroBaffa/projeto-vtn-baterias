<?php

/**
 * @file
 * Formulário para adicionar um novo produto ou editar um existente.
 * A página adapta seu conteúdo e comportamento com base na ação (editar ou adicionar).
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição.
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// --- LÓGICA DE MODO (ADICIONAR VS. EDITAR) ---

// Verifica se a URL contém os parâmetros que indicam o modo de edição.
$modo_edicao = (isset($_GET['acao']) && $_GET['acao'] == 'editar' && isset($_GET['id']));

// Inicializa um array com a estrutura de um produto e valores padrão.
// Isso evita erros no formulário ao tentar acessar chaves de um array inexistente no modo "adicionar".
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

// Se estiver em modo de edição, busca todas as informações do produto no banco de dados.
if ($modo_edicao) {
    $id_produto = (int) $_GET['id'];
    try {
        // 1. Busca os dados principais do produto.
        $stmt_produto = $conn->prepare("SELECT * FROM produtos WHERE id = :id");
        $stmt_produto->execute([':id' => $id_produto]);
        $produto_existente = $stmt_produto->fetch(PDO::FETCH_ASSOC);

        // Se o produto com o ID fornecido não existir, redireciona de volta.
        if (!$produto_existente) {
            header("Location: dashboard.php?err=Produto não encontrado.");
            exit();
        }

        // 2. Verifica se o produto está atualmente em uma promoção ativa.
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

        // 3. Busca todas as imagens associadas a este produto, na ordem correta.
        $stmt_imagens = $conn->prepare("SELECT id, image_path FROM produto_imagens WHERE produto_id = :produto_id ORDER BY ordem ASC");
        $stmt_imagens->execute([':produto_id' => $id_produto]);
        $imagens_existentes = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erro ao buscar dados do produto: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo $modo_edicao ? 'Editar' : 'Adicionar'; ?> Produto - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
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
            /* Indica que o item é arrastável */
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
        <div class="w-64 bg-white shadow-md sticky top-0 h-screen">
            <div class="p-6 text-center border-b"><a href="visao_geral.php"><img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12"></a></div>
            <nav class="mt-4">
                <a href="visao_geral.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></a>
                <a href="dashboard.php" class="flex items-center py-2 px-6 bg-gray-200 text-gray-800 font-semibold"><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></a>
                <a href="importar.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></a>
                <a href="admins.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></a>
                <a href="galeria.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria</span></a>
                <a href="contatos.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></a>
                <a href="usuarios.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></a>
                <a href="promocoes.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span></a>
            </nav>
        </div>
        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">
                    <?php echo $modo_edicao ? 'Editar' : 'Adicionar Novo'; ?> Produto
                </h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8">
                    <form action="acoes_produto.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="<?php echo $modo_edicao ? 'editar' : 'adicionar'; ?>">
                        <?php if ($modo_edicao) : ?>
                            <input type="hidden" name="id" value="<?php echo $produto_existente['id']; ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div><label for="title" class="block text-gray-600 font-medium mb-2">Título do Produto</label><input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($produto_existente['title']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                                <div><label for="sku" class="block text-gray-600 font-medium mb-2">SKU</label><input type="text" id="sku" name="sku" required value="<?php echo htmlspecialchars($produto_existente['sku']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                                <div><label for="brand" class="block text-gray-600 font-medium mb-2">Marca</label><select id="brand" name="brand" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"><?php $marcas = ['samsung', 'apple', 'xiaomi', 'lg', 'motorola', 'huawei', 'asus', 'lenovo', 'nokia', 'positivo', 'multilaser', 'philco', 'infinix'];
                                                                                                                                                                                                                        foreach ($marcas as $marca): ?><option value="<?php echo $marca; ?>" <?php if ($produto_existente['brand'] == $marca) echo 'selected'; ?>><?php echo ucfirst($marca); ?></option><?php endforeach; ?></select></div>
                                <div><label for="capacity" class="block text-gray-600 font-medium mb-2">Capacidade (mAh)</label><input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($produto_existente['capacity']); ?>" placeholder="Ex: 5000" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                                <div>
                                    <label for="condicao" class="block text-gray-600 font-medium mb-2">Condição do Produto</label>
                                    <select id="condicao" name="condicao" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="novo" <?php if (($produto_existente['condicao'] ?? 'novo') == 'novo') echo 'selected'; ?>>Novo</option>
                                        <option value="retirado" <?php if (($produto_existente['condicao'] ?? '') == 'retirado') echo 'selected'; ?>>Retirado</option>
                                    </select>
                                </div>
                                <div><label for="descricao" class="block text-gray-600 font-medium mb-2">Descrição do Produto</label><textarea id="descricao" name="descricao" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Digite os detalhes do produto aqui..."><?php echo htmlspecialchars($produto_existente['descricao'] ?? ''); ?></textarea></div>
                                <div>
                                    <label for="price" class="block text-gray-600 font-medium mb-2">Preço (ex: 124,90)</label>
                                    <input type="text" id="price" name="price" required value="<?php echo htmlspecialchars(number_format($produto_existente['price'], 2, ',', '.')); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg <?php echo $is_in_active_promo ? 'bg-gray-200 cursor-not-allowed' : ''; ?>" <?php echo $is_in_active_promo ? 'disabled' : ''; ?>>
                                    <?php if ($is_in_active_promo): ?>
                                        <div class="mt-2 p-3 bg-yellow-100 text-yellow-800 border-l-4 border-yellow-500 rounded-md text-sm">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            Este produto está na promoção "<?php echo htmlspecialchars($active_promo_name); ?>". Para editar o preço base, primeiro remova o produto da promoção na <a href="promocoes.php" class="font-bold underline">página de promoções</a>.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-600 font-medium mb-2">Imagens do Produto (até 5, arraste para reordenar)</label>
                                    <div id="image-preview-container" class="grid grid-cols-3 sm:grid-cols-4 gap-4 mb-4 min-h-[8rem]">
                                        <?php foreach ($imagens_existentes as $img): ?>
                                            <div class="image-preview-item" draggable="true" data-id="<?php echo $img['id']; ?>">
                                                <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" class="h-24 w-24 object-cover rounded-lg">
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
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end">
                            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg mr-4">Cancelar</a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">Salvar Produto</button>
                        </div>
                    </form>
                </div>
            </main>
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

            // --- LÓGICA DE UPLOAD E PREVIEW ---
            dropArea.addEventListener('click', () => imageInput.click());
            imageInput.addEventListener('change', () => handleFiles(imageInput.files));

            // Eventos para a funcionalidade de arrastar e soltar (drag-and-drop).
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
                // Adiciona os arquivos arrastados ao input de arquivo.
                imageInput.files = e.dataTransfer.files;
                handleFiles(imageInput.files);
            });

            // Processa os arquivos selecionados ou arrastados.
            function handleFiles(files) {
                const currentImageCount = previewContainer.children.length;
                if (currentImageCount + files.length > 5) {
                    alert('Pode enviar no máximo 5 imagens no total.');
                    imageInput.value = ''; // Limpa a seleção.
                    return;
                }
                // Cria um preview para cada arquivo de imagem.
                Array.from(files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            const newImage = createPreviewElement(e.target.result, null);
                            previewContainer.appendChild(newImage);
                            updateImageOrder();
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Cria o elemento HTML para a pré-visualização da imagem.
            function createPreviewElement(src, id) {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.draggable = true;
                if (id) {
                    item.dataset.id = id; // Armazena o ID da imagem (se for uma imagem existente).
                }

                const img = document.createElement('img');
                img.src = src;
                img.className = 'h-24 w-24 object-cover rounded-lg';

                const deleteBtn = document.createElement('div');
                deleteBtn.className = 'delete-btn font-bold';
                deleteBtn.innerHTML = '&times;';
                deleteBtn.onclick = () => removeImage(deleteBtn);

                item.appendChild(img);
                item.appendChild(deleteBtn);
                addDragEvents(item); // Adiciona os eventos de arrastar ao novo elemento.
                return item;
            }

            // Função global para ser chamada pelo botão de deletar.
            window.removeImage = function(buttonElement) {
                const imageItem = buttonElement.parentElement;
                const id = imageItem.dataset.id;

                // Se a imagem removida for uma que já existia no banco,
                // adiciona seu ID à lista de imagens a serem removidas no back-end.
                if (id) {
                    imagensRemovidas.push(id);
                    imagensRemovidasInput.value = JSON.stringify(imagensRemovidas);
                }

                imageItem.remove(); // Remove o preview da tela.
                updateImageOrder(); // Atualiza a ordem.
            }

            // --- LÓGICA DE REORDENAÇÃO (DRAG-AND-DROP) ---

            // Adiciona os listeners de eventos de arrastar a um item.
            function addDragEvents(item) {
                item.addEventListener('dragstart', () => {
                    draggedItem = item;
                    setTimeout(() => item.style.display = 'none', 0); // Oculta o item original.
                });
                item.addEventListener('dragend', () => {
                    setTimeout(() => {
                        draggedItem.style.display = 'block'; // Mostra o item novamente.
                        draggedItem = null;
                        updateImageOrder(); // Salva a nova ordem.
                    }, 0);
                });
            }

            // Permite que o contêiner receba itens arrastados.
            previewContainer.addEventListener('dragover', e => {
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

            // Encontra a posição correta para inserir o item arrastado.
            function getDragAfterElement(container, x) {
                const draggableElements = [...container.querySelectorAll('.image-preview-item:not([style*="display: none"])')];
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

            // Atualiza o input oculto com a ordem atual das imagens.
            function updateImageOrder() {
                const items = previewContainer.querySelectorAll('.image-preview-item');
                // Cria um array com os IDs das imagens na ordem em que aparecem.
                // Imagens novas são marcadas com "new".
                const order = Array.from(items).map(item => item.dataset.id || 'new');
                ordemImagensInput.value = JSON.stringify(order);
            }

            // Inicializa os eventos de arrastar para as imagens que já existem na página.
            document.querySelectorAll('.image-preview-item').forEach(addDragEvents);
            updateImageOrder(); // Define a ordem inicial.
        });
    </script>
</body>

</html>