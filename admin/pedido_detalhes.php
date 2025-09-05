<?php

/**
 * @file pedido_detalhes.php
 * Painel administrativo para visualização e gerenciamento de um pedido específico.
 *
 * Esta página exibe informações detalhadas de um pedido, incluindo dados do cliente
 * e a lista de itens. Permite ao administrador alterar o status do pedido,
 * adicionar novos itens e remover itens existentes.
 */

// Inicia a sessão para verificar a autenticação do administrador.
session_start();
// Se o administrador não estiver logado, redireciona para a página de login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Obtém o nome do admin da sessão para exibição (com proteção XSS).
$username = htmlspecialchars($_SESSION['username']);
// Inclui a configuração do banco de dados.
require_once 'config.php';

// --- Validação e Obtenção do ID do Pedido ---
// Pega o 'id' da URL, converte para inteiro e define 0 como padrão se não existir.
$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Se o ID for inválido (0), redireciona de volta para a lista de pedidos.
if ($pedido_id === 0) {
    header("Location: pedidos.php?err=ID inválido.");
    exit();
}

// --- Busca de Dados do Pedido e do Cliente ---
// Prepara uma query segura para buscar os dados do pedido e do usuário associado.
$stmt_pedido = $conn->prepare("
    SELECT p.*, u.nome, u.sobrenome, u.email, u.telefone, u.cpf, u.endereco 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = :id
");
$stmt_pedido->execute([':id' => $pedido_id]);
$pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC); // Busca um único resultado.

// Se nenhum pedido for encontrado com o ID fornecido, redireciona com uma mensagem de erro.
if (!$pedido) {
    header("Location: pedidos.php?err=Pedido não encontrado.");
    exit();
}

// --- Busca dos Itens do Pedido ---
// Prepara uma query para buscar todos os itens pertencentes a este pedido.
$stmt_itens = $conn->prepare("
    SELECT pi.id, pi.produto_sku, pi.quantidade, pi.preco_unitario, pi.preco_promocional_unitario, prod.title 
    FROM pedido_itens pi 
    JOIN produtos prod ON pi.produto_sku = prod.sku 
    WHERE pi.pedido_id = :id
");
$stmt_itens->execute([':id' => $pedido_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC); // Busca todos os itens.

// Array com as opções de status possíveis para um pedido.
$status_options = ['novo', 'chamou', 'negociando', 'enviado', 'entregue', 'cancelado'];

// --- Prepara Dados para o JavaScript ---
// Busca todos os produtos (SKU e Título) para alimentar o autocomplete da busca de produtos.
// O resultado é convertido para JSON para ser facilmente lido pelo JavaScript.
$lista_produtos_json = json_encode($conn->query("SELECT sku, title FROM produtos")->fetchAll(PDO::FETCH_ASSOC));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Detalhes do Pedido #<?php echo $pedido_id; ?> - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Estilos para a caixa de resultados da busca */
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
        <div class="w-64 bg-white shadow-md sticky top-0 h-screen">
        </div>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Detalhes do Pedido #<?php echo $pedido_id; ?></h1>
            </header>

            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nome'] . ' ' . $pedido['sobrenome']); ?></p>
                            <p><strong>Contato:</strong> <?php echo htmlspecialchars($pedido['email']); ?> | <?php echo htmlspecialchars($pedido['telefone']); ?></p>
                        </div>
                        <form action="acoes_pedido.php" method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="acao" value="atualizar_status">
                            <input type="hidden" name="id" value="<?php echo $pedido_id; ?>">
                            <label for="status" class="font-medium">Status:</label>
                            <select name="status" id="status" class="border rounded-md px-2 py-1">
                                <?php foreach ($status_options as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php if ($pedido['status'] == $status) echo 'selected'; ?>>
                                        <?php echo ucfirst($status); ?>
                                    </option>
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
                                        <td class="p-2 text-right">R$ <?php echo number_format($preco_final, 2, ',', '.'); ?></td>
                                        <td class="p-2 text-right font-medium">R$ <?php echo number_format($total_item, 2, ',', '.'); ?></td>
                                        <td class="p-2 text-center">
                                            <button onclick="openDeleteModal('acoes_pedido.php?acao=remover_item&item_id=<?php echo $item['id']; ?>&pedido_id=<?php echo $pedido_id; ?>')" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t mt-6 pt-6">
                        <h3 class="text-lg font-semibold mb-4">Adicionar Item ao Pedido</h3>
                        <form action="acoes_pedido.php" method="POST" class="flex items-end gap-4">
                            <input type="hidden" name="acao" value="adicionar_item">
                            <input type="hidden" name="id" value="<?php echo $pedido_id; ?>">
                            <div class="flex-grow">
                                <label for="search-product">Buscar Produto (Nome ou SKU)</label>
                                <input type="text" id="search-product" class="mt-1 block w-full border rounded-md p-2">
                                <input type="hidden" name="sku" id="selected-sku">
                                <div id="search-results" class="border rounded-md mt-1 bg-white"></div>
                            </div>
                            <div>
                                <label for="quantidade">Quantidade</label>
                                <input type="number" name="quantidade" id="quantidade" value="1" min="1" class="mt-1 block w-24 border rounded-md p-2">
                            </div>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Adicionar</button>
                        </form>
                    </div>

                    <div class="mt-6 text-right">
                        <p><strong>Total Geral:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold text-center">Confirmar Exclusão</h3>
            <p class="text-center my-4">Tem certeza de que deseja remover este item?</p>
            <div class="flex justify-center gap-4 mt-6">
                <button id="cancelDelete" class="bg-gray-300 hover:bg-gray-400 font-bold py-2 px-4 rounded-lg">Cancelar</button>
                <a id="confirmDeleteLink" href="#" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Sim, Remover</a>
            </div>
        </div>
    </div>

    <script>
        // --- SCRIPT DO AUTOCOMPLETE DE PRODUTOS ---
        // Pega a lista de produtos que foi passada pelo PHP.
        const produtos = <?php echo $lista_produtos_json; ?>;
        const searchInput = document.getElementById('search-product');
        const resultsContainer = document.getElementById('search-results');
        const skuInput = document.getElementById('selected-sku');

        // Evento que dispara a cada tecla digitada no campo de busca.
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            resultsContainer.innerHTML = ''; // Limpa resultados anteriores
            if (query.length < 2) return; // Só busca com 2+ caracteres

            // Filtra a lista de produtos para encontrar correspondências no título ou no SKU.
            const filtered = produtos.filter(p => p.title.toLowerCase().includes(query) || p.sku.toLowerCase().includes(query));

            // Exibe os primeiros 5 resultados encontrados.
            filtered.slice(0, 5).forEach(p => {
                const div = document.createElement('div');
                div.innerHTML = `${p.title} <span class="text-xs text-gray-500">(SKU: ${p.sku})</span>`;
                div.className = 'p-2 hover:bg-gray-100 result-item';
                // Ao clicar em um resultado...
                div.onclick = () => {
                    searchInput.value = p.title; // Preenche o campo de busca com o nome do produto.
                    skuInput.value = p.sku; // Preenche o campo oculto com o SKU.
                    resultsContainer.innerHTML = ''; // Limpa a lista de resultados.
                };
                resultsContainer.appendChild(div);
            });
        });

        // --- SCRIPT DO MODAL DE CONFIRMAÇÃO DE EXCLUSÃO ---
        const deleteModal = document.getElementById('deleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const confirmDeleteLink = document.getElementById('confirmDeleteLink');

        // Função para abrir o modal. Ela recebe a URL de exclusão específica do item.
        function openDeleteModal(deleteUrl) {
            confirmDeleteLink.href = deleteUrl; // Define o link do botão "Sim, Remover".
            deleteModal.classList.remove('hidden'); // Mostra o modal.
        }

        // Evento para o botão "Cancelar", que esconde o modal.
        cancelDeleteBtn.addEventListener('click', () => {
            deleteModal.classList.add('hidden');
        });

        // Evento que fecha o modal se o usuário clicar na área escura fora dele.
        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                deleteModal.classList.add('hidden');
            }
        });
    </script>
</body>

</html>