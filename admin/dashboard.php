<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// --- LÓGICA DE FILTROS, PESQUISA E ORDENAÇÃO ---
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filter_brand = $_GET['brand'] ?? 'all';
$filter_sort = $_GET['sort'] ?? 'sku_asc';
$search_term = $_GET['search'] ?? '';
$filter_stock = $_GET['stock_status'] ?? 'all';

$baseSql = "FROM produtos p";
$whereClause = " WHERE 1";
$params = [];

if ($filter_brand !== 'all') {
    $whereClause .= " AND p.brand = :brand";
    $params[':brand'] = $filter_brand;
}
if (!empty($search_term)) {
    $whereClause .= " AND (p.title LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}
if ($filter_stock === 'in_stock') {
    $whereClause .= " AND p.in_stock = 1";
} elseif ($filter_stock === 'out_of_stock') {
    $whereClause .= " AND p.in_stock = 0";
}

$orderBy = " ORDER BY p.title ASC";
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
    // [NOVA OPÇÃO DE ORDENAÇÃO]
    case 'modificado_desc':
        $orderBy = " ORDER BY p.data_modificacao DESC";
        break;
}

$total_products_stmt = $conn->prepare("SELECT COUNT(p.id) " . $baseSql . $whereClause);
$total_products_stmt->execute($params);
$total_products = $total_products_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

$sql = "SELECT
            p.id, p.brand, p.title, p.sku, p.price, p.promotional_price, p.capacity, p.in_stock, p.condicao,
            (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image,
            (SELECT COUNT(*) FROM produto_promocao pp JOIN promocoes pr ON pp.promocao_id = pr.id WHERE pp.produto_id = p.id AND pr.is_active = 1 AND NOW() BETWEEN pr.data_inicio AND pr.data_fim) as is_in_active_promo
        " . $baseSql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
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

        <?php require_once 'templates/sidebar.php'; ?>

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
                        <button type="button" id="export-csv-btn" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition"><i class="fas fa-file-download mr-2"></i> Exportar Selecionados</button>
                        <a href="forms/form_produto.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition"><i class="fas fa-plus mr-2"></i> Adicionar Produto</a>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <form action="dashboard.php" method="GET" class="space-y-4 text-sm">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-grow">
                                <div class="relative"><input type="text" name="search" id="search" placeholder="Pesquisar por Título ou SKU..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-10 pr-4 py-2 border rounded-lg">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div><label for="brand" class="mr-2">Marca:</label><select name="brand" id="brand" class="border rounded-md px-2 py-2">
                                        <option value="all">Todas</option><?php $marcas = ['samsung', 'apple', 'xiaomi', 'lg', 'motorola', 'huawei', 'asus', 'lenovo', 'nokia', 'positivo', 'multilaser', 'philco', 'infinix', 'realme'];
                                                                            foreach ($marcas as $marca): ?><option value="<?php echo $marca; ?>" <?php if ($filter_brand == $marca) echo 'selected'; ?>><?php echo ucfirst($marca); ?></option><?php endforeach; ?>
                                    </select></div>
                                <div><label for="stock_status" class="mr-2">Estoque:</label><select name="stock_status" id="stock_status" class="border rounded-md px-2 py-2">
                                        <option value="all" <?php if ($filter_stock == 'all') echo 'selected'; ?>>Todos</option>
                                        <option value="in_stock" <?php if ($filter_stock == 'in_stock') echo 'selected'; ?>>Em Estoque</option>
                                        <option value="out_of_stock" <?php if ($filter_stock == 'out_of_stock') echo 'selected'; ?>>Sem Estoque</option>
                                    </select></div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div>
                                    <label for="sort" class="mr-2">Ordenar:</label>
                                    <select name="sort" id="sort" class="border rounded-md px-2 py-2">
                                        <option value="sku_asc" <?php if ($filter_sort == 'sku_asc') echo 'selected'; ?>>Menor SKU</option>
                                        <option value="sku_desc" <?php if ($filter_sort == 'sku_desc') echo 'selected'; ?>>Maior SKU</option>
                                        <option value="modificado_desc" <?php if ($filter_sort == 'modificado_desc') echo 'selected'; ?>>Modificados Recentemente</option>
                                        <option value="title_asc" <?php if ($filter_sort == 'title_asc') echo 'selected'; ?>>Título (A-Z)</option>
                                        <option value="price_desc" <?php if ($filter_sort == 'price_desc') echo 'selected'; ?>>Maior Preço</option>
                                        <option value="price_asc" <?php if ($filter_sort == 'price_asc') echo 'selected'; ?>>Menor Preço</option>
                                        <option value="capacity_desc" <?php if ($filter_sort == 'capacity_desc') echo 'selected'; ?>>Maior Capacidade</option>
                                        <option value="id_desc" <?php if ($filter_sort == 'id_desc') echo 'selected'; ?>>Mais Recentes (Criação)</option>
                                        <option value="id_asc" <?php if ($filter_sort == 'id_asc') echo 'selected'; ?>>Mais Antigos (Criação)</option>
                                    </select>
                                </div>
                                <div><label for="limit" class="mr-2">Exibir:</label><select name="limit" id="limit" class="border rounded-md px-2 py-2">
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                        <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                        <option value="250" <?php if ($limit == 250) echo 'selected'; ?>>250</option>
                                        <option value="500" <?php if ($limit == 500) echo 'selected'; ?>>500</option>
                                        <option value="9999" <?php if ($limit == 9999) echo 'selected'; ?>>Todos</option>
                                    </select></div>
                            </div>
                            <div class="flex items-center gap-4"><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 w-full md:w-auto">Filtrar</button><a href="dashboard.php" class="text-gray-600 hover:text-gray-800 font-semibold w-full md:w-auto text-center py-2">Limpar</a></div>
                        </div>
                    </form>
                </div>

                <form action="actions/acoes_produto.php" method="POST" id="bulk-actions-form">
                    <input type="hidden" name="acao" id="bulk-action-input" value="deletar_massa">
                    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-10 text-gray-500">Nenhum produto encontrado.</td>
                                    </tr>
                                    <?php else: foreach ($products as $product): ?>
                                        <tr class="<?php echo $product['is_in_active_promo'] ? 'bg-green-50' : ''; ?>">
                                            <td class="p-2 text-center"><input type="checkbox" name="ids[]" value="<?php echo $product['id']; ?>" class="product-checkbox"></td>
                                            <td class="p-2"><img src="../<?php echo htmlspecialchars($product['image'] ?: 'assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="h-12 w-12 object-cover rounded-md" onerror="this.src='../assets/img/placeholder.svg';"></td>
                                            <td class="p-2 text-sm text-gray-700 font-medium">
                                                <a href="forms/form_produto.php?acao=editar&id=<?php echo $product['id']; ?>" target="_blank" class="hover:text-blue-600 hover:underline">
                                                    <?php echo htmlspecialchars($product['title']); ?>
                                                </a>
                                                <div class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                            </td>
                                            <td class="p-2 text-sm">
                                                <?php if ($product['is_in_active_promo'] && isset($product['promotional_price'])): ?>
                                                    <div class="flex flex-col"><span class="line-through text-gray-500 text-xs">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span><span class="font-bold text-green-600">R$ <?php echo number_format($product['promotional_price'], 2, ',', '.'); ?></span></div>
                                                <?php else: ?>
                                                    <div class="flex items-center gap-2 price-editor"><span class="font-semibold text-gray-700">R$</span><input type="text" name="price" value="<?php echo number_format($product['price'], 2, ',', '.'); ?>" data-id="<?php echo $product['id']; ?>" class="w-24 px-2 py-1 border rounded-md"><button type="button" class="text-blue-500 hover:text-blue-700 save-price-btn" title="Salvar Preço"><i class="fas fa-save"></i></button></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-2 text-sm text-center"><?php if ($product['condicao'] == 'novo'): ?><span class="px-2 py-1 font-semibold leading-tight text-blue-700 bg-blue-100 rounded-full">Novo</span><?php else: ?><span class="px-2 py-1 font-semibold leading-tight text-yellow-700 bg-yellow-100 rounded-full">Retirado</span><?php endif; ?></td>
                                            <td class="p-2 text-sm text-center"><?php if ($product['in_stock']): ?><span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full">Em Estoque</span><?php else: ?><span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full">Sem Estoque</span><?php endif; ?></td>
                                            <td class="p-2 text-center">
                                                <a href="<?php echo BASE_URL; ?>produto_detalhes.html?sku=<?php echo $product['sku']; ?>" target="_blank" class="text-purple-500 hover:text-purple-700 mx-2" title="Ver como cliente"><i class="fas fa-eye"></i></a>
                                                <a href="actions/acoes_produto.php?acao=toggle_stock&id=<?php echo $product['id']; ?>&<?php echo http_build_query($_GET); ?>" class="text-gray-500 hover:text-gray-700 mx-2" title="Ativar/Desativar Estoque"><i class="fas fa-power-off"></i></a>
                                                <a href="forms/form_produto.php?acao=editar&id=<?php echo $product['id']; ?>" target="_blank" class="text-blue-500 hover:text-blue-700 mx-2" title="Editar Completo"><i class="fas fa-pencil-alt"></i></a>
                                                <a href="actions/acoes_produto.php?acao=deletar&id=<?php echo $product['id']; ?>" class="text-red-500 hover:text-red-700 mx-2" title="Remover" onclick="return confirm('Tem certeza?');"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-start"><button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed" id="bulk-delete-btn" disabled><i class="fas fa-trash-alt mr-2"></i> Apagar Selecionados</button></div>
                </form>

                <div class="mt-6 flex justify-center items-center text-sm">
                </div>
            </main>
        </div>
    </div>
    <script>
        // O JavaScript continua o mesmo, sem necessidade de alterações.
        document.addEventListener('DOMContentLoaded', function() {
            const bulkDeleteForm = document.getElementById('bulk-actions-form');
            if (bulkDeleteForm) {
                bulkDeleteForm.addEventListener('submit', function(e) {
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

            function toggleDeleteButton() {
                const anyChecked = Array.from(productCheckboxes).some(cb => cb.checked);
                if (bulkDeleteBtn) {
                    bulkDeleteBtn.disabled = !anyChecked;
                }
            }
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    productCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    toggleDeleteButton();
                });
            }
            productCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (!this.checked && selectAllCheckbox) {
                        selectAllCheckbox.checked = false;
                    }
                    toggleDeleteButton();
                });
            });
            toggleDeleteButton();
            const exportBtn = document.getElementById('export-csv-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(productCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                    const tempForm = document.createElement('form');
                    tempForm.method = 'POST';
                    tempForm.action = 'actions/exportar_csv.php';
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
            document.querySelectorAll('.save-price-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const editorDiv = this.closest('.price-editor');
                    const priceInput = editorDiv.querySelector('input[name="price"]');
                    const productId = priceInput.dataset.id;
                    const tempForm = document.createElement('form');
                    tempForm.method = 'POST';
                    tempForm.action = 'actions/acoes_produto.php';
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'acao';
                    actionInput.value = 'editar_preco';
                    tempForm.appendChild(actionInput);
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = productId;
                    tempForm.appendChild(idInput);
                    tempForm.appendChild(priceInput.cloneNode(true));
                    document.body.appendChild(tempForm);
                    tempForm.submit();
                });
            });
        });
    </script>
</body>

</html>