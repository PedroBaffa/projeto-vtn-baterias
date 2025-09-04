<?php

/**
 * @file
 * Dashboard principal do painel de administração.
 * Exibe uma lista paginada e filtrável de todos os produtos cadastrados,
 * permitindo ações como edição, exclusão e gerenciamento de estoque.
 */

// Configurações para exibir todos os erros (útil durante o desenvolvimento).
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar esta página.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém e sanitiza o nome de usuário da sessão para exibição.
$username = htmlspecialchars($_SESSION['username']);
// Inclui a conexão com o banco de dados.
require_once 'config.php';

// --- LÓGICA DE FILTROS, PESQUISA E ORDENAÇÃO ---

// 1. Parâmetros de Paginação: Define quantos itens por página e qual a página atual.
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit; // Calcula o ponto de partida para a query SQL.

// 2. Parâmetros de Filtragem e Busca: Obtém os valores dos filtros da URL.
$filter_brand = $_GET['brand'] ?? 'all';
$filter_sort = $_GET['sort'] ?? 'sku_asc';
$search_term = $_GET['search'] ?? '';
$filter_stock = $_GET['stock_status'] ?? 'all';

// 3. Construção Dinâmica da Query SQL
// A query é montada em partes para incorporar os filtros de forma segura.
$baseSql = "FROM produtos p";
$whereClause = " WHERE 1"; // "WHERE 1" é um truque para facilitar a adição de cláusulas AND.
$params = []; // Array para armazenar os valores que serão usados nos prepared statements.

// Adiciona filtro de marca à query se uma marca específica for selecionada.
if ($filter_brand !== 'all') {
    $whereClause .= " AND p.brand = :brand";
    $params[':brand'] = $filter_brand;
}

// Adiciona filtro de pesquisa se um termo for digitado.
if (!empty($search_term)) {
    $whereClause .= " AND (p.title LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}

// Adiciona filtro de status de estoque.
if ($filter_stock === 'in_stock') {
    $whereClause .= " AND p.in_stock = 1";
} elseif ($filter_stock === 'out_of_stock') {
    $whereClause .= " AND p.in_stock = 0";
}

// Define a ordenação dos resultados.
$orderBy = " ORDER BY p.title ASC"; // Padrão
switch ($filter_sort) {
    case 'price_desc':
        $orderBy = " ORDER BY p.price DESC";
        break;
    case 'price_asc':
        $orderBy = " ORDER BY p.price ASC";
        break;
    case 'capacity_desc':
        $orderBy = " ORDER BY p.capacity DESC";
        break;
    case 'id_desc':
        $orderBy = " ORDER BY p.id DESC";
        break;
    case 'id_asc':
        $orderBy = " ORDER BY p.id ASC";
        break;
    case 'sku_desc':
        $orderBy = " ORDER BY p.sku DESC";
        break;
    case 'sku_asc':
        $orderBy = " ORDER BY p.sku ASC";
        break;
}

// 4. Execução das Queries

// Primeira query: Conta o número total de produtos que correspondem aos filtros (para a paginação).
$total_products_stmt = $conn->prepare("SELECT COUNT(p.id) " . $baseSql . $whereClause);
$total_products_stmt->execute($params);
$total_products = $total_products_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Segunda query: Busca os produtos para a página atual, aplicando todos os filtros, ordenação e limites.
$sql = "SELECT
            p.id, p.brand, p.title, p.sku, p.price, p.promotional_price, p.capacity, p.in_stock, p.condicao,
            -- Subquery para buscar a primeira imagem do produto (ordenada por 'ordem').
            (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image,
            -- Subquery para verificar se o produto está em alguma promoção ativa no momento.
            (SELECT COUNT(*) FROM produto_promocao pp JOIN promocoes pr ON pp.promocao_id = pr.id WHERE pp.produto_id = p.id AND pr.is_active = 1 AND NOW() BETWEEN pr.data_inicio AND pr.data_fim) as is_in_active_promo
        " . $baseSql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);

// Associa os parâmetros dos filtros à query (prevenção de SQL Injection).
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Produtos - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <div class="w-64 bg-white shadow-md sticky top-0 h-screen">
            <div class="p-6 text-center border-b"><a href="dashboard.php"><img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12"></a></div>
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
                <h1 class="text-2xl font-semibold text-gray-700">Gerenciar Produtos</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Lista de Produtos (<?php echo $total_products; ?> total)</h2>
                    <div class="flex items-center gap-4">
                        <button type="button" id="export-csv-btn" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition">
                            <i class="fas fa-file-download mr-2"></i> Exportar Selecionados
                        </button>
                        <a href="form_produto.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition"><i class="fas fa-plus mr-2"></i> Adicionar Produto</a>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <form action="dashboard.php" method="GET" class="space-y-4 text-sm">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-grow">
                                <label for="search" class="sr-only">Pesquisar</label>
                                <div class="relative">
                                    <input type="text" name="search" id="search" placeholder="Pesquisar por Título ou SKU..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div>
                                    <label for="brand" class="mr-2 text-gray-600 font-medium">Marca:</label>
                                    <select name="brand" id="brand" class="border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="all">Todas</option>
                                        <?php $marcas = ['samsung', 'apple', 'xiaomi', 'lg', 'motorola', 'huawei', 'asus', 'lenovo', 'nokia', 'positivo', 'multilaser', 'philco', 'infinix'];
                                        foreach ($marcas as $marca) : ?>
                                            <option value="<?php echo $marca; ?>" <?php if ($filter_brand == $marca) echo 'selected'; ?>><?php echo ucfirst($marca); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="stock_status" class="mr-2 text-gray-600 font-medium">Estoque:</label>
                                    <select name="stock_status" id="stock_status" class="border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="all" <?php if ($filter_stock == 'all') echo 'selected'; ?>>Todos</option>
                                        <option value="in_stock" <?php if ($filter_stock == 'in_stock') echo 'selected'; ?>>Em Estoque</option>
                                        <option value="out_of_stock" <?php if ($filter_stock == 'out_of_stock') echo 'selected'; ?>>Sem Estoque</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div>
                                    <label for="sort" class="mr-2 text-gray-600 font-medium">Ordenar:</label>
                                    <select name="sort" id="sort" class="border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="sku_asc" <?php if ($filter_sort == 'sku_asc') echo 'selected'; ?>>Menor SKU</option>
                                        <option value="sku_desc" <?php if ($filter_sort == 'sku_desc') echo 'selected'; ?>>Maior SKU</option>
                                        <option value="title_asc" <?php if ($filter_sort == 'title_asc') echo 'selected'; ?>>Título (A-Z)</option>
                                        <option value="price_desc" <?php if ($filter_sort == 'price_desc') echo 'selected'; ?>>Maior Preço</option>
                                        <option value="price_asc" <?php if ($filter_sort == 'price_asc') echo 'selected'; ?>>Menor Preço</option>
                                        <option value="capacity_desc" <?php if ($filter_sort == 'capacity_desc') echo 'selected'; ?>>Maior Capacidade</option>
                                        <option value="id_desc" <?php if ($filter_sort == 'id_desc') echo 'selected'; ?>>Mais Recentes</option>
                                        <option value="id_asc" <?php if ($filter_sort == 'id_asc') echo 'selected'; ?>>Mais Antigos</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="limit" class="mr-2 text-gray-600 font-medium">Exibir:</label>
                                    <select name="limit" id="limit" class="border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                        <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                        <option value="250" <?php if ($limit == 250) echo 'selected'; ?>>250</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 w-full md:w-auto">Filtrar</button>
                                <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 font-semibold w-full md:w-auto text-center py-2">Limpar</a>
                            </div>
                        </div>
                    </form>
                </div>

                <form action="acoes_produto.php" method="POST" id="bulk-actions-form">
                    <input type="hidden" name="acao" id="bulk-action-input" value="deletar_massa">
                    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="p-2 text-center w-12"><input type="checkbox" id="select-all"></th>
                                    <th class="p-2 text-left text-xs font-semibold text-gray-600 uppercase">Imagem</th>
                                    <th class="p-2 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                                    <th class="p-2 text-left text-xs font-semibold text-gray-600 uppercase">Preço</th>
                                    <th class="p-2 text-center text-xs font-semibold text-gray-600 uppercase">Condição</th>
                                    <th class="p-2 text-center text-xs font-semibold text-gray-600 uppercase">Estoque</th>
                                    <th class="p-2 text-center text-xs font-semibold text-gray-600 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($products)) : ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-10 text-gray-500">Nenhum produto encontrado.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($products as $product) : ?>
                                        <tr class="<?php echo $product['is_in_active_promo'] ? 'bg-green-50' : ''; ?>">
                                            <td class="p-2 text-center"><input type="checkbox" name="ids[]" value="<?php echo $product['id']; ?>" class="product-checkbox"></td>
                                            <td class="p-2"><img src="../<?php echo htmlspecialchars($product['image'] ?: 'assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="h-12 w-12 object-cover rounded-md" onerror="this.src='../assets/img/placeholder.svg';"></td>
                                            <td class="p-2 text-sm text-gray-700 font-medium">
                                                <?php echo htmlspecialchars($product['title']); ?>
                                                <div class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                            </td>
                                            <td class="p-2 text-sm">
                                                <?php if ($product['is_in_active_promo'] && isset($product['promotional_price'])) : ?>
                                                    <div class="flex flex-col">
                                                        <span class="line-through text-gray-500 text-xs">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                                                        <span class="font-bold text-green-600">R$ <?php echo number_format($product['promotional_price'], 2, ',', '.'); ?></span>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="flex items-center gap-2 price-editor">
                                                        <span class="font-semibold text-gray-700">R$</span>
                                                        <input type="text" name="price" value="<?php echo number_format($product['price'], 2, ',', '.'); ?>" data-id="<?php echo $product['id']; ?>" class="w-24 px-2 py-1 border border-gray-300 rounded-md">
                                                        <button type="button" class="text-blue-500 hover:text-blue-700 save-price-btn" title="Salvar Preço"><i class="fas fa-save"></i></button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-2 text-sm text-center">
                                                <?php if ($product['condicao'] == 'novo') : ?>
                                                    <span class="px-2 py-1 font-semibold leading-tight text-blue-700 bg-blue-100 rounded-full">Novo</span>
                                                <?php else : ?>
                                                    <span class="px-2 py-1 font-semibold leading-tight text-yellow-700 bg-yellow-100 rounded-full">Retirado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-2 text-sm text-center">
                                                <?php if ($product['in_stock']) : ?><span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full">Em Estoque</span>
                                                <?php else : ?><span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full">Sem Estoque</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-2 text-center">
                                                <a href="acoes_produto.php?acao=toggle_stock&id=<?php echo $product['id']; ?>&<?php echo http_build_query($_GET); ?>" class="text-gray-500 hover:text-gray-700 mx-2" title="Ativar/Desativar Estoque"><i class="fas fa-power-off"></i></a>
                                                <a href="form_produto.php?acao=editar&id=<?php echo $product['id']; ?>" class="text-blue-500 hover:text-blue-700 mx-2" title="Editar Completo"><i class="fas fa-pencil-alt"></i></a>
                                                <a href="acoes_produto.php?acao=deletar&id=<?php echo $product['id']; ?>" class="text-red-500 hover:text-red-700 mx-2" title="Remover" onclick="return confirm('Tem certeza?');"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-start"><button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed" id="bulk-delete-btn" disabled><i class="fas fa-trash-alt mr-2"></i> Apagar Selecionados</button></div>
                </form>

                <div class="mt-6 flex justify-center items-center text-sm">
                    <?php if ($total_pages > 1) : ?>
                        <nav class="flex items-center space-x-1">
                            <?php $queryString = http_build_query(array_merge($_GET, ['page' => $page - 1])); ?><a href="?<?php echo $queryString; ?>" class="px-3 py-2 rounded-md <?php echo ($page <= 1) ? 'text-gray-400 bg-gray-200 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-100'; ?>">Anterior</a>
                            <?php for ($i = 1; $i <= $total_pages; $i++) :
                                $queryString = http_build_query(array_merge($_GET, ['page' => $i])); ?><a href="?<?php echo $queryString; ?>" class="px-3 py-2 rounded-md <?php echo ($page == $i) ? 'text-white bg-green-600' : 'text-gray-700 bg-white hover:bg-gray-100'; ?>"><?php echo $i; ?></a><?php endfor; ?>
                            <?php $queryString = http_build_query(array_merge($_GET, ['page' => $page + 1])); ?><a href="?<?php echo $queryString; ?>" class="px-3 py-2 rounded-md <?php echo ($page >= $total_pages) ? 'text-gray-400 bg-gray-200 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-100'; ?>">Próximo</a>
                        </nav>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bulkDeleteForm = document.getElementById('bulk-actions-form');
            if (bulkDeleteForm) {
                bulkDeleteForm.addEventListener('submit', function(e) {
                    // Garante que a confirmação só apareça para a ação de apagar em massa.
                    if (document.activeElement.id === 'bulk-delete-btn' || (e.submitter && e.submitter.id === 'bulk-delete-btn')) {
                        if (!confirm('Tem certeza que deseja apagar os produtos selecionados?')) {
                            e.preventDefault();
                        }
                    }
                });
            }

            const selectAllCheckbox = document.getElementById('select-all');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');

            // Habilita ou desabilita o botão de apagar em massa se algum checkbox estiver marcado.
            function toggleDeleteButton() {
                const anyChecked = Array.from(productCheckboxes).some(cb => cb.checked);
                if (bulkDeleteBtn) {
                    bulkDeleteBtn.disabled = !anyChecked;
                }
            }

            // Lógica para o checkbox "Selecionar Todos".
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    productCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    toggleDeleteButton();
                });
            }

            // Lógica para os checkboxes individuais.
            productCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (!this.checked && selectAllCheckbox) {
                        selectAllCheckbox.checked = false;
                    }
                    toggleDeleteButton();
                });
            });
            toggleDeleteButton(); // Verifica o estado inicial ao carregar a página.

            // Lógica para o botão de exportar CSV.
            const exportBtn = document.getElementById('export-csv-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(productCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                    // Cria um formulário temporário para enviar os IDs dos produtos selecionados.
                    const tempForm = document.createElement('form');
                    tempForm.method = 'POST';
                    tempForm.action = 'exportar_csv.php';
                    // Se algum produto foi selecionado, envia os IDs. Se não, o form é enviado vazio e o back-end exporta todos.
                    if (selectedIds.length > 0) {
                        const idsInput = document.createElement('input');
                        idsInput.type = 'hidden';
                        idsInput.name = 'ids';
                        idsInput.value = selectedIds.join(',');
                        tempForm.appendChild(idsInput);
                    }
                    document.body.appendChild(tempForm);
                    tempForm.submit();
                    document.body.removeChild(tempForm);
                });
            }

            // Lógica para a edição rápida de preço na tabela.
            document.querySelectorAll('.save-price-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const editorDiv = this.closest('.price-editor');
                    const priceInput = editorDiv.querySelector('input[name="price"]');
                    const productId = priceInput.dataset.id;

                    // Cria um formulário temporário para enviar os dados da edição rápida.
                    const tempForm = document.createElement('form');
                    tempForm.method = 'POST';
                    tempForm.action = 'acoes_produto.php';

                    // Campo oculto para definir a ação no back-end.
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'acao';
                    actionInput.value = 'editar_preco';
                    tempForm.appendChild(actionInput);

                    // Campo oculto com o ID do produto.
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = productId;
                    tempForm.appendChild(idInput);

                    // Adiciona o campo de preço ao formulário.
                    tempForm.appendChild(priceInput.cloneNode(true));

                    document.body.appendChild(tempForm);
                    tempForm.submit();
                });
            });
        });
    </script>
</body>

</html>