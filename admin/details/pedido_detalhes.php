<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Corrigido
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once '../config.php'; // Corrigido

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pedido_id === 0) {
    header("Location: ../pedidos.php?err=ID inválido."); // Corrigido
    exit();
}

// Busca dados do pedido e do usuário
$stmt_pedido = $conn->prepare("
    SELECT p.*, u.nome, u.sobrenome, u.email, u.telefone, u.cpf, u.endereco 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = :id
");
$stmt_pedido->execute([':id' => $pedido_id]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header("Location: ../pedidos.php?err=Pedido não encontrado."); // Corrigido
    exit();
}

// Busca itens do pedido
$stmt_itens = $conn->prepare("
    SELECT pi.id, pi.produto_sku, pi.quantidade, pi.preco_unitario, pi.preco_promocional_unitario, prod.title 
    FROM pedido_itens pi 
    JOIN produtos prod ON pi.produto_sku = prod.sku 
    WHERE pi.pedido_id = :id
");
$stmt_itens->execute([':id' => $pedido_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

$status_options = ['novo', 'chamou', 'negociando', 'enviado', 'entregue', 'cancelado'];

$lista_produtos_json = json_encode($conn->query("SELECT sku, title FROM produtos")->fetchAll(PDO::FETCH_ASSOC));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Detalhes do Pedido #<?php echo $pedido_id; ?> - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        #search-results {
            max-height: 200px;
            overflow-y: auto;
        }

        .result-item {
            cursor: pointer;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.6);
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">

        <?php require_once '../templates/sidebar.php'; // CAMINHO ATUALIZADO 
        ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Detalhes do Pedido #<?php echo $pedido_id; ?></h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="../logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-gray-600"><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                            <p class="text-gray-600"><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nome'] . ' ' . $pedido['sobrenome']); ?></p>
                            <p class="text-gray-600"><strong>Contato:</strong> <?php echo htmlspecialchars($pedido['email']); ?> | <?php echo htmlspecialchars($pedido['telefone']); ?></p>
                        </div>
                        <form action="../actions/acoes_pedido.php" method="POST" class="flex items-center gap-2"> <input type="hidden" name="acao" value="atualizar_status">
                            <input type="hidden" name="id" value="<?php echo $pedido_id; ?>">
                            <label for="status" class="font-medium">Status:</label>
                            <select name="status" id="status" class="border rounded-md px-2 py-1">
                                <?php foreach ($status_options as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php if ($pedido['status'] == $status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600">Salvar</button>
                        </form>
                    </div>

                    <h3 class="text-lg font-semibold border-t pt-4 mb-4">Itens do Pedido</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <tbody>
                                <?php foreach ($itens as $item):
                                    $preco_final = $item['preco_unitario'];
                                    $total_item = $preco_final * $item['quantidade'];
                                ?>
                                    <tr class="border-b">
                                        <td class="p-2">
                                            <p class="font-medium"><?php echo htmlspecialchars($item['title']); ?></p>
                                            <p class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($item['produto_sku']); ?></p>
                                        </td>
                                        <td class="p-2 text-center"><?php echo $item['quantidade']; ?></td>
                                        <td class="p-2 text-right">R$ <?php echo number_format($preco_final, 2, ',', '.'); ?>
                                            <?php if ($item['preco_promocional_unitario']): ?><span class="text-green-600 text-xs">(Promo)</span><?php endif; ?>
                                        </td>
                                        <td class="p-2 text-right font-medium">R$ <?php echo number_format($total_item, 2, ',', '.'); ?></td>
                                        <td class="p-2 text-center">
                                            <button onclick="openDeleteModal('../actions/acoes_pedido.php?acao=remover_item&item_id=<?php echo $item['id']; ?>&pedido_id=<?php echo $pedido_id; ?>')" class="text-red-500 hover:text-red-700"> <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t mt-6 pt-6">
                        <h3 class="text-lg font-semibold mb-4">Adicionar Item ao Pedido</h3>
                        <form action="../actions/acoes_pedido.php" method="POST" class="flex items-end gap-4"> <input type="hidden" name="acao" value="adicionar_item">
                            <input type="hidden" name="id" value="<?php echo $pedido_id; ?>">
                            <div class="flex-grow">
                                <label for="search-product" class="block text-sm font-medium text-gray-700">Buscar Produto (Nome ou SKU)</label>
                                <input type="text" id="search-product" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                                <input type="hidden" name="sku" id="selected-sku">
                                <div id="search-results" class="border border-gray-300 rounded-md mt-1 bg-white absolute z-10 w-full"></div>
                            </div>
                            <div>
                                <label for="quantidade" class="block text-sm font-medium text-gray-700">Quantidade</label>
                                <input type="number" name="quantidade" id="quantidade" value="1" min="1" class="mt-1 block w-24 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            </div>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Adicionar</button>
                        </form>
                    </div>

                    <div class="mt-6 text-right">
                        <?php if ($pedido['frete_valor'] > 0): ?>
                            <p class="text-gray-600"><strong>Frete (<?php echo htmlspecialchars($pedido['frete_tipo']); ?>):</strong> R$ <?php echo number_format($pedido['frete_valor'], 2, ',', '.'); ?></p>
                        <?php endif; ?>
                        <p class="text-xl font-bold mt-2"><strong>Total Geral:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></p>
                    </div>
                    <div class="mt-8 text-right">
                        <a href="../pedidos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg transition">Voltar</a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold text-gray-800 text-center">Confirmar Exclusão</h3>
            <p class="text-center text-gray-600 my-4">Tem certeza de que deseja remover este item do pedido? Esta ação não pode ser desfeita.</p>
            <div class="flex justify-center gap-4 mt-6">
                <button id="cancelDelete" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancelar</button>
                <a id="confirmDeleteLink" href="#" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Sim, Remover</a>
            </div>
        </div>
    </div>

    <script>
        // O JavaScript continua igual, pois as referências são por ID
        const produtos = <?php echo $lista_produtos_json; ?>;
        const searchInput = document.getElementById('search-product');
        const resultsContainer = document.getElementById('search-results');
        const skuInput = document.getElementById('selected-sku');
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            resultsContainer.innerHTML = '';
            if (query.length < 2) return;
            const filtered = produtos.filter(p => p.title.toLowerCase().includes(query) || p.sku.toLowerCase().includes(query));
            filtered.slice(0, 5).forEach(p => {
                const div = document.createElement('div');
                div.innerHTML = `${p.title} <span class="text-xs text-gray-500">(SKU: ${p.sku})</span>`;
                div.className = 'p-2 hover:bg-gray-100 result-item';
                div.onclick = () => {
                    searchInput.value = p.title;
                    skuInput.value = p.sku;
                    resultsContainer.innerHTML = '';
                };
                resultsContainer.appendChild(div);
            });
        });
        const deleteModal = document.getElementById('deleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const confirmDeleteLink = document.getElementById('confirmDeleteLink');

        function openDeleteModal(deleteUrl) {
            confirmDeleteLink.href = deleteUrl;
            deleteModal.classList.remove('hidden');
        }
        cancelDeleteBtn.addEventListener('click', () => {
            deleteModal.classList.add('hidden');
        });
        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                deleteModal.classList.add('hidden');
            }
        });
    </script>
</body>

</html>